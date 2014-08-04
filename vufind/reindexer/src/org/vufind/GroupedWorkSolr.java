package org.vufind;

import org.apache.log4j.Logger;
import org.apache.solr.common.SolrInputDocument;

import java.util.*;

/**
 * A representation of the grouped record as it will be added to Solr.
 *
 * VuFind-Plus
 * User: Mark Noble
 * Date: 11/25/13
 * Time: 3:19 PM
 */
public class GroupedWorkSolr {
	private String id;
	private HashSet<String> relatedRecordIds = new HashSet<String>();
	private HashSet<String> relatedItems = new HashSet<String>();

	private String acceleratedReaderInterestLevel;
	private String acceleratedReaderReadingLevel;
	private String acceleratedReaderPointValue;
	private String allFields = "";
	private HashSet<String> alternateIds = new HashSet<String>();
	private String authAuthor;
	private String author;
	private String authorLetter;
	private HashSet<String> authorAdditional = new HashSet<String>();
	private String authorDisplay;
	private HashSet<String> author2 = new HashSet<String>();
	private HashSet<String> authAuthor2 = new HashSet<String>();
	private HashSet<String> author2Role = new HashSet<String>();
	private HashSet<String> awards = new HashSet<String>();
	private HashSet<String> availableAt = new HashSet<String>();
	private HashMap<String, HashSet<String>> availabilityToggleByLibrarySystem = new HashMap<String, HashSet<String>>();
	private HashSet<String> barcodes = new HashSet<String>();
	private String callNumberA;
	private String callNumberFirst;
	private String callNumberSubject;
	private HashSet<String> collectionGroup = new HashSet<String>();
	private HashMap<String, HashSet<String>> additionalCollections = new HashMap<String, HashSet<String>>();
	private HashSet<String> contents = new HashSet<String>();
	private Date dateAdded = null;
	private HashSet<String> dateSpans = new HashSet<String>();
	private HashSet<String> detailedLocation = new HashSet<String>();
	private HashSet<String> description = new HashSet<String>();
	private String displayDescription = "";
	private String displayDescriptionFormat = "";
	private String displayTitle;
	private Long earliestPublicationDate = null;
	private HashSet<String> econtentDevices = new HashSet<String>();
	private HashSet<String> econtentProtectionTypes = new HashSet<String>();
	private HashSet<String> econtentSources = new HashSet<String>();
	private HashSet<String> editions = new HashSet<String>();
	private HashSet<String> eras = new HashSet<String>();
	private HashSet<String> formats = new HashSet<String>();
	private HashSet<String> formatCategories = new HashSet<String>();
	private Long formatBoost = 1L;
	private HashSet<String> fullTitles = new HashSet<String>();
	private HashSet<String> genres = new HashSet<String>();
	private HashSet<String> genreFacets = new HashSet<String>();
	private HashSet<String> geographic = new HashSet<String>();
	private HashSet<String> geographicFacets = new HashSet<String>();
	private String groupingCategory;
	private HashSet<String> isbns = new HashSet<String>();
	private HashSet<String> issns = new HashSet<String>();
	private HashSet<String> iTypes = new HashSet<String>();
	private HashSet<String> keywords = new HashSet<String>();
	private HashSet<String> languages = new HashSet<String>();
	private Long languageBoost = 1L;
	private Long languageBoostSpanish = 1L;
	private HashSet<String> lccns = new HashSet<String>();
	private String lexileScore = "-1";
	private String lexileCode = "";
	private HashMap<String, Integer> literaryFormFull = new HashMap<String, Integer>();
	private HashMap<String, Integer> literaryForm = new HashMap<String, Integer>();
	private HashMap<String, Long> localBoost = new HashMap<String, Long>();
	private String localCallNumber;
	private HashMap<String, HashSet<String>> localCallNumbers = new HashMap<String, HashSet<String>>();
	private HashMap<String, HashSet<String>> localEContentProtectionTypes = new HashMap<String, HashSet<String>>();
	private HashMap<String, HashSet<String>> localEContentSources = new HashMap<String, HashSet<String>>();
	private HashMap<String, HashSet<String>> localITypes = new HashMap<String, HashSet<String>>();
	private HashMap<String, Date> localTimeSinceAdded = new HashMap<String, Date>();
	private HashSet<String> mpaaRatings = new HashSet<String>();
	private Long numHoldings = 0L;
	private HashSet<String> oclcs = new HashSet<String>();
	private HashSet<String> owningLibraries = new HashSet<String>();
	private HashSet<String> owningLocations = new HashSet<String>();
	private HashSet<String> physicals = new HashSet<String>();
	private float popularity;
	private HashSet<String> publishers = new HashSet<String>();
	private HashSet<String> publicationDates = new HashSet<String>();
	private float rating = 2.5f;
	private HashSet<String> series = new HashSet<String>();
	private HashSet<String> series2 = new HashSet<String>();
	private HashMap<String, String> sortableCallNumbers = new HashMap<String, String>();
	private String subTitle;
	private HashSet<String> targetAudienceFull = new HashSet<String>();
	private HashSet<String> targetAudience = new HashSet<String>();
	private String title;
	private HashSet<String> titleAlt = new HashSet<String>();
	private HashSet<String> titleOld = new HashSet<String>();
	private HashSet<String> titleNew = new HashSet<String>();
	private String titleSort;
	private HashSet<String> topics = new HashSet<String>();
	private HashSet<String> topicFacets = new HashSet<String>();
	private HashSet<String> upcs = new HashSet<String>();
	private HashSet<String> usableBy = new HashSet<String>();

