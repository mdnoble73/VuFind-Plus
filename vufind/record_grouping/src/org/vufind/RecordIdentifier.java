package org.vufind;

/**
 * Description goes here
 * Rampart Marc Conversion
 * User: Mark Noble
 * Date: 10/18/13
 * Time: 10:27 AM
 */
public class RecordIdentifier {
	public String type;
	public String identifier;


	@Override
	public int hashCode() {
		return toString().hashCode();
	}

	private String myString = null;
	public String toString(){
		if (myString == null){
			myString = new StringBuilder(type + ":" + identifier).toString();
		}
		return myString;
	}

	@Override
	public boolean equals(Object obj) {
		RecordIdentifier tmpObj = (RecordIdentifier)obj;
		return (tmpObj.type.equals(type) && tmpObj.identifier.equals(identifier));
	}
}
