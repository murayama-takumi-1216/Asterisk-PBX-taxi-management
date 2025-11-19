$.fn.pageMe = function(opts){
    var $this = this,
        defaults = {
            perPage: 10,
            showPrevNext: false,
            numbersPerPage: 5,
            curPage: 1,
            hidePageNumbers: false
        },
        settings = $.extend(defaults, opts);
    
    var listElement = $this;
    var perPage = settings.perPage; 
    var curPage = settings.curPage; 
    var children = listElement.children();
    var pager = $('.pagination');

    curPage--;

    if (typeof settings.childSelector!="undefined") {
        children = listElement.find(settings.childSelector);
    }
    
    if (typeof settings.pagerSelector!="undefined") {
        pager = $(settings.pagerSelector);
    }
    
    var numItems = children.length;
    var numPages = Math.ceil(numItems/perPage);

    pager.data("curr",0);
    
    if (settings.showPrevNext){
        $('<li><a href="#" class="prev_link">«</a></li>').appendTo(pager);
    }
    
    var curr = 0;
    while(numPages > curr && (settings.hidePageNumbers==false)){
        $('<li><a href="#" class="page_link">'+(curr+1)+'</a></li>').appendTo(pager);
        curr++;
    }
  
    if (settings.numbersPerPage>1) {
       $('.page_link').hide();
       $('.page_link').slice(pager.data("curr"), settings.numbersPerPage).show();
    }
    
    if (settings.showPrevNext){
        $('<li><a href="#" class="next_link">»</a></li>').appendTo(pager);
    }
    
    pager.find('.page_link:first').addClass('active');
    if (numPages==1) {
        pager.hide();
    }

    if (numItems==0) {
        pager.find('.prev_link').hide();
        pager.find('.next_link').hide();
    }

    pager.children().eq(1).addClass("active");
    
    children.hide();
    children.slice(0, perPage).show();
    
    pager.find('li .page_link').click(function(){
        var clickedPage = $(this).html().valueOf()-1;
        goTo(clickedPage,perPage);
        return false;
    });
    pager.find('li .prev_link').click(function(){
        previous();
        return false;
    });
    pager.find('li .next_link').click(function(){
        next();
        return false;
    });
 
    if(curPage>0 && curPage<numPages) {
        page=curPage;
        goTo(curPage);
    }    
   
    function previous(){
        var goToPage = parseInt(pager.data("curr")) - 1;
        if(goToPage>=0) { goTo(goToPage); }
    }
     
    function next(){
        goToPage = parseInt(pager.data("curr")) + 1;
        if(goToPage<numPages) { goTo(goToPage); }
    }
    
    function goTo(page){
        var startAt = page * perPage,
            endOn = startAt + perPage;
        
        children.css('display','none').slice(startAt, endOn).show();
        
        pager.data("curr",page);
       
        if (settings.numbersPerPage>1) {
            

            if((settings.numbersPerPage+page)>numPages) {
                if(numPages>settings.numbersPerPage) {
                dif = (settings.numbersPerPage+page) - numPages;
                $('.page_link').hide();
                $('.page_link').slice(page-dif, settings.numbersPerPage+page-dif).show();
                }
            } else {
                $('.page_link').hide();
                $('.page_link').slice(page, settings.numbersPerPage+page).show();
            }

        }
      
        pager.children().removeClass("active");
        pager.children().eq(page+1).addClass("active");
    
    }
};
