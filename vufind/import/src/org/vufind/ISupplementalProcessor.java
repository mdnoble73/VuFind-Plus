package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;

public interface ISupplementalProcessor {
	public boolean init(Ini configIni, Logger logger);
	public void finish();
}
