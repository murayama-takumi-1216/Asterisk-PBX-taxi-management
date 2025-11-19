<?php
require_once("config.php");

// MiRTA PBX uses a different CDR schema where the date field
// is named start. If you use MiRTA uncomment the following line:
//
// $datefield = 'start';

$datefield      = 'calldate';
$recordingfield = 'recordingfile';


// *************************************************************

$context   = $_SESSION[MYAP]['context'];
$extension = $_SESSION[MYAP]['extension'];

$permit    = $_SESSION[MYAP]['permit'];
$admin     = isset($_SESSION[MYAP]['admin'])?$_SESSION[MYAP]['admin']:0;
$permisos  = preg_split("/,/",$permit);

if(in_array("callhistory",$permisos) || in_array("all",$permisos)) {
    $allowed='yes';
} else {
    $allowed='no';
}

if(in_array("fullcallhistory",$permisos) || in_array("all",$permisos)) {
    $cdrfull='yes';
} else {
    $cdrfull='no';
}

if(!in_array("recordings",$permisos) && !in_array("all",$permisos)) {
    // if no recordings or all permissions, set recordingfield empty 
    $recordingfield = "''";
}

$transinbound = 'inbound';
$transoutbound = 'outbound';

// If GET with accept json, return json restful responses, that is
// call history only for the calling extension (never cdrfull for admins)
// including page and limit request parameters for pagination
// and joining with visual phonebook to retrieve contact name, company, tag/color
//
if($_SERVER['HTTP_ACCEPT']=='application/json') {
    header('Content-Type: application/json; charset=utf-8');
    if($allowed=='yes') {
        $transinbound = 'inbound';
        $transoutbound = 'outbound';

        // Get the pagination parameters from the query string
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

        // Calculate the offset based on the page number and limit
        $offset = ($page - 1) * $limit;

        $db->consulta("SET NAMES utf8");
        $query = "SELECT concat(firstname,' ',lastname) as fullname,company,group_concat(tag) as tags, picture, 
            IF(dst='".$extension."' OR dstchannel LIKE 'SIP/$extension-________' OR dstchannel LIKE 'PJSIP/$extension-________','$transinbound','$transoutbound') as direction,
            $datefield AS calldate,
            IF(dst='".$extension."' OR dstchannel LIKE 'SIP/$extension-________' OR dstchannel LIKE 'PJSIP/$extension-________',src,dst) as number,
            IF(duration>billsec,duration,billsec) as duration,
            disposition,
            uniqueid,
            clid,
            $recordingfield AS recording 
                FROM $CDRDBTABLE 
                LEFT JOIN visual_phonebook_phones on visual_phonebook_phones.number = IF(dst='".$extension."' OR dstchannel LIKE 'SIP/$extension-________' OR dstchannel LIKE 'PJSIP/$extension-________',src,dst)  
                LEFT JOIN visual_phonebook ON visual_phonebook_phones.contact_id=visual_phonebook.id  
                LEFT JOIN visual_phonebook_tags_contacts ON visual_phonebook_tags_contacts.contact_id=visual_phonebook.id 
                LEFT JOIN visual_phonebook_tags ON visual_phonebook_tags.id=visual_phonebook_tags_contacts.tag_id 
                WHERE dstchannel<>'' AND dstchannel NOT LIKE 'Local%' 
                GROUP BY uniqueid 
                HAVING direction='inbound' OR direction='outbound'  
                ORDER BY calldate DESC  
                LIMIT $offset, $limit";

        $res = $db->consulta($query);

        if(!$res) {
            header("HTTP/1.1 500 Internal Server Error");
            $resp = array("success"=>false,"status"=>500,"details"=>"Problem accessing DB");
            echo json_encode($resp);
            exit;
        }

        $data = array();

        while($row = $db->fetch_assoc($res)) {
            $data[] = $row;
        }

        // Get the total count of records
        $countQuery = "SELECT
            IF(dst='".$extension."' OR dstchannel LIKE 'SIP/$extension-________' OR dstchannel LIKE 'PJSIP/$extension-________','$transinbound','$transoutbound') as direction 
            FROM $CDRDBTABLE 
            WHERE dstchannel<>'' AND dstchannel NOT LIKE 'Local%' 
            HAVING direction='inbound' OR direction='outbound'";

        $countRes = $db->consulta($countQuery);
        $totalCount = $db->num_rows($countRes);

        // Create the response array
        $response = array(
                'status' => 'success',
                'data' => $data,
                'page' => $page,
                'limit' => $limit,
                'total' => $totalCount
                );

        // Set the appropriate headers for JSON response
        header('Content-Type: application/json; charset=utf-8');
        // Output the JSON response
        $ret = json_encode($response);
        if($ret===false) {
            header("HTTP/1.1 500 Internal Server Error");
            $resp = array("success"=>false,"status"=>500,"details"=>json_last_error_msg());
            echo json_encode($resp);
            exit;
        } else {
            // Correct Output
            echo $ret;
            exit;
        }
    } else  {
        header("HTTP/1.1 401 Unauthorized");
        $resp = array("success"=>false,"status"=>401,"details"=>json_last_error_msg());
        echo json_encode($resp);
        exit;
    }
} else {
    header("Content-Type: text/html; charset=utf-8");
}
?>
<!DOCTYPE html>
<html>
<head>
<?php
if(isset($page_title)) { 
    echo "    <title>$page_title</title>\n"; 
} else {
    echo "    <title>".TITLE."</title>\n"; 
}

