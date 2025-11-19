<?php
function custom_populate_contexts_from_tenants() {
   $panelcontexts[0]='GENERAL';
   return $panelcontexts;
}

function custom_check_extension_usage() {
    global $db, $astman, $conf;
    $extenlist = array();

   $lines = file("/usr/local/fop2/ami_config.txt");
    $channel='';
    $trunkcount=0;
    $extenlist = array();
    foreach($lines as $line) {
        $line=trim($line);
        if(preg_match("/^Name/",$line)) {
            if(isset($data['type'])) {
                if($data['type']=='queue') {
                    $data['channel']='QUEUE/'.$data['exten'];
                    $data['name']='Queue '.$data['exten'];
                    $data['context']='queues';
                }
                if($data['type']=='trunk') {
                    $partes = preg_split("/-/",$data['channel']);
                    $nn = array_pop($partes);
                    $data['name']='Trunk '.$nn;
                }
                $extenlist[$data['channel']] = $data;
                $channel='';
                unset($data['type']);
           }
           preg_match("/Name=(.*)/",$line,$matches);
           $channel = "SIP/".$matches[1];
           $data = array();
           $data['channel']=$channel;
           $data['context_id'] = 0;
           $data['exten'] = $matches[1];
        } else {
            if($channel<>'') {
                $partes = preg_split("/=/",$line);
                $param = strtolower(trim($partes[0]));
                $value = isset($partes[1])?trim($partes[1]):'';
                if($param=='callerid') {
                    $partes = preg_split("/</",$value);
                    $name = $partes[0];
                    $name = substr($name,1,-2);
                    $exten = isset($partes[1])?$partes[1]:'';
                    $exten = preg_replace("/>/","",$exten);
                    $data['callerid'] = $value;
                    $data['label'] = $name;
                    $data['name'] = $name;
                } else
                if($param=='email') {
                    $data['email'] = $value;
                } else
                if($param=='context') {
                    $data['context'] = $value;
                } else
                if($param=='mailbox') {
                    $data['mailbox'] = $value;
                }
                if($param=='type') {
                    $data['type'] = $value;
                    if($value=='trunk') { $data['exten']='trunk'; }
                }
            }
        }
    }

    // last item
    $extenlist[$data['channel']] = $data;

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