	private TreeMap<String, ScopedWorkDetails> scopedWorkDetails = new TreeMap<String, ScopedWorkDetails>();
	private TreeMap<String, LocalizedWorkDetails> localizedWorkDetails = new TreeMap<String, LocalizedWorkDetails>();

	private Logger logger;
	private GroupedWorkIndexer groupedWorkIndexer;

	public GroupedWorkSolr(GroupedWorkIndexer groupedWorkIndexer, Logger logger) {
		this.logger = logger;
		this.groupedWorkIndexer = groupedWorkIndexer;

		//Setup the scopes for the work
		createScopes(groupedWorkIndexer.getScopes());
		createLocalizations(groupedWorkIndexer.getLocalizations());
	}

	private void createLocalizations(TreeSet<LocalizationInfo> localizations) {
		for (LocalizationInfo localizationInfo: localizations){
			this.localizedWorkDetails.put(localizationInfo.getLocalName(), new LocalizedWorkDetails(localizationInfo));
		}
	}

	private void createScopes(TreeSet<Scope> scopes) {
		for (Scope curScope : scopes) {
			this.scopedWorkDetails.put(curScope.getScopeName(), new ScopedWorkDetails(curScope));
		}
	}

	public TreeMap<String, ScopedWorkDetails> getScopedWorkDetails(){
		return this.scopedWorkDetails;
	}

	public TreeMap<String, LocalizedWorkDetails> getLocalizedWorkDetails(){
		return this.localizedWorkDetails;
	}

