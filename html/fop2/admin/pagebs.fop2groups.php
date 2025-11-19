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
        $result = fop2_add_group($_POST);
        if (!$result) { $error=1; }
        break;
    case "delete":
        $oldItem = fop2_del_group($itemid);
        break;
    case "save":
        $result = fop2_edit_group($itemid,$_POST);
        if (!$result) { $error=1; }
        break;
}

$botonesdefinidos = fop2_list_botones();
$groupsdb         = fop2_list_groups(0);

?>

<div class='row' style='background-color:#78a300; padding-bottom:10px;'>
<div class="content">
<div class="col-md-8 col-sm-7 col-xs-6">
<span class='h2'><?php echo __('Groups');?></span>
<i style='vertical-align:super; top:-5px; color:#333;' class='ttip fa fa-info-circle' data-toggle='popover' data-trigger='hover' data-placement='bottom' data-content='<?php echo __("Groups can be used to restrict the buttons a user should see in his panel. You can create a group with only 5 extensions and assign it to a user in the Users tab, so he can only see those extensions."); ?>'></i>
</div>
<div class='col-md-4 col-sm-5 col-xs-6 text-right'>
<form method='post' action='<?php echo SELF?>'><div class='btn-group'><button type="submit" class="btn btn-default"><span class="fa fa-plus"></span></button><button class='btn btn-default'><?php echo __('Add Group')?></button></div></form>
</div>
</div>
</div>

<div class='row'>

<!-- left side menu -->
<div class="col-md-3">
<table class='table table-striped table-hover' style='margin-top:20px;'>
<tbody id='tablegroups'>
<?php

$default_groups="";
foreach($predefined_groups as $iid=>$grparray) {
   $default_groups.="'".__($grparray['name'])."',";
}
$default_groups=substr($default_groups,0,-1);

$groups = array_merge($predefined_groups,$groupsdb);

