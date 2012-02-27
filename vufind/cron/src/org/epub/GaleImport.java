package org.epub;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.File;
import java.io.FileOutputStream;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;
import java.io.OutputStreamWriter;
import java.net.MalformedURLException;
import java.net.URL;
import java.sql.DriverManager;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;


import org.apache.log4j.Logger;
import org.apache.pdfbox.util.PDFMergerUtility;
import org.ini4j.Profile.Section;
import org.vufind.IProcessHandler;
import org.vufind.Util;

import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

public class GaleImport extends ImportBase implements IProcessHandler {
	@Override
	public void doCronProcess(Section processSettings, Section generalSettings, Logger logger) {
		this.logger = logger;
		logger.info("Loading Gale Group titles");
		
		boolean configLoaded = loadConfig(processSettings, generalSettings);
		if (!configLoaded){
			System.out.println("Configuration could not be loaded, see log file");
			return;
		}
		
		// Connect to the VuFind MySQL database
		try {
			conn = DriverManager.getConnection(databaseConnectionInfo);
		} catch (SQLException ex) {
			// handle any errors
			logger.error("Error establishing connection to database " + databaseConnectionInfo + " " + ex.toString());
			return;
		}
		
		File sourceDir = new File(sourceDirectory);
		File[] bookFolders = sourceDir.listFiles();
		// Process each folder
		for (File bookFolder : bookFolders) {
			if (bookFolder.isDirectory()) {
				processRootBookFolder(bookFolder);
			}
		}
		
		try {
			conn.close();
		} catch (SQLException e) {
			logger.error("Error establishing closing connection to database " + databaseConnectionInfo + " " + e.toString());
			e.printStackTrace();
		}

		writeReport("galeImport");
		
		// Send report via e-mail
	}
	

	private void processRootBookFolder(File bookFolder){
	// Get the isbn represented by the
		String isbn = bookFolder.getName();
		isbn = isbn.replaceAll("-", "");
		logger.info("Processing isbn " + isbn);
		
		File[] subFolders = bookFolder.listFiles();
		int volume = 1;
		int numVolumes = subFolders.length;
		logger.info("Found " + numVolumes + " volumes for this title.");
		//Extract the cover image from the first folder
		File coverImage = null;
		for (File subFolder : subFolders){
			ImportResult result = new ImportResult();
			//Gale has added folder for each volume within the  
			String baseFilename = "Gale_" + isbn ;
			if (numVolumes != 1){
				baseFilename += "_v" + volume ;
			}
			result.setBaseFilename(baseFilename);
			result.setISBN(isbn);
			result.setVolume(volume);
			if (subFolder.isDirectory()){
				if (volume == 1){
					coverImage = extractCoverImage(subFolder);
					//Move the cover to the correct location. 
					if (coverImage != null){
						logger.info("Found cover image " + coverImage.getPath());
						try {
							String coverExtension = coverImage.getName().substring(coverImage.getName().lastIndexOf("."));
							File coverDestination = new File(coverDirectory + baseFilename + coverExtension); 
							logger.info("Copying " + coverImage.getAbsolutePath() + " to " + coverDestination.getName());
							if (coverDestination.exists()){
								result.setCoverImported("skipped");
							}else{
								Util.copyFile(coverImage, coverDestination);
								result.setCoverImported("success");
							}
							coverImage = coverDestination;
						} catch (IOException e1) {
							result.addNote("Folder " + baseFilename + " is invalid because the cover image could not be moved to the cover directory.");
							result.setCoverImported("failed");
							return;
						}
					}else{
						result.setCoverImported("failed");
					}
				}else{
					result.setCoverImported("skipped");
				}
				processBookFolder(result, subFolder, coverImage, isbn, volume, numVolumes, baseFilename);
				
				importResults.add(result);
			}
			volume++;
		}
	}
	
	private File extractCoverImage(File bookFolder){
		logger.info("Looking for cover in " + bookFolder.getName());
		File[] sourceFiles = bookFolder.listFiles();
		File imagesFolder = null;
		for (File sourceFile : sourceFiles) {
			if (sourceFile.isDirectory() && sourceFile.getName().equals("images")){
				imagesFolder = sourceFile;
			}
		}
		File coverFile = null;
		if (imagesFolder != null){
			logger.info("Found images folder " + imagesFolder.getName());
			File[] images = imagesFolder.listFiles();
			for (File sourceFile : images){
				if (sourceFile.getName().endsWith(".jpg") || sourceFile.getName().endsWith(".png") || sourceFile.getName().endsWith(".gif")) {
					coverFile = sourceFile;
					break;
				} 
			}
		}else{
			logger.info("Could not find images folder in " + bookFolder);
		}
		return coverFile;
	}
	
