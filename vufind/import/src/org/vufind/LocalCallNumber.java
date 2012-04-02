package org.vufind;

public class LocalCallNumber {
	private String locationCode;
	private String callNumber;
	
	public LocalCallNumber(String locationCode, String callNumber){
		this.locationCode = locationCode.toLowerCase();
		this.callNumber = callNumber;
	}
	public String getLocationCode() {
		return locationCode;
	}
	public void setLocationCode(String locationCode) {
		this.locationCode = locationCode;
	}
	public String getCallNumber() {
		return callNumber;
	}
	public void setCallNumber(String callNumber) {
		this.callNumber = callNumber;
	}
	public int hashCode(){
		return locationCode.hashCode() + callNumber.toLowerCase().hashCode();
	}
	@Override
	public boolean equals(Object arg0) {
		if (arg0 instanceof LocalCallNumber){
			LocalCallNumber lcn2 = (LocalCallNumber)arg0;
			return lcn2.locationCode.equalsIgnoreCase(locationCode) && lcn2.callNumber.equalsIgnoreCase(callNumber);
		}else{
			return false;
		}
	}
	
}
