package org.vufind;

import org.solrmarc.tools.StringNaturalCompare;

import java.util.HashSet;

/**
 * Contains local information about a work that is specific to the site
 * where VuFind+ is being used.
 *
 * It is important to have this information localized no matter what
 * scope is being used.
 *
 * Each library system will get it's own instance.
 *
 * Each location within the system will get it's own instance unless
 * the system has a single location since the information would be redundant.
 *
 *
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/2/2014
 * Time: 10:57 AM
 */
public class LocalizedWorkDetails {
	private LocalizationInfo localizationInfo;

	public HashSet<String> getDetailedLocations() {
		return detailedLocations;
	}

	public void setDetailedLocations(HashSet<String> detailedLocations) {
		this.detailedLocations = detailedLocations;
	}

	public HashSet<String> getAvailabilityToggle() {
		return availabilityToggle;
	}

	public void setAvailabilityToggle(HashSet<String> availabilityToggle) {
		this.availabilityToggle = availabilityToggle;
	}

	public String getCallNumberSort() {
		return callNumberSort;
	}

	public void setCallNumberSort(String callNumberSort) {
		this.callNumberSort = callNumberSort;
	}

	public HashSet<String> getLocalCallNumbers() {
		return localCallNumbers;
	}

	public void setLocalCallNumbers(HashSet<String> localCallNumbers) {
		this.localCallNumbers = localCallNumbers;
	}

	private HashSet<String> detailedLocations = new HashSet<String>();
	private HashSet<String> availabilityToggle = new HashSet<String>();
	private String callNumberSort;
	private HashSet<String> localCallNumbers = new HashSet<String>();

	public LocalizedWorkDetails(LocalizationInfo localizationInfo){
		this.localizationInfo = localizationInfo;
	}

	public LocalizationInfo getLocalizationInfo() {
		return localizationInfo;
	}

	public void setLocalizationInfo(LocalizationInfo localizationInfo) {
		this.localizationInfo = localizationInfo;
	}

	public void addDetailedLocation(String location){
		this.detailedLocations.add(location);
	}

}
