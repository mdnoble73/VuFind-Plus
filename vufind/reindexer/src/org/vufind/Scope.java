package org.vufind;

import com.sun.istack.internal.NotNull;

import java.util.HashSet;

/**
 * Description goes here
 * Pika
 * User: Mark Noble
 * Date: 6/2/2014
 * Time: 1:08 PM
 */
public class Scope implements Comparable<Scope>{
	private String scopeName;
	private String facetLabel;

	private HashSet<String> relatedPTypes = new HashSet<>();
	private HashSet<Long> relatedNumericPTypes = new HashSet<>();
	private boolean includeOverDriveCollection;
	private Long libraryId;

	//Determine if this is a library scope or location scope and store related information
	private boolean isLibraryScope;
	//If this is a library scope, we want to store pointers to the individual location scopes
	private HashSet<Scope> locationScopes = new HashSet<>();

	private boolean isLocationScope;
	private Scope libraryScope;

	private boolean restrictOwningLibraryAndLocationFacets;
	//Ownership rules indicate direct ownership of a record
	private HashSet<OwnershipRule> ownershipRules = new HashSet<>();
	//Inclusion rules indicate records owned by someone else that should be shown within the scope
	private HashSet<InclusionRule> inclusionRules = new HashSet<>();
	private String ilsCode;
	private boolean includeOverDriveAdultCollection;
	private boolean includeOverDriveTeenCollection;
	private boolean includeOverDriveKidsCollection;
	private int publicListsToInclude;

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
				this.relatedPTypes.add(relatedPType);
				try{
					Long numericPType = Long.parseLong(relatedPType);
					relatedNumericPTypes.add(numericPType);
				} catch (Exception e){
					//No need to do anything here.
				}

			}
		}
	}

	public HashSet<String> getRelatedPTypes() {
		return relatedPTypes;
	}

	public void setFacetLabel(String facetLabel) {
		this.facetLabel = facetLabel;
	}

	/**
	 * Determine if the item is part of the current scope based on location code and pType
	 *
	 *
	 * @param recordType        The type of record being checked based on profile
	 * @param locationCode      The location code for the item.  Set to blank if location codes
	 * @param subLocationCode   The sub location code to check.  Set to blank if no sub location code
	 * @return                  Whether or not the item is included within the scope
	 */
	public boolean isItemPartOfScope(@NotNull String recordType, @NotNull String locationCode, @NotNull String subLocationCode, boolean isHoldable, boolean isOnOrder, boolean isEContent){
		if (locationCode == null){
			//No location code, skip this item
			return false;
		}

		for(OwnershipRule curRule: ownershipRules){
			if (curRule.isItemOwned(recordType, locationCode, subLocationCode)){
				return true;
			}
		}

		for(InclusionRule curRule: inclusionRules){
			if (curRule.isItemIncluded(recordType, locationCode, subLocationCode, isHoldable, isOnOrder, isEContent)){
				return true;
			}
		}

		//If we got this far, it isn't included
		return false;
	}

	/**
	 * Determine if the item is part of the current scope based on location code and pType
	 *
	 *
	 * @param recordType        The type of record being checked based on profile
	 * @param locationCode      The location code for the item.  Set to blank if location codes
	 * @param subLocationCode   The sub location code to check.  Set to blank if no sub location code
	 * @return                  Whether or not the item is included within the scope
	 */
	public boolean isItemOwnedByScope(@NotNull String recordType, @NotNull String locationCode, @NotNull String subLocationCode){
		for(OwnershipRule curRule: ownershipRules){
			if (curRule.isItemOwned(recordType, locationCode, subLocationCode)){
				return true;
			}
		}

		//If we got this far, it isn't owned
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


	@Override
	public int compareTo(@NotNull Scope o) {
		return scopeName.compareTo(o.scopeName);
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

	public void addOwnershipRule(OwnershipRule ownershipRule) {
		ownershipRules.add(ownershipRule);
	}

	public void addInclusionRule(InclusionRule inclusionRule) {
		inclusionRules.add(inclusionRule);
	}

	public HashSet<Long> getRelatedNumericPTypes() {
		return relatedNumericPTypes;
	}

	public void addLocationScope(Scope locationScope) {
		this.locationScopes.add(locationScope);
	}

	public void setLibraryScope(Scope libraryScope) {
		this.libraryScope = libraryScope;
	}

	public Scope getLibraryScope() {
		return libraryScope;
	}

	public boolean isRestrictOwningLibraryAndLocationFacets() {
		return restrictOwningLibraryAndLocationFacets;
	}

	public void setRestrictOwningLibraryAndLocationFacets(boolean restrictOwningLibraryAndLocationFacets) {
		this.restrictOwningLibraryAndLocationFacets = restrictOwningLibraryAndLocationFacets;
	}

	public HashSet<Scope> getLocationScopes() {
		return locationScopes;
	}

	public String getIlsCode() {
		return ilsCode;
	}

	public void setIlsCode(String ilsCode) {
		this.ilsCode = ilsCode;
	}

	public void setIncludeOverDriveAdultCollection(boolean includeOverDriveAdultCollection) {
		this.includeOverDriveAdultCollection = includeOverDriveAdultCollection;
	}

	public boolean isIncludeOverDriveAdultCollection() {
		return includeOverDriveAdultCollection;
	}

	public void setIncludeOverDriveTeenCollection(boolean includeOverDriveTeenCollection) {
		this.includeOverDriveTeenCollection = includeOverDriveTeenCollection;
	}

	public boolean isIncludeOverDriveTeenCollection() {
		return includeOverDriveTeenCollection;
	}

	public void setIncludeOverDriveKidsCollection(boolean includeOverDriveKidsCollection) {
		this.includeOverDriveKidsCollection = includeOverDriveKidsCollection;
	}

	public boolean isIncludeOverDriveKidsCollection() {
		return includeOverDriveKidsCollection;
	}

	public void setPublicListsToInclude(int publicListsToInclude) {
		this.publicListsToInclude = publicListsToInclude;
	}

	public int getPublicListsToInclude() {
		return publicListsToInclude;
	}
}
