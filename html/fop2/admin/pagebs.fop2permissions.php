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

$action  = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$itemid  = isset($_REQUEST['itemid'])?$_REQUEST['itemid']:'';
$numpage = isset($_REQUEST['numpage'])?$_REQUEST['numpage']:'1';
$numpage = intval($numpage);
$error   = 0;

switch ($action) {
    case "add":
        $oldItem['name'] = $_POST['name'];
        $result = fop2_add_perm($_POST);
        if (!$result) { $error=1; }
        break;
    case "delete":
        $oldItem = fop2_del_perm($itemid);
        break;
    case "save":
        $result = fop2_edit_perm($itemid,$_POST);
        if (!$result) { $error=1; }
        break;
}

$perms = fop2_custom_permissions();

?>

<div class='row' style='background-color:#78a300; padding-bottom:10px;'>
<div class="content">
<div class="col-md-8 col-sm-7 col-xs-6">
<span class='h2'><?php echo __('Restricted Permissions');?></span>
<i style='vertical-align:super; top:-5px; color:#333;' class='ttip fa fa-info-circle' data-toggle='popover' data-trigger='hover' data-placement='bottom' data-content='<?php echo __("Restricted permissions lets you limit actions to a specific set of extensions/groups. They are only used for restricting actions. If you want a quick way to assign permissions, then you should use Templates as restricted permissions will limit user actions."); ?>'></i>
</div>
<div class='col-md-4 col-sm-5 col-xs-6 text-right'>
<form method='post' action='<?php echo SELF?>'><div class='btn-group'><button type="submit" class="btn btn-default"><span class="fa fa-plus"></span></button><button class='btn btn-default'><?php echo __('Add Restricted Permission')?></button></div></form>
</div>
</div>
</div>

<div class='row'>

<!-- left side menu -->
<div class="col-md-3">
<table class='table table-striped table-hover' style='margin-top:20px;'>
<tbody id='tablepermissions'>

