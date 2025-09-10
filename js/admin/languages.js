/*  Copyright © 2009-2025 Transposh Team (website : https://transposh.org)
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

(function ($) { // closure
    $(function () {
        // Existing sortable functionality
        $("#sortable").sortable({
            placeholder: "highlight",
            update: function (event, ui) {
                ui.item.unbind("click");
                ui.item.one("click", function (event) {
                    event.stopImmediatePropagation();
                    $(this).click(clickfunction);
                });
            }
        }).disableSelection();

        // Toggles display of english/original names
        $("#changename").click(function () {
            $(".langname").toggleClass("hidden");
            return false;
        });

        // Enable all languages
        $("#selectall").click(function () {
            $("#sortable .languages").addClass("lng_active");
            $("#sortable .lng_active").each(function () {
                $("input", this).val($(this).attr("id") + ",v");
            });
            return false;
        });

        // Two flows on double click, if anonymous -> active, inactive otherwise active, translatable, inactive
        clickfunction = function () {
            if ($(this).attr("id") === $("#default_list li").attr("id"))
                return;
            $(this).toggleClass("lng_active");
            // set new value
            $("input", this).val($(this).attr("id") + ($(this).hasClass("lng_active") ? ",v" : ","));
        };
        $(".languages").dblclick(clickfunction).click(clickfunction);

        // The default language droppable
        $("#default_lang").droppable({
            accept: ".languages",
            activeClass: "highlight_default",
            drop: function (ev, ui) {
                $("#default_list").empty();
                $(ui.draggable.clone().removeAttr("style").removeClass("lng_active")).appendTo("#default_list").show("slow");
                $("#default_list .logoicon").remove();
                $("#sortable").find("#" + ui.draggable.attr("id")).addClass("lng_active");
            }
        });

        // Sorting by iso
        $("#sortiso").click(function () {
            $("#sortable li").sort(function (a, b) {
                var id = $("#default_list li").attr("id")
                if ($(a).attr("id") === id)
                    return -1;
                if ($(b).attr("id") === id)
                    return 1;
                return $(a).attr("id") > $(b).attr("id") ? 1 : -1;
            }).remove().appendTo("#sortable").dblclick(clickfunction).click(clickfunction);
            return false;
        });

        // Sorting by name
        $("#sortname").click(function () {
            $("#sortable li").sort(function (a, b) {
                langa = $(".langname", a).filter(function () {
                    return !$(this).hasClass("hidden");
                }).text();
                langb = $(".langname", b).filter(function () {
                    return !$(this).hasClass("hidden");
                }).text();
                langdef = $(".langname", "#default_list li").filter(function () {
                    return !$(this).hasClass("hidden");
                }).text();
                if (langa === langdef)
                    return -1;
                if (langb === langdef)
                    return 1;
                return langa > langb ? 1 : -1;
            }).remove().appendTo("#sortable").dblclick(clickfunction).click(clickfunction);
            return false;
        });

        // Open dialog on flag click
        $('.lang-flag').click(function (e) {
            e.preventDefault();
            e.stopPropagation();
            var langcode = $(this).data('langcode');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tp_get_lang_details',
                    nonce: $('#transposh_nonce').val(),
                    langcode: langcode
                },
                success: function (response) {
                    if (response.success) {
                        $('#langcode').val(langcode).prop('disabled', true);
                        $('#lang-name').val(response.data.lang_name);
                        $('#lang-orig-name').val(response.data.lang_orig_name);
                        $('#lang-flag').val(response.data.flag);
                        $('#lang-locale').val(response.data.locale); // Set locale
                        $('#lang-rtl').prop('checked', response.data.rtl === 1); // Set RTL checkbox
                        $.each(response.data.engines, function (engine, data) {
                            $('#engine-' + engine).prop('checked', data.enabled);
                            $('#engine-code-' + engine).val(data.code === langcode || data.code === 'y' ? '' : data.code);
                        });
                        $('#lang-dialog').dialog('option', 'title', 'Edit Language Details').dialog('open');
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function () {
                    alert('Error loading language details');
                }
            });
        });

        // Initialize dialog
        $('#lang-dialog').dialog({
            autoOpen: false,
            modal: true,
            closeOnEscape: false, // Prevent closing with Escape key
            dialogClass: 'no-close', // Add class to style close button
            width: 600,
            buttons: {
                'Save': function () {
                    var $dialog = $(this);
                    var langcode = $('#langcode').val();
                    var engines = {};
                    $('input[id^="engine-"]').each(function () {
                        var engine = $(this).attr('id').replace('engine-', '');
                        engines[engine] = {
                            enabled: $(this).is(':checked') ? 1 : 0,
                            code: $('#engine-code-' + engine).val()
                        };
                    });

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'tp_save_lang_details',
                            nonce: $('#transposh_nonce').val(),
                            langcode: langcode,
                            lang_name: $('#lang-name').val(),
                            lang_orig_name: $('#lang-orig-name').val(),
                            lang_flag: $('#lang-flag').val(),
                            lang_locale: $('#lang-locale').val(), // Send locale
                            lang_rtl: $('#lang-rtl').is(':checked') ? 1 : 0, // Send RTL
                            engines: engines
                        },
                        success: function (response) {
                            if (response.success) {
                                var $li = $('#sortable #' + langcode);
                                var $flagLink = $li.find('.lang-flag');
                                var $langName = $li.find('.langname').eq(0);
                                var $langNameEn = $li.find('.langname').eq(1);
                                $langName.text(response.data.lang_orig_name);
                                $langNameEn.text(response.data.lang_name);
                                $flagLink.find('img').attr('src', transposh_vars.plugin_url + '/img/flags/' + response.data.flag + '.png');
                                alert('Language saved successfully');
                                $dialog.dialog('close');
                            } else {
                                alert('Error: ' + response.data);
                            }
                        },
                        error: function () {
                            alert('Error saving language details');
                        }
                    });
                },
                'Cancel': function () {
                    $(this).dialog('close');
                }
            }
        });
    });
}(jQuery));