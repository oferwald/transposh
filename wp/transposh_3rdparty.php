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
 * This file handles functions relevant to specific third party plugins
 */

class transposh_3rdparty {

    /** @var transposh_plugin Container class */
    private $transposh;

    /**
     * Construct our class
     * @param transposh_plugin $transposh
     */
    function transposh_3rdparty(&$transposh) {
        $this->transposh = &$transposh;

        // supercache invalidation of pages - first lets find if supercache is here
        if (function_exists('wp_super_cache_init')) {
            add_action('transposh_translation_posted', array(&$this, 'super_cache_invalidate'));
        }

        // buddypress compatability
        add_filter('bp_uri', array(&$this, 'bp_uri_filter'));
        add_filter('bbp_get_search_results_url', array(&$this, 'bbp_get_search_results_url'));
        add_filter('bp_get_activity_content_body', array(&$this, 'bp_get_activity_content_body'), 10, 2);
        add_action('bp_activity_after_save', array(&$this, 'bp_activity_after_save'));
        add_action('transposh_human_translation', array(&$this, 'transposh_buddypress_stream'), 10, 3);
        //bp_activity_permalink_redirect_url (can fit here if generic setting fails)
        // google xml sitemaps - with patch
        add_action('sm_addurl', array(&$this, 'add_sm_transposh_urls'));
        // yoast - need patch
        add_filter('wpseo_sitemap_language', array(&$this, 'add_yoast_transposh_urls'));

        // business directory plugin
        add_filter('wpbdp_get_page_link', array(&$this, 'fix_wpbdp_links_base'));
        add_filter('wpbdp_listing_link', array(&$this, 'fix_wpbdp_links'));
        add_filter('wpbdp_category_link', array(&$this, 'fix_wpbdp_links'));


        // google analyticator
        if ($this->transposh->options->transposh_collect_stats) {
            add_action('google_analyticator_extra_js_after', array(&$this, 'add_analyticator_tracking'));
        }

        // woocommerce
        add_filter('woocommerce_get_checkout_url', array(&$this, 'woo_uri_filter'));
        add_filter('woocommerce_get_cart_url', array(&$this, 'woo_uri_filter'));
    }

    function add_analyticator_tracking() {
        echo "	_gaq.push(['_setAccount', 'UA-4663695-5']);\n";
        echo "	_gaq.push(['_setDomainName', 'none']);\n";
        echo "	_gaq.push(['_setAllowLinker', true]);\n";
        echo "	_gaq.push(['_trackPageview']);\n";
    }

    function super_cache_invalidate() {
        //Now, we are actually using the referrer and not the request, with some precautions
        $GLOBALS['wp_cache_request_uri'] = substr($_SERVER['HTTP_REFERER'], stripos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) + strlen($_SERVER[''] . $_SERVER['HTTP_HOST']));
        $GLOBALS['wp_cache_request_uri'] = preg_replace('/[ <>\'\"\r\n\t\(\)]/', '', str_replace('/index.php', '/', str_replace('..', '', preg_replace("/(\?.*)?$/", '', $GLOBALS['wp_cache_request_uri']))));
        // get some supercache variables
        extract(wp_super_cache_init());
        tp_logger(wp_super_cache_init());
        // this is hackery for logged in users, a cookie is added to the request somehow and gzip is not correctly set, so we forcefully fix this
        if (!$cache_file) {
            $GLOBALS['wp_cache_gzip_encoding'] = gzip_accepted();
            unset($_COOKIE[key($_COOKIE)]);
            extract(wp_super_cache_init());
            tp_logger(wp_super_cache_init());
        }

        $dir = get_current_url_supercache_dir();
        // delete possible files that we can figure out, not deleting files for other cookies for example, but will do the trick in most cases
        $cache_fname = "{$dir}index.html";
        tp_logger("attempting delete of supercache: $cache_fname");
        @unlink($cache_fname);
        $cache_fname = "{$dir}index.html.gz";
        tp_logger("attempting delete of supercache: $cache_fname");
        @unlink($cache_fname);
        tp_logger("attempting delete of wp_cache: $cache_file");
        @unlink($cache_file);
        tp_logger("attempting delete of wp_cache_meta: $meta_pathname");
        @unlink($meta_pathname);

