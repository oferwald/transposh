<?php
/*
	Plugin Name: Transposh Translation Filter
	Plugin URI: http://transposh.org/
	Description: Translation filter for WordPress, After enabling please set languages at the <a href="options-general.php?page=transposh">the options page</a> Want to help? visit our development site at <a href="http://trac.transposh.org/">trac.transposh.org</a>.
	Author: Team Transposh
	Version: <%VERSION%>
	Author URI: http://transposh.org/
	License: GPL (http://www.gnu.org/licenses/gpl.txt)
 */

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

require_once("core/parser.php");
require_once("transposh_db.php");
require_once("transposh_widget.php");
require_once("transposh_admin.php");

//Error message displayed for the admin in case of failure
$admin_msg;

/*
 * Called when the buffer containing the original page is flushed. Triggers the
 * translation process.
 */
function process_page(&$buffer) {

    global $wp_query, $rtl_languages, $enable_auto_translate;

    $start_time = microtime(TRUE);

    // Refrain from touching the administrative interface
    if(stripos($_SERVER['REQUEST_URI'],'/wp-admin/') !== FALSE || stripos($_SERVER['REQUEST_URI'],'/xmlrpc.php') !== FALSE) {
        logger("Skipping translation for admin pages", 3);
        return $buffer;
    }

    logger("Translating " . $_SERVER['REQUEST_URI'] . " to: {$GLOBALS['lang']}", 1);
    // Don't translate the default language unless specifically allowed to...
    $default_lang = get_default_lang();
    if($GLOBALS['lang'] == $default_lang && !get_option(ENABLE_DEFAULT_TRANSLATE)) {
        logger("Skipping translation for default language $default_lang", 3);
        return $buffer;
    }

    //translate the entire page
    $parse = new parser();
    $parse->fetch_translate_func = 'fetch_translation';
    $parse->url_rewrite_func = 'rewrite_url';
    $parse->dir_rtl = (in_array ($GLOBALS['lang'], $rtl_languages));
    $parse->lang = $GLOBALS['lang'];
    $parse->is_edit_mode = $GLOBALS['is_edit_mode'];
    $parse->is_auto_translate = $enable_auto_translate;
    if(stripos($_SERVER['REQUEST_URI'],'/feed/') !== FALSE) {
        logger ("in feed!");
        $parse->is_auto_translate = false;
        $parse->is_edit_mode = false;
        $parse->feed_fix = true;
    }
    $buffer = $parse->fix_html($buffer);

    $end_time = microtime(TRUE);
    logger("Translation completed in " . ($end_time - $start_time) . " seconds", 1);

    return $buffer;
}

/*
 * Init global variables later used throughout this process.
 * Note that at the time that this function is called the wp_query is not initialized,
 * which means that query parameters are not accessiable.
 */
function init_global_vars() {
    global $home_url, $tr_plugin_url, $enable_permalinks_rewrite, $wp_rewrite;

    if (!$GLOBALS['home_url'])
        $GLOBALS['home_url'] = get_option('home');
    // Handle windows ('C:\wordpress')
    //plugin_dir_url($file);
    //$local_dir = preg_replace("/\\\\/", "/", dirname(__FILE__));
    // Get last directory name
    //$local_dir = preg_replace("/.*\//", "", $local_dir);
    // TODO - test on more platforms
    $tr_plugin_url= plugins_url('', __FILE__);
    logger("home_url: $home_url, local_dir: $local_dir tr_plugin_url: $tr_plugin_url ".WP_PLUGIN_URL,3);
    //logger(plugins_url('', __FILE__));

    if (is_object($wp_rewrite))
        if($wp_rewrite->using_permalinks() && get_option(ENABLE_PERMALINKS_REWRITE)) {
            $enable_permalinks_rewrite = TRUE;
        }
}

/*
 * Gets the default language setting, i.e. the source language which
 * should not be translated.
 * Return the default language setting
 */
function get_default_lang() {
    global $languages;

    $default = get_option(DEFAULT_LANG);
    if(!$languages[$default]) {
        $default = "en";
    }

    return $default;
}

/*
 * Setup a buffer that will contain the contents of the html page.
 * Once processing is completed the buffer will go into the translation process.
 */
