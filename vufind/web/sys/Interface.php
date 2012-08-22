<?php
/**
 *
 * Copyright (C) Villanova University 2007.
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
 */

require_once 'Smarty/Smarty.class.php';
require_once 'sys/mobile_device_detect.php';

// Smarty Extension class
class UInterface extends Smarty
{
	public $lang;
	private $vufindTheme;   // which theme(s) are active?
	private $themes; // The themes that are active
	private $isMobile = false;
	private $url;

	function UInterface()
	{
		global $configArray;
		global $timer;

		$local = $configArray['Site']['local'];
		$this->vufindTheme = $configArray['Site']['theme'];

		// Use mobile theme for mobile devices (if enabled in config.ini)
		if (isset($configArray['Site']['mobile_theme'])) {
			// If the user is overriding the UI setting, store that:
			if (isset($_GET['ui'])) {
				$_COOKIE['ui'] = $_GET['ui'];
				setcookie('ui', $_GET['ui'], null, '/');
				// If we don't already have a UI setting, detect if we're on a mobile
				// and store the result in a cookie so we don't waste time doing the
				// detection routine on every page:
			} else if (!isset($_COOKIE['ui'])) {
				$_COOKIE['ui'] = mobile_device_detect() ? 'mobile' : 'standard';
				setcookie('ui', $_COOKIE['ui'], null, '/');
			}
			// If we're mobile, override the standard theme with the mobile one:
			if ($_COOKIE['ui'] == 'mobile') {
				//Add library specific themes after the mobile theme so we can get images
				$this->vufindTheme = $configArray['Site']['mobile_theme'] . "," . $this->vufindTheme;

				$this->isMobile = true;
			}
		}

		// Check to see if multiple themes were requested; if so, build an array,
		// otherwise, store a single string.
		$themeArray = explode(',', $this->vufindTheme);
		//Make sure we always fall back to the default theme so a template does not have to be overridden.
		$themeArray[] = 'default';
		if (count($themeArray) > 1) {
			$this->template_dir = array();
			foreach ($themeArray as $currentTheme) {
				$currentTheme = trim($currentTheme);
				$this->template_dir[] = "$local/interface/themes/$currentTheme";
			}
		} else {
			$this->template_dir  = "$local/interface/themes/{$this->vufindTheme}";
		}
		$this->themes = $themeArray;
		$timer->logTime('Set theme');

		// Create an MD5 hash of the theme name -- this will ensure that it's a
		// writeable directory name (since some config.ini settings may include
		// problem characters like commas or whitespace).
		$md5 = md5($this->vufindTheme);
		$this->compile_dir   = "$local/interface/compile/$md5";
		if (!is_dir($this->compile_dir)) {
			if (!mkdir($this->compile_dir)){
				echo("Could not create compile directory {$this->compile_dir}");
				die();
			}
		}
		$this->cache_dir     = "$local/interface/cache/$md5";
		if (!is_dir($this->cache_dir)) {
			if (!mkdir($this->cache_dir)){
				echo("Could not create cache directory {$this->cache_dir}");
				die();
			}
		}
		$this->plugins_dir   = array('plugins', "$local/interface/plugins");
		$this->caching       = false;
		$this->debug         = true;
		$this->compile_check = true;

		unset($local);

		$this->register_function('translate', 'translate');
		$this->register_function('char', 'char');

		$this->assign('site', $configArray['Site']);
		$this->assign('path', $configArray['Site']['path']);
		$defaultConfig = $configArray['Site']['path'];
		$url = $_SERVER['SERVER_NAME'];
		if (isset($_SERVER['HTTPS'])){
			$url = "https://" . $url;
		}else{
			$url = "http://" . $url;
		}
		if (strlen($configArray['Site']['path']) > 0){
			$url .= '/' . $configArray['Site']['path'];
		}
		$this->url = $url;
		$this->assign('template_dir',$this->template_dir);
		$this->assign('url', $url);
		$this->assign('coverUrl', $configArray['Site']['coverUrl']);
		$this->assign('consolidateCss', isset($configArray['Site']['consolidateCss']) ? $configArray['Site']['consolidateCss'] : false);
		$this->assign('consolidateJs', isset($configArray['Site']['consolidateJs']) ? $configArray['Site']['consolidateJs'] : false);
		$this->assign('fullPath', str_replace('&', '&amp;', $_SERVER['REQUEST_URI']));
		$this->assign('requestHasParams', strpos($_SERVER['REQUEST_URI'], '?') > 0);
		$this->assign('supportEmail', $configArray['Site']['email']);
		$this->assign('libraryName', $configArray['Site']['title']);
		$this->assign('theme', $this->vufindTheme);
		$timer->logTime('Basic configuration');

		if (isset($configArray['OpenURL']) && isset($configArray['OpenURL']['url'])) {
			// Trim off any parameters (for legacy compatibility -- default config
			// used to include extraneous parameters):
			list($base) = explode('?', $configArray['OpenURL']['url']);
		} else {
			$base = false;
		}
		$this->assign('openUrlBase', empty($base) ? false : $base);

		// Other OpenURL settings:
		$this->assign('openUrlWindow',
		empty($configArray['OpenURL']['window_settings']) ?
		false : $configArray['OpenURL']['window_settings']);
		$this->assign('openUrlGraphic', empty($configArray['OpenURL']['graphic']) ?
		false : $configArray['OpenURL']['graphic']);
		$this->assign('openUrlGraphicWidth',
		empty($configArray['OpenURL']['graphic_width']) ?
		false : $configArray['OpenURL']['graphic_width']);
		$this->assign('openUrlGraphicHeight',
		empty($configArray['OpenURL']['graphic_height']) ?
		false : $configArray['OpenURL']['graphic_height']);

		$this->assign('currentTab', 'Search');

		$this->assign('authMethod', $configArray['Authentication']['method']);

		if ($configArray['Authentication']['method'] == 'Shibboleth') {
			if(!isset($configArray['Shibboleth']['login']) || !isset($configArray['Shibboleth']['target'])){
				throw new Exception('Missing parameter in the config.ini. Check if ' .
                                       'the parameters login and target are set.' );
			}

			$sessionInitiator = $configArray['Shibboleth']['login'] . '?target=' . $configArray['Shibboleth']['target'];

			if(isset($configArray['Shibboleth']['provider_id'])) {
				$sessionInitiator = $sessionInitiator . '&providerId=' . $configArray['Shibboleth']['provider_id'];
			}

			$this->assign('sessionInitiator', $sessionInitiator);

		}
	}

