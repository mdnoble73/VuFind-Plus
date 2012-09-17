<?php

require_once 'SessionInterface.php';
require_once 'services/MyResearch/lib/Session.php';

class MySQLSession extends SessionInterface {
	static public function read($sess_id) {
		$s = new Session();
		$s->session_id = $sess_id;

		if ($s->find(true)) {
			// enforce lifetime of this session data
			if ($s->remember_me == 0 && $s->last_used + self::$lifetime > time()) {
				$s->last_used = time();
				$s->update();
				return $s->data;
			} else if ($s->remember_me == 1 && $s->last_used + self::$rememberMeLifetime > time()) {
				$s->last_used = time();
				$s->update();
				return $s->data;
			} else {
				$s->delete();
				return '';
			}
		} else {
			// in seconds - easier for calcuating duration
			$s->last_used = time();
			// in date format - easier to read
			$s->created = date('Y-m-d h:i:s');
			if (isset($_SESSION['rememberMe']) && $_SESSION['rememberMe'] == true){
				$s->remember_me = 1;
			}
			$s->insert();
			return '';
		}
	}

	static public function write($sess_id, $data) {
		$s = new Session();
		$s->session_id = $sess_id;
		if ($s->find(true)) {
			$s->data = $data;
			if (isset($_SESSION['rememberMe']) && $_SESSION['rememberMe'] == true){
				$s->remember_me = 1;
			}
			return $s->update();
		} else {
			return false;
		}
	}

	static public function destroy($sess_id) {
		// Perform standard actions required by all session methods:
		parent::destroy($sess_id);

		// Now do database-specific destruction:
		$s = new Session();
		$s->session_id = $sess_id;
		return $s->delete();
	}

	static public function gc($sess_maxlifetime) {
		$s = new Session();
		$s->whereAdd('last_used + ' . $sess_maxlifetime . ' < ' . time());
		$s->delete(true);
	}

}