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
 *
 *  adapted metabox sample code from http://www.code-styling.de/
*/

/*
 * Provide the backup service class
*/

define ("TRANSPOSH_BACKUP_SERVICE_URL" , "http://svc.transposh.org/backup");
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
        $body["home_url"] = $this->transposh->home_url;
        $body["key"] = $this->transposh->options->get_transposh_key();
        $result = wp_remote_post(TRANSPOSH_BACKUP_SERVICE_URL, array('body' => $body));
        if (is_wp_error($result)) {
            echo "500 - ".$result->get_error_message();
            return;
        }
        if ($result['headers']['fail']) {
            echo "500 - ".$result['headers']['fail'];
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
                    if ($body["or".($item-1)] != $row->original)
                        $body["or".$item] = $row->original;
                    if ($body["ln".($item-1)] != $row->lang)
                        $body["ln".$item] = $row->lang;
                    if ($body["tr".($item-1)] != $row->translated)
                        $body["tr".$item] = $row->translated;
                    if ($body["tb".($item-1)] != $row->translated_by)
                        $body["tb".$item] = $row->translated_by;
                    if ($body["ts".($item-1)] != $row->timestamp)
                        $body["ts".$item] = $row->timestamp;
                    $item++;
                }
                $body["items"] = $item;
                if ($item == 0) return; // no need to post 0 items
                $result = wp_remote_post(TRANSPOSH_BACKUP_SERVICE_URL, array('body' => $body));
                if (is_wp_error($result)) {
                    echo "500 - ".$result->get_error_message();
                    return;
                }
                if ($result['headers']['fail']) {
                    echo "500 - ".$result['headers']['fail'];
                    return;
                }
                $rowstosend = $this->transposh->database->get_all_human_translation_history($row->timestamp, 500);
            }
        }
        Echo "200 - backup in sync";
    }
}