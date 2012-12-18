package org.vufind;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
//import java.util.HashMap;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Map;
import java.util.Set;

//import org.apache.commons.collections.map.LRUMap;
import org.apache.commons.collections.map.LRUMap;
import org.apache.log4j.Logger;
import org.ini4j.Ini;

public class AlphaBrowseProcessor implements IMarcRecordProcessor, IEContentProcessor, IRecordProcessor {
	private Logger logger;
	private Connection vufindConn;
	private ProcessorResults results;
	
	private HashMap<Long, String> librarySubdomains;
	
	private PreparedStatement	getExistingBrowseRecordsStmt;
	private HashMap<String, PreparedStatement>	clearAuthorBrowseRecordInfoStmts;
	private HashMap<String, PreparedStatement>	clearCallNumberBrowseRecordInfoStmts;
	private HashMap<String, PreparedStatement>	clearSubjectBrowseRecordInfoStmts;
	private HashMap<String, PreparedStatement>	clearTitleBrowseRecordInfoStmts;
	
	private PreparedStatement	getExistingTitleBrowseValue;
	private PreparedStatement	getExistingAuthorBrowseValue;
	private PreparedStatement	getExistingSubjectBrowseValue;
	private PreparedStatement	getExistingCallNumberBrowseValue;
	private PreparedStatement	insertTitleBrowseValue;
	private PreparedStatement	insertAuthorBrowseValue;
	private PreparedStatement	insertSubjectBrowseValue;
	private PreparedStatement	insertCallNumberBrowseValue;
	
	/*private PreparedStatement optimizeTitleStmt;
	private PreparedStatement optimizeAuthorStmt;
	private PreparedStatement optimizeSubjectStmt;
	private PreparedStatement optimizeCallNumberStmt;*/
	
	private PreparedStatement	getLibraryIdsForEContent;
	private HashMap<String, PreparedStatement> insertTitleBrowseScopeValueStmts;
	private HashMap<String, PreparedStatement>	insertAuthorBrowseScopeValueStmts;
	private HashMap<String, PreparedStatement>	insertSubjectBrowseScopeValueStmts;
	private HashMap<String, PreparedStatement>	insertCallNumberBrowseScopeValueStmts;
	
	//Information about how to process call numbers for local browse
	private String itemTag;
	private String callNumberSubfield;
	private String locationSubfield;
	
	@SuppressWarnings("unchecked")
	private Map<String, Long> existingBrowseValuesTitle = new LRUMap(10000); //new HashMap<String, Long>();
	@SuppressWarnings("unchecked")
	private Map<String, Long> existingBrowseValuesAuthor = new LRUMap(10000); //new HashMap<String, Long>();
	@SuppressWarnings("unchecked")
	private Map<String, Long> existingBrowseValuesSubject = new LRUMap(20000); //new HashMap<String, Long>();
	@SuppressWarnings("unchecked")
	private Map<String, Long> existingBrowseValuesCallNumber = new LRUMap(20000); //new HashMap<String, Long>(); 
	
	private boolean clearAlphaBrowseAtStartOfIndex = false;
	private boolean updateAlphaBrowseForUnchangedRecords = false;
	
	private HashSet<String> existingBrowseRecords = new HashSet<String>();

