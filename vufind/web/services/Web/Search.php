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

class Search extends Action
{
	function launch()
	{
		global $configArray;
		global $interface;

		// Assign basic values
		$interface->assign('googleKey', $configArray['GoogleSearch']['key']);
		$interface->assign('domain', $configArray['GoogleSearch']['domain']);
		$interface->assign('lookfor', strip_tags($_GET['lookfor']));

		// Use the recommendations interface to get related catalog hits
		$recommendations = array();
		$cat = RecommendationFactory::initRecommendation('CatalogResults', null, 'lookfor');
		$cat->init();
		$cat->process();
		$recommendations[] = $cat->getTemplate();

		$interface->assign('recommendations', $recommendations);

		// Set up the page
		$interface->setPageTitle('Library Web Search');
		$interface->setTemplate('home.tpl');
		$interface->display('layout.tpl');
	}
}
