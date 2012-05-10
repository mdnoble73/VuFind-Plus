package org.vufind;

import java.sql.Connection;
import java.util.Iterator;
import java.util.Set;

import org.apache.log4j.Logger;
import org.ini4j.Ini;

import com.jamesmurty.utils.XMLBuilder;

public class MarcIndexer implements IMarcRecordProcessor, IRecordProcessor {
	private String solrPort;
	private Logger logger;
	private boolean reindexUnchangedRecords;
	private ProcessorResults results;
	
	@Override
	public boolean init(Ini configIni, String serverName, long reindexLogId, Connection vufindConn, Connection econtentConn, Logger logger) {
		this.logger = logger;
		results = new ProcessorResults("Update Solr", reindexLogId, vufindConn, logger);
		solrPort = configIni.get("Reindex", "solrPort");

		//Check to see if we should clear the existing index
		String clearMarcRecordsAtStartOfIndexVal = configIni.get("Reindex", "clearMarcRecordsAtStartOfIndex");
		boolean clearMarcRecordsAtStartOfIndex;
		if (clearMarcRecordsAtStartOfIndexVal == null){
			clearMarcRecordsAtStartOfIndex = false;
		}else{
			clearMarcRecordsAtStartOfIndex = Boolean.parseBoolean(clearMarcRecordsAtStartOfIndexVal);
		}
		if (clearMarcRecordsAtStartOfIndex){
			logger.info("Clearing existing marc records from index");
			results.addNote("clearing existing marc records");
			URLPostResponse response = Util.postToURL("http://localhost:" + solrPort + "/solr/biblio2/update/?commit=true", "<delete><query>recordtype:marc</query></delete>", logger);
			if (!response.isSuccess()){
				results.addNote("Error clearing existing marc records " + response.getMessage());
			}
		}
		
		String reindexUnchangedRecordsVal = configIni.get("Reindex", "reindexUnchangedRecords");
		if (reindexUnchangedRecordsVal == null){
			reindexUnchangedRecords = true;
		}else{
			reindexUnchangedRecords = Boolean.parseBoolean(reindexUnchangedRecordsVal);
		}
		//Make sure that we don't skip unchanged records if we are clearing at the beginning
		if (clearMarcRecordsAtStartOfIndex) reindexUnchangedRecords = true;
		return true;
	}

	@Override
	public void finish() {
		//Make sure that the index is good and swap indexes
		results.addNote("calling final commit on index");
		URLPostResponse response = Util.postToURL("http://localhost:" + solrPort + "/solr/biblio2/update/", "<commit />", logger);
		if (!response.isSuccess()){
			results.addNote("Error committing changes " + response.getMessage());
		}
		results.addNote("optimizing index");
		response = Util.postToURL("http://localhost:" + solrPort + "/solr/biblio2/update/", "<optimize />", logger);
		if (!response.isSuccess()){
			results.addNote("Error optimizing index " + response.getMessage());
		}
		if (checkMarcImport()){
			results.addNote("index passed checks, swapping cores so new index is active.");
			response = Util.getURL("http://localhost:" + solrPort + "/solr/admin/cores?action=SWAP&core=biblio2&other=biblio", logger);
			if (!response.isSuccess()){
				results.addNote("Error swapping cores " + response.getMessage());
			}else{
				results.addNote("Result of swapping cores " + response.getMessage());
			}
		}else{
			results.addNote("index did not pass check, not swapping");
		}
		results.saveResults();
	}

	@Override
	public boolean processMarcRecord(MarcProcessor processor, MarcRecordDetails recordInfo, int recordStatus, Logger logger) {
		results.incRecordsProcessed();
		if (recordStatus == MarcProcessor.RECORD_UNCHANGED && !reindexUnchangedRecords){
			logger.info("Skipping record because it hasn't changed");
			results.incSkipped();
		}
		try {
			if (!recordInfo.isEContent()){
				//Create the XML document for the record
				String xmlDoc = createXmlDocForRecord(recordInfo);
				if (xmlDoc != null){
					//Post to the Solr instance
					URLPostResponse response = Util.postToURL("http://localhost:" + solrPort + "/solr/biblio2/update/", xmlDoc, logger);
					if (response.isSuccess()){
						if (recordStatus == MarcProcessor.RECORD_NEW){
							results.incAdded();
						}else{
							results.incUpdated();
						}
						return true;
					}else{
						results.incErrors();
						results.addNote(response.getMessage());
						return false;
					}
				}else{
					results.incErrors();
					return false;
				}
			}else{
				logger.info("Skipping record because it is eContent");
				results.incSkipped();
				return false;
			}
		} catch (Exception ex) {
			// handle any errors
			logger.error("Error indexing marc record " + recordInfo.getId() + " " + ex.toString());
			results.addNote("Error indexing marc record " + recordInfo.getId() + " " + ex.toString());
			results.incErrors();
			return false;
		}finally{
			if (results.getRecordsProcessed() % 100 == 0){
				results.saveResults();
			}
		}
	}

	private String createXmlDocForRecord(MarcRecordDetails recordInfo) {
		try {
			XMLBuilder builder = XMLBuilder.create("add");
			XMLBuilder doc = builder.e("doc");
			Iterator<String> keyIterator = recordInfo.getFields().keySet().iterator();
			while (keyIterator.hasNext()){
				String fieldName = keyIterator.next();
				Object fieldValue = recordInfo.getFields().get(fieldName);
				if (fieldValue instanceof String){
					if (fieldName.equals("fullrecord")){
						//doc.e("field").a("name", fieldName).cdata( ((String)fieldValue).getBytes() );
						//doc.e("field").a("name", fieldName).data( ((String)fieldValue).getBytes());
						doc.e("field").a("name", fieldName).data( Util.encodeSpecialCharacters((String)fieldValue).getBytes());
						System.out.println(Util.encodeSpecialCharacters((String)fieldValue));
					}else{
						doc.e("field").a("name", fieldName).t((String)fieldValue);
					}
				}else if (fieldValue instanceof Set){
					@SuppressWarnings("unchecked")
					Set<String> fieldValues = (Set<String>)fieldValue;
					Iterator<String> fieldValuesIter = fieldValues.iterator();
					while(fieldValuesIter.hasNext()){
						doc.e("field").a("name", fieldName).t(fieldValuesIter.next());
					}
				}
			}
			String recordXml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" + builder.asString();
			//logger.info("XML for " + recordInfo.getId() + "\r\n" + recordXml);
			return recordXml;
		} catch (Exception e) {
			results.addNote("Error creating xml doc for record " + recordInfo.getId() + " " + e.toString());
			e.printStackTrace();
			return null;
		}
	}
	
	private boolean checkMarcImport() {
		//Do not pass the import if more than 1% of the records have errors 
		if (results.getNumErrors() > results.getRecordsProcessed() * .01){
			return false;
		}else{
			return true;
		}
	}

	@Override
	public ProcessorResults getResults() {
		return results;
	}
}
