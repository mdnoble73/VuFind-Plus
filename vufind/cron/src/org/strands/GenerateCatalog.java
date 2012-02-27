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
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.HashSet;
import java.util.regex.Pattern;

import org.apache.log4j.Logger;
import org.ini4j.Profile.Section;
import org.vufind.BasicMarcInfo;
import org.vufind.IProcessHandler;
import org.vufind.MarcProcessorBase;

public class GenerateCatalog extends MarcProcessorBase implements IProcessHandler {

	private BufferedWriter writer;
	private String econtentDBConnectionInfo;
	private String vufindUrl;
	private String bookcoverUrl;
	private Connection econtentConn = null;
	private PreparedStatement getFormatsForRecord = null;

	@Override
	/**
	 * Build a csv file to import into strands
	 */
	public void doCronProcess(Section processSettings, Section generalSettings, Logger logger) {
		logger.info("Creating Catalog File for Strands");

		if (!loadConfig(processSettings, generalSettings, logger)) {
			return;
		}

		// Get the destination name where the catalog should be written to.
		String outputFileString = processSettings.get("outputFile");
		if (outputFileString == null || outputFileString.length() == 0) {
			logger.error("Output File not found in GenerateCatalog Settings.  Please specify the path as the outputFile key.");
			return;
		}

		try {
			// Create a temporary file to write the XML to as it is generated
			File tempFile = File.createTempFile("strands", "csv");
			CharsetEncoder utf8Encoder = Charset.forName("UTF8").newEncoder();
			utf8Encoder.onMalformedInput(CodingErrorAction.IGNORE);
			utf8Encoder.onUnmappableCharacter(CodingErrorAction.IGNORE);
			writer = new BufferedWriter(new OutputStreamWriter(new FileOutputStream(tempFile), utf8Encoder));

			// Create header for xml
			writer.write("id|link|title|author|image_link|publisher|description|genre|format|subject|audience|collection\r\n");
			if (!processMarcFiles(logger)) {
				logger.error("Unable to process marc files");
			}
			
			//Now load all eContent
			if (!processEContent(logger)){
				logger.error("Unable to process eContent");
			}

			writer.flush();
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

		} catch (Exception e) {
			logger.error("Error generating Strands catalog " + e.toString());
			e.printStackTrace();
			return;
		}

	}

	private boolean processEContent(Logger logger) {
		//Connect to the eContent database
		try {
			//Connect to the vufind database
			econtentConn = DriverManager.getConnection(econtentDBConnectionInfo);
			PreparedStatement allEcontentStmt = econtentConn.prepareStatement("SELECT * from econtent_record where status = 'active'");
			getFormatsForRecord = econtentConn.prepareStatement("SELECT distinct item_type from econtent_item where recordId = ?");
			ResultSet allEContent = allEcontentStmt.executeQuery();
			while (allEContent.next()){
				if (!processEContentRecord(allEContent, logger)){
					return false;
				}
			}
			
			econtentConn.close();
			return true;
		} catch (Exception ex) {
			// handle any errors
			logger.error("Error processing eContent ", ex);
			return false;
		}
	}
	
	protected boolean processEContentRecord(ResultSet eContentRecord, Logger logger) {
		try {
			// Write the id
			String id = eContentRecord.getString("id");
			logger.info("Processing eContentRecord " + id);
			writer.write("'econtentRecord" + id + "'");
			// Write a link to the title
			writer.write("|'" + vufindUrl + "EContentRecord/" + id + "'");
			writer.write("|'" + prepForCsv(eContentRecord.getString("title"), true, false) + "'");
			StringBuffer authors = new StringBuffer();
			authors.append(prepForCsv(eContentRecord.getString("author"), true, false));
			String author2 = prepForCsv(eContentRecord.getString("author2"), true,true);
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
			writer.write("|'" + bookcoverUrl + "bookcover.php?isn=" + isbn + "&amp;upc=" + upc + "&amp;id=econtentRecord" + id + "&amp;size=small&econtent=true'");

			// Get the publisher
			String publisher = eContentRecord.getString("publisher");
			writer.write("|'" + prepForCsv(publisher, true, true) + "'");

			// Get the description
			writer.write("|'" + prepForCsv(eContentRecord.getString("description"), false, true) + "'");

			// Get the genre
			writer.write("|'" + prepForCsv(eContentRecord.getString("genre"), true, true) + "'");

			// Get the format
			StringBuffer formats = new StringBuffer();
			getFormatsForRecord.setString(1, id);
			ResultSet formatsRs = getFormatsForRecord.executeQuery();
			
			while (formatsRs.next()) {
				String format = formatsRs.getString(1);
				if (formats.length() > 0) {
					formats.append(";");
				}
				formats.append(prepForCsv(format, true, true));
			}
			writer.write("|'" + formats.toString() + "'");

			// Get the subjects
			writer.write("|'" + prepForCsv(eContentRecord.getString("subject"), true, true) + "'");

			// Get the audiences
			writer.write("|'" + prepForCsv(eContentRecord.getString("target_audience"), true, true) + "'");

			// Get the format categories
			writer.write("|'EMedia'");

			writer.write("\r\n");
			
			return true;
		} catch (Exception e) {
			logger.error("Error processing eContent record", e);
			return false;
		}
	}

