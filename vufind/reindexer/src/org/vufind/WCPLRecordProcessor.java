package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.*;

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
	private long maxBibNumber = 1;
	public WCPLRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, Logger logger) {
		super(indexer, vufindConn, configIni, logger);
		this.statusesToSuppress = configIni.get("Catalog", "statusesToSuppress");
		this.locationsToSuppress = configIni.get("Catalog", "locationsToSuppress");

		try {
			PreparedStatement getMaxBibNumberStmt = vufindConn.prepareStatement("SELECT MAX(CAST(ilsId AS UNSIGNED)) FROM ils_marc_checksums");
			ResultSet maxBibNumberRS = getMaxBibNumberStmt.executeQuery();
			if (maxBibNumberRS.next()){
				maxBibNumber = maxBibNumberRS.getLong(1);
			}
		}catch(Exception e){
			logger.error("Error loading max bib number for Horizon");
		}

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

	private static SimpleDateFormat dateAddedFormatter = null;
	private Integer currentYear = null;
	Calendar curDate;
	protected void loadDateAdded(GroupedWorkSolr groupedWork, String identfier, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems) {
		//Since date added cannot be extracted from Horizon, we will approximate using the bib number
		//Formula is:  (bib number / max bib number - 1)  * 365 * (current year - 1950)
		if (currentYear == null){
			curDate = GregorianCalendar.getInstance();
			currentYear = curDate.get(Calendar.YEAR);
		}
		Long identifierNumber = Long.parseLong(identfier);
		Integer daysSinceAdded = (int)(((float)identifierNumber / (float)maxBibNumber - 1) * 375 * (currentYear - 1950));
		curDate.setTime(new Date());
		curDate.add(Calendar.DATE, daysSinceAdded);
		groupedWork.setDateAdded(curDate.getTime(), indexer.getAllScopeNames());
	}
}
