package org.econtent;

public class LibrarySpecificLink {
	private long librarySystemId;
	private String url;
	public LibrarySpecificLink(String url, long librarySystemId){
		this.url = url;
		this.librarySystemId = librarySystemId;
	}
	public long getLibrarySystemId() {
		return librarySystemId;
	}
	public String getUrl() {
		return url;
	}
	
}
