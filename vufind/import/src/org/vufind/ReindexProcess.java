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
import java.util.ArrayList;
import java.util.HashSet;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import javax.print.attribute.standard.Severity;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;

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

	private static Logger	logger	= Logger.getLogger(ReindexProcess.class);

	private static String libraryName;
	private static Ini ini;
	private static String solrPort;
	
	private static int numSevereErrors = 0;
	private static HashSet<String> distinctSevereErrors = new HashSet<String>(); 
	private static int numErrors = 0;
	private static HashSet<String> distinctErrors = new HashSet<String>();
	private static int numAdded = 0;
	private static int numExceptions = 0;
	private static HashSet<String> distinctExceptions = new HashSet<String>();
	
	/**
	 * Starts the reindexing process
	 * 
	 * @param args
	 */
	public static void main(String[] args) {
		// Get the configuration filename
		if (args.length == 0) {
			System.out.println("Please enter the name of the library to index as the first parameter");
			System.exit(1);
		}
		libraryName = args[0];

		initializeReindex();

		// 1) Runs export process to extract marc records from the ILS (if
		// applicable)
		runExportScript();
		
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
				String buildBrowseResult = SystemUtil.executeCommand(new String[]{"cmd", "/C", "index-alphabetic-browse.bat", libraryName}, logger);
				logger.info("buildBrowseResult = " + buildBrowseResult);
			}else{
				String buildBrowseResult = SystemUtil.executeCommand(new String[]{"index-alphabetic-browse.sh", libraryName}, logger);
				logger.info("buildBrowseResult = " + buildBrowseResult);
			}
			
		} catch (Exception e) {
			logger.error("Error running importScript", e);
		}
		// 11) Update resources database
		
		
		// 12) Process marc record export for eContent that can be automatically extracted
		
		
		// 13) Send email notification that the index is complete
		sendSuccessMessage();

		logger.info("Finished Reindex for " + libraryName);
	}

	private static void moveBiblio2ToBiblio() {
		// 7) Unload the main index
		String unloadBiblio2Response = postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=UNLOAD&core=biblio2", null);
		logger.info("Response for unloading biblio 2 core " + unloadBiblio2Response);
		
		String unloadBiblioResponse = postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=UNLOAD&core=biblio", null);
		logger.info("Response for unloading biblio core " + unloadBiblioResponse);
		
		// 8) Copy files from the backup index to the main index
		// Remove all files from biblio (index, spellchecker, spellShingle)
		File biblioIndexDir = new File ("../../solr-data/" + libraryName + "/biblio/index");
		File biblio2IndexDir = new File ("../../solr-data/" + libraryName + "/biblio2/index");
		File biblioSpellcheckerDir = new File ("../../solr-data/" + libraryName + "/biblio/spellchecker");
		File biblio2SpellcheckerDir = new File ("../../solr-data/" + libraryName + "/biblio2/spellchecker");
		File biblioSpellShingleDir = new File ("../../solr-data/" + libraryName + "/biblio/spellShingle");
		File biblio2SpellShingleDir = new File ("../../solr-data/" + libraryName + "/biblio2/spellShingle");
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
			sendFailureMessage();
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
		File schema = new File("../../solr-data/" + libraryName + "/biblio/conf/schema.xml");
		File schema2 = new File("../../solr-data/" + libraryName + "/biblio2/conf/schema.xml");
		try {
			copyFile(schema, schema2);
		} catch (IOException e) {
			logger.error("Unable to copy schema from biblio to biblio2", e);
			System.exit(1);
		}
		String reloadBiblio2Response = postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=RELOAD&core=biblio2", null);
		logger.info("Response for reloading biblio2 " + reloadBiblio2Response);
		// Get the marc files to process
		String marcFilePath = ini.get("Reindex", "marcPath");
		String numFilesToImportStr = ini.get("Reindex", "numFilesToImport");
		int numFilesToImport = -1;
		if (numFilesToImportStr != null && numFilesToImportStr.length() == 0){
			numFilesToImport = Integer.parseInt(numFilesToImportStr);
		}
		ArrayList<File> marcFilesToProcess = loadMarcFilesToProcess(marcFilePath, numFilesToImport);
		for (File curFile : marcFilesToProcess){
			logger.info("Processing marc file " + curFile);
			try {
				if (SystemUtil.isWindowsPlatform()){
					SystemUtil.executeCommand(new String[]{"cmd", "/C", "start", "java", "-jar", "SolrMarc.jar" , 
						"../../conf/" + libraryName + "/import.properties\" " ,
						curFile.toString()}
						, logger);
				}else{
					SystemUtil.executeCommand(new String[]{"java", "-jar", "SolrMarc.jar" , 
							"../../conf/" + libraryName + "/import.properties\" " ,
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
		String extractScript = ini.get("Reindex", "extractScript");
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
		File log4jFile = new File("../../conf/" + libraryName + "/reindex.log.properties");
		if (log4jFile.exists()) {
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		} else {
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}

		logger.info("Starting Reindex for " + libraryName);

		// Load the configuration file
		String configName = "../../conf/" + libraryName + "/import.ini";
		File configFile = new File(configName);
		if (!configFile.exists()) {
			logger.error("Could not find confiuration file " + configName);
			System.exit(1);
		}

		// Parse the configuration file
		ini = new Ini();
		try {
			ini.load(new FileReader(configFile));
		} catch (InvalidFileFormatException e) {
			logger.error("Configuration file is not valid.  Please check the syntax of the file.", e);
		} catch (FileNotFoundException e) {
			logger.error("Configuration file could not be found.  You must supply a configuration file in conf called config.ini.", e);
		} catch (IOException e) {
			logger.error("Configuration file could not be read.", e);
		}
		solrPort = ini.get("Reindex", "solrPort");
		if (solrPort.length() == 0) {
			logger.error("You must provide the port where the solr index is loaded in the import configuration file");
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
	
	private static void sendFailureMessage(){
		logger.info("Index failed, not using updated index.");
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
	}
	
	private static void sendSuccessMessage(){
		logger.info("Reindex completed successfully.");
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
	}
}
