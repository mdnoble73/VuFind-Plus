package org.vufind;

import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrServer;
import org.ini4j.Ini;

import java.io.*;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.apache.log4j.Logger;

/**
 * Indexes records extracted from the ILS
 *
 * VuFind-Plus
 * User: Mark Noble
 * Date: 11/25/13
 * Time: 2:26 PM
 */
public class GroupedWorkIndexer {
	private String serverName;
	private String solrPort;
	private Logger logger;
	private ConcurrentUpdateSolrServer updateServer;
	private IlsRecordProcessor ilsRecordProcessor;
	private OverDriveProcessor overDriveProcessor;
	private HashMap<String, HashMap<String, String>> translationMaps = new HashMap<String, HashMap<String, String>>();
	private HashMap<String, LexileTitle> lexileInformation = new HashMap<String, LexileTitle>();

	private PreparedStatement getRatingStmt;
	private Connection vufindConn;

	protected int availableAtLocationBoostValue = 50;
	protected int ownedByLocationBoostValue = 10;

	private boolean fullReindex = false;
	private long lastReindexTime;
	private Long lastReindexTimeVariableId;

	private HashSet<String> worksWithInvalidLiteraryForms = new HashSet<String>();

	public GroupedWorkIndexer(String serverName, Connection vufindConn, Connection econtentConn, Ini configIni, boolean fullReindex, Logger logger) {
		this.serverName = serverName;
		this.logger = logger;
		this.vufindConn = vufindConn;
		this.fullReindex = fullReindex;
		solrPort = configIni.get("Reindex", "solrPort");

		availableAtLocationBoostValue = Integer.parseInt(configIni.get("Reindex", "availableAtLocationBoostValue"));
		ownedByLocationBoostValue = Integer.parseInt(configIni.get("Reindex", "ownedByLocationBoostValue"));

		//Load the last Index time
		try{
			PreparedStatement loadLastGroupingTime = vufindConn.prepareStatement("SELECT * from variables WHERE name = 'last_grouping_time'");
			ResultSet lastGroupingTimeRS = loadLastGroupingTime.executeQuery();
			if (lastGroupingTimeRS.next()){
				lastReindexTime = lastGroupingTimeRS.getLong("value");
				lastReindexTimeVariableId = lastGroupingTimeRS.getLong("id");
			}
			lastGroupingTimeRS.close();
			loadLastGroupingTime.close();
		} catch (Exception e){
			logger.error("Could not load last index time from variables table ", e);
		}

		String ilsIndexingClassString = configIni.get("Reindex", "ilsIndexingClass");
		if (ilsIndexingClassString.equals("Marmot")){
			ilsRecordProcessor = new MarmotRecordProcessor(this, vufindConn, econtentConn, configIni, logger);
		}else if(ilsIndexingClassString.equals("Nashville")){
			ilsRecordProcessor = new NashvilleRecordProcessor(this, vufindConn, configIni, logger);
		}else if(ilsIndexingClassString.equals("WCPL")){
			ilsRecordProcessor = new WCPLRecordProcessor(this, vufindConn, configIni, logger);
		}
		overDriveProcessor = new OverDriveProcessor(this, vufindConn, econtentConn, configIni, fullReindex, logger);

		//Initialize the updateServer
		if (fullReindex){
			updateServer = new ConcurrentUpdateSolrServer("http://localhost:" + solrPort + "/solr/grouped2", 5000, 10);
		}else{
			updateServer = new ConcurrentUpdateSolrServer("http://localhost:" + solrPort + "/solr/grouped", 5000, 10);
		}

		//Load translation maps
		loadTranslationMaps();

		//Setup prepared statements to load local enrichment
		try {
			getRatingStmt = vufindConn.prepareStatement("SELECT AVG(rating) as averageRating from user_work_review where groupedRecordPermanentId = ? and rating > 0");
		} catch (SQLException e) {
			logger.error("Could not prepare statements to load local enrichment", e);
		}

		String lexileExportPath = configIni.get("Reindex", "lexileExportPath");
		loadLexileData(lexileExportPath);

		if (fullReindex){
			clearIndex();
		}
	}

