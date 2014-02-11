package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.util.HashSet;
import java.util.List;

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
		HashSet<String> sources = new HashSet<String>();
		HashSet<String> protectionTypes = new HashSet<String>();
		for (DataField curItem : itemRecords){
			//Check subfield w to get the source
			if (curItem.getSubfield('w') != null){
				String subfieldW = curItem.getSubfield('w').getData();
				String[] econtentData = subfieldW.split("\\s?:\\s?");
				String eContentSource = econtentData[0].trim();
				String protectionType = econtentData[1].toLowerCase().trim();
				if (protectionType.equals("external")){
					sources.add(eContentSource);
					protectionTypes.add("Externally Validated");
				}
			}
		}
		groupedWork.addEContentSources(sources);
		groupedWork.addEContentProtectionTypes(protectionTypes);
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
					if (sharing.equals("shared")){
						groupedWork.addCompatiblePType("all");
					}else{
						//Add all ptypes for this library system (further restriction is done by location)
					}
				}
			}
		}
	}

	protected List<DataField> getUnsuppressedItems(Record record) {
		return getUnsuppressedEContentItems(record);
	}
}
