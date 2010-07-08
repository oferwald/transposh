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

/*
 * Provides the sidebar widget for selecting a language and switching between edit/view
 * mode.
 */

//class that reperesent the complete plugin
class transposh_plugin_widget {

    /** @var transposh_plugin Container class */
    private $transposh;
    /** @var string Contains the name of the used widget file */
    public $base_widget_file_name;

    //constructor of class, PHP4 compatible construction for backward compatibility
    function transposh_plugin_widget(&$transposh) {
	//Register callback for WordPress events
	$this->transposh = &$transposh;
	add_action('init', array(&$this, 'init_transposh'), 1);
	add_action('widgets_init', array(&$this, 'transposh_widget_init'));
    }

    /**
     * Intercept init calls to see if it was posted by the widget.
     */
    function init_transposh() {
	if (isset($_POST['transposh_widget_posted'])) {
	    logger("Enter", 4);
	    // FIX! yes, this is needed (not with priorty!
	    //transposh_plugin::init_global_vars();
	    //$this->transposh->init_global_vars();

	    $ref = getenv('HTTP_REFERER');
	    $lang = $_POST[LANG_PARAM];
	    if ($lang == '') {
		$lang = get_language_from_url($_SERVER['HTTP_REFERER'], $this->transposh->home_url);
	    }
	    if ($lang == $this->transposh->options->get_default_language() || $lang == "none")
		    $lang = "";
	    logger("Widget referrer: $ref, lang: $lang", 4);

	    // first, we might need to get the original url back
	    if ($this->transposh->options->get_enable_url_translate()) {
		$ref = get_original_url($ref, $this->transposh->home_url, get_language_from_url($ref, $this->transposh->home_url), array($this->transposh->database, 'fetch_original'));
	    }

	    //remove existing language settings.
	    $ref = cleanup_url($ref, $this->transposh->home_url);
	    logger("cleaned referrer: $ref, lang: $lang", 4);

	    if ($lang && $this->transposh->options->get_enable_url_translate()) {
		// and then, we might have to translate it
		$ref = translate_url($ref, $this->transposh->home_url, $lang, array(&$this->transposh->database, 'fetch_translation'));
		$ref = str_replace(array('%2F', '%3A', '%3B', '%3F', '%3D', '%26'), array('/', ':', ';', '?', '=', '&'), urlencode($ref));
		logger("translated to referrer: $ref, lang: $lang", 3);

		//ref is generated with html entities encoded, needs to be
		//decoded when used in the http header (i.e. 302 redirect)
		//$ref = html_entity_decode($ref, ENT_NOQUOTES);
	    }
	    $ref = rewrite_url_lang_param($ref, $this->transposh->home_url, $this->transposh->enable_permalinks_rewrite, $lang, $_POST[EDIT_PARAM]);

	    logger("Widget redirect url: $ref", 3);

	    wp_redirect($ref);
	    exit;
	}
    }

    /**
     * Register the widget.
     */
    function transposh_widget_init() {
	logger("Enter", 4);
	if (!function_exists('register_sidebar_widget')) {
	    return;
	}

	// Register widget
	register_sidebar_widget(array('Transposh', 'widgets'), array(&$this, 'transposh_widget'));

	// Register widget control
	register_widget_control("Transposh", array(&$this, 'transposh_widget_control'));

	// Register callback for widget's css
	add_action('wp_print_styles', array(&$this, 'add_transposh_widget_css'));
	add_action('wp_print_scripts', array(&$this, 'add_transposh_widget_js'));
    }

    /**
     *
     */
    function load_widget() {
	if ($this->base_widget_file_name) {
	    return;
	} // avoid dual loading
	$file = $this->transposh->options->get_widget_file();
	$widget_src = $this->transposh->transposh_plugin_dir . 'widgets/' . $file;
	if (file_exists($widget_src)) {
	    require_once $widget_src;
	} else {
	    $file = 'tpw_default.php';
	    require_once $this->transposh->transposh_plugin_dir . 'widgets/' . $file; //TODO fix widget constant all around...
	}
	$this->base_widget_file_name = substr($file, 0, -4);
    }

