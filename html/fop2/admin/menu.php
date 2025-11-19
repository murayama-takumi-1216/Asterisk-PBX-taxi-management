<?php
//$xml      = simplexml_load_file('module.xml');
//$xmlarray = simple_xml_to_array($xml);
//$menu     = $xmlarray['menu']['item'];

$pluginmenu = fop2_get_plugins_menu();
foreach($pluginmenu as $rawname=>$menuxml) {
    $xml      = simplexml_load_file($menuxml);
    $xmlarray = simple_xml_to_array($xml);

    _bindtextdomain($rawname, realpath('./')."/plugins/$rawname/i18n/");
    $encoding = 'UTF-8';
    _bind_textdomain_codeset($rawname, $encoding);

    if(!isset($xmlarray['menu']['item'])) {
         foreach($xmlarray['menu'] as $idx=>$nitem) {
             $nitem['item']['plugin']=$rawname;
             array_push($menu,$nitem['item']);
         }
    } else {
        $xmlarray['menu']['item']['plugin']=$rawname;
        array_push($menu,$xmlarray['menu']['item']);
    }
}
//echo "<pre>";
//print_r($menu);
//echo "</pre>";

$logged_in=false;
if(is_logged_in()==2) {
    $logged_in=true;
}
 
$rootdir = "//".$_SERVER['HTTP_HOST'] . '/'. substr(dirname(__FILE__), strlen($_SERVER['DOCUMENT_ROOT']));
$contextname = array();
$contextfullname = array();
$menu_link = array();

if(!isset($_SESSION[MYAP]['fop2version'])) {
    list ($fop2version,$fop2registername,$fop2licensetype) = fop2_get_version();
    $_SESSION[MYAP]['fop2version']=$fop2version;
}

$fop2version = $_SESSION[MYAP]['fop2version'];
$fop2version   = normalize_version($fop2version);
$fop2version   = intval($fop2version);

$cuantoscontexts=0;
$panelcontextdefault=0;
$allowed='';
$allowed_tenants ='';

if(isset($db)) {
    $res = $db->consulta("DESC fop2contexts");
    if($res) {

        $panel_contexts = fop2_populate_contexts();

        if(isset($_SESSION[MYAP]['AUTHVAR'])) {
            $allowed_tenants = isset($_SESSION[MYAP]['AUTHVAR']['allowed_tenants'])?$_SESSION[MYAP]['AUTHVAR']['allowed_tenants']:'';
            if($allowed_tenants<>'') {
                $allowed = "AND id IN ($allowed_tenants)";
            }
        }

        $results = $db->consulta("SELECT * FROM fop2contexts WHERE 1=1 $allowed");
        $cuantoscontexts = $db->num_rows($results);

        $results = $db->consulta("SELECT * FROM fop2contexts WHERE exclude=0 $allowed ORDER BY context");
        $cont=0;
        while ($re = $db->fetch_assoc($results)) {
            $contextname[$re['id']]=$re['context'];
            $contextfullname[$re['id']]=$re['name'];
            if($cont==0) { $panelcontextdefault = $re['id']; }
            $cont++;
        }
        $db->seek($results,0);
    }
}

isset($_SESSION[MYAP]['AUTHVAR']['contextid'])? $_SESSION[MYAP]['AUTHVAR']['contextid'] : $panelcontextdefault;
$panelcontext = intval($panelcontext);

if($allowed_tenants<>'') {
    $allowed_array = explode(",",$allowed_tenants);
    if(!in_array($panelcontext,$allowed_array)) {
       $panelcontext = $panelcontextdefault;
    }
}


// if we have cookie for context but no real contexts on db, default to single tenant/context
if($cuantoscontexts==0) {
    $panelcontext=0;
    echo "<script>document.cookie='context=0';</script>";
}

$extramenu['pagebs.fop2buttons.php']['Actions']['Sort by Number']='pagebs.fop2buttons.php?action=sortnumber';
$extramenu['pagebs.fop2buttons.php']['Actions']['Sort by Name']='pagebs.fop2buttons.php?action=sortname';
$extramenu['pagebs.fop2users.php']['Actions']['Recreate Users']='pagebs.fop2users.php?action=create';
$extramenu['pagebs.fop2buttons.php']['Actions']['Batch Update']='onclick=\'$("#uploadcontainer").modal(); return false;\'';

