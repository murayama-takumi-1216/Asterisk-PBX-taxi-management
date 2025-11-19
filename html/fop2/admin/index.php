<?php
require_once("config.php");
require_once("functions.php");
require_once("dblib.php");
require_once("asmanager.php");
require_once("system.php");
require_once("http.php");

include("headerbs.php");

echo "<div class='wrap'>\n";

// Routines to setup database tables and initial data
require_once("dbconn.php");
include("dbsetup.php");

$callbacks = fop2_get_plugins_callback();
foreach($callbacks as $rawname=>$file) {
    include($file);
}

require_once("secure/secure-functions.php");
require_once("secure/secure.php");

include("menu.php");

echo "<div class='content'>\n";
echo "<h2>".__("Welcome")."</h2>";
echo "<p>";
if($_SESSION['FOP2Manager']['AUTHVAR']['level']=='admin') {
echo $APPNAME." ".__("lets you configure users, permissions, button details and options. It will also let you install, uninstall and configure plugins. It will read data from your configuration backend and populate its own tables with your preferences.");
}
echo "</p>";
echo "<hr/>";

echo "<div class='row'>\n";

if(check_acl("FOP2Manager Upgrade Notification")) {
    // new fop2manager version widget
    echo "<div class='col-md-12' style='display:none;' id='upgradeavailable'>\n";
    echo "<div class='card card-warning grey'>\n";
    echo "<div class='card-header'>\n";
    echo "<div class='card-title'><div class='title'>\n";
    echo __("Upgrade Available");
    echo "</div>\n";
    echo "</div>\n";
    echo "</div>\n";
    echo "<div class='card-body'>\n";
    echo "<a class='ttip' href='#' id='chlogbtn' onclick='togglechangelog()' data-toggle='popover' data-trigger='hover' data-placement='right' data-content='";
    echo __('Show Changelog');
    echo "'><span class='fa fa-angle-down' id='chlogicon'></span></a>&nbsp;";
    echo "<span id='newversion'></span>";
    echo " ";
    echo "<span id='curversion'></span>";
    echo "<button class='btn btn-warning pull-right' id='upgradeBtn' >".__('Upgrade')."</button>";
    echo "<div id='changelog' style='display:none;'>";
    echo "<hr/>\n";
    echo __('Changelog');
    echo "<div id='changelog'></div>";
    echo "</div>\n";
    echo "</div>\n";
    echo "</div>\n";
    echo "</div>\n";
}

if (!extension_loaded('pdo_sqlite')) {
    echo "<div class='col-md-12'>\n";
    echo "<div class='card card-warning grey'>\n";
    echo "<div class='card-header'>\n";
    echo "<div class='card-title'><div class='title'>\n";
    echo __("PHP SQLite module not installed");
    echo "</div>\n";
    echo "</div>\n";
    echo "</div>\n";
    echo "<div class='card-body'>\n";
    echo __("You must install the SQLite module for PHP if you want to change/set global settings and user preferences from this tool.");
    echo " ";
    echo "</div>\n";
    echo "</div>\n";
    echo "</div>\n";
}

echo "<div class='col-md-6' id='fop2serverstatus'>\n";
echo "<div class='alert alert-success'>";
echo "<div class='spinner2'><div class='bounce1'></div><div class='bounce2'></div><div class='bounce3'></div></div>";
echo "</div>";
echo "</div>";

// This shows the backend engine
echo "<div class='col-md-6'>\n";
if(check_acl("Backend Engine Widget")) {
    echo "<div class='alert alert-success'><strong>";
    echo sprintf( __('Backend Engine: %s'), $config_engine );
    echo "</strong></div>\n";
    echo "</div>";
}
echo "</div>";

if(check_acl("Licensed Plugis Widget")) {
    echo "<div class='card card-success grey' id='plugbox'>\n";
    echo "<div class='card-header'><div class='card-title'><div class='title'>\n";
    echo __("Licensed Plugins");
    echo "</div></div></div>";
    echo "<div class='card-body'>";
    echo "<div id='licensedplugins'><div class='spinner2'><div class='bounce1'></div><div class='bounce2'></div><div class='bounce3'></div></div></div>\n";
    echo "</div>";
    echo "</div>";
}

