<?php

$levels = get_fop2manager_secure_levels();

/*
$levels = array();
$query="SELECT level,detail FROM fop2managersecurelevel";
$res = $db->consulta($query);
if($db->num_rows($res)>0) {
   while($row=$db->fetch_assoc($res)) {
       $levels[$row['detail']]=$row['level'];
   }
}
*/

if(!isset($secure_required_levels)) {

    $archi = isset($page_to_menu[basename(SELF)])?$page_to_menu[basename(SELF)]:'';
    $query="SELECT level FROM fop2manageracl WHERE resource = '$archi'";
    $res = $db->consulta($query);

    if($db->num_rows($res)==0) {
        if($archi<>'') {
            $query="INSERT INTO fop2manageracl (resource,level) values ('%s','%d')";
            $reslevel = array_sum($levels);
            $db->consulta($query,array("$archi",$reslevel)); 
        } else {
            $reslevel=0;
        }
    } else {
        $row = $db->fetch_row($res);
        $reslevel = $row[0];
    }

    if($reslevel<=0) $reslevel=255;

    $query="SELECT detail FROM fop2managersecurelevel WHERE $reslevel & level";
    $res = $db->consulta($query);
    while($row=$db->fetch_row($res)) {
        $secure_required_levels[] = $row[0];
    }


}

if(isset($logout) && $logout==1) {
   flush_session();
   header("Location: ".SELF);
}

$islogged = is_logged_in();

if($islogged<2) {
    if($islogged==1) {
        $error=__('Not enough privileges');
    }
    include("login_form.php");
    exit;
}
