package org.marmot;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.*;
import java.net.*;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.HashSet;

/**
 * Exports information from eVoke using the APIs
 * VuFind-Plus
 * User: Mark Noble
 * Date: 8/29/2014
 * Time: 2:50 PM
 */
public class EVokeExportMain {
	private static Logger logger = Logger.getLogger(EVokeExportMain.class);
	private static String serverName;
	private static String evokeApiBaseUrl;
	private static String evokeAdminUser;
	private static String evokeAdminPassword;
	private static Long lastEVokeExtractTime = null;
	private static Long lastEVokeExtractTimeVariableId = null;
	private static CookieManager manager = new CookieManager();

	private static PreparedStatement getExistingRecordStmt;
	private static PreparedStatement insertRecordStmt;
	private static PreparedStatement updateRecordStmt;

	public static void main(String[] args) {
		serverName = args[0];

		Date startTime = new Date();
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.evoke_extract.properties");
		if (log4jFile.exists()){
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		}else{
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger.info(startTime.toString() + ": Starting eVoke Extract");

		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		Ini ini = loadConfigFile("config.ini");
		String exportPath = ini.get("eVoke", "evokePath");
		if (exportPath.startsWith("\"")){
			exportPath = exportPath.substring(1, exportPath.length() - 1);
		}
		evokeApiBaseUrl = ini.get("eVoke", "evokeApiUrl");
		evokeAdminUser = ini.get("eVoke", "adminUser");
		evokeAdminPassword = ini.get("eVoke", "adminPassword");

		//Determine if we are doing a full export or a partial export
		boolean partialExport = true;
		if (args.length >=2){
			if (args[1].equalsIgnoreCase("fullexport")){
				partialExport = false;
			}
		}

		//Connect to the vufind database
		Connection vufindConn = null;
		try{
			String databaseConnectionInfo = cleanIniValue(ini.get("Database", "database_vufind_jdbc"));
			vufindConn = DriverManager.getConnection(databaseConnectionInfo);
		}catch (Exception e){
			System.out.println("Error connecting to vufind database " + e.toString());
			System.exit(1);
		}

		try {
			getExistingRecordStmt = vufindConn.prepareStatement("SELECT * FROM evoke_record WHERE evoke_id = ?");
			insertRecordStmt = vufindConn.prepareStatement("INSERT INTO evoke_record (evoke_id, dateAdded, dateUpdated, deleted, dateDeleted) VALUES (?, ?, ?, 0, -1)");
			updateRecordStmt = vufindConn.prepareStatement("UPDATE evoke_record SET dateUpdated = ? WHERE evoke_id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
		}catch (Exception e){
			logger.error("Unable to create database statements for eVoke extract", e);
			System.out.println("Unable to create database statements for eVoke extract " + e.toString());
			System.exit(1);
		}

		loadLastExtractTime(vufindConn);

		manager.setCookiePolicy(CookiePolicy.ACCEPT_ALL);
		CookieHandler.setDefault(manager);
		loginToEVoke();
		if (partialExport){
			doPartialExportFromEVoke(exportPath);
		}else{
			doFullExportFromEVoke(exportPath);
		}

		updateLastExtractTime(vufindConn);

		try{
			//Close the connection
			vufindConn.close();
		}catch(Exception e){
			System.out.println("Error closing connection: " + e.toString());
			e.printStackTrace();
		}

		Date currentTime = new Date();
		logger.info(currentTime.toString() + ": Finished eVoke Extract");
	}

	private static void loginToEVoke() {
		//TODO: Load username and password from config file
		String username = "User5";
		String password = "Test2014";
		callEVokeUrl(evokeApiBaseUrl + "/UserService/login?user=" + evokeAdminUser + "&pass=" + evokeAdminPassword);
	}

	private static void doFullExportFromEVoke(String exportPath) {
		try {
			//Get a list of all evoke records
			//TODO: Eventually, this needs a way of checking to see how many titles there actually are.
			//For now, we just set an arbitrarily large number
			JSONObject allTitles = callEVokeUrl(evokeApiBaseUrl + "/SearchService/SearchAll?limit=10000");
			if (allTitles != null){
				if (allTitles.has("recordId")){
					JSONArray recordIds = allTitles.getJSONArray("recordId");
					for (int i = 0; i < recordIds.length(); i++){
						String curId = recordIds.getString(i);
						exportRecordId(curId, exportPath);
					}
				}
			}
		}catch (Exception e){
			logger.error("Error loading all titles from eVoke API", e);
		}
	}

	private static void exportRecordId(String recordId, String exportPath) {
		try {
			JSONObject recordInfo = callEVokeUrl(evokeApiBaseUrl + "/RecordService/Get_Record?recordId=" + recordId);
			if (recordInfo != null){
				String marcAsJSON = recordInfo.toString().trim();
				//Save the record to the filesystem
				File marcFile = getFileForEVokeRecord(recordId, exportPath);
				FileWriter marcWriter = new FileWriter(marcFile);
				marcWriter.write(marcAsJSON);
				marcWriter.close();

				//Save or add the record to the database
				//Check to see if there is a record in the database already
				getExistingRecordStmt.setString(1, recordId);
				ResultSet existingRecordInfo = getExistingRecordStmt.executeQuery();
				long curTime = new Date().getTime() / 1000;
				long evokeDBId = -1;
				if (existingRecordInfo.next()){
					evokeDBId = existingRecordInfo.getLong("id");
					//TODO: Only update this if something changed (different marc data or different loanable info)
					updateRecordStmt.setLong(1, curTime);
					updateRecordStmt.setString(2, recordId);
					updateRecordStmt.executeUpdate();
				}else{
					insertRecordStmt.setString(1, recordId);
					insertRecordStmt.setLong(2, curTime);
					insertRecordStmt.setLong(3, curTime);
					insertRecordStmt.executeUpdate();

					ResultSet recordIds = insertRecordStmt.getGeneratedKeys();
					if (recordIds.next()) {
						evokeDBId = recordIds.getLong(1);
						recordIds.close();
					}
				}

				if (evokeDBId != -1) {
					//Extract loanables
					JSONObject loanables = callEVokeUrl(evokeApiBaseUrl + "/RecordService/Get_Loanables?recordId=" + recordId);
					//Add loanables to the database
					if (loanables.has("loanable")) {
						if (loanables.get("loanable") instanceof JSONArray) {
							extractLoanable(loanables.getJSONObject("loanable"), recordId, evokeDBId);
						} else {
							JSONArray loanableArray = loanables.getJSONArray("loanable");
							for (int i = 0; i < loanableArray.length(); i++){
								extractLoanable(loanableArray.getJSONObject(i), recordId, evokeDBId);
							}
						}
					}
				}
			}
		}catch (Exception e){
			logger.error("Error loading all titles from eVoke API", e);
		}
	}

	private static void extractLoanable(JSONObject loanable, String evokeId, long evokeDBId) throws JSONException {
		String loanableId = loanable.getString("loanableId");
		//Get the availability for the loanable


	}

	private static File getFileForEVokeRecord(String recordId, String eVokePath) {
		String shortId = getFileIdForRecordNumber(recordId);
		String firstChars = shortId.substring(0, 4);
		String basePath = eVokePath + "/" + firstChars;
		String individualFilename = basePath + "/" + shortId + ".mrc";
		File individualFile = new File(individualFilename);
		createBaseDirectory(basePath);
		return individualFile;
	}

	private static String getFileIdForRecordNumber(String recordNumber) {
		String shortId = recordNumber.replace(".", "");
		while (shortId.length() < 8){
			shortId = "0" + shortId;
		}
		return shortId;
	}

	private static HashSet<String> basePathsValidated = new HashSet<String>();
	private static void createBaseDirectory(String basePath) {
		if (basePathsValidated.contains(basePath)) {
			return;
		}
		File baseFile = new File(basePath);
		if (!baseFile.exists()){
			if (!baseFile.mkdirs()){
				System.out.println("Could not create directory to store individual marc");
			}
		}
		basePathsValidated.add(basePath);
	}

	private static JSONObject callEVokeUrl(String url){
		try{
			URL fetchAllTitlesUrl = new URL(url);
			HttpURLConnection conn = (HttpURLConnection) fetchAllTitlesUrl.openConnection();
			conn.setRequestProperty("Accept", "application/json");
			conn.setRequestProperty("User-Agent", "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
			conn.setRequestMethod("GET");

			StringBuilder response = new StringBuilder();
			if (conn.getResponseCode() == 200) {
				// Get the response
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				//logger.debug("  Finished reading response");
				rd.close();
				if (response.toString().equals("null") || response.length() == 0){
					return null;
				}else {
					return new JSONObject(response.toString());
				}
			}else {
				logger.error("Received error " + conn.getResponseCode() + " connecting to eVoke API" );
				// Get any errors
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				logger.debug("  Finished reading response");
				logger.debug(response.toString());

				rd.close();
			}
		} catch (Exception e) {
			logger.debug("Error loading data from eVoke API", e );
		}
		return null;
	}


	private static void updateLastExtractTime(Connection vufindConn) {
		try{
			Long finishTime = new Date().getTime() / 1000;
			if (lastEVokeExtractTimeVariableId != null) {
				PreparedStatement updateVariableStmt = vufindConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
				updateVariableStmt.setLong(1, finishTime);
				updateVariableStmt.setLong(2, lastEVokeExtractTimeVariableId);
				updateVariableStmt.executeUpdate();
				updateVariableStmt.close();
			} else {
				PreparedStatement insertVariableStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('last_evoke_extract_time', ?)");
				insertVariableStmt.setString(1, Long.toString(finishTime));
				insertVariableStmt.executeUpdate();
				insertVariableStmt.close();
			}
		}catch (Exception e){
			logger.error("Error updating last extract time for eVoke", e);
			System.exit(1);
		}
	}

	private static void loadLastExtractTime(Connection vufindConn) {
		try{
			lastEVokeExtractTime = null;
			lastEVokeExtractTimeVariableId = null;

			PreparedStatement loadLastEVokeExtractTimeStmt = vufindConn.prepareStatement("SELECT * from variables WHERE name = 'last_evoke_extract_time'");
			ResultSet lastEVokeExtractTimeRS = loadLastEVokeExtractTimeStmt.executeQuery();
			if (lastEVokeExtractTimeRS.next()){
				lastEVokeExtractTime = lastEVokeExtractTimeRS.getLong("value");
				lastEVokeExtractTimeVariableId = lastEVokeExtractTimeRS.getLong("id");
			}
		}catch (Exception e){
			logger.error("Error loading last extract time from eVoke", e);
			System.exit(1);
		}
	}


	private static void doPartialExportFromEVoke(String exportPath) {
		try {
			//Get a list of all evoke records
			//TODO: Eventually, this needs a way of checking to see how many titles there actually are.
			//TODO: Update this to have better time resolution than daily changes when the API is ready
			//For now, we just set an arbitrarily large number
			String lastExtractDate = new SimpleDateFormat("yyyy-MM-dd").format(new Date(lastEVokeExtractTime * 1000));
			String today = new SimpleDateFormat("yyyy-MM-dd").format(new Date());
			JSONObject newAndUpdatedTitles = callEVokeUrl(evokeApiBaseUrl + "/SearchService/BoundedSearch?ini=" + lastExtractDate + "&end=" + today + "&limit=10000");
			if (newAndUpdatedTitles != null){
				if (newAndUpdatedTitles.has("recordId")){
					JSONArray recordIds = newAndUpdatedTitles.getJSONArray("recordId");
					for (int i = 0; i < recordIds.length(); i++){
						String curId = recordIds.getString(i);
						exportRecordId(curId, exportPath);
					}
				}
			}
		}catch (Exception e){
			logger.error("Error loading all titles from eVoke API", e);
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
