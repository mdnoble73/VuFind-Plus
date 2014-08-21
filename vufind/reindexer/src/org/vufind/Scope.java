package org.vufind;

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
	private Long accountingUnit;

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
		if (extraLocationCodesPattern != null){
			if (extraLocationCodesPattern.matcher(locationCode).matches()) {
				return true;
			}
		}
		if (includeBibsOwnedByTheLibraryOnly && !locationCode.startsWith(libraryLocationCodePrefix)){
			return false;
		}
		if (includeBibsOwnedByTheLocationOnly && !locationCode.startsWith(locationLocationCodePrefix)){
			return false;
		}

		//Make sure to include all items for the location regardless of holdability
		if (includeBibsOwnedByTheLocationOnly){
			if (locationCode.startsWith(locationLocationCodePrefix)){
				return true;
			}
		}else if (includeBibsOwnedByTheLibraryOnly){
			if (locationCode.startsWith(libraryLocationCodePrefix)){
				return true;
			}
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

	public void setExtraLocationCodes(String extraLocationCodesToInclude) {
		if (extraLocationCodesToInclude != null && extraLocationCodesToInclude.length() > 0) {
			this.extraLocationCodesPattern = Pattern.compile(extraLocationCodesToInclude);
		}
	}

	@Override
	public int compareTo(Scope o) {
		return scopeName.compareTo(o.scopeName);
	}

	public Long getAccountingUnit() {
		return accountingUnit;
	}

	public void setAccountingUnit(Long accountingUnit) {
		this.accountingUnit = accountingUnit;
	}
}
