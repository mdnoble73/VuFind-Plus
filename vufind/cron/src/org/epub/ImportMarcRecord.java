package org.epub;

import java.io.File;
import java.io.FileReader;
import java.io.InputStream;
import java.net.URL;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;
import java.util.List;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.vufind.BasicMarcInfo;
import org.vufind.CronLogEntry;
import org.vufind.CronProcessLogEntry;
import org.vufind.IProcessHandler;
import org.vufind.MarcProcessorBase;
import org.vufind.Util;

import au.com.bytecode.opencsv.CSVReader;

public class ImportMarcRecord extends MarcProcessorBase implements IProcessHandler{
	private CronProcessLogEntry processLog;
	private String source;
	private String accessType;
	
	private String vufindUrl;
	private String supplementalFilePath;
	private File supplementalFile;
	private HashMap<String, HashMap<String, String>> supplementalDataByIsbn;
	
	private PreparedStatement doesControlNumberExist;
	private PreparedStatement createEContentRecord;
	private PreparedStatement updateEContentRecord;
	private PreparedStatement createLogEntry;
	private PreparedStatement markLogEntryFinished = null;
	private PreparedStatement updateRecordsProcessed;
	private PreparedStatement updateCollection;
	private PreparedStatement updateSeries;
	
	private long logEntryId = -1;
	
	@Override
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Import eContent Marc Records");
		processLog.saveToDatabase(vufindConn, logger);
		//Import a marc record into the eContent core. 
		if (!loadConfig(servername, configIni, processSettings, logger)){
			return;
		}
		
		try {
			//Connect to the vufind database
			doesControlNumberExist = econtentConn.prepareStatement("SELECT id from econtent_record WHERE marcControlField = ?");
			createEContentRecord = econtentConn.prepareStatement("INSERT INTO econtent_record (ilsId, cover, source, title, subTitle, author, author2, description, contents, subject, language, publisher, edition, isbn, issn, upc, lccn, topic, genre, region, era, target_audience, sourceUrl, purchaseUrl, publishDate, marcControlField, accessType, date_added, marcRecord) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateEContentRecord = econtentConn.prepareStatement("UPDATE econtent_record SET ilsId = ?, cover = ?, source = ?, title = ?, subTitle = ?, author = ?, author2 = ?, description = ?, contents = ?, subject = ?, language = ?, publisher = ?, edition = ?, isbn = ?, issn = ?, upc = ?, lccn = ?, topic = ?, genre = ?, region = ?, era = ?, target_audience = ?, sourceUrl = ?, purchaseUrl = ?, publishDate = ?, marcControlField = ?, accessType = ?, date_updated = ?, marcRecord = ? WHERE id = ?");
			createLogEntry = econtentConn.prepareStatement("INSERT INTO econtent_marc_import (filename, supplementalFilename, source, accessType, dateStarted, status) VALUES (?, ?, ?, ?, ?, 'running')", PreparedStatement.RETURN_GENERATED_KEYS);
			markLogEntryFinished = econtentConn.prepareStatement("UPDATE econtent_marc_import SET dateFinished = ?, recordsProcessed = ?, status = 'finished' WHERE id = ?");
			updateRecordsProcessed = econtentConn.prepareStatement("UPDATE econtent_marc_import SET recordsProcessed = ? WHERE id = ?");
			updateCollection = econtentConn.prepareStatement("UPDATE econtent_record set collection = ? WHERE id = ? and (collection is null or collection = '')");
			updateSeries = econtentConn.prepareStatement("UPDATE econtent_record set series = ? WHERE id = ? and (series is null or series = '')");
			
			//Add a log entry to indicate that the marc file is being imported
			createLogEntry.setString(1, this.marcRecordPath);
			createLogEntry.setString(2, this.supplementalFilePath);
			createLogEntry.setString(3, this.source);
			createLogEntry.setString(4, this.accessType);
			createLogEntry.setLong(5, new Date().getTime() / 1000);
			createLogEntry.executeUpdate();
			ResultSet logResult = createLogEntry.getGeneratedKeys();
			if (logResult.next()){
				logEntryId = logResult.getLong(1);
			}
			
			//Load the supplemental file 
			logger.info("Loading supplemental file contents");
			supplementalDataByIsbn = new HashMap<String, HashMap<String, String>>();
			if (supplementalFilePath != null){
				supplementalFile = new File(supplementalFilePath);
				CSVReader reader = new CSVReader(new FileReader(supplementalFile));
				List<String[]> supplementalData = reader.readAll();
				String[] headerData = supplementalData.get(0);
				for (int i = 1; i < supplementalData.size(); i++){
					String[] fieldData = supplementalData.get(i);
					HashMap<String, String> rowData = new HashMap<String, String>();
					for (int j = 0; j < fieldData.length; j++){
						if (headerData[j].toLowerCase().equals("isbn")){
							//Strip any non numeric characters from the field
							rowData.put(headerData[j].toLowerCase(), fieldData[j].replaceAll("\\D", ""));
						}else{
							rowData.put(headerData[j].toLowerCase(), fieldData[j]);
						}
					}
					supplementalDataByIsbn.put(rowData.get("isbn"), rowData);
				}
				logger.info("Loaded supplemental file found " + supplementalDataByIsbn.size() + " rows");
			}
			
			//Process the reords
			if (!processMarcFiles(logger)) {
				logger.error("Unable to process marc files");
				return;
			}
		} catch (Exception ex) {
			// handle any errors
			logger.error("Error importing marc file ", ex);
			return;
		}finally{
			logger.info("Marking log entry finished");
			try {
				markLogEntryFinished.setLong(1, new Date().getTime() / 1000);
				markLogEntryFinished.setLong(2, this.recordsProcessed);
				markLogEntryFinished.setLong(3, logEntryId);
				markLogEntryFinished.executeUpdate();
			} catch (SQLException e) {
				logger.error("Error importing marking log as finished ", e);
			}
			processLog.setFinished();
			processLog.saveToDatabase(vufindConn, logger);
		}
	}
	
