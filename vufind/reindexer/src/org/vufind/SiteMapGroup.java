package org.vufind;


/**
 * Created by jabedo on 9/25/2016.
 * Handles site generation for a grouped work
 *
 */
public class SiteMapGroup implements Comparable {

    /*
    *
    * The ownership is determined by scope and the sitemap will be loaded by scope.
So you need to either have a set of SiteMaps (1 per scope) or update SiteMapGroup to include the scope and then have a list of works within the SiteMapGroup.  The first option is probably better.
When you add a grouped work to a SiteMap you will need to loop through all of the scopes that you are building sitemaps for and check each scope to see if the record isLibraryOwned.  The logic will be similar to the logic in: updateIndexingStats.
*/
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
    public SiteMapGroup(Long Id, String permanentId, Double popularity) {
        this.permanentId = permanentId;
        this.Id = Id;
        this.popularity = popularity;
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
