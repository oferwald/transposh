<?php

/*  Copyright © 2009-2010 Transposh Team (website : http://transposh.org)
 *
 * 	This program is free software; you can redistribute it and/or modify
 * 	it under the terms of the GNU General Public License as published by
 * 	the Free Software Foundation; either version 2 of the License, or
 * 	(at your option) any later version.
 *
 * 	This program is distributed in the hope that it will be useful,
 * 	but WITHOUT ANY WARRANTY; without even the implied warranty of
 * 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * 	GNU General Public License for more details.
 *
 * 	You should have received a copy of the GNU General Public License
 * 	along with this program; if not, write to the Free Software
 * 	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
// OLD Options - To be removed
// removed real old options support, no migration from 0.3.9 anymore
// @since 0.5.6
//Option defining transposh widget appearance
define("OLD_WIDGET_STYLE", "widget_style");
//Use CSS sprites for flags if available
define("OLD_WIDGET_CSS_FLAGS", "widget_css_flags");
//Wrap widget elements in an unordered list per #63 @since 0.3.7
define("OLD_WIDGET_IN_LIST", "widget_in_list");
//Option to enable/disable msn translation
define("OLD_ENABLE_MSN_TRANSLATE", "enable_msntranslate");
//Option to store the msn API key
define("OLD_MSN_TRANSLATE_KEY", "msn_key");

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
//Option to store translator preference @since 0.4.2
define("PREFERRED_TRANSLATOR", "preferred_translator");
//Option to enable/disable default language translation
define("ENABLE_DEFAULT_TRANSLATE", "enable_default_translate");
//Option to enable/disable default language translation @since 0.3.6
define("ENABLE_SEARCH_TRANSLATE", "enable_search_translate");
//Option to enable/disable url translation @since 0.5.3
define("ENABLE_URL_TRANSLATE", "enable_url_translate");
//Option to enable/disable rewrite of permalinks
define("ENABLE_PERMALINKS", "enable_permalinks");
//Option to enable/disable footer scripts (2.8 and up)
define("ENABLE_FOOTER_SCRIPTS", "enable_footer_scripts");
//Option to enable detect and redirect language @since 0.3.8
define("ENABLE_DETECT_LANG_AND_REDIRECT", "enable_detect_redirect");
//Option defining the default language
define("DEFAULT_LANG", "default_language");
//Option defining transposh widget file used @since 0.5.6
define("WIDGET_FILE", "widget_file");
//Option allowing progress bar display
define("WIDGET_PROGRESSBAR", "widget_progressbar");
//Allows user to set his default language per #63 @since 0.3.8
define("WIDGET_ALLOW_SET_DEFLANG", "widget_allow_set_deflang");
//Allows removing of transposh logo in exchange for an ad @since 0.6.0
define("WIDGET_REMOVE_LOGO_FOR_AD", "widget_remove_logo");
//Stores the site key to transposh services (backup @since 0.5.0)
define("TRANSPOSH_KEY", "transposh_key");
//Stores the site key to transposh services (backup @since 0.5.0)
define("TRANSPOSH_BACKUP_SCHEDULE", "transposh_backup_schedule");

class transposh_plugin_options {
//constructor of class, PHP4 compatible construction for backward compatibility

    /** @var array storing all our options */
    private $options = array();
    /** @var boolean set to true if any option was changed */
    private $changed = false;

    function transposh_plugin_options() {
	logger("creating options");
	// load them here
	$this->options = get_option(TRANSPOSH_OPTIONS);
	$this->migrate_old_config();
	logger($this->options, 4);
    }

    // TODO: remove this function in a few versions (fix css, db version..., css flag
    private function migrate_old_config() {
	logger("in migration");
	if ($this->options[OLD_WIDGET_STYLE]) {
	    if ($this->options[OLD_WIDGET_STYLE] == 1 && $this->options[OLD_WIDGET_CSS_FLAGS] == 0) {
		$this->set_widget_file('flags/tpw_flags.php');
	    }
	    if ($this->options[OLD_WIDGET_STYLE] == 1 && $this->options[OLD_WIDGET_CSS_FLAGS] == 1) {
		$this->set_widget_file('flags/tpw_flags_css.php');
	    }
	    if ($this->options[OLD_WIDGET_STYLE] == 2 && $this->options[OLD_WIDGET_CSS_FLAGS] == 0) {
		$this->set_widget_file('flagslist/tpw_list_with_flags.php');
	    }
	    if ($this->options[OLD_WIDGET_STYLE] == 2 && $this->options[OLD_WIDGET_CSS_FLAGS] == 1) {
		$this->set_widget_file('flagslist/tpw_list_with_flags_css.php');
	    }
	    unset($this->options[OLD_WIDGET_CSS_FLAGS]);
	    unset($this->options[OLD_WIDGET_IN_LIST]);
	    unset($this->options[OLD_WIDGET_STYLE]);
	    unset($this->options[OLD_MSN_TRANSLATE_KEY]);
	    unset($this->options[OLD_ENABLE_MSN_TRANSLATE]);
	    logger($this->options);
	    update_option(TRANSPOSH_OPTIONS, $this->options);
	}
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
     * return file name of the widget used
     * @since 0.5.6
     * @return string
     */
    function get_widget_file() {
	return $this->options[WIDGET_FILE];
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
	// default is true
	if (!isset($this->options[ENABLE_SEARCH_TRANSLATE])) return 1;
	return $this->options[ENABLE_SEARCH_TRANSLATE];
    }

    function get_enable_url_translate() {
	return $this->options[ENABLE_URL_TRANSLATE];
    }

    function get_enable_auto_translate() {
	// default is true
	if (!isset($this->options[ENABLE_AUTO_TRANSLATE])) return 1;
	return $this->options[ENABLE_AUTO_TRANSLATE];
    }

    function get_enable_auto_post_translate() {
	return $this->options[ENABLE_AUTO_POST_TRANSLATE];
    }

    function get_preferred_translator() {
	// default is google(1) (2 is msn)
	if (!isset($this->options[PREFERRED_TRANSLATOR])) return 1;
	return $this->options[PREFERRED_TRANSLATOR];
    }

    /**
     * Gets the default language setting, i.e. the source language which normally should not be translated.
     * @return string Default language
     */
    function get_default_language() {
	$default = $this->options[DEFAULT_LANG];
	if (!transposh_consts::$languages[$default]) {
	    if (defined('WPLANG') && transposh_consts::$languages[WPLANG]) {
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

   function set_widget_remove_logo($val) {
	$val = ($val) ? 1 : 0;
	$this->set_value($val, $this->options[WIDGET_REMOVE_LOGO_FOR_AD]);
    }

    /**
     * Set the widget file used
     * @since 0.5.6
     * @param string $val
     */
    function set_widget_file($val) {
	$this->set_value($val, $this->options[WIDGET_FILE]);
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