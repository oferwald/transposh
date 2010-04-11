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
 * Contains db realated function which are likely to be specific for each environment.
 * This implementation for use with mysql within wordpress
 *
 */

//
//Constants
//

//Table name in database for storing translations
define("TRANSLATIONS_TABLE", "translations");
define("TRANSLATIONS_LOG", "translations_log");

//Database version
define("DB_VERSION", "1.04");

//Constant used as key in options database
define("TRANSPOSH_DB_VERSION", "transposh_db_version");

class transposh_database {
    /** @property transposh_plugin $transposh father class */
    private $transposh;
    private $translations;
//constructor of class, PHP4 compatible construction for backward compatibility
    function transposh_database(&$transposh) {
        $this->transposh = &$transposh;
    }

    function prefetch_translations($originals, $lang) {
        if (!$originals) return;
        foreach ($originals as $original) {
            $original = $GLOBALS['wpdb']->escape(html_entity_decode($original, ENT_NOQUOTES, 'UTF-8'));
            if(ENABLE_APC && function_exists('apc_fetch')) {
                $cached = apc_fetch($original .'___'. $lang, $rc);
                if($rc === TRUE) {
                    //        logger("Cached: $original", 3);
                    continue;
                }
            }
            $where .= (($where) ? ' OR ' :'')."original = '$original'";
        }
        if (!$where) return;
        $table_name = $GLOBALS['wpdb']->prefix . TRANSLATIONS_TABLE;
        $query = "SELECT original, translated, source FROM $table_name WHERE ($where) and lang = '$lang' ";
        $rows = $GLOBALS['wpdb']->get_results($query,ARRAY_A);
        if(empty($rows)) return;
        foreach ($rows as $row) {
            $this->translations[$row['original']] = array(stripslashes($row['translated']), $row['source']);
        }
        logger($this->translations, 5);
    }
    /**
     * Fetch translation from db or cache.
     * Returns An array that contains the translated string and it source.
     * Will return NULL if no translation is available.
     * @param string $original
     * @param string $lang
     * @return array list(translation,source)
     */
    function fetch_translation($original, $lang) {
        $translated = NULL;
        logger("Enter: $original", 4);

        //The original is saved in db in its escaped form
        $original = $GLOBALS['wpdb']->escape(html_entity_decode($original, ENT_NOQUOTES, 'UTF-8'));

        if(ENABLE_APC && function_exists('apc_fetch')) {
            $cached = apc_fetch($original .'___'. $lang, $rc);
            if($rc === TRUE) {
                logger("Exit from cache: $cached", 4);
                return $cached;
            }
        }

        if ($this->translations[$original]) {
            $translated = $this->translations[$original];
            logger("prefetch result for $original >>> {$this->translations[$original][0]} ({$this->translations[$original][1]})" , 4);
        } else {

            $table_name = $GLOBALS['wpdb']->prefix . TRANSLATIONS_TABLE;
            $query = "SELECT * FROM $table_name WHERE original = '$original' and lang = '$lang' ";
            $row = $GLOBALS['wpdb']->get_row($query);

            if($row !== FALSE) {
                $translated_text = stripslashes($row->translated);
                $translated = array($translated_text, $row->source);

                logger("db result for $original >>> $translated_text ($lang) ({$row->source})" , 4);
            }
        }

        if(ENABLE_APC && function_exists('apc_store')) {
            //If we don't have translation still we want to have it in cache
            $cache_entry = $translated;
            if($cache_entry == NULL) {
                $cache_entry = "";
            }

            //update cache
            $rc = apc_store($original .'___'. $lang, $cache_entry, 3600);
            if($rc === TRUE) {
                logger("Stored in cache: $original => {$translated[0]},{$translated[1]}", 4);
            }
        }

        logger("Exit: $translated", 4);
        return $translated;
    }

