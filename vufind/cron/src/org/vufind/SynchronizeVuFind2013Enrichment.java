package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile;

import java.sql.*;

/**
 * Synchronizes any changes made to a VuFind 2013 installation to the current installation.
 *
 * VuFind-Plus
 * User: Mark Noble
 * Date: 5/20/14
 * Time: 3:36 PM
 */
public class SynchronizeVuFind2013Enrichment implements IProcessHandler {
	private Logger logger;
	private CronProcessLogEntry processLog;
	private Connection vufindConn;

	@Override
	public void doCronProcess(String servername, Ini configIni, Profile.Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Synchronize VuFind 2013 Enrichment");
		processLog.saveToDatabase(vufindConn, logger);
		this.vufindConn = vufindConn;
		try {
			//Get the time the last synchronization was done

			//Establish connection to VuFind 2013 instance
			Connection vufind2013connection = getVuFind2013Connection(configIni);
			if (vufind2013connection != null){
				synchronizeTags(vufind2013connection);

			}

		} catch (Exception e) {
			logger.error("Error synchronizing VuFind 2013 data to VuFind 2014");
		}finally{
			processLog.setFinished();
			processLog.saveToDatabase(vufindConn, logger);
		}
	}

	private void synchronizeTags(Connection vufind2013connection) {
		//Get a list of all tags for all users
		try{
			//TODO: limit to only loading tags added after the last synchronization
			String vufind2013Tags = "SELECT tag, record_id, source, username, password, title, author from resource_tags inner join tags on tags.id = resource_tags.tag_id inner join resource on resource_id = resource.id inner join user on user_id = user.id";
			PreparedStatement vufind2013TagsStmt = vufind2013connection.prepareStatement(vufind2013Tags);
			ResultSet vufind2013TagsRS = vufind2013TagsStmt.executeQuery();
			while (vufind2013TagsRS.next()){
				//Check to see if we have a user with the given username (unique id in Sierra)
				String username = vufind2013TagsRS.getString("username");

				//Get the work for the old resource

				//Add to VuFind 2014 if the tag doesn't exist already
			}
		}catch (Exception e){
			logger.error("Error synchronizing tags from VuFind 2013", e);
		}
	}

	private Connection getVuFind2013Connection(Ini configIni) {
		String connectionInfo = configIni.get("Database", "database_vufind_2013_jdbc");
		if (connectionInfo == null || connectionInfo.length() == 0) {
			logger.error("VuFind Database connection information not found in General Settings.  Please specify connection information in a database key.");
			return null;
		}
		Connection vufind2013Conn;
		try {
			vufind2013Conn = DriverManager.getConnection(connectionInfo);
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error establishing connection to database " + connectionInfo, ex);
			return null;
		}
		return vufind2013Conn;
	}
}
