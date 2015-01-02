package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile;
import org.marc4j.*;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.sql.Connection;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;

/**
 * Merge a main marc export file with records from a delete and updates file
 * VuFind-Plus
 * User: Mark Noble
 * Date: 12/31/2014
 * Time: 11:45 AM
 */
public class MergeMarcUpdatesAndDeletes implements IProcessHandler{
	private String recordNumberTag = "";
	private String recordNumberPrefix = "";

	@Override
	public void doCronProcess(String servername, Ini configIni, Profile.Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Merge Marc Updates and Deletes");
		processLog.saveToDatabase(vufindConn, logger);


		//Get a list of marc records that need to be processed
		String exportPath = configIni.get("Reindex", "marcPath");
		File mainFile = null;
		File deletesFile = null;
		File updatesFile = null;
		String backupPath = configIni.get("Reindex", "marcBackupPath");
		String marcEncoding = configIni.get("Reindex", "marcEncoding");

		recordNumberTag = configIni.get("Reindex", "recordNumberTag");
		recordNumberPrefix = configIni.get("Reindex", "recordNumberPrefix");

		File[] filesInExport = new File(exportPath).listFiles();
		if (filesInExport != null) {
			for (File exportFile : filesInExport) {
				if (exportFile.getName().matches(".*updated.*")) {
					updatesFile = exportFile;
				}else if (exportFile.getName().matches(".*deleted.*")) {
					deletesFile = exportFile;
				}else if (exportFile.getName().endsWith("mrc") || exportFile.getName().endsWith("marc")) {
					mainFile = exportFile;
				}
			}

			if (mainFile == null){
				logger.error("Did not find file to merge into");
				processLog.addNote("Did not find file to merge into");
				processLog.saveToDatabase(vufindConn, logger);
			}else {
				boolean errorOccurred = false;
				HashMap<String, Record> recordsToUpdate = new HashMap<String, Record>();
				if (updatesFile != null) {
					try {
						FileInputStream marcFileStream = new FileInputStream(updatesFile);
						MarcReader updatesReader = new MarcPermissiveStreamReader(marcFileStream, true, true, marcEncoding);

						//Read a list of records in the updates file
						while (updatesReader.hasNext()) {
							Record curBib = updatesReader.next();
							String recordId = getRecordIdFromMarcRecord(curBib);
							recordsToUpdate.put(recordId, curBib);
						}
						marcFileStream.close();
					} catch (IOException e) {
						processLog.addNote("Error processing updates file. " + e.toString());
						logger.error("Error loading records from updates fail", e);
						processLog.saveToDatabase(vufindConn, logger);
						errorOccurred = true;
					}
				}

				HashSet<String> recordsToDelete = new HashSet<String>();
				if (deletesFile != null) {
					try {
						FileInputStream marcFileStream = new FileInputStream(deletesFile);
						MarcReader deletesReader = new MarcPermissiveStreamReader(marcFileStream, true, true, marcEncoding);

						while (deletesReader.hasNext()) {
							Record curBib = deletesReader.next();
							String recordId = getRecordIdFromMarcRecord(curBib);
							recordsToDelete.add(recordId);
						}

						marcFileStream.close();
					} catch (IOException e) {
						processLog.incErrors();
						processLog.addNote("Error processing deletes file. " + e.toString());
						logger.error("Error processing deletes file", e);
						errorOccurred = true;
						processLog.saveToDatabase(vufindConn, logger);
					}
				}

				File mergedFile = new File(mainFile.getPath() + ".merged");
				try {
					FileInputStream marcFileStream = new FileInputStream(mainFile);
					MarcReader mainReader = new MarcPermissiveStreamReader(marcFileStream, true, true, marcEncoding);

					FileOutputStream marcOutputStream = new FileOutputStream(mergedFile);
					MarcStreamWriter mainWriter = new MarcStreamWriter(marcOutputStream);
					while (mainReader.hasNext()) {
						Record curBib = mainReader.next();
						String recordId = getRecordIdFromMarcRecord(curBib);

						if (recordsToUpdate.containsKey(recordId)) {
							//Write the updated record
							mainWriter.write(recordsToUpdate.get(recordId));
						} else if (!recordsToDelete.contains(recordId)) {
							//Unless the record is marked for deletion, write it
							mainWriter.write(curBib);
						}
					}
					mainWriter.close();
					marcFileStream.close();
				} catch (IOException e) {
					processLog.incErrors();
					processLog.addNote("Error processing main file. " + e.toString());
					logger.error("Error processing main file", e);
					errorOccurred = true;
					processLog.saveToDatabase(vufindConn, logger);
				}

				if (!new File(backupPath).exists()){
					if (!new File(backupPath).mkdirs()){
						processLog.incErrors();
						processLog.addNote("Could not create backup path");
						logger.error("Could not create backup path");
						errorOccurred = true;
						processLog.saveToDatabase(vufindConn, logger);
					}
				}
				if (updatesFile != null && !errorOccurred) {
					//Move to the backup directory
					if (!updatesFile.renameTo(new File(backupPath + "/" + updatesFile.getName()))) {
						processLog.incErrors();
						processLog.addNote("Unable to move updates file to backup directory.");
						logger.error("Unable to move updates file to backup directory");
						processLog.saveToDatabase(vufindConn, logger);
						errorOccurred = true;
					}
				}

				if (deletesFile != null && !errorOccurred) {
					//Move to the backup directory
					if (!deletesFile.renameTo(new File(backupPath + "/" + deletesFile.getName()))) {
						processLog.incErrors();
						processLog.addNote("Unable to move deletion file to backup directory.");
						logger.error("Unable to move deletion file to backup directory");
						processLog.saveToDatabase(vufindConn, logger);
						errorOccurred = true;
					}
				}

				if (!errorOccurred) {
					String mainFilePath = mainFile.getPath();
					if (!mainFile.renameTo(new File(backupPath + "/" + mainFile.getName()))) {
						processLog.incErrors();
						processLog.addNote("Unable to move main file to backup directory.");
						logger.error("Unable to move main file to backup directory");
						processLog.saveToDatabase(vufindConn, logger);
					} else {
						//Move the merged file to the main file
						if (!mergedFile.renameTo(new File(mainFilePath))){
							processLog.incErrors();
							processLog.addNote("Unable to move merged file to main file.");
							logger.error("Unable to move merged file to main file");
							processLog.saveToDatabase(vufindConn, logger);
						}
					}
				}
			}
		}else{
			logger.error("No files were found in " + exportPath);
		}
		processLog.setFinished();
		processLog.saveToDatabase(vufindConn, logger);
	}

	private String getRecordIdFromMarcRecord(Record marcRecord) {
		List<DataField> recordIdField = getDataFields(marcRecord, recordNumberTag);
		//Make sure we only get one ils identifier
		for (DataField curRecordField : recordIdField) {
			Subfield subfieldA = curRecordField.getSubfield('a');
			if (subfieldA != null && (recordNumberPrefix.length() == 0 || subfieldA.getData().length() > recordNumberPrefix.length())) {
				if (curRecordField.getSubfield('a').getData().substring(0, recordNumberPrefix.length()).equals(recordNumberPrefix)) {
					return curRecordField.getSubfield('a').getData();
				}
			}
		}
		return null;
	}

	private List<DataField> getDataFields(Record marcRecord, String tag) {
		List variableFields = marcRecord.getVariableFields(tag);
		List<DataField> variableFieldsReturn = new ArrayList<DataField>();
		for (Object variableField : variableFields){
			if (variableField instanceof DataField){
				variableFieldsReturn.add((DataField)variableField);
			}
		}
		return variableFieldsReturn;
	}
}
