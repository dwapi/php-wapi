<?php

namespace Wapi\Exception;

class WapiException extends \Exception {
  const CODE = 1;
  const MESSAGE = 'Error';
  protected $errorNo;
  
  public function __construct($message = NULL, $code = NULL, $errorNo = NULL) {
    $message = $message ?: static::MESSAGE;
    $code = $code ?: static::CODE;
    $this->errorNo = $errorNo;
    parent::__construct($message, $code);
  }
  
  function getErrorNo() {
    return $this->errorNo;
  }
}
