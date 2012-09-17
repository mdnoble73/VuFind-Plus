package org.econtent;

public class EcontentRecordInfo {
	private String ilsId;
	private String externalId;
	private long recordId;
	private String status;
	private int numItems;
	public String getIlsId() {
		return ilsId;
	}
	public void setIlsId(String ilsId) {
		this.ilsId = ilsId;
	}
	public long getRecordId() {
		return recordId;
	}
	public void setRecordId(long recordId) {
		this.recordId = recordId;
	}
	public String getStatus() {
		return status;
	}
	public void setStatus(String status) {
		this.status = status;
	}
	public int getNumItems() {
		return numItems;
	}
	public void setNumItems(int numItems) {
		this.numItems = numItems;
	}
	public String getExternalId() {
		return externalId;
	}
	public void setExternalId(String externalId) {
		this.externalId = externalId;
	}
}
