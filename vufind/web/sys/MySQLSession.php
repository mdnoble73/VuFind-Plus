<?php

require_once 'SessionInterface.php';
require_once ROOT_DIR . '/services/MyResearch/lib/Session.php';

class MySQLSession extends SessionInterface {
	static public function read($sess_id) {
		$s = new Session();
		$s->session_id = $sess_id;

		$cookieData = '';
		if ($s->find(true)) {
			//First check to see if the session expired
			$curTime = time();
			if ($s->remember_me){
				$sessionExpirationTime = $s->last_used + self::$rememberMeLifetime;
			}else{
				$sessionExpirationTime = $s->last_used + self::$lifetime;
			}
			if ($curTime > $sessionExpirationTime){
				self::destroy($sess_id);
			}else{
				// updated the session in the database to show that we just used it
				$s->last_used = $curTime;
				$s->update();
				//And increase the cookie lifetime
				if (isset($_REQUEST['rememberMe']) && $_REQUEST['rememberMe'] == true || $s->remember_me == 1){
					$sessionNewExpirationTime = $s->last_used + self::$rememberMeLifetime;
				}else{
					$sessionNewExpirationTime = $s->last_used + self::$lifetime;
				}
				$cookieData = $s->data;
			}
		} else {
			//There is no active session, we need to create a new one.
			$s->last_used = time();
			// in date format - easier to read
			$s->created = date('Y-m-d h:i:s');
			if (isset($_SESSION['rememberMe']) && $_SESSION['rememberMe'] == true){
				$s->remember_me = 1;
				$sessionNewExpirationTime = $s->last_used + self::$rememberMeLifetime;
			}else{
				$sessionNewExpirationTime = $s->last_used + self::$lifetime;
			}
			$s->insert();
		}
		/*if ($sessionNewExpirationTime){
			setcookie('VFP_SESSION', $sess_id, $sessionNewExpirationTime, '/');
		}*/
		return $cookieData;
	}

	static public function write($sess_id, $data) {
		$s = new Session();
		$s->session_id = $sess_id;
		if ($s->find(true)) {
			$s->data = $data;
			if (isset($_SESSION['rememberMe']) && $_SESSION['rememberMe'] == true){
				$s->remember_me = 1;
			}
			parent::write($sess_id, $data);
			return $s->update();
		} else {
			//No session active
			return false;
		}
	}

	static public function destroy($sess_id) {
		// Perform standard actions required by all session methods:
		parent::destroy($sess_id);

		//Remove our custom cookie
		//setcookie('VFP_SESSION', $sess_id, -1, '/');

		// Now do database-specific destruction:
		$s = new Session();
		$s->session_id = $sess_id;
		return $s->delete();
	}

	static public function gc($sess_maxlifetime) {
		//Doing this in PHP  at random times, causes problems for VuFind, do it as part of cron in Java
		/*$s = new Session();
		$s->whereAdd('last_used + ' . $sess_maxlifetime . ' < ' . time());
		$s->whereAdd('remember_me = 0');
		$s->delete(true);

		$s = new Session();
		$s->whereAdd('last_used + ' . SessionInterface::$rememberMeLifetime . ' < ' . time());
		$s->whereAdd('remember_me = 1');
		$s->delete(true);*/
	}

}