	public SolrInputDocument getSolrDocument(int availableAtBoostValue, int ownedByBoostValue) {
		SolrInputDocument doc = new SolrInputDocument();
		//Main identification
		doc.addField("id", id);
		doc.addField("alternate_ids", alternateIds);
		doc.addField("recordtype", "grouped_work");
		//Related records and sources
		doc.addField("related_record_ids", relatedRecordIds);
		doc.addField("related_record_items", relatedItems);
		//Ownership and location
		doc.addField("owning_library", owningLibraries);
		doc.addField("owning_location", owningLocations);
		doc.addField("collection_group", collectionGroup);
		for (String additionalCollection : additionalCollections.keySet()){
			doc.addField("collection_" + additionalCollection, additionalCollections.get(additionalCollection));
		}
		doc.addField("detailed_location", detailedLocation);
		doc.addField("available_at", availableAt);

		//Determine who can use the record
		doc.addField("usable_by", usableBy);

		//Title and variations
		String fullTitle = title;
		if (subTitle != null){
			fullTitle += " " + subTitle;
		}
		doc.addField("title", fullTitle);
		doc.addField("title_display", displayTitle);
		doc.addField("title_sub", subTitle);
		doc.addField("title_short", title);
		doc.addField("title_full", fullTitles);
		doc.addField("title_sort", titleSort);
		doc.addField("title_alt", titleAlt);
		doc.addField("title_old", titleOld);
		doc.addField("title_new", titleNew);

		//author and variations
		doc.addField("auth_author", authAuthor);
		doc.addField("author", author);
		doc.addField("author-letter", authorLetter);
		doc.addField("auth_author2", authAuthor2);
		doc.addField("author2", author2);
		doc.addField("author2-role", author2Role);
		doc.addField("author_additional", authorAdditional);
		doc.addField("author_display", authorDisplay);
		//format
		doc.addField("grouping_category", groupingCategory);
		doc.addField("format", formats);
		doc.addField("format_category", formatCategories);
		doc.addField("format_boost", formatBoost);

		//language related fields
		doc.addField("language", languages);
		doc.addField("language_boost", languageBoost);
		doc.addField("language_boost_es", languageBoostSpanish);
		//Publication related fields
		doc.addField("publisher", publishers);
		doc.addField("publishDate", publicationDates);
		//Sorting will use the earliest date published
		doc.addField("publishDateSort", earliestPublicationDate);

		//faceting and refined searching
		doc.addField("physical", physicals);
		doc.addField("edition", editions);
		doc.addField("dateSpan", dateSpans);
		doc.addField("series", series);
		doc.addField("series2", series2);
		doc.addField("topic", topics);
		doc.addField("topic_facet", topicFacets);
		doc.addField("genre", genres);
		doc.addField("genre_facet", genreFacets);
		doc.addField("geographic", geographic);
		doc.addField("geographic_facet", geographicFacets);
		doc.addField("era", eras);
		checkDefaultValue(literaryFormFull, "Not Coded");
		checkInconsistentLiteraryFormsFull();
		doc.addField("literary_form_full", literaryFormFull.keySet());
		checkDefaultValue(literaryForm, "Not Coded");
		checkInconsistentLiteraryForms();
		doc.addField("literary_form", literaryForm.keySet());
		checkDefaultValue(targetAudienceFull, "Unknown");
		doc.addField("target_audience_full", targetAudienceFull);
		checkDefaultValue(targetAudience, "Unknown");
		doc.addField("target_audience", targetAudience);
		//Date added to catalog
		doc.addField("date_added", dateAdded);
		if (dateAdded == null){
			//Determine date added based on publication date
			if (earliestPublicationDate != null){
				//Return number of days since the given year
				Calendar publicationDate = GregorianCalendar.getInstance();
				publicationDate.set(earliestPublicationDate.intValue(), Calendar.DECEMBER, 31);

				long indexTime = Util.getIndexDate().getTime();
				long publicationTime = publicationDate.getTime().getTime();
				long bibDaysSinceAdded = (indexTime - publicationTime) / (long)(1000 * 60 * 60 * 24);
				doc.addField("days_since_added", Long.toString(bibDaysSinceAdded));
				doc.addField("time_since_added", Util.getTimeSinceAddedForDate(publicationDate.getTime()));
			}else{
				doc.addField("days_since_added", Long.toString(Integer.MAX_VALUE));
			}
		}else{
			doc.addField("days_since_added", Util.getDaysSinceAddedForDate(dateAdded));
			doc.addField("time_since_added", Util.getTimeSinceAddedForDate(dateAdded));
		}
		doc.addField("itype", iTypes);

		doc.addField("barcode", barcodes);
		//Awards and ratings
		doc.addField("mpaa_rating", mpaaRatings);
		doc.addField("awards_facet", awards);
		if (lexileScore.length() == 0){
			doc.addField("lexile_score", -1);
		}else{
			doc.addField("lexile_score", lexileScore);
		}
		doc.addField("lexile_code", lexileCode);
		doc.addField("accelerated_reader_interest_level", acceleratedReaderInterestLevel);
		doc.addField("accelerated_reader_reading_level", acceleratedReaderReadingLevel);
		doc.addField("accelerated_reader_point_value", acceleratedReaderPointValue);
		//EContent fields
		doc.addField("econtent_device", econtentDevices);
		doc.addField("econtent_source", econtentSources);
		doc.addField("econtent_protection_type", econtentProtectionTypes);

		doc.addField("table_of_contents", contents);
		//broad search terms
		// TODO: determine if we still need all fields?
		doc.addField("all_fields", allFields);
		//TODO: change keywords to be more like old version?
		doc.addField("keywords", Util.getCRSeparatedStringFromSet(keywords));
		//identifiers
		doc.addField("lccn", lccns);
		doc.addField("oclc", oclcs);
		doc.addField("isbn", isbns);
		doc.addField("issn", issns);
		doc.addField("upc", upcs);
		//call numbers
		doc.addField("callnumber-a", callNumberA);
		doc.addField("callnumber-first", callNumberFirst);
		doc.addField("callnumber-subject", callNumberSubject);
		doc.addField("local_callnumber", localCallNumber);
		//relevance determiners
		doc.addField("popularity", Long.toString((long)popularity));
		doc.addField("num_holdings", numHoldings);
		//vufind enrichment
		doc.addField("rating", rating);
		doc.addField("rating_facet", getRatingFacet(rating));
		doc.addField("description", Util.getCRSeparatedString(description));
		doc.addField("display_description", displayDescription);

		//Save information from scopes
		for (ScopedWorkDetails scopedWorkDetail : scopedWorkDetails.values()){
			if (scopedWorkDetail.getRelatedRecords().size() > 0) {
				String scopeName = scopedWorkDetail.getScope().getScopeName();
				doc.addField("related_record_ids_" + scopeName, scopedWorkDetail.getRelatedRecords());
				doc.addField("related_items_" + scopeName, scopedWorkDetail.getRelatedItems());
				doc.addField("format_" + scopeName, scopedWorkDetail.getFormats());
				doc.addField("format_category_" +scopeName, scopedWorkDetail.getFormatCategories());
			}
		}

		//Save information from localized works
		for (LocalizedWorkDetails localizationInfo : localizedWorkDetails.values()){
			doc.addField("detailed_location_" + localizationInfo.getLocalizationInfo().getLocalName(), localizationInfo.getDetailedLocations());
		}
		//availability
		for (String subdomain: availabilityToggleByLibrarySystem.keySet()){
			HashSet<String> availabilityToggle = availabilityToggleByLibrarySystem.get(subdomain);
			doc.addField("availability_toggle_" + subdomain, availabilityToggle);
			if (availabilityToggle.size() == 2){
				doc.addField("lib_boost_" + subdomain, availableAtBoostValue);
			}else{
				doc.addField("lib_boost_" + subdomain, ownedByBoostValue);
			}
		}
		for (String subdomain: localTimeSinceAdded.keySet()){
			doc.addField("local_time_since_added_" + subdomain, Util.getTimeSinceAddedForDate(localTimeSinceAdded.get(subdomain)));
		}
		for (String subdomain: localITypes.keySet()){
			doc.addField("itype_" + subdomain, localITypes.get(subdomain));
		}
		for (String subdomain : localEContentSources.keySet()){
			doc.addField("econtent_source_" + subdomain, localEContentSources.get(subdomain));
		}
		for (String subdomain : localEContentProtectionTypes.keySet()){
			doc.addField("econtent_protection_type_" + subdomain, localEContentProtectionTypes.get(subdomain));
		}
		for (String identifier: localCallNumbers.keySet()){
			doc.addField("local_callnumber_" + identifier, localCallNumbers.get(identifier));
		}
		for (String identifier: sortableCallNumbers.keySet()){
			doc.addField("callnumber_sort_" + identifier, sortableCallNumbers.get(identifier));
		}
		//in library boosts
		for (String identifier: localBoost.keySet()){
			doc.addField("lib_boost_" + identifier, localBoost.get(identifier));
		}

		return doc;
	}

	private void checkInconsistentLiteraryForms() {
		if (literaryForm.size() == 1){
			//Yay, just one literary form
			return;
		}else{
			if (literaryForm.containsKey("Unknown")){
				//We got unknown and something else, remove the unknown
				literaryForm.remove("Unknown");
			}
			if (literaryForm.size() >= 2){
				//Hmm, we got both fiction and non-fiction
				Integer numFictionIndicators = literaryForm.get("Fiction");
				Integer numNonFictionIndicators = literaryForm.get("Non Fiction");
				if (numFictionIndicators.equals(numNonFictionIndicators)){
					//Houston we have a problem.
					//logger.warn("Found inconsistent literary forms for grouped work " + id + " both fiction and non fiction had the same amount of usage.  Defaulting to neither.");
					literaryForm.clear();
					literaryForm.put("Unknown", 1);
					groupedWorkIndexer.addWorkWithInvalidLiteraryForms(id);
				}else if (numFictionIndicators.compareTo(numNonFictionIndicators) > 0){
					logger.debug("Popularity dictates that Fiction is the correct literary form for grouped work " + id);
					literaryForm.remove("Non Fiction");
				}else if (numFictionIndicators.compareTo(numNonFictionIndicators) > 0){
					logger.debug("Popularity dictates that Non Fiction is the correct literary form for grouped work " + id);
					literaryForm.remove("Fiction");
				}
			}
		}
	}

