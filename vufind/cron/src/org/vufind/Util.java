package org.vufind;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.OutputStreamWriter;
import java.io.Reader;
import java.io.StringWriter;
import java.io.Writer;
import java.net.HttpURLConnection;
import java.net.MalformedURLException;
import java.net.URL;
import java.nio.channels.FileChannel;
import java.security.MessageDigest;
import java.util.HashSet;
import java.util.List;

import org.vufind.CopyNoOverwriteResult.CopyResult;

public class Util {
	

	public static String convertStreamToString(InputStream is) throws IOException {
		/*
		 * To convert the InputStream to String we use the Reader.read(char[]
		 * buffer) method. We iterate until the Reader return -1 which means there's
		 * no more data to read. We use the StringWriter class to produce the
		 * string.
		 */
		if (is != null) {
			Writer writer = new StringWriter();

			char[] buffer = new char[1024];
			try {
				Reader reader = new BufferedReader(new InputStreamReader(is, "UTF-8"));
				int n;
				while ((n = reader.read(buffer)) != -1) {
					writer.write(buffer, 0, n);
				}
			} finally {
				is.close();
			}
			return writer.toString();
		} else {
			return "";
		}
	}

	public static boolean doSolrUpdate(String baseIndexUrl, String body) {
		try {
			HttpURLConnection conn = null;
			OutputStreamWriter wr = null;
			URL url = new URL(baseIndexUrl + "/update/");
			conn = (HttpURLConnection) url.openConnection();
			conn.setDoOutput(true);
			conn.addRequestProperty("Content-Type", "text/xml");
			wr = new OutputStreamWriter(conn.getOutputStream());
			wr.write(body);
			wr.flush();

			// Get the response
			InputStream _is;
			boolean doOuptut = false;
			if (conn.getResponseCode() == 200) {
				_is = conn.getInputStream();
			} else {
				System.out.println("Error in update");
				System.out.println("  " + body);
				/* error from server */
				_is = conn.getErrorStream();
				doOuptut = true;
			}
			BufferedReader rd = new BufferedReader(new InputStreamReader(_is));
			String line;
			while ((line = rd.readLine()) != null) {
				if (doOuptut) System.out.println(line);
			}
			wr.close();
			rd.close();
			conn.disconnect();

			return true;
		} catch (MalformedURLException e) {
			System.out.println("Invalid url updating index " + e.toString());
			return false;
		} catch (IOException e) {
			System.out.println("IO Exception updating index " + e.toString());
			e.printStackTrace();
			return false;
		}
	}

	public static String getCRSeparatedString(List<String> values) {
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

	public static void copyFile(File sourceFile, File destFile) throws IOException {
		if (!destFile.exists()) {
			destFile.createNewFile();
		}

		FileChannel source = null;
		FileChannel destination = null;

		try {
			source = new FileInputStream(sourceFile).getChannel();
			destination = new FileOutputStream(destFile).getChannel();
			destination.transferFrom(source, 0, source.size());
		} finally {
			if (source != null) {
				source.close();
			}
			if (destination != null) {
				destination.close();
			}
		}
	}

	/**
	 * Copys the file to another directory. If there is already a file with that
	 * name, it will not overwrite the existing file. Instead, it will modify the
	 * name by appending a numeric number until it finds a unique name. i.e.
	 * File_1.pdf File_2.pdf etc Up to 99 attempts will be made.
	 * 
	 * @param fileToCopy
	 * @param directoryToCopyTo
	 * @return
	 */
	public static CopyNoOverwriteResult copyFileNoOverwrite(File fileToCopy, File directoryToCopyTo) throws IOException {
		CopyNoOverwriteResult result = new CopyNoOverwriteResult();
		if (!directoryToCopyTo.exists()) {
			throw new IOException("Directory to copy to does not exist.");
		}
		int numTries = 0;
		String newFilename = fileToCopy.getName();
		String baseFilename = newFilename.substring(0, newFilename.indexOf("."));
		String extension = newFilename.substring(newFilename.indexOf(".") + 1, newFilename.length());
		File newFile = new File(directoryToCopyTo + File.separator + newFilename);
		while (newFile.exists() && numTries < 100) {
			// Check to see if the checksums of the file are the same and if so,
			// return this name
			// without copying.
			if (newFile.length() == fileToCopy.length()) {
				try {
					String newFileChecksum = getMD5Checksum(newFile);
					String fileToCopyChecksum = getMD5Checksum(newFile);
					if (newFileChecksum.equals(fileToCopyChecksum)) {
						result.setCopyResult(CopyResult.FILE_ALREADY_EXISTS);
						result.setNewFilename(newFilename);
						return result;
					}
				} catch (Exception e) {
					throw new IOException("Error getting checksums for files", e);
				}
			}

			numTries++;
			// Get a new name
			newFilename = baseFilename + "_" + numTries + "." + extension;
			newFile = new File(directoryToCopyTo + File.separator + newFilename);

		}

		if (newFile.exists()) {
			// We ran out of tries
			throw new IOException("Unable to copy file due to not finding unique name.");
		}
		// We found a name that hasn't been used, copy it
		Util.copyFile(fileToCopy, newFile);
		result.setCopyResult(CopyResult.FILE_COPIED);
		result.setNewFilename(newFilename);
		return result;
	}
	
	

	public static String cleanIniValue(String value) {
		if (value == null) {
			return null;
		}
		value = value.trim();
		if (value.startsWith("\"")) {
			value = value.substring(1);
		}
		if (value.endsWith("\"")) {
			value = value.substring(0, value.length() - 1);
		}
		return value;
	}

	public static byte[] createChecksum(File filename) throws Exception {
		InputStream fis = new FileInputStream(filename);

		byte[] buffer = new byte[1024];
		MessageDigest complete = MessageDigest.getInstance("MD5");
		int numRead;

		do {
			numRead = fis.read(buffer);
			if (numRead > 0) {
				complete.update(buffer, 0, numRead);
			}
		} while (numRead != -1);

		fis.close();
		return complete.digest();
	}

	// see this How-to for a faster way to convert
	// a byte array to a HEX string
	public static String getMD5Checksum(File filename) throws Exception {
		byte[] b = createChecksum(filename);
		String result = "";

		for (int i = 0; i < b.length; i++) {
			result += Integer.toString((b[i] & 0xff) + 0x100, 16).substring(1);
		}
		return result;
	}

}
