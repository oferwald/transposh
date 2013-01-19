// Code adapted from: http://www.jankoatwarpspeed.com/post/2009/07/28/reinventing-drop-down-with-css-jquery.aspx

(function ($) { // closure

    $(function() {
        $(".dropdown dt a").click(function() {
            $(this).parents(".dropdown").find("dd ul").toggle();
            return false;
        });

        $(".dropdown dd ul li a").click(function() {
            var text = $(this).html();
            $(this).parents(".dropdown").find("dt a span").html(text);
            $(this).parents(".dropdown").find("dd ul").hide();

            document.location.href=$(this).parents(".dropdown").find("dt a span.value").html();
            return false;
        });

        $(document).bind('click', function(e) {
            var $clicked = $(e.target);
            if (! $clicked.parents().hasClass("dropdown"))
                $(".dropdown dd ul").hide();
        });
    });
}(jQuery)); // end of closure