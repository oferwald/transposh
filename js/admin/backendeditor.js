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

/*global Date, Math, alert, escape, clearTimeout, document, jQuery, setTimeout, t_jp, t_be, window */
(function ($) { // closure
    // If we have a single post, we can just go through with it
    $(function () {
        $.ajaxSetup({
            cache: false
        });

        $(".delete").click(function () {
            var me = this;
            var href = $(this).children().attr('href');
            console.log(href);
            $.ajax({
                url: href,
                dataType: 'json',
                /*data: {
                 action: "tp_translate_all"
                 },*/
                cache: false,
                success: function (data) {
                    if (data) {
                        $(me).parents('tr').hide();
                    } else {
                        $(me).parents('tr').css("background-color", "red");
                    }
                    ;
                }
            });
            return false;
        });
    });
}(jQuery)); // end of closure