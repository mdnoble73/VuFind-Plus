<?php
/**
 * Handles AJAX related information for authors
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 7/23/13
 * Time: 8:37 AM
 */

class Author_AJAX {
	function launch() {
		global $analytics;
		$analytics->disableTracking();
		$method = $_GET['method'];
		if (true){
			//JSON Encoded data
			header('Content-type: text/plain');
			header('Cache-Control: no-cache, must-revalidate'); // HTTP/1.1
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
			echo $this->$method();
		}
	}

	function getWikipediaData(){
		global $configArray;
		global $library;
		global $interface;
		/** @var Memcache $memCache */
		global $memCache;
		$returnVal = array();
		if (isset($configArray['Content']['authors'])
				&& stristr($configArray['Content']['authors'], 'wikipedia')
				&& (!$library || $library->showWikipediaContent == 1)
		) {
			// Only use first two characters of language string; Wikipedia
			// uses language domains but doesn't break them up into regional
			// variations like pt-br or en-gb.
			$authorName = $_REQUEST['articleName'];
			$wiki_lang = substr($configArray['Site']['language'], 0, 2);
			$authorInfo  = $memCache->get("wikipedia_article_{$authorName}_{$wiki_lang}" );
			if ($authorInfo == false){
				require_once ROOT_DIR . '/services/Author/Wikipedia.php';
				$wikipediaParser = new Author_Wikipedia();
				$authorInfo = $wikipediaParser->getWikipedia($authorName, $wiki_lang);
				$memCache->add("wikipedia_article_{$authorName}_{$wiki_lang}", $authorInfo, false, $configArray['Caching']['wikipedia_article']);
			}
			$returnVal['success'] = true;
			$returnVal['article'] = $authorInfo;
			$interface->assign('info', $authorInfo);
			$returnVal['formatted_article'] = $interface->fetch('Author/wikipedia_article.tpl');
		}else{
			$returnVal['success'] = false;
		}
		return  json_encode($returnVal);
	}
}