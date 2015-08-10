package org.vufind;

import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;

/**
 * Information about an Item that is being inserted into the index
 * Pika
 * User: Mark Noble
 * Date: 7/11/2015
 * Time: 12:07 AM
 */
public class ItemInfo {
	private String itemIdentifier;
	private String locationCode;
	private String subLocationCode;
	private String format;
	private String subFormat;
	private String formatCategory;
	private int numCopies = 1;
	private boolean isOrderItem;
	private boolean isEContent;
	private String shelfLocation;
	private String callNumber;
	private String sortableCallNumber;
	private Date dateAdded;
	private String IType;
	private String ITypeCode;
	private String eContentSource;
	private String eContentProtectionType;
	private String eContentFilename;
	private String eContentUrl;
	private String eContentSharing;
	private String statusCode;
	private String dueDate;
	private String collection;
	private RecordInfo recordInfo;

	private HashMap<String, ScopingInfo> scopingInfo = new HashMap<>();
	private String shelfLocationCode;

	public void setRecordInfo(RecordInfo recordInfo) {
		this.recordInfo = recordInfo;
	}

	public String getCollection() {
		return collection;
	}

	public void setCollection(String collection) {
		this.collection = collection;
	}

	public String getStatusCode() {
		return statusCode;
	}

	public void setStatusCode(String statusCode) {
		this.statusCode = statusCode;
	}

	public String getLocationCode() {
		return locationCode;
	}

	public void setLocationCode(String locationCode) {
		this.locationCode = locationCode;
	}

	public String getSubLocationCode() {
		return subLocationCode;
	}

	public void setSubLocationCode(String subLocationCode) {
		this.subLocationCode = subLocationCode;
	}

	public String geteContentUrl() {
		return eContentUrl;
	}

	public void seteContentUrl(String eContentUrl) {
		this.eContentUrl = eContentUrl;
	}

	public String geteContentSharing() {
		return eContentSharing;
	}

	public void seteContentSharing(String eContentSharing) {
		this.eContentSharing = eContentSharing;
	}

	public String geteContentFilename() {
		return eContentFilename;
	}

	public void seteContentFilename(String eContentFilename) {
		this.eContentFilename = eContentFilename;
	}

	public String getItemIdentifier() {
		return itemIdentifier;
	}

	public void setItemIdentifier(String itemIdentifier) {
		this.itemIdentifier = itemIdentifier;
	}

	public String getITypeCode() {
		return ITypeCode;
	}

	public void setITypeCode(String ITypeCode) {
		this.ITypeCode = ITypeCode;
	}

	public String getDueDate() {
		return dueDate;
	}

	public void setDueDate(String dueDate) {
		this.dueDate = dueDate;
	}

	public String getShelfLocation() {
		return shelfLocation;
	}

	public String getFormat() {
		return format;
	}

	public void setFormat(String format) {
		this.format = format;
	}

	public void setSubFormats(String subFormats){
		this.subFormat = subFormats;
	}

	public int getNumCopies() {
		//Deal with OverDrive always available
		if (numCopies > 1000){
			return 1;
		}else {
			return numCopies;
		}
	}

	public void setNumCopies(int numCopies) {
		this.numCopies = numCopies;
	}

	public boolean isOrderItem() {
		return isOrderItem;
	}

	public void setIsOrderItem(boolean isOrderItem) {
		this.isOrderItem = isOrderItem;
	}

	public boolean isEContent() {
		return isEContent;
	}

	public void setIsEContent(boolean isEContent) {
		this.isEContent = isEContent;
	}


	private String baseDetails = null;
	public String getDetails(){
		if (baseDetails == null){
			//Cache the part that doesn't change depending on the scope
			baseDetails = new StringBuilder().append(recordInfo.getFullIdentifier()).append("|")
					.append(getCleanDetailValue(itemIdentifier)).append("|")
					.append(getCleanDetailValue(shelfLocation)).append("|")
					.append(getCleanDetailValue(callNumber)).append("|")
					.append(getCleanDetailValue(format)).append("|")
					.append(getCleanDetailValue(formatCategory)).append("|")
					.append(numCopies).append("|")
					.append(isOrderItem).append("|")
					.append(isEContent).append("|")
					.append(getCleanDetailValue(eContentSource)).append("|")
					.append(getCleanDetailValue(eContentFilename)).append("|")
					.append(getCleanDetailValue(eContentUrl)).append("|")
					.append(getCleanDetailValue(subFormat)).append("|")
					.toString();
		}
		return baseDetails;
	}

	private String getCleanDetailValue(String value) {
		return value == null ? "" : value;
	}

	public Date getDateAdded() {
		return dateAdded;
	}

	public void setDateAdded(Date dateAdded) {
		this.dateAdded = dateAdded;
	}

	public String getIType() {
		return IType;
	}

	public void setIType(String IType) {
		this.IType = IType;
	}

