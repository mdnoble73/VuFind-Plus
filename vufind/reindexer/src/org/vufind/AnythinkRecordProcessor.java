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
 * ILS Indexing with customizations specific to Nashville
 * VuFind-Plus
 * User: Mark Noble
 * Date: 2/21/14
 * Time: 3:00 PM
 */
public class AnythinkRecordProcessor extends IlsRecordProcessor {
	public AnythinkRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, Logger logger) {
		super(indexer, vufindConn, configIni, logger);
	}

	@Override
	public void loadPrintFormatInformation(IlsRecord ilsRecord, Record record) {
		Set<String> printFormatsRaw = getFieldList(record, "949c");
		Set<String> printFormats = new HashSet<String>();
		for (String curFormat : printFormatsRaw){
			printFormats.add(curFormat.toLowerCase());
		}

		HashSet<String> translatedFormats = indexer.translateCollection("format", printFormats);
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

	protected boolean isItemSuppressed(DataField curItem) {
		//Suppressed if |c is w
		if (curItem.getSubfield('c') != null){
			if (curItem.getSubfield('c').getData().matches("eqx|ill|laptop|u|vf")){
				return true;
			}
		}
		return false;
	}

	@Override
	protected boolean isItemAvailable(PrintIlsItem ilsRecord) {
		boolean available = false;
		String status = ilsRecord.getStatus();
		String availableStatus = "is";
		if (availableStatus.indexOf(status.charAt(0)) >= 0) {
			available = true;
		}
		return available;
	}

	protected void loadAdditionalOwnershipInformation(GroupedWorkSolr groupedWork, PrintIlsItem printItem){
		String collection = printItem.getCollection();
		if (collection != null && collection.length() > 0){
			groupedWork.addCollectionGroup(indexer.translateValue("collection_group", collection.toLowerCase()));
		}
	}

	protected Set<String> getBisacSubjects(Record record){
		return getFieldList(record, "690a");
	}

}
