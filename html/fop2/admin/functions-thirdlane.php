<?php

function thirdlaneold_populate_contexts_from_tenants() {
    // This will read tenants from the voicemail.conf file
    global $db;
    $lines = file("/etc/asterisk/voicemail.conf");
    foreach($lines as $line) {
        if(preg_match("/^\[default/",$line)) {
            $contexto = substr($line,9,-2);
            if($contexto<>'') {
                $query = "INSERT INTO fop2contexts (context,name) VALUES ('$contexto','$contexto') ON DUPLICATE KEY UPDATE  context='$contexto'";
                $db->consulta($query);
            }
        }
    }
    $results = $db->select("context,id,name","fop2contexts","","","");
    $return = array();
    if(is_array($results)) {
        foreach ($results as $result) {
            $return[$result['id']] = $result['context'];
        }
    }
    return $return;
}

function thirdlane_populate_contexts_from_tenants() {
    // Simple function to populate the fop2context table with data from the tenants table
    // Returns an array with the contexts = ids
    global $db, $config_engine;

    if($config_engine=='thirdlane_old') {
        $return = thirdlaneold_populate_contexts_from_tenants();
        return $return;
    }

    $results = $db->select('id,tenant','tenants');
    if(count($results)>1) {
        if(is_array($results)) {
            foreach ($results as $result) {
                $query = "INSERT INTO fop2contexts (id,context,name) VALUES ('%s','%s','%s') ON DUPLICATE KEY UPDATE name='%s',context='%s'";
                $db->consulta($query,array($result['id'],$result['tenant'],$result['tenant'],$result['tenant'],$result['tenant']));
            }
        }

        $results = $db->select("context,name","fop2contexts","","","");
        $return = array();
        if(is_array($results)) {
            foreach ($results as $result) {
                $return[$result['name']] = $result['context'];
            }
        }
    } else {
        $return[0]='GENERAL';
    }

    return $return;
}