	public function getUrl(){
		return $this->url;
	}

	/**
	 * Get the current active theme setting.
	 *
	 * @access  public
	 * @return  string
	 */
	public function getVuFindTheme()
	{
		return $this->vufindTheme;
	}

	/*
	 * Get a list of themes that are active in the interface
	 *
	 * @return array
	 */
	public function getThemes(){
		return $this->themes;
	}

	function setTemplate($tpl)
	{
		$this->assign('pageTemplate', $tpl);
	}

	function setPageTitle($title)
	{
		//Marmot override, add the name of the site to the title unless we are using the mobile interface.
		global $configArray;
		$this->assign('shortPageTitle', translate($title));
		if ($this->isMobile){
			$this->assign('pageTitle', translate($title));
		}else{
			$this->assign('pageTitle', $configArray['Site']['title'] . ' | ' . translate($title));
		}
	}

	function getLanguage()
	{
		return $this->lang;
	}

	function setLanguage($lang)
	{
		global $configArray;

		$this->lang = $lang;
		$this->assign('userLang', $lang);
		$this->assign('allLangs', $configArray['Languages']);
	}
	/**
	 * executes & returns or displays the template results
	 *
	 * @param string $resource_name
	 * @param string $cache_id
	 * @param string $compile_id
	 * @param boolean $display
	 */
	function fetch($resource_name, $cache_id = null, $compile_id = null, $display = false)
	{
		global $timer;
		$resource = parent::fetch($resource_name, $cache_id, $compile_id, $display);
		$timer->logTime("Finished fetching $resource_name");
		return $resource;
	}

	public function isMobile(){
		return $this->isMobile;
	}
}

function translate($params) {
	global $translator;

	// If no translator exists yet, create one -- this may be necessary if we
	// encounter a failure before we are able to load the global translator
	// object.
	if (!is_object($translator)) {
		global $configArray;

		$translator = new I18N_Translator('lang', $configArray['Site']['language'],
		$configArray['System']['missingTranslations']);
	}
	if (is_array($params)) {
		return $translator->translate($params['text']);
	} else {
		return $translator->translate($params);
	}
}

function char($params) {
	extract($params);
	return chr($int);
}