/**
 * Created by mark on 1/14/14.
 *
 * Main Javascript file for vufind
 */

var Globals = Globals || {};
Globals.path = '/';
Globals.url = '/';
Globals.loggedIn = false;
Globals.automaticTimeoutLength = 0;
Globals.automaticTimeoutLengthLoggedOut = 0;

require(["vufind/base"], function (){});
require("vufind/account", ["vufind/base"], function (){});