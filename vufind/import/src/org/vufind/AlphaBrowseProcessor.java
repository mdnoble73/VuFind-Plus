package org.vufind;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
//import java.util.HashMap;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Set;

import org.apache.log4j.Logger;
import org.ini4j.Ini;

public class AlphaBrowseProcessor implements IMarcRecordProcessor, IEContentProcessor, IRecordProcessor {
	private Logger logger;
	private Connection vufindConn;
	private ProcessorResults results;
	
	private PreparedStatement	getExistingTitleBrowseValue;
	private PreparedStatement	getExistingAuthorBrowseValue;
	private PreparedStatement	getExistingSubjectBrowseValue;
	private PreparedStatement	getExistingCallNumberBrowseValue;
	private PreparedStatement	insertTitleBrowseValue;
	private PreparedStatement	insertAuthorBrowseValue;
	private PreparedStatement	insertSubjectBrowseValue;
	private PreparedStatement	insertCallNumberBrowseValue;
	
	private PreparedStatement	getLibraryIdsForEContent;
	private PreparedStatement	getExistingTitleBrowseScopeValue;
	private PreparedStatement	getExistingAuthorBrowseScopeValue;
	private PreparedStatement	getExistingSubjectBrowseScopeValue;
	private PreparedStatement	getExistingCallNumberBrowseScopeValue;
	private PreparedStatement	insertTitleBrowseScopeValue;
	private PreparedStatement	insertAuthorBrowseScopeValue;
	private PreparedStatement	insertSubjectBrowseScopeValue;
	private PreparedStatement	insertCallNumberBrowseScopeValue;
	private PreparedStatement	updateTitleBrowseScopeValue;
	private PreparedStatement	updateAuthorBrowseScopeValue;
	private PreparedStatement	updateSubjectBrowseScopeValue;
	private PreparedStatement	updateCallNumberBrowseScopeValue;
	
	//Information about how to process call numbers for local browse
	private String itemTag;
	private String callNumberSubfield;
	private String locationSubfield;
	
	private HashMap<String, Long> existingBrowseValuesTitle = new HashMap<String, Long>();
	private HashMap<String, Long> existingBrowseValuesAuthor = new HashMap<String, Long>();
	private HashMap<String, Long> existingBrowseValuesSubject = new HashMap<String, Long>();
	private HashMap<String, Long> existingBrowseValuesCallNumber = new HashMap<String, Long>();

