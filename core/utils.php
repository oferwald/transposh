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


/*
 * Update the given url to include language params.
 * param url - the original url to rewrite
 * param lang - language code
 * param is_edit - is running in edit mode.
 * param use_params_only - use only parameters as modifiers, i.e. not permalinks
 */
function rewrite_url_lang_param($url, $lang, $is_edit, $use_params_only=FALSE)
{
	global $home_url, $home_url_quoted, $enable_permalinks_rewrite;

	$url = html_entity_decode($url, ENT_NOQUOTES);

	if(!$enable_permalinks_rewrite)
	{
		//override the use only params - admin configured system to not touch permalinks
		$use_params_only = TRUE;
	}

	if($is_edit)
	{
		$params = EDIT_PARAM . '=1&';

	}

	if($use_params_only)
	{
		$params .= LANG_PARAM . "=$lang&";
	}
	else
	{
		$url = preg_replace("/$home_url_quoted\/(..(-..)?\/)?\/?/",
                                 "$home_url/$lang/",  $url);
	}

	if($params)
	{
		//insert params to url
		$url = preg_replace("/(.+\/[^\?\#]*[\?]?)/", '$1?' . $params, $url);

		//Cleanup extra &
		$url = preg_replace("/&&+/", "&", $url);

		//Cleanup extra ?
		$url = preg_replace("/\?\?+/", "?", $url);
	}

	// more cleanups
	$url = preg_replace("/&$/", "", $url);
	$url = preg_replace("/\?$/", "", $url);

	$url = htmlentities($url, ENT_NOQUOTES);

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