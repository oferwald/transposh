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

// OLD Options - To be removed
// removed real old options support, no migration from 0.3.9 anymore
// @since 0.5.6
//Option defining transposh widget appearance
define('OLD_WIDGET_STYLE', 'widget_style');
//Use CSS sprites for flags if available
define('OLD_WIDGET_CSS_FLAGS', 'widget_css_flags');
//Wrap widget elements in an unordered list per #63 @since 0.3.7
define('OLD_WIDGET_IN_LIST', 'widget_in_list');
//Option to enable/disable msn translation
define('OLD_ENABLE_MSN_TRANSLATE', 'enable_msntranslate');
//Option defining transposh widget file used @since 0.5.6
define('OLD_WIDGET_FILE', 'widget_file'); //unset!!!
//Option to store the msn API key
define('MSN_TRANSLATE_KEY', 'msn_key');
//Option to store the msn API key
define('GOOGLE_TRANSLATE_KEY', 'google_key');
//Option to store the msn API key
define('OHT_TRANSLATE_ID', 'oht_id');
//Option to store the msn API key
define('OHT_TRANSLATE_KEY', 'oht_key');

//defines are used to avoid typos
//Option defining whether anonymous translation is allowed.
define('ANONYMOUS_TRANSLATION', 'allow_anonymous_translation');
//Option defining the list of currentlly viewable languages
define('VIEWABLE_LANGS', 'viewable_languages');
//Option defining the list of currentlly editable languages
define('EDITABLE_LANGS', 'editable_languages');
//Option defining the ordered list of languages @since 0.3.9
define('SORTED_LANGS', 'sorted_languages');
//Option to enable/disable auto translation
define('ENABLE_AUTO_TRANSLATE', 'enable_autotranslate');
//Option to enable/disable auto translation
define('ENABLE_AUTO_POST_TRANSLATE', 'enable_autoposttranslate');
//Option to store translator preference @since 0.4.2
define('PREFERRED_TRANSLATOR', 'preferred_translator');
//Option to enable/disable default language translation
define('ENABLE_DEFAULT_TRANSLATE', 'enable_default_translate');
//Option to enable/disable default language translation @since 0.3.6
define('ENABLE_SEARCH_TRANSLATE', 'enable_search_translate');
//Option to enable/disable url translation @since 0.5.3
define('ENABLE_URL_TRANSLATE', 'enable_url_translate');
//Make the gettext interface optional (@since 0.6.4)
define('TRANSPOSH_GETTEXT_INTEGRATION', 'transposh_gettext_integration');
//Allow override for default locale (@since 0.7.5)
define('TRANSPOSH_DEFAULT_LOCALE_OVERRIDE', 'transposh_locale_override');
//Option to enable/disable rewrite of permalinks
define('ENABLE_PERMALINKS', 'enable_permalinks');
//Option to enable/disable footer scripts (2.8 and up)
define('ENABLE_FOOTER_SCRIPTS', 'enable_footer_scripts');
//Option to enable detect and redirect language @since 0.3.8
define('ENABLE_DETECT_LANG_AND_REDIRECT', 'enable_detect_redirect');
//Option defining the default language
define('DEFAULT_LANG', 'default_language');
//Option allowing progress bar display
define('WIDGET_PROGRESSBAR', 'widget_progressbar');
//Allows user to set his default language per #63 @since 0.3.8
define('WIDGET_ALLOW_SET_DEFLANG', 'widget_allow_set_deflang');
//Allows removing of transposh logo in exchange for an ad @since 0.6.0
define('WIDGET_REMOVE_LOGO_FOR_AD', 'widget_remove_logo');
//Allows theming of the progressbar and edit window @since 0.7.0
define('WIDGET_THEME', 'widget_theme');
//Stores the site key to transposh services (backup @since 0.5.0)
define('TRANSPOSH_KEY', 'transposh_key');
//Stores the site key to transposh services (backup @since 0.5.0)
define('TRANSPOSH_BACKUP_SCHEDULE', 'transposh_backup_schedule');
//Stores hidden warnings (@since 0.7.6)
define('TRANSPOSH_ADMIN_HIDE_WARNINGS', 'transposh_admin_hide_warnings');
//Should I allow collecting of anonymous stats (@since 0.7.6)
define('TRANSPOSH_COLLECT_STATS', 'transposh_admin_hide_warnings');

