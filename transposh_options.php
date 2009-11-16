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

//defines are used to avoid typos
//Option defining whether anonymous translation is allowed.
define("ANONYMOUS_TRANSLATION", "allow_anonymous_translation");
//Option defining the list of currentlly viewable languages
define("VIEWABLE_LANGS", "viewable_languages");
//Option defining the list of currentlly editable languages
define("EDITABLE_LANGS", "editable_languages");
//Option to enable/disable auto translation
define("ENABLE_AUTO_TRANSLATE", "enable_autotranslate");
//Option to enable/disable msn translation
define("ENABLE_MSN_TRANSLATE", "enable_msntranslate");
//Option to store the msn API key
define("MSN_TRANSLATE_KEY", "msn_key");
//Option to enable/disable rewrite of permalinks
define("ENABLE_PERMALINKS", "enable_permalinks");
//Option to enable/disable default language translation
define("ENABLE_DEFAULT_TRANSLATE", "enable_default_translate");
//Option to enable/disable footer scripts (2.8 and up)
define("ENABLE_FOOTER_SCRIPTS", "enable_footer_scripts");
//Use CSS sprites for flags if available
define("ENABLE_CSS_FLAGS", "enable_css_flags");
//Option defining the default language
define("DEFAULT_LANG", "default_language");
//Option defining transposh widget appearance
define("WIDGET_TRANSPOSH", "widget");
define("WIDGET_STYLE", "style");
define("WIDGET_PROGRESSBAR", "progressbar");


class transposh_plugin_options {
//constructor of class, PHP4 compatible construction for backward compatibility
    private $options; // array storing all our options
    private $changed = false;

    function transposh_plugin_options() {
        logger ("creating options");
        $this->migrate_old_config();
        // load them here
        $this->options = get_option(TRANSPOSH_OPTIONS);
        logger($this->options);
    }

    private function old2new($key) {
        // the substr removes the redundant transposh_ prefix
        $this->options[substr($key,10)] = get_option($key);
        delete_option($key);
    }
    // TODO: remove this function in a few versions (fix css, db version..., css flag
    private function migrate_old_config() {
        logger ("in migration");
        if (get_option(OLD_ENABLE_AUTO_TRANSLATE,666) != 666) {
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
        //    $this->options[SPAN_PREFIX] =
        //    $this->options[ENABLE_CSS_FLAGS] =

    }

    function get_anonymous_translation() {
        return $this->options[ANONYMOUS_TRANSLATION];
    }

    function get_viewable_langs() {
        // logger (__LINE__.": vlangs :".$this->options[VIEWABLE_LANGS].VIEWABLE_LANGS);
        return $this->options[VIEWABLE_LANGS];
    }

    function get_editable_langs() {
        return $this->options[EDITABLE_LANGS];
    }

    function get_widget_progressbar() {
        return $this->options[WIDGET_TRANSPOSH][WIDGET_PROGRESSBAR];
    }

    function get_widget_style() {
        //logger ("widgetstyle".$this->options[WIDGET_TRANSPOSH][WIDGET_STYLE]);
        return $this->options[WIDGET_TRANSPOSH][WIDGET_STYLE];
    }

    function get_widget_css_flags() { // FIX!
        return $this->options[ENABLE_CSS_FLAGS];
    }

    function get_enable_permalinks() {
        return $this->options[ENABLE_PERMALINKS];
    }

    function get_enable_footer_scripts() {
        return $this->options[ENABLE_FOOTER_SCRIPTS];
    }

    function get_enable_msn_translate() {
        return $this->options[ENABLE_MSN_TRANSLATE]; // FIX
    }

    function get_enable_default_translate() {
        return $this->options[ENABLE_DEFAULT_TRANSLATE];
    }

    function get_enable_auto_translate() {
        if (!isset($this->options[ENABLE_AUTO_TRANSLATE])) return 1; // default is true
        return $this->options[ENABLE_AUTO_TRANSLATE];
    }

    function get_msn_key() {
        return $this->options[MSN_TRANSLATE_KEY];
    }

/*
 * Gets the default language setting, i.e. the source language which
 * should not be translated.
 * Return the default language setting
 */
    function get_default_language() {
        $default = $this->options[DEFAULT_LANG];
        if(!$GLOBALS['languages'][$default]) {
            $default = "en";
        }
        return $default;
    }

    private function set_value($val, &$option) {
        if ($val != $option) {
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

    function set_widget_progressbar($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[WIDGET_TRANSPOSH][WIDGET_PROGRESSBAR]);
    }

    function set_widget_style($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[WIDGET_TRANSPOSH][WIDGET_STYLE]);
    }

    function set_widget_css_flags($val) { // FIX!
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_CSS_FLAGS]);
    }

    function set_enable_permalinks($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_PERMALINKS]);
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

    function set_enable_auto_translate($val) {
        $val = ($val) ? 1 : 0;
        $this->set_value($val, $this->options[ENABLE_AUTO_TRANSLATE]);
    }

    function set_msn_key($val) {
        $this->set_value($val, $this->options[MSN_TRANSLATE_KEY]);
    }

/*
 * Gets the default language setting, i.e. the source language which
 * should not be translated.
 * Return the default language setting
 */
    function set_default_language($val) {
        if(!$GLOBALS['languages'][$val]) {
            $val = "en";
        }
        $this->set_value($val, $this->options[DEFAULT_LANG]);
    }

    function update_options() {
        if ($this->changed) {
            update_option(TRANSPOSH_OPTIONS, $this->options);
        }
        else {
            logger (__METHOD__." no changes and no updates done");
        }
        $this->changed = false;
    }

    /**
     * Determine if the given language code is currentlly the default language
     * @param <type> $language
     * @return <type>
     */
    function is_default_language($language) {
        return ($this->get_default_language() == $language);
    }

    /**
     * Determine if the given language in on the list of editable languages
     * @return TRUE if editable othewise FALSE
     */
    function is_editable_language($language) {
        return (strpos($this->get_editable_langs(), $language) !== false);
    }

    /**
     * Determine if the given language in on the list of viewable languages
     * @return TRUE if editable othewise FALSE
     */
    function is_viewable_language($language) {
        return (strpos($this->get_viewable_langs(), $language) !== false);
    }

}
?>