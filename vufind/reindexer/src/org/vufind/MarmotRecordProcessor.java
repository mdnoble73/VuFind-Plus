package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

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
	/*private Connection econtentConn;
	private PreparedStatement loadEContentRecordForIlsIdStmt;
	private PreparedStatement loadEContentItemsForRecordStmt;*/

	public MarmotRecordProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Connection econtentConn, Ini configIni, Logger logger) {
		super(indexer, vufindConn, configIni, logger);
		/*this.econtentConn = econtentConn;
		try{
			loadEContentRecordForIlsIdStmt = econtentConn.prepareStatement("SELECT * FROM econtent_record where ilsId = ? and status = 'active'");
			loadEContentItemsForRecordStmt = econtentConn.prepareStatement("SELECT * FROM econtent_item where recordId = ?");
		}catch (SQLException e){
			logger.error("Unable to create statements for Restricted EContent");
		}*/
	}

	protected void loadRecordType(GroupedWorkSolr groupedWork, Record record, List<DataField> printItems, List<DataField> econtentItems) {
		//Check the items to see if we need to subdivide the record
		//We can potentially subdivide into external eContent, public domain, and restricted
		String recordId = getFirstFieldVal(record, recordNumberTag + "a");

		HashSet<String> recordTypes = new HashSet<String>();
		for (DataField curItem : econtentItems){
			String subfieldW = curItem.getSubfield('w').getData();
			if (subfieldW.indexOf(':') > 0){
				String[] econtentData = subfieldW.split("\\s?:\\s?");
				String protectionType = econtentData[1].toLowerCase().trim();
				if (protectionType.equals("acs") || protectionType.equals("drm")){
					recordTypes.add("restricted_econtent") ;
				}else if (protectionType.equals("public domain") || protectionType.equals("free")){
					recordTypes.add("public_domain_econtent");
				}else if (protectionType.equals("external")){
					recordTypes.add("external_econtent");
				}else{
					logger.warn("Unknown protection type " + protectionType);
				}
			}else{
				logger.warn("Invalid subfieldw for item in record " + recordId);
				recordTypes.add("ils");
			}
		}
		//No items, must be an order record, assume it is ils
		if (printItems.size() > 0){
			recordTypes.add("ils");
		}
		for (String recordType : recordTypes){
			groupedWork.addRelatedRecord(recordType + ":" + recordId);
		}
	}

	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		super.updateGroupedWorkSolrDataBasedOnMarc(groupedWork, record, identifier);
	}

	protected void loadAdditionalOwnershipInformation(GroupedWorkSolr groupedWork, String locationCode){
		groupedWork.addCollectionGroup(indexer.translateValue("collection_group", locationCode));
		//TODO: Make collections by library easier to define (in VuFind interface)
		if (additionalCollections != null){
			for (String additionalCollection : additionalCollections){
				groupedWork.addCollectionAdams(indexer.translateValue(additionalCollection, locationCode));
			}
		}
		ArrayList<String> subdomainsForLocation = getLibrarySubdomainsForLocationCode(locationCode);
		ArrayList<String> relatedLocationCodesForLocation = getRelatedLocationCodesForLocationCode(locationCode);
		groupedWork.addDetailedLocation(indexer.translateValue("detailed_location", locationCode), subdomainsForLocation, relatedLocationCodesForLocation);
	}

	protected void loadLocalCallNumbers(GroupedWorkSolr groupedWork, List<DataField> printItems, List<DataField> econtentItems) {
		for (DataField curItem : printItems){
			Subfield locationSubfield = curItem.getSubfield(locationSubfieldIndicator);
			if (locationSubfield != null){
				String locationCode = locationSubfield.getData();
				String callNumberPrestamp = "";
				if (callNumberPrestampSubfield != ' '){
					callNumberPrestamp = curItem.getSubfield(callNumberPrestampSubfield) == null ? "" : curItem.getSubfield(callNumberPrestampSubfield).getData();
				}
				String callNumber = "";
				if (callNumberSubfield != ' '){
					callNumber = curItem.getSubfield(callNumberSubfield) == null ? "" : curItem.getSubfield(callNumberSubfield).getData();
				}
				String callNumberCutter = "";
				if (callNumberCutterSubfield != ' '){
					callNumberCutter = curItem.getSubfield(callNumberCutterSubfield) == null ? "" : curItem.getSubfield(callNumberCutterSubfield).getData();
				}
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

	protected List<DataField> getUnsuppressedPrintItems(Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<DataField> unsuppressedItemRecords = new ArrayList<DataField>();
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				if (useEContentSubfield){
					if (itemField.getSubfield(eContentSubfield) != null){
						String eContentData = itemField.getSubfield(eContentSubfield).getData();
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
					unsuppressedItemRecords.add(itemField);
				}
			}
		}
		return unsuppressedItemRecords;
	}

	protected List<DataField> getUnsuppressedEContentItems(Record record){
		List<DataField> itemRecords = getDataFields(record, itemTag);
		List<DataField> unsuppressedEcontentRecords = new ArrayList<DataField>();
		for (DataField itemField : itemRecords){
			if (!isItemSuppressed(itemField)){
				//Check to see if the item has an eContent indicator
				boolean isEContent = false;
				boolean isOverDrive = false;
				if (useEContentSubfield){
					if (itemField.getSubfield(eContentSubfield) != null){
						String eContentData = itemField.getSubfield(eContentSubfield).getData();
						if (eContentData.indexOf(':') >= 0){
							isEContent = true;
							String[] eContentFields = eContentData.split(":");
							String sourceType = eContentFields[0].toLowerCase().trim();
							if (sourceType.equals("overdrive")){
								isOverDrive = true;
							}
						}else{
							if (itemField.getSubfield(eContentSubfield).getData().trim().equalsIgnoreCase("overdrive")){
								isOverDrive = true;
							}
						}
					}
				}
				if (!isOverDrive && isEContent){
					unsuppressedEcontentRecords.add(itemField);
				}
			}
		}
		return unsuppressedEcontentRecords;
	}

	/**
	 * Do item based determination of econtent sources, and protection types.
	 * For Marmot also load availability and ownership information for eContent since it is so similar.
	 *
	 * @param groupedWork
	 * @param itemRecords
	 */
	protected void loadEContentSourcesAndProtectionTypes(GroupedWorkSolr groupedWork, List<DataField> itemRecords) {
		for (DataField curItem : itemRecords){
			//Check subfield w to get the source
			if (curItem.getSubfield('w') != null){
				String locationCode = curItem.getSubfield(locationSubfieldIndicator) == null ? null : curItem.getSubfield(locationSubfieldIndicator).getData();
				HashSet<String> sources = new HashSet<String>();
				HashSet<String> protectionTypes = new HashSet<String>();

				String subfieldW = curItem.getSubfield('w').getData();
				String[] econtentData = subfieldW.split("\\s?:\\s?");
				String eContentSource = econtentData[0].trim();
				String protectionType = econtentData[1].toLowerCase().trim();

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
					//TODO: Determine availability
					available = true;
				}
				boolean shareWithAll = false;
				boolean shareWithLibrary = false;
				if (econtentData.length >= 3){
					String sharing = econtentData[2].trim();
					if (sharing.equalsIgnoreCase("shared")){
						shareWithAll = true;
					}else if (sharing.equalsIgnoreCase("library")){
						shareWithLibrary = true;
					}
				}else{
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
					groupedWork.addEContentSources(sources, subdomainMap.values() , locationMap.values());
					groupedWork.addEContentProtectionTypes(protectionTypes, subdomainMap.values() , locationMap.values());
					groupedWork.addCompatiblePTypes(allPTypes);
					owningLibraries.add("Shared Digital Collection");
					owningLibraries.addAll(libraryOnlineFacetMap.values());
					owningSubdomainsAndLocations.addAll(subdomainMap.values());
					owningSubdomainsAndLocations.addAll(locationMap.values());
					if (available){
						availableLibraries.addAll(libraryFacetMap.values());
						availableSubdomainsAndLocations.addAll(subdomainMap.values());
						availableSubdomainsAndLocations.addAll(locationMap.values());
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
	}

	protected void loadUsability(GroupedWorkSolr groupedWork, List<DataField> printItems, List<DataField> econtentItems) {
		super.loadUsability(groupedWork, printItems, econtentItems);

		for (DataField itemField : econtentItems){
			if (itemField.getSubfield(eContentSubfield) != null){
				String eContentData = itemField.getSubfield(eContentSubfield).getData();
				String locationCode = itemField.getSubfield(locationSubfieldIndicator) == null ? null : itemField.getSubfield(locationSubfieldIndicator).getData().trim();
				if (eContentData.indexOf(':') >= 0){
					boolean shareWithAll = false;
					boolean shareWithLibrary = false;
					String[] econtentData = eContentData.split("\\s?:\\s?");
					if (econtentData.length >= 3){
						String sharing = econtentData[2].trim();
						if (sharing.equalsIgnoreCase("shared")){
							shareWithAll = true;
						}else if (sharing.equalsIgnoreCase("library")){
							shareWithLibrary = true;
						}
					}else{
						if (locationCode != null){
							if (locationCode.startsWith("mdl")){
								shareWithAll = true;
							}else{
								shareWithLibrary = true;
							}
						}else{
							logger.error("Location code was null for item, skipping to next");
							continue;
						}
					}
					if (shareWithAll){
						groupedWork.addCompatiblePTypes(allPTypes);
						break;
					}else if (shareWithLibrary){
						if (locationCode == null){
							logger.error("Location code was null for item, skipping to next");
						} else {
							for(String curLocation : pTypesByLibrary.keySet()){
								if (locationCode.startsWith(curLocation)){
									groupedWork.addCompatiblePTypes(pTypesByLibrary.get(curLocation));
								}
							}
						}
					} else{
						logger.warn("Could not determine usability, was not shared with library or everyone");
					}
				}
			}
		}
	}

	/**
	 * Determine Record Format(s)
	 *
	 * @return Set format of record
	 */
	public Set<String> loadFormats(GroupedWorkSolr groupedWork, Record record, String identifier, List<DataField> printItems, List<DataField> econtentItems) {
		Set<String> result = new LinkedHashSet<String>();
		if (printItems.size() > 0){
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
						result.add("MusicRecording");
						break;
				}
			}
			getFormatFromPublicationInfo(record, result);
			getFormatFromNotes(record, result);
			getFormatFromEdition(record, result);
			getFormatFromPhysicalDescription(record, result);
			getFormatFromSubjects(record, result);
			getFormatFrom007(record, result);
			getFormatFromLeader(result, leader, fixedField);
		}
		if (econtentItems.size() > 0){
			getFormatFromEcontentItems(identifier, result, econtentItems);
		}

		// Nothing worked!
		if (result.isEmpty()) {
			result.add("Unknown");
		}

		return result;
	}

	private void getFormatFromEcontentItems(String identifier, Set<String> result, List<DataField> econtentItems) {
		for (DataField curItem : econtentItems){
			if (curItem.getSubfield('w') != null){
				String subfieldW = curItem.getSubfield('w').getData();
				String[] econtentData = subfieldW.split("\\s?:\\s?");
				String protectionType = econtentData[1].toLowerCase().trim();
				if (protectionType.equals("acs") || protectionType.equals("drm") || protectionType.equals("public domain") || protectionType.equals("free")){
					if (econtentData.length >= 4){
						String filename = econtentData[3].trim().toLowerCase();
						if (filename.indexOf('.') > 0){
							String fileExtension = filename.substring(filename.lastIndexOf('.') + 1);
							result.add(fileExtension);
						}else{
							//For now we know these are folders of MP3 files
							//TODO: Probably should actually open the folder to make sure that it contains MP3 files
							result.add("mp3");
						}
					}else{
						logger.warn("Filename for local econtent not specified " + subfieldW + " " + identifier);
					}
				}else if (protectionType.equals("external")){
					String iType = curItem.getSubfield(iTypeSubfield) == null ? null : curItem.getSubfield(iTypeSubfield).getData();
					if (iType != null){
						String translatedFormat = indexer.translateValue("econtent_itype_format", iType);
						result.add(translatedFormat);
					}
				}else{
					logger.warn("Unknown protection type " + protectionType);
				}
			}
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
				if (edition.getSubfield('a').getData().toLowerCase()
						.contains("large type")) {
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
					if (subfield.getData().toLowerCase().contains("large type") || subfield.getData().toLowerCase().contains("large print")) {
						result.add("LargePrint");
					} else if (subfield.getData().toLowerCase().contains("bluray")
							|| subfield.getData().toLowerCase().contains("blu-ray")) {
						result.add("Blu-ray");
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
				String sysDetailsValue = sysDetailsNote2.getSubfield('a').getData()
						.toLowerCase();
				if (sysDetailsValue.contains("playaway")) {
					result.add("Playaway");
				} else if (sysDetailsValue.contains("bluray")
						|| sysDetailsValue.contains("blu-ray")) {
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
					if (subfield.getData().toLowerCase().contains("large type")) {
						result.add("LargePrint");
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

	/*private EContentRecord getEContentRecord(String identifier){
		EContentRecord record = null;
		try{
			loadEContentRecordForIlsIdStmt.setString(1, identifier);
			ResultSet eContentRecordData = loadEContentRecordForIlsIdStmt.executeQuery();
			if (eContentRecordData.next()){
				record = new EContentRecord();
				record.setIlsId(identifier);
				record.setAccessType(eContentRecordData.getString("accessType"));
				record.setEContentRecordId(eContentRecordData.getLong("id"));
				record.setAvailableCopies(eContentRecordData.getLong("availableCopies"));
				record.setOnOrderCopies(eContentRecordData.getLong("onOrderCopies"));
				record.setSource(eContentRecordData.getString("source"));

				//Load items
				loadEContentRecordForIlsIdStmt.setLong(1, record.getEContentRecordId());
				ResultSet eContentItemData = loadEContentRecordForIlsIdStmt.executeQuery();
				while (eContentItemData.next()){
					EContentItem item = new EContentItem();
					item.setFilename(eContentItemData.getString("filename"));
					item.setFolder(eContentItemData.getString("folder"));
					item.setItemType(eContentItemData.getString("item_type"));
					item.setLibraryId(eContentItemData.getLong("libraryId"));
					record.addEContentItem(item);
				}
			}else{
				//TODO: Should we create the record here?
			}
		}catch(SQLException e){
			logger.error("Error loading eContent Record", e);
		}
		return record;
	}*/
}