$cont=0;
if (isset($groups)) {
    foreach ($groups as $d) {
        $cont++;
        if(in_array($d,$predefined_groups)) { $edit_prefix='no'; } else { $edit_prefix='td'; }
        echo "<tr ".($edit_prefix=='td' ? 'style="cursor:pointer;"':'').">";
        echo "<td id='".$edit_prefix."_".$d['id']."' class='clickable ".($itemid==$d['id'] ? 'open ':'')."'>".__($d['name'])."</td>";
        echo "<td class='".($itemid==$d['id'] ? 'open ':'')."text-right'>";
        if(in_array($d,$predefined_groups)) {
            echo "&nbsp;";
        } else {
            echo "<a style='color:#d11; cursor:pointer;' class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='left' data-content='".sprintf(__('Delete Group %s'),$d['name'])."' onclick=\"setDelete('".urlencode($d['id'])."'); return false;\"><span class='fa fa-trash'></span></a>";
        }
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

$thisItem   = fop2_get_group($itemid);

if($thisItem) {
    $hisButtons = fop2_get_group_buttons($thisItem['name']);
} else {
    $hisButtons=array();
}

?>

<form autocomplete="off" name="edit" action="<?php echo SELF; ?>" method="post" class="form-horizontal">
    <input type="hidden" name="action" id="faction" value="<?php echo ($itemid<>'' ? 'save' : 'add') ?>">
    <input type="hidden" name="itemid" id="fitemid" value="<?php echo $itemid; ?>">
    <input type="hidden" id='fnumpage' name="numpage" value="<?php echo $numpage; ?>">


<div class='section-title-container'>
<div class='h2 fhead' style='height: 55px; z-index: 1000;'><?php echo ($itemid<>'' ? sprintf(__('Edit Group %s'),$thisItem["name"]) : __('Add Group')); ?>
<div class="button-group pull-right">
    <button type="submit" class="btn btn-success" onclick="return edit_onsubmit();"><?php echo __('Submit Changes')?></button>
</div>
</div>
</div>

<div class='row'>
<div class="col-md-12">

<div class='fieldset'>
<h4><span>1. 
<?php echo __('Group Details')?>
</span></h4>
</div>

<div class="form-group">
     <label for="name" class="col-sm-3 control-label ttip" data-delay='{"show":"750", "hide":"100"}' data-toggle='popover' data-trigger='hover' data-placement='right' data-content='<?php echo __("The name for this group.")?>'><?php echo __("Name")?></label>
     <div class="col-xs-6">
        <input type="text" class="form-control" name="name" id="name" value="<?php echo htmlspecialchars(isset($thisItem['name']) ? $thisItem['name'] : ""); ?>" >
     </div>
</div>
</div>

</div>

<div class='row'>
<div class="col-md-12">

<div class='fieldset'>
<h4>
<span>2.  <?php echo __('Included Buttons')?></span>
</h4>
</div>

<div style='padding:0 10px;'>
<p><?php echo __("Select the buttons you want to include in this group."); ?></p>

<table class='fop2 table'>
<?php

$fopbutton = fop2_all_buttons();

$buttypes = array('extensions','queues','conferences','trunks','parks','ringgroups');

foreach ($buttypes as $tipo) {
$tipocorto = substr($tipo,0,-1);
$cuantos[$tipocorto]=0;
}

foreach ($fopbutton as $devname => $datarray) {
if(!isset($cuantos[$datarray['type']])) { $cuantos[$datarray['type']] = 0; }
$cuantos[$datarray['type']]++;
}

foreach ($buttypes as $tipo) {

$tipocorto = substr($tipo,0,-1);
$catname   = ucfirst($tipo);

if($cuantos[$tipocorto]<=0) { continue; }

if($tipocorto<>"extension") {
    // Separador
    echo "<tr><td colspan='3'>&nbsp;</td></tr>";
}

// Encabezado de Categoria
echo "<tr>\n";
echo "<td colspan='2'><strong>".__($catname)."</strong></td>";
echo "<td>";
if($tipocorto=="extension") {
    echo "<a href='javascript:checkAll();'>".__("Check All")."</a>\n";
} else {
    echo "&nbsp;";
}
echo "</td>";
echo "</tr>";

// Todos las extensiones
foreach ($fopbutton as $devname => $datarray) {
    if($datarray['type']==$tipocorto) {

        echo "<tr>";
        echo "<td>".($datarray['exten']==-1?'n/a':$datarray['exten'])."</td>";
        echo "<td>".$datarray['name']."</td>";
        echo "<td><input type=checkbox name='includebot[]' value='".$devname."'";

        if(in_array($datarray['devid'],$hisButtons)) {
            echo " checked ";
        }

        echo"></td>";
        echo "</tr>";
    }
}
}

?>
    </table>
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
theForm.name.focus();

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
         },
         function() {
         }
     ).set({
        labels: {
            ok: '<?php echo __('Accept');?>',
            cancel: '<?php echo __('Cancel');?>'
        },
        closable: false
     });
}

function checkAll() {
    field = document.edit;
    for (i = 0; i < field.length; i++) {
        field[i].checked = !field[i].checked;
    }
}

function edit_onsubmit() {

    var reserved = [ <?php echo $default_groups; ?> ];

    if (reserved.indexOf(theForm.name.value)>=0) {
        alertify.error("<?php echo __('That name is reserved for default groups, choose another one.')?>");
        return false;
    }


    var msgEmptyGroupName = "<?php echo __('Please enter a group name.')?>";
    if (isEmpty(theForm.name.value)) {
        alertify.error(msgEmptyGroupName);
        return false;
    }

    return true;
}

$(document).ready(function() {
  $('#tablegroups').pageMe({pagerSelector:'#myPager',showPrevNext:true,hidePageNumbers:false,perPage:<?php echo $perpage;?>,numbersPerPage:4,curPage:<?php echo $numpage;?>});

<?php
if($action=='save' && $error==0) {
?>
    alertify.success('<?php echo __('Changes saved successfully');?>');
    $('#fop2reload').show();
<?php
} else if($action=='delete') {
?>
    alertify.success('<?php echo sprintf(__("Group %s deleted!"), $oldItem['name']);?>');
    $('#fop2reload').show();
<?php
} else if($action=='add' && $error==0) {
?>
    alertify.success('<?php echo sprintf(__("Group %s inserted!"), $oldItem['name']);?>');
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
include("footerbs.php");
