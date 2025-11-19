<?php
header("Content-Type: text/html; charset=utf-8");
require_once("config.php");

$context   = $_SESSION[MYAP]['context'];
$extension = $_SESSION[MYAP]['extension'];
$permit    = $_SESSION[MYAP]['permit'];
$admin     = isset($_SESSION[MYAP]['admin'])?$_SESSION[MYAP]['admin']:0;
$permisos  = preg_split("/,/",$permit);

if(in_array("all",$permisos) || in_array("record",$permisos) || in_array("recordself",$permisos)) {
    $allowed='yes';
} else {
    $allowed='no';
}


?>
<!DOCTYPE html>
<html lang="en">
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
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <meta http-equiv="imagetoolbar" content="false"/>
    <meta name="MSSmartTagsPreventParsing" content="true"/>
    <meta name="description" content=""/>
    <meta name="keywords" content=""/>
    <link rel="stylesheet" type="text/css" href="css/jconf.css" />
    <link rel="stylesheet" type="text/css" href="css/chosen.css" />
    <link rel="stylesheet" type="text/css" href="css/jquery.noty.css" />
    <link href="css/bootstrap.min.css" media="screen" rel="stylesheet" type="text/css">
    <link rel="stylesheet" type="text/css" href="css/bootstrap.datepicker.css" />
    <link rel="stylesheet" type="text/css" href="css/dbgrid.css" />
    <link rel="stylesheet" type="text/css" href="css/flags.css" />
    <link rel="stylesheet" type="text/css" href="css/animate.css" />
    <link rel="stylesheet" type="text/css" href="css/operator.css" />
    <link media="screen" rel="stylesheet" type="text/css" href="css/font-awesome.min.css"     >
    <style>.btn{padding:5px 7px;}</style>
    <!--link media="screen" rel="stylesheet" type="text/css" href="css/vmail.css" /-->
    <script src="js/jquery-1.11.3.min.js" type="text/javascript"></script>
    <script src="js/moment-with-locales.js"></script>
    <script type="text/javascript" src="js/swfobject.js"></script>
    <script type="text/javascript" src="js/jquery.plugin.js"></script>
    <script type="text/javascript" src="js/jquery.noty.js"></script>
    <script type="text/javascript" src="js/chosen.jquery.js"></script>
    <script type="text/javascript" src="js/jquery.jconf.js"></script>
    <script src="js/bootstrap.min.js" type="text/javascript"></script>
    <script src="js/bootstrap-switch.min.js" type="text/javascript"></script>
    <script src="js/bootstrap-dropdown-on-hover.js " type="text/javascript"></script>
    <script type="text/javascript" src="js/bootstrap.datepicker.js"></script>
    <script type="text/javascript" src="js/jquery.datetimeentry.js"></script>
    <script type="text/javascript" src="js/jquery.tools.form.min.js"></script>
    <script type="text/javascript" src="js/jquery.colresizable.min.js"></script>
    <script type="text/javascript" src="js/jquery.browser.js"></script>
    <script type="text/javascript" src="js/jquery.autoheight.js"></script>
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

