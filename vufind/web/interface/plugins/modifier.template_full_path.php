<?php
/**
 * template_full_path Smarty plugin
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
 * File:     modifier.template_full_path.php
 * Type:     modifier
 * Name:     template_full_path
 * Purpose:  Find a template from one of the themes being used
 *           and return its full path.
 *           Supports one parameter:
 *              filename (required) - template to look for 
 *              eg: MyResearch/footer-buttons.tpl
 * -------------------------------------------------------------
 *
 * @param string  $filename  template file name to look for
 *
 * @return mixed        The full path to the template file, false
 *                      if none exists.
 */ // @codingStandardsIgnoreStart
function smarty_modifier_template_full_path($filename)
{   // @codingStandardsIgnoreEnd
    // Extract details from the config file, Smarty interface 
    // so we can find the template file:
    global $configArray;
    global $interface;

    $path = $configArray['Site']['path'];
    $local = $configArray['Site']['local'];
    $themes = explode(',', $interface->getVuFindTheme());

    // loop through the themes and return the path of LAST theme in the list
    // that has the template file, this allows for themes to override
    // only a subset of template files
    for ($i = count($themes) - 1; $i >= 0; $i--) {
        $theme = $themes[$i];
        $file = "{$local}/interface/themes/{$theme}/{$filename}";
        if (file_exists($file)) {
            return $file;
        }
    }
    
    // nothing found
    return false;
}
?>