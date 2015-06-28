package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.text.SimpleDateFormat;
import java.util.*;

/**
 * ILS Indexing with customizations specific to Anythink
 * VuFind-Plus
 * User: Mark Noble
 * Date: 2/21/14
 * Time: 3:00 PM
 */
public class AnythinkRecordProcessor extends IlsRecordProcessor {
	private PreparedStatement getDateAddedStmt;
	public AnythinkRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, Logger logger) {
		super(indexer, vufindConn, configIni, logger);

		try{
			getDateAddedStmt = vufindConn.prepareStatement("SELECT dateFirstDetected FROM ils_marc_checksums WHERE ilsId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
		}catch (Exception e){
			logger.error("Unable to setup prepared statement for date added to catalog");
		}
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

	protected void loadTargetAudiences(GroupedWorkSolr groupedWork, Record record, List<PrintIlsItem> printItems) {
		//For Anythink, load audiences based on collection code rather than based on the 008 and 006 fields
		HashSet<String> targetAudiences = new HashSet<String>();
		for (PrintIlsItem printItem : printItems){
			String collection = printItem.getCollection();
			if (collection != null) {
				targetAudiences.add(collection.toLowerCase());
			}
		}

		groupedWork.addTargetAudiences(indexer.translateCollection("target_audience", targetAudiences));
		groupedWork.addTargetAudiencesFull(indexer.translateCollection("target_audience", targetAudiences));
	}

	@Override
	protected void loadDateAdded(GroupedWorkSolr groupedWork, String identfier, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems, List<OnOrderItem> onOrderItems) {
		try {
			getDateAddedStmt.setString(1, identfier);
			ResultSet getDateAddedRS = getDateAddedStmt.executeQuery();
			if (getDateAddedRS.next()) {
				long timeAdded = getDateAddedRS.getLong(1);
				Date curDate = new Date(timeAdded * 1000);
				groupedWork.setDateAdded(curDate, indexer.getAllScopeNames());
				getDateAddedRS.close();
			}else{
				logger.debug("Could not determine date added for " + identfier);
			}
		}catch (Exception e){
			logger.error("Unable to load date added for " + identfier);
		}
	}
}
