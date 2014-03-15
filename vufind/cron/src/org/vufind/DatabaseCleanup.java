package org.vufind;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;

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
			int rowsRemoved = 0;
			long numStandardSessionsDeleted = vufindConn.prepareStatement("DELETE FROM session where last_used < (DATE_ADD(CURDATE(), INTERVAL -1 HOUR)) and remember_me = 0").executeUpdate();
			processLog.addNote("Deleted " + numStandardSessionsDeleted + " expired Standard Sessions");
			processLog.saveToDatabase(vufindConn, logger);
			long numRememberMeSessionsDeleted = vufindConn.prepareStatement("DELETE FROM session where last_used < (DATE_ADD(CURDATE(), INTERVAL -2 WEEK)) and remember_me = 1").executeUpdate();
			processLog.addNote("Deleted " + numStandardSessionsDeleted + " expired Remember Me Sessions");
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
		
		//Remove econtent records and related data that was created incorrectly. 
		try {
			//Anything where the ILS id matches the ID is wrong.   
			ResultSet eContentToCleanup = econtentConn.prepareStatement("SELECT id from econtent_record WHERE ilsId = id OR ilsId like 'econtentRecord%'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY).executeQuery();
			PreparedStatement removeResourceStmt = vufindConn.prepareStatement("DELETE FROM resource where record_id = ? and source = 'eContent'");
			PreparedStatement removeEContentItemsStmt = econtentConn.prepareStatement("DELETE FROM econtent_item where recordId = ?");
			PreparedStatement removeEContentRecordStmt = econtentConn.prepareStatement("DELETE FROM econtent_record where id = ?");
			int recordsRemoved = 0;
			while (eContentToCleanup.next()){
				Long curId = eContentToCleanup.getLong("id");
				//Remove related resources
				removeResourceStmt.setString(1, curId.toString());
				removeResourceStmt.executeUpdate();
				//Remove related econtent items
				removeEContentItemsStmt.setLong(1, curId);
				removeEContentItemsStmt.executeUpdate();
				//Remove the record itself
				removeEContentRecordStmt.setLong(1, curId);
				removeEContentRecordStmt.executeUpdate();
				processLog.incUpdated();
				recordsRemoved++;
				if (recordsRemoved % 1000 == 0){
					processLog.saveToDatabase(vufindConn, logger);
				}
			}
			ResultSet eContentToCleanup2 = econtentConn.prepareStatement("SELECT id from econtent_record WHERE ilsId = '' and externalId is null", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY).executeQuery();
			while (eContentToCleanup2.next()){
				Long curId = eContentToCleanup2.getLong("id");
				//Remove related resources
				removeResourceStmt.setString(1, curId.toString());
				removeResourceStmt.executeUpdate();
				//Remove related econtent items
				removeEContentItemsStmt.setLong(1, curId);
				removeEContentItemsStmt.executeUpdate();
				//Remove the record itself
				removeEContentRecordStmt.setLong(1, curId);
				removeEContentRecordStmt.executeUpdate();
				processLog.incUpdated();
				recordsRemoved++;
				if (recordsRemoved % 1000 == 0){
					processLog.saveToDatabase(vufindConn, logger);
				}
			}
			
			processLog.addNote("Removed " + recordsRemoved + " incorrectly created eContent");
			processLog.saveToDatabase(vufindConn, logger);
		} catch (SQLException e) {
			processLog.incErrors();
			processLog.addNote("Unable to remove incorrectly created econtent. " + e.toString());
			logger.error("Error removing incorrectly created econtent", e);
			processLog.saveToDatabase(vufindConn, logger);
		}
		processLog.setFinished();
		processLog.saveToDatabase(vufindConn, logger);
	}

}
