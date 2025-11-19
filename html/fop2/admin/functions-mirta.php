<?php

function mirtapbx_populate_contexts_from_tenants() {
    // Simple function to populate the fop2context table with data from the tenants table
    // Returns an array with the contexts = ids
    global $db, $config_engine, $config;

    $current_mirta_contexts = array();
    $current_fop2_contexts = array();

    $results = $db->select('te_code,te_name',$config['DBNAME'].'te_tenants');
    if(is_array($results)) {
        foreach ($results as $result) {
            $current_mirta_contexts[]=$result['te_code'];
            $query = "INSERT INTO fop2contexts (context,name) VALUES ('%s','%s') ON DUPLICATE KEY UPDATE name='%s'";
            $db->consulta($query,array($result['te_code'],$result['te_name'],$result['te_name']));
        }
    }
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

    $contexts_to_delete = array_diff($current_fop2_contexts,$current_mirta_contexts);
    $context_delete = implode("','",$contexts_to_delete);
    $query = "DELETE FROM fop2contexts WHERE context IN ('$context_delete')";

    $db->consulta($query);

    return $return;
}


// Same as functions-astdb but querying the asterisk database for sip peers and voicemail tables
function mirtapbx_check_extension_usage() {
    global $db, $astman, $conf, $panelcontext, $config;
    $extenlist = array();

    if($panelcontext<>'' && $panelcontext<>'GENERAL') {
        $where = "fop2contexts.id = '$panelcontext' ";
    } else {
        $where = "";
    }

    $cont=0;
    $fields= "CONCAT(ex_tech,'/',ex_number,'-',te_code) as dial,ex_number as extension,te_code as context,ex_name as name,concat(ex_number,'@',te_code) as mailbox,ex_webpassword as vmpin, fop2contexts.id as context_id,ex_trunk,CONCAT(ex_tech,'/',ex_number,'_',te_code) AS extrachannel, ex_id, te_code, sipfriends.name AS sip_name, sipfriends.secret AS sip_secret ";
    $results = $db->select($fields,$config['DBNAME']."ex_extensions","LEFT JOIN te_tenants on ex_te_id=te_tenants.te_id LEFT JOIN sipfriends ON CONCAT(ex_number,'-',te_code)=name LEFT JOIN fop2contexts on cast(te_code as char(50))=cast(fop2contexts.context as char(50))",$where,"","","");

    if(is_array($results)) {
        foreach ($results as $result) {

            if(preg_match("/[,&]/",$result['dial'])) {
                $partes = preg_split("/[,&]/",$result['dial']);
                $result['dial']=$partes[0];
            }

            $contprint = sprintf("%03d",$cont);
            $cont++;

            $thisexten        = $result['extension'];
            $vmpin            = $result['vmpin'];
            if ( $vmpin=="" ) { $vmpin=$thisexten; }

            //$vmemail   = $result['email'];

            $data = array();
            $data['name']         = ($result['name']=='')?$result['extension']:$result['name'];
            $data['mailbox']      = $result['mailbox'];

            if($result['ex_trunk']=='on') {
                $data['type']     = 'trunk';
            } else {
                $data['type']     = 'extension';
                $contprint        = "000";
                $qchannel         = "Local/AG-$contprint-NF-${result['ex_id']}@fromotherpbx|Penalty=0|MemberName=${result['extension']}|StateInterface=Custom:${result['extension']}-${result['te_code']}";
                $data['queuechannel'] = $qchannel;
            }

            $data['context']          = 'authenticated';
            $data['exten']            = $thisexten;
            $data['vmpin']            = $vmpin;
            $data['originatechannel'] = "Local/$thisexten@authenticated";
            $data['originatevariables'] = "PEERNAME=$thisexten@".$result['context'];
            //$data['email']   = $vmemail;
            $data['customastdb']      = 'CF/'.$thisexten;
            $data['context_id']       = $result['context_id']; 
            $data['channel']          = $result['dial']; 
            $data['accountcode']      = $result['context']; 
            $data['extrachannel']     = $result['extrachannel']; 
            $data['sip_username']     = $result['sip_name'];
            $data['sip_password']     = $result['sip_secret'];
            $extenlist[$result['dial']]  = $data;

        }
    }

    // Queues
    $fields = "CONCAT('QUEUE/',qu_id) AS dial,qu_number AS context,qu_number AS extension,qu_name AS name, fop2contexts.id as context_id";

    $results = $db->select($fields,'qu_queues',"LEFT JOIN te_tenants on qu_te_id=te_tenants.te_id LEFT JOIN fop2contexts on cast(te_code as char(50))=cast(fop2contexts.context as char(50))",'');
    if(is_array($results)) {
        $contador=0;
        foreach ($results as $result) {
            $contador++;
            if($result['extension']=='') { $result['extension'] = $contador; }
            if($result['context']=='') { $result['context'] = $contador; }
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
    $results = $db->select("CONCAT('CONFERENCE/',cr_number,'-',te_code) as dial,cr_name as name,cr_number AS extension, fop2contexts.id as context_id","cr_conferencerooms","LEFT JOIN te_tenants on cr_te_id=te_tenants.te_id LEFT JOIN fop2contexts on cast(te_code as char(50))=cast(fop2contexts.context as char(50))","","","","");

    if(is_array($results)) {
        foreach ($results as $result) {
            $data = array();
            $data['type']    = 'conference';
            $data['name']    = $result['name'];
            $data['context'] = 'authenticated';
            $data['channel'] = $result['dial'];
            $data['exten']   = $result['extension'];
            $data['context_id']= $result['context_id'];
            $extenlist[$data['channel']]  = $data;
        }
    }

    // Ringgroups
    $results = $db->select("CONCAT('RINGGROUP/',hu_number,'-',te_code) as dial,hu_name as name,hu_number AS extension, fop2contexts.id as context_id","hu_huntlists","LEFT JOIN te_tenants on hu_te_id=te_tenants.te_id LEFT JOIN fop2contexts on cast(te_code as char(50))=cast(fop2contexts.context as char(50))","","","","");

    if(is_array($results)) {
        foreach ($results as $result) {
            $data = array();
            $data['type']    = 'ringgroup';
            $data['name']    = $result['name'];
            $data['context'] = 'authenticated';
            $data['channel'] = $result['dial'];
            $data['exten']   = $result['extension'];
            $data['context_id']= $result['context_id'];
            $extenlist[$data['channel']]  = $data;
        }
    }

    // Park
    $results = $db->select("pk_name,pk_te_id,pk_start,fop2contexts.id as context_id","pk_parkinglots","LEFT JOIN te_tenants on pk_te_id=te_tenants.te_id LEFT JOIN fop2contexts on cast(te_code as char(50))=cast(fop2contexts.context as char(50))","","","","");
    if(is_array($results)) {
        foreach ($results as $result) {
            $exten = $result['pk_start'];
            $channel = "PARK/".$result['pk_name'];
            $data = array();
            $data['name']    = "Park $exten";
            $data['channel'] = $channel;
            $data['type']    = 'park';
            $data['exten']   = $exten;
            $data['context'] = 'authenticated';
            $data['context_id'] = $result['context_id'];
            $extenlist[$data['channel']]  = $data;
        }
    }

    // Garbage collect, remove fop2buttons with no context_id, a multi tenant system must have at least one context
    $query = "DELETE FROM fop2buttons WHERE context_id=0";
    $db->consulta($query);

    return $extenlist;

}



