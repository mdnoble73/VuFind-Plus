package org.vufind;

import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrServer;
import org.ini4j.Ini;

import java.io.File;
import java.io.FileReader;
import java.io.FilenameFilter;
import java.io.IOException;
import java.net.MalformedURLException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Properties;

import org.apache.log4j.Logger;

/**
 * Indexes records extracted from the ILS
 *
 * VuFind-Plus
 * User: Mark Noble
 * Date: 11/25/13
 * Time: 2:26 PM
 */
public class GroupedWorkIndexer {
	private String serverName;
	private Logger logger;
	private ConcurrentUpdateSolrServer updateServer;
	private IlsRecordProcessor ilsRecordProcessor;
	private HashMap<String, HashMap<String, String>> translationMaps = new HashMap<String, HashMap<String, String>>();

	public GroupedWorkIndexer(String serverName, Connection vufindConn, Connection groupedRecordConn, Ini configIni, Logger logger) {
		this.serverName = serverName;
		this.logger = logger;
		String solrPort = configIni.get("Reindex", "solrPort");

		ilsRecordProcessor = new IlsRecordProcessor(this, vufindConn, configIni, logger);

		//Initialize the updateServer
		try {
			updateServer = new ConcurrentUpdateSolrServer("http://localhost:" + solrPort + "/solr/grouped", 5000, 10);
		} catch (MalformedURLException e) {
			logger.error("Could not create update server for solr", e);
		}

		//Load translation maps
		loadTranslationMaps();

		//Check to see if we should clear the existing index
		String clearMarcRecordsAtStartOfIndexVal = configIni.get("Reindex", "clearMarcRecordsAtStartOfIndex");
		boolean clearMarcRecordsAtStartOfIndex = clearMarcRecordsAtStartOfIndexVal != null && Boolean.parseBoolean(clearMarcRecordsAtStartOfIndexVal);
		//TODO: Make this optional again.
		if (true || clearMarcRecordsAtStartOfIndex){
			logger.info("Clearing existing marc records from index");
			URLPostResponse response = Util.postToURL("http://localhost:" + solrPort + "/solr/grouped/update/?commit=true", "<delete><query>recordtype:grouped_record</query></delete>", logger);
			if (!response.isSuccess()){
				logger.error("Error clearing existing marc records " + response.getMessage());
			}
			response = Util.postToURL("http://localhost:" + solrPort + "/solr/grouped/update/", "<commit expungeDeletes=\"true\"/>", logger);
			if (!response.isSuccess()){
				logger.error("Error expunging deletes " + response.getMessage());
			}
		}

		processGroupedWorks(groupedRecordConn, logger);

		try {
			updateServer.commit(true, true);
			updateServer.shutdown();
		} catch (Exception e) {
			logger.error("Error calling final commit", e);
		}
	}

