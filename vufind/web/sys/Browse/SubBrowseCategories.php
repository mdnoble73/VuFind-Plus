<?php

/**
 *  Class for managing sub-categories of Browse Categories
 *
 * @category Pika
 * @author Pascal Brammeier <pascal@marmot.org>
 * Date: 6/3/2015
 *
 */
require_once 'DB/DataObject.php';

class SubBrowseCategories extends DB_DataObject {
	public $__table = 'browse_category_subcategories';
	public
		$id,
		$weight,
		$browseCategoryId,
		$subCategoryId;

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('SubBrowseCategories',$k,$v); }
// required component for all classes that extend DB_DataObject

	static function getObjectStructure(){
		require_once ROOT_DIR . '/sys/Browse/BrowseCategory.php';
		$browseCategories = new BrowseCategory();
		$browseCategories->orderBy('label');
		$browseCategories->find();
		$browseCategoryList = array();
		while($browseCategories->fetch()){
			$browseCategoryList[$browseCategories->id] = $browseCategories->label . " ({$browseCategories->textId})";
		}
		$structure = array(
			'id' => array('property'=>'id', 'type'=>'label', 'label'=>'Id', 'description'=>'The unique id of the sub-category row within the database'),
			'browseCategoryId' => array('property'=>'browseCategoryId', 'type'=>'label', 'label'=>'Browse Category', 'description'=>'The parent browse category'),
//			'browseCategoryId' => array('property'=>'browseCategoryId', 'type'=>'enum', 'values'=>$browseCategoryList, 'label'=>'Browse Category', 'description'=>'The parent browse category'),
			'subCategoryId'    => array('property'=>'subCategoryId', 'type'=>'enum', 'values'=>$browseCategoryList, 'label'=>'Sub-Category', 'description'=>'The sub-category of the parent browse category'),
			'weight' => array('property' => 'weight', 'type' => 'numeric', 'label' => 'Weight', 'weight' => 'Defines the order of the sub-categories .  Lower weights are displayed to the left of the screen.', 'required'=> true),

		);
		// commented this out until it becomes needed (Object Editor Listing functions)
//		foreach ($structure as $fieldName => $field){
//			$field['propertyOld'] = $field['property'] . 'Old';
//			$structure[$fieldName] = $field;
//		}
		return $structure;
	}

}