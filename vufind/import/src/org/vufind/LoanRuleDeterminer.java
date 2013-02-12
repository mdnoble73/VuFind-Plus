package org.vufind;

public class LoanRuleDeterminer {
	public Long rowNumber;
	public String location;
	public String pType;
	public String iType; 
	public String ageRange;
	public Long loanRuleId;
	public boolean active;
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
		this.location = location;
	}
	public String getpType() {
		return pType;
	}
	public void setpType(String pType) {
		this.pType = pType;
	}
	public String getiType() {
		return iType;
	}
	public void setiType(String iType) {
		this.iType = iType;
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
}
