<?php

function fop2_get_defplugin() {
    global $active_modules, $db, $panelcontext;

    if($panelcontext<>'') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = '';
    }

    $results = $db->select("plugins","fop2templates","","isdefault=1 $where");
    if($results) {
        $pluginarray = explode(",",$results[0]['plugins']);
        return $pluginarray;
    } else {
        return array();
    }
}


function fop2_get_defgroup() {
    global $active_modules, $db, $panelcontext;

    if($panelcontext<>'') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = '';
    }

    $results = $db->select("`groups`","fop2templates","","isdefault=1 $where");
    if($results) {
        $grouparray = explode(",",$results[0]['groups']);
        return $grouparray;
    } else {
        return array();
    }
}

function fop2_get_defperm() {
    global $active_modules, $db, $panelcontext;

    if($panelcontext<>'') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = '';
    }

    $results = $db->select( "permissions","fop2templates","","isdefault=1 $where");
    if($results) {
        return $results[0]['permissions'];
    } else {
        return '';
    }
}

function fop2_get_deftemplate() {
    global $active_modules, $db, $panelcontext;

    if($panelcontext<>'') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = '';
    }

    $results = $db->select("id","fop2templates","","isdefault=1 $where");
    if($results) {
        return $results[0]['id'];
    } else {
        return 0;
    }
}

/*
// --------------------------------------------------------------------------------
// Iterates the buttons data array and get values from there
// --------------------------------------------------------------------------------
*/
function system_all_values($field,$reset=0) {
    global $db, $panelcontext, $system_all_buttons;
    $return = array();
    if(!isset($system_all_buttons)) {
        $system_all_buttons = array();
    }

    if($reset==1) {
        $system_all_buttons = system_all_buttons();
    }

    foreach ($system_all_buttons as $chan => $data) {
        if(isset($data[$field])) {
            if($data['context_id']==$panelcontext) {
               $return[$chan]=trim($data[$field]);
            } 
        }
    }
    return $return;
}

/*
// --------------------------------------------------------------------------------
// Retrieves an array with buttons as returned by the backend system
// --------------------------------------------------------------------------------
*/
function system_all_buttons() {
    global $db, $config_engine, $conf;
    $return = array();

    if($config_engine=='freepbx') {
        $return = freepbx_check_extension_usage();
    } else if($config_engine == 'issabel') {
        $return = freepbx_check_extension_usage();
    } else if($config_engine == 'thirdlane_db') {
        $return = thirdlanedb_check_extension_usage();
    } else if($config_engine == 'thirdlane_old') {
        $return = thirdlaneold_check_extension_usage();
    } else if($config_engine == 'astdb') {
        $return = astdb_check_extension_usage();
    } else if($config_engine == 'elastix_mt') {
        $return = elastix_check_extension_usage();
    } else if($config_engine == 'mirtapbx') {
        $return = mirtapbx_check_extension_usage();
    } else if($config_engine == 'pbxware') {
        $return = pbxware_check_extension_usage();
    } else if($config_engine == 'ombutel') {
        $return = ombutel_check_extension_usage();
    } else if($config_engine == 'xivo') {
        $return = xivo_check_extension_usage();
    } else if($config_engine == 'snep') {
        $return = snep_check_extension_usage();
    } else if($config_engine == 'peke') {
        $return = peke_check_extension_usage();
    } else if($config_engine == 'custom') {
        $return = custom_check_extension_usage();
    } else {
        // Default to astdb check if engine is unknown
        if(is_callable("${config_engine}_check_extension_usage")) {
            $func = "${config_engine}_check_extension_usage";
            $return = $func();
        } else {
            // Default to astdb check if engine is unknown
            $return = astdb_check_extension_usage();
        }
    }

    $custom_return = buttons_custom_extension_usage();
    $ret = array_merge($return,$custom_return);
    return $ret;
}

/*
// --------------------------------------------------------------------------------
// Retrieves the full contents of the fop2buttons table to display on the edit page
// --------------------------------------------------------------------------------
*/
function fop2_all_buttons() {
    global $db, $panelcontext;

    $sql = "SELECT * FROM fop2buttons ";
    if($panelcontext<>'' && $panelcontext<>'GENERAL') {
        $sql.=" WHERE context_id='$panelcontext' ";
    } else {
        $sql.=" WHERE context_id=0 ";
    }
    $sql.= "ORDER BY sortorder";

    $fopbutton = array();
    $results = $db->consulta($sql);
    while($re = $db->fetch_assoc($results)) {
        $keys = array_keys($re);
        foreach($keys as $kk) {
            $ki = $kk;
            if( $kk == 'label'      ) { $ki='name'; }
            if( $kk == 'id'         ) { $ki='devid'; }
            if( $kk == 'context_id' ) { continue; }
            $fopbutton[$re['device']][$ki]=$re[$kk];
        }
    }
    return $fopbutton;
}


function fop2_list_botones() {
    global $db;
    $ret = array();
    $results = $db->select("device","fop2buttons");
    if(is_array($results)) {
        foreach($results as $result) {
            $ret[$result['device']] = 1;
        }
    }
    return $ret;
}

function fop2_custom_permissions() {
    global $db, $panelcontext;

    if($panelcontext<>'') {
        $where = " context_id='$panelcontext' ";
    } else {
        $where = '';
    }

    $allowed = array();
    $results = $db->select("*","fop2permissions","",$where,"name");
    if(is_array($results)){
        foreach($results as $result){
            $allowed[] = $result;
        }
    }
    return $allowed;
}

function fop2_list_templates() {
    global $db,$panelcontext;

    if($panelcontext<>'') {
        $where = " context_id='$panelcontext' ";
    } else {
        $where = '';
    }

    $allowed = array();
    $results = $db->select("*","fop2templates","",$where,"name");
    if(is_array($results)){
        foreach($results as $result){
            $allowed[] = $result;
        }
    }
    return $allowed;
}

function fop2_list_templates_jsobject() {
    global $db, $panelcontext, $cust_fields;

    if($panelcontext<>'') {
        $where = " context_id='$panelcontext' ";
    } else {
        $where = '';
    }

    $results = $db->select("*","fop2templates","",$where,"id");
    $finalret="";
    if(is_array($results)){
        foreach($results as $result) {
            $finalret .= "var tempperm_".$result['id']." = [];\n";
            $objperm = preg_split("/,/",$result['permissions']);
            foreach($objperm as $itm) {
                $finalret .= "tempperm_".$result['id'].".push('$itm')\n";
            }

            $objgrp = preg_split("/,/",$result['groups']);
            $finalret .= "var tempgrp_".$result['id']." = [];\n";
            foreach($objgrp as $itm) {
                $finalret .= "tempgrp_".$result['id'].".push('$itm')\n";
            }

            $objgrp = preg_split("/,/",$result['plugins']);
            $finalret .= "var tempplg_".$result['id']." = [];\n";
            foreach($objgrp as $itm) {
                $finalret .= "tempplg_".$result['id'].".push('$itm')\n";
            }

            $custom_vars = json_decode($result['custom']);

            if(!isset($cust_fields)) { $cust_fields=array(); }

            foreach($cust_fields as $idx=>$dd) {
                if($dd['type']=='multiselect' && $dd['section']=='user') {
                     $selected_allowed_profiles = $custom_vars->{'fields'}->{$dd['variablename']};
                     $finalret.="var temp_".$dd['variablename']."_".$result['id']."=[];\n";
                     foreach($selected_allowed_profiles as $val) {
                         $finalret.="temp_".$dd['variablename']."_".$result['id'].".push('$val');\n";
                     }
                }
            } 
        }
    }
    return $finalret;
}

function fop2_list_users() {
    global $db, $panelcontext;
    if($panelcontext<>'' && $panelcontext<>'GENERAL') {
        $where = " context_id = '$panelcontext' ";
    } else {
        $where = " context_id is null or context_id='GENERAL' ";
    }
    $allowed = array();
    $results = $db->select("*","fop2users","","$where","CAST(exten as SIGNED INTEGER) ASC");
    if(is_array($results)){
        foreach($results as $result){
            $allowed[] = $result;
        }
    }
    return $allowed;
}

function admin_list_users() {
    global $db, $panelcontext;
    $allowed = array();
    $results = $db->select("*","fop2managerusers","","","user");
    if(is_array($results)){
        foreach($results as $result){
            $allowed[] = $result;
        }
    }
    return $allowed;
}

function fop2_get_perm($id){
    global $db;
    //get all the variables for the meetme
    $results = $db->select("*","fop2permissions","","id='$id'");
    return ($results!==false)?$results[0]:false;
}

function fop2_get_user($id){
    global $db;
    //get all the variables for the meetme
    $results = $db->select("*","fop2users","","id = '$id'");
    return ($results!==false)?$results[0]:false;
}

function admin_get_user($id){
    global $db;
    //get all the variables for the meetme
    $results = $db->select("*","fop2managerusers","","id = '$id'");
    return ($results!==false)?$results[0]:false;
}

function admin_get_user_tenants($id){
    global $db;
    //get all the variables for the meetme
    $results = $db->select("id_context","fop2managerUserContext","","id_user = '$id'");
    return ($results!==false)?$results[0]:false;
}

function fop2_get_template($id){
    global $db;
    //get all the variables for the meetme
    $results = $db->select("*","fop2templates","","id = '$id'");
    return ($results!==false)?$results[0]:false;
}

function fop2_chk_template($name) {
    global $db, $panelcontext;

    if($panelcontext<>'' && $panelcontext<>'GENERAL') {
        $where = " context_id = '$panelcontext' ";
    } else {
        $where = " context_id is null or context_id='GENERAL' ";
    }
 
    $results = $db->select("id","fop2templates","","$where AND name = '$name'");
    if ( $results === false  ) {
        return false;
    } else {
        return true;
    }
}

function fop2_get_users() {
    global $db,$panelcontext;

    if($panelcontext<>'' && $panelcontext<>'GENERAL') {
        $where = " context_id = '$panelcontext' ";
    } else {
        $where = " context_id is null or context_id='GENERAL' ";
    }
 
    $results = $db->select("exten","fop2users","",$where);
    if ( $results === false  ) {
        return false;
    } else {
        return $results;
    }
}

function admin_get_users() {
    global $db,$panelcontext;

    $results = $db->select("user","fop2managerusers","","");
    if ( $results === false  ) {
        return false;
    } else {
        return $results;
    }
}


function fop2_chk_user($exten) {
    global $db,$panelcontext;

    if($panelcontext<>'') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = '';
    }

    $results = $db->select("id","fop2users","","exten = '$exten' $where");

    if ( $results === false  ) {
        return false;
    } else {
        return true;
    }
}

function fop2_chk_perm($name) {
    global $db,$panelcontext;

    if($panelcontext<>'') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = '';
    }

    $results = $db->select("id","fop2permissions","","name = '$name' $where");

    if ( $results === false  ) {
        return false;
    } else {
        return true;
    }
}

function fop2_chk_group($name) {
    global $db,$panelcontext;

    if($panelcontext<>'') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = '';
    }

    $results = $db->select("id","fop2groups","","name = '$name' $where");

    if ( $results === false  ) {
        return false;
    } else {
        return true;
    }
}

function fop2_add_button($det) {
    global $db;
    $queuechannel   = (isset($det['queuechannel']))?$det['queuechannel']:'';
    $customastdb    = (isset($det['customastdb']))?$det['customastdb']:'';
    $mailbox        = (isset($det['mailbox']))?$det['mailbox']:'';
    $accountcode    = (isset($det['accountcode']))?$det['accountcode']:'';
    $sip_username   = (isset($det['sip_username']))?$det['sip_username']:'';
    $sip_password   = (isset($det['sip_password']))?$det['sip_password']:'';
    $email          = (isset($det['email']))?$det['email']:'';
    $group          = (isset($det['group']))?$det['group']:'';
    $external       = (isset($det['external']))?$det['external']:'';
    $extenvoicemail = (isset($det['extenvoicemail']))?$det['extenvoicemail']:'';
    $context        = (isset($det['context']))?$det['context']:'';
    $queuecontext   = (isset($det['queuecontext']))?$det['queuecontext']:'';
    $name           = (isset($det['name']))?$det['name']:$det['channel'];
    $context_id     = (isset($det['context_id']))?$det['context_id']:'';
    $originatechan  = (isset($det['originatechannel']))?$det['originatechannel']:'';
    $extrachan      = (isset($det['extrachannel']))?$det['extrachannel']:'';
    $srv            = (isset($det['server']))?$det['server']:'';

    $results = $db->consulta(
        "REPLACE INTO fop2buttons (type, device, label, exten, context, mailbox, queuechannel, customastdb, email, `group`, external, queuecontext, extenvoicemail,context_id,originatechannel,channel,accountcode,server,sip_username,sip_password) ".
        "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
        array($det['type'],$det['channel'],$name,$det['exten'],$context,$mailbox,$queuechannel,$customastdb,$email,$group,$external,$queuecontext,$extenvoicemail,$context_id,$originatechan,$extrachan,$accountcode,$srv,$sip_username,$sip_password)
    );
}

