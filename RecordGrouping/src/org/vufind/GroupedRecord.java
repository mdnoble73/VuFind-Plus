package org.vufind;

import java.math.BigInteger;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.HashMap;

/**
 * Description goes here
 * RecordGrouping
 * User: Mark Noble
 * Date: 11/15/13
 * Time: 9:02 AM
 */
public class GroupedRecord {
	public String title = "";              //Up to 100 chars
	public String author = "";             //Up to 50  chars
	public String subtitle = "";           //Up to 175 chars
	public String groupingCategory = "";   //Up to 25  chars
	public long id;

	private static HashMap<String, String> formatsToGroupingCategory = new HashMap<String, String>();
	static {
		formatsToGroupingCategory.put("Atlas", "other");
		formatsToGroupingCategory.put("Map", "other");
		formatsToGroupingCategory.put("TapeCartridge", "other");
		formatsToGroupingCategory.put("ChipCartridge", "other");
		formatsToGroupingCategory.put("DiscCartridge", "other");
		formatsToGroupingCategory.put("TapeCassette", "other");
		formatsToGroupingCategory.put("TapeReel", "other");
		formatsToGroupingCategory.put("FloppyDisk", "other");
		formatsToGroupingCategory.put("CDROM", "other");
		formatsToGroupingCategory.put("Software", "other");
		formatsToGroupingCategory.put("Globe", "other");
		formatsToGroupingCategory.put("Braille", "book");
		formatsToGroupingCategory.put("Filmstrip", "movie");
		formatsToGroupingCategory.put("Transparency", "other");
		formatsToGroupingCategory.put("Slide", "other");
		formatsToGroupingCategory.put("Microfilm", "other");
		formatsToGroupingCategory.put("Collage", "other");
		formatsToGroupingCategory.put("Drawing", "other");
		formatsToGroupingCategory.put("Painting", "other");
		formatsToGroupingCategory.put("Print", "other");
		formatsToGroupingCategory.put("Photonegative", "other");
		formatsToGroupingCategory.put("FlashCard", "other");
		formatsToGroupingCategory.put("Chart", "other");
		formatsToGroupingCategory.put("Photo", "other");
		formatsToGroupingCategory.put("MotionPicture", "movie");
		formatsToGroupingCategory.put("Kit", "other");
		formatsToGroupingCategory.put("MusicalScore", "book");
		formatsToGroupingCategory.put("SensorImage", "other");
		formatsToGroupingCategory.put("SoundDisc", "audio");
		formatsToGroupingCategory.put("SoundCassette", "audio");
		formatsToGroupingCategory.put("SoundRecording", "audio");
		formatsToGroupingCategory.put("VideoCartridge", "movie");
		formatsToGroupingCategory.put("VideoDisc", "movie");
		formatsToGroupingCategory.put("VideoCassette", "movie");
		formatsToGroupingCategory.put("VideoReel", "movie");
		formatsToGroupingCategory.put("Video", "movie");
		formatsToGroupingCategory.put("MusicalScore", "book");
		formatsToGroupingCategory.put("MusicRecording", "music");
		formatsToGroupingCategory.put("Electronic", "other");
		formatsToGroupingCategory.put("PhysicalObject", "other");
		formatsToGroupingCategory.put("Manuscript", "book");
		formatsToGroupingCategory.put("eBook", "ebook");
		formatsToGroupingCategory.put("Book", "book");
		formatsToGroupingCategory.put("Newspaper", "book");
		formatsToGroupingCategory.put("Journal", "book");
		formatsToGroupingCategory.put("Serial", "book");
		formatsToGroupingCategory.put("Unknown", "other");
		formatsToGroupingCategory.put("Playaway", "audio");
		formatsToGroupingCategory.put("LargePrint", "book");
		formatsToGroupingCategory.put("Blu-ray", "movie");
		formatsToGroupingCategory.put("DVD", "movie");
		formatsToGroupingCategory.put("VerticalFile", "other");
		formatsToGroupingCategory.put("CompactDisc", "audio");
		formatsToGroupingCategory.put("TapeRecording", "audio");
		formatsToGroupingCategory.put("Phonograph", "audio");
		formatsToGroupingCategory.put("pdf", "ebook");
		formatsToGroupingCategory.put("epub", "ebook");
		formatsToGroupingCategory.put("jpg", "other");
		formatsToGroupingCategory.put("gif", "other");
		formatsToGroupingCategory.put("mp3", "audio");
		formatsToGroupingCategory.put("plucker", "ebook");
		formatsToGroupingCategory.put("kindle", "ebook");
		formatsToGroupingCategory.put("externalLink", "ebook");
		formatsToGroupingCategory.put("externalMP3", "audio");
		formatsToGroupingCategory.put("interactiveBook", "ebook");
		formatsToGroupingCategory.put("overdrive", "ebook");
		formatsToGroupingCategory.put("external_web", "ebook");
		formatsToGroupingCategory.put("external_ebook", "ebook");
		formatsToGroupingCategory.put("external_eaudio", "audio");
		formatsToGroupingCategory.put("external_emusic", "music");
		formatsToGroupingCategory.put("external_evideo", "movie");
		formatsToGroupingCategory.put("text", "ebook");
		formatsToGroupingCategory.put("gifs", "other");
		formatsToGroupingCategory.put("itunes", "audio");
		formatsToGroupingCategory.put("Adobe_EPUB_eBook", "ebook");
		formatsToGroupingCategory.put("Kindle_Book", "ebook");
		formatsToGroupingCategory.put("Microsoft_eBook", "ebook");
		formatsToGroupingCategory.put("OverDrive_WMA_Audiobook", "audio");
		formatsToGroupingCategory.put("OverDrive_MP3_Audiobook", "audio");
		formatsToGroupingCategory.put("OverDrive_Music", "music");
		formatsToGroupingCategory.put("OverDrive_Video", "movie");
		formatsToGroupingCategory.put("OverDrive_Read", "ebook");
		formatsToGroupingCategory.put("Adobe_PDF_eBook", "ebook");
		formatsToGroupingCategory.put("Palm", "ebook");
		formatsToGroupingCategory.put("Mobipocket_eBook", "ebook");
		formatsToGroupingCategory.put("Disney_Online_Book", "ebook");
		formatsToGroupingCategory.put("Open_PDF_eBook", "ebook");
		formatsToGroupingCategory.put("Open_EPUB_eBook", "ebook");
		formatsToGroupingCategory.put("SeedPacket", "other");
	}

