<?php
require_once("config.php");
require_once("functions.php");
require_once("system.php");
require_once("dblib.php");
require_once("asmanager.php");
require_once("dbconn.php");
require_once("parsecsv.lib.php");
require_once("secure/secure-functions.php");
require_once("secure/secure.php");

if(!isset($BUTTONS_PER_PAGE)) { $BUTTONS_PER_PAGE=150; }

$per_page=$BUTTONS_PER_PAGE;

if(!isset($_GET['page'])) {
    include("headerbs.php");
    echo "<div class='wrap'>\n";
    include("menu.php");
}
$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$itemid = isset($_REQUEST['itemid'])?$_REQUEST['itemid']:'';
$dispnum = "fop2buttons";

$messages = array();
$errors   = array();
$plugin_button_section = '';
$sorting  = "";

$callbacks = fop2_get_plugins_callback();
foreach($callbacks as $rawname=>$file) {
    include($file);
}

function plugins_buttons_callback($action,$data) {
    global $callbacks;
    foreach($callbacks as $rawname=>$ff) {
        $fname = "${rawname}_{$action}";
        if(is_callable($fname)) {
            call_user_func($fname,$_POST);
        }
    }
}

//if submitting form, update database
switch ($action) {
case "edit":
    $result = fop2_buttonsedit($_POST);
    plugins_buttons_callback($action,$_POST);
    break;
case "saveField":
    $result = fop2_fieldedit($_POST);
    plugins_buttons_callback($action,$_POST);
    if($result==0) { die("ERROR"); } else { die("$result"); }
    break;
case "refresh":
    $result = fop2_buttonsrefresh($_POST);
    plugins_buttons_callback($action,$_POST);
    break;
case "sortname":
    $sorting="name";
    $_SESSION[MYAP]['needsreload']=1;
    break;
case "sortnumber":
    $sorting="number";
    $_SESSION[MYAP]['needsreload']=1;
    break;
case "sort":
    fop2_set_button_order($_REQUEST);
    $_SESSION[MYAP]['needsreload']=1;
    die();
    break;
case "import":
    pre_process_csv($_REQUEST);
    break;
case "doimport":
    $howmanyupdated = mass_import($_REQUEST);
    if($howmanyupdated>0) {
        $messages[]=sprintf(__('%d buttons updated'), $howmanyupdated);
    }
    break;
}


$fop_buttons    = fop2_all_buttons();
$system_buttons = system_all_buttons();
if(!isset($fop_buttons)) { $fop_buttons = array(); }
if(!isset($system_buttons)) { $system_buttons = array(); }

$system_buttons_context = array();
foreach($system_buttons as $key=>$arradata) {
    if($arradata['context_id']==$panelcontext) {
        $system_buttons_context[$key]=1;
    }
}

$del_buttons = array_diff(array_keys($fop_buttons),array_keys($system_buttons_context));
$add_buttons = array_diff(array_keys($system_buttons_context),array_keys($fop_buttons));

// Remove buttons that are not in the system anymore
$contdel=0;
if(!isset($del_buttons)) { $del_buttons = array(); }
foreach($del_buttons as $chan) {
    $result = $db->select('id,exten','fop2buttons','',"device='$chan'");
    $id     = $result[0]['id'];
    $exten  = $result[0]['exten'];
    fop2_del_button($id,$exten);
    $contdel++;
}

if(!isset($add_buttons)) { $add_buttons = array(); }
$contadd=0;
foreach($add_buttons as $chan=>$dat) {
    $det = $system_buttons[$dat];
    fop2_add_button($det);
    $contadd++;
}

$need_update = 0;

if($contdel>0) {
   $messages[]=sprintf(__('%d buttons removed from system update'), $contdel);
   $need_update=1;
}

if($contadd>0) {
   $messages[]=sprintf(__('%d buttons added from system update'), $contadd);
   $need_update=1;
}

