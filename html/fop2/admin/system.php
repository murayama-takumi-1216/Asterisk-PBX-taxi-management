<?php
require_once('gettextinc.php');
require_once("kint.php");
require_once("kint-render.php");
require_once('dblib.php');

$hide_language = 0;

$page_to_menu = array();

$version="1.2.4";

// Prevent not set timezone warnings
if (!ini_get('date.timezone')) {
    if (@date_default_timezone_get()) {
        @date_default_timezone_set(date_default_timezone_get());
    }
}

set_error_handler("fop2manager_error",E_ALL);

$langs = array();
$langs['en_US']="English";
$langs['es_ES']="Español";
$langs['fr_FR']="Français";
$langs['da_DK']="Dansk";
$langs['el_GR']="Ελληνικά";
$langs['pt_BR']="Português";
$langs['uk_UA']='Ураїнська';

if(isset($_SERVER['PATH_INFO'])) {
    define("SELF",  substr($_SERVER['PHP_SELF'], 0, (strlen($_SERVER['PHP_SELF']) - @strlen($_SERVER['PATH_INFO']))));
} else {
    define("SELF",  $_SERVER['PHP_SELF']);
}

define('PROJECT_DIR', realpath('./'));
define('LOCALE_DIR', PROJECT_DIR .'/i18n');
define('DEFAULT_LOCALE', 'en_US');
define("MYAP", "FOP2Manager");

$encoding = 'UTF-8';

$locale = (isset($_COOKIE['lang']))? $_COOKIE['lang'] : DEFAULT_LOCALE;

T_setlocale(LC_MESSAGES, $locale);
$domain = 'fop2manager';
_bindtextdomain($domain, LOCALE_DIR);
// bind_textdomain_codeset is supported only in PHP 4.2.0+
_bind_textdomain_codeset($domain, $encoding); 
_textdomain($domain);

$predefined_groups = array( 
    array('id' => -1, 'name' =>"All Buttons"), 
    array('id' => -2, 'name' =>"All Extensions"),
    array('id' => -3, 'name' =>"All Queues"), 
    array('id' => -4, 'name' =>"All Conferences"),
    array('id' => -5, 'name' =>"All Trunks")
);

$conf = array();
$conf['DBHOST'] = $DBHOST;
$conf['DBUSER'] = $DBUSER;
$conf['DBPASS'] = $DBPASS;
$conf['DBNAME'] = $DBNAME;

if(!isset($ENGINE)) {
    if(is_file("/var/thirdlane_load/pbxportal-ast.sysconfig")) {
        $config_engine = 'thirdlane_db';
    } else
    if(is_file("/etc/issabelpbx.conf")) {
        $config_engine = 'issabel';
    } else
    if(is_file("/etc/amportal.conf")) {
        $config_engine = 'freepbx_old';
    } else
    if(is_file("/etc/freepbx/freepbx.conf") || is_file("/etc/freepbx.conf")) {
        $config_engine = 'freepbx';
    } else
    if(is_file("/etc/asterisk/users.txt")) {
        $config_engine = 'thirdlane_old';
    } else
    if(is_file("/etc/pbxware/pbxware.ini")) {
        $config_engine = 'pbxware';
    } else
    if(is_file("/etc/kamailio/kamailio-mhomed-elastix.cfg")) {
        $config_engine = 'elastix_mt';
    } else
    if(is_file("/etc/ombutel/ombutel.conf")) {
        $config_engine = 'ombutel';
    } else 
    if(is_dir("/etc/vitalpbx")) {
        $config_engine = 'vitalpbx';
    } else
    if(is_file("/etc/xivo/common.conf")) {
        $config_engine = 'xivo';
    } else
    if(is_file("/etc/asterisk/snep/snep-features.conf")) {
        $config_engine = 'snep';
    } else
    if(is_file("/opt/sipdoc/peke-system/etc/asterisk/asterisk.conf")) {
        $config_engine = 'peke';
    } else {
        $config_engine = "custom";
    }
} else {
    $config_engine = $ENGINE;
}

