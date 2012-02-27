<?php
/**
 * Bulk action for MyResearch module
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

require_once 'services/MyResearch/MyResearch.php';

/**
 * Bulk action for MyResearch module.  Note that this extends Action rather than
 * MyResearch -- we will rely on the various included modules to handle login control
 * rather than doing it here, since the requirements vary by action.
 *
 * @category VuFind
 * @package  Controller_MyResearch
 * @author   Luke O'Sullivan <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
class Bulk extends Action
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

        // Set FollowUp URL
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

        // Export
        if (isset($_REQUEST['export']) || isset($_REQUEST['exportInit'])) {
            include_once 'services/MyResearch/Export.php';
            $export = new Export();
            $export->launch();
        } else if (isset($_REQUEST['email'])) {
            // Email
            include_once 'services/MyResearch/Email.php';
            $email = new Email();
            $email->launch();
        } else if (isset($_REQUEST['delete'])) {
            // Delete
            include_once 'services/MyResearch/Delete.php';
            $delete = new Delete();
            $delete->launch();
        } else if (isset($_REQUEST['deleteList'])) {
            // Delete List
            include_once 'services/MyResearch/Confirm.php';
            $confirm = new Confirm();
            $confirm->launch();
        } else if (isset($_REQUEST['editList']) && isset($_POST['listID'])) {
            // Edit List
            $this->followupUrl = $configArray['Site']['url'] .
                "/MyResearch/EditList/" . $_POST['listID'];
            header("Location: " . $this->followupUrl);
            exit();
        } else if (isset($_REQUEST['sortResults'])) {
            // Sort with Javascript disabled
            include_once 'services/Search/SortResults.php';
            $sort = new SortResults();
            $sort->launch();
        } else {
            // If we get this far, no export has been matched or we have errors
            $this->followupUrl .= "?errorMsg=bulk_fail";
            header("Location: " . $this->followupUrl);
            exit();
        }
    }

}
?>
