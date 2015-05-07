package org.vufind;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

public class DatabaseCleanup implements IProcessHandler {

	@Override
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Database Cleanup");
		processLog.saveToDatabase(vufindConn, logger);

		//Remove expired sessions
		try{
			//Make sure to normalize the time based to be milliseconds, not microseconds
			long now = new Date().getTime() / 1000;
			long defaultTimeout = Long.parseLong(Util.cleanIniValue(configIni.get("Session", "lifetime")));
			long earliestDefaultSessionToKeep = now - defaultTimeout;
			long numStandardSessionsDeleted = vufindConn.prepareStatement("DELETE FROM session where last_used < " + earliestDefaultSessionToKeep + " and remember_me = 0").executeUpdate();
			processLog.addNote("Deleted " + numStandardSessionsDeleted + " expired Standard Sessions");
			processLog.saveToDatabase(vufindConn, logger);
			long rememberMeTimeout = Long.parseLong(Util.cleanIniValue(configIni.get("Session", "rememberMeLifetime")));
			long earliestRememberMeSessionToKeep = now - rememberMeTimeout;
			long numRememberMeSessionsDeleted = vufindConn.prepareStatement("DELETE FROM session where last_used < " + earliestRememberMeSessionToKeep + " and remember_me = 1").executeUpdate();
			processLog.addNote("Deleted " + numRememberMeSessionsDeleted + " expired Remember Me Sessions");
			processLog.saveToDatabase(vufindConn, logger);
		}catch (SQLException e) {
			processLog.incErrors();
			processLog.addNote("Unable to delete expired sessions. " + e.toString());
			logger.error("Error deleting expired sessions", e);
			processLog.saveToDatabase(vufindConn, logger);
		}

		//Remove old searches 
		try {
			int rowsRemoved = 0;
			ResultSet numSearchesRS = vufindConn.prepareStatement("SELECT count(id) from search where created < (CURDATE() - INTERVAL 2 DAY) and saved = 0").executeQuery();
			numSearchesRS.next();
			long numSearches = numSearchesRS.getLong(1);
			long batchSize = 100000;
			long numBatches = (numSearches / batchSize) + 1;
			processLog.addNote("Found " + numSearches + " expired searches that need to be removed.  Will process in " + numBatches + " batches");
			processLog.saveToDatabase(vufindConn, logger);
			for (int i = 0; i < numBatches; i++){
				PreparedStatement searchesToRemove = vufindConn.prepareStatement("SELECT id from search where created < (CURDATE() - INTERVAL 2 DAY) and saved = 0 LIMIT 0, " + batchSize, ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				PreparedStatement removeSearchStmt = vufindConn.prepareStatement("DELETE from search where id = ?");
				
				ResultSet searchesToRemoveRs = searchesToRemove.executeQuery();
				while (searchesToRemoveRs.next()){
					long curId = searchesToRemoveRs.getLong("id");
					removeSearchStmt.setLong(1, curId);
					rowsRemoved += removeSearchStmt.executeUpdate();
				}
				processLog.incUpdated();
				processLog.saveToDatabase(vufindConn, logger);
			}
			processLog.addNote("Removed " + rowsRemoved + " expired searches");
			processLog.saveToDatabase(vufindConn, logger);
		} catch (SQLException e) {
			processLog.incErrors();
			processLog.addNote("Unable to delete expired searches. " + e.toString());
			logger.error("Error deleting expired searches", e);
			processLog.saveToDatabase(vufindConn, logger);
		}
		
		//Remove reading history entries that are duplicate based on being renewed
		//Get a list of duplicate titles
		try {
			PreparedStatement duplicateRecordsToPreserveStmt = vufindConn.prepareStatement("SELECT COUNT(id) as numRecords, userId, groupedWorkPermanentId, source, sourceId, checkOutDate, MAX(checkInDate) as lastCheckIn FROM user_reading_history_work where deleted = 0 GROUP BY userId, source, sourceId, checkOutDate having numRecords > 1", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			PreparedStatement deleteDuplicateRecordStmt = vufindConn.prepareStatement("UPDATE user_reading_history_work SET deleted = 1 WHERE userId = ? AND groupedWorkPermanentId = ? AND source = ? and sourceId = ? and checkOutDate = ? AND checkInDate != ?");
			ResultSet duplicateRecordsRS = duplicateRecordsToPreserveStmt.executeQuery();
			while (duplicateRecordsRS.next()){
				deleteDuplicateRecordStmt.setLong(1, duplicateRecordsRS.getLong("userId"));
				deleteDuplicateRecordStmt.setString(2, duplicateRecordsRS.getString("groupedWorkPermanentId"));
				deleteDuplicateRecordStmt.setString(3, duplicateRecordsRS.getString("source"));
				deleteDuplicateRecordStmt.setString(4, duplicateRecordsRS.getString("sourceId"));
				deleteDuplicateRecordStmt.setLong(5, duplicateRecordsRS.getLong("checkoutDate"));
				deleteDuplicateRecordStmt.setLong(6, duplicateRecordsRS.getLong("lastCheckIn"));
				int numDeletions = deleteDuplicateRecordStmt.executeUpdate();
				if (numDeletions == 0){
					logger.warn("Warning did not delete any records");
				}
			}
		} catch (SQLException e) {
			processLog.incErrors();
			processLog.addNote("Unable to delete duplicate reading history entries. " + e.toString());
			logger.error("Error deleting duplicate reading history entries", e);
			processLog.saveToDatabase(vufindConn, logger);
		}
		processLog.setFinished();
		processLog.saveToDatabase(vufindConn, logger);
	}

}
