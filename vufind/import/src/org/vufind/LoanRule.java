package org.vufind;

public class LoanRule {
	private Long loanRuleId;
	private String name;
	private String code; 
	private Integer normalLoanPeriod;
	private Boolean holdable;
	public Long getLoanRuleId() {
		return loanRuleId;
	}
	public void setLoanRuleId(Long loanRuleId) {
		this.loanRuleId = loanRuleId;
	}
	public String getName() {
		return name;
	}
	public void setName(String name) {
		this.name = name;
	}
	public String getCode() {
		return code;
	}
	public void setCode(String code) {
		this.code = code;
	}
	public Integer getNormalLoanPeriod() {
		return normalLoanPeriod;
	}
	public void setNormalLoanPeriod(Integer normalLoanPeriod) {
		this.normalLoanPeriod = normalLoanPeriod;
	}
	public Boolean getHoldable() {
		return holdable;
	}
	public void setHoldable(Boolean holdable) {
		this.holdable = holdable;
	}
	
	
}
