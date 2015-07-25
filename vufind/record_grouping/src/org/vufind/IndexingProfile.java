package org.vufind;

/**
 * A copy of indexing profile information from the database
 *
 * Pika
 * User: Mark Noble
 * Date: 6/30/2015
 * Time: 10:38 PM
 */
public class IndexingProfile {
	public Long id;
	public String name;
	public String marcPath;
	public String marcEncoding;
	public String individualMarcPath;
	public String groupingClass;
	public String recordNumberTag;
	public String recordNumberPrefix;
	public String itemTag ;
	public String formatSource;
	public char format;
	public char eContentDescriptor;
}
