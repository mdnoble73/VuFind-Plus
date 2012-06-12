package org.vufind;

public class ACSResult {
	private boolean success;
	private String acsError;
	private String acsId;
	public boolean isSuccess() {
		return success;
	}
	public void setSuccess(boolean success) {
		this.success = success;
	}
	public String getAcsError() {
		return acsError;
	}
	public void setAcsError(String acsError) {
		this.acsError = acsError;
	}
	public String getAcsId() {
		return acsId;
	}
	public void setAcsId(String acsId) {
		this.acsId = acsId;
	}
}
