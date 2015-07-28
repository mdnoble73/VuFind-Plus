package org.vufind;

/**
 * Information that applies to specific scopes for the item.
 *
 * Pika
 * User: Mark Noble
 * Date: 7/14/2015
 * Time: 9:51 PM
 */
public class ScopingInfo {
	private ItemInfo item;
	private Scope scope;
	private String status;
	private String groupedStatus;
	private boolean available;
	private boolean holdable;
	private boolean locallyOwned;
	private boolean bookable;
	private boolean inLibraryUseOnly;
	private boolean libraryOwned;

	public ScopingInfo(Scope scope, ItemInfo item){
		this.item = item;
		this.scope = scope;
	}

	public String getStatus() {
		return status;
	}

	public void setStatus(String status) {
		this.status = status;
	}

	public String getGroupedStatus() {
		return groupedStatus;
	}

	public void setGroupedStatus(String groupedStatus) {
		this.groupedStatus = groupedStatus;
	}

	public boolean isAvailable() {
		return available;
	}

	public void setAvailable(boolean available) {
		this.available = available;
	}

	public boolean isHoldable() {
		return holdable;
	}

	public void setHoldable(boolean holdable) {
		this.holdable = holdable;
	}

	public boolean isLocallyOwned() {
		return locallyOwned;
	}

	public void setLocallyOwned(boolean locallyOwned) {
		this.locallyOwned = locallyOwned;
	}

	public Scope getScope() {
		return scope;
	}

	public boolean isBookable() {
		return bookable;
	}

	public void setBookable(boolean bookable) {
		this.bookable = bookable;
	}

	public boolean isInLibraryUseOnly() {
		return inLibraryUseOnly;
	}

	public void setInLibraryUseOnly(boolean inLibraryUseOnly) {
		this.inLibraryUseOnly = inLibraryUseOnly;
	}

	public boolean isLibraryOwned() {
		return libraryOwned;
	}

	public void setLibraryOwned(boolean libraryOwned) {
		this.libraryOwned = libraryOwned;
	}

	public String getScopingDetails(){
		return new StringBuilder().append(item.getFullRecordIdentifier()).append("|")
				.append(item.getItemIdentifier()).append("|")
				.append(groupedStatus).append("|")
				.append(status).append("|")
				.append(locallyOwned).append("|")
				.append(available).append("|")
				.append(holdable).append("|")
				.append(bookable).append("|")
				.append(inLibraryUseOnly).append("|")
				.append(libraryOwned)
				.toString()
				;
	}
}
