<?php
/**
 * Record Driver to control the display of eContent that is stored within the ILS.
 * Usage is restricted by DRM and/or a number of simultaneous checkouts.
 *
 * @category VuFind-Plus
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/7/14
 * Time: 10:00 AM
 */

require_once ROOT_DIR . '/RecordDrivers/MarcRecord.php';
class RestrictedEContentDriver extends MarcRecord{

} 