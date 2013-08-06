package org.innovative;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile;
import org.json.JSONException;
import org.json.JSONObject;
import org.vufind.CronLogEntry;
import org.vufind.CronProcessLogEntry;
import org.vufind.IProcessHandler;
import org.vufind.Util;

import java.io.IOException;
import java.io.InputStream;
import java.net.URL;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;

/**
 * Processes holds and checkouts that were done offline when the system comes back up.
 * VuFind-Plus
 * User: Mark Noble
 * Date: 8/5/13
 * Time: 5:18 PM
 */
public class OfflineCirculation implements IProcessHandler {
	private CronProcessLogEntry processLog;
	@Override
	public void doCronProcess(String servername, Ini configIni, Profile.Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Offline Circulation");
		processLog.saveToDatabase(vufindConn, logger);

		//Check to see if the system is offline
		String offlineStr = configIni.get("Catalog", "offline");
		if (offlineStr.toLowerCase().equals("true")){
			processLog.addNote("Not processing offline circulation because the system is currently offline.");
		}else{
			//process holds
			processOfflineHolds(configIni, vufindConn);

			//process checkouts and check ins
			processOfflineCheckouts(configIni, vufindConn);
		}
		processLog.setFinished();
		processLog.saveToDatabase(vufindConn, logger);
	}

	/**
	 * Enters any holds that were entered while the catalog was offline
	 *
	 * @param configIni   Configuration information for VuFind
	 * @param vufindConn Connection to the database
	 */
	private void processOfflineHolds(Ini configIni, Connection vufindConn) {
		processLog.addNote("Processing offline holds");
		try {
			PreparedStatement holdsToProcessStmt = vufindConn.prepareStatement("SELECT offline_hold.*, cat_username, cat_password from offline_hold INNER JOIN user on user.id = offline_hold.patronId where status='Not Processed' order by timeEntered ASC");
			PreparedStatement updateHold = vufindConn.prepareStatement("UPDATE offline_hold set timeProcessed = ?, status = ?, notes = ? where id = ?");
			String baseUrl = configIni.get("Site", "url");
			ResultSet holdsToProcessRS = holdsToProcessStmt.executeQuery();
			while (holdsToProcessRS.next()){
				processOfflineHold(updateHold, baseUrl, holdsToProcessRS);
			}
		} catch (SQLException e) {
			processLog.incErrors();
			processLog.addNote("Error processing offline holds " + e.toString());
		}

	}

	private void processOfflineHold(PreparedStatement updateHold, String baseUrl, ResultSet holdsToProcessRS) throws SQLException {
		long holdId = holdsToProcessRS.getLong("id");
		updateHold.clearParameters();
		updateHold.setLong(1, new Date().getTime() / 1000);
		updateHold.setLong(4, holdId);
		try {
			String patronBarcode = holdsToProcessRS.getString("patronBarcode");
			String patronName = holdsToProcessRS.getString("cat_username");
			String bibId = holdsToProcessRS.getString("bibId");
			URL placeHoldUrl = new URL(baseUrl + "/API/UserAPI?method=placeHold&username=" + patronName + "&password=" + patronBarcode + "&bibId=" + bibId);
			Object placeHoldDataRaw = placeHoldUrl.getContent();
			if (placeHoldDataRaw instanceof InputStream) {
				String placeHoldDataJson = Util.convertStreamToString((InputStream) placeHoldDataRaw);
				processLog.addNote("Result = " + placeHoldDataJson);
				JSONObject placeHoldData = new JSONObject(placeHoldDataJson);
				JSONObject result = placeHoldData.getJSONObject("result");
				if (result.getBoolean("success")){
					updateHold.setString(2, "Hold Succeeded");
				}else{
					updateHold.setString(2, "Hold Failed");
				}
				updateHold.setString(3, result.getString("holdMessage"));
			}
			processLog.incUpdated();
		} catch (JSONException e) {
			processLog.incErrors();
			processLog.addNote("Error Loading JSON response for placing hold " + holdId + " - '" + e.toString());
			updateHold.setString(2, "Hold Failed");
			updateHold.setString(3, "Error Loading JSON response for placing hold " + holdId + " - " + e.toString());

		} catch (IOException e) {
			processLog.incErrors();
			processLog.addNote("Error processing offline hold " + holdId + " - " + e.toString());
			updateHold.setString(2, "Hold Failed");
			updateHold.setString(3, "Error processing offline hold " + holdId + " - " + e.toString());
		}
		try {
			updateHold.executeUpdate();
		} catch (SQLException e) {
			processLog.incErrors();
			processLog.addNote("Error updating hold status for hold " + holdId + " - " + e.toString());
		}
	}

	/**
	 * Processes any checkouts and check-ins that were done while the system was offline.
	 *
	 * @param configIni   Configuration information for VuFind
	 * @param vufindConn Connection to the database
	 */
	private void processOfflineCheckouts(Ini configIni, Connection vufindConn) {
		processLog.addNote("Processing offline checkouts and check-ins");

	}
}
