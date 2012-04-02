package org.vufind;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.FileReader;
import java.io.FilenameFilter;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.MalformedURLException;
import java.net.URL;
import java.net.URLConnection;
import java.nio.channels.FileChannel;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashSet;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.econtent.ExtractEContentFromMarc;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.strands.StrandsProcessor;


/**
 * Runs the nightly reindex process to update solr index based on the latest
 * export from the ILS.
 * 
 * Reindex process does the following steps: 
 * 1) Runs export process to extract
 * marc records from the ILS (if applicable) 
 * 
 * @author Mark Noble <mnoble@turningleaftech.com>
 * 
 */
public class ReindexProcess {

	private static Logger logger	= Logger.getLogger(ReindexProcess.class);

	//General configuration
	private static String serverName;
	private static Ini configIni;
	private static String solrPort;
	
	//Reporting information
	private static long reindexLogId;
	private static int numSevereErrors = 0;
	private static HashSet<String> distinctSevereErrors = new HashSet<String>(); 
	private static int numErrors = 0;
	private static HashSet<String> distinctErrors = new HashSet<String>();
	private static int numAdded = 0;
	private static int numExceptions = 0;
	private static HashSet<String> distinctExceptions = new HashSet<String>();
	private static long startTime;
	private static long endTime;
	
	//Variables to determine what sub processes to run. 
	private static boolean updateSolr = true;
	private static boolean updateResources = true;
	private static boolean loadEContentFromMarc = false;
	private static boolean exportStrandsCatalog = false;
	private static boolean exportOPDSCatalog = true;
	
	//Database connections and prepared statements
	private static Connection vufindConn = null;
	private static Connection econtentConn = null;
	
	/**
	 * Starts the reindexing process
	 * 
	 * @param args
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
		
		initializeReindex();
		
		// Runs the export process to extract marc records from the ILS (if applicable)
		runExportScript();
		
		//Process all reords (marc records, econtent that has been added to the database, and resources)
		ArrayList<IRecordProcessor> recordProcessors = loadRecordProcesors();
		if (recordProcessors.size() > 0){
			logger.info("Initializing record processors");
			for (IRecordProcessor processor : recordProcessors){
				processor.init(configIni, serverName, logger);
			}
			//Do processing of marc records with record processors loaded above. 
			// includes indexing records
			// extracting eContent from records
			// Updating resource information
			// Saving records to strands - may need to move to resources if we are doing partial exports
			processMarcRecords(recordProcessors);
			
			//Process eContent records that have been saved to the database. 
			/*processEContentRecords(recordProcessors);
			
			//Do processing of resources as needed (for extraction of resources).
			processResources(recordProcessors);*/
			
