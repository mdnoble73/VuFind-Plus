package org.econtent;

import java.io.BufferedWriter;
import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.OutputStreamWriter;
import java.nio.charset.Charset;
import java.nio.charset.CharsetEncoder;
import java.nio.charset.CodingErrorAction;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.Date;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.vufind.IEContentProcessor;
import org.vufind.IRecordProcessor;
import org.vufind.ProcessorResults;

public class GenerateOPDS implements IEContentProcessor, IRecordProcessor{
	private Logger logger;
	private BufferedWriter writer;
	private String econtentDBConnectionInfo;
	private Connection econtentConn = null;
	private String outputFileString;
	private SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'");
	private ProcessorResults results = new ProcessorResults("OPDS export");
	
	public boolean init(Ini configIni, Logger logger) {
		// Get the destination name where the catalog should be written to.
		outputFileString = processSettings.get("outputFile");
		if (outputFileString == null || outputFileString.length() == 0) {
			logger.error("Output File not found in GenerateCatalog Settings.  Please specify the path as the outputFile key.");
			return false;
		}
		return true;
	}
	public void temp(){
		try {
			//Connect to the eContent database
			econtentConn = DriverManager.getConnection(econtentDBConnectionInfo);
			
			//Create the opds feed
			File tempFile = File.createTempFile("opds", "xml");
			CharsetEncoder utf8Encoder = Charset.forName("UTF8").newEncoder();
			utf8Encoder.onMalformedInput(CodingErrorAction.REPLACE);
			utf8Encoder.onUnmappableCharacter(CodingErrorAction.REPLACE);
			writer = new BufferedWriter(new OutputStreamWriter(new FileOutputStream(tempFile), utf8Encoder));
			
			writeOpdsHeader(writer, logger);
			
			//Get a list of all records to be converted
			PreparedStatement getEContentRecords = econtentConn.prepareStatement("SELECT * FROM econtent_record where status='active'");
			ResultSet eContentRecords = getEContentRecords.executeQuery();
			eContentRecords.last();
			int numRows = eContentRecords.getRow();
			writer.write("  <opensearch:totalResults>" + numRows + "</opensearch:totalResults>\r\n");
			eContentRecords.beforeFirst();
			while (eContentRecords.next()){
				writeOPDSEntry(eContentRecords, logger);
			}
			writeOpdsFooter(writer, logger);
			
			writer.close();
			
			// Copy the temp file to the correct location so it can be picked up by
			// strands
			File outputFile = new File(outputFileString);
			if (outputFile.exists()) {
				outputFile.delete();
			}
			if (!tempFile.renameTo(outputFile)) {
				logger.error("Could not copy the temp file to the final output file.");
			} else {
				logger.info("Output file has been created as " + outputFileString);
			}
		} catch (Exception ex) {
			// handle any errors
			logger.error("Error geerating OPDS catalog ", ex);
			return;
		}
	}

	private void writeOPDSEntry(ResultSet eContentRecords, Logger logger) throws IOException, SQLException {
		String id = eContentRecords.getString("id");
		String author = eContentRecords.getString("author");
		long dateAdded = eContentRecords.getLong("date_added") * 1000;
		long dateUpdated;
		if (eContentRecords.getLong("date_updated") == 0){
			dateUpdated = dateAdded;
		}else{
			dateUpdated = eContentRecords.getLong("date_updated") * 1000;
		}
		String language = eContentRecords.getString("language");
		String description = eContentRecords.getString("description");
		writer.write("  <entry>\r\n");
		writer.write("    <title>" + eContentRecords.getString("title") + "</title>\r\n");
		writer.write("    <id>http://catalog.douglascountylibraries.org/EContentRecord/" + id + "</id>\r\n");
		if (author != null){
			writer.write("    <author>\r\n");
			writer.write("      <name>" + author + "</name>\r\n");
			writer.write("    </author>\r\n");
		}
		writer.write("    <published>" + dateFormat.format(new Date(dateAdded)) + "</published>\r\n");
		writer.write("    <updated>" + dateFormat.format(new Date(dateUpdated)) + "</updated>\r\n");
		if (language != null){
			writer.write("    <dcterms:language>" + language + "</dcterms:language>\r\n");
		}
		if (description != null && description.length() > 0){
			writer.write("    <summary>" + description + "</summary>\r\n");
		}
		writer.write("  </entry>\r\n");
	}

	private void writeOpdsFooter(BufferedWriter writer2, Logger logger) throws IOException {
		writer.write("</xml>\r\n");
	}

	private void writeOpdsHeader(BufferedWriter writer2, Logger logger) throws IOException {
		writer.write("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n");
		writer.write("<feed xmlns:dcterms=\"http://purl.org/dc/terms/\" xmlns:thr=\"http://purl.org/syndication/thread/1.0\" xmlns:opds=\"http://opds-spec.org/2010/catalog\" xml:lang=\"en\" xmlns:opensearch=\"http://a9.com/-/spec/opensearch/1.1/\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:app=\"http://www.w3.org/2007/app\" xmlns=\"http://www.w3.org/2005/Atom\">\r\n");
		writer.write("  <id>http://catalog.douglascountylibraries.org/opds.xml</id>\r\n");
		writer.write("  <title>Douglas County Library eContent</title>\r\n");
		writer.write("  <updated>" + dateFormat.format(new Date()) + "</updated>\r\n");
		writer.write("  <author>\r\n");
		writer.write("    <name>Douglas County Libraries</name>\r\n");
		writer.write("    <uri>http://catalog.douglascountylibraries.org</uri>\r\n");
		writer.write("    <email>answers@dclibraries.org</email>\r\n");
		writer.write("  </author>\r\n");
	}

	@Override
	public ProcessorResults getResults() {
		return results;
	}
}