    /**
     * A new translation has been posted, update the translation database.
     * This has changed since we now accept multiple translations at once
     * This function accepts a new more "versatile" format
     * TODO - return some info?
     * @global <type> $user_ID - TODO
     */
    function update_translation() {

        $ref=getenv('HTTP_REFERER');
        $items = $_POST['items'];
        $lang = $_POST['ln0'];
        $source = $_POST['sr0'];
        // check params
        logger("Enter " . __FILE__ . " Params: $items, $lang, $ref", 5);
        if(!isset($items) || !isset($lang)) {
            logger("Enter " . __FILE__ . " missing Params: $items, $lang, $ref", 1);
            return;
        }

        //Check permissions, first the lanugage must be on the edit list. Then either the user
        //is a translator or automatic translation if it is enabled.
        // we must check that all sent languages are editable
        $all_editable = true;
        for ($i=0;$i<$items;$i++) {
            if (isset($_POST["ln$i"])) {
                if (!$this->transposh->options->is_editable_language($_POST["ln$i"])) {
                    $all_editable = false;
                    break;
                }
            }
        }

        if(!($all_editable &&
                ($this->transposh->is_translator() || ($source == 1 && $this->transposh->options->get_enable_auto_translate())))) {
            logger("Unauthorized translation attempt " . $_SERVER['REMOTE_ADDR'] , 1);
            header("HTTP/1.0 401 Unauthorized translation");
            exit;
        }

        //add our own custom header - so we will know that we got here
        header("Transposh: v-".TRANSPOSH_PLUGIN_VER." db_version-". DB_VERSION);

        // transaction log stuff
        global $user_ID;
        get_currentuserinfo();

        // log either the user ID or his IP
        if ('' == $user_ID) {
            $loguser = $_SERVER['REMOTE_ADDR'];
        }
        else {
            $loguser = $user_ID;
        }
        // end tl

        // We are now passing all posted items
        for ($i=0;$i<$items;$i++) {
            if (isset($_POST["tk$i"])) {
                $original =  base64_url_decode($_POST["tk$i"]);
                //The original content is encoded as base64 before it is sent (i.e. token), after we
                //decode it should just the same after it was parsed.
                $original = $GLOBALS['wpdb']->escape(html_entity_decode($original, ENT_NOQUOTES, 'UTF-8'));
            }
            if (isset($_POST["tr$i"])) {
                $translation = $_POST["tr$i"];
                //Decode & remove already escaped character to avoid double escaping
                $translation = $GLOBALS['wpdb']->escape(htmlspecialchars(stripslashes(urldecode($translation))));
            }
            if (isset($_POST["ln$i"])) {
                $lang = $_POST["ln$i"];
            }
            if (isset($_POST["sr$i"])) {
                $source = $_POST["sr$i"];
            }
            // should we backup?
            if ($source ==0) $backup_immidiate_possible = true;

            //Here we check we are not redoing stuff
            list($translated_text, $old_source) = $this->fetch_translation($original, $lang);
            if ($translated_text) {
                if ($source > 0) {
                    logger("Warning auto-translation for already translated: $original $lang", 0);
                    continue;
                    //return; // too harsh, we just need to get to the next in for
                }
                if ($translation == $GLOBALS['wpdb']->escape(htmlspecialchars(stripslashes(urldecode($translated_text)))) && $old_source == $source) {
                    logger("Warning attempt to retranslate with same text: $original, $translation", 0);
                    continue;
                    //return; // too harsh, we just need to get to the next in for
                }
            }
            // Setting the values string for the database (notice how concatanation is handled)
            $values .= "('" . $original . "','" . $translation . "','" . $lang . "','" . $source . "')".(($items != $i+1) ?', ':'');
            $delvalues .= "(original ='$original' AND lang='$lang')".(($items != $i+1) ?' OR ':'');
            // Setting the transaction log records
            $logvalues .= "('" . $original . "','" . $translation . "','" . $lang . "','".$loguser."','".$source."')".(($items != $i+1) ?', ':'');

            // If we have caching - we remove previous entry from cache
            if(ENABLE_APC && function_exists('apc_store')) {
                apc_delete($original .'___'. $lang);
            }
        }

        // avoid empty work
        if (!$values) return;
        // perform insertion to the database, with one query :)

        // since we have no primary key, replace made no sense
        /*$update = "REPLACE INTO ".$GLOBALS['wpdb']->prefix . TRANSLATIONS_TABLE." (original, translated, lang, source)
                VALUES $values";*/
        //so we'll delete all values and insert them...
        $update = "DELETE FROM ".$GLOBALS['wpdb']->prefix . TRANSLATIONS_TABLE." WHERE $delvalues";
        logger($update,3);
        $result = $GLOBALS['wpdb']->query($update);
        $update = "INSERT INTO ".$GLOBALS['wpdb']->prefix . TRANSLATIONS_TABLE." (original, translated, lang, source) VALUES $values";
        logger($update,3);
        $result = $GLOBALS['wpdb']->query($update);

        if($result !== FALSE) {
            // update the transaction log too
            $log = "INSERT INTO ".$GLOBALS['wpdb']->prefix.TRANSLATIONS_LOG." (original, translated, lang, translated_by, source) ".
                    "VALUES $logvalues";
            $result = $GLOBALS['wpdb']->query($log);

            logger("Inserted to db '$values'" , 3);
        }
        else {
            logger(mysql_error(),0);
            logger("Error !!! failed to insert to db $original , $translation, $lang," , 0);
            header("HTTP/1.0 404 Failed to update language database ".mysql_error());
        }

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

        $ref=getenv('HTTP_REFERER');
        $original =  base64_url_decode($token);
        logger ("Inside history for $original ($token)",4);

        // check params
        logger("Enter " . __FILE__ . " Params: $original , $translation, $lang, $ref", 3);
        if(!isset($original) || !isset($lang)) {
            logger("Enter " . __FILE__ . " missing params: $original, $lang," . $ref, 0);
            return;
        }
        logger ("Passed check for $lang",4);

        //Check permissions, first the lanugage must be on the edit list. Then either the user
        //is a translator or automatic translation if it is enabled.
        if(!($this->transposh->options->is_editable_language($lang) && $this->transposh->is_translator())) {
            logger("Unauthorized history request " . $_SERVER['REMOTE_ADDR'] , 1);
            header("HTTP/1.0 401 Unauthorized history");
            exit;
        }
        logger ("Passed check for editable and translator",4);

        $table_name = $GLOBALS['wpdb']->prefix . TRANSLATIONS_LOG;
        logger ("table is $table_name",4);

        //The original content is encoded as base64 before it is sent (i.e. token), after we
        //decode it should just the same after it was parsed.
        $original = $GLOBALS['wpdb']->escape(html_entity_decode($original, ENT_NOQUOTES, 'UTF-8'));

        //add  our own custom header - so we will know that we got here
        header("Transposh: v-".TRANSPOSH_PLUGIN_VER." db_version-". DB_VERSION);

        $query = "SELECT translated, translated_by, timestamp, source, user_login ".
                "FROM $table_name ".
                "LEFT JOIN {$GLOBALS['wpdb']->prefix}users ON translated_by = {$GLOBALS['wpdb']->prefix}users.id ".
                "WHERE original='$original' AND lang='$lang' ".
                "ORDER BY timestamp DESC";
        logger ("query is $query");

        $rows = $GLOBALS['wpdb']->get_results($query);
        logger ($rows,4); // trying

        if($rows !== FALSE) {
            echo '<table>' .
                    '<thead>'.
                    '<tr>'.
                    '<th>Translated</th><th/><th>By</th><th>At</th>'.
                    '</tr>'.
                    '</thead>'.
                    '<tbody>';
            foreach ($rows as $row) {
                if (is_null($row->user_login)) $row->user_login = $row->translated_by;
                echo "<tr><td>{$row->translated}</td><td source=\"{$row->source}\"/><td user_id=\"{$row->translated_by}\">{$row->user_login}</td><td>{$row->timestamp}</td></tr>";
            }
            echo '</tbody></table>';
        }

        exit;
    }

