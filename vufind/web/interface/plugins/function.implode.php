<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * Smarty {implode} function plugin
 *
 * Type:     function<br>
 * Name:     implode<br>
 * Purpose:  glue an array together as a string, with spupplied string glue, and assign it to the template<br>
 * @link http://smarty.php.net/manual/en/language.function.implode.php {implode}
 *       (Smarty online manual)
 * @author Will Mason <will at dontblinkdesign dot com>
 * @param array
 * @param Smarty
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

	if (!isset($params['assign'])) {
		$smarty->trigger_error("implode: missing 'assign' parameter");
		return;
	}

	$smarty->assign($params['assign'], implode($params['glue'], $params['subject']));
}