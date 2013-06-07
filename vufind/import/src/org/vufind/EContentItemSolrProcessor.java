package org.vufind;

import org.apache.log4j.Logger;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.HashSet;
import java.util.LinkedHashSet;
import java.util.Set;

/**
 * Processes econtent items to get the necessary data for Solr.
 * VuFind-Plus - Reindexer
 * User: Mark Noble
 * Date: 6/4/13
 * Time: 9:39 AM
 */
public class EContentItemSolrProcessor {
	private ResultSet itemInfo;
	private Set<String> formats;
	private int numItems;
	private Set<String> availableAt;
	private HashMap<String, HashSet<String>> availableAtBySystemOrLocation;
	private Set<String> availabilityToggleGlobal;
	private Set<String> buildings;
	private Logger logger;
	private MarcProcessor marcProcessor;

	public EContentItemSolrProcessor(Logger logger, MarcProcessor marcProcessor, ResultSet itemInfo, Set<String> formats, int numItems, Set<String> availableAt, HashMap<String, HashSet<String>> availableAtBySystemOrLocation, Set<String> availabilityToggleGlobal, Set<String> buildings) {
		this.logger = logger;
		this.marcProcessor = marcProcessor;
		this.itemInfo = itemInfo;
		this.formats = formats;
		this.numItems = numItems;
		this.availableAt = availableAt;
		this.availableAtBySystemOrLocation = availableAtBySystemOrLocation;
		this.availabilityToggleGlobal = availabilityToggleGlobal;
		this.buildings = buildings;
	}

	public int getNumItems() {
		return numItems;
	}

	public Set<String> getAvailableAt() {
		return availableAt;
	}

	public Set<String> getBuildings() {
		return buildings;
	}

	public EContentItemSolrProcessor invoke() throws SQLException {
		String item_type = itemInfo.getString("item_type");
		String externalFormat = itemInfo.getString("externalFormat");
		long libraryId = itemInfo.getLong("libraryId");
		numItems++;
		if (externalFormat != null && externalFormat.length() > 0) {
			formats.add(externalFormat.replaceAll("\\s", "_"));
		} else {
			formats.add(item_type);
		}
		if (libraryId == -1L) {
			//usableByPTypes.addAll(marcProcessor.getAllPTypes());
			//usableByPTypes.add("all");
			availabilityToggleGlobal.add("Available Now");
			// Loop through all libraries and mark this title as available
			for (Long curLibraryId : marcProcessor.getLibraryIds()) {
				LibraryIndexingInfo libraryIndexingInfo = marcProcessor.getLibraryIndexingInfo(curLibraryId);
				LinkedHashSet<String> libraryAvailability = new LinkedHashSet<String>();
				libraryAvailability.add("Entire Collection");
				// TODO: determine if acs and single use titles are actually available
				libraryAvailability.add("Available Now");
				availableAtBySystemOrLocation.put(libraryIndexingInfo.getSubdomain(), libraryAvailability);
				// Since we don't have availability by location for online titles, add
				// the same availability to all locations
					/*for (LocationIndexingInfo curLocationInfo : libraryIndexingInfo.getLocations().values()) {
						availableAtBySystemOrLocation.put(curLocationInfo.getCode(), libraryAvailability);
						availableAt.add(curLocationInfo.getFacetLabel());
					} */
			}

			//buildings.add("Digital Collection");
			buildings = addOnlineAvailability( buildings);
			//availableAt.add("Digital Collection");
			availableAt = addOnlineAvailability( availableAt);
			availabilityToggleGlobal.add("Available Now");
		} else {
			//usableByPTypes.addAll(marcProcessor.getCompatiblePTypes("188", marcProcessor.getLibraryIndexingInfo(libraryId).getIlsCode()));
			availableAt.add(marcProcessor.getLibrarySystemFacetForId(libraryId) + " Online");
			buildings.add(marcProcessor.getLibrarySystemFacetForId(libraryId) + " Online");
			logger.debug(marcProcessor.getLibrarySystemFacetForId(libraryId) + " Online");
			LibraryIndexingInfo libraryIndexingInfo = marcProcessor.getLibraryIndexingInfo(libraryId);
			LinkedHashSet<String> libraryAvailability = new LinkedHashSet<String>();
			libraryAvailability.add("Entire Collection");
			// TODO: determine if acs and single use titles are actually available
			libraryAvailability.add("Available Now");
			availableAtBySystemOrLocation.put(libraryIndexingInfo.getSubdomain(), libraryAvailability);
			// Since we don't have availability by location for online titles, add
			// the same availability to all locations
				/*for (LocationIndexingInfo curLocationInfo : libraryIndexingInfo.getLocations().values()) {
					availableAtBySystemOrLocation.put(curLocationInfo.getCode(), libraryAvailability);
				}*/
		}
		return this;
	}

	private Set<String> addOnlineAvailability(Set<String> itemAvailability) {
		for (String libraryFacet : marcProcessor.getLibrarySystemFacets()) {
			itemAvailability.add(libraryFacet + " Online");
		}
		return itemAvailability;
	}
}
