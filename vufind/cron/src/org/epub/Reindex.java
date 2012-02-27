package org.epub;

import java.io.InputStream;
import java.net.URL;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;

import org.apache.log4j.Logger;
import org.ini4j.Profile.Section;
import org.vufind.IProcessHandler;
import org.vufind.Util;

public class Reindex implements IProcessHandler {
	private String econtentDBConnectionInfo;
	private Connection econtentConn = null;
	private String vufindUrl;
	
	@Override
	public void doCronProcess(Section processSettings, Section generalSettings, Logger logger) {
		logger.info("Reindexing eContent");
		
		//Load configuration
		if (!loadConfig(processSettings, generalSettings, logger)){
			return;
		}
		
		try {
			//Connect to the eContent database
			econtentConn = DriverManager.getConnection(econtentDBConnectionInfo);
			
			//Reindexing all records
			PreparedStatement eContentRecordStmt = econtentConn.prepareStatement("SELECT id from econtent_record");
			ResultSet eContentRecordRS = eContentRecordStmt.executeQuery();
			while (eContentRecordRS.next()){
				long startTime = new Date().getTime();
				String econtentRecordId = eContentRecordRS.getString("id");
				try {
					URL updateIndexURL = new URL(vufindUrl + "EcontentRecord/" + econtentRecordId + "/Reindex");
					Object updateIndexDataRaw = updateIndexURL.getContent();
					if (updateIndexDataRaw instanceof InputStream) {
						String updateIndexResponse = Util.convertStreamToString((InputStream) updateIndexDataRaw);
						long endTime = new Date().getTime();
						logger.info("Indexing record " + econtentRecordId + " elapsed " + (endTime - startTime) + " response: " + updateIndexResponse);
					}
				} catch (Exception e) {
					logger.info("Unable to reindex record " + econtentRecordId, e);
				}
			}
			
			econtentConn.close();
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error establishing connection to database ", ex);
			return;
		}

	}

	private boolean loadConfig(Section processSettings, Section generalSettings, Logger logger) {
		econtentDBConnectionInfo = generalSettings.get("econtentDatabase");
		if (econtentDBConnectionInfo == null || econtentDBConnectionInfo.length() == 0) {
			logger.error("Database connection information for eContent database not found in General Settings.  Please specify connection information in a econtentDatabase key.");
			return false;
		}
		
		vufindUrl = generalSettings.get("vufindUrl");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
			return false;
		}
		
		return true;
	}
}
