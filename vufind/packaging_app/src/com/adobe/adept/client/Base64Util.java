/*************************************************************************
*
* ADOBE CONFIDENTIAL
* ___________________
*
*  Copyright 2010 Adobe Systems Incorporated
*  All Rights Reserved.
*
* NOTICE:  All information contained herein is, and remains
* the property of Adobe Systems Incorporated and its suppliers,
* if any.  The intellectual and technical concepts contained
* herein are proprietary to Adobe Systems Incorporated and its
* suppliers and are protected by trade secret or copyright law.
* Dissemination of this information or reproduction of this material
* is strictly forbidden unless prior written permission is obtained
* from Adobe Systems Incorporated.
**************************************************************************/
package com.adobe.adept.client;

import java.util.prefs.AbstractPreferences;
import java.util.prefs.BackingStoreException;
import java.util.prefs.Preferences;

public class Base64Util {

	static class DummyPrefs extends AbstractPreferences {

		String dummyVal;

		DummyPrefs() {
			super(null, "");
		}

		@Override
		protected String[] childrenNamesSpi() throws BackingStoreException {
			// TODO Auto-generated method stub
			return null;
		}

		@Override
		protected AbstractPreferences childSpi(String name) {
			// TODO Auto-generated method stub
			return null;
		}

		@Override
		protected void flushSpi() throws BackingStoreException {
			// TODO Auto-generated method stub

		}

		@Override
		protected String getSpi(String key) {
			// TODO Auto-generated method stub
			if (key.equals("dummy"))
				return dummyVal;
			return null;
		}

		@Override
		protected String[] keysSpi() throws BackingStoreException {
			// TODO Auto-generated method stub
			return null;
		}

		@Override
		protected void putSpi(String key, String value) {
			// TODO Auto-generated method stub
			if (key.equals("dummy"))
				dummyVal = value;
		}

		@Override
		protected void removeNodeSpi() throws BackingStoreException {
			// TODO Auto-generated method stub

		}

		@Override
		protected void removeSpi(String key) {
			// TODO Auto-generated method stub

		}

		@Override
		protected void syncSpi() throws BackingStoreException {
			// TODO Auto-generated method stub

		}

	}

	static public String encode(byte[] arr) {
		Preferences p = new DummyPrefs();
		p.putByteArray("dummy", arr);
		return p.get("dummy", "");
	}

	static public byte[] decode(String str) {
		Preferences p = new DummyPrefs();
		p.put("dummy", str);
		return p.getByteArray("dummy", null);
	}
}