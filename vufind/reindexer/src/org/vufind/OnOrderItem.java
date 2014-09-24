package org.vufind;

import java.util.HashSet;

/**
 * Represents Items that are currently on order
 * VuFind-Plus
 * User: Mark Noble
 * Date: 9/2/2014
 * Time: 10:10 PM
 */
public class OnOrderItem {
	private String orderNumber;
	private String bibNumber;
	private HashSet<Scope> relatedScopes = new HashSet<Scope>();
	private String status;
	private int copies;
	private String locationCode;

	public void setStatus(String status) {
		this.status = status;
	}

	public String getOrderNumber() {
		return orderNumber;
	}

	public void setOrderNumber(String orderNumber) {
		this.orderNumber = orderNumber;
	}

	public void setBibNumber(String bibNumber) {
		this.bibNumber = bibNumber;
	}

	public void addRelatedScope(Scope scope){
		relatedScopes.add(scope);
	}

	public HashSet<Scope> getRelatedScopes(){
		return relatedScopes;
	}

	public String getRelatedItemInfo() {
		return this.getOrderNumber() + "|" + locationCode +"||false|false||" + status + "|" + copies;
	}

	public String getRecordIdentifier() {
		return "ils:" + bibNumber;
	}

	public void setLocationCode(String locationCode) {
		this.locationCode = locationCode;
	}

	public int getCopies() {
		return copies;
	}

	public void setCopies(int copies) {
		this.copies = copies;
	}
}
