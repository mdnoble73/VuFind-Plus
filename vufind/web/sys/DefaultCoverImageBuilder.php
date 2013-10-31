<?php
/**
 * Creates a default image for a cover based on a default background.
 * Overlays with title and author
 * Based on work done by Juan Gimenez at Douglas County Libraries
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 10/30/13
 * Time: 5:17 PM
 */

class DefaultCoverImageBuilder {
	private $titleConst = array(
		"maxCharPerLine"=>13,
		"fontSize"=>26,
		"pixelsCharacter"=>21,
		"YPositionFirstLineOnCover" => 40, //May be more than one line for the title.
		"incrementYPosition" => 36
	);

	private $authorConst = array(
		"maxCharPerLine"=>20, //Manually tested
		"fontSize"=>18,
		"pixelsCharacter"=>14, // 280/20
		"YPositionFirstLineAuthorImage" => 35,
		"incrementYPosition" => 30
	);

	const angle = 0; //The text angle is always Zero
	const imageWidth = 280; //Pixels
	const imageHeight = 400; // Pixels
	const imagePrintableAreaHeight = 400; //Area printable in Pixels
	private $fontText;
	private $colorText = array("red"=>1, "green"=>1, "blue"=>1);

	public function __construct()
	{
		$this->fontText = ROOT_DIR .'/fonts/JosefinSans-Bold.ttf';
	}

	public function getCover($title, $author, $type)
	{
		$titleAuthorData = $this->getTitleAuthorImage($title, $author);

		$coverName = strtolower(preg_replace('/\W/', '',$type));

		if (!file_exists(ROOT_DIR.'/images/blankCovers/'.$coverName.'.jpg')){
			$coverName = 'books';
		}
		$blankCover = imagecreatefromjpeg(ROOT_DIR.'/images/blankCovers/'.$coverName.'.jpg');

		$paddingLeft = 0;
		if($titleAuthorData['height'] > self::imagePrintableAreaHeight)
		{
			$titleAuthorData = $this->scaleImageByHeight($titleAuthorData['resource'], $titleAuthorData['width'], $titleAuthorData['height']);
			$paddingLeft = ((self::imageWidth - $titleAuthorData['width']) / 2);
		}

		//$paddingTop = (self::imageHeight - self::imagePrintableAreaHeight);
		//$paddingTop += intval((self::imagePrintableAreaHeight - $titleAuthorData['height'])/2);
		$paddingTop = 10;

		imagecopymerge($blankCover, $titleAuthorData['resource'], $paddingLeft, $paddingTop, 0, 0, $titleAuthorData['width'], $titleAuthorData['height'], 100);

		return $this->prepareReturnImageMetadata($blankCover, self::imageWidth, self::imageHeight);
	}

	/**
	 * Scale a Image to a proper width
	 * @param resource $imResource
	 * @param integer $width
	 * @param integer $height
	 *
	 * @return array
	 */
	private function scaleImageByHeight($imResource, $width, $height)
	{
		$newHeight = self::imagePrintableAreaHeight;
		$percentHeightShrink = (self::imagePrintableAreaHeight * 100) / $height;
		$newWidth = intval( ($percentHeightShrink * $width) / 100);
		return $this->scaleImage($newWidth, $newHeight, $width, $height, $imResource);
	}

	/**
	 * Scale a Image to a proper width
	 * @param resource $imResource
	 * @param integer $width
	 * @param integer $height
	 *
	 * @return array
	 */
	private function scaleImageByWidth($imResource, $width, $height)
	{
		$newWidth = self::imageWidth;
		$percentWidthShrink = (self::imageWidth * 100) / $width;
		$newHeight = intval( ($percentWidthShrink * $height) / 100);
		return $this->scaleImage($newWidth, $newHeight, $width, $height, $imResource);
	}

