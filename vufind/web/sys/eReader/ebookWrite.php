<?php
	/*******************************************************************************
	* Software: Open eBook Writer                                                  *
	* Version:  0.7 Alpha                                                          *
	* Date:     2008-07-21                                                         *
	* Author:   Jacob Weigand of RIT's Open Publishing Lab                         *
	* License:  GNU General Public License (GPL)                                   *
	*                                                                              *
	* You may use, modify and redistribute this software as you wish.              *
	********************************************************************************/

require_once('ebookData.php');
class ebookWrite{
/***********
 * Private *
 ***********/
 	/**
 	 * Holds all the ebook data.
 	 **/
 	private $ebookData;

 	/**
 	 * Extracts the files inside of an existing epub file into the buildspace folder for editing.
 	 */
 	private function extractEPUB(){
		//An epub exists so extract it's contents to the buildSpace.
		if(isset($this->ebookData->epub)){
			$zip = new ZipArchive;
			$res = $zip->open($this->ebookData->epub);
			if ($res === TRUE) {
			    $zip->extractTo($this->ebookData->tempDir);
			    $zip->close();
			} else {
			    trigger_error(' Failed to extract the epub contents to the temp folder. failed, code:' . $res, E_USER_ERROR);
			}
		}
 	}

 	/**
 	 * Builds the files needed for a valid epub file. This should only be called when building
 	 * and epub from scratch and no epub file was given for editing and extracted.
 	 */
 	 private function buildFiles(){
		//Make all the files needed for the epub file.
		$metaFolder = $this->ebookData->tempDir."META-INF";
		if(!is_dir($metaFolder))
			mkdir($metaFolder, 755);

		if(!isset($this->ebookData->contentFolder)){
			$oebpsFolder = $this->ebookData->tempDir."OEBPS";
			if(!is_dir($oebpsFolder))
				mkdir($oebpsFolder, 755);
			$this->ebookData->contentFolder = $this->ebookData->tempDir."OEBPS/";
		}
		//Write the mimetype file
		$this->writeFile($this->ebookData->tempDir."mimetype", "application/epub+zip");

		//Make a opf file and set it's location
		$this->writeFile($this->ebookData->contentFolder."package.opf", $this->writeOPF());
		$this->ebookData->opfPath = "OEBPS/package.opf";

		//Make and Write the container.xml file
		$this->writeFile($this->ebookData->tempDir."META-INF/container.xml", $this->writeContainerFile());
 	}

	/**
	 * builds a string of data to be written into the opf file.
	 * @return string data to be written into the opf file.
	 */
 	private function writeOPF(){
 		$XMLhead = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
 		$packageHead = "<package version=\"2.0\" unique-identifier=\"PrimaryID\" xmlns=\"http://www.idpf.org/2007/opf\">\n";
		$this->ebookData->metadata = $this->buildMetadata();
		$this->ebookData->manifest = $this->buildManifest();
		$this->ebookData->spine = $this->buildSpine();
		$this->ebookData->guide = $this->buildGuide();
 		$packageClose = "</package>";
 		return utf8_encode($XMLhead.$packageHead.$this->ebookData->metadata.$this->ebookData->manifest.
 		$this->ebookData->spine.$this->ebookData->guide.$packageClose);
 	}

 	/**
 	 * Gathers all the metadata about this ebook and returns a string formated to be placed into the opf file.
 	 * @return string data to be sent back to the opf writter.
 	 */
 	private function buildMetadata(){
 		$metaHead = "  <metadata>\n";
 		$dcHead = "    <dc-metadata xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xmlns:opf=\"http://www.idpf.org/2007/opf\">\n";

		$tagSet0 = $this->makeTagSet("dc:Title", $this->ebookData->title);
		$tagSet1 = $this->makeTagSet("xml:lang", $this->ebookData->titleXmlLang);
 		$title = $this->buildTags($tagSet0, $tagSet1);

		$tagSet0 = $this->makeTagSet("dc:Language", $this->ebookData->language);
		$tagSet1 = $this->makeTagSet("xsi:type", $this->ebookData->languageXsiType);
 		$language = $this->buildTags($tagSet0, $tagSet1);

 		$tagSet0 = $this->makeTagSet("dc:Identifier", $this->ebookData->identifier);
		$tagSet1 = $this->makeTagSet("id", $this->ebookData->identifierId);
		$tagSet2 = $this->makeTagSet("opf:scheme", $this->ebookData->identifierScheme);
		$tagSet3 = $this->makeTagSet("xsi:type", $this->ebookData->identifierXsiType);
 		$identifier = $this->buildTags($tagSet0, $tagSet1, $tagSet2, $tagSet3);

		$tagSet0 = $this->makeTagSet("dc:Creator", $this->ebookData->creator);
		$tagSet1 = $this->makeTagSet("opf:role", $this->ebookData->creatorRole);
		$tagSet2 = $this->makeTagSet("xml:lang", $this->ebookData->creatorXmlLang);
		$tagSet3 = $this->makeTagSet("opf:file-as", $this->ebookData->creatorOpfFileAs);
 		$creator = $this->buildTags($tagSet0, $tagSet1, $tagSet2, $tagSet3);

 		$tagSet0 = $this->makeTagSet("dc:Contributor", $this->ebookData->contributor);
		$tagSet1 = $this->makeTagSet("opf:role", $this->ebookData->contributorRole);
		$tagSet2 = $this->makeTagSet("xml:lang", $this->ebookData->contributorXmlLang);
		$tagSet3 = $this->makeTagSet("opf:file-as", $this->ebookData->contributorOpfFileAs);
 		$contributor = $this->buildTags($tagSet0, $tagSet1, $tagSet2, $tagSet3);

 		$tagSet0 = $this->makeTagSet("dc:Publisher", $this->ebookData->publisher);
		$tagSet1 = $this->makeTagSet("xml:lang", $this->ebookData->publisherXmlLang);
 		$publisher = $this->buildTags($tagSet0, $tagSet1);

 		$tagSet0 = $this->makeTagSet("dc:Subject", $this->ebookData->subject);
 		$tagSet1 = $this->makeTagSet("xml:lang", $this->ebookData->subjectXmlLang);
 		$subject = $this->buildTags($tagSet0, $tagSet1);

 		$tagSet0 = $this->makeTagSet("dc:Description", $this->ebookData->description);
		$tagSet1 = $this->makeTagSet("xml:lang", $this->ebookData->descriptionXmlLang);
 		$description = $this->buildTags($tagSet0, $tagSet1);

		$tagSet0 = $this->makeTagSet("dc:Date", $this->ebookData->eBookdate);
		$tagSet1 = $this->makeTagSet("opf:event", $this->ebookData->eBookdateEvent);
 		$tagSet2 = $this->makeTagSet("xsi:type", $this->ebookData->eBookdateXsiType);
 		$eBookdate = $this->buildTags($tagSet0, $tagSet1, $tagSet2);

 		$tagSet0 = $this->makeTagSet("dc:Type", $this->ebookData->type);
		$tagSet1 = $this->makeTagSet("xsi:type", $this->ebookData->typeXsiType);
 		$type = $this->buildTags($tagSet0, $tagSet1);

 		$tagSet0 = $this->makeTagSet("dc:Format", $this->ebookData->format);
		$tagSet1 = $this->makeTagSet("xsi:type", $this->ebookData->formatXsiType);
 		$format = $this->buildTags($tagSet0, $tagSet1);

 		$tagSet0 = $this->makeTagSet("dc:Source", $this->ebookData->source);
		$tagSet1 = $this->makeTagSet("xml:lang", $this->ebookData->sourceXmlLang);
 		$source = $this->buildTags($tagSet0, $tagSet1);

 		$tagSet0 = $this->makeTagSet("dc:Relation", $this->ebookData->relation);
		$tagSet1 = $this->makeTagSet("xml:lang", $this->ebookData->relationXmlLang);
 		$relation = $this->buildTags($tagSet0, $tagSet1);

 		$tagSet0 = $this->makeTagSet("dc:Coverage", $this->ebookData->coverage);
		$tagSet1 = $this->makeTagSet("xml:lang", $this->ebookData->coverageXmlLang);
 		$coverage = $this->buildTags($tagSet0, $tagSet1);

 		$tagSet0 = $this->makeTagSet("dc:Rights", $this->ebookData->rights);
		$tagSet1 = $this->makeTagSet("xml:lang", $this->ebookData->rightsXmlLang);
 		$rights = $this->buildTags($tagSet0, $tagSet1);

 		$dcClose = "    </dc-metadata>\n";
 		$metaClose = "  </metadata>\n";

 		return $metaHead.$dcHead.$title.$language.$identifier.$creator.$contributor.$publisher.$subject.
 		$description.$eBookdate.$type.$format.$source.$relation.$coverage.$rights.$dcClose.$metaClose;
 	}

