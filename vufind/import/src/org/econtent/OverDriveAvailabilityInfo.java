package org.econtent;

public class OverDriveAvailabilityInfo {
	private long libraryId;
	private int copiesOwned;
	private int numHolds;
	private int availableCopies;
	private boolean available;
	
	public long getLibraryId() {
		return libraryId;
	}
	public void setLibraryId(long libraryId) {
		this.libraryId = libraryId;
	}
	public int getCopiesOwned() {
		return copiesOwned;
	}
	public void setCopiesOwned(int copiesOwned) {
		this.copiesOwned = copiesOwned;
	}
	public int getNumHolds() {
		return numHolds;
	}
	public void setNumHolds(int numHolds) {
		this.numHolds = numHolds;
	}
	public int getAvailableCopies() {
		return availableCopies;
	}
	public void setAvailableCopies(int availableCopies) {
		this.availableCopies = availableCopies;
	}
	public boolean isAvailable() {
		return available;
	}
	public void setAvailable(boolean available) {
		this.available = available;
	}
}
