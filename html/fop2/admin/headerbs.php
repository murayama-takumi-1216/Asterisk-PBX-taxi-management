<?php 
$partes = preg_split("|/|",dirname($_SERVER['SCRIPT_NAME']));
$pos = array_search("menu",$partes);
if($pos!==false) {
    // It is a configuration from plugin (inside menu)
    $final = array();
    $pos = $pos - 3;  // We want to remove the 3 paths for plugins/xxxx/menu from result to set basename
    for($i=0;$i<=$pos;$i++) {
        $final[]=$partes[$i];
    }
    $path = $dir = "//".$_SERVER['HTTP_HOST'] . implode("/",$final);
} else {
    $path = $dir = "//".$_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <base href='<?php echo "$path/";?>'>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $APPNAME; ?></title>

    <link rel='shortcut icon' type='image/x-icon' href='./favicon.ico' />
    <!-- Bootstrap -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/font-awesome.min.css" rel="stylesheet">
    <link href="css/bootstrap-theme.css" rel="stylesheet">
    <link href="css/bootstrap-switch.min.css" rel="stylesheet">
    <link href="css/bootstrap.datepicker.css" rel="stylesheet">
    <link href="css/jquery.ui.core.css" rel="stylesheet">
    <link href="css/bootstrap-toggle.min.css" rel="stylesheet">
    <link href="css/chosen.css" rel="stylesheet">
    <link href="css/indexsticky.css" rel="stylesheet">
    <link href="css/animate.css" rel="stylesheet">
    <link href="css/alertify.css" rel="stylesheet">
    <link href="css/alertify-bootstrap.css" rel="stylesheet">
    <link href="css/flags.css" rel="stylesheet">
    <link href="css/jconf.css" rel="stylesheet">
    <link href="css/codemirror.css" rel="stylesheet">
    <link href="css/bootstrap-colorpicker.min.css" rel="stylesheet">
    <link href="css/jquery.autocomplete.css" rel="stylesheet">
    <link href="css/jquery.resizableColumns.css" rel="stylesheet">
    <link href="css/fop2manager.css" rel="stylesheet">

    <script src="js/jquery-1.10.2.min.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/alertify.js"></script>
    <script src="js/fop2manager.js"></script>
    <script src="js/chosen.jquery.js"></script>
    <script src="js/jquery.jconf.js"></script>
    <script src="js/jquery.tools.form.min.js"></script>
    <script src="js/jquery.pageme.js"></script>
    <script src="js/jquery.resizableColumns.min.js"></script>
    <script src="js/jquery.sticky-kit.min.js "></script>
    <script src="js/bootstrap-dropdown-on-hover.js"></script>
    <script src="js/moment-with-locales.js"></script>
    <script src="js/bootstrap.datepicker.js"></script>
    <script src="js/bootstrap-switch.min.js"></script>
    <script src="js/bootstrap-toggle.min.js"></script>
    <script src="js/jquery.autocomplete.js"></script>
    <script src="js/codemirror.js"></script>
    <script src="js/bootstrap-colorpicker.min.js"></script>
    <script src="js/mode/xml.js"></script>
    <script src="js/mode/javascript.js"></script>
    <script src="js/mode/htmlmixed.js"></script>
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="js/html5shiv.js"></script>
      <script src="js/respond.min.js"></script>
    <![endif]-->


    <script>
        var lang = new Object();
        lang['Panel Reloaded']="<?php echo __('Panel Reloaded');?>";
        lang['Asterisk Reloaded']="<?php echo __('Asterisk Reloaded');?>";
        lang['extensionmissing']="<?php echo __('Please assign the extension field to a column');?>";
        lang['datamissing']="<?php echo __('Please assing data columns');?>";
        lang['ok']="<?php echo __('Accept');?>";
        lang['cancel']="<?php echo __('Cancel');?>";
    </script>
    <?php
    if(isset($extra_headers)) {
        echo $extra_headers;
    }

    ?>

  </head>
  <body>

