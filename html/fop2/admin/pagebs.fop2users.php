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

$callbacks = fop2_get_plugins_callback();
foreach($callbacks as $rawname=>$file) {
    include($file);
}

$action  = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$itemid  = isset($_REQUEST['itemid'])?$_REQUEST['itemid']:'';
$numpage = isset($_REQUEST['numpage'])?$_REQUEST['numpage']:'1';
$numpage = intval($numpage);
$filter = isset($_REQUEST['filter'])?$_REQUEST['filter']:'';
$error   = 0;

switch ($action) {
    case "add":
        $oldItem['exten'] = $_POST['userid'];
        $result = fop2_add_user($_POST);
        if (!$result) { $error=1; }
        foreach($callbacks as $rawname=>$ff) {
            $fname = "${rawname}_add_user";
            if(is_callable($fname)) {
                call_user_func($fname,$_POST);
            }
        }
        break;
    case "delete":
        $oldItem = fop2_get_user($itemid);
        fop2_del_user($itemid);
        foreach($callbacks as $rawname=>$ff) {
            $fname = "${rawname}_del_user";
            if(is_callable($fname)) {
                call_user_func($fname,$oldItem);
            }
        }
        break;
    case "save":
        $result = fop2_edit_user($itemid,$_POST);
        if (!$result) { $error=1; }
        foreach($callbacks as $rawname=>$ff) {
            $fname = "${rawname}_edit_user";
            if(is_callable($fname)) {
                call_user_func($fname,$_POST);
            }
        }
        break;
    case "create":
        $users_added = fop2_insert_users();
        foreach($callbacks as $rawname=>$ff) {
            $fname = "${rawname}_insert_user";
            if(is_callable($fname)) {
                call_user_func($fname,$_POST);
            }
        }
        break;
    case "savepref":
        fop2_edit_user_setting($_POST['extension'],$panelcontext,$_POST['settings']);
        die();
        break;
}

foreach($callbacks as $rawname=>$ff) {
    $fname = "${rawname}_populate_data";
    if(is_callable($fname)) {
        call_user_func($fname,$_POST);
    }
}
 

$botonesdefinidos      = fop2_list_botones();
$users                 = fop2_list_users();
$selected_def_perm     = fop2_get_defperm();
$selected_def_group    = fop2_get_defgroup();
$selected_def_plugin   = fop2_get_defplugin();
$selected_def_template = fop2_get_deftemplate();

$extensions = array_flip(system_all_values('exten',1));
$labels = system_all_values('name');
$name=array();
foreach($extensions as $exten=>$dev) {
    $name[$exten]=isset($labels[$dev])?$labels[$dev]:'';
}
?>

<div class='row' style='background-color:#78a300; padding-bottom:10px;'>
<div class="content">
<div class="col-md-8 col-sm-7 col-xs-6">
<span class='h2'><?php echo __('Users');?></span>
<i style='vertical-align:super; top:-5px; color:#333;' class='ttip fa fa-info-circle' data-toggle='popover' data-trigger='hover' data-placement='bottom' data-content='<?php echo __('From here you can manage Panel users and permissions, and also mark the groups the user will be able to view or the plugins it will have enabled.'); ?>'></i>
</div>
<div class='col-md-4 col-sm-5 col-xs-6 text-right'>
<form method='post' action='<?php echo SELF?>'><div class='btn-group'><button type="submit" class="btn btn-default"><span class="fa fa-plus"></span></button><button class='btn btn-default'><?php echo __('Add User')?></button></div></form>
</div>
</div>
</div>

<div class='row'>

<!-- left side menu -->
<div class="col-md-3">
<br/>

<div class='input-group'>
<input type='text' name='userfilter' id='userfilter' class="form-control" placeholder='<?php echo __('Search');?>' />
<span class="input-group-addon">
                <i class="fa fa-search"></i>
            </span>
</div>

<table class='table table-striped table-hover' style='margin-top:20px;'>
<tbody id='tableusers'>
<?php
$cont=0;
if (count($users)>0) {
    foreach ($users as $d) {
        $cont++;
        if(!isset($name[$d['exten']])) { $name[$d['exten']]=''; }
        echo "<tr id='tr_".$d['id']."' style='cursor:pointer;'>";
        echo "<td id='td_".$d['id']."' class='clickable ".($itemid==$d['id'] ? 'open ':'')."'>{$d['exten']} {$name[$d['exten']]}</td>";
        echo "<td class='".($itemid==$d['id'] ? 'open ':'')."text-right'>";
        echo "<a style='color:#d11; cursor:pointer;' class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='left' data-content='".sprintf(__('Delete User %s'),$d['exten'])."' onclick=\"setDelete('".urlencode($d['id'])."'); return false;\"><span class='fa fa-trash'></span></a>"; 
        echo "</td></tr>";
    }
}
$dif = $perpage - ($cont % $perpage);
if($dif == $perpage) { $dif=0; }
if($cont>0) { $span='colspan=2'; } else { $span=''; }
if($dif>0) {
   for($i=0;$i<$dif;$i++) {
       echo "<tr id='no_${i}no'><td $span>&nbsp;</td></tr>\n";
   }
}

