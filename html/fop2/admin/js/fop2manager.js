function debug(message) {
    if (window.console !== undefined) {
        console.log(message);
    }
}

function isEmpty( inputStr ) { if ( null == inputStr || '' == inputStr ) { return true; } return false; }

function isInteger ( a ) {  if (a == parseInt(a)) { return true; } return false; }

function fop2_removeSection(el) {
    var partes = el.split("-");
    rawname = partes[1];
    section = partes[2];
    $('#form-'+rawname+'-'+section).remove();
    return false;
}

function fop2_saveConfig(rawname,textok) {
    for (var key in iseditor) { iseditor[key].save(); }
    var mydata = $('#plugin_'+rawname).serialize();
    var myhost = window.location.href;
    $('#statusmodal').modal();
    $.post(myhost,mydata+'&plugin='+rawname+'&action=savePluginConfig',function() {alertify.success(textok); $('#statusmodal').modal('hide');});
    return false;
}

function fop2_addConfig(rawname,dest,text,errorexist,errorempty) {

    alertify.prompt(text, '', function(e, section) {
        // section is the input text
        if (e) {
           section = section.replace(/[^a-z0-9]/gi,'');
           if(section.length==0) {
               alertify.error(errorempty);
               return false;
           }
           if($('#form-'+rawname+'-'+section).length>0) {
               alertify.error(errorexist);
               return false;
           }

           var cloned = $("#form-"+rawname+"-skeleton").clone();
           cloned.find("*[id]").andSelf().each(function() { $(this).attr("id", function (i,id){ var partes = id.split('-'); partes.pop(); return partes.join('-')+'-'+section; }); });
           cloned.find("*[name]").andSelf().each(function() { $(this).attr("name", function (i,name){ if(typeof name != 'undefined') { var partes = name.split('-'); partes.pop(); return partes.join('-')+'-'+section; }}); });
           cloned.find("#legend-"+rawname+"-"+section).html(section);
           cloned.appendTo($('#'+dest));
           $('#form-'+rawname+'-'+section).show();
           set_editable_fields();
           return true;
        }
    }).set({
        labels: { ok: lang['ok'], cancel: lang['cancel'] },
        title: '',
        type: 'text',
        closable: false
     });
    return false;
}

function setGlobalPlugin(el) {
    if($(el).is(':checked')) { enable=1; } else { enable=0; }
    var partes = $(el).attr('id').split("_");
    var rawname = partes[1];
    var mydata = "action=setglobal&fop2plugin="+rawname+"&fop2globalenabled="+enable;
    var myhost = window.location.pathname;
    $.get(myhost,mydata); 
}

var fixHelper2 = function(e, tr) {
    var originals = tr.children();
    var helper = tr.clone();
    helper.children().each(function(index)
    {
      // Set helper cell sizes to match the original sizes
      $(this).width(originals.eq(index).width())
    });
    return helper;
};

function set_editable_fields() {
    $(".editable").change(function() {
        if($(this).is("select")==true) {
            $(this).blur();
            $(this).focus();
        }
    });

    $(".editable").blur(function() {
        if($(this).val() != $(this).attr('origval')) {
            var mydata = "field="+encodeURIComponent($(this).attr('id'))+"&value="+encodeURIComponent($(this).val().trim())+"&action=saveField";
            var ref=$(this);
            $(this).addClass('loading');
            $('#ajaxstatus').show();
            $.post(window.location.href, mydata, function(data) {
                  if(data.indexOf('ERROR')>0) {
                      ref.effect('highlight',{color:"#D02020"},3000);
                  } else {
                      $('#ajaxstatus').hide();
                      $('#fop2reload').show();

                      ref.removeClass('loading');
                      ref.animate({backgroundColor: '#BCED91'},200,function() { ref.animate({backgroundColor: '#FFF'},200); } )
                      ref.attr('origval',ref.val().trim());
                      ref.val(ref.attr('origval'));
                  }
            });
        }
    });
}

