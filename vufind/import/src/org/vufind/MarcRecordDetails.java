package org.vufind;

import java.io.ByteArrayOutputStream;
import java.io.UnsupportedEncodingException;
import java.lang.reflect.InvocationTargetException;
import java.lang.reflect.Method;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Date;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Iterator;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.regex.PatternSyntaxException;
import java.util.zip.CRC32;

import javax.xml.parsers.FactoryConfigurationError;
import javax.xml.parsers.ParserConfigurationException;
import javax.xml.transform.TransformerException;

import org.apache.log4j.Logger;
import org.econtent.DetectionSettings;
import org.econtent.LibrarySpecificLink;
import org.marc4j.MarcStreamWriter;
import org.marc4j.MarcWriter;
import org.marc4j.MarcXmlWriter;
import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.VariableField;
import org.solrmarc.tools.CallNumUtils;
import org.solrmarc.tools.SolrMarcIndexerException;
import org.solrmarc.tools.Utils;

import com.jamesmurty.utils.XMLBuilder;

import bsh.BshMethod;
import bsh.EvalError;
import bsh.Interpreter;
import bsh.Primitive;
import bsh.UtilEvalError;

public class MarcRecordDetails {
	private MarcProcessor										marcProcessor;
	private Logger													logger;

	private Record													record;
	private HashMap<String, Object>					mappedFields		= new HashMap<String, Object>();

	private ArrayList<LibrarySpecificLink>	sourceUrls			= new ArrayList<LibrarySpecificLink>();
	private String													purchaseUrl;
	private boolean													urlsLoaded;
	private long														checksum				= -1;

	private boolean													allFieldsMapped	= false;

	/**
	 * Does basic mapping of fields to determine if the record has changed or not
	 * 
	 * @param marcProcessor
	 * @param record
	 * @param logger
	 * @return
	 */
	public MarcRecordDetails(MarcProcessor marcProcessor, Record record, Logger logger) {
		// Preload basic information that nearly everything will need
		this.record = record;
		this.logger = logger;
		this.marcProcessor = marcProcessor;

		// Map the id field
		String fieldVal[] = marcProcessor.getMarcFieldProps().get("id");
		mapField("id", fieldVal);
	}

	/**
	 * Maps fields based on properties files for use in processors
	 * 
	 * @return
	 */
	private boolean mapRecord(String source) {
		if (allFieldsMapped) return true;
		logger.debug("Mapping record " + getId() + " " + source);
		allFieldsMapped = true;

		// Map all fields for the record
		for (String fieldName : marcProcessor.getMarcFieldProps().keySet()) {
			String fieldVal[] = marcProcessor.getMarcFieldProps().get(fieldName);
			mapField(fieldName, fieldVal);
		}

		return true;
	}