	@Override
	protected boolean processMarcRecord(BasicMarcInfo recordInfo, Logger logger) {
		try {
			//Check to see if the record already exists
			//Use Id number first because multiple copies of a title can be purchased
			//From different sources (i.e. OverDrive and 3M)
			String controlNumber = recordInfo.getId();
			if (controlNumber == null || controlNumber.length() == 0){
				//Get the control number
				controlNumber = recordInfo.getControlNumber();
			}
			boolean importRecordIntoDatabase = true;
			long eContentRecordId = -1;
			if (controlNumber.length() == 0){
				logger.warn("Control number could not be found in the marc record, importing.  Running this file multiple times could result in duplicate records in the catalog.");
			}else{
				doesControlNumberExist.setString(1, controlNumber);
				ResultSet controlNumberExists = doesControlNumberExist.executeQuery();
				if (controlNumberExists.next()){
					//The record already exists, check if it needs to be updated?
					importRecordIntoDatabase = false;
					eContentRecordId = controlNumberExists.getLong("id");
				}else{
					//Add to database
					importRecordIntoDatabase = true;
				}
			}
			
			boolean recordAdded = false;
			//TODO: Look for cover file 
			if (importRecordIntoDatabase){
				//Add to database
				logger.info("Adding control number " + controlNumber + " to the database.");
				createEContentRecord.setString(1, recordInfo.getId());
				createEContentRecord.setString(2, "");
				createEContentRecord.setString(3, source);
				createEContentRecord.setString(4, recordInfo.getMainTitle());
				createEContentRecord.setString(5, recordInfo.getSubTitle());
				createEContentRecord.setString(6, recordInfo.getMainAuthor());
				createEContentRecord.setString(7, Util.getCRSeparatedString(recordInfo.getOtherAuthors()));
				createEContentRecord.setString(8, recordInfo.getDescription());
				createEContentRecord.setString(9, Util.getCRSeparatedString(recordInfo.getContents()));
				createEContentRecord.setString(10, Util.getCRSeparatedString(recordInfo.getSubjects()));
				createEContentRecord.setString(11, recordInfo.getLanguage());
				createEContentRecord.setString(12, recordInfo.getPublisher());
				createEContentRecord.setString(13, recordInfo.getEdition());
				createEContentRecord.setString(14, Util.getCRSeparatedString(recordInfo.getAllIsbns()));
				createEContentRecord.setString(15, Util.getCRSeparatedString(recordInfo.getIssn()));
				createEContentRecord.setString(16, recordInfo.getUpc());
				createEContentRecord.setString(17, recordInfo.getLccn());
				createEContentRecord.setString(18, Util.getCRSeparatedString(recordInfo.getTopic()));
				createEContentRecord.setString(19, Util.getCRSeparatedString(recordInfo.getAllGenres()));
				createEContentRecord.setString(20, Util.getCRSeparatedString(recordInfo.getRegions()));
				createEContentRecord.setString(21, Util.getCRSeparatedString(recordInfo.getEra()));
				createEContentRecord.setString(22, Util.getCRSeparatedString(recordInfo.getTargetAudienceTranslated(targetAudienceMap)));
				createEContentRecord.setString(23, recordInfo.getSourceUrl());
				createEContentRecord.setString(24, recordInfo.getPurchaseUrl());
				createEContentRecord.setString(25, recordInfo.getPublishDate());
				createEContentRecord.setString(26, controlNumber);
				createEContentRecord.setString(27, accessType);
				createEContentRecord.setLong(28, new Date().getTime() / 1000);
				createEContentRecord.setString(29, recordInfo.toString());
				int rowsInserted = createEContentRecord.executeUpdate();
				if (rowsInserted != 1){
					logger.error("Could not insert row into the database");
				}else{
					ResultSet generatedKeys = createEContentRecord.getGeneratedKeys();
					while (generatedKeys.next()){
						eContentRecordId = generatedKeys.getLong(1);
						recordAdded = true;
					}
				}
			}else{
				//Update the record
				logger.info("Updating control number " + controlNumber + " recordId " + eContentRecordId);
				updateEContentRecord.setString(1, recordInfo.getId());
				updateEContentRecord.setString(2, "");
				updateEContentRecord.setString(3, source);
				updateEContentRecord.setString(4, recordInfo.getMainTitle());
				updateEContentRecord.setString(5, recordInfo.getSubTitle());
				updateEContentRecord.setString(6, recordInfo.getMainAuthor());
				updateEContentRecord.setString(7, Util.getCRSeparatedString(recordInfo.getOtherAuthors()));
				updateEContentRecord.setString(8, recordInfo.getDescription());
				updateEContentRecord.setString(9, Util.getCRSeparatedString(recordInfo.getContents()));
				updateEContentRecord.setString(10, Util.getCRSeparatedString(recordInfo.getSubjects()));
				updateEContentRecord.setString(11, recordInfo.getLanguage());
				updateEContentRecord.setString(12, recordInfo.getPublisher());
				updateEContentRecord.setString(13, recordInfo.getEdition());
				updateEContentRecord.setString(14, Util.getCRSeparatedString(recordInfo.getAllIsbns()));
				updateEContentRecord.setString(15, Util.getCRSeparatedString(recordInfo.getIssn()));
				updateEContentRecord.setString(16, recordInfo.getUpc());
				updateEContentRecord.setString(17, recordInfo.getLccn());
				updateEContentRecord.setString(18, Util.getCRSeparatedString(recordInfo.getTopic()));
				updateEContentRecord.setString(19, Util.getCRSeparatedString(recordInfo.getAllGenres()));
				updateEContentRecord.setString(20, Util.getCRSeparatedString(recordInfo.getRegions()));
				updateEContentRecord.setString(21, Util.getCRSeparatedString(recordInfo.getEra()));
				updateEContentRecord.setString(22, Util.getCRSeparatedString(recordInfo.getTargetAudienceTranslated(targetAudienceMap)));
				updateEContentRecord.setString(23, recordInfo.getSourceUrl());
				updateEContentRecord.setString(24, recordInfo.getPurchaseUrl());
				updateEContentRecord.setString(25, recordInfo.getPublishDate());
				updateEContentRecord.setString(26, controlNumber);
				updateEContentRecord.setString(27, accessType);
				updateEContentRecord.setLong(28, new Date().getTime() / 1000);
				updateEContentRecord.setString(29, recordInfo.toString());
				updateEContentRecord.setLong(30, eContentRecordId);
				int rowsInserted = updateEContentRecord.executeUpdate();
				if (rowsInserted != 1){
					logger.error("Could not insert row into the database");
				}else{
					recordAdded = true;
				}
			}
			
			logger.info("Finished initial insertion/update recordAdded = " + recordAdded);
			
			if (recordAdded){
				logger.info("Record processed successfully.");
				//Process supplemental information 
				ArrayList<String> allIsbns = recordInfo.getAllIsbns();
				boolean foundSupplementalData = false;
				for (String curIsbn : allIsbns){
					if (supplementalDataByIsbn.containsKey(curIsbn)){
						foundSupplementalData = true;
						logger.info("Found supplemental data for " + curIsbn + ".");
						HashMap<String, String> supplementalInfo = supplementalDataByIsbn.get(curIsbn);
						if (supplementalInfo.containsKey("collection")){
							logger.info("Setting collection to " + supplementalInfo.get("collection") + ".");
							updateCollection.setString(1, supplementalInfo.get("collection"));
							updateCollection.setLong(2, eContentRecordId);
							updateCollection.executeUpdate();
						}
						if (supplementalInfo.containsKey("series")){
							logger.info("Setting series to " + supplementalInfo.get("series") + ".");
							updateSeries.setString(1, supplementalInfo.get("series"));
							updateSeries.setLong(2, eContentRecordId);
							updateSeries.executeUpdate();
						}
					}
				}
				if (!foundSupplementalData){
					logger.warn("Did not find supplemental data for " + eContentRecordId);
				}
				reindexRecord(eContentRecordId, logger);
			}else{
				logger.info("Record NOT processed successfully.");
			}
			
			updateRecordsProcessed.setLong(1, this.recordsProcessed + 1);
			updateRecordsProcessed.setLong(2, logEntryId);
			updateRecordsProcessed.executeUpdate();
			return true;
		} catch (Exception e) {
			logger.error("Error importing marc record ", e);
			return false;
		}
	}

