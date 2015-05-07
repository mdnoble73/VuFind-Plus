package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.util.ArrayList;
import java.util.List;

/**
 * ILS Indexing with customizations specific to Flatirons Library Consortium
 *
 * VuFind-Plus
 * User: Mark Noble
 * Date: 12/29/2014
 * Time: 10:25 AM
 */
public class FlatironsRecordProcessor extends IlsRecordProcessor{
	public FlatironsRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, Logger logger) {
		super(indexer, vufindConn, configIni, logger);
	}

	@Override
	protected boolean isItemAvailable(PrintIlsItem ilsRecord) {
		boolean available = false;
		String status = ilsRecord.getStatus();
		String dueDate = ilsRecord.getDateDue() == null ? "" : ilsRecord.getDateDue();
		String availableStatus = "-oyj";
		if (status.length() > 0 && availableStatus.indexOf(status.charAt(0)) >= 0) {
			if (dueDate.length() == 0) {
				available = true;
			}
		}
		return available;
	}

	protected List<PrintIlsItem> getUnsuppressedPrintItems(String identifier, Record record){
		String bibFormat = getFirstFieldVal(record, "998e");
		boolean isEContentBibFormat = bibFormat.equals("3") || bibFormat.equals("t") || bibFormat.equals("m") || bibFormat.equals("w") || bibFormat.equals("u");
		String url = getFirstFieldVal(record, "856u");
		boolean has856 = url != null;

		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<PrintIlsItem> unsuppressedItemRecords = new ArrayList<PrintIlsItem>();
		if (!(isEContentBibFormat && has856)) {
			//The record is print
			for (DataField itemField : itemRecords){
				if (!isItemSuppressed(itemField)){
					PrintIlsItem printIlsRecord = getPrintIlsItem(record, itemField);
					if (printIlsRecord != null) {
						unsuppressedItemRecords.add(printIlsRecord);
					}
				}
			}
		}
		return unsuppressedItemRecords;
	}

	protected List<EContentIlsItem> getUnsuppressedEContentItems(String identifier, Record record){
		String bibFormat = getFirstFieldVal(record, "998e").trim();
		boolean isEContentBibFormat = bibFormat.equals("3") || bibFormat.equals("t") || bibFormat.equals("m") || bibFormat.equals("w") || bibFormat.equals("u");
		String url = getFirstFieldVal(record, "856u");
		boolean has856 = url != null;

		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<EContentIlsItem> unsuppressedEcontentRecords = new ArrayList<EContentIlsItem>();
		if (isEContentBibFormat && has856) {
			for (DataField itemField : itemRecords) {
				if (!isItemSuppressed(itemField)) {
					//Check to see if the item has an eContent indicator
					unsuppressedEcontentRecords.add(getEContentIlsRecord(record, identifier, itemField));
				}
			}
			if (itemRecords.size() == 0){
				//Much of the econtent for flatirons has no items.  Need to determine the location based on the 907b field
				String eContentLocation = getFirstFieldVal(record, "907b");
				if (eContentLocation != null) {
					EContentIlsItem ilsEContentItem = new EContentIlsItem();
					ilsEContentItem.setLocation(eContentLocation);
					ilsEContentItem.setSource("External eContent");
					ilsEContentItem.setProtectionType("external");
					ilsEContentItem.setSharing("library");
					if (url.contains("ebrary.com")) {
						ilsEContentItem.setSource("ebrary");
					}else{
						ilsEContentItem.setSource("Unknown");
					}
					ilsEContentItem.setRecordIdentifier("external_econtent:" + identifier);
					//Check the 856 tag to see if there is a link there
					List<DataField> urlFields = getDataFields(record, "856");
					for (DataField urlField : urlFields){
						//load url into the item
						if (urlField.getSubfield('u') != null){
							//Try to determine if this is a resource or not.
							if (urlField.getIndicator1() == '4' || urlField.getIndicator1() == ' ' || urlField.getIndicator1() == '0'){
								if (urlField.getIndicator2() == ' ' || urlField.getIndicator2() == '0' || urlField.getIndicator2() == '1' || urlField.getIndicator2() == '4') {
									ilsEContentItem.setUrl(urlField.getSubfield('u').getData().trim());
									break;
								}
							}

						}
					}
					ilsEContentItem.setAvailable(true);

					//Determine which scopes this title belongs to
					for (Scope curScope : indexer.getScopes()){
						boolean includedDirectly = curScope.isEContentDirectlyOwned(ilsEContentItem);
						if (curScope.isEContentLocationPartOfScope(ilsEContentItem)){
							ilsEContentItem.addRelatedScope(curScope);
							if (includedDirectly){
								ilsEContentItem.addScopeThisItemIsDirectlyIncludedIn(curScope.getScopeName());
							}
						}
					}

					unsuppressedEcontentRecords.add(ilsEContentItem);
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

	protected void loadEContentFormatInformation(IlsRecord econtentRecord, EContentIlsItem econtentItem) {
		String collection = "external_ebook";
		String translatedFormat = indexer.translateValue("format", collection);
		String translatedFormatCategory = indexer.translateValue("format_category", collection);
		String translatedFormatBoost = indexer.translateValue("format_boost", collection);
		econtentRecord.setFormatInformation(translatedFormat, translatedFormatCategory, translatedFormatBoost);
	}

	protected String getEContentSharing(EContentIlsItem ilsEContentItem, DataField itemField) {
		return "library";
	}
}
