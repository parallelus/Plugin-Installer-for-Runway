(function ($) {

    $(function () {
        var $buttons = $('.button-primary.ajax-save');

        $buttons.on('click', function () {
            $buttons
                .parent()
                .find('input')
                .attr('value', 'save');
        });

    });

    $.each($('.plugin .install a'),function( i, val ) {
        var href = $(val).attr('href').replace('#8211;%20','&');
        $(val).attr('href',href);
    });

})(jQuery);
