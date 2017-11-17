<?php
namespace Wapi;

use Wapi\Exception\MessageInvalid;
use Wapi\Exception\WapiException;
use Wapi\Protocol\Protocol;

class Message {
  
  const CLOCK_DEVIATION_THRESHOLD = 15;
  
  /**
   * @var array
   */
  public $original_message;
  
  /**
   * @var string
   */
  public $message_id;
  
  /**
   * @var string
   */
  public $path;
  
  /**
   * @var string
   */
  public $method;
  
  /**
   * @var mixed
   */
  public $data;
  
  /**
   * @var integer
   */
  public $timestamp;
  
  /**
   * @var string
   */
  public $check;
  
  /**
   * @var Client
   */
  public $client;
  
  /**
   * @var integer
   */
  public $receive_time;
  
  public function __construct(Client $client, $msg, $receive_time = NULL) {
    $this->original_message = $body = Protocol::decode($msg);
    
    if(!$body || empty($body['message_id'])) {
      throw new MessageInvalid();
    }
    
    $this->client = $client;
    
    $this->message_id = !empty($body['message_id']) ? $body['message_id'] : NULL;
    $this->method = !empty($body['method']) ? $body['method'] : NULL;
    $this->path = !empty($body['path']) ? $body['path'] : NULL;
    $this->data = isset($body['data']) ? $body['data'] : NULL;
    
    $this->receive_time = $receive_time ?: time();
    $this->timestamp = !empty($body['timestamp']) ? $body['timestamp'] : NULL;
    $this->check = !empty($body['check']) ? $body['check'] : NULL;
  }
  
  public function get($key) {
    return isset($this->original_message[$key]) ? $this->original_message[$key] : isset($this->$key) ? $this->$key : NULL;
  }
  
  public function verifyCheck($secret) {
    return $this->verifyTimestamp() && Protocol::verifyMessage($secret, $this->original_message);
  }
  
  public function verifyTimestamp() {
    return Protocol::verifyClock($this->original_message,static::CLOCK_DEVIATION_THRESHOLD);
  }
  
  public function reply($data = NULL, WapiException $error = NULL) {
    $body = [
      'message_id' => $this->message_id,
      'status' => $error ? $error->getCode() : 0,
      'data' => $data,
    ];
    
    if($error) {
      $body['error'] = $error->getMessage();
      $body['errorNo'] = $error->getErrorNo();
    }
    
    $this->client->send($body);
  }
  
  static function sign($secret, $body) {
    return Protocol::sign($secret, Protocol::encode($body));
  }
}