	private void mapField(String fieldName, String[] fieldVal) {
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
				addFields(mappedFields, indexField, null, result);
			} else
				addField(mappedFields, indexField, indexParm);
		} else if (indexType.equals("first")) {
			addField(mappedFields, indexField, getFirstFieldVal(record, mapName, indexParm));
		} else if (indexType.equals("all")) {
			addFields(mappedFields, indexField, mapName, getFieldList(record, indexParm));
		} else if (indexType.startsWith("join")) {
			String joinChar = " ";
			if (indexType.contains("(") && indexType.endsWith(")")) joinChar = indexType.replace("join(", "").replace(")", "");
			addField(mappedFields, indexField, getFieldVals(indexParm, joinChar));
		} else if (indexType.equals("std")) {
			if (indexParm.equals("era")) {
				addFields(mappedFields, indexField, mapName, getEra(record));
			} else {
				addField(mappedFields, indexField, getStd(record, indexParm));
			}
		} else if (indexType.startsWith("custom")) {
			try {
				handleCustom(mappedFields, indexType, indexField, mapName, indexParm);
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
				handleScript(mappedFields, indexType, indexField, mapName, record, indexParm);
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

	public ArrayList<LibrarySpecificLink> getSourceUrls() {
		loadUrls();
		return sourceUrls;
	}

	public String getPurchaseUrl() {
		loadUrls();
		return purchaseUrl;
	}

	public void loadUrls() {
		if (urlsLoaded) return;
		//logger.info("Loading urls from 856 field");
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
				boolean isSourceUrl = false;
				if (text.matches("(?i).*?(?:download|access online|electronic book|access digital media|access title).*?")) {
					if (!url.matches("(?i).*?vufind.*?")) {
						isSourceUrl = true;
						
					}
				} else if (text.matches("(?i).*?(?:cover|review).*?")) {
					// File is an enrichment url
				} else if (text.matches("(?i).*?purchase|buy.*?")) {
					// System.out.println("Found purchase URL");
					purchaseUrl = url;
				} else if (url.matches("(?i).*?(idm.oclc.org/login|ezproxy).*?")) {
					isSourceUrl = true;
				} else {
					logger.info("Unknown URL " + url + " " + text);
				}
				if (isSourceUrl){
					// System.out.println("Found source url");
					boolean addedUrl = false;
					long libraryId = marcProcessor.getLibraryIdForLink(url);
					if (libraryId == -1){
						//Also check link text for the record
						libraryId = marcProcessor.getLibraryIdForLink(text);
					}
					//If the library Id is still not set, check item records to see which library (or libraries own the title).
					if (libraryId == -1 && marcProcessor.getItemTag() != null && marcProcessor.getSharedEContentLocation() != null){
						@SuppressWarnings("unchecked")
						List<DataField> itemFields = record.getVariableFields(marcProcessor.getItemTag());
						for (DataField curItem : itemFields) {
							Subfield locationField = curItem.getSubfield(marcProcessor.getLocationSubfield().charAt(0));
							if (locationField != null){
								String location = locationField.getData();
								//Get the libraryId based on the location
								libraryId = getLibrarySystemIdForLocation(location);
								if (libraryId != -1L){
									sourceUrls.add(new LibrarySpecificLink(url, libraryId));
									addedUrl = true;
								}
							}
						}
					}
					if (!addedUrl){
						//This only happens if there are no items and the 
						sourceUrls.add(new LibrarySpecificLink(url, libraryId));
					}
				}
			}
		}
		
		//Get urls from item records
		//logger.info("Loading records from item records");
		if ((marcProcessor.getItemTag() != null) && (marcProcessor.getUrlSubfield() != null) && (marcProcessor.getLocationSubfield() != null)) {
			@SuppressWarnings("unchecked")
			List<DataField> itemFields = record.getVariableFields(marcProcessor.getItemTag());
			for (DataField curItem : itemFields) {
				Subfield urlField = curItem.getSubfield(marcProcessor.getUrlSubfield().charAt(0));
				if (urlField != null) {
					//logger.info("Found item based url " + urlField.getData());
					Subfield locationField = curItem.getSubfield(marcProcessor.getLocationSubfield().charAt(0));
					if (locationField != null) {
						//logger.info("  Location is " + locationField.getData());
						long libraryId = getLibrarySystemIdForLocation(locationField.getData());
						//logger.info("Adding local url " + urlField.getData() + " library system: " + libraryId);
						sourceUrls.add(new LibrarySpecificLink(urlField.getData(), libraryId));
					}
				}
			}
		}
		
		//logger.info("Num source urls found: " + sourceUrls.size());
		//logger.info("Scrape for links = " + marcProcessor.isScrapeItemsForLinks());
		if (sourceUrls.size() == 0 && marcProcessor.isScrapeItemsForLinks()) {
			//logger.info("Loading records from millennium");
			// Check the record in the ILS
			getUrlsForItemsFromMillennium();
		}

		urlsLoaded = true;
	}

	private void getUrlsForItemsFromMillennium() {
		String catalogUrl = "https://www.millennium.marmot.org";
		String scope = "93";
		String shortId = this.getId();
		shortId = shortId.substring(1, shortId.length() - 1);
		String itemUrl = catalogUrl + "/search~S" + scope + "/." + shortId + "/." + shortId + "/1,1,1,B/holdings~" + shortId;
		//logger.debug("itemUrl = " + itemUrl);
		URLPostResponse response = Util.getURL(itemUrl, logger);
		if (response.isSuccess()){
			//Extract the items from the page
			try {
				Pattern Regex = Pattern.compile("<td align=\"center\" colspan=\"3\">\\s*<a href=\"(.*?)\">(.*?)\\s*</td>", Pattern.CANON_EQ);
				Matcher RegexMatcher = Regex.matcher(response.getMessage());
				while (RegexMatcher.find()) {
					String url = RegexMatcher.group(1);
					String linkText = RegexMatcher.group(2);
					long libraryId = marcProcessor.getLibraryIdForLink(url);
					if (libraryId == -1){
						//Also check link text for the record
						libraryId = marcProcessor.getLibraryIdForLink(linkText);
					}
					//logger.info("Adding local url " + url + " library system: " + libraryId + " linkText: " + linkText);
					sourceUrls.add(new LibrarySpecificLink(url, libraryId));
				} 
			} catch (PatternSyntaxException ex) {
				// Syntax error in the regular expression
				logger.error("Could not extract items from millennium, regex was invalid " + ex.toString());
			}
		}else{
			logger.error("Could not extract items from millennium, " + response.getResponseCode() + " - " + response.getMessage());
		}
	}

	public long getChecksum() {
		if (checksum == -1) {
			CRC32 crc32 = new CRC32();
			crc32.update(record.toString().getBytes());
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

	public Set<String> getFieldList(String tagStr) {
		return this.getFieldList(this.record, tagStr);
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
	public Set<String> getFieldList(Record record, String tagStr) {
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
						result.addAll(getLinkedFieldValue(tag, subfield, separator));
					else
						result.addAll(getAllSubfields(tag + subfield, separator));
				else if (linkedField)
					result.addAll(getLinkedFieldValue(tag, subfield, separator));
				else
					result.addAll(getSubfieldDataAsSet(tag, subfield, separator));
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
	public String getFieldVals(String tagStr, String separator) {
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
	public String getFirstFieldVal(String tagStr) {
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
	@SuppressWarnings("unchecked")
	public Set<String> getLinkedFieldValue(String tag, String subfield, String separator) {
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
	protected Set<String> getSubfieldDataAsSet(String fldTag, String subfldsStr, String separator) {
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
	 * @param fieldSpec
	 *          - the desired marc fields and subfields as given in the
	 *          xxx_index.properties file
	 * @param separator
	 *          - the character to use between subfield values in the solr field
	 *          contents
	 * @return Set of values (as strings) for solr field
	 */
	@SuppressWarnings("unchecked")
	public Set<String> getAllSubfields(String fieldSpec, String separator) {
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
	public String writeRaw(Record record) {
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
	 * Write a marc record as a binary string to the
	 * 
	 * @param record
	 *          marc record object to be written
	 * @return string containing binary (UTF-8 encoded) representation of marc
	 *         record object.
	 */
	public String getRawRecord() {
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
		String eraField = getFirstFieldVal("045a");
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
	public String getDate() {
		String date = getFieldVals("260c", ", ");
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
			return getDate();
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
	 * @param indexParm
	 *          - contains the name of the custom method to invoke, as well as the
	 *          additional parameters to pass to that method.
	 */
	private void handleCustom(Map<String, Object> indexMap, String indexType, String indexField, String mapName, String indexParm)
			throws SolrMarcIndexerException {
		Object retval = null;
		Class<?> returnType = null;
		String id = this.getId();

		Class<?> classThatContainsMethod = this.getClass();
		Object objectThatContainsMethod = this;
		try {

			Method method;
			if (indexParm.indexOf("(") != -1) {
				String functionName = indexParm.substring(0, indexParm.indexOf('('));
				String parmStr = indexParm.substring(indexParm.indexOf('(') + 1, indexParm.lastIndexOf(')'));
				// parameters are separated by unescaped commas
				String parms[] = parmStr.trim().split("(?<=[^\\\\]),");
				int numparms = parms.length;
				@SuppressWarnings("rawtypes")
				Class parmClasses[] = new Class[numparms];
				Object objParms[] = new Object[numparms];
				for (int i = 0; i < numparms; i++) {
					parmClasses[i] = String.class;
					objParms[i] = Util.cleanIniValue(parms[i].trim());
				}
				method = marcProcessor.getCustomMethodMap().get(functionName);
				if (method == null) method = classThatContainsMethod.getMethod(functionName, parmClasses);
				returnType = method.getReturnType();
				retval = method.invoke(objectThatContainsMethod, objParms);
			} else {
				method = marcProcessor.getCustomMethodMap().get(indexParm);
				if (method == null) method = classThatContainsMethod.getMethod(indexParm);
				returnType = method.getReturnType();
				retval = method.invoke(objectThatContainsMethod);
			}
		} catch (SecurityException e) {
			// e.printStackTrace();
			// logger.error(record.getControlNumber() + " " + indexField + " " +
			// e.getCause());
			logger.error("SecurityException while indexing " + indexField + " for record " + (id != null ? id : "") + " -- " + e.getCause());
		} catch (NoSuchMethodException e) {
			// e.printStackTrace();
			// logger.error(record.getControlNumber() + " " + indexField + " " +
			// e.getCause());
			logger.error("NoSuchMethodException while indexing " + indexField + " for record " + (id != null ? id : "") + " -- " + e.getCause());
		} catch (IllegalArgumentException e) {
			// e.printStackTrace();
			// logger.error(record.getControlNumber() + " " + indexField + " " +
			// e.getCause());
			logger.error("IllegalArgumentException while indexing " + indexField + " for record " + (id != null ? id : "") + " -- " + e.getCause());
		} catch (IllegalAccessException e) {
			// e.printStackTrace();
			// logger.error(record.getControlNumber() + " " + indexField + " " +
			// e.getCause());
			logger.error("IllegalAccessException while indexing " + indexField + " for record " + (id != null ? id : "") + " -- " + e.getCause());
		} catch (InvocationTargetException e) {
			if (e.getTargetException() instanceof SolrMarcIndexerException) {
				throw ((SolrMarcIndexerException) e.getTargetException());
			}
			e.printStackTrace(); // DEBUG
			// logger.error(record.getControlNumber() + " " + indexField + " " +
			// e.getCause());
			logger.error("InvocationTargetException while indexing " + indexField + " for record " + (id != null ? id : "") + " -- " + e.getCause());
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
				@SuppressWarnings("rawtypes")
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
	@SuppressWarnings("unchecked")
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
	public String getTitle() {
		DataField titleField = (DataField) record.getVariableField("245");
		if (titleField == null) {
			return "";
		}

		StringBuilder titleBuilder = new StringBuilder();

		@SuppressWarnings("unchecked")
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
	public String getSortableTitle() {
		DataField titleField = (DataField) record.getVariableField("245");
		if (titleField == null) return "";

		int nonFilingInt = getInd2AsInt(titleField);

		String title = getTitle();
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

	public String getDescription() {
		return getFirstFieldVal("520a");
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

	@SuppressWarnings("unchecked")
	public String getId() {
		Object idField = mappedFields.get("id");
		if (idField instanceof String) {
			return (String) idField;
		} else if (idField instanceof Set) {
			return (String) (((Set<String>) mappedFields).iterator().next());
		} else {
			return null;
		}
	}

	public String getShortId() {
		String shortId = getId();
		if (shortId.startsWith(".b") && shortId.length() == 10) {
			// Millennium id, trim off the leading . and the trailing checksum digit
			shortId = shortId.substring(1, 9);
		}
		return shortId;
	}

	public String getIsbn() {
		// return the first 13 digit isbn or 10 digit if there are no 13
		Object isbnField = getMappedFields("isbn").get("isbn");
		if (isbnField instanceof String) {
			String curIsbn = (String) isbnField;
			if (curIsbn.indexOf(" ") > 0) {
				curIsbn = curIsbn.substring(0, curIsbn.indexOf(" "));
			}
			return curIsbn;
		} else {
			@SuppressWarnings("unchecked")
			Set<String> isbns = (Set<String>) isbnField;
			String bestIsbn = null;
			if (isbns != null && isbns.size() > 0) {
				Iterator<String> isbnIterator = isbns.iterator();
				while (isbnIterator.hasNext()) {
					String curIsbn = isbnIterator.next();
					if (curIsbn.indexOf(" ") > 0) {
						curIsbn = curIsbn.substring(0, curIsbn.indexOf(" "));
					}
					if (curIsbn.length() == 13) {
						bestIsbn = curIsbn;
						break;
					} else if (bestIsbn == null) {
						bestIsbn = curIsbn;
					}
				}
			}
			return bestIsbn;
		}
	}

	private HashMap<String, Object> getMappedFields(String source) {
		mapRecord(source);
		return mappedFields;
	}

	public String getFirstFieldValueInSet(String fieldName) {
		Object fieldValue = getMappedFields(fieldName).get(fieldName);
		if (fieldValue instanceof String) {
			return (String) fieldValue;
		} else {
			@SuppressWarnings("unchecked")
			Set<String> fieldValues = (Set<String>)fieldValue;
			if (fieldValues != null && fieldValues.size() >= 1) {
				return (String) fieldValues.iterator().next();
			}
			return "";
		}
	}

	public String getAuthor() {
		return (String) getMappedFields("auth_author").get("auth_author");
	}

	public String getSortTitle() {
		return (String) getMappedFields("title_sort").get("title_sort");
	}

	private HashMap<String, Object> getFields(String source) {
		return getMappedFields(source);
	}
	
	public Object getMappedField(String fieldName){
		return getMappedFields(fieldName).get(fieldName);
	}

	/**
	 * Extract the call number label from a record
	 * 
	 * @param record
	 * @return Call number label
	 */
	public String getFullCallNumber() {

		return (getFullCallNumber("099ab:090ab:050ab"));
	}

	/**
	 * Extract the call number label from a record
	 * 
	 * @param record
	 * @return Call number label
	 */
	public String getFullCallNumber(String fieldSpec) {

		String val = getFirstFieldVal(fieldSpec);

		if (val != null) {
			return val.toUpperCase().replaceAll(" ", "");
		} else {
			return val;
		}
	}

	/**
	 * Extract the call number label from a record
	 * 
	 * @param record
	 * @return Call number label
	 */
	public String getCallNumberLabel() {

		return getCallNumberLabel("090a:050a");
	}

	/**
	 * Extract the call number label from a record
	 * 
	 * @param record
	 * @return Call number label
	 */
	public String getCallNumberLabel(String fieldSpec) {

		String val = getFirstFieldVal(fieldSpec);

		if (val != null) {
			int dotPos = val.indexOf(".");
			if (dotPos > 0) {
				val = val.substring(0, dotPos);
			}
			return val.toUpperCase();
		} else {
			return val;
		}
	}

	/**
	 * Extract the subject component of the call number
	 * 
	 * Can return null
	 * 
	 * @param record
	 * @return Call number label
	 */
	public String getCallNumberSubject() {

		return (getCallNumberSubject("090a:050a"));
	}

	/**
	 * Extract the subject component of the call number
	 * 
	 * Can return null
	 * 
	 * @param record
	 * @return Call number label
	 */
	public String getCallNumberSubject(String fieldSpec) {

		String val = getFirstFieldVal(fieldSpec);

		if (val != null) {
			String[] callNumberSubject = val.toUpperCase().split("[^A-Z]+");
			if (callNumberSubject.length > 0) {
				return callNumberSubject[0];
			}
		}
		return (null);
	}

	/**
	 * Loops through all datafields and creates a field for "all fields"
	 * searching. Shameless stolen from Vufind Indexer Custom Code
	 * 
	 * @param record
	 *          marc record object
	 * @param lowerBoundStr
	 *          - the "lowest" marc field to include (e.g. 100). defaults to 100
	 *          if value passed doesn't parse as an integer
	 * @param upperBoundStr
	 *          - one more than the "highest" marc field to include (e.g. 900 will
	 *          include up to 899). Defaults to 900 if value passed doesn't parse
	 *          as an integer
	 * @return a string containing ALL subfields of ALL marc fields within the
	 *         range indicated by the bound string arguments.
	 */
	@SuppressWarnings("unchecked")
	public String getAllSearchableFields(String lowerBoundStr, String upperBoundStr) {
		StringBuffer buffer = new StringBuffer("");
		int lowerBound = localParseInt(lowerBoundStr, 100);
		int upperBound = localParseInt(upperBoundStr, 900);

		List<DataField> fields = record.getDataFields();
		for (DataField field : fields) {
			// Get all fields starting with the 100 and ending with the 839
			// This will ignore any "code" fields and only use textual fields
			int tag = localParseInt(field.getTag(), -1);
			if ((tag >= lowerBound) && (tag < upperBound)) {
				// Loop through subfields
				List<Subfield> subfields = field.getSubfields();
				for (Subfield subfield : subfields) {
					if (buffer.length() > 0) buffer.append(" ");
					buffer.append(subfield.getData());
				}
			}
		}
		return buffer.toString();
	}

	/**
	 * return an int for the passed string
	 * 
	 * @param str
	 * @param defValue
	 *          - default value, if string doesn't parse into int
	 */
	private int localParseInt(String str, int defValue) {
		int value = defValue;
		try {
			value = Integer.parseInt(str);
		} catch (NumberFormatException nfe) {
			// provided value is not valid numeric string
			// Ignoring it and moving happily on.
		}
		return (value);
	}

	public String getMpaaRating() {
		String val = getFirstFieldVal("521a");

		if (val != null) {
			if (val.matches("Rated\\sNR\\.?|Not Rated\\.?|NR")) {
				return "Not Rated";
			} else if (val.matches("Rated\\s(G|PG-13|PG|R|NC-17|NR|X)\\.?")) {
				try {
					Pattern Regex = Pattern.compile("Rated\\s(G|PG-13|PG|R|NC-17|NR|X)", Pattern.CANON_EQ);
					Matcher RegexMatcher = Regex.matcher(val);
					if (RegexMatcher.find()) {
						return RegexMatcher.group(1) + " Rated";
					} else {
						return val;
					}
				} catch (PatternSyntaxException ex) {
					// Syntax error in the regular expression
					return null;
				}
			} else {
				return null;
			}
		} else {
			return val;
		}
	}

	private Float	rating	= null;

	public String getRating(String recordIdSpec) {
		if (rating == null) {
			String recordId = getFirstFieldVal(recordIdSpec);
			// logger.info("Getting rating for " + recordId);
			// Check to see if the record has an eContent Record
			rating = marcProcessor.getPrintRatings().get(recordId);
			if (rating == null) {
				rating = -2.5f;
			}

			// logger.info("Rating = " + rating.toString());
		}
		return Float.toString(rating);
	}

	public String getRatingFacet(String recordIdSpec) {
		if (rating == null) {
			getRating(recordIdSpec);
		}

		if (rating > 4.5) {
			return "fiveStar";
		} else if (rating > 3.5) {
			return "fourStar";
		} else if (rating > 2.5) {
			return "threeStar";
		} else if (rating > 1.5) {
			return "twoStar";
		} else if (rating > 0.0001) {
			return "oneStar";
		} else {
			return "Unrated";
		}
	}
	
	public Set<String> getAwardName(String fieldSpec) {
		Set<String> result = new LinkedHashSet<String>();
		// Loop through the specified MARC fields:
		Set<String> fields = getFieldList(fieldSpec);
		Iterator<String> fieldsIter = fields.iterator();
		if (fields != null) {
			while(fieldsIter.hasNext()) {
				// Get the current string to work on:
				String current = fieldsIter.next();
				//Strip extra data after the award name. 
				if (current.indexOf(",") > 0){
					current = current.substring(0, current.indexOf(","));
				}
				result.add(current.trim());
			}
		}
		// return set of awards to SolrMarc
		if (result.size() == 0){
			return null;
		}
		return result;
	}
	
	public String getLexileScore(){
		String result = null;
		//Get a list of all tags that may contain the lexile score.  
		@SuppressWarnings("unchecked")
		List<VariableField> input = record.getVariableFields("521");
		Iterator<VariableField> iter = input.iterator();

		DataField field;
		while (iter.hasNext()) {
			field = (DataField) iter.next();
	    
			if (field.getSubfield('b') == null){
				continue;
			}else{
				String type = field.getSubfield('b').getData();
				if (type.matches("(?i).*?lexile.*?")){
					String lexileRawData = field.getSubfield('a').getData();
					try {
						Pattern Regex = Pattern.compile("(\\d+)",
							Pattern.CANON_EQ | Pattern.CASE_INSENSITIVE | Pattern.UNICODE_CASE);
						Matcher RegexMatcher = Regex.matcher(lexileRawData);
						if (RegexMatcher.find()) {
							String lexileData = RegexMatcher.group(1);
							
							result = lexileData;
							//System.out.println("Lexile Score " + result);
							return result;
						} 
					} catch (PatternSyntaxException ex) {
						// Syntax error in the regular expression
					}
				}
			}
		}

		return result;
	}
	
	public String getAcceleratedReaderReadingLevel(){
		String result = null;
		//Get a list of all tags that may contain the lexile score.  
		@SuppressWarnings("unchecked")
		List<VariableField> input = record.getVariableFields("526");
		Iterator<VariableField> iter = input.iterator();

		DataField field;
		while (iter.hasNext()) {
			field = (DataField) iter.next();
	    
			if (field.getSubfield('a') == null){
				continue;
			}else{
				String type = field.getSubfield('a').getData();
				if (type.matches("(?i)accelerated reader")){
					String rawData = field.getSubfield('c').getData();
					try {
						Pattern Regex = Pattern.compile("([\\d.]+)", Pattern.CANON_EQ | Pattern.CASE_INSENSITIVE | Pattern.UNICODE_CASE);
						Matcher RegexMatcher = Regex.matcher(rawData);
						if (RegexMatcher.find()) {
							String arData = RegexMatcher.group(1);
							result = arData;
							//System.out.println("AR Reading Level " + result);
							return result;
						} 
					} catch (PatternSyntaxException ex) {
						// Syntax error in the regular expression
					}
				}
			}
		}

		return result;
	}

	public String getAcceleratedReaderPointLevel(){
		String result = null;
		//Get a list of all tags that may contain the lexile score.  
		@SuppressWarnings("unchecked")
		List<VariableField> input = record.getVariableFields("526");
		Iterator<VariableField> iter = input.iterator();

		DataField field;
		while (iter.hasNext()) {
			field = (DataField) iter.next();
	    
			if (field.getSubfield('a') == null){
				continue;
			}else{
				String type = field.getSubfield('a').getData();
				if (type.matches("(?i)accelerated reader")){
					String rawData = field.getSubfield('d').getData();
					try {
						Pattern Regex = Pattern.compile("([\\d.]+)",
							Pattern.CANON_EQ | Pattern.CASE_INSENSITIVE | Pattern.UNICODE_CASE);
						Matcher RegexMatcher = Regex.matcher(rawData);
						if (RegexMatcher.find()) {
							String arData = RegexMatcher.group(1);
							result = arData;
							//System.out.println("AR Point Level " + result);
							return result;
						} 
					} catch (PatternSyntaxException ex) {
						// Syntax error in the regular expression
					}
				}
			}
		}

		return result;
	}

	public String getAcceleratedReaderInterestLevel(){
		String result = null;
		//Get a list of all tags that may contain the lexile score.  
		@SuppressWarnings("unchecked")
		List<VariableField> input = record.getVariableFields("526");
		Iterator<VariableField> iter = input.iterator();

		DataField field;
		while (iter.hasNext()) {
			field = (DataField) iter.next();
	    
			if (field.getSubfield('a') == null){
				continue;
			}else{
				String type = field.getSubfield('a').getData();
				if (type.matches("(?i)accelerated reader")){
					String arReadingLevel = field.getSubfield('b').getData();
					return arReadingLevel;
				}
			}
		}

		return result;
	}

	@SuppressWarnings("unchecked")
	public String getAllFields() {
		StringBuffer allFieldData = new StringBuffer();
		List<ControlField> controlFields = record.getControlFields();
		for (Object field : controlFields) {
			ControlField dataField = (ControlField) field;
			String data = dataField.getData();
			data = data.replace((char) 31, ' ');
			allFieldData.append(data).append(" ");
		}

		List<DataField> fields = record.getDataFields();
		for (Object field : fields) {
			DataField dataField = (DataField) field;
			List<Subfield> subfields = dataField.getSubfields();
			for (Object subfieldObj : subfields) {
				Subfield subfield = (Subfield) subfieldObj;
				allFieldData.append(subfield.getData()).append(" ");
			}
		}
		return allFieldData.toString();
	}

	public Set<String> getLiteraryForm() {
		Set<String> result = new LinkedHashSet<String>();
		String leader = record.getLeader().toString();

		ControlField ohOhEightField = (ControlField) record.getVariableField("008");
		ControlField ohOhSixField = (ControlField) record.getVariableField("006");

		// check the Leader at position 6 to determine the type of field
		char recordType = Character.toUpperCase(leader.charAt(6));
		char bibLevel = Character.toUpperCase(leader.charAt(7));
		// Figure out what material type the record is
		if ((recordType == 'A' || recordType == 'T') && (bibLevel == 'A' || bibLevel == 'C' || bibLevel == 'D' || bibLevel == 'M') /* Books */
				|| (recordType == 'M') /* Computer Files */
				|| (recordType == 'C' || recordType == 'D' || recordType == 'I' || recordType == 'J') /* Music */
				|| (recordType == 'G' || recordType == 'K' || recordType == 'O' || recordType == 'R') /*
																																															 * Visual
																																															 * Materials
																																															 */
		) {
			char targetAudienceChar;
			if (ohOhSixField != null && ohOhSixField.getData().length() >= 16) {
				targetAudienceChar = Character.toUpperCase(ohOhSixField.getData().charAt(16));
				result.add(Character.toString(targetAudienceChar));
			} else if (ohOhEightField != null && ohOhEightField.getData().length() >= 33) {
				targetAudienceChar = Character.toUpperCase(ohOhEightField.getData().charAt(33));
				result.add(Character.toString(targetAudienceChar));
			} else {
				result.add("Unknown");
			}
		} else {
			result.add("Unknown");
		}

		return result;
	}

	public Set<LocalCallNumber> getLocalCallNumbers(String itemTag, String callNumberSubfield, String locationSubfield) {
		Set<LocalCallNumber> localCallnumbers = new HashSet<LocalCallNumber>();
		@SuppressWarnings("unchecked")
		List<DataField> itemFields = record.getVariableFields(itemTag);
		Iterator<DataField> itemFieldIterator = itemFields.iterator();
		char callNumberSubfieldChar = callNumberSubfield.charAt(0);
		char locationSubfieldChar = locationSubfield.charAt(0);
		while (itemFieldIterator.hasNext()) {
			DataField itemField = (DataField) itemFieldIterator.next();
			Subfield callNumber = itemField.getSubfield(callNumberSubfieldChar);
			Subfield location = itemField.getSubfield(locationSubfieldChar);
			if (callNumber != null && location != null) {
				String callNumberData = callNumber.getData();
				callNumberData = callNumberData.replaceAll("~", " ");
				LocalCallNumber localCallNumber = new LocalCallNumber(location.getData(), callNumberData);
				localCallnumbers.add(localCallNumber);
			}
		}
		return localCallnumbers;
	}

	private Set<String>	locationCodes;

	public Set<String> getLocationCodes(String locationSpecifier, String locationSpecifier2) {
		if (locationCodes != null) {
			return locationCodes;
		}

		locationCodes = new LinkedHashSet<String>();
		// Get a list of all branches that own at least one copy from the 989d tag
		Set<String> input = getFieldList(record, locationSpecifier);
		Iterator<String> iter = input.iterator();
		while (iter.hasNext()) {
			String curLocationCode = iter.next();
			try {
				Pattern Regex = Pattern.compile("^(?:\\(\\d+\\))?(.*)\\s*$");
				Matcher RegexMatcher = Regex.matcher(curLocationCode);
				if (RegexMatcher.find()) {
					curLocationCode = RegexMatcher.group(1);
				}
				addLocationCode(curLocationCode, locationCodes);
			} catch (PatternSyntaxException ex) {
				// Syntax error in the regular expression
			}
		}

		// Add any location codes from the 998 subfield a
		if (locationSpecifier2 != null && locationSpecifier2.length() > 0 && !locationSpecifier2.equals("null")) {
			Set<String> input2 = getFieldList(record, "998a");
			Iterator<String> iter2 = input2.iterator();
			while (iter2.hasNext()) {
				String curLocationCode = iter2.next();
				if (curLocationCode.length() >= 2) {
					try {
						Pattern Regex = Pattern.compile("^(?:\\(\\d+\\))?(.*)\\s*$");
						Matcher RegexMatcher = Regex.matcher(curLocationCode);
						if (RegexMatcher.find()) {
							curLocationCode = RegexMatcher.group(1);
						}
						addLocationCode(curLocationCode, locationCodes);

					} catch (PatternSyntaxException ex) {
						// Syntax error in the regular expression
					}
				}
			}
		}
		return locationCodes;
	}

	private void addLocationCode(String locationCode, Set<String> locationCodes) {
		locationCode = locationCode.trim();
		for (String existingCode : locationCodes) {
			if (existingCode.startsWith(locationCode)) {
				// There is a more specific location code, skip this code
				return;
			}
		}
		locationCodes.add(locationCode);
		// Deal with special case collections which are treated as a branches, but
		// are really a collection that crosses multiple branches.
		if (locationCode.matches("^(ssbj[aejlnpuvkbrm]|ssbyl|ssc.*|sst.*)$")) {
			locationCodes.add("steamjuv");
		} else if (locationCode.matches("^(evabd|evaj|evajs|evebd|evej|evejs|evgbd|evgj|evgjs|evj|evajn|evejn|evgjn)$")) {
			locationCodes.add("evldjuv");
		} else if (locationCode.matches("^(mpbj|mpcj|mpdj|mpfj|mpgj|mpmj|mpmja|mpmjm|mpmjn|mpoj|mppj|mpcja|mpfja|mppja|mpbja|mpdja|mpoja|mpgja)$")) {
			locationCodes.add("mesajuv");
		} else if (locationCode
				.matches("^(mvap.*|mvbw.*|mvch.*|mvcl.*|mvco.*|mvcp.*|mvcs|mvdi.*|mvdr.*|mvem.*|mvfv.*|mvgp.*|mvlm.*|mvlo.*|mvlp.*|mvmv.*|mvni.*|mvoa.*|mvpo.*|mvpp.*|mvrm.*|mvrr.*|mvsc.*|mvsh.*|mvta.*|mvtm.*|mvto.*|mvwi.*)$")) {
			locationCodes.add("mcvsdelem");
		} else if (locationCode.matches("^(pcjv)$")) {
			locationCodes.add("pitkinjuv");
		} else if (locationCode.matches("^(gccju|gcgju|gcnju|gcpju|gcrju|gcsju)$")) {
			locationCodes.add("gcpjuv");
		}
	}

	public String getLibrarySystemBoost(String locationSpecifier, String locationSpecifier2, String activeSystem, String branchCodes) {
		Set<String> locationCodes = getLocationCodes(locationSpecifier, locationSpecifier2);

		StringBuffer branchString = new StringBuffer();
		for (String curBranch : locationCodes) {
			branchString.append(curBranch + " ");
		}
		// System.out.println(activeSystem + " regex = (?:\\(\\d+\\))?(" +
		// branchCodes + ")(\\s.*|$)");
		boolean FoundMatch = false;
		try {
			Pattern Regex = Pattern.compile("(?:\\(\\d+\\))?(" + branchCodes + ")(\\s|$)");
			Matcher RegexMatcher = Regex.matcher(branchString);
			FoundMatch = RegexMatcher.find();
		} catch (PatternSyntaxException ex) {
			// Syntax error in the regular expression
		}
		if (FoundMatch) {
			// System.out.println(activeSystem + " boost = 500");
			return "500";
		} else {
			return "0";
		}
	}

	public String getLocationBoost(String locationSpecifier, String locationSpecifier2, String activeLocation) {
		Set<String> locationCodes = getLocationCodes(locationSpecifier, locationSpecifier2);

		StringBuffer branchString = new StringBuffer();
		for (String curBranch : locationCodes) {
			branchString.append(curBranch + " ");
		}

		boolean FoundMatch = false;
		try {
			Pattern Regex = Pattern.compile("(?:\\(\\d+\\))?(" + activeLocation + ".*?)(\\s|$)");
			Matcher RegexMatcher = Regex.matcher(branchString);
			FoundMatch = RegexMatcher.find();
		} catch (PatternSyntaxException ex) {
			// Syntax error in the regular expression
		}
		if (FoundMatch) {
			// System.out.println(activeLocation + " boost = 750");
			return "750";
		} else {
			return "0";
		}
	}

	public Set<String> getFormatFromCollectionOrStd(String collectionFieldSpec, String returnFirst) {
		String collection = getFirstFieldVal(collectionFieldSpec);
		if (collection != null) {
			Set<String> result = new LinkedHashSet<String>();
			result.add(collection);
			return result;
		} else {
			return getFormat(returnFirst);
		}
	}

	/**
	 * Determine Record Format(s)
	 * 
	 * @param Record
	 *          record
	 * @return Set format of record
	 */
	public Set<String> getFormat(String returnFirst) {
		Set<String> result = new LinkedHashSet<String>();
		String leader = record.getLeader().toString();
		char leaderBit;
		ControlField fixedField = (ControlField) record.getVariableField("008");
		DataField title = (DataField) record.getVariableField("245");
		char formatCode = ' ';

		boolean returnFirstValue = false;
		if (returnFirst.equals("true")) {
			returnFirstValue = true;
		}

		// check for playaway in 260|b
		DataField sysDetailsNote = (DataField) record.getVariableField("260");
		if (sysDetailsNote != null) {
			if (sysDetailsNote.getSubfield('b') != null) {
				String sysDetailsValue = sysDetailsNote.getSubfield('b').getData().toLowerCase();
				if (sysDetailsValue.contains("playaway")) {
					result.add("Playaway");
					if (returnFirstValue) return result;
				}
			}
		}

		// Check for formats in the 538 field
		DataField sysDetailsNote2 = (DataField) record.getVariableField("538");
		if (sysDetailsNote2 != null) {
			if (sysDetailsNote2.getSubfield('a') != null) {
				String sysDetailsValue = sysDetailsNote2.getSubfield('a').getData().toLowerCase();
				if (sysDetailsValue.contains("playaway")) {
					result.add("Playaway");
					if (returnFirstValue) return result;
				} else if (sysDetailsValue.contains("bluray") || sysDetailsValue.contains("blu-ray")) {
					result.add("Blu-ray");
					if (returnFirstValue) return result;
				} else if (sysDetailsValue.contains("vertical file")) {
					result.add("VerticalFile");
					if (returnFirstValue) return result;
				}
			}
		}

		// Check for formats in the 500 tag
		DataField noteField = (DataField) record.getVariableField("500");
		if (noteField != null) {
			if (noteField.getSubfield('a') != null) {
				String noteValue = noteField.getSubfield('a').getData().toLowerCase();
				if (noteValue.contains("vertical file")) {
					result.add("VerticalFile");
					if (returnFirstValue) return result;
				}
			}
		}

		// check if there's an h in the 245
		if (title != null) {
			if (title.getSubfield('h') != null) {
				if (title.getSubfield('h').getData().toLowerCase().contains("[electronic resource]")) {
					result.add("Electronic");
					if (returnFirstValue) return result;
				}
			}
		}

		// Check for large print book (large format in 650, 300, or 250 fields)
		// Check for blu-ray in 300 fields
		DataField edition = (DataField) record.getVariableField("250");
		if (edition != null) {
			if (edition.getSubfield('a') != null) {
				if (edition.getSubfield('a').getData().toLowerCase().contains("large type")) {
					result.add("LargePrint");
					if (returnFirstValue) return result;
				}
			}
		}

		@SuppressWarnings("unchecked")
		List<DataField> physicalDescription = record.getVariableFields("300");
		if (physicalDescription != null) {
			Iterator<DataField> fieldsIter = physicalDescription.iterator();
			DataField field;
			while (fieldsIter.hasNext()) {
				field = (DataField) fieldsIter.next();
				@SuppressWarnings("unchecked")
				List<Subfield> subfields = field.getSubfields();
				Iterator<Subfield> subfieldIter = subfields.iterator();
				while (subfieldIter.hasNext()) {
					Subfield subfield = subfieldIter.next();
					if (subfield.getData().toLowerCase().contains("large type")) {
						result.add("LargePrint");
						if (returnFirstValue) return result;
					} else if (subfield.getData().toLowerCase().contains("bluray") || subfield.getData().toLowerCase().contains("blu-ray")) {
						result.add("Blu-ray");
						if (returnFirstValue) return result;
					}
				}
			}
		}
		@SuppressWarnings("unchecked")
		List<DataField> topicalTerm = record.getVariableFields("650");
		if (physicalDescription != null) {
			Iterator<DataField> fieldsIter = topicalTerm.iterator();
			DataField field;
			while (fieldsIter.hasNext()) {
				field = (DataField) fieldsIter.next();
				@SuppressWarnings("unchecked")
				List<Subfield> subfields = field.getSubfields();
				Iterator<Subfield> subfieldIter = subfields.iterator();
				while (subfieldIter.hasNext()) {
					Subfield subfield = subfieldIter.next();
					if (subfield.getData().toLowerCase().contains("large type")) {
						result.add("LargePrint");
						if (returnFirstValue) return result;
					}
				}
			}
		}

		// check the 007 - this is a repeating field
		@SuppressWarnings("unchecked")
		List<DataField> fields = record.getVariableFields("007");
		if (fields != null) {
			Iterator<DataField> fieldsIter = fields.iterator();
			ControlField formatField;
			while (fieldsIter.hasNext()) {
				formatField = (ControlField) fieldsIter.next();
				if (formatField.getData() == null || formatField.getData().length() < 2) {
					continue;
				}
				// Check for blu-ray (s in position 4)
				// This logic does not appear correct.
				/*
				 * if (formatField.getData() != null && formatField.getData().length()
				 * >= 4){ if (formatField.getData().toUpperCase().charAt(4) == 'S'){
				 * result.add("Blu-ray"); break; } }
				 */
				formatCode = formatField.getData().toUpperCase().charAt(0);
				switch (formatCode) {
				case 'A':
					switch (formatField.getData().toUpperCase().charAt(1)) {
					case 'D':
						result.add("Atlas");
						break;
					default:
						result.add("Map");
						break;
					}
					break;
				case 'C':
					switch (formatField.getData().toUpperCase().charAt(1)) {
					case 'A':
						result.add("TapeCartridge");
						break;
					case 'B':
						result.add("ChipCartridge");
						break;
					case 'C':
						result.add("DiscCartridge");
						break;
					case 'F':
						result.add("TapeCassette");
						break;
					case 'H':
						result.add("TapeReel");
						break;
					case 'J':
						result.add("FloppyDisk");
						break;
					case 'M':
					case 'O':
						result.add("CDROM");
						break;
					case 'R':
						// Do not return - this will cause anything with an
						// 856 field to be labeled as "Electronic"
						break;
					default:
						result.add("Software");
						break;
					}
					break;
				case 'D':
					result.add("Globe");
					break;
				case 'F':
					result.add("Braille");
					break;
				case 'G':
					switch (formatField.getData().toUpperCase().charAt(1)) {
					case 'C':
					case 'D':
						result.add("Filmstrip");
						break;
					case 'T':
						result.add("Transparency");
						break;
					default:
						result.add("Slide");
						break;
					}
					break;
				case 'H':
					result.add("Microfilm");
					break;
				case 'K':
					switch (formatField.getData().toUpperCase().charAt(1)) {
					case 'C':
						result.add("Collage");
						break;
					case 'D':
						result.add("Drawing");
						break;
					case 'E':
						result.add("Painting");
						break;
					case 'F':
						result.add("Print");
						break;
					case 'G':
						result.add("Photonegative");
						break;
					case 'J':
						result.add("Print");
						break;
					case 'L':
						result.add("Drawing");
						break;
					case 'O':
						result.add("FlashCard");
						break;
					case 'N':
						result.add("Chart");
						break;
					default:
						result.add("Photo");
						break;
					}
					break;
				case 'M':
					switch (formatField.getData().toUpperCase().charAt(1)) {
					case 'F':
						result.add("VideoCassette");
						break;
					case 'R':
						result.add("Filmstrip");
						break;
					default:
						result.add("MotionPicture");
						break;
					}
					break;
				case 'O':
					result.add("Kit");
					break;
				case 'Q':
					result.add("MusicalScore");
					break;
				case 'R':
					result.add("SensorImage");
					break;
				case 'S':
					switch (formatField.getData().toUpperCase().charAt(1)) {
					case 'D':
						result.add("SoundDisc");
						break;
					case 'S':
						result.add("SoundCassette");
						break;
					default:
						result.add("SoundRecording");
						break;
					}
					break;
				case 'V':
					switch (formatField.getData().toUpperCase().charAt(1)) {
					case 'C':
						result.add("VideoCartridge");
						break;
					case 'D':
						result.add("VideoDisc");
						break;
					case 'F':
						result.add("VideoCassette");
						break;
					case 'R':
						result.add("VideoReel");
						break;
					default:
						result.add("Video");
						break;
					}
					break;
				}
				if (returnFirstValue && !result.isEmpty()) {
					return result;
				}
			}
			if (!result.isEmpty() && returnFirstValue) {
				return result;
			}
		}

		// check the Leader at position 6
		if (leader.length() >= 6) {
			leaderBit = leader.charAt(6);
			switch (Character.toUpperCase(leaderBit)) {
			case 'C':
			case 'D':
				result.add("MusicalScore");
				break;
			case 'E':
			case 'F':
				result.add("Map");
				break;
			case 'G':
				// We appear to have a number of items without 007 tags marked as G's.
				// These seem to be Videos rather than Slides.
				// result.add("Slide");
				result.add("Video");
				break;
			case 'I':
				result.add("SoundRecording");
				break;
			case 'J':
				result.add("MusicRecording");
				break;
			case 'K':
				result.add("Photo");
				break;
			case 'M':
				result.add("Electronic");
				break;
			case 'O':
			case 'P':
				result.add("Kit");
				break;
			case 'R':
				result.add("PhysicalObject");
				break;
			case 'T':
				result.add("Manuscript");
				break;
			}
		}
		if (!result.isEmpty() && returnFirstValue) {
			return result;
		}

		if (leader.length() >= 7) {
			// check the Leader at position 7
			leaderBit = leader.charAt(7);
			switch (Character.toUpperCase(leaderBit)) {
			// Monograph
			case 'M':
				if (formatCode == 'C') {
					result.add("eBook");
				} else {
					if (result.isEmpty()) {
						result.add("Book");
					}
				}
				break;
			// Serial
			case 'S':
				// Look in 008 to determine what type of Continuing Resource
				formatCode = fixedField.getData().toUpperCase().charAt(21);
				switch (formatCode) {
				case 'N':
					result.add("Newspaper");
					break;
				case 'P':
					result.add("Journal");
					break;
				default:
					result.add("Serial");
					break;
				}
			}
		}

		// Nothing worked!
		if (result.isEmpty()) {
			result.add("Unknown");
		}

		return result;
	}

	/**
	 * Determine the number of items for the record
	 * 
	 * @param Record
	 *          record
	 * @return Set format of record
	 */
	public String getNumHoldings(String itemField) {
		Set<String> input = getFieldList(record, itemField);
		int numHoldings = input.size();
		if (numHoldings == 0) {
			numHoldings = 1;
		}

		return Integer.toString(numHoldings);
	}

	/**
	 * Determine Record Format(s)
	 * 
	 * @param Record
	 *          record
	 * @return Set format of record
	 */
	public Set<String> getTargetAudience() {
		Set<String> result = new LinkedHashSet<String>();
		try {
			String leader = record.getLeader().toString();

			ControlField ohOhEightField = (ControlField) record.getVariableField("008");
			ControlField ohOhSixField = (ControlField) record.getVariableField("006");

			// check the Leader at position 6 to determine the type of field
			char recordType = Character.toUpperCase(leader.charAt(6));
			char bibLevel = Character.toUpperCase(leader.charAt(7));
			// Figure out what material type the record is
			if ((recordType == 'A' || recordType == 'T') && (bibLevel == 'A' || bibLevel == 'C' || bibLevel == 'D' || bibLevel == 'M') /* Books */
					|| (recordType == 'M') /* Computer Files */
					|| (recordType == 'C' || recordType == 'D' || recordType == 'I' || recordType == 'J') /* Music */
					|| (recordType == 'G' || recordType == 'K' || recordType == 'O' || recordType == 'R') /*
																																																 * Visual
																																																 * Materials
																																																 */
			) {
				char targetAudienceChar;
				if (ohOhSixField != null && ohOhSixField.getData().length() >= 5) {
					targetAudienceChar = Character.toUpperCase(ohOhSixField.getData().charAt(5));
					if (targetAudienceChar != ' ') {
						result.add(Character.toString(targetAudienceChar));
					}
				}
				if (result.size() == 0 && ohOhEightField != null && ohOhEightField.getData().length() >= 22) {
					targetAudienceChar = Character.toUpperCase(ohOhEightField.getData().charAt(22));
					if (targetAudienceChar != ' ') {
						result.add(Character.toString(targetAudienceChar));
					}
				} else if (result.size() == 0) {
					result.add("Unknown");
				}
			} else {
				result.add("Unknown");
			}
		} catch (Exception e) {
			// leader not long enough to get target audience
			logger.debug("ERROR in getTargetAudience ", e);
			result.add("Unknown");
		}

		if (result.size() == 0) {
			result.add("Unknown");
		}

		/*
		 * Iterator iter = result.iterator(); while (iter.hasNext()){
		 * System.out.println("Audience: " + iter.next().toString()); }
		 */

		return result;
	}

	/**
	 * Determine if a record is illustrated.
	 * 
	 * @param Record
	 *          record
	 * @return String "Illustrated" or "Not Illustrated"
	 */
	public String isIllustrated() {
		String leader = record.getLeader().toString();

		// Does the leader indicate this is a "language material" that might have
		// extra
		// illustration details in the fixed fields?
		if (leader.charAt(6) == 'a') {
			String currentCode = ""; // for use in loops below

			// List of 008/18-21 codes that indicate illustrations:
			String illusCodes = "abcdefghijklmop";

			// Check the illustration characters of the 008:
			ControlField fixedField = (ControlField) record.getVariableField("008");
			if (fixedField != null) {
				String fixedFieldText = fixedField.getData().toLowerCase();
				for (int i = 18; i <= 21; i++) {
					if (i < fixedFieldText.length()) {
						currentCode = fixedFieldText.substring(i, i + 1);
						if (illusCodes.contains(currentCode)) {
							return "Illustrated";
						}
					}
				}
			}

			// Now check if any 006 fields apply:
			@SuppressWarnings("unchecked")
			List<VariableField> fields = record.getVariableFields("006");
			Iterator<VariableField> fieldsIter = fields.iterator();
			if (fields != null) {
				while (fieldsIter.hasNext()) {
					fixedField = (ControlField) fieldsIter.next();
					String fixedFieldText = fixedField.getData().toLowerCase();
					for (int i = 1; i <= 4; i++) {
						if (i < fixedFieldText.length()) {
							currentCode = fixedFieldText.substring(i, i + 1);
							if (illusCodes.contains(currentCode)) {
								return "Illustrated";
							}
						}
					}
				}
			}
		}

		// Now check for interesting strings in 300 subfield b:
		@SuppressWarnings("unchecked")
		List<DataField> fields = record.getVariableFields("300");
		Iterator<DataField> fieldsIter = fields.iterator();
		if (fields != null) {
			DataField physical;
			while (fieldsIter.hasNext()) {
				physical = (DataField) fieldsIter.next();
				@SuppressWarnings("unchecked")
				List<Subfield> subfields = physical.getSubfields('b');
				Iterator<Subfield> subfieldsIter = subfields.iterator();
				if (subfields != null) {
					String desc;
					while (subfieldsIter.hasNext()) {
						Subfield curSubfield = subfieldsIter.next();
						desc = curSubfield.getData().toLowerCase();
						if (desc.contains("ill.") || desc.contains("illus.")) {
							return "Illustrated";
						}
					}
				}
			}
		}

		// If we made it this far, we found no sign of illustrations:
		return "Not Illustrated";
	}

	public String getDateAdded(String dateFieldSpec, String dateFormat) {
		// Get the date the record was added from the 907d tag (should only be one).
		Set<String> input = getFieldList(record, dateFieldSpec);
		Iterator<String> iter = input.iterator();
		while (iter.hasNext()) {
			String curDateAdded = iter.next();
			try {
				SimpleDateFormat formatter = new SimpleDateFormat(dateFormat);
				Date dateAdded = formatter.parse(curDateAdded);
				// System.out.println("Indexing " + curDateAdded + " " +
				// dateAdded.getTime());
				SimpleDateFormat formatter2 = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'");
				return formatter2.format(dateAdded);
			} catch (Exception ex) {
				// Syntax error in the regular expression
				System.out.println("Unable to parse date added " + curDateAdded);
			}
		}
		return null;
	}

	public String getRelativeTimeAdded(String dateFieldSpec, String dateFormat) {
		// Get the date the record was added from the 998b tag (should only be one).
		String dateAdded = getDateAdded(dateFieldSpec, dateFormat);
		if (dateAdded == null) return null;

		String curDateStr = (String) dateAdded;
		SimpleDateFormat formatter2 = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss'Z'");
		try {
			Date curDate = formatter2.parse(curDateStr);
			return getTimeSinceAddedForDate(curDate);
		} catch (ParseException e) {
			logger.error("Error parsing date " + curDateStr + " in getRelativeTimeAdded");
		}

		return null;
	}

	public String getTimeSinceAddedForDate(Date curDate) {
		long timeDifferenceDays = (new Date().getTime() - curDate.getTime()) / (1000 * 60 * 60 * 24);
		// System.out.println("Time Difference Days: " + timeDifferenceDays);
		if (timeDifferenceDays <= 1) {
			return "Day";
		}
		if (timeDifferenceDays <= 7) {
			return "Week";
		}
		if (timeDifferenceDays <= 30) {
			return "Month";
		}
		if (timeDifferenceDays <= 60) {
			return "2 Months";
		}
		if (timeDifferenceDays <= 90) {
			return "Quarter";
		}
		if (timeDifferenceDays <= 180) {
			return "Six Months";
		}
		if (timeDifferenceDays <= 365) {
			return "Year";
		}
		return null;
	}

	public void addTimeSinceAddedForDateToResults(Date curDate, Set<String> result) {
		result.add(getTimeSinceAddedForDate(curDate));
	}

	public Set<String> getLibraryRelativeTimeAdded(String itemField, String locationSubfield, String dateSubfield, String dateFormat, String activeSystem,
			String branchCodes) {
		// System.out.println("Branch Codes for " + activeSystem + " are " +
		// branchCodes);
		Set<String> result = new LinkedHashSet<String>();
		// Get a list of all 989 tags that store per item information
		@SuppressWarnings("unchecked")
		List<DataField> input = record.getVariableFields(itemField);
		Iterator<DataField> iter = input.iterator();
		String dateAddedStr = null;
		SimpleDateFormat formatter = new SimpleDateFormat(dateFormat);
		Date dateAddedDate = null;
		char locationChar = locationSubfield.charAt(0);
		char dateChar = dateSubfield.charAt(0);
		// System.out.println("Active System: " + activeSystem);
		while (iter.hasNext()) {
			DataField curField = (DataField) iter.next();
			try {
				if (curField.getSubfield(locationChar) != null && curField.getSubfield(locationChar).getData() != null){
					String branchCode = curField.getSubfield(locationChar).getData().toLowerCase().trim();
					// System.out.println("Testing branch code (" + branchCode + ") for " +
					// activeSystem);
					if (branchCode.matches(branchCodes)) {
						// System.out.println("Testing branch code (" + branchCode + ") for "
						// + activeSystem);
						if (curField.getSubfield(dateChar) != null){
							String dateAddedCurStr = curField.getSubfield(dateChar).getData();
							// System.out.println("Branch: " + branchCode + " - " +
							// dateAddedCurStr);
							Date dateAddedCurDate = formatter.parse(dateAddedCurStr);
							if (dateAddedStr == null) {
								dateAddedStr = dateAddedCurStr;
								dateAddedDate = dateAddedCurDate;
							} else if (dateAddedCurDate.getTime() < dateAddedDate.getTime()) {
								dateAddedStr = dateAddedCurStr;
								dateAddedDate = dateAddedCurDate;
							}
						}
					}
				}
			} catch (Exception e) {
				logger.debug("Non-fatal error loading relative time added", e);
			}
		}

		if (dateAddedDate != null) {
			// System.out.println("Date Added String:" + dateAddedStr + " Date: " +
			// dateAddedDate.toString());
			addTimeSinceAddedForDateToResults(dateAddedDate, result);
		}
		/*
		 * for (String curResult : result){ System.out.println("  " + curResult); }
		 */
		return result;
	}

	/**
	 * Extract a numeric portion of the Dewey decimal call number
	 * 
	 * Can return null
	 * 
	 * @param record
	 * @param fieldSpec
	 *          - which MARC fields / subfields need to be analyzed
	 * @param precisionStr
	 *          - a decimal number (represented in string format) showing the
	 *          desired precision of the returned number; i.e. 100 to round to
	 *          nearest hundred, 10 to round to nearest ten, 0.1 to round to
	 *          nearest tenth, etc.
	 * @return Set containing requested numeric portions of Dewey decimal call
	 *         numbers
	 */
	public Set<String> getDeweyNumber(String fieldSpec, String precisionStr) {
		// Initialize our return value:
		Set<String> result = new LinkedHashSet<String>();

		// Precision comes in as a string, but we need to convert it to a float:
		float precision = Float.parseFloat(precisionStr);

		// Loop through the specified MARC fields:
		Set<String> input = getFieldList(record, fieldSpec);
		Iterator<String> iter = input.iterator();
		while (iter.hasNext()) {
			// Get the current string to work on:
			String current = iter.next();

			if (CallNumUtils.isValidDewey(current)) {
				// Convert the numeric portion of the call number into a float:
				float currentVal = Float.parseFloat(CallNumUtils.getDeweyB4Cutter(current));

				// Round the call number value to the specified precision:
				Float finalVal = new Float(Math.floor(currentVal / precision) * precision);

				// Convert the rounded value back to a string (with leading zeros) and
				// save it:
				result.add(CallNumUtils.normalizeFloat(finalVal.toString(), 3, -1));
			}
		}

		// If we found no call number matches, return null; otherwise, return our
		// results:
		if (result.isEmpty()) return null;
		return result;
	}

	/**
	 * Normalize Dewey numbers for searching purposes (uppercase/stripped spaces)
	 * 
	 * Can return null
	 * 
	 * @param record
	 * @param fieldSpec
	 *          - which MARC fields / subfields need to be analyzed
	 * @return Set containing normalized Dewey numbers extracted from specified
	 *         fields.
	 */
	public Set<String> getDeweySearchable(String fieldSpec) {
		// Initialize our return value:
		Set<String> result = new LinkedHashSet<String>();

		// Loop through the specified MARC fields:
		Set<String> input = getFieldList(record, fieldSpec);
		Iterator<String> iter = input.iterator();
		while (iter.hasNext()) {
			// Get the current string to work on:
			String current = iter.next();

			// Add valid strings to the set, normalizing them to be all uppercase
			// and free from whitespace.
			if (CallNumUtils.isValidDewey(current)) {
				result.add(current.toUpperCase().replaceAll(" ", ""));
			}
		}

		// If we found no call numbers, return null; otherwise, return our results:
		if (result.isEmpty()) return null;
		return result;
	}

	/**
	 * Normalize Dewey numbers for sorting purposes (use only the first valid
	 * number!)
	 * 
	 * Can return null
	 * 
	 * @param record
	 * @param fieldSpec
	 *          - which MARC fields / subfields need to be analyzed
	 * @return String containing the first valid Dewey number encountered,
	 *         normalized for sorting purposes.
	 */
	public String getDeweySortable(String fieldSpec) {
		// Loop through the specified MARC fields:
		Set<String> input = getFieldList(record, fieldSpec);
		Iterator<String> iter = input.iterator();
		while (iter.hasNext()) {
			// Get the current string to work on:
			String current = iter.next();

			// If this is a valid Dewey number, return the sortable shelf key:
			if (CallNumUtils.isValidDewey(current)) {
				return CallNumUtils.getDeweyShelfKey(current);
			}
		}

		// If we made it this far, we didn't find a valid sortable Dewey number:
		return null;
	}

	public String checkSuppression(String locationField, String locationsToSuppress, String manualSuppressionField, String manualSuppressionValue) {
		// If all locations should be suppressed, then the record should be
		// suppressed.
		Set<String> input = getFieldList(record, locationField);
		boolean suppressRecord = false;
		if (input != null && input.size() > 0) {
			Iterator<String> iter = input.iterator();
			suppressRecord = true;
			while (iter.hasNext()) {
				String curLocationCode = iter.next();
				try {
					if (!curLocationCode.matches("locationsToSuppress")) {
						suppressRecord = false;
						break;
					}
				} catch (PatternSyntaxException ex) {
					// Syntax error in the regular expression
				}
			}
		}

		if (!suppressRecord) {
			// Now, check for manually suppressed record where the 907c tag is set to
			// W
			if (manualSuppressionField != null & !manualSuppressionField.equals("null")) {
				Set<String> input2 = getFieldList(record, manualSuppressionField);
				Iterator<String> iter2 = input2.iterator();
				suppressRecord = false;
				while (iter2.hasNext()) {
					String curCode = iter2.next();
					if (curCode.matches(manualSuppressionValue)) {
						suppressRecord = true;
						break;
					}
				}
			}
		}

		// Check to see if the record is already loaded into the eContent core
		if (!suppressRecord) {
			String ilsId = this.getId();
			if (marcProcessor.getExistingEContentIds().contains(ilsId)) {
				logger.debug("Suppressing because there is an eContent record for " + ilsId);
				suppressRecord = true;
			}
		}

		if (suppressRecord) {
			// return that the record is suppressed
			return "suppressed";
		} else {
			// return that the record is not suppressed
			return "notSuppressed";
		}
	}

	/**
	 * Determine Record Format(s)
	 * 
	 * @param Record
	 *          record
	 * @return Set format of record
	 */
	public Set<String> getAvailableLocations(String itemField, String statusSubFieldIndicator, String availableStatus, String locationSubField) {
		Set<String> result = new LinkedHashSet<String>();
		@SuppressWarnings("unchecked")
		List<VariableField> itemRecords = record.getVariableFields(itemField);
		char statusSubFieldChar = statusSubFieldIndicator.charAt(0);
		char locationSubFieldChar = locationSubField.charAt(0);
		for (int i = 0; i < itemRecords.size(); i++) {
			Object field = itemRecords.get(i);
			if (field instanceof DataField) {
				DataField dataField = (DataField) field;
				// Get subfield u (status)
				Subfield statusSubfield = dataField.getSubfield(statusSubFieldChar);
				if (statusSubfield != null) {
					String status = statusSubfield.getData().trim();
					if (status.equals("online")) {
						// If the tile is available online, force the location to be online
						result.add("online");
					} else if (status.matches(availableStatus)) {
						// If the book is checked in, show it as available
						// Get subfield m (location)
						Subfield subfieldM = dataField.getSubfield(locationSubFieldChar);
						result.add(subfieldM.getData().toLowerCase());
					}
				}
			}
		}
		return result;
	}

	@SuppressWarnings({ "unchecked", "rawtypes" })
	public Set<String> getAuthors() {
		Set<String> result = new HashSet<String>();
		Object author = getMappedFields("author").get("author");
		if (author != null) {
			if (author instanceof String) {
				result.add((String) author);
			} else {
				result.addAll((Set) author);
			}
		}
		Object author2 = getMappedFields("author2").get("author2");
		if (author2 != null) {
			if (author2 instanceof String) {
				result.add((String) author2);
			} else {
				result.addAll((Set) author2);
			}
		}
		return result;
	}

	private Boolean												isEContent								= null;
	private HashMap<String, DetectionSettings>	eContentDetectionSettings	= new HashMap<String, DetectionSettings>();

	/*
	 * Determine if the record is eContent or not.
	 */
	public boolean isEContent() {
		if (isEContent == null) {
			isEContent = false;
			// Treat the record as eContent if the records is:
			// 1) It is already in the eContent database
			// 2) It matches criteria in EContentRecordDetectionSettings
			for (DetectionSettings curSettings : marcProcessor.getDetectionSettings()) {
				Set<String> fieldData = getFieldList(curSettings.getFieldSpec());
				boolean isMatch = false;
				// logger.debug("Found " + fieldData.size() + " fields matching " +
				// curSettings.getFieldSpec());
				for (String curField : fieldData) {
					// logger.debug("Testing if value " + curField.toLowerCase() +
					// " matches " + curSettings.getValueToMatch());
					isMatch = ((String) curField.toLowerCase()).matches(".*" + curSettings.getValueToMatch().toLowerCase() + ".*");
					if (isMatch) break;
				}
				if (isMatch) {
					isEContent = isMatch;
					DetectionSettings detectionSettingsForSource = eContentDetectionSettings.get(curSettings.getSource());
					if (detectionSettingsForSource == null){
						eContentDetectionSettings.put(curSettings.getSource(), curSettings);
					}
				}
			}

			if (!isEContent) {
				String ilsId = this.getId();
				if (marcProcessor.getExistingEContentIds().contains(ilsId)) {
					//logger.info("Suppressing because there is an eContent record for " + ilsId);
					isEContent = true;
				}
			}
			
			return isEContent;
		} else {
			return isEContent;
		}
	}

	public HashMap<String, DetectionSettings> getEContentDetectionSettings() {
		if (isEContent()) {
			return eContentDetectionSettings;
		} else {
			return null;
		}
	}

	protected long getLibrarySystemIdForLocation(String locationCode) {
		// Get the library system id for the location. To do this, we are
		// going to do a couple
		// Of lookups to avoid having to create an entirely new table or
		// lookup map.
		// Eventually, we should store location codes in the database and
		// automatically
		// generate translation maps which would streamline this process.
		// 1) Get the facet name from the translation map
		Map<String, String> systemMap = marcProcessor.findMap("system_map");
		if (systemMap == null){
			logger.error("Unable to load system map!");
		}
		String librarySystemFacet = Utils.remap(locationCode, systemMap, true);
		// 2) Now that we have the facet, get the id of the system
		Long librarySystemId = marcProcessor.getLibrarySystemIdFromFacet(librarySystemFacet);
		if (librarySystemId == null) {
			librarySystemId = -1L;
		}
		return librarySystemId;
	}
	
	public String createXmlDoc() throws ParserConfigurationException, FactoryConfigurationError, TransformerException { 
		XMLBuilder builder = XMLBuilder.create("add");
		XMLBuilder doc = builder.e("doc");
		HashMap <String, Object> allFields = getFields("createXmlDoc");
		Iterator<String> keyIterator = allFields.keySet().iterator();
		while (keyIterator.hasNext()){
			String fieldName = keyIterator.next();
			Object fieldValue = allFields.get(fieldName);
			if (fieldValue instanceof String){
				if (fieldName.equals("fullrecord")){
					//doc.e("field").a("name", fieldName).cdata( ((String)fieldValue).getBytes() );
					//doc.e("field").a("name", fieldName).data( ((String)fieldValue).getBytes());
					doc.e("field").a("name", fieldName).data( Util.encodeSpecialCharacters((String)fieldValue).getBytes());
					System.out.println(Util.encodeSpecialCharacters((String)fieldValue));
				}else{
					doc.e("field").a("name", fieldName).t((String)fieldValue);
				}
			}else if (fieldValue instanceof Set){
				@SuppressWarnings("unchecked")
				Set<String> fieldValues = (Set<String>)fieldValue;
				Iterator<String> fieldValuesIter = fieldValues.iterator();
				while(fieldValuesIter.hasNext()){
					doc.e("field").a("name", fieldName).t(fieldValuesIter.next());
				}
			}
		}
		String recordXml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" + builder.asString();
		//logger.info("XML for " + recordInfo.getId() + "\r\n" + recordXml);
		return recordXml;
	}
	
	public String toString(){
		String rawRecord = getRawRecord();
		rawRecord = rawRecord.replaceAll("\\x1F", "#31;");
		rawRecord = rawRecord.replaceAll("\\x1E", "#30;");
		rawRecord = rawRecord.replaceAll("\\x1D", "#29;");
		return rawRecord;
	}
}
