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
package com.adobe.adept.client;

import java.io.InputStream;
import java.io.Reader;
import java.io.StringReader;
import java.io.StringWriter;
import java.io.UnsupportedEncodingException;
import java.io.Writer;
import java.security.InvalidKeyException;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.security.PrivateKey;
import java.security.PublicKey;
import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Arrays;
import java.util.Collections;
import java.util.Comparator;
import java.util.Date;
import java.util.Enumeration;
import java.util.UUID;
import java.util.Vector;

import javax.crypto.Cipher;
import javax.crypto.Mac;
import javax.crypto.SecretKey;
import javax.crypto.spec.SecretKeySpec;
import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.transform.OutputKeys;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;

import org.w3c.dom.Attr;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.NamedNodeMap;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;

/**
 * XMLUtil Class
 * <p>
 * Contains utility methods servicing the UploadTest Class. Included in these
 * are methods that prepare parts of the Package Request and parse the XML of
 * the server response. Also included are methods that sign the Package Request
 * or attach the required HMAC. A brief explanation of the HMAC and signing
 * processes is reproduced below:
 * </p>
 * <p>
 * Signature is always based on the public/private key infrastructure. The exact
 * algorithm and strength is encoded in the certificates which are typically
 * delivered outside of the message body. The identity of the signer is always
 * explicitly specified in the message description. Signature element is always
 * placed as the last child of the element being signed. The signature is based
 * on the SHA1 digest of the serialization of the infoset of the element being
 * signed and all its attributes, children and children's attributes (not
 * including the signature element itself). HMAC is based on the shared secret.
 * </p>
 * 
 * The infoset is serialized in the following way:
 * <ol>
 * <li>All adjacent text nodes are collapsed and their leading and trailing
 * whitespace is removed.</li>
 * <li>Zero-length text nodes are removed.</li>
 * <li>Signature elements in Adept namespace are removed.</li>
 * <li>Attributes are sorted first by their namespaces and then by their names;
 * sorting is done bytewise on UTF-8 representations.</li>
 * <li>Strings are serialized by writing two-byte length (in big endian order)
 * of the UTF-8 representation and then UTF-8 representation itself</li>
 * <li>Long strings (longer than 0x7FFF) are broken into chunks: first as many
 * strings of the maximum length 0x7FFF as needed, then the remaining string.
 * This is done on the byte level, irrespective of the UTF-8 boundary.</li>
 * <li>Text nodes (text and CDATA) are serialized by writing TEXT_NODE byte and
 * then text node value.</li>
 * <li>Attributes are serialized by writing ATTRIBUTE byte, then attribute
 * namespace (empty string if no namespace), attribute name, and attribute
 * value.</li>
 * <li>Elements are serialized by writing BEGIN_ELEMENT byte, then element
 * namespace, element name, all attributes END_ATTRIBUTES byte, all children,
 * END_ELEMENT byte.</li>
 * </ol>
 * 
 * @author Piotr Kula, Peter Sorotokin
 */
public class XMLUtil {

	static DocumentBuilderFactory domFactory = createDOMFactory();

	private static final ThreadLocal<SimpleDateFormat> w3cdtf = new ThreadLocal<SimpleDateFormat>() {
		protected SimpleDateFormat initialValue() {
			try {
				return new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssZ");
			} catch (Exception e) {
				throw new Error("Exception in static initializer: "
						+ e.toString());
			}
		}
	};

	static public final String SHA1 = "http://www.w3.org/2000/09/xmldsig#hmac-sha1";

	static public final String AdeptNS = "http://ns.adobe.com/adept";

	static public final String DublinCoreNS = "http://purl.org/dc/elements/1.1/";

	static public final String XMLNS = "http://www.w3.org/XML/1998/namespace";

	static public final String XHTMLNS = "http://www.w3.org/1999/xhtml";
	
	static public final String XMLDSigNS = "http://www.w3.org/2000/09/xmldsig#";

	static public final String CANON_METH = "http://www.w3.org/TR/2001/REC-xml-c14n-20010315";

	static public final String TRANS_METH = "http://www.w3.org/2000/09/xmldsig#enveloped-signature";

	/**
	 * Byte written to indicate beginning of an element in infoset serialization
	 * for signature/HMAC
	 */
	static final byte BEGIN_ELEMENT = 1;

	/**
	 * Byte written to indicate end of attributes in an element in infoset
	 * serialization for signature/HMAC
	 */
	static final byte END_ATTRIBUTES = 2;

	/**
	 * Byte written to indicate end of an element in infoset serialization for
	 * signature/HMAC
	 */
	static final byte END_ELEMENT = 3;

	/**
	 * Byte written to indicate beginning of a text node or CDATA in infoset
	 * serialization for signature/HMAC
	 */
	static final byte TEXT_NODE = 4;

	/**
	 * Byte written to indicate beginning of attributes in infoset serialization
	 * for signature/HMAC
	 */
	static final byte ATTRIBUTE = 5;

	/**
	 * Creates and configures a DocumentBuilderFactory for use with DOM. The
	 * returned DocumentBuilderFactory is namespace aware and the parsers
	 * constructed with it will not eliminate whitespace in elements.
	 * 
	 * @return Properly configured DocumentBuilderFactory
	 */
	static DocumentBuilderFactory createDOMFactory() {
		DocumentBuilderFactory domFactory = DocumentBuilderFactory
				.newInstance();
		domFactory.setNamespaceAware(true);
		domFactory.setIgnoringElementContentWhitespace(false);
		return domFactory;
	}

	/**
	 * Returns a Document derived from the DocumentBuilderFactory
	 * <var>domFactory</var>
	 * 
	 * @see XMLUtil#createDOMFactory()
	 * @see XMLUtil#domFactory
	 * @return Properly configured empty DOM Document
	 * @throws Exception
	 *             Never, since .createDocument is called with all parameters
	 *             null
	 */
	public static Document createDocument() throws Exception {
		return domFactory.newDocumentBuilder().getDOMImplementation()
				.createDocument(null, null, null);
	}