class transposh_plugin_options {

    /** @var array storing all our options */
    private $options = array();

    /** @var boolean set to true if any option was changed */
    private $changed = false;

    function set_default_option_value($option, $value = '') {
        if (!isset($this->options[$option])) $this->options[$option] = $value;
    }

    function transposh_plugin_options() {
        logger("creating options");
        // load them here
        $this->options = get_option(TRANSPOSH_OPTIONS);
        $this->set_default_option_value(ANONYMOUS_TRANSLATION, 1);
        $this->set_default_option_value(ENABLE_SEARCH_TRANSLATE, 1);
        $this->set_default_option_value(ENABLE_AUTO_TRANSLATE, 1);
        $this->set_default_option_value(PREFERRED_TRANSLATOR, 1);
        $this->set_default_option_value(TRANSPOSH_GETTEXT_INTEGRATION, 1);
        $this->set_default_option_value(TRANSPOSH_DEFAULT_LOCALE_OVERRIDE, 1);
        $this->set_default_option_value(VIEWABLE_LANGS);
        $this->set_default_option_value(EDITABLE_LANGS);
        //$this->set_default_option_value(SORTED_LANGS);
        $this->set_default_option_value(ENABLE_AUTO_POST_TRANSLATE);
        $this->set_default_option_value(ENABLE_DEFAULT_TRANSLATE);
        $this->set_default_option_value(ENABLE_SEARCH_TRANSLATE);
        $this->set_default_option_value(ENABLE_URL_TRANSLATE);
        $this->set_default_option_value(ENABLE_PERMALINKS);
        $this->set_default_option_value(ENABLE_FOOTER_SCRIPTS);
        $this->set_default_option_value(ENABLE_DETECT_LANG_AND_REDIRECT);
        $this->set_default_option_value(DEFAULT_LANG);
        $this->set_default_option_value(WIDGET_PROGRESSBAR);
        $this->set_default_option_value(WIDGET_ALLOW_SET_DEFLANG);
        $this->set_default_option_value(WIDGET_REMOVE_LOGO_FOR_AD);
        $this->set_default_option_value(WIDGET_THEME, 'ui-lightness');
        $this->set_default_option_value(MSN_TRANSLATE_KEY);
        $this->set_default_option_value(GOOGLE_TRANSLATE_KEY);
        $this->set_default_option_value(OHT_TRANSLATE_ID);
        $this->set_default_option_value(OHT_TRANSLATE_KEY);
        $this->set_default_option_value(TRANSPOSH_KEY);
        $this->set_default_option_value(TRANSPOSH_BACKUP_SCHEDULE, 2);
        $this->set_default_option_value(TRANSPOSH_ADMIN_HIDE_WARNINGS);
        $this->set_default_option_value(TRANSPOSH_COLLECT_STATS, 1);
        $this->migrate_old_config();
        logger($this->options, 4);
    }

    // TODO: remove this function in a few versions (fix css, db version..., css flag
    private function migrate_old_config() {
        logger("in migration");
        if (isset($this->options[OLD_WIDGET_STYLE])) {          
            unset($this->options[OLD_WIDGET_CSS_FLAGS]);
            unset($this->options[OLD_WIDGET_IN_LIST]);
            unset($this->options[OLD_WIDGET_STYLE]);
            unset($this->options[OLD_ENABLE_MSN_TRANSLATE]);
            logger($this->options);
            update_option(TRANSPOSH_OPTIONS, $this->options);
        }
    }

    function get_anonymous_translation() {
        return $this->options[ANONYMOUS_TRANSLATION];
    }

    function get_viewable_langs() {
        return $this->options[VIEWABLE_LANGS];
    }

    function get_editable_langs() {
        return $this->options[EDITABLE_LANGS];
    }

