<?php
	/*******************************************************************************
	* Software: Open eBook Data Model                                              *
	* Version:  0.6 Alpha                                                          *
	* Date:     2008-07-21                                                         *
	* Author:   Jacob Weigand of RIT's Open Publishing Lab                         *
	* License:  GNU General Public License (GPL)                                   *
	*                                                                              *
	* You may use, modify and redistribute this software as you wish.              *
	********************************************************************************/

class ebookData{
	public $epub;						//Path on the server to the .epub file.
	public $tempDir;					//Path on the server to where this epub's temporary files are stored. (used only when writting)
	public $contentFolder;				//Path indide of the epub to where the content files are located.
	public $opfPath;					//Path inside of the epub to the .opf file.
	public $toc;						//Path inside of the epub to the .ncx file (Table of Contents).
	public $xpgt;						//Path inside of the epub to the .xpgt file (ADE Stylesheet).
	public $css;						//Path inside of the epub to the .css file (Cascading Stylesheet).
	//OPF parts
	public $metadata;					//Publication Information
	public $manifest;					//List of every file that is part of this epub
	public $manifestData;				//Store the manifest data
	public $spine;						//Definition of the reading order. List of XHTML files in manifest by id.
	public $spineData;					//Store the spine data
	public $spineToc;					//Optional. Attriute in the spine tag that points to the TOC manifest item.
	public $guide;						//Optional. Catorgizes the main parts of the document
	public $guideData;					//Store the guide data.
	public $tocData;					//Store all the data from in the ncx file.
	//Required Dublin Core data:
	public $title;						//Title of the eBook.
	public $titleXmlLang;				//Optional title attribute. use RFC-3066 format.
	public $language;					//Language of the eBook.
	public $languageXsiType;			//Optional language attribute. Use an appropriate standard term.
	public $identifier;					//Identifer attribute, normaly the ISBN.
	public $identifierId;				//Discribes what the identifier is.
	public $identifierScheme;			//Optional identifier attribute. Unstandardised use something sensible.
	public $identifierXsiType;			//Optional identifier attribute. Use an appropriate standard term.
	//Optional Dublin Core data:
	public $creator;					//Author of the work.
	public $creatorRole;				//optional creator attribute. see http://www.loc.gov/marc/relators/ for values.
	public $creatorXmlLang;				//Optional creator attribute. use RFC-3066 format.
	public $creatorOpfFileAs;
	public $contributor;				//Contributors to the work
	public $contributorRole;			//Optional contributor attribute. see http://www.loc.gov/marc/relators/ for values.
	public $contributorXmlLang;			//Optional contributor attribute. use RFC-3066 format.
	public $contributorOpfFileAs;
	public $publisher;
	public $publisherXmlLang;			//Optional publisher attribute. use RFC-3066 format.
	public $subject;
	public $subjectXmlLang;				//Optional subject attribute. use RFC-3066 format.
	public $description;
	public $descriptionXmlLang;			//Optional description attribute. use RFC-3066 format.
	public $eBookdate;
	public $eBookdateEvent;				//Optional eBookdate attribute. Unstandardised use something sensible.
	public $eBookdateXsiType;			//Optional eBookdate attribute. Use an appropriate standard term (such as W3CDTF)
	public $type;
	public $typeXsiType;				//Optional type attribute. Use an appropriate standard term.
	public $format;
	public $formatXsiType;				//Optional format attribute. Use an appropriate standard term.
	public $source;
	public $sourceXmlLang;			 	//Optional source attribute. use RFC-3066 format.
	public $relation;
	public $relationXmlLang;			//Optional relation attribute. use RFC-3066 format.
	public $coverage;
	public $coverageXmlLang;			//Optional coverage attribute. use RFC-3066 format.
	public $rights;
	public $rightsXmlLang;				//Optional rights attribute. use RFC-3066 format.
}
class manifest{
	public $id;
	public $href;
	public $type;
	public $fallback;
}

/**
 * The guide element identifies fundamental structural components of the publication,
 * to enable Reading Systems to provide convenient access to them.
 */
class guide{
	public $type;
	public $title;
	public $href;
}
class tagSet{
	public $tag;
	public $value;
}
class tocItem{
	public $title;
	public $src;
}
?>