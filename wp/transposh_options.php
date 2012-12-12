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

// This magic value will cause the option to be set from post
define('TP_FROM_POST', 'tp_post_1x');
// types of options
define('TP_OPT_BOOLEAN', 0);
define('TP_OPT_STRING', 1);
define('TP_OPT_IP', 2);
define('TP_OPT_OTHER', 3);

/**
 * @property string $desc Description
 */
class transposh_option {

    private $name;
    private $value;
    private $type;

    public function __construct($name, $value = '', $type = '') {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
    }

    function __toString() {
        return (string) $this->value;
    }

    function set_value($value) {
        $this->value = $value;
    }

    function from_post() {
        $this->value = $_POST[$this->name];
    }

    function get_name() {
        return $this->name;
    }

    function get_value() {
        return $this->value;
    }

    function get_type() {
        return $this->type;
    }

    function post_value_id_name() {
        return 'value="' . $this->value . '" id="' . $this->name . '" name="' . $this->name . '"';
    }

}

/**
 * Used properties for code completion - we'll try to keep them in same order as admin screens
 * 
 * Language tab
 * @property string           $default_language      Option defining the default language
 * @property transposh_option $default_language_o 
 * @property string           $viewable_languages    Option defining the list of currently viewable languages
 * @property transposh_option $viewable_languages_o 
 * @property string           $sorted_languages      Option defining the ordered list of languages @since 0.3.9
 * @property transposh_option $sorted_languages_o 
 * 
 * Settings
 * 
 * @property boolean          $allow_anonymous_translation   Option defining whether anonymous translation is allowed
 * @property transposh_option $allow_anonymous_translation_o
 * @property boolean          $enable_default_translate      Option to enable/disable default language translation
 * @property transposh_option $enable_default_translate_o
 * @property boolean          $enable_search_translate       Option to enable/disable default language translation @since 0.3.6
 * @property transposh_option $enable_search_translate_o
 * @property boolean          $transposh_gettext_integration Make the gettext interface optional (@since 0.6.4)
 * @property transposh_option $transposh_gettext_integration_o
 * @property boolean          $transposh_locale_override     Allow override for default locale (@since 0.7.5)
 * @property transposh_option $transposh_locale_override_o
 * 
 * @property boolean          $enable_permalinks             Option to enable/disable rewrite of permalinks
 * @property transposh_option $enable_permalinks_o
 * @property boolean          $enable_footer_scripts         Option to enable/disable footer scripts (2.8 and up)
 * @property transposh_option $enable_footer_scripts_o
 * @property boolean          $enable_detect_redirect        Option to enable detect and redirect language @since 0.3.8
 * @property transposh_option $enable_detect_redirect_o
 * @property boolean          $transposh_collect_stats       Should I allow collecting of anonymous stats (@since 0.7.6)
 * @property transposh_option $transposh_collect_stats_o
 * 
 * @property int              $transposh_backup_schedule     Stores the schedule for the backup service, 0-none, 1-daily, 2-live (backup @since 0.5.0)
 * @property transposh_option $transposh_backup_schedule_o  
 * @property string           $transposh_key                 Stores the site key to transposh services (backup @since 0.5.0)
 * @property transposh_option $transposh_key_o
 * 
 * Engines
 * 
 * @property boolean          $enable_autotranslate          Option to enable/disable auto translation
 * @property transposh_option $enable_autotranslate_o
 * @property boolean          $enable_autoposttranslate      Option to enable/disable auto translation of posts
 * @property transposh_option $enable_autoposttranslate_o
 * @property string           $msn_key                       Option to store the msn API key
 * @property transposh_option $msn_key_o
 * @property string           $google_key                    Option to store the msn Google key
 * @property transposh_option $google_key_o
 * @property int              $preferred_translator          Option to store translator preference @since 0.4.2
 * @property transposh_option $preferred_translator_o
 * @property string           $oht_id                        Option to store the oht ID
 * @property transposh_option $oht_id_o
 * @property string           $oht_key                       Option to store the oht key;
 * @property transposh_option $oht_key_o
 * 
 * Widget
 * 
 * @property boolean          $widget_progressbar            Option allowing progress bar display
 * @property transposh_option $widget_progressbar_o
 * @property boolean          $widget_allow_set_deflang      Allows user to set his default language per #63 @since 0.3.8
 * @property transposh_option $widget_allow_set_deflang_o
 * @property boolean          $widget_remove_logo            Allows removing of transposh logo in exchange for an ad @since 0.6.0
 * @property transposh_option $widget_remove_logo_o
 * @property string           $widget_theme                  Allows theming of the progressbar and edit window @since 0.7.0
 * @property transposh_option $widget_theme_o
 * 
 * Advanced
 * 
 * @property boolean          $enable_url_translate          Option to enable/disable url translation @since 0.5.3
 * @property transposh_option $enable_url_translate_o
 * @property boolean          $parser_dont_break_puncts      Option to allow punctuations such as , . ( not to break @since 0.9.0
 * @property transposh_option $parser_dont_break_puncts_o
 * @property boolean          $parser_dont_break_numbers     Option to allow numbers not to break @since 0.9.0
 * @property transposh_option $parser_dont_break_numbers_o
 * @property boolean          $parser_dont_break_entities    Option to allow html entities not to break @since 0.9.0
 * @property transposh_option $parser_dont_break_entities_o
 * @property boolean          $debug_enable Option to enable debug
 * @property transposh_option $debug_enable_o
 * @property int              $debug_loglevel Option holding the level of logging
 * @property transposh_option $debug_loglevel_o
 * @property string           $debug_logfile Option holding a filename to store debugging into
 * @property transposh_option $debug_logfile_o
 * @property ip               $debug_remoteip Option that limits remote firePhp debug to a certain IP
 * @property transposh_option $debug_remoteip_o
 * 
 * Hidden
 * 
 * @property transposh_option $transposh_admin_hide_warnings Stores hidden warnings (@since 0.7.6)
 * 
 */
