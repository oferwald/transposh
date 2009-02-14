<?php
/*
Plugin Name: Translation Filter
Plugin URI: http://transposh.com/#
Description: Translation filter for WordPress
Author: Amir Perlman
Version: 0.1
Author URI: http://transposh.com/
*/


require("logging.php");

//For debug purpose - mark translatable string on the page, i.e. << Hello >>
define ("MARK_TRANSLATABLE", 0);

//
//URL parameters
//

//Language indicator in URL. i.e. lang=en
define("LANG_PARAM", "lang");


//The url pointing to the base of the plugin
$plugin_url;

//The language to which the current page will be translated to. 
$lang;
 
//
//Global variables
//

//The html page which starts contains the content being translated
$page;
 
//Marks the current position of the translation process within the page
$pos = 0;

//Contains the stack of tag in the current position within the page
$tags_list = array();

//The translated html page 
$tr_page;
 
//Points to the last character that have been copied from the original to the translated page. 
$tr_mark = 0;

//Sqlite database name
$db_name = dirname(__FILE__) . "/transposh.sqlite";

//The reference to the database
$db;

//Is the current use is in edit mode. TODO: Get the information at runtime.
$is_edit_mode = true;
                             
//Error message displayed for the admin in case of failure
$admin_msg;
                             
/*
 * Called when the buffer containing the original page is flused. Triggers the
 * translation process.
 * 
 */ 
function translate_page(&$buffer) {
    
    global $wp_query, $tr_page, $page, $pos, $lang, $plugin_url;
    
    $start_time = microtime(true);
    
    if (!isset($wp_query->query_vars[LANG_PARAM]))
    {
        //No language code - avoid further processing.
        return $buffer;
        
    }
    
    if(!open_db())
    {
        //Can't open the database - avoid further processing 
        return $buffer;
    }

    $lang = $wp_query->query_vars[LANG_PARAM];
    $page = $buffer;
    logger("translating " . $_SERVER['REQUEST_URI'] . " to: $lang", 1);
    
    //translate the entire page
    process_html();
    
    $end_time = microtime(true);

    logger("Translation completed in " . ($end_time - $start_time) . " seconds", 1);
    
    //return the translated page
    return $tr_page;
}

/*
 * Open the database, returns false in case of failure.
 */
function open_db()
{
    global $db, $db_name;
    
    try
    {
        $db = new SQLiteDatabase($db_name);
        logger("Opened database" . $db_name, 4);

    }
    catch(Exception $exception)
    {
        logger("error !!! " . $exception->getMessage(), 0);
        return false;
    }

    return true;
}

/*
 * Parse the html page into tags, identify translateable string which
 * will be translated. 
 */
function process_html()
{
    logger("Enter " . __METHOD__, 4);

    global $page, $tr_page, $pos, $tags_list, $lang;
    
    while($pos < strlen($page))
    {
        //find beginning of next tag
        $pos = strpos($page, '<', $pos);
        if($pos === false)
        {
            //logger("Error finding < in pos " . $pos . " page: " . $page);
            break;
        }
        $pos++;

        //Get the element identifying this tag
        $element = get_element();
                
        //skip to end of tag
        if($element == "!DOCTYPE")
        {
            $pos = strpos($page, '>', $pos);
        }
        else if($element == "!--")
        {
            $pos = strpos($page, '-->', $pos);
        }
        else
        {
            //skip to the '>' marking the end of the element
            $pos = strpos($page, '>', $pos);
            if($page[$pos-1] == '/')
            {
                //single line tag - no need to update tags list
                process_tag_termination($element);
            }
            else if($element[0] != '/') 
            {
                $tags_list[] = $element;
                process_tag_init($element);
            }
            else
            {
                array_pop($tags_list);
                process_tag_termination($element);
            }

            //logger("position $pos, tags:" . implode(",", $tags_list));

            $pos++;
            process_current_tag();
        }
    }

    if(strlen($tr_page) > 0)
    {
        //Some translation has been taken place. Complete the translated
        //page up to the full contents of the original page.
        update_translated_page(strlen($page), -1, "");
    }
    else
    {
        $tr_page = &$page;
    }
    
    
    logger("Exit " . __METHOD__, 4);
}


/*
 * Process tag init.
 * Note: The current position in buffer points to the '>' character
 */
function process_tag_init(&$element)
{
    global $pos, $page;
    
    logger(__METHOD__ . " $element" . $page[$pos], 4);
}

/*
 * Process tag termination.
 * Note: The current position in buffer points to the '>' character
 */