function ready_buttons() {


    $('#csvupload').change(function(e){
        $in=$(this);
        var valor = $in.val();
        rest = valor.replace("C:\\fakepath\\","");
        $('#csvfilename').html(rest);
    });

    $('table').each(function() {
        var tableid = this.id;
        var offset = $(this).attr('offset');
        if(tableid!='') {
            $("#"+tableid+" tbody").sortable({helper: fixHelper2, update: function(event,ui) { 
                serial = $('#'+tableid+' tbody').sortable('serialize'); 
                var mydata = "action=sort&offset="+offset+"&table="+tableid+"&"+serial;
                $.post(window.location.href,mydata); 
                $('#fop2reload').show();
                } 
            });
        }
    });

    $(".editable").each(function() {
        var val = $(this).val();
        if($(this).attr('type') == 'checkbox') {
            if($(this).is(':checked')) {
                val = 1;
                $(this).parents("tr:first").fadeTo('slow',0.5);
            } else {
                val = 0;
            }
            $(this).val(val);
            
        }
        $(this).attr('origval',val);
    });

    set_editable_fields();

    // For auto saving after 4 seconds, on IE (propertychange) and modern browsers (input)
    var propertyChangeUnbound = false;
    $(".editable").on("propertychange", function(e) {
        if (e.originalEvent.propertyName == "value") {
            ref = $(this);
            if(typeof editfield !== "undefined") { clearTimeout(editfield); }
            editfield = setTimeout(function() { ref.blur(); ref.focus();}, 4000);
        }
    });

    $(".editable").on("input", function() {
        if (!propertyChangeUnbound) {
            $(".editable").unbind("propertychange");
            propertyChangeUnbound = true;
        }
        ref = $(this);
        if(typeof editfield !== "undefined") { clearTimeout(editfield); }
        editfield = setTimeout(function() { ref.blur(); ref.focus()}, 4000);
    });


    $('.chosen-select-100').chosen({disable_search: true, skip_no_results: true, width:'100px'});

    $(".chk").bootstrapSwitch();
    $(".chk").on('switchChange.bootstrapSwitch', function (e, data) {
        $(e.target).attr('origval',2);
        if(data===false) {
            $(e.target).parents("tr:first").fadeTo('slow',1.0);
            $(e.target).val('0');
        } else {
            $(e.target).parents("tr:first").fadeTo('slow',0.5);
            $(e.target).val('1');
        }
        $(e.target).blur();
    });
    $('.advanced').hide();
    $('#btncontainer').removeClass('hidden');

    $('.head').stick_in_parent({offset_top:40});

}

function ready_contexts() {

    $(".editable").each(function() {
        var val = $(this).val();
        if($(this).attr('type') == 'checkbox') {
            if($(this).is(':checked')) {
                val = 1;
                $(this).parents("tr:first").fadeTo('slow',0.5);
            } else {
                val = 0;
            }
            $(this).val(val);
            
        }
        $(this).attr('origval',val);
    });

    set_editable_fields();

    $(".chk").bootstrapSwitch();
    $(".chk").on('switchChange.bootstrapSwitch', function (e, data) {
        $(e.target).attr('origval',2);
        if(data===false) {
            $(e.target).parents("tr:first").fadeTo('slow',1.0);
            $(e.target).val('0');
        } else {
            $(e.target).parents("tr:first").fadeTo('slow',0.5);
            $(e.target).val('1');
        }
        $(e.target).blur();
    });
}

$(window).on('load',function() {
    // main content full width
    var screenHeight = $(document).height(); 
    var row = $('.main');

    // Assign that height to the .row
    row.css({
        'height': screenHeight + 'px',
    });

    // This makes the div's height responsive when you resize the screen or the window of the browser.
    $(window).resize(function () {
        screenHeight = $(window).height();
        rowpxHeight = row.css('height');
        rowHeight = rowpxHeight.slice(0,-2);
        if(parseInt(rowHeight)<parseInt(screenHeight)) {
            row.css({
                'height': screenHeight + 'px',
            });
        }
    });
    // end main content full width


});

