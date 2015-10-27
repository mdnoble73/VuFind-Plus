package org.vufind;

/**
 * Accelerated Reader information for a title
 * Pika
 * User: Mark Noble
 * Date: 10/21/2015
 * Time: 5:11 PM
 */
public class ARTitle {
	private String title;
	private String author;
	private String bookLevel;
	private String arPoints;
	private String interestLevel;

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

	public String getBookLevel() {
		return bookLevel;
	}

	public void setBookLevel(String bookLevel) {
		this.bookLevel = bookLevel;
	}

	public String getArPoints() {
		return arPoints;
	}

	public void setArPoints(String arPoints) {
		this.arPoints = arPoints;
	}

	public String getInterestLevel() {
		return interestLevel;
	}

	public void setInterestLevel(String interestLevel) {
		this.interestLevel = interestLevel;
	}
}