?>
</tbody>
</table>
<div class="text-center">
<ul class="pagination" id="myPager"></ul>
</div>
</div>

<div class='col-md-9'>

<?php
if ($action=="delete") { $itemid='';}

$delURL = SELF.'?'.$_SERVER['QUERY_STRING'].'&amp;action=delete';

if(!$itemid || $itemid=='') {
    $hisTemplate      = '';
    $permisos_activos = $selected_def_perm;
    $hisGroups        = $selected_def_group;
    $hisPlugins       = $selected_def_plugin;
    $userPref         = false;
} else {
    $thisItem         = fop2_get_user($itemid);
    $hisTemplate      = fop2_get_user_template($thisItem['exten']);
    $hisGroups        = fop2_get_user_groups($thisItem['exten']);
    $hisPlugins       = fop2_get_user_plugins($thisItem['exten']);
    $permisos_activos = $thisItem['permissions'];
    $userPref         = fop2_get_user_preferences($thisItem['exten'],$panelcontext);
}

$permisos_activos = explode(",",$permisos_activos);

// Generate javascript code to define array with globally enabled plugins
$globalpluginscript='';
$results= fop2_list_installed_plugins();
//print_r($results);
if(count($results)>0) {
    $globalpluginscript = "globalplugin = [];\n";
    foreach($results as $result) {
        if($result['global']==1) {
            $globalpluginscript.= "globalplugin.push('".$result['rawname']."');\n";
        }
    }
}

?>

<form autocomplete="off" name="edit" id="edit" class="form-horizontal" role="form" action="<?php echo SELF; ?>" method="post" >
    <input type="hidden" id='faction' name="action" value="<?php echo ($itemid ? 'save' : 'add') ?>">
    <input type="hidden" id='fitemid' name="itemid" value="<?php echo $itemid; ?>">
    <input type="hidden" id='fnumpage' name="numpage" value="<?php echo $numpage; ?>">
    <input type="hidden" id='ffilter' name="filter" value="<?php echo $filter; ?>">
    <input type="hidden" id='perpage' name="perpage" value="<?php echo $perpage; ?>">


<div class='section-title-container'>
<div class='fhead h2' style='height: 55px; z-index: 1000;'><?php echo ($itemid ? sprintf(__('Edit User %s'),$thisItem["exten"]) : __('Add User')); ?>
<div class="button-group pull-right">
    <button type="submit" class="btn btn-success" onclick="return edit_onsubmit();"><?php echo __('Submit Changes')?></button>
</div>
</div>
</div>

<div class='row'>
<div class="col-md-6">


<div class='fieldset'>
<h4><span>1. 
<?php echo __('Login Details')?>
</span></h4>
</div>

<?php
if(!isset($cust_fields)) $cust_fields=array();

foreach($cust_fields as $idx=>$dd) {
    if($dd['section']=='user') {
        echo render_custom_form_field($dd);
    }
}
 
?>

  <div class="form-group">
    <label for="userid" class="col-sm-3 col-xs-12 control-label ttip" data-delay='{"show":"750", "hide":"100"}' data-toggle='popover' data-trigger='hover' data-placement='right' data-content='<?php echo __('The extension number for this user.')?>'><?php echo __('Extension')?></label>
    <div class="col-sm-8 col-xs-12">
      <input type="text" class="form-control" id="userid" name="userid" placeholder="<?php echo __('Extension')?>" value="<?php echo isset($thisItem['exten']) ? htmlspecialchars($thisItem['exten']) : fop2_get_next_available_exten(); ?>" <?php echo isset($thisItem['exten']) ? 'disabled' : '' ?>>
    </div>
  </div>

  <div class="form-group">
    <label for="secret" class="col-sm-3 col-xs-12 control-label ttip" data-delay='{"show":"750", "hide":"100"}' data-toggle='popover' data-trigger='hover' data-placement='right' data-content='<?php echo __('The secret to login to the panel. If the user was autocreated on install time, the password will be the voicemail pin, if it has no voicemail enabled, it will be the same extension number.')?>'><?php echo __('Secret')?></label>
    <div class="col-sm-8 col-xs-12">
      <input type="password" class="form-control" id="secret" name="secret" placeholder="<?php echo __('Secret')?>" value="<?php echo htmlspecialchars(isset($thisItem['secret']) ? $thisItem['secret'] : ''); ?>">
    </div>
  </div>

  <div class="form-group">
    <label for="settemplate" class="col-sm-3 col-xs-12 control-label ttip" data-delay='{"show":"750", "hide":"100"}' data-toggle='popover' data-trigger='hover' data-placement='right' data-content='<?php echo __('Choose a template to quickly set or change permissions, groups and plugins.')?>'><?php echo __('Template')?></label>
    <div class="col-sm-8 col-xs-12">
            <select class='form-control chosen-select' onChange="setTemplate();" id="settemplate" name='settemplate'>
                 <option value="0"><?php echo __('(pick template)')?></option>
                 <?php
    $templates = fop2_list_templates();
    if (count($templates)>0) {
        foreach ($templates as $d) {
            echo "<option value='".$d['id']."' ";
            if(!$itemid) {
                if($selected_def_template == $d['id']) {
                    echo " selected ";
                    $hisTemplate=$d['id'];
                }
            } else {
                if($hisTemplate == $d['id']) {
                    echo " selected ";
                }
            }
            echo ">".$d['name']."</option>\n";
        }
    }
