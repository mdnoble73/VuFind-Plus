package org.vufind;

import java.io.ByteArrayOutputStream;
import java.io.UnsupportedEncodingException;
import java.lang.reflect.InvocationTargetException;
import java.lang.reflect.Method;
import java.text.SimpleDateFormat;
import java.util.Arrays;
import java.util.Date;
import java.util.HashMap;
import java.util.Iterator;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.zip.CRC32;

import org.apache.log4j.Logger;
import org.marc4j.MarcStreamWriter;
import org.marc4j.MarcWriter;
import org.marc4j.MarcXmlWriter;
import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;
import org.solrmarc.tools.SolrMarcIndexerException;
import org.solrmarc.tools.Utils;

import bsh.BshMethod;
import bsh.EvalError;
import bsh.Interpreter;
import bsh.Primitive;
import bsh.UtilEvalError;

public class MarcRecordDetails {
	private MarcProcessor						marcProcessor;
	private Logger									logger;

	private Record									record;
	private HashMap<String, Object>	fields	= new HashMap<String, Object>();

	private String									sourceUrl;
	private String									purchaseUrl;
	private boolean									urlsLoaded;
	private long										checksum;

	/**
	 * Maps fields based on properties files for use in processors
	 * 
	 * @param marcProcessor
	 * @param record
	 * @param logger
	 * @return
	 */
	@SuppressWarnings("unchecked")
	public boolean mapRecord(MarcProcessor marcProcessor, Record record, Logger logger) {
		// Preload basic information that nearly everything will need
		this.record = record;
		this.logger = logger;
		this.marcProcessor = marcProcessor;
		// System.out.println(record);

		for (String fieldName : marcProcessor.getMarcFieldProps().keySet()) {
			String fieldVal[] = marcProcessor.getMarcFieldProps().get(fieldName);
			String indexField = fieldVal[0];
			String indexType = fieldVal[1];
			String indexParm = fieldVal[2];
			String mapName = fieldVal[3];

			if (indexType.equals("constant")) {
				if (indexParm.contains("|")) {
					String parts[] = indexParm.split("[|]");
					Set<String> result = new LinkedHashSet<String>();
					result.addAll(Arrays.asList(parts));
					// if a zero length string appears, remove it
					result.remove("");
					addFields(fields, indexField, null, result);
				} else
					addField(fields, indexField, indexParm);
			} else if (indexType.equals("first")) {
				addField(fields, indexField, getFirstFieldVal(record, mapName, indexParm));
			} else if (indexType.equals("all")) {
				addFields(fields, indexField, mapName, getFieldList(record, indexParm));
			} else if (indexType.startsWith("join")) {
				String joinChar = " ";
				if (indexType.contains("(") && indexType.endsWith(")")) joinChar = indexType.replace("join(", "").replace(")", "");
				addField(fields, indexField, getFieldVals(record, indexParm, joinChar));
			} else if (indexType.equals("std")) {
				if (indexParm.equals("era")) {
					addFields(fields, indexField, mapName, getEra(record));
				} else {
					addField(fields, indexField, getStd(record, indexParm));
				}
			} else if (indexType.startsWith("custom")) {
				try {
					handleCustom(fields, indexType, indexField, mapName, record, indexParm);
				} catch (SolrMarcIndexerException e) {
					String recCntlNum = null;
					try {
						recCntlNum = record.getControlNumber();
					} catch (NullPointerException npe) { /* ignore */
					}

					if (e.getLevel() == SolrMarcIndexerException.DELETE) {
						throw new SolrMarcIndexerException(SolrMarcIndexerException.DELETE, "Record " + (recCntlNum != null ? recCntlNum : "")
								+ " purposely not indexed because " + fieldName + " field is empty");
						// logger.error("Record " + (recCntlNum != null ? recCntlNum : "") +
						// " not indexed because " + key + " field is empty -- " +
						// e.getMessage(), e);
					} else {
						logger.error("Unable to index record " + (recCntlNum != null ? recCntlNum : "") + " due to field " + fieldName + " -- " + e.getMessage(), e);
						throw (e);
					}
				}
			} else if (indexType.startsWith("script")) {
				try {
					handleScript(fields, indexType, indexField, mapName, record, indexParm);
				} catch (SolrMarcIndexerException e) {
					String recCntlNum = null;
					try {
						recCntlNum = record.getControlNumber();
					} catch (NullPointerException npe) { /* ignore */
					}

					if (e.getLevel() == SolrMarcIndexerException.DELETE) {
						logger.error(
								"Record " + (recCntlNum != null ? recCntlNum : "") + " purposely not indexed because " + fieldName + " field is empty -- " + e.getMessage(), e);
					} else {
						logger.error("Unable to index record " + (recCntlNum != null ? recCntlNum : "") + " due to field " + fieldName + " -- " + e.getMessage(), e);
						throw (e);
					}
				}
			}
		}

		return true;
	}

	public String getSourceUrl() {
		loadUrls();
		return sourceUrl;
	}

	public String getPurchaseUrl() {
		loadUrls();
		return purchaseUrl;
	}

