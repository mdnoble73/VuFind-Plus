package org.vufind;

import java.math.BigInteger;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.HashMap;
import java.util.HashSet;

/**
 * Description goes here
 * RecordGrouping
 * User: Mark Noble
 * Date: 11/15/13
 * Time: 9:02 AM
 */
public class GroupedWork implements Cloneable{
	public String title = "";              //Up to 100 chars
	public String author = "";             //Up to 50  chars
	public String subtitle = "";           //Up to 175 chars
	public String groupingCategory = "";   //Up to 25  chars

	public HashSet<RecordIdentifier> identifiers = new HashSet<RecordIdentifier>();

	private static HashMap<String, String> categoryMap = new HashMap<String, String>();
	static {
		categoryMap.put("other", "book");
		categoryMap.put("book", "book");
		categoryMap.put("ebook", "book");
		categoryMap.put("audio", "book");
		categoryMap.put("music", "music");
		categoryMap.put("movie", "movie");
	}

	public GroupedWork(){

	}

	public GroupedWork(GroupedRecord groupedRecord){
		this.title = groupedRecord.title;
		this.author = groupedRecord.author;
		this.subtitle = groupedRecord.subtitle;
		groupingCategory = categoryMap.get(groupedRecord.groupingCategory);
	}

	public String getPermanentId() {
		String permanentId = null;
		try {
			MessageDigest idGenerator = MessageDigest.getInstance("MD5");
			if (title.equals("")){
				idGenerator.update("--null--".getBytes());
			}else{
				idGenerator.update(title.getBytes());
			}
			if (subtitle.equals("")){
				idGenerator.update("--null--".getBytes());
			}else{
				idGenerator.update(subtitle.getBytes());
			}
			if (author.equals("")){
				idGenerator.update("--null--".getBytes());
			}else{
				idGenerator.update(author.getBytes());
			}
			if (groupingCategory.equals("")){
				idGenerator.update("--null--".getBytes());
			}else{
				idGenerator.update(groupingCategory.getBytes());
			}
			permanentId = new BigInteger(1, idGenerator.digest()).toString(16);
			while (permanentId.length() < 32){
				permanentId = "0" + permanentId;
			}
			//Insert -'s for formatting
			StringBuilder formattedId = new StringBuilder();
			formattedId.append(permanentId.substring(0, 8))
					.append("-")
					.append(permanentId.substring(8,12))
					.append("-")
					.append(permanentId.substring(12,16))
					.append("-")
					.append(permanentId.substring(16,20))
					.append("-")
					.append(permanentId.substring(20));
			permanentId = formattedId.toString();
		} catch (NoSuchAlgorithmException e) {
			System.out.println("Error generating permanent id" + e.toString());
		}
		//System.out.println("Permanent Id is " + permanentId);
		return permanentId;
	}

	public GroupedWork clone(){

		try {
			GroupedWork tempWork = (GroupedWork)super.clone();
			return tempWork;
		} catch (CloneNotSupportedException e) {
			e.printStackTrace();  //To change body of catch statement use File | Settings | File Templates.
			return null;
		}
	}
}
