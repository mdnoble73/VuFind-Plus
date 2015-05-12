package org.vufind;

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
	private String lastYearCheckouts;
	private String ytdCheckouts;
	private String totalCheckouts;
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
		StringBuilder fullLocation = new StringBuilder(this.locationCode != null ? this.locationCode.toLowerCase() : "");
		if (this.collection != null){
			fullLocation.append(":").append(this.collection.toLowerCase());
		}
		//Record number gets prepended to this
		StringBuilder returnValue = new StringBuilder();
		returnValue.append(this.getItemRecordNumber())                                //Position 1
				.append("|").append(fullLocation)                                         //Position 2
				.append("|").append(this.getFullCallNumber())                             //Position 3
				.append("|").append((this.available ? "true" : "false"))                  //Position 4
				.append("|").append((this.isLibraryUseOnly() ? "true" : "false"))         //Position 5
				.append("|").append(Util.getCommaSeparatedString(this.compatiblePTypes))  //Position 6
				.append("|").append(this.status);                                         //Position 7
		return returnValue.toString();
	}

	private boolean isLibraryUseOnly() {
		return status != null && status.equals("o");
	}


}