function debug(message) {
    if (window.console !== undefined) {
        console.log(message);
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

$res = $db->consulta("SHOW TABLES LIKE 'fop2recordings'");
if($db->num_rows($res)==0) {
    $querycreate="CREATE TABLE `fop2recordings` (
      `id` int(11) NOT NULL auto_increment,
      `uniqueid` varchar(50) default NULL,
      `datetime` datetime default NULL,
      `ownerextension` varchar(20) default NULL,
      `targetextension` varchar(20) default NULL,
      `filename` tinytext,
      `duration` int(11) default '0',
      `context` varchar(200) default NULL,
      PRIMARY KEY  (`id`),
      UNIQUE KEY `uni` (`uniqueid`)
    )";
    $ris = $db->consulta($querycreate);
    if(!$ris) {
        echo "<div class='container-fluid text-center'><br/><h3 class='animated tada'>Could not connect/create the recordings table.<br/><br/>Please verify your mysql credentials in config.php.</h3></div>";
        die();
    }
}


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

if($context=="") { 
    $addcontext="";
} else {
    $addcontext="${context}_";
}

// Sanitize Input
$addcontext = preg_replace("/\.[\.]+/", "", $addcontext);
$addcontext = preg_replace("/^[\/]+/", "", $addcontext);
$addcontext = preg_replace("/^[A-Za-z][:\|][\/]?/", "", $addcontext);

$extension = preg_replace("/'/", "",  $extension );
$extension = preg_replace("/\"/", "", $extension );
$extension = preg_replace("/;/", "",  $extension );

$grid =  new dbgrid($db);
$grid->set_table('fop2recordings');
$grid->salt("dldli3ks");
$grid->hide_field('id');
$grid->hide_field('context');
$grid->hide_field('filename');
$grid->no_edit_field('context');
$grid->no_edit_field('id');
$grid->set_per_page(12);

$grid->add_custom_action('renderPlay');
$grid->add_custom_action('renderDownload');

if($context=='') {
    // Single tenant condition
    $grid->set_condition("(ownerextension='$extension' OR $admin=1)");
} else {
    // Multi Tenant condition
    $grid->set_condition("context='$context' AND (ownerextension='$extension' OR $admin=1)");
}

$grid->set_fields('id,uniqueid,datetime,ownerextension,targetextension,duration,context,filename');

$fieldname = Array();
$fieldname[]=trans('Unique ID');
$fieldname[]=trans('Date');
$fieldname[]=trans('Owner Extension');
$fieldname[]=trans('Target Extension');
$fieldname[]=trans('Actions');
$fieldname[]=trans('Duration');
$fieldname[]=trans('Context');
$grid->set_display_name( array('uniqueid','datetime','ownerextension','targetextension','filename','duration','context'),
                         $fieldname);

$grid->add_delete_callback('delete_recording');

$grid->set_nocheckbox(false);
$grid->allow_view(true);
$grid->allow_edit(false);
$grid->allow_delete(true);
$grid->allow_add(false);
$grid->allow_export(false);
$grid->allow_import(false);
$grid->allow_search(true);
$grid->set_orderby("datetime");
$grid->set_orderdirection("DESC");
$grid->set_search_fields(array('ownerextension','targetextension','datetime','duration','uniqueid'));

//$grid->add_display_filter('filename','downloadfile');

//$grid->set_column_widths(array('*','*','*','*','*','100'));
$grid->set_input_parent_style("uniqueid","style='width:48%; float:left;'");
$grid->set_input_parent_style("datetime","style='width:48%; float:right; margin-right:10px;'");
$grid->set_input_parent_style("ownerextension","style='clear:both;width:48%; float:left;'");
$grid->set_input_style("ownerextension","style='text-indent:120px;'");
$grid->set_input_style("targetextension","style='text-indent:120px;'");
$grid->set_input_parent_style("targetextension","style='width:48%; float:right; margin-right:10px;'");

//$grid->add_validation_type('email','email');
$grid->show_grid();

function downloadfile($filename) {
   $hash=md5($_SESSION[MYAP]['key']);
   $uniuni = preg_replace("/[^a-zA-Z0-9]/","",$filename);
   return "<div id='$uniuni' class='audioButton' title='Play' onclick='playRecording(\"$hash\",\"$filename\",\"$uniuni\")'><img src='images/pixel.gif' width=16 height=16 alt='pixel' border='0' /></div><div onclick='javascript:downloadRecording(\"$hash\",\"$filename\");' class='audioButton dload' title='Download' id='downloadvm_$uniuni'><img src='images/pixel.gif' width=16 height=16 alt='pixel' border='0' /></div>";
}

function delete_recording($obj) { 
    $filename = ($obj[filename]); 
    unlink($filename);
}

function getFullRecordingPath($file) {
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
    if($data['filename']!='') {
        $filename = getFullRecordingPath($data['filename']);
        if($filename!='') {
            $hash = md5($_SESSION[MYAP]['key']);
            $uniuni = preg_replace("/[^a-zA-Z0-9]/","",$data['filename']);
            return "<button class='ply btn' data-toggle='tooltip' title='".trans('Play')."' data-hash='".$hash."' data-filename='".$filename."' data-uni=".$uniuni." onclick='playRecording(this)'><i class='fa fa-play-circle' id='".$uniuni."'></i></button>";
        } else {
            return '';
        }
    }
}

function renderDownload($data) {
    if($data['filename']!='') {
        $filename = getFullRecordingPath($data['filename']);
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
<audio-view id="omniplayer" class='playercdr' tabindex="0"></audio-view>
<form id='dloadfrm' method='post'><input type=hidden id='file' name='file'/></form>
</body>
</html>
