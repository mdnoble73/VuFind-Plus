package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;

import java.sql.Connection;
import java.util.HashSet;
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

	protected boolean isItemSuppressed(DataField curItem) {
		if (curItem.getSubfield('i') != null){
			return curItem.getSubfield('i').getData().equals("1");
		}else{
			return false;
		}
	}
}
