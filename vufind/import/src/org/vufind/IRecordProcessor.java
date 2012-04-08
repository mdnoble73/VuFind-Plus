package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;

public interface IRecordProcessor {
	public boolean init(Ini configIni, String serverName, Logger logger);
	public void finish();
	public ProcessorResults getResults();
}
