package org.vufind;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile;
import org.marc4j.MarcPermissiveStreamReader;
import org.marc4j.MarcReader;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;

import java.io.File;
import java.io.FileInputStream;
import java.sql.*;
import java.sql.Date;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;
import java.util.zip.DataFormatException;

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
	private String librariesToSynchronize = null;
	private Connection vufind2013connection;
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
	private PreparedStatement getExistingNotInterestedStmt;
	private PreparedStatement addNotInterestedStmt;
	private String individualMarcPath;
	private PreparedStatement getExistingMaterialsRequestStatusStmt;
	private PreparedStatement addMaterialsRequestStatusStmt;
	private PreparedStatement updateMaterialsRequestStatusStmt;
	private PreparedStatement getExistingMaterialsRequestStmt;
	private PreparedStatement addMaterialsRequestStmt;
	private PreparedStatement updateMaterialsRequestStmt;

	@Override
	public void doCronProcess(String servername, Ini configIni, Profile.Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Synchronize VuFind 2013 Enrichment");
		processLog.saveToDatabase(vufindConn, logger);
		this.logger = logger;
		this.individualMarcPath = Util.cleanIniValue(configIni.get("Reindex", "individualMarcPath"));
		if (processSettings.containsKey("librariesToSynchronize")) {
			librariesToSynchronize = Util.cleanIniValue(processSettings.get("librariesToSynchronize"));
		}
		try {
			//Get the time the last synchronization was done so we can only synchronize new things?

			//Establish connection to VuFind 2013 instance
			vufind2013connection = getVuFind2013Connection(configIni);
			Connection econtent2013connection = getEContent2013Connection(configIni);
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

				getExistingNotInterestedStmt = vufindConn.prepareStatement("SELECT * FROM user_not_interested where userId = ? AND groupedRecordPermanentId = ?", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				addNotInterestedStmt = vufindConn.prepareStatement("INSERT INTO user_not_interested (userId, groupedRecordPermanentId, dateMarked) VALUES (?, ?, ?)");

				getExistingMaterialsRequestStatusStmt = vufindConn.prepareStatement("SELECT * FROM materials_request_status WHERE description = ? AND libraryId = ?");
				addMaterialsRequestStatusStmt = vufindConn.prepareStatement("INSERT INTO materials_request_status (description, isDefault, sendEmailToPatron, emailTemplate, isOpen, isPatronCancel, libraryId) VALUES (?, ?, ?, ?, ?, ?, ?)");
				updateMaterialsRequestStatusStmt = vufindConn.prepareStatement("UPDATE materials_request_status SET isDefault = ?, sendEmailToPatron = ?, emailTemplate = ?, isOpen = ?, isPatronCancel = ? WHERE description = ? AND libraryId = ?");

				getExistingMaterialsRequestStmt = vufindConn.prepareStatement("SELECT * FROM materials_request WHERE createdBy = ? and dateCreated = ?");
				addMaterialsRequestStmt = vufindConn.prepareStatement("INSERT INTO materials_request (title, author, format, ageLevel, isbn, oclcNumber, publisher, publicationYear, articleInfo, abridged, about, comments, status, dateCreated, createdBy, dateUpdated, emailSent, holdsCreated, email, phone, season, magazineTitle, upc, issn, bookType, subFormat, magazineDate, magazineVolume, magazinePageNumbers, placeHoldWhenAvailable, holdPickupLocation, bookmobileStop, illItem, magazineNumber) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
				updateMaterialsRequestStmt = vufindConn.prepareStatement("UPDATE materials_request SET title = ?, author = ?, format = ?, ageLevel = ?, isbn = ?, oclcNumber = ?, publisher = ?, publicationYear = ?, articleInfo = ?, abridged = ?, about = ?, comments = ?, status = ?, dateUpdated = ?, emailSent = ?, holdsCreated = ?, email = ?, phone = ?, season = ?, magazineTitle = ?, upc = ?, issn = ?, bookType = ?, subFormat = ?, magazineDate = ?, magazineVolume = ?, magazinePageNumbers = ?, placeHoldWhenAvailable = ?, holdPickupLocation = ?, bookmobileStop = ?, illItem = ?, magazineNumber = ? WHERE dateCreated = ? AND createdBy = ?");

				synchronizeMaterialsRequests();
				//TODO:  Editorial Reviews
				//synchronizeEditorialReviews();
				//TODO: eContent Ratings
				//synchronizeEContentRatings();
				synchronizeNotInterested();
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

	private void synchronizeMaterialsRequests() {
		try{
			//Synchronize statuses
			PreparedStatement getMaterialsRequestStatusesVuFind2013;
			if (librariesToSynchronize == null) {
				getMaterialsRequestStatusesVuFind2013 = vufind2013connection.prepareStatement("SELECT * FROM materials_request_status ");
			}else{
				getMaterialsRequestStatusesVuFind2013 = vufind2013connection.prepareStatement("SELECT materials_request_status . *\n" +
						"FROM `materials_request_status`\n" +
						"INNER JOIN library ON library.libraryId = materials_request_status.libraryId\n" +
						"WHERE subdomain\n" +
						"IN (\n" +
						librariesToSynchronize +
						")");
			}
			ResultSet materialsRequestStatusesVuFind2013 = getMaterialsRequestStatusesVuFind2013.executeQuery();
			while (materialsRequestStatusesVuFind2013.next()){
				String description = materialsRequestStatusesVuFind2013.getString("description");
				Long libraryId = materialsRequestStatusesVuFind2013.getLong("libraryId");
				//Check to see if the status exists already
				getExistingMaterialsRequestStatusStmt.setString(1, description);
				getExistingMaterialsRequestStatusStmt.setLong(2, libraryId);
				ResultSet existingMaterialsRequestStatusRS = getExistingMaterialsRequestStatusStmt.executeQuery();
				if (existingMaterialsRequestStatusRS.next()){
					updateMaterialsRequestStatusStmt.setLong(1, materialsRequestStatusesVuFind2013.getLong("isDefault"));
					updateMaterialsRequestStatusStmt.setLong(2, materialsRequestStatusesVuFind2013.getLong("sendEmailToPatron"));
					updateMaterialsRequestStatusStmt.setString(3, materialsRequestStatusesVuFind2013.getString("emailTemplate"));
					updateMaterialsRequestStatusStmt.setLong(4, materialsRequestStatusesVuFind2013.getLong("isOpen"));
					updateMaterialsRequestStatusStmt.setLong(5, materialsRequestStatusesVuFind2013.getLong("isPatronCancel"));
					updateMaterialsRequestStatusStmt.setString(6, description);
					updateMaterialsRequestStatusStmt.setLong(7, libraryId);
					int rowsUpdated = updateMaterialsRequestStatusStmt.executeUpdate();
					if (rowsUpdated == 0){
						logger.warn("No rows updated when updating materials request status " + description + " " + libraryId);
					}
				}else{
					addMaterialsRequestStatusStmt.setString(1, description);
					addMaterialsRequestStatusStmt.setLong(2, materialsRequestStatusesVuFind2013.getLong("isDefault"));
					addMaterialsRequestStatusStmt.setLong(3, materialsRequestStatusesVuFind2013.getLong("sendEmailToPatron"));
					addMaterialsRequestStatusStmt.setString(4, materialsRequestStatusesVuFind2013.getString("emailTemplate"));
					addMaterialsRequestStatusStmt.setLong(5, materialsRequestStatusesVuFind2013.getLong("isOpen"));
					addMaterialsRequestStatusStmt.setLong(6, materialsRequestStatusesVuFind2013.getLong("isPatronCancel"));
					addMaterialsRequestStatusStmt.setLong(7, libraryId);
					int rowsUpdated = addMaterialsRequestStatusStmt.executeUpdate();
					if (rowsUpdated == 0){
						logger.warn("No rows added when adding materials request status " + description + " " + libraryId);
					}
				}
			}
			materialsRequestStatusesVuFind2013.close();

			//Synchronize requests
			PreparedStatement getMaterialsRequestsVuFind2013;
			if (librariesToSynchronize == null) {
				getMaterialsRequestsVuFind2013 = vufind2013connection.prepareStatement("SELECT username, materials_request.*, materials_request_status.description as statusName, materials_request_status.libraryId  FROM materials_request INNER JOIN user on createdBy = user.id INNER JOIN materials_request_status ON status = materials_request_status.id");
			}else{
				getMaterialsRequestsVuFind2013 = vufind2013connection.prepareStatement("SELECT username, materials_request.*, materials_request_status.description as statusName, materials_request_status.libraryId  FROM materials_request \n" +
						"INNER JOIN user on createdBy = user.id \n" +
						"INNER JOIN materials_request_status ON status = materials_request_status.id\n" +
						"INNER JOIN location on location.locationId = user.homeLocationId\n" +
						"INNER JOIN library on location.libraryId = library.libraryId\n" +
						"WHERE subdomain IN (" + librariesToSynchronize + ")");
			}
			ResultSet materialsRequestsVuFind2013 = getMaterialsRequestsVuFind2013.executeQuery();
			while (materialsRequestsVuFind2013.next()){
				String createdByUser = materialsRequestsVuFind2013.getString("username");
				Long dateCreated = materialsRequestsVuFind2013.getLong("dateCreated");
				Long dateUpdated = materialsRequestsVuFind2013.getLong("dateUpdated");

				//Synchronize the user so we have the new user id
				Long vufind2014User = synchronizeUser(createdByUser);
				//Get the status for the request
				String oldStatusName = materialsRequestsVuFind2013.getString("statusName");
				Long oldLibraryId = materialsRequestsVuFind2013.getLong("libraryId");
				getExistingMaterialsRequestStatusStmt.setString(1, oldStatusName);
				getExistingMaterialsRequestStatusStmt.setLong(2, oldLibraryId);
				ResultSet materialsRequestStatus = getExistingMaterialsRequestStatusStmt.executeQuery();
				Long vuFind2014RequestStatus;
				if (materialsRequestStatus.next()){
					vuFind2014RequestStatus = materialsRequestStatus.getLong("id");
				} else{
					logger.warn("The status for the request has not been properly migrated!");
					continue;
				}
				materialsRequestStatus.close();

				//Check to see if we already have a request created by that user
				getExistingMaterialsRequestStmt.setLong(1, vufind2014User);
				getExistingMaterialsRequestStmt.setLong(2, dateCreated);
				ResultSet existingMaterialsRequestRS = getExistingMaterialsRequestStmt.executeQuery();
				if (existingMaterialsRequestRS.next()){
					//Just check to see if the status changed.
					if (existingMaterialsRequestRS.getLong("dateUpdated") < dateUpdated){
						//The request was updated in VuFind 2013, need to update in VuFind 2014
						updateMaterialsRequestStmt.setString(1, materialsRequestsVuFind2013.getString("title"));
						updateMaterialsRequestStmt.setString(2, materialsRequestsVuFind2013.getString("author"));
						updateMaterialsRequestStmt.setString(3, materialsRequestsVuFind2013.getString("format"));
						updateMaterialsRequestStmt.setString(4, materialsRequestsVuFind2013.getString("ageLevel"));
						updateMaterialsRequestStmt.setString(5, materialsRequestsVuFind2013.getString("isbn"));
						updateMaterialsRequestStmt.setString(6, materialsRequestsVuFind2013.getString("oclcNumber"));
						updateMaterialsRequestStmt.setString(7, materialsRequestsVuFind2013.getString("publisher"));
						updateMaterialsRequestStmt.setString(8, materialsRequestsVuFind2013.getString("publicationYear"));
						updateMaterialsRequestStmt.setString(9, materialsRequestsVuFind2013.getString("articleInfo"));
						updateMaterialsRequestStmt.setLong(10, materialsRequestsVuFind2013.getLong("abridged"));
						updateMaterialsRequestStmt.setString(11, materialsRequestsVuFind2013.getString("about"));
						updateMaterialsRequestStmt.setString(12, materialsRequestsVuFind2013.getString("comments"));
						updateMaterialsRequestStmt.setLong(13, vuFind2014RequestStatus);
						updateMaterialsRequestStmt.setLong(14, materialsRequestsVuFind2013.getLong("dateUpdated"));
						updateMaterialsRequestStmt.setLong(15, materialsRequestsVuFind2013.getLong("emailSent"));
						updateMaterialsRequestStmt.setLong(16, materialsRequestsVuFind2013.getLong("holdsCreated"));
						updateMaterialsRequestStmt.setString(17, materialsRequestsVuFind2013.getString("email"));
						updateMaterialsRequestStmt.setString(18, materialsRequestsVuFind2013.getString("phone"));
						updateMaterialsRequestStmt.setString(19, materialsRequestsVuFind2013.getString("season"));
						updateMaterialsRequestStmt.setString(20, materialsRequestsVuFind2013.getString("magazineTitle"));
						updateMaterialsRequestStmt.setString(21, materialsRequestsVuFind2013.getString("upc"));
						updateMaterialsRequestStmt.setString(22, materialsRequestsVuFind2013.getString("issn"));
						updateMaterialsRequestStmt.setString(23, materialsRequestsVuFind2013.getString("bookType"));
						updateMaterialsRequestStmt.setString(24, materialsRequestsVuFind2013.getString("subFormat"));
						updateMaterialsRequestStmt.setString(25, materialsRequestsVuFind2013.getString("magazineDate"));
						updateMaterialsRequestStmt.setString(26, materialsRequestsVuFind2013.getString("magazineVolume"));
						updateMaterialsRequestStmt.setString(27, materialsRequestsVuFind2013.getString("magazinePageNumbers"));
						updateMaterialsRequestStmt.setLong(28, materialsRequestsVuFind2013.getLong("placeHoldWhenAvailable"));
						updateMaterialsRequestStmt.setLong(29, materialsRequestsVuFind2013.getLong("holdPickupLocation"));
						updateMaterialsRequestStmt.setString(30, materialsRequestsVuFind2013.getString("bookmobileStop"));
						updateMaterialsRequestStmt.setLong(31, materialsRequestsVuFind2013.getLong("illItem"));
						updateMaterialsRequestStmt.setString(32, materialsRequestsVuFind2013.getString("magazineNumber"));
						updateMaterialsRequestStmt.setLong(33, materialsRequestsVuFind2013.getLong("dateCreated"));
						updateMaterialsRequestStmt.setLong(34, vufind2014User);
						int numAdded = updateMaterialsRequestStmt.executeUpdate();
						if (numAdded != 1){
							logger.warn("Could not update materials request for " + createdByUser + " created on " + dateCreated);
						}
					}
				}else{
					//Insert the request
					addMaterialsRequestStmt.setString(1, materialsRequestsVuFind2013.getString("title"));
					addMaterialsRequestStmt.setString(2, materialsRequestsVuFind2013.getString("author"));
					addMaterialsRequestStmt.setString(3, materialsRequestsVuFind2013.getString("format"));
					addMaterialsRequestStmt.setString(4, materialsRequestsVuFind2013.getString("ageLevel"));
					addMaterialsRequestStmt.setString(5, materialsRequestsVuFind2013.getString("isbn"));
					addMaterialsRequestStmt.setString(6, materialsRequestsVuFind2013.getString("oclcNumber"));
					addMaterialsRequestStmt.setString(7, materialsRequestsVuFind2013.getString("publisher"));
					addMaterialsRequestStmt.setString(8, materialsRequestsVuFind2013.getString("publicationYear"));
					addMaterialsRequestStmt.setString(9, materialsRequestsVuFind2013.getString("articleInfo"));
					addMaterialsRequestStmt.setLong(10, materialsRequestsVuFind2013.getLong("abridged"));
					addMaterialsRequestStmt.setString(11, materialsRequestsVuFind2013.getString("about"));
					addMaterialsRequestStmt.setString(12, materialsRequestsVuFind2013.getString("comments"));
					addMaterialsRequestStmt.setLong(13, vuFind2014RequestStatus);
					addMaterialsRequestStmt.setLong(14, materialsRequestsVuFind2013.getLong("dateCreated"));
					addMaterialsRequestStmt.setLong(15, vufind2014User);
					addMaterialsRequestStmt.setLong(16, materialsRequestsVuFind2013.getLong("dateUpdated"));
					addMaterialsRequestStmt.setLong(17, materialsRequestsVuFind2013.getLong("emailSent"));
					addMaterialsRequestStmt.setLong(18, materialsRequestsVuFind2013.getLong("holdsCreated"));
					addMaterialsRequestStmt.setString(19, materialsRequestsVuFind2013.getString("email"));
					addMaterialsRequestStmt.setString(20, materialsRequestsVuFind2013.getString("phone"));
					addMaterialsRequestStmt.setString(21, materialsRequestsVuFind2013.getString("season"));
					addMaterialsRequestStmt.setString(22, materialsRequestsVuFind2013.getString("magazineTitle"));
					addMaterialsRequestStmt.setString(23, materialsRequestsVuFind2013.getString("upc"));
					addMaterialsRequestStmt.setString(24, materialsRequestsVuFind2013.getString("issn"));
					addMaterialsRequestStmt.setString(25, materialsRequestsVuFind2013.getString("bookType"));
					addMaterialsRequestStmt.setString(26, materialsRequestsVuFind2013.getString("subFormat"));
					addMaterialsRequestStmt.setString(27, materialsRequestsVuFind2013.getString("magazineDate"));
					addMaterialsRequestStmt.setString(28, materialsRequestsVuFind2013.getString("magazineVolume"));
					addMaterialsRequestStmt.setString(29, materialsRequestsVuFind2013.getString("magazinePageNumbers"));
					addMaterialsRequestStmt.setLong(30, materialsRequestsVuFind2013.getLong("placeHoldWhenAvailable"));
					addMaterialsRequestStmt.setLong(31, materialsRequestsVuFind2013.getLong("holdPickupLocation"));
					addMaterialsRequestStmt.setString(32, materialsRequestsVuFind2013.getString("bookmobileStop"));
					addMaterialsRequestStmt.setLong(33, materialsRequestsVuFind2013.getLong("illItem"));
					addMaterialsRequestStmt.setString(34, materialsRequestsVuFind2013.getString("magazineNumber"));
					int numAdded = addMaterialsRequestStmt.executeUpdate();
					if (numAdded != 1){
						logger.warn("Could not insert materials request for " + createdByUser + " created on " + dateCreated);
					}
				}
				existingMaterialsRequestRS.close();
			}
			materialsRequestsVuFind2013.close();
		} catch (Exception e){
			logger.error("Error synchronizing materials requests information");
		}
	}

	private void synchronizeNotInterested() {
		try{
			PreparedStatement getNotInterestedFromVuFind2013;
			if (librariesToSynchronize == null) {
				getNotInterestedFromVuFind2013 = vufind2013connection.prepareStatement("SELECT username, source, record_id, dateMarked from user_not_interested INNER JOIN user on userid = user.id INNER JOIN resource on resourceid = resource.id", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			}else{
				getNotInterestedFromVuFind2013 = vufind2013connection.prepareStatement("SELECT username, source, record_id, dateMarked from user_not_interested INNER JOIN user on userid = user.id INNER JOIN resource on resourceid = resource.id INNER JOIN location on location.locationId = user.homeLocationId\n" +
						"INNER JOIN library on location.libraryId = library.libraryId\n" +
						"WHERE subdomain IN (" + librariesToSynchronize + ")", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			}
			ResultSet notInterestedVuFind2013 = getNotInterestedFromVuFind2013.executeQuery();
			while (notInterestedVuFind2013.next()){
				String username = notInterestedVuFind2013.getString("username");
				Long userId = synchronizeUser(username);
				if (userId != null){
					String groupedWorkId = getWorkForResource(notInterestedVuFind2013.getString("source"), notInterestedVuFind2013.getString("record_id"));
					if (groupedWorkId != null){
						//Check to see if the user already has marked that they are not interested in this.
						getExistingNotInterestedStmt.setLong(1, userId);
						getExistingNotInterestedStmt.setString(2, groupedWorkId);
						ResultSet hasExistingNotInterestedRS = getExistingNotInterestedStmt.executeQuery();
						if (!hasExistingNotInterestedRS.next()){
							addNotInterestedStmt.setLong(1, userId);
							addNotInterestedStmt.setString(2, groupedWorkId);
							addNotInterestedStmt.setLong(3, notInterestedVuFind2013.getLong("dateMarked"));
							addNotInterestedStmt.executeUpdate();
						}
					}
				}
			}
		} catch (Exception e){
			logger.error("Error synchronizing not interested information");
		}
	}

	private void synchronizeRatingsAndReviews() {
		try{
			PreparedStatement getRatingsWithReviews;
			PreparedStatement getRatingsWithoutReviews;
			PreparedStatement getReviewsWithoutRatings;
			if (librariesToSynchronize == null) {
				getRatingsWithReviews = vufind2013connection.prepareStatement("SELECT username, source, record_id, rating, comment, comments.created FROM user_rating LEFT OUTER JOIN comments ON comments.user_id = user_rating.userId AND comments.resource_id = user_rating.resourceid INNER JOIN user on userid = user.id INNER JOIN resource on resourceid = resource.id WHERE comment is NOT NULL", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				getRatingsWithoutReviews = vufind2013connection.prepareStatement("SELECT username, source, record_id, rating FROM user_rating LEFT OUTER JOIN comments ON comments.user_id = user_rating.userId AND comments.resource_id = user_rating.resourceid INNER JOIN user on userid = user.id INNER JOIN resource on resourceid = resource.id WHERE comment is NULL", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				getReviewsWithoutRatings = vufind2013connection.prepareStatement("SELECT username, source, record_id, comment, comments.created FROM user_rating RIGHT OUTER JOIN comments ON comments.user_id = user_rating.userId AND comments.resource_id = user_rating.resourceid INNER JOIN user on user_id = user.id INNER JOIN resource on resource_id = resource.id WHERE rating is NULL", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			}else{
				getRatingsWithReviews = vufind2013connection.prepareStatement("SELECT username, source, record_id, rating, comment, comments.created FROM user_rating LEFT OUTER JOIN comments ON comments.user_id = user_rating.userId AND comments.resource_id = user_rating.resourceid INNER JOIN user on userid = user.id INNER JOIN resource on resourceid = resource.id\n" +
						"INNER JOIN location on location.locationId = user.homeLocationId\n" +
						"INNER JOIN library on location.libraryId = library.libraryId\n" +
						"WHERE subdomain IN (" + librariesToSynchronize + ") AND comment is NOT NULL", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				getRatingsWithoutReviews = vufind2013connection.prepareStatement("SELECT username, source, record_id, rating FROM user_rating LEFT OUTER JOIN comments ON comments.user_id = user_rating.userId AND comments.resource_id = user_rating.resourceid INNER JOIN user on userid = user.id INNER JOIN resource on resourceid = resource.id \n" +
						"INNER JOIN location on location.locationId = user.homeLocationId\n" +
						"INNER JOIN library on location.libraryId = library.libraryId\n" +
						"WHERE subdomain IN (" + librariesToSynchronize + ") AND comment is NULL", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
				getReviewsWithoutRatings = vufind2013connection.prepareStatement("SELECT username, source, record_id, comment, comments.created FROM user_rating RIGHT OUTER JOIN comments ON comments.user_id = user_rating.userId AND comments.resource_id = user_rating.resourceid INNER JOIN user on user_id = user.id INNER JOIN resource on resource_id = resource.id\n" +
						"INNER JOIN location on location.locationId = user.homeLocationId\n" +
						"INNER JOIN library on location.libraryId = library.libraryId\n" +
						"WHERE subdomain IN (" + librariesToSynchronize + ") AND rating is NULL", ResultSet.TYPE_FORWARD_ONLY, ResultSet.CONCUR_READ_ONLY);
			}

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
			logger.error("Error syncnhronizing ratings and reviews", e);
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
			PreparedStatement getListsStmt;
			PreparedStatement getListTitlesStmt;
			if (librariesToSynchronize == null) {
				getListsStmt = vufind2013connection.prepareStatement("SELECT user_list.id as listId, username, password, title, description, public FROM user_list inner join user on user_id = user.id");
				getListTitlesStmt = vufind2013connection.prepareStatement("SELECT source, record_id, notes, saved FROM user_resource INNER JOIN resource on resource_id = resource.id WHERE list_id = ?");
			}else{
				getListsStmt = vufind2013connection.prepareStatement("SELECT user_list.id as listId, username, password, title, description, public FROM user_list inner join user on user_id = user.id\n" +
						"INNER JOIN location on location.locationId = user.homeLocationId\n" +
						"INNER JOIN library on location.libraryId = library.libraryId\n" +
						"WHERE subdomain in (" + librariesToSynchronize + ")");
				getListTitlesStmt = vufind2013connection.prepareStatement("SELECT source, record_id, notes, saved FROM user_resource INNER JOIN resource on resource_id = resource.id WHERE list_id = ?");
			}
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
			String vufind2013Tags;
			if (librariesToSynchronize == null){
				vufind2013Tags = "SELECT tag, record_id, source, username, password, title, author, posted from resource_tags inner join tags on tags.id = resource_tags.tag_id inner join resource on resource_id = resource.id inner join user on user_id = user.id";
			} else{
				vufind2013Tags = "SELECT tag, record_id, source, username, password, title, author, posted from resource_tags inner join tags on tags.id = resource_tags.tag_id inner join resource on resource_id = resource.id inner join user on user_id = user.id\n" +
						"INNER JOIN location on location.locationId = user.homeLocationId\n" +
						"INNER JOIN library on location.libraryId = library.libraryId\n" +
						"WHERE subdomain in (" + librariesToSynchronize + ")";
			}

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

	private Pattern overdriveUrlPattern = Pattern.compile("overdrive.*?ID=(.*?)$", Pattern.CANON_EQ);
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
					//This is normal since we regularly delete and merge records
					logger.debug("Could not find grouped work for ILS record " + resourceRecordId);
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
								logger.debug("Could not find grouped work for overdrive record " + externalId);
							}
						}else{
							logger.debug("Could not handle getting grouped work for " + econtentSource + " " + resourceRecordId);
						}
					}else{
						getGroupedWorkStmt.setString(1, ilsId);
						ResultSet groupedWorkRS = getGroupedWorkStmt.executeQuery();
						if (groupedWorkRS.next()){
							permanentId = groupedWorkRS.getString("permanent_id");
						}else{
							//Some of these are because the marc record refers to an OverDrive record that we are suppressing in VuFind 2014
							Record marcRecord = getMarcRecordForIlsId(ilsId);
							if (marcRecord != null){
								List urlFields = marcRecord.getVariableFields("856");
								for (Object urlFieldObj : urlFields){
									if (urlFieldObj instanceof DataField){
										DataField urlField = (DataField)urlFieldObj;
										if (urlField.getSubfield('u') != null){
											String url = urlField.getSubfield('u').getData();

											Matcher overdriveUrlMatcher = overdriveUrlPattern.matcher(url);
											if (overdriveUrlMatcher.find()) {
												String overdriveId = overdriveUrlMatcher.group(1);
												getGroupedWorkForOverDriveStmt.setString(1, overdriveId);
												ResultSet groupedWorkRS2 = getGroupedWorkForOverDriveStmt.executeQuery();
												if (groupedWorkRS2.next()){
													permanentId = groupedWorkRS2.getString("permanent_id");
													break;
												} else{
													logger.debug("Could not find grouped work for overdrive record " + overdriveId);
												}
											}
										}
									}
								}
								if (permanentId == null){
									logger.debug("Could not find grouped work for ils record " + ilsId + " referenced from econtent record " + resourceRecordId + " even after checking marc record");
								}
							} else {
								logger.debug("Could not find grouped work for ils record " + ilsId + " referenced from econtent record " + resourceRecordId);
							}
						}
					}
				}else{
					logger.debug("Could not find econtent record for econtent record id " + resourceRecordId);
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

	private Record getMarcRecordForIlsId(String ilsId) {
		String shortId = ilsId.replace(".", "");
		while (shortId.length() < 9){
			shortId = "0" + shortId;
		}
		String firstChars = shortId.substring(0, 4);
		String basePath = individualMarcPath + "/" + firstChars;
		String individualFilename = basePath + "/" + shortId + ".mrc";
		File individualFile = new File(individualFilename);
		Record record = null;
		if (individualFile.exists()){
			try {
				FileInputStream inputStream = new FileInputStream(individualFile);
				MarcPermissiveStreamReader marcReader = new MarcPermissiveStreamReader(inputStream, true, true, "UTF-8");
				if (marcReader.hasNext()){
					try{
						record = marcReader.next();
					}catch (Exception e) {
						logger.error("Error updating solr based on marc record", e);
					}
				}
				inputStream.close();
			} catch (Exception e) {
				logger.error("Error reading data from ils file " + individualFile.toString(), e);
			}
		}
		return record;
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
