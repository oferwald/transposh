/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
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
            url: transposh_params.post_url,
            data: data,
            success: function() {
            },

            error: function(req) {
            }
        });
        translations = [];
        tokens = [];
        langs = [];
    }, 200); // wait 200 ms...
}
/*

            */
//to run at start
jQuery.noConflict();
google.load("language", "1");

//read parameters
var transposh_params = new Array();
var ext_langs = 'he|zh-tw|pt|fa|af|be|is|ga|mk|ms|sw|ws|cy|yi';
jQuery("script[src*='transposhadmin.js']").each(function (j) {
    var query_string = unescape(this.src.substring(this.src.indexOf('?')+1));
    var parms = query_string.split('&');
    for (var i=0; i<parms.length; i++) {
        var pos = parms[i].indexOf('=');
        if (pos > 0) {
            var key = parms[i].substring(0,pos);
            var val = parms[i].substring(pos+1);
            transposh_params[key] = val;
        }
    }
});

jQuery(document).ready(function() {
    //var count = 0;
    var p_count = 0;
    var prev_name='';
    var l_count = 0;

    jQuery.getJSON(transposh_params.post_url+"?tr_phrases_post=y&post="+transposh_params.post+"&random="+Math.random(), function(json) { // need to add random to avoid getting cached!
        // if we got no results than seems like we have nothing to translate
        if (json == null) {
             jQuery("#tr_loading").replaceWith('Nothing left to translate');
            return;
        }
        // create progress bars
        jQuery("#tr_loading").replaceWith('Translating<br/>Phrase: <span id="p"></span><div id="progress_bar"/>Target lanaguage: <span id="l"></span><div id="progress_bar2"/><span id="r"></span>')
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
                            ajax_translate_me(val.t,jQuery("<div>"+result.translation+"</div>").text(),lang);
                        }
                    });
                    //count++;
                //}
            });
        });
    });
});