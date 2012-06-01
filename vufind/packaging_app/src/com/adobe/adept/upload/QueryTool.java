/*************************************************************************
*
* ADOBE CONFIDENTIAL
* ___________________
*
*  Copyright 2010 Adobe Systems Incorporated
*  All Rights Reserved.
*
* NOTICE:  All information contained herein is, and remains
* the property of Adobe Systems Incorporated and its suppliers,
* if any.  The intellectual and technical concepts contained
* herein are proprietary to Adobe Systems Incorporated and its
* suppliers and are protected by trade secret or copyright law.
* Dissemination of this information or reproduction of this material
* is strictly forbidden unless prior written permission is obtained
* from Adobe Systems Incorporated.
**************************************************************************/
package com.adobe.adept.upload;

import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStream;
import java.io.PrintStream;
import java.io.StringWriter;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.Date;
import java.util.Random;

import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;

import com.adobe.adept.client.XMLUtil;

import javax.xml.transform.OutputKeys;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;


/**
 * The QueryTool sends a QueryResourceItems request to the packaging
 * server. The URL of the packaging server to query should be passed
 * to this tool as the only command-line argument. If no arguments are
 * supplied, the tool will default to the the URL contained in
 * <var>SERVER_URL</var> (which, unless modified, will not work).
 * The server URL MUST be of the form:<br>
 * http://SERVER_NAME/admin/QueryResourceItems
 * <br><br>
 * This tool also accepts a number of command-line flags that extend
 * its functionality. Remember that the first command-line argument MUST be
 * the server URL to be used for making the request. Here is a 
 * list of the accepted flags:
 * <ul>
 * <li><tt>-d</tt> or <tt>-distributor</tt> = Specify a user-defined distributor UUID
 * to make the request. This flag must be followed by the UUID to use. Remember to use
 * the correct password!
 * </li>
 * <li><tt>-f</tt> or <tt>-file</tt> = Specify that the server response (resourceItemList)
 * should be written to an XML file (for use with extracting resource UUIDs for DistribTool).
 * This flag must be followed by the name (or path/name) of the output file to use.
 * </li>
 * <li><tt>-p</tt> or<tt>-pass</tt> = Specify that the request HMAC be signed with a user-
 * defined password. This flag is to be used with a text password (The tool will take the
 * raw SHA1 hash of the password to use as HMAC Key Bytes). This flag must be followed by
 * the password text to use.
 * </li>
 * <li><tt>-p64</tt> or <tt>-pass64</tt> = Specify that the request HMAC be signed with a
 * user-defined password. This flag is to be used with a Base64-encoded Shared Secret (The
 * tool will decode the password string and use the resulting bytes as HMAC Key Bytes). This
 * flag must be followed by the Base64-encoded password String to use.
 * </li>
 * <li><tt>-v</tt> or <tt>-verbose</tt> = Enable verbose display mode. For best functionality,
 * use this flag FIRST if you are using multiple flags. 
 * </li>
 * <li><tt>-rf</tt> or <tt>-resourceFile</tt> = Extract the resource UUIDs from the server
 * response and output them to a text file (for use as a resourceFile for DistribTool). This
 * flag must be followed by the name (or path/name) of the output file to use . 
 * </li>
 * </ul>
 * @author piotrk
 * @version 1.1
 *
 */
public class QueryTool {

	/* *************** P U B L I C   F I E L D S *************** */

	/**
	 * Holds the Distributor's UUID to be used when creating the
	 * QueryResourceItems request
	 */
	public String DIST_UUID = "urn:uuid:00000000-0000-0000-0000-000000000001";

	/**
	 * Holds the DEFAULT URL of the server that the QueryResourceItems request will be
	 * sent to. This value is replaced if a parameter is used to set the server URL.
	 */
	public String SERVER_URL = "http://YOUR_SERVER/admin/QueryResourceItems";

	/** Length of time before request expiration in minutes (from current time) */
	public final int EXPIRATION_INTERVAL = 15;

	/* ************** P R I V A T E   F I E L D S ************** */

	/** Toggle verbose display mode (-v or -verbose flags) */
	private boolean verboseDisplay = false;
	/** Toggle output server response to file (-f or -file flags) */
	private boolean outputToFile = false;
	/** Toggle output resource UUID list to file (-rf or -resourceFile flags) */
	private boolean outputResourceFile = false;
	/** Used to track whether an error has occurred somehow in the request or response */
	private boolean failed = false;
	/** The supplied password is Base64 encoded shared secret (-p64 or -pass64 flags) */
	private boolean useBase64Pass = false;
	