	/**
	 * Gathers and formats all the data for the manifest to be placed into the opf file
	 * @return string data to be returned to be placed into the opf file.
	 */
 	private function buildManifest(){
 		if(!is_array($this->ebookData->manifestData)&&isset($this->ebookData->manifestData))
 			$this->ebookData->manifestData = array($this->ebookData->manifestData);
 		$manifestHead = "  <manifest>\n";
 		$string = "";
 		foreach($this->ebookData->manifestData as $man){
 			$tagSet1 = $this->makeTagSet("id", $man->id);
			$tagSet2 = $this->makeTagSet("href", $man->href);
			$tagSet3 = $this->makeTagSet("media-type", $man->type);
			$tagSet4 = $this->makeTagSet("fallback", $man->fallback);
 			$string = $string."    ".$this->buildSingleTag("item", $tagSet1, $tagSet2, $tagSet3, $tagSet4);
 		}
 		$manifestClose = "  </manifest>\n";
 		return $manifestHead.$string.$manifestClose;
 	}

 	/**
 	 * Gathers and formats all the spine data to be placed into the opf file.
 	 * @return string data to be returned to be placed into the opf file.
 	 */
 	private function buildSpine(){
 		if(!is_array($this->ebookData->spineData) && isset($this->ebookData->spineData))
 			$this->ebookData->spineData = array($this->ebookData->spineData);
 		if(isset($this->ebookData->spineToc))
 			$spineHead = "  <spine toc=\"".$this->ebookData->spineToc."\">\n";
 		else
 			$spineHead = "  <spine>\n";
 		$spine = "";
 		foreach($this->ebookData->spineData as $spItem){
 			$tagSet1 = $this->makeTagSet("idref", $spItem);
 			$spine = $spine."    ".$this->buildSingleTag("itemref", $tagSet1);
 		}
 		$spineClose = "  </spine>\n";
 		return $spineHead.$spine.$spineClose;
 	}

 	/**
 	 * Gathers all the guide data and builds it to be placed into the opf file.
 	 * @return string data to be returned to be placed into the opf file.
 	 */
 	 private function buildGuide(){
		if(isset($this->ebookData->guideData)){
			if(!is_array($this->ebookData->guideData)&&isset($this->ebookData->guideData))
				$this->ebookData->guideData = array($this->ebookData->guideData);
			$guideHead = "  <guide>\n";
			$string = "";
			foreach($this->ebookData->guideData as $gItem){
				$tagSet1 = $this->makeTagSet("type", $gItem->type);
				$tagSet2 = $this->makeTagSet("title", $gItem->title);
				$tagSet3 = $this->makeTagSet("href", $gItem->href);
	 			$string = $string."    ".$this->buildSingleTag("reference", $tagSet1, $tagSet2, $tagSet3);
			}
			$guideClose = "  </guide>\n";
			return $guideHead.$string.$guideClose;
		}else{
			return "";
		}
 	 }

 	 /**
 	  * Turn all the temp files into an epub.
 	  * @param string $name The name you want the final epub file. Don't inclue file extenshion in name.
 	  * @param string $dest The location you want the epub placed after it is done being created.
 	  */
 	 private function packageEpub($name, $dest){
 	 	// Create instance of Archive_Zip class, and pass the name of our zipfile
		$zipfile = new ZipArchive();
		$fileName = $name.'.epub';

		//Change directory so files can be added relativly
		chdir($this->ebookData->tempDir);

		// open archive
		if ($zipfile->open($fileName, ZIPARCHIVE::CREATE) !== TRUE) {
		    trigger_error("Could not open archive", E_USER_ERROR);
		}

		// Create a list of files and directories
		$list = $this->listFiles($this->ebookData->tempDir);

		// add files
		foreach($list as $f) {
		    $f = str_replace($this->ebookData->tempDir, "", $f);
		    $zipfile->addFile($f) or trigger_error("Could not add file: ".$f." to the epub.", E_USER_ERROR);
		}
		$zipfile->close();

		//Delete any file that already exits here with that name.
		if(is_file($dest.$name.'.epub'))
			unlink($dest.$name.'.epub');

		if(is_dir($dest)){
			if(!rename($this->ebookData->tempDir.$fileName, $dest.$name.'.epub'))
			  		trigger_error("A problem occurred while placing ".$name.'.epub'." into the directory ".$dest, E_USER_ERROR);
		}else{
			trigger_error($dest." Is not a directory!", E_USER_ERROR);
		}
 	 }

 	/**
 	 * Makes a container.xml file based on location of the opf file.
 	 */
 	private function writeContainerFile(){
 		if(isset($this->ebookData->opfPath)){
	 		$content = "<?xml version=\"1.0\"?>
<container version=\"1.0\" xmlns=\"urn:oasis:names:tc:opendocument:xmlns:container\">
  <rootfiles>
    <rootfile full-path=\"".$this->ebookData->opfPath."\" media-type=\"application/oebps-package+xml\"/>
  </rootfiles>
</container>";
			return $content;
 		}else{
 			trigger_error("Can't make the container file, the opf path isn't set.", E_USER_ERROR);
 		}
 	}

	/**
	 * Used to write a file
	 * @param string $text the text to be written inside of the file.
	 * @param string $file the location for the file to be written.
	 */
 	private function writeFile($file, $text = null){
 		$fh = fopen($file, 'w') or trigger_error("Can't open file ".$file, E_USER_ERROR);
		flock ($fh, LOCK_EX);
		fwrite($fh, $text);
		flock ($fh, LOCK_UN);
		fclose($fh);
 	}

	/**
	 * Makes folders recursivly
	 * @param string $pathname path to build
	 * @param int $mode chmod value along the way.
	 */
	 private function mkdir_recursive($pathname, $mode)	{
	    is_dir(dirname($pathname)) || $this->mkdir_recursive(dirname($pathname), $mode);
	    return is_dir($pathname) || mkdir($pathname, $mode);
	}

