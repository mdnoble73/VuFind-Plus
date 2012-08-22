<?php
/**
 * This file consolidates CSS and JS for all themes based on the consolidation.ini file within
 * each theme (if any).
 *
 * Must have write access to the theme folder from Apache so it may be better to run from
 * development machine and then check the combined files in to git
 */
ini_set('display_errors', true);
error_reporting(E_ALL & ~E_DEPRECATED);

require_once 'Minify/JSMin.php';
function vufind_autoloader($class) {
	if (file_exists('sys/' . $class . '.php')){
		require_once 'sys/' . $class . '.php';
	}elseif (file_exists('services/MyResearch/lib/' . $class . '.php')){
		require_once 'services/MyResearch/lib/' . $class . '.php';
	}else{
		$altclass = str_replace('_', '/', $class) . '.php';
		require_once $altclass;
	}
}
spl_autoload_register('vufind_autoloader');

$themeDir = "./interface/themes";
$minify = true;
if (isset($_REQUEST['minify']) && $_REQUEST['minify'] == "false"){
	$minify = false;
}

if (is_dir($themeDir)){
	echo("Found themes directory<br/>");
	$dirHnd = opendir($themeDir);
	if ($dirHnd){
		$themes = array();
		while (($file = readdir($dirHnd)) !== false) {
			if ($file != "." && $file != ".." && is_dir($themeDir . '/'. $file)){
				$themePath = $themeDir . '/'. $file . '/';
				$themes[$file] = array(
					'name' => $file,
					'path' => $themePath,
					'hasConsolidationFile' => file_exists($themePath . 'consolidation.ini')
				);
				if ($themes[$file]['hasConsolidationFile']){
					$themes[$file]['settings'] = parse_ini_file($themePath . 'consolidation.ini', true);
				}
			}
		}

		//Determine which themes to update
		if (isset($_REQUEST['themes'])){
			if (is_array($_REQUEST['themes'])){
				$themesToUpdate = $_REQUEST['themes'];
			}else{
				$themesToUpdate = array();
				$themesToUpdate[] = $_REQUEST['themes'];
			}
		}else{
			$themesToUpdate = array();
			foreach ($themes as $themeName => $info){
				$themesToUpdate[] = $themeName;
			}
		}

		flush();
		ob_start();
		foreach ($themes as $themeName => $info){
			if ($info['hasConsolidationFile'] && in_array($themeName, $themesToUpdate)){
				$now = time();
				echo("Consolidating $themeName<br/>");
				flush();
				ob_flush();
				set_time_limit(120);

				consolidateFiles($info, $themes, $minify);
				$end = time();
				echo (".." . ($end - $now) . " secs<br/>");
			}
		}
		ob_end_flush();
		closedir($dirHnd);
	}else{
		echo("Could not open themes directory<br/>");
	}
}else{
	echo("Could not read themes directory<br/>");
}

echo("Finished<br/>");

function consolidateFiles($info, $themes, $minify){
	$info = doInheritance($info, $themes);
	//print_r($info);

	//merge css files
	$fileGeneratedFile = $info['path'] . 'css/consolidated.min.css';
	$fileGeneratedFileHnd = fopen($fileGeneratedFile, 'w');
	foreach ($info['settings']['css'] as $filename => $scope){
		//Load contents from the search file
		$fileContents = loadCss($filename, $info['searchPaths']);
		if ($fileContents != null){
			fwrite($fileGeneratedFileHnd, "/* $filename */\r\n");
			//minify the css
			if ($minify){
				$minifiedCss = Minify_CSS::minify($fileContents, array());
			}else{
				$minifiedCss = $fileContents;
			}
			fwrite($fileGeneratedFileHnd, "$minifiedCss\r\n");
		}else{
			echo("Could not find file $filename");
		}
	}
	fclose($fileGeneratedFileHnd);

	//merge javascript files
	$fileGeneratedFile = $info['path'] . 'js/consolidated.min.js';
	if (!file_exists($info['path'] . 'js')){
		mkdir($info['path'] . 'js', true);
	}
	$fileGeneratedFileHnd = fopen($fileGeneratedFile, 'w');
	foreach ($info['settings']['javascript'] as $filename => $scope){
		//echo("Consolidating  $filename<br/>");
		//Load contents from the search file
		$fileContents = loadJavascript($filename, $info['searchPaths']);
		if ($fileContents != null){
			fwrite($fileGeneratedFileHnd, "/* $filename */\r\n");
			//minify the javascript
			if ($minify){
				$minifiedJs = JSMin::minify($fileContents);
			}else{
				$minifiedJs = $fileContents;
			}
			fwrite($fileGeneratedFileHnd, "$minifiedJs\r\n");
		}else{
			echo("Could not find file $filename");
		}
	}
	fclose($fileGeneratedFileHnd);
}

function loadJavascript($filename, $searchPaths){
	$localFile = null;
	for ($i = count($searchPaths) - 1; $i >= 0; $i--){
		if (file_exists($searchPaths[$i] . $filename)){
			$localFile = $searchPaths[$i] . $filename;
			break;
		}
	}
	//Check a couple more locations if we haven't gotten a result yet
	if ($localFile == null && file_exists('js/' . $filename)){
		$localFile = './js/' . $filename;
	}
	if ($localFile == null && file_exists('./' . $filename)){
		$localFile = './' . $filename;
	}
	if ($localFile == null){
		echo("..Did not find file for $filename<br/>");
		return null;
	}else{
		//echo("..Consolidating $localFile<br/>");
		return file_get_contents($localFile);
	}
}

function loadCss($filename, $searchPaths){
	$localFile = null;
	for ($i = count($searchPaths) - 1; $i >= 0; $i--){
		if (file_exists($searchPaths[$i] . 'css/' . $filename)){
			$localFile = $searchPaths[$i] . 'css/' . $filename;
			break;
		}
	}
	if ($localFile == null){
		echo("..Did not find file for $filename<br/>");
		return null;
	}else{
		//echo("..Consolidating $localFile<br/>");
		return file_get_contents($localFile);
	}
}

function doInheritance($info, $themes){
	$inheritFrom = isset($info['settings']['config']['inherit']) ? $info['settings']['config']['inherit'] : '';
	if (array_key_exists($inheritFrom, $themes)){
		//echo("..Inheriting from " . $inheritFrom . "<br/>");
		$infoToInherit = $themes[$inheritFrom];
		$infoToInherit = doInheritance($infoToInherit, $themes);
		$infoToInherit['searchPaths'][] = $info['path'];
		$info = ini_merge($infoToInherit, $info);
		return $info;
	}else{
		//echo("..No inheritance<br/>");
		$info['searchPaths'][] = $info['path'];
		return $info;
	}
}

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
			$config_ini[$k] = ini_merge(isset($config_ini[$k]) ? $config_ini[$k] : array(), $custom_ini[$k]);
		} else {
			$config_ini[$k] = $v;
		}
	}
	return $config_ini;
}