package org.epub;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Properties;

import javax.mail.*;
import javax.mail.internet.InternetAddress;
import javax.mail.internet.MimeMessage;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.vufind.CronLogEntry;
import org.vufind.CronProcessLogEntry;
import org.vufind.IProcessHandler;


public class CirculationProcess implements IProcessHandler{

	private Logger logger;
	private CronProcessLogEntry processLog;
	private Connection vufindConn = null;
	private Connection econtentConn = null;
	
	@Override
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		this.logger = logger;
		this.vufindConn = vufindConn;
		this.econtentConn = econtentConn;
		
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "eContent circulation");
		processLog.saveToDatabase(vufindConn, logger);
		logger.info("Running circulation process for eContent");
		
		//Activate suspended holds that have hit their activation date.
		activateSuspendedHolds();
		
		//Automatically return overdue items
		returnOverdueItems();
		
		//Cancel holds that have not been picked up after 5 days
		abandonHolds();
		
		//Place holds for wishlist records that were purchased
		processWishlist();
		
		//Send notices for items that are available that haven't had notices printed for them yet
		sendNotices();
		
		processLog.setFinished();
		processLog.saveToDatabase(vufindConn, logger);
	}
	
	private void processWishlist() {
		logger.info("Processing the wishlist.");
		processLog.addNote("Processing the wishlist.");
		try {
			//Get a list of all eContent Records that have a wishlist and that also have items
			PreparedStatement recordsToProcess = econtentConn.prepareStatement("SELECT econtent_record.id, title, author, source, count(DISTINCT econtent_wishlist.userId) as numWishList, count(DISTINCT econtent_item.id) as numItems, availableCopies FROM econtent_record INNER JOIN econtent_wishlist on econtent_record.id = econtent_wishlist.recordId INNER JOIN econtent_item on econtent_record.id = econtent_item.recordId WHERE econtent_wishlist.status = 'active' GROUP BY econtent_record.id");
			PreparedStatement wishListEntries = econtentConn.prepareStatement("SELECT econtent_wishlist.userId, econtent_wishlist.id FROM econtent_wishlist WHERE recordId = ? ORDER BY dateAdded ASC");
			PreparedStatement insertHold = econtentConn.prepareStatement("INSERT INTO econtent_hold (recordId, datePlaced, dateUpdated, userId, status) VALUES (?, ?, ?, ?, ?)");
			PreparedStatement markWishlistFilled = econtentConn.prepareStatement("UPDATE econtent_wishlist SET status = 'filled' WHERE id = ?");
			
			ResultSet recordsToProcessRs = recordsToProcess.executeQuery();
			
			while (recordsToProcessRs.next()){
				long recordId = recordsToProcessRs.getLong("id");
				long numAvailableRecords = recordsToProcessRs.getLong("availableCopies");
				logger.info("Record " + recordId + " has items added to it.  Processing the wishlist.");
				//Get a list of all users that had the record on their wishlist
				wishListEntries.setLong(1, recordId);
				ResultSet usersToProcess = wishListEntries.executeQuery();
				long curUser = 0;
				while (usersToProcess.next()){
					long userId = usersToProcess.getLong("userId");
					long wishlistId = usersToProcess.getLong("id");
					logger.info("Adding holds for user " + userId);
					//Create a hold for the user
					String holdStatus;
					if (curUser < numAvailableRecords){
						//hold is available
						holdStatus = "available";
					}else{
						//hold is active
						holdStatus = "active";
					}
					long curDate = new Date().getTime() / 1000;
					insertHold.setLong(1, recordId);
					insertHold.setLong(2, curDate);
					insertHold.setLong(3, curDate);
					insertHold.setLong(4, userId);
					insertHold.setString(5, holdStatus);
					insertHold.executeUpdate();
					
					//Update the wishlist to show that the wishlist was filled
					logger.info("Adding holds for user " + userId);
					markWishlistFilled.setLong(1, wishlistId);
					markWishlistFilled.executeUpdate();
					processLog.incUpdated();
					
				}
				
			}
		} catch (SQLException e) {
			logger.error("Error processing wish list.", e);
			processLog.incErrors();
			processLog.addNote("Error processing wish list. " + e.toString());
		}
	}

	private void activateSuspendedHolds() {
		processLog.addNote("Activating suspended eContent holds");
		long curTime = new Date().getTime() ;
		long curTimeSeconds = curTime/ 1000;
		try {
			PreparedStatement activateSuspendedHolds = econtentConn.prepareStatement("UPDATE econtent_hold SET status ='active', dateUpdated = ? WHERE status = 'suspended' AND reactivateDate < ?");
			activateSuspendedHolds.setLong(1, curTimeSeconds);
			activateSuspendedHolds.setLong(2, curTimeSeconds);
			long numHoldsActvated = activateSuspendedHolds.executeUpdate();
			logger.info("Activated " + numHoldsActvated + " suspended holds");
			processLog.addNote("Activated " + numHoldsActvated + " suspended holds");
		} catch (SQLException e) {
			processLog.incErrors();
			processLog.addNote("Error activating suspended holds. " + e.toString());
			logger.error("Error activating suspended holds.", e);
		}
	}

	private void sendNotices() {
		sendHoldAvailableNotices();
		
		sendHoldReminderNotices();
		
		sendHoldAbandonedNotices();
		
		sendReturnReminderNotices();
	}


	private void sendReturnReminderNotices() {
		logger.info("Sending return reminder notices");
		processLog.addNote("Sending return reminder notices");
		try{
			PreparedStatement usersToSendReturnReminderNoticesTo = econtentConn.prepareStatement("SELECT DISTINCT userId FROM econtent_checkout WHERE status ='out' AND returnReminderNoticeSent = 0 AND dateDue < ?");
			PreparedStatement getUserEmailStmt = vufindConn.prepareStatement("SELECT email, firstname, lastname, displayName FROM user where id = ?");
			PreparedStatement getExpiringCheckoutsForUser = econtentConn.prepareStatement("SELECT dateCheckedOut, dateDue, title, author, econtent_record.id, econtent_checkout.id as checkoutId FROM econtent_checkout INNER JOIN econtent_record on econtent_record.id = econtent_checkout.recordId WHERE userId = ? AND econtent_checkout.status ='out' AND returnReminderNoticeSent = 0 AND dateDue < ?");
			PreparedStatement updateNoticeSent = econtentConn.prepareStatement("UPDATE econtent_checkout SET returnReminderNoticeSent = 1 WHERE id = ?");
			SimpleDateFormat dateFormat = new SimpleDateFormat("E, MMMM d, yyyy");
			
			long curTime = new Date().getTime() ;
			long curTimeSeconds = curTime/ 1000;
			//Send reminders for anything due in the next 3 days
			long lastDueDateToSendNotice = curTimeSeconds + 3 * 24 * 60 * 60;
			Date expirationDate = new Date(lastDueDateToSendNotice * 1000);
			logger.info("Printing Reminder Notices for " + dateFormat.format( expirationDate));
			
			usersToSendReturnReminderNoticesTo.setLong(1, lastDueDateToSendNotice);
			ResultSet usersToSendNoticesTo = usersToSendReturnReminderNoticesTo.executeQuery();
			while (usersToSendNoticesTo.next()){
				long userId = usersToSendNoticesTo.getLong("userId");
				getUserEmailStmt.setLong(1, userId);
				ResultSet userInfo = getUserEmailStmt.executeQuery();
				while (userInfo.next()){
					String emailSubject = "Douglas County Libraries Notice";
					StringBuffer emailBody = new StringBuffer();
					String email = userInfo.getString("email");
					String firstname = userInfo.getString("firstname");
					String lastname = userInfo.getString("lastname");
					
					if (!email.equals("email")){
						logger.info("Sending notification to " + firstname + " " + lastname);
						emailBody.append(firstname + " " + lastname + "\r\n\r\n\r\n");
						emailBody.append("This is a courtesy reminder from the library that the following items will be due on " + dateFormat.format(new Date(lastDueDateToSendNotice * 1000)) + ". ");
						emailBody.append("Your access to the items will be automatically removed.  If you have downloaded any items to a portable reader, please delete the items from your device by ths date. \r\n\r\n");
						
						//Get a list of records that are available where notices have not been sent
						getExpiringCheckoutsForUser.setLong(1, userId);
						getExpiringCheckoutsForUser.setLong(2, lastDueDateToSendNotice);
						ResultSet expiringCheckouts = getExpiringCheckoutsForUser.executeQuery();
						while (expiringCheckouts.next()){
							String title = expiringCheckouts.getString("title");
							String author = expiringCheckouts.getString("author");
							emailBody.append("    " + title + " by " + author + "\r\n");
							long dateCheckedOut = expiringCheckouts.getLong("dateCheckedOut");
							Date dateCheckedOutDate = new Date(dateCheckedOut * 1000);
							emailBody.append("    Checked Out: " + dateFormat.format(dateCheckedOutDate) + "\r\n");
						}
						
						emailBody.append("Thank you,\r\n\r\n");
						emailBody.append("Douglas County Libraries\r\n");
						emailBody.append("http://www.douglascountylibraries.org/\r\n");
						
						sendNotice(email, emailSubject, emailBody.toString(), logger);
						
						expiringCheckouts.beforeFirst();
						while (expiringCheckouts.next()){
							//Mark that the notice has been sent
							long checkoutId = expiringCheckouts.getLong("checkoutId");
							updateNoticeSent.setLong(1, checkoutId);
							int numRecordsUpdated = updateNoticeSent.executeUpdate();
							if (numRecordsUpdated != 1){
								logger.error("Updated that the notice was sent.");
								processLog.incErrors();
								processLog.addNote("Error updating that the notice was sent for " + checkoutId);
							}else{
								processLog.incUpdated();
							}
						}
					}
				}
			}
		} catch (SQLException e) {
			logger.error("Error sending notices", e);
			processLog.incErrors();
			processLog.addNote("Error sending notices " + e.toString());
		}
	}

	private void sendHoldAbandonedNotices() {
		logger.info("Sending hold abandoned notices");
		processLog.addNote("Sending hold abandoned notices");
		try{
			PreparedStatement usersToSendAbandonedNoticesTo = econtentConn.prepareStatement("SELECT DISTINCT userId FROM econtent_hold WHERE status ='abandoned' AND holdAbandonedNoticeSent = 0");
			ResultSet usersToSendNoticesTo = usersToSendAbandonedNoticesTo.executeQuery();
			PreparedStatement getUserEmailStmt = vufindConn.prepareStatement("SELECT email, firstname, lastname, displayName FROM user where id = ?");
			PreparedStatement getAbandonedHoldsForUser = econtentConn.prepareStatement("SELECT datePlaced, dateUpdated, econtent_hold.status, title, author, econtent_record.id, econtent_hold.id as holdId FROM econtent_hold INNER JOIN econtent_record on econtent_record.id = econtent_hold.recordId WHERE userId = ? AND econtent_hold.status ='abandoned' AND holdAbandonedNoticeSent = 0");
			PreparedStatement updateNoticeSent = econtentConn.prepareStatement("UPDATE econtent_hold SET holdAbandonedNoticeSent = 1 WHERE id = ?");
			SimpleDateFormat dateFormat = new SimpleDateFormat("E, MMMM d, yyyy");
			
			while (usersToSendNoticesTo.next()){
				long userId = usersToSendNoticesTo.getLong("userId");
				getUserEmailStmt.setLong(1, userId);
				ResultSet userInfo = getUserEmailStmt.executeQuery();
				while (userInfo.next()){
					String emailSubject = "Douglas County Libraries - Hold Abandoned Notice";
					StringBuffer emailBody = new StringBuffer();
					String email = userInfo.getString("email");
					String firstname = userInfo.getString("firstname");
					String lastname = userInfo.getString("lastname");
					
					if (!email.equals("email")){
						logger.info("Sending notification to " + firstname + " " + lastname);
						emailBody.append(firstname + " " + lastname + "\r\n\r\n\r\n");
						emailBody.append("We wanted to let you know that the following items were held for you at the library.  Since you were unable to pick these holds up they have now expired.\r\n\r\n");
						
						//Get a list of records that are available where notices have not been sent
						getAbandonedHoldsForUser.setLong(1, userId);
						ResultSet availableHolds = getAbandonedHoldsForUser.executeQuery();
						while (availableHolds.next()){
							String title = availableHolds.getString("title");
							String author = availableHolds.getString("author");
							emailBody.append("    " + title + " by " + author + "\r\n");
							long datePlaced = availableHolds.getLong("datePlaced");
							Date datePlacedDate = new Date(datePlaced * 1000);
							long dateHoldExpires = datePlaced * 1000 + 1000 * 60 * 60 * 24 * 5; // Add 5 days
							Date dateHoldExpiresDate = new Date(dateHoldExpires);
							emailBody.append("    Placed on hold: " + dateFormat.format(datePlacedDate) + "    Expired: " + dateFormat.format(dateHoldExpiresDate) + "\r\n");
						}
						
						emailBody.append("Thank you,\r\n\r\n");
						emailBody.append("Douglas County Libraries\r\n");
						emailBody.append("http://www.douglascountylibraries.org/\r\n");
						
						sendNotice(email, emailSubject, emailBody.toString(), logger);
						
						availableHolds.beforeFirst();
						while (availableHolds.next()){
							//Mark that the notice has been sent
							long holdId = availableHolds.getLong("holdId");
							updateNoticeSent.setLong(1, holdId);
							int numRecordsUpdated = updateNoticeSent.executeUpdate();
							if (numRecordsUpdated != 1){
								logger.error("Updated that the notice was sent.");
								processLog.incErrors();
								processLog.addNote("Error updating that the notice was sent for " + holdId);
							}else{
								processLog.incUpdated();
							}
						}
					}
				}
			}
		} catch (SQLException e) {
			logger.error("Error sending notices", e);
			processLog.incErrors();
			processLog.addNote("Error sending notices " + e.toString());
		}
	}

	private void sendHoldReminderNotices() {
		//Send a reminder to users that have holds that will expire in the next 2 days
		logger.info("Sending hold reminder notices");
		processLog.addNote("Sending hold reminder notices");
		long curTime = new Date().getTime() ;
		long curTimeSeconds = curTime/ 1000;
		long latestDateToRemainActive = curTimeSeconds - (2 * 24 * 60 * 60);
		try {
			//Send notices to any users that have available holds where the notice has not been sent
			//Get a list of records to send notices for
			PreparedStatement usersToSendReminderNoticesTo = econtentConn.prepareStatement("SELECT DISTINCT userId FROM econtent_hold WHERE dateUpdated < ? AND status ='available' AND holdReminderNoticeSent = 0");
			PreparedStatement getUserEmailStmt = vufindConn.prepareStatement("SELECT email, firstname, lastname, displayName FROM user where id = ?");
			PreparedStatement getAvailableHoldsForUser = econtentConn.prepareStatement("SELECT datePlaced, dateUpdated, econtent_hold.status, title, author, econtent_record.id, econtent_hold.id as holdId FROM econtent_hold INNER JOIN econtent_record on econtent_record.id = econtent_hold.recordId WHERE holdReminderNoticeSent = 0 AND userId = ? and econtent_hold.status = 'available' AND dateUpdated < ?");
			PreparedStatement updateNoticeSent = econtentConn.prepareStatement("UPDATE econtent_hold SET holdReminderNoticeSent = 1 WHERE id = ?");
			SimpleDateFormat dateFormat = new SimpleDateFormat("E, MMMM d, yyyy");
			
			usersToSendReminderNoticesTo.setLong(1, latestDateToRemainActive);
			ResultSet usersToSendNoticesTo = usersToSendReminderNoticesTo.executeQuery();
			while (usersToSendNoticesTo.next()){
				long userId = usersToSendNoticesTo.getLong("userId");
				getUserEmailStmt.setLong(1, userId);
				ResultSet userInfo = getUserEmailStmt.executeQuery();
				while (userInfo.next()){
					String emailSubject = "Douglas County Libraries - Hold Reminder Notice";
					StringBuffer emailBody = new StringBuffer();
					String email = userInfo.getString("email");
					String firstname = userInfo.getString("firstname");
					String lastname = userInfo.getString("lastname");
					
					if (!email.equals("email")){
						logger.info("Sending notification to " + firstname + " " + lastname);
						emailBody.append(firstname + " " + lastname + "\r\n\r\n\r\n");
						emailBody.append("This is a reminder that the following items that you requested are now available for online usage. ");
						emailBody.append("You can checkout the items by accessing your eContent at http://catalog.douglascountylibraries.org/MyResearch/MyEContent.\r\n\r\n");
						emailBody.append("***Please Note***\r\n");
						emailBody.append("If you no longer need these items, please cancel them online at: http://catalog.douglascountylibraries.org/MyResearch/MyEContent.  This will allow these items to be available for the next patron.\r\n\r\n");
						
						//Get a list of records that are available where notices have not been sent
						getAvailableHoldsForUser.setLong(1, userId);
						getAvailableHoldsForUser.setLong(2, latestDateToRemainActive);
						ResultSet availableHolds = getAvailableHoldsForUser.executeQuery();
						while (availableHolds.next()){
							String title = availableHolds.getString("title");
							String author = availableHolds.getString("author");
							emailBody.append("    " + title + " by " + author + "\r\n");
							long datePlaced = availableHolds.getLong("datePlaced");
							Date datePlacedDate = new Date(datePlaced * 1000);
							long dateHoldExpires = datePlaced * 1000 + 1000 * 60 * 60 * 24 * 5; // Add 5 days
							Date dateHoldExpiresDate = new Date(dateHoldExpires);
							emailBody.append("    Placed on hold: " + dateFormat.format(datePlacedDate) + "    Expires: " + dateFormat.format(dateHoldExpiresDate) + "\r\n");
						}
						
						emailBody.append("Thank you,\r\n\r\n");
						emailBody.append("Douglas County Libraries\r\n");
						emailBody.append("http://www.douglascountylibraries.org/\r\n");
						
						sendNotice(email, emailSubject, emailBody.toString(), logger);
						
						availableHolds.beforeFirst();
						while (availableHolds.next()){
							//Mark that the notice has been sent
							long holdId = availableHolds.getLong("holdId");
							updateNoticeSent.setLong(1, holdId);
							int numRecordsUpdated = updateNoticeSent.executeUpdate();
							if (numRecordsUpdated != 1){
								logger.error("Updated that the notice was sent.");
								processLog.incErrors();
								processLog.addNote("Error updating that the notice was sent for " + holdId);
							}else{
								processLog.incUpdated();
							}
						}
					}
				}
			}
			
			
		} catch (SQLException e) {
			logger.error("Error sending notices", e);
			processLog.incErrors();
			processLog.addNote("Error sending notices " + e.toString());
		}
	}

	private void sendHoldAvailableNotices() {
		logger.info("Sending hold available notices");
		processLog.addNote("Sending hold available notices");
		try {
			//Send notices to any users that have available holds where the notice has not been sent
			//Get a list of records to send notices for
			PreparedStatement usersToSendNoticesToStmt = econtentConn.prepareStatement("SELECT DISTINCT userId FROM econtent_hold WHERE status = 'available' and holdAvailableNoticeSent = 0");
			PreparedStatement getUserEmailStmt = vufindConn.prepareStatement("SELECT email, firstname, lastname, displayName FROM user where id = ?");
			PreparedStatement getAvailableHoldsForUser = econtentConn.prepareStatement("SELECT datePlaced, dateUpdated, econtent_hold.status, title, author, econtent_record.id, econtent_hold.id as holdId FROM econtent_hold INNER JOIN econtent_record on econtent_record.id = econtent_hold.recordId WHERE holdAvailableNoticeSent = 0 AND userId = ? and econtent_hold.status = 'available'");
			PreparedStatement updateNoticeSent = econtentConn.prepareStatement("UPDATE econtent_hold SET holdAvailableNoticeSent = 1 WHERE id = ?");
			SimpleDateFormat dateFormat = new SimpleDateFormat("E, MMMM d, yyyy");
			
			ResultSet usersToSendNoticesTo = usersToSendNoticesToStmt.executeQuery();
			while (usersToSendNoticesTo.next()){
				long userId = usersToSendNoticesTo.getLong("userId");
				getUserEmailStmt.setLong(1, userId);
				ResultSet userInfo = getUserEmailStmt.executeQuery();
				while (userInfo.next()){
					String emailSubject = "Douglas County Libraries - Hold Notice";
					StringBuffer emailBody = new StringBuffer();
					String email = userInfo.getString("email");
					String firstname = userInfo.getString("firstname");
					String lastname = userInfo.getString("lastname");
					
					if (!email.equals("email")){
						logger.info("Sending notification to " + firstname + " " + lastname);
						emailBody.append(firstname + " " + lastname + "\r\n\r\n\r\n");
						emailBody.append("The following items that you requested are now available for online usage. ");
						emailBody.append("You can checkout the items by accessing your eContent at http://catalog.douglascountylibraries.org/MyResearch/MyEContent.\r\n\r\n");
						emailBody.append("***Please Note***\r\n");
						emailBody.append("If you no longer need these items, please cancel them online at: http://catalog.douglascountylibraries.org/MyResearch/MyEContent.  This will allow these items to be available for the next patron.\r\n\r\n");
						
						//Get a list of records that are available where notices have not been sent
						getAvailableHoldsForUser.setLong(1, userId);
						ResultSet availableHolds = getAvailableHoldsForUser.executeQuery();
						while (availableHolds.next()){
							String title = availableHolds.getString("title");
							String author = availableHolds.getString("author");
							emailBody.append("    " + title + " by " + author + "\r\n");
							long datePlaced = availableHolds.getLong("datePlaced");
							Date datePlacedDate = new Date(datePlaced * 1000);
							long dateHoldExpires = new Date().getTime() + 1000 * 60 * 60 * 24 * 5; // Add 5 days
							Date dateHoldExpiresDate = new Date(dateHoldExpires);
							
							emailBody.append("    Placed on hold: " + dateFormat.format(datePlacedDate) + "    Expires: " + dateFormat.format(dateHoldExpiresDate) + "\r\n");
						}
						
						emailBody.append("Thank you,\r\n\r\n");
						emailBody.append("Douglas County Libraries\r\n");
						emailBody.append("http://www.douglascountylibraries.org/\r\n");
						
						sendNotice(email, emailSubject, emailBody.toString(), logger);
						
						availableHolds.beforeFirst();
						while (availableHolds.next()){
							//Mark that the notice has been sent
							long holdId = availableHolds.getLong("holdId");
							updateNoticeSent.setLong(1, holdId);
							int numRecordsUpdated = updateNoticeSent.executeUpdate();
							if (numRecordsUpdated != 1){
								logger.error("Updated that the notice was sent.");
								processLog.incErrors();
								processLog.addNote("Unable to update that the notice was sent for holdId " + holdId);
							}else{
								processLog.incUpdated();
							}
						}
					}
				}
			}
			
			
		} catch (SQLException e) {
			logger.error("Error sending notices", e);
			processLog.incErrors();
			processLog.addNote("Error sending notices " + e.toString());
		}
	}

	private boolean sendNotice(String email, String emailSubject, String emailBody, Logger logger) {
		logger.info("Sending notice to " + email);
		logger.info("Email Subject: " + emailSubject);
		logger.info("Email Body: " + emailBody);
		
		String host = "dirsync2.dcl.lan";
		String from = "notices@dclibraries.microsoftonline.com";
		
		// Get system properties
		Properties props = System.getProperties();

		// Setup mail server
		props.put("mail.smtp.host", host);

		// Get session
		Session session = Session.getDefaultInstance(props, null);
		
		try {
			// Define message
			MimeMessage message = new MimeMessage(session);
			message.setFrom(new InternetAddress(from));
			message.addRecipient(Message.RecipientType.TO, new InternetAddress(email));
			message.setSubject(emailSubject);
			message.setText(emailBody);

			// Send message
			Transport.send(message);
			return true;
		} catch (Exception e) {
			logger.error("Unable to send notice to " + email, e);
			return false;
		}
		
	}

	private void abandonHolds() {
		//Check for any holds that were made available more than 5 days ago
		processLog.addNote("Abandoning holds that were nto picked up.");
		long curTime = new Date().getTime() ;
		long curTimeSeconds = curTime/ 1000;
		long latestDateToRemainActive = curTimeSeconds - (5 * 24 * 60 * 60);
		try {
			PreparedStatement getAbandonedHolds = econtentConn.prepareStatement("SELECT id, recordId FROM econtent_hold WHERE dateUpdated < ? AND status ='available'");
			PreparedStatement abandonHold = econtentConn.prepareStatement("UPDATE econtent_hold SET status = 'abandoned', dateUpdated = ? WHERE id = ?");
			getAbandonedHolds.setLong(1, latestDateToRemainActive);
			ResultSet abandonedHolds = getAbandonedHolds.executeQuery();
			while (abandonedHolds.next()){
				long id = abandonedHolds.getLong("id");
				long recordId = abandonedHolds.getLong("recordId");
				logger.info("Hold " + id + " has been abandoned");
				abandonHold.setLong(1, curTimeSeconds);
				abandonHold.setLong(2, id);
				long recordsAbandoned = abandonHold.executeUpdate();
				
				if (recordsAbandoned != 1){
					logger.info("Unable to abandon hold " + id);
					processLog.addNote("Unable to abandon hold " + id);
					processLog.incErrors();
				}else{
					processLog.incUpdated();
					processHoldQueue(recordId, curTimeSeconds, logger);
				}
			}
		} catch (SQLException e) {
			logger.error("Error abandoning holds", e);
			processLog.addNote("Error abandoning holds " + e.toString());
			processLog.incErrors();
		}
	}

	PreparedStatement getNextAvailableHold;
	PreparedStatement markHoldAvailable;
	private void processHoldQueue(long recordId, long curTimeSeconds, Logger logger) throws SQLException {
		if (getNextAvailableHold == null){
			getNextAvailableHold = econtentConn.prepareStatement("SELECT id FROM econtent_hold WHERE recordId = ? AND status='active' ORDER BY datePlaced ASC LIMIT 0, 1");
		}
		if (markHoldAvailable == null){
			markHoldAvailable = econtentConn.prepareStatement("UPDATE econtent_hold SET status='available', dateUpdated=? WHERE id = ?");
		}
		//Check to see if there are holds and return the item to the next user
		getNextAvailableHold.setLong(1, recordId);
		ResultSet nextHold = getNextAvailableHold.executeQuery();
		if (nextHold.next()){
			long holdId = nextHold.getLong("id");
			logger.info("Activating next hold " + holdId);
			markHoldAvailable.setLong(1, curTimeSeconds);
			markHoldAvailable.setLong(2, holdId);
			int numHoldsUpdated = markHoldAvailable.executeUpdate();
			if (numHoldsUpdated != 1){
				logger.error("Could not activate next hold in the hold queue");
			}
		}else{
			logger.info("No pending holds need to be activated");
		}
	}

	private void returnOverdueItems() {
		processLog.addNote("Returning overdue eContent");
		long curTime = new Date().getTime() ;
		long curTimeSeconds = curTime/ 1000;
		//Get a list of all items that are overdue from the database
		try {
			PreparedStatement getOverdueItems = econtentConn.prepareStatement("SELECT id, recordId, userId, dateDue FROM econtent_checkout WHERE status='out' AND dateDue < " + curTimeSeconds);
			PreparedStatement returnOverdueItem = econtentConn.prepareStatement("UPDATE econtent_checkout SET status = 'returned', dateReturned = ? WHERE id=?");
			ResultSet overdueItems = getOverdueItems.executeQuery();
			while (overdueItems.next()){
				long id = overdueItems.getLong("id");
				long recordId = overdueItems.getLong("recordId");
				long userId = overdueItems.getLong("userId");
				long dueDate = overdueItems.getLong("dateDue");
				logger.info("Record " + recordId + " is checked out to " + userId + " and is overdue was due at " + dueDate + " it is now " + curTimeSeconds);
				//Mark that the item is returned
				returnOverdueItem.setLong(1, curTimeSeconds);
				returnOverdueItem.setLong(2, id);
				int numRowReturned = returnOverdueItem.executeUpdate();
				if (numRowReturned != 1){
					logger.error("Unable to return record " + recordId + " checked out to " + userId);
					processLog.incErrors();
					processLog.addNote("Unable to return record " + recordId + " checked out to " + userId);
				}else{
					processHoldQueue(recordId, curTimeSeconds, logger);
					processLog.incUpdated();
				}
			}
		} catch (SQLException e) {
			logger.error("Error returning overdue items", e);
			processLog.incErrors();
			processLog.addNote("Error returning overdue items " + e.toString());
		}
		
	}

}
