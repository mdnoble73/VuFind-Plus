<?php
/**
 * VuDL Record View
 *
 * PHP version 5
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
 * @category VuFind
 * @package  Controller_VuDL
 * @author   David Lacy <david.lacy@villanova.edu>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
require_once 'Action.php';

/**
 * VuDL Record View
 *
 * @category VuFind
 * @package  Controller_VuDL
 * @author   David Lacy <david.lacy@villanova.edu>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_module Wiki
 */
class Record extends Action
{
    /**
     * Process incoming parameters and display the page.
     *
     * @return void
     * @access public
     */
    public function launch()
    {
        global $configArray;

        header('Content-Type: text/html; charset=ISO-8859-1');
        echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\">\n";

        // Load information on the requested record:
        // Setup Search Engine Connection
        $db = ConnectionManager::connectToIndex();

        // Retrieve the record from the index
        if (!($record = $db->getRecord($_REQUEST['id']))) {
            PEAR::raiseError(new PEAR_Error('Record Does Not Exist'));
        }
        if (strlen($record['fullrecord'])) {
            $xml = $record['fullrecord'];
            $result = simplexml_load_string($xml);
        }
        $url = isset($result->url) ? trim($result->url) : false;
        if (empty($url)) {
            PEAR::raiseError(new PEAR_Error('Not a VuDL Record'));
        }
        
        // Set up the XSLT processor:
        $xslt = new XSLTProcessor();

        // Register all non-private methods other than "launch" for access via XSLT:
        $class = get_class($this);
        $methods = get_class_methods($class);
        foreach ($methods as $method) {
            if ($method != 'launch' && substr($method, 0, 1) != '_') {
                $xslt->registerPHPFunctions($class . '::' . $method);
            }
        }

        $xsl= new DOMDocument();
        $xsl->load(dirname(__FILE__) . '/xsl/transform.xsl');

        $xslt->importStylesheet($xsl);

        $xslt->setParameter('', 'path', $configArray['Site']['path']);

        $sXml = file_get_contents($url);
        $XML = new DOMDocument();
        $XML->loadXML($sXml);
        $html = $xslt->transformToXML($XML);
        $html = str_replace('<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">', '', $html);
        $html = preg_replace('/<html[^>]*>/', '<html>', $html);
        echo $html;
    }

    /**
     * Get OCR content from a specified URL.
     *
     * @param string $url URL to load
     *
     * @return string
     * @access public
     */
    public static function getOCR($url)
    {
        if (strlen($url)) {
            return file_get_contents($url);
        }
        return;
    }

    /**
     * Standardize capitalization of a string.
     *
     * @param string $str String to capitalize
     *
     * @return string
     * @access public
     */
    public static function capitalization($str)
    {
        if (strlen($str)) {
            return ucfirst(strtolower($str));
        }
    }
}

?>
