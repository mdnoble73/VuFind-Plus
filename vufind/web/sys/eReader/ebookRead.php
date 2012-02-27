<?php
	/*******************************************************************************
	* Software: Open eBook Reader                                                  *
	* Version:  0.6 Alpha                                                          *
	* Date:     2008-07-21                                                         *
	* Author:   Jacob Weigand of RIT's Open Publishing Lab                         *
	* License:  GNU General Public License (GPL)                                   *
	*                                                                              *
	* You may use, modify and redistribute this software as you wish.              *
	********************************************************************************/

require_once('ebookData.php');
class ebookRead{
/***********
 * Private *
 ***********/
 	/**
 	 * Holds all the ebook data.
 	 **/
 	public $ebookData;
 	public $errorOccurred = false;
 	public $error = "";

	/**
	 * Opens the epub and sets all the path locations for the different types files inside of it.
	 **/
	private function processEpub(){
		//Read the mimetype file
		$mime = $this->readEpubFile($this->ebookData->epub, "mimetype");
		if(!preg_match('(application\/epub\+zip)', $mime)){
			$this->errorOccurred = true;
			$this->error = "This eBook is not properly formatted.  The mimetype is incorrect.";
			trigger_error("This eBook is not of mimetype application/epub+zip",E_USER_WARNING);
			return;
		}

		//Read the container.xml information
		$contents = $this->readEpubFile($this->ebookData->epub, "META-INF/container.xml");
		$xml = simplexml_load_string($contents);

		//Process the container.xml file and set the path to the opf file.
		$this->ebookData->opfPath = (string)$xml->rootfiles->rootfile->attributes()->{'full-path'};

		//Find and load other important files.
		$this->ebookData->toc = $this->fildFileByExt($this->ebookData->epub, "ncx");
		$this->ebookData->xpgt = $this->fildFileByExt($this->ebookData->epub, "xpgt");
		$this->ebookData->css = $this->fildFileByExt($this->ebookData->epub, "css");

		$this->loadReqOPF();
		$this->loadOptionalOPF();
		$this->loadTOC();

		$this->removeSimpleXML();
	}

	/**
	 * Gives you the path of the file(s) with the extenshion you are looking for. **Recursive Function**
	 * @param $input The epub file that contains the files your will be searching, or the directory to search.
	 * @param string $ext The extenshion you will be looking for.
	 * @return string Will return a string of a single file location if only one file with that extenshion exists,
	 * or an array of file locations if more then one exists, or null if none exist.
	 **/
	private function fildFileByExt($input, $ext){
		$contents = null;
		if(is_file($input)){
			$zip = zip_open($input);
			if ($zip) {
			  while ($zip_entry = zip_read($zip)) {
			  	$fileExt = substr(zip_entry_name($zip_entry), strrpos(zip_entry_name($zip_entry), '.') + 1);
			    	if($fileExt == $ext)	{
			    		if(!is_array($contents)){
			    			if(!isset($contents)){
			    				$contents = zip_entry_name($zip_entry);
			    			}else{
			    				$placeHolder = $contents;
			    				$contents = array($placeHolder);
			    				array_push($contents, zip_entry_name($zip_entry));
			    			}
			    		}else{
			    			array_push($contents, zip_entry_name($zip_entry));
			    		}
			    	}
			  }
				zip_close($zip);
				return $contents;
			}
		}else if(is_dir($input)){
			//**Recursive Function part**
			$results = glob($input."*.".$ext);
			foreach(scandir($input) as $item){
				if($item != "." && $item != ".." && is_dir($input.$item)){
					$results = array_merge($results, $this->fildFileByExt($input.$item.'/', $ext));
				}
			}
			return $results;
		}
	}

