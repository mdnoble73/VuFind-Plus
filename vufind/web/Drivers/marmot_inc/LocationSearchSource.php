<?php
/**
 * Information about searches for a particular location
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 5/13/13
 * Time: 10:43 AM
 */
require_once 'SearchSource.php';
class LocationSearchSource extends SearchSource{
	public $locationId;
}