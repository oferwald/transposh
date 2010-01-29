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
// source - 0 is human, 1 is google translate - 2 is msn translate , and higher reserved for future engines
/*global Date, Math, Microsoft, alert, clearTimeout, document, google, jQuery, setTimeout, t_jp, window */
(function ($) { // closure
    var langLoaded, loadLang, getMSN,
    // number of phrases that might be translated
    possibly_translateable,
    // ids of progress bars
    t_jp_prefix = t_jp.prefix,
    progressbar_id = t_jp_prefix + "pbar",
    progressbar_posted_id = progressbar_id + "_s",
    source = 1,
    //Ajax translation
    done_posted = 0, /*Timer for translation aggregation*/ timer, tokens = [], translations = []
    // the languages supported externally
    // extracted using function above + he|zh-tw|pt that we know
    //google_langs = 'af|sq|ar|be|bg|ca|zh|zh-CN|zh-TW|hr|cs|da|nl|en|et|tl|fi|fr|gl|de|el|iw|hi|hu|is|id|ga|it|ja|ko|lv|lt|mk|ms|mt|no|fa|pl|pt-PT|ro|ru|sr|sk|sl|es|sw|sv|tl|th|tr|uk|vi|cy|yi|he|zh-tw|pt',
    // got this using Microsoft.Translator.GetLanguages() with added zh and zh-tw for our needs
    //bing_langs = 'ar,bg,zh-chs,zh-cht,cs,da,nl,en,ht,fi,fr,de,el,he,it,ja,ko,pl,pt,ru,es,sv,th,zh,zh-tw';
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
            $("#" + t_jp_prefix + img_segment_id).attr('source', 1); // source is 1
            img.removeClass('tr-icon-yellow').removeClass('tr-icon-green').addClass('tr-icon-yellow');
        };

        // rewrite text for all matching items at once
        $("*[token='" + token + "'][hidden!='y']")
        .html(translation)
        .each(fix_image);

        // TODO - FIX hidden elements too (need to update father's title)
        $("*[token='" + token + "'][hidden='y']")
        .attr('trans', translation)
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
                done_posted += $("*[token='" + tokens[i] + "']").size();
            //}
            }
            $.ajax({
                type: "POST",
                url: t_jp.post_url,
                data: data,
                success: function () {
                    // Success now only updates the save progress bar (green)
                    make_progress(progressbar_posted_id, done_posted / possibly_translateable * 100);
                    //$('#' + progressbar_posted_id).progressbar('value', done_posted / possibly_translateable * 100);
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

    //function for auto translation
    function do_auto_translate() {
        // auto_translated_previously...
        var auto_translated_phrases = [], binglang = t_jp.lang;
        $("." + t_jp_prefix + '[source=""]').each(function (i) {
            // not needed!
            //var translated_id = $(this).attr('id'),
            var token = $(this).attr('token'),
            //alert(translated_id);
            // we only have orig if we have some translation,?
            to_trans = $(this).attr('orig');
            if (to_trans === undefined) {
                to_trans = $(this).html();
            }
            if (auto_translated_phrases[to_trans] !== 1) {
                auto_translated_phrases[to_trans] = 1;
                if (typeof Microsoft !== 'undefined') {
                    
                    if (binglang === 'zh') {
                        binglang = 'zh-chs';
                    } else if (binglang === 'zh-tw') {
                        binglang = 'zh-cht';
                    }
                    try {
                        Microsoft.Translator.translate(to_trans, "", binglang, function (translation) {
                            ajax_translate(token, $("<div>" + translation + "</div>").text());
                            make_progress(progressbar_id, (possibly_translateable - $("." + t_jp_prefix + '[source=""]').size()) / possibly_translateable * 100);
                            //$('#' + progressbar_id).progressbar('value', (possibly_translateable - $("." + t_jp_prefix + '[source=""]').size()) / possibly_translateable * 100);
                        });
                    }
                    catch (err) {
                    // Maybe fallback?
                    // console.log("There was an error using Microsoft.Translator - probably a bad key or URL used in key. (" + err + ")");
                    }


                } else {
                    google.language.translate(to_trans, "", t_jp.lang, function (result) {
                        if (!result.error) {
                            // we no longer refer to segment IDs, just tokens
                            //var segment_id = translated_id.substr(translated_id.lastIndexOf('_') + 1);
                            // No longer need because now included in the ajax translate
                            //fix_page($("<div>" + result.translation + "</div>").text(), 1, segment_id);
                            // ????
                            //to_trans = $(this).attr('orig');
                            ajax_translate(token, $("<div>" + result.translation + "</div>").text());
                            // update the regular progress bar
                            // done = possibly_translateable - $("." + t_jp_prefix + '[source=""]').size();
                            make_progress(progressbar_id, (possibly_translateable - $("." + t_jp_prefix + '[source=""]').size()) / possibly_translateable * 100);
                            //$('#' + progressbar_id).progressbar('value', (possibly_translateable - $("." + t_jp_prefix + '[source=""]').size()) / possibly_translateable * 100);
                        }
                    });
                }
            }
        });
    }

    loadLang = function () {
        google.load("language", "1", {
            "callback" : langLoaded
        });
    };

    $(document).ready(
        function () {
            // this is the set_default_language function
            // attach a function to the set_default_language link if its there
            $('#' + t_jp_prefix + 'setdeflang').click(function () {
                $.get(t_jp.post_url + "?tr_cookie=" + Math.random());
                $(this).hide("slow");
                return false;
            });

            var now;
            // translationstats not used yet
            //var translationstats, possibly_translateable, now;
            // now lets check if auto translate is needed
            //translationstats = $("meta[name=translation-stats]").attr("content");
            // Logic borrowed from jquery and http://json.org/json2.js - Didn't see the reason for that, if someone can modify the html, he can probably do any script he wants too...
            /*if (/^[\],:{}\s]*$/.test(translationstats.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g, "@")
                .replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, "]")
                .replace(/(?:^|:|,)(?:\s*\[)+/g, ""))) {*/

            // Try to use the native JSON parser first
            /*if (window.JSON && window.JSON.parse) {
                translationstats = window.JSON.parse(translationstats);

            } else {
                translationstats = (new Function("return " + translationstats))();
            }*/

            /*} else {
					throw "Invalid JSON: " + data;
				}*/

            //            var translationstats = window["eval"]("(" + $("meta[name=translation-stats]").attr("content") + ")"), possibly_translateable, now;
            //if (translationstats !== undefined) {
            //possibly_translateable = (translationstats.total_phrases - translationstats.translated_phrases - (translationstats.meta_phrases - translationstats.meta_translated_phrases));
            possibly_translateable = $("." + t_jp_prefix + '[source=""]').size();

            now = new Date();
            // we make sure script sub loaded are cached
            $.ajaxSetup({
                cache: true
            });
            // we'll only auto-translate and load the stuff if we either have more than 5 candidate translations, or more than one at 4am, and this language is supported...
            if ((possibly_translateable > 5 || (now.getHours() === 4 && possibly_translateable > 0)) &&
                (t_jp.google || t_jp.msn)) {
                // if we have a progress bar, we need to load the jqueryui before the auto translate, after the google was loaded, otherwise we can just go ahead
                langLoaded = function () {
                    if (t_jp.progress) {
                        var loaduiandtranslate = function () {
                            $.xLazyLoader({
                                js: 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js',
                                css: 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/themes/ui-lightness/jquery-ui.css',
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
                };
                // we now start the chain that leads to auto-translate (with or without progress)
                //if supported in msn and msn is prefered or not supported in google than msn and we have the msn key
                if ((t_jp.msn && t_jp.preferred === 2) || !t_jp.google) {
                    source = 2;
                    getMSN = function () {
                        $.getScript('http://api.microsofttranslator.com/V1/Ajax.svc/Embed?appId=' + t_jp.msnkey, langLoaded);
                    };
                    // don't know why, but that's how it works
                    if (t_jp.edit && t_jp.progress) {
                        $.getScript(t_jp.plugin_url + '/js/lazy.js', getMSN);
                    } else {
                        getMSN();
                    }
                } else {
                    $.getScript('http://www.google.com/jsapi', loadLang);
                }
            }
            //}

            // this is the part when we have editor support
            if (t_jp.edit) {
                $.getScript(t_jp.plugin_url + '/js/transposhedit.js');
            }
        });
}(jQuery)); // end of closure