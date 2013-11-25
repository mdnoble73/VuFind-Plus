package org.vufind;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile.Section;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.Date;


/**
 * Reindex Grouped Records for display within VuFind
 * 
 * @author Mark Noble <mark@marmot.org>
 * 
 */
public class ReindexProcess {

	private static Logger logger	= Logger.getLogger(ReindexProcess.class);

	//General configuration
	private static String serverName;
	private static String indexSettings;
	private static Ini configIni;
	private static String solrPort;
	
	//Reporting information
	private static long reindexLogId;
	private static long startTime;
	private static long endTime;
	private static Long reindexTime1 = null;
	private static Long reindexTime2 = null;
	
	private static long loadChangesSince = 0;

	//Variables to determine what sub processes to run.
	private static boolean reloadDefaultSchema = false;
	private static String idsToProcess = null;

	//Database connections and prepared statements
	private static Connection vufindConn = null;
	private static Connection econtentConn = null;
	
	private static PreparedStatement updateCronLogLastUpdatedStmt;
	private static PreparedStatement addNoteToCronLogStmt;

	private static PreparedStatement getOverDriveProductStmt;

	/**
	 * Starts the re-indexing process
	 * 
	 * @param args String[] The server name to index with optional parameter for properties of indexing
	 */
	public static void main(String[] args) {
		startTime = new Date().getTime();
		// Get the configuration filename
		if (args.length == 0) {
			System.out.println("Please enter the server to index as the first parameter");
			System.exit(1);
		}
		serverName = args[0];
		System.setProperty("reindex.process.serverName", serverName);
		
		if (args.length > 1){
			indexSettings = args[1];
		}
		
		initializeReindex();
		
		addNoteToCronLog("Initialized Reindex " + indexSettings);
		
		//Reload schemas
		if (reloadDefaultSchema){
			reloadDefaultSchemas();
		}
		
		//Process records from all sources
		try {
			//Process ILS records
			ILSIndexer ilsIndexer = new ILSIndexer();

			//Process OverDrive records

			//Other sources
		} catch (Error e) {
			logger.error("Error processing reindex ", e);
			addNoteToCronLog("Error processing reindex " + e.toString());
		}
		
		// Send completion information
		endTime = new Date().getTime();
		sendCompletionMessage();
		
		try {
			//Update the reindex times to indicate that a new reindex is starting
			if (reindexTime2 != null){
				vufindConn.prepareStatement("UPDATE variables set value = '" + reindexTime1 + "' WHERE name = 'reindex_time_2'").executeUpdate();
				vufindConn.prepareStatement("UPDATE variables set value = '" + (startTime / 1000) + "' WHERE name = 'reindex_time_1'").executeUpdate();
			}else if (reindexTime1 != null){
				vufindConn.prepareStatement("INSERT INTO variables set value = '" + reindexTime1 + "', name = 'reindex_time_2'").executeUpdate();
				vufindConn.prepareStatement("UPDATE variables set value = '" + (startTime / 1000) + "' WHERE name = 'reindex_time_1'").executeUpdate();
			}else{
				vufindConn.prepareStatement("INSERT INTO variables set value = '" + (startTime / 1000) + "', name = 'reindex_time_1'").executeUpdate();
			}
		} catch (SQLException e) {
			addNoteToCronLog("Error updating reindex times in database " + e.toString());
		}

		addNoteToCronLog("Finished Reindex for " + serverName);
		logger.info("Finished Reindex for " + serverName);
	}
	
	private static void reloadDefaultSchemas() {
		logger.info("Reloading schemas from default");
		try {
			//Synonyms
			logger.debug("Copying " + "../../sites/default/solr/biblio/conf/synonyms.txt" + " to " + "../../sites/default/solr/grouped/conf/synonyms.txt");
			if (!Util.copyFile(new File("../../sites/default/solr/biblio/conf/synonyms.txt"), new File("../../sites/default/solr/grouped/conf/synonyms.txt"))){
				logger.info("Unable to copy synonyms.txt to biblio2");
				addNoteToCronLog("Unable to copy synonyms.txt to biblio2");
			}
		} catch (IOException e) {
			logger.error("error reloading copying default scehmas", e);
			addNoteToCronLog("error reloading copying default scehmas " + e.toString());
		}
		//grouped
		reloadSchema("grouped");
		//genealogy
		reloadSchema("genealogy");
	}

