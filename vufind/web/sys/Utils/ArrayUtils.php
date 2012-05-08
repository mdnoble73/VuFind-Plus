<?php

class ArrayUtils
{
	
	/**
	 * Return the last key of the given array
	 * http://stackoverflow.com/questions/2348205/how-to-get-last-key-in-an-array
	 */
	static public function getLastKey($array)
	{
		end($array);
		return key($array);
	}
	
}

?>