	private void checkInconsistentLiteraryFormsFull() {
		if (literaryFormFull.size() == 1){
			//Yay, just one literary form
			return;
		}else{
			if (literaryFormFull.containsKey("Unknown")){
				//We got unknown and something else, remove the unknown
				literaryFormFull.remove("Unknown");
			}
			if (literaryFormFull.size() >= 2){
				//Hmm, we got multiple forms.  Check to see if there are inconsistent forms
				// i.e. Fiction and Non-Fiction are incompatible, but Novels and Fiction could be mixed
				int maxUsage = 0;
				HashSet<String> highestUsageLiteraryForms = new HashSet<String>();
				for (String literaryForm : literaryFormFull.keySet()){
					int curUsage = literaryFormFull.get(literaryForm);
					if (curUsage > maxUsage){
						highestUsageLiteraryForms.clear();
						highestUsageLiteraryForms.add(literaryForm);
						maxUsage = curUsage;
					}else if (curUsage == maxUsage){
						highestUsageLiteraryForms.add(literaryForm);
					}
				}
				if (highestUsageLiteraryForms.size() > 1){
					//Check to see if the highest usage literary forms are inconsistent
					if (hasInconsistentLiteraryForms(highestUsageLiteraryForms)){
						//Ugh, we have inconsistent literary forms and can't make an educated guess as to which is correct.
						literaryFormFull.clear();
						literaryFormFull.put("Unknown", 1);
						groupedWorkIndexer.addWorkWithInvalidLiteraryForms(id);
					}
				}else{
					removeInconsistentFullLiteraryForms(literaryFormFull, highestUsageLiteraryForms);
				}
			}
		}
	}

	private void removeInconsistentFullLiteraryForms(HashMap<String, Integer> literaryFormFull, HashSet<String> highestUsageLiteraryForms) {
		boolean firstLiteraryFormIsNonFiction = nonFictionFullLiteraryForms.contains(highestUsageLiteraryForms.iterator().next());
		boolean changeMade = true;
		while (changeMade){
			changeMade = false;
			for (String curLiteraryForm : literaryFormFull.keySet()){
				if (firstLiteraryFormIsNonFiction != nonFictionFullLiteraryForms.contains(curLiteraryForm)){
					logger.debug(curLiteraryForm + " got voted off the island for grouped work " + id + " because it was inconsistent with other full literary forms.");
					literaryFormFull.remove(curLiteraryForm);
					changeMade = true;
					break;
				}
			}
		}
	}

	static ArrayList<String> nonFictionFullLiteraryForms = new ArrayList<String>();
	static{
		nonFictionFullLiteraryForms.add("Non Fiction");
		nonFictionFullLiteraryForms.add("Essays");
		nonFictionFullLiteraryForms.add("Letters");
		nonFictionFullLiteraryForms.add("Speeches");
	}
	private boolean hasInconsistentLiteraryForms(HashSet<String> highestUsageLiteraryForms) {
		boolean firstLiteraryFormIsNonFiction = false;
		int numFormsChecked = 0;
		for (String curLiteraryForm : highestUsageLiteraryForms){
			if (numFormsChecked == 0){
				firstLiteraryFormIsNonFiction = nonFictionFullLiteraryForms.contains(curLiteraryForm);
			}else{
				if (firstLiteraryFormIsNonFiction != nonFictionFullLiteraryForms.contains(curLiteraryForm)){
					return true;
				}
			}
			numFormsChecked++;
		}
		return false;
	}

	private void checkDefaultValue(HashSet<String> valuesCollection, String defaultValue) {
		//Remove the default value if we get something more specific
		if (valuesCollection.contains(defaultValue) && valuesCollection.size() > 1){
			valuesCollection.remove(defaultValue);
		}else if (valuesCollection.size() == 0){
			valuesCollection.add(defaultValue);
		}
	}

	private void checkDefaultValue(HashMap<String, Integer> valuesCollection, String defaultValue) {
		//Remove the default value if we get something more specific
		if (valuesCollection.containsKey(defaultValue) && valuesCollection.size() > 1){
			valuesCollection.remove(defaultValue);
		}else if (valuesCollection.size() == 0){
			valuesCollection.put(defaultValue, 1);
		}
	}

	public String getId() {
		return id;
	}

	public void setId(String id) {
		this.id = id;
	}

	public void setTitle(String title) {
		if (title != null){
			//TODO: determine if the title should be changed or always use the first one?
			this.title = title.replace("&", "and");
			keywords.add(title);
		}
	}

	public void setDisplayTitle(String newTitle){
		if (newTitle == null){
			return;
		}
		newTitle = Util.trimTrailingPunctuation(newTitle.replace("&", "and"));
		//Strip out anything in brackets
		newTitle.replaceAll("\\[.*?\\]", "");
		if (this.displayTitle == null || newTitle.length() > this.displayTitle.length()){
			this.displayTitle = newTitle;
		}
	}