if
  ($config_engine=='thirdlane_db') {
      require_once("functions-thirdlane.php");
      $conf = parse_conf("/var/thirdlane_load/pbxportal-ast.sysconfig");
} else if
  ($config_engine=='thirdlane_old') {
      require_once("functions-thirdlane.php");
} else if
  ($config_engine=='freepbx') {
      require_once("functions-freepbx.php");
      if(is_file("/etc/freepbx/freepbx.conf")) {
          $conf = parse_conf("/etc/freepbx/freepbx.conf");
      } else if(is_file("/etc/freepbx.conf")) {
          $conf = parse_conf("/etc/freepbx.conf");
      }
} else if
  ($config_engine=='issabel') {
      require_once("functions-freepbx.php");
      $conf = parse_conf("/etc/issabelpbx.conf");
      $conf = array_merge($conf,parse_conf("/etc/amportal.conf"));
} else if
  ($config_engine=='freepbx_old') {
      require_once("functions-freepbx.php");
      $conf = parse_conf("/etc/amportal.conf");
      if(!isset($conf['DBENGINE'])) {
          if(is_file("/etc/freepbx/freepbx.conf")) {
              $conf = parse_conf("/etc/freepbx/freepbx.conf");
          } else if(is_file("/etc/freepbx.conf")) {
              $conf = parse_conf("/etc/freepbx.conf");
          }
      }
      $config_engine = "freepbx";
} else if
  ($config_engine=='pbxware') {
      require_once("functions-pbxware.php");
      $conf = parse_conf("/etc/pbxware/pbxware.ini");
      $conf['DBHOST'] = $conf['pw_mysql_host'];
      $conf['DBUSER'] = $conf['pw_mysql_username'];
      $conf['DBPASS'] = $conf['pw_mysql_password'];
      $conf['DBNAME'] = 'pbxware';
      $conf['fop2port']=4445;   // as pbxware runs chroot it cannot read fop2.cfg to take manager data
} else if
  ($config_engine=='elastix_mt') {
      require_once("functions-elastix.php");
      $conf = parse_conf("/etc/elastix.conf");
      $conf['DBHOST'] = 'localhost';
      $conf['DBUSER'] = 'root';
      $conf['DBPASS'] = $conf['mysqlrootpwd'];
      $conf['DBNAME'] = 'elxpbx';
} else if
  ($config_engine=='mirtapbx') {
      require_once("functions-mirta.php");
} else if
  ($config_engine=='vitalpbx') {
      require_once("functions-vitalpbx.php");
      $conf['DBHOST'] = 'localhost';
      $conf['DBUSER'] = 'vitalpbx';
      $conf['DBPASS'] = 'vitalpbx';
      $conf['DBNAME'] = 'ombutel';
      if(is_readable("/etc/asterisk/vitalpbx/manager__50-ombutel-user.conf")) {
          $mgrconf = parse_conf("/etc/asterisk/vitalpbx/manager__50-ombutel-user.conf");
          $conf['MGRPASS'] = $mgrconf['secret'];
          $conf['MGRUSER'] = 'asmanager';
          $conf['MGRPORT'] = 5038;
          $conf['MGRHOST'] = 'localhost';
      }
} else if
  ($config_engine=='ombutel') {
      require_once("functions-ombutel.php");

      if(is_readable("/etc/asterisk/ombutel/manager__60-fop2_secret.conf")) {
          $secret_conf = parse_conf("/etc/asterisk/ombutel/manager__60-fop2_secret.conf");
          $conf['MGRPASS'] = $secret_conf['secret'];
          $conf['MGRUSER'] = 'fop2';
          $conf['MGRPORT'] = 5038;
          $conf['MGRHOST'] = 'localhost';
      }
} else if
  ($config_engine=='custom') {
      $archi = dirname(__FILE__)."/functions-custom.php";
      if(!is_file($archi)) {
          include(dirname(__FILE__)."/headerbs.php");
          echo "<div class='wrap'>\n";
          echo "<div class='content'>\n"; 
          echo "<div class='col-md-12'>\n";
          echo "<div class='card card-warning grey'>\n";
          echo "<div class='card-header'>\n";
          echo "<div class='card-title'><div class='title'>\n";
          echo __("Warning");
          echo "</div>\n";
          echo "</div>\n";
          echo "</div>\n";
          echo "<div class='card-body'>\n";
          echo "You must have a functions-custom.php file in the manager directory.";
          echo "</div>\n";
          echo "</div>\n";
          include(dirname(__FILE__)."/footerbs.php");
          die();
      } else {
          require_once("functions-custom.php");
      }
} else if
  ($config_engine=='xivo') {
      require_once("functions-xivo.php");
} else if
  ($config_engine=='snep') {
      $conf['DBHOST'] = 'localhost';
      $conf['DBUSER'] = 'snep';
      $conf['DBPASS'] = 'sneppass';
      $conf['DBNAME'] = 'snep';
      $conf['MGRPASS'] = 'fop2custom';
      $conf['MGRUSER'] = 'fop2';
      $conf['MGRPORT'] = 5038;
      $conf['MGRHOST'] = 'localhost';
      require_once("functions-snep.php");
} else if
  ($config_engine=='peke') {
      $conf['DBHOST'] = 'localhost';
      $conf['DBUSER'] = 'root';
      $conf['DBPASS'] = '';
      $conf['DBNAME'] = 'peke_pbx';
      $conf['fop2port']=5100; 
      require_once("functions-peke.php");
} else {
      if(is_file("functions-${config_engine}.php")) {
          require_once("functions-${config_engine}.php");
      } else {
          require_once("functions-astdb.php");
      }
}
define('ENGINE',$config_engine);

