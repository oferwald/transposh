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

class tpw_image_dropdown extends transposh_base_widget {

    /**
     * This function makes sure that the jquery dependency will be met
     * @global transposh_plugin $my_transposh_plugin
     */
    static function tp_widget_js($file, $dir, $url) {
        wp_enqueue_script("transposh_widget", "$url/widgets/dropdown/tpw_image_dropdown.js", array('jquery'), TRANSPOSH_PLUGIN_VER);
    }

    /**
     * This function does the actual HTML for the widget
     * @param array $args - http://trac.transposh.org/wiki/WidgetWritingGuide#functiontp_widgets_doargs
     */
    static function tp_widget_do($args) {
        global $my_transposh_plugin;
        // we calculate the plugin path part, so we can link the images there
        $plugpath = parse_url($my_transposh_plugin->transposh_plugin_url, PHP_URL_PATH);

        echo '<dl class="tp_dropdown dropdown">';
        /* TRANSLATORS: this is what appears in the select box in dropdown subwidget */
        echo '<dt><a href="#"><span>' . __('Select language', TRANSPOSH_TEXT_DOMAIN) . '</span></a></dt><dd><ul class="' . NO_TRANSLATE_CLASS . '">';
        foreach ($args as $langrecord) {
        // $is_selected = $langrecord['active'] ? " selected=\"selected\"" : "";
            echo '<li'. ($langrecord['active'] ? ' class="tr_active"' : '' ) .'><a href="#"><img class="flag" src="' . "$plugpath/img/flags/{$langrecord['flag']}" . '.png" alt="' . $langrecord['langorig'] . '"/> ' . $langrecord['langorig'] . '<span class="value">' . $langrecord['url'] . '</span></a></li>';
        }
        echo '</ul></dd></dl>';
    }

}

?>
