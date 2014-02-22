package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashSet;
import java.util.List;
import java.util.Set;

/**
 * eContent where access is locally restricted based on our ACS server and VuFind.
 * VuFind-Plus
 * User: Mark Noble
 * Date: 2/6/14
 * Time: 9:12 AM
 */
public class RestrictedEContentProcessor extends IlsRecordProcessor {
	private Connection econtentConn;
	private PreparedStatement loadEContentRecordForIlsIdStmt;
	private PreparedStatement loadEContentItemsForRecordStmt;

	public RestrictedEContentProcessor(GroupedWorkIndexer indexer, Connection vufindConn, Connection econtentConn, Ini configIni, Logger logger) {
		super(indexer, vufindConn, configIni, logger);
		this.econtentConn = econtentConn;
		try{
			loadEContentRecordForIlsIdStmt = econtentConn.prepareStatement("SELECT * FROM econtent_record where ilsId = ? and status = 'active'");
			loadEContentItemsForRecordStmt = econtentConn.prepareStatement("SELECT * FROM econtent_item where recordId = ?");
		}catch (SQLException e){
			logger.error("Unable to create statements for Restricted EContent");
		}

	}

	protected void updateGroupedWorkSolrDataBasedOnMarc(GroupedWorkSolr groupedWork, Record record, String identifier) {
		super.updateGroupedWorkSolrDataBasedOnMarc(groupedWork, record, identifier);

		List<DataField> eContentItems = getUnsuppressedEContentItems(record);
		loadEContentSourcesAndProtectionTypes(groupedWork, eContentItems);

		//Do special stuff based on the eContentRecord
		EContentRecord eContentRecord = getEContentRecord(identifier);
	}

	protected void loadRecordType(GroupedWorkSolr groupedWork, Record record) {
		String recordId = getFirstFieldVal(record, "907a");
		groupedWork.addRelatedRecord("restricted_econtent:" + recordId);
	}

	@Override
	public Set<String> loadFormats(Record record, boolean returnFirst) {
		//Don't use this for now since will we just override all of loadFormatDetails
		return null;
	}

	private EContentRecord getEContentRecord(String identifier){
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
	}

	private void loadEContentSourcesAndProtectionTypes(GroupedWorkSolr groupedWork, List<DataField> itemRecords) {
		HashSet<String> sources = new HashSet<String>();
		HashSet<String> protectionTypes = new HashSet<String>();
		for (DataField curItem : itemRecords){
			//Check subfield w to get the source
			if (curItem.getSubfield('w') != null){
				String subfieldW = curItem.getSubfield('w').getData();
				String[] econtentData = subfieldW.split("\\s?:\\s?");
				String eContentSource = econtentData[0].trim();
				String protectionType = econtentData[1].toLowerCase().trim();
				if (protectionType.equals("acs") || protectionType.equals("drm")){
					sources.add(eContentSource);
					protectionTypes.add("Limited Access");
				}
			}
		}
		groupedWork.addEContentSources(sources);
		groupedWork.addEContentProtectionTypes(protectionTypes);
	}

	protected List<DataField> getUnsuppressedItems(Record record) {
		return getUnsuppressedEContentItems(record);
	}
}
