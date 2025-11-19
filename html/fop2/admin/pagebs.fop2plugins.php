<?php 
require_once("config.php");
require_once("functions.php");
require_once("system.php");
require_once("dblib.php");
require_once("asmanager.php");
require_once("dbconn.php");
require_once("secure/secure-functions.php");
require_once("secure/secure.php");

$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$itemid = isset($_REQUEST['itemid'])?$_REQUEST['itemid']:'';

$upload_msg = '';

$fop2_mirror = get_fastest_mirror();

if($action=='getplugin') {

    plugin_download($itemid, $fop2_mirror);

    exit;

} else if($action=='upload') {

    $res = plugin_handleupload($_FILES['uploadplug']);

    if (is_array($res)) {
        $upload_msg = "<div class='alert alert-danger'>\n";
        $upload_msg.= "<p>";
        $upload_msg.= sprintf(__('The following error(s) occurred processing the uploaded file: %s'), '<ul><li>'.implode('</li><li>',$res).'</li></ul>');
        $upload_msg.= "</p></div>\n";
    } else {
        $upload_msg = "<div class='alert alert-success'>";
        $upload_msg.= "<p>".__("Plugin uploaded successfully. You need to enable it globally or assign it to fop2 users to make it available.")."</p>\n";
        $upload_msg.= "</div>\n";
    }

} else if($action=='delplugin') {

    plugin_delete($itemid);

} else if ($action=='setglobal') {

    $rawname = $_REQUEST['fop2plugin'];
    $enabled = $_REQUEST['fop2globalenabled'];
    $db->consulta("UPDATE fop2plugins SET global='".$db->escape_string($enabled)."' WHERE rawname='".$db->escape_string($rawname)."'");
    exit;

} else if ($action=='savePluginConfig') {

    $rawname   = $_REQUEST['plugin'];
    $inifile   = $PLUGIN_DIR."$rawname/$rawname.ini";
    $ini_array = Array();

    $miparam = Array();
    $configparams = plugin_read_xml_params($rawname);
    if(count($configparams)>0) {  // Tiene parametros de configuracion
        foreach($configparams as $key => $other) {
            foreach($other as $idx => $opt) {
                 $miparam[$opt['name']]=$opt;
            }
        }
    }

    foreach($_REQUEST as $key=>$val) {


        if(preg_match("/$rawname/",$key)) {
            list ($plug, $field, $section) = preg_split("/-/",$key,3);

            if(isset($miparam[$field]['encoded'])) {
                if($miparam[$field]['encoded']=='yes') {
                    $val = base64_encode($val);
                } else {
                    $val = preg_replace('/"/',"'",$val);
                }
            }

            if($section=='default') {
                if($field<>'') {
                    $ini_array[$field]=$val;
                }
            } else if($section=='skeleton') {
            } else {
                if($field<>'') {
                    $ini_array[$section][$field]=$val;
                } 
            } 
        }
    }
    $res = array();
    foreach($ini_array as $key => $val)
    {
        if(is_array($val))
        {
            $res[] = "[$key]";
            foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
        }
        else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
    }
    file_put_contents($inifile, implode("\n", $res), LOCK_EX);

    reload_fop2();
}

list ($fop2version,$fop2registername,$fop2licensetype) = fop2_get_version();
$fop2version   = normalize_version($fop2version);
$fop2version   = intval($fop2version);

$plugin_online = plugin_get_online($fop2_mirror);
$plugin_local  = get_installed_plugins('version');
$plugin_global = plugin_get_global();

if (isset($plugin_online)) {
    $plugin_sorted=array();
	foreach ($plugin_online as $idx=>$arrdata) {
        $plugin_sorted[$idx]=$arrdata['name'];
    }
}
if(is_array($plugin_sorted)) {
   asort($plugin_sorted);
}


include("headerbs.php");
echo "<div class='wrap'>\n";
include("menu.php");

