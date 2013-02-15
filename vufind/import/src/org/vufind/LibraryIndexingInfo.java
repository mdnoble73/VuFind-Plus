package org.vufind;

import java.util.HashMap;

public class LibraryIndexingInfo {
	private Long libraryId;
	private String subdomain;
	private boolean scoped;
	private String facetLabel;
	private String ilsCode;
	private HashMap<Long, LocationIndexingInfo> locations = new HashMap<Long, LocationIndexingInfo>();
	private LocationIndexingInfo defaultLocation;
	public Long getLibraryId() {
		return libraryId;
	}
	public void setLibraryId(Long libraryId) {
		this.libraryId = libraryId;
	}
	public String getSubdomain() {
		return subdomain;
	}
	public void setSubdomain(String subdomain) {
		this.subdomain = subdomain;
	}
	
	public boolean isScoped(){
		return scoped;
	}
	public void setScoped(boolean scoped){
		this.scoped = scoped;
	}
	public void addLocation(LocationIndexingInfo location) {
		this.locations.put(location.getLocationId(), location);
	}
	public HashMap<Long, LocationIndexingInfo> getLocations() {
		return locations;
	}
	public void setFacetLabel(String facetLabel) {
		this.facetLabel = facetLabel;
	}
	public String getFacetLabel() {
		return facetLabel;
	}
	public LocationIndexingInfo getLocationIndexingInfo(String locationCode) {
		for (LocationIndexingInfo locationInfo : locations.values()){
			if (locationCode.startsWith(locationInfo.getCode())){
				return locationInfo;
			}
		}
		if (defaultLocation != null && locationCode.equals(defaultLocation.getCode())){
			return defaultLocation;
		}
		return null;
	}
	public boolean hasCode(String curCode) {
		for (LocationIndexingInfo locationInfo : locations.values()){
			if (curCode.startsWith(locationInfo.getCode())){
				return true;
			}
		}
		if (defaultLocation != null && curCode.startsWith(defaultLocation.getCode())){
			return true;
		}
		return false;
	}
	public void setIlsCode(String ilsCode) {
		this.ilsCode = ilsCode;
		LocationIndexingInfo defaultIndexingInfo = new LocationIndexingInfo();
		defaultIndexingInfo.setCode(ilsCode);
		defaultIndexingInfo.setFacetLabel(facetLabel);
		defaultIndexingInfo.setLibraryId(libraryId);
		defaultIndexingInfo.setScoped(scoped);
		defaultLocation = defaultIndexingInfo;
	}
	public String getIlsCode() {
		return ilsCode;
	}
	
	
}
