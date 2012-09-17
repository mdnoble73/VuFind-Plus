package org.econtent;

public class LibrarySpecificLink {
	private long librarySystemId;
	private String url;
	private int iType;
	private String notes;
	public LibrarySpecificLink(String url, long librarySystemId, int iType, String notes){
		this.url = url;
		this.librarySystemId = librarySystemId;
		this.iType = iType;
		this.notes = notes;
	}
	public long getLibrarySystemId() {
		return librarySystemId;
	}
	public String getUrl() {
		return url;
	}
	public int getiType() {
		return iType;
	}
	public String getNotes() {
		return notes;
	}
	
}
