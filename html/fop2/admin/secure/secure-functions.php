<?php

function ombutel_authenticate_session($session_id) {
    return ombutel_authenticate(array( 'sid' => $session_id ));
}

function ombutel_authenticate_user($username, $password) {
    return ombutel_authenticate(array( 'username' => $username, 'password' => $password ));
}

function ombutel_authenticate(array $postdata) {
    global $OMBUTEL_KEY;
    $postdata['key'] = $OMBUTEL_KEY;
    $curl = curl_init();
    curl_setopt_array(
        $curl,
        array ( 
            CURLOPT_URL => 'http://localhost/api/authenticate',
            CURLOPT_POST => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => $postdata,
        )
    );
    $response = @json_decode(@curl_exec($curl));
    curl_close($curl);
    if ($response && $response->status == 'success') {
        return $response->data->token;
    }
    return false;
}

function is_logged_in(){

    // returns:
    // 0 if not logged in
    // 1 if not enough privileges
    // 2 if logged in and privileges

    global $config_engine, $secure_required_levels;

    $not_enough=0;
    
    $userlevel = isset($_SESSION[MYAP]['AUTHVAR']['level'])?$_SESSION[MYAP]['AUTHVAR']['level']:'admin';

    if(is_array($secure_required_levels)) {
        if(!in_array($userlevel,$secure_required_levels)) {
            $not_enough=1;
        }
    }

    if(($config_engine=='freepbx' || $config_engine=='issabel') && USE_BACKEND_AUTH==true) {
        if(isset($_SESSION['AMP_user'])) {
            if($not_enough==0) {
               if(!isset($_SESSION[MYAP]['AUTHVAR']['level'])) {
                    $row=array();
                    $row['level']='admin';
                    init_session($row);
                }
                return 2;
            } else {
                return 1;
            }
        }
    }

    if ($config_engine == 'ombutel') {
        // Try FOP2 session ID first, then Ombutel. This effectively means
        // that if you are logged into ombutel you cannot log out which is
        // not ideal but fine for our purposes.
        foreach ( array( session_name(), 'sid' ) as $session_name) {
            if (isset($_COOKIE[$session_name])) {
                if (ombutel_authenticate_session($_COOKIE[$session_name])) {
                    $row=array();
                    $row['level']='admin';
                    init_session($row);
                    return 2;
                }
            }
        }
        return 0;
    }

    if(is_file("/etc/elastix.conf")) {
        if(isset($_SESSION['elastix_user']) && USE_BACKEND_AUTH==true) {
            if($not_enough==0) {
                return 2;
            } else {
                return 1;
            }
        }
    }

    if(is_file("/etc/issabel.conf")) {
        if(isset($_SESSION['issabel_user']) && USE_BACKEND_AUTH==true) {
            if($not_enough==0) {
                return 2;
            } else {
                return 1;
            }
        }
    }

    if(isset($_SESSION[MYAP]['AUTHVAR']["loggedIn"]) && $_SESSION[MYAP]['AUTHVAR']["loggedIn"] == 1 ){
        if($not_enough==0) {
            return 2;
        } else {
            return 1;
        }
    } else {
        return 0;
    }

}

function check_pass($login, $password) {

    global $db, $config_engine, $conf, $ADMINUSER, $ADMINPWD, $secure_required_levels;

    if(($config_engine=='freepbx' || $config_engine=='issabel') && USE_BACKEND_AUTH==true) {

        $query = "SELECT username AS login,IF(sections LIKE '%%*%%','admin','user') AS level,username AS name FROM ";
        $query.= $conf['DBNAME'].".ampusers WHERE username='%s' AND password_sha1=sha1('%s')";

        $res = $db->consulta($query,array($login,$password));

        if($db->num_rows($res)==1) {
            $row = $db->fetch_assoc($res);
 
            $not_enough=0;
            $userlevel = $row['level'];
            if(!in_array($userlevel,$secure_required_levels)) {
                $not_enough=1;
            }
             
            if($not_enough==1) {
                $row['error']=__('Not enough privileges');
            } else {
                $row['ok']=1;
            }
        } else {
            $row['error']=__('Invalid Credentials');
        }

    } else if ($config_engine == 'ombutel') {
        $token = ombutel_authenticate_user($login, $password);
        if ($token) {
            @session_destroy(); // Make sure to kill any old session
            // Create our session now, so PHP won't get confused when we try to log out
            session_id($token);
            @session_start();
            return array( 'ok' => 1 );
        }
        return array ( 'error' => __('Invalid Credentials') );

    } else {

        $row = array();
        $row = check_builtin_db_user($login, $password); 
        $not_enough=0;
        $userlevel = isset($row['level'])?$row['level']:'';
        if(is_array($secure_required_levels)) {
            if(!in_array($userlevel,$secure_required_levels)) {
                $not_enough=1;
            }
        }
        if(!isset($row['error'])) {
            if($not_enough==1) {
                $row['error']=__('Not enough privileges');
            } else {
                $row['ok']=1;
                return $row;
            }
        } else {
           $row = array();
           if($ADMINUSER=='fop2admin' && $ADMINPWD=='fop2admin') {
                $row['error']=__('Invalid Credentials');
           } else {
                if($login==$ADMINUSER && $password==$ADMINPWD) {
                    $row['ok']=1;
                    $row['level']='admin';
                } else {
                    $row['error']=__('Invalid Credentials');
                } 
            }
        }
    }
    return $row;
}

