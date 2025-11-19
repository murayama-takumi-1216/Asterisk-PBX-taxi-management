<?php
require_once("config.php");
require_once("functions.php");
require_once("system.php");
require_once("dblib.php");
require_once("asmanager.php");
require_once("dbconn.php");
require_once("secure/secure-functions.php");
require_once("secure/secure.php");

include("headerbs.php");

echo "<div class='wrap'>\n";
include("menu.php");

if(!isset($panelcontext)) { $panelcontext=0; }
if(!isset($contextname[$panelcontext])) {
    $contextname[$panelcontext]='GENERAL';
}
$mycontext = $contextname[$panelcontext];
$mycontext = strtoupper($mycontext);

$action  = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$itemid  = isset($_REQUEST['itemid'])?$_REQUEST['itemid']:'';
$numpage = isset($_REQUEST['numpage'])?$_REQUEST['numpage']:'1';
$numpage = intval($numpage);
$error   = 0;

$helptext = Array();
$helptext['desktopNotify']="To enable or disable desktop notifications (notification boxes that popup outside the browser, so you can see them with the browser minimized). This does not work with all browsers.";
$helptext['disablePresence']="To enable or disable the Presence select box";
$helptext['disablePresenceOther']="To enable or disable the 'Other' option on the panel presence selection, that lets users chose the reason for their state besides the predefined ones.";
$helptext['disableQueueFilter']="When you click on a queue button, the extension display will hide all extensions but the ones matching their names with its queue members. If this is enabled, then queue filtering won't be active.";
$helptext['disableVoicemail']='Lets you control if you want to enable or disable the Voicemail Explorer feature. Requires a voicemail license to be effective.';
$helptext['disableWebSocket']='Lets you disable the use of HTML5 Websocket as the communications protocol. If disabled, Adobe Flash will be used instead.';
$helptext['dynamicLineDisplay']='When enabled, extension buttons won\'t show any inactive lines, making them shorter. But also the display will be irregular as some buttons will be taller than others depending on the number of active lines.';
$helptext['enableDragTransfer']='This setting lets you control if you want drag&amp;drop transfers to be enabled in Panel.';
$helptext['hideUnregistered']='When enabled, this option will hide all extension buttons that are lagged or unregistered.';
$helptext['noExtenInLabel']='If you do not want to show the extension number in a button label, then enable this setting.';
$helptext['soundChat']='To control if you want sounds for chat events.';
$helptext['soundQueue']='To control if you want sounds for queue events.';
$helptext['soundRing']='To enable or disable the RING sound when your extension receives a call.';
$helptext['startNotRegistered']='With this setting, all extension buttons will start in unregistered state (greyed out).';
$helptext['warnClose']='To control if you want a warning when the Panel page is about to be closed.';
$helptext['warnHangup']='To control if you want a confirmation when a hangup action is performed from the panel.';
$helptext['displayQueue']='Mode in which you want queue buttons to be displayed by default. The summary view will show only the number of logged in agents and number of waiting calls, while the full view will show every queue member and all waiting calls in detail.';
$helptext['language']='Main Language';
$helptext['logoutUrl']='URL to use when the logout button is clicked in the Panel UI.';
$helptext['pdateFormat']='Date format to use on Chat events.';
$helptext['voicemailFormat']='Format in which voicemail files are stored, this must be the same as configured in /etc/asterisk/voicemail.conf';
$helptext['notifyDuration']='Duration in seconds you want notifications to be displayed.';
$helptext['showLines']='Number of lines to show per button.';
$helptext['dialPrefix']='Dial prefix to use on click to call from phonebook or when using the dial box.';
$helptext['minDigitsToPrefix']='Use dialPrefix only if the destination number matches or surpasses this length.';
$helptext['consoleDebug']='Enable javascript debugging on the browser.';

$type     = array();
$classval = array();
$classkey = array();

$type['consoleDebug']='bool';
$type['desktopNotify']='bool';
$type['disablePresence']='bool';
$type['disablePresenceOther']='bool';
$type['disableQueueFilter']='bool';
$type['disableVoicemail']='bool';
$type['disableWebSocket']='bool';
$type['dynamicLineDisplay']='bool';
$type['enableDragTransfer']='bool';
$type['hideUnregistered']='bool';
$type['noExtenInLabel']='bool';
$type['soundChat']='bool';
$type['soundQueue']='bool';
$type['soundRing']='bool';
$type['startNotRegistered']='bool';
$type['warnClose']='bool';
$type['warnHangup']='bool';