	private void reindexRecord(long eContentRecordId, Logger logger) {
		//reindex the new record
		try {
			URL url = new URL(vufindUrl + "/EContentRecord/" + eContentRecordId + "/Reindex");
			Object reindexResultRaw = url.getContent();
			if (reindexResultRaw instanceof InputStream) {
				String updateIndexResponse = Util.convertStreamToString((InputStream) reindexResultRaw);
				logger.info("Indexing record " + eContentRecordId + " response: " + updateIndexResponse);
			}
		} catch (Exception e) {
			logger.info("Unable to reindex record " + eContentRecordId, e);
		}
	}

	protected boolean loadConfig(String servername, Ini configIni, Section processSettings, Logger logger) {
		if (!super.loadConfig(servername, configIni, processSettings, logger)){
			return false;
		}
		
		vufindUrl = configIni.get("Site", "url");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
			return false;
		}
		
		//Get the file to import (overrides the base class)
		marcRecordPath = processSettings.get("marcFile");
		if (marcRecordPath == null || marcRecordPath.length() == 0) {
			logger.error("Unable to get Marc File to import in Process settings.  Please add a marcFile key.");
			return false;
		}
		
		supplementalFilePath = processSettings.get("supplementalFile");
		if (supplementalFilePath == null || supplementalFilePath.length() == 0) {
			logger.warn("Unable to get Supplemental CSV File to import in Process settings.  Please add a marcFile key.");
		}
		
		source = processSettings.get("source");
		if (source == null || source.length() == 0) {
			logger.error("Unable to get source of the records from Process settings.  Please add a source key.");
			return false;
		}
		
		accessType = processSettings.get("accessType");
		if (accessType == null || accessType.length() == 0) {
			logger.error("Unable to get accessType of the records from Process settings.  Please add a accessType key.");
			return false;
		}
		
		return true;
		
	}
}
