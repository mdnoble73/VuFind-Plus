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
import org.vufind.Util;

public class CirculationProcess implements IProcessHandler{

	private Logger logger;
	private CronProcessLogEntry processLog;
	private Connection vufindConn = null;
	private Connection econtentConn = null;
	private String mailHost;
	private String mailFrom;
	private String noticeLibraryName;
	private String siteUrl;
	
	@Override
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		this.logger = logger;
		this.vufindConn = vufindConn;
		this.econtentConn = econtentConn;
		
		processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "eContent circulation");
		processLog.saveToDatabase(vufindConn, logger);
		logger.info("Running circulation process for eContent");
		
		mailHost = configIni.get("Mail", "host");
		mailFrom = configIni.get("Site", "email");
		noticeLibraryName = Util.cleanIniValue(configIni.get("EContent", "noticeLibraryName"));
		siteUrl = Util.cleanIniValue(configIni.get("Site", "url"));
		
		//Activate suspended holds that have hit their activation date.
		activateSuspendedHolds();
		
		//Automatically return overdue items
		returnOverdueItems();
		
		//Cancel holds that have not been picked up after 5 days
		abandonHolds();
		
		//Send notices for items that are available that haven't had notices printed for them yet
		sendNotices();
		
		processLog.setFinished();
		processLog.saveToDatabase(vufindConn, logger);
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
			PreparedStatement getExpiringCheckoutsForUser = econtentConn.prepareStatement("SELECT dateCheckedOut, dateDue, title, author, econtent_checkout.id as checkoutId FROM econtent_checkout WHERE userId = ? AND econtent_checkout.status ='out' AND returnReminderNoticeSent = 0 AND dateDue < ?");
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
					String emailSubject = noticeLibraryName + " Notice";
					StringBuilder emailBody = new StringBuilder();
					String email = userInfo.getString("email");
					String firstname = userInfo.getString("firstname");
					String lastname = userInfo.getString("lastname");
					
					if (!email.equals("email")){
						logger.info("Sending notification to " + firstname + " " + lastname);
						emailBody.append(firstname).append(" ").append(lastname).append("\r\n\r\n\r\n");
						emailBody.append("This is a courtesy reminder from the library that the following items will be due on ").append(dateFormat.format(new Date(lastDueDateToSendNotice * 1000))).append(". ");
						emailBody.append("Your access to the items will be automatically removed.  If you have downloaded any items to a portable reader, please delete the items from your device by ths date. \r\n\r\n");
						
						//Get a list of records that are available where notices have not been sent
						getExpiringCheckoutsForUser.setLong(1, userId);
						getExpiringCheckoutsForUser.setLong(2, lastDueDateToSendNotice);
						ResultSet expiringCheckouts = getExpiringCheckoutsForUser.executeQuery();
						while (expiringCheckouts.next()){
							String title = expiringCheckouts.getString("title");
							String author = expiringCheckouts.getString("author");
							emailBody.append("    ").append(title).append(" by ").append(author).append("\r\n");
							long dateCheckedOut = expiringCheckouts.getLong("dateCheckedOut");
							Date dateCheckedOutDate = new Date(dateCheckedOut * 1000);
							emailBody.append("    Checked Out: ").append(dateFormat.format(dateCheckedOutDate)).append("\r\n");
						}
						
						emailBody.append("Thank you,\r\n\r\n");
						emailBody.append(noticeLibraryName).append("\r\n");
						emailBody.append(siteUrl).append("\r\n");
						
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
			PreparedStatement getAbandonedHoldsForUser = econtentConn.prepareStatement("SELECT datePlaced, dateUpdated, econtent_hold.status, title, author, econtent_hold.id as holdId FROM econtent_hold WHERE userId = ? AND econtent_hold.status ='abandoned' AND holdAbandonedNoticeSent = 0");
			PreparedStatement updateNoticeSent = econtentConn.prepareStatement("UPDATE econtent_hold SET holdAbandonedNoticeSent = 1 WHERE id = ?");
			SimpleDateFormat dateFormat = new SimpleDateFormat("E, MMMM d, yyyy");
			
			while (usersToSendNoticesTo.next()){
				long userId = usersToSendNoticesTo.getLong("userId");
				getUserEmailStmt.setLong(1, userId);
				ResultSet userInfo = getUserEmailStmt.executeQuery();
				while (userInfo.next()){
					String emailSubject = noticeLibraryName + " - Hold Abandoned Notice";
					StringBuilder emailBody = new StringBuilder();
					String email = userInfo.getString("email");
					String firstname = userInfo.getString("firstname");
					String lastname = userInfo.getString("lastname");
					
					if (!email.equals("email")){
						logger.info("Sending notification to " + firstname + " " + lastname);
						emailBody.append(firstname).append(" ").append(lastname).append("\r\n\r\n\r\n");
						emailBody.append("We wanted to let you know that the following items were held for you at the library.  Since you were unable to pick these holds up they have now expired.\r\n\r\n");
						
						//Get a list of records that are available where notices have not been sent
						getAbandonedHoldsForUser.setLong(1, userId);
						ResultSet availableHolds = getAbandonedHoldsForUser.executeQuery();
						while (availableHolds.next()){
							String title = availableHolds.getString("title");
							String author = availableHolds.getString("author");
							emailBody.append("    ").append(title).append(" by ").append(author).append("\r\n");
							long datePlaced = availableHolds.getLong("datePlaced");
							Date datePlacedDate = new Date(datePlaced * 1000);
							long dateHoldExpires = datePlaced * 1000 + 1000 * 60 * 60 * 24 * 5; // Add 5 days
							Date dateHoldExpiresDate = new Date(dateHoldExpires);
							emailBody.append("    Placed on hold: ").append(dateFormat.format(datePlacedDate)).append("    Expired: ").append(dateFormat.format(dateHoldExpiresDate)).append("\r\n");
						}
						
						emailBody.append("Thank you,\r\n\r\n");
						emailBody.append(noticeLibraryName).append("\r\n");
						emailBody.append(siteUrl).append("\r\n");
						
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
			PreparedStatement getAvailableHoldsForUser = econtentConn.prepareStatement("SELECT datePlaced, dateUpdated, econtent_hold.status, title, author, econtent_hold.id as holdId FROM econtent_hold WHERE holdReminderNoticeSent = 0 AND userId = ? and econtent_hold.status = 'available' AND dateUpdated < ?");
			PreparedStatement updateNoticeSent = econtentConn.prepareStatement("UPDATE econtent_hold SET holdReminderNoticeSent = 1 WHERE id = ?");
			SimpleDateFormat dateFormat = new SimpleDateFormat("E, MMMM d, yyyy");
			
			usersToSendReminderNoticesTo.setLong(1, latestDateToRemainActive);
			ResultSet usersToSendNoticesTo = usersToSendReminderNoticesTo.executeQuery();
			while (usersToSendNoticesTo.next()){
				long userId = usersToSendNoticesTo.getLong("userId");
				getUserEmailStmt.setLong(1, userId);
				ResultSet userInfo = getUserEmailStmt.executeQuery();
				while (userInfo.next()){
					String emailSubject = noticeLibraryName + " - Hold Reminder Notice";
					StringBuilder emailBody = new StringBuilder();
					String email = userInfo.getString("email");
					String firstname = userInfo.getString("firstname");
					String lastname = userInfo.getString("lastname");
					
					if (!email.equals("email")){
						logger.info("Sending notification to " + firstname + " " + lastname);
						emailBody.append(firstname).append(" ").append(lastname).append("\r\n\r\n\r\n");
						emailBody.append("This is a reminder that the following items that you requested are now available for online usage. ");
						emailBody.append("You can checkout the items by accessing your eContent at {$siteUrl}/MyResearch/MyEContent.\r\n\r\n");
						emailBody.append("***Please Note***\r\n");
						emailBody.append("If you no longer need these items, please cancel them online at: {$siteUrl}/MyResearch/MyEContent.  This will allow these items to be available for the next patron.\r\n\r\n");
						
						//Get a list of records that are available where notices have not been sent
						getAvailableHoldsForUser.setLong(1, userId);
						getAvailableHoldsForUser.setLong(2, latestDateToRemainActive);
						ResultSet availableHolds = getAvailableHoldsForUser.executeQuery();
						while (availableHolds.next()){
							String title = availableHolds.getString("title");
							String author = availableHolds.getString("author");
							emailBody.append("    ").append(title).append(" by ").append(author).append("\r\n");
							long datePlaced = availableHolds.getLong("datePlaced");
							Date datePlacedDate = new Date(datePlaced * 1000);
							long dateHoldExpires = datePlaced * 1000 + 1000 * 60 * 60 * 24 * 5; // Add 5 days
							Date dateHoldExpiresDate = new Date(dateHoldExpires);
							emailBody.append("    Placed on hold: ").append(dateFormat.format(datePlacedDate)).append("    Expires: ").append(dateFormat.format(dateHoldExpiresDate)).append("\r\n");
						}
						
						emailBody.append("Thank you,\r\n\r\n");
						emailBody.append(noticeLibraryName).append("\r\n");
						emailBody.append(siteUrl).append("\r\n");
						
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
			PreparedStatement getAvailableHoldsForUser = econtentConn.prepareStatement("SELECT datePlaced, dateUpdated, econtent_hold.status, title, author, econtent_hold.id as holdId FROM econtent_hold WHERE holdAvailableNoticeSent = 0 AND userId = ? and econtent_hold.status = 'available'");
			PreparedStatement updateNoticeSent = econtentConn.prepareStatement("UPDATE econtent_hold SET holdAvailableNoticeSent = 1 WHERE id = ?");
			SimpleDateFormat dateFormat = new SimpleDateFormat("E, MMMM d, yyyy");
			
			ResultSet usersToSendNoticesTo = usersToSendNoticesToStmt.executeQuery();
			while (usersToSendNoticesTo.next()){
				long userId = usersToSendNoticesTo.getLong("userId");
				getUserEmailStmt.setLong(1, userId);
				ResultSet userInfo = getUserEmailStmt.executeQuery();
				while (userInfo.next()){
					String emailSubject = noticeLibraryName + " - Hold Notice";
					StringBuilder emailBody = new StringBuilder();
					String email = userInfo.getString("email");
					String firstname = userInfo.getString("firstname");
					String lastname = userInfo.getString("lastname");
					
					if (!email.equals("email")){
						logger.info("Sending notification to " + firstname + " " + lastname);
						emailBody.append(firstname).append(" ").append(lastname).append("\r\n\r\n\r\n");
						emailBody.append("The following items that you requested are now available for online usage. ");
						emailBody.append("You can checkout the items by accessing your eContent at {$siteUrl}/MyResearch/MyEContent.\r\n\r\n");
						emailBody.append("***Please Note***\r\n");
						emailBody.append("If you no longer need these items, please cancel them online at: {$siteUrl}/MyResearch/MyEContent.  This will allow these items to be available for the next patron.\r\n\r\n");
						
						//Get a list of records that are available where notices have not been sent
						getAvailableHoldsForUser.setLong(1, userId);
						ResultSet availableHolds = getAvailableHoldsForUser.executeQuery();
						while (availableHolds.next()){
							String title = availableHolds.getString("title");
							String author = availableHolds.getString("author");
							emailBody.append("    ").append(title).append(" by ").append(author).append("\r\n");
							long datePlaced = availableHolds.getLong("datePlaced");
							Date datePlacedDate = new Date(datePlaced * 1000);
							long dateHoldExpires = new Date().getTime() + 1000 * 60 * 60 * 24 * 5; // Add 5 days
							Date dateHoldExpiresDate = new Date(dateHoldExpires);
							
							emailBody.append("    Placed on hold: ").append(dateFormat.format(datePlacedDate)).append("    Expires: ").append(dateFormat.format(dateHoldExpiresDate)).append("\r\n");
						}
						
						emailBody.append("Thank you,\r\n\r\n");
						emailBody.append(noticeLibraryName).append("\r\n");
						emailBody.append(siteUrl).append("\r\n");
						
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
		
		// Get system properties
		Properties props = System.getProperties();

		// Setup mail server
		props.put("mail.smtp.host", mailHost);

		// Get session
		Session session = Session.getDefaultInstance(props, null);
		
		try {
			// Define message
			MimeMessage message = new MimeMessage(session);
			message.setFrom(new InternetAddress(mailFrom));
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
			PreparedStatement getAbandonedHolds = econtentConn.prepareStatement("SELECT id, recordId, itemId FROM econtent_hold WHERE dateUpdated < ? AND status ='available'");
			PreparedStatement abandonHold = econtentConn.prepareStatement("UPDATE econtent_hold SET status = 'abandoned', dateUpdated = ? WHERE id = ?");
			getAbandonedHolds.setLong(1, latestDateToRemainActive);
			ResultSet abandonedHolds = getAbandonedHolds.executeQuery();
			while (abandonedHolds.next()){
				long id = abandonedHolds.getLong("id");
				String recordId = abandonedHolds.getString("recordId");
				String itemId = abandonedHolds.getString("itemId");
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
					processHoldQueue(recordId, itemId, curTimeSeconds, logger);
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
	private void processHoldQueue(String recordId, String itemId, long curTimeSeconds, Logger logger) throws SQLException {
		if (getNextAvailableHold == null){
			getNextAvailableHold = econtentConn.prepareStatement("SELECT id FROM econtent_hold WHERE recordId = ? AND itemId = ? AND status='active' ORDER BY datePlaced ASC LIMIT 0, 1");
		}
		if (markHoldAvailable == null){
			markHoldAvailable = econtentConn.prepareStatement("UPDATE econtent_hold SET status='available', dateUpdated=? WHERE id = ?");
		}
		//Check to see if there are holds and return the item to the next user
		getNextAvailableHold.setString(1, recordId);
		getNextAvailableHold.setString(2, itemId);
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
			PreparedStatement getOverdueItems = econtentConn.prepareStatement("SELECT id, recordId, itemId, userId, dateDue FROM econtent_checkout WHERE status='out' AND dateDue < " + curTimeSeconds);
			PreparedStatement returnOverdueItem = econtentConn.prepareStatement("UPDATE econtent_checkout SET status = 'returned', dateReturned = ? WHERE id=?");
			ResultSet overdueItems = getOverdueItems.executeQuery();
			while (overdueItems.next()){
				long id = overdueItems.getLong("id");
				String recordId = overdueItems.getString("recordId");
				String itemId = overdueItems.getString("itemId");
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
					processHoldQueue(recordId, itemId, curTimeSeconds, logger);
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
