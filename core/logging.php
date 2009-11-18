<?php
/*
 * Logging Utils.
 */

//
//Debug/Logging parameters
//

// Report all PHP errors
//error_reporting(E_ALL);
require_once('FirePHP.class.php');

class logger {
//Enable tracing level.
//0 - disabled. Higher numbers will show more debug information.
    private $debug_level = 3;
//if (!defined('DEBUG')) define ("DEBUG" , 3);

    private $printout = false;
    public $show_caller = true;
    private $firephp;
    /**
     * Singleton instance of FirePHP
     *
     * @var logger
     */
    protected static $instance = null;
//require_once("parser.php");

    function  __construct() {
        if (!$this->printout) {
            ob_start();
        }
//$GLOBALS['firephp'] = FirePHP::getInstance(true);
        $this->firephp = FirePHP::getInstance(true);
    }

/*
 * Print a message to log.
 */
    function do_log($msg, $severity=3) {
        if($severity <= $this->debug_level) {
            if ($this->show_caller) {
                $trace = debug_backtrace();
                $log_prefix =str_pad("{$trace[2]['class']}::{$trace[2]['function']} {$trace[1]['line']}",55,'_');
            }
            error_log(date(DATE_RFC822) . ": "  . $msg . "\n", 3,  "/tmp/transposh.log");
            if ($this->printout || !isset($this->firephp)) {
                echo $msg;
                echo (defined('EOLPRINT')) ? "\n" : "<br/>";
            }
            else {
                if ((is_array($msg) || is_object($msg)) && $this->show_caller) {
                    $this->firephp->log("$log_prefix:");
                    $this->firephp->log($msg);
                }
                else {
                    if (is_array($msg) || is_object($msg)) {
                        $this->firephp->log($msg);
                    }
                    else {
                        $this->firephp->log("$log_prefix:$msg");
                    }
                }
            }
        }
    }

    /**
     * Gets singleton instance of FirePHP
     *
     * @param boolean $AutoCreate
     * @return FirePHP
     */
    public static function getInstance($AutoCreate=false) {
        if($AutoCreate===true && !self::$instance) {
            self::init();
        }
        return self::$instance;
    }

    /**
     * Creates FirePHP object and stores it for singleton access
     *
     * @return FirePHP
     */
    public static function init() {
        return self::$instance = new self();
    }
}

$GLOBALS['logger'] = logger::getInstance(true);

function logger($msg, $severity=3) {
    $GLOBALS['logger']->do_log($msg,$severity);
}

// sample
//$GLOBALS['logger'] = logger::getInstance(true);
//$GLOBALS['logger']->show_caller = true;

?>