class transposh_plugin_options {

    /** @var array storing all our options */
    private $options = array();

    /** @var boolean set to true if any option was changed */
    private $changed = false;
    private $vars = array();

    function set_default_option_value($option, $value = '') {
        if (!isset($this->options[$option])) $this->options[$option] = $value;
    }

    // private $vars array() = (1,2,3);

    function register_option($name, $type, $default_value = '') {
        if (!isset($this->options[$name]))
                $this->options[$name] = $default_value;
        // can't log...     tp_logger($name . ' ' . $this->options[$name]);
        $this->vars[$name] = new transposh_option($name, $this->options[$name], $type);
    }

    function __get($name) {
        if (substr($name, -2) === "_o")
                return $this->vars[substr($name, 0, -2)];
        // can't!? tp_logger($this->vars[$name]->get_value(), 5);
        return $this->vars[$name]->get_value();
    }

    function __set($name, $value) {
        if ($value == TP_FROM_POST && isset($_POST[$name])) {
            $value = $_POST[$name];
        }
        
        if (TP_OPT_BOOLEAN == $this->vars[$name]->get_type()) {
            $value = ($value) ? 1 : 0;
        }
        
        if ($this->vars[$name]->get_value() !== $value) {
            tp_logger("option '$name' value set: $value");
            $this->vars[$name]->set_value($value);
            $this->changed = true;
        }
    }

