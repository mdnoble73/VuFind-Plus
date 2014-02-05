<?php
/**
 * A reference table to join grouped works and identifiers
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/4/14
 * Time: 10:59 AM
 */

class GroupedWorkIdentifierRef  extends DB_DataObject{
	public $__table = 'grouped_work_identifiers_ref';    // table name

	public $grouped_work_id;
	public $identifier_id;
} 