        // go at edit pages too
        $GLOBALS['wp_cache_request_uri'] .="?edit=1";
        extract(wp_super_cache_init());
        tp_logger(wp_super_cache_init());
        tp_logger("attempting delete of edit_wp_cache: $cache_file");
        @unlink($cache_file);
        tp_logger("attempting delete of edit_wp_cache_meta: $meta_pathname");
        @unlink($meta_pathname);
    }

    /**
     * This filter method helps buddypress understand the transposh generated URLs
     * @param string $uri The url that was originally received
     * @return string The url that buddypress should see
     */
    function bp_uri_filter($uri) {
        $lang = transposh_utils::get_language_from_url($uri, $this->transposh->home_url);
        //TODO - check using get_clean_url
        $uri = transposh_utils::cleanup_url($uri, $this->transposh->home_url);
        if ($this->transposh->options->enable_url_translate) {
            $uri = transposh_utils::get_original_url($uri, '', $lang, array($this->transposh->database, 'fetch_original'));
        }
        return $uri;
    }

    /**
     * For search form in current buddypress
     * @param type $url
     */
    function bbp_get_search_results_url($url) {
        $lang = transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->home_url);
        $href = transposh_utils::rewrite_url_lang_param($url, $this->transposh->home_url, $this->transposh->enable_permalinks_rewrite, $lang, false);
        return $href;
    }

    /**
     * After saving action, makes sure activity has proper language
     * @param BP_Activity_Activity $params
     */
    function bp_activity_after_save($params) {
        // we don't need to modify our own activity stream
        if ($params->type == 'new_translation')
            return;
        if (transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->transposh->home_url))
            bp_activity_update_meta($params->id, 'tp_language', transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->transposh->home_url));
    }

    /**
     * Change the display of activity content using the transposh meta
     * @param string $content
     * @param BP_Activity_Activity $activity
     * @return string modified content
     */
    function bp_get_activity_content_body($content, $activity = "") { //XXX
        $activity_lang = bp_activity_get_meta($activity->id, 'tp_language');
        if ($activity_lang) {
            $content = "<span lang =\"$activity_lang\">" . $content . "</span>";
        }
        return $content;
    }

    /**
     * Add an item to the activity string upon translation
     * @global object $bp the global buddypress
     * @param string $translation
     * @param string $original
     * @param string $lang
     */
    function transposh_buddypress_stream($translation, $original, $lang) {
        global $bp;

        // we must have buddypress...
        if (!function_exists('bp_activity_add'))
            return false;

        // we only log translation for logged on users
        if (!$bp->loggedin_user->id)
            return;

        /* Because blog, comment, and blog post code execution happens before anything else
          we may need to manually instantiate the activity component globals */
        if (!$bp->activity && function_exists('bp_activity_setup_globals'))
            bp_activity_setup_globals();

        // just got this from buddypress, changed action and content
        $values = array(
            'user_id' => $bp->loggedin_user->id,
            'action' => sprintf(__('%s translated a phrase to %s with transposh:', 'buddypress'), bp_core_get_userlink($bp->loggedin_user->id), substr(transposh_consts::$languages[$lang], 0, strpos(transposh_consts::$languages[$lang], ','))),
            'content' => "Original: <span class=\"no_translate\">$original</span>\nTranslation: <span class=\"no_translate\">$translation</span>",
            'primary_link' => '',
            'component' => $bp->blogs->id,
            'type' => 'new_translation',
            'item_id' => false,
            'secondary_item_id' => false,
            'recorded_time' => gmdate("Y-m-d H:i:s"),
            'hide_sitewide' => false
        );

        return bp_activity_add($values);
    }

    /**
     * This function integrates with google sitemap generator, and adds for each viewable language, the rest of the languages url
     * Also - priority is reduced by 0.2
     * And this requires the following line at the sitemap-core.php, add-url function (line 1509 at version 3.2.4)
     * do_action('sm_addurl', $page);
     * @param GoogleSitemapGeneratorPage $sm_page Object containing the page information
     */
    function add_sm_transposh_urls($sm_page) {
        tp_logger("in sitemap add url: " . $sm_page->GetUrl() . " " . $sm_page->GetPriority(), 4);
        $sm_page = clone $sm_page;
        // we need the generator object (we know it must exist...)
        $generatorObject = &GoogleSitemapGenerator::GetInstance();
        // we reduce the priorty by 0.2, but not below zero
        $sm_page->SetProprity(max($sm_page->GetPriority() - 0.2, 0));

        /* <xhtml:link 
          rel="alternate"
          hreflang="de"
          href="http://www.example.com/de" /> */

        $viewable_langs = explode(',', $this->transposh->options->viewable_languages);
        $orig_url = $sm_page->GetUrl();
        foreach ($viewable_langs as $lang) {
            if (!$this->transposh->options->is_default_language($lang)) {
                $newloc = $orig_url;
                if ($this->transposh->options->enable_url_translate) {
                    $newloc = transposh_utils::translate_url($newloc, $this->transposh->home_url, $lang, array(&$this->transposh->database, 'fetch_translation'));
                }
                $newloc = transposh_utils::rewrite_url_lang_param($newloc, $this->transposh->home_url, $this->transposh->enable_permalinks_rewrite, $lang, false);
                $sm_page->SetUrl($newloc);
                $generatorObject->AddElement($sm_page);
            }
        }
    }

    /**
     * This function integrates with yoast sitemap generator, and adds for each viewable language, the rest of the languages url
     * Also - priority is reduced by 0.2
     * And this requires the following patch in class-sitemaps.php
     * For future yoast versions, and reference, look for this function:
      if ( ! in_array( $url['loc'], $stackedurls ) ) {
      // Use this filter to adjust the entry before it gets added to the sitemap
      $url = apply_filters( 'wpseo_sitemap_entry', $url, 'post', $p );
      if ( is_array( $url ) && $url !== array() ) {
      $output .= $this->sitemap_url( $url );
      $stackedurls[] = $url['loc'];
      }
      }

      And change to:
      ------------------------------------------------------------------------

      if ( ! in_array( $url['loc'], $stackedurls ) ) {
      // Use this filter to adjust the entry before it gets added to the sitemap
      $url = apply_filters( 'wpseo_sitemap_entry', $url, 'post', $p );
      if ( is_array( $url ) && $url !== array() ) {
      $output .= $this->sitemap_url( $url );
      $stackedurls[] = $url['loc'];
      }
      $langurls = apply_filters( 'wpseo_sitemap_language',$url);
      if ( is_array( $langurls )) {
      foreach ($langurls as $langurl) {
      $output .= $this->sitemap_url( $langurl );
      }
      }
      }
      -------------------------------------------------------------------------
     * @param yoast_url array $yoast_url Object containing the page information
     */
    function add_yoast_transposh_urls($yoast_url) {
        tp_logger("in sitemap add url: " . $yoast_url['loc'] . " " . $yoast_url['pri'], 2);
        $urls = array();

        $yoast_url['pri'] = max($yoast_url['pri'] - 0.2, 0);

        $viewable_langs = explode(',', $this->transposh->options->viewable_languages);
        $orig_url = $yoast_url['loc'];
        foreach ($viewable_langs as $lang) {
            if (!$this->transposh->options->is_default_language($lang)) {
                $newloc = $orig_url;
                if ($this->transposh->options->enable_url_translate) {
                    $newloc = transposh_utils::translate_url($newloc, $this->transposh->home_url, $lang, array(&$this->transposh->database, 'fetch_translation'));
                }
                $newloc = transposh_utils::rewrite_url_lang_param($newloc, $this->transposh->home_url, $this->transposh->enable_permalinks_rewrite, $lang, false);
                $yoast_url['loc'] = $newloc;
                $urls[] = $yoast_url;
            }
        }
        return $urls;
    }

    function woo_uri_filter($url) {
        $lang = transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $this->transposh->home_url);
        tp_logger('altering woo url to:' . transposh_utils::rewrite_url_lang_param($url, $this->transposh->home_url, $this->transposh->enable_permalinks_rewrite, $lang, $this->transposh->edit_mode));
        return transposh_utils::rewrite_url_lang_param($url, $this->transposh->home_url, $this->transposh->enable_permalinks_rewrite, $lang, $this->transposh->edit_mode);
    }

    /*
     * For the business directory plugin, hidden fields needs to be added to enable search:
     * every time after the do-srch param
      1. in search.tpl.php added this:
      <input type="hidden" name="lang" value="<?php echo transposh_get_current_language(); ?>" />
      2. in widget-search.php
      printf('<input type="hidden" name="lang" value="%s" />', transposh_get_current_language());
      3. templates-ui.php
      $html .= sprintf('<input type="hidden" name="lang" value="%s" />', transposh_get_current_language());
     */

    function fix_wpbdp_cat_links($url) {
        tp_logger($url, 1);
        return $url;
        $url = preg_replace('#/.lang=[a-z]*/#', '/', $url);
        if ($this->transposh->options->is_default_language($this->transposh->target_language)) {
            return $url;
        }
        tp_logger($url, 1);
        return transposh_utils::rewrite_url_lang_param($url, $this->transposh->home_url, $this->transposh->options->enable_permalinks, $this->transposh->target_language, $this->transposh->edit_mode);
    }

    function fix_wpbdp_links($url) {
        tp_logger($url, 1);
        $url = preg_replace('#/.lang=[a-z]*/#', '/', $url);
        if ($this->transposh->options->is_default_language($this->transposh->target_language)) {
            return $url;
        }
        tp_logger($url, 1);
        return transposh_utils::rewrite_url_lang_param($url, $this->transposh->home_url, $this->transposh->options->enable_permalinks, $this->transposh->target_language, $this->transposh->edit_mode);
    }

    function fix_wpbdp_links_base($url) {
        if ($this->transposh->options->is_default_language($this->transposh->target_language)) {
            return $url;
        }
        tp_logger($url, 1);
        return transposh_utils::rewrite_url_lang_param($url, $this->transposh->home_url, $this->transposh->options->enable_permalinks, $this->transposh->target_language, $this->transposh->edit_mode);
    }

}
