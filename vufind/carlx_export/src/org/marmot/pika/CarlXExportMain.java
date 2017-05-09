package org.marmot.pika;

import java.io.*;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.sql.*;
import java.text.DateFormat;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcStreamWriter;
import org.marc4j.MarcWriter;
import org.marc4j.marc.*;
import org.marc4j.marc.impl.MarcFactoryImpl;
import org.marc4j.marc.impl.SubfieldImpl;
import org.w3c.dom.Document;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;
import org.xml.sax.SAXException;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLSession;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;

/**
 * Extracts information from a CARL.X server to determine what information needs to be updated in the index.
 *
 * Created by pbrammeier on 7/25/2016.
 *
 */
public class CarlXExportMain {
	private static Logger logger = Logger.getLogger(CarlXExportMain.class);
	private static String serverName;

	private static String itemTag;
	private static char itemRecordNumberSubfield;
	private static char locationSubfield;
	private static char statusSubfield;
	private static char dueDateSubfield;
	private static char shelvingLocationSubfield;
	private static char iTypeSubfield;
	private static char callNumberSubfield;
	private static char totalCheckoutsSubfield;
	private static char yearToDateCheckoutsSubfield;
	private static String dueDateFormat;
	private static char lastCheckInSubfield;
	private static String lastCheckInFormat;
	private static char dateCreatedSubfield;
	private static String dateCreatedFormat;

	private static String individualMarcPath;
	private static String marcOutURL;

	private static boolean fullReindex = true; // issues warnings for missing translation values
	private static String profileType;
	private static HashMap<String, TranslationMap> translationMaps = new HashMap<>();