			for (IRecordProcessor processor : recordProcessors){
				processor.finish();
			}
		}
		
		// Update the alphabetic browse database tables
		buildAlphaBrowseTables();
		
		// Send completion information
		endTime = new Date().getTime();
		sendCompletionMessage(true);

		logger.info("Finished Reindex for " + serverName);
	}
		
	private static void buildAlphaBrowseTables() {
		logger.info("Building Alphabetic Browse tables");
		//Run queries to create alphabetic browse tables from resources table
		try {
			//Setup title browse
			PreparedStatement truncateTable = vufindConn.prepareStatement("TRUNCATE title_browse");
			truncateTable.executeUpdate();
			PreparedStatement setRowNumberStatement = vufindConn.prepareStatement("SELECT 0 INTO @rn");
			setRowNumberStatement.execute();
			PreparedStatement createBrowseTable = vufindConn.prepareStatement("INSERT INTO title_browse (id, numResults, value) SELECT @rn:=@rn+1 AS id, baseQuery.numResults, baseQuery.title FROM (SELECT count(id) as numResults, title, title_sort FROM `resource` WHERE title_sort is not null and CHAR_LENGTH(title_sort) > 0 and title_sort != 'null' group by title) baseQuery order by title_sort asc");
			int numBrowseRows = createBrowseTable.executeUpdate();
			logger.info("Added " + numBrowseRows + " to title browse table");
		} catch (SQLException e) {
			logger.error("Error creating title browse table", e);
		}
		
		try {
			//Setup author browse
			PreparedStatement truncateTable = vufindConn.prepareStatement("TRUNCATE author_browse");
			truncateTable.executeUpdate();
			PreparedStatement setRowNumberStatement = vufindConn.prepareStatement("SELECT 0 INTO @rn");
			setRowNumberStatement.execute();
			PreparedStatement createBrowseTable = vufindConn.prepareStatement("INSERT INTO author_browse (id, numResults, value) SELECT @rn:=@rn+1 AS id, baseQuery.* FROM (SELECT count(id) as numResults, author FROM `resource` WHERE author is not null and CHAR_LENGTH(author) > 0 and author != 'null' group by author) baseQuery order by author asc");
			int numBrowseRows = createBrowseTable.executeUpdate();
			logger.info("Added " + numBrowseRows + " to author browse table");
		} catch (SQLException e) {
			logger.error("Error creating author browse table", e);
		}

		//Setup subject browse
		try {
			//Setup subject browse
			PreparedStatement truncateTable = vufindConn.prepareStatement("TRUNCATE subject_browse");
			truncateTable.executeUpdate();
			PreparedStatement setRowNumberStatement = vufindConn.prepareStatement("SELECT 0 INTO @rn");
			setRowNumberStatement.execute();
			PreparedStatement createBrowseTable = vufindConn.prepareStatement("INSERT INTO subject_browse (id, numResults, value) SELECT @rn:=@rn+1 AS id, baseQuery.* FROM (SELECT count(resource.id) as numResults, subject from resource inner join resource_subject on resource.id = resource_subject.resourceId inner join subject on subjectId = subject.id group by subjectId) baseQuery order by subject asc");
			int numBrowseRows = createBrowseTable.executeUpdate();
			logger.info("Added " + numBrowseRows + " to subject browse table");
		} catch (SQLException e) {
			logger.error("Error creating subject browse table", e);
		}
		
	//Setup call number browse
		try {
			//Setup callnumber browse
			PreparedStatement truncateTable = vufindConn.prepareStatement("TRUNCATE callnumber_browse");
			truncateTable.executeUpdate();
			PreparedStatement setRowNumberStatement = vufindConn.prepareStatement("SELECT 0 INTO @rn");
			setRowNumberStatement.execute();
			PreparedStatement createBrowseTable = vufindConn.prepareStatement("INSERT INTO callnumber_browse (id, numResults, value) SELECT @rn:=@rn+1 AS id, baseQuery.* FROM (SELECT count(resource.id) as numResults, callnumber from resource inner join (select resourceId, callnumber FROM resource_callnumber group by resourceId, callnumber) titleCallNumber on resource.id = resourceId group by callnumber) baseQuery order by callnumber asc");
			int numBrowseRows = createBrowseTable.executeUpdate();
			logger.info("Added " + numBrowseRows + " to callnumber browse table");
		} catch (SQLException e) {
			logger.error("Error creating callnumber browse table", e);
		}
	}

	
	private static ArrayList<IRecordProcessor> loadRecordProcesors(){
		ArrayList<IRecordProcessor> supplementalProcessors = new ArrayList<IRecordProcessor>();
		if (updateSolr){
			MarcIndexer marcIndexer = new MarcIndexer();
			if (marcIndexer.init(configIni, serverName, logger)){
				supplementalProcessors.add(marcIndexer);
			}else{
				logger.error("Could not initialize marcIndexer");
				System.exit(1);
			}
		}
		if (updateResources){
			UpdateResourceInformation resourceUpdater = new UpdateResourceInformation();
			if (resourceUpdater.init(configIni, serverName, logger)){
				supplementalProcessors.add(resourceUpdater);
			}else{
				logger.error("Could not initialize resourceUpdater");
				System.exit(1);
			}
		}
		if (loadEContentFromMarc){
			ExtractEContentFromMarc econtentExtractor = new ExtractEContentFromMarc();
			if (econtentExtractor.init(configIni, serverName, logger)){
				supplementalProcessors.add(econtentExtractor);
			}else{
				logger.error("Could not initialize econtentExtractor");
				System.exit(1);
			}
		}
		if (exportStrandsCatalog){
			StrandsProcessor strandsProcessor = new StrandsProcessor();
			if (strandsProcessor.init(configIni, serverName, logger)){
				supplementalProcessors.add(strandsProcessor);
			}else{
				logger.error("Could not initialize strandsProcessor");
				System.exit(1);
			}
		}
		if (exportOPDSCatalog){
			// 14) Generate OPDS catalog
		}
		
		return supplementalProcessors;
	}

	private static void processResources(ArrayList<IRecordProcessor> supplementalProcessors) {
		ArrayList<IResourceProcessor> resourceProcessors = new ArrayList<IResourceProcessor>();
		for (IRecordProcessor processor: supplementalProcessors){
			if (processor instanceof IResourceProcessor){
				resourceProcessors.add((IResourceProcessor)processor);
			}
		}
		if (resourceProcessors.size() == 0){
			return;
		}
		
		logger.info("Processing resources");
		try {
			PreparedStatement allResourcesStmt = vufindConn.prepareStatement("SELECT * FROM resource");
			ResultSet allResources = allResourcesStmt.executeQuery();
			while (allResources.next()){
				for (IResourceProcessor resourceProcessor : resourceProcessors){
					resourceProcessor.processResource(allResources);
				}
			}
		} catch (SQLException e) {
			logger.error("Error processing resources", e);
		}
	}

	private static void processEContentRecords(ArrayList<IRecordProcessor> supplementalProcessors) {
		try {
			logger.info("Processing econtent records");
			
			//Setup prepared statements that we will use
			PreparedStatement econtentRecordStatement = econtentConn.prepareStatement("SELECT * FROM econtent_record WHERE status = 'active'");
			PreparedStatement existingResourceStmt = vufindConn.prepareStatement("SELECT id, date_updated from resource where record_id = ? and source = 'eContent'");
			PreparedStatement addResourceStmt = vufindConn.prepareStatement("INSERT INTO resource (record_id, title, source, author, title_sort, isbn, upc, format, format_category, marc_checksum, date_updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			PreparedStatement updateResourceStmt = vufindConn.prepareStatement("UPDATE resource SET record_id = ?, title = ?, source = ?, author = ?, title_sort = ?, isbn = ?, upc = ?, format = ?, format_category = ?, marc_checksum = ?, date_updated = ? WHERE id = ?");
			
			//Check to see if the record already exists
			ResultSet allEContent = econtentRecordStatement.executeQuery();
			while (allEContent.next()){
				String econtentId = allEContent.getString("id");
				
				//Load title information so we have access regardless of 
				String title = allEContent.getString("title");
				String subTitle = allEContent.getString("subTitle");
				if (subTitle.length() > 0){
					title += ": " + subTitle;
				}
				String sortTitle = title.toLowerCase().replaceAll("^(the|an|a|el|la)\\s", "");
				String isbn = allEContent.getString("isbn");
				if (isbn.indexOf(' ') > 0){
					isbn = isbn.substring(0, isbn.indexOf(' '));
				}
				if (isbn.indexOf("\r") > 0){
					isbn = isbn.substring(0,isbn.indexOf("\r"));
				}
				if (isbn.indexOf("\n") > 0){
					isbn = isbn.substring(0,isbn.indexOf("\n"));
				}
				String upc = allEContent.getString("upc");
				if (upc.indexOf(' ') > 0){
					upc = upc.substring(0, upc.indexOf(' '));
				}
				if (upc.indexOf("\r") > 0){
					upc = upc.substring(0,upc.indexOf("\r"));
				}
				if (upc.indexOf("\n") > 0){
					upc = upc.substring(0,upc.indexOf("\n"));
				}
				System.out.println("UPC: " + upc);
				
				//Check to see if we have an existing resource
				existingResourceStmt.setString(1, econtentId);
				ResultSet existingResource = existingResourceStmt.executeQuery();
				if (existingResource.next()){
					//Check the date resource was updated and update if it was updated before the record was changed last
					boolean updateResource = false;
					long resourceUpdateTime = existingResource.getLong("date_updated");
					long econtentUpdateTime = allEContent.getLong("date_updated");
					if (econtentUpdateTime > resourceUpdateTime){
						updateResource = true;
					}
					if (updateResource){
						logger.debug("Updating Resource for eContentRecord " + econtentId);
						updateResourceStmt.setString(1, econtentId);
						updateResourceStmt.setString(2, Util.trimTo(255, title));
						updateResourceStmt.setString(3, "eContent");
						updateResourceStmt.setString(4, Util.trimTo(255, allEContent.getString("author")));
						updateResourceStmt.setString(5, Util.trimTo(255, sortTitle));
						updateResourceStmt.setString(6, Util.trimTo(13, isbn));
						updateResourceStmt.setString(7, Util.trimTo(13, upc));
						updateResourceStmt.setString(8, "");
						updateResourceStmt.setString(9, "emedia");
						updateResourceStmt.setLong(10, 0);
						updateResourceStmt.setLong(11, new Date().getTime() / 1000);
						updateResourceStmt.setLong(12, existingResource.getLong("id"));
						
						int numUpdated = updateResourceStmt.executeUpdate();
						if (numUpdated != 1){
							logger.error("Reource not updated for econtent record " + econtentId);
						}
					}else{
						logger.debug("Not updating resource for eContentRecord " + econtentId + ", it is already up to date");
					}
				}else{
					//Insert a new resource
					System.out.println("Adding resource for eContentRecord " + econtentId);
					addResourceStmt.setString(1, econtentId);
					addResourceStmt.setString(2, Util.trimTo(255, title));
					addResourceStmt.setString(3, "eContent");
					addResourceStmt.setString(4, Util.trimTo(255, allEContent.getString("author")));
					addResourceStmt.setString(5, Util.trimTo(255, sortTitle));
					addResourceStmt.setString(6, Util.trimTo(13, isbn));
					addResourceStmt.setString(7, Util.trimTo(13, upc));
					addResourceStmt.setString(8, "");
					addResourceStmt.setString(9, "emedia");
					addResourceStmt.setLong(10, 0);
					addResourceStmt.setLong(11, new Date().getTime() / 1000);
					int numAdded = addResourceStmt.executeUpdate();
					if (numAdded != 1){
						logger.error("Reource not added for econtent record " + econtentId);
					}
				}
			}
		} catch (SQLException e) {
			logger.error("Error updating resources for eContent", e);
		}
	}

	private static void processMarcRecords(ArrayList<IRecordProcessor> supplementalProcessors) {
		ArrayList<IMarcRecordProcessor> marcProcessors = new ArrayList<IMarcRecordProcessor>();
		for (IRecordProcessor processor: supplementalProcessors){
			if (processor instanceof IMarcRecordProcessor){
				marcProcessors.add((IMarcRecordProcessor)processor);
			}
		}
		if (marcProcessors.size() == 0){
			return;
		}
		
		MarcProcessor marcProcessor = new MarcProcessor();
		marcProcessor.init(serverName, configIni, vufindConn, logger);
		
		if (supplementalProcessors.size() > 0){
			logger.info("Processing exported marc records");
			marcProcessor.processMarcFiles(marcProcessors, logger);
		}
	}

	private static void importMarcFiles() {
		// Copy schema from biblio to biblio2
		File schema = new File("../../sites/" + serverName + "/solr/biblio/conf/schema.xml");
		File schema2 = new File("../../sites/" + serverName + "/solr/biblio2/conf/schema.xml");
		try {
			Util.copyFile(schema, schema2);
		} catch (IOException e) {
			logger.error("Unable to copy schema from biblio to biblio2", e);
			System.exit(1);
		}
		String reloadBiblio2Response = Util.postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=RELOAD&core=biblio2", null, logger);
		logger.info("Response for reloading biblio2 " + reloadBiblio2Response);
		// Get the marc files to process
		String marcFilePath = configIni.get("Reindex", "marcPath");
		String numFilesToImportStr = configIni.get("Reindex", "numFilesToImport");
		int numFilesToImport = -1;
		if (numFilesToImportStr != null && numFilesToImportStr.length() == 0){
			numFilesToImport = Integer.parseInt(numFilesToImportStr);
		}
		ArrayList<File> marcFilesToProcess = loadMarcFilesToProcess(marcFilePath, numFilesToImport);
		for (File curFile : marcFilesToProcess){
			logger.info("Processing marc file " + curFile);
			try {
				/*MarcImporter importer = new MarcImporter();
				importer.init(new String[]{"../../conf/" + serverName + "/import.properties", "../../conf/" + serverName + "/import.properties"});
				int returnCode = importer.handleAll();*/
				if (SystemUtil.isWindowsPlatform()){
					SystemUtil.executeCommand(new String[]{"cmd", "/C", "java", "-jar", "SolrMarc.jar" , 
						"\"../../sites/" + serverName + "/conf/import.properties\"" ,
						curFile.toString()}
						, logger);
				}else{
					SystemUtil.executeCommand(new String[]{"java",
							"-jar", "SolrMarc.jar" , 
							new File("../../sites/" + serverName + "/conf/import.properties").getAbsoluteFile().getAbsolutePath() ,
							curFile.getAbsolutePath()}
							, logger);
				}
				/*MarcImporter importer = new MarcImporter();
				importer.init(new String[]{"../../conf/" + serverName + "/import.properties", curFile.toString()});
				int numProcessed = importer.handleAll();*/
				
			} catch (Exception e) {
				logger.error("Error running importScript", e);
			}
			
		}
		
		// Reload biblio 2 again so the optimzation can cleanup files
		reloadBiblio2Response = Util.postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=RELOAD&core=biblio2", null, logger);
		logger.info("Response for reloading biblio2 " + reloadBiblio2Response);
	}

	private static void runExportScript() {
		String extractScript = configIni.get("Reindex", "extractScript");
		if (extractScript.length() > 0) {
			logger.info("Running export script");
			try {
				String reindexResult = SystemUtil.executeCommand(extractScript, "", logger);
				logger.info("Result of extractScript (" + extractScript + ") was " + reindexResult);
			} catch (IOException e) {
				logger.error("Error running extract script, stopping reindex process", e);
				System.exit(1);
			}
		}
	}

	private static void initializeReindex() {
		System.out.println("Starting to initialize system");
		// Delete the existing reindex.log file
		File solrmarcLog = new File("../../sites/" + serverName + "/logs/reindex.log");
		if (solrmarcLog.exists()){
			solrmarcLog.delete();
		}
		for (int i = 1; i <= 4; i++){
			solrmarcLog = new File("../../sites/" + serverName + "/logs/reindex.log." + i);
			if (solrmarcLog.exists()){
				solrmarcLog.delete();
			}
		}
		solrmarcLog = new File("solrmarc.log");
		if (solrmarcLog.exists()){
			solrmarcLog.delete();
		}
		for (int i = 1; i <= 4; i++){
			solrmarcLog = new File("solrmarc.log." + i);
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

		// Load the configuration file
		String configName = "../../sites/" + serverName + "/conf/config.ini";
		logger.info("Loading configuration from " + configName);
		File configFile = new File(configName);
		if (!configFile.exists()) {
			logger.error("Could not find confiuration file " + configName);
			System.exit(1);
		}

		// Parse the configuration file
		configIni = new Ini();
		try {
			configIni.load(new FileReader(configFile));
		} catch (InvalidFileFormatException e) {
			logger.error("Configuration file is not valid.  Please check the syntax of the file.", e);
		} catch (FileNotFoundException e) {
			logger.error("Configuration file could not be found.  You must supply a configuration file in conf called config.ini.", e);
		} catch (IOException e) {
			logger.error("Configuration file could not be read.", e);
		}
		solrPort = configIni.get("Reindex", "solrPort");
		if (solrPort.length() == 0) {
			logger.error("You must provide the port where the solr index is loaded in the import configuration file");
			System.exit(1);
		}
		
		String updateSolrStr = configIni.get("Reindex", "updateSolr");
		if (updateSolrStr != null){
			updateSolr = Boolean.parseBoolean(updateSolrStr);
		}
		String updateResourcesStr = configIni.get("Reindex", "updateResources");
		if (updateResourcesStr != null){
			updateResources = Boolean.parseBoolean(updateResourcesStr);
		}
		String exportStrandsCatalogStr = configIni.get("Reindex", "exportStrandsCatalog");
		if (exportStrandsCatalogStr != null){
			exportStrandsCatalog = Boolean.parseBoolean(exportStrandsCatalogStr);
		}
		String exportOPDSCatalogStr = configIni.get("Reindex", "exportOPDSCatalog");
		if (exportOPDSCatalogStr != null){
			exportOPDSCatalog = Boolean.parseBoolean(exportOPDSCatalogStr);
		}
		String loadEContentFromMarcStr = configIni.get("Reindex", "loadEContentFromMarc");
		if (loadEContentFromMarcStr != null){
			loadEContentFromMarc = Boolean.parseBoolean(loadEContentFromMarcStr);
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
		} catch (SQLException e) {
			logger.error("Could not connect to econtent database", e);
			System.exit(1);
		}
		
		//Start a reindex log entry 
		try {
			logger.info("Creating log entry for index");
			PreparedStatement createLogEntryStatement = vufindConn.prepareStatement("INSERT INTO reindex_log (startTime) VALUES (?)", PreparedStatement.RETURN_GENERATED_KEYS);
			createLogEntryStatement.setLong(1, new Date().getTime() / 1000);
			createLogEntryStatement.executeUpdate();
			ResultSet generatedKeys = createLogEntryStatement.getGeneratedKeys();
			if (generatedKeys.next()){
				reindexLogId = generatedKeys.getLong(1);
			}
		} catch (SQLException e) {
			logger.error("Unable to create log entry for reindex process", e);
			System.exit(0);
		}
		
	}

	private static ArrayList<File> loadMarcFilesToProcess(String marcFilePath, int numFilesToImport) {
		ArrayList<File> filesToProcess = new ArrayList<File>(); 
		File marcFilePathFile = new File(marcFilePath);
		if (marcFilePathFile.exists()){
			File[] files = marcFilePathFile.listFiles(new FilenameFilter() {
				public boolean accept(File arg0, String arg1) {
					return arg1.endsWith("marc") || arg1.endsWith("mrc");
				}
			});
			
			for (File curFile : files){
				filesToProcess.add(curFile);
			}
		}else{
			logger.error("marc file path " + marcFilePath + " does not exist, halting reindex.");
			System.exit(1);
		}
		return filesToProcess;
	}
	
	private static void getSolrmarcStats(File fileToRead){
		try {
			BufferedReader reader = new BufferedReader(new FileReader(fileToRead));
			String curLine = reader.readLine();
			Pattern errorPattern = Pattern.compile(".*ERROR:\\s*(.*)", Pattern.CANON_EQ);
			Pattern severePattern = Pattern.compile(".*ERROR:\\s*(.*)", Pattern.CANON_EQ);
			Pattern exceptionPattern  = Pattern.compile(".*Exception:\\s*(.*)", Pattern.CANON_EQ);
			while (curLine != null){
				Matcher errorMatcher = errorPattern.matcher(curLine);
				Matcher severeMatcher = severePattern.matcher(curLine);
				Matcher exceptionMatcher = exceptionPattern.matcher(curLine);
				if (errorMatcher.matches()){
					numErrors++;
					distinctErrors.add(errorMatcher.group(1));
				}else if (severeMatcher.matches()){
					numSevereErrors++;
					distinctSevereErrors.add(severeMatcher.group(1));
				}else if (exceptionMatcher.matches()){
					numExceptions++;
					distinctExceptions.add(exceptionMatcher.group(1));
				}else if (curLine.matches(".*Added.*")){
					numAdded++;
				}
				curLine = reader.readLine();
			}
		} catch (Exception e) {
			logger.error("Unable to get stats from Solrmarc log file", e);
		}
	}
	
	private static void sendCompletionMessage(boolean passed){
		/*StringBuffer completionNotes = new StringBuffer();
		if (passed){
			logger.info("Reindex completed successfully");
		}else{
			logger.info("Index failed, not using updated index.");
		}
		logger.info("Number of records added: " + numAdded );
		logger.info("Number of severe errors: " + numSevereErrors + " (" + distinctSevereErrors.size() + " distinct)"  );
		for (String curError : distinctSevereErrors){
			logger.info(curError);
		}
		logger.info("Number of errors: " + numErrors + " (" + distinctErrors.size() + " distinct)");
		for (String curError : distinctErrors){
			logger.info(curError);
		}
		logger.info("Number of exceptions: " + numExceptions + " (" + distinctExceptions.size() + " distinct)");
		for (String curError : distinctExceptions){
			logger.info(curError);
		}
		long elapsedTime = endTime - startTime;
		float elapsedMinutes = (float)elapsedTime / (float)(60000); 
		logger.info("Time elpased: " + elapsedMinutes + " minutes");*/
		
		try {
			PreparedStatement finishedStatement = vufindConn.prepareStatement("UPDATE reindex_log SET endTime = ?, notes = ? WHERE id=?");
			finishedStatement.setLong(1, new Date().getTime() / 1000);
			finishedStatement.setString(2, "");
			finishedStatement.setLong(3, reindexLogId);
			finishedStatement.executeUpdate();
		} catch (SQLException e) {
			logger.error("Unable to update reindex log with completion time.", e);
		}
	}
}
