package org.vufind;

import org.apache.log4j.Logger;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Subfield;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.*;
import java.util.regex.Pattern;

/**
 * Processes item records for use within solr
 * VuFind-Plus
 * User: Mark Noble
 * Date: 6/4/13
 * Time: 10:44 AM
 */
public class PrintItemSolrProcessor {
	private Set<String> librarySystems;
	private Set<String> locations;
	private Set<String> barcodes;
	private Set<String> iTypes;
	private HashMap<String, LinkedHashSet<String>> iTypesBySystem;
	private Set<String> locationCodes;
	private HashMap<String, LinkedHashSet<String>> locationsCodesBySystem;
	private Set<String> timeSinceAdded;
	private HashMap<String, LinkedHashSet<String>> timeSinceAddedBySystem;
	private HashMap<String, LinkedHashSet<String>> timeSinceAddedByLocation;
	private Set<String> availableAt;
	private Set<String> availabilityToggleGlobal;
	private HashMap<String, LinkedHashSet<String>> availableAtBySystemOrLocation;
	private LinkedHashSet<String> usableByPTypes;
	private boolean manuallySuppressed;
	private boolean allItemsSuppressed;
	private int popularity;
	private DataField itemField;
	private Logger logger;
	private MarcProcessor marcProcessor;
	Pattern digitPattern = Pattern.compile("^\\d+$");
	private static SimpleDateFormat dateAddedFormatter = new SimpleDateFormat("yyMMdd");
	private static Date indexDate = new Date();


	public PrintItemSolrProcessor(Logger logger, MarcProcessor marcProcessor, Set<String> librarySystems, Set<String> locations, Set<String> barcodes, Set<String> iTypes, HashMap<String, LinkedHashSet<String>> iTypesBySystem, Set<String> locationCodes, HashMap<String, LinkedHashSet<String>> locationsCodesBySystem, Set<String> timeSinceAdded, HashMap<String, LinkedHashSet<String>> timeSinceAddedBySystem, HashMap<String, LinkedHashSet<String>> timeSinceAddedByLocation, Set<String> availableAt, Set<String> availabilityToggleGlobal, HashMap<String, LinkedHashSet<String>> availableAtBySystemOrLocation, LinkedHashSet<String> usableByPTypes, boolean manuallySuppressed, boolean allItemsSuppressed, int popularity, DataField itemField) {
		this.logger = logger;
		this.marcProcessor = marcProcessor;
		this.librarySystems = librarySystems;
		this.locations = locations;
		this.barcodes = barcodes;
		this.iTypes = iTypes;
		this.iTypesBySystem = iTypesBySystem;
		this.locationCodes = locationCodes;
		this.locationsCodesBySystem = locationsCodesBySystem;
		this.timeSinceAdded = timeSinceAdded;
		this.timeSinceAddedBySystem = timeSinceAddedBySystem;
		this.timeSinceAddedByLocation = timeSinceAddedByLocation;
		this.availableAt = availableAt;
		this.availabilityToggleGlobal = availabilityToggleGlobal;
		this.availableAtBySystemOrLocation = availableAtBySystemOrLocation;
		this.usableByPTypes = usableByPTypes;
		this.manuallySuppressed = manuallySuppressed;
		this.allItemsSuppressed = allItemsSuppressed;
		this.popularity = popularity;
		this.itemField = itemField;
	}

	public Set<String> getTimeSinceAdded() {
		return timeSinceAdded;
	}

	public boolean isAllItemsSuppressed() {
		return allItemsSuppressed;
	}

	public int getPopularity() {
		return popularity;
	}