	public void loadUrls() {
		if (urlsLoaded) return;
		@SuppressWarnings("unchecked")
		List<VariableField> eightFiftySixFields = record.getVariableFields("856");
		for (VariableField eightFiftySixField : eightFiftySixFields) {
			DataField eightFiftySixDataField = (DataField) eightFiftySixField;
			String url = null;
			if (eightFiftySixDataField.getSubfield('u') != null) {
				url = eightFiftySixDataField.getSubfield('u').getData();
			}
			String text = null;
			if (eightFiftySixDataField.getSubfield('y') != null) {
				text = eightFiftySixDataField.getSubfield('y').getData();
			} else if (eightFiftySixDataField.getSubfield('z') != null) {
				text = eightFiftySixDataField.getSubfield('z').getData();
			} else if (eightFiftySixDataField.getSubfield('3') != null) {
				text = eightFiftySixDataField.getSubfield('3').getData();
			}

			if (text != null && url != null) {
				if (text.matches("(?i).*?(?:download|access online|electronic book|access digital media).*?")) {
					if (!url.matches("(?i).*?vufind.*?")) {
						// System.out.println("Found source url");
						sourceUrl = url;
					}
				} else if (text.matches("(?i).*?(?:cover|review).*?")) {
					// File is an enrichment url
				} else if (text.matches("(?i).*?purchase.*?")) {
					// System.out.println("Found purchase URL");
					purchaseUrl = url;
				} else if (url.matches("(?i).*?(idm.oclc.org/login|ezproxy).*?")) {
					sourceUrl = url;
				} else {
					logger.info("Unknown URL " + url + " " + text);

				}
			}
		}
		urlsLoaded = true;
	}

	public long getChecksum() {
		if (checksum == -1) {
			CRC32 crc32 = new CRC32();
			crc32.update(this.toString().getBytes());
			// System.out.println("CRC32: " + crc32.getValue());
			checksum = crc32.getValue();
		}
		return checksum;
	}

	/**
	 * Add a field-value pair to the indexMap representation of a solr doc. The
	 * value will be "translated" per the translation map indicated.
	 * 
	 * @param indexMap
	 *          - the mapping of solr doc field names to values
	 * @param ixFldName
	 *          - the name of the field to add to the solr doc
	 * @param mapName
	 *          - the name of a translation map for the field value, or null
	 * @param fieldVal
	 *          - the (untranslated) field value to add to the solr doc field
	 */
	protected void addField(Map<String, Object> indexMap, String ixFldName, String mapName, String fieldVal) {
		if (mapName != null && marcProcessor.findMap(mapName) != null) fieldVal = Utils.remap(fieldVal, marcProcessor.findMap(mapName), true);

		if (fieldVal != null && fieldVal.length() > 0) indexMap.put(ixFldName, fieldVal);
	}

	/**
	 * Add a field-value pair to the indexMap representation of a solr doc.
	 * 
	 * @param indexMap
	 *          - the mapping of solr doc field names to values
	 * @param ixFldName
	 *          - the name of the field to add to the solr doc
	 * @param mapName
	 *          - the name of a translation map for the field value, or null
	 * @param fieldVal
	 *          - the (untranslated) field value to add to the solr doc field
	 */
	protected void addField(Map<String, Object> indexMap, String ixFldName, String fieldVal) {
		addField(indexMap, ixFldName, null, fieldVal);
	}

	/**
	 * Add a field-value pair to the indexMap representation of a solr doc for
	 * each value present in the "fieldVals" parameter. The values will be
	 * "translated" per the translation map indicated.
	 * 
	 * @param indexMap
	 *          - the mapping of solr doc field names to values
	 * @param ixFldName
	 *          - the name of the field to add to the solr doc
	 * @param mapName
	 *          - the name of a translation map for the field value, or null
	 * @param fieldVals
	 *          - a set of (untranslated) field values to be assigned to the solr
	 *          doc field
	 */
	protected void addFields(Map<String, Object> indexMap, String ixFldName, String mapName, Set<String> fieldVals) {
		if (mapName != null && marcProcessor.findMap(mapName) != null) fieldVals = Utils.remap(fieldVals, marcProcessor.findMap(mapName), true);

		if (!fieldVals.isEmpty()) {
			if (fieldVals.size() == 1) {
				String value = fieldVals.iterator().next();
				indexMap.put(ixFldName, value);
			} else
				indexMap.put(ixFldName, fieldVals);
		}
	}

	/**
	 * Get Set of Strings as indicated by tagStr. For each field spec in the
	 * tagStr that is NOT about bytes (i.e. not a 008[7-12] type fieldspec), the
	 * result string is the concatenation of all the specific subfields.
	 * 
	 * @param record
	 *          - the marc record object
	 * @param tagStr
	 *          string containing which field(s)/subfield(s) to use. This is a
	 *          series of: marc "tag" string (3 chars identifying a marc field,
	 *          e.g. 245) optionally followed by characters identifying which
	 *          subfields to use. Separator of colon indicates a separate value,
	 *          rather than concatenation. 008[5-7] denotes bytes 5-7 of the 008
	 *          field (0 based counting) 100[a-cf-z] denotes the bracket pattern
	 *          is a regular expression indicating which subfields to include.
	 *          Note: if the characters in the brackets are digits, it will be
	 *          interpreted as particular bytes, NOT a pattern. 100abcd denotes
	 *          subfields a, b, c, d are desired.
	 * @return the contents of the indicated marc field(s)/subfield(s), as a set
	 *         of Strings.
	 */
	public static Set<String> getFieldList(Record record, String tagStr) {
		String[] tags = tagStr.split(":");
		Set<String> result = new LinkedHashSet<String>();
		for (int i = 0; i < tags.length; i++) {
			// Check to ensure tag length is at least 3 characters
			if (tags[i].length() < 3) {
				System.err.println("Invalid tag specified: " + tags[i]);
				continue;
			}

			// Get Field Tag
			String tag = tags[i].substring(0, 3);
			boolean linkedField = false;
			if (tag.equals("LNK")) {
				tag = tags[i].substring(3, 6);
				linkedField = true;
			}
			// Process Subfields
			String subfield = tags[i].substring(3);
			boolean havePattern = false;
			int subend = 0;
			// brackets indicate parsing for individual characters or as pattern
			int bracket = tags[i].indexOf('[');
			if (bracket != -1) {
				String sub[] = tags[i].substring(bracket + 1).split("[\\]\\[\\-, ]+");
				try {
					// if bracket expression is digits, expression is treated as character
					// positions
					int substart = Integer.parseInt(sub[0]);
					subend = (sub.length > 1) ? Integer.parseInt(sub[1]) + 1 : substart + 1;
					String subfieldWObracket = subfield.substring(0, bracket - 3);
					result.addAll(getSubfieldDataAsSet(record, tag, subfieldWObracket, substart, subend));
				} catch (NumberFormatException e) {
					// assume brackets expression is a pattern such as [a-z]
					havePattern = true;
				}
			}
			if (subend == 0) // don't want specific characters.
			{
				String separator = null;
				if (subfield.indexOf('\'') != -1) {
					separator = subfield.substring(subfield.indexOf('\'') + 1, subfield.length() - 1);
					subfield = subfield.substring(0, subfield.indexOf('\''));
				}

				if (havePattern)
					if (linkedField)
						result.addAll(getLinkedFieldValue(record, tag, subfield, separator));
					else
						result.addAll(getAllSubfields(record, tag + subfield, separator));
				else if (linkedField)
					result.addAll(getLinkedFieldValue(record, tag, subfield, separator));
				else
					result.addAll(getSubfieldDataAsSet(record, tag, subfield, separator));
			}
		}
		return result;
	}

