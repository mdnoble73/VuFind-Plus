<?php
/**
 * Delete action for MyResearch module
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

require_once 'Action.php';
require_once 'sys/Language.php';
require_once 'services/MyResearch/MyResearch.php';
require_once 'RecordDrivers/Factory.php';
require_once 'services/MyResearch/lib/FavoriteHandler.php';

/**
 * Delete action for MyResearch module
 *
 * @category VuFind
 * @package  Controller_MyResearch
 * @author   Luke O'Sullivan <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
class Delete extends MyResearch
{
    /**
     * Process parameters and display the page.
     *
     * @return void
     * @access public
     */
    public function launch()
    {
        global $configArray;
        global $interface;
        global $user;

        if (isset($_REQUEST['followup'])) {
            $this->followupUrl =  $configArray['Site']['url'] . "/".
                $_REQUEST['followupModule'];
            $this->followupUrl .= "/" . $_REQUEST['followupAction'];
        } else if (isset($_REQUEST['listID']) && !empty($_REQUEST['listID'])) {
            $this->followupUrl = $configArray['Site']['url'] .
                "/MyResearch/MyList/" . urlencode($_REQUEST['listID']);
        } else {
            $this->followupUrl = $configArray['Site']['url'] .
                "/MyResearch/Favorites";
        }

        // Check for submit
        if (isset($_POST['submit'])) {
            $this->_processSubmit();
        }

        // Display
        if (isset($_GET['lightbox'])) {
            $display = $this->_processLightbox();
            return $display;
        } else {
            $this->_processNonLightbox();
        }
    }

    /**
     * Support method - process incoming parameters.
     *
     * @return void
     * @access private
     */
    private function _processSubmit()
    {
        // Check for essentials
        if (isset($_POST['ids']) && is_array($_POST['ids'])) {
            $ids = $_POST['ids'];
            $listID = isset($_POST['listID'])?$_POST['listID']:false;
            $result = $this->deleteFavorites($ids, $listID);
            if (($result) && !empty($result['deleteDetails'])) {
                $this->infoMsg = $result['deleteDetails'];
                header(
                    "Location: " . $this->followupUrl . "?infoMsg=" .
                    urlencode($this->infoMsg)
                );
                exit();
            } else {
                $this->errorMsg = 'fav_delete_fail';
            }
        } else {
            // Missing Vital Information
            $this->errorMsg = 'fav_delete_missing';
        }
    }

    /**
     * Support method -- get details about records based on an array of IDs.
     *
     * @param array $ids IDs to look up.
     *
     * @return array
     * @access private
     */
    private function _getDeleteList($ids)
    {
        $list = array();

        foreach ($ids as $id) {
            $record = $this->db->getRecord($id);
            $list[] = array(
                'id'      => $id,
                'isbn'    => $record['isbn'],
                'author'  => $record['author'],
                'title'   => $record['title'],
                'format'  => $record['format']
            );
        }

        return $list;
    }

    /**
     * Support method - display content inside a lightbox.
     *
     * @return void
     * @access private
     */
    private function _processLightbox()
    {
        global $configArray;
        global $interface;
        global $user;

        if (isset($_POST['ids']) && !empty($_POST['ids'])) {
            $listID = isset($_GET['id'])?$_GET['id']:false;
            if ($listID) {
                // Fetch List object
                $list = User_list::staticGet($listID);
                // Send list to template so title/description can be displayed:
                $interface->assign('list', $list);
            }
            // Assign Item Info
            $interface->assign('deleteIDS', $_POST['ids']);
            $interface->assign('listID', $listID);
            $interface->assign('deleteList', $this->_getDeleteList($_POST['ids']));
            $interface->assign('title', $_GET['message']);
            return $interface->fetch('MyResearch/delete.tpl');
        } else {
            $interface->assign('title', translate('bulk_fail'));
            $interface->assign('errorMsg', $_GET['message']);
            return $interface->fetch('MyResearch/bulkError.tpl');
        }
    }

    /**
     * Support method - display content outside of a lightbox.
     *
     * @return void
     * @access private
     */
    private function _processNonLightbox()
    {
        global $configArray;
        global $interface;
        global $user;

        // Assign IDs
        if (isset($_POST['selectAll']) && is_array($_POST['idsAll'])) {
            $ids = $_POST['idsAll'];
        } else {
            $ids = $_POST['ids'];
        }
        // Check we have an array of IDS
        if (is_array($ids)) {
            $listID = isset($_POST['listID'])?$_POST['listID']:false;
            if ($listID) {
                // Fetch List object
                $list = User_list::staticGet($listID);
                // Send list to template so title/description can be displayed:
                $interface->assign('list', $list);
            }
            // Assign Item Info
            $interface->assign('listID', $listID);
            $interface->assign('errorMsg', $this->errorMsg);
            $interface->setPageTitle(translate('Delete Favorites'));
            $interface->assign('subTemplate', 'delete.tpl');
            $interface->assign('deleteIDS', $ids);
            $interface->assign('deleteList', $this->_getDeleteList($ids));
            $interface->setTemplate('view-alt.tpl');
            $interface->display('layout.tpl');
        } else {
            // Without an array of IDS, we can't perform any operations
            header(
                "Location: " . $this->followupUrl . "?errorMsg=bulk_noitems_advice"
            );
            exit();
        }
    }

    /**
     * Perform a bulk delete operation.
     *
     * @param array $ids    IDs to delete
     * @param int   $listID List to limit the delete operation to (false for no
     * limit).
     *
     * @return array        Status details.
     * @access public
     */
    public function deleteFavorites($ids, $listID = false)
    {
        global $configArray;
        global $interface;
        global $user;

        if ($listID && $listID != "") {
            $list = User_list::staticGet($listID);
            if ($user->id == $list->user_id) {
                $result = $list->removeResourcesById($ids);
            }
        } else {
            $result = $user->removeResourcesById($ids);
        }
        if ($result) {
            $this->infoMsg =  'fav_delete_success';
        }

        $results = array('deleteDetails' => $this->infoMsg);
        return $results;
    }
}
?>
