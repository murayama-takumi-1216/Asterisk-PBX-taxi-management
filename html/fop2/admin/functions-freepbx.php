<?php

/*
Check FreePBX backend to see what core modules are installed
and sets a global variable active_modules with them
*/
function set_freepbx_active_modules() {
    global $conf,$db,$active_modules,$fpbx_version;
    if(!$db->is_connected()) {
        $active_modules = array();
        return;
    }
    $database = $conf['DBNAME'];
    $query="SELECT modulename FROM $database.modules WHERE enabled=1";
    $res = $db->consulta($query);
    while($row=$db->fetch_assoc($res)) {
        if($row['modulename']=='core') { $row['modulename']="extensions"; }
        $active_modules[$row['modulename']]=1;
    }
    $active_modules['trunks']=1;
    $fpbx_version = preg_replace('/[^0-9]/','',freepbx_get_version());
}

/*
Checks for all available modules and run a check_extension_freepbx function
to retrieve a hash with all extensions and data for a complete set of buttons
so we can compare with the fop2buttons table and remove what was deleted and
insert what is new.
*/
function freepbx_check_extension_usage($exten=true, $module_hash=false) {

    global $active_modules, $context_for_device, $default_custom_context, $db;

    $context_for_device=array();
    if(is_readable("/usr/local/fop2/tenants.cfg")) {

        $res = $db->consulta("SELECT * FROM fop2contexts ORDER BY id");
        while($row=$db->fetch_assoc($res)) {
           $ctxid[$row['context']]=$row['id'];
           if(!isset($default_custom_context)) $default_custom_context=$row['id'];
        }

        $custom_contexts = parse_ini_file("/usr/local/fop2/tenants.cfg",true);
        foreach($custom_contexts as $ctx=>$midata) {
            $chans = preg_split("/,/",$midata['buttons']);
            foreach($chans as $chan) {
                $context_for_device[$chan]=$ctxid[$ctx];
            }
        }
    } else {
        $default_custom_context = 0;
    }

    $exten_usage   = array();
    $exten_matches = array();

    if (!is_array($module_hash)) {
        $module_hash = $active_modules;
    }

    if (!is_array($exten) && $exten !== true) {
        $exten = array($exten);
    }

    foreach(array_keys($module_hash) as $mod) {
        $function = $mod."_check_extensions_freepbx";
        if (function_exists($function)) {
            $module_usage = $function($exten);
            if (!empty($module_usage)) {
                $exten_usage = array_merge($module_usage, $exten_usage);
            }
        } 
    }
    if ($exten === true) {
        return $exten_usage;
    } else {
        foreach ($exten_usage as $chan=>$data) {
            foreach ($exten as $idx=>$test_exten) {
                if ($data['exten']==$test_exten) {
                    $exten_matches[$chan] = $data;
                }
            }
        }
    }
    return $exten_matches;
}