	private void processGroupedWorks(Connection groupedRecordConn, Logger logger) {
		try {
			PreparedStatement getAllGroupedWorks = groupedRecordConn.prepareStatement("SELECT * FROM grouped_work", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getGroupedWorkIdentifiers = groupedRecordConn.prepareStatement("SELECT * FROM grouped_work_identifiers where grouped_work_id = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			ResultSet groupedWorks = getAllGroupedWorks.executeQuery();
			int numWorksProcessed = 0;
			while (groupedWorks.next()){
				Long id = groupedWorks.getLong("id");
				String permanentId = groupedWorks.getString("permanent_id");
				String title = groupedWorks.getString("title");
				String subtitle = groupedWorks.getString("subtitle");
				String author = groupedWorks.getString("author");
				String grouping_category = groupedWorks.getString("grouping_category");

				//Create a solr record for the grouped work
				GroupedWorkSolr groupedWork = new GroupedWorkSolr();
				groupedWork.setId(permanentId);
				groupedWork.setTitle(title);
				groupedWork.setSubTitle(subtitle);
				groupedWork.setAuthor(author);
				groupedWork.setGroupingCategory(grouping_category);

				//Update the grouped record based on data for each work
				getGroupedWorkIdentifiers.setLong(1, id);
				ResultSet groupedWorkIdentifiers = getGroupedWorkIdentifiers.executeQuery();
				while (groupedWorkIdentifiers.next()){
					String type = groupedWorkIdentifiers.getString("type");
					String identifier = groupedWorkIdentifiers.getString("identifier");
					updateGroupedWorkForIdentifier(groupedWork, type, identifier);
				}

				//Write the record to Solr.
				try{
					updateServer.add(groupedWork.getSolrDocument());
				}catch (Exception e){
					logger.error("Error adding record to solr", e);
				}
				numWorksProcessed++;
				if (numWorksProcessed % 1000 == 0){
					try {
						updateServer.commit(true, true);
					} catch (SolrServerException e) {
						e.printStackTrace();  //To change body of catch statement use File | Settings | File Templates.
					} catch (IOException e) {
						e.printStackTrace();  //To change body of catch statement use File | Settings | File Templates.
					}
					logger.info("Processed " + numWorksProcessed + " grouped works processed.");
				}
			}
		} catch (SQLException e) {
			logger.error("Unexpected SQL error", e);
		}
	}

	private void updateGroupedWorkForIdentifier(GroupedWorkSolr groupedWork, String type, String identifier) {
		type = type.toLowerCase();
		if (type.equals("ils")){
			//Get the ils record from the individual marc records
			ilsRecordProcessor.processRecord(groupedWork, identifier);
		}else if (type.equals("overdrive")){
			processOverDriveRecord(groupedWork, identifier);
		}else if (type.equals("isbn")){
			groupedWork.addIsbn(identifier);
		}else if (type.equals("upc")){
			groupedWork.addUpc(identifier);
		}else if (type.equals("issn")){
			groupedWork.addIssn(identifier);
		}else if (type.equals("oclc")){
			groupedWork.addOclc(identifier);
		}else{
			logger.warn("Unknown identifier type " + type);
		}
	}

	private void processOverDriveRecord(GroupedWorkSolr groupedWork, String identifier) {
		//To change body of created methods use File | Settings | File Templates.
	}

	private void loadTranslationMaps(){
		//Load all translationMaps, first from default, then from the site specific configuration
		File defaultTranslationMapDirectory = new File("../../sites/default/translation_maps");
		File[] defaultTranslationMapFiles = defaultTranslationMapDirectory.listFiles(new FilenameFilter() {
			@Override
			public boolean accept(File dir, String name) {
				return name.endsWith("properties");
			}
		});

		File serverTranslationMapDirectory = new File("../../sites/" + serverName + "/translation_maps");
		File[] serverTranslationMapFiles = serverTranslationMapDirectory.listFiles(new FilenameFilter() {
			@Override
			public boolean accept(File dir, String name) {
				return name.endsWith("properties");
			}
		});

		for (File curFile : defaultTranslationMapFiles){
			String mapName = curFile.getName().replace(".properties", "");
			mapName = mapName.replace("_map", "");
			translationMaps.put(mapName, loadTranslationMap(curFile));
		}
		for (File curFile : serverTranslationMapFiles){
			String mapName = curFile.getName().replace(".properties", "");
			mapName = mapName.replace("_map", "");
			translationMaps.put(mapName, loadTranslationMap(curFile));
		}
	}

	private HashMap<String, String> loadTranslationMap(File translationMapFile) {
		Properties props = new Properties();
		try {
			props.load(new FileReader(translationMapFile));
		} catch (IOException e) {
			logger.error("Could not read translation map, " + translationMapFile.getAbsolutePath(), e);
		}
		HashMap<String, String> translationMap = new HashMap<String, String>();
		for (Object keyObj : props.keySet()){
			String key = (String)keyObj;
			translationMap.put(key, props.getProperty(key));
		}
		return translationMap;
	}

	HashSet<String> unableToTranslateWarnings = new HashSet<String>();
	public String translateValue(String mapName, String value){
		HashMap<String, String> translationMap = translationMaps.get(mapName);
		String translatedValue = null;
		if (translationMap == null){
			logger.error("Unable to find translation map for " + mapName);
			translatedValue = value;
		}else{
			if (translationMap.containsKey(value)){
				translatedValue = translationMap.get(value);
			}else{
				if (translationMap.containsKey("*")){
					translatedValue = translationMap.get("*");
				}else{
					String concatenatedValue = mapName + ":" + value;
					if (!unableToTranslateWarnings.contains(concatenatedValue)){
						logger.warn("Could not translate " + concatenatedValue);
						unableToTranslateWarnings.add(concatenatedValue);
					}
					translatedValue = value;
				}
			}
		}
		if (translatedValue.length() == 0){
			translatedValue = null;
		}
		return translatedValue;
	}
}
