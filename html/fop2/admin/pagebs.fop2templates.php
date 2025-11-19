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

foreach($callbacks as $rawname=>$ff) {
    $fname = "${rawname}_populate_data";
    if(is_callable($fname)) {
        call_user_func($fname,$_POST);
    }
}
 
$action  = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$itemid  = isset($_REQUEST['itemid'])?$_REQUEST['itemid']:'';
$numpage = isset($_REQUEST['numpage'])?$_REQUEST['numpage']:'1';
$numpage = intval($numpage);
$error   = 0;

switch ($action) {
    case "add":
        $oldItem['name'] = $_POST['templatename'];
    	$result = fop2_add_template($_POST);
    	if (!$result) { $error=1; }
        $pdata = $_POST;
        $pdata['id'] = $result;
        foreach($callbacks as $rawname=>$ff) {
            $fname = "${rawname}_add_template";
            if(is_callable($fname)) {
                call_user_func($fname,$pdata);
            }
        }
    	break;
    case "delete":
    	$oldItem = fop2_del_template($itemid);
    	break;
    case "save":
    	$result = fop2_edit_template($itemid,$_POST);
    	if (!$result) { $error=1; }
        foreach($callbacks as $rawname=>$ff) {
            $fname = "${rawname}_edit_template";
            if(is_callable($fname)) {
                $pdata = $_POST;
                call_user_func($fname,$pdata);
            }
        }
    	break;
 
}

$botonesdefinidos = fop2_list_botones();
$templates        = fop2_list_templates();

?>

<div class='row' style='background-color:#78a300; padding-bottom:10px;'>
<div class="content">
<div class="col-md-8 col-sm-7 col-xs-6">
<span class='h2'><?php echo __('Templates');?></span>
<i style='vertical-align:super; top:-5px; color:#333;' class='ttip fa fa-info-circle'  data-toggle='popover' data-trigger='hover' data-placement='bottom' data-content='<?php echo __("A template is a set of permissions, groups and plugins that you can assign to users in one step inside the Users menu for easy and quick management."); ?>'></i>
</div>
<div class='col-md-4 col-sm-5 col-xs-6 text-right'>
<form method='post' action='<?php echo SELF?>'><div class='btn-group'><button type="submit" class="btn btn-default"><span class="fa fa-plus"></span></button><button class='btn btn-default'><?php echo __('Add Template')?></button></div></form>
</div>
</div>
</div>

<div class='row'>

<!-- left side menu -->
<div class="col-md-3">
<table class='table table-striped table-hover' style='margin-top:20px;'>
<tbody id='tabletemplates'>

<?php

$cont=0;

