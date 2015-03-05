package org.vufind;

import org.apache.log4j.Logger;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.impl.*;

import java.io.BufferedReader;
import java.io.File;
import java.io.FileReader;
import java.io.IOException;

/**
 * Utility class to read eVoke MARC records which are in JSON, but are not a standard format.
 *
 * VuFind-Plus
 * User: Mark Noble
 * Date: 9/24/2014
 * Time: 12:37 PM
 */
public class EVokeMarcReader {
	private static Logger logger	= Logger.getLogger(EVokeMarcReader.class);
	public static Record readMarc(File curBibFile) {
		String fileContents = readFile(curBibFile);
		if (fileContents == null){
			return null;
		}else{
			try {
				if (fileContents.equals("null")){
					return null;
				}
				JSONObject rawObject = new JSONObject(fileContents);
				Record marc = new RecordImpl();
				//Load the leader
				if (rawObject.has("leader")) {
					marc.setLeader(new LeaderImpl(rawObject.getString("leader")));
				}
				//Load data fields
				if (rawObject.has("datafield")){
					JSONArray dataFields = rawObject.getJSONArray("datafield");
					for (int i = 0; i < dataFields.length(); i++){
						JSONObject dataFieldJson = dataFields.getJSONObject(i);
						DataField dataField = new DataFieldImpl();
						dataField.setTag(dataFieldJson.getString("@tag"));
						if (dataFieldJson.has("subfield")){
							Object subfields = dataFieldJson.get("subfield");
							if (subfields instanceof JSONArray){
								JSONArray subfieldsJSON = (JSONArray)subfields;
								for (int j = 0; j < subfieldsJSON.length(); j++){
									JSONObject subfieldJSON = subfieldsJSON.getJSONObject(j);
									loadSubfieldData(subfieldJSON, dataField);
								}
							} else{
								JSONObject subfieldJSON = (JSONObject)subfields;
								loadSubfieldData(subfieldJSON, dataField);
							}
						}
						marc.addVariableField(dataField);
					}
				}
				//Load control fields
				if (rawObject.has("controlfield")){
					JSONArray controlFields = rawObject.getJSONArray("controlfield");
					for (int i = 0; i < controlFields.length(); i++){
						JSONObject dataFieldJson = controlFields.getJSONObject(i);
						ControlField controlField = new ControlFieldImpl();
						controlField.setTag(dataFieldJson.getString("@tag"));
						controlField.setData(dataFieldJson.getString("$"));
						marc.addVariableField(controlField);
					}
				}
				return marc;
			}catch (JSONException e){
				logger.error("Error loading eVoke MARC record from JSON", e);
				return null;
			}
		}
	}

	private static void loadSubfieldData(JSONObject subfieldJSON, DataField dataField) throws JSONException {
		String code = subfieldJSON.getString("@code");
		String value = subfieldJSON.getString("$");
		Subfield subfield = new SubfieldImpl();
		subfield.setCode(code.charAt(0));
		subfield.setData(value);
		dataField.addSubfield(subfield);
	}

	private static String readFile(File curBibFile) {
		try {
			BufferedReader fileReader = new BufferedReader(new FileReader(curBibFile));
			StringBuilder fullContents = new StringBuilder();
			String curLine = fileReader.readLine();
			while (curLine != null) {
				fullContents.append(curLine);
				curLine = fileReader.readLine();
			}
			fileReader.close();
			return fullContents.toString();
		}catch(IOException e){
			logger.error("Unable to read " + curBibFile.toString(), e);
			return null;
		}
	}

}
