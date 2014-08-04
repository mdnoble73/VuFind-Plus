package org.vufind;

import java.util.regex.Pattern;

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
	private Pattern extraLocationCodesPattern;
	private String facetLabel;

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
		if (locationCode.startsWith(locationCodePrefix)){
			return true;
		}else{
			if (extraLocationCodesPattern != null){
				if (extraLocationCodesPattern.matcher(locationCode).matches()) {
					return true;
				}
			}
		}
		return false;
	}

	@Override
	public int compareTo(LocalizationInfo o) {
		return localName.compareTo(o.localName);
	}

	public void setExtraLocationCodes(String extraLocationCodesToInclude) {
		if (extraLocationCodesToInclude != null && extraLocationCodesToInclude.length() > 0) {
			this.extraLocationCodesPattern = Pattern.compile(extraLocationCodesToInclude);
		}
	}

	public String getFacetLabel() {
		return facetLabel;
	}

	public void setFacetLabel(String facetLabel) {
		this.facetLabel = facetLabel;
	}
}
