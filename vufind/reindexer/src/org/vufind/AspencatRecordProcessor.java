package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.VariableField;

import java.sql.Connection;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

/**
 * ILS Indexing with customizations specific to Aspencat
 * VuFind-Plus
 * User: Mark Noble
 * Date: 2/21/14
 * Time: 3:00 PM
 */
public class AspencatRecordProcessor extends IlsRecordProcessor {
	public AspencatRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, Logger logger) {
		super(indexer, vufindConn, configIni, logger);
	}

	@Override
	protected boolean isItemAvailable(PrintIlsItem ilsRecord) {
		if (ilsRecord.getStatus().equals("On Shelf") ||
				ilsRecord.getStatus().equals("Library Use Only")) {
			return true;
		}else{
			return false;
		}
	}

	@Override
	public void loadPrintFormatInformation(IlsRecord ilsRecord, Record record) {
		Set<String> printFormatsRaw = getFieldList(record, itemTag + collectionSubfield);
		Set<String> printFormats = new HashSet<String>();
		for (String curFormat : printFormatsRaw){
			printFormats.add(curFormat.toLowerCase());
		}

		HashSet<String> translatedFormats = indexer.translateCollection("format", printFormats);

		if (translatedFormats.size() == 0){
			//We didn't get any formats from the collections, get formats from the base method (007, 008, etc).
			super.loadPrintFormatInformation(ilsRecord, record);
		} else{
			HashSet<String> translatedFormatCategories = indexer.translateCollection("format_category", printFormats);
			ilsRecord.addFormats(translatedFormats);
			ilsRecord.addFormatCategories(translatedFormatCategories);
			Long formatBoost = 0L;
			HashSet<String> formatBoosts = indexer.translateCollection("format_boost", printFormats);
			for (String tmpFormatBoost : formatBoosts){
				if (Util.isNumeric(tmpFormatBoost)) {
					Long tmpFormatBoostLong = Long.parseLong(tmpFormatBoost);
					if (tmpFormatBoostLong > formatBoost) {
						formatBoost = tmpFormatBoostLong;
					}
				}
			}
			ilsRecord.setFormatBoost(formatBoost);
		}
	}

	private HashSet<String> additionalStatuses = new HashSet<String>();
	protected String getItemStatus(DataField itemField){
		//Determining status for Koha relies on a number of different fields
		String status = getStatusFromSubfield(itemField, '0', "Withdrawn");
		if (status != null) return status;

		status = getStatusFromSubfield(itemField, '1', "Lost");
		if (status != null) return status;

		status = getStatusFromSubfield(itemField, '4', "Damaged");
		if (status != null) return status;

		status = getStatusFromSubfield(itemField, 'q', "Checked Out");
		if (status != null) return status;

		status = getStatusFromSubfield(itemField, '7', "Library Use Only");
		if (status != null) return status;

		status = getStatusFromSubfield(itemField, 'k', null);
		if (status != null) return status;

		return "On Shelf";
	}

	private String getStatusFromSubfield(DataField itemField, char subfield, String defaultStatus) {
		if (itemField.getSubfield(subfield) != null){
			String fieldData = itemField.getSubfield(subfield).getData();
			if (!fieldData.equals("0")) {
				if (fieldData.equals("1")) {
					return defaultStatus;
				}else{
					if (subfield == 'q'){
						if (fieldData.matches("\\d{4}-\\d{2}-\\d{2}")){
							return "Checked Out";
						}
					}else if (subfield == '1'){
						if (fieldData.equals("lost")){
							return "Lost";
						}else if (fieldData.equals("missing")){
							return "Missing";
						}
					}else if (subfield == 'k') {
						if (fieldData.equals("CATALOGED") || fieldData.equals("READY")) {
							return null;
						}else if (fieldData.equals("BINDERY")){
							return "Bindery";
						}else if (fieldData.equals("IN REPAIRS")){
							return "Repair";
						}
					}
					String status = "|" + subfield + "-" + fieldData;
					if (!additionalStatuses.contains(status)){
						logger.warn("Found new status " + status);
						additionalStatuses.add(status);
					}
				}
			}
		}
		return null;
	}

	protected List<PrintIlsItem> getUnsuppressedPrintItems(String identifier, Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<PrintIlsItem> unsuppressedItemRecords = new ArrayList<PrintIlsItem>();
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				if (itemField.getSubfield(collectionSubfield) != null){
					String collection = itemField.getSubfield(collectionSubfield).getData().toLowerCase();
					if (collection.equals("ebook") || collection.equals("eaudio") || collection.equals("online")){
						isEContent = true;
					}
				}
				if (!isEContent){
					PrintIlsItem printIlsRecord = getPrintIlsItem(record, itemField);
					if (printIlsRecord != null) {
						unsuppressedItemRecords.add(printIlsRecord);
					}
				}
			}
		}
		return unsuppressedItemRecords;
	}

	protected List<EContentIlsItem> getUnsuppressedEContentItems(String identifier, Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<EContentIlsItem> unsuppressedEcontentRecords = new ArrayList<EContentIlsItem>();
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				boolean isHoopla = false;
				if (itemField.getSubfield(collectionSubfield) != null){
					String collection = itemField.getSubfield(collectionSubfield).getData().toLowerCase();
					if (collection.equals("ebook") || collection.equals("eaudio") || collection.equals("online")){
						isEContent = true;
						String sourceType = getSourceType(record, itemField);
						if (sourceType != null){
							sourceType = sourceType.toLowerCase().trim();
							if (sourceType.contains("overdrive")) {
								isOverDrive = true;
							} else if (sourceType.contains("hoopla")) {
								isHoopla = true;
							} else {
								logger.debug("Found eContent Source " + sourceType);
							}
						}else {
							//Need to figure out how to load a source
							logger.warn("Did not find an econtent source for " + identifier);
						}
					}
				}
				if (!isOverDrive && !isHoopla && isEContent){
					unsuppressedEcontentRecords.add(getEContentIlsRecord(record, identifier, itemField));
				}
			}
		}
		return unsuppressedEcontentRecords;
	}

	protected String getEContentSharing(EContentIlsItem ilsEContentItem, DataField itemField) {
		if (ilsEContentItem.getLocation().equals("ONLINE")) {
			return "shared";
		}else{
			return "library";
		}
	}

	protected void loadEContentFormatInformation(IlsRecord econtentRecord, EContentIlsItem econtentItem) {
		if (econtentItem.getCollection() != null) {
			String collection = econtentItem.getCollection().toLowerCase();
			String translatedFormat = indexer.translateValue("format", collection);
			String translatedFormatCategory = indexer.translateValue("format_category", collection);
			String translatedFormatBoost = indexer.translateValue("format_boost", collection);
			econtentRecord.setFormatInformation(translatedFormat, translatedFormatCategory, translatedFormatBoost);
		}
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
			}

			boolean shareWithAll = false;
			String sharing = curItem.getSharing();
			if (sharing.equalsIgnoreCase("shared")){
				shareWithAll = true;
			}

			if (locationCode != null && locationCode.equalsIgnoreCase("online")){
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
				//Do not set compatible ptypes for eContent since they are just determined by owning library/location
				//groupedWork.addCompatiblePTypes(curItem.getCompatiblePTypes());
				//owningLibraries.add("Shared Digital Collection");
				owningLibraries.addAll(curItem.getValidLibraryFacets());
				owningSubdomainsAndLocations.addAll(indexer.getSubdomainMap().values());
				owningSubdomainsAndLocations.addAll(indexer.getLocationMap().keySet());
				if (available){
					availableLibraries.addAll(indexer.getLibraryFacetMap().values());
					availableSubdomainsAndLocations.addAll(indexer.getSubdomainMap().values());
					availableSubdomainsAndLocations.addAll(indexer.getLocationMap().keySet());
				}
			}else{
				ArrayList<String> validSubdomains = getLibrarySubdomainsForLocationCode(locationCode);
				ArrayList<String> validLocationCodes = getRelatedLocationCodesForLocationCode(locationCode);
				groupedWork.addEContentSources(sources, validSubdomains, validLocationCodes);
				groupedWork.addEContentProtectionTypes(protectionTypes, validSubdomains, validLocationCodes);
				owningLibraries.addAll(getLibraryOnlineFacetsForLocationCode(locationCode));
				if (available){
					availableLibraries.addAll(getLibraryOnlineFacetsForLocationCode(locationCode));
					availableSubdomainsAndLocations.addAll(validSubdomains);
					availableSubdomainsAndLocations.addAll(validLocationCodes);
				}
			}
			groupedWork.addOwningLibraries(owningLibraries);
			groupedWork.addOwningLocationCodesAndSubdomains(owningSubdomainsAndLocations);
			groupedWork.addAvailableLocations(availableLibraries, availableSubdomainsAndLocations);
		}//Has subfield w
	}

	protected String getSourceType(Record record, DataField itemField) {
		//Try to figure out the source
		//Try |e
		String sourceType = null;
		if (itemField.getSubfield('e') != null){
			sourceType = itemField.getSubfield('e').getData();
		}else{
			//Try 949a
			DataField field949 = (DataField)record.getVariableField("949");
			if (field949 != null && field949.getSubfield('a') != null){
				sourceType = field949.getSubfield('a').getData();
			}else{
				DataField field037 = (DataField)record.getVariableField("037");
				if (field037 != null && field037.getSubfield('b') != null){
					sourceType = field037.getSubfield('b').getData();
				}else{
					List<VariableField> urlFields = record.getVariableFields("856");
					for (VariableField urlField : urlFields){
						DataField urlDataField = (DataField)urlField;
						if (urlDataField.getSubfield('3') != null) {
							if (urlDataField.getIndicator1() == '4' || urlDataField.getIndicator1() == ' ') {
								if (urlDataField.getIndicator2() == ' ' || urlDataField.getIndicator2() == '0' || urlDataField.getIndicator2() == '1') {
									sourceType = urlDataField.getSubfield('3').getData().trim();
									break;
								}
							}
						}
					}
				}
			}
		}
		return sourceType;
	}

	protected boolean isItemSuppressed(DataField curItem) {
		boolean suppressed = false;
		if (curItem.getSubfield('i') != null) {
			suppressed = curItem.getSubfield('i').getData().equals("1");
		}
		if (!suppressed && curItem.getSubfield(collectionSubfield) != null){
			suppressed = curItem.getSubfield(collectionSubfield).getData().equalsIgnoreCase("ill");
		}
		return suppressed;
	}
}
