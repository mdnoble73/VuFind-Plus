package org.vufind;

import java.util.ArrayList;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 2/21/14
 * Time: 4:05 PM
 */
public class EContentRecord {
	private long eContentRecordId;
	private String accessType;
	private long availableCopies;
	private long onOrderCopies;
	private String ilsId;
	private String source;
	private ArrayList<EContentItem> items;

	public long getEContentRecordId() {
		return eContentRecordId;
	}

	public void setEContentRecordId(long eContentRecordId) {
		this.eContentRecordId = eContentRecordId;
	}

	public String getAccessType() {
		return accessType;
	}

	public void setAccessType(String accessType) {
		this.accessType = accessType;
	}

	public long getAvailableCopies() {
		return availableCopies;
	}

	public void setAvailableCopies(long availableCopies) {
		this.availableCopies = availableCopies;
	}

	public long getOnOrderCopies() {
		return onOrderCopies;
	}

	public void setOnOrderCopies(long onOrderCopies) {
		this.onOrderCopies = onOrderCopies;
	}

	public String getIlsId() {
		return ilsId;
	}

	public void setIlsId(String ilsId) {
		this.ilsId = ilsId;
	}

	public String getSource() {
		return source;
	}

	public void setSource(String source) {
		this.source = source;
	}

	public ArrayList<EContentItem> getItems() {
		return items;
	}

	public void addEContentItem(EContentItem item){
		this.items.add(item);
	}
}
