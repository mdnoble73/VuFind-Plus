package org.vufind;

import java.sql.Connection;

import org.apache.log4j.Logger;
import org.ini4j.Ini;

public interface IRecordProcessor {
	public boolean init(Ini configIni, String serverName, long reindexLogId, Connection vufindConn, Connection econtentConn, Logger logger);
	public void finish();
	public ProcessorResults getResults();
}
