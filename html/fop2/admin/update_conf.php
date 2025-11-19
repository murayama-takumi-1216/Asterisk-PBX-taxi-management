<?php

// Die if we are called via web, we only want to be run from command line
if (php_sapi_name() !='cli') exit;

$sDEBUG=0;
if (isset($argv[1])) {
   $sDEBUG=1;
} 

require_once("config.php");
require_once("functions.php");
require_once("system.php");
require_once("dblib.php");
require_once("asmanager.php");
require_once("dbconn.php");
require_once("secure/secure-functions.php");
require_once("dbsetup.php");

if(!$res = $astman->connect($conf["MGRHOST"].":".$conf["MGRPORT"], $conf["MGRUSER"] , $conf["MGRPASS"], 'off')) {
    unset($astman);     
}                       

// This script will update all the fop2 tables with information about the system
// This is needed to be run whenever there is a change in the PBX configuration
// or when asterisk is reloaded. So, the same fop2 could run this script on
// RELOAD!!

$panelcontexts = fop2_populate_contexts();

foreach($panelcontexts as $panelcontext=>$contextname) {

    if($sDEBUG==1) { echo "Updating buttons for context $contextname\n"; }

    $system_buttons = system_all_buttons();
    $fop2_buttons   = fop2_all_buttons();

    if(!isset($fop2_buttons))   { $fop2_buttons = array(); }
    if(!isset($system_buttons)) { $system_buttons = array(); }

    $system_buttons_context = array();
    foreach($system_buttons as $key=>$arradata) {
        if($arradata['context_id']==$panelcontext) {
            $system_buttons_context[$key]=1;
        }
    }

    $del_buttons = array_diff(array_keys($fop2_buttons),array_keys($system_buttons_context));
    $add_buttons = array_diff(array_keys($system_buttons_context),array_keys($fop2_buttons));

    if(!isset($del_buttons)) { $del_buttons = array(); }
    if(!isset($add_buttons)) { $add_buttons = array(); }

    // Remove buttons that are not in the system anymore
    foreach($del_buttons as $chan) {
        $result = $db->select('id,exten','fop2buttons','',"device='$chan'");
        $id     = $result[0]['id'];
        $exten  = $result[0]['exten'];

        if($sDEBUG==1) { echo "Removing button $chan\n"; }

        fop2_del_button($id,$exten);
    }

    foreach($add_buttons as $chan=>$dat) {

        $det = $system_buttons[$dat];
        $queuechannel     = (isset($det['queuechannel']))?$det['queuechannel']:'';
        $customastdb      = (isset($det['customastdb']))?$det['customastdb']:'';
        $mailbox          = (isset($det['mailbox']))?$det['mailbox']:'';
        $accountcode      = (isset($det['accountcode']))?$det['accountcode']:'';
        $email            = (isset($det['email']))?$det['email']:'';
        $group            = (isset($det['group']))?$det['group']:'';
        $external         = (isset($det['external']))?$det['external']:'';
        $queuecontext     = (isset($det['queuecontext']))?$det['queuecontext']:'';
        $extenvoicemail   = (isset($det['extenvoicemail']))?$det['extenvoicemail']:'';
        $context_id       = (isset($det['context_id']))?$det['context_id']:'';
        $originatechannel = (isset($det['originatechannel']))?$det['originatechannel']:'';
        $extrachan        = (isset($det['extrachannel']))?$det['extrachannel']:'';
        $srv              = (isset($det['server']))?$det['server']:'';
        $sip_user         = (isset($det['sip_username']))?$det['sip_username']:'';
        $sip_pass         = (isset($det['sip_password']))?$det['sip_password']:'';

//        $det['name']=utf8_decode($det['name']);

        if($sDEBUG==1) {
            if(is_array($contextname)) {
                $cname = $contextname['name'];
            } else {
                $cname = $contextname;
            }
            echo "Adding button ".$det['channel']." into context $cname\n";
        }

        $results = $db->consulta(
                "REPLACE INTO fop2buttons (type, device, label, exten, context, mailbox, queuechannel, customastdb, email, `group`, external, queuecontext, extenvoicemail, originatechannel, context_id, channel, accountcode, server, sip_username, sip_password ) ".
                "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s','%s','%s', '%s', '%s', '%s', '%s', '%s')",
                array($det['type'],$det['channel'],$det['name'],$det['exten'],$det['context'],$mailbox,$queuechannel,$customastdb,$email,$group,$external,$queuecontext,$extenvoicemail,$originatechannel,$context_id,$extrachan,$accountcode,$srv, $sip_user, $sip_pass)
                );
    }

    $fop2_buttons   = fop2_all_buttons();


    if(isset($argv[2])) {

        if($sDEBUG==1) { echo "Inserting users for context $contextname\n"; }

        fop2_insert_users();
    }

    if($panelcontext==0) {
        $wherepanelcontext = '';
    } else {
        $wherepanelcontext = " AND context_id='$panelcontext' ";
    }

    fop2_recreate_default_groups($predefined_groups,$panelcontext,$wherepanelcontext,$fop2_buttons);


    // If we are on FreePBX, then update queuechannel and mailbox automagically
    foreach($system_buttons as $chan=>$dat) {

        if (extension_loaded('mbstring')) {
            $current_encoding = mb_detect_encoding($dat['name'], 'auto');
            if($current_encoding<>'UTF-8') {
                $dat['name']=utf8_decode($dat['name']);
            }
        }

        if($dat['type']=='extension') {

            if($config_engine=='freepbx' || $config_engine=='mirtapbx' || $config_engine=='issabel') {

                if(isset($fop2_buttons[$chan]['queuechannel']) && isset($dat['queuechannel'])) {
                    $fop2qchan = $fop2_buttons[$chan]['queuechannel'];
                    if($fop2qchan <> $dat['queuechannel']) {
                        $query = "UPDATE fop2buttons SET queuechannel='%s' WHERE device='%s'";
                        $db->consulta($query,array($dat['queuechannel'],$chan));
                        $need_update=1;
                    }
                }

                if(isset($fop2_buttons[$chan]['mailbox']) && isset($dat['mailbox'])) {
                    $fop2mbox  = $fop2_buttons[$chan]['mailbox'];
                    if($fop2mbox <> $dat['mailbox']) {
                        $query = "UPDATE fop2buttons SET mailbox='%s' WHERE device='%s'";
                        $db->consulta($query,array($dat['mailbox'],$chan));
                        $need_update=1;
                    }
                }

                if(isset($fop2_buttons[$chan]['queuecontext']) && isset($dat['queuecontext'])) {
                    $fop2qctx  = $fop2_buttons[$chan]['queuecontext'];
                    if($fop2qctx <> $dat['queuecontext']) {
                        $query = "UPDATE fop2buttons SET queuecontext='%s' WHERE device='%s'";
                        $db->consulta($query,array($dat['queuecontext'],$chan));
                        $need_update=1;
                    }
                }

                if(isset($fop2_buttons[$chan]['extenvoicemail']) && isset($dat['extenvoicemail'])) {
                    $fop2extv  = $fop2_buttons[$chan]['extenvoicemail'];
                    if($fop2extv <> $dat['extenvoicemail']) {
                        $query = "UPDATE fop2buttons SET extenvoicemail='%s' WHERE device='%s'";
                        $db->consulta($query,array($dat['extenvoicemail'],$chan));
                        $need_update=1;
                    }
                }

                if(isset($fop2_buttons[$chan]['accountcode']) && isset($dat['accountcode'])) {
                    $fop2extv  = $fop2_buttons[$chan]['accountcode'];
                    if($dat['accountcode']<>'') {
                        $query = "UPDATE fop2buttons SET accountcode='%s' WHERE device='%s'";
                        $db->consulta($query,array($dat['accountcode'],$chan));
                        $need_update=1;
                    }
                }

                if(isset($fop2_buttons[$chan]['context']) && isset($dat['context'])) {
                    $fop2extv  = $fop2_buttons[$chan]['context'];
                    if($dat['context']<>'') {
                        $query = "UPDATE fop2buttons SET context='%s' WHERE device='%s'";
                        $db->consulta($query,array($dat['context'],$chan));
                        $need_update=1;
                    }
                }

                if(isset($fop2_buttons[$chan]['email']) && isset($dat['email'])) {
                    $fop2extv  = $fop2_buttons[$chan]['email'];
                    if($dat['email']<>'') {
                        $query = "UPDATE fop2buttons SET email='%s' WHERE device='%s'";
                        $db->consulta($query,array($dat['email'],$chan));
                        $need_update=1;
                    }
                }

            }
            // Sync names for extensions
            if(isset($fop2_buttons[$chan]['name']) && isset($dat['name'])) {
                $fop2name  = $fop2_buttons[$chan]['name'];
                if($dat['name']<>$fop2name) {
                    $query = "UPDATE fop2buttons SET label='%s' WHERE device='%s'";
                    $db->consulta($query,array($dat['name'],$chan));
                    if($astman) {
                        $res = $astman->UserEvent('FOP2CHANGEBUTTONLABEL',array('FOP2Channel'=>$chan,'Label'=>$dat['name'],'Context'=>$panelcontext)); //XXX panelcontext or contextanme?
                    }
                }
            }
            if(isset($fop2_buttons[$chan]['sip_password']) && isset($dat['sip_password'])) {
                $sipsecret  = $fop2_buttons[$chan]['sip_password'];
                $query = "UPDATE fop2buttons SET sip_username='%s',sip_password='%s' WHERE device='%s'";
                $db->consulta($query,array($dat['sip_username'],$dat['sip_password'],$chan));
            }
        } else if($dat['type']=='queue' || $dat['type']=='conference' || $dat['type']=="ringgroup") {

            if(($config_engine=='freepbx' || $config_engine=='issabel') && $dat['context_id']==$panelcontext) {
                $fop2name  = $fop2_buttons[$chan]['name'];
                if($dat['name']<>$fop2name) {
                    $query = "UPDATE fop2buttons SET label='%s' WHERE device='%s'";
                    $db->consulta($query,array($dat['name'],$chan));
                    if($astman) {
                        $res = $astman->UserEvent('FOP2CHANGEBUTTONLABEL',array('FOP2Channel'=>$chan,'Label'=>$dat['name'],'Context'=>$panelcontext)); //XXX panelcontext or contextname?
                    }
                }
            }
        }
    }
}

plugin_insert_missing_db();

// Execute php update script for plugins if they exists
$dir = dirname(__FILE__);
$pepe = get_installed_plugins();
foreach($pepe as $plugname=>$val) {
    $updatescript = $dir."/plugins/$plugname/update_conf.php";
    if(is_file($updatescript)) {
        system("php -f $updatescript");
    }
}
