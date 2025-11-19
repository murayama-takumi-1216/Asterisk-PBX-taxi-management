#!/usr/bin/php
<?php 
require_once("config.php");
require_once("functions.php");
require_once("system.php");
require_once("dblib.php");
require_once("asmanager.php");
require_once("dbconn.php");

if (php_sapi_name() !='cli') exit;

$shrt_options = "lhi::u::v::";
$long_options = array("list","help","install::","uninstall::","version::");

$options = getopt($shrt_options,$long_options);

function print_help() {

    echo "

This tool allows you to list, install and uninstall FOP2 plugins from the command line.

Usage:
    ./fop2cli.php [options]

     Options:
       -h, --help
       -l, --list
       -i, --install
       -u, --uninstall
       -v, --version

Options:
    --help  Print a brief help message and exits

    --list
            List available plugins, status and versions

    --install=
            Install specified plugin using its short name (one word)

    --uninstall=
            Removes or uninstall plugin using its short name (one word)

    --version=
            Specify  plugin version number to install


Examples:

    Install the clock plugin:

        ./fop2cli.php --install=clock

    Install a specific version of a plugin:

        ./fop2cli.php --install=simplewallboard --version=1.0.1

    Uninstall a plugin:

        ./fop2cli.php --uninstall=simplewallboard




   "; 
exit;
}

// Check command line options
//
if(isset($options['h']) || isset($options['help'])) {
    print_help();
    exit;
} else if(isset($options['l']) || isset($options['list'])) {
    cli_plugin_list();
    exit;
} else if(isset($options['i']) || isset($options['install'])) {
    $plugin_to_install = isset($options['i'])?$options['i']:$options['install'];
    if(isset($options['v'])||isset($options['version'])) {
        $version = isset($options['v'])?$options['v']:$options['version'];
    } else {
        $version='';
    }
    cli_plugin_install($plugin_to_install,$version);
    exit;
} else if(isset($options['u']) || isset($options['uninstall'])) {
    $plugin_to_uninstall = isset($options['u'])?$options['r']:$options['uninstall'];
    cli_plugin_uninstall($plugin_to_uninstall);
    exit;
} else {
    print_help();
    exit;
}

function cli_plugin_list() {
    global $config_engine;
    $fop2_mirror = get_fastest_mirror();

    $plugin_sorted=array();
    list ($fop2version,$fop2registername,$fop2licensetype) = fop2_get_version();

    $fop2version   = normalize_version($fop2version);
    $fop2version   = intval($fop2version);

    $plugin_online = plugin_get_online($fop2_mirror);
    $plugin_local  = get_installed_plugins('version');

    if (isset($plugin_online)) {
        foreach ($plugin_online as $idx=>$arrdata) {
            if(!in_array($arrdata['name'],$plugin_sorted)) {
                $plugin_sorted[$idx]=$arrdata['name'];
            }
        }
    }

    if(is_array($plugin_sorted)) {
        asort($plugin_sorted);
    }

    $separator="\t";

    $colors = new Colors();
    if(count($plugin_sorted)>0) {
        foreach($plugin_sorted as $idx=>$name) {
            $allowed_engines=array();
            $local_version = '-';
            $raw_name = $plugin_online[$idx]['rawname'];

            $online_version = $plugin_online[$idx]['version'];
            if(array_key_exists($plugin_online[$idx]['rawname'],$plugin_local)) {
                $state = $colors->getColoredString('INSTALLED','green','black','%-15s');
                $local_version = $plugin_local[$plugin_online[$idx]['rawname']];
            } else {
                $state = $colors->getColoredString('NOT INSTALLED','blue','black','%-15s');
            }
            if(!is_array($plugin_online[$idx]['engine'])) {
                $allowed_engines=preg_split("/,/",$plugin_online[$idx]['engine']);
            } else {
                $allowd_engines=array();
            }

            if(count($allowed_engines)>0) {
                if(!in_array($config_engine,$allowed_engines)) {
                    continue;
                }
            }
            $name = sprintf("%30s",substr($name,0,30));
            $short_name = sprintf("%-20s",$raw_name);
            $local_version_print = $colors->getColoredString($local_version,"green","black",'%-5s');
            $online_version_print = $colors->getColoredString($online_version,"yellow","black",'%-5s');
            echo $state.$separator.$short_name.$separator.$name.$separator.$local_version_print.$separator.$online_version_print;
            $local_version_compare = long_version($local_version);
            $online_version_compare = long_version($online_version);
            if($online_version_compare > $local_version_compare && $local_version<>'-') {
                echo $separator."Upgrade Available";
            }
            echo "\n";
        }
    } else {
        $error = $colors->getColoredString("Could not connect to online repositories, listing only locally installed plugins","yellow","red");
        echo "$error\n\n";
        foreach($plugin_local as $rawname=>$version) {

            $infoxml   = substr(escapeshellarg($PLUGIN_DIR."$rawname/plugin.xml"),1,-1);
            if(is_readable($infoxml)) {
                $pluginxml = file_get_contents($infoxml);
                $xml  = simplexml_load_string($pluginxml);
                $data = simple_xml_to_array($xml);
                $name=$data['name'];
                $local_version=$data['version'];
            } else {
                $name=$rawname;
            }
            $online_version_print="";

            $name = sprintf("%30s",substr($name,0,30));
            $state = $colors->getColoredString('INSTALLED','green','black','%-15s');
            $short_name = sprintf("%-20s",$rawname);
            $local_version_print = $colors->getColoredString($local_version,"green","black",'%-5s');
            echo $state.$separator.$short_name.$separator.$name.$separator.$local_version_print.$separator.$online_version_print;
            echo "\n";
        }
    }
}