	public static void main(String[] args) {
		serverName = args[0];

		// Set-up Logging //
		Date startTime = new Date();
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.carlx_extract.properties");
		if (log4jFile.exists()){
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		}else{
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger.info(startTime.toString() + ": Starting CarlX Extract");

		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		Ini ini = loadConfigFile("config.ini");


		// Get Indexing Profile //

		//Connect to the vufind database
		Connection vufindConn = null;
		try{
			String databaseConnectionInfo = cleanIniValue(ini.get("Database", "database_vufind_jdbc"));
			vufindConn = DriverManager.getConnection(databaseConnectionInfo);
		}catch (Exception e){
			System.out.println("Error connecting to vufind database " + e.toString());
			System.exit(1);
		}

		Long lastCarlXExtractTime           = null;
		Long lastCarlXExtractTimeVariableId = null;
		Long profileIDNumber = null;
		Long exportStartTime = startTime.getTime() / 1000;

		//Get the Indexing Profile from the database
		try {
			PreparedStatement getCarlXIndexingProfileStmt = vufindConn.prepareStatement("SELECT * FROM indexing_profiles where name ='ils'");
			ResultSet carlXIndexingProfileRS = getCarlXIndexingProfileStmt.executeQuery();
			if (carlXIndexingProfileRS.next()) {
				profileIDNumber                    = carlXIndexingProfileRS.getLong("id");

				String itemTag                     = carlXIndexingProfileRS.getString("itemTag");
				String itemRecordNumberSubfield    = carlXIndexingProfileRS.getString("itemRecordNumber");
				String lastCheckinDateSubfield     = carlXIndexingProfileRS.getString("lastCheckinDate");
				String lastCheckinFormat           = carlXIndexingProfileRS.getString("lastCheckinFormat");
				String locationSubfield            = carlXIndexingProfileRS.getString("location");
				String itemStatusSubfield          = carlXIndexingProfileRS.getString("status");
				String dueDateSubfield             = carlXIndexingProfileRS.getString("dueDate");
				String dateCreatedSubfield         = carlXIndexingProfileRS.getString("dateCreated");
				String dateCreatedFormat           = carlXIndexingProfileRS.getString("dateCreatedFormat");
				String callNumberSubfield          = carlXIndexingProfileRS.getString("callNumber");
				String totalCheckoutsSubfield      = carlXIndexingProfileRS.getString("totalCheckouts");
				String yearToDateCheckoutsSubfield = carlXIndexingProfileRS.getString("yearToDateCheckouts");
				String individualMarcPath          = carlXIndexingProfileRS.getString("individualMarcPath");
				String profileType                 = carlXIndexingProfileRS.getString("name");
				String shelvingLocationSubfield    = carlXIndexingProfileRS.getString("shelvingLocation");
//				String collectionSubfield          = carlXIndexingProfileRS.getString("collection"); // Same as shelvingLocation
				String iTypeSubfield               = carlXIndexingProfileRS.getString("iType");

				CarlXExportMain.itemTag                     = itemTag;
				CarlXExportMain.itemRecordNumberSubfield    = itemRecordNumberSubfield.length() > 0 ? itemRecordNumberSubfield.charAt(0) : ' ';
				CarlXExportMain.lastCheckInSubfield         = lastCheckinDateSubfield.length() > 0 ? lastCheckinDateSubfield.charAt(0) : ' ';
				CarlXExportMain.lastCheckInFormat           = lastCheckinFormat;
				CarlXExportMain.locationSubfield            = locationSubfield.length() > 0 ? locationSubfield.charAt(0) : ' ';
				CarlXExportMain.statusSubfield              = itemStatusSubfield.length() > 0 ? itemStatusSubfield.charAt(0) : ' ';
				CarlXExportMain.dueDateSubfield             = dueDateSubfield.length() > 0 ? dueDateSubfield.charAt(0) : ' ';
				CarlXExportMain.dueDateFormat               = lastCheckinFormat;
				CarlXExportMain.dateCreatedSubfield         = dateCreatedSubfield.length() > 0 ? dateCreatedSubfield.charAt(0) : ' ';
				CarlXExportMain.dateCreatedFormat           = dateCreatedFormat;
				CarlXExportMain.callNumberSubfield          = callNumberSubfield.length() > 0 ? callNumberSubfield.charAt(0) : ' ';
				CarlXExportMain.totalCheckoutsSubfield      = totalCheckoutsSubfield.length() > 0 ? totalCheckoutsSubfield.charAt(0) : ' ';
				CarlXExportMain.yearToDateCheckoutsSubfield = yearToDateCheckoutsSubfield.length() > 0 ? yearToDateCheckoutsSubfield.charAt(0) : ' ';
				CarlXExportMain.shelvingLocationSubfield    = shelvingLocationSubfield.length() > 0 ? shelvingLocationSubfield.charAt(0) : ' ';
				CarlXExportMain.iTypeSubfield               = iTypeSubfield.length() > 0 ? iTypeSubfield.charAt(0) : ' ';
				CarlXExportMain.individualMarcPath          = individualMarcPath;
				CarlXExportMain.profileType                 = profileType;


				String carlXExportPath          = carlXIndexingProfileRS.getString("marcPath");
				String recordNumberTag          = carlXIndexingProfileRS.getString("recordNumberTag");
				String recordNumberPrefix       = carlXIndexingProfileRS.getString("recordNumberPrefix");
				String itemBarcodeSubfield      = carlXIndexingProfileRS.getString("barcode");
				// shelvingLocation & collection sub fields are the same in the sandbox

			} else {
				logger.error("Unable to find carlx indexing profile, please create a profile with the name ils.");
			}

		}catch (Exception e){
			logger.error("Error reading index profile for CarlX", e);
		}


		// Set update time
		long updateTime = new Date().getTime() / 1000;

		// Get Last Extract Time
		String beginTimeString = null;
		try {
			PreparedStatement loadLastCarlXExtractTimeStmt = vufindConn.prepareStatement("SELECT * from variables WHERE name = 'last_carlx_extract_time'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet lastCarlXExtractTimeRS = loadLastCarlXExtractTimeStmt.executeQuery();
			if (lastCarlXExtractTimeRS.next()){
				lastCarlXExtractTime           = lastCarlXExtractTimeRS.getLong("value");
				lastCarlXExtractTimeVariableId = lastCarlXExtractTimeRS.getLong("id");
			}

			DateFormat beginTimeFormat = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'");
			beginTimeFormat.setTimeZone(TimeZone.getTimeZone("UTC"));

			//Last Update in UTC
			Date now             = new Date();
			Date yesterday       = new Date(now.getTime() - 24 * 60 * 60 * 1000);
			Date lastExtractDate = (lastCarlXExtractTime != null) ? new Date((lastCarlXExtractTime - 120) * 1000) : yesterday;
			// Add a small buffer to the last extract time

			if (lastExtractDate.before(yesterday)){
				logger.warn("Last Extract date was more than 24 hours ago.  Just getting the last 24 hours since we should have a full extract.");
				lastExtractDate = yesterday;
			}


			beginTimeString = beginTimeFormat.format(lastExtractDate);

		} catch (Exception e) {
			logger.error("Error getting last Extract Time for CarlX", e);
		}

		// Get MarcOut WSDL url for SOAP calls
		marcOutURL = ini.get("Catalog", "marcOutApiWsdl");

		// Get All Changed Marc Records //
		String changedMarcSoapRequest = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:mar=\"http://tlcdelivers.com/cx/schemas/marcoutAPI\" xmlns:req=\"http://tlcdelivers.com/cx/schemas/request\">\n" +
				"<soapenv:Header/>\n" +
				"<soapenv:Body>\n" +
				"<mar:GetChangedBibsRequest>\n" +
				"<mar:BeginTime>"+ beginTimeString + "</mar:BeginTime>\n" +
				"<mar:Modifiers/>\n" +
				"</mar:GetChangedBibsRequest>\n" +
				"</soapenv:Body>\n" +
				"</soapenv:Envelope>";

		URLPostResponse SOAPResponse = postToURL(marcOutURL, changedMarcSoapRequest, "text/xml", null, logger);

		String[] updatedBibs = new String[0];
		String[] createdBibs = new String[0];
		String[] deletedBibs = new String[0];

		String totalBibs = "0";

		// Read SOAP Response for Changed Bibs
		try {
			Document doc                     = createXMLDocumentForSoapResponse(SOAPResponse);
			Node soapEnvelopeNode            = doc.getFirstChild();
			Node soapBodyNode                = soapEnvelopeNode.getLastChild();
			Node getChangedBibsResponseNode  = soapBodyNode.getFirstChild();
			Node responseStatusNode          = getChangedBibsResponseNode.getChildNodes().item(0).getChildNodes().item(0);
			String responseStatusCode        = responseStatusNode.getFirstChild().getTextContent();
			if (responseStatusCode.equals("0")) {
				totalBibs                      = responseStatusNode.getChildNodes().item(3).getTextContent();
				logger.debug("There are " + totalBibs + " total bibs");
				Node updatedBibsNode           = getChangedBibsResponseNode.getChildNodes().item(4); // 5th element of getChangedItemsResponseNode
				Node createdBibsNode           = getChangedBibsResponseNode.getChildNodes().item(3); // 4th element of getChangedItemsResponseNode
				Node deletedBibsNode           = getChangedBibsResponseNode.getChildNodes().item(5); // 6th element of getChangedItemsResponseNode

				// Updated Items
				updatedBibs = getIDsStringArrayFromNodeList(updatedBibsNode.getChildNodes());
				logger.debug("Found " + updatedBibs.length + " updated bibs since " + beginTimeString);

				// TODO: Process Created Bibs in the future
				// Created Bibs
				createdBibs = getIDsStringArrayFromNodeList(createdBibsNode.getChildNodes());
				logger.debug("Found " + createdBibs.length + " new bibs since " + beginTimeString);

				// TODO: Process Deleted Bibs in the future
				// Deleted Bibs
				deletedBibs = getIDsStringArrayFromNodeList(deletedBibsNode.getChildNodes());
				logger.debug("Found " + deletedBibs.length + " deleted bibs since " + beginTimeString);

			} else {
				String shortErrorMessage = responseStatusNode.getChildNodes().item(2).getTextContent();
				logger.error("Error Response for API call for Changed Bibs : " + shortErrorMessage);
				// TODO: stop execution?
//				System.out.println("Error Response for API call for Changed Bibs : " + shortErrorMessage);
//				System.exit(1);

			}



		} catch (Exception e) {
			logger.error("Error Parsing SOAP Response for Fetching Changed Bibs", e);
		}


		// Get All Changed Items //
		String changedItemsSoapRequest = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:mar=\"http://tlcdelivers.com/cx/schemas/marcoutAPI\" xmlns:req=\"http://tlcdelivers.com/cx/schemas/request\">\n" +
				"<soapenv:Header/>\n" +
				"<soapenv:Body>\n" +
				"<mar:GetChangedItemsRequest>\n" +
				"<mar:BeginTime>"+ beginTimeString + "</mar:BeginTime>\n" +
				"<mar:Modifiers/>\n" +
				"</mar:GetChangedItemsRequest>\n" +
				"</soapenv:Body>\n" +
				"</soapenv:Envelope>";

//		URLPostResponse
		SOAPResponse = postToURL(marcOutURL, changedItemsSoapRequest, "text/xml", null, logger);

		ArrayList<String> updatedItemIDs = new ArrayList<>();
		ArrayList<String> createdItemIDs = new ArrayList<>();
		ArrayList<String> deletedItemIDs = new ArrayList<>();

		String totalItems;

		// Read SOAP Response for Changed Items
		try {
			Document doc                     = createXMLDocumentForSoapResponse(SOAPResponse);
			Node soapEnvelopeNode            = doc.getFirstChild();
			Node soapBodyNode                = soapEnvelopeNode.getLastChild();
			Node getChangedItemsResponseNode = soapBodyNode.getFirstChild();
			Node responseStatusNode          = getChangedItemsResponseNode.getChildNodes().item(0).getChildNodes().item(0);
			String responseStatusCode        = responseStatusNode.getFirstChild().getTextContent();
			if (responseStatusCode.equals("0")) {
				totalItems = responseStatusNode.getChildNodes().item(3).getTextContent();
				logger.debug("There are " + totalItems + " total items");

				Node updatedItemsNode            = getChangedItemsResponseNode.getChildNodes().item(4); // 5th element of getChangedItemsResponseNode
				Node createdItemsNode            = getChangedItemsResponseNode.getChildNodes().item(3); // 4th element of getChangedItemsResponseNode
				Node deletedItemsNode            = getChangedItemsResponseNode.getChildNodes().item(5); // 6th element of getChangedItemsResponseNode

				// Updated Items
				updatedItemIDs = getIDsArrayListFromNodeList(updatedItemsNode.getChildNodes());
				logger.debug("Found " + updatedItemIDs.size() + " updated items since " + beginTimeString);

				// Created Items
				createdItemIDs = getIDsArrayListFromNodeList(createdItemsNode.getChildNodes());
				logger.debug("Found " + createdItemIDs.size() + " new items since " + beginTimeString);

				// Deleted Items
				deletedItemIDs = getIDsArrayListFromNodeList(deletedItemsNode.getChildNodes());
				logger.debug("Found " + deletedItemIDs.size() + " deleted items since " + beginTimeString);
			} else {
				String shortErrorMessage = responseStatusNode.getChildNodes().item(2).getTextContent();
				logger.error("Error Response for API call for Changed Items : " + shortErrorMessage);
				System.out.println("Error Response for API call for Changed Items : " + shortErrorMessage);
				System.exit(1);
			}

		} catch (Exception e) {
			logger.error("Error Parsing SOAP Response for Fetching Changed Items", e);
		}

		// Load Translation Map for Item Status Codes
		try {
			loadTranslationMapsForProfile(vufindConn, profileIDNumber);
		} catch (SQLException e) {
			logger.error("Failed to Load Translation Maps for CarlX Extract", e);
		}

		// Fetch Item Information
		ArrayList<ItemChangeInfo> itemUpdates  = fetchItemInformation(updatedItemIDs);
		ArrayList<ItemChangeInfo> createdItems = fetchItemInformation(createdItemIDs);

		boolean errorUpdatingDatabase = false;
		int numUpdates = 0;
		PreparedStatement markGroupedWorkForBibAsChangedStmt = null;
		try {
			vufindConn.setAutoCommit(false); // turn off for updating grouped worked for re-indexing
			markGroupedWorkForBibAsChangedStmt = vufindConn.prepareStatement("UPDATE grouped_work SET date_updated = ? where id = (SELECT grouped_work_id from grouped_work_primary_identifiers WHERE type = 'ils' and identifier = ?)");
		} catch (SQLException e) {
			logger.error("Failed to prepare statement to mark records for Re-indexing", e);
		}


		// Update Changed Bibs //

		// Fetch new Marc Data
		// Note: There is an Include949ItemData flag, but it hasn't been implemented by TLC yet. plb 9-15-2016
		String getMarcRecordsSoapRequest;
		// Build Marc Fetching Soap Request
			if (updatedBibs.length > 0) {
				try {
				String getMarcRecordsSoapRequestStart = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:mar=\"http://tlcdelivers.com/cx/schemas/marcoutAPI\" xmlns:req=\"http://tlcdelivers.com/cx/schemas/request\">\n" +
						"<soapenv:Header/>\n" +
						"<soapenv:Body>\n" +
						"<mar:GetMARCRecordsRequest>\n";
				String getMarcRecordsSoapRequestEnd = "<mar:Include949ItemData>0</mar:Include949ItemData>\n" +
						"<mar:IncludeOnlyUnsuppressed>0</mar:IncludeOnlyUnsuppressed>\n" +
						"<mar:Modifiers>\n" +
						"</mar:Modifiers>\n" +
						"</mar:GetMARCRecordsRequest>\n" +
						"</soapenv:Body>\n" +
						"</soapenv:Envelope>";

				getMarcRecordsSoapRequest = getMarcRecordsSoapRequestStart;
				// Updated Bibs
				for (String updatedBibID : updatedBibs) {
					if (updatedBibID.length() > 0) {
						getMarcRecordsSoapRequest += "<mar:BID>" + updatedBibID + "</mar:BID>";
					}
				}
				getMarcRecordsSoapRequest += getMarcRecordsSoapRequestEnd;

				URLPostResponse marcRecordSOAPResponse = postToURL(marcOutURL, getMarcRecordsSoapRequest, "text/xml", null, logger);

				// Parse Response
				Document doc                    = createXMLDocumentForSoapResponse(marcRecordSOAPResponse);
				Node soapEnvelopeNode           = doc.getFirstChild();
				Node soapBodyNode               = soapEnvelopeNode.getLastChild();
				Node getMarcRecordsResponseNode = soapBodyNode.getFirstChild();
				NodeList marcRecordInfo         = getMarcRecordsResponseNode.getChildNodes();
				Node marcRecordsResponseStatus  = getMarcRecordsResponseNode.getFirstChild().getFirstChild();
				String responseStatusCode       = marcRecordsResponseStatus.getFirstChild().getTextContent();

				if (responseStatusCode.equals("0") ) { // Successful response

					int l = marcRecordInfo.getLength();
					for (int i=1; i < l; i++ ) { // (skip first node because it is the response status)
						String currentBibID = updatedBibs[i-1];
						Node marcRecordNode = marcRecordInfo.item(i);

						// Build Marc Object from the API data
						Record updatedMarcRecordFromAPICall = buildMarcRecordFromAPIResponse(marcRecordNode, currentBibID);

						Record currentMarcRecord            = loadMarc(currentBibID);
						if (currentMarcRecord != null) {
							Integer indexOfItem;
							List<VariableField> currentMarcDataFields = currentMarcRecord.getVariableFields(itemTag);
							for (VariableField itemFieldVar: currentMarcDataFields) {
								DataField currentDataField = (DataField) itemFieldVar;
								String currentItemID = currentDataField.getSubfield(itemRecordNumberSubfield).getData();
								if (updatedItemIDs.contains(currentItemID)) {
									// Add current Item Change Info instead
									indexOfItem = updatedItemIDs.indexOf(currentItemID);
									ItemChangeInfo updatedItem = itemUpdates.get(indexOfItem);
									if (updatedItem.getBID().equals(currentBibID)) { // Double check BID in case itemIDs aren't completely unique
										updateItemDataFieldWithChangeInfo(currentDataField, updatedItem);
										itemUpdates.remove(updatedItem); // remove Item Change Info
										updatedItemIDs.remove(currentItemID); // remove itemId for list
									}
								} else if (deletedItemIDs.contains(currentItemID)) {
									deletedItemIDs.remove(currentItemID); //TODO: check the API for the same BIB ID?
									continue; // Skip adding this item into the Marc Object
								} else if (createdItemIDs.contains(currentItemID)) {
									// This shouldn't happen, but in case it does
									indexOfItem = createdItemIDs.indexOf(currentItemID);
									ItemChangeInfo createdItem = createdItems.get(indexOfItem);
									if (createdItem.getBID().equals(currentBibID)) { // Double check BID in case itemIDs aren't completely unique
										updateItemDataFieldWithChangeInfo(currentDataField, createdItem);
										createdItems.remove(createdItem); // remove Item Change Info
										createdItemIDs.remove(currentItemID); // remove itemId for list
									}
								}
								updatedMarcRecordFromAPICall.addVariableField(currentDataField);

							}
						} else {
							// We lose any existing, unchanged items.
							// TODO: Do an additional look up for Item Information ?
							logger.warn("Existing Marc Record for BID " + currentBibID + " failed to load; Writing new record with data from API");
						}

						// Save Marc Record to File
						saveMarc(updatedMarcRecordFromAPICall, currentBibID);

						// Mark Bib as Changed for Re-indexer
						try {
							markGroupedWorkForBibAsChangedStmt.setLong(1, updateTime);
							markGroupedWorkForBibAsChangedStmt.setString(2, currentBibID);
							markGroupedWorkForBibAsChangedStmt.executeUpdate();

							numUpdates++;
							if (numUpdates % 50 == 0){
								vufindConn.commit();
							}
						}catch (SQLException e){
							logger.error("Could not mark that " + currentBibID + " was changed due to error ", e);
							errorUpdatingDatabase = true;
						}
					}

				} else {
					String shortErrorMessage = marcRecordsResponseStatus.getChildNodes().item(2).getTextContent();
					logger.error("Error Response for API call for getting Marc Records : " + shortErrorMessage);
				}
			} catch (Exception e) {
				logger.error("Error Creating SOAP Request for Marc Records", e);
			}
		}


		// Now update left over item updates & new items
		if (itemUpdates.size() > 0 || createdItems.size() > 0) {
			// Item Updates
			for (ItemChangeInfo item : itemUpdates) {
				String currentUpdateItemID = item.getItemId();
				String currentBibID = item.getBID();
				if (!currentBibID.isEmpty()) {
					Record currentMarcRecord = loadMarc(currentBibID);
					if (currentMarcRecord != null) {
						Boolean itemFound = false;
						Boolean saveRecord = false;
						List<VariableField> currentMarcDataFields = currentMarcRecord.getVariableFields(itemTag);
						for (VariableField itemFieldVar: currentMarcDataFields) {
							DataField currentDataField = (DataField) itemFieldVar;
							String currentItemID = currentDataField.getSubfield(itemRecordNumberSubfield).getData();
							if (currentItemID.equals(currentUpdateItemID)) { // check ItemIDs for other item matches for this bib?
								currentMarcRecord.removeVariableField(currentDataField); // take out the existing Item tag
								updateItemDataFieldWithChangeInfo(currentDataField, item);
								currentMarcRecord.addVariableField(currentDataField);
								saveRecord = true;
								itemFound = true;
								break;
							} else if (createdItemIDs.contains(currentItemID)) {
								logger.warn("New Item " + currentItemID + "found on Bib " + currentBibID + "; Updating instead.");
								Integer indexOfItem = createdItemIDs.indexOf(currentItemID);
								ItemChangeInfo createdItem = createdItems.get(indexOfItem);
								updateItemDataFieldWithChangeInfo(currentDataField, createdItem);
								currentMarcRecord.addVariableField(currentDataField);
								createdItems.remove(createdItem); // remove Item Change Info
								createdItemIDs.remove(currentItemID); // remove itemId for list
								saveRecord = true;
							} else if (deletedItemIDs.contains(currentItemID)) {
								currentMarcRecord.removeVariableField(currentDataField);
								deletedItemIDs.remove(currentItemID); //TODO: check the API for the same BIB ID?
								saveRecord = true;
							}

						}
						if (!itemFound) {
							logger.warn("Item "+currentUpdateItemID + "to update was not found in Marc Record" + currentBibID +"; Adding instead.");
							DataField itemField = createItemDataFieldWithChangeInfo(item);
							currentMarcRecord.addVariableField(itemField);
							saveRecord = true;
						}
						if (saveRecord) {
							saveMarc(currentMarcRecord, currentBibID);

							// Mark Bib as Changed for Re-indexer
							try {
								markGroupedWorkForBibAsChangedStmt.setLong(1, updateTime);
								markGroupedWorkForBibAsChangedStmt.setString(2, currentBibID);
								markGroupedWorkForBibAsChangedStmt.executeUpdate();

								numUpdates++;
								if (numUpdates % 50 == 0){
									vufindConn.commit();
								}
							}catch (SQLException e){
								logger.error("Could not mark that " + currentBibID + " was changed due to error ", e);
								errorUpdatingDatabase = true;
							}
						}
					} else {
						// TODO: Do Marc Lookup & rebuild Marc Record?
//						logger.warn("Existing Marc Record for BID " + currentBibID + " failed to load; Writing new record with data from API");
						logger.warn("Existing Marc Record for BID " + currentBibID + " failed to load; Can not update item: " + currentUpdateItemID);
					}
				} else {
					logger.warn("Received Item "+ currentUpdateItemID + "to update without a Bib ID. No Record was updated.");
				}
			}


			// Now add left-over Created Items
			for (ItemChangeInfo item : createdItems) {
				String currentCreatedItemID = item.getItemId();
				String currentBibID = item.getBID();
				if (!currentBibID.isEmpty()) {
					Record currentMarcRecord = loadMarc(currentBibID);
					Boolean saveRecord = false;
					if (currentMarcRecord != null) {
						Boolean itemFound = false;
						List<VariableField> currentMarcDataFields = currentMarcRecord.getVariableFields(itemTag);
						for (VariableField itemFieldVar: currentMarcDataFields) {
							DataField currentDataField = (DataField) itemFieldVar;
							if (currentDataField.getTag().equals(itemTag)) {
								String currentItemID = currentDataField.getSubfield(itemRecordNumberSubfield).getData();
								if (currentItemID.equals(currentCreatedItemID)) { // check ItemIDs for other item matches for this bib?
									logger.warn("New Item " + currentItemID + " found on Bib " + currentBibID + "; Updating instead.");
									currentMarcRecord.removeVariableField(currentDataField);
									updateItemDataFieldWithChangeInfo(currentDataField, item);
									currentMarcRecord.addVariableField(currentDataField);
									saveRecord = true;
									itemFound = true;
									break;
								} else if (deletedItemIDs.contains(currentItemID)) {
									currentMarcRecord.removeVariableField(currentDataField);
									deletedItemIDs.remove(currentItemID); //TODO: check the API for the same BIB ID?
									saveRecord = true;
								}
							}
						}
						if (!itemFound) {
							logger.info("Item "+ currentCreatedItemID + "to create was not found in Marc Record" + currentBibID +"; Adding instead.");
							DataField itemField = createItemDataFieldWithChangeInfo(item);
							currentMarcRecord.addVariableField(itemField);
							saveRecord = true;
						}
					} else {
						logger.debug("Existing Marc Record for BID " + currentBibID + " failed to load; Creating new Marc Record for new item: " + currentCreatedItemID);
						currentMarcRecord = buildMarcRecordFromAPICall(currentBibID);  //TODO: Collect BIDs and do a bulk call instead?
						if (currentMarcRecord != null) {
							DataField itemField = createItemDataFieldWithChangeInfo(item);
							currentMarcRecord.addVariableField(itemField);
							saveRecord = true;
						} else {
							logger.error("Failed to load new marc record " + currentBibID + " from API call for created Item " + currentCreatedItemID);
						}
					}
					if (saveRecord) {
						saveMarc(currentMarcRecord, currentBibID);

						// Mark Bib as Changed for Re-indexer
						try {
							//TODO: this doesn't mark Newly created Bibs for Reindexing. (Doesn't have a groupedwork ID yet)
							markGroupedWorkForBibAsChangedStmt.setLong(1, updateTime);
							markGroupedWorkForBibAsChangedStmt.setString(2, currentBibID);
							markGroupedWorkForBibAsChangedStmt.executeUpdate();

							numUpdates++;
							if (numUpdates % 50 == 0){
								vufindConn.commit();
							}
						}catch (SQLException e){
							logger.error("Could not mark that " + currentBibID + " was changed due to error ", e);
							errorUpdatingDatabase = true;
						}
					}
				} else {
					logger.warn("Received Item "+ currentCreatedItemID + "to create without a Bib ID. No Record was created.");
				}
			}
		}

		// Now remove Any left-over deleted items
		if (deletedItemIDs.size() > 0 ) {
			for (String deletedItemID : deletedItemIDs) {
				//TODO: Now you *really* have to get the BID, dude.
			}
		}

		try {
			// Turn auto commit back on
			vufindConn.commit();
			vufindConn.setAutoCommit(true);
		} catch (Exception e) {
			logger.error("MySQL Error: " + e.toString());
		}


			//Connect to the CarlX database
		String url        = ini.get("Catalog", "carlx_db");
		String dbUser     = ini.get("Catalog", "carlx_db_user");
		String dbPassword = ini.get("Catalog", "carlx_db_password");
		if (url.startsWith("\"")){
			url = url.substring(1, url.length() - 1);
		}
		Connection carlxConn = null;
		try{
			//Open the connection to the database
			Properties props = new Properties();
			props.setProperty("user", dbUser);
			props.setProperty("password", dbPassword);
			carlxConn = DriverManager.getConnection(url, props);
//			orderStatusesToExport = ini.get("Reindex", "orderStatusesToExport");
//			if (orderStatusesToExport == null){
//				orderStatusesToExport = "o|1";
//			}
//			exportActiveOrders(exportPath, carlxConn);

			exportHolds(carlxConn, vufindConn);

			//Close CarlX connection
			carlxConn.close();

		}catch(Exception e){
			System.out.println("Error: " + e.toString());
			e.printStackTrace();
		}



		if (vufindConn != null) {
			try {
				// Wrap Up
				if (!errorUpdatingDatabase) {
					//Update the last extract time
					Long finishTime = new Date().getTime() / 1000;
					if (lastCarlXExtractTimeVariableId != null) {
						PreparedStatement updateVariableStmt = vufindConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
						updateVariableStmt.setLong(1, updateTime);
						updateVariableStmt.setLong(2, lastCarlXExtractTimeVariableId);
						updateVariableStmt.executeUpdate();
						updateVariableStmt.close();
					} else {
						PreparedStatement insertVariableStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('last_carlx_extract_time', ?)");
						insertVariableStmt.setString(1, Long.toString(exportStartTime));
						insertVariableStmt.executeUpdate();
						insertVariableStmt.close();
					}
				} else {
					logger.error("There was an error updating the database, not setting last extract time.");
				}

			try{
				//Close the connection
				vufindConn.close();
			}catch(Exception e){
				System.out.println("Error closing connection: " + e.toString());
			}

		} catch (Exception e) {
			logger.error("MySQL Error: " + e.toString());
		}

	}
		Date currentTime = new Date();
		logger.info(currentTime.toString() + ": Finished CarlX Extract");
	}


	private static Record buildMarcRecordFromAPIResponse(Node marcRecordNode, String currentBibID) {
		NodeList marcFields = marcRecordNode.getChildNodes();
		Integer numFields   = marcFields.getLength();

		Record updatedMarcRecordFromAPICall = MarcFactoryImpl.newInstance().newRecord();

		// Put XML data in the Record Object
		for (int j=0; j < numFields; j++) {
			Node marcField   = marcFields.item(j);
			String fieldName = marcField.getNodeName().replaceFirst("ns4:", "");
			switch (fieldName) {
				case "leader" :
					// Set Leader
					String leader = marcField.getTextContent();
					updatedMarcRecordFromAPICall.setLeader(MarcFactoryImpl.newInstance().newLeader(leader));
					break;
				case "controlField" :
					// Set Control Field
					String field = marcField.getTextContent();
					field = field.replace("{U+001E}", ""); // get rid of unicode characters at the end of some control fields.
					String tag;
					if (marcField.hasAttributes()) {
						NamedNodeMap attributes = marcField.getAttributes();
						Node attributeNode      = attributes.getNamedItem("tag");
						tag                     = attributeNode.getTextContent();
						updatedMarcRecordFromAPICall.addVariableField(MarcFactoryImpl.newInstance().newControlField(tag, field));
					} else {
						logger.warn("CarlX MarcOut data for a control field had no attributes. Could not update control field for BibID " + currentBibID);
					}
					break;
				case "dataField" :
					// Set data Field
					if (marcField.hasAttributes()) {
						// Get Tag Number
						NamedNodeMap attributes = marcField.getAttributes();
						Node attributeNode      = attributes.getNamedItem("tag");
						tag                     = attributeNode.getTextContent();

						// Get first indicator
						attributeNode        = attributes.getNamedItem("ind1");
						String tempString    = attributeNode.getNodeValue();
//												String tempString     = attributeNode.getTextContent();
						Character indicator1 = tempString.charAt(0);

						// Get second indicator
						attributeNode        = attributes.getNamedItem("ind2");
						tempString           = attributeNode.getNodeValue();
//												tempString            = attributeNode.getTextContent();
						Character indicator2 = tempString.charAt(0);

						// Go through sub-fields
						NodeList subFields   = marcField.getChildNodes();
						Integer numSubFields = subFields.getLength();

						// Initialize data field
						DataField dataField = MarcFactoryImpl.newInstance().newDataField(tag, indicator1, indicator2);

						// Add all subFields to the data field
						for (int k=0; k < numSubFields; k++) {
							Node subFieldNode = subFields.item(k);
							if (marcField.hasAttributes()) {
								attributes           = subFieldNode.getAttributes();
								attributeNode        = attributes.getNamedItem("code");
								tempString           = attributeNode.getNodeValue();
								Character code       = tempString.charAt(0);
								String subFieldValue = subFieldNode.getTextContent();
								Subfield subfield    = MarcFactoryImpl.newInstance().newSubfield(code, subFieldValue);
								dataField.addSubfield(subfield);
							}
						}

						// Add Data Field to MARC object
						updatedMarcRecordFromAPICall.addVariableField(dataField);

					} else {
						logger.warn("CarlX MarcOut data for a data field had no attributes. Could not update data field for BibID " + currentBibID);
					}
			}
		}
		return updatedMarcRecordFromAPICall;
	}

	private static ArrayList<ItemChangeInfo> fetchItemInformation(ArrayList<String> itemIDs) {
		ArrayList<ItemChangeInfo> itemUpdates = new ArrayList<>();
		if (itemIDs.size() > 0) {
			//TODO: Set an upper limit on number of IDs for one request, and process in batches
			String getItemInformationSoapRequest;
			String getItemInformationSoapRequestStart = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:mar=\"http://tlcdelivers.com/cx/schemas/marcoutAPI\" xmlns:req=\"http://tlcdelivers.com/cx/schemas/request\">\n" +
					"<soapenv:Header/>\n" +
					"<soapenv:Body>\n" +
					"<mar:GetItemInformationRequest>\n" +
					"<mar:ItemSearchType>ITEM</mar:ItemSearchType>\n";
			String getItemInformationSoapRequestEnd =
					"<mar:IncludeSuppressItems>true</mar:IncludeSuppressItems>\n" + // TODO: Do we want this on??
							"<mar:Modifiers>\n" +
							"</mar:Modifiers>\n" +
							"</mar:GetItemInformationRequest>\n" +
							"</soapenv:Body>\n" +
							"</soapenv:Envelope>";
			try {
				getItemInformationSoapRequest = getItemInformationSoapRequestStart;
				// Updated Items
				for (String updatedItem : itemIDs) {
					getItemInformationSoapRequest += "<mar:ItemSearchTerm>" + updatedItem + "</mar:ItemSearchTerm>\n";
				}
				getItemInformationSoapRequest += getItemInformationSoapRequestEnd;

				URLPostResponse ItemInformationSOAPResponse = postToURL(marcOutURL, getItemInformationSoapRequest, "text/xml", null, logger);

				// Parse Response
				Document doc                        = createXMLDocumentForSoapResponse(ItemInformationSOAPResponse);
				Node soapEnvelopeNode               = doc.getFirstChild();
				Node soapBodyNode                   = soapEnvelopeNode.getLastChild();
				Node getItemInformationResponseNode = soapBodyNode.getFirstChild();
				Node responseStatus                 = getItemInformationResponseNode.getFirstChild().getFirstChild();
																							// There is a Response Statuses Node, which then contains the Response Status Node
				String responseStatusCode           = responseStatus.getFirstChild().getTextContent();
				if (responseStatusCode.equals("0") ) { // Successful response

					NodeList ItemStatuses = getItemInformationResponseNode.getChildNodes();

					int l = ItemStatuses.getLength();
					for (int i = 1; i < l; i++) {
						// start with i = 1 to skip first node, because that is the response status node and not an item status

						Node itemStatus = ItemStatuses.item(i);
						if (itemStatus.getNodeName().contains("ItemStatus")) { // avoid other occasional nodes like "Message"

							NodeList itemDetails = itemStatus.getChildNodes();
							ItemChangeInfo currentItem = new ItemChangeInfo();

							int dl = itemDetails.getLength();
							for (int j = 0; j < dl; j++) {
								Node detail = itemDetails.item(j);
								String detailName = detail.getNodeName();
								String detailValue = detail.getTextContent();

								detailName = detailName.replaceFirst("ns4:", ""); // strip out namespace prefix

								// Handle each detail
								switch (detailName) {
									case "BID":
										currentItem.setBID(detailValue);
										break;
									case "ItemID":
										currentItem.setItemId(detailValue);
										break;
									case "LocationCode":
										currentItem.setShelvingLocation(detailValue);
										break;
									case "Status":
										// Set itemIdentifier for logging with info that we know at this point.
										String itemIdentifier = "";
										// Use code below if we every turn on switch fullReindex (logs missing translation values)
										if (currentItem.getBID().isEmpty()) {
											itemIdentifier = currentItem.getItemId().isEmpty() ? "a Carl-X Item" : " for item ID " + currentItem.getItemId();
										} else {
											itemIdentifier = currentItem.getItemId().isEmpty() ? currentItem.getBID() + " for an unknown Carl-X Item" : currentItem.getBID() + " for item ID " + currentItem.getItemId();
										}
										String statusCode = translateValue("status_codes", detailValue, itemIdentifier);
										//TODO: If status code wasn't translate, set to Unknown (U) (Not an actual listed value)?
										currentItem.setStatus(statusCode);
										break;
									case "DueDate":
										String dueDateMarc = formatDateFieldForMarc(dueDateFormat, detailValue);
										currentItem.setDueDate(dueDateMarc);
										break;
									case "LastCheckinDate":
										// There is no LastCheckinDate field in ItemInformation Call
										String lastCheckInDateMarc = formatDateFieldForMarc(lastCheckInFormat, detailValue);
										currentItem.setLastCheckinDate(lastCheckInDateMarc);
										break;
									case "CreationDate":
										String dateCreatedMarc = formatDateFieldForMarc(dateCreatedFormat, detailValue);
										currentItem.setDateCreated(dateCreatedMarc);
										break;
									case "CallNumber":
									case "CallNumberFull":
										currentItem.setCallNumber(detailValue);
										break;
									case "CircHistory": // total since counter reset: translating to total checkout per year
										currentItem.setYearToDateCheckouts(detailValue);
										break;
									case "CumulativeHistory":
										currentItem.setTotalCheckouts(detailValue);
										break;
									case "BranchCode":
										currentItem.setLocation(detailValue);
										break;
									case "MediaCode":
										currentItem.setiType(detailValue);
										break;
									// Fields we don't currently do anything with
									case "HoldsHistory": // Number of times item has gone to Hold Shelf status since counter set
									case "InHouseCirc":
									case "Price":
									case "ReserveBranchCode":
									case "ReserveType":
									case "ReserveBranchLocation":
									case "ReserveCallNumber":
									case "BranchName":
									case "BranchNumber":
									case "StatusDate": //TODO: can we use this one?
									case "ThereAtLeastOneNote":
									case "Notes":
									case "EditDate":
									case "CNLabels":
									case "Caption":
									case "Number":
									case "Part":
									case "Volume":
									case "Suffix":
//									CNLabels: Labels for the 4 call number buckets
//									Number: Third call number bucket
//									Part: Second call number bucket
//									Volume: First call number bucket
//									Suffix: Fourth call number bucket
									case "ISID":
									case "Chronology":
									case "Enumeration":
									case "OwningBranchCode":
									case "OwningBranchName":
									case "OwningBranchNumber":
									case "Suppress":
									case "Type":
										// Do Nothing
										break;
									default:
										logger.warn("New Item Detail : " + detailName);
										break;
								}
							}
							itemUpdates.add(currentItem);
						}
					}
				}
			} catch (Exception e) {
				logger.error("Error Retrieving SOAP updated items", e);
			}
		}
		return itemUpdates;
	}

	private static String[] getIDsStringArrayFromNodeList(NodeList walkThroughMe) {
		Integer l       = walkThroughMe.getLength();
		String[] idList = new String[l];
		for (int i = 0; i < l; i++) {
			idList[i] = walkThroughMe.item(i).getTextContent();
		}
		return idList;
	}

	private static String formatDateFieldForMarc(String dateFormat, String date) {
		String dateForMarc = null;
		try {
			String itemInformationDateFormat = "yyyy-MM-dd'T'HH:mm:ss.SSSXXX";
			SimpleDateFormat dateFormatter = new SimpleDateFormat(itemInformationDateFormat);
			dateFormatter.setTimeZone(TimeZone.getTimeZone("UTC"));
			Date marcDate = dateFormatter.parse(date);
			SimpleDateFormat marcDateCreatedFormat = new SimpleDateFormat(dateFormat);
			dateForMarc = marcDateCreatedFormat.format(marcDate);
		} catch (Exception e) {
			logger.error("Error while formatting a date field for Marc Record", e);
		}
		return dateForMarc;
	}

	private static ArrayList<String> getIDsArrayListFromNodeList(NodeList walkThroughMe) {
		Integer l                = walkThroughMe.getLength();
		ArrayList<String> idList = new ArrayList<>();
		for (int i = 0; i < l; i++) {
			String itemID = walkThroughMe.item(i).getTextContent();
			idList.add(itemID);
		}
		return idList;
	}

	private static void updateItemDataFieldWithChangeInfo(DataField itemField, ItemChangeInfo changeInfo) {
		itemField.getSubfield(locationSubfield).setData(changeInfo.getLocation());
		itemField.getSubfield(statusSubfield).setData(changeInfo.getStatus());
		if (callNumberSubfield != ' ' && !changeInfo.getCallNumber().isEmpty()) {
			itemField.getSubfield(callNumberSubfield).setData(changeInfo.getCallNumber());
		}

		if (totalCheckoutsSubfield != ' ' && !changeInfo.getTotalCheckouts().isEmpty()) {
			itemField.getSubfield(totalCheckoutsSubfield).setData(changeInfo.getTotalCheckouts());
		}

		if (yearToDateCheckoutsSubfield != ' ' && !changeInfo.getYearToDateCheckouts().isEmpty()) {
			itemField.getSubfield(yearToDateCheckoutsSubfield).setData(changeInfo.getYearToDateCheckouts());
		}

		if (iTypeSubfield != ' ' && !changeInfo.getYearToDateCheckouts().isEmpty()) {
			itemField.getSubfield(iTypeSubfield).setData(changeInfo.getiType());
		}

		if (dueDateSubfield != ' ') {
			if (changeInfo.getDueDate() == null) {
				if (itemField.getSubfield(dueDateSubfield) != null) {
					if (dueDateFormat.contains("-")){
						itemField.getSubfield(dueDateSubfield).setData("  -  -  ");
					} else {
						itemField.getSubfield(dueDateSubfield).setData("      ");
					}
				}
			} else {
				if (itemField.getSubfield(dueDateSubfield) == null) {
					itemField.addSubfield(new SubfieldImpl(dueDateSubfield, changeInfo.getDueDate()));
				} else {
					itemField.getSubfield(dueDateSubfield).setData(changeInfo.getDueDate());
				}
			}
		}

		if (dateCreatedSubfield != ' ') {
			if (changeInfo.getDateCreated() == null) {
				if (itemField.getSubfield(dateCreatedSubfield) != null) {
					if (dateCreatedFormat.contains("-")){
						itemField.getSubfield(dateCreatedSubfield).setData("  -  -  ");
					} else {
						itemField.getSubfield(dateCreatedSubfield).setData("      ");
					}
				}
			} else {
				if (itemField.getSubfield(dateCreatedSubfield) == null) {
					itemField.addSubfield(new SubfieldImpl(dateCreatedSubfield, changeInfo.getDateCreated()));
				} else {
					itemField.getSubfield(dateCreatedSubfield).setData(changeInfo.getDateCreated());
				}
			}
		}

		if (lastCheckInSubfield != ' ') {
			if (changeInfo.getLastCheckinDate() == null) {
				if (itemField.getSubfield(lastCheckInSubfield) != null) {
					if (lastCheckInFormat.contains("-")) {
						itemField.getSubfield(lastCheckInSubfield).setData("  -  -  ");
					} else {
						itemField.getSubfield(lastCheckInSubfield).setData("      ");
					}
				}
			} else {
				if (itemField.getSubfield(lastCheckInSubfield) == null) {
					itemField.addSubfield(new SubfieldImpl(lastCheckInSubfield, changeInfo.getLastCheckinDate()));
				} else {
					itemField.getSubfield(lastCheckInSubfield).setData(changeInfo.getLastCheckinDate());
				}
			}
		}
	}

	private static DataField createItemDataFieldWithChangeInfo(ItemChangeInfo changeInfo) {
		DataField itemField = MarcFactoryImpl.newInstance().newDataField(itemTag, ' ', ' ');
		itemField.addSubfield(new SubfieldImpl(itemRecordNumberSubfield, changeInfo.getItemId()));
		itemField.addSubfield(new SubfieldImpl(locationSubfield, changeInfo.getLocation()));
		itemField.addSubfield(new SubfieldImpl(shelvingLocationSubfield, changeInfo.getShelvingLocation()));
		itemField.addSubfield(new SubfieldImpl(statusSubfield, changeInfo.getStatus()));

		if (callNumberSubfield != ' ') {
			itemField.addSubfield(new SubfieldImpl(callNumberSubfield, changeInfo.getCallNumber()));
		}

		if (totalCheckoutsSubfield != ' ') {
			itemField.addSubfield(new SubfieldImpl(totalCheckoutsSubfield, changeInfo.getTotalCheckouts()));
		}

		if (yearToDateCheckoutsSubfield != ' ') {
			itemField.addSubfield(new SubfieldImpl(yearToDateCheckoutsSubfield, changeInfo.getYearToDateCheckouts()));
		}

		if (iTypeSubfield != ' ') {
			itemField.addSubfield(new SubfieldImpl(iTypeSubfield, changeInfo.getiType()));
		}

		if (dueDateSubfield != ' ') {
			if (changeInfo.getDueDate() == null) {
					if (dueDateFormat.contains("-")){
						itemField.addSubfield(new SubfieldImpl(dueDateSubfield, "  -  -  "));
					} else {
						itemField.addSubfield(new SubfieldImpl(dueDateSubfield, "      "));
					}
			} else {
				itemField.addSubfield(new SubfieldImpl(dueDateSubfield, changeInfo.getDueDate()));
			}
		}

		if (dateCreatedSubfield != ' ') {
			if (changeInfo.getDueDate() == null) {
					if (dateCreatedFormat.contains("-")){
						itemField.addSubfield(new SubfieldImpl(dateCreatedSubfield, "  -  -  "));
					} else {
						itemField.addSubfield(new SubfieldImpl(dateCreatedSubfield, "      "));
					}
			} else {
				itemField.addSubfield(new SubfieldImpl(dateCreatedSubfield, changeInfo.getDueDate()));
			}
		}

		if (lastCheckInSubfield != ' ') {
			if (changeInfo.getDueDate() == null) {
					if (lastCheckInFormat.contains("-")){
						itemField.addSubfield(new SubfieldImpl(lastCheckInSubfield, "  -  -  "));
					} else {
						itemField.addSubfield(new SubfieldImpl(lastCheckInSubfield, "      "));
					}
			} else {
				itemField.addSubfield(new SubfieldImpl(lastCheckInSubfield, changeInfo.getDueDate()));
			}
		}
		return itemField;
	}

	private static Record loadMarc(String curBibId) {
		//Load the existing marc record from file
		try {
			File marcFile = getFileForIlsRecord(individualMarcPath, curBibId);
			if (marcFile.exists()) {
				FileInputStream inputStream = new FileInputStream(marcFile);
				MarcPermissiveStreamReader marcReader = new MarcPermissiveStreamReader(inputStream, true, true, "UTF-8");
				if (marcReader.hasNext()) {
					Record marcRecord = marcReader.next();
					inputStream.close();
					return marcRecord;
				} else {
					logger.info("Could not read marc record for " + curBibId + ". The bib was empty");
				}
			}else{
				logger.debug("Marc Record does not exist for " + curBibId + ". It is not part of the main extract yet.");
			}
		}catch (Exception e){
			logger.error("Error updating marc record for bib " + curBibId, e);
		}
		return null;
	}

	private static void saveMarc(Record marcObject, String curBibId) {
		//Write the new marc record
//		String shortId            = getFileIdForRecordNumber(curBibId);
//		String firstChars         = "CARL";
//		String basePath           = individualMarcPath + "/" + firstChars;
//		String individualFilename = basePath + "/" + shortId + "_new.mrc";
//		File marcFile =  new File(individualFilename);
		// The above is for debugging

		File marcFile = getFileForIlsRecord(individualMarcPath, curBibId);

		MarcWriter writer = null;
		try {
			writer = new MarcStreamWriter(new FileOutputStream(marcFile, false));
			writer.write(marcObject);
			writer.close();
		} catch (FileNotFoundException e) {
			logger.error("Error saving marc record for bib " + curBibId, e);
		}
	}

	private static File getFileForIlsRecord(String individualMarcPath, String recordNumber) {
		String shortId           = getFileIdForRecordNumber(recordNumber);
		String firstChars        = shortId.substring(0, 4);
//		TODO: individual marc record folder creation needs adjusting for CarlX (PK-2191)
//		String firstChars         = "CARL";
		String basePath           = individualMarcPath + "/" + firstChars;
		String individualFilename = basePath + "/" + shortId + ".mrc";
		return new File(individualFilename);
	}

	private static String getFileIdForRecordNumber(String recordNumber) {
		while (recordNumber.length() < 10){ // pad up to a 10-digit number
			recordNumber = "0" + recordNumber;
		}
		String fileId = "CARL" + recordNumber; // add Carl prefix
		return fileId;
	}

	private static Document createXMLDocumentForSoapResponse(URLPostResponse SoapResponse) throws ParserConfigurationException, IOException, SAXException {
		DocumentBuilderFactory dbFactory = DocumentBuilderFactory.newInstance();
		DocumentBuilder dBuilder = null;
		Document doc;

		dBuilder = dbFactory.newDocumentBuilder();

		byte[]                soapResponseByteArray            = SoapResponse.getMessage().getBytes("utf-8");
		ByteArrayInputStream  soapResponseByteArrayInputStream = new ByteArrayInputStream(soapResponseByteArray);
		InputSource           soapResponseInputSource          = new InputSource(soapResponseByteArrayInputStream);

		doc = dBuilder.parse(soapResponseInputSource);
		doc.getDocumentElement().normalize();

		return doc;
	}

	private static Ini loadConfigFile(String filename){
		//First load the default config file
		String configName = "../../sites/default/conf/" + filename;
		logger.info("Loading configuration from " + configName);
		File configFile = new File(configName);
		if (!configFile.exists()) {
			logger.error("Could not find configuration file " + configName);
			System.exit(1);
		}

		// Parse the configuration file
		Ini ini = new Ini();
		try {
			ini.load(new FileReader(configFile));
		} catch (InvalidFileFormatException e) {
			logger.error("Configuration file is not valid.  Please check the syntax of the file.", e);
		} catch (FileNotFoundException e) {
			logger.error("Configuration file could not be found.  You must supply a configuration file in conf called config.ini.", e);
		} catch (IOException e) {
			logger.error("Configuration file could not be read.", e);
		}

		//Now override with the site specific configuration
		String siteSpecificFilename = "../../sites/" + serverName + "/conf/" + filename;
		logger.info("Loading site specific config from " + siteSpecificFilename);
		File siteSpecificFile = new File(siteSpecificFilename);
		if (!siteSpecificFile.exists()) {
			logger.error("Could not find server specific config file");
			System.exit(1);
		}
		try {
			Ini siteSpecificIni = new Ini();
			siteSpecificIni.load(new FileReader(siteSpecificFile));
			for (Profile.Section curSection : siteSpecificIni.values()){
				for (String curKey : curSection.keySet()){
					//logger.debug("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					//System.out.println("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					ini.put(curSection.getName(), curKey, curSection.get(curKey));
				}
			}
			//Also load password files if they exist
			String siteSpecificPassword = "../../sites/" + serverName + "/conf/config.pwd.ini";
			logger.info("Loading password config from " + siteSpecificPassword);
			File siteSpecificPasswordFile = new File(siteSpecificPassword);
			if (siteSpecificPasswordFile.exists()) {
				Ini siteSpecificPwdIni = new Ini();
				siteSpecificPwdIni.load(new FileReader(siteSpecificPasswordFile));
				for (Profile.Section curSection : siteSpecificPwdIni.values()){
					for (String curKey : curSection.keySet()){
						ini.put(curSection.getName(), curKey, curSection.get(curKey));
					}
				}
			}
		} catch (InvalidFileFormatException e) {
			logger.error("Site Specific config file is not valid.  Please check the syntax of the file.", e);
		} catch (IOException e) {
			logger.error("Site Specific config file could not be read.", e);
		}

		return ini;
	}

	public static String cleanIniValue(String value) {
		if (value == null) {
			return null;
		}
		value = value.trim();
		if (value.startsWith("\"")) {
			value = value.substring(1);
		}
		if (value.endsWith("\"")) {
			value = value.substring(0, value.length() - 1);
		}
		return value;
	}

	public static URLPostResponse postToURL(String url, String postData, String contentType, String referer, Logger logger) {
		URLPostResponse retVal;
		HttpURLConnection conn = null;
		try {
			URL emptyIndexURL = new URL(url);
			conn = (HttpURLConnection) emptyIndexURL.openConnection();
			conn.setConnectTimeout(1000);
			conn.setReadTimeout(300000);
			logger.debug("Posting To URL " + url + (postData != null && postData.length() > 0 ? "?" + postData : ""));

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
			conn.setDoInput(true);
			if (referer != null){
				conn.setRequestProperty("Referer", referer);
			}
			conn.setRequestMethod("POST");
			if (postData != null && postData.length() > 0) {
				conn.setRequestProperty("Content-Type", contentType + "; charset=utf-8");
				conn.setRequestProperty("Content-Language", "en-US");
				conn.setRequestProperty("Connection", "keep-alive");

				conn.setDoOutput(true);
				OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream(), "UTF8");
				wr.write(postData);
				wr.flush();
				wr.close();
			}

			StringBuffer response = new StringBuffer();
			if (conn.getResponseCode() == 200) {
				// Get the response
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}

				rd.close();
				retVal = new URLPostResponse(true, 200, response.toString());
			} else {
				logger.error("Received error " + conn.getResponseCode() + " posting to " + url);
				logger.info(postData);
				// Get any errors
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}

				rd.close();

				if (response.length() == 0){
					//Try to load the regular body as well
					// Get the response
					BufferedReader rd2 = new BufferedReader(new InputStreamReader(conn.getInputStream()));
					while ((line = rd2.readLine()) != null) {
						response.append(line);
					}

					rd.close();
				}
				retVal = new URLPostResponse(false, conn.getResponseCode(), response.toString());
			}

		} catch (MalformedURLException e) {
			logger.error("URL to post (" + url + ") is malformed", e);
			retVal = new URLPostResponse(false, -1, "URL to post (" + url + ") is malformed");
		} catch (IOException e) {
			logger.error("Error posting to url \r\n" + url, e);
			retVal = new URLPostResponse(false, -1, "Error posting to url \r\n" + url + "\r\n" + e.toString());
		}finally{
			if (conn != null) conn.disconnect();
		}
		return retVal;
	}

	public static String translateValue(String mapName, String value, String identifier){
		if (value == null){
			return null;
		}
		TranslationMap translationMap = translationMaps.get(mapName);
		String translatedValue;
		if (translationMap == null){
			logger.error("Unable to find translation map for " + mapName + " in profile " + profileType);
			translatedValue = value;
		}else{
			translatedValue = translationMap.translateValue(value, identifier);
		}
		return translatedValue;
	}

	private static void loadTranslationMapsForProfile(Connection vufindConn, long id) throws SQLException{
		PreparedStatement getTranslationMapsStmt = vufindConn.prepareStatement("SELECT * from translation_maps WHERE indexingProfileId = ?");
		PreparedStatement getTranslationMapValuesStmt = vufindConn.prepareStatement("SELECT * from translation_map_values WHERE translationMapId = ?");
		getTranslationMapsStmt.setLong(1, id);
		ResultSet translationsMapRS = getTranslationMapsStmt.executeQuery();
		while (translationsMapRS.next()){
			TranslationMap map = new TranslationMap(profileType, translationsMapRS.getString("name"), fullReindex, translationsMapRS.getBoolean("usesRegularExpressions"), logger);
			Long translationMapId = translationsMapRS.getLong("id");
			getTranslationMapValuesStmt.setLong(1, translationMapId);
			ResultSet translationMapValuesRS = getTranslationMapValuesStmt.executeQuery();
			while (translationMapValuesRS.next()){
				map.addValue(translationMapValuesRS.getString("value"), translationMapValuesRS.getString("translation"));
			}
			translationMaps.put(map.getMapName(), map);
		}
	}

	private static Record buildMarcRecordFromAPICall(String BibID) {
		Record marcRecordFromAPICall = null;
		try {
			String getMarcRecordsSoapRequest = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:mar=\"http://tlcdelivers.com/cx/schemas/marcoutAPI\" xmlns:req=\"http://tlcdelivers.com/cx/schemas/request\">\n" +
					"<soapenv:Header/>\n" +
					"<soapenv:Body>\n" +
					"<mar:GetMARCRecordsRequest>\n" +
					"<mar:BID>" + BibID + "</mar:BID>" +
			"		<mar:Include949ItemData>0</mar:Include949ItemData>\n" +
					"<mar:IncludeOnlyUnsuppressed>0</mar:IncludeOnlyUnsuppressed>\n" +
					"<mar:Modifiers>\n" +
					"</mar:Modifiers>\n" +
					"</mar:GetMARCRecordsRequest>\n" +
					"</soapenv:Body>\n" +
					"</soapenv:Envelope>";

			URLPostResponse marcRecordSOAPResponse = postToURL(marcOutURL, getMarcRecordsSoapRequest, "text/xml", null, logger);

			// Parse Response
			Document doc                    = createXMLDocumentForSoapResponse(marcRecordSOAPResponse);
			Node soapEnvelopeNode           = doc.getFirstChild();
			Node soapBodyNode               = soapEnvelopeNode.getLastChild();
			Node getMarcRecordsResponseNode = soapBodyNode.getFirstChild();
			NodeList marcRecordInfo         = getMarcRecordsResponseNode.getChildNodes();
			Node marcRecordsResponseStatus  = getMarcRecordsResponseNode.getFirstChild().getFirstChild();
			String responseStatusCode       = marcRecordsResponseStatus.getFirstChild().getTextContent();

			if (responseStatusCode.equals("0") ) { // Successful response
					Node marcRecordNode = marcRecordInfo.item(1);

					// Build Marc Object from the API data
					marcRecordFromAPICall = buildMarcRecordFromAPIResponse(marcRecordNode, BibID);
			} else {
				String shortErrorMessage = marcRecordsResponseStatus.getChildNodes().item(2).getTextContent();
				logger.error("Error Response for API call for getting Marc Records : " + shortErrorMessage);
			}
		} catch (Exception e) {
			logger.error("Error Creating SOAP Request for Marc Records", e);
		}
		return marcRecordFromAPICall;
	}

	private static void exportHolds(Connection carlxConn, Connection vufindConn) {

		Savepoint startOfHolds = null;
		try {
			logger.info("Starting export of holds");

			//Start a transaction so we can rebuild an entire table
			startOfHolds = vufindConn.setSavepoint();
			vufindConn.setAutoCommit(false);
			vufindConn.prepareCall("TRUNCATE TABLE ils_hold_summary").executeQuery();

			PreparedStatement addIlsHoldSummary = vufindConn.prepareStatement("INSERT INTO ils_hold_summary (ilsId, numHolds) VALUES (?, ?)");

			HashMap<String, Long> numHoldsByBib = new HashMap<>();
//			HashMap<String, Long> numHoldsByVolume = new HashMap<>();
			//Export bib level holds
			PreparedStatement bibHoldsStmt = carlxConn.prepareStatement("select bid,sum(count) numHolds from (\n" +
					"  select bid,count(1) count from transbid_v group by bid\n" +
					"  UNION ALL\n" +
					"  select bid,count(1) count from transitem_v, item_v where\n" +
					"    transcode like 'R%' and transitem_v.item=item_v.item\n" +
					"  group by bid)\n" +
					"group by bid\n" +
					"order by bid", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet bibHoldsRS = bibHoldsStmt.executeQuery();
			while (bibHoldsRS.next()){
				String bibId = bibHoldsRS.getString("bid");
				Long numHolds = bibHoldsRS.getLong("numHolds");
				numHoldsByBib.put(bibId, numHolds);
			}
			bibHoldsRS.close();

//			if (exportItemHolds) {
//				//Export item level holds
//				PreparedStatement itemHoldsStmt = carlxConn.prepareStatement("select count(hold.id) as numHolds, record_num\n" +
//						"from sierra_view.hold \n" +
//						"inner join sierra_view.bib_record_item_record_link ON hold.record_id = item_record_id \n" +
//						"inner join sierra_view.record_metadata on bib_record_item_record_link.bib_record_id = record_metadata.id \n" +
//						"WHERE status = '0' OR status = 't' " +
//						"group by record_num", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
//				ResultSet itemHoldsRS = itemHoldsStmt.executeQuery();
//				while (itemHoldsRS.next()) {
//					String bibId = itemHoldsRS.getString("record_num");
//					bibId = ".b" + bibId + getCheckDigit(bibId);
//					Long numHolds = itemHoldsRS.getLong("numHolds");
//					if (numHoldsByBib.containsKey(bibId)) {
//						numHoldsByBib.put(bibId, numHolds + numHoldsByBib.get(bibId));
//					} else {
//						numHoldsByBib.put(bibId, numHolds);
//					}
//				}
//				itemHoldsRS.close();
//			}
//
//			//Export volume level holds
//			PreparedStatement volumeHoldsStmt = carlxConn.prepareStatement("select count(hold.id) as numHolds, bib_metadata.record_num as bib_num, volume_metadata.record_num as volume_num\n" +
//					"from sierra_view.hold \n" +
//					"inner join sierra_view.bib_record_volume_record_link ON hold.record_id = volume_record_id \n" +
//					"inner join sierra_view.record_metadata as volume_metadata on bib_record_volume_record_link.volume_record_id = volume_metadata.id \n" +
//					"inner join sierra_view.record_metadata as bib_metadata on bib_record_volume_record_link.bib_record_id = bib_metadata.id \n" +
//					"WHERE status = '0' OR status = 't'\n" +
//					"GROUP BY bib_metadata.record_num, volume_metadata.record_num", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
//			ResultSet volumeHoldsRS = volumeHoldsStmt.executeQuery();
//			while (volumeHoldsRS.next()) {
//				String bibId = volumeHoldsRS.getString("bib_num");
//				bibId = ".b" + bibId + getCheckDigit(bibId);
//				String volumeId = volumeHoldsRS.getString("volume_num");
//				volumeId = ".j" + volumeId + getCheckDigit(volumeId);
//				Long numHolds = volumeHoldsRS.getLong("numHolds");
//				//Do not count these in
//				if (numHoldsByBib.containsKey(bibId)) {
//					numHoldsByBib.put(bibId, numHolds + numHoldsByBib.get(bibId));
//				} else {
//					numHoldsByBib.put(bibId, numHolds);
//				}
//				if (numHoldsByVolume.containsKey(volumeId)) {
//					numHoldsByVolume.put(volumeId, numHolds + numHoldsByVolume.get(bibId));
//				} else {
//					numHoldsByVolume.put(volumeId, numHolds);
//				}
//			}
//			volumeHoldsRS.close();


			for (String bibId : numHoldsByBib.keySet()){
				addIlsHoldSummary.setString(1, bibId);
				addIlsHoldSummary.setLong(2, numHoldsByBib.get(bibId));
				addIlsHoldSummary.executeUpdate();
			}

//			for (String volumeId : numHoldsByVolume.keySet()){
//				addIlsHoldSummary.setString(1, volumeId);
//				addIlsHoldSummary.setLong(2, numHoldsByVolume.get(volumeId));
//				addIlsHoldSummary.executeUpdate();
//			}

			try {
				vufindConn.commit();
				vufindConn.setAutoCommit(true);
			}catch (Exception e){
				logger.warn("error committing hold updates rolling back", e);
				vufindConn.rollback(startOfHolds);
			}

		} catch (Exception e) {
			logger.error("Unable to export holds from Sierra", e);
			if (startOfHolds != null) {
				try {
					vufindConn.rollback(startOfHolds);
				}catch (Exception e1){
					logger.error("Unable to rollback due to exception", e1);
				}
			}
		}
		logger.info("Finished exporting holds");

	}

}
