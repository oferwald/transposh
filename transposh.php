<?php

/*
  Plugin Name: Transposh Translation Filter
  Plugin URI: http://transposh.org/
  Description: Translation filter for WordPress, After enabling please set languages at the <a href="admin.php?page=tp_main">the options page</a> Want to help? visit our development site at <a href="http://trac.transposh.org/">trac.transposh.org</a>.
  Author: Team Transposh
  Version: %VERSION%
  Author URI: http://transposh.org/
  License: GPL (http://www.gnu.org/licenses/gpl.txt)
  Text Domain: transposh
  Domain Path: /langs
 */

/*
 * Transposh v%VERSION%
 * http://transposh.org/
 *
 * Copyright %YEAR%, Team Transposh
 * Licensed under the GPL Version 2 or higher.
 * http://transposh.org/license
 *
 * Date: %DATE%
 */

//avoid direct calls to this file where wp core files not present
if (!function_exists('add_action')) {
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
require_once("wp/transposh_backup.php");
require_once("wp/transposh_3rdparty.php");
//require_once("wp/transposh_wpmenu.php");

/**
 * This class represents the complete plugin
 */
class transposh_plugin {
    // List of contained objects

    /** @var transposh_plugin_options An options object */
    public $options;

    /** @var transposh_plugin_admin Admin page */
    private $admin;

    /** @var transposh_plugin_widget Widget control */
    public $widget;

    /** @var transposh_database The database class */
    public $database;

    /** @var transposh_postpublish Happens after editing */
    public $postpublish;

    /** @var transposh_3rdparty Happens after editing */
    private $third_party;
    // list of properties
    /** @var string The site url */
    public $home_url;

    /** @var a url of the request, assuming there was no language */
    private $clean_url;

    /** @var string The url to the plugin directory */
    public $transposh_plugin_url;

    /** @var string The directory of the plugin */
    public $transposh_plugin_dir;

    /** @var string Plugin main file and dir */
    public $transposh_plugin_basename;

    /** @var boolean Enable rewriting of URLs */
    public $enable_permalinks_rewrite;

    /** @var string The language to translate the page to, from params */
    public $target_language;

    /** @var string The language extracted from the url */
    public $tgl;

    /** @var boolean Are we currently editing the page? */
    public $edit_mode;

    /** @var string Error message displayed for the admin in case of failure */
    private $admin_msg;

    /** @var string Saved search variables */
    private $search_s;

    /** @var variable to make sure we only attempt to fix the url once, could have used remove_filter */
    private $got_request = false;

    /** @var might be that page is json... */
    private $attempt_json = false;

    /** @var boolean Is the wp_redirect being called by transposh? */
    private $transposh_redirect = false;

    /** @var boolean Did we get to process but got an empty buffer with no language? (someone flushed us) */
    private $tried_buffer = false;

    /** @var boolean Do I need to check for updates by myself? After wordpress checked his */
    private $do_update_check = false;

    /**
     * class constructor
     */
    function transposh_plugin() {
        // create and initialize sub-objects
        $this->options = new transposh_plugin_options();
        $this->database = new transposh_database($this);
        $this->admin = new transposh_plugin_admin($this);
        $this->widget = new transposh_plugin_widget($this);
        $this->postpublish = new transposh_postpublish($this);
        $this->third_party = new transposh_3rdparty($this);

        // initialize logger
        if ($this->options->debug_enable) {
            $GLOBALS['tp_logger'] = tp_logger::getInstance(true);
            $GLOBALS['tp_logger']->show_caller = true;
            $GLOBALS['tp_logger']->set_debug_level($this->options->debug_loglevel);
            $GLOBALS['tp_logger']->set_log_file($this->options->debug_logfile);
            $GLOBALS['tp_logger']->set_remoteip($this->options->debug_remoteip);
        }

        // "global" vars
        $this->home_url = get_option('home');

        // Handle windows ('C:\wordpress')
        $local_dir = preg_replace("/\\\\/", "/", dirname(__FILE__));
        // Get last directory name
        $local_dir = preg_replace("/.*\//", "", $local_dir);
        $this->transposh_plugin_url = preg_replace('#^https?://#', '//', WP_PLUGIN_URL . '/' . $local_dir);
        // TODO - test on more platforms - this failed in 2.7.1 so I am reverting for now...
        //$tr_plugin_url= plugins_url('', __FILE__);

        $this->transposh_plugin_dir = plugin_dir_path(__FILE__);

        if ($this->options->debug_enable)
            tp_logger('Transposh object created: ' . $_SERVER['REQUEST_URI'], 3);

        $this->transposh_plugin_basename = plugin_basename(__FILE__);
        //Register some functions into wordpress
        if ($this->options->debug_enable) {
            //tp_logger(preg_replace('|^' . preg_quote(WP_PLUGIN_DIR, '|') . '/|', '', __FILE__), 4); // includes transposh dir and php
            // tp_logger($this->get_plugin_name());
            tp_logger(plugin_basename(__FILE__));
        }

        // TODO: get_class_methods to replace said mess, other way?
        add_filter('plugin_action_links_' . $this->transposh_plugin_basename, array(&$this, 'plugin_action_links'));
        add_filter('query_vars', array(&$this, 'parameter_queryvars'));
        add_filter('rewrite_rules_array', array(&$this, 'update_rewrite_rules'));
        if ($this->options->enable_url_translate) {
            add_filter('request', array(&$this, 'request_filter'));
        }
        add_filter('comment_post_redirect', array(&$this, 'comment_post_redirect_filter'));
        add_filter('comment_text', array(&$this, 'comment_text_wrap'), 9999); // this is a late filter...
        add_action('init', array(&$this, 'on_init'), 0); // really high priority
//        add_action('admin_init', array(&$this, 'on_admin_init')); might use to mark where not to work?
        add_action('parse_request', array(&$this, 'on_parse_request'), 0); // should have high enough priority
        add_action('plugins_loaded', array(&$this, 'plugin_loaded'));
        add_action('shutdown', array(&$this, 'on_shutdown'));
        add_action('wp_print_styles', array(&$this, 'add_transposh_css'));
        add_action('wp_print_scripts', array(&$this, 'add_transposh_js'));
        if (!$this->options->dont_add_rel_alternate) {
            add_action('wp_head', array(&$this, 'add_rel_alternate'));
        }
//        add_action('wp_head', array(&$this,'add_transposh_async'));
        add_action('transposh_backup_event', array(&$this, 'run_backup'));
        add_action('transposh_oht_event', array(&$this, 'run_oht'));
        add_action('comment_post', array(&$this, 'add_comment_meta_settings'), 1);
        // our translation proxy
//        add_action('wp_ajax_tp_gp', array(&$this, 'on_ajax_nopriv_tp_gp'));
//        add_action('wp_ajax_nopriv_tp_gp', array(&$this, 'on_ajax_nopriv_tp_gp'));
        add_action('wp_ajax_tp_tp', array(&$this, 'on_ajax_nopriv_tp_tp')); // translate suggest proxy
        add_action('wp_ajax_nopriv_tp_tp', array(&$this, 'on_ajax_nopriv_tp_tp'));
        add_action('wp_ajax_tp_oht', array(&$this, 'on_ajax_nopriv_tp_oht'));
        add_action('wp_ajax_nopriv_tp_oht', array(&$this, 'on_ajax_nopriv_tp_oht'));
        // ajax actions in editor
        // TODO - remove some for non translators
        add_action('wp_ajax_tp_history', array(&$this, 'on_ajax_nopriv_tp_history'));
        add_action('wp_ajax_nopriv_tp_history', array(&$this, 'on_ajax_nopriv_tp_history'));
        add_action('wp_ajax_tp_translation', array(&$this, 'on_ajax_nopriv_tp_translation'));
        add_action('wp_ajax_nopriv_tp_translation', array(&$this, 'on_ajax_nopriv_tp_translation'));
        add_action('wp_ajax_tp_ohtcallback', array(&$this, 'on_ajax_nopriv_tp_ohtcallback'));
        add_action('wp_ajax_nopriv_tp_ohtcallback', array(&$this, 'on_ajax_nopriv_tp_ohtcallback'));
        add_action('wp_ajax_tp_trans_alts', array(&$this, 'on_ajax_nopriv_tp_trans_alts'));
        add_action('wp_ajax_nopriv_tp_trans_alts', array(&$this, 'on_ajax_nopriv_tp_trans_alts'));
        add_action('wp_ajax_tp_cookie', array(&$this, 'on_ajax_nopriv_tp_cookie'));
        add_action('wp_ajax_nopriv_tp_cookie', array(&$this, 'on_ajax_nopriv_tp_cookie'));
        add_action('wp_ajax_tp_cookie_bck', array(&$this, 'on_ajax_nopriv_tp_cookie_bck'));
        add_action('wp_ajax_nopriv_tp_cookie_bck', array(&$this, 'on_ajax_nopriv_tp_cookie_bck'));

        // For super proxy
        add_action('superproxy_reg_event', array(&$this, 'superproxy_reg'));
        if ($this->options->enable_superproxy) {
            add_action('wp_ajax_proxy', array(&$this, 'on_ajax_nopriv_proxy'));
            add_action('wp_ajax_nopriv_proxy', array(&$this, 'on_ajax_nopriv_proxy'));
        }

        // comment_moderation_text - future filter TODO
        // full post wrapping (should happen late)
        add_filter('the_content', array(&$this, 'post_content_wrap'), 9999);
        add_filter('the_excerpt', array(&$this, 'post_content_wrap'), 9999);
        add_filter('the_title', array(&$this, 'post_wrap'), 9999, 2);

        // allow to mark the language?
//        add_action('admin_menu', array(&$this, 'transposh_post_language'));
//        add_action('save_post', array(&$this, 'transposh_save_post_language'));
        //TODO add_action('manage_comments_nav', array(&$this,'manage_comments_nav'));
        //TODO comment_row_actions (filter)
        // Intergrating with the gettext interface
        if ($this->options->transposh_gettext_integration) {
            add_filter('gettext', array(&$this, 'transposh_gettext_filter'), 10, 3);
            add_filter('gettext_with_context', array(&$this, 'transposh_gettext_filter'), 10, 3);
            add_filter('ngettext', array(&$this, 'transposh_ngettext_filter'), 10, 4);
            add_filter('ngettext_with_context', array(&$this, 'transposh_ngettext_filter'), 10, 4);
            add_filter('locale', array(&$this, 'transposh_locale_filter'));
        }

        // internal update mechnism
        add_filter('http_request_args', array(&$this, 'filter_wordpress_org_update'), 10, 2);
        add_filter('pre_set_site_transient_update_plugins', array(&$this, 'check_for_plugin_update'));
        add_filter('plugins_api', array(&$this, 'plugin_api_call'), 10, 3);

        // debug function for bad redirects
        add_filter('wp_redirect', array(&$this, 'on_wp_redirect'), 10, 2);
        add_filter('redirect_canonical', array(&$this, 'on_redirect_canonical'), 10, 2);

        // support shortcodes
        add_shortcode('tp', array(&$this, 'tp_shortcode'));
        add_shortcode('tpe', array(&$this, 'tp_shortcode'));
        //
        // FUTURE add_action('update-custom_transposh', array(&$this, 'update'));
        // CHECK TODO!!!!!!!!!!!!
        $this->tgl = transposh_utils::get_language_from_url($_SERVER['REQUEST_URI'], $this->home_url);
        if (!$this->options->is_active_language($this->tgl)) {
            $this->tgl = '';
        }

        register_activation_hook(__FILE__, array(&$this, 'plugin_activate'));
        register_deactivation_hook(__FILE__, array(&$this, 'plugin_deactivate'));
    }

    /**
     * Attempt to fix a wp_redirect being called by someone else to include the language
     * hoping for no cycles
     * @param string $location
     * @param int $status
     * @return string
     */
    function on_wp_redirect($location, $status) {
        // no point in mangling redirection if its our own or its the default language
        if ($this->transposh_redirect || $this->options->is_default_language($this->target_language)) {
            return $location;
        }
        tp_logger($status . ' ' . $location);
        // $trace = debug_backtrace();
        // tp_logger($trace);
        // tp_logger($this->target_language);
        $location = $this->rewrite_url($location);
        return $location;
    }

    /**
     * Internally used by transposh redirection, to avoid being rewritten by self
     * assuming we know what we are doing when redirecting
     * @param string $location
     * @param int $status
     */
    function tp_redirect($location, $status = 302) {
        $this->transposh_redirect = true;
        wp_redirect($location, $status);
    }

    /**
     * Function to fix canonical redirection for some translated urls (such as tags with params)
     * @param string $red - url wordpress assumes it will redirect to
     * @param string $req - url that was originally requested
     * @return mixed false if redirect unneeded - new url if we think we should
     */
    function on_redirect_canonical($red, $req) {
        tp_logger("$red .. $req", 4);
        // if the urls are actually the same, don't redirect (same - if it had our proper take care of)
        if ($this->rewrite_url($red) == urldecode($req)) {
            return false;
        }
        // if this is not the default language, we need to make sure it redirects to what we believe is the proper url
        if (!$this->options->is_default_language($this->target_language)) {
            $red = str_replace(array('%2F', '%3A', '%3B', '%3F', '%3D', '%26'), array('/', ':', ';', '?', '=', '&'), urlencode($this->rewrite_url($red)));
        }
        return $red;
    }

    function get_clean_url() {
        if (isset($this->clean_url)) {
            return $this->clean_url;
        }
        //remove any language identifier and find the "clean" url, used for posting and calculating urls if needed
        $this->clean_url = transposh_utils::cleanup_url($_SERVER['REQUEST_URI'], $this->home_url, true);
        // we need this if we are using url translations
        if ($this->options->enable_url_translate) {
            $this->clean_url = transposh_utils::get_original_url($this->clean_url, '', $this->target_language, array($this->database, 'fetch_original'));
        }
        return $this->clean_url;
    }

//    function update() {file_location
//        require_once('./admin-header.php');

    /* 	$nonce = 'upgrade-plugin_' . $plugin;
      $url = 'update.php?action=upgrade-plugin&plugin=' . $plugin;

      $upgrader = new Plugin_Upgrader( new Plugin_Upgrader_Skin( compact('title', 'nonce', 'url', 'plugin') ) );
      $upgrader->upgrade($plugin);
     */
//        include('./admin-footer.php');
//    }

    /**
     * Check if page is special (one that we normally should not touch
     * @param string $url Url to check
     * @return boolean Is it a special page?
     */
    function is_special_page($url) {
        return ( stripos($url, '/wp-login.php') !== FALSE ||
                stripos($url, '/wp-admin/') !== FALSE ||
                stripos($url, '/wp-comments-post') !== FALSE ||
                stripos($url, '/main-sitemap.xsl') !== FALSE || //YOAST?                
                stripos($url, '.xsl') !== FALSE || //YOAST?                
                stripos($url, '.xml') !== FALSE || //YOAST?                
                stripos($url, '/xmlrpc.php') !== FALSE);
    }

    /**
     * Called when the buffer containing the original page is flushed. Triggers the translation process.
     * @param string $buffer Original page
     * @return string Modified page buffer
     */
    function process_page($buffer) { //php7?
        /*        if (!$this->target_language) {
          global $wp;
          $this->on_parse_request($wp);
          } */
        tp_logger('processing page hit with language:' . $this->target_language, 1);
        $bad_content = false;
        foreach (headers_list() as $header) {
            if (stripos($header, 'Content-Type:') !== false) {
                tp_logger($header);
                if (stripos($header, 'text') === false && stripos($header, 'json') === false) {
                    tp_logger("won't do that - $header");
                    $bad_content = true;
                }
            }
        }
        $start_time = microtime(TRUE);

        // Refrain from touching the administrative interface and important pages
        if ($this->is_special_page($_SERVER['REQUEST_URI']) && !$this->attempt_json) {
            tp_logger("Skipping translation for admin pages", 3);
        } elseif ($bad_content) {
            tp_logger("Seems like content we should not handle");
        }
        // This one fixed a bug transposh created with other pages (xml generator for other plugins - such as the nextgen gallery)
        // TODO: need to further investigate (will it be needed?)
        elseif ($this->target_language == '') {
            tp_logger("Skipping translation where target language is unset", 3);
            if (!$buffer) {
                tp_logger("seems like we had a premature flushing");
                $this->tried_buffer = true;
            }
        }
        // Don't translate the default language unless specifically allowed to...
        elseif ($this->options->is_default_language($this->target_language) && !$this->options->enable_default_translate) {
            tp_logger("Skipping translation for default language {$this->target_language}", 3);
        } else {
            // This one allows to redirect to a static element which we can find, since the redirection will remove
            // the target language, we are able to avoid nasty redirection loops
            if (is_404()) {
                global $wp;
                if (isset($wp->query_vars['pagename']) && file_exists(ABSPATH . $wp->query_vars['pagename'])) { // Hmm
                    tp_logger('Redirecting a static file ' . $wp->query_vars['pagename'], 1);
                    $this->tp_redirect('/' . $wp->query_vars['pagename'], 301);
                }
            }

            tp_logger("Translating {$_SERVER['REQUEST_URI']} to: {$this->target_language} for: {$_SERVER['REMOTE_ADDR']}", 1);

            //translate the entire page
            $parse = new tp_parser();
            $parse->fetch_translate_func = array(&$this->database, 'fetch_translation');
            $parse->prefetch_translate_func = array(&$this->database, 'prefetch_translations');
            $parse->url_rewrite_func = array(&$this, 'rewrite_url');
            $parse->split_url_func = array(&$this, 'split_url');
            $parse->dir_rtl = (in_array($this->target_language, transposh_consts::$rtl_languages));
            $parse->lang = $this->target_language;
            $parse->default_lang = $this->options->is_default_language($this->target_language);
            $parse->is_edit_mode = $this->edit_mode;
            $parse->might_json = $this->attempt_json;
            $parse->is_auto_translate = $this->is_auto_translate_permitted();
            $parse->allow_ad = $this->options->widget_remove_logo;
            // TODO - check this!
            if (stripos($_SERVER['REQUEST_URI'], '/feed/') !== FALSE) {
                tp_logger("in rss feed!", 2);
                $parse->is_auto_translate = false;
                $parse->is_edit_mode = false;
                $parse->feed_fix = true;
            }
            $parse->change_parsing_rules(!$this->options->parser_dont_break_puncts, !$this->options->parser_dont_break_numbers, !$this->options->parser_dont_break_entities);
            $buffer = $parse->fix_html($buffer);

            $end_time = microtime(TRUE);
            tp_logger('Translation completed in ' . ($end_time - $start_time) . ' seconds', 1);
        }

        return $buffer;
    }

//    function on_admin_init() {
//        tp_logger("admin init called");
//    }

    /**
     * Setup a buffer that will contain the contents of the html page.
     * Once processing is completed the buffer will go into the translation process.
     */
    function on_init() {
        tp_logger('init ' . $_SERVER['REQUEST_URI'], 4);

        // the wp_rewrite is not available earlier so we can only set the enable_permalinks here
        if (is_object($GLOBALS['wp_rewrite'])) {
            if ($GLOBALS['wp_rewrite']->using_permalinks() && $this->options->enable_permalinks) {
                tp_logger("enabling permalinks");
                $this->enable_permalinks_rewrite = TRUE;
            }
        }

        // this is an ajax special case, currently crafted and tested on buddy press, lets hope this won't make hell break loose.
        // it basically sets language based on referred when accessing wp-load.php (which is the way bp does ajax)
        tp_logger(substr($_SERVER['SCRIPT_FILENAME'], -11), 5);
        if (substr($_SERVER['SCRIPT_FILENAME'], -11) == 'wp-load.php') {
            $this->target_language = transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url);
            $this->attempt_json = true;
        }

        //buddypress old activity
        if (isset($_POST['action']) && $_POST['action'] == 'activity_get_older_updates') {
            $this->target_language = transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url);
            $this->attempt_json = true;
        }
        //alm news
        if (isset($_GET['action']) && $_GET['action'] == 'alm_query_posts') {
	   // $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_REFERER'];
            $this->target_language = transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url);
        }
        //woocommerce_update_order_review
        if (isset($_POST['action']) && $_POST['action'] == 'woocommerce_update_order_review') {
            $this->target_language = transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url);
            $this->attempt_json = true;
        }

        if (isset($_GET['wc-ajax']) && $_GET['wc-ajax'] == 'update_order_review') {
            $this->target_language = transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url);
            $this->attempt_json = true;
        }

        //woocommerce_get_refreshed_fragments
        if (isset($_POST['action']) && $_POST['action'] == 'woocommerce_get_refreshed_fragments') {
            $this->target_language = transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url);
            $this->attempt_json = true;
        }

        if (isset($_POST['action']) && $_POST['action'] == 'woocommerce_add_to_cart') {
            $this->target_language = transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url);
            $this->attempt_json = true;
        }

        tp_logger($_SERVER['REQUEST_URI'], 5);
        if (strpos($_SERVER['REQUEST_URI'], '/wpv-ajax-pagination/') === true) {
            tp_logger('wpv pagination', 5);
            $this->target_language = transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url);
        }

        // load translation files for transposh
        load_plugin_textdomain(TRANSPOSH_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/langs');

        //set the callback for translating the page when it's done
        ob_start(array(&$this, "process_page"));
    }

    /**
     * Page generation completed - flush buffer.
     */
    function on_shutdown() {
        //TODO !!!!!!!!!!!! ob_flush();
    }

    /**
     * Update the url rewrite rules to include language identifier
     * @param array $rules Old rewrite rules
     * @return array New rewrite rules
     */
    function update_rewrite_rules($rules) {
        tp_logger("Enter update_rewrite_rules", 2);

        if (!$this->options->enable_permalinks) {
            tp_logger("Not touching rewrite rules - permalinks modification disabled by admin", 2);
            return $rules;
        }

        $newRules = array();
        $lang_prefix = "(" . str_replace(',', '|', $this->options->viewable_languages) . ")/";

        $lang_parameter = "&" . LANG_PARAM . '=$matches[1]';

        //catch the root url
        $newRules[$lang_prefix . "?$"] = "index.php?lang=\$matches[1]";
        tp_logger("\t {$lang_prefix} ?$  --->  index.php?lang=\$matches[1]", 4);

        foreach ($rules as $key => $value) {
            $original_key = $key;
            $original_value = $value;

            $key = $lang_prefix . $key;

            //Shift existing matches[i] a step forward as we pushed new elements
            //in the beginning of the expression
            for ($i = 9; $i > 0; $i--) {
                $value = str_replace('[' . $i . ']', '[' . ($i + 1) . ']', $value);
            }

            $value .= $lang_parameter;

            tp_logger("\t $key ---> $value", 2);


            $newRules[$key] = $value;
            $newRules[$original_key] = $original_value;

            tp_logger(": \t{$original_key} ---> {$original_value}", 4);
        }

        tp_logger("Exit update_rewrite_rules", 2);
        return $newRules;
    }

    //function flush_transposh_rewrite_rules() {
    //add_filter('rewrite_rules_array', array(&$this, 'update_rewrite_rules'));
