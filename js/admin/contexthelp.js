/*  Copyright © 2009-2018 Transposh Team (website : http://transposh.org)
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
    $('.tp_help').live('click', function (event) {
        event.preventDefault();
        window.scrollTo(0, 0);
        $('#tab-link-' + jQuery(this).attr('rel') + ' a').trigger('click');
        if (!$('#contextual-help-link').hasClass('screen-meta-active'))
            $('#contextual-help-link').trigger('click');
    });
}(jQuery)); // end of closure