	public PrintItemSolrProcessor invoke() {
		boolean itemSuppressed = false;
		if (itemField.getSubfield('d') == null) {
			logger.debug("Did not find location code for item ");
		} else {
			String locationCode = itemField.getSubfield('d').getData().trim();
			logger.debug("Processing locationCode " + locationCode);
			// Figure out which location and library this item belongs to.
			LocationIndexingInfo locationIndexingInfo = marcProcessor.getLocationIndexingInfo(locationCode);
			LibraryIndexingInfo libraryIndexingInfo = null;
			if (locationIndexingInfo == null) {
				libraryIndexingInfo = marcProcessor.getLibraryIndexingInfoByCode(locationCode);
				if (libraryIndexingInfo != null){
					logger.debug("Warning, did not find location info for location " + locationCode);
				} else{
					logger.warn("Warning, did not find location info or library info for location " + locationCode);
				}
				if (locationCode.equalsIgnoreCase("zzzz")) {
					// logger.debug("suppressing item because location code is zzzz");
					itemSuppressed = true;
				}
			} else {
				libraryIndexingInfo = marcProcessor.getLibraryIndexingInfo(locationIndexingInfo.getLibraryId());
				if (locationIndexingInfo.isSuppressHoldings()){
					itemSuppressed = true;
				}
			}

			// Load availability (local, system, marmot)
			Subfield statusSubfield = itemField.getSubfield('g');
			Subfield dueDateField = itemField.getSubfield('m');
			Subfield icode2Subfield = itemField.getSubfield('o');
			boolean available = false;
			if (statusSubfield != null) {
				String status = statusSubfield.getData();
				String dueDate = dueDateField == null ? "" : dueDateField.getData()
						.trim();
				String availableStatus = "-dowju";
				if (availableStatus.indexOf(status.charAt(0)) >= 0) {
					if (dueDate.length() == 0) {
						if (icode2Subfield != null) {
							String icode2 = icode2Subfield.getData().toLowerCase().trim();
							if (icode2.equals("n") || icode2.equals("x")) {
								// logger.debug("Suppressing item because icode2 is " +
								// icode2);
								itemSuppressed = true;
							} else {
								available = true;
							}
						}
					}
				}
			}

			if (!itemSuppressed) {
				// Map library system (institution)
				if (libraryIndexingInfo != null) {
					librarySystems.add(libraryIndexingInfo.getFacetLabel());
				}

				// Map location (building)
				if (locationIndexingInfo != null) {
					locations.add(locationIndexingInfo.getFacetLabel());
				}
				// Check for extra locations
				LinkedHashSet<String> extraLocations = marcProcessor.getExtraLocations(locationCode);
				if (extraLocations.size() > 0) {
					locations.addAll(extraLocations);
				}

				// Barcodes
				@SuppressWarnings("unchecked")
				List<Subfield> barcodeFields = itemField.getSubfields('b');
				for (Subfield curSubfield : barcodeFields) {
					String barcode = curSubfield.getData();
					if (digitPattern.matcher(barcode).matches()) {
						barcodes.add(barcode);
					}
				}

				//Get number of times the title has been checked out
				Subfield numCheckoutsField = itemField.getSubfield('h');
				if (numCheckoutsField != null){
					int numCheckouts = Integer.parseInt(numCheckoutsField.getData());
					popularity += numCheckouts;
				}

				// Map iTypes
				Subfield iTypeSubfield = itemField.getSubfield('j');
				String iType = "0";
				if (iTypeSubfield != null) {
					iType = processItemIcode(iTypes, iTypesBySystem, libraryIndexingInfo, iTypeSubfield);
				}

				// Get Location Codes
				locationCodes.add(locationCode);
				// Get Location Codes By System
				if (libraryIndexingInfo != null) {
					LinkedHashSet<String> detailedLocationVals = locationsCodesBySystem.get(libraryIndexingInfo.getSubdomain());
					if (detailedLocationVals == null) {
						detailedLocationVals = new LinkedHashSet<String>();
						locationsCodesBySystem.put(libraryIndexingInfo.getSubdomain(), detailedLocationVals);
					}
					detailedLocationVals.add(locationCode);
				}

				// Get Location Codes By Location
				if (locationIndexingInfo != null) {
					LinkedHashSet<String> detailedLocationVals = locationsCodesBySystem.get(locationIndexingInfo.getCode());
					if (detailedLocationVals == null) {
						detailedLocationVals = new LinkedHashSet<String>();
						locationsCodesBySystem.put(locationIndexingInfo.getCode(), detailedLocationVals);
					}
					detailedLocationVals.add(locationCode);
				}

				// Map time since added (library & location)
				Subfield dateAddedField = itemField.getSubfield('k');
				if (dateAddedField != null) {
					timeSinceAdded = processItemDateAdded(timeSinceAdded, timeSinceAddedBySystem, timeSinceAddedByLocation, locationIndexingInfo, libraryIndexingInfo, dateAddedField);
				}

				// Add availability
				if (!itemSuppressed && !manuallySuppressed) {
					processItemAvailability(availableAt, availabilityToggleGlobal, availableAtBySystemOrLocation, usableByPTypes, locationCode, locationIndexingInfo, libraryIndexingInfo, available, iType);
				}
			} else {
				logger.debug("Item/Bib is suppressed.");
			}
		}
		if (!itemSuppressed) {
			allItemsSuppressed = false;
		}
		return this;
	}

	private String processItemIcode(Set<String> iTypes, HashMap<String, LinkedHashSet<String>> iTypesBySystem, LibraryIndexingInfo libraryIndexingInfo, Subfield iTypeSubfield) {
		String iType;
		iType = iTypeSubfield.getData();
		iTypes.add(iType);
		if (libraryIndexingInfo != null) {
			LinkedHashSet<String> iTypesBySystemVals;
			if (iTypesBySystem
					.containsKey(libraryIndexingInfo.getSubdomain())) {
				iTypesBySystemVals = iTypesBySystem.get(libraryIndexingInfo.getSubdomain());
			} else {
				iTypesBySystemVals = new LinkedHashSet<String>();
				iTypesBySystem.put(libraryIndexingInfo.getSubdomain(), iTypesBySystemVals);
			}

			iTypesBySystemVals.add(iType);
		}
		return iType;
	}