if($config_engine=='freepbx') {
    // Sync Labels only available in FreePBX for now
    $extramenu['pagebs.fop2buttons.php']['Actions']['Synchronize Labels']='pagebs.fop2buttons.php?action=refresh';
}
/*
if (!isset($_COOKIE['lang'])) {
    $_COOKIE['lang'] = 'en_US';
} else {
    setcookie('lang', $_COOKIE['lang'], time()+365*24*60*60);
}
*/
?>
<!-- Fixed navbar -->
<div class='navbar navbar-default navbar-fixed-top' role='navigation' id='fop2navbar'>
  <div class='container-fluid'>
    <div class='navbar-header'>

      <button type='button' class='navbar-toggle' data-toggle='collapse' data-target='.navbar-collapse'>
        <span class='sr-only'>Toggle navigation</span>
        <i class="fa fa-navicon"></i>
      </button>

      <button type='button' class='navbar-toggle' data-toggle='offcanvas'>
        <span class='sr-only'>Toggle sidebar</span>
        <i class="fa fa-chevron-left"></i>
      </button>


<?php
if($logged_in) {
// Do not show logo on top left on login form, as it is displayed in the actual login form
?>
      <div class='navbar-brand'>
        <img src='<?php echo $LOGO;?>' style='border:0; margin-left:-10px; margin-top:-10px;' class='pull-left' alt='logo'/>&nbsp;<a href='<?php echo $rootdir;?>'><div class='pull-right'><?php echo $LOGONAME; ?></div></a>
      </div>
<?php } ?>

    </div>

    <div class='navbar-collapse collapse'>

<ul class='nav navbar-nav navbar-right'>
<?php

// Reload Panel Icon
echo "<li class='dropdown'>\n";
echo "<a class='ttip pointer' id='fop2reload' style='display:none;' data-toggle='popover' data-trigger='hover' data-placement='bottom' data-content='".__('Reload Panel')."' onclick='fop2Reload();'><i class='fa fa-refresh'></i>&nbsp;<div class='pulse'></div></a>\n";
echo "</li>";

