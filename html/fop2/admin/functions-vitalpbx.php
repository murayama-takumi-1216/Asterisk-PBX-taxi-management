<?php
function vitalpbx_populate_contexts_from_tenants() {

    global $db, $config_engine, $config;

    $panelcontexts=array();
    $results = $db->select('tenant_id,name',$config['DBNAME'].'ombu_tenants');
    if(is_array($results)) {
        foreach ($results as $result) {
            $query = "INSERT INTO fop2contexts (id,context,name) VALUES ('%s','%s','%s') ON DUPLICATE KEY UPDATE name='%s'";
            $db->consulta($query,array($result['tenant_id'],$result['name'],$result['name'],$result['name']));
        }

        foreach($results as $id=>$data) {
            $panelcontexts[$data['tenant_id']]=$data['name'];
        }

    } else {
        $panelcontexts[0]='GENERAL';
    }

    return $panelcontexts;

}

function vitalpbx_check_extension_usage() {
    global $db, $astman, $conf;
    $extenlist = array();


    $fields= "if(ombu_extensions.tenant_id=1,concat(upper(technology),'/',user),concat(upper(technology),'/T',ombu_extensions.tenant_id,'_',user)) as dial, ombu_devices.description as name,extension,email,extension as vmpin,mailbox,ombu_devices.tenant_id as context_id,IF(ombu_extensions.tenant_id=1,concat('cos-',cos),concat('T',ombu_extensions.tenant_id,'_cos-',cos)) as context, concat('***',ombu_extensions.extension,'@feature-send_vm_msg') as extenvoicemail,ombu_tenants.path,secret";
    $results = $db->select($fields,'ombu_devices','LEFT JOIN ombu_extensions ON ombu_devices.extension_id=ombu_extensions.extension_id left join ombu_classes_of_service on ombu_extensions.class_of_service_id=ombu_classes_of_service.class_of_service_id  left join ombu_tenants on ombu_tenants.tenant_id=ombu_devices.tenant_id;',"","","","");
    if(is_array($results)) {
        foreach ($results as $result) {

            if(preg_match("/[,&]/",$result['dial'])) {
                $partes = preg_split("/[,&]/",$result['dial']);
                $result['dial']=$partes[0];
            }

            $thisexten = $result['extension'];
            $vmpin     = $result['vmpin'];
            $vmemail   = $result['email'];
            $partes = preg_split("/\//",$result['dial']);
            $sipuser = $partes[1];

            $data = array();
            $data['name'] = $result['name'];
            $data['channel'] = $result['dial'];
            $data['mailbox'] = $result['mailbox'];
            $data['type']    = 'extension';
            $data['context'] = $result['context'];
            $data['extenvoicemail'] = $result['extenvoicemail'];
            $data['exten']   = $thisexten;
            $data['vmpin']   = $vmpin;
            $data['email']   = $vmemail;
            $data['customastdb'] = $result['path']."/diversions/".$thisexten."/CFI/destination";
            $data['context_id'] = $result['context_id'];
            $data['sip_username'] = $sipuser;
            $data['sip_password'] = $result['secret'];

            $extenlist[$data['channel']]  = $data;

        }
    }


    // Conferences
    $results = $db->select("CONCAT('CONFERENCE/',extension) as dial,description as name,extension AS extension, tenant_id as context_id","ombu_conferences","","","","","");

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

    // Queues
    $fields = "IF(tenant_id=1,CONCAT('QUEUE/Q',extension),CONCAT('T',tenant_id,'_Q',extension)) AS dial, IF(tenant_id=1,'ext-queues',concat('T',tenant_id,'_ext-queues')) AS context,extension AS extension,description AS name, tenant_id as context_id";

    $results = $db->select($fields,'ombu_queues',"",'');
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


    $results = $db->select("CONCAT('PARK/parking-',tenant_id) as dial,description as name,extension AS extension, tenant_id as context_id, concat('parking-',tenant_id,'-parkedcalls') as context","ombu_parking_lots","","","","","");

    if(is_array($results)) {
        foreach ($results as $result) {
            $data = array();
            $data['type']    = 'park';
            $data['name']    = $result['name'];
            $data['context'] = $result['context'];
            $data['channel'] = $result['dial'];
            $data['exten']   = $result['extension'];
            $data['context_id']= $result['context_id'];
            $extenlist[$data['channel']]  = $data;
        }
    }


    return $extenlist;
}
