(function ($) { // closure
    $(function () {
        $(".warning-close").click(function () {
            $(this).parents('div:first').hide();
            $.post(ajaxurl, {
                action: 'tp_close_warning',
                id: $(this).attr('id')
            });
        });
    });
}(jQuery)); // end of closure