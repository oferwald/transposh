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

define('TRANSPOSH_BACKUP_SERVICE_URL', 'http://svc.transposh.org/backup');
define('TRANSPOSH_RESTORE_SERVICE_URL', 'http://svc.transposh.org/restore');

//define ("TRANSPOSH_BACKUP_SERVICE_URL" , "http://ofergen:8888/backup");
//class that reperesent the admin page
class transposh_backup {

    /** @var transposh_plugin $transposh father class */
    private $transposh;

//constructor of class, PHP4 compatible construction for backward compatibility
    function transposh_backup(&$transposh) {
        $this->transposh = &$transposh;
    }

    function do_backup() {
        $body = array();
        $body['home_url'] = $this->transposh->home_url;
        $body['key'] = $this->transposh->options->get_transposh_key();
        //Check if there are thing to backup, before even accessing the service
        $rowstosend = $this->transposh->database->get_all_human_translation_history('null', 1);
        if (empty($rowstosend)) {
            echo "500 - No human translations to backup.";
            return;
        }

        $result = wp_remote_post(TRANSPOSH_BACKUP_SERVICE_URL, array('body' => $body));
        if (is_wp_error($result)) {
            echo '500 - ' . $result->get_error_message();
            return;
        }
        if ($result['headers']['fail']) {
            echo '500 - ' . $result['headers']['fail'];
            return;
        }
        if ($this->transposh->options->get_transposh_key() == "") {
            $this->transposh->options->set_transposh_key($result['headers']['transposh-key']);
            // TODO: deliever new gottenkey to client side?
            //echo $this->transposh->options->get_transposh_key();
            $this->transposh->options->update_options();
        }
        if ($result['headers']['lastitem']) {
            $rowstosend = $this->transposh->database->get_all_human_translation_history($result['headers']['lastitem'], 500);
            while ($rowstosend) {
                $item = 0;
                foreach ($rowstosend as $row) {
                    if ($body['or' . ($item - 1)] != $row->original)
                            $body['or' . $item] = $row->original;
                    if ($body['ln' . ($item - 1)] != $row->lang)
                            $body['ln' . $item] = $row->lang;
                    if ($body['tr' . ($item - 1)] != $row->translated)
                            $body['tr' . $item] = $row->translated;
                    if ($body['tb' . ($item - 1)] != $row->translated_by)
                            $body['tb' . $item] = $row->translated_by;
                    if ($body['ts' . ($item - 1)] != $row->timestamp)
                            $body['ts' . $item] = $row->timestamp;
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
                if ($result['headers']['fail']) {
                    echo "500 - " . $result['headers']['fail'];
                    return;
                }
                $rowstosend = $this->transposh->database->get_all_human_translation_history($row->timestamp, 500);
            }
        }
        Echo '200 - backup in sync';
    }

    function do_restore() {
        $body['to'] = time(); //TODO: fix this to get from DB
        $body['home_url'] = $this->transposh->home_url;
        $body['key'] = $this->transposh->options->get_transposh_key();
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