	/**
	 * Get all field values specified by tagStr, joined as a single string.
	 * 
	 * @param record
	 *          - the marc record object
	 * @param tagStr
	 *          string containing which field(s)/subfield(s) to use. This is a
	 *          series of: marc "tag" string (3 chars identifying a marc field,
	 *          e.g. 245) optionally followed by characters identifying which
	 *          subfields to use.
	 * @param separator
	 *          string separating values in the result string
	 * @return single string containing all values of the indicated marc
	 *         field(s)/subfield(s) concatenated with separator string
	 */
	public String getFieldVals(Record record, String tagStr, String separator) {
		Set<String> result = getFieldList(record, tagStr);
		return org.solrmarc.tools.Utils.join(result, separator);
	}

	/**
	 * Get the first value specified by the tagStr
	 * 
	 * @param record
	 *          - the marc record object
	 * @param tagStr
	 *          string containing which field(s)/subfield(s) to use. This is a
	 *          series of: marc "tag" string (3 chars identifying a marc field,
	 *          e.g. 245) optionally followed by characters identifying which
	 *          subfields to use.
	 * @return first value of the indicated marc field(s)/subfield(s) as a string
	 */
	public static String getFirstFieldVal(Record record, String tagStr) {
		Set<String> result = getFieldList(record, tagStr);
		Iterator<String> iter = result.iterator();
		if (iter.hasNext())
			return iter.next();
		else
			return null;
	}

	/**
	 * Get the first field value, which is mapped to another value. If there is no
	 * mapping for the value, use the mapping for the empty key, if it exists,
	 * o.w., use the mapping for the __DEFAULT key, if it exists.
	 * 
	 * @param record
	 *          - the marc record object
	 * @param mapName
	 *          - name of translation map to use to xform values
	 * @param tagStr
	 *          - which field(s)/subfield(s) to use
	 * @return first value as a string
	 */
	public String getFirstFieldVal(Record record, String mapName, String tagStr) {
		Set<String> result = getFieldList(record, tagStr);
		if (mapName != null && marcProcessor.findMap(mapName) != null) {
			result = Utils.remap(result, marcProcessor.findMap(mapName), false);
			if (marcProcessor.findMap(mapName).containsKey("")) {
				result.add(marcProcessor.findMap(mapName).get(""));
			}
			if (marcProcessor.findMap(mapName).containsKey("__DEFAULT")) {
				result.add(marcProcessor.findMap(mapName).get("__DEFAULT"));
			}
		}
		Iterator<String> iter = result.iterator();
		if (iter.hasNext())
			return iter.next();
		else
			return null;
	}

	/**
	 * Given a tag for a field, and a list (or regex) of one or more subfields get
	 * any linked 880 fields and include the appropriate subfields as a String
	 * value in the result set.
	 * 
	 * @param record
	 *          - marc record object
	 * @param tag
	 *          - the marc field for which 880s are sought.
	 * @param subfield
	 *          - The subfield(s) within the 880 linked field that should be
	 *          returned [a-cf-z] denotes the bracket pattern is a regular
	 *          expression indicating which subfields to include from the linked
	 *          880. Note: if the characters in the brackets are digits, it will
	 *          be interpreted as particular bytes, NOT a pattern 100abcd denotes
	 *          subfields a, b, c, d are desired from the linked 880.
	 * @param separator
	 *          - the separator string to insert between subfield items (if null,
	 *          a " " will be used)
	 * 
	 * @return set of Strings containing the values of the designated 880
	 *         field(s)/subfield(s)
	 */
	public static Set<String> getLinkedFieldValue(final Record record, String tag, String subfield, String separator) {
		// assume brackets expression is a pattern such as [a-z]
		Set<String> result = new LinkedHashSet<String>();
		boolean havePattern = false;
		Pattern subfieldPattern = null;
		if (subfield.indexOf('[') != -1) {
			havePattern = true;
			subfieldPattern = Pattern.compile(subfield);
		}
		List<VariableField> fields = record.getVariableFields("880");
		for (VariableField vf : fields) {
			DataField dfield = (DataField) vf;
			Subfield link = dfield.getSubfield('6');
			if (link != null && link.getData().startsWith(tag)) {
				List<Subfield> subList = dfield.getSubfields();
				StringBuffer buf = new StringBuffer("");
				for (Subfield subF : subList) {
					boolean addIt = false;
					if (havePattern) {
						Matcher matcher = subfieldPattern.matcher("" + subF.getCode());
						// matcher needs a string, hence concat with empty
						// string
						if (matcher.matches()) addIt = true;
					} else
					// a list a subfields
					{
						if (subfield.indexOf(subF.getCode()) != -1) addIt = true;
					}
					if (addIt) {
						if (buf.length() > 0) buf.append(separator != null ? separator : " ");
						buf.append(subF.getData().trim());
					}
				}
				if (buf.length() > 0) result.add(Utils.cleanData(buf.toString()));
			}
		}
		return (result);
	}

