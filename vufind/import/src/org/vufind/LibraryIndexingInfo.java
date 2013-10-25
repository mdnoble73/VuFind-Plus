package org.vufind;

import java.util.HashMap;
import java.util.LinkedHashSet;

public class LibraryIndexingInfo {
	private Long libraryId;
	private int accountingUnit;
	private String subdomain;
	private String facetLabel = "";
	private String ilsCode;
	private boolean makeOrderRecordsAvailableToOtherLibraries;
	private HashMap<Long, LocationIndexingInfo> locations = new HashMap<Long, LocationIndexingInfo>();
	private LocationIndexingInfo defaultLocation;

	public String getSubdomain() {
		return subdomain;
	}
	public void setSubdomain(String subdomain) {
		this.subdomain = subdomain;
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
	public LinkedHashSet<String> getExtraLocationIndexingInfo(String locationCode) {
		LinkedHashSet<String> extraLocations = new LinkedHashSet<String>();
		for (LocationIndexingInfo locationInfo : locations.values()){
			if (locationInfo.matchesExtraLocation(locationCode)){
				extraLocations.add(locationInfo.getFacetLabel());
			}
		}
		return extraLocations;
	}
	public void setIlsCode(String ilsCode) {
		this.ilsCode = ilsCode;
		LocationIndexingInfo defaultIndexingInfo = new LocationIndexingInfo();
		defaultIndexingInfo.setCode(ilsCode);
		defaultIndexingInfo.setFacetLabel(facetLabel);
		defaultIndexingInfo.setLibraryId(libraryId);
		defaultLocation = defaultIndexingInfo;
	}
	public String getIlsCode() {
		return ilsCode;
	}

	public void setLibraryId(Long libraryId) {
		this.libraryId = libraryId;
	}

	public int getAccountingUnit() {
		return accountingUnit;
	}

	public void setAccountingUnit(int accountingUnit) {
		this.accountingUnit = accountingUnit;
	}

	public boolean isMakeOrderRecordsAvailableToOtherLibraries() {
		return makeOrderRecordsAvailableToOtherLibraries;
	}

	public void setMakeOrderRecordsAvailableToOtherLibraries(boolean makeOrderRecordsAvailableToOtherLibraries) {
		this.makeOrderRecordsAvailableToOtherLibraries = makeOrderRecordsAvailableToOtherLibraries;
	}
}
