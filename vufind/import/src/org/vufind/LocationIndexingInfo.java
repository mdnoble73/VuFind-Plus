package org.vufind;

import java.util.regex.Pattern;

public class LocationIndexingInfo {
	private String facetLabel;
	private Long locationId;
	private Long libraryId;
	private String code;

	public boolean isSuppressHoldings() {
		return suppressHoldings;
	}

	public void setSuppressHoldings(boolean suppressHoldings) {
		this.suppressHoldings = suppressHoldings;
	}

	private boolean suppressHoldings;

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
	public Long getLocationId() {
		return locationId;
	}
	public void setLocationId(Long locationId) {
		this.locationId = locationId;
	}
	public boolean matchesExtraLocation(String locationCode) {
		return extraLocationPattern != null && extraLocationPattern.matcher(locationCode).matches();
	}

	private Pattern extraLocationPattern = null;
	public void setExtraLocationCodesToInclude(String extraLocationCodesToInclude) {
		if (extraLocationCodesToInclude.length() > 0){
			extraLocationPattern = Pattern.compile(extraLocationCodesToInclude);
		}else{
			extraLocationPattern = null;
		}
	}
	
}