	protected static boolean isControlField(String fieldTag) {
		if (fieldTag.matches("00[0-9]")) {
			return (true);
		}
		return (false);
	}

	/**
	 * Get the specified subfields from the specified MARC field, returned as a
	 * set of strings to become lucene document field values
	 * 
	 * @param record
	 *          - the marc record object
	 * @param fldTag
	 *          - the field name, e.g. 245
	 * @param subfldsStr
	 *          - the string containing the desired subfields
	 * @param separator
	 *          - the separator string to insert between subfield items (if null,
	 *          a " " will be used)
	 * @returns a Set of String, where each string is the concatenated contents of
	 *          all the desired subfield values from a single instance of the
	 *          fldTag
	 */
	@SuppressWarnings("unchecked")
	protected static Set<String> getSubfieldDataAsSet(Record record, String fldTag, String subfldsStr, String separator) {
		Set<String> resultSet = new LinkedHashSet<String>();

		// Process Leader
		if (fldTag.equals("000")) {
			resultSet.add(record.getLeader().toString());
			return resultSet;
		}

		// Loop through Data and Control Fields
		// int iTag = new Integer(fldTag).intValue();
		List<VariableField> varFlds = record.getVariableFields(fldTag);
		for (VariableField vf : varFlds) {
			if (!isControlField(fldTag) && subfldsStr != null) {
				// DataField
				DataField dfield = (DataField) vf;

				if (subfldsStr.length() > 1 || separator != null) {
					// concatenate subfields using specified separator or space
					StringBuffer buffer = new StringBuffer("");
					List<Subfield> subFlds = dfield.getSubfields();
					for (Subfield sf : subFlds) {
						if (subfldsStr.indexOf(sf.getCode()) != -1) {
							if (buffer.length() > 0) buffer.append(separator != null ? separator : " ");
							buffer.append(sf.getData().trim());
						}
					}
					if (buffer.length() > 0) resultSet.add(buffer.toString());
				} else {
					// get all instances of the single subfield
					List<Subfield> subFlds = dfield.getSubfields(subfldsStr.charAt(0));
					for (Subfield sf : subFlds) {
						resultSet.add(sf.getData().trim());
					}
				}
			} else {
				// Control Field
				resultSet.add(((ControlField) vf).getData().trim());
			}
		}
		return resultSet;
	}

	/**
	 * Get the specified substring of subfield values from the specified MARC
	 * field, returned as a set of strings to become lucene document field values
	 * 
	 * @param record
	 *          - the marc record object
	 * @param fldTag
	 *          - the field name, e.g. 008
	 * @param subfldsStr
	 *          - the string containing the desired subfields
	 * @param beginIx
	 *          - the beginning index of the substring of the subfield value
	 * @param endIx
	 *          - the ending index of the substring of the subfield value
	 * @return the result set of strings
	 */
	@SuppressWarnings("unchecked")
	protected static Set<String> getSubfieldDataAsSet(Record record, String fldTag, String subfield, int beginIx, int endIx) {
		Set<String> resultSet = new LinkedHashSet<String>();

		// Process Leader
		if (fldTag.equals("000")) {
			resultSet.add(record.getLeader().toString().substring(beginIx, endIx));
			return resultSet;
		}

		// Loop through Data and Control Fields
		List<VariableField> varFlds = record.getVariableFields(fldTag);
		for (VariableField vf : varFlds) {
			if (!isControlField(fldTag) && subfield != null) {
				// Data Field
				DataField dfield = (DataField) vf;
				if (subfield.length() > 1) {
					// automatic concatenation of grouped subfields
					StringBuffer buffer = new StringBuffer("");
					List<Subfield> subFlds = dfield.getSubfields();
					for (Subfield sf : subFlds) {
						if (subfield.indexOf(sf.getCode()) != -1 && sf.getData().length() >= endIx) {
							if (buffer.length() > 0) buffer.append(" ");
							buffer.append(sf.getData().substring(beginIx, endIx));
						}
					}
					resultSet.add(buffer.toString());
				} else {
					// get all instances of the single subfield
					List<Subfield> subFlds = dfield.getSubfields(subfield.charAt(0));
					for (Subfield sf : subFlds) {
						if (sf.getData().length() >= endIx) resultSet.add(sf.getData().substring(beginIx, endIx));
					}
				}
			} else // Control Field
			{
				String cfldData = ((ControlField) vf).getData();
				if (cfldData.length() >= endIx) resultSet.add(cfldData.substring(beginIx, endIx));
			}
		}
		return resultSet;
	}

