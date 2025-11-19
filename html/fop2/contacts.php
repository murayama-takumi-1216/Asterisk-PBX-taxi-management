<?php
header("Content-Type: text/html; charset=utf-8");
require_once("config.php");

$requestaction = isset($_REQUEST['action'])?$_REQUEST['action']:'list';

$ris = $db->consulta("SET NAMES utf8");

// Session Variables
$context   = isset($_SESSION[MYAP]['context'])?$_SESSION[MYAP]['context']:'';
$extension = isset($_SESSION[MYAP]['extension'])?$_SESSION[MYAP]['extension']:-1;
$admin     = isset($_SESSION[MYAP]['admin'])?$_SESSION[MYAP]['admin']:0;
$allowed   = isset($_SESSION[MYAP]['phonebook'])?$_SESSION[MYAP]['phonebook']:'no';

if(isset($_SESSION[MYAP]['permit'])) {
    $permits = preg_split("/,/",$_SESSION[MYAP]['permit']);
    if($allowed=='no' && (in_array('phonebook',$permits) or in_array('readonly_phonebook',$permits))) {
       $allowed='yes';
    }
}

$addcontext=$context;
if($context<>'') {
    $addcontext=$context."_";
}

// Sanitize Input
$addcontext = preg_replace("/\.[\.]+/", "", $addcontext);
$addcontext = preg_replace("/^[\/]+/", "", $addcontext);
$addcontext = preg_replace("/^[A-Za-z][:\|][\/]?/", "", $addcontext);

$userdirectory = './uploads/'.$addcontext;