	/**
	 * Traverses the passed Document <var>doc</var> and sets the "id" attribute
	 * to be the user-defined ID attribute. This is used to find elements by
	 * their ID. <br>
	 * Specifically, this method simply gets the Document Element from
	 * <var>doc</var> and passes it to defineIDAttribute().
	 * 
	 * @see XMLUtil#defineIDAttribute(Element)
	 * @param doc
	 */
	public static void defineIDAttribute(Document doc) {
		Element e = doc.getDocumentElement();
		if (e != null)
			defineIDAttribute(e);
	}

	/**
	 * Traverses the passed Element <var>e</var> and sets the "id" attribute to
	 * be the user-defined ID attribute. This method recursively visits all
	 * children of the parent Element <var>e</var>. This is used to find
	 * elements by their ID
	 * 
	 * @param e
	 */
	private static void defineIDAttribute(Element e) {
		if (e.getAttribute("id").length() > 0)
			e.setIdAttribute("id", true);
		for (Node child = e.getFirstChild(); child != null; child = child
				.getNextSibling())
			if (child.getNodeType() == Document.ELEMENT_NODE)
				defineIDAttribute((Element) child);
	}

	/**
	 * Creates a DOM structured Document by parsing the contents of the InputStream
	 * passed to it, <var>in</var> 
	 * 
	 * @param in
	 *            Source to parse XML from
	 * @see XMLUtil#domFactory
	 * @return DOM structured Document created by parsing the contents of
	 *         <var>in</var>
	 * @throws Exception
	 *             If any IO or parsing errors occur
	 */
	public static Document parseXML(InputStream in) throws Exception {
		DocumentBuilder builder = domFactory.newDocumentBuilder();
		Document doc = builder.parse(new InputSource(in));
		defineIDAttribute(doc);
		return doc;
	}

	/**
	 * Creates a DOM structured Document by parsing the contents of the Reader
	 * passed to it, <var>in</var>. Note that in general, this is not a good
	 * method to use for reading files because you cannot guess XML encoding
	 * when the Reader is created.
	 * 
	 * @param in
	 *            Source to parse XML from
	 * @see XMLUtil#domFactory
	 * @return DOM structured Document created by parsing the contents of
	 *         <var>in</var>
	 * @throws Exception
	 *             If any IO or parsing errors occur
	 */
	public static Document parseXML(Reader in) throws Exception {
		DocumentBuilder builder = domFactory.newDocumentBuilder();
		Document doc = builder.parse(new InputSource(in));
		defineIDAttribute(doc);
		return doc;
	}

	/**
	 * Creates a DOM structured Document by parsing the contents of the String
	 * passed to it, <var>str</var> Note that in general, this is not a good
	 * method to use for reading arbitrary XML because you cannot guess XML
	 * encoding when reading XML file content to a string. 
	 * 
	 * @param str
	 *            Source to parse XML from
	 * @see XMLUtil#parseXML(Reader)
	 * @return DOM structured Document created by parsing <var>str</var>
	 * @throws Exception
	 *             If any IO or parsing errors occur
	 */
	public static Document parseXML(String str) throws Exception {
		return parseXML(new StringReader(str));
	}

	public static void serializeXML(Node e, Writer out) throws Exception {
		DOMSource domSource = new DOMSource(e);
		StreamResult streamResult = new StreamResult(out);
		TransformerFactory tf = TransformerFactory.newInstance();
		Transformer serializer = tf.newTransformer();
		// turn off <?xml...?> stuff as for documents that were parsed with
		// non-UTF8 encoding, serializer inserts encoding="[non-utf-8]" there which
		// it should not, since we always serialize as UTF-8
		serializer.setOutputProperty(OutputKeys.OMIT_XML_DECLARATION, "yes");
		serializer.setOutputProperty(OutputKeys.ENCODING, "UTF-8");
		// serializer.setOutputProperty(OutputKeys.INDENT, "yes");
		serializer.transform(domSource, streamResult);
	}

	public static String serializeXML(Node e) throws Exception {
		StringWriter result = new StringWriter();
		serializeXML(e, result);
		return result.toString();
	}

	/**
	 * Creates a String that represents the Date passed to it in W3C Date and
	 * Time Format.
	 * 
	 * @param date
	 *            Date to convert to W3C Date and Time Format
	 * @return String that represents the Date passed to it in W3C Date and Time
	 *         Format
	 */
	public static String dateToW3CDTF(Date date) {
		String s = w3cdtf.get().format(date);
		int index = s.length() - 2;
		return s.substring(0, index) + ":" + s.substring(index);
	}

	/**
	 * Creates a Date object from the string passed to it. The passed string
	 * needs to represent the date is W3C Date and Time Format
	 * 
	 * @param date
	 *            Source string containing date in W3C Date and Time Format
	 * @return Date object containing the date extracted from the string it is
	 *         passed
	 * @throws ParseException
	 *             If the begginning of <var>date</var> cannot be parsed
	 */
	public static Date dateFromW3CDTF(String date) throws ParseException {
		int len = date.length();
		if (len > 5 && date.charAt(len - 3) == ':') {
			char c = date.charAt(len - 6);
			if (c == '+' || c == '-')
				date = date.substring(0, len - 3) + date.substring(len - 2);
		}
		return (Date) w3cdtf.get().parseObject(date);
	}

	/**
	 * <p>
	 * The abstract Class Eater defines the framework for implementing the
	 * infoset serialization discussed in the XMLUtil class description. Eater
	 * serializes passed bytes, byte[], strings, or nodes according to the
	 * serialization specification discussed in the XMLUtil class description.
	 * 
	 * @see XMLUtil
	 */
	abstract static class Eater {
		private StringBuilder text = new StringBuilder();
		
