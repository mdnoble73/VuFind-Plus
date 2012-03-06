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
import java.net.URLEncoder;
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
 * 2) Empty backup index of all marc
 * records (preserves lists) 
 * 3) Processes marc record files in export to import
 * them into backup index 
 * 4) Optimizes backup index 
 * 5) Check the reindexing process for errors and make sure the new index is ok to load
 * 6) Swap main
 * index and backup index so backup is moved into production 
 * 7) Unload both indexes so the indexes can be synchronized
 * 8) Copy files from the backup index to the main index 
 * 9) Reload the indexes 
 * 10) Update the alphabetic browse databases 
 * 11) Update resources database 
 * 12) Process marc record export for eContent that can be automatically extracted 
 * 13) Send email notification that the index is complete
 * 
 * @author Mark Noble <mnoble@turningleaftech.com>
 * 
 */
public class ReindexProcess {

	private static Logger logger	= Logger.getLogger(ReindexProcess.class);

	//General configuration
	private static String serverName;
	private static String libraryAbbrev;
	private static Ini configIni;
	private static String solrPort;
	
	//Reporting information
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
	private static boolean loadEContentFromMarc = true;
	private static boolean exportStrandsCatalog = true;
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

		initializeReindex();

		
		// 1) Runs export process to extract marc records from the ILS (if
		// applicable)
		runExportScript();
		
		if (updateSolr){
			// 2) Empty backup index of all marc records (preserves lists)
			@SuppressWarnings("deprecation")
			String emptyIndexResponse = postToURL("http://localhost:" + solrPort + "/solr/biblio2/update?stream.body=" + URLEncoder.encode("<delete><query>recordtype:marc</query></delete>") + "&commit=true", null);
			logger.info("Response for emptying index " + emptyIndexResponse);
	
			// 3) Processes marc record files in export to import them into backup index
			importMarcFiles();
	
			// 4) Optimizes backup index (done as part of the index)
			try {
				@SuppressWarnings("deprecation")
				String optimizeResponse = postToURL("http://localhost:" + solrPort + "/solr/biblio2/update?stream.body=" + URLEncoder.encode("<optimize />"), null);
				logger.info("Response for swaping cores " + optimizeResponse);
			} catch (Exception e) {
				logger.error("Error optimizing biblio2", e);
			}
			
			// 5) Check the reindexing process for errors and make sure the new index is ok to load
			checkMarcImport();
			
			// 6) Swap main index and backup index so backup is moved into production
			String swapCoresResponse = postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=SWAP&core=biblio2&other=biblio", null);
			logger.info("Response for swaping cores " + swapCoresResponse);
			
			// 7-9) Move biblio2 index to biblio index
			moveBiblio2ToBiblio();
			
			// 10) Update the alphabetic browse databases
			try {
				logger.info("Building Alphabetic browse");
				if (SystemUtil.isWindowsPlatform()){
					String buildBrowseResult = SystemUtil.executeCommand(new String[]{"cmd", "/C", "index-alphabetic-browse.bat", libraryAbbrev}, logger);
					logger.info("buildBrowseResult = " + buildBrowseResult);
				}else{
					String buildBrowseResult = SystemUtil.executeCommand(new String[]{"index-alphabetic-browse.sh", libraryAbbrev}, logger);
					logger.info("buildBrowseResult = " + buildBrowseResult);
				}
				
			} catch (Exception e) {
				logger.error("Error running importScript", e);
			}
		}
		
		ArrayList<ISupplementalProcessor> supplementalProcessors = loadSupplementalProcessors();
		if (supplementalProcessors.size() > 0){
			for (ISupplementalProcessor processor : supplementalProcessors){
				processor.init(configIni, logger);
			}
			//Do additional processing of marc records
			doSupplementalMarcProcessing(supplementalProcessors);
			
			//Create resources for all eContent records if they don't have a resource already. 
			doSupplementalEContentProcessing(supplementalProcessors);
			
			//Do processing of resources as needed (for extraction of resources).
			doSupplementalResourceProcessing(supplementalProcessors);
			
			for (ISupplementalProcessor processor : supplementalProcessors){
				processor.finish();
			}
		}
		
		// 15) Send email notification that the index is complete or update the database
		endTime = new Date().getTime();
		sendCompletionMessage(true);

