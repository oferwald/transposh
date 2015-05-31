/*  Copyright © 2009-2015 Transposh Team (website : http://transposh.org)
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
    // list of languages
    var l = {
        'en': 'English - English',
        'af': 'Afrikaans - Afrikaans',
        'sq': 'Albanian - Shqip',
        'ar': 'Arabic - العربية',
        'hy': 'Armenian - Հայերեն',
        'az': 'Azerbaijani - azərbaycan dili',
        'eu': 'Basque - Euskara',
        'be': 'Belarusian - Беларуская',
        'bn': 'Bengali - বাংলা',
        'bg': 'Bulgarian - Български',
        'ca': 'Catalan - Català',
        'zh': 'Chinese (Simplified) - 中文(简体)',
        'zh-tw': 'Chinese (Traditional) - 中文(漢字)',
        'hr': 'Croatian - Hrvatski',
        'cs': 'Czech - Čeština',
        'da': 'Danish - Dansk',
        'nl': 'Dutch - Nederlands',
        'eo': 'Esperanto - Esperanto',
        'et': 'Estonian - Eesti keel',
        'fi': 'Finnish - Suomi',
        'fr': 'French - Français',
        'gl': 'Galician - Galego',
        'ka': 'Georgian - ქართული',
        'de': 'German - Deutsch',
        'el': 'Greek - Ελληνικά',
        'gu': 'Gujarati - ગુજરાતી',
        'ht': 'Haitian - Kreyòl ayisyen',
        'mw': 'Hmong Daw - Hmoob Daw',
        'he': 'Hebrew - עברית',
        'hi': 'Hindi - हिन्दी; हिंदी',
        'hu': 'Hungarian - Magyar',
        'is': 'Icelandic - Íslenska',
        'id': 'Indonesian - Bahasa Indonesia',
        'ga': 'Irish - Gaeilge',
        'it': 'Italian - Italiano',
        'ja': 'Japanese - 日本語',
        'kn': 'Kannada - ಕನ್ನಡ',
        'ko': 'Korean - 우리말',
        'lo': 'Lao - ພາສາລາວ',
        'la': 'Latin - Latīna',
        'lv': 'Latvian - Latviešu valoda',
        'lt': 'Lithuanian - Lietuvių kalba',
        'mk': 'Macedonian - македонски јазик',
        'ms': 'Malay - Bahasa Melayu',
        'mt': 'Maltese - Malti',
        'no': 'Norwegian - Norsk',
        'fa': 'Persian - پارسی',
        'pl': 'Polish - Polski',
        'pt': 'Portuguese - Português',
        'ro': 'Romanian - Română',
        'ru': 'Russian - Русский',
        'sr': 'Serbian - Cрпски језик',
        'sk': 'Slovak - Slovenčina',
        'sl': 'Slovene - Slovenščina',
        'es': 'Spanish - Español',
        'sw': 'Swahili - Kiswahili',
        'sv': 'Swedish - Svenska',
        'tl': 'Tagalog - Tagalog',
        'ta': 'Tamil - தமிழ்',
        'te': 'Telugu - తెలుగు',
        'th': 'Thai - ภาษาไทย',
        'tr': 'Turkish - Türkçe',
        'uk': 'Ukrainian - Українська',
        'ur': 'Urdu - اردو',
        'vi': 'Vietnamese - Tiếng Việt',
        'cy': 'Welsh - Cymraeg',
        'yi': 'Yiddish - ייִדיש'
    },
    prefix = t_jp.prefix,
    idprefix = "#" + prefix,
    localeloaded = false,
    previcon = 'prev',
    nexticon = 'next',
    right = 'right',
    left = 'left',
    rkey = 39,
    lkey = 37;

    // fix rtl stuff
    if ($("html").attr("dir") === 'rtl') {
        right = 'left';
        left = 'right';
        lkey = 39;
        rkey = 37;
        previcon = 'next';
        nexticon = 'prev';
    }

    // translation function
    function __(str){
        var s;
        if(typeof(t_jp.l) === 'object' && (s=t_jp.l[str]) ) return s;
        return str;
    }


    function fix_page_human(token, translation, source) {
        //reset to the original content - the unescaped version if translation is empty
        // TODO!
        token = token.replace(/([;&,\.\+\*\~':"\!\^#$%@\[\]\(\)=>\|])/g, '\\$1');
        if ($.trim(translation).length === 0) {
            translation = $("[data-orig='" + token + "']").attr('data-orig');
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
        $("*[data-orig='" + token + "'][data-hidden!='y']")
        .html(translation)
        .each(fix_image);

        // FIX hidden elements too (need to update father's title)
        $("*[data-orig='" + token + "'][data-hidden='y']")
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
        $.ajax({
            type: "POST",
            url: t_jp.ajaxurl, // FIX!
            data: {
                action: 'tp_translation',
                ln0: t_jp.lang,
                sr0: 0, // implicit human
                items: 1,
                tk0: token,
                tr0: translation
            },
            error: function (req) {
                fix_page_human(token, '', 1); // Will turn things back, almost
                alert("Problem saving translation, contact support.\n\nServer's message: " + req.statusText);
            }
        });
    }

    // fetch translation from google translate...
    function getgt()
    {
        if (!t_jp.google_key) {
            $.ajax({
                type: "GET",
                url: t_jp.ajaxurl,
                dataType: "json",
                data: {
                    action: 'tp_gsp',
                    tl: t_jp.lang,
                    sl: $(idprefix + "original").data('srclang'),
                    q: $(idprefix + "original").val()
                },
                success: function (result) {
                    console.log(result);
                    $(idprefix + "translation").val($("<div>" + $.trim(result.result) + "</div>").text())
                    .keyup();
                }
            });
        } else {
            t_jp.dgt($(idprefix + "original").val(), function (result) {
                $(idprefix + "translation").val($("<div>" + $.trim(result.data.translations[0].translatedText) + "</div>").text())
                .keyup();
            }, t_jp.lang);
        }
    }

    // fetch translation from bing translate...
    function getbt()
    {
        t_jp.dmt([$(idprefix + "original").val()], function (result) {
            $(idprefix + "translation").val($("<div>" + $.trim(result[0].TranslatedText) + "</div>").text())
            .keyup();
        }, t_jp.binglang);
    }

    // fetch translation from apertium translate...
    function getat()
    {
        t_jp.dat($(idprefix + "original").val(), function (result) {
            $(idprefix + "translation").val($("<div>" + $.trim(result.responseData.translatedText) + "</div>").text())
            .keyup();
        }, t_jp.lang);
    }

    // switch position of text and close button in ui-dialog for rtl
    function fix_dialog_header_rtl(dialog) {
        var uit, uitc, valr, vall;
        uit = $(dialog).dialog("widget").find('.ui-dialog-title');
        valr = uit.css('margin-right');
        vall = uit.css('margin-left');
        uit.css({
            'float': left,
            'margin-right': vall,
            'margin-left': valr
        });
        uitc = $(dialog).dialog("widget").find('.ui-dialog-titlebar-close');
        valr = uitc.css('right');
        vall = uitc.css('left');
        uitc.css({
            right: vall,
            left: valr
        });
    }

    function confirm_close() {
        var dialog = idprefix + "confirmdialog";
        $(dialog).remove();
        $('<div id="' + prefix + 'confirmdialog" title="'+__('Close without saving?')+
            '"><span class="ui-icon ui-icon-alert" style="float:'+left+'; margin-bottom:20px; margin-'+right+':7px"></span>' +
            '<span style="clear:both">' +
            __('You have made a change to the translation. Are you sure you want to discard it?') +
            '<span><span id="' + prefix + 'dcbar" style="display:block">' +
            '<button id="' + prefix + 'cancel">'+__('Cancel')+'</button>' +
            '<button id="' + prefix + 'discard">'+__('Discard')+'</button>' +
            '</span>' +
            + '</div>').appendTo("body").dialog({
            resizable: false,
            modal: true,
            minHeight: 50,
            overlay: {
                backgroundColor: '#000',
                opacity: 0.5
            }
        });

        $(idprefix + 'cancel').button({
            icons: {
                primary: "ui-icon-closethick"
            },
            text: false
        }).click( function () {
            $(dialog).dialog('close');
        });

        $(idprefix + 'discard').button({
            icons: {
                primary: "ui-icon-check"
            },
            text: false
        }).click( function () {
            $(idprefix + "translation").data('changed', false);
            $(dialog).dialog('close');
            $(idprefix + "dialog").dialog('close');
        });

        // toolbars should float...
        $(idprefix + 'dcbar').css({
            'float' : right
        }).buttonset();
        // rtl fix for buttonsets
        if ($("html").attr("dir") === 'rtl') {
            fix_dialog_header_rtl(dialog);
            var uicorner = 'ui-corner-';
            $(idprefix + 'dcbar button:first').addClass(uicorner + left).removeClass(uicorner + right);
            $(idprefix + 'dcbar button:last').addClass(uicorner + right).removeClass(uicorner + left);
        }
    }

    function history_dialog(segment_id){
        var dialog = idprefix + "historydialog";

        $(dialog).remove();

        $('<div id="' + prefix + 'historydialog" title="' + __('History') + '">'+__('Loading...')+'</div>').appendTo("body");
        $(dialog).css('padding', 0).dialog({
            width: '450px',
            // dialogClass: 'ui-widget-shadow',
            show: 'slide'//,
        });
        if ($("html").attr("dir") === 'rtl') {
            fix_dialog_header_rtl(dialog);
        }        
        $.ajax({
            url: t_jp.ajaxurl, 
            type: "POST",
            data: {
                action: 'tp_history',
                token: $(idprefix + segment_id).attr('data-orig'),
                lang: t_jp.lang
            },
            dataType: "json",
            cache: false,
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
                            icontitle = __('manual translation');
                    }
                    if (row.user_login === null) {
                        row.user_login = row.translated_by;
                    }
                    iconline = '<span class="ui-button ui-widget ui-button-icon-only" style="width: 18px; border: 0px; margin-' + right + ': 3px"><span title="' + icontitle + '" style="cursor: default" class="ui-button-icon-primary ui-icon ' + icon + '"></span><span class="ui-button-text" style="display: inline-block; "></span></span>'
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
                        url: t_jp.ajaxurl,
                        type: "POST",
                        data: {
                            action: 'tp_history',
                            token: $(idprefix + segment_id).attr('data-orig'),
                            timestamp: $(this).parents('tr').children(":last").text(),
                            lang: t_jp.lang
                        },
                        dataType: "json",
                        cache: false,
                        success: function(data) {
                            if (data === false) {
                                $(row).children().addClass('ui-state-error');
                            } else {
                                $(row).empty();
                                fix_page_human($(idprefix + segment_id).attr('data-orig'),data.translated, data.source);
                            }
                        }
                    });
                });
                $("." + prefix + "delete .ui-button-text").css('display','inline-block');
            }
        });

    }
    
    /*    function getBetweenSegments(segment_id) 
    {
        var firstSegment = prefix + segment_id;
        var next_segment_id = prefix + (Number(segment_id) + 1);
        //alert (firstSegment + " - " + next_segment_id);
        //var secondSegment = $(idprefix + next_segment_id);
        var seg = ""; // Collection of Elements
        var seenMyself = false;
        $(idprefix + segment_id).parent().contents().each(function(){
            var siblingID  = $(this).attr("id"); // Get Sibling ID
            //alert ("this = " + this + " " + siblingID);
            // End on next segment
            if (!seenMyself)
            {
                if (siblingID == firstSegment)
                    seenMyself = true;
                return true;
            }
            else if (siblingID == next_segment_id) {
                return false;
            }
            
            if ( this.nodeType == 3 || $.nodeName(this, "br") ) 
            {
                seg += this.textContent;
                //alert("Sibling - " + this.textContent);
            }
            // Skip SPANs (probably the edit buttons
            if ($(this).is("span"))
            {
                return true;
            }
            return true;
            //collection.push($(this)); // Add Sibling to Collection
        });
        return seg; // Return Collection
    }

    function add_segment(segment_id)
    {
        var seg = getBetweenSegments(Number(segment_id)-1);
        //alert ("seg = " + seg);
        //return;
        // the field values
        $(idprefix + "original").val($(idprefix + "original").val() + seg + $(idprefix + segment_id).attr('data-orig'));
        $(idprefix + "translation").val($(idprefix + "translation").val() + seg + $(idprefix + segment_id).html());

        if ($(idprefix + segment_id).attr('data-trans')) {
            $(idprefix + "translation").val($(idprefix + segment_id).attr('data-trans'));
        }
        // init data vars
        $(idprefix + "translation").data("origval", $(idprefix + "translation").val());

        // need to set approve button to enabled by default
        $(idprefix + 'approve').button("enable");

        // make sure the next and prev buttons are in order
        $(idprefix + 'prev').button("enable");
        $(idprefix + 'next').button("enable");
        if (!$(idprefix + (Number(segment_id) - 1)).length) {
            $(idprefix + 'prev').button("disable");
        }
        if (!$(idprefix + (Number(segment_id) + 1)).length) {
            $(idprefix + 'next').button("disable");
        }

        // set the original language part
        var segmentlang = $(idprefix + segment_id).attr('data-srclang');
        if (segmentlang === undefined ) {
            segmentlang = t_jp.olang;
        }
        $(idprefix + "orglang").text(l[segmentlang]);
        // old history is history
        $(idprefix+'historydialog').remove();
        // This line makes sure that the approval button is correct on creation
        // at the end of the chain, a keyup event will make sure everything is ok
        $(idprefix + "translation").keyup();
    }
  */  
    // load data to translate dialog
    function set_translate_dialog_values(segment_id) {
        // the field values
        $(idprefix + "original").val($(idprefix + segment_id).attr('data-orig'));
        $(idprefix + "translation").val($(idprefix + segment_id).html());

        if ($(idprefix + segment_id).attr('data-trans')) {
            $(idprefix + "translation").val($(idprefix + segment_id).attr('data-trans'));
        }
        // init data vars
        $(idprefix + "translation").data("origval", $(idprefix + "translation").val());

        // need to set approve button to enabled by default
        $(idprefix + 'approve').button("enable");

        // make sure the next and prev buttons are in order
        $(idprefix + 'prev').button("enable");
        $(idprefix + 'next').button("enable");
        if (!$(idprefix + (Number(segment_id) - 1)).length) {
            $(idprefix + 'prev').button("disable");
        }
        if (!$(idprefix + (Number(segment_id) + 1)).length) {
            $(idprefix + 'next').button("disable");
        }

        // oht should not be highlighted
        $(idprefix + 'oht').removeClass('ui-state-highlight');

        // set the original language part
        var segmentlang = $(idprefix + segment_id).attr('data-srclang');
        if (segmentlang === undefined ) {
            segmentlang = t_jp.olang;
        }
        $(idprefix + "orglang").text(l[segmentlang]);
        // old history is history
        $(idprefix+'historydialog').remove();
        // This line makes sure that the approval button is correct on creation
        // at the end of the chain, a keyup event will make sure everything is ok
        $(idprefix + "translation").keyup();
     

    }
    
    //Open translation dialog
    function translate_dialog(segment_id) {
        //only add button is bing support is defined for the language
        var bingbutton = '', googlebutton = '', apertiumbutton = '', ohtbutton = '', dialog = idprefix + "dialog";

        // Only add buttons if translation engine is supported in said language
        if (t_jp.msn) {
            bingbutton = '<button class="' + prefix + 'suggest" id="' + prefix + 'bing">'+__('bing suggest')+'</button>';
        }
        if (t_jp.google) {
            googlebutton = '<button class="' + prefix + 'suggest" id="' + prefix + 'google">'+__('google suggest')+'</button>';
        }
        if (t_jp.apertium) {
            apertiumbutton = '<button class="' + prefix + 'suggest" id="' + prefix + 'apertium">'+__('apertium suggest')+'</button>';
        }
        if (t_jp.oht) {
            ohtbutton = '<button class="' + prefix + 'suggest" id="' + prefix + 'oht">'+__('One Hour Translate queue')+'</button>';
        }

        // this is our current way of cleaning up, might reconsider?
        $(dialog).remove();
        $('<div id="' + prefix + 'dialog" title="' + __('Edit Translation') + '"/>').appendTo("body");

        $(dialog).css("padding", "1px").append(
            '<div style="width: 100%">' +
            '<label for="original">' + __('Original text') +' (<a href="#" title="'+__('read alternate translations')+'" id="'+prefix+'orglang"></a>)'+ '</label>' +
            '<textarea cols="80" row="3" name="original" id="' + prefix + 'original" readonly="y"/>' +
            '<span id="' + prefix + 'utlbar">' +
            '<button id="' + prefix + 'prev">'+__('previous translation')+'</button>' +
            '<button id="' + prefix + 'zoom">'+__('find on page')+'</button>' +
            /*            '<button id="' + prefix + 'add">'+__('add next phrase')+'</button>' +*/
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
            ohtbutton +
            '<button id="' + prefix + 'approve">'+__('approve translation')+'</button>' +
            '</span>' +
            '</div>' 
            );

        // toolbars should float...
        $(idprefix + 'utlbar,' + idprefix + 'ltlbar').css({
            'float' : right
        }).buttonset();
        // css for textareas
        $(dialog + ' textarea').css({
            'width': '483px',
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
        $(idprefix + 'orglang').click(function () {
            if ($(idprefix + "langmenu").length) {
                $(idprefix + "langmenu").toggle();
            } else {
                // We will show languages that have a human translation on the server
                t_jp.tfl(function() {
                    $.xLazyLoader({
                        js: [t_jp.plugin_url + '/js/jquery.ui.menu.js'],
                        success: function () {
                            $.ajax({
                                url: t_jp.ajaxurl,
                                data: {
                                    action: 'tp_trans_alts',
                                    token: $(idprefix + segment_id).attr('data-orig')
                                },
                                dataType: "json",
                                cache: false,
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
                                        my: left + ' top',
                                        at: left +' bottom',
                                        of: $(idprefix + 'orglang')
                                    });
                                }
                            });
                        }
                    });
                });
            }
            return false;
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
        $(idprefix + 'prev').click(function () {
            // save data if changed
            if ($(idprefix + 'translation').data("changed")) {
                var translation = $(idprefix + 'translation').val(),
                token = $(idprefix + segment_id).attr('data-orig');
                ajax_translate_human(token, translation);
            }
            // dec counter, reload fields
            segment_id = Number(segment_id) - 1;
            set_translate_dialog_values(segment_id);
        });
        // next button click
        $(idprefix + 'next').click(function () {
            // save data if changed
            if ($(idprefix + 'translation').data("changed")) {
                var translation = $(idprefix + 'translation').val(),
                token = $(idprefix + segment_id).attr('data-orig');
                ajax_translate_human(token, translation);
            }
            // inc counterm reload fields
            segment_id = Number(segment_id) + 1;
            set_translate_dialog_values(segment_id);
        });
        /*        // add button click
        $(idprefix + 'add').click(function () {
            // save data if changed
            if ($(idprefix + 'translation').data("changed")) {
                var translation = $(idprefix + 'translation').val(),
                token = $(idprefix + segment_id).attr('data-orig');
                ajax_translate_human(token, translation);
            }
            // inc counterm reload fields
            segment_id = Number(segment_id) + 1;
            add_segment(segment_id);
        });*/

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
            // animate the scroll (3 blinks)
            $(idprefix + segment_id).animate({
                opacity: 0.1
            }, "slow", function () {
                //make it absolute again
                $(dialog).dialog('widget').css({
                    'top': $(dialog).dialog("widget").offset().top,
                    'position': 'absolute'
                });
            }).animate({
                opacity: 1
            }, "slow").animate({
                opacity: 0.1
            }, "slow").animate({
                opacity: 1
            }, "slow").animate({
                opacity: 0.1
            }, "slow").animate({
                opacity: 1
            }, "slow");
        });

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
            t_jp.tfl(function() {
                $.xLazyLoader({
                    js: [t_jp.plugin_url + '/js/keyboard.js'],
                    css: [t_jp.plugin_url + '/css/keyboard.css'],
                    success: function () {
                        VKI_attach($(idprefix + "translation").get(0));
                        VKI_show($(idprefix + "translation").get(0));
                    }
                });
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

        $(idprefix + 'oht').button({
            icons: {
                primary: "tr-icon-oht"
            },
            text: false
        }).click(function () {
            var b = $(this);
            $.ajax({
                url: t_jp.ajaxurl, 
                data: {
                    action: 'tp_oht',
                    q: $(idprefix + "original").val(),
                    token: $(idprefix + segment_id).attr('data-orig'),
                    orglang: $(idprefix + segment_id).attr('data-srclang'),
                    lang: t_jp.lang
                },
                dataType: "json",
                cache: false,
                success: function (result) {
                    if (result) {
                        b.addClass('ui-state-highlight');
                    } else {
                        b.removeClass('ui-state-highlight');
                    }
                }
            });
        });

        // approval button
        $(idprefix + 'approve').button({
            icons: {
                primary: "ui-icon-check"
            },
            text: false
        }).click(function () {
            var translation = $(idprefix + 'translation').val(),
            token = $(idprefix + segment_id).attr('data-orig');
            // we allow approval on computer generated translations too
            if ($(idprefix + 'translation').data("changed") || $(idprefix + segment_id).attr('data-source') !== "0") {
                ajax_translate_human(token, translation);
            // at the end of the chain, a keyup event will make sure everything is ok
            }
        });

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
        // load field values
        set_translate_dialog_values(segment_id);

        // time to create the dialog
        $(dialog).dialog({
            resizable: false,
            width: 500,
            zIndex: 99999//,
        });

        // rtl fix for buttonsets, dialog
        if ($("html").attr("dir") === 'rtl') {
            fix_dialog_header_rtl(dialog);
        }

        // we don't need no focus, we don't need element control
        $(idprefix + 'orglang').blur();

        // remove virtual keyboard and history on close
        $(dialog).bind("dialogclose", function (event, ui) {
            if (typeof VKI_close === 'function') {
                VKI_close($(idprefix + "translation").get(0));
            }
            $(idprefix+'historydialog').remove();
        });

        // we allow to goto next/prev with ctrl-key
        $(dialog).keydown(function(event) {
            if (event.ctrlKey) {
                switch (event.keyCode) {
                    case lkey:
                        $(idprefix + 'prev').click();
                        break;
                    case rkey:
                        $(idprefix + 'next').click();
                        break;
                }
            }
        })
        
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
       	if (typeof $(this).attr('id') === 'undefined') return; // who let the dogs out?? (who killed my id)
        var translated_id = $(this).attr('id').substr($(this).attr('id').lastIndexOf('_') + 1), img;
        $(this).after('<span id="' + prefix + 'img_' + translated_id + '" class="tr-icon" title="' + $(this).attr('data-orig') + '"></span>');
        img = $(idprefix + 'img_' + translated_id);
        // internal function used to load locale in two needed cases (where we load jQueryui and not...)
        var loadlocaleandrundialog = function() {
            if (t_jp.locale && !localeloaded) {
                $.getScript(t_jp.plugin_url + '/js/l/'+t_jp.lang+'.js', function () {
                    localeloaded = true;
                    translate_dialog(translated_id);
                });
            } else {
                translate_dialog(translated_id);
            }
        }
        img.click(function () {
            //  if we detect that $.ui is missing (TODO - check tabs - etal) we load it first, the added or solves a jquery tools conflict !!!!!!!!!!!
            t_jp.tfju(function() {
                loadlocaleandrundialog();
            })
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