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
(function ($) { // closure

    function fix_page_human(token, translation) {
        //reset to the original content - the unescaped version if translation is empty
        // TODO!
        if ($.trim(translation).length === 0) {
            translation = $("[data-token='" + token + "']").attr('data-orig');
        }

        var fix_image = function () { // handle the image changes
            var img_segment_id = $(this).attr('id').substr($(this).attr('id').lastIndexOf('_') + 1),
            img = $("#" + t_jp.prefix + "img_" + img_segment_id);
            $("#" + t_jp.prefix + img_segment_id).attr('data-source', 0); // source is 0 human
            img.removeClass('tr-icon-yellow').removeClass('tr-icon-green').addClass('tr-icon-green');
        // TODO if ($.trim(translation).length !== 0) { remove green on zero length?

        };
        // rewrite text for all matching items at once
        $("*[data-token='" + token + "'][data-hidden!='y']")
        .html(translation)
        .each(fix_image);

        // FIX hidden elements too (need to update father's title)
        $("*[data-token='" + token + "'][data-hidden='y']")
        .attr('data-trans', translation)
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
            done_p += $("*[token='" + token + "']").size();
        }*/
        $.ajax({
            type: "POST",
            url: t_jp.post_url,
            data: data,
            success: function () {
            // Success now only updates the save progress bar (green)
            /* THINK if (t_jp.progress) {
                    if (togo > 4 && source > 0) {
                        $("#progress_bar2").progressbar('value', done_p / togo * 100);
                    }

                }*/
            },

            error: function (req) {
                alert("Error !!! failed to translate.\n\nServer's message: " + req.statusText);
            }
        });
    }

    // perform google translate of single phrase via jsonp
    function google_trans(to_trans, callback) {
        $.ajax({
            url: 'http://ajax.googleapis.com/ajax/services/language/translate' +
            '?v=1.0&q=' + escape(to_trans) + '&langpair=%7C' + t_jp.lang,
            dataType: "jsonp",
            success: callback
        });
    }

    function getgt()
    {
        $(":button:contains('Suggest - Google')").attr("disabled", "disabled").addClass("ui-state-disabled");
        $(":button:contains('Suggest - Bing')").attr("disabled", "").removeClass("ui-state-disabled");
        google_trans($("#" + t_jp.prefix + "original").val(), function (result) {
            if (result.responseStatus === 200) {
                $("#" + t_jp.prefix + "translation").val($("<div>" + result.responseData.translatedText + "</div>").text())
                .keyup();
            }
        });
    }

    // perform ms translate of single phrase via jsonp
    function ms_trans(to_trans, callback) {
        $.ajax({
            url: 'http://api.microsofttranslator.com/V2/Ajax.svc/Translate?appId=' + t_jp.MSN_APPID + '&to=' + t_jp.binglang + "&text=" + encodeURIComponent(to_trans),
            dataType: "jsonp",
            jsonp: "oncomplete",
            success: callback
        });
    }

    // fetch translation from bing translate...
    function getbt()
    {
        $(":button:contains('Suggest - Bing')").attr("disabled", "disabled").addClass("ui-state-disabled");
        $(":button:contains('Suggest - Google')").attr("disabled", "").removeClass("ui-state-disabled");
        ms_trans($("#" + t_jp.prefix + "original").val(), function (translation) {
            $("#" + t_jp.prefix + "translation").val($("<div>" + translation + "</div>").text())
            .keyup();
        });
    }

    function confirm_close() {
        $('<div id="dial" title="Close without saving?"><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>You have made a change to the translation. Are you sure you want to discard it?</p></div>').appendTo("body").dialog({
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
                    $("#" + t_jp.prefix + "translation").data("edit", {
                        changed: false
                    });
                    $(this).dialog('close');
                    $("#" + t_jp.prefix + "d-tabs").dialog('close');
                },
                Cancel: function () {
                    $(this).dialog('close');
                }
            }
        });
    }

    //Open translation dialog
    function translate_dialog(segment_id) {
        var tButtons = {}, hButtons = {};
        //only add button is bing support is defined for the language (and we got some key)
        if (t_jp.msn) {
            //ar,zh-chs,zh-cht,nl,en,fr,de,he,it,ja,ko,pl,pt,ru,es
            tButtons['Suggest - Bing'] = function () {
                getbt();
            };
        }

        // Only add button if google supports said language
        if (t_jp.google) {
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
            var translation = $('#' + t_jp.prefix + 'translation').val(),
            token = $("#" + t_jp.prefix + segment_id).attr('data-token');
            if ($('#' + t_jp.prefix + 'translation').data("edit").changed) {
                ajax_translate_human(token, translation);
                $("#" + t_jp.prefix + "translation").data("edit", {
                    changed: false
                });
            }
            $(this).dialog('close');
        };
        //tButtons["beep"] = function () {alert(Microsoft.Translator.GetLanguages())};
        hButtons = {
            Close: function () {
                $(this).dialog('close');
            }
        };

        $("#" + t_jp.prefix + "d-tabs").remove();
        $('<div id="' + t_jp.prefix + 'd-tabs" title="Edit Translation"/>').appendTo("body");
        $("#" + t_jp.prefix + "d-tabs").append('<ul/>').tabs({
            cache: true
        })
        .tabs('add', "#" + t_jp.prefix + "d-tabs-1", 'Translate')
        .tabs('add', t_jp.post_url + '?tr_token_hist=' + $("#" + t_jp.prefix + segment_id).attr('data-token') + '&lang=' + t_jp.lang, 'History')
        .css("text-align", "left")
        .css("padding", 0)
        // this is the history tab...
        .bind('tabsload', function (event, ui) {
            //TODO, formatting here, not server side
            $("table", ui.panel).addClass("ui-widget ui-widget-content").css({
                'width' : '95%',
                'padding' : '0'
            });
            //$("table thead th:last",ui.panel).after("<th/>");
            $("table thead tr", ui.panel).addClass("ui-widget-header");
            //$("table tbody tr", ui.panel).append('<td/>');
            $("table tbody td[source='2']", ui.panel).append('<span title="computer" style="display: inline-block; margin-right: 0.3em;" class="ui-icon ui-icon-gear"></span>');
            $("table tbody td[source='1']", ui.panel).append('<span title="computer" style="display: inline-block; margin-right: 0.3em;" class="ui-icon ui-icon-gear"></span>');
            $("table tbody td[source='0']", ui.panel).append('<span title="human" style="display: inline-block; margin-right: 0.3em;" class="ui-icon ui-icon-person"></span>');
        //$("table tbody tr:first td:last", ui.panel).append('<span title="remove this translation" id="' + t_jp.prefix + 'revert" style="float: left; margin-right: 0.3em;" class="ui-icon ui-icon-scissors"/>');
        //$("#" + t_jp.prefix + "revert").click(function () {
        //alert ('hi');
        //});
        })
        .bind('tabsselect', function (event, ui) {
            // Change buttons
            if ($(ui.tab).text() === 'Translate') {
                $("#" + t_jp.prefix + "d-tabs").dialog('option', 'buttons', tButtons);
            } else {
                $("#" + t_jp.prefix + "d-tabs").dialog('option', 'buttons', hButtons);
            }
        })
        .bind('dialogbeforeclose', function (event, ui) {
            if ($("#" + t_jp.prefix + "translation").data("edit").changed) {
                confirm_close();
                return false;
            }
            return true;
        });
        // fix for templates messing with li
        $("#" + t_jp.prefix + "d-tabs li").css("list-style-type", "none").css("list-style-position", "outside");
        $("#" + t_jp.prefix + "d-tabs-1").css("padding", "1px").append(
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
        /*$("#smart").click(function () {
        grabnext(segment_id);
    });*/
        $("#" + t_jp.prefix + "d-tabs-1 label").css("display", "block");
        $("#" + t_jp.prefix + "d-tabs-1 textarea.text").css({
            'margin-bottom': '12px',
            'width' : '95%',
            'padding' : '.4em'
        });
        $("#" + t_jp.prefix + "original").val($("#" + t_jp.prefix + segment_id).attr('data-orig'));
        $("#" + t_jp.prefix + "translation").val($("#" + t_jp.prefix + segment_id).html());
        if ($("#" + t_jp.prefix + segment_id).attr('data-trans')) {
            $("#" + t_jp.prefix + "translation").val($("#" + t_jp.prefix + segment_id).attr('data-trans'));
        }
        $("#" + t_jp.prefix + "translation").data("edit", {
            changed: false
        });
        $("#" + t_jp.prefix + "translation").keyup(function (e) {
            if ($("#" + t_jp.prefix + segment_id).text() !== $(this).val()) {
                $(this).css("background", "yellow");
                $(this).data("edit", {
                    changed: true
                });
            } else {
                $(this).css("background", "");
                $(this).data("edit", {
                    changed: false
                });
            }
        });
        $("#" + t_jp.prefix + "d-tabs").dialog({
            //bgiframe: true,
            //modal: true,
            //width: 'auto',
            width: 500,
            buttons: tButtons
        });
    }


    // lets add the images
    $("." + t_jp.prefix).each(function (i) {
        var translated_id = $(this).attr('id').substr($(this).attr('id').lastIndexOf('_') + 1), img;
        $(this).after('<span id="' + t_jp.prefix + 'img_' + translated_id + '" class="tr-icon" title="' + $(this).attr('data-orig') + '"></span>');
        img = $('#' + t_jp.prefix + 'img_' + translated_id);
        img.click(function () {
            //  if we detect that $.ui is missing (TODO - check tabs - etal) we load it first, the added or solves a jquery tools conflict
            if (typeof $.fn.tabs !== 'function' || typeof $.fn.dialog !== 'function') {
                $.ajaxSetup({
                    cache: true
                });
                $.getScript(t_jp.plugin_url + '/js/lazy.js', function () {
                    $.xLazyLoader({
                        js: 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/jquery-ui.min.js',
                        css: 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/ui-lightness/jquery-ui.css',
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
        if ($(this).attr('data-source') === '0') {
            img.addClass('tr-icon-green');
        }
        else if ($(this).attr('data-source')) {
            img.addClass('tr-icon-yellow');
        }
        // if the image is sourced from a hidden element - kindly "show" this
        if ($(this).attr('data-hidden') === 'y') {
            img.css({
                'opacity': '0.6'
            });
        }
    });
}(jQuery)); // end of closure