function fop2_add_perm($post) {

    global $db, $panelcontext;
    extract($post);
    $name = trim(strtolower($name));

    if ( fop2_chk_perm($name) ) {
        print_js_error(__("The permission name is already in use."));
        return false;
    }

    if ( $name=="" ) {
        print_js_error(__("The name cannot be empty.")); 
        return false;
    }

    $allperm    = strtolower(implode(" ",$permissions));
    $allpermarr = preg_split("/\s+/",$allperm);
    $allpermarr = array_unique($allpermarr);
    $allpermarr = array_filter($allpermarr);

    if(in_array('all',$allpermarr)) {
        $strperm = "all";
    } else {
        $strperm = implode(",",$allpermarr);
    }

    $groups = fop2_list_groups(1);
    $groupid = array();
    foreach ($groups as $d) {
        $groupid[$d['id']]=$d['name'];
    }

    $results = $db->consulta("INSERT INTO fop2permissions (name, permissions, context_id) VALUES ('$name', '$strperm','$panelcontext')");

    if($results==1) {
        if(isset($post['includebot'])) {
            foreach($post['includebot'] as $idx=>$chan){
                $gname = $groupid[$chan];
                $results = $db->consulta("INSERT INTO fop2PermGroup (name,id_group,name_group, context_id) VALUES ('$name', '$chan','$gname','$panelcontext')");
            }
        }
    }

    return true;
}

function fop2_edit_user($id, $post) {

    global $db, $panelcontext, $astman, $conf, $contextname;
    $id = preg_replace('/[^\d]/','',$id);
    $thisItem = fop2_get_user($id);
    $exten = $thisItem['exten'];
    $context = 'GENERAL';

    if($panelcontext<>'') {
        $where = " AND context_id='$panelcontext' ";
        $context = strtoupper($contextname[$panelcontext]);
    } else {
        $where = '';
    }

    extract($post);

    if($secret=='') { $secret=$exten; }

    if(!isset($permissions)) { $permissions=array(); } 
    $allperm    = strtolower(implode(" ",$permissions));
    $allpermarr = preg_split("/\s+/",$allperm);
    $allpermarr = array_unique($allpermarr);
    $allpermarr = array_filter($allpermarr);

    if(in_array('all',$allpermarr)) {
        $strperm = "all";
    } else {
        $strperm = implode(",",$allpermarr);
    }

    // We need to skip insertion of global plugins
    $globalplugins = array();
    $results= fop2_list_plugins();
    if(count($results)>0) {
        foreach($results as $result) {
            if($result['global']==1) {
                $globalplugins[] = $result['rawname'];
            }
        }
    }

    $results = $db->consulta("UPDATE fop2users SET secret = '%s', permissions='%s' WHERE id = '%d'",array($secret,$strperm,$id));
    $results = $db->consulta("DELETE FROM fop2UserGroup WHERE exten = '%s' $where",array($exten));
    $results = $db->consulta("DELETE FROM fop2UserPlugin WHERE exten = '%s' $where",array($exten));
    $results = $db->consulta("DELETE FROM fop2UserTemplate WHERE exten = '%s' $where",array($exten));

    $results = $db->consulta("INSERT INTO fop2UserTemplate (exten,id_template,context_id) VALUES ('$exten', '".$post['settemplate']."','$panelcontext')");

    if(isset($post['includebot'])) {
        foreach($post['includebot'] as $idx=>$chan){
            $results = $db->consulta("INSERT INTO fop2UserGroup (exten,id_group,context_id) VALUES ('$exten', '$chan', '$panelcontext')");
        }
    }
    if(isset($post['includeplugin'])) {
        foreach($post['includeplugin'] as $idx=>$chan){
            if(!in_array($chan,$globalplugins)) {
                $results = $db->consulta("INSERT INTO fop2UserPlugin (exten,id_plugin,context_id) VALUES ('$exten', '$chan', '$panelcontext')");
            }
        }
    }
 
    // AMI USER EVENT FOR FOP2CHANGEUSER
    // $results = $db->consulta("UPDATE fop2users SET secret = '$secret', permissions='$strperm' WHERE id = '$id'");

    if(!$res = $astman->connect($conf['MGRHOST'].':'.$conf['MGRPORT'], $conf['MGRUSER'] , $conf['MGRPASS'], 'off')) {
        unset($astman);
    }

    if ($astman) {
        $res = $astman->UserEvent('FOP2CHANGEUSERPASSWORD',array('User'=>$exten,'Secret'=>$secret,'Context'=>$context));
        $res = $astman->UserEvent('FOP2CHANGEUSERPERMS',array('User'=>$exten,'Permissions'=>$strperm,'Context'=>$context));
    }

    $_SESSION[MYAP]['needsreload']=1; 
    return true;
}
function admin_edit_acl($id,$value) {
    global $db;
    $db->consulta("UPDATE fop2manageracl SET level='%d' WHERE id='%d'",array($value,$id));
}

function admin_edit_user($id, $post) {

    global $db, $conf, $contextname;
 
    $thisItem = admin_get_user($id);
 
    extract($post);

  
    if($secret=='') { 
        $secret= $thisItem['password'];
        $results = $db->consulta("UPDATE fop2managerusers SET user='%s', level='%s' WHERE id = '%d'",array($user,$role,$id));
    } else {
        $results = $db->consulta("UPDATE fop2managerusers SET password = sha1('%s'), user='%s', level='%s' WHERE id = '%d'",array($secret,$user,$role,$id));
    }

    $db->consulta("DELETE FROM fop2managerUserContext WHERE id_user='%d'",$id);
   
    if(isset($tenants)) { 
        foreach($tenants as $valor) {
            $db->consulta("INSERT INTO fop2managerUserContext values ($id,$valor)");
        }
    } 

    return true;
}


function admin_add_user($post) {

    global $db, $panelcontext;

    extract($post);

    $results = $db->consulta("INSERT INTO fop2managerusers (user,password,level) VALUES ('%s', sha1('%s'), '%s')",array($user, $secret, $role));

    $id = $db->insert_id($results);
    
    if(isset($tenants)) {
        foreach($tenants as $valor) {
            $db->consulta("INSERT INTO fop2managerUserContext values ($id,$valor)");
        }
    }

}

function fop2_add_user($post) {
    global $db, $panelcontext;

    extract($post);

    if ( fop2_chk_user($userid) ) {
        print_js_error(__("The user number is already in use."));
        return false;
    }

    if($secret=='') { $secret=$userid; }

    if(!isset($permissions)) { $permissions=array(); } 
    $allperm    = strtolower(implode(" ",$permissions));
    $allperm    = preg_replace('/[^\s\da-z]/i', '', $allperm);
    $allpermarr = preg_split("/\s+/",$allperm);
    $allpermarr = array_unique($allpermarr);
    $allpermarr = array_filter($allpermarr);

    if(in_array('all',$allpermarr)) {
        $strperm = "all";
    } else {
        $strperm = implode(",",$allpermarr);
    }

    // We need to skip insertion of global plugins
    $globalplugins = array();
    $results= fop2_list_plugins();
    if(count($results)>0) {
        foreach($results as $result) {
            if($result['global']==1) {
                $globalplugins[] = $result['rawname'];
            }
        }
    }

    $results = $db->consulta("INSERT INTO fop2users (exten, secret, permissions, context_id) VALUES ('$userid', '$secret', '$strperm', '$panelcontext')");

    if($results==1) {
        if(isset($post['includebot'])) {
            foreach($post['includebot'] as $idx=>$chan){
                $results = $db->consulta("INSERT INTO fop2UserGroup (exten,id_group,context_id) VALUES ('$userid', '".$chan."','$panelcontext')");
            }
        }
        if(isset($post['includeplugin'])) {
            foreach($post['includeplugin'] as $idx=>$chan){
                if(!in_array($chan,$globalplugins)) {
                    $results = $db->consulta("INSERT INTO fop2UserPlugin (exten,id_plugin,context_id) VALUES ('$userid', '".$chan."','$panelcontext')");
                }
            }
        }
        if($post['settemplate']<>'') {
            $results = $db->consulta("INSERT INTO fop2UserTemplate (exten,id_template,context_id) VALUES ('$userid', '".$post['settemplate']."','$panelcontext')");
        } 
    }
    return true;
}

function fop2_add_template($post) {
    global $db, $panelcontext;

    if($panelcontext <> '') {
        $where = " WHERE context_id='$panelcontext' ";
    } else {
        $where = " WHERE context_id=0 ";
    }
    extract($post);
 
    $templatename=trim($templatename);

    if ( fop2_chk_template($templatename) ) {
        print_js_error(__("The template name is already in use."));
        return false;
    }

    if ( $templatename=="" ) {
        print_js_error(__("The name cannot be empty."));
        return false;
    }

    if(!isset($permissions)) { $permissions=array(); }
    $allperm    = strtolower(implode(" ",$permissions));
    $allpermarr = preg_split("/\s+/",$allperm);
    $allpermarr = array_unique($allpermarr);
    $allpermarr = array_filter($allpermarr);

    if(in_array('all',$allpermarr)) {
        $strperm = "all";
    } else {
        $strperm = implode(",",$allpermarr);
    }
    $mygroups = array();
    if(isset($post['includebot'])) {
        foreach($post['includebot'] as $idx=>$chan){
            $mygroups[] = $chan;
        }
    }
    $strgroup = implode(",",$mygroups);

    $myplugs = array();
    if(isset($post['includeplugin'])) {

        foreach($post['includeplugin'] as $idx=>$chan){
            $myplugs[] = $chan;
        }
    }
    $strplug = implode(",",$myplugs);
    $strplug = $db->escape_string($strplug);

    if(isset($makedefault)) {
        $mydef = 1;
        $results = $db->consulta("UPDATE fop2templates SET isdefault=0 $where");
    } else {
        $mydef = 0;
    }
    $results = $db->consulta("INSERT INTO fop2templates (name, permissions, `groups`, plugins, isdefault, context_id, custom) VALUES ('$templatename', '$strperm', '$strgroup','$strplug','$mydef','$panelcontext','')");
    $last = $db->insert_id($results);
    return $last;
}

function fop2_getDeviceId() {
    global $db;
    $myreturn = array();
    $results = $db->select("id,device","fop2buttons");
    if(is_array($results)){
        foreach($results as $result){
            $myreturn[$result['device']]=$result['id'];
        }
        return $myreturn;
    }
}

function fop2_get_perm_groups($name) {
    global $db, $panelcontext;

    if($panelcontext <> '') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = "";
    }

    $ret = array();
    $results = $db->select("id_group","fop2PermGroup","","name='$name' $where");
    if(is_array($results)){
        foreach($results as $idx => $cols) {
            $ret[]=$cols['id_group'];
        }
        return $ret;
    } else {
        return array();
    }
}

function fop2_get_user_template($exten) {
    global $db, $panelcontext;

    if($panelcontext <> '') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = "";
    }

    $exten = preg_replace('/[^\d\+]/','',$exten);
    $ret = 0;
    $results = $db->select("id_template","fop2UserTemplate","","exten='$exten' $where LIMIT 1");
    if(is_array($results)){
        foreach($results as $idx => $cols) {
            $ret=$cols['id_template'];
        }
        return $ret;
    } else {
        return 0;
    }
}


function fop2_get_user_groups($exten) {
    global $db, $panelcontext;

    if($panelcontext <> '') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = "";
    }

    $exten = preg_replace('/[^\d\+]/','',$exten);
    $ret = array();
    $results = $db->select("id_group","fop2UserGroup","","exten='$exten' $where");
    if(is_array($results)){
        foreach($results as $idx => $cols) {
            $ret[]=$cols['id_group'];
        }
        return $ret;
    } else {
        return array();
    }
}

function fop2_get_user_plugins($exten) {
    global $db, $panelcontext;

    if($panelcontext <> '') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = "";
    }

    $exten = preg_replace('/[^\d\+]/','',$exten);
    $ret = array();
    $results = $db->select("id_plugin","fop2UserPlugin","","exten='$exten' $where");
    if(is_array($results)){
        foreach($results as $idx => $cols) {
            $ret[]=$cols['id_plugin'];
        }
        return $ret;
    } else {
        return array();
    }
}

function fop2_del_perm($id) {
    global $db, $panelcontext;

    if($panelcontext <> '') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = "";
    }

    $id = preg_replace('/[^\d]/','',$id);
    $thisItem = fop2_get_perm($id);
    $name = $thisItem['name'];
    $results = $db->consulta("DELETE FROM fop2PermGroup WHERE name = '$name' $where");
    $results = $db->consulta("DELETE FROM fop2permissions WHERE id = '$id'");

    $results = $db->select("id,permissions","fop2users","","permissions like '%$name%' $where");
    if(is_array($results)){
        foreach($results as $idx => $perms) {
            $ret = array();
            $myid = $perms['id'];
            $mype = $perms['permissions'];
            $partperm = explode(",",$mype);
            foreach($partperm as $itemperm) {
                if($itemperm<>$name) {
                    $ret[] = $itemperm;
                }
            }
            $retstring = implode(",",$ret);
            $query = "UPDATE fop2users SET permissions='$retstring' WHERE id='$myid'";
            $results2 = $db->consulta($query);
        }
    }

    // Borramos custom permisos de template
    $results = $db->select("*","fop2templates","","permissions LIKE '%$name%' $where");
    if(is_array($results)){
        foreach($results as $result){
            $permitstring = preg_split("/,/",$result['permissions']);
            $finalperm = array();
            foreach($permitstring as $itemperm) {
                if($itemperm<>$name) {
                    $finalperm[]=$itemperm;
                }
            }
            $finalpermstring = implode(",",$finalperm);
            $db->consulta("UPDATE fop2templates SET permissions='$finalpermstring' WHERE id='".$result['id']."'");
        }
    }
    return $thisItem;
}

function fop2_del_template($id) {
    global $db;
    $id = preg_replace('/[^\d]/','',$id);
    $results = $db->select("name","fop2templates","","id='$id'");
    $db->consulta("DELETE FROM fop2templates WHERE id = '$id'","query");
    return $results[0];
}

