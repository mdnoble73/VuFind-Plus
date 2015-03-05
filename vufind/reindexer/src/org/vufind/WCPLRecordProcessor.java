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
 * Date: 4/25/14
 * Time: 11:02 AM
 */
public class WCPLRecordProcessor extends IlsRecordProcessor {
	private String statusesToSuppress;
	private String locationsToSuppress;
	public WCPLRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, Logger logger) {
		super(indexer, vufindConn, configIni, logger);
		this.statusesToSuppress = configIni.get("Catalog", "statusesToSuppress");
		this.locationsToSuppress = configIni.get("Catalog", "locationsToSuppress");
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

	@Override
	protected void loadSystemLists(GroupedWorkSolr groupedWork, Record record) {
		groupedWork.addSystemLists(this.getFieldList(record, "449a"));
	}

	protected boolean isItemSuppressed(DataField curItem) {
		Subfield statusSubfield = curItem.getSubfield(statusSubfieldIndicator);
		if (statusSubfield == null){
			return true;
		}else{
			if (statusSubfield.getData().matches(statusesToSuppress)){
				return true;
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
		//Finally suppress staff items
		Subfield staffSubfield = curItem.getSubfield('o');
		if (staffSubfield != null){
			if (staffSubfield.getData().trim().equals("1")){
				return true;
			}
		}
		return false;
	}
}
