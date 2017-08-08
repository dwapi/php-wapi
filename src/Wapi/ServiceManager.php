<?php
namespace Wapi;

class ServiceManager {
  
  /**
   * @var object[]
   */
  static $services = [];
  
  /**
   * @param string $name
   * @param null|mixed $set
   * @return null|mixed
   */
  static function service($name, $set = NULL) {
    if(isset($set)) {
      self::$services[$name] = $set;
    }
    return !empty(self::$services[$name]) ? self::$services[$name] : NULL;
  }
  
  /**
   * @return \React\EventLoop\LoopInterface
   */
  static function loop() {
    return self::service('loop');
  }
  
  /**
   * @return \Wapi\App
   */
  static function app() {
    return self::service('app');
  }
  
  /**
   * @return \Wapi\ClientManager
   */
  static function clientManager() {
    return self::service('client_manager');
  }
}