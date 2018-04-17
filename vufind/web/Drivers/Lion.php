<?php
/**
 *
 *
 * @category Pika
 * @author: Pascal Brammeier
 * Date: 2/2/2018
 *
 */


class Lion extends Sierra
{

	public function getSelfRegistrationFields()
	{
		$fields = array();
		$fields[] = array('property'=>'firstName', 'type'=>'text',  'label'=>'First Name',   'description'=>'Your first name', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property'=>'lastName',  'type'=>'text',  'label'=>'Last Name',    'description'=>'Your last name', 'maxLength' => 40, 'required' => true);
		$fields[] = array('property'=>'email',     'type'=>'email', 'label'=>'E-Mail',       'description'=>'E-Mail (for confirmation, notices and newsletters)', 'maxLength' => 128, 'required' => true);
		$fields[] = array('property'=>'phone',     'type'=>'text',  'label'=>'Phone Number', 'description'=>'Phone Number', 'maxLength' => 12, 'required' => true);
		$fields[] = array('property'=>'address',   'type'=>'text',  'label'=>'Address',      'description'=>'Address', 'maxLength' => 128, 'required' => true);
		$fields[] = array('property'=>'city',      'type'=>'text',  'label'=>'City',         'description'=>'City', 'maxLength' => 48, 'required' => true);
		$fields[] = array('property'=>'state',     'type'=>'text',  'label'=>'State',        'description'=>'State', 'maxLength' => 32, 'required' => true);
		//City State should be combined into one field when submitting registration
		$fields[] = array('property'=>'zip',       'type'=>'text',  'label'=>'Zip Code',     'description'=>'Zip Code', 'maxLength' => 5, 'required' => true);
		return $fields;
	}
}