	/**
	 * extract all the subfields requested in requested marc fields. Each instance
	 * of each marc field will be put in a separate result (but the subfields will
	 * be concatenated into a single value for each marc field)
	 * 
	 * @param record
	 *          marc record object
	 * @param fieldSpec
	 *          - the desired marc fields and subfields as given in the
	 *          xxx_index.properties file
	 * @param separator
	 *          - the character to use between subfield values in the solr field
	 *          contents
	 * @return Set of values (as strings) for solr field
	 */
	public static Set<String> getAllSubfields(final Record record, String fieldSpec, String separator) {
		Set<String> result = new LinkedHashSet<String>();

		String[] fldTags = fieldSpec.split(":");
		for (int i = 0; i < fldTags.length; i++) {
			// Check to ensure tag length is at least 3 characters
			if (fldTags[i].length() < 3) {
				System.err.println("Invalid tag specified: " + fldTags[i]);
				continue;
			}

			String fldTag = fldTags[i].substring(0, 3);

			String subfldTags = fldTags[i].substring(3);

			List<VariableField> marcFieldList = record.getVariableFields(fldTag);
			if (!marcFieldList.isEmpty()) {
				Pattern subfieldPattern = Pattern.compile(subfldTags.length() == 0 ? "." : subfldTags);
				for (VariableField vf : marcFieldList) {
					DataField marcField = (DataField) vf;
					StringBuffer buffer = new StringBuffer("");
					List<Subfield> subfields = marcField.getSubfields();
					for (Subfield subfield : subfields) {
						Matcher matcher = subfieldPattern.matcher("" + subfield.getCode());
						if (matcher.matches()) {
							if (buffer.length() > 0) buffer.append(separator != null ? separator : " ");
							buffer.append(subfield.getData().trim());
						}
					}
					if (buffer.length() > 0) result.add(Utils.cleanData(buffer.toString()));
				}
			}
		}

		return result;
	}

	/**
	 * Write a marc record as a binary string to the
	 * 
	 * @param record
	 *          marc record object to be written
	 * @return string containing binary (UTF-8 encoded) representation of marc
	 *         record object.
	 */
	protected String writeRaw(Record record) {
		ByteArrayOutputStream out = new ByteArrayOutputStream();
		MarcWriter writer = new MarcStreamWriter(out, "UTF-8");
		writer.write(record);
		writer.close();

		String result = null;
		try {
			result = out.toString("UTF-8");
		} catch (UnsupportedEncodingException e) {
			// e.printStackTrace();
			logger.error(e.getCause());
		}
		return result;
	}

	/**
	 * Write a marc record as a string containing MarcXML
	 * 
	 * @param record
	 *          marc record object to be written
	 * @return String containing MarcXML representation of marc record object
	 */
	protected String writeXml(Record record) {
		ByteArrayOutputStream out = new ByteArrayOutputStream();
		// TODO: see if this works better
		// MarcWriter writer = new MarcXmlWriter(out, false);
		MarcWriter writer = new MarcXmlWriter(out, "UTF-8");
		writer.write(record);
		writer.close();

		String tmp = null;
		try {
			tmp = out.toString("UTF-8");
		} catch (UnsupportedEncodingException e) {
			// e.printStackTrace();
			logger.error(e.getCause());
		}
		return tmp;
	}

	/**
	 * get the era field values from 045a as a Set of Strings
	 */
	public Set<String> getEra(Record record) {
		Set<String> result = new LinkedHashSet<String>();
		String eraField = getFirstFieldVal(record, "045a");
		if (eraField == null) return result;

		if (eraField.length() == 4) {
			eraField = eraField.toLowerCase();
			char eraStart1 = eraField.charAt(0);
			char eraStart2 = eraField.charAt(1);
			char eraEnd1 = eraField.charAt(2);
			char eraEnd2 = eraField.charAt(3);
			if (eraStart2 == 'l') eraEnd2 = '1';
			if (eraEnd2 == 'l') eraEnd2 = '1';
			if (eraStart2 == 'o') eraEnd2 = '0';
			if (eraEnd2 == 'o') eraEnd2 = '0';
			return getEra(result, eraStart1, eraStart2, eraEnd1, eraEnd2);
		} else if (eraField.length() == 5) {
			char eraStart1 = eraField.charAt(0);
			char eraStart2 = eraField.charAt(1);

			char eraEnd1 = eraField.charAt(3);
			char eraEnd2 = eraField.charAt(4);
			char gap = eraField.charAt(2);
			if (gap == ' ' || gap == '-') return getEra(result, eraStart1, eraStart2, eraEnd1, eraEnd2);
		} else if (eraField.length() == 2) {
			char eraStart1 = eraField.charAt(0);
			char eraStart2 = eraField.charAt(1);
			if (eraStart1 >= 'a' && eraStart1 <= 'y' && eraStart2 >= '0' && eraStart2 <= '9') return getEra(result, eraStart1, eraStart2, eraStart1, eraStart2);
		}
		return result;
	}

	/**
	 * get the two eras indicated by the four passed characters, and add them to
	 * the result parameter (which is a set). The characters passed in are from
	 * the 045a.
	 */
	public static Set<String> getEra(Set<String> result, char eraStart1, char eraStart2, char eraEnd1, char eraEnd2) {
		if (eraStart1 >= 'a' && eraStart1 <= 'y' && eraEnd1 >= 'a' && eraEnd1 <= 'y') {
			for (char eraVal = eraStart1; eraVal <= eraEnd1; eraVal++) {
				if (eraStart2 != '-' || eraEnd2 != '-') {
					char loopStart = (eraVal != eraStart1) ? '0' : Character.isDigit(eraStart2) ? eraStart2 : '0';
					char loopEnd = (eraVal != eraEnd1) ? '9' : Character.isDigit(eraEnd2) ? eraEnd2 : '9';
					for (char eraVal2 = loopStart; eraVal2 <= loopEnd; eraVal2++) {
						result.add("" + eraVal + eraVal2);
					}
				}
				result.add("" + eraVal);
			}
		}
		return result;
	}

	/**
	 * Return the date in 260c as a string
	 * 
	 * @param record
	 *          - the marc record object
	 * @return 260c, "cleaned" per org.solrmarc.tools.Utils.cleanDate()
	 */
	public String getDate(Record record) {
		String date = getFieldVals(record, "260c", ", ");
		if (date == null || date.length() == 0) return (null);
		return Utils.cleanDate(date);
	}

