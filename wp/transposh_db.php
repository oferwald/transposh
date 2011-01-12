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
 * Contains db realated function which are likely to be specific for each environment.
 * This implementation for use with mysql within wordpress
 *
 */
//
//Constants
//
//Table name in database for storing translations
define('TRANSLATIONS_TABLE', 'translations');
define('TRANSLATIONS_LOG', 'translations_log');

//Database version
define('DB_VERSION', '1.05');

//Constant used as key in options database
define('TRANSPOSH_DB_VERSION', "transposh_db_version");

class transposh_database {

    /** @var transposh_plugin father class */
    private $transposh;
    /** @var array holds prefetched translations */
    private $translations;
    /** @var string translation table name */
    private $translation_table;
    /** @var string translation log table name */
    private $translation_log_table;

    /**
     * constructor of class, PHP4 compatible construction for backward compatibility
     */
    function transposh_database(&$transposh) {
        $this->transposh = &$transposh;
        $this->translation_table = $GLOBALS['wpdb']->prefix . TRANSLATIONS_TABLE;
        $this->translation_log_table = $GLOBALS['wpdb']->prefix . TRANSLATIONS_LOG;
    }

    /**
     * Function to return a value from memory cache
     * @param string $original string we want translated
     * @param string $lang language we want it translated to
     * @return mixed array with translation or false on cache miss
     */
    function cache_fetch($original, $lang) {
        if (!TP_ENABLE_CACHE) return false;
        $cached = false;
        $key = $lang . '_' . $original;
        if (function_exists('apc_fetch')) {
            $cached = apc_fetch($key, $rc);
            if ($rc === false) return false;
            logger('apc', 5);
        } elseif (function_exists('xcache_get')) {
            $rc = xcache_isset($key);
            if ($rc === false) return false;
            $cached = xcache_get($key);
            logger('xcache', 5);
        } elseif (function_exists('eaccelerator_get')) {
            $cached = eaccelerator_get($key);
            if ($cached === null) return false;
            //TODO - unfortunantly null storing does not work here..
            logger('eaccelerator', 5);
        }
        logger("Cache fetched: $original => $cached", 4);
        if ($cached !== null && $cached !== false)
                $cached = explode('_', $cached, 2);
        return $cached;
    }

    /**
     * Function to store translation in memory cache
     * @param string $original
     * @param string $lang
     * @param array $translated
     * @param int $ttl time to live in the cache
     * @return boolean true if stored successfully
     */
    function cache_store($original, $lang, $translated, $ttl) {
        if (!TP_ENABLE_CACHE) return false;
        $key = $lang . '_' . $original;
        if ($translated !== null) $translated = implode('_', $translated);
        $rc = false;
        if (function_exists('apc_store')) {
            $rc = apc_store($key, $translated, $ttl);
        } elseif (function_exists('xcache_set')) {
            $rc = xcache_set($key, $translated, $ttl);
        } elseif (function_exists('eaccelerator_put')) {
            $rc = eaccelerator_put($key, $translated, $ttl);
        }

        if ($rc) {
            logger("Stored in cache: $original => {$translated}", 3);
        } else {
            logger("Didn't cache: $original => {$translated}", 3);
        }
        return $rc;
    }

    /**
     * Remove a value from memory cache
     * @param string $original
     * @param string $lang
     */
    function cache_delete($original, $lang) {
        if (!TP_ENABLE_CACHE) return;
        $key = $lang . '_' . $original;
        if (function_exists('apc_delete')) {
            apc_delete($key);
        } elseif (function_exists('xcache_unset')) {
            xcache_unset($key);
        } elseif (function_exists('eaccelerator_rm')) {
            eaccelerator_rm($key);
        }
    }

    /**
     * Clean the memory cache
     */
    function cache_clean() {
        if (!TP_ENABLE_CACHE) return;
        if (function_exists('apc_clear_cache')) {
            apc_clear_cache('user');
        } elseif (function_exists('xcache_unset_by_prefix')) {
            xcache_unset_by_prefix();
        }
        //TODO - clean on eaccelerator is not so clean...
    }

