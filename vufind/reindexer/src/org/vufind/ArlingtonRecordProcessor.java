package org.vufind;

import org.apache.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.*;

/**
 * Custom Record Processing for Arlington
 *
 * Pika
 * User: Mark Noble
 * Date: 10/15/2015
 * Time: 9:48 PM
 */
class ArlingtonRecordProcessor extends IIIRecordProcessor {

	ArlingtonRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, vufindConn, indexingProfileRS, logger, fullReindex);

		languageFields = "008[35-37]";

		loadOrderInformationFromExport();

		validCheckedOutStatusCodes.add("o");
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
		String availableStatus = "-o";
		if (availableStatus.indexOf(status.charAt(0)) >= 0) {
			if (dueDate.length() == 0 || dueDate.trim().equals("-  -")) {
				available = true;
			}
		}
		return available;
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
		if (literaryForm == null){
			Set<String> bibLocations = MarcUtil.getFieldList(record, "998a");
			for (String bibLocation : bibLocations){
			  if (bibLocation.length() <= 5) {
				  literaryForm = getLiteraryFormForLocation(bibLocation);
				  if (literaryForm != null){
					  break;
				  }
			  }
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
		if (targetAudiences.size() == 0){
			Set<String> bibLocations = MarcUtil.getFieldList(record, "998a");
			for (String bibLocation : bibLocations){
				if (bibLocation.length() <= 5) {
					if (addTargetAudienceBasedOnLocationCode(targetAudiences, bibLocation)) break;
				}
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
	 */
	public void loadPrintFormatInformation(RecordInfo recordInfo, Record record){
		String matType = MarcUtil.getFirstFieldVal(record, "998d");
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

	protected void loadUnsuppressedPrintItems(GroupedWorkSolr groupedWork, RecordInfo recordInfo, String identifier, Record record){
		super.loadUnsuppressedPrintItems(groupedWork, recordInfo, identifier, record);
		if (recordInfo.getNumPrintCopies() == 0){
			String matType = MarcUtil.getFirstFieldVal(record, "998d");
			if (matType != null && (matType.equals("w") || matType.equals("b"))){
				//We may have multiple items
				Set<String> locationFields = MarcUtil.getFieldList(record, "998a");
				for(String locationField: locationFields){
					ItemInfo itemInfo = new ItemInfo();
					//Load base information from the Marc Record
					String locationCode = locationField.trim();

					//Remove a count of items
					locationCode = locationCode.replaceAll("\\(\\d+\\)", "").trim();

					String itemStatus = "Library Use Only";

					itemInfo.setLocationCode(locationCode);

					//if the status and location are null, we can assume this is not a valid item
					if (!isItemValid(itemStatus, locationCode)) return;

					itemInfo.setShelfLocationCode(locationCode);
					itemInfo.setShelfLocation(getShelfLocationForItem(itemInfo, null, recordInfo.getRecordIdentifier()));

					loadItemCallNumber(record, null, itemInfo);

					itemInfo.setCollection(translateValue("collection", locationCode, recordInfo.getRecordIdentifier()));

					//set status towards the end so we can access date added and other things that may need to
					itemInfo.setStatusCode(itemStatus);
					itemInfo.setDetailedStatus(itemStatus);

					groupedWork.addKeywords(locationCode);

					recordInfo.addItem(itemInfo);
				}
			}
		}
	}

	//TODO: Can this use the main method?
	@Override
	void loadScopeInfoForPrintIlsItem(RecordInfo recordInfo, HashSet<String> audiences, ItemInfo itemInfo, Record record) {
		//Determine Availability
		boolean available = isItemAvailable(itemInfo);

		//Determine which scopes have access to this record
		String displayStatus = getDisplayStatus(itemInfo, recordInfo.getRecordIdentifier());
		String groupedDisplayStatus = getDisplayGroupedStatus(itemInfo, recordInfo.getRecordIdentifier());
		String overiddenStatus = getOverriddenStatus(itemInfo, true);
		if (overiddenStatus != null && !overiddenStatus.equals("On Shelf") && !overiddenStatus.equals("Library Use Only") && !overiddenStatus.equals("Available Online")){
			available = false;
		}

		String itemLocation = itemInfo.getLocationCode();

		String originalUrl = itemInfo.geteContentUrl();
		for (Scope curScope : indexer.getScopes()) {
			//Check to see if the record is holdable for this scope
			Scope.InclusionResult result = curScope.isItemPartOfScope(profileType, itemLocation, "", itemInfo.getIType(), audiences, recordInfo.getPrimaryFormat(), false, false, false, record, originalUrl);
			if (result.isIncluded){
				ScopingInfo scopingInfo = itemInfo.addScope(curScope);
				scopingInfo.setAvailable(available);
				scopingInfo.setHoldable(false);
				scopingInfo.setHoldablePTypes("");
				scopingInfo.setBookable(false);
				scopingInfo.setBookablePTypes("");

				scopingInfo.setInLibraryUseOnly(determineLibraryUseOnly(itemInfo, curScope));

				scopingInfo.setStatus(displayStatus);
				scopingInfo.setGroupedStatus(groupedDisplayStatus);
				if (curScope.isLocationScope()) {
					scopingInfo.setLocallyOwned(curScope.isItemOwnedByScope(profileType, itemLocation, ""));
					if (curScope.getLibraryScope() != null) {
						scopingInfo.setLibraryOwned(curScope.getLibraryScope().isItemOwnedByScope(profileType, itemLocation, ""));
					}
				}
				if (curScope.isLibraryScope()) {
					scopingInfo.setLibraryOwned(curScope.isItemOwnedByScope(profileType, itemLocation, ""));
				}
				//Check to see if we need to do url rewriting
				if (!originalUrl.equals(result.localUrl)){
					scopingInfo.setLocalUrl(result.localUrl);
				}
			}
		}
	}

	@Override
	protected List<RecordInfo> loadUnsuppressedEContentItems(GroupedWorkSolr groupedWork, String identifier, Record record){
		List<RecordInfo> unsuppressedEcontentRecords = new ArrayList<>();
		//For arlington, eContent will always have no items on the bib record.
		List<DataField> items = MarcUtil.getDataFields(record, itemTag);
		if (items.size() > 0){
			return unsuppressedEcontentRecords;
		}else{
			//No items so we can continue on.
			//Check the mat type
			String matType = MarcUtil.getFirstFieldVal(record, "998d");
			//Get the bib location
			String bibLocation = null;
			Set<String> bibLocations = MarcUtil.getFieldList(record, "998a");
			for (String tmpBibLocation : bibLocations){
				if (tmpBibLocation.matches("[a-zA-Z]{1,5}")){
					bibLocation = tmpBibLocation;
					break;
				}else if (tmpBibLocation.matches("\\(\\d+\\)([a-zA-Z]{1,5})")){
					bibLocation = tmpBibLocation.replaceAll("\\(\\d+\\)", "");
					break;
				}
			}
			//Get the url
			String url = MarcUtil.getFirstFieldVal(record, "856u");

			if (url != null && !url.toLowerCase().contains("lib.overdrive.com")){
				//Get the econtent source
				String urlLower = url.toLowerCase();
				String econtentSource;
				String specifiedSource = MarcUtil.getFirstFieldVal(record, "856x");
				if (specifiedSource != null){
					econtentSource = specifiedSource;
				}else {
					String urlText = MarcUtil.getFirstFieldVal(record, "856z");
					if (urlText != null) {
						urlText = urlText.toLowerCase();
						if (urlText.contains("gale virtual reference library")) {
							econtentSource = "Gale Virtual Reference Library";
						} else if (urlText.contains("gale directory library")) {
							econtentSource = "Gale Directory Library";
						} else if (urlText.contains("hoopla")) {
							econtentSource = "Hoopla";
						} else if (urlText.contains("national geographic virtual library")) {
							econtentSource = "National Geographic Virtual Library";
						} else if ((urlText.contains("ebscohost") || urlLower.contains("netlibrary") || urlLower.contains("ebsco"))) {
							econtentSource = "EbscoHost";
						} else {
							econtentSource = "Premium Sites";
						}
					} else {
						econtentSource = "Premium Sites";
					}
				}

				ItemInfo itemInfo = new ItemInfo();
				itemInfo.setIsEContent(true);
				itemInfo.setLocationCode(bibLocation);
				itemInfo.seteContentProtectionType("external");
				itemInfo.setCallNumber("Online");
				itemInfo.seteContentSource(econtentSource);
				itemInfo.setShelfLocation(econtentSource);
				itemInfo.setIType("eCollection");
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

				unsuppressedEcontentRecords.add(relatedRecord);
			}
		}
		return unsuppressedEcontentRecords;
	}

	protected boolean isBibSuppressed(Record record) {
		DataField field998 = (DataField)record.getVariableField("998");
		if (field998 != null){
			Subfield suppressionSubfield = field998.getSubfield('e');
			if (suppressionSubfield != null){
				String bCode3 = suppressionSubfield.getData().toLowerCase().trim();
				if (bCode3.matches("^[xnopwhd]$")){
					logger.debug("Bib record is suppressed due to bcode3 " + bCode3);
					return true;
				}
			}
		}
		return false;
	}

	protected boolean isItemSuppressed(DataField curItem) {
		Subfield icode2Subfield = curItem.getSubfield(iCode2Subfield);
		if (icode2Subfield != null && useICode2Suppression) {
			String icode2 = icode2Subfield.getData().toLowerCase().trim();

			//Suppress icode2 codes
			if (icode2.matches("^(d|e|h|n|p|y|4|5|6)$")) {
				logger.debug("Item record is suppressed due to icode2 " + icode2);
				return true;
			}
		}
		return super.isItemSuppressed(curItem);
	}

	/**
	 * For Arlington do not load Bisac Subjects and load full stings with subfields for topics
	 */
	protected void loadSubjects(GroupedWorkSolr groupedWork, Record record){
		HashSet<String> validSubjects = new HashSet<>();
		getSubjectValues(MarcUtil.getDataFields(record, "600"), validSubjects);
		getSubjectValues(MarcUtil.getDataFields(record, "610"), validSubjects);
		getSubjectValues(MarcUtil.getDataFields(record, "611"), validSubjects);
		getSubjectValues(MarcUtil.getDataFields(record, "630"), validSubjects);
		getSubjectValues(MarcUtil.getDataFields(record, "650"), validSubjects);
		getSubjectValues(MarcUtil.getDataFields(record, "651"), validSubjects);
		getSubjectValues(MarcUtil.getDataFields(record, "690"), validSubjects);

		groupedWork.addSubjects(validSubjects);
		//Add lc subjects
		//groupedWork.addLCSubjects(getLCSubjects(record));
		//Add bisac subjects
		//groupedWork.addBisacSubjects(getBisacSubjects(record));
		//groupedWork.addGenre(getAllSubfields(record, "655abcvxyz", " -- "));
		//groupedWork.addGenreFacet(getAllSubfields(record, "600v:610v:611v:630v:648v:650v:651v:655av", " -- "));
		//groupedWork.addGeographic(getAllSubfields(record, "651avxyz", " -- "));
		//groupedWork.addGeographicFacet(getAllSubfields(record, "600z:610z:611z:630z:648z:650z:651a:651z:655z", " -- "));
		//groupedWork.addEra(getAllSubfields(record, "600d:610y:611y:630y:648a:648y:650y:651y:655y", " -- "));
	}

	private void getSubjectValues(List<DataField> subjectFields, HashSet<String> validSubjects) {
		for (DataField curSubject : subjectFields){
			boolean okToInclude = true;
			Subfield subfield2 = curSubject.getSubfield('2');
			if (subfield2 != null){
				if (subfield2.getData().equalsIgnoreCase("bisac") || subfield2.getData().equalsIgnoreCase("fast")){
					okToInclude = false;
				}
			}
			if (okToInclude){
				StringBuilder subjectValue = new StringBuilder();
				for (Subfield curSubfield : curSubject.getSubfields()){
					if (curSubfield.getCode() != '2' && curSubfield.getCode() != '0'){
						if (subjectValue.length() > 0){
							subjectValue.append(" -- ");
						}
						subjectValue.append(curSubfield.getData());
					}
				}
				validSubjects.add(subjectValue.toString());
			}
		}
	}

	protected boolean use099forBibLevelCallNumbers() {
		return false;
	}
}
