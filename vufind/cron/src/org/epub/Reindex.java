package org.epub;

import java.io.InputStream;
import java.net.URL;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.vufind.CronLogEntry;
import org.vufind.CronProcessLogEntry;
import org.vufind.IProcessHandler;
import org.vufind.Util;

public class Reindex implements IProcessHandler {
	private String vufindUrl;
	
	@Override
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Reindex eContent");
		processLog.saveToDatabase(vufindConn, logger);
		logger.info("Reindexing eContent");
		
		//Load configuration
		if (!loadConfig(configIni, processSettings, logger)){
			return;
		}
		
		try {
			//TODO: Drop existing records from Solr index.
			
			//Reindexing all records
			PreparedStatement eContentRecordStmt = econtentConn.prepareStatement("SELECT id from econtent_record where status ='active' ");
			ResultSet eContentRecordRS = eContentRecordStmt.executeQuery();
			while (eContentRecordRS.next()){
				long startTime = new Date().getTime();
				String econtentRecordId = eContentRecordRS.getString("id");
				try {
					URL updateIndexURL = new URL(vufindUrl + "/EcontentRecord/" + econtentRecordId + "/Reindex?quick=true");
					Object updateIndexDataRaw = updateIndexURL.getContent();
					if (updateIndexDataRaw instanceof InputStream) {
						String updateIndexResponse = Util.convertStreamToString((InputStream) updateIndexDataRaw);
						long endTime = new Date().getTime();
						logger.info("Indexing record " + econtentRecordId + " elapsed " + (endTime - startTime) + " response: " + updateIndexResponse);
						processLog.incUpdated();
					}
				} catch (Exception e) {
					logger.info("Unable to reindex record " + econtentRecordId, e);
				}
			}
			
			processLog.setFinished();
			processLog.saveToDatabase(vufindConn, logger);
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error establishing connection to database ", ex);
			return;
		}

	}

	private boolean loadConfig(Ini configIni, Section processSettings, Logger logger) {
		vufindUrl = configIni.get("Site", "url");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
			return false;
		}
		
		return true;
	}
}
