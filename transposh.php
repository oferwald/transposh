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

require_once("logging.php");
require_once("constants.php");
require_once("parser.php");
require_once("transposh_widget.php");
require_once("transposh_admin.php");

//
//Constants
//

//Table name in database for storing translations
define("TRANSLATIONS_TABLE", "translations");
define("TRANSLATIONS_LOG", "translations_log");

//Database version
define("DB_VERSION", "1.0");

//Constant used as key in options database
define("TRANSPOSH_DB_VERSION", "transposh_db_version");

//
// Global variables
//

//The full table name, i.e. prefix + name
$table_name;

//Home url of the blog
$home_url;

//Home url of the blog - already quoted and ready for regex
$home_url_quoted;

//The url pointing to the base of the plugin
$plugin_url;

//Error message displayed for the admin in case of failure
$admin_msg;


/*
 * Called when the buffer containing the original page is flused. Triggers the
 * translation process.
 *
 */
function process_page(&$buffer) {

    global $wp_query, $tr_page, $page, $pos, $lang, $plugin_url, $is_edit_mode, $wpdb,
           $table_name;

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


    if (($wp_query->query_vars[EDIT_PARAM] == "1" ||
         $wp_query->query_vars[EDIT_PARAM] == "true"))
    {
        //Verify that the current language is editable and that the
        //user has the required permissions
        $editable_langs = get_option(EDITABLE_LANGS);

        if(is_translator() && strstr($editable_langs, $lang))
        {
            $is_edit_mode = TRUE;
        }

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
 * Fix links on the page. href needs to be modified to include
 * lang specifier and editing mode.
 *
 */
function process_anchor_tag($start, $end)
{
    global $home_url, $home_url_quoted, $lang, $is_edit_mode, $wp_rewrite;

    $href = get_attribute($start, $end, 'href');

    if($href == NULL)
    {
        return;
    }

    //Ignore urls not from this site
    if(stripos($href, $home_url) === FALSE)
    {
        return;
    }

    $use_params = FALSE;

    //Only use params if permalinks are not enabled.
    //don't fix links pointing to real files as it will cause that the
    //web server will not be able to locate them
    if(!$wp_rewrite->using_permalinks() ||
       stripos($href, '/wp-admin') !== FALSE   ||
       stripos($href, '/wp-content') !== FALSE ||
       stripos($href, '/wp-login') !== FALSE   ||
       stripos($href, '/.php') !== FALSE)
    {
        $use_params = TRUE;
    }

    $href = rewrite_url_lang_param($href, $lang, $is_edit_mode, $use_params);

    //rewrite url in translated page
    update_translated_page($start, $end, $href);
    logger(__METHOD__ . " $home_url href: $href");
}


/*
 * Update the given url to include language params.
 * param url - the original url to rewrite
 * param lang - language code
 * param is_edit - is running in edit mode.
 * param use_params_only - use only parameters as modifiers, i.e. not permalinks
 */
function rewrite_url_lang_param($url, $lang, $is_edit, $use_params_only)
{
    global $home_url, $home_url_quoted;

    if(!get_option(ENABLE_PERMALINKS_REWRITE))
    {
        //override the use only params - admin configured system to not touch permalinks
        $use_params_only = TRUE;
    }

    if($is_edit)
    {
        $params = EDIT_PARAM . '=1&';

    }

    if($use_params_only)
    {
        $params .= LANG_PARAM . "=$lang&";
    }
    else
    {
        $url = preg_replace("/$home_url_quoted\/(..\/)?\/?/",
                                 "$home_url/$lang/",  $url);
    }

    if($params)
    {
        //insert params to url
        $url = preg_replace("/(.+\/[^\?\#]*[\?]?)/", '$1?' . $params, $url);

        //Cleanup extra &
        $url = preg_replace("/&&+/", "&", $url);

            //Cleanup extra ?
        $url = preg_replace("/\?\?+/", "?", $url);
    }

    return $url;
}

/*
 * Fetch translation from db or cache.
 * Returns the translated string or NULL if not available.
 */
function fetch_translation($original)
{
    global $wpdb, $lang, $table_name;
    $translated = NULL;

    logger("Enter " . __METHOD__ . ": $original", 4);
    if(ENABLE_APC && function_exists('apc_fetch'))
    {
        $cached = apc_fetch($original . $lang, $rc);
        if($rc === TRUE)
        {
    		logger("Exit from cache " . __METHOD__ . ": $cached", 4);
        	return $cached;
        }
    }

    $query = "SELECT * FROM $table_name WHERE original = '$original' and lang = '$lang' ";
    $row = $wpdb->get_row($query);

    if($row !== FALSE)
    {
        $translated = $row->translated;
        $translated = stripslashes($translated);

        logger("db result for $original >>> $translated ($lang)" , 3);
    }


    if(ENABLE_APC && function_exists('apc_store'))
    {
        //If we don't have translation still we want to have it in cache
        $cache_entry = $translated;
        if($cache_entry == NULL)
        {
            $cache_entry = "";
        }

        //update cache
        $rc = apc_store($original . $lang, $cache_entry, 3600);
        if($rc === TRUE)
        {
            logger("Stored in cache: $original => $translated", 3);
        }
    }

    logger("Exit " . __METHOD__ . ": $translated", 4);
    return $translated;
}

/*
 * Insert references to the javascript files used in the transalted
 * version of the page.
 *
 */
function insert_javascript_includes()
{
    global $plugin_url, $wp_query;

    if (!($wp_query->query_vars[EDIT_PARAM] == "1" ||
         $wp_query->query_vars[EDIT_PARAM] == "true"))
    {
        //check permission later - for now just make sure we don't load the
        //js code when it is not needed
        return;
    }
    

    $overlib_dir = "$plugin_url/js/overlibmws";

    $js = "\n<script type=\"text/javascript\" src=\"$overlib_dir/overlibmws.js\"></script>";
    $js .= "\n<script type=\"text/javascript\" src=\"$overlib_dir/overlibmws_filter.js\"></script>";
    $js .= "\n<script type=\"text/javascript\" src=\"$overlib_dir/overlibmws_modal.js\"></script>";
    $js .= "\n<script type=\"text/javascript\" src=\"$overlib_dir/overlibmws_overtwo.js\"></script>";
    $js .= "\n<script type=\"text/javascript\" src=\"$overlib_dir/overlibmws_scroll.js\"></script>";
    $js .= "\n<script type=\"text/javascript\" src=\"$overlib_dir/overlibmws_shadow.js\"></script>";

    $js .= "\n<script type=\"text/javascript\" src=\"$plugin_url/js/transposh.js\"></script>\n";
    $js .= "\n<script type=\"text/javascript\" src=\"http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js\"></script>\n";
    $js .= "\n<script type=\"text/javascript\" src=\"http://www.google.com/jsapi\"></script>\n";
    $js .= "\n<script type=\"text/javascript\">google.load(\"language\", \"1\");</script>\n";

    echo $js;
}


/*
 * Return the img tag that will added to enable editing a translatable
 * item on the page.
 * param segement_id The id (number) identifying this segment. Needs to be
         placed within the img tag for use on client side operation (jquery)
 */
function get_img_tag($original, $translation, $segment_id, $is_translated = FALSE)
{
    global $plugin_url, $lang, $home_url;
    $url = $home_url . '/index.php';

    //For use in javascript, make the following changes:
    //1. Add slashes to escape the inner text
    //2. Convert the html special characters
    //The browser will take decode step 2 and pass it to the js engine which decode step 1 - a bit tricky
    $translation = htmlspecialchars(addslashes($translation));
    $original    = htmlspecialchars(addslashes($original));

    if ($is_translated)
    {
        $add_img = "_fix";
    }

    $img = "<img src=\"$plugin_url/translate$add_img.png\" alt=\"translate\" id=\"" . IMG_PREFIX . "$segment_id\"
           onclick=\"translate_dialog('$original','$translation','$lang','$url', '$segment_id'); return false;\"
           onmouseover=\"hint('$original'); return true;\"
           onmouseout=\"nd()\" />";

    return $img;
}


/*
 * Add custom css, i.e. transposh.css
 *
 */
function add_custom_css()
{
    transposh_css();
    insert_javascript_includes();
}

// We need some CSS to position the paragraph
function transposh_css()
{
    global $plugin_url, $wp_query;

    if (!isset($wp_query->query_vars[LANG_PARAM]))
    {
        return;
    }

    //include the transposh.css
	echo "<link rel=\"stylesheet\" href=\"$plugin_url/transposh.css\" type=\"text/css\" />";

	logger("Added transposh_css");
}

/*
 * Init global variables later used throughout this process
 */
function init_global_vars()
{
    global $home_url, $home_url_quoted, $plugin_url, $table_name, $wpdb;

    $home_url = get_option('home');
    $local_dir = preg_replace("/.*\//", "", dirname(__FILE__));

    $plugin_url= $home_url . "/wp-content/plugins/$local_dir";
    $home_url_quoted = preg_quote($home_url);
    $home_url_quoted = preg_replace("/\//", "\\/", $home_url_quoted);

    $table_name = $wpdb->prefix . TRANSLATIONS_TABLE;
}


/*
 * A new translation has been posted, update the translation database.
 *
 */
function update_translation()
{
    global $wpdb, $table_name;

    $ref=getenv('HTTP_REFERER');
    $original = $_POST['original'];
    $translation = $_POST['translation'];
    $lang = $_POST['lang'];

    if(!isset($original) || !isset($translation) || !isset($lang))
    {
        logger("Enter " . __FILE__ . " missing params: $original , $translation, $lang," .
               $ref, 0);
        return;
    }

    //Check that use is allowed to translate
    if(!is_translator())
    {
        logger("Unauthorized translation attempt " . $_SERVER['REMOTE_ADDR'] , 1);
    }

    //Decode & remove already escaped character to avoid double escaping
    $original    = $wpdb->escape(stripslashes(urldecode($original)));
    $translation = $wpdb->escape(htmlspecialchars(stripslashes(urldecode($translation))));

    $update = "REPLACE INTO  $table_name (original, translated, lang)
                VALUES ('" . $original . "','" . $translation . "','" . $lang . "')";

    $result = $wpdb->query($update);

    if($result !== FALSE)
    {
        update_transaction_log($original, $translation, $lang);

        //Delete entry from cache
        if(ENABLE_APC && function_exists('apc_store'))
        {
            apc_delete($original . $lang);
        }
        logger("Inserted to db '$original' , '$translation', '$lang' " , 3);
    }
    else
    {
        logger("Error !!! failed to insert to db $original , $translation, $lang," , 0);
        header("HTTP/1.0 404 Failed to update language database");
    }

    exit;
}


/*
 * Update the transaction log
 *
 */
function update_transaction_log(&$original, &$translation, &$lang)
{
	global $wpdb, $user_ID;
	get_currentuserinfo();

	// log either the user ID or his IP
	if ('' == $user_ID)
    {
		$loguser = $_SERVER['REMOTE_ADDR'];
	}
    else
    {
		$loguser = $user_ID;
	}

    $log = "INSERT INTO ".$wpdb->prefix.TRANSLATIONS_LOG." (original, translated, lang, translated_by)
                VALUES ('" . $original . "','" . $translation . "','" . $lang . "','".$loguser."')";

    $result = $wpdb->query($log);

    if($result === FALSE)
    {
        logger("Error !!! failed to update transaction log:  $loguser, $original ,$translation, $lang" , 0);
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
 *
 */
function update_rewrite_rules($rules){
    logger("Enter update_rewrite_rules");

    if(!get_option(ENABLE_PERMALINKS_REWRITE))
    {
        logger("Not touching rewrite rules - permalinks modification disabled by admin");
        return $rule;
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
 * Setup the translation database.
 *
 */
function setup_db()
{

    global $wpdb;
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $table_name = $wpdb->prefix . TRANSLATIONS_TABLE;

    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
    {
        logger("Attempting to create table $table_name", 0);
        $sql = "CREATE TABLE " . $table_name . " (original VARCHAR(256) NOT NULL,
                                                  lang CHAR(5) NOT NULL,
                                                  translated VARCHAR(256),
                                                  PRIMARY KEY (original, lang)) ";


        dbDelta($sql);

        //Verify that newly created table is ready for use.
        $insert = "INSERT INTO " . $table_name . " (original, translated, lang) " .
        "VALUES ('Hello','Hi There','zz')";

        $result = $wpdb->query($insert);

        if($result === FALSE)
        {
            logger("Error failed to create $table_name !!!", 0);
        }
        else
        {
            logger("Table $table_name was created successfuly", 0);
            add_option(TRANSPOSH_DB_VERSION, DB_VERSION);
        }
    }

    $table_name = $wpdb->prefix . TRANSLATIONS_LOG;

    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
    {
        logger("Attempting to create table $table_name", 0);
        $sql = "CREATE TABLE " . $table_name . " (original VARCHAR(256) NOT NULL,
                                                  lang CHAR(5) NOT NULL,
                                                  translated VARCHAR(256),
                                                  translated_by VARCHAR(15),
                                                  timestamp TIMESTAMP,
                                                  PRIMARY KEY (original, lang, timestamp)) ";


        dbDelta($sql);
    }

    logger("Exit " . __METHOD__  );
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
 *
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
 *
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
 *
 */
function plugin_install_error()
{
    global $admin_msg;
    logger("Enter " . __METHOD__, 0);

    echo '<div class="updated"><p>';
    echo 'Error has occured in the installation process of the translation plugin: <br>';

    echo $admin_msg;

    if (function_exists('deactivate_plugins') ) {
        deactivate_plugins("transposh/translate.php", "translate.php");
        echo '<br> This plugin has been automatically deactivated.';
    }

    echo '</p></div>';
}


/*
 * Callback when all plugins have been loaded. Serves as the location
 * to check that the plugin loaded successfully else trigger notification
 * to the admin and deactivate plugin.
 *
 */
function plugin_loaded()
{
    global $admin_msg;
    logger("Enter " . __METHOD__, 3);

    if (get_option(TRANSPOSH_DB_VERSION) == NULL)
    {
        $admin_msg = "Failed to locate the translation table  <em> " . TRANSLATIONS_TABLE . "</em> in local database. <br>";

        logger("Messsage to admin: $admin_msg", 0);
        //Some error occured - notify admin and deactivate plugin
        add_action('admin_notices', 'plugin_install_error');
    }

    $db_version = get_option(TRANSPOSH_DB_VERSION);

    if ($db_version != DB_VERSION)
    {
        $admin_msg = "Translation database version ($db_version) is not comptabile with this plugin (". DB_VERSION . ")  <br>";

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
	$file = preg_replace('/.*(\/[^\/]+\/[^\/]+)$/', '$1', $file);
	logger("Plugin path $file", 3);
	return $file;
}

//Register callbacks
add_action('wp_head', 'add_custom_css');
add_filter('query_vars', 'parameter_queryvars' );

add_action('init', 'on_init');
add_action('shutdown', 'on_shutdown');

add_action( 'plugins_loaded', 'plugin_loaded');
register_activation_hook(get_plugin_name(), 'plugin_activate');
register_deactivation_hook(get_plugin_name(),'plugin_deactivate');
?>