$type['displayQueue']='enum';
$type['language']='enum';
$type['logoutUrl']='text';
$type['popupUrlRinging']='base64';
$type['popupUrlConnect']='base64';
$type['popupUrlHangup']='base64';
$type['pdateFormat']='text';
$type['voicemailFormat']='text';
$type['notifyDuration']='integer';
$type['showLines']='integer';
$type['dialPrefix']='text';
$type['minDigitsToPrefix']='text';
$type['notifyRinging']='bool';
$type['notifyConnect']='bool';
$type['notifyHangup']='bool';

$type['presenceOptions']='list';

$classval['presenceOptions']='colorpick';


$enum = Array();

$enum['displayQueue']["'min'"]=__('Summary');
$enum['displayQueue']["'max'"]=__('Full');

$enum['language']["'ca'"]    = 'Català';
$enum['language']["'cr'"]    = 'Hrvatski';
$enum['language']["'da'"]    = 'Dansk';
$enum['language']["'de'"]    = 'Deutsch';
$enum['language']["'he'"]    = 'עברית';
$enum['language']["'el'"]    = 'Ελληνικά';
$enum['language']["'en'"]    = 'English';
$enum['language']["'es'"]    = 'Español';
$enum['language']["'fr_FR'"] = 'Francais';
$enum['language']["'hu'"]    = 'Magyar';
$enum['language']["'it'"]    = 'Italiano';
$enum['language']["'nl'"]    = 'Dutch';
$enum['language']["'pl'"]    = 'Polski';
$enum['language']["'pt_BR'"] = 'Português';
$enum['language']["'ru'"]    = 'Русский';
$enum['language']["'se'"]    = 'Svenska';
$enum['language']["'tr'"]    = 'Türkçe';
$enum['language']["'uk_UA'"] = 'Ураїнська';
$enum['language']["'zh'"]    = '简体中文'; 

switch ($action) {
    case "save":
        $valkey = 'val'.$itemid;
        if($type[$itemid]=='bool') {
            if(isset($_POST[$valkey])) {
                $saveval = 'true';
            } else {
                $saveval = 'false';;
            }
        } else if($type[$itemid]=='base64') {
            $saveval = "'".base64_encode($_POST[$valkey])."'";
        } else if($type[$itemid]=='enum') {
            $saveval = "'".$_POST[$valkey]."'";
        } else if($type[$itemid]=='list') {
            $parname=array(); $valname=array();
            foreach($_POST as $key=>$val) {
                if(substr($key,0,9)=='listparam') {
                    $idx=substr($key,strpos($key,"_")+1);
                    $parname[$idx]=$val;
                }
                if(substr($key,0,8)=='valparam') {
                    $idx=substr($key,strpos($key,"_")+1);
                    $valname[$idx]=$val;
                }
            }
            $finaloption=array();
            foreach($parname as $idx=>$value) {
                $finaloption[$value]=$valname[$idx];
            }
            $allvalues = base64_encode(json_encode($finaloption));
        } else {
            $saveval = $_POST[$valkey];
            $saveval = preg_replace("/[^a-zA-Z0-9 ,:-]+\/&\./","",$saveval);
            $saveval = "'".$saveval."'";
        }

        if($type[$itemid]=='list') {
            if($itemid=='presenceOptions') { $exten='PRESENCE'; }
            $result = fop2_edit_setting_list($exten,$allvalues,$mycontext);
        } else {
            $result = fop2_edit_setting($itemid,$saveval,$mycontext);
        }
        if (!$result) { $error=1; }
        break;
    case "insertpresence":
        fop2_insert_setting('PRESENCE',$mycontext,$_POST['key'],$_POST['val']);
        die();
        break;
    case "deletepresence":
        fop2_delete_setting('PRESENCE',$mycontext,$_POST['key']);
        die();
        break;
}

$db2 = new dbcon("sqlite:$SQLITEDB");
$result = $db2->consulta("SELECT * FROM setup WHERE extension='SETTINGS' AND context='$mycontext'");

$settings = array();

while($row = $db2->fetch_assoc($result)) { 
    $settings[$row['context']][$row['parameter']]=$row['value'];
}

$settings[$mycontext]['presenceOptions']='';

$result = $db2->consulta("SELECT * FROM setup WHERE extension='PRESENCE' AND context='$mycontext'");
$presence = array();
while($row = $db2->fetch_assoc($result)) { 
    $listoption['presenceOptions'][$row['parameter']]=$row['value'];
}

