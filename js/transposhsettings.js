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

//(function ($) { // closure
jQuery(function() {
    // clicking anonymous will make translatables active
    jQuery("#tr_anon").click(function() {
        if (jQuery("#tr_anon").attr("checked")) {
            jQuery(".translateable").addClass("active").removeClass("translateable");
            jQuery("#sortable .active").each(function () {
                jQuery("input",this).val(jQuery(this).attr("id")+",v,t");
            })
        }
        jQuery("#yellowcolor").toggleClass("hidden");
    });

    // makes the languages sortable, with placeholder, also prevent unneeded change after sort
    jQuery("#sortable").sortable({
        placeholder: "highlight",
        update: function(event, ui) {
            ui.item.unbind("click");
            ui.item.one("click", function (event) {
                event.stopImmediatePropagation();
                jQuery(this).click(clickfunction);
            });
        }
    });
    jQuery("#sortable").disableSelection();

    // toggles display of english/original names
    jQuery("#changename").click(function(){
        jQuery(".langname").toggleClass("hidden");
        return false;
    });

    // enable all languages
    jQuery("#selectall").click(function(){
        jQuery("#sortable .languages").addClass("active").removeClass("translateable");
        jQuery("#sortable .active").each(function () {
            jQuery("input",this).val(jQuery(this).attr("id")+",v,t");
        })
        return false;
    });

    // two flows on double click, if anonymous -> active, inactive otherwise active, translatable, inactive
    clickfunction = function () {
        if (jQuery(this).attr("id") == jQuery("#default_list li").attr("id")) return;
        if (jQuery("#tr_anon").attr("checked")) {
            jQuery(this).toggleClass("active");
        } else {
            if (jQuery(this).hasClass("active")) {
                jQuery(this).removeClass("active");
                jQuery(this).addClass("translateable")
            }
            else {
                if (jQuery(this).hasClass("translateable")) {
                    jQuery(this).removeClass("translateable");
                }
                else {
                    jQuery(this).addClass("active")
                }
            }
        }
        // set new value
        jQuery("input",this).val(jQuery(this).attr("id")+(jQuery(this).hasClass("active") ? ",v":",")+(jQuery(this).hasClass("translateable") ? ",t":","));
    }
    jQuery(".languages").dblclick(clickfunction).click(clickfunction);

    // the default language droppable
    jQuery("#default_lang").droppable({
        accept: ".languages",
        activeClass: "highlight_default",
        drop: function(ev, ui) {
            jQuery("#default_list").empty();
            jQuery(ui.draggable.clone().removeAttr("style").removeClass("active").removeClass("translateable")).appendTo("#default_list").show("slow");
            jQuery("#default_list .logoicon").remove();
            jQuery("#sortable").find("#"+ui.draggable.attr("id")).addClass("active");
        }
    });
    // sorting by iso
    jQuery("#sortiso").click(function() {
        jQuery("#sortable li").sort(function(a,b){
            //console.log(a);
            if (jQuery(a).attr("id") == jQuery("#default_list li").attr("id")) return -1;
            if (jQuery(b).attr("id") == jQuery("#default_list li").attr("id")) return 1;
            return jQuery(a).attr("id") > jQuery(b).attr("id") ? 1 : -1;
        }).remove().appendTo("#sortable").dblclick(clickfunction).click(clickfunction);
        return false;
    });
    // sorting by name
    jQuery("#sortname").click(function() {
        jQuery("#sortable li").sort(function(a,b){
            langa = jQuery(".langname",a).filter(function() {
                return !jQuery(this).hasClass("hidden")
            }).text();
            langb = jQuery(".langname",b).filter(function() {
                return !jQuery(this).hasClass("hidden")
            }).text();
            langdef = jQuery(".langname","#default_list li").filter(function() {
                return !jQuery(this).hasClass("hidden")
            }).text();
            if (langa == langdef) return -1;
            if (langb == langdef) return 1;
            return langa > langb ? 1 : -1;
        }).remove().appendTo("#sortable").dblclick(clickfunction).click(clickfunction);
        return false;
    });

    jQuery.ajaxSetup({
        cache: false
    });

    // backup button
    backupclick = function () {
        jQuery("#transposh-backup").unbind('click').click(function(){
            return false
        }).text("Backup In Progress");
        jQuery.post(ajaxurl, {
            action: 'tp_backup'
        },
        function(data) {
            var color = 'red';
            if (data[0] == '2') color = 'green';
            jQuery('#backup_result').html(data).css('color',color);
            jQuery("#transposh-backup").unbind('click').click(backupclick).text("Do Backup Now");
        });
        return false;
    };
    jQuery("#transposh-backup").click(backupclick);

    // cleanup button
    cleanautoclick = function (days,button) {
        if (!confirm("Are you sure you want to do this?")) return false;
        if (days == 0 && !confirm("Are you REALLY sure you want to do this?")) return false;
        //var button = jQuery(this);
        //console.log(button);
        var prevtext = button.text();
        button.unbind('click').click(function(){
            return false
        }).text("Cleanup in progress");
        jQuery.post(ajaxurl, {
            action: 'tp_cleanup',
            days: days
        },
        function(data) {
            button.unbind('click').click(function() {
                cleanautoclick(days,button);
                return false;
            }).text(prevtext);
        });
        return false;
    };
    jQuery("#transposh-clean-auto").click(function() {
        cleanautoclick(0,jQuery(this));
        return false;
    });

    jQuery("#transposh-clean-auto14").click(function() {
        cleanautoclick(14,jQuery(this));
        return false;
    });

    maintclick = function (button) {
        if (!confirm("Are you sure you want to do this?")) return false;
        var prevtext = button.text();
        button.unbind('click').click(function(){
            return false
        }).text("Maintenance in progress");
        jQuery.post(ajaxurl, {
            action: 'tp_maint'
        },
        function(data) {
            button.unbind('click').click(function() {
                maintclick(button);
                return false;
            }).text(prevtext);
        });
        return false;
    }
    
    jQuery("#transposh-maint").click(function() {
        maintclick(jQuery(this));
        return false;
    });

    // translate all button
    do_translate_all = function () {
        jQuery("#progress_bar_all").progressbar({
            value:0
        });
        stop_translate_var = false;
        // while there is a next
        // get next post to translate
        //var offset = "0";
        jQuery("#tr_loading").data("done",true);
        jQuery.ajaxSetup({
            cache: false
        });
        jQuery.ajax({
            url: ajaxurl,
            dataType: 'json',
            data: {
                action: "tp_translate_all"
            },
            cache: false,
            success: function (data) {
                dotimer = function(a) {
                    jQuery("#tr_allmsg").text('');
                    clearTimeout(timer2);
                    //console.log(a);
                    //console.log(jQuery("#tr_loading").data("done"));
                    if (jQuery("#tr_loading").data("done") || jQuery("#tr_loading").data("attempt")>4) {
                        jQuery("#progress_bar_all").progressbar('value' , (a+1)/data.length*100);
                        jQuery("#tr_loading").data("attempt",0);
                        translate_post(data[a]);
                        //console.log(jQuery("#tr_loading").data("done"));
                        //console.log("done translate" + a);
                        // we call the next translation here...
                        if (typeof data[a+1] !== 'undefined' && !stop_translate_var) {
                            //console.log("trigger translation of " +a);
                            timer2 = setTimeout(function() {
                                dotimer(a+1)
                            },5000);
                            jQuery("#tr_allmsg").text('Waiting 5 seconds...');
                        }
                    } else {
                        //console.log("waiting for translation to finish 60 seconds");
                        jQuery("#tr_loading").data("attempt",jQuery("#tr_loading").data("attempt")+1);
                        timer2 = setTimeout(function() {
                            dotimer(a)
                        },15000);
                        jQuery("#tr_allmsg").text('Translation incomplete - Waiting 15 seconds - attempt ' + jQuery("#tr_loading").data("attempt") + '/5');
                    }
                }
                timer2 = setTimeout(function() {
                    dotimer(0)
                },0);
            }
        });
        jQuery("#transposh-translate").text("Stop translate")
        jQuery("#transposh-translate").unbind('click').click(stop_translate);
        return false;
    }

    stop_translate = function() {
        clearTimeout(timer2);
        stop_translate_var = true;
        jQuery("#transposh-translate").text("Translate All Now")
        jQuery("#transposh-translate").unbind('click').click(do_translate_all);
        return false;
    }

    jQuery("#transposh-translate").click(do_translate_all);
    
    jQuery(".warning-close").click(function() {
        jQuery(this).parent().hide();
        jQuery.post(ajaxurl, {
            action: 'tp_close_warning',
            id: jQuery(this).parent().attr('id')
        });
    })

});
//}(jQuery)); // end of closure