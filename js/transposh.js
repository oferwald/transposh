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

// List of exposed functions:
//    t_jp.dgpt = do_mass_google_translate;
//    t_jp.dbt = do_mass_bing_translate;
//    t_jp.dyt = do_mass_yandex_translate;
//    t_jp.dat = do_mass_apertium_translate;
//    t_jp.tfl = test_for_lazyrun;
//    t_jp.tfju = test_for_jqueryui;
//    t_jp.at = do_auto_translate;

/*global Date, Math, alert, escape, clearTimeout, document, jQuery, setTimeout, t_jp, window, _mstConfig */
(function ($) { // closure
    var
            // this is the size of strings to queue, we don't want too much there
            BATCH_SIZE = 1024,
            // number of phrases that might be translated
            possibly_translateable,
            // ids of progress bars
            t_jp_prefix = t_jp.prefix,
            // source - 0 is human, 1 google , 2 bing, 3 apertium,
            //          4 yandex, 5 baidu, 6 LibreTranslate - higher reserved for future engines
            source = 1,
            //Ajax translation
            done_posted = 0, /*Timer for translation aggregation*/ timer, tokens = [], translations = []
            ;

    // This function fixes the page, it gets a token and translation and fixes this,
    // since here we only get the automated source, we use this to reduce the code size
    function fix_page(token, translation) {
        // Todo - Probably not needed, but in case we get bad stuff
        if ($.trim(translation).length === 0) {
            return;
        }
        // this is an inner function used to fix the images in the case of being inside the edit mode.
        // if we are not editing, no images will be found and nothing will happen.
        // even if this happens before the edit scripts adds the images, it won't matter as source is changed too and the
        // edit script will fix this
        var fix_image = function () { // handle the image changes
            var img_segment_id = $(this).attr('id').substr($(this).attr('id').lastIndexOf('_') + 1),
                    img = $("#" + t_jp_prefix + "img_" + img_segment_id);
            $("#" + t_jp_prefix + img_segment_id).attr('data-source', 1); // source is 1
            img.removeClass('tr-icon-yellow').removeClass('tr-icon-green').addClass('tr-icon-yellow');
        };

        // might need to escape the token selectors
        //token = token.replace(/([;&,\.\+\*\~':"\!\^#$%@\[\]\(\)=>\|])/g, '\\$1');
        token = $.escapeSelector(token);
        //window.console && console.log(token);
        // rewrite text for all matching items at once
        $("*[data-orig='" + token + "'][data-hidden!='y']")
                .html(translation)
                .each(fix_image);

        // TODO - FIX hidden elements too (need to update father's title)
        $("*[data-orig='" + token + "'][data-hidden='y']")
                .attr('data-trans', translation)
                .each(fix_image);
    }

    // we have four params, here two are implicit (source =1 auto translate, lang = target language)
    function ajax_translate(token, translation) {
        // we aggregate translations together, 200ms from the last translation we will send the timer
        // so here we remove it so nothing unexpected happens
        clearTimeout(timer);
        // push translations
        tokens.push(token);
        translations.push(translation);
        // This is a change - as we fix the pages before we got actual confirmation (worked well for auto-translation)
        fix_page(token, translation);
        timer = setTimeout(function () {
            var data = {
                ln0: t_jp.lang, // implicit
                sr0: source, // implicit auto translate... 1 if google, 2 if msn
                action: 'tp_translation',
                items: tokens.length // we can do this here because all tokens will be different
            }, i;
            for (i = 0; i < tokens.length; i += 1) {
                data["tk" + i] = tokens[i];
                data["tr" + i] = translations[i];
                // We are pre-accounting the progress bar here - which is not very nice
                //if (source > 0) {
                done_posted += $("*[data-orig='" + tokens[i].replace(/([;&,\.\+\*\~':"\!\^#$%@\[\]\(\)=>\|])/g, '\\$1') + "']").length;
                //}
            }
            $.ajax({
                type: "POST",
                url: t_jp.ajaxurl,
                data: data,
                success: function () {
                    // Success now only updates the save progress bar (green)
                    console.window && console.log(done_posted + "/" + possibly_translateable + " translations posted");
                }
                // we removed the error function, as there is no alert for automated thing, this will silently fail
                // which although bad, is what we can do for now
            });
            translations = [];
            tokens = [];
        }, 200); // wait 200 ms... -- TODO, maybe do - items*3
    }

    // happens on translate success
    function auto_translate_success(token, translation) {
        ajax_translate(token, $("<div>" + $.trim(translation) + "</div>").text());
        window.console && console.log(possibly_translateable - $("." + t_jp_prefix + '[data-source=""]').length + "/" + possibly_translateable + " auto translated");
    }

    // mass google translation - using proxy
    function do_mass_google_translate(batchtrans, callback, lang) {
        /*var sl = '';
         if (usedefault) {
         sl = t_jp.olang;
         }*/
        $.ajax({
            url: t_jp.ajaxurl,
            dataType: "json",
            type: "GET",
            // check each
            data: {
                action: 'tp_tp',
                e: 'g',
                tl: lang,
                // sl: sl,
                q: batchtrans
            },
            success: callback
        });
    }
    function do_mass_google_invoker(tokens, trans) {
        do_mass_google_translate(trans, function (result) {
            $(result.results).each(function (i) {
                auto_translate_success(tokens[i], this);
            });
        }, t_jp.lang);
    }

    /*  // mass google translation using an api key
     function do_mass_google_api_translate(batchtrans, callback, lang) {
     $.ajax({
     url: 'https://www.googleapis.com/language/translate/v2',
     dataType: "jsonp",
     data: {
     key: t_jp.google_key,
     q: batchtrans,
     target: lang,
     source: t_jp.olang
     },
     traditional: true,
     success: callback
     });
     }
     function do_mass_google_api_invoker(tokens, trans) {
     do_mass_google_api_translate(trans, function (result) {
     // if there was an error we will try the other invoker
     if (typeof result.error !== 'undefined') {
     do_mass_google_invoker(tokens, trans);
     } else {
     $(result.data.translations).each(function (i) {
     auto_translate_success(tokens[i], this.translatedText);
     });
     }
     }, t_jp.lang);
     }*/

    // mass bing translation
    function do_mass_bing_translate(batchtrans, callback, lang) {
        /*var sl = '';
         if (usedefault) {
         sl = t_jp.olang;
         }*/
        $.ajax({
            url: t_jp.ajaxurl,
            dataType: "json",
            type: "GET",
            // check each
            data: {
                action: 'tp_tp',
                e: 'b',
                tl: lang,
                // sl: sl,
                q: batchtrans
            },
            success: callback
        });
    }

    function do_mass_bing_invoker(tokens, trans) {
        source = 2;
        do_mass_bing_translate(trans, function (result) {
            $(result).each(function (i) {
                auto_translate_success(tokens[i], this.TranslatedText);
            });
        }, t_jp.lang);
    }

    // mass yandex translation - using proxy
    function do_mass_yandex_translate(batchtrans, callback, lang) {
        /*var sl = '';
         if (usedefault) {
         sl = t_jp.olang;
         }*/
        $.ajax({
            url: t_jp.ajaxurl,
            dataType: "json",
            type: "GET",
            // check each
            data: {
                action: 'tp_tp',
                e: 'y',
                tl: lang,
                // sl: sl,
                q: batchtrans
            },
            success: callback
        });
    }
    function do_mass_yandex_invoker(tokens, trans) {
        source = 4;
        do_mass_yandex_translate(trans, function (result) {
            $(result.results).each(function (i) {
                auto_translate_success(tokens[i], this);
            });
        }, t_jp.lang);
    }

    // mass baidu translation - using proxy
    function do_mass_baidu_translate(batchtrans, callback, lang) {
        /*var sl = '';
         if (usedefault) {
         sl = t_jp.olang;
         }*/
        $.ajax({
            url: t_jp.ajaxurl,
            dataType: "json",
            type: "GET",
            // check each
            data: {
                action: 'tp_tp',
                e: 'u',
                tl: lang,
                // sl: sl,
                q: batchtrans
            },
            success: callback
        });
    }
    function do_mass_baidu_invoker(tokens, trans) {
        source = 5;
        do_mass_baidu_translate(trans, function (result) {
            $(result.results).each(function (i) {
                auto_translate_success(tokens[i], this);
            });
        }, t_jp.lang);
    }

    // mass apertium translation
    function do_mass_apertium_translate(batchtrans, callback, lang) {
        $.ajax({
            url: 'http://api.apertium.org/json/translate',
            data: {
                q: batchtrans,
                langpair: t_jp.olang + '|' + lang,
                markUnknown: 'no'
            },
            dataType: "jsonp",
            traditional: true,
            success: callback
        });
    }

    function do_mass_apertium_invoker(tokens, trans) {
        source = 3;
        do_mass_apertium_translate(trans, function (result) {
            // we assume that 2xx answer should be good, 200 is good, 206 is partially good (some errors)
            if (result.responseStatus >= 200 && result.responseStatus < 300) {
                // single items get handled differently
                if (result.responseData.translatedText !== undefined) {
                    auto_translate_success(tokens[0], result.responseData.translatedText);
                } else {
                    $(result.responseData).each(function (i) {
                        if (this.responseStatus === 200) {
                            auto_translate_success(tokens[i], this.responseData.translatedText);
                        }
                    });
                }
            }
        }, t_jp.lang);
    }

    // invokes the correct mass translator based on the preferred one...
    function do_mass_invoke(batchtokens, batchtrans) {
        t_jp.preferred.some(function (engine) {
            //console.log(engine);
            if (t_jp.engines[engine]) {
                if (engine === 'a') {
                    do_mass_apertium_invoker(batchtokens, batchtrans);
                }
                if (engine === 'b') {
                    do_mass_bing_invoker(batchtokens, batchtrans);
                }
                if (engine === 'g') {
                    do_mass_google_invoker(batchtokens, batchtrans);
                }
                if (engine === 'y') {
                    do_mass_yandex_invoker(batchtokens, batchtrans);
                }
                if (engine === 'u') {
                    do_mass_baidu_invoker(batchtokens, batchtrans);
                }
                return true;
            }
        });
    }

    //function for auto translation
    function do_auto_translate() {
        // auto_translated_previously...
        var auto_translated_phrases = [], batchlength = 0, batchtrans = [], batchtokens = [];

        $("." + t_jp_prefix + '[data-source=""]').each(function () {
            var token = $(this).attr('data-orig'),
            // we only have orig if we have some translation? so it should probably not be here... ? (or maybe for future invalidations of cached auto translations)
                to_trans = $(this).attr('data-orig');
            // should not happen, but just in case
            if (!token || token.length === 0) {
                return; // continue
            }
            if (to_trans === undefined) {
                to_trans = $(this).html();
            }
            if (auto_translated_phrases[to_trans] !== 1) {
                auto_translated_phrases[to_trans] = 1;
                if (batchlength + encodeURIComponent(to_trans).length > BATCH_SIZE) {
                    do_mass_invoke(batchtokens, batchtrans);
                    batchlength = 0;
                    batchtrans = [];
                    batchtokens = [];
                }
                batchlength += encodeURIComponent(to_trans).length;
                batchtokens.push(token);
                batchtrans.push(to_trans);
            }
        });
        // this invokation is for the remaining items
        do_mass_invoke(batchtokens, batchtrans);
    }

    // helper function for lazy running
    function test_for_lazyrun(callback) {
        if (typeof $.xLazyLoader === 'function') {
            callback();
        } else {
            t_jp.$ = $; // We wanted the same jQuery... hmmm
            $.getScript(t_jp.plugin_url + '/js/lazy.js', callback).done(callback);
        }
    }

    function test_for_jqueryui(callback) {
        if (test_for_jqueryui.hit /* might be needed? - && typeof $.fn.dialog !== 'function' */) {
            callback();
        } else {
            test_for_jqueryui.hit = true;
            test_for_lazyrun(function () {
                // This is needed when old jQueryUI is being loaded (default for wp3.2)
                $.fn.propAttr = $.fn.prop || $.fn.attr;
                $.xLazyLoader({
                    js: t_jp.jQueryUI + 'jquery-ui.min.js',
                    css: t_jp.jQueryUI + 'themes/' + t_jp.theme + '/jquery-ui.css',
                    success: callback
                });
            });
        }
    }

    // expose some functions
    t_jp.dgpt = do_mass_google_translate;
    //t_jp.dgt = do_mass_google_api_translate;
    t_jp.dbt = do_mass_bing_translate;
    t_jp.dyt = do_mass_yandex_translate;
    t_jp.dut = do_mass_baidu_translate;
    t_jp.dat = do_mass_apertium_translate;
    t_jp.at = do_auto_translate;
    t_jp.tfl = test_for_lazyrun;
    t_jp.tfju = test_for_jqueryui;

    $(function () {

        // this is the set_default_language function
        // attach a function to the set_default_language link if its there
        $('.' + t_jp_prefix + 'setdeflang').on("click", function () {
            $.ajax({
                url: t_jp.ajaxurl,
                data: {
                    action: 'tp_cookie'
                },
                cache: false
            });
            $('.' + t_jp_prefix + 'setdeflang').hide("slow");
            return false;
        });

        // how many phrases are yet untranslated
        possibly_translateable = $("." + t_jp_prefix + '[data-source=""]').length;

        //now = new Date();
        // we make sure script sub loaded are cached
        $.ajaxSetup({
            cache: true
        });
        // was: we'll only auto-translate and load the stuff if we either have more than 5 candidate translations, or more than one at 4am, and this language is supported...
        // we'll translate if there's any candidate...?
        if (possibly_translateable && !t_jp.noauto && !$.isEmptyObject(t_jp.engines)) {
            do_auto_translate();
        }

        // this is the part when we have editor support
        if (t_jp.edit) {
            $.getScript(t_jp.plugin_url + '/js/transposhedit.js');
        }
    });
}(jQuery)); // end of closure