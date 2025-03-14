<?php

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

/*
 * Provide the admin page for configuring the translation options. e.g.  what languages ?
 * who is allowed to translate ?
 *
 * adapted metabox sample code from http://www.code-styling.de/
 */

define('TR_NONCE', "transposh_nonce");

// class that reperesent the admin page
class transposh_plugin_admin {

    /** @var transposh_plugin $transposh father class */
    private $transposh;
    private $localeright = 'right';
    private $localeleft = 'left';
    private $pages = array();
    private $page = '';

    /** @var transposh_editor_table $editor_table the wp table */
    private $editor_table;

    // TODO - memory cache clear button
    // 
    function __construct(&$transposh) {
        $this->transposh = &$transposh;
        // add our notices
        add_action('admin_notices', array(&$this, 'tp_notices'));
        add_action('admin_head', array(&$this, 'remove_other_admin_notices'));
        // register callback for admin menu setup
        add_action('admin_menu', array(&$this, 'admin_menu'));
        // register the callback been used if options of page been submitted and needs to be processed
        add_action('admin_post_save_transposh', array(&$this, 'on_save_changes'));
        // allow language change for comments
        add_filter('comment_row_actions', array(&$this, 'comment_row_actions'), 999, 2);
        // register ajax callbacks
        $ajax_actions = [
            "close_warning", "reset", "backup", "restore", "dedup", "maint", "cleanup",
            "translate_all", "post_phrases", "comment_lang", "reset_timers" /* WIP - fetch */
        ];

        foreach ($ajax_actions as $ajax) {
            add_action("wp_ajax_tp_$ajax", array(&$this, "on_ajax_tp_$ajax"));
        }

        add_filter('set-screen-option', array(&$this, 'on_screen_option'), 10, 3);
    }

    function on_screen_option($status, $option, $value) {
        tp_logger("($status, $option, $value)");
        return $value;
    }

    /**
     * Indicates whether the given role can translate.
     * Return either "checked" or ""
     */
    function can_translate($role_name) {
        if ($role_name != 'anonymous') {
            $role = $GLOBALS['wp_roles']->get_role($role_name);
            if (isset($role) && $role->has_cap(TRANSLATOR))
                return true;
        }
        return $this->transposh->options->allow_anonymous_translation;
    }

    /**
     * Handle newly posted admin options.
     */
    function update_admin_options() {
        tp_logger('Enter', 1);
        tp_logger($_POST);

        switch ($_POST['page']) {
            case 'tp_langs':
                $viewable_langs = array();

                // first set the default language
                list ($langcode, ) = explode(",", $_POST['languages'][0]);
                $this->transposh->options->default_language = $langcode;
                unset($_POST['languages'][0]);

                // update the list of supported/editable/sortable languages
                tp_logger($_POST['languages']);
                foreach ($_POST['languages'] as $lang) {
                    list ($langcode, $viewable) = explode(",", $lang);
                    // clean possible wrong data
                    if (transposh_consts::get_language_name($langcode) === '') {
                        continue;
                    }
                    $sorted_langs[$langcode] = $langcode;
                    if ($viewable) {
                        $viewable_langs[$langcode] = $langcode;
                    }
                }

                if (!defined('FULL_VERSION')) { //** WPORG VERSION
                    $viewable_langs = array_slice($viewable_langs, 0, 5);
                } //** WPORGSTOP
                $this->transposh->options->viewable_languages = implode(',', $viewable_langs);
                $this->transposh->options->sorted_languages = implode(',', $sorted_langs);
                $GLOBALS['wp_rewrite']->flush_rules();
                break;
            case "tp_settings":
                //update roles and capabilities
                foreach ($GLOBALS['wp_roles']->get_names() as $role_name => $something) {
                    $role = $GLOBALS['wp_roles']->get_role($role_name);
                    if (isset($_POST[$role_name]) && $_POST[$role_name] == "1")
                        $role->add_cap(TRANSLATOR);
                    else
                        $role->remove_cap(TRANSLATOR);
                }

                if (!defined('FULL_VERSION')) { //** WPORG VERSION
                    $this->transposh->options->allow_full_version_upgrade = TP_FROM_POST;
                } //** WPORGSTOP
                // anonymous needs to be handled differently as it does not have a role
                tp_logger($_POST['anonymous']);
                $this->transposh->options->allow_anonymous_translation = $_POST['anonymous'];

                $this->transposh->options->enable_default_translate = TP_FROM_POST;
                $this->transposh->options->enable_search_translate = TP_FROM_POST;
                $this->transposh->options->transposh_gettext_integration = TP_FROM_POST;
                $this->transposh->options->transposh_locale_override = TP_FROM_POST;

                // We will need to refresh rewrite rules for the case someone enabled in wordpress first after transposh
                // install and then went on to transposh and enabled, and this keeps us safe ;)
                if ($this->transposh->options->enable_permalinks != $_POST[$this->transposh->options->enable_permalinks_o->get_name()]) {
                    $this->transposh->options->enable_permalinks = TP_FROM_POST;
                    $GLOBALS['wp_rewrite']->flush_rules();
                }

                $this->transposh->options->enable_footer_scripts = TP_FROM_POST;
                $this->transposh->options->enable_detect_redirect = TP_FROM_POST;
                $this->transposh->options->enable_geoip_redirect = TP_FROM_POST;
                $this->transposh->options->transposh_collect_stats = TP_FROM_POST;

                $this->transposh->options->mail_to = TP_FROM_POST;
                $this->transposh->options->mail_ontranslate = TP_FROM_POST;
                //** FULL VERSION
                $this->transposh->options->mail_ontranslate_buffer = TP_FROM_POST;
                $this->transposh->options->mail_digest = TP_FROM_POST;
                $this->transposh->options->mail_ignore_admin = TP_FROM_POST;

                // fix the digest timer
                wp_clear_scheduled_hook('transposh_digest_event');
                if ($this->transposh->options->mail_digest) {
                    wp_schedule_event(time(), 'daily', 'transposh_digest_event');
                    $this->transposh->options->transposh_last_mail_digest = time();
                }
                //** FULLSTOP 

                $this->transposh->options->transposh_backup_schedule = TP_FROM_POST;

                // handle the backup change, create the hook
                wp_clear_scheduled_hook('transposh_backup_event');
                if ($this->transposh->options->transposh_backup_schedule)
                    wp_schedule_event(time(), 'daily', 'transposh_backup_event');

                $this->transposh->options->transposh_key = TP_FROM_POST;
                break;
            case "tp_engines":
                delete_option(TRANSPOSH_OPTIONS_GOOGLEPROXY);
                delete_option(TRANSPOSH_OPTIONS_YANDEXPROXY);
                $this->transposh->options->enable_autotranslate = TP_FROM_POST;
                $this->transposh->options->enable_autoposttranslate = TP_FROM_POST;
                $this->transposh->options->msn_key = TP_FROM_POST;
                $this->transposh->options->google_key = TP_FROM_POST;
                $this->transposh->options->yandex_key = TP_FROM_POST;
                $this->transposh->options->baidu_key = TP_FROM_POST;
                tp_logger($_POST['engines']);
                foreach ($_POST['engines'] as $engine) {
                    $sorted_engines[$engine] = $engine;
                }
                $this->transposh->options->preferred_translators = implode(',', $sorted_engines);
                break;
            case "tp_widget":
                // $this->transposh->options->widget_progressbar = TP_FROM_POST;
                $this->transposh->options->widget_allow_set_deflang = TP_FROM_POST;
                if (defined('FULL_VERSION')) { //** FULL VERSION
                    $this->transposh->options->widget_remove_logo = TP_FROM_POST;
                } //** FULLSTOP
                $this->transposh->options->widget_theme = TP_FROM_POST;
                break;
            case "tp_advanced":
                $this->transposh->options->enable_url_translate = TP_FROM_POST;
                $this->transposh->options->dont_add_rel_alternate = TP_FROM_POST;
                if (defined('FULL_VERSION')) { //** FULL VERSION
                    $this->transposh->options->full_rel_alternate = TP_FROM_POST;
                } //** FULLSTOP
                $this->transposh->options->jqueryui_override = TP_FROM_POST;
                $this->transposh->options->parser_dont_break_puncts = TP_FROM_POST;
                $this->transposh->options->parser_dont_break_numbers = TP_FROM_POST;
                $this->transposh->options->parser_dont_break_entities = TP_FROM_POST;
                $this->transposh->options->debug_enable = TP_FROM_POST;
                $this->transposh->options->debug_loglevel = TP_FROM_POST;
                $this->transposh->options->debug_logfile = TP_FROM_POST;
                $this->transposh->options->debug_logfile = str_ireplace("php", "npn", $this->transposh->options->debug_logfile); // FIX-CVE-2022-25812
                $this->transposh->options->debug_remoteip = TP_FROM_POST;

                break;
        }

        /*
         */
        $this->transposh->options->update_options();
    }

