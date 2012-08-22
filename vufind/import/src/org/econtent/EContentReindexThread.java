package org.econtent;

import org.apache.log4j.Logger;
import org.vufind.URLPostResponse;
import org.vufind.Util;

public class EContentReindexThread extends Thread{
	private ExtractEContentFromMarc econtentProcessor;
	private long eContentRecordId;
	private Logger logger;
	public EContentReindexThread(ExtractEContentFromMarc econtentProcessor, long eContentRecordId, Logger logger){
		this.econtentProcessor = econtentProcessor;
		this.eContentRecordId = eContentRecordId;
		this.logger = logger;
	}
	@Override
	public void run() {
		try {
			URLPostResponse response = Util.getURL(econtentProcessor.getVufindUrl() + "/EcontentRecord/" + eContentRecordId + "/Reindex", logger);
			if (response.isSuccess()){
				logger.debug("Record indexed properly");
			}else{
				econtentProcessor.results.incErrors();
				econtentProcessor.results.addNote("Error reindexing eContent Record " + eContentRecordId + " " +  response.getMessage());
				econtentProcessor.results.saveResults();
			}
			logger.debug("Finished reindex " + econtentProcessor.getNumReindexingThreadsRunning());
			econtentProcessor.decrementReindexingThreadsRunning();
			logger.debug("Remove thread " + econtentProcessor.getNumReindexingThreadsRunning());
		} catch (Exception e) {
			econtentProcessor.decrementReindexingThreadsRunning();
			logger.info("Unable to reindex record " + eContentRecordId, e);
		}
	}
}
