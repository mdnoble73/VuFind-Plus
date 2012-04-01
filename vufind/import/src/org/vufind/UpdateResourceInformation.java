package org.vufind;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Date;

import org.apache.log4j.Logger;
import org.ini4j.Ini;

public class UpdateResourceInformation implements IMarcRecordProcessor, IRecordProcessor{
	private Logger logger;
	private Connection conn = null;
	
	private PreparedStatement resourceUpdateStmt = null;
	private PreparedStatement resourceInsertStmt = null;
	private PreparedStatement existingResourceStmt = null;

	public boolean init(Ini configIni, String serverName, Logger logger) {
		this.logger = logger;
		// Load configuration
		String databaseConnectionInfo = Util.cleanIniValue(configIni.get("Database", "database_vufind_jdbc"));
		if (databaseConnectionInfo == null || databaseConnectionInfo.length() == 0) {
			logger.error("Database connection information not found in General Settings.  Please specify connection information in a database key.");
			return false;
		}
		try {
			conn = DriverManager.getConnection(databaseConnectionInfo);
		} catch (SQLException e) {
			logger.error("Could not connect to database", e);
			return false;
		}

		String vufindUrl = configIni.get("Site", "url");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
		}
		
		try {
			// Check to see if the record already exists
			if (existingResourceStmt == null){
				existingResourceStmt = conn.prepareStatement("SELECT id, marc_checksum from resource where record_id = ? and source = 'VuFind'");
			}
			
			if (resourceUpdateStmt == null){
				String sql = "UPDATE resource SET title = ?, title_sort = ?, author = ?, isbn = ?, upc = ?, format = ?, format_category = ?, marc_checksum=?, date_updated=? WHERE id = ?";
				resourceUpdateStmt =conn.prepareStatement(sql);
			}
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Unable to setup prepared statements", ex);
			return false;
		}
		return true;
		
	}

	@Override
	public boolean processMarcRecord(MarcProcessor processor, MarcRecordDetails recordInfo, int recordStatus, Logger logger) {
		try {
			existingResourceStmt.setString(1, recordInfo.getId());
			ResultSet existingResourceResult = existingResourceStmt.executeQuery();
			if (existingResourceResult.next()) {
				// Check to see if the record has changed 
				long existingHashCode = existingResourceResult.getLong("marc_checksum");
				if (existingHashCode == recordInfo.getChecksum()){
					//logger.debug("record has not changed, do not update it.");
					return true;
				}
				
				// Update the existing record
				String title = recordInfo.getTitle();
				String author = recordInfo.getAuthor();
				
				// Update resource SQL
				resourceUpdateStmt.setString(1, Util.trimTo(200, title));
				resourceUpdateStmt.setString(2, Util.trimTo(200, recordInfo.getSortTitle()));
				resourceUpdateStmt.setString(3, Util.trimTo(255, author));
				resourceUpdateStmt.setString(4, Util.trimTo(13, recordInfo.getIsbn()));
				resourceUpdateStmt.setString(5, Util.trimTo(13, recordInfo.getFirstFieldValueInSet("upc")));
				resourceUpdateStmt.setString(6, Util.trimTo(50, recordInfo.getFirstFieldValueInSet("format")));
				resourceUpdateStmt.setString(7, Util.trimTo(50, recordInfo.getFirstFieldValueInSet("format_category")));
				resourceUpdateStmt.setLong(8, recordInfo.getChecksum());
				resourceUpdateStmt.setLong(9, new Date().getTime() / 1000);
				resourceUpdateStmt.setLong(10, existingResourceResult.getLong(1));

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
				String author = recordInfo.getAuthor();
				// Update resource SQL
				resourceInsertStmt.setString(1, Util.trimTo(200, recordInfo.getTitle()));
				resourceInsertStmt.setString(2, Util.trimTo(200, recordInfo.getSortTitle()));
				resourceInsertStmt.setString(3, Util.trimTo(255, author));
				resourceInsertStmt.setString(4, Util.trimTo(13, recordInfo.getIsbn()));
				resourceInsertStmt.setString(5, Util.trimTo(13, recordInfo.getFirstFieldValueInSet("upc")));
				resourceInsertStmt.setString(6, Util.trimTo(50, recordInfo.getFirstFieldValueInSet("format")));
				resourceInsertStmt.setString(7, Util.trimTo(50, recordInfo.getFirstFieldValueInSet("format_category")));
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

	@Override
	public void finish() {
		try {
			conn.close();
		} catch (SQLException e) {
			logger.error("Unable to close connection", e);
		}
	}
}
