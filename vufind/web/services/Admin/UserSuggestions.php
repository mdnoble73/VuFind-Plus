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
require_once 'services/Admin/Admin.php';
require_once 'Drivers/marmot_inc/UserSuggestion.php';
require_once 'XML/Unserializer.php';

class UserSuggestions extends Admin
{
    function launch()
    {
        global $configArray;
        global $interface;
        
        //Check to see if we had data posted to us 
        if (isset($_REQUEST['submit']) && !empty($_REQUEST['submit'])){
            //Load the locations from the request data
            $ids = $_REQUEST['id'];
            $hides = $_REQUEST['hide'];
            $internalNotes = $_REQUEST['internalNotes'];
            $deletions = $_REQUEST['delete'];
            
            foreach ($ids as $id){
                //This is an existing location, update it
                $curSuggestion = new UserSuggestion();
                $curSuggestion->suggestionId = $id;
                $curSuggestion->find();
                if ($curSuggestion->N == 1){
                    if (!isset($deletions[$id]) || $deletions[$id] === FALSE){
                        //Update the record
                    
                        $curSuggestion->fetch();
                        $curSuggestion->hide = ($hides[$id] == 'on' ? 1 : 0);
                        $curSuggestion->internalNotes = $internalNotes[$id];
                        $curSuggestion->update();
                    }else{
                        //Delete the record
                        $curSuggestion->delete();
                    }
                }else{
                    //Couldn't find the record.  Something went haywire.
                }
            }
            header("Location: {$configArray['Site']['url']}/Admin/UserSuggestions");
            die();
            break;
        }
        
        //Show a list of user suggestions. 
        $suggestion = new UserSuggestion();
        if (!isset($_REQUEST['showHidden'])){
            $suggestion->whereAdd('hide = 0');
            $interface->assign('showHidden', true);
        }else{
            $interface->assign('showHidden', false);
        }
        $suggestion->orderBy('enteredOn');
        $suggestion->find();
        $suggestionList = array();
        while ($suggestion->fetch()){
            $suggestionList[$suggestion->suggestionId] = clone $suggestion;
        }
        $interface->assign('suggestions', $suggestionList);
        
        $interface->setTemplate('userSuggestions.tpl');
        $interface->setPageTitle('User Suggestions');
        $interface->display('layout.tpl');
        
    }
    
    function getAllowableRoles(){
        return array('opacAdmin');
    }
    
}