	/** Holds the name of the output File (if the -f or -file flags are used) */
	private String outputFileName = new String("");
	/** Holds the name of the output resourceFile (if the -rf or -resourceFile flags are used) */
	private String resourceFileName = new String("");
	
	/** SharedSecret HMAC Password used for signing the request with HMAC */
	private String HMAC_PASS = null;

	/**
	 * Used during the creation of the nonce, holds an incremented counter
	 * started at a random number
	 */
	private static long counter = (new Random()).nextLong();

	/** Used during the creation of the nonce, holds the start time of the server */
	private static byte[] initTime = createInitTime();

	/* ********************************************************* */
	/* ********************* M E T H O D S ********************* */
	/* ********************************************************* */

	/* ************** S T A T I C   M E T H O D S ************** */

	/**
	 * Fills an array starting at the offset with the bytes of the long
	 * 
	 * @param k
	 *            The source long
	 * @param b
	 *            Byte[] to be filled with the long bytes
	 * @param i
	 *            Offset at which to start filling array
	 */
	public static void longToBytes(long k, byte[] b, int i) {
		b[i] = (byte) (k >> 56);
		b[i + 1] = (byte) (k >> 48);
		b[i + 2] = (byte) (k >> 40);
		b[i + 3] = (byte) (k >> 32);
		b[i + 4] = (byte) (k >> 24);
		b[i + 5] = (byte) (k >> 16);
		b[i + 6] = (byte) (k >> 8);
		b[i + 7] = (byte) k;
	}

	/**
	 * Returns a byte[] with the initial time, used for nonce creation
	 * 
	 * @see QueryTool#longToBytes(long, byte[], int)
	 * @return a byte array of length 8 containing the bytes of the initial time
	 */
	public static byte[] createInitTime() {
		long time = System.currentTimeMillis() ^ 5792386608507341196L;
		byte[] bytes = new byte[8];
		longToBytes(time, bytes, 0);
		return bytes;
	}

	/**
	 * Creates a quasi-unique nonce based on the start time and an incremented
	 * counter
	 * 
	 * @see QueryTool#counter
	 * @see QueryTool#initTime
	 * @see QueryTool#longToBytes(long, byte[], int)
	 * @return a byte array of length 16 containing the nonce
	 */
	private synchronized static byte[] makeNonce() {
		byte[] nonce = new byte[16];
		counter++;
		System.arraycopy(initTime, 0, nonce, 0, 8);
		longToBytes(counter, nonce, 8);
		return nonce;
	}

	
	/* ************* R E Q U E S T   M E T H O D S ************* */
	
