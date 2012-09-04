package org.econtent;

import java.util.HashMap;
import java.util.HashSet;

public class OverDriveRecordInfo {
	//Data from base title calll
	private String id;
	private String mediaType;
	private String title;
	private String series;
	private String author;
	private HashSet<String> formats = new HashSet<String>();
	private String coverImage;
	private HashSet<Long> collections = new HashSet<Long>();
	//Data from availability call
	private HashMap<Long, OverDriveAvailabilityInfo> availabilityInfo = new HashMap<Long, OverDriveAvailabilityInfo>();
	//Data from metadata call
	private String edition;
	private String publisher;
	private String publishDate;
	private HashSet<String> contributors = new HashSet<String>();
	private HashSet<String> languages = new HashSet<String>();
	private boolean isPublicDomain;
	private boolean isPublicPerformanceAllowed;
	private String description;
	private HashSet<String> subjects = new HashSet<String>();
	//items are formats in overdrive
	private HashMap<String, OverDriveItem> items = new HashMap<String, OverDriveItem>();
	
	private boolean isShared = false;
	public String getId() {
		return id;
	}
	public void setId(String id) {
		this.id = id.toLowerCase();
	}
	public String getMediaType() {
		return mediaType;
	}
	public void setMediaType(String mediaType) {
		this.mediaType = mediaType;
	}
	public String getTitle() {
		return title;
	}
	public void setTitle(String title) {
		this.title = title;
	}
	public String getSeries() {
		return series;
	}
	public void setSeries(String series) {
		this.series = series;
	}
	public String getAuthor() {
		return author;
	}
	public void setAuthor(String author) {
		this.author = author;
	}
	public HashSet<String> getFormats() {
		return formats;
	}
	public String getCoverImage() {
		return coverImage;
	}
	public void setCoverImage(String coverImage) {
		this.coverImage = coverImage;
	}
	public HashSet<Long> getCollections() {
		return collections;
	}
	public boolean isShared() {
		return isShared;
	}
	public void setShared(boolean isShared) {
		this.isShared = isShared;
	}
	public HashMap<Long, OverDriveAvailabilityInfo> getAvailabilityInfo() {
		return availabilityInfo;
	}
	public String getEdition() {
		return edition;
	}
	public void setEdition(String edition) {
		this.edition = edition;
	}
	public String getPublisher() {
		return publisher;
	}
	public void setPublisher(String publisher) {
		this.publisher = publisher;
	}
	public String getPublishDate() {
		return publishDate;
	}
	public void setPublishDate(String publishDate) {
		this.publishDate = publishDate;
	}
	public HashSet<String> getLanguages() {
		return languages;
	}
	public void setLanguages(HashSet<String> languages) {
		this.languages = languages;
	}
	public boolean isPublicDomain() {
		return isPublicDomain;
	}
	public void setPublicDomain(boolean isPublicDomain) {
		this.isPublicDomain = isPublicDomain;
	}
	public boolean isPublicPerformanceAllowed() {
		return isPublicPerformanceAllowed;
	}
	public void setPublicPerformanceAllowed(boolean isPublicPerformanceAllowed) {
		this.isPublicPerformanceAllowed = isPublicPerformanceAllowed;
	}
	public String getDescription() {
		return description;
	}
	public void setDescription(String description) {
		this.description = description;
	}
	public HashMap<String, OverDriveItem> getItems() {
		return items;
	}
	public void setItems(HashMap<String, OverDriveItem> items) {
		this.items = items;
	}
	public HashSet<String> getContributors() {
		return contributors;
	}
	public HashSet<String> getSubjects() {
		return subjects;
	}
	public void setFormats(HashSet<String> formats) {
		this.formats = formats;
	}
	public void setAvailabilityInfo(HashMap<Long, OverDriveAvailabilityInfo> availabilityInfo) {
		this.availabilityInfo = availabilityInfo;
	}
	
}