?>
<div class='row' style='background-color:#78a300; padding-bottom:10px;'>
<div class="content">
<div class="col-md-8 col-sm-7 col-xs-6">
<span class='h2'><?php echo __('Settings');?></span>
<i style='vertical-align:super; top:-5px; color:#333;' class='ttip fa fa-info-circle' data-toggle='popover' data-trigger='hover' data-placement='bottom' data-content='<?php echo __("Settings let you change some default behaviour for the Switchboard, like enabling drag&amp;drop, webSocket, changing the number of lines to display per button, change available presence options, etc."); ?>'></i>
</div>
<div class='col-md-4 col-sm-5 col-xs-6 text-right'>
</div>
</div>
</div>

<div class='row'>

<!-- left side menu -->
<div class="col-md-4">
<table class='table table-striped table-hover' style='margin-top:20px;'>
<tbody id='tablesettings'>
<?php

$cont=0;
if (isset($settings[$mycontext])) {
    foreach ($settings[$mycontext] as $param=>$val) {
        $cont++;
        echo "<tr><td id='td_".$param."' class='pointer clickable ".($itemid==$param ? 'open ':'')."' >{$param}</td>";
        echo "<td class='".($itemid==$param ? 'open ':'')."text-right'>";
        echo display_value_for($param,$val);
        echo "</td></tr>";
    }

$dif = $perpage - ($cont % $perpage);
if($dif == $perpage) { $dif=0; }
if($cont>0) { $span='colspan=2'; } else { $span=''; }
if($dif>0) {
   for($i=0;$i<$dif;$i++) {
       echo "<tr id='no_${i}no'><td $span>&nbsp;</td></tr>\n";
   }
}
}

if($itemid<>'') {
    $valor = $settings[$mycontext][$itemid];
    $valor = preg_replace("/^'/","",$valor);
    $valor = preg_replace("/'$/","",$valor);
} else {
    $valor='';
}

?>
</tbody>
</table>
<div class="text-center">
<ul class="pagination" id="myPager"></ul>
</div>
</div>

<div class='col-md-8'>

<form autocomplete="off" name="edit" action="<?php echo SELF; ?>" method="post" class="form-horizontal">
    <input type="hidden" name="action" id="faction" value="<?php echo ($itemid<>'' ? 'save' : '') ?>">
    <input type="hidden" name="itemid" id="fitemid" value="<?php echo $itemid; ?>">
    <input type="hidden" name="itemvalue" id="fitemvalue" value="<?php echo ($valor<>'' ? $valor : ''); ?>">
    <input type="hidden" id='fnumpage' name="numpage" value="<?php echo $numpage; ?>">

<div class='section-title-container'>
<?php
if($itemid<>'') {
?>
<div class='h2 fhead' style='height: 55px; z-index: 1000;'><?php echo __('Edit Setting'); ?>
<div class="button-group pull-right">
<?php if($itemid<>'') { ?>
    <button type="submit" class="btn btn-success" onclick="return edit_onsubmit();"><?php echo __('Submit Changes')?></button>
<?php  } ?>
</div>
</div>
<?php } ?>
</div>

<div class='row'>
<div class="col-md-12">

<div class='fieldset'>
<?php 
if($itemid<>'') {

  if(isset($helptext[$itemid])) {
  echo "<p>";
  echo __($helptext[$itemid]);
  echo "</p>";
  }

  echo "<div class='form-group'>";

  echo "<label for='item' class='col-sm-3 control-label'>".$itemid."</label>";
  echo "<div class='col-xs-8'>";
  if($type[$itemid]=="bool") {

      echo "<input type='checkbox' data-on-text='".__('Yes')."' data-off-text='".__('No')."' ";
      echo "data-off-color='danger' data-on-color='success' class='chk' ";
      if($valor=='true') { echo " checked "; }
      echo "name='val$itemid' id='val$itemid'>";

  } else if($type[$itemid]=="enum"){
      echo "<select class='chosen-select' name='val$itemid' id='val$itemid'>";
      foreach($enum[$itemid] as $key=>$val) {
          $key = preg_replace("/^'/","",$key);
          $key = preg_replace("/'$/","",$key);
          echo "<option value='$key' ";
          if($valor==$key) { echo " selected "; }
          echo ">$val</option>\n";
      }
  } else if($type[$itemid]=="list"){

      $classextrakey = isset($classkey[$itemid])?$classkey[$itemid]:'';
      $classextraval = isset($classval[$itemid])?$classval[$itemid]:'';

      echo "<table class='table'>";
      echo "<tr><td><input type='text' name='listnew' id='listnew' class='form-control $classextrakey'></td><td><div class='input-group colorpicker-comonent $classextraval'><input type='text' name='valnew' id='valnew' class='form-control $classextraval'><span class='input-group-addon'><i></i></span></div></td><td><input type=button class='btn btn-primary' value='".__("Add")."' onClick=\"setInsert('$itemid');\"></button></td></tr>";
      $count=0;
     
      if(isset($listoption)) { 
          foreach($listoption[$itemid] as $key=>$val) {
              $count++;
              echo  "<tr><td><input type='text' name='listparam${itemid}_$count' class='form-control $classextrakey' value='$key'></input></td><td><div class='input-group colorpicker-comonent $classextraval'><input type='text' class='form-control' name='valparam${itemid}_$count' value='$val'><span class='input-group-addon'><i></i></span></div></input></td>";
            echo "<td><a style='color:#d11; cursor:pointer;' class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='left' data-content='".sprintf(__('Delete %s'),$key)."' onclick=\"setDelete('$itemid','".urlencode($key)."'); return false;\"><span class='fa fa-trash'></span></a></td>"; 
          }
      }
      echo "</table>";
  } else if($type[$itemid]=="base64"){
      $decodevalor = base64_decode($valor);
      echo "<textarea class='form-control' style='height: 205px;' name='val$itemid'>$decodevalor</textarea>";
  } else {
      echo "<input type='text' class='form-control' name='val$itemid' value='$valor'>";
  } 
  echo "</div></div>";
}
?>
</div>
</div>
</div>