	/**
	 * This method scans the command-line arguments for recognizable
	 * flags. Remember that the first command-line argument MUST be
	 * the server URL to be used for making the request. Here is a 
	 * list of the accepted flags:
	 * <ul>
	 * <li><tt>-d</tt> or <tt>-distributor</tt> = Specify a user-defined distributor UUID
	 * to make the request. This flag must be followed by the UUID to use. Remember to use
	 * the correct password!
	 * </li>
	 * <li><tt>-f</tt> or <tt>-file</tt> = Specify that the server response (resourceItemList)
	 * should be written to an XML file (for use with extracting resource UUIDs for DistribTool).
	 * This flag must be followed by the name (or path/name) of the output file to use.
	 * </li>
	 * <li><tt>-p</tt> or<tt>-pass</tt> = Specify that the request HMAC be signed with a user-
	 * defined password. This flag is to be used with a text password (The tool will take the
	 * raw SHA1 hash of the password to use as HMAC Key Bytes). This flag must be followed by
	 * the password text to use.
	 * </li>
	 * <li><tt>-p64</tt> or <tt>-pass64</tt> = Specify that the request HMAC be signed with a
	 * user-defined password. This flag is to be used with a Base64-encoded Shared Secret (The
	 * tool will decode the password string and use the resulting bytes as HMAC Key Bytes). This
	 * flag must be followed by the Base64-encoded password String to use.
	 * </li>
	 * <li><tt>-v</tt> or <tt>-verbose</tt> = Enable verbose display mode. For best functionality,
	 * use this flag FIRST if you are using multiple flags. 
	 * </li>
	 * <li><tt>-rf</tt> or <tt>-resourceFile</tt> = Extract the resource UUIDs from the server
	 * response and output them to a text file (for use as a resourceFile for DistribTool). This
	 * flag must be followed by the name (or path/name) of the output file to use . 
	 * </li>
	 * </ul>
	 */
	private void scanArgsForFlags(String[] args) {
		if(args.length == 0) { 
			System.out.println("No arguments specified");
			System.out.println("Using default server URL: " + SERVER_URL);
		}
		SERVER_URL = args[0];
		System.out.println("First argument will be used as server URL: " + SERVER_URL);
		for(int i = 1; i < args.length; i++) {
			if(args[i].equalsIgnoreCase("-p") || args[i].equalsIgnoreCase("-pass")) {
				try{
					i++;
					HMAC_PASS = args[i];
					if(verboseDisplay)
						System.out.println("Using user-defined password: " + HMAC_PASS);
				} catch(Exception e) {
					System.err.println("-p and -pass flags must be followed by user-defined password!");
					e.printStackTrace();
				}
			}
			else if(args[i].equalsIgnoreCase("-p64") || args[i].equalsIgnoreCase("-pass64")) {
				try{
					i++;
					useBase64Pass = true;
					HMAC_PASS = args[i];
					if(verboseDisplay)
						System.out.println("Using user-defined Base64 encoded shared secret: " + HMAC_PASS);
				} catch(Exception e) {
					System.err.println("-p64 and -pass64 flags must be followed by user-defined Base64 encoded shared secret!");
				}
			}
			else if(args[i].equalsIgnoreCase("-v") || args[i].equalsIgnoreCase("-verbose")) {
				verboseDisplay = true;
				if(verboseDisplay)
					System.out.println("Enabling verbose display mode!");
			}
			else if(args[i].equalsIgnoreCase("-d") || args[i].equalsIgnoreCase("-distributor")) {
				try{
					i++;
					DIST_UUID = args[i];
					if(verboseDisplay)
						System.out.println("Using user-defined distributor UUID: " + DIST_UUID);
				} catch(Exception e) {
					System.err.println("-d and -distributor flags must be followed by the user-defined Distributor UUID!");
					e.printStackTrace();
				}
			}
			else if(args[i].equalsIgnoreCase("-f") || args[i].equalsIgnoreCase("-file")) {
				try{
					outputToFile = true;
					i++;
					outputFileName = args[i];
					if(verboseDisplay)
						System.out.println("Will write server response to file: " + outputFileName);
				} catch(Exception e) {
					System.err.println("-f and -file flags must be followed by the ouptut file name!");
					e.printStackTrace();
				}
			}
			else if(args[i].equalsIgnoreCase("-rf") || args[i].equalsIgnoreCase("-resourceFile")) {
				try{
					outputResourceFile = true;
					i++;
					resourceFileName = args[i];
					if(verboseDisplay)
						System.out.println("Will extract resource UUID list to file: " + resourceFileName);
				} catch(Exception e) {
					System.err.println("-rf and -resourceFile flags must be followed by the output file name!");
					e.printStackTrace();
				}
			}
			else if(args[i].equalsIgnoreCase("-?") || args[i].equalsIgnoreCase("-help")) {
				displayHelp();
			}
		}
	}
	
	/**
	 * Displays the help message to console. Called when the -? or -help flags
	 * are used.
	 */
	private void displayHelp() {
		System.out.println("QueryTool");
		System.out.println("By Piotr Kula");
		System.out.println("****************");
		System.out.println("This tool requires that the first command-line argument be");
		System.out.println("The server URL of the server to query. The server URL must");
		System.out.println("be of the form: http://SERVER_NAME/admin/QueryResourceItems");
		System.out.println("This tool also accepts a number of command line flags: ");
		System.out.println("-d or -distributor = Specify a user-defined distributor UUID\n" +
				"\tto make the request. This flag must be followed by the UUID to use. \n" +
				"\tRemember to use the correct password!");
		System.out.println("-f or -file = Specify that the server response (resourceItemList)\n" +
				"\tshould be written to an XML file (for use with extracting resource UUIDs for DistribTool).\n" +
				"\tThis flag must be followed by the name (or path/name) of the output file to use.");
		System.out.println("-p or -pass = Specify that the request HMAC be signed with a user-\n" +
				"\tdefined password. This flag is to be used with a text password (The tool will take the\n" +
				"\traw SHA1 hash of the password to use as HMAC Key Bytes). This flag must be followed by\n" +
				"\tthe password text to use.");
		System.out.println("-p64 or -pass64 = Specify that the request HMAC be signed with a\n" +
				"\tuser-defined password. This flag is to be used with a Base64-encoded Shared Secret (The\n" +
				"\ttool will decode the password string and use the resulting bytes as HMAC Key Bytes). This\n" +
				"\tflag must be followed by the Base64-encoded password String to use.");
		System.out.println("-v or -verbose = Enable verbose display mode. For best functionality,\n" +
				"\tuse this flag FIRST if you are using multiple flags.");
		System.out.println("-rf or -resourceFile = Extract the resource UUIDs from the server\n" +
				"\tresponse and output them to a text file (for use as a resourceFile for DistribTool). This\n" +
				"\tflag must be followed by the name (or path/name) of the output file to use.");
	}
	
