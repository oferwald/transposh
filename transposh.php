<?php
/*
Plugin Name: Translation Filter
Plugin URI: http://transposh.org/#
Description: Translation filter for WordPress
Author: amir@transposh.com
Version: 0.1
Author URI: http://transposh.org/
*/

require_once("logging.php");
require_once("constants.php");
require_once("parser.php");
require_once("transposh_widget.php");

//
//Constants
//

//Table name in database for storing translations
define("TRANSLATIONS_TABLE", "translations");

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
    
    $start_time = microtime(true);
    
    if (!isset($wp_query->query_vars[LANG_PARAM]))
    {
        //No language code - avoid further processing.
        return $buffer;
        
    }

    if ($wp_query->query_vars[EDIT_PARAM] == "1" ||
        $wp_query->query_vars[EDIT_PARAM] == "true")
    {
        //TODO verify user has the required permissions
        $is_edit_mode = true;
    }
    

    $lang = $wp_query->query_vars[LANG_PARAM];
    $page = $buffer;

    logger("translating " . $_SERVER['REQUEST_URI'] . " to: $lang", 1);
    
    //translate the entire page
    process_html();
    
    $end_time = microtime(true);

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
    
    //Only params if permalinks is not enabled. 
    //don't fix links pointing to real files as it will cause that the
    //web server will not be able to locate them
    if(!$wp_rewrite->using_permalinks() ||   
       stripos($href, '/wp-content') !== FALSE ||
       stripos($href, '/wp-admin') !== FALSE   ||
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
    
    logger("Enter " . __METHOD__ . " $original", 4);
    if(ENABLE_APC && function_exists('apc_fetch'))
    {
        $cached = apc_fetch($original . $lang, $rc);
        if($rc === TRUE)
        {
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
        //update cache
        $rc = apc_store($original . $lang, $translated, 3600);
        if($rc === TRUE)
        {
            logger("Stored in cache: $original => $translated", 3);
        }
    }
    
    logger("Exit " . __METHOD__ . " $translated", 4);
    return $translated;
}

/*
 * Insert references to the javascript files used in the transalted
 * version of the page.
 *
 */
function insert_javascript_includes()
{
    global $pos, $plugin_url;
    
    $overlib_dir = "$plugin_url/js/overlibmws";
    
    $js = "\n<script type=\"text/javascript\" src=\"$overlib_dir/overlibmws.js\"></script>";
    $js .= "\n<script type=\"text/javascript\" src=\"$overlib_dir/overlibmws_filter.js\"></script>";
    $js .= "\n<script type=\"text/javascript\" src=\"$overlib_dir/overlibmws_modal.js\"></script>";
    $js .= "\n<script type=\"text/javascript\" src=\"$overlib_dir/overlibmws_overtwo.js\"></script>";
    $js .= "\n<script type=\"text/javascript\" src=\"$overlib_dir/overlibmws_scroll.js\"></script>";
    $js .= "\n<script type=\"text/javascript\" src=\"$overlib_dir/overlibmws_shadow.js\"></script>";

    $js .= "\n<script type=\"text/javascript\" src=\"$plugin_url/js/transposh.js\"></script>\n";

    echo $js;
}


/*
 * Return the img tag that will added to enable editing a translatable
 * item on the page. 
 *
 */
function get_img_tag($original, $translation)
{
    global $plugin_url, $lang;

    //For use in javascript, make the following changes: 
    //1. Add slashes to escape the inner text
    //2. Convert the html special characters
    //The browser will take decode step 2 and pass it to the js engine which decode step 1 - a bit tricky
    $translation = htmlspecialchars(addslashes($translation));
        
    $img = "<img src=\"$plugin_url/translate.png\" alt=\"translate\"  
           onclick=\"translate_dialog('$original','$translation','$lang','$home_url'); return false;\" 
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
    
    $plugin_url= $home_url . "/wp-content/plugins/transposh";
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

    //encode text
    $original = $wpdb->escape(htmlspecialchars(urldecode($original)));

    //remove already escaped character to avoid double escaping 
    $translation = $wpdb->escape(stripslashes(urldecode($translation)));
        
    $update = "REPLACE INTO  $table_name (original, translated, lang)
                VALUES ('" . $original . "','" . $translation . "','" . $lang . "')";

    $result = $wpdb->query($update);

    if($result !== FALSE)
    {
        //Delete entry from cache
        if(ENABLE_APC && function_exists('apc_store'))
        {
            apc_delete($original . $lang);
        }
        logger("Inserted to db '$original' , '$translation', '$lang' " , 3);
    }
    else
    {
        logger("Error !!! failed to inserted to db $original , $translation, $lang," , 0);
    }

    wp_redirect($ref);
    exit;
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

    $table_name = $wpdb->prefix . TRANSLATIONS_TABLE;

    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
    {
        logger("Attempting to create table $table_name", 0); 
        $sql = "CREATE TABLE " . $table_name . " (original VARCHAR(256) NOT NULL,
                                                  lang CHAR(5) NOT NULL,
                                                  translated VARCHAR(256),
                                                  PRIMARY KEY (original, lang)) ";
        
                                     
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
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

    logger("Exit " . __METHOD__  );
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


//Register callbacks 
add_action('wp_head', 'add_custom_css');
add_filter('query_vars', 'parameter_queryvars' );

add_action('init', 'on_init');
add_action('shutdown', 'on_shutdown');

add_action( 'plugins_loaded', 'plugin_loaded');
add_action('activate_'.str_replace('\\','/',plugin_basename(__FILE__)),'plugin_activate');
add_action('deactivate_'.str_replace('\\','/',plugin_basename(__FILE__)),'plugin_deactivate');

?>