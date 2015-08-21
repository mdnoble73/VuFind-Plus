package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.List;

/**
 * ILS Indexing with customizations specific to Flatirons Library Consortium
 *
 * Pika
 * User: Mark Noble
 * Date: 12/29/2014
 * Time: 10:25 AM
 */
public class FlatironsRecordProcessor extends IIIRecordProcessor{
	public FlatironsRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, vufindConn, configIni, indexingProfileRS, logger, fullReindex);
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		boolean available = false;
		String status = itemInfo.getStatusCode();
		String dueDate = itemInfo.getDueDate() == null ? "" : itemInfo.getDueDate();
		String availableStatus = "-oyj";
		if (status.length() > 0 && availableStatus.indexOf(status.charAt(0)) >= 0) {
			if (dueDate.length() == 0) {
				available = true;
			}
		}
		return available;
	}

	@Override
	protected void loadUnsuppressedPrintItems(GroupedWorkSolr groupedWork, RecordInfo recordInfo, String identifier, Record record){
		String bibFormat = getFirstFieldVal(record, "998e");
		boolean isEContentBibFormat = bibFormat.equals("3") || bibFormat.equals("t") || bibFormat.equals("m") || bibFormat.equals("w") || bibFormat.equals("u");
		String url = getFirstFieldVal(record, "856u");
		boolean has856 = url != null;

		List<DataField> itemRecords = getDataFields(record, itemTag);
		if (!(isEContentBibFormat && has856)) {
			//The record is print
			for (DataField itemField : itemRecords){
				if (!isItemSuppressed(itemField)){
					getPrintIlsItem(groupedWork, recordInfo, record, itemField);
				}
			}
		}
	}

	@Override
	protected List<RecordInfo> loadUnsuppressedEContentItems(GroupedWorkSolr groupedWork, String identifier, Record record){
		String bibFormat = getFirstFieldVal(record, "998e").trim();
		boolean isEContentBibFormat = bibFormat.equals("3") || bibFormat.equals("t") || bibFormat.equals("m") || bibFormat.equals("w") || bibFormat.equals("u");
		String url = getFirstFieldVal(record, "856u");
		boolean has856 = url != null;

		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<RecordInfo> unsuppressedEcontentRecords = new ArrayList<>();
		if (isEContentBibFormat && has856) {
			for (DataField itemField : itemRecords) {
				if (!isItemSuppressed(itemField)) {
					//Check to see if the item has an eContent indicator
					unsuppressedEcontentRecords.add(getEContentIlsRecord(groupedWork, record, identifier, itemField));
				}
			}
			if (itemRecords.size() == 0){
				//Much of the econtent for flatirons has no items.  Need to determine the location based on the 907b field
				String eContentLocation = getFirstFieldVal(record, "907b");
				if (eContentLocation != null) {
					ItemInfo itemInfo = new ItemInfo();
					itemInfo.setLocationCode(eContentLocation);
					itemInfo.seteContentSource("External eContent");
					itemInfo.seteContentProtectionType("external");
					itemInfo.seteContentSharing("library");
					if (url.contains("ebrary.com")) {
						itemInfo.seteContentSource("ebrary");
					}else{
						itemInfo.seteContentSource("Unknown");
					}
					RecordInfo relatedRecord = groupedWork.addRelatedRecord("external_econtent", identifier);
					relatedRecord.setSubSource(profileType);
					relatedRecord.addItem(itemInfo);
					//Check the 856 tag to see if there is a link there
					loadEContentUrl(record, itemInfo);

					//Determine which scopes this title belongs to
					for (Scope curScope : indexer.getScopes()){
						if (curScope.isItemPartOfScope(profileType, eContentLocation, "", false, false, true)){
							ScopingInfo scopingInfo = itemInfo.addScope(curScope);
							scopingInfo.setAvailable(true);
							scopingInfo.setStatus("Available Online");
							scopingInfo.setGroupedStatus("Available Online");
							scopingInfo.setHoldable(false);
							if (curScope.isLocationScope()) {
								scopingInfo.setLocallyOwned(curScope.isItemOwnedByScope(profileType, eContentLocation, ""));
							}
							if (curScope.isLibraryScope()) {
								scopingInfo.setLibraryOwned(curScope.isItemOwnedByScope(profileType, eContentLocation, ""));
							}
						}
					}

					unsuppressedEcontentRecords.add(relatedRecord);
				}
			}
		}
		return unsuppressedEcontentRecords;
	}

	protected boolean isBibSuppressed(Record record) {
		DataField field998 = (DataField)record.getVariableField("998");
		if (field998 != null){
			Subfield bcode3Subfield = field998.getSubfield('f');
			if (bcode3Subfield != null){
				String bCode3 = bcode3Subfield.getData().toLowerCase().trim();
				if (bCode3.matches("^(c|d|s|a|m|r|n)$")){
					return true;
				}
			}
		}

		String bibFormat = getFirstFieldVal(record, "998e").trim();
		boolean isEContentBibFormat = bibFormat.equals("3") || bibFormat.equals("t") || bibFormat.equals("m") || bibFormat.equals("w") || bibFormat.equals("u");
		String url = getFirstFieldVal(record, "856u");
		boolean has856 = url != null;
		if (isEContentBibFormat && has856){
			//Suppress if the url is an overdrive or hoopla url
			if (url.contains("lib.overdrive") || url.contains("hoopla")){
				return true;
			}
		}

		return false;
	}

	protected boolean isItemSuppressed(DataField curItem) {
		Subfield icode2Subfield = curItem.getSubfield(iCode2Subfield);
		if (icode2Subfield != null) {
			String icode2 = icode2Subfield.getData().toLowerCase().trim();

			//Suppress icode2 of wmsrn
			//         status = l
			//         bcode 3 = cdsamrn
			if (icode2.matches("^(w|m|s|r|n)$")) {
				return true;
			}
		}
		//Check status
		Subfield statusSubfield = curItem.getSubfield(statusSubfieldIndicator);
		if (statusSubfield != null){
			String status = statusSubfield.getData();
			if (status.equals("l")){
				return true;
			}
		}
		return false;
	}

	protected void loadEContentFormatInformation(RecordInfo econtentRecord, ItemInfo econtentItem) {
		String collection = "online_resource";
		String translatedFormat = translateValue("format", collection);
		String translatedFormatCategory = translateValue("format_category", collection);
		String translatedFormatBoost = translateValue("format_boost", collection);
		econtentItem.setFormat(translatedFormat);
		econtentItem.setFormatCategory(translatedFormatCategory);
		econtentRecord.setFormatBoost(Long.parseLong(translatedFormatBoost));
	}

	protected String getEContentSharing(ItemInfo ilsEContentItem, DataField itemField) {
		return "library";
	}

	protected boolean loanRulesAreBasedOnCheckoutLocation(){
		return false;
	}
}