	/**
	 * Retrieves the HMAC secret key from <var>HMAC_PASS</var> and
	 * returns it as a String. For the time being, the key is known to be
	 * "One4_all". If the -p -pass -p64 -pass64 flags are used, HMAC_PASS
	 * is set to be whatever String immediately follows the flag.
	 * 
	 * @see QueryTool#HMAC_PASS
	 * @return String containing HMAC secret key
	 */
	private String getHmacKey() {
		return HMAC_PASS;
	}

	/**
	 * Creates a new element in the source Document in the Adept namespace and
	 * appends it to the <var>parentElement</var>.
	 * 
	 * @param doc
	 *            Source Document in which new element will be created
	 * @param elementName
	 *            Tag name of the new element to be created
	 * @param elementContent
	 *            Text content of the new element to be created
	 * @param parentElement
	 *            Parent element to which the new element will be appended
	 * @see Document#createElementNS(String, String)
	 */
	private void addNewAdeptElement(Document doc, String elementName,
			String elementContent, Element parentElement) {
		Element newElement = doc.createElementNS(XMLUtil.AdeptNS, elementName);
		newElement.setTextContent(elementContent);
		parentElement.appendChild(newElement);
		return;
	}

	/**
	 * Transforms the passed source Document to a string utilizing Transformer
	 * for Documents. This effectively serializes the XML of the request.
	 * 
	 * @param doc
	 *            Source Document to be serialized
	 * @return String containing serialized XML
	 */
	private String transDoc(Document doc) {
		try {
			Transformer trans = TransformerFactory.newInstance()
					.newTransformer();
			trans.setOutputProperty(OutputKeys.INDENT, "yes");
			StreamResult result = new StreamResult(new StringWriter());
			DOMSource source = new DOMSource(doc);
			trans.transform(source, result);
			return result.getWriter().toString();
		} catch (Exception e) {
			e.printStackTrace();
			failed = true;
		}
		return null;
	}

	/**
	 * Creates and configures a connection to the packaging server specified by
	 * <var>targetURL</var>. The connection is configured to POST to the
	 * fulfillment server as well as be used to receive server output. A new
	 * connection must be created for every package request.
	 * 
	 * @param targetURL
	 *            URL of packaging server
	 * @return Properly configured HttpURLConnection to packaging server
	 */
	private HttpURLConnection createConnection(String targetURL) {
		try {
			System.out.println("Creating connection to Server: " + targetURL);
			URL url = new URL(targetURL);
			final HttpURLConnection conn = (HttpURLConnection) url
					.openConnection();
			conn.setRequestMethod("POST");
			conn.setRequestProperty("Content-Type",
					"application/vnd.adobe.adept+xml");
			conn.setDoOutput(true);
			return conn;
		} catch (Exception e) {
			e.printStackTrace();
			failed = true;
		}
		return null;
	}

