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

/*
 * Transposh v%VERSION%
 * http://transposh.org/
 *
 * Copyright %YEAR%, Team Transposh
 * Licensed under the GPL Version 2 or higher.
 * http://transposh.org/license
 *
 * Date: %DATE%
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
class tpw_default extends transposh_base_widget {

    static function tp_widget_do($args) {
        echo '<span class="' . NO_TRANSLATE_CLASS . '">'; // wrapping in no_translate to avoid translation of this list

        echo '<select name="lang" onchange="document.location.href=this.options[this.selectedIndex].value;">'; // this is a select box which posts on change
        foreach ($args as $langrecord) {
            $is_selected = $langrecord['active'] ? " selected=\"selected\"" : "";
            echo "<option value=\"{$langrecord['url']}\"{$is_selected}>{$langrecord['langorig']}</option>";
        }
        echo "</select><br/>";

        echo "</span>";
    }

}

?>