if($logged_in) {
    
    foreach($menu as $idx=>$arrdata) {
        // Skip if we are not running the required version at least
        if(!isset($arrdata['requiredversion'])) { $arrdata['requiredversion']=$fop2version; }
        $reqversion = $arrdata['requiredversion'];
        $reqlevel   = isset($arrdata['requiredlevel'])?$arrdata['requiredlevel']:'';
        $nativeauth = isset($arrdata['onlywithnativeauth'])?$arrdata['onlywithnativeauth']:'';

        if($fop2version<$reqversion) { continue; }

        if((USE_BACKEND_AUTH==true && ($config_engine=='freepbx' || $config_engine=='issabel' ) || $config_engine=='ombutel') && $nativeauth=='yes') {
            continue;
        }

        $check_acl_name = $arrdata['name'];

        if($reqlevel<>'') {
            // check hardcoded acl from module.xml
            if(!check_acl($check_acl_name,$levels[$reqlevel])) {
                continue;
            } 
        } else {
            // if not hardcoded in module.xml, check from db acl
            $sumlevels = array_sum($levels);
            if(!check_acl($check_acl_name,$sumlevels)) {
                continue;
            }
        }

        $menu_link[$arrdata['name']]=isset($arrdata['action'])?$arrdata['action']:'';

        if(isset($arrdata['menu'])) {
            // Drop Down menus on top always
            // If there is only one submenu item, make it a deeper array
            if(!isset($arrdata['menu']['item'][0])) {
                $temparray = $arrdata['menu']['item'];
                unset($arrdata['menu']['item']);
                $arrdata['menu']['item'][0] = $temparray;
            }
    
            $menuname = $arrdata['name'];
            $icon     = $arrdata['icon'];

            echo "  <li class='dropdown'>\n";
            echo "    <a href='#' class='dropdown-toggle' data-toggle='dropdown'>";
            echo "    <i class='fa $icon'></i> ";
            if(isset($arrdata['plugin'])) {
                echo __($arrdata['plugin'])." <b class='caret'></b></a>\n";
            } else {
                echo __($arrdata['name'])." <b class='caret'></b></a>\n";
            }
            echo "    <ul class='dropdown-menu animated flipInX'>\n";

            if(isset($extramenu[basename(SELF)])) {
                if(isset($extramenu[basename(SELF)][$arrdata['name']])) { 
                    if(count($extramenu[basename(SELF)][$arrdata['name']])>0) {
                        foreach($extramenu[basename(SELF)][$arrdata['name']] as $name=>$link) {
                            if(substr($link,0,7)=="onclick") {
                                $href="href='#' ".$link;
                            } else {
                                $href="href='".$link."'";
                            }

                            echo "<li><a $href>".__($name)."</a></li>\n";
                        }
                        echo "<li class='divider'></li>\n";
                    }
                }
            }

            foreach($arrdata['menu']['item'] as $subidx=>$subarrdata) {

                $check_acl_name = $arrdata['name']."/".$subarrdata['name'];
                $reqlevel       = isset($subarrdata['requiredlevel'])?$subarrdata['requiredlevel']:'';
                $nativeauth     = isset($subarrdata['onlywithnativeauth'])?$subarrdata['onlywithnativeauth']:'';

                if((USE_BACKEND_AUTH==true && ($config_engine=='freepbx' || $config_engine=='issabel' ) || $config_engine=='ombutel') && $nativeauth=='yes') {
                    continue;
                }

                if($reqlevel<>'') {
                    // check hardcoded acl from module.xml
                    if(!check_acl($check_acl_name,$levels[$reqlevel])) {
                        continue;
                    }
                } else {
                    // if not hardcoded in module.xml, check from db acl
                    $sumlevels = array_sum($levels);
                    if(!check_acl($check_acl_name,$sumlevels)) {
                        continue;
                    }
                }

                if(substr($subarrdata['action'],0,7)=="onclick") {
                    $href="href='#' ".$subarrdata['action'];
                } else {
                    $href="href='".$subarrdata['action']."'";
                }

                if(preg_match("/{$subarrdata['action']}/",SELF)) { $active=' class=\'active\' '; } else { $active=''; }
                if(isset($subarrdata['plugin'])) {
                    echo "<li $active><a $href>".__($subarrdata['plugin'])." </a></li>\n";
                } else {
                    echo "<li $active><a $href>".__($subarrdata['name'])." </a></li>\n";
                }
            }

            echo "</ul>\n</li>\n";
        }
    }
}

// Tenants
if($logged_in) {


    if(count($contextname)>0) {
        $selectedtenant = ($panelcontext<>'')?$contextfullname[$panelcontext]:__('tenant');

        echo "<li class='dropdown'>\n";
        echo "<a href='#' class='dropdown-toggle' data-toggle='dropdown'><i class='fa fa-building'></i> ".$selectedtenant."<b class='caret'></b></a>\n";
        echo "<ul class='dropdown-menu animated flipInX scrollable-menu'>\n";

        foreach($contextname as $ctx_id=>$ctx_name) {
            $fullname = $contextfullname[$ctx_id];
            echo '<li>';
            echo '<a href="#" onclick="setContext(\''.$ctx_id.'\'); return false;">';
            if($panelcontext==$ctx_id) {
                echo "<i class='fa fa-check' style='float: right; margin-top: 2px; margin-right: -6px;'></i> ";
            }
            echo $fullname;
            echo "</a>";
            echo "</li>\n";
        }
        echo "</ul>\n";
        echo "</li>\n";
    }
}

