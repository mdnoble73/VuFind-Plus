package org.marmot.pika;

import java.io.*;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.sql.*;
import java.text.DateFormat;
import java.text.SimpleDateFormat;
import java.util.Date;
import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile;
import org.ini4j.Profile.Section;
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

		//Get the Indexing Profile from the database
		try {
			PreparedStatement getCarlXIndexingProfileStmt = vufindConn.prepareStatement("SELECT * FROM indexing_profiles where name ='ils'");
			ResultSet carlXIndexingProfileRS = getCarlXIndexingProfileStmt.executeQuery();
			if (carlXIndexingProfileRS.next()) {
				String carlXExportPath          = carlXIndexingProfileRS.getString("marcPath");
//				String filenamesToInclude      = carlXIndexingProfileRS.getString("filenamesToInclude");
				String individualMarcPath       = carlXIndexingProfileRS.getString("individualMarcPath");
//				String groupingClass           = carlXIndexingProfileRS.getString("groupingClass");
				String recordNumberTag          = carlXIndexingProfileRS.getString("recordNumberTag");
				String recordNumberPrefix       = carlXIndexingProfileRS.getString("recordNumberPrefix");
//				String marcEncoding            = carlXIndexingProfileRS.getString("marcEncoding");
				String itemTag                  = carlXIndexingProfileRS.getString("itemTag");
				String itemRecordNumberSubfield = carlXIndexingProfileRS.getString("itemRecordNumber");
				String callNumberSubfield       = carlXIndexingProfileRS.getString("callNumber");
				String itemBarcodeSubfield      = carlXIndexingProfileRS.getString("barcode");
				String itemStatusSubfield       = carlXIndexingProfileRS.getString("status");
				String dueDateSubfield          = carlXIndexingProfileRS.getString("dueDate");
				// empty in profile.
				String lastCheckinDateSubfield  = carlXIndexingProfileRS.getString("lastCheckinDate");
				String locationSubfield         = carlXIndexingProfileRS.getString("location");
				String shelvingLocationSubfield = carlXIndexingProfileRS.getString("shelvingLocation");
				String collectionSubfield       = carlXIndexingProfileRS.getString("collection");
				// shelvingLocation & collection sub fields are the same in the sandbox

			} else {
				logger.error("Unable to find carlx indexing profile, please create a profile with the name ils.");
			}
		}catch (Exception e){
			logger.error("Error reading index profile for CarlX", e);
		}


		// Get MarcOut WSDL url for SOAP calls
		String marcOutURL = ini.get("Catalog", "marcOutApiWsdl");

//		TODO: determine actual date-time to fetch record changes from.
		DateFormat BeginTimeFormat = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss");
//		TODO: timezones??
		Date BeginTime = new Date();
//		TODO: Look up actual cut off time
		String BeginTimeString = BeginTimeFormat.format(BeginTime);

		BeginTimeString = "2013-12-31T12:00:00";
		// Use the value from the Example for now

/*  Example Call
		<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:mar="http://tlcdelivers.com/cx/schemas/marcoutAPI" xmlns:req="http://tlcdelivers.com/cx/schemas/request">
		<soapenv:Header/>
		<soapenv:Body>
		<mar:GetChangedItemsRequest>
		<mar:BeginTime>2013-12-31T12:00:00</mar:BeginTime>
		<mar:Modifiers/>
		</mar:GetChangedItemsRequest>
		</soapenv:Body>
		</soapenv:Envelope>
*/
		String exampleSoapRequest = "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:mar=\"http://tlcdelivers.com/cx/schemas/marcoutAPI\" xmlns:req=\"http://tlcdelivers.com/cx/schemas/request\">\n" +
				"<soapenv:Header/>\n" +
				"<soapenv:Body>\n" +
				"<mar:GetChangedItemsRequest>\n" +
				"<mar:BeginTime>"+ BeginTimeString + "</mar:BeginTime>\n" +
				"<mar:Modifiers/>\n" +
				"</mar:GetChangedItemsRequest>\n" +
				"</soapenv:Body>\n" +
				"</soapenv:Envelope>";

		URLPostResponse SOAPResponse = postToURL(marcOutURL, exampleSoapRequest, "text/xml", null, logger);

