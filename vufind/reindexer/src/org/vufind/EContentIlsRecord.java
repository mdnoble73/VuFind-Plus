package org.vufind;

import java.util.Collection;
import java.util.HashSet;
import java.util.LinkedHashSet;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/3/2014
 * Time: 1:52 PM
 */
public class EContentIlsRecord {
	private String location;
	private String iType;
	private String protectionType;
	private String source;
	private String sharing;
	private String acsId;
	private String filename;
	private String numberOfCopies;
	private String url;

	private String dateCreated;
	private String callNumberPreStamp;
	private String callNumber;
	private String callNumberCutter;
	private String itemRecordNumber;

	//Data that is calculated from other data
	private boolean available;
	private HashSet<Scope> relatedScopes = new HashSet<Scope>();
	private HashSet<LocalizationInfo> relatedLocalizations = new HashSet<LocalizationInfo>();
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

	public String getProtectionType() {
		return protectionType;
	}

	public void setProtectionType(String protectionType) {
		this.protectionType = protectionType;
	}

	public String getSource() {
		return source;
	}

	public void setSource(String source) {
		this.source = source;
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

	public String getItemRecordNumber() {
		return itemRecordNumber;
	}

	public void setItemRecordNumber(String itemRecordNumber) {
		this.itemRecordNumber = itemRecordNumber;
	}

	public String getSharing() {
		return sharing;
	}

	public void setSharing(String sharing) {
		this.sharing = sharing;
	}

	public String getAcsId() {
		return acsId;
	}

	public void setAcsId(String acsId) {
		this.acsId = acsId;
	}

	public String getFilename() {
		return filename;
	}

	public void setFilename(String filename) {
		this.filename = filename;
	}

	public String getNumberOfCopies() {
		return numberOfCopies;
	}

	public void setNumberOfCopies(String numberOfCopies) {
		this.numberOfCopies = numberOfCopies;
	}

	public String getUrl() {
		return url;
	}

	public void setUrl(String url) {
		this.url = url;
	}

	public boolean isAvailable() {
		return available;
	}

	public void setAvailable(boolean available) {
		this.available = available;
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

	public HashSet<LocalizationInfo> getRelatedLocalizations() {
		return relatedLocalizations;
	}

	public void setRelatedLocalizations(HashSet<LocalizationInfo> relatedLocalizations) {
		this.relatedLocalizations = relatedLocalizations;
	}

	public HashSet<String> getCompatiblePTypes() {
		HashSet<String> compatiblePTypes = new HashSet<String>();
		for (Scope scope : relatedScopes)       {
			compatiblePTypes.addAll(scope.getRelatedPTypes());
		}
		return compatiblePTypes;
	}

	public String getRecordIdentifier() {
		return recordIdentifier;
	}

	public void setRecordIdentifier(String recordIdentifier) {
		this.recordIdentifier = recordIdentifier;
	}

	public HashSet<String> getValidSubdomains() {
		HashSet<String> subdomains = new HashSet<String>();
		for (Scope curScope : relatedScopes){
			subdomains.add(curScope.getScopeName());
		}
		return subdomains;
	}

	public HashSet<String> getValidLocations() {
		return new HashSet<String>();
	}

	public HashSet<String> getValidLibraryFacets() {
		HashSet<String> subdomains = new HashSet<String>();
		for (Scope curScope : relatedScopes){
			subdomains.add(curScope.getFacetLabel());
		}
		return subdomains;
	}
}
