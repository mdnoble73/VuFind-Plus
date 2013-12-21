package org.vufind;

import java.math.BigInteger;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.HashMap;
import java.util.HashSet;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Description goes here
 * RecordGrouping
 * User: Mark Noble
 * Date: 11/15/13
 * Time: 9:02 AM
 */
public class GroupedWork implements Cloneable{
	private String title = "";              //Up to 100 chars
	private String author = "";             //Up to 50  chars
	private String subtitle = "";           //Up to 175 chars
	public String groupingCategory = "";   //Up to 25  chars

	public HashSet<RecordIdentifier> identifiers = new HashSet<RecordIdentifier>();

	private static HashMap<String, String> categoryMap = new HashMap<String, String>();
	static {
		categoryMap.put("other", "other");
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

	private String normalizeAuthor(String author) {
		String groupingAuthor = author.replaceAll("(?<=[A-Z])\\\\.(?=(\\\\s|[A-Z]|$))", " ");
		groupingAuthor = groupingAuthor.replaceAll("[^\\w\\s]", "").trim().toLowerCase();
		groupingAuthor = groupingAuthor.replaceAll("\\s{2,}", " ");
		if (groupingAuthor.length() > 50){
			groupingAuthor = groupingAuthor.substring(0, 50);
		}
		groupingAuthor = groupingAuthor.trim();
		return groupingAuthor;
	}


	private String normalizeSubtitle(String originalTitle) {
		if (originalTitle.length() > 0){
			String groupingSubtitle = originalTitle.replaceAll("&", "and");
			//Remove some common subtitles that are meaningless
			groupingSubtitle = groupingSubtitle.replaceAll("[^\\w\\s]", "").toLowerCase().trim();
			groupingSubtitle = groupingSubtitle.replaceAll("\\s{2,}", " ");
			groupingSubtitle = groupingSubtitle.replaceAll("^((a|una)\\s(.*)novel(a|la)?|a(.*)memoir|a(.*)mystery|a(.*)thriller|by\\s(.+)|a novel of .*|stories|an autobiography|a biography|a memoir in books|\\d+.*ed(ition)?|\\d+.*update|1st\\s+ed.*|a .* story|a .*\\s?book|poems|the movie)$", "");
			if (groupingSubtitle.length() > 175){
				groupingSubtitle = groupingSubtitle.substring(0, 175);
			}
			groupingSubtitle = groupingSubtitle.trim();
			return groupingSubtitle;
		}else{
			return originalTitle;
		}
	}


	private String normalizeTitle(String fullTitle, int numNonFilingCharacters) {
		String groupingTitle;
		if (numNonFilingCharacters > 0 && numNonFilingCharacters < fullTitle.length()){
			groupingTitle = fullTitle.substring(numNonFilingCharacters);
		}else{
			groupingTitle = fullTitle;
		}

		groupingTitle = makeValueSortable(groupingTitle);

		//Fix abbreviations
		groupingTitle = groupingTitle.replaceAll("(?<=[A-Z])\\\\.(?=(\\\\s|[A-Z]|$))", " ");
		//Replace & with and for better matching
		groupingTitle = groupingTitle.replace("&", "and");
		groupingTitle = groupingTitle.replaceAll("[^\\w\\s]", "").toLowerCase();
		//Replace consecutive spaces
		groupingTitle = groupingTitle.replaceAll("\\s{2,}", " ");

		int titleEnd = 100;
		if (titleEnd < groupingTitle.length()) {
			groupingTitle = groupingTitle.substring(0, titleEnd);
		}
		groupingTitle = groupingTitle.trim();
		return groupingTitle;
	}

	public GroupedWork clone() throws CloneNotSupportedException {

		try {
			return (GroupedWork)super.clone();
		} catch (CloneNotSupportedException e) {
			e.printStackTrace();  //To change body of catch statement use File | Settings | File Templates.
			return null;
		}
	}

	public String getTitle() {
		return title;
	}

	public void setTitle(String title, int numNonFilingCharacters) {
		this.title = normalizeTitle(title, numNonFilingCharacters);
	}

	public String getAuthor() {
		return author;
	}

	public void setAuthor(String author) {
		this.author = normalizeAuthor(author);
	}

	public String getSubtitle() {
		return subtitle;
	}

	public void setSubtitle(String subtitle) {
		this.subtitle = normalizeSubtitle(subtitle);
	}

	private static Pattern sortTrimmingPattern = Pattern.compile("(?i)^(?:(?:a|an|the|el|la|\"|')\\s)(.*)$");
	private static String makeValueSortable(String curTitle) {
		if (curTitle == null) return "";
		String sortTitle = curTitle.toLowerCase();
		Matcher sortMatcher = sortTrimmingPattern.matcher(sortTitle);
		if (sortMatcher.matches()) {
			sortTitle = sortMatcher.group(1);
		}
		sortTitle = sortTitle.trim();
		return sortTitle;
	}
}
