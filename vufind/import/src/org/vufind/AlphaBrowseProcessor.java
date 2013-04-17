package org.vufind;

import java.io.File;
import java.io.FileWriter;
import java.io.IOException;
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
	//private HashMap<String, PreparedStatement>	clearCallNumberBrowseRecordInfoStmts;
	private HashMap<String, PreparedStatement>	clearSubjectBrowseRecordInfoStmts;
	private HashMap<String, PreparedStatement>	clearTitleBrowseRecordInfoStmts;
	
	private String[] browseTypes = new String[]{"title", "author", "subject"};
	private HashMap<String, File> browseScopingFiles = new HashMap<String, File>();
	private HashMap<String, FileWriter> browseScopingFileWriters = new HashMap<String, FileWriter>();
	
	private PreparedStatement	getExistingTitleBrowseValue;
	private PreparedStatement	getExistingAuthorBrowseValue;
	private PreparedStatement	getExistingSubjectBrowseValue;
	//private PreparedStatement	getExistingCallNumberBrowseValue;
	private PreparedStatement	insertTitleBrowseValue;
	private PreparedStatement	insertAuthorBrowseValue;
	private PreparedStatement	insertSubjectBrowseValue;
	//private PreparedStatement	insertCallNumberBrowseValue;
	
	private PreparedStatement	getLibraryIdsForEContent;
	//private HashMap<String, PreparedStatement> insertTitleBrowseScopeValueStmts;
	//private HashMap<String, PreparedStatement>	insertAuthorBrowseScopeValueStmts;
	//private HashMap<String, PreparedStatement>	insertSubjectBrowseScopeValueStmts;
	//private HashMap<String, PreparedStatement>	insertCallNumberBrowseScopeValueStmts;
	
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
	//@SuppressWarnings("unchecked")
	//private Map<String, Long> existingBrowseValuesCallNumber = new LRUMap(20000); //new HashMap<String, Long>(); 
	
	private boolean clearAlphaBrowseAtStartOfIndex = false;
	private boolean updateAlphaBrowseForUnchangedRecords = false;
	
	private HashMap<String, HashSet<String>> existingBrowseRecords = new HashMap<String, HashSet<String>>();
	
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
				String subdomain = libraryInfoRS.getString("subdomain");
				librarySubdomains.put(libraryInfoRS.getLong("libraryId"), subdomain);
				
				for (String browseType : browseTypes){
					File libraryScopingFile = File.createTempFile(browseType + "_browse_data_" + subdomain, ".csv");
					FileWriter writer = new FileWriter(libraryScopingFile);
					browseScopingFiles.put(browseType + "_" + subdomain, libraryScopingFile);
					browseScopingFileWriters.put(browseType + "_" + subdomain, writer);
				}
			}
			for (String browseType : browseTypes){
				File libraryScopingFile = File.createTempFile(browseType + "_browse_data_global", ".csv");
				FileWriter writer = new FileWriter(libraryScopingFile);
				browseScopingFiles.put(browseType + "_" + "global", libraryScopingFile);
				browseScopingFileWriters.put(browseType + "_" + "global", writer);
			}
			//logger.debug("found " + librarySubdomains.size() + "library subdomains");
			
			getExistingBrowseRecordsStmt = vufindConn.prepareStatement("SELECT distinct record from title_browse_scoped_results_global", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			
			clearAuthorBrowseRecordInfoStmts = new HashMap<String, PreparedStatement>();
			//clearCallNumberBrowseRecordInfoStmts = new HashMap<String, PreparedStatement>();
			clearSubjectBrowseRecordInfoStmts = new HashMap<String, PreparedStatement>();
			clearTitleBrowseRecordInfoStmts = new HashMap<String, PreparedStatement>();
			
			clearAuthorBrowseRecordInfoStmts.put("global", vufindConn.prepareStatement("DELETE FROM author_browse_scoped_results_global where record = ?"));
			//clearCallNumberBrowseRecordInfoStmts.put("global", vufindConn.prepareStatement("DELETE FROM callnumber_browse_scoped_results_global where record = ?"));
			clearSubjectBrowseRecordInfoStmts.put("global", vufindConn.prepareStatement("DELETE FROM subject_browse_scoped_results_global where record = ?"));
			clearTitleBrowseRecordInfoStmts.put("global", vufindConn.prepareStatement("DELETE FROM title_browse_scoped_results_global where record = ?"));
			for (String subdomain : librarySubdomains.values()){
				clearAuthorBrowseRecordInfoStmts.put(subdomain, vufindConn.prepareStatement("DELETE FROM author_browse_scoped_results_library_" + subdomain + " where record = ?"));
				//clearCallNumberBrowseRecordInfoStmts.put(subdomain, vufindConn.prepareStatement("DELETE FROM callnumber_browse_scoped_results_library_" + subdomain + " where record = ?"));
				clearSubjectBrowseRecordInfoStmts.put(subdomain, vufindConn.prepareStatement("DELETE FROM subject_browse_scoped_results_library_" + subdomain + " where record = ?"));
				clearTitleBrowseRecordInfoStmts.put(subdomain, vufindConn.prepareStatement("DELETE FROM title_browse_scoped_results_library_" + subdomain + " where record = ?"));
			}
			
			getExistingTitleBrowseValue = vufindConn.prepareStatement("SELECT id from title_browse WHERE firstChar = ? and secondChar = ? and value = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getExistingAuthorBrowseValue = vufindConn.prepareStatement("SELECT id from author_browse WHERE firstChar = ? and secondChar = ? and value = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			getExistingSubjectBrowseValue = vufindConn.prepareStatement("SELECT id from subject_browse WHERE firstChar = ? and secondChar = ? and value = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			//getExistingCallNumberBrowseValue = vufindConn.prepareStatement("SELECT id from callnumber_browse WHERE firstChar = ? and secondChar = ? and value = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			
			insertTitleBrowseValue = vufindConn.prepareStatement("INSERT INTO title_browse (value, sortValue, alphaRank, firstChar, secondChar) VALUES (?, ?, 0, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			insertAuthorBrowseValue = vufindConn.prepareStatement("INSERT INTO author_browse (value, sortValue, alphaRank, firstChar, secondChar) VALUES (?, ?, 0, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			insertSubjectBrowseValue = vufindConn.prepareStatement("INSERT INTO subject_browse (value, sortValue, alphaRank, firstChar, secondChar) VALUES (?, ?, 0, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			//insertCallNumberBrowseValue = vufindConn.prepareStatement("INSERT INTO callnumber_browse (value, sortValue, alphaRank, firstChar, secondChar) VALUES (?, ?, 0, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			
			//insertTitleBrowseScopeValueStmts = new HashMap<String, PreparedStatement>();
			//insertAuthorBrowseScopeValueStmts = new HashMap<String, PreparedStatement>();
			//insertSubjectBrowseScopeValueStmts = new HashMap<String, PreparedStatement>();
			//insertCallNumberBrowseScopeValueStmts = new HashMap<String, PreparedStatement>();
			
			//insertTitleBrowseScopeValueStmts.put("global", vufindConn.prepareStatement("INSERT INTO title_browse_scoped_results_global (browseValueId, record) VALUES (?, ?)"));
			//insertAuthorBrowseScopeValueStmts.put("global", vufindConn.prepareStatement("INSERT INTO author_browse_scoped_results_global (browseValueId, record) VALUES (?, ?)"));
			//insertSubjectBrowseScopeValueStmts.put("global", vufindConn.prepareStatement("INSERT INTO subject_browse_scoped_results_global (browseValueId, record) VALUES (?, ?)"));
			//insertCallNumberBrowseScopeValueStmts.put("global", vufindConn.prepareStatement("INSERT INTO callnumber_browse_scoped_results_global (browseValueId, record) VALUES (?, ?)"));
			
			/*for (String subdomain : librarySubdomains.values()){
				insertTitleBrowseScopeValueStmts.put(subdomain, vufindConn.prepareStatement("INSERT INTO title_browse_scoped_results_library_" + subdomain + " (browseValueId, record) VALUES (?, ?)"));
				insertAuthorBrowseScopeValueStmts.put(subdomain, vufindConn.prepareStatement("INSERT INTO author_browse_scoped_results_library_" + subdomain + " (browseValueId, record) VALUES (?, ?)"));
				insertSubjectBrowseScopeValueStmts.put(subdomain, vufindConn.prepareStatement("INSERT INTO subject_browse_scoped_results_library_" + subdomain + " (browseValueId, record) VALUES (?, ?)"));
				//insertCallNumberBrowseScopeValueStmts.put(subdomain, vufindConn.prepareStatement("INSERT INTO callnumber_browse_scoped_results_library_" + subdomain + " (browseValueId, record) VALUES (?, ?)"));
			}*/
			
			if (clearAlphaBrowseAtStartOfIndex){
				clearBrowseIndex("title_browse");
				clearBrowseIndex("author_browse");
				clearBrowseIndex("subject_browse");
				//clearBrowseIndex("callnumber_browse");
			}else{
				//Load the existing browse values 
				results.addNote("Loading existing browse records");
				results.saveResults();
				HashSet<String> existingRecordsGlobal = new HashSet<String>();
				ResultSet getExistingBrowseRecordsRS = getExistingBrowseRecordsStmt.executeQuery();
				while(getExistingBrowseRecordsRS.next()){
					existingRecordsGlobal.add(getExistingBrowseRecordsRS.getString(1));
				}
				existingBrowseRecords.put("global", existingRecordsGlobal);
				getExistingBrowseRecordsRS.close();
				
				//Load for each library
				for (String subdomain : librarySubdomains.values()){
					PreparedStatement getExistingBrowseRecordsForLibraryStmt = vufindConn.prepareStatement("SELECT distinct record from title_browse_scoped_results_library_" + subdomain);
					ResultSet getExistingBrowseRecordsForLibraryRS = getExistingBrowseRecordsForLibraryStmt.executeQuery();
					HashSet<String> existingRecordsForLibrary = new HashSet<String>();
					while(getExistingBrowseRecordsForLibraryRS.next()){
						existingRecordsForLibrary.add(getExistingBrowseRecordsForLibraryRS.getString(1));
					}
					existingBrowseRecords.put(subdomain, existingRecordsForLibrary);
					logger.debug("Found " + existingRecordsForLibrary.size() + " records for subdomain " + subdomain);
				}
				
				results.addNote("Finished loading existing browse records");
				results.saveResults();
			}
		} catch (SQLException e) {
			results.addNote("Error setting up prepared statements for Alpha Browse Processor");
			results.incErrors();
			logger.error("Error setting up prepared statements for Alpha Browse Processor", e);
			return false;
		} catch (IOException e) {
			results.addNote("Error setting up file writers for Alpha Browse Processor");
			results.incErrors();
			logger.error("Error setting up file writers for Alpha Browse Processor", e);
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
			if (recordInfo.isEContent()){
				results.incEContentRecordsProcessed();
			}else{
				results.incRecordsProcessed();
			}
			if (!updateAlphaBrowseForUnchangedRecords && (recordStatus == MarcProcessor.RECORD_UNCHANGED)){
				//Check to see if the record has been added to alpha browse and force it to be indexed even if it hasn't changed
				if (isRecordInBrowse(recordInfo.getFullId())){
					results.incSkipped();
					return true;
				}
			}
			//Process all marc records together
			/*if (!clearAlphaBrowseAtStartOfIndex){
				//logger.debug("Clearing browse info for " + recordInfo.getId());
				clearBrowseInfoForRecord(recordInfo.getFullId());
			}*/
			HashMap<String, String> titles = recordInfo.getBrowseTitles();
			HashMap<String, String> authors = recordInfo.getBrowseAuthors();
			String recordIdFull = recordInfo.getFullId();
			HashMap<String, String> subjects = recordInfo.getBrowseSubjects();
			Set<LocalCallNumber> localCallNumbers = recordInfo.getLocalCallNumbers(itemTag, callNumberSubfield, locationSubfield);
			HashSet<Long> resourceLibraries = getLibrariesForPrintRecord(localCallNumbers);
			//logger.debug("found " + resourceLibraries.size() + " libraries for the resource");
			//logger.debug("found " + titles.size() + " titles for the resource");
			HashSet<Long> titleBrowseValues = new HashSet<Long>();
			for (String sortTitle: titles.keySet()){
				//logger.debug("  " + curTitle);
				String curTitle = titles.get(sortTitle);
				Long browseValueId = insertBrowseValue("title", curTitle, sortTitle, existingBrowseValuesTitle, insertTitleBrowseValue, getExistingTitleBrowseValue);
				titleBrowseValues.add(browseValueId);
				//addRecordIdToBrowse("title", resourceLibraries, curTitle, sortTitle, recordIdFull);
			}
			addBrowseScoping("title", resourceLibraries, titleBrowseValues, recordIdFull);
			
			//Setup author browse
			//logger.debug("found " + authors.size() + " authors for the resource");
			HashSet<Long> authorBrowseValues = new HashSet<Long>();
			for (String sortAuthor: authors.keySet()){
				//logger.debug("  " + curAuthor);
				String curAuthor = authors.get(sortAuthor);
				Long browseValueId = insertBrowseValue("author", curAuthor, sortAuthor, existingBrowseValuesAuthor, insertAuthorBrowseValue, getExistingAuthorBrowseValue);
				authorBrowseValues.add(browseValueId);
				//addRecordIdToBrowse("author", resourceLibraries, curAuthor, sortAuthor, recordIdFull);
			}
			addBrowseScoping("author", resourceLibraries, authorBrowseValues, recordIdFull);
			
			//Setup subject browse
			//logger.debug("found " + subjects.size() + " subjects for the resource");
			HashSet<Long> subjectBrowseValues = new HashSet<Long>();
			for (String sortSubject: subjects.keySet()){
				//logger.debug("  " + curSubject);
				String curSubject = subjects.get(sortSubject);
				Long browseValueId = insertBrowseValue("subject", curSubject, sortSubject, existingBrowseValuesSubject, insertSubjectBrowseValue, getExistingSubjectBrowseValue);
				subjectBrowseValues.add(browseValueId);
				//addRecordIdToBrowse("subject", resourceLibraries, curSubject, sortSubject, recordIdFull);
			}
			addBrowseScoping("subject", resourceLibraries, subjectBrowseValues, recordIdFull);
			
			//Setup call number browse
			//addCallNumbersToBrowse(localCallNumbers, recordIdFull);
			
			if (recordStatus == MarcProcessor.RECORD_NEW){
				results.incAdded();
			}else{
				results.incUpdated();
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
		}
		
	}
	
	private void addBrowseScoping(String browseType, HashSet<Long> resourceLibraries, HashSet<Long> titleBrowseValues, String recordIdFull) {
		if (resourceLibraries.size() == 0 || titleBrowseValues.size() == 0){
			return;
		}
		for (Long curLibraryId : resourceLibraries){
			String librarySubdomain = librarySubdomains.get(curLibraryId);
			FileWriter writer;
			//StringBuffer sqlStatement;
			if (curLibraryId == -1){
				writer = browseScopingFileWriters.get(browseType + "_global");
				//sqlStatement = new StringBuffer("INSERT INTO " + browseType + "_browse_scoped_results_global (browseValueId, record) VALUES ");
			}else{
				writer = browseScopingFileWriters.get(browseType + "_" + librarySubdomain);
				//sqlStatement = new StringBuffer("INSERT INTO " + browseType + "_browse_scoped_results_library_" + librarySubdomain + " (browseValueId, record) VALUES ");
			}
			
			//int numBrowseValuesAdded = 0;
			for (Long curBrowseValueId : titleBrowseValues){
				try {
					writer.write("\"" + curBrowseValueId + "\",\"" + recordIdFull + "\"\n");
				} catch (IOException e) {
					logger.error("Error saving browse values to file", e);
				}
				/*if (numBrowseValuesAdded != 0){
					sqlStatement.append(", ");
				}
				sqlStatement.append("(" + curBrowseValueId + ", '" + recordIdFull + "')");
				numBrowseValuesAdded++;*/
			}
			//logger.debug(sqlStatement.toString());
			/*try {
				vufindConn.prepareStatement(sqlStatement.toString()).executeUpdate();
			} catch (SQLException e) {
				logger.error("Error inserting browse values " + sqlStatement.toString(), e);
			}*/
		}
	}

	/*private void clearBrowseInfoForRecord(String id) {
		try {
			if (existingBrowseRecords.get("global").contains(id)){
				//logger.debug("clearing browse info for record " + id + " global scope");
				clearAuthorBrowseRecordInfoStmts.get("global").setString(1, id);
				clearAuthorBrowseRecordInfoStmts.get("global").executeUpdate();
				
				clearSubjectBrowseRecordInfoStmts.get("global").setString(1, id);
				clearSubjectBrowseRecordInfoStmts.get("global").executeUpdate();
				
				clearTitleBrowseRecordInfoStmts.get("global").setString(1, id);
				clearTitleBrowseRecordInfoStmts.get("global").executeUpdate();
				
				for (String subdomain : librarySubdomains.values()){
					if (existingBrowseRecords.get(subdomain).contains(id)){
						//logger.debug("clearing browse info for record " + id + " subdomain " + subdomain);
						clearAuthorBrowseRecordInfoStmts.get(subdomain).setString(1, id);
						clearAuthorBrowseRecordInfoStmts.get(subdomain).executeUpdate();
						
						clearSubjectBrowseRecordInfoStmts.get(subdomain).setString(1, id);
						clearSubjectBrowseRecordInfoStmts.get(subdomain).executeUpdate();
						
						clearTitleBrowseRecordInfoStmts.get(subdomain).setString(1, id);
						clearTitleBrowseRecordInfoStmts.get(subdomain).executeUpdate();
					}
				}
			}
			
			for (PreparedStatement curStatement: clearAuthorBrowseRecordInfoStmts.values()){
				curStatement.setString(1, id);
				curStatement.executeUpdate();
			}
			
			//for (PreparedStatement curStatement: clearCallNumberBrowseRecordInfoStmts.values()){
			//	curStatement.setString(1, id);
			//	curStatement.executeUpdate();
			//}
			
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
		
	}*/

	private boolean isRecordInBrowse(String recordId) {
		if (existingBrowseRecords.get("global").contains(recordId)){
			existingBrowseRecords.remove(recordId);
			//logger.debug("record " + recordId + " does exist in browse index");
			return true;
		}else{
			//logger.debug("record " + recordId + " does not exist in browse index");
			return false;
		}
	}

	/*private void addCallNumbersToBrowse(Set<LocalCallNumber> localCallNumbers, String recordIdFull) throws SQLException {
		//logger.debug("found " + localCallNumbers.size() + " call numbers for the resource");
		HashMap<String, String> distinctCallNumbers = new HashMap<String, String>(); 
		for (LocalCallNumber callNumber : localCallNumbers){
			if (callNumber.getCallNumber().length() > 0){
				//logger.debug("  " + callNumber.getCallNumber() + " " + callNumber.getLibraryId() + " " + callNumber.getLocationId());
				distinctCallNumbers.put(Util.makeValueSortable(callNumber.getCallNumber()), callNumber.getCallNumber().trim());
			}
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
				addRecordIdToBrowse("callnumber", resourceLibraries, callNumber, callNumberSort, recordIdFull);
			}
		}
	}*/

	@Override
	public boolean processEContentRecord(ResultSet resource, long recordStatus) {
		try {
			Long econtentId = resource.getLong("id");
			String recordIdFull = "econtentRecord" + resource.getString("id");
			//For alpha browse processing, everything is handled in the finish method
			results.incOverDriveNonMarcRecordsProcessed();
			if (!updateAlphaBrowseForUnchangedRecords && recordStatus == MarcProcessor.RECORD_UNCHANGED){
				logger.debug("Record has not changed since last index.");
				//Check to see if the record has been added to alpha browse
				if (isRecordInBrowse(recordIdFull)){
					logger.debug("  The record is already in the browse index, skipping.");
					results.incSkipped();
					return true;
				}else{
					logger.debug("  The record is not in the browse index, can't skip.");
				}
			}
			logger.debug("Updating alpha browse for " + recordIdFull);
			//Clear the information for the record as long as we didn't clear it already. 
			/*if (!clearAlphaBrowseAtStartOfIndex){
				clearBrowseInfoForRecord(recordIdFull);
			}*/
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
			logger.debug("found " + resourceLibraries.size() + " libraries for the resource");
			//Setup title browse
			HashSet<Long> titleBrowseValues = new HashSet<Long>();
			if (sortTitle.length() >= 1){
				//addRecordIdToBrowse("title", resourceLibraries, title, sortTitle, recordIdFull);
				Long browseValueId = insertBrowseValue("title", title, sortTitle, existingBrowseValuesTitle, insertTitleBrowseValue, getExistingTitleBrowseValue);
				if (browseValueId != null){
					titleBrowseValues.add(browseValueId);
				}
			}
			addBrowseScoping("title", resourceLibraries, titleBrowseValues, recordIdFull);
			
			//Setup author browse
			HashSet<Long> authorBrowseValues = new HashSet<Long>();
			for (String curAuthorSortable: browseAuthors.keySet()){
				String curAuthor = browseAuthors.get(curAuthorSortable);
				Long browseValueId = insertBrowseValue("author", curAuthor, curAuthorSortable, existingBrowseValuesAuthor, insertAuthorBrowseValue, getExistingAuthorBrowseValue);
				if (browseValueId != null){
					authorBrowseValues.add(browseValueId);
				}
				//if (curAuthorSortable.length() >= 1){
				//	addRecordIdToBrowse("author", resourceLibraries, browseAuthors.get(curAuthorSortable), curAuthorSortable, recordIdFull);
				//}
			}
			addBrowseScoping("author", resourceLibraries, authorBrowseValues, recordIdFull);
			
			//Setup subject browse
			HashSet<Long> subjectBrowseValues = new HashSet<Long>();
			for (String curSubjectSortable: browseSubjects.keySet()){
				//if (curSubjectSortable.length() >= 1){
				//	addRecordIdToBrowse("subject", resourceLibraries, browseSubjects.get(curSubjectSortable), curSubjectSortable, recordIdFull);
				//}
				String curSubject = browseSubjects.get(curSubjectSortable);
				Long browseValueId = insertBrowseValue("subject", curSubject, curSubjectSortable, existingBrowseValuesSubject, insertSubjectBrowseValue, getExistingSubjectBrowseValue);
				if (browseValueId != null){
					subjectBrowseValues.add(browseValueId);
				}
			}
			addBrowseScoping("subject", resourceLibraries, subjectBrowseValues, recordIdFull);
			
			if (recordStatus == MarcProcessor.RECORD_NEW){
				results.incAdded();
			}else{
				results.incUpdated();
			}
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

	
	/*private synchronized void addRecordIdToBrowse(String browseType, HashSet<Long> resourceLibraries, String browseValue, String sortValue, String recordIdFull) throws SQLException {
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
		PreparedStatement insertValueStatement = null;
		PreparedStatement getExistingBrowseValueStatement = null;
		
		Map<String, Long> existingBrowseValues = null;
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
		}//else{
		//	insertValueStatement = insertCallNumberBrowseValue;
		//	getExistingBrowseValueStatement = getExistingCallNumberBrowseValue;
		//	existingBrowseValues = existingBrowseValuesCallNumber;
		//}
		Long browseValueId = insertBrowseValue(browseType, browseValue, sortValue, existingBrowseValues, insertValueStatement,getExistingBrowseValueStatement);
		if (browseValueId == null){
			return;
		}
		
		for (Long curLibrary: resourceLibraries){
			//logger.debug("  Adding browse value " + browseValueId + " to library " + curLibrary);
			if (curLibrary == -1){
				//Add to global scope
				PreparedStatement insertBrowseScopeValueStatement = null;
				if (browseType.equals("title")){
					insertBrowseScopeValueStatement = insertTitleBrowseScopeValueStmts.get("global");
				}else if (browseType.equals("author")){
					insertBrowseScopeValueStatement = insertAuthorBrowseScopeValueStmts.get("global");
				}else if (browseType.equals("subject")){
					insertBrowseScopeValueStatement = insertSubjectBrowseScopeValueStmts.get("global");
				}//else{
				//	insertBrowseScopeValueStatement = insertCallNumberBrowseScopeValueStmts.get("global");
				//}
				insertBrowseScoping(browseType, browseValue, recordIdFull, insertBrowseScopeValueStatement, browseValueId, "global");
			}else{
				String librarySubdomain = librarySubdomains.get(curLibrary);
				//logger.debug("library subdomain for " + curLibrary + " is " + librarySubdomain);
				PreparedStatement insertBrowseLibraryScopeValueStatement = null;
				if (browseType.equals("title")){
					insertBrowseLibraryScopeValueStatement = insertTitleBrowseScopeValueStmts.get(librarySubdomain);
				}else if (browseType.equals("author")){
					insertBrowseLibraryScopeValueStatement = insertAuthorBrowseScopeValueStmts.get(librarySubdomain);
				}else if (browseType.equals("subject")){
					insertBrowseLibraryScopeValueStatement = insertSubjectBrowseScopeValueStmts.get(librarySubdomain);
				}//else{
				//	insertBrowseLibraryScopeValueStatement = insertCallNumberBrowseScopeValueStmts.get(librarySubdomain);
				//}
				insertBrowseScoping(browseType, browseValue, recordIdFull, insertBrowseLibraryScopeValueStatement, browseValueId, librarySubdomain);
			}
		}
	}*/

	/*private void insertBrowseScoping(String browseType, String browseValue, String recordIdFull,
			PreparedStatement insertBrowseScopeValueStatement, Long browseValueId, String scope) throws SQLException {
		//Add the scoping information to the table
		//Check to see if we already have an existing scope value
		try {
			insertBrowseScopeValueStatement.setLong(1, browseValueId);
			insertBrowseScopeValueStatement.setString(2, recordIdFull);
			insertBrowseScopeValueStatement.executeUpdate();
		} catch (Exception e) {
			//We occassionally get errors if multiple locations use the same call numbers
			//ignore for now.
			logger.error("Error adding " + browseType + " '" + browseValue + "' to scope " + scope + " browse scoping " + e.toString(), e);
			results.incErrors();
		}
	}*/
	
	private Long insertBrowseValue(String browseType, String browseValue, String sortValue, Map<String, Long> existingValues, PreparedStatement insertValueStatement, PreparedStatement getExistingBrowseValueStatement) {
		try {
			browseValue = Util.trimTo(255, browseValue);
			if (browseValue.length() == 0){
				//Do not insert browse values for empty values
				return null;
			}
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

	@Override
	public void finish() {
		try {
			vufindConn.prepareStatement("SET UNIQUE_CHECKS=0;").executeQuery();
			vufindConn.prepareStatement("SET foreign_key_checks=0;").executeQuery();
			//vufindConn.prepareStatement("SET sql_log_bin=0;").executeQuery();
			vufindConn.prepareStatement("SET innodb_support_xa=0;").executeQuery();
			vufindConn.prepareStatement("SET global innodb_flush_log_at_trx_commit=0;").executeQuery();
			vufindConn.setAutoCommit(false);
			
			//Update rankings
			PreparedStatement initRanking =  vufindConn.prepareStatement("set @r=0;");
			initRanking.executeUpdate();
			
			//Load data from temporary csv files
			for (String browseType : browseTypes){
				FileWriter writer = browseScopingFileWriters.get(browseType + "_global");
				writer.close();
				File titleBrowseScopingFile = browseScopingFiles.get(browseType + "_global");
				//logger.info("Global title browse file is " + titleBrowseScopingFile.getAbsolutePath());
				//Truncate the browse tables 
				clearBrowseScopingTables(browseType + "_browse");
				
				String sql = "load data local infile ? into table " + browseType + "_browse_scoped_results_global fields terminated by ',' enclosed by '\"' lines terminated by '\n' (browseValueId, record)";
				//logger.info(sql);
				PreparedStatement loadGlobalTitleCsv = vufindConn.prepareStatement(sql);
				
				loadGlobalTitleCsv.setString(1, titleBrowseScopingFile.getAbsolutePath());
				loadGlobalTitleCsv.executeUpdate();
				titleBrowseScopingFile.deleteOnExit();
				results.addNote("Imported global scoping for " + browseType);
				results.saveResults();
				vufindConn.commit();
			}
			
			results.addNote("Updating browse tables for authors");
			results.saveResults();
			PreparedStatement authorRankingUpdate = vufindConn.prepareStatement("UPDATE author_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;");
			authorRankingUpdate.executeUpdate();
			PreparedStatement authorMetaDataClear = vufindConn.prepareStatement("TRUNCATE author_browse_metadata");
			authorMetaDataClear.executeUpdate();
			PreparedStatement authorMetaDataUpdate = vufindConn.prepareStatement("INSERT INTO author_browse_metadata (SELECT 0, -1, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM author_browse inner join author_browse_scoped_results_global ON id = browseValueId where alphaRank > 0)");
			authorMetaDataUpdate.executeUpdate();
			vufindConn.commit();
			
			/*results.addNote("Updating browse tables for call numbers");
			results.saveResults();
			initRanking.executeUpdate();
			PreparedStatement callnumberRankingUpdate = vufindConn.prepareStatement("UPDATE callnumber_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;");
			callnumberRankingUpdate.executeUpdate();
			PreparedStatement callnumberMetaDataClear = vufindConn.prepareStatement("TRUNCATE callnumber_browse_metadata");
			callnumberMetaDataClear.executeUpdate();
			PreparedStatement callnumberMetaDataUpdate = vufindConn.prepareStatement("INSERT INTO callnumber_browse_metadata (SELECT 0, -1, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM callnumber_browse inner join callnumber_browse_scoped_results_global ON id = browseValueId where alphaRank > 0)");
			callnumberMetaDataUpdate.executeUpdate();*/
			
			results.addNote("Updating browse tables for subjects");
			results.saveResults();
			initRanking.executeUpdate();
			PreparedStatement subjectRankingUpdate = vufindConn.prepareStatement("UPDATE subject_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;");
			subjectRankingUpdate.executeUpdate();
			PreparedStatement subjectMetaDataClear = vufindConn.prepareStatement("TRUNCATE subject_browse_metadata");
			subjectMetaDataClear.executeUpdate();
			PreparedStatement subjectMetaDataUpdate = vufindConn.prepareStatement("INSERT INTO subject_browse_metadata (SELECT 0, -1, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM subject_browse inner join subject_browse_scoped_results_global ON id = browseValueId where alphaRank > 0)");
			subjectMetaDataUpdate.executeUpdate();
			vufindConn.commit();
			
			results.addNote("Updating browse tables for titles");
			results.saveResults();
			initRanking.executeUpdate();
			
			PreparedStatement titleRankingUpdate = vufindConn.prepareStatement("UPDATE title_browse SET alphaRank = @r:=(@r + 1) ORDER BY `sortValue`;");
			titleRankingUpdate.executeUpdate();
			PreparedStatement titleMetaDataClear = vufindConn.prepareStatement("TRUNCATE title_browse_metadata");
			titleMetaDataClear.executeUpdate();
			PreparedStatement titleMetaDataUpdate = vufindConn.prepareStatement("INSERT INTO title_browse_metadata (SELECT 0, -1, MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM title_browse inner join title_browse_scoped_results_global ON id = browseValueId where alphaRank > 0)");
			titleMetaDataUpdate.executeUpdate();
			vufindConn.commit();
			
			for (Long libraryId : librarySubdomains.keySet()){
				String subdomain = librarySubdomains.get(libraryId);
				
				for (String browseType : browseTypes){
					FileWriter writer = browseScopingFileWriters.get(browseType + "_" + subdomain);
					writer.close();
					File titleBrowseScopingFile = browseScopingFiles.get(browseType + "_" + subdomain);
					
					PreparedStatement loadScopedTitleCsv = vufindConn.prepareStatement("load data local infile ? into table " + browseType + "_browse_scoped_results_library_" + subdomain + " fields terminated by ',' enclosed by '\"' lines terminated by '\n' (browseValueId, record)");
					//logger.info(browseType + " browse file for " + subdomain + " is " + titleBrowseScopingFile.getAbsolutePath());
					loadScopedTitleCsv.setString(1, titleBrowseScopingFile.getAbsolutePath());
					loadScopedTitleCsv.executeUpdate();
					titleBrowseScopingFile.deleteOnExit();
					
					results.addNote("Imported " + subdomain + " scoping for " + browseType);
					results.saveResults();
					vufindConn.commit();
				}
				
				results.addNote("Updating meta data for " + subdomain);
				results.saveResults();
				 
				try{
					//Get the number of records for the library
					PreparedStatement numRecordsStatement = vufindConn.prepareStatement("SELECT count(record) as numRecords from title_browse_scoped_results_library_" + subdomain);
					ResultSet numRecordsRS = numRecordsStatement.executeQuery();
					if (numRecordsRS.first() && numRecordsRS.getLong("numRecords") > 0){
						vufindConn.prepareStatement("INSERT INTO title_browse_metadata (SELECT 1, " + libraryId + ", MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM title_browse inner join title_browse_scoped_results_library_" + subdomain + " ON id = browseValueId where alphaRank > 0)").executeUpdate();
						vufindConn.prepareStatement("INSERT INTO author_browse_metadata (SELECT 1, " + libraryId + ", MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM author_browse inner join author_browse_scoped_results_library_" + subdomain + " ON id = browseValueId where alphaRank > 0)").executeUpdate();
						vufindConn.prepareStatement("INSERT INTO subject_browse_metadata (SELECT 1, " + libraryId + ", MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM subject_browse inner join subject_browse_scoped_results_library_" + subdomain + " ON id = browseValueId where alphaRank > 0)").executeUpdate();
						//vufindConn.prepareStatement("INSERT INTO callnumber_browse_metadata (SELECT 1, " + libraryId + ", MIN(alphaRank) as minAlphaRank, MAX(alphaRank) as maxAlphaRank, count(id) as numResults FROM callnumber_browse inner join callnumber_browse_scoped_results_library_" + subdomain + " ON id = browseValueId where alphaRank > 0)").executeUpdate();
						vufindConn.commit();
					}else{
						logger.debug("Skipped updating " + subdomain + " because there are no records");
					}
				} catch (SQLException e) {
					logger.error("Error updating meta data for " + subdomain, e);
					results.incErrors();
					results.addNote("Error updating meta data for " + subdomain + " " + e.toString());
				}
			}
			vufindConn.setAutoCommit(true);
			vufindConn.prepareStatement("SET UNIQUE_CHECKS=1;").executeQuery();
			vufindConn.prepareStatement("SET foreign_key_checks=1;").executeQuery();
			//vufindConn.prepareStatement("SET sql_log_bin=1;").executeQuery();
			vufindConn.prepareStatement("SET innodb_support_xa=1;").executeQuery();
			vufindConn.prepareStatement("SET global innodb_flush_log_at_trx_commit=1;").executeQuery();
			results.addNote("Finished updating browse tables");
			results.saveResults();
			
		} catch (SQLException e) {
			logger.error("Error finishing Alpha Browse Processing", e);
			results.incErrors();
			results.addNote("Error finishing Alpha Browse Processing" + e.toString());
		} catch (IOException e1) {
			logger.error("Error finishing Alpha Browse Processing", e1);
			results.incErrors();
			results.addNote("Error finishing Alpha Browse Processing" + e1.toString());
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
	
	private void clearBrowseScopingTables(String tableName) throws SQLException{
		logger.info("Truncating " + tableName);
		results.addNote("Truncating " + tableName);
		results.saveResults();
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
