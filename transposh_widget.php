<?php
/*  Copyright © 2009 Transposh Team (website : http://transposh.org)
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

/*
 * Provides the sidebar widget for selecting a language and switching between edit/view
 * mode.
 */
require_once("core/logging.php");
require_once("core/constants.php");
require_once("core/utils.php");
require_once("transposh.php");


//class that reperesent the complete plugin
class transposh_plugin_widget {
    /** @property transposh_plugin $transposh father class */
    private $transposh;

    //constructor of class, PHP4 compatible construction for backward compatibility
    function transposh_plugin_widget(&$transposh) {
    //Register callback for WordPress events
        $this->transposh = &$transposh;
        add_action('init', array(&$this,'init_transposh'),1);
        add_action('widgets_init', array(&$this,'transposh_widget_init'));
    }

/*
 * Intercept init calls to see if it was posted by the widget.
 */
    function init_transposh() {
        if (isset ($_POST['transposh_widget_posted'])) {
            logger("Enter " . __METHOD__, 4);
            // FIX! yes, this is needed (not with peiorty!
            //transposh_plugin::init_global_vars();
            //$this->transposh->init_global_vars();

            $ref=getenv('HTTP_REFERER');
            $lang = $_POST[LANG_PARAM];
            logger("Widget referrer: $ref, lang: $lang", 4);

            //remove existing language settings.
            $ref = cleanup_url($ref,$this->transposh->home_url);
            logger("cleaned referrer: $ref, lang: $lang", 4);

            if($lang != "none") {
                $ref = rewrite_url_lang_param($ref,$this->transposh->home_url,$this->transposh->enable_permalinks_rewrite, $lang, $_POST[EDIT_PARAM]);
                logger("rewritten referrer: $ref, lang: $lang", 4);

            //ref is generated with html entities encoded, needs to be
            //decoded when used in the http header (i.e. 302 redirect)
            //$ref = html_entity_decode($ref, ENT_NOQUOTES);
            }

            logger("Widget redirect url: $ref", 3);

            wp_redirect($ref);
            exit;
        }
    }

/*
 * Register the widget.
 */
    function transposh_widget_init() {
        logger("Enter " . __METHOD__, 4);
        if (!function_exists('register_sidebar_widget')) {
            return;
        }

        // Register widget
        register_sidebar_widget(array('Transposh', 'widgets'),  array(&$this,'transposh_widget'));

        // Register widget control
        register_widget_control("Transposh", array(&$this,'transposh_widget_control'));

        //regigster callback for widget's css
        add_action('wp_print_styles',  array(&$this,'add_transposh_widget_css'));
    }

/*
 * Add custom css, i.e. transposh.css
 */
    function add_transposh_widget_css() {
    //include the transposh_widget.css
    // TODO: user generated version
        if ($this->transposh->options->get_widget_style() == 1 || $this->transposh->options->get_widget_style() == 2) {
            wp_enqueue_style("transposh_widget","{$this->transposh->transposh_plugin_url}/css/transposh_widget.css",array(),TRANSPOSH_PLUGIN_VER);
            if ($this->transposh->options->get_widget_css_flags()) {
                wp_enqueue_style("transposh_flags", "{$this->transposh->transposh_plugin_url}/css/transposh_flags.css",array(),TRANSPOSH_PLUGIN_VER);
                if (file_exists("{$this->transposh->transposh_plugin_url}/css/transposh_flags_u.css"))
                    wp_enqueue_style("transposh_flags", "{$this->transposh->transposh_plugin_url}/css/transposh_flags_u.css",array(),TRANSPOSH_PLUGIN_VER);
            }
        }
        logger("Added transposh_widget_css", 4);
    }

/*
 * The actual widget implementation.
 */
    function transposh_widget($args) {
        logger("Enter " . __METHOD__, 4);
        extract($args);

        $page_url = $_SERVER["REQUEST_URI"];
        logger ("p3:".$page_url, 6);

        //$options = get_option(WIDGET_TRANSPOSH);
        $viewable_langs = $this->transposh->options->get_viewable_langs();
        $editable_langs = $this->transposh->options->get_editable_langs();
        logger (__LINE__."$viewable_langs");
        logger (__LINE__."$editable_langs");
        $is_translator = $this->transposh->is_translator();

        $is_showing_languages = FALSE;
        //TODO: improve this shortening
        $plugpath = parse_url($this->transposh->transposh_plugin_url, PHP_URL_PATH);

        echo $before_widget . $before_title . __("Translation") . $after_title;

        //remove any language identifier
        $clean_page_url = cleanup_url($page_url,$this->transposh->home_url, true);
        logger ("WIDGET: clean page url: $clean_page_url ,orig: $page_url");

        switch ($this->transposh->options->get_widget_style()) {
            case 1: // flags
            case 2: // language list
            //keep the flags in the same direction regardless of the overall page direction
                echo "<div class=\"" . NO_TRANSLATE_CLASS . " transposh_flags\" >";

                foreach($GLOBALS['languages'] as $code => $lang2) {
                    list($language,$flag) = explode (",",$lang2);

                    //Only show languages which are viewable or (editable and the user is a translator)
                    if(strpos($viewable_langs, $code) !== FALSE || $this->transposh->options->is_editable_language($code) || ($this->transposh->options->get_default_language() == $code)) {
                        logger ("code = ".$code,5);
                        $page_url = rewrite_url_lang_param($clean_page_url,$this->transposh->home_url,$this->transposh->enable_permalinks_rewrite, $code, $this->transposh->edit_mode);
                        if ($this->transposh->options->get_default_language() == $code) {
                            $page_url = $clean_page_url;
                        }

                        logger ("urlpath = ".$page_url,5);
                        echo "<a href=\"" . $page_url . '"'.(($this->transposh->target_language == $code) ? ' class="tr_active"' :'').'>'.
                            display_flag("$plugpath/img/flags", $flag, $language,$this->transposh->options->get_widget_css_flags()).
                            "</a>";
                        if ($this->transposh->options->get_widget_style() != 1) {
                            echo "$language<br/>";
                        }
                        $is_showing_languages = TRUE;
                    }
                }
                echo "</div>";

                // this is the form for the edit...
                echo "<form action=\"$clean_page_url\" method=\"post\">";
                echo "<input type=\"hidden\" name=\"lang\" id=\"lang\" value=\"{$this->transposh->target_language}\"/>";
                break;
            default: // language selection

                echo "<form action=\"$clean_page_url\" method=\"post\">";
                echo "<span class=\"" .NO_TRANSLATE_CLASS . "\" >";
                echo "<select name=\"lang\"	id=\"lang\" onchange=\"Javascript:this.form.submit();\">";
                echo "<option value=\"none\">[Language]</option>";

                foreach($GLOBALS['languages'] as $code => $lang2) {
                    list($language,$flag) = explode (",",$lang2);

                    //Only show languages which are viewable or (editable and the user is a translator)
                    if(strpos($viewable_langs, $code) !== FALSE || $this->transposh->options->is_editable_language($code) || ($this->transposh->options->get_default_language() == $code)) {
                        $is_selected = ($this->transposh->target_language == $code ? "selected=\"selected\"" : "" );
                        echo "<option value=\"$code\" $is_selected>" . $language . "</option>";
                        $is_showing_languages = TRUE;
                    }
                }
                echo "</select><br/>";
                echo "</span>"; // the no_translate for the language list
        }

        //at least one language showing - add the edit box if applicable
        if($is_showing_languages) {
        //Add the edit checkbox only for translators  on languages marked as editable
            if($this->transposh->is_editing_permitted()) {
                echo "<input type=\"checkbox\" name=\"" . EDIT_PARAM . "\" value=\"1\" " .
                    ($this->transposh->edit_mode ? "checked=\"checked\"" : "") .
                    " onclick=\"this.form.submit();\"/>&nbsp;Edit Translation";
            }

            echo "<input type=\"hidden\" name=\"transposh_widget_posted\" value=\"1\"/>";
        }
        else {
        //no languages configured - error message
            echo '<p>No languages available for display. Check the Transposh settings (Admin).</p>';
        }

        echo "</form>";
        //echo "<button onClick=\"do_auto_translate();\">translate all</button>";
        echo "<div id=\"".SPAN_PREFIX."credit\">by <a href=\"http://transposh.org\"><img class=\"".NO_TRANSLATE_CLASS."\" height=\"16\" width=\"16\" src=\"$plugpath/img/tplogo.png\" style=\"padding:1px;border:0px\" title=\"Transposh\" alt=\"Transposh\"/></a></div>";
        echo $after_widget;
    }

