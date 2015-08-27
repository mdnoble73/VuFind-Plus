package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.marc.*;

import java.io.*;
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
 * Pika
 * User: Mark Noble
 * Date: 11/26/13
 * Time: 9:30 AM
 */
public abstract class IlsRecordProcessor extends MarcRecordProcessor {
	protected boolean fullReindex;
	protected String individualMarcPath;
	protected String profileType;

	protected String recordNumberTag;
	protected String itemTag;
	protected boolean loadFormatFromItems;
	protected char formatSubfield;
	protected char barcodeSubfield;
	protected char statusSubfieldIndicator;
	protected String statusesToSuppress;
	protected Pattern nonHoldableStatuses;
	protected char shelvingLocationSubfield;
	protected char collectionSubfield;
	protected char dueDateSubfield;
	protected char dateCreatedSubfield;
	protected String dateAddedFormat;
	protected char locationSubfieldIndicator;
	protected Pattern nonHoldableLocations;
	protected String locationsToSuppress;
	protected char subLocationSubfield;
	protected char iTypeSubfield;
	protected Pattern nonHoldableITypes;
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

	private static HashMap<String, Integer> numberOfHoldsByIdentifier = new HashMap<>();

	private HashMap<String, TranslationMap> translationMaps = new HashMap<>();

