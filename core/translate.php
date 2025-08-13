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
 * Contains translation scraping functions
 *
 */
require_once("constants.php");
require_once("logging.php");

/**
 * This is a static class to reduce chance of namespace collisions with other plugins
 */
class transposh_translate
{
    /******************************************
     * Proxied Yandex translate suggestions
     *****************************************/
    public static function get_yandex_translation($tl, $sl, $q)
    {
        $sid = '';
        $timestamp = 0;
        if (get_option(TRANSPOSH_OPTIONS_YANDEXPROXY, array())) {
            list($sid, $timestamp) = get_option(TRANSPOSH_OPTIONS_YANDEXPROXY, array());
        }
        tp_logger("yandex sid $sid", 1);
        if ($sid == '') {
            if ((time() - TRANSPOSH_YANDEXPROXY_DELAY > $timestamp)) {
                // attempt key refresh on error
                $url = 'https://translate.yandex.com/';
                tp_logger($url, 1);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_REFERER, "https://translate.yandex.com/");
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $UA = transposh_utils::get_clean_server_var("HTTP_USER_AGENT", FILTER_DEFAULT);
                curl_setopt($ch, CURLOPT_USERAGENT, $UA);
                $output = curl_exec($ch);
                $sidpos = strpos($output, "SID: '") + 6;
                $newout = substr($output, $sidpos);
                $sid = substr($newout, 0, strpos($newout, "',"));
                tp_logger("new sid: $sid", 1);
                // fix SID "encryption"
                $sid = implode(".", array_map(
                    function ($substring) {
                        return implode('', array_reverse(str_split($substring)));
                    },
                    explode(".", $sid)
                ));
                tp_logger("fixed sid: $sid", 1);

                if ($output === false) {
                    tp_logger('Curl error: ' . curl_error($ch));
                    return false;
                }
                //return false;
                update_option(TRANSPOSH_OPTIONS_YANDEXPROXY, array($sid, time()));
                curl_close($ch);
            }
        }

        if (!$sid) {
            tp_logger('No SID, gotta bail:' . $timestamp, 1);
            return false;
        }

        $sourceadd = '';
        if ($sl) {
            $sourceadd = "&source_lang={$sl}";
        }

        $url = "https://translate.yandex.net/api/v1/tr.json/translate?id={$sid}-0-0&srv=tr-text" .
            $sourceadd .
            "&target_lang={$tl}&reason=auto&format=text&strategy=0&disable_cache=false&ajax=1" .
            // "&yu=". not needed
            "";
            // "&sprvk=d"; captcha data

