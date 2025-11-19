var selectedContacts = [];

if(typeof(window.parent.lang)!='undefined') {
    lang = window.parent.lang;
} else {
    lang = {};
}

function loadjs() {

    if(parent.permisos.includes('readonly_phonebook')) {
       $('#addnewbutton').hide();
       parent.$('#contactsubmit').hide();
       $('#buttonimport').parent().hide();
       $('#buttonexport').parent().hide();
    }

    if(parent.permisos.includes('broadcast') || parent.permisos.includes('all')) {
       $('#buttonbroadcast').parent().show();
    } else {
       $('#buttonbroadcast').parent().hide();
    }

    if(parent.permisos.includes('conversation') || parent.permisos.includes('all')) {
       $('#buttonconversation').parent().show();
    } else {
       $('#buttonconversation').parent().hide();
    }

    if(typeof parent.setLangContacts != 'undefined') {
        parent.setLangContacts();
    }
    $('#masstags').chosen({
        width: '100%',
        skip_no_results: true,
        placeholder_text_single: lang['Select one option']
    });

    if(typeof(parent.plugins)!='undefined') {
        if(typeof(parent.plugins['phonepro'])=='undefined') {
            $('#buttonbroadcast').hide();
            $('#buttonconversation').hide();
        } else {
            $('#buttonbroadcast').on('click', function(event) {
                event.preventDefault();
                window.parent.openBroadcast();
            });
            $('#buttonconversation').on('click', function(event) {
                event.preventDefault();
                window.parent.openConversation();
            });
        }
    } else {
        $('#buttonbroadcast').hide();
        $('#buttonconversation').hide();
    }

    $('body').on('click','.list-group-item',function(event) {

        contact_id = $(event.currentTarget).attr('data-contactid');
        number = $(event.currentTarget).find('.ztop').attr('data-number');
        var index = contact_id+'_'+number;

        if(event.ctrlKey===true || event.metaKey===true) {
            if(selectedContacts.includes(index)) {
                // console.log("quitar "+index);
                // remove if already selected
                const idx = selectedContacts.indexOf(index);
                const x = selectedContacts.splice(idx, 1);
            } else {
                // select element
                // console.log("agregar "+index);
                selectedContacts.push(index);
            }
            // console.log(selectedContacts);
            markSelectedContacts();
        } else if(event.shiftKey===true) {
            if (selectedContacts.length > 0) {
                window.getSelection()?.removeAllRanges();
                let lastSelected = selectedContacts[selectedContacts.length - 1];

                let inputIDs=$('li.list-group-item.chat').map(function(){
                    return this.id
                }).get();

                if($('#'+index).isAfter($('#'+lastSelected))) {

                    let result = inputIDs.splice(inputIDs.indexOf(lastSelected),inputIDs.indexOf(index)-inputIDs.indexOf(lastSelected)+1);
                    // console.log("result",result);
                    result.map(function(el) {
                       // console.log("add to selected "+el);
                       selectedContacts.push(el);
                    });
                    markSelectedContacts();
                } else {
                    let result = inputIDs.splice(inputIDs.indexOf(index),inputIDs.indexOf(lastSelected)-inputIDs.indexOf(index)+1);
                    // console.log(index+' antes de'+lastSelected );
                    result.map(function(el) {
                       // console.log("add to selected "+el);
                       selectedContacts.push(el);
                    });
                    markSelectedContacts();
 
                }

            }
                /*
            if (selectedContacts.length > 0) {
                var lastSelected = selectedContacts[selectedContacts.length - 1];
                for (var i = lastSelected + 1; i <= contact_id; i++)
                    selectedContacts.push(i);
            } else
                selectedContacts.push(rowID);
*/


        } else {
            editrecord($(this).data('contactid'));
        }
    });

    $('body').on('click','.ztop',function(event) {
        name = $(event.target).data('name');
        number = $(event.target).data('number');
        event.stopPropagation();
        parent.dial(name+' <'+number+'>');
    })

    if(typeof(parent.permisos)!='undefined') {
    if(parent.permisos.includes('all')==true || parent.permisos.includes('managetags')==true) {
        $('#buttontags').show();
        parent.$('#managetags').show();
    } else {
        $('#buttontags').hide();
        parent.$('#managetags').hide();
    }
    }

    load_tags(false);

    $('#masstags').empty().end();
    $('#masstags').on('change',function(e){
      // console.log($(e.currentTarget).val());
      // create an invisible form to submit all selectedContacts
      var form = $('<form action="contacts.php" method="post"></form>');
      form.append('<input type="hidden" name="action" value="settags" />');
      for (var i=0; l = selectedContacts.length, i < l; i++) {
        form.append('<input type="hidden" name="contact[]" value="'+selectedContacts[i]+'" />');
      }
      form.append('<input type="hidden" name="tag" value="'+$(e.currentTarget).val()+'" />');
      $.post(form.attr('action'), form.serialize(), function(data) {
          // reload frame
         window.location.reload(); 
      });
    });

    $('#csvupload').on('change',function(e){
        $in=$(this);
        var valor = $in.val();
        rest = valor.replace("C:\\fakepath\\","");
        $('#csvfilename').html(rest);
    });

    $('[data-toggle="tooltip"]').tooltip({'container':'body','delay': { show: 500, hide: 100 }});

    $('#addnewbutton').on('click', function(event) {
        window.parent.$('#contacttitle').text(lang.newcontact);
        event.preventDefault();

        window.parent.$('#tab_NOTES').hide();
        window.parent.$('#tab_CDR').hide();
        window.parent.$('#tab_CHAT').hide();
        $('.morephones').html('');
        window.parent.$('#buttondelete').hide();
        window.parent.$('#notesubmit').hide();
        window.parent.$('#sendmessageselector').hide();
        window.parent.$('.cropit-image-preview').css('background-image','url()');

        window.parent.$('#contactsframe').hide();
        window.parent.$('#contactForm').show();
        if(window.parent.innerWidth<=1990 && window.parent.innerWidth>999) {
           window.parent.$('#slider').css('width','50%');
        } else if(window.parent.innerWidth<=1000 && window.parent.innerWidth>800) {
           window.parent.$('#slider').css('width','75%');
        } else if(window.parent.innerWidth<=800) {
           window.parent.$('#slider').css('width','100%');
        } else {
           window.parent.$('#slider').css('width','30%');
        }

        window.parent.$('.nav-tabs a[href="#panel_CONTACT"]').tab('show');
        window.parent.$('#contactaction').val('insert');

        // deselect tags
        window.parent.$('#contacttags option').each(function() { $(this).attr('selected',false); });
        window.parent.$('#contacttags').trigger("chosen:updated");
        window.parent.$('.image-editor').cropit('imageSrc','./images/user.png');

        // select first phone type
        window.parent.$('select[name="phonetype[]"]').prop("selectedIndex", 0);

        // reset all input,checkbox and select values
        window.parent.$(':input','#contactform')
         .not(':button, :submit, :reset, :hidden')
         .val('')
         .removeAttr('checked', false)
         .removeAttr('selected', false);

    });

    $('.btncontactdelete').on('click', function(event) {
        event.preventDefault();
        areyousurestring = $('#areyousure').html();
        yesstring = $('#yesstring').html();
        nostring  = $('#nostring').html();
        window.parent.alertify.set({
             labels: {
                 ok: yesstring,
                 cancel: nostring
             }
        });
        window.parent.alertify.confirm(areyousurestring, function(e) {
            if (e) {
                // console.log(selectedContacts);
                for (var i=0; l = selectedContacts.length, i < l; i++) {
                    var parts = selectedContacts[i].split('_');
                    let contact_id = parts.shift();
                    let number     = parts.join('_');
                    let numdelete  = $('#'+contact_id+'_'+number).find('.ztop').text();
                    $.ajax({
                        cache: false,
                        type: "GET",
                        data: "action=deletenumber&id="+contact_id+"&number="+btoa(numdelete),
                        url: "contacts.php",
                        success: function(data) {
                            // console.log("hide "+contact_id+"_"+number);
                            $('#'+contact_id+"_"+number).hide();
                        }
                    });

                }
                $('#hdelete').hide();
                $('#hsearch').show();
                // clear selectedcontacts
                selectedContacts=[];
                $('li.list-group-item.chat').css('background-color','#fff');
            }
        });
    });

    $('#buttondelete').on('click', function(event) {
        event.preventDefault();
        areyousurestring = $('#areyousure').html();
        yesstring = $('#yesstring').html();
        nostring  = $('#nostring').html();
        window.parent.alertify.set({
             labels: {
                 ok: yesstring,
                 cancel: nostring
             }
        });
        window.parent.alertify.confirm(areyousurestring, function(e) {
            if (e) {
                window.parent.$('#contactaction').val('delete');
            }
        });

    });

    $('#buttonclose').on('click', function(event) {
        event.preventDefault();
        parent.hideContacts();
    });

    $('#buttonimport').on('click', function(event) {
        event.preventDefault();
        $('#uploadcontainer').modal();
    });

    $('#buttonexport').on('click', function(event) {
        event.preventDefault();
        $('#formexport').submit();
    });

    $('#buttontags').on('click', function(event) {
        event.preventDefault();
        window.parent.manageTags();
    });

    $(document).on('keyup', function(e) {
        var tag = e.target.tagName.toLowerCase();
        var tagid = e.target.id;

        if ( e.which === 27) {
            if(tag=='input' && tagid=='contact-list-search') {
                $('#contact-list-search').val('');
                $('#contact-list-search').focus();
            } else if(tag=='body') {
                parent.hideContacts();
            }
        }
    });

/*
    $('.glyphicon-earphone').on('click', function(el) {
        var number = $(this).attr('data-original-title');
        parent.dial(number);
    });
*/

    $("#contact-list-search").livesearch({
        searchCallback: searchFunction,
    });
        //innerText:  $('#searchstring').html()

    const textInput = document.getElementById('contact-list-search');
    textInput.addEventListener('input', function(event) {
        // Get the current value of the input field
        const inputValue = event.target.value;
        // Check if the first character is a space
        if (inputValue.charAt(0) === ' ') {
            // If the first character is a space, remove it from the input
            event.target.value = inputValue.trimStart();
        }
    });

    $('#records').jscroll({
        nextSelector : "a.first",
        contentSelector : "li.chat",
        debug: true,
        callback: function(data) { $('[data-toggle="tooltip"]').tooltip(); },
        loadingHtml: '<div class="horizontal-bar-wrap"><div class="bar1 bar"></div></div>'
    });

    if($('#stickyheader').length >0) {
        window.onscroll = function() {myStick()};
        var header = document.getElementById("stickyheader");
        var sticky = header.offsetTop - 30;
        function myStick() {
            if (window.pageYOffset > sticky) {
                header.classList.add("sticky");
            } else {
                header.classList.remove("sticky");
            }
        }
    }

    $('#formimport').on('submit',function() {
        $('#submiticon').attr('class','fa fa-spinner fa-spin fa-lg');
        $(":submit").attr("disabled", true);
    });

    // detect ajax complete to find pagination/page and search
    $( document ).ajaxComplete(function(event, xhr, settings) {
        if(settings.url.indexOf('?search')>0 || settings.url.indexOf('&search')>0) {
            var urlparts = settings.url.split("?");
            const urlParams = new URLSearchParams('?'+urlparts[1]);4
            var page = urlParams.get('page');
            var search = urlParams.get('search');
            if(page===null) { page = 1; }
            localStorage.setItem("contacts_page",page);
            localStorage.setItem("contacts_search",search);
        }
    });

    // try to detect on scroll end event to save offset position
    // when we need to restore the list of contacts after save/refresh
    function scrollEndCallback() {
        localStorage.setItem("contacts_scrollY",window.scrollY);
    }

    if ('onscrollend' in window) {
        document.onscrollend = scrollEndCallback
    }
    else {
        document.onscroll = event => {
            clearTimeout(window.scrollEndTimer)
            window.scrollEndTimer = setTimeout(scrollEndCallback, 100)
        }
    }

    $('[data-ptrans]').each(function() {
         if(typeof(lang[$(this).data('ptrans')])!='undefined') {
              newtext = lang[$(this).data('ptrans')];
         } else {
             newtext = $(this).data('ptrans');
         }
         $(this).attr('placeholder',newtext);
    });
    $('[data-trans]').each(function() {
         if(typeof(lang[$(this).data('trans')])!='undefined') {
              newtext = lang[$(this).data('trans')];
         } else {
             newtext = $(this).data('trans');
         }
         $(this).text(newtext);
    });




}