//        $GLOBALS['wp_rewrite']->flush_rules();        
    //}

    /**
     * Let WordPress know which parameters are of interest to us.
     * @param array $vars Original queried variables
     * @return array Modified array
     */
    function parameter_queryvars($vars) {
        tp_logger('inside query vars', 4);
        $vars[] = LANG_PARAM;
        $vars[] = EDIT_PARAM;
        tp_logger($vars, 4);
        return $vars;
    }

    /**
     * Grabs and set the global language and edit params, they should be here
     * @param WP $wp - here we get the WP class
     */
    function on_parse_request($wp) {
        tp_logger('on_parse_req', 3);
        tp_logger($wp->query_vars);

        // fix for custom-permalink (and others that might be double parsing?)
        if ($this->target_language) {
            return;
        }

        // first we get the target language
        /*        $this->target_language = (isset($wp->query_vars[LANG_PARAM])) ? $wp->query_vars[LANG_PARAM] : '';
          if (!$this->target_language)
          $this->target_language = $this->options->default_language;
          tp_logger("requested language: {$this->target_language}"); */
        // TODO TOCHECK!!!!!!!!!!!!!!!!!!!!!!!!!!1
        $this->target_language = $this->tgl;
        if (!$this->target_language) {
            $this->target_language = $this->options->default_language;
        }
        tp_logger("requested language: {$this->target_language}", 3);

        if ($this->tried_buffer) {
            tp_logger("we will retrigger the output buffering");
            ob_start(array(&$this, "process_page"));
        }

        // make themes that support rtl - go rtl http://wordpress.tv/2010/05/01/yoav-farhi-right-to-left-themes-sf10
        if (in_array($this->target_language, transposh_consts::$rtl_languages)) {
            global $wp_locale;
            $wp_locale->text_direction = 'rtl';
        }

        // we'll go into this code of redirection only if we have options that need it (and no bot is involved, for the non-cookie)
        //  and this is not a special page or one that is refered by our site
        // bots can skip this altogether
        if (($this->options->enable_detect_redirect || $this->options->widget_allow_set_deflang) &&
                !($this->is_special_page($_SERVER['REQUEST_URI']) || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $this->home_url) !== false)) &&
                !(transposh_utils::is_bot())) {
            // we are starting a session if needed
            if (!session_id()) {
                session_start();
            }
            // no redirections if we already redirected in this session or we suspect cyclic redirections
            if (!isset($_SESSION['TR_REDIRECTED']) && !(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] == $_SERVER['REQUEST_URI'])) {
                tp_logger('session redirection never happened (yet)', 2);
                // we redirect once per session
                $_SESSION['TR_REDIRECTED'] = true;
                // redirect according to stored lng cookie, and than according to detection
                if (isset($_COOKIE['TR_LNG']) && $this->options->widget_allow_set_deflang) {
                    if ($_COOKIE['TR_LNG'] != $this->target_language) {
                        $url = transposh_utils::rewrite_url_lang_param($_SERVER["REQUEST_URI"], $this->home_url, $this->enable_permalinks_rewrite, $_COOKIE['TR_LNG'], $this->edit_mode);
                        if ($this->options->is_default_language($_COOKIE['TR_LNG']))
                        //TODO - fix wrt translation
                            $url = transposh_utils::cleanup_url($_SERVER["REQUEST_URI"], $this->home_url);
                        tp_logger("redirected to $url because of cookie", 4);
                        $this->tp_redirect($url);
                        exit;
                    }
                } else {
                    $bestlang = transposh_utils::prefered_language(explode(',', $this->options->viewable_languages), $this->options->default_language);
                    // we won't redirect if we should not, or this is a presumable bot
                    if ($bestlang && $bestlang != $this->target_language && $this->options->enable_detect_redirect) {
                        $url = transposh_utils::rewrite_url_lang_param($_SERVER['REQUEST_URI'], $this->home_url, $this->enable_permalinks_rewrite, $bestlang, $this->edit_mode);
                        if ($this->options->is_default_language($bestlang))
                        //TODO - fix wrt translation
                            $url = transposh_utils::cleanup_url($_SERVER['REQUEST_URI'], $this->home_url);
                        tp_logger("redirected to $url because of bestlang", 4);
                        $this->tp_redirect($url);
                        exit;
                    }
                }
            } else {
                tp_logger('session was already redirected', 2);
            }
        }
        // this method allows posts from the search box to maintain the language,
        // TODO - it has a bug of returning to original language following search, which can be resolved by removing search from widget urls, but maybe later...
        if (isset($wp->query_vars['s'])) {
            if ($this->options->enable_search_translate) {
                add_action('pre_get_posts', array(&$this, 'pre_post_search'));
                add_action('posts_where_request', array(&$this, 'posts_where_request'));
            }
            if (transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url) && !transposh_utils::get_language_from_url($_SERVER['REQUEST_URI'], $this->home_url)) {
                $this->tp_redirect(transposh_utils::rewrite_url_lang_param($_SERVER["REQUEST_URI"], $this->home_url, $this->enable_permalinks_rewrite, transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url), false)); //."&stop=y");
                exit;
            }
        }
        if (isset($wp->query_vars[EDIT_PARAM]) && $wp->query_vars[EDIT_PARAM] && $this->is_editing_permitted()) {
            $this->edit_mode = true;
            // redirect bots away from edit pages to avoid double indexing
            if (transposh_utils::is_bot()) {
                $this->tp_redirect(transposh_utils::rewrite_url_lang_param($_SERVER["REQUEST_URI"], $this->home_url, $this->enable_permalinks_rewrite, transposh_utils::get_language_from_url($_SERVER["REQUEST_URI"], $this->home_url), false), 301);
                exit;
            }
        } else {
            $this->edit_mode = false;
        }
        // We are removing our query vars since they are no longer needed and also make issues when a user select a static page as his home
        unset($wp->query_vars[LANG_PARAM]);
        unset($wp->query_vars[EDIT_PARAM]);
        tp_logger("edit mode: " . (($this->edit_mode) ? 'enabled' : 'disabled'), 2);
    }

    // TODO ? move to options?

    /**
     * Determine if the current user is allowed to translate.
     * @return boolean Is allowed to translate?
     */
    function is_translator() {
        //if anonymous translation is allowed - let anyone enjoy it
        if ($this->options->allow_anonymous_translation) {
            return TRUE;
        }
        if (is_user_logged_in() && current_user_can(TRANSLATOR)) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Plugin activation
     */
    function plugin_activate() {
        tp_logger("plugin_activate enter: " . dirname(__FILE__), 1);

        $this->database->setup_db();
        // this handles the permalink rewrite
        $GLOBALS['wp_rewrite']->flush_rules();

        // attempt to remove old files
        @unlink($this->transposh_plugin_dir . 'widgets/tpw_default.php');
        @unlink($this->transposh_plugin_dir . 'core/globals.php');

        tp_logger("plugin_activate exit: " . dirname(__FILE__), 1);
        tp_logger("testing name:" . plugin_basename(__FILE__), 4);
        // tp_logger("testing name2:" . $this->get_plugin_name(), 4);
        //activate_plugin($plugin);
    }

    /**
     * Plugin deactivation
     */
    function plugin_deactivate() {
        tp_logger("plugin_deactivate enter: " . dirname(__FILE__), 2);

        // this handles the permalink rewrite
        $GLOBALS['wp_rewrite']->flush_rules();

        tp_logger("plugin_deactivate exit: " . dirname(__FILE__), 2);
    }

    /**
     * Callback from admin_notices - display error message to the admin.
     */
    function plugin_install_error() {
        tp_logger("install error!", 1);

        echo '<div class="updated"><p>';
        echo 'Error has occured in the installation process of the translation plugin: <br>';
        echo $this->admin_msg;

        if (function_exists('deactivate_plugins')) {
            // FIXME :wtf?
            //deactivate_plugins(array(&$this, 'get_plugin_name'), "translate.php");
            ////!!!   deactivate_plugins($this->transposh_plugin_basename, "translate.php");
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
        tp_logger("Enter", 4);

        //TODO: fix this...
        $db_version = get_option(TRANSPOSH_DB_VERSION);

        if ($db_version != DB_VERSION) {
            $this->database->setup_db();
            //$this->admin_msg = "Translation database version ($db_version) is not comptabile with this plugin (". DB_VERSION . ")  <br>";

            tp_logger("Updating database in plugin loaded", 1);
            //Some error occured - notify admin and deactivate plugin
            //add_action('admin_notices', 'plugin_install_error');
        }

        //TODO: fix this too...
        $db_version = get_option(TRANSPOSH_DB_VERSION);

        if ($db_version != DB_VERSION) {
            $this->admin_msg = "Failed to locate the translation table  <em> " . TRANSLATIONS_TABLE . "</em> in local database. <br>";

            tp_logger("Messsage to admin: {$this->admin_msg}", 1);
            //Some error occured - notify admin and deactivate plugin
            add_action('admin_notices', array(&$this, 'plugin_install_error'));
        }
    }

    /**
     * Gets the plugin name to be used in activation/decativation hooks.
     * Keep only the file name and its containing directory. Don't use the full
     * path as it will break when using symbollic links.
     * TODO - check!!!
     * @return string
     */
    /* function get_plugin_name() {
      $file = __FILE__;
      $file = str_replace('\\', '/', $file); // sanitize for Win32 installs
      $file = preg_replace('|/+|', '/', $file); // remove any duplicate slash
      //keep only the file name and its parent directory
      $file = preg_replace('/.*\/([^\/]+\/[^\/]+)$/', '$1', $file);
      tp_logger("Plugin path - $file", 4);
      return $file;
      } */

    /**
     * Add custom css, i.e. transposh.css
     */
    function add_transposh_css() {
        //translation not allowed - no need for the transposh.css
        if (!$this->is_editing_permitted() && !$this->is_auto_translate_permitted())
            return;
        // actually - this is only needed when editing
        if (!$this->edit_mode) {
            return;
        }

        //include the transposh.css
        wp_enqueue_style('transposh', $this->transposh_plugin_url . '/' . TRANSPOSH_DIR_CSS . '/transposh.css', array(), TRANSPOSH_PLUGIN_VER);

        tp_logger('Added transposh_css', 4);
    }

    /**
     * Insert references to the javascript files used in the translated version of the page.
     */
    function add_transposh_js() {
        //not in any translation mode - no need for any js.
        if (!($this->edit_mode || $this->is_auto_translate_permitted() || is_admin() || $this->options->widget_allow_set_deflang))
        // TODO: need to include if allowing of setting default language - but smaller!
            return; // TODO, check just for settings page admin and pages with our translate
        wp_register_script('transposh', $this->transposh_plugin_url . '/' . TRANSPOSH_DIR_JS . '/transposh.js', array('jquery'), TRANSPOSH_PLUGIN_VER, $this->options->enable_footer_scripts);
        // true -> 1, false -> nothing
        $script_params = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'plugin_url' => $this->transposh_plugin_url,
            'lang' => $this->target_language,
            'olang' => $this->options->default_language,
            // those two options show if the script can support said engines
            'prefix' => SPAN_PREFIX,
            'preferred' => array_keys($this->options->get_sorted_engines())
        );

        $script_params['engines'] = new stdClass();
        if (in_array($this->target_language, transposh_consts::$engines['a']['langs'])) {
            $script_params['engines']->a = 1;
        }
        if (in_array($this->target_language, transposh_consts::$engines['b']['langs'])) {
            $script_params['engines']->b = 1;
//            $script_params['engines'][] = 'b';
            if (isset(transposh_consts::$engines['b']['langconv'][$this->target_language])) {
                $script_params['blang'] = transposh_consts::$engines['b']['langconv'][$this->target_language];
            }
        }
        if (in_array($this->target_language, transposh_consts::$engines['g']['langs'])) {
            $script_params['engines']->g = 1;
        }
        if (in_array($this->target_language, transposh_consts::$engines['y']['langs'])) {
            $script_params['engines']->y = 1;
        }
        if (in_array($this->target_language, transposh_consts::$engines['u']['langs'])) {
            $script_params['engines']->u = 1;
        }
        if ($this->options->oht_id && $this->options->oht_key && in_array($this->target_language, transposh_consts::$oht_languages) && current_user_can('manage_options')) {
            $script_params['engines']->o = 1;
        }
        if (!$this->options->enable_autotranslate) {
            $script_params['noauto'] = 1;
        }

        // load translations needed for edit interface
        if ($this->edit_mode) {
            $script_params['edit'] = 1;
            if (file_exists($this->transposh_plugin_dir . TRANSPOSH_DIR_JS . '/l/' . $this->target_language . '.js')) {
                $script_params['locale'] = 1;
            }
        }
        // set theme when it is needed
        if ($this->edit_mode) {
            $script_params['theme'] = $this->options->widget_theme;
            if ($this->options->jqueryui_override) {
                $script_params['jQueryUI'] = '//ajax.googleapis.com/ajax/libs/jqueryui/' . $this->options->jqueryui_override . '/';
            } else {
                $script_params['jQueryUI'] = '//ajax.googleapis.com/ajax/libs/jqueryui/' . JQUERYUI_VER . '/';
            }
        }

//          'l10n_print_after' => 'try{convertEntities(inlineEditL10n);}catch(e){};'
        wp_localize_script('transposh', 't_jp', $script_params);
        // only enqueue on real pages, for real people, other admin scripts that need this will register a dependency
        if (($this->edit_mode || $this->is_auto_translate_permitted() || $this->options->widget_allow_set_deflang) && !is_admin() && !transposh_utils::is_bot()) {
            wp_enqueue_script('transposh');
        }
        tp_logger('Added transposh_js', 4);
    }

    /**
     * Implements - http://googlewebmastercentral.blogspot.com/2010/09/unifying-content-under-multilingual.html
     */
    function add_rel_alternate() {
        if (is_404()) {
            return;
        }
        $widget_args = $this->widget->create_widget_args($this->get_clean_url());
        tp_logger($widget_args, 4);
        foreach ($widget_args as $lang) {
            if (!$lang['active']) {
                echo '<link rel="alternate" hreflang="' . $lang['isocode'] . '" href="' . $lang['url'] . '"/>';
            }
        }
    }

    /**
     * Determine if the currently selected language (taken from the query parameters) is in the admin's list
     * of editable languages and the current user is allowed to translate.
     * @return boolean Is translation allowed?
     */
    // TODO????
    function is_editing_permitted() {
        // editing is permitted for translators only
        if (!$this->is_translator()) {
            return false;
        }
        // and only on the non-default lang (unless strictly specified)
        if (!$this->options->enable_default_translate && $this->options->is_default_language($this->target_language)) {
            return false;
        }

        return $this->options->is_active_language($this->target_language);
    }

    /**
     * Determine if the currently selected language (taken from the query parameters) is in the admin's list
     * of editable languages and that automatic translation has been enabled.
     * Note that any user can auto translate. i.e. ignore permissions.
     * @return boolean Is automatic translation allowed?
     * TODO: move to options
     */
    function is_auto_translate_permitted() {
        tp_logger("checking auto translatability", 4);

        if (!$this->options->enable_autotranslate) {
            return false;
        }
        // auto translate is not enabled for default target language when enable default is disabled
        if (!$this->options->enable_default_translate && $this->options->is_default_language($this->target_language)) {
            return false;
        }

        return $this->options->is_active_language($this->target_language);
    }

    /**
     * Splits a url to translatable segments
     * @param string $href
     * @return array parts that may be translated
     */
    function split_url($href) {
        $ret = array();
        // Ignore urls not from this site
        if (!transposh_utils::is_rewriteable_url($href, $this->home_url)) {
            return $ret;
        }

        // don't fix links pointing to real files as it will cause that the
        // web server will not be able to locate them
        if (stripos($href, '/wp-admin') !== FALSE ||
                stripos($href, WP_CONTENT_URL) !== FALSE ||
                stripos($href, '/wp-login') !== FALSE ||
                stripos($href, '/.php') !== FALSE) /* ??? */ {
            return $ret;
        }

        // todo - check query part... sanitize
        //if (strpos($href, '?') !== false) {
        //    list ($href, $querypart) = explode('?', $href);
        //}
        //$href = substr($href, strlen($this->home_url));
        // this might include the sub directory for non rooted sites, but its not that important to avoid
        $href = parse_url($href, PHP_URL_PATH);
        $parts = explode('/', $href);
        foreach ($parts as $part) {
            if (!$part || is_numeric($part)) {
                continue;
            }
            $ret[] = $part;
            if ($part != str_replace('-', ' ', $part)) {
                $ret[] = str_replace('-', ' ', $part);
            }
        }
        return $ret;
    }

    /**
     * Callback from parser allowing to overide the global setting of url rewriting using permalinks.
     * Some urls should be modified only by adding parameters and should be identified by this
     * function.
     * @param $href Original href
     * @return boolean Modified href
     */
    function rewrite_url($href) {
        tp_logger("got: $href", 5);
        ////$href = str_replace('&#038;', '&', $href);
        // fix what might be messed up -- TODO
        $href = str_replace(array(TP_GTXT_BRK, TP_GTXT_IBRK, TP_GTXT_BRK_CLOSER, TP_GTXT_IBRK_CLOSER), '', $href);

        // Ignore urls not from this site
        if (!transposh_utils::is_rewriteable_url($href, $this->home_url)) {
            return $href;
        }

        // don't fix links pointing to real files as it will cause that the
        // web server will not be able to locate them
        if (stripos($href, '/wp-admin') !== FALSE ||
                stripos($href, WP_CONTENT_URL) !== FALSE ||
                stripos($href, '/wp-login') !== FALSE ||
                stripos($href, '/.php') !== FALSE) /* ??? */ {
            return $href;
        }
        $use_params = !$this->enable_permalinks_rewrite;

        // we don't really know, but we sometime rewrite urls when we are in the default language (canonicals?), so just clean them up
        //       if ($this->target_language == $this->options->default_language) 
        if ($this->options->is_default_language($this->target_language)) {
            $href = transposh_utils::cleanup_url($href, $this->home_url);
            tp_logger("cleaned up: $href", 4);
            return $href;
        }
        // some hackery needed for url translations
        // first cut home
        if ($this->options->enable_url_translate) {
            $href = transposh_utils::translate_url($href, $this->home_url, $this->target_language, array(&$this->database, 'fetch_translation'));
        }
        $href = transposh_utils::rewrite_url_lang_param($href, $this->home_url, $this->enable_permalinks_rewrite, $this->target_language, $this->edit_mode, $use_params);
        tp_logger("rewritten: $href", 4);
        return $href;
    }

    /**
     * This function adds the word setting in the plugin list page
     * @param array $links Links that appear next to the plugin
     * @return array Now with settings
     */
    function plugin_action_links($links) {
        tp_logger('in plugin action', 5);
        return array_merge(array('<a href="' . admin_url('admin.php?page=tp_main') . '">' . __('Settings') . '</a>'), $links);
    }

    /**
     * We use this to "steal" the search variables
     * @param WP_Query $query
     */
    function pre_post_search($query) {
        tp_logger('pre post', 4);
        tp_logger($query->query_vars, 4);
        // we hide the search query var from further proccesing, because we do this later
        if ($query->query_vars['s']) {
            $this->search_s = $query->query_vars['s'];
            $query->query_vars['s'] = '';
        }
    }

    /**
     * This is where we change the logic to include originals for search translation
     * @param string $where Original where clause for getting posts
     * @return string Modified where
     */
    function posts_where_request($where) {

        tp_logger($where, 3);
        // from query.php line 1742 (v2.8.6)
        // If a search pattern is specified, load the posts that match
        $q = &$GLOBALS['wp_query']->query_vars;
        // returning the saved query strings
        $q['s'] = $this->search_s;
        if (!empty($q['s'])) {
            // added slashes screw with quote grouping when done early, so done later
            $q['s'] = stripslashes($q['s']);
            if (!empty($q['sentence'])) {
                $q['search_terms'] = array($q['s']);
            } else {
                preg_match_all('/".*?("|$)|((?<=[\\s",+])|^)[^\\s",+]+/', $q['s'], $matches);
                $q['search_terms'] = array_map(create_function('$a', 'return trim($a, "\\"\'\\n\\r ");'), $matches[0]);
            }
            $n = !empty($q['exact']) ? '' : '%';
            $searchand = '';
            $search = '';
            foreach ((array) $q['search_terms'] as $term) {
                // now we'll get possible translations for this term
                $possible_original_terms = $this->database->get_orignal_phrases_for_search_term($term, $this->target_language);
                $term = addslashes_gpc($term);
                $search .= "{$searchand}(({$GLOBALS['wpdb']->posts}.post_title LIKE '{$n}{$term}{$n}') OR ({$GLOBALS['wpdb']->posts}.post_content LIKE '{$n}{$term}{$n}')";
                foreach ((array) $possible_original_terms as $term) {
                    $term = addslashes_gpc($term);
                    $search .= " OR ({$GLOBALS['wpdb']->posts}.post_title LIKE '{$n}{$term}{$n}') OR ({$GLOBALS['wpdb']->posts}.post_content LIKE '{$n}{$term}{$n}')";
                }
                // we moved this to here, so it really closes all of them
                $search .= ")";
                $searchand = ' AND ';
            }
            $term = esc_sql($q['s']);
            if (empty($q['sentence']) && count($q['search_terms']) > 1 && $q['search_terms'][0] != $q['s'])
                $search .= " OR ({$GLOBALS['wpdb']->posts}.post_title LIKE '{$n}{$term}{$n}') OR ({$GLOBALS['wpdb']->posts}.post_content LIKE '{$n}{$term}{$n}')";

            if (!empty($search)) {
                $search = " AND ({$search}) ";
                if (!is_user_logged_in())
                    $search .= " AND ({$GLOBALS['wpdb']->posts}.post_password = '') ";
            }
        }
        tp_logger($search, 3);
        return $search . $where;
    }

    /**
     * Runs a scheduled backup
     */
    function run_backup() {
        tp_logger('backup run..', 2);
        $my_transposh_backup = new transposh_backup($this);
        $my_transposh_backup->do_backup();
    }

    /**
     * Register for superproxy
     */
    function superproxy_reg() {
        $url = "http://superproxy.transposh.net/?action=register&version=0.1&entry_url=" . admin_url('admin-ajax.php');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        if ($output === false) {
            echo 'Curl error: ' . curl_error($ch);
            die();
        }

        tp_logger($output);
        curl_close($ch);

        $info = json_decode($output);
        tp_logger($info);
        if (isset($info->id)) {
            $this->options->superproxy_key = $info->id;
            $this->options->update_options();
        }
        if (isset($info->ips)) {
            $this->options->superproxy_ips = json_encode($info->ips);
            $this->options->update_options();
        }
        die();
    }

    /**
     * Runs a restore
     */
    function run_restore() {
        tp_logger('restoring..', 2);
        $my_transposh_backup = new transposh_backup($this);
        $my_transposh_backup->do_restore();
    }

    /**
     * Adding the comment meta language, for later use in display
     * TODO: can use the language detection feature of some translation engines
     * @param int $post_id
     */
    function add_comment_meta_settings($post_id) {
        if (transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url))
            add_comment_meta($post_id, 'tp_language', transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url), true);
    }

    /**
     * After a user adds a comment, makes sure he gets back to the proper language
     * TODO - check the three other params
     * @param string $url
     * @return string fixed url
     */
    function comment_post_redirect_filter($url) {
        $lang = transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url);
        if ($lang) {
            $url = transposh_utils::rewrite_url_lang_param($url, $this->home_url, $this->enable_permalinks_rewrite, $lang, $this->edit_mode);
        }
        return $url;
    }

    /**
     * Modify comments to include the relevant language span
     * @param string $text
     * @return string
     */
    function comment_text_wrap($text) {
        $comment_lang = get_comment_meta(get_comment_ID(), 'tp_language', true);
        if ($comment_lang) {
            $text = "<span lang =\"$comment_lang\">" . $text . "</span>";
            if (strpos($text, '<a href="' . $this->home_url) !== FALSE) {
                $text = str_replace('<a href="' . $this->home_url, '<a lang="' . $this->options->default_language . '" href="' . $this->home_url, $text);
            }
        }
        tp_logger("$comment_lang " . get_comment_ID(), 4);
        return $text;
    }

    /**
     * Modify posts to have language wrapping
     * @global int $id the post id
     * @param string $text the post text (or title text)
     * @return string wrapped text
     */
    function post_content_wrap($text) {
        if (!isset($GLOBALS['id'])) {
            return $text;
        }
        $lang = get_post_meta($GLOBALS['id'], 'tp_language', true);
        if ($lang) {
            $text = "<span lang =\"$lang\">" . $text . "</span>";
            if (strpos($text, '<a href="' . $this->home_url) !== FALSE) {
                $text = str_replace('<a href="' . $this->home_url, '<a lang="' . $this->options->default_language . '" href="' . $this->home_url, $text);
            }
        }
        return $text;
    }

    /**
     * Modify post title to have language wrapping
     * @param string $text the post title text
     * @return string wrapped text
     */
    function post_wrap($text, $id = 0) {
        $id = (is_object($id)) ? $id->ID : $id;
        if (!$id) {
            return $text;
        }
        $lang = get_post_meta($id, 'tp_language', true);
        if ($lang) {
            if (strpos($_SERVER['REQUEST_URI'], 'wp-admin/edit') !== false) {
                tp_logger('iamhere?' . strpos($_SERVER['REQUEST_URI'], 'wp-admin/edit'));
                $plugpath = @parse_url($this->transposh_plugin_url, PHP_URL_PATH);
                list($langeng, $langorig, $langflag) = explode(',', transposh_consts::$languages[$lang]);
                //$text = transposh_utils::display_flag("$plugpath/img/flags", $langflag, $langorig, false) . ' ' . $text;
                $text = "[$lang] " . $text;
            } else {
                $text = "<span lang =\"$lang\">" . $text . "</span>";
            }
        }
        return $text;
    }

    /**
     * This function enables the correct parsing of translated URLs
     * @global object $wp the wordpress global
     * @param array $query
     * @return $query
     */
    function request_filter($query) {
        //We only do this once, and if we have a lang
        $requri = $_SERVER['REQUEST_URI'];
        $lang = transposh_utils::get_language_from_url($requri, $this->home_url);
        if ($lang && !$this->got_request) {
            tp_logger('Trying to find original url');
            $this->got_request = true;
            // the trick is to replace the URI and put it back afterwards
            $_SERVER['REQUEST_URI'] = transposh_utils::get_original_url($requri, '', $lang, array($this->database, 'fetch_original'));
            global $wp;
            $wp->parse_request();
            $query = $wp->query_vars;
            $_SERVER['REQUEST_URI'] = $requri;
            tp_logger('new query vars are');
            tp_logger($query);
        }
        return $query;
    }

    /**
     * This function adds our markings around gettext results
     * @param string $translation
     * @param string $orig
     * @return string
     */
    function transposh_gettext_filter($translation, $orig, $domain) {
        if ($this->is_special_page($_SERVER['REQUEST_URI']) || ($this->options->is_default_language($this->tgl) && !$this->options->enable_default_translate)) {
            return $translation;
        }
        tp_logger("($translation, $orig, $domain)", 5);
        // HACK - TODO - FIX
        if (in_array($domain, transposh_consts::$ignored_po_domains))
            return $translation;
        if ($translation != $orig) {
            $translation = TP_GTXT_BRK . $translation . TP_GTXT_BRK_CLOSER;
        }
        $translation = str_replace(array('%s', '%1$s', '%2$s', '%3$s', '%4$s', '%5$s'), array(TP_GTXT_IBRK . '%s' . TP_GTXT_IBRK_CLOSER, TP_GTXT_IBRK . '%1$s' . TP_GTXT_IBRK_CLOSER, TP_GTXT_IBRK . '%2$s' . TP_GTXT_IBRK_CLOSER, TP_GTXT_IBRK . '%3$s' . TP_GTXT_IBRK_CLOSER, TP_GTXT_IBRK . '%4$s' . TP_GTXT_IBRK_CLOSER, TP_GTXT_IBRK . '%5$s' . TP_GTXT_IBRK_CLOSER), $translation);
        return $translation;
    }

    /**
     * This function adds our markings around ngettext results
     * @param string $translation
     * @param string $single
     * @param string $plural
     * @return string
     */
    function transposh_ngettext_filter($translation, $single, $plural, $domain) {
        if ($this->is_special_page($_SERVER['REQUEST_URI']) || ($this->options->is_default_language($this->tgl) && !$this->options->enable_default_translate))
            return $translation;
        tp_logger("($translation, $single, $plural, $domain)", 4);
        if (in_array($domain, transposh_consts::$ignored_po_domains))
            return $translation;
        if ($translation != $single && $translation != $plural) {
            $translation = TP_GTXT_BRK . $translation . TP_GTXT_BRK_CLOSER;
        }
        $translation = str_replace(array('%s', '%1$s', '%2$s', '%3$s', '%4$s', '%5$s'), array(TP_GTXT_IBRK . '%s' . TP_GTXT_IBRK_CLOSER, TP_GTXT_IBRK . '%1$s' . TP_GTXT_IBRK_CLOSER, TP_GTXT_IBRK . '%2$s' . TP_GTXT_IBRK_CLOSER, TP_GTXT_IBRK . '%3$s' . TP_GTXT_IBRK_CLOSER, TP_GTXT_IBRK . '%4$s' . TP_GTXT_IBRK_CLOSER, TP_GTXT_IBRK . '%5$s' . TP_GTXT_IBRK_CLOSER), $translation);
        return $translation;
    }

    /**
     * This function makes sure wordpress sees the appropriate locale on translated pages for .po/.mo and mu integration
     * @param string $locale
     * @return string 
     */
    function transposh_locale_filter($locale) {
        $lang = transposh_utils::get_language_from_url($_SERVER['REQUEST_URI'], $this->home_url);
        if (!$this->options->is_active_language($lang)) {
            $lang = '';
        }
        if (!$lang) {
            if (!$this->options->transposh_locale_override) {
                return $locale;
            }
            $lang = $this->options->default_language;
        }
        $locale = transposh_consts::get_language_locale($lang);

        return ($locale) ? $locale : $lang;
    }

    /**
     * Support for tp shortcodes - [tp]
     * @see http://trac.transposh.org/wiki/ShortCodes
     * @param array $atts
     * @param string $content
     * @return string 
     */
    function tp_shortcode($atts, $content = null) {
        $only_class = '';
        $lang = '';
        $nt_class = '';
        
        if(!is_array($atts)) { // safety check
            return do_shortcode($content);            
        }

        tp_logger($atts);
        tp_logger($content);

        if (isset($atts['not_in']) && $this->target_language) {
            if (stripos($atts['not_in'], $this->target_language) !== false) {
                return;
            }
        }

        if (isset($atts['locale']) || in_array('locale', $atts)) {
            if (isset($atts['lang']) && stripos($atts['lang'], $this->target_language) === false) {
                return;
            }
            return get_locale();
        }

        if (isset($atts['mylang']) || in_array('mylang', $atts)) {
            if (isset($atts['lang']) && stripos($atts['lang'], $this->target_language) === false) {
                return;
            }
            return $this->target_language;
        }

        if (isset($atts['lang'])) {
            $lang = ' lang="' . $atts['lang'] . '"';
        }

        if (isset($atts['only']) || in_array('only', $atts)) {
            $only_class = ' class="' . ONLY_THISLANGUAGE_CLASS . '"';
            tp_logger($atts['lang'] . " " . $this->target_language);
//            if ($atts['lang'] != $this->target_language) {
//                return;
//            }
        }

        if (isset($atts['no_translate'])) {
            $nt_class = ' class="' . NO_TRANSLATE_CLASS . '"';
        }

        if (isset($atts['widget'])) {
            ob_start();
            $this->widget->widget(array('before_widget' => '', 'before_title' => '', 'after_widget' => '', 'after_title' => ''), array('title' => '', 'widget_file' => $atts['widget']), true);
            $widgetcontent = ob_get_contents();
            ob_end_clean();
            return $widgetcontent . do_shortcode($content);
        }

        if ($lang || $only_class || $nt_class) {
            $newcontent = do_shortcode($content);
            $newcontent = str_replace('<p>', '<p><span' . $only_class . $nt_class . $lang . '>', $newcontent);
            $newcontent = str_replace('</p>', '</span></p>', $newcontent);
            return '<span' . $only_class . $nt_class . $lang . '>' . $newcontent . '</span>';
        } else {
            return do_shortcode($content);
        }
    }

    // Super Proxy 
    function on_ajax_nopriv_proxy() {
        // Check if enabled
        if (!$this->options->enable_superproxy) {
            $errstr = "Error: 500: Not enabled";
            tp_logger($errstr);
            die($errstr);
        }

        // Check requester IP to be allowed
        $ips = json_decode($this->options->superproxy_ips);
        if (!in_array($_SERVER['REMOTE_ADDR'], $ips)) {
            $errstr = "Error: 503: Unauthorized {$_SERVER['REMOTE_ADDR']}";
            tp_logger($errstr);
            die($errstr);
        }

        // We need curl for this proxy
        if (!function_exists('curl_init')) {
            $errstr = "Error: 504: fatal error - curl";
            tp_logger($errstr);
            die($errstr);
        }

        // Create proxy request
        $encoded_url = $_GET['url'];
        $url = base64_decode($encoded_url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);

        // Send the headers we got
        $reqheaders = getallheaders();
        //tp_logger($reqheaders);
        unset($reqheaders['Host']);
        unset($reqheaders['Content-Length']);
        $headers = array();
        foreach ($reqheaders as $name => $value) {
            $headers[] = "$name: $value";
        }
        //tp_logger($headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Handle POST method
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            //tp_logger($_POST);
            curl_setopt($ch, CURLOPT_POST, true);
            foreach ($_POST as $key => $value) {
                $post .= $amp . $key . "=" . urlencode($value);
                $amp = "&";
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post); //$_POST);
        }

        tp_logger("Before curl");
        $output = curl_exec($ch);
        tp_logger("After curl");
        if ($output === false) {
            $errstr = "Error: " . curl_errno($ch) . ' ' . curl_error($ch);
            tp_logger($errstr);
            die($errstr);
        }

        echo $output;
        curl_close($ch);
        die();
    }

    // transposh translation proxy ajax wrapper

    function on_ajax_nopriv_tp_tp() {
        // we need curl for this proxy
        if (!function_exists('curl_init'))
            return;

        // we are permissive for sites using multiple domains and such
        transposh_utils::allow_cors();
        // get the needed params
        $tl = $_GET['tl'];
        // avoid handling inactive languages
        if (!$this->options->is_active_language($tl))
            return;
        if (isset($_GET['sl'])) {
            $sl = $_GET['sl'];
        } else {
            $sl = '';
        }
        $suggestmode = false; // the suggest mode takes one string only, and does not save to the database
        if (isset($_GET['m']) && $_GET['m'] == 's')
            $suggestmode = true;
        if ($suggestmode) {
            $q = urlencode(stripslashes($_GET['q']));
            if (!$q)
                return;
        } else {
            // item count
            $i = 0;
            $q = array();
            foreach ($_GET['q'] as $p) {
                list(, $trans) = $this->database->fetch_translation(stripslashes($p), $tl);
                if (!$trans) {
                    $q[] = urlencode(stripslashes($p)); // fix for the + case?
                } else {
                    $r[$i] = $trans;
                }
                $i++;
            }
        }
        if ($q) {
            switch ($_GET['e']) {
                case 'g': // google
                    if (!$sl) {
                        $sl = 'auto';
                    }
                    if (!in_array($tl, transposh_consts::$engines['g']['langs'])) // nope...
                        return;
                    $source = 1;
                    $result = $this->get_google_translation($tl, $sl, $q);
                    break;
                case 'y': // yandex
                    if (!in_array($tl, transposh_consts::$engines['y']['langs'])) // nope...
                        return;
                    $source = 4;
                    $result = $this->get_yandex_translation($tl, $sl, $q);
                    break;
                case 'u': // baidu
                    if (!in_array($tl, transposh_consts::$engines['u']['langs'])) // nope...
                        return;
                    $source = 5;
                    $result = $this->get_baidu_translation($tl, $sl, $q);
                    break;

                default:
                    die('engine not supported');
            }

            if ($result === false) {
                echo 'Proxy attempt failed';
                die();
            }
        }

        // encode results 
        $jsonout = new stdClass();
        if ($suggestmode) {
            $jsonout->result = $result;
        } else {
            // here we match online results with cached ones
            $k = 0;
            for ($j = 0; $j < $i; $j++) {
                if (isset($r[$j])) {
                    $jsonout->results[] = $r[$j];
                } else {
                    // TODO: no value - original?
                    $jsonout->results[] = $result[$k];
                    $k++;
                }
            }

            //  // we send here because update translation dies... TODO: fix this mess
            //          echo json_encode($jsonout);
//
            // do the db dance - a bit hackish way to insert downloaded translations directly to the db without having
            // to pass through the user and collect $200
            if ($k) {
                $_POST['items'] = $k;
                $_POST['ln0'] = $tl;
                $_POST['sr0'] = $source; // according to used engine
                $k = 0;
                for ($j = 0; $j < $i; $j++) {
                    if (!isset($r[$j])) {
                        $_POST["tk$k"] = stripslashes($_GET['q'][$j]); // stupid, but should work
                        $_POST["tr$k"] = $jsonout->results[$j];
                        $k++;
                    }
                }
                tp_logger('updating! :)');
                tp_logger($_POST);
                $this->database->update_translation();
            }
        }

        // send out result
        echo json_encode($jsonout);
        die();
    }

    // Proxied Yandex translate suggestions
    function get_yandex_translation($tl, $sl, $q) {
        $sid = '';
        $timestamp = 0;
        if (get_option(TRANSPOSH_OPTIONS_YANDEXPROXY, array())) {
            list($sid, $timestamp) = get_option(TRANSPOSH_OPTIONS_YANDEXPROXY, array());
        }
        if ($sid == '') {
            if ((time() - TRANSPOSH_YANDEXPROXY_DELAY > $timestamp)) {
                // attempt key refresh on error
                $url = 'https://translate.yandex.com/';
                tp_logger($url, 3);
                $ch = curl_init();
                // yandex wants a referer someimes
                curl_setopt($ch, CURLOPT_REFERER, "https://translate.yandex.com/");
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                //must set agent for google to respond with utf-8
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                $output = curl_exec($ch);
                $sidpos = strpos($output, "SID: '") + 6;
                $sid = substr($output, $sidpos, strpos($output, "',", $sidpos) - $sidpos);
                tp_logger($sid);
                $sid = strrev(substr($sid, 0, 8)) . '.' . strrev(substr($sid, 9, 8)) . '.' . strrev(substr($sid, 18, 8));
                tp_logger($sid);
                if ($output === false) {
                    tp_logger('Curl error: ' . curl_error($ch));
                    return false;
                }
                update_option(TRANSPOSH_OPTIONS_YANDEXPROXY, array($sid, time()));
                curl_close($ch);
            }
        }

        if (!$sid) {
            tp_logger('No SID, gotta bail:' . $timestamp);
            return false;
        }

        if ($sl) {
            $sl .= '-';
        }
        $qstr = '';
        if (is_array($q)) {
            foreach ($q as $v) {
                $qstr .= '&text=' . $v;
            }
        } else {
            $qstr = '&text=' . $q;
        }
        $url = 'https://translate.yandex.net/api/v1/tr.json/translate?lang=' . $sl . $tl . $qstr . '&srv=tr-url&id=' . $sid . '-0-0';
        tp_logger($url, 3);
        tp_logger($q, 3);
        $ch = curl_init();
        // yandex wants a referer someimes
        curl_setopt($ch, CURLOPT_REFERER, "https://translate.yandex.com/");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //must set agent for google to respond with utf-8
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $output = curl_exec($ch);
        if ($output === false) {
            tp_logger('Curl error: ' . curl_error($ch));
            return false;
        }
        curl_close($ch);
        tp_logger($output, 3);
        $jsonarr = json_decode($output);
        tp_logger($jsonarr, 3);
        if (!$jsonarr) {
            tp_logger('No JSON here, failing');
            tp_logger($output, 3);
            return false;
        }
        if ($jsonarr->code != 200) {
            tp_logger('Some sort of error!');
            tp_logger($output, 3);
            if ($jsonarr->code == 406) { //invalid session
                update_option(TRANSPOSH_OPTIONS_YANDEXPROXY, array('', time()));
            }

            return false;
        };

        $result = $jsonarr->text;

        return $result;
    }

    // Proxied Baidu translate suggestions
    function get_baidu_translation($tl, $sl, $q) {
        $qstr = 'to=' . ((isset(transposh_consts::$engines['u']['langconv'][$tl])) ? transposh_consts::$engines['u']['langconv'][$tl] : $tl);
        if ($sl) {
            $qstr .= '&from=' . ((isset(transposh_consts::$engines['u']['langconv'][$tl])) ? transposh_consts::$engines['u']['langconv'][$sl] : $sl);
        }
        $qstr .= '&query=';
        if (is_array($q)) {
            foreach ($q as $v) {
                $qstr .= $v . "%0A";
            }
        } else {
            $qstr .= $q;
        }
        $url = 'http://fanyi.baidu.com/v2transapi';
        tp_logger($url, 3);
        tp_logger($q, 3);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //must set agent for google to respond with utf-8
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $qstr);
        $output = curl_exec($ch);
        if ($output === false) {
            tp_logger('Curl error: ' . curl_error($ch));
            return false;
        }
        curl_close($ch);
        tp_logger($output, 3);
        $jsonarr = json_decode($output);
        tp_logger($jsonarr, 3);
        if (!$jsonarr) {
            tp_logger('No JSON here, failing');
            tp_logger($output, 3);
            return false;
        }
        foreach ($jsonarr->trans_result->data as $val) {
            $result[] = $val->dst;
        }
        return $result;
    }

    function _bitwise_zfrs($a, $b) {
        if ($b == 0)
            return $a;
        return ($a >> $b) & ~(1 << (8 * PHP_INT_SIZE - 1) >> ($b - 1));
    }

    function hq($a, $chunk) {
        for ($offset = 0; $offset < strlen($chunk) - 2; $offset += 3) {
            $b = $chunk[$offset + 2];
            $b = ($b >= "a") ? ord($b) - 87 : intval($b);
            $b = ($chunk[$offset + 1] == "+") ? $this->_bitwise_zfrs($a, $b) : $a << $b;
            $a = ($chunk[$offset] == "+") ? $a + $b & 4294967295 : $a ^ $b;
        }
        return $a;
    }

    /**
     * Hey googler, if you are reading this, it means that you are actually here, why won't we work together on this?
     */
    function iq($input, $error) {
        $e = explode(".", $error);
        $value = intval($e[0]);
        for ($i = 0; $i < strlen($input); $i++) {
            $value += ord($input[$i]);
            $value = $this->hq($value, "+-a^+6");
        }
        $value = $this->hq($value, "+-3^+b+-f");
        $value ^= intval($e[1]);
        if (0 > $value) {
            $value = $value & 2147483647 + 2147483648;
        }
        $x = $value % 1E6;
        return $x . "." . ($x ^ $error);
    }

