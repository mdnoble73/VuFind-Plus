package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Profile.Section;

public class OptimizeCore implements IProcessHandler {

	@Override
	public void doCronProcess(Section processSettings, Section generalSettings, Logger logger) {
		//Get the url of the econtent core
		String baseUrl = processSettings.get("baseUrl");
		if (baseUrl == null || baseUrl.length() == 0) {
			logger.error("Unable to get baseUrl for core to be optimized in Process settings.  Please add a baseUrl key.");
			return;
		}
		
		//Optimize the solr core
		logger.info("Optimizing index " + baseUrl);
		String body = "<optimize/>";
		if (!Util.doSolrUpdate(baseUrl, body)){
			logger.error("Optimization Optimization Failed.");
		}
	}

}
