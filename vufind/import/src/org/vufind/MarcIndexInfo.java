package org.vufind;

public class MarcIndexInfo {
	private long checksum;
	private long backupChecksum;
	private boolean EContent;
	private boolean backupEContent;
	public long getChecksum() {
		return checksum;
	}
	public void setChecksum(long checksum) {
		this.checksum = checksum;
	}
	public long getBackupChecksum() {
		return backupChecksum;
	}
	public void setBackupChecksum(long backupChecksum) {
		this.backupChecksum = backupChecksum;
	}
	public boolean isEContent() {
		return EContent;
	}
	public void setEContent(boolean eContent) {
		this.EContent = eContent;
	}
	public boolean isBackupEContent() {
		return backupEContent;
	}
	public void setBackupEContent(boolean backupEContent) {
		this.backupEContent = backupEContent;
	}
	
}
