package org.vufind;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;

import org.apache.log4j.Logger;

public class ProcessorResults {
	private Long resultsId = null;
	private String processorName;
	private int recordsProcessed;
	private int eContentRecordsProcessed;
	private int resourcesProcessed;
	private int numErrors;
	private int numAdded;
	private int numUpdated;
	private int numDeleted;
	private int numSkipped;
	private ArrayList<String> notes = new ArrayList<String>();
	private long reindexLogId;
	private Connection vufindConn;
	private Logger logger;
	
	private static PreparedStatement saveResultsStmt = null;
	private static PreparedStatement updateResultsStmt = null;
	
	private static SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
	
	public ProcessorResults(String processorName, long reindexLogId, Connection vufindConn, Logger logger){
		this.processorName = processorName;
		this.reindexLogId = reindexLogId;
		this.vufindConn = vufindConn;
		this.logger = logger;
		initStatements();
		saveResults();
	}
	
	private void initStatements(){
		if (saveResultsStmt == null){
			try {
				saveResultsStmt = vufindConn.prepareStatement("INSERT INTO reindex_process_log (reindex_id, processName, recordsProcessed, eContentRecordsProcessed, resourcesProcessed, numErrors, numAdded, numUpdated, numDeleted, numSkipped, notes ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ", PreparedStatement.RETURN_GENERATED_KEYS);
				updateResultsStmt = vufindConn.prepareStatement("UPDATE reindex_process_log SET recordsProcessed = ?, eContentRecordsProcessed = ?, resourcesProcessed = ?, numErrors = ?, numAdded = ?, numUpdated = ?, numDeleted = ?, numSkipped = ?, notes = ? WHERE id = ?");
			} catch (SQLException e) {
				logger.error("Error initializing statements to update results", e);
			}
		}
	}
	
	public int geteContentRecordsProcessed() {
		return eContentRecordsProcessed;
	}

	public String getProcessorName() {
		return processorName;
	}

	public int getRecordsProcessed() {
		return recordsProcessed;
	}
	public int getNumErrors() {
		return numErrors;
	}
	public int getNumAdded() {
		return numAdded;
	}
	public int getNumUpdated() {
		return numUpdated;
	}
	public int getNumDeleted() {
		return numDeleted;
	}
	public int getNumSkipped() {
		return numSkipped;
	}

	public ArrayList<String> getNotes() {
		return notes;
	}
	public synchronized void addNote(String note) {
		Date date = new Date();
		this.notes.add(dateFormat.format(date) + " - " + note);
	}
	public int getEContentRecordsProcessed() {
		return eContentRecordsProcessed;
	}
	public int getResourcesProcessed() {
		return resourcesProcessed;
	}
	public String toCsv(){
		return processorName + ", " 
				+ recordsProcessed + ", " 
				+ eContentRecordsProcessed + ", "
				+ resourcesProcessed + ", "
				+ numErrors + ", "
				+ numAdded + ", "
				+ numUpdated + ", "
				+ numDeleted + ", "
				+ numSkipped;
	}

	public synchronized void incRecordsProcessed() {
		recordsProcessed++;
	}
	public synchronized void incEContentRecordsProcessed() {
		eContentRecordsProcessed++;
	}
	public synchronized void incResourcesProcessed() {
		resourcesProcessed++;
	}
	public synchronized void incErrors() {
		numErrors++;
	}
	public synchronized void incAdded() {
		numAdded++;
	}
	public synchronized void incUpdated() {
		numUpdated++;
	}
	public synchronized void incDeleted() {
		numDeleted++;
	}
	public synchronized void incSkipped() {
		numSkipped++;
	}

	public synchronized String getNotesHtml() {
		StringBuffer notesText = new StringBuffer("<ol class='processNotes'>");
		for (String curNote : notes){
			String cleanedNote = curNote;
			cleanedNote = cleanedNote.replaceAll("<pre>", "<code>");
			cleanedNote = cleanedNote.replaceAll("</pre>", "</code>");
			//Replace multiple line breaks
			cleanedNote = cleanedNote.replaceAll("(?:<br?>\\s*)+", "<br/>");
			cleanedNote = cleanedNote.replaceAll("<meta.*?>", "");
			cleanedNote = cleanedNote.replaceAll("<title>.*?</title>", "");
			notesText.append("<li>").append(cleanedNote).append("</li>");
		}
		notesText.append("</ol>");
		if (notesText.length() > 64000){
			return notesText.substring(0, 64000);
		}else{
			return notesText.toString();
		}
	}
	
	/**
	 * Save the results of a process to the database for display to administrators later. 
	 * 
	 * @param results
	 */
	public synchronized void saveResults() {
		try {
			if (resultsId == null){
				saveResultsStmt.setLong(1, reindexLogId);
				saveResultsStmt.setString(2, getProcessorName());
				saveResultsStmt.setLong(3, getRecordsProcessed());
				saveResultsStmt.setLong(4, geteContentRecordsProcessed());
				saveResultsStmt.setLong(5, getResourcesProcessed());
				saveResultsStmt.setLong(6, getNumErrors());
				saveResultsStmt.setLong(7, getNumAdded());
				saveResultsStmt.setLong(8, getNumUpdated());
				saveResultsStmt.setLong(9, getNumDeleted());
				saveResultsStmt.setLong(10, getNumSkipped());
				saveResultsStmt.setString(11, getNotesHtml());
				saveResultsStmt.executeUpdate();
				ResultSet resultIdRS = saveResultsStmt.getGeneratedKeys();
				if (resultIdRS.next()){
					resultsId = resultIdRS.getLong(1); 
				}
			}else{
				updateResultsStmt.setLong(1, getRecordsProcessed());
				updateResultsStmt.setLong(2, geteContentRecordsProcessed());
				updateResultsStmt.setLong(3, getResourcesProcessed());
				updateResultsStmt.setLong(4, getNumErrors());
				updateResultsStmt.setLong(5, getNumAdded());
				updateResultsStmt.setLong(6, getNumUpdated());
				updateResultsStmt.setLong(7, getNumDeleted());
				updateResultsStmt.setLong(8, getNumSkipped());
				updateResultsStmt.setString(9, getNotesHtml());
				updateResultsStmt.setLong(10, resultsId);
				updateResultsStmt.executeUpdate();
			}
			//logger.info("Saved results for process " + getProcessorName());
		} catch (Exception e) {
			logger.error("Unable to save results of process to database", e);
		}
	}
}
