package org.vufind;

import java.math.BigInteger;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.HashSet;

/**
 * A work is a title written by a particular author.  It is unique based on title and author.
 * Since titles and authors are not always entered identically, we check the first 25 characters
 * of the name after the non filing indicators.
 *
 * To check the author we compare each part of the name independently just in case something
 * is entered in reverse order.
 *
 * Rampart Marc Conversion
 * User: Mark Noble
 * Date: 10/17/13
 * Time: 8:55 AM
 */
public class NormalizedRecord {
	public String title = "";     //Up to 100 chars
	public String author = "";    //Up to 50  chars
	public String subtitle = "";  //Up to 175 chars
	public String edition = "";   //Up to 50  chars
	public String format = "";    //Up to 25  chars
	public String publisher = ""; //Up to 50  chars
	public String bibNumber;
	public int numItems;
	public boolean isOclcBib;

	public long id;

	public HashSet<RecordIdentifier> identifiers = new HashSet<RecordIdentifier>();

	public String getPermanentId() {
		String permanentId = null;
		try {
			MessageDigest idGenerator = MessageDigest.getInstance("MD5");
			if (title.equals("")){
				idGenerator.update("--null--".getBytes());
			}else{
				idGenerator.update(title.getBytes());
			}
			if (subtitle == null){
				idGenerator.update("--null--".getBytes());
			}else{
				idGenerator.update(subtitle.getBytes());
			}
			if (author == null){
				idGenerator.update("--null--".getBytes());
			}else{
				idGenerator.update(author.getBytes());
			}
			if (publisher == null){
				idGenerator.update("--null--".getBytes());
			}else{
				idGenerator.update(publisher.getBytes());
			}
			if (format == null){
				idGenerator.update("--null--".getBytes());
			}else{
				idGenerator.update(format.getBytes());
			}
			if (edition == null){
				idGenerator.update("--null--".getBytes());
			}else{
				idGenerator.update(edition.getBytes());
			}
			permanentId = new BigInteger(1, idGenerator.digest()).toString(16);
			while (permanentId.length() < 32){
				permanentId = "0" + permanentId;
			}
			//Insert -'s for formatting
			StringBuffer formattedId = new StringBuffer();
			formattedId.append(permanentId.substring(0, 8))
					.append("-")
					.append(permanentId.substring(8, 12))
					.append("-")
					.append(permanentId.substring(12, 16))
					.append("-")
					.append(permanentId.substring(16, 20))
					.append("-")
					.append(permanentId.substring(20));
			permanentId = formattedId.toString();
		} catch (NoSuchAlgorithmException e) {
			System.out.println("Error generating permanent id" + e.toString());
		}
		//System.out.println("Permanent Id is " + permanentId);
		return permanentId;
	}
}
