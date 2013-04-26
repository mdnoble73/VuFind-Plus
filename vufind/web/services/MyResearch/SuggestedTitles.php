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
		global $configArray;
		global $interface;
		global $user;

		global $library;
		global $locationSingleton;
		$location = $locationSingleton->getActiveLocation();
		$showHoldButton = 1;
		$showHoldButtonInSearchResults = 1;
		$showRatings = 1;
		if (isset($library)){
			$showRatings = $library->showRatings;
		}
		$interface->assign('showRatings', $showRatings);
		if (isset($library) && $location != null){
			$interface->assign('showFavorites', $library->showFavorites);
			$interface->assign('showComments', $library->showComments);
			$showHoldButton = (($location->showHoldButton == 1) && ($library->showHoldButton == 1)) ? 1 : 0;
			$showHoldButtonInSearchResults = (($location->showHoldButton == 1) && ($library->showHoldButtonInSearchResults == 1)) ? 1 : 0;
		}else if ($location != null){
			$interface->assign('showFavorites', 1);
			$showHoldButton = $location->showHoldButton;
		}else if (isset($library)){
			$interface->assign('showFavorites', $library->showFavorites);
			$showHoldButton = $library->showHoldButton;
			$showHoldButtonInSearchResults = $library->showHoldButtonInSearchResults;
			$interface->assign('showComments', $library->showComments);
		}else{
			$interface->assign('showFavorites', 1);
			$interface->assign('showComments', 1);
		}
		if ($showHoldButton == 0){
			$showHoldButtonInSearchResults = 0;
		}
		$interface->assign('showHoldButton', $showHoldButtonInSearchResults);

		$suggestions = Suggestions::getSuggestions();

		$resourceList = array();
		if (is_array($suggestions)) {
			foreach($suggestions as $suggestion) {
				$recordDriver = RecordDriverFactory::initRecordDriver($suggestion['titleInfo']);
				$resourceEntry = $interface->fetch($recordDriver->getSearchResult());
				$resourceList[] = $resourceEntry;
			}
		}
		$interface->assign('resourceList', $resourceList);

		global $library;
		if (isset($library)){
			$interface->assign('showRatings', $library->showRatings);
		}else{
			$interface->assign('showRatings', 1);
		}

		//Check to see if the user has rated any titles
		$interface->assign('hasRatings', $user->hasRatings());

		$interface->setPageTitle('Recommended for you');
		$interface->setTemplate('suggestedTitles.tpl');
		$interface->display('layout.tpl');
	}

}