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
import java.util.Enumeration;
import java.util.HashMap;
import java.util.HashSet;
import java.util.LinkedHashMap;
import java.util.Map;
import java.util.Properties;

import org.apache.log4j.Logger;
import org.econtent.DetectionSettings;
import org.ini4j.Ini;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.Record;
import org.solrmarc.tools.Utils;

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
	private Logger								logger;
	/** list of path to look for property files in */
	protected String[]						propertyFilePaths;
	/** list of path to look for property files in */
	protected String[]						scriptFilePaths;

	protected String							marcRecordPath;
	private HashMap<String, Long>	marcChecksums = new HashMap<String, Long>();

	/** map: keys are solr field names, values inform how to get solr field values */
	HashMap<String, String[]>			marcFieldProps	= new HashMap<String, String[]>();
	
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
	private Map<String, Interpreter>			scriptMap						= new HashMap<String, Interpreter>();;

	protected int													recordsProcessed		= 0;
	protected int													maxRecordsToProcess	= -1;
	private PreparedStatement							insertChecksumStmt;
	private PreparedStatement							updateChecksumStmt;
	
	private HashSet<String> existingEContentIds			= new HashSet<String>(); 
	private HashMap<String, Float> printRatings 		= new HashMap<String, Float>();
	private HashMap<String, Float> econtentRatings	= new HashMap<String, Float>();
	private ArrayList<DetectionSettings> detectionSettings = new ArrayList<DetectionSettings>(); 

	public static final int								RECORD_CHANGED			= 1;
	public static final int								RECORD_UNCHANGED		= 2;
	public static final int								RECORD_NEW					= 3;
	public static final int								RECORD_DELETED			= 4;

	public boolean init(String serverName, Ini configIni, Connection vufindConn, Connection econtentConn, Logger logger) {
		this.logger = logger;

		marcRecordPath = configIni.get("Reindex", "marcPath");
		// Get the directory where the marc records are stored.vufindConn
		if (marcRecordPath == null || marcRecordPath.length() == 0) {
			logger.error("Marc Record Path not found in General Settings.  Please specify the path as the marcRecordPath key.");
			return false;
		}

		// Setup where to look for translation maps
		propertyFilePaths = new String[] { "../../sites/" + serverName + "/translation_maps", "../../sites/default/translation_maps" };
		scriptFilePaths = new String[] { "../../sites/" + serverName + "/index_scripts", "../../sites/default/index_scripts" };
		System.out.println("Loading marc properties");
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

		// Load the checksums of any marc records that have been loaded already
		// This allows us to detect whether or not the record is new, has changed,
		// or is deleted
		logger.info("Loading existing checksums for records");
		try {
			PreparedStatement existingRecordChecksumsStmt = vufindConn.prepareStatement("SELECT * FROM marc_import");
			ResultSet existingRecordChecksumsRS = existingRecordChecksumsStmt.executeQuery();
			while (existingRecordChecksumsRS.next()) {
				marcChecksums.put(existingRecordChecksumsRS.getString("id"), existingRecordChecksumsRS.getLong("checksum"));
			}
		} catch (SQLException e) {
			logger.error("Unable to load checksums for existing records", e);
			return false;
		}
		
		//Load the ILS ids of any eContent records that have been loaded so we can 
		//suppress the record in the regular content
		logger.info("Loading ils ids for econtent records for suppression");
		try {
			PreparedStatement existingEContentRecordStmt = econtentConn.prepareStatement("SELECT ilsId FROM econtent_record");
			ResultSet existingEContentRecordRS = existingEContentRecordStmt.executeQuery();
			while (existingEContentRecordRS.next()) {
				existingEContentIds.add(existingEContentRecordRS.getString("ilsId"));
			}
		} catch (SQLException e) {
			logger.error("Unable to load checksums for existing records", e);
			return false;
		}
		
		// Load detection settings to determine if a record is eContent. 
		logger.info("Loading record detection settings");
		try {
			PreparedStatement eContentDetectionSettingsStmt = econtentConn.prepareStatement("SELECT * FROM econtent_record_detection_settings");
			ResultSet eContentDetectionSettingsRS = eContentDetectionSettingsStmt.executeQuery();
			while (eContentDetectionSettingsRS.next()) {
				DetectionSettings settings = new DetectionSettings();
				settings.setFieldSpec(eContentDetectionSettingsRS.getString("fieldSpec"));
				settings.setValueToMatch(eContentDetectionSettingsRS.getString("valueToMatch"));
				settings.setSource(eContentDetectionSettingsRS.getString("source"));
				settings.setAccessType(eContentDetectionSettingsRS.getString("accessType"));
				settings.setItem_type(eContentDetectionSettingsRS.getString("item_type"));
				settings.setAdd856FieldsAsExternalLinks(eContentDetectionSettingsRS.getBoolean("add856FieldsAsExternalLinks"));
				detectionSettings.add(settings);
			}
		} catch (SQLException e) {
			logger.error("Unable to load detection settings for eContent.", e);
			return false;
		}
		
		// Load ratings for print and eContent titles
		try{
			PreparedStatement printRatingsStmt = vufindConn.prepareStatement("SELECT record_id, avg(rating) as rating from resource inner join user_rating on user_rating.resourceid = resource.id where source = 'VuFind' GROUP BY record_id");
			ResultSet printRatingsRS = printRatingsStmt.executeQuery();
			while (printRatingsRS.next()){
				printRatings.put(printRatingsRS.getString("record_id"), printRatingsRS.getFloat("rating"));
			}
			PreparedStatement econtentRatingsStmt = econtentConn.prepareStatement("SELECT ilsId, avg(rating) as rating from econtent_record inner join econtent_rating on econtent_rating.recordId = econtent_record.id WHERE ilsId <> '' GROUP BY ilsId");
			ResultSet econtentRatingsRS = econtentRatingsStmt.executeQuery();
			while (econtentRatingsRS.next()){
				econtentRatings.put(econtentRatingsRS.getString("ilsId"), econtentRatingsRS.getFloat("rating"));
			}
		} catch (SQLException e) {
			logger.error("Unable to load ratings for resource", e);
			return false;
		}
		
		// Setup additional statements 
		try {
			insertChecksumStmt = vufindConn.prepareStatement("INSERT INTO marc_import (id, checksum) VALUES (?, ?)");
			updateChecksumStmt = vufindConn.prepareStatement("UPDATE marc_import SET checksum = ? WHERE id = ?");
		} catch (SQLException e) {
			logger.error("Unable to setup statements for updating marc_import table", e);
			return false;
		}
		return true;
	}

	public HashSet<String> getExistingEContentIds() {
		return existingEContentIds;
	}

	public HashMap<String, Float> getPrintRatings() {
		return printRatings;
	}

	public HashMap<String, Float> getEcontentRatings() {
		return econtentRatings;
	}
	
	public ArrayList<DetectionSettings> getDetectionSettings(){
		return detectionSettings;
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

		if (translationMaps.containsKey(mapName)) return (translationMaps.get(mapName));

		return null;
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
	private void loadTranslationMapValues(String transMapName, String mapName, String mapKeyPrefix) {
		Properties props = null;
		props = Utils.loadProperties(propertyFilePaths, transMapName);
		logger.debug("Loading Custom Map: " + transMapName);
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
				if (translationMaps.containsKey(mapName))
					valueMap = translationMaps.get(mapName);
				else {
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
		MarcRecordDetails basicInfo = new MarcRecordDetails();
		if (!basicInfo.mapRecord(this, marcRecord, logger)) {
			logger.error("Could not find item for record");
			return null;
		} else {
			return basicInfo;
		}
	}

	protected boolean processMarcFiles(ArrayList<IMarcRecordProcessor> recordProcessors, Logger logger) {
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
			for (File marcFile : marcFiles) {
				try {
					logger.info("Processing file " + marcFile.toString());
					// Open the marc record with Marc4j
					InputStream input = new FileInputStream(marcFile);
					MarcReader reader = new MarcPermissiveStreamReader(input, true, true);
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
								if (id == null){
									System.out.println("Could not load id for marc record " + recordNumber);
									System.out.println(marcFieldProps.get("id").toString());
									System.exit(1);
								}
								if (marcChecksums.containsKey(marcInfo.getId())) {
									Long lastChecksum = marcChecksums.get(marcInfo.getId());
									if (marcInfo.getChecksum() != lastChecksum) {
										logger.info("Record is changed");
										recordStatus = RECORD_CHANGED;
									} else {
										logger.info("Record is unchanged");
										recordStatus = RECORD_UNCHANGED;
									}
								} else {
									logger.info("Record is new");
									recordStatus = RECORD_NEW;
								}

								for (IMarcRecordProcessor processor : recordProcessors) {
									processor.processMarcRecord(this, marcInfo, recordStatus, logger);
								}

								// Update the checksum in the database
								if (recordStatus == RECORD_CHANGED) {
									updateChecksumStmt.setLong(1, marcInfo.getChecksum());
									updateChecksumStmt.setString(2, marcInfo.getId());
									updateChecksumStmt.executeUpdate();
								} else if (recordStatus == RECORD_NEW) {
									insertChecksumStmt.setString(1, marcInfo.getId());
									insertChecksumStmt.setLong(2, marcInfo.getChecksum());
									insertChecksumStmt.executeUpdate();
								}
							}
							recordsProcessed++;
							if (maxRecordsToProcess != -1 && recordsProcessed > maxRecordsToProcess) {
								logger.debug("Stopping processing because maximum number of records to process was reached.");
								break;
							}
						} catch (Exception e) {
							logger.error("Error processing record " + recordNumber, e);
						}
					}
					logger.info("Finished processing file " + marcFile.toString() + " found " + recordNumber + " records");
				} catch (Exception e) {
					logger.error("Error processing file " + marcFile.toString(), e);
				}
			}
			return true;
		} catch (Exception e) {
			logger.error("Unable to process marc files", e);
			return false;
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
}
