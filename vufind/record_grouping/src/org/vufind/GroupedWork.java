package org.vufind;

import java.math.BigInteger;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
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
	private String subtitle = "";           //Up to 175 chars
	private String author = "";             //Up to 50  chars
	public String groupingCategory = "";   //Up to 25  chars

	public HashSet<RecordIdentifier> identifiers = new HashSet<RecordIdentifier>();

	public String getPermanentId() {
		String permanentId = null;
		try {
			MessageDigest idGenerator = MessageDigest.getInstance("MD5");
			String fullTitle = getFullTitle();
			if (fullTitle.equals("")){
				idGenerator.update("--null--".getBytes());
			}else{
				idGenerator.update(fullTitle.getBytes());
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
			permanentId = permanentId.substring(0, 8) + "-" + permanentId.substring(8, 12) + "-" + permanentId.substring(12, 16) + "-" + permanentId.substring(16, 20) + "-" + permanentId.substring(20);
		} catch (NoSuchAlgorithmException e) {
			System.out.println("Error generating permanent id" + e.toString());
		}
		//System.out.println("Permanent Id is " + permanentId);
		return permanentId;
	}

	static Pattern authorExtract1 = Pattern.compile("^(.*)\\spresents.*$");
	static Pattern authorExtract2 = Pattern.compile("^(?:(?:a|an)\\s)?(.*)\\spresentation.*$");
	static Pattern homeEntertainmentRemoval = Pattern.compile("^(.*)\\shome entertainment$");
	static Pattern distributedByRemoval = Pattern.compile("^distributed (?:in.*\\s)?by\\s(.*)$");
	static Pattern initialsFix = Pattern.compile("(?<=[A-Z])\\.(?=(\\s|[A-Z]|$))");
	static Pattern specialCharacterStrip = Pattern.compile("[^\\w\\s]");
	static Pattern consecutiveCharacterStrip = Pattern.compile("\\s{2,}");
	static Pattern bracketedCharacterStrip = Pattern.compile("\\[(.*?)\\]");

	private String normalizeAuthor(String author) {
		String groupingAuthor = initialsFix.matcher(author).replaceAll(" ");
		groupingAuthor = bracketedCharacterStrip.matcher(groupingAuthor).replaceAll("");
		groupingAuthor = specialCharacterStrip.matcher(groupingAuthor).replaceAll("").trim().toLowerCase();
		groupingAuthor = consecutiveCharacterStrip.matcher(groupingAuthor).replaceAll(" ");
		//extract common additional info (especially for movie studios)
		Matcher authorExtract1Matcher = authorExtract1.matcher(groupingAuthor);
		if (authorExtract1Matcher.find()){
			groupingAuthor = authorExtract1Matcher.group(1);
		}
		Matcher authorExtract2Matcher = authorExtract2.matcher(groupingAuthor);
		if (authorExtract2Matcher.find()){
			groupingAuthor = authorExtract2Matcher.group(1);
		}
		//Remove home entertainment
		Matcher homeEntertainmentMatcher = homeEntertainmentRemoval.matcher(groupingAuthor);
		if (homeEntertainmentMatcher.find()){
			groupingAuthor = homeEntertainmentMatcher.group(1);
		}
		//Remove home entertainment
		Matcher distributedByRemovalMatcher = distributedByRemoval.matcher(groupingAuthor);
		if (distributedByRemovalMatcher.find()){
			groupingAuthor = distributedByRemovalMatcher.group(1);
		}

		if (groupingAuthor.length() > 50){
			groupingAuthor = groupingAuthor.substring(0, 50);
		}
		groupingAuthor = groupingAuthor.trim();
		groupingAuthor = RecordGroupingProcessor.mapAuthorAuthority(groupingAuthor);

		return groupingAuthor;
	}

	static Pattern commonSubtitlesPattern = Pattern.compile("^(.*)((a|una)\\s(.*)novel(a|la)?|a(.*)memoir|a(.*)mystery|a(.*)thriller|by\\s(.+)|a novel of .*|stories|an autobiography|a biography|a memoir in books|\\d+.*ed(ition)?|\\d+.*update|1st\\s+ed.*|an? .* story|a .*\\s?book|poems|the movie|.*series book \\d+)$");
	private String normalizeSubtitle(String originalTitle) {
		if (originalTitle.length() > 0){
			String groupingSubtitle = originalTitle.replaceAll("&", "and");

			//Remove any bracketed parts of the title
			groupingSubtitle = bracketedCharacterStrip.matcher(groupingSubtitle).replaceAll("");

			groupingSubtitle = specialCharacterStrip.matcher(groupingSubtitle).replaceAll("").toLowerCase().trim();
			groupingSubtitle = consecutiveCharacterStrip.matcher(groupingSubtitle).replaceAll(" ");

			//Remove some common subtitles that are meaningless
			Matcher commonSubtitleMatcher = commonSubtitlesPattern.matcher(groupingSubtitle);
			if (commonSubtitleMatcher.matches()){
				groupingSubtitle = commonSubtitleMatcher.group(1);
			}

			//Normalize numeric titles
			groupingSubtitle = groupingSubtitle.replace("1st", "first");
			groupingSubtitle = groupingSubtitle.replace("2nd", "second");
			groupingSubtitle = groupingSubtitle.replace("3rd", "third");
			groupingSubtitle = groupingSubtitle.replace("4th", "fourth");
			groupingSubtitle = groupingSubtitle.replace("5th", "fifth");
			groupingSubtitle = groupingSubtitle.replace("6th", "sixth");
			groupingSubtitle = groupingSubtitle.replace("7th", "seventh");
			groupingSubtitle = groupingSubtitle.replace("8th", "eighth");
			groupingSubtitle = groupingSubtitle.replace("9th", "ninth");
			groupingSubtitle = groupingSubtitle.replace("10th", "tenth");

			if (groupingSubtitle.length() > 175){
				groupingSubtitle = groupingSubtitle.substring(0, 175);
			}
			groupingSubtitle = groupingSubtitle.trim();
			return groupingSubtitle;
		}else{
			return originalTitle;
		}
	}

	static Pattern subtitleIndicator = Pattern.compile("[:;/=]") ;
	private String normalizeTitle(String fullTitle, int numNonFilingCharacters) {
		String groupingTitle;
		if (numNonFilingCharacters > 0 && numNonFilingCharacters < fullTitle.length()){
			groupingTitle = fullTitle.substring(numNonFilingCharacters);
		}else{
			groupingTitle = fullTitle;
		}

		groupingTitle = makeValueSortable(groupingTitle);

		//Remove any bracketed parts of the title
		String tmpTitle = bracketedCharacterStrip.matcher(groupingTitle).replaceAll("");
		//Make sure we don't strip the entire title
		if (tmpTitle.length() > 0){
			groupingTitle = tmpTitle;
		}

		//If the title includes a : in it, take the first part as the title and the second as the subtitle
		//TODO: test this
		Matcher subtitleMatcher = subtitleIndicator.matcher(groupingTitle);
		if (subtitleMatcher.find()){
			int startPos = subtitleMatcher.start();
			setSubtitle(groupingTitle.substring(startPos + 1));
			groupingTitle = groupingTitle.substring(0, startPos);
		}

		//Fix abbreviations
		groupingTitle = initialsFix.matcher(groupingTitle).replaceAll(" ");
		//Replace & with and for better matching
		groupingTitle = groupingTitle.replace("&", "and");
		groupingTitle = specialCharacterStrip.matcher(groupingTitle).replaceAll("").toLowerCase();
		//Replace consecutive spaces
		groupingTitle = consecutiveCharacterStrip.matcher(groupingTitle).replaceAll(" ");

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

	public String getFullTitle(){
		String fullTitle = this.title + " " + this.subtitle;
		return fullTitle.trim();
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
		if (this.subtitle.length() != 0){
			//We got multiple subtitles by splitting the original title.
			//move the current subtitle back to the title and then add this subtitle
			String tmpTitle = this.title + " " + this.subtitle;
			int titleEnd = 100;
			if (titleEnd < tmpTitle.length()) {
				tmpTitle = tmpTitle.substring(0, titleEnd);
			}
			this.title = tmpTitle.trim();
		}
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