if($config_engine=='freepbx') {
    // If we are on FreePBX, then update queuechannel and mailbox automagically
    foreach($system_buttons as $chan=>$dat) {
        if($arradata['context_id']<>$panelcontext) {
            continue;
        }
        if($dat['type']=='extension') {
            if(!isset($fop_buttons[$chan]['queuechannel'])) { $fop_buttons[$chan]['queuechannel']=''; }
            if(!isset($fop_buttons[$chan]['mailbox'])) { $fop_buttons[$chan]['mailbox']=''; }
            $fop2qchan = $fop_buttons[$chan]['queuechannel'];
            $fop2mbox  = $fop_buttons[$chan]['mailbox'];
            if(isset($dat['queuechannel'])) {
                if($fop2qchan <> $dat['queuechannel']) {
                    $query = "UPDATE fop2buttons SET queuechannel='%s' WHERE device='%s'";
                    $db->consulta($query,array($dat['queuechannel'],$chan));
                    $need_update=1;
                }
            }
            if(isset($dat['mailbox'])) {
                if($fop2mbox <> $dat['mailbox']) {
                    $query = "UPDATE fop2buttons SET mailbox='%s' WHERE device='%s'";
                    $db->consulta($query,array($dat['mailbox'],$chan));
                    $need_update=1;
                }
            }
        }
    }
}

// If system buttons information changed from what we have, then load the array
// again with the modifications.

if($need_update==1) {
   $fop_buttons    = fop2_all_buttons();
}

$fopsortedbuttons = array();
foreach ($fop_buttons as $devname => $datarray) {
    if(!isset($datarray['sortorder'])) { $datarray['sortorder']=0; }
    if(!isset($datarray['type'])) { continue; }

    if($sorting=="name") {
        $fopsortedbuttons[$devname]=strtolower($datarray['type'].$datarray['name']);
    } elseif($sorting=="number") {
        $fopsortedbuttons[$devname]=$datarray['type'].$datarray['exten'];
    } else {
        $fopsortedbuttons[$devname]=$datarray['type'].$datarray['sortorder'];
    }
}

uasort($fopsortedbuttons, "natural_sort");

$final_fop_buttons=array();
foreach($fopsortedbuttons as $devname => $orden) {
   $final_fop_buttons[$devname]=$fop_buttons[$devname];
}

// If a sort button was pressed, proceed to the actual sorting of DB entries in the fop2buttons table
if($sorting <> "") {
    $veri = array();
    $conti = array();
    foreach($final_fop_buttons as $device => $datarray) {
        if(!isset($conti[$datarray['type']])) { $conti[$datarray['type']]=1; } else { $conti[$datarray['type']]++;}
        $veri[$datarray['type']][$conti[$datarray['type']]]=$datarray['exten'];
    }
    foreach($veri as $dtype=>$ddata) {
        $vari = array();
        foreach($ddata as $idxorder=>$actualexten) {
           $vari['listitem'][$idxorder]=$dtype."!".$actualexten;
        }

        fop2_set_button_order($vari);
    }
}

$buttypes = array('extensions','queues','conferences','trunks','parks','ringgroups');

$privacyoptions = array("none","all","clid","monitor");

foreach ($buttypes as $tipo) {
    $tipocorto = substr($tipo,0,-1);
    if(!isset($cuantos[$tipocorto])) { $cuantos[$tipocorto] = 0; }
}

foreach ($final_fop_buttons as $devname => $datarray) {
    if(!isset($cuantos[$datarray['type']])) { $cuantos[$datarray['type']] = 0; }
    $cuantos[$datarray['type']]++;
}


if(isset($_GET['page'])) {
    print_section('extension','extensions',$cuantos['extension'],$_GET['page']);
    die();
}


?>

<div class='row' style='background-color:#78a300; padding-bottom:10px;'>
<div class="content">
<div class="col-md-4">
<span class='h2'><?php echo __('Buttons');?></span>
<i style='vertical-align:super; top:-5px; color:#333;' class='ttip fa fa-info-circle' data-toggle='popover' data-trigger='hover' data-placement='bottom' data-content='<?php echo __('You can change button labels and privacy options -to prevent the button to be monitored and/or hide the CallerID- among other things. You can also sort the list by dragging elements up and down or using the Sort options in the Actions menu.'); ?>'></i>
</div>
<div class="col-md-8 text-right">


<div class="btn-group">
<button type="button" onclick="toggleAdvanced(); return false;" class="btn btn-default"><i class='fa fa-wrench'></i> <?php echo __('Toggle Advanced Fields');?></button>
</div>


</div>
</div>
</div>

<form autocomplete="off" name="edit" action="<?php echo SELF ?>" method="post" onsubmit="return edit_onsubmit();">
<input type="hidden" name="action"   value="edit">

<div id='btncontainer' class='hidden'>

<?php

