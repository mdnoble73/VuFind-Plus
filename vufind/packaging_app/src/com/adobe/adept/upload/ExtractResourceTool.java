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
import java.io.FileOutputStream;
import java.io.FileReader;
import java.io.PrintStream;

import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NodeList;

import com.adobe.adept.client.XMLUtil;


/**
 * This tool will extract <resource> UUIDs from a specified XML source file.
 * In order to run this tool, you should give it a number of command-line.
 * This tool has no required arguments, but it will not function unless all
 * arguments are present. Here is a list of arguments:
 * <br><br><pre>
 * -v or -verbose = enables verbose display mode
 * -x or -xml = source XML file from which to extract UUIDs. This flag must
 * be followed by the path/name of the file to extract UUIDs from.
 * -f or -file = file to write to. This flag must be followed by the
 * path/name of the file to write output to.
 * 
 * @author piotrk
 *
 */
public class ExtractResourceTool {
	
	private boolean verboseDisplay = false;
	private boolean useXMLSource = false;
	private boolean writeOutputToFile = false;
	
	private String XMLSourceName = new String("");
	private String outputFileName = new String("");
	
	public void scanArgsForFlags(String[] args) {
		if(args.length == 0) {
			System.out.println("No arguments specified!");
			System.out.println("Tool will exit!");
		}
		for(int i = 0; i < args.length; i++) {
			if(args[i].equalsIgnoreCase("-v")
					|| args[i].equalsIgnoreCase("-verbose")) {
				verboseDisplay = true;
				System.out.println("Enabling verbose display mode!");
			}
			else if(args[i].equalsIgnoreCase("-x")
					|| args[i].equalsIgnoreCase("-xml")) {
				try {
					i++;
					XMLSourceName = args[i];
					useXMLSource = true;
					if(verboseDisplay)
						System.out.println("Will use XML Source file: " + XMLSourceName);
				} catch(Exception e) {
					System.err.println("-x and -xml flags must be followed by XML Source file to use");
					e.printStackTrace();
				}
			}
			else if(args[i].equalsIgnoreCase("-f")
					|| args[i].equalsIgnoreCase("-file")) {
				try {
					i++;
					outputFileName = args[i];
					writeOutputToFile = true;
					if(verboseDisplay)
						System.out.println("Will write extracted resourceFile to file: " + outputFileName);
				} catch(Exception e) {
					System.err.println("-f and -file flags must be followed by the output file name to use");
				}
			}
		}
	}
	
	public String generateResourceFileContent() {
		if(verboseDisplay)
			System.out.println("Generating ResourceFile Content!");
		if(useXMLSource) {
			File xmlSource = new File(XMLSourceName);
			String xmlSourceContent = new String("");
			String line = new String("");
			
			if(xmlSource.exists()) {
				if(verboseDisplay)
					System.out.println("Found XML Source File: " + xmlSource.getAbsolutePath());
				try {
					BufferedReader inFile = new BufferedReader(new FileReader(xmlSource));
					line = inFile.readLine();
					while (line != null) {
						xmlSourceContent = xmlSourceContent + line;
						line = inFile.readLine();
					}
					inFile.close();
				} catch (Exception e) {
					System.err.println("Error reading file: " + XMLSourceName);
					e.printStackTrace();
				}
				return extractResourceUUIDs(xmlSourceContent);
			}
			else {
				System.err.println("Could not find XML Source File: " + XMLSourceName);
				return null;
			}
		}
		return null;
	}
	
	public String extractResourceUUIDs(String xmlSourceContent) {
		if(xmlSourceContent == null)
			System.err.println("No xmlSourceContent to extract from!");
		else {
			String previousResource;
			String thisResource;
			
			try {
				// Set up output
				String resourceUUIDs = new String("");
				previousResource = ("");
				
				if(verboseDisplay)
					System.out.println("\nWriting resource UUIDs to resourceFile!");
				
				// Parse serverResponse into XML
				Document resourceXML = XMLUtil.parseXML(xmlSourceContent);
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
							System.out.println("Extracting UUID: " + thisResource);
						resourceUUIDs = resourceUUIDs + "\n" + thisResource;
						previousResource = thisResource;
					}
				}
				return resourceUUIDs;
			} catch (Exception e) {
				System.err.println("Error extracting UUIDs: " + outputFileName);
			}
		}
		return null;
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
	
	private void writeResourceFile(String content) {
		if(content == null) {
			System.err.println("There was no content!");
			System.err.println("Tool will not write to file!");
		}
		else {
			FileOutputStream out;
			PrintStream p;
			
			try {
				out = new FileOutputStream(outputFileName);
				p = new PrintStream(out);
				p.println(content);
				p.close();
				System.out.println("Successfully wrote output to file: " + outputFileName);
			} catch(Exception e) {
				System.err.println("Error writing to file: " + outputFileName);
			}
		}
	}
	
	public ExtractResourceTool(String[] args) {
		scanArgsForFlags(args);
		String resourceFileContent = generateResourceFileContent();
		if(writeOutputToFile)
			writeResourceFile(resourceFileContent);
	}
	public static void main(String[] args) {
		new ExtractResourceTool(args);
	}
}