	public GroupedRecord(NormalizedRecord normalizedRecord){
		this.title = normalizedRecord.title;
		this.author = normalizedRecord.author;
		this.subtitle = normalizedRecord.subtitle;
		groupingCategory = formatsToGroupingCategory.get(normalizedRecord.format);
	}

	public String getPermanentId() {
		String permanentId = null;
		try {
			MessageDigest idGenerator = MessageDigest.getInstance("MD5");
			if (title.equals("")){
				idGenerator.update("--null--".getBytes());
			}else{
				idGenerator.update(title.getBytes());
			}
			if (subtitle.equals("")){
				idGenerator.update("--null--".getBytes());
			}else{
				idGenerator.update(subtitle.getBytes());
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
			StringBuffer formattedId = new StringBuffer();
			formattedId.append(permanentId.substring(0, 8))
					.append("-")
					.append(permanentId.substring(8,12))
					.append("-")
					.append(permanentId.substring(12,16))
					.append("-")
					.append(permanentId.substring(16,20))
					.append("-")
					.append(permanentId.substring(20));
			permanentId = formattedId.toString();
		} catch (NoSuchAlgorithmException e) {
			System.out.println("Error generating permanent id" + e.toString());
		}
		//System.out.println("Permanent Id is " + permanentId);
		return permanentId;
	}
}
