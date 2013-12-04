package org.vufind;

import org.apache.solr.common.SolrInputDocument;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.Set;

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
	private String subTitle;
	private HashSet<String> fullTitles = new HashSet<String>();
	private String titleSort;
	private HashSet<String> titleAlt = new HashSet<String>();
	private HashSet<String> titleOld = new HashSet<String>();
	private HashSet<String> titleNew = new HashSet<String>();

	private String author;
	private String authAuthor;
	private String authorLetter;
	private HashSet<String> author2 = new HashSet<String>();
	private HashSet<String> authAuthor2 = new HashSet<String>();
	private HashSet<String> author2Role = new HashSet<String>();
	private HashSet<String> authorAdditional = new HashSet<String>();

	private String groupingCategory;
	private HashSet<String> formats = new HashSet<String>();
	private HashSet<String> formatCategories = new HashSet<String>();
	private Long formatBoost = 0L;

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

	private HashSet<String> keywords = new HashSet<String>();

	private HashSet<String> lccns = new HashSet<String>();
	private HashSet<String> oclcs = new HashSet<String>();
	private HashSet<String> isbns = new HashSet<String>();
	private HashSet<String> issns = new HashSet<String>();
	private HashSet<String> upcs = new HashSet<String>();

	private Long numHoldings = 0L;
	private float popularity;

	public SolrInputDocument getSolrDocument() {
		SolrInputDocument doc = new SolrInputDocument();
		//Main identification
		doc.addField("id", id);
		doc.addField("recordtype", "grouped_record");
		doc.addField("alternate_ids", alternateIds);
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
		//format
		doc.addField("grouping_category", groupingCategory);
		doc.addField("format", formats);
		doc.addField("format_category", formatCategories);
		doc.addField("format_boost", formatBoost);
		//generic fields
		//faceting and refined searching
		doc.addField("topic", topics);
		doc.addField("topic_facet", topicFacets);
		doc.addField("series", series);
		doc.addField("series2", series2);
		doc.addField("physical", physicals);
		doc.addField("dateSpan", dateSpans);
		doc.addField("edition", editions);
		doc.addField("table_of_contents", contents);
		doc.addField("genre", genres);
		doc.addField("genre_facet", genreFacets);
		doc.addField("geographic", geographic);
		doc.addField("geographic_facet", geographicFacets);
		doc.addField("era", eras);
		//broad search terms
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
		return doc;
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
			this.title = title;
			keywords.add(title);
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
}
