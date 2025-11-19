<?php

function peke_populate_contexts_from_tenants() {
    // Simple function to populate the fop2context table with data from the tenants table
    // Returns an array with the contexts = ids
    global $db, $config_engine, $config;

    $current_peke_contexts = array();
    $current_fop2_contexts = array();

    $results = $db->select('id,name',$config['DBNAME'].'Peke_Company');
    if(is_array($results)) {
        foreach ($results as $result) {
            $current_peke_contexts[]=$result['id'];
            $query = "INSERT INTO fop2contexts (context,name) VALUES ('%s','%s') ON DUPLICATE KEY UPDATE name='%s'";
            $db->consulta($query,array($result['id'],$result['name'],$result['name']));
        }
    }
    $results = $db->select("id,context,name","fop2contexts","","","");
    $return = array();
    if(is_array($results)) {
        foreach ($results as $result) {
            //if($result['exclude']==0) {
                $return[$result['id']] = $result['context'];
            //}
            $current_fop2_contexts[]=$result['context'];
        }
    }

    $contexts_to_delete = array_diff($current_fop2_contexts,$current_peke_contexts);
    $context_delete = implode("','",$contexts_to_delete);
    $query = "DELETE FROM fop2contexts WHERE context IN ('$context_delete')";

    $db->consulta($query);

    return $return;
}


// Same as functions-astdb but querying the asterisk database for sip peers and voicemail tables
function peke_check_extension_usage() {
    global $db, $astman, $conf, $panelcontext, $config;

    if($panelcontext<>'' && $panelcontext<>'GENERAL') {
        $where = "AND fop2contexts.id = '$panelcontext' ";
    } else {
        $where = "";
    }

    $extenlist = array();

    $dbtablesip       = "peke_pbx.Ast_sippeers AS sip";
    $dbtablevoicemail = "peke_pbx.Ast_voicemail AS voicemail";

    // Extensions

    $fields= "CONCAT('SIP/',sip.name) as dial,substr(sip.name,length(namedcallgroup)+1) as extension,sip.context as context,callerid as name,sip.mailbox,voicemail.password as vmpin,email, fop2contexts.id as context_id";
    $results = $db->select($fields,$dbtablesip,"LEFT JOIN $dbtablevoicemail on sip.name=voicemail.mailbox LEFT JOIN fop2contexts ON mohinterpret=fop2contexts.context","sip.name LIKE 'ext%' $where","","","");


    if(is_array($results)) {
        foreach ($results as $result) {

            if(preg_match("/[,&]/",$result['dial'])) {
                $partes = preg_split("/[,&]/",$result['dial']);
                $result['dial']=$partes[0];
            }

            $thisexten = $result['extension'];
            $vmpin     = $result['vmpin'];
            $vmemail   = $result['email'];
            $data = array();
            $data['name'] = $result['name'];
            $data['channel'] = $result['dial'];
            $data['mailbox'] = $result['mailbox'];
            $data['type']    = 'extension';
            $data['context'] = $result['context'];
            $data['exten']   = $thisexten;
            $data['vmpin']   = $vmpin;
            $data['email']   = $vmemail;
            $data['customastdb'] = 'CF/'.$thisexten;
            $data['context_id'] = $result['context_id']; 

            $extenlist[$data['channel']]  = $data;

        }
    }

    $fields = "A.name,extension AS exten,fop2contexts.id as context_id,companyId";
    $results = $db->select($fields,'Peke_Queue A',"LEFT JOIN fop2contexts ON A.companyId=fop2contexts.context","1=1 $where","","","");
    if(is_array($results)) {
        foreach ($results as $result) {
            $exten = $result['exten'];
            $print_name = preg_replace("/^".$result['companyId']."/","",$result['name']);
            $print_name = str_replace("_"," ",$print_name);
            $data = array();
            $data['channel'] = "QUEUE/".$result['name'];
            $data['type']    = "queue";
            $data['context'] = "from-users";
            $data['name']    = $print_name;
            $data['exten']   = $result['exten'];
            $data['context_id'] = $result['context_id'];
            $extenlist['QUEUE/'.$result['name']] = $data;
        }
    }

    // Conferences 
    if(is_readable("/etc/asterisk/meetme.conf")) {
        $lines = file("/etc/asterisk/meetme.conf");
        $data = array();
        $cont=0;
        foreach($lines as $line) {
            $line=trim($line);

            if(preg_match("/^conf =>/",$line)) {
                $partes = preg_split("/=>/",$line);
                $pertes = preg_split("/,/",$partes[1]);
                $value = trim($pertes[0]);
                $data['type']    = 'conference';
                $data['context'] = 'default';
                $data['exten'] = $value;
                $data['channel'] = "CONFERENCE/".$value;
                $data['context_id'] = 0; 
                $extenlist[$data['channel']] = $data;
            }

        }
    }

    if(!$res = $astman->connect($conf["MGRHOST"].":".$conf["MGRPORT"], $conf["MGRUSER"] , $conf["MGRPASS"], 'off')) {
        unset($astman);
    }

    if($astman) {
        $get = $astman->Command('dialplan show parkedcalls');
        if(is_array($get)) {

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
    }

    return $extenlist;

}
