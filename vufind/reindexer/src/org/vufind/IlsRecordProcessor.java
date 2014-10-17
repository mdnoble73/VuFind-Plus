package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.marc.*;

import java.io.File;
import java.io.FileInputStream;
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

	private static boolean holdsDataLoaded = false;
	private static HashMap<String, Integer> numberOfHoldsByIdentifier = new HashMap<String, Integer>();

	/*private static boolean availabilityDataLoaded = false;
	private static boolean getAvailabilityFromMarc = true;
	private static TreeSet<String> availableItemBarcodes = new TreeSet<String>();*/

	public IlsRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, Logger logger) {
		super(indexer, logger);
		//String marcRecordPath = configIni.get("Reindex", "marcPath");
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

		//loadAvailableItemBarcodes(marcRecordPath, logger);
		loadLoanRuleInformation(vufindConn, logger);
		loadHoldsByIdentifier(vufindConn, logger);
	}

	private void loadHoldsByIdentifier(Connection vufindConn, Logger logger) {
		if (!holdsDataLoaded){
			try{
				PreparedStatement loadHoldsStmt = vufindConn.prepareStatement("SELECT ilsId, numHolds from ils_hold_summary", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet holdsRS = loadHoldsStmt.executeQuery();
				while (holdsRS.next()) {
					numberOfHoldsByIdentifier.put(holdsRS.getString("ilsId"), holdsRS.getInt("numHolds"));
				}

			} catch (Exception e){
				logger.error("Unable to load hold data", e);
			}
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

	/*private static void loadAvailableItemBarcodes(String marcRecordPath, Logger logger) {
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
	}*/

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
			loadPopularity(groupedWork, identifier, printItems, econtentItems, onOrderItems);
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
		String itemStatus = getItemStatus(itemField);
		ilsRecord.setStatus(itemStatus);
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
		//if (getAvailabilityFromMarc){
			if (ilsRecord.getStatus() != null) {
				available = isItemAvailable(ilsRecord);
			}
		/*}else{
			if (ilsRecord.getBarcode() != null){
				available = availableItemBarcodes.contains(ilsRecord.getBarcode());
			}
		}*/
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

	protected String getItemStatus(DataField itemField){
		return getItemSubfieldData(statusSubfieldIndicator, itemField)    ;
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

	protected void loadPopularity(GroupedWorkSolr groupedWork, String recordIdentifier, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems, List<OnOrderItem> onOrderItems) {
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

		//Add popularity based on the number of holds
		//Active holds indicate that a title is more interesting so we will count each hold at double value
		popularity += 2 * getIlsHoldsForTitle(recordIdentifier);

		//Add popularity based on the number of order records.
		//Since titles that are on order don't have checkouts (or as many checkouts), give them a boost to improve relevance
		popularity += 5 * onOrderItems.size();

		//TODO: Load popularity for eContent
		groupedWork.addPopularity(popularity);
	}

	private int getIlsHoldsForTitle(String recordIdentifier) {
		if (numberOfHoldsByIdentifier.containsKey(recordIdentifier)){
			return numberOfHoldsByIdentifier.get(recordIdentifier);
		}else {
			return 0;
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
	/**
	 * Determine Record Format(s)
	 */
	public void loadPrintFormatInformation(IlsRecord ilsRecord, Record record){
		if (ilsRecord.getRelatedItems().size() > 0 || ilsRecord.getRelatedOrderItems().size() > 0){
			Set<String> printFormats = new LinkedHashSet<String>();

			String leader = record.getLeader().toString();
			char leaderBit;
			ControlField fixedField = (ControlField) record.getVariableField("008");

			// check for music recordings quickly so we can figure out if it is music
			// for category (need to do here since checking what is on the Compact
			// Disc/Phonograph, etc is difficult).
			if (leader.length() >= 6) {
				leaderBit = leader.charAt(6);
				switch (Character.toUpperCase(leaderBit)) {
					case 'J':
						printFormats.add("MusicRecording");
						break;
				}
			}
			getFormatFromPublicationInfo(record, printFormats);
			getFormatFromNotes(record, printFormats);
			getFormatFromEdition(record, printFormats);
			getFormatFromPhysicalDescription(record, printFormats);
			getFormatFromSubjects(record, printFormats);
			getFormatFrom007(record, printFormats);
			getFormatFromTitle(record, printFormats);
			getFormatFromLeader(printFormats, leader, fixedField);

			if (printFormats.size() == 0){
				logger.debug("Did not get any formats for print record " + ilsRecord.getRecordId());
			}

			filterPrintFormats(printFormats);

			HashSet<String> translatedFormats = indexer.translateCollection("format", printFormats);
			HashSet<String> translatedFormatCategories = indexer.translateCollection("format_category", printFormats);
			ilsRecord.addFormats(translatedFormats);
			ilsRecord.addFormatCategories(translatedFormatCategories);
			Long formatBoost = 0L;
			HashSet<String> formatBoosts = indexer.translateCollection("format_boost", printFormats);
			for (String tmpFormatBoost : formatBoosts){
				Long tmpFormatBoostLong = Long.parseLong(tmpFormatBoost);
				if (tmpFormatBoostLong > formatBoost){
					formatBoost = tmpFormatBoostLong;
				}
			}
			ilsRecord.setFormatBoost(formatBoost);
		}
	}

	private void filterPrintFormats(Set<String> printFormats) {
		if (printFormats.contains("Video") && printFormats.contains("DVD")){
			printFormats.remove("Video");
		}else if (printFormats.contains("SoundDisc") && printFormats.contains("SoundRecording")){
			printFormats.remove("SoundRecording");
		}else if (printFormats.contains("SoundCassette") && printFormats.contains("SoundRecording")){
			printFormats.remove("SoundRecording");
		}else if (printFormats.contains("Playaway") && printFormats.contains("SoundRecording")){
			printFormats.remove("SoundRecording");
		}else if (printFormats.contains("Book") && printFormats.contains("LargePrint")){
			printFormats.remove("Book");
		}else if (printFormats.contains("Book") && printFormats.contains("Manuscript")){
			printFormats.remove("Book");
		}else if (printFormats.contains("Book") && printFormats.contains("GraphicNovel")){
			printFormats.remove("Book");
		}/*else if (printFormats.size() > 1){
			return;
		}*/
	}

	private void getFormatFromTitle(Record record, Set<String> printFormats) {
		String titleMedium = getFirstFieldVal(record, "245h");
		if (titleMedium != null){
			titleMedium = titleMedium.toLowerCase();
			if (titleMedium.contains("sound recording-cass")){
				printFormats.add("SoundCassette");
			}else if (titleMedium.contains("large print")){
				printFormats.add("LargePrint");
			}
		}
		String titlePart = getFirstFieldVal(record, "245p");
		if (titlePart != null){
			titlePart = titlePart.toLowerCase();
			if (titlePart.contains("sound recording-cass")){
				printFormats.add("SoundCassette");
			}else if (titlePart.contains("large print")){
				printFormats.add("LargePrint");
			}
		}
	}

	private void getFormatFromPublicationInfo(Record record, Set<String> result) {
		// check for playaway in 260|b
		DataField sysDetailsNote = (DataField) record.getVariableField("260");
		if (sysDetailsNote != null) {
			if (sysDetailsNote.getSubfield('b') != null) {
				String sysDetailsValue = sysDetailsNote.getSubfield('b').getData()
						.toLowerCase();
				if (sysDetailsValue.contains("playaway")) {
					result.add("Playaway");
				}
			}
		}
	}

	private void getFormatFromEdition(Record record, Set<String> result) {
		// Check for large print book (large format in 650, 300, or 250 fields)
		// Check for blu-ray in 300 fields
		DataField edition = (DataField) record.getVariableField("250");
		if (edition != null) {
			if (edition.getSubfield('a') != null) {
				String editionData = edition.getSubfield('a').getData().toLowerCase();
				if (editionData.contains("large type") || editionData.contains("large print")) {
					result.add("LargePrint");
				}
			}
		}
	}

	private void getFormatFromPhysicalDescription(Record record, Set<String> result) {
		@SuppressWarnings("unchecked")
		List<DataField> physicalDescription = getDataFields(record, "300");
		if (physicalDescription != null) {
			Iterator<DataField> fieldsIter = physicalDescription.iterator();
			DataField field;
			while (fieldsIter.hasNext()) {
				field = fieldsIter.next();
				@SuppressWarnings("unchecked")
				List<Subfield> subFields = field.getSubfields();
				for (Subfield subfield : subFields) {
					String physicalDescriptionData = subfield.getData().toLowerCase();
					if (physicalDescriptionData.contains("large type") || physicalDescriptionData.contains("large print")) {
						result.add("LargePrint");
					} else if (physicalDescriptionData.contains("bluray") || physicalDescriptionData.contains("blu-ray")) {
						result.add("Blu-ray");
					} else if (physicalDescriptionData.contains("computer optical disc")) {
						result.add("Software");
					} else if (physicalDescriptionData.contains("sound cassettes")) {
						result.add("SoundCassette");
					} else if (physicalDescriptionData.contains("sound discs")) {
						result.add("SoundDisc");
					}
					//Since this is fairly generic, only use it if we have no other formats yet
					if (result.size() == 0 && physicalDescriptionData.matches("^.*?\\d+\\s+(p\\.|pages).*$")) {
						result.add("Book");
					}
				}
			}
		}
	}

	private void getFormatFromNotes(Record record, Set<String> result) {
		// Check for formats in the 538 field
		DataField sysDetailsNote2 = (DataField) record.getVariableField("538");
		if (sysDetailsNote2 != null) {
			if (sysDetailsNote2.getSubfield('a') != null) {
				String sysDetailsValue = sysDetailsNote2.getSubfield('a').getData().toLowerCase();
				if (sysDetailsValue.contains("playaway")) {
					result.add("Playaway");
				} else if (sysDetailsValue.contains("kinect sensor")) {
					result.add("Kinect");
				} else if (sysDetailsValue.contains("xbox")) {
					result.add("Xbox360");
				} else if (sysDetailsValue.contains("playstation 3")) {
					result.add("PlayStation3");
				} else if (sysDetailsValue.contains("playstation")) {
					result.add("PlayStation");
				} else if (sysDetailsValue.contains("nintendo wii")) {
					result.add("Wii");
				} else if (sysDetailsValue.contains("directx")) {
					result.add("WindowsGame");
				} else if (sysDetailsValue.contains("bluray") || sysDetailsValue.contains("blu-ray")) {
					result.add("Blu-ray");
				} else if (sysDetailsValue.contains("dvd")) {
					result.add("DVD");
				} else if (sysDetailsValue.contains("vertical file")) {
					result.add("VerticalFile");
				}
			}
		}

		// Check for formats in the 500 tag
		DataField noteField = (DataField) record.getVariableField("500");
		if (noteField != null) {
			if (noteField.getSubfield('a') != null) {
				String noteValue = noteField.getSubfield('a').getData().toLowerCase();
				if (noteValue.contains("vertical file")) {
					result.add("VerticalFile");
				}
			}
		}
	}

	private void getFormatFromSubjects(Record record, Set<String> result) {
		@SuppressWarnings("unchecked")
		List<DataField> topicalTerm = getDataFields(record, "650");
		if (topicalTerm != null) {
			Iterator<DataField> fieldsIter = topicalTerm.iterator();
			DataField field;
			while (fieldsIter.hasNext()) {
				field = fieldsIter.next();
				@SuppressWarnings("unchecked")
				List<Subfield> subfields = field.getSubfields();
				for (Subfield subfield : subfields) {
					if (subfield.getCode() == 'a'){
						String subfieldData = subfield.getData().toLowerCase();
						if (subfieldData.contains("large type")) {
							result.add("LargePrint");
						}else if (subfieldData.contains("playaway")) {
							result.add("Playaway");
						}else if (subfieldData.contains("graphic novel")) {
							boolean okToAdd = false;
							if (field.getSubfield('v') != null){
								String subfieldVData = field.getSubfield('v').getData().toLowerCase();
								if (!subfieldVData.contains("Television adaptation")){
									okToAdd = true;
								}else{
									System.out.println("Not including graphic novel format");
								}
							}else{
								okToAdd = true;
							}
							if (okToAdd){
								result.add("GraphicNovel");
							}
						}
					}
				}
			}
		}

		List<DataField> genreFormTerm = getDataFields(record, "655");
		if (genreFormTerm != null) {
			Iterator<DataField> fieldsIter = genreFormTerm.iterator();
			DataField field;
			while (fieldsIter.hasNext()) {
				field = fieldsIter.next();
				@SuppressWarnings("unchecked")
				List<Subfield> subfields = field.getSubfields();
				for (Subfield subfield : subfields) {
					if (subfield.getCode() == 'a'){
						String subfieldData = subfield.getData().toLowerCase();
						if (subfieldData.contains("large type")) {
							result.add("LargePrint");
						}else if (subfieldData.contains("playaway")) {
							result.add("Playaway");
						}else if (subfieldData.contains("graphic novel")) {
							boolean okToAdd = false;
							if (field.getSubfield('v') != null){
								String subfieldVData = field.getSubfield('v').getData().toLowerCase();
								if (!subfieldVData.contains("Television adaptation")){
									okToAdd = true;
								}else{
									System.out.println("Not including graphic novel format");
								}
							}else{
								okToAdd = true;
							}
							if (okToAdd){
								result.add("GraphicNovel");
							}
						}
					}
				}
			}
		}

		@SuppressWarnings("unchecked")
		List<DataField> localTopicalTerm = getDataFields(record, "690");
		if (localTopicalTerm != null) {
			Iterator<DataField> fieldsIterator = localTopicalTerm.iterator();
			DataField field;
			while (fieldsIterator.hasNext()) {
				field = fieldsIterator.next();
				Subfield subfieldA = field.getSubfield('a');
				if (subfieldA != null) {
					if (subfieldA.getData().toLowerCase().contains("seed library")) {
						result.add("SeedPacket");
					}
				}
			}
		}

		@SuppressWarnings("unchecked")
		List<DataField> addedEntryFields = getDataFields(record, "710");
		if (localTopicalTerm != null) {
			Iterator<DataField> addedEntryFieldIterator = addedEntryFields.iterator();
			DataField field;
			while (addedEntryFieldIterator.hasNext()) {
				field = addedEntryFieldIterator.next();
				Subfield subfieldA = field.getSubfield('a');
				if (subfieldA != null && subfieldA.getData() != null) {
					String fieldData = subfieldA.getData().toLowerCase();
					if (fieldData.contains("playaway digital audio") || fieldData.contains("findaway world")) {
						result.add("Playaway");
					}
				}
			}
		}
	}

	private void getFormatFrom007(Record record, Set<String> result) {
		char formatCode;// check the 007 - this is a repeating field
		@SuppressWarnings("unchecked")
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
								result.add("Atlas");
								break;
							default:
								result.add("Map");
								break;
						}
						break;
					case 'C':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'A':
								result.add("TapeCartridge");
								break;
							case 'B':
								result.add("ChipCartridge");
								break;
							case 'C':
								result.add("DiscCartridge");
								break;
							case 'F':
								result.add("TapeCassette");
								break;
							case 'H':
								result.add("TapeReel");
								break;
							case 'J':
								result.add("FloppyDisk");
								break;
							case 'M':
							case 'O':
								result.add("CDROM");
								break;
							case 'R':
								// Do not return - this will cause anything with an
								// 856 field to be labeled as "Electronic"
								break;
							default:
								result.add("Software");
								break;
						}
						break;
					case 'D':
						result.add("Globe");
						break;
					case 'F':
						result.add("Braille");
						break;
					case 'G':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'C':
							case 'D':
								result.add("Filmstrip");
								break;
							case 'T':
								result.add("Transparency");
								break;
							default:
								result.add("Slide");
								break;
						}
						break;
					case 'H':
						result.add("Microfilm");
						break;
					case 'K':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'C':
								result.add("Collage");
								break;
							case 'D':
								result.add("Drawing");
								break;
							case 'E':
								result.add("Painting");
								break;
							case 'F':
								result.add("Print");
								break;
							case 'G':
								result.add("Photonegative");
								break;
							case 'J':
								result.add("Print");
								break;
							case 'L':
								result.add("Drawing");
								break;
							case 'O':
								result.add("FlashCard");
								break;
							case 'N':
								result.add("Chart");
								break;
							default:
								result.add("Photo");
								break;
						}
						break;
					case 'M':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'F':
								result.add("VideoCassette");
								break;
							case 'R':
								result.add("Filmstrip");
								break;
							default:
								result.add("MotionPicture");
								break;
						}
						break;
					case 'O':
						result.add("Kit");
						break;
					case 'Q':
						result.add("MusicalScore");
						break;
					case 'R':
						result.add("SensorImage");
						break;
					case 'S':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'D':
								if (formatField.getData().length() >= 4) {
									char speed = formatField.getData().toUpperCase().charAt(3);
									if (speed >= 'A' && speed <= 'E') {
										result.add("Phonograph");
									} else if (speed == 'F') {
										result.add("CompactDisc");
									} else if (speed >= 'K' && speed <= 'R') {
										result.add("TapeRecording");
									} else {
										result.add("SoundDisc");
									}
								} else {
									result.add("SoundDisc");
								}
								break;
							case 'S':
								result.add("SoundCassette");
								break;
							default:
								result.add("SoundRecording");
								break;
						}
						break;
					case 'T':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'A':
								result.add("Book");
								break;
							case 'B':
								result.add("LargePrint");
								break;
						}
						break;
					case 'V':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'C':
								result.add("VideoCartridge");
								break;
							case 'D':
								result.add("VideoDisc");
								break;
							case 'F':
								result.add("VideoCassette");
								break;
							case 'R':
								result.add("VideoReel");
								break;
							default:
								result.add("Video");
								break;
						}
						break;
				}
			}
		}
	}

	private void getFormatFromLeader(Set<String> result, String leader, ControlField fixedField) {
		char leaderBit;
		char formatCode;// check the Leader at position 6
		if (leader.length() >= 6) {
			leaderBit = leader.charAt(6);
			switch (Character.toUpperCase(leaderBit)) {
				case 'C':
				case 'D':
					result.add("MusicalScore");
					break;
				case 'E':
				case 'F':
					result.add("Map");
					break;
				case 'G':
					// We appear to have a number of items without 007 tags marked as G's.
					// These seem to be Videos rather than Slides.
					// result.add("Slide");
					result.add("Video");
					break;
				case 'I':
					result.add("SoundRecording");
					break;
				case 'J':
					result.add("MusicRecording");
					break;
				case 'K':
					result.add("Photo");
					break;
				case 'M':
					result.add("Electronic");
					break;
				case 'O':
				case 'P':
					result.add("Kit");
					break;
				case 'R':
					result.add("PhysicalObject");
					break;
				case 'T':
					result.add("Manuscript");
					break;
			}
		}

		if (leader.length() >= 7) {
			// check the Leader at position 7
			leaderBit = leader.charAt(7);
			switch (Character.toUpperCase(leaderBit)) {
				// Monograph
				case 'M':
					if (result.isEmpty()) {
						result.add("Book");
					}
					break;
				// Serial
				case 'S':
					// Look in 008 to determine what type of Continuing Resource
					formatCode = fixedField.getData().toUpperCase().charAt(21);
					switch (formatCode) {
						case 'N':
							result.add("Newspaper");
							break;
						case 'P':
							result.add("Journal");
							break;
						default:
							result.add("Serial");
							break;
					}
			}
		}
	}

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