if($allowed=='yes') {

    if($requestaction=='importcontact') {
        $url = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST'].base64_decode($_REQUEST['filename']);
        $vcard_data = file_get_contents($url);
        if(isset($_REQUEST['photo'])) {

            // Add PHOTO: line to vcard
            $lines = preg_split("/\r\n|\n|\r/", $vcard_data);
            $last = array_pop($lines);
            $fotoline="PHOTO:".base64_decode($_REQUEST['photo']);

                   $plineas = str_split($fotoline,75);
                   $i=0;
                   foreach($plineas as $linea) {
                       if($i==0) {
                           $lines[]="$linea";
                       } else {
                           $lines[]=" $linea";
                       }
                       $i++;
                   }


            $lines[]=$last;
            $vcard_data = implode("\n",$lines);
        }
        $tmpfile = "/tmp/".basename($url);
        file_put_contents($tmpfile,$vcard_data);
        process_vcard($tmpfile);
        die($vcard_data);
    } else if($requestaction=='gettags') {
        $query = "SELECT id,tag,color FROM visual_phonebook_tags WHERE context='%s'";
        $res   = $db->consulta($query,$context);
        $cat = array();
        while($row = $db->fetch_row($res)) {
            if($row[2]=='') $row[2]='inherit';
            $cat[$row[0]] = array("text"=>$row[1],"color"=>$row[2]);
        }
        echo json_encode($cat);
        die();

    } else if($requestaction=='addtag') {
        $newtag = $_REQUEST['tag'];
        $query = "INSERT INTO visual_phonebook_tags (context,tag) VALUES ('%s','%s')";
        $res = $db->consulta($query,array($context,$newtag));
        $lastid = $db->insert_id($res);
        $ret = array("result"=>"ok","tag"=>$newtag,"id"=>$lastid);
        echo json_encode($ret);
        die();

    } else if($requestaction=='edit') {

        header("Content-type: application/json; charset=utf-8");
        $res  = $db->consulta("SELECT * FROM visual_phonebook WHERE id='%s'",$_REQUEST['id']);
        $data = $db->fetch_assoc($res);

        if($data['picture']<>'' && substr($data['picture'],0,4)!='http') {
            if(strpos($data['picture'],'/')===false) {
                $data['picture']=$userdirectory.$data['picture'];
            }
        }

        $res   = $db->consulta("SELECT type,number FROM visual_phonebook_phones WHERE contact_id='%s'",$_REQUEST['id']);
        $data2 = array();
        while($row=$db->fetch_assoc($res)) { $data2[]=$row; }
        $data['phones']=$data2;

        $res   = $db->consulta("SELECT tag_id FROM visual_phonebook_tags_contacts WHERE contact_id='%s'",$_REQUEST['id']);
        $data3 = array();
        while($row=$db->fetch_assoc($res)) { $data3[]=$row['tag_id']; }
        $data['tags']=$data3;

        echo json_encode($data);
        die();
    } else if($requestaction=='settags') {
        // Set tags for multiple selection
        $tag = $_REQUEST['tag'];
        foreach($_REQUEST['contact'] as $contact) {
            $partes = preg_split("/_/",$contact);
            $contactid = $partes[0];
            $db->consulta("DELETE FROM visual_phonebook_tags_contacts WHERE contact_id='%d'",array($contactid));
            $db->consulta("INSERT INTO visual_phonebook_tags_contacts (contact_id,tag_id) VALUES ('%s','%s')",array($contactid,$tag));
        }
        echo json_encode(array("result"=>"success"));
        exit;
    } else if($requestaction=='save' || $requestaction=='insert') {

        header("Content-type: application/json; charset=utf-8");

        $firstname      = $_REQUEST['firstname'];
        $lastname       = $_REQUEST['lastname'];
        $company        = $_REQUEST['company'];
        $picture        = $_REQUEST['picture'];
        $address        = $_REQUEST['address'];
        $email          = $_REQUEST['email'];
        $id             = $_REQUEST['id'];
        $position       = $_REQUEST['position'];
        $tags           = $_REQUEST['tags'];
        $identification = $_REQUEST['identification'];
        $instagram      = $_REQUEST['instagram'];
        $telegram       = $_REQUEST['telegram'];
        $privvalue      = isset($_REQUEST['private'][0])?'yes':'no';

        if($requestaction=='save') {
            $db->consulta('UPDATE visual_phonebook SET firstname="%s",lastname="%s",company="%s",address="%s",email="%s",private="%s",position="%s",identification="%s",instagram="%s",telegram="%s" WHERE id=%s',array($firstname,$lastname,$company,$address,$email,$privvalue,$position,$identification,$instagram,$telegram,$id));
            $db->consulta('DELETE FROM visual_phonebook_phones WHERE contact_id=%d',$id);
            $db->consulta('DELETE FROM visual_phonebook_tags_contacts WHERE contact_id=%d',$id);
            foreach($_REQUEST['phonetype'] as $idx=>$type) {
                $db->consulta("INSERT INTO visual_phonebook_phones (contact_id,type,number) VALUES ('%d','%s','%s')",array($id,$type,$_REQUEST['phonenumber'][$idx]));
            }
            foreach($tags as $idx=>$val_cat) {
                $db->consulta("INSERT INTO visual_phonebook_tags_contacts (contact_id,tag_id) VALUES ('%s','%s')",array($id,intval($val_cat)));
            }
        } else {
            $res = $db->consulta('INSERT INTO visual_phonebook (firstname,lastname,company,address,email,owner,private,context,position,identification,instagram,telegram) VALUES ("%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s")',array($firstname,$lastname,$company,$address,$email,$extension,$privvalue,$context,$position,$identification,$instagram,$telegram));
            if(!$res) {
                die('{"success":false,"info":"'.$db->error().'"}');
            }
            $id = $db->insert_id($res);
            $res = $db->consulta('UPDATE visual_phonebook SET picture="%s" WHERE id=%s',array($id."-picture.png",$id));
            foreach($_REQUEST['phonetype'] as $idx=>$type) {
                $db->consulta("INSERT INTO visual_phonebook_phones (contact_id,type,number) VALUES ('%d','%s','%s')",array($id,$type,$_REQUEST['phonenumber'][$idx]));
            }
            foreach($tags as $idx=>$val_cat) {
                $db->consulta("INSERT INTO visual_phonebook_tags_contacts (contact_id,tag_id) VALUES ('%s','%s')",array($id,intval($val_cat)));
            }
        }

        // save record/image
        if($_REQUEST['image-data']<>'') {

            list($nada,$image_data) = preg_split("/,/",$_REQUEST['image-data']);
            $decoded_image = base64_decode($image_data);
            $fp=fopen($userdirectory.$id."-picture.png","w");
            fputs($fp,$decoded_image);
            fclose($fp);

            if($requestaction=='save') {
                // update picture field only if image is set, otherwise keep the one on dB for backwards compatibility
                $db->consulta('UPDATE visual_phonebook SET picture="%s" WHERE id=%s', array("$id-picture.png",$id));
            }

        } else {
            $picture = '';
            if($requestaction=='save') {
                // update picture field only if image is set, otherwise keep the one on dB for backwards compatibility
                $db->consulta('UPDATE visual_phonebook SET picture="" WHERE id=%s', array($id));
                if(is_file($userdirectory.$id."-picture.png")) {
                    unlink($userdirectory.$id."-picture.png");
                }
            }


        }
        die('{"success":true,"info":"'.$id.'"}');

    } else if($requestaction=='exists') {

         $query = "SELECT id FROM visual_phonebook LEFT JOIN visual_phonebook_phones ON id=contact_id ";
         $query.= "WHERE context='$context' AND (owner='$extension' OR (owner<>'$extension' AND private='no')) AND (number = '".$_REQUEST['number']."' OR email = '".$_REQUEST['number']."') LIMIT 1";
         $res = $db->consulta($query);
         $return=0;
         while($row=$db->fetch_assoc($res)) {
             $return = $row['id'];
         }
         echo "{\"id\":$return}";
         die();

    } else if($requestaction=='delete') {

        $id = $_REQUEST['id'];
        $result = $db->select("picture","visual_phonebook","","id=$id");
        $imagen = $result[0]['picture'];
        if(is_file("{$userdirectory}{$imagen}")) {
            unlink("{$userdirectory}{$imagen}");
        }
        $db->consulta("DELETE FROM visual_phonebook WHERE id=%s",$id);
        echo "{\"action\":\"delete\",\"id\":\"$id\"}";
        die();

    } else if($requestaction=='deletenumber') {
        $id = $_REQUEST['id'];
        $number = base64_decode($_REQUEST['number']);
        $query = "DELETE FROM visual_phonebook_phones WHERE contact_id='%s' AND number='%s'";
        $res = $db->consulta($query,array($id,$number));
        $query = "select id from visual_phonebook left join visual_phonebook_phones on id=contact_id where number is null";
        $res = $db->consulta($query);
        while($row=$db->fetch_assoc($res)) {
             $query = "DELETE FROM visual_phonebook WHERE id='%d'";
             $db->consulta($query,$row['id']);
        }
        exit;
    } else if($requestaction=='getnotes') {

        $contact_id = $_REQUEST['id'];
        $page       = $_REQUEST['page'];
        $perpage    = $_REQUEST['perpage'];

        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
        if(false === $page) {
            $page = 1;
        }

        $perpage = filter_input(INPUT_GET, 'perpage', FILTER_VALIDATE_INT);
        if(false === $page) {
            $perpage = 10;
        }

        echo '{"success":true,"info":"'.getnotes($contact_id,$page,$perpage).'"}';
        exit;

    } else if($requestaction=='deletenote') {

        $note_id    = $_REQUEST['id'];
        $contact_id = $_REQUEST['contactid'];
        $page       = $_REQUEST['page'];
        $perpage    = $_REQUEST['perpage'];
        $query = "DELETE FROM fop2_notes WHERE id='%d' AND contact_id='%s'";
        $res = $db->consulta($query,array($note_id,$contact_id));
        if(!$res) {
            echo '{"success":false,"info":"Could not delete note"}';
            exit;
        } else {
            echo '{"success":true,"info":"'.getnotes($contact_id,$page,$perpage).'"}';
            exit;
        }

    } else if($requestaction=='editnote') {

        $note_id    = $_REQUEST['id'];
        $contact_id = $_REQUEST['contactid'];
        $page       = $_REQUEST['page'];
        $perpage    = $_REQUEST['perpage'];
        $query      = "SELECT note FROM fop2_notes WHERE id='%s' AND contact_id='%s'";
        $res = $db->consulta($query,array($note_id,$contact_id));
        if(!$res) {
            echo '{"success":false,"info":"Could not retrieve note"}';
            exit;
        } else {
            $row = $db->fetch_assoc($res);
            echo '{"success":true,"info":"'.base64_encode($row['note']).'"}';
            exit;
        }

    } else if($requestaction=='savenote') {

        $note_id = intval($_REQUEST['noteid']);
        $author     = $_REQUEST['author'];
        $contact_id = intval($_REQUEST['id']);
        $note = $_REQUEST['contactnote'];
        $page       = intval($_REQUEST['page']);
        $query = "UPDATE fop2_notes SET note='%s',author='%s' WHERE id='%d'";

        $res = $db->consulta($query,array($note,$author,$note_id));
        if(!$res) {
            echo'{"success":false,"info":"Could not insert note"}';
            exit;
        }
        echo '{"success":true,"info":"'.getnotes($contact_id,$page,10).'"}';
        exit;

    } else if($requestaction=='newnote') {

        $contact_id = intval($_REQUEST['id']);
        $author     = $_REQUEST['author'];
        $note       = $_REQUEST['contactnote'];
        $page       = intval($_REQUEST['page']);
        $query = "INSERT INTO fop2_notes (contact_id,author,note) VALUES ('%d','%s','%s')";
        $res = $db->consulta($query,array($contact_id,$author,$note));
        if(!$res) {
            echo'{"success":false,"info":"Could not insert note"}';
            exit;
        }
        echo '{"success":true,"info":"'.getnotes($contact_id,1,10).'"}';
        exit;
    }
}