    function transposh_widget_post($save = true) {
        logger ($_POST);
        logger (__LINE__.': handled widget post');
        $this->transposh->options->set_widget_style($_POST['transposh-style']);
        $this->transposh->options->set_widget_progressbar($_POST['transposh-progress']);
        //$this->transposh->options->set_widget_css_flags($_POST['transposh-css']);
        if ($save)
            $this->transposh->options->update_options();
        // Avoid coming here twice...
        unset($_POST['transposh-submit']);
    }

/*
 * This is the widget control, allowing the selection of presentation type.
 */
    function transposh_widget_control() {
        if (isset($_POST['transposh-submit'])) $this->transposh_widget_post();
        //$options = get_option(WIDGET_TRANSPOSH);

        echo '<p><label for="transposh-style">Style:<br />'.
            '<select id="transposh-style" name="transposh-style">'.
            '<option value="0"' . ($this->transposh->options->get_widget_style() == 0 ? ' selected="selected"' : '').'>Language selection</option>'.
            '<option value="1"' . ($this->transposh->options->get_widget_style() == 1 ? ' selected="selected"' : '').'>Flags</option>'.
            '<option value="2"' . ($this->transposh->options->get_widget_style() == 2 ? ' selected="selected"' : '').'>Language list</option>'.
            '</select>'.
            '</label></p>'.
            '<p><label for="transposh-progress">Effects:</label><br/>'.
            '<input type="checkbox" id="transposh-progress" name="transposh-progress"'.($this->transposh->options->get_widget_progressbar() ? ' checked="checked"' : '').'/>'.
            '<span style="border-bottom: 1px dotted #333; cursor: help; margin-left: 4px" title="Show progress bar when a client triggers automatic translation">Show progress bar</span><br/>'.
/*TODO- just do it :)
            '<input type="checkbox" id="transposh-css" name="transposh-css"'.($this->transposh->options->get_widget_css_flags() ? ' checked="checked"' : '').'/>'.
            '<span style="border-bottom: 1px dotted #333; cursor: help; margin-left: 4px" title="Use a single sprite with all flags, makes pages load faster. Currently not suitable if you made changes to the flags.">Use CSS flags</span>'.
*/            '</p>'.
            '<input type="hidden" name="transposh-submit" id="transposh-submit" value="1"/>';
    }
}
?>