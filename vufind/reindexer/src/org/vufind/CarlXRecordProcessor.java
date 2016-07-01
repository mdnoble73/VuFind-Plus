package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;

import java.sql.Connection;
import java.sql.ResultSet;

/**
 * Record Processing Specific to records loaded from CARL.X
 * Pika
 * User: Mark Noble
 * Date: 7/1/2016
 * Time: 11:14 AM
 */
public class CarlXRecordProcessor extends IlsRecordProcessor {
	public CarlXRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, vufindConn, configIni, indexingProfileRS, logger, fullReindex);
	}

	@Override
	protected boolean isItemAvailable(ItemInfo itemInfo) {
		return itemInfo.getStatusCode().equals("S") || itemInfo.getStatusCode().equals("SI");
	}
}
