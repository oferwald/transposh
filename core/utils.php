<?php
/*  Copyright © 2009-2010 Transposh Team (website : http://transposh.org)
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
 * Remove from url any language (or editing) params that were added for our use.
 * Return the scrubed url
*/
function cleanup_url($url, $home_url, $remove_host = false) {

    $parsedurl = @parse_url($url);
    //cleanup previous lang & edit parameter from url

    if (isset($parsedurl['query'])) {
        $params = explode('&',$parsedurl['query']);
        foreach ($params as $key => $param) {
            if (stripos($param,LANG_PARAM) === 0) unset ($params[$key]);
            if (stripos($param,EDIT_PARAM) === 0) unset ($params[$key]);
        }
    }
    // clean the query
    unset($parsedurl['query']);
    if(isset($params) && $params) {
        $parsedurl['query'] = implode('&',$params);
    }

    //cleanup lang identifier in permalinks
    //remove the language from the url permalink (if in start of path, and is a defined language)
    $home_path = rtrim(parse_url($home_url,PHP_URL_PATH),"/");
    logger ("home: $home_path ".$parsedurl['path'],5);
    if ($home_path && strpos($parsedurl['path'], $home_path) === 0) {
        logger ("homein!: $home_path",5);
        $parsedurl['path'] = substr($parsedurl['path'],strlen($home_path));
        $gluebackhome = true;
    }

    if (strlen($parsedurl['path']) > 2) {
        $secondslashpos = strpos($parsedurl['path'], "/",1);
        if (!$secondslashpos) $secondslashpos = strlen($parsedurl['path']);
        $prevlang =  substr($parsedurl['path'],1,$secondslashpos-1);
        if (isset ($GLOBALS['languages'][$prevlang])) {
            logger ("prevlang: ".$prevlang,4);
            $parsedurl['path'] = substr($parsedurl['path'],$secondslashpos);
        }
    }
    if ($gluebackhome) $parsedurl['path'] = $home_path.$parsedurl['path'];
    if ($remove_host) {
        unset ($parsedurl['scheme']);
        unset ($parsedurl['host']);
    }
    $url = glue_url($parsedurl);
    return $url;
}

/**
 * Update the given url to include language params.
 * @param string $url - Original URL to rewrite
 * @param string $lang - Target language code
 * @param boolean $is_edit - should url indicate editing
 * @param boolean $use_params_only - only use paramaters and avoid permalinks
 */
// Should send a transposh interface to here TODO - enable permalinks rewrite
// TODO - Should be able to not write default language for url (done with empty lang?)
function rewrite_url_lang_param($url,$home_url, $enable_permalinks_rewrite, $lang, $is_edit, $use_params_only=FALSE) {
    logger("rewrite old url: $url, permalinks: $enable_permalinks_rewrite, lang: $lang, is_edit: $is_edit, home_url: $home_url",5);

    $newurl = str_replace('&#038;', '&', $url);
    $newurl = html_entity_decode($newurl, ENT_NOQUOTES);
    $parsedurl = parse_url($newurl);

    // if we are dealing with some other url, we won't touch it!
    if (isset($parsedurl['host']) && !($parsedurl['host'] == parse_url($home_url,PHP_URL_HOST))) {
        return $url;
    }

    //remove prev lang and edit params - from query string - reserve other params
    if (isset($parsedurl['query'])) {
        $params = explode('&',$parsedurl['query']);
        foreach ($params as $key => $param) {
            if (stripos($param,LANG_PARAM) === 0) unset ($params[$key]);
            if (stripos($param,EDIT_PARAM) === 0) unset ($params[$key]);
        }
    }
    // clean the query
    unset($parsedurl['query']);

    //remove the language from the url permalink (if in start of path, and is a defined language)
    $home_path = rtrim(parse_url($home_url,PHP_URL_PATH),"/");
    logger ("home: $home_path ".$parsedurl['path'],5);
    if ($home_path && strpos($parsedurl['path'], $home_path) === 0) {
        logger ("homein!: $home_path",5);
        $parsedurl['path'] = substr($parsedurl['path'],strlen($home_path));
        $gluebackhome = true;
    }
    if (strlen($parsedurl['path']) > 2) {
        $secondslashpos = strpos($parsedurl['path'], "/",1);
        if (!$secondslashpos) $secondslashpos = strlen($parsedurl['path']);
        $prevlang =  substr($parsedurl['path'],1,$secondslashpos-1);
        if (isset ($GLOBALS['languages'][$prevlang])) {
            logger ("prevlang: ".$prevlang,4);
            $parsedurl['path'] = substr($parsedurl['path'],$secondslashpos);
        }
    }

    //override the use only params - admin configured system to not touch permalinks
    if(!$enable_permalinks_rewrite) {
        $use_params_only = TRUE;
    }

    //$params ="";
    if($is_edit) {
        $params[edit] = EDIT_PARAM . '=1';
    }

    if($use_params_only && $lang) {
        $params['lang'] = LANG_PARAM . "=$lang";
    }
    else {
        if ($lang) {
            if (!$parsedurl['path']) $parsedurl['path'] = "/";
            $parsedurl['path'] = "/".$lang.$parsedurl['path'];
        }
    }
    if ($gluebackhome) $parsedurl['path'] = $home_path.$parsedurl['path'];

    //insert params to url
    if(isset($params) && $params) {
        $parsedurl['query'] = implode('&',$params);
        logger("params: $params",4);
    }

    // more cleanups
    //$url = preg_replace("/&$/", "", $url);
    //$url = preg_replace("/\?$/", "", $url);

    //    $url = htmlentities($url, ENT_NOQUOTES);
    $url = glue_url($parsedurl);
    logger("new url: $url",5);
    return $url;
}

