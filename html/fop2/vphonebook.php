<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(15);
require_once("config.php");

$allowed = '';

$mydir = dirname(__FILE__);
if(is_file($mydir.'/admin/plugins/phonepro/phonepro.ini')) {
  if (isset($_SERVER['PHP_AUTH_USER'])) {
    $phonepro_conf = parse_ini_file($mydir.'/admin/plugins/phonepro/phonepro.ini',true);
    $check_pass = $phonepro_conf['chatbroker_api_password'];
    if($_SERVER['PHP_AUTH_USER']=='admim' && $_SERVER['PHP_AUTH_PW']==$amp_conf['AMPDBPASS']) {
      header('HTTP/1.0 401 Unauthorized');
      $resp = array("success"=>false,"status"=>401,"details"=>"Unauthorized");
      echo json_encode($resp);
      exit;
    } else {
      $extension = $_REQUEST['extension'];
      $context   = isset($_REQUEST['context'])?$_REQUEST['context']:'general';
      $allowed   = 'yes';
      if(isset($_REQUEST['name'])) {
        $res=$db->consulta("SET NAMES UTF8");
        $res=$db->consulta("SELECT CONCAT(firstname,' ',lastname) as fullname, CONCAT(UPPER(SUBSTRING(firstname, 1, 1)), UPPER(SUBSTRING(lastname, 1, 1))) AS initials FROM visual_phonebook LEFT JOIN visual_phonebook_phones ON id=contact_id WHERE context='%s' AND (number='%s' OR email='%s') LIMIT 1",$context,$_REQUEST['name'],$_REQUEST['name']);
        if($db->num_rows($res)>0) {
          $row=$db->fetch_assoc($res);
          if($row['initials']!='') {
            $resp = array("success"=>true,"initials"=>$row['initials'],"fullname"=>$row['fullname']);
          } else {
            $resp = array("success"=>false,"initials"=>'NN',"fullname"=>'');
          }
          echo json_encode($resp);
          exit;
        } else {
          $resp = array("success"=>false,"initials"=>'NN',"fullname"=>'');
          echo json_encode($resp);
          exit;
        }
      }
    }
  } else {
    file_put_contents("/tmp/check.log", "no auth\n", FILE_APPEND);
    $extension = $_SESSION[MYAP]['extension'];
    $context   = $_SESSION[MYAP]['context'];
  }
} else {
    file_put_contents("/tmp/check.log", "no file ".getcwd()."\n", FILE_APPEND);
  $extension = $_SESSION[MYAP]['extension'];
  $context   = $_SESSION[MYAP]['context'];
}


if(isset($_REQUEST['image'])) {
    // this comes only from FOP2, requires FOP2 valid session, but not specific session permissions
    if(!isset($_REQUEST['context'])&&!isset($_SESSION[MYAP]['key'])) {
        header("HTTP/1.1 401 Unauthorized");
        $resp = array("success"=>false,"status"=>401,"details"=>"Unauthorized");
        echo json_encode($resp);
        exit;
    }
    if(trim($_REQUEST['image'])=='') {
        $resp = array("success"=>false,"status"=>200,"details"=>"Empty query");
        echo json_encode($resp);
        exit;
    } else {
        // We query the image file on phonebook if any
        $res=$db->consulta("SELECT picture FROM visual_phonebook LEFT JOIN visual_phonebook_phones ON id=contact_id WHERE context='%s' AND (number='%s' OR email='%s') LIMIT 1",$context,$_REQUEST['image'],$_REQUEST['image']);
        if($db->num_rows($res)>0) {
            $row=$db->fetch_assoc($res);
            if($row['picture']!='' && file_exists('uploads/'.$row['picture'])) {
                $resp = array("success"=>true,"picture"=>$row['picture']);
            } else {
                $resp = array("success"=>false,"status"=>200,"details"=>"No profile image");
            }
            echo json_encode($resp);
            exit;
        } else {
            $resp = array("success"=>false,"status"=>200,"details"=>"No profile image");
            echo json_encode($resp);
            exit;
        }
    }
    exit;
}

if($allowed=='') {
    // not allowed via basic auth, check session permissions
    $allowed = isset($_SESSION[MYAP]['phonebook'])?$_SESSION[MYAP]['phonebook']:'no';
}

if($allowed=='no') {
    // check individual permissions
    if(isset($_SESSION[MYAP]['permit'])) {
        $permits = preg_split("/,/",$_SESSION[MYAP]['permit']);
        if($allowed=='no' && (in_array('phonebook',$permits) || in_array('readonly_phonebook',$permits))) {
           $allowed='yes';
        }
    }
}

if($allowed=='no') {
    header('Content-Type: application/json; charset=utf-8');
    header("HTTP/1.1 401 Unauthorized");
    $resp = array("success"=>false,"status"=>401,"details"=>"Unauthorized");
    echo json_encode($resp);
    exit;
}

$texto = $_REQUEST['term'];

if (!function_exists('json_encode')) {
    function json_encode($content) {
        require_once 'JSON.php';
        $json = new Services_JSON;
        return $json->encode($content);
    }
}

if($context=="general") { $context=""; }

