package org.vufind;

import java.util.ArrayList;

public class ProcessorResults {
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
	
	public ProcessorResults(String processorName){
		this.processorName = processorName;
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
			cleanedNote.replaceAll("<pre>", "<code>");
			cleanedNote.replaceAll("</pre>", "</code>");
			//Replace multiple line breaks
			cleanedNote.replaceAll("(?:<br?>\\s*)+", "<br/>");
			cleanedNote.replaceAll("<meta.*?>", "");
			cleanedNote.replaceAll("<title>.*?</title>", "");
			notesText.append("<li>").append(curNote).append("</li>");
		}
		notesText.append("</ol>");
		return notesText.toString();
	}
}