    /**
     * Allow fetching of multiple translation requests from the database with a single query
     * @param array $originals keys hold the strings...
     * @param string $lang
     */
    function prefetch_translations($originals, $lang) {
        if (!$originals) return;
        logger($originals, 4);
        $where = '';
        foreach ($originals as $original => $truth) {
            $original = $GLOBALS['wpdb']->escape(html_entity_decode($original, ENT_NOQUOTES, 'UTF-8'));
            $cached = $this->cache_fetch($original, $lang);
            // if $cached is not false, there is something in the cache, so no need to prefetch
            if ($cached !== false) {
                continue;
            }
            $where .= ( ($where) ? ' OR ' : '') . "original = '$original'";
        }
        // make sure $lang is reasonable, unless someone is messing with us, it will be ok
        if (!($this->transposh->options->is_editable_language($lang))) return;

        // If we have nothing, we will do nothing
        if (!$where) return;
        $query = "SELECT original, translated, source FROM {$this->translation_table} WHERE ($where) and lang = '$lang' ";
        $rows = $GLOBALS['wpdb']->get_results($query, ARRAY_A);
        if (empty($rows)) return;
        // we are saving in the array and not directly to cache, because cache might not exist...
        foreach ($rows as $row) {
            $this->translations[$row['original']] = array($row['source'], stripslashes($row['translated']));
        }
        logger('prefetched: ' . count($this->translations), 5);
    }

    /**
     * Fetch translation from db or cache.
     * Returns An array that contains the translated string and it source.
     * Will return NULL if no translation is available.
     * @param string $original
     * @param string $lang
     * @return array list(source,translation)
     */
    function fetch_translation($original, $lang) {
        $translated = null;
        logger("Fetching for: $original-$lang", 4);
        //The original is saved in db in its escaped form
        $original = $GLOBALS['wpdb']->escape(html_entity_decode($original, ENT_NOQUOTES, 'UTF-8'));
        // first we look in the cache
        $cached = $this->cache_fetch($original, $lang);
        if ($cached !== false) {
            logger("Exit from cache: {$cached[0]} {$cached[1]}", 4);
            return $cached;
        }
        // then we look for a prefetch
        if (isset($this->translations[$original])) {
            $translated = $this->translations[$original];
            logger("prefetch result for $original >>> {$this->translations[$original][0]} ({$this->translations[$original][1]})", 3);
        } else {
            // make sure $lang is reasonable, unless someone is messing with us, it will be ok
            if (!($this->transposh->options->is_editable_language($lang)))
                    return;
            $query = "SELECT * FROM {$this->translation_table} WHERE original = '$original' and lang = '$lang' ";
            $row = $GLOBALS['wpdb']->get_row($query);

            if ($row !== null) {
                $translated_text = stripslashes($row->translated);
                $translated = array($row->source, $translated_text);
                logger("db result for $original >>> $translated_text ($lang) ({$row->source})", 3);
            }
        }
        // we can store the result in the cache (or the fact we don't have one)
        $this->cache_store($original, $lang, $translated, TP_CACHE_TTL);

        return $translated;
    }

    /**
     * Fetch original from db or cache.
     * Returns the original for a given translation.
     * Will return NULL if no translation is available.
     * @param string $translation
     * @param string $lang
     * @return string $original
     */
    function fetch_original($translation, $lang) {
        $original = null;
        logger("Enter: $translation", 4);

        // The translation is saved in db in its escaped form
        $translation = $GLOBALS['wpdb']->escape(html_entity_decode($translation, ENT_NOQUOTES, 'UTF-8'));
        // The translation might be cached (notice the additional postfix)
        list($rev, $cached) = $this->cache_fetch('R_' . $translation, $lang);
        if ($rev == 'r') {
            logger("Exit from cache: $translation $cached", 4);
            return $cached;
        }
        // lang
        // FIXME - no prefetching for originals yet...
        if ($this->translations[$translation]) {
            $original = $this->translations[$translation];
            logger("prefetch result for $translation >>> {$this->translations[$translation][0]} ({$this->translations[$translation][1]})", 3);
        } else {
            $query = "SELECT * FROM {$this->translation_table} WHERE translated = '$translation' and lang = '$lang' ";
            $row = $GLOBALS['wpdb']->get_row($query);

            if ($row !== null) {
                $original = stripslashes($row->original);
                logger("db result for $translation >>> $original ($lang) ({$row->source})", 4);
            }
        }

        // we can store the result in the cache (or the fact we don't have one)
        $this->cache_store('R_' . $translation, $lang, array('r', $original), TP_CACHE_TTL);

        logger("Exit: $translation/$original", 4);
        return $original;
    }

