package org.vufind;

import java.io.File;
import java.util.Iterator;
import java.util.Set;

import javax.xml.parsers.FactoryConfigurationError;
import javax.xml.parsers.ParserConfigurationException;
import javax.xml.transform.TransformerException;

import org.apache.log4j.Logger;
import org.ini4j.Ini;

import com.jamesmurty.utils.XMLBuilder;

public class MarcIndexer implements IMarcRecordProcessor, IRecordProcessor {
	private String solrPort;
	private Logger logger;
	private String serverName;
	private boolean reindexUnchangedRecords;
	private ProcessorResults results = new ProcessorResults("Marc Indexer");
	
	@Override
	public boolean init(Ini configIni, String serverName, Logger logger) {
		this.logger = logger;
		this.serverName = serverName;
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
			Util.postToURL("http://localhost:" + solrPort + "/solr/biblio2/update/?commit=true", "<delete><query>recordtype:marc</query></delete>", logger);
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
		Util.postToURL("http://localhost:" + solrPort + "/solr/biblio2/update/", "<commit />", logger);
		Util.postToURL("http://localhost:" + solrPort + "/solr/biblio2/update/", "<optimize />", logger);
		
		if (checkMarcImport()){
			Util.postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=SWAP&core=biblio&other=biblio", null, logger);
		}
	}

	@Override
	public boolean processMarcRecord(MarcProcessor processor, MarcRecordDetails recordInfo, int recordStatus, Logger logger) {
		if (recordStatus == MarcProcessor.RECORD_UNCHANGED && !reindexUnchangedRecords){
			logger.info("Skipping record because it hasn't changed");
		}
		try {
			//Create the XML document for the record
			String xmlDoc = createXmlDocForRecord(recordInfo);
			//Post to the Solr instance
			Util.postToURL("http://localhost:" + solrPort + "/solr/biblio2/update/", xmlDoc, logger);
		} catch (Exception ex) {
			// handle any errors
			logger.error("Error indexing marc record " + recordInfo.getId() + " " + ex.toString());
			System.out.println(recordInfo.getTitle());
		}
		// TODO Auto-generated method stub
		return true;
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
			
			return builder.asString();
		} catch (ParserConfigurationException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		} catch (FactoryConfigurationError e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		} catch (TransformerException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}
		return null;
	}
	
	private boolean checkMarcImport() {
		return true;
	}

	@Override
	public ProcessorResults getResults() {
		return results;
	}
}
