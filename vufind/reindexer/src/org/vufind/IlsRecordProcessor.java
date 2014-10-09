package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.marc.*;

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
import java.util.regex.Pattern;

/**
 * Processes data that was exported from the ILS.
 *
 * VuFind-Plus
 * User: Mark Noble
 * Date: 11/26/13
 * Time: 9:30 AM
 */
public abstract class IlsRecordProcessor extends MarcRecordProcessor {
	private String individualMarcPath;

	protected String recordNumberTag;
	protected String itemTag;
	protected char barcodeSubfield;
	protected char statusSubfieldIndicator;
	protected char collectionSubfield;
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
		super(indexer, logger);
		String marcRecordPath = configIni.get("Reindex", "marcPath");
		individualMarcPath = configIni.get("Reindex", "individualMarcPath");

		itemTag = configIni.get("Reindex", "itemTag");
		recordNumberTag = configIni.get("Reindex", "recordNumberTag");
		useEContentSubfield = Boolean.parseBoolean(configIni.get("Reindex", "useEContentSubfield"));
		eContentSubfieldIndicator = getSubfieldIndicatorFromConfig(configIni, "eContentSubfield");
		barcodeSubfield = getSubfieldIndicatorFromConfig(configIni, "barcodeSubfield");
		collectionSubfield = getSubfieldIndicatorFromConfig(configIni, "collectionSubfield");
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

	@Override
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