	public boolean init(Ini configIni, String serverName, long reindexLogId, Connection vufindConn, Connection econtentConn, Logger logger) {
		this.logger = logger;
		this.vufindConn = vufindConn;
		results = new ProcessorResults("Alpha Browse Table Update", reindexLogId, vufindConn, logger);
		results.saveResults();
		
		//Load field information for local call numbers
		itemTag = configIni.get("Reindex", "itemTag");
		callNumberSubfield = configIni.get("Reindex", "callNumberSubfield");
		locationSubfield = configIni.get("Reindex", "locationSubfield");
		
		try {
			//Setup prepared statements for later usage.  
			getLibraryIdsForEContent = econtentConn.prepareStatement("SELECT distinct libraryId from econtent_item where recordId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			
			getExistingTitleBrowseValue = vufindConn.prepareStatement("SELECT id from title_browse WHERE value = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getExistingAuthorBrowseValue = vufindConn.prepareStatement("SELECT id from author_browse WHERE value = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getExistingSubjectBrowseValue = vufindConn.prepareStatement("SELECT id from subject_browse WHERE value = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getExistingCallNumberBrowseValue = vufindConn.prepareStatement("SELECT id from callnumber_browse WHERE value = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			
			insertTitleBrowseValue = vufindConn.prepareStatement("INSERT INTO title_browse (value, sortValue) VALUES (?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			insertAuthorBrowseValue = vufindConn.prepareStatement("INSERT INTO author_browse (value, sortValue) VALUES (?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			insertSubjectBrowseValue = vufindConn.prepareStatement("INSERT INTO subject_browse (value, sortValue) VALUES (?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			insertCallNumberBrowseValue = vufindConn.prepareStatement("INSERT INTO callnumber_browse (value, sortValue) VALUES (?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			
			/*getExistingTitleBrowseScopeValue = vufindConn.prepareStatement("SELECT id, numResults, relatedRecords from title_browse_scoped_results WHERE browseValueId = ? AND scope = ? AND scopeId = ?");
			getExistingAuthorBrowseScopeValue = vufindConn.prepareStatement("SELECT id, numResults, relatedRecords from author_browse_scoped_results WHERE browseValueId = ? AND scope = ? AND scopeId = ?");
			getExistingSubjectBrowseScopeValue = vufindConn.prepareStatement("SELECT id, numResults, relatedRecords from subject_browse_scoped_results WHERE browseValueId = ? AND scope = ? AND scopeId = ?");
			getExistingCallNumberBrowseScopeValue = vufindConn.prepareStatement("SELECT id, numResults, relatedRecords from callnumber_browse_scoped_results WHERE browseValueId = ? AND scope = ? AND scopeId = ?");*/
			
			insertTitleBrowseScopeValue = vufindConn.prepareStatement("INSERT INTO title_browse_scoped_results (browseValueId, scope, scopeId, record) VALUES (?, ?, ?, ?)");
			insertAuthorBrowseScopeValue = vufindConn.prepareStatement("INSERT INTO author_browse_scoped_results (browseValueId, scope, scopeId, record) VALUES (?, ?, ?, ?)");
			insertSubjectBrowseScopeValue = vufindConn.prepareStatement("INSERT INTO subject_browse_scoped_results (browseValueId, scope, scopeId, record) VALUES (?, ?, ?, ?)");
			insertCallNumberBrowseScopeValue = vufindConn.prepareStatement("INSERT INTO callnumber_browse_scoped_results (browseValueId, scope, scopeId, record) VALUES (?, ?, ?, ?)");
			
			/*updateTitleBrowseScopeValue = vufindConn.prepareStatement("UPDATE title_browse_scoped_results SET numResults = ?, relatedRecords = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			updateAuthorBrowseScopeValue = vufindConn.prepareStatement("UPDATE author_browse_scoped_results SET numResults = ?, relatedRecords = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			updateSubjectBrowseScopeValue = vufindConn.prepareStatement("UPDATE subject_browse_scoped_results SET numResults = ?, relatedRecords = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			updateCallNumberBrowseScopeValue = vufindConn.prepareStatement("UPDATE callnumber_browse_scoped_results SET numResults = ?, relatedRecords = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);*/
			
			clearBrowseIndex("title_browse");
			clearBrowseIndex("author_browse");
			clearBrowseIndex("subject_browse");
			clearBrowseIndex("callnumber_browse");
		} catch (SQLException e) {
			results.addNote("Error setting up prepared statements for Alpha Browse Processor");
			results.incErrors();
			logger.error("Error setting up prepared statements for Alpha Browse Processor", e);
			return false;
		}

		return true;
	}
	
	@Override
	public boolean processMarcRecord(MarcProcessor processor, MarcRecordDetails recordInfo, int recordStatus, Logger logger) {
		try {
			//For alpha browse processing, everything is handled in the finish method
			if (recordInfo.isEContent()){
				results.incSkipped();
				return true;
			}
			results.incRecordsProcessed();
			HashMap<String, String> titles = recordInfo.getBrowseTitles();
			HashMap<String, String> authors = recordInfo.getBrowseAuthors();
			String recordIdFull = recordInfo.getId();
			HashMap<String, String> subjects = recordInfo.getBrowseSubjects();
			Set<LocalCallNumber> localCallNumbers = recordInfo.getLocalCallNumbers(itemTag, callNumberSubfield, locationSubfield);
			HashSet<Long> resourceLibraries = getLibrariesForPrintRecord(localCallNumbers);
			//logger.debug("found " + resourceLibraries.size() + " libraries for the resource");
			HashSet<Long> resourceLocations = getLocationsForPrintRecord(localCallNumbers);
			//logger.debug("found " + resourceLocations.size() + " locations for the resource");
			//logger.debug("found " + titles.size() + " titles for the resource");
			for (String sortTitle: titles.keySet()){
				//logger.debug("  " + curTitle);
				String curTitle = titles.get(sortTitle);
				addRecordIdToBrowse("title", resourceLibraries, resourceLocations, curTitle, sortTitle, recordIdFull);
			}
			
			//Setup author browse
			//logger.debug("found " + authors.size() + " authors for the resource");
			for (String sortAuthor: authors.keySet()){
				//logger.debug("  " + curAuthor);
				String curAuthor = authors.get(sortAuthor);
				addRecordIdToBrowse("author", resourceLibraries, resourceLocations, curAuthor, sortAuthor, recordIdFull);
			}
			
			//Setup subject browse
			//logger.debug("found " + subjects.size() + " subjects for the resource");
			for (String sortSubject: subjects.keySet()){
				//logger.debug("  " + curSubject);
				String curSubject = subjects.get(sortSubject);
				addRecordIdToBrowse("subject", resourceLibraries, resourceLocations, curSubject, sortSubject, recordIdFull);
			}
			
			//Setup call number browse
			addCallNumbersToBrowse(localCallNumbers, recordIdFull);
			
			results.incAdded();
			return true;
		} catch (SQLException e) {
			results.addNote("Error processing marc record " + e.toString());
			results.incErrors();
			logger.error("Error processing marc record ", e);
			return false;
		}finally{
			if (results.getRecordsProcessed() % 100 == 0){
				results.saveResults();
			}
		}
		
	}
	
	

	private void addCallNumbersToBrowse(Set<LocalCallNumber> localCallNumbers, String recordIdFull) throws SQLException {
		//logger.debug("found " + localCallNumbers.size() + " call numbers for the resource");
		HashMap<String, String> distinctCallNumbers = new HashMap<String, String>(); 
		for (LocalCallNumber callNumber : localCallNumbers){
			//logger.debug("  " + callNumber.getCallNumber() + " " + callNumber.getLibraryId() + " " + callNumber.getLocationId());
			distinctCallNumbers.put(Util.makeValueSortable(callNumber.getCallNumber()), callNumber.getCallNumber().trim());
		}
		for (String callNumberSort : distinctCallNumbers.keySet()){
			String callNumber = distinctCallNumbers.get(callNumberSort);
			//Get the libraries and locations for this call number
			HashSet<Long> resourceLibraries = new HashSet<Long>();
			HashSet<Long> resourceLocations = new HashSet<Long>();
			resourceLibraries.add(-1L);
			for (LocalCallNumber localCallNumber : localCallNumbers){
				if (localCallNumber.getCallNumber().equals(callNumber)){
					resourceLibraries.add(localCallNumber.getLibraryId());
					resourceLocations.add(localCallNumber.getLocationId());
				}
			}
			//logger.debug("  '" + callNumber + "'");
			addRecordIdToBrowse("callnumber", resourceLibraries, resourceLocations, callNumber, callNumberSort, recordIdFull);
		}
	}

	@Override
	public boolean processEContentRecord(ResultSet resource) {
		try {
			//For alpha browse processing, everything is handled in the finish method
			results.incEContentRecordsProcessed();
			String title = resource.getString("title");
			String subTitle = resource.getString("subTitle");
			if (subTitle.length() > 0){
				title += ": " + subTitle;
			}
			String sortTitle = title.toLowerCase().replaceAll("^(the|an|a|el|la)\\s", "");
			String author = resource.getString("author");
			Long econtentId = resource.getLong("id");
			String recordIdFull = "econtentRecord" + resource.getString("id");
			String subjectsRaw = resource.getString("subject");
			String[] subjects = subjectsRaw.split("\\r\\n|\\r|\\n");
			
			HashSet<Long> resourceLibraries = getLibrariesEContentRecord(econtentId);
			//logger.debug("found " + resourceLibraries.size() + " libraries for the resource");
			HashSet<Long> resourceLocations = getLocationsForEContentRecord(econtentId);
			//logger.debug("found " + resourceLocations.size() + " locations for the resource");
			//Setup title browse
			addRecordIdToBrowse("title", resourceLibraries, resourceLocations, title, sortTitle, recordIdFull);
			
			//Setup author browse
			addRecordIdToBrowse("author", resourceLibraries, resourceLocations, author, author, recordIdFull);
			
			//Setup subject browse
			for (String curSubject: subjects){
				addRecordIdToBrowse("subject", resourceLibraries, resourceLocations, curSubject, curSubject, recordIdFull);
			}
			
			results.incAdded();
			return true;
		} catch (SQLException e) {
			results.addNote("Error processing eContentRecord " + e.toString());
			results.incErrors();
			logger.error("Error processing eContentRecord ", e);
			return false;
		}finally{
			if (results.getEContentRecordsProcessed() % 100 == 0){
				results.saveResults();
			}
		}
	}

	
	private synchronized void addRecordIdToBrowse(String browseType, HashSet<Long> resourceLibraries, HashSet<Long> resourceLocations, String browseValue, String sortValue, String recordIdFull) throws SQLException {
		if (browseValue == null){
			return;
		}
		browseValue = browseValue.replaceAll("[.,:\\\\/]$", ""); //Remove trailing punctuation
		browseValue = browseValue.trim();
		if (browseValue.length() == 0){
			return;
		}
		
		//Do additional processing of sort value - lower case it and remove any punctuation 
		sortValue = sortValue.toLowerCase();
		sortValue = sortValue.replaceAll("\\W", " "); //get rid of non alpha numeric characters
		sortValue = sortValue.replaceAll("\\s{2,}", " "); //get rid of duplicate spaces 
		sortValue = sortValue.trim();
		
		//If we've trimmed everything, use the original with punctuation
		if (sortValue.length() == 0){
			sortValue = sortValue.toLowerCase().trim();
		}
		
		//Check to see if the value is already in the table
		PreparedStatement insertValueStatement;
		PreparedStatement getExistingBrowseValueStatement;
		PreparedStatement getExistingBrowseScopeValueStatement;
		PreparedStatement insertBrowseScopeValueStatement;
		PreparedStatement updateBrowseScopeValueStatement;
		HashMap<String, Long> existingBrowseValues;
		if (browseType.equals("title")){
			insertValueStatement = insertTitleBrowseValue;
			getExistingBrowseValueStatement = getExistingTitleBrowseValue;
			getExistingBrowseScopeValueStatement = getExistingTitleBrowseScopeValue;
			insertBrowseScopeValueStatement = insertTitleBrowseScopeValue;
			updateBrowseScopeValueStatement = updateTitleBrowseScopeValue;
			existingBrowseValues = existingBrowseValuesTitle;
		}else if (browseType.equals("author")){
			insertValueStatement = insertAuthorBrowseValue;
			getExistingBrowseValueStatement = getExistingAuthorBrowseValue;
			getExistingBrowseScopeValueStatement = getExistingAuthorBrowseScopeValue;
			insertBrowseScopeValueStatement = insertAuthorBrowseScopeValue;
			updateBrowseScopeValueStatement = updateAuthorBrowseScopeValue;
			existingBrowseValues = existingBrowseValuesAuthor;
		}else if (browseType.equals("subject")){
			insertValueStatement = insertSubjectBrowseValue;
			getExistingBrowseValueStatement = getExistingSubjectBrowseValue;
			getExistingBrowseScopeValueStatement = getExistingSubjectBrowseScopeValue;
			insertBrowseScopeValueStatement = insertSubjectBrowseScopeValue;
			updateBrowseScopeValueStatement = updateSubjectBrowseScopeValue;
			existingBrowseValues = existingBrowseValuesSubject;
		}else{
			insertValueStatement = insertCallNumberBrowseValue;
			getExistingBrowseValueStatement = getExistingCallNumberBrowseValue;
			getExistingBrowseScopeValueStatement = getExistingCallNumberBrowseScopeValue;
			insertBrowseScopeValueStatement = insertCallNumberBrowseScopeValue;
			updateBrowseScopeValueStatement = updateCallNumberBrowseScopeValue;
			existingBrowseValues = existingBrowseValuesCallNumber;
		}
		Long browseValueId = insertBrowseValue(browseType, browseValue, sortValue, existingBrowseValues, insertValueStatement,getExistingBrowseValueStatement);
		if (browseValueId == null){
			return;
		}
		
		for (Long curLibrary: resourceLibraries){
			insertBrowseScoping(browseType, browseValue, curLibrary == -1 ? 0 : 1, curLibrary, recordIdFull, getExistingBrowseScopeValueStatement, insertBrowseScopeValueStatement, updateBrowseScopeValueStatement, browseValueId);
		}
		for (Long curLocation: resourceLocations){
			insertBrowseScoping(browseType, browseValue, 2, curLocation, recordIdFull, getExistingBrowseScopeValueStatement, insertBrowseScopeValueStatement, updateBrowseScopeValueStatement, browseValueId);
		}
	}

	private void insertBrowseScoping(String browseType, String browseValue, int scope, Long scopeValue, String recordIdFull, PreparedStatement getExistingBrowseScopeValueStatement,
			PreparedStatement insertBrowseScopeValueStatement, PreparedStatement updateBrowseScopeValueStatement, Long browseValueId) throws SQLException {
		//Add the scoping information to the table
		//Check to see if we already have an existing scope value
		try {
			insertBrowseScopeValueStatement.setLong(1, browseValueId);
			insertBrowseScopeValueStatement.setInt(2, scope);
			insertBrowseScopeValueStatement.setLong(3, scopeValue);
			insertBrowseScopeValueStatement.setString(4, recordIdFull);
			insertBrowseScopeValueStatement.executeUpdate();
		} catch (Exception e) {
			//We occassionally get errors if multiple locations use the same call numbers
			//ignore for now.
			logger.debug("Error adding " + browseType + " '" + browseValue + "' browse scoping " + e.toString());
		}
	}

	private Long insertBrowseValue(String browseType, String browseValue, String sortValue, HashMap<String, Long> existingValues, PreparedStatement insertValueStatement, PreparedStatement getExistingBrowseValueStatement) {
		try {
			browseValue = Util.trimTo(255, browseValue);
			String browseValueKey = browseValue.toLowerCase();
			if (existingValues.containsKey(browseValueKey)){
				return existingValues.get(browseValueKey);
			}
			getExistingBrowseValueStatement.setString(1, browseValue);
			ResultSet existingValueRS = getExistingBrowseValueStatement.executeQuery();
			if (existingValueRS.next()){
				Long existingValue = existingValueRS.getLong("id");
				existingValueRS.close();
				return existingValue;
			}else{
				existingValueRS.close();
				//Add the value to the table
				insertValueStatement.setString(1, browseValue);
				insertValueStatement.setString(2, Util.trimTo(255, sortValue));
				insertValueStatement.executeUpdate();
				ResultSet browseValueIdRS = insertValueStatement.getGeneratedKeys();
				if (browseValueIdRS.next()){
					Long browseValueId = browseValueIdRS.getLong(1);
					//MySQL is case insensitive when it comes to unique values so we need to make sure that our 
					//exisiting values are all case insensitve. 
					existingValues.put(browseValueKey, browseValueId);
					browseValueIdRS.close();
					return browseValueId;
				}else{
					results.addNote("Could not add browse value to table");
					results.incErrors();
					return null;
				}
			}
		} catch (SQLException e) {
			//This is probably because the hashset is giving a false positive on uniqueness.  (Seems to happen with UTF-8 characters)
			//Get the existing value straight from the db
			try {
				getExistingBrowseValueStatement.setString(1, browseValue);
				ResultSet existingValue = getExistingBrowseValueStatement.executeQuery();
				if (existingValue.next()){
					return existingValue.getLong("id");
				}
			} catch (SQLException e1) {
				results.addNote("Could get existing browse value '" + browseValue + "' in table " + browseType + ": " + e1.toString());
				results.incErrors();
				return null;
			}
			results.addNote("Could not add browse value '" + browseValue + "' to table " + browseType + ": " + e.toString());
			results.incErrors();
			return null;
		}
	}

	private HashSet<Long> getLibrariesForPrintRecord(Set<LocalCallNumber> callNumbers) throws SQLException {
		HashSet<Long> librariesForResource = new HashSet<Long>();
		//Use the call numbers to generate the available locations
		//Print titles are always available in the global scope (-1)
		librariesForResource.add(-1L);
		for (LocalCallNumber callNumber : callNumbers){
			librariesForResource.add(callNumber.getLibraryId());
		}
		return librariesForResource;
	}
	
	private HashSet<Long> getLibrariesEContentRecord(Long econtentId) throws SQLException {
		HashSet<Long> librariesForResource = new HashSet<Long>();
		//Get a list of libraries from the econtent database
		getLibraryIdsForEContent.setLong(1, econtentId);
		ResultSet libraryIdsForEContentRs = getLibraryIdsForEContent.executeQuery();
		while (libraryIdsForEContentRs.next()){
			librariesForResource.add(libraryIdsForEContentRs.getLong("libraryId"));
		}
		return librariesForResource;
	}
	
	private HashSet<Long> getLocationsForPrintRecord(Set<LocalCallNumber> callNumbers) throws SQLException {
		HashSet<Long> locationsForResource = new HashSet<Long>();
		//Use the call numbers to generate the available locations
		//Print titles are always available in the global scope (-1)
		locationsForResource.add(-1L);
		for (LocalCallNumber callNumber : callNumbers){
			locationsForResource.add(callNumber.getLocationId());
		}
		return locationsForResource;
	}
	
	private HashSet<Long> getLocationsForEContentRecord(Long econtentId) throws SQLException {
		HashSet<Long> locationsForResource = new HashSet<Long>();
		//Get a list of libraries from the econtent database
		getLibraryIdsForEContent.setLong(1, econtentId);
		ResultSet libraryIdsForEContentRs = getLibraryIdsForEContent.executeQuery();
		while (libraryIdsForEContentRs.next()){
			//TODO: Add all locations within the library to the list
			//locationsForResource.add(libraryIdsForEContentRs.getLong("libraryId"));
		}
		return locationsForResource;
	}

	@Override
	public void finish() {
		//TODO:  Update the order for the tabls
		/*logger.info("Building Alphabetic Browse tables");
		results.addNote("Building Alphabetic Browse tables");
		results.saveResults();
		try {
			//Run queries to create alphabetic browse tables from resources table
			try {
				//Get all resources
				saveBrowseValues("Title", titleBrowseInfo, "title_browse");
				
				saveBrowseValues("Author", authorBrowseInfo, "author_browse");
				
				saveBrowseValues("Subject", subjectBrowseInfo, "subject_browse");
				
				saveBrowseValues("Call Number", callNumberBrowseInfo, "callnumber_browse");
			} catch (SQLException e) {
				logger.error("Error creating browse tables", e);
				results.addNote("Error creating browse tables " + e.toString());
			}
			
		} catch (Error e) {
			System.out.println("Error updating Alphabetic Browse");
			e.printStackTrace();
			logger.error("Error updating Alphabetic Browse", e);
			results.addNote("Error updating Alphabetic Browse " + e.toString());
			results.saveResults();
		}*/
	}

	private void clearBrowseIndex(String tableName) throws SQLException{
		logger.info("Truncating " + tableName);
		results.addNote("Truncating " + tableName);
		results.saveResults();
		PreparedStatement truncateTable = vufindConn.prepareStatement("TRUNCATE " + tableName);
		truncateTable.executeUpdate();
		PreparedStatement truncateScopingTable = vufindConn.prepareStatement("TRUNCATE " + tableName + "_scoped_results");
		truncateScopingTable.executeUpdate();
	}

	@Override
	public ProcessorResults getResults() {
		return results;
	}

}
