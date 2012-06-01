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

import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.io.IOException;
import java.io.InputStreamReader;
import java.io.OutputStream;
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
 * The DistribTool sends a ManageDistributionRights request to the admin 
 * service in order to assign books to a distributor. It does so by creating
 * an XML-based request that is detailed in-depth below.
 * 
 * In order to run this tool, you must call it with the path to the XML 
 * configuration file as the first command-line argument. Following this
 * first argument, you can use any of the recognizable flags. Here is
 * the list of recognizable flags:
 * <pre>
 * -verbose = displays content of package request and detailed server response
 * -help -? = displays the help message
 * -p -pass = the next command-line argument is used as the user-defined HMAC password
 * </pre>
 * 
 * There are two external resources that are required for this tool to run. 
 * The first is the XML Configuration file,  whose path is specified as the
 * first command line argument to running this tool. The structure of this
 * configuration file is detailed below, and a sample is also shown. All
 * of the elements of the XML Configuration except those labeled optional
 * are required.
 * Here is a description of the elements:
 * <br><br>
 * <ul>
 * <li><tt>request</tt> = Root element of the request. Includes the Adept namespace
 * (<tt>xmlns="http://ns.adobe.com/adept"</tt>) as the default namespace.
 * </li>
 * <li><tt>serverURL</tt> = Contains the full URL to the admin service. The
 * server URL must point to the ManageDistributionRights API. 
 * (<tt>http://##YOUR_SERVER##/admin/ManageDistributionRights</tt>)
 * </li>
 * <li><tt>distributionRights</tt> = Empty element that contains child elements
 * <tt>distributor</tt>, <tt>resourceFile</tt>, and <tt>distributionType</tt>.
 * The XML Configuration file must contain one or more distributionRights elements**
 * </li>
 * <li><tt>distributor</tt> = Contains the UUID of the distributor to whom the resources
 * specified by <tt>resourceFile</tt> will be assigned.
 * </li>
 * <li><tt>resourceFile</tt> = Contains the path to the resourceFile, which holds
 * the UUIDs of the resources being assigned to the distributor 
 * (more on the resourceFile below)
 * </li>
 * <li><tt>distributionType</tt> = Contains the type of distribution that is being
 * assigned for the resources for this distributor. The only two values allowed
 * are <tt>buy</tt> and <tt>loan</tt>
 * </li>
 * <li><tt>available</tt> = OPTIONAL Contains the number of simultaneous loans that this 
 * distributor has the rights to loan out. If returnable = false, this element
 * has no meaning. The content of the available element must be an int
 * </li>
 * <li><tt>returnable</tt> = OPTIONAL Specifies whether the book in the request is returnable.
 * This value should be false for buys and true for loans. The content of the
 * returnable element must be true or false (currently, all lower-case as the API
 * is case-sensitive).
 * </li>
 * <li><tt>userType</tt> = OPTIONAL user|"passhash
 * </li>
 * <li><tt>permissions</tt> = OPTIONAL Element that contains the permissions grammar
 * for this book.
 * </li>
 * </ul><br>
 * Here is a sample XML Configuration file test-configuration.xml:
 * <pre>
 * &lt;?xml version="1.0" encoding="UTF-8"?&gt;
 * &lt;request xmlns="http://ns.adobe.com/adept"&gt;
 * 	&lt;serverURL&gt;http://your.server.com/admin/ManageDistributionRights&lt;/serverURL&gt;
 * 	&lt;distributionRights&gt;
 * 		&lt;distributor&gt;urn:uuid:88037e33-0e4d-4180-80ce-6c1d5ddf9cc9&lt;/distributor&gt;
 * 		&lt;resourceFile&gt;/Users/You/Documents/DistribTool/test-resource-list.txt&lt;/resourceFile&gt;
 * 		&lt;distributionType&gt;loan&lt;/distributionType&gt;
 * 		&lt;available&gt;2&lt;/available&gt;
 * 		&lt;returnable&gt;false&lt;/returnable&gt;
 * 		&lt;userType&gt;passhash&lt;/userType&gt;
 * 		&lt;permissions&gt;
 * 			&lt;display /&gt;
 * 			&lt;play /&gt;
 * 			&lt;excerpt /&gt;
 * 			&lt;print /&gt;
 * 		&lt;/permissions&gt;
 * 	&lt;/distributionRights&gt;
 * &lt;/request&gt;
 * </pre>
 * 
 * ** The XML Configuration file must contain one ore more complete distributionRights
 * elements. The distributionRights elements may contain different or same distributor
 * UUIDs, resourceFiles, and distributionTypes. This is useful if you want to use a
 * single request to assign "buy" books and "loan" books to the same distributor, or
 * if you want to assign one ore more sets of books to multiple distributors. If a
 * distributionRights element does not contain all of its children elements, it will
 * be skipped (but other distributionRights elements that have all of the child elements
 * present will be processed). 
 * <br><br>
 * The second external resource that is required for this tool to run is the 
 * resourceFile. The path to the resourceFile is specified in the <tt>resourceFile</tt> 
 * (relative path may be used, but remember that the path is relative to the tool's call directory, 
 * and not necessarily the XML Configuration file's directory). The structure
 * of the resourceFile is simple. It is a text file that has UUIDs of resources
 * that will be assigned to the distributor, separated by line breaks. There may only
 * be one UUID per line. As only one resource UUID is allowed per request, this tool 
 * will send one request per UUID, meaning that if one request fails, the rest will 
 * continue to be processed. A sample is shown below:
 * <br><br>
 * Here is a sample text resourceFile rest-resource-list.txt:
 * <pre>
 * urn:uuid:110faa58-c8eb-4be0-b05a-ca31908d499a
 * urn:uuid:dce65269-621e-40ec-8b70-9f73b4557b75
 * urn:uuid:7065168f-a6aa-4869-b4ef-58278b10f8d4
 * </pre>
 * The UUIDs used in the file must correspond to books in the inventory on your
 * admin server. Be sure you have permission to assign them to distributors.
 * <br><br>
 * For the sake of clarity, described below is the structure of the request formed
 * by this tool and how it is generated. Here is a description of elements:
 * <br><br>
 * <ul>
 * <li><tt>request</tt> = Root element of the request. Includes the Adept namespace
 * (<tt>xmlns="http://ns.adobe.com/adept"</tt>) as the default namespace. The <tt>action</tt>
 * attribute is set to "create", signifying that a new assignment is being made. The <tt>auth</tt>
 * attribute is set to "builtin", signifying that the built-in distributor should be the one that
 * assigns the resources to the distributor with UUID provided in the <tt>distributor</tt> element.
 * </li>
 * <li><tt>distributionRights</tt> = Empty element that contains child elements
 * <tt>distributor</tt>, <tt>resourceFile</tt>, and <tt>distributionType</tt>.
 * </li>
 * <li><tt>distributor</tt> = Contains the UUID of the distributor to whom the resource
 * specified by <tt>resource</tt> will be assigned. 
 * </li>
 * <li><tt>resource</tt> = Contains the UUID of the resource that will be assigned to 
 * the distributor. Only one resource may be assigned per request. 
 * </li>
 * <li><tt>distributionType</tt> = Contains the type of distribution that is being
 * assigned for the resource for this distributor. The only two values allowed
 * are <tt>buy</tt> and <tt>loan</tt>
 * </li>
 * <li><tt>available</tt> = OPTIONAL Contains the number of simultaneous loans that this 
 * distributor has the rights to loan out. If returnable = false, this element
 * has no meaning. The content of the available element must be an int
 * </li>
 * <li><tt>returnable</tt> = OPTIONAL Specifies whether the book in the request is returnable.
 * This value should be false for buys and true for loans. The content of the
 * returnable element must be true or false (currently, all lower-case as the API
 * is case-sensitive).
 * </li>
 * <li><tt>userType</tt> = OPTIONAL user|passhash
 * </li>
 * <li><tt>permissions</tt> = OPTIONAL Element that contains the permissions grammar
 * for this book.
 * </li>
 * <li><tt>nonce</tt> = Contains Base64-encoded Nonce, which is a quasi-unique
 * transaction identifier that is created from the system time at the time that 
 * this tool is called and an incremented counter. 
 * </li>
 * <li><tt>expiration</tt> = Contains the W3CDTF date and time of the expiration of
 * this request. The expiration is set to be <var>EXPIRATION_INTERVAL</var> minutes
 * from the current system time. 
 * </li>
 * <li><tt>hmac</tt> = Contains the Base64-encoded HMAC. This is a security feature
 * for signing the request. To learn more about the HMAC and how it is generated, 
 * see the class description for XMLUtil Class.
 * </li>
 * </ul>
 * <br><br>
 * Here is a sample request that is generated by this tool:
 * <pre>
 * &lt;?xml version="1.0" encoding="UTF-8"?&gt;
 * &lt;request action="create" auth="builtin" xmlns="http://ns.adobe.com/adept"&gt;
 * 	&lt;distributionRights&gt;
 * 		&lt;distributor&gt;urn:uuid:88037e33-0e4d-4180-80ce-6c1d5ddf9cc9&lt;/distributor&gt;
 * 		&lt;resource&gt;urn:uuid:110faa58-c8eb-4be0-b05a-ca31908d499a&lt;/resource&gt;
 * 		&lt;distributionType&gt;loan&lt;/distributionType&gt;
 * 		&lt;available&gt;2&lt;/available&gt;
 * 		&lt;returnable&gt;false&lt;/returnable&gt;
 * 		&lt;userType&gt;passhash&lt;/userType&gt;
 * 		&lt;permissions&gt;
 * 			&lt;display /&gt;
 * 			&lt;play /&gt;
 * 			&lt;excerpt /&gt;
 * 			&lt;print /&gt;
 * 		&lt;/permissions&gt;
 * 	&lt;/distributionRights&gt;
 * 	&lt;nonce&gt;UGKx9TYlIsRoBBASTevnXA==&lt;/nonce&gt;
 * 	&lt;expiration&gt;2008-07-29T10:57:58-07:00&lt;/expiration&gt;
 * 	&lt;hmac&gt;I+3eqYQX4cbrB5q83A0CEuL0pLM=&lt;/hmac&gt;
 * &lt;/request&gt;
 * </pre>
 * 
 * @author piotrk
 *
 */
public class DistribTool {
	
	/* ************** P U B L I C   F I E L D S ************** */
	
	/** Length of time before request expiration in minutes (from current time) */
	public final int EXPIRATION_INTERVAL = 15;
	
	/* *********** G L O B A L   V A R I A B L E S *********** */
	
	/** Toggles detailed output to console */
	private boolean verboseDisplay = false;
	
	/** Holds the URL for the server that the requests will be sent to */
	private String serverURL = new String("");
	
	/** Counter used to keep track of the number of attempted requests sent to
	 * the server
	 */
	private int attempted = 0;
	/**
	 * Counter used to keep track of the number of error responses received from
	 * server
	 */
	private int errors = 0;
	/**
	 * Counter used to keep track of the number of successful DistributionRights
	 * responses received from server
	 */
	private int successes = 0;
	/**
	 * Counter used to keep track of the number of distributionRights elements
	 * skipped due to improper syntax.
	 */
	private int skipped = 0;
	
	/** Holds the list of errorInfo entries for unsuccessful requests */
	private String errorList = new String();
	/** Holds the error info for the request that is being currently processed.
	 * Error info includes the distributor UUID, resource UUID, and 
	 * distributionType.
	 */
	private String errorInfo = new String();
	
	/** SharedSecret HMAC Password used for signing the request with HMAC */
	private String password = null;
	
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
	 * @see DistribTool#longToBytes(long, byte[], int)
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
	 * @see DistribTool#counter
	 * @see DistribTool#initTime
	 * @see DistribTool#longToBytes(long, byte[], int)
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
	 * Retrieves the HMAC secret key from <var>HMAC_PASS</var> and
	 * returns it as a String. For the time being, the key is known to be
	 * "One4_all".
	 * 
	 * @see DistribTool#password
	 * @return String containing HMAC secret key
	 */
	private String getHmacKey() {
		return password;
	}
	
	/**
	 * For every argument in the passed array, checks to see if it is a 
	 * recognizable command-line flag and does the appropriate 
	 * corresponding action. 
	 * <br>
	 * The following flags are accepted:
	 * <pre>
	 * -verbose = displays content of package request and detailed server response
	 * -help -? = displays the help message
	 * -p -pass = the next command-line argument is used as the user-defined HMAC password
	 * </pre>
	 * @param args Array of arguments passed to main
	 * @see DistribTool#verboseDisplay
	 * @see DistribTool#displayHelp()
	 */
	private void scanArgsForFlags(String[] args) {
		String singleArg = new String("");
		for (int i = 0; i < args.length; i++) {
			singleArg = args[i].toLowerCase();
			if (singleArg.equals("-verbose"))
				verboseDisplay = true;
			else if(singleArg.equals("-help") || singleArg.equals("-?"))
				displayHelp();
			else if(singleArg.equals("-p") || singleArg.equals("-pass")) {
				try {
					i++;
					password = args[i];
					if(verboseDisplay)
						System.out.println("Using user-defined password: " + password);
				} catch(Exception e) {
					System.err.println("-p and -pass flags must be followed by user-defined password!");
					e.printStackTrace();
				}
			}
		}
	}
	
	/**
	 * This method displays the help message. The help message can be
	 * called by using -? or -help as command-line flags.
	 */
	private void displayHelp() {
		System.out.println("DistributorRights Book Assigning Tool.");
		System.out.println("The first parameter MUST be the path to the XML configuration file");
		System.out.println("\nList of accepted command-line flags:");
		System.out.println("-verbose = displays content of requests and detailed server response");
		System.out.println("-help or -? = displays this help message");
		System.out.println("-p or -pass = the next command-line argument is used as the user-defined HMAC password");
	}
	
	/**
	 * This method extracts the text content of a single child element of the
	 * passed element <var>sourceElement</var> with the name <var>elementName</var>
	 * and the namespace <var>elementNS</var>. There must be exactly one element
	 * of the given name and namespace as a child of the <var>sourceElement</var>. If
	 * none or more than one element match the name and namespace, this method will
	 * return null and print the appropriate error to console. 
	 * 
	 * @param sourceElement parent element that has child element from which text will be extracted
	 * @param elementName name of the element whose text will be extracted
	 * @param elementNS namespace of the element whose text will be extracted
	 * @param required boolean that is true if the element is required (and will produce error if not found)
	 * @see Element#getElementsByTagNameNS(String, String)
	 * @return Text content of the element <var>elementName</var> or null if there isn't
	 * exactly one element matching the name and namespace specified. 
	 */
	private String extractSingleString(Element sourceElement, String elementName, String elementNS, boolean required) {
		NodeList list = sourceElement.getElementsByTagNameNS(elementNS, elementName);
		if (list == null || list.getLength() == 0) {
			if(required)
				System.err.println("No " + elementName + " element found!");
			return null;
		}
		else if (list.getLength() > 1) {
			if (required)
				System.err.println("Multiple " + elementName + " elements found!");
			return null;
		}
		Element element = (Element) list.item(0);
		return element.getTextContent();
	}
	
	/**
	 * This method extracts the server URL from the configuration file and sets
	 * the global variable <var>serverURL</var> with the value. The config file
	 * is passed to this method as a DOM Document <var>parsedConfig</var>. The
	 * config file must contain exactly one &lt;serverURL&gt; element as a child
	 * of the &lt;request&gt; element. 
	 * 
	 * @param parsedConfig DOM Document containing XML config file
	 * @see XMLUtil#extractAdeptElementText(Document, String)
	 * @see DistribTool#serverURL
	 */
	private void extractServerURL(Document parsedConfig) {
		String tempServerURL = XMLUtil.extractAdeptElementText(parsedConfig, "serverURL");
		if(tempServerURL == null) {
			System.err.println("Could not extract serverURL from Config File");
			System.err.println("Tool will EXIT");
			System.exit(1);
		}
		else {
			if(verboseDisplay) {
				System.out.println("Extracted ServerURL from Config File:");
				System.out.println(tempServerURL);
			}
			serverURL = tempServerURL;
		}
	}
	
	/**
	 * This method extracts and returns a NodeList of the distributionRights elements
	 * contained in the XML config file (the config file must contain one or more of 
	 * these).
	 * 
	 * @param parsedConfig DOM Document containing XML config file
	 * @see Element#getElementsByTagNameNS(String, String)
	 * @return NodeList containing all of the distributionRights elements in the XML config file
	 */
	private NodeList extractDistRightsList(Document parsedConfig) {
		NodeList list = parsedConfig.getElementsByTagNameNS(XMLUtil.AdeptNS, "distributionRights");
		if(verboseDisplay)
			System.out.println("Found " + list.getLength() + " distributionRights elements");
		
		if(list.getLength() == 0) {
			System.err.println("Did not find any distributionRights elements in XML Config File!");
			System.err.println("Tool will EXIT");
			System.exit(1);
		}
		
		return list;
	}
	
	private boolean checkForPermissions(Element distRightsElement) {
		NodeList list = distRightsElement.getElementsByTagNameNS(XMLUtil.AdeptNS, "permissions");
		if(list.getLength() == 0) {
			if(verboseDisplay)
				System.out.println("Could not extract optional element \"permissions\"!");
			return false;
		}
		else if(list.getLength() == 1) {
			return true;
		}
		else {
			System.err.println("Found " + list.getLength() + " \"permissions\" elements!");
			System.err.println("Only one \"permissions\" element is allowed for each distributionRights");
			return false;
		}
	}
	
	/**
	 * Extracts the content of the returnable element from the distributionRights element
	 * passed to it. Uses the method extractSignleString to extract the text contents of 
	 * the returnable element and returns the contents, or null if there was not exactly one
	 * returnable element or the contents is not "true" or "false"
	 * 
	 * @param distRightsElement distributionRights element from which available will be extracted
	 * @see DistribTool#extractSingleString(Element, String, String)
	 * @return String containing the contents of available element extracted from <var>distRightsElement</var>
	 * passed to this method
	 */
	private String extractReturnable(Element distRightsElement) {
		String returnable = extractSingleString(distRightsElement, "returnable", XMLUtil.AdeptNS, false);
		if (returnable == null) {
			if (verboseDisplay)
				System.out.println("Could not extract optional element \"returnable\"!");
			return null;
		}
		else if (returnable.equalsIgnoreCase("true") || returnable.equalsIgnoreCase("false")) {
			return returnable;
		}
		else {
			System.err.println("returnable \"" + returnable + "\" is not allowed!");
			System.err.println("returnable must either be \"true\" or \"false\"");
			return null;
		}
			
	}
	
	/**
	 * Extracts the content of the userType element from the distributionRights element
	 * passed to it. Uses the method extractSignleString to extract the text contents of 
	 * the userType element and returns the contents, or null if there was not exactly one
	 * userType element or the contents is not "true" or "false"
	 * 
	 * @param distRightsElement distributionRights element from which available will be extracted
	 * @see DistribTool#extractSingleString(Element, String, String)
	 * @return String containing the contents of userType element extracted from <var>distRightsElement</var>
	 * passed to this method
	 */
	private String extractUserType(Element distRightsElement) {
		String userType = extractSingleString(distRightsElement, "userType", XMLUtil.AdeptNS, false);
		if (userType == null) {
			if (verboseDisplay)
				System.out.println("Could not extract optional element \"userType\"!");
			return null;
		}
		else if (userType.equalsIgnoreCase("user") || userType.equalsIgnoreCase("passhash")) {
			return userType;
		}
		else {
			System.err.println("userType \"" + userType + "\" is not allowed!");
			System.err.println("userType must either be \"user\" or \"passhash\"");
			return null;
		}
			
	}
	
	private String extractAvailable(Element distRightsElement) {
		String available = extractSingleString(distRightsElement, "available", XMLUtil.AdeptNS, false);
		if (available == null) {
			if (verboseDisplay)
				System.out.println("Could not extract optional element \"available\"!");
			return null;
		}
		else {
			try {
				Integer.parseInt(available);
				return available;
			} catch (NumberFormatException e) {
				System.err.println("available \"" + available  + "\" is not allowed!");
				System.err.println("availalbe must be an int value!");
				return null;
			}
		}
	}
	
	/**
	 * Extracts the distributor UUID from the distributionRights element passed to it.
	 * Uses the method extractSingleString to extract the text contents of the 
	 * distributor element and returns the contents, or null if there was not exactly
	 * one distributor element.
	 * 
	 * @param distRightsElement distributionRights element from which distributor UUID will be extracted
	 * @see DistribTool#extractSingleString(Element, String, String)
	 * @return String containing distributor UUID extracted from <var>distRightsElement</var> passed to it
	 */
	private String extractDistUUID(Element distRightsElement) {
		String distUUID = extractSingleString(distRightsElement, "distributor", XMLUtil.AdeptNS, true);
		if (distUUID == null) {
			System.err.println("Could not extract Distributor UUID!");
			return null;
		}
		return distUUID;
	}
	
	/**
	 * Extracts the distributionType from the distributionRights element passed to it.
	 * Uses the method extractSingleString to extract the text contents of the 
	 * distributor element and returns the contents, or null if there was not exactly
	 * one distributionType element or the distributionType is not "buy" or "loan"
	 * 
	 * @param distRightsElement distributionRights element from which distributionType will be extracted
	 * @see DistribTool#extractSingleString(Element, String, String)
	 * @return String containing distributionType extracted from <var>distRightsElement</var> passed to it
	 */
	private String extractDistType(Element distRightsElement) {
		String distType = extractSingleString(distRightsElement, "distributionType", XMLUtil.AdeptNS, true);
		if (distType == null) {
			System.err.println("Could not extract distributionType!");
			return null;
		}
		else if(distType.equalsIgnoreCase("buy") || distType.equalsIgnoreCase("loan"))
			return distType;
		else {
			System.err.println("distributionType \"" + distType + "\" is not allowed!");
			System.err.println("distributionType must be either \"buy\" or \"loan\"!");
			return null;
		}
	}
	
	/**
	 * Extracts the resourceFile from the distributionRights element passed to it.
	 * Uses the method extractSingleString to extract the text contents of the 
	 * resourceFile element and returns the contents, or null if there was not exactly
	 * one resourceFile element.
	 * 
	 * @param distRightsElement distributionRights element from which resourceFile will be extracted
	 * @see DistribTool#extractSingleString(Element, String, String)
	 * @return String containing resourceFile extracted from <var>distRightsElement</var> passed to it
	 */
	private String extractResourceFile(Element distRightsElement) {
		String resourceFile = extractSingleString(distRightsElement, "resourceFile", XMLUtil.AdeptNS, true);
		if (resourceFile == null) {
			System.err.println("Could not extract resourceFile!");
			return null;
		}
		return resourceFile;
	}
	
	/**
	 * Processes the XML configuration file to run the tool. Specifically,
	 * this method reads the contents of the XML file passed to it into a DOM
	 * Document structure. Next, it extracts the server URL and Node List containing
	 * all of the &lt;distributionRights&gt; elements in the config file. For
	 * every element in the Node List, it calls processDistRightsElement. When
	 * all of the distributionRights elements have been processed, it calls
	 * displaySummary to display summary of the requests.
	 * 
	 * @param configFileName String containing the file name and path of the XML configuration file
	 * @see DistribTool#verboseDisplay
	 * @see XMLUtil#parseXML(String)
	 * @see DistribTool#transDoc(Document)
	 * @see DistribTool#extractServerURL(Document)
	 * @see DistribTool#extractDistRightsList(Document)
	 * @see DistribTool#processDistRightsElement(Element)
	 * @see DistribTool#displaySummary()
	 */
	private void processConfigFile(String configFileName) {
		// Set up File
		File configFile = new File(configFileName);
		
		if(configFile.exists()) {
			if (verboseDisplay)
				System.out.println("Found XML Config File: " + configFile.getAbsolutePath());
			
			try {
				// create the DOM Document
				Document parsedConfig = XMLUtil.parseXML(new FileReader(configFile));
				if (verboseDisplay) {
					System.out.println("Parsed Config File:");
					System.out.println(transDoc(parsedConfig));
				}
				//extract Server URL
				extractServerURL(parsedConfig);
				//extract NodeList
				NodeList distRightsList = extractDistRightsList(parsedConfig);
				//for every element in NodeList, call processDistRightsElement()
				for (int i = 0; i < distRightsList.getLength(); i++) {
					if(verboseDisplay)
						System.out.println("\n\nProcessing distributionRights element: " + (i+1));
					processDistRightsElement((Element) distRightsList.item(i));
				}
			} catch (Exception e) {
				e.printStackTrace();
			}
		}
		else {
			System.err.println("Could not find XML Config File: " + configFile.getAbsolutePath());
		}
		//when all distributionRights elements are processed, call displaySummary
		displaySummary();
	}
	
	/**
	 * Processes the distributionRights element passed to it for generation of requests. 
	 * Specifically, extracts the distributor UUID, distributionType, and resourceFile name. 
	 * If any of these is null, the method issues a warning and returns without making any
	 * requests (and increments skipped). If all oare valid, then the method proceeds to 
	 * read the resourceFile and calls buildAndSendDistRequest for each UUID contained 
	 * in the resourceFile to generate and send a request. 
	 * 
	 * @param distRightsElement distributionRights element to be processed
	 * @see DistribTool#extractDistUUID(Element)
	 * @see DistribTool#extractDistType(Element)
	 * @see DistribTool#extractResourceFile(Element)
	 * @see DistribTool#verboseDisplay
	 * @see DistribTool#skipped
	 * @see DistribTool#errorInfo
	 * @see DistribTool#buildAndSendDistRequest(String, String, String)
	 */
	private void processDistRightsElement(Element distRightsElement) {
		//extract meta information from distRightsElement
		String distributorUUID = extractDistUUID(distRightsElement);
		String distributionType = extractDistType(distRightsElement);
		String resourceFileName = extractResourceFile(distRightsElement);
		String available = extractAvailable(distRightsElement);
		String returnable = extractReturnable(distRightsElement);
		String userType = extractUserType(distRightsElement);
		boolean hasPermissions = checkForPermissions(distRightsElement);
		System.out.println("Processing distributionRights element for distributor: " + distributorUUID);
		
		//if verboseDisplay is on, display extracted information
		if (verboseDisplay) {
			System.out.println("\nSummary of meta information:");
			if (distributorUUID == null)
				System.out.println("Error processing distributor UUID");
			else
				System.out.println("distributor UUID: " + distributorUUID);
			if (distributionType == null)
				System.out.println("Error processing distribution type");
			else 
				System.out.println("distirbutionType: " + distributionType);
			if (resourceFileName == null)
				System.out.println("Error processing resource file");
			else
				System.out.println("resource file: " + resourceFileName);
			if (available == null)
				System.out.println("Error processing optional element \"available\"");
			else
				System.out.println("available: " + available);
			if (returnable == null)
				System.out.println("Error processing optional element \"returnable\"");
			else
				System.out.println("returnable: " + returnable);
			if (userType == null)
				System.out.println("Error processing optional element \"userType\"");
			else
				System.out.println("userType: " + userType);
			if (!hasPermissions)
				System.out.println("Error processing optional permissions element");
			else
				System.out.println("Optional permissions element will be used");
			System.out.println("End of meta information summary!\n");
		}
		
		//if any of the meta information is null, the syntax of the config file
		//is incorrect, and this entire distributionRights element will be skipped
		if (distributorUUID == null || distributionType == null || resourceFileName == null) {
			System.err.println("Skipping this distributionRights element due to improper structure");
			skipped++;	//skipping this distributionRights element
			return;
		}
		
		//prepare for reading resourceFile
		File resourceFile = new File(resourceFileName);
		String currentUUID = new String("");
		
		if (resourceFile.exists()) {
			if(verboseDisplay)
				System.out.println("Found Resource File: " + resourceFile.getAbsolutePath());
			
			try {
				//read resourceFile, line-by-line
				BufferedReader inFile = new BufferedReader(new FileReader(resourceFile));
				currentUUID = inFile.readLine();
				while (currentUUID != null) {
					attempted++;	//counts the number of attempted requests (number of UUID processed)
					//holds information about the current request in order to be able to log it if error occurs
					errorInfo = "distributor: " + distributorUUID + "\nresource: " + currentUUID + "\ndistributionType: " + distributionType + "\n\n";
					//calls buildAndSendDistRequest to build and send the distributionRights request
					buildAndSendDistRequest(distributorUUID, distributionType, currentUUID, available, returnable, userType, hasPermissions, distRightsElement);
					//reads next UUID from the list
					currentUUID = inFile.readLine();
				}
				inFile.close();
			} catch (Exception e) {
				e.printStackTrace();
			}
		}
		else {
			// if the resource file does not exist, skip this distributionRights element
			System.err.println("Could not open resource file: " + resourceFile.getAbsolutePath());
			System.err.println("Skipping this distributionRights element!");
			skipped++; //skipping this distributionRights element
			return;
		}
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
		}
		return null;
	}
	
	/**
	 * Creates and configures a connection to the packaging server specified by
	 * <var>targetURL</var>. The connection is configured to POST to the
	 * admin server as well as be used to receive server output. A new
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
		}
		return null;
	}
	
	/**
	 * Calls methods to create the XML-based request, establish an HttpURLConnection
	 * to the server, and send the request. The method sendDistRequest also receives
	 * the server's response.
	 *  
	 * @param distributorUUID String containing distributor UUID
	 * @param distributionType String containing the distributionType
	 * @param resourceUUID String containing the resource UUID
	 * @see DistribTool#buildDistRequest(String, String, String)
	 * @see DistribTool#createConnection(String)
	 * @see DistribTool#sendDistRequest(String, HttpURLConnection)
	 */
	private void buildAndSendDistRequest(String distributorUUID, String distributionType, String resourceUUID, String available, 
				String returnable, String userType, boolean hasPermissions, Element distRightsElement) {
		String request = buildDistRequest(distributorUUID, distributionType, resourceUUID, available, 
											returnable, userType, hasPermissions, distRightsElement);
		HttpURLConnection conn = createConnection(serverURL);
		sendDistRequest(request, conn);
	}
	
	/**
	 * Generates the XML-based request to be sent to the server. Specifically, 
	 * creates a DOM Document with the elements described in the Class description
	 * for DistribTool. This method also makes calls to create an expiration, nonce, 
	 * and hmac signature of the request. All elements are in the Adept namespace.
	 * 
	 * @param distributorUUID
	 * @param distributionType
	 * @param resourceUUID
	 * @see DistribTool
	 * @see XMLUtil#createDocument()
	 * @see DistribTool#addNewAdeptElement(Document, String, String, Element)
	 * @see Base64#encodeBytes(byte[])
	 * @see DistribTool#makeNonce()
	 * @see XMLUtil#dateToW3CDTF(Date)
	 * @see DistribTool#getHmacKey()
	 * @see XMLUtil#hmac(byte[], Element)
	 * @see DistribTool#transDoc(Document)
	 * @return
	 */
	private String buildDistRequest(String distributorUUID, String distributionType, String resourceUUID, String available, 
									String returnable, String userType, boolean hasPermissions, Element permissionsSource) {
		try {
			System.out.println("Generating request XML for resource: " + resourceUUID);
			//Set up blank DOM Document
			Document doc = XMLUtil.createDocument();
			
			//Creates the request root element and sets the attributes appropriately
			Element requestElement = doc.createElementNS(XMLUtil.AdeptNS, "request");
			requestElement.setAttributeNS(null, "action", "create");
			requestElement.setAttributeNS(null, "auth", "builtin");
			
			//Creates the distributionRights element and appends the appropriate children
			Element distRightsElement = doc.createElementNS(XMLUtil.AdeptNS, "distributionRights");
			addNewAdeptElement(doc, "distributor", distributorUUID, distRightsElement);
			addNewAdeptElement(doc, "resource", resourceUUID, distRightsElement);
			addNewAdeptElement(doc, "distributionType", distributionType, distRightsElement);
			if(available != null)
				addNewAdeptElement(doc, "available", available, distRightsElement);
			if(returnable != null)
				addNewAdeptElement(doc, "returnable", returnable, distRightsElement);
			if(userType != null)
				addNewAdeptElement(doc, "userType", userType, distRightsElement);
			if(hasPermissions) {
				distRightsElement.appendChild( (Element) doc.importNode(
						XMLUtil.extractElement(permissionsSource, XMLUtil.AdeptNS, "permissions"), true));
			}
			//Appends the distributionRights element to the requestElement
			requestElement.appendChild(distRightsElement);
			
			//Creates and appends the nonce
			addNewAdeptElement(doc, "nonce", Base64.encodeBytes(makeNonce()), requestElement);
			
			//Creates and appends the expiration
			addNewAdeptElement(doc, "expiration", XMLUtil.dateToW3CDTF(new Date(System.currentTimeMillis() + EXPIRATION_INTERVAL * 60 * 1000)), requestElement);
			
			//Appends the requestElement to the DOM Document
			doc.appendChild(requestElement);
			
			if( getHmacKey() != null ) {
				// retrieve HMAC key and run a raw SHA1 HASH on it.
				byte[] hmacKeyBytesSHA1 = XMLUtil.SHA1(getHmacKey());
				// use the bytes of the resulting array to generate HMAC
				XMLUtil.hmac(hmacKeyBytesSHA1, requestElement);
			}
			
			//Transforms the completed request to a String so that it may be returned
			String requestContent = transDoc(doc);
			
			if(verboseDisplay)
				System.out.println("Content of request: \n" + requestContent);
			return requestContent;
		} catch (Exception e) {
			//if an exception occurs, increment the errors counter
			//and append the errorInfo for the current resource to the errorList
			errors++;
			errorList = errorList.concat(errorInfo);
			e.printStackTrace();
		}
		return null;
	}
	
	/**
	 * Sends the generated XML request to the server and receives the server's
	 * response. Specifically, this method uses the HttpURLConnection passed to
	 * it to send the <var>request</var> content also passed to it to the server. 
	 * Then, the method reads the server's response out of the same connection.
	 * The server response code, response mime-type, and request content are 
	 * subsequently passed to the displayResponse method in order to display 
	 * the server response clearly. 
	 * 
	 * @param request String containing the valid XML contents of the request
	 * @param conn HttpURLConnection that is correctly configured to talk to the server
	 */
	private void sendDistRequest(String request, HttpURLConnection conn) {
		try {
			OutputStream out = conn.getOutputStream();
			
			// Send request to server
			out.write(request.getBytes("UTF-8"));
			out.close();
			
			conn.connect();
			
			// Receive server's response and put into StringBuffer
			final int code = conn.getResponseCode();
			final String contentType = conn.getContentType();
			final StringBuffer responseText = new StringBuffer();
			InputStreamReader in = new InputStreamReader(conn.getInputStream(),
					"UTF-8");

			char[] msg = new char[2048];
			int len;
			while ((len = in.read(msg)) > 0) {
				responseText.append(msg, 0, len);
			}

			// Pass server's response to displayResponse()
			displayResponse(code, contentType, responseText.toString());
		} catch (java.net.UnknownHostException e) {
			// If the server URL is wrong, report the warning
			System.err.println("Server URL could not be resolved");
			System.err.println("Connection to server FAILED");
			// increment the error counter and append the current
			// errorInfo to the errorList
			errorList = errorList.concat(errorInfo);
			errors++;
		} catch (IOException i) {
			i.printStackTrace();
			// increment the error counter and append the current
			// errorInfo to the errorList
			errorList = errorList.concat(errorInfo);
			errors++;
		}
	}
	
	/**
	 * Displays server's response in console window in a readable fashion. If
	 * the server returns an error, displays the error and increments errors. If
	 * the server returns a valid response, increments successes and if
	 * <var>verboseDisplay</var> = true, displays the complete Response. If
	 * <var>verboseDisplay</var> = false, displays only that the Request was
	 * successful. This method will flag an error if the server response is not
	 * 200 and the content type is not "application/vnd.adobe.adept+xml"
	 * 
	 * @param code
	 *            Server's HTML Response Code
	 * @param contentType
	 *            Server's Response Content Type
	 * @param responseString
	 *            Server's Response
	 */
	private void displayResponse(int code, String contentType,
			String responseString) {
		/* The response is an error (or is invalid) if:
		 * -> it begins with "<error" (as this is the way the packaging server returns errors)
		 * -> the response code is not 200 (the request succeeded)
		 * -> the response content type is not application/vdn.adobe.adept+xml
		 * 		(all responses from Adobe packaging servers will have this content type)
		 */
		if (responseString.substring(1, 6).equals("error") || code != 200 || !contentType.equals("application/vnd.adobe.adept+xml")) {
			if (verboseDisplay) {
				System.err.println("HTML Response Code: " + code);
				System.err.println("Response Content Type: " + contentType);
			}
			System.err.println("There was an error with the Request");
			System.err.println(responseString);
			errorList = errorList.concat(errorInfo);
			errors++;
		} else if (verboseDisplay) {
			System.out.println("HTML Response Code: " + code);
			System.out.println("Response Content Type: " + contentType);
			System.out.println("Response:\n" + responseString);
			successes++;
			System.out.println("The request was successful!");
		} else {
			successes++;
			System.out.println("The request was successful!");
		}
	}
	
	/**
	 * Displays a summary of the Tool's activity. Specifically, if
	 * at least one request was attempted, it will display the number
	 * of attempted requests, how many were successful, how many 
	 * distributionRights elements were skipped (due to bad syntax or
	 * an inability to find the resourceFile), how many unsuccessful 
	 * requests, and will finish with a list of the unsuccessful requests.
	 */
	private void displaySummary() {
		System.out.println("\nDistributionRights Requests Finished!");
		if (attempted > 0) {
			System.out.println("Number of attempted requests: " + attempted);
			System.out.println("Number of successful requests: " + successes);
			System.out.println("Number of skipped distributionRights elements: " + skipped);
			System.out.println("Number of unsuccessful requests: " + errors);
			System.out.println("List of unsuccessful requests: \n" + errorList);
		}
	}
	
	/* ******** C O N S T R U C T O R   A N D   M A I N ******** */
	
	/**
	 * <p>
	 * DistribTool Constructor.
	 * </p>
	 * <p>
	 * Calls scanArgsForFlags method to scan the passed <var>args</var> for
	 * recognizable command-line flags. Next, it sets the first argument
	 * passed to be the name of the XML configuration file for the tool.
	 * Finally, it calls processConfigFile to start running the tool.
	 * 
	 * @see #scanArgsForFlags(String[])
	 * @see #processConfigFile(String)
	 * @param args String[] that contains the command-line arguments
	 */
	public DistribTool(String[] args) {
		String configFileName = new String("");
		
		scanArgsForFlags(args);
		
		if(args.length < 1) {
			System.err.println("Too Few Arguments. EXIT");
			System.exit(1);
		}
		
		configFileName = args[0];
		processConfigFile(configFileName);
	}
	
	/**
	 * <p>
	 * Main.
	 * </p>
	 * <p>
	 * Calls the DistribTool Constructor
	 * </p>
	 * @param args String[] containing all arguments passed to DistribTool
	 */
	public static void main(String[] args) {
		new DistribTool(args);
		return;
	}
}