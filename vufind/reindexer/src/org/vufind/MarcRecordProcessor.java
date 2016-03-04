package org.vufind;

import org.apache.log4j.Logger;
import org.marc4j.marc.*;
import org.solrmarc.tools.Utils;

import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.regex.PatternSyntaxException;

/**
 * Description goes here
 * Pika
 * User: Mark Noble
 * Date: 9/29/2014
 * Time: 12:01 PM
 */
public abstract class MarcRecordProcessor {
	protected Logger logger;
	protected GroupedWorkIndexer indexer;
	Pattern mpaaRatingRegex1 = null;
	Pattern mpaaRatingRegex2 = null;
	private HashSet<String> unknownSubjectForms = new HashSet<>();

	public MarcRecordProcessor(GroupedWorkIndexer indexer, Logger logger) {
		this.indexer = indexer;
		this.logger = logger;
	}

	/**
	 * Load MARC record from disk based on identifier
	 * Then call updateGroupedWorkSolrDataBasedOnMarc to do the actual update of the work
	 *
	 * @param groupedWork the work to be updated
	 * @param identifier the identifier to load information for
	 */
	public abstract void processRecord(GroupedWorkSolr groupedWork, String identifier);

	protected void loadSubjects(GroupedWorkSolr groupedWork, Record record){
		groupedWork.addSubjects(getAllSubfields(record, "600[abcdefghjklmnopqrstuvxyz]:610[abcdefghjklmnopqrstuvxyz]:611[acdefghklnpqstuvxyz]:630[abfghklmnoprstvxyz]:650[abcdevxyz]:651[abcdevxyz]:655[abcvxyz]:690[axyz]", " -- "));
		groupedWork.addTopic(getFieldList(record, "600abcdefghjklmnopqrstuvxyz:610abcdefghjklmnopqrstuvxyz:611acdefghklnpqstuvxyz:630abfghklmnoprstvxyz:650abcdevxyz:651abcdevxyz:690axyz"));
		groupedWork.addTopicFacet(getFieldList(record, "600a:600x:610x:611x:630a:630x:648x:650a:650x:651x:655x"));
		//Add lc subjects
		groupedWork.addLCSubjects(getLCSubjects(record));
		//Add bisac subjects
		groupedWork.addBisacSubjects(getBisacSubjects(record));
		groupedWork.addGenre(getFieldList(record, "655abcvxyz"));
		groupedWork.addGenreFacet(getFieldList(record, "600v:610v:611v:630v:648v:650v:651v:655a:655v"));
		groupedWork.addGeographic(getFieldList(record, "651avxyz"));
		groupedWork.addGeographicFacet(getFieldList(record, "600z:610z:611z:630z:648z:650z:651a:651z:655z"));
		groupedWork.addEra(getFieldList(record, "600d:610y:611y:630y:648a:648y:650y:651y:655y"));
	}

	protected void updateGroupedWorkSolrDataBasedOnStandardMarcData(GroupedWorkSolr groupedWork, Record record, HashSet<ItemInfo> printItems, String identifier, String format) {
		loadTitles(groupedWork, record);
		loadAuthors(groupedWork, record, identifier);
		loadSubjects(groupedWork, record);
		/*List<DataField> seriesFields = getDataFields(record, "490");
		HashSet<String> allSeries = new HashSet<>();
		for (DataField seriesField : seriesFields){
			if (seriesField.getIndicator1() == '0' || seriesField.getIndicator1() == '1'){
				if (seriesField.getSubfield('a') != null){
					allSeries.add()
				}

			}
		}*/
		List<DataField> seriesFields = getDataFields(record, "830");
		HashSet<String> seriesWithVolumes = new HashSet<>();
		Pattern subfields830Pattern = Pattern.compile("[ap]");
		for (DataField seriesField : seriesFields){
			String series = Util.trimTrailingPunctuation(getSpecifiedSubfieldsAsString(seriesField, subfields830Pattern,"")).toString();
			//Remove anything in parens since it's normally just the format
			series = series.replaceAll("\\s+\\(.*?\\)", "");
			//Remove the word series at the end since this gets cataloged inconsistently
			series = series.replaceAll("(?i)\\s+series$", "");
			if (seriesField.getSubfield('v') != null){
				//Separate out the volume so we can link specially
				series += "|" + seriesField.getSubfield('v').getData();
			}
			seriesWithVolumes.add(series.toString());
		}
		seriesFields = getDataFields(record, "800");
		Pattern subfields800Pattern = Pattern.compile("[pqt]");
		for (DataField seriesField : seriesFields){
			String series = Util.trimTrailingPunctuation(getSpecifiedSubfieldsAsString(seriesField, subfields800Pattern,"")).toString();
			//Remove anything in parens since it's normally just the format
			series = series.replaceAll("\\s+\\(.*?\\)", "");
			//Remove the word series at the end since this gets cataloged inconsistently
			series = series.replaceAll("(?i)\\s+series$", "");

			if (seriesField.getSubfield('v') != null){
				//Separate out the volume so we can link specially
				series += "|" + seriesField.getSubfield('v').getData();
			}
			seriesWithVolumes.add(series.toString());
		}
		groupedWork.addSeriesWithVolume(seriesWithVolumes);

		groupedWork.addSeries(getFieldList(record, "830ap:800pqt"));
		groupedWork.addSeries2(getFieldList(record, "490a"));
		groupedWork.addDateSpan(getFieldList(record, "362a"));
		groupedWork.addContents(getFieldList(record, "505a:505t"));
		groupedWork.addIssns(getFieldList(record, "022a"));
		groupedWork.addOclcNumbers(getFieldList(record, "035a"));
		groupedWork.addIsbns(getFieldList(record, "020a"), format);
		List<DataField> upcFields = getDataFields(record, "024");
		for (DataField upcField : upcFields){
			if (upcField.getIndicator1() == '1' && upcField.getSubfield('a') != null){
				groupedWork.addUpc(upcField.getSubfield('a').getData());
			}
		}

		loadAwards(groupedWork, record);
		loadBibCallNumbers(groupedWork, record, identifier);
		loadLiteraryForms(groupedWork, record, printItems, identifier);
		loadTargetAudiences(groupedWork, record, printItems, identifier);
		groupedWork.addMpaaRating(getMpaaRating(record));
		//Do not load ar data from MARC since we now get it directly from Renaissance Learning
		/*groupedWork.setAcceleratedReaderInterestLevel(getAcceleratedReaderInterestLevel(record));
		groupedWork.setAcceleratedReaderReadingLevel(getAcceleratedReaderReadingLevel(record));
		groupedWork.setAcceleratedReaderPointValue(getAcceleratedReaderPointLevel(record));*/
		groupedWork.addKeywords(getAllSearchableFields(record, 100, 900));
	}

