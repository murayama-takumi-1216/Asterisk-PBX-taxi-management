<?php

function snep_populate_contexts_from_tenants() {
   $panelcontexts[0]='GENERAL';
   return $panelcontexts;
}

function snep_check_extension_usage() {

    global $db, $astman, $conf, $panelcontext;
    $extenlist = array();

    $where = " host='dynamic' ";

    $panelcontexts = snep_populate_contexts_from_tenants();

    $r_panelcontexts = array_flip($panelcontexts);

    // Extensions

    $fields = "canal AS dial, peers.email, callerid AS name, ";
    $fields.= "peers.context AS context, name as extension, ";
    $fields.= "voicemail_users.mailbox AS mailbox ";

    $joins = "left join voicemail_users on name=customer_id";

    $results = $db->select($fields,"peers",$joins,"$where","name");

    if(is_array($results)) {
        foreach ($results as $result) {
            $data = array();
            $data['type']    = 'extension';

            $name = $result['name'];
            if(preg_match("/</",$name)) {
                $partes = preg_split("/ </",$name);
                $name  = $partes[0];
                $name = preg_replace("/\"/","",$name);
            }
 
            $data['name']    = $name;
            $data['channel'] = $result['dial'];
            //$data['mailbox'] = $result['mailbox'];
            $data['context'] = $result['context'];
            $data['email']   = $result['email'];
            $data['exten']   = $result['extension'];
            $data['context_id']= 0;
            $extenlist[$data['channel']]  = $data;
        }
    }

    // Queues
    $where  = "";

    $joins = "left join regras_negocio_actions_config r1 on r1.value=q.name and r1.key='queue' left join regras_negocio_actions r2 on r1.regra_id=r2.regra_id left join regras_negocio r3 on r2.regra_id=r3.id";

    $fields = "CONCAT('QUEUE/',q.name) AS dial,'default' AS context,destino AS extension,CONCAT('Queue ',name) AS name, 0 as context_id";
    $results = $db->select($fields,'queues q',$joins,$where);
    if(is_array($results)) {
        foreach ($results as $result) {
            $data = array();
            $extension       = $result['extension'];
            $extension       = preg_replace("/RX:/","",$extension);
            $data['type']    = 'queue';
            $data['name']    = $result['name'];
            $data['context'] = $result['context'];
            $data['channel'] = $result['dial'];
            $data['exten']   = $extension;
            $data['context_id']= $result['context_id'];
            $extenlist[$data['channel']]  = $data;
        }
    }

    // Conferences
    if(is_readable("/etc/asterisk/snep/snep-conferences.conf")) {
        $lines = file("/etc/asterisk/snep/snep-conferences.conf");
        $data = array();
        $cont=0;
        foreach($lines as $line) {
            $line=trim($line);
            if(preg_match("/^;SNEP\(/",$line)) {
                $partes = preg_split("/\(/",$line);
                $pertes = preg_split("/\)/",$partes[1]);
                $value = trim($pertes[0]);
                $data['type']    = 'conference';
                $data['context'] = 'conferences';
                $data['exten'] = $value;
                $data['context_id'] = 0;
                $data['channel'] = "CONFERENCE/".$value;
                $data['name'] = "Conference $value";
                $extenlist[$data['channel']] = $data;
            }
        }
    }

    // Trunks
    $where  = "";
    $fields = "channel AS dial,'defaulttrunk' AS context,id AS extension,callerid as name, 0 as context_id ";
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

/*
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
*/
    return $extenlist;

}
