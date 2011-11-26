// Code adapted from: http://www.jankoatwarpspeed.com/post/2009/07/28/reinventing-drop-down-with-css-jquery.aspx

(function ($) { // closure

    $(document).ready(function() {
        $(".dropdown dt a").click(function() {
            $(".dropdown dd ul").toggle();
            return false;
        });

        $(".dropdown dd ul li a").click(function() {
            var text = $(this).html();
            $(".dropdown dt a span").html(text);
            $(".dropdown dd ul").hide();

            document.location.href=getSelectedValue("tp_dropdown");
            return false;
        });

        function getSelectedValue(id) {
            return $("#" + id).find("dt a span.value").html();
        }

        $(document).bind('click', function(e) {
            var $clicked = $(e.target);
            if (! $clicked.parents().hasClass("dropdown"))
                $(".dropdown dd ul").hide();
        });
    });
}(jQuery)); // end of closure