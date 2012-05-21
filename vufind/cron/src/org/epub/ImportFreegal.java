package org.epub;

import java.io.InputStream;
import java.io.StringReader;
import java.net.URL;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.Date;
import java.util.HashMap;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;
import org.vufind.Base64Coder;
import org.vufind.CronLogEntry;
import org.vufind.CronProcessLogEntry;
import org.vufind.IProcessHandler;
import org.vufind.Util;
import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;

import org.apache.commons.io.IOUtils;

public class ImportFreegal implements IProcessHandler {
	private String freegalUrl;
	private String freegalUser;
	private String freegalPIN;
	private String freegalAPIkey;
	private String freegalLibrary;
	private String vufindUrl;
	
	@Override
	public void doCronProcess(String servername, Ini configIni, Section processSettings, Connection vufindConn, Connection econtentConn, CronLogEntry cronEntry, Logger logger) {
		CronProcessLogEntry processLog = new CronProcessLogEntry(cronEntry.getLogEntryId(), "Import Freegal Content");
		processLog.saveToDatabase(vufindConn, logger);
		logger.info("Importing Freegal content.");
		
		//Load configuration
		if (!loadConfig(configIni, processSettings, logger)){
			return;
		}
		
		try {
			//Connect to the eContent database
			PreparedStatement getEContentRecord = econtentConn.prepareStatement("SELECT id FROM econtent_record WHERE title = ? AND author = ?");
			PreparedStatement addAlbumToDatabase = econtentConn.prepareStatement("INSERT INTO econtent_record (title, author, author2, accessType, availableCopies, contents, language, genre, source, collection, date_added, addedBy, cover) VALUES (?, ?, ?, 'free', 1, ?, ?, ?, 'Freegal', ?, ?, ?, ?)", PreparedStatement.RETURN_GENERATED_KEYS);
			PreparedStatement updateAlbumInDatabase = econtentConn.prepareStatement("UPDATE econtent_record SET title = ?, author = ?, author2 = ?, contents = ?, language = ?, genre = ?, collection = ?, date_updated = ?, cover = ? WHERE id = ?");
			PreparedStatement removeSongsForAlbum = econtentConn.prepareStatement("DELETE FROM econtent_item WHERE recordId = ?");
			PreparedStatement addSongToDatabase = econtentConn.prepareStatement("INSERT INTO econtent_item (recordId, link, item_type, notes, addedBy, date_added, date_updated) VALUES (?, ?, 'externalMP3', ?, ?, ?, ?)");
		
			//Get a list of all genres in the freegal site
			String genreUrl = freegalUrl + "/services/genre/" + freegalAPIkey + "/" + freegalLibrary + "/" + freegalUser;
			logger.info("Genre url: " + genreUrl);
			URL genreURL = new URL(genreUrl);
			Object genreContent = genreURL.getContent();
			
			DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
			
			Document genreDoc;
			DocumentBuilder db = dbf.newDocumentBuilder();
			InputStream genreContentStream = (InputStream)genreContent;
			String genreContentString = IOUtils.toString(genreContentStream, "UTF-8");
			//logger.info(genreContentString);
			InputSource genreSource = new InputSource(new StringReader(genreContentString));
			genreDoc = db.parse(genreSource);
			NodeList genres = genreDoc.getElementsByTagName("Genre");
			for(int i = 0; i < genres.getLength(); i++){
				Node genreNode = genres.item(i);
				String genre = genreNode.getTextContent();
				genre = genre.trim();
				if (genre.length() == 0){
					continue;
				}
				logger.info("procesing genre " + genre);
				processLog.addNote("procesing genre " + genre);
				processLog.saveToDatabase(vufindConn, logger);
				//For each genre, load a list of songs. 
				String base64Genre = Base64Coder.encodeString(genre);
				String songUrl = freegalUrl + "/services/genre/" + freegalAPIkey+ "/" + freegalLibrary + "/" + freegalUser + "/" + freegalPIN + "/" + base64Genre;
				logger.info("Song url: " + songUrl);
				Document songsDoc;
				DocumentBuilder songsDB = dbf.newDocumentBuilder();
				songsDoc = songsDB.parse(songUrl);
				NodeList songs = songsDoc.getElementsByTagName("Song");
				logger.info("Found " + songs.getLength() + " songs for genre " + genre);
				
				//Group the songs by album
				HashMap<String, Album> albums = new HashMap<String, Album>();
				for (int j = 0; j < songs.getLength(); j++){
					Element songNode = (Element)songs.item(j);
					Album album = new Album();
					album.setTitle(songNode.getElementsByTagName("Title").item(0).getTextContent());
					album.setAuthor(songNode.getElementsByTagName("ArtistText").item(0).getTextContent());
					
					if (!albums.containsKey(album.toString())){
						logger.info("Found new album " + album.toString());
						getEContentRecord.setString(1, album.getTitle());
						getEContentRecord.setString(2, album.getAuthor());
						ResultSet eContentRecordResults = getEContentRecord.executeQuery();
						if (eContentRecordResults.next()){
							//The album already exists in the database
							album.setRecordId(eContentRecordResults.getLong("id"));
						}
						album.setGenre(genre);
						album.setCoverUrl(songNode.getElementsByTagName("Album_Artwork").item(0).getTextContent());
					}else{
						album = albums.get(album.toString());
					}
					albums.put(album.toString(), album);
					//Add the song to the album
					Song song = new Song();
					song.setTitle(songNode.getElementsByTagName("SongTitle").item(0).getTextContent());
					song.setArtist(songNode.getElementsByTagName("Artist").item(0).getTextContent());
					song.setComposer(songNode.getElementsByTagName("Composer").item(0).getTextContent());
					String freegalUrl = songNode.getElementsByTagName("freegal_url").item(0).getTextContent();
					freegalUrl = freegalUrl.replaceAll("/" + freegalUser + "/", "/{patronBarcode}/");
					freegalUrl = freegalUrl.replaceAll("/" + freegalPIN + "/", "/{patronPin}/");
					song.setDownloadUrl(freegalUrl);
					album.getSongs().add(song);
				}
				
				//Process each album that has been loaded
				for (Album album : albums.values()){
					try {
						//Check to see if the cover is already downloaded and if not, download it.
						logger.info("Processing album " + album.getTitle() + " the album has " + album.getSongs().size() + " songs");
						if (album.getRecordId() == -1){
							//Add the record to the database
							addAlbumToDatabase.setString(1, album.getTitle());
							addAlbumToDatabase.setString(2, album.getAuthor());
							addAlbumToDatabase.setString(3, album.getAuthor2());
							addAlbumToDatabase.setString(4, album.getContents());
							addAlbumToDatabase.setString(5, album.getLanguage());
							addAlbumToDatabase.setString(6, album.getGenre());
							addAlbumToDatabase.setString(7, album.getCollection());
							addAlbumToDatabase.setInt(8, (int)(new Date().getTime()/100));
							addAlbumToDatabase.setInt(9, -1);
							addAlbumToDatabase.setString(10, album.getCoverUrl());
							addAlbumToDatabase.executeUpdate();
							ResultSet generatedKeys = addAlbumToDatabase.getGeneratedKeys();
							if (generatedKeys.next()){
								album.setRecordId(generatedKeys.getLong(1));
							}
						}else{
							//Update the record in the database
							updateAlbumInDatabase.setString(1, album.getTitle());
							updateAlbumInDatabase.setString(2, album.getAuthor());
							updateAlbumInDatabase.setString(3, album.getAuthor2());
							updateAlbumInDatabase.setString(4, album.getContents());
							updateAlbumInDatabase.setString(5, album.getLanguage());
							updateAlbumInDatabase.setString(6, album.getGenre());
							updateAlbumInDatabase.setString(7, album.getCollection());
							updateAlbumInDatabase.setInt(8, (int)(new Date().getTime()/100));
							updateAlbumInDatabase.setString(9, album.getCoverUrl());
							updateAlbumInDatabase.setInt(10, (int)album.getRecordId());
							updateAlbumInDatabase.executeUpdate();
							
							//Remove all existing songs for the album from the database since freegal doesn't keep unique ids
							removeSongsForAlbum.setLong(1, album.getRecordId());
							removeSongsForAlbum.executeUpdate();
							
						}
						
						//Add songs to the database
						for (Song song : album.getSongs()){
							addSongToDatabase.setLong(1, album.getRecordId());
							addSongToDatabase.setString(2, song.getDownloadUrl());
							String songNotes = song.getTitle();
							if (!song.getArtist().equals(album.getAuthor())){
								songNotes += " -- " + song.getArtist();
							}
							addSongToDatabase.setString(3, songNotes );
							addSongToDatabase.setInt(4, -1); 
							addSongToDatabase.setInt(5, (int)(new Date().getTime()/100));
							addSongToDatabase.setInt(6, (int)(new Date().getTime()/100));
							addSongToDatabase.execute();
						}
						
						//Reindex the record
						URL reindexURL = new URL (vufindUrl + "/EContentRecord/" + album.getRecordId() + "/Reindex?quick");
						Object reindexResult = reindexURL.getContent();
						logger.info("Record ID : " + album.getRecordId() + " Reindex result: " + Util.convertStreamToString((InputStream)reindexResult));
						
						processLog.incUpdated();
					} catch (Exception e) {
						logger.error("Error adding album to database, skipping.");
						processLog.incErrors();
						processLog.addNote("Error adding album to database, skipping " + e.toString());
						processLog.saveToDatabase(vufindConn, logger);
					}
					
				}
			}
			
		} catch (Exception ex) {
			// handle any errors
			logger.error("Error loading content from Freegal. ", ex);
			return;
		} finally {
			processLog.setFinished();
			processLog.saveToDatabase(vufindConn, logger);
		}

	}

