package org.vufind;

import au.com.bytecode.opencsv.CSVReader;
import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.*;

import java.io.File;
import java.io.FileReader;
import java.io.FilenameFilter;
import java.io.IOException;
import java.sql.*;
import java.util.*;
import java.util.Date;

/**
 * Description goes here
 * Rampart Marc Conversion
 * User: Mark Noble
 * Date: 10/17/13
 * Time: 9:26 AM
 */
public class RecordGroupingProcessor {
	private Logger logger;
	private String recordNumberTag = "";
	private String recordNumberPrefix = "";
	private String itemTag;
	private boolean useEContentSubfield = false;
	private char eContentSubfield = ' ';
	private PreparedStatement insertGroupedWorkStmt;
	private PreparedStatement updateDateUpdatedForGroupedWorkStmt;
	private PreparedStatement getExistingIdentifierStmt;
	private PreparedStatement insertIdentifierStmt;
	private PreparedStatement addIdentifierToGroupedWorkStmt;
	private PreparedStatement addPrimaryIdentifierForWorkStmt;
	private PreparedStatement removePrimaryIdentifierStmt;
	private PreparedStatement removeIdentifiersForPrimaryIdentifierStmt;
	private PreparedStatement removePrimaryIdentifiersForWorkStmt;
	private PreparedStatement addPrimaryIdentifierToSecondaryIdentifierRefStmt;

	private int numRecordsProcessed = 0;
	private int numGroupedWorksAdded = 0;

	private boolean fullRegrouping;
	private long startTime = new Date().getTime();

	private HashMap<String, RecordIdentifier> recordIdentifiers = new HashMap<String, RecordIdentifier>();
	private static HashMap<String, String> authorAuthorities = new HashMap<String, String>();
	private static HashMap<String, String> titleAuthorities = new HashMap<String, String>();

	private HashMap<String, HashMap<String, String>> translationMaps = new HashMap<String, HashMap<String, String>>();

	private HashMap<String, Long> existingGroupedWorks = new HashMap<String, Long>();

	//A list of grouped works that have been manually merged.
	private HashMap<String, String> mergedGroupedWorks = new HashMap<String, String>();