if($allowed=="no") {
    echo "<meta http-equiv=\"refresh\" content=\"5\" >\n";
}

?>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <meta name="MSSmartTagsPreventParsing" content="true">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <link rel="stylesheet" type="text/css" href="css/jconf.css">
    <link rel="stylesheet" type="text/css" href="css/chosen.css">
    <link rel="stylesheet" type="text/css" href="css/vmail.css">
    <link rel="stylesheet" type="text/css" href="css/jquery.noty.css">
    <link rel="stylesheet" type="text/css" media="screen" href="css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/bootstrap.datepicker.css">
    <link rel="stylesheet" type="text/css" href="css/dbgrid.css">
    <link rel="stylesheet" type="text/css" href="css/flags.css">
    <link rel="stylesheet" type="text/css" href="css/animate.css">
    <link rel="stylesheet" type="text/css" href="css/operator.css">
    <link rel="stylesheet" type="text/css" media="screen" href="css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" media="screen" href="css/bootstrap-select.css">
    <style>.btn{padding:5px 7px;}</style>

    <script src="js/date.format.js"></script>
    <script src="js/jquery-1.11.3.min.js"></script>
    <script src="js/moment-with-locales.js"></script>
    <script src="js/jquery.plugin.js"></script>
    <script src="js/jquery.noty.js"></script>
    <script src="js/chosen.jquery.js"></script>
    <script src="js/jquery.jconf.js"></script>
    <script src="js/jquery.timeago.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/bootstrap-switch.min.js"></script>
    <script src="js/bootstrap-dropdown-on-hover.js"></script>
    <script src="js/bootstrap.datepicker.js"></script>
    <script src="js/jquery.datetimeentry.js"></script>
    <script src="js/jquery.tools.form.min.js"></script>
    <script src="js/jquery.colresizable.min.js"></script>
    <script src="js/jquery.browser.js"></script>
    <script src="js/jquery.autoheight.js"></script>
    <script src="js/bootstrap-select.js"></script>
    <script src="js/decoder/wrapper.js"></script>
    <script src="js/omniplayer.js"></script>
<?php
if(isset($extrahead)) {
    foreach($extrahead as $bloque) {
        echo "$bloque";
    }
}
?>
<script>

var lang = new Object();
var language='<?php 
if(isset($_SESSION[MYAP]['language'])) {
    echo $_SESSION[MYAP]['language'];
} else {
    echo "en";
}
?>';