	/**
	 * Loads the attributes that are required for this to be a valid epub into variables
	 **/
	private function loadReqOPF(){
		//Read the opf file information
		$contents = $this->readEpubFile($this->ebookData->epub, $this->ebookData->opfPath);
		if(!isset($contents))
			trigger_error("can't read the opf file", E_USER_ERROR);

		//Format the file for our use.
		$ourFileName = $this->formatXML($contents);
		//Load our XML
		$xml = simplexml_load_string($ourFileName);

		//Load the metadata
		$this->ebookData->metadata = $this->getTag($xml, 'metadata');
		if(!isset($this->ebookData->metadata))
			trigger_error("can't read the metadata from the opf file", E_USER_ERROR);

		$this->ebookData->title = $this->getTag($this->ebookData->metadata, 'dctitle');
		if(!isset($this->ebookData->title))
			trigger_error("can't find the dublin core title data inside the opf file", E_USER_ERROR);
		$this->ebookData->language = $this->getTag($this->ebookData->metadata, 'dclanguage');
		if(!isset($this->ebookData->language))
			trigger_error("can't find the dublin core language data inside the opf file", E_USER_ERROR);
		$this->ebookData->identifier = $this->getTag($this->ebookData->metadata, "dcidentifier");
		$this->ebookData->identifierId = (string)$this->optAttributeExist($this->ebookData->identifier, "id");
		if(!isset($this->ebookData->identifier))
			trigger_error("can't find the dublin core identifier data inside the opf file", E_USER_ERROR);

		//Load the manifest
		$this->ebookData->manifest = $this->getTag($xml, 'manifest');
		if(!isset($this->ebookData->manifest))
			trigger_error("can't find the manifest inside the opf file", E_USER_ERROR);
		$this->loadManifest();
		//Finds where the content is, based on the manifest
		$this->findContentLoc();

		//Load the spine
		$this->ebookData->spine = $this->getTag($xml, 'spine');
		if(!isset($this->ebookData->spine))
			trigger_error("can't find the spine inside the opf file", E_USER_ERROR);
		$this->ebookData->spineData = $this->xmlFindData($this->ebookData->spine, 'itemref');
		$this->ebookData->spineData = $this->optAttributeExist($this->ebookData->spineData, 'idref');
	}

