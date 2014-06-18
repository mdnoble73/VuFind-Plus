package org.vufind;

import org.apache.log4j.Logger;

import java.util.HashSet;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Set;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/16/2014
 * Time: 9:14 AM
 */
public class IlsRecord {
	private static Logger logger	= Logger.getLogger(IlsRecord.class);
	private String recordId;
	private HashSet<IlsItem> relatedItems = new HashSet<IlsItem>();
	private LinkedHashSet<String> formatCategories = new LinkedHashSet<String>();
	private LinkedHashSet<String> formats = new LinkedHashSet<String>();
	private long formatBoost = 1L;
	private String edition;
	private String language;
	private String publisher;
	private String publicationDate;
	private String physicalDescription;

	public String getRecordId() {
		return recordId;
	}

	public void setRecordId(String recordId) {
		this.recordId = recordId;
	}

	public HashSet<IlsItem> getRelatedItems() {
		return relatedItems;
	}

	public void setRelatedItems(HashSet<IlsItem> relatedItems) {
		this.relatedItems = relatedItems;
	}

	private HashSet<String> recordsWithoutFormats = new HashSet<String>();
	public String getPrimaryFormat() {
		//TODO: Is there a way to pick better formats based on what is in the record
		//TODO: do we need to break up records if there are multiple formats that are incompatible?
		if (formats.size() > 0) {
			return formats.iterator().next();
		}else{
			if (!recordsWithoutFormats.contains(recordId)) {
				logger.warn("Record " + recordId + " had no formats!");
				recordsWithoutFormats.add(recordId);
			}
			return "Unknown";
		}
	}

	public String getEdition() {
		return edition;
	}

	public void setEdition(String edition) {
		this.edition = edition;
	}

	public String getLanguage() {
		return language;
	}

	public void setLanguage(String language) {
		this.language = language;
	}

	public String getPublisher() {
		return publisher;
	}

	public void setPublisher(String publisher) {
		this.publisher = publisher;
	}

	public String getPublicationDate() {
		return publicationDate;
	}

	public void setPublicationDate(String publicationDate) {
		this.publicationDate = publicationDate;
	}

	public String getPhysicalDescription() {
		return physicalDescription;
	}

	public void setPhysicalDescription(String physicalDescription) {
		this.physicalDescription = physicalDescription;
	}

	public void addItems(List<PrintIlsItem> printItems) {
		this.relatedItems.addAll(printItems);
	}
	public void addItem(IlsItem printItem) {
		this.relatedItems.add(printItem);
	}

	public void addFormats(Set<String> formats) {
		this.formats.addAll(formats);
	}

	public void addFormat(String format) {
		this.formats.add(format);
	}

	public void addFormatCategories(HashSet<String> formatCategories) {
		this.formatCategories.addAll(formatCategories);
	}

	public void addFormatCategory(String formatCategory){
		this.formatCategories.add(formatCategory);
	}

	public void setFormatBoost(Long formatBoost) {
		if (formatBoost > this.formatBoost){
			this.formatBoost = formatBoost;
		}
	}

	public HashSet<String> getFormatCategories() {
		return formatCategories;
	}

	public HashSet<String> getFormats() {
		return formats;
	}

	public long getFormatBoost() {
		return formatBoost;
	}

	public void setFormatInformation(String format, String formatCategory, String formatBoost) {
		addFormat(format);
		addFormatCategory(formatCategory);
		Long formatBoostNumeric = Long.parseLong(formatBoost);
		setFormatBoost(formatBoostNumeric);
	}

	public HashSet<Scope> getRelatedScopes() {
		HashSet<Scope> recordScopes = new HashSet<Scope>();
		for (IlsItem ilsItem : relatedItems){
			recordScopes.addAll(ilsItem.getRelatedScopes());
		}
		return recordScopes;
	}
}
