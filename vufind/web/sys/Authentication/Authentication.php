<?php
interface Authentication {
	public function __construct($additionalInfo);

	/**
	 * Authenticate the user in the system
	 *
	 * @return mixed
	 */
	public function authenticate();
}