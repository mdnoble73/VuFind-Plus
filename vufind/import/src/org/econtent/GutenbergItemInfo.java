package org.econtent;

public class GutenbergItemInfo {
	private String sourceUrl;
	private String format;
	private String filename;
	private String folder;
	private String link;
	private String notes;
	public GutenbergItemInfo(String sourceUrl, String format, String filename, String folder, String notes){
		this.sourceUrl = sourceUrl;
		this.format = format;
		if (this.format.equals("externalMp3")){
			this.link = sourceUrl;
			this.filename = "";
			this.folder = "";
		}else{
			this.filename = filename;
			this.folder = folder;
			this.link = "";
		}
		this.notes = notes;
	}
	public String getSourceUrl() {
		return sourceUrl;
	}
	public String getFormat() {
		return format;
	}
	public String getFilename() {
		return filename;
	}
	public String getFolder() {
		return folder;
	}
	public String getNotes() {
		return notes;
	}
	public String getLink(){
		return link;
	}
	
}