$(document).ready(function() {

    $('[data-toggle=offcanvas]').click(function() {
      $('.row-offcanvas').toggleClass('active');
    });

    // main content full width
    var screenHeight = $(document).height(); 
    var row = $('.main');

    // Assign that height to the .row
    row.css({
        'height': screenHeight + 'px',
    });


    $('.ttip').popover();
    editfield = '';

    $('#selectlanguage').on('change',function(event,el) {
        setLang(el.selected);
    });

    $('.chosen-select').chosen({disable_search: true, skip_no_results: true});
    $('.chosen-select-create').chosen({create_option: true, skip_no_results: true});
    $('.chosen-select-150').chosen({disable_search: true, skip_no_results: true, width:'150px'});
    $('.dropdown-toggle').dropdown();

    $('table td.clickable').on('click',function(ev) { console.log(ev); partes = ev.currentTarget.id.split("_"); if(partes[0]=='no') { return false; } setEdit(partes[1]); });

    if (window.location.pathname.indexOf('/pagebs.fop2buttons.php')>=0) {
        ready_buttons();
    }
    if (window.location.pathname.indexOf('/pagebs.fop2contexts.php')>=0) {
        ready_contexts();
    }
    if (window.location.pathname.indexOf('/pagebs.fop2plugins.php')>=0 || window.location.pathname.indexOf('/pagebs.fop2users.php')>=0) {
        $(".chk").bootstrapSwitch();
    }

    $("#fop2navbar").bootstrapDropdownOnHover();

    if($('#userfilter').length > 0 ) { // estamos en la solapa de usuarios

        $('#userfilter').on("input", function() {
            var val = this.value;
            console.log(val);
            filter_user_list(val);
        });

        $('#userfilter').keydown(function(event) { if(event.which == 27) { $('#userfilter').val(''); filter_user_list('');}});

        $('#userfilter').val($('#ffilter').val());

        filter_user_list($('#userfilter').val());
    }

});

function filter_user_list(val) {
    //$('#tableusers').find('td.clickable').each(function() { console.log($(this).text()); console.log($(this).parent().is(':visible'));});

    var perpage = parseInt( $('#perpage').val());
    var curpage = parseInt($('#fnumpage').val());

    $('#tableusers').find('td.clickable').each(function() { 
        currentlabel = $(this).text().toLowerCase();
        matchwith    = val.toLowerCase();
        var patt1 = new RegExp(matchwith);
        if (patt1.test(currentlabel) === true) {
            $(this).parent().show();
        } else {
            $(this).parent().hide();
        }
    });

    if(val=='') {

        $('#tableusers').pageMe( {
             pagerSelector:'#myPager',
             showPrevNext:true,
             hidePageNumbers:false,
             perPage:perpage,
             numbersPerPage:4,
             curPage:curpage
        });


//        $('#tableusers').pageMe({pagerSelector:'#myPager',showPrevNext:true,hidePageNumbers:false,perPage:5,numbersPerPage:4,curPage:1});
    } else {
        $('#myPager').empty();
    }

}

async function postData(url = "", data = "") {
    const response = await fetch(url, {
        method: "POST",
        mode: "cors",
        cache: "no-cache",
        credentials: "same-origin",
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        redirect: "follow",
        referrerPolicy: "no-referrer",
        body: data
    });
    return response; // parses JSON response into native JavaScript objects
}

function setLang(lang) {
    var loc = window.location.pathname.split('/').slice(0,window.location.pathname.split('/').indexOf('plugins')).join('/');  // root for fop2manager
    var langcookie = 'lang='+lang+";path="+loc;
    document.cookie=langcookie;
    window.location = window.location.href
}

function setContext(context) {
    document.cookie='context='+context+';path=/';
    postData("setcontext.php", "context="+context).then((data) => {
        window.location = window.location.origin + window.location.pathname
    });
}

function getSection(parte,pagina) {
    $('#'+parte).hide();
    $('#spinner').show();
    $('#'+parte).load('pagebs.fop2buttons.php','page='+pagina, function() { $(this).show(); $('#spinner').hide(); ready_buttons(); });
    return false;
}

function check_mass_import() {
    var tieneexten=0;
    var tienedata=0;
    $('#massimport select').each(function() {
       if($(this).val()=='extension') {
           tieneexten=1;
       } else if($(this).val()!='') {
           tienedata=1;
       }
    });
    if(tieneexten==1 && tienedata==1) {
        return true;
    } else {
        if(tieneexten==0) {
            alertify.warning(lang['extensionmissing']);
        }
        if(tienedata==0) {
            alertify.warning(lang['datamissing']);
        }
        return false;
    }
}
