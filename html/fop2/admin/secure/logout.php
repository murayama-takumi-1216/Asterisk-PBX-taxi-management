<?php
require_once("../config.php");
require_once("../functions.php");
require_once("secure-functions.php");
require_once("../system.php");

if(USE_BACKEND_AUTH==true) {
    // do not destroy backend session on logout
    if($config_engine=='issabel') {
        if(!isset($_SESSION['issabel_user'])) {
            flush_session();
        }
    } else if ($config_engine=='freepbx') {
        if(!isset($_SESSION['AMP_user'])) {
            flush_session();
        }
    } else {
        flush_session();
    }
} else {
    flush_session();
}

if(isset($_GET['redirect'])) {
    Header("Location: ".$_GET['redirect']);
}
