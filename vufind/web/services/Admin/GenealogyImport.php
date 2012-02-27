<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Action.php';
require_once 'services/Admin/ObjectEditor.php';
require_once 'services/Admin/Admin.php';
require_once 'XML/Unserializer.php';

class GenealogyImport extends Admin{
    function launch(){
        global $configArray;
        global $interface;
        
        //Check to see if we are doing the import.  If so, we need to do it in batches
        //Because PHP will time out doing the full import, break up into chunks of 25 records at a time.
        //Process is as follows: 
        //1. Display form for the user to select the file to import
        //2. User submits the file to import
        //3. File is copied to an import location 
        //4. System loads the total nummber of records in the file
        //5. Total number of records in the file are stored in the session with the filename and current status
        //6. Information sent back to browser with number of records, etc. 
        //7. Browser does AJAX callbacks to run each batch and update progress bar when each finishes. (in JSON.php) 
        //8. Separate action available to cancel the batch
        
        if (isset($_FILES["file"])){
            //Initial entry to the 
            if ($_FILES["file"]["error"] > 0){
                $interface->assign('importMessage', "Error processing uploaded file, error code: " . $_FILES["file"]["error"]);
            }else{
                if ($_FILES["file"]["type"] != 'text/csv' && $_FILES["file"]["type"] != 'text/comma-separated-values'){
                    $interface->assign('importMessage', "Only csv files are currently supported.  Please export your data to csv prior to importing. A {$_FILES["file"]["type"]} file was uploaded.");
                }else{
                    //Increase timeout to make sure that large files can be setup correctly
                    set_time_limit(600);
                    //copy the file to the import location
                    $destFileName = $_FILES["file"]["name"];
                    $destFolder = $configArray['Site']['local'] . '/files/genealogyImport';
                    $destFullPath = $destFolder . '/' . $destFileName;
                    $copyResult = copy($_FILES["file"]["tmp_name"], $destFullPath);
                    //Open the file to see how many rows there are
                    $file=fopen($destFullPath,"r");
                    //Store the filename within the session 
                    $_SESSION['genealogyImport']['filename'] = $destFullPath;
                    
                    $row = 0;
                    $headers;
                    $numRecords = 0;
                    while (($data = fgetcsv($file, 0, ",", '"')) !== FALSE){
                        if ($row == 0){
                            $headers = $data;
                        }else{
                            //Process the data to create people, marriages, obits, etc.
                            $numRecords++;
                        }
                        $row++;
                    }
                    $_SESSION['genealogyImport']['currentRecord'] = 0;
                    $_SESSION['genealogyImport']['numRecords'] = $numRecords;
            
                    $interface->assign('startImport', true);
                    $interface->assign('numRecords', $numRecords);
                    $interface->assign('percentComplete', 0);
                    fclose($file);
                }
            }
        }
        $interface->setTemplate('genealogyImport.tpl');

        $interface->setPageTitle('Import Genalogy Information');
        $interface->display('layout.tpl');
    }

    function getAllowableRoles(){
        return array('genealogyContributor');
    }

    
}