	private Set<String> processItemDateAdded(Set<String> timeSinceAdded, HashMap<String, LinkedHashSet<String>> timeSinceAddedBySystem, HashMap<String, LinkedHashSet<String>> timeSinceAddedByLocation, LocationIndexingInfo locationIndexingInfo, LibraryIndexingInfo libraryIndexingInfo, Subfield dateAddedField) {
		String dateAddedStr = dateAddedField.getData();
		try {
			Date dateAdded = dateAddedFormatter.parse(dateAddedStr);
			LinkedHashSet<String> itemTimeSinceAdded = getTimeSinceAddedForDate(dateAdded);
			if (itemTimeSinceAdded.size() > timeSinceAdded.size()) {
				timeSinceAdded = itemTimeSinceAdded;
			}
			// Check library specific time since added
			if (libraryIndexingInfo != null) {
				LinkedHashSet<String> timeSinceAddedBySystemVals = timeSinceAddedBySystem
						.get(libraryIndexingInfo.getSubdomain());
				if (timeSinceAddedBySystemVals == null
						|| itemTimeSinceAdded.size() > timeSinceAddedBySystemVals
						.size()) {
					timeSinceAddedBySystem.put(
							libraryIndexingInfo.getSubdomain(), itemTimeSinceAdded);
				}
			}
			// Check location specific time since added
			if (locationIndexingInfo != null) {
				LinkedHashSet<String> timeSinceAddedByLocationVals = timeSinceAddedByLocation
						.get(locationIndexingInfo.getCode());
				if (timeSinceAddedByLocationVals == null
						|| itemTimeSinceAdded.size() > timeSinceAddedByLocationVals
						.size()) {
					timeSinceAddedByLocation.put(locationIndexingInfo.getCode(),
							itemTimeSinceAdded);
				}
			}
		} catch (ParseException e) {
			logger.error("Error processing date added", e);
		}
		return timeSinceAdded;
	}

	private void processItemAvailability(Set<String> availableAt, Set<String> availabilityToggleGlobal, HashMap<String, LinkedHashSet<String>> availableAtBySystemOrLocation, LinkedHashSet<String> usableByPTypes, String locationCode, LocationIndexingInfo locationIndexingInfo, LibraryIndexingInfo libraryIndexingInfo, boolean available, String iType) {
		if (available) {
			availabilityToggleGlobal.add("Available Now");
		}
		// logger.debug("item is available at " + locationCode);
		// Loop through all libraries
		for (String curSubdomain : marcProcessor.getLibrarySubdomains()) {
			LinkedHashSet<String> existingAvailability = availableAtBySystemOrLocation
					.get(curSubdomain);
			if (existingAvailability != null
					&& existingAvailability.size() == 2) {
				continue;
			}
			LinkedHashSet<String> libraryAvailability = new LinkedHashSet<String>();
			libraryAvailability.add("Entire Collection");
			if (available) {
				if (libraryIndexingInfo != null
						&& libraryIndexingInfo.getSubdomain().equalsIgnoreCase(
						curSubdomain)) {
					libraryAvailability.add("Available Now");
				}
			}
			if (existingAvailability == null
					|| libraryAvailability.size() > existingAvailability.size()) {
				availableAtBySystemOrLocation.put(curSubdomain,
						libraryAvailability);
			}
		}

		// Loop through all locations
		for (String curCode : marcProcessor.getLocationCodes()) {
			LinkedHashSet<String> existingAvailability = availableAtBySystemOrLocation
					.get(curCode);
			if (existingAvailability != null
					&& existingAvailability.size() == 2) {
				// Can't get better availability
				continue;
			}
			LinkedHashSet<String> locationAvailability = new LinkedHashSet<String>();
			locationAvailability.add("Entire Collection");
			if (available) {
				if (locationIndexingInfo != null
						&& locationIndexingInfo.getCode().equalsIgnoreCase(curCode)) {
					locationAvailability.add("Available Now");
					availableAt.add(locationIndexingInfo.getFacetLabel());
				}
			}
			if (existingAvailability == null
					|| locationAvailability.size() > existingAvailability.size()) {
				availableAtBySystemOrLocation.put(curCode, locationAvailability);
			}
		}

		LinkedHashSet<String> itemUsableByPTypes = marcProcessor
				.getCompatiblePTypes(iType, locationCode);
		usableByPTypes.addAll(itemUsableByPTypes);
	}

	public LinkedHashSet<String> getTimeSinceAddedForDate(Date curDate) {
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
}
