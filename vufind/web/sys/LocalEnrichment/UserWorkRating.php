<?php
/**
 * Table Definition for User Ratings
 */
require_once 'DB/DataObject.php';
require_once 'DB/DataObject/Cast.php';

class UserWorkRating extends DB_DataObject
{
  public $__table = 'user_work_rating';    // table name
  public $id;                       //int(11)
	public $groupedRecordPermanentId; //varchar(36)
  public $userid;                   //int(11)
  public $rating;                   //int(5)
	public $dateRated;
}