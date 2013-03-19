package org.marmot;

public class OverDriveDBMetaData {
	private long id = -1;
	private long productId;
	private long checksum;
	private String sortTitle;
	private String publisher;
	private long publishDate;
	private boolean isPublicDomain;
	private boolean isPublicPerformanceAllowed;
	private String shortDescription;
	private String fullDescription;
	private float starRating;
	private int popularity;
	
	public long getId() {
		return id;
	}
	public void setId(long id) {
		this.id = id;
	}
	public long getProductId() {
		return productId;
	}
	public void setProductId(long productId) {
		this.productId = productId;
	}
	public long getChecksum() {
		return checksum;
	}
	public void setChecksum(long checksum) {
		this.checksum = checksum;
	}
	public String getSortTitle() {
		return sortTitle;
	}
	public void setSortTitle(String sortTitle) {
		this.sortTitle = sortTitle;
	}
	public String getPublisher() {
		return publisher;
	}
	public void setPublisher(String publisher) {
		this.publisher = publisher;
	}
	public long getPublishDate() {
		return publishDate;
	}
	public void setPublishDate(long publishDate) {
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
	public String getShortDescription() {
		return shortDescription;
	}
	public void setShortDescription(String shortDescription) {
		this.shortDescription = shortDescription;
	}
	public String getFullDescription() {
		return fullDescription;
	}
	public void setFullDescription(String fullDescription) {
		this.fullDescription = fullDescription;
	}
	
	public float getStarRating() {
		return starRating;
	}
	public void setStarRating(float starRating) {
		this.starRating = starRating;
	}
	public int getPopularity() {
		return popularity;
	}
	public void setPopularity(int popularity) {
		this.popularity = popularity;
	}

}
