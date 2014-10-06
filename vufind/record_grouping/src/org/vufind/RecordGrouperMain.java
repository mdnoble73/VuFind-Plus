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
import java.sql.*;
import java.util.*;
import java.util.Date;
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
	private static boolean fullRegroupingNoClear = false;

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
		if (args.length >= 2 && args[1].equalsIgnoreCase("fullRegroupingNoClear")) {
			fullRegroupingNoClear = true;
		}else if (args.length >= 2 && args[1].equalsIgnoreCase("fullRegrouping")){
			clearDatabasePriorToGrouping = true;
			fullRegrouping = true;
		}else{
			fullRegrouping = false;
		}

		RecordGroupingProcessor recordGroupingProcessor = new RecordGroupingProcessor(vufindConn, serverName, configIni, logger, fullRegrouping);

		clearDatabase(vufindConn, clearDatabasePriorToGrouping);
		loadIlsChecksums(vufindConn);

		boolean errorAddingGroupedWorks = false;
		groupEVokeRecords(configIni, recordGroupingProcessor);
		groupOverDriveRecords(econtentConnection, recordGroupingProcessor);
		groupIlsRecords(configIni, recordGroupingProcessor);

		try{
			vufindConn.setAutoCommit(false);
			if (!errorAddingGroupedWorks){
				removeGroupedWorksWithoutPrimaryIdentifiers(vufindConn);
				vufindConn.commit();
				removeUnlinkedIdentifiers(vufindConn);
				vufindConn.commit();
				makeIdentifiersLinkingToMultipleWorksInvalidForEnrichment(vufindConn);
				vufindConn.commit();
				updateLastGroupingTime(vufindConn);
				vufindConn.commit();
			}
			vufindConn.setAutoCommit(true);
		}catch (SQLException e){
			logger.error("Error in grouped work post processing", e);
		}


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

	private static void groupEVokeRecords(Ini configIni, RecordGroupingProcessor recordGroupingProcessor) {
		if (configIni.containsKey("eVoke")){
			logger.debug("Grouping eVoke records");
			int numRecordsProcessed = 0;
			int numRecordsRead = 0;
			String marcPath = configIni.get("eVoke", "evokePath");

			//Loop through each of the files tha have been exported
			File[] recordPrefixPaths = new File(marcPath).listFiles();
			if (recordPrefixPaths != null){
				String lastRecordProcessed = "";
				for (File curPrefixPath : recordPrefixPaths){
					if (curPrefixPath.isDirectory()) {
						File[] catalogBibFiles = curPrefixPath.listFiles();
						if (catalogBibFiles != null) {
							for (File curBibFile : catalogBibFiles) {
								if (curBibFile.getName().endsWith(".mrc")) {
									try {
										Record marcRecord = EVokeMarcReader.readMarc(curBibFile);
										//Record number is based on the filename. It isn't actually in the MARC record at all.
										String recordNumber = curBibFile.getName();
										recordNumber = recordNumber.substring(0, recordNumber.lastIndexOf('.'));
										RecordIdentifier primaryIdentifier = new RecordIdentifier();
										primaryIdentifier.setValue("evoke", recordNumber);
										try {
											//TODO: Determine if the record has changed sislnce we last indexed (if doing a partial update).
											recordGroupingProcessor.processEVokeRecord(marcRecord, primaryIdentifier);
											numRecordsProcessed++;
											lastRecordProcessed = recordNumber;
											numRecordsRead++;
											if (numRecordsRead % 100000 == 0) {
												recordGroupingProcessor.dumpStats();
											}
										} catch (Exception e) {
											logger.error("Unable to process record " + recordNumber, e);
										}
									} catch (Exception e) {
										logger.error("Error loading eVoke records " + numRecordsRead + " the last record processed was " + lastRecordProcessed, e);
									}
								}
								logger.warn("Finished grouping " + numRecordsRead + " records with " + numRecordsProcessed + " actual changes from the eVoke file " + curBibFile.getName());
							}
						}
					}
				}
				//TODO: Delete records that no longer exist
			}
		} else{
			logger.debug("eVoke is not configured, not processing records.");
		}


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
		//Mark any secondaryIdentifiers that link to more than one grouped record and therefore should not be used for enrichment
		try{
			boolean autoCommit = vufindConn.getAutoCommit();
			//First mark that all are ok to use
			PreparedStatement markAllIdentifiersAsValidStmt = vufindConn.prepareStatement("UPDATE grouped_work_identifiers SET valid_for_enrichment = 1");
			markAllIdentifiersAsValidStmt.executeUpdate();

			//Get a list of any secondaryIdentifiers that are used to load enrichment (isbn, issn, upc) that are attached to more than one grouped work
			vufindConn.setAutoCommit(false);
			PreparedStatement invalidIdentifiersStmt = vufindConn.prepareStatement(
					"SELECT grouped_work_identifiers.id as secondary_identifier_id, type, identifier, GROUP_CONCAT(permanent_id), GROUP_CONCAT(full_title) as titles, GROUP_CONCAT(author) as authors, GROUP_CONCAT(grouping_category) as categories, COUNT(grouped_work_id) as num_related_works\n" +
							"FROM grouped_work_identifiers \n" +
							"INNER JOIN grouped_work_identifiers_ref ON grouped_work_identifiers.id = identifier_id\n" +
							"INNER JOIN grouped_work ON grouped_work_id = grouped_work.id\n" +
							"WHERE type IN ('isbn', 'issn', 'upc')\n" +
							"GROUP BY grouped_work_identifiers.id\n" +
							"HAVING num_related_works > 1", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			ResultSet invalidIdentifiersRS = invalidIdentifiersStmt.executeQuery();
			PreparedStatement updateInvalidIdentifierStmt = vufindConn.prepareStatement("UPDATE grouped_work_identifiers SET valid_for_enrichment = 0 where id = ?");
			int numIdentifiersUpdated = 0;
			while (invalidIdentifiersRS.next()){
				String type = invalidIdentifiersRS.getString("type");
				String identifier = invalidIdentifiersRS.getString("identifier");
				String titles = invalidIdentifiersRS.getString("titles");
				String[] titlesBroken = titles.split(",");
				if (titlesBroken.length >= 2){
					String firstTitle = titlesBroken[0];
					for (int i = 1; i < titlesBroken.length; i++){
						String curTitle = titlesBroken[i];
						if (!curTitle.equals(firstTitle)){
							if (curTitle.startsWith(firstTitle) || firstTitle.startsWith(curTitle)){
								logger.info(type + " " + identifier + " did not match on titles '" + titles + "', but the titles are similar");
							}
						}
					}
				}
				String authors = invalidIdentifiersRS.getString("authors");
				String[] authorsBroken = authors.split(",");
				if (authorsBroken.length >= 2){
					String firstAuthor = authorsBroken[0];
					for (int i = 1; i < authorsBroken.length; i++){
						String curAuthor = authorsBroken[i];
						if (!curAuthor.equals(firstAuthor)){
							if (curAuthor.startsWith(firstAuthor) || firstAuthor.startsWith(curAuthor)){
								logger.info(type + " " + identifier + " did not match on authors '" + authors + "', but the authors are similar");
							}
						}
					}
				}
				String categories = invalidIdentifiersRS.getString("categories");
				String[] categoriesBroken = categories.split(",");
				if (categoriesBroken.length >= 2){
					String firstCategory = categoriesBroken[0];
					for (int i = 1; i < categoriesBroken.length; i++){
						String curCategory = categoriesBroken[i];
						if (!curCategory.equals(firstCategory)){
							if (curCategory.startsWith(firstCategory) || firstCategory.startsWith(curCategory)){
								logger.info(type + " " + identifier + " did not match on categories '" + categories + "', but the categories are similar");
							}
						}
					}
				}

				updateInvalidIdentifierStmt.setLong(1, invalidIdentifiersRS.getLong("secondary_identifier_id"));
				updateInvalidIdentifierStmt.executeUpdate();
				numIdentifiersUpdated++;
			}
			logger.info("Marked " + numIdentifiersUpdated + " secondaryIdentifiers as invalid for enrichment because they link to multiple grouped records");
			invalidIdentifiersRS.close();
			invalidIdentifiersStmt.close();
			vufindConn.commit();
			vufindConn.setAutoCommit(autoCommit);
		}catch (Exception e){
			logger.error("Unable to mark secondary identifiers as invalid for enrichment", e);
		}
	}

	private static void removeUnlinkedIdentifiers(Connection vufindConn) {
		//Remove any identifiers that are no longer linked to a primary identifier
		try{
			boolean autoCommit = vufindConn.getAutoCommit();
			vufindConn.setAutoCommit(false);
			PreparedStatement unlinkedIdentifiersStmt = vufindConn.prepareStatement("SELECT grouped_work_identifiers.id, count(primary_identifier_id) as num_primary_identifiers from grouped_work_identifiers left join grouped_work_primary_to_secondary_id_ref on grouped_work_identifiers.id = secondary_identifier_id GROUP BY secondary_identifier_id having num_primary_identifiers = 0", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			ResultSet unlinkedIdentifiersRS = unlinkedIdentifiersStmt.executeQuery();
			PreparedStatement removeIdentifierStmt = vufindConn.prepareStatement("DELETE FROM grouped_work_identifiers where id = ?");
			int numUnlinkedIdentifiersRemoved = 0;
			while (unlinkedIdentifiersRS.next()){
				removeIdentifierStmt.setLong(1, unlinkedIdentifiersRS.getLong(1));
				removeIdentifierStmt.executeUpdate();
				numUnlinkedIdentifiersRemoved++;
			}
			logger.info("Removed " + numUnlinkedIdentifiersRemoved + " identifiers that were not linked to primary identifiers");
			unlinkedIdentifiersRS.close();
			unlinkedIdentifiersStmt.close();
			vufindConn.commit();
			vufindConn.setAutoCommit(autoCommit);
		}catch(Exception e){
			logger.error("Error removing identifiers that are no longer linked to a primary identifier", e);
		}
	}

	private static void removeGroupedWorksWithoutPrimaryIdentifiers(Connection vufindConn) {
		//Remove any grouped works that no longer link to a primary identifier
		try{
			boolean autoCommit = vufindConn.getAutoCommit();
			vufindConn.setAutoCommit(false);
			PreparedStatement groupedWorksWithoutIdentifiersStmt = vufindConn.prepareStatement("SELECT grouped_work.id, count(identifier) as num_related_records from grouped_work left join grouped_work_primary_identifiers on grouped_work.id = grouped_work_primary_identifiers.grouped_work_id GROUP BY grouped_work.id HAVING num_related_records = 0", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			ResultSet groupedWorksWithoutIdentifiersRS = groupedWorksWithoutIdentifiersStmt.executeQuery();
			PreparedStatement deleteWorkStmt = vufindConn.prepareStatement("DELETE from grouped_work WHERE id = ?");
			PreparedStatement deleteRelatedIdentifiersStmt = vufindConn.prepareStatement("DELETE from grouped_work_identifiers_ref WHERE grouped_work_id = ?");
			int numWorksNotLinkedToPrimaryIdentifier = 0;
			while (groupedWorksWithoutIdentifiersRS.next()){
				deleteWorkStmt.setLong(1, groupedWorksWithoutIdentifiersRS.getLong(1));
				deleteWorkStmt.executeUpdate();

				deleteRelatedIdentifiersStmt.setLong(1, groupedWorksWithoutIdentifiersRS.getLong(1));
				deleteRelatedIdentifiersStmt.executeUpdate();
				numWorksNotLinkedToPrimaryIdentifier++;
			}
			logger.info("Removed " + numWorksNotLinkedToPrimaryIdentifier + " grouped works that were not linked to primary identifiers");
			groupedWorksWithoutIdentifiersRS.close();
			vufindConn.commit();
			vufindConn.setAutoCommit(autoCommit);
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
		int numRecordsRead = 0;
		String individualMarcPath = configIni.get("Reindex", "individualMarcPath");
		String marcPath = configIni.get("Reindex", "marcPath");

		recordNumberTag = configIni.get("Reindex", "recordNumberTag");
		recordNumberPrefix = configIni.get("Reindex", "recordNumberPrefix");

		String marcEncoding = configIni.get("Reindex", "marcEncoding");

		String loadFormatFrom = configIni.get("Reindex", "loadFormatFrom").trim();
		char formatSubfield = ' ';
		if (loadFormatFrom.equals("item")){
			formatSubfield = configIni.get("Reindex", "formatSubfield").trim().charAt(0);
		}

		//Load all files in the individual marc path.  This allows us to list directories rather than doing millions of
		//individual look ups
		HashSet<String> existingMarcFiles = new HashSet<String>();
		File individualMarcFile = new File(individualMarcPath);
		logger.debug("Starting to read existing marc files from disc");
		loadExistingMarcFiles(individualMarcFile, existingMarcFiles);
		logger.debug("Finished reading existing marc files from disc");

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
							boolean marcUpToDate = writeIndividualMarc(existingMarcFiles, individualMarcPath, curBib, recordNumber);
							if (!marcUpToDate || fullRegroupingNoClear){
								recordGroupingProcessor.processMarcRecord(curBib, loadFormatFrom, formatSubfield);
								numRecordsProcessed++;
							}
							//Mark that the record was processed
							marcRecordIdsInDatabase.remove(recordNumber);
							lastRecordProcessed = recordNumber;
							numRecordsRead++;
							if (numRecordsRead % 100000 == 0){
								recordGroupingProcessor.dumpStats();
							}
						}
						marcFileStream.close();
					}catch(Exception e){
						logger.error("Error loading catalog bibs on record " + numRecordsRead + " the last record processed was " + lastRecordProcessed, e);
					}
					logger.warn("Finished grouping " + numRecordsRead + " records with " + numRecordsProcessed + " actual changes from the ils file " + curBibFile.getName());
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

	private static void loadExistingMarcFiles(File individualMarcPath, HashSet<String> existingFiles) {
		File[] subFiles = individualMarcPath.listFiles();
		if (subFiles != null){
			for (File curFile : subFiles){
				String fileName = curFile.getName();
				if (!fileName.equals(".") && !fileName.equals("..")){
					if (curFile.isDirectory()){
						loadExistingMarcFiles(curFile, existingFiles);
					}else{
						existingFiles.add(fileName);
					}
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
			if (lastGroupingTime != null && !fullRegrouping && !fullRegroupingNoClear){
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
				if (lastGroupingTime == null || fullRegroupingNoClear){
					deletedRecordStmt = econtentConnection.prepareStatement("SELECT overdriveId FROM overdrive_api_products WHERE deleted = 1",  ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				}else{
					deletedRecordStmt = econtentConnection.prepareStatement("SELECT overdriveId FROM overdrive_api_products WHERE deleted = 1 and dateDeleted >= ?",  ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
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

	private static boolean writeIndividualMarc(HashSet<String> existingMarcFiles, String individualMarcPath, Record marcRecord, String recordNumber) {
		Boolean marcRecordUpToDate = false;
		//Copy the record to the individual marc path
		if (recordNumber != null){
			Boolean marcRecordExistsInDb = false;
			Long checksum = getChecksum(marcRecord);
			File individualFile = getFileForIlsRecord(individualMarcPath, recordNumber);

			if (!fullRegrouping){
				Long existingChecksum = getExistingChecksum(recordNumber);
				marcRecordExistsInDb = existingChecksum != null;
				marcRecordUpToDate = existingChecksum != null && existingChecksum.equals(checksum);
				marcRecordUpToDate = checkIfIndividualMarcFileExists(existingMarcFiles, marcRecordUpToDate, individualFile);
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

	private static Boolean checkIfIndividualMarcFileExists(HashSet<String> existingMarcFiles, Boolean marcRecordUpToDate, File individualFile) {
		if (!existingMarcFiles.contains(individualFile.getName())){
			marcRecordUpToDate = false;
		}
		return marcRecordUpToDate;
	}

	private static Long getExistingChecksum(String recordNumber) {
		return marcRecordChecksums.get(recordNumber);
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
		MarcStreamWriter writer2 = new MarcStreamWriter(new FileOutputStream(individualFile,false), "UTF-8");
		writer2.setAllowOversizeEntry(true);
		writer2.write(marcRecord);
		writer2.close();
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