		/**
		 * Abstract method that serializes the passed byte <var>b</var>
		 * 
		 * @param b
		 *            Byte to be serialized
		 * @see Digester#eatByte(byte)
		 * @see HMACDigester#eatByte(byte)
		 */
		abstract void eatByte(byte b);

		/**
		 * Abstract method that serializes the passed byte[] <var>bytes</var>
		 * 
		 * @param bytes
		 *            Byte[] to be serialized
		 * @see Digester#eatBytes(byte[])
		 * @see HMACDigester#eatBytes(byte[])
		 */
		abstract void eatBytes(byte[] bytes);

		/**
		 * Serializes the passed string <var>s</var> by writing the string
		 * length in big-endian order and then writing the UTF-8 string bytes.
		 * 
		 * @param s
		 *            Source string to be serialized
		 * @see XMLUtil
		 * @see Eater#eatByte(byte)
		 * @see Eater#eatBytes(byte[])
		 */
		void eatString(String s) {
			try {
				byte[] bytes = s.getBytes("UTF-8");
				int len = bytes.length;
				eatByte((byte) (len >> 8));
				eatByte((byte) len);
				eatBytes(bytes);
			} catch (UnsupportedEncodingException err) {
				err.printStackTrace();
			}
		}

		/**
		 * Serializes the passed Node <var>n</var> according to the NodeType.
		 * This method will recursively serialize any children of <var>n</var>.
		 * The way the elements are serialized is described in the XMLUtil class
		 * description.
		 * 
		 * @param n
		 * @see XMLUtil
		 * @see Eater#eatByte(byte)
		 * @see Eater#eatBytes(byte[])
		 * @see Eater#eatString(String)
		 * @see AttributeComparator
		 */
		void eatNode(Node n) {
			switch (n.getNodeType()) {
			case Document.ELEMENT_NODE: {
				Element e = (Element) n;
				String ns = n.getNamespaceURI();
				if (ns == null)
					ns = "";
				String ln = e.getLocalName();
				if (ns.equals(AdeptNS) && (ln.equals("signature") || ln.equals("hmac")))
					return; // ignored
				flushText();
				eatByte(BEGIN_ELEMENT);
				eatString(ns);
				eatString(ln);
				NamedNodeMap attr = e.getAttributes();
				int attrCount = attr.getLength();
				if (attrCount > 0) {
					Vector<Attr> attrList = new Vector<Attr>();
					for (int i = 0; i < attrCount; i++)
						attrList.add((Attr) attr.item(i));
					Collections.sort(attrList, new AttributeComparator());
					Enumeration<Attr> attrs = attrList.elements();
					while (attrs.hasMoreElements()) {
						eatNode(attrs.nextElement());
					}
				}
				eatByte(END_ATTRIBUTES);
				for (Node c = e.getFirstChild(); c != null; c = c
						.getNextSibling())
					eatNode(c);
				flushText();
				eatByte(END_ELEMENT);
				break;
			}
			case Document.TEXT_NODE:
			case Document.CDATA_SECTION_NODE: {
				String value = n.getNodeValue();
				int i = 0;
				if (text.length() == 0) {
					while (i < value.length() && Character.isWhitespace(value.charAt(i)))
						i++;
				}
				if (i < value.length())
					text.append(value, i, value.length());
				break;
			}
			case Document.ATTRIBUTE_NODE: {
				Attr a = (Attr) n;
				String ns = n.getNamespaceURI();
				if (ns == null)
					ns = "";
				// ignore xmlns declarations
				if (!ns.equals("http://www.w3.org/2000/xmlns/")) {
					/*
					 * This conditional statement ensures that the name of the
					 * attribute is retrieved correctly. If setAttribute() was
					 * used to create the attribute, the namespace will be null
					 * (so ns="") and getName() must be used to retrieve the
					 * name. If setAttributeNS() was used to create the
					 * attribute, the namespace will be the default namespace
					 * (in our case, AdeptNS), and getLocalName() must be used
					 * to retrieve the attribute name.
					 */
					String ln = (ns.length() > 0 ? a.getLocalName() : a
							.getName());
					String val = a.getValue();
					eatByte(ATTRIBUTE);
					eatString(ns);
					eatString(ln);
					eatString(val);
				}
				break;
			}
			}
		}

		private void flushText() {
			int i = text.length() - 1;
			while (i >= 0 && Character.isWhitespace(text.charAt(i)))
				i--;
			int len = i + 1;
			if (len > 0) {
				int done = 0;
				do {
					// the eater cannot take strings with length greater
					// than 0x7FFF,
					// so if value is longer, it is passed to the eater in
					// segments.
					int remains = Math.min(len - done, 0x7FFF);
					eatByte(TEXT_NODE);
					eatString(text.substring(done, done + remains));
					done += remains;
				} while (done < len);
			}
			text.setLength(0);
		}
	}

	/**
	 * The Digester Class is an implementation of the Eater abstract Class that
	 * is used to create the signature according to the specification described
	 * in the XMLUtil Class description.
	 * 
	 * @see Eater
	 * @see XMLUtil
	 */
	static class Digester extends Eater {

		MessageDigest digest;

		/**
		 * Digester Constructor. Sets the local MessageDigest <var> digest</var>
		 * to point to the one that is passed to the constructor.
		 * 
		 * @param digest
		 *            MessageDigest used to calculate signature
		 */
		Digester(MessageDigest digest) {
			this.digest = digest;
		}

		/**
		 * Places the passed byte <var>b</var> in the digest. Overrides the
		 * parent method from Eater Class.
		 * 
		 * @param b
		 *            Byte to be placed in the digest
		 * @see Eater#eatByte(byte)
		 */
		@Override
		void eatByte(byte b) {
			digest.update(b);
		}

		/**
		 * Places the passed byte[] <var>byte</var> in the digest. Overrides the
		 * parent method from Eater Class.
		 * 
		 * @param bytes
		 *            Byte[] to be placed in the digest
		 * @see Eater#eatBytes(byte[])
		 */
		@Override
		void eatBytes(byte[] bytes) {
			digest.update(bytes);
		}
	}

