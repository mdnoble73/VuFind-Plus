package org.vufind;

import org.apache.log4j.Logger;

import java.io.BufferedWriter;
import java.io.File;
import java.io.FileWriter;
import java.io.IOException;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.*;

/**
 * Created by jabedo on 9/23/2016.
 */
public class SiteMap {

    private Logger logger;
    private final String org = "marmot.org";
    private int maxPopularTitles;
    private int maxUniqueTitles;
    private Connection vufindConn;
    private HashMap<Long, ArrayList<Long>> librariesByHomeLocation ;
    public SiteMap(Logger log, Connection connection, int maxUnique, int maxPopular) {
        this.logger = log;
        this.vufindConn = connection;
        this.maxPopularTitles = maxPopular;
        this.maxUniqueTitles = maxUnique;
        librariesByHomeLocation = new HashMap<>();
        prepareLocationIds();
    }

    private void prepareLocationIds(){

        try{
            PreparedStatement getLibraryForHomeLocation = vufindConn.prepareStatement("SELECT libraryId, locationId from location");
            ResultSet librariesByHomeLocationRS = getLibraryForHomeLocation.executeQuery();
            while (librariesByHomeLocationRS.next()){
                Long locationId =librariesByHomeLocationRS.getLong("locationId");
                Long libraryId = librariesByHomeLocationRS.getLong("libraryId");
                if(!librariesByHomeLocation.containsKey(libraryId)){
                    librariesByHomeLocation.put(libraryId, new ArrayList<Long>());
                }
                librariesByHomeLocation.get(libraryId).add(locationId);
            }
            librariesByHomeLocationRS.close();
        }catch (Exception ex){
            logger.info("Unable to get location Ids");
        }

    }

    private  ArrayList<SiteMapGroup> uniqueItemsToWrite;
    private int fileID;
    private int countTracker;
    private int currentSiteMapCount;
    private Long libraryIdToWrite;
    private String scopeName;
    private String filePath;
    private String url;
    private final int maxGoogleSiteMapCount = 50000;

    private void siteMapDefaults() {
        fileID = 1;
        countTracker = 0;
        currentSiteMapCount = 0;
    }


    public void createSiteMaps(String url, File dataDir, HashMap<Scope, ArrayList<SiteMapGroup>> siteMapsByScope,
                                HashSet<Long> uniqueGroupedWorks) throws IOException {

        //create a site maps directory if it doesn't exist
        if (!dataDir.exists()) {
            if (!dataDir.mkdirs()) {
                logger.error("Could not create site map directory");
                throw new IOException("Could not create site map directory");
            }
        }
        //update the variables table
        updateVariablesTable();
       //create site map index file
        filePath = dataDir.getPath();
        this.url = url;
        Date date = new Date();
        Iterator it = siteMapsByScope.entrySet().iterator();
        while (it.hasNext()) {
            SiteMapIndex siteMapIndex = new SiteMapIndex(logger);

            Map.Entry pair = (Map.Entry) it.next();
            Scope scope = (Scope) pair.getKey();
            scopeName = scope.getScopeName();
            libraryIdToWrite = scope.getLibraryId();

            ArrayList<SiteMapGroup> siteMapGroups = (ArrayList<SiteMapGroup>)pair.getValue();
            //separate the site maps into unique and popular
            ArrayList<SiteMapGroup> unique = new ArrayList<>();
            SortedSet<SiteMapGroup> popular = new TreeSet<>();
            regroupSiteMapGroups(unique, popular, siteMapGroups, uniqueGroupedWorks);

            uniqueItemsToWrite = unique;
            siteMapDefaults();
            String fileName = buildSiteMapFileName("_unique_", fileID);
            writeToFile(fileName, "_unique_", true, maxUniqueTitles);
            siteMapIndex.addSiteMapLocation(buildLocationURL(siteMapFileName("_unique_")), date.toString());

            uniqueItemsToWrite = new ArrayList<SiteMapGroup>(popular);
            siteMapDefaults();
            fileName = buildSiteMapFileName("_popular_", fileID);
            writeToFile(fileName, "_popular_", false, maxPopularTitles);
            siteMapIndex.addSiteMapLocation(buildLocationURL(siteMapFileName("_popular_")), date.toString());

            File siteMapindexFile = getSiteMapIndexFile();
            siteMapIndex.saveFile(siteMapindexFile);


            it.remove();
        }
    }


