package org.vufind;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;

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

	public void seteContentRecordsProcessed(int eContentRecordsProcessed) {
		this.eContentRecordsProcessed = eContentRecordsProcessed;
	}

	public String getProcessorName() {
		return processorName;
	}

	public int getRecordsProcessed() {
		return recordsProcessed;
	}
	public void setRecordsProcessed(int recordsProcessed) {
		this.recordsProcessed = recordsProcessed;
	}
	public int getNumErrors() {
		return numErrors;
	}
	public void setNumErrors(int numErrors) {
		this.numErrors = numErrors;
	}
	public int getNumAdded() {
		return numAdded;
	}
	public void setNumAdded(int numAdded) {
		this.numAdded = numAdded;
	}
	public int getNumUpdated() {
		return numUpdated;
	}
	public void setNumUpdated(int numUpdated) {
		this.numUpdated = numUpdated;
	}
	public int getNumDeleted() {
		return numDeleted;
	}
	public void setNumDeleted(int numDeleted) {
		this.numDeleted = numDeleted;
	}
	public int getNumSkipped() {
		return numSkipped;
	}

	public void setNumSkipped(int numSkipped) {
		this.numSkipped = numSkipped;
	}

	public ArrayList<String> getNotes() {
		return notes;
	}
	public void addNote(String note) {
		this.notes.add(note);
	}
	public int getEContentRecordsProcessed() {
		return eContentRecordsProcessed;
	}
	public void setEContentRecordsProcessed(int eContentRecordsProcessed) {
		this.eContentRecordsProcessed = eContentRecordsProcessed;
	}
	public int getResourcesProcessed() {
		return resourcesProcessed;
	}
	public void setResourcesProcessed(int resourcesProcessed) {
		this.resourcesProcessed = resourcesProcessed;
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

	public void incRecordsProcessed() {
		recordsProcessed++;
	}
	public void incEContentRecordsProcessed() {
		eContentRecordsProcessed++;
	}
	public void incResourcesProcessed() {
		resourcesProcessed++;
	}
	public void incErrors() {
		numErrors++;
	}
	public void incAdded() {
		numAdded++;
	}
	public void incUpdated() {
		numUpdated++;
	}
	public void incDeleted() {
		numDeleted++;
	}
	public void incSkipped() {
		numSkipped++;
	}

	public String getNotesHtml() {
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
	public void saveResults() {
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
			logger.info("Saved results for process " + getProcessorName());
		} catch (Exception e) {
			logger.error("Unable to save results of process to database", e);
		}
	}
}
