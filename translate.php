<?php
/*
Plugin Name: Translation Filter
Plugin URI: http://transposh.com/#
Description: Translation filter for WordPress
Author: Amir Perlman
Version: 0.1
Author URI: http://transposh.com/
*/


require_once("logging.php");
require_once("constants.php");
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

//Class marking a section not be translated.
define("NO_TRANSLATE_CLASS", "no_translate");

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

//The language to which the current page will be translated to. 
$lang;
 
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

//Is the current use is in edit mode. 
$is_edit_mode = false;
                             
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

    if(!open_db())
    {
        //Can't open the database - avoid further processing 
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
    $no_translate = 0;
    
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
                
        if(should_skip_element($element))
        {
            //do nothing
        }
        else
        {
            //Mark tag start position
            $tag_start = $pos;
            
            //skip to the '>' marking the end of the element
            $pos = strpos($page, '>', $pos);

            //Mark tag end position
            $tag_end = $pos;
            
            if($page[$pos-1] == '/')
            {
                //single line tag - no need to update tags list
                process_tag_init($element, $tag_start, $tag_end);
            }
            else if($element[0] != '/') 
            {
                process_tag_init($element, $tag_start, $tag_end);
                $tags_list[] = $element;

                //Look for the no translate class 
                if(stripos($element, NO_TRANSLATE_CLASS) !== FALSE)
                {
                    $no_translate++;
                }
            }
            else
            {
                $popped_element = array_pop($tags_list);
                process_tag_termination($element);

                //Look for the no translate class 
                if(stripos($popped_element, NO_TRANSLATE_CLASS) !== FALSE)
                {
                    $no_translate--;
                }
            }

            $pos++;

            //skip processing while enclosed within a tag marked by no_translate
            if(!$no_translate)
            {
                process_current_tag();
            }
            
        }
    }

    if(strlen($tr_page) > 0)
    {
        //Some translation has been taken place. Complete the translated
        //page up to the full contents of the original page.
        update_translated_page(strlen($page), -1, "");
    }
    
    logger("Exit " . __METHOD__, 4);
}


/*
 * Determine if the specified element should be skipped. If so the position
 * is moved past end of tag.
 * Return true if element is skipped otherwise false. 
 */
function should_skip_element(&$element)
{
    global $page, $pos;
    $rc = true;
    
    if(strncmp($element, "!DOCTYPE", 8) == 0)
    {
        $pos = strpos($page, '>', $pos);
    }
    else if(strncmp($element, "!--", 3) == 0)
    {
        $pos = strpos($page, '-->', $pos);
    }
    else
    {
        $rc = false;
    }

    return $rc;
}

/*
 * Process tag init for the specified element, with the current start and
 * end positions within the page buffer.
 */
