<?php
interface Authentication {
	public function __construct($additionalInfo);

	/**
	 * Authenticate the user in the system
	 *
	 * @param array $additionalInfo
	 * @return mixed
	 */
	public function authenticate();
}