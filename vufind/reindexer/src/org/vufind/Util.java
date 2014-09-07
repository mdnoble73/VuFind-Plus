package org.vufind;

import org.apache.log4j.Logger;

import javax.net.ssl.HostnameVerifier;
import javax.net.ssl.HttpsURLConnection;
import javax.net.ssl.SSLSession;
import java.io.*;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.nio.channels.FileChannel;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class Util {
	public static String getCRSeparatedString(Object values) {
		StringBuffer crSeparatedString = new StringBuffer();
		if (values instanceof String){
			crSeparatedString.append((String)values);
		}else if (values instanceof Iterable){
			@SuppressWarnings("unchecked")
			Iterable<String> valuesIterable = (Iterable<String>)values;
			for (String curValue : valuesIterable) {
				if (crSeparatedString.length() > 0) {
					crSeparatedString.append("\r\n");
				}
				crSeparatedString.append(curValue);
			}
		}
		return crSeparatedString.toString();
	}
	
	public static String getCRSeparatedStringFromSet(Set<String> values) {
		StringBuffer crSeparatedString = new StringBuffer();
		for (String curValue : values) {
			if (crSeparatedString.length() > 0) {
				crSeparatedString.append("\r\n");
			}
			crSeparatedString.append(curValue);
		}
		return crSeparatedString.toString();
	}

	public static String getCRSeparatedString(HashSet<String> values) {
		StringBuffer crSeparatedString = new StringBuffer();
		for (String curValue : values) {
			if (crSeparatedString.length() > 0) {
				crSeparatedString.append("\r\n");
			}
			crSeparatedString.append(curValue);
		}
		return crSeparatedString.toString();
	}

	public static boolean copyFile(File sourceFile, File destFile) throws IOException {
		if (!sourceFile.exists()){
			return false;
		}
		if (!destFile.exists()) {
			destFile.createNewFile();
		}

		FileChannel source = null;
		FileChannel destination = null;

		try {
			source = new FileInputStream(sourceFile).getChannel();
			destination = new FileOutputStream(destFile).getChannel();
			destination.transferFrom(source, 0, source.size());
		}catch (Exception e){
			return false;
		} finally {
			if (source != null) {
				source.close();
			}
			if (destination != null) {
				destination.close();
			}
		}
		return true;
	}

	public static String cleanIniValue(String value) {
		if (value == null) {
			return null;
		}
		value = value.trim();
		if (value.length() > 0 && value.charAt(0) == '"') {
			value = value.substring(1);
		}
		if (value.length() > 0 && value.charAt(value.length() -1) == '"') {
			value = value.substring(0, value.length() - 1);
		}
		return value;
	}

	public static String trimTo(int maxCharacters, String stringToTrim) {
		if (stringToTrim == null) {
			return null;
		}
		if (stringToTrim.length() > maxCharacters) {
			stringToTrim = stringToTrim.substring(0, maxCharacters);
		}
		return stringToTrim.trim();
	}

	public static URLPostResponse getURL(String url, Logger logger) {
		URLPostResponse retVal;
		HttpURLConnection conn = null;
		try {
			logger.debug("Getting URL " + url);
			URL emptyIndexURL = new URL(url);
			conn = (HttpURLConnection) emptyIndexURL.openConnection();
			if (conn instanceof HttpsURLConnection){
				HttpsURLConnection sslConn = (HttpsURLConnection)conn;
				sslConn.setHostnameVerifier(new HostnameVerifier() {
					
					@Override
					public boolean verify(String hostname, SSLSession session) {
						//Do not verify host names
						return true;
					}
				});
			}
			conn.setConnectTimeout(3000);
			conn.setReadTimeout(450000);
			//logger.debug("  Opened connection");
			StringBuffer response = new StringBuffer();
			if (conn.getResponseCode() == 200) {
				//logger.debug("  Got successful response");
				// Get the response
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getInputStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				//logger.debug("  Finished reading response");
				rd.close();
				retVal = new URLPostResponse(true, 200, response.toString());
			} else {
				logger.error("Received error " + conn.getResponseCode() + " getting " + url);
				// Get any errors
				BufferedReader rd = new BufferedReader(new InputStreamReader(conn.getErrorStream()));
				String line;
				while ((line = rd.readLine()) != null) {
					response.append(line);
				}
				logger.debug("  Finished reading response");

				rd.close();
				retVal = new URLPostResponse(false, conn.getResponseCode(), response.toString());
			}

		} catch (MalformedURLException e) {
			logger.error("URL to post (" + url + ") is malformed", e);
			retVal = new URLPostResponse(false, -1, "URL to post (" + url + ") is malformed");
		} catch (IOException e) {
			logger.error("Error posting to url \r\n" + url, e);
			retVal = new URLPostResponse(false, -1, "Error posting to url \r\n" + url + "\r\n" + e.toString());
		}
		logger.debug("  Finished calling url");
		return retVal;
	}

	public static String prepForCsv(String input, boolean trimTrailingPunctuation, boolean crSeparatedFields) {
		if (input == null){
			return "";
		}
		if (trimTrailingPunctuation) {
			input = trimTrailingPunctuation(input);
		}
		input = input.replaceAll("'", "`");
		input = input.replaceAll("\\|", " ");
		input = input.replaceAll(";", " ");
		if (crSeparatedFields){
			input = input.replaceAll("[\\t]", " ");
			input = input.replaceAll("\\r\\n|\\r|\\n", ";");
		}else{
			input = input.replaceAll("[\\r\\n\\t]", " ");
		}
		
		// input = regex.matcher(input).replaceAll("");
		return input;
	}

	public static String trimTrailingPunctuation(String format) {
		if (format == null){
			return "";
		}
		while (format.endsWith("/") || format.endsWith(",") || format.endsWith(".") || format.endsWith(";")) {
			format = format.substring(0, format.length() - 1).trim();
		}
		return format;
	}

	public static Collection<String> trimTrailingPunctuation(Set<String> fieldList) {
		HashSet<String> trimmedCollection = new HashSet<String>();
		for (String field : fieldList){
			trimmedCollection.add(trimTrailingPunctuation(field));
		}
		return trimmedCollection;
	}

	private static Pattern sortTrimmingPattern = Pattern.compile("(?i)^(?:(?:a|an|the|el|la|\"|')\\s)(.*)$");
	public static String makeValueSortable(String curTitle) {
		if (curTitle == null) return "";
		String sortTitle = curTitle.toLowerCase();
		Matcher sortMatcher = sortTrimmingPattern.matcher(sortTitle);
		if (sortMatcher.matches()) {
			sortTitle = sortMatcher.group(1);
		}
		sortTitle = sortTitle.replaceAll("\\W", " "); //get rid of non alpha numeric characters
		sortTitle = sortTitle.replaceAll("\\s{2,}", " "); //get rid of duplicate spaces 
		sortTitle = sortTitle.trim();
		return sortTitle;
	}
	
	public static Long getDaysSinceAddedForDate(Date curDate){
		if (curDate == null){
			return null;
		}
		return (indexDate.getTime() - curDate.getTime()) / (1000 * 60 * 60 * 24);
	}
	private static Date indexDate = new Date();
	public static Date getIndexDate(){
		return indexDate;
	}
	public static LinkedHashSet<String> getTimeSinceAddedForDate(Date curDate) {
		if (curDate == null){
			return null;
		}
		long timeDifferenceDays = (indexDate.getTime() - curDate.getTime())
				/ (1000 * 60 * 60 * 24);
		// System.out.println("Time Difference Days: " + timeDifferenceDays);
		LinkedHashSet<String> result = new LinkedHashSet<String>();
		if (timeDifferenceDays <= 1) {
			result.add("Day");
		}
		if (timeDifferenceDays <= 7) {
			result.add("Week");
		}
		if (timeDifferenceDays <= 30) {
			result.add("Month");
		}
		if (timeDifferenceDays <= 60) {
			result.add("2 Months");
		}
		if (timeDifferenceDays <= 90) {
			result.add("Quarter");
		}
		if (timeDifferenceDays <= 180) {
			result.add("Six Months");
		}
		if (timeDifferenceDays <= 365) {
			result.add("Year");
		}
		return result;
	}


	public static boolean isNumeric(String stringToTest) {
		if (stringToTest == null){
			return false;
		}
		if (stringToTest.length() == 0){
			return false;
		}
		for (char curChar : stringToTest.toCharArray()){
			if (!Character.isDigit(curChar) && curChar != '.'){
				return false;
			}
		}
		return true;
	}

	public static String getCommaSeparatedString(HashSet<String> values) {
		StringBuffer crSeparatedString = new StringBuffer();
		for (String curValue : values) {
			if (crSeparatedString.length() > 0) {
				crSeparatedString.append(",");
			}
			crSeparatedString.append(curValue);
		}
		return crSeparatedString.toString();
	}

	public static boolean compareFiles(File file1, File file2, Logger logger){
		try {
			BufferedReader reader1 = new BufferedReader(new FileReader(file1));
			BufferedReader reader2 = new BufferedReader(new FileReader(file2));
			String curLine1 = reader1.readLine();
			String curLine2 = reader2.readLine();
			boolean filesMatch = Util.compareStrings(curLine1, curLine2);
			while (curLine1 != null && curLine2 != null && filesMatch){
				curLine1 = reader1.readLine();
				curLine2 = reader2.readLine();
				filesMatch = Util.compareStrings(curLine1, curLine2);
			}
			return filesMatch;
		}catch (IOException e){
			logger.error("Error comparing files", e);
			return false;
		}
	}

	private static boolean compareStrings(String curLine1, String curLine2) {
		return curLine1 == null && curLine2 == null || !(curLine1 == null || curLine2 == null) && curLine1.equals(curLine2);
	}
}
