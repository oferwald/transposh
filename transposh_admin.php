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
/*
 * Insert supported languages section in admin page
 */
function insert_supported_langs() {
    global $languages, $tr_plugin_url;

    echo
    '<script type="text/javascript">'.
        'function chbx_change(lang)'.
        '{'.
        'jQuery("#"+lang+"_edit").attr("checked",jQuery("#"+lang+"_view").attr("checked"))'.
        '}'.
        'jQuery(document).ready(function() {'.
        'jQuery("#tr_anon").click(function() {'.
        'if (jQuery("#tr_anon").attr("checked")) {'.
        'jQuery(".tr_editable").css("display","none");'.
        '} else {'.
        'jQuery(".tr_editable").css("display","");'.
        '}'.
        '});'.
        '});'.
        '</script>';
    echo '<table class="'.NO_TRANSLATE_CLASS.'" style="width: 100%"><tr>';

    // we will hide the translatable column if anonymous can translate anyway
    if (can_translate('anonymous')) $extrastyle = ' style ="display:none"';

    $columns = 2;
    for($hdr=0; $hdr < $columns; $hdr++) {
        $extrapad = ($hdr != $columns - 1) ? ";padding-right: 40px" : '';
        echo '<th style="text-align:left; width:'.(100/$columns).'%">Language</th>'.
            '<th title="Is this language user selectable?">Viewable</th>'.
            '<th title="Is this language visible for translators?"'.$extrastyle.' class="tr_editable">Translatable</th>'.
            '<th>Default</th>'.
            '<th style="text-align:left;width: 80px'.$extrapad.'" title="Can we auto-translate this language?">Auto?</th>';
    }
    echo '</tr>';

    foreach($languages as $code => $lang) {
        list ($language,$flag,$autot) = explode (",",$lang);
        if(!($i % $columns)) echo '<tr>';
        $i++;

        echo "<td><img src=\"$tr_plugin_url/img/flags/$flag.png\" alt=\"\"/>&nbsp;$language</td>";
        echo '<td align="center"><input type="checkbox" id="' . $code .'_view" name="' .
            $code . '_view" onchange="chbx_change(\'' . $code . '\')" ' . is_viewable($code) . '/></td>';
        echo '<td class="tr_editable"'.$extrastyle.' align="center"><input type="checkbox" id="' . $code . '_edit" name="' .
            $code . '_edit" ' . is_editable($code). '/></td>';
        echo "<td align=\"center\"><input type=\"radio\" name=\"default_lang\" value=\"$code\" " .
            is_default_lang($code). "/></td>";
        // TODO: Add icons?
        echo "<td>".($autot ? "Y" : "N")."</td>";

        if(!($i % $columns)) echo '</tr>';
    }
    // add a missing </tr> if needed
    if($i % $columns) echo '</tr>';
    echo '</table>';
}

/*
 * Determine if the given language code is currentlly editable
 * Return 'checked' if true otherwise ""
 */
function is_editable($code) {
    $langs = get_option(EDITABLE_LANGS);
    return (strpos($langs, $code) !== FALSE) ? 'checked="checked"' : '';
}

/*
 * Determine if the given language code is currentlly viewable
 * Return 'checked' if true otherwise ""
 */
function is_viewable($code) {
    $langs = get_option(VIEWABLE_LANGS);
    return (strpos($langs, $code) !== FALSE) ? 'checked="checked"' : '';
}

/*
 * Determine if the given language code is currentlly the default language
 * Return 'checked' if true otherwise ""
 */
function is_default_lang($code) {
    global $languages;
    $default = get_option(DEFAULT_LANG);
    if(!$languages[$default]) $default = "en";
    return ($default ==  $code) ? 'checked="checked"' : '';
}

/*
 * Insert permissions section in the admin page
 */
function insert_permissions() {
    global $wp_roles;
    //display known roles and their permission to translate
    foreach($wp_roles->get_names() as $role_name => $something) {
        echo '<input type="checkbox" value="1" name="'.$role_name.'" '.can_translate($role_name).
            '/> '.ucfirst($role_name).'&nbsp;&nbsp;&nbsp;';
    }
    //Add our own custom role
    echo '<input id="tr_anon" type="checkbox" value="1" name="anonymous" '.	can_translate('anonymous') . '/> Anonymous';
}

