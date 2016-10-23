package org.vufind;

import java.util.ArrayList;
import java.util.HashSet;

/**
 * Created by jabedo on 10/21/2016.
 */
public class SiteMapScope {
    String scopeName;
    ArrayList<SiteMapGroup> siteMaps;
    HashSet<Scope> locationScopes;

    public SiteMapScope(String scopeName,HashSet<Scope> locationScopes) {
        this.scopeName = scopeName;
        siteMaps = new ArrayList<>();
        this.locationScopes = locationScopes;
    }

    public void addSiteMap(SiteMapGroup siteMap){
        siteMaps.add(siteMap);

    }

    public String getScopeName(){
        return scopeName;
    }
}
