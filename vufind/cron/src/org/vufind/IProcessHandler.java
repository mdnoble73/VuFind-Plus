package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Profile.Section;

public interface IProcessHandler {
	public void doCronProcess(Section processSettings, Section generalSettings, Logger logger );
}