	/**
	 * Loads all the optional tags and attributes epub data into variables
	 **/
	private function loadOptionalOPF(){
		//Read the opf file information
		$contents = $this->readEpubFile($this->ebookData->epub, $this->ebookData->opfPath);
		if(!isset($contents))
			trigger_error("can't read the opf file", E_USER_ERROR);

		//Format the file for our use.
		$ourFileName = $this->formatXML($contents);
		//Load our XML
		$xml = simplexml_load_string($ourFileName);

		//Load the guide if it exists
		$this->ebookData->guide = $this->getTag($xml, 'guide');
		if(isset($this->ebookData->guide))
			$this->loadGuide();

		//Load the spine's pointer to the TOC file in the manifest, if it exists.
		$tempSpine = $this->getTag($this->ebookData->spine, 'spine');
		$this->ebookData->spineToc = (string)$this->optAttributeExist($tempSpine, "toc");

		//Optional tags
		$this->ebookData->creator = $this->getTag($this->ebookData->metadata, 'dccreator');
		$this->ebookData->contributor = $this->getTag($this->ebookData->metadata, 'dccontributor');
		$this->ebookData->publisher = $this->getTag($this->ebookData->metadata, 'dcpublisher');
		$this->ebookData->subject = $this->getTag($this->ebookData->metadata, 'dcsubject');
		$this->ebookData->description = $this->getTag($this->ebookData->metadata, 'dcdescription');
		$this->ebookData->eBookDate = $this->getTag($this->ebookData->metadata, 'dcdate');
		$this->ebookData->type = $this->getTag($this->ebookData->metadata, 'dctype');
		$this->ebookData->format = $this->getTag($this->ebookData->metadata, 'dcformat');
		$this->ebookData->source = $this->getTag($this->ebookData->metadata, 'dcsource');
		$this->ebookData->relation = $this->getTag($this->ebookData->metadata, 'dcrelation');
		$this->ebookData->coverage = $this->getTag($this->ebookData->metadata, 'dccoverage');
		$this->ebookData->rights = $this->getTag($this->ebookData->metadata, 'dcrights');

		//Optional tag attributes
		$this->ebookData->titleXmlLang = (string)$this->optAttributeExist($this->ebookData->title, "xmllang");
		$this->ebookData->languageXsiType = (string)$this->optAttributeExist($this->ebookData->language, "xsitype");
		$this->ebookData->identifierScheme = (string)$this->optAttributeExist($this->ebookData->identifier, "opfscheme");
		$this->ebookData->identifierXsiType = (string)$this->optAttributeExist($this->ebookData->identifier, "xsitype");
		$this->ebookData->creatorRole = (string)$this->optAttributeExist($this->ebookData->creator, "opfrole");
		$this->ebookData->creatorXmlLang = (string)$this->optAttributeExist($this->ebookData->creator, "xmllang");
		$this->ebookData->creatorOpfFileAs = (string)$this->optAttributeExist($this->ebookData->creator, "opffile-as");
		$this->ebookData->contributorRole = (string)$this->optAttributeExist($this->ebookData->contributor, "opfrole");
		$this->ebookData->contributorXmlLang = (string)$this->optAttributeExist($this->ebookData->contributor, "xmllang");
		$this->ebookData->contributorOpfFileAs = (string)$this->optAttributeExist($this->ebookData->contributor, "opffile-as");
		$this->ebookData->publisherXmlLang = (string)$this->optAttributeExist($this->ebookData->publisher, "xmllang");
		$this->ebookData->subjectXmlLang = (string)$this->optAttributeExist($this->ebookData->subject, "xmllang");
		$this->ebookData->descriptionXmlLang = (string)$this->optAttributeExist($this->ebookData->description, "xmllang");
		$this->ebookData->eBookdateEvent = (string)$this->optAttributeExist($this->ebookData->eBookDate, "opfevent");
		$this->ebookData->eBookdateXsiType = (string)$this->optAttributeExist($this->ebookData->eBookDate, "xsitype");
		$this->ebookData->typeXsiType = (string)$this->optAttributeExist($this->ebookData->type, "xsitype");
		$this->ebookData->formatXsiType = (string)$this->optAttributeExist($this->ebookData->format, "xsitype");
		$this->ebookData->sourceXmlLang = (string)$this->optAttributeExist($this->ebookData->source, "xmllang");
		$this->ebookData->relationXmlLang = (string)$this->optAttributeExist($this->ebookData->relation, "xmllang");
		$this->ebookData->coverageXmlLang = (string)$this->optAttributeExist($this->ebookData->coverage, "xmllang");
		$this->ebookData->rightsXmlLang = (string)$this->optAttributeExist($this->ebookData->rights, "xmllang");
	}

	/**
	 * Loads all the manifest data into the manifestData array
	 * attributes of this can be accessed in the maner of
	 * $this->ebookData->manifestData[x]->id; for the id attribute.
	 * $this->ebookData->manifestData[x]->href; for the href attribute.
	 * $this->ebookData->manifestData[x]->type; for the media type attribute.
	 **/
	private function loadManifest(){
		$this->ebookData->manifestData = array();
		foreach($this->ebookData->manifest->children() as $child){
			$manifestItem = new manifest();
			$manifestItem->id = (string)$child->attributes()->id;
			$manifestItem->href = (string)$child->attributes()->href;
			$manifestItem->type = (string)$child->attributes()->{'media-type'};
			$manifestItem->fallback = (string)$child->attributes()->fallback;
			array_push($this->ebookData->manifestData, $manifestItem);
		}
	}

	/**
	 * Loads all the guide data into the guideData array
	 * attributes of this can be accessed in the maner of
	 * $this->ebookData->guideData[x]->title; for the id attribute.
	 * $this->ebookData->guideData[x]->href; for the href attribute.
	 * $this->ebookData->guideData[x]->type; for the media type attribute.
	 **/
	private function loadGuide(){
		$this->ebookData->guideData = array();
		foreach($this->ebookData->guide->children() as $child){
			$guideItem = new guide();
			$guideItem->title = (string)$child->attributes()->title;
			$guideItem->href = (string)$child->attributes()->href;
			$guideItem->type = (string)$child->attributes()->type;
			array_push($this->ebookData->guideData, $guideItem);
		}
	}

