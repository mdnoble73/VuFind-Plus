package org.vufind;

import java.io.FileNotFoundException;
import java.io.IOException;
import java.io.InputStream;
import java.net.MalformedURLException;
import java.net.URL;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.sql.Statement;

import org.apache.log4j.Logger;
import org.ini4j.Profile.Section;
import org.json.JSONException;
import org.json.JSONObject;

public class UpdateResourceInformation extends MarcProcessorBase implements IProcessHandler {
	private Connection conn = null;
	private boolean updateExisting;

	public void doCronProcess(Section processSettings, Section generalSettings, Logger logger) {
		System.out.println("Updating Resource information");
		if (!super.loadConfig(processSettings, generalSettings, logger)) {
			return;
		}
		// Load configuration
		String databaseConnectionInfo = generalSettings.get("database");
		if (databaseConnectionInfo == null || databaseConnectionInfo.length() == 0) {
			logger.error("Database connection information not found in General Settings.  Please specify connection information in a database key.");
			return;
		}

		String vufindUrl = generalSettings.get("vufindUrl");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
		}
		
		String updateExisting = processSettings.get("updateExisting");
		if (updateExisting == null || updateExisting.length() == 0) {
			logger.warn("Unable to get updateExisting seting in Process settings, defaulting to true.");
			this.updateExisting = true;
		}else{
			this.updateExisting = Boolean.parseBoolean(updateExisting);
		}

		// Load all resources hat do not have an author set.
		try {
			conn = DriverManager.getConnection(databaseConnectionInfo);
			if (!processMarcFiles(logger)) {
				logger.error("Unable to process marc files");
				return;
			}
			// Disconnect from the database
			conn.close();
		} catch (SQLException ex) {
			// handle any errors
			System.out.println("Error establishing connection to database " + databaseConnectionInfo + " " + ex.toString());
			return;
		} catch (FileNotFoundException e) {
			logger.error("Error reading marc files " + e.toString());
			return;
		}
	}

	PreparedStatement resourceUpdateStmt = null;
	PreparedStatement resourceInsertStmt = null;
	PreparedStatement existingResourceStmt = null;
	@Override
	protected boolean processMarcRecord(BasicMarcInfo recordInfo, Logger logger) {
		try {
			// Check to see if the record already exists
			if (existingResourceStmt == null){
				existingResourceStmt = conn.prepareStatement("SELECT id from resource where record_id = ?");
			}
			existingResourceStmt.setString(1, recordInfo.getId());
			ResultSet existingResourceResult = existingResourceStmt.executeQuery();
			if (existingResourceResult.next()) {
				if (!updateExisting){
					return true;
				}
				// Update the existing record
				if (resourceUpdateStmt == null){
					String sql = "UPDATE resource SET title = ?, title_sort = ?, author = ?, isbn = ?, upc = ?, format = ?, format_category = ? WHERE record_id = ?";
					resourceUpdateStmt =conn.prepareStatement(sql);
				}
				String author = recordInfo.getAuthors().size() > 0 ? recordInfo.getAuthors().get(0) : "";
				// Update resource SQL
				resourceUpdateStmt.setString(1, trimTo(200, recordInfo.getTitle()));
				resourceUpdateStmt.setString(2, trimTo(200, recordInfo.getSortTitle()));
				resourceUpdateStmt.setString(3, trimTo(255, author));
				resourceUpdateStmt.setString(4, trimTo(13, recordInfo.getIsbn()));
				resourceUpdateStmt.setString(5, trimTo(13, recordInfo.getUpc()));
				resourceUpdateStmt.setString(6, trimTo(50, recordInfo.getFormat(formatMap)));
				resourceUpdateStmt.setString(7, trimTo(50, recordInfo.getFormatCategory(formatCategoryMap)));
				resourceUpdateStmt.setString(8, recordInfo.getId());

				int rowsUpdated = resourceUpdateStmt.executeUpdate();
				if (rowsUpdated == 0) {
					logger.debug("Unable to update resource for record " + recordInfo.getId() + " " + existingResourceResult.getString("id"));
				} else {
					//logger.info("Updated resource for " + recordInfo.getId() + " " + existingResourceResult.getString("id"));
				}
			} else {
				//Insert a new record for the resource
				if (resourceInsertStmt == null){
					String sql = "INSERT INTO resource (title, title_sort, author, isbn, upc, format, format_category, record_id, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
					resourceInsertStmt = conn.prepareStatement(sql);
				}
				String author = recordInfo.getAuthors().size() > 0 ? recordInfo.getAuthors().get(0) : "";
				// Update resource SQL
				resourceInsertStmt.setString(1, trimTo(200, recordInfo.getTitle()));
				resourceInsertStmt.setString(2, trimTo(200, recordInfo.getSortTitle()));
				resourceInsertStmt.setString(3, trimTo(255, author));
				resourceInsertStmt.setString(4, trimTo(13, recordInfo.getIsbn()));
				resourceInsertStmt.setString(5, trimTo(13, recordInfo.getUpc()));
				resourceInsertStmt.setString(6, trimTo(50, recordInfo.getFormat(formatMap)));
				resourceInsertStmt.setString(7, trimTo(50, recordInfo.getFormatCategory(formatCategoryMap)));
				resourceInsertStmt.setString(8, recordInfo.getId());
				resourceInsertStmt.setString(9, "VuFind");

				int rowsUpdated = resourceInsertStmt.executeUpdate();
				if (rowsUpdated == 0) {
					logger.debug("Unable to insert record " + recordInfo.getId());
				} else {
					//logger.info("Inserted record " + recordInfo.getId());
				}
			}
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error updating resource for record " + recordInfo.getId() + " " + ex.toString());
			System.out.println(recordInfo.getTitle());
		}
		// TODO Auto-generated method stub
		return true;
	}

	private String trimTo(int maxCharacters, String stringToTrim) {
		if (stringToTrim.length() > maxCharacters){
			stringToTrim = stringToTrim.substring(0, maxCharacters);
		}
		return stringToTrim;
	}
}
