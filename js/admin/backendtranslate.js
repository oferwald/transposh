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

/*global Date, Math, alert, escape, clearTimeout, document, jQuery, setTimeout, t_jp, t_be, window */
(function ($) { // closure

    var timer;
    var items = 0;
    var translations = [];
    var tokens = [];
    var langs = [];
    var sources = [];
    var BATCH_SIZE = 512;
    var pair_count = 0;
    var curr_pair = 0;

    // move the progress bar a bit
    function make_progress(translation, lang) {
        curr_pair += 1;
        $("#progress_bar").progressbar('value', curr_pair / pair_count * 100);
        $('#p').text('(' + lang + ') ' + translation);
        if (curr_pair === pair_count) {
            $("#tr_loading").data("done", true);
        }
    }

    // batch items for posting to server.. nice touch added for different sources for same batch...
    function ajax_translate_me(token, translation, lang, source) {
        translation = $("<div>" + $.trim(translation) + "</div>").text(); // fix some char bugs
        make_progress(translation, lang);
        // we aggregate translations together, 200ms from the last translation we will send the timer
        // so here we remove it so nothing unexpected happens
        clearTimeout(timer);
        items += 1;
        // push translations - we'll assume something is different...
        tokens.push(token);
        translations.push(translation);
        langs.push(lang);
        sources.push(source);
        timer = setTimeout(function () {
            var data = {
                action: 'tp_translation',
                items: items
            }, i;
            // this is the "smart" stuff, only coding changed info
            for (i = 0; i < items; i += 1) {
                if (tokens[i] !== tokens[i - 1]) {
                    data["tk" + i] = tokens[i];
                }
                if (langs[i] !== langs[i - 1]) {
                    data["ln" + i] = langs[i];
                }
                if (translations[i] !== translations[i - 1]) {
                    data["tr" + i] = translations[i];
                }
                if (sources[i] !== sources[i - 1]) {
                    data["sr" + i] = sources[i];
                }
            }
            $.ajax({
                type: "POST",
                url: t_jp.ajaxurl, // FIX ALL!
                data: data,
                success: function () {
                },
                error: function () {
                }
            });
            // as we posted, we can come clean (TODO - future test of results)
            items = 0;
            translations = [];
            tokens = [];
            langs = [];
            sources = [];
        }, 200); // wait 200 ms...
    }

    // and the invoker
    function do_mass_bing_invoker(tokens, trans, lang) {
        var binglang = lang;
        // fix this in ms mass...
        if (binglang === 'zh') {
            binglang = 'zh-chs';
        } else if (binglang === 'zh-tw') {
            binglang = 'zh-cht';
        }
        t_jp.dbt(trans, function (result) {
            $(result).each(function (i) {
                ajax_translate_me(tokens[i], this.TranslatedText, lang, 2); // notice the source
            });
        }, binglang);
    }

    function do_mass_apertium_invoker(tokens, trans, lang) {
        t_jp.dat(trans, function (result) {
            // we assume that 2xx answer should be good, 200 is good, 206 is partially good (some errors)
            if (result.responseStatus >= 200 && result.responseStatus < 300) {
                // single items get handled differently
                if (result.responseData.translatedText !== undefined) {
                    ajax_translate_me(tokens[0], result.responseData.translatedText);
                } else {
                    $(result.responseData).each(function (i) {
                        if (this.responseStatus === 200) {
                            ajax_translate_me(tokens[i], this.responseData.translatedText, lang, 3);
                        }
                    });
                }
            }
        }, lang);
    }

    function do_mass_google_invoker(tokens, trans, lang) {
        t_jp.dgpt(trans, function (result) {
            $(result.results).each(function (i) {
                ajax_translate_me(tokens[i], this, lang, 1);
            });
        }, lang);
    }

    function do_mass_yandex_invoker(tokens, trans, lang) {
        t_jp.dyt(trans, function (result) {
            $(result.results).each(function (i) {
                ajax_translate_me(tokens[i], this, lang, 4);
            });
        }, lang);
    }

    function do_mass_baidu_invoker(tokens, trans, lang) {
        t_jp.dut(trans, function (result) {
            $(result.results).each(function (i) {
                ajax_translate_me(tokens[i], this, lang, 4);
            });
        }, lang);
    }

    function do_invoker(batchtokens, batchtrans, currlang) {
        t_jp.preferred.some(function (engine) {
            if (t_be[engine + '_langs'].indexOf(currlang) !== -1) {
                if (engine === 'a') {
                    do_mass_apertium_invoker(batchtokens, batchtrans, currlang);
                }
                if (engine === 'b') {
                    do_mass_bing_invoker(batchtokens, batchtrans, currlang);
                }
                if (engine === 'g') {
                    do_mass_google_invoker(batchtokens, batchtrans, currlang);
                }
                if (engine === 'y') {
                    do_mass_yandex_invoker(batchtokens, batchtrans, currlang);
                }
                if (engine === 'u') {
                    do_mass_baidu_invoker(batchtokens, batchtrans, currlang);
                }
                return true;
            }
        });
    }

    // the main translate function
    function translate_post(postid) {
        var currlang = '',
                tokens = [],
                strings = [],
                str, name, val,
                batchlength = 0,
                batchtrans = [],
                batchtokens = [],
                to_trans;

        $("#tr_loading").data("done", false);
        // get the post // FIX
        $.ajax({
            url: ajaxurl,
            dataType: 'json',
            data: {
                action: "tp_post_phrases",
                post: postid
            },
            cache: false,
            success: function (json) {
                // if we got no results than seems like we have nothing to translate
                $("#tr_translate_title").html("Translating post: " + json.posttitle);
                if (json.length === undefined) {
                    $("#tr_loading").html('Nothing left to translate');
                    $("#tr_loading").data("done", true);
                    return;
                }
                // calculate # of pairs
                pair_count = 0;
                curr_pair = 0;
                for (name in json.p) {
                    pair_count += json.p[name].l.length;
                }

                // create progress bars
                $("#tr_loading").html('<br/>Translation: <span id="p"></span><div id="progress_bar"/>');
                $("#progress_bar").progressbar({
                    value: 0
                });

                // per language passing...
                for (var lang in json.langs) {
                    currlang = json.langs[lang];
                    strings = [];
                    tokens = [];
                    for (name in json.p) {
                        val = json.p[name];
                        // we have a winner
                        if (val.l.indexOf(currlang) !== -1) {
                            // add to candidates
                            strings.push(unescape(name));
                            tokens.push(unescape(name));
                            val.l.splice(val.l.indexOf(currlang), 1);
                            // if no more languages, we can remove the item from further processing
                            if (val.l.length === 0) {
                                json.length -= 1;
                                delete json.p[name];
                            }
                        }
                    }
                    if (strings.length) {
                        for (str in strings) {
                            to_trans = strings[str];
                            if (batchlength + to_trans.length > BATCH_SIZE) {
                                do_invoker(batchtokens, batchtrans, currlang);
                                batchlength = 0;
                                batchtrans = [];
                                batchtokens = [];
                            }
                            batchlength += to_trans.length;
                            batchtokens.push(tokens[str]);
                            batchtrans.push(to_trans);
                        }

                        // this invokation is for the remaining items
                        do_invoker(batchtokens, batchtrans, currlang);
                    }
                }

            }
        });
    }
    // TODO - just expose this one, not the entire set of items
    window.translate_post = translate_post;

    // If we have a single post, we can just go through with it
    $(function () {
        if (t_be.post) {
            translate_post(t_be.post);
        }
    });
}(jQuery)); // end of closure