	/**
	 *	Reads the .ncx file if there is one.
	 */
	private function loadTOC(){
		if(isset($this->ebookData->toc)){
			$manifest = $this->ebookData->manifestData;
			//Read the toc file information
			$contents = $this->readEpubFile($this->ebookData->epub, $this->ebookData->toc);
			$xml = simplexml_load_string($contents);
			$navMap = $xml->navMap;
			$finToc = array();
			foreach ($navMap->navPoint as $navPoint){
				$ch = new tocItem();
				$ch->title = (string)$navPoint->navLabel[0]->text;
				$location = (string)$navPoint->content[0]['src'];
				$file = $location;
				$anchor = "";
				if (strpos($file, '#') > 0){
					$file = substr($file, 0, strpos($file, '#'));
					$anchor = substr($location, strpos($location, '#'));
				}
				$ch->location = $location;
				//Get the src id for the location 
				foreach ($manifest as $manifestItem){
					if ($manifestItem->href == $file){
						$ch->src = $manifestItem->id . $anchor;
						break;
					}
				}
				foreach ($navPoint->navPoint as $subNavPoint){
					$subch = new tocItem();
					$subch->title = (string)$subNavPoint->navLabel[0]->text;
					$location = (string)$subNavPoint->content[0]['src'];
					$file = $location;
					$anchor = "";
					if (strpos($file, '#') > 0){
						$file = substr($file, 0, strpos($file, '#'));
						$anchor = substr($location, strpos($location, '#'));
					}
					$subch->location = $location;
					//Get the src id for the location 
					foreach ($manifest as $manifestItem){
						if ($manifestItem->href == $file){
							$subch->src = $manifestItem->id . $anchor;
							break;
						}
					}
					$ch->children[] = $subch;
				}
				$finToc[] = $ch;
			}
			/*$navPoint = $this->getTag($xml, "navPoint");
			$names = array();
			$href = array();
			foreach($navPoint as $nav){
				array_push($names, $this->getTag($nav, "text"));
				$hrefTemp = $this->getTag($nav, "content");
				array_push($href, $this->optAttributeExist($hrefTemp, "src"));
			}
			$finToc = array();
			for($x=0; $x < count($names);$x+=1){
				$ch = new tocItem();
				$ch->title = (string)$names[$x];
				$ch->src = (string)$href[$x];
				array_push($finToc, $ch);
			}*/
			$this->ebookData->tocData = $finToc;
		}
	}

	/**
	 * Sets the location of where the content of the epub is located.
	 * Can't find any documentation about content always being located with the opf file,
	 * but every epub I can find has it's content with the opf, and it's manifest points to
	 * content relative to the opf location. Setting the content path
	 * based on where the opf file is located.
	 */
	 private function findContentLoc(){
		if(isset($this->ebookData->opfPath)){
			$this->ebookData->contentFolder = dirname($this->ebookData->opfPath)."/";
		}else{
			trigger_error("Can't set the contentFolder location because the opf path dosen't exist.", E_USER_ERROR);
		}
	 }

	/**
	 * Tests if a optional attribute exists and then returns it. If an array of tags is sent in then it returns
	 * an array of the optional tags.
	 * @param SimpleXMLElement $tag The tag being checked for a particular attribute.
	 * @param string $attribute the attribute being searched for.
	 * @return SimpleXMLElement returns a attribute if given a single tag, else return an array of attributes if given an array of
	 * tags, else return null.
	 **/
	private function optAttributeExist($tag, $attribute){
		if(isset($tag)){
			if(is_array($tag)){
				$array = array();
				foreach($tag as $element){
					$data = $element->attributes()->$attribute;
					array_push($array, $data);
				}
				return $array;
			}
			return $tag->attributes()->$attribute;
		}else{
			return NULL;
		}
	}