    /**
     * Get a user sorted language list
     * @since 0.3.9
     * @return array sorted list of languages, pointing to names and flags
     */
    function get_sorted_langs() {
        if (isset($this->options[SORTED_LANGS]))
                return array_merge(array_flip(explode(",", $this->options[SORTED_LANGS])), transposh_consts::$languages);
        return transposh_consts::$languages;
    }

    function get_widget_progressbar() {
        return $this->options[WIDGET_PROGRESSBAR];
    }

    function get_widget_remove_logo() {
        return $this->options[WIDGET_REMOVE_LOGO_FOR_AD];
    }

    /**
     * return theme
     * @since 0.7.0
     * @return string 
     */
    function get_widget_theme() {
        return $this->options[WIDGET_THEME];
    }

    function get_widget_allow_set_default_language() {
        return $this->options[WIDGET_ALLOW_SET_DEFLANG];
    }

    function get_enable_permalinks() {
        return $this->options[ENABLE_PERMALINKS];
    }

    function get_enable_footer_scripts() {
        return $this->options[ENABLE_FOOTER_SCRIPTS];
    }

    function get_enable_detect_language() {
        return $this->options[ENABLE_DETECT_LANG_AND_REDIRECT];
    }

    function get_enable_default_translate() {
        return $this->options[ENABLE_DEFAULT_TRANSLATE];
    }

    function get_enable_search_translate() {
        return $this->options[ENABLE_SEARCH_TRANSLATE];
    }

    function get_enable_url_translate() {
        return $this->options[ENABLE_URL_TRANSLATE];
    }

    function get_enable_auto_translate() {
        // default is true
        return $this->options[ENABLE_AUTO_TRANSLATE];
    }

    function get_msn_key() {
        return $this->options[MSN_TRANSLATE_KEY];
    }

    function get_google_key() {
        return $this->options[GOOGLE_TRANSLATE_KEY];
    }

    function get_oht_id() {
        return $this->options[OHT_TRANSLATE_ID];
    }

    function get_oht_key() {
        return $this->options[OHT_TRANSLATE_KEY];
    }

    function get_enable_auto_post_translate() {
        return $this->options[ENABLE_AUTO_POST_TRANSLATE];
    }

    function get_preferred_translator() {
        // default is google(1) (2 is msn)
        return $this->options[PREFERRED_TRANSLATOR];
    }

    /**
     * Gets the default language setting, i.e. the source language which normally should not be translated.
     * @return string Default language
     */
    function get_default_language() {
        $default = $this->options[DEFAULT_LANG];
        if (!isset(transposh_consts::$languages[$default])) {
            if (defined('WPLANG') && isset(transposh_consts::$languages[WPLANG])) {
                $default = WPLANG;
            } else {
                $default = "en";
            }
        }
        return $default;
    }

    function get_transposh_key() {
        return $this->options[TRANSPOSH_KEY];
    }

    function get_transposh_backup_schedule() {
        return $this->options[TRANSPOSH_BACKUP_SCHEDULE];
    }

    function get_transposh_gettext_integration() {
        return $this->options[TRANSPOSH_GETTEXT_INTEGRATION];
    }

    function get_transposh_default_locale_override() {
        return $this->options[TRANSPOSH_DEFAULT_LOCALE_OVERRIDE];
    }

    function get_transposh_admin_hide_warning($id) {
        return strpos($this->options[TRANSPOSH_ADMIN_HIDE_WARNINGS], $id . ',') !== false;
    }

    function get_transposh_collect_stats() {
        return $this->options[TRANSPOSH_COLLECT_STATS];
    }

    /**
     * Sets a value at the options array
     * @param mixed $val
     * @param pointer $option Points to the option in the options array
     */
    private function set_value($val, &$option) {
        if ($val !== $option) {
            $option = $val;
            $this->changed = true;
        }
    }