	private boolean loadConfig(Ini configIni, Section processSettings, Logger logger) {
		freegalUrl = processSettings.get("freegalUrl");
		if (freegalUrl == null || freegalUrl.length() == 0) {
			logger.error("Freegal API URL not found in Process Settings.  Please specify url in freegalUrl key.");
			return false;
		}
		
		freegalUser = processSettings.get("freegalUser");
		if (freegalUser == null || freegalUser.length() == 0) {
			logger.error("Freegal User not found in Process Settings.  Please specify the barcode of a patron to use while loading freegal information in the freegalUser key.");
			return false;
		}
		
		freegalPIN = processSettings.get("freegalPIN");
		if (freegalPIN == null || freegalPIN.length() == 0) {
			logger.error("Freegal PIN not found in Process Settings.  Please specify the PIN of a patron to use while loading freegal information in the freegalPIN key.");
			return false;
		}
		freegalAPIkey = processSettings.get("freegalAPIkey");
		if (freegalAPIkey == null || freegalAPIkey.length() == 0) {
			logger.error("Freegal API Key not found in Process Settings.  Please specify the API Key for the Freegal webservices the freegalAPIkey key.");
			return false;
		}
		freegalLibrary = processSettings.get("freegalLibrary");
		if (freegalLibrary == null || freegalLibrary.length() == 0) {
			logger.error("Freegal Library Id not found in Process Settings.  Please specify the Library for the Freegal webservices the freegalLibrary key.");
			return false;
		}
		vufindUrl = configIni.get("Site", "url");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
			return false;
		}
		
		return true;
	}
}
