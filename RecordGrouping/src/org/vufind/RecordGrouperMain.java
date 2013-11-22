package org.vufind;

import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.Record;

import java.io.File;
import java.io.FileInputStream;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.Date;
import java.util.HashSet;

/**
 * Groups records so that we can show single multiple titles as one rather than as multiple lines.
 *
 * Grouping happens at 3 different levels:
 *
 */
public class RecordGrouperMain {

	public static void main(String[] args) {
		System.out.println("Starting grouping of records " + new Date().toString());
		//Connect to the database
		Connection dbConnection = null;
		Connection econtentConnection = null;
		try{
			dbConnection = DriverManager.getConnection("jdbc:mysql://localhost/marmot_record_grouping_2?user=root&password=Ms$qlR00t&useUnicode=yes&characterEncoding=UTF-8");
			econtentConnection = DriverManager.getConnection("jdbc:mysql://localhost/marmot_econtent?user=root&password=Ms$qlR00t&useUnicode=yes&characterEncoding=UTF-8");
		}catch (Exception e){
			System.out.println("Error connecting to database " + e.toString());
			System.exit(1);
		}

		//Load MARC Existing MARC Records from Sierra?
		RecordGroupingProcessor recordGroupingProcessor = new RecordGroupingProcessor(dbConnection);
		//Clear the database first
		boolean clearDatabasePriorToGrouping = true;
		boolean groupIlsRecords = true;
		boolean groupOverDriveRecords = true;

		if (clearDatabasePriorToGrouping){
			try{
				dbConnection.prepareStatement("TRUNCATE grouped_work").executeUpdate();
				dbConnection.prepareStatement("TRUNCATE grouped_work_identifiers").executeUpdate();
			}catch (Exception e){
				System.out.println("Error clearing database " + e.toString());
				System.exit(1);
			}
		}

		if (groupIlsRecords){
			File[] catalogBibFiles = new File("C:\\web\\VuFind-Plus\\RecordGrouping\\source_marcs\\").listFiles();
			int numRecordsProcessed = 0;
			long startTime = new Date().getTime();
			if (catalogBibFiles != null){
				for (File curBibFile : catalogBibFiles){
					if (curBibFile.getName().endsWith(".mrc") || curBibFile.getName().endsWith(".marc")){
						System.out.println("Processing " + curBibFile);
						try{
							MarcReader catalogReader = new MarcPermissiveStreamReader(new FileInputStream(curBibFile), true, true, "UTF8");
							while (catalogReader.hasNext()){
								Record curBib = catalogReader.next();
								recordGroupingProcessor.processMarcRecord(curBib);
								numRecordsProcessed++;
								if (numRecordsProcessed % 1000 == 0){
									long elapsedTime = new Date().getTime() - startTime;
									System.out.println("Processed " + numRecordsProcessed + " records in " + (elapsedTime / 1000) + " seconds");
								}
							}
						}catch(Exception e){
							System.out.println("Error loading catalog bibs: " + e.toString());
							e.printStackTrace();
						}
					}
					System.out.println("Finished grouping " + numRecordsProcessed + " records from the ils.");
				}
			}
		}

		//Group records from OverDrive
		if (groupOverDriveRecords){
			try{
				int numRecordsProcessed = 0;
				long startTime = new Date().getTime();
				PreparedStatement overDriveRecordsStmt = econtentConnection.prepareStatement("SELECT id, overdriveId, mediaType, title, primaryCreatorRole, primaryCreatorName FROM overdrive_api_products WHERE deleted = 0", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				PreparedStatement overDriveIdentifiersStmt = econtentConnection.prepareStatement("SELECT * FROM overdrive_api_product_identifiers WHERE id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				ResultSet overDriveRecordRS = overDriveRecordsStmt.executeQuery();
				while (overDriveRecordRS.next()){
					Long id = overDriveRecordRS.getLong("id");

					String overdriveId = overDriveRecordRS.getString("overdriveId");
					String mediaType = overDriveRecordRS.getString("mediaType");
					String title = overDriveRecordRS.getString("title");
					//String primaryCreatorRole = overDriveRecordRS.getString("primaryCreatorRole");
					String author = overDriveRecordRS.getString("primaryCreatorName");

					overDriveIdentifiersStmt.setLong(1, id);
					ResultSet overDriveIdentifierRS = overDriveIdentifiersStmt.executeQuery();
					HashSet<RecordIdentifier> overDriveIdentifiers = new HashSet<RecordIdentifier>();
					RecordIdentifier identifier = new RecordIdentifier();
					identifier.type = "overdrive";
					identifier.identifier = overdriveId;
					overDriveIdentifiers.add(identifier);
					while (overDriveIdentifierRS.next()){
						identifier = new RecordIdentifier();
						identifier.type = overDriveIdentifierRS.getString("type");
						identifier.identifier = overDriveIdentifierRS.getString("value");
						overDriveIdentifiers.add(identifier);
					}

					recordGroupingProcessor.processRecord(title, "", author, mediaType, overDriveIdentifiers);

					numRecordsProcessed++;
					if (numRecordsProcessed % 1000 == 0){
						long elapsedTime = new Date().getTime() - startTime;
						System.out.println("Processed " + numRecordsProcessed + " records in " + (elapsedTime / 1000) + " seconds");
					}
				}
			}catch (Exception e){
				System.out.println("Error loading OverDrive records: " + e.toString());
				e.printStackTrace();
			}
		}

		//Group records from other sources

		try{
			dbConnection.close();
		}catch (Exception e){
			System.out.println("Error closing database " + e.toString());
			System.exit(1);
		}
		System.out.println("Finished grouping records " + new Date().toString());
	}
}