<?php
$cont=0;
if (isset($perms)) {
    foreach ($perms as $d) {
        $cont++;
        echo "<tr id='tr_".$d['id']."' style='cursor:pointer;'>";
        echo "<td id='td_".$d['id']."' class='clickable ".($itemid==$d['id'] ? 'open ':'')."'>{$d['name']}</td>";
        echo "<td class='".($itemid==$d['id'] ? 'open ':'')."text-right'>";
        echo "<a style='color:#d11; cursor:pointer;' class='ttip' data-toggle='popover' data-trigger='hover' data-placement='left' data-content='".sprintf(__('Delete Restricted Permission %s'),$d['name'])."' onclick=\"setDelete('".urlencode($d['id'])."'); return false;\"><span class='fa fa-trash'></span></a>";
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
    $perm_name = '';
    $perms = '';
} else {
    $thisItem   = fop2_get_perm($itemid);
    $hisGroups  = fop2_get_perm_groups($thisItem['name']);
    $perm_name  = $thisItem['name'];
    $perms      = $thisItem['permissions'];
}

?>

<form autocomplete="off" name="edit" action="<?php echo SELF; ?>" method="post" class="form-horizontal">
    <input type="hidden" name="action" id="faction"  value="<?php echo ($itemid ? 'save' : 'add') ?>">
    <input type="hidden" id='fitemid' name="itemid" value="<?php echo $itemid; ?>">
    <input type="hidden" id='fnumpage' name="numpage" value="<?php echo $numpage; ?>">

<div class='section-title-container'>
<div class='h2 fhead' style='height: 55px; z-index: 1000;'><?php echo ($itemid ? sprintf(__('Edit Restricted Permission %s'),$perm_name) : __('Add Restricted Permission')); ?>
<div class="button-group pull-right">
    <button type="submit" class="btn btn-success" onclick="return edit_onsubmit();"><?php echo __('Submit Changes')?></button>
</div>
</div>
</div>


<div class='row'>
<div class="col-md-6">

<div class='fieldset'>
<h4><span>1. 
<?php echo __('Restricted Permission Details')?>
</span></h4>
</div>

<div class="form-group">
    <label class="col-sm-3 col-xs-12 control-label ttip" for="permname" data-toggle='popover' data-trigger='hover' data-placement='right' data-content='<?php echo __("The name for this permission.")?>'><?php echo __("Name")?></label>
    <div class="col-sm-8 col-xs-12">
        <input type="text" name="name" id="permname" class="form-control" maxlength="30" value="<?php echo htmlspecialchars($perm_name); ?>" <?php echo isset($thisItem['name']) ? 'disabled' : '' ?> >
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
	$permisos_activos = explode(",",$perms);
    if(!is_array($permisos_activos)) { $permisos_activos=array(); }

    $merged_perm = array_merge($permisos_activos,$stock_perms);
    // No , we do not want custom perms on the sutom perms page
    // $merged_perm = array_diff($merged_perm,$simple_cust_perm);
    $merged_perm = array_unique($merged_perm);
    $merged_perm = array_filter($merged_perm);
    asort($merged_perm);

    // We do not want custom permissions on the custom permissions page
    
	foreach ($merged_perm as $perm) {
        if(in_array($perm,$permisos_activos)) { $selected=' selected="selected" '; } else { $selected=''; }
        if($perm<>'phonebook' && $perm<>'preferences' && $perm<>'all' && $perm<>'recordself' && $perm<>'hangupself' && $perm<>'dial' && $perm<>'queueagent' && $perm<>'broadcast' && $perm<>'smsmanager') {
		    echo "            <option value='".$perm."' ".$selected.">".$perm."</option>\n";
        }
	}
?>
        </select>
  </div>
  </div>
  </div>
</div>

<div class='row'>
<div class="col-md-12">

<div class='fieldset'>
<h4>
<span>3. <?php echo __('Restrict to Groups')?></span>
</h4>
</div>

<div style='padding:0 10px;'>
<?php 

echo "<p>".__("Select the groups you want to restrict this permission to. You will be able to perform the actions only to the extension/buttons that are part of the selected groups. If no groups are selected, the permission will be allowed to every button on the panel.")."</p>"; 

$groupsdb           = fop2_list_groups(0);

if(!is_array($groupsdb)) {
    $groupsdb = array();
}

$groups = array_merge($predefined_groups,$groupsdb);

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

</div> 
<hr/> 
 
</form> 
 
 
</div> 
</div> 

<div class="push"></div>
</div>
<script>
<!--

var theForm = document.edit;
theForm.permname.focus();

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
             $('#faction').val('delete');
             $('#fitemid').val(userid);
             $('#fnumpage').val(numpage);
             theForm.submit();
     }, function() { //cancel
     }).set({
        labels: {
            ok: '<?php echo __('Accept');?>',
            cancel: '<?php echo __('Cancel');?>'
        },
        closable: false
     });
}

function edit_onsubmit() {
    <?php $stockpermstring = implode("','",$stock_perms); ?>
    var reserved = [ '<?php echo $stockpermstring; ?>' ];

    if (reserved.indexOf(theForm.permname.value)>=0) {
        alertify.error("<?php echo __('That name is reserved for stock permissions, choose another one.')?>");
        return false;
    }

    if (isEmpty(theForm.permname.value)) {
        alertify.error("<?php echo __('Please insert a permission name.')?>");
        theForm.permname.focus();
        return false;
    }

    return true;
}

$(document).ready(function() {
  $('#tablepermissions').pageMe({pagerSelector:'#myPager',showPrevNext:true,hidePageNumbers:false,perPage:<?php echo $perpage;?>,numbersPerPage:4,curPage:<?php echo $numpage;?>});

<?php
if($action=='save' && $error==0) {
?>
    alertify.success('<?php echo __('Changes saved successfully');?>');
    $('#fop2reload').show();
<?php
} else if($action=='delete') {
?>
    alertify.success('<?php echo sprintf(__("Restricted Permission %s deleted!"), $oldItem['name']);?>');
    $('#fop2reload').show();
<?php
} else if($action=='add' && $error==0) {
?>
    alertify.success('<?php echo sprintf(__("Restricted Permission %s inserted!"), $oldItem['name']);?>');
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

