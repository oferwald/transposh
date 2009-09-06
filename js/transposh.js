/*  Copyright © 2009 Transposh Team (website : http://transposh.org)
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

// fetch translation from google translate...
function getgt()
{
    jQuery(":button:contains('Suggest - Google')").attr("disabled","disabled").addClass("ui-state-disabled");
    google.language.translate(jQuery("#"+transposh_params.prefix+"original").val(), "", transposh_params.lang, function(result) {
        if (!result.error) {
            jQuery("#"+transposh_params.prefix+"translation").val(jQuery("<div>"+result.translation+"</div>").text())
            .keyup();
        }
    });
}

// fetch translation from bing... google translate...
function getbt()
{
    jQuery(":button:contains('Suggest - Bing')").attr("disabled","disabled").addClass("ui-state-disabled");
    var binglang = transposh_params.lang;
    if (binglang == 'zh') {binglang = 'zh-chs'}
    if (binglang == 'zh-tw') {binglang = 'zh-cht'}
    Microsoft.Translator.translate(jQuery("#"+transposh_params.prefix+"original").val(), "", binglang, function(translation) {
        jQuery("#"+transposh_params.prefix+"translation").val(jQuery("<div>"+translation+"</div>").text())
        .keyup();
    });
}

//Ajax translation
var done_p = 0;
var togo = 0;
//Timer for translation aggregation
var timer;
function do_timer(translation) {
    alert ("timer..."+translation);
}

var tokens = new Array();
var translations = new Array();

function ajax_translate(translation,source,segment_id) {
    // we aggregate translations together, 200ms from the last translation we will send the timer
    // so here we remove it so nothing unexpected happens
    clearTimeout(timer);
    // push translations
    tokens.push(jQuery("#"+transposh_params.prefix + segment_id).attr('token'));
    translations.push(translation);
    // This is a change - as we fix the pages before we got actual confirmation (worked well for auto-translation)
    fix_page(translation,source,segment_id);
    timer = setTimeout(function() {
        var data = {
            lang: transposh_params.lang,
            source: source,
            translation_posted: "1",
            items: tokens.length
        };
        for (var i = 0; i < tokens.length; i++) {
            data["tk"+i] = tokens[i];
            data["tr"+i] = translations[i];
            // We are pre-accounting the progress bar here - which is not very nice
            if (source > 0) {
                done_p += jQuery("*[token='"+tokens[i]+"']").size();
            }
        }
        jQuery.ajax({
            type: "POST",
            url: transposh_params.post_url,
            data: data,
            success: function() {
                // Success now only updates the save progress bar (green)
                if (transposh_params.progress) {
                    if (togo > 4 && source > 0) {
                        jQuery("#progress_bar2").progressbar('value' , done_p/togo*100);
                    }
            
                }
            },
                
            error: function(req) {
                if (source == 0) {
                    alert("Error !!! failed to translate.\n\nServer's message: " + req.statusText);
                }
            }
        });
        translations = [];
        tokens = [];
    }, 200); // wait 200 ms...
}

function fix_page(translation,source,segment_id) {
    var token = jQuery("#"+transposh_params.prefix + segment_id).attr('token');
    var new_text = translation;
    //reset to the original content - the unescaped version if translation is empty
    if(jQuery.trim(translation).length === 0) {
        new_text = jQuery("#"+transposh_params.prefix + segment_id).attr('orig');
    }
    // rewrite text for all matching items at once
    jQuery("*[token='"+token+"'][hidden!='y']")
    .html(new_text)
    .each(function (i) { // handle the image changes
        var img_segment_id = jQuery(this).attr('id').substr(jQuery(this).attr('id').lastIndexOf('_')+1);
        jQuery("#"+transposh_params.prefix+img_segment_id).attr('source',source);
        var img = jQuery("#"+transposh_params.prefix+"img_" + img_segment_id);
        img.removeClass('tr-icon-yellow').removeClass('tr-icon-green');
        if(jQuery.trim(translation).length !== 0) {
            if (source == 1) {
                //switch to the auto img
                img.addClass('tr-icon-yellow');
            } else {
                //	switch to the fix img
                img.addClass('tr-icon-green');
            }
        }
    });

    // FIX hidden elements too (need to update father's title)
    jQuery("*[token='"+token+"'][hidden='y']")
    .attr('trans',new_text)
    .each(function (i) { // handle the image changes
        var img_segment_id = jQuery(this).attr('id').substr(jQuery(this).attr('id').lastIndexOf('_')+1);
        jQuery("#"+transposh_params.prefix+img_segment_id).attr('source',source);
        var img = jQuery("#"+transposh_params.prefix+"img_" + img_segment_id);
        img.removeClass('tr-icon-yellow').removeClass('tr-icon-green');
        if(jQuery.trim(translation).length !== 0) {
            if (source == 1) {
                //switch to the auto img
                img.addClass('tr-icon-yellow');
            } else {
                //	switch to the fix img
                img.addClass('tr-icon-green');
            }
        }
    });

}

//function for auto translation
function do_auto_translate() {
    if (transposh_params.progress) {
        togo = jQuery("."+transposh_params.prefix+'[source=""]').size();
        //alert(togo);
        // progress bar is for alteast 5 items
        if (togo > 4) {
            jQuery("#"+transposh_params.prefix+"credit").append('<div style="float: left;width: 90%;height: 10px" id="progress_bar"/><div style="margin-bottom:10px;float:left;width: 90%;height: 10px" id="progress_bar2"/>')
            jQuery("#progress_bar").progressbar({
                value: 0
            });
            jQuery("#progress_bar2").progressbar({
                value: 0
            });
            // color the "save" bar
            jQuery("#progress_bar2 > div").css({
                'background':'#28F828',
                'border' : "#08A908 1px solid"
            });
        }
        var done = 0;
    }
    // auto_translated_previously...
    var auto_t_p = new Array();
    jQuery("."+transposh_params.prefix+'[source=""]').each(function (i) {
        var translated_id = jQuery(this).attr('id');
        //alert(translated_id);
        var to_trans = jQuery(this).attr('orig');
        if (to_trans == undefined) to_trans = jQuery(this).html();
        if (!(auto_t_p[to_trans] == 1)) {
            auto_t_p[to_trans] = 1;
            google.language.translate(to_trans, "", transposh_params.lang, function(result) {
                if (!result.error) {
                    var segment_id = translated_id.substr(translated_id.lastIndexOf('_')+1);
                    // No longer need because now included in the ajax translate
                    //fix_page(jQuery("<div>"+result.translation+"</div>").text(),1,segment_id);
                    ajax_translate(jQuery("<div>"+result.translation+"</div>").text(),1,segment_id);
                    if (transposh_params.progress) {
                        done = togo - jQuery("."+transposh_params.prefix+'[source=""]').size();
                        if (togo > 4) {
                            jQuery("#progress_bar").progressbar('value' , done/togo*100);
                        }
                    }
                }
            });
        }
    });
}

function confirm_close() {
    jQuery('<div id="dial" title="Close without saving?"><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>You have made a change to the translation. Are you sure you want to discard it?</p></div>').appendTo("body").dialog({
        bgiframe: true,
        resizable: false,
        height:140,
        modal: true,
        overlay: {
            backgroundColor: '#000',
            opacity: 0.5
        },
        buttons: {
            'Discard': function() {
                jQuery("#"+transposh_params.prefix+"translation").data("edit", {
                    changed: false
                });
                jQuery(this).dialog('close');
                jQuery("#"+transposh_params.prefix+"d-tabs").dialog('close');
            },
            Cancel: function() {
                jQuery(this).dialog('close');
            }
        }
    });
}

//Open translation dialog 
function translate_dialog(segment_id) {
    jQuery("#"+transposh_params.prefix+"d-tabs").remove();
    jQuery('<div id="'+transposh_params.prefix+'d-tabs" title="Edit Translation"/>').appendTo("body");
    jQuery("#"+transposh_params.prefix+"d-tabs").append('<ul/>').tabs({
        cache: true
    })
    .tabs('add',"#"+transposh_params.prefix+"d-tabs-1",'Translate')
    .tabs('add',transposh_params.post_url+'?tr_token_hist='+jQuery("#"+transposh_params.prefix + segment_id).attr('token')+'&lang='+transposh_params.lang,'History')
    .css("text-align","left")
    .css("padding",0)
    .bind('tabsload', function(event, ui) {
        //TODO, formatting here, not server side
        jQuery("table",ui.panel).addClass("ui-widget ui-widget-content").css({
            'width' : '95%',
            'padding' : '0'
        });
        //jQuery("table thead th:last",ui.panel).after("<th/>");
        jQuery("table thead tr",ui.panel).addClass("ui-widget-header");
        //jQuery("table tbody tr",ui.panel).append('<td/>');
        jQuery("table tbody td[source='1']",ui.panel).append('<span title="computer" style="display: inline-block; margin-right: 0.3em;" class="ui-icon ui-icon-gear"></span>');
        jQuery("table tbody td[source='0']",ui.panel).append('<span title="human" style="display: inline-block; margin-right: 0.3em;" class="ui-icon ui-icon-person"></span>');
    //jQuery("table tbody tr:first td:last",ui.panel).append('<span title="remove this translation" id="'+transposh_params.prefix+'revert" style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-scissors"/>');
    //jQuery("#"+transposh_params.prefix+"revert").click(function () {
    //alert ('hi');
    //});
    })
    .bind('tabsselect', function(event, ui) {
        // Change buttons
        if (jQuery(ui.tab).text() == 'Translate') {
            jQuery("#"+transposh_params.prefix+"d-tabs").dialog('option', 'buttons', tButtons);
        } else {
            jQuery("#"+transposh_params.prefix+"d-tabs").dialog('option', 'buttons', hButtons);
        }
    })
    .bind('dialogbeforeclose', function(event, ui) {
        if(jQuery("#"+transposh_params.prefix+"translation").data("edit").changed) {
            confirm_close();
            return false;
        }
        return true;
    });
    // fix for templates messing with li
    jQuery("#"+transposh_params.prefix+"d-tabs li").css("list-style-type","none").css("list-style-position","outside");
    jQuery("#"+transposh_params.prefix+"d-tabs-1").css("padding", "1px").append(
        /*'<table><tr><td>'+*/
        '<form id="'+transposh_params.prefix+'form">' +
        '<fieldset>' +
        '<label for="original">Original Text</label>' +
        '<textarea cols="80" row="3" name="original" id="'+transposh_params.prefix+'original" class="text ui-widget-content ui-corner-all" readonly="y"/>' +
        '<label for="translation">Translate To</label>' +
        '<textarea cols="80" row="3" name="translation" id="'+transposh_params.prefix+'translation" value="" class="text ui-widget-content ui-corner-all"/>' +
        '</fieldset>' +
        '</form>'/*+
        '</td><td style="width:32px">'+
        '<img src="/wp-content/plugins/transposh/img/knob/knobs/left.png"/>'+
        '<img src="/wp-content/plugins/transposh/img/knob/knobs/right.png"/>'+
        '<img id="smart" src="/wp-content/plugins/transposh/img/knob/knobs/smart.png"/>'+
        '<img src="/wp-content/plugins/transposh/img/knob/knobs/merge.png"/>'+
        '</td></tr></table>'*/);
    /*jQuery("#smart").click(function() {
        grabnext(segment_id);
    });*/
    jQuery("#"+transposh_params.prefix+"d-tabs-1 label").css("display","block");
    jQuery("#"+transposh_params.prefix+"d-tabs-1 textarea.text").css({
        'margin-bottom':'12px',
        'width' : '95%',
        'padding' : '.4em'
    });
    jQuery("#"+transposh_params.prefix+"original").val(jQuery("#"+transposh_params.prefix + segment_id).attr('orig'));
    jQuery("#"+transposh_params.prefix+"translation").val(jQuery("#"+transposh_params.prefix + segment_id).html());
    if (jQuery("#"+transposh_params.prefix + segment_id).attr('trans')) {
        jQuery("#"+transposh_params.prefix+"translation").val(jQuery("#"+transposh_params.prefix + segment_id).attr('trans'));
    }
    jQuery("#"+transposh_params.prefix+"translation").data("edit", {
        changed: false
    });
    jQuery("#"+transposh_params.prefix+"translation").keyup(function(e){
        if (jQuery("#"+transposh_params.prefix + segment_id).text() != jQuery(this).val()) {
            jQuery(this).css("background","yellow");
            jQuery(this).data("edit", {
                changed: true
            });
        } else {
            jQuery(this).css("background","");
            jQuery(this).data("edit", {
                changed: false
            });
        }
    });
    var tButtons = {};
    if (binglangs.indexOf(transposh_params.lang+',',0) > -1) {
        //ar,zh-chs,zh-cht,nl,en,fr,de,he,it,ja,ko,pl,pt,ru,es
        tButtons['Suggest - Bing'] = function() {getbt();};
    }

    if (google.language.isTranslatable(transposh_params.lang) || ext_langs.indexOf(transposh_params.lang) > -1) {
        tButtons['Suggest - Google'] = function() {getgt();};
    }
    /*    'Next': function() {
                alert(parseInt(segment_id)+1);
                translate_dialog(parseInt(segment_id)+1);
            },
            'Combine - Next': function() {
                getgt();
                //.next? .next all?
            },*/
    tButtons['Ok'] = function() {
        var translation = jQuery('#'+transposh_params.prefix+'translation').val();
        if(jQuery('#'+transposh_params.prefix+'translation').data("edit").changed) {
            ajax_translate(translation,0,segment_id);
            jQuery("#"+transposh_params.prefix+"translation").data("edit", {
                changed: false
            });
        }
        jQuery(this).dialog('close');
    };
    //tButtons["beep"] = function() {alert(Microsoft.Translator.GetLanguages())};
    var hButtons =	{
        Close: function() {
            jQuery(this).dialog('close');
        }
    };
    jQuery("#"+transposh_params.prefix+"d-tabs").dialog({
        bgiframe: true,
        modal: true,
        //width: 'auto',
        width: 500,
        buttons: tButtons
    });
}