function searchFunction(searchTerm) {
    var url=window.location.href;
    var lastChar = url.substr(url.length - 1);
    if(lastChar=='#') {
        url=url.substr(0,-1);
    }
    $('#exportfilter').val(searchTerm);
    myurl = insertParam(url,'search',searchTerm);
    myurl = myurl.replace(/&?action=([^&]$|[^&]*)/i, "");
    myurl = myurl.replace(/&?page=([^&]$|[^&]*)/i, "");
    myurl = myurl.replace(/&?id=([^&]$|[^&]*)/i, "");

    // remove contact selection if any
    selectedContacts = [];
    $('li.list-group-item.chat').css('background-color','#fff');
    $('#hdelete').hide();
    $('#hsearch').show();

    var pane = $('#records');
    pane.load(myurl+' #contact-list', function() {
        window.scroll(0,0);
        $('#records').removeData('jscroll');
        $('#records').jscroll({
            nextSelector : "a.first",
            contentSelector : "li.chat",
            debug: true,
            callback: function(data) { $('[data-toggle="tooltip"]').tooltip();},
            loadingHtml: '<div class="horizontal-bar-wrap"><div class="bar1 bar"></div></div>'
        });
    });
}

function insertParam(mystring, key, value) {
    if(mystring === false) { return; }
    if(typeof mystring == 'undefined') { return; }
    key = encodeURI(key); value = encodeURI(value);
     if(mystring.indexOf('?')>0) {
       part = mystring.split('?');
       kvp = part[1].split('&');
    } else {
       kvp = [];
       part = [ mystring, '' ];
    }
     var i=kvp.length; var x; while(i--)
    {
        x = kvp[i].split('=');
         if (x[0]==key)
        {
            x[1] = value;
            kvp[i] = x.join('=');
            break;
        }
    }
     if(i<0) {kvp[kvp.length] = [key,value].join('=');}
     var pepe = part[0] + '?' + kvp.join('&');
    return pepe;
}

