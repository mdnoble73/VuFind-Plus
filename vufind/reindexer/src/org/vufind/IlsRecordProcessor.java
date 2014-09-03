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
	protected char eContentSubfieldIndicator;
	protected char lastYearCheckoutSubfield;
	protected char ytdCheckoutSubfield;
	protected char totalCheckoutSubfield;
	protected boolean useICode2Suppression;
	protected char iCode2Subfield;
	protected String[] additionalCollections;
	protected char callNumberPrestampSubfield;
	protected char callNumberSubfield;
	protected char callNumberCutterSubfield;
	protected char itemRecordNumberSubfieldIndicator;
	protected char itemUrlSubfieldIndicator;

	private static boolean loanRuleDataLoaded = false;
	protected static ArrayList<Long> pTypes = new ArrayList<Long>();
	protected static HashMap<String, HashSet<String>> pTypesByLibrary = new HashMap<String, HashSet<String>>();
	protected static HashMap<String, HashSet<String>> pTypesForSpecialLocationCodes = new HashMap<String, HashSet<String>>();
	protected static HashSet<String> allPTypes = new HashSet<String>();
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
		eContentSubfieldIndicator = getSubfieldIndicatorFromConfig(configIni, "eContentSubfield");
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
		itemRecordNumberSubfieldIndicator = getSubfieldIndicatorFromConfig(configIni, "itemRecordNumberSubfield");
		itemUrlSubfieldIndicator = getSubfieldIndicatorFromConfig(configIni, "itemUrlSubfield");

		String additionalCollectionsString = configIni.get("Reindex", "additionalCollections");
		if (additionalCollectionsString != null){
			additionalCollections = additionalCollectionsString.split(",");
		}

		loadAvailableItemBarcodes(marcRecordPath, logger);
		loadLoanRuleInformation(vufindConn, logger);
	}

	private static void loadLoanRuleInformation(Connection vufindConn, Logger logger) {
		if (!loanRuleDataLoaded){
			//Load loan rules
			try {
				PreparedStatement pTypesStmt = vufindConn.prepareStatement("SELECT pType from ptype", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet pTypesRS = pTypesStmt.executeQuery();
				while (pTypesRS.next()) {
					pTypes.add(pTypesRS.getLong("pType"));
					allPTypes.add(pTypesRS.getString("pType"));
				}

				PreparedStatement pTypesByLibraryStmt = vufindConn.prepareStatement("SELECT pTypes, ilsCode, econtentLocationsToInclude from library", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet pTypesByLibraryRS = pTypesByLibraryStmt.executeQuery();
				while (pTypesByLibraryRS.next()) {
					String ilsCode = pTypesByLibraryRS.getString("ilsCode");
					String pTypes = pTypesByLibraryRS.getString("pTypes");
					String econtentLocationsToIncludeStr = pTypesByLibraryRS.getString("econtentLocationsToInclude");
					if (pTypes != null && pTypes.length() > 0){
						String[] pTypeElements = pTypes.split(",");
						HashSet<String> pTypesForLibrary = new HashSet<String>();
						Collections.addAll(pTypesForLibrary, pTypeElements);
						pTypesByLibrary.put(ilsCode, pTypesForLibrary);
						if (econtentLocationsToIncludeStr.length() > 0) {
							String[] econtentLocationsToInclude = econtentLocationsToIncludeStr.split(",");
							for (String econtentLocationToInclude : econtentLocationsToInclude) {
								econtentLocationToInclude = econtentLocationToInclude.trim();
								if (econtentLocationToInclude.length() > 0) {
									if (!pTypesForSpecialLocationCodes.containsKey(econtentLocationToInclude)) {
										pTypesForSpecialLocationCodes.put(econtentLocationToInclude, new HashSet<String>());
									}
									pTypesForSpecialLocationCodes.get(econtentLocationToInclude).addAll(pTypesForLibrary);
								}
							}
						}
					}
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
		while (shortId.length() < 9){
			shortId = "0" + shortId;
		}
		String firstChars = shortId.substring(0, 4);
		String basePath = individualMarcPath + "/" + firstChars;
		String individualFilename = basePath + "/" + shortId + ".mrc";
		File individualFile = new File(individualFilename);
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
	}

	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		try{
			//First load a list of print items and econtent items from the MARC record since they are needed to handle
			//Scoping and availability of records.
			List<PrintIlsItem> printItems = getUnsuppressedPrintItems(record);
			List<EContentIlsItem> econtentItems = getUnsuppressedEContentItems(identifier, record);
			List<OnOrderItem> onOrderItems = getOnOrderItems(identifier, record);

			//Break the MARC record up based on item information and load data that is scoped
			//i.e. formats, iTypes, date added to catalog, etc
			HashSet<IlsRecord> ilsRecords = loadScopedDataForMarcRecord(groupedWork, record, printItems, econtentItems);

			//Do updates based on the overall bib (shared regardless of scoping)
			loadTitles(groupedWork, record);
			loadAuthors(groupedWork, record);

			groupedWork.addAllFields(getAllFields(record));
			groupedWork.addKeywords(getAllSearchableFields(record, 100, 900));
			//Load description
			String fullDescription = Util.getCRSeparatedString(getFieldList(record, "520a"));
			for (IlsRecord ilsRecord : ilsRecords) {
				groupedWork.addDescription(fullDescription, ilsRecord.getPrimaryFormat());
			}
			groupedWork.addTopic(getFieldList(record, "600abcdefghjklmnopqrstuvxyz:610abcdefghjklmnopqrstuvxyz:611acdefghklnpqstuvxyz:630abfghklmnoprstvxyz:650abcdevxyz:651abcdevxyz:690a"));
			groupedWork.addTopicFacet(getFieldList(record, "600a:600x:600a:610x:611x:611x:630a:630x:648x:650a:650x:651x:655x"));
			groupedWork.addSeries(getFieldList(record, "440ap:800pqt:830ap"));
			groupedWork.addSeries2(getFieldList(record, "490a"));
			loadPhysicalDescription(groupedWork, record, ilsRecords);
			groupedWork.addDateSpan(getFieldList(record, "362a"));
			loadEditions(groupedWork, record, ilsRecords);
			groupedWork.addContents(getFieldList(record, "505a:505t"));
			groupedWork.addGenre(getFieldList(record, "655abcvxyz"));
			groupedWork.addGenreFacet(getFieldList(record, "600v:610v:611v:630v:648v:650v:651v:655a:655v"));
			groupedWork.addGeographic(getFieldList(record, "651avxyz"));
			groupedWork.addGeographicFacet(getFieldList(record, "600z:610z:611z:630z:648z:650z:651a:651z:655z"));
			groupedWork.addEra(getFieldList(record, "600d:610y:611y:630y:648a:648y:650y:651y:655y"));
			groupedWork.addContents(getFieldList(record, "505a:505t"));
			groupedWork.addIssns(getFieldList(record, "022a"));
			groupedWork.addOclcNumbers(getFieldList(record, "035a"));

			loadBibCallNumbers(groupedWork, record);
			loadLanguageDetails(groupedWork, record, ilsRecords);
			loadPublicationDetails(groupedWork, record, ilsRecords);
			loadLiteraryForms(groupedWork, record);
			loadTargetAudiences(groupedWork, record);
			groupedWork.addMpaaRating(groupedWork, getMpaaRating(record));

			//Do updates based on items
			loadOwnershipInformation(groupedWork, printItems, econtentItems, onOrderItems);
			loadAvailability(groupedWork, printItems, econtentItems);
			loadUsability(groupedWork, printItems, econtentItems);
			loadPopularity(groupedWork, printItems, econtentItems);
			loadDateAdded(groupedWork, printItems, econtentItems);
			loadITypes(groupedWork, printItems, econtentItems);
			loadLocalCallNumbers(groupedWork, printItems, econtentItems);
			groupedWork.addBarcodes(getFieldList(record, itemTag + barcodeSubfield));
			groupedWork.setAcceleratedReaderInterestLevel(getAcceleratedReaderInterestLevel(record));
			groupedWork.setAcceleratedReaderReadingLevel(getAcceleratedReaderReadingLevel(record));
			groupedWork.setAcceleratedReaderPointValue(getAcceleratedReaderPointLevel(record));
			groupedWork.setRelatedRecords(ilsRecords);
			groupedWork.setFormatInformation(ilsRecords);

			loadEContentSourcesAndProtectionTypes(groupedWork, econtentItems);

			loadOrderIds(groupedWork, record);

			groupedWork.addAllFields(getAllFields(record));

			groupedWork.addHoldings(printItems.size());
		}catch (Exception e){
			logger.error("Error updating grouped work for MARC record with identifier " + identifier, e);
		}
	}

	protected List<OnOrderItem> getOnOrderItems(String identifier, Record record){
		return new ArrayList<OnOrderItem>();
	}

	private void loadEditions(GroupedWorkSolr groupedWork, Record record, HashSet<IlsRecord> ilsRecords) {
		Set<String> editions = getFieldList(record, "250a");
		if (editions.size() > 0) {
			for (IlsRecord ilsRecord : ilsRecords) {
				String edition = editions.iterator().next();
				ilsRecord.setEdition(edition);
			}
		}
		groupedWork.addEditions(editions);
	}

	private void loadPhysicalDescription(GroupedWorkSolr groupedWork, Record record, HashSet<IlsRecord> ilsRecords) {
		Set<String> physicalDescriptions = getFieldList(record, "300abcefg:530abcd");
		if (physicalDescriptions.size() > 0){
			String physicalDescription = physicalDescriptions.iterator().next();
			for(IlsRecord ilsRecord : ilsRecords){
				ilsRecord.setPhysicalDescription(physicalDescription);
			}
		}
		groupedWork.addPhysical(physicalDescriptions);
	}

	protected void loadEContentSourcesAndProtectionTypes(GroupedWorkSolr groupedWork, List<EContentIlsItem> econtentItems) {
		//By default, do nothing
	}

	protected void loadLocalCallNumbers(GroupedWorkSolr groupedWork, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems) {
		//By default, do nothing.
	}

	protected void loadBibCallNumbers(GroupedWorkSolr groupedWork, Record record) {
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

	/**
	 * Break the marc record up into individual records based on the information in the record.
	 * Typically, we will get a single IlsRecord back.  However, we may get multiple records back
	 * if the original MARC record has both print and econtent records on it.
	 *
	 * @param groupedWork The groupes work that we are updating
	 * @param record The original MARC record
	 * @param printItems a list of print items from the MARC record
	 * @param econtentItems a list of econtent itmes from the MARC record
	 * @return A list of Ils Records that relate to the original marc
	 */
	protected HashSet<IlsRecord> loadScopedDataForMarcRecord(GroupedWorkSolr groupedWork, Record record, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems) {
		HashSet<IlsRecord> ilsRecords = new HashSet<IlsRecord>();
		String recordId = getFirstFieldVal(record, recordNumberTag + "a");
		String recordIdentifier = "ils:" + recordId;
		if (printItems.size() > 0) {
			IlsRecord printRecord = new IlsRecord();
			printRecord.setRecordId(recordIdentifier);
			printRecord.addItems(printItems);
			//Load formats for the print record
			loadPrintFormatInformation(printRecord, record);
			ilsRecords.add(printRecord);
			for (PrintIlsItem printItem : printItems) {
				if (printItem != null) {
					String itemInfo = recordIdentifier + "|" + printItem.getRelatedItemInfo();
					groupedWork.addRelatedItem(itemInfo);
					for (Scope scope : printItem.getRelatedScopes()) {
						ScopedWorkDetails scopedWorkDetails = groupedWork.getScopedWorkDetails().get(scope.getScopeName());
						scopedWorkDetails.addRelatedItem(itemInfo);
					}
				}else{
					logger.warn("Got an invalid print item in loadScopedDataForMarcRecord for " + recordId);
				}
			}
		}

		for (EContentIlsItem econtentItem : econtentItems) {
			//TODO: Check to see if there is already a record we want to use.
			IlsRecord econtentRecord = new IlsRecord();
			econtentRecord.setRecordId(econtentItem.getRecordIdentifier());
			econtentRecord.addItem(econtentItem);
			loadEContentFormatInformation(econtentRecord, econtentItem);
			String itemInfo = econtentItem.getRecordIdentifier() + "|" + econtentItem.getRelatedItemInfo();
			groupedWork.addRelatedItem(itemInfo);
			ilsRecords.add(econtentRecord);
			for (Scope scope : econtentItem.getRelatedScopes()) {
				ScopedWorkDetails scopedWorkDetails = groupedWork.getScopedWorkDetails().get(scope.getScopeName());
				scopedWorkDetails.addRelatedItem(itemInfo);
			}
		}
		return ilsRecords;
	}

	protected List<PrintIlsItem> getUnsuppressedPrintItems(Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<PrintIlsItem> unsuppressedItemRecords = new ArrayList<PrintIlsItem>();
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				PrintIlsItem ilsRecord = getPrintIlsRecord(itemField);
				if (ilsRecord != null) {
					unsuppressedItemRecords.add(ilsRecord);
				}
			}
		}
		return unsuppressedItemRecords;
	}

	protected EContentIlsItem getEContentIlsRecord(String identifier, DataField itemField){
		EContentIlsItem ilsRecord = new EContentIlsItem();

		ilsRecord.setDateCreated(getItemSubfieldData(dateCreatedSubfield, itemField));
		ilsRecord.setLocation(getItemSubfieldData(locationSubfieldIndicator, itemField));
		ilsRecord.setiType(getItemSubfieldData(iTypeSubfield, itemField));
		ilsRecord.setCallNumberPreStamp(getItemSubfieldData(callNumberPrestampSubfield, itemField));
		ilsRecord.setCallNumber(getItemSubfieldData(callNumberSubfield, itemField));
		ilsRecord.setCallNumberCutter(getItemSubfieldData(callNumberCutterSubfield, itemField));
		ilsRecord.setItemRecordNumber(getItemSubfieldData(itemRecordNumberSubfieldIndicator, itemField));

		Subfield eContentSubfield = itemField.getSubfield(eContentSubfieldIndicator);
		if (eContentSubfield != null){
			String eContentData = eContentSubfield.getData().trim();
			if (eContentData.indexOf(':') > 0) {
				String[] eContentFields = eContentData.split(":");
				//First element is the source, and we will always have at least the source and protection type
				ilsRecord.setSource(eContentFields[0].trim());
				ilsRecord.setProtectionType(eContentFields[1].trim().toLowerCase());
				if (eContentFields.length >= 3){
					ilsRecord.setSharing(eContentFields[2].trim().toLowerCase());
				}else{
					//Sharing depends on the location code
					if (ilsRecord.getLocation().startsWith("mdl")){
						ilsRecord.setSharing("shared");
					}else{
						ilsRecord.setSharing("library");
					}
				}

				//Remaining fields have variable definitions based on content that has been loaded over the past year or so
				if (eContentFields.length >= 4){
					//If the 4th field is numeric, it is the number of copies that can be checked out.
					if (Util.isNumeric(eContentFields[3].trim())){
						ilsRecord.setNumberOfCopies(eContentFields[3].trim());
						if (eContentFields.length >= 5){
							ilsRecord.setFilename(eContentFields[4].trim());
						}else{
							logger.warn("Filename for local econtent not specified " + eContentData + " " + identifier);
						}
						if (eContentFields.length >= 6){
							ilsRecord.setAcsId(eContentFields[5].trim());
						}
					}else{
						//Field 4 is the filename
						ilsRecord.setFilename(eContentFields[3].trim());
						if (eContentFields.length >= 5){
							ilsRecord.setAcsId(eContentFields[4].trim());
						}
					}
				}
			}
		}

		//Set record type
		String protectionType = ilsRecord.getProtectionType();
		if (protectionType.equals("acs") || protectionType.equals("drm")){
			ilsRecord.setRecordIdentifier("restricted_econtent:" + identifier); ;
		}else if (protectionType.equals("public domain") || protectionType.equals("free")){
			ilsRecord.setRecordIdentifier("public_domain_econtent:" + identifier);
		}else if (protectionType.equals("external")){
			ilsRecord.setRecordIdentifier("external_econtent:" + identifier);
		}else{
			logger.warn("Unknown protection type " + protectionType);
		}

		//Get the url if any
		Subfield urlSubfield = itemField.getSubfield(itemUrlSubfieldIndicator);
		if (urlSubfield != null){
			ilsRecord.setUrl(urlSubfield.getData().trim());
		}

		//Determine availability
		boolean available = false;
		if (protectionType.equals("external")){
			available = true;
		}else if (protectionType.equals("public domain") || protectionType.equals("free")){
			available = true;
		}else if (protectionType.equals("acs") || protectionType.equals("drm")){
			//TODO: Determine availability based on if it is checked out in the database
			available = true;
		}
		ilsRecord.setAvailable(available);

		//Determine which scopes this title belongs to
		for (Scope curScope : indexer.getScopes()){
			if (curScope.isEContentLocationPartOfScope(ilsRecord)){
				ilsRecord.addRelatedScope(curScope);
			}
		}

		//TODO: Determine the format, format category, and boost factor for this title
		return ilsRecord;
	}

	protected PrintIlsItem getPrintIlsRecord(DataField itemField) {
		PrintIlsItem ilsRecord = new PrintIlsItem();

		//Load base information from the Marc Record
		ilsRecord.setStatus(getItemSubfieldData(statusSubfieldIndicator, itemField));
		ilsRecord.setLocation(getItemSubfieldData(locationSubfieldIndicator, itemField));
		//if the status and location are null, we can assume this is not a valid item
		if (ilsRecord.getStatus() == null && ilsRecord.getLocation() == null){
			return null;
		}
		ilsRecord.setDateDue(getItemSubfieldData(dueDateSubfield, itemField));
		ilsRecord.setDateCreated(getItemSubfieldData(dateCreatedSubfield, itemField));
		ilsRecord.setiType(getItemSubfieldData(iTypeSubfield, itemField));
		ilsRecord.setLastYearCheckouts(getItemSubfieldData(lastYearCheckoutSubfield, itemField));
		ilsRecord.setYtdCheckouts(getItemSubfieldData(ytdCheckoutSubfield, itemField));
		ilsRecord.setTotalCheckouts(getItemSubfieldData(totalCheckoutSubfield, itemField));
		ilsRecord.setiCode2(getItemSubfieldData(iCode2Subfield, itemField));
		ilsRecord.setCallNumberPreStamp(getItemSubfieldData(callNumberPrestampSubfield, itemField));
		ilsRecord.setCallNumber(getItemSubfieldData(callNumberSubfield, itemField));
		ilsRecord.setCallNumberCutter(getItemSubfieldData(callNumberCutterSubfield, itemField));
		ilsRecord.setBarcode(getItemSubfieldData(barcodeSubfield, itemField));
		ilsRecord.setItemRecordNumber(getItemSubfieldData(itemRecordNumberSubfieldIndicator, itemField));

		//Determine Availability
		boolean available = false;
		if (getAvailabilityFromMarc){
			if (ilsRecord.getStatus() != null) {
				String status = ilsRecord.getStatus();
				String dueDate = ilsRecord.getDateDue() == null ? "" : ilsRecord.getDateDue();
				String availableStatus = "-dowju";
				if (availableStatus.indexOf(status.charAt(0)) >= 0) {
					if (dueDate.length() == 0) {
						available = true;
					}
				}
			}
		}else{
			if (ilsRecord.getBarcode() != null){
				available = availableItemBarcodes.contains(ilsRecord.getBarcode());
			}
		}
		ilsRecord.setAvailable(available);

		if (ilsRecord.getiType() != null && ilsRecord.getLocation() != null) {
			//Figure out which ptypes are compatible with this itype
			ilsRecord.setCompatiblePTypes(getCompatiblePTypes(ilsRecord.getiType(), ilsRecord.getLocation()));
			//Determine which scopes have access to this record
			for (Scope curScope : indexer.getScopes()) {
				if (curScope.isItemPartOfScope(ilsRecord.getLocation(), ilsRecord.getCompatiblePTypes())) {
					ilsRecord.addRelatedScope(curScope);
				}
			}
		}

		//Determine which localizations this record belongs to
		if (ilsRecord.getLocation() != null) {
			for (LocalizationInfo localizationInfo : indexer.getLocalizations()) {
				if (localizationInfo.isLocationCodeIncluded(ilsRecord.getLocation())) {
					ilsRecord.addRelatedLocalization(localizationInfo);
				}
			}
		}

		return ilsRecord;
	}

	private String getItemSubfieldData(char subfieldIndicator, DataField itemField) {
		if (subfieldIndicator == ' '){
			return null;
		}else {
			return itemField.getSubfield(subfieldIndicator) != null ? itemField.getSubfield(subfieldIndicator).getData().trim() : null;
		}
	}

	protected List<EContentIlsItem> getUnsuppressedEContentItems(String identifier, Record record){
		return new ArrayList<EContentIlsItem>();
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

	private void loadITypes(GroupedWorkSolr groupedWork, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems) {
		for (PrintIlsItem curItem : printItems){
			String location = curItem.getLocation();
			String iType = curItem.getiType();
			if (iType != null && location != null){
				String translatedIType = indexer.translateValue("itype", iType);
				ArrayList<String> relatedSubdomains = getLibrarySubdomainsForLocationCode(location);
				groupedWork.setIType(translatedIType, relatedSubdomains);
			}
		}
		for (EContentIlsItem curItem : econtentItems){
			String iType = curItem.getiType();
			String translatedIType = indexer.translateValue("itype", iType);
			String location = curItem.getLocation();
			if (iType != null && location != null){
				ArrayList<String> relatedSubdomains = getLibrarySubdomainsForLocationCode(location);
				groupedWork.setIType(translatedIType, relatedSubdomains);
			}
		}
	}

	private static SimpleDateFormat dateAddedFormatter = new SimpleDateFormat("yyMMdd");
	private void loadDateAdded(GroupedWorkSolr groupedWork, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems) {
		for (PrintIlsItem curItem : printItems){
			String locationCode = curItem.getLocation();
			String dateAddedStr = curItem.getDateCreated();
			if (locationCode != null && dateAddedStr != null){
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
		for (EContentIlsItem curItem : econtentItems){
			String locationCode = curItem.getLocation();
			String dateAddedStr = curItem.getDateCreated();
			if (locationCode != null && dateAddedStr != null){
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
		//First get the literary Forms from the 008.  These need translation
		LinkedHashSet<String> literaryForms = new LinkedHashSet<String>();
		try {
			String leader = record.getLeader().toString();

			ControlField ohOhEightField = (ControlField) record.getVariableField("008");
			ControlField ohOhSixField = (ControlField) record.getVariableField("006");

			// check the Leader at position 6 to determine the type of field
			char recordType = Character.toUpperCase(leader.charAt(6));
			char bibLevel = Character.toUpperCase(leader.charAt(7));
			// Figure out what material type the record is
			if (((recordType == 'A' || recordType == 'T') && (bibLevel == 'A' || bibLevel == 'C' || bibLevel == 'D' || bibLevel == 'M')) /* Books */
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
		if (literaryForms.size() > 1){
			//Uh oh, we have a problem
			logger.warn("Received multiple literary forms for a single marc record");
		}
		groupedWork.addLiteraryForms(indexer.translateCollection("literary_form", literaryForms));
		groupedWork.addLiteraryFormsFull(indexer.translateCollection("literary_form_full", literaryForms));

		//Now get literary forms from the subjects, these don't need translation
		HashMap<String, Integer> literaryFormsWithCount = new HashMap<String, Integer>();
		HashMap<String, Integer> literaryFormsFull = new HashMap<String, Integer>();
		//Check the subjects
		Set<String> subjectFormData = getFieldList(record, "650v:651v");
		for(String subjectForm : subjectFormData){
			subjectForm = Util.trimTrailingPunctuation(subjectForm);
			if (subjectForm.equalsIgnoreCase("Fiction")
					|| subjectForm.equalsIgnoreCase("Young adult fiction" )
					|| subjectForm.equalsIgnoreCase("Juvenile fiction" )
					|| subjectForm.equalsIgnoreCase("Junior fiction" )
					|| subjectForm.equalsIgnoreCase("Comic books, strips, etc")
					|| subjectForm.equalsIgnoreCase("Comic books,strips, etc")
					|| subjectForm.equalsIgnoreCase("Children's fiction" )
					|| subjectForm.equalsIgnoreCase("Fictional Works" )
					|| subjectForm.equalsIgnoreCase("Cartoons and comics" )
					|| subjectForm.equalsIgnoreCase("Folklore" )
					|| subjectForm.equalsIgnoreCase("Legends" )
					|| subjectForm.equalsIgnoreCase("Stories" )
					|| subjectForm.equalsIgnoreCase("Fantasy" )
					|| subjectForm.equalsIgnoreCase("Mystery fiction")
					|| subjectForm.equalsIgnoreCase("Romances")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
			}else if (subjectForm.equalsIgnoreCase("Biography")){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Non Fiction");
			}else if (subjectForm.equalsIgnoreCase("Novela juvenil")
					|| subjectForm.equalsIgnoreCase("Novela")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				addToMapWithCount(literaryFormsFull, "Novels");
			}else if (subjectForm.equalsIgnoreCase("Drama")
					|| subjectForm.equalsIgnoreCase("Dramas")
					|| subjectForm.equalsIgnoreCase("Juvenile drama")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				addToMapWithCount(literaryFormsFull, "Dramas");
			}else if (subjectForm.equalsIgnoreCase("Poetry")
					|| subjectForm.equalsIgnoreCase("Juvenile Poetry")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Poetry");
			}else if (subjectForm.equalsIgnoreCase("Humor")
					|| subjectForm.equalsIgnoreCase("Juvenile Humor")
					|| subjectForm.equalsIgnoreCase("Comedy")
					|| subjectForm.equalsIgnoreCase("Wit and humor")
					|| subjectForm.equalsIgnoreCase("Satire")
					|| subjectForm.equalsIgnoreCase("Humor, Juvenile")
					|| subjectForm.equalsIgnoreCase("Humour")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				addToMapWithCount(literaryFormsFull, "Humor, Satires, etc.");
			}else if (subjectForm.equalsIgnoreCase("Correspondence")
					){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Letters");
			}else if (subjectForm.equalsIgnoreCase("Short stories")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				addToMapWithCount(literaryFormsFull, "Short stories");
			}else if (subjectForm.equalsIgnoreCase("essays")
					){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Essays");
			}else if (subjectForm.equalsIgnoreCase("Personal narratives, American")
					|| subjectForm.equalsIgnoreCase("Personal narratives, Polish")
					|| subjectForm.equalsIgnoreCase("Personal narratives, Sudanese")
					|| subjectForm.equalsIgnoreCase("Personal narratives, Jewish")
					|| subjectForm.equalsIgnoreCase("Personal narratives")
					|| subjectForm.equalsIgnoreCase("Guidebooks")
					|| subjectForm.equalsIgnoreCase("Guide-books")
					|| subjectForm.equalsIgnoreCase("Handbooks, manuals, etc")
					|| subjectForm.equalsIgnoreCase("Problems, exercises, etc")
					|| subjectForm.equalsIgnoreCase("Case studies")
					|| subjectForm.equalsIgnoreCase("Handbooks")
					|| subjectForm.equalsIgnoreCase("Biographies")
					|| subjectForm.equalsIgnoreCase("Interviews")
					|| subjectForm.equalsIgnoreCase("Autobiography")
					|| subjectForm.equalsIgnoreCase("Cookbooks")
					|| subjectForm.equalsIgnoreCase("Dictionaries")
					|| subjectForm.equalsIgnoreCase("Encyclopedias")
					|| subjectForm.equalsIgnoreCase("Encyclopedias, Juvenile")
					|| subjectForm.equalsIgnoreCase("Dictionaries, Juvenile")
					|| subjectForm.equalsIgnoreCase("Nonfiction")
					|| subjectForm.equalsIgnoreCase("Non-fiction")
					|| subjectForm.equalsIgnoreCase("Juvenile non-fiction")
					|| subjectForm.equalsIgnoreCase("Maps")
					|| subjectForm.equalsIgnoreCase("Catalogs")
					|| subjectForm.equalsIgnoreCase("Recipes")
					|| subjectForm.equalsIgnoreCase("Diaries")
					|| subjectForm.equalsIgnoreCase("Designs and Plans")
					|| subjectForm.equalsIgnoreCase("Reference books")
					|| subjectForm.equalsIgnoreCase("Travel guide")
					|| subjectForm.equalsIgnoreCase("Textbook")
					|| subjectForm.equalsIgnoreCase("Atlas")
					|| subjectForm.equalsIgnoreCase("Atlases")
					|| subjectForm.equalsIgnoreCase("Study guides")
					){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Non Fiction");
			}else{
				if (!unknownSubjectForms.contains(subjectForm)){
					//logger.warn("Unknown subject form " + subjectForm);
					unknownSubjectForms.add(subjectForm);
				}
			}
		}
		groupedWork.addLiteraryForms(literaryFormsWithCount);
		groupedWork.addLiteraryFormsFull(literaryFormsFull);
	}
	private HashSet<String> unknownSubjectForms = new HashSet<String>();

	private void addToMapWithCount(HashMap<String, Integer> map, String elementToAdd){
		if (map.containsKey(elementToAdd)){
			map.put(elementToAdd, map.get(elementToAdd) + 1);
		}else{
			map.put(elementToAdd, 1);
		}
	}

	private void loadPublicationDetails(GroupedWorkSolr groupedWork, Record record, HashSet<IlsRecord> ilsRecords) {
		//Load publishers
		Set<String> publishers = this.getPublishers(record);
		groupedWork.addPublishers(publishers);
		if (publishers.size() > 0){
			String publisher = publishers.iterator().next();
			for(IlsRecord ilsRecord : ilsRecords){
				ilsRecord.setPublisher(publisher);
			}
		}

		//Load publication dates
		Set<String> publicationDates = this.getPublicationDates(record);
		groupedWork.addPublicationDates(publicationDates);
		if (publicationDates.size() > 0){
			String publicationDate = publicationDates.iterator().next();
			for(IlsRecord ilsRecord : ilsRecords){
				ilsRecord.setPublicationDate(publicationDate);
			}
		}

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

	private void loadLanguageDetails(GroupedWorkSolr groupedWork, Record record, HashSet<IlsRecord> ilsRecords) {
		Set <String> languages = getFieldList(record, "008[35-37]:041a:041d:041j");
		HashSet<String> translatedLanguages = new HashSet<String>();
		boolean isFirstLanguage = true;
		for (String language : languages){
			String tranlatedLanguage = indexer.translateValue("language", language);
			translatedLanguages.add(tranlatedLanguage);
			if (isFirstLanguage){
				for (IlsRecord ilsRecord : ilsRecords){
					ilsRecord.setLanguage(tranlatedLanguage);
				}
			}
			isFirstLanguage = false;
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

	private void loadPopularity(GroupedWorkSolr groupedWork, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems) {
		float popularity = 0;
		for (PrintIlsItem itemField : printItems){
			//Get number of times the title has been checked out
			String totalCheckoutsField = itemField.getTotalCheckouts();
			int totalCheckouts = 0;
			if (totalCheckoutsField != null){
				totalCheckouts = Integer.parseInt(totalCheckoutsField);
			}
			String ytdCheckoutsField = itemField.getYtdCheckouts();
			int ytdCheckouts = 0;
			if (ytdCheckoutsField != null){
				ytdCheckouts = Integer.parseInt(ytdCheckoutsField);
			}
			String lastYearCheckoutsField = itemField.getLastYearCheckouts();
			int lastYearCheckouts = 0;
			if (lastYearCheckoutsField != null){
				lastYearCheckouts = Integer.parseInt(lastYearCheckoutsField);
			}
			double itemPopularity = ytdCheckouts + .5 * (lastYearCheckouts) + .1 * (totalCheckouts - lastYearCheckouts - ytdCheckouts);
			//logger.debug("Popularity for item " + itemPopularity + " ytdCheckouts=" + ytdCheckouts + " lastYearCheckouts=" + lastYearCheckouts + " totalCheckouts=" + totalCheckouts);
			popularity += itemPopularity;
		}
		//TODO: Load popularity for eContent
		groupedWork.addPopularity(popularity);
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
		groupedWork.addAlternateTitles(this.getFieldList(record, "130adfgklnpst:240a:246a:700tnr:730adfgklnpst:740a"));
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

	protected void loadUsability(GroupedWorkSolr groupedWork, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems) {
		//Load a list of pTypes that can use this record based on loan rules
		for (PrintIlsItem curItem : printItems){
			String iType = curItem.getiType();
			String locationCode = curItem.getLocation();
			if (iType != null && locationCode != null){
				groupedWork.addCompatiblePTypes(getCompatiblePTypes(iType, locationCode));
			}
		}
	}

	protected boolean isItemSuppressed(DataField curItem) {
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

	protected void loadAvailability(GroupedWorkSolr groupedWork, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems) {
		//Calculate availability based on the record
		HashSet<String> availableAt = new HashSet<String>();
		HashSet<String> availableLocationCodes = new HashSet<String>();

		for (PrintIlsItem curItem : printItems){
			if (curItem.getLocation() != null){
				if (curItem.isAvailable()){
					availableAt.addAll(getLocationFacetsForLocationCode(curItem.getLocation()));
					availableLocationCodes.addAll(getRelatedLocationCodesForLocationCode(curItem.getLocation()));
					availableLocationCodes.addAll(getRelatedSubdomainsForLocationCode(curItem.getLocation()));
				}
			}
		}
		groupedWork.addAvailableLocations(availableAt, availableLocationCodes);
	}

	protected void loadOwnershipInformation(GroupedWorkSolr groupedWork, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems, List<OnOrderItem> onOrderItems) {
		HashSet<String> owningLibraries = new HashSet<String>();
		HashSet<String> owningLocations = new HashSet<String>();
		HashSet<String> owningLocationCodes = new HashSet<String>();
		for (PrintIlsItem curItem : printItems){
			String locationCode = curItem.getLocation();
			if (locationCode != null){
				ArrayList<String> owningLibrariesForLocationCode = getLibraryFacetsForLocationCode(locationCode);
				owningLibraries.addAll(owningLibrariesForLocationCode);
				ArrayList<String> owningLocationsForLocationCode = getLocationFacetsForLocationCode(locationCode);
				owningLocations.addAll(owningLocationsForLocationCode);
				owningLocationCodes.addAll(getRelatedLocationCodesForLocationCode(locationCode));
				owningLocationCodes.addAll(getRelatedSubdomainsForLocationCode(locationCode));

				loadAdditionalOwnershipInformation(groupedWork, curItem);
			}
			for (LocalizationInfo localizationInfo : curItem.getRelatedLocalizations()){
				owningLocations.add(localizationInfo.getFacetLabel());
				owningLocationCodes.add(localizationInfo.getLocationCodePrefix());
			}
		}
		for (OnOrderItem curOrderItem: onOrderItems){
			for (Scope curScope : curOrderItem.getRelatedScopes()){
				owningLocations.add(curScope.getFacetLabel() + " On Order");
			}
		}
		groupedWork.addOwningLibraries(owningLibraries);
		groupedWork.addOwningLocations(owningLocations);
		groupedWork.addOwningLocationCodesAndSubdomains(owningLocationCodes);
	}

	protected void loadAdditionalOwnershipInformation(GroupedWorkSolr groupedWork, PrintIlsItem printItem){

	}

	private HashSet<String> locationsWithoutLibraryFacets = new HashSet<String>();
	protected ArrayList<String> getLibraryFacetsForLocationCode(String locationCode) {
		locationCode = locationCode.toLowerCase();
		ArrayList<String> libraryFacets = new ArrayList<String>();
		for(String libraryCode : indexer.getLibraryFacetMap().keySet()){
			if (locationCode.startsWith(libraryCode)){
				libraryFacets.add(indexer.getLibraryFacetMap().get(libraryCode));
			}
		}
		if (libraryFacets.size() == 0){
			if (!locationsWithoutLibraryFacets.contains(locationCode)){
				logger.warn("Did not find any library facets for " + locationCode);
				locationsWithoutLibraryFacets.add(locationCode);
			}
		}
		return libraryFacets;
	}

	private HashSet<String> locationsWithoutLibraryOnlineFacets = new HashSet<String>();
	protected ArrayList<String> getLibraryOnlineFacetsForLocationCode(String locationCode) {
		locationCode = locationCode.toLowerCase();
		ArrayList<String> libraryOnlineFacets = new ArrayList<String>();
		for(String libraryCode : indexer.getLibraryOnlineFacetMap().keySet()){
			if (locationCode.startsWith(libraryCode)){
				libraryOnlineFacets.add(indexer.getLibraryOnlineFacetMap().get(libraryCode));
			}
		}
		if (libraryOnlineFacets.size() == 0){
			if (!locationsWithoutLibraryOnlineFacets.contains(locationCode)){
				logger.warn("Did not find any online library facets for " + locationCode);
				locationsWithoutLibraryOnlineFacets.add(locationCode);
			}
		}
		return libraryOnlineFacets;
	}

	private ArrayList<String> getRelatedSubdomainsForLocationCode(String locationCode) {
		locationCode = locationCode.toLowerCase();
		ArrayList<String> subdomains = new ArrayList<String>();
		for(String libraryCode : indexer.getSubdomainMap().keySet()){
			if (locationCode.startsWith(libraryCode)){
				subdomains.add(indexer.getSubdomainMap().get(libraryCode));
			}
		}
		if (subdomains.size() == 0){
			logger.warn("Did not find any subdomains for " + locationCode);
		}
		return subdomains;
	}

	protected ArrayList<String> getLibrarySubdomainsForLocationCode(String locationCode) {
		locationCode = locationCode.toLowerCase();
		ArrayList<String> librarySubdomains = new ArrayList<String>();
		for(String libraryCode : indexer.getSubdomainMap().keySet()){
			if (locationCode.startsWith(libraryCode)){
				librarySubdomains.add(indexer.getSubdomainMap().get(libraryCode));
			}
		}
		if (librarySubdomains.size() == 0){
			logger.warn("Did not find any library subdomains for " + locationCode);
		}
		return librarySubdomains;
	}

	private HashSet<String> locationCodesWithoutFacets = new HashSet<String>();
	private ArrayList<String> getLocationFacetsForLocationCode(String locationCode) {
		locationCode = locationCode.toLowerCase();
		ArrayList<String> locationFacets = new ArrayList<String>();
		if (locationCode == null || locationCode.length() == 0){
			return locationFacets;
		}
		locationCode = locationCode.toLowerCase();
		for(String ilsCode : indexer.getLocationMap().keySet()){
			if (locationCode.startsWith(ilsCode)){
				locationFacets.add(indexer.getLocationMap().get(ilsCode));
			}
		}
		if (locationFacets.size() == 0){
			if (!locationCodesWithoutFacets.contains(locationCode)){
				logger.debug("Did not find any location facets for '" + locationCode + "'");
				locationCodesWithoutFacets.add(locationCode);
			}
		}
		return locationFacets;
	}

	protected ArrayList<String> getRelatedLocationCodesForLocationCode(String locationCode){
		locationCode = locationCode.toLowerCase();
		ArrayList<String> locationFacets = new ArrayList<String>();
		if (locationCode == null || locationCode.length() == 0){
			return locationFacets;
		}
		for(String ilsCode : indexer.getLocationMap().keySet()){
			if (locationCode.startsWith(ilsCode)){
				locationFacets.add(ilsCode);
			}
		}
		return locationFacets;
	}

	private ArrayList<String> getIlsCodesForDetailedLocationCode(String locationCode) {
		locationCode = locationCode.toLowerCase();
		ArrayList<String> locationCodes = new ArrayList<String>();
		for(String ilsCode : indexer.getLocationMap().keySet()){
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
		}else{
			logger.debug("Did not get cached ptype compatibility for " + cacheKey);
		}
		LinkedHashSet<String> result = calculateCompatiblePTypes(iType, locationCode);

		//logger.debug("  " + result.size() + " ptypes can use this");
		ptypesByItypeAndLocation.put(cacheKey, result);
		return result;
	}

	private LinkedHashSet<String> calculateCompatiblePTypes(String iType, String locationCode) {
		//logger.debug("getCompatiblePTypes for " + cacheKey);
		LinkedHashSet<String> result = new LinkedHashSet<String>();
		if (!Util.isNumeric(iType)){
			logger.warn("IType " + iType + " was not numeric marking as incompatible with everything");
			return result;
		}
		Long iTypeLong = Long.parseLong(iType);
		//Loop through all patron types to see if the item is holdable
		for (Long pType : pTypes){
			//logger.debug("  Checking pType " + pType);
			//Loop through the loan rules to see if this itype can be used based on the location code
			for (LoanRuleDeterminer curDeterminer : loanRuleDeterminers){
				//logger.debug("    " + curDeterminer.getRowNumber() + " matches location");
				if (curDeterminer.getItemType().equals("999") || curDeterminer.getItemTypes().contains(iTypeLong)){
					//logger.debug("    " + curDeterminer.getRowNumber() + " matches iType");
					if (curDeterminer.getPatronType().equals("999") || curDeterminer.getPatronTypes().contains(pType)){
						//logger.debug("    " + curDeterminer.getRowNumber() + " matches pType");
						//Make sure the location matches
						if (curDeterminer.matchesLocation(locationCode)){
							LoanRule loanRule = loanRules.get(curDeterminer.getLoanRuleId());
							if (loanRule.getHoldable().equals(Boolean.TRUE)){
								if (curDeterminer.getPatronTypes().equals("999")){
									result.add("all");
									return result;
								}else{
									result.add(pType.toString());
								}
							}
							//We got a match, stop processing
							//logger.debug("    using determiner " + curDeterminer.getRowNumber() + " for ptype " + pType);
							break;
						}
					}
				}
			}
		}
		return result;
	}

	public String getAcceleratedReaderReadingLevel(Record marcRecord) {
		String result;
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

	/**
	 * Loops through all datafields and creates a field for "all fields"
	 * searching. Shameless stolen from Vufind Indexer Custom Code
	 *
	 * @param lowerBound
	 *          - the "lowest" marc field to include (e.g. 100)
	 * @param upperBound
	 *          - one more than the "highest" marc field to include (e.g. 900 will
	 *          include up to 899).
	 * @return a string containing ALL subfields of ALL marc fields within the
	 *         range indicated by the bound string arguments.
	 */
	@SuppressWarnings("unchecked")
	public String getAllSearchableFields(Record record, int lowerBound, int upperBound) {
		StringBuilder buffer = new StringBuilder("");

		List<DataField> fields = record.getDataFields();
		for (DataField field : fields) {
			// Get all fields starting with the 100 and ending with the 839
			// This will ignore any "code" fields and only use textual fields
			int tag = localParseInt(field.getTag(), -1);
			if ((tag >= lowerBound) && (tag < upperBound)) {
				// Loop through subfields
				List<Subfield> subfields = field.getSubfields();
				for (Subfield subfield : subfields) {
					if (buffer.length() > 0)
						buffer.append(" ");
					buffer.append(subfield.getData());
				}
			}
		}

		return buffer.toString();
	}

	/**
	 * return an int for the passed string
	 *
	 * @param str The String value of the integer to prompt
	 * @param defValue
	 *          - default value, if string doesn't parse into int
	 */
	private int localParseInt(String str, int defValue) {
		int value = defValue;
		try {
			value = Integer.parseInt(str);
		} catch (NumberFormatException nfe) {
			// provided value is not valid numeric string
			// Ignoring it and moving happily on.
		}
		return (value);
	}

	public String getAcceleratedReaderPointLevel(Record marcRecord) {
		try {
			String result;
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
	 */
	public abstract void loadPrintFormatInformation(IlsRecord ilsRecord, Record record);

	/**
	 * Load information about eContent formats.
	 *
	 * @param econtentRecord
	 * @param econtentItem
	 */
	protected void loadEContentFormatInformation(IlsRecord econtentRecord, EContentIlsItem econtentItem) {

	}

	private char getSubfieldIndicatorFromConfig(Ini configIni, String subfieldName) {
		String subfieldString = configIni.get("Reindex", subfieldName);
		char subfield = ' ';
		if (subfieldString != null && subfieldString.length() > 0)  {
			subfield = subfieldString.charAt(0);
		}
		return subfield;
	}
}
