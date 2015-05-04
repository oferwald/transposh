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
require_once('FirePHP.class.php');

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

    /** @var FirePHP used for outputing into firephp output */
    private $firephp;

    /** @var used for remote firephp debugging */
    private $remoteip;

    /** @var logger Singelton instance of our logger */
    protected static $instance = null;

    function __construct() {
        // If not outputting to stdout, we should buffer so firephp will work
        if (!$this->printout) {
            ob_start();
        }
        $this->firephp = FirePHP_tp::getInstance(true);
    }

    /**
     * Print a message to log.
     * @param mixed $msg
     * @param int $severity
     */
    function do_log($msg, $severity = 3, $do_backtrace = false, $nest = 0) {
        if ($severity <= $this->debug_level) {
            if ($this->show_caller) {
                $trace = debug_backtrace();
                if ($do_backtrace) $this->firephp->log($trace[3]);
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
            if ($this->printout || !isset($this->firephp)) {
                echo "$log_prefix:$msg";
                echo ($this->eolprint) ? "\n" : "<br/>";
            } else {
                if (!isset($_SERVER['REMOTE_ADDR']) || $this->remoteip != $_SERVER['REMOTE_ADDR']) return;
                if ((is_array($msg) || is_object($msg)) && $this->show_caller) {
                    $this->firephp->group("$log_prefix: object/array", array('Collapsed' => true,
                        'Color' => '#FF00FF'));
                    //$this->firephp->log("$log_prefix:");
                    $this->firephp->log($msg);
                    $this->firephp->groupEnd();
                } else {
                    if (is_array($msg) || is_object($msg)) {
                        $this->firephp->log($msg);
                    } else {
                        $this->firephp->log("$log_prefix:$msg");
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