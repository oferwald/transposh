/*  Copyright © 2009-2010 Transposh Team (website : http://transposh.org)
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

/*global Date, Math, Microsoft, alert, escape, clearTimeout, document, google, jQuery, setTimeout, t_jp, window */
(function ($) { // closure
    var
    // those two are constants...
    MSN_APPID = 'FACA8E2DF8DCCECE0DC311C6E57DA98EFEFA9BC6',
    BATCH_SIZE = 128,
    // number of phrases that might be translated
    possibly_translateable,
    // ids of progress bars
    t_jp_prefix = t_jp.prefix,
    progressbar_id = t_jp_prefix + "pbar",
    progressbar_posted_id = progressbar_id + "_s",
    // source - 0 is human, 1 is google translate - 2 is msn translate , and higher reserved for future engines
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

        // rewrite text for all matching items at once
        $("*[data-token='" + token + "'][data-hidden!='y']")
        .html(translation)
        .each(fix_image);

        // TODO - FIX hidden elements too (need to update father's title)
        $("*[data-token='" + token + "'][data-hidden='y']")
        .attr('data-trans', translation)
        .each(fix_image);
    }

    // function to move the progress bars (if needed)
    function make_progress(id, value) {
        if (t_jp.progress) {
            $('#' + id).progressbar('value', value);
        }
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
                translation_posted: "2",
                items: tokens.length // we can do this here because all tokens will be different
            }, i;
            for (i = 0; i < tokens.length; i += 1) {
                data["tk" + i] = tokens[i];
                data["tr" + i] = translations[i];
                // We are pre-accounting the progress bar here - which is not very nice
                //if (source > 0) {
                done_posted += $("*[data-token='" + tokens[i] + "']").size();
            //}
            }
            $.ajax({
                type: "POST",
                url: t_jp.post_url,
                data: data,
                success: function () {
                    // Success now only updates the save progress bar (green)
                    make_progress(progressbar_posted_id, done_posted / possibly_translateable * 100);
                }
            // we removed the error function, as there is no alert for automated thing, this will silently fail
            // which although bad, is what we can do for now
            });
            translations = [];
            tokens = [];
        }, 200); // wait 200 ms... -- TODO, maybe do - items*3
    }


    // function that creates the progress bar html
    // TODO: change the id
    function create_progress_bar() {
        // progress bar is for alteast 5 items
        $("#" + t_jp_prefix + "credit").css({
            'overflow': 'auto'
        }).append('<div style="float: left;width: 90%;height: 10px" id="' + progressbar_id + '"/><div style="margin-bottom:10px;float:left;width: 90%;height: 10px" id="' + progressbar_posted_id + '"/>');
        $('#' + progressbar_id).progressbar({
            value: 0
        });
        $('#' + progressbar_posted_id).progressbar({
            value: 0
        });
        // color the "save" bar
        $('#' + progressbar_posted_id + " > div").css({
            'background': '#28F828',
            'border' : "#08A908 1px solid"
        });
    }

    // happens on traslate success
    function auto_translate_success(token, translation) {
        ajax_translate(token, $("<div>" + translation + "</div>").text());
        make_progress(progressbar_id, (possibly_translateable - $("." + t_jp_prefix + '[data-source=""]').size()) / possibly_translateable * 100);
    }

    function do_mass_google_translate(batchtrans, lang, callback) {
        var q = '';
        $(batchtrans).each(function (i) {
            q += '&q=' + escape(batchtrans[i]);
        });
        // console.log (q);
        $.ajax({
            url: 'http://ajax.googleapis.com/ajax/services/language/translate' +
            '?v=1.0' + q + '&langpair=%7C' + lang,
            dataType: "jsonp",
            success: callback
        });
    }

    function do_mass_google_invoker(tokens, trans, lang) {
        do_mass_google_translate(trans, lang, function (result) {
            if (result.responseStatus === 200) {
                //FIXME (1 item..)
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
        });
    }

    function do_mass_ms_translate(batchtrans, lang, callback) {
        var q = "[";
        $(batchtrans).each(function (i) {
            q += '"' + escape(batchtrans[i]) + '",';
        });
        q = q.slice(0, -1) + ']';
        $.ajax({
            url: 'http://api.microsofttranslator.com/V2/Ajax.svc/TranslateArray?appId=' + MSN_APPID + '&to=' + lang + '&texts=' + q,
            dataType: "jsonp",
            jsonp: "oncomplete",
            success: callback
        });
    }

    function do_mass_ms_invoker(tokens, trans, lang) {
        do_mass_ms_translate(trans, lang, function (result) {
            $(result).each(function (i) {
                auto_translate_success(tokens[i], this.TranslatedText);
            });
        });
    }

    // converts a language to a one used by bing
    function to_binglang(lang) {
        var blang = lang;
        if (blang === 'zh') {
            blang = 'zh-chs';
        } else if (blang === 'zh-tw') {
            blang = 'zh-cht';
        }
        return blang;
    }

    //function for auto translation
    function do_auto_translate() {
        // auto_translated_previously...
        var auto_translated_phrases = [], binglang = to_binglang(t_jp.lang), batchlength = 0, batchtrans = [], batchtokens = [];
        //const BATCH_SIZE = 512;
        $("." + t_jp_prefix + '[data-source=""]').each(function (i) {
            // not needed!
            //var translated_id = $(this).attr('id'),
            var token = $(this).attr('data-token'),
            //alert(translated_id);
            // we only have orig if we have some translation,?
            to_trans = $(this).attr('data-orig');
            if (to_trans === undefined) {
                to_trans = $(this).html();
            }
            if (auto_translated_phrases[to_trans] !== 1) {
                auto_translated_phrases[to_trans] = 1;
                if (batchlength + to_trans.length > BATCH_SIZE) {
                    //var tmptokens = batchtokens; // context...
                    // if msn is the default
                    if (t_jp.msn && t_jp.preferred === '2') {
                        do_mass_ms_invoker(batchtokens, batchtrans, binglang);
                    } else {
                        do_mass_google_invoker(batchtokens, batchtrans, t_jp.lang);

                    }
                    batchlength = 0;
                    batchtrans = [];
                    batchtokens = [];
                }
                batchlength += to_trans.length;
                batchtokens.push(token);
                batchtrans.push(to_trans);
            //  if msn is the default
            /*if (t_jp.msn && t_jp.preferred == 2) {
		    //   try {
		    ms_trans(to_trans, "", binglang, function (translation) {
			// check error
			auto_translate_success(token,translation);
		    });
		} else {
		    google_trans(to_trans, "", t_jp.lang, function (result) {
			if (result.responseStatus == 200) {
			    auto_translate_success(token,result.responseData.translatedText);
			} //else { // TODO: errors...
		    });
		}*/
            }
        });
        if (t_jp.msn && t_jp.preferred === '2') {
            do_mass_ms_invoker(batchtokens, batchtrans, binglang);
        } else {
            do_mass_google_invoker(batchtokens, batchtrans, t_jp.lang);
        }
    }

    $(document).ready(
        function () {
            // this is the set_default_language function
            // attach a function to the set_default_language link if its there
            $('#' + t_jp_prefix + 'setdeflang').click(function () {
                $.get(t_jp.post_url + "?tr_cookie=" + Math.random());
                $(this).hide("slow");
                return false;
            });

            // how many pharses are yet untranslated
            possibly_translateable = $("." + t_jp_prefix + '[data-source=""]').size();

            //now = new Date();
            // we make sure script sub loaded are cached
            $.ajaxSetup({
                cache: true
            });
            // was: we'll only auto-translate and load the stuff if we either have more than 5 candidate translations, or more than one at 4am, and this language is supported...
            // we'll translate if there's any candidate...?
            if // ((possibly_translateable > 5 || (now.getHours() === 4 && possibly_translateable > 0)) &&
            (possibly_translateable && (t_jp.google || t_jp.msn)) {
                // if we have a progress bar, we need to load the jqueryui before the auto translate, after the google was loaded, otherwise we can just go ahead
                if (t_jp.progress) {
                    var loaduiandtranslate = function () {
                        $.xLazyLoader({
                            js: 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/jquery-ui.min.js',
                            css: 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/ui-lightness/jquery-ui.css',
                            success: function () {
                                create_progress_bar();
                                do_auto_translate();
                            }
                        });
                    };
                    if (typeof $.xLazyLoader === 'function') {
                        loaduiandtranslate();
                    } else {
                        $.getScript(t_jp.plugin_url + '/js/lazy.js', loaduiandtranslate);
                    }
                } else {
                    do_auto_translate();
                }
            }

            // this is the part when we have editor support
            if (t_jp.edit) {
                $.getScript(t_jp.plugin_url + '/js/transposhedit.js');
            }
        });
}(jQuery)); // end of closure