    /**
     * Add custom css, i.e. transposh_widget.css, flags now override widget
     */
    function add_transposh_widget_css() { //TODO ! goway
	$this->load_widget();
	logger("3");
	//include the transposh_widget.css
	// TODO: user generated version

	if (function_exists(tp_widget_css)) {
	    tp_widget_css();
	} else {
	    if (file_exists($this->transposh->transposh_plugin_dir . 'widgets/' . $this->base_widget_file_name . ".css")) {
		wp_enqueue_style("transposh_widget", "{$this->transposh->transposh_plugin_url}/widgets/{$this->base_widget_file_name}.css", array(), TRANSPOSH_PLUGIN_VER);
		//wp_enqueue_style("transposh_widget", "{$this->transposh->transposh_plugin_url}/widgets/{$base_file_name}.css", array(), TRANSPOSH_PLUGIN_VER);
	    }
	}

	logger("Added transposh_widget_css", 4);
    }

    /**
     * Add custom css, i.e. transposh_widget.css, flags now override widget
     */
    function add_transposh_widget_js() { //TODO ! goway
	$this->load_widget();
	logger("2");
	//include the transposh_widget.css
	// TODO: user generated version
	if (function_exists(tp_widget_js)) {
	    tp_widget_js();
	} else {
	    if (file_exists($this->transposh->transposh_plugin_dir . 'widgets/' . $this->base_widget_file_name . ".js")) {
		wp_enqueue_script("transposh_widget", "{$this->transposh->transposh_plugin_url}/widgets/{$this->base_widget_file_name}.js", '', TRANSPOSH_PLUGIN_VER);
	    }
	}
	logger("Added transposh_widget_js", 4);
    }