if(is_file("/usr/local/fop2/fop2.cfg") || is_file("/etc/asterisk/fop2/fop2.cfg")) {
   if(is_file("/usr/local/fop2/fop2.cfg")) {
       $fop2conf = parse_conf("/usr/local/fop2/fop2.cfg");
   }
   if(is_file("/etc/asterisk/fop2/fop2.cfg")) {
       $fop2conf = parse_conf("/etc/asterisk/fop2/fop2.cfg");
   }
   if(isset($fop2conf['listen_port'])) {
       $conf['fop2port']=$fop2conf['listen_port'];
   } else {
       $conf['fop2port']=4445;
   }
   if(isset($fop2conf['manager_port'])) {
       $conf['MGRPORT']=$fop2conf['manager_port'];
   } else {
       $conf['MGRPORT']=5038;
   }
   if(isset($fop2conf['manager_user'])) {
       $conf['MGRUSER']=$fop2conf['manager_user'];
   } else {
       $conf['MGRUSER']='admin';
   }
   if(isset($fop2conf['manager_host'])) {
       $conf['MGRHOST']=$fop2conf['manager_host'];
   } else {
       $conf['MGRHOST']='127.0.0.1';
   }
   if(isset($fop2conf['manager_secret'])) {
       $conf['MGRPASS']=$fop2conf['manager_secret'];
   } else {
       $conf['MGRPASS']='amp111';
   }
}

if(!isset($conf['fop2port'])) { $conf['fop2port']='4445'; }

// Number of records per page in users/groups/templates/permissions tables
$perpage=13;

// If this is Elastix, use correct session name
if(is_file("/etc/elastix.conf")) {
    if($config_engine=='freepbx' && USE_BACKEND_AUTH==true) {
        session_name("elastixSession");
        session_start();
        // Set password to be the same as admin in elastix
        $file = file('/etc/elastix.conf');
        foreach ($file as $line) {
            if (preg_match("/^\s*([\w]+)\s*=\s*\"?([\w\/\:\.\*\%!-]*)\"?\s*([;#].*)?/",$line,$matches)) {
                if($matches[1]=='amiadminpwd') {  $ADMINPWD = $matches[2]; };
            }
        }
        if(!isset($_SESSION[MYAP]['AUTHVAR']['level'])) {
            if(isset($_SESSION['elastix_user'])) {
                // Set manager secure level based on Issabel ACL
                $dbelx = new dbcon("sqlite:/var/www/db/acl.db");

                $query = "SELECT acl_user.name,acl_resource.name from acl_user LEFT JOIN acl_membership ON acl_user.id=id_user ";
                $query.= "LEFT JOIN acl_group_permission ON acl_membership.id_group = acl_group_permission.id_group ";
                $query.= "LEFT JOIN acl_resource ON acl_resource.id = acl_group_permission.id_resource ";
                $query.= "WHERE acl_resource.name='fop2manager' AND acl_user.name=%s";

                $result = $dbelx->consulta($query,array($_SESSION['issabel_user']));
                $num=0;
                while($row = $dbelx->fetch_assoc($result)) {
                    $num++;
                }
                if($num==0) {
                    $_SESSION[MYAP]['AUTHVAR']['level']='user';
                } else {
                    $_SESSION[MYAP]['AUTHVAR']['level']='admin';
                }
                $dbelx->close();
            }
        }
    } else if($config_engine=='freepbx' && USE_BACKEND_AUTH==false) {
        session_name("elastixSession");
        session_start();
    } else {
        session_start();
    }
} else
    if(is_file("/etc/issabel.conf")) {
        if($config_engine=='issabel' && USE_BACKEND_AUTH==true) {
        session_name("issabelSession");
        session_start();
        // Set password to be the same as admin in elastix
        $file = file('/etc/issabel.conf');
        foreach ($file as $line) {
            if (preg_match("/^\s*([\w]+)\s*=\s*\"?([\w\/\:\.\*\%!-]*)\"?\s*([;#].*)?/",$line,$matches)) {
                if($matches[1]=='amiadminpwd') {  $ADMINPWD = $matches[2]; };
            }
        }
    if(!isset($_SESSION[MYAP]['AUTHVAR']['level'])) {
        if(isset($_SESSION['issabel_user'])) {
            // Set manager secure level based on Issabel ACL
            $dbissa = new dbcon("sqlite:/var/www/db/acl.db");

            // check to see if fop2 is installed as addon, to know if use admin or user level
            $query = "SELECT * FROM acl_resource WHERE name='fop2manager'";
            $result = $dbissa->consulta($query);
            if($dbissa->num_rows($result)>0) {

                $query = "SELECT acl_user.name,acl_resource.name from acl_user LEFT JOIN acl_membership ON acl_user.id=id_user ";
                $query.= "LEFT JOIN acl_group_permission ON acl_membership.id_group = acl_group_permission.id_group ";
                $query.= "LEFT JOIN acl_resource ON acl_resource.id = acl_group_permission.id_resource ";
                $query.= "WHERE acl_resource.name='fop2manager' AND acl_user.name=%s";

                $result = $dbissa->consulta($query,array($_SESSION['issabel_user']));
                $num=0;
                while($row = $dbissa->fetch_assoc($result)) {
                    $num++;
                }
                if($num==0) {
                    $_SESSION[MYAP]['AUTHVAR']['level']='user';
                } else {
                    $_SESSION[MYAP]['AUTHVAR']['level']='admin';
                }
                $dbissa->close();
            } else {
                // if fop2 is not an issabel addon, then asume admin level if session is open
                $_SESSION[MYAP]['AUTHVAR']['level']='admin';
                $dbissa->close();
            }
        } 
    }

    // Set language cookie based on issabel setting
    if(isset($_SESSION['issabel_user'])) {
        $mylangs = array('es'=>'es_ES','en'=>'en_US','fr'=>'fr_FR','br'=>'pt_BR');
        $dbissa2 = new dbcon("sqlite:/var/www/db/settings.db");
        $query = "select value from settings where key='language'";
        $result = $dbissa2->consulta($query);
        $language = $result->fetchColumn();
        $finallanguage = isset($mylangs[$language])?$mylangs[$language]:$language;
        setcookie('lang',$finallanguage);
        $hide_language=1;
    }
 
    } else if($config_engine=='issabel' && USE_BACKEND_AUTH==false) {
        session_name("issabelSession");
        session_start();
    } else {
        session_start();
    }
} else {
    session_start();
}