	protected boolean processMarcRecord(BasicMarcInfo recordInfo, Logger logger) {
		try {
			// Write the id
			writer.write("'" + recordInfo.getId() + "'");
			// Write a link to the title
			writer.write("|'" + vufindUrl + "Record/" + recordInfo.getId() + "'");
			writer.write("|'" + prepForCsv(recordInfo.getTitle(), true, false) + "'");
			StringBuffer authors = new StringBuffer();
			for (String author : recordInfo.getAuthors()) {
				if (authors.length() > 0) {
					authors.append(";");
				}
				authors.append(prepForCsv(author, true, false));
			}
			writer.write("|'" + authors.toString() + "'");

			// Get the image link
			writer.write("|'" + bookcoverUrl + "bookcover.php?isn=" + recordInfo.getIsbn() + "&amp;upc=" + recordInfo.getUpc() + "&amp;id="
					+ recordInfo.getId() + "&amp;size=small'");

			// Get the publisher
			writer.write("|'" + prepForCsv(recordInfo.getPublisher(), true, false) + "'");

			// Get the description
			writer.write("|'" + prepForCsv(recordInfo.getDescription(), false, false) + "'");

			// Get the genre
			StringBuffer genres = new StringBuffer();
			for (String genre : recordInfo.getGeneres()) {
				if (genre.length() > 0) {
					if (genres.length() > 0) {
						genres.append(";");
					}
					genres.append(prepForCsv(genre, true, false));
				}
			}
			writer.write("|'" + genres.toString() + "'");

			// Get the format
			StringBuffer formats = new StringBuffer();
			for (String collection : recordInfo.getCollections()) {
				String format = formatMap.get(collection);
				if (format != null) {
					if (formats.length() > 0) {
						formats.append(";");
					}
					formats.append(prepForCsv(format, true, false));
				} else {
					logger.error("Could not find a format for collection " + collection);
				}
			}
			writer.write("|'" + formats.toString() + "'");

			// Get the subjects
			StringBuffer subjects = new StringBuffer();
			for (String subject : recordInfo.getSubjects()) {
				if (subjects.length() > 0) {
					subjects.append(";");
				}
				subjects.append(prepForCsv(subject, true, false));
			}
			writer.write("|'" + subjects.toString() + "'");

			// Get the audiences
			HashSet<String> targetAudiences = recordInfo.getTargetAudience();
			StringBuffer audiences = new StringBuffer();
			for (String targetAudience : targetAudiences) {
				String audience = targetAudienceMap.get(targetAudience);
				if (audience != null) {
					if (audiences.length() > 0) {
						audiences.append(";");
					}
					audiences.append(prepForCsv(audience, true, false));
				}
			}
			writer.write("|'" + audiences.toString() + "'");

			// Get the format categories
			StringBuffer categories = new StringBuffer();
			for (String collection : recordInfo.getCollections()) {
				String category = formatCategoryMap.get(collection);
				if (category != null) {
					if (categories.length() > 0) {
						categories.append(";");
					}
					categories.append(prepForCsv(category, true, false));
				}
			}
			writer.write("|'" + categories.toString() + "'");

			writer.write("\r\n");
			
			return true;
		} catch (IOException e) {
			logger.error("Error writing to catalog file, " + e.toString());
			return false;
		}
	}

	static final Pattern utf8Regex = Pattern.compile("([\\x00-\\x7F]|[\\xC0-\\xDF][\\x80-\\xBF]|[\\xE0-\\xEF][\\x80-\\xBF]{2}|[\\xF0-\\xF7][\\x80-\\xBF]{3})",
			Pattern.CANON_EQ);

	private String prepForCsv(String input, boolean trimTrailingPunctuation, boolean crSeparatedFields) {
		if (input == null){
			return "";
		}
		if (trimTrailingPunctuation) {
			input = trimTrailingPunctuation(input);
		}
		input = input.replaceAll("'", "`");
		input = input.replaceAll("\\|", " ");
		input = input.replaceAll(";", " ");
		if (crSeparatedFields){
			input = input.replaceAll("[\\t]", " ");
			input = input.replaceAll("\\r\\n|\\r|\\n", ";");
		}else{
			input = input.replaceAll("[\\r\\n\\t]", " ");
		}
		
		// input = regex.matcher(input).replaceAll("");
		return input;
	}

	private String trimTrailingPunctuation(String format) {
		if (format == null){
			return "";
		}
		if (format.endsWith("/") || format.endsWith(",") || format.endsWith(".")) {
			format = format.substring(0, format.length() - 1);
		}
		return format.trim();
	}

	protected boolean loadConfig(Section processSettings, Section generalSettings, Logger logger) {
		if (!super.loadConfig(processSettings, generalSettings, logger)){
			return false;
		}
		
		econtentDBConnectionInfo = generalSettings.get("econtentDatabase");
		if (econtentDBConnectionInfo == null || econtentDBConnectionInfo.length() == 0) {
			logger.error("Database connection information for eContent database not found in General Settings.  Please specify connection information in a econtentDatabase key.");
			return false;
		}
		vufindUrl = generalSettings.get("vufindUrl");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("VuFind URL not found in General Settings.  Please specify the url to vufind as the vufindUrl key");
			return false;
		}
		bookcoverUrl = generalSettings.get("bookcoverUrl");
		if (bookcoverUrl == null || bookcoverUrl.length() == 0) {
			logger.error("Bookcover URL not found in General Settings.  Please specify the url to the bookcover server as the bookcoverUrl key");
			return false;
		}
		
		return true;
		
	}
}