	private void processBookFolder(ImportResult result, File bookFolder, File coverImage, String isbn, int volume, int numVolumes, String baseFilename){
		// Check to see if the file has already been processed
		logger.info("Processing folder " + bookFolder.getPath() );
		// Get the files within the zip files, should get an xml folder and an images folder
		File[] sourceFiles = bookFolder.listFiles();
		File xmlFolder = null;
		File imagesFolder = null;
		if (sourceFiles == null){
			logger.error("Folder " + bookFolder.getName() + " is invalid because there are no tiles in it.");
			return;
		}
		for (File sourceFile : sourceFiles) {
			if (sourceFile.isDirectory() && sourceFile.getName().equals("xml")){
				xmlFolder = sourceFile;
			} else if (sourceFile.isDirectory() && sourceFile.getName().equals("images")){
				imagesFolder = sourceFile;
			}
		}
		
		if (xmlFolder == null) {
			result.addNote("Folder " + bookFolder.getName() + " is invalid because there is no xml in it.");
			result.setEpubImported("failed");
			result.setPdfImported("failed");
			return;
		}
		if (imagesFolder == null) {
			result.addNote("Folder " + bookFolder.getName() + " is invalid because there is no images in it.");
			result.setEpubImported("failed");
			result.setPdfImported("failed");
			return;
		}
		if (coverImage == null) {
			result.addNote("Folder " + bookFolder.getName() + " did not have a cover image in it.");
			result.setCoverImported("failed");
		}
		
		String destination = tempDirectory + baseFilename + "/";
		File destinationFolder = new File(destination);
		try{
			if (destinationFolder.exists()){
				deleteFolder(destinationFolder);
			}
			destinationFolder.mkdirs();
			
			// Create mimetype file, must be first 
			try {
				File mimetypeFile = new File(destination + "mimetype");
				mimetypeFile.createNewFile();
				FileWriter mimetypeWriter = new FileWriter(mimetypeFile);
				mimetypeWriter.write("application/epub+zip");
				mimetypeWriter.flush();
				mimetypeWriter.close();
			} catch (IOException e2) {
				// TODO Auto-generated catch block
				e2.printStackTrace();
			}
			
			File[] xmlFileList = xmlFolder.listFiles();
	
			// Find the package file
			File packageFile = null;
			ArrayList<File> xmlFiles = new ArrayList<File>();
			for (File xmlFile : xmlFileList) {
				//Copy the file to the temp directory 
				File tmpXmlFile = new File(destination + xmlFile.getName());
				try {
					Util.copyFile(xmlFile, tmpXmlFile);
					if (tmpXmlFile.getName().matches(".*_package.xml")) {
						packageFile = tmpXmlFile;
					}else if (tmpXmlFile.getName().endsWith(".xml")) {
						//Process the file to remove the dtd to avoid validation errors.
						xmlFiles.add(tmpXmlFile);
						try {
							processXMLFile(tmpXmlFile, logger);
						} catch (IOException e) {
							logger.error("Error processing XML file", e);
						}
					}
				} catch (IOException e) {
					logger.error("Error copying temp file " + xmlFile.getName(), e);
				}
			}
			
			//Find any pdf files in the images folder
			File[] imageFileList = imagesFolder.listFiles();
			ArrayList<File> pdfFiles = new ArrayList<File>();
			ArrayList<File> imageFiles = new ArrayList<File>();
			for (File imageFile : imageFileList) {
				if (imageFile.getName().endsWith(".pdf")){
					//System.out.println("Source file " + tmpImageFile.getName() + " is a PDF.");
					pdfFiles.add(imageFile);
				}else{
					try {
						File tmpImageFile = new File(destination + imageFile.getName());
						Util.copyFile(imageFile, tmpImageFile);
						imageFiles.add(tmpImageFile);
					} catch (IOException e) {
						logger.error("Error copying temp file " + imageFile.getName(), e);
					}
				}
			}
			
			String recordsToAttachTo = getRecordsToAttachTo(result, baseFilename, isbn);
			if (recordsToAttachTo == null){
				return;
			}
			result.setRelatedRecord(recordsToAttachTo);
			createEPub(result, recordsToAttachTo, baseFilename, packageFile, xmlFiles, imageFiles, destination, coverImage, volume, numVolumes);
			createPDF(result, recordsToAttachTo, baseFilename, pdfFiles, coverImage, volume, numVolumes);
			if (result.getCoverImported().equals("success")){
				//Clear the book cover
				try {
					URL clearCoverUrl = new URL(bookcoverUrl + "/API/ItemAPI?method=clearBookCoverCacheById&id=" + recordsToAttachTo);
					logger.info("clearing cover " + clearCoverUrl.toString());
					clearCoverUrl.getContent();
				} catch (MalformedURLException e) {
					result.addNote("Could not clear book cover.");
				} catch (IOException e) {
					result.addNote("Could not clear book cover.");
				}
			}
		}finally{
			//Remove the temporary directory
			deleteFolder(destinationFolder);
		}
	}
	
