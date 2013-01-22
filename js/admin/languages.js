/*  Copyright © 2009-2013 Transposh Team (website : http://transposh.org)
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
    $(function() {
        // makes the languages sortable, with placeholder, also prevent unneeded change after sort
        $("#sortable").sortable({
            placeholder: "highlight",
            update: function(event, ui) {
                ui.item.unbind("click");
                ui.item.one("click", function (event) {
                    event.stopImmediatePropagation();
                    $(this).click(clickfunction);
                });
            }
        });
        $("#sortable").disableSelection();

        // toggles display of english/original names
        $("#changename").click(function(){
            $(".langname").toggleClass("hidden");
            return false;
        });

        // enable all languages
        $("#selectall").click(function(){
            $("#sortable .languages").addClass("lng_active");
            $("#sortable .lng_active").each(function () {
                $("input",this).val($(this).attr("id")+",v");
            })
            return false;
        });

        // two flows on double click, if anonymous -> active, inactive otherwise active, translatable, inactive
        clickfunction = function () {
            if ($(this).attr("id") == $("#default_list li").attr("id")) return;
            $(this).toggleClass("lng_active");
            // set new value
            $("input",this).val($(this).attr("id")+($(this).hasClass("lng_active") ? ",v":","));
        }
        $(".languages").dblclick(clickfunction).click(clickfunction);

        // the default language droppable
        $("#default_lang").droppable({
            accept: ".languages",
            activeClass: "highlight_default",
            drop: function(ev, ui) {
                $("#default_list").empty();
                $(ui.draggable.clone().removeAttr("style").removeClass("lng_active")).appendTo("#default_list").show("slow");
                $("#default_list .logoicon").remove();
                $("#sortable").find("#"+ui.draggable.attr("id")).addClass("lng_active");
            }
        });
        // sorting by iso
        $("#sortiso").click(function() {
            $("#sortable li").sort(function(a,b){
                //console.log(a);
                if ($(a).attr("id") == $("#default_list li").attr("id")) return -1;
                if ($(b).attr("id") == $("#default_list li").attr("id")) return 1;
                return $(a).attr("id") > $(b).attr("id") ? 1 : -1;
            }).remove().appendTo("#sortable").dblclick(clickfunction).click(clickfunction);
            return false;
        });
        // sorting by name
        $("#sortname").click(function() {
            $("#sortable li").sort(function(a,b){
                langa = $(".langname",a).filter(function() {
                    return !$(this).hasClass("hidden")
                }).text();
                langb = $(".langname",b).filter(function() {
                    return !$(this).hasClass("hidden")
                }).text();
                langdef = $(".langname","#default_list li").filter(function() {
                    return !$(this).hasClass("hidden")
                }).text();
                if (langa == langdef) return -1;
                if (langb == langdef) return 1;
                return langa > langb ? 1 : -1;
            }).remove().appendTo("#sortable").dblclick(clickfunction).click(clickfunction);
            return false;
        });
    });
}(jQuery)); // end of closure