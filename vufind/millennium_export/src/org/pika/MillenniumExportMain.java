package org.pika;

import au.com.bytecode.opencsv.CSVReader;
import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcStreamWriter;
import org.marc4j.MarcWriter;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.VariableField;
import org.marc4j.marc.impl.SubfieldImpl;

import java.io.*;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.SQLException;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.*;

public class MillenniumExportMain{
	private static Logger logger = Logger.getLogger(MillenniumExportMain.class);
	private static String serverName; //Pika instance name
	private static Connection vufindConn;
	private static String itemTag;
	private static char itemRecordNumberSubfield;
	private static char locationSubfield;
	private static char statusSubfield;
	private static char dueDateSubfield;

	public static void main(String[] args){
		serverName = args[0];

		Date startTime = new Date();
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.millennium_extract.properties");
		if (log4jFile.exists()){
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		}else{
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger.info(startTime.toString() + ": Starting Millennium Extract");

		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		Ini ini = loadConfigFile("config.ini");
		String exportPath = ini.get("Reindex", "marcPath");
		if (exportPath.startsWith("\"")){
			exportPath = exportPath.substring(1, exportPath.length() - 1);
		}

		//Connect to the vufind database
		vufindConn = null;
		try{
			String databaseConnectionInfo = cleanIniValue(ini.get("Database", "database_vufind_jdbc"));
			vufindConn = DriverManager.getConnection(databaseConnectionInfo);
		}catch (Exception e){
			logger.error("Error connecting to vufind database ", e);
			System.exit(1);
		}

		//We assume that before this process runs, you have already called
		//ITEM_UPDATE_EXTRACT_PIKA.exp to create the export.

		//Information in the export is item based and includes:
		//Bib record, item record, item status, item due date, item location, item barcode
		//All information is tab delimited with no text qualifier.
		//Repeated field values are separated with |
		File[] potentialFiles = new File(exportPath).listFiles(new FilenameFilter() {
			@Override
			public boolean accept(File dir, String name) {
				if (name.matches("ITEM_UPDATE_EXTRACT_PIKA-\\d+-UPDATES")){
					return true;
				}
				return false;
			}
		});

		if (potentialFiles.length == 0){
			logger.error("Could not find updates file to process");
		}else if (potentialFiles.length > 1){
			logger.error("Found too many updates files to process");
		}else{
			//Just the right number of files
			File itemUpdateDataFile = potentialFiles[0];
			if (itemUpdateDataFile.exists()){
				//Yay, we got a file, process it.
				processItemUpdates(ini, itemUpdateDataFile);
			}else{
				logger.error("That's really weird, the update file was deleted while we were looking at it.");
			}
		}

		//Merge item changes with the individual marc records and
		//indicate that the work needs to be reindexed

		//Cleanup
		if (vufindConn != null) {
			try {
				vufindConn.close();
			}catch (Exception e){
				logger.error("error closing connection", e);
			}
		}
	}

	public static void processItemUpdates(Ini ini, File itemUpdateDataFile) {
		String individualMarcPath = ini.get("Reindex", "individualMarcPath");
		itemTag = ini.get("Reindex", "itemTag");
		itemRecordNumberSubfield = getSubfieldIndicatorFromConfig(ini, "itemRecordNumberSubfield");
		locationSubfield = getSubfieldIndicatorFromConfig(ini, "locationSubfield");
		statusSubfield = getSubfieldIndicatorFromConfig(ini, "statusSubfield");
		dueDateSubfield = getSubfieldIndicatorFromConfig(ini, "dueDateSubfield");

		//Last Update in UTC
		SimpleDateFormat dateFormatter = new SimpleDateFormat("MM-dd-yyyy");
		long updateTime = new Date().getTime() / 1000;

		SimpleDateFormat csvDateFormat = new SimpleDateFormat("MM-dd-yyyy");
		SimpleDateFormat marcDateFormat = new SimpleDateFormat("MM-dd-yy");

		HashMap<String, ArrayList<ItemChangeInfo>> changedBibs = new HashMap<String, ArrayList<ItemChangeInfo>>();
		try {
			CSVReader updateReader = new CSVReader(new FileReader(itemUpdateDataFile), '\t');
			//Read each line in the file
			String[] curItem = updateReader.readNext();
			//First line is the header, skip that.
			curItem = updateReader.readNext();
			while (curItem != null){
				if (curItem.length >= 5) {
					ItemChangeInfo changeInfo = new ItemChangeInfo();
					//First record id
					String curId = curItem[0];
					changeInfo.setItemId("." + curItem[1]);
					changeInfo.setStatus(curItem[2]);
					//Convert 4 digit year to 2 digit year
					if (curItem[3].matches("\\d{2}-\\d{2}-\\d{4}")) {
						try {
							changeInfo.setDueDate(marcDateFormat.format(csvDateFormat.parse(curItem[3])));
						} catch (ParseException e) {
							logger.error("Error parsing date " + curItem[3], e);
							changeInfo.setDueDate(curItem[3]);
						}
					} else {
						changeInfo.setDueDate(curItem[3]);
					}
					changeInfo.setLocation(curItem[4]);

					String fullId = "." + curId;
					ArrayList<ItemChangeInfo> itemChanges;
					if (changedBibs.containsKey(fullId)) {
						itemChanges = changedBibs.get(fullId);
					}else{
						itemChanges = new ArrayList<ItemChangeInfo>();
						changedBibs.put(fullId, itemChanges);
					}
					itemChanges.add(changeInfo);
				}else{
					logger.debug("Invalid row read");
				}

				//Don't forget to read the next line
				curItem = updateReader.readNext();
			}
			updateReader.close();
		} catch (IOException e) {
			logger.error("Unable to read from " + itemUpdateDataFile.getAbsolutePath(), e);
		}

		try {
			vufindConn.setAutoCommit(false);
			PreparedStatement markGroupedWorkForBibAsChangedStmt = vufindConn.prepareStatement("UPDATE grouped_work SET date_updated = ? where id = (SELECT grouped_work_id from grouped_work_primary_identifiers WHERE type = 'ils' and identifier = ?)") ;

			logger.info("A total of " + changedBibs.size() + " bibs were updated");
			int numUpdates = 0;
			for (String curBibId : changedBibs.keySet()) {
				//Update the marc record
				updateMarc(individualMarcPath, curBibId, changedBibs.get(curBibId));
				//Update the database
				try {
					markGroupedWorkForBibAsChangedStmt.setLong(1, updateTime);
					markGroupedWorkForBibAsChangedStmt.setString(2, curBibId);
					markGroupedWorkForBibAsChangedStmt.executeUpdate();

					numUpdates++;
					if (numUpdates % 50 == 0) {
						vufindConn.commit();
					}
				} catch (SQLException e) {
					logger.error("Could not mark that " + curBibId + " was changed due to error ", e);
				}
			}
			//Turn auto commit back on
			vufindConn.commit();
			vufindConn.setAutoCommit(true);
		}catch (SQLException e){
			logger.error("Error updating the database ", e);
		}

	}

	private static File getFileForIlsRecord(String individualMarcPath, String recordNumber) {
		String shortId = getFileIdForRecordNumber(recordNumber);
		String firstChars = shortId.substring(0, 4);
		String basePath = individualMarcPath + "/" + firstChars;
		String individualFilename = basePath + "/" + shortId + ".mrc";
		return new File(individualFilename);
	}

	private static void updateMarc(String individualMarcPath, String curBibId, ArrayList<ItemChangeInfo> itemChangeInfo) {
		//Load the existing marc record from file
		try {
			File marcFile = getFileForIlsRecord(individualMarcPath, curBibId);
			if (marcFile.exists()) {
				FileInputStream inputStream = new FileInputStream(marcFile);
				MarcPermissiveStreamReader marcReader = new MarcPermissiveStreamReader(inputStream, true, true, "UTF-8");
				if (marcReader.hasNext()) {
					Record marcRecord = marcReader.next();
					inputStream.close();

					//Loop through all item fields to see what has changed
					List<VariableField> itemFields = marcRecord.getVariableFields(itemTag);
					for (VariableField itemFieldVar : itemFields) {
						DataField itemField = (DataField) itemFieldVar;
						if (itemField.getSubfield(itemRecordNumberSubfield) != null) {
							String itemRecordNumber = itemField.getSubfield(itemRecordNumberSubfield).getData();
							//Update the items
							for (ItemChangeInfo curItem : itemChangeInfo) {
								//Find the correct item
								if (itemRecordNumber.equals(curItem.getItemId())) {
									itemField.getSubfield(locationSubfield).setData(curItem.getLocation());
									itemField.getSubfield(statusSubfield).setData(curItem.getStatus());
									if (curItem.getDueDate() == null) {
										if (itemField.getSubfield(dueDateSubfield) != null) {
											itemField.getSubfield(dueDateSubfield).setData("      ");
										}
									} else {
										if (itemField.getSubfield(dueDateSubfield) == null) {
											itemField.addSubfield(new SubfieldImpl(dueDateSubfield, curItem.getDueDate()));
										} else {
											itemField.getSubfield(dueDateSubfield).setData(curItem.getDueDate());
										}
									}
								}
							}
						}
					}

					//Write the new marc record
					MarcWriter writer = new MarcStreamWriter(new FileOutputStream(marcFile, false));
					writer.write(marcRecord);
					writer.close();
				} else {
					logger.warn("Could not read marc record for " + curBibId);
				}
			}else{
				logger.debug("Marc Record does not exist for " + curBibId + " it is not part of the main extract yet.");
			}
		}catch (Exception e){
			logger.error("Error updating marc record for bib " + curBibId, e);
		}
	}

	private static String getFileIdForRecordNumber(String recordNumber) {
		String shortId = recordNumber.replace(".", "");
		while (shortId.length() < 9){
			shortId = "0" + shortId;
		}
		return shortId;
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

	private static char getSubfieldIndicatorFromConfig(Ini configIni, String subfieldName) {
		String subfieldString = configIni.get("Reindex", subfieldName);
		char subfield = ' ';
		if (subfieldString != null && subfieldString.length() > 0)  {
			subfield = subfieldString.charAt(0);
		}
		return subfield;
	}

}