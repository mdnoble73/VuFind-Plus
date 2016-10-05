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

    private ArrayList<SiteMapGroup> siteMapGroups;
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

    public void createSiteMap(File dataDir, ArrayList<SiteMapLibrary> siteMapLibraries, HashMap<Long, String> branchNames, ArrayList<SiteMapGroup> siteMapGroups
    ) throws IOException {
        //create a site maps directory if it doesn't exist
        if (!dataDir.exists()) {
            if (!dataDir.mkdirs()) {
                logger.error("Could not create site map directory");
                throw new IOException("Could not create site map directory");
            }
        }
        //update the variables table
        updateVariablesTable();

            /*    loop thru all works
                 get a list of all works owned by the library
                  loop thru all siteinfos
                        filter to get unique works
                        filter popular works*/

        ArrayList<SiteMapGroup> unique = new ArrayList<>();
        SortedSet<SiteMapGroup> popular = new TreeSet<>();

        for (SiteMapGroup siteMapGroup : siteMapGroups) {
            if (siteMapGroup.getOwnerShipCount() <= 1)
                unique.add(siteMapGroup);
            else
                popular.add(siteMapGroup);
        }
        //create site map index file
        SiteMapIndex siteMapIndex = new SiteMapIndex(logger);
        int fileID = getFileID(dataDir);
        String fileName;
        for (SiteMapLibrary siteMapLibrary : siteMapLibraries) {
            try {
                fileName = buildSiteMapFileName(dataDir.getPath(), siteMapLibrary.getSubdomain(), "_unique_", fileID);
                createOutputFile(siteMapLibrary.getSubdomain(), siteMapLibrary.getLibraryID(), branchNames, maxUniqueTitles, unique, fileName);
                 Date date = new Date();
                siteMapIndex.addSiteMapLocation(fileName, date.toString());
            } catch (Exception ex) {
                logger.error(ex.getMessage());
            }
            try {
                fileName = buildSiteMapFileName(dataDir.getPath(), siteMapLibrary.getSubdomain(), "_popular_", fileID);
                createOutputFile(siteMapLibrary.getSubdomain(), siteMapLibrary.getLibraryID(), branchNames, maxPopularTitles, new ArrayList<SiteMapGroup>(popular), fileName);
                Date date = new Date();
                siteMapIndex.addSiteMapLocation(fileName, date.toString());
            } catch (Exception ex) {
                logger.error(ex.getMessage());
            }
        }
        File siteMapindexFile = getSiteMapIndexFile(dataDir.getPath());
        siteMapIndex.saveFile(siteMapindexFile);
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

    private File getSiteMapIndexFile(String path) {
        try {

            File outputFile = new File(buildSiteMapIndexFile(path));
            if (!outputFile.exists())
                outputFile.createNewFile();
            return outputFile;
        } catch (IOException ex) {
            logger.error("unable to create sitemaps index file");
        }
        return null;
    }

    private String buildSiteMapIndexFile(String path) {

        StringBuilder builder = new StringBuilder();
        builder.append(path)
                .append("\\")
                .append("sitemaps_index")
                .append(".")
                .append("xml");
        return builder.toString();
    }

    private void createOutputFile(String subdomain, Long libraryId, HashMap<Long, String> branchNames, int maxGroupedIds, ArrayList<SiteMapGroup> siteMapGroups, String fileName) {
        BufferedWriter writer = null;
        try {
            File outputFile = new File(fileName);
            if (!outputFile.exists())
                outputFile.createNewFile();
            logger.info("creating .." + fileName);
            FileWriter fw = new FileWriter(outputFile.getAbsoluteFile());
            writer = new BufferedWriter(fw);
            //add home
            String baseUrl = buildBaseUrl(subdomain);
            writer.write(baseUrl);
            writer.newLine();
            //add library branches
            Iterator it = branchNames.entrySet().iterator();
            while (it.hasNext()) {
                Map.Entry pair = (Map.Entry) it.next();
                if (pair.getKey() == libraryId) {
                    writer.write(buildBranchUrl(subdomain, (String) pair.getValue()));
                    writer.newLine();
                }
                it.remove();
            }
            int count = 0;
            for (SiteMapGroup siteMapGroup : siteMapGroups) {
                if (count <= maxGroupedIds) {
                    writer.write(buildGroupedWorkSiteMap(baseUrl, siteMapGroup));
                    writer.newLine();
                    count++;
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

        StringBuilder builder = new StringBuilder();
        builder.append(path)
                .append("\\")
                .append(subdomain)
                .append(".")
                .append(org)
                .append(fileTypeName)
                .append(String.format("%1$03d", fileID))
                .append(".txt");

        return builder.toString();
    }

    private String buildBaseUrl(String subdomain) {
        //http://adams.marmot.org/Home-- example
        StringBuilder builder = new StringBuilder();
        builder.append("https://")
                .append(subdomain)
                .append(".")
                .append(org)
                .append("/")
                .append("Home");
        return builder.toString();
    }

    private String buildBranchUrl(String subdomain, String branch) {
        //http://adams.marmot.org/as-- example
        StringBuilder builder = new StringBuilder();
        builder.append("https://")
                .append(subdomain)
                .append(".")
                .append(org)
                .append("/")
                .append(branch);
        return builder.toString();
    }

    private String buildGroupedWorkSiteMap(String baseUrl, SiteMapGroup siteMapGroup) {
        //http://adams.marmot.org/GroupedWork/24d6b52f-05de-a6d5-fc01-89ccefd7356e/Home -- example
        StringBuilder builder = new StringBuilder();
        builder.append(baseUrl)
                .append("/GroupedWork/")
                .append(siteMapGroup.getPermanentId())
                .append("/")
                .append("Home");
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
            st.setString(1,variableName);
            ResultSet rs = st.executeQuery();

            if (rs != null && rs.next()) {
                return Integer.parseInt(rs.getString("value"));
            }

           /* PreparedStatement insertVariableStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ("+  variableName +" , " +  Integer.toString(value)  + ")", Statement.RETURN_GENERATED_KEYS);
            insertVariableStmt.setString(1,variableName);
            insertVariableStmt.setString(2, Integer.toString(value));
            insertVariableStmt.executeUpdate();*/
            PreparedStatement insertVariableStmt = vufindConn.prepareStatement("INSERT INTO variables (`name`, `value`) VALUES ('"+variableName +"', ?)");
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