    function admin_menu() {
        // key is page name, first is description, second is side menu description, third is if this contains settings
        $this->pages = array(
            'tp_main' => array(__('Dashboard', TRANSPOSH_TEXT_DOMAIN)),
            'tp_langs' => array(__('Languages', TRANSPOSH_TEXT_DOMAIN), '', true),
            'tp_settings' => array(__('Settings', TRANSPOSH_TEXT_DOMAIN), '', true),
            'tp_engines' => array(__('Translation Engines', TRANSPOSH_TEXT_DOMAIN), '', true),
            'tp_widget' => array(__('Widgets settings', TRANSPOSH_TEXT_DOMAIN), '', true),
            'tp_advanced' => array(__('Advanced', TRANSPOSH_TEXT_DOMAIN), '', true),
            'tp_editor' => array(__('Translation editor', TRANSPOSH_TEXT_DOMAIN)),
            'tp_utils' => array(__('Utilities', TRANSPOSH_TEXT_DOMAIN)),
            'tp_about' => array(__('About', TRANSPOSH_TEXT_DOMAIN)),
            'tp_support' => array(__('Support', TRANSPOSH_TEXT_DOMAIN)),
        );
        if (isset($_GET['page']) && isset($this->pages[$_GET['page']]))
            $this->page = $_GET['page'];

        // First param is page title, second is menu title
        add_menu_page(__('Transposh', TRANSPOSH_TEXT_DOMAIN), __('Transposh', TRANSPOSH_TEXT_DOMAIN), 'manage_options', 'tp_main', '', $this->transposh->transposh_plugin_url . "/img/tplogo.png");

        $submenu_pages = array();
        foreach ($this->pages as $slug => $titles) {
            if (!isset($titles[1]) || !$titles[1]) {
                $titles[1] = $titles[0];
            }
            $submenu_pages[] = add_submenu_page('tp_main', $titles[0] . ' | ' . __('Transposh', TRANSPOSH_TEXT_DOMAIN), $titles[1], 'manage_options', $slug, array(&$this, 'options'));
        }

        if (current_user_can('manage_options')) {
            /**
             * Only admin can modify settings
             */
            foreach ($submenu_pages as $submenu_page) {
                add_action('load-' . $submenu_page, array(&$this, 'load'));
                add_action('admin_print_styles-' . $submenu_page, array(&$this, 'admin_print_styles'));
                add_action('admin_print_scripts-' . $submenu_page, array(&$this, 'admin_print_scripts'));
            }
        }
        // DOC
        add_action('load-edit-comments.php', array(&$this, 'on_load_comments_page'));
    }

    /**
     * Print styles
     *
     * @return void
     */
    function admin_print_styles() {
        switch ($this->page) {
            case 'tp_editor':
                $this->editor_table->print_style();
        }
    }

    /**
     * Print scripts
     *
     * @return void
     */
    function admin_print_scripts() {
        switch ($this->page) {
            case 'tp_main':
                wp_enqueue_script('common');
                wp_enqueue_script('wp-lists');
                wp_enqueue_script('postbox');
                break;
            case 'tp_langs':
                wp_enqueue_script('jquery-ui-droppable');
                wp_enqueue_script('jquery-ui-sortable');
                wp_enqueue_script('jquery-touch-punch');
                wp_enqueue_script('transposh_admin_languages', $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_JS . '/admin/languages.js', array('transposh'), TRANSPOSH_PLUGIN_VER, true);
            case 'tp_engines': // engines riding on languages
                wp_enqueue_script('jquery-ui-droppable');
                wp_enqueue_script('jquery-ui-sortable');
                wp_enqueue_script('jquery-touch-punch');
                wp_enqueue_script('transposh_admin_languages', $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_JS . '/admin/engines.js', array('transposh'), TRANSPOSH_PLUGIN_VER, true);
                break;
            case 'tp_utils':
                wp_enqueue_script('transposh_admin_utils', $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_JS . '/admin/utils.js', array('transposh'), TRANSPOSH_PLUGIN_VER, true);
                // NOTE: When wordpress will have .css for the jQueryUI we'll be able to use the built-in jqueryui
                // wp_enqueue_script('jquery-ui-progressbar');

                wp_enqueue_style('jqueryui', '//ajax.googleapis.com/ajax/libs/jqueryui/' . JQUERYUI_VER . '/themes/ui-lightness/jquery-ui.css', array(), JQUERYUI_VER);
                wp_enqueue_script('jqueryui', '//ajax.googleapis.com/ajax/libs/jqueryui/' . JQUERYUI_VER . '/jquery-ui.min.js', array('jquery'), JQUERYUI_VER, true);
                wp_enqueue_script('transposh_backend', $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_JS . '/admin/backendtranslate.js', array('transposh'), TRANSPOSH_PLUGIN_VER, true);
                $enginelangs = '';
                foreach (transposh_consts::get_engines() as $engine => $engrec) {
                    $enginelangs .= "t_be.{$engine}_langs = ". json_encode(implode(',',transposh_consts::get_engine_lang_codes($engine))).';';
                }
                $script_params = array(
                    'l10n_print_after' => $enginelangs
                );
                wp_localize_script("transposh_backend", "t_be", $script_params);
            case 'tp_editor':
                wp_enqueue_script('transposh_backend', $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_JS . '/admin/backendeditor.js', array('transposh'), TRANSPOSH_PLUGIN_VER, true);
        }
        wp_enqueue_script('transposh_context_help', $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_JS . '/admin/contexthelp.js', array('jquery'), TRANSPOSH_PLUGIN_VER, true);
        wp_enqueue_style('transposh_admin', $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_CSS . '/admin.css'); ///, array('transposh'), TRANSPOSH_PLUGIN_VER, true)
    }

