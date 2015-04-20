package org.vufind;

import java.util.HashMap;
import java.util.HashSet;
import java.util.regex.Pattern;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/2/2014
 * Time: 1:08 PM
 */
public class Scope implements Comparable<Scope>{
	private boolean isGlobalScope = true;
	private String scopeName;
	private String facetLabel;
	private HashSet<String> relatedPTypes = new HashSet<String>();
	private boolean includeBibsOwnedByTheLibraryOnly;
	private boolean includeItemsOwnedByTheLibraryOnly;
	private HashSet<String> eContentLocationCodesToInclude = new HashSet<String>();
	private boolean includeOutOfSystemExternalLinks;
	private String libraryLocationCodePrefix;
	private String locationLocationCodePrefix;
	private boolean includeBibsOwnedByTheLocationOnly;
	private boolean includeItemsOwnedByTheLocationOnly;
	private boolean includeOverDriveCollection;
	private Pattern extraLocationCodesPattern;
	private Long libraryId;
	private boolean isLibraryScope;
	private boolean isLocationScope;
	private boolean includeHoopla;

	public String getScopeName() {
		return scopeName;
	}

	public void setScopeName(String scopeName) {
		this.scopeName = scopeName;
	}

	public void setRelatedPTypes(String[] relatedPTypes) {
		for (String relatedPType : relatedPTypes) {
			relatedPType = relatedPType.trim();
			if (relatedPType.length() > 0) {
				this.relatedPTypes.add(relatedPType.trim());
			}
		}
		if (this.relatedPTypes.size() > 0 && !(this.relatedPTypes.contains("all") || this.relatedPTypes.contains("-1"))){
			isGlobalScope = false;
		}
	}

	public HashSet<String> getRelatedPTypes() {
		return relatedPTypes;
	}

	public void setIncludeBibsOwnedByTheLibraryOnly(boolean includeBibsOwnedByTheLibraryOnly) {
		this.includeBibsOwnedByTheLibraryOnly = includeBibsOwnedByTheLibraryOnly;
		if (includeBibsOwnedByTheLibraryOnly){
			isGlobalScope = false;
		}
	}

	public void setIncludeItemsOwnedByTheLibraryOnly(boolean includeItemsOwnedByTheLibraryOnly) {
		this.includeItemsOwnedByTheLibraryOnly = includeItemsOwnedByTheLibraryOnly;
		if (includeItemsOwnedByTheLibraryOnly){
			isGlobalScope = false;
		}
	}

	public void setEContentLocationCodesToInclude(String[] eContentLocationCodesToInclude) {
		for (String eContentLocationCodeToInclude : eContentLocationCodesToInclude) {
			this.eContentLocationCodesToInclude.add(eContentLocationCodeToInclude.toLowerCase().trim());
		}
		if (eContentLocationCodesToInclude.length > 0){
			isGlobalScope = false;
		}
	}

	public void setIncludeOutOfSystemExternalLinks(boolean includeOutOfSystemExternalLinks) {
		this.includeOutOfSystemExternalLinks = includeOutOfSystemExternalLinks;
	}

	public void setLibraryLocationCodePrefix(String libraryLocationCodePrefix) {
		this.libraryLocationCodePrefix = libraryLocationCodePrefix;
	}

	public void setFacetLabel(String facetLabel) {
		this.facetLabel = facetLabel;
	}

	public void setLocationLocationCodePrefix(String locationLocationCodePrefix) {
		this.locationLocationCodePrefix = locationLocationCodePrefix;
	}

	public void setIncludeBibsOwnedByTheLocationOnly(boolean includeBibsOwnedByTheLocationOnly) {
		this.includeBibsOwnedByTheLocationOnly = includeBibsOwnedByTheLocationOnly;
	}

	public void setIncludeItemsOwnedByTheLocationOnly(boolean includeItemsOwnedByTheLocationOnly) {
		this.includeItemsOwnedByTheLocationOnly = includeItemsOwnedByTheLocationOnly;
	}

	/**
	 * Determine if the item is part of the current scope based on location code and pType
	 *
	 * @param locationCode
	 * @param compatiblePTypes
	 * @return
	 */
	public boolean isItemPartOfScope(String locationCode, HashSet<String> compatiblePTypes){
		//If we're in the global scope, always include the record
		if (isGlobalScope){
			return true;
		}

		if (locationCode == null){
			//No location code, skip this item
			return false;
		}

		//First check based on location code
		//If the item is part of the extra location codes, we want to process that first.
		//Since it may not be included normally.
		if (extraLocationCodesPattern != null){
			if (extraLocationCodesPattern.matcher(locationCode).matches()) {
				return true;
			}
		}

		//Next look for exclusions if the library is using tight scoping.
		Pattern libraryCodePattern = Pattern.compile(libraryLocationCodePrefix);
		if (includeBibsOwnedByTheLibraryOnly && !libraryCodePattern.matcher(locationCode).lookingAt()){
			return false;
		}
		if (includeBibsOwnedByTheLocationOnly && !locationCode.startsWith(locationLocationCodePrefix)){
			return false;
		}

		//Make sure to include all items for the location regardless of holdability
		//Do need to make sure that the filter is active
		if (locationLocationCodePrefix != null){
			if (locationCode.startsWith(locationLocationCodePrefix)){
				return true;
			}
		}
		if (libraryLocationCodePrefix != null){
			if (locationCode.startsWith(libraryLocationCodePrefix)){
				return true;
			}
		}


		//If the item is holdable by anyone in the current scope it should be included.
		if (relatedPTypes.size() == 0 || relatedPTypes.contains("all") || relatedPTypes.contains("-1")){
			//Include all items regardless of if they are holdable or not.
			return true;
		}
		for (String pType : compatiblePTypes){
			if (pType.equals("all")){
				return true;
			}else if (relatedPTypes.contains(pType)){
				return true;
			}
		}
		//Not holdable, don't include in the scope
		return false;
	}

