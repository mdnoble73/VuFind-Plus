<?php
require_once 'RecordDrivers/IndexRecord.php';

/**
 * List Record Driver
 *
 * This class is designed to handle List records.  Much of its functionality
 * is inherited from the default index-based driver.
 */
class ListRecord extends IndexRecord
{
    public function __construct($record)
    {
        // Call the parent's constructor...
        parent::__construct($record);
    }
    
    /**
     * Assign necessary Smarty variables and return a template name to
     * load in order to display a summary of the item suitable for use in
     * search results.
     *
     * @access  public
     * @return  string              Name of Smarty template file to display.
     */
    public function getSearchResult()
    {
        global $configArray;
        global $interface;

        $id = $this->getUniqueID();
        $interface->assign('summId', $id);
        $interface->assign('summShortId', substr($id, 4)); //Trim the list prefix for the short id
        $interface->assign('summTitle', $this->getTitle());
        $interface->assign('summDescription', $this->fields['description']);
        $interface->assign('summNumTitles', $this->fields['num_titles']);
        
       return 'RecordDrivers/List/result.tpl';
    }
}