$panelcontextdefault='';
$panelcontext = (isset($_SESSION[MYAP]['AUTHVAR']["contextid"]))? $_SESSION[MYAP]['AUTHVAR']["contextid"] : $panelcontextdefault;
$panelcontext = intval($panelcontext);

if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
    header('X-UA-Compatible: IE=edge,chrome=1');
}

$extramenu=array();

if (!function_exists('json_encode')) {
    function json_encode($content) {
        require_once 'JSON.php';
        $json = new Services_JSON;
        return $json->encode($content);
    }
    function json_decode($content) {
        require_once 'JSON.php';
        $json = new Services_JSON;
        return $json->decode($content);
    }
}

if(!isset($FORCE_UTF8)) {
    $FORCE_UTF8=false;
}

if(!isset($PLUGIN_DIR)) {
    $PLUGIN_DIR = dirname(__FILE__)."/plugins/";
} else {
    if(substr($PLUGIN_DIR,0,-1)<>'/') {
        $PLUGIN_DIR.="/";
    }
}

if(!isset($SQLITEDB)) {
    $SQLITEDB="/usr/local/fop2/fop2settings.db";
}

if(is_readable('module.xml')) {
    $xml      = simplexml_load_file('module.xml');
    $xmlarray = simple_xml_to_array($xml);
    $menu     = $xmlarray['menu']['item'];
    foreach($menu as $idx=>$arrdata) {
        if(isset($arrdata['action'])) { 
            if(strpos($arrdata['action'],'php')) {
                $page_to_menu[$arrdata['action']]=$arrdata['name'];
            }
        }
        if(isset($arrdata['menu'])) {
            foreach($arrdata['menu']['item'] as $subidx=>$subarrdata) {
                if(strpos($subarrdata['action'],'php')) {
                    $page_to_menu[$subarrdata['action']]=$arrdata['name']."/".$subarrdata['name'];
                }
            }
        }
    }
}
if (!isset($_COOKIE['lang'])) {
    $_COOKIE['lang'] = 'en_US';
} else {
    setcookie('lang', $_COOKIE['lang'], time()+365*24*60*60, dirname(__FILE__) );
}
header("Content-type: text/html; charset=$encoding");

