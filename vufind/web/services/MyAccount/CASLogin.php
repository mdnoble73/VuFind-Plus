<?php
/**
 *
 * Copyright (C) Marmot Library Network 2016.
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
 */

require_once ROOT_DIR . "/Action.php";

class MyAccount_CASLogin extends Action
{
	function __construct()
	{
	}

	function launch($msg = null)
	{
		global $configArray;
		global $interface;
		global $library;
		require_once ROOT_DIR . '/CAS-1.3.4/CAS.php';

		if ($configArray['System']['debug']){
			phpCAS::setDebug();
			phpCAS::setVerbose(true);
		}

		phpCAS::client(CAS_VERSION_3_0, $library->casHost, (int)$library->casPort, $library->casContext);

		// For production use set the CA certificate that is the issuer of the cert
		// on the CAS server and uncomment the line below
		// phpCAS::setCasServerCACert($cas_server_ca_cert_path);
		// For quick testing you can disable SSL validation of the CAS server.
		// THIS SETTING IS NOT RECOMMENDED FOR PRODUCTION.
		// VALIDATING THE CAS SERVER IS CRUCIAL TO THE SECURITY OF THE CAS PROTOCOL!
		phpCAS::setNoCasServerValidation();

		// force CAS authentication
		phpCAS::forceAuthentication();

		echo
"<html>
  <head>
    <title>phpCAS simple client</title>
  </head>
  <body>
    <h1>Successful Authentication!</h1>
    <?php require 'script_info.php' ?>
		<p>the user's login is <b>" . phpCAS::getUser() . "</b>.</p>
		<p>phpCAS version is <b>" . phpCAS::getVersion() . "</b>.</p>
		<p><a href='?logout='>Logout</a></p>
	</body>
</html>";

	}
}