	private void createEPub(ImportResult result, String recordsToAttachTo, String baseFilename, File packageFile, ArrayList<File> xmlFiles, ArrayList<File> imageFiles, String destination, File coverImage, int volume, int numVolumes){
		logger.info("Creating e-pub");
		String epubName = baseFilename + ".epub";
		File epubFile = new File(libraryDirectory + epubName);
		if (epubFile.exists()) {
			result.addNote("EPub File " + epubName + " has already been processed, not processing again.");
			result.setEpubImported("skipped");
			return;
		}
		if (packageFile == null) {
			result.addNote("Folder " + baseFilename + " is invalid because there is no package file in it.");
			return;
		}
		File destinationFolder = new File(destination);
		
		// Parse the package file for later use
		DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
		
		Document packageDoc;
		try {
			dbf.setValidating(false);
			dbf.setFeature("http://xml.org/sax/features/namespaces", false);
			dbf.setFeature("http://xml.org/sax/features/validation", false);
			dbf.setFeature("http://apache.org/xml/features/nonvalidating/load-dtd-grammar", false);
			dbf.setFeature("http://apache.org/xml/features/nonvalidating/load-external-dtd", false);
			
			DocumentBuilder db = dbf.newDocumentBuilder();
			packageDoc = db.parse(packageFile);
		} catch (Exception e) {
			result.addNote("Folder " + baseFilename + " is invalid because the package file could not be read.");
			return;
		}

		try {
			// Create content.opf file from the package file
			File contentFile = new File(destination + "content.opf");
			BufferedWriter contentWriter = new BufferedWriter(new OutputStreamWriter(new FileOutputStream(contentFile), "UTF8"));
			if (!writeContent(result, contentWriter, packageDoc, baseFilename)){
				return;
			}
			contentWriter.flush();
			contentWriter.close();

			// Create META-INF folder and container.xml file
			File metaFolder = new File(destination + "META-INF/");
			metaFolder.mkdir();
			File containerFile = new File(destination + "META-INF/container.xml");
			BufferedWriter containerWriter = new BufferedWriter(new OutputStreamWriter(new FileOutputStream(containerFile), "UTF8"));
			if (!writeContainer(result, containerWriter, packageDoc, baseFilename)){
				return;
			}
			containerWriter.flush();
			containerWriter.close();
			
			// Create table of contents toc.ncx file
			File tocFile = new File(destination + "toc.ncx");
			BufferedWriter tocWriter = new BufferedWriter(new OutputStreamWriter(new FileOutputStream(tocFile),"UTF8"));
			if (!writeToc(result, tocWriter, packageDoc, baseFilename)){
				return;
			}
			tocWriter.flush();
			tocWriter.close();
			
		} catch (IOException e) {
			result.addNote("Folder " + baseFilename + " is invalid because the files within the epub could not be created.");
			e.printStackTrace();
			return;
		}
		
		// Delete the package file
		packageFile.delete();
		
		// Create zip (epub) file with new contents
		createZipFile(epubFile, destinationFolder, logger);

		addFileToDatabase(result, recordsToAttachTo, coverImage, volume, numVolumes, epubName, "Gale Group", "epub", 0, "");
	}
	
