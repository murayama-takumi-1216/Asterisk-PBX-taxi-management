
</div>
</div>

</div>
<div class='footer'>
<?php echo $APPNAME;?> - <?php echo sprintf(__("Version %s"), $version);?>
</div>

<script src="js/bootstrap.min.js"></script>
<script src="js/bootstrap-switch.min.js"></script>
<script src="js/bootstrap.file.input.js"></script>


<?php


if(isset($before_end_body)) {
    echo $before_end_body;
}
?>
<script>
function fop2Reload() {
    var myhost = 'fop2reload.php';

     alertify.confirm('','<?php echo __('Are you sure?'); ?>', function() {
             $.post(myhost,'reload=1',function() {alertify.success(lang['Panel Reloaded']); $('#fop2reload').hide();});
             return true;
     }, function() {
        // cancel
     }).set({
        labels: {
            ok: '<?php echo __('Accept');?>',
            cancel: '<?php echo __('Cancel');?>'
        },
        closable: false
     });

}

function asteriskReload() {
    var myhost = 'asteriskreload.php';

    alertify.confirm('','<?php echo __('Are you sure?'); ?>', function() {
             $('#statusmodal').modal();
             $.post(myhost,'reload=1',function() {alertify.success(lang['Asterisk Reloaded']); $('#statusmodal').modal('hide'); $('#fop2reload').hide();});
             return true;
     }, function() { 
       // cancel 
     }).set({
        labels: {
            ok: '<?php echo __('Accept');?>',
            cancel: '<?php echo __('Cancel');?>'
        },
        closable: false
     });


    return false;
}

</script>
<?php 
echo Kint_Js_Renderer::$cache;
?>
</body>
</html>