function fop2_del_user($id) {
    global $db, $panelcontext;
    $id = preg_replace('/[^\d]/','',$id);
    $thisItem = fop2_get_user($id);
    $exten = $thisItem['exten'];
    if($panelcontext<>'') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = '';
    }

    $results = $db->consulta("DELETE FROM fop2UserGroup WHERE exten = '%s' $where",$exten);
    $results = $db->consulta("DELETE FROM fop2UserPlugin WHERE exten = '%s' $where",$exten);
    $results = $db->consulta("DELETE FROM fop2UserTemplate WHERE exten = '%s' $where",$exten);
    $results = $db->consulta("DELETE FROM fop2users WHERE id = '%d' $where",$id);
}

function admin_del_user($id) {
    global $db, $panelcontext;
    $id = preg_replace('/[^\d]/','',$id);
    $results = $db->consulta("DELETE FROM fop2managerusers WHERE id='%d'",$id);
    $db->consulta("DELETE FROM fop2managerUserContext WHERE id_user='%d'",$id);
}


function fop2_buttonsrefresh($post) {
    global $db;
    $full_list = system_all_values('name',1);
    foreach($full_list as $chan => $label) {
        $label = $db->escape_string($label);
        $query="UPDATE fop2buttons SET label='$label' WHERE device='$chan'";
        $results = $db->consulta($query); 
    }
}
function fop2_fieldedit($post) {
    global $db, $conf, $astman;
    $partes = preg_split('~_(?=[^_]*$)~', $post['field']);

    $field = $partes[0];
    $id    = $partes[1];
    $val   = $db->escape_string($post['value']);

    $good_fields = array( 'name', 'privacy', 'channel', 'group', 'email', 'queuechannel', 'originatechannel', 'customastdb', 'spyoptions',
        'external', 'excludebot', 'tags', 'mailbox', 'extenvoicemail', 'queuecontext', 'cssclass', 'context', 
        'autoanswerheader', 'originatevariables', 'accountcode','sip_username','sip_password' );

    $morefields = plugin_get_custom_button_fields();

    $good_fields = array_merge($good_fields,$morefields);

    if(!in_array($field,$good_fields)) { die(" no te dejo $field"); }

    if($field=='name')       $field='label';
    if($field=='excludebot') $field='exclude';

    if($field=='label' || $field=='group') {

        $resultado = $db->select("device,IF(fop2contexts.context IS NULL, 'GENERAL', fop2contexts.context) AS panelcontext ","fop2buttons","LEFT JOIN fop2contexts ON fop2buttons.context_id=fop2contexts.id","fop2buttons.id='$id'");
        $device = $resultado[0]['device'];
        $panelcontext = $resultado[0]['panelcontext'];

        if(!$res = $astman->connect($conf['MGRHOST'].':'.$conf['MGRPORT'], $conf['MGRUSER'] , $conf['MGRPASS'], 'off')) {
            unset($astman);
        }

        if ($astman) {
            if($field=='label') {
                $res = $astman->UserEvent('FOP2CHANGEBUTTONLABEL',array('FOP2Channel'=>$device,'Label'=>$val,'Context'=>$panelcontext));
            } else if($field=='group') {
                $res = $astman->UserEvent('FOP2CHANGEBUTTONGROUP',array('FOP2Channel'=>$device,'Group'=>$val,'Context'=>$panelcontext));
            }
            $res = $astman->disconnect();
        }
    }

    $results = $db->consulta("UPDATE fop2buttons SET `$field`='$val' WHERE id='$id'");
    return $results;
}

function fop2_buttonsedit($post) {
    global $db;

    $exclude=array();
    foreach($post as $key=>$val) {
        if(preg_match("/_/",$key)) {
            $partes = preg_split("/_/",$key);
            $field = $partes[0];
            $id    = $partes[1];
            $exclude[$id]=0;
        }
    }
    foreach($post as $key=>$val) {
        if(preg_match("/_/",$key)) {
            $partes = preg_split("/_/",$key);
            $field = $partes[0];
            $id    = $partes[1];
            $val   = $db->escape_string($val);
            if($field<>"name" && $field<>"privacy" && $field<>"channel" && $field<>"group" && $field<>"email" && $field<>'queuechannel' && $field<>'originatechannel' && $field<>'customastdb' && $field<>'spyoptions' && $field<>'external' && $field<>'excludebot' && $field<>'tags') { die($field); }
            if($field=="name") $field="label";
            if($field=="excludebot") {
                $exclude[$id]=1;
            } else {
                $results = $db->consulta("UPDATE fop2buttons SET `$field`='$val' WHERE id='$id'");
            }
        }
    }
    foreach($exclude as $id=>$val) {
        $results = $db->consulta("UPDATE fop2buttons SET exclude='$val' WHERE id='$id'");
    }
}

function fop2_edit_perm($id, $post) {
    global $db, $panelcontext;
    $id = preg_replace('/[^\d]/','',$id);
    $thisItem = fop2_get_perm($id);
    $name = $thisItem['name'];

    if($panelcontext<>'') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = '';
    }

    extract($post);

    $allperm    = strtolower(implode(" ",$permissions));
    $allpermarr = preg_split("/\s+/",$allperm);
    $allpermarr = array_unique($allpermarr);
    $allpermarr = array_filter($allpermarr);

    if(in_array('all',$allpermarr)) {
        $strperm = "all";
    } else {
        $strperm = implode(",",$allpermarr);
    }

    $results = $db->consulta("UPDATE fop2permissions SET permissions='$strperm' WHERE id = '$id'");

    $results = $db->consulta("DELETE FROM fop2PermGroup WHERE name = '$name' $where");

    $groups = fop2_list_groups(1);
    foreach ($groups as $d) {
        $groupid[$d['id']]=$d['name'];
    }

    if(isset($post['includebot'])) {
        foreach($post['includebot'] as $idx=>$chan){
            $gname = $groupid[$chan];
            $results = $db->consulta("INSERT INTO fop2PermGroup (name,id_group,name_group,context_id) VALUES ('$name', '$chan','$gname','$panelcontext')");
        }
    }
    return true;
}

function fop2_edit_template($id, $post) {
    global $db;
    $id = preg_replace('/[^\d]/','',$id);
    $thisItem = fop2_get_template($id);
    $name = $thisItem['name'];

    extract($post);

    if(!isset($permissions)) { $permissions=array(); }
    $allperm    = strtolower(implode(" ",$permissions));
    $allpermarr = preg_split("/\s+/",$allperm);
    $allpermarr = array_unique($allpermarr);
    $allpermarr = array_filter($allpermarr);

    if(in_array('all',$allpermarr)) {
        $strperm = "all";
    } else {
        $strperm = implode(",",$allpermarr);
    }
    $mygroups = array();
    if(isset($post['includebot'])) {
        foreach($post['includebot'] as $idx=>$chan){
            $mygroups[$idx] = $chan;
        }
    }
    $strgroup = implode(",",$mygroups);

    $myplugs = array();
    if(isset($post['includeplugin'])) {
        foreach($post['includeplugin'] as $idx=>$chan){
            $myplugs[$idx] = $chan;
        }
    }
    $strplug = implode(",",$myplugs);
    $strplug = $db->escape_string($strplug);

    if(isset($makedefault)) {
        $mydef = 1;
        $results = $db->consulta("UPDATE fop2templates SET isdefault=0");
    } else {
        $mydef = 0;
    }

    $results = $db->consulta("UPDATE fop2templates SET `groups`= '$strgroup', permissions='$strperm', plugins='$strplug', isdefault='$mydef' WHERE id = '$id'");

    fop2_update_users_on_template_change($id,$mygroups,$strperm,$myplugs);

    return true;
}

function fop2_update_users_on_template_change($templateid,$groups,$permissions,$plugins) {
    global $db, $panelcontext;

    if($panelcontext<>'' && $panelcontext<>'GENERAL') {
        $where = " context_id = '$panelcontext' ";
    } else {
        $where = " context_id is null or context_id='GENERAL' ";
    }
   
    $where .= " AND id_template='$templateid' ";
 
    $results = $db->select("exten","fop2UserTemplate","",$where);

    $user_extension_to_change = array();
    if(is_array($results)){
        foreach($results as $result){
            $user_extension_to_change[]=$result['exten'];
        }
    }

    foreach($user_extension_to_change as $exten) {
        $results = $db->consulta("UPDATE fop2users SET permissions = '$permissions' WHERE exten = '$exten'");
        $results = $db->consulta("DELETE FROM fop2UserGroup WHERE exten = '$exten'");
        $results = $db->consulta("DELETE FROM fop2UserPlugin WHERE exten = '$exten'");

        foreach($plugins as $idx=>$chan){
            $results = $db->consulta("INSERT INTO fop2UserPlugin (exten,id_plugin,context_id) VALUES ('$exten', '".$chan."','$panelcontext')");
        }

        foreach($groups as $idx=>$chan){
            $results = $db->consulta("INSERT INTO fop2UserGroup (exten,id_group,context_id) VALUES ('$exten', '".$chan."','$panelcontext')");
        }
 
    }

}

function fop2_get_next_available_exten() {
    global $db, $panelcontext;

    if($panelcontext<>'' && $panelcontext<>'GENERAL') {
        $where = " context_id = '$panelcontext' ";
    } else {
        $where = " context_id is null or context_id='GENERAL' ";
    }
 
    $valid_exten = array();
    $results = system_all_values('exten',1);
    $types   = system_all_values('type');

    foreach($results as $key=>$val) {
        if($types[$key]=='extension') {
            $valid_exten[]=$val;
        }
    }
    asort($valid_exten);

    $existent_exten = array();
    $results = $db->select("exten","fop2users","",$where);
    if(is_array($results)){
        foreach($results as $result){
            $existent_exten[]=$result['exten'];
        }
    }
    $disponibles = array_diff($valid_exten,$existent_exten);
    asort($disponibles);
    rsort($existent_exten);

    $proximodisponible = array_shift($disponibles);
    if($proximodisponible == '') { $proximodisponible = array_shift($existent_exten); $proximodisponible++; }

    return $proximodisponible;
}

function read_voicemail_conf($file) {
    $voicemail   = array();
    if(is_readable($file)) {
        $vmf = @file($file);
        $vmctx = '';
        foreach($vmf as $linea) {
            $linea=trim($linea);
            if(preg_match("/^\[/",$linea)) {
                $vmctx = substr($linea,1,-1);
            }
            if(preg_match("/^\d/",$linea)) {
                $partes = preg_split("/=>/",$linea);
                $vmext = trim($partes[0]);
                if(isset($partes[1])) {
                    $pertes = preg_split("/,/",$partes[1]);
                    $vmpas = trim($pertes[0]);
                    $voicemail['pin']["$vmext@$vmctx"]=$vmpas;
                    $voicemail['email']["$vmext@$vmctx"]=isset($pertes[2])?trim($pertes[2]):'';
                }
            }
        }
    }
    return $voicemail;
} 

function print_js_error($string) {
    echo "<script>";
    echo "\$(document).ready(function() {\n";
    echo "alertify.error(\"" . $string  . "\");\n";
    echo "});";
    echo "</script>";
}

function fop2_get_next_group_id() {
    global $db, $panelcontext;
    $return = 0;
    if($panelcontext<>'' && $panelcontext<>'GENERAL') {
        $where = " WHERE context_id='$panelcontext' ";
    } else {
        $where = ' WHERE context_id=0 ';
    }

    $query = "SELECT id+1 AS nextid FROM fop2groups $where ORDER BY ceil(id) DESC LIMIT 1";
    $results = $db->consulta($query);
    while($re = $db->fetch_assoc($results)) {
        $return = $re['nextid'];
    }
    return $return;
}

function fop2_add_group($post) {

    global $db, $panelcontext;
    extract($post);

    $name = trim($db->escape_string($name));

    if ( fop2_chk_group($name) ) {
        print_js_error(__("The group name is already in use."));
        return false;
    }

    if ( $name =='' ) {
        print_js_error(__("The name cannot be empty."));
        return false;
    }

    $nextid = fop2_get_next_group_id();

    $results = $db->consulta("INSERT INTO fop2groups (id,name,context_id) VALUES ('$nextid','$name','$panelcontext')");

    if($results==1) {
        $results = $db->select("id,device","fop2buttons","","context_id='$panelcontext'");
        if(is_array($results)){
            foreach($results as $result){
                $butid[$result['device']]=$result['id'];
            }
        }

        if(isset($post['includebot'])) {
            foreach($post['includebot'] as $idx=>$chan){
                $results = $db->consulta("INSERT INTO fop2GroupButton (group_name,id_button,context_id) VALUES ('$name', '".$butid[$chan]."','$panelcontext')");
            }
        }
    }

    return true;
}

function fop2_get_group($id) {

    global $db,$panelcontext;

    if($panelcontext<>'' && $panelcontext<>'GENERAL') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = '';
    }

    $results = $db->select("*","fop2groups","","CAST(id AS char)='$id' $where");
    return ($results!==false)?$results[0]:false;
}

function fop2_del_button($id,$exten) {
    global $db;
    $id = preg_replace('/[^\d]/','',$id);
    if($exten<>"") {
        $results = $db->consulta("DELETE FROM fop2UserGroup WHERE exten = '$exten'");
        $results = $db->consulta("DELETE FROM fop2UserPlugin WHERE exten = '$exten'");
        $results = $db->consulta("DELETE FROM fop2users WHERE exten = '$exten'");
    }
    $results = $db->consulta("DELETE FROM fop2GroupButton WHERE id_button = '$id'");
    $results = $db->consulta("DELETE FROM fop2buttons WHERE id = '$id'");
    return true;
}

