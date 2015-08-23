package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.*;

/**
 * ILS Indexing with customizations specific to Marmot.  Handles processing
 * - print items
 * - econtent items stored within Sierra
 * - order items
 *
 * Pika
 * User: Mark Noble
 * Date: 2/21/14
 * Time: 3:00 PM
 */
public class MarmotRecordProcessor extends IIIRecordProcessor {
	public MarmotRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, vufindConn, configIni, indexingProfileRS, logger, fullReindex);
	}

	protected void loadUnsuppressedPrintItems(GroupedWorkSolr groupedWork, RecordInfo recordInfo, String identifier, Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				if (useEContentSubfield){
					if (itemField.getSubfield(eContentSubfieldIndicator) != null){
						String eContentData = itemField.getSubfield(eContentSubfieldIndicator).getData();
						if (eContentData.indexOf(':') >= 0){
							isEContent = true;
							String[] eContentFields = eContentData.split(":");
							String sourceType = eContentFields[0].toLowerCase().trim();
							if (sourceType.equals("overdrive")){
								isOverDrive = true;
							}
						}
					}
				}
				if (!isOverDrive && !isEContent){
					getPrintIlsItem(groupedWork, recordInfo, record, itemField);
				}
			}
		}
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		boolean available = false;
		String status = itemInfo.getStatusCode();
		String dueDate = itemInfo.getDueDate() == null ? "" : itemInfo.getDueDate();
		String availableStatus = "-dowju";
		if (status.length() > 0 && availableStatus.indexOf(status.charAt(0)) >= 0) {
			if (dueDate.length() == 0) {
				available = true;
			}
		}
		return available;
	}

	protected boolean isItemSuppressed(DataField curItem) {
		Subfield icode2Subfield = curItem.getSubfield(iCode2Subfield);
		if (icode2Subfield == null){
			return false;
		}
		String icode2 = icode2Subfield.getData().toLowerCase().trim();
		Subfield locationCodeSubfield = curItem.getSubfield(locationSubfieldIndicator);
		if (locationCodeSubfield == null)                                                 {
			return false;
		}
		String locationCode = locationCodeSubfield.getData().trim();

		return icode2.equals("n") || icode2.equals("x") || locationCode.equals("zzzz");
	}

	@Override
	protected List<RecordInfo> loadUnsuppressedEContentItems(GroupedWorkSolr groupedWork, String identifier, Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<RecordInfo> unsuppressedEcontentRecords = new ArrayList<>();
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				boolean isHoopla = false;
				if (useEContentSubfield){
					if (itemField.getSubfield(eContentSubfieldIndicator) != null){
						String eContentData = itemField.getSubfield(eContentSubfieldIndicator).getData();
						if (eContentData.indexOf(':') >= 0){
							isEContent = true;
							String[] eContentFields = eContentData.split(":");
							String sourceType = eContentFields[0].toLowerCase().trim();
							if (sourceType.equals("overdrive")){
								isOverDrive = true;
							}else if (sourceType.equals("hoopla")){
								isHoopla = true;
							}
						}else{
							String source = itemField.getSubfield(eContentSubfieldIndicator).getData().trim();
							if (source.equalsIgnoreCase("overdrive")){
								isOverDrive = true;
							}else if (source.equalsIgnoreCase("hoopla")){
								isHoopla = true;
							}
						}
					}
				}
				if (!isOverDrive && !isHoopla && isEContent){
					unsuppressedEcontentRecords.add(getEContentIlsRecord(groupedWork, record, identifier, itemField));
				}
			}
		}
		return unsuppressedEcontentRecords;
	}

	@Override
	protected void loadEContentFormatInformation(Record record, RecordInfo econtentRecord, ItemInfo econtentItem) {
		String protectionType = econtentItem.geteContentProtectionType();
		switch (protectionType) {
			case "acs":
			case "drm":
			case "public domain":
			case "free":
				String filename = econtentItem.geteContentFilename();
				if (filename == null) {
					//Did not get a filename, use the iType as a placeholder
					String iType = econtentItem.getITypeCode();
					if (iType != null) {
						String translatedFormat = translateValue("econtent_itype_format", iType);
						String translatedFormatCategory = translateValue("econtent_itype_format_category", iType);
						String translatedFormatBoost = translateValue("econtent_itype_format_boost", iType);
						econtentItem.setFormat(translatedFormat);
						econtentItem.setFormatCategory(translatedFormatCategory);
						econtentRecord.setFormatBoost(Long.parseLong(translatedFormatBoost));
					} else {
						logger.warn("Did not get a filename or itype for " + econtentRecord.getFullIdentifier());
					}
				} else if (filename.indexOf('.') > 0) {
					String fileExtension = filename.substring(filename.lastIndexOf('.') + 1);
					if (fileExtension.equalsIgnoreCase("noimages")) {
						//Try again, this wasn't the true extension
						String tmpFilename = filename.replace(".noimages", "");
						fileExtension = tmpFilename.substring(tmpFilename.lastIndexOf('.') + 1);
					}
					String translatedFormat = translateValue("format", fileExtension);
					String translatedFormatCategory = translateValue("format_category", fileExtension);
					String translatedFormatBoost = translateValue("format_boost", fileExtension);
					econtentItem.setFormat(translatedFormat);
					econtentItem.setFormatCategory(translatedFormatCategory);
					econtentRecord.setFormatBoost(Long.parseLong(translatedFormatBoost));
				} else {
					//For now we know these are folders of MP3 files
					//TODO: Probably should actually open the folder to make sure that it contains MP3 files
					String translatedFormat = translateValue("format", "mp3");
					String translatedFormatCategory = translateValue("format_category", "mp3");
					String translatedFormatBoost = translateValue("format_boost", "mp3");
					econtentItem.setFormat(translatedFormat);
					econtentItem.setFormatCategory(translatedFormatCategory);
					econtentRecord.setFormatBoost(Long.parseLong(translatedFormatBoost));
				}
				break;
			case "external":
				String iType = econtentItem.getITypeCode();
				if (iType != null) {
					String translatedFormat = translateValue("econtent_itype_format", iType);
					String translatedFormatCategory = translateValue("econtent_itype_format_category", iType);
					String translatedFormatBoost = translateValue("econtent_itype_format_boost", iType);
					econtentItem.setFormat(translatedFormat);
					econtentItem.setFormatCategory(translatedFormatCategory);
					econtentRecord.setFormatBoost(Long.parseLong(translatedFormatBoost));
				} else {
					logger.warn("Did not get a iType for external eContent " + econtentRecord.getFullIdentifier());
				}
				break;
			default:
				logger.warn("Unknown protection type " + protectionType);
				break;
		}
	}

	protected boolean loanRulesAreBasedOnCheckoutLocation(){
		return true;
	}
}
