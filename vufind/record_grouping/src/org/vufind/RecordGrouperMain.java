package org.vufind;

import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.MarcStreamWriter;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;

import java.io.*;
import java.nio.charset.Charset;
import java.sql.*;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
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
	private static HashSet<String> marcRecordIdsInDatabase = new HashSet<String>();
	private static PreparedStatement insertMarcRecordChecksum;
	private static PreparedStatement updateMarcRecordChecksum;
	private static PreparedStatement removeMarcRecordChecksum;

	private static String recordNumberTag = "";
	private static String recordNumberPrefix = "";

	private static Long lastGroupingTime;
	private static Long lastGroupingTimeVariableId;
	private static boolean fullRegrouping = false;

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

		logger.warn("Starting grouping of records " + new Date().toString());

		// Parse the configuration file
		Ini configIni = loadConfigFile();

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

		//Get the last grouping time
		try{
			PreparedStatement loadLastGroupingTime = vufindConn.prepareStatement("SELECT * from variables WHERE name = 'last_grouping_time'");
			ResultSet lastGroupingTimeRS = loadLastGroupingTime.executeQuery();
			if (lastGroupingTimeRS.next()){
				lastGroupingTime = lastGroupingTimeRS.getLong("value");
				lastGroupingTimeVariableId = lastGroupingTimeRS.getLong("id");
			}
			lastGroupingTimeRS.close();
			loadLastGroupingTime.close();
		} catch (Exception e){
			logger.error("Error loading last grouping time", e);
			System.exit(1);
		}

		//Check to see if we need to clear the database
		boolean clearDatabasePriorToGrouping = false;
		if (args.length >= 2 && args[1].equalsIgnoreCase("fullRegrouping")){
			clearDatabasePriorToGrouping = true;
			fullRegrouping = true;
		}else{
			fullRegrouping = false;
		}

		RecordGroupingProcessor recordGroupingProcessor = new RecordGroupingProcessor(vufindConn, configIni, logger, fullRegrouping);

		clearDatabase(vufindConn, clearDatabasePriorToGrouping);
		loadIlsChecksums(vufindConn);

		groupOverDriveRecords(econtentConnection, recordGroupingProcessor);
		groupIlsRecords(configIni, recordGroupingProcessor);
		removeGroupedWorksWithoutPrimaryIdentifiers(vufindConn);
		removeUnlinkedIdentifiers(vufindConn);
		makeIdentifiersLinkingToMultipleWorksInvalidForEnrichment(vufindConn);
		updateLastGroupingTime(vufindConn);


		recordGroupingProcessor.dumpStats();

		try{
			vufindConn.close();
		}catch (Exception e){
			logger.error("Error closing database ", e);
			System.exit(1);
		}
		logger.warn("Finished grouping records " + new Date().toString());
		long endTime = new Date().getTime();
		long elapsedTime = endTime - processStartTime;
		logger.warn("Elapsed Minutes " + (elapsedTime / 60000));
	}

	private static void updateLastGroupingTime(Connection vufindConn) {
		//Update the last grouping time in the variables table
		try{
			Long finishTime = new Date().getTime() / 1000;
			if (lastGroupingTimeVariableId != null){
				PreparedStatement updateVariableStmt  = vufindConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
				updateVariableStmt.setLong(1, finishTime);
				updateVariableStmt.setLong(2, lastGroupingTimeVariableId);
				updateVariableStmt.executeUpdate();
				updateVariableStmt.close();
			} else{
				PreparedStatement insertVariableStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('last_grouping_time', ?)");
				insertVariableStmt.setString(1, Long.toString(finishTime));
				insertVariableStmt.executeUpdate();
				insertVariableStmt.close();
			}
		}catch (Exception e){
			logger.error("Error setting last grouping time", e);
		}
	}

	private static void makeIdentifiersLinkingToMultipleWorksInvalidForEnrichment(Connection vufindConn) {
		//Mark any identifiers that link to more than one grouped record and therefore should not be used for enrichment
		try{
			PreparedStatement invalidIdentifiersStmt = vufindConn.prepareStatement("SELECT grouped_work_primary_to_secondary_id_ref.`secondary_identifier_id`, COUNT(grouped_work_id) as num_related_works FROM `grouped_work_primary_to_secondary_id_ref` INNER JOIN grouped_work_primary_identifiers ON `primary_identifier_id` = grouped_work_primary_identifiers.id GROUP BY secondary_identifier_id HAVING num_related_works > 1");
			ResultSet invalidIdentifiersRS = invalidIdentifiersStmt.executeQuery();
			PreparedStatement updateInvalidIdentifierStmt = vufindConn.prepareStatement("UPDATE grouped_work_identifiers SET valid_for_enrichment = 0 where id = ?");
			while (invalidIdentifiersRS.next()){
				updateInvalidIdentifierStmt.setLong(1, invalidIdentifiersRS.getLong("secondary_identifier_id"));
				updateInvalidIdentifierStmt.executeUpdate();
			}
			invalidIdentifiersRS.close();
			invalidIdentifiersStmt.close();
		}catch (Exception e){
			logger.error("Unable to mark identifiers as invalid for enrichment", e);
		}
	}

	private static void removeUnlinkedIdentifiers(Connection vufindConn) {
		//Remove any identifiers that are no longer linked to a primary identifier
		try{
			PreparedStatement unlinkedIdentifiersStmt = vufindConn.prepareStatement("SELECT grouped_work_identifiers.id, count(primary_identifier_id) as num_primary_identifiers from grouped_work_identifiers left join grouped_work_primary_to_secondary_id_ref on grouped_work_identifiers.id = secondary_identifier_id GROUP BY secondary_identifier_id having num_primary_identifiers = 0");
			ResultSet unlinkedIdentifiersRS = unlinkedIdentifiersStmt.executeQuery();
			PreparedStatement removeIdentifierStmt = vufindConn.prepareStatement("DELETE FROM grouped_work_identifiers where id = ?");
			while (unlinkedIdentifiersRS.next()){
				removeIdentifierStmt.setLong(1, unlinkedIdentifiersRS.getLong(1));
				removeIdentifierStmt.executeUpdate();
			}
			unlinkedIdentifiersRS.close();
			unlinkedIdentifiersStmt.close();
		}catch(Exception e){
			logger.error("Error removing identifiers that are no longer linked to a primary identifier", e);
		}
	}

	private static void removeGroupedWorksWithoutPrimaryIdentifiers(Connection vufindConn) {
		//Remove any grouped works that no longer link to a primary identifier
		try{
			PreparedStatement groupedWorksWithoutIdentifiersStmt = vufindConn.prepareStatement("SELECT grouped_work.id, count(identifier) as num_related_records from grouped_work left join grouped_work_primary_identifiers on grouped_work.id = grouped_work_primary_identifiers.grouped_work_id GROUP BY grouped_work.id HAVING num_related_records = 0");
			ResultSet groupedWorksWithoutIdentifiersRS = groupedWorksWithoutIdentifiersStmt.executeQuery();
			PreparedStatement deleteWorkStmt = vufindConn.prepareStatement("DELETE from grouped_work WHERE id = ?");
			PreparedStatement deleteRelatedIdentifiersStmt = vufindConn.prepareStatement("DELETE from grouped_work_identifiers_ref WHERE grouped_work_id = ?");
			while (groupedWorksWithoutIdentifiersRS.next()){
				deleteWorkStmt.setLong(1, groupedWorksWithoutIdentifiersRS.getLong(1));
				deleteWorkStmt.executeUpdate();

				deleteRelatedIdentifiersStmt.setLong(1, groupedWorksWithoutIdentifiersRS.getLong(1));
				deleteRelatedIdentifiersStmt.executeUpdate();
			}
			groupedWorksWithoutIdentifiersRS.close();
		}catch (Exception e){
			logger.error("Unable to remove grouped works that no longer have a primary identifier", e);
		}
	}

	private static void loadIlsChecksums(Connection vufindConn) {
		//Load MARC Existing MARC Record checksums from VuFind
		try{
			insertMarcRecordChecksum = vufindConn.prepareStatement("INSERT INTO ils_marc_checksums (ilsId, checksum) VALUES (?, ?)");
			updateMarcRecordChecksum = vufindConn.prepareStatement("UPDATE ils_marc_checksums SET checksum = ? WHERE ilsId = ?");
			removeMarcRecordChecksum = vufindConn.prepareStatement("DELETE FROM ils_marc_checksums WHERE ilsId = ?");
			if (!fullRegrouping){
				PreparedStatement loadIlsMarcChecksums = vufindConn.prepareStatement("SELECT * from ils_marc_checksums",  ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet ilsMarcChecksumRS = loadIlsMarcChecksums.executeQuery();
				while (ilsMarcChecksumRS.next()){
					marcRecordChecksums.put(ilsMarcChecksumRS.getString("ilsId"), ilsMarcChecksumRS.getLong("checksum"));
					marcRecordIdsInDatabase.add(ilsMarcChecksumRS.getString("ilsId"));
				}
				ilsMarcChecksumRS.close();
			}
		}catch (Exception e){
			logger.error("Error loading marc checksums for ILS records", e);
			System.exit(1);
		}
	}

	private static void clearDatabase(Connection vufindConn, boolean clearDatabasePriorToGrouping) {
		if (clearDatabasePriorToGrouping){
			try{
				vufindConn.prepareStatement("TRUNCATE ils_marc_checksums").executeUpdate();
				vufindConn.prepareStatement("TRUNCATE " + groupedWorkTableName).executeUpdate();
				vufindConn.prepareStatement("TRUNCATE " + groupedWorkIdentifiersTableName).executeUpdate();
				vufindConn.prepareStatement("TRUNCATE " + groupedWorkIdentifiersRefTableName).executeUpdate();
				vufindConn.prepareStatement("TRUNCATE " + groupedWorkPrimaryIdentifiersTableName).executeUpdate();
				vufindConn.prepareStatement("TRUNCATE grouped_work_primary_to_secondary_id_ref").executeUpdate();
			}catch (Exception e){
				System.out.println("Error clearing database " + e.toString());
				System.exit(1);
			}
		}
	}

	private static void groupIlsRecords(Ini configIni, RecordGroupingProcessor recordGroupingProcessor) {
		int numRecordsProcessed = 0;
		String individualMarcPath = configIni.get("Reindex", "individualMarcPath");
		String marcPath = configIni.get("Reindex", "marcPath");

		recordNumberTag = configIni.get("Reindex", "recordNumberTag");
		recordNumberPrefix = configIni.get("Reindex", "recordNumberPrefix");

		String marcEncoding = configIni.get("Reindex", "marcEncoding");

		File[] catalogBibFiles = new File(marcPath).listFiles();
		if (catalogBibFiles != null){
			String lastRecordProcessed = "";
			for (File curBibFile : catalogBibFiles){
				if (curBibFile.getName().endsWith(".mrc") || curBibFile.getName().endsWith(".marc")){
					try{
						FileInputStream marcFileStream = new FileInputStream(curBibFile);
						MarcReader catalogReader = new MarcPermissiveStreamReader(marcFileStream, true, true, marcEncoding);
						while (catalogReader.hasNext()){
							Record curBib = catalogReader.next();
							String recordNumber = getRecordNumberForBib(curBib);
							boolean marcUpToDate = writeIndividualMarc(individualMarcPath, curBib, recordNumber);
							if (!marcUpToDate){
								recordGroupingProcessor.processMarcRecord(curBib);
							}
							//Mark that the record was processed
							marcRecordIdsInDatabase.remove(recordNumber);
							lastRecordProcessed = recordNumber;
							numRecordsProcessed++;
							if (numRecordsProcessed % 100000 == 0){
								recordGroupingProcessor.dumpStats();
							}
						}
						marcFileStream.close();
					}catch(Exception e){
						logger.error("Error loading catalog bibs on record " + numRecordsProcessed + " the last record processed was " + lastRecordProcessed, e);
					}
					logger.warn("Finished grouping " + numRecordsProcessed + " records from the ils file " + curBibFile.getName());
				}
			}

			logger.info("Deleting " + marcRecordIdsInDatabase.size() + " record ids from the database since they are no longer in the export.");
			for (String recordNumber : marcRecordIdsInDatabase){
				if (!fullRegrouping){
					//Remove the record from the grouped work
					RecordIdentifier primaryIdentifier = new RecordIdentifier();
					primaryIdentifier.setValue("ils", recordNumber);
					recordGroupingProcessor.deletePrimaryIdentifier(primaryIdentifier);
				}
				//Remove the record from the ils_marc_checksums table
				try {
					removeMarcRecordChecksum.setString(1, recordNumber);
					removeMarcRecordChecksum.executeUpdate();
				} catch (SQLException e) {
					logger.error("Error removing ILS id " + recordNumber + " from ils_marc_checksums table", e);
				}
			}
		}
	}

	private static String getRecordNumberForBib(Record marcRecord) {
		String recordNumber = null;
		List<VariableField> field907 = marcRecord.getVariableFields(recordNumberTag);
		//Make sure we only get one ils identifier
		for (VariableField cur907 : field907){
			if (cur907 instanceof DataField){
				DataField cur907Data = (DataField)cur907;
				Subfield subfieldA = cur907Data.getSubfield('a');
				if (subfieldA != null && (recordNumberPrefix.length() == 0 || subfieldA.getData().length() > recordNumberPrefix.length())){
					if (cur907Data.getSubfield('a').getData().substring(0,recordNumberPrefix.length()).equals(recordNumberPrefix)){
						recordNumber = cur907Data.getSubfield('a').getData();
						break;
					}
				}
			}
		}
		return recordNumber;
	}

	private static int groupOverDriveRecords(Connection econtentConnection, RecordGroupingProcessor recordGroupingProcessor) {
		int numRecordsProcessed = 0;
		try{
			PreparedStatement overDriveRecordsStmt;
			if (lastGroupingTime != null && !fullRegrouping){
				overDriveRecordsStmt = econtentConnection.prepareStatement("SELECT id, overdriveId, mediaType, title, subtitle, primaryCreatorRole, primaryCreatorName FROM overdrive_api_products WHERE deleted = 0 and (dateUpdated >= ? OR lastMetadataChange >= ? OR lastAvailabilityChange >= ?)", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				overDriveRecordsStmt.setLong(1, lastGroupingTime);
				overDriveRecordsStmt.setLong(2, lastGroupingTime);
				overDriveRecordsStmt.setLong(3, lastGroupingTime);
			}else{
				overDriveRecordsStmt = econtentConnection.prepareStatement("SELECT id, overdriveId, mediaType, title, subtitle, primaryCreatorRole, primaryCreatorName FROM overdrive_api_products WHERE deleted = 0", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			}
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

			if (!fullRegrouping){
				PreparedStatement deletedRecordStmt;
				if (lastGroupingTime == null){
					deletedRecordStmt = econtentConnection.prepareStatement("SELECT overdriveId FROM overdrive_api_products WHERE deleted = 1");
				}else{
					deletedRecordStmt = econtentConnection.prepareStatement("SELECT overdriveId FROM overdrive_api_products WHERE deleted = 1 and dateDeleted >= ?");
					deletedRecordStmt.setLong(1, lastGroupingTime);
				}
				ResultSet recordsToDelete = deletedRecordStmt.executeQuery();
				while (recordsToDelete.next()){
					RecordIdentifier primaryIdentifier = new RecordIdentifier();
					String overdriveId = recordsToDelete.getString("overdriveId");
					primaryIdentifier.setValue("overdrive", overdriveId);
					recordGroupingProcessor.deletePrimaryIdentifier(primaryIdentifier);
				}
			}
			logger.warn("Finished grouping " + numRecordsProcessed + " records from overdrive ");
		}catch (Exception e){
			System.out.println("Error loading OverDrive records: " + e.toString());
			e.printStackTrace();
		}
		return numRecordsProcessed;
	}

	private static boolean writeIndividualMarc(String individualMarcPath, Record marcRecord, String recordNumber) {
		boolean marcRecordUpToDate = false;
		//Copy the record to the individual marc path
		if (recordNumber != null){
			boolean marcRecordExistsInDb = false;
			long checksum = getChecksum(marcRecord);
			File individualFile = getFileForIlsRecord(individualMarcPath, recordNumber);
			if (!fullRegrouping){
				Long existingChecksum = marcRecordChecksums.get(recordNumber);
				if (existingChecksum != null){
					marcRecordExistsInDb = true;
					if (existingChecksum.equals(checksum)){
						marcRecordUpToDate = true;
					}else{
						logger.debug("Checksum for " + recordNumber + " has changed new " + checksum + " old " + existingChecksum + ", need to reindex");
					}
				}
				if (!individualFile.exists()){
					marcRecordUpToDate = false;
				}
			}

			if (!marcRecordUpToDate){
				try {
					outputMarcRecord(marcRecord, individualFile);

					updateMarcRecordChecksum(recordNumber, marcRecordExistsInDb, checksum);
				} catch (IOException e) {
					logger.error("Error writing marc", e);
				}
			}
		}else{
			logger.error("Error did not find record number for MARC record");
			marcRecordUpToDate = true;
		}
		return marcRecordUpToDate;
	}

	private static File getFileForIlsRecord(String individualMarcPath, String recordNumber) {
		String shortId = getFileIdForRecordNumber(recordNumber);
		String firstChars = shortId.substring(0, 4);
		String basePath = individualMarcPath + "/" + firstChars;
		String individualFilename = basePath + "/" + shortId + ".mrc";
		File individualFile = new File(individualFilename);
		createBaseDirectory(basePath);
		return individualFile;
	}

	private static HashSet<String>basePathsValidated = new HashSet<String>();
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

	private static String getFileIdForRecordNumber(String recordNumber) {
		String shortId = recordNumber.replace(".", "");
		while (shortId.length() < 9){
			shortId = "0" + shortId;
		}
		return shortId;
	}

	private static void updateMarcRecordChecksum(String recordNumber, boolean marcRecordExists, long checksum) {
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
	}

	private static void outputMarcRecord(Record marcRecord, File individualFile) throws IOException {
		OutputStreamWriter writer = new OutputStreamWriter(new FileOutputStream(individualFile,false), Charset.forName("UTF-8").newEncoder());
		ByteArrayOutputStream out = new ByteArrayOutputStream();
		MarcStreamWriter writer2 = new MarcStreamWriter(out, "UTF-8");
		writer2.setAllowOversizeEntry(true);
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
	}

	private static Ini loadConfigFile(){
		//First load the default config file
		String configName = "../../sites/default/conf/config.ini";
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
		String siteSpecificFilename = "../../sites/" + serverName + "/conf/config.ini";
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

	public static long getChecksum(Record marcRecord) {
		CRC32 crc32 = new CRC32();
		crc32.update(marcRecord.toString().getBytes());
		return crc32.getValue();
	}
}