if($hide_language==0) {
    $isoflag = array('uk_UA'=>'ua');
    // Language
    echo "<li class='dropdown'>\n";
    echo "<a href='#' class='dropdown-toggle' data-toggle='dropdown'><i class='fa fa-globe'></i> ".__('Language')."<b class='caret'></b></a>\n";
    echo "<ul class='dropdown-menu animated flipInX'>\n";
    foreach($langs as $iso=>$langname) {
        $flagsuffix = isset($isoflag[$iso])?$isoflag[$iso]:$iso;
        echo '<li>';
        echo '<a href="#" onclick="setLang(\''.trim($iso).'\'); return false;">';
        echo "<img src='images/blank.gif' class='flag flag-$flagsuffix' alt='$langname'>\n";
        if($_COOKIE['lang']==$iso) { echo "<i class='fa fa-check' style='float: right; margin-top: 2px; margin-right: -6px;'></i> "; }
        echo $langname;
        echo "</a>";
        echo "</li>\n";
    }
    echo "</ul></li>";
}

// Logout
if($logged_in) {
    if($config_engine=='freepbx' || $config_engine=='issabel') {
        if(!isset($_SESSION['AMP_user']) && !isset($_SESSION['elastix_user']) && !isset($_SESSION['issabel_user'])) {
            echo "<li><a href='secure/logout.php?redirect=$rootdir'><i class='fa fa-sign-out-alt'></i> ".__('Logout')."</a></li>\n";
        } else {
            if(USE_BACKEND_AUTH==false) {
                echo "<li><a href='secure/logout.php?redirect=$rootdir'><i class='fa fa-sign-out-alt'></i> ".__('Logout')."</a></li>\n";
            }
        }
    } else {
        echo "<li><a href='secure/logout.php?redirect=$rootdir'><i class='fa fa-sign-out-alt'></i> ".__('Logout')."</a></li>\n";
    }
}
?>

</ul>

    </div><!--/.nav-collapse -->
  </div>
</div>
<div class='container-fluid'>


<div class="row row-offcanvas row-offcanvas-left" >
<?php        
if($logged_in) {
?>
         <div class="col-sm-2 col-md-1 sidebar-offcanvas" id="sidebar" role="navigation">
           
            <ul class="nav nav-sidebar with-scroll" data-spy="affix">
<?php
    foreach($menu as $idx=>$arrdata) {

        // Skip if we are not running the required version at least
        if(!isset($arrdata['requiredversion'])) { $arrdata['requiredversion']=$fop2version; }
        $reqversion = $arrdata['requiredversion'];
        $reqlevel   = isset($arrdata['requiredlevel'])?$arrdata['requiredlevel']:'';

        if($fop2version<$reqversion) { continue; }

        $check_acl_name = $arrdata['name'];

        if($reqlevel<>'') {
            // check hardcoded acl from module.xml
            if(!check_acl($check_acl_name,$levels[$reqlevel])) {
                continue;
            }
        } else {
            // if not hardcoded in module.xml, check from db acl
            $sumlevels = array_sum($levels);
            if(!check_acl($check_acl_name,$sumlevels)) {
                continue;
            }
        }

        $menu_link[$arrdata['name']]=isset($arrdata['action'])?$arrdata['action']:'';

        if(!isset($arrdata['menu'])) {

            if($cuantoscontexts==0 && $arrdata['action']=='pagebs.fop2contexts.php') {
               // Skip tenants/contexts menu on single tenant setups
               continue;
            }

            // Simple Action
            if(!isset($arrdata['icon'])) { $arrdata['icon']='fa-cog'; }
            if(preg_match("@{$arrdata['action']}@",SELF)) { $active=' active '; } else { $active=''; }
            echo "<li class='text-center $active'>\n";
            echo "<a href='".$arrdata['action']."'>";
            if(isset($arrdata['image'])) {
                echo "<img src='".$arrdata['image']."' style='width:40px' />";
            } else {
                echo "<div class='fa fa-2x ".$arrdata['icon']."'></div> ";
            }
            if(isset($arrdata['plugin'])) {
                echo "<div> "._dgettext($arrdata['plugin'],$arrdata['name'])."</div>";
            } else {
                echo "<div> ".__($arrdata['name'])."</div>";
            }
            echo "</a>";
            echo "</li>\n";

        }
    }
    echo "<li><div style='height:75px;'></div></li>";
?>
 
            </ul>
          
        </div><!--/span-->
        <div class="col-sm-10 col-md-11 main">
<?php 
} else {
    echo "<div class='col-md-12 col-sm-12 main'>\n";
}
?>        
          