function thirdlaneold_check_extension_usage() {
    global $db, $astman, $conf, $panelcontext;
    $extenlist = array();

    $panelcontexts = thirdlane_populate_contexts_from_tenants();

    $r_panelcontexts = array_flip($panelcontexts);
    // Extensions

    if(!is_readable("/etc/asterisk/users.txt")) { die('Cannot read /etc/asterisk/users.txt file'); }

    $voicemail = read_voicemail_conf("/etc/asterisk/voicemail.conf");
    $voicemail_pins = $voicemail['pin'];


    $lines = file("/etc/asterisk/users.txt");
    $exten='';
    $extenlist = array();
    foreach($lines as $line) {
        $line=trim($line);
        //echo $line."<br>";
        if(preg_match("/^\[/",$line)) {
            if(isset($data['channel'])) {
                $mbox = $data['mailbox'];
                $mbox2 = $mbox;
                if(preg_match("/default-/",$mbox)) {
                    $partes = preg_split("/-/",$mbox,2);
                    $panelctx = $partes[1];
                    $panelctx = substr($panelctx,0,-8);
                    $mbox2 = $partes[0]."-$panelctx";
                }
                $lastname = isset($data['last_name'])?$data['last_name']:'';
                $firstname = isset($data['first_name'])?$data['first_name']:'';
                $data['name']=$lastname." ".$firstname;
                $data['type']='extension';
                if($mbox2<>$mbox) {
                    $data['context_id']= $r_panelcontexts[$panelctx];
                    $data['context']='from-inside-'.$panelctx;
                } else {
                    $data['context_id']=0;
                    $data['context']='from-inside';
                }
                $data['vmpin'] = isset($voicemail_pins[$mbox2])?$voicemail_pins[$mbox2]:$data['exten'];
                $extenlist[$data['channel']] = $data;
            }
            $exten = substr($line,1,-1);
            $data = array();
        } else {
            if($exten<>'') { 
                $partes = preg_split("/=/",$line);
                $param = $partes[0];
                $value = isset($partes[1])?trim($partes[1]):'';
                if($param=='first_name') {
                    $data['first_name'] = $value;
                } else
                if($param=='last_name') {
                    $data['last_name'] = $value;
                } else
                if($param=='email') {
                    $data['email'] = $value;
                } else
                if($param=='phones') {
                    $data['channel'] = $value;
                } else
                if($param=='mailbox') {
                    $data['mailbox'] = $value."@default";
                } else
                if($param=='department') {
                    $data['group'] = $value;
                } else
                if($param=='ext') {
                    $data['exten'] = $value;
                }
            }
        }
    }
    // last item
    $data['type']='extension';
    $data['context']='from-inside';
    $lastname = isset($data['last_name'])?$data['last_name']:'';
    $firstname = isset($data['first_name'])?$data['first_name']:'';
    $data['name']=$lastname." ".$firstname;
    if($mbox2<>$mbox) {
        $data['context_id']= $r_panelcontexts[$panelctx];
        $data['context']='from-inside-'.$panelctx;
    } else {
        $data['context_id']=0;
        $data['context']='from-inside';
    }
 
    $data['vmpin'] = isset($voicemail_pins[$mbox2])?$voicemail_pins[$mbox2]:$data['exten'];
    $extenlist[$data['channel']] = $data;

    // Queues
    if(is_readable("/etc/asterisk/queues.conf")) { 
        $lines = file("/etc/asterisk/queues.conf");
        $data = array();
        $cont=0;
        foreach($lines as $line) {
            $line=trim($line);
            if(preg_match("/^\[/",$line)) {
                $cont++;
                $queue = substr($line,1,-1);
                $data['channel'] = "QUEUE/$queue";
                if(preg_match("/-/",$queue)) {
                    $partes = preg_split("/-/",$queue,2);
                    $panelctx = $partes[1];
                    $data['context_id']= $r_panelcontexts[$panelctx];
                } else {
                    $data['context_id']= 0;
                }
                $data['type']    = "queue";
                $data['context'] = "default";
                $data['name']    = $queue;
                $data['exten']   = $cont;
                $extenlist[$data['channel']] = $data;
            }
        }
    }

    // Conferences 
    if(is_readable("/etc/asterisk/meetme.txt")) {
        $lines = file("/etc/asterisk/meetme.txt");
        $data = array();
        $cont=0;
        foreach($lines as $line) {
            $line=trim($line);

            if(preg_match("/^\[/",$line)) {
                if(isset($data['channel'])) {
                    $data['type']    = 'conference';
                    $data['context'] = 'from-inside';
                    $extenlist[$data['channel']] = $data;
                }
                $exten = substr($line,1,-1);
                $data = array();
            } else {
                if($exten<>'') { 
                    $partes = preg_split("/=/",$line);
                    $param = $partes[0];
                    $value = isset($partes[1])?$partes[1]:'';
                    if($param=='description') {
                        $data['name'] = $value;
                    } else
                    if($param=='conference') {
                        $data['exten'] = $value;
                        $data['context_id'] = 0;
                        $data['channel'] = "CONFERENCE/".$value;
                    }
                }
            }
        }
        // last item
        $data['type']    = 'conference';
        $data['context'] = 'from-inside';
        $data['context_id'] = 0;
        $extenlist[$data['channel']] = $data;
    }

    // Trunks
    if(is_readable("/etc/asterisk/trunks.include")) {
        $cont = 0;
        $lines = file("/etc/asterisk/trunks.include");
        foreach($lines as $line) {
            $line=trim($line);
            $partes = preg_split("/=/",$line);
            $param = $partes[0];
            $value = isset($partes[1])?$partes[1]:'';
            if(preg_match("/^TRUNK_NAME/",$line)) {
                $data['name']=$value;
            } else
            if(preg_match("/^TRUNK_PROTOCOL/",$line)) {
                $data['channel']=$value."/".$data['name'];
            } else
            if(preg_match("/^TRUNK_DIAL/",$line)) {
                $cont++;
                $data['type']='trunk';
                $data['exten']=$cont;
                $data['context_id']=0;
                $extenlist[$data['channel']] = $data;
                $data = array();
            }
        }
    }

    $conf["MGRPORT"]='5038';
    $conf["MGRUSER"]='internal';
    $conf["MGRPASS"]='insecure';

    if(!$res = $astman->connect($conf["MGRHOST"].":".$conf["MGRPORT"], $conf["MGRUSER"] , $conf["MGRPASS"], 'off')) {
        unset($astman);
    }

    if($astman) {
        $get = $astman->Command('dialplan show parkedcalls');
        $get = array_shift($get);
        $lines = preg_split("/\n/",$get);
        foreach($lines as $line) {
            $line = trim($line);
            if(preg_match("/Context/",$line)) {
                $partes = preg_split("/ /",$line);
                $context = substr(trim($partes[2]),1,-1);
            }
            if(preg_match("/=>/",$line)) {
                $partes = preg_split("/=>/",$line);
                $park_exten = substr(trim($partes[0]),1,-1);
                if($context=='parkedcalls') {
                    $channel = "PARK/default";
                    $name = 'Default';
                } else {
                    $partes = preg_split("/_/",$context);
                    $channel = "PARK/parkinglot_".$partes[1];
                    $name = $partes[1];
                }
                $data = array();
                $data['name']    = $name;
                $data['channel'] = $channel;
                $data['type']    = 'park';
                $data['exten']   = $park_exten;
                $data['context'] = $context;
                $data['context_id'] = 0;
                $extenlist[$data['channel']]  = $data;
            }
        }
    }

    return $extenlist;

}