if(isset($_REQUEST['id'])) {
    // We query the contact id and tag color
    if (strpos($_REQUEST['channel'], 'instagram') !== false) {
        $res=$db->consulta("SELECT A.id,D.color FROM visual_phonebook A LEFT JOIN visual_phonebook_phones B ON A.id=B.contact_id LEFT JOIN visual_phonebook_tags_contacts C on A.id=C.contact_id LEFT JOIN visual_phonebook_tags D on C.tag_id=D.id  WHERE A.context='%s' AND (instagram='%s') LIMIT 1",$context,$_REQUEST['id'],$_REQUEST['firstname'],$_REQUEST['lastname']);
    } else if (strpos($_REQUEST['channel'], 'telegram') !== false) {
        $res=$db->consulta("SELECT A.id,D.color FROM visual_phonebook A LEFT JOIN visual_phonebook_phones B ON A.id=B.contact_id LEFT JOIN visual_phonebook_tags_contacts C on A.id=C.contact_id LEFT JOIN visual_phonebook_tags D on C.tag_id=D.id  WHERE A.context='%s' AND (telegram='%s') LIMIT 1",$context,$_REQUEST['id'],$_REQUEST['firstname'],$_REQUEST['lastname']);
    } else if (strpos($_REQUEST['channel'], 'webchat') !== false) {
        $res=$db->consulta("SELECT A.id,D.color FROM visual_phonebook A LEFT JOIN visual_phonebook_phones B ON A.id=B.contact_id LEFT JOIN visual_phonebook_tags_contacts C on A.id=C.contact_id LEFT JOIN visual_phonebook_tags D on C.tag_id=D.id  WHERE A.context='%s' AND (email='%s') LIMIT 1",$context,$_REQUEST['id'],$_REQUEST['firstname'],$_REQUEST['lastname']);
    } else {
        $res=$db->consulta("SELECT A.id,D.color FROM visual_phonebook A LEFT JOIN visual_phonebook_phones B ON A.id=B.contact_id LEFT JOIN visual_phonebook_tags_contacts C on A.id=C.contact_id LEFT JOIN visual_phonebook_tags D on C.tag_id=D.id  WHERE A.context='%s' AND (number='%s' OR (firstname='%s' AND lastname='%s') OR email='%s') LIMIT 1",$context,$_REQUEST['id'],$_REQUEST['firstname'],$_REQUEST['lastname'],$_REQUEST['id']);
    }
    if($db->num_rows($res)>0) {
        $row=$db->fetch_assoc($res);
        if($row['color']==null) $row['color']='';
        $result = array("id"=>intval($row['id']),"color"=>$row['color']);
        echo json_encode($result);
    } else {
        $result =  array("id"=>-1,"color"=>"");
        echo json_encode($result);
    }
    exit;
}

if(preg_match("/\.tel$/",$texto)) {
    $results = query_tel($texto);
} else {
    $results = query_phonebook($texto);
}

echo json_encode($results);

function query_phonebook($texto) {
    global $db;
    global $context;
    global $extension;

    $ret = array();

    $results=array();
    $db->consulta("SET NAMES 'UTF8'");

    $res=$db->consulta("SELECT number,CONCAT(firstname,' ',lastname,' (',company,')') AS name,CONCAT(identification,' ',position) AS other,GROUP_CONCAT(tag) AS tag FROM visual_phonebook A LEFT JOIN visual_phonebook_phones ON id=contact_id LEFT JOIN visual_phonebook_tags_contacts B on A.id=B.contact_id LEFT JOIN visual_phonebook_tags C ON C.id=B.tag_id WHERE A.context='%s' AND (owner='%s' OR (owner<>'%s' AND private='no')) GROUP BY number HAVING LOWER(name) LIKE '%%%s%%' OR LOWER(other) LIKE '%%%s%%' OR LOWER(tag) LIKE '%%%s%%' ORDER BY CONCAT(firstname,' ',lastname)",array($context,$extension,$extension,strtolower($texto),strtolower($texto),strtolower($texto)));

    $cont=0;
    if($db->num_rows($res)>0) {
        while($row=$db->fetch_assoc($res)) {
            $htmlname = htmlspecialchars($row['name']);
            $htmlname = preg_replace("/\(\)/","",$htmlname);
            $phone = preg_replace("/[^0-9]/","", $row['number']);

            if($phone<>"") {
                $cont++;
                $results[] = array('name'=>'','value'=>$htmlname, 'phone'=>$phone, 'cont'=>$cont);
            }
        }
    }
    return $results;
}

function query_tel($domain) {
    return ShowSection(dns_get_record ($domain,DNS_NAPTR));
}

function ShowSection($result) {
    $ret  = Array();
    $tel  = Array();
    $voip = Array();
    $replaceArray = array(array(), array());

    for ($i=0; $i<32; $i++) {
        $replaceArray[0][] = chr($i);
        $replaceArray[1][] = "";
    }

    for ($i=127; $i<160; $i++) {
        $replaceArray[0][] = chr($i);
        $replaceArray[1][] = "";
    }

    foreach($result as $idx => $record) {

        $record['services'] = str_replace($replaceArray[0], $replaceArray[1], $record['services']);
        $record['regex'] = str_replace($replaceArray[0], $replaceArray[1], $record['regex']);
        $papo = preg_split("/!/",$record['regex']);
        if(preg_match("/voice/",$record['services'])) {
           $tel[] = preg_replace("/tel:/","",$papo[2]);
        }
        if(preg_match("/voip/",$record['services']) || preg_match("/sip/",$record['services'])) {
           $voip[] = preg_replace("/sip:/","SIP/",$papo[2]);
        }
        if(preg_match("/skype/i",$record['services'])) {
           $voip[] = preg_replace("/skype:/i","SKYPE/",$papo[2]);
        }
        if(preg_match("/web/",$record['services'])) {
           $web[] = preg_replace("/web:/","",$papo[2]);
        }
    }

    foreach($tel as $valor) {
        if($valor<>"") {
            $ret[]= array('name'=>"Voice",'value'=>$valor);
        }
    }
    foreach($voip as $valor) {
        if($valor<>"") {
            $ret[]= array('name'=>"Voip",'value'=>$valor);
        }
    }
    foreach($web as $valor) {
        if($valor<>"") {
            $ret[]= array('name'=>"Web",'value'=>$valor);
        }
    }
    return $ret;
}


