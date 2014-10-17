package org.vufind;

import au.com.bytecode.opencsv.CSVReader;
import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.sql.Connection;
import java.util.*;
import java.util.regex.Pattern;

/**
 * ILS Indexing with customizations specific to Marmot
 * VuFind-Plus
 * User: Mark Noble
 * Date: 2/21/14
 * Time: 3:00 PM
 */
public class MarmotRecordProcessor extends IlsRecordProcessor {
	private String marcExportPath;
	private static HashMap<String, ArrayList<SierraOrderInformation>> orderRecordsByBib = new HashMap<String, ArrayList<SierraOrderInformation>>();

	public MarmotRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, Logger logger) {
		super(indexer, vufindConn, configIni, logger);

		marcExportPath = configIni.get("Reindex", "marcPath");
		loadOrderRecords();
	}

	private void loadOrderRecords() {
		File activeOrdersFile = new File(marcExportPath + "/active_orders.csv");
		if (activeOrdersFile.exists()){
			try {
				CSVReader reader = new CSVReader(new FileReader(activeOrdersFile));
				//Skip the header
				reader.readNext();
				String[] curLine = reader.readNext();
				while (curLine != null){
					SierraOrderInformation orderInformation = new SierraOrderInformation();
					orderInformation.setBibRecordNumber(".b" + curLine[0] + getCheckDigit(curLine[0]));
					orderInformation.setOrderNumber(".o" + curLine[1] + getCheckDigit(curLine[1]));
					orderInformation.setAccountingUnit(Long.parseLong(curLine[2]));
					orderInformation.setStatusCode(curLine[3]);
					orderInformation.setCopies(Integer.parseInt(curLine[4]));
					orderInformation.setLocationCode(curLine[5]);
					ArrayList<SierraOrderInformation> orderRecordsForBib = orderRecordsByBib.get(orderInformation.getBibRecordNumber());
					if (orderRecordsForBib == null){
						orderRecordsForBib = new ArrayList<SierraOrderInformation>();
						orderRecordsByBib.put(orderInformation.getBibRecordNumber(), orderRecordsForBib);
					}
					orderRecordsForBib.add(orderInformation);
					curLine = reader.readNext();
				}
				reader.close();
			}catch (IOException e){
				logger.error("Error loading order records", e);
			}
		}
	}

	protected List<OnOrderItem> getOnOrderItems(String identifier, Record record){
		ArrayList<OnOrderItem> onOrderItems = new ArrayList<OnOrderItem>();

		//Check to see if we have order records for the bib.  If so, add ownership for those records.
		if (orderRecordsByBib.containsKey(identifier)){
			ArrayList<SierraOrderInformation> orderInformationForBib = orderRecordsByBib.get(identifier);
			//We have a match, determine which scopes to add the record to
			for (SierraOrderInformation orderInformation : orderInformationForBib) {
				OnOrderItem orderItem = new OnOrderItem();
				orderItem.setOrderNumber(orderInformation.getOrderNumber());
				orderItem.setBibNumber(orderInformation.getBibRecordNumber());
				orderItem.setStatus(orderInformation.getStatusCode());
				orderItem.setLocationCode(orderInformation.getLocationCode());
				orderItem.setCopies(orderInformation.getCopies());
				//Get the location code/codes for the order
				if (!orderInformation.getLocationCode().equals("multi")) {
					for (Scope curScope : indexer.getScopes()) {
						//Part of scope if the location code is included directly
						//or if the scope is not limited to only including library/location codes.
						if ((!curScope.isIncludeItemsOwnedByTheLibraryOnly() && !curScope.isIncludeItemsOwnedByTheLocationOnly()) ||
								curScope.isLocationCodeIncludedDirectly(orderInformation.getLocationCode())) {
							orderItem.addRelatedScope(curScope);
						}
					}
					onOrderItems.add(orderItem);
				}
			}
		}

		return onOrderItems;
	}

	protected void loadAdditionalOwnershipInformation(GroupedWorkSolr groupedWork, PrintIlsItem printItem){
		String locationCode = printItem.getLocation();
		if (locationCode.length() > 0 && !locationCode.equalsIgnoreCase("none")){
			groupedWork.addCollectionGroup(indexer.translateValue("collection_group", locationCode));
			if (additionalCollections != null){
				for (String additionalCollection : additionalCollections){
					groupedWork.addAdditionalCollection(additionalCollection, indexer.translateValue("collection_" + additionalCollection, locationCode));
				}
			}
			String translatedDetailedLocation = indexer.translateValue("detailed_location", locationCode);
			for (ScopedWorkDetails curScope: groupedWork.getScopedWorkDetails().values()){
				if (curScope.getScope().isLocationCodeIncludedDirectly(locationCode)) {
					curScope.addDetailedLocation(translatedDetailedLocation);
				}
			}
			/*for (LocalizedWorkDetails localizedWorkDetails : groupedWork.getLocalizedWorkDetails().values()){
				if (localizedWorkDetails.getLocalizationInfo().isLocationCodeIncluded(locationCode)){
					localizedWorkDetails.addDetailedLocation(translatedDetailedLocation);
				}
			}*/
		}
	}

	protected void loadLocalCallNumbers(GroupedWorkSolr groupedWork, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems) {
		for (PrintIlsItem curItem : printItems){
			String locationCode = curItem.getLocation();
			if (locationCode != null){
				String callNumberPrestamp = curItem.getCallNumberPreStamp() == null ? "" : curItem.getCallNumberPreStamp();
				String callNumber = curItem.getCallNumber() == null ? "" : curItem.getCallNumber();
				String callNumberCutter = curItem.getCallNumberCutter() == null ? "" : curItem.getCallNumberCutter();
				String fullCallNumber = callNumberPrestamp + callNumber + callNumberCutter;
				String sortableCallNumber = callNumber + callNumberCutter;
				if (fullCallNumber.length() > 0){
					ArrayList<String> subdomainsForLocation = getLibrarySubdomainsForLocationCode(locationCode);
					ArrayList<String> relatedLocationCodesForLocation = getRelatedLocationCodesForLocationCode(locationCode);
					groupedWork.addLocalCallNumber(fullCallNumber, subdomainsForLocation, relatedLocationCodesForLocation);
					groupedWork.addCallNumberSort(sortableCallNumber, subdomainsForLocation, relatedLocationCodesForLocation);
				}
			}
		}
	}

	protected List<PrintIlsItem> getUnsuppressedPrintItems(String identifier, Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<PrintIlsItem> unsuppressedItemRecords = new ArrayList<PrintIlsItem>();
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				if (useEContentSubfield){
					if (itemField.getSubfield(eContentSubfieldIndicator) != null){
						String eContentData = itemField.getSubfield(eContentSubfieldIndicator).getData();
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
					PrintIlsItem printIlsRecord = getPrintIlsRecord(itemField);
					if (printIlsRecord != null) {
						unsuppressedItemRecords.add(printIlsRecord);
					}
				}
			}
		}
		return unsuppressedItemRecords;
	}

	@Override
	protected boolean isItemAvailable(PrintIlsItem ilsRecord) {
		boolean available = false;
		String status = ilsRecord.getStatus();
		String dueDate = ilsRecord.getDateDue() == null ? "" : ilsRecord.getDateDue();
		String availableStatus = "-dowju";
		if (status.length() > 0 && availableStatus.indexOf(status.charAt(0)) >= 0) {
			if (dueDate.length() == 0) {
				available = true;
			}
		}
		return available;
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

	protected List<EContentIlsItem> getUnsuppressedEContentItems(String identifier, Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<EContentIlsItem> unsuppressedEcontentRecords = new ArrayList<EContentIlsItem>();
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				if (useEContentSubfield){
					if (itemField.getSubfield(eContentSubfieldIndicator) != null){
						String eContentData = itemField.getSubfield(eContentSubfieldIndicator).getData();
						if (eContentData.indexOf(':') >= 0){
							isEContent = true;
							String[] eContentFields = eContentData.split(":");
							String sourceType = eContentFields[0].toLowerCase().trim();
							if (sourceType.equals("overdrive")){
								isOverDrive = true;
							}
						}else{
							if (itemField.getSubfield(eContentSubfieldIndicator).getData().trim().equalsIgnoreCase("overdrive")){
								isOverDrive = true;
							}
						}
					}
				}
				if (!isOverDrive && isEContent){
					unsuppressedEcontentRecords.add(getEContentIlsRecord(identifier, itemField));
				}
			}
		}
		return unsuppressedEcontentRecords;
	}

	/**
	 * Do item based determination of econtent sources, and protection types.
	 * For Marmot also load availability and ownership information for eContent since it is so similar.
	 *
	 * @param groupedWork The Work to load sources and protection types for
	 * @param itemRecords The item records related to the current Marc Record being indexed
	 */
	protected void loadEContentSourcesAndProtectionTypes(GroupedWorkSolr groupedWork, List<EContentIlsItem> itemRecords) {
		for (EContentIlsItem curItem : itemRecords){
			//Check subfield w to get the source
			String locationCode = curItem.getLocation();
			HashSet<String> sources = new HashSet<String>();
			HashSet<String> protectionTypes = new HashSet<String>();

			String eContentSource = curItem.getSource();
			String protectionType = curItem.getProtectionType();

			sources.add(eContentSource);

			boolean available = false;
			if (protectionType.equals("external")){
				protectionTypes.add("Externally Validated");
				available = true;
			}else if (protectionType.equals("public domain") || protectionType.equals("free")){
				protectionTypes.add("Public Domain");
				available = true;
			}else if (protectionType.equals("acs") || protectionType.equals("drm")){
				protectionTypes.add("Limited Access");
				//TODO: Determine availability based on if it is checked out in the database
				available = true;
			}

			boolean shareWithAll = false;
			boolean shareWithLibrary = false;
			String sharing = curItem.getSharing();
			if (sharing.equalsIgnoreCase("shared")){
				shareWithAll = true;
			}else if (sharing.equalsIgnoreCase("library")){
				shareWithLibrary = true;
			}

			if (locationCode != null && locationCode.equalsIgnoreCase("mdl")){
				//Share with everyone
				shareWithAll = true;
			}
			HashSet<String> owningLibraries = new HashSet<String>();
			HashSet<String> availableLibraries = new HashSet<String>();
			HashSet<String> owningSubdomainsAndLocations = new HashSet<String>();
			HashSet<String> availableSubdomainsAndLocations = new HashSet<String>();
			if (shareWithAll){
				//When we share with everyone, we only really want to share with people that want it
				groupedWork.addEContentSources(sources, curItem.getValidSubdomains() , curItem.getValidLocations());
				groupedWork.addEContentProtectionTypes(protectionTypes, curItem.getValidSubdomains() , curItem.getValidLocations());
				groupedWork.addCompatiblePTypes(curItem.getCompatiblePTypes());
				//owningLibraries.add("Shared Digital Collection");
				owningLibraries.addAll(curItem.getValidLibraryFacets());
				owningSubdomainsAndLocations.addAll(indexer.getSubdomainMap().values());
				owningSubdomainsAndLocations.addAll(indexer.getLocationMap().keySet());
				if (available){
					availableLibraries.addAll(indexer.getLibraryFacetMap().values());
					availableSubdomainsAndLocations.addAll(indexer.getSubdomainMap().values());
					availableSubdomainsAndLocations.addAll(indexer.getLocationMap().keySet());
				}
			}else if (shareWithLibrary){
				ArrayList<String> validSubdomains = getLibrarySubdomainsForLocationCode(locationCode);
				ArrayList<String> validLocationCodes = getRelatedLocationCodesForLocationCode(locationCode);
				groupedWork.addEContentSources(sources, validSubdomains, validLocationCodes);
				groupedWork.addEContentProtectionTypes(protectionTypes, validSubdomains, validLocationCodes);
				for (String curLocation : pTypesByLibrary.keySet()){
					Pattern libraryCodePattern = Pattern.compile(curLocation);
					if (locationCode != null && libraryCodePattern.matcher(locationCode).lookingAt()){
						groupedWork.addCompatiblePTypes(pTypesByLibrary.get(curLocation));
					}
				}
				owningLibraries.addAll(getLibraryOnlineFacetsForLocationCode(locationCode));
				if (available){
					availableLibraries.addAll(getLibraryOnlineFacetsForLocationCode(locationCode));
					availableSubdomainsAndLocations.addAll(validSubdomains);
					availableSubdomainsAndLocations.addAll(validLocationCodes);
				}
			}else{
				//Share with just the individual location
				groupedWork.addEContentSources(sources, new HashSet<String>(), getRelatedLocationCodesForLocationCode(locationCode));
				groupedWork.addEContentProtectionTypes(protectionTypes, new HashSet<String>(), getRelatedLocationCodesForLocationCode(locationCode));
				//TODO: Add correct owning and available locations
				for (String curLocation : pTypesByLibrary.keySet()){
					if (locationCode != null && locationCode.startsWith(curLocation)){
						groupedWork.addCompatiblePTypes(pTypesByLibrary.get(curLocation));
					}
				}
			}
			groupedWork.addOwningLibraries(owningLibraries);
			groupedWork.addOwningLocationCodesAndSubdomains(owningSubdomainsAndLocations);
			groupedWork.addAvailableLocations(availableLibraries, availableSubdomainsAndLocations);
		}//Has subfield w
	}

	protected void loadUsability(GroupedWorkSolr groupedWork, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems) {
		super.loadUsability(groupedWork, printItems, econtentItems);

		for (EContentIlsItem itemField : econtentItems){
			String locationCode = itemField.getLocation();
			boolean shareWithAll = false;
			boolean shareWithLibrary = false;
			boolean shareWithSome = false;
			String sharing = itemField.getSharing();
			if (sharing.equals("shared")){
				if (locationCode.startsWith("mdl")){
					shareWithSome = true;
				}else{
					shareWithAll = true;
				}
			}else if (sharing.equalsIgnoreCase("library")){
				shareWithLibrary = true;
			}

			if (shareWithAll){
				groupedWork.addCompatiblePTypes(allPTypes);
				break;
			}else if (shareWithLibrary) {
				if (locationCode == null) {
					logger.error("Location code was null for item, skipping to next");
				} else {
					for (String curLocation : pTypesByLibrary.keySet()) {
						Pattern libraryCodePattern = Pattern.compile(curLocation);
						if (libraryCodePattern.matcher(locationCode).lookingAt()){
							groupedWork.addCompatiblePTypes(pTypesByLibrary.get(curLocation));
						}
					}
				}
			}else if (shareWithSome){
				if (pTypesForSpecialLocationCodes.containsKey(locationCode)) {
					groupedWork.addCompatiblePTypes(pTypesForSpecialLocationCodes.get(locationCode));
				}
			} else{
				logger.warn("Could not determine usability, was not shared with library or everyone");
			}
		}
	}



	protected void loadEContentFormatInformation(IlsRecord econtentRecord, EContentIlsItem econtentItem) {
		String protectionType = econtentItem.getProtectionType();
		if (protectionType.equals("acs") || protectionType.equals("drm") || protectionType.equals("public domain") || protectionType.equals("free")){
			String filename = econtentItem.getFilename();
			if (filename == null) {
				//Did not get a filename, use the iType as a placeholder
				String iType = econtentItem.getiType();
				if (iType != null){
					String translatedFormat = indexer.translateValue("econtent_itype_format", iType);
					String translatedFormatCategory = indexer.translateValue("econtent_itype_format_category", iType);
					String translatedFormatBoost = indexer.translateValue("econtent_itype_format_boost", iType);
					econtentRecord.setFormatInformation(translatedFormat, translatedFormatCategory, translatedFormatBoost);
				}else{
					logger.warn("Did not get a filename or itype for " + econtentRecord.getRecordId());
				}
			}else if (filename.indexOf('.') > 0) {
				String fileExtension = filename.substring(filename.lastIndexOf('.') + 1);
				String translatedFormat = indexer.translateValue("format", fileExtension);
				String translatedFormatCategory = indexer.translateValue("format_category", fileExtension);
				String translatedFormatBoost = indexer.translateValue("format_boost", fileExtension);
				econtentRecord.setFormatInformation(translatedFormat, translatedFormatCategory, translatedFormatBoost);
			} else {
				//For now we know these are folders of MP3 files
				//TODO: Probably should actually open the folder to make sure that it contains MP3 files
				String translatedFormat = indexer.translateValue("format", "mp3");
				String translatedFormatCategory = indexer.translateValue("format_category", "mp3");
				String translatedFormatBoost = indexer.translateValue("format_boost", "mp3");
				econtentRecord.setFormatInformation(translatedFormat, translatedFormatCategory, translatedFormatBoost);
			}
		}else if (protectionType.equals("external")){
			String iType = econtentItem.getiType();
			if (iType != null){
				String translatedFormat = indexer.translateValue("econtent_itype_format", iType);
				String translatedFormatCategory = indexer.translateValue("econtent_itype_format_category", iType);
				String translatedFormatBoost = indexer.translateValue("econtent_itype_format_boost", iType);
				econtentRecord.setFormatInformation(translatedFormat, translatedFormatCategory, translatedFormatBoost);
			}else{
				logger.warn("Did not get a iType for external eContent " + econtentRecord.getRecordId());
			}
		}else{
			logger.warn("Unknown protection type " + protectionType);
		}
	}

	/**
	 * Calculates a check digit for a III identifier
	 * @param basedId String the base id without checksum
	 * @return String the check digit
	 */
	public static String getCheckDigit(String basedId) {
		if (basedId.length() != 7){
			return "a";
		}else{
			int sumOfDigits = 0;
			for (int i = 0; i < 7; i++){
				sumOfDigits += (8 - i) * Integer.parseInt(basedId.substring(i, i+1));
			}
			int modValue = sumOfDigits % 11;
			if (modValue == 10){
				return "x";
			}else{
				return Integer.toString(modValue);
			}
		}

	}
}
