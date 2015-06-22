package org.vufind;

import au.com.bytecode.opencsv.CSVWriter;
import org.apache.solr.client.solrj.SolrServer;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.impl.ConcurrentUpdateSolrServer;
import org.apache.solr.client.solrj.impl.HttpSolrServer;
import org.apache.solr.client.solrj.SolrQuery;
import org.apache.solr.client.solrj.response.QueryResponse;
import org.apache.solr.common.SolrDocument;
import org.apache.solr.common.SolrDocumentList;
import org.apache.solr.common.SolrInputDocument;
import org.ini4j.Ini;

import java.io.*;
import java.sql.*;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.Date;
import java.util.concurrent.ThreadFactory;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.apache.log4j.Logger;

/**
 * Indexes records extracted from the ILS
 *
 * VuFind-Plus
 * User: Mark Noble
 * Date: 11/25/13
 * Time: 2:26 PM
 */
public class GroupedWorkIndexer {
	private Ini configIni;
	private String serverName;
	private String solrPort;
	private Logger logger;
	private SolrServer solrServer;
	private Long indexStartTime;
	private ConcurrentUpdateSolrServer updateServer;
	private IlsRecordProcessor ilsRecordProcessor;
	private OverDriveProcessor overDriveProcessor;
	private EVokeProcessor evokeProcessor;
	private HooplaProcessor hooplaProcessor;
	private HashMap<String, HashMap<String, String>> translationMaps = new HashMap<String, HashMap<String, String>>();
	private HashMap<String, LexileTitle> lexileInformation = new HashMap<String, LexileTitle>();
	private Long maxWorksToProcess = -1L;

	private PreparedStatement getRatingStmt;
	private Connection vufindConn;

	protected int availableAtLocationBoostValue = 50;
	protected int ownedByLocationBoostValue = 10;

	private boolean fullReindex = false;
	private long lastReindexTime;
	private Long lastReindexTimeVariableId;
	private boolean partialReindexRunning;
	private Long partialReindexRunningVariableId;
	private boolean okToIndex = true;


	private HashSet<String> worksWithInvalidLiteraryForms = new HashSet<String>();
	private TreeSet<Scope> scopes = new TreeSet<Scope>();
	private ArrayList<String> scopeNames = new ArrayList<String>();

	PreparedStatement getGroupedWorkPrimaryIdentifiers;
	PreparedStatement getGroupedWorkIdentifiers;

