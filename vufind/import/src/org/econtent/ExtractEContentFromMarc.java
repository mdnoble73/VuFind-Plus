package org.econtent;

import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;
import java.net.MalformedURLException;
import java.sql.*;
import java.util.ArrayList;
import java.util.Collection;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;
import java.util.LinkedHashSet;
import java.util.Set;
import java.util.regex.Pattern;

import org.apache.log4j.Logger;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrServer;
import org.apache.solr.common.SolrInputDocument;
import org.ini4j.Ini;
import org.solrmarc.tools.Utils;
import org.vufind.IMarcRecordProcessor;
import org.vufind.IRecordProcessor;
import org.vufind.LexileData;
import org.vufind.LibraryIndexingInfo;
import org.vufind.LocationIndexingInfo;
import org.vufind.MarcProcessor;
import org.vufind.MarcRecordDetails;
import org.vufind.ProcessorResults;
import org.vufind.ReindexProcess;
import org.vufind.URLPostResponse;
import org.vufind.Util;

import au.com.bytecode.opencsv.CSVReader;
import au.com.bytecode.opencsv.CSVWriter;
/**
 * Run this export to build the file to import into VuFind
 * SELECT econtent_record.id, sourceUrl, item_type, filename, folder INTO OUTFILE 'd:/gutenberg_files.csv' FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '"' FROM econtent_record INNER JOIN econtent_item on econtent_record.id = econtent_item.recordId  WHERE source = 'Gutenberg';

 * @author Mark Noble
 *
 */

public class ExtractEContentFromMarc implements IMarcRecordProcessor, IRecordProcessor{
	private MarcProcessor marcProcessor;
	private String solrPort;
	private Logger logger;
	private ConcurrentUpdateSolrServer updateServer;
	private Connection econtentConn;
	
	private String localWebDir;

	private boolean clearEContentRecordsAtStartOfIndex;
	private boolean extractEContentFromUnchangedRecords;
	private int numOverDriveTitlesToLoadFromAPI;
	private ArrayList<GutenbergItemInfo> gutenbergItemInfo = null;
	
	private String vufindUrl;
	
	private HashMap<String, EcontentRecordInfo> existingEcontentIlsIds = new HashMap<String, EcontentRecordInfo>();
	private HashMap<String, EcontentRecordInfo> overDriveTitlesWithoutIlsId = new HashMap<String, EcontentRecordInfo>();
	
	private PreparedStatement createEContentRecord;
	private PreparedStatement updateEContentRecord;
	private PreparedStatement createEContentRecordForOverDrive;
	private PreparedStatement updateEContentRecordForOverDrive;
	private PreparedStatement deleteEContentItem;
	private PreparedStatement deleteEContentRecordItems;
	private PreparedStatement deleteEContentRecord;
	private PreparedStatement doesGutenbergItemExist;
	private PreparedStatement addGutenbergItem;
	private PreparedStatement updateGutenbergItem;
	
	private PreparedStatement existingEContentRecordLinks;
	private PreparedStatement addSourceUrl;
	private PreparedStatement updateSourceUrl;
	
	private PreparedStatement doesOverDriveItemExist;
	private PreparedStatement addOverDriveItem;
	private PreparedStatement updateOverDriveItem;
	
	private PreparedStatement getEContentRecordStmt;
	private PreparedStatement getItemsForEContentRecordStmt;
	private PreparedStatement getAvailabilityForEContentRecordStmt;
	
	private PreparedStatement doesOverDriveAvailabilityExist;
	private PreparedStatement addOverDriveAvailability;
	private PreparedStatement updateOverDriveAvailability;
	
	private PreparedStatement loadOverDriveFormatsStmt;
	private PreparedStatement loadOverDriveAvailabilityStmt;
	private PreparedStatement loadOverDriveContributorsStmt;
	private PreparedStatement loadOverDriveMetadataStmt;
	private PreparedStatement loadOverDriveSubjectsStmt;
	private PreparedStatement loadOverDriveISBNStmt;
	private PreparedStatement loadOverDriveUPCStmt;
	private PreparedStatement loadOverDriveLanguagesStmt;
	
	public ProcessorResults results;
	
	private HashMap<String, OverDriveBasicInfo> overDriveTitleInfo = new HashMap<String, OverDriveBasicInfo>();
	
	private HashMap<String, String> processedOverDriveRecords = new HashMap<String, String>();
	private HashMap<String, ArrayList<String>> duplicateOverDriveRecordsInMillennium = new HashMap<String, ArrayList<String>>();
	private HashMap<String, MarcRecordDetails> millenniumRecordsNotInOverDrive = new HashMap<String, MarcRecordDetails>();
	private HashSet<String> recordsWithoutOverDriveId = new HashSet<String>(); 
	
