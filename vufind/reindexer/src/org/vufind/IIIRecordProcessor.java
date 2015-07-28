package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.*;

/**
 * Record Processor to handle processing records from Millennium and Sierra
 * Pika
 * User: Mark Noble
 * Date: 7/9/2015
 * Time: 11:39 PM
 */
public abstract class IIIRecordProcessor extends IlsRecordProcessor{
	private static boolean loanRuleDataLoaded = false;
	protected static ArrayList<Long> pTypes = new ArrayList<>();
	protected static HashMap<String, HashSet<String>> pTypesByLibrary = new HashMap<>();
	protected static HashMap<String, HashSet<String>> pTypesForSpecialLocationCodes = new HashMap<>();
	protected static HashSet<String> allPTypes = new HashSet<>();
	private static HashMap<Long, LoanRule> loanRules = new HashMap<>();
	private static ArrayList<LoanRuleDeterminer> loanRuleDeterminers = new ArrayList<>();

	public IIIRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, ResultSet indexingProfileRS, Logger logger, boolean fullReindex) {
		super(indexer, vufindConn, configIni, indexingProfileRS, logger, fullReindex);
		loadLoanRuleInformation(vufindConn, logger);
	}

	private static void loadLoanRuleInformation(Connection vufindConn, Logger logger) {
		if (!loanRuleDataLoaded){
			//Load loan rules
			try {
				PreparedStatement pTypesStmt = vufindConn.prepareStatement("SELECT pType from ptype", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet pTypesRS = pTypesStmt.executeQuery();
				while (pTypesRS.next()) {
					pTypes.add(pTypesRS.getLong("pType"));
					allPTypes.add(pTypesRS.getString("pType"));
				}

				PreparedStatement pTypesByLibraryStmt = vufindConn.prepareStatement("SELECT pTypes, ilsCode, econtentLocationsToInclude from library", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet pTypesByLibraryRS = pTypesByLibraryStmt.executeQuery();
				while (pTypesByLibraryRS.next()) {
					String ilsCode = pTypesByLibraryRS.getString("ilsCode");
					String pTypes = pTypesByLibraryRS.getString("pTypes");
					String econtentLocationsToIncludeStr = pTypesByLibraryRS.getString("econtentLocationsToInclude");
					if (pTypes != null && pTypes.length() > 0){
						String[] pTypeElements = pTypes.split(",");
						HashSet<String> pTypesForLibrary = new HashSet<>();
						Collections.addAll(pTypesForLibrary, pTypeElements);
						pTypesByLibrary.put(ilsCode, pTypesForLibrary);
						if (econtentLocationsToIncludeStr.length() > 0) {
							String[] econtentLocationsToInclude = econtentLocationsToIncludeStr.split(",");
							for (String econtentLocationToInclude : econtentLocationsToInclude) {
								econtentLocationToInclude = econtentLocationToInclude.trim();
								if (econtentLocationToInclude.length() > 0) {
									if (!pTypesForSpecialLocationCodes.containsKey(econtentLocationToInclude)) {
										pTypesForSpecialLocationCodes.put(econtentLocationToInclude, new HashSet<String>());
									}
									pTypesForSpecialLocationCodes.get(econtentLocationToInclude).addAll(pTypesForLibrary);
								}
							}
						}
					}
				}

				PreparedStatement loanRuleStmt = vufindConn.prepareStatement("SELECT * from loan_rules", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet loanRulesRS = loanRuleStmt.executeQuery();
				while (loanRulesRS.next()) {
					LoanRule loanRule = new LoanRule();
					loanRule.setLoanRuleId(loanRulesRS.getLong("loanRuleId"));
					loanRule.setName(loanRulesRS.getString("name"));
					loanRule.setHoldable(loanRulesRS.getBoolean("holdable"));
					loanRule.setBookable(loanRulesRS.getBoolean("bookable"));

					loanRules.put(loanRule.getLoanRuleId(), loanRule);
				}
				logger.debug("Loaded " + loanRules.size() + " loan rules");

				PreparedStatement loanRuleDeterminersStmt = vufindConn.prepareStatement("SELECT * from loan_rule_determiners where active = 1 order by rowNumber DESC", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet loanRuleDeterminersRS = loanRuleDeterminersStmt.executeQuery();
				while (loanRuleDeterminersRS.next()) {
					LoanRuleDeterminer loanRuleDeterminer = new LoanRuleDeterminer();
					loanRuleDeterminer.setLocation(loanRuleDeterminersRS.getString("location"));
					loanRuleDeterminer.setPatronType(loanRuleDeterminersRS.getString("patronType"));
					loanRuleDeterminer.setItemType(loanRuleDeterminersRS.getString("itemType"));
					loanRuleDeterminer.setLoanRuleId(loanRuleDeterminersRS.getLong("loanRuleId"));
					loanRuleDeterminer.setActive(loanRuleDeterminersRS.getBoolean("active"));

					loanRuleDeterminers.add(loanRuleDeterminer);
				}

				logger.debug("Loaded " + loanRuleDeterminers.size() + " loan rule determiner");
			} catch (SQLException e) {
				logger.error("Unable to load loan rules", e);
			}
			loanRuleDataLoaded = true;
		}
	}

	private HashMap<String, LinkedHashSet<String>> ptypesByItypeAndLocation = new HashMap<>();
	protected LinkedHashSet<String> getCompatiblePTypes(String iType, String locationCode) {
		if (loanRuleDeterminers.size() == 0){
			return new LinkedHashSet<>();
		}
		String cacheKey = iType + ":" + locationCode;
		if (ptypesByItypeAndLocation.containsKey(cacheKey)){
			return ptypesByItypeAndLocation.get(cacheKey);
		}else{
			logger.debug("Did not get cached ptype compatibility for " + cacheKey);
		}
		LinkedHashSet<String> result = calculateCompatiblePTypes(iType, locationCode);

		//logger.debug("  " + result.size() + " ptypes can use this");
		ptypesByItypeAndLocation.put(cacheKey, result);
		return result;
	}


	private HashMap<String, ArrayList<LoanRule>> cachedRelevantLoanRules = new HashMap<>();
	private ArrayList<LoanRule> getRelevantLoanRules(String iType, String locationCode, HashSet<Long> pTypesToCheck){
		String key = iType + locationCode + pTypesToCheck.toString();
		ArrayList<LoanRule> relevantLoanRules = cachedRelevantLoanRules.get(key);
		if (relevantLoanRules == null){
			relevantLoanRules = new ArrayList<>();
		}else{
			return relevantLoanRules;
		}
		Long iTypeLong = Long.parseLong(iType);
		for (LoanRuleDeterminer curDeterminer : loanRuleDeterminers){
			if (curDeterminer.isActive()){
				//logger.debug("    " + curDeterminer.getRowNumber() + " matches location");
				if (curDeterminer.getItemType().equals("999") || curDeterminer.getItemTypes().contains(iTypeLong)) {
					//logger.debug("    " + curDeterminer.getRowNumber() + " matches iType");
					if (curDeterminer.getPatronType().equals("999") || isPTypeValid(curDeterminer.getPatronTypes(), pTypesToCheck)) {
						//logger.debug("    " + curDeterminer.getRowNumber() + " matches pType");
						//Make sure the location matches
						if (curDeterminer.matchesLocation(locationCode)) {
							LoanRule loanRule = loanRules.get(curDeterminer.getLoanRuleId());
							relevantLoanRules.add(loanRule);
							break;
						}
					}
				}
			}
		}
		cachedRelevantLoanRules.put(key, relevantLoanRules);
		return relevantLoanRules;
	}

	private boolean isPTypeValid(HashSet<Long> determinerPatronTypes, HashSet<Long> pTypesToCheck) {
		//For our case,
		if (pTypesToCheck.size() == 0){
			return true;
		}
		for (Long determinerPType : determinerPatronTypes){
			for (Long pTypeToCheck : pTypesToCheck){
				if (pTypeToCheck.equals(determinerPType)) {
					return true;
				}
			}
		}
		return false;
	}

	private LinkedHashSet<String> calculateCompatiblePTypes(String iType, String locationCode) {
		//logger.debug("getCompatiblePTypes for " + cacheKey);
		LinkedHashSet<String> result = new LinkedHashSet<>();
		if (!Util.isNumeric(iType)){
			logger.warn("IType " + iType + " was not numeric marking as incompatible with everything");
			return result;
		}
		Long iTypeLong = Long.parseLong(iType);
		//Loop through all patron types to see if the item is holdable
		for (Long pType : pTypes){
			//logger.debug("  Checking pType " + pType);
			//Loop through the loan rules to see if this itype can be used based on the location code
			for (LoanRuleDeterminer curDeterminer : loanRuleDeterminers){
				if (curDeterminer.isActive()){
					//logger.debug("    " + curDeterminer.getRowNumber() + " matches location");
					if (curDeterminer.getItemType().equals("999") || curDeterminer.getItemTypes().contains(iTypeLong)) {
						//logger.debug("    " + curDeterminer.getRowNumber() + " matches iType");
						if (curDeterminer.getPatronType().equals("999") || curDeterminer.getPatronTypes().contains(pType)) {
							//logger.debug("    " + curDeterminer.getRowNumber() + " matches pType");
							//Make sure the location matches
							if (curDeterminer.matchesLocation(locationCode)) {
								LoanRule loanRule = loanRules.get(curDeterminer.getLoanRuleId());
								if (loanRule.getHoldable().equals(Boolean.TRUE)) {
									if (curDeterminer.getPatronType().equals("999")) {
										result.add("all");
										return result;
									} else {
										result.add(pType.toString());
									}
								}
								//We got a match, stop processing
								//logger.debug("    using determiner " + curDeterminer.getRowNumber() + " for ptype " + pType);
								break;
							}
						}
					}
				}
			}
		}
		return result;
	}

	@Override
	protected boolean isItemHoldable(ItemInfo itemInfo, Scope curScope) {
		ArrayList<LoanRule> relevantLoanRules = getRelevantLoanRules(itemInfo.getITypeCode(), itemInfo.getLocationCode(), curScope.getRelatedNumericPTypes());
		for (LoanRule loanRule : relevantLoanRules){
			if (loanRule.getHoldable()){
				return true;
			}
		}
		return false;
	}

	@Override
	protected boolean isItemBookable(ItemInfo itemInfo, Scope curScope) {
		ArrayList<LoanRule> relevantLoanRules = getRelevantLoanRules(itemInfo.getITypeCode(), itemInfo.getLocationCode(), curScope.getRelatedNumericPTypes());
		for (LoanRule loanRule : relevantLoanRules){
			if (loanRule.getBookable()){
				return true;
			}
		}
		return false;
	}
}
