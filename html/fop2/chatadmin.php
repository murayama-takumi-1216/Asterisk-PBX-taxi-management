<?php
require_once("config.php");

$allowed='no';

if(isset($_SESSION[MYAP]['admin'])) {
    $allowed='yes';
} else
if(isset($_SESSION[MYAP]['permit'])) {
    $perms = explode(",",$_SESSION[MYAP]['permit']);
    if(in_array('chatadmin',$perms)) {
        $allowed="yes";
    }
}


header("Content-Type: text/html; charset=utf-8");

$extrahead[]="
<style>

.transcript-list {
border-radius: 5px;
background-color: #eeeeee;
margin: 70px 0 20px 0;
padding: 10px;
border: 1px solid #cccccc;
}

.transcript-list li.relative_time {
list-style-type: none;
margin-bottom: 10px;
font-style: italic;
text-shadow: 0px 1px 1px white;
font-weight: bold;
text-align: center;
color: #d65129;
}

.transcript-list li.transcript:hover {
background-color: white;
box-shadow: 0px 0px 3px rgba(0, 0, 0, 0.8);
border: 1px solid white;
}

.transcript-list li.transcript .transcript_wrapper {
position: relative;
overflow: hidden;
width: 850px;
}

.transcript-list li.transcript {
list-style-type: none;
margin-bottom: 7px;
padding: 10px;
border-radius: 3px;
padding-left: 10px;
border: 1px solid #cccccc;
overflow: hidden;
clear: both;
}

.transcript_wrapper:before {
        position:absolute;
        font-family: FontAwesome;
        top:0;
        left:0px;
        content: '\f086'; 
}
.transcript_wrapper { padding-left:25px; }


.transcript-list a {
text-decoration: none;
color: #555;
}

th, td, caption {
float: none !important;
text-align: left;
font-weight: normal;
vertical-align: middle;
padding: 4px 10px 4px 5px;
}

table#messages {
border: 1px solid #edefef !important;
color: #2a3c43;
font-family: Helvetica, Arial, Sans-serif;
overflow: hidden;
table-layout: fixed;
width: 100%;
}

table#messages td.message {
font-size: 1.2em;
word-wrap: none;
}

table#messages td.author {
overflow: hidden;
text-overflow: ellipsis;
font-weight: bold;
font-size: 1.1em;
padding-right: 25px;
text-align: right;
width: 90px;
}

td.time {
color: #8f999b;
}

table#messages tr.chatfrom {
background-color: white;
}

table#messages tr.chatto {
background-color: #edefef;
}

tr.chatto td.author {
color: #d65129;
}

ul.details {
margin: 10px 0px;
white-space: nowrap;
overflow: hidden;
background-color: #B7E5A5;
padding: 5px;
}

ul.details li {
display: inline;
padding: 2px 5px;
font-size: 1.15em;
color: #8f999b;
}

</style>
";
require_once("config.php");
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-us" lang="en-us" >
<head>
<?php
if(isset($page_title)) { 
    echo "    <title>$page_title</title>\n"; 
} else {
    echo "    <title>".TITLE."</title>\n"; 
}

if($allowed=="no") {
   echo "<meta http-equiv=\"refresh\" content=\"5\" >\n";
}
?>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <meta http-equiv="imagetoolbar" content="false"/>
    <meta name="MSSmartTagsPreventParsing" content="true"/>
    <meta name="description" content=""/>
    <meta name="keywords" content=""/>

    <link media="screen" rel="stylesheet" type="text/css" href="css/bootstrap.min.css"        >
    <!--link media="screen" rel="stylesheet" type="text/css" href="css/operator.css"             -->
    <link media="screen" rel="stylesheet" type="text/css" href="css/chat.css"                 >
    <link media="screen" rel="stylesheet" type="text/css" href="css/vmail.css"                >
    <link media="screen" rel="stylesheet" type="text/css" href="css/alertify.core.css"        >
    <link media="screen" rel="stylesheet" type="text/css" href="css/alertify.bootstrap3.css"  >
    <link media="screen" rel="stylesheet" type="text/css" href="css/jquery.contextMenu.css"   >
    <link media="screen" rel="stylesheet" type="text/css" href="css/typeahead.css"            >
    <link media="screen" rel="stylesheet" type="text/css" href="css/jquery-ui.css"            >
    <link media="screen" rel="stylesheet" type="text/css" href="css/gridstack.css"            >
    <link media="screen" rel="stylesheet" type="text/css" href="css/bootstrap-switch.min.css" >
    <link media="screen" rel="stylesheet" type="text/css" href="css/bootstrap-select.css"     >
    <link media="screen" rel="stylesheet" type="text/css" href="css/flags.css"                >
    <link media="screen" rel="stylesheet" type="text/css" href="css/dbgrid.css"               >
    <link media="screen" rel="stylesheet" type="text/css" href="css/animate.css"              >
    <link media="screen" rel="stylesheet" type="text/css" href="css/font-awesome.min.css"     >

    <script type="text/javascript" src="js/jquery-1.11.3.min.js"           ></script>
    <script type="text/javascript" src="js/alertify.min.js"                ></script>
    <script type="text/javascript" src="js/bootstrap.min.js"               ></script>

