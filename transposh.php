<?php
/*
	Plugin Name: Transposh Translation Filter
	Plugin URI: http://transposh.org/#
	Description: Translation filter for WordPress, After enabling please set languages at the <a href="options-general.php?page=Transposh">the options page</a> Want to help? visit our development site at <a href="http://trac.transposh.org/">trac.transposh.org</a>.
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
 * Called when the buffer containing the original page is flused. Triggers the
 * translation process.
 */
function process_page(&$buffer) {

	global $wp_query, $tr_page, $page, $pos, $lang, $plugin_url, $is_edit_mode;

	$start_time = microtime(TRUE);

	if (!isset($wp_query->query_vars[LANG_PARAM]))
	{
		//No language code - avoid further processing.
		return $buffer;

	}

	$lang = $wp_query->query_vars[LANG_PARAM];
	$default_lang = get_default_lang();
	if($lang == $default_lang)
	{
		//Don't translate the default language
		logger("Skipping translation for default language $default_lang", 3);
		return $buffer;
	}

	$page = $buffer;

	if (($wp_query->query_vars[EDIT_PARAM] == "1" || $wp_query->query_vars[EDIT_PARAM] == "true") &&
	     is_editing_permitted())
	{
		$is_edit_mode = TRUE;
	}

	logger("translating " . $_SERVER['REQUEST_URI'] . " to: $lang", 1);

	//translate the entire page
	process_html();

	$end_time = microtime(TRUE);

	logger("Translation completed in " . ($end_time - $start_time) . " seconds", 1);

	//return the translated page unless it is empty, othewise return the original
	return (strlen($tr_page) > 0 ? $tr_page : $page);
}

/*
 * Init global variables later used throughout this process.
 * Note that at the time that this function is called the wp_query is not initialized,
 * which means that query parameters are not accessiable.
 */
function init_global_vars()
{
	global $home_url, $home_url_quoted, $plugin_url, $enable_permalinks_rewrite, $wp_rewrite;

	$home_url = get_option('home');
	$local_dir = preg_replace("/.*\//", "", dirname(__FILE__));

	$plugin_url= $home_url . "/wp-content/plugins/$local_dir";
	$home_url_quoted = preg_quote($home_url);
	$home_url_quoted = preg_replace("/\//", "\\/", $home_url_quoted);

	if($wp_rewrite->using_permalinks() && get_option(ENABLE_PERMALINKS_REWRITE))
	{
		$enable_permalinks_rewrite = TRUE;
	}
}

/*
 * Gets the default language setting, i.e. the source language which
 * should not be translated.
 * Return the default language setting
 */
function get_default_lang()
{
	global $languages;

	$default = get_option(DEFAULT_LANG);
	if(!$languages[$default])
	{
		$default = "en";
	}

	return $default;
}

/*
 * Setup a buffer that will contain the contents of the html page.
 * Once processing is completed the buffer will go into the translation process.
 */
function on_init()
{
	logger(__METHOD__ . $_SERVER['REQUEST_URI']);
	init_global_vars();

	if ($_POST['translation_posted'])
	{
		update_translation();
	}
	elseif ($_GET['tr_token_hist']) {
		get_translation_history($_GET['tr_token_hist'], $_GET['lang']);
	}
	elseif ($_GET['tp_gif']) {
		$trans_gif_64 = "R0lGODlhAQABAIAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==";
		header("Content-type: image/gif");
		print(base64_decode($trans_gif_64));
		exit;
	}
	else
	{
		//set the callback for translating the page when it's done
		ob_start("process_page");
	}
}

/*
 * Page generation completed - flush buffer.
 */
function on_shutdown()
{
	ob_flush();
}

/*
 * Update the url rewrite rules to include language identifier
 */
function update_rewrite_rules($rules){
	logger("Enter update_rewrite_rules");

	if(!get_option(ENABLE_PERMALINKS_REWRITE))
	{
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
		for($i = 6; $i > 0; $i--)
		{
			$value = str_replace('['. $i .']', '['. ($i + 2) .']', $value);
		}

		$value .= $lang_parameter;

		logger("\t" . $key . "  --->  " . $value);


		$newRules[$key] = $value;
		$newRules[$original_key] = $original_value;

		logger("\t" . $original_key . "  --->  " . $original_value);
	}

	logger("Exit update_rewrite_rules");
	return $newRules;
}

