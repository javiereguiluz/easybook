/*
 * Setup when ready
 */
$(document).ready(function() {

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
    var $footer = $.mobile.activePage.children("[data-role='footer'][data-position='fixed']");
    $footer.fixedtoolbar('show');
    setTimeout(
            function(args){
                args.$footer.fixedtoolbar('hide');
            },
            3000, 
            {'$footer': $footer});
});

/*
 * Show fixed toolbar when scrolling, but only if not was already visible
 */
$(document).bind("scrollstart", function(){
    if (!$.mobile.activePage) {
        return;
    }
    var $footer = $.mobile.activePage.children("[data-role='footer'][data-position='fixed']");
    var wasShowing = $footer.hasClass('in');
    $footer.fixedtoolbar('show');
    setTimeout(
            function(args){
                if (!args.wasShowing) {
                    args.$footer.fixedtoolbar('hide');
                }
            },
            3000, 
            {'$footer': $footer, 'wasShowing' : wasShowing});
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
        $.mobile.silentScroll(0);
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

$(document).bind("pagebeforecreate", function(){
    // Init popups
    $('a[title]').makePopUp();
    
    // Initialize tooltips for images.
    // Attribute 'alt' is used as text.
    $('img[alt]').makePopUp();
});

$(document).bind("pageinit", function(){
    // force JQm to enhance the menu (toc) page
    $.mobile.loadPage("#menu");
    
});

// Plugin to create poppus out of regular links
(function ($) {

    $.fn.makePopUp = function (options) {

        return this.each(function (index) {
            
            var $this = $(this);

            var $link = $this;
            var tagType = $this[0].tagName.toLowerCase();
            if (tagType != 'a') {
                // Applying a popup to non-link, so surround it with a link
                $link = $('<a href="#">');
                $link.attr('title', $this.attr('title'));
                $link.attr('alt', $this.attr('alt'));
                $this.removeAttr('title');
                $this.removeAttr('alt');
                $this.wrapAll($link);
                $link = $this.parent();
            }
                
            // Extract text and remove title attribute
            var text = $link.attr('title');
            if (!text) {
                text = $link.attr('alt');
            }
            
            $link.removeAttr('title');
            
            // Crate unique id
            var id = 'popup-'+tagType+'-'+index;
                
            // Add data-rel attribute and href to link
            $link.attr('data-rel', 'popup');
            $link.attr('href', '#'+id);
            
            // Create div with popup text
            var divPopUp = $('<div data-role="popup">');
            divPopUp.html('<p>'+text+'</p>')
                .attr('id', id)
                .attr('data-theme', 'e');
            
            // Add to page
            $this.parents('[data-role="page"]').append(divPopUp);
        });
    };
})(jQuery);