package org.vufind;


import java.util.ArrayList;

/**
 * Created by jabedo on 9/25/2016.
 */
public class SiteMapGroup implements Comparable {
    private Long Id;

    public Long getId() {
        return Id;
    }

    private String permanentId;

    public String getPermanentId() {
        return permanentId;
    }

    private double popularity;

    public double getPopularity() {
        return popularity;
    }

    private boolean isLibraryOwned;

    private ArrayList<String> scopeNames;

    public  ArrayList<String> GetValidScopeNames(){
        return scopeNames;
    }

    private int ownerShipCount;
    public SiteMapGroup(Long Id, String permanentId, Double popularity, boolean isLibraryOwned, int ownerShipCount) {
        this.permanentId = permanentId;
        this.Id = Id;
        this.popularity = popularity;
        this.isLibraryOwned = isLibraryOwned;
        this.ownerShipCount = ownerShipCount;
    }

    public boolean getIsLibraryOwned() {
        return isLibraryOwned;
    }

    public int getOwnerShipCount() {
        return ownerShipCount;
    }

    @Override
    public int compareTo(Object o) {
        //compare object based on popularity
        SiteMapGroup toCompare = (SiteMapGroup) o;
        if (toCompare.getPopularity() < this.popularity) return -1;
        if (toCompare.getPopularity() > this.popularity) return 1;

        return 0;
    }


}
