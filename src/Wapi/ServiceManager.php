<?php
namespace Wapi;

class ServiceManager {
  
  /**
   * @var object[]
   */
  static $services = [];
  
  /**
   * @param string $name
   * @param null|object $set
   * @return object|null
   */
  static function service($name, $set = NULL) {
    if(isset($set)) {
      static::$services[$name] = $set;
    }
    return !empty(static::$services[$name]) ? static::$services[$name] : NULL;
  }
}