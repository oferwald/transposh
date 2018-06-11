<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$file = $argv[1];
$version = strtolower($argv[2]);
//var_dump($argv);
$excludesection = FALSE;

$handle = fopen($file, "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $ignorenext = false;
        // those lines will never be included
        if (strpos($line, 'FULL VERSION') !== false ||
                strpos($line, 'FULLSTOP') !== false ||
                strpos($line, 'WPORG VERSION') !== false ||
                strpos($line, 'WPORGSTOP') !== false) {
            $ignorenext = true;
        }
        if ($version == "full") {
            if (strpos($line, 'WPORG VERSION') !== false) {
                $excludesection = true;
            }
            if (strpos($line, 'WPORGSTOP') !== false) {
                $excludesection = false;
            }
        }
        if ($version == "wporg") {
            if (strpos($line, 'FULL VERSION') !== false) {
                $excludesection = true;
            }
            if (strpos($line, 'FULLSTOP') !== false) {
                $excludesection = false;
            }
        }
        if (!$excludesection && !$ignorenext) {
            echo $line;
        }
    }

    fclose($handle);
} else {
    // error opening the file.
} 