		logger.info("Finished Reindex for " + serverName);
	}
	
	private static ArrayList<ISupplementalProcessor> loadSupplementalProcessors(){
		ArrayList<ISupplementalProcessor> supplementalProcessors = new ArrayList<ISupplementalProcessor>();
		if (updateResources){
			UpdateResourceInformation resourceUpdater = new UpdateResourceInformation();
			if (resourceUpdater.init(configIni, logger)){
				supplementalProcessors.add(resourceUpdater);
			}else{
				logger.error("Could not initialize resourceUpdater");
				System.exit(1);
			}
		}
		if (loadEContentFromMarc){
			ExtractEContentFromMarc econtentExtractor = new ExtractEContentFromMarc();
			if (econtentExtractor.init(configIni, logger)){
				supplementalProcessors.add(econtentExtractor);
			}else{
				logger.error("Could not initialize econtentExtractor");
				System.exit(1);
			}
		}
		if (exportStrandsCatalog){
			StrandsProcessor strandsProcessor = new StrandsProcessor();
			if (strandsProcessor.init(configIni, logger)){
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

	private static void doSupplementalResourceProcessing(ArrayList<ISupplementalProcessor> supplementalProcessors) {
		ArrayList<IResourceProcessor> resourceProcessors = new ArrayList<IResourceProcessor>();
		for (ISupplementalProcessor processor: supplementalProcessors){
			if (processor instanceof IResourceProcessor){
				resourceProcessors.add((IResourceProcessor)processor);
			}
		}
		if (resourceProcessors.size() == 0){
			return;
		}
		
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

	private static void doSupplementalEContentProcessing(ArrayList<ISupplementalProcessor> supplementalProcessors) {
		try {
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

	private static void doSupplementalMarcProcessing(ArrayList<ISupplementalProcessor> supplementalProcessors) {
		ArrayList<IMarcRecordProcessor> marcProcessors = new ArrayList<IMarcRecordProcessor>();
		for (ISupplementalProcessor processor: supplementalProcessors){
			if (processor instanceof IMarcRecordProcessor){
				marcProcessors.add((IMarcRecordProcessor)processor);
			}
		}
		if (marcProcessors.size() == 0){
			return;
		}
		
		MarcProcessor marcProcessor = new MarcProcessor();
		marcProcessor.init(configIni, logger);
		
		if (supplementalProcessors.size() > 0){
			marcProcessor.processMarcFiles(marcProcessors, logger);
		}
	}

	private static void moveBiblio2ToBiblio() {
		// 7) Unload the main index
		String unloadBiblio2Response = postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=UNLOAD&core=biblio2", null);
		logger.info("Response for unloading biblio 2 core " + unloadBiblio2Response);
		
		String unloadBiblioResponse = postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=UNLOAD&core=biblio", null);
		logger.info("Response for unloading biblio core " + unloadBiblioResponse);
		
		// 8) Copy files from the backup index to the main index
		// Remove all files from biblio (index, spellchecker, spellShingle)
		File biblioIndexDir = new File ("../../solr-data/" + libraryAbbrev + "/biblio/index");
		File biblio2IndexDir = new File ("../../solr-data/" + libraryAbbrev + "/biblio2/index");
		File biblioSpellcheckerDir = new File ("../../solr-data/" + libraryAbbrev + "/biblio/spellchecker");
		File biblio2SpellcheckerDir = new File ("../../solr-data/" + libraryAbbrev + "/biblio2/spellchecker");
		File biblioSpellShingleDir = new File ("../../solr-data/" + libraryAbbrev + "/biblio/spellShingle");
		File biblio2SpellShingleDir = new File ("../../solr-data/" + libraryAbbrev + "/biblio2/spellShingle");
		deleteDirectory(biblioIndexDir);
		deleteDirectory(biblioSpellcheckerDir);
		deleteDirectory(biblioSpellShingleDir);
		copyDir(biblio2IndexDir, biblioIndexDir);
		copyDir(biblio2SpellcheckerDir, biblioSpellcheckerDir);
		copyDir(biblio2SpellShingleDir, biblioSpellShingleDir);
		
		// 9) Reload the indexes
		String createBiblioResponse = postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=CREATE&name=biblio&instanceDir=biblio", null);
		logger.info("Response for creating biblio2 core " + createBiblioResponse);
		
		String createBiblio2Response = postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=CREATE&name=biblio2&instanceDir=biblio2", null);
		logger.info("Response for creating biblio2 core " + createBiblio2Response);
	}

	private static void checkMarcImport() {
		ArrayList<File> solrmarcLogs = new ArrayList<File>();
		File solrmarcLog = new File("solrmarc.log");
		if (solrmarcLog.exists()){
			solrmarcLogs.add(solrmarcLog);
		}
		for (int i = 1; i <= 4; i++){
			solrmarcLog = new File("solrmarc.log." + i);
			if (solrmarcLog.exists()){
				solrmarcLogs.add(solrmarcLog);
			}
		}
		
		for (File curFile : solrmarcLogs){
			getSolrmarcStats(curFile);
		}
		if (numAdded < 1000 || (numSevereErrors + numErrors + numExceptions) > numAdded){
			sendCompletionMessage(false);
			System.exit(0);
		}
	}

	private static void importMarcFiles() {
		// Delete the existing solrmarc.log file
		File solrmarcLog = new File("solrmarc.log");
		if (solrmarcLog.exists()){
			solrmarcLog.delete();
		}
		for (int i = 1; i <= 4; i++){
			solrmarcLog = new File("solrmarc.log." + i);
			if (solrmarcLog.exists()){
				solrmarcLog.delete();
			}
		}
		
		// Copy schema from biblio to biblio2
		File schema = new File("../../solr-data/" + libraryAbbrev + "/biblio/conf/schema.xml");
		File schema2 = new File("../../solr-data/" + libraryAbbrev + "/biblio2/conf/schema.xml");
		try {
			copyFile(schema, schema2);
		} catch (IOException e) {
			logger.error("Unable to copy schema from biblio to biblio2", e);
			System.exit(1);
		}
		String reloadBiblio2Response = postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=RELOAD&core=biblio2", null);
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
				if (SystemUtil.isWindowsPlatform()){
					SystemUtil.executeCommand(new String[]{"cmd", "/C", "java", "-jar", "SolrMarc.jar" , 
						"\"../../conf/" + serverName + "/import.properties\"" ,
						curFile.toString()}
						, logger);
				}else{
					SystemUtil.executeCommand(new String[]{"java", "-jar", "SolrMarc.jar" , 
							"../../conf/" + serverName + "/import.properties" ,
							curFile.toString()}
							, logger);
				}
				
			} catch (Exception e) {
				logger.error("Error running importScript", e);
			}
			
		}
		
		// Reload biblio 2 again so the optimzation can cleanup files
		reloadBiblio2Response = postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=RELOAD&core=biblio2", null);
		logger.info("Response for reloading biblio2 " + reloadBiblio2Response);
	}

	private static void runExportScript() {
		String extractScript = configIni.get("Reindex", "extractScript");
		if (extractScript.length() > 0) {
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
		// Initialize the logger
		File log4jFile = new File("../../conf/" + serverName + "/log4j.reindex.properties");
		if (log4jFile.exists()) {
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		} else {
			System.out.println("Could not find log4j configuration " + log4jFile.getAbsolutePath());
		}

		logger.info("Starting Reindex for " + serverName);

		// Load the configuration file
		String configName = "../../conf/" + serverName + "/config.ini";
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
		libraryAbbrev = configIni.get("Reindex", "libraryAbbrev");
		
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
		
	}

	private static void copyDir(File source, File dest) {
		if (!dest.exists()){
			dest.mkdir();
		}
		if (source.exists() == false){
			logger.error("Source directory " + source.toString() + " does not exist!");
			return;
		}
		File[] sourceFiles = source.listFiles();
		for (File curFile : sourceFiles){
			File destFile = new File(dest.getAbsolutePath() + "/" + curFile.getName());
			if (curFile.isDirectory()){
				copyDir(curFile, destFile);
			}else{
				try {
					copyFile(curFile, destFile);
				} catch (IOException e) {
					logger.error("Error copying file", e);
				}
			}
		}
	}

	private static void deleteDirectory(File dirToDelete) {
		File[] files = dirToDelete.listFiles();
		for (File curFile : files){
			if (curFile.isDirectory()){
				deleteDirectory(curFile);
			}else{
				curFile.delete();
			}
		}
		dirToDelete.delete();
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

	private static String postToURL(String url, String postData) {
		try {
			URL emptyIndexURL = new URL(url);
			URLConnection conn = emptyIndexURL.openConnection();
			if (postData != null && postData.length() > 0){
				conn.setDoOutput(true);
				OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream());
				wr.write(postData);
				wr.flush();
				wr.close();
			}

			// Get the response
			BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
			String line;
			StringBuffer response = new StringBuffer();
			while ((line = rd.readLine()) != null) {
				response.append(line);
			}
			
			rd.close();
			return response.toString();
		} catch (MalformedURLException e) {
			logger.error("URL to post (" + url + ") is malformed", e);
			return "";
		} catch (IOException e) {
			logger.error("Error posting to url ", e);
			return "";
		}
	}

	private static void copyFile(File sourceFile, File destFile) throws IOException {
		if (!destFile.exists()) {
			destFile.createNewFile();
		}
	
		FileChannel source = null;
		FileChannel destination = null;
	
		try {
			source = new FileInputStream(sourceFile).getChannel();
			destination = new FileOutputStream(destFile).getChannel();
			destination.transferFrom(source, 0, source.size());
		} finally {
			if (source != null) {
				source.close();
			}
			if (destination != null) {
				destination.close();
			}
		}
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
		logger.info("Time elpased: " + elapsedMinutes + " minutes");
	}
}
