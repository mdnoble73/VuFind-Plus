<?php
/**
 * Table Definition for user_list
 */
require_once 'DB/DataObject.php';

class User_list extends DB_DataObject
{
	###START_AUTOCODE
	/* the code below is auto generated do not remove the above tag */

	public $__table = 'user_list';                       // table name
	public $id;                              // int(11)  not_null primary_key auto_increment
	public $user_id;                         // int(11)  not_null multiple_key
	public $title;                           // string(200)  not_null
	public $description;                     // string(500)
	public $created;                         // datetime(19)  not_null binary
	public $public;                          // int(11)  not_null

	/* Static get */
	function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('User_list',$k,$v); }

	/* the code above is auto generated do not remove the tag below */
	###END_AUTOCODE

	function getResources($tags = null)
	{
		$resourceList = array();

		$sql = "SELECT DISTINCT resource.*, user_resource.saved FROM resource, user_resource " .
               "WHERE resource.id = user_resource.resource_id " .
               "AND user_resource.user_id = '$this->user_id' " .
               "AND user_resource.list_id = '$this->id'";

		if ($tags) {
			for ($i=0; $i<count($tags); $i++) {
				$sql .= " AND resource.id IN (SELECT DISTINCT resource_tags.resource_id " .
                    "FROM resource_tags, tags " .
                    "WHERE resource_tags.tag_id=tags.id AND tags.tag = '" . 
				addslashes($tags[$i]) . "' AND resource_tags.user_id = '$this->user_id' " .
                    "AND resource_tags.list_id = '$this->id')";
			}
		}

		$resource = new Resource();
		$resource->query($sql);
		if ($resource->N) {
			while ($resource->fetch()) {
				$cleanedResource = $this->cleanResource(clone($resource));
				if ($cleanedResource != false){
					$resourceList[] = $cleanedResource;
				}
			}
		}

		return $resourceList;
	}

	var $catalog;
	function cleanResource($resource){
		global $configArray;
		global $interface;
		global $user;

		// Connect to Database
		$this->catalog = new CatalogConnection($configArray['Catalog']['driver']);

		//Filter list information for bad words as needed.
		if ($user == false || $this->user_id != $user->id){
			//Load all bad words.
			global $library;
			require_once('Drivers/marmot_inc/BadWord.php');
			$badWords = new BadWord();
			$badWordsList = $badWords->getBadWordExpressions();

			//Determine if we should censor bad words or hide the comment completely.
			$censorWords = true;
			if (isset($library)) $censorWords = $library->hideCommentsWithBadWords == 0 ? true : false;
			if ($censorWords){
				//Filter Title
				$titleText = $resource->title;
				foreach ($badWordsList as $badWord){
					$titleText = preg_replace($badWord, '***', $titleText);
				}
				$resource->title = $titleText;
				//Filter description
				$descriptionText = $this->description;
				foreach ($badWordsList as $badWord){
					$descriptionText = preg_replace($badWord, '***', $descriptionText);
				}
				$this->description = $descriptionText;
			}else{
				//Check for bad words in the title or description
				$titleText = $resource->title . ' ' . $resource->description;
				foreach ($badWordsList as $badWord){
					if (preg_match($badWord,$titleText)){
						return false;
						//PEAR::raiseError(new PEAR_Error('You do not have permission to view this list'));
						//break;
					}
				}
			}
		}
		return $resource;
	}

	function getTags()
	{
		$tagList = array();

		$sql = "SELECT resource_tags.* FROM resource, resource_tags, user_resource " .
               "WHERE resource.id = user_resource.resource_id " .
               "AND resource.id = resource_tags.resource_id " .
               "AND user_resource.user_id = '$this->user_id' " .
               "AND user_resource.list_id = '$this->id'";
		$resource = new Resource();
		$resource->query($sql);
		if ($resource->N) {
			while ($resource->fetch()) {
				$tagList[] = clone($resource);
			}
		}

		return $tagList;
	}

	/**
	 * @todo: delete any unused tags
	 */
	function removeResource($resource)
	{
		// Remove the Saved Resource
		require_once 'services/MyResearch/lib/User_list.php';
		require_once 'services/MyResearch/lib/Resource.php';
		$join = new User_resource();
		$join->user_id = $this->user_id;
		$join->resource_id = $resource->id;
		$join->list_id = $this->id;
		$join->delete();

		// Remove the Tags from the resource
		$join = new Resource_tags();
		$join->user_id = $this->user_id;
		$join->resource_id = $resource->id;
		$join->list_id = $this->id;
		$join->delete();
	}

	/**
	 * remove all resources within this list
	 */
	function removeAllResources($tags = null){
		$allResources = $this->getResources($tags);
		foreach ($allResources as $resource){
			$this->removeResource($tags);
		}
	}

}