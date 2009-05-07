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

/*
 * Provides the sidebar widget for selecting a language and switching between edit/view
 * mode.
 */
require_once("core/logging.php");
require_once("core/constants.php");
require_once("transposh.php");

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

        //remove existing language settings.
        $ref = cleanup_url($ref);

		if($lang != "none")
		{
			$is_edit = $_POST[EDIT_PARAM];
			$ref = rewrite_url_lang_param($ref, $lang, $is_edit);

			//ref is generated with html entities encoded, needs to be
			//decoded when used in the http header (i.e. 302 redirect)
			$ref = html_entity_decode($ref, ENT_NOQUOTES);
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

	// Register widget control
	register_widget_control("Transposh",'transposh_widget_control');
	
	//regigster callback for widget's css
	add_action('wp_print_styles', 'add_transposh_widget_css');
}

/*
 * Add custom css, i.e. transposh.css
 */
function add_transposh_widget_css() {
	global $tr_plugin_url;
	
	//include the transposh_widget.css 
	wp_enqueue_style("transposh_widget","$tr_plugin_url/css/transposh_widget.css",array(),'<%VERSION%>');
	logger("Added transposh_widget_css");
}

/*
 * The actual widget implementation.
 */
function transposh_widget($args)
{
	logger("Enter " . __METHOD__, 4);
	global $languages, $wp_query, $tr_plugin_url,$lang;
	extract($args);

	$page_url =  ($_SERVER['HTTPS'] == 'on' ?
                  'https://' : 'http://') . $_SERVER["SERVER_NAME"];
	$page_url .= ($_SERVER["SERVER_PORT"] != "80" ? ":" .$_SERVER["SERVER_PORT"] : "");
	$page_url .= $_SERVER["REQUEST_URI"];

	$is_edit = ($wp_query->query_vars[EDIT_PARAM] == "1" ? TRUE : FALSE);
	$lang = $wp_query->query_vars[LANG_PARAM];

	$options = get_option(WIDGET_TRANSPOSH);
	$viewable_langs = get_option(VIEWABLE_LANGS);
	$editable_langs = get_option(EDITABLE_LANGS);
	$is_translator = is_translator();

    $is_showing_languages = FALSE;
    //TODO: improve this shortening
    $plugpath = parse_url($tr_plugin_url, PHP_URL_PATH);

	echo $before_widget . $before_title . __("Translation") . $after_title;

	switch ($options['style']) {
		case 1: // flags
            //keep the flags in the same direction regardless of the overall page direction
            echo "<div class=\"" . NO_TRANSLATE_CLASS . " transposh_flags\" >";

            foreach($languages as $code => $lang2)
			{
				list($language,$flag) = explode (",",$lang2);

                //remove any language identifier
                $page_url = cleanup_url($page_url);

				//Only show languages which are viewable or (editable and the user is a translator)
				if(strpos($viewable_langs, $code) !== FALSE ||
				   ($is_translator && strpos($editable_langs, $code) !== FALSE) ||
				   (get_option(DEFAULT_LANG) == $code && $lang))
				{
    				$page_url2 = rewrite_url_lang_param($page_url, $code, $is_edit);
    				if (get_option(DEFAULT_LANG) == $code) {
    					$page_url2 = $page_url;
    				}
                    //TODO: improve this hacky! shortening
                    $urlpath = parse_url($page_url2, PHP_URL_PATH);
                    if (trim(parse_url($page_url2, PHP_URL_QUERY)) != '')
                        $urlpath .= '?'.parse_url($page_url2, PHP_URL_QUERY);
                    if (trim(parse_url($page_url2, PHP_URL_FRAGMENT)) != '')
                        $urlpath .= '#'.parse_url($page_url2, PHP_URL_FRAGMENT);

					echo "<a href=\"" . $urlpath . "\">".
                         "<img src=\"$plugpath/img/flags/$flag.png\" title=\"$language\" alt=\"$language\"".
                         " style=\"padding: 1px 3px;border: 0px\"/></a>";
                    $is_showing_languages = TRUE;
				}
			}
            echo "</div>";

			// this is the form for the edit...
			echo "<form action=\"$page_url\" method=\"post\">";
			echo "<input type=\"hidden\" name=\"lang\" id=\"lang\" value=\"$lang\"/>";
			break;
		default: // language list

            echo "<form action=\"$page_url\" method=\"post\">";
            echo "<span class=\"" .NO_TRANSLATE_CLASS . "\" >";
			echo "<select name=\"lang\"	id=\"lang\" onchange=\"Javascript:this.form.submit();\">";
			echo "<option value=\"none\">[Language]</option>";

			foreach($languages as $code => $lang2)
			{
				list($language,$flag) = explode (",",$lang2);

                //Only show languages which are viewable or (editable and the user is a translator)
				if(strpos($viewable_langs, $code) !== FALSE ||
				   ($is_translator && strpos($editable_langs, $code) !== FALSE) ||
				   (get_option(DEFAULT_LANG) == $code && $lang))
				{
					$is_selected = ($lang == $code ? "selected=\"selected\"" : "" );
					echo "<option value=\"$code\" $is_selected>" . $language . "</option>";
                    $is_showing_languages = TRUE;
				}
			}
			echo "</select><br/>";
            echo "</span>"; // the no_translate for the language list
	}


    //at least one language showing - add the edit box if applicable
    if($is_showing_languages)
    {
        //Add the edit checkbox only for translators  on languages marked as editable
        if($is_translator && strpos($editable_langs, $lang) !== FALSE)
        {
            echo "<input type=\"checkbox\" name=\"" . EDIT_PARAM . "\" value=\"1\" " .
                ($is_edit ? "checked=\"checked\"" : "") .
                " onclick=\"this.form.submit();\"/>&nbsp;Edit Translation";
        }

        echo "<input type=\"hidden\" name=\"transposh_widget_posted\" value=\"1\"/>";
    }
    else
    {
        //no languages configured - error message
        echo '<p> No languages available for display. Check the Transposh settings (Admin).</p>';
    }

    echo "</form>";
    //echo "<button onClick=\"do_auto_translate();\">translate all</button>";
	echo "<div id=\"".SPAN_PREFIX."credit\">by <a href=\"http://transposh.org\"><img src=\"$plugpath/img/tplogo.png\" style=\"padding:1px;border:0px\" title=\"Transposh\" alt=\"Transposh\"/></a></div>";
    echo $after_widget;
}