    /**
     * Creates the widget html
     * @param array $args Contains such as $before_widget, $after_widget, $before_title, $after_title, etc
     */
    function transposh_widget($args) {
	//hmmm, this should actually prepare all vars needed, include the correct widget and send the vars to that function,
	// so what are those vars?
	// languages to list, with their urls, active language, target url for posting, anything else?
	$this->load_widget();

	$plugpath = parse_url($this->transposh->transposh_plugin_url, PHP_URL_PATH);

	$calc_url = false; // By default, avoid calculating the urls
	if (function_exists(tp_widget_needs_post_url))
		$calc_url = tp_widget_needs_post_url();
	$widget_args = array();
	$page_url = $_SERVER["REQUEST_URI"];
	$clean_page_url = cleanup_url($page_url, $this->transposh->home_url, true);
	if ($this->transposh->options->get_enable_url_translate() && $calc_url) {
	    $clean_page_url = get_original_url($clean_page_url, '', $this->transposh->target_language, array($this->transposh->database, 'fetch_original'));
	}
	foreach ($this->transposh->options->get_sorted_langs() as $code => $langrecord) {
	    list ($langname, $language, $flag) = explode(",", $langrecord);

	    //Only show languages which are viewable or (editable and the user is a translator)
	    if ($this->transposh->options->is_viewable_language($code) ||
		    ($this->transposh->options->is_editable_language($code) && $this->transposh->is_translator()) ||
		    ($this->transposh->options->is_default_language($code))) {
		if ($this->transposh->options->get_enable_url_translate() && $calc_url) {
		    $page_url = translate_url($clean_page_url, '', $code, array(&$this->transposh->database, 'fetch_translation'));
		} else {
		    $page_url = $clean_page_url;
		}
		// clean $code in default lanaguge
		if ($calc_url)
			$page_url = rewrite_url_lang_param($page_url, $this->transposh->home_url, $this->transposh->enable_permalinks_rewrite, ($code == $this->transposh->options->get_default_language()) ? '' : $code, $this->transposh->edit_mode);
		$widget_args[] = array("lang" => $langname, "langorig" => $language, "flag" => $flag, "isocode" => $code, "url" => $page_url, "active" => ($this->transposh->target_language == $code));
	    }
	}
	//    logger($widget_args);
	logger("Enter widget", 4);
	logger($args);
	extract($args);
	logger($args, 4);

	logger("p3:" . $page_url, 6);

	$is_showing_languages = FALSE;
	//TODO: improve this shortening

	echo $before_widget . $before_title . __("Translation") . $after_title;

	//remove any language identifier
	$clean_page_url = cleanup_url($page_url, $this->transposh->home_url, true);
	if ($this->transposh->options->get_enable_url_translate()) {
	    $clean_page_url = get_original_url($clean_page_url, '', $this->transposh->target_language, array($this->transposh->database, 'fetch_original'));
	}
	logger("WIDGET: clean page url: $clean_page_url ,orig: $page_url");
	echo "<form id=\"tp_form\" action=\"$clean_page_url\" method=\"post\">";

	tp_widget_do($widget_args);
	/*
	  switch ($this->transposh->options->get_widget_style()) {
	  case 1: // flags
	  case 2: // language list
	  //keep the flags in the same direction regardless of the overall page direction
	  echo "<div class=\"" . NO_TRANSLATE_CLASS . " transposh_flags\" >";
	  if ($this->transposh->options->get_widget_in_list())
	  echo "<ul>";

	  foreach ($this->transposh->options->get_sorted_langs() as $code => $langrecord) {
	  list ($langname, $language, $flag) = explode(",", $langrecord);

	  //Only show languages which are viewable or (editable and the user is a translator)
	  if ($this->transposh->options->is_viewable_language($code) ||
	  ($this->transposh->options->is_editable_language($code) && $this->transposh->is_translator()) ||
	  ($this->transposh->options->is_default_language($code))) {
	  logger("code = " . $code, 5);
	  if ($this->transposh->options->get_enable_url_translate()) {
	  $page_url = translate_url($clean_page_url, '', $code, array(&$this->transposh->database, 'fetch_translation'));
	  } else {
	  $page_url = $clean_page_url;
	  }
	  // clean $code in default lanaguge
	  if ($code == $this->transposh->options->get_default_language())
	  $code = "";
	  $page_url = rewrite_url_lang_param($page_url, $this->transposh->home_url, $this->transposh->enable_permalinks_rewrite, $code, $this->transposh->edit_mode);

	  logger("urlpath = " . $page_url, 5);
	  if ($this->transposh->options->get_widget_in_list())
	  echo "<li>";
	  echo "<a href=\"" . $page_url . '"' . (($this->transposh->target_language == $code) ? ' class="tr_active"' : '') . '>' .
	  display_flag("$plugpath/img/flags", $flag, $language, $this->transposh->options->get_widget_css_flags()) .
	  "</a>";
	  if ($this->transposh->options->get_widget_style() != 1) {
	  echo "$language<br/>";
	  if ($this->transposh->options->get_widget_in_list())
	  echo "</li>";
	  }
	  $is_showing_languages = TRUE;
	  }
	  }
	  if ($this->transposh->options->get_widget_in_list())
	  echo "</ul>";
	  echo "</div>";

	  // this is the form for the edit...
	  if ($this->transposh->options->get_widget_in_list())
	  echo "<ul><li>";
	  echo "<form action=\"$clean_page_url\" method=\"post\">";
	  echo "<input type=\"hidden\" name=\"lang\" id=\"lang\" value=\"{$this->transposh->target_language}\"/>";
	  break;
	  default: // language selection

	  if ($this->transposh->options->get_widget_in_list())
	  echo "<ul><li>";
	  echo "<form action=\"$clean_page_url\" method=\"post\">";
	  echo "<span class=\"" . NO_TRANSLATE_CLASS . "\" >";
	  echo "<select name=\"lang\"	id=\"lang\" onchange=\"Javascript:this.form.submit();\">";
	  echo "<option value=\"none\">[Language]</option>";

	  foreach ($this->transposh->options->get_sorted_langs() as $code => $langrecord) {
	  list ($langname, $language, $flag) = explode(",", $langrecord);

	  //Only show languages which are viewable or (editable and the user is a translator)
	  if ($this->transposh->options->is_viewable_language($code) ||
	  ($this->transposh->options->is_editable_language($code) && $this->transposh->is_translator()) ||
	  ($this->transposh->options->is_default_language($code))) {
	  $is_selected = ($this->transposh->target_language == $code ? "selected=\"selected\"" : "" );
	  echo "<option value=\"$code\" $is_selected>" . $language . "</option>";
	  $is_showing_languages = TRUE;
	  }
	  }
	  echo "</select><br/>";
	  echo "</span>"; // the no_translate for the language list
	  }
	 */
	//at least one language showing - add the edit box if applicable
	if (!empty($widget_args)) {
	    if ($this->transposh->options->get_widget_allow_set_default_language()) {
		If ((isset($_COOKIE['TR_LNG']) && $_COOKIE['TR_LNG'] != $this->transposh->target_language) || (!isset($_COOKIE['TR_LNG']) && !$this->transposh->options->is_default_language($this->transposh->target_language))) {
		    echo '<a id="' . SPAN_PREFIX . 'setdeflang" onClick="return false;" href="' . $this->transposh->post_url . '?tr_cookie_bck">Set as default language</a><br/>';
		}
	    }
	    //Add the edit checkbox only for translators  on languages marked as editable
	    if ($this->transposh->is_editing_permitted()) {
		echo "<input type=\"checkbox\" name=\"" . EDIT_PARAM . "\" value=\"1\" " .
		($this->transposh->edit_mode ? "checked=\"checked\"" : "") .
		" onclick=\"this.form.submit();\"/>&nbsp;Edit Translation";
	    }

	    echo "<input type=\"hidden\" name=\"transposh_widget_posted\" value=\"1\"/>";
	} else {
	    //no languages configured - error message
	    echo '<p>No languages available for display. Check the Transposh settings (Admin).</p>';
	}

	echo "</form>";
	//if ($this->transposh->options->get_widget_in_list()) echo "</li></ul>";
	//TODO: maybe... echo "<button onClick=\"do_auto_translate();\">translate all</button>";
	//if ($this->transposh->options->get_widget_in_list()) echo "<ul><li>";
	// Now this is a comment for those wishing to remove our logo (tplogo.png) and link (transposh.org) from the widget
	// first - according to the gpl, you may do so - but since the code has changed - please make in available under the gpl
	// second - we did invest a lot of time and effort into this, and the link is a way to help us grow and show your appreciation, if it
	// upsets you, feel more than free to move this link somewhere else on your page, such as the footer etc.
	// third - feel free to write your own widget, the translation core will work
	// forth - you can ask for permission, with a good reason, if you contributed to the code - it's a good enough reason :)
	// last - if you just delete the following line, it means that you have little respect to the whole copyright thing, which as far as we
	// understand means that by doing so - you are giving everybody else the right to do the same and use your work without any attribution
	echo "<div id=\"" . SPAN_PREFIX . "credit\">by <a href=\"http://tran" . "sposh.org\"><img class=\"" . NO_TRANSLATE_CLASS . "\" height=\"16\" width=\"16\" src=\"$plugpath/img/tplog" . "o.png\" style=\"padding:1px;border:0px\" title=\"Transposh\" alt=\"Transposh\"/></a></div>";
	//if ($this->transposh->options->get_widget_in_list()) echo "</li></ul>";
	echo $after_widget;
    }

