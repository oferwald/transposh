<?php

/*
  Plugin Name: Flags (With CSS)
  Plugin URI: http://transposh.org/
  Description: Widget with flags links (Using css sprites)
  Author: Team Transposh
  Version: 1.0
  Author URI: http://transposh.org/
  License: GPL (http://www.gnu.org/licenses/gpl.txt)
 */

/*  Copyright © 2009-2010 Transposh Team (website : http://transposh.org)
 *
 * 	This program is free software; you can redistribute it and/or modify
 * 	it under the terms of the GNU General Public License as published by
 * 	the Free Software Foundation; either version 2 of the License, or
 * 	(at your option) any later version.
 *
 * 	This program is distributed in the hope that it will be useful,
 * 	but WITHOUT ANY WARRANTY; without even the implied warranty of
 * 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * 	GNU General Public License for more details.
 *
 * 	You should have received a copy of the GNU General Public License
 * 	along with this program; if not, write to the Free Software
 * 	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * This function allows the widget to tell the invoker if it needs to calculate different urls per language, here it is needed
 * @return boolean
 */
function tp_widget_needs_post_url() {
    return true;
}

/**
 * Creates the list of flags (with css)
 * @param array $args - http://trac.transposh.org/wiki/WidgetWritingGuide#functiontp_widgets_doargs
 */
function tp_widget_do($args) {
    echo "<div class=\"" . NO_TRANSLATE_CLASS . " transposh_flags\" >";
    foreach ($args as $langrecord) {
	echo "<a href=\"{$langrecord['url']}\"" . ($langrecord['active'] ? ' class="tr_active"' : '' ) . '>' .
	display_flag("", $langrecord['flag'], $langrecord['langorig'], true) .
	"</a>";
    }
    echo "</div>";
}
?>
