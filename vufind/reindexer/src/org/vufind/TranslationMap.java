package org.vufind;

import org.apache.log4j.Logger;

import java.util.HashMap;
import java.util.HashSet;
import java.util.LinkedHashSet;
import java.util.Set;

/**
 * A translation map to translate values
 *
 * Pika
 * User: Mark Noble
 * Date: 7/9/2015
 * Time: 10:43 PM
 */
public class TranslationMap {
	private Logger logger;
	private String profileName;
	private String mapName;
	private boolean fullReindex;
	private HashMap<String, String> translationValues = new HashMap<>();

	public TranslationMap(String profileName, String mapName, boolean fullReindex, Logger logger){
		this.profileName = profileName;
		this.mapName = mapName;
		this.fullReindex = fullReindex;
		this. logger = logger;
	}

	HashSet<String> unableToTranslateWarnings = new HashSet<>();
	public String translateValue(String value){
		String translatedValue;
		String lowerCaseValue = value.toLowerCase();
		if (translationValues.containsKey(lowerCaseValue)){
			translatedValue = translationValues.get(lowerCaseValue);
		}else{


			if (translationValues.containsKey("*")){
				translatedValue = translationValues.get("*");
			}else{
				String concatenatedValue = mapName + ":" + value;
				if (!unableToTranslateWarnings.contains(concatenatedValue)){
					if (fullReindex) {
						logger.warn("Could not translate '" + concatenatedValue + "' in profile " + profileName);
					}
					unableToTranslateWarnings.add(concatenatedValue);
				}
				translatedValue = value;
			}
		}

		if (translatedValue != null){
			if (translatedValue.equals("nomap")){
				translatedValue = value;
			}else {
				translatedValue = translatedValue.trim();
				if (translatedValue.length() == 0) {
					translatedValue = null;
				}
			}
		}
		return translatedValue;
	}

	public LinkedHashSet<String> translateCollection(Set<String> values) {
		LinkedHashSet<String> translatedCollection = new LinkedHashSet<>();
		for (String value : values){
			String translatedValue = translateValue(value);
			if (translatedValue != null) {
				translatedCollection.add(translatedValue);
			}
		}
		return  translatedCollection;
	}

	public String getMapName() {
		return mapName;
	}

	public void addValue(String value, String translation) {
		translationValues.put(value.toLowerCase(), translation);
	}
}
