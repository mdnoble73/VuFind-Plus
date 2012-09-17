<?php

/**
 * Checks to see if the user agent is a bot.
 *
 * Copyright (C) Douglas County Libraries 2011.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @version 1.0
 * @author Mark Noble <mnoble@turningleaftech.com>
 * @copyright Copyright (C) Douglas County Libraries 2011.
 */
class BotChecker{

	static $isBot = null;
	/**
	 *
	 * Determines if the current request appears to be from a bot
	 */
	public static function isRequestFromBot(){
		if (BotChecker::$isBot == null){
			global $servername;
			if (file_exists('../../sites/' . $servername . '/conf/bots.ini')){
				$fhnd = fopen('../../sites/' . $servername . '/conf/bots.ini', 'r');
			}elseif (file_exists('../../sites/default/conf/bots.ini')){
				$fhnd = fopen('../../sites/default/conf/bots.ini', 'r');
			}else{
				return false;
			}

			$isBot = false;
			$userAgent = $_SERVER['HTTP_USER_AGENT'];
			while (($curAgent = fgets($fhnd, 4096)) !== false) {
				//Remove line separators
				$curAgent = str_replace("\r", '', $curAgent);
				$curAgent = str_replace("\n", '', $curAgent);
				if (strcasecmp($userAgent, $curAgent) == 0 ){
					$isBot = true;
					break;
				}
			}
			fclose($fhnd);

			BotChecker::$isBot = $isBot;
		}
		return BotChecker::$isBot;
	}
}