function fop2_del_group($id) {
    global $db, $panelcontext;
    $id = preg_replace('/[^\d]/','',$id);
    $thisItem = fop2_get_group($id);
    $name = $thisItem['name'];
    $results = $db->consulta("DELETE FROM fop2GroupButton WHERE group_name = '$name' AND context_id='$panelcontext'");
    $results = $db->consulta("DELETE FROM fop2groups WHERE id = '$id'");
    $results = $db->consulta("DELETE FROM fop2UserGroup WHERE id_group = '$id'");

    // Borramos grupo de template
    $results = $db->select("*","fop2templates","","`groups` LIKE '%$name%' AND context_id='$panelcontext'");
    if(is_array($results)){
        foreach($results as $result){
            $grupejos = preg_split("/,/",$result['groups']);
            $finalgrp = array();
            foreach($grupejos as $itemgroup) {
                if($itemgroup<>$name) {
                    $finalgrp[]=$itemgroup;
                }
            }
            $finalgroupstring = implode(",",$finalgrp);
            $db->consulta("UPDATE fop2templates SET `groups`='$finalgroupstring' WHERE id='".$result['id']."'");
        }
    }
    return $thisItem;
}

function fop2_get_user_preferences($extension,$context) {
    global $SQLITEDB;
    $panelcontexts = fop2_get_contexts();
    $contextname = $panelcontexts[$context];

    if(!is_writable($SQLITEDB)) { return false; }

    $db2 = new dbcon("sqlite:$SQLITEDB");
    try {
        $res = $db2->consulta("SELECT * FROM setup WHERE context=%s AND extension=%s AND parameter<>'grid'",array($contextname,$extension));
        $result = array();
        while($row = $db2->fetch_assoc($res)) { 
            $result[$row['parameter']]=$row['value'];
        }
        return $result;
    } catch (PDOException $e) {
        echo "<br><br><br><div class='debug danger'><h3>DB Connection fail: ".$e->getMessage()."</h3></div>";
    }
}
function fop2_edit_user_setting($extension,$context,$settings) {
    global $SQLITEDB;
    $panelcontexts = fop2_get_contexts();
    $contextname = $panelcontexts[$context];

    $db2 = new dbcon("sqlite:$SQLITEDB");

    $settings = base64_decode($settings);
    $eachsetting = preg_split("/&/",$settings);

    $update = array();
    foreach($eachsetting as $pepi) {
        list($field,$value) = preg_split("/!/",$pepi,2);
        try {
            $result = $db2->consulta("REPLACE INTO setup (extension, context, parameter,value) VALUES ( '$extension', '$contextname', '$field', '$value' )");
            fputs($fp,"REPLACE INTO setup (extension, context, parameter,value) VALUES ( '$extension', '$contextname', '$field', '$value' )\n");
        } catch (PDOException $e) {
            echo "<br><br><br><div class='debug danger'><h3>DB Connection fail: ".$e->getMessage()."</h3></div>";
        }
    }
}

function fop2_edit_setting_list($extension,$allvalues,$context) {
    global $SQLITEDB;
    $db2 = new dbcon("sqlite:$SQLITEDB");
    try {
        $allvalues = json_decode(base64_decode($allvalues));
        $db2->consulta("DELETE FROM setup WHERE extension=%s AND context=%s",$extension,$context);

        foreach($allvalues as $key=>$val) {
            $result = $db2->consulta("INSERT INTO setup (extension,context,parameter,value) VALUES (%s,%s,%s,%s)",$extension,$context,$key,$val);
        }
        return $result;
    } catch (PDOException $e) {
        echo "<br><br><br><div class='debug danger'><h3>DB Connection fail: ".$e->getMessage()."</h3></div>";
    }
}

function fop2_delete_setting($extension,$context,$key) {
    global $SQLITEDB;
    $db2 = new dbcon("sqlite:$SQLITEDB");
    try {
        $result = $db2->consulta("DELETE FROM setup WHERE extension=%s AND context=%s AND parameter=%s",$extension,$context,$key);
        return $result;
    } catch (PDOException $e) {
        echo "<br><br><br><div class='debug danger'><h3>DB Connection fail: ".$e->getMessage()."</h3></div>";
    }
}

function fop2_insert_setting($extension,$context,$key,$val) {
    global $SQLITEDB;
    $db2 = new dbcon("sqlite:$SQLITEDB");
    try {
        $result = $db2->consulta("INSERT INTO setup (extension,context,parameter,value) VALUES (%s,%s,%s,%s)",$extension,$context,$key,$val);
        return $result;
    } catch (PDOException $e) {
        echo "<br><br><br><div class='debug danger'><h3>DB Connection fail: ".$e->getMessage()."</h3></div>";
    }
}

function fop2_edit_setting($parameter,$value,$context) {
    global $SQLITEDB;
    $extension='SETTINGS';
    $db2 = new dbcon("sqlite:$SQLITEDB");
    try {
        $result = $db2->consulta("UPDATE setup SET value=%s WHERE context=%s AND extension=%s AND parameter=%s",array($value,$context,$extension,$parameter));
        return $result;
    } catch (PDOException $e) {
        echo "<br><br><br><div class='debug danger'><h3>DB Connection fail: ".$e->getMessage()."</h3></div>";
    }
}

function fop2_edit_group($id,$post) {
    global $db, $panelcontext;
    $id = preg_replace('/[^\d]/','',$id);
    $thisItem = fop2_get_group($id);
    $oldname = $thisItem['name'];
    extract($post);

    if($name<>$oldname) {
        // Rename Group

        if ( fop2_chk_group($name) ) {
            print_js_error(__("The group name is already in use."));
            return false;
        }

        if ( $name =='' ) {
            print_js_error(__("The name cannot be empty."));
            return false;
        }
 
        $results = $db->consulta("UPDATE fop2groups SET name='$name' WHERE id='$id'");
        fputs($fp,"UPDATE fop2groups SET name='$name' WHERE id='$id'\n"); 
    }

    $results = $db->consulta("DELETE FROM fop2GroupButton WHERE group_name = '$oldname' AND context_id='$panelcontext'");
    $results = $db->select("id,device","fop2buttons");
    if(is_array($results)){
        foreach($results as $result){
            $butid[$result['device']]=$result['id'];
        }
    }

    foreach($post['includebot'] as $idx=>$chan){
        $results = $db->consulta("INSERT INTO fop2GroupButton (group_name,id_button,context_id) VALUES ('$name', '".$butid[$chan]."','$panelcontext')");
    }
    return true;
}

function fop2_list_groups($all) {
    global $db,$panelcontext;

    if($panelcontext<>'' && $panelcontext<>'GENERAL') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = ' AND context_id=0';
    }

    if($all==1) {
        $results = $db->select("*","fop2groups","","id>=-9 $where","name");
    } else {
        $results = $db->select("*","fop2groups","","id>=0 $where","name");
    }
    if(is_array($results)){
        foreach($results as $result){
            $allowed[] = $result;
        }
    }
    if (isset($allowed)) {
        return $allowed;
    } else {
        return array();
    }
}

function fop2_list_installed_plugins() {
    global $db, $PLUGIN_DIR;
    $allowed=array();
    $results = $db->select("*","fop2plugins","","","name");
    if(is_array($results)){
        foreach($results as $result){
            if(is_dir($PLUGIN_DIR.$result['rawname'])) {
                $allowed[] = $result;
            }
        }
    }
    return $allowed;
}

function fop2_list_plugins() {
    global $db;
    $allowed=array();
    $results = $db->select("*","fop2plugins","","","name");
    if(is_array($results)){
        foreach($results as $result){
            $allowed[] = $result;
        }
    }
    return $allowed;
}

function fop2_get_plugins_callback() {
    global $db, $PLUGIN_DIR;
    $results = $db->select("*","fop2plugins","","","name");
    $cbk=array();
    if(is_array($results)){
        foreach($results as $result){
            if(is_dir($PLUGIN_DIR.$result['rawname'])) {
                if(is_file($PLUGIN_DIR.$result['rawname']."/callback.php")) {
                    $cbk[$result['rawname']] = $PLUGIN_DIR.$result['rawname']."/callback.php";
                }
            }
        }
    }
    return $cbk;
}

function draw_dashboard_widget($section,$link,$icon,$count,$color='blue') {
    echo "<div class='col-md-3'>\n";
    echo "   <a href='".$link."'>\n";
    echo "           <div class='card $color summary-inline'>\n";
    echo "               <div class='card-body'> \n";
    echo "                   <i class='icon fa $icon fa-4x'></i>\n";
    echo "                   <div class='content'>\n";
    echo "                       <div class='title'>$count</div>\n";
    echo "                       <div class='sub-title'>".__($section)."</div>\n";
    echo "                   </div>\n";
    echo "                   <div class='clear-both'></div>\n";
    echo "               </div>\n";
    echo "           </div>\n";
    echo "    </a>\n";
    echo "</div>";
}

function fop2_get_plugins_menu() {
    global $db, $PLUGIN_DIR;
    $results = $db->select("*","fop2plugins","","","name");
    $cbk=array();
    if(is_array($results)){
        foreach($results as $result){
            if(is_dir($PLUGIN_DIR.$result['rawname'])) {
                if(is_file($PLUGIN_DIR.$result['rawname']."/menu/module.xml")) {
                    $cbk[$result['rawname']] = $PLUGIN_DIR.$result['rawname']."/menu/module.xml";
                }
            }
        }
    }
    return $cbk;
}


function fop2_get_group_buttons($name) {
    global $db, $panelcontext;

    if($panelcontext<>'' && $panelcontext<>'GENERAL') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = ' AND context_id=0 ';
    }

    $ret = array();
    $results = $db->select("id_button","fop2GroupButton","","group_name='$name' $where");
    if(is_array($results)){
        foreach($results as $idx => $cols) {
            $ret[]=$cols['id_button'];
        }
        return $ret;
    } else {
        return array();
    }
}

function fop2_set_button_order($vars) {
    global $db;
    foreach($vars['listitem'] as $orden=>$typeexten) {

        if(isset($vars['table'])) {
            $partes=preg_split("/_/",$vars['table']);
            $type = $partes[1];
            $exten  = $typeexten;
        } else {
            $partes = preg_split("/!/",$typeexten);
            $exten  = $partes[1];
            $type   = $partes[0];
        }

        if(!isset($vars['offset'])) { $vars['offset']=0; }

        $exten = preg_replace("/^trunkout/","OUT_",$exten);
        $final_orden = $orden + $vars['offset'];
        $query="UPDATE fop2buttons SET sortorder='$final_orden' WHERE exten='$exten' AND type='$type'";
        $db->consulta($query);
    }
}

function get_fastest_mirror() {
   $mirror = array();
   $mirror[]="download.fop2.com";
   $mirror[]="download2.fop2.com";
   $mirror[]="download3.fop2.com";
   $faster = 1000;
   $use = $mirror[0];
   foreach($mirror as $host) {
      $result = ping($host,1);
      if($result<$faster) { $use = $host; $faster=$result; }
   }
   return $use;
}

function is_valid_mirror($value) {
   $mirror = array();
   $mirror[]="download.fop2.com";
   $mirror[]="download2.fop2.com";
   $mirror[]="download3.fop2.com";
   if(!in_array($value,$mirror)) {
       return false;
   } else {
       return true;
   }
}

function plugin_get_online($mirror) {
    $return = array();
    $fn = "http://$mirror/plugins/plugins.xml";
    ini_set('user_agent','Wget/1.10.2 (Red Hat modified)');

    $ip = get_addr_by_host($mirror);
    if($ip==$mirror) {
        // Fallo en DNS
        $available_online ="<plugins><plugin></plugin></plugins>";
    } else {
        $available_online = @file_get_contents($fn,false);
        if($available_online==false) {
            $available_online ="<plugins><plugin></plugin></plugins>";
        }
    }
    $xml = simplexml_load_string($available_online);
    $xmlarray = simple_xml_to_array($xml);
    return $xmlarray['plugin'];

}

function plugin_get_global() {
    global $db;
    $ret = array();
    $results = $db->select("rawname","fop2plugins","","global=1");
    if(is_array($results)){
        foreach($results as $idx => $cols) {
            $ret[$cols['rawname']]=1;
        }
    } 
    return $ret;
}

function get_installed_plugins($field='') {

    global $PLUGIN_DIR;

    $results = array();

    if(!is_dir($PLUGIN_DIR)) {
       mkdir($PLUGIN_DIR);
    }

    if ($handle = opendir($PLUGIN_DIR)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                if(is_dir($PLUGIN_DIR.$entry)) {
                     if(file_exists($PLUGIN_DIR.$entry.'/plugin.xml')) { 
                         $data = file_get_contents($PLUGIN_DIR.$entry.'/plugin.xml');
                         //$xml = new xml2Array();
                         //$xmlarray = $xml->parseAdvanced($data);

                         $xml = simplexml_load_string($data);
                         if($xml === false) { continue; }
                         $xmlarray = simple_xml_to_array($xml);

                         if($field<>'') {
                             $results[$xmlarray['rawname']]=$xmlarray[$field];
                         } else {
                             $results[$xmlarray['rawname']]=$xmlarray;
                         }
                     }
                }
            }
        }
        closedir($handle);
    }
    return $results;
}

function plugin_read_xml_params($rawname) {
    global $PLUGIN_DIR;
    if(is_readable($PLUGIN_DIR.$rawname.'/plugin.xml')) {
        $data = file_get_contents($PLUGIN_DIR.$rawname.'/plugin.xml');
        $xml = simplexml_load_string($data);
        $xmlarray = simple_xml_to_array($xml);
    }
    $ret = isset($xmlarray['params'])?$xmlarray['params']:array();

    // Enclose single item parameter into an indexed array
    if(isset($ret['param']['name'])) {
        $atmp['param'][]=$ret['param'];
        $ret  = $atmp;
    }
    return $ret;
}

