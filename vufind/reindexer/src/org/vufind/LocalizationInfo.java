package org.vufind;

/**
 * Contains information about how to localize data
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/2/2014
 * Time: 3:36 PM
 */
public class LocalizationInfo implements Comparable<LocalizationInfo> {
	private String localName;
	private String locationCodePrefix;

	public String getLocationCodePrefix() {
		return locationCodePrefix;
	}

	public void setLocationCodePrefix(String locationCodePrefix) {
		this.locationCodePrefix = locationCodePrefix;
	}

	public String getLocalName() {
		return localName;
	}

	public void setLocalName(String localName) {
		this.localName = localName;
	}

	public boolean isLocationCodeIncluded(String locationCode){
		return locationCode.startsWith(locationCodePrefix);
	}

	@Override
	public int compareTo(LocalizationInfo o) {
		return localName.compareTo(o.localName);
	}
}