function get_language_from_url($url, $home_url) {

    $parsedurl = @parse_url($url);

    //option 1, lanaguage is in the query ?lang=xx
    if (isset($parsedurl['query'])) {
        $params = explode('&',$parsedurl['query']);
        foreach ($params as $key => $param) {
            if (stripos($param,LANG_PARAM) === 0) {
                $langa = explode("=",$params[$key]);
                return ($langa[1]);
            }
        }
    }

    //option 2, language is in permalink

    //cleanup lang identifier in permalinks
    //remove the language from the url permalink (if in start of path, and is a defined language)
    $home_path = rtrim(parse_url($home_url,PHP_URL_PATH),"/");
//    logger ("home: $home_path ".$parsedurl['path'],5);
    if ($home_path && strpos($parsedurl['path'], $home_path) === 0) {
//        logger ("homein!: $home_path",5);
        $parsedurl['path'] = substr($parsedurl['path'],strlen($home_path));
//        $gluebackhome = true;
    }

    if (strlen($parsedurl['path']) > 2) {
        $secondslashpos = strpos($parsedurl['path'], "/",1);
        if (!$secondslashpos) $secondslashpos = strlen($parsedurl['path']);
        $prevlang =  substr($parsedurl['path'],1,$secondslashpos-1);
        if (isset ($GLOBALS['languages'][$prevlang])) {
            //logger ("prevlang: ".$prevlang,4);
            //$parsedurl['path'] = substr($parsedurl['path'],$secondslashpos);
            return $prevlang;
        }
    }
    return false;
}

/**
 * glue a parse_url array back to a url
 * @param array $parsed url_parse style array
 * @return combined url
 */
function glue_url($parsed) {
    if (!is_array($parsed)) {
        return false;
    }

    $uri = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '' : '//') : '';
    $uri .= isset($parsed['user']) ? $parsed['user'].(isset($parsed['pass']) ? ':'.$parsed['pass'] : '').'@' : '';
    $uri .= isset($parsed['host']) ? $parsed['host'] : '';
    $uri .= isset($parsed['port']) ? ':'.$parsed['port'] : '';

    if (isset($parsed['path'])) {
        $uri .= (substr($parsed['path'], 0, 1) == '/') ?
                $parsed['path'] : ((!empty($uri) ? '/' : '' ) . $parsed['path']);
    }

    $uri .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
    $uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';

    return $uri;
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

/**
 * Function to translate a given url permalink to a target language
 * TODO - check params
 * @param string $href
 * @param string $home_url
 * @param string $target_language
 * @param function $fetch_translation_func
 * @return string translated url permalink
 */
function translate_url($href, $home_url, $target_language,$fetch_translation_func) {
    // todo - check query part... sanitize
    if (strpos($href,'?') !== false) {
        list ($href,$querypart) = explode('?', $href);
        $querypart = '?'.$querypart;
    }
    $href = substr($href,strlen($home_url)+1);
    $parts = explode('/', $href);
    foreach ($parts as $part) {
        if (!$part) continue;
        list($translated_text, $old_source) = call_user_func_array($fetch_translation_func, array($part, $target_language));
        if ($translated_text)
            $url .= '/'.str_replace(' ', '-',$translated_text);
        else {
            // now the same attempt with '-' replaced to ' '
            list($translated_text, $old_source) = call_user_func_array($fetch_translation_func, array(str_replace('-', ' ', $part), $target_language));
            //logger ($part. ' '.str_replace('-', ' ', $part).' '.$translated_text);
            if ($translated_text)
                $url .= '/'.str_replace(' ', '-',$translated_text);
            else
                $url .= '/'.$part;
        }
    }
    if (substr($href,strlen($href)-1) == '/')
        $url.='/';
    return $home_url.$url.$querypart;
}