//to run at start
jQuery.noConflict();
//read parameters
var transposh_params = new Array();
var ext_langs = 'he|zh-tw|pt|fa|af|be|is|ga|mk|ms|sw|ws|cy|yi';
jQuery("script[src*='transposh.js']").each(function (j) {
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

//console.log('hi');

google.load("language", "1");
// first we check if msn was even included
var binglangs = '';
if (typeof(Microsoft) != 'undefined') {
    try {
        binglangs = String(Microsoft.Translator.GetLanguages())+',zh,zh-tw,';        
    }
    catch (err) {
        alert("There was an error using Microsoft.Translator - probably a bad key or URL used in key. ("+err+")");
    }
}

jQuery(document).ready(
    function() {
        // an implicit param
        if (typeof(jQuery().progressbar) != 'undefined') {
            transposh_params.progress = true;
        }
        // TODO: he, iw? :)
        if (google.language.isTranslatable(transposh_params.lang) || ext_langs.indexOf(transposh_params.lang) > -1) {
            do_auto_translate();
        }
        if (transposh_params.edit) {
            // lets add the images
            jQuery("."+transposh_params.prefix).each(function (i) {
                var translated_id = jQuery(this).attr('id').substr(jQuery(this).attr('id').lastIndexOf('_')+1);
                jQuery(this).after('<span id="'+transposh_params.prefix+'img_'+translated_id+'" class="tr-icon" title="'+jQuery(this).attr('orig')+'"></span>');
                var img = jQuery('#'+transposh_params.prefix+'img_'+translated_id);
                img.click(function () {
                    translate_dialog(translated_id);
                    return false;
                }).css({
                    'border':'0px',
                    'margin':'1px',
                    'padding':'0px'
                });
                if (jQuery(this).attr('source') == '1')
                    img.addClass('tr-icon-yellow');
                else if (jQuery(this).attr('source') == '0')
                    img.addClass('tr-icon-green');
                // if the image is sourced from a hidden element - kinly "show" this
                if (jQuery(this).attr('hidden') == 'y') {
                    img.css({
                        'opacity':'0.6'
                    });
                }
            });
        }
    }
    );