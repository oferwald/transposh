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

/*global Date, Math, alert, escape, clearTimeout, document, jQuery, setTimeout, t_jp, window */

var timer;
var items = 0;
var translations = [];
var tokens = [];
var langs = [];
var sources = [];
var BATCH_SIZE = 128;
var pair_count = 0;
var curr_pair = 0;
t_jp.MSN_APPID = 'FACA8E2DF8DCCECE0DC311C6E57DA98EFEFA9BC6';

// move the progress bar a bit
function make_progress(translation, lang) {
    curr_pair += 1;
    jQuery("#progress_bar").progressbar('value', curr_pair / pair_count * 100);
    jQuery('#p').text('(' + lang + ') ' + translation);
    if (curr_pair === pair_count) {
        jQuery("#tr_loading").data("done", true);
    }
}

// batch items for posting to server.. nice touch added for different sources for same batch...
function ajax_translate_me(token, translation, lang, source) {
    translation = jQuery("<div>" + $.trim(translation) + "</div>").text(); // fix some char bugs
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
            translation_posted: "2",
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
        jQuery.ajax({
            type: "POST",
            url: t_jp.post_url,
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

// this is the mass translate for MS
function do_mass_ms_translate(batchtrans, callback) {
    var q = "[";
    jQuery(batchtrans).each(function (i) {
        q += '"' + encodeURIComponent(batchtrans[i]) + '",';
    });
    q = q.slice(0, -1) + ']';
    jQuery.ajax({
        url: 'http://api.microsofttranslator.com/V2/Ajax.svc/TranslateArray?appId=' + t_jp.MSN_APPID + '&to=' + t_jp.binglang + '&texts=' + q,
        dataType: "jsonp",
        jsonp: "oncomplete",
        success: callback
    });
}

// and the invoker
function do_mass_ms_invoker(tokens, trans, lang) {
    t_jp.binglang = lang;
    // fix this in ms mass...
    if (t_jp.binglang === 'zh') {
        t_jp.binglang = 'zh-chs';
    } else if (t_jp.binglang === 'zh-tw') {
        t_jp.binglang = 'zh-cht';
    }
    do_mass_ms_translate(trans, function (result) {
        jQuery(result).each(function (i) {
            ajax_translate_me(tokens[i], this.TranslatedText, lang, 2); // notice the source
        });
    });
}

// this is a mass translate of one string to many langs
function do_mass_google_translate_l(tran, langs,  callback) {
    var langpairs = '', key;
    //$(langs).each(function (i) {
    for (key in langs) {
        langpairs += '&langpair=%7C' + langs[key];
    }

    jQuery.ajax({
        url: 'http://ajax.googleapis.com/ajax/services/language/translate' +
        '?v=1.0&q=' + encodeURIComponent(tran) + langpairs,
        dataType: "jsonp",
        success: callback
    });
}

// the invoker
function do_mass_google_invoker_l(token, to_tran, langs) {
    do_mass_google_translate_l(to_tran, langs, function (result) {
        if (result.responseStatus === 200) {
            // single items get handled differently
            if (result.responseData.translatedText !== undefined) {
                ajax_translate_me(token, result.responseData.translatedText, langs[0], 1); // notice the source...
            } else {
                jQuery(result.responseData).each(function (i) {
                    if (this.responseStatus === 200) {
                        ajax_translate_me(token, this.responseData.translatedText, langs[i], 1);
                    }
                });
            }
        }
    });
}

// the main translate function
function translate_post(postid) {
    var currlang = '',
    tokens = [],
    strings = [],
    lang, str, name, val,
    batchlength = 0,
    batchtrans = [],
    batchtokens = [],
    to_trans;

    jQuery("#tr_loading").data("done", false);
    // get the post
    jQuery.getJSON(t_jp.post_url + "?tr_phrases_post=y&post=" + postid + "&random=" + Math.random(), function (json) { // need to add random to avoid getting cached!
        // if we got no results than seems like we have nothing to translate
        jQuery("#tr_translate_title").html("Translating post: " + json.posttitle);
        if (json.length === undefined) {
            jQuery("#tr_loading").html('Nothing left to translate');
            jQuery("#tr_loading").data("done", true);
            return;
        }
        // calculate # of pairs
        pair_count = 0;
        curr_pair = 0;
        for (name in json.p) {
            pair_count += json.p[name].l.length;
        }

        // create progress bars
        jQuery("#tr_loading").html('<br/>Translation: <span id="p"></span><div id="progress_bar"/>');
        jQuery("#progress_bar").progressbar({
            value: 0
        });

        // per language passing...
        // this things happens when msn translate is default
        if (t_jp.preferred === '2') {
            // traverse on langs of msn
            for (lang in t_jp.m_langs) {
                currlang = t_jp.m_langs[lang];
                strings = [];
                tokens = [];
                for (name in json.p) {
                    val = json.p[name];
                    // we have a winner
                    if (val.l.indexOf(currlang) !== -1) {
                        // add to candidates
                        strings.push(unescape(name));
                        tokens.push(val.t);
                        val.l.splice(val.l.indexOf(currlang), 1);
                        // if no more languages, we can remove the item from further processing
                        if (val.l.length === 0) {
                            json.length -= 1;
                            delete json.p[name];
                        }
                    }
                }
                // we had some matches - now we batchify
                if (strings.length) {
                    for (str in strings) {
                        to_trans = strings[str];
                        if (batchlength + to_trans.length > BATCH_SIZE) {
                            do_mass_ms_invoker(batchtokens, batchtrans, currlang);
                            batchlength = 0;
                            batchtrans = [];
                            batchtokens = [];
                        }
                        batchlength += to_trans.length;
                        batchtokens.push(tokens[str]);
                        batchtrans.push(to_trans);
                    }

                    // this invokation is for the remaining items
                    do_mass_ms_invoker(batchtokens, batchtrans, currlang);
                }
            }
        }

        // in the google thingy we just batch by string, much simpler, maybe we should
        // also batch if we have the same language for all (far future TODO)
        for (name in json.p) {
            val = json.p[name];
            do_mass_google_invoker_l(val.t, unescape(name), val.l);
        }
    // FIX??  ajax_translate_me(val.t,jQuery("<div>"+result.translation+"</div>").text(),lang);
    });
}
// TODO - just expose this one, not the entire set of items

// If we have a single post, we can just go through with it
jQuery(document).ready(function () {
    if (t_jp.post) {
        translate_post(t_jp.post);
    }
});