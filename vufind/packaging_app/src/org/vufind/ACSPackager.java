package org.vufind;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.IOException;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;
import java.util.HashMap;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile.Section;

import com.adobe.adept.upload.PackageTool;

public class ACSPackager {
	private static Logger logger	= Logger.getLogger(ACSPackager.class);
	private static Ini configIni;
	private static File packagingRootDir;
	private static String packagingUrl;
	private static String distributionUrl;
	private static HashMap<String, String> distributorSecrets = new HashMap<String, String>();
	//Database connections and prepared statements
	private static Connection packagingConn = null;
	private static PreparedStatement getNextFileToPackage;
	private static PreparedStatement setErrorStmt;
	private static PreparedStatement startPackagingStmt;
	private static PreparedStatement setAcsIdStmt;
	
	/**
	 * Starts the ACS packaging process
	 * 
	 * @param args
	 */
	public static void main(String[] args) {
		// Initialize the logger
		File log4jFile = new File("log4j.properties");
		if (log4jFile.exists()) {
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		} else {
			System.out.println("Could not find log4j configuration " + log4jFile.getAbsolutePath());
			System.exit(1);
		}
		//Load configuration
		loadConfig(args);
		
		//Connect to the database
		logger.debug("Connecting to database");
		connectToDatabase();
		
		//Get the next file to be processed
		while (true){
			logger.debug("Getting next file to package");
			try {
				ResultSet nextFileRS = getNextFileToPackage.executeQuery();
				if (nextFileRS.next()){
					Long id = nextFileRS.getLong("id");
					String distributorId = nextFileRS.getString("distributorId");
					String distributorIdShort = distributorId.replace("urn:uuid:", "");
					Long copies = nextFileRS.getLong("copies");
					String filename = nextFileRS.getString("filename");
					String previousAcsId = nextFileRS.getString("previousAcsId");
					
					//Make sure the file exists 
					String fullPathToFile = packagingRootDir.getPath() + File.separator + distributorIdShort + File.separator + filename;
					File fileToPackage = new File(fullPathToFile);
					logger.debug("Packaging " + fullPathToFile);
					//Mark that the packaging process has started
					startPackagingStmt.setLong(1, new Date().getTime() / 1000);
					startPackagingStmt.setLong(2, new Date().getTime() / 1000);
					startPackagingStmt.setLong(3, id);
					startPackagingStmt.executeUpdate();
					if (fileToPackage.exists()){
						ACSResult result = packageFile(distributorId, fullPathToFile, copies, previousAcsId);
						if (result.isSuccess()){
							setAcsIdStmt.setString(1, result.getAcsId());
							setAcsIdStmt.setLong(2, new Date().getTime() / 1000);
							setAcsIdStmt.setLong(3, new Date().getTime() / 1000);
							setAcsIdStmt.setLong(4, id);
							setAcsIdStmt.executeUpdate();
						}else{
							setErrorStmt.setString(1, result.getAcsError());
							setErrorStmt.setLong(2, new Date().getTime() / 1000);
							setErrorStmt.setLong(3, new Date().getTime() / 1000);
							setErrorStmt.setLong(4, id);
							setErrorStmt.executeUpdate();
						}
					}else{
						//Indicate that an error occurred
						logger.debug("File " + fullPathToFile + " does not exist");
						setErrorStmt.setString(1, "The file does not exist");
						setErrorStmt.setLong(2, new Date().getTime() / 1000);
						setErrorStmt.setLong(3, new Date().getTime() / 1000);
						setErrorStmt.setLong(4, id);
						setErrorStmt.executeUpdate();
					}
					//Update the status of the file
				}else{
					//No files to process, rest for a minute
					try {
						Thread.sleep(60 * 1000);
					} catch (InterruptedException e) {
						logger.error("Thread was interrupted, quitting", e);
						break;
					}
				}
			} catch (SQLException e) {
				logger.error("Error getting next file to package", e);
				break;
			}
		}
		
		//Close the conenections
		if (packagingConn != null){
			try {
				packagingConn.close();
			} catch (SQLException e) {
				logger.error("Could not close database connection", e);
			}
		}
	}

