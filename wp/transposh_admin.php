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
            add_filter('rewrite_rules_array', 'update_rewrite_rules');
            $GLOBALS['wp_rewrite']->flush_rules();
        }

        $this->transposh->options->set_enable_footer_scripts($_POST[ENABLE_FOOTER_SCRIPTS]);
        $this->transposh->options->set_enable_detect_language($_POST[ENABLE_DETECT_LANG_AND_REDIRECT]);
        $this->transposh->options->set_enable_auto_translate($_POST[ENABLE_AUTO_TRANSLATE]);
        $this->transposh->options->set_enable_auto_post_translate($_POST[ENABLE_AUTO_POST_TRANSLATE]);
        $this->transposh->options->set_enable_default_translate($_POST[ENABLE_DEFAULT_TRANSLATE]);
        $this->transposh->options->set_enable_search_translate($_POST[ENABLE_SEARCH_TRANSLATE]);
        $this->transposh->options->set_enable_url_translate($_POST[ENABLE_URL_TRANSLATE]);
        $this->transposh->options->set_preferred_translator($_POST[PREFERRED_TRANSLATOR]);
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
            $filterVal['settings_page_transposh'] = '<p>Transposh makes your blog translatable</p>' .
                    '<a href="http://transposh.org/">Plugin homepage</a><br/>' .
                    '<a href="http://transposh.org/faq/">Frequently asked questions</a>';
        }
        return $filterVal;
    }

    // extend the admin menu
    function on_admin_menu() {
        //add our own option page, you can also add it to different sections or use your own one
        $this->pagehook = add_options_page('Transposh control center', 'Transposh', 'manage_options', TRANSPOSH_ADMIN_PAGE_NAME, array(&$this, 'on_show_page'));
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
        add_meta_box('transposh-sidebox-about', 'About this plugin', array(&$this, 'on_sidebox_about_content'), $this->pagehook, 'side', 'core');
        add_meta_box('transposh-sidebox-widget', 'Widget settings', array(&$this, 'on_sidebox_widget_content'), $this->pagehook, 'side', 'core');
        add_meta_box('transposh-sidebox-news', 'Plugin news', array(&$this, 'on_sidebox_news_content'), $this->pagehook, 'side', 'core');
        add_meta_box('transposh-sidebox-stats', 'Plugin stats', array(&$this, 'on_sidebox_stats_content'), $this->pagehook, 'side', 'core');
        add_meta_box('transposh-sidebox-translate', 'Translate all', array(&$this, 'on_sidebox_translate_content'), $this->pagehook, 'side', 'core');
        add_meta_box('transposh-contentbox-languages', 'Supported languages', array(&$this, 'on_contentbox_languages_content'), $this->pagehook, 'normal', 'core');
        add_meta_box('transposh-contentbox-translation', 'Translation settings', array(&$this, 'on_contentbox_translation_content'), $this->pagehook, 'normal', 'core');
        add_meta_box('transposh-contentbox-autotranslation', 'Automatic translation settings', array(&$this, 'on_contentbox_auto_translation_content'), $this->pagehook, 'normal', 'core');
        add_meta_box('transposh-contentbox-general', 'Generic settings', array(&$this, 'on_contentbox_generic_content'), $this->pagehook, 'normal', 'core');
        add_meta_box('transposh-contentbox-database', 'Database maintenance', array(&$this, 'on_contentbox_database_content'), $this->pagehook, 'normal', 'core');
    }

    //executed to show the plugins complete admin page
    function on_show_page() {
        //we need the global screen column value to beable to have a sidebar in WordPress 2.8
        //global $screen_layout_columns;
        //add a 3rd content box now for demonstration purpose, boxes added at start of page rendering can't be switched on/off,
        //may be needed to ensure that a special box is always available
        add_meta_box('transposh-contentbox-community', 'Transposh community features', array(&$this, 'on_contentbox_community_content'), $this->pagehook, 'normal', 'core');
        //define some data can be given to each metabox during rendering - not used now
        //$data = array('My Data 1', 'My Data 2', 'Available Data 1');

        echo '<div id="transposh-general" class="wrap">';
        screen_icon('options-general');

        echo '<h2>Transposh</h2>' .
        '<form action="admin-post.php" method="post">';

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
        '<input type="submit" value="Save Changes" class="button-primary" name="Submit"/>' .
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
        if (!current_user_can('manage_options')) wp_die(__('Problems?'));
        // cross check the given referer
        check_admin_referer(TR_NONCE);

        // process here your on $_POST validation and / or option saving
        $this->transposh->widget->transposh_widget_post(FALSE);
        $this->update_admin_options();

        // lets redirect the post request into get request (you may add additional params at the url, if you need to show save results
        wp_redirect($_POST['_wp_http_referer']);
    }

    // below you will find for each registered metabox the callback method, that produces the content inside the boxes
    // i did not describe each callback dedicated, what they do can be easily inspected and compare with the admin page displayed

    function on_sidebox_about_content($data) {
        echo '<ul style="list-style-type:disc;margin-left:20px;">';
        echo '<li><a href="http://transposh.org/">Plugin Homepage</a></li>';
        echo '<li><a href="http://transposh.org/redir/newfeature">Suggest a Feature</a></li>';
        // support Forum
        echo '<li><a href="http://transposh.org/redir/newticket">Report a Bug</a></li>';
        // donate with PayPal
        echo '</ul>';
    }

    function on_sidebox_widget_content($data) {
        $this->transposh->widget->transposh_widget_control();
    }

    function on_sidebox_news_content($data) {
        echo '<div style="margin:6px">';
        wp_widget_rss_output('http://feeds2.feedburner.com/transposh',array('items' => 5));
        echo '</div>';
    }

    function on_sidebox_stats_content($data) {
        $this->transposh->database->db_stats();
    }

    function on_sidebox_translate_content($data) {
        echo '<div id="progress_bar_all"></div><div id="tr_translate_title"></div>';
        echo '<div id="tr_loading" style="margin: 0 0 10px 0">Translate by clicking the button below</div>';
        echo '<a id="transposh-translate" href="' . $this->transposh->post_url . '?translate_all&offset=1" onclick="return false;" class="button">Translate All Now</a><br/>';
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
	#sortable li, #default_lang li { margin: 3px 3px 3px 0; padding: 5px; float: left; width: 190px; height: 14px;}
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
            float: right;
            margin-left:2px;
            margin-top:-1px;
        }
	</style>';

        // this is the default language location
        list ($langname, $langorigname, $flag) = explode(",", transposh_consts::$languages[$this->transposh->options->get_default_language()]);
        echo '<div id="default_lang" style="overflow:auto;padding-bottom:10px;">Default Language (drag another language here to make it default)';
        echo '<ul id="default_list"><li id="' . $this->transposh->options->get_default_language() . '" class="languages">'
        . transposh_utils::display_flag("{$this->transposh->transposh_plugin_url}/img/flags", $flag, $langorigname, false/* $this->transposh->options->get_widget_css_flags() */)
        . '<input type="hidden" name="languages[]" value="' . $this->transposh->options->get_default_language() . '" />'
        . '&nbsp;<span class="langname">' . $langorigname . '</span><span class="langname hidden">' . $langname . '</span></li>';
        echo '</ul></div>';
        // list of languages
        echo '<div style="overflow:auto; clear: both;">Available Languages (Click to toggle language state - Drag to sort in the widget)';
        echo '<ul id="sortable">';
        foreach ($this->transposh->options->get_sorted_langs() as $langcode => $langrecord) {
            list ($langname, $langorigname, $flag) = explode(",", $langrecord);
            echo '<li id="' . $langcode . '" class="languages ' . ($this->transposh->options->is_viewable_language($langcode) || $this->transposh->options->is_default_language($langcode) ? "active" : "")
            . (!$this->transposh->options->is_viewable_language($langcode) && $this->transposh->options->is_editable_language($langcode) ? "translateable" : "") . '"><div style="float:left">'
            . transposh_utils::display_flag("{$this->transposh->transposh_plugin_url}/img/flags", $flag, false /* $langorigname,$this->transposh->options->get_widget_css_flags() */)
            . '<input type="hidden" name="languages[]" value="' . $langcode . ($this->transposh->options->is_viewable_language($langcode) ? ",v" : ",") . ($this->transposh->options->is_viewable_language($langcode) ? ",t" : ",") . '" />'
            . '&nbsp;<span class="langname">' . $langorigname . '</span><span class="langname hidden">' . $langname . '</span></div>';
            if (in_array($langcode, transposh_consts::$google_languages))
                    echo '<img width="16" height="16" alt="g" class="logoicon" title="Language supported by google translate" src="' . $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_IMG . '/googleicon.png"/>';
            if (in_array($langcode, transposh_consts::$bing_languages))
                    echo '<img width="16" height="16" alt="b" class="logoicon" title="Language supported by bing translate" src="' . $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_IMG . '/bingicon.png"/>';
            if (in_array($langcode, transposh_consts::$rtl_languages))
                    echo '<img width="16" height="16" alt="r" class="logoicon" title="Language is written from right to left" src="' . $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_IMG . '/rtlicon.png"/>';
            echo '</li>';
        }
        echo "</ul></div>";
        // options to play with
        echo '<div style="clear: both;">Display options:<br/><ul style="list-style-type: disc; margin-left: 20px;font-size:11px">';
        echo '<li><a href="#" id="changename">Toggle names of languages between English and Original</a></li>';
        echo '<li><a href="#" id="selectall">Make all languages active</a></li>';
        echo '<li><a href="#" id="sortname">Sort by language name</a></li>';
        echo '<li><a href="#" id="sortiso">Sort by lSO code</a></li></ul>';
        echo 'Legend: Green - active, <span id="yellowcolor"' . ($this->transposh->options->get_anonymous_translation() ? ' class ="hidden"' : '') . '>Yellow - translateable (only translators will see this language), </span>blank - inactive';
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
        echo '<h4>Who can translate ?</h4>';
        //display known roles and their permission to translate
        foreach ($GLOBALS['wp_roles']->get_names() as $role_name => $something) {
            echo '<input type="checkbox" value="1" name="' . $role_name . '" ' . $this->can_translate($role_name) .
            '/> ' . ucfirst($role_name) . '&nbsp;&nbsp;&nbsp;';
        }
        //Add our own custom role
        echo '<input id="tr_anon" type="checkbox" value="1" name="anonymous" ' . $this->can_translate('anonymous') . '/> Anonymous';

        /*
         * Insert the option to enable/disable default language translation.
         * Disabled by default.
         */
        echo '<h4>Enable default language translation</h4>';
        echo '<input type="checkbox" value="1" name="' . ENABLE_DEFAULT_TRANSLATE . '" ' . $this->checked($this->transposh->options->get_enable_default_translate()) . '/> ' .
        'Allow translation of default language - useful for sites with more than one major language';

        /**
         * Insert the option to enable search in translated languages
         * Enabled by default.
         * @since 0.3.6
         */
        echo '<h4>Enable search in translated languages</h4>';
        echo '<input type="checkbox" value="1" name="' . ENABLE_SEARCH_TRANSLATE . '" ' . $this->checked($this->transposh->options->get_enable_search_translate()) . '/> ' .
        'Allow search of translated languages, in those languages (and the original language)';

        /**
         * Insert the option to enable translation of urls
         * Disbaled by default.
         * @since 0.5.3
         */
        echo '<h4>Enable url translation (experimental)</h4>';
        echo '<input type="checkbox" value="1" name="' . ENABLE_URL_TRANSLATE . '" ' . $this->checked($this->transposh->options->get_enable_url_translate()) . '/> ' .
        'Allow translation of permalinks and urls';
    }

    function on_contentbox_auto_translation_content($data) {

        /*
         * Insert the option to enable/disable automatic translation.
         * Enabled by default.
         */
        echo '<h4>Enable automatic translation</h4>';
        echo '<input type="checkbox" value="1" name="' . ENABLE_AUTO_TRANSLATE . '" ' . $this->checked($this->transposh->options->get_enable_auto_translate()) . '/> ' .
        'Allow automatic translation of pages';

        /**
         * Insert the option to enable/disable automatic translation upon publishing.
         * Disabled by default.
         *  @since 0.3.5 */
        echo '<h4>New - Enable automatic translation after posting</h4>';
        echo '<input type="checkbox" value="1" name="' . ENABLE_AUTO_POST_TRANSLATE . '" ' . $this->checked($this->transposh->options->get_enable_auto_post_translate()) . '/> ' .
        'Do automatic translation immediately after a post has been published';

        /*
         * Choose default translator... TODO (explain better in wiki)
         */
        echo '<h4>Select preferred auto translation engine</h4>';
        echo '<label for="' . PREFERRED_TRANSLATOR . '">Translation engine:' .
        '<select name="' . PREFERRED_TRANSLATOR . '">' .
        '<option value="1"' . ($this->transposh->options->get_preferred_translator() == 1 ? ' selected="selected"' : '') . '>Google</option>' .
        '<option value="2"' . ($this->transposh->options->get_preferred_translator() == 2 ? ' selected="selected"' : '') . '>Bing</option>' .
        '</select>' .
        '</label>';
    }

    function on_contentbox_generic_content($data) {
        /*
         * Insert the option to enable/disable rewrite of perlmalinks.
         * When disabled only parameters will be used to identify the current language.
         */
        echo '<h4>Rewrite URLs</h4>';
        echo '<input type="checkbox" value="1" name="' . ENABLE_PERMALINKS . '" ' . $this->checked($this->transposh->options->get_enable_permalinks()) . '/> ' .
        'Rewrite URLs to be search engine friendly, ' .
        'e.g.  (http://wordpress.org/<strong>en</strong>). ' .
        'Requires that permalinks will be enabled.';

        /*
         * Insert the option to enable/disable pushing of scripts to footer.
         * Works on wordpress 2.8 and up
         */
        if (floatval($GLOBALS['wp_version']) >= 2.8) {
            echo '<h4>Add scripts to footer</h4>';
            echo '<input type="checkbox" value="1" name="' . ENABLE_FOOTER_SCRIPTS . '" ' . $this->checked($this->transposh->options->get_enable_footer_scripts()) . '/> ' .
            'Push transposh scripts to footer of page instead of header, makes pages load faster. ' .
            'Requires that your theme should have proper footer support.';
        }

        /**
         * Insert the option to enable/disable language auto-detection
         * @since 0.3.8 */
        echo '<h4>Auto detect language for users</h4>';
        echo '<input type="checkbox" value="1" name="' . ENABLE_DETECT_LANG_AND_REDIRECT . '" ' . $this->checked($this->transposh->options->get_enable_detect_language()) . '/> ' .
        'This enables auto detection of language used by the user as defined in the ACCEPT_LANGUAGES they send. ' .
        'This will redirect the first page accessed in the session to the same page with the detected language.';

        /* WIP2
          echo '<a href="http://transposh.org/services/index.php?flags='.$flags.'">Gen sprites</a>'; */
    }

    function on_contentbox_database_content($data) {
        /*
         * Insert two buttons allowing removal of automated translations from database
         */
        echo '<div style="margin:10px 0"><a id="transposh-clean-auto" href="#" nonce="'.  wp_create_nonce('transposh-clean').'" class="button">Clean all automated translations</a></div>';
        echo '<div style="margin:10px 0"><a id="transposh-clean-auto14" href="#" nonce="'.  wp_create_nonce('transposh-clean').'" class="button">Clean automated translations older than 14 days</a></div>';
    }

    function on_contentbox_community_content($data) {
        echo '<h4>Backup service for human translation</h4>';
        echo '<input type="radio" value="1" name="' . TRANSPOSH_BACKUP_SCHEDULE . '" ' . $this->checked($this->transposh->options->get_transposh_backup_schedule() == 1) . '/>Enable daily backup<br/>';
        echo '<input type="radio" value="2" name="' . TRANSPOSH_BACKUP_SCHEDULE . '" ' . $this->checked($this->transposh->options->get_transposh_backup_schedule() == 2) . '/>Enable live backup<br/>';
        echo '<input type="radio" value="0" name="' . TRANSPOSH_BACKUP_SCHEDULE . '" ' . $this->checked($this->transposh->options->get_transposh_backup_schedule() == 0) . '/>Disable backup (Can be run manually by clicking the button below)<br/>';
        echo 'Service Key: <input type="text" size="32" class="regular-text" value="' . $this->transposh->options->get_transposh_key() . '" id="' . TRANSPOSH_KEY . '" name="' . TRANSPOSH_KEY . '"/> <a target="_blank" href="http://transposh.org/faq/#restore">How to restore?</a><br/>';
        echo '<div id="backup_result"></div>';
        echo '<div style="margin:10px 0"><a id="transposh-backup" href="#" class="button">Do Backup Now</a></div>';
    }

}
?>