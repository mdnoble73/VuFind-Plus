package org.marmot.pika;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.Date;
import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile;
import org.ini4j.Profile.Section;

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

//		TODO: call APIs for records that have changed since last time.

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
		String exampleSoapRequest = "\t\t<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:mar=\"http://tlcdelivers.com/cx/schemas/marcoutAPI\" xmlns:req=\"http://tlcdelivers.com/cx/schemas/request\">\n" +
				"\t\t<soapenv:Header/>\n" +
				"\t\t<soapenv:Body>\n" +
				"\t\t<mar:GetChangedItemsRequest>\n" +
				"\t\t<mar:BeginTime>2013-12-31T12:00:00</mar:BeginTime>\n" +
				"\t\t<mar:Modifiers/>\n" +
				"\t\t</mar:GetChangedItemsRequest>\n" +
				"\t\t</soapenv:Body>\n" +
				"\t\t</soapenv:Envelope>";

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


}
