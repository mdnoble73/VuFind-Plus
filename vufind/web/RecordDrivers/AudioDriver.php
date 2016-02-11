<?php

/**
 * Description goes here
 *
 * @category VuFind-Plus-2014
 * @author Mark Noble <mark@marmot.org>
 * Date: 2/10/2016
 * Time: 9:17 PM
 */
class AudioDriver extends IslandoraDriver {

	public function getViewAction() {
		return 'Audio';
	}
}