	/**
	 * Return the index datestamp as a string
	 */
	public String getCurrentDate() {
		SimpleDateFormat df = new SimpleDateFormat("yyyyMMddHHmm");
		return df.format(new Date());
	}

	/**
	 * get values that don't require parsing specified record fields: raw, xml,
	 * date, index_date ...
	 * 
	 * @param indexParm
	 *          - what type of value to return
	 */
	private String getStd(Record record, String indexParm) {
		if (indexParm.equals("raw") || indexParm.equalsIgnoreCase("FullRecordAsMARC")) {
			return writeRaw(record);
		} else if (indexParm.equals("xml") || indexParm.equalsIgnoreCase("FullRecordAsXML")) {
			return writeXml(record);
		} else if (indexParm.equals("xml") || indexParm.equalsIgnoreCase("FullRecordAsText")) {
			return (record.toString().replaceAll("\n", "<br/>"));
		} else if (indexParm.equals("date") || indexParm.equalsIgnoreCase("DateOfPublication")) {
			return getDate(record);
		} else if (indexParm.equals("index_date") || indexParm.equalsIgnoreCase("DateRecordIndexed")) {
			return getCurrentDate();
		}
		return null;
	}

	/**
	 * Calling a custom method defined in a user-supplied custom subclass of
	 * SolrIndexer, do the processing indicated by a custom function, putting the
	 * solr field name and value into the indexMap parameter
	 * 
	 * @param indexMap
	 *          - The map contain the solr index record that is being constructed
	 *          for this MARC record.
	 * @param indexType
	 *          - Indicates whether the the solr record should be deleted if no
	 *          value is generated by this custom indexing method.
	 * @param indexField
	 *          - The name of the field to be added to the solr index record. Note
	 *          that in that case of a custom index method that returns a Map, the
	 *          keys of the map define the names of the fields to be added, and
	 *          this value is then simply a dummy.
	 * @param mapName
	 *          - The name (or file and name) of a translation map to use to
	 *          convert the data in the specified fields of the MARC record to the
	 *          desired values to be included in the Solr index record. (If
	 *          mapName is null, the values in the record will be returned as-is.)
	 * @param record
	 *          - The MARC record that is being indexed.
	 * @param indexParm
	 *          - contains the name of the custom method to invoke, as well as the
	 *          additional parameters to pass to that method.
	 */
	private void handleCustom(Map<String, Object> indexMap, String indexType, String indexField, String mapName, Record record, String indexParm)
			throws SolrMarcIndexerException {
		Object retval = null;
		Class<?> returnType = null;
		String recCntlNum = null;
		try {
			recCntlNum = record.getControlNumber();
		} catch (NullPointerException npe) { /*
																					 * ignore as this is for error msgs
																					 * only
																					 */
		}

		String className = null;
		Class<?> classThatContainsMethod = this.getClass();
		Object objectThatContainsMethod = this;
		try {
			if (indexType.matches("custom(DeleteRecordIfFieldEmpty)?[(][a-zA-Z0-9.]+[)]")) {
				className = indexType.replaceFirst("custom(DeleteRecordIfFieldEmpty)?[(]([a-zA-Z0-9.]+)[)]", "$2");
				if (marcProcessor.getCustomMixinMap().containsKey(className)) {
					objectThatContainsMethod = marcProcessor.getCustomMixinMap().get(className);
					classThatContainsMethod = objectThatContainsMethod.getClass();
				}
			}

			Method method;
			if (indexParm.indexOf("(") != -1) {
				String functionName = indexParm.substring(0, indexParm.indexOf('('));
				String parmStr = indexParm.substring(indexParm.indexOf('(') + 1, indexParm.lastIndexOf(')'));
				// parameters are separated by unescaped commas
				String parms[] = parmStr.trim().split("(?<=[^\\\\]),");
				int numparms = parms.length;
				Class parmClasses[] = new Class[numparms + 1];
				parmClasses[0] = Record.class;
				Object objParms[] = new Object[numparms + 1];
				objParms[0] = record;
				for (int i = 0; i < numparms; i++) {
					parmClasses[i + 1] = String.class;
					objParms[i + 1] = Util.cleanIniValue(parms[i].trim());
				}
				method = marcProcessor.getCustomMethodMap().get(functionName);
				if (method == null) method = classThatContainsMethod.getMethod(functionName, parmClasses);
				returnType = method.getReturnType();
				retval = method.invoke(objectThatContainsMethod, objParms);
			} else {
				method = marcProcessor.getCustomMethodMap().get(indexParm);
				if (method == null) method = classThatContainsMethod.getMethod(indexParm, new Class[] { Record.class });
				returnType = method.getReturnType();
				retval = method.invoke(objectThatContainsMethod, new Object[] { record });
			}
		} catch (SecurityException e) {
			// e.printStackTrace();
			// logger.error(record.getControlNumber() + " " + indexField + " " +
			// e.getCause());
			logger.error("Error while indexing " + indexField + " for record " + (recCntlNum != null ? recCntlNum : "") + " -- " + e.getCause());
		} catch (NoSuchMethodException e) {
			// e.printStackTrace();
			// logger.error(record.getControlNumber() + " " + indexField + " " +
			// e.getCause());
			logger.error("Error while indexing " + indexField + " for record " + (recCntlNum != null ? recCntlNum : "") + " -- " + e.getCause());
		} catch (IllegalArgumentException e) {
			// e.printStackTrace();
			// logger.error(record.getControlNumber() + " " + indexField + " " +
			// e.getCause());
			logger.error("Error while indexing " + indexField + " for record " + (recCntlNum != null ? recCntlNum : "") + " -- " + e.getCause());
		} catch (IllegalAccessException e) {
			// e.printStackTrace();
			// logger.error(record.getControlNumber() + " " + indexField + " " +
			// e.getCause());
			logger.error("Error while indexing " + indexField + " for record " + (recCntlNum != null ? recCntlNum : "") + " -- " + e.getCause());
		} catch (InvocationTargetException e) {
			if (e.getTargetException() instanceof SolrMarcIndexerException) {
				throw ((SolrMarcIndexerException) e.getTargetException());
			}
			e.printStackTrace(); // DEBUG
			// logger.error(record.getControlNumber() + " " + indexField + " " +
			// e.getCause());
			logger.error("Error while indexing " + indexField + " for record " + (recCntlNum != null ? recCntlNum : "") + " -- " + e.getCause());
		}
		boolean deleteIfEmpty = false;
		if (indexType.startsWith("customDeleteRecordIfFieldEmpty")) deleteIfEmpty = true;
		boolean result = finishCustomOrScript(indexMap, indexField, mapName, returnType, retval, deleteIfEmpty);
		if (result == true) throw new SolrMarcIndexerException(SolrMarcIndexerException.DELETE);
	}

