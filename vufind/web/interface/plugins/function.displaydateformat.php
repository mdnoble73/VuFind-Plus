<?php
/**
 * displaydateformat Smarty plugin
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
 * @package  Smarty_Plugins
 * @author   Luke O'Sullivan <l.osullivan@swansea.ac.uk>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://vufind.org/wiki/building_a_plugin Wiki
 */

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.displaydateformat.php
 * Type:     function
 * Name:     displaydateformat
 * Purpose:  Converts a date to a alaphetical help string
 * -------------------------------------------------------------
 *
 * @param array $params An optional keyed array containing [date], a due date string
 * to be formated and [replace], an array of characters to replace the numbers in
 * [date]
 *
 * @return string Alphabetical help string
 */
require_once 'sys/VuFindDate.php';
// @codingStandardsIgnoreStart
function smarty_function_displaydateformat($params, &$smarty)
{   // @codingStandardsIgnoreEnd
    $dateFormat = new VuFindDate();
    $search = array("1", "2", "3");

    if (!empty($params['replace']) && !empty($params['date'])) {
        $replace = $params['replace'];
        $dueDateHelpString = $params['date'];
    } else {
        $dueDateHelpString
            = $dateFormat->convertToDisplayDate("m-d-y", "11-22-3333");
        $replace = array(
            translate("date_month_placeholder"),
            translate("date_day_placeholder"),
            translate("date_year_placeholder")
        );
    }

    return str_replace($search, $replace, $dueDateHelpString);
}
?>