	private static void reloadSchema(String schemaName) {
		boolean reloadIndex = true;
		addNoteToCronLog("Reloading Schema " + schemaName);
		try {
			logger.debug("Copying " + "../../sites/default/solr/" + schemaName + "/conf/schema.xml" + " to " + "../../sites/" + serverName + "/solr/" + schemaName + "/conf/schema.xml");
			if (!Util.copyFile(new File("../../sites/default/solr/" + schemaName + "/conf/schema.xml"), new File("../../sites/" + serverName + "/solr/" + schemaName + "/conf/schema.xml"))){
				logger.info("Unable to copy schema for " + schemaName);
				addNoteToCronLog("Unable to copy schema for " + schemaName);
				reloadIndex = false;
			}
			logger.debug("Copying " + "../../sites/default/solr/" + schemaName + "/conf/mapping-FoldToASCII.txt" + " to " + "../../sites/" + serverName + "/solr/" + schemaName + "/conf/mapping-FoldToASCII.txt");
			if (!Util.copyFile(new File("../../sites/default/solr/" + schemaName + "/conf/mapping-FoldToASCII.txt"), new File("../../sites/" + serverName + "/solr/" + schemaName + "/conf/mapping-FoldToASCII.txt"))){
				logger.info("Unable to copy mapping-FoldToASCII.txt for " + schemaName);
				addNoteToCronLog("Unable to copy mapping-FoldToASCII.txt for " + schemaName);
			}
			logger.debug("Copying " + "../../sites/default/solr/" + schemaName + "/conf/mapping-ISOLatin1Accent.txt" + " to " + "../../sites/" + serverName + "/solr/" + schemaName + "/conf/mapping-ISOLatin1Accent.txt");
			if (!Util.copyFile(new File("../../sites/default/solr/" + schemaName + "/conf/mapping-ISOLatin1Accent.txt"), new File("../../sites/" + serverName + "/solr/" + schemaName + "/conf/mapping-ISOLatin1Accent.txt"))){
				logger.info("Unable to copy mapping-ISOLatin1Accent.txt for " + schemaName);
				addNoteToCronLog("Unable to copy mapping-ISOLatin1Accent.txt for " + schemaName);
			}
			logger.debug("Copying " + "../../sites/default/solr/" + schemaName + "/conf/synonyms.txt" + " to " + "../../sites/" + serverName + "/solr/" + schemaName + "/conf/synonyms.txt");
			if (!Util.copyFile(new File("../../sites/default/solr/" + schemaName + "/conf/synonyms.txt"), new File("../../sites/" + serverName + "/solr/" + schemaName + "/conf/synonyms.txt"))){
				logger.info("Unable to copy mapping-ISOLatin1Accent.txt for " + schemaName);
				addNoteToCronLog("Unable to copy mapping-ISOLatin1Accent.txt for " + schemaName);
			}
			logger.debug("Copying " + "../../sites/default/solr/" + schemaName + "/conf/solrconfig.xml" + " to " + "../../sites/" + serverName + "/solr/" + schemaName + "/conf/solrconfig.xml");
			if (!Util.copyFile(new File("../../sites/default/solr/" + schemaName + "/conf/solrconfig.xml"), new File("../../sites/" + serverName + "/solr/" + schemaName + "/conf/solrconfig.xml"))){
				logger.info("Unable to copy solrconfig.xml for " + schemaName);
				addNoteToCronLog("Unable to copy solrconfig.xml for " + schemaName);
			}
		} catch (IOException e) {
			logger.error("error reloading default schema for " + schemaName, e);
			addNoteToCronLog("error reloading default schema for " + schemaName + " " + e.toString());
			reloadIndex = false;
		}
		if (reloadIndex){
			URLPostResponse response = Util.getURL("http://localhost:" + solrPort + "/solr/admin/cores?action=RELOAD&core=" + schemaName, logger);
			if (!response.isSuccess()){
				logger.error("Error reloading default schema for " + schemaName + " " + response.getMessage());
				addNoteToCronLog("Error reloading default schema for " + schemaName + " " + response.getMessage());
			}
		}
	}

