package org.vufind;

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
	private HashSet<String> alternateIds = new HashSet<String>();
	private HashSet<String> recordSources = new HashSet<String>();

	private HashSet<String> owningLibraries = new HashSet<String>();
	private HashSet<String> owningLocations = new HashSet<String>();
	private HashSet<String> collectionGroup = new HashSet<String>();

	private HashSet<String> collectionAdams = new HashSet<String>();
	private HashSet<String> collectionMsc = new HashSet<String>();
	private HashSet<String> collectionWestern = new HashSet<String>();

	private HashMap<String, HashSet<String>> collectionByLibrarySystem = new HashMap<String, HashSet<String>>();
	private HashSet<String> detailedLocation = new HashSet<String>();
	private HashMap<String, HashSet<String>> detailedLocationByLibrarySystem = new HashMap<String, HashSet<String>>();

	private HashSet<String> availableAt = new HashSet<String>();
	private HashMap<String, HashSet<String>> availabilityToggleByLibrarySystem = new HashMap<String, HashSet<String>>();

	private HashSet<String> usableBy = new HashSet<String>();

	private String title;
	private String displayTitle;
	private String subTitle;
	private HashSet<String> fullTitles = new HashSet<String>();
	private String titleSort;
	private HashSet<String> titleAlt = new HashSet<String>();
	private HashSet<String> titleOld = new HashSet<String>();
	private HashSet<String> titleNew = new HashSet<String>();

	private String author;
	private String authorDisplay;
	private String authAuthor;
	private String authorLetter;
	private HashSet<String> author2 = new HashSet<String>();
	private HashSet<String> authAuthor2 = new HashSet<String>();
	private HashSet<String> author2Role = new HashSet<String>();
	private HashSet<String> authorAdditional = new HashSet<String>();

	private String groupingCategory;
	private HashSet<String> formats = new HashSet<String>();
	private HashSet<String> formatCategories = new HashSet<String>();
	private Long formatBoost = 1L;

	private HashSet<String> languages = new HashSet<String>();
	private Long languageBoost = 1L;
	private Long languageBoostSpanish = 1L;

	private HashSet<String> publishers = new HashSet<String>();
	private HashSet<String> publicationDates = new HashSet<String>();
	private Long earliestPublicationDate = null;

	private HashSet<String> topics = new HashSet<String>();
	private HashSet<String> topicFacets = new HashSet<String>();
	private HashSet<String> series = new HashSet<String>();
	private HashSet<String> series2 = new HashSet<String>();
	private HashSet<String> physicals = new HashSet<String>();
	private HashSet<String> dateSpans = new HashSet<String>();
	private HashSet<String> editions = new HashSet<String>();
	private HashSet<String> contents = new HashSet<String>();
	private HashSet<String> genres = new HashSet<String>();
	private HashSet<String> genreFacets = new HashSet<String>();
	private HashSet<String> geographic = new HashSet<String>();
	private HashSet<String> geographicFacets = new HashSet<String>();
	private HashSet<String> eras = new HashSet<String>();

	private HashSet<String> literaryFormFull = new HashSet<String>();
	private HashSet<String> literaryForm = new HashSet<String>();
	private HashSet<String> targetAudienceFull = new HashSet<String>();
	private HashSet<String> targetAudience = new HashSet<String>();

	private Date dateAdded = null;
	private HashMap<String, Date> localTimeSinceAdded = new HashMap<String, Date>();

	private HashSet<String> iTypes = new HashSet<String>();
	private HashMap<String, HashSet<String>> localITypes = new HashMap<String, HashSet<String>>();
	private HashSet<String> mpaaRatings = new HashSet<String>();
	private HashSet<String> barcodes = new HashSet<String>();

	//Awards and ratings
	private HashSet<String> awards = new HashSet<String>();
	private HashSet<String> lexileScore = new HashSet<String>();
	private HashSet<String> lexileCode = new HashSet<String>();
	private String acceleratedReaderInterestLevel;
	private String acceleratedReaderReadingLevel;
	private Float acceleratedReaderPointValue;

	private float rating = 2.5f;

	private HashSet<String> keywords = new HashSet<String>();

	private HashSet<String> lccns = new HashSet<String>();
	private HashSet<String> oclcs = new HashSet<String>();
	private HashSet<String> isbns = new HashSet<String>();
	private HashSet<String> issns = new HashSet<String>();
	private HashSet<String> upcs = new HashSet<String>();

	private Long numHoldings = 0L;
	private float popularity;
	private HashSet<String> econtentDevices = new HashSet<String>();
	private HashSet<String> econtentSources = new HashSet<String>();
	private HashMap<String, HashSet<String>> localEContentSources = new HashMap<String, HashSet<String>>();
	private HashSet<String> econtentProtectionType = new HashSet<String>();
	private HashMap<String, HashSet<String>> localEContentProtectionTypes = new HashMap<String, HashSet<String>>();

	public SolrInputDocument getSolrDocument() {
		SolrInputDocument doc = new SolrInputDocument();
		//Main identification
		doc.addField("id", id);
		doc.addField("alternate_ids", alternateIds);
		doc.addField("recordtype", "grouped_work");
		//Related records and sources
		doc.addField("related_record_ids", relatedRecordIds);
		doc.addField("record_source", recordSources);
		//Ownership and location
		doc.addField("owning_library", owningLibraries);
		doc.addField("owning_location", owningLocations);
		doc.addField("collection_group", collectionGroup);
		doc.addField("collection_adams", collectionAdams);
		doc.addField("collection_msc", collectionMsc);
		doc.addField("collection_western", collectionWestern);
		//detailed locations
		doc.addField("detailed_location", detailedLocation);
		for (String subdomain: detailedLocationByLibrarySystem.keySet()){
			doc.addField("detailed_location_" + subdomain, detailedLocationByLibrarySystem.get(subdomain));
		}
		//availability
		doc.addField("available_at", availableAt);
		for (String subdomain: availabilityToggleByLibrarySystem.keySet()){
			doc.addField("availability_toggle_" + subdomain, detailedLocationByLibrarySystem.get(subdomain));
		}

		//Determine who can use the record
		doc.addField("usable_by", usableBy);

		//Title and variations
		doc.addField("title", title + " " + subTitle);
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
		doc.addField("literary_form_full", literaryFormFull);
		checkDefaultValue(literaryForm, "Not Coded");
		doc.addField("literary_form", literaryForm);
		checkDefaultValue(targetAudienceFull, "Unknown");
		doc.addField("target_audience_full", targetAudienceFull);
		checkDefaultValue(targetAudience, "Unknown");
		doc.addField("target_audience", targetAudience);
		//Date added to catalog
		doc.addField("date_added", dateAdded);
		doc.addField("time_since_added", getTimeSinceAddedForDate(dateAdded));
		for (String subdomain: localTimeSinceAdded.keySet()){
			doc.addField("local_time_since_added_" + subdomain, getTimeSinceAddedForDate(localTimeSinceAdded.get(subdomain)));
		}
		doc.addField("itype", iTypes);
		for (String subdomain: localITypes.keySet()){
			doc.addField("itype_" + subdomain, localITypes.get(subdomain));
		}
		doc.addField("barcode", barcodes);
		//Awards and ratings
		doc.addField("mpaa_rating", mpaaRatings);
		doc.addField("awards_facet", awards);
		doc.addField("lexile_score", lexileScore);
		doc.addField("lexile_code", lexileCode);
		doc.addField("accelerated_reader_interest_level", acceleratedReaderInterestLevel);
		doc.addField("accelerated_reader_reading_level", acceleratedReaderReadingLevel);
		doc.addField("accelerated_reader_point_value", acceleratedReaderPointValue);
		//EContent fields
		doc.addField("econtent_device", econtentDevices);
		doc.addField("econtent_source", econtentSources);
		for (String subdomain: localEContentSources.keySet()){
			doc.addField("econtent_source_" + subdomain, localEContentSources.get(subdomain));
		}
		doc.addField("econtent_protection_type", econtentProtectionType);
		for (String subdomain: localEContentProtectionTypes.keySet()){
			doc.addField("econtent_protection_type_" + subdomain, localEContentProtectionTypes.get(subdomain));
		}

		doc.addField("table_of_contents", contents);
		//broad search terms
		//TODO: allfields
		//TODO: keywords
		doc.addField("keywords", Util.getCRSeparatedStringFromSet(keywords));
		//identifiers
		doc.addField("lccn", lccns);
		doc.addField("oclc", oclcs);
		doc.addField("isbn", isbns);
		doc.addField("issn", issns);
		doc.addField("upc", upcs);
		//call numbers
		//in library boosts
		//relevance determiners
		doc.addField("popularity", Long.toString((long)popularity));
		doc.addField("num_holdings", numHoldings);
		//vufind enrichment
		//TODO: load rating for the grouped record from the database
		doc.addField("rating", rating);
		doc.addField("rating_facet", getRatingFacet(rating));

		return doc;
	}

	private void checkDefaultValue(HashSet<String> valuesCollection, String defaultValue) {
		//Remove the default value if we get something more specific
		if (valuesCollection.contains(defaultValue) && valuesCollection.size() > 1){
			valuesCollection.remove(defaultValue);
		}else if (valuesCollection.size() == 0){
			valuesCollection.add(defaultValue);
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
			//TODO: determine if the title should be changed?
			this.title = title.replace("&", "and");
			keywords.add(title);
		}
	}

	public void setDisplayTitle(String newTitle){
		if (newTitle == null){
			return;
		}
		newTitle = Util.trimTrailingPunctuation(newTitle.replace("&", "and"));
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

	public void addRelatedRecord(String recordIdentifier) {
		relatedRecordIds.add(recordIdentifier);
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

	public void addRecordSources(HashSet<String> recordSources) {
		this.recordSources.addAll(recordSources);
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

	public void addCollectionAdams(String collection) {
		collectionAdams.add(collection);
	}

	public void addCollectionMsc(String collection) {
		collectionMsc.add(collection);
	}

	public void addCollectionWestern(String collection) {
		collectionWestern.add(collection);
	}

	public void addDetailedLocation(String location, ArrayList<String> relatedSubdomains){
		detailedLocation.add(location);
		for (String subdomain: relatedSubdomains){
			HashSet<String> tmpDetailedLocations = detailedLocationByLibrarySystem.get(subdomain);
			if (tmpDetailedLocations == null){
				tmpDetailedLocations = new HashSet<String>();
				detailedLocationByLibrarySystem.put(subdomain, tmpDetailedLocations);
			}
			tmpDetailedLocations.add(location);
		}
	}


	public void addAvailableLocations(HashSet<String> availableLocations){
		availableAt.addAll(availableLocations);
		//TODO: Setup availability toggles by location and system here
		//By doing it when we add locations, we can simplify the code that determines base availability
	}

	public void addCompatiblePTypes(HashSet<String> compatiblePTypes){
		usableBy.addAll(compatiblePTypes);
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
		this.authAuthor2.addAll(fieldList);
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
		this.topics.addAll(fieldList);
	}

	public void addTopicFacet(Set<String> fieldList) {
		this.topicFacets.addAll(fieldList);
	}

	public void addSeries(Set<String> fieldList) {
		this.series.addAll(fieldList);
	}

	public void addSeries(String series) {
		this.series.add(series);
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
		this.genres.addAll(fieldList);
	}

	public void addGenreFacet(Set<String> fieldList) {
		this.genreFacets.addAll(fieldList);
	}

	public void addGeographic(Set<String> fieldList) {
		this.geographic.addAll(fieldList);
	}

	public void addGeographicFacet(Set<String> fieldList) {
		this.geographicFacets.addAll(fieldList);
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
		this.literaryForm.addAll(literaryForms);
	}

	public void addLiteraryForm(String literaryForm) {
		this.literaryForm.add(literaryForm.trim());
	}

	public void addLiteraryFormsFull(HashSet<String> literaryFormsFull) {
		this.literaryFormFull.addAll(literaryFormsFull);
	}

	public void addLiteraryFormFull(String literaryForm) {
		this.literaryFormFull.add(literaryForm.trim());
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

	private static Date indexDate = new Date();
	private LinkedHashSet<String> getTimeSinceAddedForDate(Date curDate) {
		if (curDate == null){
			return null;
		}
		long timeDifferenceDays = (indexDate.getTime() - curDate.getTime())
				/ (1000 * 60 * 60 * 24);
		// System.out.println("Time Difference Days: " + timeDifferenceDays);
		LinkedHashSet<String> result = new LinkedHashSet<String>();
		if (timeDifferenceDays <= 1) {
			result.add("Day");
		}
		if (timeDifferenceDays <= 7) {
			result.add("Week");
		}
		if (timeDifferenceDays <= 30) {
			result.add("Month");
		}
		if (timeDifferenceDays <= 60) {
			result.add("2 Months");
		}
		if (timeDifferenceDays <= 90) {
			result.add("Quarter");
		}
		if (timeDifferenceDays <= 180) {
			result.add("Six Months");
		}
		if (timeDifferenceDays <= 365) {
			result.add("Year");
		}
		return result;
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



}
