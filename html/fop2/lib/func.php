<?php

function echodebug($str,$extraclass='') {
    if(!DEBUG) return;

    $numargs = func_num_args();
    $args    = func_get_args();

    if(strlen($str)==0) {
        return;
    }

    echo "<div class='debug $extraclass'>$extraclass";
    $format = charset_decode_utf8(array_shift($args));

    if ($numargs > 1){
        echo vsprintf($format, $args);
    } else {
        echo $format;
    }

    echo "</div>";
}

function sqldebug($str) {
    $numargs = func_num_args();
    $args    = func_get_args();

    if(strlen($str)==0) {
        return;
    }
       
    echo "<div class='sqldebug'>";

    $format = charset_decode_utf8(array_shift($args));

    if ($numargs > 1){
        echo vsprintf($format, $args);
    } else {
        echo $format;
    }
    echo "</div>";
}

function trans($str) {
    // Funcion de traduccion de textos para applications
    // multilenguage
    global $lang; 
    global $language; 
    global $traduccionesQueFaltan;
    global $CONFIG;

    $numargs = func_num_args();
    $args    = func_get_args();

    $format  = array_shift($args);

    if (array_key_exists($str, $lang[$language])){
        $format = $lang[$language][$str];
    } else {
        if (isset($CONFIG['ResaltarSinTraduccion'][''])) {
            $format = "{"."$str"."}";
            if (!array_key_exists($str, $traduccionesQueFaltan)) {
                $traduccionesQueFaltan[$str] = $str;
            }
        } else {
            $format = $str;
        }
        // guarda los str faltantes de un idioma en un archivo 
        // (para usar mientras se esta traducciendo el sitio)
       
        /* 
        $lineaAgregar = '$lang[$mylang][\''.$str.'\'] = "'.$str.'";';
        $archifaltante = dirname(__FILE__)."/../lang/faltantes_".$language.".php";
        if(!is_file($archifaltante)) {
            $fh = fopen($archifaltante, "w") or echodebug("No pude crear archivo $archifaltante");
            fclose ($fh);
        } 
        if(is_file($archifaltante)) {
            $fh = fopen($archifaltante, "r+") or echodebug("No pude abrir $archifaltante");
            if($fh) {
                $existe = false;
                while (!feof($fh)) {
                    $bufer = fgets($fh, 4096);
                    if(trim($bufer) == $lineaAgregar){
                        $existe = true;
                        break;
                    }
                }
                if (!$existe) {
                    fwrite($fh, $lineaAgregar."\n");
                }

                fclose ($fh);
              }
        }
        */
        
    }
    //$format = charset_decode_utf8($format);

    if ($numargs > 1){
        return vsprintf($format, $args);
    } else {
        return $format;
    }
}

function set_config($DBSETUP) {
    // Function to set CONFIG values from "setup" table
    // populating the global $CONFIG variable for the application
    if(!isset($DBSETUP)) { return; }
    global $db,$CONFIG;

    $res = $db->consulta("desc $DBSETUP");
    if(!$res) {
      $querycreate = "create table `$DBSETUP` (
        `id` int(11) not null auto_increment,
        `keyword` varchar(150) not null,
        `parameter` varchar(150) default '',
        `value` varchar(150) default '',
        PRIMARY KEY  (`id`),
        UNIQUE KEY `keypar` (`keyword`,`parameter`)
      )";
      $res = $db->consulta($querycreate);
    }
 
    $res = $db->consulta('SELECT keyword,parameter,value FROM '.$DBSETUP);
    while($row=$db->fetch_assoc($res)) {
        $CONFIG[$row['keyword']][$row['parameter']]=$row['value'];
    }
}

function get_language() {
    // Function que recupera el idioma de la aplicacion
    // tomando el nombre de usuario del framework de 
    // autenticacion, con idioma por defecto ingles
    global $CONFIG;
    if(isset($_SESSION[MYAP]['AUTHVAR']['login'])) {
        $userl = $_SESSION[MYAP]['AUTHVAR']['login'];
        if(isset($CONFIG['language'][$userl])) {
            $language = $CONFIG['language'][$userl];
        } else {
            $language = "";
        }
        if($language=="") {
            $language = $CONFIG['language'][''];
        }
    } else {
        $language = $CONFIG['language'][''];
    }

    if($language=="") { $language="en"; }
    return $language;
}