	public IlsRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, logger);
		this.fullReindex = fullReindex;
		//String marcRecordPath = configIni.get("Reindex", "marcPath");
		try {
			profileType = indexingProfileRS.getString("name");
			individualMarcPath = indexingProfileRS.getString("individualMarcPath");

			recordNumberTag = indexingProfileRS.getString("recordNumberTag");
			suppressItemlessBibs = indexingProfileRS.getBoolean("suppressItemlessBibs");

			itemTag = indexingProfileRS.getString("itemTag");
			itemRecordNumberSubfieldIndicator = getSubfieldIndicatorFromConfig(indexingProfileRS, "itemRecordNumber");

			callNumberPrestampSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "callNumberPrestamp");
			callNumberSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "callNumber");
			callNumberCutterSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "callNumberCutter");
			callNumberPoststampSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "callNumberPoststamp");

			locationSubfieldIndicator = getSubfieldIndicatorFromConfig(indexingProfileRS, "location");
			try {
				String pattern = indexingProfileRS.getString("nonHoldableLocations");
				if (pattern != null && pattern.length() > 0) {
					nonHoldableLocations = Pattern.compile("^(" + pattern + ")$");
				}
			}catch (Exception e){
				logger.error("Could not load non holdable locations", e);
			}
			subLocationSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "subLocation");
			shelvingLocationSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "shelvingLocation");
			collectionSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "collection");
			locationsToSuppress = indexingProfileRS.getString("locationsToSuppress");

			itemUrlSubfieldIndicator = getSubfieldIndicatorFromConfig(indexingProfileRS, "itemUrl");

			loadFormatFromItems = indexingProfileRS.getString("formatSource").equals("item");
			formatSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "format");
			barcodeSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "barcode");
			statusSubfieldIndicator = getSubfieldIndicatorFromConfig(indexingProfileRS, "status");
			statusesToSuppress = indexingProfileRS.getString("statusesToSuppress");
			try {
				String pattern = indexingProfileRS.getString("nonHoldableStatuses");
				if (pattern != null && pattern.length() > 0) {
					nonHoldableStatuses = Pattern.compile("^(" + pattern + ")$");
				}
			}catch (Exception e){
				logger.error("Could not load non holdable statuses", e);
			}

			dueDateSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "dueDate");

			ytdCheckoutSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "yearToDateCheckouts");
			lastYearCheckoutSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "lastYearCheckouts");
			totalCheckoutSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "totalCheckouts");

			iTypeSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "iType");
			try {
				String pattern = indexingProfileRS.getString("nonHoldableITypes");
				if (pattern != null && pattern.length() > 0) {
					nonHoldableITypes = Pattern.compile("^(" + pattern + ")$");
				}
			}catch (Exception e){
				logger.error("Could not load non holdable iTypes", e);
			}

			dateCreatedSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "dateCreated");
			dateAddedFormat = indexingProfileRS.getString("dateCreatedFormat");

			iCode2Subfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "iCode2");
			useICode2Suppression = indexingProfileRS.getBoolean("useICode2Suppression");

			eContentSubfieldIndicator = getSubfieldIndicatorFromConfig(indexingProfileRS, "eContentDescriptor");
			useEContentSubfield = eContentSubfieldIndicator != ' ';

			useItemBasedCallNumbers = indexingProfileRS.getBoolean("useItemBasedCallNumbers");
			volumeSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "volume");


			orderTag = indexingProfileRS.getString("orderTag");
			orderLocationSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "orderLocation");
			orderCopiesSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "orderCopies");
			orderStatusSubfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "orderStatus");
			orderCode3Subfield = getSubfieldIndicatorFromConfig(indexingProfileRS, "orderCode3");

			String additionalCollectionsString = configIni.get("Reindex", "additionalCollections");
			if (additionalCollectionsString != null) {
				additionalCollections = additionalCollectionsString.split(",");
			}

			//loadAvailableItemBarcodes(marcRecordPath, logger);
			loadHoldsByIdentifier(vufindConn, logger);

			loadTranslationMapsForProfile(vufindConn, indexingProfileRS.getLong("id"));
		}catch (Exception e){
			logger.error("Error loading indexing profile information from database", e);
		}
	}

	private void loadTranslationMapsForProfile(Connection vufindConn, long id) throws SQLException{
		PreparedStatement getTranslationMapsStmt = vufindConn.prepareStatement("SELECT * from translation_maps WHERE indexingProfileId = ?");
		PreparedStatement getTranslationMapValuesStmt = vufindConn.prepareStatement("SELECT * from translation_map_values WHERE translationMapId = ?");
		getTranslationMapsStmt.setLong(1, id);
		ResultSet translationsMapRS = getTranslationMapsStmt.executeQuery();
		while (translationsMapRS.next()){
			TranslationMap map = new TranslationMap(profileType, translationsMapRS.getString("name"), fullReindex, translationsMapRS.getBoolean("usesRegularExpressions"), logger);
			Long translationMapId = translationsMapRS.getLong("id");
			getTranslationMapValuesStmt.setLong(1, translationMapId);
			ResultSet translationMapValuesRS = getTranslationMapValuesStmt.executeQuery();
			while (translationMapValuesRS.next()){
				map.addValue(translationMapValuesRS.getString("value"), translationMapValuesRS.getString("translation"));
			}
			translationMaps.put(map.getMapName(), map);
		}
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
		try {
			byte[] fileContents = Util.readFileBytes(individualFilename);
			//FileInputStream inputStream = new FileInputStream(individualFile);
			InputStream inputStream = new ByteArrayInputStream(fileContents);
			MarcPermissiveStreamReader marcReader = new MarcPermissiveStreamReader(inputStream, true, true, "UTF-8");
			if (marcReader.hasNext()){
				record = marcReader.next();
			}
			inputStream.close();
		} catch (Exception e) {
			logger.error("Error reading data from ils file " + individualFilename, e);
		}
		return record;
	}

	@Override
	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		//For ILS Records, we can create multiple different records, one for print and order items,
		//and one or more for eContent items.
		HashSet<RecordInfo> allRelatedRecords = new HashSet<>();

		try{
			//If the entire bib is suppressed, update stats and bail out now.
			if (isBibSuppressed(record)){
				return;
			}

			// Let's first look for the print/order record
			RecordInfo recordInfo = groupedWork.addRelatedRecord(profileType, identifier);
			loadUnsuppressedPrintItems(groupedWork, recordInfo, identifier, record);
			loadOnOrderItems(groupedWork, recordInfo, record, recordInfo.getNumPrintCopies() > 0);
			//If we don't get anything remove the record we just added
			if (recordInfo.getNumPrintCopies() == 0 && recordInfo.getNumCopiesOnOrder() == 0 && suppressItemlessBibs) {
				groupedWork.removeRelatedRecord(recordInfo);
			}else{
				allRelatedRecords.add(recordInfo);
			}

			//Since print formats are loaded at the record level, do it after we have loaded items
			loadPrintFormatInformation(recordInfo, record);

			//Now look for any eContent that is defined within the ils
			List<RecordInfo> econtentRecords = loadUnsuppressedEContentItems(groupedWork, identifier, record);
			allRelatedRecords.addAll(econtentRecords);

			//Do updates based on the overall bib (shared regardless of scoping)
			updateGroupedWorkSolrDataBasedOnStandardMarcData(groupedWork, record, recordInfo.getRelatedItems());

			//Special processing for ILS Records
			String fullDescription = Util.getCRSeparatedString(getFieldList(record, "520a"));
			for (RecordInfo ilsRecord : allRelatedRecords) {
				String primaryFormat = ilsRecord.getPrimaryFormat();
				if (primaryFormat == null){
					primaryFormat = "Unknown";
				}
				groupedWork.addDescription(fullDescription, primaryFormat);
			}
			loadEditions(groupedWork, record, allRelatedRecords);
			loadPhysicalDescription(groupedWork, record, allRelatedRecords);
			loadLanguageDetails(groupedWork, record, allRelatedRecords);
			loadPublicationDetails(groupedWork, record, allRelatedRecords);
			loadSystemLists(groupedWork, record);

			//Do updates based on items
			loadPopularity(groupedWork, identifier);
			groupedWork.addBarcodes(getFieldList(record, itemTag + barcodeSubfield));

			loadOrderIds(groupedWork, record);

			int numPrintItems = recordInfo.getNumPrintCopies();
			if (!suppressItemlessBibs && numPrintItems == 0){
				numPrintItems = 1;
			}
			groupedWork.addHoldings(numPrintItems + recordInfo.getNumCopiesOnOrder());
		}catch (Exception e){
			logger.error("Error updating grouped work for MARC record with identifier " + identifier, e);
		}
	}

	protected boolean isBibSuppressed(Record record) {
		return false;
	}

	protected void loadSystemLists(GroupedWorkSolr groupedWork, Record record) {
		//By default, do nothing
	}

	protected void loadOnOrderItems(GroupedWorkSolr groupedWork, RecordInfo recordInfo, Record record, boolean hasTangibleItems){
		List<DataField> orderFields = getDataFields(record, orderTag);
		for (DataField curOrderField : orderFields){
			int copies = 0;
			//If the location is multi, we actually have several records that should be processed separately
			List<Subfield> detailedLocationSubfield = curOrderField.getSubfields(orderLocationSubfield);
			if (detailedLocationSubfield.size() == 0){
				//Didn't get detailed locations
				if (curOrderField.getSubfield(orderCopiesSubfield) != null){
					copies = Integer.parseInt(curOrderField.getSubfield(orderCopiesSubfield).getData());
				}
				createAndAddOrderItem(recordInfo, curOrderField, "multi", copies);
			} else{
				for (Subfield curLocationSubfield : detailedLocationSubfield){
					String curLocation = curLocationSubfield.getData();
					if (curLocation.startsWith("(")){
						//There are multiple copies for this location
						copies = Integer.parseInt(curLocation.substring(1, curLocation.indexOf(")")));
						curLocation = curLocation.substring(curLocation.indexOf(")") + 1);
					}else{
						copies = 1;
					}
					createAndAddOrderItem(recordInfo, curOrderField, curLocation, copies);
					//For On Order Items, increment popularity based on number of copies that are being purchased.
					groupedWork.addPopularity(copies);
				}
			}
		}
		if (recordInfo.getNumCopiesOnOrder() > 0 && !hasTangibleItems){
			groupedWork.addKeywords("On Order");
			groupedWork.addKeywords("Coming Soon");
			HashSet<String> additionalOrderSubjects = new HashSet<>();
			additionalOrderSubjects.add("On Order");
			additionalOrderSubjects.add("Coming Soon");
			groupedWork.addTopic(additionalOrderSubjects);
			groupedWork.addTopicFacet(additionalOrderSubjects);
		}
	}

	private void createAndAddOrderItem(RecordInfo recordInfo, DataField curOrderField, String location, int copies) {
		ItemInfo itemInfo = new ItemInfo();
		if (curOrderField.getSubfield('a') == null){
			//Skip if we have no identifier
			return;
		}
		String orderNumber = curOrderField.getSubfield('a').getData();
		itemInfo.setLocationCode(location);
		itemInfo.setSubLocationCode("");
		itemInfo.setItemIdentifier(orderNumber);
		itemInfo.setNumCopies(copies);
		itemInfo.setIsEContent(false);
		itemInfo.setIsOrderItem(true);
		itemInfo.setCallNumber("ON ORDER");
		itemInfo.setSortableCallNumber("ON ORDER");
		itemInfo.setDetailedStatus("On Order");
		//Format and Format Category should be set at the record level, so we don't need to set them here.


		//Shelf Location also include the name of the ordering branch if possible
		boolean hasLocationBasedShelfLocation = false;
		boolean hasSystemBasedShelfLocation = false;
		itemInfo.setShelfLocation("On Order");

		String status = "";
		if (curOrderField.getSubfield(orderStatusSubfield) != null) {
			status = curOrderField.getSubfield(orderStatusSubfield).getData();
		}
		String code3 = null;
		if (orderCode3Subfield != ' ' && curOrderField.getSubfield(orderCode3Subfield) != null){
			code3 = curOrderField.getSubfield(orderCode3Subfield).getData();
		}

		if (isOrderItemValid(status, code3)){
			recordInfo.addItem(itemInfo);
			for (Scope scope: indexer.getScopes()){
				if (scope.isItemPartOfScope(profileType, location, "", true, true, false)){
					ScopingInfo scopingInfo = itemInfo.addScope(scope);
					if (scope.isLocationScope()) {
						scopingInfo.setLocallyOwned(scope.isItemOwnedByScope(profileType, location, ""));
					}
					if (scope.isLibraryScope()) {
						 scopingInfo.setLibraryOwned(scope.isItemOwnedByScope(profileType, location, ""));
					}
					if (scopingInfo.isLocallyOwned()){
						if (scope.isLibraryScope() && !hasLocationBasedShelfLocation && !hasSystemBasedShelfLocation){
							hasSystemBasedShelfLocation = true;
						}
						if (scope.isLocationScope() && !hasLocationBasedShelfLocation){
							hasLocationBasedShelfLocation = true;
							itemInfo.setShelfLocation("On Order");
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

	protected boolean isOrderItemValid(String status, String code3) {
		return status.equals("o") || status.equals("1");
	}

	protected void loadEContentSourcesAndProtectionTypes(ItemInfo itemRecord) {
		//By default, do nothing
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

	protected void loadUnsuppressedPrintItems(GroupedWorkSolr groupedWork, RecordInfo recordInfo, String identifier, Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				getPrintIlsItem(groupedWork, recordInfo, record, itemField);
				//Can return null if the record does not have status and location
				//This happens with secondary call numbers sometimes.
			}
		}
	}

	protected RecordInfo getEContentIlsRecord(GroupedWorkSolr groupedWork, Record record, String identifier, DataField itemField){
		ItemInfo itemInfo = new ItemInfo();
		itemInfo.setIsEContent(true);
		RecordInfo relatedRecord = null;

		loadDateAdded(identifier, itemField, itemInfo);
		String itemLocation = getItemSubfieldData(locationSubfieldIndicator, itemField);
		itemInfo.setLocationCode(itemLocation);
		String itemSublocation = getItemSubfieldData(subLocationSubfield, itemField);
		if (itemSublocation == null){
			itemSublocation = "";
		}
		itemInfo.setSubLocationCode(itemSublocation);
		if (itemSublocation.length() > 0){
			itemInfo.setSubLocation(translateValue("sub_location", itemSublocation));
		}
		itemInfo.setITypeCode(getItemSubfieldData(iTypeSubfield, itemField));
		itemInfo.setIType(translateValue("itype", getItemSubfieldData(iTypeSubfield, itemField)));
		loadItemCallNumber(record, itemField, itemInfo);
		itemInfo.setItemIdentifier(getItemSubfieldData(itemRecordNumberSubfieldIndicator, itemField));
		itemInfo.setShelfLocation(getShelfLocationForItem(itemInfo, itemField));

		itemInfo.setCollection(translateValue("collection", getItemSubfieldData(collectionSubfield, itemField)));

		loadEContentSourcesAndProtectionTypes(itemInfo);

		Subfield eContentSubfield = itemField.getSubfield(eContentSubfieldIndicator);
		if (eContentSubfield != null){
			String eContentData = eContentSubfield.getData().trim();
			if (eContentData.indexOf(':') > 0) {
				String[] eContentFields = eContentData.split(":");
				//First element is the source, and we will always have at least the source and protection type
				itemInfo.seteContentSource(eContentFields[0].trim());
				itemInfo.seteContentProtectionType(eContentFields[1].trim().toLowerCase());
				if (eContentFields.length >= 3){
					itemInfo.seteContentSharing(eContentFields[2].trim().toLowerCase());
				}else{
					//Sharing depends on the location code
					if (itemLocation.startsWith("mdl")){
						itemInfo.seteContentSharing("shared");
					}else{
						itemInfo.seteContentSharing("library");
					}
				}

				//Remaining fields have variable definitions based on content that has been loaded over the past year or so
				if (eContentFields.length >= 4){
					//If the 4th field is numeric, it is the number of copies that can be checked out.
					if (Util.isNumeric(eContentFields[3].trim())){
						//ilsEContentItem.setNumberOfCopies(eContentFields[3].trim());
						if (eContentFields.length >= 5){
							itemInfo.seteContentFilename(eContentFields[4].trim());
						}else{
							logger.warn("Filename for local econtent not specified " + eContentData + " " + identifier);
						}
					}else{
						//Field 4 is the filename
						itemInfo.seteContentFilename(eContentFields[3].trim());
					}
				}
			}
		}else{
			//This is for a "less advanced" catalog, set some basic info
			itemInfo.seteContentProtectionType("external");
			itemInfo.seteContentSharing(getEContentSharing(itemInfo, itemField));
			itemInfo.seteContentSource(getSourceType(record, itemField));
		}

		//Set record type
		String protectionType = itemInfo.geteContentProtectionType();
		switch (protectionType) {
			case "acs":
			case "drm":
				relatedRecord = groupedWork.addRelatedRecord("restricted_econtent", identifier);
				relatedRecord.setSubSource(profileType);
				relatedRecord.addItem(itemInfo);
				break;
			case "public domain":
			case "free":
				relatedRecord = groupedWork.addRelatedRecord("public_domain_econtent", identifier);
				relatedRecord.setSubSource(profileType);
				relatedRecord.addItem(itemInfo);
				break;
			case "external":
				relatedRecord = groupedWork.addRelatedRecord("external_econtent", identifier);
				relatedRecord.setSubSource(profileType);
				relatedRecord.addItem(itemInfo);
				break;
			default:
				logger.warn("Unknown protection type " + protectionType + " found in record " + identifier);
				break;
		}

		loadEContentFormatInformation(record, relatedRecord, itemInfo);

		//Get the url if any
		Subfield urlSubfield = itemField.getSubfield(itemUrlSubfieldIndicator);
		if (urlSubfield != null){
			itemInfo.seteContentUrl(urlSubfield.getData().trim());
		}else if (protectionType.equals("external")){
			//Check the 856 tag to see if there is a link there
			List<DataField> urlFields = getDataFields(record, "856");
			for (DataField urlField : urlFields){
				//load url into the item
				if (urlField.getSubfield('u') != null){
					//Try to determine if this is a resource or not.
					if (urlField.getIndicator1() == '4' || urlField.getIndicator1() == ' ' || urlField.getIndicator1() == '0' || urlField.getIndicator1() == '7'){
						if (urlField.getIndicator2() == ' ' || urlField.getIndicator2() == '0' || urlField.getIndicator2() == '1' || urlField.getIndicator2() == '8') {
							itemInfo.seteContentUrl(urlField.getSubfield('u').getData().trim());
							break;
						}
					}

				}
			}

		}

		//Determine availability
		boolean available = false;
		boolean holdable = false;
		switch (protectionType) {
			case "external":
				available = true;
				break;
			case "public domain":
			case "free":
				available = true;
				break;
			case "acs":
			case "drm":
				//TODO: Determine availability based on if it is checked out in the database
				available = true;
				holdable = true;
				break;
		}

		if (available){
			itemInfo.setDetailedStatus("Available Online");
		}else{
			itemInfo.setDetailedStatus("Checked Out");
		}
		//Determine which scopes this title belongs to
		for (Scope curScope : indexer.getScopes()){
			if (curScope.isItemPartOfScope(profileType, itemLocation, itemSublocation, holdable, false, true)){
				ScopingInfo scopingInfo = itemInfo.addScope(curScope);
				scopingInfo.setAvailable(available);
				if (available) {
					scopingInfo.setStatus("Available Online");
					scopingInfo.setGroupedStatus("Available Online");
				}else{
					scopingInfo.setStatus("Checked Out");
					scopingInfo.setGroupedStatus("Checked Out");
				}
				scopingInfo.setHoldable(holdable);
				if (curScope.isLocationScope()) {
					scopingInfo.setLocallyOwned(curScope.isItemOwnedByScope(profileType, itemLocation, itemSublocation));
				}
				if (curScope.isLibraryScope()) {
					scopingInfo.setLibraryOwned(curScope.isItemOwnedByScope(profileType, itemLocation, itemSublocation));
				}
			}
		}

		return relatedRecord;
	}

	protected void loadDateAdded(String recordIdentifier, DataField itemField, ItemInfo itemInfo) {
		String dateAddedStr = getItemSubfieldData(dateCreatedSubfield, itemField);
		if (dateAddedStr != null) {
			try {
				if (dateAddedFormatter == null){
					dateAddedFormatter = new SimpleDateFormat(dateAddedFormat);
				}
				Date dateAdded = dateAddedFormatter.parse(dateAddedStr);
				itemInfo.setDateAdded(dateAdded);
			} catch (ParseException e) {
				logger.error("Error processing date added for record identifier " + recordIdentifier + " profile " + profileType + " using format " + dateAddedFormat, e);
			}
		}
	}

	protected String getEContentSharing(ItemInfo ilsEContentItem, DataField itemField) {
		return "shared";
	}

	protected String getSourceType(Record record, DataField itemField) {
		return "Unknown Source";
	}

	protected static SimpleDateFormat dateAddedFormatter = null;
	protected ItemInfo getPrintIlsItem(GroupedWorkSolr groupedWork, RecordInfo recordInfo, Record record, DataField itemField) {
		if (dateAddedFormatter == null){
			dateAddedFormatter = new SimpleDateFormat(dateAddedFormat);
		}
		ItemInfo itemInfo = new ItemInfo();
		//Load base information from the Marc Record

		String itemStatus = getItemStatus(itemField);

		String itemLocation = getItemSubfieldData(locationSubfieldIndicator, itemField);
		itemInfo.setLocationCode(itemLocation);
		String itemSublocation = getItemSubfieldData(subLocationSubfield, itemField);
		if (itemSublocation == null){
			itemSublocation = "";
		}
		itemInfo.setSubLocationCode(itemSublocation);
		if (itemSublocation.length() > 0){
			itemInfo.setSubLocation(translateValue("sub_location", itemSublocation));
		}

		//if the status and location are null, we can assume this is not a valid item
		if (!isItemValid(itemStatus, itemLocation)) return null;

		itemInfo.setShelfLocationCode(getItemSubfieldData(locationSubfieldIndicator, itemField));
		itemInfo.setShelfLocation(getShelfLocationForItem(itemInfo, itemField));

		loadDateAdded(recordInfo.getRecordIdentifier(), itemField, itemInfo);
		String dueDateStr = getItemSubfieldData(dueDateSubfield, itemField);
		itemInfo.setDueDate(dueDateStr);

		itemInfo.setITypeCode(getItemSubfieldData(iTypeSubfield, itemField));
		itemInfo.setIType(translateValue("itype", getItemSubfieldData(iTypeSubfield, itemField)));

		double itemPopularity = getItemPopularity(itemField);
		groupedWork.addPopularity(itemPopularity);

		loadItemCallNumber(record, itemField, itemInfo);
		itemInfo.setItemIdentifier(getItemSubfieldData(itemRecordNumberSubfieldIndicator, itemField));

		itemInfo.setCollection(translateValue("collection", getItemSubfieldData(collectionSubfield, itemField)));

		//set status towards the end so we can access date added and other things that may need to
		itemInfo.setStatusCode(itemStatus);
		if (itemStatus != null) {
			setDetailedStatus(itemInfo, itemField, itemStatus);
		}

		if (loadFormatFromItems && formatSubfield != ' '){
			String format = getItemSubfieldData(formatSubfield, itemField);
			if (format != null) {
				itemInfo.setFormat(translateValue("format", format));
				itemInfo.setFormatCategory(translateValue("format_category", format));
				String formatBoost = translateValue("format_boost", format);
				try {
					if (formatBoost != null && formatBoost.length() > 0) {
						recordInfo.setFormatBoost(Integer.parseInt(formatBoost));
					}
				} catch (Exception e) {
					logger.warn("Could not get boost for format " + format);
				}
			}
		}

		//Determine Availability
		boolean available = isItemAvailable(itemInfo);

		//Determine which scopes have access to this record
		String displayStatus = getDisplayStatus(itemInfo);
		String groupedDisplayStatus = getDisplayGroupedStatus(itemInfo);

		for (Scope curScope : indexer.getScopes()) {
			//Check to see if the record is holdable for this scope
			HoldabilityInformation isHoldable = isItemHoldable(itemInfo, curScope);
			BookabilityInformation isBookable = isItemBookable(itemInfo, curScope);
			if (curScope.isItemPartOfScope(profileType, itemLocation, itemSublocation, isHoldable.isHoldable(), false, false)){
				ScopingInfo scopingInfo = itemInfo.addScope(curScope);
				scopingInfo.setAvailable(available);
				scopingInfo.setHoldable(isHoldable.isHoldable());
				scopingInfo.setHoldablePTypes(isHoldable.getHoldablePTypes().toString());
				scopingInfo.setBookable(isBookable.isBookable());
				scopingInfo.setBookablePTypes(isBookable.getBookablePTypes().toString());

				scopingInfo.setInLibraryUseOnly(determineLibraryUseOnly(itemInfo, curScope));

				scopingInfo.setStatus(displayStatus);
				scopingInfo.setGroupedStatus(groupedDisplayStatus);
				if (curScope.isLocationScope()) {
					scopingInfo.setLocallyOwned(curScope.isItemOwnedByScope(profileType, itemLocation, itemSublocation));
				}
				if (curScope.isLibraryScope()) {
					scopingInfo.setLibraryOwned(curScope.isItemOwnedByScope(profileType, itemLocation, itemSublocation));
				}
			}
		}

		recordInfo.addItem(itemInfo);
		return itemInfo;
	}

	protected boolean determineLibraryUseOnly(ItemInfo itemInfo, Scope curScope) {
		return false;
	}

	protected void setDetailedStatus(ItemInfo itemInfo, DataField itemField, String itemStatus) {
		itemInfo.setDetailedStatus(translateValue("detailed_status", itemStatus));
	}

	protected String getDisplayGroupedStatus(ItemInfo itemInfo) {
		return translateValue("item_grouped_status", itemInfo.getStatusCode());
	}

	protected String getDisplayStatus(ItemInfo itemInfo) {
		return translateValue("item_status", itemInfo.getStatusCode());
	}

	protected double getItemPopularity(DataField itemField) {
		String totalCheckoutsField = getItemSubfieldData(totalCheckoutSubfield, itemField);
		int totalCheckouts = 0;
		if (totalCheckoutsField != null){
			totalCheckouts = Integer.parseInt(totalCheckoutsField);
		}
		String ytdCheckoutsField = getItemSubfieldData(ytdCheckoutSubfield, itemField);
		int ytdCheckouts = 0;
		if (ytdCheckoutsField != null){
			ytdCheckouts = Integer.parseInt(ytdCheckoutsField);
		}
		String lastYearCheckoutsField = getItemSubfieldData(lastYearCheckoutSubfield, itemField);
		int lastYearCheckouts = 0;
		if (lastYearCheckoutsField != null){
			lastYearCheckouts = Integer.parseInt(lastYearCheckoutsField);
		}
		double itemPopularity = ytdCheckouts + .5 * (lastYearCheckouts) + .1 * (totalCheckouts - lastYearCheckouts - ytdCheckouts);
		if (itemPopularity == 0){
			itemPopularity = 1;
		}
		return itemPopularity;
	}

	protected boolean isItemValid(String itemStatus, String itemLocation) {
		return !(itemStatus == null && itemLocation == null);
	}

	private void loadItemCallNumber(Record record, DataField itemField, ItemInfo itemInfo) {
		if (useItemBasedCallNumbers) {
			String callNumberPreStamp = getItemSubfieldDataWithoutTrimming(callNumberPrestampSubfield, itemField);
			String callNumber = getItemSubfieldDataWithoutTrimming(callNumberSubfield, itemField);
			String callNumberCutter = getItemSubfieldDataWithoutTrimming(callNumberCutterSubfield, itemField);
			String callNumberPostStamp = getItemSubfieldData(callNumberPoststampSubfield, itemField);
			String volume = getItemSubfieldData(volumeSubfield, itemField);

			StringBuilder fullCallNumber = new StringBuilder();
			StringBuilder sortableCallNumber = new StringBuilder();
			if (callNumberPreStamp != null) {
				fullCallNumber.append(callNumberPreStamp);
			}
			if (callNumber != null){
				if (fullCallNumber.length() > 0 && fullCallNumber.charAt(fullCallNumber.length() - 1) != ' '){
					fullCallNumber.append(' ');
				}
				fullCallNumber.append(callNumber);
				sortableCallNumber.append(callNumber);
			}
			if (callNumberCutter != null){
				if (fullCallNumber.length() > 0 && fullCallNumber.charAt(fullCallNumber.length() - 1) != ' '){
					fullCallNumber.append(' ');
				}
				fullCallNumber.append(callNumberCutter);
				if (sortableCallNumber.length() > 0 && sortableCallNumber.charAt(sortableCallNumber.length() - 1) != ' '){
					sortableCallNumber.append(' ');
				}
				sortableCallNumber.append(callNumberCutter);
			}
			if (callNumberPostStamp != null){
				if (fullCallNumber.length() > 0 && fullCallNumber.charAt(fullCallNumber.length() - 1) != ' '){
					fullCallNumber.append(' ');
				}
				fullCallNumber.append(callNumberPostStamp);
				if (sortableCallNumber.length() > 0 && sortableCallNumber.charAt(sortableCallNumber.length() - 1) != ' '){
					sortableCallNumber.append(' ');
				}
				sortableCallNumber.append(callNumberPostStamp);
			}
			if (volume != null){
				if (fullCallNumber.length() > 0 && fullCallNumber.charAt(fullCallNumber.length() - 1) != ' '){
					fullCallNumber.append(' ');
				}
				fullCallNumber.append(volume);
				if (sortableCallNumber.length() > 0 && sortableCallNumber.charAt(sortableCallNumber.length() - 1) != ' '){
					sortableCallNumber.append(' ');
				}
				sortableCallNumber.append(volume);
			}
			itemInfo.setCallNumber(fullCallNumber.toString());
			itemInfo.setSortableCallNumber(sortableCallNumber.toString());
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
				itemInfo.setCallNumber(callNumber.trim());
				itemInfo.setSortableCallNumber(callNumber.trim());
			}
		}
	}

	protected HoldabilityInformation isItemHoldable(ItemInfo itemInfo, Scope curScope){
		if (nonHoldableITypes != null && itemInfo.getITypeCode() != null && itemInfo.getITypeCode().length() > 0){
			if (nonHoldableITypes.matcher(itemInfo.getITypeCode()).matches()){
				return new HoldabilityInformation(false, new HashSet<Long>());
			}
		}
		if (nonHoldableLocations != null && itemInfo.getLocationCode() != null && itemInfo.getLocationCode().length() > 0){
			if (nonHoldableLocations.matcher(itemInfo.getLocationCode()).matches()){
				return new HoldabilityInformation(false, new HashSet<Long>());
			}
		}
		if (nonHoldableStatuses != null && itemInfo.getStatusCode() != null && itemInfo.getStatusCode().length() > 0){
			if (nonHoldableStatuses.matcher(itemInfo.getStatusCode()).matches()){
				return new HoldabilityInformation(false, new HashSet<Long>());
			}
		}
		return new HoldabilityInformation(true, new HashSet<Long>());
	}

	protected BookabilityInformation isItemBookable(ItemInfo itemInfo, Scope curScope) {
		return new BookabilityInformation(false, new HashSet<Long>());
	}

	protected String getShelfLocationForItem(ItemInfo itemInfo, DataField itemField) {
		String shelfLocation = getItemSubfieldData(locationSubfieldIndicator, itemField);
		if (shelfLocation == null || shelfLocation.length() == 0 || shelfLocation.equals("none")){
			return "";
		}else {
			return translateValue("shelf_location", shelfLocation);
		}
	}

	protected String getItemStatus(DataField itemField){
		return getItemSubfieldData(statusSubfieldIndicator, itemField);
	}

	protected abstract boolean isItemAvailable(ItemInfo itemInfo);

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

	protected List<RecordInfo> loadUnsuppressedEContentItems(GroupedWorkSolr groupedWork, String identifier, Record record){
		return new ArrayList<>();
	}



	protected void loadPopularity(GroupedWorkSolr groupedWork, String recordIdentifier) {
		//Add popularity based on the number of holds (we have already done popularity for prior checkouts)
		//Active holds indicate that a title is more interesting so we will count each hold at double value
		double popularity = 2 * getIlsHoldsForTitle(recordIdentifier);
		groupedWork.addPopularity(popularity);
	}

	private int getIlsHoldsForTitle(String recordIdentifier) {
		if (numberOfHoldsByIdentifier.containsKey(recordIdentifier)){
			return numberOfHoldsByIdentifier.get(recordIdentifier);
		}else {
			return 0;
		}
	}

	protected boolean isItemSuppressed(DataField curItem) {
		if (statusSubfieldIndicator != ' ') {
			Subfield statusSubfield = curItem.getSubfield(statusSubfieldIndicator);
			if (statusSubfield == null) {
				return true;
			} else {
				if (statusSubfield.getData().matches(statusesToSuppress)) {
					return true;
				}
			}
		}
		Subfield locationSubfield = curItem.getSubfield(locationSubfieldIndicator);
		if (locationSubfield == null){
			return true;
		}else{
			if (locationSubfield.getData().matches(locationsToSuppress)){
				return true;
			}
		}
		return false;
	}

	/**
	 * Determine Record Format(s)
	 */
	/**
	 * Determine Record Format(s)
	 */
	public void loadPrintFormatInformation(RecordInfo recordInfo, Record record){
		LinkedHashSet<String> printFormats = new LinkedHashSet<>();

		//We should already have formats based on the items
		if (loadFormatFromItems && formatSubfield != ' ' && recordInfo.hasItemFormats()){
			return;
		}

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
		getFormatFromDigitalFileCharacteristics(record, printFormats);
		getFormatFromLeader(printFormats, leader, fixedField);

		if (printFormats.size() == 0){
			logger.debug("Did not get any formats for print record " + recordInfo.getFullIdentifier() + ", assuming it is a book ");
			printFormats.add("Book");
		}

		filterPrintFormats(printFormats);

		HashSet<String> translatedFormats = translateCollection("format", printFormats);
		HashSet<String> translatedFormatCategories = translateCollection("format_category", printFormats);
		recordInfo.addFormats(translatedFormats);
		recordInfo.addFormatCategories(translatedFormatCategories);
		Long formatBoost = 0L;
		HashSet<String> formatBoosts = translateCollection("format_boost", printFormats);
		for (String tmpFormatBoost : formatBoosts){
			try {
				Long tmpFormatBoostLong = Long.parseLong(tmpFormatBoost);
				if (tmpFormatBoostLong > formatBoost) {
					formatBoost = tmpFormatBoostLong;
				}
			}catch (NumberFormatException e){
				logger.warn("Could not load format boost for format " + tmpFormatBoost + " profile " + profileType);
			}
		}
		recordInfo.setFormatBoost(formatBoost);
	}

	private void getFormatFromDigitalFileCharacteristics(Record record, LinkedHashSet<String> printFormats) {
		Set<String> fields = getFieldList(record, "347b");
		for (String curField : fields){
			if (curField.equalsIgnoreCase("Blu-Ray")){
				printFormats.add("Blu-ray");
			}else if (curField.equalsIgnoreCase("DVD video")){
				printFormats.add("DVD");
			}
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
		}else if (printFormats.contains("Playaway") && printFormats.contains("Video")){
			printFormats.remove("Video");
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
		}else if (printFormats.contains("Book") && printFormats.contains("Kit")){
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
			}else if (titleMedium.contains("book club kit")){
				printFormats.add("BookClubKit");
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
				} else if (sysDetailsValue.contains("xbox one") && !sysDetailsValue.contains("compatible")) {
					result.add("XboxOne");
				} else if (sysDetailsValue.contains("xbox") && !sysDetailsValue.contains("compatible")) {
					result.add("Xbox360");
				} else if (sysDetailsValue.contains("playstation 4") && !sysDetailsValue.contains("compatible")) {
					result.add("PlayStation4");
				} else if (sysDetailsValue.contains("playstation 3") && !sysDetailsValue.contains("compatible")) {
					result.add("PlayStation3");
				} else if (sysDetailsValue.contains("playstation") && !sysDetailsValue.contains("compatible")) {
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
								if (!subfieldVData.contains("television adaptation")){
									okToAdd = true;
								}else{
									//System.out.println("Not including graphic novel format");
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
									//System.out.println("Not including graphic novel format");
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
	 * @param record
	 * @param econtentRecord The record to load format information for
	 * @param econtentItem   The item to load format information for
	 */
	protected void loadEContentFormatInformation(Record record, RecordInfo econtentRecord, ItemInfo econtentItem) {

	}

	protected char getSubfieldIndicatorFromConfig(ResultSet indexingProfileRS, String subfieldName) throws SQLException{
		String subfieldString = indexingProfileRS.getString(subfieldName);
		char subfield = ' ';
		if (!indexingProfileRS.wasNull() && subfieldString.length() > 0)  {
			subfield = subfieldString.charAt(0);
		}
		return subfield;
	}

	public String translateValue(String mapName, String value){
		if (value == null){
			return null;
		}
		TranslationMap translationMap = translationMaps.get(mapName);
		String translatedValue;
		if (translationMap == null){
			logger.error("Unable to find translation map for " + mapName + " in profile " + profileType);
			translatedValue = value;
		}else{
			translatedValue = translationMap.translateValue(value);
		}
		return translatedValue;
	}

	public HashSet<String> translateCollection(String mapName, HashSet<String> values) {
		TranslationMap translationMap = translationMaps.get(mapName);
		HashSet<String> translatedValues;
		if (translationMap == null){
			logger.error("Unable to find translation map for " + mapName + " in profile " + profileType);
			translatedValues = values;
		}else{
			translatedValues = translationMap.translateCollection(values);
		}
		return translatedValues;

	}
}
