<?php
/**
 * Imports Lists for a user from prior catalog (Millennium WebPAC, Encore, Etc).
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/26/14
 * Time: 10:35 PM
 */

require_once ROOT_DIR . '/services/MyResearch/MyResearch.php';
class ImportListsFromClassic extends MyResearch{

	/**
	 * Process parameters and display the page.
	 *
	 * @return void
	 * @access public
	 */
	public function launch()
	{
		global $interface;
		global $user;

		//Import Lists from the ILS
		/** @var MillenniumDriver $catalog */
		$catalog = $this->catalog;
		$results = $catalog->importListsFromIls();
		$interface->assign('importResults', $results);

		//Reload all lists for the user
		$listList = $user->getLists();
		$interface->assign('listList', $listList);

		$interface->setPageTitle('Import Results');
		$interface->setTemplate('favorites.tpl');

		$interface->display('layout.tpl');
	}

}