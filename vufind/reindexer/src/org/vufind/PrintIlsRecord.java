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
public class PrintIlsRecord {
	private String status;
	private String dateDue;
	private String dateCreated;
	private String location;
	private String iType;
	private String lastYearCheckouts;
	private String ytdCheckouts;
	private String totalCheckouts;
	private String iCode2;
	private String callNumberPreStamp;
	private String callNumber;
	private String callNumberCutter;
	private String barcode;
	private String itemRecordNumber;

	//Data that is calculated from other data
	private boolean available;
	private HashSet<Scope> relatedScopes = new HashSet<Scope>();
	private HashSet<LocalizationInfo> relatedLocalizations = new HashSet<LocalizationInfo>();
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

	public String getLocation() {
		return location;
	}

	public void setLocation(String location) {
		this.location = location;
	}

	public String getiType() {
		return iType;
	}

	public void setiType(String iType) {
		this.iType = iType;
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

	public String getCallNumberPreStamp() {
		return callNumberPreStamp;
	}

	public void setCallNumberPreStamp(String callNumberPreStamp) {
		this.callNumberPreStamp = callNumberPreStamp;
	}

	public String getCallNumber() {
		return callNumber;
	}

	public void setCallNumber(String callNumber) {
		this.callNumber = callNumber;
	}

	public String getCallNumberCutter() {
		return callNumberCutter;
	}

	public void setCallNumberCutter(String callNumberCutter) {
		this.callNumberCutter = callNumberCutter;
	}

	public String getBarcode() {
		return barcode;
	}

	public void setBarcode(String barcode) {
		this.barcode = barcode;
	}

	public String getItemRecordNumber() {
		return itemRecordNumber;
	}

	public void setItemRecordNumber(String itemRecordNumber) {
		this.itemRecordNumber = itemRecordNumber;
	}


	public boolean isAvailable() {
		return available;
	}

	public void setAvailable(boolean available) {
		this.available = available;
	}

	public HashSet<Scope> getRelatedScopes() {
		return relatedScopes;
	}

	public void addRelatedScope(Scope scope){
		relatedScopes.add(scope);
	}

	public void setCompatiblePTypes(LinkedHashSet<String> compatiblePTypes) {
		this.compatiblePTypes = compatiblePTypes;
	}

	public LinkedHashSet<String> getCompatiblePTypes() {
		return compatiblePTypes;
	}

	public void addRelatedLocalization(LocalizationInfo localizationInfo) {
		relatedLocalizations.add(localizationInfo);
	}
}
