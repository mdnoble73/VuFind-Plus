package org.strands;

import java.io.File;
import java.io.FileOutputStream;
import java.io.BufferedWriter;
import java.io.OutputStreamWriter;
import java.io.IOException;
import java.nio.charset.Charset;
import java.nio.charset.CharsetEncoder;
import java.nio.charset.CodingErrorAction;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.regex.Pattern;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.vufind.MarcRecordDetails;
import org.vufind.IEContentProcessor;
import org.vufind.IMarcRecordProcessor;
import org.vufind.IRecordProcessor;
import org.vufind.MarcProcessor;
import org.vufind.ProcessorResults;
import org.vufind.Util;

public class StrandsProcessor implements IMarcRecordProcessor, IEContentProcessor, IRecordProcessor {
	private Logger logger;
	private BufferedWriter writer;
	private String vufindUrl;
	private String bookcoverUrl;
	private PreparedStatement getFormatsForRecord = null;
	private File tempFile;
	private String strandsCatalogFile;
	private ProcessorResults results;
	
	/**
	 * Build a csv file to import into strands
	 */
	public boolean init(Ini configIni, String serverName, long reindexLogId, Connection vufindConn, Connection econtentConn, Logger logger) {
		this.logger = logger;
		results = new ProcessorResults("Strands Export", reindexLogId, vufindConn, logger);
		logger.info("Creating Catalog File for Strands");

		vufindUrl = configIni.get("Site", "url");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("VuFind URL not found in General Settings.  Please specify the url to vufind as the vufindUrl key");
			return false;
		}
		bookcoverUrl = configIni.get("Site", "coverUrl");
		if (bookcoverUrl == null || bookcoverUrl.length() == 0) {
			logger.error("Bookcover URL not found in General Settings.  Please specify the url to the bookcover server as the bookcoverUrl key");
			return false;
		}
		
		// Get the destination name where the catalog should be written to.
		strandsCatalogFile = configIni.get("Reindex", "strandsCatalogFile");
		if (strandsCatalogFile == null || strandsCatalogFile.length() == 0) {
			logger.error("Output File not found in GenerateCatalog Settings.  Please specify the path as the outputFile key.");
			return false;
		}
		
		//Connect to the eContent database
		try {
			//Connect to the vufind database
			getFormatsForRecord = econtentConn.prepareStatement("SELECT distinct item_type from econtent_item where recordId = ?");
			
		} catch (Exception ex) {
			// handle any errors
			logger.error("Error processing eContent ", ex);
			return false;
		}

