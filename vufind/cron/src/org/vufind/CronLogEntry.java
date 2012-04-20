package org.vufind;

import java.util.ArrayList;
import java.util.Date;

public class CronLogEntry {
	private long logEntryId;
	private Date started;
	private Date lastUpdate; //The last time the log entry was updated so we can tell if a process is stuck 
	private Date finished;
	private ArrayList<String> notes = new ArrayList<String>();
	public CronLogEntry(){
		this.started = new Date();
	}
	public Date getLastUpdate() {
		lastUpdate = new Date();
		return lastUpdate;
	}
	public long getLogEntryId() {
		return logEntryId;
	}
	public void setLogEntryId(long logEntryId) {
		this.logEntryId = logEntryId;
	}
	
	public Date getStarted() {
		return started;
	}
	public void setStarted(Date started) {
		this.started = started;
	}
	public Date getFinished() {
		return finished;
	}
	public void setFinished(Date finished) {
		this.finished = finished;
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
			notesText.append("<li>").append(curNote).append("</li>");
		}
		notesText.append("</ol>");
		return notesText.toString();
	}
	
}
