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
$filter = isset($_REQUEST['filter'])?$_REQUEST['filter']:'';
$error   = 0;

switch ($action) {
    case "add":
        $oldItem['user'] = $_POST['user'];
        $result = admin_add_user($_POST);
        if (!$result) { $error=1; }
        break;
    case "delete":
        $oldItem = admin_get_user($itemid);
        admin_del_user($itemid);
        break;
    case "save":
        $result = admin_edit_user($itemid,$_POST);
        if (!$result) { $error=1; }
        break;
}

$adminusers = admin_list_users();

$iconclass=get_fop2manager_secure_levels_icons();

?>

<div class='row' style='background-color:#78a300; padding-bottom:10px;'>
<div class="content">
<div class="col-md-8 col-sm-7 col-xs-6">
<span class='h2'><?php echo __('Panel Manager Users');?></span>
<i style='vertical-align:super; top:-5px; color:#333;' class='ttip fa fa-info-circle' data-toggle='popover' data-trigger='hover' data-placement='bottom' data-content='<?php echo __('From here you can manage Panel Manager users and their role'); ?>'></i>
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
if (count($adminusers)>0) {
    foreach ($adminusers as $d) {
        $cont++;
        echo "<tr id='tr_".$d['id']."' style='cursor:pointer;'>";
        echo "<td id='td_".$d['id']."' class='clickable ".($itemid==$d['id'] ? 'open ':'')."'>";
        echo "<i class='".$iconclass["{$d['level']}"]."'></i>&nbsp;";
        echo "{$d['user']}</td>";
        echo "<td class='".($itemid==$d['id'] ? 'open ':'')."text-right'>";
        echo "<a style='color:#d11; cursor:pointer;' class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='left' data-content='".sprintf(__('Delete User %s'),$d['user'])."' onclick=\"setDelete('".urlencode($d['id'])."'); return false;\"><span class='fa fa-trash'></span></a>"; 
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

$thisItem    = admin_get_user($itemid);
$thisTenants = admin_get_user_tenants($itemid);
$mytenants   = array();
if(is_array($thisTenants)) {
    foreach($thisTenants as $id=>$val) {
        $mytenants[]=$val;
    }
}

$hisRole = isset($thisItem)?$thisItem['level']:'';

$fop2contexts = fop2_get_contexts();


?>

<form autocomplete="off" name="edit" id="edit" class="form-horizontal" role="form" action="<?php echo SELF; ?>" method="post" >
    <input type="hidden" id='faction' name="action" value="<?php echo ($itemid ? 'save' : 'add') ?>">
    <input type="hidden" id='fitemid' name="itemid" value="<?php echo $itemid; ?>">
    <input type="hidden" id='fnumpage' name="numpage" value="<?php echo $numpage; ?>">
    <input type="hidden" id='ffilter' name="filter" value="<?php echo $filter; ?>">
    <input type="hidden" id='perpage' name="perpage" value="<?php echo $perpage; ?>">


<div class='section-title-container'>
<div class='fhead h2' style='height: 55px; z-index: 1000;'><?php echo ($itemid ? sprintf(__('Edit User %s'),$thisItem["user"]) : __('Add User')); ?>
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

  <div class="form-group">
    <label for="user" class="col-sm-3 col-xs-12 control-label ttip" data-delay='{"show":"750", "hide":"100"}' data-toggle='popover' data-trigger='hover' data-placement='right' data-content='<?php echo __('The login name for this user.')?>'><?php echo __('Login')?></label>
    <div class="col-sm-8 col-xs-12">
      <input type="text" class="form-control" id="user" name="user" placeholder="<?php echo __('Login')?>" value="<?php echo isset($thisItem['user']) ? htmlspecialchars($thisItem['user']) : '' ?>" >
    </div>
  </div>
  <div class="form-group">
    <label for="secret" class="col-sm-3 col-xs-12 control-label ttip" data-delay='{"show":"750", "hide":"100"}' data-toggle='popover' data-trigger='hover' data-placement='right' data-content='<?php echo __('The secret to login to Panel Manager.')?>'><?php echo __('Secret')?></label>
    <div class="col-sm-8 col-xs-12">
      <input type="password" class="form-control" id="secret" name="secret" placeholder="<?php echo __('Secret')?>" value="<?php echo htmlspecialchars(isset($thisItem['secret']) ? $thisItem['secret'] : ''); ?>">
    </div>
  </div>
  <div class="form-group">
    <label for="role" class="col-sm-3 col-xs-12 control-label ttip" data-delay='{"show":"750", "hide":"100"}' data-toggle='popover' data-trigger='hover' data-placement='right' data-content='<?php echo __('Choose the user role.')?>'><?php echo __('Role')?></label>
    <div class="col-sm-8 col-xs-12">
            <select class='form-control chosen-select' id="role" name='role'>
                 <option value="0"><?php echo __('(pick role)')?></option>
                 <?php
        foreach ($levels as $d=>$i) {
            echo "<option value='".$d."' ";
            if($itemid) {
                if($hisRole == $d) {
                    echo " selected ";
                }
            }
            echo ">".$d."</option>\n";
        }
?>
            </select>

   </div>
  </div>

</div>
<div class="col-md-6">

<?php
if($cuantoscontexts>0) { 
?>
<div class='fieldset'>

<h4>
<span>2.  <?php echo __('Tenants')?></span>
</h4>
</div>

<p><?php echo __('Choose which tenants this user will be able to see. If none selected, then the user will be able to see all tenants.'); ?></p>

  <div class="form-group">
      <div class="col-sm-12 col-xs-12">
        <select name='tenants[]' id="tenants" class='chosen-select-create form-control' multiple data-placeholder="<?php echo __('(pick tenant)');?>">
<?php

    foreach ($fop2contexts as $tenantid=>$tenantname) {
        if(in_array($tenantid,$mytenants)) { $selected = ' selected '; } else { $selected=''; } 
        echo "         <option value='".$tenantid."' ".$selected.">".$tenantname."</option>\n";
    }

?>
        </select>
  </div>
</div>

</div>
<?php } ?>
</div>


<div class="push"></div>

</div>

<?php

$adminusers = admin_get_users();

echo "
<script>

var theForm = document.edit;
theForm.user.focus();

";

$adminusers = admin_get_users();

$fusers = array();
$fusersstring = "";
if(is_array($adminusers)) {
    foreach ($adminusers as $index) {
        $fusers[]="'".$index['user']."'";
    }
    $fusersstring=join(",",$fusers);
}
echo "var adminusers = [ ".$fusersstring. " ];\n";
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

function edit_onsubmit() {

    if(theForm.faction.value=='add') { 

        var msgEmptyRole = "<?php echo __('Please select a role for this user.')?>";
        var msgEmptyLogin = "<?php echo __('Please insert a login name.')?>";
        var msgAlreadyThere = "<?php echo __('That user already exists.')?>";
        var msgEmptyPassword = "<?php echo __('Please fill the password')?>";

        if (adminusers.indexOf(theForm.user.value)>=0) {
            alertify.error(msgAlreadyThere);
            theForm.user.focus();
            return false;
        }

        if (isEmpty(theForm.user.value)) {
            alertify.error(msgEmptyLogin);
            theForm.user.focus();
            return false;
        }

        if (isEmpty(theForm.secret.value)) {
            alertify.error(msgEmptyPassword);
            theForm.secret.focus();
            return false;
        }
        if($('#role')[0].selectedIndex==0) {
            alertify.error(msgEmptyRole);
            theForm.role.focus();
            return false;
        }

        return true;

    } else {
        return true;
    }
    return false;
}

$(document).ready(function() {


<?php

if($action=='save' && $error==0) {
?>
    alertify.success('<?php echo __('Changes saved successfully');?>');
<?php
} else  if($action=='delete' && $error==0) {
?>
    alertify.success('<?php echo sprintf(__("User %s deleted!"), $oldItem['user']);?>');
<?php
} else if($action=='add' && $error==0) {
?>
    alertify.success('<?php echo sprintf(__("User %s inserted!"), $oldItem['user']);?>');
<?php
} 
?>

});

//-->
</script>
<?php
include("footerbs.php");
