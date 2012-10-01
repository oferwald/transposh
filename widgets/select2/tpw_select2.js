(function ($) { // closure
    $(function () {
        function format(language) {
            var originalOption = language.element; 
            var img = '<span class="trf trf-'+$(originalOption).data('flag')+'" title="'+$(originalOption).data('lang')+'"></span> ';
            return img + language.text;
        }

        function format2(language) {
            var originalOption = $(this.element).children('[value="'+language.id+'"]'); 
            var img = '<span style="display: inline-block; margin: 0" class="trf trf-'+$(originalOption).data('flag')+'" title="'+$(originalOption).data('lang')+'"></span> ';
            return img + language.text;
        }

        jQuery(".tp_lang2").select2({
            formatResult: format,
            formatSelection: format2
        });
    });
    
}(jQuery)); // end of closure
