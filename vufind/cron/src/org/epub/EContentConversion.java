package org.epub;

import java.io.InputStream;
import java.net.URL;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Types;
import java.util.Date;

import org.apache.log4j.Logger;
import org.ini4j.Profile.Section;
import org.vufind.IProcessHandler;
import org.vufind.Util;

public class EContentConversion implements IProcessHandler{
	private String vufindDBConnectionInfo;
	private String econtentDBConnectionInfo;
	private Connection vufindConn = null;
	private Connection econtentConn = null;
	private String vufindUrl;
	
	@Override
	public void doCronProcess(Section processSettings, Section generalSettings, Logger logger) {
		logger.info("Converting eContent from old format to new format");
		
		//Load configuration
		if (!loadConfig(processSettings, generalSettings, logger)){
			return;
		}
		
		try {
			//Connect to the vufind database
			vufindConn = DriverManager.getConnection(vufindDBConnectionInfo);
			//Connect to the eContent database
			econtentConn = DriverManager.getConnection(econtentDBConnectionInfo);
			
			//Get a list of all records to be converted
			PreparedStatement epubFilesToConvert = vufindConn.prepareStatement("SELECT * FROM epub_files");
			PreparedStatement createEContentRecord = econtentConn.prepareStatement("INSERT INTO econtent_record (ilsId, cover, source, title, author, description, addedBy, date_added, date_updated, accessType, availableCopies) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			PreparedStatement updateEContentRecord = econtentConn.prepareStatement("UPDATE econtent_record SET title=?, author=?, isbn=?, upc=? WHERE id=?");
			PreparedStatement getResourceInfo = vufindConn.prepareStatement("SELECT * FROM resource WHERE record_id = ?");
			PreparedStatement createEContentItem = econtentConn.prepareStatement("INSERT INTO econtent_item (filename, acsId, folder, recordId, item_type, notes, addedBy, date_added, date_updated) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
			PreparedStatement checkForExistingEContentRecord = econtentConn.prepareStatement("SELECT id from econtent_record WHERE ilsId = ?");
			PreparedStatement getEContentItem = econtentConn.prepareStatement("SELECT id from econtent_item WHERE filename = ? and folder = ? and item_type = ?");
			ResultSet epubFiles = epubFilesToConvert.executeQuery();
			
			//For each record
			while (epubFiles.next()){
				//Get the files that will be attached as items 
				String relatedRecords = epubFiles.getString("relatedRecords");
				String epubId = epubFiles.getString("id");
				logger.info("Converting epub_file id: " + epubId + " related records: " + relatedRecords);
				Integer relatedRecord;
				if (relatedRecords.length() == 0){
					relatedRecord = null;
				}else if (relatedRecords.matches(".*\\|.*")){
					String records[] = relatedRecords.split("\\|");
					try {
						relatedRecord = Integer.parseInt(records[0]);
					} catch (NumberFormatException e) {
						logger.error("Unable to get related record for item " + relatedRecords + " split into " + records.length + " sections");
						continue;
					}
				}else{
					relatedRecord = Integer.parseInt(relatedRecords);
				}
				
				Integer econtentRecordId = null;
				long currentTime = new Date().getTime();
				if (relatedRecord != null){
					checkForExistingEContentRecord.setInt(1, relatedRecord);
					ResultSet checkRecord = checkForExistingEContentRecord.executeQuery();
					if (checkRecord.next()){
						econtentRecordId = checkRecord.getInt("id");
					}
				}
				
				try {
					//Add the econtent record
					if (econtentRecordId == null){
						logger.info("Importing record for eContent file because a record did not exist.");
						//Create the record for the file
						if (relatedRecord == null){
							createEContentRecord.setNull(1, Types.VARCHAR);
						}else{
							createEContentRecord.setInt(1, relatedRecord);
						}
						createEContentRecord.setString(2, epubFiles.getString("cover"));
						createEContentRecord.setString(3, epubFiles.getString("source"));
						createEContentRecord.setString(4, epubFiles.getString("title"));
						createEContentRecord.setString(5, epubFiles.getString("author"));
						createEContentRecord.setString(6, epubFiles.getString("description"));
						createEContentRecord.setInt(7, -1);
						createEContentRecord.setLong(8, currentTime / 1000);
						createEContentRecord.setLong(9, currentTime / 1000);
						int hasDRM = epubFiles.getInt("hasDRM");
						if (hasDRM == 0){
							createEContentRecord.setString(10, "free");
						}else if (hasDRM == 1){
							createEContentRecord.setString(10, "acs");
						}else{
							createEContentRecord.setString(10, "singleUse");
						}
						createEContentRecord.setInt(11, epubFiles.getInt("availableCopies"));
						
						createEContentRecord.executeUpdate();
						ResultSet generatedKeys = createEContentRecord.getGeneratedKeys();
						if (generatedKeys.next()){
							econtentRecordId = generatedKeys.getInt(1);
						}else{
							logger.error("Unable to create eContent record.");
							continue;
						}
						
						//Get resource information to supplement the econtent record
						if (relatedRecord != null){
							logger.info("Updating resource for the title.");
							getResourceInfo.setInt(1, relatedRecord);
							ResultSet resourceInfo = getResourceInfo.executeQuery();
							if (resourceInfo.next()){
								updateEContentRecord.setString(1, resourceInfo.getString("title"));
								updateEContentRecord.setString(2, resourceInfo.getString("author"));
								updateEContentRecord.setString(3, resourceInfo.getString("isbn"));
								updateEContentRecord.setString(4, resourceInfo.getString("upc"));
								updateEContentRecord.setInt(5, econtentRecordId);
								updateEContentRecord.executeUpdate();
							}
						}
					}
					
					//Check to see if there is an item already added 
					getEContentItem.setString(1, epubFiles.getString("filename"));
					getEContentItem.setString(2, epubFiles.getString("folder"));
					getEContentItem.setString(3, epubFiles.getString("type").toLowerCase());
					ResultSet existingItem = getEContentItem.executeQuery();
					if (!existingItem.next()){
						//Insert item information
						logger.info("Adding file or folder to the record.");
						createEContentItem.setString(1, epubFiles.getString("filename"));
						createEContentItem.setString(2, epubFiles.getString("acsId"));
						createEContentItem.setString(3, epubFiles.getString("folder"));
						createEContentItem.setInt(4, econtentRecordId);
						createEContentItem.setString(5, epubFiles.getString("type").toLowerCase());
						createEContentItem.setString(6, epubFiles.getString("notes"));
						createEContentItem.setInt(7, -1);
						createEContentItem.setLong(8, currentTime / 1000);
						createEContentItem.setLong(9, currentTime / 1000);
						int recordsInserted = createEContentItem.executeUpdate();
						if (recordsInserted == 0){
							logger.error("unable to create eContent item");
						}else{
							logger.info("E-Pub File file:" + epubFiles.getString("filename") + " folder:" + epubFiles.getString("folder") + " was added to econtentRecordId " + econtentRecordId);
							//Insert into solr index
							try {
								URL updateIndexURL = new URL(vufindUrl + "EContentRecord/" + econtentRecordId + "/Reindex");
								Object updateIndexDataRaw = updateIndexURL.getContent();
								if (updateIndexDataRaw instanceof InputStream) {
									String updateIndexResponse = Util.convertStreamToString((InputStream) updateIndexDataRaw);
									logger.info("Indexing record " + econtentRecordId + " response: " + updateIndexResponse);
								}
							} catch (Exception e) {
								logger.info("Unable to reindex record " + econtentRecordId, e);
							}
						}
					}
				} catch (Exception e) {
					logger.error("Error processing epub_file " + epubId, e);
				}
			}
			
			// Disconnect from the database
			vufindConn.close();
			econtentConn.close();
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error establishing connection to database ", ex);
			return;
		}
	}

	private boolean loadConfig(Section processSettings, Section generalSettings, Logger logger) {
		vufindDBConnectionInfo = generalSettings.get("database");
		if (vufindDBConnectionInfo == null || vufindDBConnectionInfo.length() == 0) {
			logger.error("Database connection information for vufind database not found in General Settings.  Please specify connection information in a database key.");
			return false;
		}
		
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
		
		return true;
	}

}
