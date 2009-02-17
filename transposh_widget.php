<?php

/*
 * Provides the following:
 * 1. Widget for sidebar for selecting a language and switching between edit/view
 * mode.
 * 2. Admin page for configuring language selection and other configuration settings.
 *
 */
require_once("logging.php");
require_once("constants.php");

/*
 * Intercept init calls to see if it was posted by the widget. 
 *
 */
function init_transposh()
{
    if ($_POST['transposh_widget_posted'])
    {
        logger("Enter " . __METHOD__, 4);
        $ref=getenv('HTTP_REFERER');
        $lang = $_POST[LANG_PARAM];

        //cleanup previous lang edit parameter from url
        $ref = preg_replace("/(" . LANG_PARAM . "|" . EDIT_PARAM . ")=[^&]*/i", "", $ref);


        if($lang != "none")
        {
            $lang = LANG_PARAM . "=$lang";
            
            $ref .= (strstr($ref, '?') ? "&$lang" : "?$lang");
            $ref .= ($_POST[EDIT_PARAM] == "1" ? "&edit=1" : "");

            //Cleanup extra &&&
            $ref = preg_replace("/&&+/", "&", $ref);
        }

        logger("Widget redirect url: $ref", 3);
                
        wp_redirect($ref);
        exit;
    }
}

/*
 * Register the widget. 
 */
function transposh_widget_init()
{
    logger("Enter " . __METHOD__, 4);
    if (!function_exists('register_sidebar_widget'))
    {
        return;
    }
    
	// Register widget
    register_sidebar_widget(array('Transposh', 'widgets'), 'transposh_widget');
}


/*
 * The actual widget implementation.
 */
function transposh_widget($args)
{
    logger("Enter " . __METHOD__, 4);
    global $languages, $wp_query; 
    extract($args);
    
    $page_url =  ($_SERVER['HTTPS'] == 'on' ?
                  'https://' : 'http://') . $_SERVER["SERVER_NAME"];
    $page_url .= ($_SERVER["SERVER_PORT"] != "80" ? ":" .$_SERVER["SERVER_PORT"] : "");
    $page_url .= $_SERVER["REQUEST_URI"];

    $is_edit = ($wp_query->query_vars[EDIT_PARAM] == "1" ? true : false);
    $lang = $wp_query->query_vars[LANG_PARAM];
    
    echo $before_widget . $before_title . __("Transposh") . $after_title;
    ?>
    <form action="<?php echo $page_url ?>" method="post">
         <select name="lang" id="lang" onchange="Javascript:this.form.submit();">
         <option value="none">[Language]</option>

         <?php

         foreach($languages as $code => $language)
         {
             $is_selected = ($lang == $code ? "selected=\"selected\"" : "" );
             echo "<option value=\"$code\" $is_selected>$language</option>";
         }
    
         ?>

         </select>
         <br/>
         <?php echo "<input type=\"checkbox\" name=\"" . EDIT_PARAM . "\" value=\"1\"" .
               ($is_edit ? "checked=\"1\"" : "0") .
                "\" onchange=\"Javascript:this.form.submit();\"/>Edit Translation<br/>";
         ?>
         <input type="hidden" name="transposh_widget_posted" value="1" />
    </form> 
         
<?php
    echo $after_widget;
}

/*
 *
 */
function transposh_admin_menu()
{
    logger("Enter " . __METHOD__, 4);
}

//Register callback for WordPress events
add_action('init', 'init_transposh',0);
add_action('widgets_init', 'transposh_widget_init');
add_action('admin_menu', 'transposh_admin_menu');

?>