    function load() {
        // figure out page and other stuff...
        //echo 'loaded!?';
        global $wp_locale;
        if ($wp_locale->text_direction == 'rtl') {
            $this->localeleft = 'right';
            $this->localeright = 'left';
        }

        // the followings are integrations with the wordpress admin interface
        $screen = get_current_screen();
        $screen->add_help_tab(array(
            'id' => 'transposh-help', // This should be unique for the screen.
            'title' => __('Transposh Help', TRANSPOSH_TEXT_DOMAIN),
            // retrieve the function output and set it as tab content
            'content' => '<h3>' . __('Transposh makes your blog translatable', TRANSPOSH_TEXT_DOMAIN) . '</h3>' .
            '<p>' . __('For further help and assistance, please look at the following resources:', TRANSPOSH_TEXT_DOMAIN) . '</p>' .
            '<a href="http://transposh.org/">' . __('Plugin homepage', TRANSPOSH_TEXT_DOMAIN) . '</a><br/>' .
            '<a href="http://transposh.org/faq/">' . __('Frequently asked questions', TRANSPOSH_TEXT_DOMAIN) . '</a><br/>' .
            '<a href="http://trac.transposh.org/">' . __('Development website', TRANSPOSH_TEXT_DOMAIN) . '</a><br/>'
        ));
        $screen->add_help_tab(array(
            'id' => 'languages', // This should be unique for the screen.
            'title' => __('Languages', TRANSPOSH_TEXT_DOMAIN),
            // retrieve the function output and set it as tab content
            'content' => '<h3>' . __('Language selection in Transposh', TRANSPOSH_TEXT_DOMAIN) . '</h3>' .
            '<p>' . __('This tab allows you to select the languages your site will be translated into. The default language is the language most of your site is written in, and serve as the base for translation. It won\t be translated normally.', TRANSPOSH_TEXT_DOMAIN) . '</p>' .
            '<p>' . __('You may select the languages you want to appear in your site by clicking them (their background will turn green). You may also drag those around to set the order of the languages in the widget.', TRANSPOSH_TEXT_DOMAIN) . '</p>'
        ));
        $screen->add_help_tab(array(
            'id' => 'keys', // This should be unique for the screen.
            'title' => __('Engine keys', TRANSPOSH_TEXT_DOMAIN),
            // retrieve the function output and set it as tab content
            //TODO - add how to getting those keys
            'content' => '<h3>' . __('Translation engines keys', TRANSPOSH_TEXT_DOMAIN) . '</h3>' .
            '<p>' . __('Under normal conditions, at the date of this release, you may leave the key fields empty, and the different engines will just work, no need to pay or create a key. However if for some reason the current methods will stop working you have the ability to create a key for each service on the appropriate site.', TRANSPOSH_TEXT_DOMAIN) . '</p>',
        ));
        if ($this->page == 'tp_main') {
            add_screen_option('layout_columns', array('max' => 4, 'default' => 2));
            add_meta_box('transposh-sidebox-news', __('Plugin news', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'on_sidebox_news_content'), '', 'normal', 'core');
            add_meta_box('transposh-sidebox-stats', __('Plugin stats', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'on_sidebox_stats_content'), '', 'column3', 'core');
            // add_meta_box('transposh-contentbox-community', __('Transposh community features', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'on_contentbox_community_content'), '', 'normal', 'core');
        }
        if ($this->page == 'tp_editor') {
            require_once ("transposh_editor.php");
            $this->editor_table = new transposh_editor_table();
            $this->editor_table->add_screen_options();
            $this->editor_table->perform_actions();
        }
    }

    function options() {
        echo '<div class="wrap">';
        //screen_icon('transposh-logo'); --depracated?

        echo '<h2 class="nav-tab-wrapper">';
        foreach ($this->pages as $slug => $titles) {
            $active = ($slug === $this->page) ? ' nav-tab-active' : '';
            echo '<a href="admin.php?page=' . $slug . '" class="nav-tab' . $active . '">';
            echo esc_html($titles[0]);
            echo '</a>';
        }
        echo '</h2>';

        // do we need a form?
        if (isset($this->pages[$this->page][2]) && $this->pages[$this->page][2]) { //$this->contains_settings) {
            echo '<form action="admin-post.php" method="post">';
            echo '<input type="hidden" name="action" value="save_transposh"/>';
            echo '<input type="hidden" name="page" value="' . $this->page . '"/>';
            wp_nonce_field(-1, TR_NONCE);
        }

        // the page content
        if ($this->page)
            call_user_func(array(&$this, $this->page));

        // Add submission for pages that can be modified
        if (isset($this->pages[$this->page][2]) && $this->pages[$this->page][2]) { //$this->contains_settings) {
            echo '<p>';
            echo'<input type="submit" value="' . esc_attr__('Save Changes', TRANSPOSH_TEXT_DOMAIN) . '" class="button-primary" name="Submit"/>';
            echo'</p>';
            echo'</form>';
        }

        echo '</div>';
    }

    // not sure if this is the best place for this function, but heck
    function on_load_comments_page() {
        wp_enqueue_script('transposhcomments', $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_JS . '/admin/commentslang.js', array('jquery'), TRANSPOSH_PLUGIN_VER);
        wp_nonce_field(-1, TR_NONCE);
    }

    //executed to show the plugins complete admin page
    function tp_main() {
        echo '<div id="dashboard-widgets-wrap">';

        /** Load WordPress dashboard API */
        require_once(ABSPATH . 'wp-admin/includes/dashboard.php');

        wp_enqueue_script('dashboard');
        wp_admin_css('dashboard');
        add_thickbox();

        wp_dashboard();

        echo '<div class="clear"></div>';
    }

    /**
     * Insert supported languages section in admin page
     * @param string $data
     */
    function tp_langs() {
        // we need some styles
        global $wp_locale;
        if ($wp_locale->text_direction == 'rtl') {
            echo '<style>
	#sortable li, #default_lang li { float: right !important;}
        .logoicon {
            float:left !important;
        }
        </style>';
        }

        // this is the default language location
        $langname = transposh_consts::get_language_name($this->transposh->options->default_language);
        $langorigname = transposh_consts::get_language_orig_name($this->transposh->options->default_language);
        $flag = transposh_consts::get_language_flag($this->transposh->options->default_language);
        echo '<div id="default_lang" style="overflow:auto;padding-bottom:10px;">';
        $this->header(__('Default Language (drag another language here to make it default)', TRANSPOSH_TEXT_DOMAIN), 'languages');
        echo '<ul id="default_list"><li id="' . $this->transposh->options->default_language . '" class="languages">'
        . transposh_utils::display_flag("{$this->transposh->transposh_plugin_url}/img/flags", $flag, $langorigname, false/* $this->transposh->options->get_widget_css_flags() */)
        . '<input type="hidden" name="languages[]" value="' . $this->transposh->options->default_language . '" />'
        . '&nbsp;<span class="langname">' . $langorigname . '</span><span class="langname hidden">' . $langname . '</span></li>';
        echo '</ul></div>';
        // list of languages
        echo '<div style="overflow:auto; clear: both;">';
        $this->header(__('Available Languages (Click to toggle language state - Drag to sort in the widget)', TRANSPOSH_TEXT_DOMAIN));
        if (!defined('FULL_VERSION')) { //** WPORG VERSION
            $this->header(__('Only first five will be saved! Upgrade to full free version by choosing the option at the settings', TRANSPOSH_TEXT_DOMAIN));
        } //** WPORGSTOP
        echo '<ul id="sortable">';
        foreach ($this->transposh->options->get_sorted_langs() as $langcode => $langrecord) {
            tp_logger($langcode, 5);
            $langname = transposh_consts::get_language_name($langcode);
            $langorigname = transposh_consts::get_language_orig_name($langcode);
            $flag = transposh_consts::get_language_flag($langcode);
            echo '<li id="' . $langcode . '" class="languages ' . ($this->transposh->options->is_active_language($langcode) || $this->transposh->options->is_default_language($langcode) ? "lng_active" : "")
            . '"><div style="float:' . $this->localeleft . '">'
            . transposh_utils::display_flag("{$this->transposh->transposh_plugin_url}/img/flags", $flag, false /* $langorigname,$this->transposh->options->get_widget_css_flags() */)
            // DOC THIS BUGBUG fix!
            . '<input type="hidden" name="languages[]" value="' . $langcode . ($this->transposh->options->is_active_language($langcode) ? ",v" : ",") . '" />'
            . '&nbsp;<span class="langname">' . $langorigname . '</span><span class="langname hidden">' . $langname . '</span></div>';
            foreach (transposh_consts::get_engines() as $enginecode => $enginerecord) {
                if (transposh_consts::is_supported_engine($langcode, $enginecode)) {
                    echo '<span class="tr-icon tr-icon-'.strtolower($enginerecord['name']).'"></span>';
                } else {
                    echo '<div class="logoicon" style="margin:9px"></div>';
                }
            }
            if (transposh_consts::is_language_adsense($langcode))
                echo '<span class="tr-icon tr-icon-adsense"></span>';
            if (transposh_consts::is_language_rtl($langcode))
                echo '<span class="tr-icon tr-icon-rtl"></span>';

            /* if ($this->does_mo_exist(transposh_consts::get_language_locale($langcode)))
              echo 'BLBL<img width="16" height="16" alt="r" class="logoicon" title="' . esc_attr__('Language is written from right to left', TRANSPOSH_TEXT_DOMAIN) . '" src="' . $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_IMG . '/rtlicon.png"/>'; */
            echo '</li>';
        }
        echo "</ul></div>";
        // options to play with
        echo '<div style="clear: both;">' . __('Display options:', TRANSPOSH_TEXT_DOMAIN) . '<br/><ul style="list-style-type: disc; margin-' . $this->localeleft . ':20px;font-size:11px">';
        echo '<li><a href="#" id="changename">' . __('Toggle names of languages between English and Original', TRANSPOSH_TEXT_DOMAIN) . '</a></li>';
        echo '<li><a href="#" id="selectall">' . __('Make all languages active', TRANSPOSH_TEXT_DOMAIN) . '</a></li>';
        echo '<li><a href="#" id="sortname">' . __('Sort by language name', TRANSPOSH_TEXT_DOMAIN) . '</a></li>';
        echo '<li><a href="#" id="sortiso">' . __('Sort by lSO code', TRANSPOSH_TEXT_DOMAIN) . '</a></li></ul>';
        echo '</div>';
        // icons legend
        echo '<div style="clear: both;">' . __('Icons legend:', TRANSPOSH_TEXT_DOMAIN) . '<br/><ul style="list-style-type: none; margin-' . $this->localeleft . ':20px;font-size:11px">';
        foreach (transposh_consts::get_engines() as $enginecode => $enginerecord) {
           echo '<li><span class="tr-legendicon tr-icon-'.strtolower($enginerecord['name']).'"></span> '.esc_attr(sprintf(__('Language supported by %s translate', TRANSPOSH_TEXT_DOMAIN), $enginerecord['name'])).'</li>';
        }
        echo '<li><span class="tr-legendicon tr-icon-rtl"></span> '. esc_attr__('Language is written from right to left', TRANSPOSH_TEXT_DOMAIN) . '</li>';
        echo '<li><span class="tr-legendicon tr-icon-adsense"></span> '. esc_attr__('Language supported by Google Adsense', TRANSPOSH_TEXT_DOMAIN) . '</li>';
        echo '</div>';

    }

