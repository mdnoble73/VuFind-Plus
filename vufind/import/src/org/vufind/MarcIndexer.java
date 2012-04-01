package org.vufind;

import java.io.ObjectInputStream.GetField;
import java.sql.ResultSet;
import java.sql.SQLException;
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
	
	@Override
	public boolean init(Ini configIni, Logger logger) {
		this.logger = logger;
		solrPort = configIni.get("Reindex", "solrPort");
		return true;
	}

	@Override
	public void finish() {
		//Make sure that the index is good and swap indexes
		Util.postToURL("http://localhost:" + solrPort + "/solr/biblio2/update/", "<commit />", logger);
		Util.postToURL("http://localhost:" + solrPort + "/solr/biblio2/update/", "<optimize />", logger);
	}

	@Override
	public boolean processMarcRecord(MarcProcessor processor, MarcRecordDetails recordInfo, int recordStatus, Logger logger) {
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
					doc.e("field").a("name", fieldName).t((String)fieldValue);
				}else{
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

}
