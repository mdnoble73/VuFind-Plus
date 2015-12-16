<?php
/**
 * Record Driver to handle loading data for Hoopla Records
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/18/14
 * Time: 10:50 AM
 */

require_once ROOT_DIR . '/RecordDrivers/ExternalEContentDriver.php';
class SideLoadedRecord extends ExternalEContentDriver {
	/**
	 * Constructor.  We build the object using data from the Hoopla records stored on disk.
	 * Will be similar to a MarcRecord with slightly different functionality
	 *
	 * @param array|File_MARC_Record|string $record
	 * @param  GroupedWork $groupedWork;
	 * @access  public
	 */
	public function __construct($record, $groupedWork = null) {
		parent::__construct($record, $groupedWork);
	}

	function getRecordUrl(){
		global $configArray;
		$recordId = $this->getUniqueID();

		return $configArray['Site']['path'] . "/{$this->profileType}/$recordId";
	}
}