function thirdlanedb_check_extension_usage() {

    global $db, $astman, $conf, $panelcontext;
    $extenlist = array();

    if($panelcontext<>'' && $panelcontext<>'GENERAL' && $panelcontext<>'1') {
        $where = "u.tenantid = '$panelcontext' ";
    } else {
        $where = "";
    }

    $panelcontexts = thirdlane_populate_contexts_from_tenants();

    $r_panelcontexts = array_flip($panelcontexts);

    // Extensions

    $fields = "phones AS dial, u.email AS email, CONCAT(last_name,' ',first_name) AS name, ";
    $fields.= "u.mobile AS external, s.context AS context, u.ext as extension, ";
    $fields.= "u.mailbox AS mailbox, d.department AS `group`, IFNULL(u.tenantid,'') as context_id ";

    $joins= "LEFT JOIN directory d ON u.ext=d.ext ";
    $joins.= "LEFT JOIN sip_users s ON s.name=substr(u.phones,5)";


    $results = $db->select($fields,"user_extensions u",$joins,"$where","name");

    if(is_array($results)) {
        foreach ($results as $result) {
            $data = array();
            $data['type']    = 'extension';
            $data['name']    = $result['name'];
            $data['channel'] = $result['dial'];
            $data['mailbox'] = $result['mailbox'];
            $data['context'] = $result['context'];
            $data['email']   = $result['email'];
            $data['group']   = $result['group'];
            $data['exten']   = $result['extension'];
            $data['external']= $result['external'];
            $data['context_id']= $result['context_id'];
            $extenlist[$data['channel']]  = $data;
        }
    }

    // Queues
    $where  = "queues.tenantid = '$panelcontext' ";
    $fields = "CONCAT('QUEUE/',name) AS dial,'default' AS context,id AS extension,CONCAT('Queue ',name) AS name, queues.tenantid as context_id";
    $results = $db->select($fields,'queues','',$where);
    if(is_array($results)) {
        foreach ($results as $result) {
            $data = array();
            $data['type']    = 'queue';
            $data['name']    = $result['name'];
            $data['context'] = $result['context'];
            $data['channel'] = $result['dial'];
            $data['exten']   = $result['extension'];
            $data['context_id']= $result['context_id'];
            $extenlist[$data['channel']]  = $data;
        }
    }
 
    // Conferences
    $where  = "conferences.tenantid = '$panelcontext' ";
    $fields = "CONCAT('CONFERENCE/',name) AS dial,'default' AS context,id AS extension,CONCAT('Conference ',name) AS name, conferences.tenantid as context_id ";
    $results = $db->select($fields,'conferences','',$where);
    if(is_array($results)) {
        foreach ($results as $result) {
            $data = array();
            $data['type']    = 'conference';
            $data['name']    = $result['name'];
            $data['context'] = $result['context'];
            $data['channel'] = $result['dial'];
            $data['exten']   = $result['extension'];
            $data['context_id']= $result['context_id'];
            $extenlist[$data['channel']]  = $data;
        }
    }

    // Trunks
    $where  = "trunks.tenantid = '$panelcontext' ";
    $fields = "CONCAT(protocol,'/',name) AS dial,'default' AS context,id AS extension,name, trunks.tenantid as context_id ";
    $results = $db->select($fields,'trunks','',$where);
    if(is_array($results)) {
        foreach ($results as $result) {
            $data = array();
            $data['type']    = 'trunk';
            $data['name']    = $result['name'];
            $data['context'] = $result['context'];
            $data['channel'] = $result['dial'];
            $data['exten']   = $result['extension'];
            $data['context_id']= $result['context_id'];
            $extenlist[$data['channel']]  = $data;
        }
    }

    if(!$res = $astman->connect($conf["MGRHOST"].":".$conf["MGRPORT"], $conf["MGRUSER"] , $conf["MGRPASS"], 'off')) {
        unset($astman);
    }

    if($astman) {
        $get = $astman->Command('dialplan show parkedcalls');
        $get = array_shift($get);
        $lines = preg_split("/\n/",$get);
        foreach($lines as $line) {
            $line = trim($line);
            if(preg_match("/Context/",$line)) {
                $partes = preg_split("/ /",$line);
                $context = substr(trim($partes[2]),1,-1);
            }
            if(preg_match("/=>/",$line)) {
                $partes = preg_split("/=>/",$line);
                $park_exten = substr(trim($partes[0]),1,-1);
                if($context=='parkedcalls') {
                    $channel = "PARK/default";
                    $name = 'Default';
                    $ctxid = 0;
                } else {
                    $partes = preg_split("/_/",$context);
                    $channel = "PARK/parkinglot_".$partes[1];
                    $name = $partes[1];
                    $ctxid = $r_panelcontexts[$name];
                }
                $data = array();
                $data['name']    = $name;
                $data['channel'] = $channel;
                $data['type']    = 'park';
                $data['exten']   = $park_exten;
                $data['context'] = $context;
                $data['context_id'] = $ctxid;
                if($ctxid==$panelcontext) { 
                    $extenlist[$data['channel']]  = $data;
                }
            }
        }
    }

    return $extenlist;

}