	private void loadLexileData(String lexileExportPath) {
		try{
			File lexileData = new File(lexileExportPath);
			BufferedReader lexileReader = new BufferedReader(new FileReader(lexileData));
			//Skip over the header
			lexileReader.readLine();
			String lexileLine = lexileReader.readLine();
			while (lexileLine != null){
				String[] lexileFields = lexileLine.split("\\t");
				LexileTitle titleInfo = new LexileTitle();
				if (lexileFields.length >= 11){
					titleInfo.setTitle(lexileFields[0]);
					titleInfo.setAuthor(lexileFields[1]);
					String isbn = lexileFields[3];
					titleInfo.setLexileCode(lexileFields[4]);
					titleInfo.setLexileScore(lexileFields[5]);
					titleInfo.setSeries(lexileFields[9]);
					titleInfo.setAwards(lexileFields[10]);
					titleInfo.setDescription(lexileFields[11]);
					lexileInformation.put(isbn, titleInfo);
				}
				lexileLine = lexileReader.readLine();
			}
			logger.info("Read " + lexileInformation.size() + " lines of lexile data");
		}catch (Exception e){
			logger.error("Error loading lexile data", e);
		}
	}

	private void clearIndex() {
		//Check to see if we should clear the existing index
		logger.info("Clearing existing marc records from index");
		try {
			updateServer.deleteByQuery("recordtype:grouped_work", 10);
			updateServer.commit(true, true);
		} catch (Exception e) {
			logger.error("Error deleting from index", e);
		}
	}

	public void finishIndexing(){
		try {
			updateServer.commit(true, true);
		} catch (Exception e) {
			logger.error("Error calling final commit", e);
		}
		try {
			//Optimize to trigger improve performance
			updateServer.optimize(true, true);
		} catch (Exception e) {
			logger.error("Error optimizing index", e);
		}
		try {
			updateServer.shutdown();
		} catch (Exception e) {
			logger.error("Error shutting down update server", e);
		}
		//Swap the indexes
		if (fullReindex)  {
			try {
				Util.getURL("http://localhost:" + solrPort + "/solr/admin/cores?action=SWAP&core=grouped2&other=grouped", logger);
			} catch (Exception e) {
				logger.error("Error shutting down update server", e);
			}
		}
		writeWorksWithInvalidLiteraryForms();
		updateLastReindexTime();
	}

	private void writeWorksWithInvalidLiteraryForms() {
		File worksWithInvalidLiteraryFormsFile = new File ("/var/log/vufind-plus/" + serverName + "/worksWithInvalidLiteraryForms.txt");
		try{
			FileWriter writer = new FileWriter(worksWithInvalidLiteraryFormsFile, false);
			logger.warn("Found " + worksWithInvalidLiteraryForms.size() + " grouped works with invalid literary forms\r\n");
			writer.write("Found " + worksWithInvalidLiteraryForms.size() + " grouped works with invalid literary forms\r\n");
			writer.write("Works with inconsistent literary forms\r\n");
			for (String curId : worksWithInvalidLiteraryForms){
				writer.write(curId + "\r\n");
			}
		}catch(Exception e){
			logger.error("Error writing works with invalid literary forms", e);
		}
	}

	private void updateLastReindexTime() {
		//Update the last grouping time in the variables table
		try{
			Long finishTime = new Date().getTime() / 1000;
			if (lastReindexTimeVariableId != null){
				PreparedStatement updateVariableStmt  = vufindConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
				updateVariableStmt.setLong(1, finishTime);
				updateVariableStmt.setLong(2, lastReindexTimeVariableId);
				updateVariableStmt.executeUpdate();
				updateVariableStmt.close();
			} else{
				PreparedStatement insertVariableStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('last_grouping_time', ?)");
				insertVariableStmt.setString(1, Long.toString(finishTime));
				insertVariableStmt.executeUpdate();
				insertVariableStmt.close();
			}
		}catch (Exception e){
			logger.error("Error setting last grouping time", e);
		}
	}

