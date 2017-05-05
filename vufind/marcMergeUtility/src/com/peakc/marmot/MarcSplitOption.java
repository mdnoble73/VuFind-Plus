package com.peakc.marmot;

import org.marc4j.MarcStreamWriter;
import org.marc4j.MarcWriter;
import org.marc4j.marc.ControlField;
import org.marc4j.marc.DataField;
import org.marc4j.marc.Record;
import org.marc4j.marc.Subfield;
import org.marc4j.marc.impl.RecordImpl;

import java.io.File;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.util.List;
import java.util.regex.Pattern;

/**
 * Information about how to split a MARC record
 * VuFind-Plus
 * User: Mark Noble
 * Date: 11/21/2014
 * Time: 6:18 PM
 */
public class MarcSplitOption {
	private String filename;
	private MarcWriter marcWriter;
	private String locationsToInclude;
	private Pattern locationsToIncludePattern;
	private String itemTag;
	private char locationSubfield;

	public String getFilename() {
		return filename;
	}

	public void setFilename(String basePath, String filename) throws FileNotFoundException {
		this.filename = filename;
		if (basePath.endsWith("/")) basePath.substring(0, basePath.length() -1);
		File basePathFile = new File(basePath);
		if (!basePathFile.exists()){
			basePathFile.mkdirs();
		}
		marcWriter = new MarcStreamWriter(new FileOutputStream(basePath + "/" + filename));
	}

	public String getLocationsToInclude() {
		return locationsToInclude;
	}

	public void setLocationsToInclude(String locationsToInclude) {
		this.locationsToInclude = locationsToInclude;
		locationsToIncludePattern = Pattern.compile(locationsToInclude);
	}

	public void setItemTag(String itemTag) {
		this.itemTag = itemTag;
	}

	public String getItemTag() {
		return itemTag;
	}

	public void setLocationSubfield(char locationSubfield) {
		this.locationSubfield = locationSubfield;
	}

	public char getLocationSubfield() {
		return locationSubfield;
	}

	public void close() {
		marcWriter.close();
	}

	public void processRecord(Record curBib) {
		//Check to see if the bib is valid for this splitter
		List<DataField> itemFields = (List<DataField>)curBib.getVariableFields(itemTag);
		boolean validBib = false;
		for (DataField curItem : itemFields){
			Subfield locationSubfieldInst = curItem.getSubfield(locationSubfield);
			if (locationSubfieldInst != null){
				String locationCode = locationSubfieldInst.getData().trim();
				if (locationsToIncludePattern.matcher(locationCode).matches()){
					validBib = true;
					break;
				}
			}
		}

		if (validBib) {
			//if we have a valid bib, make a copy and write it to the split marc
			Record marcCopy = new RecordImpl();
			marcCopy.setLeader(curBib.getLeader());
			for (ControlField curField : (List<ControlField>) curBib.getControlFields()) {
				marcCopy.addVariableField(curField);
			}
			for (DataField curField : (List<DataField>) curBib.getDataFields()) {
				boolean addField = true;
				if (curField.getTag().equals(itemTag)) {
					Subfield locationSubfieldInst = curField.getSubfield(locationSubfield);
					if (locationSubfieldInst != null) {
						String locationCode = locationSubfieldInst.getData();
						if (locationsToIncludePattern.matcher(locationCode).matches()) {
							addField = true;
						}else{
							addField = false;
						}
					}
				} else {
					addField = true;
				}
				if (addField) {
					marcCopy.addVariableField(curField);
				}
			}
			marcWriter.write(marcCopy);
		}
	}
}
