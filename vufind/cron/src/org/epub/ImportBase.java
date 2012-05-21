package org.epub;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.FileWriter;
import java.io.IOException;
import java.io.InputStream;
import java.net.URL;
import java.net.URLEncoder;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.zip.ZipEntry;
import java.util.zip.ZipInputStream;
import java.util.zip.ZipOutputStream;

import org.apache.log4j.Logger;
import org.ini4j.Profile.Section;
import org.json.JSONArray;
import org.json.JSONObject;
import org.vufind.Util;

public abstract class ImportBase {

	protected String databaseConnectionInfo;
	protected String sourceDirectory;
	protected String libraryDirectory;
	protected Logger logger;
	protected String coverDirectory;
	protected String tempDirectory;
	protected String resultsDirectory;
	protected String vufindUrl;
	protected String accessType;
	protected String bookcoverUrl;
	protected ArrayList<ImportResult> importResults = new ArrayList<ImportResult>();
	protected Connection conn;

	public ImportBase() {
		super();
	}

	protected String getRecordsToAttachTo(ImportResult importResult, String baseFilename, String isbn) {
	// Update vufind database with information about the epub file
		// Query the solr index to see if we can find the record id for the isbn or title
		try {
			URL searchURL =  new URL(vufindUrl + "API/SearchAPI?method=search&lookfor=" + isbn + "&type=ISN&shard[]=eContent");
			logger.info("Searching for existing record " + searchURL.toString());
			Object searchDataRaw = searchURL.getContent();
			if (searchDataRaw instanceof InputStream) {
				String searchDataJson = Util.convertStreamToString((InputStream) searchDataRaw);
				//System.out.println("Json for search results " + searchDataRaw);
				JSONObject searchData = new JSONObject(searchDataJson);
				JSONObject result = searchData.getJSONObject("result");
				if (result.has("recordSet")){
					JSONArray recordSet = result.getJSONArray("recordSet");
					if (recordSet.length() == 1){
						JSONObject curRecord = recordSet.getJSONObject(0);
						String id = curRecord.getString("id");
						return id;
					}else{
						importResult.addNote("Did not automatically attach " + baseFilename + " to VuFind because multiple possible records were found.");
					}
				}
			}
		} catch (Exception e) {
			importResult.addNote("Folder " + baseFilename + " is invalid because VuFind could not be searched for the record.");
			e.printStackTrace();
		}
		return null;
	}

	protected boolean loadConfig(Section processSettings, Section generalSettings) {
		databaseConnectionInfo = generalSettings.get("database");
		if (databaseConnectionInfo == null || databaseConnectionInfo.length() == 0) {
			logger.error("Database connection information not found in process Settings.  Please specify connection information in a database key.");
			return false;
		}
		
		// Get the source directory from the ini
		sourceDirectory = processSettings.get("sourceDirectory");
		if (sourceDirectory == null || sourceDirectory.length() == 0) {
			logger.error("Source Directory not found in process Settings.  Please specify the path to the raw files as the sourceDirectory key.");
			return false;
		}
		if (!sourceDirectory.endsWith("\\") & !sourceDirectory.endsWith("/")) {
			sourceDirectory += "/";
		}
	
		libraryDirectory = processSettings.get("library");
		if (libraryDirectory == null || libraryDirectory.length() == 0) {
			logger.error("Library not found in process Settings.  Please specify the path to the EContent library as the library key.");
			return false;
		}
		if (!libraryDirectory.endsWith("\\") & !libraryDirectory.endsWith("/")) {
			libraryDirectory += "/";
		}
		
		coverDirectory = processSettings.get("coverDirectory");
		if (coverDirectory == null || coverDirectory.length() == 0) {
			logger.error("Cover directory not found in process Settings.  Please specify the path where covers should be stored as the coverDirectory key.");
			return false;
		}
		if (!coverDirectory.endsWith("\\") & !coverDirectory.endsWith("/")) {
			coverDirectory += "/";
		}
	
		tempDirectory = processSettings.get("tempDirectory");
		if (tempDirectory == null || tempDirectory.length() == 0) {
			logger.error("Temp Directory not found in process Settings.  Please specify a path that can be used to manipulate the files as the tempDirectory key.");
			return false;
		}
		if (!tempDirectory.endsWith("\\") & !tempDirectory.endsWith("/")) {
			tempDirectory += "/";
		}
		
		resultsDirectory = processSettings.get("resultsDirectory");
		if (resultsDirectory == null || resultsDirectory.length() == 0) {
			logger.error("Results Directory not found in process Settings.  Please specify a path that can be used to manipulate the files as the resultsDirectory key.");
			return false;
		}
		if (!resultsDirectory.endsWith("\\") & !resultsDirectory.endsWith("/")) {
			resultsDirectory += "/";
		}
		
		vufindUrl = generalSettings.get("vufindUrl");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
			return false;
		}
		
		bookcoverUrl = generalSettings.get("bookcoverUrl");
		if (bookcoverUrl == null || bookcoverUrl.length() == 0) {
			logger.error("Unable to get URL for Book covers in General settings.  Please add a bookcoverUrl key.");
			return false;
		}
		
		
		String drmTypeStr = processSettings.get("drmType");
		if (drmTypeStr == null || drmTypeStr.length() == 0) {
			logger.error("Unable to get DRM Type in Process settings.  Please add a drmType key.");
			return false;
		}else{
			if (!drmTypeStr.matches("free|singleUse|acs")){
				logger.error("Invalid DRM Type in Process settings.  DRM Type must be free, singleUse, or acs.");
				return false;
			}else{
				accessType = drmTypeStr;
			}
		}
		
