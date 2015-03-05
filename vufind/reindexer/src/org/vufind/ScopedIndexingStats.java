package org.vufind;

/**
 * Store stats about what has been indexed for each scope.
 *
 * Pika
 * User: Mark Noble
 * Date: 3/2/2015
 * Time: 7:14 PM
 */
public class ScopedIndexingStats {
	private String scopeName;
	public int numLocalWorks;
	public int numSuperScopeWorks;
	public int numLocalIlsRecords;
	public int numSuperScopeIlsRecords;
	public int numLocalIlsItems;
	public int numSuperScopeIlsItems;
	public int numLocalEContentItems;
	public int numSuperScopeEContentItems;
	public int numLocalOrderItems;
	public int numSuperScopeOrderItems;
	public int numLocalOverDriveRecords;
	public int numSuperScopeOverDriveRecords;
	public int numHooplaRecords;

	public ScopedIndexingStats(String scopeName) {
		this.scopeName = scopeName;
	}

	public String getScopeName() {
		return scopeName;
	}
}
