package org.vufind;

import java.util.HashSet;

public class LoanRuleDeterminer {
	private Long rowNumber;
	private String location;
	private String trimmedLocation;
	private String patronType;
	private HashSet<Long>	patronTypes;
	private String itemType;
	private HashSet<Long> itemTypes;
	private String ageRange;
	private Long loanRuleId;
	private boolean active;
	
	public Long getRowNumber() {
		return rowNumber;
	}
	public void setRowNumber(Long rowNumber) {
		this.rowNumber = rowNumber;
	}
	public String getLocation() {
		return location;
	}
	public void setLocation(String location) {
		location = location.trim();
		this.location = location;
		if (location.endsWith("*")){
			trimmedLocation = location.substring(0, location.length() -1).toLowerCase();
		}else{
			trimmedLocation = location.toLowerCase();
		}
	}
	
	public String getAgeRange() {
		return ageRange;
	}
	public void setAgeRange(String ageRange) {
		this.ageRange = ageRange;
	}
	public Long getLoanRuleId() {
		return loanRuleId;
	}
	public void setLoanRuleId(Long loanRuleId) {
		this.loanRuleId = loanRuleId;
	}
	public boolean isActive() {
		return active;
	}
	public void setActive(boolean active) {
		this.active = active;
	}
	public String getPatronType() {
		return patronType;
	}
	public void setPatronType(String patronType) {
		this.patronType = patronType;
		patronTypes = splitNumberRangeString(patronType);
	}
	public String getItemType() {
		return itemType;
	}
	public void setItemType(String itemType) {
		this.itemType = itemType;
		itemTypes = splitNumberRangeString(itemType);
	}
	private HashSet<Long> splitNumberRangeString(String numberRangeString) {
		HashSet<Long> result = new HashSet<Long>();
		String[] iTypeValues = numberRangeString.split(",");
		
		for (int i = 0; i < iTypeValues.length; i++){
			if (iTypeValues[i].indexOf('-') > 0){
				String[] iTypeRange = iTypeValues[i].split("-");
				Long iTypeRangeStart = Long.parseLong(iTypeRange[0]);
				Long iTypeRangeEnd = Long.parseLong(iTypeRange[1]);
				for (Long j = iTypeRangeStart; j <= iTypeRangeEnd; j++){
					result.add(j);
				}
			}else{
				result.add(Long.parseLong(iTypeValues[i]));
			}
		}
		return result;
	}
	public boolean matchesLocation(String locationCode) {
		if (location.equals("*") || location.equals("?????")){
			return true;
		}
		return locationCode.toLowerCase().startsWith(this.trimmedLocation);
	}
	public HashSet<Long> getPatronTypes() {
		return patronTypes;
	}
	public HashSet<Long> getItemTypes() {
		return itemTypes;
	}

}
