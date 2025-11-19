<?php
require_once("config.php");
require_once("functions.php");
require_once("dblib.php");
require_once("asmanager.php");
require_once("system.php");
require_once("http.php");

$fop2_mirror   = get_fastest_mirror();

// Check online version
$fop2managerxml = uHttp::sendHttpRequest("http://".$fop2_mirror."/plugins/fop2manager.xml", 5);
$versiononline  = value_in('version',$fop2managerxml);
$changelog      = value_in('changelog',$fop2managerxml);
$rawname        = value_in('rawname',$fop2managerxml);

if($versiononline=='') {
    $versiononline = $version;
    $rawname       = "fop2manager";
    $changelog     = "";
    $adminfile     = $rawname."-".$versiononline;
} else {
    $changelog     = preg_replace("/\n/","<br/>",$changelog);
    $adminfile     = $rawname."-".$versiononline;
}

$versioncompare='';
$version_partes = preg_split("/\./",$version);
foreach($version_partes as $part) {
    $versioncompare.=sprintf("%02d",$part);
}

$versiononlinecompare='';
$versiononline_partes = preg_split("/\./",$versiononline);
foreach($versiononline_partes as $part) {
    $versiononlinecompare.=sprintf("%02d",$part);
}

//echo "version compare $versioncompare, version online $versiononlinecompare<br>";

$newversion = sprintf(__('New %s version %s available for download'),$APPNAME,$versiononline);
$curversion = sprintf(__('(Your current version is %s)'),$version);

header('Content-Type: application/javascript; charset=utf8');

if($versiononlinecompare > $versioncompare) {
    echo '$("#upgradeavailable").show();'."\n";
    echo '$("#newversion").html("'.$newversion."\");\n";
    echo '$("#curversion").html("'.$curversion."\");\n";
    echo '$("#changelog").html("'.$changelog."\");\n";
    echo "frameSrc='downloadfile.php?file=$adminfile';\n";

}
