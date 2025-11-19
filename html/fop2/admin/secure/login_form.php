<?php
require_once('config.php');
require_once('functions.php');
require_once('system.php');
require_once('dblib.php');
require_once('asmanager.php');
require_once('http.php');
require_once('dbconn.php');

if(!isset($error)) { $error=''; }

if(!isset($_POST['user'])) {
   $user     = '';
   $password = '';
} else {
   $user     = $_POST['user'];
   $password = $_POST['password'];

   $row = check_pass($user, $password);

   if(isset($row['error'])) {
      $error = $row['error'];
      // if we do not have enough privileges, check for main entry pages to log into something at least
      if($error=='Not enough privileges' && isset($row['userlevel'])) {
         $mainpages=array();
         $mainpages['Users']      = 'pagebs.fop2users.php';
         $mainpages['Buttons']    = 'pagebs.fop2buttons.php';
         $mainpages['Groups']     = 'pagebs.fop2groups.php';
         $mainpages['Templates']  = 'pagebs.fop2templates.php';
         $mainpages['Permitions'] = 'pagebs.fop2permissions.php';
         foreach($mainpages as $name=>$url) {
             if(check_acl($name,$row['numlevel'])) {
                 unset($row['error']);
                 init_session($row);
                 header("Location: $url");
                 break;
             }
         }
      }
     
   } else {
      init_session($row);
      header("Location: ".SELF);
   }
}

include("headerbs.php");

echo "<div class='wrap'>\n";

include("menu.php");

echo "<div class='content'>\n";

?>

<div class="row" style='margin-top:30px;'>
    <div class="col-md-4 col-md-offset-4">

<div class="panel panel-default">
  <div class="panel-heading">
      <img src='<?php echo $LOGO;?>' style='border:0; margin-top:-6px; margin-left:-10px;' alt='logo'/>&nbsp;<a href='<?php echo $rootdir;?>'><?php echo $LOGONAME;?></a>
  </div>
  <div class="panel-body">

        <form role="form" method='post' action='<?php echo SELF?>'>
            <br/>
            <div class="form-group">
                <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <input type="text" name="user" id="user" class="form-control input-lg" placeholder="<?php echo __('User');?>" value='<?php echo $user;?>'>
                </div>
            </div>
            <div class="form-group">
                <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                <input type="password" name="password" id="password" class="form-control input-lg" placeholder="<?php echo __('Password');?>" value='<?php echo $password;?>'>
                </div>
            </div>
            <hr/>

            <div class="row">
                <div class="col-xs-6 col-md-6 col-md-offset-3 col-xs-offset-3"><button class="btn btn-success btn-block btn-lg"><?php echo __('Log in');?></button></div>
            </div>
        </form>

  </div>
</div>


    </div>
</div>

</div>

<div class="push"></div>
</div>
<script>
$(document).ready(function() {
//document.cookie="context=0";
<?php
if($error<>'') {
?>
    alertify.error('<?php echo $error;?>');
<?php
}
?>
});
</script>
<?php
include("footerbs.php");