	public boolean isEContentLocationPartOfScope(EContentIlsItem ilsRecord) {
		String sharing = ilsRecord.getSharing();
		String locationCode = ilsRecord.getLocation().toLowerCase();
		if (ilsRecord.getProtectionType().endsWith("external") && includeOutOfSystemExternalLinks){
			return true;
		}else if ((sharing.equals("shared") || sharing.equals("library")) && libraryLocationCodePrefix.length() >0 && locationCode.startsWith(libraryLocationCodePrefix)){
			return true;
		}else if (locationLocationCodePrefix != null && locationLocationCodePrefix.length() > 0 && locationCode.startsWith(locationLocationCodePrefix)){
			return true;
		}else if (this.eContentLocationCodesToInclude.contains(locationCode)){
			return true;
		}
		return false;
	}

	public String getFacetLabel() {
		return facetLabel;
	}


	public boolean isIncludeOverDriveCollection() {
		return includeOverDriveCollection;
	}

	public void setIncludeOverDriveCollection(boolean includeOverDriveCollection) {
		this.includeOverDriveCollection = includeOverDriveCollection;
	}

	public void setLibraryId(Long libraryId) {
		this.libraryId = libraryId;
	}

	public Long getLibraryId() {
		return libraryId;
	}

	public boolean isIncludeOutOfSystemExternalLinks() {
		return includeOutOfSystemExternalLinks;
	}

	public void setExtraLocationCodes(String extraLocationCodesToInclude) {
		if (extraLocationCodesToInclude != null && extraLocationCodesToInclude.length() > 0) {
			this.extraLocationCodesPattern = Pattern.compile(extraLocationCodesToInclude, Pattern.CASE_INSENSITIVE);
		}
	}

	@Override
	public int compareTo(Scope o) {
		return scopeName.compareTo(o.scopeName);
	}

	HashMap<String, Boolean> locationCodeIncludedDirectly = new HashMap<String, Boolean>();
	public boolean isLocationCodeIncludedDirectly(String locationCode) {
		if (locationCodeIncludedDirectly.containsKey(locationCode)){
			return locationCodeIncludedDirectly.get(locationCode);
		}
		if (locationCode == null){
			return false;
		}
		if (locationLocationCodePrefix != null && locationCode.startsWith(locationLocationCodePrefix)){
			locationCodeIncludedDirectly.put(locationCode, Boolean.TRUE);
			return true;
		}

		if (isLibraryScope) {
			if (libraryLocationCodePrefix != null){
				Pattern libraryCodePattern = Pattern.compile(libraryLocationCodePrefix, Pattern.CASE_INSENSITIVE);
				if (libraryCodePattern.matcher(locationCode).lookingAt()) {
					locationCodeIncludedDirectly.put(locationCode, Boolean.TRUE);
					return true;
				}
			}
		}else{
			if (locationLocationCodePrefix != null) {
				Pattern locationCodePattern = Pattern.compile(locationLocationCodePrefix, Pattern.CASE_INSENSITIVE);
				if (locationCodePattern.matcher(locationCode).lookingAt()) {
					locationCodeIncludedDirectly.put(locationCode, Boolean.TRUE);
					return true;
				}
			}
		}
		if (extraLocationCodesPattern != null){
			if (extraLocationCodesPattern.matcher(locationCode).matches()) {
				locationCodeIncludedDirectly.put(locationCode, Boolean.TRUE);
				return true;
			}
		}
		if (isGlobalScope){
			locationCodeIncludedDirectly.put(locationCode, Boolean.TRUE);
			return true;
		}
		locationCodeIncludedDirectly.put(locationCode, Boolean.FALSE);
		return false;
	}

	public void setIsLibraryScope(boolean isLibraryScope) {
		this.isLibraryScope = isLibraryScope;
	}

	public boolean isLibraryScope() {
		return isLibraryScope;
	}

	public void setIsLocationScope(boolean isLocationScope) {
		this.isLocationScope = isLocationScope;
	}

	public boolean isLocationScope() {
		return isLocationScope;
	}

	public boolean isIncludeItemsOwnedByTheLibraryOnly() {
		return includeItemsOwnedByTheLibraryOnly;
	}

	public boolean isIncludeItemsOwnedByTheLocationOnly() {
		return includeItemsOwnedByTheLocationOnly;
	}

	public boolean isIncludeHoopla() {
		return includeHoopla;
	}

	public void setIncludeHoopla(boolean includeHoopla) {
		this.includeHoopla = includeHoopla;
	}

	public boolean isEContentDirectlyOwned(EContentIlsItem ilsEContentItem) {
		String sharing = ilsEContentItem.getSharing();
		String locationCode = ilsEContentItem.getLocation().toLowerCase();

		if ((sharing.equals("shared") || sharing.equals("library")) && libraryLocationCodePrefix.length() >0 && locationCode.startsWith(libraryLocationCodePrefix)){
			return true;
		}else if (locationLocationCodePrefix != null && locationLocationCodePrefix.length() > 0 && locationCode.startsWith(locationLocationCodePrefix)){
			return true;
		}else if (this.eContentLocationCodesToInclude.contains(locationCode)){
			return true;
		}
		return false;
	}
}
