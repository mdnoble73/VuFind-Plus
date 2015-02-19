package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.marc.Record;

import java.io.File;
import java.io.FileInputStream;
import java.util.HashSet;
import java.util.Set;

/**
 * Extracts data from Hoopla Marc records to fill out information within the work to be indexed.
 *
 * Pika
 * User: Mark Noble
 * Date: 12/17/2014
 * Time: 10:30 AM
 */
public class HooplaProcessor extends MarcRecordProcessor {
	private String individualMarcPath;
	public HooplaProcessor(GroupedWorkIndexer indexer, Ini configIni, Logger logger) {
		super(indexer, logger);

		individualMarcPath = configIni.get("Hoopla", "individualMarcPath");
	}

	@Override
	public void processRecord(GroupedWorkSolr groupedWork, String identifier) {
		//Load the marc record from disc
		String firstChars = identifier.substring(0, 7);
		String basePath = individualMarcPath + "/" + firstChars;
		String individualFilename = basePath + "/" + identifier + ".mrc";
		File individualFile = new File(individualFilename);
		try {
			FileInputStream inputStream = new FileInputStream(individualFile);
			MarcPermissiveStreamReader marcReader = new MarcPermissiveStreamReader(inputStream, true, true, "UTF-8");
			if (marcReader.hasNext()){
				try{
					Record record = marcReader.next();
					updateGroupedWorkSolrDataBasedOnMarc(groupedWork, record, identifier);
				}catch (Exception e) {
					logger.error("Error updating solr based on hoopla marc record", e);
				}
			}
			inputStream.close();
		} catch (Exception e) {
			logger.error("Error reading data from hoopla file " + individualFile.toString(), e);
		}
	}

	@Override
	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		//Do updates based on the overall bib (shared regardless of scoping)
		updateGroupedWorkSolrDataBasedOnStandardMarcData(groupedWork, record, null);

		//Do special processing for Hoopla which does not have individual items within the record
		//Instead, each record has essentially unlimited items that can be used at one time.
		//There are also not multiple formats within a record that we would need to split out.

		//First get format
		String format = getFirstFieldVal(record, "099a");
		format = format.replace(" hoopla", "");
		String formatCategory = indexer.translateValue("format_category", format);
		String formatBoostStr = indexer.translateValue("format_boost", format);
		Long formatBoost = Long.parseLong(formatBoostStr);

		//Load editions
		Set<String> editions = getFieldList(record, "250a");
		String primaryEdition = null;
		if (editions.size() > 0) {
			primaryEdition = editions.iterator().next();
		}
		groupedWork.addEditions(editions);

		//Load Languages
		Set <String> languages = getFieldList(record, "008[35-37]:041a:041d:041j");
		HashSet<String> translatedLanguages = new HashSet<String>();
		boolean isFirstLanguage = true;
		translatedLanguages = indexer.translateCollection("language", languages);
		String primaryLanguage = null;
		for (String language : languages){
			if (primaryLanguage == null){
				primaryLanguage = indexer.translateValue("language", language);
			}
			String languageBoost = indexer.translateValue("language_boost", language);
			if (languageBoost != null){
				Long languageBoostVal = Long.parseLong(languageBoost);
				groupedWork.setLanguageBoost(languageBoostVal);
			}
			String languageBoostEs = indexer.translateValue("language_boost_es", language);
			if (languageBoostEs != null){
				Long languageBoostVal = Long.parseLong(languageBoost);
				groupedWork.setLanguageBoostSpanish(languageBoostVal);
			}
		}
		groupedWork.setLanguages(translatedLanguages);

		//Load publication details
		//Load publishers
		Set<String> publishers = this.getPublishers(record);
		groupedWork.addPublishers(publishers);
		String publisher = null;
		if (publishers.size() > 0){
			publisher = publishers.iterator().next();
		}

		//Load publication dates
		Set<String> publicationDates = this.getPublicationDates(record);
		groupedWork.addPublicationDates(publicationDates);
		String publicationDate = null;
		if (publicationDates.size() > 0){
			publicationDate = publicationDates.iterator().next();
		}

		//Load physical description
		Set<String> physicalDescriptions = getFieldList(record, "300abcefg:530abcd");
		String physicalDescription = null;
		if (physicalDescriptions.size() > 0){
			physicalDescription = physicalDescriptions.iterator().next();
		}
		groupedWork.addPhysical(physicalDescriptions);

		//Update the work with format information
		groupedWork.addFormat(format);
		groupedWork.addFormatCategory(formatCategory);
		groupedWork.setFormatBoost(formatBoost);

		//Figure out ownership information
		HashSet<Scope> relatedScopes = new HashSet<Scope>();
		HashSet<String> owningLibraries = new HashSet<String>();
		HashSet<String> owningLibraryCodes = new HashSet<String>();
		HashSet<String> owningLocationCodes = new HashSet<String>();
		HashSet<String> owningLocations = new HashSet<String>();
		HashSet<String> owningLocationCodesAndSubdomains = new HashSet<String>();
		for (Scope curScope: indexer.getScopes()){
			if (curScope.isIncludeHoopla()){
				relatedScopes.add(curScope);
				if (curScope.isLibraryScope()){
					owningLibraries.add(curScope.getFacetLabel());
					owningLibraryCodes.add(curScope.getScopeName());
				}else{
					owningLocations.add(curScope.getFacetLabel());
					owningLocationCodes.add(curScope.getScopeName());
				}
				owningLocationCodesAndSubdomains.add(curScope.getScopeName());
			}
		}
		groupedWork.addOwningLibraries(owningLibraries);
		groupedWork.addOwningLocations(owningLocations);
		groupedWork.addOwningLocationCodesAndSubdomains(owningLocationCodesAndSubdomains);

		groupedWork.addEContentSource("Hoopla", owningLibraryCodes, owningLocationCodes);

		//Load availability
		//For hoopla, everything is always available
		//Availability should be any libraries that can access hoopla

		groupedWork.addAvailableLocations(indexer.getHooplaLocationFacets(), owningLocationCodesAndSubdomains);
		groupedWork.addAvailabilityByFormatForLocation(owningLocationCodesAndSubdomains, format, "available");

		//TODO: Popularity - Hoopla
		groupedWork.addPopularity(1);

		//TODO: Date added, could this be done based of date first detected in Pika?

		//Related Record
		//TODO: add url? or add url within an item record
		String recordIdentifier = groupedWork.addRelatedRecord("hoopla:" + identifier, format, primaryEdition, primaryLanguage, publisher, publicationDate, physicalDescription);

		//Setup information based on the scopes
		//Do not set compatible ptypes for eContent since they are just determined by owning library/location
		for (Scope validScope : relatedScopes) {
			//groupedWork.addCompatiblePTypes(validScope.getRelatedPTypes());
			ScopedWorkDetails workDetails = groupedWork.getScopedWorkDetails().get(validScope.getScopeName());
			workDetails.getRelatedRecords().add(recordIdentifier);

			workDetails.addFormat(format);
			workDetails.addFormatCategory(formatCategory);
			workDetails.setFormatBoost(formatBoost);
		}
	}
}