	public GroupedWorkIndexer(String serverName, Connection vufindConn, Connection econtentConn, Ini configIni, boolean fullReindex, Logger logger) {
		indexStartTime = new Date().getTime() / 1000;
		this.serverName = serverName;
		this.logger = logger;
		this.vufindConn = vufindConn;
		this.fullReindex = fullReindex;
		this.configIni = configIni;
		solrPort = configIni.get("Reindex", "solrPort");

		availableAtLocationBoostValue = Integer.parseInt(configIni.get("Reindex", "availableAtLocationBoostValue"));
		ownedByLocationBoostValue = Integer.parseInt(configIni.get("Reindex", "ownedByLocationBoostValue"));

		String maxWorksToProcessStr = Util.cleanIniValue(configIni.get("Reindex", "maxWorksToProcess"));
		if (maxWorksToProcessStr != null && maxWorksToProcessStr.length() > 0){
			try{
				maxWorksToProcess = Long.parseLong(maxWorksToProcessStr);
				logger.warn("Processing a maximum of " + maxWorksToProcess + " works");
			}catch (NumberFormatException e){
				logger.warn("Unable to parse max works to process " + maxWorksToProcessStr);
			}
		}

		//Load the last Index time
		try{
			PreparedStatement loadLastGroupingTime = vufindConn.prepareStatement("SELECT * from variables WHERE name = 'last_reindex_time'");
			ResultSet lastGroupingTimeRS = loadLastGroupingTime.executeQuery();
			if (lastGroupingTimeRS.next()){
				lastReindexTime = lastGroupingTimeRS.getLong("value");
				lastReindexTimeVariableId = lastGroupingTimeRS.getLong("id");
			}
			lastGroupingTimeRS.close();
			loadLastGroupingTime.close();
		} catch (Exception e){
			logger.error("Could not load last index time from variables table ", e);
		}

		//Check to see if a partial reindex is running
		try{
			PreparedStatement loadPartialReindexRunning = vufindConn.prepareStatement("SELECT * from variables WHERE name = 'partial_reindex_running'");
			ResultSet loadPartialReindexRunningRS = loadPartialReindexRunning.executeQuery();
			if (loadPartialReindexRunningRS.next()){
				partialReindexRunning = loadPartialReindexRunningRS.getBoolean("value");
				partialReindexRunningVariableId = loadPartialReindexRunningRS.getLong("id");
			}
			loadPartialReindexRunningRS.close();
			loadPartialReindexRunning.close();
		} catch (Exception e){
			logger.error("Could not load last index time from variables table ", e);
		}

		//Load a few statements we will need later
		try{
			getGroupedWorkPrimaryIdentifiers = vufindConn.prepareStatement("SELECT * FROM grouped_work_primary_identifiers where grouped_work_id = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			//MDN 4/14 - Do not restrict by valid for enrichment since many popular titles
			//Wind up with different work id's due to differences in cataloging.
			getGroupedWorkIdentifiers = vufindConn.prepareStatement("SELECT * FROM grouped_work_identifiers inner join grouped_work_identifiers_ref on identifier_id = grouped_work_identifiers.id where grouped_work_id = ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			//TODO: Restore this functionality
			//getGroupedWorkIdentifiers = vufindConn.prepareStatement("SELECT * FROM grouped_work_identifiers inner join grouped_work_identifiers_ref on identifier_id = grouped_work_identifiers.id where grouped_work_id = ? and valid_for_enrichment = 1", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
		} catch (Exception e){
			logger.error("Could not load statements to get identifiers ", e);
		}

		//Initialize the updateServer and solr server
		if (fullReindex){
			updateServer = new ConcurrentUpdateSolrServer("http://localhost:" + solrPort + "/solr/grouped2", 5000, 10);
			solrServer = new HttpSolrServer("http://localhost:" + solrPort + "/solr/grouped2");
		}else{
			//Check to make sure that at least a couple of minutes have elapsed since the last index
			//Periodically in the middle of the night we get indexes every minute or multiple times a minute
			//which is annoying especially since it generally means nothing is changing.
			long elapsedTime = indexStartTime - lastReindexTime;
			long minIndexingInterval = 4 * 60;
			if (elapsedTime < minIndexingInterval) {
				try {
					logger.debug("Pausing between indexes, last index ran " + elapsedTime / 60 + " minutes ago");
					logger.debug("Pausing for " + (minIndexingInterval - elapsedTime) + " seconds");
					GroupedReindexProcess.addNoteToReindexLog("Pausing between indexes, last index ran " + elapsedTime / 60 + " minutes ago");
					GroupedReindexProcess.addNoteToReindexLog("Pausing for " + (minIndexingInterval - elapsedTime) + " seconds");
					Thread.sleep((minIndexingInterval - elapsedTime) * 1000);
				} catch (InterruptedException e) {
					logger.warn("Pause was interrupted while pausing between indexes");
				}

			}

			if (partialReindexRunning){
				//Oops, a reindex is already running.
				//No longer really care about this since it doesn't happen and there are other ways of finding a stuck process
				//logger.warn("A partial reindex is already running, check to make sure that reindexes don't overlap since that can cause poor performance");
				GroupedReindexProcess.addNoteToReindexLog("A partial reindex is already running, check to make sure that reindexes don't overlap since that can cause poor performance");
			}else{
				updatePartialReindexRunning(true);
			}
			updateServer = new ConcurrentUpdateSolrServer("http://localhost:" + solrPort + "/solr/grouped", 5000, 10);
			solrServer = new HttpSolrServer("http://localhost:" + solrPort + "/solr/grouped");
		}

		loadSystemAndLocationData();

		//Initialize processors
		String ilsIndexingClassString = configIni.get("Reindex", "ilsIndexingClass");
		if (ilsIndexingClassString.equals("Marmot")){
			ilsRecordProcessor = new MarmotRecordProcessor(this, vufindConn, configIni, logger);
		}else if(ilsIndexingClassString.equals("Nashville")){
			ilsRecordProcessor = new NashvilleRecordProcessor(this, vufindConn, configIni, logger);
		}else if(ilsIndexingClassString.equals("WCPL")){
			ilsRecordProcessor = new WCPLRecordProcessor(this, vufindConn, configIni, logger);
		}else if(ilsIndexingClassString.equals("Anythink")){
			ilsRecordProcessor = new AnythinkRecordProcessor(this, vufindConn, configIni, logger);
		}else if(ilsIndexingClassString.equals("Aspencat")){
			ilsRecordProcessor = new AspencatRecordProcessor(this, vufindConn, configIni, logger);
		}else if(ilsIndexingClassString.equals("Flatirons")){
			ilsRecordProcessor = new FlatironsRecordProcessor(this, vufindConn, configIni, logger);
		}else{
			logger.error("Unknown indexing class " + ilsIndexingClassString);
			okToIndex = false;
			return;
		}
		overDriveProcessor = new OverDriveProcessor(this, vufindConn, econtentConn, logger);
		evokeProcessor = new EVokeProcessor(this, vufindConn, configIni, logger);
		hooplaProcessor = new HooplaProcessor(this, configIni, logger);
		//Load translation maps
		loadTranslationMaps();

		//Setup prepared statements to load local enrichment
		try {
			getRatingStmt = vufindConn.prepareStatement("SELECT AVG(rating) as averageRating from user_work_review where groupedRecordPermanentId = ? and rating > 0");
		} catch (SQLException e) {
			logger.error("Could not prepare statements to load local enrichment", e);
		}

		String lexileExportPath = configIni.get("Reindex", "lexileExportPath");
		loadLexileData(lexileExportPath);

		if (fullReindex){
			clearIndex();
		}
	}

	public boolean isOkToIndex(){
		return okToIndex;
	}

	private boolean libraryAndLocationDataLoaded = false;
	protected HashMap<String, String> libraryFacetMap = new HashMap<String, String>();
	protected HashMap<String, String> libraryOnlineFacetMap = new HashMap<String, String>();
	protected HashSet<String> hooplaLocationFacets = new HashSet<String>();
	protected HashSet<String> hooplaLocationCodes = new HashSet<String>();
	protected HashMap<String, String> locationMap = new HashMap<String, String>();
	protected HashMap<String, String> subdomainMap = new HashMap<String, String>();

	//Keep track of what we are indexing for validation purposes
	public TreeSet<String> ilsRecordsIndexed = new TreeSet<String>();
	public TreeSet<String> hooplaRecordsIndexed = new TreeSet<String>();
	public TreeSet<String> overDriveRecordsIndexed = new TreeSet<String>();
	public TreeSet<String> ilsRecordsSkipped = new TreeSet<String>();
	public TreeSet<String> hooplaRecordsSkipped = new TreeSet<String>();
	public TreeSet<String> overDriveRecordsSkipped = new TreeSet<String>();
	public TreeMap<String, ScopedIndexingStats> indexingStats = new TreeMap<String, ScopedIndexingStats>();

	private void loadSystemAndLocationData() {
		indexingStats.put("global", new ScopedIndexingStats("global"));
		if (!libraryAndLocationDataLoaded){
			//Setup translation maps for system and location
			try {
				PreparedStatement libraryInformationStmt = vufindConn.prepareStatement("SELECT libraryId, ilsCode, subdomain, " +
						"displayName, facetLabel, pTypes, restrictSearchByLibrary, econtentLocationsToInclude, enableOverdriveCollection, " +
						"includeOutOfSystemExternalLinks, useScope, orderAccountingUnit, includeHoopla FROM library ORDER BY ilsCode ASC",
						ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
				ResultSet libraryInformationRS = libraryInformationStmt.executeQuery();
				while (libraryInformationRS.next()){
					String code = libraryInformationRS.getString("ilsCode").toLowerCase();
					String facetLabel = libraryInformationRS.getString("facetLabel");
					String subdomain = libraryInformationRS.getString("subdomain");
					String displayName = libraryInformationRS.getString("displayName");
					boolean includeHoopla = libraryInformationRS.getBoolean("includeHoopla");
					if (facetLabel.length() == 0){
						facetLabel = displayName;
					}
					if (facetLabel.length() > 0){
						String onlineFacetLabel = facetLabel + " Online";
						libraryFacetMap.put(code, facetLabel);
						libraryOnlineFacetMap.put(code, onlineFacetLabel);
						if (includeHoopla){
							hooplaLocationFacets.add(onlineFacetLabel);
						}
					}
					subdomainMap.put(code, subdomain);
					//These options determine how scoping is done
					Long libraryId = libraryInformationRS.getLong("libraryId");
					String pTypes = libraryInformationRS.getString("pTypes");
					if (pTypes == null) {pTypes = "";}
					boolean restrictSearchByLibrary = libraryInformationRS.getBoolean("restrictSearchByLibrary");
					String econtentLocationsToInclude = libraryInformationRS.getString("econtentLocationsToInclude");
					if (econtentLocationsToInclude == null) {econtentLocationsToInclude = "all";}
					boolean includeOutOfSystemExternalLinks = libraryInformationRS.getBoolean("includeOutOfSystemExternalLinks");
					boolean useScope = libraryInformationRS.getBoolean("useScope");
					boolean includeOverdrive = libraryInformationRS.getBoolean("enableOverdriveCollection");


					//Determine if we need to build a scope for this library
					//MDN 10/1/2014 always build scopes because it makes coding more consistent elsewhere.
					//We need to build a scope
					Scope newScope = new Scope();
					newScope.setIsLibraryScope(true);
					newScope.setIsLocationScope(false);
					newScope.setScopeName(subdomain);
					newScope.setLibraryId(libraryId);
					newScope.setFacetLabel(facetLabel);
					newScope.setLibraryLocationCodePrefix(code);
					newScope.setIncludeOutOfSystemExternalLinks(includeOutOfSystemExternalLinks);
					newScope.setRelatedPTypes(pTypes.split(","));
					newScope.setIncludeBibsOwnedByTheLibraryOnly(restrictSearchByLibrary);
					newScope.setIncludeItemsOwnedByTheLibraryOnly(useScope);
					newScope.setEContentLocationCodesToInclude(econtentLocationsToInclude.split(","));
					newScope.setIncludeOverDriveCollection(includeOverdrive);
					newScope.setIncludeHoopla(includeHoopla);
					scopes.add(newScope);
					scopeNames.add(newScope.getScopeName());
					indexingStats.put(newScope.getScopeName(), new ScopedIndexingStats(newScope.getScopeName()));
				}

				PreparedStatement locationInformationStmt = vufindConn.prepareStatement("SELECT library.libraryId, code, ilsCode, " +
						"library.subdomain, location.facetLabel, location.displayName, library.pTypes, library.useScope as useScopeLibrary, " +
						"location.useScope as useScopeLocation, library.scope AS libraryScope, location.scope AS locationScope, " +
						"restrictSearchByLocation, restrictSearchByLibrary, library.econtentLocationsToInclude as econtentLocationsToIncludeLibrary, " +
						"location.econtentLocationsToInclude as econtentLocationsToIncludeLocation, library.enableOverdriveCollection as enableOverdriveCollectionLibrary, " +
						"location.enableOverdriveCollection as enableOverdriveCollectionLocation, includeOutOfSystemExternalLinks, " +
						"extraLocationCodesToInclude, includeHoopla " +
						"FROM location INNER JOIN library on library.libraryId = location.libraryid ORDER BY code ASC",
						ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
				ResultSet locationInformationRS = locationInformationStmt.executeQuery();
				while (locationInformationRS.next()){
					String code = locationInformationRS.getString("code").toLowerCase();
					String libraryIlsCode = locationInformationRS.getString("ilsCode").toLowerCase();
					String facetLabel = locationInformationRS.getString("facetLabel");
					String extraLocationCodesToInclude = locationInformationRS.getString("extraLocationCodesToInclude").toLowerCase();
					String displayName = locationInformationRS.getString("displayName");
					if (facetLabel.length() == 0){
						facetLabel = displayName;
					}
					locationMap.put(code, facetLabel);

					//Determine if we need to build a scope for this location
					Long libraryId = locationInformationRS.getLong("libraryId");
					String pTypes = locationInformationRS.getString("pTypes");
					if (pTypes == null) pTypes = "";
					boolean restrictSearchByLibrary = locationInformationRS.getBoolean("restrictSearchByLibrary");
					boolean restrictSearchByLocation = locationInformationRS.getBoolean("restrictSearchByLocation");
					boolean includeOverDriveCollectionLibrary = locationInformationRS.getBoolean("enableOverdriveCollectionLibrary");
					boolean includeOverDriveCollectionLocation = locationInformationRS.getBoolean("enableOverdriveCollectionLocation");
					String econtentLocationsToIncludeLibrary = locationInformationRS.getString("econtentLocationsToIncludeLibrary");
					if (econtentLocationsToIncludeLibrary == null){
						econtentLocationsToIncludeLibrary = "all";
					}
					String econtentLocationsToIncludeLocation = locationInformationRS.getString("econtentLocationsToIncludeLocation");
					if (econtentLocationsToIncludeLocation == null || econtentLocationsToIncludeLocation.length() == 0){
						econtentLocationsToIncludeLocation = econtentLocationsToIncludeLibrary;
					}
					boolean includeOutOfSystemExternalLinks = locationInformationRS.getBoolean("includeOutOfSystemExternalLinks");
					boolean useScopeLibrary = locationInformationRS.getBoolean("useScopeLibrary");
					Integer libraryScope = locationInformationRS.getInt("libraryScope");
					boolean useScopeLocation = locationInformationRS.getBoolean("useScopeLocation");
					Integer locationScope = locationInformationRS.getInt("locationScope");
					boolean includeHoopla = locationInformationRS.getBoolean("includeHoopla");
					if (includeHoopla){
						hooplaLocationCodes.add(code);
					}
					if (pTypes.length() == 0 && !restrictSearchByLocation && econtentLocationsToIncludeLocation.equalsIgnoreCase("all") && includeOutOfSystemExternalLinks && !useScopeLocation){
						logger.debug("Not creating a scope for locations because there are no restrictions for the location " + code);
					}else{
						//Check to see if the location has the same restrictions as the library.
						boolean needLocationScope = false;
						if (restrictSearchByLocation || !econtentLocationsToIncludeLibrary.equals(econtentLocationsToIncludeLocation)){
							needLocationScope = true;
						}else if (useScopeLocation && !libraryScope.equals(locationScope)){
							needLocationScope = true;
						}
						if (needLocationScope){
							Scope locationScopeInfo = new Scope();
							locationScopeInfo.setIsLibraryScope(false);
							locationScopeInfo.setIsLocationScope(true);
							locationScopeInfo.setScopeName(code);
							locationScopeInfo.setLibraryId(libraryId);
							locationScopeInfo.setLibraryLocationCodePrefix(libraryIlsCode);
							locationScopeInfo.setLocationLocationCodePrefix(code);
							locationScopeInfo.setRelatedPTypes(pTypes.split(","));
							locationScopeInfo.setFacetLabel(facetLabel);
							locationScopeInfo.setIncludeBibsOwnedByTheLibraryOnly(restrictSearchByLibrary);
							locationScopeInfo.setIncludeBibsOwnedByTheLocationOnly(restrictSearchByLocation);
							locationScopeInfo.setIncludeItemsOwnedByTheLibraryOnly(useScopeLibrary);
							locationScopeInfo.setIncludeItemsOwnedByTheLocationOnly(useScopeLocation);
							locationScopeInfo.setEContentLocationCodesToInclude(econtentLocationsToIncludeLocation.split(","));
							locationScopeInfo.setIncludeOutOfSystemExternalLinks(includeOutOfSystemExternalLinks);
							locationScopeInfo.setIncludeOverDriveCollection(includeOverDriveCollectionLibrary && includeOverDriveCollectionLocation);
							locationScopeInfo.setIncludeHoopla(includeHoopla);
							locationScopeInfo.setExtraLocationCodes(extraLocationCodesToInclude);

							if (!scopes.contains(locationScopeInfo)){
								scopes.add(locationScopeInfo);
								scopeNames.add(locationScopeInfo.getScopeName());
								indexingStats.put(locationScopeInfo.getScopeName(), new ScopedIndexingStats(locationScopeInfo.getScopeName()));
							}else{
								logger.debug("Not adding location scope because a library scope with the name " + locationScopeInfo.getScopeName() + " exists already.");
								for (Scope existingLibraryScope : scopes){
									if (existingLibraryScope.equals(locationScopeInfo)){
										existingLibraryScope.setIsLocationScope(true);
										break;
									}
								}
							}
						}else{
							logger.debug("No scope needed for " + code + " because the library scope works just fine");
						}
					}
				}
			} catch (SQLException e) {
				logger.error("Error setting up system maps", e);
			}
			libraryAndLocationDataLoaded = true;
		}
	}

	private void loadLexileData(String lexileExportPath) {
		try{
			File lexileData = new File(lexileExportPath);
			BufferedReader lexileReader = new BufferedReader(new FileReader(lexileData));
			//Skip over the header
			lexileReader.readLine();
			String lexileLine = lexileReader.readLine();
			while (lexileLine != null){
				String[] lexileFields = lexileLine.split("\\t");
				LexileTitle titleInfo = new LexileTitle();
				if (lexileFields.length >= 11){
					titleInfo.setTitle(lexileFields[0]);
					titleInfo.setAuthor(lexileFields[1]);
					String isbn = lexileFields[3];
					titleInfo.setLexileCode(lexileFields[4]);
					titleInfo.setLexileScore(lexileFields[5]);
					titleInfo.setSeries(lexileFields[9]);
					titleInfo.setAwards(lexileFields[10]);
					titleInfo.setDescription(lexileFields[11]);
					lexileInformation.put(isbn, titleInfo);
				}
				lexileLine = lexileReader.readLine();
			}
			logger.info("Read " + lexileInformation.size() + " lines of lexile data");
		}catch (Exception e){
			logger.error("Error loading lexile data", e);
		}
	}

	private void clearIndex() {
		//Check to see if we should clear the existing index
		logger.info("Clearing existing marc records from index");
		try {
			updateServer.deleteByQuery("recordtype:grouped_work", 10);
			updateServer.commit(true, true);
		} catch (Exception e) {
			logger.error("Error deleting from index", e);
		}
	}

	public void finishIndexing(){
		logger.info("Finishing indexing");
		try {
			logger.info("Calling commit");
			updateServer.commit(true, true);
		} catch (Exception e) {
			logger.error("Error calling final commit", e);
		}
		//Solr now optimizes itself.  No need to force an optimization.
		try {
			//Optimize to trigger improved performance.  If we're doing a full reindex, need to wait for the searcher since
			// we are going to swap in a minute.
			logger.info("Optimizing index");
			if (fullReindex) {
				updateServer.optimize(true, true);
			}
			logger.info("Finished Optimizing index");
		} catch (Exception e) {
			logger.error("Error optimizing index", e);
		}
		try {
			logger.info("Shutting down the update server");
			updateServer.shutdown();
		} catch (Exception e) {
			logger.error("Error shutting down update server", e);
		}
		//Swap the indexes
		if (fullReindex)  {
			try {
				Util.getURL("http://localhost:" + solrPort + "/solr/admin/cores?action=SWAP&core=grouped2&other=grouped", logger);
			} catch (Exception e) {
				logger.error("Error shutting down update server", e);
			}
		}
		writeWorksWithInvalidLiteraryForms();
		updateLastReindexTime();
		updatePartialReindexRunning(false);
		//Write validation information
		if (fullReindex) {
			writeValidationInformation();
			writeStats();
		}
	}

	private void writeStats() {
		try {
			File dataDir = new File(configIni.get("Reindex", "marcPath"));
			dataDir = dataDir.getParentFile();
			//write the records in CSV format to the data directory
			Date curDate = new Date();
			String curDateFormatted = dayFormatter.format(curDate);
			File recordsFile = new File(dataDir.getAbsolutePath() + "/reindex_stats_" + curDateFormatted + ".csv");
			CSVWriter recordWriter = new CSVWriter(new FileWriter(recordsFile));
			recordWriter.writeNext(new String[]{
					"Scope Name",
					"Num local works",
					"Num super scope works",
					"Num local ils records",
					"Num super scope ils records",
					"Num local ils items",
					"Num super scope ils items",
					"Num local ils econtent items",
					"Num super scope ils econtent items",
					"Num local order items",
					"Num super scope order items",
					"Num local overdrive records",
					"Num super scope overdrive records",
					"Num hoopla records",
			});

			//Write global scope
			ScopedIndexingStats stats = indexingStats.get("global");
			recordWriter.writeNext(new String[]{
					stats.getScopeName(),
					Integer.toString(stats.numLocalWorks),
					Integer.toString(stats.numSuperScopeWorks),
					Integer.toString(stats.numLocalIlsRecords),
					Integer.toString(stats.numSuperScopeIlsRecords),
					Integer.toString(stats.numLocalIlsItems),
					Integer.toString(stats.numSuperScopeIlsItems),
					Integer.toString(stats.numLocalEContentItems),
					Integer.toString(stats.numSuperScopeEContentItems),
					Integer.toString(stats.numLocalOrderItems),
					Integer.toString(stats.numSuperScopeOrderItems),
					Integer.toString(stats.numLocalOverDriveRecords),
					Integer.toString(stats.numSuperScopeOverDriveRecords),
					Integer.toString(stats.numHooplaRecords),
			});

			//Write custom scopes
			for (String curScope: indexingStats.keySet()){
				stats = indexingStats.get(curScope);
				if (!curScope.equals("global")) {
					recordWriter.writeNext(new String[]{
							stats.getScopeName(),
							Integer.toString(stats.numLocalWorks),
							Integer.toString(stats.numSuperScopeWorks),
							Integer.toString(stats.numLocalIlsRecords),
							Integer.toString(stats.numSuperScopeIlsRecords),
							Integer.toString(stats.numLocalIlsItems),
							Integer.toString(stats.numSuperScopeIlsItems),
							Integer.toString(stats.numLocalEContentItems),
							Integer.toString(stats.numSuperScopeEContentItems),
							Integer.toString(stats.numLocalOrderItems),
							Integer.toString(stats.numSuperScopeOrderItems),
							Integer.toString(stats.numLocalOverDriveRecords),
							Integer.toString(stats.numSuperScopeOverDriveRecords),
							Integer.toString(stats.numHooplaRecords),
					});
				}
			}
			recordWriter.flush();
			recordWriter.close();
		} catch (IOException e) {
			logger.error("Unable to write statistics", e);
		}
	}

	private void writeValidationInformation() {
		writeExistingRecordsFile(hooplaRecordsIndexed, "reindexer_hoopla_records_processed");
		writeExistingRecordsFile(hooplaRecordsSkipped, "reindexer_hoopla_records_skipped");
		writeExistingRecordsFile(overDriveRecordsIndexed, "reindexer_overdrive_records_processed");
		writeExistingRecordsFile(overDriveRecordsSkipped, "reindexer_overdrive_records_skipped");
		writeExistingRecordsFile(ilsRecordsIndexed, "reindexer_ils_records_processed");
		writeExistingRecordsFile(ilsRecordsSkipped, "reindexer_ils_records_skipped");
	}

	private static SimpleDateFormat dayFormatter = new SimpleDateFormat("yyyy-MM-dd");
	private void writeExistingRecordsFile(TreeSet<String> recordNumbersInExport, String filePrefix) {
		try {
			File dataDir = new File(configIni.get("Reindex", "marcPath"));
			dataDir = dataDir.getParentFile();
			//write the records in CSV format to the data directory
			Date curDate = new Date();
			String curDateFormatted = dayFormatter.format(curDate);
			File recordsFile = new File(dataDir.getAbsolutePath() + "/" + filePrefix + "_" + curDateFormatted + ".csv");
			CSVWriter recordWriter = new CSVWriter(new FileWriter(recordsFile));
			for (String curRecord: recordNumbersInExport){
				recordWriter.writeNext(new String[]{curRecord});
			}
			recordWriter.flush();
			recordWriter.close();
		} catch (IOException e) {
			logger.error("Unable to write existing records to " + filePrefix, e);
		}
	}

	private void updatePartialReindexRunning(boolean running) {
		if (!fullReindex) {
			logger.info("Updating partial reindex running");
			//Update the last grouping time in the variables table
			try {
				if (partialReindexRunningVariableId != null) {
					PreparedStatement updateVariableStmt = vufindConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
					updateVariableStmt.setString(1, Boolean.toString(running));
					updateVariableStmt.setLong(2, partialReindexRunningVariableId);
					updateVariableStmt.executeUpdate();
					updateVariableStmt.close();
				} else {
					PreparedStatement insertVariableStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('partial_reindex_running', ?)", Statement.RETURN_GENERATED_KEYS);
					insertVariableStmt.setString(1, Boolean.toString(running));
					insertVariableStmt.executeUpdate();
					ResultSet generatedKeys = insertVariableStmt.getGeneratedKeys();
					if (generatedKeys.next()){
						partialReindexRunningVariableId = generatedKeys.getLong(1);
					}
					insertVariableStmt.close();
				}
			} catch (Exception e) {
				logger.error("Error setting last grouping time", e);
			}
		}
	}

	private void writeWorksWithInvalidLiteraryForms() {
		logger.info("Writing works with invalid literary forms");
		File worksWithInvalidLiteraryFormsFile = new File ("/var/log/vufind-plus/" + serverName + "/worksWithInvalidLiteraryForms.txt");
		try {
			if (worksWithInvalidLiteraryForms.size() > 0) {
				FileWriter writer = new FileWriter(worksWithInvalidLiteraryFormsFile, false);
				logger.debug("Found " + worksWithInvalidLiteraryForms.size() + " grouped works with invalid literary forms\r\n");
				writer.write("Found " + worksWithInvalidLiteraryForms.size() + " grouped works with invalid literary forms\r\n");
				writer.write("Works with inconsistent literary forms\r\n");
				for (String curId : worksWithInvalidLiteraryForms){
					writer.write(curId + "\r\n");
				}
			}
		}catch(Exception e){
			logger.error("Error writing works with invalid literary forms", e);
		}
	}

	private void updateLastReindexTime() {
		//Update the last grouping time in the variables table.  This needs to be the time the index started to catch anything that changes during the index
		try{
			if (lastReindexTimeVariableId != null){
				PreparedStatement updateVariableStmt  = vufindConn.prepareStatement("UPDATE variables set value = ? WHERE id = ?");
				updateVariableStmt.setLong(1, indexStartTime);
				updateVariableStmt.setLong(2, lastReindexTimeVariableId);
				updateVariableStmt.executeUpdate();
				updateVariableStmt.close();
			} else{
				PreparedStatement insertVariableStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('last_reindex_time', ?)");
				insertVariableStmt.setString(1, Long.toString(indexStartTime));
				insertVariableStmt.executeUpdate();
				insertVariableStmt.close();
			}
		}catch (Exception e){
			logger.error("Error setting last grouping time", e);
		}
	}

	public Long processGroupedWorks() {
		Long numWorksProcessed = 0L;
		try {
			PreparedStatement getAllGroupedWorks;
			PreparedStatement getNumWorksToIndex;
			PreparedStatement setLastUpdatedTime = vufindConn.prepareStatement("UPDATE grouped_work set date_updated = ? where id = ?");
			if (fullReindex){
				getAllGroupedWorks = vufindConn.prepareStatement("SELECT * FROM grouped_work", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
				getNumWorksToIndex = vufindConn.prepareStatement("SELECT count(id) FROM grouped_work", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
			}else{
				//Load all grouped works that have changed since the last time the index ran
				getAllGroupedWorks = vufindConn.prepareStatement("SELECT * FROM grouped_work WHERE date_updated IS NULL OR date_updated >= ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
				getAllGroupedWorks.setLong(1, lastReindexTime);
				getNumWorksToIndex = vufindConn.prepareStatement("SELECT count(id) FROM grouped_work WHERE date_updated IS NULL OR date_updated >= ?", ResultSet.TYPE_FORWARD_ONLY,  ResultSet.CONCUR_READ_ONLY);
				getNumWorksToIndex.setLong(1, lastReindexTime);
			}

			//Get the number of works we will be processing
			ResultSet numWorksToIndexRS = getNumWorksToIndex.executeQuery();
			numWorksToIndexRS.next();
			Long numWorksToIndex = numWorksToIndexRS.getLong(1);
			GroupedReindexProcess.addNoteToReindexLog("Starting to process " + numWorksToIndex + " grouped works");

			ResultSet groupedWorks = getAllGroupedWorks.executeQuery();
			while (groupedWorks.next()){
				Long id = groupedWorks.getLong("id");
				String permanentId = groupedWorks.getString("permanent_id");
				String grouping_category = groupedWorks.getString("grouping_category");
				Long lastUpdated = groupedWorks.getLong("date_updated");
				if (groupedWorks.wasNull()){
					lastUpdated = null;
				}
				processGroupedWork(id, permanentId, grouping_category);

				numWorksProcessed++;
				if (numWorksProcessed % 5000 == 0){
					//commitChanges();
					logger.info("Processed " + numWorksProcessed + " grouped works processed.");
				}
				if (maxWorksToProcess != -1 && numWorksProcessed >= maxWorksToProcess){
					logger.warn("Stopping processing now because we've reached the max works to process.");
					break;
				}
				if (lastUpdated == null){
					setLastUpdatedTime.setLong(1, indexStartTime - 1); //Set just before the index started so we don't index multiple times
					setLastUpdatedTime.setLong(2, id);
					setLastUpdatedTime.executeUpdate();
				}
			}
		} catch (SQLException e) {
			logger.error("Unexpected SQL error", e);
		}
		logger.info("Finished processing grouped works.  Processed a total of " + numWorksProcessed + " grouped works");
		return numWorksProcessed;
	}

	public void processGroupedWork(Long id, String permanentId, String grouping_category) throws SQLException {
		//Create a solr record for the grouped work
		GroupedWorkSolr groupedWork = new GroupedWorkSolr(this, logger);
		groupedWork.setId(permanentId);
		groupedWork.setGroupingCategory(grouping_category);

		getGroupedWorkPrimaryIdentifiers.setLong(1, id);
		ResultSet groupedWorkPrimaryIdentifiers = getGroupedWorkPrimaryIdentifiers.executeQuery();
		int numPrimaryIdentifiers = 0;
		while (groupedWorkPrimaryIdentifiers.next()){
			String type = groupedWorkPrimaryIdentifiers.getString("type");
			String identifier = groupedWorkPrimaryIdentifiers.getString("identifier");
			//This does the bulk of the work building fields for the solr document
			updateGroupedWorkForPrimaryIdentifier(groupedWork, type, identifier);
			numPrimaryIdentifiers++;
		}

		if (numPrimaryIdentifiers > 0) {
			indexingStats.get("global").numSuperScopeWorks++;
			indexingStats.get("global").numLocalWorks++;
			//Update the grouped record based on data for each work
			getGroupedWorkIdentifiers.setLong(1, id);
			ResultSet groupedWorkIdentifiers = getGroupedWorkIdentifiers.executeQuery();
			//This just adds isbns, issns, upcs, and oclc numbers to the index
			while (groupedWorkIdentifiers.next()) {
				String type = groupedWorkIdentifiers.getString("type");
				String identifier = groupedWorkIdentifiers.getString("identifier");
				updateGroupedWorkForSecondaryIdentifier(groupedWork, type, identifier);
			}

			//Load local (VuFind) enrichment for the work
			loadLocalEnrichment(groupedWork);
			//Load lexile data for the work
			loadLexileDataForWork(groupedWork);

			//Write the record to Solr.
			try {
				SolrInputDocument inputDocument = groupedWork.getSolrDocument(availableAtLocationBoostValue, ownedByLocationBoostValue);
				updateServer.add(inputDocument);

				for (String scope: groupedWork.getScopedWorkDetails().keySet()){
					if (groupedWork.getScopedWorkDetails().get(scope).isLocallyOwned()) {
						indexingStats.get(scope).numLocalWorks++;
					}
					indexingStats.get(scope).numSuperScopeWorks++;
				}
			} catch (Exception e) {
				logger.error("Error adding record to solr", e);
			}
		}else{
			//Log that this record did not have primary identifiers after
		}
	}

	private void loadLexileDataForWork(GroupedWorkSolr groupedWork) {
		for(String isbn : groupedWork.getIsbns()){
			if (lexileInformation.containsKey(isbn)){
				LexileTitle lexileTitle = lexileInformation.get(isbn);
				String lexileCode = lexileTitle.getLexileCode();
				if (lexileCode.length() > 0){
					groupedWork.setLexileCode(this.translateValue("lexile_code", lexileCode));
				}
				groupedWork.setLexileScore(lexileTitle.getLexileScore());
				groupedWork.addAwards(lexileTitle.getAwards());
				if (lexileTitle.getSeries().length() > 0){
					groupedWork.addSeries(lexileTitle.getSeries());
				}
				break;
			}
		}
	}

	private void loadLocalEnrichment(GroupedWorkSolr groupedWork) {
		//Load rating
		try{
			getRatingStmt.setString(1, groupedWork.getId());
			ResultSet ratingsRS = getRatingStmt.executeQuery();
			if (ratingsRS.next()){
				Float averageRating = ratingsRS.getFloat("averageRating");
				if (averageRating != null){
					groupedWork.setRating(averageRating);
				}
			}
		}catch (Exception e){
			logger.error("Unable to load local enrichment", e);
		}
	}

	private void updateGroupedWorkForPrimaryIdentifier(GroupedWorkSolr groupedWork, String type, String identifier) {
		groupedWork.addAlternateId(identifier);
		type = type.toLowerCase();
		if (type.equals("ils")){
			//Get the ils record from the individual marc records
			ilsRecordProcessor.processRecord(groupedWork, identifier);
		}else if (type.equals("overdrive")){
			overDriveProcessor.processRecord(groupedWork, identifier);
		}else if (type.equals("evoke")) {
			evokeProcessor.processRecord(groupedWork, identifier);
		}else if (type.equals("hoopla")){
			hooplaProcessor.processRecord(groupedWork, identifier);
		}else{
			logger.warn("Unknown identifier type " + type);
		}
	}

	private void updateGroupedWorkForSecondaryIdentifier(GroupedWorkSolr groupedWork, String type, String identifier) {
		type = type.toLowerCase();
		if (type.equals("isbn")){
			groupedWork.addIsbn(identifier);
		}else if (type.equals("upc")){
			groupedWork.addUpc(identifier);
		}else if (type.equals("order")){
			//Add as an alternate id
			groupedWork.addAlternateId(identifier);
		}else if (!type.equals("issn") && !type.equals("oclc")){
			logger.warn("Unknown identifier type " + type);
		}
	}

	private void loadTranslationMaps(){
		//Load all translationMaps, first from default, then from the site specific configuration
		File defaultTranslationMapDirectory = new File("../../sites/default/translation_maps");
		File[] defaultTranslationMapFiles = defaultTranslationMapDirectory.listFiles(new FilenameFilter() {
			@Override
			public boolean accept(File dir, String name) {
				return name.endsWith("properties");
			}
		});

		File serverTranslationMapDirectory = new File("../../sites/" + serverName + "/translation_maps");
		File[] serverTranslationMapFiles = serverTranslationMapDirectory.listFiles(new FilenameFilter() {
			@Override
			public boolean accept(File dir, String name) {
				return name.endsWith("properties");
			}
		});

		for (File curFile : defaultTranslationMapFiles){
			String mapName = curFile.getName().replace(".properties", "");
			mapName = mapName.replace("_map", "");
			translationMaps.put(mapName, loadTranslationMap(curFile));
		}
		for (File curFile : serverTranslationMapFiles){
			String mapName = curFile.getName().replace(".properties", "");
			mapName = mapName.replace("_map", "");
			translationMaps.put(mapName, loadTranslationMap(curFile));
		}
	}

	private HashMap<String, String> loadTranslationMap(File translationMapFile) {
		Properties props = new Properties();
		try {
			props.load(new FileReader(translationMapFile));
		} catch (IOException e) {
			logger.error("Could not read translation map, " + translationMapFile.getAbsolutePath(), e);
		}
		HashMap<String, String> translationMap = new HashMap<String, String>();
		for (Object keyObj : props.keySet()){
			String key = (String)keyObj;
			translationMap.put(key.toLowerCase(), props.getProperty(key));
		}
		return translationMap;
	}

	HashSet<String> unableToTranslateWarnings = new HashSet<String>();
	public String translateValue(String mapName, String value){
		if (value == null){
			return value;
		}
		HashMap<String, String> translationMap = translationMaps.get(mapName);
		String translatedValue;
		if (translationMap == null){
			logger.error("Unable to find translation map for " + mapName);
			translatedValue = value;
		}else{
			String lowerCaseValue = value.toLowerCase();
			if (translationMap.containsKey(lowerCaseValue)){
				translatedValue = translationMap.get(lowerCaseValue);
			}else{
				if (translationMap.containsKey("*")){
					translatedValue = translationMap.get("*");
				}else{
					String concatenatedValue = mapName + ":" + value;
					if (!unableToTranslateWarnings.contains(concatenatedValue)){
						if (fullReindex) {
							logger.warn("Could not translate '" + concatenatedValue + "'");
						}
						unableToTranslateWarnings.add(concatenatedValue);
					}
					translatedValue = value;
				}
			}
		}
		if (translatedValue != null){
			translatedValue = translatedValue.trim();
			if (translatedValue.length() == 0){
				translatedValue = null;
			}
		}
		return translatedValue;
	}

	public LinkedHashSet<String> translateCollection(String mapName, Set<String> values) {
		LinkedHashSet<String> translatedCollection = new LinkedHashSet<String>();
		for (String value : values){
			String translatedValue = translateValue(mapName, value);
			if (translatedValue != null) {
				translatedCollection.add(translatedValue);
			}
		}
		return  translatedCollection;
	}

	private final static Pattern FOUR_DIGIT_PATTERN_BRACES							= Pattern.compile("\\[[12]\\d{3}\\]");
	private final static Pattern				FOUR_DIGIT_PATTERN_ONE_BRACE					= Pattern.compile("\\[[12]\\d{3}");
	private final static Pattern				FOUR_DIGIT_PATTERN_STARTING_WITH_1_2	= Pattern.compile("(20|19|18|17|16|15)[0-9][0-9]");
	private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_1						= Pattern.compile("l\\d{3}");
	private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_2						= Pattern.compile("\\[19\\]\\d{2}");
	private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_3						= Pattern.compile("(20|19|18|17|16|15)[0-9][-?0-9]");
	private final static Pattern				FOUR_DIGIT_PATTERN_OTHER_4						= Pattern.compile("i.e. (20|19|18|17|16|15)[0-9][0-9]");
	private final static Pattern				BC_DATE_PATTERN												= Pattern.compile("[0-9]+ [Bb][.]?[Cc][.]?");

	/**
	 * Cleans non-digits from a String
	 *
	 * @param date
	 *          String to parse
	 * @return Numeric part of date String (or null)
	 */
	public static String cleanDate(final String date) {
		if (date == null || date.length() == 0){
			return null;
		}
		Matcher matcher_braces = FOUR_DIGIT_PATTERN_BRACES.matcher(date);

		String cleanDate = null; // raises DD-anomaly

		if (matcher_braces.find()) {
			cleanDate = matcher_braces.group();
			cleanDate = removeOuterBrackets(cleanDate);
		} else{
			Matcher matcher_ie_date = FOUR_DIGIT_PATTERN_OTHER_4.matcher(date);
			if (matcher_ie_date.find()) {
				cleanDate = matcher_ie_date.group().replaceAll("i.e. ", "");
			} else {
				Matcher matcher_one_brace = FOUR_DIGIT_PATTERN_ONE_BRACE.matcher(date);
				if (matcher_one_brace.find()) {
					cleanDate = matcher_one_brace.group();
					cleanDate = removeOuterBrackets(cleanDate);
				} else {
					Matcher matcher_bc_date = BC_DATE_PATTERN.matcher(date);
					if (matcher_bc_date.find()) {
						cleanDate = null;
					} else {
						Matcher matcher_start_with_1_2 = FOUR_DIGIT_PATTERN_STARTING_WITH_1_2.matcher(date);
						if (matcher_start_with_1_2.find()) {
							cleanDate = matcher_start_with_1_2.group();
						} else {
							Matcher matcher_l_plus_three_digits = FOUR_DIGIT_PATTERN_OTHER_1.matcher(date);
							if (matcher_l_plus_three_digits.find()) {
								cleanDate = matcher_l_plus_three_digits.group().replaceAll("l", "1");
							} else {
								Matcher matcher_bracket_19_plus_two_digits = FOUR_DIGIT_PATTERN_OTHER_2.matcher(date);
								if (matcher_bracket_19_plus_two_digits.find()) {
									cleanDate = matcher_bracket_19_plus_two_digits.group().replaceAll("\\[", "").replaceAll("\\]", "");
								} else{
									Matcher matcher_three_digits_plus_unk = FOUR_DIGIT_PATTERN_OTHER_3.matcher(date);
									if (matcher_three_digits_plus_unk.find()) {
										cleanDate = matcher_three_digits_plus_unk.group().replaceAll("[-?]", "0");
									}
								}
							}
						}
					}
				}
			}
		}
		if (cleanDate != null) {
			Calendar calendar = Calendar.getInstance();
			SimpleDateFormat dateFormat = new SimpleDateFormat("yyyy");
			String thisYear = dateFormat.format(calendar.getTime());
			try {
				if (Integer.parseInt(cleanDate) > Integer.parseInt(thisYear) + 1) cleanDate = null;
			} catch (NumberFormatException nfe) {
				cleanDate = null;
			}
		}
		return cleanDate;
	}

	/**
	 * Remove single square bracket characters if they are the start and/or end
	 * chars (matched or unmatched) and are the only square bracket chars in the
	 * string.
	 */
	public static String removeOuterBrackets(String origStr) {
		if (origStr == null || origStr.length() == 0) return origStr;

		String result = origStr.trim();

		if (result.length() > 0) {
			boolean openBracketFirst = result.charAt(0) == '[';
			boolean closeBracketLast = result.endsWith("]");
			if (openBracketFirst && closeBracketLast && result.indexOf('[', 1) == -1 && result.lastIndexOf(']', result.length() - 2) == -1)
				// only square brackets are at beginning and end
				result = result.substring(1, result.length() - 1);
			else if (openBracketFirst && result.indexOf(']') == -1)
				// starts with '[' but no ']'; remove open bracket
				result = result.substring(1);
			else if (closeBracketLast && result.indexOf('[') == -1)
				// ends with ']' but no '['; remove close bracket
				result = result.substring(0, result.length() - 1);
		}

		return result.trim();
	}

	public Long processPublicUserLists() {
		Long numListsProcessed = 0l;
		try{
			PreparedStatement listsStmt;
			if (fullReindex){
				//Delete all lists from the index
				updateServer.deleteByQuery("recordtype:list");
				//Get a list of all public lists
				listsStmt = vufindConn.prepareStatement("SELECT user_list.id as id, deleted, public, title, description, user_list.created, dateUpdated, firstname, lastname, displayName, homeLocationId from user_list INNER JOIN user on user_id = user.id WHERE public = 1 AND deleted = 0");
			}else{
				//Get a list of all lists that are were changed since the last update
				listsStmt = vufindConn.prepareStatement("SELECT user_list.id as id, deleted, public, title, description, user_list.created, dateUpdated, firstname, lastname, displayName, homeLocationId from user_list INNER JOIN user on user_id = user.id WHERE dateUpdated > ?");
				listsStmt.setLong(1, lastReindexTime);
			}

			PreparedStatement getTitlesForListStmt = vufindConn.prepareStatement("SELECT groupedWorkPermanentId, notes from user_list_entry WHERE listId = ?");
			ResultSet allPublicListsRS = listsStmt.executeQuery();
			while (allPublicListsRS.next()){
				updateSolrForList(getTitlesForListStmt, allPublicListsRS);
				numListsProcessed++;
			}
			updateServer.commit();

		}catch (Exception e){
			logger.error("Error processing public lists", e);
		}
		logger.info("Finished processing public lists");
		return numListsProcessed;
	}

	private void updateSolrForList(PreparedStatement getTitlesForListStmt, ResultSet allPublicListsRS) throws SQLException, SolrServerException, IOException {
		UserListSolr userListSolr = new UserListSolr(this);
		Long listId = allPublicListsRS.getLong("id");

		int deleted = allPublicListsRS.getInt("deleted");
		int isPublic = allPublicListsRS.getInt("public");
		if (deleted == 1 || isPublic == 0){
			updateServer.deleteByQuery("id:list");
		}else{
			userListSolr.setId(listId);
			userListSolr.setTitle(allPublicListsRS.getString("title"));
			userListSolr.setDescription(allPublicListsRS.getString("description"));
			userListSolr.setCreated(allPublicListsRS.getLong("created"));

			String displayName = allPublicListsRS.getString("displayName");
			String firstName = allPublicListsRS.getString("firstname");
			String lastName = allPublicListsRS.getString("lastname");
			if (displayName != null && displayName.length() > 0){
				userListSolr.setAuthor(displayName);
			}else{
				if (firstName == null) firstName = "";
				if (lastName == null) lastName = "";
				String firstNameFirstChar = "";
				if (firstName.length() > 0){
					firstNameFirstChar = firstName.charAt(0) + ". ";
				}
				userListSolr.setAuthor(firstNameFirstChar + lastName);
			}

			//Get information about all of the list titles.
			getTitlesForListStmt.setLong(1, listId);
			ResultSet allTitlesRS = getTitlesForListStmt.executeQuery();
			while (allTitlesRS.next()){
				String groupedWorkId = allTitlesRS.getString("groupedWorkPermanentId");
				SolrQuery query = new SolrQuery();
				query.setQuery("id:" + groupedWorkId + " AND recordtype:grouped_work");
				query.setFields("title", "author");

				QueryResponse response = solrServer.query(query);
				SolrDocumentList results = response.getResults();
				//Should only ever get one response
				for (Object result : results) {
					SolrDocument curWork = (SolrDocument) result;
					userListSolr.addListTitle(groupedWorkId, curWork.getFieldValue("title"), curWork.getFieldValue("author"));
				}
			}

			updateServer.add(userListSolr.getSolrDocument(availableAtLocationBoostValue, ownedByLocationBoostValue));
		}
	}

	public void addWorkWithInvalidLiteraryForms(String id) {
		this.worksWithInvalidLiteraryForms.add(id);
	}

	public HashMap<String, String> getSubdomainMap() {
		return subdomainMap;
	}

	public HashMap<String, String> getLocationMap() {
		return locationMap;
	}

	public HashMap<String, String> getLibraryFacetMap() {
		return libraryFacetMap;
	}

	public HashMap<String, String> getLibraryOnlineFacetMap() {
		return libraryOnlineFacetMap;
	}

	public TreeSet<Scope> getScopes() {
		return this.scopes;
	}

	public ArrayList<String> getAllScopeNames(){
		return scopeNames;
	}

	/**
	 * A list of facets (library system display name + Online) which have access to Hoopla
	 *
	 * @return A list of facets
	 */
	public HashSet<String> getHooplaLocationFacets() {
		return hooplaLocationFacets;
	}

}