    function __construct() {

        // can't      tp_logger("creating options");
        // load them here
        $this->options = get_option(TRANSPOSH_OPTIONS);
//        tp_logger($this->options);

        $this->register_option('default_language', TP_OPT_STRING); // default?
        $this->register_option('viewable_languages', TP_OPT_STRING);
        $this->register_option('sorted_languages', TP_OPT_STRING);

        $this->register_option('allow_anonymous_translation', TP_OPT_BOOLEAN, 1);
        $this->register_option('enable_default_translate', TP_OPT_BOOLEAN, 0);
        $this->register_option('enable_search_translate', TP_OPT_BOOLEAN, 1);
        $this->register_option('transposh_gettext_integration', TP_OPT_BOOLEAN, 1);
        $this->register_option('transposh_locale_override', TP_OPT_BOOLEAN, 1);

        $this->register_option('enable_permalinks', TP_OPT_BOOLEAN, 0);
        $this->register_option('enable_footer_scripts', TP_OPT_BOOLEAN, 0);
        $this->register_option('enable_detect_redirect', TP_OPT_BOOLEAN, 0);
        $this->register_option('transposh_collect_stats', TP_OPT_BOOLEAN, 1);

        $this->register_option('transposh_backup_schedule', TP_OPT_OTHER, 2);
        $this->register_option('transposh_key', TP_OPT_STRING);

        $this->register_option('enable_autotranslate', TP_OPT_BOOLEAN, 1);
        $this->register_option('enable_autoposttranslate', TP_OPT_BOOLEAN, 1);
        $this->register_option('msn_key', TP_OPT_STRING);
        $this->register_option('google_key', TP_OPT_STRING);
        $this->register_option('preferred_translator', TP_OPT_OTHER, 1); // 1 is Google, 2 is MSN
        $this->register_option('oht_id', TP_OPT_STRING);
        $this->register_option('oht_key', TP_OPT_STRING);


        $this->register_option('widget_progressbar', TP_OPT_BOOLEAN, 0);
        $this->register_option('widget_allow_set_deflang', TP_OPT_BOOLEAN, 0);
        $this->register_option('widget_remove_logo', TP_OPT_BOOLEAN, 0);
        $this->register_option('widget_theme', TP_OPT_STRING, 'ui-lightness');

        $this->register_option('enable_url_translate', TP_OPT_BOOLEAN, 0);
        $this->register_option('parser_dont_break_puncts', TP_OPT_BOOLEAN, 0);
        $this->register_option('parser_dont_break_numbers', TP_OPT_BOOLEAN, 0);
        $this->register_option('parser_dont_break_entities', TP_OPT_BOOLEAN, 0);
        $this->register_option('debug_enable', TP_OPT_BOOLEAN, 0);
        $this->register_option('debug_loglevel', TP_OPT_OTHER, 3);
        $this->register_option('debug_logfile', TP_OPT_STRING, '');
        $this->register_option('debug_remoteip', TP_OPT_IP, '');


        $this->register_option('transposh_admin_hide_warnings', TP_OPT_OTHER);


        // Fix default language if needed, only done once now, and since this was being done constantly, we gain
        //tp_logger($this->default_language->get_value());

        if (!isset(transposh_consts::$languages[$this->default_language])) {
            if (defined('WPLANG') && isset(transposh_consts::$languages[WPLANG])) {
                $this->default_language = WPLANG;
            } else {
                $this->default_language = "en";
            }
        }

        // can't log...   tp_logger($this->options, 4);
    }

    /**
     * Get a user sorted language list
     * @since 0.3.9
     * @return array sorted list of languages, pointing to names and flags
     */
    function get_sorted_langs() {
        if ($this->sorted_languages) {
            tp_logger($this->sorted_languages, 5);
            return array_merge(array_flip(explode(",", $this->sorted_languages)), transposh_consts::$languages);
        }
        return transposh_consts::$languages;
    }

    function get_transposh_admin_hide_warning($id) {
        return strpos($this->transposh_admin_hide_warnings, $id . ',') !== false;
    }

    function set_transposh_admin_hide_warning($id) {
        if (!$this->get_transposh_admin_hide_warning($id)) {
            $this->transposh_admin_hide_warnings = $this->transposh_admin_hide_warnings . $id . ',';
        }
    }

    /**
     * Updates options at the wordpress options table if there was a change
     */
    function update_options() {
        if ($this->changed) {
            foreach ($this->vars as $name => $var) {
                $this->options[$name] = $var->get_value();
            }
            update_option(TRANSPOSH_OPTIONS, $this->options);
            $this->changed = false;
        } else {
            tp_logger("no changes and no updates done", 3);
        }
    }

    /**
     * Resets all options except keys
     */
    function reset_options() {
        $this->options = array();
        foreach (array('msn_key', 'google_key', 'oht_id', 'oht_key','transposh_key') as $key) {
            $this->options[$key] = $this->vars[$key]->get_value();
        }
            update_option(TRANSPOSH_OPTIONS, $this->options);
    }

    /**
     * Determine if the given language code is the default language
     * @param string $language
     * @return boolean Is this the default language?
     */
    function is_default_language($language) { // XXXX
        return ($this->default_language == $language || '' == $language);
    }

    /**
     * Determine if the given language in on the list of active languages
     * @return boolean Is this language viewable?
     */
    function is_active_language($language) {
        if ($this->is_default_language($language)) return true;
        return (strpos($this->viewable_languages . ',', $language . ',') !== false);
    }

}

?>