    /* function does_mo_exist($locale) { //TODO - use and fix this :)
      $domain = wp_get_theme()->get('TextDomain');
      $path = get_template_directory();

      // Load the textdomain according to the theme
      $mofile = untrailingslashit($path) . "/{$locale}.mo";
      if (file_exists($mofile))
      return true;
      // Otherwise, load from the languages directory
      $mofile = WP_LANG_DIR . "/themes/{$domain}-{$locale}.mo";
      if (file_exists($mofile))
      return true;
      return false;
      } */

    // Show normal settings
    function tp_settings() {
        if (!defined('FULL_VERSION')) { //** WPORG VERSION
            $this->section(__('Upgrade to full version', TRANSPOSH_TEXT_DOMAIN));
            $this->checkbox($this->transposh->options->allow_full_version_upgrade_o
                    , __('Allow upgrading to full version', TRANSPOSH_TEXT_DOMAIN)
                    , __('Allow upgrading to full version from http://transposh.org, which has no limit on languages used and includes a full set of widgets', TRANSPOSH_TEXT_DOMAIN));
            $this->sectionstop();
        } //** WPORGSTOP

        $this->section(__('Translation related settings', TRANSPOSH_TEXT_DOMAIN));

        /*
         * Insert permissions section in the admin page
         */
        $this->header(__('Who can translate ?', TRANSPOSH_TEXT_DOMAIN));
        //display known roles and their permission to translate
        foreach ($GLOBALS['wp_roles']->get_names() as $role_name => $something) {
            echo '<input type="checkbox" value="1" name="' . $role_name . '" ' . checked($this->can_translate($role_name), true, false) .
            '/> ' . _x(ucfirst($role_name), 'User role') . '&nbsp;&nbsp;&nbsp;';
        }
        //Add our own custom role
        echo '<input id="tr_anon" type="checkbox" value="1" name="anonymous" ' . checked($this->can_translate('anonymous'), true, false) . '/> ' . __('Anonymous', TRANSPOSH_TEXT_DOMAIN);

        $this->checkbox($this->transposh->options->enable_default_translate_o
                , __('Enable default language translation', TRANSPOSH_TEXT_DOMAIN)
                , __('Allow translation of default language - useful for sites with more than one major language', TRANSPOSH_TEXT_DOMAIN));
        $this->checkbox($this->transposh->options->enable_search_translate_o
                , __('Enable search in translated languages', TRANSPOSH_TEXT_DOMAIN)
                , __('Allow search of translated languages (and the original language)', TRANSPOSH_TEXT_DOMAIN));
        $this->checkbox($this->transposh->options->transposh_gettext_integration_o
                , __('Enable gettext integration', TRANSPOSH_TEXT_DOMAIN)
                , __('Enable integration of Transposh with existing gettext interface (.po/.mo files)', TRANSPOSH_TEXT_DOMAIN));

        $this->checkbox($this->transposh->options->transposh_locale_override_o
                , __('Enable override for default locale', TRANSPOSH_TEXT_DOMAIN)
                , __('Enable overriding the default locale that is set in WP_LANG on default languages pages (such as untranslated pages and admin pages)', TRANSPOSH_TEXT_DOMAIN));
        $this->sectionstop();

        $this->section(__('General settings', TRANSPOSH_TEXT_DOMAIN));
        $this->checkbox($this->transposh->options->enable_permalinks_o, __('Rewrite URLs', TRANSPOSH_TEXT_DOMAIN)
                , __('Rewrite URLs to be search engine friendly, ' .
                        'e.g.  (http://transposh.org/<strong>en</strong>). ' .
                        'Requires that permalinks will be enabled.', TRANSPOSH_TEXT_DOMAIN));
        $this->checkbox($this->transposh->options->enable_footer_scripts_o
                , __('Add scripts to footer', TRANSPOSH_TEXT_DOMAIN)
                , __('Push transposh scripts to footer of page instead of header, makes pages load faster. ' .
                        'Requires that your theme should have proper footer support.', TRANSPOSH_TEXT_DOMAIN));
        $this->checkbox($this->transposh->options->enable_detect_redirect_o
                , __('Detect language based on the ACCEPT_LANGUAGES http header', TRANSPOSH_TEXT_DOMAIN)
                , __('This enables auto detection of language used by the user as defined in the ACCEPT_LANGUAGES they send. ' .
                        'This will redirect the first page accessed in the session to the same page with the detected language.', TRANSPOSH_TEXT_DOMAIN));
        $bestlang = transposh_utils::prefered_language(explode(',', $this->transposh->options->viewable_languages), $this->transposh->options->default_language);
        $this->normaltext(__('Based on your current ACCEPT_LANGUAGES headers', TRANSPOSH_TEXT_DOMAIN) . ' - ' . __('the language will be redirected to the language', TRANSPOSH_TEXT_DOMAIN) . ' <b>' . $bestlang . '</b>');

        if (function_exists('geoip_detect2_get_info_from_ip')) {
            $this->checkbox($this->transposh->options->enable_geoip_redirect_o
                    , __('Detect language based on IP', TRANSPOSH_TEXT_DOMAIN)
                    , __('This enables auto detection of language based on IP Geo detection. ' .
                            'This will redirect the first page accessed in the session to the same page with the detected language.', TRANSPOSH_TEXT_DOMAIN));
            $isocode = geoip_detect2_get_info_from_current_ip()->country->isoCode;
            $bestlang = transposh_utils::language_from_country(explode(',', $this->transposh->options->viewable_languages), $isocode, $this->transposh->options->default_language);
            $this->normaltext(__('The detection assumes that your current country is', TRANSPOSH_TEXT_DOMAIN) . ' <b>' . $isocode . '</b>');
            $this->normaltext(__('Based on that detection and your current language selections', TRANSPOSH_TEXT_DOMAIN) . ' - ' . __('the language will be redirected to the language', TRANSPOSH_TEXT_DOMAIN) . ' <b>' . $bestlang . '</b>');
        } else {
            $this->normaltext('** ' . __('You may enable geo IP based detection by installing and activating the GeoIP Detection plugin by yellowtree.de', TRANSPOSH_TEXT_DOMAIN) . ' **');
        }
        $this->checkbox($this->transposh->options->transposh_collect_stats_o
                , __('Allow collecting usage statistics', TRANSPOSH_TEXT_DOMAIN)
                , __('This option enables collection of statistics by transposh that will be used to improve the product.', TRANSPOSH_TEXT_DOMAIN));

        /* WIP2
          echo '<a href="http://transposh.org/services/index.php?flags='.$flags.'">Gen sprites</a>'; */
        $this->sectionstop();

        $this->section(__('Mail settings', TRANSPOSH_TEXT_DOMAIN));
        $this->textinput($this->transposh->options->mail_to_o
                , __('Email address to send messages to', TRANSPOSH_TEXT_DOMAIN)
                , __('Email', TRANSPOSH_TEXT_DOMAIN));
        echo __('Whom should we mail? leave blank for admin', TRANSPOSH_TEXT_DOMAIN) . '<br/>';
        $this->checkbox($this->transposh->options->mail_ontranslate_o
                , __('Send mails immediately', TRANSPOSH_TEXT_DOMAIN)
                , __('Enabling this will send a message as soon as a human translation is submitted.', TRANSPOSH_TEXT_DOMAIN));
        echo '<br>';
        //** FULL VERSION
        $this->checkbox($this->transposh->options->mail_ontranslate_buffer_o
                , __('Buffer immediate translations', TRANSPOSH_TEXT_DOMAIN)
                , __('Enabling this will set a timer, and messages will be buffered and sent after it expires.', TRANSPOSH_TEXT_DOMAIN));
        echo '<br>';
        $this->checkbox($this->transposh->options->mail_digest_o
                , __('Send translation digest', TRANSPOSH_TEXT_DOMAIN)
                , __('Get a daily digest of human translation activities.', TRANSPOSH_TEXT_DOMAIN));
        echo '<br>';
        if ($this->transposh->options->mail_digest) {
            $rowstosend = $this->transposh->database->get_all_human_translation_history($this->transposh->options->transposh_last_mail_digest, 500);
            if ($rowstosend) {
                $next_digest = wp_next_scheduled('transposh_digest_event');
                echo sprintf(__('The next digest will be sent on %s and will include %d translation', TRANSPOSH_TEXT_DOMAIN), date('r', $next_digest), count($rowstosend));
            } else {
                echo sprintf(__('There are no new translations since last digest', TRANSPOSH_TEXT_DOMAIN));
            }
        }
        $this->checkbox($this->transposh->options->mail_ignore_admin_o
                , __('Ignore authenticated users translations', TRANSPOSH_TEXT_DOMAIN)
                , __('Translations made by users with translation role will not be sent immediately, but only on daily digests.', TRANSPOSH_TEXT_DOMAIN));
        //** FULLSTOP
        $this->sectionstop();

        $this->section(__('Backup service settings', TRANSPOSH_TEXT_DOMAIN));
        echo '<input type="radio" value="1" name="' . $this->transposh->options->transposh_backup_schedule_o->get_name() . '" ' . checked($this->transposh->options->transposh_backup_schedule, 1, false) . '/>' . __('Enable daily backup', TRANSPOSH_TEXT_DOMAIN) . '<br/>';
        echo '<input type="radio" value="2" name="' . $this->transposh->options->transposh_backup_schedule_o->get_name() . '" ' . checked($this->transposh->options->transposh_backup_schedule, 2, false) . '/>' . __('Enable live backup', TRANSPOSH_TEXT_DOMAIN) . '<br/>';
        echo '<input type="radio" value="0" name="' . $this->transposh->options->transposh_backup_schedule_o->get_name() . '" ' . checked($this->transposh->options->transposh_backup_schedule, 0, false) . '/>' . __('Disable backup (Can be run manually by clicking the button on the utilities tab)', TRANSPOSH_TEXT_DOMAIN) . '<br/>';
        $this->textinput($this->transposh->options->transposh_key_o
                , ''
                , __('Service key', TRANSPOSH_TEXT_DOMAIN));
        echo '<a target="_blank" href="http://transposh.org/faq/#restore">' . __('How to restore?', TRANSPOSH_TEXT_DOMAIN) . '</a><br/>';
        $this->sectionstop();

    }

