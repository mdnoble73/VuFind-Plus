package org.vufind;

import java.io.*;
import java.net.HttpURLConnection;
import java.net.URL;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.Date;

import au.com.bytecode.opencsv.CSVWriter;
import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile.Section;
import org.json.JSONArray;
import org.json.JSONObject;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLSession;
import org.apache.commons.codec.binary.Base64;

/**
 * Export data to
 * VuFind-Plus
 * User: Mark Noble
 * Date: 10/15/13
 * Time: 8:59 AM
 */
public class SierraExportMain{
	private static Logger logger = Logger.getLogger(SierraExportMain.class);
	private static String serverName;

	public static void main(String[] args){
		serverName = args[0];

		Date startTime = new Date();
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.sierra_extract.properties");
		if (log4jFile.exists()){
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		}else{
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger.info(startTime.toString() + ": Starting Sierra Extract");

		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		Ini ini = loadConfigFile("config.ini");
		String exportPath = ini.get("Reindex", "marcPath");
		if (exportPath.startsWith("\"")){
			exportPath = exportPath.substring(1, exportPath.length() - 1);
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

		//Connect to the sierra database
		String url = ini.get("Catalog", "sierra_db");
		if (url.startsWith("\"")){
			url = url.substring(1, url.length() - 1);
		}
		Connection conn = null;
		try{
			//Open the connection to the database
			conn = DriverManager.getConnection(url);

			exportActiveOrders(exportPath, conn);

			exportAvailability(exportPath, conn);

		}catch(Exception e){
			System.out.println("Error: " + e.toString());
			e.printStackTrace();
		}

		//Get a list of works that have changed since the last index
		getChangedRecordsFromApi(ini, vufindConn);

		if (conn != null){
			try{
				//Close the connection
				conn.close();
			}catch(Exception e){
				System.out.println("Error closing connection: " + e.toString());
				e.printStackTrace();
			}
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
		logger.info(currentTime.toString() + ": Finished Sierra Extract");
	}

	private static void getChangedRecordsFromApi(Ini ini, Connection vufindConn) {
		//Get the time the last extract was done
		try{
			logger.debug("Starting to load changed records from Sierra using the API");
			Long lastSierraExtractTime = null;
			Long lastSierraExtractTimeVariableId = null;
			PreparedStatement loadLastSierraExtractTimeStmt = vufindConn.prepareStatement("SELECT * from variables WHERE name = 'last_sierra_extract_time'");
			ResultSet lastSierraExtractTimeRS = loadLastSierraExtractTimeStmt.executeQuery();
			if (lastSierraExtractTimeRS.next()){
				lastSierraExtractTime = lastSierraExtractTimeRS.getLong("value");
				lastSierraExtractTimeVariableId = lastSierraExtractTimeRS.getLong("id");
			}

			//Only mark records as changed
			if (lastSierraExtractTime != null){
				//TODO:: Test this after the API is installed
				String apiVersion = cleanIniValue(ini.get("Catalog", "api_version"));
				if (apiVersion == null || apiVersion.length() == 0){
					return;
				}
				String apiBaseUrl = ini.get("Catalog", "url") + "/iii/sierra-api/v" + apiVersion;

				SimpleDateFormat dateFormatter = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'");
				String dateUpdated = dateFormatter.format(new Date(lastSierraExtractTime * 1000));
				long updateTime = new Date().getTime() / 1000;

				//Extract the ids of all records that have changed.  That will allow us to mark
				//That the grouped record has changed which will force the work to be indexed
				//In reality, this will only update availability unless we pull the full marc record
				//from the API since we only have updated availability, not location data or metadata
				PreparedStatement markGroupedWorkForBibAsChangedStmt = vufindConn.prepareStatement("UPDATE grouped_work SET date_updated = ? where id = (SELECT grouped_work_id from grouped_work_primary_identifiers WHERE type = 'ils' and identifier = ?)") ;
				JSONObject changedRecords = callSierraApiURL(ini, apiBaseUrl, apiBaseUrl + "/bibs/?updatedDate=[" + dateUpdated + ",]&limit=2000&fields=id&deleted=false&suppressed=false");
				if (changedRecords != null && changedRecords.has("entries")){
					JSONArray changedIds = changedRecords.getJSONArray("entries");
					for(int i = 0; i < changedIds.length(); i++){
						String curId = changedIds.getString(i);
						String fullId = ".b" + curId + getCheckDigit(curId);
						markGroupedWorkForBibAsChangedStmt.setLong(1, updateTime);
						markGroupedWorkForBibAsChangedStmt.setString(2, fullId);
						markGroupedWorkForBibAsChangedStmt.executeUpdate();

						//TODO: Determine if it is worth forming a full MARC record for ouput to the marc_recs folder
					}
				}

				//TODO: Process deleted records as well
			}

			//Update the last extract time
			Long finishTime = new Date().getTime() / 1000;
			if (lastSierraExtractTimeVariableId != null){
				PreparedStatement updateVariableStmt  = vufindConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
				updateVariableStmt.setLong(1, finishTime);
				updateVariableStmt.setLong(2, lastSierraExtractTimeVariableId);
				updateVariableStmt.executeUpdate();
				updateVariableStmt.close();
			} else{
				PreparedStatement insertVariableStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('last_sierra_extract_time', ?)");
				insertVariableStmt.setString(1, Long.toString(finishTime));
				insertVariableStmt.executeUpdate();
				insertVariableStmt.close();
			}

		} catch (Exception e){
			logger.error("Error loading changed records from Sierra API", e);
			System.exit(1);
		}
	}

	private static void exportAvailability(String exportPath, Connection conn) throws SQLException, IOException {
		logger.info("Starting export of available items");
		PreparedStatement getAvailableItemsStmt = conn.prepareStatement("SELECT barcode " +
				"from sierra_view.item_view " +
				//"left join sierra_view.checkout on sierra_view.item_view.id = sierra_view.checkout.item_record_id " +
				"WHERE " +
				"item_status_code IN ('-', 'o', 'd', 'w', 'j', 'u') " +
				"AND icode2 != 'n' AND icode2 != 'x' " +
				"AND is_suppressed = 'f' " +
				"AND BARCODE != ''"
				//"AND patron_record_id = null"
		);
		ResultSet activeOrdersRS = null;
		boolean loadError = false;
		try{
			activeOrdersRS = getAvailableItemsStmt.executeQuery();
		}catch (SQLException e1){
			logger.error("Error loading available items", e1);
			loadError = true;
		}
		if (!loadError){
			File availableItemsFile = new File(exportPath + "/available_items.csv");
			CSVWriter availableItemWriter = new CSVWriter(new FileWriter(availableItemsFile));
			availableItemWriter.writeAll(activeOrdersRS, false);
			availableItemWriter.close();
			activeOrdersRS.close();
		}

		//Also export items with checkouts
		logger.info("Starting export of checkouts");
		PreparedStatement allCheckoutsStmt = conn.prepareStatement("SELECT barcode " +
				"FROM sierra_view.checkout " +
				"INNER JOIN sierra_view.item_view on item_view.id = checkout.item_record_id"
		);
		ResultSet checkoutsRS = null;
		loadError = false;
		try{
			checkoutsRS = allCheckoutsStmt.executeQuery();
		}catch (SQLException e1){
			logger.error("Error loading checkouts", e1);
			loadError = true;
		}
		if (!loadError){
			File checkoutsFile = new File(exportPath + "/checkouts.csv");
			CSVWriter checkoutsWriter = new CSVWriter(new FileWriter(checkoutsFile));
			checkoutsWriter.writeAll(checkoutsRS, false);
			checkoutsWriter.close();
			checkoutsRS.close();
		}
	}

	private static void exportActiveOrders(String exportPath, Connection conn) throws SQLException, IOException {
		logger.info("Starting export of active orders");
		PreparedStatement getActiveOrdersStmt = conn.prepareStatement("select bib_view.record_num as bib_record_num, order_view.record_num as order_record_num, accounting_unit_code_num, order_status_code \n" +
				"from sierra_view.order_view " +
				"inner join sierra_view.bib_record_order_record_link on bib_record_order_record_link.order_record_id = order_view.record_id " +
				"inner join sierra_view.bib_view on sierra_view.bib_view.id = bib_record_order_record_link.bib_record_id " +
				"where order_status_code = 'o' or order_status_code = '1' and order_view.is_suppressed = 'f' ");
		ResultSet activeOrdersRS = null;
		boolean loadError = false;
		try{
			activeOrdersRS = getActiveOrdersStmt.executeQuery();
		} catch (SQLException e1){
			logger.error("Error loading active orders", e1);
			loadError = true;
		}
		if (!loadError){
			File orderRecordFile = new File(exportPath + "/active_orders.csv");
			CSVWriter orderRecordWriter = new CSVWriter(new FileWriter(orderRecordFile));
			orderRecordWriter.writeAll(activeOrdersRS, true);
			orderRecordWriter.close();
			activeOrdersRS.close();
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
			//Also load password files if they exist
			String siteSpecificPassword = "../../sites/" + serverName + "/conf/config.pwd.ini";
			logger.info("Loading password config from " + siteSpecificPassword);
			File siteSpecificPasswordFile = new File(siteSpecificPassword);
			if (siteSpecificPasswordFile.exists()) {
				Ini siteSpecificPwdIni = new Ini();
				siteSpecificPwdIni.load(new FileReader(siteSpecificPasswordFile));
				for (Section curSection : siteSpecificPwdIni.values()){
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

	private static String sierraAPIToken;
	private static String sierraAPITokenType;
	private static long sierraAPIExpiration;
	private static boolean connectToSierraAPI(Ini configIni, String baseUrl){
		//Check to see if we already have a valid token
		if (sierraAPIToken != null){
			if (sierraAPIExpiration - new Date().getTime() > 0){
				//logger.debug("token is still valid");
				return true;
			}else{
				logger.debug("Token has exipred");
			}
		}
		//Connect to the API to get our token
		HttpURLConnection conn;
		try {
			URL emptyIndexURL = new URL(baseUrl + "/token");
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
			String clientKey = cleanIniValue(configIni.get("Catalog", "clientKey"));
			String clientSecret = cleanIniValue(configIni.get("Catalog", "clientSecret"));
			String encoded = Base64.encodeBase64String((clientKey + ":" + clientSecret).getBytes());
			conn.setRequestProperty("Authorization", "Basic "+encoded);
			conn.setDoOutput(true);
			OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream(), "UTF8");
			wr.write("grant_type=client_credentials");
			wr.flush();
			wr.close();

			StringBuilder response = new StringBuilder();
			if (conn.getResponseCode() == 200) {
				// Get the response
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				rd.close();
				JSONObject parser = new JSONObject(response.toString());
				sierraAPIToken = parser.getString("access_token");
				sierraAPITokenType = parser.getString("token_type");
				logger.debug("Token expires at " + parser.getLong("expires_in"));
				sierraAPIExpiration = new Date().getTime() + (parser.getLong("expires_in") * 1000) - 10000;
				//logger.debug("OverDrive token is " + sierraAPIToken);
			} else {
				logger.error("Received error " + conn.getResponseCode() + " connecting to overdrive authentication service" );
				// Get any errors
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				logger.debug("  Finished reading response\r\n" + response);

				rd.close();
				return false;
			}

		} catch (Exception e) {
			logger.error("Error connecting to overdrive API", e );
			return false;
		}
		return true;
	}

	private static JSONObject callSierraApiURL(Ini configIni, String baseUrl, String overdriveUrl) {
		if (connectToSierraAPI(configIni, baseUrl)){
			//Connect to the API to get our token
			HttpURLConnection conn;
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
				conn.setRequestProperty("Authorization", sierraAPITokenType + " " + sierraAPIToken);

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
					return new JSONObject(response.toString());
				} else {
					logger.error("Received error " + conn.getResponseCode() + " connecting to sierra API");
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
				logger.debug("Error loading data from overdrive API ", e );
			}
		}
		return null;
	}

	/**
	 * Calculates a check digit for a III identifier
	 * @param basedId String the base id without checksum
	 * @return String the check digit
	 */
	public static String getCheckDigit(String basedId) {
		if (basedId.length() != 7){
			return "a";
		}else{
			int sumOfDigits = 0;
			for (int i = 0; i < 7; i++){
				sumOfDigits += (8 - i) * Integer.parseInt(basedId.substring(i, i+1));
			}
			int modValue = sumOfDigits % 11;
			if (modValue == 10){
				return "x";
			}else{
				return Integer.toString(modValue);
			}
		}

	}
}