    /**
     * Function to return human translations history
     * @param string $date - either null for all or a date to get terms after
     * @return array List of rows
     */
    function get_all_human_translation_history($date ="null", $limit = "") {

        $table_name = $GLOBALS['wpdb']->prefix . TRANSLATIONS_LOG;
        logger ("table is $table_name",4);

        //add  our own custom header - so we will know that we got here
//        header("Transposh: v-".TRANSPOSH_PLUGIN_VER." db_version-". DB_VERSION);

        if ($date != "null") $dateterm = "and UNIX_TIMESTAMP(timestamp) > $date";
        if ($limit) $limitterm = "LIMIT $limit";
        $query = "SELECT original, lang, translated, translated_by, UNIX_TIMESTAMP(timestamp) as timestamp ".
                "FROM $table_name ".
                "WHERE source= 0 $dateterm ".
                "ORDER BY timestamp ASC $limitterm";
        logger ("query is $query");

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

        if( $installed_ver != DB_VERSION ) {
            $table_name = $GLOBALS['wpdb']->prefix . TRANSLATIONS_TABLE;

            logger("Attempting to create table $table_name", 0);
            // notice - keep every field on a new line or dbdelta fails
            $GLOBALS['wpdb']->query("ALTER TABLE $table_name DROP PRIMARY KEY");
            $sql = "CREATE TABLE $table_name (
                    original TEXT NOT NULL, 
                    lang CHAR(5) NOT NULL, 
                    translated TEXT, 
                    source TINYINT NOT NULL, 
                    KEY original (original(6),lang)
                    ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
            /*            $sql = "CREATE TABLE $table_name (original VARCHAR(255) NOT NULL,".
                    "lang CHAR(5) NOT NULL,".
                    "translated VARCHAR(255),".
                    "source TINYINT NOT NULL,".
                    "PRIMARY KEY (original, lang)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";*/

            dbDelta($sql);

            $table_name = $GLOBALS['wpdb']->prefix . TRANSLATIONS_LOG;

            logger("Attempting to create table $table_name", 0);
            // notice - keep every field on a new line or dbdelta fails
            $GLOBALS['wpdb']->query("ALTER TABLE $table_name DROP PRIMARY KEY");
            $sql = "CREATE TABLE $table_name (
                    original text NOT NULL, 
                    lang CHAR(5) NOT NULL, 
                    translated text, 
                    translated_by VARCHAR(15), 
                    source TINYINT NOT NULL, 
                    timestamp TIMESTAMP, 
                    KEY original (original(6),lang,timestamp)
                    ) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
            /*            $sql = "CREATE TABLE $table_name (original VARCHAR(255) NOT NULL,".
                    "lang CHAR(5) NOT NULL,".
                    "translated VARCHAR(255),".
                    "translated_by VARCHAR(15),".
                    "source TINYINT NOT NULL,".
                    "timestamp TIMESTAMP,".
                    "PRIMARY KEY (original, lang, timestamp)) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";*/

            dbDelta($sql);
            update_option(TRANSPOSH_DB_VERSION, DB_VERSION);
        }