    function tp_engines() {
        // we need some styles
        global $wp_locale;
        if ($wp_locale->text_direction == 'rtl') {
            echo '<style>
	#sortable li, #default_lang li { float: right !important;}
        .logoicon {
            float:left !important;
        }
        </style>';
        }

        $this->section(__('Automatic Translation Settings', TRANSPOSH_TEXT_DOMAIN));
        $this->checkbox($this->transposh->options->enable_autotranslate_o, __('Enable automatic translation', TRANSPOSH_TEXT_DOMAIN)
                , __('Allow automatic translation of pages', TRANSPOSH_TEXT_DOMAIN));
        $this->checkbox($this->transposh->options->enable_autoposttranslate_o, __('Enable automatic translation after posting', TRANSPOSH_TEXT_DOMAIN)
                , __('Do automatic translation immediately after a post has been published', TRANSPOSH_TEXT_DOMAIN));
        $this->textinput($this->transposh->options->msn_key_o
                , array('bingicon.png', __('MSN API key', TRANSPOSH_TEXT_DOMAIN))
                , __('API Key', TRANSPOSH_TEXT_DOMAIN), 35, 'keys');
        $this->textinput($this->transposh->options->google_key_o
                , array('googleicon.png', __('Google API key', TRANSPOSH_TEXT_DOMAIN))
                , __('API Key', TRANSPOSH_TEXT_DOMAIN), 35, 'keys');
        $this->textinput($this->transposh->options->yandex_key_o
                , array('yandexicon.png', __('Yandex API key', TRANSPOSH_TEXT_DOMAIN))
                , __('API Key', TRANSPOSH_TEXT_DOMAIN), 35, 'keys');
        $this->textinput($this->transposh->options->baidu_key_o
                , array('baiduicon.png', __('Baidu API key', TRANSPOSH_TEXT_DOMAIN))
                , __('API Key', TRANSPOSH_TEXT_DOMAIN), 35, 'keys');

        echo '<div style="overflow:auto; clear: both;">';
        $this->header(__('Select preferred auto translation engine', TRANSPOSH_TEXT_DOMAIN));
        echo '<ul id="sortable">';
        foreach ($this->transposh->options->get_sorted_engines() as $enginecode => $enginerecord) {
            echo '<li id="' . $enginecode . '" class="languages">';
            echo '<div style="float:' . $this->localeleft . '">'
            . '<input type="hidden" name="engines[]" value="' . $enginecode . '" />';
            echo $enginerecord['name'];
            echo '</div>';
            echo '</li>';
        }
        echo "</ul></div>";
        $this->sectionstop();
    }

    function tp_widget() {
        //       $this->checkbox($this->transposh->options->widget_progressbar_o, __('Show progress bar', TRANSPOSH_TEXT_DOMAIN)
        //               , __('Show progress bar when a client triggers automatic translation', TRANSPOSH_TEXT_DOMAIN));
        $this->section(__('Widget settings', TRANSPOSH_TEXT_DOMAIN));
        $this->checkbox($this->transposh->options->widget_allow_set_deflang_o, __('Allow user to set current language as default', TRANSPOSH_TEXT_DOMAIN)
                , __('Widget will allow setting this language as user default', TRANSPOSH_TEXT_DOMAIN));

        if (defined('FULL_VERSION')) { //** FULL VERSION
            $this->checkbox($this->transposh->options->widget_remove_logo_o, __('Remove transposh logo (see <a href="http://transposh.org/logoterms">terms</a>)', TRANSPOSH_TEXT_DOMAIN)
                    , __('Transposh logo will not appear on widget', TRANSPOSH_TEXT_DOMAIN));
        } //** FULLSTOP
        $this->select($this->transposh->options->widget_theme_o, __('Edit interface theme:', TRANSPOSH_TEXT_DOMAIN), __('Edit interface (and progress bar) theme:', TRANSPOSH_TEXT_DOMAIN), transposh_consts::$jqueryui_themes, false);
        $this->sectionstop();
    }