    /**
     * A new translation has been posted, update the translation database.
     * This has changed since we now accept multiple translations at once
     * This function accepts a new more "versatile" format
     * TODO - return some info?
     * @global <type> $user_ID - TODO
     */
    function update_translation() {

        $ref = getenv('HTTP_REFERER');
        $items = $_POST['items'];
        $lang = $_POST['ln0'];
        $source = $_POST['sr0'];
        // check params
        logger("Enter " . __FILE__ . " Params: $items, $lang, $ref", 5);
        if (!isset($items) || !isset($lang)) {
            logger("Enter " . __FILE__ . " missing Params: $items, $lang, $ref", 1);
            return;
        }

        //Check permissions, first the lanugage must be on the edit list. Then either the user
        //is a translator or automatic translation if it is enabled.
        // we must check that all sent languages are editable
        $all_editable = true;
        for ($i = 0; $i < $items; $i++) {
            if (isset($_POST["ln$i"])) {
                if (!$this->transposh->options->is_editable_language($_POST["ln$i"])) {
                    $all_editable = false;
                    break;
                }
            }
        }
        if (!($all_editable &&
                ($this->transposh->is_translator() || ($source > 0 && $this->transposh->options->get_enable_auto_translate())))) {
            logger("Unauthorized translation attempt " . $_SERVER['REMOTE_ADDR'], 1);
            header("HTTP/1.0 401 Unauthorized translation");
            exit;
        }

        //add our own custom header - so we will know that we got here
        header("Transposh: v-" . TRANSPOSH_PLUGIN_VER . " db_version-" . DB_VERSION);

        // translation log stuff
        global $user_ID;
        get_currentuserinfo();

        // log either the user ID or his IP
        if ('' == $user_ID) {
            $loguser = $_SERVER['REMOTE_ADDR'];
        } else {
            $loguser = $user_ID;
        }

        // reset values (for good code style)
        $values = '';
        $delvalues = '';
        $logvalues = '';
        $backup_immidiate_possible = false;
        $firstitem = true;
        $alreadybatched = array();
        // We are now processing all posted items
        for ($i = 0; $i < $items; $i++) {
            if (isset($_POST["tk$i"])) {
                $original = transposh_utils::base64_url_decode($_POST["tk$i"]);
                // The original content is encoded as base64 before it is sent (i.e. token), after we
                // decode it should just the same after it was parsed.
                $original = $GLOBALS['wpdb']->escape(html_entity_decode($original, ENT_NOQUOTES, 'UTF-8'));
            }
            if (isset($_POST["tr$i"])) {
                $translation = $_POST["tr$i"];
                // Decode & remove already escaped character to avoid double escaping
                $translation = $GLOBALS['wpdb']->escape(htmlspecialchars(stripslashes(urldecode($translation))));
            }
            if (isset($_POST["ln$i"])) {
                $lang = $_POST["ln$i"];
            }
            if (isset($_POST["sr$i"])) {
                $source = $_POST["sr$i"];
            }

            // we attempt to avoid
            if (isset($alreadybatched[$original . '---' . $lang])) {
                logger("Warning same item appeared twice in batch: $original $lang", 1);
                continue;
            }
            $alreadybatched[$original . '---' . $lang] = true;
            // should we backup? - yes on any human translation
            if ($source == 0) $backup_immidiate_possible = true;

            //Here we check we are not redoing stuff
            list($old_source, $translated_text) = $this->fetch_translation($original, $lang);
            if ($translated_text) {
                if ($source > 0) {
                    logger("Warning auto-translation for already translated: $original $lang", 1);
                    continue;
                    //return; // too harsh, we just need to get to the next in for
                }
                if ($translation == $GLOBALS['wpdb']->escape(htmlspecialchars(stripslashes(urldecode($translated_text)))) && $old_source == $source) {
                    logger("Warning attempt to retranslate with same text: $original, $translation", 1);
                    continue;
                    //return; // too harsh, we just need to get to the next in for
                }
            }
            // Setting the values string for the database (notice how concatanation is handled)
            $values .= ( $firstitem ? '' : ', ') . "('" . $original . "','" . $translation . "','" . $lang . "','" . $source . "')";
            $delvalues .= ( $firstitem ? '' : ' OR ') . "(original ='$original' AND lang='$lang')";
            // Setting the transaction log records
            $logvalues .= ( $firstitem ? '' : ', ') . "('" . $original . "','" . $translation . "','" . $lang . "','" . $loguser . "','" . $source . "')";

            // If we have caching - we remove previous entry from cache
            $this->cache_delete($original, $lang);
            $firstitem = false;
            // TODO - maybe store value here?
        }

        // avoid empty database work
        if (!$values) return;
        // perform insertion to the database, with one query :)
        // since we no longer have a primary key, replacement made no sense
        /* $update = "REPLACE INTO ".$GLOBALS['wpdb']->prefix . TRANSLATIONS_TABLE." (original, translated, lang, source)
          VALUES $values"; */
        //so we'll delete all values and insert them...
        $update = "DELETE FROM " . $this->translation_table . " WHERE $delvalues";
        logger($update, 3);
        $result = $GLOBALS['wpdb']->query($update);
        $update = "INSERT INTO " . $this->translation_table . " (original, translated, lang, source) VALUES $values";
        logger($update, 3);
        $result = $GLOBALS['wpdb']->query($update);

        // if the insertion worked, we will update the transaction log
        if ($result !== FALSE) {
            $log = "INSERT INTO " . $this->translation_log_table . " (original, translated, lang, translated_by, source) " .
                    "VALUES $logvalues";
            $result = $GLOBALS['wpdb']->query($log);
            logger("Inserted to db '$values'", 3);
        } else {
            logger(mysql_error(), 0);
            logger("Error !!! failed to insert to db $original , $translation, $lang,", 0);
            header("HTTP/1.0 404 Failed to update language database " . mysql_error());
        }

        // if its a human translation we will call the action, this takes the assumption of a single human translation in
        // a function call, which should probably be verified (FIXME move up?)
        if ($source == 0) {
            do_action('transposh_human_translation', $translation, $original, $lang);
        }

        // TODO: move this to an action
        // Should we backup now?
        if ($backup_immidiate_possible && $this->transposh->options->get_transposh_backup_schedule() == 2) {
            $this->transposh->run_backup();
        }
        // this is a termination for the ajax sequence
        exit;
    }