function field_validator($field_descr, $field_data, $field_type, $min_length="", $max_length="", $field_required=1) {
    /*
    Field validator:
    This is a handy function for validating the data passed to
    us from a user's <form> fields.

    Using this function we can check a certain type of data was
    passed to us (email, digit, number, etc) and that the data
    was of a certain length.
    */
    # array for storing error messages
    global $messages;
    
    # first, if no data and field is not required, just return now:
    if(!$field_data && !$field_required){ return; }

    # initialize a flag variable - used to flag whether data is valid or not
    $field_ok=false;

    # this is the regexp for email validation:
    $email_regexp="^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|";
    $email_regexp.="(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$";

    # a hash array of "types of data" pointing to "regexps" used to validate the data:
    $data_types=array(
        "email"=>$email_regexp,
        "digit"=>"^[0-9]$",
        "number"=>"^[0-9]+$",
        "alpha"=>"^[a-zA-Z]+$",
        "alpha_space"=>"^[a-zA-Z ]+$",
        "alphanumeric"=>"^[a-zA-Z0-9]+$",
        "alphanumeric_space"=>"^[a-zA-Z0-9 ]+$",
        "string"=>""
    );
    
    # check for required fields
    if ($field_required && empty($field_data)) {
        $messages[] = "$field_descr is a required field.";
        return;
    }
    
    # if field type is a string, no need to check regexp:
    if ($field_type == "string") {
        $field_ok = true;
    } else {
        # Check the field data against the regexp pattern:
        $field_ok = preg_match("/{$data_types[$field_type]}/", $field_data);
    }
   
    # if field data is bad, add message:
    if (!$field_ok) { 
        $messages[] = "Please enter a valid $field_descr.";
        return;
    }

    # field data min length checking:
    if ($field_ok && ($min_length > 0)) {
        if (strlen($field_data) < $min_length) {
            $messages[] = "$field_descr is invalid, it should be at least $min_length character(s).";
            return;
        }
    }

    # field data max length checking:
    if ($field_ok && ($max_length > 0)) {
        if (strlen($field_data) > $max_length) {
            $messages[] = "$field_descr is invalid, it should be less than $max_length characters.";
            return;
        }
    }
}


function charset_decode_utf8($string) {
    /* Only do the slow convert if there are 8-bit characters */
    /* avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that */
    if (! preg_match("/[\200-\237]/", $string) and ! preg_match("/[\241-\377]/", $string))
        return $string;

    // decode three byte unicode characters
    $string = preg_replace("/([\340-\357])([\200-\277])([\200-\277])/e",
    "'&#'.((ord('\\1')-224)*4096 + (ord('\\2')-128)*64 + (ord('\\3')-128)).';'",
    $string);

    // decode two byte unicode characters
    $string = preg_replace("/([\300-\337])([\200-\277])/e",
    "'&#'.((ord('\\1')-192)*64+(ord('\\2')-128)).';'",
    $string);

    return $string;
}

function limpiarEntidades($param) {
    return is_array($param) ? array_map('limpiarEntidades', $param) : htmlentities($param, ENT_QUOTES);
}

function funcErrorHandler($errno, $errstr, $errfile, $errline) {
    switch ($errno) {
        case E_USER_ERROR:
            $extraclass='error';
            break;
        case E_USER_WARNING:
            $extraclass='warning';
            break;
        case E_USER_NOTICE:
            $extraclass='notice';
            break;
        default:
           $extraclass="notice";
           break;
    }
    echodebug("<h2>$errfile (line $errline)</h2><br/>$errstr",$extraclass);
}

function PHP4_array_diff_key() {
    $arrs = func_get_args();
    $result = array_shift($arrs);
    foreach ($arrs as $array) {
        foreach ($result as $key => $v) {
            if (array_key_exists($key, $array)) {
                unset($result[$key]);
            }
        }
    }
    return $result;
}

?>
