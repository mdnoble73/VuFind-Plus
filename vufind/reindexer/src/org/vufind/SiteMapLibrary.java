package org.vufind;

/**
 * Created by jabedo on 9/28/2016.
 */
public class SiteMapLibrary {
    private Long libraryID;
    private String subdomain;
    private boolean isLibraryScope;

    public SiteMapLibrary(Long libraryID, String subdomain, boolean isLibraryScope){
        this.libraryID = libraryID;
        this.subdomain = subdomain;
        this.isLibraryScope = isLibraryScope;
    }

    public String getSubdomain(){
        return subdomain;
    }

    public  Long getLibraryID(){
        return  libraryID;
    }
}