    /*
     * Get translation history for some translation.
     */

    function get_translation_history($token, $lang) {

        $ref = getenv('HTTP_REFERER');
        $original = transposh_utils::base64_url_decode($token);
        logger("Inside history for $original ($token)", 4);

        // check params
        logger("Enter " . __FILE__ . " Params: $original , $lang, $ref", 3);
        if (!isset($original) || !isset($lang)) {
            logger("Enter " . __FILE__ . " missing params: $original, $lang," . $ref, 0);
            return;
        }
        logger("Passed check for $lang", 4);

        // Check permissions, first the lanugage must be on the edit list. Then either the user
        // is a translator or automatic translation if it is enabled.
        if (!($this->transposh->options->is_editable_language($lang) && $this->transposh->is_translator())) {
            logger("Unauthorized history request " . $_SERVER['REMOTE_ADDR'], 1);
            header('HTTP/1.0 401 Unauthorized history');
            exit;
        }
        logger('Passed check for editable and translator', 4);

        // The original content is encoded as base64 before it is sent (i.e. token), after we
        // decode it should just the same after it was parsed.
        $original = $GLOBALS['wpdb']->escape(html_entity_decode($original, ENT_NOQUOTES, 'UTF-8'));

        // add our own custom header - so we will know that we got here
        header('Transposh: v-' . TRANSPOSH_PLUGIN_VER . ' db_version-' . DB_VERSION);

        $query = "SELECT translated, translated_by, timestamp, source, user_login " .
                "FROM {$this->translation_log_table} " .
                "LEFT JOIN {$GLOBALS['wpdb']->prefix}users ON translated_by = {$GLOBALS['wpdb']->prefix}users.id " .
                "WHERE original='$original' AND lang='$lang' " .
                "ORDER BY timestamp DESC";
        logger("query is $query");

        $rows = $GLOBALS['wpdb']->get_results($query);
        for ($i = 0; $i < count($rows); $i++) {
            if (($rows[$i]->translated_by == $_SERVER['REMOTE_ADDR'] && $rows[$i]->source == '0') || (is_user_logged_in() && current_user_can(TRANSLATOR)) || current_user_can('manage_options')) {
                $rows[$i]->can_delete = true;
            }
        }
        // sending as json
        // CHECK!!! header("Content-type: application/json");
        echo json_encode($rows);
        exit;
    }