	/**
	 * <p>
	 * Creates the XML content of the QueryResourceItems request.
	 * </p>
	 * <p>
	 * Specifically, this method creates a new DOM Document and appends elements
	 * to create the package request. First, the <var>requestElement</var> is
	 * created in the Adept namespace. Then, the method calls addNewAdeptElement
	 * four times to create elements and append them to <var>requestElement</var>.
	 * The following four elements are added in order:
	 * </p>
	 * <ul>
	 * <li>&lt;distributor&gt;, containing the distributor ID from
	 * <var>DIST_UUID</var>.</li>
	 * <li>&lt;nonce&gt;, containing the Base64-encoded nonce created with
	 * makeNonce().</li>
	 * <li>&lt;expiration&gt;, containing the expiration of the
	 * QueryResourceItems request. The expiration is set to be the current time +
	 * <var>EXPIRATION_INTERVAL</var> minutes.</li>
	 * <li>&lt;QueryResoureItems&gt;, this empty element notifies the server
	 * that this is a QueryResourceRequest</li>
	 * </ul>
	 * <p>
	 * The <var>requestElement</var> is appended to the Document <var>doc</var>.
	 * The completed <var>requestElement</var> is passed to hmac(), which
	 * creates the HMAC for the request and appends it to the
	 * <var>requestElement</var> in an &lt;hmac&gt; element. The completed
	 * QueryResourceItems request is passed to transDoc to be converted into a
	 * string, which is returned
	 * </p>
	 * <p>
	 * For clarity, the structure of the QueryResourceItems request is shown
	 * below
	 * </p>
	 * 
	 * <pre>
	 * &lt;request xmlns:&quot;http://ns.adobe.com/adept/&quot;&gt;
	 * 	&lt;distributor&gt;DIST_UUID&lt;/distributor&gt;
	 * 	&lt;nonce&gt; Base64(NONCE) &lt;/nonce&gt;
	 * 	&lt;expiration&gt; W3CDTF DATE &lt;/expiration&gt;
	 * 	&lt;QueryResourceItems /&gt;
	 * 	&lt;hmac&gt; Base64(HMAC) &lt;/hmac&gt;
	 * &lt;/request&gt;
	 * </pre>
	 * 
	 * @see XMLUtil#createDocument()
	 * @see XMLUtil#AdeptNS
	 * @see XMLUtil#dateToW3CDTF(Date)
	 * @see XMLUtil#hmac(byte[], Element)
	 * @see QueryTool#addNewAdeptElement(Document, String, String, Element)
	 * @see QueryTool#makeNonce()
	 * @see QueryTool#getHmacKey()
	 * @see QueryTool#transDoc(Document)
	 * @see QueryTool#DIST_UUID
	 * @see QueryTool#EXPIRATION_INTERVAL
	 * @see Base64#encodeBytes(byte[])
	 * @return String containing the XML content of the QueryResourceItems
	 *         request.
	 */
	private String createRequestContent() {
		try {
			// Create empty Document
			Document doc = XMLUtil.createDocument();
			// Create requestElement in Adept namespace
			Element requestElement = doc.createElementNS(XMLUtil.AdeptNS,
					"request");
			// Attach four required elements to requestElement
			addNewAdeptElement(doc, "distributor", DIST_UUID, requestElement);
			addNewAdeptElement(doc, "nonce", Base64.encodeBytes(makeNonce()),
					requestElement);
			addNewAdeptElement(doc, "expiration", XMLUtil
					.dateToW3CDTF(new Date(System.currentTimeMillis()
							+ EXPIRATION_INTERVAL * 60 * 1000)), requestElement);
			addNewAdeptElement(doc, "QueryResourceItems", "", requestElement);
			doc.appendChild(requestElement);

			if( getHmacKey() != null ) {
				// retrieve HMAC password
				byte[] hmacKeyBytes;
				if(useBase64Pass) {
					// if -p64 or -pass64 was used to supply 
					// a base64 encoded shared secret, the
					// hmacKeyBytes are just that base64 String
					// decoded.
					hmacKeyBytes = Base64.decode(getHmacKey());
				}
				else {
					// if -p or -p (or the default built-in pass)
					// is used, the HmacKey is a ASCII string, and
					// the hmacKeyBytes are just the raw SHA1
					// hash of that String
					hmacKeyBytes = XMLUtil.SHA1(getHmacKey());
				}
				// use the resulting bytes to generate HMAC
				XMLUtil.hmac(hmacKeyBytes, requestElement);
			}

			// Create XML string from the Document and return it
			String requestContent = transDoc(doc);
			
			if(verboseDisplay)
				System.out.println("Request Content: \n" + requestContent);
			
			return requestContent;
		} catch (Exception e) {
			e.printStackTrace();
			failed = true;
		}
		return null;
	}

