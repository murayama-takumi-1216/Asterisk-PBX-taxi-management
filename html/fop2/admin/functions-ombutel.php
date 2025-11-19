<?php
function ombutel_populate_contexts_from_tenants() {
   $panelcontexts[0]='GENERAL';
   return $panelcontexts;
}

// Same as functions-astdb but querying the asterisk database for sip peers and voicemail tables
function ombutel_check_extension_usage() {
    global $db, $astman, $conf;
    $extenlist = array();
  
    $token = ombutel_authenticate(array());

    $postdata = http_build_query(
        array(
            'token' => $token
        )
    );

    $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        )
    );

    $scontext = stream_context_create($opts);

    $fxs=array();
    $dahdichan = json_decode(file_get_contents('http://localhost/api/dahdi_channels', false, $scontext));
    foreach($dahdichan->data as $dah) {
        $chanid  = $dah->channel_id;
        $chanidx = $dah->global_index;
        $fxs[$chanid]=$chanidx;
    }

    $mapdevice=array();
    $devices = json_decode(file_get_contents('http://localhost/api/devices', false, $scontext));
    foreach($devices->data as $device) {
        $exten = $device->assigned_exten;
        $devid = $device->extension_id;
        $tech  = strtoupper($device->technology);
        $name  = $device->user;
        if($tech=='FXS') { 
            $tech='DAHDI'; 
            $name = $fxs[$device->user];
        }

        if($exten<>'') {
            $mapdevice[$devid][]=$tech."/".$name;
        }
    }

    $cos=array();
    $classofservice = json_decode(file_get_contents('http://localhost/api/class_of_services', false, $scontext));
    foreach($classofservice->data as $classof) {
        $id = $classof->class_of_service_id;
        $suffix = $classof->cos;
        $cos[$id]=$suffix;
    }

    $tnt=array();
    $tenants = json_decode(file_get_contents('http://localhost/api/tenants', false, $scontext));
    foreach($tenants->data as $tenant) {
        $id   = $tenant->tenant_id;
        $def  = $tenant->default;
        $path = $tenant->path;
        $tnt[$id]=$path;
    }

    $extensions = json_decode(file_get_contents('http://localhost/api/extensions', false, $scontext));
    foreach($extensions->data as $extension) {
        $data = array();
        $extrachannel = "";
        if(isset($mapdevice[$extension->extension_id])) {
            
            $device       = array_shift($mapdevice[$extension->extension_id]);
            $extrachannel = implode("&",$mapdevice[$extension->extension_id]);

        } else {
            $device  = "SIP/{$extension->extension}";
        }
        $mailbox = $extension->mailbox;
        $fullcid = $extension->internal_cid;
        $hotdesk = $extension->hot_desking;
        $cosid   = $extension->class_of_service_id;
        $name    = $extension->name;
        $ext     = $extension->extension;
     
        $context = "cos-".$cos[$cosid];

        if ( $hotdesk == "true" ) {
            $device = "USER/{$extension->extension}"; 
        }

        if(preg_match("/</",$fullcid)) {
            $partes  = preg_split("/</",$fullcid);
            $cidnum  = substr(trim($partes[1]),0,-1);
            $cidname = substr(trim($partes[0]),1,-1);
            $fullcid = $cidname;
        }

        if($cidname==$ext) { $cidname=$name; }

        // this is the default tenant hash/path
        $tenant = $tnt[1];

        // ombutel feature code for add queue member does not set statedevice 
        // $qchannel  = ombutel_construct_queuechannel($extension->extension,$device,$cidname,$context);
        $qchannel  = ombutel_construct_queuechannel($extension->extension,'',$cidname,$context);

        // passing hint as the state interface
        // $qchannel  = ombutel_construct_queuechannel($extension->extension,$extension->extension.'@etension-hints',$cidname,$context);

        $data = array();
        $data['context_id']   = 0;
        $data['type']         = 'extension';
        $data['channel']      = $device;
        $data['name']         = $cidname;
        $data['exten']        = $extension->extension;
        $data['mailbox']      = $mailbox;
        $data['context']      = $context;
        $data['customastdb']  = $tenant;
        $data['queuechannel'] = $qchannel;

        if ( $hotdesk == "true" ) {
            $data['originatechannel']="Local/".$extension->extension."@$context";
        }
        if ( $extrachannel <> "") {
            $data['extrachannel']=$extrachannel;
        }
 
        $extenlist[$device] = $data;
 
    }

    // Queues
    $queues = json_decode(file_get_contents('http://localhost/api/queues', false, $scontext));
    foreach($queues->data as $queue) {
        $data = array();
        $exten   = $queue->extension;
        $device  = "QUEUE/q{$exten}";
        $name    = $queue->description;
        $context = "ext-queues";

        $data['channel']    = $device;
        $data['type']       = "queue";
        $data['context']    = $context;
        $data['exten']      = $queue->extension;
        $data['name']       = $name;
        $data['context_id'] = 0; 

        $extenlist[$data['channel']] = $data;

    }

    // Conferences
    $conferences = json_decode(file_get_contents('http://localhost/api/conferences', false, $scontext));
    foreach($conferences->data as $conference) {
        $data = array();
        $exten   = $conference->extension;
        $device  = "CONFERENCE/{$exten}";
        $name    = $conference->description;
        $context = "ext-conferences";

        $data['channel']    = $device;
        $data['type']       = "conference";
        $data['context']    = $context;
        $data['exten']      = $conference->extension;
        $data['name']       = $name;
        $data['context_id'] = 0; 

        $extenlist[$data['channel']] = $data;

    }

    // Trunks
    $trunks = json_decode(file_get_contents('http://localhost/api/trunks', false, $scontext));
    foreach($trunks->data as $trunk) {
        $device  = strtoupper($trunk->technology)."/".$trunk->outgoing_username;
        $exten   = $trunk->trunk_id;
        $name    = $trunk->description;
        $context = "ext-trunks";

        $data['channel']    = $device;
        $data['type']       = "trunk";
        $data['context']    = $context;
        $data['exten']      = $exten;
        $data['name']       = $name;
        $data['context_id'] = 0; 

        $extenlist[$data['channel']] = $data;

    }

    // Ring Groups
    $ringgroups = json_decode(file_get_contents('http://localhost/api/ring_groups', false, $scontext));
    foreach($ringgroups->data as $ringgroup) {
        $exten   = $ringgroup->extension;
        $device  = "RINGGROUP/{$exten}";
        $name    = $ringgroup->description;
        $context = "ext-ringgroups";

        $data['channel']    = $device;
        $data['type']       = "ringgroup";
        $data['context']    = $context;
        $data['exten']      = $ringgroup->extension;
        $data['name']       = $name;
        $data['context_id'] = 0; 

        $extenlist[$data['channel']] = $data;

    }

    // Parking Lots 
    $parkinglots = json_decode(file_get_contents('http://localhost/api/parking_lots',false,$scontext));
    foreach($parkinglots->data as $parkinglot) {
        $exten   = $parkinglot->extension;
        $device  = "PARK/parking-{$parkinglot->parking_lot_id}";
        $name    = $parkinglot->description;
        $context = "parking-{$parkinglot->parking_lot_id}-parkedcalls";

        $data['channel']    = $device;
        $data['type']       = "park";
        $data['context']    = $context;
        $data['name']       = $name;
        $data['exten']      = $parkinglot->extension;
        $data['context_id'] = 0; 

        $extenlist[$data['channel']] = $data;

    }

    return $extenlist;

}

function ombutel_construct_queuechannel($extension,$device='',$name,$qcontext) {

    // queuechannel=
    //   Local/602@cos-all/n|Penalty=0|MemberName=Nicolas Gudino 1|StateInterface=SIP/602
    //   &Local/602@cos-all/n|Penalty=0|MemberName=Nicolas Gudino 1|StateInterface=SIP/602|Queue=100
    //   &Local/602@cos-all/n|Penalty=0|MemberName=Nicolas Gudino 1|StateInterface=SIP/602|Queue=101

    $member = array();

    $stint = '';
    if($device<>'') {
        $stint = "|StateInterface=$device";
    }

    $def[] = "Local/$extension@$qcontext|Penalty=0|MemberName=$name".$stint;

    $return = implode("&",$def);
    return $return;
}
