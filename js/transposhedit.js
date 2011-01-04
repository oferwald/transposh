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

/*global Date, Math, alert, escape, clearTimeout, document, jQuery, setTimeout, t_jp, t_jl, window, VKI_attach, VKI_show, VKI_close */
(function ($) { // closure

    function __(str){
        var s;
        if(typeof(t_jl) === 'object' && (s=t_jl[str]) ) return s;
        return str;
    }

    var l = {
        en: 'English',
        af: 'Afrikaans',
        sq: 'Shqip',
        ar: 'العربية',
        hy: 'Հայերեն',
        az: 'azərbaycan dili',
        eu: 'Euskara',
        be: 'Беларуская',
        bg: 'Български',
        ca: 'Català',
        zh: '中文 - 简体',
        'zh-tw': '中文 - 漢字',
        hr: 'Hrvatski',
        cs: 'Čeština',
        da: 'Dansk',
        nl: 'Nederlands',
        eo: 'Esperanto',
        et: 'Eesti keel',
        fi: 'Suomi',
        fr: 'Français',
        gl: 'Galego',
        ka: 'ქართული',
        de: 'Deutsch',
        el: 'Ελληνικά',
        ht: 'Kreyòl ayisyen',
        he: 'עברית',
        hi: 'हिन्दी; हिंदी',
        hu: 'Magyar',
        is: 'Íslenska',
        id: 'Bahasa Indonesia',
        ga: 'Gaeilge',
        it: 'Italiano',
        ja: '日本語',
        ko: '우리말',
        la: 'latīna',
        lv: 'Latviešu valoda',
        lt: 'Lietuvių kalba',
        mk: 'македонски јазик',
        ms: 'Bahasa Melayu',
        mt: 'Malti',
        no: 'Norsk',
        fa: 'فارسی',
        pl: 'Polski',
        pt: 'Português',
        ro: 'Română',
        ru: 'Русский',
        sr: 'Cрпски језик',
        sk: 'Slovenčina',
        sl: 'Slovenščina', //slovenian
        es: 'Español',
        sw: 'Kiswahili',
        sv: 'svenska',
        tl: 'Tagalog', // fhilipino
        th: 'ภาษาไทย',
        tr: 'Türkçe',
        uk: 'Українська',
        ur: 'اردو',
        vi: 'Tiếng Việt',
        cy: 'Cymraeg',
        yi: 'ייִדיש'
    }

    var prefix = t_jp.prefix,
    idprefix = "#" + prefix;

    function fix_page_human(token, translation, source) {
        //reset to the original content - the unescaped version if translation is empty
        // TODO!
        if ($.trim(translation).length === 0) {
            translation = $("[data-token='" + token + "']").attr('data-orig');
        }

        var fix_image = function () { // handle the image changes
            var img_segment_id = $(this).attr('id').substr($(this).attr('id').lastIndexOf('_') + 1),
            img = $(idprefix + "img_" + img_segment_id);
            $(idprefix + img_segment_id).attr('data-source', source); // source is 0 human
            img.removeClass('tr-icon-yellow').removeClass('tr-icon-green')
            if (source == 0) {
                img.addClass('tr-icon-green');
            } else if (source) {
                img.addClass('tr-icon-yellow');
            }
        };
        // rewrite text for all matching items at once
        $("*[data-token='" + token + "'][data-hidden!='y']")
        .html(translation)
        .each(fix_image);

        // FIX hidden elements too (need to update father's title)
        $("*[data-token='" + token + "'][data-hidden='y']")
        .attr('data-trans', translation)
        .each(fix_image);

        // fix interface by issue of keyup, and make sure the data holds proper original
        $(idprefix + "translation").data('origval', translation);
        $(idprefix + "translation").keyup();

    }

    // here we don't need the timer, this is human based
    function ajax_translate_human(token, translation) {
        // push translations
        // This is a change - as we fix the pages before we got actual confirmation (worked well for auto-translation)
        fix_page_human(token, translation, 0);
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
            '?v=1.0&q=' + encodeURIComponent(to_trans) + '&langpair=%7C' + t_jp.lang,
            dataType: "jsonp",
            success: callback
        });
    }

    function getgt()
    {
        google_trans($(idprefix + "original").val(), function (result) {
            if (result.responseStatus === 200) {
                $(idprefix + "translation").val($("<div>" + $.trim(result.responseData.translatedText) + "</div>").text())
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
        ms_trans($(idprefix + "original").val(), function (translation) {
            $(idprefix + "translation").val($("<div>" + $.trim(translation) + "</div>").text())
            .keyup();
        });
    }

    // perform apertium translate of single phrase via jsonp
    function apertium_trans(to_trans, callback) {
        $.ajax({
            url: 'http://api.apertium.org/json/translate?q=' + encodeURIComponent(to_trans) + '&langpair=en%7C' + t_jp.lang, // || &key=YOURAPIKEY&markUnknown=yes
            dataType: "jsonp",
            success: callback
        });
    }

    // fetch translation from apertium translate...
    function getat()
    {
        apertium_trans($(idprefix + "original").val(), function (result) {
            $(idprefix + "translation").val($("<div>" + $.trim(result.responseData.translatedText) + "</div>").text())
            .keyup();
        });
    }

    function confirm_close() {
        $('<div id="dial" title="'+__('Close without saving?')+'"><p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>'+__('You have made a change to the translation. Are you sure you want to discard it?') + '</p></div>').appendTo("body").dialog({
            resizable: false,
            modal: true,
            overlay: {
                backgroundColor: '#000',
                opacity: 0.5
            },
            buttons: {
                'Discard': function () {
                    $(idprefix + "translation").data('changed', false);
                    $(this).dialog('close');
                    $(idprefix + "dialog").dialog('close');
                },
                Cancel: function () {
                    $(this).dialog('close');
                }
            }
        });
    }

    function history_dialog(segment_id){
        var dialog = idprefix + "historydialog", left = "left", right = "right";
        /*if ($(dialog).length) {
            $(dialog).dialog('open');
            return;
        }*/
        if (jQuery("html").attr("dir") === 'rtl') {
            right = 'left';
            left = 'right';
        }

        $(dialog).remove();
        //$(idprefix+'historydialog').remove();

        $('<div id="' + prefix + 'historydialog" title="' + __('History') + '">'+__('Loading...')+'</div>').appendTo("body");
        $(dialog).css('padding', 0).dialog({
            width: '450px',
            // dialogClass: 'ui-widget-shadow',
            show: 'slide'//,
        //stack: true
        });
        $.ajax({
            url: t_jp.post_url,
            data: {
                tr_token_hist: $(idprefix + segment_id).attr('data-token'),
                lang: t_jp.lang
            },
            dataType: "json",
            success: function(data) {
                var icon, icontitle, iconline, delline;
                $(dialog).empty().append(
                    '<table width="100%">' +
                    '<col style="width: 80%;">' +
                    '<col>' +
                    '<col>' +
                    '<thead>'+
                    '<tr> ' +
                    '<th>'+__('Translated')+'</th><th>'+__('By')+'</th><th>'+__('At')+'</th>' +
                    '</tr>' +
                    '</thead>' +
                    '<tbody>' +
                    '</tbody>' +
                    '</table>');
                $.each(data, function(index, row) {
                    switch(row.source)
                    {
                        case '1':
                            icon = 'tr-icon-google';
                            icontitle = __('google');
                            break;
                        case '2':
                            icon = 'tr-icon-bing';
                            icontitle = __('bing');
                            break;
                        case '3':
                            icon = 'tr-icon-apertium';
                            icontitle = __('apertium');
                            break;
                        default:
                            icon = 'ui-icon-person';
                            icontitle = __('human translation');
                    }
                    if (row.user_login === null) {
                        row.user_login = row.translated_by;
                    }
                    iconline = '<span class="ui-button ui-widget ui-button-icon-only" style="width: 18px; border: 0px; margin-' + right + ': 3px"><span title="' + icontitle + '" style="cursor: default;" class="ui-button-icon-primary ui-icon ' + icon + '"></span><span class="ui-button-text" style="display: inline-block; "></span></span>'
                    if (row.can_delete) {
                        delline = '<span class="' + prefix + 'delete" title="' + __('delete') + '" style="width: 18px; margin-' + left + ': 3px">';
                    } else {
                        delline = '';
                    }
                    $(dialog + " tbody").append('<tr><td>' + row.translated + '</td><td id="' + prefix +'histby">' + iconline + row.user_login + '</td><td id="' + prefix +'histstamp">' +row.timestamp + delline + '</td></tr>');
                });
                $(idprefix + "histby," + idprefix + "histstamp").css('white-space','nowrap');
                $(dialog + " th").addClass('ui-widget-header').css('padding', '3px');
                $(dialog + " td").addClass('ui-widget-content').css('padding', '3px');
                $("." + prefix + "delete").button({
                    icons: {
                        primary: "ui-icon-circle-close"
                    },
                    text: false
                }).click(function() {
                    var row = $(this).parents('tr');
                    $.ajax({
                        url: t_jp.post_url,
                        data: {
                            tr_token_hist: $(idprefix + segment_id).attr('data-token'),
                            timestamp: $(this).parents('tr').children(":last").text(),
                            action: 'delete',
                            lang: t_jp.lang
                        },
                        dataType: "json",
                        success: function(data) {
                            if (data === false) {
                                $(row).children().addClass('ui-state-error');
                            } else {
                                console.log (data);
                                console.log($(idprefix + segment_id).attr('data-token'),data.translated, data.source);
                                $(row).empty();
                                fix_page_human($(idprefix + segment_id).attr('data-token'),data.translated, data.source);
                            //            var translation = $(idprefix + 'translation').val(),
                            //token = $(idprefix + segment_id).attr('data-token');
                            // we allow approval on computer generated translations too
                            //            if ($(idprefix + 'translation').data("changed") || $(idprefix + segment_id).attr('data-source') !== "0") {
                            //                ajax_translate_human(token, translation);

                            }
                        }
                    });
                });
                $("." + prefix + "delete .ui-button-text").css('display','inline-block');
            }
        });

    }
    //Open translation dialog
    function translate_dialog(segment_id) {
        //only add button is bing support is defined for the language
        var bingbutton = '', googlebutton = '', apertiumbutton = '', right = 'right', left = 'left', previcon = 'prev', nexticon = 'next', dialog = idprefix + "dialog";
        if (jQuery("html").attr("dir") === 'rtl') {
            right = 'left';
            left = 'right';
            previcon = 'next';
            nexticon = 'prev';
        }

        if (t_jp.msn) {
            bingbutton = '<button class="' + prefix + 'suggest" id="' + prefix + 'bing">'+__('bing suggest')+'</button>';
        }
        // Only add button if google supports said language
        if (t_jp.google) {
            googlebutton = '<button class="' + prefix + 'suggest" id="' + prefix + 'google">'+__('google suggest')+'</button>';
        }
        if (t_jp.apertium) {
            apertiumbutton = '<button class="' + prefix + 'suggest" id="' + prefix + 'apertium">'+__('apertium suggest')+'</button>';
        }

        // this is our current way of cleaning up, might reconsider?
        $(dialog).remove();
        $('<div id="' + prefix + 'dialog" title="' + __('Edit Translation') + '"/>').appendTo("body");

        var segmentlang = $(idprefix + segment_id).attr('data-srclang');
        if (segmentlang === undefined ) {
            segmentlang = t_jp.olang;
        }

        $(dialog).css("padding", "1px").append(
            '<div style="width: 100%">' +
            '<label for="original">' + __('Original text') +' (<span id="'+prefix+'orglang">'+l[segmentlang]+'</span>)'+ '</label>' +
            '<textarea cols="80" row="3" name="original" id="' + prefix + 'original" readonly="y"/>' +
            '<span id="' + prefix + 'utlbar">' +
            '<button id="' + prefix + 'flag">'+__('read alternate translations')+'</button>' +
            '<button id="' + prefix + 'prev">'+__('previous translation')+'</button>' +
            '<button id="' + prefix + 'zoom">'+__('find on page')+'</button>' +
            '<button id="' + prefix + 'next">'+__('next translation')+'</button>' +
            '</span>' +
            '<label for="translation">' + __('Translate to') + '</label>' +
            '<textarea cols="80" row="3" name="translation" lang="' + t_jp.lang + '"id="' + prefix + 'translation"/>' +
            '<span id="' + prefix + 'ltlbar">' +
            '<button id="' + prefix + 'history">'+__('view translation log')+'</button>' +
            '<button id="' + prefix + 'keyboard">'+__('virtual keyboard')+'</button>' +
            googlebutton +
            bingbutton +
            apertiumbutton +
            '<button id="' + prefix + 'approve">'+__('approve translation')+'</button>' +
            '</span>' +
            '</div>' 
            );

        // toolbars should float...
        $(idprefix + 'utlbar,' + idprefix + 'ltlbar').css({
            'float' : right
        }).buttonset();
        // rtl fix for buttonsets
        if (jQuery("html").attr("dir") === 'rtl') {
            jQuery('#tr_utlbar button:first').addClass('ui-corner-right').removeClass('ui-icon-left');
            jQuery('#tr_utlbar button:last').addClass('ui-corner-left').removeClass('ui-icon-right');
            jQuery('#tr_ltlbar button:first').addClass('ui-corner-right').removeClass('ui-icon-left');
            jQuery('#tr_ltlbar button:last').addClass('ui-corner-left').removeClass('ui-icon-right');
        }
        // css for textareas
        $(dialog + ' textarea').css({
            'width': '485px',
            'padding': '.4em',
            'margin': '2px 0 0 0',
            'resize': 'vertical' // this is for chrome and firefox
        }).addClass('text ui-widget-content ui-corner-all');

        // make sure buttons don't interfere with labels
        $(dialog + ' label').css({
            'display': 'block',
            'clear': 'both'
        });

        // buttonize
        $(idprefix + 'flag').button({
            icons: {
                primary: "ui-icon-flag"
            },
            text: false
        }).click(function () {
            if ($(idprefix + "langmenu").length) {
                $(idprefix + "langmenu").toggle();
            } else {
                // We will show languages that have a human translation on the server
                $.xLazyLoader({
                    js: [t_jp.plugin_url + '/js/jquery.ui.menu.js'],
                    success: function () {
                        $.ajax({
                            url: t_jp.post_url,
                            data: {
                                tr_token_alt: $(idprefix + segment_id).attr('data-token')
                            },
                            dataType: "json",
                            success: function(data) {
                                var itemlang
                                if (!(itemlang = $(idprefix + segment_id).attr('data-srclang'))) {
                                    itemlang = t_jp.olang;
                                }
                                var liflag = '<li data-translated="' + $(idprefix + segment_id).attr('data-orig') + '"><a href="#">' + l[itemlang] + '</a></li>'
                                $(data).each(function(index, item) {
                                    if (item.lang !== t_jp.lang) {
                                        liflag = liflag + '<li data-translated="' + item.translated + '"><a href="#">' + l[item.lang] + '</a></li>'
                                    }
                                });
                                $('<ul style="position: absolute; top: 0px" id="' + prefix + 'langmenu">' + liflag).appendTo(dialog);

                                $(idprefix + "langmenu").menu({
                                    select: function(event, ui) {
                                        $(this).hide();
                                        $(idprefix + "original").val(ui.item.attr('data-translated'));
                                        $(idprefix + "orglang").text(ui.item.text()).addClass('ui-state-highlight');
                                        if (l[itemlang] === ui.item.text()) {
                                            $(idprefix + "orglang").removeClass('ui-state-highlight');
                                        }
                                    },
                                    input: $(this)
                                }).show().css({
                                    top:0,
                                    left:0
                                }).position({
                                    my: right + ' top',
                                    at: left +' top',
                                    of: $(idprefix + 'flag')
                                });
                            }
                        });
                    }
                });
            }
        });

        $(idprefix + 'prev').button({
            icons: {
                primary: "ui-icon-seek-" + previcon
            },
            text: false
        });
        $(idprefix + 'zoom').button({
            icons: {
                primary: "ui-icon-search"
            },
            text: false
        });
        $(idprefix + 'next').button({
            icons: {
                primary: "ui-icon-seek-" + nexticon
            },
            text: false
        });
        // prev button click
        if ($(idprefix + (Number(segment_id) - 1)).length) {
            $(idprefix + 'prev').click(function () {
                translate_dialog(Number(segment_id) - 1);
            });
        } else {
            $(idprefix + 'prev').button("disable");
        }
        // zoom button click
        $(idprefix + 'zoom').click(function () {
            $('html, body').animate({
                scrollTop: $(idprefix + segment_id).offset().top
            }, 500);
            // fix dialog to screen while scrolling
            $(dialog).dialog('widget').css({
                'top': $(dialog).dialog("widget").offset().top  - window.scrollY,
                'position': 'fixed'
            });
            // animate the scroll
            jQuery(idprefix + segment_id).animate({
                opacity: 0.1
            }, "slow").animate({
                opacity: 1
            }, "slow", function () {
                //make it absolute again
                $(dialog).dialog('widget').css({
                    'top': $(dialog).dialog("widget").offset().top,
                    'position': 'absolute'
                });
            });
        });

        // next button click
        if ($(idprefix + (Number(segment_id) + 1)).length) {
            $(idprefix + 'next').click(function () {
                translate_dialog(Number(segment_id) + 1);
            });
        } else {
            $(idprefix + 'next').button("disable");
        }

        $(idprefix + 'history').button({
            icons: {
                primary: "ui-icon-clipboard"
            },
            text: false
        }).click(function () {
            history_dialog(segment_id)
        });

        $(idprefix + 'keyboard').button({
            icons: {
                primary: "ui-icon-calculator"
            },
            text: false
        }).click(function () {
            $.xLazyLoader({
                js: [t_jp.plugin_url + '/js/keyboard.js'],
                css: [t_jp.plugin_url + '/css/keyboard.css'],
                success: function () {
                    VKI_attach(jQuery(idprefix + "translation").get(0));
                    VKI_show(jQuery(idprefix + "translation").get(0));
                }
            });
        });

        $(idprefix + 'google').button({
            icons: {
                primary: "tr-icon-google"
            },
            text: false
        }).click(function () {
            getgt();
            $('.' + prefix + 'suggest').button("enable");
            $(this).button("disable");
        });
        $(idprefix + 'bing').button({
            icons: {
                primary: "tr-icon-bing"
            },
            text: false
        }).click(function () {
            getbt();
            $('.' + prefix + 'suggest').button("enable");
            $(this).button("disable");
        });
        $(idprefix + 'apertium').button({
            icons: {
                primary: "tr-icon-apertium"
            },
            text: false
        }).click(function () {
            getat();
            $('.' + prefix + 'suggest').button("enable");
            $(this).button("disable");
        });
        $(idprefix + 'approve').button({
            icons: {
                primary: "ui-icon-check"
            },
            text: false
        }).click(function () {
            var translation = $(idprefix + 'translation').val(),
            token = $(idprefix + segment_id).attr('data-token');
            // we allow approval on computer generated translations too
            if ($(idprefix + 'translation').data("changed") || $(idprefix + segment_id).attr('data-source') !== "0") {
                ajax_translate_human(token, translation);
            // at the end of the chain, a keyup event will make sure everything is ok
            }
        });

        // setting textarea values
        $(idprefix + "original").val($(idprefix + segment_id).attr('data-orig'));

        $(idprefix + "translation").val($(idprefix + segment_id).html());

        if ($(idprefix + segment_id).attr('data-trans')) {
            $(idprefix + "translation").val($(idprefix + segment_id).attr('data-trans'));
        }
        // init data vars
        $(idprefix + "translation")
        //.data("changed",false)
        .data("origval", $(idprefix + "translation").val());

        $(idprefix + "translation").keyup(function (e) {
            if ($(this).data("origval") !== $(this).val()) {
                $(this).addClass("ui-state-highlight");
                $(idprefix + 'approve').button("enable");
                $(this).data("changed", true);
            } else {
                $(this).removeClass("ui-state-highlight");
                if ($(idprefix + segment_id).attr('data-source') === "0") {
                    $(idprefix + 'approve').button("disable");
                }
                $(this).data("changed", false);
            }
        });

        // This line makes sure that the approval button is correct on creation
        $(idprefix + "translation").keyup();

        // time to create the dialog
        $(dialog).dialog({
            //modal: true,
            //width: 'auto',
            //autoopen: false,
            resizable: false,
            width: 500//,
        //   buttons: tButtons
        });

        // remove virtual keyboard and history on close
        $(dialog).bind("dialogclose", function (event, ui) {
            if (typeof VKI_close === 'function') {
                VKI_close(jQuery(idprefix + "translation").get(0));
            }
            $(idprefix+'historydialog').remove();
        });

        // show confirmation dialog before closing
        $(dialog).bind('dialogbeforeclose', function (event, ui) {
            if ($(idprefix + "translation").data("changed")) {
                confirm_close();
                return false;
            }
            return true;
        });

    }


    // lets add the images
    $("." + prefix).each(function (i) {
        var translated_id = $(this).attr('id').substr($(this).attr('id').lastIndexOf('_') + 1), img;
        $(this).after('<span id="' + prefix + 'img_' + translated_id + '" class="tr-icon" title="' + $(this).attr('data-orig') + '"></span>');
        img = $(idprefix + 'img_' + translated_id);
        img.click(function () {
            //  if we detect that $.ui is missing (TODO - check tabs - etal) we load it first, the added or solves a jquery tools conflict !!!!!!!!!!!
            if (typeof $.fn.tabs !== 'function' || typeof $.fn.dialog !== 'function') {
                $.ajaxSetup({
                    cache: true
                });
                $.getScript(t_jp.plugin_url + '/js/lazy.js', function () {
                    $.xLazyLoader({
                        js: ['http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.7/jquery-ui.min.js'],
                        css: ['http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.7/themes/ui-lightness/jquery-ui.css'],
                        success: function () {
                            // Load locale - todo - better...
                            if (t_jp.locale) {
                                $.xLazyLoader({
                                    js: [t_jp.plugin_url + '/js/l/'+t_jp.lang+'.js'],
                                    success: function () {
                                        translate_dialog(translated_id);
                                    }
                                });
                            } else {
                                translate_dialog(translated_id);
                            }
                        }
                    });
                });
            } else {
                if (t_jp.locale) {
                    $.xLazyLoader({
                        js: [t_jp.plugin_url + '/js/l/'+t_jp.lang+'.js'],
                        success: function () {
                            translate_dialog(translated_id);
                        }
                    });
                } else {
                    translate_dialog(translated_id);
                }
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