<?php
if(isset($extrahead)) {
    foreach($extrahead as $bloque) {
        echo "$bloque";
    }
}
?>
</head>
<body>
<?php

if($allowed <> "yes") {

    if(!isset($_SESSION[MYAP]['retries'])) {
        $_SESSION[MYAP]['retries']=1;
    } else {
        $_SESSION[MYAP]['retries']++;
    }

   if(!isset($SQLITEDB)) {
       $SQLITEDB="/usr/local/fop2/fop2settings.db";
   }
 
   echo "<div class='container-fluid text-center'><br/>";

   if($_SESSION[MYAP]['retries']>10) {
       echo "<h3 class='animated tada'>You do not have permissions to access this resource</h3>";
       echo "<br/><br/><btn class='btn btn-default' onclick='javascript:window.location.reload();'>Refresh</button>";
   } else {
       echo "<h3>Please wait...</h3>";
   }
   echo "</div>";
   die();
}

$db2 = new dbcon('sqlite:'.$SQLITEDB);

$result = $db2->consulta("SELECT id,datetime(date,'localtime') As date,extenfrom,extento,context,chat FROM chatlog  WHERE 1=1 ORDER BY date ASC");

while($row = $db2->fetch_assoc($result)) { 

    if(!isset($date)) { $date=$row['date']; $interval=5001;}

    $datetime1 = strtotime($date);
    $datetime2 = strtotime($row['date']);
    $interval = $datetime2 - $datetime1;

    $from = $row['extenfrom'];
    $to   = $row['extento'];
    $date = $row['date'];

    if(!isset($cont[$to."-".$from])) { $cont[$to."-".$from]=$datetime2; }
    if($interval>5000) {
        $cont[$to."-".$from]=$datetime2;
    }

    if(!isset($chat[$to."-".$from][$cont[$to."-".$from]])) { $chat[$to."-".$from][$cont[$to."-".$from]] = array(); }
    array_push($chat[$to."-".$from][$cont[$to."-".$from]],$row['id']);

    if(!isset($cont[$from."-".$to])) { $cont[$from."-".$to]=$datetime2; }
    if($interval>5000) {
        $cont[$from."-".$to]=$datetime2;
    }

    if(!isset($chat[$from."-".$to][$cont[$from."-".$to]])) { $chat[$from."-".$to][$cont[$from."-".$to]] = array(); }
    array_push($chat[$from."-".$to][$cont[$from."-".$to]],$row['id']);

}

// pasada para remover duplicados to-from   from-to
foreach($chat as $key=>$datos) {
    list($to,$from) = preg_split("/-/",$key,2);
    foreach($datos as $tstamp=>$ids) {
        if(!isset($allchats[$tstamp]["$to-$from-$tstamp"])) {
            if(!isset($allchats[$tstamp]["$from-$to-$tstamp"])) {
                $allchats[$tstamp]["$to-$from-$tstamp"]=$ids;
            }
        }
    }
}

$chat=array();
$allsortchats = $allchats;
krsort($allsortchats);
$allchats=array();
foreach($allsortchats as $tstamp=>$pepe) {
    foreach($pepe as $key=>$vals) {
        $allchats[$key]=$vals;
    }
}

$query = isset($_REQUEST['query'])?$_REQUEST['query']:'';
if($query<>'') { $morepars="&query=$query"; } else { $morepars=''; }
$position = isset($_REQUEST['position'])?$_REQUEST['position']:1;
$position = intval($position);

$per_page = 5;

//echo "<pre>";
//print_r($allchats);
//echo "</pre>";

$locales = array(
    "ca" => "ca_CA.UTF-8",
    "cr" => "cr_CR.UTF-8",
    "da" => "da_DA.UTF-8",
    "de" => "de_DE.UTF-8",
    "he" => "he_HE.UTF-8",
    "el" => "el_EL.UTF-8",
    "en" => "en_EN.UTF-8",
    "es" => "es_ES.UTF-8",
    "fr_FR" => "fr_FR.UTF-8",
    "hu" => "hu_HU.UTF-8",
    "it" => "it_IT.UTF-8",
    "nl" => "nl_NL.UTF-8",
    "pl" => "pl_PL.UTF-8",
    "pt_BR" => "pt_BR.UTF-8",
    "ru" => "ru_RU.UTF-8",
    "se" => "se_SE.UTF-8",
    "tr" => "tr_TR.UTF-8",
    "uk_UA" => "uk_UA.UTF-8",
    "zh" => "zh_ZH.UTF-8"
);

setlocale(LC_TIME, $locales[$mylang]);

