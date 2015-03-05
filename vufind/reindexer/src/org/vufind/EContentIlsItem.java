package org.vufind;

import java.util.Collection;
import java.util.HashSet;
import java.util.LinkedHashSet;

/**
 * A specific item record for eContent based on data within the ILS
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/3/2014
 * Time: 1:52 PM
 */
public class EContentIlsItem extends IlsItem{
	private String protectionType;
	private String source;
	private String sharing;
	private String acsId;
	private String filename;
	private String numberOfCopies;
	private String url;

	//Data that is calculated from other data
	private boolean available;

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

	public HashSet<String> getValidLocations() {
		return new HashSet<String>();
	}

	public String getRelatedItemInfo() {
		String itemInfo = this.getItemRecordNumber() +
				"|" + this.location +
				"|" + (this.available ? "true" : "false") +
				"|" + this.sharing +
				"|" + this.source;
		if (url != null && url.length() > 0){
			itemInfo += "|" + this.url;
		}
		return itemInfo;
	}
}
