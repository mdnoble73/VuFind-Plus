package org.vufind;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Iterator;
import java.util.Set;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.solrmarc.tools.Utils;

public class UpdateResourceInformation implements IMarcRecordProcessor, IRecordProcessor{
	private Logger logger;
	private Connection conn = null;
	
	private boolean updateUnchangedResources = false;
	
	private PreparedStatement resourceUpdateStmt = null;
	private PreparedStatement resourceInsertStmt = null;
	private PreparedStatement deleteResourceStmt = null;
	
	//Code related to subjects of resources
	private HashMap<String, Long> existingSubjects;
	private PreparedStatement getExistingSubjectsStmt = null;
	private PreparedStatement insertSubjectStmt = null;
	private PreparedStatement clearResourceSubjectsStmt = null;
	private PreparedStatement linkResourceToSubjectStmt = null;
	
	//Code related to call numbers
	private HashMap<String, Long> locations;
	private PreparedStatement getLocationsStmt = null;
	private PreparedStatement clearResourceCallnumbersStmt = null;
	private PreparedStatement addCallnumberToResourceStmt = null;
	
	//Information about how to process call numbers for local browse
	private String itemTag;
	private String callNumberSubfield;
	private String locationSubfield;
	
	//A list of existing resources so we can mark records as deleted if they no longer exist
	private HashMap<String, BasicResourceInfo> existingResources = new HashMap<String, BasicResourceInfo>();
	
	private ProcessorResults results = new ProcessorResults("Update Resources");
	
	public boolean init(Ini configIni, String serverName, Logger logger) {
		this.logger = logger;
		// Load configuration
		String databaseConnectionInfo = Util.cleanIniValue(configIni.get("Database", "database_vufind_jdbc"));
		if (databaseConnectionInfo == null || databaseConnectionInfo.length() == 0) {
			logger.error("Database connection information not found in General Settings.  Please specify connection information in a database key.");
			return false;
		}
		try {
			conn = DriverManager.getConnection(databaseConnectionInfo);
		} catch (SQLException e) {
			logger.error("Could not connect to database", e);
			return false;
		}

		String vufindUrl = configIni.get("Site", "url");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
		}
		
		String updateUnchangedResourcesVal = configIni.get("Reindex", "updateUnchangedResources");
		if (updateUnchangedResourcesVal != null && updateUnchangedResourcesVal.length() > 0){
			updateUnchangedResources = Boolean.parseBoolean(updateUnchangedResourcesVal);
		}
		
		
		try {
			// Check to see if the record already exists
			resourceUpdateStmt =conn.prepareStatement("UPDATE resource SET title = ?, title_sort = ?, author = ?, isbn = ?, upc = ?, format = ?, format_category = ?, marc_checksum=?, marc = ?, shortId = ?, date_updated=?, deleted=0 WHERE id = ?");
			resourceInsertStmt = conn.prepareStatement("INSERT INTO resource (title, title_sort, author, isbn, upc, format, format_category, record_id, shortId, marc_checksum, marc, source, deleted) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)", PreparedStatement.RETURN_GENERATED_KEYS);
			deleteResourceStmt = conn.prepareStatement("UPDATE resource SET deleted = 1 WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			
			getExistingSubjectsStmt = conn.prepareStatement("SELECT * FROM subject");
			ResultSet existingSubjectsRS = getExistingSubjectsStmt.executeQuery();
			existingSubjects = new HashMap<String, Long>();
			while (existingSubjectsRS.next()){
				existingSubjects.put(existingSubjectsRS.getString("subject"),existingSubjectsRS.getLong("id") );
			}
			existingSubjectsRS.close();
			insertSubjectStmt = conn.prepareStatement("INSERT INTO subject (subject) VALUES (?)", PreparedStatement.RETURN_GENERATED_KEYS);
			clearResourceSubjectsStmt = conn.prepareStatement("DELETE FROM resource_subject WHERE resourceId = ?");
			linkResourceToSubjectStmt = conn.prepareStatement("INSERT INTO resource_subject (subjectId, resourceId) VALUES (?, ?)");
			
			getLocationsStmt = conn.prepareStatement("SELECT locationId, code FROM location");
			ResultSet locationsRS = getLocationsStmt.executeQuery();
			locations = new HashMap<String, Long>();
			while (locationsRS.next()){
				locations.put(locationsRS.getString("code").toLowerCase(),locationsRS.getLong("locationId") );
			}
			
			clearResourceCallnumbersStmt = conn.prepareStatement("DELETE FROM resource_callnumber WHERE resourceId = ?");
			addCallnumberToResourceStmt = conn.prepareStatement("INSERT INTO resource_callnumber (resourceId, locationId, callnumber) VALUES (?, ?, ?)");
			
			//Load field information for local call numbers
			itemTag = configIni.get("Reindex", "itemTag");
			callNumberSubfield = configIni.get("Reindex", "callNumberSubfield");
			locationSubfield = configIni.get("Reindex", "locationSubfield");
			
			//Get a list of resources that have already been installed. 
			PreparedStatement existingResourceStmt = conn.prepareStatement("SELECT record_id, id, marc_checksum, deleted from resource where source = 'VuFind'");
			ResultSet existingResourceRS = existingResourceStmt.executeQuery();
			while (existingResourceRS.next()){
				String ilsId = existingResourceRS.getString("record_id");
				BasicResourceInfo resourceInfo = new BasicResourceInfo(ilsId, existingResourceRS.getLong("id"), existingResourceRS.getLong("marc_checksum"), existingResourceRS.getBoolean("deleted"));
				existingResources.put(ilsId, resourceInfo);
			}
			
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Unable to setup prepared statements", ex);
			return false;
		}
		return true;
		
	}

