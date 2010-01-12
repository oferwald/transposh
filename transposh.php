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

/*  Copyright © 2009-2010 Transposh Team (website : http://transposh.org)
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

//avoid direct calls to this file where wp core files not present
if (!function_exists ('add_action')) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit();
}

require_once("core/logging.php");
require_once("core/constants.php");
require_once("core/utils.php");
require_once("core/jsonwrapper/jsonwrapper.php");
require_once("core/parser.php");
require_once("wp/transposh_db.php");
require_once("wp/transposh_widget.php");
require_once("wp/transposh_admin.php");
require_once("wp/transposh_options.php");
require_once("wp/transposh_postpublish.php");

/**
 * This class represents the complete plugin
 */
class transposh_plugin {
    // List of contained objects
    /** @var transposh_plugin_options An options object*/
    public $options;
    /** @var transposh_plugin_admin Admin page*/
    private $admin;
    /** @var transposh_plugin_widget Widget control*/
    public $widget;
    /** @var transposh_database The database class*/
    public $database;
    /** @var transposh_postpublish Happens after editing*/
    public $postpublish;

    // list of properties
    /** @var string The site url*/
    public $home_url;
    /** @var string Where the javascript should post to*/
    public $post_url;
    /** @var string The url to the plugin directory*/
    public $transposh_plugin_url;
    /** @var boolean Enable rewriting of URLs*/
    public $enable_permalinks_rewrite;
    /** @var string The language to translate the page to*/
    public $target_language;
    /** @var boolean Are we currently editing the page?*/
    public $edit_mode;
    /** @var string Error message displayed for the admin in case of failure*/
    private $admin_msg;
    /** @var string Saved search variables*/
    private $search_s;

    /**
     * class constructor
     */
    function transposh_plugin() {
        // create and initialize sub-objects
        $this->options = new transposh_plugin_options();
        $this->database = new transposh_database($this);
        $this->admin =  new transposh_plugin_admin($this);
        $this->widget = new transposh_plugin_widget($this);
        $this->postpublish = new transposh_postpublish($this);

        // "global" vars
        $this->home_url = get_option('home');

        // Handle windows ('C:\wordpress')
        $local_dir = preg_replace("/\\\\/", "/", dirname(__FILE__));
        // Get last directory name
        $local_dir = preg_replace("/.*\//", "", $local_dir);
        $this->transposh_plugin_url = WP_PLUGIN_URL .'/'. $local_dir;
        // TODO - test on more platforms - this failed in 2.7.1 so I am reverting for now...
        //$tr_plugin_url= plugins_url('', __FILE__);

        $this->post_url = $this->transposh_plugin_url."/wp/transposh_ajax.php";

        logger("Object created". $_SERVER['REQUEST_URI'], 3);

        //Register some functions into wordpress
        logger (preg_replace( '|^' . preg_quote(WP_PLUGIN_DIR, '|') . '/|', '', __FILE__ ),4); // includes transposh dir and php
        add_filter('plugin_action_links_' .preg_replace( '|^' . preg_quote(WP_PLUGIN_DIR, '|') . '/|', '', __FILE__ ), array(&$this,'plugin_action_links'));
        add_filter('query_vars', array(&$this,'parameter_queryvars' ));
        add_filter('rewrite_rules_array', array(&$this,'update_rewrite_rules'));
        add_action('init', array(&$this,'on_init'),0); // really high priority
        add_action('parse_request', array(&$this,'on_parse_request'));
        add_action('plugins_loaded', array(&$this,'plugin_loaded'));
        add_action('shutdown', array(&$this,'on_shutdown'));
        add_action('wp_print_styles', array(&$this,'add_transposh_css'));
        add_action('wp_print_scripts', array(&$this,'add_transposh_js'));
        add_action('wp_head', array(&$this,'add_transposh_async'));
        add_action("sm_addurl",array(&$this,'add_sm_transposh_urls'));
        register_activation_hook(__FILE__, array(&$this,'plugin_activate'));
        register_deactivation_hook(__FILE__,array(&$this,'plugin_deactivate'));
    }