	protected void loadAwards(GroupedWorkSolr groupedWork, Record record){
		Set<String> awardFields = getFieldList(record, "586a");
		HashSet<String> awards = new HashSet<>();
		for (String award : awardFields){
			//Normalize the award name
			if (award.contains("Caldecott")) {
				award = "Caldecott Medal";
			}else if (award.contains("Pulitzer") || award.contains("Puliter")){
				award = "Pulitzer Prize";
			}else if (award.contains("Newbery")){
				award = "Newbery Medal";
			}else {
				if (award.contains(":")) {
					String[] awardParts = award.split(":");
					award = awardParts[0].trim();
				}
				//Remove dates
				award = award.replaceAll("\\d{2,4}", "");
				//Remove punctuation
				award = award.replaceAll("[^\\w\\s]", "");
			}
			awards.add(award.trim());
		}
		groupedWork.addAwards(awards);
	}


	protected Set<String> getBisacSubjects(Record record){
		HashSet<String> bisacSubjects = new HashSet<>();
		List<DataField> fields = getDataFields(record, "650");
		for (DataField field : fields){
			if (field.getIndicator2() == '0' || field.getIndicator2() == '1') {
				continue;
			}
			if (field.getSubfield('2') != null){
				if (field.getSubfield('2').getData().equals("bisacsh") ||
						field.getSubfield('2').getData().equals("bisacmt") ||
						field.getSubfield('2').getData().equals("bisacrt")){
					if (field.getSubfield('a') != null){
						bisacSubjects.add(field.getSubfield('a').getData());
					}
					if (field.getSubfield('x') != null){
						bisacSubjects.add(field.getSubfield('x').getData());
					}
				}
			}
		}
		return bisacSubjects;
	}


	protected Set<String> getLCSubjects(Record record) {
		HashSet<String> lcSubjects = new HashSet<>();
		List<DataField> fields = getDataFields(record, "650");
		for (DataField field : fields){
			if (field.getIndicator2() == '0' || field.getIndicator2() == '1'){
				if (field.getSubfield('2') != null){
					if (field.getSubfield('2').getData().equals("bisacsh") ||
							field.getSubfield('2').getData().equals("bisacmt") ||
							field.getSubfield('2').getData().equals("bisacrt")){
						continue;
					}
				}
				if (field.getSubfield('a') != null){
					lcSubjects.add(field.getSubfield('a').getData());
				}
				if (field.getSubfield('x') != null){
					lcSubjects.add(field.getSubfield('x').getData());
				}
			}
		}
		return lcSubjects;
	}

