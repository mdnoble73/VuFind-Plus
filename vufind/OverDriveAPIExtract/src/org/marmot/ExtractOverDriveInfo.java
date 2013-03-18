package org.marmot;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.net.HttpURLConnection;
import java.net.URL;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;
import java.util.HashMap;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLSession;

import org.apache.commons.codec.binary.Base64;
import org.apache.log4j.Logger;
import org.marmot.OverDriveRecordInfo;
import org.ini4j.Ini;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

public class ExtractOverDriveInfo {
	private static Logger logger = Logger.getLogger(ExtractOverDriveInfo.class);
	private Ini configIni;
	private Connection econtentConn;
	private OverDriveExtractLogEntry results;
	
	//Overdrive API information
	private String clientSecret;
	private String clientKey;
	private String accountId;
	private String overDriveAPIToken;
	private String overDriveAPITokenType;
	private long overDriveAPIExpiration;
	
	private HashMap<String, OverDriveRecordInfo> overDriveTitles = new HashMap<String, OverDriveRecordInfo>();
	private HashMap<String, Long> advantageCollectionToLibMap = new HashMap<String, Long>();
	
	public void extractOverDriveInfo(Ini configIni, Connection vufindConn, Connection econtentConn, OverDriveExtractLogEntry logEntry ) {
		this.configIni = configIni;
		this.econtentConn = econtentConn;
		this.results = logEntry;
		
		try {
			PreparedStatement advantageCollectionMapStmt = vufindConn.prepareStatement("SELECT libraryId, overdriveAdvantageName, overdriveAdvantageProductsKey FROM library where overdriveAdvantageName > ''");
			ResultSet advantageCollectionMapRS = advantageCollectionMapStmt.executeQuery();
			while (advantageCollectionMapRS.next()){
				advantageCollectionToLibMap.put(advantageCollectionMapRS.getString(2), advantageCollectionMapRS.getLong(1));
			}
			
			//Load products from API 
			String clientSecret = configIni.get("OverDrive", "clientSecret");
			String clientKey = configIni.get("OverDrive", "clientKey");
			String accountId = configIni.get("OverDrive", "accountId");
			
			if (clientSecret == null && clientKey == null && accountId == null){
				logEntry.addNote("Did not find correct configuration in config.ini, not loading overdrive titles");
			}else{
				//Load library products
				loadLibraryProducts();
			}
		} catch (SQLException e) {
		// handle any errors
			logger.error("Error initializing overdrive extraction", e);
			results.addNote("Error initializing overdrive extraction " + e.toString());
			results.incErrors();
			results.saveResults();
		}
	}
	private void loadLibraryProducts() {
		JSONObject libraryInfo = callOverDriveURL("http://api.overdrive.com/v1/libraries/" + accountId);
		try {
			String libraryName = libraryInfo.getString("name");
			String mainProductUrl = libraryInfo.getJSONObject("links").getJSONObject("products").getString("href");
			loadProductsFromUrl(libraryName, mainProductUrl, false);
			logger.debug("loaded " + overDriveTitles.size() + " overdrive titles in shared collection");
			//Get a list of advantage collections
			if (libraryInfo.getJSONObject("links").has("advantageAccounts")){
				JSONObject advantageInfo = callOverDriveURL(libraryInfo.getJSONObject("links").getJSONObject("advantageAccounts").getString("href"));
				if (advantageInfo.has("advantageAccounts")){
					JSONArray advantageAccounts = advantageInfo.getJSONArray("advantageAccounts");
					for (int i = 0; i < advantageAccounts.length(); i++){
						JSONObject curAdvantageAccount = advantageAccounts.getJSONObject(i);
						String advantageSelfUrl = curAdvantageAccount.getJSONObject("links").getJSONObject("self").getString("href");
						JSONObject advantageSelfInfo = callOverDriveURL(advantageSelfUrl);
						String advantageName = curAdvantageAccount.getString("name");
						String productUrl = advantageSelfInfo.getJSONObject("links").getJSONObject("products").getString("href");
						loadProductsFromUrl(advantageName, productUrl, true);
					}
				}else{
					results.addNote("The API indicate that the library has advantage accounts, but none were returned from " + libraryInfo.getJSONObject("links").getJSONObject("advantageAccounts").getString("href"));
					results.incErrors();
				}
				logger.debug("loaded " + overDriveTitles.size() + " overdrive titles in shared collection and advantage collections");
			}
		} catch (Exception e) {
			results.addNote("error loading information from OverDrive API " + e.toString());
			results.incErrors();
			logger.error("Error loading overdrive titles", e);
		}
	}
	
