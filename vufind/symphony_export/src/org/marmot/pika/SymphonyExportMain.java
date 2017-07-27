package org.marmot.pika;
import au.com.bytecode.opencsv.CSVReader;
import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile;
import org.marc4j.MarcReader;
import org.marc4j.MarcStreamReader;
import org.marc4j.MarcStreamWriter;
import org.marc4j.marc.*;

import java.io.*;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.*;
import java.util.zip.CRC32;

/**
 * Extracts information from Symphony server
 * Created by mnoble on 7/25/2017.
 */
public class SymphonyExportMain {
	private static Logger logger = Logger.getLogger(SymphonyExportMain.class);
	private static String serverName;

	private static IndexingProfile indexingProfile;

	private static Long lastSymphonyExtractTimeVariableId = null;

	private static boolean hadErrors = false;

	public static void main(String[] args){
		serverName = args[0];

		// Set-up Logging //
		Date startTime = new Date();
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.symphony_extract.properties");
		if (log4jFile.exists()){
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		}else{
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger.info(startTime.toString() + ": Starting CarlX Extract");

		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		Ini ini = loadConfigFile("config.ini");

		//Connect to the vufind database
		Connection pikaConn = null;
		try{
			String databaseConnectionInfo = cleanIniValue(ini.get("Database", "database_vufind_jdbc"));
			pikaConn = DriverManager.getConnection(databaseConnectionInfo);
		}catch (Exception e){
			System.out.println("Error connecting to vufind database " + e.toString());
			System.exit(1);
		}

		// The time this export started
		Long exportStartTime = startTime.getTime() / 1000;

		// The time the last export started
		long lastExportTime = getLastExtractTime(pikaConn);

		String profileToLoad = "ils";
		if (args.length > 1){
			profileToLoad = args[1];
		}
		indexingProfile = IndexingProfile.loadIndexingProfile(pikaConn, profileToLoad, logger);

		//Check for new marc out
		processNewMarcExports(lastExportTime, pikaConn);

		//Check for a new holds file
		processNewHoldsFile(lastExportTime, pikaConn);

		//update the last export start time
		try {
			// Wrap Up
			if (!hadErrors) {
				//Update the last extract time
				if (lastSymphonyExtractTimeVariableId != null) {
					PreparedStatement updateVariableStmt = pikaConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
					updateVariableStmt.setLong(1, exportStartTime);
					updateVariableStmt.setLong(2, lastSymphonyExtractTimeVariableId);
					updateVariableStmt.executeUpdate();
					updateVariableStmt.close();
					logger.debug("Updated last extract time to " + exportStartTime);
				} else {
					PreparedStatement insertVariableStmt = pikaConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('last_symphony_extract_time', ?)");
					insertVariableStmt.setString(1, Long.toString(exportStartTime));
					insertVariableStmt.executeUpdate();
					insertVariableStmt.close();
					logger.debug("Set last extract time to " + exportStartTime);
				}
			} else {
				logger.error("There was an error updating during the extract, not setting last extract time.");
			}

			try{
				//Close the connection
				pikaConn.close();
			}catch(Exception e){
				System.out.println("Error closing connection: " + e.toString());
			}
		} catch (Exception e) {
			logger.error("MySQL Error: " + e.toString());
		}
	}

	/**
	 * Check the marc folder to see if the holds files have been updated since the last export time.
	 *
	 * If so, load a count of holds per bib and then update the database.
	 *
	 * @param lastExportTime the last time the export was run
	 * @param pikaConn       the connection to the database
	 */
	private static void processNewHoldsFile(long lastExportTime, Connection pikaConn) {
		HashMap<String, Integer> holdsByBib = new HashMap<>();
		File holdFile = new File(indexingProfile.marcPath + "/Pika - Hold Information.csv");
		if (holdFile.exists()){
			long now = new Date().getTime();
			long holdFileLastModified = holdFile.lastModified();
			if (now - holdFileLastModified > 2 * 24 * 60 * 60 * 1000){
				logger.warn("Holds File was last written more than 2 days ago");
			}else if (holdFileLastModified > lastExportTime){
				logger.info("Found a new holds file");
				try {
					CSVReader holdsReader = new CSVReader(new FileReader(holdFile));
					String[] holdsData = holdsReader.readNext();
					while (holdsData != null){
						if (holdsData.length == 3){
							String catalogId = holdsData[0];
							//Make sure the catalog is numeric
							if (catalogId.matches("^\\d+$")){
								if (holdsByBib.containsKey(catalogId)){
									holdsByBib.put(catalogId, holdsByBib.get(catalogId) +1);
								}else{
									holdsByBib.put(catalogId, 1);
								}
							}
						}
						holdsData = holdsReader.readNext();
					}
				}catch (Exception e){
					logger.error("Error reading holds file ", e);
					hadErrors = true;
				}
				logger.info("Read " + holdsByBib.size() + " bibs with holds");
			}
		}else{
			logger.warn("No holds file found");
			hadErrors = true;
		}

		File periodicalsHoldFile = new File(indexingProfile.marcPath + "/Pika - Hold - Periodicals Information.csv");
		if (periodicalsHoldFile.exists()){
			long now = new Date().getTime();
			long holdFileLastModified = periodicalsHoldFile.lastModified();
			if (now - holdFileLastModified > 2 * 24 * 60 * 60 * 1000){
				logger.warn("Periodicals Holds File was last written more than 2 days ago");
			}else if (holdFileLastModified > lastExportTime){
				logger.info("Found a new periodicals holds file");
				try {
					CSVReader holdsReader = new CSVReader(new FileReader(periodicalsHoldFile));
					String[] holdsData = holdsReader.readNext();
					while (holdsData != null){
						if (holdsData.length == 3){
							String catalogId = holdsData[0];
							//Make sure the catalog is numeric
							if (catalogId.matches("^\\d+$")){
								if (holdsByBib.containsKey(catalogId)){
									holdsByBib.put(catalogId, holdsByBib.get(catalogId) +1);
								}else{
									holdsByBib.put(catalogId, 1);
								}
							}
						}
						holdsData = holdsReader.readNext();
					}
					logger.info(holdsByBib.size() + " bibs with holds (including periodicals)");
				}catch (Exception e){
					logger.error("Error reading periodicals holds file ", e);
					hadErrors = true;
				}
			}
		}else{
			logger.warn("No periodicals holds file found");
			hadErrors = true;
		}

		//Now that we've counted all the holds, update the database
		if (!hadErrors){
			try {
				pikaConn.prepareCall("DELETE FROM ils_hold_summary").executeUpdate();
				logger.info("Removed existing holds");
				PreparedStatement updateHoldsStmt = pikaConn.prepareStatement("INSERT INTO ils_hold_summary (ilsId, numHolds) VALUES (?, ?)");
				for (String ilsId : holdsByBib.keySet()){
					updateHoldsStmt.setString(1, "a" + ilsId);
					updateHoldsStmt.setInt(2, holdsByBib.get(ilsId));
					int numUpdates = updateHoldsStmt.executeUpdate();
					if (numUpdates != 1){
						logger.info("Hold was not inserted " + "a" + ilsId + " " + holdsByBib.get(ilsId));
					}
				}
				logger.info("Finished adding new holds to the database");
			}catch (Exception e){
				logger.error("Error updating holds database", e);
				hadErrors = true;
			}
		}
	}

	/**
	 * Check the updates folder for any files that have arrived since our last export, but after the
	 * last full export.
	 *
	 * If we get new files, load the MARC records from the file and compare what we have on disk.
	 * If the checksum has changed, we should mark the records as updated in the database and replace
	 * the current MARC with the new record.
	 *
	 * @param lastExportTime the last time the export was run
	 * @param pikaConn       the connection to the database
	 */
	private static void processNewMarcExports(long lastExportTime, Connection pikaConn) {
		File fullExportFile = new File(indexingProfile.marcPath + "/fullexport.mrc");
		File fullExportDirectory = fullExportFile.getParentFile();
		File sitesDirectory = fullExportDirectory.getParentFile();
		File updatesDirectory = new File(sitesDirectory.getAbsolutePath() + "/marc_updates");
		File updatesFile = new File(updatesDirectory.getAbsolutePath() + "/Pika-hourly.mrc");
		if (!fullExportFile.exists()){
			logger.error("Full export file did not exist");
			hadErrors = true;
			return;
		}
		if (!updatesFile.exists()){
			logger.warn("Updates file did not exist");
			hadErrors = true;
			return;
		}
		if (updatesFile.lastModified() < fullExportFile.lastModified()){
			logger.debug("Updates File was written before the full export, ignoring");
			return;
		}
		if (updatesFile.lastModified() < lastExportTime){
			logger.info("Not processing updates file because it hasn't changed since the last run of the export process.");
			return;
		}

		//If we got this far we have a good updates file to process.
		try {
			PreparedStatement getChecksumStmt = pikaConn.prepareStatement("SELECT checksum FROM ils_marc_checksums where source = ? AND ilsId = ?");
			PreparedStatement updateChecksumStmt = pikaConn.prepareStatement("UPDATE ils_marc_checksums set checksum = ? where source = ? AND ilsId = ?");
			PreparedStatement getGroupedWorkIdStmt = pikaConn.prepareStatement("SELECT grouped_work_id from grouped_work_primary_identifiers WHERE type = ? AND identifier = ?");
			PreparedStatement updateGroupedWorkStmt = pikaConn.prepareStatement("UPDATE grouped_work set date_updated = ? where id = ?");

			MarcReader updatedMarcReader = new MarcStreamReader(new FileInputStream(updatesFile));
			while (updatedMarcReader.hasNext()){
				Record marcRecord = updatedMarcReader.next();
				//Get the id of the record
				String recordNumber = getPrimaryIdentifierFromMarcRecord(marcRecord);
				//Check to see if the checksum has changed
				getChecksumStmt.setString(1, indexingProfile.name);
				getChecksumStmt.setString(2, recordNumber);
				ResultSet getChecksumRS = getChecksumStmt.executeQuery();
				if (getChecksumRS.next()){
					//If it has, write the file to disk and update the database
					Long oldChecksum = getChecksumRS.getLong(1);
					Long newChecksum = getChecksum(marcRecord);
					if (!oldChecksum.equals(newChecksum)){
						getGroupedWorkIdStmt.setString(1, indexingProfile.name);
						getGroupedWorkIdStmt.setString(2, recordNumber);
						ResultSet getGroupedWorkIdRS = getGroupedWorkIdStmt.executeQuery();
						if (getGroupedWorkIdRS.next()) {
							Long groupedWorkId = getGroupedWorkIdRS.getLong(1);

							//Save the marc record
							File ilsFile = indexingProfile.getFileForIlsRecord(recordNumber);
							MarcStreamWriter writer2 = new MarcStreamWriter(new FileOutputStream(ilsFile,false), "UTF-8");
							writer2.setAllowOversizeEntry(true);
							writer2.write(marcRecord);
							writer2.close();

							//Mark the work as changed
							updateGroupedWorkStmt.setLong(1, new Date().getTime() / 1000);
							updateGroupedWorkStmt.setLong(2, groupedWorkId);
							updateGroupedWorkStmt.executeUpdate();

							//Save the new checksum so we don't reprocess
							updateChecksumStmt.setLong(1, newChecksum);
							updateChecksumStmt.setString(2, indexingProfile.name);
							updateChecksumStmt.setString(3, recordNumber);
							updateChecksumStmt.executeUpdate();
						}else{
							logger.warn("Could not find grouped work for MARC " + recordNumber);
						}
					}else{
						logger.debug("Skipping MARC " + recordNumber + " because it hasn't changed");
					}
				}else{
					logger.debug("MARC Record " + recordNumber + " is new since the last full export");
				}

			}
		}catch (Exception e){
			logger.error("Error loading updated marcs", e);
			hadErrors = true;
		}
	}

	private static String getPrimaryIdentifierFromMarcRecord(Record marcRecord) {
		List<VariableField> recordNumberFields = marcRecord.getVariableFields(indexingProfile.recordNumberTag);
		String recordNumber = null;
		//Make sure we only get one ils identifier
		for (VariableField curVariableField : recordNumberFields) {
			if (curVariableField instanceof DataField) {
				DataField curRecordNumberField = (DataField) curVariableField;
				Subfield subfieldA = curRecordNumberField.getSubfield('a');
				if (subfieldA != null && (indexingProfile.recordNumberPrefix.length() == 0 || subfieldA.getData().length() > indexingProfile.recordNumberPrefix.length())) {
					if (curRecordNumberField.getSubfield('a').getData().substring(0, indexingProfile.recordNumberPrefix.length()).equals(indexingProfile.recordNumberPrefix)) {
						recordNumber = curRecordNumberField.getSubfield('a').getData().trim();
						break;
					}
				}
			} else {
				//It's a control field
				ControlField curRecordNumberField = (ControlField) curVariableField;
				recordNumber = curRecordNumberField.getData().trim();
				break;
			}
		}
		return recordNumber;
	}

	private static Long getLastExtractTime(Connection vufindConn) {
		Long lastSymphonyExtractTime = null;
		try {
			PreparedStatement loadLastSymphonyExtractTimeStmt = vufindConn.prepareStatement("SELECT * from variables WHERE name = 'last_symphony_extract_time'", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet lastSymphonyExtractTimeRS = loadLastSymphonyExtractTimeStmt.executeQuery();
			if (lastSymphonyExtractTimeRS.next()){
				lastSymphonyExtractTime           = lastSymphonyExtractTimeRS.getLong("value");
				SymphonyExportMain.lastSymphonyExtractTimeVariableId = lastSymphonyExtractTimeRS.getLong("id");
				logger.debug("Last extract time was " + lastSymphonyExtractTime);
			}else{
				logger.debug("Last extract time was not set in the database");
			}

			//Last Update in UTC
			Date now             = new Date();
			Date yesterday       = new Date(now.getTime() - 24 * 60 * 60 * 1000);
			// Add a small buffer (2 minutes) to the last extract time
			Date lastExtractDate = (lastSymphonyExtractTime != null) ? new Date((lastSymphonyExtractTime * 1000) - (120 * 1000)) : yesterday;

			if (lastExtractDate.before(yesterday)){
				logger.warn("Last Extract date was more than 24 hours ago.  Just getting the last 24 hours since we should have a full extract.");
				lastSymphonyExtractTime = yesterday.getTime();
			}else{
				lastSymphonyExtractTime = lastExtractDate.getTime();
			}

		} catch (Exception e) {
			logger.error("Error getting last Extract Time for CarlX", e);
		}
		return lastSymphonyExtractTime;
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

	private static String cleanIniValue(String value) {
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

	private static long getChecksum(Record marcRecord) {
		CRC32 crc32 = new CRC32();
		String marcRecordContents = marcRecord.toString();
		//There can be slight differences in how the record length gets calculated between ILS export and what is written
		//by MARC4J since there can be differences in whitespace and encoding.
		// Remove the text LEADER
		// Remove the length of the record
		// Remove characters in position 12-16 (position of data)
		marcRecordContents = marcRecordContents.substring(12, 19) + marcRecordContents.substring(24).trim();
		marcRecordContents = marcRecordContents.replaceAll("\\p{C}", "?");
		crc32.update(marcRecordContents.getBytes());
		return crc32.getValue();
	}
}
