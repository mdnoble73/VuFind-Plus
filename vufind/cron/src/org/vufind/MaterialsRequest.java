package org.vufind;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.io.InputStream;
import java.net.URL;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Properties;

import javax.mail.Message;
import javax.mail.Session;
import javax.mail.Transport;
import javax.mail.internet.InternetAddress;
import javax.mail.internet.MimeMessage;

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
	private String vufindDBConnectionInfo;
	private Connection vufindConn = null;
	private String vufindUrl;
	private Logger logger;
	private String installPath;
	private String libraryName;
	private String circulationPhone;
	private String circulationEmail;
	private String circulationUrl;
	private String emailFrom;
	private Emailer emailer;
	
	@Override
	public void doCronProcess(Ini ini, Logger logger) {
		this.logger = logger;
		if (!loadConfig(ini, logger)){
			return;
		}
		emailer = new Emailer(ini, logger);
		
		try {
			//Connect to the vufind database
			vufindConn = DriverManager.getConnection(vufindDBConnectionInfo);
			
			sendEmailNotifications();
			
			generateHolds();
			
			// Disconnect from the database
			vufindConn.close();
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error establishing connection to database ", ex);
			return;
		}
	}
	
	/**
	 * Send notifications to patrons based on actions performed by the cataloging group. 
	 */
	private void sendEmailNotifications(){
		//Get a list of requests that need notifications sent
		try {
			PreparedStatement requestsToEmailStmt = vufindConn.prepareStatement("SELECT * FROM materials_request inner join user on user.id = materials_request.createdBy WHERE emailSent = 0 AND status IN ('owned', 'purchased', 'ILLplaced', 'notEnoughInfo')");
			PreparedStatement setEmailSentStmt = vufindConn.prepareStatement("UPDATE materials_request SET emailSent=1 where id =?");
			ResultSet requestsToEmail = requestsToEmailStmt.executeQuery();
			//For each request, 
			while (requestsToEmail.next()){
				boolean emailSent = true;
				//Get information about the patron to send the notification to
				String requestId = requestsToEmail.getString("id");
				String firstName = requestsToEmail.getString("firstName");
				String lastName = requestsToEmail.getString("lastName");
				String emailTo = requestsToEmail.getString("email");
			
				if (emailTo != null && emailTo.length() > 0){
					//Get the template to use while sending the notification
					String status = requestsToEmail.getString("status");
					File emailTemplate = new File(installPath + "/cron/templates/" + status + ".tpl");
					if (!emailTemplate.exists()){
						emailTemplate = new File(installPath + "/cron/templates/materialsRequest.tpl");
					}
					if (!emailTemplate.exists()){
						logger.error("Could not find template for email text to send to patron");
					}
				
					BufferedReader reader = new BufferedReader(new FileReader(emailTemplate));
					StringBuffer emailBody = new StringBuffer();
					String curLine = reader.readLine();
					while (curLine != null){
						emailBody.append(curLine + "\r\n" );
						curLine = reader.readLine();
					}
					
					String emailBodyStr = emailBody.toString();
					//Replace variables as needed
					emailBodyStr = emailBodyStr.replaceAll("\\{firstName\\}", firstName);
					emailBodyStr = emailBodyStr.replaceAll("\\{lastName\\}", lastName);
					emailBodyStr = emailBodyStr.replaceAll("\\{email\\}", emailTo);
					emailBodyStr = emailBodyStr.replaceAll("\\{status\\}", status);
					if (libraryName != null){
						emailBodyStr = emailBodyStr.replaceAll("\\{libraryName\\}", libraryName);
					}
					if (circulationPhone != null){
						emailBodyStr = emailBodyStr.replaceAll("\\{circulationPhone\\}", circulationPhone);
					}
					if (circulationEmail != null){
						emailBodyStr = emailBodyStr.replaceAll("\\{circulationEmail\\}", circulationEmail);
					}
					if (circulationUrl != null){
						emailBodyStr = emailBodyStr.replaceAll("\\{circulationUrl\\}", circulationUrl);
					}
				
					//Send the email
					String emailSubject = "Your materials request was processed.";
					emailSent = emailer.sendEmail(emailTo, emailFrom, emailSubject, emailBodyStr, null);
					
				}else{
					logger.warn("Not sending email for request " + requestId + " because email field was not set.");
				}
				if (emailSent){
					//Mark that the email was sent
					setEmailSentStmt.setString(1, requestId);
					setEmailSentStmt.executeUpdate();
				}
				
			}
		} catch (Exception e) {
			logger.error("Error sending emails for materials requests", e);
		}
	}

	/**
	 * If a title has been added to the catalog, add 
	 */
	private void generateHolds(){
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
						}
					}else{
						logger.error("Error searching for isbn " + requestIsbn);
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
							}else{
								logger.info("hold could not be created " + result.getString("holdMessage"));
							}
						} catch (JSONException e) {
							logger.error("Unable to load results of placing the hold", e);
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
		}
	}
	
	protected boolean loadConfig(Ini ini, Logger logger) {
		vufindDBConnectionInfo = Util.cleanIniValue(ini.get("Database", "database_vufind_jdbc"));
		if (vufindDBConnectionInfo == null || vufindDBConnectionInfo.length() == 0) {
			logger.error("Database connection information for vufind database not found in General Settings.  Please specify connection information in a database key.");
			return false;
		}
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
