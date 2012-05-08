package org.douglascountylibraries;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import org.vufind.CronLogEntry;
import org.vufind.CronProcessLogEntry;
import org.vufind.IProcessHandler;
import org.vufind.Util;


import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.net.MalformedURLException;
import java.net.URL;
import java.net.URLConnection;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Iterator;

public class UpdateReadingHistory implements IProcessHandler {
	private CronProcessLogEntry processLog;
	private PreparedStatement getUsersStmt;
	private PreparedStatement getResourceStmt;
	private PreparedStatement readingHistoryStatement;
	private PreparedStatement insertResourceStmt;
	private PreparedStatement updateReadingHistoryStmt;
	private PreparedStatement insertReadingHistoryStmt;
	private String strandsApid;
	private String vufindUrl;
	private SimpleDateFormat checkoutDateFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
	private Logger logger;
	private boolean loadPrintHistory = true;
	private boolean loadEcontentHistory = true;
	private boolean loadOverdriveHistory = false;
	
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Update Reading History");
		processLog.saveToDatabase(vufindConn, logger);
		
		this.logger = logger;
		logger.info("Updating Reading History");
		processLog.addNote("Updating Reading History");

		vufindUrl = configIni.get("Site", "url");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
			processLog.incErrors();
			processLog.addNote("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
			return;
		}

		strandsApid = configIni.get("Strands", "APID");
		
		String loadPrintSetting = processSettings.get("loadPrint");
		if (loadPrintSetting != null){
			loadPrintHistory = loadPrintSetting.equals("true");
		}
		String loadEcontentSetting = processSettings.get("loadEcontent");
		if (loadEcontentSetting != null){
			loadEcontentHistory = loadEcontentSetting.equals("true");
		}
		String loadOverdriveSetting = processSettings.get("loadOverdrive");
		if (loadOverdriveSetting != null){
			loadOverdriveHistory = loadOverdriveSetting.equals("true");
		}

		// Connect to the VuFind MySQL database
		try {
			// Get a list of all patrons that have reading history turned on.
			getUsersStmt = vufindConn.prepareStatement("SELECT * FROM user where trackReadingHistory=1");
			getResourceStmt = vufindConn.prepareStatement("SELECT * from resource WHERE record_id = ? and source = ?");
			readingHistoryStatement = vufindConn.prepareStatement("SELECT * FROM user_reading_history WHERE userId=? AND resourceId = ?");
			insertResourceStmt = vufindConn.prepareStatement("INSERT into resource (record_id, source) values (?, ?)", Statement.RETURN_GENERATED_KEYS);
			updateReadingHistoryStmt = vufindConn.prepareStatement("UPDATE user_reading_history SET daysCheckedOut=?, lastCheckoutDate=? WHERE userId=? AND resourceId = ?");
			insertReadingHistoryStmt = vufindConn.prepareStatement("INSERT INTO user_reading_history (userId, resourceId, lastCheckoutDate, firstCheckoutDate, daysCheckedOut) VALUES (?, ?, ?, ?, 1)");
			
			ResultSet userResults = getUsersStmt.executeQuery();
			while (userResults.next()) {
				// For each patron
				Long userId = userResults.getLong("id");
				String cat_username = userResults.getString("cat_username");
				String cat_password = userResults.getString("cat_password");
				logger.info("Loading Reading History for patron " + cat_username);
				if (loadPrintHistory){
					processPrintTitles(userId, cat_username, cat_password);
				}
				if (loadEcontentHistory){
					processEContentTitles(userId, cat_username, cat_password);
				}
				if (loadOverdriveHistory){
					processOverDriveTitles(userId, cat_username, cat_password);
				}
				processLog.incUpdated();
				processLog.saveToDatabase(vufindConn, logger);
			}
			userResults.close();
		} catch (SQLException e) {
			logger.error("Unable get a list of users that need to have their reading list updated ", e);
			processLog.incErrors();
			processLog.addNote("Unable get a list of users that need to have their reading list updated " + e.toString());
		}
		