	public void setSubTitle(String subTitle) {
		if (subTitle != null){
			//TODO: determine if the subtitle should be changed?
			this.subTitle = subTitle;
			keywords.add(subTitle);
		}
	}
	public void setSortableTitle(String sortableTitle) {
		if (sortableTitle != null){
			this.titleSort = sortableTitle;
		}
	}

	public void addFullTitles(Set<String> fullTitles){
		this.fullTitles.addAll(fullTitles);
	}

	public void addFullTitle(String title) {
		this.fullTitles.add(title);
	}

	public void addAlternateTitles(Set<String> altTitles){
		this.titleAlt.addAll(altTitles);
	}

	public void addOldTitles(Set<String> oldTitles){
		this.titleOld.addAll(oldTitles);
	}

	public void addNewTitles(Set<String> newTitles){
		this.titleNew.addAll(newTitles);
	}

	public void setAuthor(String author) {
		this.author = author;
		keywords.add(author);
	}

	public void setAuthorDisplay(String newAuthor){
		this.authorDisplay = Util.trimTrailingPunctuation(newAuthor);
	}

	public void setAuthAuthor(String author) {
		this.authAuthor = author;
		keywords.add(author);
	}

	public void addRelatedRecord(String recordIdentifier, String format, String edition, String language, String publisher, String publicationDate, String physicalDescription) {
		relatedRecordIds.add(recordIdentifier
				+ "|" + (format == null ? "" : Util.trimTrailingPunctuation(format.replace('|', ' ')))
				+ "|" + (edition == null ? "" : Util.trimTrailingPunctuation(edition.replace('|', ' ')))
				+ "|" + (language == null ? "" : Util.trimTrailingPunctuation(language.replace('|', ' ')))
				+ "|" + (publisher == null ? "" : Util.trimTrailingPunctuation(publisher.replace('|', ' ')))
				+ "|" + (publicationDate == null ? "" : Util.trimTrailingPunctuation(publicationDate.replace('|', ' ')))
				+ "|" + (physicalDescription == null ? "" : Util.trimTrailingPunctuation(physicalDescription.replace('|', ' ')))
		);
	}

	public void addLccn(String lccn) {
		lccns.add(lccn);
	}
	public void addOclc(String oclc) {
		oclcs.add(oclc);
	}
	public void addIsbn(String isbn) {
		isbns.add(isbn);
	}
	public HashSet<String> getIsbns() {
		return isbns;
	}
	public void addIssn(String issn) {
		issns.add(issn);
	}
	public void addUpc(String upc) {
		upcs.add(upc);
	}

	public void addRelatedRecords(HashSet<String> relatedRecordIds) {
		this.relatedRecordIds.addAll(relatedRecordIds);
	}

	public void addAlternateIds(HashSet<String> alternateIds) {
		this.alternateIds.addAll(alternateIds);
	}
	public void addAlternateId(String alternateId) {
		this.alternateIds.add(alternateId);
	}

	public void addOwningLibraries(HashSet<String> owningLibraries) {
		this.owningLibraries.addAll(owningLibraries);
	}

	public void addOwningLocations(HashSet<String> owningLocations) {
		this.owningLocations.addAll(owningLocations);
	}

	public void setGroupingCategory(String groupingCategory) {
		this.groupingCategory = groupingCategory;
	}

	public void addCollectionGroup(String collection_group) {
		collectionGroup.add(collection_group);
	}

	public void addAdditionalCollection(String collectionName, String collection) {
		if (!additionalCollections.containsKey(collectionName)){
			additionalCollections.put(collectionName, new HashSet<String>());
		}
		additionalCollections.get(collectionName).add(collection);
	}

	public void addDetailedLocation(String location){
		detailedLocation.add(location);
	}


	public void addAvailableLocations(HashSet<String> availableLocations, HashSet<String> availableLocationCodes){
		availableAt.addAll(availableLocations);
		//By doing it when we add locations, we can simplify the code that determines base availability
		HashSet<String> availableToggle = new HashSet<String>();
		availableToggle.add("Entire Collection");
		availableToggle.add("Available Now");
		for (String curLocationCode : availableLocationCodes){
			availabilityToggleByLibrarySystem.put(curLocationCode, availableToggle);
		}
	}

	public void addOwningLocationCodesAndSubdomains(HashSet<String> owningLocationCodes){
		HashSet<String> availabilityToggle = new HashSet<String>();
		availabilityToggle.add("Entire Collection");
		for (String curLocationCode : owningLocationCodes){
			if (!availabilityToggleByLibrarySystem.containsKey(curLocationCode)){
				availabilityToggleByLibrarySystem.put(curLocationCode, availabilityToggle);
			}
		}
	}

	public void addCompatiblePTypes(HashSet<String> compatiblePTypes){
		if (compatiblePTypes != null) {
			usableBy.addAll(compatiblePTypes);
		}else{
			logger.warn("compatiblePTypes was null in addCompatiblePTypes");
		}
	}

	public void addCompatiblePType(String pType){
		usableBy.add(pType);
	}

	public void setAuthorLetter(String authorLetter) {
		this.authorLetter = authorLetter;
	}

	public void addAuthAuthor2(Set<String> fieldList) {
		this.authAuthor2.addAll(fieldList);
	}

	public void addAuthor2(Set<String> fieldList) {
		this.author2.addAll(fieldList);
	}

	public void addAuthor2Role(Set<String> fieldList) {
		this.author2Role.addAll(fieldList);
	}

	public void addAuthorAdditional(Set<String> fieldList) {
		this.authorAdditional.addAll(fieldList);
	}

	public void addFormats(Set<String> formats) {
		this.formats.addAll(formats);
	}

	public void addFormat(String format) {
		this.formats.add(format);
	}

	public void addFormatCategories(HashSet<String> formatCategories) {
		this.formatCategories.addAll(formatCategories);
	}

