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

import java.security.SecureRandom;
import java.util.Random;

import javax.crypto.Cipher;
import javax.crypto.spec.SecretKeySpec;

public class SecurityUtil {

	static public byte[] encryptAES(byte[] msg, byte[] key) throws Exception {
		SecretKeySpec keySpec = new SecretKeySpec(key, "AES");
		Cipher cipher = Cipher.getInstance("AES/CBC/PKCS5Padding");
		cipher.init(Cipher.ENCRYPT_MODE, keySpec, new SecureRandom());
		byte[] iv = cipher.getIV();
		byte[] enc = cipher.doFinal(msg);
		byte[] result = new byte[16 + enc.length];
		System.arraycopy(iv, 0, result, 0, 16);
		System.arraycopy(enc, 0, result, 16, enc.length);
		return result;
	}

	static public byte[] makeKey() {
		byte[] key = new byte[16];
		SecureRandom sr = new SecureRandom();
		sr.nextBytes(key);
		return key;
	}

	private static byte[] initTime = createInitTime();

	private static long counter = (new Random()).nextLong();

	static public void longToBytes(long k, byte[] b, int i) {
		b[i] = (byte) (k >> 56);
		b[i + 1] = (byte) (k >> 48);
		b[i + 2] = (byte) (k >> 40);
		b[i + 3] = (byte) (k >> 32);
		b[i + 4] = (byte) (k >> 24);
		b[i + 5] = (byte) (k >> 16);
		b[i + 6] = (byte) (k >> 8);
		b[i + 7] = (byte) k;
	}

	private static byte[] createInitTime() {
		// server start time
		long time = System.currentTimeMillis() ^ 3392387608507367157L;
		byte[] bytes = new byte[8];
		longToBytes(time, bytes, 0);
		return bytes;
	}

	public synchronized static byte[] newNonce() {
		byte[] nonce = new byte[16];
		counter++;
		System.arraycopy(initTime, 0, nonce, 0, 8);
		longToBytes(counter, nonce, 8);
		return nonce;
	}
	
}