if(count($templates)>0) {
    foreach ($templates as $d) {
        $cont++;
        echo "<tr id='tr_".$d['id']."' style='cursor:pointer;'>";
        echo "<td id='td_".$d['id']."' class='clickable ".($itemid==$d['id'] ? 'open ':'')."'>{$d['name']}</td>";
        echo "<td class='".($itemid==$d['id'] ? 'open ':'')."text-right'>";
        echo "<div style='color:#d11; cursor:pointer;' class='ttip' data-toggle='popover' data-trigger='hover' data-placement='left' data-content='".sprintf(__('Delete Template %s'),$d['name'])."' onclick=\"setDelete('".urlencode($d['id'])."'); return false;\"><span class='fa fa-trash'></span></div>";
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
if ($action == 'delete') { $itemid=""; }


if(!$itemid || $itemid=='') {
    $hisGroups = array();
    $hisPlugins = array();
    $temp_name = '';
    $is_default = 0;
    $perms = '';
} else {
    $thisItem   = fop2_get_template($itemid);
    $hisGroups  = explode(",",$thisItem['groups']);
    $hisPlugins = explode(",",$thisItem['plugins']);
    $temp_name  = $thisItem['name'];
    $is_default = $thisItem['isdefault'];
    $perms      = $thisItem['permissions'];
}
$delURL = SELF.'?'.$_SERVER['QUERY_STRING'].'&action=delete';

?>

<form autocomplete="off" name="edit" class="form-horizontal" role='form' action="<?php echo SELF; ?>" method="post">
    <input type="hidden" name="action" id="faction" value="<?php echo ($itemid ? 'save' : 'add') ?>">
    <input type="hidden" id='fitemid' name="itemid" value="<?php echo $itemid; ?>">
    <input type="hidden" id='fnumpage' name="numpage" value="<?php echo $numpage; ?>">

<div class='section-title-container'>
<div class='h2 fhead' style='height: 55px; z-index: 1000;'><?php echo ($itemid ? sprintf(__('Edit Template %s'),$temp_name) : __('Add Template')); ?>
<div class="button-group pull-right">
    <button type="submit" class="btn btn-success" onclick="return edit_onsubmit();"><?php echo __('Submit Changes')?></button>
</div>
</div>
</div>

<div class='row'>
<div class="col-md-6">

<div class='fieldset'>
<h4><span>1. 
<?php echo __('Template Details')?>
</span></h4>
</div>

  <div class="form-group">
    <label for="templatename" class="col-sm-3 col-xs-12 control-label ttip" data-toggle='popover' data-trigger='hover' data-placement='right' data-content='<?php echo __("The name for this template.")?>'><?php echo __("Name")?></label>
    <div class="col-xs-12 col-sm-8">
        <input type="text" id="templatename" class="form-control" name="templatename" value="<?php echo htmlspecialchars($temp_name); ?>" <?php echo isset($thisItem['name']) ? 'disabled' : '' ?> placeholder="<?php echo __("Name")?>" >
    </div>
  </div>

  <div class="form-group">
    <div class="col-xs-3 col-sm-3">
        <input id="makedefault" class="pull-right" type=checkbox name='makedefault' <?php  echo ($is_default=="1")?'checked="checked"':'' ?>>
    </div>
    <div class="col-sm-9 col-xs-9">
    <span class="control-label ttip" data-toggle='popover' data-trigger='hover' data-placement='right' data-content='<?php echo __("Assign this template by default when adding new users.")?>'><?php echo __("Make Default")?></span>
    </div> 
  </div>

</div>

<div class="col-md-6">

<div class='fieldset'>
<h4>
<span>2.  <?php echo __('Permissions')?></span>
</h4>
</div>

  <div class="form-group">
    <label for="permissions" class="col-sm-3 col-xs-12 control-label ttip" data-toggle='popover' data-trigger='hover' data-placement='right' data-content='<?php echo __('Choose the permission you want to grant to the user.')?>'><?php echo __('Permissions')?>
    </label>
        <div class="col-sm-8 col-xs-12">
        <select name='permissions[]' id="permissions" class='chosen-select form-control' multiple data-create_option_text="<?php echo __('Create option');?>" data-placeholder="<?php echo __('(pick permission)');?>">
<?php
	$stock_perms = fop2_permissions();
	$cust_perm   = fop2_custom_permissions();
    $simple_cust_perm = array();
	foreach ($cust_perm as $perm) { $simple_cust_perm[]=$perm['name']; }
	$permisos_activos = explode(",",$perms);
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
<p><?php echo __('Select the groups you want the user to see in the panel. If no groups are selected the user will be able to see all buttons.');?></p>

<?php

    $groupsdb           = fop2_list_groups(0);

    if(!is_array($groupsdb)) { $groupsdb = array(); }

    $groups = array_merge($predefined_groups,$groupsdb);

    foreach ($groups as $count => $datarray) {
        echo "<div class='checkbox'>";
        echo "<label><input type=checkbox name='includebot[]' value='".$datarray['id']."'";

        if(in_array($datarray['id'],$hisGroups)) {
            echo " checked ";
        }

        echo "> ".__($datarray['name']);
        echo "</label></div>";
    }

?>
  </div>
</div>

<?php
   $results= fop2_list_plugins();
   if(count($results)>0){

?>

<div class="col-md-6"> 
 
<div style='height:20px;'>&nbsp;</div> 
<div class='fieldset'> 
<h4> 
<span>4. <?php echo __('Plugins')?></span> 
</h4> 
</div> 
 
<div style='padding:0 10px;'> 
<p><?php echo __('Select the plugins you want to load for this user.'); ?></p> 
 
<?php 
       foreach($results as $result) { 
        echo "<div class='checkbox'>"; 
        echo "<label><input type=checkbox name='includeplugin[]' value='".$result['rawname']."'"; 
 
        if(in_array($result['rawname'],$hisPlugins) || $result['global']==1) { 
            echo " checked='checked' "; 
            if($result['global']==1) { echo " disabled='disabled' "; } 
        } 
 
        echo"> ".$result['name']; 
        echo "</label></div>"; 
     
       } 
}  
?> 
</div>
</div>
</div>

<hr/>

<?php
if(!isset($cust_fields)) $cust_fields=array();
$showcust=0;
$cust_fields_template=array();
foreach($cust_fields as $idx=>$dd) {
    if($dd['template']==true) {
        $cust_fields_template[]=$dd;
        $showcust++;
    }
}

if($showcust>0) {
// show custom fields in templates only if defined 
?>
<div class='row'>

<div class="col-md-6">

<div style='height:20px;'>&nbsp;</div> 
<div class='fieldset'> 
<h4> 
<span>5. <?php echo __('Extra Fields')?></span> 
</h4> 
</div> 

<div style='padding:0 10px;'>
<p><?php echo __('Select extra field defaults for this template'); ?></p>

<?php
foreach($cust_fields_template as $idx=>$dd) {
echo render_custom_form_field($dd);
}
?>

</div>
 
</div>

<div class="col-md-6">
</div>

</div>
<?php } ?>


</form>


</div>
</div>

<div class="push"></div>
</div>

<script>
<!--

var theForm = document.edit;
theForm.templatename.focus();

function setSave(userid) {
     numpage = $('#myPager').find('li.active')[0].innerText;
     $('#faction').val('save');
     $('#fnumpage').val(numpage);
     theForm.submit();
}

function setEdit(userid) {
     numpage = $('#myPager').find('li.active')[0].innerText;
     $('#faction').val('edit');
     $('#fitemid').val(userid);
     $('#fnumpage').val(numpage);
     theForm.submit();
}

function setDelete(userid) {
     alertify.confirm('','<?php echo __('Are you sure?'); ?>', function(e) {
             numpage = $('#myPager').find('li.active')[0].innerText;
             $('#fnumpage').val(numpage);
             $('#fitemid').val(userid);
             $('#faction').val('delete');
             theForm.submit();
     }, function() {
     }).set({
        labels: {
            ok: '<?php echo __('Accept');?>',
            cancel: '<?php echo __('Cancel');?>'
        },
        closable: false
     });
}

function edit_onsubmit() {
    var msgEmptyUserId = "<?php echo __('Please enter a template name.')?>";
    if (isEmpty(theForm.templatename.value)) {
        alertify.error(msgEmptyUserId);
        theForm.templatename.focus();
        return false;
    }

    return true;
}

$(document).ready(function() {
  $('#tabletemplates').pageMe({pagerSelector:'#myPager',showPrevNext:true,hidePageNumbers:false,perPage:<?php echo $perpage;?>,numbersPerPage:4,curPage:<?php echo $numpage;?>});

<?php
if($action=='save' && $error==0) {
?>
    alertify.success('<?php echo __('Changes saved successfully');?>');
    $('#fop2reload').show();
<?php
} else if($action=='delete') {
?>
    alertify.success('<?php echo sprintf(__("Template %s deleted!"), $oldItem['name']);?>');
    $('#fop2reload').show();
<?php
} else if($action=='add' && $error==0) {
?>
    alertify.success('<?php echo sprintf(__("Template %s inserted!"), $oldItem['name']);?>');
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
