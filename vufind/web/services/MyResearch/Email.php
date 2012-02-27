<?php
/**
 * Email action for MyResearch module.
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
require_once 'sys/Mailer.php';
require_once 'sys/Language.php';
require_once 'services/MyResearch/MyResearch.php';
require_once 'RecordDrivers/Factory.php';

/**
 * Email action for MyResearch module.
 *
 * @category VuFind
 * @package  Controller_MyResearch
 * @author   Luke O'Sullivan <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
class Email extends MyResearch
{
    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        // Do not require login for email:
        parent::__construct(true);
    }

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

        if (isset($_POST['submit'])) {
            $this->_processSubmit();
        }

        // Display Page
        if (isset($_GET['lightbox'])) {
            return $this->_processLightbox();
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
        global $interface;
        global $configArray;

        if (isset($_POST['ids'])) {
            $url = $configArray['Site']['url'] . "/Search/Results?lookfor=" .
                urlencode(implode($_POST['ids'], " ")) . "&type=ids";
            $result = $this->sendEmail(
                $url, $_POST['to'], $_POST['from'], $_POST['message']
            );

            if (!PEAR::isError($result)) {
                $this->followupUrl .= "?infoMsg=" . urlencode("fav_email_success");
                header("Location: " . $this->followupUrl);
                exit();
            } else {
                // Assign Error Message and Available Data
                $this->errorMsg = $result->getMessage();
                $interface->assign('formTo', $_POST['to']);
                $interface->assign('formFrom', $_POST['from']);
                $interface->assign('formMessage', $_POST['message']);
                $interface->assign('formIDS', $_POST['ids']);
            }
        } else {
            $this->errorMsg = 'fav_email_missing';
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
    private function _getEmailList($ids)
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
        global $interface;

        if (!empty($_POST['ids'])) {
            // Assign Item Info
            $interface->assign('title', $_GET['message']);
            $interface->assign('emailIDS', $_POST['ids']);
            $interface->assign('emailList', $this->_getEmailList($_POST['ids']));
            return $interface->fetch('MyResearch/email.tpl');
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
        global $interface;

        if (is_array($_POST['idsAll']) && isset($_POST['selectAll'])) {
            $idArray = $_POST['idsAll'];
        } else {
            $idArray  = $_POST['ids'];
        }
        if (is_array($idArray)) {
            // Assign Item Info
            $interface->assign('errorMsg', $this->errorMsg);
            $interface->assign('emailList', $this->_getEmailList($idArray));
            $interface->setPageTitle(translate('Email Selected Favorites'));
            $interface->assign('subTemplate', 'email.tpl');
            $interface->assign('emailIDS', $idArray);
            // If we're on a particular list, save the ID so we can redirect to
            // the appropriate page after sending the email.
            if (isset($_REQUEST['listID']) && !empty($_REQUEST['listID'])) {
                $interface->assign('listID', $_REQUEST['listID']);
            }
            $interface->setTemplate('view-alt.tpl');
            $interface->display('layout.tpl');

        } else {
            // Without IDS, we can't perform any operations
            header(
                "Location: " . $this->followupUrl . "?errorMsg=bulk_noitems_advice"
            );
            exit();
        }
    }

    /**
     * Send the MyResearch email.
     *
     * @param string $url     URL to include in message
     * @param string $to      Message recipient
     * @param string $from    Message sender
     * @param string $message Extra note to add to message
     *
     * @return mixed          Boolean true on success, PEAR_Error on failure.
     * @access public
     */
    public function sendEmail($url, $to, $from, $message)
    {
        global $interface;

        $subject = translate('Library Catalog Search Result');
        $interface->assign('from', $from);
        $interface->assign('message', $message);
        $interface->assign('msgUrl', $url);
        $body = $interface->fetch('Emails/share-link.tpl');

        $mail = new VuFindMailer();
        return $mail->send($to, $from, $subject, $body);
    }
}
?>
