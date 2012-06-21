<?php
require_once 'sys/Solr.php';
require_once 'User_list.php';
require_once 'services/MyResearch/lib/FavoriteHandler.php';

/**
 * User List Solr Class
 *
 * Offers functionality for recording list data into Solr
 *
 * @version     $Revision: 1.13 $
 * @author      Andrew S. Nagy <andrew.nagy@villanova.edu>
 * @access      public
 */
class User_list_solr{
	private $mainIndex;
	private $backupIndex;
	public function __construct($host){
		$this->mainIndex = new Solr($host, 'biblio');
		$this->backupIndex = new Solr($host, 'biblio2');
	}

	/**
	 * Save a list to Solr
	 *
	 * @param User_list $list
	 */
	public function saveList($list){
		global $user;
		global $timer;

		$fullList = User_list::staticGet('id', $list->id);
		$timer->logTime('Loaded User list to save to solr');
		if ($fullList->public != 1){
			return false;
		}
		$resources = $fullList->getResources();
		$timer->logTime('loaded resources so it can be saved to solr');
		$doc = array();
		$doc['id'] = 'list' . $fullList->id;
		//$doc['id_sort'] = -1;
		$doc['recordtype'] = 'list';
		$doc['title'] = $fullList->title;
		$doc['title_proper'] = $fullList->title;
		$doc['title_sort'] = $fullList->title;
		$doc['format_category'] = 'Lists';
		$doc['format'] = 'List';
		$doc['description'] = $fullList->description;
		$doc['bib_suppression'] = 'notsuppressed';
		$doc['num_titles'] = count($resources);
		$doc['num_holdings'] = count($resources);
		//Boosting to get towards the top of results
		$doc['format_boost'] = 100;
		$doc['language_boost'] = 500;
		//Add the list of titles and authors to the content
		$contents = '';
		//Load the full resources from solr Utilize the favorites handler
		$favList = new FavoriteHandler($resources, $user, $fullList->id, false);
		$titlesList = $favList->getTitles();
		$timer->logTime('Loaded full title information to save to solr');

		foreach ($titlesList as $title){
			//Load the full record so we can get the title and author for the list
			$contents .= ' ' . $title['title'] . ' ' . (isset($title['author']) ? $title['author'] : '') ;
		}
		$doc['contents'] = $contents;
		$timer->logTime('Built Contents for the list');

		$xml = $this->mainIndex->getSaveXML($doc, false);
		$timer->logTime('Created XML to save to the main index');
		$savedToMainIndex = false;
		if ($this->mainIndex->saveRecord($xml)) {
			$this->mainIndex->commit();
			$savedToMainIndex = true;
		} else {
			return new PEAR_Error('Could not save list to main index');
		}
		$timer->logTime('Saved List to the main index');

		$savedToBackupIndex = false;
		if ($this->backupIndex->saveRecord($xml)) {
			$this->backupIndex->commit();
			$savedToBackupIndex = true;
		} else {
			return new PEAR_Error('Could not save list to backup index');
		}
		$timer->logTime('Saved List to the backup index');

		return $savedToMainIndex && $savedToBackupIndex;
	}

	/**
	 * Remove a list from the Solr Index
	 *
	 * @param User_list $list
	 */
	public function deleteList($list){
		if ($list->public != 1){
			//No need to delete because it is not public.
			return true;
		}
		$deletedFromMainIndex = false;
		if ($this->mainIndex->deleteRecord('list' . $list->id)) {
			$this->mainIndex->commit();
			$deletedFromMainIndex = true;
		} else {
			return new PEAR_Error('Could not save list to main index');
		}
		$deletedFromBackupIndex = false;
		if ($this->backupIndex->deleteRecord('list' . $list->id)) {
			$this->backupIndex->commit();
			$deletedFromBackupIndex = true;
		} else {
			return new PEAR_Error('Could not save list to main index');
		}
		return $deletedFromMainIndex && $deletedFromBackupIndex;
	}
}