	 /**
	  * Removes or empties a directory.
	  * @param string $directory The directory to be deleted or emptied
	  * @param bool $empty If you want to empty the directory and not delete it then set this to true.
	  */
	private function rmdir_recursive($directory, $empty=FALSE){
	     // if the path has a slash at the end we remove it here
	     if(substr($directory,-1) == '/'){
	         $directory = substr($directory,0,-1);
	     }
	     // if the path is not valid or is not a directory ...
	     if(!file_exists($directory) || !is_dir($directory)) {
	         // ... we return false and exit the function
	         return FALSE;
	     // ... if the path is not readable
	     }elseif(!is_readable($directory)){
	         // ... we return false and exit the function
	         return FALSE;
	     // ... else if the path is readable
	     }else{
	         // we open the directory
	         $handle = opendir($directory);
	         // and scan through the items inside
	         while (FALSE !== ($item = readdir($handle)))         {
	             // if the filepointer is not the current directory
	             // or the parent directory
	             if($item != '.' && $item != '..') {
	                 // we build the new path to delete
	                 $path = $directory.'/'.$item;
	                 // if the new path is a directory
	                 if(is_dir($path)) {
	                     // we call this function with the new path
	                     $this->rmdir_recursive($path);
	                 // if the new path is a file
	                 }else{
	                     // we remove the file
	                     unlink($path);
	                 }
	             }
	         }
	         // close the directory
	         closedir($handle);
	         // if the option to empty is not set to true
	         if($empty == FALSE)         {
	             // try to delete the now empty directory
	             if(!rmdir($directory))             {
	                 // return false if not possible
	                 return FALSE;
	             }
	         }
	         // return success
	        return TRUE;
	    }
	}

	/**
	 * Creates a temporary build folder for all the epub parts to go into before creation.
	 */
	private function makeBuildFolder(){
		// Get temporary directory
		if (!empty($_ENV['TMP'])) {
			$tempdir = $_ENV['TMP'];
		} elseif (!empty($_ENV['TMPDIR'])) {
				$tempdir = $_ENV['TMPDIR'];
		} elseif (!empty($_ENV['TEMP'])) {
			$tempdir = $_ENV['TEMP'];
		} else {
			$tempdir = dirname(tempnam('', 'epb'));
		}

		if (empty($tempdir)) { trigger_error ('No temporary directory', E_USER_ERROR); }

		// Make sure trailing slash is there
		$tempdir = rtrim($tempdir, '/');
		$tempdir .= '/';

		// Make sure temporary directory is writable
		if (is_writable($tempdir) == false) {
		        trigger_error ('Temporary directory isn\'t writable', E_USER_ERROR);
		}

		// Create temp name for our own directory
		$this->ebookData->tempDir = tempnam($tempdir, 'epb');

		// Make sure another file or directory doesn't already exist with this name
		if(is_dir($this->ebookData->tempDir)){
			rmdir($this->ebookData->tempDir);
		}else if(is_file($this->ebookData->tempDir)){
			unlink($this->ebookData->tempDir);
		}

		// Create directory
		mkdir($this->ebookData->tempDir);
		$this->ebookData->tempDir .= '/';

		if(isset($this->ebookData->contentFolder))
			$this->ebookData->contentFolder = $this->ebookData->tempDir.$this->ebookData->contentFolder;
	}

	/**
	 * Allows items to be added to the manifest
	 * @param string $id a discreption of the manifest item
	 * @param string $href referance to the file location of the item.
	 * @param string $media The MIME type of the file being sent.
	 * @param string $fall is the fall back item.
	 */
	private function addManifestItem($id, $href, $media, $fall = null){
		$man = new manifest();
		$man->id = $id;
		$man->href = $href;
		$man->type = $media;
		$man->fallback = $fall;

		if(isset($this->ebookData->manifestData)){
			if(is_array($this->ebookData->manifestData)){
				array_push($this->ebookData->manifestData, $man);
			}else{
				$this->ebookData->manifestData = array($this->ebookData->manifestData);
				array_push($this->ebookData->manifestData, $man);
			}
		}else{
			$this->ebookData->manifestData = $man;
		}
	}

	/**
	 * Makes sure all the required data is set.
	 * @return bool Returns true if everything passes and false if we failed.
	 */
	 private function validateData(){
		$passed = true;
		if(!isset($this->ebookData->title)){
			$passed = false;
			trigger_error("The Title is not set.",E_USER_WARNING);
		}
		if(!isset($this->ebookData->language)){
			$passed = false;
			trigger_error("The Language is not set.",E_USER_WARNING);
		}
		if(!isset($this->ebookData->identifier)){
			$passed = false;
			trigger_error("The Identifier is not set.",E_USER_WARNING);
		}
		if(!isset($this->ebookData->manifestData)){
			$passed = false;
			trigger_error("There is no manifest, one is required.",E_USER_WARNING);
		}
		if(!isset($this->ebookData->spineData)){
			$passed = false;
			trigger_error("There is no spine, one is required.",E_USER_WARNING);
		}
    	if (!is_dir($this->ebookData->contentFolder)){
    		$passed = false;
			trigger_error("The content folder inside of the buildSource folder was not created," .
					" one is required.",E_USER_WARNING);
    	}
    	if($this->is_empty_dir($this->ebookData->contentFolder)){
	        $passed = false;
			trigger_error("There is no content, The content folder is empty.",E_USER_WARNING);
	    }
		return $passed;
	 }

	/**
	 * Makes a pair of data into a tagset
	 * @param string $tag Name of the tag to be placed into the tag
	 * @param string $input the value to be placed into the value attribute.
	 * @return tagSet Returns the data in a datatype container class.
	 */
	 private function makeTagSet($tag, $input){
	 	if(isset($input) && $input != ""){
	 		$tagSet = new tagSet();
	 		$tagSet->tag = $tag;
	 		$tagSet->value = $input;
	 		return $tagSet;
	 	}else{
	 		return null;
	 	}
	 }

	/**
	 * Give this function four tagSet's and it will make a string of XML for an opf file. If
	 * and array of tags are sent in it will make that many tags accoridingly.
	 * @param tagSet $main The primary tag for everything to be added too.
	 * @param tagSet $opt1 Optional attributes to be added to the tag.
	 * @param tagSet $opt2 Optional attributes to be added to the tag.
	 * @param tagSet $opt3 Optional attributes to be added to the tag.
	 */
	 private function buildTags($main, $opt1 = null, $opt2 = null, $opt3 = null){
		if(isset($main->value) && isset($opt1) && (count($main->value) != count($opt1->value))){
			trigger_error("You have ".count($main->value)." ".$main->tag." tags and you have ".count($opt1->value)." ".$opt1->tag." attributes. They must be of the same size", E_USER_ERROR);
		}
		if(isset($main->value) && isset($opt2) && (count($main->value) != count($opt2->value))){
			trigger_error("You have ".count($main->value)." ".$main->tag." tags and you have ".count($opt2->value)." ".$opt2->tag." attributes. They must be of the same size", E_USER_ERROR);
		}
		if(isset($main->value) && isset($opt3) && (count($main->value) != count($opt3->value))){
			trigger_error("You have ".count($main->value)." ".$main->tag." tags and you have ".count($opt3->value)." ".$opt3->tag." attributes. They must be of the same size", E_USER_ERROR);
		}
		$string = "";
		if(is_array($main->value)){
			for($x = 0; $x < count($main->value); $x+=1){
				if(isset($main->value[$x]) && $main->value[$x] != ""){
					$main->value[$x] = trim($main->value[$x], "\n\r\x0B\0");
					$string = $string."      <".$main->tag;
					if(isset($opt1)){
						$string = $string." ".$opt1->tag."=\"".$opt1->value[$x]."\"";
					}
					if(isset($opt2)){
						$string = $string." ".$opt2->tag."=\"".$opt2->value[$x]."\"";
					}
					if(isset($opt3)){
						$string = $string." ".$opt3->tag."=\"".$opt3->value[$x]."\"";
					}
					$string = $string.">".$main->value[$x]."</".$main->tag.">\n";
				}
			}
		}elseif(isset($main->value)){
			$main->value = trim($main->value, "\n\r\x0B\0");
			$string = "      <".$main->tag.">".$main->value."</".$main->tag.">\n";
		}
		return $string;
	 }