    function set_anonymous_translation($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ANONYMOUS_TRANSLATION]);
    }

    function set_viewable_langs($val) {
        $this->set_value($val, $this->options[VIEWABLE_LANGS]);
    }

    function set_editable_langs($val) {
        $this->set_value($val, $this->options[EDITABLE_LANGS]);
    }

    function set_sorted_langs($val) {
        $this->set_value($val, $this->options[SORTED_LANGS]);
    }

    function set_widget_progressbar($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[WIDGET_PROGRESSBAR]);
    }

    function set_widget_remove_logo($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[WIDGET_REMOVE_LOGO_FOR_AD]);
    }

    /**
     * Set the widget theme
     * @since 0.7.0
     * @param string $val
     */
    function set_widget_theme($val) {
        $this->set_value($val, $this->options[WIDGET_THEME]);
    }

    function set_widget_allow_set_default_language($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[WIDGET_ALLOW_SET_DEFLANG]);
    }

    function set_enable_permalinks($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_PERMALINKS]);
    }

    function set_enable_detect_language($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_DETECT_LANG_AND_REDIRECT]);
    }

    function set_enable_footer_scripts($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_FOOTER_SCRIPTS]);
    }

    function set_enable_default_translate($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_DEFAULT_TRANSLATE]);
    }

    function set_enable_search_translate($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_SEARCH_TRANSLATE]);
    }

    function set_enable_url_translate($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_URL_TRANSLATE]);
    }

    function set_enable_auto_translate($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_AUTO_TRANSLATE]);
    }

    function set_msn_key($val) {
        $this->set_value($val, $this->options[MSN_TRANSLATE_KEY]);
    }

    function set_google_key($val) {
        $this->set_value($val, $this->options[GOOGLE_TRANSLATE_KEY]);
    }

    function set_oht_id($val) {
        $this->set_value($val, $this->options[OHT_TRANSLATE_ID]);
    }

    function set_oht_key($val) {
        $this->set_value($val, $this->options[OHT_TRANSLATE_KEY]);
    }

    function set_enable_auto_post_translate($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_AUTO_POST_TRANSLATE]);
    }

    function set_preferred_translator($val) {
        $this->set_value($val, $this->options[PREFERRED_TRANSLATOR]);
    }

    /**
     * Sets the default language setting, i.e. the source language which
     * should not be translated.
     * @param string $val Language set as default
     */
    function set_default_language($val) {
        if (!transposh_consts::$languages[$val]) {
            $val = "en";
        }
        $this->set_value($val, $this->options[DEFAULT_LANG]);
    }

    function set_transposh_key($val) {
        $this->set_value($val, $this->options[TRANSPOSH_KEY]);
    }

    function set_transposh_backup_schedule($val) {
        $this->set_value($val, $this->options[TRANSPOSH_BACKUP_SCHEDULE]);
    }

    function set_transposh_gettext_integration($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[TRANSPOSH_GETTEXT_INTEGRATION]);
    }

    function set_transposh_default_locale_override($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[TRANSPOSH_DEFAULT_LOCALE_OVERRIDE]);
    }

    function set_transposh_admin_hide_warning($id) {
        if (!$this->get_transposh_admin_hide_warning($id)) {
            $this->set_value($this->options[TRANSPOSH_ADMIN_HIDE_WARNINGS] . $id . ',', $this->options[TRANSPOSH_ADMIN_HIDE_WARNINGS]);
        }
    }

    function set_transposh_collect_stats($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[TRANSPOSH_COLLECT_STATS]);
    }

    /**
     * Updates options at the wordpress options table if there was a change
     */
    function update_options() {
        if ($this->changed) {
            update_option(TRANSPOSH_OPTIONS, $this->options);
        } else {
            logger("no changes and no updates done");
        }
        $this->changed = false;
    }

    /**
     * Determine if the given language code is the default language
     * @param string $language
     * @return boolean Is this the default language?
     */
    function is_default_language($language) {
        return ($this->get_default_language() == $language || '' == $language);
    }

    /**
     * Determine if the given language in on the list of editable languages
     * @return boolean Is this language editable?
     */
    function is_editable_language($language) {
        if ($this->is_default_language($language)) return true;
        return (strpos($this->get_editable_langs().',', $language.',') !== false);
    }

    /**
     * Determine if the given language in on the list of viewable languages
     * @return boolean Is this language viewable?
     */
    function is_viewable_language($language) {
        if ($this->is_default_language($language)) return true;
        return (strpos($this->get_viewable_langs().',', $language.',') !== false);
    }

}

?>