/*
Returns list of FreePBX Trunks
*/
function trunks_check_extensions_freepbx() {
    global $conf,$db;
    $database = $conf['DBNAME'];
 
    $res = $db->consulta("DESC $database.`trunks`");
    if($res) {

        $sql = "SELECT `trunkid` , `tech` , `channelid` , `disabled` FROM $database.`trunks` ORDER BY `trunkid`";
        $ris = $trunks = $db->consulta($sql);
        $extenlist = array();

        while($trunk = $db->fetch_assoc($ris)) {
            $trunk_id = "OUT_".$trunk['trunkid'];
            $disabled = $trunk['disabled'];
            $tech = strtoupper($trunk['tech']);
            switch ($tech) {
                case 'IAX':
                    $dialstring = 'IAX2/'.$trunk['channelid'];
                    break;
                case 'CUSTOM':
                    $dialstring = $trunk['channelid'];
                break;
                default:
                    $dialstring = $tech.'/'.$trunk['channelid'];
                    break;
            }
            $thisexten = "OUT_".$trunk['trunkid'];
            $dialstring = preg_replace('/(.*)\/\$OUTNUM\$(.*)?/','$1',trim($dialstring));
            $data = array();
            $data['name']    = $dialstring;
            $data['channel'] = $dialstring;
            $data['type']    = "trunk";
            $data['exten']   = $thisexten;
            if(preg_match("/^DAHDI/i",$dialstring) || preg_match("/^ZAP/i",$dialstring)) {
                $data['email']   = '1-32';
            }
            $data['context'] = '';
            $panel_context = get_custom_context($data['channel']);
            $data['context_id'] = $panel_context; // FreePBX Does not support Contexts
            $extenlist[$dialstring]=$data;
        }

    } else {
        // FreePBX super viejo
        $sql = "SELECT t.variable, t.value, d.value state FROM `globals` t ";
        $sql.= "JOIN (SELECT x.variable, x.value FROM globals x WHERE x.variable ";
        $sql.= "LIKE 'OUTDISABLE\_%') d ON substring(t.variable,5) = substring(d.variable,12) ";
        $sql.= "WHERE t.variable LIKE 'OUT\_%' UNION ALL SELECT v.variable, v.value, concat(substring(v.value,1,0),'off') state ";
        $sql.= "FROM `globals` v WHERE v.variable LIKE 'OUT\_%' AND concat('OUTDISABLE_',substring(v.variable,5)) ";
        $sql.= "NOT IN ( SELECT variable from globals WHERE variable LIKE 'OUTDISABLE\_%' ) ORDER BY variable";

        $res = $db->consulta($sql);
        $extenlist = array();

        while($trunk = $db->fetch_assoc($res)) {
            $thisexten = $trunk['variable'];
            $dialstring = strtoupper($trunk['value']);
            $dialstring = preg_replace('/(.*)\/\$OUTNUM\$(.*)?/','$1',trim($dialstring));
            $data = array();
            $data['name']    = $dialstring;
            $data['channel'] = $dialstring;
            $data['type']    = "trunk";
            $data['exten']   = $thisexten;
            $data['context'] = '';
            $panel_context = get_custom_context($data['channel']);
            $data['context_id'] = $panel_context; // FreePBX Does not support Contexts
            $extenlist[$dialstring]=$data;
        }
    }

    return $extenlist;
}

function ringgroups_check_extensions_freepbx($exten=true) {
    global $conf, $db;
    $database = $conf['DBNAME'];
    $extenlist=array();
    $sql = "SELECT `grpnum`, `description` FROM ringgroups ORDER BY CAST(grpnum as UNSIGNED)";
    $res = $db->consulta($sql);
    while($result = $db->fetch_assoc($res)) { 
        $thisexten = $result['grpnum'];
        $data = array();
        $data['name'] = $result['description'];
        $data['channel'] = "RINGGROUP/$thisexten";
        $data['type']    = "ringgroup";
        $data['context'] = "from-internal";
        $data['exten']   = $thisexten;
        $data['context_id'] = 0; // FreePBX Does not support Contexts
        $extenlist[$data['channel']]  = $data;
    }
    return $extenlist;
}

