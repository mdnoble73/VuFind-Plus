package org.marmot.pika;

import java.io.*;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.sql.*;
import java.text.DateFormat;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.List;
import java.util.TimeZone;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile;
import org.ini4j.Profile.Section;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcStreamWriter;
import org.marc4j.MarcWriter;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.VariableField;
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
 * Created by pbrammeier on 7/25/2016.
 */
public class CarlXExportMain {
	private static Logger logger = Logger.getLogger(CarlXExportMain.class);
	private static String serverName;

	private static String itemTag;
	private static char itemRecordNumberSubfield;
	private static char locationSubfield;
	private static char statusSubfield;
	private static char dueDateSubfield; //TODO: Ignore for now
	private static String dueDateFormat;
	private static char lastCheckInSubfield;
	private static String lastCheckInFormat;

	private static String individualMarcPath;


	public static void main(String[] args) {
		serverName = args[0];

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

		//Get the Indexing Profile from the database
		try {
			PreparedStatement getCarlXIndexingProfileStmt = vufindConn.prepareStatement("SELECT * FROM indexing_profiles where name ='ils'");
			ResultSet carlXIndexingProfileRS = getCarlXIndexingProfileStmt.executeQuery();
			if (carlXIndexingProfileRS.next()) {
				String itemTag                  = carlXIndexingProfileRS.getString("itemTag");
				String itemRecordNumberSubfield = carlXIndexingProfileRS.getString("itemRecordNumber");
				String lastCheckinDateSubfield  = carlXIndexingProfileRS.getString("lastCheckinDate");
				String lastCheckinFormat        = carlXIndexingProfileRS.getString("lastCheckinFormat");
				String locationSubfield         = carlXIndexingProfileRS.getString("location");
				String itemStatusSubfield       = carlXIndexingProfileRS.getString("status");
				String dueDateSubfield          = carlXIndexingProfileRS.getString("dueDate");
				String individualMarcPath       = carlXIndexingProfileRS.getString("individualMarcPath");

				CarlXExportMain.itemTag                  = itemTag;
				CarlXExportMain.itemRecordNumberSubfield = itemRecordNumberSubfield.length() > 0 ? itemRecordNumberSubfield.charAt(0) : ' ';
				CarlXExportMain.lastCheckInSubfield      = lastCheckinDateSubfield.length() > 0 ? lastCheckinDateSubfield.charAt(0) : ' ';
				CarlXExportMain.lastCheckInFormat        = lastCheckinFormat;
				CarlXExportMain.locationSubfield         = locationSubfield.length() > 0 ? locationSubfield.charAt(0) : ' ';
				CarlXExportMain.statusSubfield           = itemStatusSubfield.length() > 0 ? itemStatusSubfield.charAt(0) : ' ';
				CarlXExportMain.dueDateSubfield          = dueDateSubfield.length() > 0 ? dueDateSubfield.charAt(0) : ' ';
				// TODO: No value in CarlX sandbox indexing profile.  Might not use?
				CarlXExportMain.dueDateFormat            = lastCheckinFormat;
				// TODO: Not in indexing profile
				CarlXExportMain.individualMarcPath = individualMarcPath;

				String carlXExportPath          = carlXIndexingProfileRS.getString("marcPath");
//				String filenamesToInclude      = carlXIndexingProfileRS.getString("filenamesToInclude");
//				String groupingClass           = carlXIndexingProfileRS.getString("groupingClass");
				String recordNumberTag          = carlXIndexingProfileRS.getString("recordNumberTag");
				String recordNumberPrefix       = carlXIndexingProfileRS.getString("recordNumberPrefix");
//				String marcEncoding            = carlXIndexingProfileRS.getString("marcEncoding");
				String callNumberSubfield       = carlXIndexingProfileRS.getString("callNumber");
				String itemBarcodeSubfield      = carlXIndexingProfileRS.getString("barcode");
				String shelvingLocationSubfield = carlXIndexingProfileRS.getString("shelvingLocation");
				String collectionSubfield       = carlXIndexingProfileRS.getString("collection");
				// shelvingLocation & collection sub fields are the same in the sandbox

			} else {
				logger.error("Unable to find carlx indexing profile, please create a profile with the name ils.");
			}

		}catch (Exception e){
			logger.error("Error reading index profile for CarlX", e);
		}

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

			beginTimeString = "2013-12-31T12:00:00Z";
			// TODO: Delete Line; Use the value from the Example for now

		} catch (Exception e) {
			logger.error("Error getting last Extract Time for CarlX", e);
		}

