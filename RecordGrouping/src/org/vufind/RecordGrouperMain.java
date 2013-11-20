package org.vufind;

import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.Record;

import java.io.File;
import java.io.FileInputStream;
import java.sql.Connection;
import java.sql.DriverManager;
import java.util.Date;

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
		try{
			dbConnection = DriverManager.getConnection("jdbc:mysql://localhost/marmot_record_grouping_2?user=root&password=Ms$qlR00t&useUnicode=yes&characterEncoding=UTF-8");
		}catch (Exception e){
			System.out.println("Error connecting to database " + e.toString());
			System.exit(1);
		}

		//Load MARC Existing MARC Records from Sierra?
		RecordGroupingProcessor recordGroupingProcessor = new RecordGroupingProcessor(dbConnection);
		//Clear the database first
		boolean clearDatabasePriorToGrouping = true;
		if (clearDatabasePriorToGrouping){
			try{
				dbConnection.prepareStatement("TRUNCATE grouped_record").executeUpdate();
				dbConnection.prepareStatement("TRUNCATE grouped_record_to_normalized_record").executeUpdate();
				dbConnection.prepareStatement("TRUNCATE grouped_work").executeUpdate();
				dbConnection.prepareStatement("TRUNCATE grouped_work_to_grouped_record").executeUpdate();
				dbConnection.prepareStatement("TRUNCATE normalized_record").executeUpdate();
				dbConnection.prepareStatement("TRUNCATE normalized_record_related_bibs").executeUpdate();
				dbConnection.prepareStatement("TRUNCATE normalized_record_identifiers").executeUpdate();
			}catch (Exception e){
				System.out.println("Error clearing database " + e.toString());
				System.exit(1);
			}
		}

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
				System.out.println("Finished grouping records from catalog.");
			}
		}

		//Group records from OverDrive

		//TODO: Group records from other sources

		try{
			dbConnection.close();
		}catch (Exception e){
			System.out.println("Error closing database " + e.toString());
			System.exit(1);
		}
		System.out.println("Finished grouping records " + new Date().toString());
	}
}
