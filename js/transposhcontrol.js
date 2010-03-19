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

    // backup button
    backupclick = function () {
        jQuery("#transposh-backup").click(function(){
            return false
            }).text("Backup In Progress");
        jQuery.get(t_jp.post_url + "?backup=" + Math.random(),function(data) {
            var color = 'red';
            if (data[0] == '2') color = 'green';
            jQuery('#backup_result').html(data).css('color',color);
            jQuery("#transposh-backup").click(backupclick).text("Do Backup Now");
        });
        return false;
    };
    jQuery("#transposh-backup").click(backupclick);

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
        jQuery.getJSON(t_jp.post_url,{
            translate_all:"y"
        }, function (data) {
            dotimer = function(a) {
                clearTimeout(timer2);
                //console.log(a);
                //console.log(jQuery("#tr_loading").data("done"));
                if (jQuery("#tr_loading").data("done") || jQuery("#tr_loading").data("attempt")>4) {
                    jQuery("#progress_bar_all").progressbar('value' , (a+1)/data.length*100);
                    jQuery("#tr_loading").data("attempt",0);
                    translate_post(data[a]);
                    //console.log(jQuery("#tr_loading").data("done"));
                    //console.log("done translate" + a);
                    if (data[a] && !stop_translate_var) {
                        //console.log("trigger translation of " +a);
                        timer2 = setTimeout(function() {
                            dotimer(a+1)
                        },1000);
                    }
                } else {
                    //console.log("waiting for translation to finish 60 seconds");
                    jQuery("#tr_loading").data("attempt",jQuery("#tr_loading").data("attempt")+1);
                    timer2 = setTimeout(function() {
                        dotimer(a)
                    },60000);
                }
            }
            timer2 = setTimeout(function() {
                dotimer(0)
            },0);
        });
        jQuery("#transposh-translate").text("Stop translate")
        jQuery("#transposh-translate").click(stop_translate);
        return false;
    }

    stop_translate = function() {
        clearTimeout(timer2);
        stop_translate_var = true;
        jQuery("#transposh-translate").text("Translate All Now")
        jQuery("#transposh-translate").click(do_translate_all);
        return false;
    }

    jQuery("#transposh-translate").click(do_translate_all);

});
//}(jQuery)); // end of closure