function extensions_check_extensions_freepbx($exten=true) {

    global $conf, $db, $fpbx_version, $ZULU_TECH, $ZULU_COMBINE;
    $deviceFor = array();
    $database = $conf['DBNAME'];

    $queue_conf = freepbx_get_queues();

    $voicemail        = read_voicemail_conf("/etc/asterisk/voicemail.conf");
    $voicemail_pins   = isset($voicemail['pin'])?$voicemail['pin']:'';
    $voicemail_emails = isset($voicemail['email'])?$voicemail['email']:'';

    if($fpbx_version <= 260) {
        $qcontext="from-internal";
    } else {
        $qcontext="from-queue";
    }

    // Find voicmeail prefix to direct voicemail dial
    $vmprefix='';
    $results = $db->select("value","$database.globals","","variable='VM_PREFIX'");
    if(is_array($results)) {
        $vmprefix=$results[0]['value'];
    }
    if($vmprefix=='') {
        $results = $db->select("IF(customcode='' or customcode is null,defaultcode,customcode) AS value","$database.featurecodes","","modulename='voicemail' AND featurename='directdialvoicemail'");
        if(is_array($results)) {
            $vmprefix=$results[0]['value'];
        }
    }

    $accountcode = array();
    $context     = array();
    $secret      = array();
    $tables      = array();
    $tables[]    = 'sip';
    $tables[]    = 'iax';
    $tables[]    = 'dahdi';

    foreach($tables as $accounttable) {
        $results = $db->select('id,keyword,data',"$database.$accounttable","","(keyword='accountcode' OR keyword='context' OR keyword='secret') AND data<>'' AND data IS NOT NULL");
        if(is_array($results)) {
            foreach ($results as $result) {
                 if($result['keyword']=='accountcode') {
                     $accountcode[$result['id']]=$result['data'];
                 } else if($result['keyword']=='context') {
                     $context[$result['id']]=$result['data'];
                 } else if($result['keyword']=='secret') {
                     if($accounttable=='sip') {
                         $secret[$result['id']]=$result['data'];
                     }
                 }
            }
        }
    }

    if (isset($conf['EXTENSIONS']) && $conf['EXTENSIONS'] == "deviceanduser") {
        $fields = "extension,concat('USER/',extension) AS dial, if(voicemail='novm','',concat(extension,'@',voicemail)) AS mailbox,";
        $fields.= "name,IF(voicemail<>'novm',concat('$vmprefix',extension,'\@ext-local'),'') AS extenvoicemail";
    } else {
        $fields  = "extension, name, IF(dial IS null,CONCAT('VIRTUAL/',extension),dial) AS dial, ";
        $fields .= "IF(voicemail='novm','',CONCAT(extension,'@',voicemail)) AS mailbox, IF(voicemail<>'novm',concat('$vmprefix',extension,'\@ext-local'),'') AS extenvoicemail ";
    }
    $join = "LEFT JOIN $database.devices ON users.extension=devices.id";

    $extenlist = array();
    if (is_array($exten) && empty($exten)) {
        return $extenlist;
    }

    $where='';
    if (is_array($exten)) {
        $where = "extension in ('".implode("','",$exten)."')";
    }

    $allres = array();
    $allres[] = $db->select($fields,"$database.users",$join,$where,"","","");

    // zulu softphones
    $fields_zulu = "device AS extension,concat('".$ZULU_TECH."/',device) AS dial, if(voicemail='novm','',concat(extension,'@',voicemail)) AS mailbox,";
    $fields_zulu.= "name,IF(voicemail<>'novm',concat('*',extension,'\@ext-local'),'') AS extenvoicemail";
    $join_zulu   = "INNER JOIN $database.zulu_softphones ON users.extension=zulu_softphones.user";

    if($db->table_exists('zulu_softphones') && $ZULU_COMBINE==false) {
        $allres[] = $db->select($fields_zulu,"$database.users",$join_zulu,$where,"","","");
    }

    // webrtc clients
    $fields_webrtc = "device AS extension,concat('PJSIP/',device) AS dial, if(voicemail='novm','',concat(extension,'@',voicemail)) AS mailbox,";
    $fields_webrtc.= "name,IF(voicemail<>'novm',concat('*',extension,'\@ext-local'),'') AS extenvoicemail, 'WEBRTC' as `group`";
    $join_webrtc   = "INNER JOIN $database.webrtc_clients ON users.extension=webrtc_clients.user";

    if($db->table_exists('webrtc_clients') && $ZULU_COMBINE==false) {
        $allres[] = $db->select($fields_webrtc,"$database.users",$join_webrtc,$where,"","","");
    }

    foreach($allres as $results) {
        if(is_array($results)) {
            foreach ($results as $result) {

                if(preg_match("/[,&]/",$result['dial'])) {
                    $partes = preg_split("/[,&]/",$result['dial']);
                    $result['dial']=$partes[0];
                }
                $account   = isset($accountcode[$result['extension']])?$accountcode[$result['extension']]:'';
                $pcontext  = isset($context[$result['extension']])?$context[$result['extension']]:'from-internal';
                $thisexten = $result['extension'];
                $qchannel  = freepbx_construct_queuechannel($thisexten,$result['dial'],$result['name'],$qcontext,$queue_conf);
                $vmpin     = isset($voicemail_pins[$result['mailbox']])?$voicemail_pins[$result['mailbox']]:$thisexten;
                $vmemail   = isset($voicemail_emails[$result['mailbox']])?$voicemail_emails[$result['mailbox']]:'';
                $data = array();
                $data['name'] = $result['name'];
                $data['channel'] = $result['dial'];
                if(isset($conf['ZULUENABLED'])) {
                    if($ZULU_COMBINE==true && $conf['ZULUENABLED']==true) {
                        $data['extrachannel'] = $ZULU_TECH.'/90'.$thisexten;
                    }
                }
                $data['mailbox'] = $result['mailbox'];
                $data['type']    = 'extension';
                $data['context'] = $pcontext;
                $data['exten']   = $thisexten;
                $data['vmpin']   = $vmpin;
                $data['email']   = $vmemail;
                $data['customastdb'] = 'CF/'.$thisexten;
                $data['queuechannel'] = $qchannel;
                $data['queuecontext'] = $qcontext;
                $data['accountcode']  = $account;
                $data['extenvoicemail'] = $result['extenvoicemail'];
                $data['sip_username'] = isset($secret[$result['extension']])?$result['extension']:'';
                $data['sip_password'] = isset($secret[$result['extension']])?$secret[$result['extension']]:'';
                if(isset($result['group'])) { $data['group'] = $result['group']; }

                $panel_context = get_custom_context($data['channel']);
                $data['context_id'] = $panel_context; // FreePBX Does not support Contexts
   
                if(substr($data['channel'],0,6)=="VIRTUA" || substr($data['channel'],0,6)=="CUSTOM" || substr($data['channel'],0,4)=="USER") {
                    $data['originatechannel']="Local/$thisexten@from-internal";
                }
    
                $extenlist[$data['channel']]  = $data;
   
                $deviceFor[$thisexten]=$result['dial'];

            }
        }
    }

    // Ahora que tenemos devices, nos fijamos restrictqueue

    $restrictstring="";
    foreach($queue_conf as $queue=>$data) {
        if(!isset($data['dynmemberonly'])) { $data['dynmemberonly']=''; }
        if($data['dynmemberonly']=='yes' || $data['dynmemberonly']=='1') {
            if(isset($data['dynmembers'])) {
                $datarray=array();
                foreach($data['dynmembers'] as $member) {
                    $pen_pos = strrpos($member, ",");
                    if ( $pen_pos !== false ) {
                        $thisexten = substr($member,0,$pen_pos); 
                    } else {
                        $thisexten = $member;
                    }
                    $thisexten = preg_replace("/[^0-9#*]/", "", $thisexten); 
                    if(isset($deviceFor[$thisexten])) { $datarray[]=$deviceFor[$thisexten]; }
                }
                $restrictstring .= "restrictqueue=QUEUE/$queue:".implode(",",$datarray)."\\n";
            }
        } 
    }
    if($restrictstring <> '') {
        $query = "REPLACE INTO fop2settings (keyword,value) VALUES ('restrictqueue','$restrictstring')";
        $db->consulta($query);
    } else {
        $query = "DELETE FROM fop2settings WHERE keyword='restrictqueue'";
        $db->consulta($query);
    }
    return $extenlist;
}