function check_builtin_db_user($login, $password) {
    global $db, $config_engine, $conf;

    $query = "SELECT id,user,level FROM fop2managerusers WHERE user='%s' AND password=sha1('%s')";

    $res = $db->consulta($query,array($login,$password));

    if($db->num_rows($res)>0) {
        $row = $db->fetch_assoc($res);
        $letcontext=array();
        $query = "SELECT id_context,context from fop2managerUserContext LEFT JOIN fop2contexts on id_context=fop2contexts.id WHERE id_user='%d'";
        $ris = $db->consulta($query,$row['id']);

        if($db->num_rows($ris)==0) {
            $query = "select id,context from fop2contexts limit 1";
            $ras = $db->consulta($query);
            if($db->num_rows($ras)>0) {
                $myrow = $db->fetch_assoc($ras);
                $_SESSION[MYAP]['AUTHVAR']["context"]=$myrow['context'];
                $_SESSION[MYAP]['AUTHVAR']["contextid"]=$myrow['id'];
            }
        } else {
            $c=0;
            while($rew = $db->fetch_assoc($ris)) {
                $letcontext[]=$rew['id_context'];
                if($c==0) {
                    $_SESSION[MYAP]['AUTHVAR']["context"]=$rew['context'];
                    $_SESSION[MYAP]['AUTHVAR']["contextid"]=$rew['id_context'];
                }
            $c++;
            }
            $finaltenants = implode(",",$letcontext);
            $row['allowed_tenants']=$finaltenants;
        }
    } else {
       $row['error']=__('Invalid Credentials');
    }

    return $row;    
}

function init_session($row) {
    $_SESSION[MYAP]['AUTHVAR']["loggedIn"]        = true;
    $_SESSION[MYAP]['AUTHVAR']["ip"]              = $_SERVER['REMOTE_ADDR'];
    $_SESSION[MYAP]['AUTHVAR']["level"]           = $row['level'];
    $_SESSION[MYAP]['AUTHVAR']["allowed_tenants"] = $row['allowed_tenants'];
}

function flush_session() {
    unset($_SESSION[MYAP]["AUTHVAR"]);
    unset($_COOKIE['context']);
    setcookie("context", "0", time()-3600);
    setcookie(session_name(), null, -1, '/');
    @session_destroy();
    return true;
}

function check_acl($resource, $reqlevellevel=1) {

    global $db, $conf, $levels;

    $mylevel = $levels[$_SESSION[MYAP]['AUTHVAR']["level"]];

    $query="SELECT level FROM fop2manageracl WHERE resource = '%s'";
    $res = $db->consulta($query,$resource);

    if($res) {

        if($db->num_rows($res)==0) {
            if($resource<>'') {
                $query="INSERT INTO fop2manageracl (resource,level) values ('%s','%d')";
                $db->consulta($query,array($resource,$reqlevellevel));
            }
        } else {
            $row = $db->fetch_row($res);
        }

        $query = "SELECT * FROM fop2manageracl WHERE resource='%s' AND %d & level";
        $db->consulta($query,array($resource,$mylevel));
        if($db->num_rows($res)==0) {
            return false;
        } else {
            return true;
        }
    } else {
        // If using Panel Manager without a datbase connection, then let them pass
        return true;
    }

}

