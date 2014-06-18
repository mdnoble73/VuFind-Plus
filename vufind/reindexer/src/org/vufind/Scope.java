package org.vufind;

import java.util.HashSet;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/2/2014
 * Time: 1:08 PM
 */
public class Scope implements Comparable<Scope>{
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
	private Long libraryId;

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
	}

	public HashSet<String> getRelatedPTypes() {
		return relatedPTypes;
	}

	public void setIncludeBibsOwnedByTheLibraryOnly(boolean includeBibsOwnedByTheLibraryOnly) {
		this.includeBibsOwnedByTheLibraryOnly = includeBibsOwnedByTheLibraryOnly;
	}

	public void setIncludeItemsOwnedByTheLibraryOnly(boolean includeItemsOwnedByTheLibraryOnly) {
		this.includeItemsOwnedByTheLibraryOnly = includeItemsOwnedByTheLibraryOnly;
	}

	public void setEContentLocationCodesToInclude(String[] eContentLocationCodesToInclude) {
		for (String eContentLocationCodeToInclude : eContentLocationCodesToInclude) {
			this.eContentLocationCodesToInclude.add(eContentLocationCodeToInclude.trim());
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

	public boolean isItemPartOfScope(String locationCode, HashSet<String> compatiblePTypes){
		if (includeBibsOwnedByTheLibraryOnly && !locationCode.startsWith(libraryLocationCodePrefix)){
			return false;
		}
		if (includeBibsOwnedByTheLocationOnly && !locationCode.startsWith(locationLocationCodePrefix)){
			return false;
		}
		//If the item is holdable by anyone in the current scope it should be included.
		if (relatedPTypes.size() == 0 || relatedPTypes.contains("all")){
			//Include all items regardless of if they are holdable or not.
			return true;
		}
		for (String pType : compatiblePTypes){
			if (relatedPTypes.contains(pType)){
				return true;
			}
		}
		//Not holdable, don't include in the scope
		return false;
	}

	public boolean isEContentLocationPartOfScope(EContentIlsItem ilsRecord) {
		String sharing = ilsRecord.getSharing();
		String locationCode = ilsRecord.getLocation();
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

	@Override
	public int compareTo(Scope o) {
		return scopeName.compareTo(o.scopeName);
	}
}