function conferences_check_extensions_freepbx($exten=true) {
    global $conf,$db;
    $database = $conf['DBNAME'];
    $extenlist = array();
    if (is_array($exten) && empty($exten)) {
        return $extenlist;
    }
    $where='';
    if (is_array($exten)) {
        $where.= "exten in ('".implode("','",$exten)."')";
    }
    $results = $db->select("exten,description","$database.meetme","",$where,"","","exten");

    if(is_array($results)) {
        foreach ($results as $result) {
            $thisexten = $result['exten'];
            $data = array();
            $data['name'] = $result['description'];
            $data['channel'] = "CONFERENCE/$thisexten";
            $data['context'] = "from-internal";
            $data['type']    = 'conference';
            $data['exten']   = $thisexten;
            $panel_context = get_custom_context($data['channel']);
            $data['context_id'] = $panel_context; // FreePBX Does not support Contexts
            $extenlist[$data['channel']]  = $data;
        }
    }
    return $extenlist;
}

function parking_check_extensions_freepbx($exten=true) {

    global $db, $conf;
    $database = $conf['DBNAME'];
    $extenlist = array();

    $resu = $db->consulta("SELECT table_name FROM information_schema.tables WHERE table_schema = '$database' AND table_name = 'parkinglot'");
    if($db->num_rows($resu) > 0) {
        $results = $db->select("data","$database.parkinglot","","keyword='parkext'");
        if(is_array($results)) {
            foreach ($results as $result) {
                $thisexten = $result['data'];
                $data = array();
                $data['name']   = "Default";
                $data['channel'] = "PARK/default";
                $data['type']    = 'park';
                $data['exten']   = $thisexten;
                $data['context'] = 'parkedcalls'; 
                $panel_context = get_custom_context($data['channel']);
                $data['context_id'] = $panel_context; // FreePBX Does not support Contexts
                $extenlist[$data['channel']]  = $data;
            }
        }
    } else {
        $name_field = "id";
        if(file_exists("/etc/issabel.conf")) { $name_field = "name"; }
        $results= $db->select("if(`defaultlot`='no',concat('PARK/parkinglot_',$name_field),'PARK/default') AS channel,parkext AS exten,id,name","$database.parkplus");
        if(is_array($results)){
            foreach($results as $result) {
                $thisexten = $result['exten'];
                $data = array();
                $data['name']   = ucfirst($result['name']);
                $data['channel'] = $result['channel'];
                $data['type']    = 'park';
                $data['exten']   = $thisexten;
                $panel_context = get_custom_context($data['channel']);
                $data['context_id'] = $panel_context; // FreePBX Does not support Contexts
                if($result['channel']=='PARK/default') {
                    $data['context'] = 'parkedcalls';
                } else {
                    $data['context'] = 'parkinglot_'.$result['id']; 
                }
                $extenlist[$result['channel']]  = $data;
            }
        }
    }
    return $extenlist;

}