	public void processGroupedWorks() {
		int numWorksProcessed = 0;
		try {
			PreparedStatement getAllGroupedWorks;
			if (fullReindex){
				getAllGroupedWorks = vufindConn.prepareStatement("SELECT * FROM grouped_work", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			}else{
				//Load all grouped works that have changed since the last time the index ran
				getAllGroupedWorks = vufindConn.prepareStatement("SELECT * FROM grouped_work WHERE date_updated > ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
				getAllGroupedWorks.setLong(1, lastReindexTime);
			}
			PreparedStatement getGroupedWorkPrimaryIdentifiers = vufindConn.prepareStatement("SELECT * FROM grouped_work_primary_identifiers where grouped_work_id = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			PreparedStatement getGroupedWorkIdentifiers = vufindConn.prepareStatement("SELECT * FROM grouped_work_identifiers inner join grouped_work_identifiers_ref on identifier_id = grouped_work_identifiers.id where grouped_work_id = ? and valid_for_enrichment = 1", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			ResultSet groupedWorks = getAllGroupedWorks.executeQuery();
			while (groupedWorks.next()){
				Long id = groupedWorks.getLong("id");
				String permanentId = groupedWorks.getString("permanent_id");
				String grouping_category = groupedWorks.getString("grouping_category");

				//Create a solr record for the grouped work
				GroupedWorkSolr groupedWork = new GroupedWorkSolr(this, logger);
				groupedWork.setId(permanentId);
				groupedWork.setGroupingCategory(grouping_category);

				getGroupedWorkPrimaryIdentifiers.setLong(1, id);
				ResultSet groupedWorkPrimaryIdentifiers = getGroupedWorkPrimaryIdentifiers.executeQuery();
				while (groupedWorkPrimaryIdentifiers.next()){
					String type = groupedWorkPrimaryIdentifiers.getString("type");
					String identifier = groupedWorkPrimaryIdentifiers.getString("identifier");
					//This does the bulk of the work building fields for the solr document
					updateGroupedWorkForPrimaryIdentifier(groupedWork, type, identifier);
				}

				//Update the grouped record based on data for each work
				getGroupedWorkIdentifiers.setLong(1, id);
				ResultSet groupedWorkIdentifiers = getGroupedWorkIdentifiers.executeQuery();
				//This just adds isbns, issns, upcs, and oclc numbers to the index
				while (groupedWorkIdentifiers.next()){
					String type = groupedWorkIdentifiers.getString("type");
					String identifier = groupedWorkIdentifiers.getString("identifier");
					updateGroupedWorkForSecondaryIdentifier(groupedWork, type, identifier);
				}

				//Load local (VuFind) enrichment for the work
				loadLocalEnrichment(groupedWork);
				//Load lexile data for the work
				loadLexileDataForWork(groupedWork);

				//Write the record to Solr.
				try{
					updateServer.add(groupedWork.getSolrDocument(availableAtLocationBoostValue, ownedByLocationBoostValue));
				}catch (Exception e){
					logger.error("Error adding record to solr", e);
				}
				numWorksProcessed++;
				if (numWorksProcessed % 5000 == 0){
					commitChanges();
					//logger.info("Processed " + numWorksProcessed + " grouped works processed.");
				}
			}
		} catch (SQLException e) {
			logger.error("Unexpected SQL error", e);
		}
		logger.warn("Finished processing grouped works.  Processed a total of " + numWorksProcessed + " grouped works");
	}

	private void loadLexileDataForWork(GroupedWorkSolr groupedWork) {
		for(String isbn : groupedWork.getIsbns()){
			if (lexileInformation.containsKey(isbn)){
				LexileTitle lexileTitle = lexileInformation.get(isbn);
				String lexileCode = lexileTitle.getLexileCode();
				if (lexileCode.length() > 0){
					groupedWork.setLexileCode(this.translateValue("lexile_code", lexileCode));
				}
				groupedWork.setLexileScore(lexileTitle.getLexileScore());
				groupedWork.addAwards(lexileTitle.getAwards());
				if (lexileTitle.getSeries().length() > 0){
					groupedWork.addSeries(lexileTitle.getSeries());
				}
				break;
			}
		}
	}

	private void commitChanges() {
		try {
			updateServer.commit(true, true);
		} catch (SolrServerException e) {
			logger.error("Error updating solr", e);
		} catch (IOException e) {
			logger.error("Error updating solr", e);
		}
	}

	private void loadLocalEnrichment(GroupedWorkSolr groupedWork) {
		//Load rating
		try{
			getRatingStmt.setString(1, groupedWork.getId());
			ResultSet ratingsRS = getRatingStmt.executeQuery();
			if (ratingsRS.next()){
				Float averageRating = ratingsRS.getFloat("averageRating");
				if (averageRating != null){
					groupedWork.setRating(averageRating);
				}
			}
		}catch (Exception e){
			logger.error("Unable to load local enrichment", e);
		}
	}

	private void updateGroupedWorkForPrimaryIdentifier(GroupedWorkSolr groupedWork, String type, String identifier) {
		groupedWork.addAlternateId(identifier);
		type = type.toLowerCase();
		if (type.equals("ils")){
			//Get the ils record from the individual marc records
			ilsRecordProcessor.processRecord(groupedWork, identifier);
		}else if (type.equals("overdrive")){
			overDriveProcessor.processRecord(groupedWork, identifier);
		}else{
			logger.warn("Unknown identifier type " + type);
		}
	}

	private void updateGroupedWorkForSecondaryIdentifier(GroupedWorkSolr groupedWork, String type, String identifier) {
		type = type.toLowerCase();
		if (type.equals("isbn")){
			groupedWork.addIsbn(identifier);
		}else if (type.equals("upc")){
			groupedWork.addUpc(identifier);
		}else if (type.equals("issn")){
			groupedWork.addIssn(identifier);
		}else if (type.equals("oclc")){
			groupedWork.addOclc(identifier);
		}else if (type.equals("order")){
			//Add as an alternate id
			groupedWork.addAlternateId(identifier);
		}else{
			logger.warn("Unknown identifier type " + type);
		}
	}

	private void loadTranslationMaps(){
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

	public HashSet<String> translateCollection(String mapName, Set<String> values) {
		HashSet<String> translatedCollection = new HashSet<String>();
		for (String value : values){
			translatedCollection.add(translateValue(mapName, value));
		}
		return  translatedCollection;
	}

	private final static Pattern FOUR_DIGIT_PATTERN_BRACES							= Pattern.compile("\\[[12]\\d{3}\\]");
	private final static Pattern				FOUR_DIGIT_PATTERN_ONE_BRACE					= Pattern.compile("\\[[12]\\d{3}");
	private final static Pattern				FOUR_DIGIT_PATTERN_STARTING_WITH_1_2	= Pattern.compile("(20|19|18|17|16|15)[0-9][0-9]");
	private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_1						= Pattern.compile("l\\d{3}");
	private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_2						= Pattern.compile("\\[19\\]\\d{2}");
	private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_3						= Pattern.compile("(20|19|18|17|16|15)[0-9][-?0-9]");
	private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_4						= Pattern.compile("i.e. (20|19|18|17|16|15)[0-9][0-9]");
	private final static Pattern				BC_DATE_PATTERN												= Pattern.compile("[0-9]+ [Bb][.]?[Cc][.]?");

	/**
	 * Cleans non-digits from a String
	 *
	 * @param date
	 *          String to parse
	 * @return Numeric part of date String (or null)
	 */
	public static String cleanDate(final String date) {
		if (date == null || date.length() == 0){
			return null;
		}
		Matcher matcher_braces = FOUR_DIGIT_PATTERN_BRACES.matcher(date);

		String cleanDate = null; // raises DD-anomaly

		if (matcher_braces.find()) {
			cleanDate = matcher_braces.group();
			cleanDate = removeOuterBrackets(cleanDate);
		} else{
			Matcher matcher_ie_date = FOUR_DIGIT_PATTERN_OTHER_4.matcher(date);
			if (matcher_ie_date.find()) {
				cleanDate = matcher_ie_date.group().replaceAll("i.e. ", "");
			} else {
				Matcher matcher_one_brace = FOUR_DIGIT_PATTERN_ONE_BRACE.matcher(date);
				if (matcher_one_brace.find()) {
					cleanDate = matcher_one_brace.group();
					cleanDate = removeOuterBrackets(cleanDate);
				} else {
					Matcher matcher_bc_date = BC_DATE_PATTERN.matcher(date);
					if (matcher_bc_date.find()) {
						cleanDate = null;
					} else {
						Matcher matcher_start_with_1_2 = FOUR_DIGIT_PATTERN_STARTING_WITH_1_2.matcher(date);
						if (matcher_start_with_1_2.find()) {
							cleanDate = matcher_start_with_1_2.group();
						} else {
							Matcher matcher_l_plus_three_digits = FOUR_DIGIT_PATTERN_OTHER_1.matcher(date);
							if (matcher_l_plus_three_digits.find()) {
								cleanDate = matcher_l_plus_three_digits.group().replaceAll("l", "1");
							} else {
								Matcher matcher_bracket_19_plus_two_digits = FOUR_DIGIT_PATTERN_OTHER_2.matcher(date);
								if (matcher_bracket_19_plus_two_digits.find()) {
									cleanDate = matcher_bracket_19_plus_two_digits.group().replaceAll("\\[", "").replaceAll("\\]", "");
								} else{
									Matcher matcher_three_digits_plus_unk = FOUR_DIGIT_PATTERN_OTHER_3.matcher(date);
									if (matcher_three_digits_plus_unk.find()) {
										cleanDate = matcher_three_digits_plus_unk.group().replaceAll("[-?]", "0");
									}
								}
							}
						}
					}
				}
			}
		}
		if (cleanDate != null) {
			Calendar calendar = Calendar.getInstance();
			SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy");
			String thisYear = dateFormat.format(calendar.getTime());
			try {
				if (Integer.parseInt(cleanDate) > Integer.parseInt(thisYear) + 1) cleanDate = null;
			} catch (NumberFormatException nfe) {
				cleanDate = null;
			}
		}
		return cleanDate;
	}

	/**
	 * Remove single square bracket characters if they are the start and/or end
	 * chars (matched or unmatched) and are the only square bracket chars in the
	 * string.
	 */
	public static String removeOuterBrackets(String origStr) {
		if (origStr == null || origStr.length() == 0) return origStr;

		String result = origStr.trim();

		if (result.length() > 0) {
			boolean openBracketFirst = result.charAt(0) == '[';
			boolean closeBracketLast = result.endsWith("]");
			if (openBracketFirst && closeBracketLast && result.indexOf('[', 1) == -1 && result.lastIndexOf(']', result.length() - 2) == -1)
				// only square brackets are at beginning and end
				result = result.substring(1, result.length() - 1);
			else if (openBracketFirst && result.indexOf(']') == -1)
				// starts with '[' but no ']'; remove open bracket
				result = result.substring(1);
			else if (closeBracketLast && result.indexOf('[') == -1)
				// ends with ']' but no '['; remove close bracket
				result = result.substring(0, result.length() - 1);
		}

		return result.trim();
	}

	public void processPublicUserLists() {
		//TODO:  Add public lists to the index
	}

	public void addWorkWithInvalidLiteraryForms(String id) {
		this.worksWithInvalidLiteraryForms.add(id);
	}
}