        // POST data
        $q = urldecode($q);
        $postData = [
            'text' => $q,
            'options' => 4
        ];
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1); // Set method to POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData)); // Encode POST data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return response as string
        $UA = transposh_utils::get_clean_server_var("HTTP_USER_AGENT", FILTER_DEFAULT);
        curl_setopt($ch, CURLOPT_USERAGENT, $UA);
        curl_setopt($ch, CURLOPT_REFERER, "https://translate.yandex.com/");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: */*',
            'X-Retpath-Y: https://translate.yandex.com',
            'Origin: https://translate.yandex.com',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: cross-site',
            'TE: trailers'
        ]);

        $output = curl_exec($ch);
        curl_close($ch);
        tp_logger($output, 1);
        $jsonarr = json_decode($output);
        tp_logger($jsonarr, 3);
        if (!$jsonarr) {
            tp_logger('No JSON here, failing', 1);
            tp_logger($output, 3);
            return false;
        }
        if ($jsonarr->code != 200) {
            tp_logger('Some sort of error!', 1);
            tp_logger($output, 1);
            if ($jsonarr->code == 406 || $jsonarr->code == 405) { //invalid session
                update_option(TRANSPOSH_OPTIONS_YANDEXPROXY, array('', time()));
            }

            return false;
        }

        return $jsonarr->text;
    }

    /******************************************
     * Proxied Baidu translate suggestions
     ******************************************/
    public static function get_baidu_translation($tl, $sl, $q)
    {
        // URL for the request
        $url = 'https://fanyi.baidu.com/ait/text/translate';
        tp_logger("Baidu translate", 1);

        // JSON payload
        $q = urldecode($q);
        $data = [
            "query" => $q,
            "from" => "en", // BUG
            "to" => transposh_consts::get_engine_lang_code($tl,'u'),
            // "reference" => "",
            // "corpusIds" => [],
            // "needPhonetic" => false,
            // "domain" => "common"
        ];

        $jsonData = json_encode($data);

        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return response as string
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Skip SSL verification (not recommended for production)
        $UA = transposh_utils::get_clean_server_var("HTTP_USER_AGENT", FILTER_DEFAULT);
        curl_setopt($ch, CURLOPT_USERAGENT, $UA);

        // Set headers
        $headers = [
            'Accept: text/event-stream',
            'Accept-Language: en-US,en;q=0.5',
            'Accept-Encoding: gzip, deflate, br, zstd',
            'Content-Type: application/json',
            'Origin: https://fanyi.baidu.com',
            'Sec-Fetch-Dest: empty',
            'Sec-Fetch-Mode: cors',
            'Sec-Fetch-Site: same-origin',
            'Pragma: no-cache',
            'Cache-Control: no-cache'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo "cURL Error: " . curl_error($ch);
        } else {
            // Since it's an event stream, process line by line
            $lines = explode("\n", $response);
            foreach ($lines as $line) {
                if (strpos($line, 'data:') === 0) {
                    $json = substr($line, 5); // Remove "data: " prefix
                    $decoded = json_decode($json, true);
                    if ($decoded) {
                        if (isset($decoded['data']['event']) && $decoded['data']['event'] == 'Translating') {
                            return $decoded['data']['list'][0]['dst'];
                        }
                    }
                }
            }
        }
        return false;
    }

    /* helper function for Google Translate */
    private static function _bitwise_zfrs($a, $b)
    {
        if ($b == 0)
            return $a;
        return ($a >> $b) & ~(1 << (8 * PHP_INT_SIZE - 1) >> ($b - 1));
    }

    private static function hq($a, $chunk)
    {
        for ($offset = 0; $offset < strlen($chunk) - 2; $offset += 3) {
            $b = $chunk[$offset + 2];
            $b = ($b >= "a") ? ord($b) - 87 : intval($b);
            $b = ($chunk[$offset + 1] == "+") ? self::_bitwise_zfrs($a, $b) : $a << $b;
            $a = ($chunk[$offset] == "+") ? $a + $b & 4294967295 : $a ^ $b;
        }
        return $a;
    }

    /**
     * Hey googler, if you are reading this, it means that you are actually here, why won't we work together on this?
     */
    private static function iq(string $input, string $error): string
    {
        [$base, $key] = array_map('intval', explode('.', $error, 2));
        $value = $base;
        $inputLen = strlen($input);
        for ($i = 0; $i < $inputLen; $i++) {
            $value += ord($input[$i]);
            $value = self::hq($value, '+-a^+6');
        }
        $value = self::hq($value, '+-3^+b+-f');
        $value ^= $key;
        if ($value < 0) {
            $value = ($value & 0x7FFFFFFF) + 0x80000000;
        }
        $x = $value % 1E6;
        return "$x." . ($x ^ $base);
    }

    /******************************************
     * Proxied translation for Google Translate
     *****************************************/

    public static function get_google_translation($tl, $sl, $q)
    {
        if (get_option(TRANSPOSH_OPTIONS_GOOGLEPROXY, array())) {
            list($googlemethod, $timestamp) = get_option(TRANSPOSH_OPTIONS_GOOGLEPROXY, array());
            //$googlemethod = 0;
            //$timestamp = 0;
            tp_logger("Google method $googlemethod, " . date(DATE_RFC2822, $timestamp) . ", current:" . date(DATE_RFC2822, time()) . " Delay:" . TRANSPOSH_GOOGLEPROXY_DELAY, 1);
        } else {
            tp_logger("Google is clean", 1);
            $googlemethod = 0;
        }

        // we preserve the method, and will ignore lower methods for the given delay period
        if (isset($timestamp) && (time() - TRANSPOSH_GOOGLEPROXY_DELAY > $timestamp)) {
            delete_option(TRANSPOSH_OPTIONS_GOOGLEPROXY);
        }
        tp_logger('Google proxy initiated', 1);
        $qstr = '';
        $iqstr = '';
        if (is_array($q)) {
            foreach ($q as $v) {
                $qstr .= '&q=' . $v;
                $iqstr .= urldecode($v);
            }
        } else {
            $qstr = '&q=' . $q;
            $iqstr = urldecode($q);
        }

        // we avoid curling we had all results prehand
        $urls = array(
            'http://translate.google.com',
            'http://212.199.205.226',
            'http://74.125.195.138',
            'https://translate.googleapis.com');

        $attempt = 1;
        $failed = true;
        foreach ($urls as $gurl) {
            if ($googlemethod < $attempt && $failed) {
                $failed = false;
                tp_logger("Attempt: $attempt", 1);
                $url = $gurl . '/translate_a/t?client=te&v=1.0&tl=' . $tl . '&sl=' . $sl . '&tk=' . self::iq($iqstr, '406448.272554134');
                tp_logger($url, 3);
                tp_logger($q, 3);
                tp_logger($iqstr, 3);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                //must set agent for Google to respond with utf-8
                $UA = transposh_utils::get_clean_server_var("HTTP_USER_AGENT");
                tp_logger($UA, 1);
                curl_setopt($ch, CURLOPT_USERAGENT, $UA);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $qstr);
                // timeout is probably a good idea
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
                curl_setopt($ch, CURLOPT_TIMEOUT, 7);

                //if the attempt is 2 or more, we skip ipv6 and use an alternative user agent
                if ($attempt > 1) {
                    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                    curl_setopt($ch, CURLOPT_USERAGENT, transposh_utils::get_clean_server_var('HTTP_USER_AGENT'));
                }
                $output = curl_exec($ch);
                $info = curl_getinfo($ch);
                tp_logger('Curl code is: ' . $info['http_code'], 1);
                curl_close($ch);
                tp_logger($output, 3);
                if ($info['http_code'] != 200) {
                    tp_logger("method fail - $attempt", 1);
                    $failed = true;
                    update_option(TRANSPOSH_OPTIONS_GOOGLEPROXY, array($attempt, time()));
                }
                unset($info);
            }
            $attempt++;
        }

        // Maybe in the future we may attempt with a key
        if ($failed) {
            tp_logger('out of options, die for the day!', 1);
            return false;
        }

        if ($output === false) {
            tp_logger('Curl error: ' . curl_error($ch));
            return false;
        }

        tp_logger($output, 3);

        $jsonarr = json_decode($output);
        if (!$jsonarr) {
            tp_logger("google didn't return Proper JSON, lets try to recover", 2);
            $newout = str_replace(',,', ',', $output);
            tp_logger($newout);
            $jsonarr = json_decode($newout);
            if (!$jsonarr) {
                tp_logger('No JSON here, failing');
                tp_logger($output, 3);
                return false;
            }
        }
        tp_logger($jsonarr);
        if (is_array($jsonarr)) {
            if (is_array($jsonarr[0])) {
                foreach ($jsonarr as $val) {
                    // need to drill
                    while (is_array($val)) {
                        $val = $val[0];
                    }
                    $result[] = $val;
                }
            } else {
                // yes - it was all that was needed to fix the Google 2022 translation change
                $result = $jsonarr;
            }
        } else {
            $result[] = $jsonarr;
        }
        return $result;
    }

    /******************************************
     * Proxied translation for Bing translate
     *****************************************/

    public static function getBingTranslatorTokens() {
        if (get_option(TRANSPOSH_OPTIONS_BINGPROXY, array())) {
            list($tokens, $timestamp) = get_option(TRANSPOSH_OPTIONS_BINGPROXY, array());
            // If keys are still valid, return them
            if ((time() - TRANSPOSH_BINGPROXY_DELAY < $timestamp) && (!empty($tokens['IG']) && !empty($tokens['IID']) && !empty($tokens['key']) && !empty($tokens['token']))) {
                tp_logger("using saved Bing translator tokens", 1);
                return $tokens;
            }
        }
        tp_logger("getting new Bing translator tokens", 1);
        $url = "https://www.bing.com/translator";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $UA = transposh_utils::get_clean_server_var("HTTP_USER_AGENT", FILTER_DEFAULT);
        curl_setopt($ch, CURLOPT_USERAGENT, $UA);

        $response = curl_exec($ch);
        curl_close($ch);

        // Extract IG (Instance GUID) and token
        preg_match('/IG:"([a-zA-Z0-9_-]+)"/', $response, $ig_matches);
        preg_match('/data-iid="([a-zA-Z0-9._-]+)"/', $response, $iid_matches);
        preg_match('/params_AbusePreventionHelper\s*=\s*\[(\d+),\s*"([^"]+)",\s*\d+\]/', $response, $token_matches);

        $tokens = [
            'IG' => $ig_matches[1] ?? '',
            'IID' => $iid_matches[1] ?? '',
            'key' => $token_matches[1] ?? '',
            'token' => $token_matches[2] ?? ''
        ];
        update_option(TRANSPOSH_OPTIONS_BINGPROXY, array($tokens, time()));
        return $tokens;
    }

    public static function get_bing_translation($tl, $sl, $q)
    {
        $tokens = transposh_translate::getBingTranslatorTokens();
        if (empty($tokens['IG']) || empty($tokens['IID']) || empty($tokens['key']) || empty($tokens['token'])) {
            tp_logger("Error: Unable to retrieve necessary tokens.",1);
        }

        $url = "https://www.bing.com/ttranslatev3?isVertical=1&&IG={$tokens['IG']}&IID={$tokens['IID']}";
        $tl = transposh_consts::get_engine_lang_code($tl, 'b');
        tp_logger($tl);
        $postData = [
            'fromLang' => $sl,
            'text' => $q,
            'to' => $tl,
            'token' => $tokens['token'],
            'key' => $tokens['key']
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/x-www-form-urlencoded",
        ]);
        $UA = transposh_utils::get_clean_server_var("HTTP_USER_AGENT", FILTER_DEFAULT);
        curl_setopt($ch, CURLOPT_USERAGENT, $UA);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            tp_logger("Error: HTTP $httpCode received.");
        }

        $data = json_decode($response, true);
        tp_logger($data,1);
        if (isset($data[0]['translations'][0]['text'])) {
            return $data[0]['translations'][0]['text'];
        } else {
            //var_dump($data);
            tp_logger("Error: Unable to parse translation response.");
        }
        return false;
    }
}