package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;

import java.sql.Connection;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

/**
 * ILS Indexing with customizations specific to Nashville
 * VuFind-Plus
 * User: Mark Noble
 * Date: 2/21/14
 * Time: 3:00 PM
 */
public class NashvilleRecordProcessor extends IlsRecordProcessor {
	public NashvilleRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, Logger logger) {
		super(indexer, vufindConn, configIni, logger);
	}

	@Override
	public Set<String> loadFormats(GroupedWorkSolr groupedWork, Record record, String identifier, List<DataField> printItems, List<DataField> econtentItems) {
		String format = getFirstFieldVal(record, "998d");
		Set<String> formats = new HashSet<String>();
		formats.add(format);
		return formats;
	}

}