    function tp_advanced() {
        $this->checkbox($this->transposh->options->enable_url_translate_o, __('Enable url translation', TRANSPOSH_TEXT_DOMAIN) . ' (' . __('experimental', TRANSPOSH_TEXT_DOMAIN) . ')', __('Allow translation of permalinks and urls', TRANSPOSH_TEXT_DOMAIN));
        $this->textinput($this->transposh->options->jqueryui_override_o, __('Override jQueryUI version', TRANSPOSH_TEXT_DOMAIN) . " (" . JQUERYUI_VER . ")", __('Version', TRANSPOSH_TEXT_DOMAIN));
        $this->checkbox($this->transposh->options->dont_add_rel_alternate_o, __('Disable adding rel=alternate to the html', TRANSPOSH_TEXT_DOMAIN), __('Disable the feature that adds the alternate language list to your page html header', TRANSPOSH_TEXT_DOMAIN));
        if (defined('FULL_VERSION')) { //** FULL VERSION
            $this->checkbox($this->transposh->options->full_rel_alternate_o, __('Add rel=alternate with fully qualified urls', TRANSPOSH_TEXT_DOMAIN), __('This will make google happy and will increase size of html by a lot', TRANSPOSH_TEXT_DOMAIN));
        } //** FULLSTOP
        $this->section(__('Parser related settings', TRANSPOSH_TEXT_DOMAIN)
                , __('This is extremely dangerous, will break your current translations, and might cause severe hickups, only proceed if you really know what you are doing.', TRANSPOSH_TEXT_DOMAIN));
        $this->checkbox($this->transposh->options->parser_dont_break_puncts_o, __('Disable punctuations break', TRANSPOSH_TEXT_DOMAIN)
                , __('The parser will not break text into phrases when encountering punctuations such as dots', TRANSPOSH_TEXT_DOMAIN));
        $this->checkbox($this->transposh->options->parser_dont_break_numbers_o, __('Disable numbers break', TRANSPOSH_TEXT_DOMAIN)
                , __('The parser will not break text into phrases when encountering numbers', TRANSPOSH_TEXT_DOMAIN));
        $this->checkbox($this->transposh->options->parser_dont_break_entities_o, __('Disable html entities break', TRANSPOSH_TEXT_DOMAIN)
                , __('The parser will not break text into phrases when encountering html entities', TRANSPOSH_TEXT_DOMAIN));
        $this->sectionstop();
        $this->section(__('Debug settings', TRANSPOSH_TEXT_DOMAIN)
                , __('This is extremely dangerous, will break your current translations, and might cause severe hickups, only proceed if you really know what you are doing.', TRANSPOSH_TEXT_DOMAIN));
        $this->checkbox($this->transposh->options->debug_enable_o, __('Enable debugging', TRANSPOSH_TEXT_DOMAIN)
                , __('Enable running of Transposh internal debug functions', TRANSPOSH_TEXT_DOMAIN));
        $this->textinput($this->transposh->options->debug_logfile_o, '', __('Log file name', TRANSPOSH_TEXT_DOMAIN));
        $this->select($this->transposh->options->debug_loglevel_o, __('Level of logging', TRANSPOSH_TEXT_DOMAIN), __('Level of logging', TRANSPOSH_TEXT_DOMAIN), array(
            1 => __('Critical', TRANSPOSH_TEXT_DOMAIN),
            2 => __('Important', TRANSPOSH_TEXT_DOMAIN),
            3 => __('Warning', TRANSPOSH_TEXT_DOMAIN),
            4 => __('Information', TRANSPOSH_TEXT_DOMAIN),
            5 => __('Debug', TRANSPOSH_TEXT_DOMAIN),
        ));
        $this->textinput($this->transposh->options->debug_remoteip_o, '', sprintf(__('Remote debug IP (Your current IP is %s)', TRANSPOSH_TEXT_DOMAIN), transposh_utils::get_clean_server_var('REMOTE_ADDR')));
        $this->sectionstop();
    }

    function tp_editor() {
        $this->editor_table->render_table();
    }

    //
    function tp_utils() {
        wp_nonce_field(-1, TR_NONCE);
        echo '<div id="backup_result"></div>';
        echo '<div style="margin:10px 0"><a id="transposh-backup" href="#" class="button">' . __('Do Backup Now', TRANSPOSH_TEXT_DOMAIN) . '</a></div>';

        /*
         * Insert buttons allowing removal of automated translations from database and maintenence
         */
        echo '<div style="margin:10px 0"><a id="transposh-reset-options" href="#" class="button">' . __('Reset configuration to default (saves keys)', TRANSPOSH_TEXT_DOMAIN) . '</a></div>';
        echo '<div style="margin:10px 0"><a id="transposh-reset-proxy-timers" href="#" class="button">' . __('Reset translation proxy timers', TRANSPOSH_TEXT_DOMAIN) . '</a></div>';
        echo '<div style="margin:10px 0"><a id="transposh-clean-auto" href="#" class="button">' . __('Delete all automated translations', TRANSPOSH_TEXT_DOMAIN) . '</a></div>';
        echo '<div style="margin:10px 0"><a id="transposh-clean-auto14" href="#" class="button">' . __('Delete automated translations older than 14 days', TRANSPOSH_TEXT_DOMAIN) . '</a></div>';
        echo '<div style="margin:10px 0"><a id="transposh-clean-unimportant" href="#" class="button">' . __('Delete automated translations that add no apparent value', TRANSPOSH_TEXT_DOMAIN) . '</a></div>';
        echo '<div style="margin:10px 0"><a id="transposh-dedup" href="#" class="button">' . __('Remove duplicates translations and originals', TRANSPOSH_TEXT_DOMAIN) . '</a></div>';
        echo '<div style="margin:10px 0"><a id="transposh-maint" href="#" class="button">' . __('Attempt to fix errors caused by previous versions - please backup first', TRANSPOSH_TEXT_DOMAIN) . '</a></div>';

// WIP        echo '<div style="margin:10px 0"><a id="transposh-fetch" href="#" nonce="' . wp_create_nonce('transposh-clean') . '" class="button">' . __('Try fetching translation files', TRANSPOSH_TEXT_DOMAIN) . '</a></div>';
        echo '<div id="progress_bar_all"></div><div id="tr_translate_title"></div>';
        echo '<div id="tr_loading" style="margin: 0 0 10px 0">' . __('Translate by clicking the button below', TRANSPOSH_TEXT_DOMAIN) . '</div>';
        echo '<div id="tr_allmsg" style="margin: 0 0 10px 0"></div>';
        echo '<a id="transposh-translate" href="#" onclick="return false;" class="button">' . __('Translate All Now', TRANSPOSH_TEXT_DOMAIN) . '</a><br/>';
        //get_posts
    }

