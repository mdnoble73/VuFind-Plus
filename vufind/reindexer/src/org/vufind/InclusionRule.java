package org.vufind;

import java.util.HashMap;
import java.util.regex.Pattern;

/**
 * Information to determine if a particular record/item should be included within a given scope
 *
 * Pika
 * User: Mark Noble
 * Date: 7/10/2015
 * Time: 11:31 AM
 */
public class InclusionRule {
	private String recordType;
	private String locationCode;
	private Pattern locationCodePattern;
	private String subLocationCode;
	private Pattern subLocationCodePattern;
	private boolean includeHoldableOnly;
	private boolean includeItemsOnOrder;
	private boolean includeEContent;

	public InclusionRule(String recordType, String locationCode, String subLocationCode, boolean includeHoldableOnly, boolean includeItemsOnOrder, boolean includeEContent){
		this.recordType = recordType;
		this.locationCode = locationCode;
		this.subLocationCode = subLocationCode;
		this.includeHoldableOnly = includeHoldableOnly;
		this.includeItemsOnOrder = includeItemsOnOrder;
		this.includeEContent = includeEContent;

		if (locationCode.length() == 0){
			locationCode = ".*";
		}
		this.locationCodePattern = Pattern.compile(locationCode, Pattern.CASE_INSENSITIVE);
		if (subLocationCode.length() == 0){
			subLocationCode = ".*";
		}
		this.subLocationCodePattern = Pattern.compile(subLocationCode, Pattern.CASE_INSENSITIVE);
	}

	private HashMap<String, Boolean> inclusionCache = new HashMap<>();
	public boolean isItemIncluded(String recordType, String locationCode, String subLocationCode, boolean isHoldable, boolean isOnOrder, boolean isEContent){
		String key = new StringBuilder(recordType)
				.append(locationCode)
				.append(subLocationCode)
				.append(isHoldable)
				.append(isOnOrder)
				.append(isHoldable)
				.append(isEContent)
				.toString();
		if (!inclusionCache.containsKey(key)){
			//Do the quick checks first
			boolean isIncluded;
			if (!isEContent && (includeHoldableOnly && !isHoldable)){
				isIncluded = false;
			}else if (!includeItemsOnOrder && isOnOrder){
				isIncluded =  false;
			}else if (!includeEContent && isEContent){
				isIncluded =  false;
			}else if (!this.recordType.equals(recordType)){
				isIncluded =  false;
			}else if (locationCodePattern.matcher(locationCode).lookingAt() && subLocationCodePattern.matcher(subLocationCode).lookingAt()){
				//We got a match based on location so this is good to go
				isIncluded =  true;
			}else{
				isIncluded =  false;
			}
			inclusionCache.put(key, isIncluded);
		}
		return inclusionCache.get(key);
	}
}