	protected abstract void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier);

	protected void loadEditions(GroupedWorkSolr groupedWork, Record record, HashSet<RecordInfo> ilsRecords) {
		Set<String> editions = getFieldList(record, "250a");
		if (editions.size() > 0) {
			String edition = editions.iterator().next();
			for (RecordInfo ilsRecord : ilsRecords) {
				ilsRecord.setEdition(edition);
			}
		}
		groupedWork.addEditions(editions);
	}

	protected void loadPhysicalDescription(GroupedWorkSolr groupedWork, Record record, HashSet<RecordInfo> ilsRecords) {
		Set<String> physicalDescriptions = getFieldList(record, "300abcefg:530abcd");
		if (physicalDescriptions.size() > 0){
			String physicalDescription = physicalDescriptions.iterator().next();
			for(RecordInfo ilsRecord : ilsRecords){
				ilsRecord.setPhysicalDescription(physicalDescription);
			}
		}
		groupedWork.addPhysical(physicalDescriptions);
	}

	protected String getCallNumberSubject(Record record, String fieldSpec) {
		String val = getFirstFieldVal(record, fieldSpec);

		if (val != null) {
			String[] callNumberSubject = val.toUpperCase().split("[^A-Z]+");
			if (callNumberSubject.length > 0) {
				return callNumberSubject[0];
			}
		}
		return null;
	}

	public String getMpaaRating(Record record) {
		if (mpaaRatingRegex1 == null) {
			mpaaRatingRegex1 = Pattern.compile(
					"(?:.*?)Rated\\s(G|PG-13|PG|R|NC-17|NR|X)(?:.*)", Pattern.CANON_EQ);
		}
		if (mpaaRatingRegex2 == null) {
			mpaaRatingRegex2 = Pattern.compile(
					"(?:.*?)(G|PG-13|PG|R|NC-17|NR|X)\\sRated(?:.*)", Pattern.CANON_EQ);
		}
		String val = getFirstFieldVal(record, "521a");

		if (val != null) {
			if (val.matches("Rated\\sNR\\.?|Not Rated\\.?|NR")) {
				return "Not Rated";
			}
			try {
				Matcher mpaaMatcher1 = mpaaRatingRegex1.matcher(val);
				if (mpaaMatcher1.find()) {
					// System.out.println("Matched matcher 1, " + mpaaMatcher1.group(1) +
					// " Rated " + getId());
					return mpaaMatcher1.group(1) + " Rated";
				} else {
					Matcher mpaaMatcher2 = mpaaRatingRegex2.matcher(val);
					if (mpaaMatcher2.find()) {
						// System.out.println("Matched matcher 2, " + mpaaMatcher2.group(1)
						// + " Rated " + getId());
						return mpaaMatcher2.group(1) + " Rated";
					} else {
						return null;
					}
				}
			} catch (PatternSyntaxException ex) {
				// Syntax error in the regular expression
				return null;
			}
		} else {
			return null;
		}
	}

	protected void loadTargetAudiences(GroupedWorkSolr groupedWork, Record record, HashSet<ItemInfo> printItems, String identifier) {
		Set<String> targetAudiences = new LinkedHashSet<>();
		try {
			String leader = record.getLeader().toString();

			ControlField ohOhEightField = (ControlField) record.getVariableField("008");
			ControlField ohOhSixField = (ControlField) record.getVariableField("006");

			// check the Leader at position 6 to determine the type of field
			char recordType = Character.toUpperCase(leader.charAt(6));
			char bibLevel = Character.toUpperCase(leader.charAt(7));
			// Figure out what material type the record is
			if ((recordType == 'A' || recordType == 'T')
					&& (bibLevel == 'A' || bibLevel == 'C' || bibLevel == 'D' || bibLevel == 'M') /* Books */
					|| (recordType == 'M') /* Computer Files */
					|| (recordType == 'C' || recordType == 'D' || recordType == 'I' || recordType == 'J') /* Music */
					|| (recordType == 'G' || recordType == 'K' || recordType == 'O' || recordType == 'R') /*
																																																 * Visual
																																																 * Materials
																																																 */
					) {
				char targetAudienceChar;
				if (ohOhSixField != null && ohOhSixField.getData().length() > 5) {
					targetAudienceChar = Character.toUpperCase(ohOhSixField.getData()
							.charAt(5));
					if (targetAudienceChar != ' ') {
						targetAudiences.add(Character.toString(targetAudienceChar));
					}
				}
				if (targetAudiences.size() == 0 && ohOhEightField != null
						&& ohOhEightField.getData().length() > 22) {
					targetAudienceChar = Character.toUpperCase(ohOhEightField.getData()
							.charAt(22));
					if (targetAudienceChar != ' ') {
						targetAudiences.add(Character.toString(targetAudienceChar));
					}
				} else if (targetAudiences.size() == 0) {
					targetAudiences.add("Unknown");
				}
			} else {
				targetAudiences.add("Unknown");
			}
		} catch (Exception e) {
			// leader not long enough to get target audience
			logger.debug("ERROR in getTargetAudience ", e);
			targetAudiences.add("Unknown");
		}

		if (targetAudiences.size() == 0) {
			targetAudiences.add("Unknown");
		}

		groupedWork.addTargetAudiences(indexer.translateSystemCollection("target_audience", targetAudiences, identifier));
		groupedWork.addTargetAudiencesFull(indexer.translateSystemCollection("target_audience_full", targetAudiences, identifier));
	}

	protected void loadLiteraryForms(GroupedWorkSolr groupedWork, Record record, HashSet<ItemInfo> printItems, String identifier) {
		//First get the literary Forms from the 008.  These need translation
		LinkedHashSet<String> literaryForms = new LinkedHashSet<>();
		try {
			String leader = record.getLeader().toString();

			ControlField ohOhEightField = (ControlField) record.getVariableField("008");
			ControlField ohOhSixField = (ControlField) record.getVariableField("006");

			// check the Leader at position 6 to determine the type of field
			char recordType = Character.toUpperCase(leader.charAt(6));
			char bibLevel = Character.toUpperCase(leader.charAt(7));
			// Figure out what material type the record is
			if (((recordType == 'A' || recordType == 'T') && (bibLevel == 'A' || bibLevel == 'C' || bibLevel == 'D' || bibLevel == 'M')) /* Books */
					) {
				char literaryFormChar;
				if (ohOhSixField != null && ohOhSixField.getData().length() > 16) {
					literaryFormChar = Character.toUpperCase(ohOhSixField.getData().charAt(16));
					if (literaryFormChar != ' ') {
						literaryForms.add(Character.toString(literaryFormChar));
					}
				}
				if (literaryForms.size() == 0 && ohOhEightField != null && ohOhEightField.getData().length() > 33) {
					literaryFormChar = Character.toUpperCase(ohOhEightField.getData().charAt(33));
					if (literaryFormChar != ' ') {
						literaryForms.add(Character.toString(literaryFormChar));
					}
				}
				if (literaryForms.size() == 0) {
					literaryForms.add(" ");
				}
			} else {
				literaryForms.add("Unknown");
			}
		} catch (Exception e) {
			logger.error("Unexpected error", e);
		}
		if (literaryForms.size() > 1){
			//Uh oh, we have a problem
			logger.warn("Received multiple literary forms for a single marc record");
		}
		groupedWork.addLiteraryForms(indexer.translateSystemCollection("literary_form", literaryForms, identifier));
		groupedWork.addLiteraryFormsFull(indexer.translateSystemCollection("literary_form_full", literaryForms, identifier));

		//Now get literary forms from the subjects, these don't need translation
		HashMap<String, Integer> literaryFormsWithCount = new HashMap<>();
		HashMap<String, Integer> literaryFormsFull = new HashMap<>();
		//Check the subjects
		Set<String> subjectFormData = getFieldList(record, "650v:651v");
		for(String subjectForm : subjectFormData){
			subjectForm = Util.trimTrailingPunctuation(subjectForm);
			if (subjectForm.equalsIgnoreCase("Fiction")
					|| subjectForm.equalsIgnoreCase("Young adult fiction" )
					|| subjectForm.equalsIgnoreCase("Juvenile fiction" )
					|| subjectForm.equalsIgnoreCase("Junior fiction" )
					|| subjectForm.equalsIgnoreCase("Comic books, strips, etc")
					|| subjectForm.equalsIgnoreCase("Comic books,strips, etc")
					|| subjectForm.equalsIgnoreCase("Children's fiction" )
					|| subjectForm.equalsIgnoreCase("Fictional Works" )
					|| subjectForm.equalsIgnoreCase("Cartoons and comics" )
					|| subjectForm.equalsIgnoreCase("Folklore" )
					|| subjectForm.equalsIgnoreCase("Legends" )
					|| subjectForm.equalsIgnoreCase("Stories" )
					|| subjectForm.equalsIgnoreCase("Fantasy" )
					|| subjectForm.equalsIgnoreCase("Mystery fiction")
					|| subjectForm.equalsIgnoreCase("Romances")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
			}else if (subjectForm.equalsIgnoreCase("Biography")){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Non Fiction");
			}else if (subjectForm.equalsIgnoreCase("Novela juvenil")
					|| subjectForm.equalsIgnoreCase("Novela")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				addToMapWithCount(literaryFormsFull, "Novels");
			}else if (subjectForm.equalsIgnoreCase("Drama")
					|| subjectForm.equalsIgnoreCase("Dramas")
					|| subjectForm.equalsIgnoreCase("Juvenile drama")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				addToMapWithCount(literaryFormsFull, "Dramas");
			}else if (subjectForm.equalsIgnoreCase("Poetry")
					|| subjectForm.equalsIgnoreCase("Juvenile Poetry")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Poetry");
			}else if (subjectForm.equalsIgnoreCase("Humor")
					|| subjectForm.equalsIgnoreCase("Juvenile Humor")
					|| subjectForm.equalsIgnoreCase("Comedy")
					|| subjectForm.equalsIgnoreCase("Wit and humor")
					|| subjectForm.equalsIgnoreCase("Satire")
					|| subjectForm.equalsIgnoreCase("Humor, Juvenile")
					|| subjectForm.equalsIgnoreCase("Humour")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				addToMapWithCount(literaryFormsFull, "Humor, Satires, etc.");
			}else if (subjectForm.equalsIgnoreCase("Correspondence")
					){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Letters");
			}else if (subjectForm.equalsIgnoreCase("Short stories")
					){
				addToMapWithCount(literaryFormsWithCount, "Fiction");
				addToMapWithCount(literaryFormsFull, "Fiction");
				addToMapWithCount(literaryFormsFull, "Short stories");
			}else if (subjectForm.equalsIgnoreCase("essays")
					){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Essays");
			}else if (subjectForm.equalsIgnoreCase("Personal narratives, American")
					|| subjectForm.equalsIgnoreCase("Personal narratives, Polish")
					|| subjectForm.equalsIgnoreCase("Personal narratives, Sudanese")
					|| subjectForm.equalsIgnoreCase("Personal narratives, Jewish")
					|| subjectForm.equalsIgnoreCase("Personal narratives")
					|| subjectForm.equalsIgnoreCase("Guidebooks")
					|| subjectForm.equalsIgnoreCase("Guide-books")
					|| subjectForm.equalsIgnoreCase("Handbooks, manuals, etc")
					|| subjectForm.equalsIgnoreCase("Problems, exercises, etc")
					|| subjectForm.equalsIgnoreCase("Case studies")
					|| subjectForm.equalsIgnoreCase("Handbooks")
					|| subjectForm.equalsIgnoreCase("Biographies")
					|| subjectForm.equalsIgnoreCase("Interviews")
					|| subjectForm.equalsIgnoreCase("Autobiography")
					|| subjectForm.equalsIgnoreCase("Cookbooks")
					|| subjectForm.equalsIgnoreCase("Dictionaries")
					|| subjectForm.equalsIgnoreCase("Encyclopedias")
					|| subjectForm.equalsIgnoreCase("Encyclopedias, Juvenile")
					|| subjectForm.equalsIgnoreCase("Dictionaries, Juvenile")
					|| subjectForm.equalsIgnoreCase("Nonfiction")
					|| subjectForm.equalsIgnoreCase("Non-fiction")
					|| subjectForm.equalsIgnoreCase("Juvenile non-fiction")
					|| subjectForm.equalsIgnoreCase("Maps")
					|| subjectForm.equalsIgnoreCase("Catalogs")
					|| subjectForm.equalsIgnoreCase("Recipes")
					|| subjectForm.equalsIgnoreCase("Diaries")
					|| subjectForm.equalsIgnoreCase("Designs and Plans")
					|| subjectForm.equalsIgnoreCase("Reference books")
					|| subjectForm.equalsIgnoreCase("Travel guide")
					|| subjectForm.equalsIgnoreCase("Textbook")
					|| subjectForm.equalsIgnoreCase("Atlas")
					|| subjectForm.equalsIgnoreCase("Atlases")
					|| subjectForm.equalsIgnoreCase("Study guides")
					){
				addToMapWithCount(literaryFormsWithCount, "Non Fiction");
				addToMapWithCount(literaryFormsFull, "Non Fiction");
			}else{
				if (!unknownSubjectForms.contains(subjectForm)){
					//logger.warn("Unknown subject form " + subjectForm);
					unknownSubjectForms.add(subjectForm);
				}
			}
		}
		groupedWork.addLiteraryForms(literaryFormsWithCount);
		groupedWork.addLiteraryFormsFull(literaryFormsFull);
	}

	private void addToMapWithCount(HashMap<String, Integer> map, String elementToAdd){
		if (map.containsKey(elementToAdd)){
			map.put(elementToAdd, map.get(elementToAdd) + 1);
		}else{
			map.put(elementToAdd, 1);
		}
	}

	protected void loadPublicationDetails(GroupedWorkSolr groupedWork, Record record, HashSet<RecordInfo> ilsRecords) {
		//Load publishers
		Set<String> publishers = this.getPublishers(record);
		groupedWork.addPublishers(publishers);
		if (publishers.size() > 0){
			String publisher = publishers.iterator().next();
			for(RecordInfo ilsRecord : ilsRecords){
				ilsRecord.setPublisher(publisher);
			}
		}

		//Load publication dates
		Set<String> publicationDates = this.getPublicationDates(record);
		groupedWork.addPublicationDates(publicationDates);
		if (publicationDates.size() > 0){
			String publicationDate = publicationDates.iterator().next();
			for(RecordInfo ilsRecord : ilsRecords){
				ilsRecord.setPublicationDate(publicationDate);
			}
		}

	}

	public Set<String> getPublicationDates(Record record) {
		@SuppressWarnings("unchecked")
		List<VariableField> rdaFields = record.getVariableFields("264");
		HashSet<String> publicationDates = new HashSet<>();
		String date;
		//Try to get from RDA data
		if (rdaFields.size() > 0){
			for (VariableField curField : rdaFields){
				if (curField instanceof DataField){
					DataField dataField = (DataField)curField;
					if (dataField.getIndicator2() == '1'){
						Subfield subFieldC = dataField.getSubfield('c');
						if (subFieldC != null){
							date = subFieldC.getData();
							publicationDates.add(date);
						}
					}
				}
			}
		}
		//Try to get from 260
		if (publicationDates.size() ==0) {
			publicationDates.addAll(getFieldList(record, "260c"));
		}
		//Try to get from 008, but only need to do if we don't have anything else
		if (publicationDates.size() == 0) {
			publicationDates.add(getFirstFieldVal(record, "008[7-10]"));
		}

		return publicationDates;
	}

	public Set<String> getPublishers(Record record){
		Set<String> publisher = new LinkedHashSet<>();
		//First check for 264 fields
		@SuppressWarnings("unchecked")

		List<DataField> rdaFields = getDataFields(record, "264");
		if (rdaFields.size() > 0){
			for (DataField curField : rdaFields){
				if (curField.getIndicator2() == '1'){
					Subfield subFieldB = curField.getSubfield('b');
					if (subFieldB != null){
						publisher.add(subFieldB.getData());
					}
				}
			}
		}
		publisher.addAll(getFieldList(record, "260b"));
		return publisher;
	}

	protected String languageFields = "008[35-37]:041a";
	protected String translationFields = "041b:041d:041h:041j";
	protected void loadLanguageDetails(GroupedWorkSolr groupedWork, Record record, HashSet<RecordInfo> ilsRecords, String identifier) {
		Set <String> languages = getFieldList(record, languageFields);
		HashSet<String> translatedLanguages = new HashSet<>();
		boolean isFirstLanguage = true;
		for (String language : languages){
			String translatedLanguage = indexer.translateSystemValue("language", language, identifier);
			translatedLanguages.add(translatedLanguage);
			if (isFirstLanguage){
				for (RecordInfo ilsRecord : ilsRecords){
					ilsRecord.setPrimaryLanguage(translatedLanguage);
				}
			}
			isFirstLanguage = false;
			String languageBoost = indexer.translateSystemValue("language_boost", language, identifier);
			if (languageBoost != null){
				Long languageBoostVal = Long.parseLong(languageBoost);
				groupedWork.setLanguageBoost(languageBoostVal);
			}
			String languageBoostEs = indexer.translateSystemValue("language_boost_es", language, identifier);
			if (languageBoostEs != null){
				Long languageBoostVal = Long.parseLong(languageBoostEs);
				groupedWork.setLanguageBoostSpanish(languageBoostVal);
			}
		}
		groupedWork.setLanguages(translatedLanguages);

		Set<String> translations = getFieldList(record, translationFields);
		translatedLanguages = new HashSet<>();
		for (String translation : translations) {
			String translatedLanguage = indexer.translateSystemValue("language", translation, identifier);
			translatedLanguages.add(translatedLanguage);
		}
		groupedWork.setTranslations(translatedLanguages);
	}

	protected void loadAuthors(GroupedWorkSolr groupedWork, Record record, String identifier) {
		//auth_author = 100abcd, first
		groupedWork.setAuthAuthor(this.getFirstFieldVal(record, "100abcd"));
		//author = a, first
		//MDN 2/6/2016 - Do not use 710 because it is not truly the author.  This has the potential
		//of showing some disconnects with how records are grouped, but improves the display of the author
		//710 is still indexed as part of author 2 #ARL-146
		//groupedWork.setAuthor(this.getFirstFieldVal(record, "100abcdq:110ab:710a"));
		groupedWork.setAuthor(this.getFirstFieldVal(record, "100abcdq:110ab"));
		//author-letter = 100a, first
		groupedWork.setAuthorLetter(this.getFirstFieldVal(record, "100a"));
		//auth_author2 = 700abcd
		groupedWork.addAuthAuthor2(this.getFieldList(record, "700abcd"));
		//author2 = 110ab:111ab:700abcd:710ab:711ab:800a
		groupedWork.addAuthor2(this.getFieldList(record, "110ab:111ab:700abcd:710ab:711ab:800a"));
		//author_additional = 505r:245c
		groupedWork.addAuthorAdditional(this.getFieldList(record, "505r:245c"));
		//Load contributors with role
		List<DataField> contributorFields = this.getDataFields(record, new String[]{"700","710"});
		HashSet<String> contributors = new HashSet<>();
		Pattern contributorSubfieldPattern = Pattern.compile("[abcdetmnr]");
		for (DataField contributorField : contributorFields){
			StringBuilder contributor = getSpecifiedSubfieldsAsString(contributorField, contributorSubfieldPattern, "");
			if (contributorField.getTag().equals("700") && contributorField.getSubfield('4') != null){
				String role = indexer.translateSystemValue("contributor_role", Util.trimTrailingPunctuation(contributorField.getSubfield('4').getData()), identifier);
				contributor.append("|" + role);
			}
			contributors.add(contributor.toString());
		}
		groupedWork.addAuthor2Role(contributors);

		//author_display = 100a:110a:260b:710a:245c, first
		//#ARL-95 Do not show display author from the 710 or from the 245c since neither are truly authors
		String displayAuthor = this.getFirstFieldVal(record, "100a:110ab:260b");
		if (displayAuthor != null && displayAuthor.indexOf(';') > 0){
			displayAuthor = displayAuthor.substring(0, displayAuthor.indexOf(';') -1);
		}
		groupedWork.setAuthorDisplay(displayAuthor);
	}

	protected void loadTitles(GroupedWorkSolr groupedWork, Record record) {
		//title (full title done by index process by concatenating short and subtitle

		//title short
		groupedWork.setTitle(this.getFirstFieldVal(record, "245a"));
		//title sub
		//MDN 2/6/2016 add np to subtitle #ARL-163
		groupedWork.setSubTitle(this.getFirstFieldVal(record, "245bnp"));
		//display title
		groupedWork.setDisplayTitle(this.getFirstFieldVal(record, "245abnp"));
		//title full
		groupedWork.addFullTitles(this.getAllSubfields(record, "245", " "));
		//title sort
		groupedWork.setSortableTitle(this.getSortableTitle(record));
		//title alt
		groupedWork.addAlternateTitles(this.getFieldList(record, "130adfgklnpst:240a:246a:700tnr:730adfgklnpst:740a"));
		//title old
		groupedWork.addOldTitles(this.getFieldList(record, "780ast"));
		//title new
		groupedWork.addNewTitles(this.getFieldList(record, "785ast"));
	}

	protected void loadBibCallNumbers(GroupedWorkSolr groupedWork, Record record, String identifier) {
		groupedWork.setCallNumberA(getFirstFieldVal(record, "099a:090a:050a"));
		String firstCallNumber = getFirstFieldVal(record, "099a[0]:090a[0]:050a[0]");
		if (firstCallNumber != null){
			groupedWork.setCallNumberFirst(indexer.translateSystemValue("callnumber", firstCallNumber, identifier));
		}
		String callNumberSubject = getCallNumberSubject(record, "090a:050a");
		if (callNumberSubject != null){
			groupedWork.setCallNumberSubject(indexer.translateSystemValue("callnumber_subject", callNumberSubject, identifier));
		}
	}

	protected String getFirstFieldVal(Record record, String fieldSpec) {
		Set<String> result = getFieldList(record, fieldSpec);
		if (result.size() == 0){
			return null;
		}else{
			return result.iterator().next();
		}
	}

	protected List<DataField> getDataFields(Record marcRecord, String tag) {
		List variableFields = marcRecord.getVariableFields(tag);
		List<DataField> variableFieldsReturn = new ArrayList<>();
		for (Object variableField : variableFields){
			if (variableField instanceof DataField){
				variableFieldsReturn.add((DataField)variableField);
			}
		}
		return variableFieldsReturn;
	}

	protected List<DataField> getDataFields(Record marcRecord, String[] tags) {
		List variableFields = marcRecord.getVariableFields(tags);
		List<DataField> variableFieldsReturn = new ArrayList<>();
		for (Object variableField : variableFields){
			if (variableField instanceof DataField){
				variableFieldsReturn.add((DataField)variableField);
			}
		}
		return variableFieldsReturn;
	}

	protected ControlField getControlField(Record marcRecord, String tag){
		List variableFields = marcRecord.getVariableFields(tag);
		ControlField variableFieldReturn = null;
		for (Object variableField : variableFields){
			if (variableField instanceof ControlField){
				variableFieldReturn = (ControlField)variableField;
			}
		}
		return variableFieldReturn;
	}

	/**
	 * Loops through all datafields and creates a field for "keywords"
	 * searching. Shameless stolen from Vufind Indexer Custom Code
	 *
	 * @param lowerBound
	 *          - the "lowest" marc field to include (e.g. 100)
	 * @param upperBound
	 *          - one more than the "highest" marc field to include (e.g. 900 will
	 *          include up to 899).
	 * @return a string containing ALL subfields of ALL marc fields within the
	 *         range indicated by the bound string arguments.
	 */
	@SuppressWarnings("unchecked")
	public String getAllSearchableFields(Record record, int lowerBound, int upperBound) {
		StringBuilder buffer = new StringBuilder("");

		List<DataField> fields = record.getDataFields();
		for (DataField field : fields) {
			// Get all fields starting with the 100 and ending with the 839
			// This will ignore any "code" fields and only use textual fields
			int tag = localParseInt(field.getTag(), -1);
			if ((tag >= lowerBound) && (tag < upperBound)) {
				// Loop through subfields
				List<Subfield> subfields = field.getSubfields();
				for (Subfield subfield : subfields) {
					if (buffer.length() > 0)
						buffer.append(" ");
					buffer.append(subfield.getData());
				}
			}
		}

		return buffer.toString();
	}

	/**
	 * return an int for the passed string
	 *
	 * @param str The String value of the integer to prompt
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
	protected Set<String> getFieldList(Record record, String tagStr) {
		String[] tags = tagStr.split(":");
		Set<String> result = new LinkedHashSet<>();
		for (String tag1 : tags) {
			// Check to ensure tag length is at least 3 characters
			if (tag1.length() < 3) {
				System.err.println("Invalid tag specified: " + tag1);
				continue;
			}

			// Get Field Tag
			String tag = tag1.substring(0, 3);
			boolean linkedField = false;
			if (tag.equals("LNK")) {
				tag = tag1.substring(3, 6);
				linkedField = true;
			}
			// Process Subfields
			String subfield = tag1.substring(3);
			boolean havePattern = false;
			int subend = 0;
			// brackets indicate parsing for individual characters or as pattern
			int bracket = tag1.indexOf('[');
			if (bracket != -1) {
				String sub[] = tag1.substring(bracket + 1).split("[\\]\\[\\-, ]+");
				try {
					// if bracket expression is digits, expression is treated as character
					// positions
					int substart = Integer.parseInt(sub[0]);
					subend = (sub.length > 1) ? Integer.parseInt(sub[1]) + 1
							: substart + 1;
					String subfieldWObracket = subfield.substring(0, bracket - 3);
					result.addAll(getSubfieldDataAsSet(record, tag, subfieldWObracket,
							substart, subend));
				} catch (NumberFormatException e) {
					// assume brackets expression is a pattern such as [a-z]
					havePattern = true;
				}
			}
			if (subend == 0) // don't want specific characters.
			{
				String separator = null;
				if (subfield.indexOf('\'') != -1) {
					separator = subfield.substring(subfield.indexOf('\'') + 1,
							subfield.length() - 1);
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
	 * Get the specified substring of subfield values from the specified MARC
	 * field, returned as a set of strings to become lucene document field values
	 *
	 * @param record
	 *          - the marc record object
	 * @param fldTag
	 *          - the field name, e.g. 008
	 * @param subfield
	 *          - the string containing the desired subfields
	 * @param beginIx
	 *          - the beginning index of the substring of the subfield value
	 * @param endIx
	 *          - the ending index of the substring of the subfield value
	 * @return the result set of strings
	 */
	@SuppressWarnings("unchecked")
	protected Set<String> getSubfieldDataAsSet(Record record, String fldTag, String subfield, int beginIx, int endIx) {
		Set<String> resultSet = new LinkedHashSet<>();

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
					// automatic concatenation of grouped subFields
					StringBuilder buffer = new StringBuilder("");
					List<Subfield> subFields = dfield.getSubfields();
					for (Subfield sf : subFields) {
						if (subfield.indexOf(sf.getCode()) != -1
								&& sf.getData().length() >= endIx) {
							if (buffer.length() > 0)
								buffer.append(" ");
							buffer.append(sf.getData().substring(beginIx, endIx));
						}
					}
					resultSet.add(buffer.toString());
				} else {
					// get all instances of the single subfield
					List<Subfield> subFlds = dfield.getSubfields(subfield.charAt(0));
					for (Subfield sf : subFlds) {
						if (sf.getData().length() >= endIx)
							resultSet.add(sf.getData().substring(beginIx, endIx));
					}
				}
			} else // Control Field
			{
				String cfldData = ((ControlField) vf).getData();
				if (cfldData.length() >= endIx)
					resultSet.add(cfldData.substring(beginIx, endIx));
			}
		}
		return resultSet;
	}

	/**
	 * Get the specified subfields from the specified MARC field, returned as a
	 * set of strings to become lucene document field values
	 *
	 * @param fldTag
	 *          - the field name, e.g. 245
	 * @param subfieldsStr
	 *          - the string containing the desired subfields
	 * @param separator
	 *          - the separator string to insert between subfield items (if null,
	 *          a " " will be used)
	 * @return a Set of String, where each string is the concatenated contents of
	 *          all the desired subfield values from a single instance of the
	 *          fldTag
	 */
	@SuppressWarnings("unchecked")
	protected Set<String> getSubfieldDataAsSet(Record record, String fldTag, String subfieldsStr, String separator) {
		Set<String> resultSet = new LinkedHashSet<>();

		// Process Leader
		if (fldTag.equals("000")) {
			resultSet.add(record.getLeader().toString());
			return resultSet;
		}

		// Loop through Data and Control Fields
		// int iTag = new Integer(fldTag).intValue();
		List<VariableField> varFlds = record.getVariableFields(fldTag);
		if (varFlds == null){
			return resultSet;
		}
		for (VariableField vf : varFlds) {
			if (!isControlField(fldTag) && subfieldsStr != null) {
				// DataField
				DataField dfield = (DataField) vf;

				if (subfieldsStr.length() > 1 || separator != null) {
					// concatenate subfields using specified separator or space
					StringBuilder buffer = new StringBuilder("");
					List<Subfield> subFields = dfield.getSubfields();
					for (Subfield sf : subFields) {
						if (subfieldsStr.indexOf(sf.getCode()) != -1) {
							if (buffer.length() > 0) {
								buffer.append(separator != null ? separator : " ");
							}
							buffer.append(sf.getData().trim());
						}
					}
					if (buffer.length() > 0){
						resultSet.add(buffer.toString());
					}
				} else if (subfieldsStr.length() == 1) {
					// get all instances of the single subfield
					List<Subfield> subFields = dfield.getSubfields(subfieldsStr.charAt(0));
					for (Subfield sf : subFields) {
						resultSet.add(sf.getData().trim());
					}
				} else {
					logger
							.warn("No subfield provided when getting getSubfieldDataAsSet for "
									+ fldTag);
				}
			} else {
				// Control Field
				resultSet.add(((ControlField) vf).getData().trim());
			}
		}
		return resultSet;
	}

	protected boolean isControlField(String fieldTag) {
		return fieldTag.matches("00[0-9]");
	}

	/**
	 * Given a tag for a field, and a list (or regex) of one or more subfields get
	 * any linked 880 fields and include the appropriate subfields as a String
	 * value in the result set.
	 *
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
	public Set<String> getLinkedFieldValue(Record record, String tag, String subfield, String separator) {
		// assume brackets expression is a pattern such as [a-z]
		Set<String> result = new LinkedHashSet<>();
		Pattern subfieldPattern = null;
		if (subfield.indexOf('[') != -1) {
			subfieldPattern = Pattern.compile(subfield);
		}
		List<VariableField> fields = record.getVariableFields("880");
		for (VariableField vf : fields) {
			DataField dfield = (DataField) vf;
			Subfield link = dfield.getSubfield('6');
			if (link != null && link.getData().startsWith(tag)) {
				List<Subfield> subList = dfield.getSubfields();
				StringBuilder buf = new StringBuilder("");
				for (Subfield subF : subList) {
					boolean addIt = false;
					if (subfieldPattern != null) {
						Matcher matcher = subfieldPattern.matcher("" + subF.getCode());
						// matcher needs a string, hence concat with empty
						// string
						if (matcher.matches())
							addIt = true;
					} else {
						// a list a subfields
						if (subfield.indexOf(subF.getCode()) != -1)
							addIt = true;
					}
					if (addIt) {
						if (buf.length() > 0)
							buf.append(separator != null ? separator : " ");
						buf.append(subF.getData().trim());
					}
				}
				if (buf.length() > 0)
					result.add(Utils.cleanData(buf.toString()));
			}
		}
		return (result);
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
	public Set<String> getAllSubfields(Record record, String fieldSpec, String separator) {
		Set<String> result = new LinkedHashSet<>();

		String[] fldTags = fieldSpec.split(":");
		for (String fldTag1 : fldTags) {
			// Check to ensure tag length is at least 3 characters
			if (fldTag1.length() < 3) {
				System.err.println("Invalid tag specified: " + fldTag1);
				continue;
			}

			String fldTag = fldTag1.substring(0, 3);

			String subfldTags = fldTag1.substring(3);

			List<VariableField> marcFieldList = record.getVariableFields(fldTag);
			if (!marcFieldList.isEmpty()) {
				Pattern subfieldPattern = Pattern
						.compile(subfldTags.length() == 0 ? "." : subfldTags);
				for (VariableField vf : marcFieldList) {
					DataField marcField = (DataField) vf;

					StringBuilder buffer = getSpecifiedSubfieldsAsString(marcField, subfieldPattern, separator);
					if (buffer.length() > 0)
						result.add(Utils.cleanData(buffer.toString()));
				}
			}
		}

		return result;
	}

	protected StringBuilder getSpecifiedSubfieldsAsString(DataField marcField, Pattern subfieldPattern, String separator) {
		StringBuilder buffer = new StringBuilder("");
		List<Subfield> subFields = marcField.getSubfields();
		for (Subfield subfield : subFields) {
			Matcher matcher = subfieldPattern.matcher("" + subfield.getCode());
			if (matcher.matches()) {
				if (buffer.length() > 0)
					buffer.append(separator != null ? separator : " ");
				buffer.append(subfield.getData().trim());
			}
		}
		return buffer;
	}

	protected void loadEContentUrl(Record record, ItemInfo itemInfo) {
		List<DataField> urlFields = getDataFields(record, "856");
		for (DataField urlField : urlFields){
			//load url into the item
			if (urlField.getSubfield('u') != null){
				//Try to determine if this is a resource or not.
				if (urlField.getIndicator1() == '4' || urlField.getIndicator1() == ' ' || urlField.getIndicator1() == '0'){
					if (urlField.getIndicator2() == ' ' || urlField.getIndicator2() == '0' || urlField.getIndicator2() == '1' || urlField.getIndicator2() == '4') {
						itemInfo.seteContentUrl(urlField.getSubfield('u').getData().trim());
						break;
					}
				}

			}
		}
	}

	/**
	 * Get the title (245ab) from a record, without non-filing chars as specified
	 * in 245 2nd indicator, and lower cased.
	 *
	 * @return 245a and 245b values concatenated, with trailing punctuation removed, and
	 *         with non-filing characters omitted. Null returned if no title can
	 *         be found.
	 */
	public String getSortableTitle(Record record) {
		DataField titleField = (DataField) record.getVariableField("245");
		if (titleField == null || titleField.getSubfield('a') == null)
			return "";

		int nonFilingInt = getInd2AsInt(titleField);

		String title = titleField.getSubfield('a').getData();
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
		if (Character.isDigit(ind2char))
			result = Integer.valueOf(String.valueOf(ind2char));
		return result;
	}
}