function process_tag_init(&$element, $start, $end)
{
    switch ($element)
    {
        case 'a':
            process_anchor_tag($start, $end);
            break;
        case 'div' :     
        case 'span':
            process_span_or_div_tag($element, $start, $end);
            break;
    }
    
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
 * Handle span tags. Looks for 'no tranlate' identifier that will disable
 * translation for the enclosed session.
 *
 */
function process_span_or_div_tag(&$element, $start, $end)
{
    
    $cls = get_attribute($start, $end, 'class');

    if($cls == NULL)
    {
        return;
    }

    //Look for the no translate class 
    if(stripos($cls, NO_TRANSLATE_CLASS) === FALSE)
    {
        return;
    }

    //Mark the element as not translatable
    $element .= "." . NO_TRANSLATE_CLASS;
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
 * Search for the given attribute within the limit of the start and
 * end position within the buffer.
 * Returns the string containing the attribute if available otherwise NULL.
 * In addition the start and end position are moved to boundaries of the
 * attribute's value. 
 */
function get_attribute(&$start, &$end, $id)
{
    global $page;
        
    //look for the id within the given limits.
    while($start < $end)
    {
        $index = 0;
    
        while($start < $end && $page[$start + $index] == $id[$index]
              && $index < strlen($id))
        {
            $index++;
        }

        if($index == strlen($id))
        {
            //we have match
            break;
        }

        $start++;
    }

    if($start == $end)
    {
        return NULL;
    }
    
    //look for the " or ' marking start of attribute's value
    while($start < $end && $page[$start] != '"' && $page[$start] != "'")
    {
        $start++;
    }

    $start++;
    if($start >= $end)
    {
        return NULL;
    }

    $tmp = $start + 1;
    //look for the " or ' marking the end of attribute's value
    while($tmp < $end && $page[$tmp] != '"' && $page[$tmp] != "'")
    {
        $tmp++;
    }
    
    $end = $tmp - 1;
    
        
    return substr($page, $start, $end - $start + 1);
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

    //translate only specific elements - <a> or <div>
    if($current_tag == 'a' || array_search('div', $tags_list))
    {
        skip_white_space();
        $start = $pos;

        while($pos < strlen($page) && $page[$pos] != '<')
        {
            //will break translation unit when one of the following characters is reached: ., 
            if(is_sentence_breaker($pos))
             {
                 $pos++;
                 translate_text($start);
                 $start = $pos;
             }
             else
             {
                 $pos++;
             }
        }
        
        if($pos > $start)
        {
            translate_text($start);
        }
    }
    logger("Exit" .  __METHOD__ . " : $current_tag" , 4);
}


/*
 * Determine if the current position in buffer is a sentence breaker, e.g. '.' or ',' .
 * Note html markups are not considered sentence breaker within the scope of this function.
 * Return true is current position marks a break in sentence otherwise false
 */
function is_sentence_breaker($position)
{
    global $page;
    $rc = false;
    
    //characters which break the sentence into segments
    if($page[$position] == ',' || $page[$position] == '?' ||
       $page[$position] == '!' ||
       ($page[$position] == '.' && !is_number($position+1)))
    {
        $rc = true;
    }
    
    return $rc;
}


/*
 * Determine if the current position is a number.
 * Return true if a number otherwise false
 */
function is_number($position)
{
    global $page;
    
    if($page[$position] >= '0' && $page[$position] <= '9')
    {
        return true;
    }

    return false;
}
    
/*
 * Determine if the current position in buffer is a white space. 
 * return true if current position marks a white space otherwise false.
 */ 
function is_white_space($position)
{
    global $page;
    
    if($page[$position] == " "  || $page[$position] ==  ""    ||
       $page[$position] == "\t" || $page[$position] == "\r"   ||
       $page[$position] == "\n" || $page[$position] == "\x0B" ||
       $page[$position] == "\0")
    {
        return true;
    }
}

/*
 * Skip within buffer past unreadable characters , i.e. white space
 * and characters considred to be a sentence breaker. Staring from the specified
 * position going either forward or backward. 
 * param forward - indicate direction going either backward of forward. 
 */
function skip_unreadable_chars(&$index, $forward=true)
{
    global $page, $pos;

    if(!isset($index))
    {
        //use $pos as the default position if not specified otherwise
        $index = &$pos;
    }
    $start = $index;

    while($index < strlen($page) && $index > 0 &&
          (is_white_space($index) || is_sentence_breaker($index)))
    {
        ($forward ? $index++ : $index--);
    }
    
    return $index;
}

/*
 * Skip within buffer past white space characters , Staring from the specified
 * position going either forward or backward. 
 * param forward - indicate direction going either backward of forward. 
 */
function skip_white_space(&$index, $forward=true)
{
    global $page, $pos;

    if(!isset($index))
    {
        //use $pos as the default position if not specified otherwise
        $index = &$pos;
    }

    while($index < strlen($page) && $index > 0 && is_white_space($index))
    {
        ($forward ? $index++ : $index--);
    }

    return $index;
}

/**
 * Translate the text between the given start position and the current
 * position (pos) within the buffer. 
 */
function translate_text($start)
{
    logger("Enter " . __METHOD__  . " : $start", 4);
    global $page, $pos, $is_edit_mode;

    //trim unreadable chars from the start position going forward
    skip_unreadable_chars($start);

    //Set the end position of the string to one back from current position
    //(i.e. current position points to '<') and trim white space from the right
    //backwards
    $end = $pos - 1;
    $end = skip_unreadable_chars($end, $forward=false);
    
    if($start >= $end)
    {
        //empty string - nothing to do
        return;
    }

    $original_text = substr($page, $start, $end - $start + 1);

    //Cleanup and prepare text
    $original_text = scrub_text($original_text);
    if($original_text == NULL)
    {
        //nothing left from the text
        return;
    }

    $translated_text = fetch_translation($original_text);
    if($translated_text != NULL)
    {
        logger("Translation: $original_text => $translated_text");
        $translated_text = htmlspecialchars($translated_text);
        update_translated_page($start, $end, $translated_text);
    }

    if($is_edit_mode)
    {
        $img = get_img_tag($original_text, $translated_text);
        update_translated_page($end + 1, - 1, $img);
    }

    logger("Exit " . __METHOD__  . " : $original_text" , 4);
}


/*
 * Scrubs text prior to translation to remove/encode special
 * characters.
 * Return the scurbed text, or NULL if nothing left to translate
 */
function scrub_text(&$text)
{
    //skip strings like without any readable characters (i.e. ".")
    //Todo: need a broader defintion for non-ascii characters as well
    if(preg_match("/^[.?!|\(\)\[\],0-9]+$/", $text))
    {
        return NULL;
    }

    //replace multi space chars with a single space
    $text = preg_replace("/\s\s+/", " ", $text);

    //Make that the string is encoded in the same way as it will
    //decoded, when passed back for translation (i.e. post)
    $text = htmlspecialchars($text);

    return $text;
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
 * item on the page
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