?>
<div class='row' style='background-color:#78a300; padding-bottom:10px;'>
<div class="content">
<div class="col-md-12">
<span class='h2'><?php echo __('Plugins');?></span>
<i style='vertical-align:super; top:-5px; color:#333;' class='ttip fa fa-info-circle'  data-toggle='popover' data-trigger='hover' data-placement='bottom' data-content='<?php echo __('From here you can install, uninstall, upload and configure plugins. You can get plugins from online repositories, or upload and manage local plugins.'); ?>'></i>
</div>
</div>
</div>

<div class='row' style='height:1em;'></div>
<?php
$licensedplugins=explode(',',plugin_get_licensed());

if($licensedplugins[0] == '0') {

    echo  "<div class='alert alert-danger alert-dismissable'>";
    echo "<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>\n";
    echo __("Panel Server is not responding. Be sure the service is running!");
    echo "</div>\n";

}

if($upload_msg <> '') { echo $upload_msg; }

if (count($plugin_online)>0) {

?>

<div id="modulelist">
<div class="category" id="category_Online">
<h3><?php echo __("Online Repository - Available Plugins")."</h3>\n";?>

<div>
<div class='row head' style='background-color:#f2f3f4; margin:0; padding:10px; z-index:2;' id='tablehead'>
<div class='col-md-1'><?php echo __('Price');?></div>
<div class='col-md-3'><?php echo __('Name');?></div>
<div class='col-md-2'><?php echo __('Installed Version');?></div>
<div class='col-md-2'><?php echo __('Available Version');?></div>
<div class='col-md-2'><?php echo __('Globally Enabled');?></div>
<div class='col-md-2'>&nbsp;</div>
</div>
</div>

<?php

	foreach ($plugin_sorted as $idx=>$plugname) {
        $arrdata = $plugin_online[$idx];
        $rawname           = $arrdata['rawname'];
        $name              = $arrdata['name'];
        $plugversion       = $arrdata['version'];
        $description       = $arrdata['description'];
        $help              = $arrdata['help'];
        if(is_array($help)) { $help=''; }
        $price             = intval($arrdata['price']);
        $changelog         = preg_replace("/;/","<br>",$arrdata['changelog']);
        if(is_array($changelog)) { $changelog=''; }
        //$engine            = ($arrdata['engine']<>'')?$arrdata['engine']:$config_engine;
        //$global            = ($arrdata['global']<>'')?$arrdata['global']:'no';
        $engine            = (is_array($arrdata['engine']) && count($arrdata['engine'])==0)?$config_engine:$arrdata['engine'];
        $global            = (is_array($arrdata['global']) && count($arrdata['global'])==0)?'no':$arrdata['global'];
        $fop2minversion    = (is_array($arrdata['fop2minversion']) && count($arrdata['fop2minversion'])==0)?'0':$arrdata['fop2minversion'];
        $fop2minversion    = intval($fop2minversion);

        $installed_version = '-';
        $show_changelog = 0;

        if(isset($arrdata['description-'.$_COOKIE['lang']])) {
            $description = $arrdata['description-'.$_COOKIE['lang']];
            $description = str_replace(array("\0", "\n", "\t", "\r"), '', $description);
        }

        $partes = preg_split('/\./',$plugversion);
        $superversion = $partes[0]*100000 + $partes[1]*1000 + $partes[2]*10;

        // skip plugins for other engines
        $valid_engines = explode(",",$engine);
        if(!in_array($config_engine,$valid_engines)) { continue; }

        if(isset($plugin_local[$rawname])) {
            $installed_version = $plugin_local[$rawname];
            $partes = preg_split('/\./',$plugin_local[$rawname]);
            $superversionlocal = $partes[0]*100000 + $partes[1]*1000 + $partes[2]*10;
        } else {
            $superversionlocal = 0;
        }

        if(in_array($rawname,$licensedplugins)) { $paid=1; } else { $paid=0; }

        if($superversion == $superversionlocal) { 
            $delurl = SELF;
            $status = "<form method=post action='$delurl' id='delete_".$rawname."'>\n";
            $status.= "<input type=hidden name=action value='delplugin'>\n";
            $status.= "<input type=hidden name='itemid' value='$rawname'>\n";
            $status.= "<button class='btn btn-danger col-md-12' onclick='setUninstall(\"$rawname\"); return false;'><i class='fa fa-trash pull-left'></i>&nbsp;".__('Uninstall')."</button>\n";
            $status.= "</form>\n";
        } else if($superversionlocal==0) {
            if($price==0 || ($price>0 && $paid==1)) {
                // get-download plugin, but check if min version is set, otherwise do not show get plugin button
                if($fop2version>0 && $fop2version>=$fop2minversion) {
                    $geturl = SELF;
                    $status = "<form method=post action='$geturl'>\n";
                    $status.= "<input type=hidden name='itemid' value='$rawname-$plugversion'>\n";
                    $status.= "<input type=hidden name='action' value='getplugin'>\n";
                    $status.= "<button class='btn btn-success col-md-12' onclick='getPlugin(\"$rawname-$plugversion\"); return false;'><i class='fa fa-floppy-o pull-left'></i>&nbsp;".__('Install')."</button>\n";
                    $status.= "</form>";
                } else {
                    $status = "<span class='label label-danger'>".__('Not compatible with this version')."</span>"; 
                }
            } else {
                if($fop2version>0 && $fop2version>=$fop2minversion) {
                    $geturl = 'http://www.fop2.com/buy.php?#'.$rawname;
                    $status = "<a class='btn btn-default col-md-12' href='$geturl' onclick='window.open(this.href); return false;'><i class='fa fa-shopping-cart pull-left'></i>&nbsp;".__('Buy')."</a>";
                } else {
                    $status = "<span class='label label-danger'>".__('Not compatible with this version')."</span>"; 
                }
            }
        } else if($superversionlocal<$superversion) {
            if($fop2version>0 && $fop2version>=$fop2minversion) {
                $geturl = SELF;
                $status = "<form method=post action='$geturl'>\n";
                $status.= "<input type=hidden name=action value='getplugin'>\n";
                $status.= "<input type=hidden name=itemid value='$rawname-$plugversion'>\n";
                $status.= "<button class='btn btn-success col-md-12' onclick='getPlugin(\"$rawname-$plugversion\"); return false;'>";
                $status.= __('Update');
                $status.= "</button></form>";
                $show_changelog=1;
           } else {
                $status = "<span class='label label-danger'>".__('Not compatible with this version')."</span>"; 
           }
        }
        
        if(isset($plugin_global[$rawname]) || $global=='yes') { $checked=' checked '; } else { $checked=''; }

        if($global=='yes') { $disabled='disabled=disabled'; } else { $disabled=''; }

        if($price<=0) { 
            $pr="<span class='label label-success'>".__('Free')."</span>";
        } else { 
            if($paid==1) { 
                $pr="<span class='label label-default' style='text-decoration:line-through;'>$".number_format($price,2)."</span>"; 
            } else {
                $pr="<span class='label label-info'>$".number_format($price,2)."</span>"; 
            }
        }

        // skip paid plugins on ombutel systems
        if($config_engine=='ombutel' && $price>0 && $superversionlocal<=0) { continue; }

        echo "<div class='row' style='background-color:#ddd; margin:0; padding:10px;'>\n";
        echo "<div class='col-md-1'>$pr</div>\n";
        echo "<div class='col-md-3'><strong>$name</strong>";
        if($help<>"") {
            echo " &nbsp; <a href='$help'><i class='fa fa-external-link' aria-hidden='true'></i></a>";
        }
        echo "</div>\n";
        echo "<div class='col-md-2'>";
        if($installed_version<>'-') {  
            echo "<span class='label label-info'>$installed_version</span>";
        } else {
            echo $installed_version;
        }
        echo "</div>\n";
        echo "<div class='col-md-2'>$plugversion</div>\n";
        echo "<div class='col-md-2'><input id='global_".$rawname."' type='checkbox' $checked $disabled onclick='setGlobalPlugin(this);'/></div>\n";
        echo "<div class='col-md-2'>$status</div>\n"; 
        echo "</div>\n";

        if($superversionlocal>0) {    // Esta intalado
            $configparams = plugin_read_xml_params($rawname);
            if(count($configparams)>0) {  // Tiene parametros de configuracion

                echo "<div class='row' style='background-color:#B4DBB3; margin:0; padding:10px;'>\n";
                echo "<div class='col-md-10'>$description</div>";
                echo "<div class='col-md-2 text-right'>";
                echo "<button type='button' class='btn btn-info ttip col-md-12' ";
                echo "data-toggle='popover' data-trigger='hover' data-placement='bottom' data-content='";
                echo __('Hide/Show Plugin Settings'); 
                echo "' onclick='toggleconfig(\"".$rawname."\")'>";
                echo "<i class='fa fa-cog pull-left'>";
                echo "</i>&nbsp;".__('Settings')."</button>";
                echo "</div>";
                echo "</div>";

                echo "<div class='row' style='background-color: #B1D1BC; margin:0; padding:10px; display:none' id='config_$rawname'><div class='col-md-12'>\n"; 
                echo fop2_print_config_form($rawname);
                echo "</div></div>\n";
            } else {
                echo "<div class='row' style='margin:0; padding:10px; background-color:#B4DBB3;'><div class='col-md-12'><span>$description</span><br/>";

                if($changelog<>'' && $show_changelog==1) {
                    echo "<a class='ttip' id='chlogbtn$rawname' href='javascript:void(0);' onclick='togglechangelog(\"$rawname\")' data-toggle='popover' data-trigger='hover' data-placement='bottom' data-content='";
                    echo __('Show Changelog');
                    echo "'>".__('Changelog')."<span class='fa fa-angle-down' id='chlogicon$rawname'></span></a>&nbsp;<br>";
                    echo "<div id='changelog$rawname' class='panel panel-body' style='display:none; font-size: 0.8em;'>$changelog</div>";
                }
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<div class='row' style='margin:0; padding:10px; background-color:#B4DBB3;'><div class='col-md-12'><span>$description</span><br/>";

            if($changelog<>'' && $show_changelog==1) {
                echo "<a class='ttip' id='chlogbtn$rawname' href='javascript:void(0);' onclick='togglechangelog(\"$rawname\")' data-toggle='popover' data-trigger='hover' data-placement='bottom' data-content='";
                echo __('Show Changelog');
                echo "'>".__('Changelog')."<span class='fa fa-angle-down' id='chlogicon$rawname'></span></a>&nbsp;<br>";
                echo "<div id='changelog$rawname' class='panel panel-body' style='display:none; font-size: 0.8.em;'>$changelog</div>";
            } 
            echo "</div>";
            echo "</div>";
        }

        echo "<div class='row' style='height:1em;'></div>";
    }
    echo "</div></div>\n";
} else {
    echo "<div class='alert alert-danger'>";
    echo __('Online Repository not reachable/accessible');
    echo "</div>";
}

if(!isset($plugin_online)) { $plugin_online=array(); }

$plugname_online=array();
foreach ($plugin_online as $idx=>$arrdata) {
    $plugname_online[$arrdata['rawname']]=$arrdata['version'];
}
$plugin_local = array_keys($plugin_local);
$plugname_online = array_keys($plugname_online);
$locally_installed_not_online = array_diff($plugin_local, $plugname_online);

if(count($locally_installed_not_online)>0) {
    echo "<div id='modulelistlocal'><div class='category' id='category_Local'><h3> ".__("Locally Available Plugins")."</h3>\n";
?>
<div>
<div class='row head' style='background-color:#f2f3f4; margin:0; padding:10px; z-index:2;' id='tableheadlocal'>
<div class='col-md-4'><?php echo __('Name');?></div>
<div class='col-md-2'><?php echo __('Installed Version');?></div>
<div class='col-md-2'><?php echo __('Available Version');?></div>
<div class='col-md-2'><?php echo __('Globally Enabled');?></div>
<div class='col-md-2'>&nbsp;</div>
</div>
</div>
<?php
    asort($locally_installed_not_online);

	foreach ($locally_installed_not_online as $idx=>$rawname) {
        
        $infoxml   = substr(escapeshellarg($PLUGIN_DIR."$rawname/plugin.xml"),1,-1);

        if(is_readable($infoxml)) {
            $pluginxml = file_get_contents($infoxml);

            //$xml       = new xml2Array();
            //$xmlarray  = $xml->parseAdvanced($pluginxml);
            //$data      = $xmlarray['plugin'];
            $xml  = simplexml_load_string($pluginxml);
            $data = simple_xml_to_array($xml);
            if(!isset($data['global'])) { $data['global']=array(); }
            $global = (is_array($data['global']) && count($data['global']==0))?'no':$data['global'];
        } else {
            $data['rawname']     = $rawname;
            $data['name']        = $rawname;
            $data['version']     = $plugversion;
            $data['description'] = '';
        }
        $rawnamedb     = $db->escape_string($data['rawname']);
        $namedb        = $db->escape_string($data['name']);
        $versiondb     = $db->escape_string($data['version']);
        $descriptiondb = $db->escape_string($data['description']);

        $description   = $data['description'];
        if(isset($data['description-'.$_COOKIE['lang']])) {
            $description = $data['description-'.$_COOKIE['lang']];
            $description = str_replace(array("\0", "\n", "\t", "\r"), '', $description);
        }

        if(isset($plugin_global[$data['rawname']]) || $global=='yes') { $checked=' checked '; } else { $checked=''; }
        if($global=='yes') { $disabled='disabled=disabled'; } else { $disabled=''; }

        $results = $db->consulta( "INSERT INTO fop2plugins (rawname, name, version, description) values ('".$rawnamedb."','".$namedb."','".$versiondb."','".$descriptiondb."') ON DUPLICATE KEY UPDATE rawname='$rawnamedb', name='$namedb', version='$versiondb', description='$descriptiondb'");

        echo "<div class='row' style='background-color:#ddd; margin:0; padding:10px;'>\n";
        echo "<div class='col-md-4'><strong>".$data['name']."</strong></div>";
        echo "<div class='col-md-2'><span class='label label-info'>".$data['version']."</span></div>";
        echo "<div class='col-md-2'>-</div>";
        echo "<div class='col-md-2'><input id='global_".$rawname."' type='checkbox' $checked $disabled onclick='setGlobalPlugin(this);'/></div>";
        echo "<div class='col-md-2 text-right'>&nbsp;</div>";
        echo "</div>";

       $configparams = plugin_read_xml_params($rawname);
       if(count($configparams)>0) {  // Tiene parametros de configuracion

           echo "<div class='row' style='background-color:#B4DBB3; margin:0; padding:10px;'>\n";
           echo "<div class='col-md-10'>$description</div>";
           echo "<div class='col-md-2 text-right'>";
           echo "<button type='button' class='btn btn-info ttip col-md-12' ";
           echo "data-toggle='popover' data-trigger='hover' data-placement='bottom' data-content='";
           echo __('Hide/Show Plugin Settings');
           echo "' onclick='toggleconfig(\"".$rawname."\")'>";
           echo "<i class='fa fa-cog pull-left'>";
           echo "</i>".__('Settings')."</button>";
           echo "</div>";
           echo "</div>";

           echo "<div class='row' style='background-color: #B1D1BC; margin:0; padding:10px; display:none' id='config_$rawname'><div class='col-md-12'>\n";
           echo fop2_print_config_form($rawname);
           echo "</div></div>\n";
       } else {
           echo "<div class='row' style='margin:0; padding:10px; background-color:#B4DBB3;'><div class='col-md-12'>$description</div>";
           echo "</div>";
       }

       echo "<div class='row' style='height:1em;'></div>";
    }

}
?>
  </div>
</div>
<hr style='color: #bbb; background-color: #bbb; height: 2px;'/>
<div id='uploadplugin'>
  <div class='category' id='category_upload'>
  <h3> <?php echo __("Upload Plugin"); ?></h3>
<p><?php echo __('You can upload a tar gzip file containing a panel plugin from your local system. If a plugin with the same name already exists, it will be overwritten.'); ?></p>
<br/>
<form class='form-horizontal' name="plugin-upload" action="<?php echo SELF;?>" method="post" enctype="multipart/form-data">
<input type="hidden" name="action" value="upload" />
<input type="file" class='control-form' name="uploadplug" /> &nbsp;&nbsp; <button class='btn btn-default' type="submit"><i class='fa fa-upload pull-left'></i>&nbsp;<?php echo __('Upload');?></button>
</form>
  </div>
</div>
</div>
<br/>
<br/>
<script>
$(document).ready(function() {
    iseditor = new Object();

    $('input[type=file]').bootstrapFileInput();
    $('.file-input-wrapper').find('span').html('<i class="fa fa-list-alt pull-left"></i>&nbsp;<?php echo __('Browse');?>');

<?php
if(isset($_SESSION[MYAP]['needsreload'])) {
?>
    $('#fop2reload').show();
<?php
}
?>

});

function setUninstall(rawname) {
     alertify.confirm('','<?php echo __('Are you sure?'); ?>', function(e) {
        $('#delete_'+rawname).submit();
     }, function() { 
        // cancel
     }).set({
        labels: {
            ok: '<?php echo __('Accept');?>',
            cancel: '<?php echo __('Cancel');?>'
        },
        closable: false
     });
}

function getPlugin(plugin) {
    $('#statusmodal').modal();
    var mydata = "action=getplugin&itemid="+plugin;
    $.post(window.location.href, mydata, function(data) {
        if(data.indexOf('ERROR')>=0) {
            $('#statusmodal').modal('hide');
            if(data.indexOf('ERROR1')>=0) {
                error = data.substr(7);
                alertify.error('<?php echo __("Error extracting plugin archive");?> ('+error+')');
            }
        } else {
            alertify.success('<?php echo __("Plugin installed successfully. You need to enable it globally or assign it to some users to make it available."); ?>');
        }
        $('#statusmodal').modal('hide');
        window.location = window.location.href;
    });
}

function toggleconfig(rawname) {
    $('#config_'+rawname).toggle();

    $('textarea[data-format]').each(function() {
      var codemode = $(this).attr('data-format');
      if(codemode=='html') { codemode='htmlmixed'; }
      if(typeof(iseditor[$(this).attr('id')]) == 'undefined') { 
          iseditor[$(this).attr('id')] = CodeMirror.fromTextArea($(this)[0],{ lineNumbers: true,   mode: codemode});
      }
    });
 
}

function togglechangelog(rawname) {
    $('#changelog'+rawname).slideToggle("slow", function() {
    if($('#changelog'+rawname).is(':visible')) {
        $('#chlogicon'+rawname).removeClass('fa-angle-down').addClass('fa-angle-up');;
        $('#chlogbtn'+rawname).attr('data-content','<?php echo __('Hide Changelog');?>');
    } else {
        $('#chlogicon'+rawname).removeClass('fa-angle-up').addClass('fa-angle-down');;
        $('#chlogbtn'+rawname).attr('data-content','<?php echo __('Show Changelog');?>');
    }
    });
}

$(".main a[href^='http://']").attr("target","_blank");

</script>

<div id='statusmodal' class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php echo __('Please wait...');?></h3>
            </div>
            <div class="modal-body">
                <div class="progress progress-striped active">
                    <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="1000" aria-valuemin="0" aria-valuemax="100" style="width: 100%">
               </div>
           </div>
       </div>
    </div>
</div>

<div class="push"></div>
</div>
<?php
include("footerbs.php");
