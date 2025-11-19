<?php
require_once("config.php");
$debug=0;
set_time_limit(3);

if( ! ini_get('date.timezone') )
{
    date_default_timezone_set('GMT');
}

if(isset($_REQUEST['refresh'])) {
     session_start();
     die();
}

if(SESSIONDEBUG===true) {
    $debug=1;
    $now = date('r');
}

if($debug==1) {
    $fp = fopen("/tmp/setvar.log","a");
    fputs($fp,"--------------------------------\n");
}

$archiconf = "/usrlocal/fop2/fop2.cfg";

$conf_location=array();
$conf_location[]="/usr/local/fop2/fop2.cfg";
$conf_location[]="/etc/fop2/fop2.cfg";

foreach($conf_location as $fconf) {
   if(is_readable($fconf)) {
      $archiconf=$fconf;
   }
}

$conf = parse_conf($archiconf);

function parse_conf($filename) {

    $file = file($filename);

    foreach ($file as $line) {
        if (preg_match("/^\s*([\w]+)\s*=\s*\"?([\w\/\:\.\*\%!=\+\#@&\\$-]*)\"?\s*([;].*)?/",$line,$matches)) {
            $matches[1] = preg_replace("/^AMP/","",$matches[1]);
            $conf[ $matches[1] ] = $matches[2];
        }
    }

    return $conf;
}



function authReq($exten,$pass,$passedkey) {
    global $fp, $debug, $now, $conf;
    $dati='';
    if($debug==1) {
        $pepe = print_r($_SESSION[MYAP],1);
        fputs($fp,"sesion: $pepe\n");
        fputs($fp,"app: ".MYAP."\n");
    }
    if($exten=="") { 
        if($debug==1) { fclose($fp); }
        return 0; 
    }
    $timeout   = 5;
    if(isset($conf['listen_ip'])) {
       $host = $conf['listen_ip'];
    } else {
       $host = "localhost";
    }
    $context   = isset($_SESSION[MYAP]['context'])?$_SESSION[MYAP]['context']:'GENERAL';
    if($context=="") { $context="GENERAL"; }
    $optfile = "fop2-variables".strtoupper($context).".txt";
    
    if($debug==1) { fputs($fp,"tengo en sesion el contexto $context\n"); }

    if(is_file($optfile)) {
      $text = file_get_contents($optfile);
      $partes = preg_match("/port=(\d+)/",$text,$matches);
      $port=$matches[1];
      if($port=="") { $port="4445"; }
    } else {
      $port="4445";
    }
    $now = date('r');
    if($debug==1) { fputs($fp,"$now\topening manager connection on host $host, port $port\n"); }
    $sk=fsockopen($host,$port,$errnum,$errstr,$timeout) ;
    $now = date('r');
    if($debug==1) { fputs($fp,"$now\tconnection to manager ok!\n"); }
    if (!is_resource($sk)) {
        if($debug==1) { $now = date('r'); fputs($fp,"$now\texit zero, could not connect!\n"); }
        return 0;
        //exit("connection fail: ".$errnum." ".$errstr."\n") ;
    } else {
        $passmd5 = md5($pass.$passedkey);
        if($debug==1) { $now = date('r');  fputs($fp,"$now\tantes de fputs $exten,$pass,$passmd5\n"); }
        fputs($sk, "<msg data=\"$context|checkauth|$exten|$pass\" />\0");
        while (!feof($sk)) {
            $dati.=fgets ($sk, 10);
            if($debug==1) { $now = date('r'); fputs($fp,"$now\tduring while, response: $dati\n"); }
        }
    }
    fclose($sk);

    if($debug==1) { $now = date('r'); fputs($fp,"$now\tclosing manager connection\n"); }

    $dati=preg_replace("/{.*}/","",$dati);
    $dati=preg_replace("/[^a-z]/","",$dati);
    if($debug==1) { $now = date('r'); fputs($fp,"$now\tFinal response after trim: ($dati)\n"); }
    if($dati=="ok") {
       return 1;
    } else {
       return 0;
    }
}

function getKey($text) {
    $text = preg_replace("/}/","",$text);
    $text = trim(preg_replace("/{/","",$text));
    $partes = preg_split("/,/",$text);
    foreach($partes as $valor) {
        $valor = trim($valor);
        $pertas = preg_split("/:/",$valor);
        $pertas[0]=preg_replace("/\"/","",$pertas[0]);
        $pertas[1]=trim($pertas[1]);
        $pertas[1]=preg_replace("/\"/","",$pertas[1]);
        $devuelvo[$pertas[0]]=$pertas[1];
    }
    return $devuelvo["data"];
}

if(isset($_POST['sesvar'])) {

    $valid_vars[] = "context";
    $valid_vars[] = "contextoriginalcase";
    $valid_vars[] = "extension";
    $valid_vars[] = "readonly_phonebook";
    $valid_vars[] = "phonebook";
    $valid_vars[] = "admin";
    $valid_vars[] = "vpath";
    $valid_vars[] = "vfile";
    $valid_vars[] = "logout";
    $valid_vars[] = "key";
    $valid_vars[] = "permit";
    $valid_vars[] = "language";
    $valid_vars[] = "allowed_extensions";
    $variable= $_POST['sesvar'];
    $value   = $_POST['value'];

    if(!in_array($variable,$valid_vars)) {
        if($debug==1) { $now = date('r');  fputs($fp,"$now\tsetting $variable dissallowed as it is not in allowed list\n"); fclose($fp); }
        die('no way');
    }

    if($variable == "key") {
        if(!authReq($_POST['exten'],$_POST['pass'],$_POST['value'])) {
            if($debug==1) { $now = date('r');  fputs($fp,"$now\tBad authentication. Exiting...\n"); fclose($fp); }
            die("bad auth");
        }
    } else if($variable == "context") {
       // allow set context with no auth nor key
        //session_destroy();
        $_SESSION[MYAP]=array();
        session_start();
    } else if($variable == "logout") {
        //session_destroy();
        $_SESSION[MYAP]=array();
        exit;
    } else {
        if($variable != "extension" || $variable != "language" ) {
            if(!isset($_SESSION[MYAP]['key'])) {
                if($debug==1) { $now = date('r');  fputs($fp,"$now\tsetting $variable dissallowed as no key was set\n"); fclose($fp); }
                die("no key set");
            }
        }
    }

    $_SESSION[MYAP][$variable]=$value;

    if($debug==1) { 
        $pepe = print_r($_SESSION[MYAP],1);
        fputs($fp,"sesion: $pepe\n");
    }
    if($debug==1) { $now = date('r');  fputs($fp,"$now\tsetting variable $variable = $value\n--------------------------------\n"); fclose($fp); }
    session_write_close();
    die("ok"); 
}