function on_init() {
    logger(__METHOD__ . $_SERVER['REQUEST_URI'], 4);
    init_global_vars();

    if (isset($_POST['translation_posted'])) {
        update_translation();
    }
    elseif (isset($_GET['tr_token_hist'])) {
        get_translation_history($_GET['tr_token_hist'], $_GET['lang']);
    }
    else {
    //set the callback for translating the page when it's done
        ob_start("process_page");
    }
}

/*
 * Page generation completed - flush buffer.
 */
function on_shutdown() {
    ob_flush();
}

/*
 * Update the url rewrite rules to include language identifier
 */
function update_rewrite_rules($rules) {
    logger("Enter update_rewrite_rules");

    if(!get_option(ENABLE_PERMALINKS_REWRITE)) {
        logger("Not touching rewrite rules - permalinks modification disabled by admin");
        return $rules;
    }

    $newRules = array();
    $lang_prefix="([a-z]{2,2}(\-[a-z]{2,2})?)/";

    $lang_parameter= "&" . LANG_PARAM . '=$matches[1]';

    //catch the root url
    $newRules[$lang_prefix."?$"] = "index.php?lang=\$matches[1]";
    logger("\t" . $lang_prefix."?$" . "  --->  " . "index.php?lang=\$matches[1]");

    foreach ($rules as $key=>$value) {
        $original_key = $key;
        $original_value = $value;

        $key = $lang_prefix . $key;

        //Shift existing matches[i] two step forward as we pushed new elements
        //in the beginning of the expression
        for($i = 6; $i > 0; $i--) {
            $value = str_replace('['. $i .']', '['. ($i + 2) .']', $value);
        }

        $value .= $lang_parameter;

        logger("\t" . $key . "  --->  " . $value,4);


        $newRules[$key] = $value;
        $newRules[$original_key] = $original_value;

        logger("\t" . $original_key . "  --->  " . $original_value,4);
    }

    logger("Exit update_rewrite_rules");
    return $newRules;
}

/*
 * Let WordPress know which parameters are of interest to us.
 */
function parameter_queryvars($vars) {
    logger ("inside query vars",5);
    $vars[] = LANG_PARAM;
    $vars[] = EDIT_PARAM;
    logger ($vars,5);
    return $vars;
}

/**
 * Grabs and set the global language and edit params, they should be here
 * @param <type> $wp - here we get the WP class
 */
function on_parse_request($wp) {
    logger ("on_parse_req");
    logger ($wp->query_vars);
    $GLOBALS['lang'] = $wp->query_vars[LANG_PARAM];
    if (!$GLOBALS['lang']) $GLOBALS['lang'] = get_default_lang();
    logger ("global: ".$GLOBALS['lang'],4);
    if (isset($wp->query_vars[EDIT_PARAM]) && $wp->query_vars[EDIT_PARAM] && is_editing_permitted()) {
        $GLOBALS['is_edit_mode'] = true;
    } else {
        $GLOBALS['is_edit_mode'] = false;
    }
    logger ("global-edit: ".$GLOBALS['is_edit_mode'],4);
}

/*
 * Determine if the current user is allowed to translate.
 * Return TRUE if allowed to translate otherwise FALSE
 */
function is_translator() {
    if(is_user_logged_in()) {
        if(current_user_can(TRANSLATOR)) {
            return TRUE;
        }
    }

    if(get_option(ANONYMOUS_TRANSLATION)) {
    //if anonymous translation is allowed - let anyone enjoy it
        return TRUE;
    }

    return FALSE;
}

/*
 * Plugin activated.
 */
function plugin_activate() {
    global $wp_rewrite;
    logger("plugin_activate enter: " . dirname(__FILE__));

    setup_db();

    add_filter('rewrite_rules_array', 'update_rewrite_rules');
    $wp_rewrite->flush_rules();

    logger("plugin_activate exit: " . dirname(__FILE__));
    logger("testing name:".plugin_basename(__FILE__));
    logger("testing name2:".get_plugin_name());
//activate_plugin($plugin);
}

/*
 * Plugin deactivated.
 */
function plugin_deactivate() {
    global $wp_rewrite;
    logger("plugin_deactivate enter: " . dirname(__FILE__));

    remove_filter('rewrite_rules_array', 'update_rewrite_rules');
    $wp_rewrite->flush_rules();

    logger("plugin_deactivate exit: " . dirname(__FILE__));
}

