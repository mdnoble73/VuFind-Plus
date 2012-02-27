package org.epub;

import java.util.ArrayList;
import java.util.HashSet;

public class Album {
	private long recordId = -1;
	private String title;
	private String author;
	private String genre;
	private String coverUrl;
	private ArrayList<Song> songs = new ArrayList<Song>();
	public long getRecordId() {
		return recordId;
	}
	public void setRecordId(long recordId) {
		this.recordId = recordId;
	}
	public String getTitle() {
		return title;
	}
	public void setTitle(String title) {
		this.title = title;
	}
	public String getAuthor() {
		return author;
	}
	public void setAuthor(String author) {
		this.author = author;
	}
	public String getAuthor2() {
		HashSet<String> allAuthors =  new HashSet<String>();
		for (Song song : songs){
			String[] songArtists = song.getArtist().split(",");
			for (String artist : songArtists){
				allAuthors.add(artist);
			}
			String[] songComposers = song.getComposer().split(",");
			for (String composer : songComposers){
				allAuthors.add(composer);
			}
		}
		//Create a carriage return separated list of unique authors and contributors
		StringBuffer author2 = new StringBuffer();
		for (String tmpAuthor : allAuthors){
			if (!tmpAuthor.equalsIgnoreCase(this.author)){
				author2.append(tmpAuthor + "\r\n");
			}
		}
		return author2.toString();
	}
	public String getContents() {
		StringBuffer contents = new StringBuffer();
		for (Song song : songs){
			contents.append(song.getTitle() + " -- " + song.getArtist() + "\r\n");
		}
		return contents.toString();
	}
	public String getLanguage() {
		//TODO: This should be loaded based on genre
		return "English";
	}
	public String getGenre() {
		return genre;
	}
	public void setGenre(String genre) {
		this.genre = genre;
	}
	public String getCollection() {
		//TODO: This should be loaded based on genre
		if (genre.equals("Children's") || genre.equals("Children's Music")){
			return "Juv. emusic";
		}
		return "Adult emusic";
	}
	public ArrayList<Song> getSongs() {
		return songs;
	}
	public void setSongs(ArrayList<Song> songs) {
		this.songs = songs;
	}
	public String toString(){
		return title + " - " + author;
	}
	public void setCoverUrl(String coverUrl) {
		this.coverUrl = coverUrl;
	}
	public String getCoverUrl() {
		return coverUrl;
	}
}
