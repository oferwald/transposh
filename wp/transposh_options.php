<?php
/*  Copyright © 2009-2010 Transposh Team (website : http://transposh.org)
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
// OLD Options - To be removed
//Option defining whether anonymous translation is allowed.
define("OLD_ANONYMOUS_TRANSLATION", "transposh_allow_anonymous_translation");
//Option defining the list of currentlly viewable languages
define("OLD_VIEWABLE_LANGS", "transposh_viewable_languages");
//Option defining the list of currentlly editable languages
define("OLD_EDITABLE_LANGS", "transposh_editable_languages");
//Option to enable/disable auto translation
define("OLD_ENABLE_AUTO_TRANSLATE", "transposh_enable_autotranslate");
//Option to enable/disable msn translation
define("OLD_ENABLE_MSN_TRANSLATE", "transposh_enable_msntranslate");
//Option to store the msn API key
define("OLD_MSN_TRANSLATE_KEY", "transposh_msn_key");
//Option to enable/disable rewrite of permalinks
define("OLD_ENABLE_PERMALINKS_REWRITE", "transposh_enable_permalinks");
//Option to enable/disable default language translation
define("OLD_ENABLE_DEFAULT_TRANSLATE", "transposh_enable_default_translate");
//Option to enable/disable footer scripts (2.8 and up)
define("OLD_ENABLE_FOOTER_SCRIPTS", "transposh_enable_footer_scripts");
//Use CSS sprites for flags if available
define("OLD_ENABLE_CSS_FLAGS", "transposh_enable_css_flags");
//Option defining the default language
define("OLD_DEFAULT_LANG", "transposh_default_language");
//Option defining transposh widget appearance
define("OLD_WIDGET_TRANSPOSH", "transposh_widget");
//Option to enable/disable footer scripts (2.8 and up) -- @deprecated 0.3.9
define("OLD_ALTERNATE_POST", "alternate_post_method");

//defines are used to avoid typos
//Option defining whether anonymous translation is allowed.
define("ANONYMOUS_TRANSLATION", "allow_anonymous_translation");
//Option defining the list of currentlly viewable languages
define("VIEWABLE_LANGS", "viewable_languages");
//Option defining the list of currentlly editable languages
define("EDITABLE_LANGS", "editable_languages");
//Option defining the ordered list of languages @since 0.3.9
define("SORTED_LANGS", "sorted_languages");
//Option to enable/disable auto translation
define("ENABLE_AUTO_TRANSLATE", "enable_autotranslate");
//Option to enable/disable auto translation
define("ENABLE_AUTO_POST_TRANSLATE", "enable_autoposttranslate");
//Option to enable/disable msn translation
define("ENABLE_MSN_TRANSLATE", "enable_msntranslate");
//Option to store the msn API key
define("MSN_TRANSLATE_KEY", "msn_key");
//Option to store translator preference @since 0.4.2
define("PREFERRED_TRANSLATOR", "preferred_translator");
//Option to enable/disable default language translation
define("ENABLE_DEFAULT_TRANSLATE", "enable_default_translate");
//Option to enable/disable default language translation @since 0.3.6
define("ENABLE_SEARCH_TRANSLATE", "enable_search_translate");
//Option to enable/disable rewrite of permalinks
define("ENABLE_PERMALINKS", "enable_permalinks");
//Option to enable/disable footer scripts (2.8 and up)
define("ENABLE_FOOTER_SCRIPTS", "enable_footer_scripts");
//Option to enable detect and redirect language @since 0.3.8
define("ENABLE_DETECT_LANG_AND_REDIRECT", "enable_detect_redirect");
//Option defining the default language
define("DEFAULT_LANG", "default_language");
//Option defining transposh widget appearance
define("WIDGET_STYLE", "widget_style");
//Option allowing progress bar display
define("WIDGET_PROGRESSBAR", "widget_progressbar");
//Use CSS sprites for flags if available
define("WIDGET_CSS_FLAGS", "widget_css_flags");
//Wrap widget elements in an unordered list per #63 @since 0.3.7
define("WIDGET_IN_LIST", "widget_in_list");
//Allows user to set his default language per #63 @since 0.3.8
define("WIDGET_ALLOW_SET_DEFLANG", "widget_allow_set_deflang");
//Stores the site key to transposh services (backup @since 0.5.0)
define("TRANSPOSH_KEY","transposh_key");
//Stores the site key to transposh services (backup @since 0.5.0)
define("TRANSPOSH_BACKUP_SCHEDULE","transposh_backup_schedule");


class transposh_plugin_options {
//constructor of class, PHP4 compatible construction for backward compatibility
    /** @var array storing all our options */
    private $options = array();
    /** @var boolean set to true if any option was changed */
    private $changed = false;

    function transposh_plugin_options() {
        logger ("creating options");
        // load them here
        $this->options = get_option(TRANSPOSH_OPTIONS);
        $this->migrate_old_config();
        logger($this->options,4);
    }

    private function old2new($key) {
        // the substr removes the redundant transposh_ prefix
        $this->options[substr($key,10)] = get_option($key);
        delete_option($key);
    }
    // TODO: remove this function in a few versions (fix css, db version..., css flag
    private function migrate_old_config() {
        logger ("in migration");
        if (get_option(OLD_ENABLE_AUTO_TRANSLATE,666) != 666 || get_option(OLD_WIDGET_TRANSPOSH,666) != 666) {
            logger ("old options exist - converting");
            $this->old2new(OLD_ANONYMOUS_TRANSLATION);
            $this->old2new(OLD_VIEWABLE_LANGS);
            $this->old2new(OLD_EDITABLE_LANGS);
            $this->old2new(OLD_ENABLE_AUTO_TRANSLATE);
            $this->old2new(OLD_ENABLE_MSN_TRANSLATE);
            $this->old2new(OLD_MSN_TRANSLATE_KEY);
            $this->old2new(OLD_ENABLE_PERMALINKS_REWRITE);
            $this->old2new(OLD_ENABLE_DEFAULT_TRANSLATE);
            $this->old2new(OLD_ENABLE_FOOTER_SCRIPTS);
            $this->old2new(OLD_ENABLE_CSS_FLAGS);
            $this->old2new(OLD_DEFAULT_LANG);
            $this->old2new(OLD_WIDGET_TRANSPOSH);
            logger($this->options);
            update_option(TRANSPOSH_OPTIONS, $this->options);
        }
        //some options were moved
        logger($this->options,4);
        if (isset($this->options['widget'])) {
            logger ('isset');
            $this->set_widget_style($this->options['widget']['style']);
            $this->set_widget_progressbar($this->options['widget']['progressbar']);
            unset($this->options['widget']);
            logger($this->options);
        }
        if (is_array($this->options) && array_key_exists('enable_css_flags',$this->options)) {
            $this->set_widget_css_flags($this->options['enable_css_flags']);
            unset($this->options['enable_css_flags']);
            logger($this->options);
        }
        // this has deprecated at 0.3.9
        unset ($this->options[OLD_ALTERNATE_POST]);
    }

    function get_anonymous_translation() {
        if (!isset($this->options[ANONYMOUS_TRANSLATION])) return 1; // default is true
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
            return array_merge(array_flip(explode(",",$this->options[SORTED_LANGS])),$GLOBALS['languages']);
        return $GLOBALS['languages'];
    }

    function get_widget_progressbar() {
        return $this->options[WIDGET_PROGRESSBAR];
    }

    function get_widget_style() {
        return $this->options[WIDGET_STYLE];
    }

    function get_widget_css_flags() {
        return $this->options[WIDGET_CSS_FLAGS];
    }

    function get_widget_in_list() {
        return $this->options[WIDGET_IN_LIST];
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

    function get_enable_msn_translate() {
        return $this->options[ENABLE_MSN_TRANSLATE]; // FIX
    }

    function get_enable_default_translate() {
        return $this->options[ENABLE_DEFAULT_TRANSLATE];
    }

    function get_enable_search_translate() {
        if (!isset($this->options[ENABLE_SEARCH_TRANSLATE])) return 1; // default is true
        return $this->options[ENABLE_SEARCH_TRANSLATE];
    }

    function get_enable_auto_translate() {
        if (!isset($this->options[ENABLE_AUTO_TRANSLATE])) return 1; // default is true
        return $this->options[ENABLE_AUTO_TRANSLATE];
    }

    function get_enable_auto_post_translate() {
        return $this->options[ENABLE_AUTO_POST_TRANSLATE];
    }

    function get_msn_key() {
        return $this->options[MSN_TRANSLATE_KEY];
    }

    function get_preferred_translator() {
        if (!isset($this->options[PREFERRED_TRANSLATOR])) return 1; // default is google (2 is msn)
        return $this->options[PREFERRED_TRANSLATOR];
    }

    /**
     * Gets the default language setting, i.e. the source language which normally should not be translated.
     * @return string Default language
     */
    function get_default_language() {
        $default = $this->options[DEFAULT_LANG];
        if(!$GLOBALS['languages'][$default]) {
            if (defined('WPLANG') && $GLOBALS['languages'][WPLANG]) {
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

    function set_widget_style($val) {
        $this->set_value($val, $this->options[WIDGET_STYLE]);
    }

    function set_widget_css_flags($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[WIDGET_CSS_FLAGS]);
    }

    function set_widget_in_list($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[WIDGET_IN_LIST]);
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
        $this->set_value($val,$this->options[ENABLE_DETECT_LANG_AND_REDIRECT]);
    }

    function set_enable_footer_scripts($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_FOOTER_SCRIPTS]);
    }

    function set_enable_msn_translate($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_MSN_TRANSLATE]); // FIX
    }

    function set_enable_default_translate($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_DEFAULT_TRANSLATE]);
    }

    function set_enable_search_translate($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_SEARCH_TRANSLATE]);
    }

    function set_enable_auto_translate($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_AUTO_TRANSLATE]);
    }

    function set_enable_auto_post_translate($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_AUTO_POST_TRANSLATE]);
    }

    function set_msn_key($val) {
        $this->set_value($val, $this->options[MSN_TRANSLATE_KEY]);
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
        if(!$GLOBALS['languages'][$val]) {
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

    /**
     * Updates options at the wordpress options table if there was a change
     */
    function update_options() {
        if ($this->changed) {
            update_option(TRANSPOSH_OPTIONS, $this->options);
        }
        else {
            logger ("no changes and no updates done");
        }
        $this->changed = false;
    }

    /**
     * Determine if the given language code is the default language
     * @param string $language
     * @return boolean Is this the default language?
     */
    function is_default_language($language) {
        return ($this->get_default_language() == $language);
    }

    /**
     * Determine if the given language in on the list of editable languages
     * @return boolean Is this language editable?
     */
    function is_editable_language($language) {
        return (strpos($this->get_editable_langs(), $language) !== false);
    }

    /**
     * Determine if the given language in on the list of viewable languages
     * @return boolean Is this language viewable?
     */
    function is_viewable_language($language) {
        return (strpos($this->get_viewable_langs(), $language) !== false);
    }

}
?>