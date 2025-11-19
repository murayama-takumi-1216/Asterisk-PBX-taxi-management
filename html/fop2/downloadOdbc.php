<?php

define("MYAP",  "FOP2");
define("TITLE", "Flash Operator Panel 2");
if(isset($_SERVER['PATH_INFO'])) {
    define("SELF",  substr($_SERVER['PHP_SELF'], 0, (strlen($_SERVER['PHP_SELF']) - @strlen($_SERVER['PATH_INFO']))));
} else {
    define("SELF",  $_SERVER['PHP_SELF']);
}

function range_download($file) {

    $fp = @fopen($file, 'rb');

    $size   = filesize($file); // File size
    $length = $size;           // Content length
    $start  = 0;               // Start byte
    $end    = $size - 1;       // End byte
    // Now that we've gotten so far without errors we send the accept range header
    /* At the moment we only support single ranges.
     * Multiple ranges requires some more work to ensure it works correctly
     * and comply with the spesifications: http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
     *
     * Multirange support annouces itself with:
     * header('Accept-Ranges: bytes');
     *
     * Multirange content must be sent with multipart/byteranges mediatype,
     * (mediatype = mimetype)
     * as well as a boundry header to indicate the various chunks of data.
     */
    header("Accept-Ranges: 0-$length");
    // header('Accept-Ranges: bytes');
    // multipart/byteranges
    // http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
    if (isset($_SERVER['HTTP_RANGE'])) {

        $c_start = $start;
        $c_end   = $end;
        // Extract the range string
        list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
        // Make sure the client hasn't sent us a multibyte range
        if (strpos($range, ',') !== false) {

            // (?) Shoud this be issued here, or should the first
            // range be used? Or should the header be ignored and
            // we output the whole content?
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $start-$end/$size");
            // (?) Echo some info to the client?
            exit;
        }
        // If the range starts with an '-' we start from the beginning
        // If not, we forward the file pointer
        // And make sure to get the end byte if spesified
        if ($range0 == '-') {

            // The n-number of the last bytes is requested
            $c_start = $size - substr($range, 1);
        }
        else {

            $range  = explode('-', $range);
            $c_start = $range[0];
            $c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
        }
        /* Check the range and make sure it's treated according to the specs.
         * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
         */
        // End bytes can not be larger than $end.
        $c_end = ($c_end > $end) ? $end : $c_end;
        // Validate the requested range and return an error if it's not correct.
        if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {

            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $start-$end/$size");
            // (?) Echo some info to the client?
            exit;
        }
        $start  = $c_start;
        $end    = $c_end;
        $length = $end - $start + 1; // Calculate new content length
        fseek($fp, $start);
        header('HTTP/1.1 206 Partial Content');
    }
    // Notify the client the byte range we'll be outputting
    header("Content-Range: bytes $start-$end/$size");
    header("Content-Length: $length");

    // Start buffered download
    $buffer = 1024 * 8;
    while(!feof($fp) && ($p = ftell($fp)) <= $end) {

        if ($p + $buffer > $end) {

            // In case we're only outputtin a chunk, make sure we don't
            // read past the length
            $buffer = $end - $p + 1;
        }
        set_time_limit(0); // Reset time limit for big files
        echo fread($fp, $buffer);
        flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
    }

    fclose($fp);

}

// Session start
session_start();

//$_SESSION[MYAP]['vpath']='dbi:ODBC:asterisk!voicemessages';

if(!isset($_REQUEST['file'])) {
    die("No filename specified");
}

list ($getid,$file_name) = preg_split("/!/",$_REQUEST['file'],2);

$realid=md5($_SESSION[MYAP]['key']);

if($realid<>$getid) {
    die("invalid id");
}

// required for IE, otherwise Content-disposition is ignored
if(ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', 'Off');
}

if(!isset($_SESSION[MYAP]['vpath'])) {
    die("no way 1");
} else {
    $vpath = $_SESSION[MYAP]['vpath'];
    $partes = preg_split("/!/",$_SESSION[MYAP]['vpath']);
    if(!isset($partes[1])) {
       $table="voicemessages";
    } else {
       $table=$partes[1];
       $vpath=$partes[0];
    }
}

$partes         = preg_split("/:/",$vpath);
$dsnname        = $partes[2];

$msgid          = substr($file_name,0,strrpos($file_name,"."));
$file_extension = strtolower(substr(strrchr($file_name,"."),1));

$msgid2 = $_SESSION[MYAP]['vfile'];
if($_SESSION[MYAP]['vfile'] <> $msgid) {
    die("no way 2 $msgid2 y $file_extension y $file ($msgid)");
}

if($file_extension<>"wav" && $file_extension<>"WAV" && $file_extension<>"gsm" && $file_extension<>"mp3" && $file_extension<>"ogg") {
    die("Only wav or gsm allowed $file_extension");
}

if($file_extension=="mp3") {
   $field="recordingmp3";
} else if ($file_extension=="ogg") {
   $field="recordingogg";
} else {
   $field="recording";
}

$link = odbc_connect($dsnname, '', '');
$sql  = "select $field from $table WHERE id='$msgid'";
$consulta = odbc_exec($link,$sql);
if(!$consulta) {
    trigger_error('[sql] exec: '.$sql, E_USER_ERROR);
}

if($file_extension=="wav") {
   $mtype="audio/wav";
} else if($file_extension=="mp3") {
   $mtype="audio/mpeg";
} else if($file_extension=="ogg") {
   $mtype="audio/ogg";
} else {
   $mtype="application/octect-stream";
}
$filename2 = "msg".$msgid.".".$file_extension;
$final_basename = basename($filename2);

//$temp = "./tmp/$final_basename";

$temp = tempnam("/tmp", "vmast");
$fp   = fopen($temp,"wb");
odbc_binmode($consulta, ODBC_BINMODE_RETURN);
odbc_longreadlen($consulta, 4096);
odbc_fetch_row($consulta);

while(($chunk = odbc_result($consulta,1))!==false) {
    fwrite($fp,$chunk);
}
fclose($fp);

$file_size=filesize($temp);

odbc_free_result($consulta);

header("Pragma: public"); // required
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false); // required for certain browsers 
header("Content-Type: $mtype");
header("Content-Disposition: attachment; filename=\"$final_basename\";" );
header("Content-Transfer-Encoding: binary");
header("Content-Length: $file_size");

if (isset($_SERVER['HTTP_RANGE']))  { // do it for any device that supports byte-ranges not only iPhone
    range_download($temp);
}
else {
    header("Content-Type: $mtype");
    header("Content-Length: ".filesize($temp));
    readfile($temp);
}

unlink($temp);
