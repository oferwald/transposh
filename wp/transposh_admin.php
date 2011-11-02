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
 * Provide the admin page for configuring the translation options. eg.  what languages ?
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

// constructor of class, PHP4 compatible construction for backward compatibility
    function transposh_plugin_admin(&$transposh) {
        $this->transposh = &$transposh;
        // add filter for WordPress 2.8 changed backend box system !
        add_filter('screen_layout_columns', array(&$this, 'on_screen_layout_columns'), 10, 2);
        // add some help
        add_filter('contextual_help_list', array(&$this, 'on_contextual_help'), 100, 2);
        // register callback for admin menu  setup
        add_action('admin_menu', array(&$this, 'on_admin_menu'));
        // register the callback been used if options of page been submitted and needs to be processed
        add_action('admin_post_save_transposh', array(&$this, 'on_save_changes'));
        // register a callback to allow admin removal of warnings
        add_action('wp_ajax_closed_tpwarn', array(&$this, 'on_closed_tpwarn'));
    }

    /**
     * Indicates whether the given role can translate.
     * Return either "checked" or ""
     */
    function can_translate($role_name) {
        if ($role_name != 'anonymous') {
            $role = $GLOBALS['wp_roles']->get_role($role_name);
            if (isset($role) && $role->has_cap(TRANSLATOR))
                    return 'checked="checked"';
        }
        else
                return ($this->transposh->options->get_anonymous_translation()) ? 'checked="checked"' : '';
    }

    /**
     * Handle newly posted admin options.
     */
    function update_admin_options() {
        logger('Enter', 1);
        logger($_POST);
        $viewable_langs = array();
        $editable_langs = array();

        //update roles and capabilities
        foreach ($GLOBALS['wp_roles']->get_names() as $role_name => $something) {
            $role = $GLOBALS['wp_roles']->get_role($role_name);
            if ($_POST[$role_name] == "1") $role->add_cap(TRANSLATOR);
            else $role->remove_cap(TRANSLATOR);
        }

        // anonymous needs to be handled differently as it does not have a role
        $this->transposh->options->set_anonymous_translation($_POST['anonymous']);

        // first set the default language
        list ($langcode, $viewable, $translateable) = explode(",", $_POST['languages'][0]);
        $this->transposh->options->set_default_language($langcode);
        unset($_POST['languages'][0]);

        // update the list of supported/editable/sortable languages
        logger($_POST['languages']);
        foreach ($_POST['languages'] as $code => $lang) {
            list ($langcode, $viewable, $translateable) = explode(",", $lang);
            $sorted_langs[$langcode] = $langcode;
            if ($viewable) {
                $viewable_langs[$langcode] = $langcode;
                // force that every viewable lang is editable
                $editable_langs[$langcode] = $langcode;
            }

            if ($translateable) {
                $editable_langs[$langcode] = $langcode;
            }
        }

        $this->transposh->options->set_viewable_langs(implode(',', $viewable_langs));
        $this->transposh->options->set_editable_langs(implode(',', $editable_langs));
        $this->transposh->options->set_sorted_langs(implode(',', $sorted_langs));

        if ($this->transposh->options->get_enable_permalinks() != $_POST[ENABLE_PERMALINKS]) {
            $this->transposh->options->set_enable_permalinks($_POST[ENABLE_PERMALINKS]);
            // rewrite rules - refresh. - because we want them set or unset upon this change
            // TODO - need to check if its even needed
            add_filter('rewrite_rules_array', array(&$this->transposh, 'update_rewrite_rules'));
            $GLOBALS['wp_rewrite']->flush_rules();
        }

        $this->transposh->options->set_enable_footer_scripts($_POST[ENABLE_FOOTER_SCRIPTS]);
        $this->transposh->options->set_enable_detect_language($_POST[ENABLE_DETECT_LANG_AND_REDIRECT]);
        $this->transposh->options->set_transposh_collect_stats($_POST[TRANSPOSH_COLLECT_STATS]);
        $this->transposh->options->set_enable_auto_translate($_POST[ENABLE_AUTO_TRANSLATE]);
        $this->transposh->options->set_enable_auto_post_translate($_POST[ENABLE_AUTO_POST_TRANSLATE]);
        $this->transposh->options->set_enable_default_translate($_POST[ENABLE_DEFAULT_TRANSLATE]);
        $this->transposh->options->set_enable_search_translate($_POST[ENABLE_SEARCH_TRANSLATE]);
        $this->transposh->options->set_enable_url_translate($_POST[ENABLE_URL_TRANSLATE]);
        $this->transposh->options->set_transposh_gettext_integration($_POST[TRANSPOSH_GETTEXT_INTEGRATION]);
        $this->transposh->options->set_transposh_default_locale_override($_POST[TRANSPOSH_DEFAULT_LOCALE_OVERRIDE]);
        $this->transposh->options->set_preferred_translator($_POST[PREFERRED_TRANSLATOR]);
        $this->transposh->options->set_msn_key($_POST[MSN_TRANSLATE_KEY]);
        $this->transposh->options->set_google_key($_POST[GOOGLE_TRANSLATE_KEY]);
        $this->transposh->options->set_transposh_key($_POST[TRANSPOSH_KEY]);

        // handle change of schedule for backup to daily
        if ($_POST[TRANSPOSH_BACKUP_SCHEDULE] != $this->transposh->options->get_transposh_backup_schedule()) {
            wp_clear_scheduled_hook('transposh_backup_event');
            if ($_POST[TRANSPOSH_BACKUP_SCHEDULE] == 1)
                    wp_schedule_event(time(), 'daily', 'transposh_backup_event');
        }
        $this->transposh->options->set_transposh_backup_schedule($_POST[TRANSPOSH_BACKUP_SCHEDULE]);

        $this->transposh->options->update_options();
    }

    // for WordPress 2.8 we have to tell, that we support 2 columns !
    function on_screen_layout_columns($columns, $screen) {
        if ($screen == $this->pagehook) {
            $columns[$this->pagehook] = 2;
        }
        return $columns;
    }

    //add some help
    function on_contextual_help($filterVal, $screen) {
        if ($screen == 'settings_page_transposh') {
            $filterVal['settings_page_transposh'] = '<p>' . __('Transposh makes your blog translatable', TRANSPOSH_TEXT_DOMAIN) . '</p>' .
                    '<a href="http://transposh.org/">' . __('Plugin homepage', TRANSPOSH_TEXT_DOMAIN) . '</a><br/>' .
                    '<a href="http://transposh.org/faq/">' . __('Frequently asked questions', TRANSPOSH_TEXT_DOMAIN) . '</a>';
        }
        return $filterVal;
    }

    // extend the admin menu
    function on_admin_menu() {
        //add our own option page, you can also add it to different sections or use your own one
        $this->pagehook = add_options_page(__('Transposh control center', TRANSPOSH_TEXT_DOMAIN), __('Transposh', TRANSPOSH_TEXT_DOMAIN), 'manage_options', TRANSPOSH_ADMIN_PAGE_NAME, array(&$this, 'on_show_page'));
        // register callback gets call prior your own page gets rendered
        add_action('load-' . $this->pagehook, array(&$this, 'on_load_page'));
    }

    // will be executed if wordpress core detects this page has to be rendered
    function on_load_page() {
        //ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
        wp_enqueue_script('common');
        wp_enqueue_script('wp-lists');
        wp_enqueue_script('postbox');

        //TODO - make up my mind on using .css flags here (currently no)
        //if ($this->transposh->options->get_widget_css_flags())
//            wp_enqueue_style("transposh_flags",$this->transposh->transposh_plugin_url."/widgets/flags/tpw_flags.css",array(),TRANSPOSH_PLUGIN_VER);
        wp_enqueue_script('jquery-ui-droppable');
        wp_enqueue_script('transposh_control', $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_JS . '/transposhcontrol.js', array(), TRANSPOSH_PLUGIN_VER, true);
        wp_localize_script('transposh_control', 't_jp', array(
            'post_url' => $this->transposh->post_url,
            'preferred' => $this->transposh->options->get_preferred_translator(),
            'l10n_print_after' => 't_jp.g_langs = ' . json_encode(transposh_consts::$google_languages) . '; t_jp.m_langs = ' . json_encode(transposh_consts::$bing_languages) . ';'/*
                  /* ,
                  'plugin_url' => $this->transposh_plugin_url,
                  'edit' => ($this->edit_mode? '1' : ''),
                  //'rtl' => (in_array ($this->target_language, $GLOBALS['rtl_languages'])? 'true' : ''),
                  'lang' => $this->target_language,
                  // those two options show if the script can support said engines
                  'prefix' => SPAN_PREFIX,
                  'preferred'=> $this->options->get_preferred_translator(),
                  'progress'=>$this->edit_mode || $this->options->get_widget_progressbar() ? '1' : '') */
//   	,'l10n_print_after' => 'try{convertEntities(inlineEditL10n);}catch(e){};'
                // need to do this because the way wordpress encodes stuff
        ));
        wp_enqueue_script('google', "http://www.google.com/jsapi", array(), '1', true);
        wp_enqueue_script('transposh_admin', $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_JS . '/transposhadmin.js', array(), TRANSPOSH_PLUGIN_VER, true);
        wp_enqueue_style('jqueryui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/ui-lightness/jquery-ui.css', array(), '1.8.2');
        wp_enqueue_script('jqueryui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/jquery-ui.min.js', array('jquery'), '1.8.2', true);

        //add several metaboxes now, all metaboxes registered during load page can be switched off/on at "Screen Options" automatically, nothing special to do therefore
        add_meta_box('transposh-sidebox-about', __('About this plugin', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'on_sidebox_about_content'), $this->pagehook, 'side', 'core');
        add_meta_box('transposh-sidebox-widget', __('Widget settings', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'on_sidebox_widget_content'), $this->pagehook, 'side', 'core');
        add_meta_box('transposh-sidebox-news', __('Plugin news', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'on_sidebox_news_content'), $this->pagehook, 'side', 'core');
        add_meta_box('transposh-sidebox-stats', __('Plugin stats', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'on_sidebox_stats_content'), $this->pagehook, 'side', 'core');
        add_meta_box('transposh-sidebox-translate', __('Translate all', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'on_sidebox_translate_content'), $this->pagehook, 'side', 'core');
        add_meta_box('transposh-contentbox-languages', __('Supported languages', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'on_contentbox_languages_content'), $this->pagehook, 'normal', 'core');
        add_meta_box('transposh-contentbox-translation', __('Translation settings', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'on_contentbox_translation_content'), $this->pagehook, 'normal', 'core');
        add_meta_box('transposh-contentbox-autotranslation', __('Automatic translation settings', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'on_contentbox_auto_translation_content'), $this->pagehook, 'normal', 'core');
        add_meta_box('transposh-contentbox-general', __('Generic settings', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'on_contentbox_generic_content'), $this->pagehook, 'normal', 'core');
        add_meta_box('transposh-contentbox-database', __('Database maintenance', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'on_contentbox_database_content'), $this->pagehook, 'normal', 'core');
    }

    //executed to show the plugins complete admin page
    function on_show_page() {
        global $wp_locale;
        if ($wp_locale->text_direction == 'rtl') {
            $this->localeleft = 'right';
            $this->localeright = 'left';
        }

        //we need the global screen column value to beable to have a sidebar in WordPress 2.8
        //global $screen_layout_columns;
        //add a 3rd content box now for demonstration purpose, boxes added at start of page rendering can't be switched on/off,
        //may be needed to ensure that a special box is always available
        add_meta_box('transposh-contentbox-community', __('Transposh community features', TRANSPOSH_TEXT_DOMAIN), array(&$this, 'on_contentbox_community_content'), $this->pagehook, 'normal', 'core');
        //define some data can be given to each metabox during rendering - not used now
        //$data = array('My Data 1', 'My Data 2', 'Available Data 1');

        echo '<div id="transposh-general" class="wrap">';
        screen_icon('options-general');
        echo '<h2>' . __('Transposh', TRANSPOSH_TEXT_DOMAIN) . '</h2>' .
        '<form action="admin-post.php" method="post">';

        // add some user warnings that leads to some FAQs
        if ((int) ini_get('memory_limit') < 64) {
            $this->add_warning('tp_mem_warning', sprintf(__('Your current PHP memory limit of %s is quite low, if you experience blank pages please consider increasing it.', TRANSPOSH_TEXT_DOMAIN), ini_get('memory_limit')) . ' <a href="http://transposh.org/faq#blankpages">' . __('Check Transposh FAQs', TRANSPOSH_TEXT_DOMAIN) . '</a>');
        }

        if (!(class_exists('Memcache') /* !!&& $this->memcache->connect(TP_MEMCACHED_SRV, TP_MEMCACHED_PORT) */) && !function_exists('apc_fetch') && !function_exists('xcache_get') && !function_exists('eaccelerator_get')) {
            $this->add_warning('tp_cache_warning', __('We were not able to find a supported in-memory caching engine, installing one can improve performance.', TRANSPOSH_TEXT_DOMAIN) . ' <a href="http://transposh.org/faq#performance">' . __('Check Transposh FAQs', TRANSPOSH_TEXT_DOMAIN) . '</a>');
        }

        wp_nonce_field(TR_NONCE);
        wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false);
        wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false);

        echo '<input type="hidden" name="action" value="save_transposh" />' .
        '<div id="poststuff" class="metabox-holder' . ((2 == $GLOBALS['screen_layout_columns']) ? ' has-right-sidebar' : '') . '">' .
        '<div id="side-info-column" class="inner-sidebar">';
        do_meta_boxes($this->pagehook, 'side', '');

        echo '</div>' .
        '<div id="post-body" class="has-sidebar">' .
        '<div id="post-body-content" class="has-sidebar-content">	';

        do_meta_boxes($this->pagehook, 'normal', '');
        /* Maybe add static content here later */
        //do_meta_boxes($this->pagehook, 'additional', $data);

        echo '<p>' .
        '<input type="submit" value="' . __('Save Changes') . '" class="button-primary" name="Submit"/>' .
        '</p>' .
        '</div>' .
        '</div>' .
        '<br class="clear"/>' .
        '</div>' .
        '</form>' .
        '</div>' .
        '<script type="text/javascript">' . "\n" .
        '//<![CDATA[' . "\n" .
        'jQuery(document).ready( function($) {';
        // close postboxes that should be closed
        echo "$('.if-js-closed').removeClass('if-js-closed').addClass('closed');";
        // postboxes setup
        echo "postboxes.add_postbox_toggles('" . $this->pagehook . "');" .
        '});	' . "\n" .
        '//]]>' . "\n" .
        '</script>';
    }

    // executed if the post arrives initiated by pressing the submit button of form
    function on_save_changes() {
        //user permission check
        if (!current_user_can('manage_options'))
                wp_die(__('Problems?', TRANSPOSH_TEXT_DOMAIN));
        // cross check the given referer
        check_admin_referer(TR_NONCE);

        // process here your on $_POST validation and / or option saving
        $this->transposh->widget->transposh_widget_post(FALSE);
        $this->update_admin_options();

        // lets redirect the post request into get request (you may add additional params at the url, if you need to show save results
        $this->transposh->tp_redirect($_POST['_wp_http_referer']);
    }

    // below you will find for each registered metabox the callback method, that produces the content inside the boxes
    // i did not describe each callback dedicated, what they do can be easily inspected and compare with the admin page displayed

    function on_sidebox_about_content($data) {
        echo '<ul style="list-style-type:disc;margin-' . $this->localeleft . ':20px;">';
        echo '<li><a href="http://transposh.org/">' . __('Plugin Homepage', TRANSPOSH_TEXT_DOMAIN) . '</a></li>';
        echo '<li><a href="http://transposh.org/redir/newfeature">' . __('Suggest a Feature', TRANSPOSH_TEXT_DOMAIN) . '</a></li>';
        // support Forum
        echo '<li><a href="http://transposh.org/redir/newticket">' . __('Report a Bug', TRANSPOSH_TEXT_DOMAIN) . '</a></li>';
        // donate with PayPal
        echo '</ul>';
    }

    function on_sidebox_widget_content($data) {
        $this->transposh->widget->transposh_widget_control();
    }

    function on_sidebox_news_content($data) {
        echo '<div style="margin:6px">';
        wp_widget_rss_output('http://feeds2.feedburner.com/transposh', array('items' => 5));
        echo '</div>';
    }

    function on_sidebox_stats_content($data) {
        $this->transposh->database->db_stats();
    }

    function on_sidebox_translate_content($data) {
        echo '<div id="progress_bar_all"></div><div id="tr_translate_title"></div>';
        echo '<div id="tr_loading" style="margin: 0 0 10px 0">' . __('Translate by clicking the button below', TRANSPOSH_TEXT_DOMAIN) . '</div>';
        echo '<a id="transposh-translate" href="' . $this->transposh->post_url . '?translate_all&offset=1" onclick="return false;" class="button">' . __('Translate All Now', TRANSPOSH_TEXT_DOMAIN) . '</a><br/>';
        //get_posts
    }

    /**
     * Insert supported languages section in admin page
     * @param string $data
     */
    function on_contentbox_languages_content($data) {
        // we need some styles
        echo '<style type="text/css">
	#sortable { list-style-type: none; margin: 0; padding: 0; }
	#sortable li, #default_lang li { margin: 3px 3px 3px 0; padding: 5px; float: ' . $this->localeleft . '; width: 190px; height: 14px;}
	.languages {
            -moz-border-radius: 6px;
            -khtml-border-radius: 6px;
            -webkit-border-radius: 6px;
            border-radius: 6px;
            border-style:solid;
            border-width:1px;
            line-height:1;
         }
	.highlight {
            -moz-border-radius: 6px;
            -khtml-border-radius: 6px;
            -webkit-border-radius: 6px;
            border-radius: 6px;
            border-style:solid;
            border-width:1px;
            line-height:1;
            background: #FFE45C;
            width: 190px;
            height: 14px;
        }
	.highlight_default {
            background: #FFE45C;
        }
        .active {
            background: #45FF51;
        }
        .translateable {
            background: #FFFF51;
        }
        .hidden {
        display: none;
        }
        .logoicon {
            float:' . $this->localeright . ';
            margin-left:2px;
            margin-top:-1px;
        }
	</style>';

        // this is the default language location
        list ($langname, $langorigname, $flag) = explode(",", transposh_consts::$languages[$this->transposh->options->get_default_language()]);
        echo '<div id="default_lang" style="overflow:auto;padding-bottom:10px;">' . __('Default Language (drag another language here to make it default)', TRANSPOSH_TEXT_DOMAIN);
        echo '<ul id="default_list"><li id="' . $this->transposh->options->get_default_language() . '" class="languages">'
        . transposh_utils::display_flag("{$this->transposh->transposh_plugin_url}/img/flags", $flag, $langorigname, false/* $this->transposh->options->get_widget_css_flags() */)
        . '<input type="hidden" name="languages[]" value="' . $this->transposh->options->get_default_language() . '" />'
        . '&nbsp;<span class="langname">' . $langorigname . '</span><span class="langname hidden">' . $langname . '</span></li>';
        echo '</ul></div>';
        // list of languages
        echo '<div style="overflow:auto; clear: both;">' . __('Available Languages (Click to toggle language state - Drag to sort in the widget)', TRANSPOSH_TEXT_DOMAIN);
        echo '<ul id="sortable">';
        foreach ($this->transposh->options->get_sorted_langs() as $langcode => $langrecord) {
            list ($langname, $langorigname, $flag) = explode(",", $langrecord);
            echo '<li id="' . $langcode . '" class="languages ' . ($this->transposh->options->is_viewable_language($langcode) || $this->transposh->options->is_default_language($langcode) ? "active" : "")
            . (!$this->transposh->options->is_viewable_language($langcode) && $this->transposh->options->is_editable_language($langcode) ? "translateable" : "") . '"><div style="float:' . $this->localeleft . '">'
            . transposh_utils::display_flag("{$this->transposh->transposh_plugin_url}/img/flags", $flag, false /* $langorigname,$this->transposh->options->get_widget_css_flags() */)
            . '<input type="hidden" name="languages[]" value="' . $langcode . ($this->transposh->options->is_viewable_language($langcode) ? ",v" : ",") . ($this->transposh->options->is_viewable_language($langcode) ? ",t" : ",") . '" />'
            . '&nbsp;<span class="langname">' . $langorigname . '</span><span class="langname hidden">' . $langname . '</span></div>';
            if (in_array($langcode, transposh_consts::$google_languages))
                    echo '<img width="16" height="16" alt="g" class="logoicon" title="' . esc_attr__('Language supported by google translate', TRANSPOSH_TEXT_DOMAIN) . '" src="' . $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_IMG . '/googleicon.png"/>';
            if (in_array($langcode, transposh_consts::$bing_languages))
                    echo '<img width="16" height="16" alt="b" class="logoicon" title="' . esc_attr__('Language supported by bing translate', TRANSPOSH_TEXT_DOMAIN) . '" src="' . $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_IMG . '/bingicon.png"/>';
            if (in_array($langcode, transposh_consts::$apertium_languages))
                    echo '<img width="16" height="16" alt="a" class="logoicon" title="' . esc_attr__('Language supported by apertium translate', TRANSPOSH_TEXT_DOMAIN) . '" src="' . $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_IMG . '/apertiumicon.png"/>';
            if (in_array($langcode, transposh_consts::$rtl_languages))
                    echo '<img width="16" height="16" alt="r" class="logoicon" title="' . esc_attr__('Language is written from right to left', TRANSPOSH_TEXT_DOMAIN) . '" src="' . $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_IMG . '/rtlicon.png"/>';
            echo '</li>';
        }
        echo "</ul></div>";
        // options to play with
        echo '<div style="clear: both;">' . __('Display options:', TRANSPOSH_TEXT_DOMAIN) . '<br/><ul style="list-style-type: disc; margin-' . $this->localeleft . ':20px;font-size:11px">';
        echo '<li><a href="#" id="changename">' . __('Toggle names of languages between English and Original', TRANSPOSH_TEXT_DOMAIN) . '</a></li>';
        echo '<li><a href="#" id="selectall">' . __('Make all languages active', TRANSPOSH_TEXT_DOMAIN) . '</a></li>';
        echo '<li><a href="#" id="sortname">' . __('Sort by language name', TRANSPOSH_TEXT_DOMAIN) . '</a></li>';
        echo '<li><a href="#" id="sortiso">' . __('Sort by lSO code', TRANSPOSH_TEXT_DOMAIN) . '</a></li></ul>';
        echo __('Legend:', TRANSPOSH_TEXT_DOMAIN) . ' ' . __('Green - active', TRANSPOSH_TEXT_DOMAIN) . ', <span id="yellowcolor"' . ($this->transposh->options->get_anonymous_translation() ? ' class ="hidden"' : '') . '>' . __('Yellow - translateable (only translators will see this language)', TRANSPOSH_TEXT_DOMAIN) . ', </span>' . __('blank - inactive', TRANSPOSH_TEXT_DOMAIN);
        echo '</div>';
    }

    /**
     * uses a boolean expression to make checkboxes check
     * @param boolean $eval
     * @return string used for checkboxes
     */
    private function checked($eval) {
        return $eval ? 'checked="checked"' : '';
    }

    function on_contentbox_translation_content($data) {
        /*
         * Insert permissions section in the admin page
         */
        echo '<h4>' . __('Who can translate ?', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
        //display known roles and their permission to translate
        foreach ($GLOBALS['wp_roles']->get_names() as $role_name => $something) {
            echo '<input type="checkbox" value="1" name="' . $role_name . '" ' . $this->can_translate($role_name) .
            '/> ' . _x(ucfirst($role_name), 'User role') . '&nbsp;&nbsp;&nbsp;';
        }
        //Add our own custom role
        echo '<input id="tr_anon" type="checkbox" value="1" name="anonymous" ' . $this->can_translate('anonymous') . '/> ' . __('Anonymous', TRANSPOSH_TEXT_DOMAIN);

        /*
         * Insert the option to enable/disable default language translation.
         * Disabled by default.
         */
        echo '<h4>' . __('Enable default language translation', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
        echo '<input type="checkbox" value="1" name="' . ENABLE_DEFAULT_TRANSLATE . '" ' . $this->checked($this->transposh->options->get_enable_default_translate()) . '/> ' .
        __('Allow translation of default language - useful for sites with more than one major language', TRANSPOSH_TEXT_DOMAIN);

        /**
         * Insert the option to enable search in translated languages
         * Enabled by default.
         * @since 0.3.6
         */
        echo '<h4>' . __('Enable search in translated languages', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
        echo '<input type="checkbox" value="1" name="' . ENABLE_SEARCH_TRANSLATE . '" ' . $this->checked($this->transposh->options->get_enable_search_translate()) . '/> ' .
        __('Allow search of translated languages (and the original language)', TRANSPOSH_TEXT_DOMAIN);

        /**
         * Insert the option to enable translation of urls
         * Disbaled by default.
         * @since 0.5.3
         */
        echo '<h4>' . __('Enable url translation', TRANSPOSH_TEXT_DOMAIN) . ' (' . __('experimental', TRANSPOSH_TEXT_DOMAIN) . ')</h4>';
        echo '<input type="checkbox" value="1" name="' . ENABLE_URL_TRANSLATE . '" ' . $this->checked($this->transposh->options->get_enable_url_translate()) . '/> ' .
        __('Allow translation of permalinks and urls', TRANSPOSH_TEXT_DOMAIN);

        /**
         * Insert the option to enable gettext integration
         * Enabled by default.
         * @since 0.6.4
         */
        echo '<h4>' . __('Enable gettext integration', TRANSPOSH_TEXT_DOMAIN) . ' (' . __('experimental', TRANSPOSH_TEXT_DOMAIN) . ')</h4>';
        echo '<input type="checkbox" value="1" name="' . TRANSPOSH_GETTEXT_INTEGRATION . '" ' . $this->checked($this->transposh->options->get_transposh_gettext_integration()) . '/> ' .
        __('Enable integration of Transposh with existing gettext interface (.po/.mo files)', TRANSPOSH_TEXT_DOMAIN);

        /**
         * Insert the option to enable default locale override
         * Enabled by default.
         * @since 0.7.5
         */
        echo '<h4>' . __('Enable override for default locale', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
        echo '<input type="checkbox" value="1" name="' . TRANSPOSH_DEFAULT_LOCALE_OVERRIDE . '" ' . $this->checked($this->transposh->options->get_transposh_default_locale_override()) . '/> ' .
        __('Enable overriding the default locale that is set in WP_LANG on default languages pages (such as untranslated pages and admin pages)', TRANSPOSH_TEXT_DOMAIN);
    }

    function on_contentbox_auto_translation_content($data) {

        /*
         * Insert the option to enable/disable automatic translation.
         * Enabled by default.
         */
        echo '<h4>' . __('Enable automatic translation', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
        echo '<input type="checkbox" value="1" name="' . ENABLE_AUTO_TRANSLATE . '" ' . $this->checked($this->transposh->options->get_enable_auto_translate()) . '/> ' .
        __('Allow automatic translation of pages', TRANSPOSH_TEXT_DOMAIN);

        /**
         * Insert the option to enable/disable automatic translation upon publishing.
         * Disabled by default.
         *  @since 0.3.5 */
        echo '<h4>' . __('Enable automatic translation after posting', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
        echo '<input type="checkbox" value="1" name="' . ENABLE_AUTO_POST_TRANSLATE . '" ' . $this->checked($this->transposh->options->get_enable_auto_post_translate()) . '/> ' .
        __('Do automatic translation immediately after a post has been published', TRANSPOSH_TEXT_DOMAIN);

        /**
         * Allow users to insert their own API keys
         */
        echo '<h4>' . __('MSN API key', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
        echo __('API Key', TRANSPOSH_TEXT_DOMAIN).': <input type="text" size="35" class="regular-text" value="' . $this->transposh->options->get_msn_key() . '" id="'.MSN_TRANSLATE_KEY.'" name="'.MSN_TRANSLATE_KEY.'"/>';

          /**
         * Allow users to insert their own API keys
         */
        echo '<h4>' . __('Google API key', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
        echo __('API Key', TRANSPOSH_TEXT_DOMAIN).': <input type="text" size="35" class="regular-text" value="' . $this->transposh->options->get_google_key() . '" id="'.GOOGLE_TRANSLATE_KEY.'" name="'.GOOGLE_TRANSLATE_KEY.'"/>';

        /*
         * Choose default translator... TODO (explain better in wiki)
         */
        echo '<h4>' . __('Select preferred auto translation engine', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
        echo '<label for="' . PREFERRED_TRANSLATOR . '">' . __('Translation engine:', TRANSPOSH_TEXT_DOMAIN) .
        '<select name="' . PREFERRED_TRANSLATOR . '">' .
        '<option value="1"' . ($this->transposh->options->get_preferred_translator() == 1 ? ' selected="selected"' : '') . '>' . __('Google', TRANSPOSH_TEXT_DOMAIN) . '</option>' .
        '<option value="2"' . ($this->transposh->options->get_preferred_translator() == 2 ? ' selected="selected"' : '') . '>' . __('Bing', TRANSPOSH_TEXT_DOMAIN) . '</option>' .
        '</select>' .
        '</label>';
    }

    function on_contentbox_generic_content($data) {
        /*
         * Insert the option to enable/disable rewrite of perlmalinks.
         * When disabled only parameters will be used to identify the current language.
         */
        echo '<h4>' . __('Rewrite URLs', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
        echo '<input type="checkbox" value="1" name="' . ENABLE_PERMALINKS . '" ' . $this->checked($this->transposh->options->get_enable_permalinks()) . '/> ' .
        __('Rewrite URLs to be search engine friendly, ' .
                'e.g.  (http://transposh.org/<strong>en</strong>). ' .
                'Requires that permalinks will be enabled.', TRANSPOSH_TEXT_DOMAIN);

        /*
         * Insert the option to enable/disable pushing of scripts to footer.
         * Works on wordpress 2.8 and up
         */
        if (floatval($GLOBALS['wp_version']) >= 2.8) {
            echo '<h4>' . __('Add scripts to footer', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
            echo '<input type="checkbox" value="1" name="' . ENABLE_FOOTER_SCRIPTS . '" ' . $this->checked($this->transposh->options->get_enable_footer_scripts()) . '/> ' .
            __('Push transposh scripts to footer of page instead of header, makes pages load faster. ' .
                    'Requires that your theme should have proper footer support.', TRANSPOSH_TEXT_DOMAIN);
        }

        /**
         * Insert the option to enable/disable language auto-detection
         * @since 0.3.8 */
        echo '<h4>' . __('Auto detect language for users', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
        echo '<input type="checkbox" value="1" name="' . ENABLE_DETECT_LANG_AND_REDIRECT . '" ' . $this->checked($this->transposh->options->get_enable_detect_language()) . '/> ' .
        __('This enables auto detection of language used by the user as defined in the ACCEPT_LANGUAGES they send. ' .
                'This will redirect the first page accessed in the session to the same page with the detected language.', TRANSPOSH_TEXT_DOMAIN);

        /**
         * Insert the option to enable/disable statics collection
         * @since 0.3.8 */
        echo '<h4>' . __('Allow collecting usage statistics', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
        echo '<input type="checkbox" value="1" name="' . TRANSPOSH_COLLECT_STATS . '" ' . $this->checked($this->transposh->options->get_transposh_collect_stats()) . '/> ' .
        __('This option enables collection of statistics by transposh that will be used to improve the product. ', TRANSPOSH_TEXT_DOMAIN);

        /* WIP2
          echo '<a href="http://transposh.org/services/index.php?flags='.$flags.'">Gen sprites</a>'; */
    }

    function on_contentbox_database_content($data) {
        /*
         * Insert buttons allowing removal of automated translations from database and maintenence
         */
        echo '<div style="margin:10px 0"><a id="transposh-clean-auto" href="#" nonce="' . wp_create_nonce('transposh-clean') . '" class="button">' . __('Delete all automated translations', TRANSPOSH_TEXT_DOMAIN) . '</a></div>';
        echo '<div style="margin:10px 0"><a id="transposh-clean-auto14" href="#" nonce="' . wp_create_nonce('transposh-clean') . '" class="button">' . __('Delete automated translations older than 14 days', TRANSPOSH_TEXT_DOMAIN) . '</a></div>';
        echo '<div style="margin:10px 0"><a id="transposh-maint" href="#" nonce="' . wp_create_nonce('transposh-clean') . '" class="button">' . __('Attempt to fix errors caused by previous versions - please backup first', TRANSPOSH_TEXT_DOMAIN) . '</a></div>';
    }

    function on_contentbox_community_content($data) {
        echo '<h4>' . __('Backup service for human translation', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
        echo '<input type="radio" value="1" name="' . TRANSPOSH_BACKUP_SCHEDULE . '" ' . $this->checked($this->transposh->options->get_transposh_backup_schedule() == 1) . '/>' . __('Enable daily backup', TRANSPOSH_TEXT_DOMAIN) . '<br/>';
        echo '<input type="radio" value="2" name="' . TRANSPOSH_BACKUP_SCHEDULE . '" ' . $this->checked($this->transposh->options->get_transposh_backup_schedule() == 2) . '/>' . __('Enable live backup', TRANSPOSH_TEXT_DOMAIN) . '<br/>';
        echo '<input type="radio" value="0" name="' . TRANSPOSH_BACKUP_SCHEDULE . '" ' . $this->checked($this->transposh->options->get_transposh_backup_schedule() == 0) . '/>' . __('Disable backup (Can be run manually by clicking the button below)', TRANSPOSH_TEXT_DOMAIN) . '<br/>';
        echo __('Service Key:', TRANSPOSH_TEXT_DOMAIN) . ' <input type="text" size="32" class="regular-text" value="' . $this->transposh->options->get_transposh_key() . '" id="' . TRANSPOSH_KEY . '" name="' . TRANSPOSH_KEY . '"/> <a target="_blank" href="http://transposh.org/faq/#restore">' . __('How to restore?', TRANSPOSH_TEXT_DOMAIN) . '</a><br/>';
        echo '<div id="backup_result"></div>';
        echo '<div style="margin:10px 0"><a id="transposh-backup" href="#" class="button">' . __('Do Backup Now', TRANSPOSH_TEXT_DOMAIN) . '</a></div>';
    }

    function add_warning($id, $message) {
        if (!$this->transposh->options->get_transposh_admin_hide_warning($id)) {
            echo '<div id="' . $id . '" class="error">' .
            '<span class="ui-icon ui-icon-alert" style="float: ' . $this->localeleft . '; margin-' . $this->localeright . ': .3em;"></span>' .
            $message .
            '<span class="warning-close ui-icon ui-icon-closethick" style="float:' . $this->localeright . '; margin-' . $this->localeleft . ': .3em;"></span>' .
            '</div>';
        }
    }

    function on_closed_tpwarn() {
        $this->transposh->options->set_transposh_admin_hide_warning($_POST['id']);
        $this->transposh->options->update_options();
        die(); // this is required to return a proper result
    }

}

?>