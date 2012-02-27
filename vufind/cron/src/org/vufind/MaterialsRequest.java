package org.vufind;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.Properties;

import javax.mail.Message;
import javax.mail.Session;
import javax.mail.Transport;
import javax.mail.internet.InternetAddress;
import javax.mail.internet.MimeMessage;

import org.apache.log4j.Logger;
import org.ini4j.Profile.Section;

/**
 * Handles processing background tasks for Materials Requests including 
 * sending emails to patrons and generating holds
 * 
 * Copyright (C) Anythink Libraries 2012.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Anythink Libraries 2012.
 * 
 */
public class MaterialsRequest implements IProcessHandler{
	private String vufindDBConnectionInfo;
	private Connection vufindConn = null;
	private Logger logger;
	private String localPath;
	private String libraryName;
	private String circulationPhone;
	private String circulationEmail;
	private String circulationUrl;
	private String emailHost;
	private String emailPort;
	private String emailFrom;
	
	@Override
	public void doCronProcess(Section processSettings, Section generalSettings, Logger logger) {
		this.logger = logger;
		if (!loadConfig(processSettings, generalSettings, logger)){
			return;
		}
		
		try {
			//Connect to the vufind database
			vufindConn = DriverManager.getConnection(vufindDBConnectionInfo);
			
			sendEmailNotifications();
			
			generateHolds();
			
			addRequestsToIndex();
			
			// Disconnect from the database
			vufindConn.close();
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error establishing connection to database ", ex);
			return;
		}
	}
	
	/**
	 * Send notifications to patrons based on actions performed by the cataloging group. 
	 */
	private void sendEmailNotifications(){
		//Get a list of requests that need notifications sent
		try {
			PreparedStatement requestsToEmailStmt = vufindConn.prepareStatement("SELECT * FROM materials_request inner join user on user.id = materials_request.createdBy WHERE emailSent = 0 AND status IN ('owned', 'purchased', 'ILLplaced', 'notEnoughInfo')");
			ResultSet requestsToEmail = requestsToEmailStmt.executeQuery();
			//For each request, 
			while (requestsToEmail.next()){
				//Get information about the patron to send the notification to
				String firstName = requestsToEmail.getString("firstName");
				String lastName = requestsToEmail.getString("lastName");
				String email = requestsToEmail.getString("email");
			
				//Get the template to use while sending the notification
				String status = requestsToEmail.getString("status");
				File emailTemplate = new File(localPath + "/cron/templates/" + status + ".tpl");
				if (!emailTemplate.exists()){
					emailTemplate = new File(localPath + "/cron/templates/materialsRequest.tpl");
				}
				if (!emailTemplate.exists()){
					logger.error("Could not find template for email text to send to patron");
				}
			
				BufferedReader reader = new BufferedReader(new FileReader(emailTemplate));
				StringBuffer emailBody = new StringBuffer();
				String curLine = reader.readLine();
				while (curLine != null){
					emailBody.append(curLine + "\r\n" );
					curLine = reader.readLine();
				}
				
				String emailBodyStr = emailBody.toString();
				//Replace variables as needed
				emailBodyStr = emailBodyStr.replaceAll("\\{firstName\\}", firstName);
				emailBodyStr = emailBodyStr.replaceAll("\\{lastName\\}", lastName);
				emailBodyStr = emailBodyStr.replaceAll("\\{email\\}", email);
				emailBodyStr = emailBodyStr.replaceAll("\\{status\\}", status);
				if (libraryName != null){
					emailBodyStr = emailBodyStr.replaceAll("\\{libraryName\\}", libraryName);
				}
				if (circulationPhone != null){
					emailBodyStr = emailBodyStr.replaceAll("\\{circulationPhone\\}", circulationPhone);
				}
				if (circulationEmail != null){
					emailBodyStr = emailBodyStr.replaceAll("\\{circulationEmail\\}", circulationEmail);
				}
				if (circulationUrl != null){
					emailBodyStr = emailBodyStr.replaceAll("\\{circulationUrl\\}", circulationUrl);
				}
			
				//Send the email
				String emailSubject = "Your materials request was processed.";
				sendEmail(email, emailSubject, emailBodyStr);
			
				//Mark that the email was sent
			}
		} catch (Exception e) {
			logger.error("Error sending emails for materials requests", e);
		}
		
	}
	
	private boolean sendEmail(String email, String emailSubject, String emailBody) {
		logger.info("Sending notice to " + email);
		logger.info("Email Subject: " + emailSubject);
		logger.info("Email Body: " + emailBody);
		
		// Get system properties
		Properties props = System.getProperties();

		// Setup mail server
		props.put("mail.smtp.host", emailHost);
		//props.put("mail.smtp.port", emailPort);

		// Get session
		Session session = Session.getDefaultInstance(props, null);
		
		try {
			// Define message
			MimeMessage message = new MimeMessage(session);
			message.setFrom(new InternetAddress(emailFrom));
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
	
	/**
	 * If a title has been ordered, need to place a hold for each person that has 
	 * added it to their wishlist
	 */
	private void generateHolds(){
		//TBD: need to determine whether this is occurring based on wishlist or when the original order is placed. 
		
	}
	
	private void addRequestsToIndex(){
		//TBD: May not be needed depending on workflow
	}
	protected boolean loadConfig(Section processSettings, Section generalSettings, Logger logger) {
		vufindDBConnectionInfo = generalSettings.get("database");
		if (vufindDBConnectionInfo == null || vufindDBConnectionInfo.length() == 0) {
			logger.error("Database connection information for vufind database not found in General Settings.  Please specify connection information in a database key.");
			return false;
		}
		
		localPath = generalSettings.get("local");
		if (localPath == null || localPath.length() == 0) {
			logger.error("Local path to vufind installation not found in General Settings.  Please specify location in local key.");
			return false;
		}
		emailHost = generalSettings.get("emailHost");
		if (emailHost == null || emailHost.length() == 0) {
			logger.error("Email Host not found in General Settings.  Please specify host in emailHost key.");
			return false;
		}
		emailPort = generalSettings.get("emailPort");
		if (emailPort == null || emailPort.length() == 0) {
			logger.error("Email Port not found in General Settings.  Please specify host in emailPort key.");
			return false;
		}
		emailFrom = processSettings.get("emailFrom");
		if (emailFrom == null || emailFrom.length() == 0) {
			logger.error("Email From address not found in Process Settings.  Please specify host in emailPort key.");
			return false;
		}
		
		libraryName = processSettings.get("libraryName");
		if (libraryName == null || libraryName.length() == 0) {
			logger.warn("Library Name not found in Process Settings.  Please specify add libraryName key.");
		}
		circulationPhone = processSettings.get("circulationPhone");
		if (circulationPhone == null || circulationPhone.length() == 0) {
			logger.warn("Circulation Department Phone Number not found in Process Settings.  Please specify add circulationPhone key.");
		}
		circulationEmail = processSettings.get("circulationEmail");
		if (circulationEmail == null || circulationEmail.length() == 0) {
			logger.warn("Circulation Department Email not found in Process Settings.  Please specify add circulationPhone key.");
		}
		circulationUrl = processSettings.get("circulationUrl");
		if (circulationUrl == null || circulationUrl.length() == 0) {
			logger.warn("Circulation Department not found in Process Settings.  Please specify add circulationUrl key.");
		}
		return true;
	}

}
