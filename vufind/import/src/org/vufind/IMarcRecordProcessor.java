package org.vufind;

import org.apache.log4j.Logger;

public interface IMarcRecordProcessor {
	public boolean processMarcRecord(MarcProcessor processor, BasicMarcInfo recordInfo, Logger logger) ;
}