jQuery(document).ready(function($) {
    jQuery.ajaxSetup({async: false});
    var ret = jQuery.getScript("js/lang_"+language+".js");
    if(ret==1) {
        jQuery.getScript("js/lang_en.js");
    } 
    $('.clicktodial').attr('data-original-title',lang.dial);
    jQuery.ajaxSetup({async: true});
    jQuery("time.timeago").timeago();
    $('.ttip [data-toggle="tooltip"]').tooltip({container:'body'});

    $('.duration').each(function(ev,el) {
         seconds = $(el).text();
         dura = new Date(1000 * seconds).toISOString().substr(11, 8);
         $(el).text(dura);
    });

});

function debug(message) {
    if(window.console !== undefined) {
        console.log(message);
    }
};

function setFilterDir(elem) {
    valor = elem.options[elem.selectedIndex].value;
    srch = $('#dbgrid_search').val();
    if(srch!='') { insertParam('dbgrid_search',srch,''); }
    insertParam('filterdir',valor,'');
    insertParam('dbgrid_page',1,'reload');
}

function setFilterDispo(elem) {
    valor = elem.options[elem.selectedIndex].value;
    srch = $('#dbgrid_search').val();
    if(srch!='') { insertParam('dbgrid_search',srch,''); }
    insertParam('filterdispo',valor,'');
    insertParam('dbgrid_page',1,'reload');
}

var kvp = document.location.search.substr(1).split('&');

function insertParam(key, value) {
    key = escape(key); value = escape(value);

    var i=kvp.length; var x; while(i--) {
        x = kvp[i].split('=');

        if (x[0]==key) {
                x[1] = value;
                kvp[i] = x.join('=');
                break;
        }
    }

    if(i<0) {kvp[kvp.length] = [key,value].join('=');}

    if(commit='reload') {
        document.location.search = kvp.join('&'); 
    }
}

function downloadRecording(el) {

    var hash = $(el).data('hash');
    var file = $(el).data('filename');
    var pars  = {};
    var url   = 'setvar.php';
    var pars2 = hash+"!"+file;

    pars['sesvar']='vfile';
    pars['value']=file;

    jQuery.ajax( {
       type: 'POST',
       url: url,
       data: pars,
       success: function(output, status) {
           debug("success now try downloadFile "+pars2);
           downloadFile('download.php',pars2);
       }
    });
}

function playRecording(el) {

    var key = $(el).data('key');
    var hash = $(el).data('hash');
    var file = $(el).data('filename');
    var iconid = $(el).data('uni');
    var url  = 'setvar.php';
    var pars = {};

    var format = /[^\.]*$/.exec(file);

    debug('file '+file+' and format '+format);
    pars['sesvar']='vfile';
    pars['value']=file;
    pars2  = hash+"!"+file;
    url2   = 'download.php?file='+pars2;
    debug("Attempt to download disk file "+file);

    jQuery.ajax({
        type: 'POST',
        url: url,
        data: pars,
        success: function(output, status) {
            console.log("sesvar ok, now play with " + url2 + " on icon " + iconid);
            if ($('#' + iconid).hasClass('playing')) {
                doplay(url2,iconid);
                console.log('is already playing');
            } else {
                doplay(url2,iconid);
                console.log('do play');
            }
        }
    });

}

function downloadFile(url,pars) {
    $('#dloadfrm').attr('action',url);
    $('#file').val(pars); 
    $('#dloadfrm').submit();
}

</script>

</head>
<body style='overflow-x: hidden; padding-top:0;'>
<div class='xcontainer-fluid'>
<?php

/*
echo "<pre>";
print_r($_REQUEST);
echo "</pre>";
*/

