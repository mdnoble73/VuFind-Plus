package org.epub;

public class ImportResult {
	private String ISBN;
	private String baseFilename;
	private int volume;
	private String relatedRecord;
	private String coverImported = "";
	private String pdfImported = "";
	private String epubImported = "";
	private String notes = new String();
	private String acsId;

	public String getISBN() {
		return ISBN;
	}

	public void setISBN(String iSBN) {
		ISBN = iSBN;
	}

	public String getRelatedRecord() {
		return relatedRecord;
	}

	public void setRelatedRecord(String relatedRecord) {
		this.relatedRecord = relatedRecord;
	}

	public String getNotes() {
		return notes;
	}

	public void setNotes(String notes) {
		this.notes = notes;
	}

	public int getVolume() {
		return volume;
	}

	public void setVolume(int volume) {
		this.volume = volume;
	}

	public String getBaseFilename() {
		return baseFilename;
	}

	public void setBaseFilename(String baseFilename) {
		this.baseFilename = baseFilename;
	}

	public void addNote(String note) {
		if (notes.length() > 0) {
			notes += ", ";
		}
		notes += note;
	}

	public String getCoverImported() {
		return coverImported;
	}

	public void setCoverImported(String coverImported) {
		this.coverImported = coverImported;
	}

	public String getPdfImported() {
		return pdfImported;
	}

	public void setPdfImported(String pdfImported) {
		this.pdfImported = pdfImported;
	}

	public String getEpubImported() {
		return epubImported;
	}

	public void setEpubImported(String epubImported) {
		this.epubImported = epubImported;
	}

	public String getAcsId() {
		return acsId;
	}

	public void setAcsId(String acsId) {
		this.acsId = acsId;
	}

	public void setStatus(String type, String status, String note){
		if (type.equals("pdf")){
			setPdfImported(status);
		}else{
			setEpubImported(status);
		}
		if (note != null && note.length() > 0){
			addNote(note);
		}
	}

	public Object getSatus(String type) {
		if (type.equals("pdf")){
			return pdfImported;
		}else{
			return epubImported;
		}
	}

}
