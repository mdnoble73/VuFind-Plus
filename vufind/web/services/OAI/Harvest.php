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

require_once 'Harvester/Harvester.php';

class Harvest
{

    var $siteList = array();
    var $harvester;
    var $db;

    function __construct()
    {
        global $configArray;
    
        $this->harvester = new OAIHarvester();

        // Setup Search Engine Connection
        $class = $configArray['Index']['engine'];
        $this->db = new $class($configArray['Index']['url']);
        
        $this->siteList = parse_ini_file('sites.ini', true);
    }

    function launch()
    {
        foreach($this->siteList as $site) {
            $this->harvester->setHost($site['host']);
            $recordList = $this->harvester->GetRecords('oai_dc');
            foreach($recordList as $record) {
                print_r($record);
                //$this->db->addRecord($record);
            }
        }
    }
}

?>