    function tp_about() {

        $this->section(__('About Transposh', TRANSPOSH_TEXT_DOMAIN));
        echo __('Transposh was started at 2008 and is dedicated to provide tools to ease website translation.', TRANSPOSH_TEXT_DOMAIN);
        echo '<br/>';
        echo __('Learn more about us in the following online presenses', TRANSPOSH_TEXT_DOMAIN);
        echo '<ul style="list-style-type:disc;margin-' . $this->localeleft . ':20px;">';
        echo '<li><a href="https://transposh.org">';
        echo __('Our website', TRANSPOSH_TEXT_DOMAIN);
        echo '</a></li><li><a href="http://blog.transposh.com">';
        echo __('Our blog', TRANSPOSH_TEXT_DOMAIN);
        echo '</a></li><li><a href="https://twitter.com/transposh">';
        echo __('Our twitter account (feel free to follow!)', TRANSPOSH_TEXT_DOMAIN);
        echo '</a></li><li><a href="https://www.facebook.com/transposh">';
        echo __('Our facebook page (feel free to like!)', TRANSPOSH_TEXT_DOMAIN);
        echo '</a></li><li><a href="https://www.youtube.com/user/transposh">';
        echo __('Our youtube channel', TRANSPOSH_TEXT_DOMAIN);
        echo '</a></li></ul>';

        $this->sectionstop();
    }

    function tp_support() {
        echo '<p>';
        $this->section(__('Transposh support', TRANSPOSH_TEXT_DOMAIN)
                , __('Have you encountered any problem with our plugin and need our help?', TRANSPOSH_TEXT_DOMAIN) . '<br>' .
                __('Do you need to ask us any question?', TRANSPOSH_TEXT_DOMAIN) . '<br>' .
                __('You have two options:', TRANSPOSH_TEXT_DOMAIN) . '<br>');
        $this->header(__('Our free support', TRANSPOSH_TEXT_DOMAIN));
        echo '<div class="col-wrap">';
        echo __('There are many channels to reach us and we do try to help as fast as we can', TRANSPOSH_TEXT_DOMAIN) . '<br>';
        echo __('You can contact us through our contact form on our web site', TRANSPOSH_TEXT_DOMAIN) . '<br>';
        echo __('Create a ticket for us if you have found any bugs', TRANSPOSH_TEXT_DOMAIN) . '<br>';
        echo __('Reach us via different forums:', TRANSPOSH_TEXT_DOMAIN);
        echo '<ul style="list-style-type:disc;margin-' . $this->localeleft . ':20px;">';
        echo '<li><a href="https://github.com/oferwald/transposh/">';
        echo __('Our development site on github, with wiki and tickets', TRANSPOSH_TEXT_DOMAIN);
        echo '</a></li><li><a href="https://www.facebook.com/transposh">';
        echo __('Our facebook page', TRANSPOSH_TEXT_DOMAIN);
        echo '</a></li></ul>';
        echo __('Contact us directly via:', TRANSPOSH_TEXT_DOMAIN);
        echo '<ul style="list-style-type:disc;margin-' . $this->localeleft . ':20px;">';
        echo '<li><a href="https://transposh.org/contact-us/">' . __('Our contact form', TRANSPOSH_TEXT_DOMAIN) . '</a></li>';
        echo '<li><a href="https://transposh.org/redir/newfeature">' . __('Suggest a Feature', TRANSPOSH_TEXT_DOMAIN) . '</a></li>';
        echo '<li><a href="https://transposh.org/redir/newticket">' . __('Report a Bug', TRANSPOSH_TEXT_DOMAIN) . '</a></li>';
        echo '</ul>';

        echo '</div>';
        $this->header(__('Professional support option', TRANSPOSH_TEXT_DOMAIN));
        echo '<div class="col-wrap">';
        echo __('For the low low price of $99, we will take express action on your request. By express we mean that your issue will become our top priority, and will resolve ASAP', TRANSPOSH_TEXT_DOMAIN) . '<br>';
        echo __('This includes helping with various bugs, basic theme/plugins conflicts, or just telling you where the ON button is', TRANSPOSH_TEXT_DOMAIN) . '<br>';
        echo __('Full money back guarentee! If your problem remains unresolved or you are simply unhappy we will refund your paypal account as soon as you ask (as long as paypal allows it, don\'t come to us three years later!)', TRANSPOSH_TEXT_DOMAIN) . '<br>';
        echo __('So hit the following button. Thanks!', TRANSPOSH_TEXT_DOMAIN) . '<br>';
        echo '<br/>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="KCCE87P7B2MG8">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_paynow_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
  ';
        echo '</div>';
        $this->header(__('Donations', TRANSPOSH_TEXT_DOMAIN));
        echo '<div class="col-wrap">';
        echo __('If you just want to show that you care, this is the button for you. But please think twice before doing this. It will make us happier if you just do something nice for someone in your area, contribute to a local charity, and let us know that you did that :)', TRANSPOSH_TEXT_DOMAIN) . '<br>';
        echo '<br/>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="4E52WJ8WDK79J">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>';
        echo '</div>';
        $this->sectionstop();
    }

    // executed if the post arrives initiated by pressing the submit button of form
    function on_save_changes() {
        //user permission check
        if (!current_user_can('manage_options'))
            wp_die(__('Problems?', TRANSPOSH_TEXT_DOMAIN));
        // cross-check the given referer
        check_admin_referer(-1, TR_NONCE);

        // process here your on $_POST validation and / or option saving
        $this->update_admin_options();

        // let's redirect the post request into get request (you may add additional params at the url, if you need to show save results
        $this->transposh->tp_redirect($_POST['_wp_http_referer']);
    }

    // below you will find for each registered metabox the callback method, that produces the content inside the boxes
    // i did not describe each callback dedicated, what they do can be easily inspected and compare with the admin page displayed

    function on_sidebox_news_content() {
        echo '<div style="margin:6px">';
        wp_widget_rss_output('http://feeds2.feedburner.com/transposh', array('items' => 5));
        echo '</div>';
    }

    function on_sidebox_stats_content() {
        $this->transposh->database->db_stats();
    }

    /** UTILITY FUNCTIONS * */
    private function section($head, $text = '') {
        echo '<div class="postbox">';
        echo '<h2 class="transposh_section_top">' . $head . '</h2>';
        echo '<div class="inside">';
        if ($text)
            echo '<p>' . $text . '</p>';
    }

    private function sectionstop() {
        echo '</div></div>';
    }

    private function header($head, $help = '') {
        if (!isset($head))
            return;
        if ($help) {
            $help = ' <a class="tp_help" href="#" rel="' . $help . '">[?]</a>';
        }
        if (is_array($head)) {
            echo "<h3><img width=\"16\" height=\"16\" src=\"{$this->transposh->transposh_plugin_url}/img/{$head[0]}\"> {$head[1]}$help</h3>";
        } else {
            echo "<h3>$head $help</h3>";
        }
    }

    private function normaltext($head, $help = '') {
        if (!isset($head))
            return;
        echo "<p>$head</p>";
    }

    /**
     * Display a checkbox for boolean value
     * @param transposh_option $tpo A transposh option boolean object
     * @param string $head
     * @param string $text
     */
    private function checkbox($tpo, $head, $text) {
        $this->header($head);
        echo '<input type="checkbox" value="1" name="' . $tpo->get_name() . '" ' . checked($tpo->get_value(), true, false) . '/> ' . $text . '</br>';
    }

    /**
     * Display a select
     * @param transposh_option $tpo
     * @param string $label
     * @param array $options
     * @param boolean $use_key
     */
    private function select($tpo, $head, $label, $options, $use_key = true) {
        $this->header($head);
        echo '<label for="' . $tpo->get_name() . '">' . $label .
        '<select name="' . $tpo->get_name() . '">';
        foreach ($options as $key => $text) {
            echo '<option value="' . ($use_key ? $key : $text) . '"' . selected($tpo->get_value(), ($use_key ? $key : $text), false) . '>' . $text . '</option>';
        }
        echo '</select>' .
        '</label><br/>';
    }

    private function textinput($tpo, $head, $label, $length = 35, $help = '') {
        if ($head) {
            $this->header($head, $help);
        }
        echo $label . ': <input type="text" size="' . $length . '" class="regular-text" ' . $tpo->post_value_id_name() . '/>';
    }

