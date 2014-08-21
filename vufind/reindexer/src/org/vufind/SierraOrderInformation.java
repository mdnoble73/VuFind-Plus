package org.vufind;

/**
 * Information about order records for Sierra.
 * VuFind-Plus
 * User: Mark Noble
 * Date: 8/18/2014
 * Time: 7:36 AM
 */
public class SierraOrderInformation {
	private String bibRecordNumber;
	private String orderNumber;
	private long accountingUnit;
	private String statusCode;

	public String getBibRecordNumber() {
		return bibRecordNumber;
	}

	public void setBibRecordNumber(String bibRecordNumber) {
		this.bibRecordNumber = bibRecordNumber;
	}

	public String getOrderNumber() {
		return orderNumber;
	}

	public void setOrderNumber(String orderNumber) {
		this.orderNumber = orderNumber;
	}

	public long getAccountingUnit() {
		return accountingUnit;
	}

	public void setAccountingUnit(long accountingUnit) {
		this.accountingUnit = accountingUnit;
	}

	public String getStatusCode() {
		return statusCode;
	}

	public void setStatusCode(String statusCode) {
		this.statusCode = statusCode;
	}
}