	/**
	 * Find the specified data.
	 * @param SimpleXMLElement $input is the xml document.
	 * @param string $tag is the tag with the data being searched for.
	 * @return SimpleXMLElement If we only have one result from the xmlFindData function then we return
	 * it else we send back the whole array.
	 **/
	private function getTag(SimpleXMLElement $input, $tag){
		$data = $this->xmlFindData($input, $tag);
		if(count($data) < 1){
			return NULL;
		}else if(count($data) == 1){
			return $data[0];
		}else{
			return $data;
		}
	}

	/**
	 * Find the specified data. **Recursive Function**
	 * @param SimpleXMLElement $xmlInput is the xml document
	 * @param string $tag is the tag with the data being searched for
	 * @return SimpleXMLElement an array of the results.
	 **/
	private function xmlFindData(SimpleXMLElement $xmlInput, $tag){
		$array = array();
		//If we find a match, save it.
		if($xmlInput->getName() == $tag){
			array_push($array, $xmlInput);
		}
		//If there are no children then this don't run.
		foreach($xmlInput->children() as $child){
			$recurse = $this->xmlFindData($child, $tag);
			//Save each submatch.
			$array = array_merge($array, $recurse);
		}
		//Return all submatches to the next higher level.
		return $array;
	}

	/**
	 * To standardize the tag structure. This removes all colons in the tags and makes all the tags lowercase
	 * @param string $xmlString The XML file as a string
	 * @return string that has been formatted.
	 **/
	private function formatXML($xmlString){
		/**SimpleXML cant read XML tags that have colons in them so we are going to remove them here. I dont
	 	 * want to remove the colons from web locations. So I am just removing them from the dc tags **/
		$xmlString = str_replace("dc:", "dc", $xmlString);
		$xmlString = str_replace("xml:", "xml", $xmlString);
		$xmlString = str_replace("xsi:", "xsi", $xmlString);
		$xmlString = str_replace("opf:", "opf", $xmlString);
		//This turns all the tags to lower case. A Bug adds a \backslash before every "quote.
		$xmlString = preg_replace ('!(</?)(\w+)([^>]*?>)!e', "'\\1'.strtolower('\\2').'\\3'", $xmlString);
		//This removes all the added \ from the previous replace
		$xmlString = str_replace("\\\"", "\"", $xmlString);
		return $xmlString;
	}

	/**
	 * SimpleXML has problems with being serialized and this limits how this class can be used,
	 * so when we are done reading the data from it, we will erace all instances of the simpleXML
	 * objects from the ebookData object.
	 */
	private function removeSimpleXML(){
		$this->ebookData->metadata = null;
		$this->ebookData->manifest = null;
		$this->ebookData->spine = null;
		$this->ebookData->guide = null;
		//Convert all simpleXML to strings.
		$this->ebookData->title = (string)$this->ebookData->title;
		$this->ebookData->language = (string)$this->ebookData->language;
		$this->ebookData->identifier = (string)$this->ebookData->identifier;
		$this->ebookData->creator = (string)$this->ebookData->creator;
		$this->ebookData->contributor = (string)$this->ebookData->contributor;
		$this->ebookData->publisher = (string)$this->ebookData->publisher;
		$this->ebookData->subject = (string)$this->ebookData->subject;
		$this->ebookData->description = (string)$this->ebookData->description;
		$this->ebookData->eBookDate = (string)$this->ebookData->eBookDate;
		$this->ebookData->type = (string)$this->ebookData->type;
		$this->ebookData->format = (string)$this->ebookData->format;
		$this->ebookData->source = (string)$this->ebookData->source;
		$this->ebookData->relation = (string)$this->ebookData->relation;
		$this->ebookData->coverage = (string)$this->ebookData->coverage;
		$this->ebookData->rights = (string)$this->ebookData->rights;

		for($x = 0;$x<count($this->ebookData->spineData);$x+=1){
			$this->ebookData->spineData[$x] = (string)$this->ebookData->spineData[$x];
		}
	}

