package org.vufind;

public class OrderInfo {
	private String recordId;
	private String orderRecordId;
	private String status;
	private String locationCode;
	private int numCopies;
	public String getRecordId() {
		return recordId;
	}
	public void setRecordId(String recordId) {
		this.recordId = recordId;
	}
	public String getOrderRecordId() {
		return orderRecordId;
	}
	public void setOrderRecordId(String orderRecordId) {
		this.orderRecordId = orderRecordId;
	}
	
	public String getStatus() {
		return status;
	}
	public void setStatus(String status) {
		this.status = status;
	}
	public String getLocationCode() {
		return locationCode;
	}
	public void setLocationCode(String locationCode) {
		this.locationCode = locationCode;
	}

	public int getNumCopies() {
		return numCopies;
	}

	public void setNumCopies(int numCopies) {
		this.numCopies = numCopies;
	}
}
