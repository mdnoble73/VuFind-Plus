package com.peakc.marmot;

import org.apache.log4j.Logger;
import org.marc4j.marc.*;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;

public class BasicMarcInfo {
	private Record record;
	private String id;
	private String title;
	private String sortTitle;
	private String mainAuthor;
	private ArrayList<String> authors;
	private ArrayList<String> otherAuthors;
	private String isbn;
	private String upc;
	private String publisher;
	private String description;
	private HashSet<String> genres;
	private String genre;
	private ArrayList<String> subjects;
	private HashSet<String> collections;
	private String format;
	private boolean urlsLoaded = false;
	private String purchaseUrl;
	private String sourceUrl;
	
	private Logger logger;
	
	@SuppressWarnings("unchecked")
	public boolean load(Record record, Logger logger){
		//Preload basic information that nearly everything will need
		this.record = record;
		this.logger = logger;
		//System.out.println(record);
		
		String id = getFieldValue(record, "950", new char[] { 'a' });
		if (id == null || id.length() == 0 ) {
			logger.error("Could not find id for record");
			return false;
		} else {
			logger.debug("Procesing id " + id);
			this.id = id;
		}
		// Get the title
		title = getFieldValue(record, "245", new char[] { 'a', 'b', 'n' });
		//Get number of non-filing characters for the title
		VariableField field = record.getVariableField("245");
		sortTitle = "";
		if (field != null && field instanceof DataField) {
			DataField dataField = (DataField) field;
			sortTitle += (dataField.getSubfield('a') == null) ? "" : " " + dataField.getSubfield('a').getData();
			if (dataField.getIndicator2() != ' '){
				int numNonFilingChars = Integer.parseInt(Character.toString(dataField.getIndicator2()));
				if (numNonFilingChars < title.length()){
					sortTitle = title.substring(numNonFilingChars);
				}
			}
			sortTitle += (dataField.getSubfield('b') == null) ? "" : " " + dataField.getSubfield('b').getData();
			sortTitle += (dataField.getSubfield('n') == null) ? "" : " " + dataField.getSubfield('n').getData();
			sortTitle = sortTitle.toLowerCase();
		}
		
		// Get the author(s)
		mainAuthor = getFieldValue(record, "100", new char[]{'a'});
		if (mainAuthor.length() == 0 ){
			mainAuthor = getFieldValue(record, "110", new char[]{'a'});
		}
		authors = getFieldValues(record, new String[] { "100", "700" }, new char[] { 'a', 'b', 'c' });
		authors.addAll(getFieldValues(record, new String[] { "110", "710" }, new char[] { 'a', 'b' }));
		//Get rid of the main author for other authors 
		otherAuthors = (ArrayList<String>)authors.clone();
		for (String author : otherAuthors){
			if (author.equals(mainAuthor)){
				otherAuthors.remove(author);
				break;
			}
		}
		
	// Get the ISBN and UPC (ignore format category since that is derived)
		isbn = getFieldValue(record, "020", new char[] { 'a' });
		// Strip the format if any
		if (isbn.contains(" ")) {
			isbn = isbn.substring(0, isbn.indexOf(' '));
		}
		upc = getFieldValue(record, "024", new char[] { 'a' });
		
		publisher = getFieldValue(record, "260", new char[] { 'b' });
		
		description = getFieldValue(record, "520", new char[] { 'a' });
		
		return true;
	}
	
	protected String getFieldValue(Record record, String tag, char[] subfields) {
		VariableField field = record.getVariableField(tag);
		StringBuffer fieldValue = new StringBuffer();
		if (field != null && field instanceof DataField) {
			DataField dataField = (DataField) field;
			for (char subfield : subfields) {
				if (dataField.getSubfield(subfield) != null && dataField.getSubfield(subfield).getData() != null){
					//System.out.println(dataField.getSubfield(subfield).getData());
					if (fieldValue.length() > 0){
						fieldValue.append(" ");
					}
					fieldValue.append( dataField.getSubfield(subfield).getData());
				}
			}
		}

		return fieldValue.toString().trim();
	}

	protected ArrayList<String> getFieldValues(Record record, String[] tags, char[] subfields) {
		@SuppressWarnings("unchecked")
		List<VariableField> fields = (List<VariableField>)record.getVariableFields(tags);
		ArrayList<String> fieldValues = new ArrayList<String>();
		for (VariableField field : fields) {
			//System.out.println("Found field " + field.getTag());
			StringBuffer fieldValue = new StringBuffer();
			if (field != null && field instanceof DataField) {
				DataField dataField = (DataField) field;
				for (char subfield : subfields) {
					if (dataField.getSubfield(subfield) != null){
						//System.out.println("Found subfield " + subfield + " " + dataField.getSubfield(subfield).toString());
						if (dataField.getSubfield(subfield).getData() != null){
							//System.out.println(dataField.getSubfield(subfield).getData());
							if (fieldValue.length() > 0){
								fieldValue.append(" ");
							}
							fieldValue.append( dataField.getSubfield(subfield).getData());
						}
					}
				}
			}
			if (fieldValue.length() > 0){
				fieldValues.add(fieldValue.toString().trim());
			}
		}

		return fieldValues;
	}

