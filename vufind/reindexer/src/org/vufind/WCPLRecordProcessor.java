package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;

import java.sql.Connection;
import java.util.HashMap;
import java.util.List;
import java.util.Set;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 4/25/14
 * Time: 11:02 AM
 */
public class WCPLRecordProcessor extends IlsRecordProcessor {
	public WCPLRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, Logger logger) {
		super(indexer, vufindConn, configIni, logger);
	}

	@Override
	public void loadFormats(GroupedWorkSolr groupedWork, Record record, String identifier, List<PrintIlsRecord> printItems, List<EContentIlsRecord> econtentItems) {
		//Get formats based on 949c
		Set<String> collectionFields = this.getFieldList(record, "949c");
	}
}
