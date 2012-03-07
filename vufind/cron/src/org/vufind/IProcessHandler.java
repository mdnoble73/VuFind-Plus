package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;

public interface IProcessHandler {
	public void doCronProcess(Ini ini, Logger logger );
}
