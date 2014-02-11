package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;

import java.sql.Connection;
import java.util.HashSet;
import java.util.List;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 2/6/14
 * Time: 9:12 AM
 */
public class RestrictedEContentProcessor extends IlsRecordProcessor {
	public RestrictedEContentProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Connection econtentConn, Ini configIni, Logger logger) {
		super(indexer, vufindConn, configIni, logger);
	}

	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		super.updateGroupedWorkSolrDataBasedOnMarc(groupedWork, record, identifier);

		List<DataField> eContentItems = getUnsuppressedEContentItems(record);
		loadEContentSourcesAndProtectionTypes(groupedWork, eContentItems);
	}

	protected void loadRecordType(GroupedWorkSolr groupedWork, Record record) {
		String recordId = getFirstFieldVal(record, "907a");
		groupedWork.addRelatedRecord("restricted_econtent:" + recordId);
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
				if (protectionType.equals("acs") || protectionType.equals("drm")){
					sources.add(eContentSource);
					protectionTypes.add("Limited Access");
				}
			}
		}
		groupedWork.addEContentSources(sources);
		groupedWork.addEContentProtectionTypes(protectionTypes);
	}

	protected List<DataField> getUnsuppressedItems(Record record) {
		return getUnsuppressedEContentItems(record);
	}
}
