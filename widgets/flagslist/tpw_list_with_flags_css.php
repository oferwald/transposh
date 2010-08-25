<?php

/*
  Plugin Name: List with flags (css)
  Plugin URI: http://transposh.org/
  Description: Widget with flags links (using css sprites) followed by language name
  Author: Team Transposh
  Version: 1.0.1
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

/**
 * This function allows the widget to tell the invoker if it needs to calculate different urls per language
 * @return boolean
 */
function tp_widget_needs_post_url() {
    return true;
}

/**
 * Instructs usage of a different .css file
 * @global transposh_plugin $my_transposh_plugin
 */
function tp_widget_css() {
    global $my_transposh_plugin;
    wp_enqueue_style("transposh_widget", "{$my_transposh_plugin->transposh_plugin_url}/widgets/flags/tpw_flags_css.css", array(), TRANSPOSH_PLUGIN_VER);
}

/**
 * Creates the list of flags (using css sprites) - followed by a language name link
 * @global transposh_plugin $my_transposh_plugin
 * @param array $args - http://trac.transposh.org/wiki/WidgetWritingGuide#functiontp_widgets_doargs
 */
function tp_widget_do($args) {
    echo "<div class=\"" . NO_TRANSLATE_CLASS . " transposh_flags\" >";
    foreach ($args as $langrecord) {
        echo "<a href=\"{$langrecord['url']}\"" . ($langrecord['active'] ? ' class="tr_active"' : '' ) . '>' .
        transposh_utils::display_flag('', $langrecord['flag'], $langrecord['langorig'], true) . '</a>';
        echo "<a href=\"{$langrecord['url']}\"" . ($langrecord['active'] ? ' class="tr_active"' : '' ) . '>' . "{$langrecord['langorig']}</a><br/>";
    }
    echo "</div>";
}
?>