/*
 * Insert the option to enable/disable rewrite of perlmalinks.
 * When disabled only parameters will be used to identify the current language.
 */
function insert_permalink_rewrite_option() {
    $checked = (get_option(ENABLE_PERMALINKS_REWRITE)) ? 'checked="checked"' :'';
    echo '<input type="checkbox" value="1" name="enable_permalinks" '. $checked . '/> '.
        'Rewrite URLs to be search engine friendly, '.
        'e.g.  (http://wordpress.org/<strong>en</strong>). '.
        'Requires that permalinks will be enabled.';
}

/*
 * Insert the option to enable/disable pushing of scripts to footer.
 */
function insert_script_footer_option() {
    $checked = (get_option(ENABLE_FOOTER_SCRIPTS)) ? 'checked="checked"' :'';
    echo '<input type="checkbox" value="1" name="enable_footer_scripts" '. $checked . '/> '.
        'Push transposh scripts to footer of page instead of header, makes pages load faster. '.
        'Requires that your theme should have proper footer support.';
}

/*
 * Insert the option to enable/disable automatic translation.
 * Enabled by default.
 */
function insert_auto_translate_option() {
    $checked = (get_option(ENABLE_AUTO_TRANSLATE,1)) ? 'checked="checked"' : '';
    echo '<input type="checkbox" value="1" name="enable_autotranslate" '.$checked.'/> '.
        'Allow automatic translation of pages (currently using Google Translate)';
}

/*
 * Insert the option to enable/disable default language translation.
 * Disabled by default.
 */
function insert_default_translate_option() {
    $checked = (get_option(ENABLE_DEFAULT_TRANSLATE,0)) ? 'checked="checked"' : '';
    echo '<input type="checkbox" value="1" name="enable_defaulttranslate" '.$checked.'/> '.
        'Allow translation of default language - useful for sites with more than one major language';
}
//
/*
 * Indicates whether the given role can translate.
 * Return either "checked" or ""
 */
function can_translate($role_name) {
    global $wp_roles;
    if($role_name != 'anonymous') {
        $role = $wp_roles->get_role($role_name);
        if(isset($role) && $role->has_cap(TRANSLATOR))
            return 'checked="checked"';

    }
    else
        return (get_option(ANONYMOUS_TRANSLATION,1)) ? 'checked="checked"' : '';
}
//
/*
 * Handle newly posted admin options.
 */
function update_admin_options() {
    logger('Entry ' . __METHOD__, 1);
    global $wp_roles, $languages;
    $viewable_langs = array();
    $editable_langs = array();

    //update roles and capabilities
    foreach($wp_roles->get_names() as $role_name => $something) {
        $role = $wp_roles->get_role($role_name);
        if($_POST[$role_name] == "1")
            $role->add_cap(TRANSLATOR);
        else
            $role->remove_cap(TRANSLATOR);
    }

    //Anonymous needs to be handled differently as it does not have a role
    update_option(ANONYMOUS_TRANSLATION, ($_POST['anonymous']) ? 1 : 0);

    //Update the list of supported/editable languages
    foreach($languages as $code => $lang) {
        if($_POST[$code . '_view']) {
            $viewable_langs[$code] = $code;
            // force that every viewable lang is editable
            $editable_langs[$code] = $code;
        }

        if($_POST[$code . '_edit']) {
            $editable_langs[$code] = $code;
        }
    }

    update_option(VIEWABLE_LANGS, implode(',', $viewable_langs));
    update_option(EDITABLE_LANGS, implode(',', $editable_langs));
    update_option(DEFAULT_LANG,   $_POST['default_lang']);

    if(get_option(ENABLE_PERMALINKS_REWRITE) != $_POST['enable_permalinks']) {
        global $wp_rewrite;
        update_option(ENABLE_PERMALINKS_REWRITE, $_POST['enable_permalinks']);

        //rewrite rules
        add_filter('rewrite_rules_array', 'update_rewrite_rules');
        $wp_rewrite->flush_rules();
    }

    if(get_option(ENABLE_FOOTER_SCRIPTS) != $_POST['enable_footer_scripts'])
        update_option(ENABLE_FOOTER_SCRIPTS, $_POST['enable_footer_scripts']);

    if(get_option(ENABLE_AUTO_TRANSLATE,1) != $_POST['enable_autotranslate'])
        update_option(ENABLE_AUTO_TRANSLATE, $_POST['enable_autotranslate']);

    if(get_option(ENABLE_DEFAULT_TRANSLATE) != $_POST['enable_defaulttranslate'])
        update_option(ENABLE_DEFAULT_TRANSLATE, $_POST['enable_defaulttranslate']);

}