	private void createPDF(ImportResult result, String recordsToAttachTo, String baseFilename, ArrayList<File> pdfFiles, File coverImage, int volume, int numVolumes){
		logger.info("Creating pdf");
		//Check to see if the PDF has already been created. 
		String pdfName = baseFilename + ".pdf";
		File pdfFile = new File(libraryDirectory + pdfName);
		if (pdfFile.exists()){
			logger.info("PDF File " + pdfName + " has already been processed, not processing again.");
			result.setPdfImported("skipped");
			return;
		}
		
		//Merge the pdf files that represent each page 
		logger.info("Merging " + pdfFiles.size() + " into a single pdf with the name " + baseFilename);
		PDFMergerUtility mergePdf = new PDFMergerUtility();
		for (File curPdf : pdfFiles){
			mergePdf.addSource(curPdf);
		}
		mergePdf.setDestinationFileName(libraryDirectory + pdfName);
		try {
			mergePdf.mergeDocuments();
		} catch (Error e1) {
			result.addNote("Combined pdf could not be made for the file.  " + e1.toString());
			e1.printStackTrace();
			return;
		} catch (Exception e1) {
			result.addNote("Combined pdf could not be made for the file.  " + e1.toString());
			e1.printStackTrace();
			return;
		}
		
		addFileToDatabase(result, recordsToAttachTo, coverImage, volume, numVolumes, pdfName, "Gale Group", "pdf", 0, "");
	}
	
	private void processXMLFile(File xmlFile, Logger logger) throws IOException{
		//Strip the dtd from the xml file. 
		String tempFilename = xmlFile.getName() + ".tmp";
		File tempFile = new File(tempDirectory + tempFilename);
		BufferedReader reader = new BufferedReader(new FileReader(xmlFile));
		BufferedWriter writer = new BufferedWriter(new FileWriter(tempFile));
		String curLine = reader.readLine();
		Pattern docTypeRegex = Pattern.compile("<!DOCTYPE(.*)?\"galeeBkdoc\\.dtd\"(.*)", Pattern.CANON_EQ);
		while (curLine != null){
			Matcher regexMatcher = docTypeRegex.matcher(curLine);
			if (regexMatcher.find()){
				//curLine = regexMatcher.group(1) + regexMatcher.group(2);
				curLine = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">";
			}
			//Replace lines that are gale tags
			if (curLine.matches("^</?gale:.*>$") || curLine.matches("<?xml version=\"1.0\"?>")){
				curLine = reader.readLine();
				continue;
			}
			//Replace gale attributes
			curLine = curLine.replaceAll("<gale:imgGroup>", "");
			curLine = curLine.replaceAll("gale:\\w*=\\\".*?\\\"", "");
			curLine = curLine.replaceAll("gale:\\w*\\s", "");
			//Get rid of any empty tags we may have created.
			curLine = curLine.replaceAll("< />", "");
			writer.write(curLine + "\r\n");
			curLine = reader.readLine();
		}
		writer.flush();
		writer.close();
		reader.close();
		
		//Delete the original file 
		if (!xmlFile.delete()){
			logger.error("Could not delete file " + xmlFile.getPath());
		}
		
		//Rename the new file to the original name
		if (!tempFile.renameTo(xmlFile)){
			logger.error("Could not rename file " + xmlFile.getPath());
		}
	}

	private void deleteFolder(File destinationFolder) {
		File[] childFiles = destinationFolder.listFiles();
		if (childFiles != null){
			for (File childFile : childFiles){
				if (childFile.isDirectory()){
					deleteFolder(childFile);
				}else{
					childFile.delete();
				}
			}
			destinationFolder.delete();
		}
	}