	/**
	 * <p>
	 * Creates an HttpURLConnection to the fulfillment server, sends the server
	 * the XML QueryResourceItems request, and receives the server's response.
	 * Specifically, this method creates an HttpURLConnection to the server with
	 * URL specified in <var>SERVER_URL</var> and sends the XML request
	 * contained in the passed String <var>queryRequest</var>. Next, the method
	 * receives the server's response into a StringBuffer. Finally, the content
	 * of the StringBuffer is converted to String and returned.
	 * </p>
	 * 
	 * @param queryRequest
	 *            Contains the XML QueryResourceItems request
	 * @see QueryTool#createConnection(String)
	 * @see QueryTool#SERVER_URL
	 * @return A string containing the XML of the server's response
	 */
	private String sendRequestToServer(String queryRequest) {
		try {
			// Create connection to server with SERVER_URL
			HttpURLConnection conn = createConnection(SERVER_URL);
			OutputStream out = conn.getOutputStream();

			System.out.println("Sending QueryResourceItem Request");

			// Send XML request to server
			out.write(queryRequest.getBytes("UTF-8"));
			out.close();

			// Make sure connection is still open
			conn.connect();

			// Receive server's response,
			final StringBuffer responseText = new StringBuffer();
			final int code = conn.getResponseCode();
			final String contentType = conn.getContentType();
			InputStreamReader in = new InputStreamReader(conn.getInputStream(),
					"UTF-8");

			char[] msg = new char[2048];
			int len;
			while ((len = in.read(msg)) > 0) {
				responseText.append(msg, 0, len);
			}
			
			/*
			 * Here are some checks to make sure that the server response
			 * is valid. 
			 * 1) If the server response begins with "<error", the server
			 * 		response is an error!
			 * 2) If the server response code is anything but 200 (successful
			 * 		request), the response is an error!
			 * 3) If the server response contentType is anything except
			 * 		"application/vnd.adobe.adept+xml" (the only contentType
			 * 		used by Adobe Content Server 4 communication), the response
			 * 		is an error!
			 */
			if(responseText.substring(1, 6).equals("error")) {
				System.err.println("Server returned an error!");
				failed = true;
			}
			if(code != 200) {
				System.err.println("Server returned unexpected Response Code: " + code);
				failed = true;
			}
			if(!contentType.equals("application/vnd.adobe.adept+xml")) { 
				System.err.println("Server returned unexpected Content Type: " + contentType);
				failed = true;
			}
			
			// return
			return responseText.toString();
			
		} catch (java.net.UnknownHostException e) {
			System.err.println("Server URL could not be resolved");
			System.err.println("Connection to server FAILED");
			failed = true;
		} catch (IOException i) {
			i.printStackTrace();
			failed = true;
		}
		return null;
	}

	/**
	 * Displays the server's XML response.
	 * 
	 * @param serverResponse
	 *            String containing server's XML response
	 */
	private void displayServerResponse(String serverResponse) {
		if(serverResponse == null) {
			System.err.println("There was no server response!");
			System.err.println("Make sure the server URL is correct");
		}
		else if(failed) {
			System.err.println("Your request did not process successfully!");
			System.err.println("Server Response:");
			System.err.println(serverResponse);
			System.err.println("\nQueryResourceItems request was unsuccessful!");
		}
		else {
			System.out.println("Server Response:");
			System.out.println(serverResponse);
			System.out.println("\nQueryResourceItems request successful!");
		}
		return;
	}
	
	/**
	 * This method writes the server response to the file specified with
	 * the <tt>-f</tt> or <tt>-file</tt> flags when the tool is run. The
	 * name of the file to write to is stored globally in the <var>outputFileName</var>
	 * variable. 
	 * 
	 * @see #outputFileName
	 * @param serverResponse String containing server's response
	 */
	private void writeOutputToFile(String serverResponse) {
		if(serverResponse == null) {
			System.err.println("There was no server response!");
			System.err.println("Make sure the server URL is correct");
		}
		else {
			FileOutputStream out;
			PrintStream p;
			
			try {
				out = new FileOutputStream(outputFileName);
				p = new PrintStream(out);
				p.println(serverResponse);
				p.close();
				System.out.println("Successfully wrote output to file: " + outputFileName);
			} catch (Exception e) {
				System.err.println("Error writing to file: " + outputFileName);
				failed = true;
			}
		}
	}
	
