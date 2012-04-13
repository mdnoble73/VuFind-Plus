package org.vufind;

public class AlphaBrowseInfo {
	private String value;
	private long numResults;
	
	public AlphaBrowseInfo(String value, long numResults){
		this.value = value;
		this.numResults = numResults;
	}
	
	public String getValue() {
		return value;
	}
	public void setValue(String value) {
		this.value = value;
	}
	public long getNumResults() {
		return numResults;
	}
	public void setNumResults(long numResults) {
		this.numResults = numResults;
	}
	
}
