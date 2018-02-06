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
				'serverValidation' => 'validateSource',
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
				'serverValidation' => 'validateDestination',
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
		return $structure;
	}

	function validateSource(){
		//Setup validation return array
		$validationResults = array(
				'validatedOk' => true,
				'errors' => array(),
		);

		if (!preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', $this->sourceGroupedWorkId)){
			$validationResults = array(
					'validatedOk' => false,
					'errors' => array("The format of the source {$this->sourceGroupedWorkId} is not a valid work id"),
			);
		}
		return $validationResults;
	}

	function validateDestination(){
		//Setup validation return array
		$validationResults = array(
			'validatedOk' => true,
			'errors' => array(),
		);

		if ($this->destinationGroupedWorkId == $this->sourceGroupedWorkId){
			$validationResults = array(
				'validatedOk' => false,
				'errors' => array('The source work id cannot match the destination work id'),
			);
		}elseif (!preg_match('/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}/i', $this->destinationGroupedWorkId)){
			$validationResults = array(
					'validatedOk' => false,
					'errors' => array('The format of the destination is not a valid work id'),
			);
		}else{
			//Make sure the destination actually exists (not a big deal if the source doesn't since invalid ones will just be skipped)
			require_once ROOT_DIR . '/sys/Grouping/GroupedWork.php';
			$groupedWork = new GroupedWork();
			$groupedWork->permanent_id = $this->destinationGroupedWorkId;
			if (!$groupedWork->find(true)){
				$validationResults = array(
						'validatedOk' => false,
						'errors' => array('The destination work id does not exist'),
				);
			}
		}

		return $validationResults;
	}
} 