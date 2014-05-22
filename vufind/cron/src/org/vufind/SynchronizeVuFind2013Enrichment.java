package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile;

import java.sql.*;
import java.sql.Date;
import java.util.*;

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
	private Connection vufind2013connection;
	private Connection econtent2013connection;
	private PreparedStatement getUserFromVuFind2013Stmt;
	private PreparedStatement getUserFromVuFind2014Stmt;
	private PreparedStatement addUserToVuFind2014Stmt;
	private PreparedStatement getGroupedWorkStmt;
	private PreparedStatement getGroupedWorkForOverDriveStmt;
	private PreparedStatement getExistingTagStmt;
	private PreparedStatement addTagStmt;
	private PreparedStatement getIlsIdForEContentRecordStmt;
	private PreparedStatement getExistingListStmt;
	private PreparedStatement addListStmt;
	private PreparedStatement getExistingListTitleStmt;
	private PreparedStatement addTitleToListStmt;
	private PreparedStatement addWorkReviewStmt;
	private PreparedStatement getExistingWorkReviewStmt;

	@Override
	public void doCronProcess(String servername, Ini configIni, Profile.Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Synchronize VuFind 2013 Enrichment");
		processLog.saveToDatabase(vufindConn, logger);
		this.logger = logger;
		try {
			//TODO: Get the time the last synchronization was done

			//Establish connection to VuFind 2013 instance
			vufind2013connection = getVuFind2013Connection(configIni);
			econtent2013connection = getEContent2013Connection(configIni);
			if (vufind2013connection != null){
				//Initialize prepared statements we will need later
				getUserFromVuFind2013Stmt = vufind2013connection.prepareStatement("SELECT * from user where username = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				getUserFromVuFind2014Stmt = vufindConn.prepareStatement("SELECT id from user where username = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				addUserToVuFind2014Stmt = vufindConn.prepareStatement("INSERT INTO user (username,password,firstname,lastname,email,cat_username,cat_password,created,homeLocationId,myLocation1Id,myLocation2Id,bypassAutoLogout,displayName,phone,patronType,disableRecommendations,disableCoverArt,overdriveEmail,promptForOverdriveEmail) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);

				getGroupedWorkStmt = vufindConn.prepareStatement("SELECT permanent_id FROM grouped_work inner join grouped_work_primary_identifiers on grouped_work_id = grouped_work.id WHERE type = 'ils' and identifier = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				getGroupedWorkForOverDriveStmt = vufindConn.prepareStatement("SELECT permanent_id FROM grouped_work inner join grouped_work_primary_identifiers on grouped_work_id = grouped_work.id WHERE type = 'overdrive' and identifier = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				getIlsIdForEContentRecordStmt = econtent2013connection.prepareStatement("SELECT ilsId, source, externalId from econtent_record where id = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);

				getExistingTagStmt = vufindConn.prepareStatement("SELECT * FROM user_tags WHERE groupedRecordPermanentId = ? AND userId = ? and tag = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				addTagStmt = vufindConn.prepareStatement("INSERT INTO user_tags (groupedRecordPermanentId, userId, tag, dateTagged) VALUES (?, ?, ?, ?)");

				getExistingListStmt = vufindConn.prepareStatement("SELECT id FROM user_list WHERE user_id = ? AND title = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				addListStmt = vufindConn.prepareStatement("INSERT into user_list (user_id, title, description, public, dateUpdated, created, deleted) VALUES (?, ?, ?, ?, ?, ?, 0)", PreparedStatement.RETURN_GENERATED_KEYS);
				getExistingListTitleStmt = vufindConn.prepareStatement("SELECT * FROM user_list_entry WHERE listId = ? and groupedWorkPermanentId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				addTitleToListStmt = vufindConn.prepareStatement("INSERT into user_list_entry (listId, groupedWorkPermanentId, notes, dateAdded, weight) VALUES (?, ?, ?, ?, 0)");

				getExistingWorkReviewStmt = vufindConn.prepareStatement("SELECT id from user_work_review WHERE groupedRecordPermanentId = ? AND userId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				addWorkReviewStmt = vufindConn.prepareStatement("INSERT into user_work_review (groupedRecordPermanentId, userId, rating, review, dateRated) VALUES (?, ?, ?, ?, ?)");

				//TODO: synchronizeNotInterested();
				synchronizeRatingsAndReviews();
				synchronizeLists();
				synchronizeTags();

			} else{
				logger.error("Could not connect to VuFind 2013");
			}

		} catch (Exception e) {
			logger.error("Error synchronizing VuFind 2013 data to VuFind 2014");
		}finally{
			processLog.setFinished();
			processLog.saveToDatabase(vufindConn, logger);
		}
	}

	private void synchronizeRatingsAndReviews() {
		try{
			PreparedStatement getRatingsWithReviews = vufind2013connection.prepareStatement("SELECT username, source, record_id, rating, comment, comments.created FROM user_rating LEFT OUTER JOIN comments ON comments.user_id = user_rating.userId AND comments.resource_id = user_rating.resourceid INNER JOIN user on userid = user.id INNER JOIN resource on resourceid = resource.id WHERE comment is NOT NULL");
			PreparedStatement getRatingsWithoutReviews = vufind2013connection.prepareStatement("SELECT username, source, record_id, rating FROM user_rating LEFT OUTER JOIN comments ON comments.user_id = user_rating.userId AND comments.resource_id = user_rating.resourceid INNER JOIN user on userid = user.id INNER JOIN resource on resourceid = resource.id WHERE comment is NULL");
			PreparedStatement getReviewsWithoutRatings = vufind2013connection.prepareStatement("SELECT username, source, record_id, comment, comments.created FROM user_rating RIGHT OUTER JOIN comments ON comments.user_id = user_rating.userId AND comments.resource_id = user_rating.resourceid INNER JOIN user on user_id = user.id INNER JOIN resource on resource_id = resource.id WHERE rating is NULL");

			//Process ratings with reviews
			ResultSet ratingsWithReviewsRS = getRatingsWithReviews.executeQuery();
			while (ratingsWithReviewsRS.next()){
				String username = ratingsWithReviewsRS.getString("username");
				Long userId = synchronizeUser(username);
				if (userId != null){
					String groupedWorkId = getWorkForResource(ratingsWithReviewsRS.getString("source"), ratingsWithReviewsRS.getString("record_id"));
					if (groupedWorkId != null){
						Integer rating = ratingsWithReviewsRS.getInt("rating");
						String review = ratingsWithReviewsRS.getString("comment");
						Date dateRated = ratingsWithReviewsRS.getDate("created");
						Long timeRated = dateRated == null ? null : dateRated.getTime() / 1000;
						addWorkReview(groupedWorkId, userId, rating, review, timeRated);
					}
				}
			}

			//Process ratings without reviews
			ResultSet ratingsWithoutReviewsRS = getRatingsWithoutReviews.executeQuery();
			while (ratingsWithoutReviewsRS.next()){
				String username = ratingsWithoutReviewsRS.getString("username");
				Long userId = synchronizeUser(username);
				if (userId != null){
					String groupedWorkId = getWorkForResource(ratingsWithoutReviewsRS.getString("source"), ratingsWithoutReviewsRS.getString("record_id"));
					if (groupedWorkId != null){
						Integer rating = ratingsWithoutReviewsRS.getInt("rating");
						addWorkReview(groupedWorkId, userId, rating, "", null);
					}
				}
			}

			//Process reviews without ratings
			ResultSet reviewsWithoutRatingsRS = getReviewsWithoutRatings.executeQuery();
			while (reviewsWithoutRatingsRS.next()){
				String username = reviewsWithoutRatingsRS.getString("username");
				Long userId = synchronizeUser(username);
				if (userId != null){
					String groupedWorkId = getWorkForResource(reviewsWithoutRatingsRS.getString("source"), reviewsWithoutRatingsRS.getString("record_id"));
					if (groupedWorkId != null){
						String review = reviewsWithoutRatingsRS.getString("comment");
						Date dateRated = reviewsWithoutRatingsRS.getDate("created");
						Long timeRated = dateRated == null ? null : dateRated.getTime() / 1000;
						addWorkReview(groupedWorkId, userId, -1, review, timeRated);
					}
				}
			}
		}catch (Exception e){
			logger.error("Error sycnhronizing ratings and reviews", e);
		}
	}

	private void addWorkReview(String groupedWorkId, Long userId, Integer rating, String review, Long dateRated) {
		try{
			//Check to see if there is already a review for the statement
			getExistingWorkReviewStmt.setString(1, groupedWorkId);
			getExistingWorkReviewStmt.setLong(2, userId);
			ResultSet existingWorkReviewRS = getExistingWorkReviewStmt.executeQuery();
			if (!existingWorkReviewRS.next()){
				addWorkReviewStmt.setString(1, groupedWorkId);
				addWorkReviewStmt.setLong(2, userId);
				addWorkReviewStmt.setInt(3, rating);
				if (review == null) review = "";
				addWorkReviewStmt.setString(4, review);
				if (dateRated == null){
					addWorkReviewStmt.setLong(5, new java.util.Date().getTime() / 1000);
				}else{
					addWorkReviewStmt.setLong(5, dateRated);
				}
				addWorkReviewStmt.executeUpdate();
			}
		}catch (Exception e){
			logger.error("Error adding work review", e);
		}
	}

	private void synchronizeLists() {
		try{
			//Get a list of all lists in VuFind 2013
			//TODO: Filter based on last time the synchronization was run, filter based on
			PreparedStatement getListsStmt = vufind2013connection.prepareStatement("SELECT user_list.id as listId, username, password, title, description, public FROM user_list inner join user on user_id = user.id");
			PreparedStatement getListTitlesStmt = vufind2013connection.prepareStatement("SELECT source, record_id, notes, saved FROM user_resource INNER JOIN resource on resource_id = resource.id WHERE list_id = ?");
			ResultSet vufind2013Lists = getListsStmt.executeQuery();
			while (vufind2013Lists.next()){
				//Check to see if we have a user with the given username (unique id in Sierra)
				String username = vufind2013Lists.getString("username");
				Long userId = synchronizeUser(username);
				if (userId != null){
					//Check to see if the list already exists within VuFind 2014
					String listTitle = vufind2013Lists.getString("title");
					Long vufind2013ListId = vufind2013Lists.getLong("listId");
					getExistingListStmt.setLong(1, userId);
					getExistingListStmt.setString(2, listTitle);
					ResultSet existingListRS = getExistingListStmt.executeQuery();
					Long listId;
					if (!existingListRS.next()){
						//Create a new list
						addListStmt.setLong(1, userId);
						addListStmt.setString(2, listTitle);
						addListStmt.setString(3, vufind2013Lists.getString("description"));
						addListStmt.setLong(4, vufind2013Lists.getLong("public"));
						addListStmt.setLong(5, new java.util.Date().getTime() / 1000);
						addListStmt.setLong(6, new java.util.Date().getTime() / 1000);
						addListStmt.executeUpdate();
						ResultSet generatedKeysRS = addListStmt.getGeneratedKeys();
						generatedKeysRS.next();
						listId = generatedKeysRS.getLong(1);
					}else{
						listId = existingListRS.getLong("id");
					}

					//Get a list of all titles in the list
					getListTitlesStmt.setLong(1, vufind2013ListId);
					ResultSet vuFind2013ListTitlesRS = getListTitlesStmt.executeQuery();
					while (vuFind2013ListTitlesRS.next()){
						String groupedWorkId = getWorkForResource(vuFind2013ListTitlesRS.getString("source"), vuFind2013ListTitlesRS.getString("record_id"));
						if (groupedWorkId != null){
							//Check to see if the work is already on the list
							getExistingListTitleStmt.setLong(1, listId);
							getExistingListTitleStmt.setString(2, groupedWorkId);
							ResultSet existingListTitleRS = getExistingListTitleStmt.executeQuery();
							if (!existingListTitleRS.next()){
								//Add the title to the list
								addTitleToListStmt.setLong(1, listId);
								addTitleToListStmt.setString(2, groupedWorkId);
								addTitleToListStmt.setString(3, vuFind2013ListTitlesRS.getString("notes"));
								Date dateAdded = vuFind2013ListTitlesRS.getDate("saved");
								addTitleToListStmt.setLong(4, dateAdded.getTime() / 1000);
								addTitleToListStmt.executeUpdate();
							}
						}
					}
				}
			}
		} catch (Exception e){
			logger.error("Error synchronizing lists", e);
		}
	}

	private void synchronizeTags() {
		//Get a list of all tags for all users
		try{
			//TODO: limit to only loading tags added after the last synchronization
			String vufind2013Tags = "SELECT tag, record_id, source, username, password, title, author, posted from resource_tags inner join tags on tags.id = resource_tags.tag_id inner join resource on resource_id = resource.id inner join user on user_id = user.id";
			PreparedStatement vufind2013TagsStmt = vufind2013connection.prepareStatement(vufind2013Tags);
			ResultSet vufind2013TagsRS = vufind2013TagsStmt.executeQuery();
			while (vufind2013TagsRS.next()){
				//Check to see if we have a user with the given username (unique id in Sierra)
				String username = vufind2013TagsRS.getString("username");
				Long userId = synchronizeUser(username);
				if (userId != null){
					//Get the work for the old resource
					String resourceSource = vufind2013TagsRS.getString("source");
					String resourceRecordId = vufind2013TagsRS.getString("record_id");
					String groupedWorkId = getWorkForResource(resourceSource, resourceRecordId);
					if (groupedWorkId != null){
						String tag = vufind2013TagsRS.getString("tag");
						Date datePosted = vufind2013TagsRS.getDate("posted");
						//Check to see if we have already added a tag to this work for the user.
						getExistingTagStmt.setString(1, groupedWorkId);
						getExistingTagStmt.setLong(2, userId);
						getExistingTagStmt.setString(3, tag);
						ResultSet existingTagRS = getExistingTagStmt.executeQuery();

						if (!existingTagRS.next()){
							//Add to VuFind 2014 if the tag doesn't exist already
							addTagStmt.setString(1, groupedWorkId);
							addTagStmt.setLong(2, userId);
							addTagStmt.setString(3, tag);
							addTagStmt.setLong(4, datePosted.getTime() / 1000);
							addTagStmt.executeUpdate();
						}
						existingTagRS.close();
					}
				}
			}
			vufind2013TagsRS.close();
		}catch (Exception e){
			logger.error("Error synchronizing tags from VuFind 2013", e);
		}
	}

	private HashMap<String, String> processedResources = new HashMap<String, String>();
	private String getWorkForResource(String resourceSource, String resourceRecordId) {
		String resourceKey = resourceSource + "_" + resourceRecordId;
		if (processedResources.containsKey(resourceKey)){
			return processedResources.get(resourceKey);
		}
		String permanentId = null;
		try{
			if (resourceSource.equalsIgnoreCase("vufind")){
				//Get the grouped work
				getGroupedWorkStmt.setString(1, resourceRecordId);
				ResultSet groupedWorkRS = getGroupedWorkStmt.executeQuery();
				if (groupedWorkRS.next()){
					permanentId = groupedWorkRS.getString("permanent_id");
				}else{
					logger.warn("Could not find grouped work for ILS record " + resourceRecordId);
				}
			}else{
				//Get the ils id for the econtent record
				getIlsIdForEContentRecordStmt.setLong(1, Long.parseLong(resourceRecordId));
				ResultSet getIlsIdForEContentRecordRS = getIlsIdForEContentRecordStmt.executeQuery();
				if (getIlsIdForEContentRecordRS.next()){
					String ilsId = getIlsIdForEContentRecordRS.getString("ilsId");
					if (ilsId == null){
						String econtentSource = getIlsIdForEContentRecordRS.getString("source");
						String externalId = getIlsIdForEContentRecordRS.getString("externalId");
						if (econtentSource.equalsIgnoreCase("overdrive")){
							getGroupedWorkForOverDriveStmt.setString(1, externalId);
							ResultSet groupedWorkRS = getGroupedWorkForOverDriveStmt.executeQuery();
							if (groupedWorkRS.next()){
								permanentId = groupedWorkRS.getString("permanent_id");
							} else{
								logger.warn("Could not find grouped work for overdrive record " + externalId);
							}
						}else{
							logger.warn("Could not handle getting grouped work for " + econtentSource + " " + resourceRecordId);
						}
					}else{
						getGroupedWorkStmt.setString(1, ilsId);
						ResultSet groupedWorkRS = getGroupedWorkStmt.executeQuery();
						if (groupedWorkRS.next()){
							permanentId = groupedWorkRS.getString("permanent_id");
						}else{
							logger.warn("Could not find grouped work for ils record " + ilsId + " referenced from econtent record " + resourceRecordId);
							//TODO: Some of these are because the marc record refers to an OverDrive record that we are suppressing in VuFind 2014
						}
					}
				}else{
					logger.warn("Could not find econtent record for econtent record id " + resourceRecordId);
				}
			}
		}catch (Exception e){
			logger.error("Error getting work for resource", e);
		}
		if (permanentId != null){
			processedResources.put(resourceKey, permanentId);
		}
		return permanentId;
	}

	private HashMap<String, Long> processedUsers = new HashMap<String, Long>();
	private Long synchronizeUser(String username) {
		if (processedUsers.containsKey(username)){
			return processedUsers.get(username);
		}

		Long userId = null;
		try{
			//Check to see if we have the user in VuFind 2014 already
			getUserFromVuFind2014Stmt.setString(1, username);
			ResultSet vufind2014UserRS = getUserFromVuFind2014Stmt.executeQuery();
			boolean userExists = false;
			while (vufind2014UserRS.next()){
				userExists = true;
				userId = vufind2014UserRS.getLong("id");
			}
			if (!userExists){
				//Need to transfer information from VuFind 2013 to VuFind 2014
				getUserFromVuFind2013Stmt.setString(1, username);
				ResultSet vufind2013User = getUserFromVuFind2013Stmt.executeQuery();
				while (vufind2013User.next()){
					addUserToVuFind2014Stmt.setString(1, vufind2013User.getString("username"));
					addUserToVuFind2014Stmt.setString(2, vufind2013User.getString("password"));
					addUserToVuFind2014Stmt.setString(3, vufind2013User.getString("firstname"));
					addUserToVuFind2014Stmt.setString(4, vufind2013User.getString("lastname"));
					addUserToVuFind2014Stmt.setString(5, vufind2013User.getString("email"));
					addUserToVuFind2014Stmt.setString(6, vufind2013User.getString("cat_username"));
					addUserToVuFind2014Stmt.setString(7, vufind2013User.getString("cat_password"));
					addUserToVuFind2014Stmt.setDate(8, vufind2013User.getDate("created"));
					addUserToVuFind2014Stmt.setLong(9, vufind2013User.getLong("homeLocationId"));
					addUserToVuFind2014Stmt.setLong(10, vufind2013User.getLong("myLocation1Id"));
					addUserToVuFind2014Stmt.setLong(11, vufind2013User.getLong("myLocation2Id"));
					addUserToVuFind2014Stmt.setLong(12, vufind2013User.getLong("bypassAutoLogout"));
					addUserToVuFind2014Stmt.setString(13, vufind2013User.getString("displayName"));
					addUserToVuFind2014Stmt.setString(14, vufind2013User.getString("phone"));
					addUserToVuFind2014Stmt.setLong(15, vufind2013User.getLong("patronType"));
					addUserToVuFind2014Stmt.setLong(16, vufind2013User.getLong("disableRecommendations"));
					addUserToVuFind2014Stmt.setLong(17, vufind2013User.getLong("disableCoverArt"));
					addUserToVuFind2014Stmt.setString(18, vufind2013User.getString("overdriveEmail"));
					addUserToVuFind2014Stmt.setLong(19, vufind2013User.getLong("promptForOverdriveEmail"));
					addUserToVuFind2014Stmt.executeUpdate();
					ResultSet generatedKeys = addUserToVuFind2014Stmt.getGeneratedKeys();
					while (generatedKeys.next()){
						userId = generatedKeys.getLong(1);
					}
				}
			}
			processedUsers.put(username, userId);
		}catch (Exception e){
			logger.error("Error synchronizing user", e);
		}
		return userId;
	}

	private Connection getVuFind2013Connection(Ini configIni) {
		String connectionInfo = Util.cleanIniValue(configIni.get("Database", "database_vufind_2013_jdbc"));
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

	private Connection getEContent2013Connection(Ini configIni) {
		String connectionInfo = Util.cleanIniValue(configIni.get("Database", "database_econtent_2013_jdbc"));
		if (connectionInfo == null || connectionInfo.length() == 0) {
			logger.error("EContent Database connection information not found in General Settings.  Please specify connection information in a database key.");
			return null;
		}
		Connection econtent2013Conn;
		try {
			econtent2013Conn = DriverManager.getConnection(connectionInfo);
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error establishing connection to database " + connectionInfo, ex);
			return null;
		}
		return econtent2013Conn;
	}
}
