<?php
/**
 * Table Definition for resource
 */
require_once 'DB/DataObject.php';

class Resource extends DB_DataObject {
	###START_AUTOCODE
	/* the code below is auto generated do not remove the above tag */

	public $__table = 'resource';                        // table name
	public $id;                              // int(11)  not_null primary_key auto_increment
	public $record_id;                       // string(30)  not_null multiple_key
	public $shortId;                       // string(30)  not_null multiple_key
	public $title;                           // string(200)  not_null
	public $title_sort;                           // string(200)  sortable title
	public $author;
	public $isbn;
	public $upc;
	public $format;
	public $format_category;
	public $marc;
	public $marc_checksum;
	public $source = 'VuFind';               // string(50)  not_null

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('Resource',$k,$v); }

	/* the code above is auto generated do not remove the tag below */
	###END_AUTOCODE

	/**
	 * Get tags associated with the current resource.
	 *
	 * @access  public
	 * @param   int     $limit          Max. number of tags to return (0 = no limit)
	 * @return  array
	 */
	function getTags($limit = 10)
	{
		//Get a reference to the scope we are in.
		global $library;
		global $user;
			
		$tagList = array();

		$query = "SELECT MIN(tags.id) as id, tags.tag, COUNT(*) as cnt " .
                 "FROM tags inner join resource_tags on tags.id = resource_tags.tag_id " .
                 "WHERE resource_id = '{$this->id}' " .
                 "GROUP BY tags.tag " .
                 "ORDER BY cnt DESC, tags.tag LIMIT 0, $limit";
		$tag = new Tags();
		$tag->query($query);
		if ($tag->N) {
			//Load all bad words.
			require_once('Drivers/marmot_inc/BadWord.php');
			$badWords = new BadWord();
			$badWordsList = $badWords->getBadWordExpressions();
		
			while ($tag->fetch()) {
				//Determine if the current user added the tag
				$userAddedThis = false;
				if ($user){
					$rTag = new Resource_tags();
					$rTag->tag_id = $tag->id;
					$rTag->user_id = $user->id;
					$rTag->find();
					if ($rTag->N > 0){
						$userAddedThis = true;
					}
				}
				$tag->userAddedThis = $userAddedThis;
				//Filter the tags prior to display to censor bad words
				$okToAdd = true;
				if (!$userAddedThis){
					//The user will always see their own tags no matter how filthy.
					foreach ($badWordsList as $badWord){
						if (preg_match($badWord, trim($tag->tag))){
							$okToAdd = false;
							break;
						}
					}
				}
				if ($okToAdd){
					$tagList[] = clone($tag);
					// Return prematurely if we hit the tag limit:
					if ($limit > 0 && count($tagList) >= $limit) {
						return $tagList;
					}
				}
			}
		}

		return $tagList;
	}
	