	/**
	 * The HMACDigester class is an implementation of the Eater abstract Class
	 * that is used to create the HMAC according to the specifications described
	 * in the XMLUtil Class description.
	 * 
	 * @see Eater
	 * @see XMLUtil
	 */
	static class HMACDigester extends Eater {
		Mac mac;

		/**
		 * HMACDisgester constructor. Sets the local Mac <var>mac</var> to point
		 * to the one that is passed to the constructor.
		 * 
		 * @param mac
		 *            Mac used to calculate HMAC
		 */
		HMACDigester(Mac mac) {
			this.mac = mac;
		}

		/**
		 * Places the passed byte <var>b</var> in the Mac. Overrides the parent
		 * method from Eater Class.
		 * 
		 * @param b
		 *            Byte to be placed in the digest
		 * @see Eater#eatByte(byte)
		 */
		@Override
		void eatByte(byte b) {
			mac.update(b);
		}

		/**
		 * Places the passed byte[] <var>bytes</var> in the Mac. Overrides the
		 * parent method from Eater Class.
		 */
		@Override
		void eatBytes(byte[] bytes) {
			mac.update(bytes);
		}
	}

	/**
	 * The AttributeComparator Class provides the mechanism for sorting
	 * Attributes as described by the specification in the XMLUtil Class
	 * description. Attributes are sorted first by their namespaces and then by
	 * their names; sorting is done bytewise on UTF-8 representations.
	 * 
	 * @see XMLUtil
	 */
	static class AttributeComparator implements Comparator<Attr> {

		/**
		 * Compares two nodes <var>arg0</var> and <var>arg1</var> that are
		 * passed to it according to the specification described in the XMLUtil
		 * Class description.
		 * 
		 * @param arg0
		 *            Attribute to compare against <var>arg1</var>
		 * @param arg1
		 *            Attribute to compare against <var>arg2</var>
		 * @return -1 if the <var>arg1</var> should precede <var>arg0</var> <br>
		 *         0 if <var>arg1</var> and <var>arg2</var> are equal <br>
		 *         a positive int if <var>arg0</var> should precede
		 *         <var>arg1</var>
		 */
		public int compare(Attr a1, Attr a2) {
			try {
				String n1 = a1.getNamespaceURI();
				String n2 = a2.getNamespaceURI();
				if (n1 == n2 || (n1 != null && n2 != null && n1.equals(n2))) {
					if (n1 == null) {
						// apparently, there is a bug in some Java versions,
						// that localName is null when namespace is null
						n1 = a1.getNodeName();
						n2 = a2.getNodeName();
					} else {
						n1 = a1.getLocalName();
						n2 = a2.getLocalName();
					}
				}
				if (n2 == null)
					return 1;
				if (n1 == null)
					return -1;
				byte[] b1 = n1.getBytes("UTF-8");
				byte[] b2 = n2.getBytes("UTF-8");
				int len = b1.length > b2.length ? b2.length : b1.length;
				for (int i = 0; i < len; i++) {
					int i1 = (int) (b1[i] & 0xFF);
					int i2 = (int) (b2[i] & 0xFF);
					int d = i1 - i2;
					if (d != 0)
						return d;
				}
				return b1.length - b2.length;
			} catch (UnsupportedEncodingException err) {
				err.printStackTrace();
				return 0;
			}
		}
	}

	/**
	 * Encodes and encrypts the signature element that is passed to it and
	 * returns the result in a byte array. The element <var>e</var> is first
	 * encoded with SHA-1 encoding, and the encrypted with RSA/ECB/PKCS1Padding,
	 * making use of the PrivateKey that it is passed
	 * 
	 * @param key
	 *            PrivateKey with which to encrypt
	 * @param e
	 *            Source element to encode
	 * @see Digester#eatNode(Node)
	 * @return byte[] containing the encoded signature element
	 */
	private static byte[] getSignatureBytes(PrivateKey key, Element e) {
		try {
			MessageDigest d = MessageDigest.getInstance("SHA-1");
			Digester ds = new Digester(d);
			ds.eatNode(e);
			byte[] hash = d.digest();
			Cipher cipher = Cipher.getInstance("RSA/ECB/PKCS1Padding");
			cipher.init(Cipher.ENCRYPT_MODE, key);
			return cipher.doFinal(hash);
		} catch (Exception ex) {
			ex.printStackTrace();
			throw new RuntimeException(e.toString());
		}
	}

	/**
	 * Compares the signature bytes <var>signature</var> passed to it to the
	 * signature element. In order to compare them, this method creates a
	 * signature for the passed element <var>e</var> using SHA-1 encoding, and
	 * stores the result in a byte[]. The method then decrypts
	 * <var>signature</var> using RSA/ECB/PKCS1Padding and the passed PublicKey
	 * <var>key</var>
	 * 
	 * @param signature
	 *            Encrypted signature bytes
	 * @param key
	 *            Public Key to use for decrypting
	 * @param e
	 *            Source element to compare signature to
	 * @return Result of array comparison between encoded element signature and
	 *         decrypted signature bytes
	 */
	private static boolean checkSignature(byte[] signature, PublicKey key,
			Element e) {
		try {
			MessageDigest d = MessageDigest.getInstance("SHA-1");
			Digester ds = new Digester(d);
			ds.eatNode(e);
			byte[] hash = d.digest();
			Cipher cipher = Cipher.getInstance("RSA/ECB/PKCS1Padding");
			cipher.init(Cipher.DECRYPT_MODE, key);
			byte[] dhash = cipher.doFinal(signature);
			return Arrays.equals(hash, dhash);
		} catch (Exception ex) {
			ex.printStackTrace();
			throw new RuntimeException(e.toString());
		}
	}

