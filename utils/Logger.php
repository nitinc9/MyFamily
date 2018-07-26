<?php
/**
 * Description: The class to provide logging related utilities.
 * 
 * @author Nitin Patil
 */
class Logger {

    /** The debug log level. */
    public static $DEBUG = 1;
    
    /** The info log level. */
    public static $INFO = 2;

    /** The warning log level. */
    public static $WARN = 3;
    
    /** The error log level. */
    public static $ERROR = 4;
    
    /** The log file handle. */
    private $fh;
    
    /** The log level. */
    private $level;
    
    
    /**
     * Initializes the logger.
     * 
     * @param logFile: The log file.
     */
    public function __construct($logFile) {
        $this->fh = fopen($logFile, 'a+');
        $this->level = $INFO; // default log level
    }
    
    /**
     * Closes the log.
     */
    public function close() {
        fclose($this->fh);
    }
    
    /**
     * Sets the log level.
     * 
     * @param logLevel: The log level to set.
     */
    public function setLogLevel($logLevel) {
        $level = 0;
        switch ($logLevel) {
            case 'debug':
                $level = Logger::$DEBUG;
                break;
            case 'info':
                $level = Logger::$INFO;
                break;
            case 'warn':
                $level = Logger::$WARN;
                break;
            case 'error':
                $level = Logger::$ERROR;
                break;
            default:
                throw new Exception('Invalid log level: ' . $logLevel);
        }
        $this->level = $level;
    }
    
    /**
     * Returns true if debug is enabled.
     */
    public function isDebugEnabled() {
        return ($this->level == Logger::$DEBUG);
    }
    
    /**
     * Logs a debug message.
     * 
     * @param msg: The message to log.
     */
    public function debug($msg) {
        $this->log(Logger::$DEBUG, $msg);
    }
    
    /**
     * Logs an info message.
     * 
     * @param msg: The message to log.
     */
    public function info($msg) {
        $this->log(Logger::$INFO, $msg);
    }
    
    /**
     * Logs a warning message.
     * 
     * @param msg: The message to log.
     */
    public function warn($msg) {
        $this->log(Logger::$WARN, $msg);
    }
    
    /**
     * Logs an error message.
     * 
     * @param msg: The message to log.
     */
    public function error($msg) {
        $this->log(Logger::$ERROR, $msg);
    }
    
    /**
     * Logs a message only if the log level is equal or higher to the current level.
     * 
     * @param level: The log level to use for logging.
     * @param msg: The message to log.
     */
    public function log($level, $msg) {
        if ($level >= $this->level) {
            $ts = date('Y-m-d h:i:s');
            $logLevel = '';
            switch ($level) {
                case Logger::$DEBUG:
                    $logLevel = 'DEBUG';
                    break;
                case Logger::$INFO:
                    $logLevel = 'INFO';
                    break;
                case Logger::$WARN:
                    $logLevel = 'WARN';
                    break;
                case Logger::$ERROR:
                    $logLevel = 'ERROR';
                    break;
                default:
                    throw new Exception('Invalid log level: ' . $level);
            }
            $entry = sprintf("%s [%s]: %s\n", $ts, $logLevel, $msg);
            fwrite($this->fh, $entry);
        }
    }
}
?>