		// Get MarcOut WSDL url for SOAP calls
		String marcOutURL = ini.get("Catalog", "marcOutApiWsdl");


		String changedItemsSoapRequest = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:mar=\"http://tlcdelivers.com/cx/schemas/marcoutAPI\" xmlns:req=\"http://tlcdelivers.com/cx/schemas/request\">\n" +
				"<soapenv:Header/>\n" +
				"<soapenv:Body>\n" +
				"<mar:GetChangedItemsRequest>\n" +
				"<mar:BeginTime>"+ beginTimeString + "</mar:BeginTime>\n" +
				"<mar:Modifiers/>\n" +
				"</mar:GetChangedItemsRequest>\n" +
				"</soapenv:Body>\n" +
				"</soapenv:Envelope>";

		URLPostResponse SOAPResponse = postToURL(marcOutURL, changedItemsSoapRequest, "text/xml", null, logger);

		String[] updatedItemIDs = new String[0];
//		String[] createdItemIDs = new String[0];
//		String[] deletedItemIDs = new String[0];

		try {
			Document doc = createXMLDocumentForSoapResponse(SOAPResponse);

			// Navigate Down Soap Response
//			Node soapEnvelopeNode            = doc.getChildNodes().item(0); // alternative path
			Node soapEnvelopeNode            = doc.getFirstChild();
//			Node soapBodyNode                = soapEnvelopeNode.getChildNodes().item(1); // alternative path
			Node soapBodyNode                = soapEnvelopeNode.getLastChild();
//			Node getChangedItemsResponseNode = soapBodyNode.getChildNodes().item(0); // alternative path
			Node getChangedItemsResponseNode = soapBodyNode.getFirstChild();
			Node updatedItemsNode            = getChangedItemsResponseNode.getChildNodes().item(4); // 5th element of getChangedItemsResponseNode
			Node responseStatusNode          = getChangedItemsResponseNode.getChildNodes().item(0).getChildNodes().item(0);
			//TODO: use the responseStatusNode to give diagnostic errors
			String totalItems                = responseStatusNode.getChildNodes().item(3).getTextContent();

//			Node createdItemsNode            = getChangedItemsResponseNode.getChildNodes().item(3); // 4th element of getChangedItemsResponseNode
//			Node deletedItemsNode            = getChangedItemsResponseNode.getChildNodes().item(5); // 6th element of getChangedItemsResponseNode

			// These will be re-used
			NodeList walkThroughMe;
			int l;

			// Updated Items
			walkThroughMe = updatedItemsNode.getChildNodes();
			l = walkThroughMe.getLength();
			updatedItemIDs = new String[l];
			for (int i = 0; i < l; i++) {
				updatedItemIDs[i] = walkThroughMe.item(i).getTextContent();
			}

			// TODO: Process Created Items in the future
//			// Created Items
//			walkThroughMe = createdItemsNode.getChildNodes();
//			l = walkThroughMe.getLength();
//			createdItemIDs = new String[l];
//			for (int i = 0; i < l; i++) {
//				createdItemIDs[i] = walkThroughMe.item(i).getTextContent();
//			}

			// TODO: Process Deleted Items in the future
//			// Deleted Items
//			walkThroughMe = deletedItemsNode.getChildNodes();
//			l = walkThroughMe.getLength();
//			deletedItemIDs = new String[l];
//			for (int i = 0; i < l; i++) {
//				deletedItemIDs[i] = walkThroughMe.item(i).getTextContent();
//			}

		} catch (Exception e) {
			logger.error("Error Parsing SOAP Response", e);
		}