    function transposh_widget_post($save = true) {
	logger($_POST);
	logger('handled widget post');
	$this->transposh->options->set_widget_file($_POST[WIDGET_FILE]);
	$this->transposh->options->set_widget_progressbar($_POST[WIDGET_PROGRESSBAR]);
	//$this->transposh->options->set_widget_css_flags($_POST[WIDGET_CSS_FLAGS]);
	//$this->transposh->options->set_widget_in_list($_POST[WIDGET_IN_LIST]);
	$this->transposh->options->set_widget_allow_set_default_language($_POST[WIDGET_ALLOW_SET_DEFLANG]);
	if ($save) $this->transposh->options->update_options();
	// Avoid coming here twice...
	unset($_POST['transposh-submit']);
    }

    /**
     * Inspired by the get_plugins function of wordpress
     */
    function get_widgets($widget_folder = '') {
	get_plugins();

//        	if ( ! $cache_plugins = wp_cache_get('plugins', 'plugins') )
	//$cache_plugins = array();
	//if ( isset($cache_plugins[ $plugin_folder ]) )
	//	return $cache_plugins[ $plugin_folder ];

	$tp_widgets = array();
	$widget_root = $this->transposh->transposh_plugin_dir . "widgets";
	if (!empty($widget_folder)) $widget_root .= $widget_folder;

	// Files in wp-content/widgets directory
	$widgets_dir = @opendir($widget_root);
	$widget_files = array();
	if ($widgets_dir) {
	    while (($file = readdir($widgets_dir) ) !== false) {
		if (substr($file, 0, 1) == '.') continue;
		if (is_dir($widget_root . '/' . $file)) {
		    $widgets_subdir = @ opendir($widget_root . '/' . $file);
		    if ($widgets_subdir) {
			while (($subfile = readdir($widgets_subdir) ) !== false) {
			    if (substr($subfile, 0, 1) == '.') continue;
			    if (substr($subfile, 0, 3) == 'tpw' && substr($subfile, -4) == '.php')
				    $widget_files[] = "$file/$subfile";
			}
		    }
		}
		if (substr($file, 0, 3) == 'tpw' && substr($file, -4) == '.php')
			$widget_files[] = $file;
	    }
	} else {
	    return $tp_widgets;
	}

	@closedir($widgets_dir);
	@closedir($widgets_subdir);

	if (empty($widget_files)) return $tp_widgets;

	foreach ($widget_files as $widget_file) {
	    if (!is_readable("$widget_root/$widget_file")) continue;

	    $widget_data = get_plugin_data("$widget_root/$widget_file", false, false); //Do not apply markup/translate as it'll be cached.

	    if (empty($widget_data['Name'])) continue;

	    $tp_widgets[plugin_basename($widget_file)] = $widget_data;
	}

	uasort($tp_widgets, create_function('$a, $b', 'return strnatcasecmp( $a["Name"], $b["Name"] );'));

//        $cache_widgets[$widget_folder] = $tp_widgets;
//        wp_cache_set('widgets', $cache_widgets, 'widgets');

	return $tp_widgets;
//        get_widgets($widget_folder);
    }