	 /**
	  * Makes a tag, but it is not followed with a closing tag.
	  * @param tagSet $main The name of the primary tag
	  * @param tagSet $opt1 Optional attributes to be added to the tag.
	  * @param tagSet $opt2 Optional attributes to be added to the tag.
	  * @param tagSet $opt3 Optional attributes to be added to the tag.
	  */
	 private function buildSingleTag($main, $opt1 = null, $opt2 = null, $opt3 = null, $opt4 = null){
		if(!isset($main)){
			trigger_error("Tag name must be set", E_USER_ERROR);
		}
		$string = $string."<".$main;
		if(isset($opt1)){
			$string = $string." ".$opt1->tag."=\"".$opt1->value."\"";
		}
		if(isset($opt2)){
			$string = $string." ".$opt2->tag."=\"".$opt2->value."\"";
		}
		if(isset($opt3)){
			$string = $string." ".$opt3->tag."=\"".$opt3->value."\"";
		}
		if(isset($opt4)){
			$string = $string." ".$opt4->tag."=\"".$opt4->value."\"";
		}
		$string = $string." />\n";
		return $string;
	 }

	/**
	 * Check if a directory is empty.
	 * @param string $dir the directory to be checked if it is empty or not.
	 * @return bool true if the directory is empty.
	 */
	private function is_empty_dir($dir){
	    if ($dh = opendir($dir)){
	        while ($file = readdir($dh)){
	            if ($file != '.' && $file != '..') {
	                closedir($dh);
	                return false;
	            }
	        }
	        closedir($dh);
	        return true;
	    }
	    else return false; // whatever the reason is : no such dir, not a dir, not readable
	}

	/**
	 * List all the files in a folder and all subfolders. **Recursive Function**
	 * @param string $input the folder to search.
	 * @return string Array of strings that contain all the files in the specified directory.
	 */
	private function listFiles($input){
		$results = array();
		foreach(scandir($input) as $item){
			if($item != "." && $item != ".." && is_file($input.$item)){
				array_push($results, $input.$item);
			}elseif($item != "." && $item != ".." && is_dir($input.$item)){
				$results = array_merge($results, $this->listFiles($input.$item.'/'));
			}
		}
		return $results;
	}

	/**
	 * Makes sure everything inside of the epub is proper.
	 * @return bool Returns true if the epub is valid.
	 */
	 private function validateEPUB(){
		$passed = false;
	 }

	 /**
	  * Tells you if an item exists in the manifest or not.
	  * @param string $item the item being checked for.
	  * @return bool returns true if it exists and false if it does not.
	  */
	 private function existInManifest($item){
		$pass = false;
		foreach($this->ebookData->manifestData as $manId){
			if($item == (string)$manId->id)
				$pass = true;
		}
		return $pass;
	 }

 /*********
 * Public *
 **********/

 	/**
 	 * Constructor
 	 * @param ebookData $ebookData Optional, give this writter a ebookData object for editing.
 	 */
 	public function ebookWrite($ebookData = null){
		if(is_a($ebookData, 'ebookData')){
			$this->ebookData = $ebookData;
			$this->makeBuildFolder();
			if(isset($this->ebookData->epub))
				$this->extractEPUB();
		}else{
			$this->ebookData = new ebookData();
			$this->makeBuildFolder();
		}
 	}

 	/**
 	 * Puts together the eBook and places it in the desired directory. Caution this will overwrite any
 	 * existing by that name in the specified destination directory.
 	 * @param string $name The name you want the epub file to have. Don't include a file extenshion.
 	 * @param string $dest The location you want the final epub file.
 	 */
 	public function buildEPUB($name, $dest){
		if(!$this->validateData())
			trigger_error("Could not build the epub file.", E_USER_ERROR);

		//If this is a new epub then build the files for it, if not then just write the edited opf file for the epub.
		if(!isset($this->ebookData->opfPath)){
			$this->buildFiles();
		}else{
			if(is_file($this->ebookData->tempDir.$this->ebookData->opfPath)){
				$this->writeFile($this->ebookData->tempDir.$this->ebookData->opfPath, $this->writeOPF());
			}else{
				trigger_error("".$this->ebookData->tempDir.$this->ebookData->opfPath." is not a file.", E_USER_ERROR);
			}
		}
		//TODO: Now make sure everything was built properly.
		//$this->validateEPUB();

		//zip up files into an epub
		$this->packageEpub($name, $dest);

		//delete temp files.
		$this->rmdir_recursive($this->ebookData->tempDir);
	}