?>
            </select>

   </div>
  </div>

</div>
<div class="col-md-6">

<div class='fieldset'>
<h4>
<span>2.  <?php echo __('Permissions')?></span>
</h4>
</div>

<p><?php echo __('Choose the permission you want to grant to the user.') ?></p>

  <div class="form-group">
      <div class="col-sm-12 col-xs-12">
        <select name='permissions[]' id="permissions" class='chosen-select-create form-control' multiple data-create_option_text="<?php echo __('Create option');?>" data-placeholder="<?php echo __('(pick permission)');?>">
<?php
    $stock_perms = fop2_permissions();
    $cust_perm   = fop2_custom_permissions();
    $simple_cust_perm = array();
    foreach ($cust_perm as $perm) { $simple_cust_perm[]=$perm['name']; }
    if(!is_array($permisos_activos)) { $permisos_activos=array(); }

    // We want permissions that are added by users to be displayed
    // but we also want a unique and non null array as result
    $merged_perm = array_merge($permisos_activos,$stock_perms);
    $merged_perm = array_diff($merged_perm,$simple_cust_perm);
    $merged_perm = array_unique($merged_perm);
    $merged_perm = array_filter($merged_perm);
    asort($merged_perm);

    foreach ($simple_cust_perm as $perm) {
        if(in_array($perm,$permisos_activos)) { $selected=' selected="selected" '; } else { $selected=''; }
        echo "            <option value='".$perm."' ".$selected.">[".$perm."]</option>\n";
    }

    foreach ($merged_perm as $perm) {
        if(in_array($perm,$permisos_activos)) { $selected=' selected="selected" '; } else { $selected=''; }
        echo "            <option value='".$perm."' ".$selected.">".$perm."</option>\n";
    }
?>
        </select>
  </div>
</div>


</div>
</div>


<div class='row'>
<div class="col-md-6">

<div style='height:20px;'>&nbsp;</div>
<div class='fieldset'>
<h4>
<span>3. <?php echo __('Groups')?></span>
</h4>
</div>

<div style='padding:0 10px;'>
<?php echo __('Select the groups you want the user to see in the panel. If no groups are selected the user will be able to see all buttons.');?>

  <div class="form-group">
      <div class="col-sm-12 col-xs-12"-->
        <select multiple name='includebot[]' id="groups" class='chosen-select form-control' data-placeholder="<?php echo __('(pick group)');?>">

<?php

    $groupsdb           = fop2_list_groups(0);

    if(!is_array($groupsdb)) {
        $groupsdb = array();
    }

    $groups = array_merge($predefined_groups,$groupsdb);

    foreach ($groups as $count => $datarray) {
/*        echo "<div class='checkbox'>";
        echo "<label><input type=checkbox class='achk' name='includebot[]' value='".$datarray['id']."'";

        if(in_array($datarray['id'],$hisGroups)) {
            echo " checked ";
        }

        echo "> ".__($datarray['name']);
        echo "</label></div>";
*/
           echo "<option value='".$datarray['id']."' ";
           if(in_array($datarray['id'],$hisGroups)) {
                echo " selected ";
           }
           echo ">".__($datarray['name'])."</option>";
 
    }

?>
</select>
</div>
</div>

<?php
   $results= fop2_list_installed_plugins();
   if(count($results)>0){

?>
</div>

</div>
<div class="col-md-6">

<div style='height:20px;'>&nbsp;</div>
<div class='fieldset'>
<h4>
<span>4. <?php echo __('Plugins')?></span>
</h4>
</div>

<div style='padding:0 10px;'>

<p><?php echo __('Select the plugins you want to load for this user.'); ?></p>

  <div class="form-group">
      <div class="col-sm-12 col-xs-12"-->
        <select multiple name='includeplugin[]' id="plugins" class='chosen-select form-control' data-placeholder="<?php echo __('(pick plugins)');?>">

<?php
       foreach($results as $result) {
           echo "<option value='".$result['rawname']."' ";
           if(in_array($result['rawname'],$hisPlugins) || $result['global']==1) {
            echo " selected ";
            if($result['global']==1) { echo " disabled "; }
           }
           echo ">".$result['name']."</option>";
       }
} 
?>
</div></div>
</select>
</div>

</div>
</div>

</form>
<div id='end'></div>

</div>
</div>

