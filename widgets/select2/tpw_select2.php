<?php

/*
  Plugin Name: Select2 based Dropdown
  Plugin URI: http://transposh.org/
  Description: A nice select2 based widget based on the select2 library (http://ivaynberg.github.com/select2/index.html)
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

class tpw_select2 extends transposh_base_widget {

    static function tp_widget_js($file, $dir, $url) {
        wp_enqueue_script("select2", "$url/widgets/select2/select2.min.js", array('jquery'), TRANSPOSH_PLUGIN_VER);
        wp_enqueue_script("transposh_widget_select2", "$url/widgets/select2/tpw_select2.js", array('jquery'), TRANSPOSH_PLUGIN_VER);
    }
    
    static function tp_widget_css($file, $dir, $url) {
        wp_enqueue_style("flags_tpw_flags_css", "$url/widgets/flags/tpw_flags_css.css", array(), TRANSPOSH_PLUGIN_VER);
        wp_enqueue_style("select2", "$url/widgets/select2/select2.css", array(), TRANSPOSH_PLUGIN_VER);
    }
    
    static function tp_widget_do($args) {
        echo '<span class="' . NO_TRANSLATE_CLASS . '">'; 

        echo '<select style="width:100%" name="lang" class="tp_lang2" id="tp_lang2" onchange="document.location.href=this.options[this.selectedIndex].value;">'; 
        foreach ($args as $langrecord) {
            $is_selected = $langrecord['active'] ? " selected=\"selected\"" : "";
            echo "<option value=\"{$langrecord['url']}\" data-flag=\"{$langrecord['flag']}\" data-lang=\"{$langrecord['lang']}\"{$is_selected}>{$langrecord['langorig']}</option>";
        }
        echo "</select><br/>";

        echo "</span>";
    }

}

?>
