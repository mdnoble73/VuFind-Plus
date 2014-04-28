package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.marc.*;
import org.solrmarc.tools.Utils;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileReader;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.regex.PatternSyntaxException;

/**
 * Processes data that was exported from the ILS.
 *
 * VuFind-Plus
 * User: Mark Noble
 * Date: 11/26/13
 * Time: 9:30 AM
 */
public abstract class IlsRecordProcessor {
	private String individualMarcPath;
	protected Logger logger;
	protected GroupedWorkIndexer indexer;

	protected String recordNumberTag;
	protected String itemTag;
	protected char barcodeSubfield;
	protected char statusSubfieldIndicator;
	protected char dueDateSubfield;
	protected char dateCreatedSubfield;
	protected char locationSubfieldIndicator;
	protected char iTypeSubfield;
	protected boolean useEContentSubfield = false;
	protected char eContentSubfield;
	protected char lastYearCheckoutSubfield;
	protected char ytdCheckoutSubfield;
	protected char totalCheckoutSubfield;
	protected boolean useICode2Suppression;
	protected char iCode2Subfield;
	protected String[] additionalCollections;
	protected char callNumberPrestampSubfield;
	protected char callNumberSubfield;
	protected char callNumberCutterSubfield;

	private static boolean libraryAndLocationDataLoaded = false;
	protected static HashMap<String, String> libraryMap = new HashMap<String, String>();
	protected static HashMap<String, String> locationMap = new HashMap<String, String>();
	protected static HashMap<String, String> subdomainMap = new HashMap<String, String>();

	private static boolean loanRuleDataLoaded = false;
	private static ArrayList<Long> pTypes = new ArrayList<Long>();
	private static HashMap<Long, LoanRule> loanRules = new HashMap<Long, LoanRule>();
	private static ArrayList<LoanRuleDeterminer> loanRuleDeterminers = new ArrayList<LoanRuleDeterminer>();

	private static boolean availabilityDataLoaded = false;
	private static boolean getAvailabilityFromMarc = true;
	private static TreeSet<String> availableItemBarcodes = new TreeSet<String>();