	private static ACSResult packageFile(String distributorId, String filename, Long copies, String previousAcsId) {
		PackageTool packager = new PackageTool(packagingUrl, distributionUrl);
		String distributorSecret = distributorSecrets.get(distributorId);
		if (distributorSecret == null){
			ACSResult result = new ACSResult();
			result.setSuccess(false);
			result.setAcsError("Distributor secret not found for id " + distributorId + ", please add shared secret to configuration file");
			return result;
		}
		String[] uploadFlags = new String[]{
			"-verbose",
		};
		packager.setFlags(uploadFlags);
		packager.setHmacKey(distributorSecret);
		if (previousAcsId != null && previousAcsId.length() > 0){
			packager.setAction("replace");
			packager.setResource(previousAcsId);
		}
		
		return packager.packageFile(filename, distributorId, copies);
	}

	private static void connectToDatabase() {
		String databaseConnectionInfo = Util.cleanIniValue(configIni.get("Database", "database_packaging_jdbc"));
		if (databaseConnectionInfo == null || databaseConnectionInfo.length() == 0) {
			logger.error("Packaging Database connection information not found in Database Section.  Please specify connection information in database_packaging_jdbc.");
			System.exit(1);
		}
		try {
			packagingConn = DriverManager.getConnection(databaseConnectionInfo);
			
			//Prepare statements that will be needed later
			getNextFileToPackage = packagingConn.prepareStatement("SELECT * FROM acs_packaging_log WHERE status = 'pending' ORDER BY created ASC LIMIT 1");
			startPackagingStmt = packagingConn.prepareStatement("UPDATE acs_packaging_log set packagingStartTime = ?, status = 'sentToAcsServer', lastUpdate = ? WHERE id = ?");
			setErrorStmt = packagingConn.prepareStatement("UPDATE acs_packaging_log set acsError = ?, status = 'acsError', lastUpdate = ?, packagingEndTime = ? WHERE id = ?");
			setAcsIdStmt = packagingConn.prepareStatement("UPDATE acs_packaging_log set acsId = ?, status = 'acsIdGenerated', lastUpdate = ?, packagingEndTime = ? WHERE id = ?");
		} catch (SQLException e) {
			logger.error("Could not connect to packaging database and setup prepared statements", e);
			System.exit(1);
		}
	}

	private static void loadConfig(String[] args) {
		// Load the configuration file
		String configName = "config.ini";
		logger.info("Loading configuration from " + configName);
		File configFile = new File(configName);
		if (!configFile.exists()) {
			logger.error("Could not find configuration file " + configName);
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
		
		String packagingRootDirStr = Util.cleanIniValue(configIni.get("Packaging", "rootPackagingDir"));
		if (packagingRootDirStr == null || packagingRootDirStr.length() == 0) {
			logger.error("Root Packaging Directory " + packagingRootDirStr + " not found in Packaging Section.  Please specify the directory where unencrypted files can be found in rootPackagingDir.");
			System.exit(1);
		}
		packagingRootDir = new File(packagingRootDirStr);
		if (packagingRootDir.exists() == false){
			logger.error("Root Packaging Directory " + packagingRootDirStr + "does not exist, stopping.");
			System.exit(1);
		}
		
		packagingUrl = Util.cleanIniValue(configIni.get("Packaging", "packagingUrl"));
		if (packagingUrl == null || packagingUrl.length() == 0) {
			logger.error("Packaging URL not found in Packaging Section.  Please specify the URL for the packaging service.");
			System.exit(1);
		}
		
		distributionUrl = Util.cleanIniValue(configIni.get("Packaging", "distributionUrl"));
		if (distributionUrl == null || distributionUrl.length() == 0) {
			logger.error("Distribution URL not found in Packaging Section.  Please specify the URL for the distribution service.");
			System.exit(1);
		}
		
		//Get the shared secrets for the distributors
		Section distributorSecretSection = configIni.get("DistributorSecrets");
		for (String distributorId : distributorSecretSection.keySet()){
			distributorSecrets.put(distributorId, Util.cleanIniValue(distributorSecretSection.get(distributorId)));
		}
	}
}
