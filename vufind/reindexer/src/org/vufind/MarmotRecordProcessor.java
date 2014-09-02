package org.vufind;

import au.com.bytecode.opencsv.CSVReader;
import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.sql.Connection;
import java.util.*;

/**
 * ILS Indexing with customizations specific to Marmot
 * VuFind-Plus
 * User: Mark Noble
 * Date: 2/21/14
 * Time: 3:00 PM
 */
public class MarmotRecordProcessor extends IlsRecordProcessor {
	private String marcExportPath;
	private static HashMap<String, ArrayList<SierraOrderInformation>> orderRecordsByBib = new HashMap<String, ArrayList<SierraOrderInformation>>();

	public MarmotRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Ini configIni, Logger logger) {
		super(indexer, vufindConn, configIni, logger);

		marcExportPath = configIni.get("Reindex", "marcPath");
		loadOrderRecords();
	}

	private void loadOrderRecords() {
		File activeOrdersFile = new File(marcExportPath + "/active_orders.csv");
		if (activeOrdersFile.exists()){
			try {
				CSVReader reader = new CSVReader(new FileReader(activeOrdersFile));
				String[] curLine = reader.readNext();
				//Skip the header
				curLine = reader.readNext();
				while (curLine != null){
					SierraOrderInformation orderInformation = new SierraOrderInformation();
					orderInformation.setBibRecordNumber(".b" + curLine[0] + getCheckDigit(curLine[0]));
					orderInformation.setOrderNumber(".o" + curLine[1] + getCheckDigit(curLine[1]));
					orderInformation.setAccountingUnit(Long.parseLong(curLine[2]));
					orderInformation.setStatusCode(curLine[3]);
					ArrayList<SierraOrderInformation> orderRecordsForBib = orderRecordsByBib.get(orderInformation.getBibRecordNumber());
					if (orderRecordsForBib == null){
						orderRecordsForBib = new ArrayList<SierraOrderInformation>();
						orderRecordsByBib.put(orderInformation.getBibRecordNumber(), orderRecordsForBib);
					}
					orderRecordsForBib.add(orderInformation);
					curLine = reader.readNext();
				}
				reader.close();
			}catch (IOException e){
				logger.error("Error loading order records", e);
			}
		}
	}

	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		super.updateGroupedWorkSolrDataBasedOnMarc(groupedWork, record, identifier);

		//Check to see if we have order records for the bib.  If so, add ownership for those records.
		/*if (orderRecordsByBib.containsKey(identifier)){
			ArrayList<SierraOrderInformation> orderInformationForBib = orderRecordsByBib.get(identifier);
			//We have a match, determine which scopes to add the record to
			for (ScopedWorkDetails scope : groupedWork.getScopedWorkDetails().values()){
				for (SierraOrderInformation orderInformation : orderInformationForBib) {
					if (scope.getScope().getAccountingUnit() == orderInformation.getAccountingUnit()) {
						//TODO: Figure out what needs to be done to add the order to the scope.
					}
				}
			}

		}*/
	}

	protected void loadAdditionalOwnershipInformation(GroupedWorkSolr groupedWork, PrintIlsItem printItem){
		String locationCode = printItem.getLocation();
		if (locationCode.length() > 0 && !locationCode.equalsIgnoreCase("none")){
			groupedWork.addCollectionGroup(indexer.translateValue("collection_group", locationCode));
			if (additionalCollections != null){
				for (String additionalCollection : additionalCollections){
					groupedWork.addAdditionalCollection(additionalCollection, indexer.translateValue("collection_" + additionalCollection, locationCode));
				}
			}
			String translatedDetailedLocation = indexer.translateValue("detailed_location", locationCode);
			for (ScopedWorkDetails curScope: groupedWork.getScopedWorkDetails().values()){
				if (curScope.getScope().isLocationCodeIncludedDirectly(locationCode)) {
					curScope.addDetailedLocation(translatedDetailedLocation);
				}
			}
			for (LocalizedWorkDetails localizedWorkDetails : groupedWork.getLocalizedWorkDetails().values()){
				if (localizedWorkDetails.getLocalizationInfo().isLocationCodeIncluded(locationCode)){
					localizedWorkDetails.addDetailedLocation(translatedDetailedLocation);
				}
			}
		}
	}

	protected void loadLocalCallNumbers(GroupedWorkSolr groupedWork, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems) {
		for (PrintIlsItem curItem : printItems){
			String locationCode = curItem.getLocation();
			if (locationCode != null){
				String callNumberPrestamp = curItem.getCallNumberPreStamp() == null ? "" : curItem.getCallNumberPreStamp();
				String callNumber = curItem.getCallNumber() == null ? "" : curItem.getCallNumber();
				String callNumberCutter = curItem.getCallNumberCutter() == null ? "" : curItem.getCallNumberCutter();
				String fullCallNumber = callNumberPrestamp + callNumber + callNumberCutter;
				String sortableCallNumber = callNumber + callNumberCutter;
				if (fullCallNumber.length() > 0){
					ArrayList<String> subdomainsForLocation = getLibrarySubdomainsForLocationCode(locationCode);
					ArrayList<String> relatedLocationCodesForLocation = getRelatedLocationCodesForLocationCode(locationCode);
					groupedWork.addLocalCallNumber(fullCallNumber, subdomainsForLocation, relatedLocationCodesForLocation);
					groupedWork.addCallNumberSort(sortableCallNumber, subdomainsForLocation, relatedLocationCodesForLocation);
				}
			}
		}
	}

	protected List<PrintIlsItem> getUnsuppressedPrintItems(Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<PrintIlsItem> unsuppressedItemRecords = new ArrayList<PrintIlsItem>();
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				if (useEContentSubfield){
					if (itemField.getSubfield(eContentSubfieldIndicator) != null){
						String eContentData = itemField.getSubfield(eContentSubfieldIndicator).getData();
						if (eContentData.indexOf(':') >= 0){
							isEContent = true;
							String[] eContentFields = eContentData.split(":");
							String sourceType = eContentFields[0].toLowerCase().trim();
							if (sourceType.equals("overdrive")){
								isOverDrive = true;
							}
						}
					}
				}
				if (!isOverDrive && !isEContent){
					PrintIlsItem printIlsRecord = getPrintIlsRecord(itemField);
					if (printIlsRecord != null) {
						unsuppressedItemRecords.add(printIlsRecord);
					}
				}
			}
		}
		return unsuppressedItemRecords;
	}

	protected List<EContentIlsItem> getUnsuppressedEContentItems(String identifier, Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<EContentIlsItem> unsuppressedEcontentRecords = new ArrayList<EContentIlsItem>();
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				if (useEContentSubfield){
					if (itemField.getSubfield(eContentSubfieldIndicator) != null){
						String eContentData = itemField.getSubfield(eContentSubfieldIndicator).getData();
						if (eContentData.indexOf(':') >= 0){
							isEContent = true;
							String[] eContentFields = eContentData.split(":");
							String sourceType = eContentFields[0].toLowerCase().trim();
							if (sourceType.equals("overdrive")){
								isOverDrive = true;
							}
						}else{
							if (itemField.getSubfield(eContentSubfieldIndicator).getData().trim().equalsIgnoreCase("overdrive")){
								isOverDrive = true;
							}
						}
					}
				}
				if (!isOverDrive && isEContent){
					unsuppressedEcontentRecords.add(getEContentIlsRecord(identifier, itemField));
				}
			}
		}
		return unsuppressedEcontentRecords;
	}

	/**
	 * Do item based determination of econtent sources, and protection types.
	 * For Marmot also load availability and ownership information for eContent since it is so similar.
	 *
	 * @param groupedWork The Work to load sources and protection types for
	 * @param itemRecords The item records related to the current Marc Record being indexed
	 */
	protected void loadEContentSourcesAndProtectionTypes(GroupedWorkSolr groupedWork, List<EContentIlsItem> itemRecords) {
		for (EContentIlsItem curItem : itemRecords){
			//Check subfield w to get the source
			String locationCode = curItem.getLocation();
			HashSet<String> sources = new HashSet<String>();
			HashSet<String> protectionTypes = new HashSet<String>();

			String eContentSource = curItem.getSource();
			String protectionType = curItem.getProtectionType();

			sources.add(eContentSource);

			boolean available = false;
			if (protectionType.equals("external")){
				protectionTypes.add("Externally Validated");
				available = true;
			}else if (protectionType.equals("public domain") || protectionType.equals("free")){
				protectionTypes.add("Public Domain");
				available = true;
			}else if (protectionType.equals("acs") || protectionType.equals("drm")){
				protectionTypes.add("Limited Access");
				//TODO: Determine availability based on if it is checked out in the database
				available = true;
			}

			boolean shareWithAll = false;
			boolean shareWithLibrary = false;
			String sharing = curItem.getSharing();
			if (sharing.equalsIgnoreCase("shared")){
				shareWithAll = true;
			}else if (sharing.equalsIgnoreCase("library")){
				shareWithLibrary = true;
			}

			if (locationCode != null && locationCode.equalsIgnoreCase("mdl")){
				//Share with everyone
				shareWithAll = true;
			}
			HashSet<String> owningLibraries = new HashSet<String>();
			HashSet<String> availableLibraries = new HashSet<String>();
			HashSet<String> owningSubdomainsAndLocations = new HashSet<String>();
			HashSet<String> availableSubdomainsAndLocations = new HashSet<String>();
			if (shareWithAll){
				//When we share with everyone, we only really want to share with people that want it
				groupedWork.addEContentSources(sources, curItem.getValidSubdomains() , curItem.getValidLocations());
				groupedWork.addEContentProtectionTypes(protectionTypes, curItem.getValidSubdomains() , curItem.getValidLocations());
				groupedWork.addCompatiblePTypes(curItem.getCompatiblePTypes());
				//owningLibraries.add("Shared Digital Collection");
				owningLibraries.addAll(curItem.getValidLibraryFacets());
				owningSubdomainsAndLocations.addAll(indexer.getSubdomainMap().values());
				owningSubdomainsAndLocations.addAll(indexer.getLocationMap().values());
				if (available){
					availableLibraries.addAll(indexer.getLibraryFacetMap().values());
					availableSubdomainsAndLocations.addAll(indexer.getSubdomainMap().values());
					availableSubdomainsAndLocations.addAll(indexer.getLocationMap().values());
				}
			}else if (shareWithLibrary){
				ArrayList<String> validSubdomains = getLibrarySubdomainsForLocationCode(locationCode);
				ArrayList<String> validLocationCodes = getRelatedLocationCodesForLocationCode(locationCode);
				groupedWork.addEContentSources(sources, validSubdomains, validLocationCodes);
				groupedWork.addEContentProtectionTypes(protectionTypes, validSubdomains, validLocationCodes);
				for (String curLocation : pTypesByLibrary.keySet()){
					if (locationCode.startsWith(curLocation)){
						groupedWork.addCompatiblePTypes(pTypesByLibrary.get(curLocation));
					}
				}
				owningLibraries.addAll(getLibraryOnlineFacetsForLocationCode(locationCode));
				if (available){
					availableLibraries.addAll(getLibraryOnlineFacetsForLocationCode(locationCode));
					availableSubdomainsAndLocations.addAll(validSubdomains);
					availableSubdomainsAndLocations.addAll(validLocationCodes);
				}
			}else{
				//Share with just the individual location
				groupedWork.addEContentSources(sources, new HashSet<String>(), getRelatedLocationCodesForLocationCode(locationCode));
				groupedWork.addEContentProtectionTypes(protectionTypes, new HashSet<String>(), getRelatedLocationCodesForLocationCode(locationCode));
				//TODO: Add correct owning and available locations
				for (String curLocation : pTypesByLibrary.keySet()){
					if (locationCode.startsWith(curLocation)){
						groupedWork.addCompatiblePTypes(pTypesByLibrary.get(curLocation));
					}
				}
			}
			groupedWork.addOwningLibraries(owningLibraries);
			groupedWork.addOwningLocationCodesAndSubdomains(owningSubdomainsAndLocations);
			groupedWork.addAvailableLocations(availableLibraries, availableSubdomainsAndLocations);
		}//Has subfield w
	}

	protected void loadUsability(GroupedWorkSolr groupedWork, List<PrintIlsItem> printItems, List<EContentIlsItem> econtentItems) {
		super.loadUsability(groupedWork, printItems, econtentItems);

		for (EContentIlsItem itemField : econtentItems){
			String locationCode = itemField.getLocation();
			boolean shareWithAll = false;
			boolean shareWithLibrary = false;
			boolean shareWithSome = false;
			String sharing = itemField.getSharing();
			if (sharing.equals("shared")){
				if (locationCode.startsWith("mdl")){
					shareWithSome = true;
				}else{
					shareWithAll = true;
				}
			}else if (sharing.equalsIgnoreCase("library")){
				shareWithLibrary = true;
			}

			if (shareWithAll){
				groupedWork.addCompatiblePTypes(allPTypes);
				break;
			}else if (shareWithLibrary) {
				if (locationCode == null) {
					logger.error("Location code was null for item, skipping to next");
				} else {
					for (String curLocation : pTypesByLibrary.keySet()) {
						if (locationCode.startsWith(curLocation)) {
							groupedWork.addCompatiblePTypes(pTypesByLibrary.get(curLocation));
						}
					}
				}
			}else if (shareWithSome){
				if (pTypesForSpecialLocationCodes.containsKey(locationCode)) {
					groupedWork.addCompatiblePTypes(pTypesForSpecialLocationCodes.get(locationCode));
				}
			} else{
				logger.warn("Could not determine usability, was not shared with library or everyone");
			}
		}
	}

	/**
	 * Determine Record Format(s)
	 */
	public void loadPrintFormatInformation(IlsRecord ilsRecord, Record record){
		if (ilsRecord.getRelatedItems().size() > 0){
			Set<String> printFormats = new LinkedHashSet<String>();

			String leader = record.getLeader().toString();
			char leaderBit;
			ControlField fixedField = (ControlField) record.getVariableField("008");

			// check for music recordings quickly so we can figure out if it is music
			// for category (need to do here since checking what is on the Compact
			// Disc/Phonograph, etc is difficult).
			if (leader.length() >= 6) {
				leaderBit = leader.charAt(6);
				switch (Character.toUpperCase(leaderBit)) {
					case 'J':
						printFormats.add("MusicRecording");
						break;
				}
			}
			getFormatFromPublicationInfo(record, printFormats);
			getFormatFromNotes(record, printFormats);
			getFormatFromEdition(record, printFormats);
			getFormatFromPhysicalDescription(record, printFormats);
			getFormatFromSubjects(record, printFormats);
			getFormatFrom007(record, printFormats);
			getFormatFromTitle(record, printFormats);
			getFormatFromLeader(printFormats, leader, fixedField);

			if (printFormats.size() == 0){
				logger.debug("Did not get any formats for print record " + ilsRecord.getRecordId());
			}

			filterPrintFormats(printFormats);

			HashSet<String> translatedFormats = indexer.translateCollection("format", printFormats);
			HashSet<String> translatedFormatCategories = indexer.translateCollection("format_category", printFormats);
			ilsRecord.addFormats(translatedFormats);
			ilsRecord.addFormatCategories(translatedFormatCategories);
			Long formatBoost = 0L;
			HashSet<String> formatBoosts = indexer.translateCollection("format_boost", printFormats);
			for (String tmpFormatBoost : formatBoosts){
				Long tmpFormatBoostLong = Long.parseLong(tmpFormatBoost);
				if (tmpFormatBoostLong > formatBoost){
					formatBoost = tmpFormatBoostLong;
				}
			}
			ilsRecord.setFormatBoost(formatBoost);
		}
	}

	private void filterPrintFormats(Set<String> printFormats) {
		if (printFormats.contains("Video") && printFormats.contains("DVD")){
			printFormats.remove("Video");
		}else if (printFormats.contains("SoundDisc") && printFormats.contains("SoundRecording")){
			printFormats.remove("SoundRecording");
		}else if (printFormats.contains("SoundCassette") && printFormats.contains("SoundRecording")){
			printFormats.remove("SoundRecording");
		}else if (printFormats.contains("Playaway") && printFormats.contains("SoundRecording")){
			printFormats.remove("SoundRecording");
		}else if (printFormats.contains("Book") && printFormats.contains("LargePrint")){
			printFormats.remove("Book");
		}else if (printFormats.contains("Book") && printFormats.contains("Manuscript")){
			printFormats.remove("Book");
		}else if (printFormats.size() > 1){
			return;
		}
	}

	private void getFormatFromTitle(Record record, Set<String> printFormats) {
		String titleMedium = getFirstFieldVal(record, "245h");
		if (titleMedium != null){
			titleMedium = titleMedium.toLowerCase();
			if (titleMedium.contains("sound recording-cass")){
				printFormats.add("SoundCassette");
			}else if (titleMedium.contains("large print")){
				printFormats.add("LargePrint");
			}
		}
		String titlePart = getFirstFieldVal(record, "245p");
		if (titlePart != null){
			titlePart = titlePart.toLowerCase();
			if (titlePart.contains("sound recording-cass")){
				printFormats.add("SoundCassette");
			}else if (titlePart.contains("large print")){
				printFormats.add("LargePrint");
			}
		}
	}

	protected void loadEContentFormatInformation(IlsRecord econtentRecord, EContentIlsItem econtentItem) {
		String locationCode = econtentItem.getLocation();
		String protectionType = econtentItem.getProtectionType();
		String sharing = econtentItem.getSharing();
		if (protectionType.equals("acs") || protectionType.equals("drm") || protectionType.equals("public domain") || protectionType.equals("free")){
			String filename = econtentItem.getFilename();
			if (filename == null) {
				//Did not get a filename, use the iType as a placeholder
				String iType = econtentItem.getiType();
				if (iType != null){
					String translatedFormat = indexer.translateValue("econtent_itype_format", iType);
					String translatedFormatCategory = indexer.translateValue("econtent_itype_format_category", iType);
					String translatedFormatBoost = indexer.translateValue("econtent_itype_format_boost", iType);
					econtentRecord.setFormatInformation(translatedFormat, translatedFormatCategory, translatedFormatBoost);
				}else{
					logger.warn("Did not get a filename or itype for " + econtentRecord.getRecordId());
				}
			}else if (filename.indexOf('.') > 0) {
				String fileExtension = filename.substring(filename.lastIndexOf('.') + 1);
				String translatedFormat = indexer.translateValue("format", fileExtension);
				String translatedFormatCategory = indexer.translateValue("format_category", fileExtension);
				String translatedFormatBoost = indexer.translateValue("format_boost", fileExtension);
				econtentRecord.setFormatInformation(translatedFormat, translatedFormatCategory, translatedFormatBoost);
			} else {
				//For now we know these are folders of MP3 files
				//TODO: Probably should actually open the folder to make sure that it contains MP3 files
				String translatedFormat = indexer.translateValue("format", "mp3");
				String translatedFormatCategory = indexer.translateValue("format_category", "mp3");
				String translatedFormatBoost = indexer.translateValue("format_boost", "mp3");
				econtentRecord.setFormatInformation(translatedFormat, translatedFormatCategory, translatedFormatBoost);
			}
		}else if (protectionType.equals("external")){
			String iType = econtentItem.getiType();
			if (iType != null){
				String translatedFormat = indexer.translateValue("econtent_itype_format", iType);
				String translatedFormatCategory = indexer.translateValue("econtent_itype_format_category", iType);
				String translatedFormatBoost = indexer.translateValue("econtent_itype_format_boost", iType);
				econtentRecord.setFormatInformation(translatedFormat, translatedFormatCategory, translatedFormatBoost);
			}else{
				logger.warn("Did not get a iType for external eContent " + econtentRecord.getRecordId());
			}
		}else{
			logger.warn("Unknown protection type " + protectionType);
		}
	}

	private void getFormatFromPublicationInfo(Record record, Set<String> result) {
		// check for playaway in 260|b
		DataField sysDetailsNote = (DataField) record.getVariableField("260");
		if (sysDetailsNote != null) {
			if (sysDetailsNote.getSubfield('b') != null) {
				String sysDetailsValue = sysDetailsNote.getSubfield('b').getData()
						.toLowerCase();
				if (sysDetailsValue.contains("playaway")) {
					result.add("Playaway");
				}
			}
		}
	}

	private void getFormatFromEdition(Record record, Set<String> result) {
		// Check for large print book (large format in 650, 300, or 250 fields)
		// Check for blu-ray in 300 fields
		DataField edition = (DataField) record.getVariableField("250");
		if (edition != null) {
			if (edition.getSubfield('a') != null) {
				String editionData = edition.getSubfield('a').getData().toLowerCase();
				if (editionData.contains("large type") || editionData.contains("large print")) {
					result.add("LargePrint");
				}
			}
		}
	}

	private void getFormatFromPhysicalDescription(Record record, Set<String> result) {
		@SuppressWarnings("unchecked")
		List<DataField> physicalDescription = getDataFields(record, "300");
		if (physicalDescription != null) {
			Iterator<DataField> fieldsIter = physicalDescription.iterator();
			DataField field;
			while (fieldsIter.hasNext()) {
				field = fieldsIter.next();
				@SuppressWarnings("unchecked")
				List<Subfield> subFields = field.getSubfields();
				for (Subfield subfield : subFields) {
					String physicalDescriptionData = subfield.getData().toLowerCase();
					if (physicalDescriptionData.contains("large type") || physicalDescriptionData.contains("large print")) {
						result.add("LargePrint");
					} else if (physicalDescriptionData.contains("bluray") || physicalDescriptionData.contains("blu-ray")) {
						result.add("Blu-ray");
					} else if (physicalDescriptionData.contains("computer optical disc")) {
						result.add("Software");
					} else if (physicalDescriptionData.contains("sound cassettes")) {
						result.add("SoundCassette");
					} else if (physicalDescriptionData.contains("sound discs")) {
						result.add("SoundDisc");
					}
					//Since this is fairly generic, only use it if we have no other formats yet
					if (result.size() == 0 && physicalDescriptionData.matches("^.*?\\d+\\s+(p\\.|pages).*$")) {
						result.add("Book");
					}
				}
			}
		}
	}

	private void getFormatFromNotes(Record record, Set<String> result) {
		// Check for formats in the 538 field
		DataField sysDetailsNote2 = (DataField) record.getVariableField("538");
		if (sysDetailsNote2 != null) {
			if (sysDetailsNote2.getSubfield('a') != null) {
				String sysDetailsValue = sysDetailsNote2.getSubfield('a').getData().toLowerCase();
				if (sysDetailsValue.contains("playaway")) {
					result.add("Playaway");
				} else if (sysDetailsValue.contains("kinect sensor")) {
					result.add("Kinect");
				} else if (sysDetailsValue.contains("xbox")) {
					result.add("Xbox360");
				} else if (sysDetailsValue.contains("playstation 3")) {
					result.add("PlayStation3");
				} else if (sysDetailsValue.contains("playstation")) {
					result.add("PlayStation");
				} else if (sysDetailsValue.contains("nintendo wii")) {
					result.add("Wii");
				} else if (sysDetailsValue.contains("directx")) {
					result.add("WindowsGame");
				} else if (sysDetailsValue.contains("bluray") || sysDetailsValue.contains("blu-ray")) {
					result.add("Blu-ray");
				} else if (sysDetailsValue.contains("dvd")) {
					result.add("DVD");
				} else if (sysDetailsValue.contains("vertical file")) {
					result.add("VerticalFile");
				}
			}
		}

		// Check for formats in the 500 tag
		DataField noteField = (DataField) record.getVariableField("500");
		if (noteField != null) {
			if (noteField.getSubfield('a') != null) {
				String noteValue = noteField.getSubfield('a').getData().toLowerCase();
				if (noteValue.contains("vertical file")) {
					result.add("VerticalFile");
				}
			}
		}
	}

	private void getFormatFromSubjects(Record record, Set<String> result) {
		@SuppressWarnings("unchecked")
		List<DataField> topicalTerm = getDataFields(record, "650");
		if (topicalTerm != null) {
			Iterator<DataField> fieldsIter = topicalTerm.iterator();
			DataField field;
			while (fieldsIter.hasNext()) {
				field = fieldsIter.next();
				@SuppressWarnings("unchecked")
				List<Subfield> subfields = field.getSubfields();
				for (Subfield subfield : subfields) {
					if (subfield.getCode() == 'a'){
						String subfieldData = subfield.getData().toLowerCase();
						if (subfieldData.contains("large type")) {
							result.add("LargePrint");
						}else if (subfieldData.contains("playaway")) {
							result.add("Playaway");
						}else if (subfieldData.contains("graphic novel")) {
							boolean okToAdd = false;
							if (field.getSubfield('v') != null){
								String subfieldVData = field.getSubfield('v').getData().toLowerCase();
								if (!subfieldVData.contains("Television adaptation")){
									okToAdd = true;
								}else{
									System.out.println("Not including graphic novel format");
								}
							}else{
								okToAdd = true;
							}
							if (okToAdd){
								result.add("GraphicNovel");
							}
						}
					}
				}
			}
		}

		@SuppressWarnings("unchecked")
		List<DataField> localTopicalTerm = getDataFields(record, "690");
		if (localTopicalTerm != null) {
			Iterator<DataField> fieldsIterator = localTopicalTerm.iterator();
			DataField field;
			while (fieldsIterator.hasNext()) {
				field = fieldsIterator.next();
				Subfield subfieldA = field.getSubfield('a');
				if (subfieldA != null) {
					if (subfieldA.getData().toLowerCase().contains("seed library")) {
						result.add("SeedPacket");
					}
				}
			}
		}

		@SuppressWarnings("unchecked")
		List<DataField> addedEntryFields = getDataFields(record, "710");
		if (localTopicalTerm != null) {
			Iterator<DataField> addedEntryFieldIterator = addedEntryFields.iterator();
			DataField field;
			while (addedEntryFieldIterator.hasNext()) {
				field = addedEntryFieldIterator.next();
				Subfield subfieldA = field.getSubfield('a');
				if (subfieldA != null && subfieldA.getData() != null) {
					String fieldData = subfieldA.getData().toLowerCase();
					if (fieldData.contains("playaway digital audio") || fieldData.contains("findaway world")) {
						result.add("Playaway");
					}
				}
			}
		}
	}

	private void getFormatFrom007(Record record, Set<String> result) {
		char formatCode;// check the 007 - this is a repeating field
		@SuppressWarnings("unchecked")
		List<DataField> fields = getDataFields(record, "007");
		if (fields != null) {
			Iterator<DataField> fieldsIter = fields.iterator();
			ControlField formatField;
			while (fieldsIter.hasNext()) {
				formatField = (ControlField) fieldsIter.next();
				if (formatField.getData() == null || formatField.getData().length() < 2) {
					continue;
				}
				// Check for blu-ray (s in position 4)
				// This logic does not appear correct.
				/*
				 * if (formatField.getData() != null && formatField.getData().length()
				 * >= 4){ if (formatField.getData().toUpperCase().charAt(4) == 'S'){
				 * result.add("Blu-ray"); break; } }
				 */
				formatCode = formatField.getData().toUpperCase().charAt(0);
				switch (formatCode) {
					case 'A':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'D':
								result.add("Atlas");
								break;
							default:
								result.add("Map");
								break;
						}
						break;
					case 'C':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'A':
								result.add("TapeCartridge");
								break;
							case 'B':
								result.add("ChipCartridge");
								break;
							case 'C':
								result.add("DiscCartridge");
								break;
							case 'F':
								result.add("TapeCassette");
								break;
							case 'H':
								result.add("TapeReel");
								break;
							case 'J':
								result.add("FloppyDisk");
								break;
							case 'M':
							case 'O':
								result.add("CDROM");
								break;
							case 'R':
								// Do not return - this will cause anything with an
								// 856 field to be labeled as "Electronic"
								break;
							default:
								result.add("Software");
								break;
						}
						break;
					case 'D':
						result.add("Globe");
						break;
					case 'F':
						result.add("Braille");
						break;
					case 'G':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'C':
							case 'D':
								result.add("Filmstrip");
								break;
							case 'T':
								result.add("Transparency");
								break;
							default:
								result.add("Slide");
								break;
						}
						break;
					case 'H':
						result.add("Microfilm");
						break;
					case 'K':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'C':
								result.add("Collage");
								break;
							case 'D':
								result.add("Drawing");
								break;
							case 'E':
								result.add("Painting");
								break;
							case 'F':
								result.add("Print");
								break;
							case 'G':
								result.add("Photonegative");
								break;
							case 'J':
								result.add("Print");
								break;
							case 'L':
								result.add("Drawing");
								break;
							case 'O':
								result.add("FlashCard");
								break;
							case 'N':
								result.add("Chart");
								break;
							default:
								result.add("Photo");
								break;
						}
						break;
					case 'M':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'F':
								result.add("VideoCassette");
								break;
							case 'R':
								result.add("Filmstrip");
								break;
							default:
								result.add("MotionPicture");
								break;
						}
						break;
					case 'O':
						result.add("Kit");
						break;
					case 'Q':
						result.add("MusicalScore");
						break;
					case 'R':
						result.add("SensorImage");
						break;
					case 'S':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'D':
								if (formatField.getData().length() >= 4) {
									char speed = formatField.getData().toUpperCase().charAt(3);
									if (speed >= 'A' && speed <= 'E') {
										result.add("Phonograph");
									} else if (speed == 'F') {
										result.add("CompactDisc");
									} else if (speed >= 'K' && speed <= 'R') {
										result.add("TapeRecording");
									} else {
										result.add("SoundDisc");
									}
								} else {
									result.add("SoundDisc");
								}
								break;
							case 'S':
								result.add("SoundCassette");
								break;
							default:
								result.add("SoundRecording");
								break;
						}
						break;
					case 'T':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'A':
								result.add("Book");
								break;
							case 'B':
								result.add("LargePrint");
								break;
						}
						break;
					case 'V':
						switch (formatField.getData().toUpperCase().charAt(1)) {
							case 'C':
								result.add("VideoCartridge");
								break;
							case 'D':
								result.add("VideoDisc");
								break;
							case 'F':
								result.add("VideoCassette");
								break;
							case 'R':
								result.add("VideoReel");
								break;
							default:
								result.add("Video");
								break;
						}
						break;
				}
			}
		}
	}

	private void getFormatFromLeader(Set<String> result, String leader, ControlField fixedField) {
		char leaderBit;
		char formatCode;// check the Leader at position 6
		if (leader.length() >= 6) {
			leaderBit = leader.charAt(6);
			switch (Character.toUpperCase(leaderBit)) {
				case 'C':
				case 'D':
					result.add("MusicalScore");
					break;
				case 'E':
				case 'F':
					result.add("Map");
					break;
				case 'G':
					// We appear to have a number of items without 007 tags marked as G's.
					// These seem to be Videos rather than Slides.
					// result.add("Slide");
					result.add("Video");
					break;
				case 'I':
					result.add("SoundRecording");
					break;
				case 'J':
					result.add("MusicRecording");
					break;
				case 'K':
					result.add("Photo");
					break;
				case 'M':
					result.add("Electronic");
					break;
				case 'O':
				case 'P':
					result.add("Kit");
					break;
				case 'R':
					result.add("PhysicalObject");
					break;
				case 'T':
					result.add("Manuscript");
					break;
			}
		}

		if (leader.length() >= 7) {
			// check the Leader at position 7
			leaderBit = leader.charAt(7);
			switch (Character.toUpperCase(leaderBit)) {
				// Monograph
				case 'M':
					if (result.isEmpty()) {
						result.add("Book");
					}
					break;
				// Serial
				case 'S':
					// Look in 008 to determine what type of Continuing Resource
					formatCode = fixedField.getData().toUpperCase().charAt(21);
					switch (formatCode) {
						case 'N':
							result.add("Newspaper");
							break;
						case 'P':
							result.add("Journal");
							break;
						default:
							result.add("Serial");
							break;
					}
			}
		}
	}

	/**
	 * Calculates a check digit for a III identifier
	 * @param basedId String the base id without checksum
	 * @return String the check digit
	 */
	public static String getCheckDigit(String basedId) {
		if (basedId.length() != 7){
			return "a";
		}else{
			int sumOfDigits = 0;
			for (int i = 0; i < 7; i++){
				sumOfDigits += (8 - i) * Integer.parseInt(basedId.substring(i, i+1));
			}
			int modValue = sumOfDigits % 11;
			if (modValue == 10){
				return "x";
			}else{
				return Integer.toString(modValue);
			}
		}

	}
}
