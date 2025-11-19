<?php
header("Content-Type: text/html; charset=utf-8");
require_once("config.php");

$page_title = trans('Tags');

// Session Variables
$context   = isset($_SESSION[MYAP]['context'])?$_SESSION[MYAP]['context']:'';
$extension = isset($_SESSION[MYAP]['extension'])?$_SESSION[MYAP]['extension']:-1;
$admin     = isset($_SESSION[MYAP]['admin'])?$_SESSION[MYAP]['admin']:0;
$allowed   = isset($_SESSION[MYAP]['phonebook'])?$_SESSION[MYAP]['phonebook']:'no';

if(isset($_SESSION[MYAP]['permit'])) {
    $permits = preg_split("/,/",$_SESSION[MYAP]['permit']);
    if($allowed=='no' && in_array('phonebook',$permits)) {
       $allowed='yes';
    }
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
    die();
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
    <link href="css/bootstrap.min.css" media="screen" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" type="text/css" href="css/bootstrap.datepicker.css" />
    <link rel="stylesheet" type="text/css" href="css/dbgrid.css" />
    <link rel="stylesheet" type="text/css" href="css/flags.css" />
    <link media="screen" rel="stylesheet" type="text/css" href="css/coloris.min.css" />
    <link rel="stylesheet" type="text/css" href="css/animate.css" />
    <link media="screen" rel="stylesheet" type="text/css" href="css/vmail.css" />
    <script src="js/jquery-1.11.3.min.js" type="text/javascript"></script>
    <script src="js/moment-with-locales.js"></script>
    <script src="js/swfobject.js"></script>
    <script src="js/jquery.plugin.js"></script>
    <script src="js/jquery.noty.js"></script>
    <script src="js/chosen.jquery.js"></script>
    <script src="js/jquery.jconf.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/bootstrap-switch.min.js"></script>
    <script src="js/bootstrap-dropdown-on-hover.js"></script>
    <script src="js/bootstrap.datepicker.js"></script>
    <script src="js/jquery.datetimeentry.js"></script>
    <script src="js/jquery.tools.form.min.js"></script>
    <script src="js/jquery.colresizable.min.js"></script>
    <script src="js/jquery.browser.js"></script>
    <script src="js/jquery.autoheight.js"></script>
    <script src="js/soundengine.js"></script>
    <script src="js/coloris.min.js"></script>

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

</script>

</head>
<body style='overflow-x: hidden;'>
<div class='xcontainer-fluid'>
<?php

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

$grid = new dbgrid($db);
$grid->set_table('visual_phonebook_tags');
$grid->salt("dldli3ks");
$grid->hide_field('id');
$grid->hide_field('context');
$grid->no_edit_field('id');
$grid->set_input_type('context','hidden');
$grid->set_input_type('id','hidden');
$grid->set_per_page(6);
$grid->add_extra_attribute('color','data-coloris');
if($context!='') {
    // Multi Tenant condition
    $grid->set_default_values('context',$context);
    $grid->set_condition("context='$context'");
} else {
    $grid->no_edit_field('context');
}

$grid->set_fields('id,tag,color,context');

$fieldname = Array();
$fieldname[]=trans('Tag');
$fieldname[]=trans('Color');
$grid->set_display_name( array('tag','color'), $fieldname);

$grid->set_nocheckbox(true);
$grid->allow_view(false);
$grid->allow_edit(true);
$grid->allow_delete(true);
$grid->allow_add(true);
$grid->allow_export(false);
$grid->allow_import(false);
$grid->allow_search(true);
$grid->set_orderby("tag");
$grid->set_search_fields(array('tag'));
$grid->add_display_filter('color','show_color');

$grid->show_grid();

function show_color($color) {
    return "<div style='width:3em;height:1.5em;background-color:$color;'></div>";
}


?>
</div>
</body>
</html>
