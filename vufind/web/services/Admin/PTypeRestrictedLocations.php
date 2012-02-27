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
require_once 'Drivers/marmot_inc/PTypeRestrictedLocation.php';
require_once 'XML/Unserializer.php';

class PTypeRestrictedLocations extends ObjectEditor
{
    function getObjectType(){
        return 'PTypeRestrictedLocation';
    }
    function getToolName(){
        return 'PTypeRestrictedLocations';
    }
    function getPageTitle(){
        return 'Locations restricted by Patron Type';
    }
    function getAllObjects(){
        $object = new PTypeRestrictedLocation();
        $object->orderBy('millenniumCode');
        $object->find();
        $objectList = array();
        while ($object->fetch()){
            $objectList[$object->locationId] = clone $object;
        }
        return $objectList;
    }
    function getObjectStructure(){
        $structure = array(
          'millenniumCode' => array('property'=>'millenniumCode', 'type'=>'text', 'label'=>'Millennium Code', 'description'=>'A unique id for the non holdable location'),
          'holdingDisplay' => array('property'=>'holdingDisplay', 'type'=>'text', 'label'=>'Holding Display', 'description'=>'The text displayed in the holdings list within Millennium'),
          'allowablePtypes' => array('property'=>'allowablePtypes', 'type'=>'text', 'label'=>'Allowable P-Types', 'description'=>'The patron types which are allowed to checkout items with this code separated with pipes (|)'),
        );
        foreach ($structure as $fieldName => $field){
            $field['propertyOld'] = $field['property'] . 'Old';
            $structure[$fieldName] = $field;
        }
        return $structure;
    }
    function getPrimaryKeyColumn(){
        return 'millenniumCode';
    }
    function getIdKeyColumn(){
        return 'locationId';
    }
    function getAllowableRoles(){
        return array('opacAdmin');
    }
    
}