	private boolean writeContent(ImportResult result, BufferedWriter contentWriter, Document packageDoc, String bookName) {
		try {
			contentWriter.write("<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n");
			//Get the unique id of the package from the packageDocument
			NodeList packageNodes = packageDoc.getElementsByTagName("package");
			String uid;
			if (packageNodes.getLength() != 1){
				result.addNote("Folder " + bookName + " is invalid because the package file did not have a single package element.");
				return false;
			}else{
				Node packageNode = packageNodes.item(0);
				uid = packageNode.getAttributes().getNamedItem("unique-identifier").getTextContent();
			}
			contentWriter.write("<package version=\"2.0\" xmlns=\"http://www.idpf.org/2007/opf\" unique-identifier=\"" + uid + "\">\r\n");
			
			//Write the metadata
			contentWriter.write("<metadata xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:opf=\"http://www.idpf.org/2007/opf\">\r\n");
			NodeList metadataNodes = packageDoc.getElementsByTagName("dc-metadata");
			for (int i = 0; i < metadataNodes.getLength(); i++){
				Node metadataNode = metadataNodes.item(i);
				for (int j = 0; j < metadataNode.getChildNodes().getLength(); j++){
					Node metaItemNode = metadataNode.getChildNodes().item(j);
					writeNodeToFile(metaItemNode, contentWriter);
				}
			}
			contentWriter.write("</metadata>\r\n");
			
			//Write the manifest
			contentWriter.write("<manifest>\r\n");
			//Write all items from the original package
			NodeList manifestNodes = packageDoc.getElementsByTagName("manifest");
			for (int i = 0; i < manifestNodes.getLength(); i++){
				Node manifestNode = manifestNodes.item(i);
				for (int j = 0; j < manifestNode.getChildNodes().getLength(); j++){
					Node manifestItemNode = manifestNode.getChildNodes().item(j);
					writeNodeToFile(manifestItemNode, contentWriter);
				}
			}
			//Add the table of contents
			contentWriter.write("<item id=\"ncx\" href=\"toc.ncx\" media-type=\"application/x-dtbncx+xml\"/>\r\n");
			contentWriter.write("</manifest>\r\n");
				
			//Write the spine
			contentWriter.write("<spine toc=\"ncx\">\r\n");
			//Write all items from the original package
			NodeList spineNodes = packageDoc.getElementsByTagName("spine");
			for (int i = 0; i < spineNodes.getLength(); i++){
				Node spineNode = spineNodes.item(i);
				for (int j = 0; j < spineNode.getChildNodes().getLength(); j++){
					Node spineRefNode = spineNode.getChildNodes().item(j);
					writeNodeToFile(spineRefNode, contentWriter);
				}
			}
			contentWriter.write("</spine>\r\n");
			
			//Write the guide
			contentWriter.write("<guide>\r\n");
			//Write all items from the original package
			NodeList guideNodes = packageDoc.getElementsByTagName("guide");
			for (int i = 0; i < guideNodes.getLength(); i++){
				Node guideNode = guideNodes.item(i);
				for (int j = 0; j < guideNode.getChildNodes().getLength(); j++){
					Node guideRefNode = guideNode.getChildNodes().item(j);
					writeNodeToFile(guideRefNode, contentWriter);
				}
			}
			contentWriter.write("</guide>\r\n");
			
			contentWriter.write("</package>\r\n");
			return true;
		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
			return false;
		}
	}

	private void writeNodeToFile(Node node, BufferedWriter contentWriter) throws IOException {
		// TODO Auto-generated method stub
		boolean writeEndTag = true;
		if (node instanceof Element){
			Element metaItemElement = (Element)node;
			String tagName = metaItemElement.getTagName();
			if (tagName.startsWith("dc:")){
				tagName = tagName.toLowerCase();
			}
			contentWriter.write("<" + tagName);
			for (int k = 0; k < metaItemElement.getAttributes().getLength(); k++){
				Node attribute = metaItemElement.getAttributes().item(k);
				String attributeName = attribute.getNodeName();
				if (attributeName.startsWith("dc:")){
					attributeName = attributeName.toLowerCase();
				}
				if (attributeName.equals("scheme")){
					attributeName = "opf:scheme";
				}
				if (attributeName.startsWith("gale:")){
					continue;
				}
				String attributeValue = attribute.getNodeValue();
				if (attributeValue.equals("text/x-oeb1-document")){
					attributeValue = "application/xhtml+xml";
				}
				contentWriter.write(" " + attributeName + "=\"" + attributeValue + "\"");
			}
			
			
			if (metaItemElement.hasChildNodes()){
				contentWriter.write(">");
				//contentWriter.write("\r\n");
				for (int i = 0; i < metaItemElement.getChildNodes().getLength(); i++){
					Node childNode = metaItemElement.getChildNodes().item(i);
					if (childNode instanceof Element){
						writeNodeToFile(childNode, contentWriter);
					}else{
						contentWriter.write(childNode.getTextContent());
					}
				}
			}else{
				if (metaItemElement.getTextContent().length() != 0){
					contentWriter.write(">");
					contentWriter.write(metaItemElement.getTextContent());
				}else{
					writeEndTag = false;
					contentWriter.write("/>\r\n");
				}
			}
			if (writeEndTag){
				contentWriter.write("</" + tagName + ">\r\n");
			}
		}
	}