    /**
     * Delete a specific translation history from the logs
     * @param string $token
     * @param string $lang
     * @param string $timestamp
     */
    function del_translation_history($token, $lang, $timestamp) {
        $original = transposh_utils::base64_url_decode($token);
        $original = $GLOBALS['wpdb']->escape(html_entity_decode($original, ENT_NOQUOTES, 'UTF-8'));
        $lang = $GLOBALS['wpdb']->escape($lang);
        $timestamp = $GLOBALS['wpdb']->escape($timestamp);
        header('Transposh: v-' . TRANSPOSH_PLUGIN_VER . ' db_version-' . DB_VERSION);
        $query = "SELECT translated, translated_by, timestamp, source " .
                "FROM {$this->translation_log_table} " .
                "WHERE original='$original' AND lang='$lang' AND timestamp='$timestamp' " .
                "ORDER BY timestamp DESC";
        $rows = $GLOBALS['wpdb']->get_results($query);
        logger($query, 3);
        // We only delete if we found something to delete and it is allowed to delete it (user either did that - by ip, has the translator role or is an admin)
        if (!empty($rows) && (($rows[0]->translated_by == $_SERVER['REMOTE_ADDR'] && $rows[0]->source == '0') || (is_user_logged_in() && current_user_can(TRANSLATOR)) || current_user_can('manage_options'))) {
            // delete faulty record
            $query = "DELETE " .
                    "FROM {$this->translation_log_table} " .
                    "WHERE original='$original' AND lang='$lang' AND timestamp='$timestamp'";
            $GLOBALS['wpdb']->query($query);
            logger($query, 3);
            // retrieve last translation
            $query = "SELECT translated, source " .
                    "FROM {$this->translation_log_table} " .
                    "WHERE original='$original' AND lang='$lang' " .
                    "ORDER BY timestamp DESC";
            $rows = $GLOBALS['wpdb']->get_results($query);
            logger($query, 3);

            // delete and revert to last in database
            $delvalues = "(original ='$original' AND lang='$lang')";
            $update = "DELETE FROM " . $this->translation_table . " WHERE $delvalues";
            $this->cache_delete($original, $lang);
            logger($update, 3);
            $result = $GLOBALS['wpdb']->query($update);
            if (!empty($rows)) {
                $translated = $GLOBALS['wpdb']->escape($rows[0]->translated);
                $values = "('" . $original . "','" . $translated . "','" . $lang . "','" . $rows[0]->source . "')";
                $update = "INSERT INTO " . $this->translation_table . " (original, translated, lang, source) VALUES $values";
                logger($update, 3);
                $result = $GLOBALS['wpdb']->query($update);
            } else {
                // there is nothing to revert to...
                $rows[0]->translated = '';
            }
            echo json_encode($rows[0]);
        } else {
            echo json_encode(false);
        }
        exit;
    }

    /**
     * Get translation alternatives for some translation.
     * @param string $token
     */
    function get_translation_alt($token) {

        //$ref = getenv('HTTP_REFERER');
        $original = transposh_utils::base64_url_decode($token);
        logger("Inside alt for $original ($token)", 4);

        if (!isset($original)) {
            exit;
        }

        // Check permissions
        if (!($this->transposh->is_translator())) {
            logger("Unauthorized alt request " . $_SERVER['REMOTE_ADDR'], 1);
            header('HTTP/1.0 401 Unauthorized alt request');
            exit;
        }
        logger('Passed check for editable and translator', 4);

        // The original content is encoded as base64 before it is sent (i.e. token), after we
        // decode it should just the same after it was parsed.
        // TODO - check if needed later
        $original = $GLOBALS['wpdb']->escape(html_entity_decode($original, ENT_NOQUOTES, 'UTF-8'));

        // add our own custom header - so we will know that we got here
        // TODO - move to ajax file?
        header('Transposh: v-' . TRANSPOSH_PLUGIN_VER . ' db_version-' . DB_VERSION);

        $query = "SELECT translated, lang " .
                "FROM {$this->translation_table} " .
                "WHERE original='$original' AND source=0 " .
                "ORDER BY lang";
        logger("query is $query");
        $rows = $GLOBALS['wpdb']->get_results($query);

        echo json_encode($rows);
        exit;
    }