function queues_check_extensions_freepbx($exten=true) {
    global $active_modules, $db;

    $extenlist = array();
    if (is_array($exten) && empty($exten)) {
        return $extenlist;
    }
 
    $context="from-internal";
   
    $results = $db->consulta("DESC queues_config");
    if(isset($results)) {
        # FreePBX 2.8 o superior
        $queues_table = "queues_config";
        $where='';
        $orderby = "extension";
        if (is_array($exten)) {
           $where = "extension in ('".implode("','",$exten)."')";
        }
    } else {
        # FreePBX Viejo 
        $queues_table = "extensions";
        $orderby = "extension";
        $where = "application='Queue' ";
        if (is_array($exten)) {
            $where .= "AND extension in ('".implode("','",$exten)."')";
        }
    }
    
    $results = $db->select("extension,descr",$queues_table,"",$where,"","",$orderby);

    if(is_array($results)) {
        foreach ($results as $result) {
            $thisexten = $result['extension'];
            $data = array();
            $data['name'] = $result['descr'];
            $data['channel'] = "QUEUE/$thisexten";
            $data['context'] = $context;
            $data['type'] = 'queue';
            $data['exten']   = $thisexten;
            $panel_context = get_custom_context($data['channel']);
            $data['context_id'] = $panel_context; // FreePBX Does not support Contexts
            $extenlist[$data['channel']]  = $data;
        }
    }

    return $extenlist;
}

function freepbx_get_version() {
    global $conf,$db;
    $database = $conf['DBNAME'];
    $result = $db->select("value","$database.admin","","variable='version'");
    if(is_array($result)) {
        return $result[0]['value'];
    }
}

