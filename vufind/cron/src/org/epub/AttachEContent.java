package org.epub;

import java.io.File;
import java.io.FileFilter;
import java.io.IOException;
import java.io.InputStream;
import java.net.URL;
import java.net.URLEncoder;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Date;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.apache.log4j.Logger;
import org.ini4j.Profile.Section;
import org.json.JSONArray;
import org.json.JSONObject;
import org.vufind.IProcessHandler;
import org.vufind.Util;

public class AttachEContent implements IProcessHandler {
	protected Logger logger;
	
	//Configuration settings
	private String sourceDirectory;
	protected String libraryDirectory;
	private String vufindUrl;
	
	private String econtentDBConnectionInfo;
	private Connection econtentConn = null;
	
	//Saved settings
	private PreparedStatement getRelatedRecords;
	private PreparedStatement addEContentItem;
	private PreparedStatement doesItemExist;
	private PreparedStatement createLogEntry;
	private PreparedStatement markLogEntryFinished = null;
	private PreparedStatement updateRecordsProcessed;
	private long logEntryId = -1;
	
	protected int filesProcessed = 0;
	protected int maxRecordsToProcess = -1;
	protected ArrayList<ImportResult> importResults = new ArrayList<ImportResult>();
	
	@Override
	public void doCronProcess(Section processSettings, Section generalSettings, Logger logger) {
		this.logger = logger;
		logger.info("Loading files from folders");

		boolean configLoaded = loadConfig(processSettings, generalSettings);
		if (!configLoaded){
			System.out.println("Configuration could not be loaded, see log file");
			return;
		}

		// Connect to the VuFind MySQL database
		try {
			econtentConn = DriverManager.getConnection(econtentDBConnectionInfo);
			
			//Setup prepared statements for processing the folder
			createLogEntry = econtentConn.prepareStatement("INSERT INTO econtent_attach (sourcePath, dateStarted, status) VALUES (?, ?, 'running')", PreparedStatement.RETURN_GENERATED_KEYS);
			markLogEntryFinished = econtentConn.prepareStatement("UPDATE econtent_attach SET dateFinished = ?, recordsProcessed = ?, status = 'finished' WHERE id = ?");
			updateRecordsProcessed = econtentConn.prepareStatement("UPDATE econtent_attach SET recordsProcessed = ? WHERE id = ?");
			getRelatedRecords = econtentConn.prepareStatement("SELECT id, accessType, source FROM econtent_record WHERE isbn like ?");
			doesItemExist = econtentConn.prepareStatement("SELECT id from econtent_item WHERE filename = ? AND recordId = ?");
			addEContentItem = econtentConn.prepareStatement("INSERT INTO econtent_item (filename, acsId, recordId, item_type, addedBy, date_added, date_updated) VALUES (?, ?, ?, ?, ?, ?, ?)");
			
			//Add a log entry to indicate that the source folder is being processed
			createLogEntry.setString(1, sourceDirectory);
			createLogEntry.setLong(2, new Date().getTime() / 1000);
			createLogEntry.executeUpdate();
			ResultSet logResult = createLogEntry.getGeneratedKeys();
			if (logResult.next()){
				logEntryId = logResult.getLong(1);
			}
			
			// Loop through all files and sub-folders in the source folder
			File folder = new File(sourceDirectory);
			processFolder(folder);
			
			
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error establishing connection to database " + econtentDBConnectionInfo, ex);
			return;
		}finally {
			logger.info("Marking log entry finished");
			try {
				markLogEntryFinished.setLong(1, new Date().getTime() / 1000);
				markLogEntryFinished.setLong(2, this.filesProcessed);
				markLogEntryFinished.setLong(3, logEntryId);
				markLogEntryFinished.executeUpdate();
			} catch (SQLException e) {
				logger.error("Error importing marking log as finished ", e);
			}
		}
	}

