package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;

import java.sql.Connection;
import java.sql.ResultSet;

/**
 * Description goes here
 * Pika
 * User: Mark Noble
 * Date: 7/8/2015
 * Time: 4:43 PM
 */
public class NashvilleSchoolsRecordProcessor extends IlsRecordProcessor {
	public NashvilleSchoolsRecordProcessor(GroupedWorkIndexer groupedWorkIndexer, Connection vufindConn, Ini configIni, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(groupedWorkIndexer, vufindConn, configIni, indexingProfileRS, logger, fullReindex);
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		//TODO: Figure out how to determine if an item is available
		return true;
	}

	protected boolean isItemValid(String itemStatus, String itemLocation) {
		return itemLocation != null;
	}
}
