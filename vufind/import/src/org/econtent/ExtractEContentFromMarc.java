package org.econtent;

import java.io.FileReader;
import java.io.InputStream;
import java.net.URL;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Date;

import org.apache.log4j.Logger;
import org.econtent.GutenbergItemInfo;
import org.ini4j.Ini;
import org.vufind.MarcRecordDetails;
import org.vufind.IMarcRecordProcessor;
import org.vufind.IRecordProcessor;
import org.vufind.MarcProcessor;
import org.vufind.Util;

import au.com.bytecode.opencsv.CSVReader;
/**
 * Run this export to build the file to import into VuFind
 * SELECT econtent_record.id, sourceUrl, item_type, filename, folder INTO OUTFILE 'd:/gutenberg_files.csv' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' FROM econtent_record INNER JOIN econtent_item on econtent_record.id = econtent_item.recordId  WHERE source = 'Gutenberg';

 * @author Mark Noble
 *
 */

public class ExtractEContentFromMarc implements IMarcRecordProcessor, IRecordProcessor{
	private Logger logger;
	private String econtentDBConnectionInfo;
	private Connection econtentConn = null;
	private String overdriveUrl;
	private ArrayList<GutenbergItemInfo> gutenbergItemInfo = null;
	
	private String vufindUrl;
	
	private PreparedStatement doesIlsIdExist;
	private PreparedStatement createEContentRecord;
	private PreparedStatement updateEContentRecord;
	//private PreparedStatement createLogEntry;
	//private PreparedStatement markLogEntryFinished = null;
	//private PreparedStatement updateRecordsProcessed;
	private PreparedStatement doesGutenbergItemExist;
	private PreparedStatement addGutenbergItem;
	private PreparedStatement updateGutenbergItem;
	
	//private long logEntryId = -1;
	
