package org.econtent;

import org.apache.log4j.Logger;
import org.vufind.MarcRecordDetails;

public class EContentAddItemsThread extends Thread {
	private ExtractEContentFromMarc econtentProcessor;
	private MarcRecordDetails recordInfo;
	private Logger logger;
	private String source;
	private DetectionSettings detectionSettings;
	private long eContentRecordId;

	public EContentAddItemsThread(ExtractEContentFromMarc econtentProcessor, MarcRecordDetails recordInfo, Logger logger, String source, DetectionSettings detectionSettings, long eContentRecordId) {
		this.econtentProcessor = econtentProcessor;
		this.recordInfo = recordInfo;
		this.logger = logger;
		this.source = source;
		this.detectionSettings = detectionSettings;
		this.eContentRecordId = eContentRecordId;
	}
	
	public void run(){
		if (source.equalsIgnoreCase("gutenberg")){
			econtentProcessor.attachGutenbergItems(recordInfo, eContentRecordId, logger);
		}else if (detectionSettings.getSource().equalsIgnoreCase("overdrive")){
			econtentProcessor.setupOverDriveItems(recordInfo, eContentRecordId, detectionSettings, logger);
		}else if (detectionSettings.isAdd856FieldsAsExternalLinks()){
			//Automatically setup 856 links as external links
			econtentProcessor.setupExternalLinks(recordInfo, eContentRecordId, detectionSettings, logger);
		}
		logger.info("Items added successfully.");
		econtentProcessor.decrementItemAttachmentThreadsRunning();
		econtentProcessor.reindexRecord(eContentRecordId, logger);
	}
}
