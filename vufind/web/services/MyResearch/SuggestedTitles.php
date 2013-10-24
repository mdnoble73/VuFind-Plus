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

require_once ROOT_DIR . '/services/MyResearch/MyResearch.php';
require_once ROOT_DIR . '/services/MyResearch/lib/FavoriteHandler.php';
require_once ROOT_DIR . '/services/MyResearch/lib/Suggestions.php';

/**
 * MyResearch Home Page
 *
 * This controller needs some cleanup and organization.
 *
 * @version  $Revision: 1.27 $
 */
class SuggestedTitles extends MyResearch
{

	function launch()
	{
		global $interface;
		global $user;

		$suggestions = Suggestions::getSuggestions();

		$resourceList = array();
		$curIndex = 0;
		if (is_array($suggestions)) {
			foreach($suggestions as $suggestion) {
				$interface->assign('resultIndex', ++$curIndex);
				/** @var IndexRecord $recordDriver */
				$recordDriver = RecordDriverFactory::initRecordDriver($suggestion['titleInfo']);
				$resourceEntry = $interface->fetch($recordDriver->getSearchResult());
				$resourceList[] = $resourceEntry;
			}
		}
		$interface->assign('resourceList', $resourceList);

		//Check to see if the user has rated any titles
		$interface->assign('hasRatings', $user->hasRatings());

		$interface->setPageTitle('Recommended for you');
		$interface->setTemplate('suggestedTitles.tpl');
		$interface->display('layout.tpl');
	}

}