//class that reperesent the complete plugin
class transposh_plugin {

//constructor of class, PHP4 compatible construction for backward compatibility
    function transposh_plugin() {
    //add filter for WordPress 2.8 changed backend box system !
        add_filter('screen_layout_columns', array(&$this, 'on_screen_layout_columns'), 10, 2);
        //add some help
        add_filter('contextual_help_list', array(&$this, 'on_contextual_help'),100,2);
        //register callback for admin menu  setup
        add_action('admin_menu', array(&$this, 'on_admin_menu'));
        //register the callback been used if options of page been submitted and needs to be processed
        add_action('admin_post_save_transposh', array(&$this, 'on_save_changes'));
    }

    //for WordPress 2.8 we have to tell, that we support 2 columns !
    function on_screen_layout_columns($columns, $screen) {
        if ($screen == $this->pagehook) {
            $columns[$this->pagehook] = 2;
        }
        return $columns;
    }

    //add some help
    function on_contextual_help($filterVal,$screen) {
        if($screen == "settings_page_transposh") {
            $filterVal["settings_page_transposh"] = '<p>Transposh makes your blog translatable</p>'.
                '<a href="http://transposh.org/">Plugin homepage</a><br/>'.
                '<a href="http://transposh.org/faq/">Frequently asked questions</a>';
        }
        return $filterVal;
    }

    //extend the admin menu
    function on_admin_menu() {
    //add our own option page, you can also add it to different sections or use your own one
        $this->pagehook = add_options_page('Transposh control center', "Transposh", 'manage_options', TRANSPOSH_ADMIN_PAGE_NAME, array(&$this, 'on_show_page'));
        //register  callback gets call prior your own page gets rendered
        add_action('load-'.$this->pagehook, array(&$this, 'on_load_page'));
    }

    //will be executed if wordpress core detects this page has to be rendered
    function on_load_page() {
    //ensure, that the needed javascripts been loaded to allow drag/drop, expand/collapse and hide/show of boxes
        wp_enqueue_script('common');
        wp_enqueue_script('wp-lists');
        wp_enqueue_script('postbox');

        //add several metaboxes now, all metaboxes registered during load page can be switched off/on at "Screen Options" automatically, nothing special to do therefore
        add_meta_box('transposh-sidebox-about', 'About this plugin', array(&$this, 'on_sidebox_about_content'), $this->pagehook, 'side', 'core');
        add_meta_box('transposh-sidebox-widget', 'Widget settings', array(&$this, 'on_sidebox_widget_content'), $this->pagehook, 'side', 'core');
        add_meta_box('transposh-sidebox-news', 'Plugin news', array(&$this, 'on_sidebox_news_content'), $this->pagehook, 'side', 'core');
        add_meta_box('transposh-sidebox-stats', 'Plugin stats', array(&$this, 'on_sidebox_stats_content'), $this->pagehook, 'side', 'core');
        add_meta_box('transposh-contentbox-languages', 'Supported languages', array(&$this, 'on_contentbox_languages_content'), $this->pagehook, 'normal', 'core');
        add_meta_box('transposh-contentbox-translation', 'Translation settings', array(&$this, 'on_contentbox_translation_content'), $this->pagehook, 'normal', 'core');
        add_meta_box('transposh-contentbox-general', 'Generic settings', array(&$this, 'on_contentbox_generic_content'), $this->pagehook, 'normal', 'core');
    }