	@Override
	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		try{
			//First load a list of print items and econtent items from the MARC record since they are needed to handle
			//Scoping and availability of records.
			List<PrintIlsItem> printItems = getUnsuppressedPrintItems(identifier, record);
			List<EContentIlsItem> econtentItems = getUnsuppressedEContentItems(identifier, record);
			List<OnOrderItem> onOrderItems = getOnOrderItems(identifier, record);

			//Break the MARC record up based on item information and load data that is scoped
			//i.e. formats, iTypes, date added to catalog, etc
			HashSet<IlsRecord> ilsRecords = loadScopedDataForMarcRecord(groupedWork, record, printItems, econtentItems, onOrderItems);

			//Do updates based on the overall bib (shared regardless of scoping)
			updateGroupedWorkSolrDataBasedOnStandardMarcData(groupedWork, record);

			//Special processing for ILS Records
			String fullDescription = Util.getCRSeparatedString(getFieldList(record, "520a"));
			for (IlsRecord ilsRecord : ilsRecords) {
				groupedWork.addDescription(fullDescription, ilsRecord.getPrimaryFormat());
			}
			loadEditions(groupedWork, record, ilsRecords);
			loadPhysicalDescription(groupedWork, record, ilsRecords);
			loadLanguageDetails(groupedWork, record, ilsRecords);
			loadPublicationDetails(groupedWork, record, ilsRecords);

			//Do updates based on items
			loadOwnershipInformation(groupedWork, printItems, econtentItems, onOrderItems);
			loadAvailability(groupedWork, printItems, econtentItems);
			loadUsability(groupedWork, printItems, econtentItems);
			loadPopularity(groupedWork, printItems, econtentItems);
			loadDateAdded(groupedWork, printItems, econtentItems);
			loadITypes(groupedWork, printItems, econtentItems);
			loadLocalCallNumbers(groupedWork, printItems, econtentItems);
			groupedWork.addBarcodes(getFieldList(record, itemTag + barcodeSubfield));
			groupedWork.setRelatedRecords(ilsRecords);
			groupedWork.setFormatInformation(ilsRecords);

			loadEContentSourcesAndProtectionTypes(groupedWork, econtentItems);

			loadOrderIds(groupedWork, record);

			groupedWork.addHoldings(printItems.size());
		}catch (Exception e){
			logger.error("Error updating grouped work for MARC record with identifier " + identifier, e);
		}
	}

	protected List<OnOrderItem> getOnOrderItems(String identifier, Record record){
		return new ArrayList<OnOrderItem>();
	}

	protected void loadEContentSourcesAndProtectionTypes(GroupedWorkSolr groupedWork, List<EContentIlsItem> econtentItems) {
		//By default, do nothing
	}

	protected void loadLocalCallNumbers(GroupedWorkSolr groupedWork, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems) {
		//By default, do nothing.
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
	 * @param groupedWork The grouped work that we are updating
	 * @param record The original MARC record
	 * @param printItems a list of print items from the MARC record
	 * @param econtentItems a list of econtent items from the MARC record
	 * @param onOrderItems a list of items that are on order
	 * @return A list of Ils Records that relate to the original marc
	 */
	protected HashSet<IlsRecord> loadScopedDataForMarcRecord(GroupedWorkSolr groupedWork, Record record, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems, List<OnOrderItem> onOrderItems) {
		HashSet<IlsRecord> ilsRecords = new HashSet<IlsRecord>();
		String recordId = getFirstFieldVal(record, recordNumberTag + "a");
		String recordIdentifier = "ils:" + recordId;
		if (printItems.size() > 0 || onOrderItems.size() > 0) {
			IlsRecord printRecord = new IlsRecord();
			printRecord.setRecordId(recordIdentifier);
			printRecord.addItems(printItems);
			printRecord.addRelatedOrderItems(onOrderItems);
			/*if (onOrderItems.size() > 0) {
				logger.warn("Record " + recordId + " " + groupedWork.getDisplayTitle() + " has " + onOrderItems.size() + " order records");
			}*/
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

			for (OnOrderItem orderItem : onOrderItems) {
				if (orderItem != null) {
					String itemInfo = orderItem.getRecordIdentifier() + "|" + orderItem.getRelatedItemInfo();
					groupedWork.addRelatedItem(itemInfo);
					for (Scope scope : orderItem.getRelatedScopes()) {
						ScopedWorkDetails scopedWorkDetails = groupedWork.getScopedWorkDetails().get(scope.getScopeName());
						scopedWorkDetails.addRelatedItem(itemInfo);
					}
				} else {
					logger.warn("Got an invalid order item in loadScopedDataForMarcRecord for " + recordId);
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

	protected List<PrintIlsItem> getUnsuppressedPrintItems(String identifier, Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<PrintIlsItem> unsuppressedItemRecords = new ArrayList<PrintIlsItem>();
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				PrintIlsItem ilsRecord = getPrintIlsRecord(itemField);
				ilsRecord.setRecordIdentifier(identifier);
				unsuppressedItemRecords.add(ilsRecord);
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
		if (collectionSubfield != ' ') {
			ilsRecord.setCollection(getItemSubfieldData(collectionSubfield, itemField));
		}

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
			ilsRecord.setRecordIdentifier("restricted_econtent:" + identifier);
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
		ilsRecord.setCallNumberPreStamp(getItemSubfieldDataWithoutTrimming(callNumberPrestampSubfield, itemField));
		ilsRecord.setCallNumber(getItemSubfieldDataWithoutTrimming(callNumberSubfield, itemField));
		ilsRecord.setCallNumberCutter(getItemSubfieldDataWithoutTrimming(callNumberCutterSubfield, itemField));
		ilsRecord.setBarcode(getItemSubfieldData(barcodeSubfield, itemField));
		ilsRecord.setItemRecordNumber(getItemSubfieldData(itemRecordNumberSubfieldIndicator, itemField));
		ilsRecord.setCollection(getItemSubfieldData(collectionSubfield, itemField));

		//Determine Availability
		boolean available = false;
		if (getAvailabilityFromMarc){
			if (ilsRecord.getStatus() != null) {
				available = isItemAvailable(ilsRecord);
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
		}
		//Determine which scopes have access to this record
		for (Scope curScope : indexer.getScopes()) {
			if (curScope.isItemPartOfScope(ilsRecord.getLocation(), ilsRecord.getCompatiblePTypes())) {
				ilsRecord.addRelatedScope(curScope);
			}
		}

		return ilsRecord;
	}

	protected abstract boolean isItemAvailable(PrintIlsItem ilsRecord);

	private String getItemSubfieldData(char subfieldIndicator, DataField itemField) {
		if (subfieldIndicator == ' '){
			return null;
		}else {
			return itemField.getSubfield(subfieldIndicator) != null ? itemField.getSubfield(subfieldIndicator).getData().trim() : null;
		}
	}

	private String getItemSubfieldDataWithoutTrimming(char subfieldIndicator, DataField itemField) {
		if (subfieldIndicator == ' '){
			return null;
		}else {
			return itemField.getSubfield(subfieldIndicator) != null ? itemField.getSubfield(subfieldIndicator).getData() : null;
		}
	}

	protected List<EContentIlsItem> getUnsuppressedEContentItems(String identifier, Record record){
		return new ArrayList<EContentIlsItem>();
	}

	private void loadITypes(GroupedWorkSolr groupedWork, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems) {
		for (PrintIlsItem curItem : printItems){
			String location = curItem.getLocation();
			String iType = curItem.getiType();
			if (iType != null && location != null){
				String translatedIType = indexer.translateValue("itype", iType);
				if (translatedIType != null) {
					ArrayList<String> relatedSubdomains = getLibrarySubdomainsForLocationCode(location);
					ArrayList<String> relatedLocations = getRelatedLocationCodesForLocationCode(location);
					groupedWork.setIType(translatedIType, relatedSubdomains, relatedLocations);
				}
			}
		}
		for (EContentIlsItem curItem : econtentItems){
			String iType = curItem.getiType();
			String translatedIType = indexer.translateValue("itype", iType);
			String location = curItem.getLocation();
			if (iType != null && location != null){
				ArrayList<String> relatedSubdomains = getLibrarySubdomainsForLocationCode(location);
				ArrayList<String> relatedLocations = getRelatedLocationCodesForLocationCode(location);
				groupedWork.setIType(translatedIType, relatedSubdomains, relatedLocations);
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
		return false;
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
			for (Scope curScope : curItem.getRelatedScopes()){
				if (curScope.isLocationScope() && curScope.isLocationCodeIncludedDirectly(locationCode)) {
					if (!owningLocations.contains(curScope.getFacetLabel())) {
						owningLocations.add(curScope.getFacetLabel());
					}
				}
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
			Pattern libraryCodePattern = Pattern.compile(libraryCode);
			if (libraryCodePattern.matcher(locationCode).lookingAt()){
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
			Pattern libraryCodePattern = Pattern.compile(libraryCode);
			if (libraryCodePattern.matcher(locationCode).lookingAt()){
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
			Pattern libraryCodePattern = Pattern.compile(libraryCode);
			if (libraryCodePattern.matcher(locationCode).lookingAt()){
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
			Pattern libraryCodePattern = Pattern.compile(libraryCode);
			if (libraryCodePattern.matcher(locationCode).lookingAt()){
				librarySubdomains.add(indexer.getSubdomainMap().get(libraryCode));
			}
		}
		if (librarySubdomains.size() == 0){
			logger.warn("Did not find any library subdomains for " + locationCode);
		}
		return librarySubdomains;
	}

	private HashSet<String> locationCodesWithoutFacets = new HashSet<String>();
	private HashMap<String, ArrayList<String>> locationFacetsForLocationCode = new HashMap<String, ArrayList<String>>();
	private ArrayList<String> getLocationFacetsForLocationCode(String locationCode) {
		locationCode = locationCode.toLowerCase();
		if (locationFacetsForLocationCode.containsKey(locationCode)){
			return locationFacetsForLocationCode.get(locationCode);
		}
		ArrayList<String> locationFacets = new ArrayList<String>();
		if (locationCode == null || locationCode.length() == 0){
			locationFacetsForLocationCode.put(locationCode, locationFacets);
			return locationFacets;
		}
		locationCode = locationCode.toLowerCase();
		for(String ilsCode : indexer.getLocationMap().keySet()){
			Pattern libraryCodePattern = Pattern.compile(ilsCode);
			if (libraryCodePattern.matcher(locationCode).lookingAt()){
				locationFacets.add(indexer.getLocationMap().get(ilsCode));
			}
		}
		if (locationFacets.size() == 0){
			if (!locationCodesWithoutFacets.contains(locationCode)){
				logger.debug("Did not find any location facets for '" + locationCode + "'");
				locationCodesWithoutFacets.add(locationCode);
			}
		}
		locationFacetsForLocationCode.put(locationCode, locationFacets);
		return locationFacets;
	}

	protected HashMap<String, ArrayList> relatedLocationCodesForLocationCode = new HashMap<String, ArrayList>();
	protected ArrayList<String> getRelatedLocationCodesForLocationCode(String locationCode){
		locationCode = locationCode.toLowerCase();
		if (relatedLocationCodesForLocationCode.containsKey(locationCode)){
			return relatedLocationCodesForLocationCode.get(locationCode);
		}
		ArrayList<String> locationFacets = new ArrayList<String>();
		if (locationCode == null || locationCode.length() == 0){
			relatedLocationCodesForLocationCode.put(locationCode, locationFacets);
			return locationFacets;
		}
		for(String ilsCode : indexer.getLocationMap().keySet()){
			Pattern libraryCodePattern = Pattern.compile(ilsCode);
			if (libraryCodePattern.matcher(locationCode).lookingAt()){
				locationFacets.add(ilsCode);
			}
		}
		relatedLocationCodesForLocationCode.put(locationCode, locationFacets);
		return locationFacets;
	}

	protected HashMap<String, ArrayList> ilsCodesForDetailedLocationCode = new HashMap<String, ArrayList>();
	private ArrayList<String> getIlsCodesForDetailedLocationCode(String locationCode) {
		locationCode = locationCode.toLowerCase();
		if (ilsCodesForDetailedLocationCode.containsKey(locationCode)){
			return ilsCodesForDetailedLocationCode.get(locationCode);
		}
		ArrayList<String> locationCodes = new ArrayList<String>();
		for(String ilsCode : indexer.getLocationMap().keySet()){
			Pattern locationPattern = Pattern.compile(ilsCode);
			if (locationPattern.matcher(locationCode).lookingAt()){
				locationCodes.add(ilsCode);
			}
		}
		ilsCodesForDetailedLocationCode.put(locationCode, locationCodes);
		return locationCodes;
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
