(function ($) { // closure
    // list of languages
    var l = {
        en: 'English',
        af: 'Afrikaans',
        sq: 'Shqip',
        ar: 'العربية',
        hy: 'Հայերեն',
        az: 'azərbaycan dili',
        eu: 'Euskara',
        be: 'Беларуская',
        bg: 'Български',
        ca: 'Català',
        zh: '中文 - 简体',
        'zh-tw': '中文 - 漢字',
        hr: 'Hrvatski',
        cs: 'Čeština',
        da: 'Dansk',
        nl: 'Nederlands',
        eo: 'Esperanto',
        et: 'Eesti keel',
        fi: 'Suomi',
        fr: 'Français',
        gl: 'Galego',
        ka: 'ქართული',
        de: 'Deutsch',
        el: 'Ελληνικά',
        ht: 'Kreyòl ayisyen',
        he: 'עברית',
        hi: 'हिन्दी; हिंदी',
        hu: 'Magyar',
        is: 'Íslenska',
        id: 'Bahasa Indonesia',
        ga: 'Gaeilge',
        it: 'Italiano',
        ja: '日本語',
        ko: '우리말',
        la: 'latīna',
        lv: 'Latviešu valoda',
        lt: 'Lietuvių kalba',
        mk: 'македонски јазик',
        ms: 'Bahasa Melayu',
        mt: 'Malti',
        no: 'Norsk',
        fa: 'فارسی',
        pl: 'Polski',
        pt: 'Português',
        ro: 'Română',
        ru: 'Русский',
        sr: 'Cрпски језик',
        sk: 'Slovenčina',
        sl: 'Slovenščina', //slovenian
        es: 'Español',
        sw: 'Kiswahili',
        sv: 'svenska',
        tl: 'Tagalog', // fhilipino
        th: 'ภาษาไทย',
        tr: 'Türkçe',
        uk: 'Українська',
        ur: 'اردو',
        vi: 'Tiếng Việt',
        cy: 'Cymraeg',
        yi: 'ייִדיש'
    }
    
    $(function() {
        var commentclickfunction = function() {
            var options = '<option value="">Unset</option>', selected, lang = $(this).data('lang');
            $.each(l, function(x) {
                if (x === lang) {
                    selected = 'selected="selected"'
                } else {
                    selected = ''
                };
                options += '<option value="'+x+'"'+selected+'>'+l[x]+'</option>'
            });
            $(this).closest(".row-actions").toggleClass("row-actions-active").toggleClass("row-actions");
            $(this).replaceWith('<select data-cid="'+$(this).data('cid')+'">'+options+"</select>");
            $(".language select").change(function(){
                $.get(ajaxurl,
                    {
                        action: 'tp_comment_lang',
                        lang: $(this).val(),
                        cid: $(this).data('cid')
                    }
                );
                var cid = $(this).data('cid');
                $(this).closest(".row-actions-active").toggleClass("row-actions-active").toggleClass("row-actions");
                $(this).replaceWith('<a data-cid="'+cid+'" data-lang="'+$(this).val()+'" href="" onclick="return false">'+$('[data-cid='+cid+'] option:selected').text()+'</a>');
                $('[data-cid='+cid+']').click(commentclickfunction);
            });
        };
        $(".language a").click(commentclickfunction);
    })    
    
}(jQuery))

