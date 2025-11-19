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

if(isset($_POST['action'])) {
    if($_POST['action']=='save') {
        if($_POST['value']>0) {
            admin_edit_acl(intval($_POST['id']),intval($_POST['value']));
        }
        die();
    }
}
?>
<div class='row' style='background-color:#78a300; padding-bottom:10px;'>
<div class="content">
<div class="col-md-12">
<span class='h2'><?php echo __('Access Control');?></span>
<i style='vertical-align:super; top:-5px; color:#333;' class='ttip fa fa-info-circle'  data-toggle='popover' data-trigger='hover' data-placement='bottom' data-content='<?php echo __('From here you can define what roles are needed to access different resources on Panel Manager.'); ?>'></i>
</div>
</div>
</div>

<div class='row' style='height:1em;'></div>

<div id="acllist">
<div class="category" id="category_Online">

<div>
<div class='row head' style='background-color:#f2f3f4; margin:0; padding:10px; z-index:2;' id='tablehead'>
<div class='col-md-8'><?php echo __('Resource');?></div>
<div class='col-md-4'><?php echo __('Required Roles');?></div>
</div>
</div>
<div><div class='row'><div class='col-md-12'>&nbsp;</div></div></div>
<?php

$query = "SELECT * FROM fop2manageracl ORDER BY resource";
$res = $db->consulta($query);

$cont=1;
while($row = $db->fetch_assoc($res)) {
    if($cont%2) { $bgcolor='#ddd'; } else { $bgcolor='#B4DBB3'; }
    $cont++;
    $id       = $row['id'];
    $resource = $row['resource'];
    $level    = $row['level'];

    if($resource=='') { continue; }

    $partes = preg_split("/\//",$resource);
    $partos = array_map("__",$partes);
    $resource = implode("/",$partos);

        echo "<div class='row' style='background-color:$bgcolor; margin:0; padding:10px;'>\n";
        echo "<div class='col-md-8'>$resource</div>\n";
        echo "<div class='col-md-4'>";
        echo "<SELECT name='level' id='level_$id' class='chosen-select-create form-control' multiple data-placeholder='".__('(pick one)')."' onchange='acl_change(this);'>";
        foreach($levels as $tokname=>$idlevel) {
         
            if($level & $idlevel) {
                $selected = ' selected ';
            } else {
                $selected = ' ';
            }
            echo "<option value='$idlevel' $selected>$tokname</option>";
        }

        echo "</select>";
        echo "</div>\n";
        echo "</div>\n";

        echo "<div class='row' style='height:1em;'></div>";
    }
    echo "</div></div>\n";

?>
</div>
<script>
$(document).ready(function() {
    iseditor = new Object();

});

$(".main a[href^='http://']").attr("target","_blank");

var msgEmpty = "<?php echo __('Value cannot be empty.')?>";

function acl_change(el) {
    acl_id = $(el).attr('id').substr(6);
    values = $(el).val();
    var sum=0;
    console.log(typeof values);
    if(values !== null) {
        for (var i = values.length; !!i--;){
            sum += parseInt(values[i]);
        }
    }
    if(sum>0) {
        var data = "action=save&id="+acl_id+"&value="+sum;
        $.post(window.location.href, data, function(data) { });
    } else {
        alertify.error(msgEmpty);
    }
    
}

</script>

<?php
include("footerbs.php");