function plugin_insert_missing_db() {
    // Inserta datos de plugins localmente instalados en la tabla fop2plugins 
    global $db;
    $plugsondb = array();
    $plugin_db = fop2_list_plugins();
    foreach($plugin_db as $idx=>$data) {
        $plugsondb[] = $data['rawname'];
    }
    $plugin_local = get_installed_plugins();
    foreach($plugin_local as $rawname=>$data) {
        if(!in_array($rawname,$plugsondb)) {
            if(!isset($data['global'])) { $data['global']=0; }
            if($data['global']=="yes" || $data['global']=="1") { $global=1; } else { $global=0; }
            $results = $db->consulta( "INSERT INTO fop2plugins (rawname, name, version, description, global) values ('%s','%s','%s','%s', '%s') ", 
                                  array( $data['rawname'] ,$data['name'], $data['version'], substr($data['description'],0,254), $global ) );
        }
    }
}

function fop2_recreate_default_groups($predefined_groups,$panelcontext,$wherepanelcontext,$fop2_buttons='') {
    global $db;

    $qry = "DELETE FROM fop2setup WHERE keyword='dbready'";
    $results = $db->consulta($qry);

    $qry = "INSERT INTO fop2setup (keyword,parameter,value) VALUES ('dbready',UNIX_TIMESTAMP(now()),0)";
    $results = $db->consulta($qry);

    if($fop2_buttons == '') {
        $fop2_buttons   = fop2_all_buttons();
    }

    foreach($predefined_groups as $id=>$gdata) {
        $qry = "REPLACE INTO fop2groups (id,name,context_id) values('${gdata['id']}','${gdata['name']}','$panelcontext')";
        #echo "$qry\n";
        $results = $db->consulta($qry);
    }

    $channels     = system_all_values('channel',1);
    $types        = system_all_values('type');

    $sql = "DELETE FROM fop2GroupButton WHERE group_name='All Buttons' $wherepanelcontext";
    #echo "$sql\n";
    $results = $db->consulta($sql);

    // All Extension = -2
    $sql = "DELETE FROM fop2GroupButton WHERE group_name='All Extensions' $wherepanelcontext";
    #echo "$sql\n";

    $results = $db->consulta($sql);
    foreach($channels as $chan=>$val) {
        if($types[$chan]=='extension') {
            if(isset($fop2_buttons[$val])) {
                $btid = $fop2_buttons[$val]['devid'];
                $sql = "INSERT INTO fop2GroupButton (group_name,id_button,context_id) VALUES ('All Extensions','$btid','$panelcontext')";
                $results = $db->consulta($sql);
                $sql = "INSERT INTO fop2GroupButton (group_name,id_button,context_id) VALUES ('All Buttons','$btid','$panelcontext')";
                $results = $db->consulta($sql);
            } 
        }
    }

    // All Queues
    $sql = "DELETE FROM fop2GroupButton WHERE group_name='All Queues' $wherepanelcontext";
    $results = $db->consulta($sql);
    foreach($channels as $chan=>$val) {
        if($types[$chan]=='queue') {
            if(isset($fop2_buttons[$val])) {
                $btid = $fop2_buttons[$val]['devid'];
                $sql = "INSERT INTO fop2GroupButton (group_name,id_button,context_id) VALUES ('All Queues','$btid','$panelcontext')";
                $results = $db->consulta($sql);
                $sql = "INSERT INTO fop2GroupButton (group_name,id_button,context_id) VALUES ('All Buttons','$btid','$panelcontext')";
                $results = $db->consulta($sql);
            }
        }
    }

    // All Conferences
    $sql = "DELETE FROM fop2GroupButton WHERE group_name='All Conferences' $wherepanelcontext";
    $results = $db->consulta($sql);
    foreach($channels as $chan=>$val) {
        if($types[$chan]=='conference') {
            if(isset($fop2_buttons[$val])) {
                $btid = $fop2_buttons[$val]['devid'];
                $sql = "INSERT INTO fop2GroupButton (group_name,id_button,context_id) VALUES ('All Conferences','$btid','$panelcontext')";
                $results = $db->consulta($sql);
                $sql = "INSERT INTO fop2GroupButton (group_name,id_button,context_id) VALUES ('All Buttons','$btid','$panelcontext')";
                $results = $db->consulta($sql);
            }
        }
    }

    // All Trunks
    $sql = "DELETE FROM fop2GroupButton WHERE group_name='All Trunks' $wherepanelcontext";
    $results = $db->consulta($sql);
    foreach($channels as $chan=>$val) {
        if($types[$chan]=='trunk') {
            if(isset($fop2_buttons[$val])) {
                $btid = $fop2_buttons[$val]['devid'];
                $sql = "INSERT INTO fop2GroupButton (group_name,id_button,context_id) VALUES ('All Trunks','$btid','$panelcontext')";
                $results = $db->consulta($sql);
                $sql = "INSERT INTO fop2GroupButton (group_name,id_button,context_id) VALUES ('All Buttons','$btid','$panelcontext')";
                $results = $db->consulta($sql);
            }
        }
    }

    foreach($channels as $chan=>$val) {
        if($types[$chan]=='park' || $types[$chan]=='ringgroup') {
            if(isset($fop2_buttons[$val])) {
                $btid = $fop2_buttons[$val]['devid'];
                $sql = "INSERT INTO fop2GroupButton (group_name,id_button,context_id) VALUES ('All Buttons','$btid','$panelcontext')";
                $results = $db->consulta($sql);
            }
        }
    }

    $qry = "DELETE FROM fop2setup WHERE keyword='dbready'";
    $results = $db->consulta($qry);

}

function fop2_rrmdir($dir) {

    if(!preg_match('/\/plugins/',$dir) && !preg_match('/\/_cache/',$dir)) { die("no dice $dir"); }

    foreach(glob($dir . '/*') as $file) {
        if(is_dir($file)) { fop2_rrmdir($file); } else { unlink($file); }; 
    }
    rmdir($dir); 
}

function plugin_delete($itemid) {
    global $db, $PLUGIN_DIR;
    $itemid = preg_replace("/[^A-Za-z0-9\._-]/","",$itemid);
    $rawname = $itemid;
    $deldir = substr(escapeshellarg($PLUGIN_DIR.$rawname),1,-1);
    fop2_rrmdir($deldir);
    $results = $db->consulta( "DELETE FROM fop2plugins WHERE rawname='".$db->escape_string($rawname)."'");
    $results = $db->consulta( "DELETE FROM fop2UserPlugin WHERE id_plugin='".$db->escape_string($rawname)."'");

}

function plugin_handleupload($uploaded_file) {

    global $PLUGIN_DIR;

    $directory = dirname(__FILE__);
    $errors = array();
        
    if (!isset($uploaded_file['tmp_name']) || !file_exists($uploaded_file['tmp_name'])) {
        $errors[] = __("Error finding uploaded file. Did you select one? Check your PHP and/or web server configuration.");
        return $errors;
    }
        
    if (!preg_match('/\.(tar\.gz|tgz)$/', $uploaded_file['name'])) {
        $errors[] = __("File must be in tar+gzip (.tgz or .tar.gz) format");
        return $errors;
    }
        
    if (!preg_match('/^([A-Za-z][A-Za-z0-9_]+)\-([0-9a-zA-Z]+(\.[0-9a-zA-Z]+)*)\.(tar\.gz|tgz)$/', $uploaded_file['name'], $matches)) {
        $errors[] = __("Filename does not have the correct format: it must be pluginname-version.tar.gz (eg. customplugin-0.1.tar.gz)");
        return $errors;
    } else {
        $pluginname    = $matches[1];
        $pluginversion = $matches[2];
    }
        
    $temppath = $directory.'/_cache/'.uniqid("upload");

    if (! @mkdir($temppath) ) {
        return array(sprintf(__("Error creating temporary directory: %s"), $temppath));
    }
    $filename = $temppath.'/'.$uploaded_file['name'];
        
    move_uploaded_file($uploaded_file['tmp_name'], $filename);
        
    exec("tar ztf ".escapeshellarg($filename), $output, $exitcode);
    if ($exitcode != 0) {
        $errors[] = __("Error untaring uploaded file. Must be a tar+gzip file");
        return $errors;
    }
    foreach ($output as $line) {
        // make sure all lines start with "pluginname/"
        if (!preg_match('/^'.$pluginname.'\//', $line)) {
            $errors[] = 'File extracting to invalid location: '.$line;
        }
    }
    if (count($errors)) {
        return $errors;
    }

    // remove old cache dir if it exists
    exec("rm -rf ".$directory."/_cache/$pluginname", $output, $exitcode);
    if ($exitcode != 0) {
        return array(sprintf(__('Could not remove %s to install new version'), $directory.'/_cache/'.$pluginnam));
    }

    // extract downlaoded file into cache dir
    exec("tar zxf ".escapeshellarg($filename)." -C ".escapeshellarg($directory.'/_cache/'), $output, $exitcode);
    if ($exitcode != 0) {
        return array(sprintf(__('Could not untar %s to %s'), $filename, $directory.'/_cache'));
    }

    // Read current ini file before extraction and save its values
    $ini_array = array();
    $current_inifile = substr(escapeshellarg($PLUGIN_DIR."$pluginname/$pluginname.ini"),1,-1);
    if(file_exists($current_inifile)) {
        $ini_array = parse_ini_file($current_inifile, true);
    }

    // remove current plugin directory if it exists
    exec("rm -rf ".$PLUGIN_DIR.$pluginname, $output, $exitcode);
    if ($exitcode != 0) {
        return array(sprintf(__('Could not remove old plugin %s to install new version'), $PLUGIN_DIR.$pluginname));
    }

    // move downlaoded version to active plugin directory
    exec("mv ".$directory."/_cache/$pluginname ".$PLUGIN_DIR.$pluginname, $output, $exitcode);
    if ($exitcode != 0) {
        return array(sprintf(__('Could not move %s to %s'), $directory."/_cache/$pluginname", $PLUGIN_DIR));
    }

    // remove temporary directory/files
    exec("rm -rf ".$temppath);
    if ($exitcode != 0) {
        return array(sprintf(__('Could not remove temporary upload directory: %s'), $temppath));
    }

    // original ini was not empty, regenerate ini file with old content, merged with new if there are new keys
    if(count($ini_array)>0) {
        $ini_array_new = parse_ini_file($current_inifile, true);
        $array_final = array_replace_recursive($ini_array_new, $ini_array);
        $res = array();
        foreach($array_final as $key => $val)
        {
            if(is_array($val))
            {
                $res[] = "[$key]";
                foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
            }
            else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
        }
        file_put_contents($current_inifile, implode("\r\n", $res), LOCK_EX);
    }



    return true;
}

function fop2_get_version() {
    global $conf, $fop2conf;
    $timeout   = 5;
    $host      = isset($fop2conf['listen_ip'])?$fop2conf['listen_ip']:"localhost";
    $context   = "GENERAL";
    $port      = $conf['fop2port'];
    $dati      = "";

    $sk=fsockopen($host,$port,$errnum,$errstr,$timeout) ;
    if (!is_resource($sk)) {
        $version = "2.00!0!0";
        $partes = preg_split("/!/",$version);
        return $partes;
    } else {
        fputs($sk, "<msg data=\"$context|version|0|0\" />\0");
        stream_set_timeout($sk, 2);
        $dati.=fgets ($sk, 2048);
    }
    fclose($sk);
    $dati=preg_replace("/{.*}/","",$dati);
    if($dati=='') { $dati='2.00!0!0'; }
    $partes = preg_split("/!/",$dati);
    $partes[1] = base64_decode($partes[1]);
    return $partes;
}

function plugin_get_licensed() {
    global $conf, $fop2conf;
    $timeout   = 5;
    $host      = isset($fop2conf['listen_ip'])?$fop2conf['listen_ip']:"localhost";
    $context   = "GENERAL";
    $port      = $conf['fop2port'];
    $dati      = "";

    $sk=fsockopen($host,$port,$errnum,$errstr,$timeout) ;
    if (!is_resource($sk)) {
        return '0';
    } else {
        fputs($sk, "<msg data=\"$context|plugins|0|0\" />\0");
        stream_set_timeout($sk, 2);
        $dati.=fgets ($sk, 2048);
    }
    fclose($sk);
    $dati=preg_replace("/{.*}/","",$dati);
    $dati=preg_replace("/[^a-zA-Z,]/","",$dati);
    return $dati;
}