	private boolean writeContainer(ImportResult result, BufferedWriter containerWriter, Document packageDoc, String name) {
		try {
			containerWriter.write("<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\r\n");
			containerWriter.write("<container version=\"1.0\" xmlns=\"urn:oasis:names:tc:opendocument:xmlns:container\">\r\n");
			containerWriter.write("<rootfiles>\r\n");
			containerWriter.write("<rootfile full-path=\"content.opf\" media-type=\"application/oebps-package+xml\"/>\r\n");
			containerWriter.write("</rootfiles>\r\n");
			containerWriter.write("</container>\r\n");
			return true;
		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
			return false;
		}
	}

	private boolean writeToc(ImportResult result, BufferedWriter tocWriter, Document packageDoc, String bookName) {
		try {
			tocWriter.write("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n");
			tocWriter.write("<!DOCTYPE ncx PUBLIC \"-//NISO//DTD ncx 2005-1//EN\" \"http://www.daisy.org/z3986/2005/ncx-2005-1.dtd\">\r\n");
			tocWriter.write("<ncx version=\"2005-1\" xml:lang=\"en\" xmlns=\"http://www.daisy.org/z3986/2005/ncx/\">\r\n");
			tocWriter.write("<head>\r\n");
			//Get the unique id of the package from the packageDocument
			NodeList packageNodes = packageDoc.getElementsByTagName("package");
			String uid;
			if (packageNodes.getLength() != 1){
				result.addNote("Folder " + bookName + " is invalid because the package file did not have a single package element.");
				return false;
			}else{
				Node packageNode = packageNodes.item(0);
				uid = packageNode.getAttributes().getNamedItem("unique-identifier").getTextContent();
			}
			tocWriter.write("<meta name=\"dtb:uid\" content=\"" + uid + "\"/>\r\n");
			tocWriter.write("<meta name=\"dtb:depth\" content=\"1\"/>\r\n");
			tocWriter.write("<meta name=\"dtb:totalPageCount\" content=\"0\"/>\r\n");
			tocWriter.write("<meta name=\"dtb:maxPageNumber\" content=\"0\"/>\r\n");
			tocWriter.write("</head>\r\n");
			
			//Get the document title
			NodeList titleNodes = packageDoc.getElementsByTagName("dc:Title");
			String title;
			if (titleNodes.getLength() != 1){
				result.addNote("Folder " + bookName + " is invalid because the package file did not have a single title element.");
				return false;
			}else{
				Node titleNode = titleNodes.item(0);
				title = titleNode.getTextContent();
			}
			tocWriter.write("<docTitle>\r\n");
			tocWriter.write("<text>" + title + "</text>\r\n");
			tocWriter.write("</docTitle>\r\n");
			
			//Process the navMap
			tocWriter.write("<navMap>\r\n");
			//Get all reference elements from the guide
			NodeList referenceNodes = packageDoc.getElementsByTagName("reference");
			for (int i = 0; i < referenceNodes.getLength(); i++){
				Node referenceNode = referenceNodes.item(i);
				String type = referenceNode.getAttributes().getNamedItem("type").getTextContent();
				String refTitle = referenceNode.getAttributes().getNamedItem("title").getTextContent();
				String href = referenceNode.getAttributes().getNamedItem("href").getTextContent();
				tocWriter.write("<navPoint class=\"" + type + "\" id=\"item_" + (i+1) + "\" playOrder=\"" + (i + 1) + "\">\r\n");
				tocWriter.write("<navLabel><text>" + refTitle + "</text></navLabel>\r\n");
				tocWriter.write("<content src=\"" + href + "\"/>\r\n");
				tocWriter.write("</navPoint>\r\n");
			}	
			tocWriter.write("</navMap>\r\n");
			
			tocWriter.write("</ncx>\r\n");
			
			return true;
		} catch (IOException e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
			return false;
		}
	}
}
