package org.marmot;

public class OverDriveDBInfo {
	private String overDriveId;
	private long dbId;
	private String mediaType;
	private String title;
	private String series;
	private String primaryCreatorRole;
	private String primaryCreatorName;
	private long dateAdded;
	private long dateUpdated;
	private long lastMetadataCheck;
	private long lastMetadataChange;
	private long lastAvailabilityCheck;
	private long lastAvailabilityChange;
	private boolean deleted;
	
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
	
	
	public String getOverDriveId() {
		return overDriveId;
	}
	public void setOverDriveId(String overDriveId) {
		this.overDriveId = overDriveId;
	}
	public long getDbId() {
		return dbId;
	}
	public void setDbId(long dbId) {
		this.dbId = dbId;
	}
	public long getDateAdded() {
		return dateAdded;
	}
	public void setDateAdded(long dateAdded) {
		this.dateAdded = dateAdded;
	}
	public long getDateUpdated() {
		return dateUpdated;
	}
	public void setDateUpdated(long dateUpdated) {
		this.dateUpdated = dateUpdated;
	}
	public long getLastMetadataCheck() {
		return lastMetadataCheck;
	}
	public void setLastMetadataCheck(long lastMetadataCheck) {
		this.lastMetadataCheck = lastMetadataCheck;
	}
	public long getLastMetadataChange() {
		return lastMetadataChange;
	}
	public void setLastMetadataChange(long lastMetadataChange) {
		this.lastMetadataChange = lastMetadataChange;
	}
	public long getLastAvailabilityCheck() {
		return lastAvailabilityCheck;
	}
	public void setLastAvailabilityCheck(long lastAvailabilityCheck) {
		this.lastAvailabilityCheck = lastAvailabilityCheck;
	}
	public long getLastAvailabilityChange() {
		return lastAvailabilityChange;
	}
	public void setLastAvailabilityChange(long lastAvailabilityChange) {
		this.lastAvailabilityChange = lastAvailabilityChange;
	}
	
}
