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
    /** @var transposh_plugin Container class */
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
            logger("Enter", 4);
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
        logger("Enter", 4);
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

    /**
     * Creates the widget html
     * @param array $args Contains such as $before_widget, $after_widget, $before_title, $after_title, etc
     */
    function transposh_widget($args) {
        logger("Enter widget", 4);
        extract($args);
        logger($args,4);

        $page_url = $_SERVER["REQUEST_URI"];
        logger ("p3:".$page_url, 6);

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
                if ($this->transposh->options->get_widget_in_list()) echo "<ul>";

                foreach($GLOBALS['languages'] as $code => $lang2) {
                    list($language,$flag) = explode (",",$lang2);

                    //Only show languages which are viewable or (editable and the user is a translator)
                    if($this->transposh->options->is_viewable_language($code) ||
                            ($this->transposh->options->is_editable_language($code)   && $this->transposh->is_translator()) ||
                            ($this->transposh->options->is_default_language($code))) {
                        logger ("code = ".$code,5);
                        $page_url = rewrite_url_lang_param($clean_page_url,$this->transposh->home_url,$this->transposh->enable_permalinks_rewrite, $code, $this->transposh->edit_mode);
                        if ($this->transposh->options->is_default_language($code)) {
                            $page_url = $clean_page_url;
                        }

                        logger ("urlpath = ".$page_url,5);
                        if ($this->transposh->options->get_widget_in_list()) echo "<li>";
                        echo "<a href=\"" . $page_url . '"'.(($this->transposh->target_language == $code) ? ' class="tr_active"' :'').'>'.
                                display_flag("$plugpath/img/flags", $flag, $language,$this->transposh->options->get_widget_css_flags()).
                                "</a>";
                        if ($this->transposh->options->get_widget_style() != 1) {
                            echo "$language<br/>";
                            if ($this->transposh->options->get_widget_in_list()) echo "</li>";
                        }
                        $is_showing_languages = TRUE;
                    }
                }
                if ($this->transposh->options->get_widget_in_list()) echo "</ul>";
                echo "</div>";

                // this is the form for the edit...
                if ($this->transposh->options->get_widget_in_list()) echo "<ul><li>";
                echo "<form action=\"$clean_page_url\" method=\"post\">";
                echo "<input type=\"hidden\" name=\"lang\" id=\"lang\" value=\"{$this->transposh->target_language}\"/>";
                break;
            default: // language selection

                if ($this->transposh->options->get_widget_in_list()) echo "<ul><li>";
                echo "<form action=\"$clean_page_url\" method=\"post\">";
                echo "<span class=\"" .NO_TRANSLATE_CLASS . "\" >";
                echo "<select name=\"lang\"	id=\"lang\" onchange=\"Javascript:this.form.submit();\">";
                echo "<option value=\"none\">[Language]</option>";

                foreach($GLOBALS['languages'] as $code => $lang2) {
                    list($language,$flag) = explode (",",$lang2);

                    //Only show languages which are viewable or (editable and the user is a translator)
                    if($this->transposh->options->is_viewable_language($code) ||
                            ($this->transposh->options->is_editable_language($code)   && $this->transposh->is_translator()) ||
                            ($this->transposh->options->is_default_language($code))) {
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
            if ($this->transposh->options->get_widget_allow_set_default_language()) {
                If ((isset($_COOKIE['TR_LNG']) && $_COOKIE['TR_LNG'] != $this->transposh->target_language) || (!isset($_COOKIE['TR_LNG']) && !$this->transposh->options->is_default_language($this->transposh->target_language))) {
                    if ($this->transposh->js_included) {
                        echo '<a href="#" id="'.SPAN_PREFIX.'setdeflang" onClick="return false;">Set as default language</a><br/>';
                    } else {
                        echo '<a href="'.$this->transposh->home_url.'?tr_cookie_bck">Set as default language</a><br/>';
                    }
                }
            }
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
        if ($this->transposh->options->get_widget_in_list()) echo "</li></ul>";
        //TODO: maybe... echo "<button onClick=\"do_auto_translate();\">translate all</button>";
        if ($this->transposh->options->get_widget_in_list()) echo "<ul><li>";
        // Now this is a comment for those wishing to remove our logo (tplogo.png) and link (transposh.org) from the widget
        // first - according to the gpl, you may do so - but since the code has changed - please make in available under the gpl
        // second - we did invest a lot of time and effort into this, and the link is a way to help us grow and show your appreciation, if it
        // upsets you, feel more than free to move this link somewhere else on your page, such as the footer etc.
        // third - feel free to write your own widget, the translation core will work
        // forth - you can ask for permission, with a good reason, if you contributed to the code - it's a good enough reason :)
        // last - if you just delete the following line, it means that you have little respect to the whole copyright thing, which as far as we
        // understand means that by doing so - you are giving everybody else the right to do the same and use your work without any attribution
        echo "<div id=\"".SPAN_PREFIX."credit\">by <a href=\"http://tran"."sposh.org\"><img class=\"".NO_TRANSLATE_CLASS."\" height=\"16\" width=\"16\" src=\"$plugpath/img/tplog"."o.png\" style=\"padding:1px;border:0px\" title=\"Transposh\" alt=\"Transposh\"/></a></div>";
        if ($this->transposh->options->get_widget_in_list()) echo "</li></ul>";
        echo $after_widget;
    }

    function transposh_widget_post($save = true) {
        logger ($_POST);
        logger ('handled widget post');
        $this->transposh->options->set_widget_style($_POST[WIDGET_STYLE]);
        $this->transposh->options->set_widget_progressbar($_POST[WIDGET_PROGRESSBAR]);
        $this->transposh->options->set_widget_css_flags($_POST[WIDGET_CSS_FLAGS]);
        $this->transposh->options->set_widget_in_list($_POST[WIDGET_IN_LIST]);
        $this->transposh->options->set_widget_allow_set_default_language($_POST[WIDGET_ALLOW_SET_DEFLANG]);
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

        echo '<p><label for="'.WIDGET_STYLE.'">Style:<br />'.
                '<select id="transposh-style" name="'.WIDGET_STYLE.'">'.
                '<option value="0"' . ($this->transposh->options->get_widget_style() == 0 ? ' selected="selected"' : '').'>Language selection</option>'.
                '<option value="1"' . ($this->transposh->options->get_widget_style() == 1 ? ' selected="selected"' : '').'>Flags</option>'.
                '<option value="2"' . ($this->transposh->options->get_widget_style() == 2 ? ' selected="selected"' : '').'>Language list</option>'.
                '</select>'.
                '</label></p>'.
                '<p><label for="transposh-progress">Effects:</label><br/>'.
                '<input type="checkbox" id="'.WIDGET_PROGRESSBAR.'" name="'.WIDGET_PROGRESSBAR.'"'.($this->transposh->options->get_widget_progressbar() ? ' checked="checked"' : '').'/>'.
                '<span style="border-bottom: 1px dotted #333; cursor: help; margin-left: 4px" title="Show progress bar when a client triggers automatic translation">Show progress bar</span><br/>'.
                '<input type="checkbox" id="'.WIDGET_CSS_FLAGS.'" name="'.WIDGET_CSS_FLAGS.'"'.($this->transposh->options->get_widget_css_flags() ? ' checked="checked"' : '').'/>'.
                '<span style="border-bottom: 1px dotted #333; cursor: help; margin-left: 4px" title="Use a single sprite with all flags, makes pages load faster. Currently not suitable if you made changes to the flags.">Use CSS flags</span><br/>'.
                '<input type="checkbox" id="'.WIDGET_ALLOW_SET_DEFLANG.'" name="'.WIDGET_ALLOW_SET_DEFLANG.'"'.($this->transposh->options->get_widget_allow_set_default_language() ? ' checked="checked"' : '').'/>'.
                '<span style="border-bottom: 1px dotted #333; cursor: help; margin-left: 4px" title="Widget will allow setting this language as user default.">Allow user to set current language as default</span><br/>'.
                '<input type="checkbox" id="'.WIDGET_IN_LIST.'" name="'.WIDGET_IN_LIST.'"'.($this->transposh->options->get_widget_in_list() ? ' checked="checked"' : '').'/>'.
                '<span style="border-bottom: 1px dotted #333; cursor: help; margin-left: 4px" title="Wraps generated widget code with UL helps with some CSSs.">Wrap widget with an unordered list (UL)</span>'.
                '</p>'.
                '<input type="hidden" name="transposh-submit" id="transposh-submit" value="1"/>';
    }
}

/**
 * Function provided for old widget include code compatability
 * @param array $args Not needed
 */
function transposh_widget($args = array()) {
    $GLOBALS['my_transposh_plugin']->widget->transposh_widget($args);
}
?>