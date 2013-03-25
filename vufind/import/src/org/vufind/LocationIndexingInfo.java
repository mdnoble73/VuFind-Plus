package org.vufind;

import java.util.regex.Pattern;

public class LocationIndexingInfo {
	private String facetLabel;
	private String extraLocationCodesToInclude;
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
	public boolean matchesExtraLocation(String locationCode) {
		if (extraLocationPattern == null){
			return false;
		}else{
			return extraLocationPattern.matcher(locationCode).matches();
		}
	}
	public String getExtraLocationCodesToInclude() {
		return extraLocationCodesToInclude;
	}
	private Pattern extraLocationPattern = null;
	public void setExtraLocationCodesToInclude(String extraLocationCodesToInclude) {
		this.extraLocationCodesToInclude = extraLocationCodesToInclude;
		if (extraLocationCodesToInclude.length() > 0){
			extraLocationPattern = Pattern.compile(extraLocationCodesToInclude);
		}else{
			extraLocationPattern = null;
		}
	}
	
}