function process_tag_termination(&$element)
{
    global $pos, $tags_list, $page;
    
    logger(__METHOD__ . " $element ". $page[$pos], 4);
}


/*
 * Return the element id within the current tag. 
 */
function get_element()
{
    logger("Enter " . __METHOD__, 5);
    global $page, $pos;
    
    skip_white_space();
    
    $start = $pos;
    
    //keep scanning till the first white space or the '>' mark
    while($pos < strlen($page) && $page[$pos] != ' '&&
          $page[$pos] != '>' && $page[$pos] != '\t')
    {
        $pos++;
    }
    
    logger("Exit " . __METHOD__, 5);
    return substr($page,$start, $pos - $start);
}

/*
 * Attempt to process the content of the tag (if exists). If the current
 * is of a type that need translation then translate, otherwise skip.
 *
 */
function process_current_tag()
{
    global $page, $pos, $tags_list;

    $current_tag = end($tags_list);
    logger("Enter " . __METHOD__  ." : $current_tag", 4);

    //translate only specific elements - <a> or <p>
    if($current_tag == 'a' || array_search('div', $tags_list))
    {
        
        skip_white_space();
        $start = $pos;

        while($pos < strlen($page) && $page[$pos] != '<')
        {
            //will break translation unit when one of the following characters is reached: .!?
            //Note: handles the case of multi termination chars, e.g. !!! and also
            //identifies decimal point e.g. 2.0 which should not break a sentence
            if(($page[$pos] == '.' || $page[$pos] == '!' || $page[$pos] == '?') &&
               ($page[$pos+1] != '.' && $page[$pos+1] != '!' && $page[$pos+1] != '?' &&
                ($page[$pos+1] < '0' || $page[$pos+1] > '9')))
             {
                 $pos++;

                 extract_string($start);
                 $start = $pos;
             }
             else
             {
                 $pos++;
             }
        }
        
        if($pos > $start)
        {
            extract_string($start);
        }
    }
    logger("Exit" .  __METHOD__ . " : $current_tag" , 4);
}

/*
 * Skip forward within buffer past the white spaces starting from the
 * specified position. Going either backward of forward. 
 */
function skip_white_space(&$index, $forward=true)
{
    global $page, $pos;

    if(!isset($index))
    {
        //use $pos as the default position if not specified otherwise
        $index = &$pos;
    }

    while($index < strlen($page) && $index > 0 &&
          ($page[$index] ==  " " || $page[$index] ==  ""    ||
           $page[$index] == "\t" || $page[$index] == "\r"   ||
           $page[$index] == "\n" || $page[$index] == "\x0B" ||
           $page[$index] == "\0"))
    {
        ($forward ? $index++ : $index--);
    }

    return $index;
}

/**
 * Extract the text between the given start position and the current
 * position (pos) within the buffer. 
 */
function extract_string($start)
{
    logger("Enter " . __METHOD__  . " : $start", 4);
    global $page, $pos, $is_edit_mode;

    //trim white space from the start position going forward
    skip_white_space($start);

    //Set the end position of the string to one back from current position
    //(i.e. current position points to '<') and trim white space from the right
    //backwards
    $end = $pos - 1;
    $end = skip_white_space($end, $forward=false);
    
    if($start >= $end)
    {
        //empty string - nothing to do
        return;
    }

    $original_text = substr($page, $start, $end - $start + 1);

    //skip strings like without any readable characters (i.e. ".")
    //Todo: need a broader defintion for non-ascii characters as well
    if(preg_match("/^[.?!|\(\)\[\],0-9]+$/", $original_text))
    {
        return;
    }

    //replace multi space chars with a single space
    $original_text = preg_replace("/\s\s+/", " ", $original_text);

    $translated_text = fetch_translation($original_text);

    if($translated_text != NULL)
    {
        logger("$original_text translated to $translated_text");
        update_translated_page($start, $end, $translated_text);
    }

    if($is_edit_mode)
    {
        $img = get_img_tag($original_text, $translated_text);
        update_translated_page($pos, - 1, $img);
    }
    
    logger("Exit " . __METHOD__  . " : $original_text" , 4);
}

/*
 * Translate a single string.
 * Returns the translated string or NULL if not available.
 */
