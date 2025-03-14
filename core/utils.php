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

/**
 *
 * Contains utility functions which are shared across the plugin.
 *
 */
require_once("constants.php");
require_once("logging.php");

/**
 * This is a static class to reduce chance of namespace collisions with other plugins
 */
class transposh_utils {

    /**
     * Encode URLs based of RFC 3986
     */
    public static function urlencode($url) {
        $entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
        $replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
        return str_replace($entities, $replacements, urlencode($url));
    }

    /**
     * Remove from url any language (or editing) params that were added for our use.
     * Return the scrubbed url
     */
    public static function cleanup_url($url, $home_url, $remove_host = false) {

        $parsedurl = @parse_url($url);
        //cleanup previous lang & edit parameter from url

        if (isset($parsedurl['query'])) {
            $params = explode('&', $parsedurl['query']);
            foreach ($params as $key => $param) {
                if (stripos($param, LANG_PARAM) === 0) {
                    unset($params[$key]);
                }
                if (stripos($param, EDIT_PARAM) === 0) {
                    unset($params[$key]);
                }
            }
        }
        // clean the query
        unset($parsedurl['query']);
        if (isset($params) && $params) {
            $parsedurl['query'] = implode('&', $params);
        }

        $gluebackhome = false;
        //cleanup lang identifier in permalinks
        //remove the language from the url permalink (if in start of path, and is a defined language)
        $home_path = rtrim((string)@parse_url($home_url, PHP_URL_PATH), "/");
        tp_logger("home: $home_path " . @$parsedurl['path'], 5);
        if ($home_path && @strpos($parsedurl['path'], $home_path) === 0) {
            tp_logger("homein!: $home_path", 5);
            $parsedurl['path'] = substr($parsedurl['path'], strlen($home_path));
            $gluebackhome = true;
        }

        if (@strlen($parsedurl['path']) > 2) {
            $secondslashpos = strpos($parsedurl['path'], "/", 1);
            if (!$secondslashpos) {
                $secondslashpos = strlen($parsedurl['path']);
            }
            $prevlang = substr($parsedurl['path'], 1, $secondslashpos - 1);
            if (transposh_consts::is_supported_language($prevlang)) {
                tp_logger("prevlang: " . $prevlang, 4);
                $parsedurl['path'] = substr($parsedurl['path'], $secondslashpos);
            }
        }
        if ($gluebackhome) {
            $parsedurl['path'] = $home_path . $parsedurl['path'];
        }
        if ($remove_host) {
            unset($parsedurl['scheme']);
            unset($parsedurl['host']);
        }
        $url = transposh_utils::glue_url($parsedurl);
        if (!$url) {
            return '/';
        }
        return transposh_utils::urlencode($url);
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
    public static function rewrite_url_lang_param($url, $home_url, $enable_permalinks_rewrite, $lang, $is_edit, $use_params_only = FALSE) {
        tp_logger("rewrite old url: $url, permalinks: $enable_permalinks_rewrite, lang: $lang, is_edit: $is_edit, home_url: $home_url", 5);

        $newurl = str_replace('&#038;', '&', $url);
        $newurl = html_entity_decode($newurl, ENT_NOQUOTES);
        $parsedurl = @parse_url($newurl);

        // if we are dealing with some other url, we won't touch it!
        if (isset($parsedurl['host']) && !($parsedurl['host'] == @parse_url($home_url, PHP_URL_HOST))) {
            return $url;
        }

        //remove prev lang and edit params - from query string - reserve other params
        if (isset($parsedurl['query'])) {
            $params = explode('&', $parsedurl['query']);
            foreach ($params as $key => $param) {
                if (stripos($param, LANG_PARAM) === 0) {
                    unset($params[$key]);
                }
                if (stripos($param, EDIT_PARAM) === 0) {
                    unset($params[$key]);
                }
            }
        }
        // clean the query
        unset($parsedurl['query']);

        // remove the language from the url permalink (if in start of path, and is a defined language)
        $gluebackhome = false;
        $home_path = rtrim((String)@parse_url($home_url, PHP_URL_PATH), "/");
        if (isset($parsedurl['path'])) {
            tp_logger("home: $home_path " . $parsedurl['path'], 5);
        }
        if ($home_path && strpos($parsedurl['path'], $home_path) === 0) {
            tp_logger("homein!: $home_path", 5);
            $parsedurl['path'] = substr($parsedurl['path'], strlen($home_path));
            $gluebackhome = true;
        }
        if (isset($parsedurl['path']) && strlen($parsedurl['path']) > 2) {
            $secondslashpos = strpos($parsedurl['path'], "/", 1);
            if (!$secondslashpos) {
                $secondslashpos = strlen($parsedurl['path']);
            }
            $prevlang = substr($parsedurl['path'], 1, $secondslashpos - 1);
            if (transposh_consts::is_supported_language($prevlang)) {
                tp_logger("prevlang: " . $prevlang, 4);
                $parsedurl['path'] = substr($parsedurl['path'], $secondslashpos);
            }
        }

        // override the use only params - admin configured system to not touch permalinks
        if (!$enable_permalinks_rewrite) {
            $use_params_only = TRUE;
        }

        //$params ="";
        if ($is_edit) {
            $params['edit'] = EDIT_PARAM . '=1';
        }

        if ($use_params_only && $lang) {
            $params['lang'] = LANG_PARAM . "=$lang";
        } else {
            if ($lang) {
                if (!isset($parsedurl['path'])) {
                    $parsedurl['path'] = "/"; //wait for it
                }
                $parsedurl['path'] = "/" . $lang . $parsedurl['path'];
            }
        }
        if ($gluebackhome) {
            $parsedurl['path'] = $home_path . $parsedurl['path'];
        }

        // insert params to url
        if (isset($params) && $params) {
            $parsedurl['query'] = implode('&', $params);
            tp_logger($params, 4);
        }

        // more cleanups
        //$url = preg_replace("/&$/", "", $url);
        //$url = preg_replace("/\?$/", "", $url);
        //    $url = htmlentities($url, ENT_NOQUOTES);
        $url = transposh_utils::glue_url($parsedurl);
        tp_logger("new url: $url", 5);
        return $url;
    }

    public static function get_language_from_url($url, $home_url) {

        $parsedurl = @parse_url($url);

        //option 1, lanaguage is in the query ?lang=xx
        if (isset($parsedurl['query'])) {
            $params = explode('&', $parsedurl['query']);
            foreach ($params as $key => $param) {
                if (stripos($param, LANG_PARAM) === 0) {
                    $langa = explode("=", $params[$key]);
                    if (transposh_consts::is_supported_language($langa[1])) {
                        return ($langa[1]);
                    }
                }
            }
        }

        // option 2, language is in permalink
        // cleanup lang identifier in permalinks
        // remove the language from the url permalink (if in start of path, and is a defined language)
        $home_path = rtrim((string)@parse_url($home_url, PHP_URL_PATH), "/");
//    logger ("home: $home_path ".$parsedurl['path'],5);
        if ($home_path && strpos($parsedurl['path'], $home_path) === 0) {
//        logger ("homein!: $home_path",5);
            $parsedurl['path'] = substr($parsedurl['path'], strlen($home_path));
//        $gluebackhome = true;
        }

        if (isset($parsedurl['path']) && strlen($parsedurl['path']) > 2) {
            $secondslashpos = strpos($parsedurl['path'], "/", 1);
            if (!$secondslashpos) {
                $secondslashpos = strlen($parsedurl['path']);
            }
            $prevlang = substr($parsedurl['path'], 1, $secondslashpos - 1);
            if (transposh_consts::is_supported_language($prevlang)) {
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
     * @return string combined url
     */
    public static function glue_url($parsed) {
        if (!is_array($parsed)) {
            return false;
        }

        $uri = isset($parsed['scheme']) ? $parsed['scheme'] . ':' . ((strtolower($parsed['scheme']) == 'mailto') ? '' : '//') : '';
        $uri .= isset($parsed['user']) ? $parsed['user'] . (isset($parsed['pass']) ? ':' . $parsed['pass'] : '') . '@' : '';
        $uri .= isset($parsed['host']) ? $parsed['host'] : '';
        $uri .= isset($parsed['port']) ? ':' . $parsed['port'] : '';

        if (isset($parsed['path'])) {
            $uri .= ( substr($parsed['path'], 0, 1) == '/') ?
                    $parsed['path'] : ((!empty($uri) ? '/' : '' ) . $parsed['path']);
        }

        $uri .= isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $uri .= isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return $uri;
    }

    /**
     * Encode a string as base 64 while avoiding characters which should be avoided
     * in uri, e.g. + is interpeted as a space.
     */
    public static function base64_url_encode($input) {
        return strtr(base64_encode($input), '+/=', '-_,');
    }

    /**
     * Decode a string previously decoded with base64_url_encode
     */
    public static function base64_url_decode($input) {
        return base64_decode(strtr($input, '-_,', '+/='));
    }

    /**
     * Function to translate a given url permalink to a target language
     * TODO - check params
     * @param string $href
     * @param string $home_url
     * @param string $target_language
     * @param callable $fetch_translation_func
     * @return string translated url permalink
     */
    public static function translate_url($href, $home_url, $target_language, callable $fetch_translation_func) {
        $url = '';
        $querypart = '';
        $fragment = '';
        // todo - check query part/fragment... sanitize
        if (strpos($href, '#') !== false) {
            list ($href, $fragment) = explode('#', $href);
            $fragment = '#' . $fragment;
        }
        if (strpos($href, '?') !== false) {
            list ($href, $querypart) = explode('?', $href);
            $querypart = '?' . $querypart;
        }
        $href = substr($href, strlen($home_url));
        $parts = explode('/', $href);
        foreach ($parts as $part) {
            if (!$part)
                continue;
            if (is_numeric($part)) {
                $translated_text = $part;
            } else {
                list($source, $translated_text) = call_user_func_array($fetch_translation_func, array($part, $target_language));
            }
            if ($translated_text) {
                $ttext = str_replace('-', '--', $translated_text);
                $ttext = str_replace(' ', '-', $ttext);
                $ttext = str_replace('?', '(qm)', $ttext);
                $url .= '/' . $ttext;
            } else {
                // now the same attempt with '-' replaced to ' '
                list($source, $translated_text) = call_user_func_array($fetch_translation_func, array(str_replace('-', ' ', $part), $target_language));
                //logger ($part. ' '.str_replace('-', ' ', $part).' '.$translated_text);
                if ($translated_text) {
                    $ttext = str_replace('-', '--', $translated_text);
                    $ttext = str_replace(' ', '-', $ttext);
                    $ttext = str_replace('?', '(qm)', $ttext);
                    $url .= '/' . $ttext;
                } else
                    $url .= '/' . $part;
            }
        }
        if (substr($href, strlen($href) - 1) == '/')
            $url .= '/';
        return $home_url . $url . $querypart . $fragment;
    }

    /**
     * From a given translated url, tries to get the original URL
     * @param string $href
     * @param string $home_url
     * @param string $target_language
     * @param callable $fetch_translation_func
     * @return string
     */
    public static function get_original_url($href, $home_url, $target_language, $fetch_translation_func) {
        $href = substr($href, strlen($home_url));
        $url = stripslashes(urldecode($href));
        $params = ($pos = strpos($url, '?')) ? substr($url, $pos) : '';
        $url = (($pos = strpos($url, '?')) ? substr($url, 0, $pos) : $url);
        $url2 = '';
        $parts = explode('/', $url);
        foreach ($parts as $part) {
            if (!$part)
                continue;
            // don't attempt for lang or numbers
            if ($part == $target_language || is_numeric($part)) {
                $url2 .= '/' . $part;
                continue;
            }

            // we attempt to find an original text
            $original_text = call_user_func_array($fetch_translation_func, array($part, $target_language));
            if (!$original_text) {
                // if the part has dashes we attempt to resolve original without them
                $part2 = str_replace('--', 'tmptmptmp', $part);
                $part2 = str_replace('-', ' ', $part2);
                $part2 = str_replace('(qm)', '?', $part2);
                $part2 = str_replace('tmptmptmp', '-', $part2);
                if ($part != $part2) {
                    $original_text = call_user_func_array($fetch_translation_func, array($part2, $target_language));
                }
            }
            // we'll add it if we have it
            if ($original_text) {
                $url2 .= '/' . strtolower(str_replace(' ', '-', $original_text));
            } else {
                $url2 .= '/' . $part;
            }
        }
        if ($url2 == '') {
            $url2 = '/';
        }
        // TODO: Consider sanitize_title_with_dashes
        // TODO : need to handle params....
        //tp_logger(substr($url,strlen($url)-1));
        //if (substr($url,strlen($url)-1) == '/') $url2 .= '/';
        //$url2 = rtrim($url2,'/');
        // tp_logger("h $home_url hr $href ur $url ur2 $url2");
        //$href = $this->home_url.$url2;
        if (substr($href, strlen($href) - 1) == '/') {
            $url2 .= '/';
        }
        $url2 = str_replace('//', '/', $url2);
        return $home_url . $url2 . $params;
    }

    /**
     * Checks that we may perform a rewrite on said url
     * @param string url to be checked $url
     * @param string the base url of the site $home_url
     * @return boolean if this is rewriteable 
     */
    public static function is_rewriteable_url($url, $home_url) {
        if (!is_array($home_url)) {
            if (strpos($home_url, ':')) {
                $home_url = substr($home_url, strpos($home_url, ':'));
            }
            return (stripos($url, $home_url) !== FALSE);
        } else {
            foreach ($home_url as $home) {
                if (strpos($home, ':')) {
                    $home = substr($home, strpos($home, ':'));
                }
                if (stripos($url, $home_url) !== FALSE) {
                    return true;
                }
            }
            return false;
        }
    }

    /**
     * Function to display a flag
     * @param string $path path to flag images
     * @param string $flag the flag (normally iso code)
     * @param string $language the name of the lanaguage
     * @param boolean $css using css code?
     * @return string Html with flag
     */
    public static function display_flag($path, $flag, $language, $css = false) {
        if (!$css) {
            return "<img src=\"$path/$flag.png\" title=\"$language\" alt=\"$language\"/>";
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
    public static function prefered_language($available_languages, $default_lang = "auto", $http_accept_language = "auto") {
        // if $http_accept_language was left out, read it from the HTTP-Header
        if ($http_accept_language == "auto") {
            $http_accept_language = transposh_utils::get_clean_server_var( 'HTTP_ACCEPT_LANGUAGE');
        }

        // standard  for HTTP_ACCEPT_LANGUAGE is defined under
        // http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
        // pattern to find is therefore something like this:
        //    1#( language-range [ ";" "q" "=" qvalue ] )
        // where:
        //    language-range  = ( ( 1*8ALPHA *( "-" 1*8ALPHA ) ) | "*" )
        //    qvalue         = ( "0" [ "." 0*3DIGIT ] )
        //            | ( "1" [ "." 0*3("0") ] )
        preg_match_all("/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" .
                "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i", $http_accept_language, $hits, PREG_SET_ORDER);

        // default language (in case of no hits) is the first in the array
        if ($default_lang == 'auto') {
            $bestlang = $available_languages[0];
        } else {
            $bestlang = $default_lang;
        }
        $bestqval = 0;

        foreach ($hits as $arr) {
            // read data from the array of this hit
            $langprefix = strtolower($arr[1]);
            if (!empty($arr[3])) {
                $langrange = strtolower($arr[3]);
                $language = $langprefix . "-" . $langrange;
            } else {
                $language = $langprefix;
            }
            $qvalue = 1.0;
            if (!empty($arr[5])) {
                $qvalue = floatval($arr[5]);
            }

            // find q-maximal language
            if (in_array($language, $available_languages) && ($qvalue > $bestqval)) {
                $bestlang = $language;
                $bestqval = $qvalue;
            }
            // if no direct hit, try the prefix only but decrease q-value by 10% (as http_negotiate_language does)
            else if (in_array($langprefix, $available_languages) && (($qvalue * 0.9) > $bestqval)) { // CHECK!
                $bestlang = $langprefix;
                $bestqval = $qvalue * 0.9;
            }
        }
        return $bestlang;
    }

    public static function language_from_country($available_languages, $country, $default_lang = "auto") {
        if ($default_lang == 'auto') {
            $bestlang = $available_languages[0];
        } else {
            $bestlang = $default_lang;
        }
        if (isset(transposh_consts::get_country_mapping()[strtolower($country)])) {
            $lang = transposh_consts::get_country_mapping()[strtolower($country)];
            if (strpos($lang, ',') !== false) {
                $langs = explode(",", $lang);
                foreach ($langs as $lang) {
                    if (in_array($lang, $available_languages)) {
                        return $lang;
                    }
                }
            }
        } else {
            $lang = strtolower($country); // those are the countries that have equal languages and names - (de, fr, etc)
        }
        if (in_array($lang, $available_languages)) {
            return $lang;
        }

        return $bestlang;
    }

    public static function is_bot() {
        return preg_match("#(bot|yandex|validator|google|jeeves|spider|crawler|slurp)#si", transposh_utils::get_clean_server_var( 'HTTP_USER_AGENT'));
    }

    public static function allow_cors() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: X-Requested-With');
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * Cleans stray locale markings
     * @param string $input
     * @return string
     */
    public static function clean_breakers($input) {
        return str_replace(array(TP_GTXT_BRK, TP_GTXT_IBRK, TP_GTXT_BRK_CLOSER, TP_GTXT_IBRK_CLOSER), '', $input);
    }

    /**
     * Returns the wordpress user by a given "by", if its an IP, just return it
     * @param string $by
     * @return string
     */
    public static function wordpress_user_by_by($by) {
        if (strpos($by, '.') === false && strpos($by, ':') === false && is_numeric($by)) {
            $user_info = get_userdata($by);
            $by = $user_info->user_login;
        }
        return $by;
    }
    /**
     * Return a server var, because of the 15 years old filter_input bug.
     * @param String $var
     * @return string
     */
    public static function get_clean_server_var($var) {
        $ret = filter_input(INPUT_SERVER, $var);
        if (!$ret && isset($_SERVER[$var])) {
            $ret = $_SERVER[$var];
        }
        return $ret;
    }

}
