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

})(jQuery);