foreach ($buttypes as $tipo) {

    $catname=ucfirst($tipo);
    $tipocorto = substr($tipo,0,-1);

    if($cuantos[$tipocorto]<=0) { continue; }

    echo "<div id='head_$tipocorto' class='row head' style='background-color:#88b310; margin:10px 0 0 0; padding:5px; z-index:3;'>\n";
    echo "<div class='col-md-12'>\n";
    echo "<span class='h3'>".__($catname)."</span>";
    echo "</div>\n";
    echo "</div>\n";

    echo "<div id='container_${tipocorto}'>";
    print_section($tipocorto,$tipo,$cuantos[$tipocorto]);
    echo "</div>\n";
    if($tipocorto=='extension') {
        echo "<div id='spinner' class='spinner'> <div class='cube1'></div> <div class='cube2'></div> </div>";
    }
}

function print_section($tipocorto, $tipo, $cuantos, $page=1) {

    global $final_fop_buttons, $privacyoptions, $per_page, $plugin_button_section;

    if($page==0) {$page=1; }
    $nextpage = $page+1;
    $offset = ($page * $per_page) - $per_page;
    $page--;
    $total_pages  = ceil($cuantos / $per_page);
    $first_record = $per_page * $page;
    $last_record  = $first_record + $per_page; 

    $conta=0;

//    echo "pages $total_pages, first record $first_record, last record $last_record, current page $page<br>";
    echo "<table class='fop2 table' id='sorteame_${tipocorto}' offset='$offset'>\n";
    echo "<tbody>\n";

    foreach ($final_fop_buttons as $devname => $datarray) {

        if(strtolower($datarray['type'])==strtolower($tipocorto)) {

            $conta++;

            if($conta<=$first_record) { continue; }

            $myitemid = $datarray['exten'];
            $myitemid = preg_replace("/OUT_/","trunkout",$myitemid);

            echo "<tr id='listitem_".$myitemid."'>\n";
            echo "<td>";

            if($tipo=="extensions") {

                echo "<div class='row'>\n";

                echo "<div class='col-md-2'>\n";
                // Numero de Extension - Campo no editable
                echo "<div class='h4'>";
                echo ($datarray['exten']==-1?'n/a':$datarray['exten']);
                echo "</div>\n";
                echo "</div>\n";

                echo "<div class='col-md-4'>\n";
                // Label
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('You can change the button label to be displayed on the panel.')."'>".__('Label')."</a><br/><input type=text id='name_".$datarray['devid']."' name='name_".$datarray['devid']."' class='editable form-control' value=\"".$datarray['name']."\" ><br/>\n";
                echo "</div>\n";

                echo "<div class='col-md-4'>\n";
                // Email
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('You can set an email for every button to enable the option to email the user directly from the panel.')."'>".__('Email')."</a><br/><input type=text name='email_".$datarray['devid']."' value='".$datarray['email']."' class='editable form-control' id='email_".$datarray['devid']."'>\n";
                echo "</div>\n";

                echo "<div class='col-md-2'>\n";
                // Privacy
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('This option can be used to disable monitoring or CallerID display on the button.')."'>".__('Privacy')."</a><br/><select name='privacy_".$datarray['devid']."' class='chosen-select-100 form-control editable' id='privacy_".$datarray['devid']."'>";
                foreach($privacyoptions as $valor) {
                    echo "<option value='$valor' ";
                    if($datarray['privacy']==$valor) echo " selected ";
                    echo ">$valor</option>\n";
                }
                echo"</select>";
                echo "</div>\n";

                echo "</div> <!-- end row-->\n";

                echo "<div class='row'>\n";

                echo "<div class='col-md-2 col-md-offset-2'>\n";
                // Group
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content=\"".__('You can group extensions visually on the panel page. If no group is specified all the extensions are going to be displayed on the Extensions box. If you type another name, a new box will be added with the name you choose. Any buttons with the same group name will be displayed on that box.')."\">".__('Group')."</a><br/><input type=text class='editable form-control' id='group_".$datarray['devid']."' name='group_".$datarray['devid']."' value='".$datarray['group']."'><br/>\n";
                echo "</div>\n";

                echo "<div class='col-md-2'>\n";
                // External Transfer
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('number@context for external/mobile transfers. If @context is omited it will use the default one for the extension.')."'>".__('External Transfer')."</a><br/><input type=text name='external_".$datarray['devid']."' value='".$datarray['external']."' class='editable form-control' id='external_".$datarray['devid']."'>\n";
                echo "</div>\n";

                echo "<div class='col-md-2'>\n";
                // Tags
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('Comma separated lists of tags. They will be searched/matched when you use the Filter input box on the panel.')."'>".__('Tags')."</a><br/><input type=text name='tags_".$datarray['devid']."' value='".$datarray['tags']."' class='editable form-control' id='tags_".$datarray['devid']."'>\n";
                echo "</div>\n";

                echo "<div class='col-md-2'>\n";
                // Custom ASTDB
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('ASTDB key to search for custom states. Default configuration is for displaying Call Forward Unconditional states.')."'>".__('Custom ASTDB')."</a><br/><input type=text name='customastdb_".$datarray['devid']."' value='".$datarray['customastdb']."' class='editable form-control' id='customastdb_".$datarray['devid']."'>\n";
                echo "</div>\n";

                echo "<div class='col-md-2'>\n";
                // Campo Enable/Disabled
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-placement='top' ";
                echo "data-trigger='hover' data-content='".__('Enable or disable this button from the panel view.')."'>".__('Enabled');
                echo "</a> <br/>";
                echo "<input type='checkbox' data-on-text='".__('No')."' data-off-text='".__('Yes')."' ";
                echo "data-off-color='success' data-on-color='danger' ";
                if($datarray['exclude']==1) { echo " checked "; }
                echo " name='excludebot_".$datarray['devid']."' id='excludebot_".$datarray['devid']."' class='chk editable' />\n";
                echo "</div>\n";

                echo "</div> <!-- end row-->\n";

                echo "<div class='row'>\n";

                echo "<div class='col-md-2 col-md-offset-2 advanced'>\n";
                // Extra Channel
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('Extra Channel. You can use it to add extra channels to affect the status of this button.')."'>".__('Extra Channel')."</a><br/><input type=text name='channel_".$datarray['devid']."' value='".$datarray['channel']."' class='editable form-control' id='channel_".$datarray['devid']."'><br/>\n";
                echo "</div>\n";

                echo "<div class='col-md-2 advanced'>\n";
                // Queue Context
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('Context to match Local queue members of type Local/exten@context and relate them to extension buttons via the extension.')."'>".__('Queue Context')."</a><br/><input type=text class='editable form-control' name='queuecontext_".$datarray['devid']."' value='".htmlspecialchars($datarray['queuecontext'])."' id='queuecontext_".$datarray['devid']."'>\n";
                echo "</div>\n";

                echo "<div class='col-md-2 advanced'>\n";
                // Extenvoicemail
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('Voicemail extension in order to transfer directly to voicemail (extenvoicemail).')."'>".__('Extension to Voicemail')."</a><br/><input type=text class='editable form-control' name='extenvoicemail_".$datarray['devid']."' value='".$datarray['extenvoicemail']."' id='extenvoicemail_".$datarray['devid']."'>\n";
                echo "</div>\n";

                echo "<div class='col-md-2 advanced'>\n";
                // Mailbox
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('Voicemail mailbox to query for new voicemail.')."'>".__('Mailbox')."</a><br/><input type=text class='editable form-control' name='mailbox_".$datarray['devid']."' value='".$datarray['mailbox']."' id='mailbox_".$datarray['devid']."'>\n";
                echo "</div>\n";

                echo "<div class='col-md-2 advanced'>\n";
                // Chanspy Options
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('Options to pass to chanspy. Can be used to restrict spying to certain groups (option g, SPYGROUP) or silence the beep (option q). Example: qg(SALES)')."'>".__('ChanSpy Options')."</a><br/><input type=text name='spyoptions_".$datarray['devid']."' value='".$datarray['spyoptions']."' class='editable form-control' id='spyoptions_".$datarray['devid']."'>\n";
                echo "</div>\n";

                echo "</div> <!-- end row-->\n";

                echo "<div class='row'>\n";

                echo "<div class='col-md-2 col-md-offset-2 advanced'>\n";
                // Originate Channel
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('Channel to use as originator when performing a dial from the panel. You can use a Local type channel and use sip headers for setting the phone to auto answer.')."'>".__('Originate Channel')."</a><br/><input type=text name='originatechannel_".$datarray['devid']."' value='".$datarray['originatechannel']."' class='editable form-control' id='originatechannel_".$datarray['devid']."'><br/>\n";
                echo "</div>\n";

                echo "<div class='col-md-2 advanced'>\n";
                // Context
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('Dialplan context where this extension can be reached')."'>".__('Context')."</a><br/><input type=text name='context_".$datarray['devid']."' value='".$datarray['context']."' class='editable form-control' id='context_".$datarray['devid']."'><br/>\n";
                echo "</div>\n";

                echo "<div class='col-md-4 advanced'>\n";
                // Queue Channel
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('Channel to use as queue member when adding/removing/pausing this button into a queue.')."'>".__('Queue Channel')."</a><br/><input type=text class='editable form-control' name='queuechannel_".$datarray['devid']."' value='".htmlspecialchars($datarray['queuechannel'])."' id='queuechannel_".$datarray['devid']."'>\n";
                echo "</div>\n";

                echo "<div class='col-md-2 advanced'>\n";
                // CSS Class
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('CSS Class to apply to the button, so you can modify the style for an individual button via css. You will have to add your classes to css/theme/theme.css')."'>".__('CSS Class')."</a><br/><input type=text name='cssclass_".$datarray['devid']."' value='".$datarray['cssclass']."' class='editable form-control' id='cssclass_".$datarray['devid']."'>\n";
                echo "</div>\n";

                echo "</div> <!-- end row-->\n";

                echo "<div class='row'>\n";

                echo "<div class='col-md-4 advanced col-md-offset-2'>\n";
                // Originate variables
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('Variables to send to the Asterisk Manager when issuing Originate commands (performing a dial from the panel).')."'>".__('Originate Variables')."</a><br/><input type=text name='originatevariables_".$datarray['devid']."' value='".$datarray['originatevariables']."' class='editable form-control' id='originatevariables_".$datarray['devid']."'><br/>\n";
                echo "</div>\n";

                echo "<div class='col-md-4 advanced'>\n";
                // AutoAnswer Header
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('Variable to send to the Asterisk Manager when issuing Originate commands specific for setting a phone to auto answer via sip headers or similar. This will be used if the user toggles auto answer in its own Preferences setting')."'>".__('AutoAnswer Header')."</a><br/><input type=text name='autoanswerheader_".$datarray['devid']."' value='".$datarray['autoanswerheader']."' class='editable form-control' id='autoanswerheader_".$datarray['devid']."'><br/>\n";
                echo "</div>\n";

                echo "<div class='col-md-2 advanced'>\n";
                // Accountcode
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('Accountcode to use when Originating calls with this extension from the panel.')."'>".__('Accountcode')."</a><br/><input type=text name='accountcode_".$datarray['devid']."' value='".$datarray['accountcode']."' class='editable form-control' id='accountcode_".$datarray['devid']."'>\n";
                echo "</div>\n";


                echo "</div> <!-- end row-->\n";


                echo "<div class='row'>\n";

                echo "<div class='col-md-4 advanced col-md-offset-2'>\n";
                // SIP Username
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('SIP Username for use with web based phone.')."'>".__('SIP Username')."</a><br/><input type=text name='sip_username_".$datarray['devid']."' value='".$datarray['sip_username']."' class='editable form-control' id='sip_username_".$datarray['devid']."'><br/>\n";
                echo "</div>\n";

                echo "<div class='col-md-4 advanced'>\n";
                // SIP Password
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('SIP Account Password')."'>".__('SIP Password')."</a><br/><input type=text name='sip_password_".$datarray['devid']."' value='".$datarray['sip_password']."' class='editable form-control' id='sip_password_".$datarray['devid']."'><br/>\n";
                echo "</div>\n";

                echo "<div class='col-md-2 advanced'>\n";
                echo "</div>\n";

                echo "</div> <!-- end row-->\n";

                // replace all variables #{name} with corresponding datarray[name] value
                $plugin_section_print = preg_replace_callback('/#{([^}]*)}/',function($matches) use ($datarray) {  return $datarray[$matches[1]]; },$plugin_button_section);
                // show custom fields for button edit from plugins
                echo $plugin_section_print;


            } else if($tipo=="trunks") {

                    echo "<div class='row'>\n";

                    echo "<div class='col-md-2'>\n";
                    // Numero de Extension - Campo no editable
                    echo "<div class='h4'>";
                    echo ($datarray['exten']==-1?'n/a':$datarray['exten']);
                    echo "</div>\n";
                    echo "</div>\n";

                    echo "<div class='col-md-2'>\n";
                    // Label
                    echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('You can change the button label to be displayed on the panel.')."'>".__('Label')."</a><br/><input type=text id='name_".$datarray['devid']."' name='name_".$datarray['devid']."' class='editable form-control' value=\"".$datarray['name']."\" ><br/>\n";
                    echo "</div>\n";

                    echo "<div class='col-md-2'>\n";
                    //Privacy
                    echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('This option can be used to disable monitoring or CallerID display on the button.')."'>".__('Privacy')."</a><br/><select name='privacy_".$datarray['devid']."' class='chosen-select-100 form-control editable' id='privacy_".$datarray['devid']."'>\n";
                    foreach($privacyoptions as $valor) {
                        echo "<option value='$valor' ";
                        if($datarray['privacy']==$valor) echo " selected ";
                        echo ">$valor</option>\n";
                    }
                    echo"</select>";

                    echo "</div>\n";
                    if (preg_match("/^dahdi/i",$datarray['device']) or preg_match("/^zap/i",$datarray['device'])) {
                        echo "<div class='col-md-4'>";
                        echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('You must specify the channel number range for zap/dahdi trunks in this field.')."'>";
                        echo __('Zap/Dahdi Channels (Eg: 1-24)');
                        echo "</a>";
                        echo "<br/><input type=text name='email_".$datarray['devid']."' value='".$datarray['email']."' class='editable form-control' id='email_".$datarray['devid']."'>\n";
                        echo "</div>\n";
                    } else {
                        echo "<div class='col-md-4'>";
                        echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('You might want to specify an additional device name or put the correct device name in case you are using custom trunks.')."'>";
                        echo __('Extra Channel');
                        echo "</a>";
                        echo "<br/><input type=text name='channel_".$datarray['devid']."' value='".$datarray['channel']."' class='editable form-control' id='channel_".$datarray['devid']."'>\n";
                        echo "</div>\n";
                    }

                    echo "<div class='col-md-2'>\n";
                    // Campo Enable/Disabled
                    echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-placement='top' ";
                    echo "data-trigger='hover' data-content='".__('Enable or disable this button from the panel view.')."'>".__('Enabled');
                    echo "</a> <br/>";
                    echo "<input type='checkbox' data-on-text='".__('No')."' data-off-text='".__('Yes')."' ";
                    echo "data-off-color='success' data-on-color='danger' ";
                    if($datarray['exclude']==1) { echo " checked "; }
                    echo " name='excludebot_".$datarray['devid']."' id='excludebot_".$datarray['devid']."' class='chk editable' />\n";
                    echo "</div>\n";

                    echo "</div> <!-- end row -->\n";
            } else if($tipo=="queues" || $tipo=="ringgroups") {

                echo "<div class='row'>\n";
                echo "<div class='col-md-2'>\n";
                // Numero de Extension - Campo no editable
                echo "<div class='h4'>";
                echo ($datarray['exten']==-1?'n/a':$datarray['exten']);
                echo "</div>\n";
                echo "</div>\n";

                echo "<div class='col-md-4'>\n";
                // Label
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('You can change the button label to be displayed on the panel.')."'>".__('Label')."</a><br/><input type=text id='name_".$datarray['devid']."' name='name_".$datarray['devid']."' class='editable form-control' value=\"".$datarray['name']."\" ><br/>\n";
                echo "</div>\n";

                echo "<div class='col-md-2'>\n";
                // Label
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('Voicemail mailbox to query for new voicemail.')."'>".__('Mailbox')."</a><br/><input type=text class='editable form-control' name='mailbox_".$datarray['devid']."' value='".$datarray['mailbox']."' id='mailbox_".$datarray['devid']."'>\n";
                echo "</div>\n";

                if($tipo=="queues") {
                    echo "<div class='col-md-2'>\n";
                    echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content=\"".__('You can group queues visually on the panel page. If no group is specified all the queues are going to be displayed on the Queues box. If you type another name, a new box will be added with the name you choose. Any buttons with the same group name will be displayed on that box.')."\">".__('Group')."</a><br/><input type=text class='editable form-control' id='group_".$datarray['devid']."' name='group_".$datarray['devid']."' value='".$datarray['group']."'><br/>\n";
                    echo "</div>\n";

                echo "<div class='col-md-2'>\n";
                } else {
                    // ring group does not have group
                    echo "<div class='col-md-2 col-md-offset-2'>\n";
                }

                // Campo Enable/Disabled
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-placement='top' ";
                echo "data-trigger='hover' data-content='".__('Enable or disable this button from the panel view.')."'>".__('Enabled');
                echo "</a> <br/>";
                echo "<input type='checkbox' data-on-text='".__('No')."' data-off-text='".__('Yes')."' ";
                echo "data-off-color='success' data-on-color='danger' ";
                if($datarray['exclude']==1) { echo " checked "; }
                echo " name='excludebot_".$datarray['devid']."' id='excludebot_".$datarray['devid']."' class='chk editable' />";
                echo "</div>\n";

                echo "</div> <!-- end row -->\n";


            } else {
                echo "<div class='row'>\n";

                echo "<div class='col-md-2'>\n";
                // Numero de Extension - Campo no editable
                echo "<div class='h4'>";
                echo ($datarray['exten']==-1?'n/a':$datarray['exten']);
                echo "</div>\n";
                echo "</div>\n";

                echo "<div class='col-md-4'>\n";
                // Label
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='top' data-content='".__('You can change the button label to be displayed on the panel.')."'>".__('Label')."</a><br/><input type=text id='name_".$datarray['devid']."' name='name_".$datarray['devid']."' class='editable form-control' value=\"".$datarray['name']."\" ><br/>\n";
                echo "</div>\n";


                echo "<div class='col-md-2 col-md-offset-4'>\n";
                // Campo Enable/Disabled
                echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-placement='top' ";
                echo "data-trigger='hover' data-content='".__('Enable or disable this button from the panel view.')."'>".__('Enabled');
                echo "</a> <br/>";
                echo "<input type='checkbox' data-on-text='".__('No')."' data-off-text='".__('Yes')."' ";
                echo "data-off-color='success' data-on-color='danger' ";
                if($datarray['exclude']==1) { echo " checked "; }
                echo " name='excludebot_".$datarray['devid']."' id='excludebot_".$datarray['devid']."' class='chk editable' />";
                echo "</div>\n";

                echo "</div> <!-- end row -->\n";

            }

            echo "</td>\n";
            echo "</tr>\n";

            if($tipocorto=="extension" && $conta>=$last_record) {  break; }
        }
    }

    if($tipocorto=="extension" && $total_pages > 1) {
        echo "<tr><td style='text-align: right;'>";
        if($page>0) {
            echo "<a class='btn btn-default' href='#' onClick=\"getSection('container_${tipocorto}',$page); return false\">Previous</a> &nbsp; ";
        }
        if($nextpage-1 < $total_pages) {
            echo "<a class='btn btn-default' href='#' onClick=\"getSection('container_${tipocorto}',$nextpage); return false\">Next</a>";
        }
        echo "</td></tr>";
    }

    echo "</tbody>\n</table>\n";


}