		processLog.setFinished();
		processLog.saveToDatabase(vufindConn, logger);
	}
	
	private void processPrintTitles(Long userId, String cat_username, String cat_password) throws SQLException{
		try {
			// Call the patron API to get their checked out items
			URL patronApiUrl = new URL(vufindUrl + "/API/UserAPI?method=getPatronCheckedOutItems&username=" + cat_username + "&password=" + cat_password + "&includeEContent=false");
			Object patronDataRaw = patronApiUrl.getContent();
			if (patronDataRaw instanceof InputStream) {
				String patronDataJson = Util.convertStreamToString((InputStream) patronDataRaw);
				logger.info(patronApiUrl.toString());
				logger.info("Json for patron checked out items " + patronDataJson);
				try {
					JSONObject patronData = new JSONObject(patronDataJson);
					JSONObject result = patronData.getJSONObject("result");
					if (result.getBoolean("success") && result.has("checkedOutItems") && result.get("checkedOutItems").getClass() == JSONObject.class) {
						JSONObject checkedOutItems = result.getJSONObject("checkedOutItems");
						@SuppressWarnings("unchecked")
						Iterator<String> keys = (Iterator<String>) checkedOutItems.keys();
						while (keys.hasNext()) {
							String curKey = keys.next();
							JSONObject checkedOutItem = checkedOutItems.getJSONObject(curKey);
							// System.out.println(checkedOutItem.toString());
							String bibId = checkedOutItem.getString("id");
							String checkoutDateStr = checkedOutItem.getString("checkoutdate");
							logger.debug(bibId + " : " + checkoutDateStr);

							// Get the resource for this bibId
							long resourceId = getResourceForBib(bibId, "VuFind");
							if (resourceId == -1){
								logger.error("Could not retrieve or create resource for bib Id " + bibId);
								processLog.incErrors();
								processLog.addNote("Could not retrieve or create resource for bib Id " + bibId);
								continue;
							}else{
								// Update the reading history
								try {
									Date checkoutDate = checkoutDateFormat.parse(checkoutDateStr);
									updateReadingHistory(userId, bibId, resourceId, checkoutDate);
									processLog.incUpdated();
								} catch (ParseException e) {
									logger.error("Could not parse checkout date " + e.toString());
									processLog.incErrors();
									processLog.addNote("Could not parse checkout date " + e.toString());
								}
							}
						}
					} else {
						logger.info("Call to getPatronCheckedOutItems returned a success code of false for " + cat_username);
					}
				} catch (JSONException e) {
					logger.error("Unable to load patron information from for " + cat_username + " exception loading response ", e);
					processLog.incErrors();
					processLog.addNote("Unable to load patron information from for " + cat_username + " exception loading response " + e.toString());
				}
			} else {
				logger.error("Unable to load patron information from for " + cat_username + ": expected to get back an input stream, received a "
						+ patronDataRaw.getClass().getName());
				processLog.incErrors();
			}
		} catch (MalformedURLException e) {
			logger.error("Bad url for patron API " + e.toString());
			processLog.incErrors();
		} catch (IOException e) {
			logger.error("Unable to retrieve information from patron API for " + cat_username + ": " + e.toString());
			processLog.incErrors();
		}
	}

	private void processEContentTitles(Long userId, String cat_username, String cat_password) throws SQLException{
		try {
			// Call the patron API to get their checked out items
			URL patronApiUrl;
			patronApiUrl = new URL(vufindUrl + "/API/UserAPI?method=getPatronCheckedOutEContent&username=" + cat_username + "&password=" + cat_password);
			Object patronDataRaw = patronApiUrl.getContent();
			if (patronDataRaw instanceof InputStream) {
				String patronDataJson = Util.convertStreamToString((InputStream) patronDataRaw);
				logger.info(patronApiUrl.toString());
				logger.info("Json for patron checked out econtent " + patronDataJson);
				try {
					JSONObject patronData = new JSONObject(patronDataJson);
					JSONObject result = patronData.getJSONObject("result");
					if (result.getBoolean("success")) {
						JSONArray checkedOutItems = result.getJSONArray("checkedOutItems");
						for (int i = 0; i < checkedOutItems.length(); i++){
							JSONObject checkedOutItem = checkedOutItems.getJSONObject(i);
							// System.out.println(checkedOutItem.toString());
							String bibId = checkedOutItem.getString("id");
							String checkoutDateStr = checkedOutItem.getString("checkoutdate");
							logger.debug(bibId + " : " + checkoutDateStr);

							// Get the resource for this bibId
							long resourceId = getResourceForBib(bibId, "eContent");
							if (resourceId == -1){
								logger.error("Could not retrieve or create resource for bib Id " + bibId);
								continue;
							}else{
								// Update the reading history
								Date checkoutDate = new Date(new Long(checkoutDateStr));
								updateReadingHistory(userId, "econtentRecord" + bibId, resourceId, checkoutDate);
							}
						}
					} else {
						logger.warn("Call to getPatronCheckedOutEContent returned a success code of false for " + cat_username);
					}
				} catch (JSONException e) {
					logger.error("Unable to load patron information from for " + cat_username + " exception loading response " + e.toString(), e);
				}
			} else {
				logger.error("Unable to load patron information from for " + cat_username + ": expected to get back an input stream, received a "
						+ patronDataRaw.getClass().getName());
			}
		} catch (MalformedURLException e) {
			logger.error("Bad url for patron API ", e);
		} catch (IOException e) {
			logger.error("Unable to retrieve information from patron API for " + cat_username, e);
		}
	}
	
	private void processOverDriveTitles(Long userId, String cat_username, String cat_password) throws SQLException{
		try {
			// Call the patron API to get their checked out items
			URL patronApiUrl;
			patronApiUrl = new URL(vufindUrl + "/API/UserAPI?method=getPatronCheckedOutItemsOverDrive&username=" + cat_username + "&password=" + cat_password);
			Object patronDataRaw = patronApiUrl.getContent();
			if (patronDataRaw instanceof InputStream) {
				String patronDataJson = Util.convertStreamToString((InputStream) patronDataRaw);
				logger.info(patronApiUrl.toString());
				logger.info("Json for patron checked out OverDrive items " + patronDataJson);
				try {
					JSONObject patronData = new JSONObject(patronDataJson);
					JSONObject result = patronData.getJSONObject("result");
					if (result.getBoolean("success")) {
						JSONArray checkedOutItems = result.getJSONArray("items");
						for (int i = 0; i < checkedOutItems.length(); i++){
							JSONObject checkedOutItem = checkedOutItems.getJSONObject(i);
							// System.out.println(checkedOutItem.toString());
							String bibId = checkedOutItem.getString("recordId");
							String checkoutDateStr = checkedOutItem.getString("checkoutdate");
							logger.debug(bibId + " : " + checkoutDateStr);

							// Get the resource for this bibId
							long resourceId = getResourceForBib(bibId, "eContent");
							if (resourceId == -1){
								logger.error("Could not retrieve or create resource for bib Id " + bibId);
								continue;
							}else{
								// Update the reading history
								Date checkoutDate = new Date(new Long(checkoutDateStr));
								updateReadingHistory(userId, "econtentRecord" + bibId, resourceId, checkoutDate);
							}
						}
					} else {
						logger.warn("Call to getPatronCheckedOutEContent returned a success code of false for " + cat_username);
					}
				} catch (JSONException e) {
					logger.error("Unable to load patron information from for " + cat_username + " exception loading response " , e);
				}
			} else {
				logger.error("Unable to load patron information from for " + cat_username + ": expected to get back an input stream, received a "
						+ patronDataRaw.getClass().getName());
			}
		} catch (MalformedURLException e) {
			logger.error("Bad url for patron API ", e);
		} catch (IOException e) {
			logger.error("Unable to retrieve information from patron API for " + cat_username, e);
		}
	}
	
	private void updateReadingHistory(Long userId, String bibId, long resourceId, Date checkoutDate) throws SQLException, IOException {
		readingHistoryStatement.setLong(1, userId);
		readingHistoryStatement.setLong(2, resourceId);
		ResultSet readingHistoryResult = readingHistoryStatement.executeQuery();
		Date currentDate = new Date();
			
		if (readingHistoryResult.next()) {
			// Set the lastCheckoutDate
			Date lastCheckoutDate = readingHistoryResult.getDate("lastCheckoutDate");
			if (currentDate.getTime() - lastCheckoutDate.getTime() > 24 * 60 * 60 * 1000) {
				long daysCheckedOut = readingHistoryResult.getLong("daysCheckedOut");
				// We have rolled to a new date, increase the
				// daysCheckedOut and set the lastCheckOutDate to
				// today.
				daysCheckedOut++;
				updateReadingHistoryStmt.setLong(1, daysCheckedOut);
				updateReadingHistoryStmt.setDate(2, new java.sql.Date(currentDate.getTime()));
				updateReadingHistoryStmt.setLong(3, userId);
				updateReadingHistoryStmt.setLong(4, resourceId);
				int updateOk = updateReadingHistoryStmt.executeUpdate();
				if (updateOk != 1) {
					logger.error("Failed to add item to reading history");
					
				}
			}

			// Increment the number of days the item has been
			// checked out if a day has elapsed
		} else {
			// This is a new item in the reading history, record it.
			insertReadingHistoryStmt.setLong(1, userId);
			insertReadingHistoryStmt.setLong(2, resourceId);
			insertReadingHistoryStmt.setDate(3, new java.sql.Date(currentDate.getTime()));
			insertReadingHistoryStmt.setDate(4, new java.sql.Date(checkoutDate.getTime()));
			int updateOk = insertReadingHistoryStmt.executeUpdate();
			if (updateOk != 1) {
				logger.error("Failed to add item to reading history");
			}
			// Make a call to strands to indicate that the item was
			// checked out.
			if (strandsApid != null && strandsApid.length() > 0) {
				String orderid = userId + "_" + (checkoutDate.getTime() / 1000);
				//Need to send bibid rather than resource id to strands
				String url = "http://bizsolutions.strands.com/api2/event/purchased.sbs?needresult=true&apid=" + strandsApid + "&item=" + bibId + "::0.00::1&user=" + userId + "&orderid=" + orderid;
				logger.debug("Calling strands " + url);
				URL strandsUrl = new URL(url);
				URLConnection yc = strandsUrl.openConnection();
				BufferedReader in = new BufferedReader(new InputStreamReader(yc.getInputStream()));
				String inputLine;

				while ((inputLine = in.readLine()) != null){
					logger.debug(inputLine);
				}
				in.close();
			}else{
				logger.debug("Skipping logging strands information.");
			}
		}

	}

	private long getResourceForBib(String bibId, String source) throws SQLException {
		long resourceId = -1;
		getResourceStmt.setString(1, bibId);
		getResourceStmt.setString(2, source);
		ResultSet resourceResult = getResourceStmt.executeQuery();
		if (resourceResult.next()) {
			// Get the resource id
			resourceId = resourceResult.getLong("id");
		} else {
			// add the resource to the database
			insertResourceStmt.setString(1, bibId);
			insertResourceStmt.setString(2, "VuFind");
			int updateOk = insertResourceStmt.executeUpdate();
			if (updateOk == 1){
				resourceResult = insertResourceStmt.getGeneratedKeys();
				if (resourceResult.next()) {
					resourceId = resourceResult.getInt(1);
				} else {
					resourceId = -1;
				}
			}else{
				resourceId = -1;
			}
			resourceResult.close();
		}
		return resourceId;
	}

}
