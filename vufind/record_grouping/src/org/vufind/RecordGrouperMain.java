package org.vufind;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.MarcStreamWriter;
import org.marc4j.MarcWriter;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.io.*;
import java.nio.charset.Charset;
import java.sql.*;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;
import java.util.zip.CRC32;

/**
 * Groups records so that we can show single multiple titles as one rather than as multiple lines.
 *
 * Grouping happens at 3 different levels:
 *
 */
public class RecordGrouperMain {

	private static Logger logger	= Logger.getLogger(RecordGrouperMain.class);
	private static String serverName;

	public static String groupedWorkTableName = "grouped_work";
	public static String groupedWorkIdentifiersTableName = "grouped_work_identifiers";
	public static String groupedWorkIdentifiersRefTableName = "grouped_work_identifiers_ref";
	public static String groupedWorkPrimaryIdentifiersTableName = "grouped_work_primary_identifiers";

	private static HashMap<String, Long> marcRecordChecksums = new HashMap<String, Long>();
	private static PreparedStatement insertMarcRecordChecksum;
	private static PreparedStatement updateMarcRecordChecksum;

	public static void main(String[] args) {
		// Get the configuration filename
		if (args.length == 0) {
			System.out.println("Please enter the server to index as the first parameter");
			System.exit(1);
		}
		serverName = args[0];
		long processStartTime = new Date().getTime();

		// Initialize the logger
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.grouping.properties");
		if (log4jFile.exists()) {
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		} else {
			System.out.println("Could not find log4j configuration " + log4jFile.getAbsolutePath());
			System.exit(1);
		}

		logger.debug("Starting grouping of records " + new Date().toString());

		// Parse the configuration file
		Ini configIni = loadConfigFile("config.ini");

		//Connect to the database
		Connection vufindConn = null;
		Connection econtentConnection = null;
		try{
			String databaseConnectionInfo = cleanIniValue(configIni.get("Database", "database_vufind_jdbc"));
			vufindConn = DriverManager.getConnection(databaseConnectionInfo);
			String econtentDBConnectionInfo = cleanIniValue(configIni.get("Database", "database_econtent_jdbc"));
			econtentConnection = DriverManager.getConnection(econtentDBConnectionInfo);
		}catch (Exception e){
			System.out.println("Error connecting to database " + e.toString());
			System.exit(1);
		}

		//Load MARC Existing MARC Record checksums from VuFind
		try{
			PreparedStatement loadIlsMarcChecksums = vufindConn.prepareStatement("SELECT * from ils_marc_checksums",  ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			insertMarcRecordChecksum = vufindConn.prepareStatement("INSERT INTO ils_marc_checksums (ilsId, checksum) VALUES (?, ?)");
			updateMarcRecordChecksum = vufindConn.prepareStatement("UPDATE ils_marc_checksums SET checksum = ? WHERE ilsId = ?");
			ResultSet ilsMarcChecksumRS = loadIlsMarcChecksums.executeQuery();
			while (ilsMarcChecksumRS.next()){
				marcRecordChecksums.put(ilsMarcChecksumRS.getString("ilsId"), ilsMarcChecksumRS.getLong("checksum"));
			}
			ilsMarcChecksumRS.close();

		}catch (Exception e){
			logger.error("Error loading marc checksums for ILS records", e);
			System.exit(1);
		}


		RecordGroupingProcessor recordGroupingProcessor = new RecordGroupingProcessor(vufindConn, configIni, logger);
		//Clear the database first
		boolean clearDatabasePriorToGrouping = true;
		boolean groupIlsRecords = true;
		boolean groupOverDriveRecords = true;

		if (clearDatabasePriorToGrouping){
			try{
				vufindConn.prepareStatement("TRUNCATE " + groupedWorkTableName).executeUpdate();
				vufindConn.prepareStatement("TRUNCATE " + groupedWorkIdentifiersTableName).executeUpdate();
				vufindConn.prepareStatement("TRUNCATE " + groupedWorkIdentifiersRefTableName).executeUpdate();
				vufindConn.prepareStatement("TRUNCATE " + groupedWorkPrimaryIdentifiersTableName).executeUpdate();
			}catch (Exception e){
				System.out.println("Error clearing database " + e.toString());
				System.exit(1);
			}
		}

		int numRecordsProcessed = 0;

		//Group records from OverDrive
		if (groupOverDriveRecords){
			try{
				PreparedStatement overDriveRecordsStmt = econtentConnection.prepareStatement("SELECT id, overdriveId, mediaType, title, subtitle, primaryCreatorRole, primaryCreatorName FROM overdrive_api_products WHERE deleted = 0", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				PreparedStatement overDriveIdentifiersStmt = econtentConnection.prepareStatement("SELECT * FROM overdrive_api_product_identifiers WHERE id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				PreparedStatement overDriveCreatorStmt = econtentConnection.prepareStatement("SELECT fileAs FROM overdrive_api_product_creators WHERE productId = ? AND role like ? ORDER BY id", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet overDriveRecordRS = overDriveRecordsStmt.executeQuery();
				while (overDriveRecordRS.next()){
					Long id = overDriveRecordRS.getLong("id");

					String overdriveId = overDriveRecordRS.getString("overdriveId");
					String mediaType = overDriveRecordRS.getString("mediaType");
					String title = overDriveRecordRS.getString("title");
					String subtitle = overDriveRecordRS.getString("subtitle");
					String primaryCreatorRole = overDriveRecordRS.getString("primaryCreatorRole");
					String author = overDriveRecordRS.getString("primaryCreatorName");
					//primary creator in overdrive is always first name, last name.  Therefore, we need to look in the creators table
					if (author != null){
						overDriveCreatorStmt.setLong(1, id);
						overDriveCreatorStmt.setString(2, primaryCreatorRole);
						ResultSet creatorInfoRS = overDriveCreatorStmt.executeQuery();
						boolean swapFirstNameLastName = false;
						if (creatorInfoRS.next()){
							String tmpAuthor = creatorInfoRS.getString("fileAs");
							if (tmpAuthor.equals(author) && (mediaType.equals("ebook") || mediaType.equals("audiobook"))){
								swapFirstNameLastName = true;
							}else{
								author = tmpAuthor;
							}
						} else {
							swapFirstNameLastName = true;
						}
						if (swapFirstNameLastName){
							if (author.contains(" ")){
								String[] authorParts = author.split("\\s+");
								StringBuilder tmpAuthor = new StringBuilder();
								for (int i = 1; i < authorParts.length; i++){
									tmpAuthor.append(authorParts[i]).append(" ");
								}
								tmpAuthor.append(authorParts[0]);
								author = tmpAuthor.toString();
							}
						}
						creatorInfoRS.close();
					}

					overDriveIdentifiersStmt.setLong(1, id);
					ResultSet overDriveIdentifierRS = overDriveIdentifiersStmt.executeQuery();
					HashSet<RecordIdentifier> overDriveIdentifiers = new HashSet<RecordIdentifier>();
					RecordIdentifier primaryIdentifier = new RecordIdentifier();
					primaryIdentifier.setValue("overdrive", overdriveId);
					while (overDriveIdentifierRS.next()){
						RecordIdentifier identifier = new RecordIdentifier();
						identifier.setValue(overDriveIdentifierRS.getString("type"), overDriveIdentifierRS.getString("value"));
						if (identifier.isValid()){
							overDriveIdentifiers.add(identifier);
						}
					}

					recordGroupingProcessor.processRecord(primaryIdentifier, title, subtitle, author, mediaType, overDriveIdentifiers);
					numRecordsProcessed++;
				}
				overDriveRecordRS.close();
				logger.info("Finished grouping " + numRecordsProcessed + " records from overdrive ");
			}catch (Exception e){
				System.out.println("Error loading OverDrive records: " + e.toString());
				e.printStackTrace();
			}
		}
		//logger.debug("-----------------------------------------------------------");
		//logger.debug("Finished processing OverDrive records");
		//recordGroupingProcessor.dumpStats();

		if (groupIlsRecords){
			String individualMarcPath = configIni.get("Reindex", "individualMarcPath");
			String marcPath = configIni.get("Reindex", "marcPath");
			File[] catalogBibFiles = new File(marcPath).listFiles();
			if (catalogBibFiles != null){
				for (File curBibFile : catalogBibFiles){
					if (curBibFile.getName().endsWith(".mrc") || curBibFile.getName().endsWith(".marc")){
						System.out.println("Processing " + curBibFile);
						try{
							FileInputStream marcFileStream = new FileInputStream(curBibFile);
							MarcReader catalogReader = new MarcPermissiveStreamReader(marcFileStream, true, true, "UTF8");
							while (catalogReader.hasNext()){
								Record curBib = catalogReader.next();
								recordGroupingProcessor.processMarcRecord(curBib);

								writeIndividualMarc(individualMarcPath, curBib);

								numRecordsProcessed++;
								/*if (numRecordsProcessed % 25000 == 0){
									long elapsedTime = new Date().getTime() - startTime;
									recordGroupingProcessor.dumpStats();
									long minutesWritingMarcs = timeSpentWritingMarcRecords / (60 * 1000);
									logger.debug("Spent " + minutesWritingMarcs + " minutes writing marc records");
								}*/
							}
							marcFileStream.close();
						}catch(Exception e){
							logger.error("Error loading catalog bibs: ", e);
						}
						logger.info("Finished grouping " + numRecordsProcessed + " records from the ils file " + curBibFile.getName());
					}
				}
			}
		}

		//TODO: Group records from other sources

		//TODO: Do fuzzy matching for any identifiers that link to more than one grouped work.

		recordGroupingProcessor.dumpStats();

		try{
			vufindConn.close();
		}catch (Exception e){
			logger.error("Error closing database ", e);
			System.exit(1);
		}
		logger.info("Finished grouping records " + new Date().toString());
		long endTime = new Date().getTime();
		long elapsedTime = endTime - processStartTime;
		logger.info("Elapsed Minutes " + (elapsedTime / 60000));
	}

	private static void writeIndividualMarc(String individualMarcPath, Record marcRecord) {
		//Copy the record to the individual marc path
		DataField field907 = (DataField)marcRecord.getVariableField("907");
		String recordNumber = null;
		if (field907 != null) {
			Subfield subfieldA = field907.getSubfield('a');
			if (subfieldA != null && subfieldA.getData().length() > 2){
				if (field907.getSubfield('a').getData().substring(0,2).equals(".b")){
					recordNumber = field907.getSubfield('a').getData();
				}
			}
		}
		if (recordNumber != null){
			boolean marcRecordUpToDate = false;
			boolean marcRecordExists = false;
			long checksum = getChecksum(marcRecord);
			if (marcRecordChecksums.containsKey(recordNumber)){
				marcRecordExists = true;
				if (checksum == marcRecordChecksums.get(recordNumber)){
					marcRecordUpToDate = true;
				}
			}
			if (!marcRecordUpToDate){
				String shortId = recordNumber.replace(".", "");
				String firstChars = shortId.substring(0, 4);
				String basePath = individualMarcPath + "/" + firstChars;
				String individualFilename = basePath + "/" + shortId + ".mrc";
				File individualFile = new File(individualFilename);
				File baseFile = new File(basePath);
				if (!baseFile.exists()){
					if (!baseFile.mkdirs()){
						System.out.println("Could not create directory to store individual marc");
					}
				}

				try {
					OutputStreamWriter writer = new OutputStreamWriter(new FileOutputStream(individualFile,false), Charset.forName("UTF-8").newEncoder());
					ByteArrayOutputStream out = new ByteArrayOutputStream();
					MarcWriter writer2 = new MarcStreamWriter(out, "UTF-8");
					writer2.write(marcRecord);
					writer2.close();

					String result = null;
					try {
						result = out.toString("UTF-8");
					} catch (UnsupportedEncodingException e) {
						// e.printStackTrace();
						System.out.println(e.getCause());
					}
					if (result != null){
						writer.write(result);
					}
					writer.close();

					try{
						if (!marcRecordExists){
							insertMarcRecordChecksum.setString(1, recordNumber);
							insertMarcRecordChecksum.setLong(2, checksum);
							insertMarcRecordChecksum.executeUpdate();
						}else{
							updateMarcRecordChecksum.setLong(1, checksum);
							updateMarcRecordChecksum.setString(2, recordNumber);
							updateMarcRecordChecksum.executeUpdate();
						}
					}catch (SQLException e){
						logger.error("Unable to update checksum for ils marc record", e);
					}
				} catch (IOException e) {
					logger.error("Error writing marc", e);
				}
			}
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

	public static long getChecksum(Record marcRecord) {
		CRC32 crc32 = new CRC32();
		crc32.update(marcRecord.toString().getBytes());
		return crc32.getValue();
	}
}