	public void addFormatCategory(String formatCategory){
		this.formatCategories.add(formatCategory);
	}

	public void setFormatBoost(Long formatBoost) {
		if (formatBoost > this.formatBoost){
			this.formatBoost = formatBoost;
		}
	}

	public void addHoldings(int recordHoldings) {
		this.numHoldings += recordHoldings;
	}

	public void addPopularity(float itemPopularity) {
		this.popularity += itemPopularity;
	}

	public void addTopic(Set<String> fieldList) {
		this.topics.addAll(Util.trimTrailingPunctuation(fieldList));
	}

	public void addTopicFacet(Set<String> fieldList) {
		this.topicFacets.addAll(Util.trimTrailingPunctuation(fieldList));
	}

	public void addSeries(Set<String> fieldList) {
		for(String curField : fieldList){
			if (!curField.equalsIgnoreCase("none")){
				this.series.add(Util.trimTrailingPunctuation(curField));
			}
		}
	}

	public void addSeries(String series) {
		if (series != null && !series.equalsIgnoreCase("none")){
			this.series.add(Util.trimTrailingPunctuation(series));
		}
	}

	public void addSeries2(Set<String> fieldList) {
		this.series2.addAll(fieldList);
	}

	public void addPhysical(Set<String> fieldList) {
		this.physicals.addAll(fieldList);
	}

	public void addDateSpan(Set<String> fieldList) {
		this.dateSpans.addAll(fieldList);
	}

	public void addEditions(Set<String> fieldList) {
		this.editions.addAll(fieldList);
	}

	public void addContents(Set<String> fieldList) {
		this.contents.addAll(fieldList);
	}

	public void addGenre(Set<String> fieldList) {
		this.genres.addAll(Util.trimTrailingPunctuation(fieldList));
	}

	public void addGenreFacet(Set<String> fieldList) {
		this.genreFacets.addAll(Util.trimTrailingPunctuation(fieldList));
	}

	public void addGeographic(Set<String> fieldList) {
		this.geographic.addAll(Util.trimTrailingPunctuation(fieldList));
	}

	public void addGeographicFacet(Set<String> fieldList) {
		this.geographicFacets.addAll(Util.trimTrailingPunctuation(fieldList));
	}

	public void addEra(Set<String> fieldList) {
		this.eras.addAll(fieldList);
	}

	public void setLanguageBoost(Long languageBoost) {
		if (languageBoost > this.languageBoost){
			this.languageBoost = languageBoost;
		}
	}

	public void setLanguageBoostSpanish(Long languageBoostSpanish) {
		if (languageBoostSpanish > this.languageBoostSpanish){
			this.languageBoostSpanish = languageBoostSpanish;
		}
	}

	public void setLanguages(HashSet<String> languages) {
		this.languages.addAll(languages);
	}

	public void addPublishers(Set<String> publishers) {
		this.publishers.addAll(publishers);
	}

	public void addPublisher(String publisher){
		this.publishers.add(publisher);
	}

	public void addPublicationDates(Set<String> publicationDate) {
		for (String pubDate : publicationDate){
			addPublicationDate(pubDate);
		}
	}

	public void addPublicationDate(String publicationDate){
		String cleanDate = GroupedWorkIndexer.cleanDate(publicationDate);
		if (cleanDate != null){
			this.publicationDates.add(cleanDate);
			//Convert the date to a long and see if it is before the current date
			Long pubDateLong = Long.parseLong(cleanDate);
			if (earliestPublicationDate == null || pubDateLong < earliestPublicationDate){
				earliestPublicationDate = pubDateLong;
			}
		}
	}

	public void addLiteraryForms(HashSet<String> literaryForms) {
		for (String curLiteraryForm : literaryForms){
			this.addLiteraryForm(curLiteraryForm);
		}
	}

	public void addLiteraryForms(HashMap<String, Integer> literaryForms) {
		for (String curLiteraryForm : literaryForms.keySet()){
			this.addLiteraryForm(curLiteraryForm, literaryForms.get(curLiteraryForm));
		}
	}

	public void addLiteraryForm(String literaryForm, int count) {
		literaryForm = literaryForm.trim();
		if (this.literaryForm.containsKey(literaryForm)){
			Integer numMatches = this.literaryForm.get(literaryForm);
			this.literaryForm.put(literaryForm, numMatches + count);
		}else{
			this.literaryForm.put(literaryForm, count);
		}
	}

	public void addLiteraryForm(String literaryForm) {
		addLiteraryForm(literaryForm, 1);
	}

	public void addLiteraryFormsFull(HashMap<String, Integer> literaryFormsFull) {
		for (String curLiteraryForm : literaryFormsFull.keySet()){
			this.addLiteraryFormFull(curLiteraryForm, literaryFormsFull.get(curLiteraryForm));
		}
	}

	public void addLiteraryFormsFull(HashSet<String> literaryFormsFull) {
		for (String curLiteraryForm : literaryFormsFull){
			this.addLiteraryFormFull(curLiteraryForm);
		}
	}

	public void addLiteraryFormFull(String literaryForm, int count) {
		literaryForm = literaryForm.trim();
		if (this.literaryFormFull.containsKey(literaryForm)){
			Integer numMatches = this.literaryFormFull.get(literaryForm);
			this.literaryFormFull.put(literaryForm, numMatches + count);
		}else{
			this.literaryFormFull.put(literaryForm, count);
		}
	}

	public void addLiteraryFormFull(String literaryForm) {
		this.addLiteraryFormFull(literaryForm, 1);
	}

	public void addTargetAudiences(HashSet<String> target_audience) {
		targetAudience.addAll(target_audience);
	}