/*
 * Remove from url any language (or editing) params that were added for our use.
 * Return the scrubed url
 */
function cleanup_url($url)
{
    global $home_url, $home_url_quoted;

    //cleanup previous lang & edit parameter from url
    $url = preg_replace("/(" . LANG_PARAM . "|" . EDIT_PARAM . ")=[^&]*/i", "", $url);


    if(!$home_url)
    {
        //make sure required home urls are fetched - as they are need now
        init_global_vars();
    }

    //cleanup lang identifier in permalinks
    $url = preg_replace("/$home_url_quoted\/(..\/)/", "$home_url/",  $url);

    //some more cleans
    $url = preg_replace("/&$/", "", $url);
	$url = preg_replace("/\?$/", "", $url);

    return $url;
}

/*
 * Mark the given text so it will not subject to translation.
 * Return the text with the required tags
 */
function no_translate($text)
{
	return "<span class=\"" . NO_TRANSLATE_CLASS . "\">$text</span>";
}

/*
 * This is the widget control, allowing the selection of presentation type.
 */
function transposh_widget_control()
{
	$options = $newoptions = get_option(WIDGET_TRANSPOSH);

	if ( isset($_POST['transposh-submit']) )
    {
		$newoptions['style'] = $_POST['transposh-style'];
        if ($_POST['transposh-progress'])
            $newoptions['progressbar'] = 1;
        else
            $newoptions['progressbar'] = 0;
	}

	if ( $options != $newoptions )
    {
		$options = $newoptions;
		update_option(WIDGET_TRANSPOSH, $options);
	}

    echo '<p><label for="transposh-style">Style:<br />'.
         '<select id="transposh-style" name="transposh-style">'.
         '<option value="0"' . ($options['style'] == 0 ? ' selected="selected"' : '').'>Language list</option>'.
         '<option value="1"' . ($options['style'] == 1 ? ' selected="selected"' : '').'>Flags</option>'.
         '</select>'.
         '</label></p>'.
         '<p><label for="transposh-progress">Effects:<br/>'.
         '<input type="checkbox" id="transposh-progress" name="transposh-progress"'.($options['progressbar'] ? ' checked="checked"' : '').'/>'.
         '&nbsp;Show progress bar</label></p>'.
         '<input type="hidden" name="transposh-submit" id="transposh-submit" value="1"/>';
}

//Register callback for WordPress events
add_action('init', 'init_transposh',0);
add_action('widgets_init', 'transposh_widget_init');

?>