    /**
     * Check if page is special (one that we normally should not touch
     * @param string $url Url to check
     * @return boolean Is it a special page?
     */
    function is_special_page($url) {
        return (stripos($url,'/wp-login.php') !== FALSE ||
                        stripos($url,'/wp-admin/') !== FALSE ||
                        stripos($url,'/xmlrpc.php') !== FALSE);
    }
    /**
     * Called when the buffer containing the original page is flushed. Triggers the translation process.
     * @param string $buffer Original page
     * @return string Modified page buffer
     */
    function process_page(&$buffer) {

        $start_time = microtime(TRUE);

        // Refrain from touching the administrative interface and important pages
        if($this->is_special_page($_SERVER['REQUEST_URI'])) {
            logger("Skipping translation for admin pages", 3);
            return $buffer;
        }

        // This one fixed a bug transposh created with other pages (xml generator for other plugins - such as the nextgen gallery)
        // TODO: need to further investigate (will it be needed?)
        if($this->target_language == "")
            return $buffer;
        // Don't translate the default language unless specifically allowed to...
        if($this->options->is_default_language($this->target_language) && !$this->options->get_enable_default_translate()) {
            logger("Skipping translation for default language {$this->target_language}", 3);
            return $buffer;
        }

        logger("Translating " . $_SERVER['REQUEST_URI'] . " to: {$this->target_language}", 1);

        //translate the entire page
        $parse = new parser();
        $parse->fetch_translate_func = array(&$this->database,'fetch_translation');
        $parse->url_rewrite_func = array(&$this, 'rewrite_url');
        $parse->dir_rtl = (in_array ($this->target_language, $GLOBALS['rtl_languages']));
        $parse->lang = $this->target_language;
        $parse->is_edit_mode = $this->edit_mode;
        $parse->is_auto_translate = $this->is_auto_translate_permitted();
        // TODO - check this!
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

    /**
     * Setup a buffer that will contain the contents of the html page.
     * Once processing is completed the buffer will go into the translation process.
     */
    function on_init() {
        logger($_SERVER['REQUEST_URI'], 4);

        // the wp_rewrite is not available earlier so we can only set the enable_permalinks here
        if (is_object($GLOBALS['wp_rewrite']))
            if($GLOBALS['wp_rewrite']->using_permalinks() && $this->options->get_enable_permalinks()) {
                logger ("enabling permalinks");
                $this->enable_permalinks_rewrite = TRUE;
            }

        //set the callback for translating the page when it's done
        ob_start(array(&$this,"process_page"));
    }

    /**
     * Page generation completed - flush buffer.
     */
    function on_shutdown() {
        ob_flush();
    }

    /**
     * Update the url rewrite rules to include language identifier
     * @param array $rules Old rewrite rules
     * @return array New rewrite rules
     */
    function update_rewrite_rules($rules) {
        logger("Enter update_rewrite_rules");

        if(!$this->options->get_enable_permalinks()) {
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

            logger(": \t{$original_key} ---> {$original_value}",4);
        }

        logger("Exit update_rewrite_rules");
        return $newRules;
    }

    /**
     * Let WordPress know which parameters are of interest to us.
     * @param array $vars Original queried variables
     * @return array Modified array
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
     * @param WP $wp - here we get the WP class
     */
    function on_parse_request($wp) {
        logger ("on_parse_req");
        logger ($wp->query_vars);

        // first we get the target language
        $this->target_language = $wp->query_vars[LANG_PARAM];
        if (!$this->target_language)
            $this->target_language = $this->options->get_default_language();
        logger ("requested language: ".$this->target_language);

        // we'll go into this code of redirection only if we have options that need it (and no bot is involved, for the non-cookie)  and this is not a special page or one that is refered by our site
        if (($this->options->get_enable_detect_language() || $this->options->get_widget_allow_set_default_language()) &&
                !($this->is_special_page($_SERVER['REQUEST_URI']) || strpos($_SERVER['HTTP_REFERER'], $this->home_url) !== false)) {
            // we are starting a session if needed
            if (!session_id()) session_start();
            // no redirections if we already redirected in this session or we suspect cyclic redirections
            if (!$_SESSION['TR_REDIRECTED'] && !($_SERVER['HTTP_REFERER'] == $_SERVER['REQUEST_URI'])) {
                logger ('session redirection never happened (yet)');
                // we redirect once per session
                $_SESSION['TR_REDIRECTED'] = true;
                // redirect according to stored lng cookie, and than according to detection
                if (isset($_COOKIE['TR_LNG']) && $this->options->get_widget_allow_set_default_language()) {
                    if ($_COOKIE['TR_LNG'] != $this->target_language) {
                        $url = rewrite_url_lang_param($_SERVER["REQUEST_URI"], $this->home_url, $this->enable_permalinks_rewrite, $_COOKIE['TR_LNG'],$this->edit_mode);
                        if ($this->options->is_default_language($_COOKIE['TR_LNG'])) $url = cleanup_url($_SERVER["REQUEST_URI"], $this->home_url);
                        wp_redirect($url);
                        exit;
                    }
                } else {
                    $bestlang = prefered_language(explode(',',$this->options->get_viewable_langs()),$this->options->get_default_language());
                    // we won't redirect if we should not, or this is a presumable bot
                    if ($bestlang && $bestlang != $this->target_language && $this->options->get_enable_detect_language() && !(preg_match("#(bot|yandex|validator|google|jeeves|spider|crawler|slurp)#si", $_SERVER['HTTP_USER_AGENT']))) {
                        $url = rewrite_url_lang_param($_SERVER["REQUEST_URI"], $this->home_url, $this->enable_permalinks_rewrite, $bestlang,$this->edit_mode);
                        if ($this->options->is_default_language($bestlang)) $url = cleanup_url($_SERVER["REQUEST_URI"], $this->home_url);
                        wp_redirect($url);
                        exit;
                    }
                }
            } else {
                logger ('session was already redirected');
            }
        }
        // this method allows posts from the search box to maintain the language,
        // TODO - it has a bug of returning to original language following search, which can be resolved by removing search from widget urls, but maybe later...
        if (isset($wp->query_vars['s'])) {
            if ($this->options->get_enable_search_translate()) {
                add_action('pre_get_posts', array(&$this,'pre_post_search'));
                add_action('posts_where_request', array(&$this,'posts_where_request'));
            }
            if (get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url) && !get_language_from_url($_SERVER['REQUEST_URI'], $this->home_url)) {
                wp_redirect(rewrite_url_lang_param($_SERVER["REQUEST_URI"], $this->home_url, $this->enable_permalinks_rewrite, get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url), false));//."&stop=y");
                exit;
            }
        }
        if (isset($wp->query_vars[EDIT_PARAM]) && $wp->query_vars[EDIT_PARAM] && $this->is_editing_permitted()) {
            $this->edit_mode = true;
        }
        else {
            $this->edit_mode = false;
        }
        // We are removing our query vars since they are no longer needed and also make issues when a user select a static page as his home
        unset ($wp->query_vars[LANG_PARAM]);
        unset ($wp->query_vars[EDIT_PARAM]);
        logger ("edit mode: ".$this->edit_mode);
    }

    // TODO ? move to options?
    /**
     * Determine if the current user is allowed to translate.
     * @return boolean Is allowed to translate?
     */
    function is_translator() {
        //if anonymous translation is allowed - let anyone enjoy it
        if($this->options->get_anonymous_translation()) {
            return TRUE;
        }
        if(is_user_logged_in() && current_user_can(TRANSLATOR)) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Plugin activation
     */
    function plugin_activate() {
        logger("plugin_activate enter: " . dirname(__FILE__));

        $this->database->setup_db();
        // is it needed? the filter is already there? // TODO
        add_filter('rewrite_rules_array', array(&$this,'update_rewrite_rules'));
        $GLOBALS['wp_rewrite']->flush_rules();

        logger("plugin_activate exit: " . dirname(__FILE__));
        logger("testing name:".plugin_basename(__FILE__));
        logger("testing name2:".$this->get_plugin_name());
        //activate_plugin($plugin);
    }

    /**
     * Plugin deactivation
     */
    function plugin_deactivate() {
        logger("plugin_deactivate enter: " . dirname(__FILE__));

        // is it needed? the filter is already there? // TODO
        add_filter('rewrite_rules_array', array(&$this,'update_rewrite_rules'));
        $GLOBALS['wp_rewrite']->flush_rules();

        logger("plugin_deactivate exit: " . dirname(__FILE__));
    }

    /**
     * Callback from admin_notices - display error message to the admin.
     */
    function plugin_install_error() {
        logger("install error!", 0);

        echo '<div class="updated"><p>';
        echo 'Error has occured in the installation process of the translation plugin: <br>';

        echo $this->admin_msg;

        if (function_exists('deactivate_plugins') ) {
            // FIXME :wtf?
            deactivate_plugins(array(&$this,'get_plugin_name'), "translate.php");
            echo '<br> This plugin has been automatically deactivated.';
        }

        echo '</p></div>';
    }

    /**
     * Callback when all plugins have been loaded. Serves as the location
     * to check that the plugin loaded successfully else trigger notification
     * to the admin and deactivate plugin.
     * TODO - needs revisiting!
     */
    function plugin_loaded() {
        logger("Enter", 4);

        //TODO: fix this...
        $db_version = get_option(TRANSPOSH_DB_VERSION);

        if ($db_version != DB_VERSION) {
            $this->database->setup_db();
            //$this->admin_msg = "Translation database version ($db_version) is not comptabile with this plugin (". DB_VERSION . ")  <br>";

            logger("Updating database in plugin loaded", 0);
            //Some error occured - notify admin and deactivate plugin
            //add_action('admin_notices', 'plugin_install_error');
        }

        //TODO: fix this too...
        $db_version = get_option(TRANSPOSH_DB_VERSION);

        if ($db_version != DB_VERSION) {
            $this->admin_msg = "Failed to locate the translation table  <em> " . TRANSLATIONS_TABLE . "</em> in local database. <br>";

            logger("Messsage to admin: {$this->admin_msg}", 0);
            //Some error occured - notify admin and deactivate plugin
            add_action('admin_notices', array(&$this,'plugin_install_error'));
        }
    }

    /**
     * Gets the plugin name to be used in activation/decativation hooks.
     * Keep only the file name and its containing directory. Don't use the full
     * path as it will break when using symbollic links.
     * TODO - check!!!
     * @return string
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

    /**
     * Add custom css, i.e. transposh.css
     */
    function add_transposh_css() {
        if(!$this->is_editing_permitted() && !$this->is_auto_translate_permitted()) {
            //translation not allowed - no need for the transposh.css
            return;
        }
        //include the transposh.css
        wp_enqueue_style("transposh","{$this->transposh_plugin_url}/css/transposh.css",array(),TRANSPOSH_PLUGIN_VER);
        // we have to load the jquery-ui css just in some cases
//        if ($this->edit_mode || $this->options->get_widget_progressbar())
//            wp_enqueue_style("jquery","http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/themes/ui-lightness/jquery-ui.css",array(),'1.0');
        logger("Added transposh_css",4);
    }

    /**
     * Insert references to the javascript files used in the translated version of the page.
     */
    function add_transposh_js() {
        //not in any translation mode - no need for any js.
        if (!$this->edit_mode && !$this->is_auto_translate_permitted()) {
            return;
        }

        if($this->edit_mode || $this->is_auto_translate_permitted()) {
            //TODO - fix (onetime var)
            wp_deregister_script('jquery');
            wp_enqueue_script("jquery","http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js",array(),'1.3.2');
            // toying around - for later...
            //wp_enqueue_script("jquery","http://code.jquery.com/jquery-1.4a2.min.js",array(),'1.4a2');
            //wp_enqueue_script("jquery","http://api.jquery.com/scripts/jquery-1.4.js",array(),'1.4b');
        }
    }

    /**
     * Inserts the transposh async loading in the head.
     * @return nothing
     */
    function add_transposh_async() {
        if (!$this->edit_mode && !$this->is_auto_translate_permitted()) {
            return;
        }

        echo "<script type=\"text/javascript\">";
        echo "var t_jp=t_jp || [];";
        echo "t_jp.post_url='{$this->post_url}';";
        echo "t_jp.plugin_url='{$this->transposh_plugin_url}';";
        echo "t_jp.edit=".($this->edit_mode? 'true' : 'false').";";
        //echo "t_jp.rtl=".(in_array ($this->target_language, $GLOBALS['rtl_languages'])? 'true' : 'false').";";
        echo "t_jp.lang='{$this->target_language}';";
        echo "t_jp.prefix='".SPAN_PREFIX."';";
        echo "t_jp.msnkey='{$this->options->get_msn_key()}';";
        echo "t_jp.progress=".($this->edit_mode || $this->options->get_widget_progressbar() ? 'true' : 'false').";";

        /*
         *         // let's lazy load! (worked, didn't make sense)
//                jQuery(document).ready(
                jQuery(window).load(
    function() {
                jQuery.ajax({
			type: 'GET',
			url: '{$this->transposh_plugin_url}/js/transposh.js?ver=".TRANSPOSH_PLUGIN_VER."',
			dataType: 'script',
                        cache: true
		});
  });
        */
        echo "var tp = document.createElement('script'); tp.type = 'text/javascript'; tp.async = true;";
        echo "tp.src = '{$this->transposh_plugin_url}/js/transposh.js?ver=".TRANSPOSH_PLUGIN_VER."';";
        echo "(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(tp);";
        echo"</script>";
    }

    /**
     * Determine if the currently selected language (taken from the query parameters) is in the admin's list
     * of editable languages and the current user is allowed to translate.
     * @return boolean Is translation allowed?
     */
    // TODO????
    function is_editing_permitted() {
        // editing is permitted for translators only
        if(!$this->is_translator()) return false;
        // and only on the non-default lang (unless strictly specified)
        if (!$this->options->get_enable_default_translate() && $this->options->is_default_language($this->target_language)) return false;

        return $this->options->is_editable_language($this->target_language);
    }

    /**
     * Determine if the currently selected language (taken from the query parameters) is in the admin's list
     * of editable languages and that automatic translation has been enabled.
     * Note that any user can auto translate. i.e. ignore permissions.
     * @return boolean Is automatic translation allowed?
     */
    function is_auto_translate_permitted() {
        logger("checking auto translatability",4);

        if(!$this->options->get_enable_auto_translate()) return false;
        // auto translate is not enabled for default target language when enable default is disabled
        if (!$this->options->get_enable_default_translate() && $this->options->is_default_language($this->target_language)) return false;

        return $this->options->is_editable_language($this->target_language);
    }

    /**
     * Callback from parser allowing to overide the global setting of url rewriting using permalinks.
     * Some urls should be modified only by adding parameters and should be identified by this
     * function.
     * @param $href Original href
     * @return boolean Modified href
     */
    function rewrite_url($href) {
        $use_params = FALSE;
        logger ("got: $href",5);

        // Ignore urls not from this site
        if(stripos($href, $this->home_url) === FALSE) {
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
        $use_params = !$this->enable_permalinks_rewrite;

        $href = rewrite_url_lang_param($href,$this->home_url,$this->enable_permalinks_rewrite, $this->target_language, $this->edit_mode, $use_params);
        logger ("rewritten: $href",4);
        return $href;
    }

    /**
     * This function adds the word setting in the plugin list page
     * @param array $links Links that appear next to the plugin
     * @return array Now with settings
     */
    function plugin_action_links( $links ) {
        logger ("in plugin action");
        return array_merge( array('<a href="' . admin_url('options-general.php?page='.TRANSPOSH_ADMIN_PAGE_NAME) . '">Settings</a>'), $links );
    }

    /**
     * We use this to "steal" the search variables
     * @param WP_Query $query
     */
    function pre_post_search ($query) {
        logger ('pre post',4);
        logger ($query->query_vars);
        // we hide the search query var from further proccesing, because we do this later
        if ($query->query_vars['s']) {
            $this->search_s = $query->query_vars['s'];
            $query->query_vars['s'] = "";
        }
    }

    /**
     * This is where we change the logic to include originals for search translation
     * @param string $where Original where clause for getting posts
     * @return string Modified where
     */
    function posts_where_request ($where) {

        logger ($where);
        // from query.php line 1742 (v2.8.6)
        // If a search pattern is specified, load the posts that match
        $q = &$GLOBALS['wp_query']->query_vars;
        // returning the saved query strings
        $q['s'] = $this->search_s;
        if ( !empty($q['s']) ) {
            // added slashes screw with quote grouping when done early, so done later
            $q['s'] = stripslashes($q['s']);
            if ( !empty($q['sentence']) ) {
                $q['search_terms'] = array($q['s']);
            } else {
                preg_match_all('/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $q['s'], $matches);
                $q['search_terms'] = array_map(create_function('$a', 'return trim($a, "\\"\'\\n\\r ");'), $matches[0]);
            }
            $n = !empty($q['exact']) ? '' : '%';
            $searchand = '';
            foreach( (array) $q['search_terms'] as $term) {
                // now we'll get possible translations for this term
                $possible_original_terms = $this->database->get_orignal_phrases_for_search_term($term, $this->target_language);
                $term = addslashes_gpc($term);
                $search .= "{$searchand}(({$GLOBALS['wpdb']->posts}.post_title LIKE '{$n}{$term}{$n}') OR ({$GLOBALS['wpdb']->posts}.post_content LIKE '{$n}{$term}{$n}')";
                foreach( (array) $possible_original_terms as $term) {
                    $term = addslashes_gpc($term);
                    $search .= " OR ({$GLOBALS['wpdb']->posts}.post_title LIKE '{$n}{$term}{$n}') OR ({$GLOBALS['wpdb']->posts}.post_content LIKE '{$n}{$term}{$n}')";
                }
                // we moved this to here, so it really closes all of them
                $search .= ")";
                $searchand = ' AND ';
            }
            $term = $GLOBALS['wpdb']->escape($q['s']);
            if (empty($q['sentence']) && count($q['search_terms']) > 1 && $q['search_terms'][0] != $q['s'] )
                $search .= " OR ({$GLOBALS['wpdb']->posts}.post_title LIKE '{$n}{$term}{$n}') OR ({$GLOBALS['wpdb']->posts}.post_content LIKE '{$n}{$term}{$n}')";

            if ( !empty($search) ) {
                $search = " AND ({$search}) ";
                if ( !is_user_logged_in() )
                    $search .= " AND ({$GLOBALS['wpdb']->posts}.post_password = '') ";
            }
        }
        logger ($search);
        return $search.$where;
    }

    /**
     * This function integrates with google sitemap generator, and adds for each viewable language, the rest of the languages url
     * Also - priority is reduced by 0.2
     * And this requires the following line at the sitemap-core.php, add-url function (line 1509 at version 3.2.2)
     * do_action('sm_addurl', &$page);
     * @param GoogleSitemapGeneratorPage $sm_page Object containing the page information
     */
    function add_sm_transposh_urls(&$sm_page) {
        logger ("in sitemap add url: ".$sm_page->GetUrl()." ".$sm_page->GetPriority());
        // we need the generator object (we know it must exist...)
        $generatorObject = &GoogleSitemapGenerator::GetInstance();
        // we reduce the priorty by 0.2, but not below zero
        $sm_page->SetProprity(max($sm_page->GetPriority() - 0.2, 0));

        $viewable_langs = explode(",",$this->options->get_viewable_langs());
        foreach ($viewable_langs as $lang) {
            if (!$this->options->is_default_language($lang)) {
                $newloc = rewrite_url_lang_param($sm_page->GetUrl(), $this->home_url, $this->enable_permalinks_rewrite, $lang, false);
                $sm_page->SetUrl($newloc);
                $generatorObject->AddElement($sm_page);
            }
        }
    }
}
$my_transposh_plugin = new transposh_plugin();

?>