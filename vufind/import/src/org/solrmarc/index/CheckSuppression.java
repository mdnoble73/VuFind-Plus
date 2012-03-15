package org.solrmarc.index;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.HashSet;
import java.util.Iterator;
import java.util.Set;

import org.marc4j.marc.Record;

public class CheckSuppression {
	private Connection			econtentDatabase;
	private HashSet<String>	eContentRecords = new HashSet<String>();
	
	public CheckSuppression(Connection vufindDatabase, Connection econtentDatabase) {
		// Load a list of records that are in the eContent database
		this.econtentDatabase = econtentDatabase;
		loadEContentRecords();
	}

	public String getSuppression(Record record, String recordIdSpec, String suppressionFieldSpec, String suppressionPattern) {
		if (suppressionFieldSpec != null && suppressionFieldSpec.length() > 0 && suppressionPattern != null & suppressionPattern.length() > 0) {
			Set<String> input = SolrIndexer.getFieldList(record, suppressionFieldSpec);
			Iterator<String> iter = input.iterator();
			while (iter.hasNext()) {
				String curLocationCode = iter.next();
				if (curLocationCode.matches(suppressionPattern)) {
					System.out.println("Suppressing record due to location code");
					return "suppressed";
				}
			}
		}
		
		Set<String> fields = SolrIndexer.getFieldList(record, recordIdSpec);
		Iterator<String> fieldsIter = fields.iterator();
		if (fields != null) {
			while(fieldsIter.hasNext()) {
				// Get the current string to work on:
				String recordId = fieldsIter.next();
				// Check to see if the record has an eContent Record
				if (eContentRecords.contains(recordId)){
					//There is at least one record.
					System.out.println("Suppressing because there is an eContent record for " + recordId);
					return "suppressed";
				}
			}
		}

		return "notsuppressed";
	}

	public void loadEContentRecords() {
		try {
			PreparedStatement eContentRecordStmt = econtentDatabase.prepareStatement("SELECT DISTINCT ilsId FROM econtent_record where ilsId is not null");
			ResultSet eContentRecordRs = eContentRecordStmt.executeQuery();
			while (eContentRecordRs.next()) {
				eContentRecords.add(eContentRecordRs.getString(1));
			}
		} catch (SQLException e) {
			System.out.println("Error loading eContent Records");
		}
	}
}
