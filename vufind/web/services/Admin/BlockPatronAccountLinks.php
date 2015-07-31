<?php

/**
 * Pika
 *
 * Author: Pascal Brammeier
 * Date: 7/30/2015
 *
 */

require_once ROOT_DIR . '/Action.php';
require_once ROOT_DIR . '/sys/Administration/BlockPatronAccountLink.php'; // Database object
require_once ROOT_DIR . '/services/Admin/ObjectEditor.php';

class Admin_BlockPatronAccountLinks extends ObjectEditor
{

	function getAllowableRoles()
	{
		return array('opacAdmin', 'libraryAdmin');
	}

	/**
	 * The class name of the object which is being edited
	 */
	function getObjectType()
	{
		return 'BlockPatronAccountLink';
	}

	/**
	 * The page name of the tool (typically the plural of the object)
	 */
	function getToolName()
	{
		return 'BlockPatronAccountLinks';
	}

	/**
	 * The title of the page to be displayed
	 */
	function getPageTitle()
	{
		return 'Block Patron Account Links';
	}

	/**
	 * Load all objects into an array keyed by the primary key
	 */
	function getAllObjects()
	{
		$object = new BlockPatronAccountLink();
		$object->find();
		$objectList = array();
		while ($object->fetch()){
			$objectList[$object->id] = clone $object;
		}
		return $objectList;
	}

	/**
	 * Define the properties which are editable for the object
	 * as well as how they should be treated while editing, and a description for the property
	 */
	function getObjectStructure()
	{
		return BlockPatronAccountLink::getObjectStructure();
	}

	/**
	 * The name of the column which defines this as unique
	 */
	function getPrimaryKeyColumn()
	{
		return 'id';
	}

	/**
	 * The id of the column which serves to join other columns
	 */
	function getIdKeyColumn()
	{
		return 'id';
	}


}