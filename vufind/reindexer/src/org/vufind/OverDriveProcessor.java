package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
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
	//private HashMap<Long, String> subdomainMap = new HashMap<Long, String>();

	public OverDriveProcessor(GroupedWorkIndexer groupedWorkIndexer, Connection vufindConn, Connection econtentConn, Ini configIni, Logger logger) {
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
			ResultSet libraryInformationRS = libraryInformationStmt.executeQuery();
			while (libraryInformationRS.next()){
				Long libraryId = libraryInformationRS.getLong("libraryId");
				String facetLabel = libraryInformationRS.getString("facetLabel");
				if (facetLabel.length() > 0){
				facetLabel += " Online";
				}
				String subdomain = libraryInformationRS.getString("subdomain");
				libraryMap.put(libraryId, facetLabel);
				//subdomainMap.put(libraryId, subdomain);
			}
		} catch (SQLException e) {
			logger.error("Error setting up system maps", e);
		}
	}

	public void processRecord(GroupedWorkSolr groupedWork, String identifier) {
		groupedWork.addRelatedRecord("overdrive:" + identifier);
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
				if (mediaType.equals("Audiobook")){
					groupedWork.addFormatCategory("Audio Books");
				}else if (mediaType.equals("Video")){
					groupedWork.addFormatCategory("Movies");
				}else{
					groupedWork.addFormatCategory(mediaType);
				}
				groupedWork.addSeries(productRS.getString("series"));
				groupedWork.setAuthor(productRS.getString("primaryCreatorName"));
				groupedWork.setAuthorDisplay(productRS.getString("primaryCreatorName"));
				productRS.close();

				loadOverDriveMetadata(groupedWork, productId);
				loadOverDriveFormats(groupedWork, productId);
				loadOverDriveLanguages(groupedWork, productId);
				loadOverDriveSubjects(groupedWork, productId);
				//Load availability
				getProductAvailabilityStmt.setLong(1, productId);
				ResultSet availabilityRS = getProductAvailabilityStmt.executeQuery();
				HashSet<String> owningLibraries = new HashSet<String>();
				HashSet<String> availableLibraries = new HashSet<String>();
				while (availabilityRS.next()){
					long libraryId = availabilityRS.getLong("libraryId");
					boolean available = availabilityRS.getBoolean("available");
					int copiesOwned = availabilityRS.getInt("copiesOwned");
					if (libraryId == -1){
						//Everyone has access to this
						owningLibraries.addAll(libraryMap.values());
						if (available){
							availableLibraries.addAll(libraryMap.values());
						}
					}else{
						owningLibraries.add(libraryMap.get(libraryId));
						if (available){
							availableLibraries.add(libraryMap.get(libraryId));
						}
					}
				}
				groupedWork.addOwningLibraries(owningLibraries);
				groupedWork.addAvailableLocations(availableLibraries);
				groupedWork.addCompatiblePType("all");
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
		String literaryForm = null;
		String literaryFormFull = null;
		String targetAudience = "Adult";
		String targetAudienceFull = "Adult";
		while (subjectsRS.next()){
			String curSubject = subjectsRS.getString("name");
			if (curSubject.contains("Nonfiction")){
				literaryForm = "Non Fiction";
				literaryFormFull = "Non Fiction";
				genres.add("Non Fiction");
			}else	if (curSubject.contains("Fiction")){
				literaryForm = "Fiction";
				literaryFormFull = "Fiction";
				genres.add("Fiction");
			}

			if (curSubject.contains("Poetry")){
				literaryFormFull = "Poetry";
			}if (curSubject.contains("Short Stories") || curSubject.contains("Drama") || curSubject.contains("Essays")){
				literaryFormFull = curSubject;
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
		if (literaryForm != null){
			groupedWork.addLiteraryForm(literaryForm);
		}
		if (literaryFormFull != null){
			groupedWork.addLiteraryFormFull(literaryFormFull);
		}
		groupedWork.addTargetAudience(targetAudience);
		groupedWork.addTargetAudienceFull(targetAudienceFull);
	}

	private void loadOverDriveLanguages(GroupedWorkSolr groupedWork, Long productId) throws SQLException {
		//Load languages
		getProductLanguagesStmt.setLong(1, productId);
		ResultSet languagesRS = getProductLanguagesStmt.executeQuery();
		HashSet<String> languages = new HashSet<String>();
		while (languagesRS.next()){
			languages.add(languagesRS.getString("name"));
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
	}

	private void loadOverDriveFormats(GroupedWorkSolr groupedWork, Long productId) throws SQLException {
		//Load formats
		getProductFormatsStmt.setLong(1, productId);
		ResultSet formatsRS = getProductFormatsStmt.executeQuery();
		HashSet<String> formats = new HashSet<String>();
		Long formatBoost = 1L;
		while (formatsRS.next()){
			String format = formatsRS.getString("name");
			formats.add(format);
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
		groupedWork.addFormats(formats);
		formatsRS.close();
	}

	private void loadOverDriveMetadata(GroupedWorkSolr groupedWork, Long productId) throws SQLException {
		//Load metadata
		getProductMetadataStmt.setLong(1, productId);
		ResultSet metadataRS = getProductMetadataStmt.executeQuery();
		if (metadataRS.next()){
			groupedWork.setSortableTitle(metadataRS.getString("sortTitle"));
			groupedWork.addPublisher(metadataRS.getString("publisher"));
			groupedWork.addPublicationDate(metadataRS.getString("publishDate"));
			//Need to divide this because it seems to be all time checkouts for all libraries, not just our libraries
			//Hopefully OverDrive will give us better stats in the near future that we can use.
			groupedWork.addPopularity(metadataRS.getFloat("popularity") / 500f);
		}
		metadataRS.close();
	}
}
