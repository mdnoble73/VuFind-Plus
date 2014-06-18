package org.vufind;

import java.util.HashSet;
import java.util.LinkedHashSet;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/3/2014
 * Time: 8:35 AM
 */
public class PrintIlsItem extends IlsItem {
	private String status;
	private String dateDue;
	private String dateCreated;
	private String lastYearCheckouts;
	private String ytdCheckouts;
	private String totalCheckouts;
	private String iCode2;
	private String barcode;

	//Data that is calculated from other data
	private boolean available;
	private LinkedHashSet<String> compatiblePTypes;

	public String getStatus() {
		return status;
	}

	public void setStatus(String status) {
		this.status = status;
	}

	public String getDateDue() {
		return dateDue;
	}

	public void setDateDue(String dateDue) {
		this.dateDue = dateDue;
	}

	public String getDateCreated() {
		return dateCreated;
	}

	public void setDateCreated(String dateCreated) {
		this.dateCreated = dateCreated;
	}

	public String getLastYearCheckouts() {
		return lastYearCheckouts;
	}

	public void setLastYearCheckouts(String lastYearCheckouts) {
		this.lastYearCheckouts = lastYearCheckouts;
	}

	public String getYtdCheckouts() {
		return ytdCheckouts;
	}

	public void setYtdCheckouts(String ytdCheckouts) {
		this.ytdCheckouts = ytdCheckouts;
	}

	public String getTotalCheckouts() {
		return totalCheckouts;
	}

	public void setTotalCheckouts(String totalCheckouts) {
		this.totalCheckouts = totalCheckouts;
	}

	public String getiCode2() {
		return iCode2;
	}

	public void setiCode2(String iCode2) {
		this.iCode2 = iCode2;
	}

	public String getBarcode() {
		return barcode;
	}

	public void setBarcode(String barcode) {
		this.barcode = barcode;
	}

	public boolean isAvailable() {
		return available;
	}

	public void setAvailable(boolean available) {
		this.available = available;
	}

	public void setCompatiblePTypes(LinkedHashSet<String> compatiblePTypes) {
		this.compatiblePTypes = compatiblePTypes;
	}

	public LinkedHashSet<String> getCompatiblePTypes() {
		return compatiblePTypes;
	}

	public String getRelatedItemInfo(){
		return this.getItemRecordNumber() +
				"|" + this.location +
				"|" + this.getFullCallNumber() +
				"|" + (this.available ? "true" : "false") +
				"|" + (this.isLibraryUseOnly() ? "true" : "false") +
				"|" + Util.getCommaSeparatedString(this.compatiblePTypes);
	}

	private boolean isLibraryUseOnly() {
		return status != null && status.equals("o");
	}


}