	public void addTargetAudience(String target_audience) {
		targetAudience.add(target_audience);
	}

	public void addTargetAudiencesFull(HashSet<String> target_audience_full) {
		targetAudienceFull.addAll(target_audience_full);
	}

	public void addTargetAudienceFull(String target_audience) {
		targetAudienceFull.add(target_audience);
	}

	public void setDateAdded(Date date, ArrayList<String> relatedLocations){
		if (dateAdded == null || date.before(dateAdded)){
			dateAdded = date;
		}
		for (String relatedLocation : relatedLocations){
			if (!localTimeSinceAdded.containsKey(relatedLocation)){
				localTimeSinceAdded.put(relatedLocation, date);
			}else if (date.before(localTimeSinceAdded.get(relatedLocation))){
				localTimeSinceAdded.put(relatedLocation, date);
			}
		}
	}

	private Set<String> getRatingFacet(Float rating) {
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

	public void setIType(String iType, ArrayList<String> relatedSubdomains) {
		this.iTypes.add(iType);
		for (String subdomain : relatedSubdomains){
			if (!localITypes.containsKey(subdomain)){
				localITypes.put(subdomain, new HashSet<String>());
			}
			localITypes.get(subdomain).add(iType);
		}
	}

	public void addMpaaRating(GroupedWorkSolr groupedWork, String mpaaRating) {
		this.mpaaRatings.add(mpaaRating);
	}

	public void addBarcodes(Set<String> barcodeList) {
		this.barcodes.addAll(barcodeList);
	}

	public void setRating(float rating) {
		this.rating = rating;
	}

	public void addEContentSources(HashSet<String> eContentSources, Collection<String> relatedSubdomains, Collection<String> relatedLocations){
		econtentSources.addAll(eContentSources);
		keywords.addAll(eContentSources);
		for (String subdomain : relatedSubdomains){
			HashSet<String> valuesForIdentifier = localEContentSources.get(subdomain);
			if (valuesForIdentifier == null){
				valuesForIdentifier = new HashSet<String>();
				localEContentSources.put(subdomain, valuesForIdentifier);
			}
			valuesForIdentifier.addAll(eContentSources);
		}
		for (String locationCode : relatedLocations){
			HashSet<String> valuesForIdentifier = localEContentSources.get(locationCode);
			if (valuesForIdentifier == null){
				valuesForIdentifier = new HashSet<String>();
				localEContentSources.put(locationCode, valuesForIdentifier);
			}
			valuesForIdentifier.addAll(eContentSources);
		}
	}
	public void addEContentSource(String eContentSource, Collection<String> relatedSubdomains, Collection<String> relatedLocations){
		econtentSources.add(eContentSource);
		keywords.add(eContentSource);
		for (String subdomain : relatedSubdomains){
			HashSet<String> valuesForIdentifier = localEContentSources.get(subdomain);
			if (valuesForIdentifier == null){
				valuesForIdentifier = new HashSet<String>();
				localEContentSources.put(subdomain, valuesForIdentifier);
			}
			valuesForIdentifier.add(eContentSource);
		}
		for (String locationCode : relatedLocations){
			HashSet<String> valuesForIdentifier = localEContentSources.get(locationCode);
			if (valuesForIdentifier == null){
				valuesForIdentifier = new HashSet<String>();
				localEContentSources.put(locationCode, valuesForIdentifier);
			}
			valuesForIdentifier.add(eContentSource);
		}
	}
	public void addEContentProtectionTypes(HashSet<String> eContentProtectionTypes, Collection<String> relatedSubdomains, Collection<String> relatedLocations){
		econtentProtectionTypes.addAll(eContentProtectionTypes);
		for (String subdomain : relatedSubdomains){
			HashSet<String> valuesForIdentifier = localEContentProtectionTypes.get(subdomain);
			if (valuesForIdentifier == null){
				valuesForIdentifier = new HashSet<String>();
				localEContentProtectionTypes.put(subdomain, valuesForIdentifier);
			}
			valuesForIdentifier.addAll(eContentProtectionTypes);
		}
		for (String locationCode : relatedLocations){
			HashSet<String> valuesForIdentifier = localEContentProtectionTypes.get(locationCode);
			if (valuesForIdentifier == null){
				valuesForIdentifier = new HashSet<String>();
				localEContentProtectionTypes.put(locationCode, valuesForIdentifier);
			}
			valuesForIdentifier.addAll(eContentProtectionTypes);
		}
	}
	public void addEContentProtectionType(String eContentProtectionType, Collection<String> relatedSubdomains, Collection<String> relatedLocations){
		econtentProtectionTypes.add(eContentProtectionType);
		for (String subdomain : relatedSubdomains){
			HashSet<String> valuesForIdentifier = localEContentProtectionTypes.get(subdomain);
			if (valuesForIdentifier == null){
				valuesForIdentifier = new HashSet<String>();
				localEContentProtectionTypes.put(subdomain, valuesForIdentifier);
			}
			valuesForIdentifier.add(eContentProtectionType);
		}
		for (String locationCode : relatedLocations){
			HashSet<String> valuesForIdentifier = localEContentProtectionTypes.get(locationCode);
			if (valuesForIdentifier == null){
				valuesForIdentifier = new HashSet<String>();
				localEContentProtectionTypes.put(locationCode, valuesForIdentifier);
			}
			valuesForIdentifier.add(eContentProtectionType);
		}
	}

	public void setLexileScore(String lexileScore) {
		this.lexileScore = lexileScore;
	}

	public void setLexileCode(String lexileCode) {
		this.lexileCode = lexileCode;
	}

	public void addAwards(HashSet<String> awards) {
		this.awards.addAll(awards);
	}

	public void setAcceleratedReaderInterestLevel(String acceleratedReaderInterestLevel) {
		if (acceleratedReaderInterestLevel != null){
			this.acceleratedReaderInterestLevel = acceleratedReaderInterestLevel;
		}
	}

	public void setAcceleratedReaderReadingLevel(String acceleratedReaderReadingLevel) {
		if (acceleratedReaderReadingLevel != null){
			this.acceleratedReaderReadingLevel = acceleratedReaderReadingLevel;
		}
	}

	public void setAcceleratedReaderPointValue(String acceleratedReaderPointValue) {
		if (acceleratedReaderPointValue != null){
			this.acceleratedReaderPointValue = acceleratedReaderPointValue;
		}
	}

	public void addAllFields(String fields){
		allFields += " " + fields;
	}

	public void setCallNumberA(String callNumber) {
		if (callNumber != null && callNumberA == null){
			this.callNumberA = callNumber;
		}
	}
	public void setCallNumberFirst(String callNumber) {
		if (callNumber != null && callNumberFirst == null){
			this.callNumberFirst = callNumber;
		}
	}
	public void setCallNumberSubject(String callNumber) {
		if (callNumber != null && callNumberSubject == null){
			this.callNumberSubject = callNumber;
		}
	}

	public void addContents(HashSet<String> contents){
		this.contents.addAll(contents);
	}

	public void addEContentDevices(HashSet<String> devices){
		this.econtentDevices.addAll(devices);
	}

	public void addLocalCallNumber(String fullCallNumber, ArrayList<String> subdomainsForLocation, ArrayList<String> relatedLocationCodesForLocation) {
		if (localCallNumber == null){
			localCallNumber = fullCallNumber;
		}
		for (String subdomain : subdomainsForLocation){
			HashSet<String> curCallNumbers = localCallNumbers.get(subdomain);
			if (curCallNumbers == null){
				curCallNumbers = new HashSet<String>();
				localCallNumbers.put(subdomain, curCallNumbers);
			}
			curCallNumbers.add(fullCallNumber);
		}
		for (String curCode : relatedLocationCodesForLocation){
			HashSet<String> curCallNumbers = localCallNumbers.get(curCode);
			if (curCallNumbers == null){
				curCallNumbers = new HashSet<String>();
				localCallNumbers.put(curCode, curCallNumbers);
			}
			curCallNumbers.add(fullCallNumber);
		}
	}

	public void addCallNumberSort(String sortableCallNumber, ArrayList<String> subdomainsForLocation, ArrayList<String> relatedLocationCodesForLocation) {
		for (String subdomain : subdomainsForLocation){
			if (!sortableCallNumbers.containsKey(subdomain)){
				sortableCallNumbers.put(subdomain, sortableCallNumber);
			}
		}
		for (String curCode : relatedLocationCodesForLocation){
			if (!sortableCallNumbers.containsKey(curCode)){
				sortableCallNumbers.put(curCode, sortableCallNumber);
			}
		}
	}

	public void addKeywords(String keywords){
		this.keywords.add(keywords);
	}

	public void addDescription(String description, String recordFormat){
		if (description == null || description.length() == 0){
			return;
		}
		this.description.add(description);
		if (this.displayDescription.length() == 0){
			this.displayDescription = description;
			this.displayDescriptionFormat = recordFormat;
		}else{
			//Only overwrite if we get a better format
			if (recordFormat.equals("Book") || recordFormat.equals("eBook") ){
				if (description.length() > this.displayDescription.length()){
					this.displayDescription = description;
					this.displayDescriptionFormat = recordFormat;
				}
			} else if (!displayDescriptionFormat.equals("Book") && !displayDescriptionFormat.equals("eBook")){
				if (description.length() > this.displayDescription.length()) {
					this.displayDescription = description;
					this.displayDescriptionFormat = recordFormat;
				}
			}
		}
	}

	public void addRelatedItem(String relatedItemInfo) {
		relatedItems.add(relatedItemInfo);
	}

	public void setRelatedRecords(HashSet<IlsRecord> ilsRecords) {
		for(IlsRecord ilsRecord : ilsRecords){
			addRelatedRecord(ilsRecord.getRecordId(), ilsRecord.getPrimaryFormat(), ilsRecord.getEdition(), ilsRecord.getLanguage(), ilsRecord.getPublisher(), ilsRecord.getPublicationDate(), ilsRecord.getPhysicalDescription());
			//Now update for scopes
			for (Scope relatedScope : ilsRecord.getRelatedScopes()){
				scopedWorkDetails.get(relatedScope.getScopeName()).addRelatedRecord(ilsRecord.getRecordId(), ilsRecord.getPrimaryFormat(), ilsRecord.getEdition(), ilsRecord.getLanguage(), ilsRecord.getPublisher(), ilsRecord.getPublicationDate(), ilsRecord.getPhysicalDescription());
			}
		}
	}

	public void setFormatInformation(HashSet<IlsRecord> ilsRecords) {
		for(IlsRecord ilsRecord : ilsRecords){
			addFormats(ilsRecord.getFormats());
			addFormatCategories(ilsRecord.getFormatCategories());
			setFormatBoost(ilsRecord.getFormatBoost());
			//Now update for scopes
			for (Scope relatedScope : ilsRecord.getRelatedScopes()){
				ScopedWorkDetails workDetails = scopedWorkDetails.get(relatedScope.getScopeName());
				workDetails.addFormat(ilsRecord.getFormats());
				workDetails.addFormatCategories(ilsRecord.getFormatCategories());
				workDetails.setFormatBoost(ilsRecord.getFormatBoost());
			}
		}
	}
}
