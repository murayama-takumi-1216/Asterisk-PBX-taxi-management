<?php
require_once("config.php");
require_once("functions.php");
require_once("dblib.php");
require_once("asmanager.php");
require_once("system.php");
require_once("dbconn.php");
include("dbsetup.php");
require_once("secure/secure-functions.php");
require_once("secure/secure.php");
include("headerbs.php");

$mirror   = get_fastest_mirror();

$file   = isset($_REQUEST['file'])?$_REQUEST['file']:'';
$done   = isset($_REQUEST['done'])?$_REQUEST['done']:0;

if($file=='') { die(); }
if($mirror=='') { $mirror=urlencode("http://download.fop2.com"); }

function endfile() {
    echo "<script src='js/bootstrap.min.js'></script>\n";
    echo "</body></html>";
    die();
}

if($done==0) {
?>

<div style='width:450px' class='center-block'>
  <div class="progress progress-striped active">
    <div id='pbar' class="progress-bar"  role="progressbar" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
  </div>
</div>

<?php
} else {

$directory = dirname(__FILE__)."/_cache/";
$filename  = $directory.substr(escapeshellarg($file),1,-1).".tgz";

if(!is_readable($filename)) {
    echo "<div class='alert alert-danger'>\n";
    echo sprintf(__('Cannot read file %s for extraction'),$filename);
    echo "</div>\n";
    endfile();
}

if(!is_dir($directory)) {
    echo "<div class='alert alert-danger'>\n";
    echo sprintf(__('Extraction directory %s does not exists!'),$directory);
    echo "</div>\n";
    endfile();
}

exec("tar zxf ".$filename." -C ".$directory, $output, $exitcode);

if ($exitcode != 0) {
    echo "<div class='alert alert-danger'>\n";
    echo sprintf(__('Error while extracting file %s into directory %s'),$filename,$directory);
    echo "</div>\n";
    endfile();
} else {
    @unlink($filename);
    $cache_in_extract_dir = $directory."admin/_cache/";
    if(is_dir($cache_in_extract_dir)) { fop2_rrmdir($cache_in_extract_dir); }
    if(is_readable($directory."options.txt")) { unlink($directory."options.txt"); }
    echo "<div class='alert alert-success'>\n";
    echo __('File extracted successfully!');
    echo "</div>\n";
}

// Copy current files as backup
$downloaded_update_dir = $directory."admin";

if(!is_dir($downloaded_update_dir)) {
    echo "<div class='alert alert-danger'>\n";
    echo sprintf(__('Problem trying to find the extracted upgrade files. There is no %s directory.'),$downloaded_update_dir);
    echo "</div>\n";
    endfile();
} else {
    // copy recursively the extracted update to same dir

    // backup config.php
    exec("mv ".dirname(__FILE__)."/config.php ".dirname(__FILE__)."/config.bak.php", $output, $exitcode);

    smart_copy($downloaded_update_dir,dirname(__FILE__));

    // remove the extracted update dir
    fop2_rrmdir($downloaded_update_dir);

    // restore config.php
    // todo
    exec("mv ".dirname(__FILE__)."/config.bak.php ".dirname(__FILE__)."/config.php", $output, $exitcode);

    echo "<div class='alert alert-success'>\n";
    echo sprintf(__('%s upgraded!'),$APPNAME);
    echo "</div>\n";
}

?>
<script>

$(document).ready(function() {
    showParentClose();
});

function showParentClose() {
    var el = window.parent.document.getElementById('modalclose');
    $(el).show();
}
</script>
<?php
endfile();
}

// Is is not fully downloaded, continue with the 
// ajax background job
if($done==0) {

?>
<script>
isDone=0;

(function worker() {
    if (typeof starting == 'undefined') {
        starting=0;
    } else {
        starting=1;
    };

    $.ajax({
        url: 'chunkdonwloadhelper.php',
        async: true,
        type: 'POST',
        dataType: 'json',
        data: { 
            isStart: starting,
            file: '<?php echo $file;?>',
            mirror: '<?php echo $mirror;?>'
        },
        success: function(data) {
            debug('success'); 
            var curSize = parseFloat(data.currentSize);
            var expSize = parseFloat(data.expectedSize);
            var Status  = data.Status;

            if(Status!='200') { debug('status '+Status); debug('finish'); isDone=1; }
            
            if(data.currentSize == data.expectedSize) {
                isDone=1;
                $('#pbar').css('width','100%');
                $('#pbar').html('100%');
                debug('termine');
                setTimeout(function() { 
                   var nuevo = window.location.href;
                   nuevo +="&done=1";
                   window.location.href=nuevo;
                },2000);
            } else {
                var percent = parseInt ( (curSize * 100) / expSize );
                $('#pbar').css('width',percent+'%');
                $('#pbar').html(percent+'%');
            }
        },
        complete: function(xhr,estado) {
            if(estado=='success') {
                debug(estado);
                if(isDone==0) {
                    setTimeout(worker, 10);
                }
            }
        }
     })
})();
</script>
<?php } ?>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>
