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
 * Logging utility class
 */

// Report all PHP errors
//error_reporting(E_ALL);
require_once('chromelogger.php');

class tp_logger {

    /** @var string Name of file to log into */
    private $logfile;

    /** @var int Tracing level, 0 is disabled (almost) and higher numbers show more debug info */
    private $debug_level = 3;

    /** @var boolean should logging be outputted to stdout */
    public $printout = false;

    /** @var boolean should logging outputted to stdout include an EOL */
    public $eolprint = false;

    /** @var boolean shell we show which function called the logger */
    public $show_caller = true;

    /** @var used for remote firephp debugging */
    private $remoteip;

    private $global_log = 0;
    
    private $logstr = "";

    /** @var logger Singelton instance of our logger */
    protected static $instance = null;

    function __construct() {
        // If not outputting to stdout, we should buffer so firephp will work
        if (!$this->printout) {
            ob_start();
        }
    }

    /**
     * Print a message to log.
     * @param mixed $msg
     * @param int $severity
     */
    function do_log($msg, $severity = 3, $do_backtrace = false, $nest = 0) {
        //globalvarlogging
        if ($severity<$this->global_log) {
            $this->logstr .= $msg."<br>";
        }
        if ($severity <= $this->debug_level) {
            if ($this->show_caller) {
                $trace = debug_backtrace();
                if ($do_backtrace)
                    ChromePhp_tp::log($trace[3]);
                if (isset($trace[2 + $nest]['class'])) {
                    $log_prefix = str_pad("{$trace[2 + $nest]['class']}::{$trace[2 + $nest]['function']} {$trace[1 + $nest]['line']}", 55 + $nest, '_');
                } else {
                    $prefile = substr($trace[1 + $nest]['file'], strrpos($trace[1 + $nest]['file'], "/"));
                    $log_prefix = str_pad("{$prefile}::{$trace[1 + $nest]['function']} {$trace[1 + $nest]['line']}", 55 + $nest, '_');
                }
            }
            if (isset($this->logfile) && $this->logfile) {
                if (!is_array($msg) && !is_object($msg)) {
                    error_log(date(DATE_W3C) . " $log_prefix: " . $msg . "\n", 3, $this->logfile);
                } else {
                    if (is_array($msg)) {
                        error_log(date(DATE_W3C) . " $log_prefix: Array start\n", 3, $this->logfile);
                    } else {
                        error_log(date(DATE_W3C) . " $log_prefix: Object start\n", 3, $this->logfile);
                    }
                    foreach ($msg as $key => $item) {
                        if (!is_array($item)) {
                            if (!is_object($item) || method_exists($item, '__toString'))
                                error_log(date(DATE_W3C) . " $log_prefix: $key => $item\n", 3, $this->logfile);
                        } else {
                            error_log(date(DATE_W3C) . " $log_prefix: subarray -> $key\n", 3, $this->logfile);
                            $this->do_log($item, $severity, false, $nest + 1);
                        }
                    }
                    error_log(date(DATE_W3C) . " $log_prefix: Array stop\n", 3, $this->logfile);
                }
            }
            if ($this->printout/* || !isset($this->firephp) */) {
                echo "$log_prefix:$msg";
                echo ($this->eolprint) ? "\n" : "<br/>";
            } else {
                if (!transposh_utils::get_clean_server_var( 'REMOTE_ADDR') || $this->remoteip != transposh_utils::get_clean_server_var( 'REMOTE_ADDR')) {
                    return;
                }
                if ((is_array($msg) || is_object($msg)) && $this->show_caller) {
                    ChromePhp_tp::groupCollapsed("$log_prefix: object/array");
                    ChromePhp_tp::log($msg);
                    ChromePhp_tp::groupEnd();
                } else {
                    if (is_array($msg) || is_object($msg)) {
                        ChromePhp_tp::log($msg);
                    } else {
                        ChromePhp_tp::log("$log_prefix:$msg");
                    }
                }
            }
        }
    }

    /**
     * Gets singleton instance of logger
     * @param boolean $AutoCreate
     * @return logger
     */
    public static function getInstance($AutoCreate = false) {
        if ($AutoCreate === true && !self::$instance) {
            self::init();
        }
        return self::$instance;
    }

    /**
     * Creates logger object and stores it for singleton access
     * @return logger
     */
    public static function init() {
        return self::$instance = new self();
    }

    public function set_debug_level($int) {
        $this->debug_level = $int;
    }

    public function set_global_log($int) {
        if ($int == 0) {$this->logstr = "";}
        $this->global_log = $int;        
    }

    public function get_logstr() {
        return $this->logstr;        
    }

    public function set_log_file($filename) {
        $this->logfile = $filename;
    }

    public function set_remoteip($remoteip) {
        $this->remoteip = $remoteip;
    }

}

// We create a global singelton instance
$GLOBALS['tp_logger'] = tp_logger::getInstance(true);

/**
 * This function provides easier access to logging using the singleton object
 * @param mixed $msg
 * @param int $severity
 */
/* function tp_logger($msg, $severity = 3, $do_backtrace = false) {
  $GLOBALS['tp_logger']->do_log($msg, $severity, $do_backtrace);
  } */

/*
 *  sample of how to modify logging parameters from anywhere
 * 
  $GLOBALS['tp_logger'] = tp_logger::getInstance(true);
  $GLOBALS['tp_logger']->show_caller = true;
 */
?>