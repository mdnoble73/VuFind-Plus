package org.vufind;

import au.com.bytecode.opencsv.CSVReader;
import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;

import java.io.File;
import java.io.FileReader;
import java.sql.Connection;
import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;

/**
 * Custom Record Processing for Arlington
 *
 * Pika
 * User: Mark Noble
 * Date: 10/15/2015
 * Time: 9:48 PM
 */
public class ArlingtonRecordProcessor extends IIIRecordProcessor {
	private HashMap <String, ArrayList<OrderInfo>> orderInfoFromExport = new HashMap();
	private String exportPath;
	public ArlingtonRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, vufindConn, configIni, indexingProfileRS, logger, fullReindex);

		languageFields = "008[35-37]:041b";
		try {
			exportPath = indexingProfileRS.getString("marcPath");
		}catch (Exception e){
			logger.error("Unable to load marc path from indexing profile");
		}
		loadOrderInformation();
	}

	private void loadOrderInformation() {
		File activeOrders = new File(this.exportPath + "/active_orders.csv");
		if (activeOrders.exists()){
			try{
				CSVReader reader = new CSVReader(new FileReader(activeOrders));
				//First line is headers
				reader.readNext();
				String[] orderData;
				while ((orderData = reader.readNext()) != null){
					OrderInfo orderRecord = new OrderInfo();
					String recordId = ".b" + orderData[0] + getCheckDigit(orderData[0]);
					orderRecord.setRecordId(recordId);
					String orderRecordId = ".o" + orderData[1] + getCheckDigit(orderData[1]);
					orderRecord.setOrderRecordId(orderRecordId);
					orderRecord.setStatus(orderData[3]);
					orderRecord.setNumCopies(Integer.parseInt(orderData[4]));
					//Get the order record based on the accounting unit
					orderRecord.setLocationCode(orderData[5]);
					if (orderInfoFromExport.containsKey(recordId)){
						orderInfoFromExport.get(recordId).add(orderRecord);
					}else{
						ArrayList<OrderInfo> orderRecordColl = new ArrayList<OrderInfo>();
						orderRecordColl.add(orderRecord);
						orderInfoFromExport.put(recordId, orderRecordColl);
					}
				}
			}catch(Exception e){
				logger.error("Error loading order records from active orders", e);
			}
		}
	}

	@Override
	protected boolean loanRulesAreBasedOnCheckoutLocation() {
		return false;
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		boolean available = false;
		String status = itemInfo.getStatusCode();
		String dueDate = itemInfo.getDueDate() == null ? "" : itemInfo.getDueDate();
		String availableStatus = "-";
		if (availableStatus.indexOf(status.charAt(0)) >= 0) {
			if (dueDate.length() == 0 || dueDate.trim().equals("-  -")) {
				available = true;
			}
		}
		return available;
	}

	protected void loadOnOrderItems(GroupedWorkSolr groupedWork, RecordInfo recordInfo, Record record, boolean hasTangibleItems){
		ArrayList<OrderInfo> orderItems = orderInfoFromExport.get(recordInfo.getRecordIdentifier());
		if (orderItems != null) {
			for (OrderInfo orderItem : orderItems) {
				createAndAddOrderItem(recordInfo, orderItem);
				//For On Order Items, increment popularity based on number of copies that are being purchased.
				groupedWork.addPopularity(orderItem.getNumCopies());
			}
			if (recordInfo.getNumCopiesOnOrder() > 0 && !hasTangibleItems) {
				groupedWork.addKeywords("On Order");
				groupedWork.addKeywords("Coming Soon");
				HashSet<String> additionalOrderSubjects = new HashSet<>();
				additionalOrderSubjects.add("On Order");
				additionalOrderSubjects.add("Coming Soon");
				groupedWork.addTopic(additionalOrderSubjects);
				groupedWork.addTopicFacet(additionalOrderSubjects);
			}
		}
	}

	private void createAndAddOrderItem(RecordInfo recordInfo, OrderInfo orderItem) {
		ItemInfo itemInfo = new ItemInfo();
		String orderNumber = orderItem.getOrderRecordId();
		String location = orderItem.getLocationCode();
		itemInfo.setLocationCode(orderItem.getLocationCode());
		itemInfo.setItemIdentifier(orderNumber);
		itemInfo.setNumCopies(orderItem.getNumCopies());
		itemInfo.setIsEContent(false);
		itemInfo.setIsOrderItem(true);
		itemInfo.setCallNumber("ON ORDER");
		itemInfo.setSortableCallNumber("ON ORDER");
		itemInfo.setDetailedStatus("On Order");
		//Format and Format Category should be set at the record level, so we don't need to set them here.

		//Shelf Location also include the name of the ordering branch if possible
		boolean hasLocationBasedShelfLocation = false;
		boolean hasSystemBasedShelfLocation = false;

		//Add the library this is on order for
		itemInfo.setShelfLocation("On Order");

		String status = orderItem.getStatus();

		if (isOrderItemValid(status, null)){
			recordInfo.addItem(itemInfo);
			for (Scope scope: indexer.getScopes()){
				if (scope.isItemPartOfScope(profileType, location, "", true, true, false)){
					ScopingInfo scopingInfo = itemInfo.addScope(scope);
					if (scope.isLocationScope()) {
						scopingInfo.setLocallyOwned(scope.isItemOwnedByScope(profileType, location, ""));
					}
					if (scope.isLibraryScope()) {
						boolean libraryOwned = scope.isItemOwnedByScope(profileType, location, "");
						scopingInfo.setLibraryOwned(libraryOwned);
						if (itemInfo.getShelfLocation().equals("On Order")){
							itemInfo.setShelfLocation(scopingInfo.getScope().getFacetLabel() + " On Order");
						}
					}
					if (scopingInfo.isLocallyOwned()){
						if (scope.isLibraryScope() && !hasLocationBasedShelfLocation && !hasSystemBasedShelfLocation){
							hasSystemBasedShelfLocation = true;
						}
						if (scope.isLocationScope() && !hasLocationBasedShelfLocation){
							hasLocationBasedShelfLocation = true;
							if (itemInfo.getShelfLocation().equals("On Order")) {
								itemInfo.setShelfLocation(scopingInfo.getScope().getFacetLabel() + "On Order");
							}
						}
					}
					scopingInfo.setAvailable(false);
					scopingInfo.setHoldable(true);
					scopingInfo.setStatus("On Order");
					scopingInfo.setGroupedStatus("On Order");
				}
			}
		}
	}

	@Override
	protected void loadLiteraryForms(GroupedWorkSolr groupedWork, Record record, HashSet<ItemInfo> printItems, String identifier) {
		//For Arlington we can load the literary forms based off of the location code:
		// ??f?? = Fiction
		// ??n?? = Non-Fiction
		// ??x?? = Other
		String literaryForm = null;
		for (ItemInfo printItem : printItems){
			String locationCode = printItem.getShelfLocationCode();
			if (locationCode != null) {
				literaryForm = getLiteraryFormForLocation(locationCode);
				if (literaryForm != null){
					break;
				}
			}
		}
		if (printItems.size() == 0){
			String bibLocation = getFirstFieldVal(record, "998a");
			if (bibLocation != null) {
				literaryForm = getLiteraryFormForLocation(bibLocation);
			}
		}
		if (literaryForm == null){
			literaryForm = "Other";
		}
		groupedWork.addLiteraryForm(literaryForm);
		groupedWork.addLiteraryFormFull(literaryForm);
	}

	private String getLiteraryFormForLocation(String locationCode) {
		String literaryForm = null;
		if (locationCode.length() >= 3) {
			if (locationCode.charAt(2) == 'f') {
				literaryForm = "Fiction";
			} else if (locationCode.charAt(2) == 'n') {
				literaryForm = "Non Fiction";
			}
		}
		return literaryForm;
	}

	@Override
	protected void loadTargetAudiences(GroupedWorkSolr groupedWork, Record record, HashSet<ItemInfo> printItems, String identifier) {
		//For Arlington we can load the target audience based off of the location code:
		// ?a??? = Adult
		// ?j??? = Kids
		// ?y??? = Teen
		HashSet<String> targetAudiences = new HashSet<>();
		for (ItemInfo printItem : printItems){
			String locationCode = printItem.getShelfLocationCode();
			if (addTargetAudienceBasedOnLocationCode(targetAudiences, locationCode)) break;
		}
		if (printItems.size() == 0){
			String bibLocation = getFirstFieldVal(record, "998a");
			if (bibLocation != null) {
				addTargetAudienceBasedOnLocationCode(targetAudiences, bibLocation);
			}
		}
		if (targetAudiences.size() == 0){
			targetAudiences.add("Other");
		}
		groupedWork.addTargetAudiences(targetAudiences);
		groupedWork.addTargetAudiencesFull(targetAudiences);
	}

	private boolean addTargetAudienceBasedOnLocationCode(HashSet<String> targetAudiences, String locationCode) {
		if (locationCode != null) {
			if (locationCode.length() >= 2) {
				if (locationCode.charAt(1) == 'a') {
					targetAudiences.add("Adult");
					return true;
				} else if (locationCode.charAt(1) == 'j') {
					targetAudiences.add("Juvenile");
					return true;
				} else if (locationCode.charAt(1) == 'y') {
					targetAudiences.add("Young Adult");
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Load format information for the record.  For arlington, we will load from the material type (998d)
	 * @param recordInfo
	 * @param record
	 */
	public void loadPrintFormatInformation(RecordInfo recordInfo, Record record){
		String matType = getFirstFieldVal(record, "998d");
		String translatedFormat = translateValue("format", matType, recordInfo.getRecordIdentifier());
		String translatedFormatCategory = translateValue("format_category", matType, recordInfo.getRecordIdentifier());
		recordInfo.addFormat(translatedFormat);
		if (translatedFormatCategory != null) {
			recordInfo.addFormatCategory(translatedFormatCategory);
		}
		String formatBoost = translateValue("format_boost", matType, recordInfo.getRecordIdentifier());
		try {
			Long tmpFormatBoostLong = Long.parseLong(formatBoost);
			recordInfo.setFormatBoost(tmpFormatBoostLong);
		} catch (NumberFormatException e) {
			logger.warn("Could not load format boost for format " + formatBoost + " profile " + profileType);
		}
	}

	@Override
	protected List<RecordInfo> loadUnsuppressedEContentItems(GroupedWorkSolr groupedWork, String identifier, Record record){
		List<RecordInfo> unsuppressedEcontentRecords = new ArrayList<>();
		//For arlington, eContent will always have no items on the bib record.
		List<DataField> items = getDataFields(record, itemTag);
		if (items.size() > 0){
			return unsuppressedEcontentRecords;
		}else{
			//No items so we can continue on.
			//Check the mat type
			String matType = getFirstFieldVal(record, "998d");
			//Get the bib location
			String bibLocation = getFirstFieldVal(record, "998a");
			//Get the url
			String url = getFirstFieldVal(record, "856u");

			if (url != null && !url.toLowerCase().contains("lib.overdrive.com")){
				//Get the econtent source
				String urlLower = url.toLowerCase();
				String econtentSource;
				String urlText = getFirstFieldVal(record, "856z");
				if (urlText != null){
					urlText = urlText.toLowerCase();
					if (urlText.contains("gale virtual reference library")){
						econtentSource = "Gale Virtual Reference Library";
					}else if (urlText.contains("gale directory library")){
						econtentSource = "Gale Directory Library" ;
					}else if (urlText.contains("hoopla")){
						econtentSource = "Hoopla";
					}else if (urlText.contains("national geographic virtual library")){
						econtentSource = "National Geographic Virtual Library";
					}else if ((urlText.contains("ebscohost") || urlLower.contains("netlibrary") || urlLower.contains("ebsco"))){
						econtentSource = "EbscoHost";
					}else{
						econtentSource = "Premium Sites";
					}
				}else{
					econtentSource = "Premium Sites";
				}

				ItemInfo itemInfo = new ItemInfo();
				itemInfo.setIsEContent(true);
				itemInfo.setLocationCode(bibLocation);
				itemInfo.seteContentProtectionType("external");
				itemInfo.setCallNumber("Online");
				itemInfo.seteContentSource(econtentSource);
				itemInfo.setShelfLocation(econtentSource);
				RecordInfo relatedRecord = groupedWork.addRelatedRecord("external_econtent", identifier);
				relatedRecord.setSubSource(profileType);
				relatedRecord.addItem(itemInfo);
				itemInfo.seteContentUrl(url);

				//Set the format based on the material type
				itemInfo.setFormat(translateValue("format", matType, identifier));
				itemInfo.setFormatCategory(translateValue("format_category", matType, identifier));
				String boostStr = translateValue("format_boost", matType, identifier);
				try{
					int boost = Integer.parseInt(boostStr);
					relatedRecord.setFormatBoost(boost);
				} catch (Exception e){
					logger.warn("Unable to load boost for " + identifier + " got boost " + boostStr);
				}

				itemInfo.setDetailedStatus("Available Online");

				//Determine which scopes this title belongs to
				for (Scope curScope : indexer.getScopes()){
					if (curScope.isItemPartOfScope(profileType, bibLocation, "", false, false, true)){
						ScopingInfo scopingInfo = itemInfo.addScope(curScope);
						scopingInfo.setAvailable(true);
						scopingInfo.setStatus("Available Online");
						scopingInfo.setGroupedStatus("Available Online");
						scopingInfo.setHoldable(false);
						if (curScope.isLocationScope()) {
							scopingInfo.setLocallyOwned(curScope.isItemOwnedByScope(profileType, bibLocation, ""));
						}
						if (curScope.isLibraryScope()) {
							scopingInfo.setLibraryOwned(curScope.isItemOwnedByScope(profileType, bibLocation, ""));
						}
					}
				}

				unsuppressedEcontentRecords.add(relatedRecord);
			}
		}
		return unsuppressedEcontentRecords;
	}
}
