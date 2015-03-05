package org.vufind;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 2/21/14
 * Time: 4:08 PM
 */
public class EContentItem {
	private String itemType;
	private Long libraryId;
	private String filename;
	private String folder;

	public String getItemType() {
		return itemType;
	}

	public void setItemType(String itemType) {
		this.itemType = itemType;
	}

	public Long getLibraryId() {
		return libraryId;
	}

	public void setLibraryId(Long libraryId) {
		this.libraryId = libraryId;
	}

	public String getFilename() {
		return filename;
	}

	public void setFilename(String filename) {
		this.filename = filename;
	}

	public String getFolder() {
		return folder;
	}

	public void setFolder(String folder) {
		this.folder = folder;
	}
}