?>
</div>
</form>
<div id='end'></div>

<script>
$(document).ready(function() {
<?php

if(count($messages)>0) {
    foreach($messages as $msg) {
        echo "alertify.success('$msg');\n";
    }
}

if(count($errors)>0) {
    foreach($errors as $id=>$dati) {
        echo "alertify.".$dati['kind']."('".$dati['message']."');\n";
    }
}


if(isset($_SESSION[MYAP]['needsreload'])) {
?>
    $('#fop2reload').show();
<?php
}

?>


});

function toggleAdvanced() {
    $(".advanced").toggle();
    $(document.body).trigger("sticky_kit:recalc");
}

</script>


       <div id='uploadcontainer' class="modal fade" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <div class='xcontent'><span class='fa fa-list-alt pull-left' style='padding: 7px 5px; font-size:1.5em;'></span><h3 class='modal-title uploadtitle'><?php echo __('Batch Update via CSV File'); ?></h3></div>
                    </div>
                    <div class="modal-body"><?php echo __('Select a CSV file to upload with data to perform the mass update. You must pass an extension field in the CSV file to match against the Button extension. For example:'); ?><br/><br/>
<pre>100,some@email.com,15552121,Sales
101,another@email.com,15553212,Sales
</pre>
                    <form method='post' enctype='multipart/form-data' id='formimport' name='formimport'><div id='csvfilename' class='label label-info'></div><div></div><div id='uploadbutton' class='btn btn-default'><input type="file" id="csvupload" name="csvupload"><?php echo __('Browse');?></div> <span id='xcsvfilename'></span><input type='hidden' name='action' value='import'><input type='submit' class='btn btn-primary' value='<?php echo __('Upload');?>'></input></form>
                    </div>
                </div>
            </div>
        </div>