	public String geteContentSource() {
		return eContentSource;
	}

	public void seteContentSource(String eContentSource) {
		this.eContentSource = eContentSource;
	}

	public String geteContentProtectionType() {
		return eContentProtectionType;
	}

	public void seteContentProtectionType(String eContentProtectionType) {
		this.eContentProtectionType = eContentProtectionType;
	}

	public String getCallNumber() {
		return callNumber;
	}

	public void setCallNumber(String callNumber) {
		this.callNumber = callNumber;
	}


	public String getSortableCallNumber() {
		return sortableCallNumber;
	}

	public void setSortableCallNumber(String sortableCallNumber) {
		this.sortableCallNumber = sortableCallNumber;
	}

	public String getFormatCategory() {
		return formatCategory;
	}

	public void setFormatCategory(String formatCategory) {
		this.formatCategory = formatCategory;
	}

	public void setShelfLocation(String shelfLocation) {
		this.shelfLocation = shelfLocation;
	}

	public ScopingInfo addScope(Scope scope) {
		ScopingInfo scopeInfo;
		if (scopingInfo.containsKey(scope.getScopeName())){
			scopeInfo = scopingInfo.get(scope.getScopeName());
		}else{
			scopeInfo = new ScopingInfo(scope, this);
			scopingInfo.put(scope.getScopeName(), scopeInfo);
		}
		return scopeInfo;
	}

	public HashMap<String, ScopingInfo> getScopingInfo() {
		return scopingInfo;
	}

	public HashSet<String> getAllOwningLibraries() {
		HashSet<String> owningLibraryValues = new HashSet<>();
		for (ScopingInfo curScope : scopingInfo.values()){
			if (curScope.isLibraryOwned() && curScope.getScope().isLibraryScope()) {
				if (isEContent) {
					owningLibraryValues.add(curScope.getScope().getFacetLabel() + " Online");
				}else{
					owningLibraryValues.add(curScope.getScope().getFacetLabel());
				}
			}else if (isEContent){
				//Show that the collection that this is part of
				owningLibraryValues.add(shelfLocation);
			}
		}
		return owningLibraryValues;
	}

	public HashSet<String> getAllOwningLocations() {
		HashSet<String> owningLibraryValues = new HashSet<>();
		for (ScopingInfo curScope : scopingInfo.values()){
			if (curScope.isLibraryOwned() && isEContent() && curScope.getScope().isLibraryScope()){
				owningLibraryValues.add(curScope.getScope().getFacetLabel() + " Online");
			}else if (curScope.isLocallyOwned() && curScope.getScope().isLocationScope()){
				owningLibraryValues.add(curScope.getScope().getFacetLabel());
			}else if (isEContent){
				//Show that the collection that this is part of
				owningLibraryValues.add(shelfLocation);
			}
		}
		return owningLibraryValues;
	}

	public boolean isValidForScope(Scope scope){
		return scopingInfo.containsKey(scope.getScopeName());
	}

	public boolean isValidForScope(String scopeName){
		return scopingInfo.containsKey(scopeName);
	}

	public boolean isAvailableInScope(Scope scope) {
		ScopingInfo scopeData = scopingInfo.get(scope.getScopeName());
		if (scopeData != null){
			if (scopeData.isAvailable()){
				return true;
			}
		}
		return false;
	}

	public boolean isLocallyAvailableInScope(Scope scope) {
		ScopingInfo scopeData = scopingInfo.get(scope.getScopeName());
		if (scopeData != null){
			if (scopeData.isLocallyOwned() && scopeData.isAvailable()){
				return true;
			}
		}
		return false;
	}

	public boolean isLocallyOwned(Scope scope) {
		ScopingInfo scopeData = scopingInfo.get(scope.getScopeName());
		if (scopeData != null){
			if (scopeData.isLocallyOwned()){
				return true;
			}
		}
		return false;
	}

	public boolean isLibraryOwned(Scope scope) {
		ScopingInfo scopeData = scopingInfo.get(scope.getScopeName());
		if (scopeData != null){
			if (scopeData.isLibraryOwned()){
				return true;
			}
		}
		return false;
	}

	public boolean isLocallyOwned(String scopeName) {
		ScopingInfo scopeData = scopingInfo.get(scopeName);
		if (scopeData != null){
			if (scopeData.isLocallyOwned()){
				return true;
			}
		}
		return false;
	}

	public boolean isLibraryOwned(String scopeName) {
		ScopingInfo scopeData = scopingInfo.get(scopeName);
		if (scopeData != null){
			if (scopeData.isLocallyOwned()){
				return true;
			}
		}
		return false;
	}

	public String getShelfLocationCode() {
		return shelfLocationCode;
	}

	public void setShelfLocationCode(String shelfLocationCode) {
		this.shelfLocationCode = shelfLocationCode;
	}

	public String getFullRecordIdentifier() {
		return recordInfo.getFullIdentifier();
	}
}
