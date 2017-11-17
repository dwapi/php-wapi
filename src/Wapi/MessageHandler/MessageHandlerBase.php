<?php
namespace Wapi\MessageHandler;

use Wapi\ClientManager;
use Wapi\Exception\AccessDenied;
use Wapi\Exception\MethodNotFound;
use Wapi\Exception\ParametersInvalid;
use Wapi\Exception\WapiException;
use Wapi\Message;
use React\Promise\Promise;
use Wapi\ServiceManager;

abstract class MessageHandlerBase implements MessageHandlerInterface {
  
  /**
   * @var \Wapi\Message
   */
  public $message;
  
  public function __construct(Message $message) {
    $this->message = $message;
  }
  
  public function handle() {
    try {
      $this->access();
      
      $method = $this->message->method;
      $methods = $this->getMethods();
      try {
        if (!empty($methods[$method]) && is_callable($methods[$method]['callback'])) {
          if(!empty($methods[$method]['schema'])) {
            $this->validateSchema($methods[$method]['schema'], $this->message->data);
          }
          $result = call_user_func($methods[$method]['callback'], $this->message->data, $this->message);
          if($result instanceof Promise) {
            $result->then(function($result){
              $this->message->reply($result);
            }, function(WapiException $exception){
              $this->message->reply(NULL, $exception);
            });
          } else {
            $this->message->reply($result);
          }
        } else {
          throw new MethodNotFound();
        }
      } catch (WapiException $exception) {
        $this->message->reply(NULL, $exception);
      }
    } catch (WapiException $exception) {
      /** @var ClientManager $client_manager */
      $client_manager = ServiceManager::service('client_manager');
      $this->message->reply(NULL, $exception);
      $client_manager->clientRemove($this->message->client);
    }
    
    return FALSE;
  }
  
  
  public function validateSchema($schema, $values) {
    foreach($schema AS $property => $definition) {
      $max_length = NULL;
      if(!empty($definition['max_length'])) {
        $max_length = $definition['max_length'];
      }
      
      $required = !empty($definition['required']);
      $type = !empty($definition['multi']) ? 'array' : $definition['type'];
      
      if($error = $this->validateProperty($values, $property, $required, $type, $max_length)) {
        throw new ParametersInvalid($error . ": " . $property);
      }
      
      if($type == 'array' && is_array($values[$property])) {
        foreach($values[$property] AS $num => $value) {
          if($error = $this->validateProperty($values[$property], $num, $required, $definition['type'], $max_length)) {
            throw new ParametersInvalid($error . ": " . $property);
          }
        }
      }
      if($definition['type'] == 'assoc' && !empty($values[$property]) && !empty($definition['children'])) {
        if($error = $this->validateSchema($definition['children'], $values[$property])) {
          throw new ParametersInvalid($error . ": " . $property);
        }
      }
    }
    
    return NULL;
  }
  
  
  public function validateProperty($parent, $property, $required, $type, $max_length = NULL) {
    
    $isAssoc = function(array $arr) {
      if (array() === $arr) return true;
      return array_keys($arr) !== range(0, count($arr) - 1);
    };
    
    if($required && empty($parent[$property]) && $parent[$property] !== FALSE) {
      return "Missing required property";
    }
    
    $value = $parent[$property];
    
    if($value && !$type === 'any') {
      
      if ((gettype($value) != $type && $type != 'assoc') || ($type == 'assoc' && !$isAssoc($value))) {
        return "Wrong data type";
      }
      
      if ($max_length && (strlen($value) > $max_length)) {
        return "Value too long";
      }
    }
    
    return NULL;
  }
}