    /**
     * Function to return human translations history
     * @param string $date - either null for all or a date to get terms after
     * @return array List of rows
     */
    function get_all_human_translation_history($date ="null", $limit = "") {
        if ($date != "null")
                $dateterm = "and UNIX_TIMESTAMP(timestamp) > $date";
        if ($limit) $limitterm = "LIMIT $limit";
        $query = "SELECT original, lang, translated, translated_by, UNIX_TIMESTAMP(timestamp) as timestamp " .
                "FROM {$this->translation_log_table} " .
                "WHERE source= 0 $dateterm " .
                "ORDER BY timestamp ASC $limitterm";
        logger("query is $query");

        $rows = $GLOBALS['wpdb']->get_results($query);
        return $rows;
    }

    /*
     * Setup the translation database.
     */

    function setup_db() {
        logger("Enter");
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $installed_ver = get_option(TRANSPOSH_DB_VERSION);

        if ($installed_ver != DB_VERSION) {
            logger("Attempting to create table {$this->translation_table}", 0);
            // notice - keep every field on a new line or dbdelta fails
            $GLOBALS['wpdb']->query("ALTER TABLE {$this->translation_table} DROP PRIMARY KEY");
            $sql = "CREATE TABLE {$this->translation_table} (
                    original TEXT NOT NULL, 
                    lang CHAR(5) NOT NULL, 
                    translated TEXT, 
                    source TINYINT NOT NULL, 
                    KEY original (original(6),lang)
                    ) DEFAULT CHARSET=utf8 COLLATE=utf8_bin";

            dbDelta($sql);
            $GLOBALS['wpdb']->query("ALTER TABLE {$this->translation_table} CONVERT TO CHARSET utf8 COLLATE utf8_bin");

            logger("Attempting to create table {$this->translation_log_table}", 0);
            // notice - keep every field on a new line or dbdelta fails
            // this should be removed in a far future...
            $GLOBALS['wpdb']->query("ALTER TABLE {$this->translation_log_table} DROP PRIMARY KEY");
            $sql = "CREATE TABLE {$this->translation_log_table} (
                    original text NOT NULL, 
                    lang CHAR(5) NOT NULL, 
                    translated text, 
                    translated_by VARCHAR(15), 
                    source TINYINT NOT NULL, 
                    timestamp TIMESTAMP, 
                    KEY original (original(6),lang,timestamp)
                    ) DEFAULT CHARSET=utf8 COLLATE=utf8_bin";

            dbDelta($sql);
            $GLOBALS['wpdb']->query("ALTER TABLE {$this->translation_log_table} CONVERT TO CHARSET utf8 COLLATE utf8_bin");

