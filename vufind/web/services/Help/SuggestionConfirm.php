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
require_once('recaptcha/recaptchalib.php');


class SuggestionConfirm extends Action
{
    function launch()
    {
        global $interface;
        global $configArray;
        $interface->setPageTitle('Thank You');
        $interface->assign('subTemplate', 'suggestion-confirm.tpl');
        $interface->setTemplate('view-alt.tpl');
        $interface->display('layout.tpl', 'SuggestionConfirm');
    }
}