	/**
	 * Returns the contents of the specified file inside the epub file.
	 * @param file $zipFile is the compressed epub file.
	 * @param string $fileLocation is what you want to open from inside of $zipFile.
	 * @return string returns a string representation of the designated file.
	 **/
	private function readEpubFile($zipFile, $fileLocation){
		$ext = substr($zipFile, strrpos($zipFile, '.') + 1);
		if(strcasecmp($ext, "epub") == 0 && is_file($zipFile)){
			$zip = zip_open($zipFile);
			$contents = "";
			if ($zip) {
			  while ($zip_entry = zip_read($zip)) {
			  		$zipEntryName = zip_entry_name($zip_entry); 
			    	if($zipEntryName == $fileLocation && zip_entry_open($zip, $zip_entry))	{
			    		$contents = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
			    		zip_entry_close($zip_entry);
			    	}
			  }
				zip_close($zip);
				if(isset($contents)){
					return $contents;
				}else{
					$this->errorOccurred = true;
					$this->error = "Could not find the file ".$fileLocation." inside of ".$zipFile;
					trigger_error("Could not find the file ".$fileLocation." inside of ".$zipFile, E_USER_ERROR);
					return;
				}
			}
		}else{
			$this->errorOccurred = true;
			$this->error = "Can't find the file ".$zipFile;
			trigger_error("Can't find the file ".$zipFile, E_USER_ERROR);
			return;
		}
	}

/***********
 * Public *
 ***********/