<div class='row'>
<div class="col-md-6">
<?php
if($userPref !== false) { 
?>
<div style='height:20px;'>&nbsp;</div>
<div class='fieldset'>
<h4>
<span>5. <?php echo __('User Preferences')?></span>
</h4>
</div>
<div style='padding:0 10px;'>
  <div class="form-group">
      <button id='showpref' class='btn btn-default'><?php echo __('User Preferences');?></button>
  </div>
</div>
<?php } ?>
</div>
<div class="col-md-6">
</div>
</div>

<div class="push"></div>

</div>


<?php

if(isset($thisItem['exten'])) {
    $prefexten = $thisItem['exten'];
} else {
    $prefexten = fop2_get_next_available_exten(); 
}
?>        
        <div id='preferencePane' class="modal fade" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <div class='content'><span class='fa fa-wrench pull-left' style='padding: 7px 5px; font-size:1.5em;'></span><h3 class="modal-title preferencestitle"><?php echo __('Preferences');?> <?php echo $prefexten;?></h3></div>
                    </div>
                    <div class="modal-body">
                        <div class="container-fluid nopadding">
                            <form class='' id='preferences' name="preferences">
                            <div class="row">
                                <div class="col-md-6">
                                    <fieldset>
                                        <legend id='prefSounds'></legend>
                                            <div class="form-group">
                                            <label for='prefSoundChat' id='labelSoundChat' class="col-lg-6 control-label pl0">Chat Sounds</label>
                                            <div class='col-lg-6'><input id='prefSoundChat' name='prefSoundChat' type='checkbox' class='chk' data-on-text='<?php echo __('Yes');?>' data-off-text='<?php echo __('No');?>'/></div>
                                            <div class='clear'></div>
                                            </div>
<br/>
                                            <div class="form-group">
                                            <label for='prefSoundQueue' id='labelSoundQueue' class="col-lg-6 control-label pl0">Queue Sounds</label>
                                            <div class='col-lg-6'><input id='prefSoundQueue' name='prefSoundQueue' type='checkbox' class='chk' data-on-text='<?php echo __('Yes');?>' data-off-text='<?php echo __('No');?>'/></div>
                                            <div class='clear'></div>
                                            </div>
<br/>
                                            <div class="form-group">
                                            <label for='prefSoundRing' id='labelSoundRing' class="col-lg-6 control-label pl0 ">Ring Sounds</label>
                                            <div class='col-lg-6'><input id='prefSoundRing' name='prefSoundRing' type='checkbox' class='chk' data-on-text='<?php echo __('Yes');?>' data-off-text='<?php echo __('No');?>'/></div>
                                            <div class='clear'></div>
                                            </div>
<br/>
                                    </fieldset>
                                </div>
                                <div class="col-md-6">
                                    <fieldset>
                                        <legend id='prefDisplay'></legend>
                                        <div class="form-group">
                                            <label for="prefDisplayLanguage" id="labelDisplayLanguage" class="col-sm-7 control-label pl0">Language</label>
                                            <div class='col-sm-4 pl0'><select id="prefDisplayLanguage" name="prefDisplayLanguage" data-width="100%" class="chosen-select nopad"></select></div>
                                            <div class='clear'></div>
                                        </div>
<br/>
                                        <div class="form-group">
                                            <label for='prefDisplayQueue' id='labelDisplayQueue' class="col-sm-7 control-label pl0"></label>
                                                <div class='col-sm-4 pl0'>
                                                <select id='prefDisplayQueue' name='prefDisplayQueue' data-width="100%" class="chosen-select nopad">
                                                    <option value='min'><?php echo __('Summary'); ?></option>
                                                    <option value='max'><?php echo __('Detailed'); ?></option>
                                                </select>
                                                </div>
                                            <div class='clear'></div>
                                        </div>
<br/>
                                        <div class="form-group">
                                            <label for='prefDisplayDynamicLine' id='labelDisplayDynamicLine' class="col-sm-7 control-label pl0"></label>
                                            <div class='col-sm-4 pl0'>
                                                <input id='prefDisplayDynamicLine' name='prefDisplayDynamicLine' type='checkbox' class='chk'  data-on-text='<?php echo __('Yes');?>' data-off-text='<?php echo __('No');?>'></li>
                                           </div>
                                            <div class='clear'></div>
                                       </div>
