package org.vufind;

import java.io.File;
import java.io.ObjectInputStream.GetField;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Date;
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
			moveBiblio2ToBiblio();
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
					doc.e("field").a("name", fieldName).t(Util.encodeSpecialCharacters((String)fieldValue));
				}else{
					Set<String> fieldValues = (Set<String>)fieldValue;
					Iterator<String> fieldValuesIter = fieldValues.iterator();
					while(fieldValuesIter.hasNext()){
						doc.e("field").a("name", fieldName).t(Util.encodeSpecialCharacters(fieldValuesIter.next()));
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
	
	private void moveBiblio2ToBiblio() {
		// 7) Unload the main index
		String unloadBiblio2Response = Util.postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=UNLOAD&core=biblio2", null, logger);
		logger.info("Response for unloading biblio 2 core " + unloadBiblio2Response);
		
		String unloadBiblioResponse = Util.postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=UNLOAD&core=biblio", null, logger);
		logger.info("Response for unloading biblio core " + unloadBiblioResponse);
		
		// 8) Copy files from the backup index to the main index
		// Remove all files from biblio (index, spellchecker, spellShingle)
		File biblioIndexDir = new File ("../../sites/" + serverName + "/solr/biblio/index");
		File biblio2IndexDir = new File ("../../sites/" + serverName + "/solr/biblio2/index");
		File biblioSpellcheckerDir = new File ("../../sites/" + serverName + "/solr/biblio/spellchecker");
		File biblio2SpellcheckerDir = new File ("../../sites/" + serverName + "/solr/biblio2/spellchecker");
		File biblioSpellShingleDir = new File ("../../sites/" + serverName + "/solr/biblio/spellShingle");
		File biblio2SpellShingleDir = new File ("../../sites/" + serverName + "/solr/biblio2/spellShingle");
		logger.info("Deleting directory " + biblioIndexDir.getAbsolutePath());
		if (!Util.deleteDirectory(biblioIndexDir)){
			logger.error("Could not delete directory " + biblioIndexDir);
		}
		if (!Util.deleteDirectory(biblioSpellcheckerDir)){
			logger.error("Could not delete directory " + biblioSpellcheckerDir);
		}
		if (!Util.deleteDirectory(biblioSpellShingleDir)){
			logger.error("Could not delete directory " + biblioSpellShingleDir);
		}
		Util.copyDir(biblio2IndexDir, biblioIndexDir);
		Util.copyDir(biblio2SpellcheckerDir, biblioSpellcheckerDir);
		Util.copyDir(biblio2SpellShingleDir, biblioSpellShingleDir);
		
		// 9) Reload the indexes
		String createBiblioResponse = Util.postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=CREATE&name=biblio&instanceDir=biblio", null, logger);
		logger.info("Response for creating biblio2 core " + createBiblioResponse);
		
		String createBiblio2Response = Util.postToURL("http://localhost:" + solrPort + "/solr/admin/cores?action=CREATE&name=biblio2&instanceDir=biblio2", null, logger);
		logger.info("Response for creating biblio2 core " + createBiblio2Response);
	}

	private boolean checkMarcImport() {
		return true;
	}

}
