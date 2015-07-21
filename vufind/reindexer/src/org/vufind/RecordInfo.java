package org.vufind;

import java.util.Collection;
import java.util.HashMap;
import java.util.HashSet;

/**
 * Information about a Record within the system
 *
 * Pika
 * User: Mark Noble
 * Date: 7/11/2015
 * Time: 12:05 AM
 */
public class RecordInfo {
	private String source;
	private String subSource;
	private String recordIdentifier;

	//Formats exist at both the item and record level because
	//Various systems define them in both ways.
	private String primaryFormat;
	private HashSet<String> formats = new HashSet<String>();
	private HashSet<String> formatCategories = new HashSet<String>();
	private long formatBoost = 1;

	private String edition;
	private String primaryLanguage;
	private String publisher;
	private String publicationDate;
	private String physicalDescription;

	private HashSet<ItemInfo> relatedItems = new HashSet<>();
	public RecordInfo(String source, String recordIdentifier){
		this.source = source;
		this.recordIdentifier = recordIdentifier;
	}

	public void setSubSource(String subSource) {
		this.subSource = subSource;
	}

	public long getFormatBoost() {
		return formatBoost;
	}

	public void setFormatBoost(long formatBoost) {
		if (formatBoost > this.formatBoost) {
			this.formatBoost = formatBoost;
		}
	}

	public void setPrimaryFormat(String primaryFormat) {
		this.primaryFormat = primaryFormat;
	}

	public void setEdition(String edition) {
		this.edition = edition;
	}

	public void setPrimaryLanguage(String primaryLanguage) {
		this.primaryLanguage = primaryLanguage;
	}

	public void setPublisher(String publisher) {
		this.publisher = publisher;
	}

	public void setPublicationDate(String publicationDate) {
		this.publicationDate = publicationDate;
	}

	public void setPhysicalDescription(String physicalDescription) {
		this.physicalDescription = physicalDescription;
	}

	public HashSet<ItemInfo> getRelatedItems() {
		return relatedItems;
	}

	public void setRecordIdentifier(String source, String recordIdentifier) {
		this.source = source;
		this.recordIdentifier = recordIdentifier;
	}

	public String getRecordIdentifier() {
		return recordIdentifier;
	}

	public String getDetails() {
		return source + ":" + recordIdentifier + "|" +
				getPrimaryFormat() + "|" +
				(edition == null ? "" : edition) + "|" +
				primaryLanguage + "|" +
				publisher + "|" +
				publicationDate + "|" +
				physicalDescription
			;
	}

	protected String getPrimaryFormat() {
		HashMap<String, Integer> relatedFormats = new HashMap<>();
		for (String format : formats){
			relatedFormats.put(format, 1);
		}
		for (ItemInfo curItem : relatedItems){
			if (curItem.getFormat() != null) {
				if (relatedFormats.containsKey(curItem.getFormat())) {
					relatedFormats.put(curItem.getFormat(), relatedFormats.get(curItem.getFormat()));
				} else {
					relatedFormats.put(curItem.getFormat(), 1);
				}
			}
		}
		int timesUsed = 0;
		String mostUsedFormat = null;
		for (String curFormat : relatedFormats.keySet()){
			if (relatedFormats.get(curFormat) > timesUsed){
				mostUsedFormat = curFormat;
				timesUsed = relatedFormats.get(curFormat);
			}
		}
		if (mostUsedFormat == null){
			return "Unknown";
		}
		return mostUsedFormat;
	}

	public void addItem(ItemInfo itemInfo) {
		relatedItems.add(itemInfo);
		itemInfo.setRecordInfo(this);
	}

	public HashSet<String> getAllOwningLibraries() {
		HashSet<String> owningLibraryValues = new HashSet<>();
		for (ItemInfo curItem : relatedItems){
			owningLibraryValues.addAll(curItem.getAllOwningLibraries());
		}
		return owningLibraryValues;
	}

	public HashSet<String> getAllITypes() {
		HashSet<String> values = new HashSet<>();
		for (ItemInfo curItem : relatedItems){
			values.add(curItem.getIType());
		}
		return values;
	}

	public HashSet<String> getAllFormats() {
		HashSet<String> values = new HashSet<>();
		values.addAll(formats);
		for (ItemInfo curItem : relatedItems){
			values.add(curItem.getFormat());
		}
		return values;
	}

	public HashSet<String> getAllFormatCategories() {
		HashSet<String> values = new HashSet<>();
		values.addAll(formatCategories);
		for (ItemInfo curItem : relatedItems){
			values.add(curItem.getFormatCategory());
		}
		return values;
	}

	public HashSet<String> getAllOwningLocations() {
		HashSet<String> owningLibraryValues = new HashSet<>();
		for (ItemInfo curItem : relatedItems){
			owningLibraryValues.addAll(curItem.getAllOwningLocations());
		}
		return owningLibraryValues;
	}

	public boolean isValidForScope(Scope scope) {
		for (ItemInfo curItem : relatedItems){
			if (curItem.isValidForScope(scope)){
				return true;
			}
		}
		return false;
	}

	public HashSet<ItemInfo> getRelatedItemsForScope(Scope scope) {
		HashSet<ItemInfo> values = new HashSet<>();
		for (ItemInfo curItem : relatedItems){
			if (curItem.isValidForScope(scope)){
				values.add(curItem);
			}
		}
		return values;
	}

	public int getNumCopiesOnOrder() {
		int numOrders = 0;
		for (ItemInfo curItem : relatedItems){
			if (curItem.isOrderItem()){
				numOrders += curItem.getNumCopies();
			}
		}
		return numOrders;
	}

	public String getFullIdentifier() {
		return source + ":" + recordIdentifier;
	}

	public int getNumPrintCopies() {
		int numPrintCopies = 0;
		for (ItemInfo curItem : relatedItems){
			if (!curItem.isOrderItem() && !curItem.isEContent()){
				numPrintCopies += curItem.getNumCopies();
			}
		}
		return numPrintCopies;
	}

	public HashSet<String> getAllEContentSources() {
		HashSet<String> values = new HashSet<>();
		for (ItemInfo curItem : relatedItems){
			values.add(curItem.geteContentSource());
		}
		return values;
	}

	public HashSet<String> getAllEContentProtectionTypes() {
		HashSet<String> values = new HashSet<>();
		for (ItemInfo curItem : relatedItems){
			values.add(curItem.geteContentProtectionType());
		}
		return values;
	}

	public void addFormats(HashSet<String> translatedFormats) {
		this.formats.addAll(translatedFormats);
	}

	public void addFormatCategories(HashSet<String> translatedFormatCategories) {
		this.formatCategories.addAll(translatedFormatCategories);
	}
}