if($allowed <> "yes") {
    
    if(!isset($_SESSION[MYAP]['retries'])) {
        $_SESSION[MYAP]['retries']=1;
    } else {
        $_SESSION[MYAP]['retries']++;
    }

   echo "<div class='container-fluid text-center'><br/>";

   if($_SESSION[MYAP]['retries']>10) {
       echo "<h3 class='animated tada'>You do not have permissions to access this resource</h3>";
       echo "<br/><br/><btn class='btn btn-default' onclick='javascript:window.location.reload();'>Refresh</button>";
   } else {
       echo "<h3>Please wait...</h3>";
   }
   echo "</div>";
   die();
}

if($cdrfull=='yes') {
    $allextens = json_decode($_SESSION[MYAP]['allowed_extensions']);
    $fop2extensions = "'".implode("','",$allextens)."'";
}

if($context=="") { 
    $addcontext="";
} else {
    $addcontext="{$context}_";
}

// Sanitize Input
$addcontext = preg_replace("/\.[\.]+/", "", $addcontext);
$addcontext = preg_replace("/^[\/]+/", "", $addcontext);
$addcontext = preg_replace("/^[A-Za-z][:\|][\/]?/", "", $addcontext);

$extension = preg_replace("/'/", "",  $extension );
$extension = preg_replace("/\"/", "", $extension );
$extension = preg_replace("/;/", "",  $extension );

$transinbound = 'inbound';
$transoutbound = 'outbound';

$grid =  new dbgrid($db);
$grid->set_table($CDRDBTABLE);
$grid->set_pk('uniqueid');
$grid->add_structure('number', 'text',null,'');
$grid->salt("dldli3ksa");

if($cdrfull=='yes') {
    $grid->set_fields("IF(dst IN ($fop2extensions),IF(src IN ($fop2extensions) OR cnum IN ($fop2extensions),'internal','inbound'),'outbound') AS direction, $datefield, src, dst, IF(duration>billsec,duration,billsec) AS duration,disposition,uniqueid,clid,$recordingfield");
} else {
    $grid->set_fields("IF(dst='".$extension."' OR dstchannel LIKE 'SIP/$extension-________' OR dstchannel LIKE 'PJSIP/$extension-________','$transinbound','$transoutbound') as direction,$datefield,IF(dst='".$extension."' OR dstchannel LIKE 'SIP/$extension-________' OR dstchannel LIKE 'PJSIP/$extension-________',src,dst) as number,IF(duration>billsec,duration,billsec) as duration,disposition,uniqueid,clid,$recordingfield");
}

$grid->hide_field('uniqueid');
$grid->hide_field('clid');
$grid->hide_field('disposition');
$grid->hide_field($recordingfield);
$grid->no_edit_field('number');
$grid->no_edit_field($recordingfield);

$condstring="";

$direction_filter   = isset($_REQUEST['filterdir'])?$_REQUEST['filterdir']:'';
$disposition_filter = isset($_REQUEST['filterdispo'])?$_REQUEST['filterdispo']:'';
$number_filter      = isset($_REQUEST['dbgrid_numbers'])?$_REQUEST['dbgrid_numbers']:'';

if($number_filter=='') {
    $grid->set_per_page(8);
} else {
    $grid->set_per_page(6);
}

$customboton="<form class='form-inline'><div class='form-group'>
             <select class='form-control selectpicker' name='filterby' onchange='setFilterDir(this)'>";
$customboton.="<option value='' "; if($direction_filter=='') { $customboton.= 'selected'; }; $customboton.= ">".trans('All')."</option>\n";
$customboton.="<option value='inbound' "; if($direction_filter=='inbound'){ $customboton.= 'selected';}; $customboton.= ">".trans('inbound')."</option>\n";
$customboton.="<option value='outbound' ";if($direction_filter=='outbound'){ $customboton.= 'selected';}; $customboton.= ">".trans('outbound')."</option>\n";
$customboton.="</select></div>\n";

$grid->add_custom_toolbar($customboton);
$grid->add_custom_action('renderPlay');
$grid->add_custom_action('renderDownload');