function fop2_print_config_form($rawname) {
    global $PLUGIN_DIR;

    $inifile = $PLUGIN_DIR."$rawname/$rawname.ini";

    if(file_exists($inifile)) {
        $ini_array = parse_ini_file($inifile, true);
    } else {
        $ini_array = array();
    }
    $convertToArray = 0;
    $multiform      = array();
    $multiform_type = array();
    $allpars        = plugin_read_xml_params($rawname);

    $ret="<h3>".__('Settings')."</h3><form id='plugin_$rawname' class='form-horizontal'>\n";
    $ret.="<fieldset style='display:none; margin-top:10px;' id='form-$rawname-skeleton'>\n";
    $ret.="<legend><span id='legend-".$rawname."-skeleton'></span></legend>\n";

    foreach ( $allpars['param'] as $key=>$param ) {
        if(!is_array($param)) {
           $convertToArray=1;
           break;
        }
    }

    if($convertToArray==1) {
        $tempA              = $allpars['param'];
        $allpars['param']   = array();
        $allpars['param'][] = $tempA;
    }

    $fftype            = array();
    $fftype['string']  = 'text';
    $fftype['text']    = 'text';
    $fftype['bool']    = 'bool';
    $fftype['integer'] = 'number';
    $fftype['number']  = 'number';
 
    // Generates Skeleton Hidden FORM for cloning multiple ini sections
    foreach ( $allpars['param'] as $key=>$param ) {

       $formname          = $param['name'];
       $formtype          = $param['type'];
       $formdefault       = isset($param['default'])?$param['default']:'';
       $formmulti         = isset($param['multi'])?$param['multi']:'';
       $formencoded       = isset($param['encoded'])?$param['encoded']:'no';
       $formformat        = isset($param['format'])?$param['format']:'';
       if($formencoded=='yes') { $formdefault = is_array($formdefault)?'':base64_decode($formdefault); }

       if(is_array($formdefault)) {
          $formdefault='';
       }

       if($formmulti=='yes') {

           $partes_name       = preg_split("/_/",$formname);
           $finalname         = '';
           foreach($partes_name as $parte) {
               $finalname.=" ".ucfirst($parte);
           }
 
           $ret.="<div class='form-group'>\n";
           $ret.="<label class='col-sm-3 control-label' for='$rawname-".$formname."-skeleton'>$finalname:</label>";
           $ret.="<div class='col-sm-9'>\n";

           // $ret.="<input type='".$fftype[$formtype]."' name='$rawname-".$formname."-skeleton' id='$rawname-".$formname."-skeleton' value='".$formdefault."' class='form-control'>";

          if($formtype=="text") {
               if($formformat <> '') {
                  $formatattr = " data-format='$formformat' ";
               } else {
                  $formatattr = '';
               }
               $ret.="<textarea class='form-control' rows=4 ";
               $ret.="name='$rawname-".$formname."-skeleton' id='$rawname-".$formname."-skeleton' $formatattr>".htmlspecialchars($formdefault)."</textarea>";
           } else if($formtype=="bool") {
               $ret.="<input type='hidden' value='0' name='$rawname-".$formname."-skeleton' id='$rawname-".$formname."-skeleton'>\n";
               $ret.="<input type='checkbox' data-on-text='".__('Yes')."' data-off-text='".__('No')."' class='chk' name='$rawname-".$formname."-skeleton' id='$rawname-".$formname."-skeleton' value='1' ";
               if($formdefault==1) { $ret.=" checked "; }
               $ret.=">";
           } else {
               $ret.="<input class='form-control' type='".$fftype[$formtype]."' ";
               $ret.="name='$rawname-".$formname."-skeleton' id='$rawname-".$formname."-skeleton' value=\"".htmlspecialchars($formdefault)."\">";
           }

           $ret.="</div>\n";
           $ret.="</div>\n";
       }
    }

    $ret.="<button class='btn btn-default pull-right' id='removesection-$rawname-skeleton' onClick='fop2_removeSection(this.id); return false;'>".__('Remove Section')."</button><br/>";
    $ret.="</fieldset>\n\n";

    $fftype            = array();
    $fftype['string']  = 'text';
    $fftype['integer'] = 'number';
    $fftype['number']  = 'number';
    // Display Actual Plugin Connfig Form
    $ret.="<fieldset id='form-$rawname-default' style='margin-top:10px;'>\n";
    foreach ( $allpars['param'] as $key=>$param ) {
       $formname          = $param['name'];
       $formtype          = $param['type'];
       $formdefault       = isset($param['default'])?$param['default']:'';
       $formencoded       = isset($param['encoded'])?$param['encoded']:'no';
       $formformat        = isset($param['format'])?$param['format']:'';
       if(is_array($formdefault)) { $formdefault=''; }
       $formmulti         = isset($param['multi'])?$param['multi']:'no';
       $formonlymulti     = isset($param['onlymulti'])?$param['onlymulti']:'no';
       $formval           = (isset($ini_array[$formname]))?$ini_array[$formname]:$formdefault;
       if($formencoded=='yes') { $formval = base64_decode($formval); }

       $partes_name       = preg_split("/_/",$formname);
       $finalname         = '';
       
       if($formonlymulti<>'yes') {  // do not display an 'onlymulti' ini in the main section
           $ret.="<div class='form-group'>\n";
           $ret.="<label class='col-sm-3 control-label' for='$rawname-".$formname."-default'>";
           foreach($partes_name as $parte) {
               $finalname.=" ".ucfirst($parte);
           }
           $ret.="$finalname:</label>";
           $ret.="<div class='col-sm-9'>\n";
      
           if($formtype=="text") {
               if($formformat <> '') {
                  $formatattr = " data-format='$formformat' ";
               } else {
                  $formatattr = '';
               }
               $ret.="<textarea class='form-control' rows=4 ";
               $ret.="name='$rawname-".$formname."-default' id='$rawname-".$formname."-default' $formatattr>".htmlspecialchars($formval)."</textarea>";
           } else if($formtype=="bool") {
               $ret.="<input type='hidden' value='0' name='$rawname-".$formname."-default' id='$rawname-".$formname."-default'>\n";
               $ret.="<input type='checkbox' data-on-text='".__('Yes')."' data-off-text='".__('No')."' class='chk' name='$rawname-".$formname."-default' id='$rawname-".$formname."-default' value='1' ";
               if($formval==1) { $ret.=" checked "; }
               $ret.=">";
           } else {
               $ret.="<input class='form-control' type='".$fftype[$formtype]."' ";
               $ret.="name='$rawname-".$formname."-default' id='$rawname-".$formname."-default' value=\"".htmlspecialchars($formval)."\">";
           }

           $ret.="</div>\n";
           $ret.="</div>\n";
       }

       if($formmulti=='yes') {
           $multiform[$formname]         = $formdefault;
           $multiform_type[$formname]    = $formtype;
           $multiform_encoded[$formname] = $formencoded;
           $multiform_format[$formname]  = $formformat;
       }
    }
    $ret.="</fieldset>\n";

    foreach($ini_array as $section=>$val) {
        if(is_array($val)) {
            $ret.="<fieldset id='form-$rawname-$section' style='margin-top:10px;'><legend style='border-top:1px solid #aaa;'>$section</legend>\n";
            foreach($multiform as $formname=>$formdefault) {

                $formtype = $multiform_type[$formname];
                $formencoded = $multiform_encoded[$formname];
                $formformat = $multiform_format[$formname];

                $formval = (isset($ini_array[$section][$formname]))?$ini_array[$section][$formname]:$formdefault;
                if($formencoded=='yes') { $formval = base64_decode($formval); }

                $partes_name       = preg_split("/_/",$formname);
                $finalname         = '';
                foreach($partes_name as $parte) {
                    $finalname.=" ".ucfirst($parte);
                }
 
                $ret.="<div class='form-group'>\n";

       $ret.="<label class='col-sm-3 control-label' for='$rawname-".$formname."-$section'>";
       $finalname='';
       foreach($partes_name as $parte) {
           $finalname.=" ".ucfirst($parte);
       }
       $ret.="$finalname:</label>";
       $ret.="<div class='col-sm-9'>\n";

      if($formtype=="text") {
           if($formformat <> '') {
              $formatattr = " data-format='$formformat' ";
           } else {
              $formatattr = '';
           }
           $ret.="<textarea class='form-control' rows=4 ";
           $ret.="name='$rawname-".$formname."-$section' id='$rawname-".$formname."-$section' $formatattr>".htmlspecialchars($formval)."</textarea>";
       } else if($formtype=="bool") {
           $ret.="<input type='hidden' value='0' name='$rawname-".$formname."-$section' id='$rawname-".$formname."-$section'>\n";
           $ret.="<input type='checkbox' data-on-text='".__('Yes')."' data-off-text='".__('No')."' class='chk' name='$rawname-".$formname."-$section' id='$rawname-".$formname."-$section' value='1' ";
           if($formval==1) { $ret.=" checked "; }
           $ret.=">";
       } else {
           $ret.="<input class='form-control' type='".$fftype[$formtype]."' ";
           $ret.="name='$rawname-".$formname."-$section' id='$rawname-".$formname."-$section' value=\"".htmlspecialchars($formval)."\">";
       }

       $ret.="</div>\n";
       $ret.="</div>\n";

 
//                $ret.="<label class='col-sm-2 control-label' for='$rawname-".$formname."-$section'>$finalname:</label>";
//                $ret.="<div class='col-sm-10'>\n";
//                $ret.="<input class='form-control' type='".$fftype[$formtype]."' name='$rawname-".$formname."-$section' id='$rawname-".$formname."-$section' value='".htmlspecialchars($formval)."'>";
//                $ret.="</div>\n"; 
//                $ret.="</div>\n";
            }
            $ret.="<button class='btn btn-default pull-right' id='removesection-$rawname-$section' onClick='fop2_removeSection(this.id); return false;'>".__('Remove Section')."</button><br/>";
            $ret.="</fieldset>\n";
        }
    }
    $ret.="</form>\n<hr style='border-top-color:#aaa; margin-top:10px; margin-bottom:10px;'/>";

    $ret.="<div class='pull-right'>";
    if(count($multiform)>0) {
        $ret.="<button class='btn btn-default' onClick='fop2_addConfig(\"$rawname\",\"plugin_$rawname\",\"".__('Enter Section')."\",\"".__("That section already exists!")."\",\"".__("Section cannot be empty!")."\"); return false;'>".__('Add Section')."</button>";
    }
    $ret.="&nbsp;&nbsp;<button class='btn btn-default' onClick='fop2_saveConfig(\"$rawname\",\"".__("Configuration saved")."\"); return false;'>".__('Save')."</button>";
    $ret.="</div>";
    return $ret;
}

function ping($host,$timeout=1) {
    /* ICMP ping packet with a pre-calculated checksum */
    // $ip = gethostbyname($host);
    $ip = get_addr_by_host($host);

    if($ip==$host) {
       return 1000;
    }

    $output = array();
    $com = 'ping -n -w ' . $timeout . ' -c 1 ' . $ip;
    
    $exitcode = 0;

    exec($com, $output, $exitcode);

    if ($exitcode == 0 || $exitcode == 1)
    { 
        foreach($output as $cline)
        {
            if (strpos($cline, ' bytes from ') !== FALSE)
            {
                $out = (int)ceil(floatval(substr($cline, strpos($cline, 'time=') + 5)));
                return $out;
            }
        }
    }
    
    return 1000;
}

function get_addr_by_host($host, $timeout = 3) {
    $query = `nslookup -timeout=$timeout -retry=1 $host`;
    if(!is_null($query)) {
        if(preg_match('/\nAddress: (.*)\n/', $query, $matches)) {
            return trim($matches[1]);
        }
        return $host;
    } else {
        $ip = gethostbyname($host);
        return $ip;
    }
}

function plugin_download($itemid, $mirror) {
    global $db, $PLUGIN_DIR;

    $itemid = preg_replace("/[^A-Za-z0-9\._-]/","",$itemid);
    list ( $rawname, $nada) = preg_split("/-/",$itemid,2);
    $fn = "http://$mirror/plugins/$itemid.tgz";

    $pluginjs = file_get_contents($fn);
    if($pluginjs===false) {
        echo('ERROR1 404 FILE NOT FOUND');
        return;
    }
    $tgzfile = substr(escapeshellarg($PLUGIN_DIR."$itemid.tgz"),1,-1);

    // Read current ini file before extraction and save its values
    $ini_array = array();
    $current_inifile = substr(escapeshellarg($PLUGIN_DIR."$rawname/$rawname.ini"),1,-1);
    if(file_exists($current_inifile)) {
        $ini_array = parse_ini_file($current_inifile, true);
    } 

    $fp = fopen($tgzfile,"w");
    fputs($fp,$pluginjs);
    fclose($fp);

    exec("tar zxf ".$tgzfile." -C ".escapeshellarg($PLUGIN_DIR), $output, $exitcode);
    unlink($tgzfile);

    if($exitcode==0) {
        // Insertar en la base de datos el plugin
        $infoxml   = substr(escapeshellarg($PLUGIN_DIR."$rawname/plugin.xml"),1,-1);
        $pluginxml = file_get_contents($infoxml);
        $global    = value_in('global',$pluginxml);
        $rawname   = value_in('rawname',$pluginxml);
        $name      = value_in('name',$pluginxml);
        $version   = value_in('version',$pluginxml);
        $description = value_in('description',$pluginxml);
        $results = $db->consulta( "REPLACE INTO fop2plugins (rawname, name, version, description, global) values ('".$db->escape_string($rawname)."','".$db->escape_string($name)."','".$db->escape_string($version)."','".$db->escape_string($description)."','$global') ");

        // original ini was not empty, regenerate ini file with old content, merged with new if there are new keys
        if(count($ini_array)>0) {
            $ini_array_new = parse_ini_file($current_inifile, true);
            $array_final = array_replace_recursive($ini_array_new, $ini_array);
            $res = array();
            foreach($array_final as $key => $val)
            {
                if(is_array($val))
                {
                    $res[] = "[$key]";
                    foreach($val as $skey => $sval) $res[] = "$skey = ".(is_numeric($sval) ? $sval : '"'.$sval.'"');
                }
                else $res[] = "$key = ".(is_numeric($val) ? $val : '"'.$val.'"');
            }
            file_put_contents($current_inifile, implode("\r\n", $res), LOCK_EX);
        }

        echo 'OK';
    } else {
        echo "ERROR1 $exitcode";
    }
}

