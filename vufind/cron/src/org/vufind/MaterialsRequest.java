package org.vufind;

import java.io.InputStream;
import java.net.URL;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

/**
 * Handles processing background tasks for Materials Requests including 
 * sending emails to patrons and generating holds
 * 
 * Copyright (C) Anythink Libraries 2012.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Anythink Libraries 2012.
 * 
 */
public class MaterialsRequest implements IProcessHandler{
	private Connection vufindConn = null;
	private CronProcessLogEntry processLog;
	private String vufindUrl;
	private Logger logger;
	private String installPath;
	private String libraryName;
	private String circulationPhone;
	private String circulationEmail;
	private String circulationUrl;
	private String emailFrom;
	
	@Override
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Materials Request");
		processLog.saveToDatabase(vufindConn, logger);
		this.logger = logger;
		this.vufindConn = vufindConn;
		if (!loadConfig(configIni, logger)){
			return;
		}
		
		generateHolds();
		
		processLog.setFinished();
		processLog.saveToDatabase(vufindConn, logger);
	}
	
	/**
	 * If a title has been added to the catalog, add 
	 */
	private void generateHolds(){
		processLog.addNote("Generating holds for materials requests that have arrived");
		//Get a list of requests to generate holds for
		try {
			PreparedStatement requestsToEmailStmt = vufindConn.prepareStatement("SELECT materials_request.*, cat_username, cat_password FROM materials_request inner join user on user.id = materials_request.createdBy WHERE placeHoldWhenAvailable = 1 and holdsCreated = 0 and status IN ('owned', 'purchased')");
			PreparedStatement setHoldsCreatedStmt = vufindConn.prepareStatement("UPDATE materials_request SET holdsCreated=1 where id =?");
			ResultSet requestsToCreateHolds = requestsToEmailStmt.executeQuery();
			//For each request, 
			while (requestsToCreateHolds.next()){
				boolean holdCreated = false;
				//Check to see if the title has been received based on the ISBN or OCLC Number
				String requestId = requestsToCreateHolds.getString("id");
				String requestIsbn = requestsToCreateHolds.getString("isbn");
				String requestIssn = requestsToCreateHolds.getString("issn");
				String requestUpc = requestsToCreateHolds.getString("upc");
				String requestOclcNumber = requestsToCreateHolds.getString("oclcNumber");
				String holdPickupLocation = requestsToCreateHolds.getString("holdPickupLocation");
				String cat_username = requestsToCreateHolds.getString("cat_username");
				String cat_password = requestsToCreateHolds.getString("cat_password");
				
				String recordId = null;
				//Search for the isbn 
				if ((requestIsbn != null && requestIsbn.length() > 0) || (requestIssn != null && requestIssn.length() > 0) || (requestUpc != null && requestUpc.length() > 0) || (requestOclcNumber != null && requestOclcNumber.length() > 0)){
					URL searchUrl;
					if (requestIsbn != null && requestIsbn.length() > 0){
						searchUrl = new URL(vufindUrl + "/API/SearchAPI?method=search&lookfor=" + requestIsbn + "&type=isn");
					}else if (requestIssn != null && requestIssn.length() > 0){
						searchUrl = new URL(vufindUrl + "/API/SearchAPI?method=search&lookfor=" + requestIssn + "&type=isn");
					}else if (requestUpc != null && requestUpc.length() > 0){
						searchUrl = new URL(vufindUrl + "/API/SearchAPI?method=search&lookfor=" + requestUpc + "&type=isn");
					}else{
						searchUrl = new URL(vufindUrl + "/API/SearchAPI?method=search&lookfor=oclc" + requestOclcNumber + "&type=allfields");
					}
					Object searchDataRaw = searchUrl.getContent();
					if (searchDataRaw instanceof InputStream) {
						String searchDataJson = Util.convertStreamToString((InputStream) searchDataRaw);
						try {
							JSONObject searchData = new JSONObject(searchDataJson);
							JSONObject result = searchData.getJSONObject("result");
							if (result.getInt("recordCount") > 0){
								//Found a record
								JSONArray recordSet = result.getJSONArray("recordSet");
								JSONObject firstRecord = recordSet.getJSONObject(0);
								recordId = firstRecord.getString("id");
							}
						} catch (JSONException e) {
							logger.error("Unable to load search result", e);
							processLog.incErrors();
							processLog.addNote("Unable to load search result " + e.toString());
						}
					}else{
						logger.error("Error searching for isbn " + requestIsbn);
						processLog.incErrors();
						processLog.addNote("Error searching for isbn " + requestIsbn);
					}
				}
				
				if (recordId != null){
					//Place a hold on the title for the user
					URL placeHoldUrl;
					if (recordId.matches("econtentRecord\\d+")){
						placeHoldUrl = new URL(vufindUrl + "/API/UserAPI?method=placeEContentHold&username=" + cat_username + "&password=" + cat_password + "&recordId=" + recordId);
					}else{
						placeHoldUrl = new URL(vufindUrl + "/API/UserAPI?method=placeHold&username=" + cat_username + "&password=" + cat_password + "&bibId=" + recordId + "&campus=" + holdPickupLocation);
					}
					logger.info("Place Hold URL: " + placeHoldUrl);
					Object placeHoldDataRaw = placeHoldUrl.getContent();
					if (placeHoldDataRaw instanceof InputStream) {
						String placeHoldDataJson = Util.convertStreamToString((InputStream) placeHoldDataRaw);
						try {
							JSONObject placeHoldData = new JSONObject(placeHoldDataJson);
							JSONObject result = placeHoldData.getJSONObject("result");
							holdCreated = result.getBoolean("success");
							if (holdCreated){
								logger.info("hold was created successfully.");
								processLog.incUpdated();
							}else{
								logger.info("hold could not be created " + result.getString("holdMessage"));
								processLog.incErrors();
								processLog.addNote("hold could not be created " + result.getString("holdMessage"));
							}
						} catch (JSONException e) {
							logger.error("Unable to load results of placing the hold", e);
							processLog.incErrors();
							processLog.addNote("Unable to load results of placing the hold " + e.toString());
						}
					}
				}
			
				if (holdCreated){
					//Mark that the hold was created
					setHoldsCreatedStmt.setString(1, requestId);
					setHoldsCreatedStmt.executeUpdate();
				}
			}
			
		} catch (Exception e) {
			logger.error("Error generating holds for purchased requests ", e);
			processLog.incErrors();
			processLog.addNote("Error generating holds for purchased requests " + e.toString());
		}
	}
	
	protected boolean loadConfig(Ini ini, Logger logger) {
		vufindUrl = Util.cleanIniValue(ini.get("Site", "url"));
		
		installPath = ini.get("Site", "installPath");
		if (installPath == null || installPath.length() == 0) {
			logger.error("Local path to vufind installation not found in General Settings.  Please specify location in local key.");
			return false;
		}
		emailFrom = ini.get("MaterialsRequest", "emailFrom");
		if (emailFrom == null || emailFrom.length() == 0) {
			logger.error("Email From address not found in Process Settings.  Please specify host in emailPort key.");
			return false;
		}
		
		libraryName = ini.get("Site", "libraryName");
		if (libraryName == null || libraryName.length() == 0) {
			logger.warn("Library Name not found in Process Settings.  Please specify add libraryName key.");
		}
		circulationPhone = ini.get("MaterialsRequest", "phone");
		if (circulationPhone == null || circulationPhone.length() == 0) {
			logger.warn("Circulation Department Phone Number not found in Process Settings.  Please specify add circulationPhone key.");
		}
		circulationEmail = ini.get("MaterialsRequest", "email");
		if (circulationEmail == null || circulationEmail.length() == 0) {
			logger.warn("Circulation Department Email not found in Process Settings.  Please specify add circulationPhone key.");
		}
		circulationUrl = ini.get("MaterialsRequest", "url");
		if (circulationUrl == null || circulationUrl.length() == 0) {
			logger.warn("Circulation Department not found in Process Settings.  Please specify add circulationUrl key.");
		}
		return true;
	}

}
