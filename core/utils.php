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

/**
 *
 * Contains utility functions which are shared across the plugin.
 *
 */

require_once("constants.php");
require_once("logging.php");

/*
 * Update the given url to include language params.
 * param url - the original url to rewrite (expects full urls)
 * param lang - language code
 * param is_edit - is running in edit mode.
 * param use_params_only - use only parameters as modifiers, i.e. not permalinks
 */
function rewrite_url_lang_param($url, $lang, $is_edit, $use_params_only=FALSE) {
    global $home_url, $home_url_quoted, $enable_permalinks_rewrite;
    logger("old url: $url, lang: $lang, is_edit: $is_edit",5);
    //logger("home url: $home_url",3);
    //logger("home url_quoted: $home_url_quoted",3);
    //logger("enable_permalinks_rewrite: $enable_permalinks_rewrite",3);
    logger("url: $url",6);
    $url = html_entity_decode($url, ENT_NOQUOTES);
    $url = str_replace('&#038;', '&', $url);
    logger("urldec: $url",6);
    
    //remove prev lang and edit params?
    $url = preg_replace("/(" . LANG_PARAM . "|" . EDIT_PARAM . ")=[^&]*/i", "", $url);

    if(!$enable_permalinks_rewrite) {
    //override the use only params - admin configured system to not touch permalinks
        $use_params_only = TRUE;
    }

    $params ="";
    if($is_edit) {
        $params = EDIT_PARAM . '=1&';
    }

    if($use_params_only) {
        $params .= LANG_PARAM . "=$lang&";
    }
    else {
        $url = preg_replace("/$home_url_quoted\/(..(-..)?\/)?\/?/",
            "$home_url/$lang/",  $url);
    }
    logger("params: $params",6);

    if($params) {
    //insert params to url
        $url = preg_replace("/(.+\/[^\?\#]*[\?]?)/", '$1?' . $params, $url);
        logger("new url2: $url",6);

        //Cleanup extra &
        $url = preg_replace("/&&+/", "&", $url);

        //Cleanup extra ?
        $url = preg_replace("/\?\?+/", "?", $url);
    }

    // more cleanups
    $url = preg_replace("/&$/", "", $url);
    $url = preg_replace("/\?$/", "", $url);

    $url = htmlentities($url, ENT_NOQUOTES);
    logger("new url: $url",5);
    return $url;
}


/**
 * Encode a string as base 64 while avoiding characters which should be avoided
 * in uri, e.g. + is interpeted as a space.
 */
function base64_url_encode($input) {
    return strtr(base64_encode($input), '+/=', '-_,');
}

/**
 * Decode a string previously decoded with base64_url_encode
 */
function base64_url_decode($input) {
    return base64_decode(strtr($input, '-_,', '+/='));
}

?>