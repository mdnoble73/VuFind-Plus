package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.Date;
import java.util.HashSet;
import java.util.Set;
import java.util.regex.Pattern;

/**
 * Description goes here
 * Pika
 * User: Mark Noble
 * Date: 4/25/14
 * Time: 11:02 AM
 */
public class AACPLRecordProcessor extends IlsRecordProcessor {
	private PreparedStatement getDateAddedStmt;
	public AACPLRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, vufindConn, configIni, indexingProfileRS, logger, fullReindex);

		try{
			getDateAddedStmt = vufindConn.prepareStatement("SELECT dateFirstDetected FROM ils_marc_checksums WHERE ilsId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		}catch (Exception e){
			logger.error("Unable to setup prepared statement for date added to catalog");
		}
	}

	protected boolean isItemSuppressed(DataField curItem) {
		if (statusSubfieldIndicator != ' ') {
			Subfield statusSubfield = curItem.getSubfield(statusSubfieldIndicator);
			//For Anne Arundel, the status is blank if the item is on shelf
			if (statusSubfield != null) {
				if (statusSubfield.getData().matches(statusesToSuppress)) {
					return true;
				}
			}
		}
		Subfield locationSubfield = curItem.getSubfield(locationSubfieldIndicator);
		if (locationSubfield == null){
			return true;
		}else{
			if (locationSubfield.getData().trim().matches(locationsToSuppress)){
				return true;
			}
		}
		if (collectionSubfield != ' '){
			Subfield collectionSubfieldValue = curItem.getSubfield(collectionSubfield);
			if (collectionSubfieldValue == null){
				return true;
			}else{
				if (collectionSubfieldValue.getData().trim().matches(collectionsToSuppress)){
					return true;
				}
			}
		}
		return false;
	}

	protected String getItemStatus(DataField itemField){
		String subfieldData = getItemSubfieldData(statusSubfieldIndicator, itemField);
		if (subfieldData == null){
			subfieldData = "ONSHELF";
		}
		return subfieldData;
	}

	Pattern availableStati = Pattern.compile("^(y)$");
	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		boolean available = false;
		if (itemInfo.getStatusCode().equals("ONSHELF")) {
			available = true;
		}
		return available;
	}

	@Override
	protected void loadDateAdded(String identfier, DataField itemField, ItemInfo itemInfo) {
		try {
			getDateAddedStmt.setString(1, identfier);
			ResultSet getDateAddedRS = getDateAddedStmt.executeQuery();
			if (getDateAddedRS.next()) {
				long timeAdded = getDateAddedRS.getLong(1);
				Date curDate = new Date(timeAdded * 1000);
				itemInfo.setDateAdded(curDate);
				getDateAddedRS.close();
			}else{
				logger.debug("Could not determine date added for " + identfier);
			}
		}catch (Exception e){
			logger.error("Unable to load date added for " + identfier);
		}
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

	protected void loadTargetAudiences(GroupedWorkSolr groupedWork, Record record, HashSet<ItemInfo> printItems, String identifier) {
		//For Wake County, load audiences based on collection code rather than based on the 008 and 006 fields
		HashSet<String> targetAudiences = new HashSet<>();
		for (ItemInfo printItem : printItems){
			String collection = printItem.getCollection();
			if (collection != null) {
				targetAudiences.add(collection.toLowerCase());
			}
		}

		HashSet<String> translatedAudiences = translateCollection("audience", targetAudiences, identifier);
		groupedWork.addTargetAudiences(translatedAudiences);
		groupedWork.addTargetAudiencesFull(translatedAudiences);
	}
}