function freepbx_get_queues() {

    global $db,$astman,$conf,$fpbx_version;
    $return = array();
    $database = $conf['DBNAME'];

    if($fpbx_version <= 260) {
        $context="from-internal";
    } else {
        $context="from-queue";
    }

    if(isset($astman)) {
        if(!$res = $astman->connect($conf["MGRHOST"].":".$conf["MGRPORT"], $conf["MGRUSER"] , $conf["MGRPASS"], 'off')) {
            unset($astman);
        }
    }

    $results = $db->select("id,keyword,data","$database.queues_details","","keyword in ('strategy','member')");

    if ($results === false) { return $return; }

    foreach($results as $result) {
        if(!isset($return[$result['id']])) {
            $return[$result['id']]=array();
        }
        if($result['keyword']=='member') { 
            $return[$result['id']]['members'][]=$result['data']; 
        }
    }

    if (isset($astman)) {
        foreach($return as $queue=>$data) {
            $dynmem = Array();
            $mem    = Array();
            $get=$astman->database_show('QPENALTY/'.$queue.'/agents');
            if ($get) {
                foreach($get as $key => $value){
                    $key=explode('/',$key);
                    $mem[$key[4]]=$value;
                }
                foreach($mem as $mem => $pty){
                    $dynmem[]=freepbx_construct_member($mem,$pty,$context);
                }
                $return[$queue]['dynmembers']=$dynmem;

            } else {
               $return[$queue]['dynmembers']=array();
            }

            $return[$queue]['dynmemberonly'] = $astman->database_get('QPENALTY/'.$queue,'dynmemberonly');
        }
    }



    return $return; 
}

function freepbx_construct_member($mem,$pty,$context) {

    $return       = '';
    $exten_prefix = strtoupper(substr($mem,0,1));
    $this_member  = preg_replace("/[^0-9#\,*]/", "", $mem);

    switch ($exten_prefix) {
        case 'A':
          $exten_type = 'Agent';
          break;
        case 'S':
          $exten_type = 'SIP';
          break;
        case 'P':
          $exten_type = 'PJSIP';
          break;
        case 'X':
          $exten_type = 'IAX2';
          break;
        case 'Z':
          $exten_type = 'ZAP';
          break;
        case 'D':
          $exten_type = 'DAHDI';
          break;
        default;
          $exten_type = 'Local';
    }

    // remove blanks // prefix with the channel
    if (!empty($this_member)) {
        switch($exten_type) {
            case 'Agent':
            case 'SIP':
            case 'PJSIP':
            case 'IAX2':
            case 'ZAP':
            case 'DAHDI':
                $return = "$exten_type/$this_member,$pty";
                break;
            case 'Local':
                $return = "$exten_type/$this_member@$context/n,$pty";
                break;
        }
    }
    return $return;
}

function freepbx_construct_queuechannel($extension,$device,$name,$qcontext,$queue_conf) {

    // queuechannel=
    //    Local/602@from-queue/n|Penalty=0|MemberName=Nicolas Gudino 1|StateInterface=SIP/602
    //   &Local/602@from-queue/n|Penalty=0|MemberName=Nicolas Gudino 1|StateInterface=SIP/602|Queue=100
    //   &Local/602@from-queue/n|Penalty=0|MemberName=Nicolas Gudino 1|StateInterface=SIP/602|Queue=101

    $member = array();

    foreach($queue_conf as $queue=>$data) {

        $allmembers = array();
        if(isset($data['dynmembers'])) {
            $allmembers = array_merge($data['dynmembers'],$allmembers);
        }
        if(isset($data['members'])) {
            $allmembers = array_merge($data['members'],$allmembers);
        }

        foreach($allmembers as $idx=>$mem) {
            $pen_pos = strrpos($mem, ",");
            if ( $pen_pos !== false ) {
                $thisexten = substr($mem,0,$pen_pos); 
            } else {
                $thisexten = $mem; 
            }
            $thisexten = preg_replace("/[^0-9#*]/", "", $thisexten); 

            if($extension==$thisexten) {
                $member[$queue][$thisexten]=$mem."|".$device;
            }
        }

    }
    if(substr($device,0,6)=="VIRTUA" || substr($device,0,6)=="CUSTOM" || substr($device,0,4)=="USER") {
        $stint = "|StateInterface=hint:".$extension."@ext-local";
    } else {
        $stint = "|StateInterface=$device";
    }
    $def[] = "Local/$extension@$qcontext/n|Penalty=0|MemberName=$name".$stint;
    foreach($member as $queue=>$datos) {
        foreach($datos as $exten=>$mem) {
            $partos = preg_split("/\|/",$mem);
            $partes = preg_split("/,/",$partos[0]);
            $dev    = $partes[0];
            $pen    = (isset($partes[1]))?$partes[1]:0;
            $def[] = "$dev|Penalty=$pen|MemberName=$name".$stint."|Queue=$queue";
        }
    }
    $return = implode("&",$def);
    return $return;
}

