package org.vufind;

import java.io.*;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.Date;

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

		Date currentTime = new Date();
		File log4jFile = new File("../../sites/" + serverName + "/conf/log4j.sierra_extract.properties");
		if (log4jFile.exists()){
			PropertyConfigurator.configure(log4jFile.getAbsolutePath());
		}else{
			System.out.println("Could not find log4j configuration " + log4jFile.toString());
		}
		logger.info(currentTime.toString() + ": Starting Sierra Extract");

		// Read the base INI file to get information about the server (current directory/cron/config.ini)
		Ini ini = loadConfigFile("config.ini");

		//Connect to the database
		String url = ini.get("Catalog", "sierra_db");
		Connection conn = null;
		try{
			//Open the connection to the database
			conn = DriverManager.getConnection(url);

			//Create a file to hold the results
			File outputFile = new File("./export/exported_marcs.mrc");
			MarcWriter marcWriter = new MarcStreamWriter(new FileOutputStream(outputFile));

			PreparedStatement loadAllRecordsStmt = conn.prepareStatement("SELECT id from sierra_view.record_metadata where record_type_code = 'b' and deletion_date_gmt is null limit 25;");
			PreparedStatement loadLeaderStmt = conn.prepareStatement("SELECT * from sierra_view.leader_field where record_id=?;");
			PreparedStatement varFieldsStmt = conn.prepareStatement("SELECT * from sierra_view.var_field_view where record_id=? order by marc_tag, occ_num;");
			PreparedStatement loadSubfieldsStmt = conn.prepareStatement("SELECT * from sierra_view.subfield where record_id=? order by marc_tag, occ_num;");
			PreparedStatement loadItemsStmt = conn.prepareStatement("SELECT * from sierra_view.item_view where sierra_view.item_view.id in (SELECT item_record_id from sierra_view.bib_record_item_record_link where bib_record_id =?)");
			ResultSet allRecords = loadAllRecordsStmt.executeQuery();
			MarcFactory marcFactory = MarcFactoryImpl.newInstance();
			while (allRecords.next()){
				Record marcRecord = marcFactory.newRecord();
				Long curId = allRecords.getLong("id");
				System.out.println("Processing record " + curId);

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

				//Setup fixed fields
				loadFixedFieldsStmt.setLong(1, curId);
				ResultSet fixedFieldsRS = loadFixedFieldsStmt.executeQuery();
				while (fixedFieldsRS.next()) {
					String controlNumber = "00" + fixedFieldsRS.getString("control_num");
					//System.out.println(" loading fixed field " + controlNumber);
					ControlField controlField = new ControlFieldImpl(controlNumber);
					String fieldData = fixedFieldsRS.getString("p01") +
							fixedFieldsRS.getString("p02") +
							fixedFieldsRS.getString("p03") +
							fixedFieldsRS.getString("p04") +
							fixedFieldsRS.getString("p05") +
							fixedFieldsRS.getString("p06") +
							fixedFieldsRS.getString("p07") +
							fixedFieldsRS.getString("p08") +
							fixedFieldsRS.getString("p09") +
							fixedFieldsRS.getString("p10") +
							fixedFieldsRS.getString("p11") +
							fixedFieldsRS.getString("p12") +
							fixedFieldsRS.getString("p13") +
							fixedFieldsRS.getString("p14") +
							fixedFieldsRS.getString("p15") +
							fixedFieldsRS.getString("p16") +
							fixedFieldsRS.getString("p17") +
							fixedFieldsRS.getString("p18") +
							fixedFieldsRS.getString("p19") +
							fixedFieldsRS.getString("p20") +
							fixedFieldsRS.getString("p21") +
							fixedFieldsRS.getString("p22") +
							fixedFieldsRS.getString("p23") +
							fixedFieldsRS.getString("p24") +
							fixedFieldsRS.getString("p25") +
							fixedFieldsRS.getString("p26") +
							fixedFieldsRS.getString("p27") +
							fixedFieldsRS.getString("p28") +
							fixedFieldsRS.getString("p29") +
							fixedFieldsRS.getString("p30") +
							fixedFieldsRS.getString("p31") +
							fixedFieldsRS.getString("p32") +
							fixedFieldsRS.getString("p33") +
							fixedFieldsRS.getString("p34") +
							fixedFieldsRS.getString("p35") +
							fixedFieldsRS.getString("p36") +
							fixedFieldsRS.getString("p37") +
							fixedFieldsRS.getString("p38") +
							fixedFieldsRS.getString("p39") +
							fixedFieldsRS.getString("p40") +
							fixedFieldsRS.getString("p41") +
							fixedFieldsRS.getString("p42") +
							fixedFieldsRS.getString("p43") +
							fixedFieldsRS.getString("remainder");
					controlField.setData(fieldData.trim());
					marcRecord.addVariableField(controlField);
				}

				//Load variable fields
				loadSubfieldsStmt.setLong(1, curId);
				ResultSet subfields = loadSubfieldsStmt.executeQuery();
				String lastMarcTag = null;
				int lastOccurrence = 0;
				DataField curField = null;
				while (subfields.next()){
					String marcTag = subfields.getString("marc_tag");
					int occurrence = subfields.getInt("occ_num");
					String tag = subfields.getString("tag");
					if (lastMarcTag == null || !lastMarcTag.equalsIgnoreCase(marcTag) || lastOccurrence != occurrence){
						if (tag == null || tag.length() == 0){
							ControlField controlField = marcFactory.newControlField(marcTag, subfields.getString("content"));
							curField = null;
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
							curField = dataField;
						}
					}
					if (tag != null && tag.length() > 0){
						Subfield subfield = marcFactory.newSubfield(tag.charAt(0), subfields.getString("content"));
						curField.addSubfield(subfield);
					}
					lastMarcTag = marcTag;
					lastOccurrence = occurrence;
				}

				//Export items
				loadItemsStmt.setLong(1, curId);
				ResultSet items = loadItemsStmt.executeQuery();
				int numItems = 0;
				while (items.next()){
					System.out.println("    Processing item " + items.getLong("id"));
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
					loadSubfieldsStmt.setLong(1, itemId);
					subfields = loadSubfieldsStmt.executeQuery();
					while (subfields.next()){
						String marcTag = subfields.getString("marc_tag");
						String tag = subfields.getString("tag");
						String content = subfields.getString("content");
						System.out.println("      " + marcTag + "|" + tag + " " + content);
					}

					numItems++;
				}
				System.out.println("  Found " + numItems + " items");

				//Write the record
				//try{
					marcWriter.write(marcRecord);
				//}catch (Exception e){
				//	System.out.println("  error writing marc " + e.toString());
				//}
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
