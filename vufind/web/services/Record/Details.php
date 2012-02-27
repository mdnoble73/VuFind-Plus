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

require_once 'Record.php';

class Details extends Record
{
    function launch()
    {
        global $interface;
    
        if (isset($_GET['lightbox'])) {
            $interface->assign('staffDetails', $this->recordDriver->getStaffView());
            return $interface->fetch('Record/view-details.tpl');
        }
        
        global $library;
        if (isset($library)){
            $interface->assign('showTextThis', $library->showTextThis);
            $interface->assign('showEmailThis', $library->showEmailThis);
            $interface->assign('showFavorites', $library->showFavorites);
            $interface->assign('linkToAmazon', $library->linkToAmazon);
            $interface->assign('enablePospectorIntegration', $library->enablePospectorIntegration);
        }else{
            $interface->assign('showTextThis', 1);
            $interface->assign('showEmailThis', 1);
            $interface->assign('showFavorites', 1);
            $interface->assign('linkToAmazon', 1);
            $interface->assign('enablePospectorIntegration', isset($configArray['Content']['Prospector']) && $configArray['Content']['Prospector'] == true ? 1 : 0);
        }
        if (!$interface->is_cached($this->cacheId)) {
            $interface->setPageTitle(translate('Staff View') . ': ' . $this->recordDriver->getBreadcrumb());

            $interface->assign('staffDetails', $this->recordDriver->getStaffView());
            $interface->assign('subTemplate', 'view-details.tpl');

            $interface->setTemplate('view-marc.tpl');
        }

        // Display Page
        $interface->display('layout.tpl', $this->cacheId);
    }
}

?>
