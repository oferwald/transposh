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

/*'function chbx_change(lang)'.
                '{'.
                'jQuery("#"+lang+"_edit").attr("checked",jQuery("#"+lang+"_view").attr("checked"))'.
                '}'.
                'jQuery(document).ready(function() {'.
                'jQuery("#tr_anon").click(function() {'.
                'if (jQuery("#tr_anon").attr("checked")) {'.
                'jQuery(".tr_editable").css("display","none");'.
                '} else {'.
                'jQuery(".tr_editable").css("display","");'.
                '}'.
                '});'.
                '});'.
                '</script>';*/
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

    // makes the languages sortable, with placeholder
    jQuery("#sortable").sortable({
        placeholder: "highlight"
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
    jQuery(".languages").dblclick(function() {
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
    });

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
});
