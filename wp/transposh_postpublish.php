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
 *
 */

/*
 * Provides the side widget in the page/edit pages which will do translations
 */

/**
 * class that makes changed to the edit page and post page, adding our change to the side ba
 */
class transposh_postpublish {

    /** @var transposh_plugin Container class */
    private $transposh;
    /** @var boolean Did we just edited/saved? */
    private $just_published = false;

    /**
     *
     * Construct our class
     * @param transposh_plugin $transposh
     */
    function transposh_postpublish(&$transposh) {
        $this->transposh = &$transposh;
        // we'll only do something if so configured to do
        if ($this->transposh->options->get_enable_auto_post_translate()) {
            add_action('edit_post', array(&$this, 'on_edit'));
            // add_action('publish_post',array(&$this, 'on_publish'));
            add_action('admin_menu', array(&$this, 'on_admin_menu'));
        }
    }

    /**
     * Admin menu created action, where we create our metaboxes
     */
    function on_admin_menu() {
//add our metabox to the post and pubish pages
        logger('adding metaboxes');
        add_meta_box('transposh_postpublish', 'Transposh', array(&$this, "transposh_postpublish_box"), 'post', 'side', 'core');
        add_meta_box('transposh_postpublish', 'Transposh', array(&$this, "transposh_postpublish_box"), 'page', 'side', 'core');
        if ($_GET['justedited']) {
            //wp_enqueue_script("google", "http://www.google.com/jsapi", array(), '1', true);
            wp_enqueue_script("transposh", $this->transposh->transposh_plugin_url . '/' . TRANSPOSH_DIR_JS . '/transposhadmin.js', array('jquery'), TRANSPOSH_PLUGIN_VER, true);
            wp_localize_script("transposh", "t_jp", array(
                'post_url' => $this->transposh->post_url,
                'post' => $_GET['post'],
                'preferred' => $this->transposh->options->get_preferred_translator(),
                'l10n_print_after' => 't_jp.g_langs = ' . json_encode($GLOBALS['google_languages']) . '; t_jp.m_langs = ' . json_encode($GLOBALS['bing_languages']) . ';'/*
                      'plugin_url' => $this->transposh_plugin_url,
                      'edit' => ($this->edit_mode? '1' : ''),
                      //'rtl' => (in_array ($this->target_language, $GLOBALS['rtl_languages'])? 'true' : ''),
                      'lang' => $this->target_language,
                      // those two options show if the script can support said engines
                      'prefix' => SPAN_PREFIX,

                      'progress'=>$this->edit_mode || $this->options->get_widget_progressbar() ? '1' : '') */
//			'l10n_print_after' => 'try{convertEntities(inlineEditL10n);}catch(e){};'
            ));
            wp_enqueue_style('jqueryui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/ui-lightness/jquery-ui.css', array(), '1.8.2');
            wp_enqueue_script('jqueryui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/jquery-ui.min.js', array('jquery'), '1.8.2', true);
        }
    }

    /**
     * Loop through all the post phrases and return them in json formatted script
     * @param int $postID
     */
    function get_post_phrases($postID) {
        // Some security, to avoid others from seeing private posts
        if (!current_user_can('edit_post', $postID)) return;
        $post = get_post($postID);
        // Display filters
        $title = apply_filters('the_title', $post->post_title);
        $content = apply_filters('the_content', $post->post_content);
        // TODO - grab phrases from rss excerpt
        //$output = get_the_excerpt();
        // echo apply_filters('the_excerpt_rss', $output);
        //TODO - get comments text

        $parser = new parser();
        $phrases = $parser->get_phrases_list($content);
        $phrases2 = $parser->get_phrases_list($title);

        // Merge the two arrays for traversing
        $phrases = array_merge($phrases, $phrases2);

        // Add phrases from permalink
        if ($this->transposh->options->get_enable_url_translate()) {
            $permalink = get_permalink($postID);
            $permalink = substr($permalink, strlen($this->transposh->home_url) + 1);
            $parts = explode('/', $permalink);
            foreach ($parts as $part) {
                if (!$part || is_numeric($part)) continue;
                $part = str_replace('-', ' ', $part);
                $phrases[] = urldecode($part);
            }
        }

        foreach ($phrases as $key) {
            foreach (explode(',', $this->transposh->options->get_editable_langs()) as $lang) {
                // if this isn't the default language or we specifically allow default language translation, we will seek this out...
                // as we don't normally want to auto-translate the default language -FIX THIS to include only correct stuff, how?
                if (!$this->transposh->options->is_default_language($lang) || $this->transposh->options->get_enable_default_translate()) {
                    // There is no point in returning phrases, languages pairs  that cannot be translated
                    if (in_array($lang, $GLOBALS['bing_languages']) || in_array($lang, $GLOBALS['google_languages'])) {
                        list($translation, $source) = $this->transposh->database->fetch_translation($key, $lang);
                        if (!$translation) {
                            // p stands for phrases, l stands for languages, t is token
                            if (!is_array($json['p'][$key]['l'])) {
                                $json['p'][$key]['l'] = array();
                            }
                            array_push($json['p'][$key]['l'], $lang);
                        }
                    }
                }
            }
            // only if a languages list was created we'll need to translate this
            if (is_array($json['p'][$key]['l'])) {
                $json['p'][$key]['t'] = base64_url_encode($key);
                $json['length']++;
            }
        }

        // add the title
        //        if ($json['length'])
        $json['posttitle'] = $title;

        // the header helps with debugging
        header("Content-type: text/javascript");
        echo json_encode($json);
    }

    /**
     * This is the box that appears on the side
     */
    function transposh_postpublish_box() {
        // the nonce will help double translation if time has passed
        if ($_GET['justedited'] && wp_verify_nonce($_GET['justedited']))
                $this->just_published = true;

        if ($this->just_published) {
            echo '<div id="tr_loading">Publication happened - loading phrases list...</div>';
        } else {
            echo 'Waiting for publication';
        }
    }

    /**
     * When this happens, the boxes are not created and a redirect happens, we currently use this to inform the next stage we are involved
     * @param int $postID
     */
    function on_edit($postID) {
        add_filter('wp_redirect', array(&$this, 'inform_published'));
    }

    /**
     * We add the justedited param here
     * @param string $url Original URL
     * @return string redirected URL
     */
    function inform_published($url) {
        return add_query_arg('justedited', wp_create_nonce(), $url);
    }

}
?>