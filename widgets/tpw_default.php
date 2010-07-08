<?php

/*
  Plugin Name: Default
  Plugin URI: http://transposh.org/
  Description: Default widget for transposh
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

/*
 * Want to write your own widget? - visit the wiki page on widgets http://trac.transposh.org/wiki/WidgetWritingGuide
 */

/*
 * This widget is the default langauge list widget, the one which provides a drop down select box which allows to choose a new target language
 */

/**
 * This function does the actual HTML for the widget
 * @param array $args - http://trac.transposh.org/wiki/WidgetWritingGuide#functiontp_widgets_doargs
 */
function tp_widget_do($args) {
    echo '<span class="' . NO_TRANSLATE_CLASS . '">'; // wrapping in no_translate to avoid translation of this list
    echo '<select name="lang" id="lang" onchange="Javascript:this.form.submit();">'; // this is a select box which posts on change
    echo '<option value="none">[Language]</option>';
    foreach ($args as $langrecord) {
	$is_selected = $langrecord['active'] ? " selected=\"selected\"" : "";
	echo "<option value=\"{$langrecord['isocode']}\"{$is_selected}>{$langrecord['langorig']}</option>";
    }
    echo "</select><br/>";
    echo "</span>";
}
?>