	/**
	 * Signs the passed Element <var>e</var> by appending a &lt;signature&gt;
	 * element. The signature contains the encrypted signature bytes received
	 * from getSignatureBytes(key, e) encoded into Base64.
	 * 
	 * @param key
	 *            PrivateKey passed to getSignatureByte(key, e) in order to
	 *            create encoded signature bytes
	 * @param e
	 *            Element to be signed
	 * @see XMLUtil#getSignatureBytes(PrivateKey, Element)
	 * @see Base64#encodeBytes(byte[])
	 * @throws Exception
	 *             If a DOMException would be thrown by appendChild or
	 *             createElementNS
	 */
	public static void sign(PrivateKey key, Element e) throws Exception {
		byte[] signatureBytes = getSignatureBytes(key, e);
		Document doc = e.getOwnerDocument();
		Element signature = doc.createElementNS(AdeptNS, "signature");
		signature.appendChild(doc.createTextNode(Base64Util
				.encode(signatureBytes)));
		e.appendChild(signature);
	}

	public static void addUserNonceExpiration(Element e, UUID user)
			throws Exception {
		Document doc = e.getOwnerDocument();
		Element adminE = doc.createElementNS(XMLUtil.AdeptNS, "user");
		adminE.appendChild(doc.createTextNode("urn:uuid:" + user));
		e.appendChild(adminE);

		Element nonceE = doc.createElementNS(XMLUtil.AdeptNS, "nonce");
		nonceE.appendChild(doc.createTextNode(Base64Util.encode(SecurityUtil
				.newNonce())));
		e.appendChild(nonceE);

		Element expirationE = doc
				.createElementNS(XMLUtil.AdeptNS, "expiration");
		expirationE.appendChild(doc.createTextNode(XMLUtil
				.dateToW3CDTF(new Date(
						System.currentTimeMillis() + 15 * 60 * 1000))));
		e.appendChild(expirationE);
	}

	/**
	 * Signs the passed Element <var>e</var> with HMAC by appending a
	 * &lt;hmac&gt; element. &lt;hmac&gt; contains the the encrypted bytes
	 * recieved from createHMAC(secretKeyBytes, e) encoded into Base64.
	 * 
	 * @param secretKeyBytes
	 *            contains the bytes of the HMAC password
	 * @param e
	 *            Element to be HMAC-signed
	 * @see XMLUtil#createHMAC(byte[], Element)
	 * @see Base64#encodeBytes(byte[])
	 * @throws Exception
	 *             If a DOMExeption would be thrown by appendChild or
	 *             createElementNS
	 */
	public static void hmac(byte[] secretKeyBytes, Element e) throws Exception {
		byte[] hmacKeyBytes = createHMAC(secretKeyBytes, e);
		Document doc = e.getOwnerDocument();
		Element hmacElement = doc.createElementNS(AdeptNS, "hmac");
		hmacElement.appendChild(doc.createTextNode(Base64Util
				.encode(hmacKeyBytes)));
		e.appendChild(hmacElement);
	}

	/**
	 * Creates the HMAC bytes from the Element <var>packageElement</var> passed
	 * to it, making use of <var>secretKeyBytes</var> and the HmacSHA1
	 * algorythm. This method uses the HMACDigester to calculate the HMAC
	 * content. The HMAC is created according to the specification described in
	 * the XMLUtil Class description.
	 * 
	 * @param secretKeyBytes
	 *            byte[] containing the bytes of the secret key
	 * @param packageElement
	 *            Source Element to calculate HMAC for
	 * @see HMACDigester#eatNode(Node)
	 * @return byte[] containing the HMAC created from the passed Element
	 *         <var>packageElement</var>
	 */
	public static byte[] createHMAC(byte[] secretKeyBytes,
			Element packageElement) {
		try {
			final String alg = "HmacSHA1";
			Mac mac = Mac.getInstance(alg);
			SecretKey key = new SecretKeySpec(secretKeyBytes, alg);
			mac.init(key);
			HMACDigester HMACGenerator = new HMACDigester(mac);
			HMACGenerator.eatNode(packageElement);
			return mac.doFinal();
		} catch (NoSuchAlgorithmException e) {
			throw new Error("HmacSHA1 not supported???");
		} catch (InvalidKeyException e) {
			throw new Error("Hmac key is not supported");
		}
	}

	/**
	 * Verifies if the passed Element <var>e</var> has a valid signature
	 * attached. The signature is stored in the &lt;signature&gt; element that
	 * is a child of <var>e</var> and must be decoded from Base64 encoding.
	 * 
	 * @param key
	 *            PublicKey used for decrypting the contents of the signature
	 *            element
	 * @param e
	 *            Source Element that contains the signature to verify
	 * @see Base64#decode(String)
	 * @see XMLUtil#checkSignature(byte[], PublicKey, Element)
	 * @return true if the &lt;signature&gt; element is present and contains a
	 *         valid signature <br>
	 *         false if the &lt;signature&gt; is missing or contains an invalid
	 *         signature
	 * @throws Exception
	 *             when getTextContent would return more characters than would
	 *             fit in a <tt>DOMString</tt> variable on th implementation
	 *             platform
	 */
	public static boolean verifySignature(PublicKey key, Element e)
			throws Exception {
		byte[] recordedSignature = null;
		for (Node n = e.getFirstChild(); n != null; n = n.getNextSibling()) {
			if (n.getNodeType() != Document.ELEMENT_NODE)
				continue;
			Element ne = (Element) n;
			if (ne.getLocalName().equals("signature")
					&& ne.getNamespaceURI().equals(AdeptNS)) {
				recordedSignature = Base64Util.decode(ne.getTextContent());
			}
		}
		if (recordedSignature == null)
			return false;
		return checkSignature(recordedSignature, key, e);
	}

