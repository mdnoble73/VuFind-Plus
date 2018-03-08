package org.vufind;

import org.apache.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.sql.Connection;
import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.List;

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
class LionRecordProcessor extends IIIRecordProcessor {
	LionRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, vufindConn, indexingProfileRS, logger, fullReindex);

		loadOrderInformationFromExport();

		validCheckedOutStatusCodes.add("&");
		validCheckedOutStatusCodes.add("c");
		validCheckedOutStatusCodes.add("o");
		validCheckedOutStatusCodes.add("y");
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		boolean available = false;
		String status = itemInfo.getStatusCode();
		String dueDate = itemInfo.getDueDate() == null ? "" : itemInfo.getDueDate();
		String availableStatus = "-&couvy";
		if (status.length() > 0 && availableStatus.indexOf(status.charAt(0)) >= 0) {
			if (dueDate.length() == 0) {
				available = true;
			}
		}

		return available;
	}

	protected boolean loanRulesAreBasedOnCheckoutLocation(){
		return false;
	}

	protected boolean determineLibraryUseOnly(ItemInfo itemInfo, Scope curScope) {
		return itemInfo.getStatusCode().equals("o");
	}
}
