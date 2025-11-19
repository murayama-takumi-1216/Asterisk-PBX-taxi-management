<?php   
    
$apikey="asdfasdfasdfasdfasdfasdfasdafaasdfQ";

function pbxware_populate_contexts_from_tenants() {
    global $db,$apikey;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/?apikey=${apikey}&action=pbxware.tenant.list");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $joutput = curl_exec($ch);
    $output = json_decode($joutput);
    curl_close($ch);
    foreach($output as $id=>$tn) {
         $contexto = $tn->name;
         $contexto = $tn->name;
         $query = "REPLACE INTO fop2contexts (id,context,name) VALUES ($id,'$contexto','$contexto')";
         $db->consulta($query);
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

function pbxware_check_extension_usage() {
    global $apikey, $db, $astman, $conf;
    $extenlist = array();

    $tenantid  = array();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost/?apikey=${apikey}&action=pbxware.tenant.list");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $joutput = curl_exec($ch);    
    $output = json_decode($joutput);
    curl_close($ch);
    foreach($output as $id=>$tn) {
         $tenantid[$id]=$tn->tenantcode;
    }       

    foreach($tenantid as $tnid=>$tncode) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost/?apikey=${apikey}&action=pbxware.ext.list&server=$tnid");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $joutput = curl_exec($ch);
        $output = json_decode($joutput);
        curl_close($ch);

        foreach($output as $id=>$tn) {
            $device = strtoupper($tn->protocol)."/".$tncode.$tn->ext;
            $label  = $tn->name;

            $data = array();
            $data['context_id']   = $tnid;
            $data['type']         = 'extension';
            $data['channel']      = $device;
            $data['name']         = $label;
            $data['exten']        = $tn->ext;
            $data['email']        = $tn->email;
            // $data['mailbox']      = $mailbox;
            $data['context']      = "t-$tncode";
            // $data['queuechannel'] = $qchannel;

            $extenlist[$device] = $data;
        }

    }

    return $extenlist;
}       
