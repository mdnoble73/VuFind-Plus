<?php
/**
 * Export action for MyResearch module.
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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

/**
 * Export action for MyResearch module.
 *
 * @category VuFind
 * @package  Controller_MyResearch
 * @author   Luke O'Sullivan <vufind-tech@lists.sourceforge.net>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
class Export extends MyResearch
{
    /**
     * Constructor
     *
     * @access public
     */
    public function __construct()
    {
        // Do not require login for export:
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
        global $configArray;
        global $interface;

        $doExport = false;

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

        // Check for Session Info
        if (isset($_REQUEST['exportInit'])) {
            $doExport = $this->_exportInit();
        }

        if (!$doExport) {
            // Check for submit
            if (isset($_POST['submit'])) {
                $this->_processSubmit();
            }
            // Display
            if (isset($_GET['lightbox'])) {
                return $this->_processLightbox();
            } else {
                $this->_processNonLightbox();
            }
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
        if (isset($_POST['format']) && isset($_POST['ids'])) {
            $_SESSION['exportIDS'] =  $_POST['ids'];
            $_SESSION['exportFormat'] = $_POST['format'];

            if ($_SESSION['exportIDS'] && $_SESSION['exportFormat']) {
                header(
                    "Location: " . $this->followupUrl .
                    "?infoMsg=export_success&showExport=true"
                );
                exit();
            } else {
                $this->errorMsg = 'bulk_fail';
            }
        } else {
            $this->errorMsg = 'export_missing';
        }
    }

    /**
     * Support method - display appropriate headers for the export.
     *
     * @param string $type The content-type value.
     * @param string $name The filename of the output.
     *
     * @return void
     * @access private
     */
    private function _exportHeaders($type, $name)
    {
        // For some reason, IE has trouble handling content types under SSL
        // (possibly only when self-signed certificates are involved -- further
        // testing is needed).  For now, as a work-around, let's always use the
        // text/plain content type when we're dealing with IE and SSL -- the
        // file extension should still allow the browser to do the right thing.
        if ($_SERVER['HTTPS'] == 'on'
            && strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')
        ) {
            $type = 'text/plain';
        }
        header('Content-type: ' . $type);
        header("Content-Disposition: attachment; filename=\"{$name}\";");
    }

    /**
     * Support method - export records based on settings saved in the session
     * (generally by a prior AJAX call).
     *
     * @return void
     * @access private
     */
    private function _exportInit()
    {
        global $configArray;
        global $interface;

        // Check for essentials
        $ids = $_SESSION['exportIDS'];
        $format = $_SESSION['exportFormat'];
        if (isset($format) && is_array($ids)) {
            $result = $this->exportAll($format, $ids);
            if ($result && !PEAR::isError($result)
                && !empty($result['exportDetails'])
            ) {
                $export = true;
                switch(strtolower($format)) {
                case 'bibtex':
                    $this->_exportHeaders(
                        'application/x-bibtex', 'VuFindExport.bibtex'
                    );
                    break;
                case 'endnote':
                    $this->_exportHeaders(
                        'application/x-endnote-refer', 'VuFindExport.enw'
                    );
                    break;
                case 'marc':
                    $this->_exportHeaders('application/MARC', 'VuFindExport.mrc');
                    break;
                default:
                    $export = false;
                }
            }

            if ($export) {
                $interface->assign('bulk', $result['exportDetails']);
                $interface->display('MyResearch/export/bulk.tpl');
                return true;
            } else {
                $this->errorMsg = 'bulk_fail';
            }
        } else {
            // Missing Vital Information
            $this->errorMsg = 'export_missing';
        }
        return false;
    }

    /**
     * Support method -- get details about records based on an array of IDs.
     *
     * @param array $ids IDs to look up.
     *
     * @return array
     * @access private
     */
    private function _getExportList($ids)
    {
        $exportList = array();

        foreach ($ids as $id) {
            $record = $this->db->getRecord($id);
            $exportList[] = array(
                'id'      => $id,
                'isbn'    => $record['isbn'],
                'author'  => $record['author'],
                'title'   => $record['title'],
                'format'  => $record['format']
            );
        }

        return $exportList;
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

        if (!empty($_POST['ids'])) {
            // Assign Item Info
            $interface->assign('exportIDS', $_POST['ids']);
            $interface->assign('exportList', $this->_getExportList($_POST['ids']));
            $interface->assign('title', $_GET['message']);
            return $interface->fetch('MyResearch/export.tpl');
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

        // Assign IDs
        if (isset($_POST['selectAll']) && is_array($_POST['idsAll'])) {
            $ids = $_POST['idsAll'];
        } else if (isset($_POST['ids'])) {
            $ids = $_POST['ids'];
        }
        $_POST['ids'] = "";
        // Check we have an array of IDS
        if (is_array($ids)) {
            // Assign Item Info
            $interface->assign('errorMsg', $this->errorMsg);
            $interface->assign('infoMsg', $this->infoMsg);
            $interface->setPageTitle(translate('Export Favorites'));
            $interface->assign('subTemplate', 'export.tpl');
            $interface->assign('exportIDS', $ids);
            $interface->assign('exportList', $this->_getExportList($ids));
            // If we're on a particular list, save the ID so we can redirect to
            // the appropriate page after exporting.
            if (isset($_REQUEST['listID']) && !empty($_REQUEST['listID'])) {
                $interface->assign('listID', $_REQUEST['listID']);
            }
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
     * Perform a bulk export operation.
     *
     * @param string $format Format to export (should be a valid value for the
     * getExport method of the record driver)
     * @param array  $ids    IDs to export
     *
     * @return array         Exported documents
     * @access public
     */
    public function exportAll($format, $ids)
    {
        global $interface;
        global $configArray;

        $exportDetails = array();
        $errorMsgDetails = array();

        foreach ($ids as $id) {
            // Retrieve the record from the index
            if (!($record = $this->db->getRecord($id))) {
                $errorMsgDetails[] = $id;
            } else {
                $recordDetails = RecordDriverFactory::initRecordDriver($record);
                // Assign core metadata to be sure export has all necessary values
                // available:
                $recordDetails->getCoreMetadata();
                $result = $recordDetails->getExport($format);
                if (!empty($result)) {
                    $exportDetails[] = $interface->fetch($result);
                } else {
                    $errorMsgDetails[] = $id;
                }
            }
        }
        $results = array(
            'exportDetails' => $exportDetails,
            'errorDetails' => $errorMsgDetails
        );
        return $results;
    }
}
?>
