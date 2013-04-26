<?php
require_once 'bootstrap.php';

// Initiate Session State
$session_type = $configArray['Session']['type'];
$session_lifetime = $configArray['Session']['lifetime'];
$session_rememberMeLifetime = $configArray['Session']['rememberMeLifetime'];
register_shutdown_function('session_write_close');
if (isset($configArray['Site']['cookie_domain'])){
	session_set_cookie_params(0, '/', $configArray['Site']['cookie_domain']);
}
require_once ROOT_DIR . '/sys/' . $session_type . '.php';
if (class_exists($session_type)) {
	$session = new $session_type();
	$session->init($session_lifetime, $session_rememberMeLifetime);
}

$session::gc(3600);