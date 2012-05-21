package org.vufind;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileReader;
import java.io.FilenameFilter;
import java.io.IOException;
import java.io.InputStream;
import java.util.HashMap;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.Record;

public abstract class MarcProcessorBase {
	protected String marcRecordPath;
	protected HashMap<String, String> formatMap;
	protected HashMap<String, String> formatCategoryMap;
	protected HashMap<String, String> targetAudienceMap;
	protected int recordsProcessed = 0;
	protected int maxRecordsToProcess = -1;
	private Logger logger;
	
	protected boolean loadConfig(String servername, Ini configIni, Section processSettings, Logger logger){
		this.logger = logger;
		// Get the directory where the marc records are stored.
		marcRecordPath = configIni.get("EContent", "marcPath");
		if (marcRecordPath == null || marcRecordPath.length() == 0) {
			logger.error("Marc Record Path not found in General Settings.  Please specify the path as the marcRecordPath key.");
			return false;
		}
		
		// Read the format map
		String formatMapFileString = "../../sites/" + servername + "/translation_maps/format_map.properties";
		File formatMapFile = null;
		if (formatMapFileString == null || formatMapFileString.length() == 0) {
			logger.error("Format Map File not found in GenerateCatalog Settings.  Please specify the path as the formatMapFile key.");
			return false;
		} else {
			formatMapFile = new File(formatMapFileString);
			if (!formatMapFile.exists()) {
				logger.error("Format Map File does not exist.  Please check the formatMapFile key.");
				return false;
			}
		}

		// Read the category map
		String formatCategoryMapFileString = "../../sites/" + servername + "/translation_maps/format_category_map.properties";
		File formatCategoryMapFile = null;
		if (formatCategoryMapFileString == null || formatCategoryMapFileString.length() == 0) {
			logger.error("Format Category Map File not found in GenerateCatalog Settings.  Please specify the path as the formatCategoryMapFile key.");
			return false;
		} else {
			formatCategoryMapFile = new File(formatCategoryMapFileString);
			if (!formatCategoryMapFile.exists()) {
				logger.error("Format Category Map File " + formatCategoryMapFileString + " does not exist.  Please check the formatCategoryMapFile key.");
				return false;
			}
		}

		// Read the target audience map
		String targetAudienceFileString = "../../sites/" + servername + "/translation_maps/target_audience_map.properties";
		File targetAudienceMapFile = null;
		if (targetAudienceFileString == null || targetAudienceFileString.length() == 0) {
			logger.error("Target Audience Map File not found in GenerateCatalog Settings.  Please specify the path as the targetAudienceMapFile key.");
			return false;
		} else {
			targetAudienceMapFile = new File(targetAudienceFileString);
			if (!targetAudienceMapFile.exists()) {
				logger.error("Target Audience File does not exist.  Please check the targetAudienceFile key.");
				return false;
			}
		}
		
		String maxRecordsToProcessValue = processSettings.get("maxRecordsToProcess");
		if (maxRecordsToProcessValue != null){
			maxRecordsToProcess = Integer.parseInt(maxRecordsToProcessValue);
		}

		try {
			formatMap = readPropertiesFile(formatMapFile);
			formatCategoryMap = readPropertiesFile(formatCategoryMapFile);
			targetAudienceMap = readPropertiesFile(targetAudienceMapFile);
		}catch (IOException e){
			logger.error("Unable to load map from properties file");
			return false;
		}
		return true;
	}
	

	protected HashMap<String, String> readPropertiesFile(File formatMapFile) throws IOException {
		HashMap<String, String> formatMap = new HashMap<String, String>();
		BufferedReader reader = new BufferedReader(new FileReader(formatMapFile));
		String inputLine = reader.readLine();
		logger.info("Reading properties file " + formatMapFile.getAbsolutePath());
		while (inputLine != null) {
			inputLine = inputLine.trim();
			if (inputLine.length() == 0 || inputLine.startsWith("#")) {
				inputLine = reader.readLine();
				continue;
			}
			String[] property = inputLine.split("\\s*=\\s*");
			if (property.length == 2) {
				formatMap.put(property[0], property[1]);
			} else {
				System.out.println("Could not find a property in line " + property);
			}
			inputLine = reader.readLine();
		}
		System.out.println("Finished reading properties file " + formatMapFile.getAbsolutePath() + " found " + formatMap.size() + " records.");
		return formatMap;
	}	
	
	protected BasicMarcInfo getBasicMarcInfo(Record marcRecord, Logger logger){
		BasicMarcInfo basicInfo = new BasicMarcInfo();
		if (!basicInfo.load(marcRecord, logger)){
			logger.error("Could not find item for record");
			return null;
		}else{
			return basicInfo;
		}
	}
	
	protected boolean processMarcFiles(Logger logger) throws FileNotFoundException{
	// Get a list of Marc files to process
		File marcRecordDirectory = new File(marcRecordPath);
		File[] marcFiles;
		if (marcRecordDirectory.isDirectory()){
			marcFiles = marcRecordDirectory.listFiles(new FilenameFilter() {
				@Override
				public boolean accept(File dir, String name) {
					if (name.matches("(?i).*?\\.(marc|mrc)")) {
						return true;
					} else {
						return false;
					}
				}
			});
		}else{
			marcFiles = new File[]{marcRecordDirectory};
		}
		
		// Loop through each marc record
		for (File marcFile : marcFiles) {
			try {
				logger.info("Processing file " + marcFile.toString());
				// Open the marc record with Marc4j
				InputStream input = new FileInputStream(marcFile);
				MarcReader reader = new MarcPermissiveStreamReader(input, true, true);
				int recordNumber = 0;
				while (reader.hasNext()) {
					recordNumber++;
					try{
						// Loop through each record
						Record record = reader.next();
						// Process record
						BasicMarcInfo marcInfo = getBasicMarcInfo(record, logger);
						if (marcInfo != null){
							logger.debug("Pocessing marc record " + marcInfo.getId());
							processMarcRecord(marcInfo, logger);
						}
						recordsProcessed++;
						if (maxRecordsToProcess != -1 && recordsProcessed > maxRecordsToProcess){
							break;
						}
					}catch (Exception e){
						logger.error("Error processing record " + recordNumber, e);
					}
				}
			} catch (Exception e) {
				logger.error("Error processing file " + marcFile.toString(), e);
			}
		}
		return true;
	}
	
	protected abstract boolean processMarcRecord(BasicMarcInfo recordInfo, Logger logger);
}
