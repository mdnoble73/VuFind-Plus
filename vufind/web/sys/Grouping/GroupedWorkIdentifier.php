<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 12/6/13
 * Time: 9:51 AM
 */

class GroupedWorkIdentifier extends DB_DataObject{
	public $__table = 'grouped_work_identifiers';    // table name

	public $type;
	public $identifier;
}