<br/>
                                    </fieldset>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-md-12">
                                    <fieldset>
                                        <legend id='prefPopup'>Popup</legend>
                                        <div class="row">
                                            <div class="col-md-6 form-group">
                                                <div class='col-lg-6 control-label pl0'><label for='prefAutoPopup' id='labelAutoPopup'></label></div>
                                                <div class='col-lg-6'><input id='prefAutoPopup' name='prefAutoPopup' type='checkbox' class='chk' data-on-text='<?php echo __('Yes');?>' data-off-text='<?php echo __('No');?>'></div>
                                            </div>
                                            <div class="col-md-6 form-group">
                                                <div class='col-sm-7 control-label pl0'><label for='prefDisplayNotifyDuration' id='labelDisplayNotifyDuration' class='control-label'></label></div>
                                                <div class='col-sm-4 pl0'><input id='prefDisplayNotifyDuration' name='prefDisplayNotifyDuration' type='number' value='' class="form-control"></div>
                                            </div>
                                        </div>
                                        <div class="row form-group">
                                            <div class='col-lg-10'><span class='emphasis'>URL</span></div>
                                            <div class='col-lg-1'><span class='emphasis' id='labelNotification'><?php echo __('Notification'); ?></span></div>
                                        </div>

                                        <div class="row form-group">
                                            <div class='col-lg-3'><label for='prefPopupUrlRinging' id='labelPopupUrlRinging' class="control-label">Ringing</label></div>
                                            <div class='col-lg-7'><input id='prefPopupUrlRinging' name='prefPopupUrlRinging' type='text' class="form-control"></div>
                                            <div class='col-lg-2'><input id='prefNotifyRinging' name='prefNotifyRinging' type='checkbox' class='chk' data-on-text='<?php echo __('Yes');?>' data-off-text='<?php echo __('No');?>'></div>
                                        </div>
                                        <div class="row form-group">
                                            <div class='col-lg-3'><label for='prefPopupUrlConnect' id='labelPopupUrlConnect' class="control-label">Connect</label></div>
                                            <div class='col-lg-7'><input id='prefPopupUrlConnect' name='prefPopupUrlConnect' type='text' class="form-control"></div>
                                            <div class='col-lg-2'><input id='prefNotifyConnect' name='prefNotifyConnect' type='checkbox' class='chk' data-on-text='<?php echo __('Yes');?>' data-off-text='<?php echo __('No');?>'></div>
                                        </div>
                                        <div class="row form-group">
                                            <div class='col-lg-3'><label for='prefPopupUrlHangup' id='labelPopupUrlHangup' class="control-label">Hangup</label></div>
                                            <div class='col-lg-7'><input id='prefPopupUrlHangup' name='prefPopupUrlHangup' type='text' class="form-control"></div>
                                            <div class='col-lg-2'><input id='prefNotifyHangup' name='prefNotifyHangup' type='checkbox' class='chk' data-on-text='<?php echo __('Yes');?>' data-off-text='<?php echo __('No');?>'></div>
                                        </div>
                                    </fieldset>

                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <fieldset>
                                        <legend id='prefPhone'>Phone</legend>
                                        <div class="row form-group">
                                            <div class='col-lg-3'><label for='prefAutoAnswer' id='labelAutoAnswer' class="control-label"></label></div>
                                            <div class='col-lg-4'><input id='prefAutoAnswer' name='prefAutoAnswer' type='checkbox' class='chk'  data-on-text='<?php echo __('Yes');?>' data-off-text='<?php echo __('No');?>'></div>
                                        </div>
                                    </fieldset>
                                </div>
                            </div>
                      <hr/>
                <div class='text-center'>
                    <button class='btn btn-primary butAccept' id='prefbuttonaccept'><?php echo __('Accept');?></button>
                </div>
            </form>

        </div>
        </div>
        </div>
        </div>
        </div>

<script>
<!--

    $('#prefSounds').html("<?php echo __('Sounds');?>");
    $('#prefDisplay').html("<?php echo __('Display');?>");
    $('#prefPopup').html("<?php echo __('Popup');?>");
    $('#prefPhone').html("<?php echo __('Phone');?>");

    $('#labelSoundChat').html("<?php echo __('Chat Sounds');?>");
    $('#labelSoundQueue').html("<?php echo __('Queue Sounds');?>");
    $('#labelSoundRing').html("<?php echo __('Ring Sounds');?>");

    $('#labelDisplayQueue').html("<?php echo __('Default Queue View');?>");

    $('#labelDisplayDynamicLine').html("<?php echo __('Dynamic Line Display');?>");
    $('#labelDisplayNotifyDuration').html("<?php echo __('Notify Duration');?>");
    $('#labelDisplayLanguage').html("<?php echo __('Language');?>");
    $('#labelAutoPopup').html("<?php echo __('Automatic Popup');?>");
    $('#labelAutoAnswer').html("<?php echo __('Auto Answer');?>");

    var availLang      = new Object();
    availLang['ca']    = 'Català';
    availLang['cr']    = 'Hrvatski';
    availLang['da']    = 'Dansk';
    availLang['de']    = 'Deutsch';
    availLang['he']    = 'עברית';
    availLang['el']    = 'Ελληνικά';
    availLang['en']    = 'English';
    availLang['es']    = 'Español';
    availLang['fr_FR'] = 'Francais';
    availLang['hu']    = 'Magyar';
    availLang['it']    = 'Italiano';
    availLang['nl']    = 'Dutch'
    availLang['pl']    = 'Polski';
    availLang['pt_BR'] = 'Português';
    availLang['ru']    = 'Русский';
    availLang['se']    = 'Svenska';
    availLang['tr']    = 'Türkçe';
    availLang['uk_UA'] = 'Ураїнська';
    availLang['zh']    = '简体中文'; 

    // add language Options to UI taken from prsence.js array
    for (var item in availLang) {
        itemPrint = availLang[item];
        $('#prefDisplayLanguage').append($('<option>', {
            value: item,
            text: itemPrint
        }).attr({'data-content':'<i class="flag flag-'+item+'"></i> '+itemPrint}));
    }

