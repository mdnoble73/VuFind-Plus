<?php
/**
 * Table Definition for change_tracker
 *
 * PHP version 5
 *
 * Copyright (C) Villanova University 2010.
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
 * @category VuFind
 * @package  DB_DataObject
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://pear.php.net/package/DB_DataObject/ PEAR Documentation
 */
require_once 'DB/DataObject.php';

/**
 * Table Definition for change_tracker
 *
 * @category VuFind
 * @package  DB_DataObject
 * @author   Demian Katz <demian.katz@villanova.edu>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://pear.php.net/package/DB_DataObject/ PEAR Documentation
 */ // @codingStandardsIgnoreStart
class Change_tracker extends DB_DataObject
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'change_tracker';                  // table name
    public $core;                            // string(30)  not_null primary_key
    public $id;                              // string(64)  not_null primary_key
    public $first_indexed;                   // datetime(19)  binary
    public $last_indexed;                    // datetime(19)  binary
    public $last_record_change;              // datetime(19)  binary
    public $deleted;                         // datetime(19)  binary

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Change_tracker',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
    // @codingStandardsIgnoreEnd

    private $_dateFormat = 'Y-m-d H:i:s';   // date/time format for database

    /**
     * Support method for index() -- create a new row in the database.
     *
     * @param int $change The timestamp of the last record change.
     *
     * @return bool       True on success, false on failure.
     * @access private
     */
    private function _createRow($change)
    {
        // Save new values to the object:
        $this->first_indexed = $this->last_indexed = date($this->_dateFormat);
        $this->last_record_change = date($this->_dateFormat, $change);

        // Save new values to the database:
        return $this->insert();
    }

    /**
     * Support method for index() -- update an existing row in the database.
     *
     * @param int $change The timestamp of the last record change.
     *
     * @return bool       True on success, false on failure.
     * @access private
     */
    private function _updateRow($change)
    {
        // Save new values to the object:
        $this->last_indexed = date($this->_dateFormat);
        $this->last_record_change = date($this->_dateFormat, $change);

        // If first indexed is null, we're restoring a deleted record, so
        // we need to treat it as new -- we'll use the current time.
        if (empty($this->first_indexed)) {
            $this->first_indexed = $this->last_indexed;
        }

        // Make sure the record is "undeleted" if necessary:
        $this->deleted = '';

        // Save new values to the database:
        return $this->update();
    }

    /**
     * Update the change tracker table to indicate that a record has been deleted.
     *
     * This method should be called on a "fresh" Change_tracker object.  After the
     * method has been called, the current object will be populated with relevant
     * information about the specified record.
     *
     * @param string $core The Solr core holding the record.
     * @param string $id   The ID of the record being indexed.
     *
     * @return bool        True on success, false on failure.
     * @access public
     */
    public function markDeleted($core, $id)
    {
        // Set up the primary key so we can load information....
        $this->core = $core;
        $this->id = $id;

        // Check if the row already exists:
        $exists = $this->find(true);

        // If the record is already deleted, we don't need to do anything!
        if (!empty($this->deleted)) {
            return true;
        }

        // Save new value to the object:
        $this->deleted = date($this->_dateFormat);

        // Update the database:
        if ($exists) {
            return $this->update();
        } else {
            return $this->insert();
        }
    }

    /**
     * Update the change_tracker table to reflect that a record has been indexed.
     * We need to know the date of the last change to the record (independent of
     * its addition to the index) in order to tell the difference between a
     * reindex of a previously-encountered record and a genuine change.
     *
     * This method should be called on a "fresh" Change_tracker object.  After the
     * method has been called, the current object will be populated with relevant
     * information about the specified record.
     *
     * @param string $core   The Solr core holding the record.
     * @param string $id     The ID of the record being indexed.
     * @param int    $change The timestamp of the last record change.
     *
     * @return bool          True on success, false on failure.
     * @access public
     */
    public function index($core, $id, $change)
    {
        // Set up the primary key so we can load information....
        $this->core = $core;
        $this->id = $id;

        // No row?  Create one!
        if (!$this->find(true)) {
            return $this->_createRow($change);
        } else {
            // Row already exists?  See if it needs to be updated...

            // Are we restoring a previously deleted record, or was the stored
            // record change date before current record change date?  Either way,
            // we need to update the table!
            if (!empty($this->deleted)
                || strtotime($this->last_record_change) < $change
            ) {
                return $this->_updateRow($change);
            }
        }

        // If we got this far, no database activity was necessary, so we can
        // safely report success:
        return true;
    }
}