/*
 * Callback from admin_notices - display error message to the admin.
 */
function plugin_install_error() {
    global $admin_msg;
    logger("Enter " . __METHOD__, 0);

    echo '<div class="updated"><p>';
    echo 'Error has occured in the installation process of the translation plugin: <br>';

    echo $admin_msg;

    if (function_exists('deactivate_plugins') ) {
        deactivate_plugins(get_plugin_name(), "translate.php");
        echo '<br> This plugin has been automatically deactivated.';
    }

    echo '</p></div>';
}

/*
 * Callback when all plugins have been loaded. Serves as the location
 * to check that the plugin loaded successfully else trigger notification
 * to the admin and deactivate plugin.
 */
function plugin_loaded() {
    global $admin_msg;
    logger("Enter " . __METHOD__, 4);

    $db_version = get_option(TRANSPOSH_DB_VERSION);

    if ($db_version != DB_VERSION) {
        setup_db();
        //$admin_msg = "Translation database version ($db_version) is not comptabile with this plugin (". DB_VERSION . ")  <br>";

        logger("Updating database in plugin loaded", 0);
    //Some error occured - notify admin and deactivate plugin
    //add_action('admin_notices', 'plugin_install_error');
    }

    if ($db_version != DB_VERSION) {
        $admin_msg = "Failed to locate the translation table  <em> " . TRANSLATIONS_TABLE . "</em> in local database. <br>";

        logger("Messsage to admin: $admin_msg", 0);
        //Some error occured - notify admin and deactivate plugin
        add_action('admin_notices', 'plugin_install_error');
    }
}

/*
 * Gets the plugin name to be used in activation/decativation hooks.
 * Keep only the file name and its containing directory. Don't use the full
 * path as it will break when using symbollic links.
 */
function get_plugin_name() {
    $file = __FILE__;
    $file = str_replace('\\','/',$file); // sanitize for Win32 installs
    $file = preg_replace('|/+|','/', $file); // remove any duplicate slash

    //keep only the file name and its parent directory
    $file = preg_replace('/.*\/([^\/]+\/[^\/]+)$/', '$1', $file);
    logger("Plugin path - $file", 4);
    return $file;
}

/*
 * Add custom css, i.e. transposh.css
 */
function add_transposh_css() {
    global $tr_plugin_url;

    if(!is_editing_permitted() && !is_auto_translate_permitted()) {
    //translation not allowed - no need for the transposh.css
        return;
    }
    //include the transposh.css
    wp_enqueue_style("transposh","$tr_plugin_url/css/transposh.css",array(),'<%VERSION%>');
    wp_enqueue_style("jquery","http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.1/themes/ui-lightness/jquery-ui.css",array(),'1.0');
    logger("Added transposh_css",4);
}

/*
 * Insert references to the javascript files used in the transalted
 * version of the page.
 */
function add_transposh_js() {
    global $tr_plugin_url, $wp_query, $home_url,  $enable_auto_translate, $wp_version;

    $enable_auto_translate = is_auto_translate_permitted();
    if(!is_editing_permitted() && !$enable_auto_translate) {
    //translation not allowed - no need for any js.
        return;
    }

    if (!$GLOBALS['is_edit_mode'] && !$enable_auto_translate) {
    //Not in any translation mode - no need for any js.
        return;
    }

    $options = get_option(WIDGET_TRANSPOSH);

    $edit_mode = "";
    if($GLOBALS['is_edit_mode']) {
        $edit_mode = "&".EDIT_PARAM."=y";
    }

    if($GLOBALS['is_edit_mode'] || $options['progressbar']) {
        wp_enqueue_script("jqueryui","http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js",array("jquery"),'1.7.2',get_option(ENABLE_FOOTER_SCRIPTS));
    }

    if($GLOBALS['is_edit_mode'] || $enable_auto_translate) {
        $post_url = $home_url . '/index.php';
        wp_deregister_script('jquery');
        wp_enqueue_script("jquery","http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js",array(),'1.3.2');
        // jQuery pushing below might cause issues
        //wp_enqueue_script("jquery","http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js",array(),'1.3.2', get_option(ENABLE_FOOTER_SCRIPTS));
        wp_enqueue_script("google","http://www.google.com/jsapi",array(),'1',get_option(ENABLE_FOOTER_SCRIPTS));
        wp_enqueue_script("transposh","$tr_plugin_url/js/transposh.js?post_url=$post_url{$edit_mode}&lang={$GLOBALS['lang']}&prefix=".SPAN_PREFIX,array("jquery"),'<%VERSION%>',get_option(ENABLE_FOOTER_SCRIPTS));
    }
}