if($db->is_connected()) {
    // Information on Home

    if($panelcontext<>'') {
        $where = " WHERE context_id='$panelcontext' ";
        $whererc = " AND context_id='$panelcontext' ";
    } else {
        $where = '';
        $whererc = '';
    }

    fop2_recreate_default_groups($predefined_groups,$panelcontext,$whererc);

    echo "<div class='row'>\n";

    $sections = Array();
    $sections['Users']      = array('table'=>'fop2users','icon'=>'fa-user');
    $sections['Buttons']    = array('table'=>'fop2buttons','icon'=>'fa-th-large');
    $sections['Templates']  = array('table'=>'fop2templates','icon'=>'fa-list-alt');
    $sections['Groups']     = array('table'=>'fop2groups','icon'=>'fa-users');

    foreach($sections as $section=>$adata) {

        $table = $adata['table'];
        $icon  = $adata['icon'];

        $res = $db->consulta("SELECT count(*) FROM $table $where");
        $row = $db->fetch_row($res);
        $cont = $row[0];

        if(check_acl($section)) {
            if(isset($menu_link[$section])) {
                $widget_link = $menu_link[$section];
            } else {
                $widget_link = '';
            }
            draw_dashboard_widget($section,$widget_link,$icon,$cont);
        }
    }

    // Draw widgets for plugins secionts if any, defined in callbacks.php as rawname_dashboard_widget()

    // First we read module.xml from plugins to see if they have a menu added, and from there we extract the name, icon and link
    // We store data on an array for every possible plugin menu
    $pluginmenu = fop2_get_plugins_menu();
    $mani=array();
    foreach($pluginmenu as $rawname=>$menuxml) {
        $xml      = simplexml_load_file($menuxml);
        $xmlarray = simple_xml_to_array($xml);
        if(!isset($xmlarray['menu']['item'])) {
             foreach($xmlarray['menu'] as $idx=>$nitem) {
                 $nitem['item']['rawname']=$rawname;
                 array_push($mani,$nitem['item']);
             }
        } else {
            $xmlarray['menu']['item']['rawname']=$rawname;
            array_push($mani,$xmlarray['menu']['item']);
        }
    }

    foreach($callbacks as $rawname=>$file) {
        foreach($mani as $idx => $arrmenu) {
            if($rawname == $arrmenu['rawname']) {
                 $link = $arrmenu['action'];
                 $name = $arrmenu['name']; 
                 $icon = $arrmenu['icon']; 
                 $fname = $rawname."_".strtolower(str_replace(" ","",$name))."_dashboard_widget";
                 if(is_callable($fname)) {
                     if(check_acl(strtolower($name))) {
                         call_user_func($fname,$name,$link,$icon);
                     }
                 }
            }
        }
    }
}
?>

</div>
</div>

<div id="upgradeModal" class="modal fade" aria-hidden="true" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h3><?php echo __('Please wait...'); ?></h3>
      </div>
      <div class="modal-body">
        <iframe id='modaliframe' src='about:blank' style="width:99.6%; height:200px; border:0;"></iframe>
      </div>
      <div class="modal-footer">
          <button type="button" id='modalclose' class="btn btn-default" data-dismiss="modal"><?php echo __('Close');?></button>
      </div>
    </div>
  </div>
</div>
<div class="push"></div>
</div>
<script>
$('#modalclose').hide();

$('#upgradeModal').on('show.bs.modal', function () {
    $('iframe').attr("src",frameSrc);
});

$('#upgradeModal').on('hidden.bs.modal', function () {
    window.location.reload();
});

$('#upgradeBtn').click(function(){
   $('#upgradeModal').modal('show');
});

function togglechangelog() {
    $('#changelog').toggle();
    if($('#changelog').is(':visible')) {
        $('#chlogicon').removeClass('fa-angle-down').addClass('fa-angle-up');;
        $('#chlogbtn').attr('data-content','<?php echo __('Hide Changelog');?>');
    } else {
        $('#chlogicon').removeClass('fa-angle-up').addClass('fa-angle-down');;
        $('#chlogbtn').attr('data-content','<?php echo __('Show Changelog');?>');
    }
}

<?php
if(isset($_SESSION[MYAP]['needsreload'])) {
?>
    $('#fop2reload').show();
<?php
}
?>

$.ajax({
    url: 'checkupdates.php',
    contentType: 'charset=utf8'
});

$.ajax({
    url: 'checkserver.php',
    contentType: 'charset=utf8'
});
  
</script>

<?php

include("footerbs.php");
