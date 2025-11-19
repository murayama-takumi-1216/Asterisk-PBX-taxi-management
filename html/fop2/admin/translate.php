<?php
require_once("config.php");
require_once("functions.php");
require_once("system.php");
//require_once("dblib.php");

$locale = array_shift($_REQUEST);
$string = array_shift($_REQUEST);
$pars = array();
foreach($_REQUEST as $key=>$val) {
   $pars[] = $val;
}

T_setlocale(LC_MESSAGES, $locale);
$domain = 'fop2manager';
_bindtextdomain($domain, LOCALE_DIR);
// bind_textdomain_codeset is supported only in PHP 4.2.0+
_bind_textdomain_codeset($domain, $encoding); 
_textdomain($domain);

echo vsprintf(__($string),$pars);

