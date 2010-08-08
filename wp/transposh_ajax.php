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
 * This file handles various AJAX needs of our plugin
 */
// The following headers allow for cross-domain posting here, which is useful for some weird sites
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: X-Requested-With');
header('Access-Control-Max-Age: 86400');

// Exit early so the page isn't fully loaded for options requests
if (strtolower($_SERVER['REQUEST_METHOD']) == 'options') {
    exit();
}

// we need wordpress and us...
$root = $_SERVER["DOCUMENT_ROOT"];
$self = $_SERVER["SCRIPT_NAME"];
// go down 4 dirs...
for ($i = 0; $i < 5; $i++)
    $self = substr($self, 0, -strlen(strrchr($self, '/')));
if (file_exists($root . $self . '/wp-load.php')) {
    require_once $root . $self . '/wp-load.php';
} else {
    // fallback plan
    require_once('../../../../wp-load.php');
}
//  the case of posted translation
if (isset($_POST['translation_posted'])) {
    do_action('transposh_translation_posted');
    $my_transposh_plugin->database->update_translation();
}
// getting translation history
elseif (isset($_GET['tr_token_hist'])) {
    $my_transposh_plugin->database->get_translation_history($_GET['tr_token_hist'], $_GET['lang']);
}
// getting phrases of a post (if we are in admin)
elseif (isset($_GET['tr_phrases_post'])) {
    $my_transposh_plugin->postpublish->get_post_phrases($_GET['post']);
}
// set the cookie with ajax, no redirect needed
elseif (isset($_GET['tr_cookie'])) {
    setcookie('TR_LNG', transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $my_transposh_plugin->home_url), time() + 90 * 24 * 60 * 60, COOKIEPATH, COOKIE_DOMAIN);
    logger('Cookie ' . transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $my_transposh_plugin->home_url));
}
// Set our cookie and return (if no js works - or we are in the default language)
elseif (isset($_GET['tr_cookie_bck'])) {
    setcookie('TR_LNG', transposh_utils::get_language_from_url($_SERVER['HTTP_REFERER'], $my_transposh_plugin->home_url), time() + 90 * 24 * 60 * 60, COOKIEPATH, COOKIE_DOMAIN);
    if ($_SERVER['HTTP_REFERER']) {
        wp_redirect($_SERVER['HTTP_REFERER']);
    } else {
        wp_redirect($my_transposh_plugin->home_url);
    }
}
// Start full translation
elseif (isset($_GET['translate_all'])) {
    // get all ids in need of translation
    $page_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE (post_type='page' OR post_type='post') AND (post_status='publish' OR post_status='private') ORDER BY ID DESC");
    // only high capabilities users can...
    if (!current_user_can('edit_post', $page_ids[0])) return;
    echo json_encode($page_ids);
}
// Proxyed translation for google translate
elseif (isset($_GET['tgp'])) {
    // we need curl for this proxy
    if (!function_exists('curl_init')) return;
    // we want to avoid unneeded work or dos attacks on languages we don't support
    if (!in_array($_GET['tgl'], $google_proxied_languages) || !$my_transposh_plugin->options->is_editable_language($_GET['tgl']))
            return;
    $url = 'http://translate.google.com/translate_a/t?client=a&text=' . urlencode($_GET['tgp']) . '&tl=' . $_GET['tgl'] . '&sl=auto';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //must set agent for google to respond with utf-8
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $output = curl_exec($ch);
    curl_close($ch);
    header('Content-type: text/html; charset=utf-8');
    echo $output;
}
// Start backup on demand
elseif (isset($_GET['backup'])) {
    $my_transposh_plugin->run_backup();
}
// Start cleanup on demand
elseif (isset($_GET['cleanup'])) {
    // just make sure the admin started this... recently enough
    check_ajax_referer('transposh-clean','nonce');
    $my_transposh_plugin->database->cleanup($_GET['days']);
}

?>