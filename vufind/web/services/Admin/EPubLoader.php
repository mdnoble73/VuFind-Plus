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
require_once 'XML/Unserializer.php';
require_once 'sys/eContent/EContentItem.php';

class EPubLoader extends ObjectEditor
{

    function getObjectType(){
        return 'EContentItem';
    }
    function getToolName(){
        return 'EContentItem';
    }
    function getPageTitle(){
        return 'E-Pub Loader';
    }
    function getAllObjects(){
        $library = new EContentItem();
        $library->orderBy('filename');
        $library->find();
        $libraryList = array();
        while ($library->fetch()){
            $libraryList[$library->id] = clone $library;
        }
        return $libraryList;
    }
    function getObjectStructure(){
        return EContentItem::getObjectStructure();
   }
    function getPrimaryKeyColumn(){
        return 'filename';
    }
    function getIdKeyColumn(){
        return 'id';
    }
    function getAllowableRoles(){
        return array('epubAdmin');
    }
    
}