if($cdrfull=='yes') {

    if($number_filter!='') {
        $nbrs = preg_split("/,/",$number_filter);
        $condstring.="(";
        $numa = array();
        foreach($nbrs as $nbr) {
            $numa[]="dst='$nbr'";
        }
        $condstring.=implode(" OR ",$numa);
        $condstring.=" OR ";
        $numa = array();
        foreach($nbrs as $nbr) {
            $numa[]="src='$nbr' OR cnum='$nbr'";
        }
        $condstring.=implode(" OR ",$numa);
        $condstring.=")";
    }

} else {
    // limited call history to own extension

    if($direction_filter=="") {
        if($number_filter=='') {
            $condstring ="(src='$extension' OR channel LIKE 'SIP/$extension-________' OR channel LIKE 'PJSIP/$extension-________' OR dst='$extension' OR dstchannel LIKE 'SIP/$extension-________' OR dstchannel LIKE 'PJSIP/$extension-________' ) ";
        } else {
            $nbrs = preg_split("/,/",$number_filter);
            $condstring ="(((src='$extension' OR channel LIKE 'SIP/$extension-________' OR channel LIKE 'PJSIP/$extension-________') && ";
            $condstring.="(";
            $numa = array();
            foreach($nbrs as $nbr) {
                $numa[]="dst='$nbr'";
            }
            $condstring.=implode(" OR ",$numa);
            $condstring.=")) OR (";
            $condstring.="(dst='$extension' OR dstchannel LIKE 'SIP/$extension-________' OR dstchannel LIKE 'PJSIP/$extension-________' ) && ";
            $condstring.="(";
            $numa = array();
            foreach($nbrs as $nbr) {
                $numa[]="src='$nbr'";
            }
            $condstring.=implode(" OR ",$numa);
            $condstring.=")))";
        }
    } else if($direction_filter=="inbound") {
        if($number_filter=='') {
            $condstring ="(dst='$extension' OR dstchannel LIKE 'SIP/$extension-________' OR dstchannel LIKE 'PJSIP/$extension-________') ";
        } else {
            $nbrs = preg_split("/,/",$number_filter);
            $condstring="((dst='$extension' OR dstchannel LIKE 'SIP/$extension-________' OR dstchannel LIKE 'PJSIP/$extension-________' ) && ";
            $condstring.="(";
            $numa = array();
            foreach($nbrs as $nbr) {
                $numa[]="src='$nbr'";
            }
            $condstring.=implode(" OR ",$numa);
            $condstring.="))";
        }
    } else {
        if($number_filter=='') {
            $condstring="(src='$extension' OR channel LIKE 'SIP/$extension-________' OR channel LIKE 'PJSIP/$extension-________')";
        } else {
            $nbrs = preg_split("/,/",$number_filter);
            $condstring="((src='$extension' OR channel LIKE 'SIP/$extension-________' OR channel LIKE 'PJSIP/$extension-________') &&";
            $condstring.="(";
            $numa = array();
            foreach($nbrs as $nbr) {
                $numa[]="dst='$nbr'";
            }
            $condstring.=implode(" OR ",$numa);
            $condstring.="))";
        }
    }
}

if($cdrfull=='yes') {
    if($direction_filter=="") {
        $having = "HAVING direction='inbound' OR direction='outbound'";
    } else if($direction_filter=="inbound") {
        $having = "HAVING direction = 'inbound'";
    } else {
        $having = "HAVING direction = 'outbound'";
    }
}

if($disposition_filter<>"") {
    if($condstring<>"") { $condstring .= " AND "; }
    $condstring .="(disposition='".strtoupper($direction_filter)."') ";
} 

//if($cdrfull=='yes') {
    if($condstring<>"") { $condstring .= " AND "; }
    $condstring .= "dstchannel<>'' AND dstchannel NOT LIKE 'Local%%'";
//}

// Uncomment this if you want to make the cdr reports tenant aware in Thirdlane or similar setups
// The userfield might need to be changed to the proper field
//
// if($condstring<>"") { $condstring .= " AND "; }
// $condstring .= "(userfield='$context') ";


