<?php
require_once("config.php");
require_once("functions.php");
require_once("dblib.php");
require_once("asmanager.php");
require_once("system.php");
require_once("http.php");

header('Content-Type: application/javascript; charset=utf8');

list ($fop2version,$fop2registername,$fop2licensetype) = fop2_get_version();
$_SESSION[MYAP]['fop2version']=$fop2version;

$licensedplugins=explode(',',plugin_get_licensed());

if($licensedplugins[0] <> '0' ) {

    // Panel Server is running, list licensed plugins if any
    $fop2_mirror   = get_fastest_mirror(); 
    $plugin_online = plugin_get_online($fop2_mirror);

    if (isset($plugin_online)) {
        foreach ($plugin_online as $idx=>$arrdata) {
            $plugin_name[$arrdata['rawname']]=$arrdata['name'];
        }
    }
    $plugin_name['phoneprou']="WebRTC Browser Phone PRO Unlimited";
    $plugin_name['phoneprow']="WebRTC Browser Phone PRO without Chat";

    if($licensedplugins[0]<>'') {
        $allplugins = array();
        foreach($licensedplugins as $rawname) {
            $nameprint = isset($plugin_name[$rawname])?$plugin_name[$rawname]:$rawname;
            $allplugins[]=$nameprint;
        }
        $nameprint = implode(", ",$allplugins);
    } else {
        $nameprint = "<div class='alert alert-warning'>";
        $nameprint.= __("There are no licensed plugins");
        $nameprint.= "</div>";
    }
    echo '$("#plugbox").show();'."\n";
    echo '$("#licensedplugins").html("'.$nameprint."\");\n";

}

// This is to check if Panel Server is running
if($licensedplugins[0] == '0') {
    $sstatus = "<div class='alert alert-danger'>";
    $sstatus.= __("Panel Server is not responding. Be sure the service is running!");
    $sstatus.= "</div>";
    echo '$("#plugbox").hide();'."\n";
} else {
    $sstatus = "<div class='alert alert-success'>";
    $sstatus.= "<strong>".__("Panel Server Status: OK");
    if($fop2version<>'') {
        if($_SESSION['FOP2Manager']['AUTHVAR']['level']=='admin') {
            $sstatus .= " - ".__("Version").": $fop2version";
        }
    }
    $sstatus.= "</strong>";
    $sstatus.= "</div>";
}
echo '$("#fop2serverstatus").html("'.$sstatus."\");\n";