	/**
	 * Constructor
	 * @param $epub Location of an .epub file for processing or an instance of an ebookData object.
	 **/
	public function ebookRead($epub){
		if(is_a($epub, 'ebookData')){
			$this->ebookData = $epub;
		}else if(is_file($epub)){
			$this->ebookData = new ebookData();
			if(!file_exists($epub)){
	      		trigger_error('File '.basename($epub).' not found', E_USER_ERROR);
	    	}else{
				$ext = substr($epub, strrpos($epub, '.') + 1);
				if(strcasecmp($ext, "epub") == 0){
					$this->ebookData->epub = $epub;
					$this->processEpub();
				}else{
					trigger_error("You must input a file with the extension epub, found $ext", E_USER_ERROR);
				}
			}
		}else{
			trigger_error("improper parameters input for the ebookRead class decleration.", E_USER_ERROR);
		}
	}

/*********************
 * Getter Functions *
 *********************/
	/**
	 * Gives you the title by default or the attribute specified
	 * @param string $optional optional attributes are, XmlLang.
	 * @return string returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcTitle($optional = null){
		switch($optional){
			case "XmlLang":
				return $this->ebookData->titleXmlLang;
				break;
			default:
				return $this->ebookData->title;
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param string $optional optional attributes are, XsiType.
	 * @return string returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcLanguage($optional = null){
		switch($optional){
			case "XsiType":
				return $this->ebookData->languageXsiType;
				break;
			default:
				return $this->ebookData->language;
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param string $optional optional attributes are, Scheme, XsiType, Id.
	 * @return string returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcIdentifier($optional = null){
		switch($optional){
			case "Scheme":
				return $this->ebookData->identifierScheme;
				break;
			case "XsiType":
				return $this->ebookData->identifierXsiType;
				break;
			case "Id":
				return $this->ebookData->identifierId;
				break;
			default:
				return $this->ebookData->identifier;
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param string $optional optional attributes are, Role, XmlLang, OpfFileAs.
	 * @return string returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcCreator($optional = null){
		switch($optional){
			case "Role":
				return $this->ebookData->creatorRole;
				break;
			case "XmlLang":
				return $this->ebookData->creatorXmlLang;
				break;
			case "OpfFileAs":
				return $this->ebookData->creatorOpfFileAs;
				break;
			default:
				return $this->ebookData->creator;
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param string $optional optional attributes are, Role, XmlLang, OpfFileAs.
	 * @return string returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcContributor($optional = null){
		switch($optional){
			case "Role":
				return $this->ebookData->contributorRole;
				break;
			case "XmlLang":
				return $this->ebookData->contributorXmlLang;
				break;
			case "OpfFileAs":
				return $this->ebookData->contributorOpfFileAs;
				break;
			default:
				return $this->ebookData->contributor;
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param string $optional optional attributes are, XmlLang.
	 * @return string returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcPublisher($optional = null){
		switch($optional){
			case "XmlLang":
				return $this->ebookData->publisherXmlLang;
				break;
			default:
				return $this->ebookData->publisher;
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param string $optional optional attributes are, XmlLang.
	 * @return string returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcSubject($optional = null){
		switch($optional){
			case "XmlLang":
				return $this->ebookData->subjectXmlLang;
				break;
			default:
				return $this->ebookData->subject;
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param string $optional optional attributes are, XmlLang.
	 * @return string returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcDescription($optional = null){
		switch($optional){
			case "XmlLang":
				return $this->ebookData->descriptionXmlLang;
				break;
			default:
				return $this->ebookData->description;
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param string $optional optional attributes are, Event, XsiType.
	 * @return string returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcDate($optional = null){
		switch($optional){
			case "Event":
				return $this->ebookData->eBookdateEvent;
				break;
			case "XsiType":
				return $this->ebookData->eBookdateXsiType;
				break;
			default:
				return $this->ebookData->eBookDate;
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param string $optional optional attributes are, XsiType.
	 * @return string returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcType($optional = null){
		switch($optional){
			case "XsiType":
				return $this->ebookData->typeXsiType;
				break;
			default:
				return $this->ebookData->type;
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param string $optional optional attributes are, XsiType.
	 * @return string returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcFormat($optional = null){
		switch($optional){
			case "XsiType":
				return $this->ebookData->formatXsiType;
				break;
			default:
				return $this->ebookData->format;
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param string $optional optional attributes are, XmlLang.
	 * @return string returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcSource($optional = null){
		switch($optional){
			case "XmlLang":
				return $this->ebookData->sourceXmlLang;
				break;
			default:
				return $this->ebookData->source;
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param string $optional optional attributes are, XmlLang.
	 * @return string returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcRelation($optional = null){
		switch($optional){
			case "XmlLang":
				return $this->ebookData->relationXmlLang;
				break;
			default:
				return $this->ebookData->relation;
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param string $optional optional attributes are, XmlLang.
	 * @return string returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcCoverage($optional = null){
		switch($optional){
			case "XmlLang":
				return $this->ebookData->coverageXmlLang;
				break;
			default:
				return $this->ebookData->coverage;
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param string $optional optional attributes are, XmlLang.
	 * @return string returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcRights($optional = null){
		switch($optional){
			case "XmlLang":
				return $this->ebookData->rightsXmlLang;
				break;
			default:
				return $this->ebookData->rights;
		}
	}

	/**
 	 * Gets the manifest item desired
 	 * @param int $index The index number of the manifest item.
 	 * @param string $item What attribute do you want from the manifest (id, href, or media-type).
 	 * @return string the requested data if it exists else returns null.
 	 **/
	public function getManifestItem($index, $item){
		if($item == 'media-type')
			$item = 'type';
		return $this->ebookData->manifestData[$index]->$item;
	}

	/**
	 * Get the manifest item by the requested ID.
	 * @param string $id The requested ID
	 * @return manifest Returns the manifest item you were looking for or if not found null.
	 */
	public function getManifestById($id){
		foreach($this->ebookData->manifestData as $man){
			if($man->id == $id){
				return $man;
			}
		}
		return null;
	}
	
	public function getManifestSize(){
		return count($this->ebookData->manifestData);
	}

	/**
 	 * Gets the manifest item desired
 	 * @param string $item What attribute do you want from the manifest (id, href, or media-type).
 	 * @return string a array of strings of the requested data if it exists else returns null.
 	 **/
	public function getManifest($item){
		if($item == 'media-type')
			$item = 'type';
		$array = array();
		foreach($this->ebookData->manifestData as $man){
			array_push($array, $man->$item);
		}
		return $array;
	}

	/**
	 * Gets a desired spine item
	 * @param int $index The index of the spine item to return.
	 * @return string The requested data. If the data dosen't exist then return null.
	 **/
	 public function getSpineItem($index){
		return $this->ebookData->spineData[$index];
	 }