	/**
	 * Extracts the UUID from the element named <var>name</var> from the
	 * Document <var>message</var>. Will return null if <var>message</var> does
	 * not contain the named element or if the element does not contain a UUID.
	 * 
	 * @param message
	 *            Source message to extract UUID from
	 * @param name
	 *            Name of the element that contains the UUID
	 * @see XMLUtil#extractAdeptElementText(Document, String)
	 * @return UUID contained in the named element, <br>
	 *         or null if <var>message</var> does not contain the named element
	 *         or the named element does not contain a UUID
	 */
	public static UUID extractUUID(Document message, String name) {
		return extractUUID(message, name, true);
	}
	
	/**
	 * Extracts the UUID from the element named <var>name</var> from the
	 * Document <var>message</var>. Will return null if <var>message</var> does
	 * not contain the named element or if the element does not contain a UUID.
	 * 
	 * @param message
	 *            Source message to extract UUID from
	 * @param name
	 *            Name of the element that contains the UUID
	 * @see XMLUtil#extractAdeptElementText(Document, String)
	 * @return UUID contained in the named element, <br>
	 *         or null if <var>message</var> does not contain the named element
	 *         or the named element does not contain a UUID
	 */
	public static UUID extractUUID(Document message, String name, boolean strict) {
		String userStr = extractAdeptElementText(message, name, strict);
		if (userStr == null || !userStr.startsWith("urn:uuid:"))
			return null;
		return UUID.fromString(userStr.substring(9));
	}

	/**
	 * Extracts and decodes the Base64 encoded binary content of the element
	 * named <var>name</var> from the Document <var>message</var>.
	 * 
	 * @param message
	 *            Source Document that contains the element named
	 *            <var>name</var>
	 * @param name
	 *            Name of the element that contains the binary content to
	 *            extract
	 * @see XMLUtil#extractAdeptElementText(Document, String)
	 * @see Base64#decode(String)
	 * @return byte[] containing the decoded content of the element
	 *         <var>name</var>
	 */
	public static byte[] extractBinary(Document message, String name) {
		return extractBinary (message, name, true);
	}
	
	/**
	 * Extracts and decodes the Base64 encoded binary content of the element
	 * named <var>name</var> from the Document <var>message</var>.
	 * 
	 * @param message
	 *            Source Document that contains the element named
	 *            <var>name</var>
	 * @param name
	 *            Name of the element that contains the binary content to
	 *            extract
	 * @see XMLUtil#extractAdeptElementText(Document, String)
	 * @see Base64#decode(String)
	 * @return byte[] containing the decoded content of the element
	 *         <var>name</var>
	 */
	public static byte[] extractBinary(Document message, String name, boolean strict) {
		String fpStr = extractAdeptElementText(message, name, strict);
		if (fpStr == null)
			return null;
		return Base64Util.decode(fpStr);
	}

	/**
	 * Extracts the text content of the Adept Namespace element named
	 * <var>name</var> from the Document <var>message</var>. This method returns
	 * null if the named element is not a child of <var>message</var> or if
	 * <var>message</var> contains more than one element named <var>name</var>
	 * 
	 * @param message
	 *            Source Document that contains the element named
	 *            <var>name</var>
	 * @param name
	 *            Name of the element that contains the text content to extract
	 * @see XMLUtil#AdeptNS
	 * @return String containing the text content of the element
	 *         <var>name</var>, <br>
	 *         or null if <var>message</var> contains zero or more than one
	 *         element named <var>name</var>
	 */
	public static String extractAdeptElementText(Document message, String name) {
		return extractAdeptElementText(message, name, true);
	}
	
	/**
	 * Extracts the text content of the Adept Namespace element named
	 * <var>name</var> from the Document <var>message</var>. This method returns
	 * null if the named element is not a child of <var>message</var> or if
	 * <var>message</var> contains more than one element named <var>name</var>
	 * 
	 * @param message
	 *            Source Document that contains the element named
	 *            <var>name</var>
	 * @param name
	 *            Name of the element that contains the text content to extract
	 * @see XMLUtil#AdeptNS
	 * @return String containing the text content of the element
	 *         <var>name</var>, <br>
	 *         or null if <var>message</var> contains zero or more than one
	 *         element named <var>name</var>
	 */
	public static String extractAdeptElementText(Document message, String name, boolean strict) {
		NodeList list = message.getElementsByTagNameNS(XMLUtil.AdeptNS, name);
		if (list == null || list.getLength() == 0 || (strict && list.getLength() > 1))
			return null;
		Element element = (Element) list.item(0);
		return element.getTextContent();
	}

	/**
	 * Extracts the text content of the Dublin Core Namespace element named
	 * <var>name</var> from the Document <var>message</var>. This method
	 * returns null if the named element is not a child of <var>message</var>
	 * or if <var>message</var> contains more than one element named <var>name</var>
	 * 
	 * @param message
	 *            Source Document that contains the element named <var>name</var>
	 * @param name
	 *            Name of the element that contains the text content to extract
	 * @see XMLUtil#AdeptNS
	 * @return String containing the text content of the element <var>name</var>,
	 *         <br>
	 *         or null if <var>message</var> contains zero or more than one
	 *         element named <var>name</var>
	 */
	public static String extractDCElementText(Document message, String name) {
		return extractDCElementText(message, name, true);
	}
	
	/**
	 * Extracts the text content of the Dublin Core Namespace element named
	 * <var>name</var> from the Document <var>message</var>. This method
	 * returns null if the named element is not a child of <var>message</var>
	 * or if <var>message</var> contains more than one element named <var>name</var>
	 * 
	 * @param message
	 *            Source Document that contains the element named <var>name</var>
	 * @param name
	 *            Name of the element that contains the text content to extract
	 * @see XMLUtil#AdeptNS
	 * @return String containing the text content of the element <var>name</var>,
	 *         <br>
	 *         or null if <var>message</var> contains zero or more than one
	 *         element named <var>name</var>
	 */
	public static String extractDCElementText(Document message, String name, boolean strict) {
		NodeList list = message.getElementsByTagNameNS(XMLUtil.DublinCoreNS,
				name);
		if (list == null || list.getLength() == 0 || (strict && list.getLength() > 1))
			return null;
		Element element = (Element) list.item(0);
		return element.getTextContent();
	}

