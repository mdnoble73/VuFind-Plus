package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.HashSet;
import java.util.Set;

/**
 * ILS Indexing with customizations specific to Nashville
 * Pika
 * User: Mark Noble
 * Date: 2/21/14
 * Time: 3:00 PM
 */
public class NashvilleRecordProcessor extends IIIRecordProcessor {
	public NashvilleRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, vufindConn, configIni, indexingProfileRS, logger, fullReindex);
	}

	@Override
	public void loadPrintFormatInformation(RecordInfo ilsRecord, Record record) {
		Set<String> printFormatsRaw = getFieldList(record, "998d");
		HashSet<String> printFormats = new HashSet<>();
		for (String curFormat : printFormatsRaw){
			printFormats.add(curFormat.toLowerCase());
		}

		HashSet<String> translatedFormats = translateCollection("format", printFormats);
		HashSet<String> translatedFormatCategories = translateCollection("format_category", printFormats);
		ilsRecord.addFormats(translatedFormats);
		ilsRecord.addFormatCategories(translatedFormatCategories);
		Long formatBoost = 0L;
		HashSet<String> formatBoosts = translateCollection("format_boost", printFormats);
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
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		boolean available = false;
		String status = itemInfo.getStatusCode();
		String dueDate = itemInfo.getDueDate() == null ? "" : itemInfo.getDueDate();
		String availableStatus = "-do";
		if (availableStatus.indexOf(status.charAt(0)) >= 0) {
			if (dueDate.length() == 0 || dueDate.trim().equals("-  -")) {
				available = true;
			}
		}
		return available;
	}

	protected boolean isItemSuppressed(DataField curItem) {
		Subfield locationCodeSubfield = curItem.getSubfield(locationSubfieldIndicator);
		if (locationCodeSubfield == null) {
			return false;
		}
		String locationCode = locationCodeSubfield.getData().trim();

		if (locationCode.matches(".*sup")){
			return true;
		}else{
			return super.isItemSuppressed(curItem);
		}
	}

	@Override
	protected boolean isOrderItemValid(String status, String code3) {
		return (code3 == null || !code3.equals("s")) && (status.equals("o") || status.equals("1") || status.equals("a") || status.equals("q"));
	}

	protected boolean loanRulesAreBasedOnCheckoutLocation(){
		return true;
	}

	protected boolean determineLibraryUseOnly(ItemInfo itemInfo, Scope curScope) {
		return itemInfo.getStatusCode().equals("o");
	}
}
