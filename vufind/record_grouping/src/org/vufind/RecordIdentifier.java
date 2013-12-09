package org.vufind;

/**
 * Description goes here
 * Rampart Marc Conversion
 * User: Mark Noble
 * Date: 10/18/13
 * Time: 10:27 AM
 */
public class RecordIdentifier {
	private String type;
	private String identifier;


	@Override
	public int hashCode() {
		return toString().hashCode();
	}

	private String myString = null;
	public String toString(){
		if (myString == null){
			myString = type + ":" + identifier;
		}
		return myString;
	}

	@Override
	public boolean equals(Object obj) {
		if (obj instanceof  RecordIdentifier){
			RecordIdentifier tmpObj = (RecordIdentifier)obj;
			return (tmpObj.type.equals(type) && tmpObj.identifier.equals(identifier));
		}else{
			return false;
		}
	}

	public String getType() {
		return type;
	}

	public boolean isValid() {
		if (type.equals("isbn") || type.equals("upc")){
			return type.matches("^\\d+$");
		}else{
			return identifier.length() > 0;
		}
	}

	public String getIdentifier() {
		return identifier;
	}

	public void setValue(String type, String identifier) {
		this.type = type.toLowerCase();
		if (this.type.equals("isbn") || this.type.equals("upc")){
			identifier = identifier.replaceAll("\\D", "");
		}
		this.identifier = identifier;
	}
}