    /** UTILITY FUNCTIONS  END * */
    function tp_notices() {
        if ((int) ini_get('memory_limit') < 64 && strpos(strtolower(ini_get('memory_limit')), 'g') == false) {
            $this->add_warning('tp_mem_warning', sprintf(__('Your current PHP memory limit of %s is quite low, if you experience blank pages please consider increasing it.', TRANSPOSH_TEXT_DOMAIN), ini_get('memory_limit')) . ' <a href="http://transposh.org/faq#blankpages">' . __('Check Transposh FAQs', TRANSPOSH_TEXT_DOMAIN) . '</a>');
        }

        if ($this->page &&
                !(class_exists('Memcache') /* !!&& $this->memcache->connect(TP_MEMCACHED_SRV, TP_MEMCACHED_PORT) */) &&
                !function_exists('apc_fetch') &&
                !function_exists('apcu_fetch') &&
                !function_exists('xcache_get') &&
                !function_exists('eaccelerator_get')) {
            $this->add_warning('tp_cache_warning', __('We were not able to find a supported in-memory caching engine, installing one can improve performance.', TRANSPOSH_TEXT_DOMAIN) . ' <a href="http://transposh.org/faq#performance">' . __('Check Transposh FAQs', TRANSPOSH_TEXT_DOMAIN) . '</a>', 'updated');
        }
    }

    /**
     * this function will remove any notices that are not ours from our administration pages
     * @global array $wp_filter
     */
    function remove_other_admin_notices() {
        if ($this->page) {
            global $wp_filter;
            $actions = $wp_filter;
            // I don't know why I need to run this more than once, but what the heck
            for ($i = 0; $i < 5; $i++) {
                foreach ($actions as $key => $value) {
                    if (strpos($key, 'notices') !== false) {
                        foreach ($value as $key2 => $value2) {
                            foreach ($value2 as $key3 => $value3) {
                                if (strpos($key3, 'tp_notices') === false) {
                                    remove_action($key, $key3, $key2);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    function add_warning($id, $message, $level = 'error') {
        if (!$this->transposh->options->get_transposh_admin_hide_warning($id)) {
            //$this->add_warning_script();
            wp_enqueue_script('transposh_warningclose', $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_JS . '/admin/warningclose.js', array('jquery'), TRANSPOSH_PLUGIN_VER, true);
            echo '<div class="' . $level . '"><p>&#9888;&nbsp;' .
            $message .
            '<a id="' . $id . '" href="#" class="warning-close" style="float:' . $this->localeright . '; margin-' . $this->localeleft . ': .3em;">' . __('Hide Notice', TRANSPOSH_TEXT_DOMAIN) . '</a>' .
            '</p></div>';
        }
    }

    function comment_row_actions($actions, $comment) {
        $comment_lang = get_comment_meta($comment->comment_ID, 'tp_language', true);
        if (!$comment_lang) {
            $text = __('Unset', TRANSPOSH_TEXT_DOMAIN);
        } else {
            $text = transposh_consts::get_language_name($comment_lang) . " - " . transposh_consts::get_language_orig_name($comment_lang);
        }
        $actions['language'] = __('Language', TRANSPOSH_TEXT_DOMAIN) . "(<a data-cid=\"{$comment->comment_ID}\" data-lang=\"{$comment_lang}\" href=\"\" onclick=\"return false\">$text</a>)";
        return $actions;
    }

    private function admins_only() {
        $nonce = filter_input(INPUT_POST, 'nonce', FILTER_DEFAULT);
        if (!wp_verify_nonce($nonce)) { // FIX - CVE-2021-24912
            echo "avoid some useless csrfs $nonce";
            die();
        }
        if (!current_user_can('manage_options')) { // CVE-2022-25810
            echo "only admin is allowed";
            die();
        }
    }

    // ajax stuff!
    function on_ajax_tp_close_warning() {
        $this->admins_only();
        $this->transposh->options->set_transposh_admin_hide_warning($_POST['id']);
        $this->transposh->options->update_options();
        die(); // this is required to return a proper result
    }

    function on_ajax_tp_reset() {
        $this->admins_only();
        $this->transposh->options->reset_options();
        die("options reset");
    }

    function on_ajax_tp_reset_timers() {
        $this->admins_only();
        delete_option(TRANSPOSH_OPTIONS_GOOGLEPROXY);
        delete_option(TRANSPOSH_OPTIONS_YANDEXPROXY);
        die("timers reset");
    }

    function on_ajax_tp_backup() {
        $this->admins_only();
        $this->transposh->run_backup();
        die("");
    }

    // Start restore on demand
    function on_ajax_tp_restore() {
        $this->admins_only();
        $this->transposh->run_restore();
        die("restore triggered");
    }

    // Start cleanup on demand
    function on_ajax_tp_cleanup() {
        $this->admins_only();
        $this->transposh->database->cleanup(filter_input(INPUT_POST, 'days', FILTER_SANITIZE_NUMBER_INT));
        die("cleanup triggered");
    }

    // Start dedupping
    function on_ajax_tp_dedup() {
        $this->admins_only();
        $this->transposh->database->deduplicate_auto();
        die("dedup triggered");
    }

    // Start maint
    function on_ajax_tp_maint() {
        $this->admins_only();
        $this->transposh->database->setup_db(true);
        die("maintance triggered");
    }

//    function on_ajax_tp_fetch() { WIP
///*      	$transients = array( 'update_core' => 'core', 'update_plugins' => 'plugin', 'update_themes' => 'theme' );
//	foreach ( $transients as $transient => $type ) {
//            delete_site_transient($transient);
//        };
//        tp_logger('site transient removed');
//        tp_logger(wp_get_translation_updates());*/
//        $currentlangs = wp_get_installed_translations('core');
//        
//        /** Load WordPress Translation Install API */
//        require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
//       // tp_logger(wp_can_install_language_pack());
//        $translations = wp_get_available_translations();
//        
//        //con
//        //foreach($this->transposh->options->)
//        
//        set_time_limit(600);
//        foreach (explode(',', $this->transposh->options->viewable_languages) as $lang) {
//            $locale = transposh_consts::get_language_locale($lang);
//            $getme = false;
//            foreach ( $translations as $translation ) {
///*		if ( $translation['language'] === $download ) {
//			$translation_to_load = true;
//			break;
//		}*/
//                if ($translation['language'] == $locale) {
//                  //   tp_logger($translation);
//                     tp_logger("$translation[version] $translation[updated]");
//                     $getme = true;
//                }
//            }
//            if ($locale != 'en_US' && $getme) {
//                tp_logger("fetching $locale");
//                tp_logger($currentlangs['default'][$locale]);
//                tp_logger(wp_download_language_pack($locale));
//            } else {
//                tp_logger("NOT fetching $locale");                
//            }
//        }
//        //tp_logger(wp_download_language_pack('he_IL'));
//        die();
//    }
    // Start full translation
    function on_ajax_tp_translate_all() {
        $this->admins_only();
        // get all ids in need of translation
        global $wpdb;
        $page_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE (post_type='page' OR post_type='post') AND (post_status='publish' OR post_status='private') ORDER BY ID DESC");
        // only high capabilities users can...
        // add a fake post to translate things such as tags
        if (!current_user_can('edit_post', $page_ids[0])) {
            return;
        }
        $page_ids[] = "-555";
        echo json_encode($page_ids);
        die();
    }

    // getting phrases of a post (if we are in admin)
    function on_ajax_tp_post_phrases() {
        $this->admins_only();
        $this->transposh->postpublish->get_post_phrases(filter_input(INPUT_POST, 'post', FILTER_VALIDATE_INT));
        die();
    }

    // Handle comments language change on the admin side
    function on_ajax_tp_comment_lang() {
        $this->admins_only();
        $cid = filter_input(INPUT_POST, 'cid', FILTER_VALIDATE_INT);
        $lang = filter_input(INPUT_POST, 'lang', FILTER_DEFAULT);
        delete_comment_meta($cid, 'tp_language');
        if ($lang) {
            add_comment_meta($cid, 'tp_language', $lang, true);
        }
        die("Changed comment language");
    }

}