            update_option(TRANSPOSH_DB_VERSION, DB_VERSION);
        }

        logger("Exit");
    }

    /**
     * Provides some stats about our database
     */
    function db_stats() {
        echo '<h4>' . __('Database stats', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
        $query = "SELECT count(*) as count FROM `{$this->translation_table}`";
        $rows = $GLOBALS['wpdb']->get_results($query);
        foreach ($rows as $row) {
            if ($row->count)
                    printf('<p>' . __('Total of <strong style="color:red">%s</strong> translated phrases.', TRANSPOSH_TEXT_DOMAIN) . '</p>', $row->count);
        }

        $query = "SELECT count(*) as count,lang FROM `{$this->translation_table}` WHERE source='0' GROUP BY `lang` ORDER BY `count` DESC LIMIT 3";
        $rows = $GLOBALS['wpdb']->get_results($query);
        foreach ($rows as $row) {
            if ($row->count)
                    printf('<p>' . __('<strong>%1s</strong> has <strong style="color:red">%2s</strong> human translated phrases.', TRANSPOSH_TEXT_DOMAIN) . '</p>', $row->lang, $row->count);
        }

        echo '<h4>' . __('Recent activity', TRANSPOSH_TEXT_DOMAIN) . '</h4>';
        $query = "SELECT * FROM `{$this->translation_log_table}` WHERE source='0' ORDER BY `timestamp` DESC LIMIT 3";
        $rows = $GLOBALS['wpdb']->get_results($query);
        foreach ($rows as $row) {
            $td = mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $row->timestamp);
            printf('<p>' . __('On <strong>%1s</strong><br/>user <strong>%2s</strong> translated<br/>"<strong>%3s</strong>"<br/>to <strong style="color:red">%4s</strong><br/>"<strong>%5s</strong>"', TRANSPOSH_TEXT_DOMAIN) . '</p>', $td, $row->translated_by, $row->original, $row->lang, $row->translated);
        }
    }

    /**
     * This function returns a list of candidate phrases which might contain a requested translated string
     * @param string $term The search term
     * @param string $language The language being searched
     * @return array Original phrases in which $term appears
     */
    function get_orignal_phrases_for_search_term($term, $language) {
        $n = '%';
        $term = $GLOBALS['wpdb']->escape(html_entity_decode($term, ENT_NOQUOTES, 'UTF-8'));
        $language = $GLOBALS['wpdb']->escape($language);
        $query = "SELECT original" .
                " FROM `{$this->translation_table}`" .
                " WHERE `lang` = '$language'" .
                " AND `translated` LIKE '{$n}{$term}{$n}'";
        //TODO wait for feedbacks to see if we should put a limit here.
        logger($query, 4);
        $result = array();
        if (strlen($term) < 3) return $result;
        $rows = $GLOBALS['wpdb']->get_results($query);

        foreach ($rows as $row) {
            $addme = true;
            // now lets use the a-priori for reduction
            // two possibilities for reduction, new is included in old, or some old includes this new
            foreach ($result as $k => $r) {
                // if our original is included in a string in the result, that is no longer needed...
                if (stripos($r, $row->original) !== false) {
                    unset($result[$k]);
                }
                // if the other way around is true, we won't have to add it
                if (stripos($row->original, $r) !== false) {
                    $addme = false;
                }
            }
            if ($addme) $result[] = $row->original;
        }

        return $result;
    }

    /**
     * This function removes translations and translation logs from the database, only
     * when the last translation is automated
     * @param int $days
     */
    function cleanup($days = 0) {
        $days = intval($days); // some security
        $cleanup = 'DELETE ' . $this->translation_table . ' ,' . $this->translation_log_table .
                ' FROM ' . $this->translation_table .
                ' INNER JOIN ' . $this->translation_log_table .
                ' ON ' . $this->translation_table . '.original = ' . $this->translation_log_table . '.original' .
                ' AND ' . $this->translation_table . '.lang = ' . $this->translation_log_table . '.lang' .
                ' WHERE ' . $this->translation_table . '.source > 0' .
                " AND timestamp < SUBDATE(NOW(),$days)";
        $result = $GLOBALS['wpdb']->query($cleanup);
        logger($cleanup, 4);
        // clean up cache so that results will actually show
        $this->cache_clean();
        exit;
    }

    function db_maint() {
        // clean duplicate log entries
        $dedup = 'SELECT * , count( * )' .
                ' FROM ' . $this->translation_log_table .
                ' GROUP BY `original` , `lang` , `translated` , `translated_by` , `timestamp` , `source`' .
                ' HAVING count( * ) >1';
        $rows = $GLOBALS['wpdb']->get_results($dedup);
        logger($dedup, 3);
        foreach ($rows as $row) {
            $row->original = $GLOBALS['wpdb']->escape($row->original);
            $row->translated = $GLOBALS['wpdb']->escape($row->translated);
            $row->translated_by = $GLOBALS['wpdb']->escape($row->translated_by);
            $delvalues = "(original ='{$row->original}' AND lang='{$row->lang}' AND translated='{$row->translated}'" .
                    " AND translated_by='{$row->translated_by}' AND timestamp='{$row->timestamp}' AND source='{$row->source}')";
            $update = "DELETE FROM " . $this->translation_log_table . " WHERE $delvalues";
            logger($update, 3);
            $result = $GLOBALS['wpdb']->query($update);
            $values = "('{$row->original}','{$row->lang}','{$row->translated}','$row->translated_by','$row->timestamp','$row->source')";
            $update = "INSERT INTO " . $this->translation_log_table . " (original, lang, translated, translated_by, timestamp, source) VALUES $values";
            logger($update, 3);
            $result = $GLOBALS['wpdb']->query($update);
            $this->cache_delete($row->original, $row->lang);
        }
        //$this->cache_clean();
        // clean autotranslate entries post dating a human translation
        $autojunk = 'SELECT w2 . *' .
                ' FROM ' . $this->translation_log_table . ' AS w1' .
                ' INNER JOIN ' . $this->translation_log_table . ' AS w2' .
                ' ON w1.original = w2.original' .
                ' AND w1.lang = w2.lang' .
                ' AND w1.source =0' .
                ' AND w2.source >0' .
                ' AND w1.timestamp < w2.timestamp';
        $rows = $GLOBALS['wpdb']->get_results($autojunk);
        logger($autojunk, 3);
        foreach ($rows as $row) {
            $row->original = $GLOBALS['wpdb']->escape($row->original);
            $row->translated = $GLOBALS['wpdb']->escape($row->translated);
            $row->translated_by = $GLOBALS['wpdb']->escape($row->translated_by);
            $delvalues = "(original ='{$row->original}' AND lang='{$row->lang}' AND translated='{$row->translated}'" .
                    " AND translated_by='{$row->translated_by}' AND timestamp='{$row->timestamp}' AND source='{$row->source}')";
            $update = "DELETE FROM " . $this->translation_log_table . " WHERE $delvalues";
            $result = $GLOBALS['wpdb']->query($update);
            logger($update, 3);
            $this->cache_delete($row->original, $row->lang);
        }
        // check for discrepencies (last translation in log does not equal current translation)
        // TODO
        /* SELECT *
          FROM `wp_translations` AS w1
          INNER JOIN wp_translations_log AS w2 ON w1.original = w2.original
          AND w1.lang = w2.lang
          LEFT OUTER JOIN wp_translations_log AS w3 ON ( w2.original = w3.original
          AND w2.lang = w3.lang
          AND w2.timestamp < w3.timestamp )
          WHERE w3.original IS NULL
          AND w1.translated != w2.translated */

        // check for transalations that have no log representation
        // TODO
        /* SELECT *
          FROM   wp_translations
          LEFT OUTER JOIN wp_translations_log
          ON (wp_translations.original = wp_translations_log.original AND wp_translations.lang = wp_translations_log.lang)
          WHERE wp_translations_log.original IS NULL
         */

        // if we have multiple translations for the same item, we will delete them and revert to latest from log
        // TODO - handle this, but not now, case sensativity issues
        /* $duptrans = 'SELECT *' .
          ' FROM ' . $this->translation_table .
          ' GROUP BY original, lang' .
          ' HAVING count( * ) >1';
          $rows = $GLOBALS['wpdb']->get_results($duptrans);
          logger($duptrans, 3);
          foreach ($rows as $row) {
          $row->original = $GLOBALS['wpdb']->escape($row->original);
          $delvalues = "(original ='{$row->original}' AND lang='{$row->lang}')";
          $row->original = $GLOBALS['wpdb']->escape($row->original);
          $update = "DELETE FROM " . $this->translation_log_table . " WHERE $delvalues";
          logger($update, 3);
          // $result = $GLOBALS['wpdb']->query($update);
          } */
        $this->cache_clean();
        exit;
    }

    function restore_translation($original, $lang, $translation, $by, $timestamp) {
        // TODO in future
        // if there is a newer human translation, just ignore this
        // if there is a newer auto translation, remove it
        // update it
        // TODO - change this part to use the update_translation function
        $original = $GLOBALS['wpdb']->escape(html_entity_decode($original, ENT_NOQUOTES, 'UTF-8'));
        $translation = $GLOBALS['wpdb']->escape(html_entity_decode($translation, ENT_NOQUOTES, 'UTF-8'));
        $source = 0;
        // for now - just update it...
        $values .= "('" . $original . "','" . $translation . "','" . $lang . "','" . $source . "')";
        $delvalues .= "(original ='$original' AND lang='$lang')";
        // Setting the transaction log records
        $logvalues .= "('" . $original . "','" . $translation . "','" . $lang . "','" . $by . "',FROM_UNIXTIME(" . $timestamp . "),'" . $source . "')";

        $update = "DELETE FROM " . $this->translation_table . " WHERE $delvalues";
        logger($update, 3);
        $result = $GLOBALS['wpdb']->query($update);
        $update = "INSERT INTO " . $this->translation_table . " (original, translated, lang, source) VALUES $values";
        logger($update, 3);
        $result = $GLOBALS['wpdb']->query($update);

        if ($result !== FALSE) {
            // update the transaction log too
            $log = "INSERT INTO " . $this->translation_log_table . " (original, translated, lang, translated_by, timestamp, source) " .
                    "VALUES $logvalues";
            logger($log, 3);
            $result = $GLOBALS['wpdb']->query($log);
        } else {
            logger(mysql_error(), 0);
            logger("Error !!! failed to insert to db $original , $translation, $lang,", 0);
            header("HTTP/1.0 404 Failed to update language database " . mysql_error());
        }
    }

}

?>