	public boolean init(Ini configIni, String serverName, long reindexLogId, Connection vufindConn, Connection econtentConn, Logger logger) {
		this.logger = logger;
		//Import a marc record into the eContent core. 
		if (!loadConfig(configIni, logger)){
			return false;
		}
		this.econtentConn = econtentConn;
		results = new ProcessorResults("Extract eContent from ILS", reindexLogId, vufindConn, logger);
		solrPort = configIni.get("Reindex", "solrPort");
		
		localWebDir = configIni.get("Site", "local");
		
		//Initialize the updateServer
		try {
			updateServer = new ConcurrentUpdateSolrServer("http://localhost:" + solrPort + "/solr/biblio2", 500, 10);
			//updateServer = new ConcurrentUpdateSolrServer("http://localhost:" + solrPort + "/solr/econtent2", 500, 10);
		} catch (MalformedURLException e) {
			logger.error("Error updating solr", e);
		}
		
		//Check to see if we should clear the existing index
		String clearEContentRecordsAtStartOfIndexVal = configIni.get("Reindex", "clearEContentRecordsAtStartOfIndex");
		clearEContentRecordsAtStartOfIndex = clearEContentRecordsAtStartOfIndexVal != null && Boolean.parseBoolean(clearEContentRecordsAtStartOfIndexVal);

		results.addNote("clearEContentRecordsAtStartOfIndex = " + clearEContentRecordsAtStartOfIndex);
		if (clearEContentRecordsAtStartOfIndex){
			logger.info("Clearing existing econtent records from index");
			results.addNote("clearing existing econtent records");
			URLPostResponse response = Util.postToURL("http://localhost:" + solrPort + "/solr/biblio2/update/?commit=true", "<delete><query>recordtype:econtentRecord</query></delete>", logger);
			//URLPostResponse response = Util.postToURL("http://localhost:" + solrPort + "/solr/econtent2/update/?commit=true", "<delete><query>recordtype:econtentRecord</query></delete>", logger);
			if (!response.isSuccess()){
				results.addNote("Error clearing existing econtent records " + response.getMessage());
			}
		}
		
		String extractEContentFromUnchangedRecordsVal = configIni.get("Reindex", "extractEContentFromUnchangedRecords");
		if (extractEContentFromUnchangedRecordsVal == null){
			logger.debug("Did not get a value for extractEContentFromUnchangedRecords");
			extractEContentFromUnchangedRecords = false;
		}else{
			extractEContentFromUnchangedRecords = Boolean.parseBoolean(extractEContentFromUnchangedRecordsVal);
			logger.debug("extractEContentFromUnchangedRecords = " + extractEContentFromUnchangedRecords + " " + extractEContentFromUnchangedRecords);
		}
		if (clearEContentRecordsAtStartOfIndex) {extractEContentFromUnchangedRecords = true;}
		results.addNote("extractEContentFromUnchangedRecords = " + extractEContentFromUnchangedRecords);
		
		String numOverDriveTitlesToLoadFromAPIVal = configIni.get("Reindex", "numOverDriveTitlesToLoadFromAPI");
		if (numOverDriveTitlesToLoadFromAPIVal == null){
			numOverDriveTitlesToLoadFromAPI = -1;
		}else{
			numOverDriveTitlesToLoadFromAPI = Integer.parseInt(numOverDriveTitlesToLoadFromAPIVal);
		}
		results.addNote("numOverDriveTitlesToLoadFromAPI = " + numOverDriveTitlesToLoadFromAPI);
		
		try {
			//Connect to the vufind database
			createEContentRecord = econtentConn.prepareStatement("INSERT INTO econtent_record (ilsId, cover, source, title, subTitle, author, author2, description, contents, subject, language, publisher, publishLocation, physicalDescription, edition, isbn, issn, upc, lccn, topic, genre, region, era, target_audience, sourceUrl, purchaseUrl, publishDate, marcControlField, accessType, date_added, marcRecord, externalId, itemLevelOwnership) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateEContentRecord = econtentConn.prepareStatement("UPDATE econtent_record SET ilsId = ?, cover = ?, source = ?, title = ?, subTitle = ?, author = ?, author2 = ?, description = ?, contents = ?, subject = ?, language = ?, publisher = ?, publishLocation = ?, physicalDescription = ?, edition = ?, isbn = ?, issn = ?, upc = ?, lccn = ?, topic = ?, genre = ?, region = ?, era = ?, target_audience = ?, sourceUrl = ?, purchaseUrl = ?, publishDate = ?, marcControlField = ?, accessType = ?, date_updated = ?, marcRecord = ?, externalId = ?, itemLevelOwnership = ?, status = 'active' WHERE id = ?");
			
			createEContentRecordForOverDrive = econtentConn.prepareStatement("INSERT INTO econtent_record (ilsId, cover, source, title, author, author2, description, subject, language, publisher, edition, isbn, upc, publishDate, accessType, date_added, externalId, itemLevelOwnership, series) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateEContentRecordForOverDrive = econtentConn.prepareStatement("UPDATE econtent_record SET ilsId = NULL, cover = ?, source = ?, title = ?, author = ?, author2 = ?, description = ?, subject = ?, language = ?, publisher = ?, edition = ?, isbn = ?, upc = ?, publishDate = ?, accessType = ?, date_updated = ?, externalId = ?, itemLevelOwnership = ?, series = ?, status = 'active' WHERE id = ?");
			
			deleteEContentRecord = econtentConn.prepareStatement("UPDATE econtent_record set status = 'deleted' where id = ?");
			deleteEContentRecordItems = econtentConn.prepareStatement("DELETE FROM econtent_item where recordId = ?");
			deleteEContentItem = econtentConn.prepareStatement("DELETE FROM econtent_item where id = ?");
			
			doesGutenbergItemExist = econtentConn.prepareStatement("SELECT id from econtent_item WHERE recordId = ? AND item_type = ? and notes = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addGutenbergItem = econtentConn.prepareStatement("INSERT INTO econtent_item (recordId, item_type, filename, folder, link, notes, date_added, addedBy, date_updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
			updateGutenbergItem = econtentConn.prepareStatement("UPDATE econtent_item SET filename = ?, folder = ?, link = ?, date_updated =? WHERE recordId = ? AND item_type = ? AND notes = ?");
			
			existingEContentRecordLinks = econtentConn.prepareStatement("SELECT id, link, libraryId, item_type from econtent_item WHERE recordId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addSourceUrl = econtentConn.prepareStatement("INSERT INTO econtent_item (recordId, item_type, notes, link, date_added, addedBy, date_updated, libraryId) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
			updateSourceUrl = econtentConn.prepareStatement("UPDATE econtent_item SET link = ?, date_updated = ?, item_type = ?, notes = ? WHERE id = ?");
			
			doesOverDriveItemExist =  econtentConn.prepareStatement("SELECT id from econtent_item WHERE recordId = ? AND externalFormatId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addOverDriveItem = econtentConn.prepareStatement("INSERT INTO econtent_item (recordId, item_type, externalFormat, externalFormatId, externalFormatNumeric, identifier, sampleName_1, sampleUrl_1, sampleName_2, sampleUrl_2, date_added, addedBy, date_updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", Statement.RETURN_GENERATED_KEYS);
			updateOverDriveItem = econtentConn.prepareStatement("UPDATE econtent_item SET externalFormat = ?, externalFormatId = ?, externalFormatNumeric = ?, identifier = ?, sampleName_1 = ?, sampleUrl_1 = ?, sampleName_2 = ?, sampleUrl_2 = ?, date_updated =? WHERE id = ?");
			
			doesOverDriveAvailabilityExist = econtentConn.prepareStatement("SELECT id from econtent_availability where recordId = ? and libraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addOverDriveAvailability = econtentConn.prepareStatement("INSERT INTO econtent_availability (recordId, copiesOwned, availableCopies, numberOfHolds, libraryId) VALUES (?, ?, ?, ?, ?)");
			updateOverDriveAvailability = econtentConn.prepareStatement("UPDATE econtent_availability SET copiesOwned = ?, availableCopies = ?, numberOfHolds = ? WHERE id = ?");
			
			getEContentRecordStmt = econtentConn.prepareStatement("SELECT * FROM econtent_record WHERE id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getItemsForEContentRecordStmt = econtentConn.prepareStatement("SELECT * FROM econtent_item WHERE recordId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getAvailabilityForEContentRecordStmt= econtentConn.prepareStatement("SELECT * FROM econtent_availability WHERE recordId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			
			loadOverDriveFormatsStmt = econtentConn.prepareStatement("SELECT * FROM overdrive_api_product_formats WHERE productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			loadOverDriveAvailabilityStmt = econtentConn.prepareStatement("SELECT * FROM overdrive_api_product_availability WHERE productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			loadOverDriveContributorsStmt = econtentConn.prepareStatement("SELECT fileAs FROM overdrive_api_product_creators WHERE productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			loadOverDriveMetadataStmt = econtentConn.prepareStatement("SELECT * FROM overdrive_api_product_metadata WHERE productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			loadOverDriveISBNStmt = econtentConn.prepareStatement("SELECT type, value FROM overdrive_api_product_identifiers WHERE productId = ? and type = 'ISBN'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			loadOverDriveUPCStmt = econtentConn.prepareStatement("SELECT type, value FROM overdrive_api_product_identifiers WHERE productId = ? and type = 'UPC'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			loadOverDriveSubjectsStmt= econtentConn.prepareStatement("SELECT name FROM overdrive_api_product_subjects INNER JOIN overdrive_api_product_subjects_ref on overdrive_api_product_subjects_ref.subjectId = overdrive_api_product_subjects.id WHERE productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			loadOverDriveLanguagesStmt = econtentConn.prepareStatement("SELECT name FROM overdrive_api_product_languages INNER JOIN overdrive_api_product_languages_ref on overdrive_api_product_languages_ref.languageId = overdrive_api_product_languages.id WHERE productId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			
			PreparedStatement existingEcontentIlsIdsStmt = econtentConn.prepareStatement("SELECT econtent_record.id, ilsId, status, externalId, count(econtent_item.id) as numItems from econtent_item RIGHT join econtent_record on econtent_record.id = recordId where ilsId is not null and ilsId != '' and status = 'active' group by econtent_record.id", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet existingEcontentIlsIdsRS = existingEcontentIlsIdsStmt.executeQuery();
			HashMap <String, String> ilsIdsForExternalIds = new HashMap<String, String>();
			while (existingEcontentIlsIdsRS.next()){
				EcontentRecordInfo recordInfo = new EcontentRecordInfo();
				recordInfo.setRecordId(existingEcontentIlsIdsRS.getLong(1));
				recordInfo.setIlsId(existingEcontentIlsIdsRS.getString(2));
				recordInfo.setStatus(existingEcontentIlsIdsRS.getString(3));
				recordInfo.setExternalId(existingEcontentIlsIdsRS.getString(4));
				recordInfo.setNumItems(existingEcontentIlsIdsRS.getInt(5));
				if (existingEcontentIlsIds.containsKey(recordInfo.getIlsId())){
					//More than one record has been created for this ilsId.  Only want one active record 
					if (recordInfo.getStatus().equals("active")){
						EcontentRecordInfo existingInfo = existingEcontentIlsIds.get(recordInfo.getIlsId());
						if (!existingInfo.getStatus().equals("active")){
							//Existing record is not active and new record is, use the new
							if (recordInfo.getStatus().equals("active") && recordInfo.getExternalId() != null && recordInfo.getExternalId().length() != 0){
								ilsIdsForExternalIds.put(recordInfo.getExternalId(), recordInfo.getIlsId());
							}
							logger.debug(recordInfo.getIlsId());
							existingEcontentIlsIds.put(recordInfo.getIlsId(), recordInfo);
						}else if (existingInfo.getStatus().equals("active")){
							//Existing record is active and new record is not, keep the existing
							logger.warn("Warning ilsId " + recordInfo.getIlsId() + " is not unique in the econtent record table, it is active for both " + existingInfo.getRecordId() + " and " + recordInfo.getRecordId() + " using first record found");
							deleteEContentRecord(recordInfo);
						}
					}
				}else{
					if (recordInfo.getStatus().equals("active") && recordInfo.getExternalId() != null && recordInfo.getExternalId().length() != 0){
						ilsIdsForExternalIds.put(recordInfo.getExternalId(), recordInfo.getIlsId());
					}
					logger.debug(recordInfo.getIlsId());
					existingEcontentIlsIds.put(recordInfo.getIlsId(), recordInfo);
				}
			}
			results.addNote("Found " + existingEcontentIlsIds.size() + " records with ilsids in the database.");
			results.addNote(ilsIdsForExternalIds.size() + " records with external ids also have ilsIds");
			
			PreparedStatement overDriveTitlesWithoutIlsIdStmt = econtentConn.prepareStatement("SELECT econtent_record.id, externalId, status, count(econtent_item.id) as numItems from econtent_item RIGHT join econtent_record on econtent_record.id = recordId WHERE externalId is NOT NULL AND (ilsId IS NULL or ilsId = '') and status = 'active' group by econtent_record.id", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet overDriveTitlesWithoutIlsIdRS = overDriveTitlesWithoutIlsIdStmt.executeQuery();
			while (overDriveTitlesWithoutIlsIdRS.next()){
				EcontentRecordInfo recordInfo = new EcontentRecordInfo();
				recordInfo.setRecordId(overDriveTitlesWithoutIlsIdRS.getLong(1));
				recordInfo.setExternalId(overDriveTitlesWithoutIlsIdRS.getString(2));
				recordInfo.setStatus(overDriveTitlesWithoutIlsIdRS.getString(3));
				recordInfo.setNumItems(overDriveTitlesWithoutIlsIdRS.getInt(4));
				if (ilsIdsForExternalIds.containsKey(recordInfo.getExternalId())){
					logger.warn("Record " + recordInfo.getExternalId() + " now has a marc record.  Removing old record");
					deleteEContentRecord(recordInfo);
				}else{
					if (overDriveTitlesWithoutIlsId.containsKey(recordInfo.getExternalId())){
						if (recordInfo.getStatus().equals("active")){
							EcontentRecordInfo existingInfo = overDriveTitlesWithoutIlsId.get(recordInfo.getExternalId());
							if (!existingInfo.getStatus().equals("active")){
								//Existing record is not active and new record is, use the new 
								overDriveTitlesWithoutIlsId.put(recordInfo.getExternalId(), recordInfo);
							}else if (existingInfo.getStatus().equals("active")){
								//Existing record is active and new record is not, keep the existing
								logger.warn("Warning externalId " + recordInfo.getExternalId() + " is not unique in the econtent record table, it is active for both " + existingInfo.getRecordId() + " and " + recordInfo.getRecordId() + " using first record found");
								deleteEContentRecord(recordInfo);
							}
						}
					}else{
						overDriveTitlesWithoutIlsId.put(recordInfo.getExternalId(), recordInfo);
					}
				}
			}
			
			
			results.addNote("Found " + overDriveTitlesWithoutIlsId.size() + " records without ilsids in the database.");
			
		} catch (Exception ex) {
			// handle any errors
			logger.error("Error initializing econtent extraction ", ex);
			return false;
		}finally{
			results.saveResults();
		}
		
		return loadOverDriveTitlesFromDB();
	}

	
	private void deleteEContentRecord(EcontentRecordInfo econtentInfo) {
		try {
			if (econtentInfo.getIlsId() == null || econtentInfo.getIlsId().length() == 0){
				logger.debug("ExternalId " + econtentInfo.getExternalId() + ", record " + econtentInfo.getRecordId() + " is no longer valid, removing");
			}else{
				logger.debug("ILSId " + econtentInfo.getIlsId() + ", record " + econtentInfo.getRecordId() + " is no longer valid, removing");
			}
			deleteEContentRecord.setLong(1, econtentInfo.getRecordId());
			deleteEContentRecord.executeUpdate();
			deleteEContentRecordItems.setLong(1, econtentInfo.getRecordId());
			deleteEContentRecordItems.executeUpdate();
			deleteRecord(econtentInfo.getRecordId());
			results.incDeleted();
		} catch (SQLException e) {
			logger.error("Error deleting eContent record that no longer exists " + econtentInfo.getRecordId(), e);
			results.incErrors();
		}
	}


	/**
	 * Load overdrive information from the database
	 * 
	 * @return true or false depending on if the titles could be loaded from the database.
	 */
	private boolean loadOverDriveTitlesFromDB() {
		results.addNote("Loading OverDrive information from Database");
		results.saveResults();
		
		try {
			PreparedStatement productsStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_products");
			ResultSet productsRS = productsStmt.executeQuery();
			while (productsRS.next()){
				OverDriveBasicInfo basicInfo = new OverDriveBasicInfo();
				basicInfo.setId(productsRS.getLong("id"));
				basicInfo.setOverdriveId(productsRS.getString("overdriveId"));
				basicInfo.setTitle(productsRS.getString("title"));
				basicInfo.setSeries(productsRS.getString("series"));
				basicInfo.setAuthor(productsRS.getString("primaryCreatorName"));
				basicInfo.setCover(productsRS.getString("cover"));
				basicInfo.setMediaType(productsRS.getString("mediaType"));
				basicInfo.setLastChange(Math.max(Math.max(Math.max(productsRS.getLong("dateUpdated"), productsRS.getLong("lastMetadataChange")), productsRS.getLong("lastAvailabilityChange")), productsRS.getLong("dateDeleted")));
				basicInfo.setDeleted(productsRS.getBoolean("deleted"));
				overDriveTitleInfo.put(basicInfo.getOverdriveId().toLowerCase(), basicInfo);
			}
			logger.debug("Loaded " + overDriveTitleInfo.size() + " overdrive products from the database");
		} catch (SQLException e) {
			results.addNote("error loading OverDrive information from database " + e.toString());
			results.incErrors();
			logger.error("Error loading OverDrive information from database", e);
			return false;
		}
		
		return true;
	}

	@Override
	public boolean processMarcRecord(MarcProcessor marcProcessor, MarcRecordDetails recordInfo, int recordStatus, Logger logger) {
		this.marcProcessor = marcProcessor; 
		try {
			if (recordInfo.isEContent()){
				results.incEContentRecordsProcessed();
			}else{
				results.incRecordsProcessed();
			}
			String ilsId = recordInfo.getIlsId();
			if (!recordInfo.isEContent()){
				//This record is not econtent
				if (existingEcontentIlsIds.containsKey(ilsId)){
					//Delete the existing record
					EcontentRecordInfo econtentInfo = existingEcontentIlsIds.get(ilsId);
					if (econtentInfo.getStatus().equals("active")){
						logger.debug("Record " + econtentInfo.getIlsId() + " is no longer eContent, removing");
						deleteEContentRecord.setLong(1, econtentInfo.getRecordId());
						deleteEContentRecord.executeUpdate();
						if (!clearEContentRecordsAtStartOfIndex){
								deleteRecord(econtentInfo.getRecordId());
						}
						results.incDeleted();
					}else{
						results.incSkipped();
					}
					existingEcontentIlsIds.remove(ilsId);
				}else{
					//logger.debug("Skipping record, it is not eContent");
					results.incSkipped();
				}
				return false;
			}
			
			//First things first, check to see if we have indexed this before.
			//If we have, set the econtent record id so we have it for use later
			EcontentRecordInfo existingRecordInfo = null;
			boolean existingRecord = false;
			String overDriveId = recordInfo.getExternalId();
			if (existingEcontentIlsIds.containsKey(ilsId)){
				logger.debug("Found existing econtent record for ilsId " + ilsId);
				existingRecordInfo = existingEcontentIlsIds.get(ilsId);
				logger.debug("  existing id is " + existingRecordInfo.getRecordId());
				recordInfo.seteContentRecordId(existingRecordInfo.getRecordId());
				existingEcontentIlsIds.remove(ilsId);
				existingRecord = true;
			}else{
				//Check based on overdrive id if any (since we may be converting from API loaded title to marc
				if (overDriveId != null && overDriveTitlesWithoutIlsId.containsKey(overDriveId)){
					logger.debug("Found existing econtent record based on overdrive id " + overDriveId);
					EcontentRecordInfo eContentRecordInfo = overDriveTitlesWithoutIlsId.get(overDriveId);
					recordInfo.seteContentRecordId(eContentRecordInfo.getRecordId());
					existingRecord = true;
					overDriveTitlesWithoutIlsId.remove(overDriveId);
				}else{
					logger.debug("Did not find existing econtent record for ilsId " + ilsId);
				}
			}
			
			//Record is eContent, get additional details about how to process it.
			HashMap<String, DetectionSettings> detectionSettingsBySource = recordInfo.getEContentDetectionSettings();
			if (detectionSettingsBySource == null || detectionSettingsBySource.size() == 0){
				logger.error("Record " + ilsId + " was tagged as eContent, but we did not get detection settings for it.");
				results.addNote("Record " + ilsId + " was tagged as eContent, but we did not get detection settings for it.");
				results.incErrors();
				return false;
			}

			//Don't iterate through sources since then we get multiple records for a single ILS which leads to major problems.
			//Index based on the main source for now.
			String source = detectionSettingsBySource.keySet().iterator().next();
			String allSources = Util.getCRSeparatedString(detectionSettingsBySource.keySet());
			//for (String source : detectionSettingsBySource.keySet()){
			logger.debug("Record " + ilsId + " is eContent, first source is " + source + " there are " + detectionSettingsBySource.size() + " source(s)");
			DetectionSettings detectionSettings = detectionSettingsBySource.get(source);
			//Generally should only have one source, but in theory there could be multiple sources for a single record
			String accessType = detectionSettings.getAccessType();
			//Make sure that overdrive titles are updated if we need to check availability
			OverDriveBasicInfo overdriveBasicInfo = null;

			if (source.matches("(?i)^overdrive.*")){
				if (overDriveId != null){
					overdriveBasicInfo = overDriveTitleInfo.get(overDriveId.toLowerCase());
					if (overdriveBasicInfo != null){
						//Check to see if data changed since the last index time
						if (overdriveBasicInfo.getLastChange() >= ReindexProcess.getLoadChangesSince() || extractEContentFromUnchangedRecords || (recordStatus == MarcProcessor.RECORD_CHANGED_PRIMARY) || (recordStatus == MarcProcessor.RECORD_CHANGED_SECONDARY)){
							//This record should be reindexed
							logger.debug("Overdrive record has changed, reindexing (infoChanged = " + (overdriveBasicInfo.getLastChange() > ReindexProcess.getLoadChangesSince()) + ", extractEContentFromUnchangedRecords=" + extractEContentFromUnchangedRecords + ", recordStatus changed primary=" + (recordStatus == MarcProcessor.RECORD_CHANGED_PRIMARY) + ", changedsecondary=" + (recordStatus == MarcProcessor.RECORD_CHANGED_SECONDARY) + ")");
						}else if (recordInfo.geteContentRecordId() == null){
							logger.debug("Overdrive record has not changed, but is not in the database, reindexing.");
						}else if (existingRecordInfo.getNumItems() == 0){
							logger.debug("Record is unchanged, but there are no items so indexing to try to get items.");
						}else if (!existingRecordInfo.getStatus().equalsIgnoreCase("active")){
							logger.debug("Record is unchanged, is not active indexing to correct the status.");
						}else{
							logger.debug("Skipping overdrive record because the record is not changed");
							results.incSkipped();
							return false;
						}
					}else{
						//Overdrive record, force processing to make sure we get updated availability
						logger.debug("Record is overdrive (" + overDriveId + "), but didn't get Basic information from API");
					}
				}else{
					logger.debug("Record is tagged as overdrive, but didn't find a URL in the marc record to extract the id from.");
					results.incErrors();
					results.addNote("Did not find overdrive id in marc record " + ilsId);
					recordsWithoutOverDriveId.add(ilsId);
					return false;
				}
			}else if (recordStatus == MarcProcessor.RECORD_UNCHANGED){
				if (extractEContentFromUnchangedRecords){
					logger.debug("Record is unchanged, but reindex unchanged records is on");
				}else if (!existingRecord){
					logger.debug("Record is unchanged, but the record does not exist in the eContent database.");
				}else if (existingRecordInfo.getNumItems() == 0){
					logger.debug("Record is unchanged, but there are no items so indexing to try to get items.");
				}else if (!existingRecordInfo.getStatus().equalsIgnoreCase("active")){
					logger.debug("Record is unchanged, is not active indexing to correct the status.");
				}else{
					logger.debug("Skipping because the record is not changed");
					results.incSkipped();
					return false;
				}
			}else{
				if (recordStatus == MarcProcessor.RECORD_CHANGED_PRIMARY || recordStatus == MarcProcessor.RECORD_CHANGED_SECONDARY){
					logger.debug("Record has changed");
				}else{
					logger.debug("Record is new");
				}
			}


			//Make sure to map the record before we change id based on eContent record id!
			recordInfo.mapRecord("ExtractEContent");
			long eContentRecordId = -1;
			if (recordInfo.geteContentRecordId() != null){
				eContentRecordId = recordInfo.geteContentRecordId();
			}

			if (ilsId.length() == 0){
				logger.warn("ILS Id could not be found in the marc record, importing.  Running this file multiple times could result in duplicate records in the catalog.");
			}

			boolean recordAdded = false;

			String cover = "";
			if (overDriveId != null){
				//logger.debug("OverDrive ID is " + overDriveId);
				if (overdriveBasicInfo != null){
					cover = overdriveBasicInfo.getCover();
				}else{
					logger.debug("Did not find overdrive information for id " + overDriveId);
				}
			}
			if (!existingRecord){
				//Add to database
				eContentRecordId = addEContentRecordToDb(recordInfo, cover, logger, source, allSources, accessType, ilsId, eContentRecordId);
				recordInfo.seteContentRecordId(eContentRecordId);
				recordAdded = (eContentRecordId != -1);
			}else{
				//Update the record
				recordAdded = updateEContentRecordInDb(recordInfo, cover, logger, source, allSources, accessType, ilsId, eContentRecordId, recordAdded);
			}

			if (recordAdded){
				logger.debug("Record added/updated, adding items");
				addItemsToEContentRecord(recordInfo, logger, source, detectionSettings, eContentRecordId);
			}else{
				logger.info("Record NOT processed successfully.");
			}
			//}
			
			//logger.debug("Finished processing record");
			return true;
		} catch (Exception e) {
			logger.error("Error extracting eContent for record " + recordInfo.getIlsId(), e);
			results.incErrors();
			results.addNote("Error extracting eContent for record " + recordInfo.getIlsId() + " " + e.toString());
			return false;
		}finally{
			if (results.getRecordsProcessed() % 100 == 0){
				results.saveResults();
			}
		}
	}

	private void addItemsToEContentRecord(MarcRecordDetails recordInfo, Logger logger, String source, DetectionSettings detectionSettings, long eContentRecordId) {
		//Non threaded implementation for adding items
		boolean itemsAdded = true;
		if (source.toLowerCase().startsWith("gutenberg")){
			logger.debug("  Adding gutenberg items");
			attachGutenbergItems(recordInfo, eContentRecordId, logger);
		}else if (source.toLowerCase().startsWith("overdrive")){
			logger.debug("  Adding overdrive items");
			itemsAdded = setupOverDriveItems(recordInfo, eContentRecordId, detectionSettings, logger);
		}else if (detectionSettings.isAdd856FieldsAsExternalLinks()){
			//Automatically setup 856 links as external links
			logger.debug("  Adding external items");
			setupExternalLinks(recordInfo, eContentRecordId, detectionSettings, logger);
		}
		if (itemsAdded){
			logger.debug("  Items added successfully, reindexing. " + recordInfo.geteContentRecordId() + " " + recordInfo.getId());
			reindexRecord(recordInfo, eContentRecordId, logger);
		};
	}

	private boolean updateEContentRecordInDb(MarcRecordDetails recordInfo, String cover, Logger logger, String source, String allSources, String accessType, String ilsId, long eContentRecordId,
			boolean recordAdded) throws SQLException, IOException {
		//logger.info("Updating ilsId " + ilsId + " recordId " + eContentRecordId);
		int curField = 1;
		updateEContentRecord.setString(curField++, recordInfo.getId());
		updateEContentRecord.setString(curField++, cover);
		updateEContentRecord.setString(curField++, allSources);
		updateEContentRecord.setString(curField++, Util.trimTo(255, recordInfo.getFirstFieldValueInSet("title_short")));
		updateEContentRecord.setString(curField++, Util.trimTo(255, recordInfo.getFirstFieldValueInSet("title_sub")));
		updateEContentRecord.setString(curField++, Util.trimTo(255, recordInfo.getFirstFieldValueInSet("author")));
		updateEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("author2")));
		updateEContentRecord.setString(curField++, recordInfo.getDescription());
		updateEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("contents")));
		HashMap<String, String> subjects = recordInfo.getBrowseSubjects(false);
		updateEContentRecord.setString(curField++, Util.getCRSeparatedString(subjects.values()));
		updateEContentRecord.setString(curField++, recordInfo.getFirstFieldValueInSet("language"));
		updateEContentRecord.setString(curField++, Util.trimTo(255, recordInfo.getFirstFieldValueInSet("publisher")));
		updateEContentRecord.setString(curField++, Util.trimTo(100, (recordInfo.getPublicationLocation().size() >= 1 ? recordInfo.getPublicationLocation().iterator().next(): "")));
		updateEContentRecord.setString(curField++, Util.trimTo(100, recordInfo.getEContentPhysicalDescription()));
		updateEContentRecord.setString(curField++, recordInfo.getFirstFieldValueInSet("edition"));
		updateEContentRecord.setString(curField++, Util.trimTo(500, Util.getCRSeparatedString(recordInfo.getMappedField("isbn"))));
		updateEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("issn")));
		updateEContentRecord.setString(curField++, recordInfo.getFirstFieldValueInSet("upc"));
		updateEContentRecord.setString(curField++, recordInfo.getFirstFieldValueInSet("lccn"));
		updateEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("topic_facet")));
		updateEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("genre")));
		updateEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("geographic")));
		updateEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("era")));
		updateEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("target_audience")));
		String sourceUrl = "";
		if (recordInfo.getSourceUrls().size() == 1){
			sourceUrl = recordInfo.getSourceUrls().get(0).getUrl();
		}
		updateEContentRecord.setString(curField++, sourceUrl);
		updateEContentRecord.setString(curField++, recordInfo.getPurchaseUrl());
		updateEContentRecord.setString(curField++, recordInfo.getFirstFieldValueInSet("publishDate"));
		updateEContentRecord.setString(curField++, recordInfo.getFirstFieldValueInSet("ctrlnum"));
		updateEContentRecord.setString(curField++, accessType);
		updateEContentRecord.setLong(curField++, new Date().getTime() / 1000);
		updateEContentRecord.setString(curField++, null);
		updateEContentRecord.setString(curField++, recordInfo.getExternalId());
		updateEContentRecord.setInt(curField++, recordInfo.hasItemLevelOwnership());
		updateEContentRecord.setLong(curField++, eContentRecordId);
		int rowsInserted = updateEContentRecord.executeUpdate();
		if (rowsInserted != 1){
			logger.error("Could not update record " + eContentRecordId + " for id " + ilsId + " in the database, number of rows updated was " + rowsInserted);
			results.incErrors();
			results.addNote("Error updating econtent record " + eContentRecordId + " for id " + ilsId + " number of rows updated was " + rowsInserted);
		}else{
			recordAdded = true;
			results.incUpdated();
		}
		return recordAdded;
	}

