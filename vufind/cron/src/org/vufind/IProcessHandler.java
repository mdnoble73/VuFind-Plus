package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

public interface IProcessHandler {
	public void doCronProcess(Ini configIni, Section processSettings, Logger logger );
}