    private void writeToFile(String fileName, String fileType, Boolean writeLibraryAndBranches, int maxTitles ) {

        BufferedWriter writer = null;
        try {
            File outputFile = new File(fileName);
            if (outputFile.exists())
                outputFile.delete();

            outputFile.createNewFile();
            logger.info("creating .." + fileName);
            FileWriter fw = new FileWriter(outputFile.getAbsoluteFile());
            writer = new BufferedWriter(fw);
            //add system
            countTracker++;

            if (writeLibraryAndBranches) {

                String baseUrl = buildBranchUrl("System", Long.toString(libraryIdToWrite));
                writer.write(baseUrl);
                writer.newLine();

                //add library branches?
                ArrayList<Long> branches = librariesByHomeLocation.get(libraryIdToWrite);
                for (Long libId : branches) {
                    baseUrl = buildBranchUrl("Branch", Long.toString(libId));
                    writer.write(baseUrl);
                    writer.newLine();
                    countTracker++;
                }
            }

            for (int i = currentSiteMapCount; i < uniqueItemsToWrite.size(); i++) {
                SiteMapGroup siteMapGroup = uniqueItemsToWrite.get(i);

                if(i >= maxTitles)
                    break;

                if (countTracker <= maxGoogleSiteMapCount) {
                    writer.write(buildGroupedWorkSiteMap(siteMapGroup.getPermanentId()));
                    writer.newLine();
                    countTracker++;
                } else {
                    fileID++;
                    currentSiteMapCount = i + 1;
                    countTracker = 0;
                    fileName = buildSiteMapFileName(fileType, fileID);
                    writeToFile(fileName, fileType, false, maxTitles);
                }
            }
            logger.info("created: " + fileName);
        } catch (IOException ex) {
            logger.error("Could not create unique works file");
            logger.error("Error creating: " + fileName);
        } finally {
            try {
                writer.close();
            } catch (Exception ex) {
                /*ignore*/
            }
        }
    }


    ///regroups the works into unique and sorted popular works
    private void regroupSiteMapGroups( ArrayList<SiteMapGroup> unique ,SortedSet<SiteMapGroup> popular, ArrayList<SiteMapGroup> siteMapGroups,HashSet<Long> uniqueGroupedWorks ){

        for(int i = 0; i < siteMapGroups.size(); i++){
            SiteMapGroup siteMapGroup =siteMapGroups.get(i);
            if( uniqueGroupedWorks.contains(siteMapGroup.getId())){
                unique.add(siteMapGroup);
            }
            else{
                popular.add(siteMapGroup);
            }
        }
    }

    private StringBuilder baseUrl() {
        StringBuilder builder = new StringBuilder();
        builder.append("https://")
                .append(scopeName)
                .append(".")
                .append(org);
        return builder;
    }

    private String buildLocationURL(String fileName) {
        StringBuilder builder = baseUrl();
        builder.append("/")
                .append("sitemaps")
                .append("/")
                .append(fileName);
        return builder.toString();
    }



    private File getSiteMapIndexFile() {
        try {

            File outputFile = new File(buildSiteMapIndexFile());
            if (outputFile.exists())
                outputFile.delete();

            outputFile.createNewFile();
            return outputFile;
        } catch (IOException ex) {
            logger.error("unable to create sitemaps index file");
        }
        return null;
    }

    private String buildSiteMapIndexFile() {

        StringBuilder builder = new StringBuilder();
        builder.append(filePath)
                .append("\\")
                .append(scopeName)
                .append(".")
                .append(org)
                .append(".xml");

        return builder.toString();
    }

    private String buildSiteMapFileName(String fileTypeName, int fileID) {
        return filePath + "\\" + siteMapFileName(fileTypeName, fileID);
    }
    private String siteMapFileName(String fileTypeName) {
        StringBuilder builder = new StringBuilder();
        builder.append(scopeName)
                .append(".")
                .append(org)
                .append(fileTypeName)
                .append(String.format("%1$03d", fileID))
                .append(".txt");

        return builder.toString();
    }

    private String siteMapFileName(String fileTypeName, int fileID) {
        StringBuilder builder = new StringBuilder();
        builder.append(scopeName)
                .append(".")
                .append(org)
                .append(fileTypeName)
                .append(String.format("%1$03d", fileID))
                .append(".txt");

        return builder.toString();
    }


    private String buildBranchUrl(String branch, String branchID) {
        //https://adams.marmot.org/Library/1/Branch
        //https://adams.marmot.org/Library/1/System
        StringBuilder builder = baseUrl();
        builder.append("/")
                .append("Library")
                .append("/")
                .append(branchID)
                .append("/")
                .append(branch);
        return builder.toString();
    }

    private String buildGroupedWorkSiteMap(String id) {
        //https://adams.marmot.org/GroupedWork/24d6b52f-05de-a6d5-fc01-89ccefd7356e/Home -- example
        StringBuilder builder = baseUrl();
        builder.append("/GroupedWork/")
                .append(id)
                .append("/")
                .append("Home");
        return builder.toString();
    }

    private void updateVariablesTable() {

        try {

            maxUniqueTitles = addRowToVariablesTable("num_title_in_unique_sitemap", maxUniqueTitles);
            maxPopularTitles = addRowToVariablesTable("num_titles_in_most_popular_sitemap", maxPopularTitles);

        } catch (SQLException ignored) {

        }
    }

    private int addRowToVariablesTable(String variableName, int value) throws SQLException {

        try {
            PreparedStatement st = vufindConn.prepareStatement("SELECT value from variables WHERE name = ?");
            st.setString(1, variableName);
            ResultSet rs = st.executeQuery();

            if (rs != null && rs.next()) {
                return Integer.parseInt(rs.getString("value"));
            }

            PreparedStatement insertVariableStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('" + variableName + "', ?)");
            insertVariableStmt.setString(1, Long.toString(value));
            insertVariableStmt.executeUpdate();
            insertVariableStmt.close();

            return value;

        } catch (Exception ex) {
            /*ignore*/
        } finally {
          /*ignore*/
        }
        return 0;
    }

}