	/**
	 * This method extracts the resource UUIDs from the server response and
	 * writes them to the file specified with the <tt>-rf</tt> or <tt>-resourceFile</tt>
	 * flags when the tool is run. The name of the file to write to is stored
	 * globally in the <var>resourceFileName</tt> variable. Duplicate resource
	 * UUIDs will skipped (this is important as the &lt;resource&gt; element 
	 * appears in the resourceItemInfo AND the licenseToken (which is a child
	 * of resourceItemInfo)).
	 * <br><br>
	 * Specifically, this file takes the serverResponse 
	 * 
	 * @see #resourceFileName
	 * @see #extractResourceList(Document)
	 * @param serverResponse
	 */
	private void writeResourceFile(String serverResponse) {
		if(serverResponse == null) {
			System.err.println("There was no server response!");
			System.err.println("Make sure the server URL is correct");
		}
		else {
			FileOutputStream out;
			PrintStream p;
			String previousResource;
			String thisResource;
			
			// Parse the serverResponse into XML and extract resource UUIDs.
			// Write the resource UUIDs to a a file.
			try {
				// Set up output
				out = new FileOutputStream(resourceFileName);
				p = new PrintStream(out);
				previousResource = ("");
				
				if(verboseDisplay)
					System.out.println("\nWriting resource UUIDs to resourceFile!");
				
				// Parse serverResponse into XML
				Document resourceXML = XMLUtil.parseXML(serverResponse);
				// Extract NodeList of <resource> elements
				NodeList resourceList = extractResourceList(resourceXML);
				
				for(int i = 0; i < resourceList.getLength(); i++) {
					thisResource = ( (Element) resourceList.item(i) ).getTextContent();
					if(thisResource == null) {
						System.err.println("Found empty resourceList item! Skipping!");
					}
					else if(thisResource.equalsIgnoreCase(previousResource)) {
						if(verboseDisplay)
							System.out.println("Found repeat resource UUID! Skipping!");
					}
					else {
						if(verboseDisplay)
							System.out.println("Writing UUID: " + thisResource);
						p.println(thisResource);
						previousResource = thisResource;
					}
				}
				p.close();
				System.out.println("Successfully wrote to resourceFile: " + resourceFileName);
			} catch (Exception e) {
				System.err.println("Error writing to resourceFile: " + resourceFileName);
				failed = true;
			}
		}
	}
		
	/**
	 * Extracts a NodeList of &lt;resource&gt; elements from the source Document
	 * that is passed to it. If there are no resource elements in <var>resourceXML</var>
	 * the method will return null.
	 * 
	 * @param resourceXML source Document for extracting resource elements
	 * @return NodeList containing resource elements or null if there weren't any
	 */
	private NodeList extractResourceList(Document resourceXML) {
		NodeList list = resourceXML.getElementsByTagNameNS(XMLUtil.AdeptNS, "resource");
		if(list.getLength() == 0) {
			System.err.println("Did not find any <resource> elements in XML response!");
			return null;
		}
		if(verboseDisplay)
			System.out.println("Found " + list.getLength() + " <resource> elements");
		return list;
	}
	

	/* ******** C O N S T R U C T O R   A N D   M A I N ******** */

	/**
	 * <p>
	 * QueryTool Constructor.
	 * </p>
	 * <p>
	 * Calls createRequestContent() to create the XML QueryResourceItems
	 * request, then passes the resulting string to
	 * sendRequestToServer(queryRequest) in order to send the XML request to the
	 * server. Finally, it passes the server's response to
	 * displayServerResponse(serverResponse) to display the server's response.
	 * </p>
	 * 
	 * @see QueryTool#createRequestContent()
	 * @see QueryTool#sendRequestToServer(String)
	 * @see QueryTool#displayServerResponse(String)
	 */
	public QueryTool(String[] args) {
		//checkForServerURL(args);
		scanArgsForFlags(args);
		String queryRequest = createRequestContent();
		String serverResponse = sendRequestToServer(queryRequest);
		displayServerResponse(serverResponse);
		if(outputToFile)
			writeOutputToFile(serverResponse);
		if(outputResourceFile)
			writeResourceFile(serverResponse);
	}

	/**
	 * <p>
	 * Main.
	 * </p>
	 * <p>
	 * Calls the QueryTool Constructor
	 * </p>
	 * 
	 * @param args
	 *            String[] containing all arguments passed to QueryTool (not
	 *            used)
	 */
	public static void main(String[] args) {
		new QueryTool(args);
		return;
	}
}