$notallowed = '';
if($extension == -1 || $allowed<>'yes') {

    if(!isset($_SESSION[MYAP]['retries'])) {
        $_SESSION[MYAP]['retries']=1;
    } else {
        $_SESSION[MYAP]['retries']++;
    }

    // If no session extension is set, kick the user out
    if($_SESSION[MYAP]['retries']>10) {
        $notallowed = "<br><br><div class='container-fluid text-center'><br/><h3 class='animated tada'>".trans('You do not have permissions to access this resource')."</h3></div>";
    } else {
        $notallowed = '<br><br><div class="container-fluid text-center"><br/>';
        $notallowed.= '<svg class="circular"><circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="4" stroke-miterlimit="10"/></svg>';
        $notallowed.= '</div>';
    }
}

// START DATABASE/TABLES SETUP AND UPGRADE
$res = $db->consulta("SHOW TABLES LIKE 'visual_phonebook'");
if($db->num_rows($res)==0) {
    $querycreate = "CREATE TABLE `visual_phonebook` (
      `id` int not null auto_increment,
      `firstname` varchar(50) default null,
      `lastname`  varchar(50) default null,
      `company`   varchar(100) default null,
      `owner`     varchar(50) default '',
      `private`   enum('yes','no') default 'no',
      `picture`   varchar(250) default null,
      `email`     varchar(150) default '',
      `instagram` varchar(64) default '',
      `telegram`  varchar(64) default '',
      `address`   varchar(150) default '',
      `position`  varchar(100) default '',
      `identification` varchar(100) default '',
      `context`   varchar(150) default '',
      primary key  (`id`),
      UNIQUE KEY `fullname` (`firstname`,`lastname`),
      key `search` (`firstname`,`lastname`,`company`),
      KEY `ide` (`identification`)
    ) ENGINE=InnoDB default charset=utf8mb4";
    $ris = $db->consulta($querycreate);
    if(!$ris) {
        echo "<h1><br/>could not connect/create the phonebook table.<br/><br/>please verify your database credentials in config.php.</h1>";
        die();
    }
}

// Update tables for older versions
$alldbfields   = array();
$alldbfields['visual_phonebook']['email']= "ALTER TABLE `visual_phonebook` ADD `email` varchar(150) DEFAULT '' AFTER phone2";
$alldbfields['visual_phonebook']['address']= "ALTER TABLE `visual_phonebook` ADD `address` varchar(150) DEFAULT '' AFTER email";
$alldbfields['visual_phonebook']['identification']= "ALTER TABLE `visual_phonebook` ADD `identification` varchar(100) DEFAULT '' AFTER address";
$alldbfields['visual_phonebook']['position']= "ALTER TABLE `visual_phonebook` ADD `position` varchar(100) DEFAULT '' AFTER address";
$alldbfields['visual_phonebook']['instagram']= "ALTER TABLE `visual_phonebook` ADD `instagram` varchar(64) DEFAULT '' AFTER email";
$alldbfields['visual_phonebook']['telegram']= "ALTER TABLE `visual_phonebook` ADD `telegram` varchar(64) DEFAULT '' AFTER instagram";

foreach($alldbfields as $table => $rest) {

    $res = $db->consulta("DESC $table");

    if($res) {

        // table exists, check if we need to add /update_fields to it
        $existdbfield = array();
        while($row = $db->fetch_assoc($res)) {
            $campo = $row['Field'];
            $existdbfield[$campo]=1;
        }

        foreach($rest as $campo=>$query) {
            if(!isset($existdbfield[$campo])) {
                $db->consulta($query);
                $updated_field[$table][$campo]=1;
            }
        }
    }
}

$res = $db->consulta("DESC $table picture");
while($row = $db->fetch_assoc($res)) {
    if($row['Type']!='varchar(250)') {
        $db->consulta("ALTER TABLE `visual_phonebook` CHANGE picture picture varchar(250)");
    }
};

$doindex1=1;
$doindex2=1;
$res = $db->consulta("show index from visual_phonebook");
if($res) {
    while($row = $db->fetch_assoc($res)) {
        if($row['Key_name']=='fullname') {
            $doindex1=0;
        }
        if($row['Key_name']=='ide') {
            $doindex2=0;
        }
    }
}
if($doindex==1) {
    $db->consulta("ALTER TABLE visual_phonebook ADD UNIQUE fullname(firstname,lastname)");
}
if($doindex2==1) {
    $db->consulta("ALTER TABLE visual_phonebook ADD INDEX ide(identification)");
}

$res = $db->consulta("SHOW TABLES LIKE 'fop2_notes'");
if($db->num_rows($res)==0) {
    $querycreate = "CREATE TABLE IF NOT EXISTS fop2_notes ( id int(11) auto_increment primary key not null, contact_id int(11), datetime timestamp, note text, author varchar(30), INDEX cid(contact_id), INDEX auth(author))";
    $ris = $db->consulta($querycreate);
    if(!$ris) {
        echo "<h1><br/>could not connect/create the phonebook notes table.<br/><br/>please verify your database credentials in config.php.</h1>";
        die();
    }
}

$res = $db->consulta("SHOW TABLES LIKE 'visual_phonebook_phones'");
if($db->num_rows($res)==0) {

    $query = "ALTER TABLE visual_phonebook ENGINE=InnoDB";
    $ris = $db->consulta($query);

    $querycreate = "CREATE TABLE `visual_phonebook_phones` (
      `contact_id` int ,
      `type` enum('home','work','cell','fax','other','main','whatsapp','sms') DEFAULT 'other',
      `number` varchar(50) DEFAULT NULL,
      INDEX cid (contact_id),
      FOREIGN KEY(contact_id) REFERENCES visual_phonebook(id) ON DELETE CASCADE
    ) ENGINE InnoDB default charset=utf8mb4";

    $ris = $db->consulta($querycreate);
    if(!$ris) {
        echo "<h1><br/>could not connect/create the phonebook table.<br/><br/>please verify your database credentials in config.php.</h1>";
        die();
    } else {
        $convert=0;
        $res = $db->consulta("DESC visual_phonebook");
        while($row = $db->fetch_assoc($res)) {
            $campo = $row['Field'];
            if($campo=='phone1') {
              $convert=1;
            }
        }
        if($convert==1) {
            $res = $db->consulta("select id,phone1 from visual_phonebook where phone1<>''");
            while($row=$db->fetch_assoc($res)) {
                $query = "INSERT INTO visual_phonebook_phones (contact_id,type,number) VALUES ('%d','main','%s')";
                $db->consulta($query,array($row['id'],$row['phone1']));
            }
            $res = $db->consulta("select id,phone2 from visual_phonebook where phone2<>''");
            while($row=$db->fetch_assoc($res)) {
                $query = "INSERT INTO visual_phonebook_phones (contact_id,type,number) VALUES ('%d','other','%s')";
                $db->consulta($query,array($row['id'],$row['phone2']));
            }
            $db->consulta("ALTER TABLE visual_phonebook DROP phone1");
            $db->consulta("ALTER TABLE visual_phonebook DROP phone2");
        }
    }
}