$('#showpref').on('click',function() { $('#preferencePane').modal(); return false;});
$('#prefbuttonaccept').on('click',function() { $('#preferencePane').modal('hide'); return false;});


<?php

if(!isset($userPref) || $userPref===false) { $userPref=array(); }

if(!isset($userPref['autoPopup'])) {
   $userPref['autoPopup']='off';
}
if(!isset($userPref['autoAnswer'])) {
   $userPref['autoAnswer']='off';
}
if(!isset($userPref['dynamicLineDisplay'])) {
   $userPref['dynamicLineDisplay']='off';
}
if(!isset($userPref['soundChat'])) {
   $userPref['soundChat']='on';
}
if(!isset($userPref['soundQueue'])) {
   $userPref['soundQueue']='on';
}
if(!isset($userPref['soundRing'])) {
   $userPref['soundRing']='on';
}
if(!isset($userPref['displayQueue'])) {
   $userPref['displayQueue']='max';
}
if(!isset($userPref['popupUrl'])) {
   $userPref['popupUrl']='';
}
if(!isset($userPref['popupUrlRinging'])) {
       $userPref['popupUrlRinging']='';
}
if(!isset($userPref['popupUrlConnect'])) {
       $userPref['popupUrlConnect']='';
}
if(!isset($userPref['popupUrlHangup'])) {
       $userPref['popupUrlHangup']='';
}

if($userPref['autoPopup']=='on') {
    echo "\$('#prefAutoPopup').prop('checked', true);\n";
}

if($userPref['autoAnswer']=='on') {
    echo "\$('#prefAutoAnswer').prop('checked', true);\n";
}

if($userPref['dynamicLineDisplay']=='on') {
    echo "\$('#prefDisplayDynamicLine').prop('checked', true);\n";
}

if($userPref['soundChat']=='on' || !isset($userPref['soundChat'])) {
    echo "\$('#prefSoundChat').prop('checked', true);\n";
}

if($userPref['soundQueue']=='on' || !isset($userPref['soundQueue'])) {
    echo "\$('#prefSoundQueue').prop('checked', true);\n";
}

if($userPref['soundRing']=='on' || !isset($userPref['soundRing'])) {
    echo "\$('#prefSoundRing').prop('checked', true);\n";
}

if($userPref['displayQueue']=='max' || !isset($userPref['displayQueue'])) {
    echo "\$('#prefDisplayQueue').val('max');\n";
} else {
    echo "\$('#prefDisplayQueue').val('min');\n";
}

if(isset($userPref['language'])) {
    echo "\$('#prefDisplayLanguage').val('".$userPref['language']."');\n";
} else {
    echo "\$('#prefDisplayLanguage').val('en');\n";
}

if(isset($userPref['notifyDuration'])) {
    echo "\$('#prefDisplayNotifyDuration').val('".$userPref['notifyDuration']."');\n";
} else {
    echo "\$('#prefDisplayNotifyDuration').val('6');\n";
}
echo "\$('#prefPopupUrlRinging').val('".base64_decode($userPref['popupUrlRinging'])."');\n";
echo "\$('#prefPopupUrlConnect').val('".base64_decode($userPref['popupUrlConnect'])."');\n";
echo "\$('#prefPopupUrlHangup').val('".base64_decode($userPref['popupUrlHangup'])."');\n";



?>

    $('#preferencePane').on('hidden.bs.modal', function (e) {
        savePreferences();
    });


function savePreferences() {
    var stringPref = [];

        if ($('#prefSoundChat').is(':checked')) {
            debug('sound chat on');
            stringPref.push("soundChat!on");
        } else {
            debug('sound chat off');
            stringPref.push("soundChat!");
        }

        if ($('#prefSoundQueue').is(':checked')) {
            stringPref.push("soundQueue!on");
        } else {
            stringPref.push("soundQueue!");
        }

        if ($('#prefSoundRing').is(':checked')) {
            stringPref.push("soundRing!on");
        } else {
            stringPref.push("soundRing!");
        }

        if ($('#prefDisplayDynamicLine').is(':checked')) {
            stringPref.push("dynamicLineDisplay!on");
        } else {
            stringPref.push("dynamicLineDisplay!off");
        }

        if ($('#prefDisplayQueue')[0].options[$('#prefDisplayQueue')[0].selectedIndex].value == "min") {
            stringPref.push("displayQueue!min");
        } else {
            stringPref.push("displayQueue!max");
        }

        if ($('#prefAutoPopup').is(':checked')) {
            stringPref.push("autoPopup!on");
        } else {
            stringPref.push("autoPopup!");
        }

        if ($('#prefAutoAnswer').is(':checked')) {
            stringPref.push("autoAnswer!on");
        } else {
            stringPref.push("autoAnswer!");
        }

        if ($('#prefPopupUrlRinging').val() != '') {
            var urlencode = btoa($('#prefPopupUrlRinging').val());
            stringPref.push("popupUrlRinging!" + urlencode);
        } else {
            stringPref.push("popupUrlRinging!");
        }

        if ($('#prefPopupUrlConnect').val() != '') {
            var urlencode = btoa($('#prefPopupUrlConnect').val());
            stringPref.push("popupUrlConnect!" + urlencode);
        } else {
            stringPref.push("popupUrlConnect!");
        }

        if ($('#prefPopupUrlHangup').val() != '') {
            var urlencode = btoa($('#prefPopupUrlHangup').val());
            stringPref.push("popupUrlHangup!" + urlencode);
        } else {
            stringPref.push("popupUrlHangup!");
        }

        var notifyDuration = $('#prefDisplayNotifyDuration').val();
        stringPref.push("notifyDuration!" + notifyDuration);

        var language = $('#prefDisplayLanguage').val();
        stringPref.push("language!" + language);

        var fullPref = btoa(stringPref.join("&"));
        var data = "action=savepref&extension=<?php echo $prefexten;?>&settings="+fullPref;
        $.post(window.location.href, data, function(data) { });
}


