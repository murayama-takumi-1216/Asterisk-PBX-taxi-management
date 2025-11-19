<?php

function elastix_populate_contexts_from_tenants() {
    // Simple function to populate the fop2context table with data from the tenants table
    // Returns an array with the contexts = ids
    global $db, $config_engine;

    $results = $db->select('domain,domain','organization');
    if(is_array($results)) {
        foreach ($results as $result) {
            $query = "INSERT INTO fop2contexts (context,name) VALUES ('%s','%s') ON DUPLICATE KEY UPDATE name='%s'";
            $db->consulta($query,array($result['domain'],$result['domain'],$result['domain']));
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

function elastix_check_extension_usage() {

    global $db, $astman, $conf, $panelcontext;
    $extenlist = array();

    if($panelcontext<>'' && $panelcontext<>'GENERAL') {
        $where = "fop2contexts.id = '$panelcontext' ";
    } else {
        $where = "";
    }

    $panelcontexts = elastix_populate_contexts_from_tenants();

    $r_panelcontexts = array_flip($panelcontexts);

    // Extensions

    $fields = "dial, IF(elxweb_device is NULL,'',replace(device,'_','@')) AS email, clid_name AS name, ";
    $fields.= "'' AS external, concat(organization_domain,'-ext-local') AS context, exten as extension, ";
    $fields.= "CONCAT(exten,'@',voicemail) AS mailbox, fop2contexts.id as context_id";

    $joins = "left join organization o on organization_domain=o.domain left join fop2contexts on o.domain=fop2contexts.context"; 

    $results = $db->select($fields,"extension",$joins,"$where","name");

    if(is_array($results)) {
        foreach ($results as $result) {
            $data = array();
            $data['type']    = 'extension';
            $data['name']    = $result['name'];
            $data['channel'] = $result['dial'];
            $data['mailbox'] = $result['mailbox'];
            $data['context'] = $result['context'];
            $data['email']   = $result['email'];
            $data['exten']   = $result['extension'];
            $data['context_id']= $result['context_id'];
            $extenlist[$data['channel']]  = $data;
        }
    }

    // Queues
    // select name,description,queue_number from queue;

    $fields = "CONCAT('QUEUE/',queue.name) AS dial,concat(substr(queue.name,1,instr(queue.name,'_')-1),'-ext-queues') AS context,queue_number AS extension,queue.description AS name, fop2contexts.id as context_id";
    $results = $db->select($fields,'queue',"left join organization o on substr(queue.name,1,instr(queue.name,'_')-1)=o.domain left join fop2contexts on o.domain=fop2contexts.context",$where);

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
    $fields = "CONCAT('CONFERENCE/',confno) AS dial,CONCAT(organization_domain,'-ext-meetme') AS context,ext_conf AS extension,CONCAT('Conference ',meetme.name) AS name, fop2contexts.id as context_id ";
    $results = $db->select($fields,'meetme','left join organization o on organization_domain=o.domain left join fop2contexts on o.domain=fop2contexts.context',$where);
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
    $fields = "CONCAT(upper(tech),'/',replace(channelid,'|','')) AS dial,'from-pstn' AS context,trunk.trunkid AS extension,CONCAT(upper(tech),'/',replace(channelid,'|','')) AS name, fop2contexts.id AS context_id ";
    $results = $db->select($fields,'trunk_organization','left join organization o on organization_domain=domain left join fop2contexts on o.domain=fop2contexts.context left join trunk on trunk_organization.trunkid=trunk.trunkid',$where);
    if(is_array($results)) {
        foreach ($results as $result) {
            $data = array();
            $data['type']    = 'trunk';
            $data['name']    = $result['name'];
            $data['context'] = $result['context'];
            $data['channel'] = $result['dial'];
            $data['exten']   = $result['extension'];
            $data['context_id']= $result['context_id'];
            if(preg_match("/^DAHDI/",$result['name'])) {
                $data['email']="1-32";
            } 
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