$res = $db->consulta("SHOW TABLES LIKE 'visual_phonebook_tags'");
if($db->num_rows($res)==0) {
    $querycreate = "CREATE TABLE `visual_phonebook_tags` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `context` varchar(150) DEFAULT '',
      `tag` varchar(190) DEFAULT NULL,
      `color` varchar(20) DEFAULT '',
      PRIMARY KEY (`id`),
      UNIQUE KEY `mytag` (`tag`)
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $ris = $db->consulta($querycreate);
    if(!$ris) {
        echo "<h1><br/>Could not connect/create phonebook tags table.<br/><br/>please verify your database credentials in config.php.</h1>";
        echo $db->error();
        die();
    } 
}

$res = $db->consulta("SHOW TABLES LIKE 'visual_phonebook_tags_contacts'");
if($db->num_rows($res)==0) {
    $querycreate = "CREATE TABLE `visual_phonebook_tags_contacts` (
      `contact_id` int ,
      `tag_id` int,
      INDEX conid (contact_id),
      INDEX catid (tag_id),
      FOREIGN KEY(contact_id) REFERENCES visual_phonebook(id) ON DELETE CASCADE,
      FOREIGN KEY(tag_id) REFERENCES visual_phonebook_tags(id) ON DELETE CASCADE
    ) ENGINE InnoDB default charset=utf8mb4";

    $ris = $db->consulta($querycreate);
    if(!$ris) {
        echo "<h1><br/>Could not connect/create phonebook tags relation table.<br/><br/>please verify your database credentials in config.php.</h1>";
        echo $db->error();
        die();
    } 
}

/* END DATABASE SETUP/UPGRADE */

function truncate($text, $chars = 25) {
    if (strlen($text) <= $chars) {
        return $text;
    }
    $text = $text." ";
    $text = substr($text,0,$chars);
    $text = substr($text,0,strrpos($text,' '));
    $text = $text."...";
    return $text;
}

function getnotes($contact_id,$page,$perpage) {
    global $db;
    if(!isset($page)) $page=1;
    if(!isset($perpage)) $perpage=10;
    $offset = ($page - 1) * $perpage;
    $query  = "SELECT count(*) FROM fop2_notes WHERE contact_id='%d'";
    $res = $db->consulta($query,$contact_id);
    $total_rows = $db->fetch_row($res)[0];
    $total_pages = ceil($total_rows/$perpage);
    $query  = "SELECT id,datetime,author,note FROM fop2_notes WHERE contact_id='%d' ORDER BY datetime DESC LIMIT $offset,$perpage";
    $res = $db->consulta($query,$contact_id);
    $ret = "<table class='table table-striped'><thead><tr><th style='width:150px;'>".trans("Date")."</th><th style='width:100px;'>".trans("Author")."</th><th>".trans("Note")."</th><th style='width:90px;'></th></tr></thead>";
    $ret .= "<tbody>";
    while($row = $db->fetch_assoc($res)) {
        $date = $row['datetime'];
        $gmdate = $date;
        $gmdate = str_replace(" ","T",$date);
        $gmdate .= 'Z';
        $ret.="<tr><td>";
        $ret.="<time class='timeago ttip' data-toggle='tooltip' data-placement='bottom' datetime='$gmdate'>$date</time>";
        $ret.="</td>";
        $ret.="<td>".$row['author']."</td>";
        $ret.="<td><span data-toggle='popover' data-delay='700' data-placement='bottom' data-trigger='hover' data-content='".$row['note']."'>".truncate($row['note'],200)."</td><td>";
        $ret.="<a class='btn btn-xs btn-default' style='padding-right:5px;'  data-toggle='tooltip' data-placement='top' title='".trans("Edit")."' onclick='editnote(".$row['id'].",".$contact_id.")'><i class='fa fa-pencil'></i><a>";
        $ret.="<a class='btn btn-xs btn-danger' data-toggle='tooltip' data-placement='top' title='".trans("Delete")."' onclick='deletenote(".$row['id'].",".$contact_id.")'><i class='fa fa-trash'></i><a>";
        $ret.="</td></tr>";
    }
    $ret .= "</tbody></table>";

    if($total_pages>1) {

        $ret.= '<nav class="pagination-container" style="text-align:center;"><div id="pagination-numbers">';
        if($page==1) {
            $disa1=' disabled ';
            $disa2=' disabled="disabled" ';
            $fonclick='';
            $onclick='';
        } else {
            $disa1='';
            $disa2='';
            $fonclick=' onclick="getpagenotes(1)" ';
            $onclick=' onclick="getpagenotes('.($page-1).')" ';
        }
        $ret.= "<button aria-label='First page' page-index='first' class='pagination-number $disa1' $disa2 $fonclick><i class='fa fa-angle-double-left'></i></button>";
        $ret.= "<button aria-label='Previous page' page-index='previous' class='pagination-number $disa1' $disa2 $onclick><i class='fa fa-angle-left'></i></button>";
        list($min,$max) = getPageRange($page,$total_pages);
        foreach(range($min,$max) as $a) {
            if($a==$page) { $active = 'active'; } else { $active= ''; }
            $ret.= "<button aria-label='Page $a' page-index='1' class='pagination-number $active' onclick='getpagenotes($a);'>$a</button>";
        }
        if($page==$total_pages) {
            $disa1=' disabled ';
            $disa2=' disabled="disabled" ';
            $fonclick='';
            $onclick='';
        } else {
            $disa1='';
            $disa2='';
            $fonclick=' onclick="getpagenotes('.($page+1).')" ';
            $onclick=' onclick="getpagenotes('.($total_pages).')" ';
        }
        $ret.= "<button aria-label='Next page' page-index='next' class='pagination-number $disa1' $disa2 $fonclick><i class='fa fa-angle-right'></i></button>";
        $ret.= "<button aria-label='Last page' page-index='last' class='pagination-number $disa1' $disa2 $onclick><i class='fa fa-angle-double-right'></i></button>";
        $ret.= '</div></nav>';
        $ret.= '<span class="hidden" id="note_current_page">'.$page.'</span>';
    }

    return base64_encode($ret);
}