function fetch_translation($original)
{
    global $db, $lang;
    $translated = NULL;
    
    logger("Enter " . __METHOD__ . " $original", 4);
    
    try
    {
        
        $result = $db->query("SELECT * FROM phrases WHERE original = '$original' and lang = '$lang' "); 
        
        if($result != NULL && $result->valid())
        {
            $row = $result->current();
            $translated = $row['translated'];
            
            logger("db result for $original >>> $translated ($lang)" , 3);
        }
    }
    catch(Exception $exception)
    {
        logger("Exception !!!! " . $exception->getMessage(), 0);
        return NULL;
    }
    
    
    //Mark all translatable string on page
    if(MARK_TRANSLATABLE)
    {
        if($translated)
        {
            $translated = "$translated";
        }
        else
        {
            $translated = "&raquo $original &laquo";
        }
    }
    
    logger("Exit " . __METHOD__ . " $translated", 4);
    return $translated;
}


/**
 * Insert a translated text to the translated page.
 * Currentlly assume that we always insert and move forward - not moving
 * back in buffer.
 * param start - marks the starting position of the replaced string in the original page.
 * param end - marks the end position of the replaced string in the original page. Use -1 to do insert instead of replace.
 * param translated_text - text to be inserted.
 */
function update_translated_page($start, $end, $translated_text)
{
    global $page, $tr_page, $tr_mark;
    
    //Bring the translated up to date up to the start position.
    while($tr_mark < $start)
    {
        $tr_page .= $page[$tr_mark++];
    }

    $tr_page .= $translated_text;

    if($end > $start)
    {
        //Move mark to correlate the posistion between the two pages.
        //Only do this when some content has been replaced, i.e. not
        //an insert.
        $tr_mark = $end + 1;
    }
    
}


/*
 * Insert references to the javascript files used in the transalted
 * version of the page.
 *
 */
function insert_javascript_includes()
{
    global $pos, $plugin_url;
    
    $oberlib_dir = "$plugin_url/js/oberlibmws";
    
    $js = "\n<script type=\"text/javascript\" src=\"$oberlib_dir/overlibmws.js\"></script>";
    $js .= "\n<script type=\"text/javascript\" src=\"$oberlib_dir/overlibmws_filter.js\"></script>";
    $js .= "\n<script type=\"text/javascript\" src=\"$oberlib_dir/overlibmws_modal.js\"></script>";
    $js .= "\n<script type=\"text/javascript\" src=\"$oberlib_dir/overlibmws_overtwo.js\"></script>";
    $js .= "\n<script type=\"text/javascript\" src=\"$oberlib_dir/overlibmws_scroll.js\"></script>";
    $js .= "\n<script type=\"text/javascript\" src=\"$oberlib_dir/overlibmws_shadow.js\"></script>";

    $js .= "\n<script type=\"text/javascript\" src=\"$plugin_url/js/transposh.js\"></script>\n";

    echo $js;
}


/*
 * Return the img tag that will added to enable editing a translatable
 * item on the page
 *
 */
function get_img_tag($original, $translation)
{
    global $plugin_url, $lang;
    
    $img = "<img src=\"$plugin_url/translate.png\" alt=\"translate\"  
           onclick=\"translate_dialog('$original','$translation','$lang','$plugin_url/insert_translation.php'); return false;\" 
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
 * Setup a buffer that will contain the contents of the html page.
 * Once processing is completed the buffer will go into the translation process.
 */
function on_init()
{
    global $plugin_url;
    
    logger(__METHOD__ . $_SERVER['REQUEST_URI']);
    $plugin_url= get_option('home') . "/wp-content/plugins/transposh";

    ob_start("translate_page");
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

    logger("Added query var: " . LANG_PARAM);
    return $qvars;
}


/*
 * Setup the translation database. 
 *
 */
function setup_db() 
{
    global $db_name, $db;
    
    logger("Enter " . __METHOD__  );

    if (file_exists($db_name))
    {
        //TODO: verify db version before going further

        //database exists nothing more todo
        return;
    }

    logger("Attempting to create database $db_name", 0);

    try
    {
        //For some reason the global declaraion is not set when this function is called ? 
        $db_name = dirname(__FILE__) . "/transposh.sqlite";
        
        // create new database (procedural interface)
        $db = new SQLiteDatabase($db_name);
        logger("Opened database $db_name", 0);
        
        
        //Create table
        $db->query("CREATE TABLE phrases (original CHAR(512),
                                          lang CHAR(5),
                                          translated CHAR(512),
                                          PRIMARY KEY (original, lang))");
        logger("Created table phrases");
    }
    catch(Exception $exception)
    {
        logger("error !!! " . $exception->getMessage());
        die($exception->getMessage());
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
    global $admin_msg, $db_name;
    logger("Enter " . __METHOD__, 0);

    if (!file_exists($db_name))
    {
        $admin_msg = "Failed to locate database <em> $db_name </em>. <br>";
        $admin_msg .= "Verify that sqlite is supported and that <em> plugins/transposh </em>
                       directory is writable. <br>";
        
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