	 /**
	 * Gets a desired spine item
	 * @return string a array of strings of the requested data. If the data dosen't exist then return null.
	 **/
	 public function getSpine(){
		return $this->ebookData->spineData;
	 }

	 /**
	  * Gets the table of contents
	  * @return tocItem array of tocItems with the name of each chapeter and where they are located.
	  */
	  public function getTOC(){
	  	return $this->ebookData->tocData;
	  }
	  
	/**
	  * Gets the table of contents
	  * @return tocItem array of tocItems with the name of each chapeter and where they are located.
	  */
	public function getTitle(){
	  	return $this->ebookData->title;
	}

	 /**
 	 * Gets the guide item desired
 	 * @param int $index The index number of the guide item.
 	 * @param string $item What attribute do you want from the guide (title, href, or type).
 	 * @return string a string of the requested data if it exists else returns null.
 	 **/
	public function getGuideItem($index, $item){
		return $this->ebookData->guideData[$index]->$item;
	}

	/**
 	 * Gets the guide item desired
 	 * @param string $item What attribute do you want from the guide (title, href, or type).
 	 * @return string a string of the requested data if it exists else returns null.
 	 **/
	public function getGuide($item){
		if(isset($this->ebookData->guideData)){
			$array = array();
			if(!is_array($this->ebookData->guideData))
				$this->ebookData->guideData = array($this->ebookData->guideData);
			foreach($this->ebookData->guideData as $gud){
				array_push($array, $gud->$item);
			}
			return $array;
		}else{
			return null;
		}
	}

	/**
	 * Get a file path by extenshion
	 * @param string $ext The file exenshion of the file your looking for.
	 * @return string Null if there are no results, a single file path if only one file exists, or an array
	 * of paths if more then one file by that extenshion exist.
	 **/
	public function getFilePath($ext){
		if(isset($this->ebookData->tempDir)){
			return $this->fildFileByExt($this->ebookData->tempDir, $ext);
		}else if(isset($this->ebookData->epub)){
			return $this->fildFileByExt($this->ebookData->epub, $ext);
		}else{
			trigger_error("Can't find a file by extenshion. No resources to search.", E_USER_ERROR);
		}
	}

	/**
	 * Gets the location of all the content inside of the epub.
	 * @return string the file location of the content folder.
	 */
	 public function getContentLoc(){
	 	return $this->ebookData->contentFolder;
	 }

 	/**
	 * Will give you the requested file out of the epub
	 * Note: When finding files based on the manifest's href attribute, the file locations are
	 * relative to where the opf file is located in the file structure.
	 * @param string $location Where file is located inside of the content area.
	 * @return string The contents of the requested file.
	 **/
	public function getContentFile($location){
		if(isset($this->ebookData->tempDir)){
			if (file_exists($this->ebookData->contentFolder.$location)){
			 return file_get_contents($this->ebookData->contentFolder.$location);
			}else{
				//Try replacing %20 with spaces
				$location = str_replace('%20', ' ', $location);
				if (file_exists($this->ebookData->contentFolder.$location)){
	       return file_get_contents($this->ebookData->contentFolder.$location);
	      }else{
	        trigger_error("Can't open content. There is no content.", E_USER_ERROR);
	      }
			}
		}else if(isset($this->ebookData->epub)){
			return $this->readEpubFile($this->ebookData->epub, $this->ebookData->contentFolder.$location);
		}else{
			trigger_error("Can't open content. There is no content.", E_USER_ERROR);
		}
	}

	/**
	 * Give the id of the file you want as it appears in the manifest.
	 * @param string $item The manifest item wanted to output.
	 * @return string The contents of the requested item.
	 */
	 public function getContentById($item){
		foreach($this->ebookData->manifestData as $man){
			if($man->id == $item){
				return $this->getContentFile($man->href);
			}
		}
		return null;
	 }

	/**
	 * Get the eBook data object. Holds all the data from the epub.
	 * @return eBookData Sends back the eBookData
	 */
	public function getEBookDataObject(){
		return $this->ebookData;
	}
}
?>