package org.marmot;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Date;

import org.apache.log4j.Logger;

public class OverDriveExtractLogEntry {
	private Long logEntryId = null;
	private Date startTime;
	private Date lastUpdate; //The last time the log entry was updated so we can tell if a process is stuck 
	private Date endTime;
	private ArrayList<String> notes = new ArrayList<String>();
	private int numProducts = 0;
	private int numErrors = 0;
	private int numAdded = 0;
	private int numDeleted = 0;
	private int numAvailabilityChanges = 0;
	private int numMetadataChanges = 0;
	private Connection econtentConn;
	private Logger logger;
	
	public OverDriveExtractLogEntry(Connection econtentConn, Logger logger){
		this.econtentConn = econtentConn;
		this.logger = logger;
		this.startTime = new Date();
		try {
			insertLogEntry = econtentConn.prepareStatement("INSERT into overdrive_extract_log (startTime) VALUES (?)", PreparedStatement.RETURN_GENERATED_KEYS);
			updateLogEntry = econtentConn.prepareStatement("UPDATE overdrive_extract_log SET lastUpdate = ?, endTime = ?, notes = ?, numProducts = ?, numErrors =? numAdded = ?, numDeleted = ?, numAvailabilityChanges = ?, numMetadataChanges = ? WHERE id = ?", PreparedStatement.RETURN_GENERATED_KEYS);
		} catch (SQLException e) {
			logger.error("Error creating prepared statements to update log", e);
		}
	}
	public Date getLastUpdate() {
		lastUpdate = new Date();
		return lastUpdate;
	}
	public Long getLogEntryId() {
		return logEntryId;
	}
	public void setLogEntryId(Long logEntryId) {
		this.logEntryId = logEntryId;
	}
	
	public ArrayList<String> getNotes() {
		return notes;
	}
	public void addNote(String note) {
		this.notes.add(note);
	}
	
	public String getNotesHtml() {
		StringBuffer notesText = new StringBuffer("<ol class='cronNotes'>");
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
			notesText.substring(0, 64000);
		}
		return notesText.toString();
	}
	
	private static PreparedStatement insertLogEntry;
	private static PreparedStatement updateLogEntry;
	public boolean saveResults() {
		try {
			if (logEntryId == null){
				insertLogEntry.setLong(1, startTime.getTime() / 1000);
				insertLogEntry.executeUpdate();
				ResultSet generatedKeys = insertLogEntry.getGeneratedKeys();
				if (generatedKeys.next()){
					logEntryId = generatedKeys.getLong(1);
				}
			}else{
				updateLogEntry.setLong(1, getLastUpdate().getTime() / 1000);
				if (endTime == null){
					updateLogEntry.setNull(2, java.sql.Types.INTEGER);
				}else{
					updateLogEntry.setLong(2, endTime.getTime() / 1000);
				}
				updateLogEntry.setString(3, getNotesHtml());
				updateLogEntry.setInt(4, numProducts);
				updateLogEntry.setInt(5, numErrors);
				updateLogEntry.setInt(6, numAdded);
				updateLogEntry.setInt(7, numDeleted);
				updateLogEntry.setInt(8, numAvailabilityChanges);
				updateLogEntry.setInt(9, numMetadataChanges);
				updateLogEntry.setLong(10, logEntryId);
				updateLogEntry.executeUpdate();
			}
			return true;
		} catch (SQLException e) {
			logger.error("Error creating prepared statements to update log", e);
			return false;
		}
	}
	public void setFinished() {
		this.endTime = new Date();
	}
	public void incProducts(){
		numProducts++;
	}
	public void incErrors(){
		numErrors++;
	}
	public void incAdded(){
		numAdded++;
	}
	public void incDeleted(){
		numDeleted++;
	}
	public void incAvailabilityChanges(){
		numAvailabilityChanges++;
	}
	public void incMetadataChanges(){
		numMetadataChanges++;
	}

}
