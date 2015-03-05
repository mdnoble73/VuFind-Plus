package org.vufind;

/**
 * A title that is checked out to a user for reading history
 * VuFind-Plus
 * User: Mark Noble
 * Date: 12/11/2014
 * Time: 1:34 PM
 */
public class CheckedOutTitle {
	private Long id;
	private String groupedWorkPermanentId;
	private String source;
	private String sourceId;

	public Long getId() {
		return id;
	}

	public void setId(Long id) {
		this.id = id;
	}

	public String getGroupedWorkPermanentId() {
		return groupedWorkPermanentId;
	}

	public void setGroupedWorkPermanentId(String groupedWorkPermanentId) {
		this.groupedWorkPermanentId = groupedWorkPermanentId;
	}

	public String getSource() {
		return source;
	}

	public void setSource(String source) {
		this.source = source;
	}

	public String getSourceId() {
		return sourceId;
	}

	public void setSourceId(String sourceId) {
		this.sourceId = sourceId;
	}
}
