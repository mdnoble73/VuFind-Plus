<?php
/**
 * XSLT importer support methods.
 *
 * PHP version 5
 *
 * Copyright (c) Demian Katz 2010.
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
 * @package  Utilities
 * @author   Andrew S. Nagy <vufind-tech@lists.sourceforge.net>
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/importing_records Wiki
 */

// set up util environment
$importPath = dirname(__FILE__);
require_once $importPath . '/../util/util.inc.php';

require_once 'sys/Proxy_Request.php';
require_once 'sys/ConnectionManager.php';

// Read Config file
$configArray = readConfig();

// Set default timezone
date_default_timezone_set($configArray['Site']['timezone']);

// Save program name before we start shifting the $argv array:
$progName = basename($argv[0]);

// Process switches:
$switchError = false;
$testMode = false;
$index = 'Solr';
while (isset($argv[1]) && substr($argv[1], 0, 1) == '-') {
    switch ($argv[1]) {
    case '--test-only';
        $testMode = true;
        break;
    case '--index';
        if (!isset($argv[2])) {
            echo "Missing parameter to --index switch!\n\n";
            $switchError = true;
        } else {
            $index = $argv[2];
            array_shift($argv);
        }
        break;
    default:
        echo "Unrecognized switch: {$argv[1]}\n\n";
        $switchError = true;
        break;
    }
    array_shift($argv);
}

// Display help message if parameters missing:
if (!isset($argv[2]) || $switchError) {
    echo "Usage: {$progName} [--test-only] [--index <type>] XML_file " .
        "properties_file\n" .
        "\tXML_file - source file to index\n" .
        "\tproperties_file - import configuration file\n" .
        "If the optional --test-only flag is set, transformed XML will be " .
        "displayed\non screen for debugging purposes, but it will not be " .
        "indexed into VuFind.\n\n" .
        "If the optional --index parameter is set, it must be followed by " .
        "the name of\na class for accessing Solr; it defaults to the standard " .
        "Solr class, but could\nbe overridden with, for example, SolrAuth to " .
        "load authority records.\n\n" .
        "Note: See vudl.properties and ojs.properties for configuration " . 
        "examples.\n";
    exit(1);
}

// Setup Local Database Connection
ConnectionManager::connectToDatabase();

// Process the file (or die trying):
$xml = processXSLT($argv[1], $argv[2]);
if (!$xml) {
    exit(1);
}

// Save the results (or just display them, if in test mode):
if (!$testMode) {
    $solr = ConnectionManager::connectToIndex($index);
    $result = $solr->saveRecord($xml);
    if (!PEAR::isError($result)) {
        echo "Successfully imported {$argv[1]}...\n";
        exit(0);
    } else {
        echo "Fatal error: " . $result->getMessage() . "\n";
        exit(1);
    }
} else {
    echo $xml . "\n";
}

/**
 * Main function -- transform $xmlFile using the provided $properties configuration.
 *
 * @param string $xmlFile    XML file to transform.
 * @param string $properties Properties file.
 *
 * @return  mixed            Transformed XML (false on error).
 */
function processXSLT($xmlFile, $properties)
{
    global $importPath;

    // Load properties file:
    if (!file_exists($properties)) {
        echo "Cannot load properties file: {$properties}.\n";
        return false;
    }
    $options = parse_ini_file($properties, true);

    // Make sure required parameter is set:
    if (!isset($options['General']['xslt'])) {
        echo "Properties file ({$properties}) is missing General/xslt setting.\n";
        return false;
    }
    $xslFile = $importPath . '/xsl/' . $options['General']['xslt'];

    // Initialize the XSL processor:
    $xsl = initializeXSL($options);
    
    // Load up the style sheet
    $style = new DOMDocument;
    if (!$style->load($xslFile)) {
        echo "Problem loading XSL file: {$xslFile}.\n";
        return false;
    }
    $xsl->importStyleSheet($style);

    // Load up the XML document
    $xml = new DOMDocument;
    if (!$xml->load($xmlFile)) {
        echo "Problem loading XML file: {$xmlFile}.\n";
        return false;
    }

    // Process and return the XML through the style sheet
    $result = $xsl->transformToXML($xml);
    if (!$result) {
        echo "Problem transforming XML.\n";
    }
    return $result;
}

/**
 * Support function -- initialize an XSLT processor using settings from the
 * user-specified properties file.
 *
 * @param array $options Parsed contents of properties file.
 *
 * @return object        XSLT processor.
 */
function initializeXSL($options)
{
    global $importPath;

    // Prepare an XSLT processor and pass it some variables
    $xsl = new XSLTProcessor();

    // Register PHP functions, if specified:
    if (isset($options['General']['php_function'])) {
        $functions = is_array($options['General']['php_function']) ?
            $options['General']['php_function'] :
            array($options['General']['php_function']);
        foreach ($functions as $function) {
            $xsl->registerPHPFunctions($function);
        }
    }

    // Register custom classes, if specified:
    if (isset($options['General']['custom_class'])) {
        $classes = is_array($options['General']['custom_class']) ?
            $options['General']['custom_class'] :
            array($options['General']['custom_class']);
        foreach ($classes as $class) {
            // Find the file containing the class; if necessary, be forgiving
            // about filename case.
            $fullPath = $importPath . '/xsl/' . $class . '.php';
            if (!file_exists($fullPath)) {
                $fullPath = $importPath . '/xsl/' . strtolower($class) . '.php';
            }
            include_once $fullPath;
            $methods = get_class_methods($class);
            foreach ($methods as $method) {
                $xsl->registerPHPFunctions($class . '::' . $method);
            }
        }
    }

    // Load parameters, if provided:
    if (isset($options['Parameters'])) {
        $xsl->setParameter('', $options['Parameters']);
    }
    
    return $xsl;
}
?>