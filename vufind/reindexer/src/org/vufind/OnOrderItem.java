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

	public String getStatus() {
		return status;
	}

	public void setStatus(String status) {
		this.status = status;
	}

	public String getOrderNumber() {
		return orderNumber;
	}

	public void setOrderNumber(String orderNumber) {
		this.orderNumber = orderNumber;
	}

	public String getBibNumber() {
		return bibNumber;
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
}
