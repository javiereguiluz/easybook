// jqmTooltip v1.0
// by Jon Jandoc
// jon@yupso.com

// Fixed some errors by MAGS

(function ($) {

    $.fn.jqmTooltip = function (options) {

        var settings = {
            'arrow': true,
            'fadeSpeed': 200,
            'maxWidth': 280,
            'minSideMargins': 15,
            'position': 'autoUnder',
            'offset': 15,
            'text': 'title'
        }, useTitle;

        function showTooltip(tooltip) {
            $(tooltip).addClass('active').fadeIn(settings.fadeSpeed);
        }
        function hideTooltip(tooltip) {
            if (tooltip = 'all') {
                tooltip = $('.jqmTooltip');
            }
            $(tooltip).removeClass('active').fadeOut(settings.fadeSpeed);
        }
        /* MAGS: This doesn't belong here
        if (settings.text == 'title') {
        	useTitle = true;
        }
        */
        // Hide visible tooltips when clicking rest of screen or on resize
        this.parents('div:jqmData(role="page")').bind('tap.jqmTooltip', function (event) {
            hideTooltip('all');
        });
        $(window).bind('resize.jqmTooltip', function (event) {
            hideTooltip('all');
        });

        return this.each(function () {
            // Merge settings
            if (options) {
                $.extend(settings, options);
            }
            
            /* MAGS: This belongs here */
            if (settings.text == 'title') {
            	useTitle = true;
            }

            var $this = $(this),
            	cssStyles = {},
                tooltipID = 'tooltip-' + Math.floor(Math.random() * 1000),
                overlay, overlayWidth, overlayLeft, overlayOffset, element, elemOffset, windowHeight, windowWidth;

            // Create overlay
            overlay = $('<div class="jqmTooltip">');

            // Grab inner text from title tag by default
            if (useTitle) {
                settings.text = $this.attr('title');
                $this.attr('title', '');
            }
            overlay.html(settings.text)
			    .attr('id', tooltipID)
                .css('display', 'none')
			    .append('<a href="#" class="close">&#215;</div>')
                .find('.close').bind('tap', function (e) {
                    e.preventDefault();
                    hideTooltip('#' + tooltipID);
                });
            if (settings.arrow) {
                overlay.append('<div class="arrow">');
                var arrowLeft;
            }

            $this.parents('div:jqmData(role="page")').append(overlay);

            // Bind tap event
            $this.bind('tap.jqmTooltip', function (e) {
                e.stopPropagation();
                if (overlay.hasClass('active')) {
                    hideTooltip(overlay);
                } else {

                    element = $(e.target);
                    elemOffset = element.offset();

                    windowHeight = $(window).height();
                    windowWidth = $(window).width();
                    overlayWidth = windowWidth - (2 * settings.minSideMargins);

                    if (overlayWidth > settings.maxWidth) {
                        overlayWidth = settings.maxWidth;
                    }
                    cssStyles.width = overlayWidth;

                    // position overlay horizontally based on trigger element and screen width
                    overlayLeft = (elemOffset.left + (element.width() / 2)) - (overlayWidth / 2)

                    // check if overlay is too far right
                    if (overlayLeft + overlayWidth > windowWidth) {
                        cssStyles.left = 'auto';
                        cssStyles.right = settings.minSideMargins;
                        if (settings.arrow) {
                            overlayOffset = overlay.offset();
                            arrowLeft = ((elemOffset.left - (windowWidth - settings.minSideMargins - overlayWidth)) + (element.width() / 2)) + 'px';
                            overlay.find('.arrow').css('left', arrowLeft);
                        }
                        // check if overlay is too far left
                    } else if (overlayLeft < 0) {
                        cssStyles.left = settings.minSideMargins;
                        cssStyles.right = 'auto';
                        if (settings.arrow) {
                            arrowLeft = ((elemOffset.left - settings.minSideMargins) + (element.width() / 2)) + 'px';
                            overlay.find('.arrow').css('left', arrowLeft);
                        }
                        // otherwise center overlay under element
                    } else {
                        cssStyles.left = (elemOffset.left + (element.width() / 2)) - (overlayWidth / 2)
                        if (settings.arrow) {
                            overlay.find('.arrow').css('left', '50%');
                        }
                    }

                    // position overlay vertically based on settings
                    switch (settings.position) {
                        case ('over'):
                            overlay.css(cssStyles);
                            showTooltip(overlay);
                            cssStyles.top = elemOffset.top - overlay.outerHeight() - settings.offset + 'px';
                            overlay.addClass('over').removeClass('under').css(cssStyles);
                            break;
                        case ('under'):
                            cssStyles.top = elemOffset.top + element.outerHeight() + settings.offset + 'px';
                            cssStyles.bottom = 'auto';
                            overlay.addClass('under').css(cssStyles);
                            showTooltip(overlay);
                            break;
                        case ('autoOver'):
                            overlay.css(cssStyles);
                            showTooltip(overlay);
                            if ((overlay.outerHeight() + settings.offset) > (elemOffset.top - $(window).scrollTop())) {
                                cssStyles.top = elemOffset.top + element.outerHeight() + settings.offset + 'px';
                                overlay.addClass('under').removeClass('over').css(cssStyles);
                            } else {
                                cssStyles.top = elemOffset.top - overlay.outerHeight() - settings.offset + 'px';
                                overlay.addClass('over').removeClass('under').css(cssStyles);
                            }
                            break;
                        default: // aka 'autoUnder'
                            overlay.css(cssStyles);
                            showTooltip(overlay);
                            if ((overlay.outerHeight() + settings.offset) > ($(window).height() - elemOffset.top)) {
                                cssStyles.top = elemOffset.top - overlay.outerHeight() - settings.offset + 'px';
                                overlay.addClass('over').removeClass('under').css(cssStyles);
                            } else {
                                cssStyles.top = elemOffset.top + element.outerHeight() + settings.offset + 'px';
                                overlay.addClass('under').removeClass('over').css(cssStyles);
                            }
                            break;
                    }

                };

            });

        });

    };
})(jQuery);