// end of user settings script, start of regular settings fucntions

var theForm = document.edit;
theForm.userid.focus();


<?php
// javascript arrays for permissions and templates
echo fop2_list_templates_jsobject();
echo $globalpluginscript;
?>

<?php
$fop2users = fop2_get_users();
$fusers = array();
$fusersstring = "";
if(is_array($fop2users)) {
    foreach ($fop2users as $index) {
        $fusers[]="'".$index['exten']."'";
    }
    $fusersstring=join(",",$fusers);
}
echo "var fop2users = [ ".$fusersstring. " ];";
?>

function setSave(userid) {
     try {
         numpage = $('#myPager').find('li.active')[0].innerText;
     } catch(err) {
         numpage = 1;
     }
     $('#faction').val('save');
     $('#fnumpage').val(numpage);
     $('#ffilter').val($('#userfilter').val());
     theForm.submit();
}

function setEdit(userid) {
     try {
         numpage = $('#myPager').find('li.active')[0].innerText;
     } catch(err) {
         numpage = 1;
     }
     $('#faction').val('edit');
     $('#fitemid').val(userid);
     $('#fnumpage').val(numpage);
     $('#ffilter').val($('#userfilter').val());
     debug('num page '+numpage);
     debug('faction '+$('#faction').val());
     debug('fitemid '+$('#fitemid').val());
     theForm.submit();
}


