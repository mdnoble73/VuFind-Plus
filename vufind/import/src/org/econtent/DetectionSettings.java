package org.econtent;

public class DetectionSettings{
	//Data loaded from the database
	private String fieldSpec;
	private String valueToMatch;
	private String source;
	private String accessType;
	private String item_type;
	private boolean add856FieldsAsExternalLinks;

	public String getFieldSpec() {
		return fieldSpec;
	}
	public void setFieldSpec(String fieldSpec) {
		this.fieldSpec = fieldSpec;
	}
	public String getValueToMatch() {
		return valueToMatch;
	}
	public void setValueToMatch(String valueToMatch) {
		this.valueToMatch = valueToMatch;
	}
	public String getSource() {
		return source;
	}
	public void setSource(String source) {
		this.source = source;
	}
	public String getAccessType() {
		return accessType;
	}
	public void setAccessType(String accessType) {
		this.accessType = accessType;
	}
	public String getItem_type() {
		return item_type;
	}
	public void setItem_type(String item_type) {
		this.item_type = item_type;
	}
	public boolean isAdd856FieldsAsExternalLinks() {
		return add856FieldsAsExternalLinks;
	}
	public void setAdd856FieldsAsExternalLinks(boolean add856FieldsAsExternalLinks) {
		this.add856FieldsAsExternalLinks = add856FieldsAsExternalLinks;
	}
}