	private void processFolder(File folder) {
		//Check to see if there are subfolders within this folder
		File[] subFolders = folder.listFiles(new FileFilter() {
			@Override
			public boolean accept(File pathname) {
				return pathname.isDirectory();
			}
		});
		for (File subFolder : subFolders){
			processFolder(subFolder); 
		}
		Pattern nameRegex = Pattern.compile("([\\d-]+)\\.(pdf|epub)", Pattern.CANON_EQ | Pattern.CASE_INSENSITIVE | Pattern.UNICODE_CASE);
		File[] files = folder.listFiles();
		for (File file : files) {
			logger.info("Processing file " + file.getName());
			if (file.isDirectory()) {
				//TODO: Determine how to deal with nested folders?
				//processFolder(file);
			} else {
				// File check to see if it is of a known type
				Matcher nameMatcher = nameRegex.matcher(file.getName());
				if (nameMatcher.matches()) {
					ImportResult importResult = new ImportResult();
					String isbn = nameMatcher.group(1);
					String fileType = nameMatcher.group(2).toLowerCase();
					importResult.setBaseFilename(isbn);
					isbn = isbn.replaceAll("-", "");
					importResult.setISBN(isbn);
					importResult.setCoverImported("");
					
					try {
						// Get the record for the isbn
						getRelatedRecords.setString(1, "%" + isbn + "%");
						ResultSet existingRecords = getRelatedRecords.executeQuery();
						if (!existingRecords.next()){
							//No record found 
							logger.info("Could not find record for ISBN " + isbn);
							importResult.setStatus(fileType, "failed", "Could not find record for ISBN " + isbn);
						}else{
							logger.info("Found at least one record for " + isbn);
							if (existingRecords.last()){
								if (existingRecords.getRow() >= 2){
									logger.info("Multiple records were found for ISBN " + isbn);
									importResult.setStatus(fileType, "failed", "Multiple records were found for ISBN " + isbn);
								}else{
									//We have an existing record
									existingRecords.first();
									String recordId = existingRecords.getString("id");
									String accessType = existingRecords.getString("accessType");
									String source = existingRecords.getString("source");
									logger.info("  Attaching file to " + recordId + " accessType = " + accessType + " source=" + source);
										
									// Copy the file to the library if it does not exist already
									File resultsFile = new File(libraryDirectory + source + "_" + file.getName());
									if (resultsFile.exists()) {
										logger.info("Skipping file because it already exists in the library");
										importResult.setStatus(fileType, "skipped" ,"File has already been copied to library");
									} else {
										logger.info("Importing file " + file.getName());
										//Check to see if the file has already been added to the library.
										doesItemExist.setString(1, file.getName());
										doesItemExist.setString(2, recordId);
										ResultSet existingItems = doesItemExist.executeQuery();
										if (existingItems.next()){
											//The item already exists
											logger.info("  the file has already been attached to this record");
											importResult.setStatus(fileType, "skipped" ,"The file has already been aded as an eContent Item");
										}else{
											
											try {
												logger.info("  copying the file to library source=" + file + " dest=" + resultsFile);
												//Copy the pdf file to the library
												Util.copyFile(file, resultsFile);
												
												//Add file to acs server
												boolean addedToAcs = true;
												if (accessType.equals("acs")){
													System.out.println("Adding file to the ACS server");
													addedToAcs = addFileToAcsServer(fileType, resultsFile, importResult);
												}
												
												if (addedToAcs){
													//filename, acsId, recordId, item_type, addedBy, date_added, date_updated
													long curTimeSec = new Date().getTime() / 1000;
													addEContentItem.setString(1, resultsFile.getName());
													addEContentItem.setString(2, importResult.getAcsId());
													addEContentItem.setString(3, recordId);
													addEContentItem.setString(4, fileType);
													addEContentItem.setLong(5, -1);
													addEContentItem.setLong(6, curTimeSec);
													addEContentItem.setLong(7, curTimeSec);
													int rowsInserted = addEContentItem.executeUpdate();
													if (rowsInserted == 1){
														importResult.setStatus(fileType, "success", "");
														logger.info("  added to the database");
													}else{
														logger.info("  file could not be added to the database");
													}
												}else{
													logger.info("  the file could not be added to the acs server");
												}
												
												if (importResult.getSatus(fileType).equals("failed")){
													//If we weren't able to add the file correctly, remove it so it will be processed next time. 
													resultsFile.delete();
												}
												
											} catch (IOException e) {
												logger.error("Error copying file to record", e);
												importResult.setStatus(fileType, "failed", "Error copying file " + e.toString());
											}
										}
									}
								}
							}
						}
					} catch (SQLException e) {
						logger.error("Error finding related records", e);
						importResult.setStatus("pdf", "failed", "SQL error processing file " + e.toString());
					}
					importResults.add(importResult);
					//Update that another file has been processed.
					filesProcessed++;
					try {
						updateRecordsProcessed.setLong(1, filesProcessed);
						updateRecordsProcessed.setLong(2, logEntryId);
						updateRecordsProcessed.executeUpdate();
					} catch (SQLException e) {
						logger.error("Error updating number of records processed.", e);
					}
				}
			}
		}
		
	}
	
	protected boolean loadConfig(Section processSettings, Section generalSettings) {
		econtentDBConnectionInfo = generalSettings.get("econtentDatabase");
		if (econtentDBConnectionInfo == null || econtentDBConnectionInfo.length() == 0) {
			logger.error("Database connection information for eContent database not found in General Settings.  Please specify connection information in a econtentDatabase key.");
			return false;
		}
		
		vufindUrl = generalSettings.get("vufindUrl");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
			return false;
		}
		
		libraryDirectory = generalSettings.get("eContentLibrary");
		if (libraryDirectory == null || libraryDirectory.length() == 0) {
			logger.error("Library not found in process Settings.  Please specify the path to the eContent library as the eContentLibrary key.");
			return false;
		}
		if (!libraryDirectory.endsWith("\\") & !libraryDirectory.endsWith("/")) {
			libraryDirectory += "/";
		}
		
		sourceDirectory = processSettings.get("source");
		if (sourceDirectory == null || sourceDirectory.length() == 0) {
			logger.error("Source information not found in Process Settings.  Please specify source key.");
			return false;
		}
		
		return true;
	}
	
	protected boolean addFileToAcsServer(String type, File sourceFile, ImportResult result){
		//Call an API on vufind to make this easier and promote code reuse
		try {
			URL apiUrl = new URL(vufindUrl + "API/ItemAPI?method=addFileToAcsServer&filename=" + URLEncoder.encode(sourceFile.getName(), "utf8"));
			
			String responseJson = Util.convertStreamToString((InputStream)apiUrl.getContent());
			logger.info("ACS Response: " + responseJson);
			JSONObject responseData = new JSONObject(responseJson);
			if (responseData.has("error")){
				result.setStatus(type, "failed", "Error adding to ACS Server " + responseData.getString("error") );
				return false;
			}else{
				JSONObject resultObject = responseData.getJSONObject("result");
				if (resultObject.has("acsId")){
					result.setAcsId(resultObject.getString("acsId"));
					return true;
				}else{
					result.setStatus(type, "failed", "Unable to retrieve ACS Id" );
					return true;
				}
				
			}
		} catch (Exception e) {
			result.setStatus(type, "failed", "Could not add file to ACS server " + e.toString());
			return false;
		}
		
	}
}