function freepbx_populate_contexts_from_tenants() {
    return issabel_populate_contexts_from_tenants();
}

function issabel_populate_contexts_from_tenants() {
    // Simple function to populate the fop2context table with data from the tenants table
    // Returns an array with the contexts = ids
    global $db, $config_engine, $config, $current_custom_context;
    $return = array();

    if(is_readable("/usr/local/fop2/tenants.cfg")) {
        $current_fop2_contexts = array();
        $results = $db->select("id,context,name,exclude","fop2contexts","","","");
        $return = array();
        if(is_array($results)) {
            foreach ($results as $result) {
                if($result['exclude']==0) {
                    $return[$result['id']] = $result['context'];
                }
                $current_fop2_contexts[]=$result['context'];
            }
        }

        $current_custom_contexts = array();
        $contexts_in_ini = parse_ini_file("/usr/local/fop2/tenants.cfg",true);
        $current_custom_contexts = array_keys($contexts_in_ini);
        $idx=0;
        foreach ($current_custom_contexts as $val) {
            $idx++;
            $query = "INSERT INTO fop2contexts (context,name) VALUES ('%s','%s') ON DUPLICATE KEY UPDATE name='%s'";
            $res = $db->consulta($query,array($val,$val,$val));
        }
        $contexts_to_delete = array_diff($current_fop2_contexts,$current_custom_contexts);

        if(count($contexts_to_delete)>0) {
            $context_delete = implode("','",$contexts_to_delete);
            $query = "DELETE FROM fop2contexts WHERE context IN ('$context_delete')";
            $db->consulta($query);
        }

        $res = $db->consulta("SELECT id,context FROM fop2contexts WHERE exclude=0");
        $return = array();
        while($row=$db->fetch_assoc($res)) {
            $return[$row['id']]=$row['context'];
        }
        //$return = $current_custom_contexts;

    } else {
        $db->consulta("TRUNCATE TABLE fop2contexts");
        $res = $db->consulta("UPDATE IGNORE fop2buttons SET context_id=0");
        $res = $db->consulta("DELETE FROM fop2buttons WHERE context_id<>0");
        $res = $db->consulta("DELETE FROM fop2users WHERE context_id>0");
        $res = $db->consulta("DELETE FROM fop2GroupButton WHERE context_id>0");
        $res = $db->consulta("DELETE FROM fop2UserTemplate WHERE context_id>0");
        $res = $db->consulta("DELETE FROM fop2UserPlugin WHERE context_id>0");
        $res = $db->consulta("DELETE FROM fop2UserGroup WHERE context_id>0");
        $res = $db->consulta("DELETE FROM fop2PermGroup WHERE context_id>0");
        $return = array(0=>'GENERAL');
    }
    return $return;
}

function get_custom_context($channel) {
    global $context_for_device, $custom_contexts, $default_custom_context;
    if(isset($context_for_device[$channel])) {
        return $context_for_device[$channel];
    } else {
        return $default_custom_context;
    }
}
