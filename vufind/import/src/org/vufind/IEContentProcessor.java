package org.vufind;

import java.sql.ResultSet;

public interface IEContentProcessor {
	public boolean processEContentRecord(ResultSet resource, long recordStatus);
}