if (!function_exists('array_replace_recursive'))
{
  function array_replace_recursive($array, $array1)
  {
    function recurse($array, $array1)
    {
      foreach ($array1 as $key => $value)
      {
        // create new key in $array, if it is empty or not an array
        if (!isset($array[$key]) || (isset($array[$key]) && !is_array($array[$key])))
        {
          $array[$key] = array();
        }

        // overwrite the value in the base array
        if (is_array($value))
        {
          $value = recurse($array[$key], $value);
        }
        $array[$key] = $value;
      }
      return $array;
    }

    // handle the arguments, merge one by one
    $args = func_get_args();
    $array = $args[0];
    if (!is_array($array))
    {
      return $array;
    }
    for ($i = 1; $i < count($args); $i++)
    {
      if (is_array($args[$i]))
      {
        $array = recurse($array, $args[$i]);
      }
    }
    return $array;
  }
}
function value_in($element_name, $xml, $content_only = true) {
    if ($xml == false) {
        return false;
    }
    $found = preg_match('#<'.$element_name.'(?:\s+[^>]+)?>(.*?)'.
            '</'.$element_name.'>#s', $xml, $matches);
    if ($found != false) {
        if ($content_only) {
            return $matches[1];  //ignore the enclosing tags
        } else {
            return $matches[0];  //return the full pattern match
        }
    }
    // No match found: return false.
    return false;
}

function fop2manager_core_download($itemid, $mirror) {

    global $db;

    $directory = dirname(__FILE__)."/_cache/";

    if(!is_dir($directory)) {
        mkdir($directory);
    }

    $itemid = preg_replace("/[^A-Za-z0-9\._-]/","",$itemid);
    $fn = "http://$mirror/plugins/$itemid.tgz";

    $pluginjs = @ file_get_contents($fn);
    $tgzfile = substr(escapeshellarg($directory."$itemid.tgz"),1,-1);

    echo "tgz $tgzfile, dir $directory<br>";

    $fp = fopen($tgzfile,"w");
    fputs($fp,$pluginjs);
    fclose($fp);

    exec("tar zxf ".$tgzfile." -C ".escapeshellarg($directory), $output, $exitcode);
    unlink($tgzfile);

    if($exitcode==0) {
        // Actualizar
        die('OK');
    } else {
        die("ERROR1 $exitcode");
    }
}

function parse_conf($filename,$section='') {
    // section is for parsing a specific [section] of an .ini or .conf file only

    global $config_engine, $conf;

    $file = file($filename);
    $current_section='';

    foreach ($file as $line) {

        if (preg_match("/^\[(.*)\]/",$line,$matches)) {
            $current_section = trim($matches[1]);
        }
        if (preg_match("/^\s*([^=]*)\s*=\s*[\"']?([\w\/\:\.\,\}\{\>\<\(\)\*\?\%!=\+\#@&\\$-]*)[\"']?\s*([;].*)?/",$line,$matches)) {
            if(preg_match('/\$amp_conf/',$matches[1])) {
                $matches[1] = preg_replace('/\$amp_conf\[[\'\"]/','',$matches[1]);
                $matches[1] = trim($matches[1]);
                $matches[1] = substr($matches[1],0,-2);
            }
            $matches[1] = trim($matches[1]);
            $matches[1] = preg_replace("/^AMP/","",$matches[1]);
            if($config_engine=='thirdlane_db') {
                if($matches[1]=='DBCONFIG') { $matches[1]='DBNAME'; $conf['DBHOST']='localhost';} // Convert thirdlane db name entry
            }
            if( $section=='' || $section==$current_section) {
                if(substr($matches[1],0,1)!=";") {
                    $conf[ $matches[1] ] = $matches[2];
                }
            }
        }
    }

    if($config_engine=='thirdlane_db' || $config_engine=='thirdlane_old') {
        $conf['MGRPORT']='5038';
        $conf['MGRUSER']='internal';
        $conf['MGRPASS']='insecure';
    } 

    if(!isset($conf['MGRPORT'])) {
        $conf['MGRPORT']='5038';
    }

    if(!isset($conf['MGRHOST'])) {
        $conf['MGRHOST']='127.0.0.1';
    }

    if(!isset($conf['DBHOST']) || $conf['DBHOST']=='') {
        $conf['DBHOST']='127.0.0.1';
    }

    return $conf;
}

function fop2_insert_users() {
    global $db, $panelcontext;

    $cont       = 0;
    $extensctx  = array();
    $vmpins     = system_all_values('vmpin',1);
    $extensions = system_all_values('exten');
    $types      = system_all_values('type');
    $pnlctx     = system_all_values('context_id');

    $existingexten=array();
    $fop2users = fop2_get_users();
    if(is_array($fop2users)) {
        foreach($fop2users as $idx=>$dat) {
            $existingexten[]=$dat['exten'];
        }
    }

    if($panelcontext<>'') {
        $where = " AND context_id='$panelcontext' ";
    } else {
        $where = ' AND context_id=0 ';
    }

    foreach ($types as $chan=>$type) {
        if($type=='extension') {
            if(isset($vmpins[$chan])) {
                $extensctx[$pnlctx[$chan]][$extensions[$chan]]=$vmpins[$chan];
            } else {
                $extensctx[$pnlctx[$chan]][$extensions[$chan]]=$extensions[$chan];
            }
        }
    }

    $selected_def_template = fop2_get_deftemplate();

    if($selected_def_template>0) {
        $selected_def_perm     = fop2_get_defperm();
        $selected_def_group    = fop2_get_defgroup();
        $selected_def_plugin   = fop2_get_defplugin();
    }

    foreach($extensctx as $pnlctx=>$extens) {
        if($pnlctx==$panelcontext) {
            ksort($extens);
            $permisos='all';
            foreach($extens as $exten=>$clave) {
                if(in_array($exten,$existingexten)) {
                    continue;
                }
                $cont++;
                if($selected_def_template>0) {

                    $results = $db->consulta("DELETE FROM fop2UserGroup WHERE exten = '$exten' $where");
                    $results = $db->consulta("DELETE FROM fop2UserPlugin WHERE exten = '$exten' $where");
                    $results = $db->consulta("DELETE FROM fop2UserTemplate WHERE exten = '$exten' $where");

                    $results = $db->consulta("INSERT INTO fop2users (exten,secret,permissions,context_id) VALUES ('$exten','$clave','$selected_def_perm','$pnlctx')");

                    foreach($selected_def_group as $groupid) { 
                        $results = $db->consulta("INSERT INTO fop2UserGroup (exten,id_group,context_id) VALUES ('$exten',$groupid,'$pnlctx')","query");
                    }

                    foreach($selected_def_plugin as $rawname) { 
                        $results = $db->consulta("INSERT INTO fop2UserPlugin (exten,id_plugin,context_id) VALUES ('$exten','$rawname','$pnlctx')","query");
                    }

                    $results = $db->consulta("INSERT INTO fop2UserTemplate (exten,id_template,context_id) VALUES ('$exten',$selected_def_template,'$pnlctx')","query");

                } else {
                    $results = $db->consulta("INSERT INTO fop2users (exten,secret,permissions,context_id) VALUES ('$exten','$clave','$permisos','$pnlctx')");
                    $permisos="dial,transfer,transferexternal,pickup,phonebook,preferences,hangupself,chat,callhistory";
                    $results = $db->consulta("INSERT INTO fop2UserGroup (exten,id_group,context_id) VALUES ('$exten',-1,'$pnlctx')","query");
                }
            }
        }
    }
    return $cont;
}

function fop2_permissions() {
    // Returns list of fop2 permissions as an array
    $perm = array( 'all', 'dial', 'hangup', 'hangupself', 'meetme', 
                   'pickup', 'record', 'recordself', 'spy', 'transfer', 'whisper',
                   'transferexternal', 'queuemanager', 'queueagent', 'queuepause',
                   'readonly_phonebook','phonebook','chat','chatadmin', 'broadcast', 'preferences',
                   'voicemailadmin','sms','smsmanager','callhistory','managetags','fullcallhistory','recordings'
            );

    $plugin_local = get_installed_plugins();
    foreach($plugin_local as $rawname=>$data) {
        if(isset($data['additionalperm'])) {
            if(!is_array($data['additionalperm'])) {
               $new = array($data['additionalperm']);
               $data['additionalperm']=$new;
            }
            foreach($data['additionalperm'] as $iperm) {
                if(is_array($iperm)) {
                    foreach($iperm as $pperm) {
                        $perm[]=$pperm;
                    }
                } else {
                    $perm[]=$iperm;
                }
            }
        }
    }
    $perm = array_unique($perm);
    asort($perm);
    return $perm;
}

function plugin_get_custom_button_fields() {
    $plugin_local = get_installed_plugins();
    $custom_fields=array();
    foreach($plugin_local as $rawname=>$data) {
        if(isset($data['custombuttonfields'])) {
            $custom_fields = array_merge($custom_fields,explode(' ',$data['custombuttonfields']));
        }
    }
    asort($custom_fields);
    return $custom_fields;
}


function debug($data,$type='log') {
    global $DEBUG;
    if($DEBUG>0) {
         if($type=='error') {
             je($data);
         } else {
             j($data);
         }
    }
}

function fop2manager_error($errno, $errstr, $errfile, $errline) {
    global $DEBUG;
    $error_level = error_reporting();

    if($error_level==0) { return; }

    switch ($errno) {
        case E_USER_ERROR:
            $extraclass='danger';
            break;
        case E_USER_WARNING:
            $extraclass='warning';
            break;
        case E_USER_NOTICE:
            $extraclass='info';
            break;
        default:
           $extraclass="info";
           break;
    }
    if($DEBUG==0 && $extraclass=='info') {  return; }

    try {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    } catch (Exception $e) {
        je("$errno, $errstr ($errfile, $errline");
    }

}

function fop2_get_contexts() {
    global $db;
    $resultsfinal = array();
    $results = $db->select("id,name","fop2contexts","","");
    if($results==false) {
        $resultsfinal[0]='GENERAL';
    } else {
        foreach($results as $idx => $data) {
             $resultsfinal[$data['id']]=$data['name'];
        }
    }
    return $resultsfinal;
}

function fop2_populate_contexts() {
    global $config_engine;
    if($config_engine=='thirdlane_db') {
        $panelcontexts = thirdlane_populate_contexts_from_tenants();
    } else if($config_engine=='elastix_mt') {
        $panelcontexts = elastix_populate_contexts_from_tenants();
    } else if($config_engine=='mirtapbx') {
        $panelcontexts = mirtapbx_populate_contexts_from_tenants();
    } else if($config_engine=='pbxware') {
        $panelcontexts = pbxware_populate_contexts_from_tenants();
    } else if($config_engine=='peke') {
        $panelcontexts = peke_populate_contexts_from_tenants();
    } else if($config_engine=='custom') {
        $panelcontexts = custom_populate_contexts_from_tenants();
    } else {
        if(is_callable($config_engine."_populate_contexts_from_tenants")) {
            $panelcontexts = call_user_func($config_engine."_populate_contexts_from_tenants");
        } else {
            $panelcontexts[0]='GENERAL';
        }
    }
    return $panelcontexts;
}

function reload_fop2() {
    global $astman, $conf;

    if(!$res = $astman->connect($conf['MGRHOST'].':'.$conf['MGRPORT'], $conf['MGRUSER'] , $conf['MGRPASS'], 'off')) {
        unset($astman);
    }

    if ($astman) {
        $res = $astman->UserEvent('FOP2RELOAD',array('Channel'=>'ZAP/1'));
        unset($_SESSION[MYAP]['needsreload']);
    }
}

function reload_asterisk() {
    global $astman, $conf;

    if(!$res = $astman->connect($conf['MGRHOST'].':'.$conf['MGRPORT'], $conf['MGRUSER'] , $conf['MGRPASS'], 'off')) {
        unset($astman);
    }

    if ($astman) {
        $res = $astman->UserEvent('Reload',array('Channel'=>'ZAP/1'));
        unset($_SESSION[MYAP]['needsreload']);
    }
}

function buttons_custom_extension_usage() {
    $file = "/usr/local/fop2/buttons_custom.cfg";
    $extenlist=array();
    $channel='';

    if(is_readable($file)) {
        $lines = @file($file);

        foreach($lines as $line) {
            $line=trim($line);
            if(preg_match("/^\[/",$line)) {
                if($channel<>'') {
                    if(!isset($data['type'])) {
                        $data['type']='extension';
                    }
                    if(!isset($data['extension'])) {
                        $data['exten']=$channel;
                        $data['extension']=$channel;
                    }
                    if(!isset($data['context'])) {
                        $$data['context']='from-internal';
                    }
                    $extenlist[$channel] = $data;
                }
                preg_match("/\[([^]]*)].*/",$line,$matches);
                $channel = $matches[1];
                $data = array();
                $data['channel']=$channel;
                $data['context_id'] = 0; 
            } else {
                if($channel<>'') {
                    $partes = preg_split("/=/",$line);
                    $param = $partes[0];
                    $value = isset($partes[1])?trim($partes[1]):'';
                    if($param=='label') {
                        $data['name'] = $value;
                    } else
                    if($param=='extension') {
                        $data['exten'] = $value;
                        $data['extension'] = $value;
                        $data['vmpin'] = $value;
                    } else
                    if($param=='context') {
                        $data['context'] = $value;
                    } else
                    if($param=='mailbox') {
                        $data['mailbox'] = $value;
                    } else 
                    if($param=='channel') {
                        $data['extrachannel']=$value;
                    } else {
                        if(trim($param)<>'') {
                            $data[$param]=$value;
                        }
                    }
                }
            }
        }
        // last item
        if(!isset($data['type'])) {
            $data['type']='extension';
        }
        if(!isset($data['extension'])) {
            $data['exten']=$channel;
        }
        if(!isset($data['context'])) {
            $data['context']='from-internal';
        }
        $extenlist[$data['channel']] = $data;
    }
    return $extenlist;
}

