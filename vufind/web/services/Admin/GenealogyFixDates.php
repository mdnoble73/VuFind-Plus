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
require_once 'sys/Genealogy/Person.php';
require_once 'services/Admin/Admin.php';
require_once 'XML/Unserializer.php';

class GenealogyFixDates extends Admin{
    function launch(){
        global $configArray;
        global $interface;
        
        //Check to see if we are doing the reindex.  If so, we need to do it in batches
        //Because PHP will time out doing the full reindex, break up into chunks of 25 records at a time.
        //Process is as follows: 
        //1. Display form for the user to start the reindex process
        //2. System loads the total nummber of records in the database
        //5. Total number of records in the database are stored in the session with the filename and current status
        //6. Information sent back to browser with number of records, etc. 
        //7. Browser does AJAX callbacks to run each batch and update progress bar when each finishes. (in JSON.php) 
        //8. Separate action available to cancel the batch
        
        if (isset($_REQUEST["submit"])){
            $person = new Person();
            $person->find();
            $numRecords = $person->N;
            
            $_SESSION['genealogyDateFix']['currentRecord'] = 0;
            $_SESSION['genealogyDateFix']['numRecords'] = $numRecords;
    
            $interface->assign('startDateFix', true);
            $interface->assign('numRecords', $numRecords);
            $interface->assign('percentComplete', 0);
        }
        $interface->setTemplate('genealogyFixDates.tpl');

        $interface->setPageTitle('Fix Dates in Genalogy Information');
        $interface->display('layout.tpl');
    }

    function getAllowableRoles(){
        return array('genealogyContributor');
    }

    
}