		// Fetch Item Information
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
			if (updatedItemIDs.length > 0) {
				//TODO: Set an upper limit on number of IDs for one request, and process in batches
				getItemInformationSoapRequest = getItemInformationSoapRequestStart;
				// Updated Items
				for (String updatedItem : updatedItemIDs) {
					getItemInformationSoapRequest += "<mar:ItemSearchTerm>" + updatedItem + "</mar:ItemSearchTerm>\n";
				}
				getItemInformationSoapRequest += getItemInformationSoapRequestEnd;

				URLPostResponse ItemInformationSOAPResponse = postToURL(marcOutURL, getItemInformationSoapRequest, "text/xml", null, logger);

				// Parse Response
				Document doc = createXMLDocumentForSoapResponse(ItemInformationSOAPResponse);

				// Navigate Down Soap Response
				Node soapEnvelopeNode               = doc.getFirstChild();
				Node soapBodyNode                   = soapEnvelopeNode.getLastChild();
				Node getItemInformationResponseNode = soapBodyNode.getFirstChild();
				NodeList ItemStatuses               = getItemInformationResponseNode.getChildNodes();
//				Node itemInformationResponseNodeResponseStatus = getItemInformationResponseNode.getFirstChild();
				// TODO: Read for errors

				int l = ItemStatuses.getLength();
				for (int i = 1;i < l; i++) {
					// start with i = 1 to skip first node, because that is the response status node and not an item status
					Node itemStatus = ItemStatuses.item(i);
					ItemChangeInfo currentItem = new ItemChangeInfo();

					NodeList itemDetails = itemStatus.getChildNodes();
					int dl = itemDetails.getLength();
					for (int j = 0;j < dl; j++) {
						Node detail        = itemDetails.item(j);
						String detailName  = detail.getNodeName();
						String detailValue = detail.getTextContent();

						detailName = detailName.replaceFirst("ns4:", ""); // strip out namespace prefix

						// Handle each detail
						switch (detailName) {
							case "BID" :
								currentItem.setBID(detailValue); // TODO: Or add CARL prefix here?
								break;
							case "ItemID" :
								currentItem.setItemId(detailValue);
								break;
							case "LocationCode" :
								currentItem.setLocation(detailValue);
								//TODO: use Branch Number ??
								break;
							case "Status" :
								//TODO: Reverse Translate; I need a new map
								currentItem.setStatus(detailValue);
								break;
							case "DueDate" :
								currentItem.setDueDate(detailValue);
								break;
							case "LastCheckinDate" :
								// There is no LastCheckinDate field in ItemInformation Call
								currentItem.setLastCheckinDate(detailValue);
								break;
						}
					}
					// TODO: Write Item information into marc record
					updateMarc(individualMarcPath, currentItem.getBID(), currentItem);
				}


			}

		} catch (Exception e) {
			logger.error("Error Creating SOAP Call", e);

		}

		if (vufindConn != null){
			try{
				//Close the connection
				vufindConn.close();
			}catch(Exception e){
				System.out.println("Error closing connection: " + e.toString());
				e.printStackTrace();
			}
		}
		Date currentTime = new Date();
		logger.info(currentTime.toString() + ": Finished CarlX Extract");



	}

	private static void updateMarc(String individualMarcPath, String curBibId, ItemChangeInfo itemChangeInfo) {
		//Load the existing marc record from file
		try {
			File marcFile = getFileForIlsRecord(individualMarcPath, curBibId);
			if (marcFile.exists()) {
				FileInputStream inputStream = new FileInputStream(marcFile);
				MarcPermissiveStreamReader marcReader = new MarcPermissiveStreamReader(inputStream, true, true, "UTF-8");
				if (marcReader.hasNext()) {
					Record marcRecord = marcReader.next();
					inputStream.close();

					//Loop through all item fields to see what has changed
					List<VariableField> itemFields = marcRecord.getVariableFields(itemTag);
					for (VariableField itemFieldVar : itemFields) {
						DataField itemField = (DataField) itemFieldVar;
						if (itemField.getSubfield(itemRecordNumberSubfield) != null) {
							String itemRecordNumber = itemField.getSubfield(itemRecordNumberSubfield).getData();
							//Update the items
//							for (ItemChangeInfo curItem : itemChangeInfo) {
							ItemChangeInfo curItem = itemChangeInfo;
								//Find the correct item
								if (itemRecordNumber.equals(curItem.getItemId())) {
									itemField.getSubfield(locationSubfield).setData(curItem.getLocation());
									itemField.getSubfield(statusSubfield).setData(curItem.getStatus());
									if (curItem.getDueDate() == null) {
										if (itemField.getSubfield(dueDateSubfield) != null) {
											if (dueDateFormat.contains("-")){
												itemField.getSubfield(dueDateSubfield).setData("  -  -  ");
											} else {
												itemField.getSubfield(dueDateSubfield).setData("      ");
											}
										}
									} else {
										if (itemField.getSubfield(dueDateSubfield) == null) {
											itemField.addSubfield(new SubfieldImpl(dueDateSubfield, curItem.getDueDate()));
										} else {
											itemField.getSubfield(dueDateSubfield).setData(curItem.getDueDate());
										}
									}
									if (lastCheckInSubfield != ' ') {
										if (curItem.getLastCheckinDate() == null) {
											if (itemField.getSubfield(lastCheckInSubfield) != null) {
												if (lastCheckInFormat.contains("-")) {
													itemField.getSubfield(lastCheckInSubfield).setData("  -  -  ");
												} else {
													itemField.getSubfield(lastCheckInSubfield).setData("      ");
												}
											}
										} else {
											if (itemField.getSubfield(lastCheckInSubfield) == null) {
												itemField.addSubfield(new SubfieldImpl(lastCheckInSubfield, curItem.getLastCheckinDate()));
											} else {
												itemField.getSubfield(lastCheckInSubfield).setData(curItem.getLastCheckinDate());
											}
										}
									}
								}
//							} // end of for loop
						}
					}

					//Write the new marc record
//					MarcWriter writer = new MarcStreamWriter(new FileOutputStream(marcFile, false));
//					writer.write(marcRecord);
//					writer.close();
//					TODO: uncomment above.
				} else {
					logger.info("Could not read marc record for " + curBibId + " the bib was empty");
				}
			}else{
				logger.debug("Marc Record does not exist for " + curBibId + " it is not part of the main extract yet.");
			}
		}catch (Exception e){
			logger.error("Error updating marc record for bib " + curBibId, e);
		}
	}

	// Array of ItemChangeInfo, the above version will be for a single item
	private static void updateMarc(String individualMarcPath, String curBibId, ArrayList<ItemChangeInfo> itemChangeInfo) {
		//Load the existing marc record from file
		try {
			File marcFile = getFileForIlsRecord(individualMarcPath, curBibId);
			if (marcFile.exists()) {
				FileInputStream inputStream = new FileInputStream(marcFile);
				MarcPermissiveStreamReader marcReader = new MarcPermissiveStreamReader(inputStream, true, true, "UTF-8");
				if (marcReader.hasNext()) {
					Record marcRecord = marcReader.next();
					inputStream.close();

					//Loop through all item fields to see what has changed
					List<VariableField> itemFields = marcRecord.getVariableFields(itemTag);
					for (VariableField itemFieldVar : itemFields) {
						DataField itemField = (DataField) itemFieldVar;
						if (itemField.getSubfield(itemRecordNumberSubfield) != null) {
							String itemRecordNumber = itemField.getSubfield(itemRecordNumberSubfield).getData();
							//Update the items
							for (ItemChangeInfo curItem : itemChangeInfo) {
								//Find the correct item
								if (itemRecordNumber.equals(curItem.getItemId())) {
									itemField.getSubfield(locationSubfield).setData(curItem.getLocation());
									itemField.getSubfield(statusSubfield).setData(curItem.getStatus());
									if (curItem.getDueDate() == null) {
										if (itemField.getSubfield(dueDateSubfield) != null) {
											if (dueDateFormat.contains("-")){
												itemField.getSubfield(dueDateSubfield).setData("  -  -  ");
											} else {
												itemField.getSubfield(dueDateSubfield).setData("      ");
											}
										}
									} else {
										if (itemField.getSubfield(dueDateSubfield) == null) {
											itemField.addSubfield(new SubfieldImpl(dueDateSubfield, curItem.getDueDate()));
										} else {
											itemField.getSubfield(dueDateSubfield).setData(curItem.getDueDate());
										}
									}
									if (lastCheckInSubfield != ' ') {
										if (curItem.getLastCheckinDate() == null) {
											if (itemField.getSubfield(lastCheckInSubfield) != null) {
												if (lastCheckInFormat.contains("-")) {
													itemField.getSubfield(lastCheckInSubfield).setData("  -  -  ");
												} else {
													itemField.getSubfield(lastCheckInSubfield).setData("      ");
												}
											}
										} else {
											if (itemField.getSubfield(lastCheckInSubfield) == null) {
												itemField.addSubfield(new SubfieldImpl(lastCheckInSubfield, curItem.getLastCheckinDate()));
											} else {
												itemField.getSubfield(lastCheckInSubfield).setData(curItem.getLastCheckinDate());
											}
										}
									}
								}
							}
						}
					}

					//Write the new marc record
					MarcWriter writer = new MarcStreamWriter(new FileOutputStream(marcFile, false));
					writer.write(marcRecord);
					writer.close();
				} else {
					logger.info("Could not read marc record for " + curBibId + " the bib was empty");
				}
			}else{
				logger.debug("Marc Record does not exist for " + curBibId + " it is not part of the main extract yet.");
			}
		}catch (Exception e){
			logger.error("Error updating marc record for bib " + curBibId, e);
		}
	}

	private static File getFileForIlsRecord(String individualMarcPath, String recordNumber) {
		String shortId = getFileIdForRecordNumber(recordNumber);
//		String firstChars = shortId.substring(0, 4);
//		TODO: individual marc record folder creation needs adjusting for CarlX
		String firstChars = "CARL";
		String basePath = individualMarcPath + "/" + firstChars;
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


	/**
	 * I used this to initially walk through SOAP document. It can be deleted and removed.
	 * @param nodeList
	 */
	private static void printNote(NodeList nodeList) {

		for (int count = 0; count < nodeList.getLength(); count++) {

			Node tempNode = nodeList.item(count);

			// make sure it's element node.
			if (tempNode.getNodeType() == Node.ELEMENT_NODE) {

				// get node name and value
				System.out.println("\nNode Name =" + tempNode.getNodeName() + " [OPEN]");
				System.out.println("Node Value =" + tempNode.getTextContent());

				if (tempNode.hasAttributes()) {

					// get attributes names and values
					NamedNodeMap nodeMap = tempNode.getAttributes();

					for (int i = 0; i < nodeMap.getLength(); i++) {

						Node node = nodeMap.item(i);
						System.out.println("attr name : " + node.getNodeName());
						System.out.println("attr value : " + node.getNodeValue());

					}

				}

				if (tempNode.hasChildNodes()) {

					// loop again if has child nodes
					printNote(tempNode.getChildNodes());

				}

				System.out.println("Node Name =" + tempNode.getNodeName() + " [CLOSE]");

			}
		}
	}

}
