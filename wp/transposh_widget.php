<?php

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
 * Provides the sidebar widget for selecting a language and switching between edit/view
 * mode.
 */

//Define widget file prefix
define('TRANSPOSH_WIDGET_PREFIX', 'tpw_');

//class that reperesent the complete plugin
class transposh_plugin_widget {

    /** @var transposh_plugin Container class */
    private $transposh;
    /** @var string Contains the name of the used widget file */
    public $base_widget_file_name;

    //constructor of class, PHP4 compatible construction for backward compatibility
    function transposh_plugin_widget(&$transposh) {
        //Register callback for WordPress events
        $this->transposh = &$transposh;
        add_action('init', array(&$this, 'init_transposh'), 1);
        add_action('widgets_init', array(&$this, 'transposh_widget_init'));
    }

    /**
     * Intercept init calls to see if it was posted by the widget.
     */
    function init_transposh() {
        if (isset($_POST['transposh_widget_posted'])) {
            logger("Enter", 4);

            $ref = getenv('HTTP_REFERER');
            $lang = $_POST[LANG_PARAM];
            if ($lang == '') {
                $lang = transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->transposh->home_url);
            }
            if ($lang == $this->transposh->options->get_default_language() || $lang == "none")
                    $lang = '';
            logger("Widget referrer: $ref, lang: $lang", 4);

            // first, we might need to get the original url back
            if ($this->transposh->options->get_enable_url_translate()) {
                $ref = transposh_utils::get_original_url($ref, $this->transposh->home_url, transposh_utils::get_language_from_url($ref, $this->transposh->home_url), array($this->transposh->database, 'fetch_original'));
            }

            //remove existing language settings.
            $ref = transposh_utils::cleanup_url($ref, $this->transposh->home_url);
            logger("cleaned referrer: $ref, lang: $lang", 4);

            if ($lang && $this->transposh->options->get_enable_url_translate()) {
                // and then, we might have to translate it
                $ref = transposh_utils::translate_url($ref, $this->transposh->home_url, $lang, array(&$this->transposh->database, 'fetch_translation'));
                $ref = transposh_utils::urlencode($ref);
                logger("translated to referrer: $ref, lang: $lang", 3);
            }
            $ref = transposh_utils::rewrite_url_lang_param($ref, $this->transposh->home_url, $this->transposh->enable_permalinks_rewrite, $lang, $_POST[EDIT_PARAM]);

            logger("Widget redirect url: $ref", 3);

            wp_redirect($ref);
            exit;
        }
    }

    /**
     * Register the widget.
     */
    function transposh_widget_init() {
        logger('Enter', 4);
        if (!function_exists('register_sidebar_widget')) {
            return;
        }

        // Register widget
        wp_register_sidebar_widget('Transposh', __('Transposh', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'transposh_widget'), array('description' => __('Transposh language selection widget', TRANSPOSH_TEXT_DOMAIN)));

        // Register widget control
        wp_register_widget_control('Transposh', __('Transposh', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'transposh_widget_control'));

        // Register callback for widget's css and js
        add_action('wp_print_styles', array(&$this, 'add_transposh_widget_css'));
        add_action('wp_print_scripts', array(&$this, 'add_transposh_widget_js'));
    }

    /**
     * Loads the widget code
     */
    function load_widget() {
        // avoid dual loadings
        if ($this->base_widget_file_name) return;

        $file = $this->transposh->options->get_widget_file();
        $widget_src = $this->transposh->transposh_plugin_dir . TRANSPOSH_DIR_WIDGETS . '/' . $file;
        if ($file && file_exists($widget_src)) {
            include_once $widget_src;
        } else {
            $file = 'default/tpw_default.php';
            include_once $this->transposh->transposh_plugin_dir . TRANSPOSH_DIR_WIDGETS . '/' . $file;
        }
        $this->base_widget_file_name = substr($file, 0, -4);
    }

    /**
     * Add custom css, i.e. transposh_widget.css, flags now override widget
     */
    function add_transposh_widget_css() { //TODO ! goway
        $this->load_widget();

        if (function_exists('tp_widget_css')) {
            tp_widget_css();
        } else {
            $widget_css = TRANSPOSH_DIR_WIDGETS . '/' . $this->base_widget_file_name . ".css";
            if (file_exists($this->transposh->transposh_plugin_dir . $widget_css)) {
                wp_enqueue_style('transposh_widget', $this->transposh->transposh_plugin_url . '/' . $widget_css, '', TRANSPOSH_PLUGIN_VER);
            }
        }

        logger('Added transposh_widget_css', 4);
    }

    /**
     * Add custom css, i.e. transposh_widget.css, flags now override widget
     */
    function add_transposh_widget_js() { //TODO ! goway
        $this->load_widget();

        if (function_exists('tp_widget_js')) {
            tp_widget_js();
        } else {
            $widget_js = TRANSPOSH_DIR_WIDGETS . '/' . $this->base_widget_file_name . ".js";
            if (file_exists($this->transposh->transposh_plugin_dir . $widget_js)) {
                wp_enqueue_script('transposh_widget', $this->transposh->transposh_plugin_url . '/' . $widget_js, '', TRANSPOSH_PLUGIN_VER);
            }
        }
        logger('Added transposh_widget_js', 4);
    }

    function create_widget_args($calc_url, $clean_page_url) {
        $widget_args = array();
        $page_url = '';
        // loop on the languages
        foreach ($this->transposh->options->get_sorted_langs() as $code => $langrecord) {
            list ($langname, $language, $flag) = explode(',', $langrecord);

            // Only send languages which are viewable or (editable and the user is a translator)
            if ($this->transposh->options->is_viewable_language($code) ||
                    ($this->transposh->options->is_editable_language($code) && $this->transposh->is_translator()) ||
                    ($this->transposh->options->is_default_language($code))) {
                if ($calc_url) {
                    if ($this->transposh->options->get_enable_url_translate() && !$this->transposh->options->is_default_language($code)) {
                        $page_url = transposh_utils::translate_url($clean_page_url, '', $code, array(&$this->transposh->database, 'fetch_translation'));
                    } else {
                        $page_url = $clean_page_url;
                    }
                    // clean $code in default lanaguge
                    $page_url = transposh_utils::rewrite_url_lang_param($page_url, $this->transposh->home_url, $this->transposh->enable_permalinks_rewrite, $this->transposh->options->is_default_language($code) ? '' : $code, $this->transposh->edit_mode);
                }
                $widget_args[] = array(
                    'lang' => $langname,
                    'langorig' => $language,
                    'flag' => $flag,
                    'isocode' => $code,
                    'url' => $page_url,
                    'active' => ($this->transposh->target_language == $code));
            }
        }
        return $widget_args;
    }

    /**
     * Creates the widget html
     * @param array $args Contains such as $before_widget, $after_widget, $before_title, $after_title, etc
     */
    function transposh_widget($args) {
        $this->load_widget();

        // hmmm, this should actually prepare all vars needed, include the correct widget and send the vars to that function,
        $calc_url = false; // By default, avoid calculating the urls
        if (function_exists('tp_widget_needs_post_url'))
                $calc_url = tp_widget_needs_post_url();

        $clean_page = $this->transposh->get_clean_url();

        logger("WIDGET: clean page url: $clean_page", 4);

        $widget_args = $this->create_widget_args($calc_url, $clean_page);
        // at this point the widget args are ready

        logger('Enter widget', 4);

        // extract args given by wordpress
        extract($args);
        logger($args, 4);

        // widget default title
        echo $before_widget . $before_title . __('Translation', TRANSPOSH_TEXT_DOMAIN) . $after_title;

        // the widget is inside a form used for posting a language change or edit request
        echo '<form id="tp_form" action="' . $clean_page . '" method="post">';

        // actually run the external widget code
        tp_widget_do($widget_args);

        //at least one language showing - add the edit box if applicable
        if (!empty($widget_args)) {
            // this is the set default language line
            if ($this->transposh->options->get_widget_allow_set_default_language()) {
                If ((isset($_COOKIE['TR_LNG']) && $_COOKIE['TR_LNG'] != $this->transposh->target_language) || (!isset($_COOKIE['TR_LNG']) && !$this->transposh->options->is_default_language($this->transposh->target_language))) {
                    echo '<a id="' . SPAN_PREFIX . 'setdeflang" onClick="return false;" href="' . $this->transposh->post_url . '?tr_cookie_bck">' . __('Set as default language', TRANSPOSH_TEXT_DOMAIN) . '</a><br/>';
                }
            }
            // add the edit checkbox only for translators for languages marked as editable
            if ($this->transposh->is_editing_permitted()) {
                echo '<input type="checkbox" name="' . EDIT_PARAM . '" value="1" ' .
                ($this->transposh->edit_mode ? 'checked="checked"' : '') .
                ' onclick="this.form.submit();"/>&nbsp;Edit Translation';
            }

            echo '<input type="hidden" name="transposh_widget_posted" value="1"/>';
        } else {
            //no languages configured - error message
            echo '<p>No languages available for display. Check the Transposh settings (Admin).</p>';
        }

        echo "</form>";

        // Now this is a comment for those wishing to remove our logo (tplogo.png) and link (transposh.org) from the widget
        // first - according to the gpl, you may do so - but since the code has changed - please make in available under the gpl
        // second - we did invest a lot of time and effort into this, and the link is a way to help us grow and show your appreciation, if it
        // upsets you, feel more than free to move this link somewhere else on your page, such as the footer etc.
        // third - feel free to write your own widget, the translation core will work
        // forth - you can ask for permission, with a good reason, if you contributed to the code - it's a good enough reason :)
        // fifth - if you just delete the following line, it means that you have little respect to the whole copyright thing, which as far as we
        // understand means that by doing so - you are giving everybody else the right to do the same and use your work without any attribution
        // last - you can now remove the logo in exchange to a few percentage of ad and affiliate revenues on your pages, isn't that better?
        $plugpath = parse_url($this->transposh->transposh_plugin_url, PHP_URL_PATH);

        if (!$this->transposh->options->get_widget_remove_logo()) {
            $tagline = esc_attr__('Transposh', TRANSPOSH_TEXT_DOMAIN) . ' - ';
            switch (ord(md5($_SERVER['REQUEST_URI'])) % 5) {
                case 0:
                    $tagline .= esc_attr__('translation plugin for wordpress', TRANSPOSH_TEXT_DOMAIN);
                    break;
                case 1:
                    $tagline .= esc_attr__('wordpress translation plugin', TRANSPOSH_TEXT_DOMAIN);
                    break;
                case 2:
                    $tagline .= esc_attr__('translate your blog to 60+ languages', TRANSPOSH_TEXT_DOMAIN);
                    break;
                case 3:
                    $tagline .= esc_attr__('website crowdsourcing translation plugin', TRANSPOSH_TEXT_DOMAIN);
                    break;
                case 4:
                    $tagline .= esc_attr__('google translate and bing translate plugin for wordpress', TRANSPOSH_TEXT_DOMAIN);
                    break;
            }

            $extralang = '';
            if ($this->transposh->target_language != 'en') {
                $extralang = $this->transposh->target_language;
            }
        }

        echo '<div id="' . SPAN_PREFIX . 'credit">';
        if (!$this->transposh->options->get_widget_remove_logo()) {
            echo 'by <a href="http://tran' . 'sposh.org/' . $extralang . '"><img class="' . NO_TRANSLATE_CLASS . '" height="16" width="16" src="' .
            $plugpath . '/img/tplog' . 'o.png" style="padding:1px;border:0px" title="' . $tagline . '" alt="' . $tagline . '"/></a>';
        }
        echo '</div>';
        echo $after_widget;
    }

    function transposh_widget_post($save = true) {
        logger($_POST);
        logger('handled widget post');
        $this->transposh->options->set_widget_file($_POST[WIDGET_FILE]);
        $this->transposh->options->set_widget_progressbar($_POST[WIDGET_PROGRESSBAR]);
        $this->transposh->options->set_widget_allow_set_default_language($_POST[WIDGET_ALLOW_SET_DEFLANG]);
        $this->transposh->options->set_widget_remove_logo($_POST[WIDGET_REMOVE_LOGO_FOR_AD]);
        $this->transposh->options->set_widget_theme($_POST[WIDGET_THEME]);
        if ($save) $this->transposh->options->update_options();
        // Avoid coming here twice...
        unset($_POST['transposh-submit']);
    }

    /**
     * Inspired (and used code) from the get_plugins function of wordpress
     */
    function get_widgets($widget_folder = '') {
        get_plugins();

        $tp_widgets = array();
        $widget_root = $this->transposh->transposh_plugin_dir . "widgets";
        if (!empty($widget_folder)) $widget_root .= $widget_folder;

        // Files in wp-content/widgets directory
        $widgets_dir = @opendir($widget_root);
        $widget_files = array();
        if ($widgets_dir) {
            while (($file = readdir($widgets_dir) ) !== false) {
                if (substr($file, 0, 1) == '.') continue;
                if (is_dir($widget_root . '/' . $file)) {
                    $widgets_subdir = @ opendir($widget_root . '/' . $file);
                    if ($widgets_subdir) {
                        while (($subfile = readdir($widgets_subdir) ) !== false) {
                            if (substr($subfile, 0, 1) == '.') continue;
                            if (substr($subfile, 0, 4) == TRANSPOSH_WIDGET_PREFIX && substr($subfile, -4) == '.php')
                                    $widget_files[] = "$file/$subfile";
                        }
                    }
                }
                if (substr($file, 0, 4) == TRANSPOSH_WIDGET_PREFIX && substr($file, -4) == '.php')
                        $widget_files[] = $file;
            }
        } else {
            return $tp_widgets;
        }

        @closedir($widgets_dir);
        @closedir($widgets_subdir);

        if (empty($widget_files)) return $tp_widgets;

        foreach ($widget_files as $widget_file) {
            if (!is_readable("$widget_root/$widget_file")) continue;

            $widget_data = get_plugin_data("$widget_root/$widget_file", false, false); //Do not apply markup/translate as it'll be cached.

            if (empty($widget_data['Name'])) continue;

            $tp_widgets[plugin_basename($widget_file)] = $widget_data;
        }

        uasort($tp_widgets, create_function('$a, $b', 'return strnatcasecmp( $a["Name"], $b["Name"] );'));

        return $tp_widgets;
    }

    /**
     * This is the widget control, allowing the selection of presentation type.
     */
    function transposh_widget_control() {
        if (isset($_POST['transposh-submit'])) $this->transposh_widget_post();
        $themes = array('base', 'black-tie', 'blitzer', 'cupertino', 'dark-hive', 'dot-luv', 'eggplant', 'excite-bike', 'flick',
            'hot-sneaks', 'humanity', 'le-frog', 'mint-choc', 'overcast', 'pepper-grinder', 'redmond', 'smoothness', 'south-street',
            'start', 'sunny', 'swanky-purse', 'trontastic', 'ui-darkness', 'ui-lightness', 'vader');

        $widgets = $this->get_widgets();

        echo '<p><label for="' . WIDGET_FILE . '">' . __('Style:', TRANSPOSH_TEXT_DOMAIN) . '<br/>' .
        '<select id="transposh-style" name="' . WIDGET_FILE . '">';
        foreach ($widgets as $file => $widget) {
            logger($widget, 4);
            $selected = ($this->transposh->options->get_widget_file() == $file) ? ' selected="selected"' : '';
            echo "<option value=\"$file\"$selected>{$widget['Name']}</option>";
        }
        echo '</select>' .
        '</label></p>' .
        '<p><label for="transposh-progress">' . __('Effects:', TRANSPOSH_TEXT_DOMAIN) . '</label><br/>' .
        '<input type="checkbox" id="' . WIDGET_PROGRESSBAR . '" name="' . WIDGET_PROGRESSBAR . '"' . ($this->transposh->options->get_widget_progressbar() ? ' checked="checked"' : '') . '/>' .
        '<span style="border-bottom: 1px dotted #333; cursor: help; margin-left: 4px" title="' . esc_attr__('Show progress bar when a client triggers automatic translation', TRANSPOSH_TEXT_DOMAIN) . '">' . __('Show progress bar', TRANSPOSH_TEXT_DOMAIN) . '</span><br/>' .
        '<input type="checkbox" id="' . WIDGET_ALLOW_SET_DEFLANG . '" name="' . WIDGET_ALLOW_SET_DEFLANG . '"' . ($this->transposh->options->get_widget_allow_set_default_language() ? ' checked="checked"' : '') . '/>' .
        '<span style="border-bottom: 1px dotted #333; cursor: help; margin-left: 4px" title="' . esc_attr__('Widget will allow setting this language as user default', TRANSPOSH_TEXT_DOMAIN) . '">' . __('Allow user to set current language as default', TRANSPOSH_TEXT_DOMAIN) . '</span><br/>' .
        '<input type="checkbox" id="' . WIDGET_REMOVE_LOGO_FOR_AD . '" name="' . WIDGET_REMOVE_LOGO_FOR_AD . '"' . ($this->transposh->options->get_widget_remove_logo() ? ' checked="checked"' : '') . '/>' .
        '<span style="border-bottom: 1px dotted #333; cursor: help; margin-left: 4px" title="' . esc_attr__('Transposh logo will not appear on widget', TRANSPOSH_TEXT_DOMAIN) . '">' . __('Remove transposh logo (see <a href="http://transposh.org/logoterms">terms</a>)', TRANSPOSH_TEXT_DOMAIN) . '</span><br/>' .
        '</p>';

        echo '<p><label for="' . WIDGET_THEME . '">' . __('Theme:', TRANSPOSH_TEXT_DOMAIN) . '<br/>' .
        '<select id="transposh-style" name="' . WIDGET_THEME . '">';
        foreach ($themes as $theme) {
            $selected = ($this->transposh->options->get_widget_theme() == $theme) ? ' selected="selected"' : '';
            echo "<option value=\"$theme\"$selected>{$theme}</option>";
        }
        echo '</select>' .
        '</label></p>' .
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