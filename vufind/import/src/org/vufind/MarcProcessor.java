package org.vufind;

import java.io.File;
import java.io.FileInputStream;
import java.io.FilenameFilter;
import java.io.IOException;
import java.io.InputStream;
import java.util.ArrayList;
import java.util.HashMap;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.Record;

public class MarcProcessor {
	protected String marcRecordPath;
	private HashMap<String, String> formatMap;
	private HashMap<String, String> formatCategoryMap;
	private HashMap<String, String> targetAudienceMap;
	private String idField = "";
	
	protected int recordsProcessed = 0;
	protected int maxRecordsToProcess = -1;
	
	public boolean init(Ini configIni, Logger logger){
		// Get the directory where the marc records are stored.
		marcRecordPath = configIni.get("Reindex", "marcPath");
		if (marcRecordPath == null || marcRecordPath.length() == 0) {
			logger.error("Marc Record Path not found in General Settings.  Please specify the path as the marcRecordPath key.");
			return false;
		}
		
		String translationMapPath = configIni.get("Reindex", "translationMapPath");
		// Read the format map
		String formatMapFileString = translationMapPath + "/format_map.properties";
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
		String formatCategoryMapFileString = translationMapPath + "/format_category_map.properties";
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
		String targetAudienceFileString = translationMapPath + "/target_audience_full_map.properties";
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
		
		String maxRecordsToProcessValue = configIni.get("Reindex", "maxResourcesToProcess");
		if (maxRecordsToProcessValue != null){
			maxRecordsToProcess = Integer.parseInt(maxRecordsToProcessValue);
		}

		try {
			formatMap = Util.readPropertiesFile(formatMapFile);
			formatCategoryMap = Util.readPropertiesFile(formatCategoryMapFile);
			targetAudienceMap = Util.readPropertiesFile(targetAudienceMapFile);
		}catch (IOException e){
			logger.error("Unable to load map from properties file");
			return false;
		}
		
		//Get the idField 
		this.idField = configIni.get("Reindex", "idField");
		if (idField == null){
			logger.error("Unable to get the idField for the record, please provide the idField in the Reindex section");
			return false;
		}
		return true;
	}
	
	protected BasicMarcInfo getBasicMarcInfo(Record marcRecord, Logger logger){
		BasicMarcInfo basicInfo = new BasicMarcInfo();
		if (!basicInfo.load(this, marcRecord, logger)){
			logger.error("Could not find item for record");
			return null;
		}else{
			return basicInfo;
		}
	}
	
	protected boolean processMarcFiles(ArrayList<IMarcRecordProcessor> recordProcessors, Logger logger) {
		try {
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
								//logger.debug("Pocessing marc record " + marcInfo.getId());
								for(IMarcRecordProcessor processor: recordProcessors){
									processor.processMarcRecord(this, marcInfo, logger);
								}
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
		} catch (Exception e) {
			logger.error("Unable to process marc files", e);
			return false;
		}
	}
	
	public HashMap<String, String> getFormatMap() {
		return formatMap;
	}


	public HashMap<String, String> getFormatCategoryMap() {
		return formatCategoryMap;
	}


	public HashMap<String, String> getTargetAudienceMap() {
		return targetAudienceMap;
	}

	public String getIdField() {
		return idField;
	}
}