	private static StringBuffer reindexNotes = new StringBuffer();
	private static SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
	public static void addNoteToCronLog(String note) {
		try {
			Date date = new Date();
			reindexNotes.append("<br>").append(dateFormat.format(date)).append(note);
			addNoteToCronLogStmt.setString(1, Util.trimTo(65535, reindexNotes.toString()));
			addNoteToCronLogStmt.setLong(2, new Date().getTime() / 1000);
			addNoteToCronLogStmt.setLong(3, reindexLogId);
			addNoteToCronLogStmt.executeUpdate();
		} catch (SQLException e) {
			logger.error("Error adding note to Reindex Log", e);
		}
	}
	
	public static void updateLastUpdateTime(){
		try {
			updateCronLogLastUpdatedStmt.setLong(1, new Date().getTime() / 1000);
			updateCronLogLastUpdatedStmt.setLong(2, reindexLogId);
			updateCronLogLastUpdatedStmt.executeUpdate();
			//Sleep for a little bit to make sure we don't block connectivity for other programs 
			Thread.sleep(5);
			//Thread.yield();
		} catch (SQLException e) {
			logger.error("Error setting last updated time in Cron Log", e);
		} catch (InterruptedException e) {
			logger.error("Sleep interrupted", e);
		}
	}

	private static void initializeReindex() {
		// Delete the existing reindex.log file
		File solrmarcLog = new File("../../sites/" + serverName + "/logs/reindex.log");
		if (solrmarcLog.exists()){
			solrmarcLog.delete();
		}
		for (int i = 1; i <= 10; i++){
			solrmarcLog = new File("../../sites/" + serverName + "/logs/reindex.log." + i);
			if (solrmarcLog.exists()){
				solrmarcLog.delete();
			}
		}
		solrmarcLog = new File("org.solrmarc.log");
		if (solrmarcLog.exists()){
			solrmarcLog.delete();
		}
		for (int i = 1; i <= 4; i++){
			solrmarcLog = new File("org.solrmarc.log." + i);
			if (solrmarcLog.exists()){
				solrmarcLog.delete();
			}
		}
		
		// Initialize the logger
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.reindex.properties");
		if (log4jFile.exists()) {
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		} else {
			System.out.println("Could not find log4j configuration " + log4jFile.getAbsolutePath());
			System.exit(1);
		}
		
		logger.info("Starting Reindex for " + serverName);

		// Parse the configuration file
		configIni = loadConfigFile("config.ini");
		
		if (indexSettings != null){
			logger.info("Loading index settings from override file " + indexSettings);
			String indexSettingsName = "../../sites/" + serverName + "/conf/" + indexSettings + ".ini";
			File indexSettingsFile = new File(indexSettingsName);
			if (!indexSettingsFile.exists()) {
				indexSettingsName = "../../sites/default/conf/" + indexSettings + ".ini";
				indexSettingsFile = new File(indexSettingsName);
				if (!indexSettingsFile.exists()) {
					logger.error("Could not find indexSettings file " + indexSettings);
					System.exit(1);
				}
			}
			try {
				Ini indexSettingsIni = new Ini();
				indexSettingsIni.load(new FileReader(indexSettingsFile));
				for (Section curSection : indexSettingsIni.values()){
					for (String curKey : curSection.keySet()){
						logger.debug("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
						//System.out.println("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
						configIni.put(curSection.getName(), curKey, curSection.get(curKey));
					}
				}
			} catch (InvalidFileFormatException e) {
				logger.error("IndexSettings file is not valid.  Please check the syntax of the file.", e);
			} catch (IOException e) {
				logger.error("IndexSettings file could not be read.", e);
			}
		}
		
		solrPort = configIni.get("Reindex", "solrPort");
		if (solrPort == null || solrPort.length() == 0) {
			logger.error("You must provide the port where the solr index is loaded in the import configuration file");
			System.exit(1);
		}
		
		String reloadDefaultSchemaStr = configIni.get("Reindex", "reloadDefaultSchema");
		if (reloadDefaultSchemaStr != null){
			reloadDefaultSchema = Boolean.parseBoolean(reloadDefaultSchemaStr);
		}

		logger.info("Setting up database connections");
		//Setup connections to vufind and econtent databases
		String databaseConnectionInfo = Util.cleanIniValue(configIni.get("Database", "database_vufind_jdbc"));
		if (databaseConnectionInfo == null || databaseConnectionInfo.length() == 0) {
			logger.error("VuFind Database connection information not found in Database Section.  Please specify connection information in database_vufind_jdbc.");
			System.exit(1);
		}
		try {
			vufindConn = DriverManager.getConnection(databaseConnectionInfo);
			//Load the last index times
			ResultSet reindexTime1RS = vufindConn.prepareStatement("SELECT * from variables where name = 'reindex_time_1'").executeQuery();
			if (reindexTime1RS.next()){
				reindexTime1 = reindexTime1RS.getLong("value");
			}
			ResultSet reindexTime2RS = vufindConn.prepareStatement("SELECT * from variables where name = 'reindex_time_2'").executeQuery();
			if (reindexTime2RS.next()){
				reindexTime2 = reindexTime2RS.getLong("value");
			}
			if (reindexTime2 != null){
				loadChangesSince = reindexTime2;
			}
		} catch (SQLException e) {
			logger.error("Could not connect to vufind database", e);
			System.exit(1);
		}
		
		
		String econtentDBConnectionInfo = Util.cleanIniValue(configIni.get("Database", "database_econtent_jdbc"));
		if (econtentDBConnectionInfo == null || econtentDBConnectionInfo.length() == 0) {
			logger.error("Database connection information for eContent database not found in Database Section.  Please specify connection information as database_econtent_jdbc key.");
			System.exit(1);
		}
		try {
			econtentConn = DriverManager.getConnection(econtentDBConnectionInfo);
			getOverDriveProductStmt = econtentConn.prepareStatement("SELECT id, dateAdded, GREATEST(dateUpdated, lastMetadataChange, lastAvailabilityChange, dateDeleted) as dateUpdated, deleted from overdrive_api_products where overdriveId = ?");
		} catch (SQLException e) {
			logger.error("Could not connect to econtent database", e);
			System.exit(1);
		}
		
		//Start a reindex log entry 
		try {
			logger.info("Creating log entry for index");
			PreparedStatement createLogEntryStatement = vufindConn.prepareStatement("INSERT INTO reindex_log (startTime, lastUpdate, notes) VALUES (?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			createLogEntryStatement.setLong(1, new Date().getTime() / 1000);
			createLogEntryStatement.setLong(2, new Date().getTime() / 1000);
			createLogEntryStatement.setString(3, "Initialization complete");
			createLogEntryStatement.executeUpdate();
			ResultSet generatedKeys = createLogEntryStatement.getGeneratedKeys();
			if (generatedKeys.next()){
				reindexLogId = generatedKeys.getLong(1);
			}
			
			updateCronLogLastUpdatedStmt = vufindConn.prepareStatement("UPDATE reindex_log SET lastUpdate = ? WHERE id = ?");
			addNoteToCronLogStmt = vufindConn.prepareStatement("UPDATE reindex_log SET notes = ?, lastUpdate = ? WHERE id = ?");
		} catch (SQLException e) {
			logger.error("Unable to create log entry for reindex process", e);
			System.exit(0);
		}
		
		idsToProcess = Util.cleanIniValue(configIni.get("Reindex", "idsToProcess"));
		if (idsToProcess == null || idsToProcess.length() == 0){
			idsToProcess = null;
			logger.debug("Did not load a set of idsToProcess");
		}else{
			logger.debug("idsToProcess = " + idsToProcess);
		}
		
	}
	
	private static void sendCompletionMessage(){
		long elapsedTime = endTime - startTime;
		float elapsedMinutes = (float)elapsedTime / (float)(60000); 
		logger.info("Time elpased: " + elapsedMinutes + " minutes");
		
		try {
			PreparedStatement finishedStatement = vufindConn.prepareStatement("UPDATE reindex_log SET endTime = ? WHERE id = ?");
			finishedStatement.setLong(1, new Date().getTime() / 1000);
			finishedStatement.setLong(2, reindexLogId);
			finishedStatement.executeUpdate();
		} catch (SQLException e) {
			logger.error("Unable to update reindex log with completion time.", e);
		}
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
			for (Section curSection : siteSpecificIni.values()){
				for (String curKey : curSection.keySet()){
					//logger.debug("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					//System.out.println("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					ini.put(curSection.getName(), curKey, curSection.get(curKey));
				}
			}
		} catch (InvalidFileFormatException e) {
			logger.error("Site Specific config file is not valid.  Please check the syntax of the file.", e);
		} catch (IOException e) {
			logger.error("Site Specific config file could not be read.", e);
		}
		return ini;
	}
	
	public static long getLoadChangesSince() {
		return loadChangesSince;
	}
}
