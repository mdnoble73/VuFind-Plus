package org.vufind;

import org.apache.log4j.Logger;
import org.apache.log4j.pattern.IntegerPatternConverter;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

/**
 * Record Processing Specific to records loaded from CARL.X
 * Pika
 * User: Mark Noble
 * Date: 7/1/2016
 * Time: 11:14 AM
 */
public class CarlXRecordProcessor extends IlsRecordProcessor {
	public CarlXRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, vufindConn, configIni, indexingProfileRS, logger, fullReindex);
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		return itemInfo.getStatusCode().equals("S") || itemInfo.getStatusCode().equals("SI");
	}

	protected String getShelfLocationForItem(ItemInfo itemInfo, DataField itemField, String identifier) {
		String locationCode = getItemSubfieldData(locationSubfieldIndicator, itemField);
		String location = translateValue("location", locationCode, identifier);
		String shelvingLocation = getItemSubfieldData(shelvingLocationSubfield, itemField);
		if (shelvingLocation != null && !shelvingLocation.equals(locationCode)){
			if (location == null){
				location = translateValue("shelf_location", shelvingLocation, identifier);
			}else {
				location += " - " + translateValue("shelf_location", shelvingLocation, identifier);
			}
		}
		return location;
	}

	int numSampleRecordsWithMultiplePrintFormats = 0;
	@Override
	public void loadPrintFormatInformation(RecordInfo ilsRecord, Record record) {
		List<DataField> items = getDataFields(record, itemTag);
		HashMap<String, Integer> printFormats = new HashMap<>();
		for (DataField curItem : items){
			Subfield formatField = curItem.getSubfield(formatSubfield);
			if (formatField != null) {
				String curFormat = formatField.getData();
				String printFormatLower = curFormat.toLowerCase();
				if (!printFormats.containsKey(printFormatLower)) {
					printFormats.put(printFormatLower, 1);
				} else {
					printFormats.put(printFormatLower, printFormats.get(printFormatLower) + 1);
				}
			}
		}

		HashSet<String> selectedPrintFormats = new HashSet<>();
		if (selectedPrintFormats.size() > 1 && numSampleRecordsWithMultiplePrintFormats < 100){
			logger.debug("Record " + ilsRecord.getRecordIdentifier() + " had multiple formats based on the item information");
			numSampleRecordsWithMultiplePrintFormats++;
		}
		int maxPrintFormats = 0;
		String selectedFormat = "";
		for (String printFormat : printFormats.keySet()){
			int numUsages = printFormats.get(printFormat);
			if (numUsages > maxPrintFormats){
				selectedFormat = printFormat;
				maxPrintFormats = numUsages;
			}
		}
		selectedPrintFormats.add(selectedFormat);

		HashSet<String> translatedFormats = translateCollection("format", selectedPrintFormats, ilsRecord.getRecordIdentifier());
		HashSet<String> translatedFormatCategories = translateCollection("format_category", selectedPrintFormats, ilsRecord.getRecordIdentifier());
		ilsRecord.addFormats(translatedFormats);
		ilsRecord.addFormatCategories(translatedFormatCategories);
		Long formatBoost = 0L;
		HashSet<String> formatBoosts = translateCollection("format_boost", selectedPrintFormats, ilsRecord.getRecordIdentifier());
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
