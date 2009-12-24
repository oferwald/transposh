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
*/

/*
 * This file handles various AJAX needs of our plugin
 */
// we need wordpress and us...
require_once('../../../../wp-load.php');

// the case of posted translation
if (isset($_POST['translation_posted'])) {
    if ($_POST['translation_posted'] == 2) {
        $my_transposh_plugin->database->update_translation_new();
    }
    else {
        $my_transposh_plugin->database->update_translation();
    }
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
    setcookie('TR_LNG',get_language_from_url($_SERVER['HTTP_REFERER'], $my_transposh_plugin->home_url),time()+90*24*60*60,COOKIEPATH,COOKIE_DOMAIN);
    logger ('Cookie '.get_language_from_url($_SERVER['HTTP_REFERER'], $my_transposh_plugin->home_url));
}
// Set our cookie and return (if no js works - or we are in the default language)
elseif (isset($_GET['tr_cookie_bck'])) {
    setcookie('TR_LNG',get_language_from_url($_SERVER['HTTP_REFERER'], $my_transposh_plugin->home_url),time()+90*24*60*60,COOKIEPATH,COOKIE_DOMAIN);
    wp_redirect($_SERVER['HTTP_REFERER']);
}
?>