	private void loadProductsFromUrl(String libraryName, String mainProductUrl, boolean isAdvantage) throws JSONException {
		JSONObject productInfo = callOverDriveURL(mainProductUrl);
		long numProducts = productInfo.getLong("totalItems");
		//if (numProducts > 50) numProducts = 50;
		logger.debug(libraryName + " collection has " + numProducts + " products in it");
		results.addNote("Loading OverDrive information for " + libraryName);
		results.saveResults();
		long batchSize = 300;
		Long libraryId = getLibraryIdForOverDriveAccount(libraryName);
		for (int i = 0; i < numProducts; i += batchSize){
			logger.debug("Processing " + libraryName + " batch from " + i + " to " + (i + batchSize));
			String batchUrl = mainProductUrl + "?offset=" + i + "&limit=" + batchSize;
			JSONObject productBatchInfo = callOverDriveURL(batchUrl);
			JSONArray products = productBatchInfo.getJSONArray("products");
			for(int j = 0; j <products.length(); j++ ){
				JSONObject curProduct = products.getJSONObject(j);
				OverDriveRecordInfo curRecord = loadOverDriveRecordFromJSON(libraryName, curProduct);
				if (libraryId == -1){
					curRecord.setShared(true);
				}
				if (overDriveTitles.containsKey(curRecord.getId())){
					OverDriveRecordInfo oldRecord = overDriveTitles.get(curRecord.getId());
					oldRecord.getCollections().add(libraryId);
				}else{
					//logger.debug("Loading record " + curRecord.getId());
					overDriveTitles.put(curRecord.getId(), curRecord);
				}
			}
		}
	}
	
	private OverDriveRecordInfo loadOverDriveRecordFromJSON(String libraryName, JSONObject curProduct) throws JSONException {
		OverDriveRecordInfo curRecord = new OverDriveRecordInfo();
		curRecord.setId(curProduct.getString("id"));
		//logger.debug("Processing overdrive title " + curRecord.getId());
		curRecord.setTitle(curProduct.getString("title"));
		curRecord.setMediaType(curProduct.getString("mediaType"));
		if (curProduct.has("series")){
			curRecord.setSeries(curProduct.getString("series"));
		}
		if (curProduct.has("primaryCreator")){
			curRecord.setAuthor(curProduct.getJSONObject("primaryCreator").getString("name"));
		}
		for (int k = 0; k < curProduct.getJSONArray("formats").length(); k++){
			curRecord.getFormats().add(curProduct.getJSONArray("formats").getJSONObject(k).getString("id"));
		}
		if (curProduct.has("images") && curProduct.getJSONObject("images").has("thumbnail")){
			curRecord.setCoverImage(curProduct.getJSONObject("images").getJSONObject("thumbnail").getString("href"));
		}
		curRecord.getCollections().add(getLibraryIdForOverDriveAccount(libraryName));
		return curRecord;
	}
	
	private Long getLibraryIdForOverDriveAccount(String libraryName) {
		if (advantageCollectionToLibMap.containsKey(libraryName)){
			return advantageCollectionToLibMap.get(libraryName);
		}
		return -1L;
	}
	
	private JSONObject callOverDriveURL(String overdriveUrl) {
		int maxConnectTries = 5;
		for (int connectTry = 1 ; connectTry < maxConnectTries; connectTry++){
			if (connectToOverDriveAPI(connectTry != 1)){
				if (connectTry != 1){
					logger.debug("Connecting to " + overdriveUrl + " try " + connectTry);
				}
				//Connect to the API to get our token
				HttpURLConnection conn = null;
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
					conn.setRequestProperty("Authorization", overDriveAPITokenType + " " + overDriveAPIToken);
					
					StringBuffer response = new StringBuffer();
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
						logger.error("Received error " + conn.getResponseCode() + " connecting to overdrive API try " + connectTry );
						// Get any errors
						BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
						String line;
						while ((line = rd.readLine()) != null) {
							response.append(line);
						}
						logger.debug("  Finished reading response");
	
						rd.close();
					}
	
				} catch (Exception e) {
					logger.debug("Error loading data from overdrive API try " + connectTry, e );
				}
			}
		}
		logger.error("Failed to call overdrive url " +overdriveUrl + " in " + maxConnectTries + " calls");
		results.addNote("Failed to call overdrive url " +overdriveUrl + " in " + maxConnectTries + " calls");
		results.saveResults();
		return null;
	}

	private boolean connectToOverDriveAPI(boolean getNewToken){
		//Check to see if we already have a valid token
		if (overDriveAPIToken != null && getNewToken == false){
			if (overDriveAPIExpiration - new Date().getTime() > 0){
				return true;
			}
		}
		//Connect to the API to get our token
		HttpURLConnection conn = null;
		try {
			URL emptyIndexURL = new URL("https://oauth.overdrive.com/token");
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
			String encoded = Base64.encodeBase64String(new String(clientKey+":"+clientSecret).getBytes());
			conn.setRequestProperty("Authorization", "Basic "+encoded);
			conn.setDoOutput(true);
			OutputStreamWriter wr = new OutputStreamWriter(conn.getOutputStream(), "UTF8");
			wr.write("grant_type=client_credentials");
			wr.flush();
			wr.close();
			
			StringBuffer response = new StringBuffer();
			if (conn.getResponseCode() == 200) {
				// Get the response
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				rd.close();
				JSONObject parser = new JSONObject(response.toString());
				overDriveAPIToken = parser.getString("access_token");
				overDriveAPITokenType = parser.getString("token_type");
				overDriveAPIExpiration = parser.getLong("expires_in") - 10000;
				//logger.debug("OverDrive token is " + overDriveAPIToken);
			} else {
				logger.error("Received error " + conn.getResponseCode() + " connecting to overdrive authentication service" );
				// Get any errors
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				//logger.debug("  Finished reading response");

				rd.close();
				return false;
			}

		} catch (Exception e) {
			logger.error("Error connecting to overdrive API", e );
			return false;
		}
		return true;
	}
}