/**
	 * Get tags associated with the current resource that the current user added.
	 *
	 * @access  public
	 * @param   int     $limit          Max. number of tags to return (0 = no limit)
	 * @return  array
	 */
	function getTagsForList($listId, $limit = 10)
	{
		//Get a reference to the scope we are in.
		global $library;
		global $user;
			
		$tagList = array();

		$query = "SELECT tags.id as id, tags.tag " .
                 "FROM tags inner join resource_tags on tags.id = resource_tags.tag_id " .
                 "WHERE resource_id = '{$this->id}' and list_id = '{$listId}'" .
                 "ORDER BY tags.tag LIMIT 0, $limit";
		$tag = new Tags();
		$tag->query($query);
		if ($tag->N) {
			//Load all bad words.
			require_once('Drivers/marmot_inc/BadWord.php');
			$badWords = new BadWord();
			$badWordsList = $badWords->getBadWordExpressions();
		
			while ($tag->fetch()) {
				//Determine if the current user added the tag
				$userAddedThis = false;
				if ($user){
					$rTag = new Resource_tags();
					$rTag->tag_id = $tag->id;
					$rTag->user_id = $user->id;
					$rTag->find();
					if ($rTag->N > 0){
						$userAddedThis = true;
					}
				}
				$tag->userAddedThis = $userAddedThis;
				//Filter the tags prior to display to censor bad words
				$okToAdd = true;
				if (!$userAddedThis){
					//The user will always see their own tags no matter how filthy.
					foreach ($badWordsList as $badWord){
						if (preg_match($badWord, trim($tag->tag))){
							$okToAdd = false;
							break;
						}
					}
				}
				if ($okToAdd){
					$tagList[] = clone($tag);
					// Return prematurely if we hit the tag limit:
					if ($limit > 0 && count($tagList) >= $limit) {
						return $tagList;
					}
				}
			}
		}

		return $tagList;
	}

	function addTag($tag, $user)
	{
		require_once 'services/MyResearch/lib/Tags.php';
		require_once 'services/MyResearch/lib/Resource_tags.php';

		$tags = new Tags();
		$tags->tag = $tag;
		if (!$tags->find(true)) {
			$tags->insert();
		}

		$rTag = new Resource_tags();
		$rTag->resource_id = $this->id;
		$rTag->tag_id = $tags->id;
		$rTag->user_id = $user->id;
		if (!$rTag->find()) {
			$rTag->insert();
		}

		return true;
	}

	function removeTag($tagId, $user, $removeFromAllResources = false)
	{
		require_once 'services/MyResearch/lib/Tags.php';
		require_once 'services/MyResearch/lib/Resource_tags.php';

		$rTag = new Resource_tags();
		if (!$removeFromAllResources){

			$rTag->resource_id = $this->id;
		}
		$rTag->tag_id = $tagId;
		$rTag->user_id = $user->id;
		$rTag->find();
		if ($rTag->N > 0){
			while ($rTag->fetch()) {
				$rTag->delete();
			}
		}else{
			//the tag was not found.
			return false;
		}

		//Check to see if the tag is still in use by any user for any resource.
		$rTag = new Resource_tags();
		$rTag->tag_id = $tagId;
		$rTag->find();
		if ($rTag->N == 0){
			//Tag is still in use, delete it.
			$tags = new Tags();
			$tags->id = $tagId;
			if ($tags->find(true)) {
				$tags->delete();
			}
		}

		return true;
	}

	function addComment($body, $user, $source = 'VuFind')
	{
		require_once 'services/MyResearch/lib/Comments.php';

		$comment = new Comments();
		$comment->user_id = $user->id;
		$comment->resource_id = $this->id;
		$comment->comment = $body;
		$comment->created = date('Y-m-d h:i:s');
		$comment->insert();

		return true;
	}

	function getComments($source = 'VuFind'){
		require_once 'services/MyResearch/lib/Comments.php';

		$sql = "SELECT comments.*, concat(user.firstname, ' ', user.lastname) as fullname, user.displayName as displayName " .
               "FROM comments RIGHT OUTER JOIN user on comments.user_id = user.id " .
               "WHERE comments.resource_id = '$this->id' ORDER BY comments.created";

		//Get a reference to the scope we are in so we can determine how to process the comments.
		global $library;
		global $user;
		//Load all bad words.
		require_once('Drivers/marmot_inc/BadWord.php');
		$badWords = new BadWord();
		$badWordsList = $badWords->getBadWordExpressions();

		$commentList = array();
		$commentList['user'] = array();
		$commentList['staff'] = array();

		$comment = new Comments();
		$comment->query($sql);
		if ($comment->N) {
			while ($comment->fetch()) {
				$okToAdd = true;
				if (isset($user) && $user != false && $user->id == $comment->user_id){
					//It's always ok to show the user what they wrote
				} else {
					//Determine if we should censor bad words or hide the comment completely.
					$censorWords = true;
					if (isset($library)) $censorWords = $library->hideCommentsWithBadWords == 0 ? true : false;
					if ($censorWords){
						$commentText = $comment->comment;
						foreach ($badWordsList as $badWord){
							$commentText = preg_replace($badWord, '***', $commentText);
						}
						$comment->comment = $commentText;
					}else{
						//Remove comments with bad words
						$commentText = trim($comment->comment);
						foreach ($badWordsList as $badWord){
							if (preg_match($badWord,$commentText)){
								$okToAdd = false;
								break;
							}
						}
					}
				}
				if ($okToAdd){
					//Remove any hashtags that were added to the review.
					if (preg_match('/#.*/', $comment->comment)){
						$comment->comment = preg_replace('/#.*/', '', $comment->comment);
						$commentList['staff'][] = clone($comment);
					}else{
						$commentList['user'][] = clone($comment);
					}
				}
			}
		}

		return $commentList;
	}

	function addRating($ratingValue, $user)
	{
		require_once 'Drivers/marmot_inc/UserRating.php';

		$rating = new UserRating();
		$rating->userid = $user->id;
		$rating->resourceid = $this->id;
		//Check to see if it already exists.
		$rating->find();
		if ($rating->N){
			$rating->fetch();
			$rating->rating = $ratingValue;
			$rating->update();
		}else{
			$rating->rating = $ratingValue;
			$rating->insert();
		}

		return true;
	}

	function getRatingData($user){
		global $configArray;
		global $memcache;
		$ratingData = $memcache->get("rating_{$this->record_id}");
		if (!$ratingData){
			require_once 'Drivers/marmot_inc/UserRating.php';
	
			//Set default rating data
			$ratingData = array(
	            'average' => 0,
	            'count'   => 0,
	            'user'    => 0,
	            'summary' => array(
	                'fiveStar'   => 0,
	                'fourStar'   => 0,
	                'threeStar'   => 0,
	                'twoStar'   => 0,
	                'oneStar'   => 0,
			),
			);
	
			//Get rating data for the resource
			$sql = "SELECT AVG(rating) average, count(rating) count, sum(1star) num1star, sum(2star) num2star, sum(3star) num3star, sum(4star) num4star, sum(5star) num5star FROM (SELECT rating, (rating = 1) as 1star, (rating = 2) as 2star, (rating = 3) as 3star, (rating = 4) as 4star, (rating = 5) as 5star from user_rating inner join resource on user_rating.resourceid = resource.id where resource.record_id =  '{$this->record_id}') ratingData";
			$rating = new UserRating();
			$rating->query($sql);
			if ($rating->N > 0){
				$rating->fetch();
				$ratingData['average'] = number_format($rating->average, 2);
				$ratingData['count'] = $rating->count;
				$ratingData['summary']['oneStar'] = $rating->num1star == null ? 0 :  $rating->num1star;
				$ratingData['summary']['twoStar'] = $rating->num2star == null ? 0 :  $rating->num2star;
				$ratingData['summary']['threeStar'] = $rating->num3star == null ? 0 :  $rating->num3star;
				$ratingData['summary']['fourStar'] = $rating->num4star == null ? 0 :  $rating->num4star;
				$ratingData['summary']['fiveStar'] = $rating->num5star == null ? 0 :  $rating->num5star;
			}
			//Get user rating
			if (isset($user) && $user != false){
				$rating = new UserRating();
				$rating->userid = $user->id;
				$rating->resourceid = $this->record_id;
				$rating->find();
				if ($rating->N){
					$rating->fetch();
					$ratingData['user'] = $rating->rating;
				}
			}
	
			//Create a graph of the individual ratings
			if ($configArray['Content']['ratingsGraph']){
				$ratingData['summaryGraph'] = $this->createRatingGraph($ratingData);
			}
			$memcache->set("rating_{$this->record_id}", $ratingData, 0, $configArray['Caching']['rating']);
		}
		return $ratingData;
	}

	function createRatingGraph($ratingData){
		global $configArray;
		// This array of values is just here for the example.

		$values = $ratingData['summary'];

		// Get the total number of columns we are going to plot
		$rows  = count($values);

		$imagewidth = 135;
		$imageheight = 60;

		// Get the height and width of the graph
		$width = 50;
		$height = 60;

		// Set the amount of space between each column
		$padding = 2;

		// Get the width of 1 column
		$row_height = $height / $rows ;

		// Generate the image variables
		$im        = imagecreate($imagewidth,$imageheight);
		$gray      = imagecolorallocate ($im,0xcc,0xcc,0xcc);
		$gray_lite = imagecolorallocate ($im,0xed,0xed,0xed);
		$gray_dark = imagecolorallocate ($im,0x8d,0x8d,0x8d);
		$white     = imagecolorallocate ($im,0xf9,0xf9,0xf9);
		$text      = imagecolorallocate ($im,0x32,0x32,0x32);

		// Fill in the background of the image
		imagefilledrectangle($im,0,0,$imagewidth,$imageheight,$white);

		$maxv = 0;

		// Calculate the maximum value we are going to plot
		foreach($values as $value)$maxv = max($value,$maxv);
		if ($maxv == 0){
			$maxv = 1;
		}

		// Now plot each column
		$i = 0;
		foreach($values as $value)
		{
			$row_width = ($width / 100) * (( $value / $maxv) *100);

			$y1 = $i*$row_height;
			$x1 = $row_width-$width+53;
			$y2 = (($i+1)*$row_height)-$padding;
			$x2 = $row_width+55;

			$y3 = $i*$row_height;
			$x3 = $row_width-$width+53;
			$y4 = (($i+1)*$row_height)-$padding;
			$x4 = $width+55;

			imagefilledrectangle($im,$x4,$y4,$x3,$y3,$gray_lite);
			imagefilledrectangle($im,$x2,$y2,$x1,$y1,$gray_dark);
			if (isset($valuses[$i])){
				imagestring($im,2,$x4+4,$y3-3,$values[$i],$text);
			}

			$i++;
		}

		// Send the PNG header information. Replace for JPEG or GIF or whatever
		$imagestring = file_get_contents("interface/themes/default/images/stars.png");
		$image2 = imagecreatefromstring($imagestring);

		imagecopy($im,$image2,0,0,0,0, imagesx($image2),imagesy($image2));

		if (!file_exists('images/fiveStar/')){
			mkdir('images/fiveStar/', true);
		}

		imagepng($im, "images/fiveStar/{$this->record_id}.png");
		imagedestroy($im);
		return "images/fiveStar/{$this->record_id}.png";
	}
}