function editrecord(id) {
    if(selectedContacts.length>0) {
        selectedContacts=[];
        $('li.list-group-item.chat').css('background-color','#fff');
        $('#hdelete').hide();
        $('#hsearch').show();
    } else {
        window.parent.editrecord(id);
    }
}

function load_tags(cleartags) {

    // load tags/categories on ajax query, do not get them twice if reloading the contacts iframe
    $.getJSON( "contacts.php?action=gettags", function( data ) {

        var saved_val = [];
        if(cleartags) {
            saved_val = window.parent.$('#contacttags').val();
            window.parent.$('#contacttags').empty().end();
            $('#masstags').empty().end();
        }

        var category_options = window.parent.$('#contacttags option');
        var category_values = $.map(category_options, function(option) {
            if(option.value!='') {
                return option.value;
            }
        });

        var items = [];
        $.each( data, function( key, val ) {
            $('#masstags').append( $("<option>", { "selected": false, "text": val.text, "value": key, "data-color-option":val.color}) );
            if(!category_values.includes(key)) {
                window.parent.$('#contacttags').append( $("<option>", { "selected": false, "text": val.text, "value": key, "data-color-option":val.color}) );
            }
        });

        if(cleartags) {
            saved_val = window.parent.$('#contacttags').val(saved_val);
        }

        window.parent.$('#contacttags').trigger("chosen:updated");
        $('#masstags').trigger("chosen:updated");

    });
}

