package org.vufind;

import java.io.*;
import java.sql.*;
import java.util.Date;

import au.com.bytecode.opencsv.CSVWriter;
import org.apache.log4j.Logger;
import org.apache.log4j.PropertyConfigurator;
import org.ini4j.Ini;
import org.ini4j.InvalidFileFormatException;
import org.ini4j.Profile.Section;
import org.marc4j.MarcStreamWriter;
import org.marc4j.MarcWriter;
import org.marc4j.marc.*;
import org.marc4j.marc.impl.*;

/**
 * Export data to
 * VuFind-Plus
 * User: Mark Noble
 * Date: 10/15/13
 * Time: 8:59 AM
 */
public class SierraExportMain{
	private static Logger logger = Logger.getLogger(SierraExportMain.class);
	private static String serverName;

	public static void main(String[] args){
		serverName = args[0];

		Date startTime = new Date();
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.sierra_extract.properties");
		if (log4jFile.exists()){
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		}else{
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger.info(startTime.toString() + ": Starting Sierra Extract");

		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		Ini ini = loadConfigFile("config.ini");
		String exportPath = ini.get("Reindex", "marcPath");
		if (exportPath.startsWith("\"")){
			exportPath = exportPath.substring(1, exportPath.length() - 1);
		}

		//Connect to the database
		String url = ini.get("Catalog", "sierra_db");
		if (url.startsWith("\"")){
			url = url.substring(1, url.length() - 1);
		}
		Connection conn = null;
		int numRecordsRead = 0;
		try{
			//Open the connection to the database
			conn = DriverManager.getConnection(url);


			boolean exportRecords = false;
			if (exportRecords){
				logger.info("Starting export of records");
				//Create a file to hold the results
				File outputFile = new File(exportPath + "/exported_marcs.mrc");
				MarcWriter marcWriter = new MarcStreamWriter(new FileOutputStream(outputFile));

				PreparedStatement loadAllRecordsStmt = conn.prepareStatement("SELECT id from sierra_view.bib_record where is_suppressed = 'f' limit 10000;",  ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				PreparedStatement loadLeaderStmt = conn.prepareStatement("SELECT * from sierra_view.leader_field where record_id=?;",  ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_UPDATABLE);
				PreparedStatement loadVarFieldsStmt = conn.prepareStatement("SELECT * from sierra_view.varfield_view where record_id=? order by marc_tag, occ_num;");
				PreparedStatement loadItemsStmt = conn.prepareStatement("SELECT * from sierra_view.item_view where sierra_view.item_view.id in (SELECT item_record_id from sierra_view.bib_record_item_record_link where bib_record_id =?)");
				ResultSet allRecords = loadAllRecordsStmt.executeQuery();
				MarcFactory marcFactory = MarcFactoryImpl.newInstance();
				int totalRecords = 1616273;
				while (allRecords.next()){
					Record marcRecord = marcFactory.newRecord();
					Long curId = allRecords.getLong("id");
					//String recordNumber = allRecords.getString("record_num");
					//System.out.println("Processing record " + curId);

					//Setup the leader
					loadLeaderStmt.setLong(1, curId);
					ResultSet leaderData = loadLeaderStmt.executeQuery();
					Leader leader = marcRecord.getLeader();
					marcRecord.setLeader(leader);
					if (leaderData.next()){
						char recordStatusCode = leaderData.getString("record_status_code").length() == 1  ? leaderData.getString("record_status_code").charAt(0) : ' ';
						leader.setRecordStatus(recordStatusCode);
						leader.setTypeOfRecord(leaderData.getString("record_type_code").charAt(0));
						leader.setImplDefined1(new char[]{leaderData.getString("bib_level_code").charAt(0), leaderData.getString("control_type_code").charAt(0)});
						leader.setCharCodingScheme(leaderData.getString("char_encoding_scheme_code").charAt(0));
						leader.setImplDefined2(new char[]{leaderData.getString("encoding_level_code").charAt(0), leaderData.getString("descriptive_cat_form_code").charAt(0), leaderData.getString("multipart_level_code").charAt(0)});
					}else{
						System.out.println("  Warning, no leader data found for record " + curId);
						continue;
					}

					//Setup fields
					loadVarFieldsStmt.setLong(1, curId);
					ResultSet subfields = loadVarFieldsStmt.executeQuery();
					DataField curField = null;
					while (subfields.next()){
						String marcTag = subfields.getString("marc_tag");
						if (marcTag == null || marcTag.length() == 0){
							continue;
						}
						int occurrence = subfields.getInt("occ_num");
						int tagNum = Integer.parseInt(marcTag);
						if (tagNum < 10){
							ControlField controlField = marcFactory.newControlField(marcTag, subfields.getString("field_content"));
							marcRecord.addVariableField(controlField);
						}else{
							//Changed field, create a new one.
							char indicator1 = subfields.getString("marc_ind1").charAt(0);
							if (indicator1 == '\\'){
								indicator1 = ' ';
							}
							char indicator2 = subfields.getString("marc_ind2").charAt(0);
							if (indicator2 == '\\'){
								indicator2 = ' ';
							}
							DataField dataField = marcFactory.newDataField(marcTag, indicator1, indicator2);
							marcRecord.addVariableField(dataField);
							String content = subfields.getString("field_content");
							String[] fields = content.split("\\|");
							for (String field : fields){
								if (field.length() >= 1){
									char tag = field.charAt(0);
									dataField.addSubfield(new SubfieldImpl(tag, field.substring(1)));
								}
							}
						}
					}

					//Export items
					loadItemsStmt.setLong(1, curId);
					ResultSet items = loadItemsStmt.executeQuery();
					int numItems = 0;
					while (items.next()){
						//System.out.println("    Processing item " + items.getLong("id"));
						//Create 989 subfields for each item
						DataField itemField = marcFactory.newDataField("989", ' ', ' ');
						marcRecord.addVariableField(itemField);
						String barcode = items.getString("barcode");
						if (barcode != null && barcode.length() > 0){
							itemField.addSubfield(new SubfieldImpl('b', items.getString("barcode")));
						}
						itemField.addSubfield(new SubfieldImpl('d', items.getString("location_code")));
						itemField.addSubfield(new SubfieldImpl('j', items.getString("itype_code_num")));
						itemField.addSubfield(new SubfieldImpl('g', items.getString("item_status_code")));
						itemField.addSubfield(new SubfieldImpl('h', items.getString("checkout_total")));
						itemField.addSubfield(new SubfieldImpl('i', items.getString("renewal_total")));
						//Get additional data
						Long itemId = items.getLong("id");

						//loadSubfieldsStmt.setLong(1, itemId);
						//subfields = loadSubfieldsStmt.executeQuery();
						//while (subfields.next()){
						//	String marcTag = subfields.getString("marc_tag");
						//	String tag = subfields.getString("tag");
						//	String content = subfields.getString("content");
						//	System.out.println("      " + marcTag + "|" + tag + " " + content);
						//}

						numItems++;
					}
					//System.out.println("  Found " + numItems + " items");

					numRecordsRead++;
					//Write the record
					marcWriter.write(marcRecord);
					if (numRecordsRead % 1000 == 0){
						Date curDate = new Date();
						long elapsedTime = curDate.getTime() - startTime.getTime();
						long predictedFinish = elapsedTime * totalRecords / numRecordsRead ;
						double predictedFinishHours = (double)predictedFinish / (double)(1000 * 60 * 60);
						Date finishTime = new Date();
						finishTime.setTime(predictedFinish + startTime.getTime());
						System.out.println("Read " + numRecordsRead + " in " + (elapsedTime / 1000) + " seconds predicted total run time for " + totalRecords + " is " + String.format("%.2f", predictedFinishHours) + " hours");
					}
				}
			}

			boolean exportActiveOrders = true;
			if (exportActiveOrders){
				logger.info("Starting export of active orders");
				PreparedStatement getActiveOrdersStmt = conn.prepareStatement("select bib_view.record_num as bib_record_num, order_view.record_num as order_record_num, accounting_unit_code_num, order_status_code \n" +
						"from sierra_view.order_view " +
						"inner join sierra_view.bib_record_order_record_link on bib_record_order_record_link.order_record_id = order_view.record_id " +
						"inner join sierra_view.bib_view on sierra_view.bib_view.id = bib_record_order_record_link.bib_record_id " +
						"where order_status_code = 'o' or order_status_code = '1' and order_view.is_suppressed = 'f' ");
				ResultSet activeOrdersRS = null;
				boolean loadError = false;
				try{
					activeOrdersRS = getActiveOrdersStmt.executeQuery();
				} catch (SQLException e1){
					logger.error("Error loading active orders", e1);
					loadError = true;
				}
				if (!loadError){
					File orderRecordFile = new File(exportPath + "/active_orders.csv");
					CSVWriter orderRecordWriter = new CSVWriter(new FileWriter(orderRecordFile));
					orderRecordWriter.writeAll(activeOrdersRS, true);
					orderRecordWriter.close();
					activeOrdersRS.close();
				}
			}

			boolean exportAvailableItems = true;
			if (exportAvailableItems){
				logger.info("Starting export of available items");
				PreparedStatement getAvailableItemsStmt = conn.prepareStatement("SELECT barcode " +
						"from sierra_view.item_view " +
						//"left join sierra_view.checkout on sierra_view.item_view.id = sierra_view.checkout.item_record_id " +
						"WHERE " +
						"item_status_code IN ('-', 'o', 'd', 'w', 'j', 'u') " +
						"AND icode2 != 'n' AND icode2 != 'x' " +
						"AND is_suppressed = 'f' " +
						"AND BARCODE != ''"
						//"AND patron_record_id = null"
				);
				ResultSet activeOrdersRS = null;
				boolean loadError = false;
				try{
					activeOrdersRS = getAvailableItemsStmt.executeQuery();
				}catch (SQLException e1){
					logger.error("Error loading available items", e1);
					loadError = true;
				}
				if (!loadError){
					File availableItemsFile = new File(exportPath + "/available_items.csv");
					CSVWriter availableItemWriter = new CSVWriter(new FileWriter(availableItemsFile));
					availableItemWriter.writeAll(activeOrdersRS, false);
					availableItemWriter.close();
					activeOrdersRS.close();
				}

				//Also export items with checkouts
				logger.info("Starting export of checkouts");
				PreparedStatement allCheckoutsStmt = conn.prepareStatement("SELECT barcode " +
						"FROM sierra_view.checkout " +
						"INNER JOIN sierra_view.item_view on item_view.id = checkout.item_record_id"
				);
				ResultSet checkoutsRS = null;
				loadError = false;
				try{
					checkoutsRS = allCheckoutsStmt.executeQuery();
				}catch (SQLException e1){
					logger.error("Error loading checkouts", e1);
					loadError = true;
				}
				if (!loadError){
					File checkoutsFile = new File(exportPath + "/checkouts.csv");
					CSVWriter checkoutsWriter = new CSVWriter(new FileWriter(checkoutsFile));
					checkoutsWriter.writeAll(checkoutsRS, false);
					checkoutsWriter.close();
					checkoutsRS.close();
				}
			}
		}catch(Exception e){
			System.out.println("Error: " + e.toString());
			e.printStackTrace();
		}
		if (conn != null){
			try{
				//Close the connection
				conn.close();
			}catch(Exception e){
				System.out.println("Error closing connection: " + e.toString());
				e.printStackTrace();
			}
		}
		Date currentTime = new Date();
		logger.info(currentTime.toString() + ": Finished Sierra Extract");
	}

	private static Ini loadConfigFile(String filename){
		//First load the default config file
		String configName = "../../sites/default/conf/" + filename;
		logger.info("Loading configuration from " + configName);
		File configFile = new File(configName);
		if (!configFile.exists()) {
			logger.error("Could not find configuration file " + configName);
			System.exit(1);
		}

		// Parse the configuration file
		Ini ini = new Ini();
		try {
			ini.load(new FileReader(configFile));
		} catch (InvalidFileFormatException e) {
			logger.error("Configuration file is not valid.  Please check the syntax of the file.", e);
		} catch (FileNotFoundException e) {
			logger.error("Configuration file could not be found.  You must supply a configuration file in conf called config.ini.", e);
		} catch (IOException e) {
			logger.error("Configuration file could not be read.", e);
		}

		//Now override with the site specific configuration
		String siteSpecificFilename = "../../sites/" + serverName + "/conf/" + filename;
		logger.info("Loading site specific config from " + siteSpecificFilename);
		File siteSpecificFile = new File(siteSpecificFilename);
		if (!siteSpecificFile.exists()) {
			logger.error("Could not find server specific config file");
			System.exit(1);
		}
		try {
			Ini siteSpecificIni = new Ini();
			siteSpecificIni.load(new FileReader(siteSpecificFile));
			for (Section curSection : siteSpecificIni.values()){
				for (String curKey : curSection.keySet()){
					//logger.debug("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					//System.out.println("Overriding " + curSection.getName() + " " + curKey + " " + curSection.get(curKey));
					ini.put(curSection.getName(), curKey, curSection.get(curKey));
				}
			}
		} catch (InvalidFileFormatException e) {
			logger.error("Site Specific config file is not valid.  Please check the syntax of the file.", e);
		} catch (IOException e) {
			logger.error("Site Specific config file could not be read.", e);
		}
		return ini;
	}
}
