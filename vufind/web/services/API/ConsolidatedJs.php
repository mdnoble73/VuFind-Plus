<?php
/**
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
 */

require_once 'Action.php';

class ConsolidatedJs extends Action {

  function launch()
  {
    global $configArray;
    // Connect to Catalog
    $this->catalog = new CatalogConnection($configArray['Catalog']['driver']);

    //Add caching information with two week expiration
		$expires = 60*60*24*14; 
		Header ("Content-type: application/javascript");
		header("Cache-Control: maxage=".$expires);
		header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');

    $fullJs .= "\r\n/* jquery-1.5.1-min.js */\r\n";
    $fullJs .= file_get_contents('/js/jquery-1.7.1.min.js', true);
    $fullJs .= "\r\n/* event-min.js */\r\n";
    $fullJs .= file_get_contents('/js/yui/event-min.js', true);
    $fullJs .= "\r\n/* connection-min.js */\r\n";
    $fullJs .= file_get_contents('/js/yui/connection-min.js', true);
    $fullJs .= "\r\n/* dragdrop-min.js */\r\n";
    $fullJs .= file_get_contents('/js/yui/dragdrop-min.js', true);
    $fullJs .= "\r\n/* ajax.yui.js */\r\n";
    $fullJs .= file_get_contents('/js/ajax.yui.js', true);
    $fullJs .= "\r\n/* jquery-ui-1.8.11.custom.min.js */\r\n";
    $fullJs .= file_get_contents('/js/jqueryui/jquery-ui-1.8.11.custom.min.js', true);
    $fullJs .= "\r\n/* scripts.js */\r\n";
    $fullJs .= file_get_contents('/js/scripts.js', true);
    $fullJs .= "\r\n/* jquery.blockUI.js */\r\n";
    $fullJs .= file_get_contents('/js/bookcart/jquery.blockUI.js', true);
    $fullJs .= "\r\n/* json2.js */\r\n";
    $fullJs .= file_get_contents('/js/bookcart/json2.js', true);
    $fullJs .= "\r\n/* jquery.cookie.js */\r\n";
    $fullJs .= file_get_contents('/js/bookcart/jquery.cookie.js', true);
    $fullJs .= "\r\n/* bookcart.js */\r\n";
    $fullJs .= file_get_contents('/js/bookcart/bookcart.js', true);
    
    $fullJs .= "\r\n/* jquery.rater.js */\r\n";
    $fullJs .= file_get_contents('/js/starrating/jquery.rater.js', true);
    $fullJs .= "\r\n/* jquery.waitforimages.js */\r\n";
    $fullJs .= file_get_contents('/js/jquery.waitforimages.js', true);
    $fullJs .= "\r\n/* autofill.js */\r\n";
    $fullJs .= file_get_contents('/js/autofill.js', true);
    
    $fullJs .= "\r\n/* description.js */\r\n";
    $fullJs .= file_get_contents('/js/description.js', true);
    $fullJs .= "\r\n/* jquery.bgiframe.js */\r\n";
    $fullJs .= file_get_contents('/js/tooltip/lib/jquery.bgiframe.js', true);
    $fullJs .= "\r\n/* jquery.tooltip.js */\r\n";
    $fullJs .= file_get_contents('/js/tooltip/jquery.tooltip.js', true);
    $fullJs .= "\r\n/* title-scroller.js */\r\n";
    $fullJs .= file_get_contents('/js/title-scroller.js', true);
    $fullJs .= "\r\n/* record ajax.js */\r\n";
    $fullJs .= file_get_contents('/services/Record/ajax.js', true);
    $fullJs .= "\r\n/* search ajax.js */\r\n";
    $fullJs .= file_get_contents('/services/Search/ajax.js', true);
    
    $fullJs .= "\r\n/* overdrive.js */\r\n";
    $fullJs .= file_get_contents('/js/overdrive.js', true);
    
    echo $fullJs;
  }
}