	/**
	 * Extracts the UUID from the child element named <var>name</var> from the
	 * Element <var>e</var>. The named element must be in the Adept namespace.
	 * This method will return null if <var>e</var> does not contain the named
	 * element or if the named element does not contain a UUID.
	 * 
	 * @param e
	 *            Source Element that is the parent of the named element
	 * @param name
	 *            Name of the element to extract UUID from
	 * @see XMLUtil#extractAdeptElementText(Element, String)
	 * @return UUID contained in the named element, <br>
	 *         or null if <var>e</var> does not contain the named element or the
	 *         element does not contain a UUID
	 */
	public static UUID extractUUID(Element e, String name) {
		return extractUUID(e, name, true);
	}
	
	/**
	 * Extracts the UUID from the child element named <var>name</var> from the
	 * Element <var>e</var>. The named element must be in the Adept namespace.
	 * This method will return null if <var>e</var> does not contain the named
	 * element or if the named element does not contain a UUID.
	 * 
	 * @param e
	 *            Source Element that is the parent of the named element
	 * @param name
	 *            Name of the element to extract UUID from
	 * @see XMLUtil#extractAdeptElementText(Element, String)
	 * @return UUID contained in the named element, <br>
	 *         or null if <var>e</var> does not contain the named element or the
	 *         element does not contain a UUID
	 */
	public static UUID extractUUID(Element e, String name, boolean strict) {
		String userStr = extractAdeptElementText(e, name, strict);
		if (userStr == null || !userStr.startsWith("urn:uuid:"))
			return null;
		return UUID.fromString(userStr.substring(9));
	}

	/**
	 * Extracts and decodes the Base64 encoded binary content of the child
	 * element named <var>name</var> from the Element <var>e</var>. The named
	 * element must be in the Adept namespace.
	 * 
	 * @param e
	 *            Element that contains the child element named <var>name</var>
	 * @param name
	 *            Name of the element that contains the binary content to
	 *            extract
	 * @see XMLUtil#extractAdeptElementText(Element, String)
	 * @see Base64#decode(String)
	 * @return byte[] containing the decoded content of the element
	 *         <var>name</var>
	 */
	public static byte[] extractBinary(Element message, String name) {
		return extractBinary(message, name, true);
	}
	
	/**
	 * Extracts and decodes the Base64 encoded binary content of the child
	 * element named <var>name</var> from the Element <var>e</var>. The named
	 * element must be in the Adept namespace.
	 * 
	 * @param e
	 *            Element that contains the child element named <var>name</var>
	 * @param name
	 *            Name of the element that contains the binary content to
	 *            extract
	 * @see XMLUtil#extractAdeptElementText(Element, String)
	 * @see Base64#decode(String)
	 * @return byte[] containing the decoded content of the element
	 *         <var>name</var>
	 */
	public static byte[] extractBinary(Element message, String name, boolean strict) {
		String fpStr = extractAdeptElementText(message, name, strict);
		if (fpStr == null)
			return null;
		return Base64Util.decode(fpStr);
	}

	/**
	 * Extracts the text content of the Adept Namespace child element named
	 * <var>name</var> from the Element <var>e</var>. This method returns null
	 * if the named element is not a child of <var>e</var> or if <var>e</var>
	 * contains more than one element named <var>name</var>
	 * 
	 * @param e
	 *            Element that contains the child element named <var>name</var>
	 * @param name
	 *            Name of the element that contains the text content to extract
	 * @see XMLUtil#AdeptNS
	 * @return String containing the text content of the element
	 *         <var>name</var>, <br>
	 *         or null if <var>e</var> contains zero or more than one element
	 *         named <var>name</var>
	 */
	public static String extractAdeptElementText(Element e, String name) {
		return extractAdeptElementText(e, name, true);
	}
	
	/**
	 * Extracts the text content of the Adept Namespace child element named
	 * <var>name</var> from the Element <var>e</var>. This method returns null
	 * if the named element is not a child of <var>e</var> or if <var>e</var>
	 * contains more than one element named <var>name</var>
	 * 
	 * @param e
	 *            Element that contains the child element named <var>name</var>
	 * @param name
	 *            Name of the element that contains the text content to extract
	 * @see XMLUtil#AdeptNS
	 * @return String containing the text content of the element
	 *         <var>name</var>, <br>
	 *         or null if <var>e</var> contains zero or more than one element
	 *         named <var>name</var>
	 */
	public static String extractAdeptElementText(Element e, String name, boolean strict) {
		NodeList list = e.getElementsByTagNameNS(XMLUtil.AdeptNS, name);
		if (list == null || list.getLength() == 0 || (strict && list.getLength() > 1))
			return null;
		Element element = (Element) list.item(0);
		return element.getTextContent();
	}

	/**
	 * Extracts the element named <var>name</var> with the namespace
	 * <var>elementNS</var> that is a descentant of the Document
	 * <var>message</var>. This method returns null if the named elemens is not
	 * a child of <var>message</var> or if <var>message</var> contains more than
	 * one element named <var>name</var>
	 * 
	 * @param message
	 *            Source Document that contains the element named
	 *            <var>name</var>
	 * @param elementNS
	 *            Namespace of the element named <var>name</var>
	 * @param name
	 *            Name of the element to be extracted
	 * @return Element named <var>name</var>, <br>
	 *         or null if <var>message</var> contains zero or more than one
	 *         element named <var>name</var>
	 */
	public static Element extractElement(Document message, String elementNS,
			String name) {
		return extractElement(message, elementNS, name, true);
	}
	
