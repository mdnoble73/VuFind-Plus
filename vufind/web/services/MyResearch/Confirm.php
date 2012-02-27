<?php
/**
 * Confirm action for MyResearch Module
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2011.
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
 * @category VuFind
 * @package  Controller_MyResearch
 * @author   Luke O'Sullivan <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */

require_once 'MyResearch.php';

/**
 * Confirm action for MyResearch Module
 *
 * @category VuFind
 * @package  Controller_MyResearch
 * @author   Luke O'Sullivan <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
class Confirm extends MyResearch
{
    /**
     * Process parameters and display the page.
     *
     * @return void
     * @access public
     */
    public function launch()
    {
        global $interface;
        global $configArray;

        // Delete List Confirmation
        if (isset($_POST['deleteList']) && isset($_POST['listID'])) {
            if ($_POST['confirm']) {
                include_once 'services/MyResearch/MyList.php';
                $myList = new MyList();
                $myList->launch();
            } else if ($_POST['cancel']) {
                $followupUrl =  $configArray['Site']['url'] .
                    "/MyResearch/MyList/" . urlencode($_POST['listID']);
                header(
                    "Location: " . $followupUrl . "?infoMsg=fav_list_delete_cancel"
                );
                exit();
            } else {
                $interface->assign('confirmAction', 'deleteList');
                $interface->assign('listID', $_POST['listID']);
                $interface->assign('listName', $_POST['listName']);
                $interface->setpageTitle('delete_list');
                $this->infoMsg = 'confirm_delete_list_text';
                return $this->_display();
            }
        }
        // If we get this far, we're missing some vital information
    }

    /**
     * Private support method -- display the confirmation dialog.
     *
     * @return void
     * @access private
     */
    private function _display()
    {
        global $interface;

        // Set Messages
        $interface->assign('infoMsg', $this->infoMsg);
        $interface->assign('errorMsg', $this->errorMsg);

        // Display Page
        if (isset($_GET['lightbox'])) {
            $interface->assign('title', $_GET['message']);
            return $interface->fetch('MyResearch/confirm.tpl');
        } else {
            $interface->assign('subTemplate', 'confirm.tpl');
            $interface->setTemplate('view-alt.tpl');
            $interface->display('layout.tpl');
        }
    }
}

?>
