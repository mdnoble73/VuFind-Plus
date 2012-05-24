package org.epub;

import java.io.File;
import java.io.FileFilter;
import java.io.IOException;
import java.io.InputStream;
import java.net.MalformedURLException;
import java.net.URL;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Date;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import org.vufind.CopyNoOverwriteResult;
import org.vufind.CronLogEntry;
import org.vufind.CronProcessLogEntry;
import org.vufind.IProcessHandler;
import org.vufind.Util;

public class Packaging implements IProcessHandler{
	private CronProcessLogEntry processLog;
	private Logger logger; 
	private String vufindUrl;
	private File rootFTPDirFile; 
	private File econtentLibraryDirectory; 
	private String activePackagingSource;
	private String[] allPackagingSources;
	private PreparedStatement doesItemExistForRecord;
	private PreparedStatement updateItemFilename;
	private PreparedStatement getAccessTypeForRecord;
	private PreparedStatement createItemForRecord;
	
	@Override
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Packaging eContent");
		processLog.saveToDatabase(vufindConn, logger);
		this.logger = logger;
		logger.info("Packaging eContent");
		
		try{
			if (!initializePackaging(configIni, econtentConn, logger)){
				return;
			}
			
			processLog.addNote("Checking for new files from publishers.");
			processPublisherDirectories();
			
			processLog.addNote("Checking for new covers from publishers.");
			processNewPublisherCovers();
			
			processLog.addNote("Checking for updated results from packaging service.");
			getResultsFromPackagingService();
		}finally{
			processLog.setFinished();
			processLog.saveToDatabase(vufindConn, logger);
		}
	}

	private boolean initializePackaging(Ini configIni, Connection econtentConn, Logger logger) {
		vufindUrl = Util.cleanIniValue(configIni.get("Site", "url"));
		
		//Load configuration
		String rootFTPDir = configIni.get("EContent", "rootFTPDir");
		if (rootFTPDir == null || rootFTPDir.length() == 0){
			logger.error("Could not find rootFTPDir in EContent section of the config file");
			processLog.addNote("Could not find rootFTPDir in EContent section of the config file, stopping process.");
			processLog.incErrors();
			return false;
		}
		
		rootFTPDirFile = new File(rootFTPDir);
		if (rootFTPDirFile.exists() == false){
			logger.error(rootFTPDir + " does not exist, not looking for new files to process");
			processLog.addNote(rootFTPDir + " does not exist, stopping process.");
			processLog.incErrors();
			return false;
		}
		
		String econtentLibraryDir = configIni.get("EContent", "library");
		if (econtentLibraryDir == null || econtentLibraryDir.length() == 0){
			logger.error("Could not find library in EContent section of the config file");
			processLog.addNote("Could not find library in EContent section of the config file, stopping process.");
			processLog.incErrors();
			return false;
		}
		
		econtentLibraryDirectory = new File(econtentLibraryDir);
		if (econtentLibraryDirectory.exists() == false){
			logger.error(econtentLibraryDir + " does not exist, not looking for new files to process");
			processLog.addNote(econtentLibraryDir + " does not exist, stopping process.");
			processLog.incErrors();
			return false;
		}
		
		activePackagingSource = configIni.get("EContent", "activePackagingSource");
		if (activePackagingSource == null || activePackagingSource.length() == 0){
			logger.warn("Could not find activePackagingSource in EContent section of the config file");
			processLog.addNote("Warning, could not find activePackagingSource in EContent section of the config file, providing a packaging source will allow files to be processed on test and production systems.");
		}
		
		String allPackagingSourcesStr = configIni.get("EContent", "allPackagingSources");
		if (allPackagingSourcesStr == null || allPackagingSourcesStr.length() == 0){
			logger.warn("Could not find allPackagingSources in EContent section of the config file");
			processLog.addNote("Warning, could not find allPackagingSources in EContent section of the config file, providing a packaging source will allow files to be processed on test and production systems.");
			allPackagingSources = new String[0];
		}else{
			allPackagingSources = allPackagingSourcesStr.split(",");
		}
		
		//Setup prepared statements
		try {
			doesItemExistForRecord = econtentConn.prepareStatement("SELECT id, filename from econtent_item where recordId = ? and item_type = ? and notes = ?");
			updateItemFilename = econtentConn.prepareStatement("UPDATE econtent_item set filename = ? where id = ?");
			getAccessTypeForRecord = econtentConn.prepareStatement("SELECT accessType from econtent_record where id = ?");
			createItemForRecord = econtentConn.prepareStatement("INSERT INTO econtent_item (recordId, filename, item_type, notes, addedBy, date_added, date_updated) VALUES (?, ?, ?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
		} catch (SQLException e) {
			logger.error("Unable to prepare statements for packaging");
			processLog.addNote("Unable to prepare statements for packaging, stopping");
			processLog.incErrors();
			return false;
		}
		return true;
	}

	private void processNewPublisherCovers() {
		// TODO Auto-generated method stub
		
	}

	private void getResultsFromPackagingService() {
		// TODO Auto-generated method stub
		
	}

	private void processPublisherDirectories() {
		//Get a list of publisher directories
		File[] publisherDirectories = rootFTPDirFile.listFiles(new FileFilter() {
			@Override
			public boolean accept(File arg0) {
				if (arg0.isDirectory() && !arg0.getName().equals(".") && !arg0.getName().equals("..")){
					return true;
				}else{
					return false;
				}
			}
		});
		
		for (File publisherDir : publisherDirectories){
			System.out.println("Processing " + publisherDir.toString());
			String receivedDataPath = publisherDir.toString() + File.separator + "received" + File.separator + "data";
			File receivedDataFile = new File(receivedDataPath);
			if (!receivedDataFile.exists()){
				processLog.addNote("Directory to receive files from publisher " + receivedDataPath + " does not exist, skipping publisher " + publisherDir.getName() + ".");
				processLog.incErrors();
				continue;
			}
			
			String processedDataPath = publisherDir.toString() + File.separator + "processed" + File.separator + "processed";
			if (activePackagingSource != null && activePackagingSource.length() > 0){
				processedDataPath += "_" + activePackagingSource;
			}
			File processedDataFile = new File(processedDataPath);
			if (processedDataFile.exists() == false){
				if (!processedDataFile.mkdirs()){
					processLog.addNote("Could not create directory to store processed files" + processedDataPath + ", skipping publisher " + publisherDir.getName() + ".");
					processLog.incErrors();
					continue;
				}
			}
			
			processPublisherFiles(receivedDataFile, processedDataFile);
		}
	}

	private void processPublisherFiles(File receivedDataFolder, File processedDataFolder) {
		File[] filesToPublish = receivedDataFolder.listFiles();
		for (File fileToProcess : filesToPublish){
			//Check to see the file already exists in the processed folder
			File processedFile = new File(processedDataFolder + File.separator + fileToProcess.getName());
			if (processedFile.exists()){
				//Check to see if the file is the same as the old file
				if (fileToProcess.length() == processedFile.length()){
					processLog.addNote("Skipping " + fileToProcess + " because it has already been processed");
				}else{
					processLog.addNote("Processing updated file " + fileToProcess);
					processPublisherFile(fileToProcess, processedFile, true);
				}
			}else{
				//The file is new and needs to be processed.  
				processLog.addNote("Processing new file " + fileToProcess);
				processPublisherFile(fileToProcess, processedFile, false);
			}
		}
	}

	private void processPublisherFile(File fileToProcess, File processedFile, boolean updatedFile) {
		//Get the record the file belongs to.
		String filename = fileToProcess.getName();
		String baseFilename = filename.substring(0, filename.indexOf("."));
		String isn = baseFilename;
		String volume = "";
		String extension = filename.substring(filename.indexOf(".") + 1, filename.length());
		if (baseFilename.indexOf("_") > 0){
			isn = baseFilename.substring(0, baseFilename.indexOf("_"));
			volume = baseFilename.substring(0, baseFilename.indexOf("_"));
		}
		logger.debug("Processing isn " + isn + " volume " + volume + " extension = " + extension);
		
		String recordId = getRecordIdForIsn(isn);
		if (recordId == null){
			processLog.addNote("Could not find a record Id for " + isn +  ", waiting until next time to process. ");
			processLog.incErrors();
			return;
		}else{
			logger.debug("Found recordId " + recordId);
			String shortId = recordId.replace("econtentRecord", "");
			String notes = "";
			String format = getFormatByExtension(extension);
			if (format == null){
				processLog.addNote("Could not find a valid format for " + extension +  ", not processing. ");
				processLog.incErrors();
				return;
			}
			if (volume.length() > 0){
				notes = "Volumne " + volume;
			}
			
			
			
			try {
				//Get the accessType for the record
				getAccessTypeForRecord.setString(1, shortId);
				ResultSet accessTypeRS = getAccessTypeForRecord.executeQuery();
				String accessType;
				if (accessTypeRS.next()){
					accessType = accessTypeRS.getString("accessType");
				}else{
					processLog.addNote("Could not get access type for the record");
					processLog.incErrors();
					return;
				}
				
				//Look for item record
				doesItemExistForRecord.setString(1, shortId);
				doesItemExistForRecord.setString(2, format);
				doesItemExistForRecord.setString(3, notes);
				
				//Copy file to vufind library and find a unique name for it.
				CopyNoOverwriteResult copyResult;
				String libraryFilename;
				boolean fileChanged = true;
				try {
					copyResult = Util.copyFileNoOverwrite(fileToProcess, econtentLibraryDirectory);
					fileChanged = copyResult.getCopyResult() == CopyNoOverwriteResult.CopyResult.FILE_COPIED;
					libraryFilename = copyResult.getNewFilename();
				} catch (IOException e) {
					processLog.addNote("Error copying file to library directory " + recordId + " , file " + filename + " - " + e.toString());
					processLog.incErrors();
					return;
				}
				
				//Get the itemId for the file
				ResultSet doesItemExistForRecordRS = doesItemExistForRecord.executeQuery();
				Long itemId = null;
				if (doesItemExistForRecordRS.next()){
					//Item already exists
					itemId = doesItemExistForRecordRS.getLong("id");
					String existingFilename = doesItemExistForRecordRS.getString("filename");
					if (fileChanged){
						//Update the existing record
						updateItemFilename.setString(1, libraryFilename);
						updateItemFilename.setLong(2, itemId);
						updateItemFilename.executeUpdate();
					}else{
						//File already exists in the database and the library, nothing to do. 
						processLog.addNote("File already exists in eContent library and database, nothing to do, stopping.");
						return;
					}
				}else{
					//Item does not exist
					createItemForRecord.setString(1, shortId);
					createItemForRecord.setString(2, libraryFilename);
					createItemForRecord.setString(3, format);
					createItemForRecord.setString(4, notes);
					createItemForRecord.setLong(5, -1);
					createItemForRecord.setLong(6, new Date().getTime() / 1000);
					createItemForRecord.setLong(7, new Date().getTime() / 1000);
					int numUpdates = createItemForRecord.executeUpdate();
					ResultSet itemIdRs = createItemForRecord.getGeneratedKeys();
					if (itemIdRs.next()){
						itemId = itemIdRs.getLong(1);
					}
				}
				
				if (accessType.equalsIgnoreCase("acs")){
					//Send the file to the acs server.
				}
				
				logger.debug("Item Id for file is " + itemId);
				processLog.incUpdated();
			} catch (SQLException e1) {
				processLog.addNote("Error updating items for record " + recordId + " , file " + filename + " - " + e1.toString());
				processLog.incErrors();
				return;
			}
			
			//Copy the file to the processed directory so it is not processed again. 
			try {
				Util.copyFile(fileToProcess, processedFile);
			} catch (IOException e) {
				processLog.addNote("Could not copy file " + fileToProcess +  " to " + processedFile + " - " + e.toString());
				processLog.incErrors();
				return;
			}
		}
	}

	private String getFormatByExtension(String extension) {
		extension = extension.toLowerCase();
		if (extension.equals("pdf")){
			return "pdf";
		}else if (extension.equals("epub")){
			return "epub";
		}else if (extension.equals("mobi")){
			return "mobi";
		}else{
			return null;
		}
	}

	private String getRecordIdForIsn(String isn) {
		try {
			URL searchUrl = new URL(vufindUrl + "/API/SearchAPI?method=search&lookfor=" + isn + "&type=ISN&basicType=ISN&shard[]=eContent");
			Object searchDataRaw = searchUrl.getContent();
			if (searchDataRaw instanceof InputStream) {
				String searchDataJson = Util.convertStreamToString((InputStream) searchDataRaw);
				//logger.debug(searchDataJson);
				try {
					JSONObject searchData = new JSONObject(searchDataJson);
					JSONObject result = searchData.getJSONObject("result");
					if (result.getInt("recordCount") > 0){
						//Found a record
						JSONArray recordSet = result.getJSONArray("recordSet");
						JSONObject firstRecord = recordSet.getJSONObject(0);
						String recordId = firstRecord.getString("id");
						return recordId;
					}
				} catch (JSONException e) {
					logger.error("Unable to load search result", e);
					processLog.incErrors();
					processLog.addNote("Unable to load search result " + e.toString());
				}
			}else{
				logger.error("Error searching for isbn " + isn);
				processLog.incErrors();
				processLog.addNote("Error searching for isbn " + isn);
			}
		} catch (Exception e) {
			processLog.incErrors();
			processLog.addNote("Error searching for isbn " + isn);
		}
		return null;
	}
}
