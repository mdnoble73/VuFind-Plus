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

require_once 'services/MyResearch/lib/Resource.php';
require_once 'services/MyResearch/lib/User.php';

class ViewEPub extends Action
{
    private $user;

    function __construct()
    {
        $this->user = UserAccount::isLoggedIn();
    }

    function launch()
    {
        global $interface;
        global $configArray;

        // Check if user is logged in
        if (!$this->user) {
            // Needed for "back to record" link in view-alt.tpl:
            $interface->assign('id', $_GET['id']);
            // Needed for login followup:
            $interface->assign('recordId', $_GET['id']);
            if (isset($_GET['lightbox'])) {
                $interface->assign('title', $_GET['message']);
                $interface->assign('message', 'You must be logged in first');
                $interface->assign('followup', true);
                $interface->assign('followupModule', 'Record');
                $interface->assign('followupAction', 'ViewEPub');
                return $interface->fetch('AJAX/login.tpl');
            } else {
                $interface->assign('followup', true);
                $interface->assign('followupModule', 'Record');
                $interface->assign('followupAction', 'ViewEPub');
                $interface->setPageTitle('You must be logged in first');
                $interface->assign('subTemplate', '../MyResearch/login.tpl');
                $interface->setTemplate('view-alt.tpl');
                $interface->display('layout.tpl', 'ViewEPub' . $_GET['id']);
            }
            exit();
        }else{
	        echo('<result><redirect>' . $configArray['Site']['path'] . '/EContent/' . urlencode($_GET['id']) . '/Viewer</redirect></result>');
        }
        exit();

    }
}
?>