<hr/>

</form>


</div>
</div>


<script>
<!--

var theForm = document.edit;

function setSave(userid) {
     numpage = $('#myPager').find('li.active')[0].innerText;
     $('#faction').val('save');
     $('#fnumpage').val(numpage);
     theForm.submit();
}

function setEdit(userid) {
     numpage = $('#myPager').find('li.active')[0].innerText;
     debug('set edit '+userid);
     $('#faction').val('edit');
     $('#fitemid').val(userid);
     $('#fnumpage').val(numpage);
     theForm.submit();
}

function setInsert(itemid) {
    var setkey = $('#listnew').val();
    var setval = $('#valnew').val();

    if(setkey != '' && setval != '' && itemid=='presenceOptions') {
         console.log('set save '+itemid+' key '+setkey+' val '+setval); 
         var data = "action=insertpresence&key="+setkey+'&val='+setval;
         $.post(window.location.href, data, function(data) { window.location.reload(false);});
    }
}

function setDelete(itemid,key) {
     //numpage = $('#myPager').find('li.active')[0].innerText;
     //debug('set edit '+userid);
     //$('#faction').val('edit');
     //$('#fitemid').val(userid);
     //$('#fnumpage').val(numpage);
     //theForm.submit();

     if(itemid=='presenceOptions') {

     alertify.confirm('',
         '<?php echo __('Are you sure?'); ?>', 
         function(e) {
             var data = "action=deletepresence&key="+key;
             $.post(window.location.href, data, function(data) { window.location.reload(false);});
         },
         function(e) {
           // cancel;
         }
     ).set({
        labels: {
            ok: '<?php echo __('Accept');?>',
            cancel: '<?php echo __('Cancel');?>'
        },
        closable: false
     });

     }
}

function edit_onsubmit() {
    return true;
}

$(document).ready(function() {

  $(".chk").bootstrapSwitch();
  $('.chosen-select').chosen({disable_search: true, skip_no_results: true});

  $('#tablesettings').pageMe({pagerSelector:'#myPager',showPrevNext:true,hidePageNumbers:false,perPage:<?php echo $perpage;?>,numbersPerPage:4,curPage:<?php echo $numpage;?>});

   $('.colorpick').colorpicker();

<?php

if($action=='save' && $error==0) {
?>
    alertify.success('<?php echo __('Changes saved successfully');?>');
    $('#fop2reload').show();
<?php
} else {
if(isset($_SESSION[MYAP]['needsreload'])) { 
?>
    $('#fop2reload').show();
<?php 
}
}
?>
});
//-->
</script>

<div class="push"></div>
</div>
<?php

function display_value_for($itemid,$val) {
    global $type;
    global $enum;
    if($type[$itemid]=="bool") {
        $valprint=array();
        $ret = "<span class='label ";
        if($val=='true') { $ret.=" label-success"; } else { $ret.=" label-danger"; }
        $valprint['true']=__('Yes');
        $valprint['false']=__('No');
        $ret .="'>".$valprint[$val]."</span>";
    } else if ($type[$itemid]=="enum") {
        $ret = $enum[$itemid][$val];
    } else if ($type[$itemid]=="base64") {
        $ret = base64_decode($val);
        $ret = preg_replace("/^'/","",$ret);
        $ret = preg_replace("/'$/","",$ret);
    } else {
        $ret = $val;
        $ret = preg_replace("/^'/","",$ret);
        $ret = preg_replace("/'$/","",$ret);
    }
    return $ret;
}
include("footerbs.php");
