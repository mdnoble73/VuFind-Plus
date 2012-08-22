package org.vufind;

public class ResourceCallNumber {
	private Long libraryId;
	private Long locationId;
	private String callNumber;
	public ResourceCallNumber(String callNumber, long locationId, long libraryId) {
		this.callNumber = callNumber;
		this.libraryId = libraryId;
		this.locationId = locationId;
	}
	public Long getLibraryId() {
		return libraryId;
	}
	public void setLibraryId(Long libraryId) {
		this.libraryId = libraryId;
	}
	public Long getLocationId() {
		return locationId;
	}
	public void setLocationId(Long locationId) {
		this.locationId = locationId;
	}
	public String getCallNumber() {
		return callNumber;
	}
	public void setCallNumber(String callNumber) {
		this.callNumber = callNumber;
	}
	
}