        logger("Exit" );
    }

    function db_stats () {
        echo "<h4>Database stats</h4>";
        $table_name = $GLOBALS['wpdb']->prefix . TRANSLATIONS_TABLE;
        $log_table_name = $GLOBALS['wpdb']->prefix . TRANSLATIONS_LOG;
        $query = "SELECT count(*) as count FROM `$table_name`";
        $rows = $GLOBALS['wpdb']->get_results($query);
        foreach ($rows as $row) {
            if ($row->count)
                echo "<p>Total of <strong style=\"color:red\">{$row->count}</strong> translated phrases.</p>";
        }

        $query = "SELECT count(*) as count,lang FROM `$table_name` WHERE source='0' GROUP BY `lang` ORDER BY `count` DESC LIMIT 3";
        $rows = $GLOBALS['wpdb']->get_results($query);
        foreach ($rows as $row) {
            if ($row->count)
                echo "<p><strong>{$row->lang}</strong> has <strong style=\"color:red\">{$row->count}</strong> human translated phrases.</p>";
        }

        echo "<h4>Recent activity</h4>";
        $query = "SELECT * FROM `$log_table_name` WHERE source='0' ORDER BY `timestamp` DESC LIMIT 3";
        $rows = $GLOBALS['wpdb']->get_results($query);
        foreach ($rows as $row) {
            $td = mysql2date(get_option('date_format').' '.get_option('time_format'), $row->timestamp);
            //the_date();
            echo "<p>On <strong>{$td}</strong><br/>user <strong>{$row->translated_by}</strong> translated<br/>".
                    "\"<strong>{$row->original}</strong>\"<br/>to ".
                    "<strong style=\"color:red\">{$row->lang}</strong><br/>\"<strong>{$row->translated}</strong>\"</p>";
        }
    }

    /**
     * This function returns a list of candidate phrases which might contain a requested translated string
     * @param string $term The search term
     * @param string $language The language being searched
     * @return array Original phrases in which $term appears
     */
    function get_orignal_phrases_for_search_term($term, $language) {
        $table_name = $GLOBALS['wpdb']->prefix . TRANSLATIONS_TABLE;
        $n = '%';
        $term = addslashes_gpc($term);
        $query = "SELECT original
                        FROM `$table_name`
                        WHERE `lang` LIKE '$language'
                        AND `translated` LIKE '{$n}{$term}{$n}'";
        //TODO wait for feedbacks to see if we should put a limit here.

        logger ($query,4);
        $result = array();
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
                if (stripos($row->original,$r) !== false) {
                    $addme = false;
                }
            }
            if ($addme) $result[] = $row->original;
        }

        return $result;
    }
}
?>