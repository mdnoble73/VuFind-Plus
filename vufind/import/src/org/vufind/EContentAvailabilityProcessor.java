package org.vufind;

import org.apache.log4j.Logger;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.HashSet;
import java.util.LinkedHashSet;
import java.util.Set;

/**
 * Processes availability for an econtent record.
 *
 * User: Mark Noble
 * Date: 6/4/13
 * Time: 9:24 AM
 */
public class EContentAvailabilityProcessor {
	private ResultSet availabilityInfo;
	private Set<String> availableAt;
	private HashMap<String, HashSet<String>> availableAtBySystemOrLocation;
	private Set<String> availabilityToggleGlobal;
	private Set<String> buildings;
	private int numHoldings;
	private boolean hasAvailabilityInfo;
	private Logger logger;
	private MarcProcessor marcProcessor;

	public EContentAvailabilityProcessor(Logger logger, MarcProcessor marcProcessor, ResultSet availabilityInfo, Set<String> availableAt, HashMap<String, HashSet<String>> availableAtBySystemOrLocation, Set<String> availabilityToggleGlobal, Set<String> buildings, int numHoldings, boolean hasAvailabilityInfo) {
		this.logger = logger;
		this.marcProcessor = marcProcessor;
		this.availabilityInfo = availabilityInfo;
		this.availableAt = availableAt;
		this.availableAtBySystemOrLocation = availableAtBySystemOrLocation;
		this.availabilityToggleGlobal = availabilityToggleGlobal;
		this.buildings = buildings;
		this.numHoldings = numHoldings;
		this.hasAvailabilityInfo = hasAvailabilityInfo;
	}

	public Set<String> getAvailableAt() {
		return availableAt;
	}

	public Set<String> getBuildings() {
		return buildings;
	}

	public int getNumHoldings() {
		return numHoldings;
	}

	public boolean isHasAvailabilityInfo() {
		return hasAvailabilityInfo;
	}

	public EContentAvailabilityProcessor invoke() throws SQLException {
		if (!hasAvailabilityInfo) {
			logger.debug("Record has availability information");
			// This is the first availability line. We may have information from the items
			// which need to be cleared so we can use availability.
			buildings.clear();
			availableAt.clear();
			availableAtBySystemOrLocation.clear();
			availabilityToggleGlobal.clear();
			availabilityToggleGlobal.add("Entire Collection");
			//usableByPTypes.clear();
			hasAvailabilityInfo = true;
		}
		int copiesOwned = availabilityInfo.getInt("copiesOwned");
		int availableCopies = availabilityInfo.getInt("availableCopies");
		long libraryId = availabilityInfo.getLong("libraryId");
		logger.debug("Processing library " + libraryId + ", copiesOwned = " + copiesOwned + ", availableCopies = " + availableCopies);
			/*if (libraryId == -1L) {
				usableByPTypes.addAll(marcProcessor.getAllPTypes());
			} else {
				usableByPTypes.addAll(marcProcessor.getCompatiblePTypes("188", marcProcessor.getLibraryIndexingInfo(libraryId).getIlsCode()));
			}*/
		if (copiesOwned > 0) {
			availabilityToggleGlobal.add("Entire Collection");
			HashSet<String> libraryAvailability = new LinkedHashSet<String>();
			libraryAvailability.add("Entire Collection");
			if (availableCopies > 0){
				logger.debug("Title is available to library " + libraryId );
				libraryAvailability.add("Available Now");
				if (libraryId == -1L){
					availabilityToggleGlobal.add("Available Now");
					availableAt = addOnlineAvailability(availableAt);
				} else{
					LibraryIndexingInfo libraryIndexingInfo = marcProcessor.getLibraryIndexingInfo(libraryId);
					if (libraryIndexingInfo.getFacetLabel() != null){
						availableAt.add(libraryIndexingInfo.getFacetLabel() + " Online");
					}
				}
			}
			if (libraryId == -1L) {
				buildings = addOnlineAvailability(buildings);
				for (Long curLibraryId : marcProcessor.getLibraryIds()) {
					setAvailabilityToggleForLibrary(libraryAvailability, curLibraryId);
				}
			} else {
				buildings.add(marcProcessor.getLibrarySystemFacetForId(libraryId) + " Online");
				setAvailabilityToggleForLibrary(libraryAvailability, libraryId);
			}
		}
		numHoldings += copiesOwned;
		return this;
	}

	private HashSet<String> setAvailabilityToggleForLibrary(HashSet<String> libraryAvailability, Long curLibraryId) {
		LibraryIndexingInfo libraryIndexingInfo = marcProcessor.getLibraryIndexingInfo(curLibraryId);
		//Make sure we don't wipe out availability if there is already availability set.
		HashSet<String> existingAvailability = availableAtBySystemOrLocation.get(libraryIndexingInfo.getSubdomain());
		if (existingAvailability != null){
			if (existingAvailability.size() > libraryAvailability.size()){
				logger.debug("Using existing availability for library " + curLibraryId);
				libraryAvailability = existingAvailability;
			}
		}
		availableAtBySystemOrLocation.put(libraryIndexingInfo.getSubdomain(), libraryAvailability);
		// Since we don't have availability by location for online titles,
		// add the same availability to all locations
		for (LocationIndexingInfo curLocationInfo : libraryIndexingInfo.getLocations().values()) {
			availableAtBySystemOrLocation.put(curLocationInfo.getCode(), libraryAvailability);
		}
		return libraryAvailability;
	}

	private Set<String> addOnlineAvailability(Set<String> itemAvailability) {
		for (String libraryFacet : marcProcessor.getLibrarySystemFacets()) {
			itemAvailability.add(libraryFacet + " Online");
		}
		return itemAvailability;
	}
}
