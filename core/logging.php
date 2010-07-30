<?php

/*  Copyright © 2009-2010 Transposh Team (website : http://transposh.org)
 *
 * 	This program is free software; you can redistribute it and/or modify
 * 	it under the terms of the GNU General Public License as published by
 * 	the Free Software Foundation; either version 2 of the License, or
 * 	(at your option) any later version.
 *
 * 	This program is distributed in the hope that it will be useful,
 * 	but WITHOUT ANY WARRANTY; without even the implied warranty of
 * 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * 	GNU General Public License for more details.
 *
 * 	You should have received a copy of the GNU General Public License
 * 	along with this program; if not, write to the Free Software
 * 	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/*
 * Logging utility class
 */

// Report all PHP errors
//error_reporting(E_ALL);
require_once('FirePHP.class.php');

define('TP_LOG_FILE', '/tmp/transposh.log');

class logger {

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
    /** @var logger Singelton instance of our logger */
    protected static $instance = null;

    function __construct() {
        // If not outputting to stdout, we should buffer so firephp will work
        if (!$this->printout) {
            ob_start();
        }
        $this->firephp = FirePHP::getInstance(true);
    }

    /**
     * Print a message to log.
     * @param mixed $msg
     * @param int $severity
     */
    function do_log($msg, $severity=3) {
        if ($severity <= $this->debug_level) {
            if ($this->show_caller) {
                $trace = debug_backtrace();
                if ($trace[2]['class']) {
                    $log_prefix = str_pad("{$trace[2]['class']}::{$trace[2]['function']} {$trace[1]['line']}", 55, '_');
                } else {
                    $prefile = substr($trace[1]['file'], strrpos($trace[1]['file'], "/"));
                    $log_prefix = str_pad("{$prefile}::{$trace[1]['function']} {$trace[1]['line']}", 55, '_');
                }
            }
            if (!is_array($msg) && !is_object($msg)) {
                error_log(date(DATE_RFC822) . " $log_prefix: " . $msg . "\n", 3, TP_LOG_FILE);
            } else {
                if (is_array($msg)) {
                    error_log(date(DATE_RFC822) . " $log_prefix: Array start\n", 3, TP_LOG_FILE);
                } else {
                    error_log(date(DATE_RFC822) . " $log_prefix: Object start\n", 3, TP_LOG_FILE);
                }
                foreach ($msg as $key => $item) {
                    if (!is_array($item)) {
                        if (!is_object($item) || method_exists($item, '__toString'))
                                error_log(date(DATE_RFC822) . " $log_prefix: $key => $item\n", 3, TP_LOG_FILE);
                    } else {
                        error_log(date(DATE_RFC822) . " $log_prefix: subarray -> $key\n", 3, TP_LOG_FILE);
                        $this->do_log($item, $severity);
                    }
                }
                error_log(date(DATE_RFC822) . " $log_prefix: Array stop\n", 3, TP_LOG_FILE);
            }
            if ($this->printout || !isset($this->firephp)) {
                echo "$log_prefix:$msg";
                echo ($this->eolprint) ? "\n" : "<br/>";
            } else {
                if ((is_array($msg) || is_object($msg)) && $this->show_caller) {
                    $this->firephp->log("$log_prefix:");
                    $this->firephp->log($msg);
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
    public static function getInstance($AutoCreate=false) {
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

}

// We create a global singelton instance
$GLOBALS['logger'] = logger::getInstance(true);

/**
 * This function provides easier access to logging using the singleton object
 * @param mixed $msg
 * @param int $severity
 */
function logger($msg, $severity=3) {
    $GLOBALS['logger']->do_log($msg, $severity);
}

/*
 *  sample of how to modify logging parameters from anywhere
 * 
  $GLOBALS['logger'] = logger::getInstance(true);
  $GLOBALS['logger']->show_caller = true;
 */
?>