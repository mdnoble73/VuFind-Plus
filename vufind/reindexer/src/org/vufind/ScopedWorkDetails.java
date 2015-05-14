package org.vufind;

import java.util.Date;
import java.util.HashSet;
import java.util.Set;

/**
 * Contains information that is specific to a search scope.
 * Scoped work includes a selection of facets that vary depending on
 * which records are included as well as the actual records and items that
 * are included.
 *
 * VuFind-Plus
 * User: Mark Noble
 * Date: 5/30/2014
 * Time: 4:12 PM
 */
public class ScopedWorkDetails {
	private Scope scope;

	private boolean isLocallyOwned = false;
	private HashSet<String> formats = new HashSet<String>();
	private HashSet<String> formatCategories = new HashSet<String>();
	private Long formatBoost = 0L;
	private HashSet<String> relatedRecords = new HashSet<String>();
	private HashSet<String> relatedItems = new HashSet<String>();
	private HashSet<String> detailedLocations = new HashSet<String>();

	public HashSet<String> getRelatedRecords() {
		return relatedRecords;
	}

	public HashSet<String> getRelatedItems() {
		return relatedItems;
	}

	public void addRelatedOrderItem(String recordInfo, OnOrderItem orderItem) {
		String relatedItemDetails = orderItem.getRelatedItemInfo();

		StringBuilder fullItemDetails = new StringBuilder(recordInfo).append("~~").append(relatedItemDetails);
		//TODO: Determine if we need any additional details based on the scope (holdability, etc).
		this.relatedItems.add(fullItemDetails.toString());
	}

	public void addRelatedEContentItem(String recordInfo, EContentIlsItem econtentItem) {
		String relatedItemDetails = econtentItem.getRelatedItemInfo();

		StringBuilder fullItemDetails = new StringBuilder(recordInfo).append("~~").append(relatedItemDetails);
		fullItemDetails.append("~~").append(true)
				.append("|").append(true)
				.append("|").append(true);
		this.relatedItems.add(fullItemDetails.toString());
	}

	public void addRelatedItem(String recordInfo, IlsItem item) {
		String relatedItemDetails = item.getRelatedItemInfo();
		//Add additional information based on the scope
		//Check if the record is holdable
		boolean isHoldable = true;

		//Check if the record is local
		boolean isLocalItem = false;
		boolean isLibraryItem = false;
		if (scope.isGlobalScope()){
			isLocalItem = true;
			isLibraryItem = true;
		} else {
			if (scope.isLocationScope() && scope.isLocationCodeIncludedDirectly(item.getLibrarySystemCode(), item.getLocationCode())) {
				isLocalItem = true;
			}
			//Check if the record is owned by the library
			if (scope.isLibraryScope() && scope.isLocationCodeIncludedDirectly(item.getLibrarySystemCode(), item.getLocationCode())) {
				isLibraryItem = true;
			}
		}

		StringBuilder fullItemDetails = new StringBuilder(recordInfo).append("~~").append(relatedItemDetails);
		fullItemDetails.append("~~").append(isHoldable)
				.append("|").append(isLocalItem)
				.append("|").append(isLibraryItem);
		this.relatedItems.add(fullItemDetails.toString());
	}

	public ScopedWorkDetails(Scope curScope) {
		this.scope = curScope;
	}

	public HashSet<String> getFormats() {
		return formats;
	}

	public HashSet<String> getFormatCategories() {
		return formatCategories;
	}

	public void addFormat(String format){
		this.formats.add(format);
	}
	public void addFormatCategory(String formatCategory){
		this.formatCategories.add(formatCategory);
	}
	public void addFormatCategories(HashSet<String> formatCategories){
		this.formatCategories.addAll(formatCategories);
	}

	public Scope getScope() {
		return scope;
	}

	public void addFormat(Set<String> formats) {
		this.formats.addAll(formats);
	}

	public void setFormatBoost(Long formatBoost) {
		if (this.formatBoost == null) {
			this.formatBoost = formatBoost;
		} else {
			this.formatBoost += formatBoost;
		}
	}

	public void addRelatedRecord(String recordId, String primaryFormat, String edition, String language, String publisher, String publicationDate, String physicalDescription) {
		relatedRecords.add(recordId
						+ "|" + (primaryFormat == null ? "" : Util.trimTrailingPunctuation(primaryFormat.replace('|', ' ')))
						+ "|" + (edition == null ? "" : Util.trimTrailingPunctuation(edition.replace('|', ' ')))
						+ "|" + (language == null ? "" : Util.trimTrailingPunctuation(language.replace('|', ' ')))
						+ "|" + (publisher == null ? "" : Util.trimTrailingPunctuation(publisher.replace('|', ' ')))
						+ "|" + (publicationDate == null ? "" : Util.trimTrailingPunctuation(publicationDate.replace('|', ' ')))
						+ "|" + (physicalDescription == null ? "" : Util.trimTrailingPunctuation(physicalDescription.replace('|', ' ')))
		);
	}


	public void addDetailedLocation(String translatedDetailedLocation) {
		this.detailedLocations.add(translatedDetailedLocation);
	}

	public HashSet<String> getDetailedLocations(){
		return this.detailedLocations;
	}

	public boolean isLocallyOwned() {
		return isLocallyOwned;
	}

	public void setLocallyOwned(boolean isLocallyOwned) {
		this.isLocallyOwned = isLocallyOwned;
	}
}
