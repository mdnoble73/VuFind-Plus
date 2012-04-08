<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.image.php
 * Type:     function
 * Name:     css
 * Purpose:  Loads an image source from the appropriate theme
 *           directory.  Supports two parameters:
 *              filename (required) - file to load from
 *                  interface/themes/[theme]/images/ folder.
 * -------------------------------------------------------------
 */
function smarty_function_image($params, &$smarty)
{
	// Extract details from the config file and parameters so we can find CSS files:
	global $configArray;
	$path = $configArray['Site']['path'];
	$local = $configArray['Site']['local'];
	$themes = explode(',', $configArray['Site']['theme']);
	$themes[] = 'default';
	$filename = $params['filename'];

	// Loop through the available themes looking for the requested CSS file:
	$imgSrc = false;
	foreach ($themes as $theme) {
		$theme = trim($theme);

		// If the file exists on the local file system, set $css to the relative
		// path needed to link to it from the web interface.
		if (file_exists("{$local}/interface/themes/{$theme}/images/{$filename}")) {
			$imgSrc = "{$path}/interface/themes/{$theme}/images/{$filename}";
			break;
		}
	}

	// If we couldn't find the file, we shouldn't try to link to it:
	if (!$imgSrc) {
		return '';
	}

	// We found the file -- build the link tag:
	return $imgSrc;
}
?>