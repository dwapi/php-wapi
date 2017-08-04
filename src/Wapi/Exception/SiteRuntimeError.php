<?php

namespace Wapi\Exception;

class SiteRuntimeError extends WapiException {
  const CODE = 7;
  const MESSAGE = 'Site error.';
  
  public function __construct($message, $code) {
    parent::__construct($message, static::CODE, $code);
  }
}
