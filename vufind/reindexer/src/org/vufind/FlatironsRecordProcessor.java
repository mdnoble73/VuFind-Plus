package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.util.HashSet;
import java.util.Set;

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

	protected boolean isItemSuppressed(DataField curItem) {
		Subfield icode2Subfield = curItem.getSubfield(iCode2Subfield);
		if (icode2Subfield == null){
			return false;
		}
		String icode2 = icode2Subfield.getData().toLowerCase().trim();

		//Suppress icode2 of wmsrn
		//         status = l
		//         bcode 3 = cdsamrn
		return icode2.equals("n") || icode2.equals("x");
	}
}
