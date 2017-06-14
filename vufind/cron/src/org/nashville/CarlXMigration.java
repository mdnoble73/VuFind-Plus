package org.nashville;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.VariableField;
import org.vufind.CronLogEntry;
import org.vufind.CronProcessLogEntry;
import org.vufind.IProcessHandler;

import java.io.File;
import java.io.FileInputStream;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.util.HashMap;
import java.util.List;

/**
 * Created by mnoble on 6/7/2017.
 */
public class CarlXMigration implements IProcessHandler{
	private CronProcessLogEntry processLog;
	private String lssExportLocation;
	private String carlxExportLocation;

	public void doCronProcess(String servername, Ini configIni, Profile.Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Import Steamboat Genealogy");
		if (!loadConfig(configIni, processSettings)){
			processLog.addNote("Unable to load configuration");
			processLog.incErrors();
			return;
		}

		fixRecordsNotToMerge(vufindConn, logger);

		processLog.setFinished();
		processLog.saveToDatabase(vufindConn, logger);
	}

	private void fixRecordsNotToMerge(Connection vufindConn, Logger logger) {
		//Get the export file from CARL.X
		File carlXExport = new File(carlxExportLocation);
		if (!carlXExport.exists()){
			logger.warn("Could not find carlx export in " + carlxExportLocation);
			processLog.addNote("Could not find carlx export in " + carlxExportLocation);
			processLog.incErrors();
			return;
		}
		//Get the old LSS export
		File lssExport = new File(lssExportLocation);
		if (!lssExport.exists()){
			logger.warn("Could not find LSS export in " + lssExportLocation);
			processLog.addNote("Could not find LSS export in " + lssExportLocation);
			processLog.incErrors();
			return;
		}

		//Make a map for LSS records to map 001 to the 039
		HashMap<String, String> lssControlNumberToUniqueId = new HashMap<>();
		try {
			MarcPermissiveStreamReader lssReader = new MarcPermissiveStreamReader(new FileInputStream(lssExport), true, true);
			while (lssReader.hasNext()){
				Record lssRecord = lssReader.next();
				String controlNumber = ((ControlField)lssRecord.getVariableField("001")).getData().trim();
				VariableField lssNumberField = lssRecord.getVariableField("039");
				if (lssNumberField != null){
					DataField lssNumberDataField = (DataField)lssNumberField;
					String lssNumber = lssNumberDataField.getSubfield('a').getData();
					if (lssControlNumberToUniqueId.containsKey(controlNumber)){
						logger.warn("Warning control number " + controlNumber + " was not unique");
					}else{
						lssControlNumberToUniqueId.put(controlNumber, lssNumber);
					}
				}else{
					logger.warn("Did not find the lss number for record with control number " + controlNumber);
				}
			}

		}catch (Exception e){
			logger.error("Error in fixRecordsNotToMerge" ,  e);
			processLog.addNote("Error in fixRecordsNotToMerge - " +  e.toString());
			processLog.incErrors();
		}

		//Loop through all records
		try {
			PreparedStatement updateRecordNotToGroupStmt = vufindConn.prepareStatement("UPDATE nongrouped_records SET source='ils', recordId = ? where source = ? and recordId = ? ");

			MarcPermissiveStreamReader carlxReader = new MarcPermissiveStreamReader(new FileInputStream(carlXExport), true, true);
			//Check the 907 (millennium) and 908 (LSS)
			//Update the old within the records not to group based on the 910
			while (carlxReader.hasNext()){
				Record carlxRecord = carlxReader.next();
				VariableField carlXIdentifierField = carlxRecord.getVariableField("910");
				String carlxIdentifier = ((DataField)carlXIdentifierField).getSubfield('a').getData();
				List<VariableField> millenniumIdentifierFields = carlxRecord.getVariableFields("907");
				for (VariableField millenniumIdentifierField : millenniumIdentifierFields){
					String millenniumIdentifier = ((DataField)millenniumIdentifierField).getSubfield('a').getData();

					if (millenniumIdentifier.matches("\\.b.*")) {
						updateRecordNotToGroupStmt.setString(1, carlxIdentifier);
						updateRecordNotToGroupStmt.setString(2, "millennium");
						updateRecordNotToGroupStmt.setString(3, millenniumIdentifier);
						int numUpdated = updateRecordNotToGroupStmt.executeUpdate();
						if (numUpdated == 1) {
							processLog.incUpdated();
							processLog.addNote("Updated Millennium identifier " + millenniumIdentifier + " to " + carlxIdentifier);
						}
					}else{
						logger.debug("Invalid millennium identifer");
					}
				}
				List<VariableField> lssControlNumberFields = carlxRecord.getVariableFields("908");
				for (VariableField lssControlNumberField : lssControlNumberFields){
					String lssControlNumber = ((DataField)lssControlNumberField).getSubfield('a').getData();
					String lssIdentifier = lssControlNumberToUniqueId.get(lssControlNumber);
					if (lssIdentifier != null) {
						updateRecordNotToGroupStmt.setString(1, carlxIdentifier);
						updateRecordNotToGroupStmt.setString(2, "lss");
						updateRecordNotToGroupStmt.setString(3, lssIdentifier);
						int numUpdated = updateRecordNotToGroupStmt.executeUpdate();
						if (numUpdated == 1) {
							processLog.incUpdated();
							processLog.addNote("Updated LSS identifier " + lssIdentifier + " to " + carlxIdentifier);
						}
					}else{
						//It looks like there is more than just control numbers from LSS here so this is normal.
						logger.debug("Did not find an identifier for lss control number " + lssControlNumber);
					}
				}
			}
		}catch (Exception e){
			logger.error("Error in fixRecordsNotToMerge", e);
			processLog.addNote("Error in fixRecordsNotToMerge - " +  e.toString());
			processLog.incErrors();
		}
	}

	private boolean loadConfig(Ini configIni, Profile.Section processSettings) {
		lssExportLocation = processSettings.get("lssExportLocation");
		carlxExportLocation = processSettings.get("carlxExportLocation");
		return true;
	}
}
