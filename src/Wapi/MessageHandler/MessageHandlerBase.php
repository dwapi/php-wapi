<?php
namespace Wapi\MessageHandler;

use Wapi\ClientManager;
use Wapi\Exception\AccessDenied;
use Wapi\Exception\MethodNotFound;
use Wapi\Exception\WapiException;
use Wapi\Message;
use React\Promise\Promise;

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
      $methods = static::getMethods();
      try {
        if (!empty($methods[$method]) && method_exists($this, $methods[$method])) {
          $result = call_user_func([$this, $methods[$method]], $this->message->data);
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
      $client_manager = ClientManager::getInstance();
      $this->message->reply(NULL, $exception);
      $client_manager->clientRemove($this->message->client);
    }
    
    return FALSE;
  }
}