// Import from .vcf files
function process_vcard($file) {

    global $db, $extension, $userdirectory;

    $existing_tags = array();
    $count_color = 0;
    $hex_colors = array('#2e9fe2','#bb0983','#faf113','#42c200','#00079f','#ba6800','#900402','#309981','#6100b1','#8de000','#e001cd','#70a402','#2d8b00','#d20a20','#064cc2','#a5053c');

    $query = "SELECT tag,id FROM visual_phonebook_tags";
    $res = $db->consulta($query);
    while($row=$db->fetch_row($res)) {
        $existing_tags[$row[0]]=$row[1];
    }

    $fp=fopen($file,"r");
    $lastfield='';
    while (($line = fgets($fp)) !== false) {
        $line = chop($line);
        if($line=='BEGIN:VCARD') {
             $data=array();
             $phones=array();
        }
        if(substr($line,0,1)==" ") {
            if($lastfield<>'') {
                 $data[$lastfield]=$data[$lastfield].substr($line,1);
                 //$lastfield='';
            }
        }
        if(substr($line,0,2)=="N:") {
            $milinea = preg_split("/;/",substr($line,2));
            $data['lastname']=$milinea[0];
            $data['firstname']=$milinea[1];
            $lastfield='';
        }
        if(substr($line,0,5)=="PHOTO") {
            $milinea = preg_split("/:/",$line,2);
            $data['picture']=$milinea[1];
            $lastfield='picture';
        }
        if(substr($line,0,4)=="TEL;") {
            $milinea = preg_split("/:/",substr($line,4));
            $milinea2 = strtolower(substr($milinea[0],5));
            $partes = preg_split("/;/",$milinea2,2);
            $type = $partes[0];
            $number = $milinea[1];
            $phones[] = array('type'=>$type,'number'=>$number);
        }
        if(preg_match("/ADR;TYPE/",$line)) {
            $milinea = preg_split("/:/",$line);
            $data['address']=$milinea[1];
            $lastfield='address';
        }
        if(preg_match("/EMAIL;TYPE/",$line)) {
            $milinea = preg_split("/:/",$line);
            $data['email']=$milinea[1];
            $lastfield='email';
        }
        if(preg_match("/item\d.ORG:(.*)/",$line,$matches)) {
            $data['company']=$matches[1];
            $lastfield='company';
        }
        if(preg_match("/^X-IDENTIFICATION:(.*)/",$line,$matches)) {
            $data['identification']=$matches[1];
            $lastfield='identification';
        }
        if(preg_match("/^ROLE:(.*)/",$line,$matches)) {
            $data['position']=$matches[1];
            $lastfield='position';
        }
        if(preg_match("/^CATEGORIES:(.*)/",$line,$matches)) {
            $data['tags']=preg_split("/,/",$matches[1]);
            $lastfield='tags';
        }

        if($line=='END:VCARD') {
            if(count($phones)>0) {
                if(!isset($data['company'])) $data['company']='';
                if(!isset($data['email'])) $data['email']='';
                if(!isset($data['picture'])) $data['picture']='';
                if(!isset($data['identification'])) $data['identification']='';
                if(!isset($data['position'])) $data['position']='';
                if(!isset($data['tags'])) $data['tags']=array();
                if(!isset($data['address'])) {
                    $address='';
                } else {
                    $partes = preg_split("/;/",$data['address']);
                    if(isset($partes[7])) {
                        $address = preg_replace("/\\\,/",",",$partes[7]);
                    } else {
                        $address = preg_replace("/\\\,/",",",$partes[2]);
                    }
                    $address = str_replace('\n',',',$address);
                }

                $query = "INSERT INTO visual_phonebook(firstname,lastname,company,email,picture,address,identification,position,owner) VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s')";
                $res = $db->consulta($query,array($data['firstname'],$data['lastname'],$data['company'],$data['email'],$data['picture'],$address,$data['identification'],$data['position'],$extension));
                $lastid = $db->insert_id();
                if($lastid>0) {
                    foreach($phones as $idx=>$phone) {
                        $query = "INSERT INTO visual_phonebook_phones (contact_id,type,number) VALUES ('%d','%s','%s')";
                        $res = $db->consulta($query,array($lastid,$phone['type'],$phone['number']));
                    }
                    foreach($data['tags'] as $tag) {
                        if(!isset($existing_tags[$tag])) {
                            $mycolor = $hex_colors[$count_color];
                            $query = "INSERT INTO visual_phonebook_tags (tag,color) VALUES ('%s','%s')";
                            $ros = $db->consulta($query,$tag,$mycolor);
                            $tag_id = $db->insert_id($ros);
                            $existing_tags[$tag]=$tag_id;
                            $count_color++;
                            if($count_color>=15) { $count_color=0; }
                        } else {
                            $tag_id = $existing_tags[$tag];
                        }
                        $query = "INSERT INTO visual_phonebook_tags_contacts (contact_id,tag_id) VALUES ('%d','%d')";
                        $res = $db->consulta($query,array($lastid,$tag_id));
                    }
                }
            }
        }
    }
    fclose($fp);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta content="text/html; charset=utf-8" http-equiv="content-type">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta content="YES" name="apple-touch-fullscreen">
    <meta content="width=device-width, minimum-scale = 0.1, maximum-scale = 1.0, user-scalable=yes" name="viewport">

    <title>Contacts</title>

    <link href="css/bootstrap.min.css" media="screen" rel="stylesheet" type="text/css">
    <link href="css/jquery-ui.css" media="screen" rel="stylesheet" type="text/css">
    <link href="css/alertify.core.css" media="screen" rel="stylesheet" type="text/css">
    <link href="css/alertify.bootstrap3.css" media="screen" rel="stylesheet" type="text/css">
    <link href="css/contacts.css" media="screen" rel="stylesheet" type="text/css">
    <link href="css/animate.css" media="screen" rel="stylesheet" type="text/css">
    <link href="css/font-awesome.min.css" media="screen" rel="stylesheet" type="text/css">
    <link media="screen" rel="stylesheet" type="text/css" href="css/chosen.css">
    <script src="js/lodash.min.js"></script>
    <script src="js/jquery-1.11.3.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/jquery.ui.touch-punch.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.cropit.js"></script>
    <script src="js/jquery.timeago.js"></script>
    <script src="js/bootstrap-dropdown-on-hover.js"></script>
    <script src="js/alertify.min.js"></script>
    <script src="js/jquery.jscroll.js"></script>
    <script src="js/jquery.livesearch.js"></script>
    <script src="js/chosen.jquery.js"></script>
    <script src="js/contacts.js"></script>
<?php
    if($allowed=='no' or $extension=="-1") {
        echo "<meta http-equiv=\"refresh\" content=\"3\" >\n";
    }
?>
</head>
<body>

<div class='contents'>

<?php
if($notallowed<>'') {
    echo $notallowed;
    echo "</div>";
    echo "</body></html>";
    die();
}

if ($requestaction=='export') {
    $mensaje=array();
    $search = $_REQUEST['search'];
    $search = preg_replace("/\"/","",$search);
    $search = preg_replace("/;/","",$search);
    if($search<>"") {
        $condition = " (CONCAT(firstname,' ',lastname) LIKE \"%%%s%%\" OR company LIKE \"%%%s%%\" OR number LIKE \"%%%s%%\" OR email LIKE \"%%%s%%\" OR position LIKE \"%%%s%%\" OR identification LIKE \"%%%s%%\" OR tag LIKE \"%%%s%%\")";
    } else {
        $condition="1=1";
    }

    $query = "SELECT A.id,firstname,lastname,company,address,email,type,number,picture,identification,position,tag FROM visual_phonebook A LEFT JOIN visual_phonebook_phones ON id=contact_id LEFT JOIN  visual_phonebook_tags_contacts B on A.id=B.contact_id LEFT JOIN visual_phonebook_tags C ON C.id=B.tag_id WHERE A.context='%s' AND (owner='%s' OR (owner<>'%s' AND private='no')) AND ($condition) ORDER BY firstname,lastname,number";

    $res = $db->consulta($query,array($context,$extension,$extension,$search,$search,$search,$search,$search,$search,$search));
    $cuantos = $db->num_rows($res);
    if($cuantos==0) {
        $mensaje[] = array('kind'=>'warning','message'=>trans('There are no records to export'));
    }
    if(count($mensaje)>0) {
        print_messages($mensaje);
        exit;
    }
    @ob_end_clean();
    $filename = "contacts.vcf";
    header('Content-Type: text/x-vcard;charset=utf-8;');
    header("Content-Disposition: attachment; filename=$filename");
    $curid = 0;
    $cont = 0;
    $record = array();
    while ($row = $db->fetch_assoc($res)) {


       if($curid!=$row['id'] && $cont>0) {
           $rec = "BEGIN:VCARD\n";
           $rec.= "VERSION:3.0\n";
           ksort($record);
           foreach($record as $key=>$val) {
               if($key=='CATEGORIES') {
                   $linea="$key:".implode(",",$val);
               } else {
                   $linea="$key:$val";
               }
               if(strlen($linea)<75) {
                   $rec.="$linea\n";
               } else {
                   $lineas = str_split($linea,75);
                   $i=0;
                   foreach($lineas as $linea) {
                       if($i==0) {
                           $rec.="$linea\n";
                       } else {
                           $rec.=" $linea\n";
                       }
                       $i++;
                   }
               }
           }
           $rec.= "END:VCARD\n";
           echo $rec;
           if($cont+1<$cuantos) {
               // clear record array if its not the last one
               $record = array();
           }
       }

       $record['FN']=$row['firstname']." ".$row['lastname'];
       $record['N']=$row['lastname'].";".$row['firstname'].";;;";
       if($row['address']!='')        { $record['ADR;TYPE=HOME']=';;'.$row['address'].";;;;".$row['address']; }
       if($row['number']!='')         { $record['TEL;TYPE='.strtoupper($row['type'])]=$row['number']; }
       if($row['picture']!='')        { $record['PHOTO']=$row['picture']; }
       if($row['company']!='')        { $record['item1.ORG']=$row['company']; }
       if($row['identification']!='') { $record['X-IDENTIFICATION']=$row['identification']; }
       if($row['position']!='')       { $record['ROLE']=$row['position']; }
       if($row['tag']!='')            { if(!isset($record['CATEGORIES'])) { $record['CATEGORIES']=array(); } $record['CATEGORIES'][$row['tag']]=$row['tag']; }

       $curid = $row['id'];
       $cont++;
    }
    $rec = "BEGIN:VCARD\n";
    $rec.= "VERSION:3.0\n";
    ksort($record);
    foreach($record as $key=>$val) {
        if($key=='CATEGORIES') {
            $linea="$key:".implode(",",$val);
        } else {
            $linea="$key:$val";
        }
        if(strlen($linea)<75) {
            $rec.="$linea\n";
        } else {
            $lineas = str_split($linea,75);
            $i=0;
            foreach($lineas as $linea) {
                if($i==0) {
                    $rec.="$linea\n";
                } else {
                    $rec.=" $linea\n";
                }
                $i++;
            }
        }
    }
    $rec.= "END:VCARD\n";
    echo $rec;
    exit;

} else if ($requestaction=='import') {

    $arrFile = $_FILES['csvupload'];
    $file    = $arrFile['tmp_name'];

    if ($arrFile['size']>0 && !empty($file)) {
        if (is_uploaded_file($file)) {
        if (copy ($file, $userdirectory."contactsimportCSV.csv")) {
            $name_upload="contactsimportCSV.csv";
        }else{
            $error[]=array('kind'=>'warning','mesage'=>trans('Could not copy uploaded file'));
        }
        }else{
            $error[]=array('kind'=>'warning','message'=>trans('Could not upload file'));
        }
    }else{
        $error[]=array('kind'=>'warning','message'=>trans('Could not upload file'));
    }

    if($name_upload == "") {
        $error[]=array('kind'=>'warning','message'=>trans('Empty file?'));
    }
    if(count($error)>0) { print_messages($error); } else {
        // procesa csv
        $mensaje = process_vcard($userdirectory."contactsimportCSV.csv");
        print_messages($mensaje);
    }

    print_contacts();

} else {
    // Si no hay accion, mostramos lista
    print_contacts();

}

function getPageRange($current,$max,$range_offset=2) {
    $first = $current - $range_offset;
    $last = $current + $range_offset;
    if($first<=0) {
        $first=1;
    }
    if($last>=$max) {
        $last=$max;
    }
    return [ $first, $last];
}

function print_messages($errors) {
    foreach($errors as $error) {
    echo "<div class='alert alert-".$error['kind']." alert-dismissible' role='alert'>
  <button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button>
  <strong>".$error['message']."</strong></div>";
    }
}

function print_contacts() {
    global $db, $extension, $admin, $userdirectory, $context;

    if(isset($_GET['search'])) {
       $search = $_GET['search'];
       $search = preg_replace("/\"/","",$search);
       $search = preg_replace("/;/","",$search);
       if($search<>"") {
           $condition = " (CONCAT(firstname,' ',lastname) LIKE \"%$search%\" OR company LIKE \"%$search%\" OR number LIKE \"%$search%\" OR email LIKE \"%$search%\" OR position LIKE \"%$search%\" OR identification LIKE \"%$search%\" OR tag LIKE \"%$search%\")";
       } else {
           $condition="1=1";
       }

    } else {
        $condition="(1=1)";
        $search="";
    }

    if(isset($_GET['contactid'])) {
        $contactid = intval($_GET['contactid']);
        $condition=" A.id=$contactid ";
    }

/*
    $query = "SELECT firstname,lastname,company,number,email,position,identification FROM visual_phonebook A ";
    $query.= "LEFT JOIN visual_phonebook_phones ON id=contact_id ";
    $query.= "LEFT JOIN visual_phonebook_tags_contacts B on A.id=B.contact_id ";
    $query.= "LEFT JOIN visual_phonebook_tags C ON C.id=B.tag_id ";
    $query.= "WHERE A.context='$context' AND (owner='$extension' OR (owner<>'$extension' AND private='no')) GROUP BY number HAVING $condition";
*/



    $query = "SELECT A.id,firstname,lastname,picture,concat(firstname,' ',lastname) AS name,";
    $query.= "company,type,number,email,address,owner,position,identification,instagram,telegram,";
    $query.="color ";
    $query.= "FROM visual_phonebook A ";
    $query.= "LEFT JOIN visual_phonebook_phones ON A.id=contact_id ";
    $query.= "LEFT JOIN visual_phonebook_tags_contacts B on A.id=B.contact_id ";
    $query.= "LEFT JOIN visual_phonebook_tags C ON C.id=B.tag_id ";
    $query.= "WHERE A.context='$context' AND (owner='$extension' OR (owner<>'$extension' AND private='no')) ";
    $query.= "AND ($condition) ";
    $query.= "GROUP BY firstname,lastname,number ORDER BY name ";

    $querycount = "SELECT count(*) as count FROM (SELECT A.id ";
    $querycount.= "FROM visual_phonebook A ";
    $querycount.= "LEFT JOIN visual_phonebook_phones ON A.id=contact_id ";
    $querycount.= "LEFT JOIN visual_phonebook_tags_contacts B on A.id=B.contact_id ";
    $querycount.= "LEFT JOIN visual_phonebook_tags C ON C.id=B.tag_id ";
    $querycount.= "WHERE A.context='$context' AND (owner='$extension' OR (owner<>'$extension' AND private='no')) ";
    $querycount.= "AND ($condition) ";
    $querycount.= "GROUP BY firstname,lastname) A";

/*
 * Only works on MySQL 8 or newer
 *
    $query = "SELECT A.id,A.firstname,A.lastname,A.picture,concat(A.firstname,' ',A.lastname) AS name,";
    $query.= "A.company,B.type,B.number,A.email,A.address,A.owner,A.position,A.identification,A.instagram,A.telegram,C.tag_id,D.color ";
    $query.= "FROM visual_phonebook A ";
    $query.= "LEFT JOIN visual_phonebook_phones B on A.id=B.contact_id ";
    $query.= "LEFT JOIN (SELECT DISTINCT contact_id,MAX(tag_id) over(partition by contact_id) AS tag_id FROM visual_phonebook_tags_contacts LEFT JOIN visual_phonebook_tags on visual_phonebook_tags.id=tag_id) C ON A.id=C.contact_id ";
    $query.= "LEFT JOIN visual_phonebook_tags D on D.id=C.tag_id ";
    $query.= "WHERE A.context='$context' AND (owner='$extension' OR (owner<>'$extension' AND private='no')) ";
    $query.= "AND ($condition) ";
    $query.= "ORDER BY name ";

    $querycount = "SELECT COUNT(*) AS count FROM visual_phonebook A ";
    $querycount.= "LEFT JOIN visual_phonebook_phones B ON A.id=B.contact_id ";
    $querycount.= "LEFT JOIN visual_phonebook_tags_contacts B on A.id=B.contact_id ";
    $querycount.= "LEFT JOIN visual_phonebook_tags C ON C.id=B.tag_id ";
    $querycount.= "WHERE A.context='$context' AND (owner='$extension' OR (owner<>'$extension' AND private='no')) ";
    $querycount.= "AND ($condition) ";
*/

    // Only perform query to count results if we do not have rcount passed via request 
    if(isset($_REQUEST['rcount'])) {
        $rec_count = $_REQUEST['rcount'];
    } else { 
        $res = $db->consulta($querycount);
        $data = $db->fetch_assoc($res);
        $rec_count = intval($data['count']);
    }

    $rec_limit = 10;
    if( isset($_GET['page'] ) ) {
        $page = $_GET['page'];
        $offset = $rec_limit * $page;
    } else {
        $page = 0;
        $offset = 0;
    }
    $left_rec = $rec_count - ($page * $rec_limit);
    $tot_page = ceil($rec_count / $rec_limit);

    $mysearch = $_REQUEST['search'];
    echo "
    <div class='xrow' data-page='$page' data-totpage='$tot_page'>
        <div class='xcol-xs-12 xcol-sm-12' _style='padding-right:5px;'>
            <div id='stickyheader' class='xpanel xpanel-default' style='margin-bottom:0;'>

                <div class='panel-heading c-list'>
                    <span class='title' style='padding:5px; font-size:1.5em;' id='contactstitle'>".trans("Contacts")."</span>
                    <ul class='pull-right c-controls'>
                        <li>
                           <form id='addnew'><input type=hidden name='action' value='new'><input type=hidden name='number' id='contactnewnumber' value=''></form>
                           <a id='addnewbutton' style='cursor:pointer;color:#e6f5ff;' data-toggle='tooltip' data-placement='bottom' title='".trans('Add Record')."'><i class='fa fa-plus' style='font-size:1.5em;'></i></a>
                        </li>
                        <li class='dropdown' style='margin-right:10px;'>
                        <a href='#' class='dropdown-toggle' data-toggle='dropdown' role='button' aria-haspopup='true' aria-expanded='false'>
                        <i class='fa fa-bars' style='font-size:1.5em; color:#e6f5ff;'></i>
                        </a>
                        <ul class='dropdown-menu dropdown-menu-right animated flipInX'>
                            <li role='presentation'><a class='pointer' role='menuitem' id='buttonimport'><span class='fa fa-upload'></span> <span class='importtitle'>".trans('Import')."</span></a></li>
                            <li role='presentation'><a class='pointer' role='menuitem' id='buttonexport'><span class='fa fa-download'></span> <span class='exporttitle'>".trans('Export')."</span></a></li>
                            <li role='presentation'><a class='pointer' role='menuitem' id='buttontags'><span class='fa fa-tags'></span> <span class='tagstitle'>".trans('Tags')."</span></a></li>
                            <li role='presentation'><a class='pointer' role='menuitem' id='buttonbroadcast'><span class='fa fa-bullhorn'></span> <span class='tagstitle'>".trans('Broadcast')."</span></a></li>
                            <li role='presentation'><a class='pointer' role='menuitem' id='buttonconversation'><span class='fa fa-group'></span> <span class='tagstitle'>".trans('Create group')."</span></a></li>
                            <li role='presentation'><a class='pointer' role='menuitem' id='buttonclose'><span class='fa fa-arrow-right'></span> <span class='closetitle'>Close</span></a></li>
                        </ul>
                        </li>
                    </ul>
                </div>

                <div class='xrow' id='hsearch' style='height:3.4em;'>
                    <div class='xcol-xs-12'>
                        <div class='input-group c-search'>
                            <input type='text' class='form-control' autocomplete='off' id='contact-list-search' value='$mysearch' data-ptrans='Search'>
                            <span class='input-group-btn'>
                                <button class='btn btn-default' type='button'><span class='fa fa-search text-muted'></span></button>
                            </span>
                        </div>
                    </div>
                </div>
                <div id='hdelete' class='row' style='margin-right:5px; margin-left:0px;height:3.4em;'>
                <div class='col-xs-8'>
                <div style='text-align:left;' data-trans='Set Tag'>Set Tag</div>
                <select name='masstags' id='masstags'><option value=''></option></select>
                </div>
                <div class='col-xs-4' style='line-height:3.5em; vertical-align:baseline;'>
                <button type='button' class='btncontactdelete btn btn-sm btn-danger' data-trans='Delete'>Delete</button>
                </div>
                </div>
            </div>

    ";

    echo "<div id='records'>";
    echo "<ul class='list-group' id='contact-list' style='margin:0;'> ";

    // Add limit for pagination and infinite scroll
    
    $limitstr = "LIMIT $offset,$rec_limit";
    if(isset($_REQUEST['action'])) {
        if($_REQUEST['action']=='restoreposition') {
            $limit = ($_REQUEST['page']+1)*$rec_limit;
            $limitstr = "LIMIT 0,$limit";
        }
    }
    $query.= $limitstr;

    $res = $db->consulta($query);
    $page++;
    if($db->num_rows($res)==0) {
        echo "<div style='text-align:center; font-weight:400; color:#000;' class='alert alert-info'>".trans("No records found")."</div>";
    }
    while($row = $db->fetch_assoc($res)) {
        $colors = preg_split("/\|/",$row['color']);
        // ignore empty entries
        $colors_filtered = array_filter($colors, function($value) {
            return !empty($value);
        });

        $final_color = array_shift(array_values($colors_filtered));
        if($final_color=='') {$final_color='#F3F3F3';}

        $name =  $row['name'];
        $numberstrip = preg_replace("/[^0-9]/","",$row['number']);

        echo "<li id='".$row['id']."_".$numberstrip."' class='list-group-item chat contact".$row['id']."' data-contactid='".$row['id']."' style='padding:5px 1px 5px 1px; border-radius:5px; margin-bottom:5px;'>";
        
        echo '<div style="position:absolute;top:0;left:0;bottom:0;width:10px; background-color: '.$final_color.';border-radius:5px 0 0 5px;"></div>';

        echo "<div style='display:flex;flex-direction:row;margin-left:15px;'>";
        echo "<div class='nopad'>\n";

        if(is_file($userdirectory.$row['picture'])) {
            echo "<img src='$userdirectory".$row['picture']."' class='lazy img-responsive img-circle avatar'>";
        } else if(is_file('/var/www/html/'.$row['picture'])) {
            echo "<img src='".$row['picture']."' class='lazy img-responsive img-circle avatar'>";
        } else if(substr($row['picture'],0,4)=='http') {
            echo "<img src='".$row['picture']."' class='lazy img-responsive img-circle avatar'>";
        } else {
            $initial = mb_substr(trim($row['firstname']),0,1).mb_substr(trim($row['lastname']),0,1);
            $colors = array('#bf360c','#7b1fa2','#0097a7','#f4511e','#689f38','#5c6bc0','#8a8a8a','#8d6e63','#039be5','#5c6bc0');
            $hash = intval(hash("crc32b",$name),16)%10;

            $style = 'style="background-color: '.$colors[$hash].';"';
            if(is_numeric($initial)) {
                echo "<div class='avatar icon-user-default'>&nbsp;</div>";
            } else {
                echo "<div class='avatar monogram icon-user-defaulta' $style>$initial</div>";
            }
        }

        echo "      </div>";
        $name = htmlentities($name,ENT_QUOTES);
        echo "
                        <div class='nopad' style='padding-left:10px;'>
                            <span class='name nopadding searchit'>".$name."</span><br/>
                            ";

        if($row['company']<>'') {
                            echo "<span class='company searchit'>".htmlentities($row['company'])."</span><br/>";
        }
        echo "<a class='ztop' data-name='$name' data-toggle='tooltip' data-placement='bottom' data-original-title='".trans('Click to dial')."' data-number='$numberstrip' href='#' xonclick='parent.dial(\"$name <$numberstrip>\");'>".$row['number']."</a><br/>";

        echo " </div>";
        echo "
                        </div>
                        <div class='editlink'><a href='javascript:editrecord(".$row['id'].");' class='label label-default'>".trans('Edit Record')."</a></div>
                    </li> ";

    }

    if($page<$tot_page && $tot_page>0) {
        $backpage=$page-2;
        if($backpage>=0) {
            echo "<li class='chat' style='display:none;'><a class='back' href=\"contacts.php?page=$backpage&search={$search}&rcount={$rec_count}\">Prev $rec_limit Records</a></li>";
        }
        echo "<li class='chat' style='display:none;'><a class='first' href=\"contacts.php?page=$page&search={$search}&rcount={$rec_count}\">Next $rec_limit Records</a></li>";
    }
    
    echo "</ul>";
    echo "</div></div></div>";

}
?>

        <div id='uploadcontainer' class="modal fade" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <div class='content'><span class='fa fa-list-alt pull-left' style='padding: 7px 5px; font-size:1.5em;'></span><h3 class='modal-title uploadtitle'><?php echo trans("Import");?></h3></div>
                    </div>
                    <div class="modal-body">
                    <form method='post' enctype='multipart/form-data' id='formimport' name='formimport'><div id='uploadbutton' class='btn btn-default'><input type="file" id="csvupload" name="csvupload"><?php echo trans("Browse");?></div> <span id='csvfilename'></span><input type='hidden' name='action' value='import'>
                    <!--input type='submit' class='btn btn-primary'></input-->
                    <button type="submit" class="btn btn-success"> <i class="fa fa-arrow-circle-right fa-lg" id='submiticon' ></i> <?php echo trans('Upload'); ?> </button>
                    </form>
                    </div>
                </div>
            </div>
        </div>

<form class='hidden' id='formdelete'><input type=hidden name='action' value='delete'><input type=hidden name='id' id='formdeleteid' value=''></form>
<form class='hidden' id='formexport'><input type=hidden name='action' value='export'><input type=hidden name='search' id='exportfilter' value=''></form>
<span class='hidden' id='areyousure'>Are you sure?</span>
<span class='hidden' id='yesstring'>Yes</span>
<span class='hidden' id='nostring'>No</span>
<span class='hidden' id='searchstring'>Search</span>
<script>
  loadjs();
  setTimeout(function() { $(".alert").fadeOut(1000); },5000);
  <?php
    if(isset($_REQUEST['action'])) {
        if($_REQUEST['action']=='restoreposition') {
            echo "var savedY = parseInt(localStorage.getItem('contacts_scrollY'));\n";
            echo "window.scrollTo(0,savedY);\n";
        }
    }
  ?>
</script>
</body>
</html>