/*
 * Let WordPress which parameters are of interest to us.
 */
function parameter_queryvars($qvars)
{
	$qvars[] = LANG_PARAM;
	$qvars[] = EDIT_PARAM;

	return $qvars;
}

/*
 * Determine if the current user is allowed to translate.
 * Return TRUE if allowed to translate otherwise FALSE
 */
function is_translator()
{
	if(is_user_logged_in())
	{
		if(current_user_can(TRANSLATOR))
		{
			return TRUE;
		}
	}

	if(get_option(ANONYMOUS_TRANSLATION))
	{
		//if anonymous translation is allowed - let anyone enjoy it
		return TRUE;
	}

	return FALSE;
}

/*
 * Plugin activated.
 */
function plugin_activate()
{
	global $wp_rewrite;
	logger("plugin_activate enter: " . dirname(__FILE__));

	setup_db();

	add_filter('rewrite_rules_array', 'update_rewrite_rules');
	$wp_rewrite->flush_rules();

	logger("plugin_activate exit: " . dirname(__FILE__));
}

/*
 * Plugin deactivated.
 */
function plugin_deactivate(){
	global $wp_rewrite;
	logger("plugin_deactivate enter: " . dirname(__FILE__));

	remove_filter('rewrite_rules_array', 'update_rewrite_rules');
	$wp_rewrite->flush_rules();

	logger("plugin_deactivate exit: " . dirname(__FILE__));
}

/*
 * Callback from admin_notices - display error message to the admin.
 */