	/**
	 * Extracts the element named <var>name</var> with the namespace
	 * <var>elementNS</var> that is a descentant of the Document
	 * <var>message</var>. This method returns null if the named elemens is not
	 * a child of <var>message</var> or if <var>message</var> contains more than
	 * one element named <var>name</var>
	 * 
	 * @param message
	 *            Source Document that contains the element named
	 *            <var>name</var>
	 * @param elementNS
	 *            Namespace of the element named <var>name</var>
	 * @param name
	 *            Name of the element to be extracted
	 * @return Element named <var>name</var>, <br>
	 *         or null if <var>message</var> contains zero or more than one
	 *         element named <var>name</var>
	 */
	public static Element extractElement(Document message, String elementNS,
			String name, boolean strict) {
		NodeList list = message.getElementsByTagNameNS(elementNS, name);
		if (list == null || list.getLength() == 0 || (strict && list.getLength() > 1))
			return null;
		Element element = (Element) list.item(0);
		return element;
	}

	/**
	 * Extracts the element named <var>name</var> with the namespace
	 * <var>elementNS</var> that is a descentant of the element
	 * <var>message</var>. This method returns null if the named elemens is not
	 * a child of <var>message</var> or if <var>message</var> contains more than
	 * one element named <var>name</var>
	 * 
	 * @param message
	 *            Source Document that contains the element named
	 *            <var>name</var>
	 * @param elementNS
	 *            Namespace of the element named <var>name</var>
	 * @param name
	 *            Name of the element to be extracted
	 * @return Element named <var>name</var>, <br>
	 *         or null if <var>message</var> contains zero or more than one
	 *         element named <var>name</var>
	 */
	public static Element extractElement(Element message, String elementNS,
			String name) {
		return extractElement(message, elementNS, name, true);
	}
	
	/**
	 * Extracts the element named <var>name</var> with the namespace
	 * <var>elementNS</var> that is a descentant of the element
	 * <var>message</var>. This method returns null if the named elemens is not
	 * a child of <var>message</var> or if <var>message</var> contains more than
	 * one element named <var>name</var>
	 * 
	 * @param message
	 *            Source Document that contains the element named
	 *            <var>name</var>
	 * @param elementNS
	 *            Namespace of the element named <var>name</var>
	 * @param name
	 *            Name of the element to be extracted
	 * @return Element named <var>name</var>, <br>
	 *         or null if <var>message</var> contains zero or more than one
	 *         element named <var>name</var>
	 */
	public static Element extractElement(Element message, String elementNS,
			String name, boolean strict) {
		NodeList list = message.getElementsByTagNameNS(elementNS, name);
		if (list == null || list.getLength() == 0 || (strict && list.getLength() > 1))
			return null;
		Element element = (Element) list.item(0);
		return element;
	}

	/**
	 * Extracts the content of the element named <var>name</var> with the namespace
	 * <var>elementNS</var> that is a descentant of the element
	 * <var>message</var>. This method returns null if the named elemens is not
	 * a child of <var>message</var> or if <var>message</var> contains more than
	 * one element named <var>name</var>
	 * 
	 * @param message
	 *            Source Document that contains the element named
	 *            <var>name</var>
	 * @param elementNS
	 *            Namespace of the element named <var>name</var>
	 * @param name
	 *            Name of the element to be extracted
	 * @return Element named <var>name</var>, <br>
	 *         or null if <var>message</var> contains zero or more than one
	 *         element named <var>name</var>
	 */
	public static String extractElementText(Element message, String elementNS,
			String name) {
		return extractElementText(message, elementNS, name, true);
	}
	
	/**
	 * Extracts the content of the element named <var>name</var> with the namespace
	 * <var>elementNS</var> that is a descentant of the element
	 * <var>message</var>. This method returns null if the named elemens is not
	 * a child of <var>message</var> or if <var>message</var> contains more than
	 * one element named <var>name</var>
	 * 
	 * @param message
	 *            Source Document that contains the element named
	 *            <var>name</var>
	 * @param elementNS
	 *            Namespace of the element named <var>name</var>
	 * @param name
	 *            Name of the element to be extracted
	 * @return Element named <var>name</var>, <br>
	 *         or null if <var>message</var> contains zero or more than one
	 *         element named <var>name</var>
	 */
	public static String extractElementText(Element message, String elementNS,
			String name, boolean strict) {
		NodeList list = message.getElementsByTagNameNS(elementNS, name);
		if (list == null || list.getLength() == 0 || (strict && list.getLength() != 1))
			return null;
		Element element = (Element) list.item(0);
		return element.getTextContent();
	}

	/**
	 * <p>
	 * Escapes XML reserved characters to their corresponding XML-safe versions.
	 * Specifically, this method:
	 * </p>
	 * <xmp> Replaces & with &amp; Replaces < with &lt; Replaces > with &gt;
	 * </xmp>
	 * 
	 * @param txt
	 *            Source string with text that needs to have XML characters
	 *            escaped
	 * @return String that has reserved XML characters escaped
	 */
	public static String escapeXMLText(String txt) {
		return txt.replace("&", "&amp;").replace("<", "&lt;").replace(">",
				"&gt;");
	}

	/**
	 * Escapes XML attribute reserved characters to their corresponding XML-safe
	 * versions. Specifically, this method: <xmp> Replaces " with &quot; </xmp>
	 * 
	 * @param txt
	 *            Source string with text that needs to have XML attribute
	 *            characters escaped
	 * @return String that has reserved XML attribute characters escaped
	 */
	public static String escapeXMLAttrText(String txt) {
		return escapeXMLText(txt).replace("\"", "&quot;");
	}

	public static byte[] SHA1(String text) throws NoSuchAlgorithmException,
			UnsupportedEncodingException {
		MessageDigest md;
		md = MessageDigest.getInstance("SHA-1");
		byte[] sha1hash = new byte[40];
		md.update(text.getBytes(), 0, text.length());
		// "iso-8859-1"
		sha1hash = md.digest();
		return (sha1hash);
	}
}
