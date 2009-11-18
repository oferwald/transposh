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
 *
 *  adapted metabox sample code from http://www.code-styling.de/
 */

/*
 * Provide the admin page for configuring the translation options. eg.  what languages ?
 * who is allowed to translate ?
 */

define ("TR_NONCE","transposh_nonce");

require_once("core/logging.php");
//class that reperesent the complete plugin
class transposh_postpublish {
    /** @property transposh_plugin $transposh father class */
    private $transposh;
//constructor of class, PHP4 compatible construction for backward compatibility
    function transposh_postpublish(&$transposh) {
        $this->transposh = &$transposh;
        add_action('admin_menu', array(&$this, 'on_admin_menu'));
    }

    function on_admin_menu() {
        //add our metabox to the post and pubish pages
        logger ('adding metaboxes');
        add_meta_box( 'myplugin_sectionid', __( 'My Post Section Title', 'myplugin_textdomain' ), array(&$this, "myplugin_inner_custom_box"), 'post', 'side', 'core');
        add_meta_box( 'myplugin_sectionid', __( 'My Post Section Title', 'myplugin_textdomain' ), array(&$this, "myplugin_inner_custom_box"), 'page', 'side', 'core');
    }

        /*add_action('publish_post', 'on_publish');
        add_action('edit_post', 'on_publish');
        add_action('admin_menu', 'on_publish');

        if ( function_exists('add_meta_box') ) {
            add_meta_box( 'myplugin_sectionid', __( 'My Post Section Title', 'myplugin_textdomain' ),
                'myplugin_inner_custom_box', 'post', 'normal' );
        }*/

/* Prints the inner fields for the custom post/page section */
    function myplugin_inner_custom_box() {

        // Use nonce for verification

        logger ("was here!");
        echo '<input type="hidden" name="myplugin_noncename" id="myplugin_noncename" value="' .
            wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

        // The actual fields for data entry

        echo '<label for="myplugin_new_field">' . __("Description for this field", 'myplugin_textdomain' ) . '</label> ';
        echo '<input type="text" name="myplugin_new_field" value="whatever" size="25" />';
    }

/*
 *
 * Happens after post is published
 */

    function on_publish() {
        logger("published - trying enqueue");
        add_meta_box( 'myplugin_sectionid', __( 'My Post Section Title', 'myplugin_textdomain' ),
            'myplugin_inner_custom_box', 'post', 'normal', 'high' );
        logger("metaboxed!");

        add_meta_box('categorydiv', __('Categories'), 'post_categories_meta_box', 'post', 'side', 'core');
        wp_enqueue_script("transposh","{$this->transposh_plugin_url}/js/transposh.js?post_url=$post_url{$edit_mode}&lang={$this->target_language}&prefix=".SPAN_PREFIX,array("jquery"),TRANSPOSH_PLUGIN_VER,get_option(ENABLE_FOOTER_SCRIPTS));
    }

}
?>