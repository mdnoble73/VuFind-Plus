package org.econtent;

import java.io.InputStream;
import java.net.URL;

import org.apache.log4j.Logger;
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
		// TODO Auto-generated method stub
		try {
			URL url = new URL(econtentProcessor.getVufindUrl() + "/EcontentRecord/" + eContentRecordId + "/Reindex");
			Object reindexResultRaw = url.getContent();
			if (reindexResultRaw instanceof InputStream) {
				String updateIndexResponse = Util.convertStreamToString((InputStream) reindexResultRaw);
				logger.info("Indexing record " + eContentRecordId + " response: " + updateIndexResponse);
			}
			logger.info("Finished reindex " + econtentProcessor.getNumReindexingThreadsRunning());
			econtentProcessor.decrementReindexingThreadsRunning();
			logger.info("Remove thread " + econtentProcessor.getNumReindexingThreadsRunning());
		} catch (Exception e) {
			econtentProcessor.decrementReindexingThreadsRunning();
			logger.info("Unable to reindex record " + eContentRecordId, e);
		}
	}
}
