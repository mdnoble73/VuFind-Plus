package org.econtent;

public class EcontentRecordInfo {
	private String ilsId;
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
}