// Proxied translation for google translate
    function get_google_translation($tl, $sl, $q) {
        if (get_option(TRANSPOSH_OPTIONS_GOOGLEPROXY, array())) {
            list($googlemethod, $timestamp) = get_option(TRANSPOSH_OPTIONS_GOOGLEPROXY, array());
            //$googlemethod = 0;
            //$timestamp = 0;
            tp_logger("Google method $googlemethod, $timestamp", 1);
        } else {
            tp_logger("Google is clean", 1);
            $googlemethod = 0;
        }
        // we preserve the method, and will ignore lower methods for the given delay period
        if (isset($timestamp) && (time() - TRANSPOSH_GOOGLEPROXY_DELAY > $timestamp)) {
            delete_option(TRANSPOSH_OPTIONS_GOOGLEPROXY);
        }
        tp_logger('Google proxy initiated', 1);
        $qstr = '';
        $iqstr = '';
        if (is_array($q)) {
            foreach ($q as $v) {
                $qstr .= '&q=' . $v;
                $iqstr .= urldecode($v);
            }
        } else {
            $qstr = '&q=' . $q;
            $iqstr = urldecode($q);
        }
        // we avoid curling we had all results prehand
        $urls = array(
            'http://translate.google.com',
            'http://212.199.205.226',
            'http://74.125.195.138',
            'https://translate.googleapis.com');

        $attempt = 1;
        $failed = true;
        foreach ($urls as $gurl) {
            if ($googlemethod < $attempt && $failed) {
                $failed = false;
                tp_logger("Attempt: $attempt", 1);
                $url = $gurl . '/translate_a/t?client=te&v=1.0&tl=' . $tl . '&sl=' . $sl . '&tk=' . $this->iq($iqstr, '406448.272554134');
                tp_logger($url, 3);
                tp_logger($q, 3);
                tp_logger($iqstr, 3);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                //must set agent for google to respond with utf-8
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $qstr);

                //if the attempt is 2 or more, we skip ipv6 and use an alternative user agent
                if ($attempt > 1) {
                    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
                }
                $output = curl_exec($ch);
                $info = curl_getinfo($ch);
                tp_logger('Curl code is: ' . $info['http_code'], 1);
                curl_close($ch);
                tp_logger($output, 3);
                if ($info['http_code'] != 200) {
                    tp_logger("method fail - $attempt", 1);
                    $failed = true;
                    update_option(TRANSPOSH_OPTIONS_GOOGLEPROXY, array($attempt, time()));
                }
                unset($info);
            }
            $attempt++;
        }

        // TODO - last attempt, with key

        if ($failed) {
            tp_logger('out of options, die for the day!', 1);
            return false;
        }

        if ($output === false) {
            tp_logger('Curl error: ' . curl_error($ch));
            return false;
        }

        tp_logger($output, 3);
        // weird output that happens - $output='[[[[["Nnọọ"]],,"en"],[[["ụwa"]],,"en"],[[["Kedu ihe na-eme"]],,"en"]]]';
        $jsonarr = json_decode($output);
        if (!$jsonarr) {
            tp_logger("google didn't return Proper JSON, lets try to recover", 2);
            $newout = str_replace(',,', ',', $output);
            tp_logger($newout);
            $jsonarr = json_decode($newout);
            if (!$jsonarr) {
                tp_logger('No JSON here, failing');
                tp_logger($output, 3);
                return false;
            }
        }
        tp_logger($jsonarr);
        if (is_array($jsonarr)) {
            if (is_array($jsonarr[0])) {
                foreach ($jsonarr as $val) {
                    // need to drill
                    while (is_array($val)) {
                        $val = $val[0];
                    }
                    $result[] = $val;
                    //   tp_logger('$here');
                }
            } else {
                $result[] = $jsonarr[0];
            }
        } else {
            $result[] = $jsonarr;
        }
        /*
          //        header('Content-type: text/html; charset=utf-8');

          }
         */
        return $result;
    }

    /**
     * Queue for One Hour Translate
     */
    function on_ajax_nopriv_tp_oht() {
        // Admin access only
        if (!current_user_can('manage_options')) {
            echo "only admin is allowed";
            die();
        }
        $oht = get_option(TRANSPOSH_OPTIONS_OHT, array());
        if (!isset($_GET['orglang']))
            $_GET['orglang'] = $this->options->default_language;
        $key = $_GET['token'] . '@' . $_GET['lang'] . '@' . $_GET['orglang'];
        if (isset($oht[$key])) {
            unset($oht[$key]);
            tp_logger('oht false');
            echo json_encode(false);
        } else {
            $oht[$key] = array('q' => $_GET['q'], 'l' => $_GET['lang'], 'ol' => $_GET['orglang'], 't' => $_GET['token']);
            tp_logger('oht true');
            echo json_encode(true);
        }

        update_option(TRANSPOSH_OPTIONS_OHT, $oht);

        // we will make an oht send event in defined time
        wp_clear_scheduled_hook('transposh_oht_event');
        wp_schedule_single_event(time() + TRANSPOSH_OHT_DELAY, 'transposh_oht_event');

        die();
    }

    /**
     * OHT event running
     */
    function run_oht() {
        tp_logger("oht should run", 2);
        $oht = get_option(TRANSPOSH_OPTIONS_OHT, array());
        tp_logger($oht, 3);
        $ohtp = get_option(TRANSPOSH_OPTIONS_OHT_PROJECTS, array());
        $projectid = time();
        //send less data
        $ohtbody = array();
        $pcount = 0;
        foreach ($oht as $arr) {
            $pcount++;
            tp_logger($arr);
            $ohtbody[$arr['t']] = array('q' => $arr['q'], 'l' => $arr['l'], 'ol' => $arr['ol']);
        }
        $ohtbody['pid'] = $projectid;
        $ohtbody['id'] = $this->options->oht_id;
        $ohtbody['key'] = $this->options->oht_key;
        $ohtbody['callback'] = admin_url('admin-ajax.php');
        $ohtbody['homeurl'] = $this->home_url;
        tp_logger($ohtbody);
        // now we send this, add to log that it was sent to oht.. we'll also add a timer to make sure it gets back to us
        $ret = wp_remote_post('http://svc.transposh.org/oht.php', array('body' => $ohtbody));
        if ($ret['response']['code'] == '200') {
            delete_option(TRANSPOSH_OPTIONS_OHT);
            $ohtp[$projectid] = $pcount;
            update_option(TRANSPOSH_OPTIONS_OHT_PROJECTS, $ohtp);
        } else {
            tp_logger($ret, 1);
        }
    }

    // getting translation history
    function on_ajax_nopriv_tp_history() {
        // deleting
        transposh_utils::allow_cors();
        if (isset($_POST['timestamp'])) {
            $this->database->del_translation_history(stripslashes($_POST['token']), $_POST['lang'], $_POST['timestamp']);
        }
        $this->database->get_translation_history(stripslashes($_POST['token']), $_POST['lang']);
        die();
    }

    // the case of posted translation
    function on_ajax_nopriv_tp_translation() {
        transposh_utils::allow_cors();
        do_action('transposh_translation_posted');
        $this->database->update_translation();
        die();
    }

    /**
     * callback from one hour translation
     */
    function on_ajax_nopriv_tp_ohtcallback() {
        $ohtp = get_option(TRANSPOSH_OPTIONS_OHT_PROJECTS, array());
        tp_logger($ohtp);
        if ($ohtp[$_POST['projectid']]) {
            Logger($_POST['projectid'] . " was found and will be processed");
            do_action('transposh_oht_callback');
            tp_logger($_POST);
            $ohtp[$_POST['projectid']] -= $_POST['items'];
            if ($ohtp[$_POST['projectid']] <= 0) {
                unset($ohtp[$_POST['projectid']]);
            }
            tp_logger($ohtp);
            update_option(TRANSPOSH_OPTIONS_OHT_PROJECTS, $ohtp);
            $this->database->update_translation("OHT");
        }
        die();
    }

    // getting translation alternates
    function on_ajax_nopriv_tp_trans_alts() {
        transposh_utils::allow_cors();
        $this->database->get_translation_alt($_GET['token']);
        die();
    }

    // set the cookie with ajax, no redirect needed
    function on_ajax_nopriv_tp_cookie() {
        setcookie('TR_LNG', transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url), time() + 90 * 24 * 60 * 60, COOKIEPATH, COOKIE_DOMAIN);
        tp_logger('Cookie ' . transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url));
        die();
    }

    // Set our cookie and return (if no js works - or we are in the default language)
    function on_ajax_nopriv_tp_cookie_bck() {
        setcookie('TR_LNG', transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url), time() + 90 * 24 * 60 * 60, COOKIEPATH, COOKIE_DOMAIN);
        if ($_SERVER['HTTP_REFERER']) {
            $this->tp_redirect($_SERVER['HTTP_REFERER']);
        } else {
            $this->tp_redirect($my_transposh_plugin->home_url);
        }
        die();
    }

    // Catch the wordpress.org update post
    function filter_wordpress_org_update($arr, $url) {
        tp_logger($url, 5);
        /* if ($url == "http://api.wordpress.org/plugins/info/1.0/") {
          tp_logger($arr);
          } */
        // hide from wordpress.org
        if ($url == "http://api.wordpress.org/plugins/update-check/1.0/") {
            $plugs = unserialize($arr['body']['plugins']);
            tp_logger($plugs->plugins[$this->transposh_plugin_basename], 4);
            unset($plugs->plugins[$this->transposh_plugin_basename]);
            $arr['body']['plugins'] = serialize($plugs);
            tp_logger($arr, 5);
            // now we should query our own service
            $this->do_update_check = true;
        } elseif (strpos($url, "api.wordpress.org/plugins/update-check/") !== false) {
            $plugs = json_decode($arr['body']['plugins'], true);
            unset($plugs['plugins'][$this->transposh_plugin_basename]);
            $arr['body']['plugins'] = json_encode($plugs);
            tp_logger($arr, 5);
            $this->do_update_check = true;
        }
        return $arr;
    }

    function check_for_plugin_update($checked_data) {
        global $wp_version;
        tp_logger('should we check for upgrades?', 4);
        if (!$this->do_update_check) {
            return $checked_data; // thanks wizzud (don't kill the transient)
        }
        $this->do_update_check = false; // for next time
        tp_logger('yes, we should', 4);

        $args = array(
            'slug' => $this->transposh_plugin_basename,
            'version' => '%VERSION%', //$checked_data->checked[$this->transposh_file_location],
        );
        $request_string = array(
            'body' => array(
                'action' => 'basic_check',
                'request' => serialize($args),
                'api-key' => md5(get_bloginfo('url'))
            ),
            'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
        );

        // Start checking for an update
        $raw_response = wp_remote_post(TRANSPOSH_UPDATE_SERVICE_URL, $request_string);

        tp_logger($raw_response, 5);

        if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
            $response = unserialize($raw_response['body']);

        if (is_object($response) && !empty($response)) // Feed the update data into WP updater
            $checked_data->response[$this->transposh_plugin_basename] = $response;

        return $checked_data;
    }

    // Take over the Plugin info screen
    function plugin_api_call($def, $action, $args) {
        global $wp_version;

        if (!isset($args->slug) || ($args->slug != $this->transposh_plugin_basename))
            return false;

        // Get the current version
        $plugin_info = get_site_transient('update_plugins');
        //$current_version = $plugin_info->checked[$plugin_slug . '/' . $plugin_slug . '.php'];
        $args->version = '%VERSION';

        $request_string = array(
            'body' => array(
                'action' => $action,
                'request' => serialize($args),
                'api-key' => md5(get_bloginfo('url'))
            ),
            'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
        );

        $request = wp_remote_post(TRANSPOSH_UPDATE_SERVICE_URL, $request_string);

        if (is_wp_error($request)) {
            $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>'), $request->get_error_message());
        } else {
            $res = unserialize($request['body']);

            if ($res === false)
                $res = new WP_Error('plugins_api_failed', __('An unknown error occurred'), $request['body']);
        }

        return $res;
    }

}

