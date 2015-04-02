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

	public void addRelatedItem(String relatedItem) {
		this.relatedItems.add(relatedItem);
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
		} else if (formatBoost > this.formatBoost){
			this.formatBoost = formatBoost;
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