	public RecordGroupingProcessor(Connection dbConnection, String serverName, Ini configIni, Logger logger, boolean fullRegrouping) {
		this.logger = logger;
		this.fullRegrouping = fullRegrouping;
		recordNumberTag = configIni.get("Reindex", "recordNumberTag");
		recordNumberPrefix = configIni.get("Reindex", "recordNumberPrefix");
		itemTag = configIni.get("Reindex", "itemTag");
		useEContentSubfield = Boolean.parseBoolean(configIni.get("Reindex", "useEContentSubfield"));
		eContentSubfield = getSubfieldIndicatorFromConfig(configIni, "eContentSubfield");

		try{
			insertGroupedWorkStmt = dbConnection.prepareStatement("INSERT INTO " + RecordGrouperMain.groupedWorkTableName + " (full_title, author, grouping_category, permanent_id, date_updated) VALUES (?, ?, ?, ?, ?)", Statement.RETURN_GENERATED_KEYS) ;
			updateDateUpdatedForGroupedWorkStmt = dbConnection.prepareStatement("UPDATE grouped_work SET date_updated = ? where id = ?");
			getExistingIdentifierStmt = dbConnection.prepareStatement("SELECT id FROM " + RecordGrouperMain.groupedWorkIdentifiersTableName + " where type = ? and identifier = ?",  ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			insertIdentifierStmt = dbConnection.prepareStatement("INSERT INTO " + RecordGrouperMain.groupedWorkIdentifiersTableName + " (type, identifier) VALUES (?, ?)", Statement.RETURN_GENERATED_KEYS);
			addIdentifierToGroupedWorkStmt = dbConnection.prepareStatement("INSERT IGNORE INTO " + RecordGrouperMain.groupedWorkIdentifiersRefTableName + " (grouped_work_id, identifier_id) VALUES (?, ?)");
			addPrimaryIdentifierForWorkStmt = dbConnection.prepareStatement("INSERT INTO grouped_work_primary_identifiers (grouped_work_id, type, identifier) VALUES (?, ?, ?)", Statement.RETURN_GENERATED_KEYS);
			removePrimaryIdentifierStmt = dbConnection.prepareStatement("DELETE FROM grouped_work_primary_identifiers WHERE type = ? and identifier = ?");
			removeIdentifiersForPrimaryIdentifierStmt = dbConnection.prepareStatement("DELETE FROM grouped_work_primary_to_secondary_id_ref where primary_identifier_id = ?");
			removePrimaryIdentifiersForWorkStmt = dbConnection.prepareStatement("DELETE FROM grouped_work_primary_identifiers where grouped_work_id = ?");
			addPrimaryIdentifierToSecondaryIdentifierRefStmt = dbConnection.prepareStatement("INSERT INTO grouped_work_primary_to_secondary_id_ref (primary_identifier_id, secondary_identifier_id) VALUES (?, ?) ");
			loadAuthorities();
			if (!fullRegrouping){
				PreparedStatement loadExistingGroupedWorksStmt = dbConnection.prepareStatement("SELECT id, permanent_id from grouped_work");
				ResultSet loadExistingGroupedWorksRS = loadExistingGroupedWorksStmt.executeQuery();
				while (loadExistingGroupedWorksRS.next()){
					existingGroupedWorks.put(loadExistingGroupedWorksRS.getString("permanent_id"), loadExistingGroupedWorksRS.getLong("id"));
				}
				loadExistingGroupedWorksRS.close();
				loadExistingGroupedWorksStmt.close();
			}
			PreparedStatement loadMergedWorksStmt = dbConnection.prepareStatement("SELECT * from merged_grouped_works");
			ResultSet mergedWorksRS = loadMergedWorksStmt.executeQuery();
			while (mergedWorksRS.next()){
				mergedGroupedWorks.put(mergedWorksRS.getString("sourceGroupedWorkId"), mergedWorksRS.getString("destinationGroupedWorkId"));
			}
			mergedWorksRS.close();
		}catch (Exception e){
			logger.error("Error setting up prepared statements", e);
		}

		loadTranslationMaps(serverName);
	}

	private char getSubfieldIndicatorFromConfig(Ini configIni, String subfieldName) {
		String subfieldString = configIni.get("Reindex", subfieldName);
		char subfield = ' ';
		if (subfieldString.length() > 0)  {
			subfield = subfieldString.charAt(0);
		}
		return subfield;
	}

	public static String mapAuthorAuthority(String originalAuthor){
		if (authorAuthorities.containsKey(originalAuthor)){
			return authorAuthorities.get(originalAuthor);
		}else{
			return originalAuthor;
		}
	}

	public static String mapTitleAuthority(String originalTitle){
		if (titleAuthorities.containsKey(originalTitle)){
			return titleAuthorities.get(originalTitle);
		}else{
			return originalTitle;
		}
	}

	private void loadAuthorities() {
		try {
			CSVReader csvReader = new CSVReader(new FileReader(new File("./author_authorities.properties")));
			String[] curLine = csvReader.readNext();
			while (curLine != null){
				if (curLine.length >= 2){
					authorAuthorities.put(curLine[0], curLine[1]);
				}
				curLine = csvReader.readNext();
			}
		} catch (IOException e) {
			logger.error("Unable to load author authorities", e);
		}
		try {
			CSVReader csvReader = new CSVReader(new FileReader(new File("./title_authorities.properties")));
			String[] curLine = csvReader.readNext();
			while (curLine != null){
				if (curLine.length >= 2){
					titleAuthorities.put(curLine[0], curLine[1]);
				}
				curLine = csvReader.readNext();
			}
		} catch (IOException e) {
			logger.error("Unable to load title authorities", e);
		}
	}

	private RecordIdentifier getPrimaryIdentifierFromMarcRecord(Record marcRecord){
		RecordIdentifier identifier = null;
		List<DataField> field907 = getDataFields(marcRecord, recordNumberTag);
		//Make sure we only get one ils identifier
		for (DataField cur907 : field907){
			Subfield subfieldA = cur907.getSubfield('a');
			if (subfieldA != null && (recordNumberPrefix.length() == 0 || subfieldA.getData().length() > recordNumberPrefix.length())){
				if (cur907.getSubfield('a').getData().substring(0,recordNumberPrefix.length()).equals(recordNumberPrefix)){
					String recordNumber = cur907.getSubfield('a').getData();
					identifier = new RecordIdentifier();
					identifier.setValue("ils", recordNumber);
					break;
				}
			}
		}

		//Check to see if the record is an overdrive record
		if (useEContentSubfield){
			boolean allItemsOverDrive = true;

			List<DataField> itemFields = getDataFields(marcRecord, itemTag);
			int numItems = itemFields.size();
			for (DataField itemField : itemFields){
				if (itemField.getSubfield(eContentSubfield) != null){
					//Check the protection types and sources
					String eContentData = itemField.getSubfield(eContentSubfield).getData();
					if (eContentData.indexOf(':') >= 0){
						String[] eContentFields = eContentData.split(":");
						String sourceType = eContentFields[0].toLowerCase().trim();
						if (!sourceType.equals("overdrive")){
							allItemsOverDrive = false;
						}
					}else{
						allItemsOverDrive = false;
					}
				}else{
					allItemsOverDrive = false;
				}
			}
			if (numItems == 0){
				allItemsOverDrive = false;
			}
			if (allItemsOverDrive){
				//Don't return a primary identifier for this record (we will suppress the bib and just use OverDrive APIs)
				return null;
			}
		}else{
			//Check the 856 for an overdrive url
			List<DataField> linkFields = getDataFields(marcRecord, "856");
			for (DataField linkField : linkFields){
				if (linkField.getSubfield('u') != null){
					//Check the url to see if it is from OverDrive
					String linkData = linkField.getSubfield('u').getData().trim();
					if (linkData.matches("(?i)^http://.*?lib\\.overdrive\\.com/ContentDetails\\.htm\\?id=[\\da-f]{8}-[\\da-f]{4}-[\\da-f]{4}-[\\da-f]{4}-[\\da-f]{12}$")){
						return null;
					}
				}
			}
		}

		if (identifier != null && identifier.isValid()){
			return identifier;
		}else{
			return null;
		}
	}
	private HashSet<RecordIdentifier> getIdentifiersFromMarcRecord(Record marcRecord) {
		HashSet<RecordIdentifier> identifiers = new HashSet<RecordIdentifier>();
		//Load identifiers
		List<DataField> identifierFields = getDataFields(marcRecord, new String[]{"020", "024"});
		for (DataField identifierField : identifierFields){
			if (identifierField.getSubfield('a') != null){
				String identifierValue = identifierField.getSubfield('a').getData().trim();
				//Get rid of any extra data at the end of the identifier
				if (identifierValue.indexOf(' ') > 0){
					identifierValue = identifierValue.substring(0, identifierValue.indexOf(' '));
				}
				String identifierType;
				if (identifierField.getTag().equals("020")){
					identifierType = "isbn";
					identifierValue = identifierValue.replaceAll("\\D", "");
					if (identifierValue.length() == 10){
						identifierValue = convertISBN10to13(identifierValue);
					}
				}else{
					identifierType = "upc";
				}
				RecordIdentifier identifier = new RecordIdentifier();
				if (identifierValue.length() > 20){
					continue;
				}else if (identifierValue.length() == 0){
					continue;
				}
				identifier.setValue(identifierType, identifierValue);
				if (identifier.isValid()){
					identifiers.add(identifier);
				}
			}
		}
		return identifiers;
	}

	private List<DataField> getDataFields(Record marcRecord, String tag) {
		List variableFields = marcRecord.getVariableFields(tag);
		List<DataField> variableFieldsReturn = new ArrayList<DataField>();
		for (Object variableField : variableFields){
			if (variableField instanceof DataField){
				variableFieldsReturn.add((DataField)variableField);
			}
		}
		return variableFieldsReturn;
	}

	private List<DataField> getDataFields(Record marcRecord, String[] tags) {
		List variableFields = marcRecord.getVariableFields(tags);
		List<DataField> variableFieldsReturn = new ArrayList<DataField>();
		for (Object variableField : variableFields){
			if (variableField instanceof DataField){
				variableFieldsReturn.add((DataField)variableField);
			}
		}
		return variableFieldsReturn;
	}

	public void processMarcRecord(Record marcRecord, String loadFormatFrom, char formatSubfield){
		RecordIdentifier primaryIdentifier = getPrimaryIdentifierFromMarcRecord(marcRecord);
		processMarcRecord(marcRecord, primaryIdentifier, loadFormatFrom, formatSubfield);
	}

	public void processMarcRecord(Record marcRecord, RecordIdentifier primaryIdentifier, String loadFormatFrom, char formatSubfield){
		if (primaryIdentifier != null){
			//Get data for the grouped record
			GroupedWork workForTitle = new GroupedWork();

			//Title
			DataField field245 = setWorkTitleBasedOnMarcRecord(marcRecord, workForTitle);

			//Format
			String groupingFormat;
			if (loadFormatFrom.equals("bib")){
				String format = getFormatFromBib(marcRecord);
				groupingFormat = categoryMap.get(formatsToGroupingCategory.get(format));
			}else {
				//get format from item
				groupingFormat = getFormatFromItems(marcRecord, formatSubfield);
			}

			//Author
			setWorkAuthorBasedOnMarcRecord(marcRecord, workForTitle, field245, groupingFormat);

			//Identifiers
			HashSet<RecordIdentifier> identifiers = getIdentifiersFromMarcRecord(marcRecord);

			workForTitle.groupingCategory = groupingFormat;
			workForTitle.identifiers = identifiers;

			addGroupedWorkToDatabase(primaryIdentifier, workForTitle);
		}
	}

	public void processEVokeRecord(Record marcRecord, RecordIdentifier primaryIdentifier){
		if (primaryIdentifier != null){
			//Get data for the grouped record
			GroupedWork workForTitle = new GroupedWork();

			//Title
			DataField field245 = setWorkTitleBasedOnMarcRecord(marcRecord, workForTitle);

			//Format - right now
			String format = "eBook";
			String groupingFormat = categoryMap.get(formatsToGroupingCategory.get(format));

			//Author
			setWorkAuthorBasedOnMarcRecord(marcRecord, workForTitle, field245, groupingFormat);

			//Identifiers
			HashSet<RecordIdentifier> identifiers = getIdentifiersFromMarcRecord(marcRecord);

			workForTitle.groupingCategory = groupingFormat;
			workForTitle.identifiers = identifiers;

			addGroupedWorkToDatabase(primaryIdentifier, workForTitle);
		}
	}

	private void setWorkAuthorBasedOnMarcRecord(Record marcRecord, GroupedWork workForTitle, DataField field245, String groupingFormat) {
		String author = null;
		DataField field100 = (DataField)marcRecord.getVariableField("100");
		DataField field110 = (DataField)marcRecord.getVariableField("110");
		DataField field260 = (DataField)marcRecord.getVariableField("260");
		DataField field710 = (DataField)marcRecord.getVariableField("710");

		//Depending on the format we will promote the use of the 245c
		if (field100 != null && field100.getSubfield('a') != null){
			author = field100.getSubfield('a').getData();
		}else if (field110 != null && field110.getSubfield('a') != null){
			author = field110.getSubfield('a').getData();
		}else if (groupingFormat.equals("book") && field245 != null && field245.getSubfield('c') != null){
			author = field245.getSubfield('c').getData();
			if (author.indexOf(';') > 0){
				author = author.substring(0, author.indexOf(';') -1);
			}
		}else if (field260 != null && field260.getSubfield('b') != null){
			author = field260.getSubfield('b').getData();
		}else if (field710 != null && field710.getSubfield('a') != null){
			author = field710.getSubfield('a').getData();
		}else if (!groupingFormat.equals("book") && field245 != null && field245.getSubfield('c') != null){
			author = field245.getSubfield('c').getData();
			if (author.indexOf(';') > 0){
				author = author.substring(0, author.indexOf(';') -1);
			}
		}
		if (author != null){
			workForTitle.setAuthor(author);
		}
	}

	private DataField setWorkTitleBasedOnMarcRecord(Record marcRecord, GroupedWork workForTitle) {
		DataField field245 = (DataField)marcRecord.getVariableField("245");
		if (field245 != null && field245.getSubfield('a') != null){
			String fullTitle = field245.getSubfield('a').getData();

			char nonFilingCharacters = field245.getIndicator2();
			if (nonFilingCharacters == ' ') nonFilingCharacters = '0';
			int numNonFilingCharacters = 0;
			if (nonFilingCharacters >= '0' && nonFilingCharacters <= '9'){
				numNonFilingCharacters = Integer.parseInt(Character.toString(nonFilingCharacters));
			}

			//Add in subtitle (subfield b as well to avoid problems with gov docs, etc)
			StringBuilder groupingSubtitle = new StringBuilder();
			if (field245.getSubfield('b') != null){
				groupingSubtitle.append(field245.getSubfield('b').getData());
			}

			//Group volumes, seasons, etc. independently
			if (field245.getSubfield('n') != null){
				if (groupingSubtitle.length() > 0) groupingSubtitle.append(" ");
				groupingSubtitle.append(field245.getSubfield('n').getData());
			}
			if (field245.getSubfield('p') != null){
				if (groupingSubtitle.length() > 0) groupingSubtitle.append(" ");
				groupingSubtitle.append(field245.getSubfield('p').getData());
			}

			workForTitle.setTitle(fullTitle, numNonFilingCharacters, groupingSubtitle.toString());
		}
		return field245;
	}

	private void addGroupedWorkToDatabase(RecordIdentifier primaryIdentifier, GroupedWork groupedWork) {
		String groupedWorkPermanentId = groupedWork.getPermanentId();
		if (mergedGroupedWorks.containsKey(groupedWorkPermanentId)){
			String originalGroupedWorkPermanentId = groupedWorkPermanentId;
			groupedWorkPermanentId = mergedGroupedWorks.get(groupedWorkPermanentId);
			groupedWork.overridePermanentId(groupedWorkPermanentId);

			logger.debug("Overriding grouped work " + originalGroupedWorkPermanentId + " with " + groupedWorkPermanentId);

			//Mark that the original was updated
			if (existingGroupedWorks.containsKey(originalGroupedWorkPermanentId)) {
				//There is an existing grouped record
				long originalGroupedWorkId = existingGroupedWorks.get(originalGroupedWorkPermanentId);
				markWorkUpdated(originalGroupedWorkId);
				try {
					removePrimaryIdentifiersForWorkStmt.setLong(1, originalGroupedWorkId);
					removePrimaryIdentifiersForWorkStmt.executeUpdate();
				} catch (SQLException e) {
					logger.error("Error removing primary identifiers for merged work " + originalGroupedWorkPermanentId + "(" + originalGroupedWorkId + ")");
				}
			}
		}
		numRecordsProcessed++;
		long groupedWorkId = -1;
		try{
			boolean groupedWorkFound = false;
			if (existingGroupedWorks.containsKey(groupedWorkPermanentId)){
				//There is an existing grouped record
				groupedWorkId = existingGroupedWorks.get(groupedWorkPermanentId);

				//Mark that the work has been updated
				markWorkUpdated(groupedWorkId);
				groupedWorkFound = true;
			}

			if (!groupedWorkFound){
				//Need to insert a new grouped record
				insertGroupedWorkStmt.setString(1, groupedWork.getTitle());
				insertGroupedWorkStmt.setString(2, groupedWork.getAuthor());
				insertGroupedWorkStmt.setString(3, groupedWork.groupingCategory);
				insertGroupedWorkStmt.setString(4, groupedWorkPermanentId);
				insertGroupedWorkStmt.setLong(5, new Date().getTime() / 1000);

				insertGroupedWorkStmt.executeUpdate();
				ResultSet generatedKeysRS = insertGroupedWorkStmt.getGeneratedKeys();
				if (generatedKeysRS.next()){
					groupedWorkId = generatedKeysRS.getLong(1);
				}
				generatedKeysRS.close();
				numGroupedWorksAdded++;
				existingGroupedWorks.put(groupedWorkPermanentId, groupedWorkId);
			}

			//Update identifiers
			addPrimaryIdentifierForWorkToDB(groupedWorkId, primaryIdentifier);
			addIdentifiersForRecordToDB(groupedWorkId, groupedWork.identifiers, primaryIdentifier);
		}catch (Exception e){
			logger.error("Error adding grouped record to grouped work ", e);
		}

	}

	private void markWorkUpdated(long groupedWorkId) {
		try{
			updateDateUpdatedForGroupedWorkStmt.setLong(1, new Date().getTime() / 1000);
			updateDateUpdatedForGroupedWorkStmt.setLong(2, groupedWorkId);
			updateDateUpdatedForGroupedWorkStmt.executeUpdate();
		}catch (Exception e){
			logger.error("Error updating date updated for grouped work ", e);
		}
	}

	private void addPrimaryIdentifierForWorkToDB(long groupedWorkId, RecordIdentifier primaryIdentifier) {
		deletePrimaryIdentifier(primaryIdentifier);

		try {
			addPrimaryIdentifierForWorkStmt.setLong(1, groupedWorkId);
			addPrimaryIdentifierForWorkStmt.setString(2, primaryIdentifier.getType());
			addPrimaryIdentifierForWorkStmt.setString(3, primaryIdentifier.getIdentifier());
			addPrimaryIdentifierForWorkStmt.executeUpdate();
			ResultSet primaryIdentifierRS = addPrimaryIdentifierForWorkStmt.getGeneratedKeys();
			primaryIdentifierRS.next();
			primaryIdentifier.setIdentifierId(primaryIdentifierRS.getLong(1));
			primaryIdentifierRS.close();
		} catch (SQLException e) {
			logger.error("Error adding primary identifier to grouped work " + groupedWorkId + " " + primaryIdentifier.toString(), e);
		}
	}

	private void addIdentifiersForRecordToDB(long groupedWorkId, HashSet<RecordIdentifier> identifiers, RecordIdentifier primaryIdentifier) throws SQLException {
		//Remove any references to old identifiers
		removeIdentifiersForPrimaryIdentifier(primaryIdentifier);

		//Cleanup identifiers that no longer have any primary identifiers at the end.
		for (RecordIdentifier curIdentifier :  identifiers){
			if (recordIdentifiers.containsKey(curIdentifier.toString())){
				curIdentifier = recordIdentifiers.get(curIdentifier.toString());
			} else {
				insertNewSecondaryIdentifier(curIdentifier);
			}
			if (!curIdentifier.isLinkedToGroupedWork(groupedWorkId)){
				addSecondaryIdentifierToGroupedWork(groupedWorkId, curIdentifier);
			}
			addPrimaryToSecondaryReferences(primaryIdentifier, curIdentifier);
		}
	}

	private void addPrimaryToSecondaryReferences(RecordIdentifier primaryIdentifier, RecordIdentifier curIdentifier) throws SQLException {
		//add a reference between the primary identifier and secondary identifiers.
		addPrimaryIdentifierToSecondaryIdentifierRefStmt.setLong(1, primaryIdentifier.getIdentifierId());
		addPrimaryIdentifierToSecondaryIdentifierRefStmt.setLong(2, curIdentifier.getIdentifierId());
		addPrimaryIdentifierToSecondaryIdentifierRefStmt.executeUpdate();
	}

	private void addSecondaryIdentifierToGroupedWork(long groupedWorkId, RecordIdentifier curIdentifier) {
		//Add the identifier reference
		try{
			addIdentifierToGroupedWorkStmt.setLong(1, groupedWorkId);
			addIdentifierToGroupedWorkStmt.setLong(2, curIdentifier.getIdentifierId());
			addIdentifierToGroupedWorkStmt.executeUpdate();
			curIdentifier.addRelatedGroupedWork(groupedWorkId);
		}catch (SQLException e){
			logger.error("Error adding identifier " + curIdentifier.getType() + " - " + curIdentifier.getIdentifier() + " identifierId " + curIdentifier.getIdentifierId() + " to grouped work " + groupedWorkId, e);
		}
	}

	private void insertNewSecondaryIdentifier(RecordIdentifier curIdentifier) throws SQLException {
		//This is a brand new identifier
		insertIdentifierStmt.setString(1, curIdentifier.getType());
		insertIdentifierStmt.setString(2, curIdentifier.getIdentifier());
		try{
			insertIdentifierStmt.executeUpdate();
			ResultSet generatedKeys = insertIdentifierStmt.getGeneratedKeys();
			generatedKeys.next();
			long identifierId = generatedKeys.getLong(1);
			generatedKeys.close();
			curIdentifier.setIdentifierId(identifierId);
			if (curIdentifier.isSharedIdentifier()){
				recordIdentifiers.put(curIdentifier.toString(), curIdentifier);
			}
		}catch (SQLException e){
			if (fullRegrouping){
				logger.warn("Tried to insert a duplicate identifier " + curIdentifier.toString());
			}
			//Get the id of the identifier
			getExistingIdentifierStmt.setString(1, curIdentifier.getType());
			getExistingIdentifierStmt.setString(2, curIdentifier.getIdentifier());
			ResultSet identifierIdRs = getExistingIdentifierStmt.executeQuery();
			if (identifierIdRs.next()){
				curIdentifier.setIdentifierId(identifierIdRs.getLong(1));
			}
			identifierIdRs.close();
		}
	}

	private void removeIdentifiersForPrimaryIdentifier(RecordIdentifier primaryIdentifier) {
		if (!fullRegrouping){
			try{
				removeIdentifiersForPrimaryIdentifierStmt.setLong(1, primaryIdentifier.getIdentifierId());
				removeIdentifiersForPrimaryIdentifierStmt.executeUpdate();
			} catch (SQLException e) {
				logger.error("Unable to remove secondary identifiers from primary identifier " + primaryIdentifier.toString() + " id " + primaryIdentifier.getIdentifierId(), e);
			}
		}
	}

	public void processRecord(RecordIdentifier primaryIdentifier, String title, String subtitle, String author, String format, HashSet<RecordIdentifier>identifiers){
		GroupedWork groupedWork = new GroupedWork();

		//Replace & with and for better matching
		groupedWork.setTitle(title, 0, subtitle);

		if (author != null){
			groupedWork.setAuthor(author);
		}

		if (format.equalsIgnoreCase("audiobook")){
			groupedWork.groupingCategory = "book";
		}else if (format.equalsIgnoreCase("ebook")){
			groupedWork.groupingCategory = "book";
		}else if (format.equalsIgnoreCase("music")){
			groupedWork.groupingCategory = "music";
		}else if (format.equalsIgnoreCase("video")){
			groupedWork.groupingCategory = "movie";
		}

		groupedWork.identifiers = identifiers;

		addGroupedWorkToDatabase(primaryIdentifier, groupedWork);
	}

	private String getFormatFromItems(Record record, char formatSubfield) {
		List<DataField> itemFields = getDataFields(record, itemTag);
		for (DataField itemField : itemFields) {
			if (itemField.getSubfield(formatSubfield) != null) {
				String originalFormat = itemField.getSubfield(formatSubfield).getData().toLowerCase();
				String format = translateValue("format_group", originalFormat);
				if (format != null && !format.equals(originalFormat)){
					return format;
				}
			}
		}
		return "book";
	}
	private String getFormatFromBib(Record record) {
		//Check to see if the title is eContent based on the 989 field
		List<DataField> itemFields = getDataFields(record, itemTag);
		for (DataField itemField : itemFields) {
			if (itemField.getSubfield('w') != null) {
				//The record is some type of eContent.  For this purpose, we don't care what type.
				return "eContent";
			}
		}

		String leader = record.getLeader().toString();
		char leaderBit;
		ControlField fixedField = (ControlField) record.getVariableField("008");
		char formatCode;

		// check for music recordings quickly so we can figure out if it is music
		// for category (need to do here since checking what is on the Compact
		// Disc/Phonograph, etc is difficult).
		if (leader.length() >= 6) {
			leaderBit = leader.charAt(6);
			switch (Character.toUpperCase(leaderBit)) {
				case 'J':
					return "MusicRecording";
			}
		}

		// check for playaway in 260|b
		DataField sysDetailsNote = (DataField) record.getVariableField("260");
		if (sysDetailsNote != null) {
			if (sysDetailsNote.getSubfield('b') != null) {
				String sysDetailsValue = sysDetailsNote.getSubfield('b').getData().toLowerCase();
				if (sysDetailsValue.contains("playaway")) {
					return "Playaway";
				}
			}
		}

		// Check for formats in the 538 field
		DataField sysDetailsNote2 = (DataField) record.getVariableField("538");
		if (sysDetailsNote2 != null) {
			if (sysDetailsNote2.getSubfield('a') != null) {
				String sysDetailsValue = sysDetailsNote2.getSubfield('a').getData().toLowerCase();
				if (sysDetailsValue.contains("playaway")) {
					return "Playaway";
				} else if (sysDetailsValue.contains("bluray")
						|| sysDetailsValue.contains("blu-ray")) {
					return "Blu-ray";
				} else if (sysDetailsValue.contains("dvd")) {
					return "DVD";
				} else if (sysDetailsValue.contains("vertical file")) {
					return "VerticalFile";
				}
			}
		}

		// Check for formats in the 500 tag
		DataField noteField = (DataField) record.getVariableField("500");
		if (noteField != null) {
			if (noteField.getSubfield('a') != null) {
				String noteValue = noteField.getSubfield('a').getData().toLowerCase();
				if (noteValue.contains("vertical file")) {
					return "VerticalFile";
				}
			}
		}

		// Check for large print book (large format in 650, 300, or 250 fields)
		// Check for blu-ray in 300 fields
		DataField edition = (DataField) record.getVariableField("250");
		if (edition != null) {
			if (edition.getSubfield('a') != null) {
				if (edition.getSubfield('a').getData().toLowerCase().contains("large type")) {
					return "LargePrint";
				}
			}
		}

		List<DataField> physicalDescription = getDataFields(record, "300");
		if (physicalDescription != null) {
			Iterator<DataField> fieldsIter = physicalDescription.iterator();
			DataField field;
			while (fieldsIter.hasNext()) {
				field = fieldsIter.next();
				@SuppressWarnings("unchecked")
				List<Subfield> subFields = field.getSubfields();
				for (Subfield subfield : subFields) {
					if (subfield.getData().toLowerCase().contains("large type")) {
						return "LargePrint";
					} else if (subfield.getData().toLowerCase().contains("bluray")
							|| subfield.getData().toLowerCase().contains("blu-ray")) {
						return "Blu-ray";
					}
				}
			}
		}
		List<DataField> topicalTerm = getDataFields(record, "650");
		if (topicalTerm != null) {
			Iterator<DataField> fieldsIter = topicalTerm.iterator();
			DataField field;
			while (fieldsIter.hasNext()) {
				field = fieldsIter.next();
				@SuppressWarnings("unchecked")
				List<Subfield> subfields = field.getSubfields();
				for (Subfield subfield : subfields) {
					if (subfield.getData().toLowerCase().contains("large type")) {
						return "LargePrint";
					}
				}
			}
		}

		List<DataField> localTopicalTerm = getDataFields(record, "690");
		if (localTopicalTerm != null) {
			Iterator<DataField> fieldsIterator = localTopicalTerm.iterator();
			DataField field;
			while (fieldsIterator.hasNext()) {
				field = fieldsIterator.next();
				Subfield subfieldA = field.getSubfield('a');
				if (subfieldA != null) {
					if (subfieldA.getData().toLowerCase().contains("seed library")) {
						return "SeedPacket";
					}
				}
			}
		}

		// check the 007 - this is a repeating field
		List<DataField> fields = getDataFields(record, "007");
		if (fields != null) {
			Iterator<DataField> fieldsIter = fields.iterator();
			ControlField formatField;
			while (fieldsIter.hasNext()) {
				formatField = (ControlField) fieldsIter.next();
				if (formatField.getData() == null || formatField.getData().length() < 2) {
					continue;
				}
				// Check for blu-ray (s in position 4)
				// This logic does not appear correct.
			/*
			 * if (formatField.getData() != null && formatField.getData().length()
			 * >= 4){ if (formatField.getData().toUpperCase().charAt(4) == 'S'){
			 * result.add("Blu-ray"); break; } }
			 */
				formatCode = formatField.getData().toUpperCase().charAt(0);
				switch (formatCode) {
					case 'A':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'D':
								return "Atlas";
							default:
								return "Map";
						}
					case 'C':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'A':
								return "TapeCartridge";
							case 'B':
								return "ChipCartridge";
							case 'C':
								return "DiscCartridge";
							case 'F':
								return "TapeCassette";
							case 'H':
								return "TapeReel";
							case 'J':
								return "FloppyDisk";
							case 'M':
							case 'O':
								return "CDROM";
							case 'R':
								// Do not return - this will cause anything with an
								// 856 field to be labeled as "Electronic"
								break;
							default:
								return "Software";
						}
						break;
					case 'D':
						return "Globe";
					case 'F':
						return "Braille";
					case 'G':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'C':
							case 'D':
								return "Filmstrip";
							case 'T':
								return "Transparency";
							default:
								return "Slide";
						}
					case 'H':
						return "Microfilm";
					case 'K':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'C':
								return "Collage";
							case 'D':
								return "Drawing";
							case 'E':
								return "Painting";
							case 'F':
								return "Print";
							case 'G':
								return "Photonegative";
							case 'J':
								return "Print";
							case 'L':
								return "Drawing";
							case 'O':
								return "FlashCard";
							case 'N':
								return "Chart";
							default:
								return "Photo";
						}
					case 'M':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'F':
								return "VideoCassette";
							case 'R':
								return "Filmstrip";
							default:
								return "MotionPicture";
						}
					case 'O':
						return "Kit";
					case 'Q':
						return "MusicalScore";
					case 'R':
						return "SensorImage";
					case 'S':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'D':
								if (formatField.getData().length() >= 4) {
									char speed = formatField.getData().toUpperCase().charAt(3);
									if (speed >= 'A' && speed <= 'E') {
										return "Phonograph";
									} else if (speed == 'F') {
										return "CompactDisc";
									} else if (speed >= 'K' && speed <= 'R') {
										return "TapeRecording";
									} else {
										return "SoundDisc";
									}
								} else {
									return "SoundDisc";
								}
							case 'S':
								return "SoundCassette";
							default:
								return "SoundRecording";
						}
					case 'T':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'A':
								return "Book";
							case 'B':
								return "LargePrint";
						}
					case 'V':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'C':
								return "VideoCartridge";
							case 'D':
								return "VideoDisc";
							case 'F':
								return "VideoCassette";
							case 'R':
								return "VideoReel";
							default:
								return "Video";
						}
				}
			}
		}

		// check the Leader at position 6
		if (leader.length() >= 6) {
			leaderBit = leader.charAt(6);
			switch (Character.toUpperCase(leaderBit)) {
				case 'C':
				case 'D':
					return "MusicalScore";
				case 'E':
				case 'F':
					return "Map";
				case 'G':
					// We appear to have a number of items without 007 tags marked as G's.
					// These seem to be Videos rather than Slides.
					// return "Slide");
					return "Video";
				case 'I':
					return "SoundRecording";
				case 'J':
					return "MusicRecording";
				case 'K':
					return "Photo";
				case 'M':
					return "Electronic";
				case 'O':
				case 'P':
					return "Kit";
				case 'R':
					return "PhysicalObject";
				case 'T':
					return "Manuscript";
			}
		}

		if (leader.length() >= 7) {
			// check the Leader at position 7
			leaderBit = leader.charAt(7);
			switch (Character.toUpperCase(leaderBit)) {
				// Monograph
				case 'M':
					return "Book";
				// Serial
				case 'S':
					// Look in 008 to determine what type of Continuing Resource
					if (fixedField != null) {
						formatCode = fixedField.getData().toUpperCase().charAt(21);
						switch (formatCode) {
							case 'N':
								return "Newspaper";
							case 'P':
								return "Journal";
							default:
								return "Serial";
						}
					}
			}
		}
		// Nothing worked!
		return "Unknown";
	}

	public static String convertISBN10to13(String isbn10){
		if (isbn10.length() != 10){
			return null;
		}
		String isbn = "978" + isbn10.substring(0, 9);
		//Calculate the 13 digit checksum
		int sumOfDigits = 0;
		for (int i = 0; i < 12; i++){
			int multiplier = 1;
			if (i % 2 == 1){
				multiplier = 3;
			}
			int curDigit = Integer.parseInt(Character.toString(isbn.charAt(i)));
			sumOfDigits += multiplier * curDigit;
		}
		int modValue = sumOfDigits % 10;
		int checksumDigit;
		if (modValue == 0){
			checksumDigit = 0;
		}else{
			checksumDigit = 10 - modValue;
		}
		return  isbn + Integer.toString(checksumDigit);
	}

	private static HashMap<String, String> formatsToGroupingCategory = new HashMap<String, String>();
	static {
		formatsToGroupingCategory.put("Atlas", "other");
		formatsToGroupingCategory.put("Map", "other");
		formatsToGroupingCategory.put("TapeCartridge", "other");
		formatsToGroupingCategory.put("ChipCartridge", "other");
		formatsToGroupingCategory.put("DiscCartridge", "other");
		formatsToGroupingCategory.put("TapeCassette", "other");
		formatsToGroupingCategory.put("TapeReel", "other");
		formatsToGroupingCategory.put("FloppyDisk", "other");
		formatsToGroupingCategory.put("CDROM", "other");
		formatsToGroupingCategory.put("Software", "other");
		formatsToGroupingCategory.put("Globe", "other");
		formatsToGroupingCategory.put("Braille", "book");
		formatsToGroupingCategory.put("Filmstrip", "movie");
		formatsToGroupingCategory.put("Transparency", "other");
		formatsToGroupingCategory.put("Slide", "other");
		formatsToGroupingCategory.put("Microfilm", "other");
		formatsToGroupingCategory.put("Collage", "other");
		formatsToGroupingCategory.put("Drawing", "other");
		formatsToGroupingCategory.put("Painting", "other");
		formatsToGroupingCategory.put("Print", "other");
		formatsToGroupingCategory.put("Photonegative", "other");
		formatsToGroupingCategory.put("FlashCard", "other");
		formatsToGroupingCategory.put("Chart", "other");
		formatsToGroupingCategory.put("Photo", "other");
		formatsToGroupingCategory.put("MotionPicture", "movie");
		formatsToGroupingCategory.put("Kit", "other");
		formatsToGroupingCategory.put("MusicalScore", "book");
		formatsToGroupingCategory.put("SensorImage", "other");
		formatsToGroupingCategory.put("SoundDisc", "audio");
		formatsToGroupingCategory.put("SoundCassette", "audio");
		formatsToGroupingCategory.put("SoundRecording", "audio");
		formatsToGroupingCategory.put("VideoCartridge", "movie");
		formatsToGroupingCategory.put("VideoDisc", "movie");
		formatsToGroupingCategory.put("VideoCassette", "movie");
		formatsToGroupingCategory.put("VideoReel", "movie");
		formatsToGroupingCategory.put("Video", "movie");
		formatsToGroupingCategory.put("MusicalScore", "book");
		formatsToGroupingCategory.put("MusicRecording", "music");
		formatsToGroupingCategory.put("Electronic", "other");
		formatsToGroupingCategory.put("PhysicalObject", "other");
		formatsToGroupingCategory.put("Manuscript", "book");
		formatsToGroupingCategory.put("eBook", "ebook");
		formatsToGroupingCategory.put("Book", "book");
		formatsToGroupingCategory.put("Newspaper", "book");
		formatsToGroupingCategory.put("Journal", "book");
		formatsToGroupingCategory.put("Serial", "book");
		formatsToGroupingCategory.put("Unknown", "other");
		formatsToGroupingCategory.put("Playaway", "audio");
		formatsToGroupingCategory.put("LargePrint", "book");
		formatsToGroupingCategory.put("Blu-ray", "movie");
		formatsToGroupingCategory.put("DVD", "movie");
		formatsToGroupingCategory.put("VerticalFile", "other");
		formatsToGroupingCategory.put("CompactDisc", "audio");
		formatsToGroupingCategory.put("TapeRecording", "audio");
		formatsToGroupingCategory.put("Phonograph", "audio");
		formatsToGroupingCategory.put("pdf", "ebook");
		formatsToGroupingCategory.put("epub", "ebook");
		formatsToGroupingCategory.put("jpg", "other");
		formatsToGroupingCategory.put("gif", "other");
		formatsToGroupingCategory.put("mp3", "audio");
		formatsToGroupingCategory.put("plucker", "ebook");
		formatsToGroupingCategory.put("kindle", "ebook");
		formatsToGroupingCategory.put("externalLink", "ebook");
		formatsToGroupingCategory.put("externalMP3", "audio");
		formatsToGroupingCategory.put("interactiveBook", "ebook");
		formatsToGroupingCategory.put("overdrive", "ebook");
		formatsToGroupingCategory.put("external_web", "ebook");
		formatsToGroupingCategory.put("external_ebook", "ebook");
		formatsToGroupingCategory.put("external_eaudio", "audio");
		formatsToGroupingCategory.put("external_emusic", "music");
		formatsToGroupingCategory.put("external_evideo", "movie");
		formatsToGroupingCategory.put("text", "ebook");
		formatsToGroupingCategory.put("gifs", "other");
		formatsToGroupingCategory.put("itunes", "audio");
		formatsToGroupingCategory.put("Adobe_EPUB_eBook", "ebook");
		formatsToGroupingCategory.put("Kindle_Book", "ebook");
		formatsToGroupingCategory.put("Microsoft_eBook", "ebook");
		formatsToGroupingCategory.put("OverDrive_WMA_Audiobook", "audio");
		formatsToGroupingCategory.put("OverDrive_MP3_Audiobook", "audio");
		formatsToGroupingCategory.put("OverDrive_Music", "music");
		formatsToGroupingCategory.put("OverDrive_Video", "movie");
		formatsToGroupingCategory.put("OverDrive_Read", "ebook");
		formatsToGroupingCategory.put("Adobe_PDF_eBook", "ebook");
		formatsToGroupingCategory.put("Palm", "ebook");
		formatsToGroupingCategory.put("Mobipocket_eBook", "ebook");
		formatsToGroupingCategory.put("Disney_Online_Book", "ebook");
		formatsToGroupingCategory.put("Open_PDF_eBook", "ebook");
		formatsToGroupingCategory.put("Open_EPUB_eBook", "ebook");
		formatsToGroupingCategory.put("eContent", "ebook");
		formatsToGroupingCategory.put("SeedPacket", "other");
	}

	private static HashMap<String, String> categoryMap = new HashMap<String, String>();
	static {
		categoryMap.put("other", "book");
		categoryMap.put("book", "book");
		categoryMap.put("ebook", "book");
		categoryMap.put("audio", "book");
		categoryMap.put("music", "music");
		categoryMap.put("movie", "movie");
	}


	public void dumpStats() {
		long totalElapsedTime = new Date().getTime() - startTime;
		long totalElapsedMinutes = totalElapsedTime / (60 * 1000);
		logger.debug("-----------------------------------------------------------");
		logger.debug("Processed " + numRecordsProcessed + " records in " + totalElapsedMinutes + " minutes");
		logger.debug("Created a total of " + numGroupedWorksAdded + " grouped works");
	}

	public void deletePrimaryIdentifier(RecordIdentifier primaryIdentifier) {
		if (fullRegrouping) return;
		try {
			//Delete the previous primary identifiers as needed
			removePrimaryIdentifierStmt.setString(1, primaryIdentifier.getType());
			removePrimaryIdentifierStmt.setString(2, primaryIdentifier.getIdentifier());
			removePrimaryIdentifierStmt.executeUpdate();

			//Also remove the links to the secondary identifiers
			removeIdentifiersForPrimaryIdentifierStmt.setLong(1, primaryIdentifier.getIdentifierId());
			removeIdentifiersForPrimaryIdentifierStmt.executeUpdate();
		} catch (SQLException e) {
			logger.error("Error removing primary identifier from old grouped works " + primaryIdentifier.toString(), e);
		}
	}

	private void loadTranslationMaps(String serverName){
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
		String translatedValue;
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
						logger.warn("Could not translate '" + concatenatedValue + "'");
						unableToTranslateWarnings.add(concatenatedValue);
					}
					translatedValue = value;
				}
			}
		}
		if (translatedValue != null){
			translatedValue = translatedValue.trim();
			if (translatedValue.length() == 0){
				translatedValue = null;
			}
		}
		return translatedValue;
	}
}
