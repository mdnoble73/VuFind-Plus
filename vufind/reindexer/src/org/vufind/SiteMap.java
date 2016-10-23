package org.vufind;

import org.apache.log4j.Logger;

import java.io.BufferedWriter;
import java.io.File;
import java.io.FileWriter;
import java.io.IOException;
import java.sql.*;
import java.util.*;
import java.util.Date;

/**
 * Created by jabedo on 9/23/2016.
 */
public class SiteMap {

    private Logger logger;
    private final String org = "marmot.org";
    private int maxPopularTitles;
    private int maxUniqueTitles;
    private Connection vufindConn;

    public SiteMap(Logger log, Connection connection, int maxUnique, int maxPopular) {
        this.logger = log;
        this.vufindConn = connection;
        this.maxPopularTitles = maxPopular;
        this.maxUniqueTitles = maxUnique;
    }

    public void createSiteMap(File dataDir, HashMap<Scope, ArrayList<SiteMapGroup>> siteMapsByScope,
                              HashSet<Long> uniqueGroupedWorks/*, HashMap<String, Long> locationScopes */) throws IOException {
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

        int fileID = getFileID(dataDir);
        Date date = new Date();
        Iterator it = siteMapsByScope.entrySet().iterator();
        while (it.hasNext()) {
            SiteMapIndex siteMapIndex = new SiteMapIndex(logger);

            Map.Entry pair = (Map.Entry) it.next();
            Scope scope = (Scope)pair.getKey();
            String scopeName = scope.getScopeName();
            Long libraryID = scope.getLibraryId();

          /*   ArrayList<String> branchIds = new ArrayList<>();
           for(Scope branchScope: scope.getLocationScopes()){
                *//*branchIds.add( Long.toOctalString(branchScope.getLibraryId()));*//*
                if(locationScopes.containsKey(branchScope.getScopeName())){
                    branchIds.add(Long.toString( locationScopes.get(branchScope.getScopeName()))    );
                    Long id= locationScopes.get(branchScope.getScopeName());
                }

            }
            */

            ArrayList<SiteMapGroup> siteMapGroups = (ArrayList<SiteMapGroup>)pair.getValue();
            //separate the site maps into unique and popular
            ArrayList<SiteMapGroup> unique = new ArrayList<>();
            SortedSet<SiteMapGroup> popular = new TreeSet<>();
            regroupSiteMapGroups(unique, popular, siteMapGroups, uniqueGroupedWorks);

            String fileType = "_unique_";
            String fileName = buildSiteMapFileName(dataDir.getPath(), scopeName, fileType, fileID);
            TreeMap<String, List<SiteMapGroup>> fileMapsGroupings = buildSiteMapGroupings(unique, fileName);
            Iterator it2 = fileMapsGroupings.entrySet().iterator();
            while (it2.hasNext()) {
                Map.Entry kvp = (Map.Entry) it2.next();
                writeSiteMap(new ArrayList<>((List<SiteMapGroup>) kvp.getValue()), (String) kvp.getKey(), scopeName, libraryID/*,branchIds*/);
                it2.remove();
            }
            siteMapIndex.addSiteMapLocation(buildLocationURL(scopeName, siteMapFileName(scopeName, fileType, fileID)), date.toString());
             fileType = "_popular_";
             fileName = buildSiteMapFileName(dataDir.getPath(), scopeName, fileType, fileID);
             fileMapsGroupings = buildSiteMapGroupings(new ArrayList(popular), fileName);
             it2 = fileMapsGroupings.entrySet().iterator();
            while (it2.hasNext()) {
                Map.Entry kvp = (Map.Entry) it2.next();
                writeSiteMap(new ArrayList<>((List<SiteMapGroup>) kvp.getValue()), (String) kvp.getKey(), scopeName, libraryID/*,branchIds*/);
                it2.remove();
            }
            siteMapIndex.addSiteMapLocation(buildLocationURL(scopeName, siteMapFileName(scopeName, fileType, fileID)), date.toString());

            File siteMapindexFile = getSiteMapIndexFile(dataDir.getPath(),scopeName);
            siteMapIndex.saveFile(siteMapindexFile);
            it.remove();
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

    private void writeSiteMap( ArrayList<SiteMapGroup> siteMapGroups,  String fileName, String scopName, Long libraryID/*, ArrayList<String> branchIds*/) {
        try {
            writeToOutputFile(scopName, maxUniqueTitles, siteMapGroups, fileName,libraryID/*, branchIds*/);
        } catch (Exception ex) {
            logger.error(ex.getMessage());
        }
    }

    private StringBuilder baseUrl(String subdomain) {
        StringBuilder builder = new StringBuilder();
        builder.append("https://")
                .append(subdomain)
                .append(".")
                .append(org);
        return builder;
    }

    private String buildLocationURL(String subdomain, String fileName) {
        StringBuilder builder = baseUrl(subdomain);
        builder.append("/")
                .append("sitemaps")
                .append("/")
                .append(fileName);
        return builder.toString();
    }


    private int getFileID(File dataDir) {
        int fileID = 0;
        File[] files = dataDir.listFiles();
        for (File file : files) {
            String fileName = file.getName();
            if (!fileName.endsWith("txt"))
                continue;

            if (fileName.contains("_popular_")) {
                fileID = getSiteMapSequenceFileID(fileID, fileName, "_popular_");
            }
            if (fileName.contains("_unique_")) {
                fileID = getSiteMapSequenceFileID(fileID, fileName, "_unique_");
            }
        }
        fileID++; // next available number
        return fileID;
    }

    //regroups the files into 50000 sizes---per google requirement
    private  TreeMap<String, List<SiteMapGroup>> buildSiteMapGroupings(ArrayList<SiteMapGroup> siteMapGroups, String fileName) {
        final int maxGoogleSiteMapCount = 50000;
        TreeMap<String, List<SiteMapGroup>> siteMapGroupings = new TreeMap<>();
        int totalGroupings = siteMapGroups.size() / maxGoogleSiteMapCount + 1;
        for (int i = 0; i < totalGroupings; i++) {
            int startIndex = i * maxGoogleSiteMapCount > siteMapGroups.size() ? siteMapGroups.size() : i * maxGoogleSiteMapCount;
            int endIndex =((i + 1) * maxGoogleSiteMapCount - 1) > siteMapGroups.size() ?  siteMapGroups.size(): (i + 1) * maxGoogleSiteMapCount - 1;
            List<SiteMapGroup> subList = siteMapGroups.subList(startIndex, endIndex);
            if (i == 0) {
                siteMapGroupings.put(fileName, subList);
            } else {
                String subName = fileName;
                for(int j = 0; j < i; j++ ){
                    fileName += "#";
                }

                siteMapGroupings.put(subName, subList);
            }
        }

        return siteMapGroupings;
    }

    private File getSiteMapIndexFile(String path, String subdomain) {
        try {

            File outputFile = new File(buildSiteMapIndexFile(path, subdomain));
            if (outputFile.exists())
                outputFile.delete();

            outputFile.createNewFile();
            return outputFile;
        } catch (IOException ex) {
            logger.error("unable to create sitemaps index file");
        }
        return null;
    }

    private String buildSiteMapIndexFile(String path, String subdomain) {

        StringBuilder builder = new StringBuilder();
        builder.append(path)
                .append("\\")
                .append(subdomain)
                .append(".")
                .append(org)
                .append(".xml");

        return builder.toString();
    }

    private void writeToOutputFile(String subdomain, int maxGroupedIds, ArrayList<SiteMapGroup> siteMapGroups, String fileName, Long libraryID/*, ArrayList<String> branchIds*/) {
        BufferedWriter writer = null;
        try {
            File outputFile = new File(fileName);
            if (!outputFile.exists())
                outputFile.createNewFile();
            logger.info("creating .." + fileName);
            FileWriter fw = new FileWriter(outputFile.getAbsoluteFile());
            writer = new BufferedWriter(fw);
            //add system
            int urlCount = 1;
            String baseUrl = buildBranchUrl(subdomain, "System", Long.toString(libraryID) );
            writer.write(baseUrl);
            writer.newLine();

             baseUrl = buildBranchUrl(subdomain, "Branch",  Long.toString(libraryID));
            writer.write(baseUrl);
            writer.newLine();

            //add library branches?

            int count = 0;
            for (SiteMapGroup siteMapGroup : siteMapGroups) {
                if (count <= maxGroupedIds) {
                    writer.write(buildGroupedWorkSiteMap(subdomain,  siteMapGroup.getPermanentId()));
                    writer.newLine();
                    count++;
                    urlCount++;
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

    private int getSiteMapSequenceFileID(int fileID, String filename, String fileTypeName) {

        int tmpFileID = Integer.parseInt(filename.split(fileTypeName)[1].split("\\.")[0]);
        if (tmpFileID > fileID)
            fileID = tmpFileID;
        return fileID;
    }

    private String buildSiteMapFileName(String path, String subdomain, String fileTypeName, int fileID) {
        return path + "\\" + siteMapFileName(subdomain, fileTypeName, fileID);
    }

    private String siteMapFileName(String subdomain, String fileTypeName, int fileID) {
        StringBuilder builder = new StringBuilder();
        builder.append(subdomain)
                .append(".")
                .append(org)
                .append(fileTypeName)
                .append(String.format("%1$03d", fileID))
                .append(".txt");

        return builder.toString();
    }

    private String buildBranchUrl(String subdomain, String branch, String branchID) {
        //https://adams.marmot.org/Library/1/Branch
        //https://adams.marmot.org/Library/1/System
        StringBuilder builder = baseUrl(subdomain);
        builder.append("/")
                .append("Library")
                .append("/")
                .append(branchID)
                .append("/")
                .append(branch);
        return builder.toString();
    }

    private String buildGroupedWorkSiteMap(String subdomain, String permanetID) {
        //https://adams.marmot.org/GroupedWork/24d6b52f-05de-a6d5-fc01-89ccefd7356e/Home -- example
        StringBuilder builder = baseUrl(subdomain);
        builder.append("/GroupedWork/")
                .append(permanetID);
                /*.append("/");
                .append("Home");*/
        return builder.toString();
    }

    private void updateVariablesTable() {

        try {

            maxUniqueTitles = addRowToVariablesTable("num_title_in_unique_sitemap", maxUniqueTitles);
            maxPopularTitles = addRowToVariablesTable("num_titles_in_most_popular_sitemap", maxPopularTitles);

        } catch (SQLException ex) {

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

