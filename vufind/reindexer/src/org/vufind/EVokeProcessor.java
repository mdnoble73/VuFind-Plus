package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.marc.Record;

import java.io.File;
import java.io.FileInputStream;
import java.sql.Connection;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 9/29/2014
 * Time: 11:27 AM
 */
public class EVokeProcessor extends MarcRecordProcessor{
	private String individualMarcPath;
	private Logger logger;

	public EVokeProcessor(GroupedWorkIndexer groupedWorkIndexer, Connection vufindConn, Ini configIni, Logger logger){
		super(groupedWorkIndexer, logger);
		this.indexer = groupedWorkIndexer;
		this.logger = logger;

		individualMarcPath = configIni.get("Reindex", "individualMarcPath");
	}

	public void processRecord(GroupedWorkSolr groupedWork, String identifier) {
		//Load the marc record from disc
		String firstChars = identifier.substring(0, 4);
		String basePath = individualMarcPath + "/" + firstChars;
		String individualFilename = basePath + "/" + identifier + ".mrc";
		File individualFile = new File(individualFilename);
		try {
			Record marcRecord = EVokeMarcReader.readMarc(individualFile);
			updateGroupedWorkSolrDataBasedOnMarc(groupedWork, marcRecord, identifier);
		} catch (Exception e) {
			logger.error("Error reading data from ils file " + individualFile.toString(), e);
		}
	}

	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		//Get a list of items for the record

		//Do updates based on the overall bib (shared regardless of scoping)
		updateGroupedWorkSolrDataBasedOnStandardMarcData(groupedWork, record);

		//Do special processing for eVoke
		String fullDescription = Util.getCRSeparatedString(getFieldList(record, "520a"));
		//TODO: Determine the format correctly
		groupedWork.addDescription(fullDescription, "eBook");
		//TODO: Do stuff that requires formats to be loaded
		/*loadEditions(groupedWork, record, ilsRecords);
		loadPhysicalDescription(groupedWork, record, ilsRecords);
		loadLanguageDetails(groupedWork, record, ilsRecords);
		loadPublicationDetails(groupedWork, record, ilsRecords);

		loadOwnershipInformation(groupedWork, printItems, econtentItems, onOrderItems);
		loadAvailability(groupedWork, printItems, econtentItems);
		loadUsability(groupedWork, printItems, econtentItems);
		loadPopularity(groupedWork, printItems, econtentItems);
		loadDateAdded(groupedWork, printItems, econtentItems);
		loadITypes(groupedWork, printItems, econtentItems);

		loadLocalCallNumbers(groupedWork, printItems, econtentItems);
		groupedWork.addBarcodes(getFieldList(record, itemTag + barcodeSubfield));
		groupedWork.setRelatedRecords(ilsRecords);
		groupedWork.setFormatInformation(ilsRecords);

		loadEContentSourcesAndProtectionTypes(groupedWork, econtentItems);

		groupedWork.addHoldings(printItems.size());*/
	}
}
