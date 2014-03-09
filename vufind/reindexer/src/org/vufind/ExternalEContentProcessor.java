package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 2/6/14
 * Time: 9:11 AM
 */
public class ExternalEContentProcessor extends IlsRecordProcessor {

	public ExternalEContentProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Connection econtentConn, Ini configIni, Logger logger) {
		super(indexer, vufindConn, configIni, logger);
	}

	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		super.updateGroupedWorkSolrDataBasedOnMarc(groupedWork, record, identifier);

		List<DataField> eContentItems = getUnsuppressedEContentItems(record);
		loadEContentSourcesAndProtectionTypes(groupedWork, eContentItems);
	}

	protected void loadRecordType(GroupedWorkSolr groupedWork, Record record) {
		String recordId = getFirstFieldVal(record, "907a");
		groupedWork.addRelatedRecord("external_econtent:" + recordId);
	}

	private void loadEContentSourcesAndProtectionTypes(GroupedWorkSolr groupedWork, List<DataField> itemRecords) {
		for (DataField curItem : itemRecords){
			//Check subfield w to get the source
			if (curItem.getSubfield('w') != null){
				String locationCode = curItem.getSubfield(locationSubfieldIndicator) == null ? null : curItem.getSubfield(locationSubfieldIndicator).getData();
				HashSet<String> sources = new HashSet<String>();
				HashSet<String> protectionTypes = new HashSet<String>();

				String subfieldW = curItem.getSubfield('w').getData();
				String[] econtentData = subfieldW.split("\\s?:\\s?");
				String eContentSource = econtentData[0].trim();
				String protectionType = econtentData[1].toLowerCase().trim();

				if (protectionType.equals("external")){
					sources.add(eContentSource);
					protectionTypes.add("Externally Validated");

					boolean shareWithAll = false;
					boolean shareWithLibrary = false;
					if (econtentData.length >= 3){
						String sharing = econtentData[2].trim();
						if (sharing.equalsIgnoreCase("shared")){
							shareWithAll = true;
						}else if (sharing.equalsIgnoreCase("library")){
							shareWithLibrary = true;
						}
					}else{
						shareWithLibrary = true;
					}

					if (locationCode != null && locationCode.equalsIgnoreCase("mdl")){
						//Share with everyone
						shareWithAll = true;
					}
					if (shareWithAll){
						groupedWork.addEContentSources(sources, subdomainMap.values() , locationMap.values());
						groupedWork.addEContentProtectionTypes(protectionTypes, subdomainMap.values() , locationMap.values());
					}else if (shareWithLibrary){
						groupedWork.addEContentSources(sources, getLibrarySubdomainsForLocationCode(locationCode), getRelatedLocationCodesForLocationCode(locationCode));
						groupedWork.addEContentProtectionTypes(protectionTypes, getLibrarySubdomainsForLocationCode(locationCode), getRelatedLocationCodesForLocationCode(locationCode));
					}else{
						groupedWork.addEContentSources(sources, new HashSet<String>(), getRelatedLocationCodesForLocationCode(locationCode));
						groupedWork.addEContentProtectionTypes(protectionTypes, new HashSet<String>(), getRelatedLocationCodesForLocationCode(locationCode));
					}
				}

			}
		}
	}

	protected void loadUsability(GroupedWorkSolr groupedWork, List<DataField> unsuppressedItemRecords) {
		//Load a list of ptypes that can use this record based on sharing in the eContent subfield
		for (DataField curItem : unsuppressedItemRecords){
			//Check subfield w to get the source
			if (curItem.getSubfield('w') != null){
				String subfieldW = curItem.getSubfield('w').getData();
				String[] econtentData = subfieldW.split("\\s?:\\s?");
				String protectionType = econtentData[1].toLowerCase().trim();

				if (protectionType.equals("external")){
					Subfield locationSubfield = curItem.getSubfield('d');
					String sharing;
					if (locationSubfield.getData().equals("mdl")){
						sharing = "shared";
					}else{
						sharing = "library";
					}
					if (econtentData.length >= 3){
						sharing = econtentData[2].trim().toLowerCase();
					}
					if (sharing.equalsIgnoreCase("shared")){
						groupedWork.addCompatiblePType("all");
					}else if (sharing.equalsIgnoreCase("library")){
						//TODO: Add all ptypes for this library system
					}else{
						//TODO: Add all ptypes for this location
					}
				}
			}
		}
	}

	@Override
	public Set<String> loadFormats(Record record, boolean returnFirst) {
		//This isn't used for external eContent
		return null;
	}

	protected void loadFormatDetails(GroupedWorkSolr groupedWork, Record record) {
		List<DataField> itemFields = getUnsuppressedEContentItems(record);

		HashSet<String> formatCategories = new HashSet<String>();
		HashSet<String> eContentDevices = new HashSet<String>();
		Long formatBoost = 1L;
		for (DataField itemField : itemFields){
			String iType = itemField.getSubfield(iTypeSubfield) == null ? null : itemField.getSubfield(iTypeSubfield).getData();

			if (iType != null){
				String locationCode = itemField.getSubfield(locationSubfieldIndicator) == null ? null : itemField.getSubfield(locationSubfieldIndicator).getData();

				HashSet<String> translatedFormats = new HashSet<String>();
				String translatedFormat = indexer.translateValue("econtent_itype_format", iType);
				translatedFormats.add(translatedFormat);

				//Get sharing for the record
				boolean shareWithAll = false;
				boolean shareWithLibrary = false;
				if (itemField.getSubfield('w') != null){
					String subfieldW = itemField.getSubfield('w').getData();
					String[] econtentData = subfieldW.split("\\s?:\\s?");
					if (econtentData.length >= 3){
						String sharing = econtentData[2].trim();
						if (sharing.equalsIgnoreCase("shared")){
							shareWithAll = true;
						}else if (sharing.equalsIgnoreCase("library")){
							shareWithLibrary = true;
						}
					}else{
						shareWithLibrary = true;
					}
				}

				if (locationCode != null){
					if (locationCode.equalsIgnoreCase("mdl")){
						//Share with everyone
						shareWithAll = true;
					}
					if (shareWithAll){
						groupedWork.addFormats(translatedFormats, subdomainMap.values() , locationMap.values());
					}else if (shareWithLibrary){
						groupedWork.addFormats(translatedFormats, getLibrarySubdomainsForLocationCode(locationCode), getRelatedLocationCodesForLocationCode(locationCode));
					}else{
						groupedWork.addFormats(translatedFormats, new HashSet<String>(), getRelatedLocationCodesForLocationCode(locationCode));
					}

				}

				formatCategories.add(indexer.translateValue("econtent_itype_format_category", iType));
				String formatBoostStr = indexer.translateValue("econtent_itype_format_boost", iType);
				try{
					Long curFormatBoost = Long.parseLong(formatBoostStr);
					if (curFormatBoost > formatBoost){
						formatBoost = curFormatBoost;
					}
				}catch (NumberFormatException e){
					logger.warn("Could not parse format_boost " + formatBoostStr);
				}
				String deviceString = indexer.translateValue("device_compatibility", translatedFormat.replace(' ', '_'));
				String[] devices = deviceString.split("\\|");
				for (String device : devices){
					eContentDevices.add(device.trim());
				}

			}
		}
		//Format for external eContent is only valid for the specific location the record belongs to
		groupedWork.addFormatCategories(formatCategories);
		groupedWork.setFormatBoost(formatBoost);
		groupedWork.addEContentDevices(eContentDevices);
	}

	protected List<DataField> getUnsuppressedItems(Record record) {
		return getUnsuppressedEContentItems(record);
	}
}
