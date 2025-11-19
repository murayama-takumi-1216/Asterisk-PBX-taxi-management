<?php
// Database connection details
//
// Only needed if you do not use FreePBX or want to specify
// a different database than the freepbx one.

$DBHOST = 'localhost';
$DBNAME = '';
$DBUSER = '';
$DBPASS = '';

$FORCE_UTF8 = false;
$CDRDBTABLE = "asteriskcdrdb.cdr";

// This is the preference sqlite database for FOP2 User and Context Preferences
$SQLITEDB="/usr/local/fop2/fop2settings.db";

// ---------------------------------------------------------
// Do not modify below this line
// ---------------------------------------------------------

// Parse special config files for Ombutel
foreach(glob('/etc/fop2/config-web-*.conf') as $conf_file) {
       include_once($conf_file);
}

$phpver = substr(phpversion(),0,1);
$DBSETUP="fop2setup";

define('DEBUG',false);
define('SESSIONDEBUG',false);

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors',     0);
ini_set("session.cookie_lifetime", "0");    // conservar session hasta que se cierre el navegador
ini_set("session.gc_maxlifetime", 60*60*9); // duracion maxima de la session

// Site specific
define("MYAP",  "FOP2");
define("TITLE", "Flash Operator Panel 2");
if(isset($_SERVER['PATH_INFO'])) {
    define("SELF",  substr($_SERVER['PHP_SELF'], 0, (strlen($_SERVER['PHP_SELF']) - @strlen($_SERVER['PATH_INFO']))));
} else {
    define("SELF",  $_SERVER['PHP_SELF']);
}

// Parsing time calculation
$time      = explode(' ', microtime());
$time      = $time[1] + $time[0];
$begintime = $time;

// Session start
session_start();
//session_register(MYAP);

// General classes inclussion
require_once("lib/func.php");

if($phpver>4) {
    require_once("lib/dblib.php");
    require_once("lib/dbgrid.php");
} else {
    require_once("lib/dblib4.php");
    require_once("lib/dbgrid4.php");
}

set_error_handler("funcErrorHandler",E_ALL);

if(function_exists('mysqli_connect')) {

    $try_connect=1;
    if($DBUSER!='' && $DBPASS!='') {
        $db = new dbcon($DBHOST, $DBUSER, $DBPASS, $DBNAME, false, true, $FORCE_UTF8);
        if($db->is_connected()) {
            $try_connect=0;
        }
    }
    if($try_connect==1) {
        // Database connection details from amportal 
        if (is_readable("/etc/amportal.conf")) {
            $amp_conf = parse_amportal_conf("/etc/amportal.conf");
            $DBHOST = $amp_conf['AMPDBHOST'];
            $DBNAME = $amp_conf['AMPDBNAME'];
            $DBUSER = $amp_conf['AMPDBUSER'];
            $DBPASS = $amp_conf['AMPDBPASS'];

            if(!isset($amp_conf['AMPDBENGINE'])) {
                if(is_file("/etc/freepbx/freepbx.conf")) {
                    $amp_conf = parse_amportal_conf("/etc/freepbx/freepbx.conf");
                } else if(is_file("/etc/freepbx.conf")) {
                    $amp_conf = parse_amportal_conf("/etc/freepbx.conf");
                }
                $DBHOST = $amp_conf['AMPDBHOST'];
                $DBNAME = $amp_conf['AMPDBNAME'];
                $DBUSER = $amp_conf['AMPDBUSER'];
                $DBPASS = $amp_conf['AMPDBPASS'];
            }

        } else if (is_readable("/etc/freepbx/freepbx.conf")) {
            $amp_conf = parse_amportal_conf("/etc/freepbx/freepbx.conf");
            $DBHOST = $amp_conf['AMPDBHOST'];
            $DBNAME = $amp_conf['AMPDBNAME'];
            $DBUSER = $amp_conf['AMPDBUSER'];
            $DBPASS = $amp_conf['AMPDBPASS'];
        } else if (is_readable("/etc/freepbx.conf")) {
            $amp_conf = parse_amportal_conf("/etc/freepbx.conf");
            $DBHOST = $amp_conf['AMPDBHOST'];
            $DBNAME = $amp_conf['AMPDBNAME'];
            $DBUSER = $amp_conf['AMPDBUSER'];
            $DBPASS = $amp_conf['AMPDBPASS'];
        } else if (is_readable("/etc/asterisk/snep/snep-features.conf")) {
            $DBHOST = 'localhost';
            $DBNAME = 'snep';
            $DBUSER = 'snep';
            $DBPASS = 'sneppass';
            $CDRDBTABLE = "snep.cdr";
        } 
        $db = new dbcon($DBHOST, $DBUSER, $DBPASS, $DBNAME, true, true, $FORCE_UTF8);
    }

    set_config($DBSETUP);

}

$traduccionesQueFaltan = Array();

$language="en";
if(isset($_SESSION[MYAP]['language'])) {
   $language = $_SESSION[MYAP]['language'];
   $langfile = realpath(dirname(__FILE__))."/lang/$language.php";
   if(!is_file($langfile)) {
       $language="en";
   } 
}

require_once("lang/$language.php");

function parse_amportal_conf($filename) {

    $file = file($filename);
    if (is_array($file)) {
        foreach ($file as $line) {
            if (preg_match("/^\s*([^=]*)\s*=\s*[\"']?([\w\/\:\.\,\}\{\>\<\(\)\*\?\%!=\+\#@&\\$-]*)[\"']?\s*([;].*)?/",$line,$matches)) {
                if(preg_match('/\$amp_conf/',$matches[1])) {
                    $matches[1] = preg_replace('/\$amp_conf\[\'/','',$matches[1]);
                    $matches[1] = preg_replace('/\$amp_conf\["/','',$matches[1]);
                    $matches[1] = trim($matches[1]);
                    $matches[1] = substr($matches[1],0,-2);
                }
                $matches[1] = trim($matches[1]);
                $conf[ $matches[1] ] = trim($matches[2]);
            }
        }
    } else {
        die("<h1>".sprintf("Missing or unreadable config file (%s)...cannot continue", $filename)."</h1>");
    }
    return $conf;
}

?>
