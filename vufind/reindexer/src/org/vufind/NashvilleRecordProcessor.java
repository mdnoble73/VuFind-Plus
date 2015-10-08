package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;
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

		HashSet<String> translatedFormats = translateCollection("format", printFormats, ilsRecord.getRecordIdentifier());
		HashSet<String> translatedFormatCategories = translateCollection("format_category", printFormats, ilsRecord.getRecordIdentifier());
		ilsRecord.addFormats(translatedFormats);
		ilsRecord.addFormatCategories(translatedFormatCategories);
		Long formatBoost = 0L;
		HashSet<String> formatBoosts = translateCollection("format_boost", printFormats, ilsRecord.getRecordIdentifier());
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

	protected List<RecordInfo> loadUnsuppressedEContentItems(GroupedWorkSolr groupedWork, String identifier, Record record){
		List<RecordInfo> unsuppressedEcontentRecords = new ArrayList<>();

		//Check to see if we should add a supplemental record:
		String url = getFirstFieldVal(record, "856u");
		if (url.contains("digital.library.nashville.org") || url.contains("www.library.nashville.org/localhistory/findingaids") || url.contains("nashville.contentdm.oclc.org")){
			//Much of the econtent for flatirons has no items.  Need to determine the location based on the 907b field
			String eContentLocation = getFirstFieldVal(record, "945l");
			if (eContentLocation != null) {
				ItemInfo itemInfo = new ItemInfo();
				itemInfo.setIsEContent(true);
				itemInfo.setLocationCode(eContentLocation);
				itemInfo.seteContentSource("Nashville Archives");
				itemInfo.seteContentProtectionType("external");
				itemInfo.seteContentSharing("library");
				itemInfo.setCallNumber("Online");
				itemInfo.setShelfLocation(itemInfo.geteContentSource());
				RecordInfo relatedRecord = groupedWork.addRelatedRecord("external_econtent", identifier);
				relatedRecord.setSubSource(profileType);
				relatedRecord.addItem(itemInfo);
				itemInfo.seteContentUrl(url);

				loadEContentFormatInformation(record, relatedRecord, itemInfo);
				itemInfo.setFormat("Digitized Content");
				itemInfo.setFormatCategory("Other");
				relatedRecord.setFormatBoost(1);

				itemInfo.setDetailedStatus("Available Online");

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

		return unsuppressedEcontentRecords;
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
