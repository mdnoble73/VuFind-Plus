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
	protected String dateAddedFormat;
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
	protected boolean useItemBasedCallNumbers;
	protected char callNumberPrestampSubfield;
	protected char callNumberSubfield;
	protected char callNumberCutterSubfield;
	protected char callNumberPoststampSubfield;
	protected char volumeSubfield;
	protected char itemRecordNumberSubfieldIndicator;
	protected char itemUrlSubfieldIndicator;
	protected boolean suppressItemlessBibs;

	//Fields for loading order information
	protected String orderTag;
	protected char orderLocationSubfield;
	protected char orderCopiesSubfield;
	protected char orderStatusSubfield;
	protected char orderCode3Subfield;

	private static boolean loanRuleDataLoaded = false;
	protected static ArrayList<Long> pTypes = new ArrayList<Long>();
	protected static HashMap<String, HashSet<String>> pTypesByLibrary = new HashMap<String, HashSet<String>>();
	protected static HashMap<String, HashSet<String>> pTypesForSpecialLocationCodes = new HashMap<String, HashSet<String>>();
	protected static HashSet<String> allPTypes = new HashSet<String>();
	private static HashMap<Long, LoanRule> loanRules = new HashMap<Long, LoanRule>();
	private static ArrayList<LoanRuleDeterminer> loanRuleDeterminers = new ArrayList<LoanRuleDeterminer>();

	private static HashMap<String, Integer> numberOfHoldsByIdentifier = new HashMap<String, Integer>();

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
		dateAddedFormat = Util.cleanIniValue(configIni.get("Reindex", "dateAddedFormat"));
		lastYearCheckoutSubfield = getSubfieldIndicatorFromConfig(configIni, "lastYearCheckoutSubfield");
		ytdCheckoutSubfield = getSubfieldIndicatorFromConfig(configIni, "ytdCheckoutSubfield");
		totalCheckoutSubfield = getSubfieldIndicatorFromConfig(configIni, "totalCheckoutSubfield");
		useICode2Suppression = Boolean.parseBoolean(configIni.get("Reindex", "useICode2Suppression"));
		iCode2Subfield = getSubfieldIndicatorFromConfig(configIni, "iCode2Subfield");
		callNumberPrestampSubfield = getSubfieldIndicatorFromConfig(configIni, "callNumberPrestampSubfield");
		callNumberSubfield = getSubfieldIndicatorFromConfig(configIni, "callNumberSubfield");
		callNumberCutterSubfield = getSubfieldIndicatorFromConfig(configIni, "callNumberCutterSubfield");
		callNumberPoststampSubfield = getSubfieldIndicatorFromConfig(configIni, "callNumberPoststampSubfield");
		useItemBasedCallNumbers = Boolean.parseBoolean(configIni.get("Reindex", "useItemBasedCallNumbers"));
		volumeSubfield = getSubfieldIndicatorFromConfig(configIni, "volumeSubfield");
		itemRecordNumberSubfieldIndicator = getSubfieldIndicatorFromConfig(configIni, "itemRecordNumberSubfield");
		itemUrlSubfieldIndicator = getSubfieldIndicatorFromConfig(configIni, "itemUrlSubfield");
		suppressItemlessBibs = Boolean.parseBoolean(configIni.get("Reindex", "suppressItemlessBibs"));

		orderTag = configIni.get("Reindex", "orderTag");
		orderLocationSubfield = getSubfieldIndicatorFromConfig(configIni, "orderLocationSubfield");
		orderCopiesSubfield = getSubfieldIndicatorFromConfig(configIni, "orderCopiesSubfield");
		orderStatusSubfield = getSubfieldIndicatorFromConfig(configIni, "orderStatusSubfield");
		orderCode3Subfield = getSubfieldIndicatorFromConfig(configIni, "orderCode3Subfield");

		String additionalCollectionsString = configIni.get("Reindex", "additionalCollections");
		if (additionalCollectionsString != null){
			additionalCollections = additionalCollectionsString.split(",");
		}

		//loadAvailableItemBarcodes(marcRecordPath, logger);
		loadLoanRuleInformation(vufindConn, logger);
		loadHoldsByIdentifier(vufindConn, logger);
	}

	private void loadHoldsByIdentifier(Connection vufindConn, Logger logger) {
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
		Record record = loadMarcRecordFromDisk(identifier);

		if (record != null){
			try{
				updateGroupedWorkSolrDataBasedOnMarc(groupedWork, record, identifier);
			}catch (Exception e) {
				logger.error("Error updating solr based on marc record", e);
			}
		}
	}

	public Record loadMarcRecordFromDisk(String identifier) {
		Record record = null;
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
				record = marcReader.next();
			}
			inputStream.close();
		} catch (Exception e) {
			logger.error("Error reading data from ils file " + individualFile.toString(), e);
		}
		return record;
	}

	@Override
	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		String step = "start";
		try{
			if (isBibSuppressed(record)){
				indexer.ilsRecordsSkipped.add(identifier);
				return;
			}
			indexer.ilsRecordsIndexed.add(identifier);
			//First load a list of print items and econtent items from the MARC record since they are needed to handle
			//Scoping and availability of records.
			step = "load print items";
			List<PrintIlsItem> printItems = getUnsuppressedPrintItems(identifier, record);
			step = "load eContent items";
			List<EContentIlsItem> econtentItems = getUnsuppressedEContentItems(identifier, record);
			step = "load order items";
			List<OnOrderItem> onOrderItems = getOnOrderItems(identifier, record);

			//Break the MARC record up based on item information and load data that is scoped
			//i.e. formats, iTypes, date added to catalog, etc
			step = "load scoped data";
			HashSet<IlsRecord> ilsRecords = addRecordAndItemsToAppropriateScopesAndLoadFormats(groupedWork, record, printItems, econtentItems, onOrderItems);

			//Do updates based on the overall bib (shared regardless of scoping)
			step = "update work based on standard data";
			updateGroupedWorkSolrDataBasedOnStandardMarcData(groupedWork, record, printItems);

			//Special processing for ILS Records
			step = "load description";
			String fullDescription = Util.getCRSeparatedString(getFieldList(record, "520a"));
			for (IlsRecord ilsRecord : ilsRecords) {
				groupedWork.addDescription(fullDescription, ilsRecord.getPrimaryFormat());
			}
			step = "editions, physical description, etc";
			loadEditions(groupedWork, record, ilsRecords);
			loadPhysicalDescription(groupedWork, record, ilsRecords);
			loadLanguageDetails(groupedWork, record, ilsRecords);
			loadPublicationDetails(groupedWork, record, ilsRecords);
			loadSystemLists(groupedWork, record);

			//Do updates based on items
			step = "load ownership info";
			loadOwnershipInformation(groupedWork, printItems, econtentItems, onOrderItems);
			step = "load availability";
			loadAvailability(groupedWork, printItems, econtentItems, ilsRecords);
			step = "load usability";
			loadUsability(groupedWork, printItems, econtentItems);
			step = "load popularity";
			loadPopularity(groupedWork, identifier, printItems, econtentItems, onOrderItems);
			step = "load date added";
			loadDateAdded(groupedWork, identifier, printItems, econtentItems);
			step = "load iTypes";
			loadITypes(groupedWork, printItems, econtentItems);
			step = "load call numbers";
			loadLocalCallNumbers(groupedWork, printItems, econtentItems);
			groupedWork.addBarcodes(getFieldList(record, itemTag + barcodeSubfield));
			groupedWork.setRelatedRecords(ilsRecords);
			step = "set formats";
			groupedWork.setFormatInformation(ilsRecords);

			step = "load econtent sources";
			loadEContentSourcesAndProtectionTypes(groupedWork, econtentItems);

			step = "load order ids";
			loadOrderIds(groupedWork, record);

			step = "add holdings";
			int numPrintItems = printItems.size();
			if (!suppressItemlessBibs && numPrintItems == 0){
				numPrintItems = 1;
			}
			groupedWork.addHoldings(numPrintItems + onOrderItems.size());
		}catch (Exception e){
			logger.error("Error updating grouped work for MARC record with identifier " + identifier + " on step " + step, e);
		}
	}

	protected boolean isBibSuppressed(Record record) {
		return false;
	}

	protected void loadSystemLists(GroupedWorkSolr groupedWork, Record record) {
		//By default, do nothing
	}

	protected List<OnOrderItem> getOnOrderItems(String identifier, Record record){
		if (orderTag == null){
			return new ArrayList<OnOrderItem>();
		}else{
			ArrayList<OnOrderItem> orderItems = new ArrayList<OnOrderItem>();

			List<DataField> orderFields = getDataFields(record, orderTag);
			for (DataField curOrderField : orderFields){
				OnOrderItem orderItem = new OnOrderItem();
				orderItem.setBibNumber(identifier);
				String orderNumber = curOrderField.getSubfield('a').getData();
				orderItem.setOrderNumber(orderNumber);
				if (curOrderField.getSubfield(orderCopiesSubfield) == null){
					//Assume one copy
					orderItem.setCopies(1);
				}else{
					orderItem.setCopies(Integer.parseInt(curOrderField.getSubfield(orderCopiesSubfield).getData()));
				}

				String status = curOrderField.getSubfield(orderStatusSubfield).getData();
				String code3 = null;
				if (orderCode3Subfield != ' '){
					code3 = curOrderField.getSubfield(orderCode3Subfield).getData();
				}

				//TODO: DO we need to allow customization of active order statuses?
				if (isOrderItemValid(status, code3)){
					orderItem.setStatus(status);
					String location = curOrderField.getSubfield(orderLocationSubfield).getData();
					if (!location.equals("multi")) {
						orderItem.setLocationCode(location.trim());
						for (Scope curScope : indexer.getScopes()) {
							//Part of scope if the location code is included directly
							//or if the scope is not limited to only including library/location codes.
							boolean includedDirectly = curScope.isLocationCodeIncludedDirectly(location, location);
							if ((!curScope.isIncludeItemsOwnedByTheLibraryOnly() && !curScope.isIncludeItemsOwnedByTheLocationOnly()) ||
									includedDirectly) {
								if (includedDirectly) {
									orderItem.addScopeThisItemIsDirectlyIncludedIn(curScope.getScopeName());
								}
								orderItem.addRelatedScope(curScope);
							}
						}
						orderItems.add(orderItem);
					}else{
						orderItem.setLocationCode(location.trim());
						for (Scope curScope : indexer.getScopes()) {
							//Part of scope if the location code is included directly
							//or if the scope is not limited to only including library/location codes.
							orderItem.addRelatedScope(curScope);
							orderItem.addScopeThisItemIsDirectlyIncludedIn(curScope.getScopeName());
						}
						orderItems.add(orderItem);
					}
				}
			}

			return orderItems;
		}
	}

	protected boolean isOrderItemValid(String status, String code3) {
		return status.equals("o") || status.equals("1");
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
	protected HashSet<IlsRecord> addRecordAndItemsToAppropriateScopesAndLoadFormats(GroupedWorkSolr groupedWork, Record record, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems, List<OnOrderItem> onOrderItems) {
		HashSet<IlsRecord> ilsRecords = new HashSet<IlsRecord>();
		String recordId = getFirstFieldVal(record, recordNumberTag + "a");
		String recordIdentifier = "ils:" + recordId;

		HashSet<String> scopesThatContainRecord = new HashSet<String>();
		HashSet<String> scopesThatContainRecordDirectly = new HashSet<String>();
		//Add stats to indicate that the record is part of the global scope
		if (printItems.size() > 0 || onOrderItems.size() > 0 || econtentItems.size() > 0 || !suppressItemlessBibs) {
			indexer.indexingStats.get("global").numLocalIlsRecords++;
			indexer.indexingStats.get("global").numSuperScopeIlsRecords++;
		}else{
			indexer.ilsRecordsSkipped.add(recordId);
		}

		if ((printItems.size() > 0 || onOrderItems.size() > 0) || !suppressItemlessBibs) {
			IlsRecord printRecord = new IlsRecord();
			printRecord.setRecordId(recordIdentifier);
			printRecord.addItems(printItems);
			printRecord.addRelatedOrderItems(onOrderItems);
			//Load formats for the print record
			loadPrintFormatInformation(printRecord, record);
			ilsRecords.add(printRecord);
			for (PrintIlsItem printItem : printItems) {
				if (printItem != null) {
					indexer.indexingStats.get("global").numLocalIlsItems++;
					indexer.indexingStats.get("global").numSuperScopeIlsItems++;

					String itemInfo = recordIdentifier + "|" + printItem.getRelatedItemInfo();
					groupedWork.addRelatedItem(itemInfo);
					for (Scope scope : printItem.getRelatedScopes()) {
						ScopedWorkDetails scopedWorkDetails = groupedWork.getScopedWorkDetails().get(scope.getScopeName());
						scopedWorkDetails.addRelatedItem(recordIdentifier, printItem);
						indexer.indexingStats.get(scope.getScopeName()).numSuperScopeIlsItems++;
						if (!scopesThatContainRecord.contains(scope.getScopeName())){
							scopesThatContainRecord.add(scope.getScopeName());
							indexer.indexingStats.get(scope.getScopeName()).numSuperScopeIlsRecords++;
						}
					}

					for (String scope: printItem.getScopesThisItemIsDirectlyIncludedIn()){
						indexer.indexingStats.get(scope).numLocalIlsItems++;
						if (!scopesThatContainRecordDirectly.contains(scope)){
							scopesThatContainRecordDirectly.add(scope);
							indexer.indexingStats.get(scope).numLocalIlsRecords++;
							groupedWork.getScopedWorkDetails().get(scope).setLocallyOwned(true);
						}
					}
				}else{
					logger.warn("Got an invalid print item in loadScopedDataForMarcRecord for " + recordId);
				}
			}

			for (OnOrderItem orderItem : onOrderItems) {
				if (orderItem != null) {
					indexer.indexingStats.get("global").numLocalOrderItems++;
					indexer.indexingStats.get("global").numSuperScopeOrderItems++;
					String itemInfo = orderItem.getRecordIdentifier() + "|" + orderItem.getRelatedItemInfo();
					groupedWork.addRelatedItem(itemInfo);
					for (Scope scope : orderItem.getRelatedScopes()) {
						//Add the record to the scope, but only if there are no print titles (which have better information)
						if (printItems.size() == 0){
							groupedWork.getScopedWorkDetails().get(scope.getScopeName()).addRelatedRecord(
									recordIdentifier,
									printRecord.getPrimaryFormat() != null ? printRecord.getPrimaryFormat() : "Item On Order",
									printRecord.getEdition(),
									printRecord.getLanguage(),
									printRecord.getPublisher(),
									printRecord.getPublicationDate(),
									printRecord.getPhysicalDescription()
							);}
						ScopedWorkDetails scopedWorkDetails = groupedWork.getScopedWorkDetails().get(scope.getScopeName());
						scopedWorkDetails.addRelatedOrderItem(orderItem.getRecordIdentifier(), orderItem);
						indexer.indexingStats.get(scope.getScopeName()).numSuperScopeOrderItems++;
						if (!scopesThatContainRecord.contains(scope.getScopeName())){
							scopesThatContainRecord.add(scope.getScopeName());
							indexer.indexingStats.get(scope.getScopeName()).numSuperScopeIlsRecords++;
						}
					}
					for (String scope: orderItem.getScopesThisItemIsDirectlyIncludedIn()){
						indexer.indexingStats.get(scope).numLocalOrderItems++;
						if (!scopesThatContainRecordDirectly.contains(scope)){
							scopesThatContainRecordDirectly.add(scope);
							indexer.indexingStats.get(scope).numLocalIlsRecords++;
						}
					}
				} else {
					logger.warn("Got an invalid order item in loadScopedDataForMarcRecord for " + recordId);
				}
			}

			if (!suppressItemlessBibs && printItems.size() == 0 && onOrderItems.size() == 0){
				for (Scope scope : indexer.getScopes()){
					ScopedWorkDetails scopedWorkDetails = groupedWork.getScopedWorkDetails().get(scope.getScopeName());
					scopedWorkDetails.addRelatedRecord(
							recordIdentifier,
							printRecord.getPrimaryFormat() != null ? printRecord.getPrimaryFormat() : "Item On Order",
							printRecord.getEdition(),
							printRecord.getLanguage(),
							printRecord.getPublisher(),
							printRecord.getPublicationDate(),
							printRecord.getPhysicalDescription()
					);
					if (!scopesThatContainRecord.contains(scope.getScopeName())){
						scopesThatContainRecord.add(scope.getScopeName());
						indexer.indexingStats.get(scope.getScopeName()).numSuperScopeIlsRecords++;
					}
					if (!scopesThatContainRecordDirectly.contains(scope.getScopeName())){
						scopesThatContainRecordDirectly.add(scope.getScopeName());
						indexer.indexingStats.get(scope.getScopeName()).numLocalIlsRecords++;
					}
				}
			}
		}

		for (EContentIlsItem econtentItem : econtentItems) {
			indexer.indexingStats.get("global").numLocalEContentItems++;
			indexer.indexingStats.get("global").numSuperScopeEContentItems++;
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
				scopedWorkDetails.addRelatedEContentItem(econtentItem.getRecordIdentifier(), econtentItem);
				indexer.indexingStats.get(scope.getScopeName()).numSuperScopeEContentItems++;
				if (!scopesThatContainRecord.contains(scope.getScopeName())){
					scopesThatContainRecord.add(scope.getScopeName());
					indexer.indexingStats.get(scope.getScopeName()).numSuperScopeIlsRecords++;
				}
			}
			for (String scope: econtentItem.getScopesThisItemIsDirectlyIncludedIn()){
				indexer.indexingStats.get(scope).numLocalEContentItems++;
				if (!scopesThatContainRecordDirectly.contains(scope)){
					scopesThatContainRecordDirectly.add(scope);
					indexer.indexingStats.get(scope).numLocalIlsRecords++;
				}
			}
		}
		return ilsRecords;
	}

	protected List<PrintIlsItem> getUnsuppressedPrintItems(String identifier, Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<PrintIlsItem> unsuppressedItemRecords = new ArrayList<PrintIlsItem>();
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				PrintIlsItem ilsRecord = getPrintIlsItem(record, itemField);
				//Can return null if the record does not have status and location
				//This happens with secondary call numbers sometimes.
				if (ilsRecord != null) {
					ilsRecord.setRecordIdentifier(identifier);
					unsuppressedItemRecords.add(ilsRecord);
				}
			}
		}
		return unsuppressedItemRecords;
	}

	protected EContentIlsItem getEContentIlsRecord(Record record, String identifier, DataField itemField){
		EContentIlsItem ilsEContentItem = new EContentIlsItem();

		ilsEContentItem.setDateCreated(getItemSubfieldData(dateCreatedSubfield, itemField));
		ilsEContentItem.setLocationCode(getItemSubfieldData(locationSubfieldIndicator, itemField));
		ilsEContentItem.setiType(getItemSubfieldData(iTypeSubfield, itemField));
		ilsEContentItem.setCallNumberPreStamp(getItemSubfieldData(callNumberPrestampSubfield, itemField));
		ilsEContentItem.setCallNumber(getItemSubfieldData(callNumberSubfield, itemField));
		ilsEContentItem.setCallNumberCutter(getItemSubfieldData(callNumberCutterSubfield, itemField));
		ilsEContentItem.setCallNumberPostStamp(getItemSubfieldData(callNumberPoststampSubfield, itemField));
		ilsEContentItem.setVolume(getItemSubfieldData(volumeSubfield, itemField));
		ilsEContentItem.setItemRecordNumber(getItemSubfieldData(itemRecordNumberSubfieldIndicator, itemField));
		if (collectionSubfield != ' ') {
			ilsEContentItem.setCollection(getItemSubfieldData(collectionSubfield, itemField));
		}

		Subfield eContentSubfield = itemField.getSubfield(eContentSubfieldIndicator);
		if (eContentSubfield != null){
			String eContentData = eContentSubfield.getData().trim();
			if (eContentData.indexOf(':') > 0) {
				String[] eContentFields = eContentData.split(":");
				//First element is the source, and we will always have at least the source and protection type
				ilsEContentItem.setSource(eContentFields[0].trim());
				ilsEContentItem.setProtectionType(eContentFields[1].trim().toLowerCase());
				if (eContentFields.length >= 3){
					ilsEContentItem.setSharing(eContentFields[2].trim().toLowerCase());
				}else{
					//Sharing depends on the location code
					if (ilsEContentItem.getLocationCode().startsWith("mdl")){
						ilsEContentItem.setSharing("shared");
					}else{
						ilsEContentItem.setSharing("library");
					}
				}

				//Remaining fields have variable definitions based on content that has been loaded over the past year or so
				if (eContentFields.length >= 4){
					//If the 4th field is numeric, it is the number of copies that can be checked out.
					if (Util.isNumeric(eContentFields[3].trim())){
						ilsEContentItem.setNumberOfCopies(eContentFields[3].trim());
						if (eContentFields.length >= 5){
							ilsEContentItem.setFilename(eContentFields[4].trim());
						}else{
							logger.warn("Filename for local econtent not specified " + eContentData + " " + identifier);
						}
						if (eContentFields.length >= 6){
							ilsEContentItem.setAcsId(eContentFields[5].trim());
						}
					}else{
						//Field 4 is the filename
						ilsEContentItem.setFilename(eContentFields[3].trim());
						if (eContentFields.length >= 5){
							ilsEContentItem.setAcsId(eContentFields[4].trim());
						}
					}
				}
			}
		}else{
			//This is for a "less advanced" catalog, set some basic info
			ilsEContentItem.setSource("External eContent");
			ilsEContentItem.setProtectionType("external");
			ilsEContentItem.setSharing(getEContentSharing(ilsEContentItem, itemField));
			ilsEContentItem.setSource(getSourceType(record, itemField));
		}

		//Set record type
		String protectionType = ilsEContentItem.getProtectionType();
		if (protectionType.equals("acs") || protectionType.equals("drm")){
			ilsEContentItem.setRecordIdentifier("restricted_econtent:" + identifier);
		}else if (protectionType.equals("public domain") || protectionType.equals("free")){
			ilsEContentItem.setRecordIdentifier("public_domain_econtent:" + identifier);
		}else if (protectionType.equals("external")){
			ilsEContentItem.setRecordIdentifier("external_econtent:" + identifier);
		}else{
			logger.warn("Unknown protection type " + protectionType + " found in record " + identifier);
		}

		//Get the url if any
		Subfield urlSubfield = itemField.getSubfield(itemUrlSubfieldIndicator);
		if (urlSubfield != null){
			ilsEContentItem.setUrl(urlSubfield.getData().trim());
		}else if (protectionType.equals("external")){
			//Check the 856 tag to see if there is a link there
			List<DataField> urlFields = getDataFields(record, "856");
			for (DataField urlField : urlFields){
				//load url into the item
				if (urlField.getSubfield('u') != null){
					//Try to determine if this is a resource or not.
					if (urlField.getIndicator1() == '4' || urlField.getIndicator1() == ' ' || urlField.getIndicator1() == '0' || urlField.getIndicator1() == '7'){
						if (urlField.getIndicator2() == ' ' || urlField.getIndicator2() == '0' || urlField.getIndicator2() == '1' || urlField.getIndicator2() == '8') {
							ilsEContentItem.setUrl(urlField.getSubfield('u').getData().trim());
							break;
						}
					}

				}
			}

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
		ilsEContentItem.setAvailable(available);

		//Determine which scopes this title belongs to
		for (Scope curScope : indexer.getScopes()){
			boolean includedDirectly = curScope.isEContentDirectlyOwned(ilsEContentItem);
			if (curScope.isEContentLocationPartOfScope(ilsEContentItem)){
				ilsEContentItem.addRelatedScope(curScope);
				if (includedDirectly){
					ilsEContentItem.addScopeThisItemIsDirectlyIncludedIn(curScope.getScopeName());
				}
			}
		}

		//TODO: Determine the format, format category, and boost factor for this title
		return ilsEContentItem;
	}

	protected String getEContentSharing(EContentIlsItem ilsEContentItem, DataField itemField) {
		return "shared";
	}

	protected String getSourceType(Record record, DataField itemField) {
		return "Unknown Source";
	}

	protected String getLocationForItem(DataField itemField){
		return getItemSubfieldData(locationSubfieldIndicator, itemField);
	}

	protected String getLibrarySystemCodeForItem(DataField itemField){
		return getItemSubfieldData(locationSubfieldIndicator, itemField);
	}

	protected String getShelfLocationCodeForItem(DataField itemField){
		return getItemSubfieldData(locationSubfieldIndicator, itemField);
	}

	protected String getCollectionForItem(DataField itemField){
		return getItemSubfieldData(collectionSubfield, itemField);
	}

	protected PrintIlsItem getPrintIlsItem(Record record, DataField itemField) {
		PrintIlsItem ilsItem = new PrintIlsItem();

		//Load base information from the Marc Record
		String itemStatus = getItemStatus(itemField);
		ilsItem.setStatus(itemStatus);
		ilsItem.setLocationCode(getLocationForItem(itemField));
		ilsItem.setLibrarySystemCode(getLibrarySystemCodeForItem(itemField));
		ilsItem.setShelfLocationCode(getShelfLocationCodeForItem(itemField));
		ilsItem.setShelfLocation(getShelfLocationForItem(itemField));
		//if the status and location are null, we can assume this is not a valid item
		if (ilsItem.getStatus() == null && ilsItem.getLocationCode() == null){
			return null;
		}
		ilsItem.setDateDue(getItemSubfieldData(dueDateSubfield, itemField));
		ilsItem.setDateCreated(getItemSubfieldData(dateCreatedSubfield, itemField));
		ilsItem.setiType(getItemSubfieldData(iTypeSubfield, itemField));
		ilsItem.setLastYearCheckouts(getItemSubfieldData(lastYearCheckoutSubfield, itemField));
		ilsItem.setYtdCheckouts(getItemSubfieldData(ytdCheckoutSubfield, itemField));
		ilsItem.setTotalCheckouts(getItemSubfieldData(totalCheckoutSubfield, itemField));
		if (useItemBasedCallNumbers) {
			ilsItem.setCallNumberPreStamp(getItemSubfieldDataWithoutTrimming(callNumberPrestampSubfield, itemField));
			ilsItem.setCallNumber(getItemSubfieldDataWithoutTrimming(callNumberSubfield, itemField));
			ilsItem.setCallNumberCutter(getItemSubfieldDataWithoutTrimming(callNumberCutterSubfield, itemField));
			ilsItem.setCallNumberPostStamp(getItemSubfieldData(callNumberPoststampSubfield, itemField));
		}else{
			String callNumber = null;
			DataField localCallNumberField = (DataField)record.getVariableField("099");
			if (localCallNumberField != null){
				callNumber = "";
				for (Subfield curSubfield : localCallNumberField.getSubfields()){
					callNumber += " " + curSubfield.getData().trim();
				}
			}
			if (callNumber == null){
				DataField deweyCallNumberField = (DataField)record.getVariableField("092");
				if (deweyCallNumberField != null){
					callNumber = "";
					for (Subfield curSubfield : deweyCallNumberField.getSubfields()){
						callNumber += " " + curSubfield.getData().trim();
					}
				}
			}
			if (callNumber != null) {
				ilsItem.setCallNumber(callNumber.trim());
			}
		}
		ilsItem.setVolume(getItemSubfieldData(volumeSubfield, itemField));
		ilsItem.setBarcode(getItemSubfieldData(barcodeSubfield, itemField));
		ilsItem.setItemRecordNumber(getItemSubfieldData(itemRecordNumberSubfieldIndicator, itemField));
		ilsItem.setCollection(getCollectionForItem(itemField));

		//Determine Availability
		boolean available = false;
		//if (getAvailabilityFromMarc){
			if (ilsItem.getStatus() != null) {
				available = isItemAvailable(ilsItem);
			}
		/*}else{
			if (ilsRecord.getBarcode() != null){
				available = availableItemBarcodes.contains(ilsRecord.getBarcode());
			}
		}*/
		ilsItem.setAvailable(available);

		if (ilsItem.getiType() != null && ilsItem.getLocationCode() != null) {
			//Figure out which ptypes are compatible with this itype
			ilsItem.setCompatiblePTypes(getCompatiblePTypes(ilsItem.getiType(), ilsItem.getLocationCode()));
		}
		//Determine which scopes have access to this record
		for (Scope curScope : indexer.getScopes()) {
			boolean includedDirectly = curScope.isLocationCodeIncludedDirectly(ilsItem.getLibrarySystemCode(), ilsItem.getLocationCode());
			if (curScope.isItemPartOfScope(ilsItem.getLibrarySystemCode(), ilsItem.getLocationCode(), ilsItem.getCompatiblePTypes())) {
				if (includedDirectly){
					ilsItem.addScopeThisItemIsDirectlyIncludedIn(curScope.getScopeName());
				}
				ilsItem.addRelatedScope(curScope);
			}
		}

		return ilsItem;
	}

	protected String getShelfLocationForItem(DataField itemField) {
		String shelfLocation = getItemSubfieldData(locationSubfieldIndicator, itemField);
		if (shelfLocation == null || shelfLocation.length() == 0 || shelfLocation.equals("none")){
			return "";
		}else {
			return indexer.translateValue("shelf_location", shelfLocation);
		}
	}

	protected String getItemStatus(DataField itemField){
		return getItemSubfieldData(statusSubfieldIndicator, itemField);
	}

	protected abstract boolean isItemAvailable(PrintIlsItem ilsRecord);

	protected String getItemSubfieldData(char subfieldIndicator, DataField itemField) {
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
			String location = curItem.getLocationCode();
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
			String location = curItem.getLocationCode();
			if (iType != null && location != null){
				String translatedIType = indexer.translateValue("itype", iType);
				ArrayList<String> relatedSubdomains = getLibrarySubdomainsForLocationCode(location);
				ArrayList<String> relatedLocations = getRelatedLocationCodesForLocationCode(location);
				groupedWork.setIType(translatedIType, relatedSubdomains, relatedLocations);
			}
		}
	}

	private static SimpleDateFormat dateAddedFormatter = null;
	protected void loadDateAdded(GroupedWorkSolr groupedWork, String identifier, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems) {
		if (dateAddedFormatter == null){
			dateAddedFormatter = new SimpleDateFormat(dateAddedFormat);
		}
		for (PrintIlsItem curItem : printItems){
			String locationCode = curItem.getLocationCode();
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
			String locationCode = curItem.getLocationCode();
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
			String locationCode = curItem.getLocationCode();
			if (iType != null && locationCode != null){
				groupedWork.addCompatiblePTypes(getCompatiblePTypes(iType, locationCode));
			}
		}
	}

	protected boolean isItemSuppressed(DataField curItem) {
		return false;
	}

	protected void loadAvailability(GroupedWorkSolr groupedWork, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems, HashSet<IlsRecord> ilsRecords) {
		//Calculate availability based on the record
		HashSet<String> availableAt = new HashSet<String>();
		HashSet<String> availableLocationCodes = new HashSet<String>();

		HashSet<String> relatedFormats = new HashSet<String>();
		for (IlsRecord curRecord : ilsRecords){
			relatedFormats.addAll(curRecord.getFormats());
			relatedFormats.addAll(curRecord.getFormatCategories());
		}

		for (PrintIlsItem curItem : printItems){
			if (curItem.getLocationCode() != null){
				HashSet<String> relatedLocations = new HashSet<String>();
				relatedLocations.addAll(getLocationFacetsForLocationCode(curItem.getLocationCode()));
				HashSet<String> relatedScopes = new HashSet<String>();
				relatedScopes.addAll(getRelatedLocationCodesForLocationCode(curItem.getLocationCode()));
				relatedScopes.addAll(getRelatedSubdomainsForLocationCode(curItem.getLibrarySystemCode()));
				if (curItem.isAvailable()){
					availableAt.addAll(relatedLocations);
					availableLocationCodes.addAll(relatedScopes);
					//Add subdomains to get related scopes
					groupedWork.addAvailabilityByFormatForLocation(relatedScopes, relatedFormats, "available");
				}
				groupedWork.addAvailabilityByFormatForLocation(relatedScopes, relatedFormats, "local");
			}
		}

		//TODO: Process eContent as well?

		groupedWork.addAvailableLocations(availableAt, availableLocationCodes);
	}

	protected void loadOwnershipInformation(GroupedWorkSolr groupedWork, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems, List<OnOrderItem> onOrderItems) {
		HashSet<String> owningLibraries = new HashSet<String>();
		HashSet<String> owningLocations = new HashSet<String>();
		HashSet<String> owningLocationCodes = new HashSet<String>();
		for (PrintIlsItem curItem : printItems){
			String librarySystemCode = curItem.getLibrarySystemCode();
			String locationCode = curItem.getLocationCode();
			if (locationCode != null){
				ArrayList<String> owningLibrariesForLocationCode = getLibraryFacetsForLocationCode(librarySystemCode);
				owningLibraries.addAll(owningLibrariesForLocationCode);
				ArrayList<String> owningLocationsForLocationCode = getLocationFacetsForLocationCode(locationCode);
				owningLocations.addAll(owningLocationsForLocationCode);
				owningLocationCodes.addAll(getRelatedLocationCodesForLocationCode(locationCode));
				owningLocationCodes.addAll(getRelatedSubdomainsForLocationCode(librarySystemCode));

				loadAdditionalOwnershipInformation(groupedWork, curItem);
			}
			for (Scope curScope : curItem.getRelatedScopes()){
				if (curScope.isLocationScope() && curScope.isLocationCodeIncludedDirectly(librarySystemCode, locationCode)) {
					if (!owningLocations.contains(curScope.getFacetLabel())) {
						owningLocations.add(curScope.getFacetLabel());
					}
				}
			}
		}
		//TODO: set ownership information for eContent

		for (OnOrderItem curOrderItem: onOrderItems){
			for (Scope curScope : curOrderItem.getRelatedScopes()){
				if (curScope.isLibraryScope()){
					owningLibraries.add(curScope.getFacetLabel() + " On Order");
				}else {
					owningLocations.add(curScope.getFacetLabel() + " On Order");
				}
			}
		}
		groupedWork.addOwningLibraries(owningLibraries);
		groupedWork.addOwningLocations(owningLocations);
		groupedWork.addOwningLocationCodesAndSubdomains(owningLocationCodes);
	}

	protected void loadAdditionalOwnershipInformation(GroupedWorkSolr groupedWork, PrintIlsItem printItem){

	}

	private HashSet<String> locationsWithoutLibraryFacets = new HashSet<String>();
	private HashMap<String, Pattern> libraryCodePatterns = new HashMap<String, Pattern>();
	protected ArrayList<String> getLibraryFacetsForLocationCode(String locationCode) {
		locationCode = locationCode.trim().toLowerCase();
		ArrayList<String> libraryFacets = new ArrayList<String>();
		for(String libraryCode : indexer.getLibraryFacetMap().keySet()){
			Pattern libraryCodePattern = libraryCodePatterns.get(libraryCode);
			if (libraryCodePattern == null){
				libraryCodePattern = Pattern.compile(libraryCode);
				libraryCodePatterns.put(libraryCode, libraryCodePattern);
			}
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
			Pattern libraryCodePattern = libraryCodePatterns.get(libraryCode);
			if (libraryCodePattern == null){
				libraryCodePattern = Pattern.compile(libraryCode);
				libraryCodePatterns.put(libraryCode, libraryCodePattern);
			}
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
			Pattern libraryCodePattern = libraryCodePatterns.get(libraryCode);
			if (libraryCodePattern == null){
				libraryCodePattern = Pattern.compile(libraryCode);
				libraryCodePatterns.put(libraryCode, libraryCodePattern);
			}
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
			Pattern libraryCodePattern = libraryCodePatterns.get(libraryCode);
			if (libraryCodePattern == null){
				libraryCodePattern = Pattern.compile(libraryCode);
				libraryCodePatterns.put(libraryCode, libraryCodePattern);
			}
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
		for(String libraryCode : indexer.getLocationMap().keySet()){
			Pattern libraryCodePattern = libraryCodePatterns.get(libraryCode);
			if (libraryCodePattern == null){
				libraryCodePattern = Pattern.compile(libraryCode);
				libraryCodePatterns.put(libraryCode, libraryCodePattern);
			}
			if (libraryCodePattern.matcher(locationCode).lookingAt()){
				locationFacets.add(indexer.getLocationMap().get(libraryCode));
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
		for(String libraryCode : indexer.getLocationMap().keySet()){
			Pattern libraryCodePattern = libraryCodePatterns.get(libraryCode);
			if (libraryCodePattern == null){
				libraryCodePattern = Pattern.compile(libraryCode);
				libraryCodePatterns.put(libraryCode, libraryCodePattern);
			}
			if (libraryCodePattern.matcher(locationCode).lookingAt()){
				locationFacets.add(libraryCode);
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
		for(String libraryCode : indexer.getLocationMap().keySet()){
			Pattern libraryCodePattern = libraryCodePatterns.get(libraryCode);
			if (libraryCodePattern == null){
				libraryCodePattern = Pattern.compile(libraryCode);
				libraryCodePatterns.put(libraryCode, libraryCodePattern);
			}
			if (libraryCodePattern.matcher(locationCode).lookingAt()){
				locationCodes.add(libraryCode);
			}
		}
		ilsCodesForDetailedLocationCode.put(locationCode, locationCodes);
		return locationCodes;
	}

	private HashMap<String, LinkedHashSet<String>> ptypesByItypeAndLocation = new HashMap<String, LinkedHashSet<String>>();
	public LinkedHashSet<String> getCompatiblePTypes(String iType, String locationCode) {
		if (loanRuleDeterminers.size() == 0){
			return new LinkedHashSet<String>();
		}
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
								if (curDeterminer.getPatronType().equals("999")){
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
				logger.debug("Did not get any formats for print record " + ilsRecord.getRecordId() + ", assuming it is a book ");
				printFormats.add("Book");
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
		}else if (printFormats.contains("Book") && printFormats.contains("MusicalScore")){
			printFormats.remove("Book");
		}else if (printFormats.contains("Book") && printFormats.contains("BookClubKit")){
			printFormats.remove("Book");
		}else if (printFormats.contains("Book") && printFormats.contains("Manuscript")){
			printFormats.remove("Manuscript");
		}else if (printFormats.contains("Kinect") || printFormats.contains("XBox360")
				|| printFormats.contains("XBoxOne") || printFormats.contains("PlayStation")
				|| printFormats.contains("PlayStation3") || printFormats.contains("PlayStation4")
				|| printFormats.contains("Wii") || printFormats.contains("WiiU")
				|| printFormats.contains("3DS") || printFormats.contains("WindowsGame")){
			printFormats.remove("Software");
			printFormats.remove("Electronic");
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
		String title = getFirstFieldVal(record, "245a");
		if (title != null){
			title = title.toLowerCase();
			if (title.contains("book club kit")){
				printFormats.add("BookClubKit");
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
				} else if (sysDetailsValue.contains("xbox one")) {
					result.add("XboxOne");
				} else if (sysDetailsValue.contains("xbox")) {
					result.add("Xbox360");
				} else if (sysDetailsValue.contains("playstation 4")) {
					result.add("PlayStation4");
				} else if (sysDetailsValue.contains("playstation 3")) {
					result.add("PlayStation3");
				} else if (sysDetailsValue.contains("playstation")) {
					result.add("PlayStation");
				} else if (sysDetailsValue.contains("wii u")) {
					result.add("WiiU");
				} else if (sysDetailsValue.contains("nintendo wii")) {
					result.add("Wii");
				} else if (sysDetailsValue.contains("nintendo 3ds")) {
					result.add("3DS");
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
					if (fixedField != null && fixedField.getData().length() >= 22) {
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
	}

	/**
	 * Load information about eContent formats.
	 *
	 * @param econtentRecord
	 * @param econtentItem
	 */
	protected void loadEContentFormatInformation(IlsRecord econtentRecord, EContentIlsItem econtentItem) {

	}

	protected char getSubfieldIndicatorFromConfig(Ini configIni, String subfieldName) {
		String subfieldString = configIni.get("Reindex", subfieldName);
		char subfield = ' ';
		if (subfieldString != null && subfieldString.length() > 0)  {
			subfield = subfieldString.charAt(0);
		}
		return subfield;
	}
}
