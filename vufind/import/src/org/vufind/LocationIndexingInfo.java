package org.vufind;

public class LocationIndexingInfo {
	private String facetLabel;
	private Long locationId;
	private Long libraryId;
	private String code;
	private boolean scoped;
	public String getFacetLabel() {
		return facetLabel;
	}
	public void setFacetLabel(String facetLabel) {
		this.facetLabel = facetLabel;
	}
	public Long getLibraryId() {
		return libraryId;
	}
	public void setLibraryId(Long libraryId) {
		this.libraryId = libraryId;
	}
	public String getCode() {
		return code;
	}
	public void setCode(String code) {
		this.code = code;
	}
	public boolean isScoped() {
		return scoped;
	}
	public void setScoped(boolean scoped) {
		this.scoped = scoped;
	}
	public Long getLocationId() {
		return locationId;
	}
	public void setLocationId(Long locationId) {
		this.locationId = locationId;
	}
	
}
