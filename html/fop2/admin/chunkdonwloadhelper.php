<?php
require_once('config.php');
require_once("dblib.php");
require_once('functions.php');
require_once('system.php');
require_once("asmanager.php");
require_once('http.php');
require_once("dbconn.php");
include("dbsetup.php");
require_once("secure/secure-functions.php");
require_once("secure/secure.php");

$file       = $_REQUEST['file'];
$mirror     = urldecode($_REQUEST['mirror']);
if(!is_valid_mirror($mirror)) {
    die();
}

$directory  = dirname(__FILE__)."/_cache/";
if(!is_dir($directory)) { 
    if (! @mkdir($directory) ) {
        die("Could not create $directory");
    }
}

$isStart    = isset($_REQUEST['isStart'])?$_REQUEST['isStart']:0;
$completed  = isset($_REQUEST['completed'])?$_REQUEST['completed']:0;
$outputFile = $directory.$file.".tgz";

if($isStart==0) {
    // Si es una llamada inicial/comienzo, borro descargar parcial y tamaño
    if(is_readable($directory."options.txt")) { unlink($directory."options.txt"); }
    if(is_readable("$outputFile")) { unlink($outputFile); }
}

$return = uHttp::downloadChunk($mirror."/plugins/".$file.".tgz", $outputFile, $isStart == 1);
echo $return;
