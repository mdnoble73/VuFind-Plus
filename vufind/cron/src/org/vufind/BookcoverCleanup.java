package org.vufind;

import java.io.File;
import java.io.FilenameFilter;
import java.util.Date;

import org.apache.log4j.Logger;
import org.ini4j.Ini;
import org.ini4j.Profile.Section;

public class BookcoverCleanup  implements IProcessHandler{
  public void doCronProcess(Ini configIni, Logger logger ){
    System.out.println("Removing old bookcovers");
    
    String coverPath = configIni.get("Site", "coverPath");
    String[] coverPaths = new String[]{"/small", "/medium", "/large"};
    Long currentTime = new Date().getTime();
    
    for (String path : coverPaths){
      int numFilesDeleted = 0;
      
      String fullPath = coverPath + path;
      File coverDirectoryFile = new File(fullPath);
      if (!coverDirectoryFile.exists()){
        System.out.println("Directory " + coverDirectoryFile.getAbsolutePath() + " does not exist.  Please check configuration file.");
      }else{
        System.out.println("Cleaning up covers in " + coverDirectoryFile.getAbsolutePath());
        File[] filesToCheck = coverDirectoryFile.listFiles(new FilenameFilter() {
          public boolean accept(File dir, String name) {
            return name.toLowerCase().endsWith("jpg") || name.toLowerCase().endsWith("png");
          }
        });
        for (File curFile : filesToCheck){
          if (curFile.lastModified() < (currentTime - 2 * 7 * 24 * 3600)){
            curFile.delete();
            numFilesDeleted++;
          }
        }
        if (numFilesDeleted > 0){
          System.out.println("\tRemoved " + numFilesDeleted + " files.");
        }
      }
    }
  }
}
