<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {implode} function plugin
 *
 * Name:     implode<br>
 * Purpose:  glue an array together as a string, with supplied string glue, and assign it to the template
 * @link http://smarty.php.net/manual/en/language.function.implode.php {implode}
 *       (Smarty online manual)
 * @author Will Mason <will at dontblinkdesign dot com>
 * @param array $params
 * @param $smarty
 */
function smarty_function_implode($params, &$smarty)
{
	if (!isset($params['subject'])) {
		$smarty->trigger_error("implode: missing 'subject' parameter");
		return;
	}

	if (!isset($params['glue'])) {
		$smarty->trigger_error("implode: missing 'glue' parameter");
		return;
	}

	$implodedValue = null;
	if (is_array($params['subject'])){
		$implodedValue = implode($params['glue'], $params['subject']);
	}else{
		$implodedValue = $params['subject'];
	}

	if (!isset($params['assign'])) {
		return $implodedValue;
	}else{
		$smarty->assign($params['assign'], $implodedValue);
	}
}