<?php

function astdb_check_extension_usage() {
    global $db, $astman, $conf;
    $extenlist = array();

    // Extensions

    $voicemail = read_voicemail_conf("/etc/asterisk/voicemail.conf");
    $voicemail_pins = $voicemail['pin'];

    $lines = file("/etc/asterisk/sip.conf");
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
                $extenlist[$data['channel']] = $data;
            }
            preg_match("/\[([^]]*)].*/",$line,$matches);
            $channel = $matches[1];
            $data = array();
            $data['channel']="SIP/".$channel;
            $data['context_id'] = 0; 
        } else {
            if($channel<>'') { 
                $partes = preg_split("/=/",$line);
                $param = trim($partes[0]);
                $value = isset($partes[1])?trim($partes[1]):'';
                if($param=='callerid') {
                    $partes = preg_split("/</",$value);
                    $name = $partes[0];
                    $exten = isset($partes[1])?$partes[1]:'';
                    $exten = preg_replace("/>/","",$exten);
                    $data['callerid'] = $value;
                    $data['name'] = $name;
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
                $data['type']    = "queue";
                $data['context'] = "default";
                $data['context_id'] = 0; 
                $data['name']    = $queue;
                $data['exten']   = $cont;
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
                $data['context_id'] = 0; 
                $data['channel'] = "CONFERENCE/".$value;
                $extenlist[$data['channel']] = $data;
            }

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
