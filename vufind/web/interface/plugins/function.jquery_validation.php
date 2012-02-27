<?php
/**
 * jquery_validation function Smarty plugin
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
 * @package  Smarty_Plugins
 * @author   Tuan Nguyen <tuan@yorku.ca>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_plugin Wiki
 */

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.jquery_validation.php
 * Type:     function
 * Name:     jquery_validation
 * Purpose:  Print a formatted string so jquery metadata
 *           and validation plugins can understand.
 * -------------------------------------------------------------
 *
 * @param array  $params  Incoming parameter array
 * @param object &$smarty Smarty object
 *
 * @return string        jquery-formatted string
 */ // @codingStandardsIgnoreStart
function smarty_function_jquery_validation($params, &$smarty)
{   // @codingStandardsIgnoreEnd
    // jquery validation rules that this plugin currently supports
    $supported_rules = array('required', 'email', 'digits', 'equalTo', 
        'phoneUS', 'mobileUK');
    $messages = array();
    $rules = array();
    foreach ($supported_rules as $rule) {
        if (isset($params[$rule])) {
            switch($rule) {
            case 'equalTo':
                $rules[] = "equalTo:'" . $params['equalToField'] . "'";
                $messages[$rule] = translate($params[$rule]);
                break;
            default:
                $rules[] = "$rule:true";
                $messages[$rule] = translate($params[$rule]);
                break;
            }
        }
    }
    
    // format the output
    $output = '{' . implode(',', $rules) . ',messages:{';
    $first = true;
    foreach ($messages as $rule => $message) {
        if (!$first) {
            $output .= ',';
        }
        $output .= "$rule:'$message'";
        $first = false;
    }
    $output .= '}}';
    return $output; 
}
?>
