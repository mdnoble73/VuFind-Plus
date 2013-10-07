package org.marmot;

import java.util.HashSet;

public class OverDriveRecordInfo {
	//Data from base title calll
	private String id;
	private String mediaType;
	private String title;
	private String series;
	private String primaryCreatorRole;
	private String primaryCreatorName;
	private HashSet<String> formats = new HashSet<String>();
	private String coverImage;
	private HashSet<Long> collections = new HashSet<Long>();
	//Data from metadata call
	private String publisher;
	private String publishDate;
	private boolean isPublicDomain;
	private boolean isPublicPerformanceAllowed;
	private String description;
	private HashSet<String> subjects = new HashSet<String>();
	private String rawData;
	private boolean isShared = false;

	public String getRawData() {
		return rawData;
	}

	public void setRawData(String rawData) {
		this.rawData = rawData;
	}

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
		this.title = title.replaceAll("&#174;", "�");
	}
	public String getSeries() {
		return series;
	}
	public void setSeries(String series) {
		this.series = series;
	}
	
	public String getPrimaryCreatorRole() {
		return primaryCreatorRole;
	}
	public void setPrimaryCreatorRole(String primaryCreatorRole) {
		this.primaryCreatorRole = primaryCreatorRole;
	}
	public String getPrimaryCreatorName() {
		return primaryCreatorName;
	}
	public void setPrimaryCreatorName(String primaryCreatorName) {
		this.primaryCreatorName = primaryCreatorName;
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
	public HashSet<String> getSubjects() {
		return subjects;
	}
	public void setFormats(HashSet<String> formats) {
		this.formats = formats;
	}
	
}
