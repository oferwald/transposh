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

/*
 * Provide the backup service class
 */

//class that reperesent the admin page
class transposh_backup {

    /** @var transposh_plugin $transposh father class */
    private $transposh;

//constructor of class, PHP4 compatible construction for backward compatibility
    function transposh_backup(&$transposh) {
        $this->transposh = &$transposh;
    }

    function init_body() {
        $body = array();
        $body['home_url'] = $this->transposh->home_url;
        $body['key'] = $this->transposh->options->transposh_key;
        $body['v'] = '2';
        $body['tpv'] = '%VERSION%';
        return $body;
    }

    function do_backup() {
        $body = $this->init_body();
        //Check if there are thing to backup, before even accessing the service
        $rowstosend = $this->transposh->database->get_all_human_translation_history('null', 1);
        if (empty($rowstosend)) {
            echo "500 - No human translations to backup.";
            return;
        }

        // this one is for getting the key
        $result = wp_remote_post(TRANSPOSH_BACKUP_SERVICE_URL, array('body' => $body));
        if (is_wp_error($result)) {
            echo '500 - ' . $result->get_error_message();
            return;
        }
        if (isset($result['headers']['fail'])) {
            echo '500 - ' . $result['headers']['fail'];
            return;
        }
        if ($this->transposh->options->transposh_key == "") {
            $this->transposh->options->transposh_key = $result['headers']['transposh-key'];
            // TODO: deliever new gottenkey to client side?
            $this->transposh->options->update_options();
        }
        if (isset($result['headers']['lastitem'])) {
            $rowstosend = $this->transposh->database->get_all_human_translation_history($result['headers']['lastitem'], 100);
            while ($rowstosend) {
                $item = 0;
                $lastorig = '';
                $lastlang = '';
                $lasttrans = '';
                $lastby = '';
                $lastts = '';
                $body = $this->init_body();
                foreach ($rowstosend as $row) {
                    if ($lastorig != $row->original) {
                        $body['or' . $item] = $row->original;
                        $lastorig = $row->original;
                    }
                    if ($lastlang != $row->lang) {
                        $body['ln' . $item] = $row->lang;
                        $lastlang = $row->lang;
                    }
                    if ($lasttrans != $row->translated) {
                        $body['tr' . $item] = $row->translated;
                        $lasttrans = $row->translated;
                    }
                    if ($lastby != $row->translated_by) {
                        $body['tb' . $item] = $row->translated_by;
                        $lastby = $row->translated_by;
                    }
                    if ($lastts != $row->timestamp) {
                        $body['ts' . $item] = $row->timestamp;
                        $lastts = $row->timestamp;
                    }
                    $item++;
                }
                $body['items'] = $item;
                // no need to post 0 items
                if ($item == 0) return;
                $result = wp_remote_post(TRANSPOSH_BACKUP_SERVICE_URL, array('body' => $body));
                if (is_wp_error($result)) {
                    echo "500 - " . $result->get_error_message();
                    return;
                }
                if (isset($result['headers']['fail'])) {
                    echo "500 - " . $result['headers']['fail'];
                    return;
                }
                $rowstosend = $this->transposh->database->get_all_human_translation_history($row->timestamp, 100);
            }
        }
        echo '200 - backup in sync';
    }

    function do_restore() {
        $body['to'] = time(); //TODO: fix this to get from DB
        $body['home_url'] = $this->transposh->home_url;
        $body['key'] = $this->transposh->options->transposh_key;
        $result = wp_remote_get(TRANSPOSH_RESTORE_SERVICE_URL . "?to={$body['to']}&key={$body['key']}&home_url={$body['home_url']}"); // gotta be a better way...
        $lines = split("[\n|\r]", $result['body']);
        foreach ($lines as $line) {
            $trans = split(',', $line);
            if ($trans[0])
                    $this->transposh->database->restore_translation($trans[0], $trans[1], $trans[2], $trans[3], $trans[4]);
        }
        // clean up cache so that results will actually show
        $this->transposh->database->cache_clean();
        exit;
    }

}