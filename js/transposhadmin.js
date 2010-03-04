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

var timer;
var items = 0;
var translations = [];
var tokens = [];
var langs = [];
function ajax_translate_me(token,translation,lang) {
    // we aggregate translations together, 200ms from the last translation we will send the timer
    // so here we remove it so nothing unexpected happens
    clearTimeout(timer);
    items++;
    // push translations - we'll assume something is different...
    tokens.push(token);
    translations.push(translation);
    langs.push(lang);
    ////////translations.push(translation);
    timer = setTimeout(function() {
        var data = {
            sr0: 1, // source is auto...
            translation_posted: "2",
            items: items
        };
        // this is the "smart" stuff, only coding changed info
        for (var i = 0; i < items; i++) {
            if (tokens[i] != tokens[i-1])
                data["tk"+i] = tokens[i];
            if (langs[i] != langs[i-1])
                data["ln"+i] = langs[i];
            if (translations[i] != translations[i-1])
                data["tr"+i] = translations[i];
        }
        jQuery.ajax({
            type: "POST",
            url: t_jp.post_url,
            data: data,
            success: function() {
            },

            error: function() {
            }
        });
        items = 0;
        translations = [];
        tokens = [];
        langs = [];
    }, 200); // wait 200 ms...
}

function translate_post(postid) {
    // count = 0;
    var p_count = 0;
    var prev_name='';
    var l_count = 0;
    jQuery("#tr_loading").data("done",false);
    jQuery.getJSON(t_jp.post_url+"?tr_phrases_post=y&post="+postid+"&random="+Math.random(), function(json) { // need to add random to avoid getting cached!
        // if we got no results than seems like we have nothing to translate
        jQuery("#tr_translate_title").html("Translating post: "+json.posttitle);
        if (json.length === undefined) {
            jQuery("#tr_loading").html('Nothing left to translate');
            jQuery("#tr_loading").data("done",true);
            return;
        }
        // create progress bars
        jQuery("#tr_loading").html('Translating<br/>Phrase: <span id="p"></span><div id="progress_bar"/>Target lanaguage: <span id="l"></span><div id="progress_bar2"/><span id="r"></span>');
        jQuery("#progress_bar").progressbar({
            value:0
        });
        jQuery("#progress_bar2").progressbar({
            value:0
        });
        jQuery.each(json.p, function(name, val) {
            jQuery("#progress_bar2").progressbar('value' , l_count/val.l.length*100);
            jQuery.each(val.l, function(id,lang) {
                // if (count <1000) {
                google.language.translate(name, "", lang, function(result) {
                    if (!result.error) {
                        // No longer need because now included in the ajax translate
                        if (prev_name != name) {
                            prev_name = name;
                            l_count = 0;
                            p_count++;
                        }
                        jQuery("#progress_bar").progressbar('value' , p_count/json.length*100);
                        l_count++;
                        jQuery("#progress_bar2").progressbar('value' , l_count/val.l.length*100);
                        jQuery('#p').text(jQuery("<div>"+name+"</div>").text());
                        jQuery('#l').text(lang);
                        jQuery('#r').text(jQuery("<div>"+result.translation+"</div>").text());
                        if (p_count === json.length && l_count === val.l.length)
                            jQuery("#tr_loading").data("done",true);
                        ajax_translate_me(val.t,jQuery("<div>"+result.translation+"</div>").text(),lang);
                    }
                });
            //count++;
            //}
            });
        });
    });
}

google.load("language", "1");

jQuery(document).ready(function() {
    if (t_jp.post) {
        translate_post(t_jp.post);
    }
});