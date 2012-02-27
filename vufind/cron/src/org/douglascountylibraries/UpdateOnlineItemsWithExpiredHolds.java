package org.douglascountylibraries;

import java.io.IOException;
import java.io.InputStream;
import java.net.MalformedURLException;
import java.net.URL;
import java.util.Iterator;

import org.apache.log4j.Logger;
import org.ini4j.Profile.Section;
import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;
import org.vufind.IProcessHandler;
import org.vufind.Util;

public class UpdateOnlineItemsWithExpiredHolds implements IProcessHandler {
	private Logger logger;
	private String vufindUrl;
	
	@Override
	public void doCronProcess(Section processSettings, Section generalSettings, Logger logger) {
		this.logger = logger;
		logger.info("Returning online items with expired holds");

		vufindUrl = generalSettings.get("vufindUrl");
		if (vufindUrl == null || vufindUrl.length() == 0) {
			logger.error("Unable to get URL for VuFind in General settings.  Please add a vufindUrl key.");
		}
		
		//Get a list of all items that need to be returned.  Utilize the API to reduce code redundancies. 
		try {
			URL itemApiUrl = new URL(vufindUrl + "API/ItemAPI?method=getOnlineItemsToReturn");
			Object onlineItemDataRaw = itemApiUrl.getContent();
			if (onlineItemDataRaw instanceof InputStream) {
				String onlineItemJson = Util.convertStreamToString((InputStream) onlineItemDataRaw);
				logger.info("Json for expired online items " + onlineItemJson);
				try {
					JSONObject onlineItemData = new JSONObject(onlineItemJson);
					JSONArray onlineItemsWithExpiredHolds = onlineItemData.getJSONArray("result");
					for (int i = 0; i < onlineItemsWithExpiredHolds.length(); i++){
						JSONObject expiredItem = onlineItemsWithExpiredHolds.getJSONObject(i);
						checkInItem(expiredItem);
					}
				} catch (JSONException e) {
					logger.error("Error converting result to JSON object", e);
				}
			}
		} catch (MalformedURLException e) {
			logger.error("Bad url for item API ", e);
		} catch (IOException e) {
			logger.error("Unable to retrieve expired online items from Item API", e);
		}
	}

	private void checkInItem(JSONObject itemToCheckIn) throws JSONException, MalformedURLException, IOException{
		String barcode = itemToCheckIn.getString("barcode");
		String itemid = itemToCheckIn.getString("itemid");
		String location = itemToCheckIn.getString("location");
		String collection = itemToCheckIn.getString("collection");
		String id = itemToCheckIn.getString("id");
		
		logger.info("Preparing to return item " + itemid + " barcode " + barcode );
		URL checkInURL = new URL(vufindUrl + "API/ItemAPI?method=checkInItem&barcode=" + barcode);
		Object checkInDataRaw = checkInURL.getContent();
		if (checkInDataRaw instanceof InputStream) {
			String checkInDataJson = Util.convertStreamToString((InputStream) checkInDataRaw);
			logger.info("Check in response " + checkInDataJson );
			JSONObject checkInData = new JSONObject(checkInDataJson);
			JSONObject result = checkInData.getJSONObject("result");
			if (result.getBoolean("success")) {
				logger.info("Item was checked in successfully.");
			}else{
				String errorMessage = result.getString("message");
				logger.error("Could not return item " + itemid + " error was " + errorMessage);
			}
		}else{
			logger.error("Response from check in url was not an input stream.");
		}
	}
}
