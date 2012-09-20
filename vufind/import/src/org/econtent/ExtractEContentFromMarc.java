package org.econtent;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Collection;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Set;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLSession;

import org.apache.commons.codec.binary.Base64;
import org.apache.log4j.Logger;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrServer;
import org.apache.solr.common.SolrInputDocument;
import org.econtent.GutenbergItemInfo;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import org.vufind.LexileData;
import org.vufind.MarcRecordDetails;
import org.vufind.IMarcRecordProcessor;
import org.vufind.IRecordProcessor;
import org.vufind.MarcProcessor;
import org.vufind.ProcessorResults;
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
	
	private String localWebDir;
	
	private boolean extractEContentFromUnchangedRecords;
	private boolean checkOverDriveAvailability;
	private String econtentDBConnectionInfo;
	private ArrayList<GutenbergItemInfo> gutenbergItemInfo = null;
	
	private String vufindUrl;
	
	private HashMap<String, EcontentRecordInfo> existingEcontentIlsIds = new HashMap<String, EcontentRecordInfo>();
	private HashMap<String, EcontentRecordInfo> overDriveTitlesWithoutIlsId = new HashMap<String, EcontentRecordInfo>();
	
	private PreparedStatement createEContentRecord;
	private PreparedStatement updateEContentRecord;
	private PreparedStatement createEContentRecordForOverDrive;
	private PreparedStatement updateEContentRecordForOverDrive;
	private PreparedStatement deleteEContentItem;
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
	
	public ProcessorResults results;
	
	//Overdrive API information 
	private String clientSecret;
	private String clientKey;
	private String accountId;
	private String overDriveAPIToken;
	private String overDriveAPITokenType;
	private long overDriveAPIExpiration;
	private String overDriveProductsKey;
	private HashMap<String, OverDriveRecordInfo> overDriveTitles = new HashMap<String, OverDriveRecordInfo>();
	private HashMap<String, Long> advantageCollectionToLibMap = new HashMap<String, Long>();
	private HashMap<Long, String> libToOverDriveAPIKeyMap = new HashMap<Long, String>();
	private HashMap<String, Long> overDriveFormatMap = new HashMap<String, Long>();
	
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
		results = new ProcessorResults("Extract eContent from ILS", reindexLogId, vufindConn, logger);
		solrPort = configIni.get("Reindex", "solrPort");
		
		localWebDir = configIni.get("Site", "local");
		
		//Initialize the updateServer
		try {
			updateServer = new ConcurrentUpdateSolrServer("http://localhost:" + solrPort + "/solr/econtent2", 500, 10);
		} catch (MalformedURLException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		
		//Check to see if we should clear the existing index
		String clearEContentRecordsAtStartOfIndexVal = configIni.get("Reindex", "clearEContentRecordsAtStartOfIndex");
		boolean clearEContentRecordsAtStartOfIndex;
		if (clearEContentRecordsAtStartOfIndexVal == null){
			clearEContentRecordsAtStartOfIndex = false;
		}else{
			clearEContentRecordsAtStartOfIndex = Boolean.parseBoolean(clearEContentRecordsAtStartOfIndexVal);
		}
		results.addNote("clearEContentRecordsAtStartOfIndex = " + clearEContentRecordsAtStartOfIndex);
		if (clearEContentRecordsAtStartOfIndex){
			logger.info("Clearing existing econtent records from index");
			results.addNote("clearing existing econtent records");
			URLPostResponse response = Util.postToURL("http://localhost:" + solrPort + "/solr/econtent2/update/?commit=true", "<delete><query>recordtype:econtentRecord</query></delete>", logger);
			if (!response.isSuccess()){
				results.addNote("Error clearing existing econtent records " + response.getMessage());
			}
		}
		
		String extractEContentFromUnchangedRecordsVal = configIni.get("Reindex", "extractEContentFromUnchangedRecords");
		if (extractEContentFromUnchangedRecordsVal == null){
			logger.debug("Did not get a value for reindexUnchangedRecordsVal");
			extractEContentFromUnchangedRecords = false;
		}else{
			extractEContentFromUnchangedRecords = Boolean.parseBoolean(extractEContentFromUnchangedRecordsVal);
			logger.debug("reindexUnchangedRecords = " + extractEContentFromUnchangedRecords + " " + extractEContentFromUnchangedRecords);
		}
		if (clearEContentRecordsAtStartOfIndex) extractEContentFromUnchangedRecords = true;
		results.addNote("extractEContentFromUnchangedRecords = " + extractEContentFromUnchangedRecords);
		
		String checkOverDriveAvailabilityVal = configIni.get("Reindex", "checkOverDriveAvailability");
		if (checkOverDriveAvailabilityVal == null){
			checkOverDriveAvailability = true;
		}else{
			checkOverDriveAvailability = Boolean.parseBoolean(checkOverDriveAvailabilityVal);
		}
		results.addNote("checkOverDriveAvailability = " + checkOverDriveAvailability);
		
		overDriveProductsKey = configIni.get("OverDrive", "productsKey");
		if (overDriveProductsKey == null){
			logger.warn("Warning no products key provided for OverDrive");
		}
		
		overDriveFormatMap.put("Adobe EPUB eBook", 410L);
		overDriveFormatMap.put("Kindle Book", 420L);
		overDriveFormatMap.put("Microsoft eBook", 1L);
		overDriveFormatMap.put("OverDrive WMA Audiobook", 25L);
		overDriveFormatMap.put("OverDrive MP3 Audiobook", 425L);
		overDriveFormatMap.put("OverDrive Music", 30L);
		overDriveFormatMap.put("OverDrive Video", 35L);
		overDriveFormatMap.put("Adobe PDF eBook", 50L);
		overDriveFormatMap.put("Palm", 150L);
		overDriveFormatMap.put("Mobipocket eBook", 90L);
		overDriveFormatMap.put("Disney Online Book", 302L);
		overDriveFormatMap.put("Open PDF eBook", 450L);
		overDriveFormatMap.put("Open EPUB eBook", 810L);
		
		try {
			//Connect to the vufind database
			createEContentRecord = econtentConn.prepareStatement("INSERT INTO econtent_record (ilsId, cover, source, title, subTitle, author, author2, description, contents, subject, language, publisher, edition, isbn, issn, upc, lccn, topic, genre, region, era, target_audience, sourceUrl, purchaseUrl, publishDate, marcControlField, accessType, date_added, marcRecord, externalId, itemLevelOwnership) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateEContentRecord = econtentConn.prepareStatement("UPDATE econtent_record SET ilsId = ?, cover = ?, source = ?, title = ?, subTitle = ?, author = ?, author2 = ?, description = ?, contents = ?, subject = ?, language = ?, publisher = ?, edition = ?, isbn = ?, issn = ?, upc = ?, lccn = ?, topic = ?, genre = ?, region = ?, era = ?, target_audience = ?, sourceUrl = ?, purchaseUrl = ?, publishDate = ?, marcControlField = ?, accessType = ?, date_updated = ?, marcRecord = ?, externalId = ?, itemLevelOwnership = ? WHERE id = ?");
			
			createEContentRecordForOverDrive = econtentConn.prepareStatement("INSERT INTO econtent_record (ilsId, cover, source, title, author, author2, description, subject, language, publisher, edition, isbn, publishDate, accessType, date_added, externalId, itemLevelOwnership) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateEContentRecordForOverDrive = econtentConn.prepareStatement("UPDATE econtent_record SET ilsId = NULL, cover = ?, source = ?, title = ?, author = ?, author2 = ?, description = ?, subject = ?, language = ?, publisher = ?, edition = ?, isbn = ?, publishDate = ?, accessType = ?, date_updated = ?, externalId = ?, itemLevelOwnership = ? WHERE id = ?");
			
			deleteEContentRecord = econtentConn.prepareStatement("UPDATE econtent_record set status = 'deleted' where id = ?");
			deleteEContentItem = econtentConn.prepareStatement("DELETE FROM econtent_item where id = ?");
			
			doesGutenbergItemExist = econtentConn.prepareStatement("SELECT id from econtent_item WHERE recordId = ? AND item_type = ? and notes = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addGutenbergItem = econtentConn.prepareStatement("INSERT INTO econtent_item (recordId, item_type, filename, folder, link, notes, date_added, addedBy, date_updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
			updateGutenbergItem = econtentConn.prepareStatement("UPDATE econtent_item SET filename = ?, folder = ?, link = ?, date_updated =? WHERE recordId = ? AND item_type = ? AND notes = ?");
			
			existingEContentRecordLinks = econtentConn.prepareStatement("SELECT id, link, libraryId from econtent_item WHERE recordId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addSourceUrl = econtentConn.prepareStatement("INSERT INTO econtent_item (recordId, item_type, notes, link, date_added, addedBy, date_updated, libraryId) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
			updateSourceUrl = econtentConn.prepareStatement("UPDATE econtent_item SET link = ?, date_updated = ?, item_type = ?, notes = ? WHERE id = ?");
			
			doesOverDriveItemExist =  econtentConn.prepareStatement("SELECT id from econtent_item WHERE recordId = ? AND externalFormatId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addOverDriveItem = econtentConn.prepareStatement("INSERT INTO econtent_item (recordId, item_type, externalFormat, externalFormatId, externalFormatNumeric, identifier, sampleName_1, sampleUrl_1, sampleName_2, sampleUrl_2, date_added, addedBy, date_updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			updateOverDriveItem = econtentConn.prepareStatement("UPDATE econtent_item SET externalFormat = ?, externalFormatId = ?, externalFormatNumeric = ?, identifier = ?, sampleName_1 = ?, sampleUrl_1 = ?, sampleName_2 = ?, sampleUrl_2 = ?, date_updated =? WHERE id = ?");
			
			doesOverDriveAvailabilityExist = econtentConn.prepareStatement("SELECT id from econtent_availability where recordId = ? and libraryId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			addOverDriveAvailability = econtentConn.prepareStatement("INSERT INTO econtent_availability (recordId, copiesOwned, availableCopies, numberOfHolds, libraryId) VALUES (?, ?, ?, ?, ?)");
			updateOverDriveAvailability = econtentConn.prepareStatement("UPDATE econtent_availability SET copiesOwned = ?, availableCopies = ?, numberOfHolds = ? WHERE id = ?");
			
			getEContentRecordStmt = econtentConn.prepareStatement("SELECT * FROM econtent_record WHERE id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getItemsForEContentRecordStmt = econtentConn.prepareStatement("SELECT * FROM econtent_item WHERE recordId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getAvailabilityForEContentRecordStmt= econtentConn.prepareStatement("SELECT * FROM econtent_availability WHERE recordId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			
			PreparedStatement existingEcontentIlsIdsStmt = econtentConn.prepareStatement("SELECT econtent_record.id, ilsId, status, count(econtent_item.id) as numItems from econtent_item RIGHT join econtent_record on econtent_record.id = recordId GROUP by ilsId", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet existingEcontentIlsIdsRS = existingEcontentIlsIdsStmt.executeQuery();
			while (existingEcontentIlsIdsRS.next()){
				EcontentRecordInfo recordInfo = new EcontentRecordInfo();
				recordInfo.setRecordId(existingEcontentIlsIdsRS.getLong(1));
				recordInfo.setIlsId(existingEcontentIlsIdsRS.getString(2));
				recordInfo.setStatus(existingEcontentIlsIdsRS.getString(3));
				recordInfo.setNumItems(existingEcontentIlsIdsRS.getInt(4));
				existingEcontentIlsIds.put(recordInfo.getIlsId(), recordInfo);
			}
			
			PreparedStatement overDriveTitlesWithoutIlsIdStmt = econtentConn.prepareStatement("SELECT econtent_record.id, externalId, status, count(econtent_item.id) as numItems from econtent_item RIGHT join econtent_record on econtent_record.id = recordId WHERE externalId is NOT NULL AND ilsId IS NULL GROUP by externalId", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet overDriveTitlesWithoutIlsIdRS = overDriveTitlesWithoutIlsIdStmt.executeQuery();
			while (overDriveTitlesWithoutIlsIdRS.next()){
				EcontentRecordInfo recordInfo = new EcontentRecordInfo();
				recordInfo.setRecordId(overDriveTitlesWithoutIlsIdRS.getLong(1));
				recordInfo.setExternalId(overDriveTitlesWithoutIlsIdRS.getString(2));
				recordInfo.setStatus(overDriveTitlesWithoutIlsIdRS.getString(3));
				recordInfo.setNumItems(overDriveTitlesWithoutIlsIdRS.getInt(4));
				overDriveTitlesWithoutIlsId.put(recordInfo.getIlsId(), recordInfo);
			}
			
			PreparedStatement advantageCollectionMapStmt = vufindConn.prepareStatement("SELECT libraryId, overdriveAdvantageName, overdriveAdvantageProductsKey FROM library where overdriveAdvantageName > ''");
			ResultSet advantageCollectionMapRS = advantageCollectionMapStmt.executeQuery();
			while (advantageCollectionMapRS.next()){
				advantageCollectionToLibMap.put(advantageCollectionMapRS.getString(2), advantageCollectionMapRS.getLong(1));
				libToOverDriveAPIKeyMap.put(advantageCollectionMapRS.getLong(1), advantageCollectionMapRS.getString(3));
			}
		} catch (Exception ex) {
			// handle any errors
			logger.error("Error initializing econtent extraction ", ex);
			return false;
		}finally{
			results.saveResults();
		}
		
		return loadOverDriveTitlesFromAPI(configIni);
		
	}
	
	private boolean loadOverDriveTitlesFromAPI(Ini configIni) {
		clientSecret = configIni.get("OverDrive", "clientSecret");
		clientKey = configIni.get("OverDrive", "clientKey");
		accountId = configIni.get("OverDrive", "accountId");
		results.addNote("Loading OverDrive information from API");
		results.saveResults();
		if (clientSecret != null && clientKey != null && accountId != null){
			//Connect to the API
			connectToOverDriveAPI();
			//Get the library information 
			JSONObject libraryInfo = callOverDriveURL("http://api.overdrive.com/v1/libraries/" + accountId);
			try {
				String libraryName = libraryInfo.getString("name");
				String mainProductUrl = libraryInfo.getJSONObject("links").getJSONObject("products").getString("href");
				loadProductsFromUrl(libraryName, mainProductUrl, false);
				logger.debug("loaded " + overDriveTitles.size() + " overdrive titles in shared collection");
				//Get a list of advantage collections
				if (libraryInfo.getJSONObject("links").has("advantageAccounts")){
					JSONObject advantageInfo = callOverDriveURL(libraryInfo.getJSONObject("links").getJSONObject("advantageAccounts").getString("href"));
					JSONArray advantageAccounts = advantageInfo.getJSONArray("advantageAccounts");
					for (int i = 0; i < advantageAccounts.length(); i++){
						JSONObject curAdvantageAccount = advantageAccounts.getJSONObject(i);
						String advantageSelfUrl = curAdvantageAccount.getJSONObject("links").getJSONObject("self").getString("href");
						JSONObject advantageSelfInfo = callOverDriveURL(advantageSelfUrl);
						String advantageName = curAdvantageAccount.getString("name");
						String productUrl = advantageSelfInfo.getJSONObject("links").getJSONObject("products").getString("href");
						loadProductsFromUrl(advantageName, productUrl, true);
					}
					logger.debug("loaded " + overDriveTitles.size() + " overdrive titles in shared collection and advantage collections");
				}
			} catch (JSONException e) {
				results.addNote("error loading information from OverDrive API " + e.toString());
				results.incErrors();
				logger.error("Error loading overdrive titles", e);
			}
			
			return true;
		}else{
			results.addNote("Not loading OverDrive information from API since the clientSecret, clientKey, and accountId are not set");
			return true;
		}
	}

	private void loadProductsFromUrl(String libraryName, String mainProductUrl, boolean isAdvantage) throws JSONException {
		JSONObject productInfo = callOverDriveURL(mainProductUrl);
		long numProducts = productInfo.getLong("totalItems");
		//if (numProducts > 50) numProducts = 50;
		logger.debug(libraryName + " collection has " + numProducts + " products in it");
		results.addNote("Loading OverDrive information for " + libraryName);
		results.saveResults();
		long batchSize = 300;
		Long libraryId = getLibraryIdForOverDriveAccount(libraryName);
		for (int i = 0; i < numProducts; i += batchSize){
			int tries = 0;
			boolean productsLoaded = false;
			while (tries < 3 && productsLoaded == false){
				logger.debug("Processing " + libraryName + " batch from " + i + " to " + (i + batchSize));
				String batchUrl = mainProductUrl + "?offset=" + i + "&limit=" + batchSize;
				JSONObject productBatchInfo = callOverDriveURL(batchUrl);
				if (productBatchInfo == null){
					tries++;
					continue;
				}else{
					productsLoaded = true;
				}
				JSONArray products = productBatchInfo.getJSONArray("products");
				for(int j = 0; j <products.length(); j++ ){
					JSONObject curProduct = products.getJSONObject(j);
					OverDriveRecordInfo curRecord = loadOverDriveRecordFromJSON(libraryName, curProduct);
					if (libraryId == -1){
						curRecord.setShared(true);
					}
					if (overDriveTitles.containsKey(curRecord.getId())){
						OverDriveRecordInfo oldRecord = overDriveTitles.get(curRecord.getId());
						oldRecord.getCollections().add(libraryId);
					}else{
						//logger.debug("Loading record " + curRecord.getId());
						overDriveTitles.put(curRecord.getId(), curRecord);
					}
				}
			}
		}
	}

	private Long getLibraryIdForOverDriveAccount(String libraryName) {
		if (advantageCollectionToLibMap.containsKey(libraryName)){
			return advantageCollectionToLibMap.get(libraryName);
		}
		return -1L;
	}

	private OverDriveRecordInfo loadOverDriveRecordFromJSON(String libraryName, JSONObject curProduct) throws JSONException {
		OverDriveRecordInfo curRecord = new OverDriveRecordInfo();
		curRecord.setId(curProduct.getString("id"));
		//logger.debug("Processing overdrive title " + curRecord.getId());
		curRecord.setTitle(curProduct.getString("title"));
		curRecord.setMediaType(curProduct.getString("mediaType"));
		if (curProduct.has("series")){
			curRecord.setSeries(curProduct.getString("series"));
		}
		if (curProduct.has("primaryCreator")){
			curRecord.setAuthor(curProduct.getJSONObject("primaryCreator").getString("name"));
		}
		for (int k = 0; k < curProduct.getJSONArray("formats").length(); k++){
			curRecord.getFormats().add(curProduct.getJSONArray("formats").getJSONObject(k).getString("id"));
		}
		if (curProduct.has("images")){
			curRecord.setCoverImage(curProduct.getJSONObject("images").getJSONObject("thumbnail").getString("href"));
		}
		curRecord.getCollections().add(getLibraryIdForOverDriveAccount(libraryName));
		return curRecord;
	}

	private void loadOverDriveAvailability(OverDriveRecordInfo overDriveInfo) {
		logger.debug("Loading availability, " + overDriveInfo.getId() + " is in " + overDriveInfo.getCollections().size() + " collections");
		
		for (Long curCollection : overDriveInfo.getCollections()){
			String apiKey = null;
			if (curCollection == -1L){
				apiKey = overDriveProductsKey;
			}else{
				apiKey = libToOverDriveAPIKeyMap.get(curCollection);
			}
			if (apiKey == null){
				logger.error("Unable to get api key for colleciton " + curCollection);
			}
			String url = "http://api.overdrive.com/v1/collections/" + apiKey + "/products/" + overDriveInfo.getId() + "/availability";
			JSONObject availability = callOverDriveURL(url);
			
			try {
				OverDriveAvailabilityInfo availabilityInfo = new OverDriveAvailabilityInfo();
				availabilityInfo.setLibraryId(curCollection);
				if (availability.has("available")){
					String availableField = availability.getString("available");
					//logger.debug("Available = " + availableField);
					availabilityInfo.setAvailable(availableField.equals("true"));
				}else{
					availabilityInfo.setAvailable(false);
				}
				
				availabilityInfo.setCopiesOwned(availability.getInt("copiesOwned"));
				availabilityInfo.setAvailableCopies(availability.getInt("copiesAvailable"));
				availabilityInfo.setNumHolds(availability.getInt("numberOfHolds"));
				overDriveInfo.getAvailabilityInfo().put(curCollection, availabilityInfo);
			} catch (JSONException e) {
				logger.error("Error loading availability for title ", e);
				results.addNote("Error loading availability for title " + overDriveInfo.getId() + " " + e.toString());
				results.incErrors();
			}
		}
	}
	
	private void loadOverDriveMetaData(OverDriveRecordInfo overDriveInfo) {
		//logger.debug("Loading metadata, " + overDriveInfo.getId() + " is in " + overDriveInfo.getCollections().size() + " collections");
		//Get a list of the collections that own the record 
		long firstCollection = overDriveInfo.getCollections().iterator().next();
		String apiKey = null;
		if (firstCollection == -1L){
			apiKey = overDriveProductsKey;
		}else{
			apiKey = libToOverDriveAPIKeyMap.get(firstCollection);
		}
		if (apiKey == null){
			logger.error("Unable to get api key for colleciton " + firstCollection);
		}
		String url = "http://api.overdrive.com/v1/collections/" + apiKey + "/products/" + overDriveInfo.getId() + "/metadata";
		JSONObject metaData = callOverDriveURL(url);
		if (metaData == null){
			logger.error("Could not load metadata from " + url);
		}
		try {
			overDriveInfo.setEdition(metaData.has("edition") ? metaData.getString("edition") : "");
			overDriveInfo.setPublisher(metaData.has("publisher") ? metaData.getString("publisher") : "");
			overDriveInfo.setPublishDate(metaData.has("publishDate") ? metaData.getString("publishDate") : "");
			if (metaData.has("contributors")){
				JSONArray contributors = metaData.getJSONArray("contributors");
				for (int i = 0; i < contributors.length(); i++){
					JSONObject contributor = contributors.getJSONObject(i);
					overDriveInfo.getContributors().add(contributor.getString("name"));
				}
			}
			if (metaData.has("languages")){
				JSONArray languages = metaData.getJSONArray("languages");
				for (int i = 0; i < languages.length(); i++){
					JSONObject language = languages.getJSONObject(i);
					overDriveInfo.getLanguages().add(language.getString("name"));
				}
			}
			if (metaData.has("isPublicDomain")){
				overDriveInfo.setPublicDomain(metaData.getBoolean("isPublicDomain"));
			}
			if (metaData.has("isPublicPerformanceAllowed")){
				overDriveInfo.setPublicPerformanceAllowed(metaData.getBoolean("isPublicPerformanceAllowed"));
			}
			if (metaData.has("fullDescription")){
				overDriveInfo.setDescription(metaData.getString("fullDescription"));
			}else if (metaData.has("shortDescription")){
				overDriveInfo.setDescription(metaData.getString("shortDescription"));
			}
			if (metaData.has("subjects")){
				JSONArray subjects = metaData.getJSONArray("subjects");
				for (int i = 0; i < subjects.length(); i++){
					JSONObject subject = subjects.getJSONObject(i);
					overDriveInfo.getSubjects().add(subject.getString("value"));
				}
			}
			JSONArray formats = metaData.getJSONArray("formats");
			for (int i = 0; i < formats.length(); i++){
				JSONObject format = formats.getJSONObject(i);
				OverDriveItem curItem = new OverDriveItem();
				curItem.setFormatId(format.getString("id"));
				curItem.setFormat(format.getString("name"));
				curItem.setFormatNumeric(overDriveFormatMap.get(curItem.getFormat()));
				curItem.setFilename(format.getString("fileName"));
				curItem.setPartCount(format.has("partCount") ? format.getLong("partCount") : 0L);
				curItem.setSize(format.has("fileSize") ? format.getLong("fileSize") : 0L);
				if (format.has("identifiers")){
					StringBuffer identifierValue = new StringBuffer();
					JSONArray identifiers = format.getJSONArray("identifiers");
					for (int j = 0; j < identifiers.length(); j++){
						JSONObject identifier = identifiers.getJSONObject(j);
						if (identifierValue.length() > 0) {
							identifierValue.append("\r\n");
						}
						identifierValue.append(identifier.getString("value"));
					}
					curItem.setIdentifier(format.getJSONArray("identifiers").getJSONObject(0).getString("value"));
				}
				if (format.has("samples")){
					JSONArray samples = format.getJSONArray("samples");
					for (int j = 0; j < samples.length(); j++){
						JSONObject sample = samples.getJSONObject(j);
						if (j == 0){
							curItem.setSampleName_1(sample.getString("source"));
							curItem.setSampleUrl_1(sample.getString("url"));
						}else if (j == 1){
							curItem.setSampleName_2(sample.getString("source"));
							curItem.setSampleUrl_2(sample.getString("url"));
						}else{
							logger.warn("Record " + overDriveInfo.getId() + " had more than 2 samples for format " + curItem.getFormat());
						}
					}
				}
				overDriveInfo.getItems().put(curItem.getFormatId(), curItem);
			}
			
		} catch (JSONException e) {
			logger.error("Error loading meta data for title ", e);
			results.addNote("Error loading meta data for title " + overDriveInfo.getId() + " " + e.toString());
			results.incErrors();
		}
	}
	
	private JSONObject callOverDriveURL(String overdriveUrl) {
		if (connectToOverDriveAPI()){
		//Connect to the API to get our token
			HttpURLConnection conn = null;
			try {
				URL emptyIndexURL = new URL(overdriveUrl);
				conn = (HttpURLConnection) emptyIndexURL.openConnection();
				if (conn instanceof HttpsURLConnection){
					HttpsURLConnection sslConn = (HttpsURLConnection)conn;
					sslConn.setHostnameVerifier(new HostnameVerifier() {
						
						@Override
						public boolean verify(String hostname, SSLSession session) {
							//Do not verify host names
							return true;
						}
					});
				}
				conn.setRequestMethod("GET");
				conn.setRequestProperty("Authorization", overDriveAPITokenType + " " + overDriveAPIToken);
				
				StringBuffer response = new StringBuffer();
				if (conn.getResponseCode() == 200) {
					// Get the response
					BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
					String line;
					while ((line = rd.readLine()) != null) {
						response.append(line);
					}
					//logger.debug("  Finished reading response");
					rd.close();
					return new JSONObject(response.toString());
				} else {
					logger.error("Received error " + conn.getResponseCode() + " connecting to overdrive API" );
					// Get any errors
					BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
					String line;
					while ((line = rd.readLine()) != null) {
						response.append(line);
					}
					logger.debug("  Finished reading response");

					rd.close();
					return null;
				}

			} catch (Exception e) {
				logger.error("Error loading data from overdrive API", e );
				return null;
			}
		}else{
			return null;
		}
	}

	private boolean connectToOverDriveAPI(){
		//Check to see if we already have a valid token
		if (overDriveAPIToken != null ){
			if (overDriveAPIExpiration - new Date().getTime() > 0){
				return true;
			}
		}
		//Connect to the API to get our token
		HttpURLConnection conn = null;
		try {
			URL emptyIndexURL = new URL("https://oauth.overdrive.com/token");
			conn = (HttpURLConnection) emptyIndexURL.openConnection();
			if (conn instanceof HttpsURLConnection){
				HttpsURLConnection sslConn = (HttpsURLConnection)conn;
				sslConn.setHostnameVerifier(new HostnameVerifier() {
					
					@Override
					public boolean verify(String hostname, SSLSession session) {
						//Do not verify host names
						return true;
					}
				});
			}
			conn.setRequestMethod("POST");
			conn.setRequestProperty("Content-Type", "application/x-www-form-urlencoded;charset=UTF-8");
			String encoded = Base64.encodeBase64String(new String(clientKey+":"+clientSecret).getBytes());
			conn.setRequestProperty("Authorization", "Basic "+encoded);
			conn.setDoOutput(true);
			OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream(), "UTF8");
			wr.write("grant_type=client_credentials");
			wr.flush();
			wr.close();
			
			StringBuffer response = new StringBuffer();
			if (conn.getResponseCode() == 200) {
				// Get the response
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				rd.close();
				JSONObject parser = new JSONObject(response.toString());
				overDriveAPIToken = parser.getString("access_token");
				overDriveAPITokenType = parser.getString("token_type");
				overDriveAPIExpiration = parser.getLong("expires_in") - 10000;
				//logger.debug("OverDrive token is " + overDriveAPIToken);
			} else {
				logger.error("Received error " + conn.getResponseCode() + " connecting to overdrive API" );
				// Get any errors
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				//logger.debug("  Finished reading response");

				rd.close();
				return false;
			}

		} catch (Exception e) {
			logger.error("Error connecting to overdrive API", e );
			return false;
		}
		return true;
	}

	@Override
	public boolean processMarcRecord(MarcProcessor marcProcessor, MarcRecordDetails recordInfo, int recordStatus, Logger logger) {
		this.marcProcessor = marcProcessor; 
		try {
			results.incRecordsProcessed();
			if (!recordInfo.isEContent()){
				if (existingEcontentIlsIds.containsKey(recordInfo.getId())){
					//Delete the existing record
					EcontentRecordInfo econtentInfo = existingEcontentIlsIds.get(recordInfo.getId());
					if (econtentInfo.getStatus().equals("active")){
						//logger.debug("Record is no longer eContent, removing");
						deleteEContentRecord.setLong(1, econtentInfo.getRecordId());
						deleteEContentRecord.executeUpdate();
						deleteRecord(econtentInfo.getRecordId(), logger);
						results.incDeleted();
					}else{
						results.incSkipped();
					}
					existingEcontentIlsIds.remove(recordInfo.getId());
				}else{
					//logger.debug("Skipping record, it is not eContent");
					results.incSkipped();
				}
				return false;
			}
			
			//logger.debug("Record is eContent, processing");
			//Record is eContent, get additional details about how to process it.
			HashMap<String, DetectionSettings> detectionSettingsBySource = recordInfo.getEContentDetectionSettings();
			if (detectionSettingsBySource == null || detectionSettingsBySource.size() == 0){
				logger.error("Record " + recordInfo.getId() + " was tagged as eContent, but we did not get detection settings for it.");
				results.addNote("Record " + recordInfo.getId() + " was tagged as eContent, but we did not get detection settings for it.");
				results.incErrors();
				return false;
			}
			
			for (String source : detectionSettingsBySource.keySet()){
				//logger.debug("Record " + recordInfo.getId() + " is eContent, source is " + source);
				DetectionSettings detectionSettings = detectionSettingsBySource.get(source);
				//Generally should only have one source, but in theory there could be multiple sources for a single record
				String accessType = detectionSettings.getAccessType();
				//Make sure that overdrive titles are updated if we need to check availability
				if (source.matches("(?i)^overdrive.*") && checkOverDriveAvailability){
					//Overdrive record, force processing to make sure we get updated availability
					//logger.debug("Record is overdrive, forcing reindex to check overdrive availability");
				}else if (recordStatus == MarcProcessor.RECORD_UNCHANGED || recordStatus == MarcProcessor.RECORD_CHANGED_SECONDARY){
					if (extractEContentFromUnchangedRecords){
						//logger.debug("Record is unchanged, but reindex unchanged records is on");
					}else{
						//Check to see if we have items for the record
						if (!existingEcontentIlsIds.containsKey(recordInfo.getId())){
							//logger.debug("Record is unchanged, but the record does not exist in the eContent database.");
						}else{
							EcontentRecordInfo existingRecordInfo = existingEcontentIlsIds.get(recordInfo.getId());
							if (existingRecordInfo.getNumItems() == 0){
								//logger.debug("Record is unchanged, but there are no items so indexing to try to get items.");
							}else if (!existingRecordInfo.getStatus().equalsIgnoreCase("active")){
								//logger.debug("Record is unchanged, is not active indexing to correct the status.");
							}else{
								existingEcontentIlsIds.remove(recordInfo.getId());
								//logger.debug("Skipping because the record is not changed");
								results.incSkipped();
								return false;
							}
						}
					}
				}else{
					/*if (recordStatus == MarcProcessor.RECORD_CHANGED_PRIMARY){
						logger.debug("Record has changed");
					}else{
						logger.debug("Record is new");
					}*/
				}
				
				
				//Check to see if the record already exists
				String ilsId = recordInfo.getId();
				boolean importRecordIntoDatabase = true;
				long eContentRecordId = -1;
				if (ilsId.length() == 0){
					logger.warn("ILS Id could not be found in the marc record, importing.  Running this file multiple times could result in duplicate records in the catalog.");
				}else{
					if (existingEcontentIlsIds.containsKey(ilsId)){
						EcontentRecordInfo eContentRecordInfo = existingEcontentIlsIds.get(ilsId);
						//The record already exists, check if it needs to be updated?
						importRecordIntoDatabase = false;
						eContentRecordId = eContentRecordInfo.getRecordId();
						existingEcontentIlsIds.remove(recordInfo.getId());
					}else{
						//Add to database
						importRecordIntoDatabase = true;
					}
				}
				
				boolean recordAdded = false;
				String overDriveId = recordInfo.getExternalId();
				String cover = "";
				if (overDriveId != null){
					//logger.debug("OverDrive ID is " + overDriveId);
					OverDriveRecordInfo overDriveInfo = overDriveTitles.get(overDriveId);
					if (overDriveInfo != null){
						cover = overDriveInfo.getCoverImage();
						
						//If we do not have an eContentRecordId already, check to see if there is one based on the 
						//overdrive id
						if (eContentRecordId == -1 && overDriveTitlesWithoutIlsId.containsKey(overDriveId)){
							EcontentRecordInfo eContentRecordInfo = overDriveTitlesWithoutIlsId.get(overDriveId);
							importRecordIntoDatabase = false;
							eContentRecordId = eContentRecordInfo.getRecordId();
						}
					}else{
						logger.debug("Did not find overdrive information for id " + overDriveId);
					}
				}
				if (importRecordIntoDatabase){
					//Add to database
					eContentRecordId = addEContentRecordToDb(recordInfo, cover, logger, source, accessType, ilsId, eContentRecordId);
					recordAdded = (eContentRecordId != -1);
				}else{
					//Update the record
					recordAdded = updateEContentRecordInDb(recordInfo, cover, logger, source, accessType, ilsId, eContentRecordId, recordAdded);
				}
				
				logger.info("Finished initial insertion/update recordAdded = " + recordAdded);
				
				if (recordAdded){
					addItemsToEContentRecord(recordInfo, logger, source, detectionSettings, eContentRecordId);
				}else{
					logger.info("Record NOT processed successfully.");
				}
			}
			
			//logger.debug("Finished processing record");
			return true;
		} catch (Exception e) {
			logger.error("Error extracting eContent for record " + recordInfo.getId(), e);
			results.incErrors();
			results.addNote("Error extracting eContent for record " + recordInfo.getId() + " " + e.toString());
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
		if (source.equalsIgnoreCase("gutenberg")){
			attachGutenbergItems(recordInfo, eContentRecordId, logger);
		}else if (detectionSettings.getSource().matches("(?i)^overdrive.*")){
			itemsAdded = setupOverDriveItems(recordInfo, eContentRecordId, detectionSettings, logger);
		}else if (detectionSettings.isAdd856FieldsAsExternalLinks()){
			//Automatically setup 856 links as external links
			setupExternalLinks(recordInfo, eContentRecordId, detectionSettings, logger);
		}
		if (itemsAdded){
			logger.info("Items added successfully.");
			reindexRecord(recordInfo, eContentRecordId, logger);
		};
	}

	private boolean updateEContentRecordInDb(MarcRecordDetails recordInfo, String cover, Logger logger, String source, String accessType, String ilsId, long eContentRecordId,
			boolean recordAdded) throws SQLException, IOException {
		//logger.info("Updating ilsId " + ilsId + " recordId " + eContentRecordId);
		updateEContentRecord.setString(1, recordInfo.getId());
		updateEContentRecord.setString(2, cover);
		updateEContentRecord.setString(3, source);
		updateEContentRecord.setString(4, Util.trimTo(255, recordInfo.getFirstFieldValueInSet("title_short")));
		updateEContentRecord.setString(5, Util.trimTo(255, recordInfo.getFirstFieldValueInSet("title_sub")));
		updateEContentRecord.setString(6, recordInfo.getFirstFieldValueInSet("author"));
		updateEContentRecord.setString(7, Util.getCRSeparatedString(recordInfo.getMappedField("author2")));
		updateEContentRecord.setString(8, recordInfo.getDescription());
		updateEContentRecord.setString(9, Util.getCRSeparatedString(recordInfo.getMappedField("contents")));
		updateEContentRecord.setString(10, Util.getCRSeparatedString(recordInfo.getMappedField("topic_facet")));
		updateEContentRecord.setString(11, recordInfo.getFirstFieldValueInSet("language"));
		updateEContentRecord.setString(12, Util.trimTo(255, recordInfo.getFirstFieldValueInSet("publisher")));
		updateEContentRecord.setString(13, recordInfo.getFirstFieldValueInSet("edition"));
		updateEContentRecord.setString(14, Util.trimTo(500, Util.getCRSeparatedString(recordInfo.getMappedField("isbn"))));
		updateEContentRecord.setString(15, Util.getCRSeparatedString(recordInfo.getMappedField("issn")));
		updateEContentRecord.setString(16, recordInfo.getFirstFieldValueInSet("upc"));
		updateEContentRecord.setString(17, recordInfo.getFirstFieldValueInSet("lccn"));
		updateEContentRecord.setString(18, Util.getCRSeparatedString(recordInfo.getMappedField("topic")));
		updateEContentRecord.setString(19, Util.getCRSeparatedString(recordInfo.getMappedField("genre")));
		updateEContentRecord.setString(20, Util.getCRSeparatedString(recordInfo.getMappedField("geographic")));
		updateEContentRecord.setString(21, Util.getCRSeparatedString(recordInfo.getMappedField("era")));
		updateEContentRecord.setString(22, Util.getCRSeparatedString(recordInfo.getMappedField("target_audience")));
		String sourceUrl = "";
		if (recordInfo.getSourceUrls().size() == 1){
			sourceUrl = recordInfo.getSourceUrls().get(0).getUrl();
		}
		updateEContentRecord.setString(23, sourceUrl);
		updateEContentRecord.setString(24, recordInfo.getPurchaseUrl());
		updateEContentRecord.setString(25, recordInfo.getFirstFieldValueInSet("publishDate"));
		updateEContentRecord.setString(26, recordInfo.getFirstFieldValueInSet("ctrlnum"));
		updateEContentRecord.setString(27, accessType);
		updateEContentRecord.setLong(28, new Date().getTime() / 1000);
		updateEContentRecord.setString(29, recordInfo.toString());
		updateEContentRecord.setString(30, recordInfo.getExternalId());
		updateEContentRecord.setInt(31, recordInfo.hasItemLevelOwnership());
		updateEContentRecord.setLong(32, eContentRecordId);
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

	private long addEContentRecordToDb(MarcRecordDetails recordInfo, String cover, Logger logger, String source, String accessType, String ilsId, long eContentRecordId)
			throws SQLException, IOException {
		//logger.info("Adding ils id " + ilsId + " to the database.");
		createEContentRecord.setString(1, recordInfo.getId());
		createEContentRecord.setString(2, cover);
		createEContentRecord.setString(3, source);
		createEContentRecord.setString(4, Util.trimTo(255, recordInfo.getFirstFieldValueInSet("title_short")));
		createEContentRecord.setString(5, Util.trimTo(255, recordInfo.getFirstFieldValueInSet("title_sub")));
		createEContentRecord.setString(6, recordInfo.getFirstFieldValueInSet("author"));
		createEContentRecord.setString(7, Util.getCRSeparatedString(recordInfo.getMappedField("author2")));
		createEContentRecord.setString(8, recordInfo.getDescription());
		createEContentRecord.setString(9, Util.getCRSeparatedString(recordInfo.getMappedField("contents")));
		createEContentRecord.setString(10, Util.getCRSeparatedString(recordInfo.getMappedField("topic_facet")));
		createEContentRecord.setString(11, recordInfo.getFirstFieldValueInSet("language"));
		createEContentRecord.setString(12, Util.trimTo(255, recordInfo.getFirstFieldValueInSet("publisher")));
		createEContentRecord.setString(13, recordInfo.getFirstFieldValueInSet("edition"));
		createEContentRecord.setString(14, Util.trimTo(500, Util.getCRSeparatedString(recordInfo.getMappedField("isbn"))));
		createEContentRecord.setString(15, Util.getCRSeparatedString(recordInfo.getMappedField("issn")));
		createEContentRecord.setString(16, recordInfo.getFirstFieldValueInSet("language"));
		createEContentRecord.setString(17, recordInfo.getFirstFieldValueInSet("lccn"));
		createEContentRecord.setString(18, Util.getCRSeparatedString(recordInfo.getMappedField("topic")));
		createEContentRecord.setString(19, Util.getCRSeparatedString(recordInfo.getMappedField("genre")));
		createEContentRecord.setString(20, Util.getCRSeparatedString(recordInfo.getMappedField("geographic")));
		createEContentRecord.setString(21, Util.getCRSeparatedString(recordInfo.getMappedField("era")));
		createEContentRecord.setString(22, Util.getCRSeparatedString(recordInfo.getMappedField("target_audience")));
		String sourceUrl = "";
		if (recordInfo.getSourceUrls().size() == 1){
			sourceUrl = recordInfo.getSourceUrls().get(0).getUrl();
		}
		createEContentRecord.setString(23, sourceUrl);
		createEContentRecord.setString(24, recordInfo.getPurchaseUrl());
		createEContentRecord.setString(25, recordInfo.getFirstFieldValueInSet("publishDate"));
		createEContentRecord.setString(26, recordInfo.getFirstFieldValueInSet("ctrlnum"));
		createEContentRecord.setString(27, accessType);
		createEContentRecord.setLong(28, new Date().getTime() / 1000);
		createEContentRecord.setString(29, recordInfo.toString());
		createEContentRecord.setString(30, recordInfo.getExternalId());
		createEContentRecord.setInt(31, recordInfo.hasItemLevelOwnership());
		int rowsInserted = createEContentRecord.executeUpdate();
		if (rowsInserted != 1){
			logger.error("Could not insert row into the database, rowsInserted was " + rowsInserted);
			results.incErrors();
			results.addNote("Error inserting econtent record for id " + ilsId + " number of rows updated was not 1");
		}else{
			ResultSet generatedKeys = createEContentRecord.getGeneratedKeys();
			if (generatedKeys.next()){
				eContentRecordId = generatedKeys.getLong(1);
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
			results.addNote("Could not load source URLs for " + recordInfo.getId() + " " + e1.toString());
			return;
		}
		logger.info("Found " + sourceUrls.size() + " urls for " + recordInfo.getId());
		if (sourceUrls.size() == 0){
			results.addNote("Warning, could not find any urls for " + recordInfo.getId() + " source " + detectionSettings.getSource() + " protection type " + detectionSettings.getAccessType());
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
		logger.info("There are " + allLinks.size() + " links that need to be deleted");
		for (LinkInfo tmpLinkInfo : allLinks){
			try {
				deleteEContentItem.setLong(1, tmpLinkInfo.getItemId());
				deleteEContentItem.executeUpdate();
			} catch (SQLException e) {
				// TODO Auto-generated catch block
				e.printStackTrace();
			}
		}
	}
	
	private void addExternalLink(LinkInfo existingLinkInfo, LibrarySpecificLink linkInfo, long eContentRecordId, DetectionSettings detectionSettings, Logger logger) {
		//Check to see if the link already exists
		try {
			if (existingLinkInfo != null){
				//logger.debug("Updating link " + linkInfo.getUrl() + " libraryId = " + linkInfo.getLibrarySystemId());
				String existingUrlValue = existingLinkInfo.getLink();
				Long existingItemId = existingLinkInfo.getItemId();
				if (existingUrlValue == null || !existingUrlValue.equals(linkInfo.getUrl())){
					//Url does not match, add it to the record. 
					updateSourceUrl.setString(1, linkInfo.getUrl());
					updateSourceUrl.setLong(2, new Date().getTime());
					updateSourceUrl.setString(3, getItemTypeByItype(linkInfo.getiType()));
					updateSourceUrl.setString(4, linkInfo.getNotes());
					updateSourceUrl.setLong(5, existingItemId);
					updateSourceUrl.executeUpdate();
				}
			}else{
				//logger.debug("Adding link " + linkInfo.getUrl() + " libraryId = " + linkInfo.getLibrarySystemId());
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
		ArrayList<LibrarySpecificLink> sourceUrls;
		try {
			sourceUrls = recordInfo.getSourceUrls();
		} catch (IOException e) {
			results.incErrors();
			results.addNote("Could not load source URLs for overdrive record " + recordInfo.getId() + " " + e.toString());
			return false;
		}
		//logger.debug("Found " + sourceUrls.size() + " urls for overdrive id " + recordInfo.getId());
		//Check the items within the record to see if there are any location specific links
		String overDriveId = null;
		for(LibrarySpecificLink link : sourceUrls){
			Matcher RegexMatcher = overdriveIdPattern.matcher(link.getUrl());
			if (RegexMatcher.find()) {
				overDriveId = RegexMatcher.group().toLowerCase();
				break;
			}
		}
		if (overDriveId != null){
			OverDriveRecordInfo overDriveInfo = overDriveTitles.get(overDriveId);
			if (overDriveInfo == null){
				//results.incErrors();
				//results.addNote("Did not find overdrive information for id " + overDriveId + " in information loaded from the API.");
				millenniumRecordsNotInOverDrive.put(overDriveId, recordInfo);
				return false;
			}else{
				//Check to see if we have already processed this id
				if (processedOverDriveRecords.containsKey(overDriveId)){
					ArrayList<String> duplicateRecords;
					if (duplicateOverDriveRecordsInMillennium.containsKey(overDriveId)){
						duplicateRecords = duplicateOverDriveRecordsInMillennium.get(overDriveId);
					}else{
						duplicateRecords = new ArrayList<String>();
						duplicateRecords.add(processedOverDriveRecords.get(overDriveId));
						duplicateOverDriveRecordsInMillennium.put(overDriveId, duplicateRecords);
					}
					duplicateRecords.add(recordInfo.getId());
					return false;
				}else{
					processedOverDriveRecords.put("overDriveId", recordInfo.getId());
					overDriveTitles.remove(overDriveInfo.getId());
					addOverdriveItemsAndAvailability(overDriveInfo, eContentRecordId);
					return true;
				}
			}
		}else{
			//results.incErrors();
			recordsWithoutOverDriveId.add(recordInfo.getId());
			//results.addNote("Did not find overdrive id for record " + recordInfo.getId() + " " + eContentRecordId);
			return false;
		}
	}
	
	private void addOverdriveItemsAndAvailability(OverDriveRecordInfo overDriveInfo, long eContentRecordId) {
		//Add items
		//logger.debug("Adding overdrive items and availability");
		loadOverDriveMetaData(overDriveInfo);
		//logger.debug("loaded meta data, found " + overDriveInfo.getItems().size() + " items.");
		for (OverDriveItem curItem : overDriveInfo.getItems().values()){
			try {
				doesOverDriveItemExist.setLong(1, eContentRecordId);
				doesOverDriveItemExist.setString(2, curItem.getFormatId());
				ResultSet existingOverDriveId = doesOverDriveItemExist.executeQuery();
				if (existingOverDriveId.next()){
					//logger.debug("There is an existing item for this id");
					Long existingItemId = existingOverDriveId.getLong("id");
					//Url does not match, add it to the record. 
					updateOverDriveItem.setString(1, curItem.getFormat());
					updateOverDriveItem.setString(2, curItem.getFormatId());
					updateOverDriveItem.setLong(3, curItem.getFormatNumeric());
					updateOverDriveItem.setString(4, curItem.getIdentifier());
					updateOverDriveItem.setString(5, curItem.getSampleName_1());
					updateOverDriveItem.setString(6, curItem.getSampleUrl_1());
					updateOverDriveItem.setString(7, curItem.getSampleName_2());
					updateOverDriveItem.setString(8, curItem.getSampleUrl_2());
					updateOverDriveItem.setLong(9, new Date().getTime());
					updateOverDriveItem.setLong(10, existingItemId);
					updateOverDriveItem.executeUpdate();
					//logger.debug("Updated the existing item " + existingItemId);
				}else{
					//the url does not exist, insert it
					addOverDriveItem.setLong(1, eContentRecordId);
					addOverDriveItem.setString(2, "overdrive");
					addOverDriveItem.setString(3, curItem.getFormat());
					addOverDriveItem.setString(4, curItem.getFormatId());
					addOverDriveItem.setLong(5, curItem.getFormatNumeric());
					addOverDriveItem.setString(6, curItem.getIdentifier());
					addOverDriveItem.setString(7, curItem.getSampleName_1());
					addOverDriveItem.setString(8, curItem.getSampleUrl_1());
					addOverDriveItem.setString(9, curItem.getSampleName_2());
					addOverDriveItem.setString(10, curItem.getSampleUrl_2());
					addOverDriveItem.setLong(11, new Date().getTime());
					addOverDriveItem.setLong(12, -1);
					addOverDriveItem.setLong(13, new Date().getTime());
					addOverDriveItem.executeUpdate();
					//logger.debug("Added new item to record " + eContentRecordId);
				}
			} catch (SQLException e) {
				logger.error("Error adding item to overdrive record " + eContentRecordId + " " + overDriveInfo.getId(), e);
				results.addNote("Error adding item to overdrive record " + eContentRecordId + " " + overDriveInfo.getId() + " " + e.toString());
				results.incErrors();
			}
		}
		
		//Add availability
		loadOverDriveAvailability(overDriveInfo);
		//logger.debug("loaded availability, found " + overDriveInfo.getAvailabilityInfo().size() + " items.");
		for (Long curLibraryId : overDriveInfo.getAvailabilityInfo().keySet()){
			OverDriveAvailabilityInfo availabiltyInfo = overDriveInfo.getAvailabilityInfo().get(curLibraryId);
			try {
				doesOverDriveAvailabilityExist.setLong(1, eContentRecordId);
				doesOverDriveAvailabilityExist.setLong(2, curLibraryId);
				ResultSet availabilityRS = doesOverDriveAvailabilityExist.executeQuery();
				if (availabilityRS.next()){
					long availabilityId = availabilityRS.getLong(1);
					updateOverDriveAvailability.setLong(1, availabiltyInfo.getCopiesOwned());
					updateOverDriveAvailability.setLong(2, availabiltyInfo.getAvailableCopies());
					updateOverDriveAvailability.setLong(3, availabiltyInfo.getNumHolds());
					updateOverDriveAvailability.setLong(4, availabilityId);
					updateOverDriveAvailability.executeUpdate();
				}else{
					addOverDriveAvailability.setLong(1, eContentRecordId);
					addOverDriveAvailability.setLong(2, availabiltyInfo.getCopiesOwned());
					addOverDriveAvailability.setLong(3, availabiltyInfo.getAvailableCopies());
					addOverDriveAvailability.setLong(4, availabiltyInfo.getNumHolds());
					addOverDriveAvailability.setLong(5, availabiltyInfo.getLibraryId());
					addOverDriveAvailability.executeUpdate();
				}
			} catch (SQLException e) {
				logger.error("Error adding availability to record " + eContentRecordId + " " + overDriveInfo.getId(), e);
				results.addNote("Error adding availability to record " + eContentRecordId + " " + overDriveInfo.getId() + " " + e.toString());
				results.incErrors();
			}
		}
	}

	protected synchronized void attachGutenbergItems(MarcRecordDetails recordInfo, long eContentRecordId, Logger logger) {
		//Add the links that are currently available for the record
		ArrayList<LibrarySpecificLink> sourceUrls;
		try {
			sourceUrls = recordInfo.getSourceUrls();
		} catch (IOException e1) {
			results.incErrors();
			results.addNote("Could not load source URLs for gutenberg record " + recordInfo.getId() + " " + e1.toString());
			return;
		}
		//If no, load the source url
		for (LibrarySpecificLink curLink : sourceUrls){
			String sourceUrl = curLink.getUrl();
			logger.info("Loading gutenberg items " + sourceUrl);
			try {
				//Get the source URL from the export of all items. 
				for (GutenbergItemInfo curItem : gutenbergItemInfo){
					if (curItem.getSourceUrl().equalsIgnoreCase(sourceUrl)){
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
	}
	protected void deleteRecord(long eContentRecordId, Logger logger){
		try {
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
			if (doc != null){
				//Post to the Solr instance
				//logger.debug("Added document to solr");
				updateServer.add(doc);
				//updateServer.add(doc, 60000);
				results.incAdded();
			}else{
				results.incErrors();
			}
		} catch (Exception e) {
			results.addNote("Error creating xml doc for record " + recordInfo.getId() + " " + e.toString());
			e.printStackTrace();
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
	
	private void addOverDriveTitlesWithoutMarcToIndex(){
		results.addNote("Adding OverDrive titles without marc records to index");
		for (String overDriveId : overDriveTitles.keySet()){
			OverDriveRecordInfo recordInfo = overDriveTitles.get(overDriveId);
			//logger.debug("Adding OverDrive record " + recordInfo.getId());
			loadOverDriveMetaData(recordInfo);
			try {
				long econtentRecordId = -1;
				if (overDriveTitlesWithoutIlsId.containsKey(overDriveId)){
					EcontentRecordInfo econtentInfo = overDriveTitlesWithoutIlsId.get(overDriveId);
					econtentRecordId = econtentInfo.getRecordId();
					//We have already added this title before
					updateOverDriveRecordWithoutMarcRecordInDb(recordInfo, econtentRecordId);
				}else{
					//New title
					econtentRecordId = addOverDriveRecordWithoutMarcRecordToDb(recordInfo);
				}
				
				if (econtentRecordId != -1){
					addOverdriveItemsAndAvailability(recordInfo, econtentRecordId);
				}
				//Reindex the record
				SolrInputDocument doc = createSolrDocForOverDriveRecord(recordInfo, econtentRecordId);
				updateServer.add(doc);
				
			} catch (Exception e) {
				logger.error("Error processing eContent record " + overDriveId , e);
				results.incErrors();
				results.addNote("Error processing eContent record " + overDriveId + " " + e.toString());
			}
		}
	}
	
	private SolrInputDocument createSolrDocForOverDriveRecord(OverDriveRecordInfo recordInfo, long econtentRecordId) {
		SolrInputDocument doc = new SolrInputDocument();
		doc.addField("id", "econtentRecord" + econtentRecordId);
		doc.addField("id_sort", "econtentRecord" + econtentRecordId);
		
		doc.addField("collection", "Western Colorado Catalog");
		int numHoldings = 0;
		for (Long systemId : recordInfo.getAvailabilityInfo().keySet()){
			OverDriveAvailabilityInfo curAvailability = recordInfo.getAvailabilityInfo().get(systemId);
			numHoldings += curAvailability.getCopiesOwned();
			if (systemId == -1){
				doc.addField("institution", "Digital Collection");
				doc.addField("building", "Digital Collection");
				for (String libraryFacet : marcProcessor.getAdvantageLibraryFacets()){
					doc.addField("institution", libraryFacet + " Online");
					doc.addField("building", libraryFacet + " Online");
				}
				if (curAvailability.isAvailable()){
					doc.addField("available_at", "Digital Collection");
					for (String libraryFacet : marcProcessor.getAdvantageLibraryFacets()){
						doc.addField("available_at", libraryFacet + " Online");
					}
				}
				
			}else{
				String libraryName = marcProcessor.getLibrarySystemFacetForId(systemId);
				doc.addField("institution", libraryName + " Online");
				doc.addField("building", libraryName + " Online");
				if (curAvailability.isAvailable()){
					doc.addField("available_at", libraryName + " Online");
				}
			}
		}
		doc.addField("collection_group", "Electronic Access");
		if (recordInfo.getLanguages().size() == 0){
			doc.addField("language_boost", "0");
			doc.addField("language_boost_es", "0");
		}else{
			for (String curLanguage : recordInfo.getLanguages()){
				doc.addField("language", curLanguage);
				if (curLanguage.equalsIgnoreCase("English")){
					doc.addField("language_boost", "300");
					doc.addField("language_boost_es", "0");
				}else if (curLanguage.equalsIgnoreCase("Spanish")){
					doc.addField("language_boost", "0");
					doc.addField("language_boost_es", "300");
				}else{
					doc.addField("language_boost", "0");
					doc.addField("language_boost_es", "0");
				}
			}
		}
		
		String firstFormat = null;
		LexileData lexileData = null;
		Set<String> econtentDevices = new HashSet<String>();
		for (OverDriveItem curItem : recordInfo.getItems().values()){
			doc.addField("format", curItem.getFormat());
			if (firstFormat == null){
				firstFormat = curItem.getFormat().replace(" ", "_");
			}
			
			if (curItem.getIdentifier() != null){
				doc.addField("isbn", curItem.getIdentifier());
				if (lexileData == null){
					String isbn = curItem.getIdentifier();
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
			String devicesForFormat = marcProcessor.findMap("device_compatibility_map").get(curItem.getFormat().replace(" ", "_"));
			if (devicesForFormat != null){
				String[] devices = devicesForFormat.split("\\|");
				for (String device : devices){
					econtentDevices.add(device);
				}
			}
		}
		if (firstFormat != null){
			doc.addField("format_boost", marcProcessor.findMap("format_boost_map").get(firstFormat));
			doc.addField("format_category", marcProcessor.findMap("format_category_map").get(firstFormat));
		}
		doc.addField("author", recordInfo.getAuthor());
		for (String curContributor : recordInfo.getContributors()){
			doc.addField("author2", curContributor);
		}
		doc.addField("title", recordInfo.getTitle());
		doc.addField("title_full", recordInfo.getTitle());
		for (String curSubject : recordInfo.getSubjects()){
			doc.addField("subject_facet", curSubject);
			doc.addField("topic", curSubject);
			doc.addField("topic_facet", curSubject);
		}
		doc.addField("publisher", recordInfo.getPublisher());
		doc.addField("publishDate", recordInfo.getPublishDate());
		doc.addField("publishDateSort", recordInfo.getPublishDate());
		doc.addField("edition", recordInfo.getEdition());
		doc.addField("description", recordInfo.getDescription());
		doc.addField("series", recordInfo.getSeries());
		//Deal with always available titles by reducing hold count
		if (numHoldings > 1000){
			numHoldings = 5;
		}
		doc.addField("num_holdings", Integer.toString(numHoldings));
		
		if (lexileData != null){
			doc.addField("lexile_score", lexileData.getLexileScore());
			doc.addField("lexile_code", lexileData.getLexileCode());
		}
		for (String curDevice : econtentDevices){
			doc.addField("econtent_device", curDevice);
		}
		doc.addField("econtent_source", "OverDrive");
		doc.addField("econtent_protection_type", "external");
		doc.addField("recordtype", "econtentRecord");
		Float rating = marcProcessor.getEcontentRatings().get(econtentRecordId);
		if (rating == null) {
			rating = -2.5f;
		}
		doc.addField("rating", Float.toString(rating));
		Set<String> ratingFacets = marcProcessor.getGetRatingFacet(rating);
		for (String ratingFacet : ratingFacets){
			doc.addField("rating_facet", ratingFacet);
		}
		
		Collection<String> allFieldNames = doc.getFieldNames();
		StringBuffer fieldValues = new StringBuffer();
		for (String fieldName : allFieldNames){
			if (fieldValues.length() > 0) fieldValues.append(" ");
			fieldValues.append(doc.getFieldValue(fieldName));
		}
		doc.addField("allfields", fieldValues.toString());
		doc.addField("keywords", fieldValues.toString());
		
		return doc;
	}

	private boolean updateOverDriveRecordWithoutMarcRecordInDb(OverDriveRecordInfo recordInfo, long eContentRecordId) throws SQLException, IOException {
		//logger.info("Updating ilsId " + ilsId + " recordId " + eContentRecordId);
		updateEContentRecordForOverDrive.setString(1, recordInfo.getCoverImage());
		updateEContentRecordForOverDrive.setString(2, "OverDrive");
		updateEContentRecordForOverDrive.setString(3, recordInfo.getTitle());
		updateEContentRecordForOverDrive.setString(4, recordInfo.getAuthor());
		updateEContentRecordForOverDrive.setString(5, Util.getCRSeparatedString(recordInfo.getContributors()));
		updateEContentRecordForOverDrive.setString(6, recordInfo.getDescription());
		updateEContentRecordForOverDrive.setString(7, Util.getCRSeparatedString(recordInfo.getSubjects()));
		updateEContentRecordForOverDrive.setString(8, recordInfo.getLanguages().size() >= 1 ? recordInfo.getLanguages().iterator().next() : "");
		updateEContentRecordForOverDrive.setString(9, Util.trimTo(255, recordInfo.getPublisher()));
		updateEContentRecordForOverDrive.setString(10, recordInfo.getEdition());
		StringBuffer identifiers = new StringBuffer();
		for (OverDriveItem curItem : recordInfo.getItems().values()){
			if (identifiers.length() > 0) identifiers.append("\r\n");
			identifiers.append(curItem.getIdentifier());
		}
		updateEContentRecordForOverDrive.setString(11, identifiers.toString());
		updateEContentRecordForOverDrive.setString(12, recordInfo.getPublishDate());
		updateEContentRecordForOverDrive.setString(13, "external");
		updateEContentRecordForOverDrive.setLong(14, new Date().getTime() / 1000);
		updateEContentRecordForOverDrive.setString(15, recordInfo.getId());
		updateEContentRecordForOverDrive.setInt(16, 0);
		updateEContentRecordForOverDrive.setLong(17, eContentRecordId);
		int rowsInserted = updateEContentRecordForOverDrive.executeUpdate();
		boolean recordAdded = false;
		if (rowsInserted != 1){
			logger.error("Could not update overdrive record " + eContentRecordId + " for id " + recordInfo.getId() + " in the database, number of rows updated was " + rowsInserted);
			results.incErrors();
			results.addNote("Error updating overdrive econtent record " + eContentRecordId + " for id " + recordInfo.getId() + " number of rows updated was " + rowsInserted);
		}else{
			recordAdded = true;
			results.incUpdated();
		}
		return recordAdded;
	}

	private long addOverDriveRecordWithoutMarcRecordToDb(OverDriveRecordInfo recordInfo) throws SQLException, IOException {
		long eContentRecordId= -1;
		//logger.info("Adding ils id " + ilsId + " to the database.");
		createEContentRecordForOverDrive.setString(1, recordInfo.getCoverImage());
		createEContentRecordForOverDrive.setString(2, "OverDrive");
		createEContentRecordForOverDrive.setString(3, Util.trimTo(255, recordInfo.getTitle()));
		createEContentRecordForOverDrive.setString(4, recordInfo.getAuthor());
		createEContentRecordForOverDrive.setString(5, Util.getCRSeparatedString(recordInfo.getContributors()));
		createEContentRecordForOverDrive.setString(6, recordInfo.getDescription());
		createEContentRecordForOverDrive.setString(7, Util.getCRSeparatedString(recordInfo.getSubjects()));
		createEContentRecordForOverDrive.setString(8, recordInfo.getLanguages().size() >= 1 ? recordInfo.getLanguages().iterator().next() : "");
		createEContentRecordForOverDrive.setString(9, Util.trimTo(255, recordInfo.getPublisher()));
		createEContentRecordForOverDrive.setString(10, recordInfo.getEdition());
		StringBuffer identifiers = new StringBuffer();
		for (OverDriveItem curItem : recordInfo.getItems().values()){
			if (identifiers.length() > 0) identifiers.append("\r\n");
			identifiers.append(curItem.getIdentifier());
		}
		createEContentRecordForOverDrive.setString(11, identifiers.toString());
		createEContentRecordForOverDrive.setString(12, recordInfo.getPublishDate());
		createEContentRecordForOverDrive.setString(13, "external");
		createEContentRecordForOverDrive.setLong(14, new Date().getTime() / 1000);
		createEContentRecordForOverDrive.setString(15, recordInfo.getId());
		createEContentRecordForOverDrive.setInt(16, 0);
		int rowsInserted = createEContentRecordForOverDrive.executeUpdate();
		if (rowsInserted != 1){
			logger.error("Could not insert row into the database, rowsInserted was " + rowsInserted);
			results.incErrors();
			results.addNote("Error inserting econtent record for overdrive id " + recordInfo.getId() + " number of rows updated was not 1");
		}else{
			ResultSet generatedKeys = createEContentRecordForOverDrive.getGeneratedKeys();
			if (generatedKeys.next()){
				eContentRecordId = generatedKeys.getLong(1);
				results.incAdded();
			}
		}
		return eContentRecordId;
	}

	@Override
	public void finish() {
		if (overDriveTitles.size() > 0){
			results.addNote(overDriveTitles.size() + " overdrive titles were found using the OverDrive API but did not have an associated MARC record.");
			results.saveResults();
			addOverDriveTitlesWithoutMarcToIndex();
		}
		
		//Make sure that the index is good and swap indexes
		results.addNote("calling final commit on index");
		
		try {
			results.addNote("calling final commit on index");
			URLPostResponse response = Util.postToURL("http://localhost:" + solrPort + "/solr/biblio2/econtent2/", "<commit />", logger);
			if (!response.isSuccess()){
				results.incErrors();
				results.addNote("Error committing changes " + response.getMessage());
			}
			/*results.addNote("optimizing index");
			URLPostResponse optimizeResponse = Util.postToURL("http://localhost:" + solrPort + "/solr/econtent2/update/", "<optimize />", logger);
			if (!optimizeResponse.isSuccess()){
				results.addNote("Error optimizing index " + optimizeResponse.getMessage());
			}*/
			if (checkMarcImport()){
				results.addNote("index passed checks, swapping cores so new index is active.");
				URLPostResponse postResponse = Util.getURL("http://localhost:" + solrPort + "/solr/admin/cores?action=SWAP&core=econtent2&other=econtent", logger);
				if (!postResponse.isSuccess()){
					results.addNote("Error swapping cores " + postResponse.getMessage());
				}else{
					results.addNote("Result of swapping cores " + postResponse.getMessage());
				}
			}else{
				results.incErrors();
				results.addNote("index did not pass check, not swapping");
			}
			
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
			writer.writeNext(new String[]{"OverDrive ID", "Title", "Author", "Media Type", "Publisher"});
			for (String overDriveId : overDriveTitles.keySet()){
				OverDriveRecordInfo overDriveTitle = overDriveTitles.get(overDriveId);
				writer.writeNext(new String[]{overDriveId, overDriveTitle.getTitle(), overDriveTitle.getAuthor(), overDriveTitle.getMediaType(), overDriveTitle.getPublisher()});
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
