/*  Copyright © 2009-2018 Transposh Team (website : http://transposh.org)
 *
 *	This program is free software; you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation; either version 2 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program; if not, write to the Free Software
 *	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

(function ($) { // closure
    // list of languages
    var l = {
        'en': 'English - English',
        'af': 'Afrikaans - Afrikaans',
        'sq': 'Albanian - Shqip',
        'ar': 'Arabic - العربية',
        'hy': 'Armenian - Հայերեն',
        'az': 'Azerbaijani - azərbaycan dili',
        'eu': 'Basque - Euskara',
        'be': 'Belarusian - Беларуская',
        'bn': 'Bengali - বাংলা',
        'bg': 'Bulgarian - Български',
        'ca': 'Catalan - Català',
        'zh': 'Chinese (Simplified) - 中文(简体)',
        'zh-tw': 'Chinese (Traditional) - 中文(漢字)',
        'hr': 'Croatian - Hrvatski',
        'cs': 'Czech - Čeština',
        'da': 'Danish - Dansk',
        'nl': 'Dutch - Nederlands',
        'eo': 'Esperanto - Esperanto',
        'et': 'Estonian - Eesti keel',
        'fi': 'Finnish - Suomi',
        'fr': 'French - Français',
        'gl': 'Galician - Galego',
        'ka': 'Georgian - ქართული',
        'de': 'German - Deutsch',
        'el': 'Greek - Ελληνικά',
        'gu': 'Gujarati - ગુજરાતી',
        'ht': 'Haitian - Kreyòl ayisyen',
        'he': 'Hebrew - עברית',
        'hi': 'Hindi - हिन्दी; हिंदी',
        'hu': 'Hungarian - Magyar',
        'is': 'Icelandic - Íslenska',
        'id': 'Indonesian - Bahasa Indonesia',
        'ga': 'Irish - Gaeilge',
        'it': 'Italian - Italiano',
        'ja': 'Japanese - 日本語',
        'kn': 'Kannada - ಕನ್ನಡ',
        'ko': 'Korean - 우리말',
        'la': 'Latin - Latīna',
        'lv': 'Latvian - Latviešu valoda',
        'lt': 'Lithuanian - Lietuvių kalba',
        'mk': 'Macedonian - македонски јазик',
        'ms': 'Malay - Bahasa Melayu',
        'mt': 'Maltese - Malti',
        'no': 'Norwegian - Norsk',
        'fa': 'Persian - پارسی',
        'pl': 'Polish - Polski',
        'pt': 'Portuguese - Português',
        'ro': 'Romanian - Română',
        'ru': 'Russian - Русский',
        'sr': 'Serbian - Cрпски језик',
        'sk': 'Slovak - Slovenčina',
        'sl': 'Slovene - Slovenščina',
        'es': 'Spanish - Español',
        'sw': 'Swahili - Kiswahili',
        'sv': 'Swedish - Svenska',
        'tl': 'Tagalog - Tagalog',
        'ta': 'Tamil - தமிழ்',
        'te': 'Telugu - తెలుగు',
        'th': 'Thai - ภาษาไทย',
        'tr': 'Turkish - Türkçe',
        'uk': 'Ukrainian - Українська',
        'ur': 'Urdu - اردو',
        'vi': 'Vietnamese - Tiếng Việt',
        'cy': 'Welsh - Cymraeg',
        'yi': 'Yiddish - ייִדיש'
    }

    $(function () {
        var commentclickfunction = function () {
            var options = '<option value="">Unset</option>', selected, lang = $(this).data('lang');
            $.each(l, function (x) {
                if (x === lang) {
                    selected = 'selected="selected"'
                } else {
                    selected = ''
                }
                ;
                options += '<option value="' + x + '"' + selected + '>' + l[x] + '</option>'
            });
            $(this).closest(".row-actions").toggleClass("row-actions-active").toggleClass("row-actions");
            $(this).replaceWith('<select data-cid="' + $(this).data('cid') + '">' + options + "</select>");
            $(".language select").change(function () {
                $.get(ajaxurl,
                        {
                            action: 'tp_comment_lang',
                            lang: $(this).val(),
                            cid: $(this).data('cid')
                        }
                );
                var cid = $(this).data('cid');
                $(this).closest(".row-actions-active").toggleClass("row-actions-active").toggleClass("row-actions");
                $(this).replaceWith('<a data-cid="' + cid + '" data-lang="' + $(this).val() + '" href="" onclick="return false">' + $('[data-cid=' + cid + '] option:selected').text() + '</a>');
                $('[data-cid=' + cid + ']').click(commentclickfunction);
            });
        };
        $(".language a").click(commentclickfunction);
    })

}(jQuery))