 /*********************
 * Setter Functions *
 *********************/
	/**
	 * Sets the title. Multiple instances are permitted.
	 * @param string $title The title to be set. If you want set more then one title send an array into this parameter.
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcTitle($title, $index = null){
		if(!isset($index)){
			$this->ebookData->title = $title;
		}else if(is_array($this->ebookData->title)&&isset($index)){
			$this->ebookData->title[$index] = $title;
		}else{
			trigger_error("Can't retrieve the title index number ".$index.". Title may not be an array.",E_USER_WARNING);
		}
	}

	/**
	 * Set the optional title attributes.
	 * @param string $attrib The name of the optional attributes to be set. Optional attributes are XmlLang.
	 * @param string $attribValue The value to be set for the optional attribute. If you want set more then one title attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate title index
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return bool true if set or false if not.
	 */
	public function setDcTitleAttrib($attrib, $attribValue, $index = null){
		if(is_array($this->ebookData->title)&&is_array($attribValue)){
			if(count($this->ebookData->title) < count($attribValue))
				trigger_error("The number of attribute values being added is greater then the number of titles.", E_USER_ERROR);
		}

		if(!isset($index)){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->titleXmlLang = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else if(is_array($this->ebookData->title)&&isset($index)&&isset($this->ebookData->title[$index])){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->titleXmlLang[$index] = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else{
			trigger_error("Can't retrieve the title index number ".$index.". That title index is not set.", E_USER_ERROR);
		}
	}

	/**
	 * Sets the language. Multiple instances are permitted.
	 * @param string $language The language to be set. If you want set more then one language send an array into this parameter.
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcLanguage($language, $index = null){
		if(!isset($index)){
			$this->ebookData->language = $language;
		}else if(is_array($this->ebookData->language)&&isset($index)){
			$this->ebookData->language[$index] = $language;
		}else{
			trigger_error("Can't retrieve the language index number ".$index.". Language may not be an array.",E_USER_WARNING);
		}
	}

	/**
	 * Sets the optional language attributes.
	 * @param string $attrib The name of the optional attributes to be set. Optional attributes are XsiType.
	 * @param string $attribValue The value to be set for the optional attribute. If you want set more then one language attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate language index
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return bool true if set or false if not.
	 */
	public function setDcLanguageAttrib($attrib, $attribValue, $index = null){
		if(is_array($this->ebookData->language)&&is_array($attribValue)){
			if(count($this->ebookData->language) < count($attribValue))
				trigger_error("The number of attribute values being added is greater then the number of languages.", E_USER_ERROR);
		}

		if(!isset($index)){
			switch($attrib){
				case "XsiType":
					$this->ebookData->languageXsiType = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else if(is_array($this->ebookData->language)&&isset($index)&&isset($this->ebookData->language[$index])){
			switch($attrib){
				case "XsiType":
					$this->ebookData->languageXsiType[$index] = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else{
			trigger_error("Can't retrieve the language index number ".$index.". That language index is not set.", E_USER_ERROR);
		}
	}

	/**
	 * Sets the Identifier. Multiple instances are permitted.
	 * @param string $identifier the Identifier to be set. If you want set more then one Identifier send an array into this parameter.
	 * @param string $id discription of what the identifier is.
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcIdentifier($identifier, $id, $index = null){
		if(!isset($index)){
			$this->ebookData->identifier = $identifier;
			$this->ebookData->identifierId = $id;
		}else if(is_array($this->ebookData->identifier)&&is_array($this->ebookData->identifierId)&&isset($index)){
			$this->ebookData->identifier[$index] = $identifier;
			$this->ebookData->identifierId[$index] = $id;
		}else{
			trigger_error("Can't retrieve the identifier index number ".$index.". identifier may not be an array.",E_USER_WARNING);
		}
	}

	/**
	 * Sets the optional identifier attributes.
	 * @param string $attrib Id is a required attribute. The name of the optional attributes to be set.
	 * Optional attributes are Scheme, XsiType.
	 * @param string $attribValue The value to be set for the optional attribute. If you want set more then one identifier attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate identifier index
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return bool true if set or false if not.
	 */
	public function setDcIdentifierAttrib($attrib, $attribValue, $index = null){
		if(is_array($this->ebookData->identifier)&&is_array($attribValue)){
			if(count($this->ebookData->identifier) < count($attribValue))
				trigger_error("The number of attribute values being added is greater then the number of identifiers.", E_USER_ERROR);
		}

		if(!isset($index)){
			switch($attrib){
				case "Scheme":
					$this->ebookData->identifierScheme = $attribValue;
					return true;
					break;
				case "XsiType":
					$this->ebookData->identifierXsiType = $attribValue;
					return true;
					break;
				case "Id":
					$this->ebookData->identifierId = $attribValue;
					break;
				default:
					return false;
			}
		}else if(is_array($this->ebookData->identifier)&&isset($index)&&isset($this->ebookData->identifier[$index])){
			switch($attrib){
				case "Scheme":
					$this->ebookData->identifierScheme[$index] = $attribValue;
					return true;
					break;
				case "XsiType":
					$this->ebookData->identifierXsiType[$index] = $attribValue;
					return true;
					break;
				case "Id":
					$this->ebookData->identifierId[$index] = $attribValue;
					break;
				default:
					return false;
			}
		}else{
			trigger_error("Can't retrieve the identifier index number ".$index.". That identifier index is not set.", E_USER_ERROR);
		}
	}

	/**
	 * Sets the creator. Multiple instances are permitted.
	 * @param string $creator The creator to be set. If you want set more then one creator send an array into this parameter.
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcCreator($creator, $index = null){
		if(!isset($index)){
			$this->ebookData->creator = $creator;
		}else if(is_array($this->ebookData->creator)&&isset($index)){
			$this->ebookData->creator[$index] = $creator;
		}else{
			trigger_error("Can't retrieve the creator index number ".$index.". creator may not be an array.",E_USER_WARNING);
		}
	}

	/**
	 * Sets the optional creator attributes.
	 * @param string $attrib The name of the optional attributes to be set. optional attributes are, Role, XmlLang, OpfFileAs.
	 * @param string $attribValue The value to be set for the optional attribute. If you want set more then one creator attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate creator index
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return bool true if set or false if not.
	 */
	public function setDcCreatorAttrib($attrib, $attribValue, $index = null){
		if(is_array($this->ebookData->creator)&&is_array($attribValue)){
			if(count($this->ebookData->creator) < count($attribValue))
				trigger_error("The number of attribute values being added is greater then the number of creators.", E_USER_ERROR);
		}

		if(!isset($index)){
			switch($attrib){
				case "Role":
					$this->ebookData->creatorRole = $attribValue;
					return true;
					break;
				case "XmlLang":
					$this->ebookData->creatorXmlLang = $attribValue;
					return true;
					break;
				case "OpfFileAs":
					$this->ebookData->creatorOpfFileAs = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else if(is_array($this->ebookData->creator)&&isset($index)&&isset($this->ebookData->creator[$index])){
			switch($attrib){
				case "Role":
					$this->ebookData->creatorRole[$index] = $attribValue;
					return true;
					break;
				case "XmlLang":
					$this->ebookData->creatorXmlLang[$index] = $attribValue;
					return true;
					break;
				case "OpfFileAs":
					$this->ebookData->creatorOpfFileAs[$index] = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else{
			trigger_error("Can't retrieve the creator index number ".$index.". That creator index is not set.", E_USER_ERROR);
		}
	}

	/**
	 * Sets the contributor. Multiple instances are permitted.
	 * @param string $contributor The contributor to be set. If you want set more then one contributor send an array into this parameter.
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcContributor($contributor, $index = null){
		if(!isset($index)){
			$this->ebookData->contributor = $contributor;
		}else if(is_array($this->ebookData->contributor)&&isset($index)){
			$this->ebookData->contributor[$index] = $contributor;
		}else{
			trigger_error("Can't retrieve the contributor index number ".$index.". contributor may not be an array.",E_USER_WARNING);
		}
	}

	/**
	 * Sets the optional contributor attributes.
	 * @param string $attrib The name of the optional attributes to be set. Optional attributes are, Role, XmlLang, OpfFileAs.
	 * @param string $attribValue The value to be set for the optional attribute. If you want set more then one contributor attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate contributor index
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return bool true if set or false if not.
	 */
	public function setDcContributorAttrib($attrib, $attribValue, $index = null){
		if(is_array($this->ebookData->contributor)&&is_array($attribValue)){
			if(count($this->ebookData->contributor) < count($attribValue))
				trigger_error("The number of attribute values being added is greater then the number of contributors.", E_USER_ERROR);
		}

		if(!isset($index)){
			switch($attrib){
				case "Role":
					$this->ebookData->contributorRole = $attribValue;
					return true;
					break;
				case "XmlLang":
					$this->ebookData->contributorXmlLang = $attribValue;
					return true;
					break;
				case "OpfFileAs":
					$this->ebookData->contributorOpfFileAs = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else if(is_array($this->ebookData->contributor)&&isset($index)&&isset($this->ebookData->contributor[$index])){
			switch($attrib){
				case "Role":
					$this->ebookData->contributorRole[$index] = $attribValue;
					return true;
					break;
				case "XmlLang":
					$this->ebookData->contributorXmlLang[$index] = $attribValue;
					return true;
					break;
				case "OpfFileAs":
					$this->ebookData->contributorOpfFileAs[$index] = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else{
			trigger_error("Can't retrieve the contributor index number ".$index.". That contributor index is not set.", E_USER_ERROR);
		}
	}

	/**
	 * Sets the publisher. Multiple instances are permitted.
	 * @param string $publisher The publisher to be set. If you want set more then one publisher send an array into this parameter.
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcPublisher($publisher, $index = null){
		if(!isset($index)){
			$this->ebookData->publisher = $publisher;
		}else if(is_array($this->ebookData->publisher)&&isset($index)){
			$this->ebookData->publisher[$index] = $publisher;
		}else{
			trigger_error("Can't retrieve the publisher index number ".$index.". publisher may not be an array.",E_USER_WARNING);
		}
	}

	/**
	 * Sets the optional publisher attributes.
	 * @param string $attrib The name of the optional attributes to be set. Optional attributes are, XmlLang.
	 * @param string $attribValue The value to be set for the optional attribute. If you want set more then one publisher attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate publisher index
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return bool true if set or false if not.
	 */
	public function setDcPublisherAttrib($attrib, $attribValue, $index = null){
		if(is_array($this->ebookData->publisher)&&is_array($attribValue)){
			if(count($this->ebookData->publisher) < count($attribValue))
				trigger_error("The number of attribute values being added is greater then the number of publishers.", E_USER_ERROR);
		}

		if(!isset($index)){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->publisherXmlLang = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else if(is_array($this->ebookData->publisher)&&isset($index)&&isset($this->ebookData->publisher[$index])){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->publisherXmlLang[$index] = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else{
			trigger_error("Can't retrieve the publisher index number ".$index.". That publisher index is not set.", E_USER_ERROR);
		}
	}

	/**
	 * Sets the subject. Multiple instances are permitted.
	 * @param string $subject The subject to be set. If you want set more then one subject send an array into this parameter.
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcSubject($subject, $index = null){
		if(!isset($index)){
			$this->ebookData->subject = $subject;
		}else if(is_array($this->ebookData->subject)&&isset($index)){
			$this->ebookData->subject[$index] = $subject;
		}else{
			trigger_error("Can't retrieve the subject index number ".$index.". subject may not be an array.",E_USER_WARNING);
		}
	}

	/**
	 * Sets the optional subject attributes.
	 * @param string $attrib The name of the optional attributes to be set. Optional attributes are, XmlLang.
	 * @param string $attribValue The value to be set for the optional attribute. If you want set more then one subject attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate subject index
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return bool true if set or false if not.
	 */
	public function setDcSubjectAttrib($attrib, $attribValue, $index = null){
		if(is_array($this->ebookData->subject)&&is_array($attribValue)){
			if(count($this->ebookData->subject) < count($attribValue))
				trigger_error("The number of attribute values being added is greater then the number of subjects.", E_USER_ERROR);
		}

		if(!isset($index)){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->subjectXmlLang = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else if(is_array($this->ebookData->subject)&&isset($index)&&isset($this->ebookData->subject[$index])){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->subjectXmlLang[$index] = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else{
			trigger_error("Can't retrieve the subject index number ".$index.". That subject index is not set.", E_USER_ERROR);
		}
	}

	/**
	 * Sets the description. Multiple instances are permitted.
	 * @param string $description The description to be set. If you want set more then one description send an array into this parameter.
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcDescription($description, $index = null){
		if(!isset($index)){
			$this->ebookData->description = $description;
		}else if(is_array($this->ebookData->description)&&isset($index)){
			$this->ebookData->description[$index] = $description;
		}else{
			trigger_error("Can't retrieve the description index number ".$index.". description may not be an array.",E_USER_WARNING);
		}
	}

	/**
	 * Sets the optional description attributes.
	 * @param string $attrib The name of the optional attributes to be set. Optional attributes are, XmlLang.
	 * @param string $attribValue The value to be set for the optional attribute. If you want set more then one description attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate description index
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return bool true if set or false if not.
	 */
	public function setDcDescriptionAttrib($attrib, $attribValue, $index = null){
		if(is_array($this->ebookData->description)&&is_array($attribValue)){
			if(count($this->ebookData->description) < count($attribValue))
				trigger_error("The number of attribute values being added is greater then the number of descriptions.", E_USER_ERROR);
		}

		if(!isset($index)){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->descriptionXmlLang = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else if(is_array($this->ebookData->description)&&isset($index)&&isset($this->ebookData->description[$index])){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->descriptionXmlLang[$index] = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else{
			trigger_error("Can't retrieve the description index number ".$index.". That description index is not set.", E_USER_ERROR);
		}
	}

	/**
	 * Sets the date. Multiple instances are permitted.
	 * @param string $date The date to be set. If you want set more then one date send an array into this parameter.
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcDate($eBookdate, $index = null){
		if(!isset($index)){
			$this->ebookData->eBookdate = $eBookdate;
		}else if(is_array($this->ebookData->eBookdate)&&isset($index)){
			$this->ebookData->eBookdate[$index] = $eBookdate;
		}else{
			trigger_error("Can't retrieve the date index number ".$index.". date may not be an array.",E_USER_WARNING);
		}
	}


	/**
	 * Sets the optional date attributes.
	 * @param string $attrib The name of the optional attributes to be set. Optional attributes are, Event, XsiType.
	 * @param string $attribValue The value to be set for the optional attribute. If you want set more then one date attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate date index
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return bool true if set or false if not.
	 */
	public function setDcDateAttrib($attrib, $attribValue, $index = null){
		if(is_array($this->ebookData->eBookdate)&&is_array($attribValue)){
			if(count($this->ebookData->eBookdate) < count($attribValue))
				trigger_error("The number of attribute values being added is greater then the number of dates.", E_USER_ERROR);
		}

		if(!isset($index)){
			switch($attrib){
				case "Event":
					$this->ebookData->eBookdateEvent = $attribValue;
					return true;
					break;
				case "XsiType":
					$this->ebookData->eBookdateXsiType = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else if(is_array($this->ebookData->eBookdate)&&isset($index)&&isset($this->ebookData->eBookdate[$index])){
			switch($attrib){
				case "Event":
					$this->ebookData->eBookdateEvent[$index] = $attribValue;
					return true;
					break;
				case "XsiType":
					$this->ebookData->eBookdateXsiType[$index] = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else{
			trigger_error("Can't retrieve the date index number ".$index.". That date index is not set.", E_USER_ERROR);
		}
	}

	/**
	 * Sets the type. Multiple instances are permitted.
	 * @param string $type The type to be set. If you want set more then one type send an array into this parameter.
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcType($type, $index = null){
		if(!isset($index)){
			$this->ebookData->type = $type;
		}else if(is_array($this->ebookData->type)&&isset($index)){
			$this->ebookData->type[$index] = $type;
		}else{
			trigger_error("Can't retrieve the type index number ".$index.". type may not be an array.",E_USER_WARNING);
		}
	}

	/**
	 * Sets the optional type attributes.
	 * @param string $attrib The name of the optional attributes to be set. Optional attributes are XsiType.
	 * @param string $attribValue The value to be set for the optional attribute. If you want set more then one type attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate type index
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return bool true if set or false if not.
	 */
	public function setDcTypeAttrib($attrib, $attribValue, $index = null){
		if(is_array($this->ebookData->type)&&is_array($attribValue)){
			if(count($this->ebookData->type) < count($attribValue))
				trigger_error("The number of attribute values being added is greater then the number of types.", E_USER_ERROR);
		}

		if(!isset($index)){
			switch($attrib){
				case "XsiType":
					$this->ebookData->typeXsiType = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else if(is_array($this->ebookData->type)&&isset($index)&&isset($this->ebookData->type[$index])){
			switch($attrib){
				case "XsiType":
					$this->ebookData->typeXsiType[$index] = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else{
			trigger_error("Can't retrieve the type index number ".$index.". That type index is not set.", E_USER_ERROR);
		}
	}

	/**
	 * Sets the format. Multiple instances are permitted.
	 * @param string $format The format to be set. If you want set more then one format send an array into this parameter.
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcFormat($format, $index = null){
		if(!isset($index)){
			$this->ebookData->format = $format;
		}else if(is_array($this->ebookData->format)&&isset($index)){
			$this->ebookData->format[$index] = $format;
		}else{
			trigger_error("Can't retrieve the format index number ".$index.". format may not be an array.",E_USER_WARNING);
		}
	}

	/**
	 * Sets the optional format attributes.
	 * @param string $attrib The name of the optional attributes to be set. Optional attributes are XsiType.
	 * @param string $attribValue The value to be set for the optional attribute. If you want set more then one format attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate format index
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return bool true if set or false if not.
	 */
	public function setDcFormatAttrib($attrib, $attribValue, $index = null){
		if(is_array($this->ebookData->format)&&is_array($attribValue)){
			if(count($this->ebookData->format) < count($attribValue))
				trigger_error("The number of attribute values being added is greater then the number of formats.", E_USER_ERROR);
		}

		if(!isset($index)){
			switch($attrib){
				case "XsiType":
					$this->ebookData->formatXsiType = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else if(is_array($this->ebookData->format)&&isset($index)&&isset($this->ebookData->format[$index])){
			switch($attrib){
				case "XsiType":
					$this->ebookData->formatXsiType[$index] = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else{
			trigger_error("Can't retrieve the format index number ".$index.". That format index is not set.", E_USER_ERROR);
		}
	}

	/**
	 * Sets the source. Multiple instances are permitted.
	 * @param string $source The source to be set. If you want set more then one source send an array into this parameter.
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcSource($source, $index = null){
		if(!isset($index)){
			$this->ebookData->source = $source;
		}else if(is_array($this->ebookData->source)&&isset($index)){
			$this->ebookData->source[$index] = $source;
		}else{
			trigger_error("Can't retrieve the source index number ".$index.". source may not be an array.",E_USER_WARNING);
		}
	}

	/**
	 * Set the optional source attributes.
	 * @param string $attrib The name of the optional attributes to be set. Optional attributes are XmlLang.
	 * @param string $attribValue The value to be set for the optional attribute. If you want set more then one source attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate source index
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return bool true if set or false if not.
	 */
	public function setDcSourceAttrib($attrib, $attribValue, $index = null){
		if(is_array($this->ebookData->source)&&is_array($attribValue)){
			if(count($this->ebookData->source) < count($attribValue))
				trigger_error("The number of attribute values being added is greater then the number of sources.", E_USER_ERROR);
		}

		if(!isset($index)){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->sourceXmlLang = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else if(is_array($this->ebookData->source)&&isset($index)&&isset($this->ebookData->source[$index])){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->sourceXmlLang[$index] = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else{
			trigger_error("Can't retrieve the source index number ".$index.". That source index is not set.", E_USER_ERROR);
		}
	}

	/**
	 * Sets the relation. Multiple instances are permitted.
	 * @param string $relation The relation to be set. If you want set more then one relation send an array into this parameter.
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcRelation($relation, $index = null){
		if(!isset($index)){
			$this->ebookData->relation = $relation;
		}else if(is_array($this->ebookData->relation)&&isset($index)){
			$this->ebookData->relation[$index] = $relation;
		}else{
			trigger_error("Can't retrieve the relation index number ".$index.". relation may not be an array.",E_USER_WARNING);
		}
	}

	/**
	 * Set the optional relation attributes.
	 * @param string $attrib The name of the optional attributes to be set. Optional attributes are XmlLang.
	 * @param string $attribValue The value to be set for the optional attribute. If you want set more then one relation attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate relation index
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return bool true if set or false if not.
	 */
	public function setDcRelationAttrib($attrib, $attribValue, $index = null){
		if(is_array($this->ebookData->relation)&&is_array($attribValue)){
			if(count($this->ebookData->relation) < count($attribValue))
				trigger_error("The number of attribute values being added is greater then the number of relations.", E_USER_ERROR);
		}

		if(!isset($index)){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->relationXmlLang = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else if(is_array($this->ebookData->relation)&&isset($index)&&isset($this->ebookData->relation[$index])){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->relationXmlLang[$index] = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else{
			trigger_error("Can't retrieve the relation index number ".$index.". That relation index is not set.", E_USER_ERROR);
		}
	}

	/**
	 * Sets the coverage. Multiple instances are permitted.
	 * @param string $coverage The coverage to be set. If you want set more then one coverage send an array into this parameter.
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcCoverage($coverage, $index = null){
		if(!isset($index)){
			$this->ebookData->coverage = $coverage;
		}else if(is_array($this->ebookData->coverage)&&isset($index)){
			$this->ebookData->coverage[$index] = $coverage;
		}else{
			trigger_error("Can't retrieve the coverage index number ".$index.". coverage may not be an array.",E_USER_WARNING);
		}
	}

	/**
	 * Set the optional coverage attributes.
	 * @param string $attrib The name of the optional attributes to be set. Optional attributes are XmlLang.
	 * @param string $attribValue The value to be set for the optional attribute. If you want set more then one coverage attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate coverage index
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return bool true if set or false if not.
	 */
	public function setDcCoverageAttrib($attrib, $attribValue, $index = null){
		if(is_array($this->ebookData->coverage)&&is_array($attribValue)){
			if(count($this->ebookData->coverage) < count($attribValue))
				trigger_error("The number of attribute values being added is greater then the number of coverages.", E_USER_ERROR);
		}

		if(!isset($index)){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->coverageXmlLang = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else if(is_array($this->ebookData->coverage)&&isset($index)&&isset($this->ebookData->coverage[$index])){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->coverageXmlLang[$index] = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else{
			trigger_error("Can't retrieve the coverage index number ".$index.". That coverage index is not set.", E_USER_ERROR);
		}
	}

	/**
	 * Sets the rights. Multiple instances are permitted.
	 * @param string $rights The rights to be set. If you want set more then one rights send an array into this parameter.
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcRights($rights, $index = null){
		if(!isset($index)){
			$this->ebookData->rights = $rights;
		}else if(is_array($this->ebookData->rights)&&isset($index)){
			$this->ebookData->rights[$index] = $rights;
		}else{
			trigger_error("Can't retrieve the rights index number ".$index.". rights may not be an array.",E_USER_WARNING);
		}
	}

	/**
	 * Set the optional rights attributes.
	 * @param string $attrib The name of the optional attributes to be set. Optional attributes are XmlLang.
	 * @param string $attribValue The value to be set for the optional attribute. If you want set more then one rights attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate rights index
	 * @param int $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return bool true if set or false if not.
	 */
	public function setDcRightsAttrib($attrib, $attribValue, $index = null){
		if(is_array($this->ebookData->rights)&&is_array($attribValue)){
			if(count($this->ebookData->rights) < count($attribValue))
				trigger_error("The number of attribute values being added is greater then the number of rightss.", E_USER_ERROR);
		}

		if(!isset($index)){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->rightsXmlLang = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else if(is_array($this->ebookData->rights)&&isset($index)&&isset($this->ebookData->rights[$index])){
			switch($attrib){
				case "XmlLang":
					$this->ebookData->rightsXmlLang[$index] = $attribValue;
					return true;
					break;
				default:
					return false;
			}
		}else{
			trigger_error("Can't retrieve the rights index number ".$index.". That rights index is not set.", E_USER_ERROR);
		}
	}

	/**
	 * Sets a desired spine order. The spine is used to determine the reading order.
	 * @param string $spineId Send an array of ID names as they appear in the manifest in the order you want them read.
	 * @param int $index If you wish to set or change just one element of the spine then specify what one in the index parameter.
	 **/
	 public function setSpine($spineId, $index = null){
	 	if(is_array($spineId)){
		 	//varify that all the spine items exist in the manifest.
		 	foreach($spineId as $id){
		 		if(!$this->existInManifest($id))
		 			trigger_error("".$id." does not exist in the manifest.", E_USER_ERROR);
		 	}
		 	if(!isset($index)){
			 	//If everything matched up to the manifest then set the spine.
			 	$this->ebookData->spineData = $spineId;
		 	}else{
		 		trigger_error("A single spine item can't hold an array of spine items.", E_USER_ERROR);
		 	}
	 	}else if(isset($index)){
	 		$this->ebookData->spineData[$index] = $spineId;
	 	}else{
	 		$this->ebookData->spineData = $spineId;
	 	}
	 }

	 /**
	  * Used to tell the spine what manifest item is the Table Of Contents. This does not need to
	  * be set, this is optional.
	  * @param $toc The manifest item that is the table of contents.
	  */
	 public function setSpineToc($toc){
		if(!$this->existInManifest($toc)){
			trigger_error("".$toc." does not exist in the manifest.", E_USER_ERROR);
		}
		$this->ebookData->spineToc = $toc;
	 }

	 /**
 	 * sets the guide item desired. Arrays can be sent into the attributes to set more then one
 	 * guide item at a time. The guide element identifies fundamental structural components of the publication,
	 * to enable Reading Systems to provide convenient access to them.
 	 * @param string $title Name of the guide item.
 	 * @param string $type The required type attribute describes the publication component referenced by the href
	 * attribute. Type should be of this list: cover, title-page, toc "table of contents", index "back-of-book
	 * style index", glossary, acknowledgements, bibliography, colophon, copyright-page, dedication, epigraph,
	 * foreword, loi "list of illustrations", lot "list of tables", notes, and preface.
 	 * @param string $href location of the guide item.
 	 * @param int $index The index number of the guide item to be set.
 	 **/
	public function setGuide($title, $type, $href, $index = null){
		if(count($title)==count($type)&&count($type)==count($href)&&is_array($title)){
			if(!isset($index)){
				$this->ebookData->guideData = array();
				for($x = 0; $x < count($title); $x+=1){
					$guide = new guide();
					$guide->title = $title[$x];
					$guide->type = $type[$x];
					$guide->href = $href[$x];
					array_push($this->ebookData->guideData, $guide);
				}
			}else{
				trigger_error("You can not put an array of guide items into a single guide item.", E_USER_ERROR);
			}
		}else if(count($title)==count($type)&&count($type)==count($href)&&is_array($this->ebookData->guideData)){
			$guide = new guide();
			$guide->title = $title;
			$guide->type = $type;
			$guide->href = $href;
			if(isset($index)){
				$this->ebookData->guideData[$index] = $guide;
			}else{
				$this->ebookData->guideData = $guide;
			}
		}else{
			trigger_error("Can't add guide item. Make sure you have all required attributes the same size.", E_USER_ERROR);
		}
	}

 	/**
 	 * This is used for adding content files to an epub.
 	 * @param string $fileLoc Where is the file currently located.
 	 * @param string $subDir If you want the file in a subdirectory of the content folder. Must end with a slash.
 	 * @param string $name Short discriptive name of what the content item is. eg: Chapter 3.
 	 * @param string $mime the mime type of the file being added.
 	 * @param string $fallBackId the id of an item you wish to use as a fallback item.
 	 * @return bool Returns true if the file was added successfully and false if not.
 	 */
	public function addContentFile($fileLoc, $name, $mime, $subDir = null, $fallBackId = null){
		//Check that we have a file
		if((!empty($fileLoc))) {
			$filename = basename($fileLoc);

			if(!isset($this->ebookData->contentFolder)){
				mkdir($this->ebookData->tempDir."OEBPS", 755);
				$this->ebookData->contentFolder = $this->ebookData->tempDir."OEBPS/";
			}

			if(!file_exists($this->ebookData->contentFolder.$subDir))
				$this->mkdir_recursive($this->ebookData->contentFolder.$subDir, 755);

			$newname = $this->ebookData->contentFolder.$subDir.$filename;

			//TODO: add autodetection of mimetype.
			//Attempt to move the uploaded file to it's new place
			$this->addManifestItem($name, $subDir.$filename, $mime, $fallBackId);
			if (!copy($fileLoc, $newname))
		  		trigger_error("A problem occurred during file upload!", E_USER_ERROR);
		} else {
		 	trigger_error("No file uploaded\n <br />", E_USER_ERROR);
		}
	}

	/**
	 * Given a manifest Id this function will remove the specified file and all referances
	 * from the manifest, spine, and guide.
	 * @param string $manId The Manifest ID to be deleted.
	 * @return bool Returns true if the file was removed from the manifest.
	 */
	public function removeContentFile($manId){
		if(isset($this->ebookData->manifestData)){
			$man = false;
			$href="";
			for($x = 0; isset($this->ebookData->manifestData[$x]);$x+=1){
				if($this->ebookData->manifestData[$x]->id == $manId){
					$href = $this->ebookData->manifestData[$x]->href;
					unlink($this->ebookData->contentFolder.$this->ebookData->manifestData[$x]->href);
					array_splice($this->ebookData->manifestData, $x, 1);
					$man = true;
				}
			}
			for($x = 0; isset($this->ebookData->spineData[$x]);$x+=1){
				if($this->ebookData->spineData[$x] == $manId){
					array_splice($this->ebookData->spineData, $x, 1);
				}
			}
			for($x = 0; isset($this->ebookData->guideData[$x]);$x+=1){
				if((string)$this->ebookData->guideData[$x]->href == $href){
					array_splice($this->ebookData->guideData, $x, 1);
				}
			}
			if($man){
				return true;
			}else{
				return false;
			}
		}else{
			trigger_error("The Manifest has not been set yet.", E_USER_ERROR);
		}
	}
}
?>