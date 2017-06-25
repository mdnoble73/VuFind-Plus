package org.vufind;

import org.marc4j.marc.Record;

import java.util.HashMap;
import java.util.HashSet;
import java.util.Set;
import java.util.regex.Pattern;

/**
 * Information to determine if a particular record/item should be included within a given scope
 *
 * Pika
 * User: Mark Noble
 * Date: 7/10/2015
 * Time: 11:31 AM
 */
class InclusionRule {
	private String recordType;
	private Pattern locationCodePattern;
	private Pattern subLocationCodePattern;
	private Pattern iTypePattern;
	private Pattern audiencePattern;
	private Pattern formatPattern;
	private boolean includeHoldableOnly;
	private boolean includeItemsOnOrder;
	private boolean includeEContent;
	private String marcTagToMatch;
	private Pattern marcValueToMatchPattern;
	private boolean includeExcludeMatches;
	private String urlToMatch;
	private String urlReplacement;

	InclusionRule(String recordType, String locationCode, String subLocationCode, String iType, String audience, String format, boolean includeHoldableOnly, boolean includeItemsOnOrder, boolean includeEContent, String marcTagToMatch, String marcValueToMatch, boolean includeExcludeMatches, String urlToMatch, String urlReplacement){
		this.recordType = recordType;
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

		if (iType == null || iType.length() == 0){
			iType = ".*";
		}
		this.iTypePattern = Pattern.compile(iType, Pattern.CASE_INSENSITIVE);

		if (audience == null || audience.length() == 0){
			audience = ".*";
		}
		this.audiencePattern = Pattern.compile(audience, Pattern.CASE_INSENSITIVE);

		if (format == null || format.length() == 0){
			format = ".*";
		}
		this.formatPattern = Pattern.compile(format, Pattern.CASE_INSENSITIVE);


		if (marcTagToMatch == null){
			this.marcTagToMatch = "";
		}else{
			this.marcTagToMatch = marcTagToMatch;
		}

		if (marcValueToMatch == null || marcValueToMatch.length() == 0){
			marcValueToMatch = ".*";
		}
		this.marcValueToMatchPattern = Pattern.compile(marcValueToMatch);

		this.includeExcludeMatches = includeExcludeMatches;

		this.urlToMatch = urlToMatch;
		this.urlReplacement = urlReplacement;
	}

	private HashMap<String, Boolean> inclusionCache = new HashMap<>();
	boolean isItemIncluded(String recordType, String locationCode, String subLocationCode, String iType, HashSet<String> audiences, String format, boolean isHoldable, boolean isOnOrder, boolean isEContent, Record marcRecord){
		String key = recordType +
				locationCode +
				subLocationCode +
				iType +
				Util.getCsvSeparatedString(audiences) +
				format +
				isHoldable +
				isOnOrder +
				isHoldable +
				isEContent;
		boolean isIncluded;

		if (!inclusionCache.containsKey(key)){
			//Do the quick checks first
			if (!isEContent && (includeHoldableOnly && !isHoldable)){
				isIncluded = false;
			}else if (!includeItemsOnOrder && isOnOrder){
				isIncluded =  false;
			}else if (!includeEContent && isEContent){
				isIncluded =  false;
			}else if (!this.recordType.equals(recordType)){
				isIncluded =  false;
			}else if (locationCodePattern.matcher(locationCode).lookingAt() &&
					subLocationCodePattern.matcher(subLocationCode).lookingAt() &&
					(formatPattern.matcher(format).lookingAt())
					){

				//We got a match based on location check formats iTypes etc
				if (iType != null && !iTypePattern.matcher(iType).lookingAt()){
					isIncluded =  false;
				}else{
					boolean audienceMatched = false;
					for (String audience : audiences){
						if (audiencePattern.matcher(audience).lookingAt()){
							audienceMatched = true;
							break;
						}
					}
					isIncluded = audienceMatched;
				}
			}else{
				isIncluded = false;
			}
			inclusionCache.put(key, isIncluded);
		}else{
			isIncluded = inclusionCache.get(key);
		}
		if (isIncluded && marcTagToMatch.length() > 0){
			boolean hasMatch = false;
			Set<String> marcValuesToCheck = MarcUtil.getFieldList(marcRecord, marcTagToMatch);
			for (String marcValueToCheck : marcValuesToCheck){
				if (marcValueToMatchPattern.matcher(marcValueToCheck).lookingAt()){
					hasMatch = true;
					break;
				}
			}
			if (hasMatch){
				return includeExcludeMatches;
			}
		}
		return inclusionCache.get(key);
	}

	String getLocalUrl(String url){
		if (urlToMatch == null || urlToMatch.length() == 0 || urlReplacement == null || urlReplacement.length() == 0){
			return url;
		}else{
			return url.replaceFirst(urlToMatch, urlReplacement);
		}
	}
}
