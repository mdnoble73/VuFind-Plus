package org.vufind;

public class OrderRecord {
	private String recordId;
	private String orderRecordId;
	private String status;
	private String orderingLibrary;
	private String locationCode;
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
	public String getOrderingLibrary() {
		return orderingLibrary;
	}
	public void setOrderingLibrary(String orderingLibrary) {
		this.orderingLibrary = orderingLibrary;
	}
	public String getLocationCode() {
		return locationCode;
	}
	public void setLocationCode(String locationCode) {
		this.locationCode = locationCode;
	}
}