if(!isset($_REQUEST['view'])) {


echo "<nav class='navbar navbar-default navbar-fixed-top'>
  <div class='container-fluid'>
      <form class='navbar-form navbar-right' role='search'>
        <div class='form-group'>
          <input type='text' class='form-control' placeholder='".trans('Search')."' name='query' id='search' value='$query'>
        </div>
        <button type='submit' class='btn btn-default'>".trans('Search')."</button>
      </form>
  </div>
</nav>";

    echo "<div class='container-fluid'>";
    echo "<div class='row'>";
    echo "<div class='col-lg-12'>";
    echo "<ul class='transcript-list'>\n";

    $totalcount = 0;
    $rowcount = 0;
    $last_elapsed = "";

 
   $limit = $position + $per_page ;

    foreach($allchats as $key=>$ids) {
        list($to,$from,$tstamp) = preg_split("/-/",$key,3);

        $totalcount++; 
        //echo "total $totalcount<br>";

        $textids = implode(",",$ids);
        $result = $db2->consulta("select chat from chatlog where id in ($textids)");
        $total_words=0;

        if($query<>'') {
            $match=0;
        } else { 
            $match=1; 
        }

        if(preg_match("/$query/",$to)) { $match=1; }
        if(preg_match("/$query/",$from)) { $match=1; }

        while($row = $db2->fetch_assoc($result)) {
            $words = str_word_count($row['chat']);
            $total_words += $words;
            if($query<>'') {
                if(preg_match("/$query/i",$row['chat'])) {
                    $match=1;
                }
            }
        }

        $show_next=0;

        if($match==1) {

            $print_date = strftime('%A, %d de %B de %Y, %H:%M:%S', $tstamp);

            $rowcount++;
            if($rowcount>$limit) { /*echo "skip per page $rowcount $limit<br>";*/ $show_next=1; break; }
            if($rowcount<$position) { /*echo "skip before $rowcount $position<br>";*/ continue; }
            $elapsed = ago($tstamp);
            if($last_elapsed <> $elapsed) {
                echo "<li class='relative_time'>".$elapsed."</li>";
            }
            $last_elapsed=$elapsed;

            echo "<a href='chatadmin.php?view=$to-$from-$tstamp&words=$total_words{$morepars}&position=$position'>";
            echo "<li class='dialog transcript'><div class='transcript_wrapper'>";
            echo sprintf(trans("conversation between <strong>%s</strong> and <strong>%s</strong>"),$to,$from);
            echo " - $print_date ";
            echo sprintf(trans("%d words"),$total_words);
            echo "</div></li></a>"; 
        }
    }
    echo "</ul>";
    echo "</div></div>";

    $prev_page = $position - $per_page - 1;

    echo "<div class='row'>";
    echo "<div class='col-lg-12'>";
    echo "<div class='pull-right'>";
    if($position>1) {
        echo "<a class='btn btn-default' href='?position=$prev_page{$morepars}'>".trans("Previous Page")."</a>";
    }
    if($show_next==1) {
        if($position > 1) { echo " &nbsp; "; }
        echo "<a class='btn btn-default' href='?position=$rowcount{$morepars}'>".trans("Next Page")."</a>";
    }
    echo "<div>";
    echo "<div>";
    echo "<div>";

} else {

    echo "<div class='container-fluid'>";
    echo "<div class='row'>";
    echo "<div class='col-lg-12'>";

    echo "<a href='chatadmin.php?position=$position{$morepars}'>← ".trans('Back to All Transcripts')."</a><br/>";
    echo "<ul class='details'>";
    $total_words = $_REQUEST['words'];
    foreach($allchats as $key=>$ids) {
        list($to,$from,$tstamp) = preg_split("/-/",$key,3);
        if($key==$_REQUEST['view']) {
            //$dateprint = date('Y-m-d H:i:s',$tstamp);
            $dateprint = strftime('%A, %d de %B de %Y, %H:%M:%S', $tstamp);
            //echo "<li>Chat between <strong>$to</strong> and <strong>$from</strong> for $total_words words</li>";
            echo "<li>".sprintf(trans("conversation between <strong>%s</strong> and <strong>%s</strong>"),$to,$from);
            echo "(".sprintf(trans("%d words"),$total_words).")</li>";
            echo "<li>$dateprint</li>";
            echo "</ul>";
            echo "<table class='table' id='messages'>";

            $textids = implode(",",$ids);
            $result =  $db2->consulta("select extenfrom,extento,chat,datetime(date,'localtime') As date from chatlog where id in ($textids) ORDER BY id");
            while($row = $db2->fetch_assoc($result)) {
                if($row['extenfrom']==$to) { $trclass='chatto'; } else { $trclass='chatfrom'; }
                echo "<tr class='$trclass'><td class='author'>".$row['extenfrom']."</td><td class='message'>".$row['chat']."</td><td class='time' width='200px'>".$row['date']."</td></tr>";
            }
            break;
        }
    }
    echo "</table>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
}

function ago($i){
    $m = time()-$i; $o=trans('just now');
    $t = array(
  trans('year')=>31556926,
  trans('month')=>2629744,
  trans('week')=>604800,
  trans('day')=>86400,
  trans('hour')=>3600,
  trans('minute')=>60,
  trans('second')=>1);
    foreach($t as $u=>$s){
        if($s<=$m){
           $v=floor($m/$s); 
           $o=sprintf(trans("about %d %s ago"),$v,$u.($v==1?'':'s'));
           break;
        }
    }
    return "$o";
}