		try {
			// Create a temporary file to write the XML to as it is generated
			tempFile = File.createTempFile("strands", "csv");
			CharsetEncoder utf8Encoder = Charset.forName("UTF8").newEncoder();
			utf8Encoder.onMalformedInput(CodingErrorAction.IGNORE);
			utf8Encoder.onUnmappableCharacter(CodingErrorAction.IGNORE);
			writer = new BufferedWriter(new OutputStreamWriter(new FileOutputStream(tempFile), utf8Encoder));

			// Create header for xml
			writer.write("id|link|title|author|image_link|publisher|description|genre|format|subject|audience|collection\r\n");

		} catch (Exception e) {
			logger.error("Error generating Strands catalog " + e.toString());
			e.printStackTrace();
			return false;
		}
		return true;
	}

	
	static final Pattern utf8Regex = Pattern.compile("([\\x00-\\x7F]|[\\xC0-\\xDF][\\x80-\\xBF]|[\\xE0-\\xEF][\\x80-\\xBF]{2}|[\\xF0-\\xF7][\\x80-\\xBF]{3})",
			Pattern.CANON_EQ);

	@Override
	public boolean processEContentRecord(ResultSet eContentRecord) {
		try {
			results.incEContentRecordsProcessed();
			// Write the id
			String id = eContentRecord.getString("id");
			logger.info("Processing eContentRecord " + id);
			writer.write("'econtentRecord" + id + "'");
			// Write a link to the title
			writer.write("|'" + vufindUrl + "/EContentRecord/" + id + "'");
			writer.write("|'" + Util.prepForCsv(eContentRecord.getString("title"), true, false) + "'");
			StringBuffer authors = new StringBuffer();
			authors.append(Util.prepForCsv(eContentRecord.getString("author"), true, false));
			String author2 = Util.prepForCsv(eContentRecord.getString("author2"), true,true);
			if (author2.length() > 0){
				if (authors.length() > 0){
					authors.append(";");
				}
				authors.append(author2);
			}
			writer.write("|'" + authors.toString() + "'");

			// Get the image link
			String isbn = eContentRecord.getString("isbn");
			String upc = eContentRecord.getString("upc");
			writer.write("|'" + bookcoverUrl + "/bookcover.php?isn=" + isbn + "&upc=" + upc + "&id=econtentRecord" + id + "&size=small&econtent=true'");

			// Get the publisher
			String publisher = eContentRecord.getString("publisher");
			writer.write("|'" + Util.prepForCsv(publisher, true, true) + "'");

			// Get the description
			writer.write("|'" + Util.prepForCsv(eContentRecord.getString("description"), false, true) + "'");

			// Get the genre
			writer.write("|'" + Util.prepForCsv(eContentRecord.getString("genre"), true, true) + "'");

			// Get the format
			StringBuffer formats = new StringBuffer();
			getFormatsForRecord.setString(1, id);
			ResultSet formatsRs = getFormatsForRecord.executeQuery();
			
			while (formatsRs.next()) {
				String format = formatsRs.getString(1);
				if (formats.length() > 0) {
					formats.append(";");
				}
				formats.append(Util.prepForCsv(format, true, true));
			}
			writer.write("|'" + formats.toString() + "'");

			// Get the subjects
			writer.write("|'" + Util.prepForCsv(eContentRecord.getString("subject"), true, true) + "'");

			// Get the audiences
			writer.write("|'" + Util.prepForCsv(eContentRecord.getString("target_audience"), true, true) + "'");

			// Get the format categories
			writer.write("|'EMedia'");

			writer.write("\r\n");
			results.incAdded();
			
			return true;
		} catch (Exception e) {
			results.incErrors();
			results.addNote("Error processing eContent record " + e.toString());
			logger.error("Error processing eContent record", e);
			return false;
		}
	}

	@Override
	public boolean processMarcRecord(MarcProcessor processor, MarcRecordDetails recordInfo, int recordStatus, Logger logger) {
		try {
			results.incRecordsProcessed();
			// Write the id
			writer.write("'" + recordInfo.getId() + "'");
			// Write a link to the title
			writer.write("|'" + vufindUrl + "/Record/" + recordInfo.getId() + "'");
			writer.write("|'" + Util.prepForCsv(recordInfo.getTitle(), true, false) + "'");
			StringBuffer authors = new StringBuffer();
			for (String author : recordInfo.getAuthors()) {
				if (authors.length() > 0) {
					authors.append(";");
				}
				authors.append(Util.prepForCsv(author, true, false));
			}
			writer.write("|'" + authors.toString() + "'");

			// Get the image link
			writer.write("|'" + bookcoverUrl + "/bookcover.php?isn=" + recordInfo.getIsbn() + "&upc=" + recordInfo.getFirstFieldValueInSet("upc") + "&id="
					+ recordInfo.getId() + "&amp;size=small'");

			// Get the publisher
			writer.write("|'" + Util.prepForCsv(recordInfo.getFirstFieldValueInSet("publisher"), true, false) + "'");

			// Get the description
			writer.write("|'" + Util.prepForCsv(recordInfo.getDescription(), false, false) + "'");

			// Get the genre
			String genres = Util.getSemiColonSeparatedString(recordInfo.getMappedField("genre"), true);
			writer.write("|'" + genres + "'");

			// Get the format
			String formats = Util.getSemiColonSeparatedString(recordInfo.getMappedField("format"), true);
			writer.write("|'" + formats.toString() + "'");

			// Get the subjects
			String subjects = Util.getSemiColonSeparatedString(recordInfo.getMappedField("topic"), true);
			writer.write("|'" + subjects.toString() + "'");

			// Get the audiences
			String audiences = Util.getSemiColonSeparatedString(recordInfo.getMappedField("target_audience"), true);
			writer.write("|'" + audiences.toString() + "'");

			// Get the format categories
			String categories = Util.getSemiColonSeparatedString(recordInfo.getMappedField("format_category"), true);
			writer.write("|'" + categories.toString() + "'");

			writer.write("\r\n");
			results.incAdded();
			
			return true;
		} catch (IOException e) {
			logger.error("Error writing to catalog file, " + e.toString());
			results.addNote("Error writing to catalog file, " + e.toString());
			return false;
		}
	}

	@Override
	public void finish() {
		try {
			writer.flush();
			writer.close();

			// Copy the temp file to the correct location so it can be picked up by
			// strands
			File outputFile = new File(strandsCatalogFile);
			if (outputFile.exists()) {
				outputFile.delete();
			}
			if (!tempFile.renameTo(outputFile)) {
				logger.error("Could not copy the temp file to the final output file.");
			} else {
				logger.info("Output file has been created as " + strandsCatalogFile);
			}
			
		} catch (IOException e) {
			results.addNote("Error saving strands catalog " + e.toString());
			logger.error("Error saving strands catalog", e);
		}
	}
	@Override
	public ProcessorResults getResults() {
		return results;
	}
}