function cli_plugin_install($rawname,$version='') {

    global $PLUGIN_DIR;
    
    $owner_data = posix_getpwuid(fileowner($PLUGIN_DIR));
    $owner_name = $owner_data['name'];

    $colors = new Colors();

    $fop2_mirror = get_fastest_mirror();

    $plugin_sorted=array();
    list ($fop2version,$fop2registername,$fop2licensetype) = fop2_get_version();

    $fop2version   = normalize_version($fop2version);
    $fop2version   = intval($fop2version);

    $plugin_online = plugin_get_online($fop2_mirror);
    $plugin_local  = get_installed_plugins('version');

    if(count($plugin_online)==0) {
        $error = $colors->getColoredString("Could not connect to online repositories, unable to install","yellow","red");
        echo "$error\n\n";
        exit(1);
    }

    if(array_key_exists($rawname,$plugin_local) && $version=='') {
        $out = $colors->getColoredString(sprintf("Plugin %s already installed",$rawname),"yellow","black");
        echo "$out\n";
        exit(1);
    } else {
        $good=0;
        foreach($plugin_online as $idx=>$data) {
            if($data['rawname']==$rawname) {
                $latestversion = $data['version'];
                $good=1;
            }
        }
        if($good==0) {
            $out = $colors->getColoredString(sprintf("Plugin %s does not exist. Verify the name.",$rawname),"yellow","black");
            echo "$out\n";
            exit(1);
        }
        $getversion = ($version<>'')?$version:$latestversion;
        $itemid = $rawname."-".$getversion;
        ob_start();
        plugin_download($itemid,$fop2_mirror);
        $return=ob_get_contents();
        ob_end_clean();
        if(preg_match("/ERROR/",$return)) {
            $code = substr($return,7);
            $out = $colors->getColoredString(sprintf("Error installing %s. Code: %s",$itemid,$code),"yellow","black");
            echo "$out\n";
            exit(1);
        } else {
            exec("find ${PLUGIN_DIR}$rawname -name \* -exec chown $owner_name.$owner_name {} +");
            $out = $colors->getColoredString(sprintf("Plugin %s version %s installed successfully",$rawname,$getversion),"green","black");
            echo "$out\n";
            exit(1);
        }
    }

}

function cli_plugin_uninstall($rawname) {
    global $PLUGIN_DIR;
    $plugin_local  = get_installed_plugins('version');
    $colors = new Colors();
    if(array_key_exists($rawname,$plugin_local)) {
        plugin_delete($rawname);
        $deldir = substr(escapeshellarg($PLUGIN_DIR.$rawname),1,-1);
        if(is_dir($deldir)) {
           // could not delete?
            $out = $colors->getColoredString(sprintf("Plugin %s directory could not be removed. Check file permissions and ownership.",$rawname),"yellow","black");
            echo "$out\n";
            exit(1);
        } else {
            $out = $colors->getColoredString(sprintf("Plugin %s uninstalled successfully.",$rawname),"green","black");
            echo "$out\n";
            exit(0);
        }
    } else {
        $out = $colors->getColoredString(sprintf("Plugin %s is not installed. Aborting.",$rawname),"yellow","black");
        echo "$out\n";
        exit(1);
    }
}

function long_version($plugversion) {
    $partes = preg_split('/\./',$plugversion);
    if(!isset($partes[2])) $partes[1]=0;
    if(!isset($partes[2])) $partes[2]=0;
    $superversion = $partes[0]*100000 + $partes[1]*1000 + $partes[2]*10;
    return intval($superversion);
}

class Colors {
    private $foreground_colors = array();
    private $background_colors = array();

    public function __construct() {
        // Set up shell colors
        $this->foreground_colors['black'] = '0;30';
        $this->foreground_colors['dark_gray'] = '1;30';
        $this->foreground_colors['blue'] = '0;34';
        $this->foreground_colors['light_blue'] = '1;34';
        $this->foreground_colors['green'] = '0;32';
        $this->foreground_colors['light_green'] = '1;32';
        $this->foreground_colors['cyan'] = '0;36';
        $this->foreground_colors['light_cyan'] = '1;36';
        $this->foreground_colors['red'] = '0;31';
        $this->foreground_colors['light_red'] = '1;31';
        $this->foreground_colors['purple'] = '0;35';
        $this->foreground_colors['light_purple'] = '1;35';
        $this->foreground_colors['brown'] = '0;33';
        $this->foreground_colors['yellow'] = '1;33';
        $this->foreground_colors['light_gray'] = '0;37';
        $this->foreground_colors['white'] = '1;37';

        $this->background_colors['black'] = '40';
        $this->background_colors['red'] = '41';
        $this->background_colors['green'] = '42';
        $this->background_colors['yellow'] = '43';
        $this->background_colors['blue'] = '44';
        $this->background_colors['magenta'] = '45';
        $this->background_colors['cyan'] = '46';
        $this->background_colors['light_gray'] = '47';
    }

    // Returns colored string
    public function getColoredString($string, $foreground_color = null, $background_color = null, $format = '') {

        $colored_string = "";

        // Check if given foreground color found
        if (isset($this->foreground_colors[$foreground_color])) {
            $colored_string .= "\033[" . $this->foreground_colors[$foreground_color] . "m";
        }
        // Check if given background color found
        if (isset($this->background_colors[$background_color])) {
            $colored_string .= "\033[" . $this->background_colors[$background_color] . "m";
        }

        // Add string and end coloring
        if($format=='') {
            $colored_string .=  $string;
        } else {
            $colored_string .= sprintf($format,$string);
        }

        $colored_string .=  "\033[0m";

        return $colored_string;

    } 

    // Returns all foreground color names
    public function getForegroundColors() {
        return array_keys($this->foreground_colors);
    }

    // Returns all background color names
    public function getBackgroundColors() {
        return array_keys($this->background_colors);
    }
}
