<?php

/**
 * Support function -- merge the contents of two arrays parsed from ini files.
 *
 * @param   config_ini  The base config array.
 * @param   custom_ini  Overrides to apply on top of the base array.
 * @return  array       The merged results.
 */
function ini_merge($config_ini, $custom_ini)
{
	foreach ($custom_ini as $k => $v) {
		if (is_array($v)) {
			$config_ini[$k] = ini_merge($config_ini[$k], $custom_ini[$k]);
		} else {
			$config_ini[$k] = $v;
		}
	}
	return $config_ini;
}

/**
 * Support function -- load the main configuration options, overriding with
 * custom local settings if applicable.
 *
 * @return  array       The desired config.ini settings in array format.
 */
function readConfig()
{
	//Read default configuration file
	$configFile = '../../sites/default/conf/packaging_web_service.ini';
	$mainArray = parse_ini_file($configFile, true);
	
	global $servername;
	$serverUrl = $_SERVER['SERVER_NAME'];
	$server = $serverUrl;
	$serverParts = explode('.', $server);
	$servername = 'default';
	while (count($serverParts) > 0){
		$tmpServername = join('.', $serverParts);
		$configFile = "../../sites/$tmpServername/conf/config.ini";
		if (file_exists($configFile)){
			$serverArray = parse_ini_file($configFile, true);
			$mainArray = ini_merge($mainArray, $serverArray);
			$servername = $tmpServername;
		}
		array_shift($serverParts);
	}
	
	if ($mainArray == false){
		echo("Unable to parse configuration file $configFile, please check syntax");
	}
	//If we are accessing the site via a subdomain, need to preserve the subdomain
	if (isset($_SERVER['HTTPS'])){
		$mainArray['Site']['url'] = "https://" . $serverUrl;
	}else{
		$mainArray['Site']['url'] = "http://" . $serverUrl;
	}
	
	return $mainArray;
}