function setDelete(userid) {
     try {
         numpage = $('#myPager').find('li.active')[0].innerText;
     } catch(err) {
         numpage = 1;
     }
 
     alertify.confirm('',
         '<?php echo __('Are you sure?'); ?>', 
         function(e) {
             $('#faction').val('delete');
             $('#fitemid').val(userid);
             $('#fnumpage').val(numpage);
             $('#ffilter').val($('#userfilter').val());
             theForm.submit();
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

function contains(a, e) {
    for(j=0;j<a.length;j++)if(a[j]==e)return true;
    return false;
}

function unique(a) {
    tmp = new Array(0);
    for(i=0;i<a.length;i++){
        if(!contains(tmp, a[i])){
            tmp.length+=1;
            tmp[tmp.length-1]=a[i];
        }
    }
    return tmp;
}

function not_empty(value)
{ //Strips leading and trailing whitespace and tests if anything remains.
  var re = (value.replace(/^\s+|\s+$/g,'').length > 0)?true:false;
  return re;
}

function oc(a) {
  var o = {};
  for(var i=0;i<a.length;i++) {
    o[a[i]]='';
  }
  return o;
}

function setTemplate() {
    template = document.getElementById('settemplate').value;

    if(template=='' || template=='0') {
        var nowperm = [];
        var nowgrp = [];
        var nowplg = [];
        $('#permissions').prop('disabled',false);
        $('#groups').prop('disabled',false);
        $('#plugins').prop('disabled',false);

<?php
        foreach($cust_fields as $idx=>$dd) {
            if($dd['section']=='user' && $dd['type']=='multiselect') {
                echo "\$('#".$dd['variablename']."').prop('disabled',false);\n";
            }
        }
?>

    } else {
        eval("var nowperm = tempperm_"+template);
        eval("var nowgrp  = tempgrp_"+template);
        eval("var nowplg  = tempplg_"+template);


        $('#permissions').prop('disabled',true);
        $('#groups').prop('disabled',true);
        $('#plugins').prop('disabled',true);

<?php
        foreach($cust_fields as $idx=>$dd) {
            if($dd['section']=='user' && $dd['type']=='multiselect') {
                echo "\$('#".$dd['variablename']."').prop('disabled',true);\n";
            }
        }
?>


    }

    field = document.forms['edit'].elements['permissions[]'];
    for (i = 0; i < field.length; i++) {
        if(field[i].value in oc(nowperm)) {
            field[i].selected = true;
        } else {
            field[i].selected = false;
        }
    }
    $("#permissions").trigger("chosen:updated");

    field = document.forms['edit'].elements['includebot[]'];
    for (i = 0; i < field.length; i++) {
        if(field[i].value in oc( nowgrp )) {
            field[i].selected = true;
        } else {
            field[i].selected = false;
        }
    }
    $("#groups").trigger("chosen:updated");

    field = document.forms['edit'].elements['includeplugin[]'];
    for (i = 0; i < field.length; i++) {
        if(field[i].value in oc( globalplugin )) {
            field[i].selected = true;
            $(field[i]).prop('disabled',true);
        } else {
            if(field[i].value in oc( nowplg )) {
                field[i].selected = true;
            } else {
                field[i].selected = false;
            }
        }
    }
    $("#plugins").trigger("chosen:updated");

<?php
foreach($cust_fields as $idx=>$dd) {
    if($dd['section']=='user' && $dd['type']=='multiselect') {
        echo "
            if(template>0) {

                if(typeof('temp_".$dd['variablename']."_'+template)!=undefined) {
                    eval(\"var nowcust = temp_".$dd['variablename']."_\"+template);
                }

                field = document.forms['edit'].elements['".$dd['variablename']."'];
                for (i = 0; i < field.length; i++) {
                    field[i].removeAttribute('selected');
                    if(field[i].value in oc( nowcust )) {
                        field[i].selected = true;
                        field[i].setAttribute('selected',true);
                    } else {
                        field[i].selected = false;
                        field[i].removeAttribute('selected');
                    } 
                }
            } else {
                field = document.forms['edit'].elements['".$dd['variablename']."'];
                for (i = 0; i < field.length; i++) {
                    field[i].removeAttribute('selected');
                    field[i].selected = false;
                }
            }
            $(\"#".$dd['variablename']."\").trigger(\"chosen:updated\");

        ";
    }
}
?> 

}

function edit_onsubmit() {

    if(theForm.faction.value=='add') { 

        var msgEmptyUserId = "<?php echo __('Please insert an extension number.')?>";
        var msgInvalidUserId = "<?php echo __('The extension must be numeric.')?>";
        var msgAlreadyThere = "<?php echo __('That extension already exists.')?>";

        if (fop2users.indexOf(theForm.userid.value)>=0) {
            alertify.error(msgAlreadyThere);
            theForm.userid.focus();
            return false;
        }

        if (isEmpty(theForm.userid.value)) {
            alertify.error(msgEmptyUserId);
            theForm.userid.focus();
            return false;
        }

        if (!isInteger(theForm.userid.value)) {
            alertify.error(msgInvalidUserId);
            theForm.userid.focus();
            return false;
        }

        $('#permissions').prop('disabled',false);
        $('#groups').prop('disabled',false);
        $('#plugins').prop('disabled',false);

<?php
        foreach($cust_fields as $idx=>$dd) {
            if($dd['section']=='user' && $dd['type']=='multiselect') {
                echo "\$('#".$dd['variablename']."').prop('disabled',false);\n";
            }
        }
?>

        return true;

    } else {
        $('#permissions').prop('disabled',false);
        $('#groups').prop('disabled',false);
        $('#plugins').prop('disabled',false);

<?php
        foreach($cust_fields as $idx=>$dd) {
            if($dd['section']=='user' && $dd['type']=='multiselect') {
                echo "\$('#".$dd['variablename']."').prop('disabled',false);\n";
            }
        }
?>


        return true; 
    }
    return false;
}

$(document).ready(function() {


<?php

if($hisTemplate>0) {
?>
        $('#permissions').prop('disabled',true);
        $('#groups').prop('disabled',true);
        $('#plugins').prop('disabled',true);
        $("#permissions").trigger("chosen:updated");
        $("#groups").trigger("chosen:updated");
        $("#plugins").trigger("chosen:updated");

<?php
        foreach($cust_fields as $idx=>$dd) {
            if($dd['section']=='user' && $dd['type']=='multiselect') {
                echo "\$('#".$dd['variablename']."').prop('disabled',true);\n";
                echo "\$('#".$dd['variablename']."').trigger('chosen:updated');\n";
            }
        }
?>



<?php
}
if($selected_def_template>0) {
?>
    var templateselected = $('#settemplate').val();
    if(templateselected>0) {
        $('#permissions').prop('disabled',true);
        $('#groups').prop('disabled',true);
        $('#plugins').prop('disabled',true);
    }
<?php
}

if($action=='save' && $error==0) {
?>
    alertify.success('<?php echo __('Changes saved successfully');?>');
    $('#fop2reload').show();
<?php
} else  if($action=='delete' && $error==0) {
?>
    alertify.success('<?php echo sprintf(__("User %s deleted!"), $oldItem['exten']);?>');
    $('#fop2reload').show();
<?php
} else if($action=='add' && $error==0) {
?>
    alertify.success('<?php echo sprintf(__("User %s inserted!"), $oldItem['exten']);?>');
    $('#fop2reload').show();
<?php
} else if($action=='create' && $error==0) {
?>
    alertify.success('<?php echo sprintf(__("%s users added"), $users_added);?>');
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
<?php
include("footerbs.php");