function get_fop2manager_secure_levels() {
    global $db;

    $levels = array();

    if(!$db->table_exists('fop2managersecurelevel')) {
        $query = "CREATE TABLE `fop2managersecurelevel` ( `level` int(11) NOT NULL default '0', ";
        $query.= "`detail` varchar(30) default NULL, `icon` varchar(50) default NULL, PRIMARY KEY  (`level`), ";
        $query.= "UNIQUE KEY `det` (`detail`)) DEFAULT CHARSET=UTF8;";
        $db->consulta($query);

        $db->consulta("INSERT INTO fop2managersecurelevel (level,detail,icon) VALUES (1,'admin','fa fa-shield-alt')");
        $db->consulta("INSERT INTO fop2managersecurelevel (level,detail,icon) VALUES (2,'user','fa fa-user')");
    }

    $query="SELECT level,detail FROM fop2managersecurelevel";
    $res = $db->consulta($query);
    if($res) {
        if($db->num_rows($res)>0) {
            while($row=$db->fetch_assoc($res)) {
                $levels[$row['detail']]=$row['level'];
            }
        } else {
            $levels['admin']=1;
            $levels['user']=2;
        }
    } else {
        $levels['admin']=1;
        $levels['user']=2;
    }
    return $levels;
}

function render_custom_form_field($dd) {
    global $thisItem;
    global ${$dd['variablename']};
    $sel = 'selected_'.$dd['variablename'];
    global $$sel;

    $tiptext = isset($dd['helptext'])?$dd['helptext']:'';
    $ret= "<div class='form-group'>\n";
    $ret.= "<label for='${dd['name']}' class='col-sm-3 col-xs-12 control-label ttip' data-delay='{\"show\":\"750\", \"hide\":\"100\"}' data-toggle='popover' data-trigger='hover' data-placement='right' data-content='$tiptext'>".$dd['displayname']."</label>\n";

 
    if($dd['type']=='text') {

        $editvalue = isset(${$dd['variablename']}[$thisItem['exten']])?${$dd['variablename']}[$thisItem['exten']]:'';
        $editvalue = htmlspecialchars($editvalue);
        $ret.= "<div class='col-sm-8 col-xs-12'>\n";
        $ret.= "   <input type='text' class='form-control' id='${dd['name']}' name='${dd['name']}' value='$editvalue'>";
        $ret.= "</div>\n";
    } else if($dd['type']=='multiselect') {
        $ret.= "<div class='col-sm-8 col-xs-12'>\n";
        $ret.= "   <select multiple class='chosen-select form-control' id='${dd['variablename']}' name='${dd['name']}' >\n";
        $sel = "selected_${dd['variablename']}";
        foreach(${$dd['variablename']} as $pp) {
           $ret.= "<option value='$pp' ";
           if(in_array($pp,${$sel})) { $ret.= " selected "; }
           $ret.= ">$pp</option>";
        }
        $ret.= "   </select>\n";
        $ret.= "</div>\n";
    }
    $ret.= "</div>\n";
    return $ret;
}

function get_fop2manager_secure_levels_icons() {
    global $db;
    $iconclass=array();
    $res = $db->consulta("SELECT detail,icon FROM fop2managersecurelevel");
    if($res) {
        while($row=$db->fetch_assoc($res)) {
            $iconclass[$row['detail']]=$row['icon'];
        }
    } else {
        $iconclass['admin']='fa fa-shield-alt';
        $iconclass['user']='fa fa-user';
    }
    return $iconclass;
}


// Used for natural sorting or arrays
function natural_sort($a, $b) {
    return strnatcmp($a, $b);
}

// This one will get a version string like 2.27.33.beta and returns a computable version like 022733
function normalize_version($version) {
    $normalizedversion = '';
    $partes            = preg_split( "/\./", $version );
    $cuantos           = count($partes);
    for ( $i = 0 ; $i < $cuantos ; $i++ ) {
        if ( is_numeric( $partes[$i] ) ) {
            $normalizedversion .= sprintf( "%02d", $partes[$i] );
        }
    }
    return $normalizedversion;
}

/**
* Create a new directory, and the whole path.
*
* If  the  parent  directory  does  not exists, we will create it,
* etc.
* @todo
*     - PHP5 mkdir functoin supports recursive, it should be used
* @author baldurien at club-internet dot fr 
* @param string the directory to create
* @param int the mode to apply on the directory
* @return bool return true on success, false else
* @previousNames mkdirs
*/

function make_recursive_directories($dir, $mode = 0777, $recursive = true) {
    if( is_null($dir) || $dir === "" ){
        return FALSE;
    }
    
    if( is_dir($dir) || $dir === "/" ){
        return TRUE;
    }
    if( make_recursive_directories(dirname($dir), $mode, $recursive) ){
        return mkdir($dir, $mode);
    }
    return FALSE;
}

/**
 * Copies file or folder from source to destination, it can also do
 * recursive copy by recursively creating the dest file or directory path if it wasn't exist
 * Use cases:
 * - Src:/home/test/file.txt ,Dst:/home/test/b ,Result:/home/test/b -> If source was file copy file.txt name with b as name to destination
 * - Src:/home/test/file.txt ,Dst:/home/test/b/ ,Result:/home/test/b/file.txt -> If source was file Creates b directory if does not exsits and copy file.txt into it
 * - Src:/home/test ,Dst:/home/ ,Result:/home/test/** -> If source was directory copy test directory and all of its content into dest      
 * - Src:/home/test/ ,Dst:/home/ ,Result:/home/**-> if source was direcotry copy its content to dest
 * - Src:/home/test ,Dst:/home/test2 ,Result:/home/test2/** -> if source was directoy copy it and its content to dest with test2 as name
 * - Src:/home/test/ ,Dst:/home/test2 ,Result:->/home/test2/** if source was directoy copy it and its content to dest with test2 as name
 * @author Sina Salek (<a href="http://sina.salek.ws/node/1289" title="http://sina.salek.ws/node/1289">http://sina.salek.ws/node/1289</a>)
 * @todo
 *  - Should have rollback so it can undo the copy when it wasn't completely successful
 *  - It should be possible to turn off auto path creation feature f
 *  - Supporting callback func
 *  - May prevent some issues on shared enviroments : <a href="http://us3.php.net/umask" title="http://us3.php.net/umask">http://us3.php.net/umask</a>
 * @param $source //file or folder
 * @param $dest ///file or folder
 * @param $options //folderPermission,filePermission
 * @return boolean
 */
function smart_copy($source, $dest, $options=array('folderPermission'=>0755,'filePermission'=>0755))
{
    $result=false;
    
    //For Cross Platform Compatibility
    if (!isset($options['noTheFirstRun'])) {
        $source=str_replace('\\','/',$source);
        $dest=str_replace('\\','/',$dest);
        $options['noTheFirstRun']=true;
    }
    
    if (is_file($source)) {
        if ($dest[strlen($dest)-1]=='/') {
            if (!file_exists($dest)) {
                make_recursive_directories($dest,$options['folderPermission'],true);
            }
            $__dest=$dest."/".basename($source);
        } else {
            $__dest=$dest;
        }
        $path_parts = pathinfo($__dest);
        if($path_parts['extension']=='ini') {
            //echo "skip ini file $__dest<br>";
        } else {
            $result=copy($source, $__dest);
            chmod($__dest,$options['filePermission']);
        }
    } elseif(is_dir($source)) {
        if ($dest[strlen($dest)-1]=='/') {
            if ($source[strlen($source)-1]=='/') {
                //Copy only contents
            } else {
                //Change parent itself and its contents
                $dest=$dest.basename($source);
                if(!is_dir($dest)) { @mkdir($dest); }
                chmod($dest,$options['filePermission']);
            }
        } else {
            if ($source[strlen($source)-1]=='/') {
                //Copy parent directory with new name and all its content
                if(!is_dir($dest)) { @mkdir($dest,$options['folderPermission']); }
                chmod($dest,$options['filePermission']);
            } else {
                //Copy parent directory with new name and all its content
                if(!is_dir($dest)) { @mkdir($dest,$options['folderPermission']); }
                chmod($dest,$options['filePermission']);
            }
        }

        $dirHandle=opendir($source);
        while($file=readdir($dirHandle))
        {
            if($file!="." && $file!="..")
            {
                $__dest=$dest."/".$file;
                $__source=$source."/".$file;

                $pos = strpos($__source,"_cache");
                $resto = substr($__source,$pos+6);
                if(preg_match("/_cache/",$resto)) {
                   // echo "skip $__source<br>";
                } else {
                    if ($__source!=$dest) {
                        $result=smart_copy($__source, $__dest, $options);
                    }
                }
            }
        }
        closedir($dirHandle);
        
    } else {
        $result=false;
    }
    return $result;
}

/** 
 * Converts a simpleXML element into an array. Preserves attributes.<br/> 
 * You can choose to get your elements either flattened, or stored in a custom 
 * index that you define.<br/> 
 * For example, for a given element 
 * <code> 
 * <field name="someName" type="someType"/> 
 * </code> 
 * <br> 
 * if you choose to flatten attributes, you would get: 
 * <code> 
 * $array['field']['name'] = 'someName'; 
 * $array['field']['type'] = 'someType'; 
 * </code> 
 * If you choose not to flatten, you get: 
 * <code> 
 * $array['field']['@attributes']['name'] = 'someName'; 
 * </code> 
 * <br>__________________________________________________________<br> 
 * Repeating fields are stored in indexed arrays. so for a markup such as: 
 * <code> 
 * <parent> 
 *     <child>a</child> 
 *     <child>b</child> 
 *     <child>c</child> 
 * ... 
 * </code> 
 * you array would be: 
 * <code> 
 * $array['parent']['child'][0] = 'a'; 
 * $array['parent']['child'][1] = 'b'; 
 * ...And so on. 
 * </code> 
 * @param simpleXMLElement    $xml            the XML to convert 
 * @param boolean|string    $attributesKey    if you pass TRUE, all values will be 
 *                                            stored under an '@attributes' index. 
 *                                            Note that you can also pass a string 
 *                                            to change the default index.<br/> 
 *                                            defaults to null. 
 * @param boolean|string    $childrenKey    if you pass TRUE, all values will be 
 *                                            stored under an '@children' index. 
 *                                            Note that you can also pass a string 
 *                                            to change the default index.<br/> 
 *                                            defaults to null. 
 * @param boolean|string    $valueKey        if you pass TRUE, all values will be 
 *                                            stored under an '@values' index. Note 
 *                                            that you can also pass a string to 
 *                                            change the default index.<br/> 
 *                                            defaults to null. 
 * @return array the resulting array. 
 */ 
function simple_xml_to_array(SimpleXMLElement $xml,$attributesKey=null,$childrenKey=null,$valueKey=null){ 

    if($childrenKey && !is_string($childrenKey)){$childrenKey = '@children';} 
    if($attributesKey && !is_string($attributesKey)){$attributesKey = '@attributes';} 
    if($valueKey && !is_string($valueKey)){$valueKey = '@values';} 

    $return = array(); 
    $name = $xml->getName(); 
    $_value = trim((string)$xml); 
    if(!strlen($_value)){$_value = null;}; 

    if($_value!==null){ 
        if($valueKey){$return[$valueKey] = $_value;} 
        else{$return = $_value;} 
    } 

    $children = array(); 
    $first = true; 
    foreach($xml->children() as $elementName => $child){ 
        $value = simple_xml_to_array($child,$attributesKey, $childrenKey,$valueKey); 
        if(isset($children[$elementName])){ 
            if(is_array($children[$elementName])){ 
                if($first){ 
                    $temp = $children[$elementName]; 
                    unset($children[$elementName]); 
                    $children[$elementName][] = $temp; 
                    $first=false; 
                } 
                $children[$elementName][] = $value; 
            }else{ 
                $children[$elementName] = array($children[$elementName],$value); 
            } 
        } 
        else{ 
            $children[$elementName] = $value; 
        } 
    } 
    if($children){ 
        if($childrenKey){$return[$childrenKey] = $children;} 
        else{$return = array_merge($return,$children);} 
    } 

    $attributes = array(); 
    foreach($xml->attributes() as $name=>$value){ 
        $attributes[$name] = trim($value); 
    } 
    if($attributes){ 
        if($attributesKey){$return[$attributesKey] = $attributes;} 
        else{$return = array_merge($return, $attributes);} 
    } 

    return $return; 
}

function mysql_backup($database) {

    global $db;

    $directory = dirname(__FILE__);
    $tempdump  = $directory.'/_cache/'.uniqid('dump');
    $fp=fopen($tempdump,"w");

    $tables = @mysql_list_tables($database);
    while ($row = @mysql_fetch_row($tables)) { $table_list[] = $row[0]; }

    fputs($fp, 'USE '.$database.";\n\n");

    for ($i = 0; $i < @count($table_list); $i++) {

        if(substr($table_list[$i],0,4)!="fop2" && $table_list[$i]!='visual_phonebook') { 
            continue; 
        }
        $results = mysql_query('SELECT * FROM ' . $database . '.' . $table_list[$i]);

        while ($row = @mysql_fetch_assoc($results)) {

            fputs($fp, 'INSERT IGNORE INTO `' . $table_list[$i] .'` (');

                    $data = Array();

                    while (list($key, $value) = @each($row)) { $data['keys'][] = $key; $data['values'][] = addslashes($value); }

                    fputs($fp, '`'.join($data['keys'], '`,`') . '`)' . "\n" . 'VALUES (\'' . join($data['values'], '\', \'') . '\');' . "\n");

        }

        fputs($fp, str_repeat("\n", 2));

    }
    fclose($fp); 

    if (file_exists($tempdump)) {

        header("Pragma: public"); // required
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false); // required for certain browsers 
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"fop2database.dump\";" );
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".filesize($tempdump));
        readfile("$tempdump");
        exit();

    } 
}
