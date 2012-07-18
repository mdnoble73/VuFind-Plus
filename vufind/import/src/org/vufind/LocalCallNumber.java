package org.vufind;

public class LocalCallNumber {
	private long locationId;
	private long libraryId;
	private String callNumber;
	
	public LocalCallNumber(long locationId, long libraryId, String callNumber){
		this.locationId = locationId;
		this.libraryId = libraryId;
		this.callNumber = callNumber;
	}
	
	public String getCallNumber() {
		return callNumber;
	}
	public void setCallNumber(String callNumber) {
		this.callNumber = callNumber;
	}
	public int hashCode(){
		return new Long(locationId).hashCode() + callNumber.toLowerCase().hashCode();
	}
	@Override
	public boolean equals(Object arg0) {
		if (arg0 instanceof LocalCallNumber){
			LocalCallNumber lcn2 = (LocalCallNumber)arg0;
			return locationId == lcn2.locationId && lcn2.callNumber.equalsIgnoreCase(callNumber);
		}else{
			return false;
		}
	}

	public void setLocationId(long locationId) {
		this.locationId = locationId;
	}

	public long getLocationId() {
		return locationId;
	}

	public long getLibraryId() {
		return libraryId;
	}

	public void setLibraryId(long libraryId) {
		this.libraryId = libraryId;
	}
	
}
