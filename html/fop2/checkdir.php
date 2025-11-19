<?php

require_once('config.php');
 
// Here you can fire your own popups or do whatever you want
// If the function returns ZERO it will disable regular notifications
// If you still want regular notifications, have the function return ONE


function custom_popup($ALLVAR) {

    // For Asternic Tag Call
    if($ALLVAR['poptype']=="ringing") {

        // RINGING NOTIFICATION

        $exten     = $ALLVAR['exten'];

        $clidname  = base64_decode($ALLVAR['clidname']);
        $clidnum   = base64_decode($ALLVAR['clidnum']);
        $fromqueue = base64_decode($ALLVAR['fromqueue']);

        // EXAMPLE TO DO A GOOGLE SEARCH ON CALLERID NAME
        // header("Content-type: text/javascript");
        // echo "window.open('http://www.google.com/search?q=$clidname');\n";

        // EXAMPLE TO DO A VTIGER SEARCH ON CALLERID NUMBER
        //header("Content-type: text/javascript");
        //echo "window.open('/vtigercrm/index.php?module=Home&action=UnifiedSearch&query_string=$clidnum')";

        /*
        // EXAMPLE TO UPDATE A FOP2 DIV VIA AJAX
        $par = "?";
        foreach($ALLVAR as $key=>$val) {
            $par.="$key=$val&";
        }
        header("Content-type: text/javascript");
        echo "\$('#asternicTag').load('popupasternic.php$par');\n";
        */

    } else {

        // AGENT CONNECT NOTIFICATION

        /*
        // EXAMPLE TO UPDATE A DIV VIA AJAX

        $par = "?";
        foreach($ALLVAR as $key=>$val) {
            $par.="$key=$val&";
        }

        header("Content-type: text/javascript");
        echo "\$('#asternicTag').load('popupasternic.php$par');\n";
        */
    }

    return 1;   // We still want regular notifications
}

// Execute custom function
if(function_exists("custom_popup")) {
    $ret = custom_popup($_GET);
    if(!$ret) {
        exit;
    }
}

// If there is no custom function, search in Phonebook Database
// to return Fancy data if we have a match

$context       = (isset($_SESSION[MYAP]['context']))?$_SESSION[MYAP]['context']:'';
$clidnum       = (isset($_GET['clidnum']))?$_GET['clidnum']:"";
$clidname      = (isset($_GET['clidname']))?$_GET['clidname']:"";
$fromqueue     = (isset($_GET['fromqueue']))?base64_decode($_GET['fromqueue']):"";
$exten         = (isset($_GET['exten']))?$_GET['exten']:"";
$plainresponse = (isset($_GET['response']))?$_GET['response']:"";

if($fromqueue<>'') {
    $picture = "./images/user-group.png";
} else {
    $picture = "./images/user.png";
}
$url         = (isset($_GET['url']))?$_GET['url']:"";

$decodedClidnum = base64_decode($clidnum);
$largo          = strlen($decodedClidnum);
$significant    = 8;
$startoffset    = 0;

if($largo > 2) { // prevent short numbers (extensions) from trigger a lookup

    if($largo > $significant) {
        $startoffset=$largo-$significant;
    }

    $clid_significant = substr( $decodedClidnum, $startoffset );

    $res = $db->consulta("SET NAMES utf8");
    $res = $db->consulta("SELECT visual_phonebook.id, concat(firstname,' ',lastname) AS name,company,picture FROM visual_phonebook LEFT JOIN visual_phonebook_phones ON id=contact_id WHERE ( REPLACE(REPLACE(REPLACE(REPLACE(number,'-',''),'(',''),')',''),' ','') LIKE '%%%s') AND context='%s' ORDER BY LENGTH(number) LIMIT 1",trim($clid_significant),$context);


    if($res) {
        if($db->num_rows()>0) {
            $row = $db->fetch_assoc($res);
            $clidname = $row['name'];
            $id = $row['id'];
            if($row['company'] <> '') {
                $clidname .= "<br/>".$row['company'];
            }
            $clidname = base64_encode($clidname);
            $picture = ($row['picture']<>'')?$row['picture']:'nopic';

            if($context<>"") {
                $picture=$context."_".$picture;
            }

            if(file_exists("./uploads/".$picture)) {
                $picture = "./uploads/".$picture;
            }  else {
                if($fromqueue<>'') {
                    $picture = "./images/user-group.png";
                } else {
                    $picture = "./images/user.png";
                }
            }
        }
    }
}

if($clidnum<>'undefined' && $clidname<>'undefined') {
    if($plainresponse=='') {
        $codequeue = base64_encode($fromqueue);
        Header( "X-JSON: { \"contactid\":\"$id\",\"clidnum\": \"$clidnum\", \"clidname\": \"$clidname\", \"picture\": \"$picture\", \"queue\": \"$codequeue\", \"url\": \"$url\" }");
    } else {
        echo $id;
    }
} 

