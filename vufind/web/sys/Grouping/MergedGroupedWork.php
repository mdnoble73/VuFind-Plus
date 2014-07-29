<?php

/**
 * A Grouped Work that has been manually merged
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/29/14
 * Time: 3:14 PM
 */
class MergedGroupedWork extends DB_DataObject {
	public $__table = 'merged_grouped_works';
	public $id;
	public $sourceGroupedWorkId;
	public $destinationGroupedWorkId;
	public $notes;

	static function getObjectStructure() {
		$structure = array(
			array(
				'property' => 'id',
				'type' => 'hidden',
				'label' => 'Id',
				'description' => 'The unique id of the merged grouped work in the database',
				'storeDb' => true,
				'primaryKey' => true,
			),
			array(
				'property' => 'sourceGroupedWorkId',
				'type' => 'text',
				'size' => 36,
				'maxLength' => 36,
				'label' => 'Source Grouped Work Id',
				'description' => 'The id of the grouped work to be merged.',
				'storeDb' => true,
				'required' => true,
			),
			array(
				'property' => 'destinationGroupedWorkId',
				'type' => 'text',
				'size' => 36,
				'maxLength' => 36,
				'label' => 'Destination Grouped Work Id',
				'description' => 'The id of the grouped work to merge the work into.',
				'storeDb' => true,
				'required' => true,
			),
			array(
				'property' => 'notes',
				'type' => 'text',
				'size' => 250,
				'maxLength' => 250,
				'label' => 'Notes',
				'description' => 'Notes related to the merged work.',
				'storeDb' => true,
				'required' => true,
			),
		);
		foreach ($structure as $fieldName => $field){
			if (isset($field['property'])){
				$field['propertyOld'] = $field['property'] . 'Old';
				$structure[$fieldName] = $field;
			}
		}
		return $structure;
	}
} 