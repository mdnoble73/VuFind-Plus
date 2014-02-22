package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;

import java.sql.Connection;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 2/6/14
 * Time: 9:13 AM
 */
public class PublicDomainEContentProcessor extends IlsRecordProcessor {
	public PublicDomainEContentProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Connection econtentConn, Ini configIni, Logger logger) {
		super(indexer, vufindConn, configIni, logger);
	}

	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		super.updateGroupedWorkSolrDataBasedOnMarc(groupedWork, record, identifier);

		List<DataField> eContentItems = getUnsuppressedEContentItems(record);
		loadEContentSourcesAndProtectionTypes(groupedWork, eContentItems);
	}

	protected void loadRecordType(GroupedWorkSolr groupedWork, Record record) {
		String recordId = getFirstFieldVal(record, "907a");
		groupedWork.addRelatedRecord("public_domain_econtent:" + recordId);
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
				if (protectionType.equals("public domain") || protectionType.equals("free")){
					sources.add(eContentSource);
					protectionTypes.add("Public Domain");
				}
			}
		}
		groupedWork.addEContentSources(sources);
		groupedWork.addEContentProtectionTypes(protectionTypes);
	}

	protected List<DataField> getUnsuppressedItems(Record record) {
		return getUnsuppressedEContentItems(record);
	}

	@Override
	public Set<String> loadFormats(Record record, boolean returnFirst) {
		//Don't use this for now since will we just override all of loadFormatDetails
		return null;
	}

	@Override
	protected void loadFormatDetails(GroupedWorkSolr groupedWork, Record record) {
		Set<String> iTypes = getFieldList(record, itemTag + iTypeSubfield);
		HashSet<String> translatedFormats = new HashSet<String>();
		HashSet<String> formatCategories = new HashSet<String>();
		Long formatBoost = 1L;
		for (String iType : iTypes){
			translatedFormats.add(indexer.translateValue("econtent_itype_format", iType));
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
		}
		groupedWork.addFormats(translatedFormats);
		groupedWork.addFormatCategories(formatCategories);
		groupedWork.setFormatBoost(formatBoost);
	}
}
