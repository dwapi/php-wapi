<?php
namespace Wapi;

use Drupal\Component\Serialization\Json;
use Wapi\Exception\System\CompileError;
use Wapi\Exception\System\CompileWarning;
use Wapi\Exception\System\CoreError;
use Wapi\Exception\System\CoreWarning;
use Wapi\Exception\System\Deprecated;
use Wapi\Exception\System\Error;
use Wapi\Exception\System\Notice;
use Wapi\Exception\System\Parse;
use Wapi\Exception\System\RecoverableError;
use Wapi\Exception\System\Strict;
use Wapi\Exception\System\UserDeprecated;
use Wapi\Exception\System\UserError;
use Wapi\Exception\System\UserNotice;
use Wapi\Exception\System\UserWarning;
use Wapi\Exception\System\Warning;

class ErrorHandler {
  
  public $error_file_path;
  public $file;
  
  public function __construct($error_file_path) {
    $this->error_file_path = $error_file_path;
    $this->file = new \SplFileObject($this->error_file_path, 'a');
    register_shutdown_function( [$this, "checkForFatal"] );
    set_error_handler( [$this, "logError"] );
    set_exception_handler( [$this, "logException"] );
    error_reporting( E_ALL );
  }
  
  static function init($error_file) {
    static::getInstance(new static($error_file));
  }
  
  /**
   * @param null $set
   * @return null|static
   */
  static function getInstance($set = NULL) {
    static $instance;
    
    if($set) {
      $instance = $set;
    }
    
    return $instance;
  }
  
  /**
   * Error handler, passes flow over the exception logger with new ErrorException.
   */
  public static function logError( $err_severity, $err_msg, $err_file, $err_line, $context = null )
  {
    try {
      switch ($err_severity) {
        case E_ERROR:               throw new Error            ($err_msg, 0, $err_severity, $err_file, $err_line);
        case E_WARNING:             throw new Warning          ($err_msg, 0, $err_severity, $err_file, $err_line);
        case E_PARSE:               throw new Parse            ($err_msg, 0, $err_severity, $err_file, $err_line);
        case E_NOTICE:              throw new Notice           ($err_msg, 0, $err_severity, $err_file, $err_line);
        case E_CORE_ERROR:          throw new CoreError        ($err_msg, 0, $err_severity, $err_file, $err_line);
        case E_CORE_WARNING:        throw new CoreWarning      ($err_msg, 0, $err_severity, $err_file, $err_line);
        case E_COMPILE_ERROR:       throw new CompileError     ($err_msg, 0, $err_severity, $err_file, $err_line);
        case E_COMPILE_WARNING:     throw new CompileWarning   ($err_msg, 0, $err_severity, $err_file, $err_line);
        case E_USER_ERROR:          throw new UserError        ($err_msg, 0, $err_severity, $err_file, $err_line);
        case E_USER_WARNING:        throw new UserWarning      ($err_msg, 0, $err_severity, $err_file, $err_line);
        case E_USER_NOTICE:         throw new UserNotice       ($err_msg, 0, $err_severity, $err_file, $err_line);
        case E_STRICT:              throw new Strict           ($err_msg, 0, $err_severity, $err_file, $err_line);
        case E_RECOVERABLE_ERROR:   throw new RecoverableError ($err_msg, 0, $err_severity, $err_file, $err_line);
        case E_DEPRECATED:          throw new Deprecated       ($err_msg, 0, $err_severity, $err_file, $err_line);
        case E_USER_DEPRECATED:     throw new UserDeprecated   ($err_msg, 0, $err_severity, $err_file, $err_line);
      }
    } catch (\ErrorException $e) {
      static::logException($e);
    }
  }

  /**
   * Uncaught exception handler.
   */
  static function logException( \Exception $e )
  {
    if($handler = ErrorHandler::getInstance()) {
      $type = get_class($e);
      if ($e instanceof \ErrorException) {
        $class_parts = explode('\\', $type);
        $type = array_pop($class_parts);
      }
      $line = [
        date('Y-m-d H:i:s'),
        $type,
        json_encode($e->getMessage()),
        $e->getFile(),
        $e->getLine(),
        json_encode($e->getTrace())
      ];
      $handler->file->fputcsv($line);
    }
  }

  /**
   * Checks for a fatal error, work around for set_error_handler not working on fatal errors.
   */
  public function checkForFatal()
  {
    $error = error_get_last();
    if ( $error["type"] == E_ERROR ) {
      $this->logError( $error["type"], $error["message"], $error["file"], $error["line"] );
    }
  }

  static function getErrors($tail = 20) {
    $rows = [];
    if($handler = ErrorHandler::getInstance()) {
      $lines = [];
      $path = escapeshellarg($handler->error_file_path);
      exec("tail -$tail $path", $lines);
      foreach ($lines AS $line) {
        $row = str_getcsv($line);
        $rows[] = [
          'time' => $row[0],
          'type' => $row[1],
          'message' => json_decode($row[2], TRUE),
          'file' => $row[3],
          'line' => $row[4],
          'trace' => !empty($row[5]) ? $row[5] : '',
        ];
      }
    }
  
    return $rows;
  }
  
  static function clearErrors() {
    if($handler = ErrorHandler::getInstance()) {
      $handler->file->ftruncate(0);
    }
  }
  
}