	@SuppressWarnings("unchecked")
	@Override
	public boolean processMarcRecord(MarcProcessor processor, MarcRecordDetails recordInfo, int recordStatus, Logger logger) {
		Long resourceId = -1L;
		
		boolean updateSubjectAndCallNumber = true;
		
		try {
			//Check to see if we have an existing resource
			if (existingResources.containsKey(recordInfo.getId())){
				results.incRecordsProcessed();
				BasicResourceInfo basicResourceInfo = existingResources.get(recordInfo.getId());
				resourceId = basicResourceInfo.getResourceId();
				//Remove the resource from the existingResourcesList so 
				//We can determine which resources no longer exist
				existingResources.remove(recordInfo.getId());
				if (updateUnchangedResources || (basicResourceInfo.getMarcChecksum() != recordInfo.getChecksum())){
					// Update the existing record
					String title = recordInfo.getTitle();
					String author = recordInfo.getAuthor();
					
					// Update resource SQL
					resourceUpdateStmt.setString(1, Util.trimTo(200, title));
					resourceUpdateStmt.setString(2, Util.trimTo(200, recordInfo.getSortTitle()));
					resourceUpdateStmt.setString(3, Util.trimTo(255, author));
					resourceUpdateStmt.setString(4, Util.trimTo(13, recordInfo.getIsbn()));
					resourceUpdateStmt.setString(5, Util.trimTo(13, recordInfo.getFirstFieldValueInSet("upc")));
					resourceUpdateStmt.setString(6, Util.trimTo(50, recordInfo.getFirstFieldValueInSet("format")));
					resourceUpdateStmt.setString(7, Util.trimTo(50, recordInfo.getFirstFieldValueInSet("format_category")));
					resourceUpdateStmt.setLong(8, recordInfo.getChecksum());
					resourceUpdateStmt.setBytes(9, recordInfo.getRawRecord().getBytes());
					resourceUpdateStmt.setString(10, recordInfo.getShortId());
					resourceUpdateStmt.setLong(11, new Date().getTime() / 1000);
					resourceUpdateStmt.setLong(12, resourceId);

					int rowsUpdated = resourceUpdateStmt.executeUpdate();
					if (rowsUpdated == 0) {
						logger.debug("Unable to update resource for record " + recordInfo.getId() + " " + resourceId);
						results.incErrors();
					}else{
						results.incUpdated();
					}
				}else{
					updateSubjectAndCallNumber = false;
					results.incSkipped();
				}
				

			} else {
				String author = recordInfo.getAuthor();
				// Update resource SQL
				resourceInsertStmt.setString(1, Util.trimTo(200, recordInfo.getTitle()));
				resourceInsertStmt.setString(2, Util.trimTo(200, recordInfo.getSortTitle()));
				resourceInsertStmt.setString(3, Util.trimTo(255, author));
				resourceInsertStmt.setString(4, Util.trimTo(13, recordInfo.getIsbn()));
				resourceInsertStmt.setString(5, Util.trimTo(13, recordInfo.getFirstFieldValueInSet("upc")));
				resourceInsertStmt.setString(6, Util.trimTo(50, recordInfo.getFirstFieldValueInSet("format")));
				resourceInsertStmt.setString(7, Util.trimTo(50, recordInfo.getFirstFieldValueInSet("format_category")));
				resourceInsertStmt.setString(8, recordInfo.getId());
				resourceInsertStmt.setString(9, recordInfo.getShortId());
				resourceInsertStmt.setLong(10, recordInfo.getChecksum());
				resourceInsertStmt.setBytes(11, recordInfo.getRawRecord().getBytes());
				resourceInsertStmt.setString(11, "VuFind");

				int rowsUpdated = resourceInsertStmt.executeUpdate();
				if (rowsUpdated == 0) {
					logger.debug("Unable to insert record " + recordInfo.getId());
					results.incErrors();
				} else {
					results.incAdded();
					//Get the resourceId
					ResultSet insertedResourceIds = resourceInsertStmt.getGeneratedKeys();
					if (insertedResourceIds.next()){
						resourceId = insertedResourceIds.getLong(1);
					}
				}
			}
			
			if (resourceId != -1 && updateSubjectAndCallNumber){
				clearResourceSubjectsStmt.setLong(1, resourceId);
				clearResourceSubjectsStmt.executeUpdate();
				clearResourceCallnumbersStmt.setLong(1, resourceId);
				clearResourceCallnumbersStmt.executeUpdate();
				//Add subjects 
				Object subjects = recordInfo.getFields().get("topic_facet");
				Set<String> subjectsToProcess = new HashSet<String>();
				if (subjects != null){
					if (subjects instanceof String){
						subjectsToProcess.add((String)subjects); 
					}else{
						subjectsToProcess.addAll((Set<String>)subjects);
					}
					Iterator<String> subjectIterator = subjectsToProcess.iterator();
					while (subjectIterator.hasNext()){
						String curSubject = subjectIterator.next();
						//Trim trailing punctuation from the subject
						curSubject = Utils.cleanData(curSubject);
						//Check to see if the subject exists already
						Long subjectId = existingSubjects.get(curSubject);
						if (subjectId == null){
							//Insert the subject into the subject table
							insertSubjectStmt.setString(1, curSubject);
							insertSubjectStmt.executeUpdate();
							ResultSet generatedKeys = insertSubjectStmt.getGeneratedKeys();
							if (generatedKeys.next()){
								subjectId = generatedKeys.getLong(1);
								existingSubjects.put(curSubject, subjectId);
							}
						}
						if (subjectId != null){
							linkResourceToSubjectStmt.setLong(1, subjectId);
							linkResourceToSubjectStmt.setLong(2, resourceId);
							linkResourceToSubjectStmt.executeUpdate();
						}
					}
				}
				
				if (callNumberSubfield != null && callNumberSubfield.length() > 0 && locationSubfield != null && locationSubfield.length() > 0){
					//Add call numbers based on the location
					Set<LocalCallNumber> localCallNumbers = recordInfo.getLocalCallNumbers(itemTag, callNumberSubfield, locationSubfield);
					for (LocalCallNumber curCallNumber : localCallNumbers){
						Long locationId = locations.get(curCallNumber.getLocationCode());
						if (locationId != null){
							addCallnumberToResourceStmt.setLong(1, resourceId);
							addCallnumberToResourceStmt.setLong(2, locationId);
							addCallnumberToResourceStmt.setString(3, curCallNumber.getCallNumber());
							addCallnumberToResourceStmt.executeUpdate();
						}
					}
				}
			}
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error updating resource for record " + recordInfo.getId() + " " + ex.toString());
			System.out.println(recordInfo.getTitle());
			results.incErrors();
		}
		return true;
	}

	@Override
	public void finish() {
		//Mark any resources that no longer exist as deleted.
		logger.info("Deleting resources that no longer from resources table.");
		for (BasicResourceInfo resourceInfo : existingResources.values()){
			try {
				deleteResourceStmt.setLong(1, resourceInfo.getResourceId());
				deleteResourceStmt.executeUpdate();
			} catch (SQLException e) {
				logger.error("Unable to delete "  + resourceInfo.getResourceId(), e);
			}
			results.incDeleted();
		}
		try {
			conn.close();
		} catch (SQLException e) {
			logger.error("Unable to close connection", e);
		}
	}

	@Override
	public ProcessorResults getResults() {
		return results;
	}
}
