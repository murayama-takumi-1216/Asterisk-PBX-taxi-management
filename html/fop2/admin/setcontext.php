<?php
require_once("config.php");
require_once("functions.php");
require_once("dblib.php");
require_once("asmanager.php");
require_once("system.php");
require_once("http.php");

require_once("dbconn.php");
include("dbsetup.php");

require_once("secure/secure-functions.php");
require_once("secure/secure.php");

$context = $_REQUEST['context'];
$fop2contexts = fop2_get_contexts();

if(isset($fop2contexts[$context])) {
    $_SESSION[MYAP]['AUTHVAR']["context"]=$fop2contexts[$context];
    $_SESSION[MYAP]['AUTHVAR']["contextid"]=$context;
}
