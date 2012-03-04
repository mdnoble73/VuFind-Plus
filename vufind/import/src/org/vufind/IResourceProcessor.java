package org.vufind;

import java.sql.ResultSet;

public interface IResourceProcessor {
	public boolean processResource(ResultSet resource);	
}
