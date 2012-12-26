package org.vufind;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.SQLException;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

public class DatabaseCleanup implements IProcessHandler {

	@Override
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Database Cleanup");
		processLog.saveToDatabase(vufindConn, logger);
		
		//Remove old searches 
		try {
			PreparedStatement removeSearches = vufindConn.prepareStatement("DELETE FROM search where created < (CURDATE() - INTERVAL 2 DAY) and saved = 0");
			int rowsRemoved = removeSearches.executeUpdate();
			processLog.incUpdated();
			processLog.addNote("Removed " + rowsRemoved + " expired searches");
			processLog.saveToDatabase(vufindConn, logger);
		} catch (SQLException e) {
			processLog.incErrors();
			processLog.addNote("Unable to delete expired searches.");
			processLog.saveToDatabase(vufindConn, logger);
		}
		processLog.setFinished();
		processLog.saveToDatabase(vufindConn, logger);
	}

}