	private function scaleImage($newWidth, $newHeight, $width, $height, $imResource)
	{
		$thumb = imagecreatetruecolor($newWidth, $newHeight);
		$black = imagecolorallocate($thumb, 0, 0, 0);
		imagecolortransparent($thumb, $black); //Make the image transparent. Black is the background color by default.
		imagecopyresized($thumb, $imResource, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		return $this->prepareReturnImageMetadata($thumb, $newWidth, $newHeight);
	}

	public function getXValueTitleLine($titleLine, $width)
	{
		return $this->getXValueLine($titleLine, $width, $this->titleConst);
	}

	public function getXValueAuthorLine($authorLine, $width)
	{
		return $this->getXValueLine($authorLine, $width, $this->authorConst);
	}

	private function getXValueLine($line, $width, $configuration)
	{
		$numberCharacters = strlen($line);
		$pixelsForLine = $numberCharacters * $configuration['pixelsCharacter'];
		$freePixels = $width - $pixelsForLine;
		$freeSidesPixels = intval($freePixels/2);
		$x = $freeSidesPixels;
		return $x;
	}

	private function getTextImage($lines, $configuration, $methodXValue)
	{
		$maxLineLength = $this->maxLineLength($lines);

		$width = $maxLineLength * $configuration['pixelsCharacter'];

		if ($width < self::imageWidth)
		{
			$width = self::imageWidth;
		}

		$height = ( count($lines) + 0.5) * $configuration['incrementYPosition'];
		$fontSize = $configuration['fontSize'];

		$im = imagecreatetruecolor($width, $height);
		$colorText = imagecolorallocate($im, $this->colorText['red'], $this->colorText['green'], $this->colorText['blue']); //#444444

		$black = imagecolorallocate($im, 0, 0, 0);
		imagecolortransparent($im, $black); //Make the image transparent. Black is the background color by default.

		foreach ($lines as $key=>$val)
		{
			$x = $this->$methodXValue($lines[$key], $width);
			$y = $configuration['incrementYPosition'] * ($key+1);
			imagettftext($im, $fontSize, self::angle, $x, $y, -$colorText, $this->fontText, $lines[$key]);
		}

		return $this->prepareReturnImageMetadata($im, $width, $height);
	}

	public function getAuthorImage($author)
	{
		$partsAuthor = $this->getAuthorParts($author);
		return $this->getTextImage($partsAuthor, $this->authorConst, "getXValueAuthorLine");
	}


	public function getTitleImage($title)
	{
		$partsTitle = wordwrap($title, $this->titleConst['maxCharPerLine']);
		$partsTitle = explode("\n", $partsTitle);
		//$partsTitle = $this->getTitleParts($title);
		return $this->getTextImage($partsTitle, $this->titleConst, "getXValueTitleLine");
	}

	public function getTitleAuthorImage($title, $author)
	{
		$imTitle = $this->getTitleImage($title);
		$imAuthor = $this->getAuthorImage($author);

		if($imTitle['width'] > self::imageWidth)
		{
			$imTitle = $this->scaleImageByWidth($imTitle['resource'], $imTitle['width'], $imTitle['height']);
		}

		if($imAuthor['width'] > self::imageWidth)
		{
			$imAuthor = $this->scaleImageByWidth($imAuthor['resource'], $imAuthor['width'], $imAuthor['height']);
		}
		//All the images are now within the width
		//Let's go to glue them

		$newHeight = $imTitle['height'] + $imAuthor['height'];

		$titleAuthorImage = imagecreatetruecolor(self::imageWidth, $newHeight);
		$black = imagecolorallocate($titleAuthorImage, 0, 0, 0);
		imagecolortransparent($titleAuthorImage, $black); //Make the image transparent. Black is the background color by default.

		imagecopymerge($titleAuthorImage, $imTitle['resource'], 0, 0, 0, 0, $imTitle['width'], $imTitle['height'], 100);
		imagecopymerge($titleAuthorImage, $imAuthor['resource'], 0, $imTitle['height'], 0, 0, $imAuthor['width'], $imAuthor['height'], 100);

		return $this->prepareReturnImageMetadata($titleAuthorImage, self::imageWidth, $newHeight);
	}

	public function maxAuthorLineLength($author)
	{
		$partsAuthor = $this->getAuthorParts($author);
		return $this->maxLineLength($partsAuthor);
	}

	public function maxTitleLineLength($title)
	{
		$partsTitle = $this->getTitleParts($title);
		return $this->maxLineLength($partsTitle);
	}

	private function maxLineLength($partsString)
	{

		$maxLength = 0;
		foreach($partsString as $line)
		{
			if (strlen($line) > $maxLength)
			{
				$maxLength = strlen($line);
			}
		}
		return $maxLength;
	}

	public function getTitleParts($title)
	{
		return $this->getStringParts($title, $this->titleConst['maxCharPerLine']);
	}

	public function getAuthorParts($author)
	{
		return $this->getStringParts($author, $this->authorConst['maxCharPerLine']);
	}


	private function prepareReturnImageMetadata($resource, $width, $height)
	{
		return array("resource"=>$resource, "width"=>$width, "height"=>$height);
	}

	private function removeEmptyLines($lines)
	{
		foreach ($lines as $key=>$val)
		{
			if (strlen(trim($val)) == 0)
			{
				unset($lines[$key]);
			}
		}
		return $lines;
	}

	private function getStringParts($text, $maxCharsPerLine)
	{
		$parts = explode(" ", $text);
		$wordsNumbers = count($parts);
		if ($wordsNumbers == 1)
		{
			return array($text);
		}

		if (strlen($text) <= $maxCharsPerLine)
		{
			return array($text);
		}

		$parts = $this->removeEmptyLines($parts);

		$preWord = NULL;
		$titleParts = array();
		$i = 0;
		foreach ($parts as $word)
		{
			if ($preWord === NULL)
			{
				if (strlen($word)>$maxCharsPerLine)
				{
					$titleParts[$i] = $word;
					$i++;
				}
				else
				{
					$preWord = $word;
				}
			}
			else
			{
				if (strlen($preWord." ".$word) > $maxCharsPerLine)
				{
					$titleParts[$i] = $preWord;
					$i++;
					if(strlen($word)>12)
					{
						$titleParts[$i] = $word;
						$i++;
						$preWord = NULL;
					}
					else
					{
						$preWord = $word;
					}
				}
				else
				{
					$preWord = $preWord." ".$word;
				}
			}
		}
		if($preWord !== NULL)
		{
			$titleParts[$i] = $preWord;
		}

		return $titleParts;

	}
} 