	/**
	 * Analogous to handleCustom, however instead of calling a custom method
	 * defined in a user-supplied custom subclass SolrIndexer, this will invoke a
	 * custom BeanShell script method found in a script file that is referenced in
	 * the index specification in parentheses following the keyword "script".
	 * 
	 * @param indexMap
	 *          - The map contain the solr index record that is being constructed
	 *          for this MARC record.
	 * @param indexType
	 *          - Indicates whether the the solr record should be deleted if no
	 *          value is generated by this custom indexing script
	 * @param indexField
	 *          - The name of the field to be added to the solr index record. Note
	 *          that in that case of a custom index method that returns a Map, the
	 *          keys of the map define the names of the fields to be added, and
	 *          this value is then simply a dummy.
	 * @param mapName
	 *          - The name (or file and name) of a translation map to use to
	 *          convert the data in the specified fields of the MARC record to the
	 *          desired values to be included in the Solr index record. (If
	 *          mapName is null, the values in the record will be returned as-is.)
	 * @param record
	 *          - The MARC record that is being indexed.
	 * @param indexParm
	 *          - contains the name of the custom BeanShell script method to
	 *          invoke, as well as the additional parameters to pass to that
	 *          method.
	 */
	private void handleScript(Map<String, Object> indexMap, String indexType, String indexField, String mapName, Record record, String indexParm) {
		String scriptFileName = indexType.replaceFirst("script[A-Za-z]*[(]", "").replaceFirst("[)]$", "");
		Interpreter bsh = marcProcessor.getInterpreterForScript(scriptFileName);
		Object retval;
		Class<?> returnType;
		String functionName = null;
		try {
			bsh.set("indexer", this);
			BshMethod bshmethod;
			if (indexParm.indexOf("(") != -1) {
				functionName = indexParm.substring(0, indexParm.indexOf('('));
				String parmStr = indexParm.substring(indexParm.indexOf('(') + 1, indexParm.lastIndexOf(')'));
				// parameters are separated by unescaped commas
				String parms[] = parmStr.trim().split("(?<=[^\\\\]),");
				int numparms = parms.length;
				Class parmClasses[] = new Class[numparms + 1];
				parmClasses[0] = Record.class;
				Object objParms[] = new Object[numparms + 1];
				objParms[0] = record;
				for (int i = 0; i < numparms; i++) {
					parmClasses[i + 1] = String.class;
					objParms[i + 1] = Util.cleanIniValue(parms[i].trim());
				}
				bshmethod = bsh.getNameSpace().getMethod(functionName, parmClasses);
				if (bshmethod == null) {
					throw new IllegalArgumentException("Unable to find Specified method " + functionName + " in  script: " + scriptFileName);
				} else {
					returnType = bshmethod.getReturnType();
					retval = bshmethod.invoke(objParms, bsh);
				}
			} else {
				bshmethod = bsh.getNameSpace().getMethod(indexParm, new Class[] { Record.class });
				if (bshmethod == null) {
					throw new IllegalArgumentException("Unable to find Specified method " + indexParm + " in  script: " + scriptFileName);
				} else {
					returnType = bshmethod.getReturnType();
					retval = bshmethod.invoke(new Object[] { record }, bsh);
				}
			}
			if (returnType == null && retval != null) returnType = retval.getClass();
		} catch (EvalError e) {
			throw new IllegalArgumentException("Error while trying to evaluate script: " + scriptFileName, e);
		} catch (UtilEvalError e) {
			throw new IllegalArgumentException("Unable to find Specified method " + functionName + " in  script: " + scriptFileName, e);
		}
		boolean deleteIfEmpty = false;
		if (indexType.startsWith("scriptDeleteRecordIfFieldEmpty")) deleteIfEmpty = true;
		if (retval == Primitive.NULL) retval = null;
		boolean result = finishCustomOrScript(indexMap, indexField, mapName, returnType, retval, deleteIfEmpty);
		if (result == true) throw new SolrMarcIndexerException(SolrMarcIndexerException.DELETE);
	}