	private long addEContentRecordToDb(MarcRecordDetails recordInfo, String cover, Logger logger, String source, String allSources, String accessType, String ilsId, long eContentRecordId)
			throws SQLException, IOException {
		logger.info("Adding ils id " + ilsId + " to the database. recordInfo.getIlsId() = " + recordInfo.getIlsId());
		int curField = 1;
		createEContentRecord.setString(curField++, recordInfo.getIlsId());
		createEContentRecord.setString(curField++, cover);
		createEContentRecord.setString(curField++, Util.trimTo(50, allSources));
		createEContentRecord.setString(curField++, Util.trimTo(255, recordInfo.getFirstFieldValueInSet("title_short")));
		createEContentRecord.setString(curField++, Util.trimTo(255, recordInfo.getFirstFieldValueInSet("title_sub")));
		createEContentRecord.setString(curField++, Util.trimTo(255, recordInfo.getFirstFieldValueInSet("author")));
		createEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("author2")));
		createEContentRecord.setString(curField++, recordInfo.getDescription());
		createEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("contents")));
		HashMap<String, String> subjects = recordInfo.getBrowseSubjects(false);
		//logger.debug("Found " + subjects.size() + " subjects");
		createEContentRecord.setString(curField++, Util.getCRSeparatedString(subjects.values()));
		createEContentRecord.setString(curField++, recordInfo.getFirstFieldValueInSet("language"));
		createEContentRecord.setString(curField++, Util.trimTo(255, recordInfo.getFirstFieldValueInSet("publisher")));
		createEContentRecord.setString(curField++, Util.trimTo(100, (recordInfo.getPublicationLocation().size() >= 1 ? recordInfo.getPublicationLocation().iterator().next(): "")));
		createEContentRecord.setString(curField++, Util.trimTo(100, recordInfo.getEContentPhysicalDescription()));
		createEContentRecord.setString(curField++, Util.trimTo(255, recordInfo.getFirstFieldValueInSet("edition")));
		createEContentRecord.setString(curField++, Util.trimTo(500, Util.getCRSeparatedString(recordInfo.getMappedField("isbn"))));
		createEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("issn")));
		createEContentRecord.setString(curField++, recordInfo.getFirstFieldValueInSet("language"));
		createEContentRecord.setString(curField++, recordInfo.getFirstFieldValueInSet("lccn"));
		createEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("topic_facet")));
		createEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("genre")));
		createEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("geographic")));
		createEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("era")));
		createEContentRecord.setString(curField++, Util.getCRSeparatedString(recordInfo.getMappedField("target_audience")));
		String sourceUrl = "";
		if (recordInfo.getSourceUrls().size() == 1){
			sourceUrl = recordInfo.getSourceUrls().get(0).getUrl();
		}
		createEContentRecord.setString(curField++, sourceUrl);
		createEContentRecord.setString(curField++, recordInfo.getPurchaseUrl());
		createEContentRecord.setString(curField++, recordInfo.getFirstFieldValueInSet("publishDate"));
		createEContentRecord.setString(curField++, recordInfo.getFirstFieldValueInSet("ctrlnum"));
		createEContentRecord.setString(curField++, accessType);
		createEContentRecord.setLong(curField++, new Date().getTime() / 1000);
		createEContentRecord.setString(curField++, null);
		createEContentRecord.setString(curField++, recordInfo.getExternalId());
		createEContentRecord.setInt(curField++, recordInfo.hasItemLevelOwnership());
		int rowsInserted = createEContentRecord.executeUpdate();
		if (rowsInserted != 1){
			logger.error("Could not insert row into the database, rowsInserted was " + rowsInserted);
			results.incErrors();
			results.addNote("Error inserting econtent record for id " + ilsId + " number of rows updated was not 1");
		}else{
			ResultSet generatedKeys = createEContentRecord.getGeneratedKeys();
			if (generatedKeys.next()){
				eContentRecordId = generatedKeys.getLong(1);
				logger.debug("Added econtentRecord for ilsId " + recordInfo.getIlsId() + " new ID is " + eContentRecordId);
				results.incAdded();
			}
		}
		return eContentRecordId;
	}

	protected synchronized void setupExternalLinks(MarcRecordDetails recordInfo, long eContentRecordId, DetectionSettings detectionSettings, Logger logger) {
		//Get existing links from the record
		ArrayList<LinkInfo> allLinks = new ArrayList<LinkInfo>();
		try {
			existingEContentRecordLinks.setLong(1, eContentRecordId);
			ResultSet allExistingUrls = existingEContentRecordLinks.executeQuery();
			while (allExistingUrls.next()){
				LinkInfo curLinkInfo = new LinkInfo();
				curLinkInfo.setItemId(allExistingUrls.getLong("id"));
				curLinkInfo.setLink(allExistingUrls.getString("link"));
				curLinkInfo.setLibraryId(allExistingUrls.getLong("libraryId"));
				curLinkInfo.setItemType(allExistingUrls.getString("item_type"));
				allLinks.add(curLinkInfo);
			}
		} catch (SQLException e) {
			results.incErrors();
			results.addNote("Could not load existing links for eContentRecord " + eContentRecordId);
			return;
		}
		//logger.debug("Found " + allLinks.size() + " existing links");
		
		//Add the links that are currently available for the record
		ArrayList<LibrarySpecificLink> sourceUrls;
		try {
			sourceUrls = recordInfo.getSourceUrls();
		} catch (IOException e1) {
			results.incErrors();
			results.addNote("Could not load source URLs for " + recordInfo.getIlsId() + " " + e1.toString());
			return;
		}
		logger.info("Found " + sourceUrls.size() + " urls for " + recordInfo.getIlsId());
		if (sourceUrls.size() == 0){
			results.addNote("Warning, could not find any urls for " + recordInfo.getIlsId() + " source " + detectionSettings.getSource() + " protection type " + detectionSettings.getAccessType());
		}
		for (LibrarySpecificLink curLink : sourceUrls){
			//Look for an existing link
			LinkInfo linkForSourceUrl = null;
			for (LinkInfo tmpLinkInfo : allLinks){
				if (tmpLinkInfo.getLibraryId() == curLink.getLibrarySystemId()){
					linkForSourceUrl = tmpLinkInfo;
				}
			}
			addExternalLink(linkForSourceUrl, curLink, eContentRecordId, detectionSettings, logger);
			if (linkForSourceUrl != null){
				allLinks.remove(linkForSourceUrl);
			}
		}
		
		//Remove any links that no longer exist
		if (allLinks.size() > 0){
			logger.info("There are " + allLinks.size() + " links that need to be deleted");
			for (LinkInfo tmpLinkInfo : allLinks){
				try {
					deleteEContentItem.setLong(1, tmpLinkInfo.getItemId());
					deleteEContentItem.executeUpdate();
				} catch (SQLException e) {
					logger.error("Error deleting eContent item", e);
				}
			}
		}
	}
	
	private void addExternalLink(LinkInfo existingLinkInfo, LibrarySpecificLink linkInfo, long eContentRecordId, DetectionSettings detectionSettings, Logger logger) {
		//Check to see if the link already exists
		try {
			if (existingLinkInfo != null){
				logger.debug("Updating link " + linkInfo.getUrl() + " libraryId = " + linkInfo.getLibrarySystemId());
				String existingUrlValue = existingLinkInfo.getLink();
				Long existingItemId = existingLinkInfo.getItemId();
				String newItemType = getItemTypeByItype(linkInfo.getiType());
				if (existingUrlValue == null || !existingUrlValue.equals(linkInfo.getUrl()) || !newItemType.equals(existingLinkInfo.getItemType())){
					//Url does not match, add it to the record. 
					updateSourceUrl.setString(1, linkInfo.getUrl());
					updateSourceUrl.setLong(2, new Date().getTime());
					updateSourceUrl.setString(3, newItemType);
					updateSourceUrl.setString(4, linkInfo.getNotes());
					updateSourceUrl.setLong(5, existingItemId);
					updateSourceUrl.executeUpdate();
				}
			}else{
				logger.debug("Adding link " + linkInfo.getUrl() + " libraryId = " + linkInfo.getLibrarySystemId());
				//the url does not exist, insert it
				addSourceUrl.setLong(1, eContentRecordId);
				addSourceUrl.setString(2, getItemTypeByItype(linkInfo.getiType()));
				addSourceUrl.setString(3, linkInfo.getNotes());
				addSourceUrl.setString(4, linkInfo.getUrl());
				addSourceUrl.setLong(5, new Date().getTime());
				addSourceUrl.setLong(6, -1);
				addSourceUrl.setLong(7, new Date().getTime());
				addSourceUrl.setLong(8, linkInfo.getLibrarySystemId());
				addSourceUrl.executeUpdate();
			}
		} catch (SQLException e) {
			logger.error("Error adding link to record " + eContentRecordId + " " + linkInfo.getUrl(), e);
			results.addNote("Error adding link to record " + eContentRecordId + " " + linkInfo.getUrl() + " " + e.toString());
			results.incErrors();
		}
		
	}
	
	private String getItemTypeByItype(int iType) {
		if (iType == 188 ){
			return "external_ebook";
		}else if (iType == 110){
			return "external_eaudio";
		}else if (iType == 111){
			return "external_evideo";
		}else if (iType == 216){
			return "external_web";
		}else if (iType == 254){
			return "external_emusic";
		}else{
			return "externalLink";
		}
	}

	Pattern overdriveIdPattern = Pattern.compile("[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}", Pattern.CANON_EQ);
	protected boolean setupOverDriveItems(MarcRecordDetails recordInfo, long eContentRecordId, DetectionSettings detectionSettings, Logger logger){
		//Check the items within the record to see if there are any location specific links
		String overDriveId = recordInfo.getExternalId();
		if (overDriveId != null){
			
			OverDriveBasicInfo overDriveBasicInfo = overDriveTitleInfo.get(overDriveId.toLowerCase());
			if (overDriveBasicInfo == null){
				//results.incErrors();
				results.addNote("Did not find overdrive information for id " + overDriveId + " in information loaded from the API.");
				millenniumRecordsNotInOverDrive.put(overDriveId, recordInfo);
				return false;
			}else{
				//Check to see if we have already processed this id
				if (processedOverDriveRecords.containsKey(overDriveId)){
					logger.debug("This record has already been processed");
					ArrayList<String> duplicateRecords;
					if (duplicateOverDriveRecordsInMillennium.containsKey(overDriveId)){
						duplicateRecords = duplicateOverDriveRecordsInMillennium.get(overDriveId);
					}else{
						duplicateRecords = new ArrayList<String>();
						duplicateRecords.add(processedOverDriveRecords.get(overDriveId));
						duplicateOverDriveRecordsInMillennium.put(overDriveId, duplicateRecords);
					}
					duplicateRecords.add(recordInfo.getIlsId());
					return false;
				}else{
					processedOverDriveRecords.put("overDriveId", recordInfo.getIlsId());
					overDriveTitleInfo.remove(overDriveId.toLowerCase());
					addOverdriveItemsAndAvailability(overDriveBasicInfo, eContentRecordId);
					return true;
				}
			}
		}else{
			//results.incErrors();
			recordsWithoutOverDriveId.add(recordInfo.getIlsId());
			results.addNote("Did not find overdrive id for record " + recordInfo.getId() + " " + eContentRecordId);
			return false;
		}
	}
	
	private void addOverdriveItemsAndAvailability(OverDriveBasicInfo overDriveInfo, long eContentRecordId) {
		try {
			//Add items
			logger.debug("Adding items for " + overDriveInfo.getOverdriveId() + " id " + overDriveInfo.getId());
			loadOverDriveFormatsStmt.setLong(1, overDriveInfo.getId());
			ResultSet overDriveFormats = loadOverDriveFormatsStmt.executeQuery();
			
			int numItemsFound = 0;
			StringBuilder econtentItemIds = new StringBuilder();
			while (overDriveFormats.next()){
				numItemsFound++;
				try {
					doesOverDriveItemExist.setLong(1, eContentRecordId);
					doesOverDriveItemExist.setString(2, overDriveFormats.getString("textId"));
					ResultSet existingOverDriveId = doesOverDriveItemExist.executeQuery();
					if (econtentItemIds.length() != 0){
						econtentItemIds.append("', '");
					}
					if (existingOverDriveId.next()){
						//logger.debug("There is an existing item for this id");
						Long existingItemId = existingOverDriveId.getLong("id");
						econtentItemIds.append(existingItemId);
						//Url does not match, add it to the record. 
						updateOverDriveItem.setString(1, overDriveFormats.getString("name"));
						updateOverDriveItem.setString(2, overDriveFormats.getString("textId"));
						updateOverDriveItem.setLong(3, overDriveFormats.getLong("numericId"));
						updateOverDriveItem.setString(4, "");
						updateOverDriveItem.setString(5, overDriveFormats.getString("sampleSource_1"));
						updateOverDriveItem.setString(6, overDriveFormats.getString("sampleUrl_1"));
						updateOverDriveItem.setString(7, overDriveFormats.getString("sampleSource_2"));
						updateOverDriveItem.setString(8, overDriveFormats.getString("sampleUrl_2"));
						updateOverDriveItem.setLong(9, new Date().getTime());
						updateOverDriveItem.setLong(10, existingItemId);
						updateOverDriveItem.executeUpdate();
						//logger.debug("Updated the existing item " + existingItemId);
					}else{
						//the url does not exist, insert it
						addOverDriveItem.setLong(1, eContentRecordId);
						addOverDriveItem.setString(2, "overdrive");
						addOverDriveItem.setString(3, overDriveFormats.getString("name"));
						addOverDriveItem.setString(4, overDriveFormats.getString("textId"));
						addOverDriveItem.setLong(5, overDriveFormats.getLong("numericId"));
						addOverDriveItem.setString(6, "");
						addOverDriveItem.setString(7, overDriveFormats.getString("sampleSource_1"));
						addOverDriveItem.setString(8, overDriveFormats.getString("sampleUrl_1"));
						addOverDriveItem.setString(9, overDriveFormats.getString("sampleSource_2"));
						addOverDriveItem.setString(10, overDriveFormats.getString("sampleUrl_2"));
						addOverDriveItem.setLong(11, new Date().getTime());
						addOverDriveItem.setLong(12, -1);
						addOverDriveItem.setLong(13, new Date().getTime());
						addOverDriveItem.executeUpdate();
						ResultSet addItemKeys = addOverDriveItem.getGeneratedKeys();
						if (addItemKeys.next()){
							String generatedKey = addItemKeys.getString(1);
							logger.debug("added new item " + generatedKey);
							econtentItemIds.append(generatedKey);
						}else{
							logger.debug("Could not get generated key when adding item");
						}
						//logger.debug("Added new item to record " + eContentRecordId);
					}
				} catch (SQLException e) {
					logger.error("Error adding item to overdrive record " + eContentRecordId + " " + overDriveInfo.getId(), e);
					results.addNote("Error adding item to overdrive record " + eContentRecordId + " " + overDriveInfo.getId() + " " + e.toString());
					results.incErrors();
				}
			}
			if (numItemsFound > 0){
				logger.debug("  Found " + numItemsFound + " items for the title, removing any items except " + econtentItemIds.toString());

				//Delete any items that have been removed
				String deleteOldItemsSql = "DELETE FROM econtent_item WHERE recordId = " + eContentRecordId + " and id NOT IN ('" + econtentItemIds.toString() + "')";
				PreparedStatement deleteOldOverDriveItems = econtentConn.prepareStatement(deleteOldItemsSql);
				int numDeleted = deleteOldOverDriveItems.executeUpdate();
				logger.debug("Deleted " + numDeleted + " items");
			}

			//logger.debug("loaded availability, found " + overDriveInfo.getAvailabilityInfo().size() + " items.");
			loadOverDriveAvailabilityStmt.setLong(1, overDriveInfo.getId());
			ResultSet overDriveAvailability = loadOverDriveAvailabilityStmt.executeQuery();
			while (overDriveAvailability.next()){
				long curLibraryId = overDriveAvailability.getLong("libraryId");
				try {
					doesOverDriveAvailabilityExist.setLong(1, eContentRecordId);
					doesOverDriveAvailabilityExist.setLong(2, curLibraryId);
					ResultSet availabilityRS = doesOverDriveAvailabilityExist.executeQuery();
					if (availabilityRS.next()){
						long availabilityId = availabilityRS.getLong(1);
						updateOverDriveAvailability.setLong(1, overDriveAvailability.getLong("copiesOwned"));
						updateOverDriveAvailability.setLong(2, overDriveAvailability.getLong("copiesAvailable"));
						updateOverDriveAvailability.setLong(3, overDriveAvailability.getLong("numberOfHolds"));
						updateOverDriveAvailability.setLong(4, availabilityId);
						updateOverDriveAvailability.executeUpdate();
					}else{
						addOverDriveAvailability.setLong(1, eContentRecordId);
						addOverDriveAvailability.setLong(2, overDriveAvailability.getLong("copiesOwned"));
						addOverDriveAvailability.setLong(3, overDriveAvailability.getLong("copiesAvailable"));
						addOverDriveAvailability.setLong(4, overDriveAvailability.getLong("numberOfHolds"));
						addOverDriveAvailability.setLong(5, curLibraryId);
						addOverDriveAvailability.executeUpdate();
					}
				} catch (SQLException e) {
					logger.error("Error adding availability to record " + eContentRecordId + " " + overDriveInfo.getId(), e);
					results.addNote("Error adding availability to record " + eContentRecordId + " " + overDriveInfo.getId() + " " + e.toString());
					results.incErrors();
				}
			}
		} catch (SQLException e) {
			logger.error("Error adding items and availability to record " + eContentRecordId + " " + overDriveInfo.getId(), e);
			results.addNote("Error adding items and availability to record " + eContentRecordId + " " + overDriveInfo.getId() + " " + e.toString());
			results.incErrors();
		}
	}

	protected synchronized void attachGutenbergItems(MarcRecordDetails recordInfo, long eContentRecordId, Logger logger) {
		//Add the links that are currently available for the record
		ArrayList<LibrarySpecificLink> sourceUrls;
		try {
			sourceUrls = recordInfo.getSourceUrls();
		} catch (IOException e1) {
			results.incErrors();
			results.addNote("Could not load source URLs for gutenberg record " + recordInfo.getIlsId() + " " + e1.toString());
			return;
		}
		//If no, load the source url
		for (LibrarySpecificLink curLink : sourceUrls){
			String sourceUrl = curLink.getUrl();
			logger.info("Loading gutenberg items " + sourceUrl);
			try {
				//Get the source URL from the export of all items. 
				int numGutenbergItemsFound = 0;
				for (GutenbergItemInfo curItem : gutenbergItemInfo){
					if (curItem.getSourceUrl().equalsIgnoreCase(sourceUrl)){
						numGutenbergItemsFound++;
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
				if (numGutenbergItemsFound == 0){
					logger.warn("No items found for gutenberg title " + recordInfo.getIlsId());
				}
				
				//Attach items based on the source URL
			} catch (Exception e) {
				logger.info("Unable to add items for " + eContentRecordId, e);
			}
		}
	}
	protected void deleteRecord(long eContentRecordId){
		try {
			logger.debug("deleting record " + eContentRecordId);
			updateServer.deleteById("econtentRecord" + eContentRecordId);
		} catch (Exception e) {
			results.addNote("Error deleting for econtentRecord" + eContentRecordId + " " + e.toString());
			results.incErrors();
			e.printStackTrace();
		}
	}

	protected void reindexRecord(MarcRecordDetails recordInfo, final long eContentRecordId, final Logger logger) {
		//Do direct indexing of the record
		try {
			//String xmlDoc = recordInfo.createXmlDoc();
			getEContentRecordStmt.setLong(1, eContentRecordId);
			ResultSet eContentRecordRS = getEContentRecordStmt.executeQuery();
			getItemsForEContentRecordStmt.setLong(1, eContentRecordId);
			ResultSet eContentItemsRS = getItemsForEContentRecordStmt.executeQuery();
			getAvailabilityForEContentRecordStmt.setLong(1, eContentRecordId);
			ResultSet eContentAvailabilityRS = getAvailabilityForEContentRecordStmt.executeQuery();
			
			SolrInputDocument doc = recordInfo.getEContentSolrDocument(eContentRecordId, eContentRecordRS, eContentItemsRS, eContentAvailabilityRS);
			//SolrInputDocument doc = recordInfo.getSolrDocument();
			if (doc != null){
				//Post to the Solr instance
				//logger.debug("Added document to solr");
				updateServer.add(doc);
			}else{
				results.incErrors();
			}
		} catch (Exception e) {
			results.addNote("Error creating xml doc for eContentRecord " + eContentRecordId + " (" + recordInfo.getIlsId() + ") " + e.toString());
			logger.error("Error creating xml doc for eContentRecord " + eContentRecordId + " (" + recordInfo.getIlsId() + ")", e);
			results.incErrors();
			e.printStackTrace();
		}
	}

	protected boolean loadConfig(Ini configIni, Logger logger) {
		String econtentDBConnectionInfo = Util.cleanIniValue(configIni.get("Database", "database_econtent_jdbc"));
		if (econtentDBConnectionInfo == null || econtentDBConnectionInfo.length() == 0) {
			logger.error("Database connection information for eContent database not found in General Settings.  Please specify connection information in a econtentDatabase key.");
			return false;
		}
		
		vufindUrl = configIni.get("Site", "url");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
			return false;
		}
		
		//Get a list of information about Gutenberg items
		String gutenbergItemFile = configIni.get("Reindex", "gutenbergItemFile");
		if (gutenbergItemFile == null || gutenbergItemFile.length() == 0){
			logger.warn("Unable to get Gutenberg Item File in Process settings.  Please add a gutenbergItemFile key.");
		}else{
			HashSet<String> validFormats = new HashSet<String>();
			validFormats.add("epub");
			validFormats.add("pdf");
			validFormats.add("jpg");
			validFormats.add("gif");
			validFormats.add("mp3");
			validFormats.add("plucker");
			validFormats.add("kindle");
			validFormats.add("externalLink");
			validFormats.add("externalMP3");
			validFormats.add("interactiveBook");
			validFormats.add("overdrive");
			
			//Load the items 
			gutenbergItemInfo = new ArrayList<GutenbergItemInfo>();
			try {
				CSVReader gutenbergReader = new CSVReader(new FileReader(gutenbergItemFile));
				//Read headers
				gutenbergReader.readNext();
				String[] curItemInfo = gutenbergReader.readNext();
				while (curItemInfo != null){
					//element 0 is a record id
					String sourceUrl = curItemInfo[1];
					String format = curItemInfo[2];
					String filename = curItemInfo[3];
					String folder = curItemInfo.length >= 5 ? curItemInfo[4] : "";
					String notes = curItemInfo.length >= 6 ? curItemInfo[5] : "";

					GutenbergItemInfo itemInfo = new GutenbergItemInfo(sourceUrl, format, filename, folder, notes);
					
					gutenbergItemInfo.add(itemInfo);
					curItemInfo = gutenbergReader.readNext();
				}
				gutenbergReader.close();
			} catch (Exception e) {
				logger.error("Could not read Gutenberg Item file", e);
			}
			
		}
		
		return true;
		
	}
	
	private void addOverDriveTitlesWithoutMarcToIndex(){
		results.addNote("Adding OverDrive titles without marc records to index");
		int numRecordsAdded = 0;
		for (String overDriveId : overDriveTitleInfo.keySet()){
			OverDriveBasicInfo recordInfo = overDriveTitleInfo.get(overDriveId.toLowerCase());
			numRecordsAdded++;
			if (numOverDriveTitlesToLoadFromAPI > 0 && numRecordsAdded > numOverDriveTitlesToLoadFromAPI){
				break;
			}
			results.incOverDriveNonMarcRecordsProcessed();
			if (!(recordInfo.getLastChange() > ReindexProcess.getLoadChangesSince() || extractEContentFromUnchangedRecords)){
				logger.debug("OverDrive Record " + recordInfo.getId() + " has not changed since last index.");
				continue;
			}
			logger.debug("Adding OverDrive record " + recordInfo.getId() +  " " + recordInfo.getTitle());
			try {
				long econtentRecordId = -1;
				results.incOverDriveNonMarcRecordsProcessed();
				PreparedStatement updateStatement;
				boolean existingRecord;
				if (overDriveTitlesWithoutIlsId.containsKey(overDriveId)){
					logger.debug("Found existing title for overdrive record " + overDriveId);
					EcontentRecordInfo econtentInfo = overDriveTitlesWithoutIlsId.get(overDriveId);
					econtentRecordId = econtentInfo.getRecordId();
					updateStatement = updateEContentRecordForOverDrive;
					overDriveTitlesWithoutIlsId.remove(overDriveId);
					existingRecord = true;
				}else{
					//New title
					logger.debug("Found new overdrive record " + overDriveId);
					updateStatement = createEContentRecordForOverDrive;
					existingRecord = false;
				}
				SolrInputDocument doc = new SolrInputDocument();
				addFieldToDoc(doc, "id_alt", recordInfo.getOverdriveId());
				addFieldToDoc(doc, "collection", "Western Colorado Catalog");
				addFieldToDoc(doc, "id", "econtentRecord" + econtentRecordId);
				addFieldToDoc(doc, "bib_suppression", "notsuppressed");
				addFieldToDoc(doc, "collection_group", "Electronic Access");
				addFieldToDoc(doc, "econtent_source", "OverDrive");
				addFieldToDoc(doc, "econtent_protection_type", "Externally Validated");
				addFieldToDoc(doc, "recordtype", "econtentRecord");
				
				int curCol = 1;
				updateStatement.setString(curCol++, recordInfo.getCover());
				updateStatement.setString(curCol++, "OverDrive");
				updateStatement.setString(curCol++, Util.trimTo(255, recordInfo.getTitle()));
				addFieldToDoc(doc, "title", recordInfo.getTitle());
				addFieldToDoc(doc, "title_full", recordInfo.getTitle());
				addFieldToDoc(doc, "title_sort", Util.makeValueSortable(recordInfo.getTitle()));
				updateStatement.setString(curCol++, Util.trimTo(255, recordInfo.getAuthor()));
				addFieldToDoc(doc, "author", recordInfo.getAuthor());
				//Load contributors
				loadOverDriveContributorsStmt.setLong(1, recordInfo.getId());
				ResultSet loadContributorsRS = loadOverDriveContributorsStmt.executeQuery();
				StringBuilder contributors = new StringBuilder();
				LinkedHashSet<String> contributorsSet = new LinkedHashSet<String>();
				while (loadContributorsRS.next()){
					if (contributors.length() > 0) contributors.append("\r\n");
					contributors.append(loadContributorsRS.getString("fileAs"));
					contributorsSet.add(loadContributorsRS.getString("fileAs"));
				}
				updateStatement.setString(curCol++, contributors.toString());
				addFieldToDoc(doc, "author2", contributorsSet);
				//Load metadata
				loadOverDriveMetadataStmt.setLong(1, recordInfo.getId()); 
				ResultSet metadataRS = loadOverDriveMetadataStmt.executeQuery();
				metadataRS.next();
				updateStatement.setString(curCol++, metadataRS.getString("fullDescription"));
				addFieldToDoc(doc, "description", metadataRS.getString("fullDescription"));
				addFieldToDoc(doc, "series", recordInfo.getSeries());
				//Load subjects
				loadOverDriveSubjectsStmt.setLong(1, recordInfo.getId());
				ResultSet loadSubjectsRS = loadOverDriveSubjectsStmt.executeQuery();
				StringBuilder subjects = new StringBuilder();
				LinkedHashSet<String> subjectsSet = new LinkedHashSet<String>();
				while (loadSubjectsRS.next()){
					if (subjects.length() > 0) subjects.append("\r\n");
					subjects.append(loadSubjectsRS.getString("name"));
					subjectsSet.add(loadSubjectsRS.getString("name"));
				}
				updateStatement.setString(curCol++, subjects.toString());
				addFieldToDoc(doc, "subject_facet", subjectsSet);
				addFieldToDoc(doc, "topic", subjectsSet);
				addFieldToDoc(doc, "topic_facet", subjectsSet);
				//Load Languages
				loadOverDriveLanguagesStmt.setLong(1, recordInfo.getId());
				ResultSet loadLanguagesRS = loadOverDriveLanguagesStmt.executeQuery();
				if (loadLanguagesRS.next()){
					updateStatement.setString(curCol++, loadLanguagesRS.getString("name"));
					addFieldToDoc(doc, "language", loadLanguagesRS.getString("name"));
					String firstLanguage = loadLanguagesRS.getString("name");
					if (firstLanguage.equalsIgnoreCase("English")){
						addFieldToDoc(doc, "language_boost", "300");
						addFieldToDoc(doc, "language_boost_es", "0");
					}else if (firstLanguage.equalsIgnoreCase("Spanish")){
						addFieldToDoc(doc, "language_boost", "0");
						addFieldToDoc(doc, "language_boost_es", "300");
					}else{
						addFieldToDoc(doc, "language_boost", "0");
						addFieldToDoc(doc, "language_boost_es", "0");
					}
				}else{
					updateStatement.setString(curCol++, "");
					addFieldToDoc(doc, "language_boost", "0");
					addFieldToDoc(doc, "language_boost_es", "0");
				}
				updateStatement.setString(curCol++, metadataRS.getString("publisher"));
				addFieldToDoc(doc, "publisher", metadataRS.getString("publisher"));
				updateStatement.setString(curCol++, ""); //Does not look like edition is available in api
				
				//Load ISBNs
				LexileData lexileData = null;
				loadOverDriveISBNStmt.setLong(1, recordInfo.getId());
				ResultSet loadISBNsRS = loadOverDriveISBNStmt.executeQuery();
				StringBuilder isbns = new StringBuilder();
				Set<String> isbnSet = new LinkedHashSet<String>();
				while (loadISBNsRS.next()){
					String isbn = loadISBNsRS.getString("value");
					if (isbns.length() > 0) isbns.append("\r\n");
					isbns.append(isbn);
					isbnSet.add(isbn);
					if (lexileData == null){
						if (isbn.indexOf(" ") > 0) {
							isbn = isbn.substring(0, isbn.indexOf(" "));
						}
						if (isbn.length() == 10){
							isbn = Util.convertISBN10to13(isbn);
						}
						if (isbn.length() == 13){
							lexileData = marcProcessor.getLexileDataForIsbn(isbn);
						}
					}
				}
				addFieldToDoc(doc, "isbn", isbnSet);
				updateStatement.setString(curCol++, isbns.toString());
				if (lexileData != null){
					addFieldToDoc(doc, "lexile_score", lexileData.getLexileScore());
					addFieldToDoc(doc, "lexile_code", lexileData.getLexileCode());
				}
				//Load UPCs
				loadOverDriveUPCStmt.setLong(1, recordInfo.getId());
				ResultSet loadUPCsRS = loadOverDriveUPCStmt.executeQuery();
				StringBuilder upcs = new StringBuilder();
				Set<String> upcSet = new LinkedHashSet<String>();
				while (loadUPCsRS.next()){
					if (upcs.length() > 0) upcs.append("\r\n");
					upcs.append(loadUPCsRS.getString("value"));
					upcSet.add(loadUPCsRS.getString("value"));
				}
				updateStatement.setString(curCol++, upcs.toString());
				addFieldToDoc(doc, "upc", upcSet);
				updateStatement.setString(curCol++, metadataRS.getString("publishDate"));
				addFieldToDoc(doc, "publishDate", metadataRS.getString("publishDate"));
				addFieldToDoc(doc, "publishDateSort", metadataRS.getString("publishDate"));
				updateStatement.setString(curCol++, "external");
				updateStatement.setLong(curCol++, new Date().getTime() / 1000);
				updateStatement.setString(curCol++, recordInfo.getOverdriveId());
				updateStatement.setInt(curCol++, 0);
				updateStatement.setString(curCol++, recordInfo.getSeries());
				if (existingRecord){
					updateStatement.setLong(curCol++, econtentRecordId);
				}
				
				//Save to database
				int rowsInserted = updateStatement.executeUpdate();
				if (rowsInserted != 1){
					logger.error("Could not insert row into the database, rowsInserted was " + rowsInserted);
					results.incErrors();
					results.addNote("Error inserting econtent record for overdrive id " + recordInfo.getId() + " number of rows updated was not 1");
				}else{
					if (!existingRecord){
						ResultSet generatedKeys = createEContentRecordForOverDrive.getGeneratedKeys();
						if (generatedKeys.next()){
							econtentRecordId = generatedKeys.getLong(1);
							results.incAdded();
						}
					}else{
						results.incUpdated();
					}
					
					if (econtentRecordId != -1){
						addOverdriveItemsAndAvailability(recordInfo, econtentRecordId);
						
						//Load availability
						int numHoldings = 0;
						Set<String> availableAt = new LinkedHashSet<String>();
						Set<String> availabilityToggleGlobal = new LinkedHashSet<String>();
						availabilityToggleGlobal.add("Entire Collection");
						loadOverDriveAvailabilityStmt.setLong(1, recordInfo.getId());
						ResultSet overDriveAvailability = loadOverDriveAvailabilityStmt.executeQuery();
						HashSet<Long> availableSystems = new HashSet<Long>();
						LinkedHashSet<String> usableByPTypes = new LinkedHashSet<String>();
						while (overDriveAvailability.next()){
							long curLibraryId = overDriveAvailability.getLong("libraryId");
							numHoldings += overDriveAvailability.getLong("copiesOwned");
							boolean isAvailable = overDriveAvailability.getBoolean("available");
							if (isAvailable){
								availableSystems.add(curLibraryId);
								logger.debug("Available for library " + curLibraryId);
							}
							if (curLibraryId == -1){
								//addFieldToDoc(doc, "institution", "Digital Collection");
								//addFieldToDoc(doc, "building", "Digital Collection");
								//usableByPTypes.addAll(marcProcessor.getAllPTypes());
								for (String libraryFacet : marcProcessor.getLibrarySystemFacets()){
									//addFieldToDoc(doc, "institution", libraryFacet + " Online");
									addFieldToDoc(doc, "building", libraryFacet + " Online");
									if (isAvailable){
										availableAt.add(libraryFacet + " Online");
									}
								}
								if (isAvailable){
									availabilityToggleGlobal.add("Available Now");
									//availableAt.add("Digital Collection");
								}
								
							}else{
								//usableByPTypes.addAll(marcProcessor.getCompatiblePTypes("188", marcProcessor.getLibraryIndexingInfo(curLibraryId).getIlsCode()));
								String libraryName = marcProcessor.getLibrarySystemFacetForId(curLibraryId);
								//addFieldToDoc(doc, "institution", libraryName + " Online");
								addFieldToDoc(doc, "building", libraryName + " Online");
								if (isAvailable){
									availabilityToggleGlobal.add("Available Now");
									availableAt.add(libraryName + " Online");
								}
							}
						}
						addFieldToDoc(doc, "available_at", availableAt);
						addFieldToDoc(doc, "availability_toggle", availabilityToggleGlobal);
						addFieldToDoc(doc, "usable_by",  usableByPTypes);
						
						HashMap<String, LinkedHashSet<String>> availableAtBySystemOrLocation = new HashMap<String, LinkedHashSet<String>>();
						for (Long libraryId : marcProcessor.getLibraryIds()){
							LibraryIndexingInfo libraryIndexingInfo = marcProcessor.getLibraryIndexingInfo(libraryId);
							LinkedHashSet<String> libraryAvailability = new LinkedHashSet<String>();
							libraryAvailability.add("Entire Collection");
							//Check for availability in the shared Collection 
							if (availableSystems.contains(new Long(-1))){
								libraryAvailability.add("Available Now");
							}else if (availableSystems.contains(libraryId)){
								libraryAvailability.add("Available Now");
							}

							availableAtBySystemOrLocation.put(libraryIndexingInfo.getSubdomain(), libraryAvailability);
							//Since we don't have availability by location for online titles, add the same availability to all locations
							for (LocationIndexingInfo curLocationInfo : libraryIndexingInfo.getLocations().values()){
								availableAtBySystemOrLocation.put(curLocationInfo.getCode(), libraryAvailability);
							}
						}
						//Add library specific availability
						for (String code : availableAtBySystemOrLocation.keySet()){
							addFieldToDoc(doc, "availability_toggle_" + code, availableAtBySystemOrLocation.get(code));
						}
						
						//Deal with always available titles by reducing hold count
						if (numHoldings > 1000){
							numHoldings = 5;
						}
						addFieldToDoc(doc, "num_holdings", Integer.toString(numHoldings));
						
						//Load formats
						String firstFormat = null;
						
						Set<String> econtentDevices = new LinkedHashSet<String>();
						Set<String> formats = new LinkedHashSet<String>();
						
						loadOverDriveFormatsStmt.setLong(1, recordInfo.getId());
						ResultSet overDriveFormatsRS = loadOverDriveFormatsStmt.executeQuery();
						while(overDriveFormatsRS.next()){
							String formatName = overDriveFormatsRS.getString("name");
							String formatValue = formatName.replace(" ", "_");
							String translatedFormat = Utils.remap(formatValue, marcProcessor.findMap("format_map"), true);
							if (translatedFormat != null){
								formats.add(translatedFormat);
							}else{
								logger.debug("Did not find format translation for " + formatValue);
							}
							if (firstFormat == null){
								firstFormat = formatValue;
							}
							String devicesForFormat = marcProcessor.findMap("device_compatibility_map").get(formatValue);
							if (devicesForFormat != null){
								String[] devices = devicesForFormat.split("\\|");
								for (String device : devices){
									econtentDevices.add(device);
								}
							}
						}
						addFieldToDoc(doc, "format", formats);
						if (firstFormat != null){
							addFieldToDoc(doc, "format_boost", marcProcessor.findMap("format_boost_map").get(firstFormat));
							addFieldToDoc(doc, "format_category", marcProcessor.findMap("format_category_map").get(firstFormat));
						}
						addFieldToDoc(doc, "econtent_device", econtentDevices);
						
						Float rating = marcProcessor.getEcontentRatings().get(econtentRecordId);
						if (rating == null) {
							rating = -2.5f;
						}
						addFieldToDoc(doc, "rating", Float.toString(rating));
						Set<String> ratingFacets = marcProcessor.getGetRatingFacet(rating);
						addFieldToDoc(doc, "rating_facet", ratingFacets);
						
						Collection<String> allFieldNames = doc.getFieldNames();
						StringBuffer fieldValues = new StringBuffer();
						for (String fieldName : allFieldNames){
							if (fieldValues.length() > 0) fieldValues.append(" ");
							fieldValues.append(doc.getFieldValue(fieldName));
						}
						addFieldToDoc(doc, "allfields", fieldValues.toString());
						addFieldToDoc(doc, "keywords", fieldValues.toString());
						//logger.debug(doc.toString());
						updateServer.add(doc);
					}
				}
			} catch (Exception e) {
				logger.error("Error processing eContent record " + overDriveId , e);
				results.incErrors();
				results.addNote("Error processing eContent record " + overDriveId + " " + e.toString());
			}
		}
	}
	
	private void addFieldToDoc(SolrInputDocument doc, String fieldName, String fieldVal){
		if (fieldVal != null && fieldVal.length() > 0){
			doc.addField(fieldName, fieldVal);
		}
	}
	
	private void addFieldToDoc(SolrInputDocument doc, String fieldName, Set<String> fieldVals){
		if (!fieldVals.isEmpty()) {
			if (fieldVals.size() == 1) {
				String value = fieldVals.iterator().next();
				doc.addField(fieldName, value);
			} else
				doc.addField(fieldName, fieldVals);
		}
	}
	
	@Override
	public void finish() {
		if (overDriveTitleInfo.size() > 0){
			results.addNote(overDriveTitleInfo.size() + " overdrive titles were found using the OverDrive API but did not have an associated MARC record.");
			results.saveResults();
			if (numOverDriveTitlesToLoadFromAPI != 0){
				addOverDriveTitlesWithoutMarcToIndex();
			}
		}else{
			logger.debug("Did not find any OverDrive titles in the API that did not have MARC records");
		}
		
		//Remove any eContent that is no longer active
		if (existingEcontentIlsIds.size() > 0){
			results.addNote("Found " + existingEcontentIlsIds.size() + " eContent titles with ILS Ids that need to be deleted");
			for (String curIlsId : existingEcontentIlsIds.keySet()){
				EcontentRecordInfo econtentInfo = existingEcontentIlsIds.get(curIlsId);
				if (econtentInfo.getStatus().equals("active")){
					deleteEContentRecord(econtentInfo);
				}
			}
		}
		
		//Remove any records that are eContent without ILS ids that are no longer active. 
		if (overDriveTitlesWithoutIlsId.size() > 0){
			results.addNote("Found " + overDriveTitlesWithoutIlsId.size() + " eContent titles without ILS Ids that need to be deleted");
			for (String curExternalId : overDriveTitlesWithoutIlsId.keySet()){
				EcontentRecordInfo econtentInfo = overDriveTitlesWithoutIlsId.get(curExternalId);
				if (econtentInfo.getStatus().equals("active")){
					deleteEContentRecord(econtentInfo);
				}
			}
		}
		
		//Make sure that the index is good and swap indexes
		results.addNote("calling final commit on index");
		
		try {
			results.addNote("calling final commit on index");
			URLPostResponse response = Util.postToURL("http://localhost:" + solrPort + "/solr/biblio2/update/", "<commit />", logger);
			//URLPostResponse response = Util.postToURL("http://localhost:" + solrPort + "/solr/econtent2/update/", "<commit />", logger);
			if (!response.isSuccess()){
				results.incErrors();
				results.addNote("Error committing changes " + response.getMessage());
			}
			/*if (checkMarcImport()){
				results.addNote("index passed checks, swapping cores so new index is active.");
				//URLPostResponse postResponse = Util.getURL("http://localhost:" + solrPort + "/solr/admin/cores?action=SWAP&core=econtent2&other=econtent", logger);
				//if (!postResponse.isSuccess()){
				//	results.addNote("Error swapping cores " + postResponse.getMessage());
				//}else{
				//	results.addNote("Result of swapping cores " + postResponse.getMessage());
				//}
			}else{
				results.incErrors();
				results.addNote("index did not pass check, not swapping");
			}*/
			
		} catch (Exception e) {
			results.addNote("Error finalizing index " + e.toString());
			results.incErrors();
			logger.error("Error finalizing index ", e);
		}
		results.saveResults();
		
		//Write millenniumRecordsNotInOverDrive
		try {
			File millenniumRecordsNotInOverDriveFile = new File(localWebDir + "/millenniumRecordsNotInOverDriveFile.csv");
			CSVWriter writer = new CSVWriter(new FileWriter(millenniumRecordsNotInOverDriveFile));
			writer.writeNext(new String[]{"OverDrive ID", "Millennium Record Id", "Title", "Author"});
			for (String overDriveId : millenniumRecordsNotInOverDrive.keySet()){
				MarcRecordDetails curDetails = millenniumRecordsNotInOverDrive.get(overDriveId);
				writer.writeNext(new String[]{overDriveId, curDetails.getId(), curDetails.getTitle(), curDetails.getAuthor()});
			}
			writer.close();
			results.addNote("Report of records that existing in Millennium, but not OverDrive <a href='" + vufindUrl + "/millenniumRecordsNotInOverDriveFile.csv'>millenniumRecordsNotInOverDriveFile.csv</a>");
		} catch (IOException e) {
			results.addNote("Error saving millenniumRecordsNotInOverDriveFile " + e.toString());
			results.incErrors();
			logger.error("Error saving millenniumRecordsNotInOverDriveFile ", e);
		}
		
		//Write duplicateOverDriveRecordsInMillennium
		try {
			File duplicateOverDriveRecordsInMillenniumFile = new File(localWebDir + "/duplicateOverDriveRecordsInMillennium.csv");
			CSVWriter writer = new CSVWriter(new FileWriter(duplicateOverDriveRecordsInMillenniumFile));
			writer.writeNext(new String[]{"OverDrive ID", "Related Records"});
			for (String overDriveId : duplicateOverDriveRecordsInMillennium.keySet()){
				ArrayList<String> relatedRecords = duplicateOverDriveRecordsInMillennium.get(overDriveId);
				StringBuffer relatedRecordsStr = new StringBuffer();
				for (String curRecord: relatedRecords){
					if (relatedRecordsStr.length() > 0){
						relatedRecordsStr.append(";");
					}
					relatedRecordsStr.append(curRecord);
				}
				writer.writeNext(new String[]{overDriveId, relatedRecordsStr.toString()});
			}
			writer.close();
			results.addNote("Report of OverDrive Ids that are linked to by more than one record in Millennium <a href='" + vufindUrl + "/duplicateOverDriveRecordsInMillennium.csv'>duplicateOverDriveRecordsInMillennium.csv</a>");
		} catch (IOException e) {
			results.addNote("Error saving duplicateOverDriveRecordsInMillenniumFile " + e.toString());
			results.incErrors();
			logger.error("Error saving duplicateOverDriveRecordsInMillenniumFile ", e);
		}
		
		//Write report of overdrive ids we don't have MARC record for
		try {
			File overDriveRecordsWithoutMarcsFile = new File(localWebDir + "/OverDriveRecordsWithoutMarcs.csv");
			CSVWriter writer = new CSVWriter(new FileWriter(overDriveRecordsWithoutMarcsFile));
			writer.writeNext(new String[]{"OverDrive ID", "Title", "Author", "Media Type"});
			for (String overDriveId : overDriveTitleInfo.keySet()){
				OverDriveBasicInfo overDriveTitle = overDriveTitleInfo.get(overDriveId.toLowerCase());
				writer.writeNext(new String[]{overDriveId, overDriveTitle.getTitle(), overDriveTitle.getAuthor(), overDriveTitle.getMediaType()});
			}
			writer.close();
			results.addNote("Report of OverDrive Titles that we do not have MARC records for <a href='" + vufindUrl + "/OverDriveRecordsWithoutMarcs.csv'>OverDriveRecordsWithoutMarcs.csv</a>");
		} catch (IOException e) {
			results.addNote("Error saving overDriveRecordsWithoutMarcsFile " + e.toString());
			results.incErrors();
			logger.error("Error saving overDriveRecordsWithoutMarcsFile ", e);
		}
		
		//Write a report of marc records that are tagged as overdrive records but do not have an overdrive id in the url
		try {
			File marcsWithoutOverDriveIdFile = new File(localWebDir + "/MarcsWithoutOverDriveId.csv");
			CSVWriter writer = new CSVWriter(new FileWriter(marcsWithoutOverDriveIdFile));
			writer.writeNext(new String[]{"Bib Record"});
			for (String bibId : recordsWithoutOverDriveId){
				writer.writeNext(new String[]{bibId});
			}
			writer.close();
			results.addNote("Report of MARC records that do not have an OverDrive ID <a href='" + vufindUrl + "/MarcsWithoutOverDriveId.csv'>MarcsWithoutOverDriveId.csv</a>");
		} catch (IOException e) {
			results.addNote("Error saving marcsWithoutOverDriveIdFile " + e.toString());
			results.incErrors();
			logger.error("Error saving marcsWithoutOverDriveIdFile ", e);
		}
		
		
		results.addNote("Finished eContent extraction");
		results.saveResults();
	}
	
	private boolean checkMarcImport() {
		//Do not pass the import if more than 1% of the records have errors 
		if (results.getNumErrors() > results.getRecordsProcessed() * .01){
			return false;
		}else{
			return true;
		}
	}
	
	@Override
	public ProcessorResults getResults() {
		return results;
	}

	public String getVufindUrl() {
		return vufindUrl;
	}

}
