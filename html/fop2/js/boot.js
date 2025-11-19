jQuery.ajaxSetup({async: false});
jQuery.getScript("js/presence.js", function() {
    if(typeof(applicationTitle)!='undefined') {
        document.title=applicationTitle;
    }
    var ret = jQuery.getScript("js/lang_"+language+".js");
    if(ret==1) {
        jQuery.getScript("js/lang_en.js");
    }
});
jQuery.getScript("js/fop2.js");
jQuery.getScript("js/jcallback.js");
jQuery.ajaxSetup({async: true});
$('head').append( $('<link rel="stylesheet" type="text/css" />').attr('href', 'css/theme/theme.css') );
$('head').append( $('<link rel="stylesheet" type="text/css" />').attr('href', 'css/custom_theme/theme.css') );

