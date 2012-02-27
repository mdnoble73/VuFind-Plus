<?php
	/*******************************************************************************
	* Software: Open eBook Library                                                 *
	* Version:  0.7 Alpha                                                          *
	* Date:     2008-07-21                                                         *
	* Author:   Jacob Weigand of RIT's Open Publishing Lab                         *
	* License:  GNU General Public License (GPL)                                   *
	*                                                                              *
	* You may use, modify and redistribute this software as you wish.              *
	********************************************************************************/
require_once('ebookRead.php');
require_once('ebookWrite.php');
require_once('ebookData.php');
/**
 * This is the primary class intented for general use. This class uses the open ebook reader and the open
 * ebook writter classes. If you wish to only read or write you can use the single libary insted of this
 * combined one. This class is only intended to make it easier to see and use the reader and writter, There
 * should only be calls to functions from reader or writter here.
 **/
class ebook{
	private $ebookReader;
	private $ebookWritter;
	private $ebookData;
	/**
	 * Set "useSessions" to true if you want to use this library with amfphp.
	 * This will make the ebookData persistent by storing the data in a session.
	 */
	private $useSessions = true;
	
	public function readErrorOccurred(){
		return $this->ebookReader->errorOccurred;
	}
	public function readError(){
		return $this->ebookReader->error;
	}

	/**
	 * Constructor
	 * @param $ebookData epubFile Optional. Use this if you are editing an existing epub file.
	 */
	public function ebook($epub = null){
		if(is_a($epub, 'ebookData')){
			$this->ebookData = $epub;
			$this->ebookReader = new ebookRead($this->ebookData);
			$this->ebookWritter = new ebookWrite($this->ebookData);
		}elseif(is_file($epub)){
			$this->ebookReader = new ebookRead($epub);
			$this->ebookWritter = new ebookWrite($this->ebookReader->getEBookDataObject());
		}else{
			if($this->useSessions){
				session_write_close();
				session_start();
				if(isset($_SESSION['data'])){
					$this->ebookData = unserialize($_SESSION['data']);
				}else{
					$this->ebookData = new ebookData();
				}
			}else{
				$this->ebookData = new ebookData();
			}
			$this->ebookReader = new ebookRead($this->ebookData);
			$this->ebookWritter = new ebookWrite($this->ebookData);
		}
	}