<div id='ajaxstatus'><?php echo __('Saving...');?></div>
<div class="push"></div>
</div>
<?php
include("footerbs.php");

function pre_process_csv($req) {
    global $APPNAME, $version, $errors;

    $userdirectory="/tmp/";

    $arrFile = $_FILES['csvupload'];
    $file    = $arrFile['tmp_name'];

    if ($arrFile['size']>0 && !empty($file)) {
        if (is_uploaded_file($file)) {
        if (copy ($file, $userdirectory."massupdate.csv")) {
            $name_upload="massupdate.csv";
        }else{
            $errors[]=array('kind'=>'warning','message'=>__('Could not copy uploaded file'));
        }
        }else{
            $errors[]=array('kind'=>'warning','message'=>__('Could not upload file'));
        }
    }else{
        $errors[]=array('kind'=>'warning','message'=>__('Could not upload file'));
    }

    if($name_upload == "") {
        $errors[]=array('kind'=>'warning','message'=>__('Empty file?'));
    }

    if(count($errors)==0) { 
        // procesa csv

        // Edit uploaded file and remove empty lines if any as it will break csv processing
        file_put_contents($userdirectory."massupdate.notempty.csv",
            preg_replace(
                '~[\r\n]+~',
                "\r\n",
                trim(file_get_contents($userdirectory."massupdate.csv"))
            )
        );

        $csv = new parseCSV();
        $csv->heading = false;
        $csv->auto($userdirectory."massupdate.notempty.csv");
       
        $fields=array();
        $fields[''] = '';
        $fields['extension'] = 'Extension';
        $fields['email'] = 'Email Address';
        $fields['group'] = 'Group';
        $fields['external'] = 'Mobile Number';
        $fields['tags'] = 'Tag';
        $fields['accountcode'] = 'Accountcode';
        $fields['autoanswerheader'] = 'AutoAnswer header';
        $opt='';
        foreach($fields as $key=>$val) {
           $opt.="<option value='$key'>$val</option>\n";
        }
 
        $rows = count($csv->data[0]);
        $serialized = base64_encode(json_encode($csv->data));

echo "<div class='row' style='background-color:#78a300; padding-bottom:10px;'>
<div class='content'>
<div class='col-md-12'>
<h3>";
echo __("Batch Update");
echo "</h3></div></div></div>";
echo "<div class='row'>
<div class='acontent'>
<div class='col-md-12'>
";


        echo "<h4>";
        echo __('Please assign fields for your CSV file columns. The extension field is mandatory and will be used to match the corresponding extension in the Panel Buttons list. Columns with no assigned field will be discarded.');
        echo "</h4>";

        echo "<form method='post' id='massimport' onsubmit='return check_mass_import();'>";
        echo "<input type=hidden name=action value='doimport'>";
        echo "<input type=hidden name=data value='$serialized'>";
        echo "<table class='table'><tr>";
        for($a=0;$a<$rows;$a++) {
             echo "<td>";
             echo "<select class='form-control' name='row$a' id='row$a'>";
             echo $opt;
             echo "</select>";
             echo "</td>";
        }
        echo "</tr>";
        echo "<tr>";
 
        for($a=0;$a<$rows;$a++) {
             echo "<td>".$csv->data[0][$a]."</td>";
        }
        echo "</tr></table>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "<button class='btn btn-default' id='csvready'>".__('Submit Changes')."</button></form>";
        echo "</div>";
        include("footerbs.php");
        exit;
    }
}

function mass_import($req) {
     global $db;
     $count=0;
     $data = base64_decode($req['data']);
     foreach($req as $key=>$val) {
         if(substr($key,0,3)=='row') {
             if($val<>'') {
                 $keyid = substr($key,3);
                 $field[$keyid]=$val;
             }
         }
     }
     $datos_json = json_decode($data);
     foreach($datos_json as $idx=>$dataarray) {
         $actualrow=array();
         foreach($dataarray as $kid=>$value) {
             if(isset($field[$kid])) {
                 $actualrow[$field[$kid]]=$value;
             }
         }
         $upfield=array();
         $extension='';
         foreach($actualrow as $key=>$val) {
             if($key<>'extension') {
                $upfield[]="`$key`='$val'";
             } else {
                $extension=$val;
             }
         }
         $updatedfields = implode(',',$upfield);
         $query = "UPDATE fop2buttons SET ".$updatedfields." WHERE exten='$extension'";
         $res = $db->consulta($query);
         $count++;
        
     }
     return $count;
}
