/*
 * Setup when ready
 */
$(document).ready(function() {

    // Initialize tooltips for links.
    // Attribute "title" is used as text.
    $('a[title]').jqmTooltip({
        'position' : 'autoUnder'
    });

    // Initialize tooltips for images.
    // Attribute 'alt' is used as text. 
    $('img[alt]').each(function(index) {
        if (this.alt.trim() != '') {
            $(this).jqmTooltip({
                'position' : 'autoUnder',
                'text' : this.alt
            });
        }
    });

    // Make anchor links actually work
    $('ul[data-role="listview"] a').click(function(e) {
        // Extract the hash in the target. 
        // No hash means that the target is not an <a> tag but one of its children (i.e. an <h3>)
        hash =  $(e.target).get(0).hash;
        if (!hash) {
            hash = $(e.target).parents('a').get(0).hash;
        }
        // Find the page it is contained into (or it is itself)
        page = $(hash).closest('div[data-role="page"]').get(0);

        // Look if we are already in that page
        if ($(page).get(0) != $.mobile.activePage.get(0)) {
            // Change to it and scroll to the anchor
            $.mobile.changePage($(page));
            $(page).on('pageshow', function(){
                $.mobile.silentScroll($(hash).offset().top);
            });
        } else {
            // Just scroll to the anchor
            menu.hide();
            $.mobile.silentScroll($(hash).offset().top);
        } 
    });
});

/*
 * Show fixed toolbar after changing to a page and hide it after a while
 */
$(document).bind("pagechange", function(){
    $.mobile.activePage.children("[data-position='fixed']").fixedtoolbar('show');
    setTimeout(
            function(){
                $.mobile.activePage.children("[data-position='fixed']").fixedtoolbar('hide');
            },
            3000);
});

/*
 * Show fixed toolbar when scrolling, but only if not was already visible
 */
$(document).bind("scrollstart", function(){
    var wasShowing = $.mobile.activePage.children("[data-position='fixed']").hasClass('in');
    $.mobile.activePage.children("[data-position='fixed']").fixedtoolbar('show');
    setTimeout(
            function(){
                if (!wasShowing) {
                    $.mobile.activePage.children("[data-position='fixed']").fixedtoolbar('hide');
                }
            },
            3000);
});

/*
 * Scroll to top on button click
 */
$(function(){
    $("a.toTop").click(function(){
        $.mobile.silentScroll(0);
    });
}); 

/*
 * Manage menu (TOC)
 */
$(function(){
    // show or hide menu
    $("a.showMenu").click(function(){
        menu.toggle();
        return false;
    });

    $(document).live('pagebeforeshow',function(event, ui){
        menu.hide();
    });
});

var menu = {
        menuStatus : false,
        
        show: function() {
            $("#menu").css('display','block').css('z-index', '0');
            $("#menu").css('position','absolute');
            $(".ui-page-active").css('z-index', '1');
            me = this;
            $(".ui-page-active").animate({marginLeft: "250px",}, 300,function(){me.menuStatus = true;});
        },
        
        hide: function() {
            me = this;
            $(".ui-page-active").animate({marginLeft: "0px",}, 300,function(){me.menuStatus = false;});
            $("#menu").css('position','fixed');
            $("#menu").css('display','none');
        },
        
        toggle: function() {
            if (this.menuStatus == true) {
                this.hide();
            } else {
                this.show();
            }
        }
};

// force JQm to enhance the menu (toc) page
$(document).bind("pageinit", function(){
    $.mobile.loadPage("#menu");
});