$customboton="<div class='form-group'>
             <select class='form-control selectpicker' name='filterbydispo' onchange='setFilterDispo(this)'>";
$customboton.="<option value='' "; if($disposition_filter=='') { $customboton.= 'selected'; }; $customboton.= ">".trans('All')."</option>\n";
$customboton.="<option value='answered' "; if($disposition_filter=='answered'){ $customboton.= 'selected';}; $customboton.= ">".trans('Answered')."</option>\n";
$customboton.="<option value='no answer' ";if($disposition_filter=='no answer'){ $customboton.= 'selected';}; $customboton.= ">".trans('No answer')."</option>\n";
$customboton.="<option value='busy' ";if($disposition_filter=='busy'){ $customboton.= 'selected';}; $customboton.= ">".trans('Busy')."</option>\n";
$customboton.="<option value='failed' ";if($disposition_filter=='failed'){ $customboton.= 'selected';}; $customboton.= ">".trans('Failed')."</option>\n";
$customboton.="</select></div>";

$customboton.="</form>\n";
$grid->add_custom_toolbar($customboton);

$grid->set_condition($condstring.' '.$having);

$fieldname = Array();
$fieldname[]=trans('Direction');
$fieldname[]=trans('Date');
$fieldname[]=trans('Number');
$fieldname[]=trans('Duration');
$fieldname[]=trans('Billsec');
$fieldname[]=trans('Disposition');
$fieldname[]=trans('Uniqueid');
$fieldname[]=trans('Clid');
$fieldname[]=trans('src');
$fieldname[]=trans('dst');
$fieldname[]=trans('dcontext');
$fieldname[]=trans('channel');
$fieldname[]=trans('dstchannel');
$fieldname[]=trans('lastapp');
$fieldname[]=trans('lastdata');
$fieldname[]=trans('amaflags');
$fieldname[]=trans('accountcode');
$fieldname[]=trans('userfield');
$fieldname[]=trans('did');
$fieldname[]=trans('recordingfile');
$fieldname[]=trans('cnum');
$fieldname[]=trans('cnam');
$fieldname[]=trans('outbound_cnum');
$fieldname[]=trans('outbound_cnam');
$fieldname[]=trans('dst_cnam');
$grid->set_display_name( array('direction',$datefield,'number','duration','billsec','disposition', 'uniqueid', 'clid', 'src', 'dst', 'dcontext', 'channel', 'dstchannel', 'lastapp', 'lastdata', 'amaflags', 'accountcode', 'userfield', 'did', 'recordingfile', 'cnum', 'cnam', 'outbound_cnum', 'outbound_cnam', 'dst_cnam'), $fieldname);

$grid->set_nocheckbox(true);
$grid->allow_view(true);
$grid->allow_edit(false);
$grid->allow_delete(false);
$grid->allow_add(false);
$grid->allow_export(false);
$grid->allow_import(false);
$grid->allow_search(true);
$grid->set_orderby("$datefield");
$grid->set_orderdirection("DESC");
$grid->set_search_fields(array('src','dst',$datefield,'clid','uniqueid'));

$grid->add_structure('direction','hidden',null,null,null,null,null);

$grid->add_display_filter("direction",'directionFilter');
$grid->add_display_filter("duration",'durationFilter');

$grid->add_display_filter($datefield,'dateFilter');

$grid->add_display_filter('number','clickdial');

$grid->show_grid();

function durationFilter($duration,$datos) {
    return "<span class='duration'>$duration</span>";
}

function dateFilter($date,$datos) {
    $date = str_replace(" ","T",$date);
    $date .= 'Z';
    return "<time class='timeago ttip' data-toggle='tooltip' data-placement='bottom' datetime='$date'>$date</time>";
}