function plugin_install_error()
{
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
function plugin_loaded()
{
	global $admin_msg;
	logger("Enter " . __METHOD__, 4);

	$db_version = get_option(TRANSPOSH_DB_VERSION);

	if ($db_version != DB_VERSION)
	{
		setup_db();
		//$admin_msg = "Translation database version ($db_version) is not comptabile with this plugin (". DB_VERSION . ")  <br>";

		logger("Updating database in plugin loaded", 0);
		//Some error occured - notify admin and deactivate plugin
		//add_action('admin_notices', 'plugin_install_error');
	}

	if ($db_version != DB_VERSION)
	{
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
function get_plugin_name()
{
	$file = __FILE__;
	$file = str_replace('\\','/',$file); // sanitize for Win32 installs
	$file = preg_replace('|/+|','/', $file); // remove any duplicate slash

	//keep only the file name and its parent directory
	$file = preg_replace('/.*\/([^\/]+\/[^\/]+)$/', '$1', $file);
	logger("Plugin path $file", 3);
	return $file;
}

/*
 * Add custom css, i.e. transposh.css
 */
function add_transposh_css() {
	global $plugin_url;

	if(!is_editing_permitted() && !is_auto_translate_permitted())
	{
		//translation not allowed - no need for the transposh.css
		return;
	}
	//include the transposh.css
	wp_enqueue_style("transposh","$plugin_url/css/transposh.css",array(),'1.0.1');
	wp_enqueue_style("jquery","http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.1/themes/ui-lightness/jquery-ui.css",array(),'1.0.1');
	logger("Added transposh_css");
}

/*
 * Insert references to the javascript files used in the transalted
 * version of the page.
 */
function add_transposh_js() {
	global $plugin_url, $wp_query, $lang, $home_url,  $enable_auto_translate;

	$enable_auto_translate = is_auto_translate_permitted();
	if(!is_editing_permitted() && !$enable_auto_translate)
	{
		//translation not allowed - no need for any js.
		return;
	}

	$is_edit_param_enabled = $wp_query->query_vars[EDIT_PARAM];

	if (!$is_edit_param_enabled && !$enable_auto_translate)
	{
		//Not in any translation mode - no need for any js.
		return;
	}

	//$overlib_dir = "$plugin_url/js/overlibmws";

	if($is_edit_param_enabled)
	{
		/*wp_enqueue_script("overlibmws","$overlib_dir/overlibmws.js",array(),'1.0');
		wp_enqueue_script("overlibmws1","$overlib_dir/overlibmws_filter.js",array("overlibmws"),'1.0');
		wp_enqueue_script("overlibmws2","$overlib_dir/overlibmws_modal.js",array("overlibmws1"),'1.0');
		wp_enqueue_script("overlibmws3","$overlib_dir/overlibmws_overtwo.js",array("overlibmws2"),'1.0');
		wp_enqueue_script("overlibmws4","$overlib_dir/overlibmws_scroll.js",array("overlibmws3"),'1.0');
		wp_enqueue_script("overlibmws5","$overlib_dir/overlibmws_shadow.js",array("overlibmws4"),'1.0');*/
		wp_enqueue_script("jqueryui","http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.1/jquery-ui.min.js",array("jquery"),'1.7.1');
		$edit_mode = "&edit=y";
	}

	if($is_edit_param_enabled || $enable_auto_translate)
	{
		$post_url = $home_url . '/index.php';
		wp_deregister_script('jquery');
		wp_enqueue_script("jquery","http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js",array(),'1.3.2');
		wp_enqueue_script("google","http://www.google.com/jsapi",array(),'1');
		wp_enqueue_script("transposh","$plugin_url/js/transposh.js?post_url=$post_url{$edit_mode}&lang={$lang}&prefix=".SPAN_PREFIX,array("jquery"),'1.0');
	}
}


/**
 * Determine if the currently selected language (taken from the query parameters) is in the admin's list
 * of editable languages and the current user is allowed to translate.
 *
 * @return TRUE if translation allowed otherwise FALSE
 */
function is_editing_permitted()
{
	global $wp_query;

	if(!is_translator())
	{
		return FALSE;
	}

	if (!isset($wp_query->query_vars[LANG_PARAM]))
	{
		return FALSE;
	}

	$lang = $wp_query->query_vars[LANG_PARAM];
	return is_editable_lang($lang);
}

/**
 * Determine if the given language in on the list of editable languages
 * @return TRUE if editable othewise FALSE
 */
function is_editable_lang($lang)
{
	$editable_langs = get_option(EDITABLE_LANGS);

	if(strpos($editable_langs, $lang) === FALSE)
	{
		//not an editable language
		return FALSE;
	}

	return TRUE;
}


/**
 * Determine if the currently selected language (taken from the query parameters) is in the admin's list
 * of editable languages and that automatic translation has been enabled.
 * Note that any user can auto translate. i.e. ignore permissions.
 *
 * @return TRUE if automatic translation allowed otherwise FALSE
 */
function is_auto_translate_permitted()
{
	global $wp_query;

	if(!get_option(ENABLE_AUTO_TRANSLATE, 1))
	{
		return FALSE;
	}

	if (!isset($wp_query->query_vars[LANG_PARAM]))
	{
		return FALSE;
	}

	$lang = $wp_query->query_vars[LANG_PARAM];
	return is_editable_lang($lang);
}
/**
 * Callback from parser allowing to overide the global setting of url rewriting using permalinks.
 * Some urls should be modified only by adding parameters and should be identified by this
 * function.
 * @param $href
 * @return TRUE if parameters should be used instead of rewriting as a permalink
 */
function is_url_excluded_from_permalink_rewrite($href)
{
	$use_params = FALSE;

	//don't fix links pointing to real files as it will cause that the
	//web server will not be able to locate them
	if(stripos($href, '/wp-admin') !== FALSE   ||
	   stripos($href, '/wp-content') !== FALSE ||
	   stripos($href, '/wp-login') !== FALSE   ||
	   stripos($href, '/.php') !== FALSE)
	{
		$use_params = TRUE;
	}

	return $use_params;
}

//Register callbacks
add_filter('query_vars', 'parameter_queryvars' );
add_action('wp_print_styles', 'add_transposh_css');
add_action('wp_print_scripts', 'add_transposh_js');

add_action('init', 'on_init');
add_action('shutdown', 'on_shutdown');

add_action( 'plugins_loaded', 'plugin_loaded');
register_activation_hook(get_plugin_name(), 'plugin_activate');
register_deactivation_hook(get_plugin_name(),'plugin_deactivate');

add_filter('rewrite_rules_array', 'update_rewrite_rules');
?>