	public IlsRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, Logger logger) {
		String marcRecordPath = configIni.get("Reindex", "marcPath");
		individualMarcPath = configIni.get("Reindex", "individualMarcPath");
		this.logger = logger;
		this.indexer = indexer;

		itemTag = configIni.get("Reindex", "itemTag");
		recordNumberTag = configIni.get("Reindex", "recordNumberTag");
		useEContentSubfield = Boolean.parseBoolean(configIni.get("Reindex", "useEContentSubfield"));
		eContentSubfield = getSubfieldIndicatorFromConfig(configIni, "eContentSubfield");
		barcodeSubfield = getSubfieldIndicatorFromConfig(configIni, "barcodeSubfield");
		statusSubfieldIndicator = getSubfieldIndicatorFromConfig(configIni, "statusSubfield");
		dueDateSubfield = getSubfieldIndicatorFromConfig(configIni, "dueDateSubfield");
		locationSubfieldIndicator = getSubfieldIndicatorFromConfig(configIni, "locationSubfield");
		iTypeSubfield = getSubfieldIndicatorFromConfig(configIni, "iTypeSubfield");
		dateCreatedSubfield = getSubfieldIndicatorFromConfig(configIni, "dateCreatedSubfield");
		lastYearCheckoutSubfield = getSubfieldIndicatorFromConfig(configIni, "lastYearCheckoutSubfield");
		ytdCheckoutSubfield = getSubfieldIndicatorFromConfig(configIni, "ytdCheckoutSubfield");
		totalCheckoutSubfield = getSubfieldIndicatorFromConfig(configIni, "totalCheckoutSubfield");
		useICode2Suppression = Boolean.parseBoolean(configIni.get("Reindex", "useICode2Suppression"));
		iCode2Subfield = getSubfieldIndicatorFromConfig(configIni, "iCode2Subfield");
		callNumberPrestampSubfield = getSubfieldIndicatorFromConfig(configIni, "callNumberPrestampSubfield");
		callNumberSubfield = getSubfieldIndicatorFromConfig(configIni, "callNumberSubfield");
		callNumberCutterSubfield = getSubfieldIndicatorFromConfig(configIni, "callNumberCutterSubfield");

		String additionalCollectionsString = configIni.get("Reindex", "additionalCollections");
		if (additionalCollections != null){
			additionalCollections = additionalCollectionsString.split(",");
		}

		loadSystemAndLocationData(vufindConn, logger);

		loadAvailableItemBarcodes(marcRecordPath, logger);
		loadLoanRuleInformation(vufindConn, logger);
	}

	private static void loadSystemAndLocationData(Connection vufindConn, Logger logger) {
		if (!libraryAndLocationDataLoaded){
			//Setup translation maps for system and location
			try {
				PreparedStatement libraryInformationStmt = vufindConn.prepareStatement("SELECT ilsCode, subdomain, facetLabel FROM library", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
				ResultSet libraryInformationRS = libraryInformationStmt.executeQuery();
				while (libraryInformationRS.next()){
					String code = libraryInformationRS.getString("ilsCode");
					String facetLabel = libraryInformationRS.getString("facetLabel");
					String subdomain = libraryInformationRS.getString("subdomain");
					libraryMap.put(code, facetLabel);
					subdomainMap.put(code, subdomain);
				}

				PreparedStatement locationInformationStmt = vufindConn.prepareStatement("SELECT code, facetLabel FROM location", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
				ResultSet locationInformationRS = locationInformationStmt.executeQuery();
				while (locationInformationRS.next()){
					String code = locationInformationRS.getString("code");
					String facetLabel = locationInformationRS.getString("facetLabel");
					locationMap.put(code, facetLabel);
				}
			} catch (SQLException e) {
				logger.error("Error setting up system maps", e);
			}
			libraryAndLocationDataLoaded = true;
		}
	}

	private static void loadLoanRuleInformation(Connection vufindConn, Logger logger) {
		if (!loanRuleDataLoaded){
			//Load loan rules
			try {
				PreparedStatement pTypesStmt = vufindConn.prepareStatement("SELECT pType from ptype", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet pTypesRS = pTypesStmt.executeQuery();
				while (pTypesRS.next()) {
					pTypes.add(pTypesRS.getLong("pType"));
				}

				PreparedStatement loanRuleStmt = vufindConn.prepareStatement("SELECT * from loan_rules", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet loanRulesRS = loanRuleStmt.executeQuery();
				while (loanRulesRS.next()) {
					LoanRule loanRule = new LoanRule();
					loanRule.setLoanRuleId(loanRulesRS.getLong("loanRuleId"));
					loanRule.setName(loanRulesRS.getString("name"));
					loanRule.setHoldable(loanRulesRS.getBoolean("holdable"));

					loanRules.put(loanRule.getLoanRuleId(), loanRule);
				}
				logger.debug("Loaded " + loanRules.size() + " loan rules");

				PreparedStatement loanRuleDeterminersStmt = vufindConn.prepareStatement("SELECT * from loan_rule_determiners where active = 1 order by rowNumber DESC", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet loanRuleDeterminersRS = loanRuleDeterminersStmt.executeQuery();
				while (loanRuleDeterminersRS.next()) {
					LoanRuleDeterminer loanRuleDeterminer = new LoanRuleDeterminer();
					loanRuleDeterminer.setLocation(loanRuleDeterminersRS.getString("location"));
					loanRuleDeterminer.setPatronType(loanRuleDeterminersRS.getString("patronType"));
					loanRuleDeterminer.setItemType(loanRuleDeterminersRS.getString("itemType"));
					loanRuleDeterminer.setLoanRuleId(loanRuleDeterminersRS.getLong("loanRuleId"));
					loanRuleDeterminer.setRowNumber(loanRuleDeterminersRS.getLong("rowNumber"));

					loanRuleDeterminers.add(loanRuleDeterminer);
				}

				logger.debug("Loaded " + loanRuleDeterminers.size() + " loan rule determiner");
			} catch (SQLException e) {
				logger.error("Unable to load loan rules", e);
			}
			loanRuleDataLoaded = true;
		}
	}

	private static void loadAvailableItemBarcodes(String marcRecordPath, Logger logger) {
		if (!availabilityDataLoaded){
			File availableItemsFile = new File(marcRecordPath + "/available_items.csv");
			if (!availableItemsFile.exists()){
				return;
			}
			File checkoutsFile = new File(marcRecordPath + "/checkouts.csv");
			try{
				logger.debug("Loading availability for barcodes");
				getAvailabilityFromMarc = false;
				BufferedReader availableItemsReader = new BufferedReader(new FileReader(availableItemsFile));
				String availableBarcode;
				while ((availableBarcode = availableItemsReader.readLine()) != null){
					if (availableBarcode.length() > 0){
						availableItemBarcodes.add(Util.cleanIniValue(availableBarcode).trim());
					}
				}
				availableItemsReader.close();
				logger.info("Found a total of " + availableItemBarcodes.size() + " barcodes that are available");

				//Remove any items that were checked out
				logger.debug("removing availability for checked out barcodes");
				BufferedReader checkoutsReader = new BufferedReader(new FileReader(checkoutsFile));
				String checkedOutBarcode;
				while ((checkedOutBarcode = checkoutsReader.readLine()) != null){
					availableItemBarcodes.remove(Util.cleanIniValue(checkedOutBarcode));
				}
				checkoutsReader.close();
				logger.info("After removing checked out barcodes, there were a total of " + availableItemBarcodes.size() + " barcodes that are available");

			}catch(Exception e){
				logger.error("Error loading available items", e);
			}
			availabilityDataLoaded = true;
		}
	}

	public void processRecord(GroupedWorkSolr groupedWork, String identifier){
		String shortId = identifier.replace(".", "");
		String firstChars = shortId.substring(0, 4);
		String basePath = individualMarcPath + "/" + firstChars;
		String individualFilename = basePath + "/" + shortId + ".mrc";
		File individualFile = new File(individualFilename);
		if (individualFile.exists()){
			try {
				FileInputStream inputStream = new FileInputStream(individualFile);
				MarcPermissiveStreamReader marcReader = new MarcPermissiveStreamReader(inputStream, true, true, "UTF-8");
				if (marcReader.hasNext()){
					try{
						Record record = marcReader.next();
						updateGroupedWorkSolrDataBasedOnMarc(groupedWork, record, identifier);
					}catch (Exception e) {
						logger.error("Error updating solr based on marc record", e);
					}
				}
				inputStream.close();
			} catch (Exception e) {
				logger.error("Error reading data from ils file " + individualFile.toString(), e);
			}
		}else{
			logger.warn("Did not find marc record for related record " + identifier);
		}
		//We didn't get a marc record, skip this record.
	}

	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		try{
			List<DataField> unsuppressedItems = getUnsuppressedItems(record);

			loadRecordType(groupedWork, record);

			//Do updates based on the overall bib
			loadTitles(groupedWork, record);
			loadAuthors(groupedWork, record);

			loadFormatDetails(groupedWork, record);

			groupedWork.addTopic(getFieldList(record, "600abcdefghjklmnopqrstuvxyz:610abcdefghjklmnopqrstuvxyz:611acdefghklnpqstuvxyz:630abfghklmnoprstvxyz:650abcdevxyz:651abcdevxyz:690a"));
			groupedWork.addTopicFacet(getFieldList(record, "600a:600x:600a:610x:611x:611x:630a:630x:648x:650a:650x:651x:655x"));
			groupedWork.addSeries(getFieldList(record, "440ap:800pqt:830ap"));
			groupedWork.addSeries2(getFieldList(record, "490a"));
			groupedWork.addPhysical(getFieldList(record, "300abcefg:530abcd"));
			groupedWork.addDateSpan(getFieldList(record, "362a"));
			groupedWork.addEditions(getFieldList(record, "250a"));
			groupedWork.addContents(getFieldList(record, "505a:505t"));
			groupedWork.addGenre(getFieldList(record, "655abcvxyz"));
			groupedWork.addGenreFacet(getFieldList(record, "600v:610v:611v:630v:648v:650v:651v:655a:655v"));
			groupedWork.addGeographic(getFieldList(record, "651avxyz"));
			groupedWork.addGeographicFacet(getFieldList(record, "600z:610z:611z:630z:648z:650z:651a:651z:655z"));
			groupedWork.addEra(getFieldList(record, "600d:610y:611y:630y:648a:648y:650y:651y:655y"));
			groupedWork.addContents(getFieldList(record, "505a:505t"));

			loadCallNumbers(groupedWork, record);
			loadLanguageDetails(groupedWork, record);
			loadPublicationDetails(groupedWork, record);
			loadLiteraryForms(groupedWork, record);
			loadTargetAudiences(groupedWork, record);
			groupedWork.addMpaaRating(groupedWork, getMpaaRating(record));

			//Do updates based on items
			loadOwnershipInformation(groupedWork, unsuppressedItems);
			loadAvailability(groupedWork, unsuppressedItems);
			loadUsability(groupedWork, unsuppressedItems);
			loadPopularity(groupedWork, unsuppressedItems);
			loadDateAdded(groupedWork, unsuppressedItems);
			loadITypes(groupedWork, unsuppressedItems);
			loadLocalCallNumbers(groupedWork, unsuppressedItems);
			groupedWork.addBarcodes(getFieldList(record, itemTag + barcodeSubfield));
			groupedWork.setAcceleratedReaderInterestLevel(getAcceleratedReaderInterestLevel(record));
			groupedWork.setAcceleratedReaderReadingLevel(getAcceleratedReaderReadingLevel(record));
			groupedWork.setAcceleratedReaderPointValue(getAcceleratedReaderPointLevel(record));

			loadOrderIds(groupedWork, record);

			groupedWork.addAllFields(getAllFields(record));

			groupedWork.addHoldings(unsuppressedItems.size());
		}catch (Exception e){
			logger.error("Error updating grouped work for MARC record with identifier " + identifier, e);
		}
	}

	protected void loadLocalCallNumbers(GroupedWorkSolr groupedWork, List<DataField> unsuppressedItems) {
		//By default, do nothing.
	}

	protected void loadCallNumbers(GroupedWorkSolr groupedWork, Record record) {
		groupedWork.setCallNumberA(getFirstFieldVal(record, "099a:090a:050a"));
		String firstCallNumber = getFirstFieldVal(record, "099a[0]:090a[0]:050a[0]");
		if (firstCallNumber != null){
			groupedWork.setCallNumberFirst(indexer.translateValue("callnumber", firstCallNumber));
		}
		String callNumberSubject = getCallNumberSubject(record, "090a:050a");
		if (callNumberSubject != null){
			groupedWork.setCallNumberSubject(indexer.translateValue("callnumber_subject", callNumberSubject));
		}
	}

	private String getCallNumberSubject(Record record, String fieldSpec) {
		String val = getFirstFieldVal(record, fieldSpec);

		if (val != null) {
			String[] callNumberSubject = val.toUpperCase().split("[^A-Z]+");
			if (callNumberSubject.length > 0) {
				return callNumberSubject[0];
			}
		}
		return null;
	}

	private void loadOrderIds(GroupedWorkSolr groupedWork, Record record) {
		//Load order ids from recordNumberTag
		Set<String> recordIds = getFieldList(record, recordNumberTag + "a");
		for(String recordId : recordIds){
			if (recordId.startsWith(".o")){
				groupedWork.addAlternateId(recordId);
			}
		}
	}

	protected List<DataField> getUnsuppressedItems(Record record) {
		return getUnsuppressedPrintItems(record);
	}

	protected void loadRecordType(GroupedWorkSolr groupedWork, Record record) {
		String recordId = getFirstFieldVal(record, recordNumberTag + "a");
		groupedWork.addRelatedRecord("ils:" + recordId);
	}

	protected List<DataField> getUnsuppressedPrintItems(Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<DataField> unsuppressedItemRecords = new ArrayList<DataField>();
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				if (useEContentSubfield){
					if (itemField.getSubfield(eContentSubfield) != null){
						String eContentData = itemField.getSubfield(eContentSubfield).getData();
						if (eContentData.indexOf(':') >= 0){
							isEContent = true;
							String[] eContentFields = eContentData.split(":");
							String sourceType = eContentFields[0].toLowerCase().trim();
							if (sourceType.equals("overdrive")){
								isOverDrive = true;
							}
						}
					}
				}
				if (!isOverDrive && !isEContent){
					unsuppressedItemRecords.add(itemField);
				}
			}
		}
		return unsuppressedItemRecords;
	}

	protected List<DataField> getUnsuppressedEContentItems(Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<DataField> unsuppressedEcontentRecords = new ArrayList<DataField>();
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				if (useEContentSubfield){
					if (itemField.getSubfield(eContentSubfield) != null){
						String eContentData = itemField.getSubfield(eContentSubfield).getData();
						if (eContentData.indexOf(':') >= 0){
							isEContent = true;
							String[] eContentFields = eContentData.split(":");
							String sourceType = eContentFields[0].toLowerCase().trim();
							if (sourceType.equals("overdrive")){
								isOverDrive = true;
							}
						}
					}
				}
				if (!isOverDrive && isEContent){
					unsuppressedEcontentRecords.add(itemField);
				}
			}
		}
		return unsuppressedEcontentRecords;
	}

	Pattern mpaaRatingRegex1 = null;
	Pattern mpaaRatingRegex2 = null;
	public String getMpaaRating(Record record) {
		if (mpaaRatingRegex1 == null) {
			mpaaRatingRegex1 = Pattern.compile(
					"(?:.*?)Rated\\s(G|PG-13|PG|R|NC-17|NR|X)(?:.*)", Pattern.CANON_EQ);
		}
		if (mpaaRatingRegex2 == null) {
			mpaaRatingRegex2 = Pattern.compile(
					"(?:.*?)(G|PG-13|PG|R|NC-17|NR|X)\\sRated(?:.*)", Pattern.CANON_EQ);
		}
		String val = getFirstFieldVal(record, "521a");

		if (val != null) {
			if (val.matches("Rated\\sNR\\.?|Not Rated\\.?|NR")) {
				return "Not Rated";
			}
			try {
				Matcher mpaaMatcher1 = mpaaRatingRegex1.matcher(val);
				if (mpaaMatcher1.find()) {
					// System.out.println("Matched matcher 1, " + mpaaMatcher1.group(1) +
					// " Rated " + getId());
					return mpaaMatcher1.group(1) + " Rated";
				} else {
					Matcher mpaaMatcher2 = mpaaRatingRegex2.matcher(val);
					if (mpaaMatcher2.find()) {
						// System.out.println("Matched matcher 2, " + mpaaMatcher2.group(1)
						// + " Rated " + getId());
						return mpaaMatcher2.group(1) + " Rated";
					} else {
						return null;
					}
				}
			} catch (PatternSyntaxException ex) {
				// Syntax error in the regular expression
				return null;
			}
		} else {
			return null;
		}
	}

	private void loadITypes(GroupedWorkSolr groupedWork, List<DataField> unsuppressedItemRecords) {
		for (DataField curItem : unsuppressedItemRecords){
			Subfield locationSubfield = curItem.getSubfield(locationSubfieldIndicator);
			Subfield iTypeField = curItem.getSubfield(iTypeSubfield);
			if (iTypeField != null && locationSubfield != null){
				String iType = indexer.translateValue("itype", iTypeField.getData());
				String location = locationSubfield.getData();
				ArrayList<String> relatedSubdomains = getLibrarySubdomainsForLocationCode(location);
				groupedWork.setIType(iType, relatedSubdomains);
			}
		}
	}

	private static SimpleDateFormat dateAddedFormatter = new SimpleDateFormat("yyMMdd");
	private void loadDateAdded(GroupedWorkSolr groupedWork, List<DataField> unsuppressedItemRecords) {
		for (DataField curItem : unsuppressedItemRecords){
			Subfield locationSubfield = curItem.getSubfield(locationSubfieldIndicator);
			Subfield dateAddedField = curItem.getSubfield(dateCreatedSubfield);
			if (locationSubfield != null && dateAddedField != null){
				String locationCode = locationSubfield.getData();
				String dateAddedStr = dateAddedField.getData();
				try{
					Date dateAdded = dateAddedFormatter.parse(dateAddedStr);
					ArrayList<String> relatedLocations = getLibrarySubdomainsForLocationCode(locationCode);
					relatedLocations.addAll(getIlsCodesForDetailedLocationCode(locationCode));
					groupedWork.setDateAdded(dateAdded, relatedLocations);
				} catch (ParseException e) {
					logger.error("Error processing date added", e);
				}
			}
		}
	}

	private void loadTargetAudiences(GroupedWorkSolr groupedWork, Record record) {
		Set<String> targetAudiences = new LinkedHashSet<String>();
		try {
			String leader = record.getLeader().toString();

			ControlField ohOhEightField = (ControlField) record.getVariableField("008");
			ControlField ohOhSixField = (ControlField) record.getVariableField("006");

			// check the Leader at position 6 to determine the type of field
			char recordType = Character.toUpperCase(leader.charAt(6));
			char bibLevel = Character.toUpperCase(leader.charAt(7));
			// Figure out what material type the record is
			if ((recordType == 'A' || recordType == 'T')
					&& (bibLevel == 'A' || bibLevel == 'C' || bibLevel == 'D' || bibLevel == 'M') /* Books */
					|| (recordType == 'M') /* Computer Files */
					|| (recordType == 'C' || recordType == 'D' || recordType == 'I' || recordType == 'J') /* Music */
					|| (recordType == 'G' || recordType == 'K' || recordType == 'O' || recordType == 'R') /*
																																																 * Visual
																																																 * Materials
																																																 */
					) {
				char targetAudienceChar;
				if (ohOhSixField != null && ohOhSixField.getData().length() > 5) {
					targetAudienceChar = Character.toUpperCase(ohOhSixField.getData()
							.charAt(5));
					if (targetAudienceChar != ' ') {
						targetAudiences.add(Character.toString(targetAudienceChar));
					}
				}
				if (targetAudiences.size() == 0 && ohOhEightField != null
						&& ohOhEightField.getData().length() > 22) {
					targetAudienceChar = Character.toUpperCase(ohOhEightField.getData()
							.charAt(22));
					if (targetAudienceChar != ' ') {
						targetAudiences.add(Character.toString(targetAudienceChar));
					}
				} else if (targetAudiences.size() == 0) {
					targetAudiences.add("Unknown");
				}
			} else {
				targetAudiences.add("Unknown");
			}
		} catch (Exception e) {
			// leader not long enough to get target audience
			logger.debug("ERROR in getTargetAudience ", e);
			targetAudiences.add("Unknown");
		}

		if (targetAudiences.size() == 0) {
			targetAudiences.add("Unknown");
		}

		groupedWork.addTargetAudiences(indexer.translateCollection("target_audience", targetAudiences));
		groupedWork.addTargetAudiencesFull(indexer.translateCollection("target_audience_full", targetAudiences));
	}

	private void loadLiteraryForms(GroupedWorkSolr groupedWork, Record record) {
		Set<String> literaryForms = new LinkedHashSet<String>();
		try {
			String leader = record.getLeader().toString();

			ControlField ohOhEightField = (ControlField) record.getVariableField("008");
			ControlField ohOhSixField = (ControlField) record.getVariableField("006");

			// check the Leader at position 6 to determine the type of field
			char recordType = Character.toUpperCase(leader.charAt(6));
			char bibLevel = Character.toUpperCase(leader.charAt(7));
			// Figure out what material type the record is
			if ((recordType == 'A' || recordType == 'T')
					&& (bibLevel == 'A' || bibLevel == 'C' || bibLevel == 'D' || bibLevel == 'M') /* Books */
					|| (recordType == 'M') /* Computer Files */
					|| (recordType == 'C' || recordType == 'D' || recordType == 'I' || recordType == 'J') /* Music */
					|| (recordType == 'G' || recordType == 'K' || recordType == 'O' || recordType == 'R') /*
																																																 * Visual
																																																 * Materials
																																																 */
					) {
				char literaryFormChar;
				if (ohOhSixField != null && ohOhSixField.getData().length() > 16) {
					literaryFormChar = Character.toUpperCase(ohOhSixField.getData().charAt(16));
					if (literaryFormChar != ' ') {
						literaryForms.add(Character.toString(literaryFormChar));
					}
				}
				if (literaryForms.size() == 0 && ohOhEightField != null && ohOhEightField.getData().length() > 33) {
					literaryFormChar = Character.toUpperCase(ohOhEightField.getData().charAt(33));
					if (literaryFormChar != ' ') {
						literaryForms.add(Character.toString(literaryFormChar));
					}
				}
				if (literaryForms.size() == 0) {
					literaryForms.add(" ");
				}
			} else {
				literaryForms.add("Unknown");
			}
		} catch (Exception e) {
			logger.error("Unexpected error", e);
		}
		groupedWork.addLiteraryForms(indexer.translateCollection("literary_form", literaryForms));
		groupedWork.addLiteraryFormsFull(indexer.translateCollection("literary_form_full", literaryForms));
	}

	private void loadPublicationDetails(GroupedWorkSolr groupedWork, Record record) {
		Set<String> publishers = this.getPublishers(record);
		groupedWork.addPublishers(publishers);
		Set<String> publicationDate = this.getPublicationDates(record);
		groupedWork.addPublicationDates(publicationDate);
	}

	public Set<String> getPublicationDates(Record record) {
		@SuppressWarnings("unchecked")
		List<VariableField> rdaFields = record.getVariableFields("264");
		HashSet<String> publicationDates = new HashSet<String>();
		String date;
		//Try to get from RDA data
		if (rdaFields.size() > 0){
			for (VariableField curField : rdaFields){
				if (curField instanceof DataField){
					DataField dataField = (DataField)curField;
					if (dataField.getIndicator2() == '1'){
						Subfield subFieldC = dataField.getSubfield('c');
						if (subFieldC != null){
							date = subFieldC.getData();
							publicationDates.add(date);
						}
					}
				}
			}
		}
		//Try to get from 260
		publicationDates.addAll(getFieldList(record, "260c"));
		//Try to get from 008
		publicationDates.add(getFirstFieldVal(record, "008[7-10]"));

		return publicationDates;
	}

	public Set<String> getPublishers(Record record){
		Set<String> publisher = new LinkedHashSet<String>();
		//First check for 264 fields
		@SuppressWarnings("unchecked")

		List<DataField> rdaFields = getDataFields(record, "264");
		if (rdaFields.size() > 0){
			for (DataField curField : rdaFields){
				if (curField.getIndicator2() == '1'){
					Subfield subFieldB = curField.getSubfield('b');
					if (subFieldB != null){
						publisher.add(subFieldB.getData());
					}
				}
			}
		}
		publisher.addAll(getFieldList(record, "260b"));
		return publisher;
	}

	private void loadLanguageDetails(GroupedWorkSolr groupedWork, Record record) {
		Set <String> languages = getFieldList(record, "008[35-37]:041a:041d:041j");
		HashSet<String> translatedLanguages = new HashSet<String>();
		for (String language : languages){
			translatedLanguages.add(indexer.translateValue("language", language));
			String languageBoost = indexer.translateValue("language_boost", language);
			if (languageBoost != null){
				Long languageBoostVal = Long.parseLong(languageBoost);
				groupedWork.setLanguageBoost(languageBoostVal);
			}
			String languageBoostEs = indexer.translateValue("language_boost_es", language);
			if (languageBoostEs != null){
				Long languageBoostVal = Long.parseLong(languageBoost);
				groupedWork.setLanguageBoostSpanish(languageBoostVal);
			}
		}
		groupedWork.setLanguages(translatedLanguages);
	}

	private void loadPopularity(GroupedWorkSolr groupedWork, List<DataField> unsuppressedItemRecords) {
		float popularity = 0;
		for (DataField itemField : unsuppressedItemRecords){
			//Get number of times the title has been checked out
			Subfield totalCheckoutsField = itemField.getSubfield(totalCheckoutSubfield);
			int totalCheckouts = 0;
			if (totalCheckoutsField != null){
				totalCheckouts = Integer.parseInt(totalCheckoutsField.getData());
			}
			Subfield ytdCheckoutsField = itemField.getSubfield(ytdCheckoutSubfield);
			int ytdCheckouts = 0;
			if (ytdCheckoutsField != null){
				ytdCheckouts = Integer.parseInt(ytdCheckoutsField.getData());
			}
			Subfield lastYearCheckoutsField = itemField.getSubfield(lastYearCheckoutSubfield);
			int lastYearCheckouts = 0;
			if (lastYearCheckoutsField != null){
				lastYearCheckouts = Integer.parseInt(lastYearCheckoutsField.getData());
			}
			double itemPopularity = ytdCheckouts + .5 * (lastYearCheckouts) + .1 * (totalCheckouts - lastYearCheckouts - ytdCheckouts);
			//logger.debug("Popularity for item " + itemPopularity + " ytdCheckouts=" + ytdCheckouts + " lastYearCheckouts=" + lastYearCheckouts + " totalCheckouts=" + totalCheckouts);
			popularity += itemPopularity;
		}
		groupedWork.addPopularity(popularity);
	}

	protected void loadFormatDetails(GroupedWorkSolr groupedWork, Record record) {
		Set<String> formats = loadFormats(record, false);
		HashSet<String> translatedFormats = new HashSet<String>();
		HashSet<String> formatCategories = new HashSet<String>();
		Long formatBoost = 1L;
		if (formats != null){
			for (String format : formats){
				translatedFormats.add(indexer.translateValue("format", format));
				formatCategories.add(indexer.translateValue("format_category", format));
				String formatBoostStr = indexer.translateValue("format_boost", format);
				try{
					Long curFormatBoost = Long.parseLong(formatBoostStr);
					if (curFormatBoost > formatBoost){
						formatBoost = curFormatBoost;
					}
				}catch (NumberFormatException e){
					logger.warn("Could not parse format_boost " + formatBoostStr);
				}
			}
		}
		//By default, formats are valid for all locations.
		groupedWork.addFormats(translatedFormats, subdomainMap.values(), locationMap.keySet());
		groupedWork.addFormatCategories(formatCategories);
		groupedWork.setFormatBoost(formatBoost);
	}


	private void loadAuthors(GroupedWorkSolr groupedWork, Record record) {
		//auth_author = 100abcd, first
		groupedWork.setAuthAuthor(this.getFirstFieldVal(record, "100abcd"));
		//author = a, first
		groupedWork.setAuthor(this.getFirstFieldVal(record, "100abcdq:110a:710a"));
		//author-letter = 100a, first
		groupedWork.setAuthorLetter(this.getFirstFieldVal(record, "100a"));
		//auth_author2 = 700abcd
		groupedWork.addAuthAuthor2(this.getFieldList(record, "700abcd"));
		//author2 = 110ab:111ab:700abcd:710ab:711ab:800a
		groupedWork.addAuthor2(this.getFieldList(record, "110ab:111ab:700abcd:710ab:711ab:800a"));
		//author2-role = 700e:710e
		groupedWork.addAuthor2Role(this.getFieldList(record, "700e:710e"));
		//author_additional = 505r:245c
		groupedWork.addAuthorAdditional(this.getFieldList(record, "505r:245c"));
		//author_display = 100a:110a:260b:710a:245c, first
		String displayAuthor = this.getFirstFieldVal(record, "100a:110a:260b:710a:245c");
		if (displayAuthor != null && displayAuthor.indexOf(';') > 0){
			displayAuthor = displayAuthor.substring(0, displayAuthor.indexOf(';') -1);
		}
		groupedWork.setAuthorDisplay(displayAuthor);
	}

	private void loadTitles(GroupedWorkSolr groupedWork, Record record) {
		//title (full title done by index process by concatenating short and subtitle

		//title short
		groupedWork.setTitle(this.getFirstFieldVal(record, "245a"));
		//title sub
		groupedWork.setSubTitle(this.getFirstFieldVal(record, "245b"));
		//display title
		groupedWork.setDisplayTitle(this.getFirstFieldVal(record, "245abnp"));
		//title full
		groupedWork.addFullTitles(this.getAllSubfields(record, "245", " "));
		//title sort
		groupedWork.setSortableTitle(this.getSortableTitle(record));
		//title alt
		groupedWork.addAlternateTitles(this.getFieldList(record, "130adfgklnpst:240a:246a:730adfgklnpst:740a"));
		//title old
		groupedWork.addOldTitles(this.getFieldList(record, "780ast"));
		//title new
		groupedWork.addNewTitles(this.getFieldList(record, "785ast"));
	}

	protected String getFirstFieldVal(Record record, String fieldSpec) {
		Set<String> result = getFieldList(record, fieldSpec);
		if (result.size() == 0){
			return null;
		}else{
			return result.iterator().next();
		}
	}

	protected void loadUsability(GroupedWorkSolr groupedWork, List<DataField> unsuppressedItemRecords) {
		//Load a list of ptypes that can use this record based on loan rules
		for (DataField curItem : unsuppressedItemRecords){
			Subfield iTypeSubfieldVal = curItem.getSubfield(iTypeSubfield);
			Subfield locationCodeSubfield = curItem.getSubfield(locationSubfieldIndicator);
			if (iTypeSubfieldVal != null && locationCodeSubfield != null){
				String iType = iTypeSubfieldVal.getData().trim();
				String locationCode = locationCodeSubfield.getData().trim();
				groupedWork.addCompatiblePTypes(getCompatiblePTypes(iType, locationCode));
			}
		}
	}

	private boolean isItemSuppressed(DataField curItem) {
		Subfield icode2Subfield = curItem.getSubfield(iCode2Subfield);
		if (icode2Subfield == null){
			return false;
		}
		String icode2 = icode2Subfield.getData().toLowerCase().trim();
		Subfield locationCodeSubfield = curItem.getSubfield(locationSubfieldIndicator);
		if (locationCodeSubfield == null)                                                 {
			return false;
		}
		String locationCode = locationCodeSubfield.getData().trim();

		return icode2.equals("n") || icode2.equals("x") || locationCode.equals("zzzz");
	}

	private void loadAvailability(GroupedWorkSolr groupedWork, List<DataField> itemRecords) {
		//Calculate availability based on the record
		HashSet<String> availableAt = new HashSet<String>();
		HashSet<String> availableLocationCodes = new HashSet<String>();

		for (DataField curItem : itemRecords){
			Subfield statusSubfield = curItem.getSubfield(statusSubfieldIndicator);
			Subfield dueDateField = curItem.getSubfield(dueDateSubfield);
			Subfield locationCodeField = curItem.getSubfield(locationSubfieldIndicator);
			if (locationCodeField != null){
				String locationCode = locationCodeField.getData().trim();
				boolean available = false;
				if (getAvailabilityFromMarc){
					if (statusSubfield != null) {
						String status = statusSubfield.getData();
						String dueDate = dueDateField == null ? "" : dueDateField.getData().trim();
						String availableStatus = "-dowju";
						if (availableStatus.indexOf(status.charAt(0)) >= 0) {
							if (dueDate.length() == 0) {
								available = true;
							}
						}
					}
				}else{
					if (curItem.getSubfield(barcodeSubfield) != null){
						String barcode = curItem.getSubfield(barcodeSubfield).getData().trim();
						available = availableItemBarcodes.contains(barcode);
					}
				}

				if (available) {
					availableAt.addAll(getLocationFacetsForLocationCode(locationCode));
					availableLocationCodes.addAll(getRelatedLocationCodesForLocationCode(locationCode));
					availableLocationCodes.addAll(getRelatedSubdomainsForLocationCode(locationCode));
				}
			}
		}
		groupedWork.addAvailableLocations(availableAt, availableLocationCodes);

	}

	private void loadOwnershipInformation(GroupedWorkSolr groupedWork, List<DataField> itemRecords) {
		HashSet<String> owningLibraries = new HashSet<String>();
		HashSet<String> owningLocations = new HashSet<String>();
		HashSet<String> owningLocationCodes = new HashSet<String>();
		for (DataField curItem : itemRecords){
			Subfield locationSubfield = curItem.getSubfield(locationSubfieldIndicator);
			if (locationSubfield != null){
				String locationCode = locationSubfield.getData().trim();
				owningLibraries.addAll(getLibraryFacetsForLocationCode(locationCode));

				owningLocations.addAll(getLocationFacetsForLocationCode(locationCode));
				owningLocationCodes.addAll(getRelatedLocationCodesForLocationCode(locationCode));
				owningLocationCodes.addAll(getRelatedSubdomainsForLocationCode(locationCode));

				loadAdditionalOwnershipInformation(groupedWork, locationCode);
			}
		}
		groupedWork.addOwningLibraries(owningLibraries);
		groupedWork.addOwningLocations(owningLocations);
		groupedWork.addOwningLocationCodesAndSubdomains(owningLocationCodes);
	}

	protected void loadAdditionalOwnershipInformation(GroupedWorkSolr groupedWork, String locationCode){

	}

	private ArrayList<String> getLibraryFacetsForLocationCode(String locationCode) {
		ArrayList<String> libraryFacets = new ArrayList<String>();
		for(String libraryCode : libraryMap.keySet()){
			if (locationCode.startsWith(libraryCode)){
				libraryFacets.add(libraryMap.get(libraryCode));
			}
		}
		if (libraryFacets.size() == 0){
			logger.warn("Did not find any library facets for " + locationCode);
		}
		return libraryFacets;
	}

	private ArrayList<String> getRelatedSubdomainsForLocationCode(String locationCode) {
		ArrayList<String> subdomains = new ArrayList<String>();
		for(String libraryCode : subdomainMap.keySet()){
			if (locationCode.startsWith(libraryCode)){
				subdomains.add(subdomainMap.get(libraryCode));
			}
		}
		if (subdomains.size() == 0){
			logger.warn("Did not find any subdomains for " + locationCode);
		}
		return subdomains;
	}

	protected ArrayList<String> getLibrarySubdomainsForLocationCode(String locationCode) {
		ArrayList<String> librarySubdomains = new ArrayList<String>();
		for(String libraryCode : subdomainMap.keySet()){
			if (locationCode.startsWith(libraryCode)){
				librarySubdomains.add(subdomainMap.get(libraryCode));
			}
		}
		if (librarySubdomains.size() == 0){
			logger.warn("Did not find any library subdomains for " + locationCode);
		}
		return librarySubdomains;
	}

	private HashSet<String> locationCodesWithoutFacets = new HashSet<String>();
	private ArrayList<String> getLocationFacetsForLocationCode(String locationCode) {
		ArrayList<String> locationFacets = new ArrayList<String>();
		if (locationCode == null || locationCode.length() == 0){
			return locationFacets;
		}
		for(String ilsCode : locationMap.keySet()){
			if (locationCode.startsWith(ilsCode)){
				locationFacets.add(locationMap.get(ilsCode));
			}
		}
		if (locationFacets.size() == 0){
			if (!locationCodesWithoutFacets.contains(locationCode)){
				logger.warn("Did not find any location facets for '" + locationCode + "'");
				locationCodesWithoutFacets.add(locationCode);
			}
		}
		return locationFacets;
	}

	protected ArrayList<String> getRelatedLocationCodesForLocationCode(String locationCode){
		ArrayList<String> locationFacets = new ArrayList<String>();
		if (locationCode == null || locationCode.length() == 0){
			return locationFacets;
		}
		for(String ilsCode : locationMap.keySet()){
			if (locationCode.startsWith(ilsCode)){
				locationFacets.add(ilsCode);
			}
		}
		return locationFacets;
	}

	private ArrayList<String> getIlsCodesForDetailedLocationCode(String locationCode) {
		ArrayList<String> locationCodes = new ArrayList<String>();
		for(String ilsCode : locationMap.keySet()){
			if (locationCode.startsWith(ilsCode)){
				locationCodes.add(ilsCode);
			}
		}
		return locationCodes;
	}

	protected List<DataField> getDataFields(Record marcRecord, String tag) {
		List variableFields = marcRecord.getVariableFields(tag);
		List<DataField> variableFieldsReturn = new ArrayList<DataField>();
		for (Object variableField : variableFields){
			if (variableField instanceof DataField){
				variableFieldsReturn.add((DataField)variableField);
			}
		}
		return variableFieldsReturn;
	}

	private HashMap<String, LinkedHashSet<String>> ptypesByItypeAndLocation = new HashMap<String, LinkedHashSet<String>>();
	public LinkedHashSet<String> getCompatiblePTypes(String iType, String locationCode) {
		String cacheKey = iType + ":" + locationCode;
		if (ptypesByItypeAndLocation.containsKey(cacheKey)){
			return ptypesByItypeAndLocation.get(cacheKey);
		}
		//logger.debug("getCompatiblePTypes for " + cacheKey);
		LinkedHashSet<String> result = new LinkedHashSet<String>();
		Long iTypeLong = Long.parseLong(iType);
		//Loop through all patron types to see if the item is holdable
		for (Long pType : pTypes){
			//logger.debug("  Checking pType " + pType);
			//Loop through the loan rules to see if this itype can be used based on the location code
			for (LoanRuleDeterminer curDeterminer : loanRuleDeterminers){
				//logger.debug("   Checking determiner " + curDeterminer.getRowNumber() + " " + curDeterminer.getLocation());
				//Make sure the location matchs
				if (curDeterminer.matchesLocation(locationCode)){
					//logger.debug("    " + curDeterminer.getRowNumber() + " matches location");
					if (curDeterminer.getItemType().equals("999") || curDeterminer.getItemTypes().contains(iTypeLong)){
						//logger.debug("    " + curDeterminer.getRowNumber() + " matches iType");
						if (curDeterminer.getPatronType().equals("999") || curDeterminer.getPatronTypes().contains(pType)){
							//logger.debug("    " + curDeterminer.getRowNumber() + " matches pType");
							LoanRule loanRule = loanRules.get(curDeterminer.getLoanRuleId());
							if (loanRule.getHoldable().equals(Boolean.TRUE)){
								result.add(pType.toString());
							}
							//We got a match, stop processig
							//logger.debug("    using determiner " + curDeterminer.getRowNumber() + " for ptype " + pType);
							break;
						}
					}
				}
			}
		}
		//logger.debug("  " + result.size() + " ptypes can use this");
		ptypesByItypeAndLocation.put(cacheKey, result);
		return result;
	}

	public String getAcceleratedReaderReadingLevel(Record marcRecord) {
		String result = null;
		// Get a list of all tags that may contain the lexile score.
		@SuppressWarnings("unchecked")
		List<VariableField> input = marcRecord.getVariableFields("526");
		Iterator<VariableField> iter = input.iterator();

		DataField field;
		while (iter.hasNext()) {
			field = (DataField) iter.next();

			if (field.getSubfield('a') != null) {
				String type = field.getSubfield('a').getData();
				if (type.matches("(?i)accelerated reader")) {
					if (field.getSubfield('c') != null){
						String rawData = field.getSubfield('c').getData();
						try {
							Pattern Regex = Pattern.compile("([\\d.]+)", Pattern.CANON_EQ
									| Pattern.CASE_INSENSITIVE | Pattern.UNICODE_CASE);
							Matcher RegexMatcher = Regex.matcher(rawData);
							if (RegexMatcher.find()) {
								result = RegexMatcher.group(1);
								// System.out.println("AR Reading Level " + result);
								return result;
							}
						} catch (PatternSyntaxException ex) {
							// Syntax error in the regular expression
						}
					}
				}
			}
		}

		return null;
	}

	public String getAllFields(Record marcRecord) {
		StringBuilder allFieldData = new StringBuilder();
		List<ControlField> controlFields = marcRecord.getControlFields();
		for (Object field : controlFields) {
			ControlField dataField = (ControlField) field;
			String data = dataField.getData();
			data = data.replace((char) 31, ' ');
			allFieldData.append(data).append(" ");
		}

		List<DataField> fields = marcRecord.getDataFields();
		for (Object field : fields) {
			DataField dataField = (DataField) field;
			List<Subfield> subfields = dataField.getSubfields();
			for (Object subfieldObj : subfields) {
				Subfield subfield = (Subfield) subfieldObj;
				allFieldData.append(subfield.getData()).append(" ");
			}
		}

		return allFieldData.toString();
	}

	public String getAcceleratedReaderPointLevel(Record marcRecord) {
		try {
			String result = null;
			// Get a list of all tags that may contain the lexile score.
			@SuppressWarnings("unchecked")
			List<VariableField> input = marcRecord.getVariableFields("526");
			Iterator<VariableField> iter = input.iterator();

			DataField field;
			while (iter.hasNext()) {
				field = (DataField) iter.next();

				if (field.getSubfield('a') != null) {
					String type = field.getSubfield('a').getData();
					if (type.matches("(?i)accelerated reader") && field.getSubfield('d') != null) {
						String rawData = field.getSubfield('d').getData();
						try {
							Pattern Regex = Pattern.compile("([\\d.]+)", Pattern.CANON_EQ
									| Pattern.CASE_INSENSITIVE | Pattern.UNICODE_CASE);
							Matcher RegexMatcher = Regex.matcher(rawData);
							if (RegexMatcher.find()) {
								result = RegexMatcher.group(1);
								// System.out.println("AR Point Level " + result);
								return result;
							}
						} catch (PatternSyntaxException ex) {
							// Syntax error in the regular expression
						}
					}
				}
			}

			return null;
		} catch (Exception e) {
			logger.error("Error mapping AR points");
			return null;
		}
	}

	public String getAcceleratedReaderInterestLevel(Record marcRecord) {
		try {
			String result = null;
			// Get a list of all tags that may contain the lexile score.
			@SuppressWarnings("unchecked")
			List<VariableField> input = marcRecord.getVariableFields("526");
			Iterator<VariableField> iter = input.iterator();

			DataField field;
			while (iter.hasNext()) {
				field = (DataField) iter.next();

				if (field.getSubfield('a') != null &&  field.getSubfield('b') != null) {
					String type = field.getSubfield('a').getData();
					if (type.matches("(?i)accelerated reader")) {
						return field.getSubfield('b').getData();
					}
				}
			}

			return null;
		} catch (Exception e) {
			logger.error("Error mapping AR interest level", e);
			return null;
		}
	}

	/**
	 * Get Set of Strings as indicated by tagStr. For each field spec in the
	 * tagStr that is NOT about bytes (i.e. not a 008[7-12] type fieldspec), the
	 * result string is the concatenation of all the specific subfields.
	 *
	 * @param record
	 *          - the marc record object
	 * @param tagStr
	 *          string containing which field(s)/subfield(s) to use. This is a
	 *          series of: marc "tag" string (3 chars identifying a marc field,
	 *          e.g. 245) optionally followed by characters identifying which
	 *          subfields to use. Separator of colon indicates a separate value,
	 *          rather than concatenation. 008[5-7] denotes bytes 5-7 of the 008
	 *          field (0 based counting) 100[a-cf-z] denotes the bracket pattern
	 *          is a regular expression indicating which subfields to include.
	 *          Note: if the characters in the brackets are digits, it will be
	 *          interpreted as particular bytes, NOT a pattern. 100abcd denotes
	 *          subfields a, b, c, d are desired.
	 * @return the contents of the indicated marc field(s)/subfield(s), as a set
	 *         of Strings.
	 */
	protected Set<String> getFieldList(Record record, String tagStr) {
		String[] tags = tagStr.split(":");
		Set<String> result = new LinkedHashSet<String>();
		for (String tag1 : tags) {
			// Check to ensure tag length is at least 3 characters
			if (tag1.length() < 3) {
				System.err.println("Invalid tag specified: " + tag1);
				continue;
			}

			// Get Field Tag
			String tag = tag1.substring(0, 3);
			boolean linkedField = false;
			if (tag.equals("LNK")) {
				tag = tag1.substring(3, 6);
				linkedField = true;
			}
			// Process Subfields
			String subfield = tag1.substring(3);
			boolean havePattern = false;
			int subend = 0;
			// brackets indicate parsing for individual characters or as pattern
			int bracket = tag1.indexOf('[');
			if (bracket != -1) {
				String sub[] = tag1.substring(bracket + 1).split("[\\]\\[\\-, ]+");
				try {
					// if bracket expression is digits, expression is treated as character
					// positions
					int substart = Integer.parseInt(sub[0]);
					subend = (sub.length > 1) ? Integer.parseInt(sub[1]) + 1
							: substart + 1;
					String subfieldWObracket = subfield.substring(0, bracket - 3);
					result.addAll(getSubfieldDataAsSet(record, tag, subfieldWObracket,
							substart, subend));
				} catch (NumberFormatException e) {
					// assume brackets expression is a pattern such as [a-z]
					havePattern = true;
				}
			}
			if (subend == 0) // don't want specific characters.
			{
				String separator = null;
				if (subfield.indexOf('\'') != -1) {
					separator = subfield.substring(subfield.indexOf('\'') + 1,
							subfield.length() - 1);
					subfield = subfield.substring(0, subfield.indexOf('\''));
				}

				if (havePattern)
					if (linkedField)
						result.addAll(getLinkedFieldValue(record, tag, subfield, separator));
					else
						result.addAll(getAllSubfields(record, tag + subfield, separator));
				else if (linkedField)
					result.addAll(getLinkedFieldValue(record, tag, subfield, separator));
				else
					result.addAll(getSubfieldDataAsSet(record, tag, subfield, separator));
			}
		}
		return result;
	}

	/**
	 * Get the specified substring of subfield values from the specified MARC
	 * field, returned as a set of strings to become lucene document field values
	 *
	 * @param record
	 *          - the marc record object
	 * @param fldTag
	 *          - the field name, e.g. 008
	 * @param subfield
	 *          - the string containing the desired subfields
	 * @param beginIx
	 *          - the beginning index of the substring of the subfield value
	 * @param endIx
	 *          - the ending index of the substring of the subfield value
	 * @return the result set of strings
	 */
	@SuppressWarnings("unchecked")
	protected Set<String> getSubfieldDataAsSet(Record record, String fldTag, String subfield, int beginIx, int endIx) {
		Set<String> resultSet = new LinkedHashSet<String>();

		// Process Leader
		if (fldTag.equals("000")) {
			resultSet.add(record.getLeader().toString().substring(beginIx, endIx));
			return resultSet;
		}

		// Loop through Data and Control Fields
		List<VariableField> varFlds = record.getVariableFields(fldTag);
		for (VariableField vf : varFlds) {
			if (!isControlField(fldTag) && subfield != null) {
				// Data Field
				DataField dfield = (DataField) vf;
				if (subfield.length() > 1) {
					// automatic concatenation of grouped subFields
					StringBuilder buffer = new StringBuilder("");
					List<Subfield> subFields = dfield.getSubfields();
					for (Subfield sf : subFields) {
						if (subfield.indexOf(sf.getCode()) != -1
								&& sf.getData().length() >= endIx) {
							if (buffer.length() > 0)
								buffer.append(" ");
							buffer.append(sf.getData().substring(beginIx, endIx));
						}
					}
					resultSet.add(buffer.toString());
				} else {
					// get all instances of the single subfield
					List<Subfield> subFlds = dfield.getSubfields(subfield.charAt(0));
					for (Subfield sf : subFlds) {
						if (sf.getData().length() >= endIx)
							resultSet.add(sf.getData().substring(beginIx, endIx));
					}
				}
			} else // Control Field
			{
				String cfldData = ((ControlField) vf).getData();
				if (cfldData.length() >= endIx)
					resultSet.add(cfldData.substring(beginIx, endIx));
			}
		}
		return resultSet;
	}

	/**
	 * Get the specified subfields from the specified MARC field, returned as a
	 * set of strings to become lucene document field values
	 *
	 * @param fldTag
	 *          - the field name, e.g. 245
	 * @param subfieldsStr
	 *          - the string containing the desired subfields
	 * @param separator
	 *          - the separator string to insert between subfield items (if null,
	 *          a " " will be used)
	 * @return a Set of String, where each string is the concatenated contents of
	 *          all the desired subfield values from a single instance of the
	 *          fldTag
	 */
	@SuppressWarnings("unchecked")
	protected Set<String> getSubfieldDataAsSet(Record record, String fldTag, String subfieldsStr, String separator) {
		Set<String> resultSet = new LinkedHashSet<String>();

		// Process Leader
		if (fldTag.equals("000")) {
			resultSet.add(record.getLeader().toString());
			return resultSet;
		}

		// Loop through Data and Control Fields
		// int iTag = new Integer(fldTag).intValue();
		List<VariableField> varFlds = record.getVariableFields(fldTag);
		for (VariableField vf : varFlds) {
			if (!isControlField(fldTag) && subfieldsStr != null) {
				// DataField
				DataField dfield = (DataField) vf;

				if (subfieldsStr.length() > 1 || separator != null) {
					// concatenate subfields using specified separator or space
					StringBuilder buffer = new StringBuilder("");
					List<Subfield> subFields = dfield.getSubfields();
					for (Subfield sf : subFields) {
						if (subfieldsStr.indexOf(sf.getCode()) != -1) {
							if (buffer.length() > 0) {
								buffer.append(separator != null ? separator : " ");
							}
							buffer.append(sf.getData().trim());
						}
					}
					if (buffer.length() > 0){
						resultSet.add(buffer.toString());
					}
				} else if (subfieldsStr.length() == 1) {
					// get all instances of the single subfield
					List<Subfield> subFields = dfield.getSubfields(subfieldsStr.charAt(0));
					for (Subfield sf : subFields) {
						resultSet.add(sf.getData().trim());
					}
				} else {
					logger
							.warn("No subfield provided when getting getSubfieldDataAsSet for "
									+ fldTag);
				}
			} else {
				// Control Field
				resultSet.add(((ControlField) vf).getData().trim());
			}
		}
		return resultSet;
	}

	protected boolean isControlField(String fieldTag) {
		return fieldTag.matches("00[0-9]");
	}

	/**
	 * Given a tag for a field, and a list (or regex) of one or more subfields get
	 * any linked 880 fields and include the appropriate subfields as a String
	 * value in the result set.
	 *
	 * @param tag
	 *          - the marc field for which 880s are sought.
	 * @param subfield
	 *          - The subfield(s) within the 880 linked field that should be
	 *          returned [a-cf-z] denotes the bracket pattern is a regular
	 *          expression indicating which subfields to include from the linked
	 *          880. Note: if the characters in the brackets are digits, it will
	 *          be interpreted as particular bytes, NOT a pattern 100abcd denotes
	 *          subfields a, b, c, d are desired from the linked 880.
	 * @param separator
	 *          - the separator string to insert between subfield items (if null,
	 *          a " " will be used)
	 *
	 * @return set of Strings containing the values of the designated 880
	 *         field(s)/subfield(s)
	 */
	@SuppressWarnings("unchecked")
	public Set<String> getLinkedFieldValue(Record record, String tag, String subfield, String separator) {
		// assume brackets expression is a pattern such as [a-z]
		Set<String> result = new LinkedHashSet<String>();
		boolean havePattern = false;
		Pattern subfieldPattern = null;
		if (subfield.indexOf('[') != -1) {
			havePattern = true;
			subfieldPattern = Pattern.compile(subfield);
		}
		List<VariableField> fields = record.getVariableFields("880");
		for (VariableField vf : fields) {
			DataField dfield = (DataField) vf;
			Subfield link = dfield.getSubfield('6');
			if (link != null && link.getData().startsWith(tag)) {
				List<Subfield> subList = dfield.getSubfields();
				StringBuilder buf = new StringBuilder("");
				for (Subfield subF : subList) {
					boolean addIt = false;
					if (havePattern) {
						Matcher matcher = subfieldPattern.matcher("" + subF.getCode());
						// matcher needs a string, hence concat with empty
						// string
						if (matcher.matches())
							addIt = true;
					} else
					// a list a subfields
					{
						if (subfield.indexOf(subF.getCode()) != -1)
							addIt = true;
					}
					if (addIt) {
						if (buf.length() > 0)
							buf.append(separator != null ? separator : " ");
						buf.append(subF.getData().trim());
					}
				}
				if (buf.length() > 0)
					result.add(Utils.cleanData(buf.toString()));
			}
		}
		return (result);
	}

	/**
	 * extract all the subfields requested in requested marc fields. Each instance
	 * of each marc field will be put in a separate result (but the subfields will
	 * be concatenated into a single value for each marc field)
	 *
	 * @param fieldSpec
	 *          - the desired marc fields and subfields as given in the
	 *          xxx_index.properties file
	 * @param separator
	 *          - the character to use between subfield values in the solr field
	 *          contents
	 * @return Set of values (as strings) for solr field
	 */
	@SuppressWarnings("unchecked")
	public Set<String> getAllSubfields(Record record, String fieldSpec, String separator) {
		Set<String> result = new LinkedHashSet<String>();

		String[] fldTags = fieldSpec.split(":");
		for (String fldTag1 : fldTags) {
			// Check to ensure tag length is at least 3 characters
			if (fldTag1.length() < 3) {
				System.err.println("Invalid tag specified: " + fldTag1);
				continue;
			}

			String fldTag = fldTag1.substring(0, 3);

			String subfldTags = fldTag1.substring(3);

			List<VariableField> marcFieldList = record.getVariableFields(fldTag);
			if (!marcFieldList.isEmpty()) {
				Pattern subfieldPattern = Pattern
						.compile(subfldTags.length() == 0 ? "." : subfldTags);
				for (VariableField vf : marcFieldList) {
					DataField marcField = (DataField) vf;
					StringBuilder buffer = new StringBuilder("");
					List<Subfield> subFields = marcField.getSubfields();
					for (Subfield subfield : subFields) {
						Matcher matcher = subfieldPattern.matcher("" + subfield.getCode());
						if (matcher.matches()) {
							if (buffer.length() > 0)
								buffer.append(separator != null ? separator : " ");
							buffer.append(subfield.getData().trim());
						}
					}
					if (buffer.length() > 0)
						result.add(Utils.cleanData(buffer.toString()));
				}
			}
		}

		return result;
	}

	/**
	 * Get the title (245ab) from a record, without non-filing chars as specified
	 * in 245 2nd indicator, and lower cased.
	 *
	 * @return 245a and 245b values concatenated, with trailing punctuation removed, and
	 *         with non-filing characters omitted. Null returned if no title can
	 *         be found.
	 */
	public String getSortableTitle(Record record) {
		DataField titleField = (DataField) record.getVariableField("245");
		if (titleField == null || titleField.getSubfield('a') == null)
			return "";

		int nonFilingInt = getInd2AsInt(titleField);

		String title = titleField.getSubfield('a').getData();
		title = title.toLowerCase();

		// Skip non-filing chars, if possible.
		if (title.length() > nonFilingInt) {
			title = title.substring(nonFilingInt);
		}

		if (title.length() == 0) {
			return null;
		}

		return title;
	}

	/**
	 * @param df
	 *          a DataField
	 * @return the integer (0-9, 0 if blank or other) in the 2nd indicator
	 */
	protected int getInd2AsInt(DataField df) {
		char ind2char = df.getIndicator2();
		int result = 0;
		if (Character.isDigit(ind2char))
			result = Integer.valueOf(String.valueOf(ind2char));
		return result;
	}

	/**
	 * Determine Record Format(s)
	 *
	 * @return Set format of record
	 */
	public abstract Set<String> loadFormats(Record record, boolean returnFirst);

	private char getSubfieldIndicatorFromConfig(Ini configIni, String subfieldName) {
		String subfieldString = configIni.get("Reindex", subfieldName);
		char subfield = ' ';
		if (subfieldString.length() > 0)  {
			subfield = subfieldString.charAt(0);
		}
		return subfield;
	}
}
