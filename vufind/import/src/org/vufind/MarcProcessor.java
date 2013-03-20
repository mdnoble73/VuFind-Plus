package org.vufind;

import java.io.File;
import java.io.FileInputStream;
import java.io.FileReader;
import java.io.FilenameFilter;
import java.io.IOException;
import java.io.InputStream;
import java.lang.reflect.Method;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Enumeration;
import java.util.HashMap;
import java.util.HashSet;
import java.util.LinkedHashMap;
import java.util.LinkedHashSet;
import java.util.Map;
import java.util.Properties;
import java.util.Set;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.Record;
import org.solrmarc.tools.Utils;

import au.com.bytecode.opencsv.CSVReader;
import bsh.EvalError;
import bsh.Interpreter;

/**
 * Reads Marc Records from a marc file or files Loads the data into fields based
 * on marc.properties and marc_local.properties, Determines if the record is
 * new/updated/unchanged Applies processors to the record Determines which
 * records no longer exist in the marc record Applies processors to the deleted
 * records
 * 
 * Draws heavily from SolrMarc functionality with some simplifications
 * 
 * @author Mark Noble
 * 
 */
public class MarcProcessor {
	private Logger													logger;
	/** list of path to look for property files in */
	protected String[]											propertyFilePaths;
	/** list of path to look for property files in */
	protected String[]											scriptFilePaths;
	private String													marcEncoding		= "UTF8";

	protected String												marcRecordPath;
	private HashMap<String, MarcIndexInfo>	marcIndexInfo		= new HashMap<String, MarcIndexInfo>();

	/** map: keys are solr field names, values inform how to get solr field values */
	HashMap<String, String[]>								marcFieldProps	= new HashMap<String, String[]>();
	
	private String idsToProcess = null;
	
	public HashMap<String, String[]> getMarcFieldProps() {
		return marcFieldProps;
	}

	/**
	 * map of translation maps. keys are names of translation maps; values are the
	 * translation maps (hence, it's a map of maps)
	 */
	HashMap<String, Map<String, String>>	translationMaps			= new HashMap<String, Map<String, String>>();

	/**
	 * map of custom methods. keys are names of custom methods; values are the
	 * methods to call for that custom method
	 */
	private Map<String, Method>						customMethodMap			= new HashMap<String, Method>();

	/**
	 * map of script interpreters. keys are names of scripts; values are the
	 * Interpterers
	 */
	private Map<String, Interpreter>			scriptMap						= new HashMap<String, Interpreter>();

	protected int													recordsProcessed		= 0;
	protected int													maxRecordsToProcess	= -1;
	private PreparedStatement							insertMarcInfoStmt;
	private PreparedStatement							updateMarcInfoStmt;

	private Set<String>								existingEContentIds			= Collections.synchronizedSet(new HashSet<String>());
	private Map<String, Float>				printRatings						= Collections.synchronizedMap(new HashMap<String, Float>());
	private Map<Long, Float>					econtentRatings					= Collections.synchronizedMap(new HashMap<Long, Float>());
	private Map<String, Long>					librarySystemFacets			= Collections.synchronizedMap(new HashMap<String, Long>());
	private Map<Long, String>					libraryIdToSystemFacets	= Collections.synchronizedMap(new HashMap<Long, String>());
	private Map<String, Long>					locationFacets					= Collections.synchronizedMap(new HashMap<String, Long>());
	private Map<String, Long>					eContentLinkRules				= Collections.synchronizedMap(new HashMap<String, Long>());
	private ArrayList<String>					advantageLibraryFacets	= new ArrayList<String>();
	private ArrayList<String>					locationCodes						= new ArrayList<String>();
	private ArrayList<String>					librarySubdomains				= new ArrayList<String>();
	private ArrayList<Long>						libraryIds				= new ArrayList<Long>();
	private HashMap<Long, LibraryIndexingInfo> libraryIndexingInfo = new HashMap<Long, LibraryIndexingInfo>();
	
	private HashMap<Long, LoanRule> loanRules = new HashMap<Long, LoanRule>();
	private ArrayList<LoanRuleDeterminer> loanRuleDeterminers = new ArrayList<LoanRuleDeterminer>();
	private ArrayList<Long> pTypes = new ArrayList<Long>();
	
	private HashMap<String, LexileData> lexileInfo = new HashMap<String, LexileData>();
	
	private String												itemTag;
	private String												locationSubfield;
	private String												urlSubfield;
	private String												sharedEContentLocation;
	private boolean												scrapeItemsForLinks;
	private String												catalogUrl;

	public static final int								RECORD_CHANGED_PRIMARY		= 1;
	public static final int								RECORD_UNCHANGED					= 2;
	public static final int								RECORD_NEW								= 3;
	public static final int								RECORD_DELETED						= 4;
	public static final int								RECORD_CHANGED_SECONDARY	= 1;
	
