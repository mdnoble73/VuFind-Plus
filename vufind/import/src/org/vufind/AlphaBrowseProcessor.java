package org.vufind;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
//import java.util.HashMap;
import java.util.HashSet;
import java.util.Set;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

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
	
	/*private HashMap<String, Long> existingBrowseValuesTitle = new HashMap<String, Long>();
	private HashMap<String, Long> existingBrowseValuesAuthor = new HashMap<String, Long>();
	private HashMap<String, Long> existingBrowseValuesSubject = new HashMap<String, Long>();
	private HashMap<String, Long> existingBrowseValuesCallNumber = new HashMap<String, Long>();*/

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
			
			getExistingTitleBrowseScopeValue = vufindConn.prepareStatement("SELECT id, numResults, relatedRecords from title_browse_scoped_results WHERE browseValueId = ? AND scope = ? AND scopeId = ?");
			getExistingAuthorBrowseScopeValue = vufindConn.prepareStatement("SELECT id, numResults, relatedRecords from author_browse_scoped_results WHERE browseValueId = ? AND scope = ? AND scopeId = ?");
			getExistingSubjectBrowseScopeValue = vufindConn.prepareStatement("SELECT id, numResults, relatedRecords from subject_browse_scoped_results WHERE browseValueId = ? AND scope = ? AND scopeId = ?");
			getExistingCallNumberBrowseScopeValue = vufindConn.prepareStatement("SELECT id, numResults, relatedRecords from callnumber_browse_scoped_results WHERE browseValueId = ? AND scope = ? AND scopeId = ?");
			
			insertTitleBrowseScopeValue = vufindConn.prepareStatement("INSERT INTO title_browse_scoped_results (browseValueId, scope, scopeId, numResults, relatedRecords) VALUES (?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			insertAuthorBrowseScopeValue = vufindConn.prepareStatement("INSERT INTO author_browse_scoped_results (browseValueId, scope, scopeId, numResults, relatedRecords) VALUES (?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			insertSubjectBrowseScopeValue = vufindConn.prepareStatement("INSERT INTO subject_browse_scoped_results (browseValueId, scope, scopeId, numResults, relatedRecords) VALUES (?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			insertCallNumberBrowseScopeValue = vufindConn.prepareStatement("INSERT INTO callnumber_browse_scoped_results (browseValueId, scope, scopeId, numResults, relatedRecords) VALUES (?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			
			updateTitleBrowseScopeValue = vufindConn.prepareStatement("UPDATE title_browse_scoped_results SET numResults = ?, relatedRecords = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			updateAuthorBrowseScopeValue = vufindConn.prepareStatement("UPDATE author_browse_scoped_results SET numResults = ?, relatedRecords = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			updateSubjectBrowseScopeValue = vufindConn.prepareStatement("UPDATE subject_browse_scoped_results SET numResults = ?, relatedRecords = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			updateCallNumberBrowseScopeValue = vufindConn.prepareStatement("UPDATE callnumber_browse_scoped_results SET numResults = ?, relatedRecords = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
			
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
	
	@SuppressWarnings("unchecked")
	@Override
	public boolean processMarcRecord(MarcProcessor processor, MarcRecordDetails recordInfo, int recordStatus, Logger logger) {
		try {
			//For alpha browse processing, everything is handled in the finish method
			if (recordInfo.isEContent()){
				results.incSkipped();
				return true;
			}
			results.incRecordsProcessed();
			Set<String> titles = recordInfo.getAllTitles();
			logger.debug("found " + titles.size() + " titles for the resource");
			Set<String> authors = recordInfo.getAuthors();
			logger.debug("found " + authors.size() + " authors for the resource");
			String recordIdFull = recordInfo.getId();
			Object subjectsRaw = recordInfo.getMappedField("topic");
			Set<String> subjects = new HashSet<String>();
			if (subjectsRaw != null){
				if (subjectsRaw instanceof String){
					subjects.add((String)subjectsRaw); 
				}else{
					subjects.addAll((Set<String>)subjectsRaw);
				}
			}
			logger.debug("found " + subjects.size() + " subjects for the resource");
			Set<LocalCallNumber> localCallNumbers = recordInfo.getLocalCallNumbers(itemTag, callNumberSubfield, locationSubfield);
			HashSet<Long> resourceLibraries = getLibrariesForPrintRecord(localCallNumbers);
			//logger.debug("found " + resourceLibraries.size() + " libraries for the resource");
			HashSet<Long> resourceLocations = getLocationsForPrintRecord(localCallNumbers);
			//logger.debug("found " + resourceLocations.size() + " locations for the resource");
			for (String curTitle: titles){
				logger.debug("  " + curTitle);
				addRecordIdToBrowse("title", resourceLibraries, resourceLocations, curTitle, this.getSortableTitle(curTitle), recordIdFull);
			}
			
			//Setup author browse
			for (String curAuthor: authors){
				logger.debug("  " + curAuthor);
				addRecordIdToBrowse("author", resourceLibraries, resourceLocations, curAuthor, curAuthor, recordIdFull);
			}
			
			//Setup subject browse
			for (String curSubject: subjects){
				logger.debug("  " + curSubject);
				addRecordIdToBrowse("subject", resourceLibraries, resourceLocations, curSubject, curSubject, recordIdFull);
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
	
	private Pattern sortTrimmingPattern = Pattern.compile("(?i)^(?:(?:a|an|the|el|la|\"|')\\s)(.*)$");
	private String getSortableTitle(String curTitle) {
		Matcher sortMatcher = sortTrimmingPattern.matcher(curTitle);
		if (sortMatcher.matches()) {
			return sortMatcher.group(1);
		}else{
			return curTitle;
		}
	}

	private void addCallNumbersToBrowse(Set<LocalCallNumber> localCallNumbers, String recordIdFull) throws SQLException {
		for (LocalCallNumber callNumber : localCallNumbers){
			//Get the libraries and locations for this call nuber
			HashSet<Long> resourceLibraries = new HashSet<Long>();
			resourceLibraries.add(-1L);
			resourceLibraries.add(callNumber.getLibraryId());
			HashSet<Long> resourceLocations = new HashSet<Long>();
			resourceLocations.add(callNumber.getLocationId());
			addRecordIdToBrowse("callnumber", resourceLibraries, resourceLocations, callNumber.getCallNumber(), callNumber.getCallNumber(), recordIdFull);
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
		//HashMap<String, Long> existingBrowseValues;
		if (browseType.equals("title")){
			insertValueStatement = insertTitleBrowseValue;
			getExistingBrowseValueStatement = getExistingTitleBrowseValue;
			getExistingBrowseScopeValueStatement = getExistingTitleBrowseScopeValue;
			insertBrowseScopeValueStatement = insertTitleBrowseScopeValue;
			updateBrowseScopeValueStatement = updateTitleBrowseScopeValue;
			//existingBrowseValues = existingBrowseValuesTitle;
		}else if (browseType.equals("author")){
			insertValueStatement = insertAuthorBrowseValue;
			getExistingBrowseValueStatement = getExistingAuthorBrowseValue;
			getExistingBrowseScopeValueStatement = getExistingAuthorBrowseScopeValue;
			insertBrowseScopeValueStatement = insertAuthorBrowseScopeValue;
			updateBrowseScopeValueStatement = updateAuthorBrowseScopeValue;
			//existingBrowseValues = existingBrowseValuesAuthor;
		}else if (browseType.equals("subject")){
			insertValueStatement = insertSubjectBrowseValue;
			getExistingBrowseValueStatement = getExistingSubjectBrowseValue;
			getExistingBrowseScopeValueStatement = getExistingSubjectBrowseScopeValue;
			insertBrowseScopeValueStatement = insertSubjectBrowseScopeValue;
			updateBrowseScopeValueStatement = updateSubjectBrowseScopeValue;
			//existingBrowseValues = existingBrowseValuesSubject;
		}else{
			insertValueStatement = insertCallNumberBrowseValue;
			getExistingBrowseValueStatement = getExistingCallNumberBrowseValue;
			getExistingBrowseScopeValueStatement = getExistingCallNumberBrowseScopeValue;
			insertBrowseScopeValueStatement = insertCallNumberBrowseScopeValue;
			updateBrowseScopeValueStatement = updateCallNumberBrowseScopeValue;
			//existingBrowseValues = existingBrowseValuesCallNumber;
		}
		Long browseValueId = insertBrowseValue(browseType, browseValue, sortValue/*, existingBrowseValues*/, insertValueStatement,getExistingBrowseValueStatement);
		if (browseValueId == null){
			return;
		}
		
		for (Long curLibrary: resourceLibraries){
			insertBrowseScoping(curLibrary == -1 ? 0 : 1, curLibrary, recordIdFull, getExistingBrowseScopeValueStatement, insertBrowseScopeValueStatement, updateBrowseScopeValueStatement, browseValueId);
		}
		for (Long curLocation: resourceLocations){
			insertBrowseScoping(2, curLocation, recordIdFull, getExistingBrowseScopeValueStatement, insertBrowseScopeValueStatement, updateBrowseScopeValueStatement, browseValueId);
		}
	}

	private void insertBrowseScoping(int scope, Long scopeValue, String recordIdFull, PreparedStatement getExistingBrowseScopeValueStatement,
			PreparedStatement insertBrowseScopeValueStatement, PreparedStatement updateBrowseScopeValueStatement, Long browseValueId) throws SQLException {
		//Add the scoping information to the table
		//Check to see if we already have an existing scope value
		getExistingBrowseScopeValueStatement.setLong(1, browseValueId);
		getExistingBrowseScopeValueStatement.setInt(2, scope);
		getExistingBrowseScopeValueStatement.setLong(3, scopeValue);
		ResultSet existingBrowseScopeValue = getExistingBrowseScopeValueStatement.executeQuery();
		if (existingBrowseScopeValue.next()){
			Long id = existingBrowseScopeValue.getLong("id");
			Long numResults = existingBrowseScopeValue.getLong("numResults");
			String curRelatedRecords = existingBrowseScopeValue.getString("relatedRecords");
			updateBrowseScopeValueStatement.setLong(1, numResults +1);
			if (numResults >= 20){
				updateBrowseScopeValueStatement.setString(2, "");
			}else{
				updateBrowseScopeValueStatement.setString(2, curRelatedRecords + "," + recordIdFull);
			}
			updateBrowseScopeValueStatement.setLong(3, id);
			updateBrowseScopeValueStatement.executeUpdate();
		}else{
			insertBrowseScopeValueStatement.setLong(1, browseValueId);
			insertBrowseScopeValueStatement.setInt(2, scope);
			insertBrowseScopeValueStatement.setLong(3, scopeValue);
			insertBrowseScopeValueStatement.setLong(4, 1L);
			insertBrowseScopeValueStatement.setString(5, recordIdFull);
			insertBrowseScopeValueStatement.executeUpdate();
		}
	}

	private Long insertBrowseValue(String browseType, String browseValue, String sortValue, /*HashMap<String, Long> existingValues, */PreparedStatement insertValueStatement, PreparedStatement getExistingBrowseValueStatement) {
		try {
			browseValue = Util.trimTo(255, browseValue);
			//String browseValueKey = browseValue.toLowerCase();
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
					/*existingValues.put(browseValueKey, browseValueId);*/
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