		return true;
	}
	
	protected void writeReport(String reportName){
	// Generate report for cataloging showing what has been imported into VuFind
		SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd_HH-mm");
		File resultFile = new File(resultsDirectory + reportName + "Results_" + dateFormat.format(new Date())  + ".csv");
		try {
			FileWriter writer = new FileWriter(resultFile);
			writer.write("ISBN,Volume,Base Filename,Cover Imported,PDF Imported,EPub Imported,Notes\r\n");
			for (ImportResult result : importResults){
				writer.write(result.getISBN() + ",");
				writer.write(result.getVolume() + ",");
				writer.write(result.getBaseFilename() + ",");
				writer.write(result.getCoverImported() + ",");
				writer.write(result.getPdfImported() + ",");
				writer.write(result.getEpubImported() + ",");
				writer.write(result.getNotes() + "\r\n");
			}
			writer.flush();
			writer.close();
		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
	}

	protected void createZipFile(File zipFile, File folderToZip, Logger logger) {
		if (!folderToZip.isDirectory()) {
			return;
		}
	
		try {
			ZipOutputStream out = new ZipOutputStream(new FileOutputStream(zipFile));
			logger.info("Creating : " + zipFile.getName());
			addDir(folderToZip, out, folderToZip.getAbsolutePath());
			// Complete the ZIP file
			out.close();
	
		} catch (IOException e) {
			e.printStackTrace();
			System.exit(1);
		}
	
	}

	private void addDir(File dirObj, ZipOutputStream out, String relativePath) throws IOException {
		File[] files = dirObj.listFiles();
		byte[] tmpBuf = new byte[1024];
	
		for (File file : files) {
			if (file.isDirectory()) {
				addDir(file, out, relativePath);
				continue;
			}
	
			FileInputStream in = new FileInputStream(file.getAbsolutePath());
	
			String zipEntryName = file.getAbsolutePath().substring(relativePath.length() + 1);
			zipEntryName = zipEntryName.replaceAll("\\\\", "/");
			//System.out.println("Adding file " + zipEntryName);
			out.putNextEntry(new ZipEntry(zipEntryName));
	
			// Transfer from the file to the ZIP file
			int len;
			while ((len = in.read(tmpBuf)) > 0) {
				out.write(tmpBuf, 0, len);
			}
	
			// Complete the entry
			out.closeEntry();
			in.close();
		}
	}

	public void extractZipFiles(File zipFile, String destination) {
		try {
			byte[] buf = new byte[1024];
			ZipInputStream zipinputstream = null;
			ZipEntry zipentry;
			zipinputstream = new ZipInputStream(new FileInputStream(zipFile));
	
			zipentry = zipinputstream.getNextEntry();
			while (zipentry != null) {
				// for each entry to be extracted
				String entryName = zipentry.getName();
				int n;
				FileOutputStream fileoutputstream;
				File newFile = new File(entryName);
				String directory = newFile.getParent();
	
				if (directory == null) {
					if (newFile.isDirectory())
						break;
				}
	
				fileoutputstream = new FileOutputStream(destination + entryName);
	
				while ((n = zipinputstream.read(buf, 0, 1024)) > -1)
					fileoutputstream.write(buf, 0, n);
	
				fileoutputstream.close();
				zipinputstream.closeEntry();
				zipentry = zipinputstream.getNextEntry();
	
			}// while
	
			zipinputstream.close();
		} catch (Exception e) {
			e.printStackTrace();
		}
	}

	

	protected void addFileToDatabase(ImportResult result, String recordsToAttachTo, File coverImage, int volume, int numVolumes, String fileName, String source, String type, int accesType, String acsId) {
		try {
			PreparedStatement epubFilesStmt = conn.prepareStatement("SELECT * FROM epub_files WHERE relatedRecords like ? AND filename = ?");
			epubFilesStmt.setString(1, recordsToAttachTo);
			epubFilesStmt.setString(2, fileName);
			
			ResultSet existingFilesStmt = epubFilesStmt.executeQuery();
			if (existingFilesStmt.next()){
				//We already have a record so we don't need to update this one.
				logger.info(fileName + " is already attached to " + recordsToAttachTo);
				result.setStatus(type, "skipped", "Already attached to " + recordsToAttachTo);
			}else{
				PreparedStatement insertEpubStmt = conn.prepareStatement("INSERT INTO epub_files (filename, cover, acsId, relatedRecords, type, source, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
				String note = "";
				if (numVolumes != 1){
					note = "Volume " + volume;
				}
				insertEpubStmt.setString(1, fileName);
				insertEpubStmt.setString(2, coverImage == null ? "" : coverImage.getName());
				insertEpubStmt.setString(4, acsId);
				insertEpubStmt.setString(5, recordsToAttachTo);
				insertEpubStmt.setString(6, type);
				insertEpubStmt.setString(7, source);
				insertEpubStmt.setString(8, note);
				int recordsInserted = insertEpubStmt.executeUpdate();
				if (recordsInserted != 1){
					result.setStatus(type, "failed", "The record could not be inserted into the database.");
					return;
				}
			}
			result.setStatus(type, "success", "");
			
		} catch (SQLException e) {
			result.setStatus(type, "failed", "The database could not be updated.");
			e.printStackTrace();
			return;
		}
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