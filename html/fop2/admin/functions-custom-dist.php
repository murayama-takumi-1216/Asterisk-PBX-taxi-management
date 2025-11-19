<?php
function custom_populate_contexts_from_tenants() {
   $panelcontexts[0]='GENERAL';
   return $panelcontexts;
}

// Same as functions-astdb but querying the asterisk database for sip peers and voicemail tables
function custom_check_extension_usage() {
    global $db, $astman, $conf;
    $extenlist = array();

    $dbtablesip       = "asterisk.sippeers AS sip";
    $dbtablevoicemail = "asterisk.voicemail AS voicemail";

    // Extensions

    $voicemail = read_voicemail_conf("/etc/asterisk/voicemail.conf");
    $voicemail_pins = $voicemail['pin'];

    $sip_config_files = array("/etc/asterisk/sip.conf","/etc/asterisk/sip_custom.conf","/etc/asterisk/sip_additional.conf");
    foreach($sip_config_files as $file) {
    if(!is_readable($file)) { continue;  }
    $lines = file($file);
    $channel='';
    $trunkcount=0;
    $extenlist = array();
    foreach($lines as $line) {
        $line=trim($line);
        if(preg_match("/^\[/",$line)) {
            if(isset($data['channel'])) {
                if(isset($data['callerid'])) {
                    if(strpos($data['callerid'],'asreceived')!==false) {
                        $trunkcount++;
                        $data['type']='trunk';
                        $data['exten']='trunk_'.$trunkcount;
                    } else {
                        $data['type']='extension';
                        $mbox = isset($data['mailbox'])?$data['mailbox']:'';
                        if(isset($data['exten'])) {
                            $data['vmpin'] = isset($voicemail_pins[$mbox])?$voicemail_pins[$mbox]:$data['exten'];
                        }
                    }
                } else {
                    $trunkcount++;
                    $data['type']='trunk';
                    $data['exten']='trunk_'.$trunkcount;
                }
                $data['context_id'] = 0; 
                if(!isset($data['context'])) {  $data['context']='default'; }
                $extenlist[$data['channel']] = $data;
            }
            preg_match("/\[([^]]*)].*/",$line,$matches);
            $channel = $matches[1];
            $data = array();
            $data['channel']="SIP/".$channel;
        } else {
            if($channel<>'') { 
                $partes = preg_split("/=/",$line);
                $param = trim($partes[0]);
                $value = isset($partes[1])?trim($partes[1]):'';
                if($param=='callerid') {
                    if(preg_match("/</",$value)) {
                        $partes = preg_split("/ </",$value);
                        $name = $partes[0];
                        $exten = $partes[1];
                        $exten = preg_replace("/>/","",$exten);
                    } else {
                       $name = $value;
                       $exten = $value;
                    }
                    $data['callerid'] = $value;
                    $data['name'] = str_replace('"','',$name);
                    $data['exten'] = $exten;
                } else
                if($param=='host') {
                    $data['host'] = $value;
                } else
                if($param=='context') {
                    $data['context'] = $value;
                } else
                if($param=='mailbox') {
                    $data['mailbox'] = $value;
                } 
            }
        }
    }
    // last item
    $mbox = isset($data['mailbox'])?$data['mailbox']:'';
    if(isset($data['callerid'])) {
        $data['type']='extension';
    } else {
        $trunkcount++;
        $data['type']='trunk';
        $data['exten']='trunk_'.$trunkcount;
    }
    if(isset($data['exten'])) {
        $data['vmpin'] = isset($voicemail_pins[$mbox])?$voicemail_pins[$mbox]:$data['exten'];
    }
    $data['context_id'] = 0; 
    if(!isset($data['context'])) {  $data['context']='default'; }
    $extenlist[$data['channel']] = $data;

    } // end foreach sip config files

    $fields= "CONCAT('SIP/',name) as dial,name as extension,sip.context as context,callerid as name,sip.mailbox,voicemail.password as vmpin,email";
    $results = $db->select($fields,$dbtablesip,"LEFT JOIN $dbtablevoicemail on sip.name=voicemail.mailbox","","","","");
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
            $data['context_id'] = 0; 

            $extenlist[$data['channel']]  = $data;

        }
    }

    // Queues
    if(is_readable("/etc/asterisk/queues.conf")) { 
        $lines = file("/etc/asterisk/queues.conf");
        $data = array();
        $cont=0;
        foreach($lines as $line) {
            $line=trim($line);
            if(preg_match("/^\[/",$line)) {
                $cont++;
                preg_match("/\[([^]]*)].*/",$line,$matches);
                $queue = $matches[1];
                $data['channel'] = "QUEUE/$queue";
                $data['type']    = "queue";
                $data['context'] = "default";
                $data['name']    = $queue;
                $data['exten']   = $cont;
                $data['context_id'] = 0; 
                $extenlist[$data['channel']] = $data;
            }
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