	/**
 	 * Puts together the eBook and places it in the desired directory.
 	 * @param $name The name you want the epub file to have. Don't include a file extenshion.
 	 * @param $dest The location you want the final epub file.
 	 */
 	public function buildEPUB($name, $dest){
		$this->ebookWritter->buildEPUB($name, $dest);
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param $optional optional attributes are, XmlLang.
	 * @return returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcTitle($optional = null){
		return $this->ebookReader->getDcTitle($optional);
	}

	/**
	 * Sets the title
	 * @param $title The title to be set. If you want set more then one send an array.
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcTitle($title, $index = null){
		$this->ebookWritter->setDcTitle($title, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Set the optional title attributes.
	 * @param $attrib The name of the optional attributes to be set. Optional attributes are XmlLang.
	 * @param $attribValue The value to be set for the optional attribute.
	 * @return true if set or false if not.
	 */
	 public function setDcTitleAttrib($attrib, $attribValue, $index = null){
	 	$this->ebookWritter->setDcTitleAttrib($attrib, $attribValue, $index);
	 	if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	 }

	/**
	 * Gives you the title by default or the attribute specified
	 * @param $optional optional attributes are, XsiType.
	 * @return returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcLanguage($optional = null){
		return $this->ebookReader->getDcLanguage($optional);
	}

	/**
	 * Sets the language. Multiple instances are permitted.
	 * @param $language The language to be set. If you want set more then one language send an array into this parameter.
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcLanguage($language, $index = null){
		$this->ebookWritter->setDcLanguage($language, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Sets the optional language attributes.
	 * @param $attrib The name of the optional attributes to be set. Optional attributes are XsiType.
	 * @param $attribValue The value to be set for the optional attribute. If you want set more then one language attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate language index
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return true if set or false if not.
	 */
	public function setDcLanguageAttrib($attrib, $attribValue, $index = null){
		$this->ebookWritter->setDcLanguageAttrib($attrib, $attribValue, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param $optional optional attributes are, Scheme, XsiType, Id.
	 * @return returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcIdentifier($optional = null){
		return $this->ebookReader->getDcIdentifier($optional);
	}

	/**
	 * Sets the Identifier. Multiple instances are permitted.
	 * @param $identifier the Identifier to be set. If you want set more then one Identifier send an array into this parameter.
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcIdentifier($identifier, $index = null){
		$this->ebookWritter->setDcIdentifier($identifier, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Sets the optional identifier attributes.
	 * @param $attrib Id is a required attribute. The name of the optional attributes to be set.
	 * Optional attributes are Scheme, XsiType.
	 * @param $attribValue The value to be set for the optional attribute. If you want set more then one identifier attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate identifier index
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return true if set or false if not.
	 */
	public function setDcIdentifierAttrib($attrib, $attribValue, $index = null){
		$this->ebookWritter->setDcIdentifierAttrib($attrib, $attribValue, $index = null);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param $optional optional attributes are, Role, XmlLang, OpfFileAs.
	 * @return returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcCreator($optional = null){
		return $this->ebookReader->getDcCreator($optional);
	}

	/**
	 * Sets the creator. Multiple instances are permitted.
	 * @param $creator The creator to be set. If you want set more then one creator send an array into this parameter.
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcCreator($creator, $index = null){
		$this->ebookWritter->setDcCreator($creator, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Sets the optional creator attributes.
	 * @param $attrib The name of the optional attributes to be set. optional attributes are, Role, XmlLang, OpfFileAs.
	 * @param $attribValue The value to be set for the optional attribute. If you want set more then one creator attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate creator index
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return true if set or false if not.
	 */
	public function setDcCreatorAttrib($attrib, $attribValue, $index = null){
		$this->ebookWritter->setDcCreatorAttrib($attrib, $attribValue, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param $optional optional attributes are, Role, XmlLang, OpfFileAs.
	 * @return returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcContributor($optional = null){
		return $this->ebookReader->getDcContributor($optional);
	}

	/**
	 * Sets the contributor. Multiple instances are permitted.
	 * @param $contributor The contributor to be set. If you want set more then one contributor send an array into this parameter.
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcContributor($contributor, $index = null){
		$this->ebookWritter->setDcContributor($contributor, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Sets the optional contributor attributes.
	 * @param $attrib The name of the optional attributes to be set. Optional attributes are, Role, XmlLang, OpfFileAs.
	 * @param $attribValue The value to be set for the optional attribute. If you want set more then one contributor attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate contributor index
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return true if set or false if not.
	 */
	public function setDcContributorAttrib($attrib, $attribValue, $index = null){
		$this->ebookWritter->setDcContributorAttrib($attrib, $attribValue, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param $optional optional attributes are, XmlLang.
	 * @return returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcPublisher($optional = null){
		return $this->ebookReader->getDcPublisher($optional);
	}

	/**
	 * Sets the publisher. Multiple instances are permitted.
	 * @param $publisher The publisher to be set. If you want set more then one publisher send an array into this parameter.
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcPublisher($publisher, $index = null){
		$this->ebookWritter->setDcPublisher($publisher, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Sets the optional publisher attributes.
	 * @param $attrib The name of the optional attributes to be set. Optional attributes are, XmlLang.
	 * @param $attribValue The value to be set for the optional attribute. If you want set more then one publisher attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate publisher index
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return true if set or false if not.
	 */
	public function setDcPublisherAttrib($attrib, $attribValue, $index = null){
		$this->ebookWritter->setDcPublisherAttrib($attrib, $attribValue, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param $optional optional attributes are, XmlLang.
	 * @return returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcSubject($optional = null){
		return $this->ebookReader->getDcSubject($optional);
	}

		/**
	 * Sets the subject. Multiple instances are permitted.
	 * @param $subject The subject to be set. If you want set more then one subject send an array into this parameter.
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcSubject($subject, $index = null){
		$this->ebookWritter->setDcSubject($subject, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Sets the optional subject attributes.
	 * @param $attrib The name of the optional attributes to be set. Optional attributes are, XmlLang.
	 * @param $attribValue The value to be set for the optional attribute. If you want set more then one subject attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate subject index
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return true if set or false if not.
	 */
	public function setDcSubjectAttrib($attrib, $attribValue, $index = null){
		$this->ebookWritter->setDcSubjectAttrib($attrib, $attribValue, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param $optional optional attributes are, XmlLang.
	 * @return returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcDescription($optional = null){
		return $this->ebookReader->getDcDescription($optional);
	}

	/**
	 * Sets the description. Multiple instances are permitted.
	 * @param $description The description to be set. If you want set more then one description send an array into this parameter.
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcDescription($description, $index = null){
		$this->ebookWritter->setDcDescription($description, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Sets the optional description attributes.
	 * @param $attrib The name of the optional attributes to be set. Optional attributes are, XmlLang.
	 * @param $attribValue The value to be set for the optional attribute. If you want set more then one description attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate description index
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return true if set or false if not.
	 */
	public function setDcDescriptionAttrib($attrib, $attribValue, $index = null){
		$this->ebookWritter->setDcDescriptionAttrib($attrib, $attribValue, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param $optional optional attributes are, Event, XsiType.
	 * @return returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcDate($optional = null){
		return $this->ebookReader->getDcDate($optional);
	}

	/**
	 * Sets the date. Multiple instances are permitted.
	 * @param $date The date to be set. If you want set more then one date send an array into this parameter.
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcDate($eBookdate, $index = null){
		$this->ebookWritter->setDcDate($eBookdate, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}


	/**
	 * Sets the optional date attributes.
	 * @param $attrib The name of the optional attributes to be set. Optional attributes are, Event, XsiType.
	 * @param $attribValue The value to be set for the optional attribute. If you want set more then one date attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate date index
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return true if set or false if not.
	 */
	public function setDcDateAttrib($attrib, $attribValue, $index = null){
		$this->ebookWritter->setDcDateAttrib($attrib, $attribValue, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param $optional optional attributes are, XsiType.
	 * @return returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcType($optional = null){
		return $this->ebookReader->getDcType($optional);
	}

	/**
	 * Sets the type. Multiple instances are permitted.
	 * @param $type The type to be set. If you want set more then one type send an array into this parameter.
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcType($type, $index = null){
		$this->ebookWritter->setDcType($type, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Sets the optional type attributes.
	 * @param $attrib The name of the optional attributes to be set. Optional attributes are XsiType.
	 * @param $attribValue The value to be set for the optional attribute. If you want set more then one type attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate type index
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return true if set or false if not.
	 */
	public function setDcTypeAttrib($attrib, $attribValue, $index = null){
		$this->ebookWritter->setDcTypeAttrib($attrib, $attribValue, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param $optional optional attributes are, XsiType.
	 * @return returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcFormat($optional = null){
		return $this->ebookReader->getDcFormat($optional);
	}

	/**
	 * Sets the format. Multiple instances are permitted.
	 * @param $format The format to be set. If you want set more then one format send an array into this parameter.
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcFormat($format, $index = null){
		$this->ebookWritter->setDcFormat($format, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Sets the optional format attributes.
	 * @param $attrib The name of the optional attributes to be set. Optional attributes are XsiType.
	 * @param $attribValue The value to be set for the optional attribute. If you want set more then one format attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate format index
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return true if set or false if not.
	 */
	public function setDcFormatAttrib($attrib, $attribValue, $index = null){
		$this->ebookWritter->setDcFormatAttrib($attrib, $attribValue, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param $optional optional attributes are, XmlLang.
	 * @return returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcSource($optional = null){
		return $this->ebookReader->getDcSource($optional);
	}

	/**
	 * Sets the source. Multiple instances are permitted.
	 * @param $source The source to be set. If you want set more then one source send an array into this parameter.
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcSource($source, $index = null){
		$this->ebookWritter->setDcSource($source, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Set the optional source attributes.
	 * @param $attrib The name of the optional attributes to be set. Optional attributes are XmlLang.
	 * @param $attribValue The value to be set for the optional attribute. If you want set more then one source attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate source index
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return true if set or false if not.
	 */
	public function setDcSourceAttrib($attrib, $attribValue, $index = null){
		$this->ebookWritter->setDcSourceAttrib($attrib, $attribValue, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param $optional optional attributes are, XmlLang.
	 * @return returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcRelation($optional = null){
		return $this->ebookReader->getDcRelation($optional);
	}

	/**
	 * Sets the relation. Multiple instances are permitted.
	 * @param $relation The relation to be set. If you want set more then one relation send an array into this parameter.
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcRelation($relation, $index = null){
		$this->ebookWritter->setDcRelation($relation, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Set the optional relation attributes.
	 * @param $attrib The name of the optional attributes to be set. Optional attributes are XmlLang.
	 * @param $attribValue The value to be set for the optional attribute. If you want set more then one relation attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate relation index
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return true if set or false if not.
	 */
	public function setDcRelationAttrib($attrib, $attribValue, $index = null){
		$this->ebookWritter->setDcRelationAttrib($attrib, $attribValue, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param $optional optional attributes are, XmlLang.
	 * @return returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcCoverage($optional = null){
		return $this->ebookReader->getDcCoverage($optional);
	}

	/**
	 * Sets the coverage. Multiple instances are permitted.
	 * @param $coverage The coverage to be set. If you want set more then one coverage send an array into this parameter.
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcCoverage($coverage, $index = null){
		$this->ebookWritter->setDcCoverage($coverage, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Set the optional coverage attributes.
	 * @param $attrib The name of the optional attributes to be set. Optional attributes are XmlLang.
	 * @param $attribValue The value to be set for the optional attribute. If you want set more then one coverage attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate coverage index
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return true if set or false if not.
	 */
	public function setDcCoverageAttrib($attrib, $attribValue, $index = null){
		$this->ebookWritter->setDcCoverageAttrib($attrib, $attribValue, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Gives you the title by default or the attribute specified
	 * @param $optional optional attributes are, XmlLang.
	 * @return returns the title when nothing is specified for $optional or returns the
	 * attribute requested.
	 */
	public function getDcRights($optional = null){
		return $this->ebookReader->getDcRights($optional);
	}

		/**
	 * Sets the rights. Multiple instances are permitted.
	 * @param $rights The rights to be set. If you want set more then one rights send an array into this parameter.
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 */
	public function setDcRights($rights, $index = null){
		$this->ebookWritter->setDcRights($rights, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Set the optional rights attributes.
	 * @param $attrib The name of the optional attributes to be set. Optional attributes are XmlLang.
	 * @param $attribValue The value to be set for the optional attribute. If you want set more then one rights attribute send
	 * an array into this parameter. Make sure the attributes corresponde the appropriate rights index
	 * @param $index if there is more then one instance of this item, specify it and that item will be edited.
	 * @return true if set or false if not.
	 */
	public function setDcRightsAttrib($attrib, $attribValue, $index = null){
		$this->ebookWritter->setDcRightsAttrib($attrib, $attribValue, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Gets the location of all the content inside of the epub.
	 * @return the file location of the content folder.
	 */
	 public function getContentLoc(){
	 	return $this->ebookReader->getContentLoc();
	 }
	 
	 public function getManifestSize(){
	 	return $this->ebookReader->getManifestSize();
	 }

 	/**
 	 * Gets the manifest item desired
 	 * @param $index The index number of the manifest item.
 	 * @param $item What attribute do you want from the manifest (id, href, or media-type).
 	 * @return a string of the requested data if it exists else returns null.
 	 **/
	public function getManifestItem($index, $item){
		return $this->ebookReader->getManifestItem($index, $item);
	}

	/**
 	 * Gets the manifest item desired
 	 * @param $item What attribute do you want from the manifest (id, href, or media-type).
 	 * @return a array of strings of the requested data if it exists else returns null.
 	 **/
	public function getManifest($item){
		return $this->ebookReader->getManifest($item);
	}

/**
 	 * This is used for adding content files to an epub. Make sure you have write permission to the
 	 * destination directory.
 	 * @param $fileLoc Where is the file currently located.
 	 * @param $subDir If you want the file in a subdirectory of the content folder.
 	 * @param $name Short discriptive name of what the content item is. eg: Chapter 3.
 	 * @param $mime the mime type of the file being added.
 	 * @param $fallBackId the id of an item you wish to use as a fallback item.
 	 * @return Returns true if the file was added successfully and false if not.
 	 */
	public function addContentFile($fileLoc, $name, $mime, $subDir = null, $fallBackId = null){
		$this->ebookWritter->addContentFile($fileLoc, $name, $mime, $subDir, $fallBackId);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}

	/**
	 * Given a manifest Id this function will remove the specified file and all referances
	 * from the manifest, spine, and guide.
	 * @param string $manId The Manifest ID to be deleted.
	 * @return bool Returns true if the file and all referances were removed.
	 */
	public function removeContentFile($manId){
		return $this->ebookWritter->removeContentFile($manId);
	}

	/**
	 * Gets a desired spine item
	 * @param $index The index of the spine item to return.
	 * @return a string of the requested data. If the data dosen't exist then return null.
	 **/
	 public function getSpineItem($index){
		return $this->ebookReader->getSpineItem($index);
	 }

	 /**
	 * Gets a desired spine item
	 * @return a array of strings of the requested data. If the data dosen't exist then return null.
	 **/
	 public function getSpine(){
		return $this->ebookReader->getSpine();
	 }
	 
	/**
	  * Gets the table of contents
	  * @return tocItem array of tocItems with the name of each chapeter and where they are located.
	  */
	public function getTOC(){
	  	return $this->ebookReader->getTOC();
	}
	
	/**
	  * Gets the table of contents
	  * @return tocItem array of tocItems with the name of each chapeter and where they are located.
	  */
	public function getTitle(){
	  	return $this->ebookReader->getTitle();
	}

	/**
	 * Sets a desired spine order. The spine is used to determine the reading order.
	 * @param $spineId Send an array of ID names as they appear in the manifest in the order you want them read.
	 **/
	 public function setSpine($spineId){
	 	$this->ebookWritter->setSpine($spineId);
	 	if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	 }

	 /**
	  * Used to tell the spine what manifest item is the Table Of Contents. This does not need to
	  * be set, this is optional.
	  * @param $toc The manifest item that is the table of contents.
	  */
	 public function setSpineToc($toc){
		$this->ebookWritter->setSpineToc($toc);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	 }

	 /**
 	 * Gets the guide item desired
 	 * @param $index The index number of the guide item.
 	 * @param $item What attribute do you want from the guide (title, href, or type).
 	 * @return a string of the requested data if it exists else returns null.
 	 **/
	public function getGuideItem($index, $item){
		return $this->ebookReader->getGuideItem($index, $item);
	}

	/**
 	 * Gets the guide item desired
 	 * @param $item What attribute do you want from the guide (title, href, or type).
 	 * @return a string of the requested data if it exists else returns null.
 	 **/
	public function getGuide($item){
		return $this->ebookReader->getGuide($item);
	}

	 /**
 	 * sets the guide item desired. Arrays can be sent into the attributes to set more then one
 	 * guide item at a time. The guide element identifies fundamental structural components of the publication,
	 * to enable Reading Systems to provide convenient access to them.
 	 * @param $title Name of the guide item.
 	 * @param $type The required type attribute describes the publication component referenced by the href
	 * attribute. Type should be of this list cover, title-page, toc "table of contents", index "back-of-book
	 * style index", glossary, acknowledgements, bibliography, colophon, copyright-page, dedication, epigraph,
	 * foreword, loi "list of illustrations", lot "list of tables", notes, and preface.
 	 * @param $href location of the guide item.
 	 * @param $index The index number of the guide item to be set.
 	 **/
	public function setGuide($title, $type, $href, $index = null){
		$this->ebookWritter->setGuide($title, $type, $href, $index);
		if($this->useSessions){
			$_SESSION['data'] = serialize($this->ebookData);
		}
	}


	/**
	 * Get a file path by extenshion
	 * @param $ext The file exenshion of the file your looking for.
	 * @return Null if there are no results, a single file path if only one file exists, or an array
	 * of paths if more then one file by that extenshion exist.
	 **/
	public function getFilePath($ext){
		return $this->ebookReader->getFilePath($ext);
	}

	/**
	 * Will give you the requested file out of the epub
	 * Note: When finding files based on the manifest's href attribute, the file locations are
	 * relative to where the opf file is all ready located in the file structure. So if the opf file
	 * is at /ops/contnet.opf and your image is at /ops/img/wolf.jpg then your manifest href for that
	 * image will read /img/wolf.jpg. Use basename(getFilePath("opf")); for the directories leading to the OPF file.
	 * @param $location Where inside of the epub is the file you want
	 * @return a string of the contents of the requested file.
	 **/
	public function getContentFile($location){
		return $this->ebookReader->getContentFile($location);
	}

	/**
	 * Give the id of the file you want as it appears in the manifest.
	 * @param string $item The manifest item wanted to output.
	 * @return string The contents of the requested item.
	 */
	 public function getContentById($item){
		return $this->ebookReader->getContentById($item);
	 }
	
	/**
	 * Get the eBook data object. Holds all the data from the epub.
	 * @return Sends back the eBookData
	 */
	public function getEBookDataObject(){
		return $this->ebookReader->getEBookDataObject();
	}

	/**
	 * Destroys a session. This is only used if useSessions is set to true.
	 */
	public function sessionDestroy(){
		if($this->useSessions){
			session_destroy();
		}
	}
}
?>