    //executed to show the plugins complete admin page
    function on_show_page() {
    //we need the global screen column value to beable to have a sidebar in WordPress 2.8
        global $screen_layout_columns;
        //add a 3rd content box now for demonstration purpose, boxes added at start of page rendering can't be switched on/off,
        //may be needed to ensure that a special box is always available
        add_meta_box('transposh-contentbox-community', 'Transposh community features (upcoming)', array(&$this, 'on_contentbox_community_content'), $this->pagehook, 'normal', 'core');
        //define some data can be given to each metabox during rendering - not used now
        //$data = array('My Data 1', 'My Data 2', 'Available Data 1');
        ?>
<div id="transposh-general" class="wrap">
            <?php screen_icon('options-general'); ?>
    <h2>Transposh</h2>
    <form action="admin-post.php" method="post">
                <?php wp_nonce_field(TR_NONCE); ?>
                <?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
                <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
        <input type="hidden" name="action" value="save_transposh" />

        <div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
            <div id="side-info-column" class="inner-sidebar">
                        <?php do_meta_boxes($this->pagehook, 'side', $data); ?>
            </div>
            <div id="post-body" class="has-sidebar">
                <div id="post-body-content" class="has-sidebar-content">
                            <?php do_meta_boxes($this->pagehook, 'normal', $data);
                                 /* Maybe add static content here later */
                            //do_meta_boxes($this->pagehook, 'additional', $data); ?>
                    <p>
                        <input type="submit" value="Save Changes" class="button-primary" name="Submit"/>
                    </p>
                </div>
            </div>
            <br class="clear"/>

        </div>
    </form>
</div>
<script type="text/javascript">
    //<![CDATA[
    jQuery(document).ready( function($) {
        // close postboxes that should be closed
        $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
        // postboxes setup
        postboxes.add_postbox_toggles('<?php echo $this->pagehook; ?>');
    });
    //]]>
</script>

    <?php
    }

    //executed if the post arrives initiated by pressing the submit button of form
    function on_save_changes() {
    //user permission check
        if ( !current_user_can('manage_options') )
            wp_die( __('Problems?') );
        //cross check the given referer
        check_admin_referer(TR_NONCE);

        //process here your on $_POST validation and / or option saving
        update_admin_options();
        transposh_widget_post();

        //lets redirect the post request into get request (you may add additional params at the url, if you need to show save results
        wp_redirect($_POST['_wp_http_referer']);
    }

    //below you will find for each registered metabox the callback method, that produces the content inside the boxes
    //i did not describe each callback dedicated, what they do can be easily inspected and compare with the admin page displayed

    function on_sidebox_about_content($data) {
        echo '<ul style="list-style-type:disc;margin-left:20px;">';
        echo '<li><a href="http://transposh.org/">Plugin Homepage</a></li>';
        echo '<li><a href="http://transposh.org/redir/newfeature">Suggest a Feature</a></li>';
        //Support Forum
        echo '<li><a href="http://transposh.org/redir/newticket">Report a Bug</a></li>';
        //Donate with PayPal
        echo '</ul>';
    }
    function on_sidebox_widget_content($data) {
        transposh_widget_control();
    }

    function on_sidebox_news_content($data) {
        require_once(ABSPATH . WPINC . '/rss.php');
        echo '<div style="margin:6px">';
        wp_rss('http://feeds2.feedburner.com/transposh', 5);
        echo '</div>';
    }
    function on_sidebox_stats_content($data) {
        db_stats();
    }

    function on_contentbox_languages_content($data) {
        insert_supported_langs();
    }

    function on_contentbox_translation_content($data) {
        echo '<h4>Who can translate ?</h4>';
        insert_permissions();

        echo '<h4>Enable automatic translation</h4>';
        insert_auto_translate_option();

        echo '<h4>Enable default language translation</h4>';
        insert_default_translate_option();

    }
    function on_contentbox_generic_content($data) {
        global $wp_version;
        echo '<h4>Rewrite URLs</h4>';
        insert_permalink_rewrite_option();

        if (floatval($wp_version) >= 2.8) {
            echo '<h4>Add scripts to footer</h4>';
            insert_script_footer_option();
        }
    }
    function on_contentbox_community_content($data) {
        echo "<p>This space is reserved for the coming community features of Transposh that will help you find translators to help with your site.</p>";
    }
}

$my_transposh_plugin = new transposh_plugin();
?>