package org.blueink;

import java.io.InputStream;
import java.io.StringReader;
import java.net.URL;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;

import org.apache.commons.io.IOUtils;
import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import org.vufind.CronLogEntry;
import org.vufind.CronProcessLogEntry;
import org.vufind.IProcessHandler;
import org.vufind.Util;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;

public class ImportReviews implements IProcessHandler{
	private CronProcessLogEntry processLog ;
	private Logger logger;
	private String vufindUrl;
	private PreparedStatement checkForExistingReview;
	private PreparedStatement insertNewReview;
	private PreparedStatement updateExistingReview;
	private SimpleDateFormat dateFormatter = new SimpleDateFormat("MM/dd/yyyy");
	
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		this.logger = logger;
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Import blueink reviews");
		processLog.saveToDatabase(vufindConn, logger);
		
		vufindUrl = Util.cleanIniValue(configIni.get("Site", "url"));
		
		//Setup the Prepared Statements we will need
		try {
			checkForExistingReview = vufindConn.prepareStatement("SELECT editorialReviewId, review, pubDate FROM editorial_reviews WHERE recordId = ? AND source = 'BlueInk Reviews'");
			insertNewReview = vufindConn.prepareStatement("INSERT INTO editorial_reviews (recordId, title, pubDate, review, source) VALUES (?, ?, ?, ?, ?)");
			updateExistingReview = vufindConn.prepareStatement("UPDATE editorial_reviews SET title = ?, pubDate = ?, review = ? WHERE editorialReviewId = ?");
		} catch (SQLException e) {
			logger.error("Error creating prepared statements to update blueink reviews", e);
			processLog.addNote("Error creating prepared statements to update blueink reviews " + e.toString());
			processLog.incErrors();
		}
		
		
		//Load information from the reviews feed
		importReviewsFromFeed("http://www.blueinkreview.com/files/reviews.xml");
		
		//TODO: Load information from the archive feed?
		//Right now the reviews provided in each feed are identical so there is no need to import both. 
		//importReviewsFromFeed("http://www.blueinkreview.com/files/reviews.xml");
		
		processLog.setFinished();
		processLog.saveToDatabase(vufindConn, logger);
	}

	private void importReviewsFromFeed(String blueinkFeed) {
		//Get a list of all reviews from the blueink feed
		processLog.addNote("Loading reviews from " + blueinkFeed);
		try {
			URL reviewsURL = new URL(blueinkFeed);
			Object reviewsContent = reviewsURL.getContent();
			
			DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
			
			Document reviewsDoc;
			DocumentBuilder db = dbf.newDocumentBuilder();
			InputStream reviewsContentStream = (InputStream)reviewsContent;
			String reviewsContentString = IOUtils.toString(reviewsContentStream, "UTF-8");
			//logger.info(genreContentString);
			InputSource reviewsSource = new InputSource(new StringReader(reviewsContentString));
			reviewsDoc = db.parse(reviewsSource);
			NodeList reviews = reviewsDoc.getElementsByTagName("message");
			for(int i = 0; i < reviews.getLength(); i++){
				Node reviewNode = reviews.item(i);
				if (reviewNode instanceof Element){
					importReview((Element)reviewNode);
				}
			}
		} catch (Exception e) {
			logger.error("Error loading reviews from blueink", e);
			processLog.addNote("Error loading reviews from blueink " + e.toString());
			processLog.incErrors();
		}
	}

	private void importReview(Element reviewNode) {
		//Get the ISBN from the node
		String originalIsbn = reviewNode.getElementsByTagName("ISBN").item(0).getTextContent();
		//Clean up the isbn 
		String isbn = originalIsbn.replaceAll("\\W", "");
		isbn = isbn.trim();
		if (isbn.length() == 0){
			processLog.addNote("Skipping " + originalIsbn + " because it is not a valid ISBN");
			return;
		}
		//Get the actual review from the node
		String reviewText = reviewNode.getElementsByTagName("summary").item(0).getTextContent();
		
		//Get the actul review from the node
		String reviewTitle = reviewNode.getElementsByTagName("Title").item(0).getTextContent();
		
		String reviewDateStr = reviewNode.getElementsByTagName("ReviewDate").item(0).getTextContent();
		Date reviewDate = new Date();
		try {
			reviewDate = dateFormatter.parse(reviewDateStr);
		} catch (ParseException e1) {
			// TODO Auto-generated catch block
			logger.warn("Could not parse review date", e1);
		}
		
		logger.info("Processing review for " + isbn);
		
		//Check to see if we get a record for the isbn
		ArrayList<String> recordIds = getRecordIdsByIsbn(isbn);
		for(String recordId : recordIds){
			//Check to see if we have a review for the record Id already
			try {
				checkForExistingReview.setString(1, recordId);
				ResultSet existingReviewRS = checkForExistingReview.executeQuery();
				if (existingReviewRS.next()){
					Long existingId = existingReviewRS.getLong("editorialReviewId");
					String existingReviewText = existingReviewRS.getString("review");
					
					boolean needsUpdate = !existingReviewText.equals(reviewText);
					if (needsUpdate){
						logger.info("Updating existing Review");
						updateExistingReview.setString(1, reviewTitle);
						updateExistingReview.setLong(2, reviewDate.getTime());
						updateExistingReview.setString(3, reviewText);
						updateExistingReview.setLong(4, existingId);
						updateExistingReview.executeUpdate();
						processLog.incUpdated();
					}
				}else{
					logger.info("Adding new Review");
					//Add a new review
					insertNewReview.setString(1, recordId);
					insertNewReview.setString(2, reviewTitle);
					insertNewReview.setLong(3, reviewDate.getTime());
					insertNewReview.setString(4, reviewText);
					insertNewReview.setString(5, "BlueInk Reviews");
					insertNewReview.executeUpdate();
					processLog.incUpdated();
				}
			} catch (SQLException e) {
				logger.error("Error adding review from blueink for record " + recordId, e);
				processLog.addNote("Error adding review from blueink for record " + recordId + " " + e.toString());
				processLog.incErrors();
			}
		}
		
	}

	private ArrayList<String> getRecordIdsByIsbn(String isbn) {
		ArrayList<String> recordsForIsbn = new ArrayList<String>();
		URL searchUrl;
		try {
			searchUrl = new URL(vufindUrl + "/API/SearchAPI?method=search&lookfor=" + isbn + "&type=isn");
			Object searchDataRaw = searchUrl.getContent();
			if (searchDataRaw instanceof InputStream) {
				String searchDataJson = Util.convertStreamToString((InputStream) searchDataRaw);
				try {
					JSONObject searchData = new JSONObject(searchDataJson);
					JSONObject result = searchData.getJSONObject("result");
					int numRecords = result.getInt("recordCount");
					if (result.getInt("recordCount") > 0){
						//Found a record
						JSONArray recordSet = result.getJSONArray("recordSet");
						for (int i = 0; i < numRecords; i++){
							JSONObject firstRecord = recordSet.getJSONObject(i);
							String recordId = firstRecord.getString("id");
							recordsForIsbn.add(recordId);
						}
					}
				} catch (JSONException e) {
					logger.error("Unable to load search result", e);
					processLog.incErrors();
					processLog.addNote("Unable to load search result " + e.toString());
				}
			}
		} catch (Exception e) {
			logger.error("Error loading record Ids by ISBN", e);
		}
		return recordsForIsbn;
	}
	
	
}
