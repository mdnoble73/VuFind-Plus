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

require_once "Action.php";

class Login extends Action
{
    function __construct()
    {
    }

    function launch($msg = null)
    {
        global $interface;
        global $configArray;
        global $module;
        global $action;

        // We should never access this module directly -- this is called by other
        // actions as a support function.  If accessed directly, just redirect to
        // the MyResearch home page.
        if ($module == 'Admin' && $action == 'Login') {
            header('Location: Home');
            die();
        }

        // Assign the followup task to come back to after they login -- note that
        //     we need to check for a pre-existing followup task in case we've
        //     looped back here due to an error (bad username/password, etc.).
        $followup = isset($_REQUEST['followup']) ? $_REQUEST['followup'] : $action;

        // Don't go to the trouble if we're just logging in to the Home action
        if ($followup != 'Home') {
            $interface->assign('followup', $followup);
            $interface->assign('followupModule', isset($_REQUEST['followupModule']) ? 
                $_REQUEST['followupModule'] : $module);

            $interface->assign('followupAction', isset($_REQUEST['followupAction']) ? 
                $_REQUEST['followupAction'] : 'Home');

        }
        $interface->assign('message', $msg);
        if (isset($_REQUEST['login'])) {
            $interface->assign('login', $_REQUEST['login']);
        }
        $interface->setTemplate('login.tpl');
        $interface->display('layout.tpl');
    }
}