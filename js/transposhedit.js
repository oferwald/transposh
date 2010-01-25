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
// source - 0 is human, 1 is gt - 2 and higher reserved for future engines
/*global Date, Math, Microsoft, alert, clearTimeout, document, google, jQuery, setTimeout, t_jp, window */
// fetch translation from google translate...
(function () { // closure
    var
    google_langs = 'af|sq|ar|be|bg|ca|zh|zh-CN|zh-TW|hr|cs|da|nl|en|et|tl|fi|fr|gl|de|el|iw|hi|hu|is|id|ga|it|ja|ko|lv|lt|mk|ms|mt|no|fa|pl|pt-PT|ro|ru|sr|sk|sl|es|sw|sv|tl|th|tr|uk|vi|cy|yi|he|zh-tw|pt',
    bing_langs = 'ar,bg,zh-chs,zh-cht,cs,da,nl,en,fi,fr,de,el,he,it,ja,ko,pl,pt,ru,es,sv,th,zh,zh-tw';

    function fix_page_human(token, translation) {
        new_text = translation;
        //reset to the original content - the unescaped version if translation is empty
        // TODO!
        /*if (jQuery.trim(translation).length === 0) {
            new_text = jQuery("#" + t_jp.prefix + segment_id).attr('orig');
        }*/

         var fix_image = function () { // handle the image changes
            var img_segment_id = jQuery(this).attr('id').substr(jQuery(this).attr('id').lastIndexOf('_') + 1),
            img = jQuery("#" + t_jp.prefix + "img_" + img_segment_id);
            jQuery("#" + t_jp.prefix + img_segment_id).attr('source', 0); // source is 0 human
            img.removeClass('tr-icon-yellow').removeClass('tr-icon-green').addClass('tr-icon-green');
            // TODO if (jQuery.trim(translation).length !== 0) { remove green on zero length?

        };
        // rewrite text for all matching items at once
        jQuery("*[token='" + token + "'][hidden!='y']")
        .html(new_text)
        .each(fix_image);

        // FIX hidden elements too (need to update father's title)
        jQuery("*[token='" + token + "'][hidden='y']")
        .attr('trans', new_text)
        .each(fix_image);
    }

    // here we don't need the timer, this is human based
    function ajax_translate_human(token, translation) {
        // push translations
        // This is a change - as we fix the pages before we got actual confirmation (worked well for auto-translation)
        fix_page_human(token, translation);
        var data = {
            ln0: t_jp.lang,
            sr0: 0, // implicit human
            translation_posted: "2",
            items: 1,
            tk0: token,
            tr0: translation
        };
        // We are pre-accounting the progress bar here - which is not very nice
        /*TODO think!!!! if (source > 0) {
            done_p += jQuery("*[token='" + token + "']").size();
        }*/
        jQuery.ajax({
            type: "POST",
            url: t_jp.post_url,
            data: data,
            success: function () {
                // Success now only updates the save progress bar (green)
                /* THINK if (t_jp.progress) {
                    if (togo > 4 && source > 0) {
                        jQuery("#progress_bar2").progressbar('value', done_p / togo * 100);
                    }

                }*/
            },

            error: function (req) {
                    alert("Error !!! failed to translate.\n\nServer's message: " + req.statusText);
            }
        });
    }

    function getgt()
    {
        if (typeof google === 'undefined') {
            langLoaded = function () {
                getgt();
            };
            jQuery.xLazyLoader({
                //                js: 'http://www.google.com/jsapi?callback=loadLang'
                js: 'http://www.google.com/jsapi',
                success: loadLang
            });
        } else {
            jQuery(":button:contains('Suggest - Google')").attr("disabled", "disabled").addClass("ui-state-disabled");
            google.language.translate(jQuery("#" + t_jp.prefix + "original").val(), "", t_jp.lang, function (result) {
                if (!result.error) {
                    jQuery("#" + t_jp.prefix + "translation").val(jQuery("<div>" + result.translation + "</div>").text())
                    .keyup();
                }
            });
        }
    }

    // fetch translation from bing translate...
    function getbt()
    {
        if (typeof Microsoft === 'undefined') {
            jQuery.xLazyLoader({
                js: 'http://api.microsofttranslator.com/V1/Ajax.svc/Embed?appId=' + t_jp.msnkey,
                success: function () {
                    getbt();
                }
            });

        } else {
            jQuery(":button:contains('Suggest - Bing')").attr("disabled", "disabled").addClass("ui-state-disabled");
            var binglang = t_jp.lang;
            if (binglang === 'zh') {
                binglang = 'zh-chs';
            }
            if (binglang === 'zh-tw') {
                binglang = 'zh-cht';
            }
            try {
                Microsoft.Translator.translate(jQuery("#" + t_jp.prefix + "original").val(), "", binglang, function (translation) {
                    jQuery("#" + t_jp.prefix + "translation").val(jQuery("<div>" + translation + "</div>").text())
                    .keyup();
                });
            }
            catch (err) {
                alert("There was an error using Microsoft.Translator - probably a bad key or URL used in key. (" + err + ")");
            }
        }
    }

    function confirm_close() {
        jQuery('<div id="dial" title="Close without saving?"><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>You have made a change to the translation. Are you sure you want to discard it?</p></div>').appendTo("body").dialog({
            bgiframe: true,
            resizable: false,
            height: 140,
            modal: true,
            overlay: {
                backgroundColor: '#000',
                opacity: 0.5
            },
            buttons: {
                'Discard': function () {
                    jQuery("#" + t_jp.prefix + "translation").data("edit", {
                        changed: false
                    });
                    jQuery(this).dialog('close');
                    jQuery("#" + t_jp.prefix + "d-tabs").dialog('close');
                },
                Cancel: function () {
                    jQuery(this).dialog('close');
                }
            }
        });
    }
    
    //Open translation dialog
    function translate_dialog(segment_id) {
        var tButtons = {}, hButtons = {};
        //only add button is bing support is defined for the language (and we got some key)
        if (bing_langs.indexOf(t_jp.lang) > -1 && t_jp.msnkey !== '') {
            //ar,zh-chs,zh-cht,nl,en,fr,de,he,it,ja,ko,pl,pt,ru,es
            tButtons['Suggest - Bing'] = function () {
                getbt();
            };
        }

        // Only add button if google supports said language
        if (google_langs.indexOf(t_jp.lang) > -1) {
            tButtons['Suggest - Google'] = function () {
                getgt();
            };
        }
        /*    'Next': function () {
                alert(parseInt(segment_id) + 1);
                translate_dialog(parseInt(segment_id) + 1);
            },
            'Combine - Next': function () {
                something?();
                //.next? .next all?
            },*/
        tButtons.Ok = function () {
            var translation = jQuery('#' + t_jp.prefix + 'translation').val(),
            token = jQuery("#" + t_jp.prefix + segment_id).attr('token');
            if (jQuery('#' + t_jp.prefix + 'translation').data("edit").changed) {
                ajax_translate_human(token, translation);
                jQuery("#" + t_jp.prefix + "translation").data("edit", {
                    changed: false
                });
            }
            jQuery(this).dialog('close');
        };
        //tButtons["beep"] = function () {alert(Microsoft.Translator.GetLanguages())};
        hButtons = {
            Close: function () {
                jQuery(this).dialog('close');
            }
        };

        jQuery("#" + t_jp.prefix + "d-tabs").remove();
        jQuery('<div id="' + t_jp.prefix + 'd-tabs" title="Edit Translation"/>').appendTo("body");
        jQuery("#" + t_jp.prefix + "d-tabs").append('<ul/>').tabs({
            cache: true
        })
        .tabs('add', "#" + t_jp.prefix + "d-tabs-1", 'Translate')
        .tabs('add', t_jp.post_url + '?tr_token_hist=' + jQuery("#" + t_jp.prefix + segment_id).attr('token') + '&lang=' + t_jp.lang, 'History')
        .css("text-align", "left")
        .css("padding", 0)
        .bind('tabsload', function (event, ui) {
            //TODO, formatting here, not server side
            jQuery("table", ui.panel).addClass("ui-widget ui-widget-content").css({
                'width' : '95%',
                'padding' : '0'
            });
            //jQuery("table thead th:last",ui.panel).after("<th/>");
            jQuery("table thead tr", ui.panel).addClass("ui-widget-header");
            //jQuery("table tbody tr", ui.panel).append('<td/>');
            jQuery("table tbody td[source='1']", ui.panel).append('<span title="computer" style="display: inline-block; margin-right: 0.3em;" class="ui-icon ui-icon-gear"></span>');
            jQuery("table tbody td[source='0']", ui.panel).append('<span title="human" style="display: inline-block; margin-right: 0.3em;" class="ui-icon ui-icon-person"></span>');
        //jQuery("table tbody tr:first td:last", ui.panel).append('<span title="remove this translation" id="' + t_jp.prefix + 'revert" style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-scissors"/>');
        //jQuery("#" + t_jp.prefix + "revert").click(function () {
        //alert ('hi');
        //});
        })
        .bind('tabsselect', function (event, ui) {
            // Change buttons
            if (jQuery(ui.tab).text() === 'Translate') {
                jQuery("#" + t_jp.prefix + "d-tabs").dialog('option', 'buttons', tButtons);
            } else {
                jQuery("#" + t_jp.prefix + "d-tabs").dialog('option', 'buttons', hButtons);
            }
        })
        .bind('dialogbeforeclose', function (event, ui) {
            if (jQuery("#" + t_jp.prefix + "translation").data("edit").changed) {
                confirm_close();
                return false;
            }
            return true;
        });
        // fix for templates messing with li
        jQuery("#" + t_jp.prefix + "d-tabs li").css("list-style-type", "none").css("list-style-position", "outside");
        jQuery("#" + t_jp.prefix + "d-tabs-1").css("padding", "1px").append(
            /*'<table><tr><td>'+*/
            '<form id="' + t_jp.prefix + 'form">' +
            '<fieldset>' +
            '<label for="original">Original Text</label>' +
            '<textarea cols="80" row="3" name="original" id="' + t_jp.prefix + 'original" class="text ui-widget-content ui-corner-all" readonly="y"/>' +
            '<label for="translation">Translate To</label>' +
            '<textarea cols="80" row="3" name="translation" id="' + t_jp.prefix + 'translation" value="" class="text ui-widget-content ui-corner-all"/>' +
            '</fieldset>' +
            '</form>'/* +
        '</td><td style="width:32px">' +
        '<img src="/wp-content/plugins/transposh/img/knob/knobs/left.png"/>' +
        '<img src="/wp-content/plugins/transposh/img/knob/knobs/right.png"/>' +
        '<img id="smart" src="/wp-content/plugins/transposh/img/knob/knobs/smart.png"/>'+
        '<img src="/wp-content/plugins/transposh/img/knob/knobs/merge.png"/>'+
        '</td></tr></table>'*/);
        /*jQuery("#smart").click(function () {
        grabnext(segment_id);
    });*/
        jQuery("#" + t_jp.prefix + "d-tabs-1 label").css("display", "block");
        jQuery("#" + t_jp.prefix + "d-tabs-1 textarea.text").css({
            'margin-bottom': '12px',
            'width' : '95%',
            'padding' : '.4em'
        });
        jQuery("#" + t_jp.prefix + "original").val(jQuery("#" + t_jp.prefix + segment_id).attr('orig'));
        jQuery("#" + t_jp.prefix + "translation").val(jQuery("#" + t_jp.prefix + segment_id).html());
        if (jQuery("#" + t_jp.prefix + segment_id).attr('trans')) {
            jQuery("#" + t_jp.prefix + "translation").val(jQuery("#" + t_jp.prefix + segment_id).attr('trans'));
        }
        jQuery("#" + t_jp.prefix + "translation").data("edit", {
            changed: false
        });
        jQuery("#" + t_jp.prefix + "translation").keyup(function (e) {
            if (jQuery("#" + t_jp.prefix + segment_id).text() !== jQuery(this).val()) {
                jQuery(this).css("background", "yellow");
                jQuery(this).data("edit", {
                    changed: true
                });
            } else {
                jQuery(this).css("background", "");
                jQuery(this).data("edit", {
                    changed: false
                });
            }
        });
        jQuery("#" + t_jp.prefix + "d-tabs").dialog({
            bgiframe: true,
            modal: true,
            //width: 'auto',
            width: 500,
            buttons: tButtons
        });
    }


    // lets add the images
    jQuery("." + t_jp.prefix).each(function (i) {
        var translated_id = jQuery(this).attr('id').substr(jQuery(this).attr('id').lastIndexOf('_') + 1), img;
        jQuery(this).after('<span id="' + t_jp.prefix + 'img_' + translated_id + '" class="tr-icon" title="' + jQuery(this).attr('orig') + '"></span>');
        img = jQuery('#' + t_jp.prefix + 'img_' + translated_id);
        img.click(function () {
            //  if we detect that jQuery.ui is missing (TODO - check tabs - etal) we load it first
            if (typeof jQuery.fn.tabs !== 'function') {
                jQuery.ajaxSetup({
                    cache: true
                });
                jQuery.getScript(t_jp.plugin_url + '/js/lazy.js', function () {
                    jQuery.xLazyLoader({
                        js: 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js',
                        css: 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/themes/ui-lightness/jquery-ui.css',
                        success: function () {
                            translate_dialog(translated_id);
                        }
                    });
                });
            } else {
                translate_dialog(translated_id);
            }
            return false;
        }).css({
            'border': '0px',
            'margin': '1px',
            'padding': '0px'
        });
        if (jQuery(this).attr('source') === '1') {
            img.addClass('tr-icon-yellow');
        }
        else if (jQuery(this).attr('source') === '0') {
            img.addClass('tr-icon-green');
        }
        // if the image is sourced from a hidden element - kindly "show" this
        if (jQuery(this).attr('hidden') === 'y') {
            img.css({
                'opacity': '0.6'
            });
        }
    });
}()); // end of closure