function directionFilter($direction,$datos) {
    $trans = trans($direction);
    if($direction=='inbound') {
        if($datos['disposition']=='ANSWERED') {
            $icon="<i class='fop2-icon-call-received'></i>";
        } else {
            $icon="<i class='fop2-icon-call-missed text-danger ttip' data-toggle='tooltip' data-placement='bottom' data-original-title='".trans($datos['disposition'])."'></i>";
        }
    } else {
        if($datos['disposition']=='ANSWERED') {
            $icon="<i class='fop2-icon-call-made'></i>";
        } else {
            $icon="<i class='fop2-icon-call-missed text-danger' data-toggle='tooltip' data-placement='bottom' data-original-title='".trans($datos['disposition'])."'></i>";
        }
    }

    return $icon.' '.$trans;
}

function dispoColor($dispo,$datos) {

   $clid = htmlentities($datos['clid']);

   $color=array();
   $color['ANSWERED']="</i><span class='label label-success' title='$clid'>".trans($dispo)."</span>";
   $color['NO ANSWER']="<span class='label label-danger' title='$clid'>".trans($dispo)."</span>";
   $color['FAILED']="<span class='label label-danger' title='$clid'>".trans($dispo)."</span>";
   $color['BUSY']="<span class='label label-warning' title='$clid'>".trans($dispo)."</span>";
   if(isset($color[$dispo])) {
      return $color[$dispo];
   } else {
      return $dispo;
   }
}

function clickdial($number,$datos) {

   global $transoutbound;

   $numberstrip = preg_replace("/[^0-9#\*]/","",$number);
   $clid = htmlentities($datos['clid']);

   if($datos['direction']==$transoutbound) {
      $toprint = $number;
   } else {
      $toprint = $clid;
   }

   if(strlen($numberstrip)>0) {
       return "<div style='height:1.5em;'><a data-toggle='tooltip' class='clicktodial ttip' data-original-title='click to dial' href='javascript:void();' onclick='parent.dial(\"$numberstrip\"); return false;'>$toprint</a></div>";
   } else {
       return $number;
   }
}

function getFullRecordingPath($file) {
    global $recordingfield;
    if(substr($file,0,1)=='/') {
        $filename = $file;
    } else {
        $partes = preg_split("/-/",$file);
        $year   = substr($partes[3],0,4);
        $month  = substr($partes[3],4,2);
        $day    = substr($partes[3],6,2);
        $filename="/var/spool/asterisk/monitor/$year/$month/$day/".$file;
    }
    if(is_file($filename)) {
        return $filename;
    } else {
        return '';
    }
}

function renderPlay($data) {
    global $recordingfield;
    if($data[$recordingfield]!='') {
        $filename = getFullRecordingPath($data[$recordingfield]);
        if($filename!='') {
            $hash = md5($_SESSION[MYAP]['key']);
            $uniuni = preg_replace("/[^a-zA-Z0-9]/","",$data[$recordingfield]);
            return "<button class='ply btn' data-toggle='tooltip' title='".trans('Play')."' data-hash='".$hash."' data-filename='".$filename."' data-uni=".$uniuni." onclick='playRecording(this)'><i class='fa fa-play-circle' id='".$uniuni."'></i></button>";
        } else {
            return '';
        }
    }
}

function renderDownload($data) {
    global $recordingfield;
    if($data[$recordingfield]!='') {
        $filename = getFullRecordingPath($data[$recordingfield]);
        if($filename!='') {
            $hash=md5($_SESSION[MYAP]['key']);
            return "<button class='btn' data-toggle='tooltip' title='".trans('Download')."' data-hash='".$hash."' data-filename='".$filename."' onclick='downloadRecording(this)'><i class='fa fa-play-circle fa-rotate-90'></i></button>";
        } else {
            return '';
        }
    }
}


?>
</div>
</div>
<audio-view id="omniplayer" class='playercdr' tabindex="0"></audio-view>
<form id='dloadfrm' method='post'><input type=hidden id='file' name='file'></form>
</body>
</html>