	public boolean init(Ini configIni, String serverName, long reindexLogId, Connection vufindConn, Connection econtentConn, Logger logger) {
		this.logger = logger;
		this.vufindConn = vufindConn;
		results = new ProcessorResults("Alpha Browse Table Update", reindexLogId, vufindConn, logger);
		results.saveResults();
		
		//Load field information for local call numbers
		itemTag = configIni.get("Reindex", "itemTag");
		callNumberSubfield = configIni.get("Reindex", "callNumberSubfield");
		locationSubfield = configIni.get("Reindex", "locationSubfield");
		
		String clearAlphaBrowseAtStartOfIndexStr = configIni.get("Reindex", "clearAlphaBrowseAtStartOfIndex");
		if (clearAlphaBrowseAtStartOfIndexStr != null){
			clearAlphaBrowseAtStartOfIndex = Boolean.parseBoolean(clearAlphaBrowseAtStartOfIndexStr);
		}
		results.addNote("clearAlphaBrowseAtStartOfIndex = " + clearAlphaBrowseAtStartOfIndex);
		String updateAlphaBrowseForUnchangedRecordsStr = configIni.get("Reindex", "updateAlphaBrowseForUnchangedRecords");
		if (updateAlphaBrowseForUnchangedRecordsStr != null){
			updateAlphaBrowseForUnchangedRecords = Boolean.parseBoolean(updateAlphaBrowseForUnchangedRecordsStr);
		}
		results.addNote("updateAlphaBrowseForUnchangedRecords = " + updateAlphaBrowseForUnchangedRecords);
		
		try {
			//Setup prepared statements for later usage.  
			getLibraryIdsForEContent = econtentConn.prepareStatement("SELECT distinct libraryId from econtent_item where recordId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			
			librarySubdomains = new HashMap<Long, String>();
			PreparedStatement loadLibraryInfo = vufindConn.prepareStatement("SELECT libraryId, subdomain FROM library");
			ResultSet libraryInfoRS = loadLibraryInfo.executeQuery();
			while (libraryInfoRS.next()){
				librarySubdomains.put(libraryInfoRS.getLong("libraryId"), libraryInfoRS.getString("subdomain"));
			}
			//logger.debug("found " + librarySubdomains.size() + "library subdomains");
			
			getExistingBrowseRecordsStmt = vufindConn.prepareStatement("SELECT distinct record from title_browse_scoped_results_global", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			
			clearAuthorBrowseRecordInfoStmts = new HashMap<String, PreparedStatement>();
			clearCallNumberBrowseRecordInfoStmts = new HashMap<String, PreparedStatement>();
			clearSubjectBrowseRecordInfoStmts = new HashMap<String, PreparedStatement>();
			clearTitleBrowseRecordInfoStmts = new HashMap<String, PreparedStatement>();
			
			/*optimizeTitleStmt = vufindConn.prepareStatement("OPTIMIZE TABLE title_browse");
			optimizeAuthorStmt = vufindConn.prepareStatement("OPTIMIZE TABLE author_browse");
			optimizeSubjectStmt = vufindConn.prepareStatement("OPTIMIZE TABLE subject_browse");
			optimizeCallNumberStmt = vufindConn.prepareStatement("OPTIMIZE TABLE callnumber_browse");*/
			
			clearAuthorBrowseRecordInfoStmts.put("global", vufindConn.prepareStatement("DELETE FROM author_browse_scoped_results_global where record = ?"));
			clearCallNumberBrowseRecordInfoStmts.put("global", vufindConn.prepareStatement("DELETE FROM callnumber_browse_scoped_results_global where record = ?"));
			clearSubjectBrowseRecordInfoStmts.put("global", vufindConn.prepareStatement("DELETE FROM subject_browse_scoped_results_global where record = ?"));
			clearTitleBrowseRecordInfoStmts.put("global", vufindConn.prepareStatement("DELETE FROM title_browse_scoped_results_global where record = ?"));
			for (String subdomain : librarySubdomains.values()){
				clearAuthorBrowseRecordInfoStmts.put(subdomain, vufindConn.prepareStatement("DELETE FROM author_browse_scoped_results_library_" + subdomain + " where record = ?"));
				clearCallNumberBrowseRecordInfoStmts.put(subdomain, vufindConn.prepareStatement("DELETE FROM callnumber_browse_scoped_results_library_" + subdomain + " where record = ?"));
				clearSubjectBrowseRecordInfoStmts.put(subdomain, vufindConn.prepareStatement("DELETE FROM subject_browse_scoped_results_library_" + subdomain + " where record = ?"));
				clearTitleBrowseRecordInfoStmts.put(subdomain, vufindConn.prepareStatement("DELETE FROM title_browse_scoped_results_library_" + subdomain + " where record = ?"));
			}
			
			getExistingTitleBrowseValue = vufindConn.prepareStatement("SELECT id from title_browse WHERE firstChar = ? and secondChar = ? and value = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getExistingAuthorBrowseValue = vufindConn.prepareStatement("SELECT id from author_browse WHERE firstChar = ? and secondChar = ? and value = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getExistingSubjectBrowseValue = vufindConn.prepareStatement("SELECT id from subject_browse WHERE firstChar = ? and secondChar = ? and value = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getExistingCallNumberBrowseValue = vufindConn.prepareStatement("SELECT id from callnumber_browse WHERE firstChar = ? and secondChar = ? and value = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			
			insertTitleBrowseValue = vufindConn.prepareStatement("INSERT INTO title_browse (value, sortValue, alphaRank, firstChar, secondChar) VALUES (?, ?, 0, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			insertAuthorBrowseValue = vufindConn.prepareStatement("INSERT INTO author_browse (value, sortValue, alphaRank, firstChar, secondChar) VALUES (?, ?, 0, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			insertSubjectBrowseValue = vufindConn.prepareStatement("INSERT INTO subject_browse (value, sortValue, alphaRank, firstChar, secondChar) VALUES (?, ?, 0, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			insertCallNumberBrowseValue = vufindConn.prepareStatement("INSERT INTO callnumber_browse (value, sortValue, alphaRank, firstChar, secondChar) VALUES (?, ?, 0, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			
			
			insertTitleBrowseScopeValueStmts = new HashMap<String, PreparedStatement>();
			insertAuthorBrowseScopeValueStmts = new HashMap<String, PreparedStatement>();
			insertSubjectBrowseScopeValueStmts = new HashMap<String, PreparedStatement>();
			insertCallNumberBrowseScopeValueStmts = new HashMap<String, PreparedStatement>();
			
			insertTitleBrowseScopeValueStmts.put("global", vufindConn.prepareStatement("INSERT INTO title_browse_scoped_results_global (browseValueId, record) VALUES (?, ?)"));
			insertAuthorBrowseScopeValueStmts.put("global", vufindConn.prepareStatement("INSERT INTO author_browse_scoped_results_global (browseValueId, record) VALUES (?, ?)"));
			insertSubjectBrowseScopeValueStmts.put("global", vufindConn.prepareStatement("INSERT INTO subject_browse_scoped_results_global (browseValueId, record) VALUES (?, ?)"));
			insertCallNumberBrowseScopeValueStmts.put("global", vufindConn.prepareStatement("INSERT INTO callnumber_browse_scoped_results_global (browseValueId, record) VALUES (?, ?)"));
			
			for (String subdomain : librarySubdomains.values()){
				insertTitleBrowseScopeValueStmts.put(subdomain, vufindConn.prepareStatement("INSERT INTO title_browse_scoped_results_library_" + subdomain + " (browseValueId, record) VALUES (?, ?)"));
				insertAuthorBrowseScopeValueStmts.put(subdomain, vufindConn.prepareStatement("INSERT INTO author_browse_scoped_results_library_" + subdomain + " (browseValueId, record) VALUES (?, ?)"));
				insertSubjectBrowseScopeValueStmts.put(subdomain, vufindConn.prepareStatement("INSERT INTO subject_browse_scoped_results_library_" + subdomain + " (browseValueId, record) VALUES (?, ?)"));
				insertCallNumberBrowseScopeValueStmts.put(subdomain, vufindConn.prepareStatement("INSERT INTO callnumber_browse_scoped_results_library_" + subdomain + " (browseValueId, record) VALUES (?, ?)"));
			}
			
			if (clearAlphaBrowseAtStartOfIndex){
				clearBrowseIndex("title_browse");
				clearBrowseIndex("author_browse");
				clearBrowseIndex("subject_browse");
				clearBrowseIndex("callnumber_browse");
			}else{
				//Load the existing browse values 
				results.addNote("Loading existing browse records");
				results.saveResults();
				ResultSet getExistingBrowseRecordsRS = getExistingBrowseRecordsStmt.executeQuery();
				while(getExistingBrowseRecordsRS.next()){
					existingBrowseRecords.add(getExistingBrowseRecordsRS.getString(1));
				}
				getExistingBrowseRecordsRS.close();
				results.addNote("Finished loading existing browse records");
				results.saveResults();
			}
		} catch (SQLException e) {
			results.addNote("Error setting up prepared statements for Alpha Browse Processor");
			results.incErrors();
			logger.error("Error setting up prepared statements for Alpha Browse Processor", e);
			return false;
		}finally{
			results.saveResults();
		}
		return true;
	}
	
	@Override
	public boolean processMarcRecord(MarcProcessor processor, MarcRecordDetails recordInfo, int recordStatus, Logger logger) {
		try {
			//For alpha browse processing, everything is handled in the finish method
			results.incRecordsProcessed();
			if (!updateAlphaBrowseForUnchangedRecords && (recordStatus == MarcProcessor.RECORD_UNCHANGED)){
				//Check to see if the record has been added to alpha browse and force it to be indexed even if it hasn't changed
				if (isRecordInBrowse(recordInfo.getId())){
					results.incSkipped();
					return true;
				}
			}
			if (!recordInfo.isEContent()){
				if (!clearAlphaBrowseAtStartOfIndex){
					//logger.debug("Clearing browse info for " + recordInfo.getId());
					clearBrowseInfoForRecord(recordInfo.getId());
				}
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
				
				if (recordStatus == MarcProcessor.RECORD_NEW){
					results.incAdded();
				}else{
					results.incUpdated();
				}
			}else{
				results.incSkipped();
			}
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
			/*if (results.getRecordsProcessed() % 10000 == 0){
				optimizeTables();
			}*/
		}
		
	}
	
	/*private void optimizeTables(){
		try {
			optimizeTitleStmt.execute();
			optimizeAuthorStmt.execute();
			optimizeSubjectStmt.execute();
			optimizeCallNumberStmt.execute();
		} catch (SQLException e) {
			results.addNote("Error processing optimizing tables " + e.toString());
			results.incErrors();
			logger.error("Error processing optimizing tables ", e);
		}
	}*/

	private void clearBrowseInfoForRecord(String id) {
		try {
			for (PreparedStatement curStatement: clearAuthorBrowseRecordInfoStmts.values()){
				curStatement.setString(1, id);
				curStatement.executeUpdate();
			}
			
			for (PreparedStatement curStatement: clearCallNumberBrowseRecordInfoStmts.values()){
				curStatement.setString(1, id);
				curStatement.executeUpdate();
			}
			
			for (PreparedStatement curStatement: clearSubjectBrowseRecordInfoStmts.values()){
				curStatement.setString(1, id);
				curStatement.executeUpdate();
			}
			
			for (PreparedStatement curStatement: clearTitleBrowseRecordInfoStmts.values()){
				curStatement.setString(1, id);
				curStatement.executeUpdate();
			}
		} catch (SQLException e) {
			results.incErrors();
			results.addNote("Error clearing browse info for record " + id + " " + e.toString());
			logger.error("Error clearing browse info for record " + id, e);
		}
		
	}

	private boolean isRecordInBrowse(String recordId) {
		if (existingBrowseRecords.contains(recordId)){
			existingBrowseRecords.remove(recordId);
			//logger.debug("record " + recordId + " does exist in browse index");
			return true;
		}else{
			//logger.debug("record " + recordId + " does not exist in browse index");
			return false;
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
			//logger.debug("  '" + callNumberSort + "' - '" + callNumber + "'");
			if (callNumberSort.length() > 0){
				addRecordIdToBrowse("callnumber", resourceLibraries, resourceLocations, callNumber, callNumberSort, recordIdFull);
			}
		}
	}

	@Override
	public boolean processEContentRecord(ResultSet resource, long recordStatus) {
		try {
			Long econtentId = resource.getLong("id");
			String recordIdFull = "econtentRecord" + resource.getString("id");
			//For alpha browse processing, everything is handled in the finish method
			results.incEContentRecordsProcessed();
			if (!updateAlphaBrowseForUnchangedRecords && recordStatus == MarcProcessor.RECORD_UNCHANGED){
				//Check to see if the record has been added to alpha browse
				if (isRecordInBrowse(recordIdFull)){
					results.incSkipped();
					return true;
				}
			}
			//Clear the information for the record as long as we didn't clear it already. 
			if (!clearAlphaBrowseAtStartOfIndex){
				clearBrowseInfoForRecord(recordIdFull);
			}
			String title = resource.getString("title");
			String subTitle = resource.getString("subTitle");
			if (subTitle.length() > 0){
				title += ": " + subTitle;
			}
			String sortTitle = Util.makeValueSortable(title);
			HashMap <String, String> browseAuthors = new HashMap<String, String>();
			String author = resource.getString("author");
			browseAuthors.put(Util.makeValueSortable(author), author);
			String authorsRaw = resource.getString("author2");
			String[] authors = authorsRaw.split("\\r\\n|\\r|\\n");
			for (String curAuthor : authors){
				browseAuthors.put(Util.makeValueSortable(curAuthor), curAuthor);
			}
			
			String subjectsRaw = resource.getString("subject");
			String[] subjects = subjectsRaw.split("\\r\\n|\\r|\\n");
			HashMap <String, String> browseSubjects = new HashMap<String, String>();
			for (String subject : subjects){
				browseSubjects.put(Util.makeValueSortable(subject), subject);
			}
			String topicsRaw = resource.getString("topic");
			if (topicsRaw == null) topicsRaw = "";
			String[] topics = topicsRaw.split("\\r\\n|\\r|\\n");
			for (String topic : topics){
				browseSubjects.put(Util.makeValueSortable(topic), topic);
			}
			
			HashSet<Long> resourceLibraries = getLibrariesEContentRecord(econtentId);
			//logger.debug("found " + resourceLibraries.size() + " libraries for the resource");
			HashSet<Long> resourceLocations = getLocationsForEContentRecord(econtentId);
			//logger.debug("found " + resourceLocations.size() + " locations for the resource");
			//Setup title browse
			if (sortTitle.length() >= 1){
				addRecordIdToBrowse("title", resourceLibraries, resourceLocations, title, sortTitle, recordIdFull);
			}
			
			//Setup author browse
			for (String curAuthorSortable: browseAuthors.keySet()){
				if (curAuthorSortable.length() >= 1){
					addRecordIdToBrowse("author", resourceLibraries, resourceLocations, browseAuthors.get(curAuthorSortable), curAuthorSortable, recordIdFull);
				}
			}
			
			//Setup subject browse
			for (String curSubjectSortable: browseSubjects.keySet()){
				if (curSubjectSortable.length() >= 1){
					addRecordIdToBrowse("subject", resourceLibraries, resourceLocations, browseSubjects.get(curSubjectSortable), curSubjectSortable, recordIdFull);
				}
			}
			
			//No call numbers for digital content
			if (recordStatus == MarcProcessor.RECORD_NEW){
				results.incAdded();
			}else{
				results.incUpdated();
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
		//logger.debug("Adding record id to browse " + browseType + " browseValue");
		
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
		
		Map<String, Long> existingBrowseValues;
		if (browseType.equals("title")){
			insertValueStatement = insertTitleBrowseValue;
			getExistingBrowseValueStatement = getExistingTitleBrowseValue;
			existingBrowseValues = existingBrowseValuesTitle;
		}else if (browseType.equals("author")){
			insertValueStatement = insertAuthorBrowseValue;
			getExistingBrowseValueStatement = getExistingAuthorBrowseValue;
			existingBrowseValues = existingBrowseValuesAuthor;
		}else if (browseType.equals("subject")){
			insertValueStatement = insertSubjectBrowseValue;
			getExistingBrowseValueStatement = getExistingSubjectBrowseValue;
			existingBrowseValues = existingBrowseValuesSubject;
		}else{
			insertValueStatement = insertCallNumberBrowseValue;
			getExistingBrowseValueStatement = getExistingCallNumberBrowseValue;
			existingBrowseValues = existingBrowseValuesCallNumber;
		}
		Long browseValueId = insertBrowseValue(browseType, browseValue, sortValue, existingBrowseValues, insertValueStatement,getExistingBrowseValueStatement);
		if (browseValueId == null){
			return;
		}
		
		for (Long curLibrary: resourceLibraries){
			//logger.debug("  Adding browse value " + browseValueId + " to library " + curLibrary);
			if (curLibrary == -1){
				//Add to global scope
				PreparedStatement insertBrowseScopeValueStatement;
				if (browseType.equals("title")){
					insertBrowseScopeValueStatement = insertTitleBrowseScopeValueStmts.get("global");
				}else if (browseType.equals("author")){
					insertBrowseScopeValueStatement = insertAuthorBrowseScopeValueStmts.get("global");
				}else if (browseType.equals("subject")){
					insertBrowseScopeValueStatement = insertSubjectBrowseScopeValueStmts.get("global");
				}else{
					insertBrowseScopeValueStatement = insertCallNumberBrowseScopeValueStmts.get("global");
				}
				insertBrowseScoping(browseType, browseValue, recordIdFull, insertBrowseScopeValueStatement, browseValueId);
			}else{
				String librarySubdomain = librarySubdomains.get(curLibrary);
				//logger.debug("library subdomain for " + curLibrary + " is " + librarySubdomain);
				PreparedStatement insertBrowseLibraryScopeValueStatement;
				if (browseType.equals("title")){
					insertBrowseLibraryScopeValueStatement = insertTitleBrowseScopeValueStmts.get(librarySubdomain);
				}else if (browseType.equals("author")){
					insertBrowseLibraryScopeValueStatement = insertAuthorBrowseScopeValueStmts.get(librarySubdomain);
				}else if (browseType.equals("subject")){
					insertBrowseLibraryScopeValueStatement = insertSubjectBrowseScopeValueStmts.get(librarySubdomain);
				}else{
					insertBrowseLibraryScopeValueStatement = insertCallNumberBrowseScopeValueStmts.get(librarySubdomain);
				}
				insertBrowseScoping(browseType, browseValue, recordIdFull, insertBrowseLibraryScopeValueStatement, browseValueId);
			}
		}
	}

	private void insertBrowseScoping(String browseType, String browseValue, String recordIdFull,
			PreparedStatement insertBrowseScopeValueStatement, Long browseValueId) throws SQLException {
		//Add the scoping information to the table
		//Check to see if we already have an existing scope value
		try {
			insertBrowseScopeValueStatement.setLong(1, browseValueId);
			insertBrowseScopeValueStatement.setString(2, recordIdFull);
			insertBrowseScopeValueStatement.executeUpdate();
		} catch (Exception e) {
			//We occassionally get errors if multiple locations use the same call numbers
			//ignore for now.
			logger.error("Error adding " + browseType + " '" + browseValue + "' browse scoping " + e.toString(), e);
		}
	}

	private Long insertBrowseValue(String browseType, String browseValue, String sortValue, Map<String, Long> existingValues, PreparedStatement insertValueStatement, PreparedStatement getExistingBrowseValueStatement) {
		try {
			browseValue = Util.trimTo(255, browseValue);
			Long existingBrowseValueId = getExistingBrowseValueId(browseValue, sortValue, existingValues, getExistingBrowseValueStatement);
			if (existingBrowseValueId != null){
				return existingBrowseValueId;
			}else{
				//Add the value to the table
				insertValueStatement.setString(1, browseValue);
				insertValueStatement.setString(2, Util.trimTo(255, sortValue));
				insertValueStatement.setString(3, sortValue.substring(0, 1));
				insertValueStatement.setString(4, (sortValue.length() > 1) ? sortValue.substring(1, 2) : " ");
				insertValueStatement.executeUpdate();
				ResultSet browseValueIdRS = insertValueStatement.getGeneratedKeys();
				if (browseValueIdRS.next()){
					Long browseValueId = browseValueIdRS.getLong(1);
					//MySQL is case insensitive when it comes to unique values so we need to make sure that our 
					//exisiting values are all case insensitve. 
					existingValues.put(sortValue, browseValueId);
					browseValueIdRS.close();
					return browseValueId;
				}else{
					results.addNote("Could not add browse value to table");
					results.incErrors();
					return null;
				}
			}
		} catch (SQLException e) {
			logger.error("Error adding browse value" + e);
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

	private Long getExistingBrowseValueId(String browseValue, String sortValue, Map<String, Long> existingValues,
			PreparedStatement getExistingBrowseValueStatement) throws SQLException {
		Long existingBrowseValueId = existingValues.get(browseValue);
		if (existingBrowseValueId == null){
			getExistingBrowseValueStatement.setString(1, sortValue.substring(0, 1));
			getExistingBrowseValueStatement.setString(2, (sortValue.length() > 1) ? sortValue.substring(1, 2) : " ");
			getExistingBrowseValueStatement.setString(3, Util.trimTo(255, browseValue));
			ResultSet existingValueRS = getExistingBrowseValueStatement.executeQuery();
			if (existingValueRS.next()){
				existingBrowseValueId = existingValueRS.getLong("id");
				existingValueRS.close();
				existingValues.put(browseValue, existingBrowseValueId);
			}
		}else{
			//logger.debug("Found cached value");
		}
		return existingBrowseValueId;
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
		//Make sure we add the global scope
		librariesForResource.add(-1L);
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
		try {
			//Update rankings
			PreparedStatement initRanking =  vufindConn.prepareStatement("set @r=0;");
			initRanking.executeUpdate();
			
			results.addNote("Updating browse tables for authors");
			results.saveResults();
			PreparedStatement authorRankingUpdate = vufindConn.prepareStatement("UPDATE author_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;");
			authorRankingUpdate.executeUpdate();
			PreparedStatement authorMetaDataClear = vufindConn.prepareStatement("TRUNCATE author_browse_metadata");
			authorMetaDataClear.executeUpdate();
			PreparedStatement authorMetaDataUpdate = vufindConn.prepareStatement("INSERT INTO author_browse_metadata (SELECT 0, -1, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM author_browse inner join author_browse_scoped_results_global ON id = browseValueId where alphaRank > 0)");
			authorMetaDataUpdate.executeUpdate();
			
			results.addNote("Updating browse tables for call numbers");
			results.saveResults();
			initRanking.executeUpdate();
			PreparedStatement callnumberRankingUpdate = vufindConn.prepareStatement("UPDATE callnumber_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;");
			callnumberRankingUpdate.executeUpdate();
			PreparedStatement callnumberMetaDataClear = vufindConn.prepareStatement("TRUNCATE callnumber_browse_metadata");
			callnumberMetaDataClear.executeUpdate();
			PreparedStatement callnumberMetaDataUpdate = vufindConn.prepareStatement("INSERT INTO callnumber_browse_metadata (SELECT 0, -1, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM callnumber_browse inner join callnumber_browse_scoped_results_global ON id = browseValueId where alphaRank > 0)");
			callnumberMetaDataUpdate.executeUpdate();
			
			results.addNote("Updating browse tables for subjects");
			results.saveResults();
			initRanking.executeUpdate();
			PreparedStatement subjectRankingUpdate = vufindConn.prepareStatement("UPDATE subject_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;");
			subjectRankingUpdate.executeUpdate();
			PreparedStatement subjectMetaDataClear = vufindConn.prepareStatement("TRUNCATE subject_browse_metadata");
			subjectMetaDataClear.executeUpdate();
			PreparedStatement subjectMetaDataUpdate = vufindConn.prepareStatement("INSERT INTO subject_browse_metadata (SELECT 0, -1, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM subject_browse inner join subject_browse_scoped_results_global ON id = browseValueId where alphaRank > 0)");
			subjectMetaDataUpdate.executeUpdate();
			
			results.addNote("Updating browse tables for titles");
			results.saveResults();
			initRanking.executeUpdate();
			PreparedStatement titleRankingUpdate = vufindConn.prepareStatement("UPDATE title_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;");
			titleRankingUpdate.executeUpdate();
			PreparedStatement titleMetaDataClear = vufindConn.prepareStatement("TRUNCATE title_browse_metadata");
			titleMetaDataClear.executeUpdate();
			PreparedStatement titleMetaDataUpdate = vufindConn.prepareStatement("INSERT INTO title_browse_metadata (SELECT 0, -1, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM title_browse inner join title_browse_scoped_results_global ON id = browseValueId where alphaRank > 0)");
			titleMetaDataUpdate.executeUpdate();
			
			for (Long libraryId : librarySubdomains.keySet()){
				String subdomain = librarySubdomains.get(libraryId);
				results.addNote("Updating meta data for " + subdomain);
				results.saveResults();
				try{
					vufindConn.prepareStatement("INSERT INTO title_browse_metadata (SELECT 1, " + libraryId + ", MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM title_browse inner join title_browse_scoped_results_library_" + subdomain + " ON id = browseValueId where alphaRank > 0)").executeUpdate();
					vufindConn.prepareStatement("INSERT INTO author_browse_metadata (SELECT 1, " + libraryId + ", MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM author_browse inner join author_browse_scoped_results_library_" + subdomain + " ON id = browseValueId where alphaRank > 0)").executeUpdate();
					vufindConn.prepareStatement("INSERT INTO subject_browse_metadata (SELECT 1, " + libraryId + ", MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM subject_browse inner join subject_browse_scoped_results_library_" + subdomain + " ON id = browseValueId where alphaRank > 0)").executeUpdate();
					vufindConn.prepareStatement("INSERT INTO callnumber_browse_metadata (SELECT 1, " + libraryId + ", MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM callnumber_browse inner join callnumber_browse_scoped_results_library_" + subdomain + " ON id = browseValueId where alphaRank > 0)").executeUpdate();
				} catch (SQLException e) {
					logger.error("Error updating meta data for " + subdomain, e);
					results.incErrors();
					results.addNote("Error updating meta data for " + subdomain + " " + e.toString());
				}
			}
			
			results.addNote("Finished updating browse tables");
			results.saveResults();
			
		} catch (SQLException e) {
			logger.error("Error finishing Alpha Browse Processing", e);
			results.incErrors();
			results.addNote("Error finishing Alpha Browse Processing" + e.toString());
		}
		
	}

	private void clearBrowseIndex(String tableName) throws SQLException{
		logger.info("Truncating " + tableName);
		results.addNote("Truncating " + tableName);
		results.saveResults();
		PreparedStatement truncateTable = vufindConn.prepareStatement("TRUNCATE " + tableName);
		truncateTable.executeUpdate();
		//truncateTable.executeUpdate();
		PreparedStatement truncateScopingTable = vufindConn.prepareStatement("TRUNCATE " + tableName + "_scoped_results_global");
		truncateScopingTable.executeUpdate();
		
		for (String subdomain : librarySubdomains.values()){
			PreparedStatement truncateLibraryScopingTable = vufindConn.prepareStatement("TRUNCATE " + tableName + "_scoped_results_library_" + subdomain);
			truncateLibraryScopingTable.executeUpdate();
		}
	}

	@Override
	public ProcessorResults getResults() {
		return results;
	}

}
