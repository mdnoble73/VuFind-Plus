package org.econtent;

public class OverDriveBasicInfo {
	private long id;
	private String overdriveId;
	private String title;
	private String series;
	private String author;
	private String cover;
	private String mediaType;
	private long lastChange;
	private boolean deleted;
	
	public long getId() {
		return id;
	}
	public void setId(long id) {
		this.id = id;
	}
	public String getCover() {
		return cover;
	}
	public void setCover(String cover) {
		this.cover = cover;
	}
	public String getOverdriveId() {
		return overdriveId;
	}
	public void setOverdriveId(String overdriveId) {
		this.overdriveId = overdriveId;
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
	public long getLastChange() {
		return lastChange;
	}
	public void setLastChange(long lastChange) {
		this.lastChange = lastChange;
	}
	public boolean isDeleted() {
		return deleted;
	}
	public void setDeleted(boolean deleted) {
		this.deleted = deleted;
	}
	public String getMediaType() {
		return mediaType;
	}
	public void setMediaType(String mediaType) {
		this.mediaType = mediaType;
	}
}