function markSelectedContacts() {
    $('li.list-group-item.chat').css('background-color','#fff');
    if(selectedContacts.length>0) {
        $('#hsearch').hide();
        $('#hdelete').show();
    } else {
        $('#hsearch').show();
        $('#hdelete').hide();
    }
    for (var i=0; l = selectedContacts.length, i < l; i++) {
        //console.log("pintar "+selectedContacts[i]);
        $('#'+selectedContacts[i]).css('background-color','#ddd');
    }
}

(function($) {
        $.fn.isAfter = function ($elm){
            var $this = $(this);
            var $myParents = $this.parents();
            var $elmParents = $elm.parents();

            var $myTreeLast = $this;

            var level = 0;
            for(var i in $myParents)
            {
                var $elmTreeLast = $elm;
                if (!$myParents.hasOwnProperty(i))
                {
                    continue;
                }
                var $parent = $($myParents[i]);

                for (var j in $elmParents)
                {
                    if (!$elmParents.hasOwnProperty(j))
                    {
                        continue;
                    }

                    var $elmParent = $($elmParents[j]);

                    if ($parent[0] === $elmParent[0])
                    {
                        var myTreePos = $myTreeLast.index();
                        var elmTreePos = $elmTreeLast.index();
                        return (myTreePos > elmTreePos);
                    }

                    $elmTreeLast = $elmParent;
                }
                $myTreeLast = $parent;
            }

            return false;

        }
})(jQuery);
