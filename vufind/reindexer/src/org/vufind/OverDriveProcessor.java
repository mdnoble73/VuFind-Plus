package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.json.JSONException;
import org.json.JSONObject;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;

/**
 * Description goes here
 * VuFind-Plus
 * User: Mark Noble
 * Date: 12/9/13
 * Time: 9:14 AM
 */
public class OverDriveProcessor {
	private GroupedWorkIndexer indexer;
	private Logger logger;
	private PreparedStatement getProductInfoStmt;
	private PreparedStatement getProductMetadataStmt;
	private PreparedStatement getProductAvailabilityStmt;
	//private PreparedStatement getProductCreatorsStmt;
	private PreparedStatement getProductFormatsStmt;
	private PreparedStatement getProductLanguagesStmt;
	private PreparedStatement getProductSubjectsStmt;

	private HashMap<Long, String> libraryMap = new HashMap<Long, String>();
	private HashMap<Long, String> subdomainMap = new HashMap<Long, String>();
	private static HashMap<Long, HashSet<String>> locationsForLibrary = new HashMap<Long, HashSet<String>>();
	private static HashSet<String> allLocationCodes = new HashSet<String>();


	public OverDriveProcessor(GroupedWorkIndexer groupedWorkIndexer, Connection vufindConn, Connection econtentConn, Ini configIni, boolean fullReindex, Logger logger) {
		this.indexer = groupedWorkIndexer;
		this.logger = logger;
		try {
			getProductInfoStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_products where overdriveId = ?");
			getProductMetadataStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_product_metadata where productId = ?");
			getProductAvailabilityStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_product_availability where productId = ?");
			//getProductCreatorsStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_product_creators where productId = ?");
			getProductFormatsStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_product_formats where productId = ?");
			getProductLanguagesStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_product_languages inner join overdrive_api_product_languages_ref on overdrive_api_product_languages.id = languageId where productId = ?");
			getProductSubjectsStmt = econtentConn.prepareStatement("SELECT * from overdrive_api_product_subjects inner join overdrive_api_product_subjects_ref on overdrive_api_product_subjects.id = subjectId where productId = ?");

		} catch (SQLException e) {
			logger.error("Error setting up overdrive processor", e);
		}

		//Setup translation maps for system and location
		try {
			PreparedStatement libraryInformationStmt = vufindConn.prepareStatement("SELECT libraryId, ilsCode, subdomain, facetLabel FROM library", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			PreparedStatement locationsForLibraryStmt = vufindConn.prepareStatement("SELECT locationId, code, facetLabel FROM location WHERE libraryId = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			ResultSet libraryInformationRS = libraryInformationStmt.executeQuery();
			while (libraryInformationRS.next()){
				Long libraryId = libraryInformationRS.getLong("libraryId");
				String facetLabel = libraryInformationRS.getString("facetLabel");
				if (facetLabel.length() > 0){
					facetLabel += " Online";
				}
				String subdomain = libraryInformationRS.getString("subdomain");
				libraryMap.put(libraryId, facetLabel);
				subdomainMap.put(libraryId, subdomain);
				locationsForLibraryStmt.setLong(1, libraryId);
				ResultSet locationsForLibraryRS = locationsForLibraryStmt.executeQuery();
				HashSet<String> relatedLocations = new HashSet<String>();
				while (locationsForLibraryRS.next()){
					relatedLocations.add(locationsForLibraryRS.getString("code"));
					allLocationCodes.add(locationsForLibraryRS.getString("code"));
				}
				locationsForLibrary.put(libraryId, relatedLocations);
				locationsForLibraryRS.close();
			}
			libraryInformationRS.close();
		} catch (SQLException e) {
			logger.error("Error setting up system maps", e);
		}
	}

	public void processRecord(GroupedWorkSolr groupedWork, String identifier) {
		try {
			getProductInfoStmt.setString(1, identifier);
			ResultSet productRS = getProductInfoStmt.executeQuery();
			if (productRS.next()){
				Long productId = productRS.getLong("id");
				String title = productRS.getString("title");
				String subtitle = productRS.getString("subtitle");
				if (subtitle == null){
					subtitle = "";
				}
				groupedWork.setTitle(title);
				groupedWork.setDisplayTitle(title);
				groupedWork.setSubTitle(subtitle);
				String fullTitle = title + " " + subtitle;
				fullTitle = fullTitle.trim();
				groupedWork.addFullTitle(fullTitle);
				groupedWork.setDisplayTitle(fullTitle);
				String mediaType = productRS.getString("mediaType");
				String formatCategory;
				String primaryFormat;
				if (mediaType.equals("Audiobook")){
					formatCategory = "Audio Books";
					primaryFormat = "eAudiobook";
				}else if (mediaType.equals("Video")){
					formatCategory = "Movies";
					primaryFormat = "eVideo";
				}else{
					formatCategory = mediaType;
					primaryFormat = mediaType;
				}
				groupedWork.addFormatCategory(formatCategory);
				groupedWork.addSeries(productRS.getString("series"));
				groupedWork.setAuthor(productRS.getString("primaryCreatorName"));
				groupedWork.setAuthorDisplay(productRS.getString("primaryCreatorName"));
				productRS.close();

				HashMap<String, String> metadata = loadOverDriveMetadata(groupedWork, productId);
				String primaryLanguage = loadOverDriveLanguages(groupedWork, productId);
				loadOverDriveSubjects(groupedWork, productId);

				//Load availability & determine which scopes are valid for the record
				//Start by assuming that all scopes are invalid, and then prove which ones are valid
				HashSet<Scope> validScopes = new HashSet<Scope>();
				HashSet<Scope> invalidScopes = new HashSet<Scope>();
				invalidScopes.addAll(indexer.getScopes());

				getProductAvailabilityStmt.setLong(1, productId);
				ResultSet availabilityRS = getProductAvailabilityStmt.executeQuery();
				HashSet<String> owningLibraries = new HashSet<String>();
				HashSet<String> availableLibraries = new HashSet<String>();
				HashSet<String> owningSubdomains = new HashSet<String>();
				HashSet<String> owningLocations = new HashSet<String>();
				HashSet<String> owningSubdomainsAndLocations = new HashSet<String>();
				HashSet<String> availableSubdomainsAndLocations = new HashSet<String>();

				groupedWork.addRelatedRecord("overdrive:" + identifier, primaryFormat, "", primaryLanguage, metadata.get("publisher"), metadata.get("publicationDate"), "");
				boolean partOfSharedCollection = false;
				while (availabilityRS.next()){
					long libraryId = availabilityRS.getLong("libraryId");
					boolean available = availabilityRS.getBoolean("available");
					int copiesOwned = availabilityRS.getInt("copiesOwned");
					if (libraryId == -1){
						//Everyone has access to this
						partOfSharedCollection = true;
						//Add all scopes that want the overdrive collection
						boolean changeMade = true;
						while (changeMade) {
							changeMade = false;
							for (Scope curScope : invalidScopes) {
								if (curScope.isIncludeOverDriveCollection()) {
									validScopes.add(curScope);
									invalidScopes.remove(curScope);
									changeMade = true;
									break;
								}
							}
						}
						owningLibraries.add("Shared Digital Collection");
						owningLibraries.addAll(libraryMap.values());
						owningSubdomainsAndLocations.addAll(subdomainMap.values());
						owningSubdomains.addAll(subdomainMap.values());
						for (Long curLibraryId : libraryMap.keySet()){
							owningSubdomainsAndLocations.addAll(locationsForLibrary.get(curLibraryId));
							owningLocations.addAll(locationsForLibrary.get(curLibraryId));
						}
						if (available){
							availableLibraries.addAll(libraryMap.values());
							availableSubdomainsAndLocations.addAll(subdomainMap.values());
							for (Long curLibraryId : libraryMap.keySet()){
								availableSubdomainsAndLocations.addAll(locationsForLibrary.get(curLibraryId));
							}
						}
					}else{
						//This is an advantage title
						boolean changeMade = true;
						while (changeMade) {
							changeMade = false;
							for (Scope curScope : invalidScopes) {
								if (curScope.isIncludeOverDriveCollection() && curScope.getLibraryId().equals(libraryId) || curScope.isIncludeOutOfSystemExternalLinks()) {
									validScopes.add(curScope);
									invalidScopes.remove(curScope);
									changeMade = true;
									break;
								}
							}
						}
						owningLibraries.add(libraryMap.get(libraryId));
						owningSubdomainsAndLocations.add(subdomainMap.get(libraryId));
						owningSubdomainsAndLocations.addAll(locationsForLibrary.get(libraryId));
						owningSubdomains.add(subdomainMap.get(libraryId));
						owningLocations.addAll(locationsForLibrary.get(libraryId));
						if (available){
							availableLibraries.add(libraryMap.get(libraryId));
							availableSubdomainsAndLocations.add(subdomainMap.get(libraryId));
							availableSubdomainsAndLocations.addAll(locationsForLibrary.get(libraryId));
						}
					}//End processing availability
				}
				groupedWork.addOwningLibraries(owningLibraries);
				groupedWork.addOwningLocationCodesAndSubdomains(owningSubdomainsAndLocations);


				groupedWork.addAvailableLocations(availableLibraries, availableSubdomainsAndLocations);
				groupedWork.addEContentSource("OverDrive", owningSubdomainsAndLocations, new ArrayList<String>());
				groupedWork.addEContentProtectionType("Limited Access", owningSubdomainsAndLocations, new ArrayList<String>());
				//Setup information based on the scopes
				for (Scope validScope : validScopes) {
					groupedWork.addCompatiblePTypes(validScope.getRelatedPTypes());
					groupedWork.getScopedWorkDetails().get(validScope.getScopeName()).getRelatedRecords().add("overdrive:" + identifier);
				}

				loadOverDriveFormats(groupedWork, productId, formatCategory, owningSubdomains, owningLocations, validScopes);
			}
		} catch (SQLException e) {
			logger.error("Error loading information from Database for overdrive title", e);
		}

	}

	private void loadOverDriveSubjects(GroupedWorkSolr groupedWork, Long productId) throws SQLException {
		//Load subject data
		getProductSubjectsStmt.setLong(1, productId);
		ResultSet subjectsRS = getProductSubjectsStmt.executeQuery();
		HashSet<String> topics = new HashSet<String>();
		HashSet<String> genres = new HashSet<String>();
		HashMap<String, Integer> literaryForm = new HashMap<String, Integer>();
		HashMap<String, Integer> literaryFormFull = new HashMap<String, Integer>();
		String targetAudience = "Adult";
		String targetAudienceFull = "Adult";
		while (subjectsRS.next()){
			String curSubject = subjectsRS.getString("name");
			if (curSubject.contains("Nonfiction")){
				addToMapWithCount(literaryForm, "Non Fiction");
				addToMapWithCount(literaryFormFull, "Non Fiction");
				genres.add("Non Fiction");
			}else	if (curSubject.contains("Fiction")){
				addToMapWithCount(literaryForm, "Fiction");
				addToMapWithCount(literaryFormFull, "Fiction");
				genres.add("Fiction");
			}

			if (curSubject.contains("Poetry")){
				addToMapWithCount(literaryForm, "Fiction");
				addToMapWithCount(literaryFormFull, "Poetry");
			}else if (curSubject.contains("Essays")){
				addToMapWithCount(literaryForm, "Non Fiction");
				addToMapWithCount(literaryFormFull, curSubject);
			}else if (curSubject.contains("Short Stories") || curSubject.contains("Drama")){
				addToMapWithCount(literaryForm, "Fiction");
				addToMapWithCount(literaryFormFull, curSubject);
			}

			if (curSubject.contains("Juvenile")){
				targetAudience = "Juvenile";
				targetAudienceFull = "Juvenile";
			}else if (curSubject.contains("Young Adult")){
				targetAudience = "Juvenile";
				targetAudienceFull = "Adolescent (14-17)";
			}else if (curSubject.contains("Picture Book")){
				targetAudience = "Juvenile";
				targetAudienceFull = "Preschool (0-5)";
			}else if (curSubject.contains("Beginning Reader")){
				targetAudience = "Juvenile";
				targetAudienceFull = "Primary (6-8)";
			}

			topics.add(curSubject);
		}
		groupedWork.addTopic(topics);
		groupedWork.addTopicFacet(topics);
		groupedWork.addGenre(genres);
		groupedWork.addGenreFacet(genres);
		if (literaryForm.size() > 0){
			groupedWork.addLiteraryForms(literaryForm);
		}
		if (literaryFormFull.size() > 0){
			groupedWork.addLiteraryFormsFull(literaryFormFull);
		}
		groupedWork.addTargetAudience(targetAudience);
		groupedWork.addTargetAudienceFull(targetAudienceFull);
	}

	private void addToMapWithCount(HashMap<String, Integer> map, String elementToAdd){
		if (map.containsKey(elementToAdd)){
			map.put(elementToAdd, map.get(elementToAdd) + 1);
		}else{
			map.put(elementToAdd, 1);
		}
	}

	private String loadOverDriveLanguages(GroupedWorkSolr groupedWork, Long productId) throws SQLException {
		String primaryLanguage = null;
		//Load languages
		getProductLanguagesStmt.setLong(1, productId);
		ResultSet languagesRS = getProductLanguagesStmt.executeQuery();
		HashSet<String> languages = new HashSet<String>();
		while (languagesRS.next()){
			String language = languagesRS.getString("name");
			languages.add(language);
			if (primaryLanguage == null){
				primaryLanguage = language;
			}
			String languageCode = languagesRS.getString("code");
			String languageBoost = indexer.translateValue("language_boost", languageCode);
			if (languageBoost != null){
				Long languageBoostVal = Long.parseLong(languageBoost);
				groupedWork.setLanguageBoost(languageBoostVal);
			}
			String languageBoostEs = indexer.translateValue("language_boost_es", languageCode);
			if (languageBoostEs != null){
				Long languageBoostVal = Long.parseLong(languageBoost);
				groupedWork.setLanguageBoostSpanish(languageBoostVal);
			}
		}
		groupedWork.setLanguages(languages);
		languagesRS.close();
		if (primaryLanguage == null){
			primaryLanguage = "English";
		}
		return primaryLanguage;
	}

	private void loadOverDriveFormats(GroupedWorkSolr groupedWork, Long productId, String formatCategory, HashSet<String> owningSubdomains, HashSet<String> owningLocations, HashSet<Scope> validScopes) throws SQLException {
		//Load formats
		getProductFormatsStmt.setLong(1, productId);
		ResultSet formatsRS = getProductFormatsStmt.executeQuery();
		HashSet<String> formats = new HashSet<String>();
		HashSet<String> eContentDevices = new HashSet<String>();
		Long formatBoost = 1L;
		while (formatsRS.next()){
			String format = formatsRS.getString("name");
			formats.add(format);
			String deviceString = indexer.translateValue("device_compatibility", format.replace(' ', '_'));
			String[] devices = deviceString.split("\\|");
			for (String device : devices){
				eContentDevices.add(device.trim());
			}
			String formatBoostStr = indexer.translateValue("format_boost", format.replace(' ', '_'));
			try{
				Long curFormatBoost = Long.parseLong(formatBoostStr);
				if (curFormatBoost > formatBoost){
					formatBoost = curFormatBoost;
				}
			}catch (NumberFormatException e){
				logger.warn("Could not parse format_boost " + formatBoostStr);
			}
		}
		//By default, formats are good for all locations
		groupedWork.addFormats(formats);
		for (ScopedWorkDetails scopedWorkDetails : groupedWork.getScopedWorkDetails().values()){
			if (validScopes.contains(scopedWorkDetails.getScope())){
				scopedWorkDetails.addFormat(formats);
				scopedWorkDetails.addFormatCategory(formatCategory);
			}
		}
		groupedWork.setFormatBoost(formatBoost);
		groupedWork.addEContentDevices(eContentDevices);
		formatsRS.close();
	}

	private HashMap<String, String> loadOverDriveMetadata(GroupedWorkSolr groupedWork, Long productId) throws SQLException {
		HashMap<String, String> returnMetadata = new HashMap<String, String>();
		//Load metadata
		getProductMetadataStmt.setLong(1, productId);
		ResultSet metadataRS = getProductMetadataStmt.executeQuery();
		if (metadataRS.next()){
			groupedWork.setSortableTitle(metadataRS.getString("sortTitle"));
			String publisher = metadataRS.getString("publisher");
			groupedWork.addPublisher(publisher);
			returnMetadata.put("publisher", publisher);
			String publicationDate = metadataRS.getString("publishDate");
			groupedWork.addPublicationDate(publicationDate);
			returnMetadata.put("publicationDate", publicationDate);
			//Need to divide this because it seems to be all time checkouts for all libraries, not just our libraries
			//Hopefully OverDrive will give us better stats in the near future that we can use.
			groupedWork.addPopularity(metadataRS.getFloat("popularity") / 500f);

			//Decode JSON data to get a little more information
			try {
				JSONObject jsonData = new JSONObject(metadataRS.getString("rawData"));
				if (jsonData.has("ATOS")){
					groupedWork.setAcceleratedReaderReadingLevel(jsonData.getString("ATOS"));
				}
			} catch (JSONException e) {
				logger.error("Error loading raw data for OverDrive MetaData");
			}
		}
		metadataRS.close();
		return returnMetadata;
	}
}
