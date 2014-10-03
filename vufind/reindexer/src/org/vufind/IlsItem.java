package org.vufind;

import java.util.HashSet;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/16/2014
 * Time: 9:17 AM
 */
public class IlsItem {
	protected String location;
	private String iType;

	private String dateCreated;
	private String callNumberPreStamp;
	private String callNumber;
	private String callNumberCutter;
	private String itemRecordNumber;
	private String collection;

	private HashSet<Scope> relatedScopes = new HashSet<Scope>();
	private String recordIdentifier;

	public String getDateCreated() {
		return dateCreated;
	}

	public void setDateCreated(String dateCreated) {
		this.dateCreated = dateCreated;
	}

	public String getLocation() {
		return location;
	}

	public void setLocation(String location) {
		this.location = location;
	}

	public String getiType() {
		return iType;
	}

	public void setiType(String iType) {
		this.iType = iType;
	}

	public String getCallNumberPreStamp() {
		return callNumberPreStamp;
	}

	public void setCallNumberPreStamp(String callNumberPreStamp) {
		this.callNumberPreStamp = callNumberPreStamp;
	}

	public String getCallNumber() {
		return callNumber;
	}

	public void setCallNumber(String callNumber) {
		this.callNumber = callNumber;
	}

	public String getCallNumberCutter() {
		return callNumberCutter;
	}

	public void setCallNumberCutter(String callNumberCutter) {
		this.callNumberCutter = callNumberCutter;
	}

	public String getFullCallNumber() {
		StringBuilder fullCallNumber = new StringBuilder();
		if (this.callNumberPreStamp != null) {
			fullCallNumber.append(this.callNumberPreStamp);
		}
		if (this.callNumber != null){
			fullCallNumber.append(this.callNumber);
		}
		if (this.callNumberCutter != null){
			fullCallNumber.append(this.callNumberCutter);
		}
		return fullCallNumber.toString().trim();
	}

	public String getItemRecordNumber() {
		return itemRecordNumber;
	}

	public void setItemRecordNumber(String itemRecordNumber) {
		this.itemRecordNumber = itemRecordNumber;
	}

	public HashSet<Scope> getRelatedScopes() {
		return relatedScopes;
	}

	public void setRelatedScopes(HashSet<Scope> relatedScopes) {
		this.relatedScopes = relatedScopes;
	}

	public void addRelatedScope(Scope scope){
		relatedScopes.add(scope);
	}

	public String getRecordIdentifier() {
		return recordIdentifier;
	}

	public void setRecordIdentifier(String recordIdentifier) {
		this.recordIdentifier = recordIdentifier;
	}

	public HashSet<String> getCompatiblePTypes() {
		HashSet<String> compatiblePTypes = new HashSet<String>();
		for (Scope scope : relatedScopes)       {
			compatiblePTypes.addAll(scope.getRelatedPTypes());
		}
		return compatiblePTypes;
	}

	public HashSet<String> getValidSubdomains() {
		HashSet<String> subdomains = new HashSet<String>();
		for (Scope curScope : relatedScopes){
			subdomains.add(curScope.getScopeName());
		}
		return subdomains;
	}

	public HashSet<String> getValidLibraryFacets() {
		HashSet<String> subdomains = new HashSet<String>();
		for (Scope curScope : relatedScopes){
			subdomains.add(curScope.getFacetLabel());
		}
		return subdomains;
	}

	public String getCollection() {
		return collection;
	}

	public void setCollection(String collection) {
		this.collection = collection;
	}
}