//		TODO: Parse & Process Response
		DocumentBuilderFactory dbFactory = DocumentBuilderFactory.newInstance();
		DocumentBuilder dBuilder = null;
		Document doc;
		try {
			dBuilder = dbFactory.newDocumentBuilder();

			byte[]                soapResponseByteArray            = SOAPResponse.getMessage().getBytes("utf-8");
			ByteArrayInputStream  soapResponseByteArrayInputStream = new ByteArrayInputStream(soapResponseByteArray);
			InputSource           soapResponseInputSource          = new InputSource(soapResponseByteArrayInputStream);

			doc = dBuilder.parse(soapResponseInputSource);
			doc.getDocumentElement().normalize();

			// Navigate Down Soap Response
//			Node soapEnvelopeNode            = doc.getChildNodes().item(0);
			Node soapEnvelopeNode            = doc.getFirstChild();
//			Node soapBodyNode                = soapEnvelopeNode.getChildNodes().item(1);
			Node soapBodyNode                = soapEnvelopeNode.getLastChild();
//			Node getChangedItemsResponseNode = soapBodyNode.getChildNodes().item(0);
			Node getChangedItemsResponseNode = soapBodyNode.getFirstChild();
			Node createdItemsNode            = getChangedItemsResponseNode.getChildNodes().item(3); // 4th element of getChangedItemsResponseNode
			Node updatedItemsNode            = getChangedItemsResponseNode.getChildNodes().item(4); // 5th element of getChangedItemsResponseNode
			Node deletedItemsNode            = getChangedItemsResponseNode.getChildNodes().item(5); // 6th element of getChangedItemsResponseNode
			String totalItems                = getChangedItemsResponseNode.getChildNodes().item(0).getChildNodes().item(2).getTextContent();


			// These will be re-used
			NodeList walkThroughme;
			int l;

			// Created Items
			walkThroughme = createdItemsNode.getChildNodes();
			l = walkThroughme.getLength();
			String[] createdItemIDs = new String[l];
			for (int i = 0; i < l; i++) {
				createdItemIDs[i] = walkThroughme.item(i).getTextContent();
			}
//			printNote(doc.getElementsByTagNameNS("ns3", "CreatedItems"));
//		parent: doc.getChildNodes().item(0).getChildNodes().item(1).getChildNodes().item(0).getChildNodes().item(3)

			// Updated Items
			walkThroughme = updatedItemsNode.getChildNodes();
			l = walkThroughme.getLength();
			String[] updatedItemIDs = new String[l];
			for (int i = 0; i < l; i++) {
				updatedItemIDs[i] = walkThroughme.item(i).getTextContent();
			}
//			printNote(doc.getElementsByTagNameNS("ns3", "UpdatedItems"));
			//parent: doc.getChildNodes().item(0).getChildNodes().item(1).getChildNodes().item(0).getChildNodes().item(4)
			// doc.getChildNodes().item(0).getChildNodes().item(1).getChildNodes().item(0).getChildNodes().item(4).getChildNodes().item(0).getTextContent()

			// Deleted Items
			walkThroughme = deletedItemsNode.getChildNodes();
			l = walkThroughme.getLength();
			String[] deletedItemIDs = new String[l];
			for (int i = 0; i < l; i++) {
				deletedItemIDs[i] = walkThroughme.item(i).getTextContent();
			}
//			printNote(doc.getElementsByTagNameNS("ns3", "DeletedItems"));
			//parent: doc.getChildNodes().item(0).getChildNodes().item(1).getChildNodes().item(0).getChildNodes().item(5)


//			printNote(doc.getChildNodes());


		} catch (Exception e) {
			logger.error("Error Parsing SOAP Response", e);
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
