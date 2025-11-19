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
$sorting="";

//if submitting form, update database
switch ($action) {
case "saveField":
    //$result = fop2_fieldedit($_POST);
    $partes = preg_split("/_/",$_POST['field']);
    $field = $partes[0];
    $id    = $partes[1];
    if($field=='excludebot') { $field='exclude'; }
    $query    = "UPDATE fop2contexts SET %s='%d' WHERE id='%d'";
    $db->consulta($query,array($field,$_POST['value'],$id));
    die();
    break;
}


?>

<div class='row' style='background-color:#78a300; padding-bottom:10px;'>
<div class="content">
<div class="col-md-4">
<span class='h2'><?php echo __('Tenants');?></span>
<i style='vertical-align:super; top:-5px; color:#333;' class='ttip fa fa-info-circle' data-toggle='popover' data-trigger='hover' data-placement='bottom' data-content='<?php echo __('You can enable or disable FOP2 for particular tenats from this page.'); ?>'></i>
</div>
<div class="col-md-8 text-right">


</div>
</div>
</div>

<form autocomplete="off" name="edit" action="<?php echo SELF ?>" method="post" onsubmit="return edit_onsubmit();">
<input type="hidden" name="action"   value="edit">

<div id='btncontainer'>

<?php

    $allowed='';
    $allowed_tenants = isset($_SESSION[MYAP]['AUTHVAR']['allowed_tenants'])?$_SESSION[MYAP]['AUTHVAR']['allowed_tenants']:'';
    if($allowed_tenants<>'') {
        $allowed = "AND id IN ($allowed_tenants)";
    }

    echo "<table class='fop2 table' id='fop2contextstable'>\n";
    echo "<tbody>\n";

    $results = $db->consulta("SELECT * FROM fop2contexts WHERE 1=1 $allowed ORDER BY context");
    $cont=0;
    while ($datarray = $db->fetch_assoc($results)) {
 
            $myitemid = $datarray['id'];

            echo "<tr id='listitem_".$myitemid."'>\n";
            echo "<td>";
            echo "<div class='col-md-5'>\n";
            echo $datarray['context'];
            echo "</div>";
            echo "<div class='col-md-5'>\n";
            echo $datarray['name'];
            echo "</div>";

            echo "<div class='col-md-2'>\n";
            // Campo Enable/Disabled
            echo "<a href='#' tabindex=-1 class='ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-placement='top' ";
            echo "data-trigger='hover' data-content='".__('Enable or disable this button from the panel view.')."'>".__('Enabled');
            echo "</a> <br/>";
            echo "<input type='checkbox' data-on-text='".__('No')."' data-off-text='".__('Yes')."' ";
            echo "data-off-color='success' data-on-color='danger' ";
            if($datarray['exclude']==1) { echo " checked "; }
            echo " name='excludebot_".$datarray['id']."' id='excludebot_".$datarray['id']."' class='chk editable' />\n";
            echo "</div>\n";

            echo "</td></tr>";
     }

    echo "</tbody>\n</table>\n";


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
}

</script>


<div id='ajaxstatus'><?php echo __('Saving...');?></div>
<div class="push"></div>
</div>
<?php
include("footerbs.php");