	/**
	 * Finish up the processing for a custom indexing function or a custom
	 * BeanShell script method
	 * 
	 * @param indexMap
	 *          - The map contain the solr index record that is being constructed
	 *          for this MARC record.
	 * @param indexField
	 *          - The name of the field to be added to the solr index record. Note
	 *          that in that case of a custom index method that returns a Map, the
	 *          keys of the map define the names of the fields to be added, and
	 *          this value is then simply a dummy.
	 * @param mapName
	 *          - The name (or file and name) of a translation map to use to
	 *          convert the data in the specified fields of the MARC record to the
	 *          desired values to be included in the Solr index record. (If
	 *          mapName is null, the values in the record will be returned as-is.)
	 * @param returnType
	 *          - The Class of the return type of the custom indexing function or
	 *          the custom BeanShell script method, the valid expected types are
	 *          String, Set<String>, or Map<String, Object>
	 * @param retval
	 *          - The value that was returned from the custom indexing function or
	 *          the custom BeanShell script method
	 * @param deleteIfEmpty
	 *          - Indicates whether the the solr record should be deleted if no
	 *          value was generated.
	 * @return returns true if the indexing process should stop and the solr
	 *         record should be deleted.
	 */
	private boolean finishCustomOrScript(Map<String, Object> indexMap, String indexField, String mapName, Class<?> returnType, Object retval,
			boolean deleteIfEmpty) {
		if (returnType == null || retval == null)
			return (deleteIfEmpty);
		else if (returnType.isAssignableFrom(Map.class)) {
			if (deleteIfEmpty && ((Map<String, String>) retval).size() == 0) return (true);
			if (retval != null) indexMap.putAll((Map<String, String>) retval);
		} else if (returnType.isAssignableFrom(Set.class)) {
			Set<String> fields = (Set<String>) retval;
			if (mapName != null && marcProcessor.findMap(mapName) != null) fields = Utils.remap(fields, marcProcessor.findMap(mapName), true);
			if (deleteIfEmpty && fields.size() == 0) return (true);
			addFields(indexMap, indexField, null, fields);
		} else if (returnType.isAssignableFrom(String.class)) {
			String field = (String) retval;
			if (mapName != null && marcProcessor.findMap(mapName) != null) field = Utils.remap(field, marcProcessor.findMap(mapName), true);
			addField(indexMap, indexField, null, field);
		}
		return false;
	}

	/**
	 * Get the 245a (and 245b, if it exists, concatenated with a space between the
	 * two subfield values), with trailing punctuation removed. See
	 * org.solrmarc.tools.Utils.cleanData() for details on the punctuation removal
	 * 
	 * @param record
	 *          - the marc record object
	 * @return 245a, b, and k values concatenated in order found, with trailing
	 *         punct removed. Returns empty string if no suitable title found.
	 */
	public String getTitle(Record record) {
		DataField titleField = (DataField) record.getVariableField("245");
		if (titleField == null) {
			return "";
		}

		StringBuilder titleBuilder = new StringBuilder();

		Iterator<Subfield> iter = titleField.getSubfields().iterator();
		while (iter.hasNext()) {
			Subfield f = iter.next();
			char code = f.getCode();
			if (code == 'a' || code == 'b' || code == 'k') {
				titleBuilder.append(f.getData());
			}
		}

		return Utils.cleanData(titleBuilder.toString());
	}

	/**
	 * Get the title (245ab) from a record, without non-filing chars as specified
	 * in 245 2nd indicator, and lowercased.
	 * 
	 * @param record
	 *          - the marc record object
	 * @return 245a and 245b values concatenated, with trailing punct removed, and
	 *         with non-filing characters omitted. Null returned if no title can
	 *         be found.
	 * 
	 * @see org.solrmarc.index.SolrIndexer.getTitle()
	 */
	public String getSortableTitle(Record record) {
		DataField titleField = (DataField) record.getVariableField("245");
		if (titleField == null) return "";

		int nonFilingInt = getInd2AsInt(titleField);

		String title = getTitle(record);
		title = title.toLowerCase();

		// Skip non-filing chars, if possible.
		if (title.length() > nonFilingInt) {
			title = title.substring(nonFilingInt);
		}

		if (title.length() == 0) {
			return null;
		}

		return title;
	}

	/**
	 * @param df
	 *          a DataField
	 * @return the integer (0-9, 0 if blank or other) in the 2nd indicator
	 */
	protected int getInd2AsInt(DataField df) {
		char ind2char = df.getIndicator2();
		int result = 0;
		if (Character.isDigit(ind2char)) result = Integer.valueOf(String.valueOf(ind2char));
		return result;
	}

	public String getId() {
		return (String) fields.get("id");
	}

	public String getTitle() {
		return (String) fields.get("title");
	}
	
	public String getIsbn(){
		//return the first 13 digit isbn or 10 digit if there are no 13
		Object isbnField = fields.get("isbn");
		if (isbnField instanceof String){
			String curIsbn = (String)isbnField;
			if (curIsbn.indexOf(" ") > 0){
				curIsbn = curIsbn.substring(0, curIsbn.indexOf(" "));
			}
			return curIsbn;
		}else{
			Set<String> isbns = (Set<String>)isbnField;
			String bestIsbn = null;
			if (isbns != null && isbns.size() > 0){
				Iterator<String> isbnIterator = isbns.iterator();
				while (isbnIterator.hasNext()){
					String curIsbn = isbnIterator.next();
					if (curIsbn.indexOf(" ") > 0){
						curIsbn = curIsbn.substring(0, curIsbn.indexOf(" "));
					}
					if (curIsbn.length() == 13){
						bestIsbn = curIsbn;
						break;
					}else if (bestIsbn == null){
						bestIsbn = curIsbn;
					}
				}
			}
			return bestIsbn;
		}
	}
	
	public String getFirstFieldValueInSet(String fieldName){
		Object fieldValue = fields.get(fieldName);
		if (fieldValue instanceof String){
			return (String)fieldValue;
		}else{
			Set<String> fieldValues = (Set<String>)fields.get(fieldName);
			if (fieldValues != null &&fieldValues.size() >= 1){
				return (String)fieldValues.iterator().next();
			}
			return null;
		}
	}

	public String getAuthor() {
		return (String)fields.get("author");
	}

	public String getSortTitle() {
		return (String) fields.get("title_sort");
	}
	public HashMap<String, Object> getFields() {
		return fields;
	}
}