	public boolean init(String serverName, Ini configIni, Connection vufindConn, Connection econtentConn, Logger logger) {
		this.logger = logger;

		marcRecordPath = configIni.get("Reindex", "marcPath");
		// Get the directory where the marc records are stored.vufindConn
		if (marcRecordPath == null || marcRecordPath.length() == 0) {
			logger.error("Marc Record Path not found in Reindex Settings.  Please specify the path as the marcPath key.");
			return false;
		}

		marcEncoding = configIni.get("Reindex", "marcEncoding");
		if (marcEncoding == null || marcEncoding.length() == 0) {
			logger.error("Marc Encoding not found in Reindex Settings.  Please specify the path as the defaultEncoding key.");
			return false;
		}
		
		idsToProcess = Util.cleanIniValue(configIni.get("Reindex", "idsToProcess"));
		if (idsToProcess == null || idsToProcess.length() == 0){
			idsToProcess = null;
			logger.debug("Did not load a set of idsToProcess");
		}else{
			logger.debug("idsToProcess = " + idsToProcess);
		}

		// Setup where to look for translation maps
		propertyFilePaths = new String[] { "../../sites/" + serverName + "/translation_maps", "../../sites/default/translation_maps" };
		scriptFilePaths = new String[] { "../../sites/" + serverName + "/index_scripts", "../../sites/default/index_scripts" };
		logger.info("Loading marc properties");
		// Load default marc.properties and marc properties for site into Properties
		// Object
		Properties marcProperties = new Properties();
		try {
			File marcPropertiesFile = new File("../../sites/default/conf/marc.properties");

			marcProperties.load(new FileReader(marcPropertiesFile));
			logger.info("Finished reading marc properties file, found " + marcFieldProps.keySet().size() + " entries");
			if (serverName != null) {
				File marcLocalPropertiesFile = new File("../../sites/" + serverName + "/conf/marc_local.properties");
				if (marcLocalPropertiesFile.exists()) {
					marcProperties.load(new FileReader(marcLocalPropertiesFile));
				}
			}
		} catch (IOException e1) {
			// TODO Auto-generated catch block
			e1.printStackTrace();
		}

		// Do additional processing of map properties to determine how each should
		// be processed.
		processMarcFieldProperties(marcProperties);

		String maxRecordsToProcessValue = configIni.get("Reindex", "maxRecordsToProcess");
		if (maxRecordsToProcessValue != null) {
			maxRecordsToProcess = Integer.parseInt(maxRecordsToProcessValue);
		}

		// Load field information for local call numbers
		itemTag = configIni.get("Reindex", "itemTag");
		urlSubfield = configIni.get("Reindex", "itemUrlSubfield");
		locationSubfield = configIni.get("Reindex", "locationSubfield");
		sharedEContentLocation = configIni.get("Reindex", "sharedEContentLocation");
		String scrapeItemsForLinksStr = configIni.get("Reindex", "scrapeItemsForLinks");
		if (scrapeItemsForLinksStr != null) {
			scrapeItemsForLinks = Boolean.parseBoolean(scrapeItemsForLinksStr);
		}
		catalogUrl = configIni.get("Catalog", "url");

		// Load the checksums of any marc records that have been loaded already
		// This allows us to detect whether or not the record is new, has changed,
		// or is deleted
		logger.info("Loading existing checksums for records");
		ReindexProcess.addNoteToCronLog("Loading existing checksums for records");
		try {
			PreparedStatement existingRecordChecksumsStmt = vufindConn.prepareStatement("SELECT * FROM marc_import", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet existingRecordChecksumsRS = existingRecordChecksumsStmt.executeQuery();
			while (existingRecordChecksumsRS.next()) {
				MarcIndexInfo marcInfo = new MarcIndexInfo();
				marcInfo.setChecksum(existingRecordChecksumsRS.getLong("checksum"));
				marcInfo.setBackupChecksum(existingRecordChecksumsRS.getLong("backup_checksum"));
				marcInfo.setEContent(existingRecordChecksumsRS.getBoolean("eContent"));
				marcInfo.setBackupEContent(existingRecordChecksumsRS.getBoolean("backup_eContent"));
				marcIndexInfo.put(existingRecordChecksumsRS.getString("id"), marcInfo);
			}
			existingRecordChecksumsRS.close();
		} catch (SQLException e) {
			logger.error("Unable to load checksums for existing records", e);
			ReindexProcess.addNoteToCronLog("Unable to load checksums for existing records " + e.toString());
			return false;
		}

		// Load the ILS ids of any eContent records that have been loaded so we can
		// suppress the record in the regular content
		logger.info("Loading ils ids for econtent records for suppression");
		ReindexProcess.addNoteToCronLog("Loading ils ids for econtent records for suppression");
		try {
			PreparedStatement existingEContentRecordStmt = econtentConn.prepareStatement("SELECT ilsId FROM econtent_record", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet existingEContentRecordRS = existingEContentRecordStmt.executeQuery();
			while (existingEContentRecordRS.next()) {
				existingEContentIds.add(existingEContentRecordRS.getString(1));
			}
		} catch (SQLException e) {
			logger.error("Unable to load checksums for existing records", e);
			return false;
		}
		
		// Load ratings for print and eContent titles
		logger.info("Loading ratings");
		ReindexProcess.addNoteToCronLog("Loading ratings");
		try {
			PreparedStatement printRatingsStmt = vufindConn
					.prepareStatement(
							"SELECT record_id, avg(rating) as rating from resource inner join user_rating on user_rating.resourceid = resource.id where source = 'VuFind' GROUP BY record_id",
							ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet printRatingsRS = printRatingsStmt.executeQuery();
			while (printRatingsRS.next()) {
				printRatings.put(printRatingsRS.getString("record_id"), printRatingsRS.getFloat("rating"));
			}
			printRatingsRS.close();
			PreparedStatement econtentRatingsStmt = econtentConn
					.prepareStatement(
							"SELECT econtent_record.id, avg(rating) as rating from econtent_record inner join econtent_rating on econtent_rating.recordId = econtent_record.id WHERE ilsId <> '' GROUP BY ilsId",
							ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet econtentRatingsRS = econtentRatingsStmt.executeQuery();
			while (econtentRatingsRS.next()) {
				econtentRatings.put(econtentRatingsRS.getLong(1), econtentRatingsRS.getFloat(2));
			}
			econtentRatingsRS.close();
		} catch (SQLException e) {
			logger.error("Unable to load ratings for resource", e);
			return false;
		}

		// Load information from library table
		try {
			PreparedStatement librarySystemFacetStmt = vufindConn.prepareStatement("SELECT libraryId, subdomain, facetLabel, defaultLibraryFacet, eContentLinkRules, overdriveAdvantageProductsKey, ilsCode from library", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet librarySystemFacetRS = librarySystemFacetStmt.executeQuery();
			while (librarySystemFacetRS.next()) {
				Long libraryId = librarySystemFacetRS.getLong("libraryId");
				String facetLabel = librarySystemFacetRS.getString("facetLabel");
				String defaultLibraryFacet = librarySystemFacetRS.getString("defaultLibraryFacet");
				String librarySubdomain = librarySystemFacetRS.getString("subdomain");
				String ilsCode = librarySystemFacetRS.getString("ilsCode");
				LibraryIndexingInfo libraryInfo = new LibraryIndexingInfo();
				librarySubdomains.add(librarySubdomain);
				libraryInfo.setLibraryId(libraryId);
				libraryInfo.setSubdomain(librarySubdomain);
				libraryInfo.setScoped(defaultLibraryFacet.length() > 0);
				libraryInfo.setFacetLabel(facetLabel);
				libraryInfo.setIlsCode(ilsCode);
				libraryIndexingInfo.put(libraryId, libraryInfo);
				libraryIds.add(libraryId);
				
				librarySystemFacets.put(facetLabel, libraryId);
				String eContentLinkRulesStr = librarySystemFacetRS.getString("eContentLinkRules");
				if (eContentLinkRulesStr != null && eContentLinkRulesStr.length() > 0) {
					eContentLinkRulesStr = ".*(" + eContentLinkRulesStr.toLowerCase() + ").*";
					eContentLinkRules.put(eContentLinkRulesStr, librarySystemFacetRS.getLong("libraryId"));
				}
				libraryIdToSystemFacets.put(librarySystemFacetRS.getLong("libraryId"), facetLabel);
				String overdriveAdvantageProductsKey = librarySystemFacetRS.getString("overdriveAdvantageProductsKey");
				if (overdriveAdvantageProductsKey != null && overdriveAdvantageProductsKey.length() > 0){
					advantageLibraryFacets.add(facetLabel);
				}
			}
			logger.debug("Loaded " + librarySubdomains.size() + " librarySubdomains");
		} catch (SQLException e) {
			logger.error("Unable to load library System Facet information", e);
			return false;
		}
		
		try {
			PreparedStatement locationFacetStmt = vufindConn.prepareStatement("SELECT locationId, libraryId, facetLabel, defaultLocationFacet, code from location", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet locationFacetRS = locationFacetStmt.executeQuery();
			while (locationFacetRS.next()) {
				Long libraryId = locationFacetRS.getLong("libraryId");
				Long locationId = locationFacetRS.getLong("locationId");
				String facetLabel = locationFacetRS.getString("facetLabel");
				String code = locationFacetRS.getString("code").trim();
				locationCodes.add(code);
				String defaultLocationFacet = locationFacetRS.getString("defaultLocationFacet");
				//logger.debug(locationFacetRS.getString("facetLabel") + " = " + locationFacetRS.getLong("locationId"));
				locationFacets.put(facetLabel, locationId);
				//Load information for indexing items
				LibraryIndexingInfo libraryInfo = libraryIndexingInfo.get(libraryId);
				
				LocationIndexingInfo locationInfo = new LocationIndexingInfo();
				locationInfo.setLibraryId(libraryId);
				locationInfo.setLocationId(locationId);
				locationInfo.setFacetLabel(facetLabel);
				locationInfo.setScoped(defaultLocationFacet.length() > 0);
				locationInfo.setCode(code);
				libraryInfo.addLocation(locationInfo);
				
			}
			logger.debug("Loaded " + locationCodes.size() + " locations");
		} catch (SQLException e) {
			logger.error("Unable to load location Facet information", e);
			return false;
		}
		
		//Load lexile data
		String lexileExportPath = configIni.get("Reindex", "lexileExportPath");
		if (lexileExportPath != null && lexileExportPath.length() > 0){
			loadLexileInfo(lexileExportPath);
		}
		
		//Load loan rules
		try {
			PreparedStatement pTypesStmt = vufindConn.prepareStatement("SELECT pType from ptype", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet pTypesRS = pTypesStmt.executeQuery();
			while (pTypesRS.next()) {
				pTypes.add(pTypesRS.getLong("pType"));
			}
			
			PreparedStatement loanRuleStmt = vufindConn.prepareStatement("SELECT * from loan_rules", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			ResultSet loanRulesRS = loanRuleStmt.executeQuery();
			while (loanRulesRS.next()) {
				LoanRule loanRule = new LoanRule();
				loanRule.setLoanRuleId(loanRulesRS.getLong("loanRuleId"));
				loanRule.setName(loanRulesRS.getString("name"));
				loanRule.setHoldable(loanRulesRS.getBoolean("holdable"));
				
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
				loanRuleDeterminer.setRowNumber(loanRuleDeterminersRS.getLong("rowNumber"));
				
				loanRuleDeterminers.add(loanRuleDeterminer);
			}
			
			logger.debug("Loaded " + loanRuleDeterminers.size() + " loan rule determiner");
		} catch (SQLException e) {
			logger.error("Unable to load loan rules", e);
			return false;
		}

		// Setup additional statements
		try {
			insertMarcInfoStmt = vufindConn.prepareStatement("INSERT INTO marc_import (id, checksum, eContent) VALUES (?, ?, ?)");
			updateMarcInfoStmt = vufindConn
					.prepareStatement("UPDATE marc_import SET checksum = ?, backup_checksum = ?, eContent = ?, backup_eContent = ? WHERE id = ?");
		} catch (SQLException e) {
			logger.error("Unable to setup statements for updating marc_import table", e);
			return false;
		}
		ReindexProcess.addNoteToCronLog("Finished setting up MarcProcessor");
		return true;
	}

	private void loadLexileInfo(String lexileExportPath) {
		File lexileExportFile = new File(lexileExportPath);
		if (lexileExportFile.exists()){
			try {
				CSVReader reader = new CSVReader(new FileReader(lexileExportFile), '\t');
				String [] nextLine;
				//Skip the first line
				reader.readNext();
				while ((nextLine = reader.readNext()) != null) {
					if (nextLine.length >= 10){
						LexileData lexileData = new LexileData();
						lexileData.setIsbn(nextLine[3]);
						lexileData.setLexileCode(nextLine[4]);
						if (nextLine[5] != null && nextLine[5].length() > 0){
							lexileData.setLexileScore(nextLine[5]);
						}else{
							lexileData.setLexileScore(null);
						}
						lexileData.setSeries(nextLine[9]);
						lexileData.setAwards(nextLine[10]);
						lexileInfo.put(lexileData.getIsbn(), lexileData);
					}
				}
				reader.close();
				ReindexProcess.addNoteToCronLog("Finished loading lexile information.  Found " + lexileInfo.size() + " titles in lexile export.");
			} catch (Exception e) {
				// TODO Auto-generated catch block
				ReindexProcess.addNoteToCronLog("Error loading information from lexile export " + e.toString());
				logger.error("Error loading information from lexile export ", e);
			}
		}else{
			ReindexProcess.addNoteToCronLog("Could not find lexile information for path " + lexileExportPath);
		}
	}

	public Set<String> getExistingEContentIds() {
		return existingEContentIds;
	}

	public Map<String, Float> getPrintRatings() {
		return printRatings;
	}

	public Map<Long, Float> getEcontentRatings() {
		return econtentRatings;
	}

	/**
	 * Parse the properties
	 * 
	 * @param marcProperties
	 */
	private void processMarcFieldProperties(Properties marcProperties) {
		Enumeration<?> en = marcProperties.propertyNames();

		while (en.hasMoreElements()) {
			String propName = (String) en.nextElement();
			// ignore map, pattern_map; they are handled separately
			if (!propName.startsWith("map") && !propName.startsWith("pattern_map")) {
				String propValue = marcProperties.getProperty(propName);
				String fieldDef[] = new String[4];
				fieldDef[0] = propName;
				fieldDef[3] = null;
				if (propValue.startsWith("\"")) {
					// value is a constant if it starts with a quote
					fieldDef[1] = "constant";
					fieldDef[2] = propValue.trim().replaceAll("\"", "");
				} else
				// not a constant
				{
					// split it into two pieces at first comma or space
					String values[] = propValue.split("[, ]+", 2);
					if (values[0].startsWith("custom") || values[0].startsWith("script")) {
						fieldDef[1] = values[0];

						// parse sections of custom value assignment line in
						// _index.properties file
						String lastValues[];
						// get rid of empty parens
						if (values[1].indexOf("()") != -1) values[1] = values[1].replace("()", "");

						// index of first open paren after custom method name
						int parenIx = values[1].indexOf('(');

						// index of first unescaped comma after method name
						int commaIx = Utils.getIxUnescapedComma(values[1]);

						if (parenIx != -1 && commaIx != -1 && parenIx < commaIx) {
							// remainder should be split after close paren
							// followed by comma (optional spaces in between)
							lastValues = values[1].trim().split("\\) *,", 2);

							// Reattach the closing parenthesis:
							if (lastValues.length == 2) lastValues[0] += ")";
						} else
							// no parens - split comma preceded by optional spaces
							lastValues = values[1].trim().split(" *,", 2);

						fieldDef[2] = lastValues[0].trim();

						fieldDef[3] = lastValues.length > 1 ? lastValues[1].trim() : null;
						// is this a translation map?
						if (fieldDef[3] != null && fieldDef[3].contains("map")) {
							try {
								fieldDef[3] = loadTranslationMap(marcProperties, fieldDef[3]);
							} catch (IllegalArgumentException e) {
								logger.error("Unable to find file containing specified translation map (" + fieldDef[3] + ")");
								throw new IllegalArgumentException("Error: Problems reading specified translation map (" + fieldDef[3] + ")");
							}
						}
					} // end custom
					else if (values[0].equals("xml") || values[0].equals("raw") || values[0].equals("date") || values[0].equals("json") || values[0].equals("json2")
							|| values[0].equals("index_date") || values[0].equals("era")) {
						fieldDef[1] = "std";
						fieldDef[2] = values[0];
						fieldDef[3] = values.length > 1 ? values[1].trim() : null;
						// NOTE: assuming no translation map here
						if (fieldDef[2].equals("era") && fieldDef[3] != null) {
							try {
								fieldDef[3] = loadTranslationMap(marcProperties, fieldDef[3]);
							} catch (IllegalArgumentException e) {
								logger.error("Unable to find file containing specified translation map (" + fieldDef[3] + ")");
								throw new IllegalArgumentException("Error: Problems reading specified translation map (" + fieldDef[3] + ")");
							}
						}
					} else if (values[0].equalsIgnoreCase("FullRecordAsXML") || values[0].equalsIgnoreCase("FullRecordAsMARC")
							|| values[0].equalsIgnoreCase("FullRecordAsJson") || values[0].equalsIgnoreCase("FullRecordAsJson2")
							|| values[0].equalsIgnoreCase("FullRecordAsText") || values[0].equalsIgnoreCase("DateOfPublication")
							|| values[0].equalsIgnoreCase("DateRecordIndexed")) {
						fieldDef[1] = "std";
						fieldDef[2] = values[0];
						fieldDef[3] = values.length > 1 ? values[1].trim() : null;
						// NOTE: assuming no translation map here
					} else if (values.length == 1) {
						fieldDef[1] = "all";
						fieldDef[2] = values[0];
						fieldDef[3] = null;
					} else
					// other cases of field definitions
					{
						String values2[] = values[1].trim().split("[ ]*,[ ]*", 2);
						fieldDef[1] = "all";
						if (values2[0].equals("first") || (values2.length > 1 && values2[1].equals("first"))) fieldDef[1] = "first";

						if (values2[0].startsWith("join")) fieldDef[1] = values2[0];

						if ((values2.length > 1 && values2[1].startsWith("join"))) fieldDef[1] = values2[1];

						if (values2[0].equalsIgnoreCase("DeleteRecordIfFieldEmpty") || (values2.length > 1 && values2[1].equalsIgnoreCase("DeleteRecordIfFieldEmpty")))
							fieldDef[1] = "DeleteRecordIfFieldEmpty";

						fieldDef[2] = values[0];
						fieldDef[3] = null;

						// might we have a translation map?
						if (!values2[0].equals("all") && !values2[0].equals("first") && !values2[0].startsWith("join")
								&& !values2[0].equalsIgnoreCase("DeleteRecordIfFieldEmpty")) {
							fieldDef[3] = values2[0].trim();
							if (fieldDef[3] != null) {
								try {
									fieldDef[3] = loadTranslationMap(marcProperties, fieldDef[3]);
								} catch (IllegalArgumentException e) {
									logger.error("Unable to find file containing specified translation map (" + fieldDef[3] + ")");
									throw new IllegalArgumentException("Error: Problems reading specified translation map (" + fieldDef[3] + ")");
								}
							}
						}
					} // other cases of field definitions

				} // not a constant

				marcFieldProps.put(propName, fieldDef);

			} // if not map or pattern_map

		} // while enumerating through property names
	}

	/**
	 * load the translation map into transMapMap
	 * 
	 * @param indexProps
	 *          _index.properties as Properties object
	 * @param translationMapSpec
	 *          the specification of a translation map - could be name of a
	 *          _map.properties file, or something in _index properties ...
	 * @return the name of the translation map
	 */
	protected String loadTranslationMap(Properties indexProps, String translationMapSpec) {
		if (translationMapSpec.length() == 0) return null;

		String mapName = null;
		String mapKeyPrefix = null;
		if (translationMapSpec.startsWith("(") && translationMapSpec.endsWith(")")) {
			// translation map entries are in passed Properties object
			mapName = translationMapSpec.replaceAll("[\\(\\)]", "");
			mapKeyPrefix = mapName;
			loadTranslationMapValues(indexProps, mapName, mapKeyPrefix);
		} else {
			// translation map is a separate file
			String transMapFname = null;
			if (translationMapSpec.contains("(") && translationMapSpec.endsWith(")")) {
				String mapSpec[] = translationMapSpec.split("(//s|[()])+");
				transMapFname = mapSpec[0];
				mapName = mapSpec[1];
				mapKeyPrefix = mapName;
			} else {
				transMapFname = translationMapSpec;
				mapName = translationMapSpec.replaceAll(".properties", "");
				mapKeyPrefix = "";
			}

			if (findMap(mapName) == null) loadTranslationMapValues(transMapFname, mapName, mapKeyPrefix);
		}

		return mapName;
	}

	/**
	 * Get the appropriate Map object from populated transMapMap
	 * 
	 * @param mapName
	 *          the name of the translation map to find
	 * @return populated Map object
	 */
	public Map<String, String> findMap(String mapName) {
		if (mapName.startsWith("pattern_map:")) mapName = mapName.substring("pattern_map:".length());

		if (translationMaps.containsKey(mapName)) {
			return (translationMaps.get(mapName));
		}else{
			loadTranslationMapValues(mapName + ".properties", mapName, "");
			return (translationMaps.get(mapName));
		}
	}

	/**
	 * Load translation map into transMapMap. Look for translation map in site
	 * specific directory first; if not found, look in solrmarc top directory
	 * 
	 * @param transMapName
	 *          name of translation map file to load
	 * @param mapName
	 *          - the name of the Map to go in transMapMap (the key in
	 *          transMapMap)
	 * @param mapKeyPrefix
	 *          - any prefix on individual Map keys (entries in the value in
	 *          transMapMap)
	 */
	public void loadTranslationMapValues(String transMapName, String mapName, String mapKeyPrefix) {
		Properties props = null;
		props = Utils.loadProperties(propertyFilePaths, transMapName);
		logger.debug("Loading Custom Map: " + transMapName + " found " + props.size() + " properties");
		loadTranslationMapValues(props, mapName, mapKeyPrefix);
	}

	/**
	 * populate transMapMap
	 * 
	 * @param transProps
	 *          - the translation map as a Properties object
	 * @param mapName
	 *          - the name of the Map to go in transMapMap (the key in
	 *          transMapMap)
	 * @param mapKeyPrefix
	 *          - any prefix on individual Map keys (entries in the value in
	 *          transMapMap)
	 */
	private void loadTranslationMapValues(Properties transProps, String mapName, String mapKeyPrefix) {
		Enumeration<?> en = transProps.propertyNames();
		while (en.hasMoreElements()) {
			String property = (String) en.nextElement();
			if (mapKeyPrefix.length() == 0 || property.startsWith(mapKeyPrefix)) {
				String mapKey = property.substring(mapKeyPrefix.length());
				if (mapKey.startsWith(".")) mapKey = mapKey.substring(1);
				String value = transProps.getProperty(property);
				value = value.trim();
				if (value.equals("null")) value = null;

				Map<String, String> valueMap;
				if (translationMaps.containsKey(mapName)){
					valueMap = translationMaps.get(mapName);
				} else {
					valueMap = new LinkedHashMap<String, String>();
					translationMaps.put(mapName, valueMap);
				}

				valueMap.put(mapKey, value);
			}
		}
	}

	/**
	 * Process the marc record and extract all fields from the raw marc record
	 * according to field rules.
	 * 
	 * @param marcRecord
	 * @param logger
	 * @return
	 */
	protected MarcRecordDetails mapMarcInfo(Record marcRecord, Logger logger) {
		MarcRecordDetails basicInfo = new MarcRecordDetails(this, marcRecord, logger);
		return basicInfo;
	}

	protected boolean processMarcFiles(final ArrayList<IMarcRecordProcessor> recordProcessors, final Logger logger) {
		try {
			// Get a list of Marc files to process
			File marcRecordDirectory = new File(marcRecordPath);
			File[] marcFiles;
			if (marcRecordDirectory.isDirectory()) {
				marcFiles = marcRecordDirectory.listFiles(new FilenameFilter() {
					@Override
					public boolean accept(File dir, String name) {
						if (name.matches("(?i).*?\\.(marc|mrc)")) {
							return true;
						} else {
							return false;
						}
					}
				});
			} else {
				marcFiles = new File[] { marcRecordDirectory };
			}

			// Loop through each marc record
			for (final File marcFile : marcFiles) {
				processMarcFile(recordProcessors, logger, marcFile);
			}
			return true;
		} catch (Exception e) {
			logger.error("Unable to process marc files", e);
			return false;
		} catch (Error e) {
			logger.error("Error processing marc files", e);
			return false;
		}
	}

	private void processMarcFile(ArrayList<IMarcRecordProcessor> recordProcessors, Logger logger, File marcFile) {
		try {
			logger.info("Processing file " + marcFile.toString());
			ReindexProcess.addNoteToCronLog("Processing file " + marcFile.toString());
			// Open the marc record with Marc4j
			InputStream input = new FileInputStream(marcFile);
			MarcReader reader = new MarcPermissiveStreamReader(input, true, true, marcEncoding);
			int recordNumber = 0;
			while (reader.hasNext()) {
				recordNumber++;
				try {
					// Loop through each record
					Record record = reader.next();
					// Process record
					MarcRecordDetails marcInfo = mapMarcInfo(record, logger);
					if (marcInfo != null) {
						// Check to see if the record has been loaded before
						int recordStatus;
						String id = marcInfo.getId();
						if (id == null) {
							System.out.println("Could not load id for marc record " + recordNumber);
							System.out.println(marcFieldProps.get("id").toString());
							continue;
						}
						//Check the list of ids to process if any to see if we should skip this recod
						if (idsToProcess != null){
							if (!id.matches(idsToProcess)){
								continue;
							}else{
								logger.debug("processing record " + id + " because it is in the list of ids to process " + idsToProcess);
							}
						}
						MarcIndexInfo marcIndexedInfo = null;
						String marcRecordId = marcInfo.getId();
						if (marcIndexInfo.containsKey(marcInfo.getId())) {
							marcIndexedInfo = marcIndexInfo.get(marcInfo.getId());
							if (marcInfo.getChecksum() != marcIndexedInfo.getChecksum()){
								//logger.debug("Record is changed - checksum");
								recordStatus = RECORD_CHANGED_PRIMARY;
							}else if (marcInfo.isEContent() != marcIndexedInfo.isEContent()){
								//logger.debug("Record is changed - econtent");
								recordStatus = RECORD_CHANGED_PRIMARY;
							}else if (marcInfo.getChecksum() != marcIndexedInfo.getBackupChecksum()){
								//logger.debug("Record is changed - backup checksum");
								recordStatus = RECORD_CHANGED_SECONDARY;
							}else if (marcInfo.isEContent() != marcIndexedInfo.isBackupEContent()) {
								//logger.debug("Record is changed - backup econtent");
								recordStatus = RECORD_CHANGED_SECONDARY;
							} else {
								// logger.info("Record is unchanged");
								recordStatus = RECORD_UNCHANGED;
							}
						} else {
							logger.debug("Record is new");
							recordStatus = RECORD_NEW;
						}
						
						for (IMarcRecordProcessor processor : recordProcessors) {
							//logger.debug(recordNumber + " - " + processor.getClass().getName() + " - " + marcInfo.getId());
							processor.processMarcRecord(this, marcInfo, recordStatus, logger);
						}

						updateMarcRecordChecksum(marcRecordId, marcInfo, recordStatus, marcIndexedInfo);
					}
					marcInfo = null;
					recordsProcessed++;
					if (maxRecordsToProcess != -1 && recordsProcessed > maxRecordsToProcess) {
						ReindexProcess.addNoteToCronLog("Stopping processing because maximum number of records to process was reached.");
						logger.debug("Stopping processing because maximum number of records to process was reached.");
						break;
					}
					if (recordsProcessed % 1000 == 0){
						ReindexProcess.updateLastUpdateTime();
					}
				} catch (Exception e) {
					ReindexProcess.addNoteToCronLog("Exception processing record " + recordNumber + " - " + e.toString());
					logger.error("Exception processing record " + recordNumber, e);
				} catch (Error e) {
					ReindexProcess.addNoteToCronLog("Error processing record " + recordNumber + " - " + e.toString());
					logger.error("Error processing record " + recordNumber, e);
				}
			}
			input.close();
			logger.info("Finished processing file " + marcFile.toString() + " found " + recordNumber + " records");
			ReindexProcess.addNoteToCronLog("Finished processing file " + marcFile.toString() + " found " + recordNumber + " records");
		} catch (Exception e) {
			logger.error("Error processing file " + marcFile.toString(), e);
		}
	}

	private void updateMarcRecordChecksum(String recordId, MarcRecordDetails marcInfo, int recordStatus, MarcIndexInfo marcIndexedInfo) throws SQLException {
		try {
			// Update the checksum in the database
			if (recordStatus == RECORD_CHANGED_PRIMARY || recordStatus == RECORD_CHANGED_SECONDARY) {
				updateMarcInfoStmt.setLong(1, marcInfo.getChecksum());
				updateMarcInfoStmt.setLong(2, marcIndexedInfo.getChecksum());
				updateMarcInfoStmt.setInt(3, marcInfo.isEContent() ? 1 : 0);
				updateMarcInfoStmt.setInt(4, marcIndexedInfo.isEContent() ? 1 : 0);
				updateMarcInfoStmt.setString(5, recordId);
				updateMarcInfoStmt.executeUpdate();
			} else if (recordStatus == RECORD_NEW) {
				insertMarcInfoStmt.setString(1, recordId);
				insertMarcInfoStmt.setLong(2, marcInfo.getChecksum());
				insertMarcInfoStmt.setInt(3, marcInfo.isEContent() ? 1 : 0);
				insertMarcInfoStmt.executeUpdate();
			}
		} catch (Exception e) {
			ReindexProcess.addNoteToCronLog("Error updating marc checksum for " + recordId + " marcInfo id is " + marcInfo.getId());
		}
	}

	public Map<String, Method> getCustomMethodMap() {
		return customMethodMap;
	}

	/**
	 * First checks whether a given BeanShell script has been already loaded, and
	 * if so returns the BeanShell Interpreter created from that script. Is it
	 * hasn't been loaded this function will read in the named script file, create
	 * a new BeanShell Interpreter, and have that Interpreter process the named
	 * script.
	 * 
	 * @param scriptFileName
	 * @return
	 */
	public Interpreter getInterpreterForScript(String scriptFileName) {
		if (scriptMap.containsKey(scriptFileName)) {
			return (scriptMap.get(scriptFileName));
		}
		Interpreter bsh = new Interpreter();
		bsh.setClassLoader(this.getClass().getClassLoader());
		InputStream script = Utils.getPropertyFileInputStream(scriptFilePaths, scriptFileName);
		String scriptContents;
		try {
			scriptContents = Utils.readStreamIntoString(script);
			bsh.eval(scriptContents);
		} catch (IOException e) {
			throw new IllegalArgumentException("Unable to read script: " + scriptFileName, e);
		} catch (EvalError e) {
			throw new IllegalArgumentException("Unable to evaluate script: " + scriptFileName, e);
		}
		scriptMap.put(scriptFileName, bsh);
		return (bsh);
	}

	public String getItemTag() {
		return itemTag;
	}

	public String getLocationSubfield() {
		return locationSubfield;
	}

	public String getUrlSubfield() {
		return urlSubfield;
	}

	public String getSharedEContentLocation() {
		return sharedEContentLocation;
	}

	public Long getLibrarySystemIdFromFacet(String librarySystemFacet) {
		return librarySystemFacets.get(librarySystemFacet);
	}
	public String getLibrarySystemFacetForId(Long libraryId){
		return libraryIdToSystemFacets.get(libraryId);
	}
	public Long getLocationIdFromFacet(String locationFacet){
		return locationFacets.get(locationFacet);
	}
	public Long getLibraryIdForLink(String link){
		String lowerLink = link.toLowerCase();
		for (String curRule : eContentLinkRules.keySet()){
			if (lowerLink.matches(curRule)){
				return eContentLinkRules.get(curRule);
			}
		}
		return -1L;
	}

	public boolean isScrapeItemsForLinks() {
		return scrapeItemsForLinks;
	}

	public String getCatalogUrl() {
		return catalogUrl;
	}

	public LexileData getLexileDataForIsbn(String isbn) {
		return lexileInfo.get(isbn);
	}

	public Set<String> getGetRatingFacet(Float rating) {
		Set<String> ratingFacet = new HashSet<String>();
		if (rating >= 4.75) {
			ratingFacet.add("fiveStar");
		}
		if (rating >= 4) {
			ratingFacet.add("fourStar");
		}
		if (rating >= 3) {
			ratingFacet.add("threeStar");
		}
		if (rating >= 2) {
			ratingFacet.add("twoStar");
		}
		if (rating >= 0.0001) {
			ratingFacet.add("oneStar");
		}
		if (ratingFacet.size() == 0){
			ratingFacet.add("Unrated");
		}
		return ratingFacet;
	}

	public ArrayList<String> getAdvantageLibraryFacets() {
		return advantageLibraryFacets;
	}

	public LibraryIndexingInfo getLibraryIndexingInfo(Long libraryId) {
		return libraryIndexingInfo.get(libraryId);
	}

	private HashMap<String, LocationIndexingInfo> locationIndexingInfoByCode = new HashMap<String, LocationIndexingInfo>();
	public LocationIndexingInfo getLocationIndexingInfo(String locationCode) {
		if (locationIndexingInfoByCode.containsKey(locationCode)){
			return locationIndexingInfoByCode.get(locationCode);
		}
		for (LibraryIndexingInfo libraryInfo : libraryIndexingInfo.values()){
			LocationIndexingInfo locationInfo = libraryInfo.getLocationIndexingInfo(locationCode);
			if (locationInfo != null){
				locationIndexingInfoByCode.put(locationCode, locationInfo);
				return locationInfo;
			}
		}
		return null;
	}

	public ArrayList<String> getLocationCodes() {
		return locationCodes;
	}
	
	public ArrayList<String> getLibrarySubdomains() {
		return librarySubdomains;
	}
	
	public ArrayList<Long> getLibraryIds() {
		return libraryIds;
	}

	public boolean isUsableByPType(String iType, String pType) {
		//TODO: Check loan rules to see if the iType is available to the specified ptype
		return true;
	}

	private HashMap<String, LinkedHashSet<String>> ptypesByItypeAndLocation = new HashMap<String, LinkedHashSet<String>>();
	public LinkedHashSet<String> getCompatiblePTypes(String iType, String locationCode) {
		String cacheKey = iType + ":" + locationCode;
		if (ptypesByItypeAndLocation.containsKey(cacheKey)){
			return ptypesByItypeAndLocation.get(cacheKey);
		}
		//logger.debug("getCompatiblePTypes for " + cacheKey);
		LinkedHashSet<String> result = new LinkedHashSet<String>();
		Long iTypeLong = Long.parseLong(iType);
		//Loop through all patron types to see if the item is holdable
		for (Long pType : pTypes){
			//logger.debug("  Checking pType " + pType);
			//Loop through the loan rules to see if this itype can be used based on the location code
			for (LoanRuleDeterminer curDeterminer : loanRuleDeterminers){
				//logger.debug("   Checking determiner " + curDeterminer.getRowNumber() + " " + curDeterminer.getLocation());
				//Make sure the location matchs
				if (curDeterminer.matchesLocation(locationCode)){
					//logger.debug("    " + curDeterminer.getRowNumber() + " matches location");
					if (curDeterminer.getItemType().equals("999") || curDeterminer.getItemTypes().contains(iTypeLong)){
						//logger.debug("    " + curDeterminer.getRowNumber() + " matches iType");
						if (curDeterminer.getPatronType().equals("999") || curDeterminer.getPatronTypes().contains(pType)){
							//logger.debug("    " + curDeterminer.getRowNumber() + " matches pType");
							LoanRule loanRule = loanRules.get(curDeterminer.getLoanRuleId());
							if (loanRule.getHoldable().equals(Boolean.TRUE)){
								result.add(pType.toString());
							}
							//We got a match, stop processig
							//logger.debug("    using determiner " + curDeterminer.getRowNumber() + " for ptype " + pType);
							break;
						}
					}
				}
			}
		}
		//logger.debug("  " + result.size() + " ptypes can use this");
		ptypesByItypeAndLocation.put(cacheKey, result);
		return result;
	}

	private HashSet<String> allPtypes;
	public Set<String> getAllPTypes() {
		if (allPtypes != null){
			return allPtypes;
		}
		allPtypes = new LinkedHashSet<String>();
		for (Long pType : pTypes){
			allPtypes.add(pType.toString());
		}
		return allPtypes;
	}

}
