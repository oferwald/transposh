<?php

/*
  Plugin Name: Dropdown selection with image
  Plugin URI: http://transposh.org/
  Description: A widget using javascript to present a dropdown selection box with images - adapted from: http://www.jankoatwarpspeed.com/post/2009/07/28/reinventing-drop-down-with-css-jquery.asp
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

/**
 * This function makes sure that the jquery dependency will be met
 * @global transposh_plugin $my_transposh_plugin
 */
function tp_widget_js() {
    global $my_transposh_plugin;
    wp_enqueue_script("transposh_widget", "{$my_transposh_plugin->transposh_plugin_url}/widgets/{$my_transposh_plugin->widget->base_widget_file_name}.js", array('jquery'), TRANSPOSH_PLUGIN_VER);
}

/**
 * This function does the actual HTML for the widget
 * @param array $args - http://trac.transposh.org/wiki/WidgetWritingGuide#functiontp_widgets_doargs
 */
function tp_widget_do($args) {
    global $my_transposh_plugin;
    // we calculate the plugin path part, so we can link the images there
    $plugpath = parse_url($my_transposh_plugin->transposh_plugin_url, PHP_URL_PATH);

    // we use this hidden field to later post the value
    echo '<input type="hidden" name="lang" id="lang" value=""/>';

    echo '<span class="' . NO_TRANSLATE_CLASS . '">';
    echo '<dl id="tp_dropdown" class="dropdown">';
    echo '<dt><a href="#"><span>Select language</span></a></dt><dd><ul>';

    foreach ($args as $langrecord) {
        $is_selected = $langrecord['active'] ? " selected=\"selected\"" : "";
        echo '<li><a href="#"><img class="flag" src="' . "$plugpath/img/flags/{$langrecord['flag']}" . '.png" alt="' . $langrecord['langorig'] . '"/> ' . $langrecord['langorig'] . '<span class="value">' . $langrecord['isocode'] . '</span></a></li>';
    }

    echo '</ul></dd></dl>';
    echo '</span>';
}
?>
