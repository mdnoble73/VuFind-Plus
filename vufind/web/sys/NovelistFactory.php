<?php
/**
 * Description goes here
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 4/29/13
 * Time: 8:48 AM
 */

class NovelistFactory {
	static function getNovelist(){
		global $configArray;
		if (!isset($configArray['Novelist']['apiVersion']) || $configArray['Novelist']['apiVersion'] == 1){
			require_once ROOT_DIR . '/sys/Novelist/Novelist.php';
			$novelist = new Novelist();
		}elseif ($configArray['Novelist']['apiVersion'] == 2){
			require_once ROOT_DIR . '/sys/Novelist/Novelist2.php';
			$novelist = new Novelist2();
		}else{
			require_once ROOT_DIR . '/sys/Novelist/Novelist3.php';
			$novelist = new Novelist3();
		}
		return $novelist;
	}
}