/**
 * From a given translated url, tries to get the original URL
 * @param string $href
 * @param string $home_url
 * @param string $target_language
 * @param function $fetch_translation_func
 * @return string
 */
function get_original_url($href, $home_url, $target_language,$fetch_translation_func) {
    $href = substr($href,strlen($home_url)+1);
    $url = urldecode($href);
    $url = (($pos=strpos($url, '?')) ? substr($url, 0, $pos) : $url);
    $parts = explode('/', $url);
    foreach ($parts as $part) {
        if (!$part) continue;
        // don't attempt for lang or numbers
        if ($part == $target_language || is_numeric($part)) {
            $url2 .= '/'.$part;
            continue;
        }

        $original_text = call_user_func_array($fetch_translation_func, array($part, $target_language));
        if ($original_text)
            $url2 .= '/'.strtolower(str_replace(' ', '-',$original_text)); //? CHECK
        else {
            $original_text = call_user_func_array($fetch_translation_func, array(str_replace('-', ' ', $part), $target_language));
            if ($original_text)
                $url2 .= '/'.strtolower(str_replace(' ', '-',$original_text)); //? CHECK
            else
                $url2 .= '/'.$part;
        }
    }
    // TODO: Consider sanitize_title_with_dashes
    // TODO : need to handle params....
    //logger(substr($url,strlen($url)-1));
    //if (substr($url,strlen($url)-1) == '/') $url2 .= '/';
    //$url2 = rtrim($url2,'/');
    //logger ("$href $url $url2");
    //$href = $this->home_url.$url2;
    if (substr($href,strlen($href)-1) == '/')
        $url2.='/';
    return $home_url.$url2;
}

/**
 * Function to display a flag
 * @param string $path path to flag images
 * @param string $flag the flag (normally iso code)
 * @param string $language the name of the lanaguage
 * @param boolean $css using css code?
 * @return string Html with flag
 */
function display_flag ($path, $flag, $language, $css = false) {
    if (!$css) {
        return  "<img src=\"$path/$flag.png\" title=\"$language\" alt=\"$language\"/>";
    } else {
        return "<span title=\"$language\" class=\"trf trf-{$flag}\"></span>";
    }
}

/**
 * determine which language out of an available set the user prefers most
 * adapted from php documentation page
 * @param array $available_languages array with language-tag-strings (must be lowercase) that are available
 * @param string $default_lang Language that will be default (first in available languages if not provided)
 * @param string $http_accept_language a HTTP_ACCEPT_LANGUAGE string (read from $_SERVER['HTTP_ACCEPT_LANGUAGE'] if left out)
 * @return string
 */
function prefered_language ($available_languages,$default_lang="auto",$http_accept_language="auto") {
    // if $http_accept_language was left out, read it from the HTTP-Header
    if ($http_accept_language == "auto") $http_accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

    // standard  for HTTP_ACCEPT_LANGUAGE is defined under
    // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
    // pattern to find is therefore something like this:
    //    1#( language-range [ ";" "q" "=" qvalue ] )
    // where:
    //    language-range  = ( ( 1*8ALPHA *( "-" 1*8ALPHA ) ) | "*" )
    //    qvalue         = ( "0" [ "." 0*3DIGIT ] )
    //            | ( "1" [ "." 0*3("0") ] )
    preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" .
            "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i",
            $http_accept_language, $hits, PREG_SET_ORDER);

    // default language (in case of no hits) is the first in the array
    if ($default_lang=='auto') $bestlang = $available_languages[0]; else $bestlang = $default_lang;
    $bestqval = 0;

    foreach ($hits as $arr) {
        // read data from the array of this hit
        $langprefix = strtolower ($arr[1]);
        if (!empty($arr[3])) {
            $langrange = strtolower ($arr[3]);
            $language = $langprefix . "-" . $langrange;
        }
        else $language = $langprefix;
        $qvalue = 1.0;
        if (!empty($arr[5])) $qvalue = floatval($arr[5]);

        // find q-maximal language
        if (in_array($language,$available_languages) && ($qvalue > $bestqval)) {
            $bestlang = $language;
            $bestqval = $qvalue;
        }
        // if no direct hit, try the prefix only but decrease q-value by 10% (as http_negotiate_language does)
        else if (in_array($languageprefix,$available_languages) && (($qvalue*0.9) > $bestqval)) {
            $bestlang = $languageprefix;
            $bestqval = $qvalue*0.9;
        }
    }
    return $bestlang;
}

?>