$my_transposh_plugin = new transposh_plugin();

// some global functions for programmers

/**
 * Function provided for old widget include code compatability
 * @param array $args Not needed
 */
function transposh_widget($args = array(), $instance = array('title' => 'Translation'), $extcall = false) {
    global $my_transposh_plugin;
    $my_transposh_plugin->widget->widget($args, $instance, $extcall); //TODO!!! 
}

/**
 * Function for getting the current language
 * @return string
 */
function transposh_get_current_language() {
    global $my_transposh_plugin;
    return $my_transposh_plugin->target_language;
}

/**
 * Function for use in themes to allow different outputs
 * @param string $default - the default text in the default language
 * @param array $altarray - array including alternatives in the format ("es" => "hola")
 */
function transposh_echo($default, $altarray) {
    global $my_transposh_plugin;
    if (isset($altarray[transposh_get_current_language()])) {
        if (transposh_get_current_language() != $my_transposh_plugin->options->default_language) {
            echo TP_GTXT_BRK . $altarray[transposh_get_current_language()] . TP_GTXT_BRK_CLOSER;
        } else {
            echo $altarray[transposh_get_current_language()];
        }
    } else {
        echo $default;
    }
}

/**
 * This function provides easier access to logging using the singleton object
 * @param mixed $msg
 * @param int $severity
 */
function tp_logger($msg, $severity = 3, $do_backtrace = false) {
    global $my_transposh_plugin;
    if (isset($my_transposh_plugin) && is_object($my_transposh_plugin) && !$my_transposh_plugin->options->debug_enable) {
        return;
    }
    $GLOBALS['tp_logger']->do_log($msg, $severity, $do_backtrace);
}