	public String getId() {
		return id;
	}

	public void setId(String id) {
		this.id = id;
	}

	public String getTitle() {
		return title;
	}
	
	public String getMainTitle() {
		return getFieldValue(record, "245", new char[]{'a'});
	}
	public String getSubTitle(){
		return getFieldValue(record, "245", new char[]{'b'});
	}

	public void setTitle(String title) {
		this.title = title;
	}

	public String getIsbn() {
		return isbn;
	}
	
	public ArrayList<String> getAllIsbns(){
		ArrayList<String> allIsbns = getFieldValues(record, new String[]{"020"}, new char[]{'a'});
		return allIsbns;
	}
	
	public ArrayList<String> getIssn(){
		ArrayList<String> mainIssn = getFieldValues(record, new String[]{"022"}, new char[]{'a'});
		ArrayList<String> additionalIssn = getFieldValues(record, new String[]{"440", "490", "730", "776", "780", "785"}, new char[]{'x'});
		mainIssn.addAll(additionalIssn);
		return mainIssn;
	}
	public String getLccn() {
		return getFieldValue(record, "010", new char['a']);
	}
	

	public void setIsbn(String isbn) {
		this.isbn = isbn;
	}

	public String getUpc() {
		return upc;
	}

	public void setUpc(String upc) {
		this.upc = upc;
	}

	public String getPublisher() {
		return publisher;
	}

	public void setPublisher(String publisher) {
		this.publisher = publisher;
	}

	public String getDescription() {
		return description;
	}

	public void setDescription(String description) {
		this.description = description;
	}

	public HashSet<String> getGenres() {
		return genres;
	}

	public void setGenres(HashSet<String> genres) {
		this.genres = genres;
	}

	public String getGenre() {
		return genre;
	}

	public void setGenre(String genre) {
		this.genre = genre;
	}

	public String getFormat() {
		return format;
	}

	public void setFormat(String format) {
		this.format = format;
	}

	public ArrayList<String> getAuthors() {
		return authors;
	}

	public void setAuthors(ArrayList<String> authors) {
		this.authors = authors;
	}

	public ArrayList<String> getSubjects() {
		if (subjects == null){
			subjects = new ArrayList<String>();
			@SuppressWarnings("unchecked")
			List<DataField> subjectFields = (List<DataField>) record.getVariableFields(new String[] { "600", "610", "630", "650", "651", "655" });
			for (DataField subject : subjectFields) {
				@SuppressWarnings("rawtypes")
				List subFields = subject.getSubfields();
				StringBuffer subjectTitle = new StringBuffer();
				for (Object subFieldObj : subFields) {
					Subfield subField = (Subfield) subFieldObj;
					if (subField.getCode() != 2) {
						if (subjectTitle.length() > 0) {
							subjectTitle.append(" -- ").append(subField.getData());
						} else {
							subjectTitle.append(subField.getData());
						}

					}
				}
				subjects.add(subjectTitle.toString());
			}
		}
		return subjects;
	}
	
	public HashSet<String> getGeneres(){
		if (genres == null){
			genres = new HashSet<String>();
			genres.addAll(getFieldValues(record, new String[] { "600", "610", "611", "630", "648", "650", "651", "655" }, new char[] { 'v' }));
			genres.addAll(getFieldValues(record, new String[] { "655" }, new char[] { 'a' }));
		}
		return genres;
	}
	
	public HashSet<String> getCollections(){
		if (collections == null){
			//System.out.println("Loading collections");
			collections = new HashSet<String>();
			collections.addAll(getFieldValues(record, new String[] { "949" }, new char[] { 'c' }));
		}
		return collections;
	}
	