/**
 * Determine if the currently selected language (taken from the query parameters) is in the admin's list
 * of editable languages and the current user is allowed to translate.
 *
 * @return TRUE if translation allowed otherwise FALSE
 */
function is_editing_permitted() {
    global $wp_query;
    // editing is permitted for translators only
    if(!is_translator()) return FALSE;
    // and only on the non-default lang (unless strictly specified)
    if (!get_option(ENABLE_DEFAULT_TRANSLATE) && $GLOBALS['lang'] == get_default_lang()) return false;

    return is_editable_lang($GLOBALS['lang']);
}

/**
 * Determine if the given language in on the list of editable languages
 * @return TRUE if editable othewise FALSE
 */
function is_editable_lang($language) {
    $editable_langs = get_option(EDITABLE_LANGS);
    return (strpos($editable_langs, $language) === FALSE) ? FALSE : TRUE;
}


/**
 * Determine if the currently selected language (taken from the query parameters) is in the admin's list
 * of editable languages and that automatic translation has been enabled.
 * Note that any user can auto translate. i.e. ignore permissions.
 *
 * @return TRUE if automatic translation allowed otherwise FALSE
 */
function is_auto_translate_permitted() {
    global $wp_query;
    logger('checking auto translatability');

    if(!get_option(ENABLE_AUTO_TRANSLATE, 1)) return FALSE;

    return is_editable_lang($GLOBALS['lang']);
}
/**
 * Callback from parser allowing to overide the global setting of url rewriting using permalinks.
 * Some urls should be modified only by adding parameters and should be identified by this
 * function.
 * @param $href
 * @return TRUE if parameters should be used instead of rewriting as a permalink
 */
function rewrite_url($href) {
    global $enable_permalinks_rewrite, $home_url;
    $use_params = FALSE;
    logger ("got: $href",5);

    // Ignore urls not from this site
    if(stripos($href, $home_url) === FALSE) {
        return $href;
    }

    // don't fix links pointing to real files as it will cause that the
    // web server will not be able to locate them
    if(stripos($href, '/wp-admin') !== FALSE   ||
        stripos($href, WP_CONTENT_URL) !== FALSE ||
        stripos($href, '/wp-login') !== FALSE   ||
        stripos($href, '/.php') !== FALSE) {
        return $href;
    }
    $use_params = !$enable_permalinks_rewrite;

    $href = rewrite_url_lang_param($href, $GLOBALS['lang'], $GLOBALS['is_edit_mode'], $use_params);
    logger ("rewritten: $href",4);
    return $href;
}

function plugin_action_links( $links ) {
    logger ("in plugin action");
    return array_merge( array('<a href="' . admin_url('options-general.php?page='.TRANSPOSH_ADMIN_PAGE_NAME) . '">Settings</a>'), $links );
}

//Register callbacks
add_filter('query_vars', 'parameter_queryvars' );
add_action('parse_request', 'on_parse_request');
logger (preg_replace( '|^' . preg_quote(WP_PLUGIN_DIR, '|') . '/|', '', __FILE__ ));
add_filter('plugin_action_links_' .preg_replace( '|^' . preg_quote(WP_PLUGIN_DIR, '|') . '/|', '', __FILE__ ), 'plugin_action_links');
add_action('wp_print_styles', 'add_transposh_css');
add_action('wp_print_scripts', 'add_transposh_js');
add_action('init', 'on_init');
add_action('shutdown', 'on_shutdown');

add_action( 'plugins_loaded', 'plugin_loaded');
register_activation_hook(__FILE__, 'plugin_activate');
register_deactivation_hook(__FILE__,'plugin_deactivate');

add_filter('rewrite_rules_array', 'update_rewrite_rules');
?>