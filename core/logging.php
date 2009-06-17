<?php
/*
 * Logging Utils.
 */

//
//Debug/Logging parameters
//

// Report all PHP errors
//error_reporting(E_ALL);

//Enable tracing level.
//0 - disabled. Higher numbers will show more debug information.
if (!defined('DEBUG')) define ("DEBUG" , 3);

require_once('FirePHP.class.php');
require_once("parser.php");
if (!defined('PRINTOUT')) {
    ob_start();
}

$firephp = FirePHP::getInstance(true);
/*
 * Print a message to log.
 */
function logger($msg, $severity=3) {
    global $firephp;
    if($severity <= DEBUG) {
        error_log(date(DATE_RFC822) . ": "  . $msg . "\n", 3,  "/tmp/transposh.log");
        if (defined('PRINTOUT')) {
            echo $msg;
            echo (defined('EOLPRINT')) ? "\n" : "<br/>";
        } else {
            $firephp->log($msg);
        }
    }
}


?>