    /**
     * This is the widget control, allowing the selection of presentation type.
     */
    function transposh_widget_control() {
	if (isset($_POST['transposh-submit'])) $this->transposh_widget_post();

	$widgets = $this->get_widgets();

	echo '<p><label for="' . WIDGET_FILE . '">Style:<br />' .
	'<select id="transposh-style" name="' . WIDGET_FILE . '">';
	foreach ($widgets as $file => $widget) {
	    logger($widget);
	    $selected = ($this->transposh->options->get_widget_file() == $file) ? ' selected="selected"' : '';
	    echo "<option value=\"$file\"$selected>{$widget['Name']}</option>";
	}
//        '<option value="0"' . ($this->transposh->options->get_widget_style() == 0 ? ' selected="selected"' : '') . '>Language selection</option>' .
//        '<option value="1"' . ($this->transposh->options->get_widget_style() == 1 ? ' selected="selected"' : '') . '>Flags</option>' .
//        '<option value="2"' . ($this->transposh->options->get_widget_style() == 2 ? ' selected="selected"' : '') . '>Language list</option>' .
//        }
	echo '</select>' .
	'</label></p>' .
	'<p><label for="transposh-progress">Effects:</label><br/>' .
	'<input type="checkbox" id="' . WIDGET_PROGRESSBAR . '" name="' . WIDGET_PROGRESSBAR . '"' . ($this->transposh->options->get_widget_progressbar() ? ' checked="checked"' : '') . '/>' .
	'<span style="border-bottom: 1px dotted #333; cursor: help; margin-left: 4px" title="Show progress bar when a client triggers automatic translation">Show progress bar</span><br/>' .
	//'<input type="checkbox" id="' . WIDGET_CSS_FLAGS . '" name="' . WIDGET_CSS_FLAGS . '"' . ($this->transposh->options->get_widget_css_flags() ? ' checked="checked"' : '') . '/>' .
	//'<span style="border-bottom: 1px dotted #333; cursor: help; margin-left: 4px" title="Use a single sprite with all flags, makes pages load faster. Currently not suitable if you made changes to the flags.">Use CSS flags</span><br/>' .
	'<input type="checkbox" id="' . WIDGET_ALLOW_SET_DEFLANG . '" name="' . WIDGET_ALLOW_SET_DEFLANG . '"' . ($this->transposh->options->get_widget_allow_set_default_language() ? ' checked="checked"' : '') . '/>' .
	'<span style="border-bottom: 1px dotted #333; cursor: help; margin-left: 4px" title="Widget will allow setting this language as user default.">Allow user to set current language as default</span><br/>' .
	//'<input type="checkbox" id="' . WIDGET_IN_LIST . '" name="' . WIDGET_IN_LIST . '"' . ($this->transposh->options->get_widget_in_list() ? ' checked="checked"' : '') . '/>' .
	//'<span style="border-bottom: 1px dotted #333; cursor: help; margin-left: 4px" title="Wraps generated widget code with UL helps with some CSSs.">Wrap widget with an unordered list (UL)</span>' .
	'</p>' .
	'<input type="hidden" name="transposh-submit" id="transposh-submit" value="1"/>';
    }

}

/**
 * Function provided for old widget include code compatability
 * @param array $args Not needed
 */
function transposh_widget($args = array()) {
    $GLOBALS['my_transposh_plugin']->widget->transposh_widget($args);
}
?>