	public boolean init(Ini configIni, String serverName, Logger logger) {
		this.logger = logger;
		//Import a marc record into the eContent core. 
		if (!loadConfig(configIni, logger)){
			return false;
		}
		
		try {
			//Connect to the vufind database
			econtentConn = DriverManager.getConnection(econtentDBConnectionInfo);
			doesIlsIdExist = econtentConn.prepareStatement("SELECT id from econtent_record WHERE ilsId = ?");
			createEContentRecord = econtentConn.prepareStatement("INSERT INTO econtent_record (ilsId, cover, source, title, subTitle, author, author2, description, contents, subject, language, publisher, edition, isbn, issn, upc, lccn, topic, genre, region, era, target_audience, sourceUrl, purchaseUrl, publishDate, marcControlField, accessType, date_added, marcRecord) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateEContentRecord = econtentConn.prepareStatement("UPDATE econtent_record SET ilsId = ?, cover = ?, source = ?, title = ?, subTitle = ?, author = ?, author2 = ?, description = ?, contents = ?, subject = ?, language = ?, publisher = ?, edition = ?, isbn = ?, issn = ?, upc = ?, lccn = ?, topic = ?, genre = ?, region = ?, era = ?, target_audience = ?, sourceUrl = ?, purchaseUrl = ?, publishDate = ?, marcControlField = ?, accessType = ?, date_updated = ?, marcRecord = ? WHERE id = ?");
			//createLogEntry = econtentConn.prepareStatement("INSERT INTO econtent_marc_import (filename, dateStarted, status) VALUES (?, ?, 'running')", PreparedStatement.RETURN_GENERATED_KEYS);
			//markLogEntryFinished = econtentConn.prepareStatement("UPDATE econtent_marc_import SET dateFinished = ?, recordsProcessed = ?, status = 'finished' WHERE id = ?");
			//updateRecordsProcessed = econtentConn.prepareStatement("UPDATE econtent_marc_import SET recordsProcessed = ? WHERE id = ?");
			doesGutenbergItemExist = econtentConn.prepareStatement("SELECT id from econtent_item WHERE recordId = ? AND item_type = ? and notes = ?");
			addGutenbergItem = econtentConn.prepareStatement("INSERT INTO econtent_item (recordId, item_type, filename, folder, link, notes, date_added, addedBy, date_updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
			updateGutenbergItem = econtentConn.prepareStatement("UPDATE econtent_item SET filename = ?, folder = ?, link = ?, date_updated =? WHERE recordId = ? AND item_type = ? AND notes = ?");
			
			//Add a log entry to indicate that the marc file is being imported
			/*createLogEntry.setString(1, this.marcRecordPath);
			createLogEntry.setLong(2, new Date().getTime() / 1000);
			createLogEntry.executeUpdate();
			ResultSet logResult = createLogEntry.getGeneratedKeys();
			if (logResult.next()){
				logEntryId = logResult.getLong(1);
			}*/
			
		} catch (Exception ex) {
			// handle any errors
			logger.error("Error initializing econtent extraction ", ex);
			return false;
		}finally{
			/*logger.info("Marking log entry finished");
			try {
				markLogEntryFinished.setLong(1, new Date().getTime() / 1000);
				markLogEntryFinished.setLong(2, this.recordsProcessed);
				markLogEntryFinished.setLong(3, logEntryId);
				markLogEntryFinished.executeUpdate();
			} catch (SQLException e) {
				logger.error("Error importing marking log as finished ", e);
			}*/
		}
		return true;
	}
	
	@Override
	public boolean processMarcRecord(MarcProcessor marcProcessor, MarcRecordDetails recordInfo, int recordStatus, Logger logger) {
		try {
			//Check the 856 tag to see if this is a source that we can handle. 
			String sourceUrl = recordInfo.getSourceUrl();
			String source = null;
			String accessType = null;
			boolean addRecordToEContent = false;
			if (sourceUrl == null){
				//logger.debug("Title does not appear to be econtent");
			}else{
				//logger.info("Checking source url " + sourceUrl);
				if (sourceUrl.matches("(?i).*gutenberg.*")){
					logger.info("Importing gutenberg title");
					source = "Gutenberg";
					accessType = "free";
					addRecordToEContent = true;
				}else if (sourceUrl.matches("(?i).*" + overdriveUrl + ".*")){
					logger.info("Importing overdrive title");
					source = "OverDrive";
					accessType = "free";
					addRecordToEContent = true;
				}else{
					//logger.info("Title does not appear to be econtent " + sourceUrl);
				}
			}
			
			if (addRecordToEContent){
				//Check to see if the record already exists
				String ilsId = recordInfo.getId();
				if (ilsId.length() == 0){
					//Get the ils id
					ilsId = recordInfo.getId();
				}
				boolean importRecordIntoDatabase = true;
				long eContentRecordId = -1;
				if (ilsId.length() == 0){
					logger.warn("ILS Id could not be found in the marc record, importing.  Running this file multiple times could result in duplicate records in the catalog.");
				}else{
					doesIlsIdExist.setString(1, ilsId);
					ResultSet ilsIdExists = doesIlsIdExist.executeQuery();
					if (ilsIdExists.next()){
						//The record already exists, check if it needs to be updated?
						importRecordIntoDatabase = false;
						eContentRecordId = ilsIdExists.getLong("id");
					}else{
						//Add to database
						importRecordIntoDatabase = true;
					}
				}
				
				boolean recordAdded = false;
				if (importRecordIntoDatabase){
					
					//Add to database
					logger.info("Adding ils id " + ilsId + " to the database.");
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
					createEContentRecord.setString(22, Util.getCRSeparatedString(recordInfo.getTargetAudienceTranslated(marcProcessor.getTargetAudienceMap())));
					createEContentRecord.setString(23, recordInfo.getSourceUrl());
					createEContentRecord.setString(24, recordInfo.getPurchaseUrl());
					createEContentRecord.setString(25, recordInfo.getPublishDate());
					createEContentRecord.setString(26, recordInfo.getControlNumber());
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
					logger.info("Updating ilsId " + ilsId + " recordId " + eContentRecordId);
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
					updateEContentRecord.setString(22, Util.getCRSeparatedString(recordInfo.getTargetAudienceTranslated(marcProcessor.getTargetAudienceMap())));
					updateEContentRecord.setString(23, recordInfo.getSourceUrl());
					updateEContentRecord.setString(24, recordInfo.getPurchaseUrl());
					updateEContentRecord.setString(25, recordInfo.getPublishDate());
					updateEContentRecord.setString(26, recordInfo.getControlNumber());
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
					if (source.equals("Gutenberg")){
						attachGutenbergItems(recordInfo, eContentRecordId, logger);
					}
					logger.info("Record processed successfully.");
					reindexRecord(eContentRecordId, logger);
				}else{
					logger.info("Record NOT processed successfully.");
				}
				
				/*updateRecordsProcessed.setLong(1, this.recordsProcessed + 1);
				updateRecordsProcessed.setLong(2, logEntryId);
				updateRecordsProcessed.executeUpdate();*/
				return true;
			}else{
				return false;
			}
		} catch (Exception e) {
			logger.error("Error importing marc record ", e);
			return false;
		}
	}

	private void attachGutenbergItems(MarcRecordDetails recordInfo, long eContentRecordId, Logger logger) {
		//If no, load the source url 
		String sourceUrl = recordInfo.getSourceUrl();
		logger.info("Loading gutenberg items " + sourceUrl);
		try {
			//Get the source URL from the export of all items. 
			for (GutenbergItemInfo curItem : gutenbergItemInfo){
				if (curItem.getSourceUrl().equalsIgnoreCase(recordInfo.getSourceUrl())){
					//Check to see if the item is already attached to the record.  
					doesGutenbergItemExist.setLong(1, eContentRecordId);
					doesGutenbergItemExist.setString(2, curItem.getFormat());
					doesGutenbergItemExist.setString(3, curItem.getNotes());
					ResultSet itemExistRs = doesGutenbergItemExist.executeQuery();
					if (itemExistRs.next()){
						//Check to see if the item needs to be updated (different folder or filename)
						updateGutenbergItem.setString(1, curItem.getFilename());
						updateGutenbergItem.setString(2, curItem.getFolder());
						updateGutenbergItem.setString(3, curItem.getLink());
						updateGutenbergItem.setLong(4, new Date().getTime());
						updateGutenbergItem.setLong(5, eContentRecordId);
						updateGutenbergItem.setString(6, curItem.getFormat());
						updateGutenbergItem.setString(7, curItem.getNotes());
						updateGutenbergItem.executeUpdate();
					}else{
						//Item does not exist, need to add it to the record.
						addGutenbergItem.setLong(1, eContentRecordId);
						addGutenbergItem.setString(2, curItem.getFormat());
						addGutenbergItem.setString(3, curItem.getFilename());
						addGutenbergItem.setString(4, curItem.getFolder());
						addGutenbergItem.setString(5, curItem.getLink());
						addGutenbergItem.setString(6, curItem.getNotes());
						addGutenbergItem.setLong(7, new Date().getTime());
						addGutenbergItem.setInt(8, -1);
						addGutenbergItem.setLong(9, new Date().getTime());
						addGutenbergItem.executeUpdate();
					}
				}
			}
			//Attach items based on the source URL
		} catch (Exception e) {
			logger.info("Unable to add items for " + eContentRecordId, e);
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

	protected boolean loadConfig(Ini configIni, Logger logger) {
		
		econtentDBConnectionInfo = Util.cleanIniValue(configIni.get("Database", "database_econtent_jdbc"));
		if (econtentDBConnectionInfo == null || econtentDBConnectionInfo.length() == 0) {
			logger.error("Database connection information for eContent database not found in General Settings.  Please specify connection information in a econtentDatabase key.");
			return false;
		}
		
		vufindUrl = configIni.get("Site", "url");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
			return false;
		}
		
		//Load link to overdrive if any
		overdriveUrl = configIni.get("OverDrive", "marcIndicator");
		if (overdriveUrl == null || overdriveUrl.length() == 0) {
			logger.warn("Unable to get OverDrive Url in Process settings.  Please add a overdriveUrl key.");
		}
		
		//Get a list of information about Gutenberg items
		String gutenbergItemFile = configIni.get("Reindex", "gutenbergItemFile");
		if (gutenbergItemFile == null || gutenbergItemFile.length() == 0){
			logger.warn("Unable to get Gutenberg Item File in Process settings.  Please add a gutenbergItemFile key.");
		}else{
			//Load the items 
			gutenbergItemInfo = new ArrayList<GutenbergItemInfo>();
			try {
				CSVReader gutenbergReader = new CSVReader(new FileReader(gutenbergItemFile));
				//Read headers
				gutenbergReader.readNext();
				String[] curItemInfo = gutenbergReader.readNext();
				while (curItemInfo != null){
					GutenbergItemInfo itemInfo = new GutenbergItemInfo(curItemInfo[1], curItemInfo[2], curItemInfo[3], curItemInfo[4], curItemInfo[5]);
					gutenbergItemInfo.add(itemInfo);
					curItemInfo = gutenbergReader.readNext();
				}
			} catch (Exception e) {
				logger.error("Could not read Gutenberg Item file");
			}
			
		}
		
		return true;
		
	}

	@Override
	public void finish() {
		try {
			econtentConn.close();
		} catch (SQLException e) {
			logger.error("Unable to close connection", e);
		}
	}
}