	public HashSet<String> getTargetAudience() {
		HashSet<String> result = new HashSet<String>();
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
				char targetAudienceChar = ' ';
				if (ohOhSixField != null && ohOhSixField.getData() != null && ohOhSixField.getData().length() >= 5) {
					targetAudienceChar = Character.toUpperCase(ohOhSixField.getData().charAt(5));
				}
				if (targetAudienceChar == ' ' && ohOhEightField != null && ohOhEightField.getData() != null && ohOhEightField.getData().length() >= 22) {
					targetAudienceChar = Character.toUpperCase(ohOhEightField.getData().charAt(22));
				}
				if (targetAudienceChar == ' '){
					result.add("E"); //Assume Adult if we don't get a better indicator
				}else{
					result.add(Character.toString(targetAudienceChar));
				}
			} else {
				result.add("Unknown");
			}
		} catch (Exception e) {
			logger.error("ERROR in getTargetAudience " + e.toString());
			e.printStackTrace();
			result.add("Unknown");
		}

		return result;
	}
	
	public HashSet<String> getTargetAudienceTranslated(HashMap<String, String> targetAudienceMap) {
		HashSet<String> targetAudience = getTargetAudience();
		HashSet<String> result = new HashSet<String>();
		for (String curAudience : targetAudience){
			if (targetAudienceMap.containsKey(curAudience)){
				//logger.info("translated audience " + curAudience + " to " + targetAudienceMap.get(curAudience));
				result.add(targetAudienceMap.get(curAudience));
			}else{
				//logger.warn("Could not find translation for audience " + curAudience + " there are " + targetAudienceMap.size() + " rows in the map");
				result.add(curAudience);
			}
		}

		return result;
	}

	public String getFormatCategory(HashMap<String, String> formatCategoryMap) {
		for (String collection : getCollections()) {
			String formatCategory = formatCategoryMap.get(collection);
			if (formatCategory != null) {
				return formatCategory;
			}
		}
		return "";
	}
	
	public String getFormat(HashMap<String, String> formatMap) {
		for (String collection : getCollections()) {
			String format = formatMap.get(collection);
			if (format != null) {
				return format;
			}
		}
		return "";
	}

	public void setSortTitle(String sortTitle) {
		this.sortTitle = sortTitle;
	}

	public String getSortTitle() {
		return sortTitle;
	}

	public String getControlNumber() {
		ControlField ohOhOneField = (ControlField) record.getVariableField("001");
		if (ohOhOneField != null && ohOhOneField.getData() != null){
			return ohOhOneField.getData().trim();
		}else{
			return null;
		}
	}
	
	public String toString(){
		return record.toString();
	}
	
	public String getMainAuthor() {
		return mainAuthor;
	}

	public ArrayList<String> getOtherAuthors() {
		return otherAuthors;
	}
	
	public ArrayList<String> getContents(){
		return getFieldValues(record, new String[]{"505"}, new char[]{'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t'});
	}

	public String getLanguage() {
		//TODO: Fix this to correctly return language code
		return "English";
	}

	public String getEdition() {
		return getFieldValue(record, "250", new char[]{'a'});
	}
	public ArrayList<String> getTopic() {
		ArrayList<String> topics = getFieldValues(record, new String[]{"600", "610"}, new char[]{'a','b','c','d','e','f','g','h','j','k','l','m','n','o','p','q','r','s','t','u','v','x','y','z'});
		topics.addAll(getFieldValues(record, new String[]{"630"}, new char[]{'a','b','f','g','h','k','l','m','n','o','p','q','r','s','t','v','x','y','z'}));
		topics.addAll(getFieldValues(record, new String[]{"650"}, new char[]{'a','b','c','d','e','v','x','y','z'}));
		return topics;
	}
	public ArrayList<String> getAllGenres() {
		return getFieldValues(record, new String[]{"655"}, new char[]{'a','b','c','v','x','y','z'});
	}
	public ArrayList<String> getRegions() {
		return getFieldValues(record, new String[]{"651"}, new char[]{'a','v','x','y','z'});
	}
	public ArrayList<String> getEra() {
		ArrayList<String> era = getFieldValues(record, new String[]{"600"}, new char[]{'d'});
		era.addAll(getFieldValues(record, new String[]{"648"}, new char[]{'a'}));
		era.addAll(getFieldValues(record, new String[]{"610", "611","630","648", "650","651","655"}, new char[]{'y'}));
		return era;
	}
	public String getSourceUrl(){
		loadUrls();
		return sourceUrl;
	}
	public String getPurchaseUrl(){
		loadUrls();
		return purchaseUrl;
	}
	public void loadUrls(){
		if (urlsLoaded) return;
		@SuppressWarnings("unchecked")
		List<VariableField> eightFiftySixFields = record.getVariableFields("856");
		for (VariableField eightFiftySixField : eightFiftySixFields){
			DataField eightFiftySixDataField = (DataField)eightFiftySixField;
			String url = null;
			if (eightFiftySixDataField.getSubfield('u') != null){
				url = eightFiftySixDataField.getSubfield('u').getData();
			}
			String text = null;
			if (eightFiftySixDataField.getSubfield('y') != null){
				text = eightFiftySixDataField.getSubfield('y').getData();
			}else if (eightFiftySixDataField.getSubfield('z') != null){
				text = eightFiftySixDataField.getSubfield('z').getData();
			}else if (eightFiftySixDataField.getSubfield('3') != null){
				text = eightFiftySixDataField.getSubfield('3').getData();
			}
			
			if (text != null && url != null){
				if (text.matches("(?i).*?(?:download|access online|electronic book|access digital media|access title).*?")  ){
					if (!url.matches("(?i).*?vufind.*?")){
						//System.out.println("Found source url");
						sourceUrl = url;
					}
				}else if (text.matches("(?i).*?(?:cover|review).*?")){
					//File is an enrichment url
				}else if (text.matches("(?i).*?purchase.*?")){
					//System.out.println("Found purchase URL");
					purchaseUrl = url;
				}else if (url.matches("(?i).*?(idm.oclc.org/login|ezproxy).*?")){
					sourceUrl = url;
				}else{
					logger.info("Unknown URL " + url + " " + text);
					
				}
			}
		}
		urlsLoaded = true;
	}
	public String getPublishDate(){
		return getFieldValue(record, "260", new char[]{'c'});
	}
}
