<?php
namespace Wapi;

use Drupal\Component\Serialization\Json;
use Wapi\Exception\MessageInvalid;
use Wapi\Exception\WapiException;

class Message {
  
  const CLOCK_DEVIATION_THRESHOLD = 15;
  
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
    $body = json_decode($msg, TRUE);
    
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
  
  public function verifyCheck($secret) {
    if(!$this->check) {
      return FALSE;
    }
    $time_check = $this->verifyTimestamp();
    return $time_check && ($this->calculateCheck($secret) == $this->check);
  }
  
  public function verifyTimestamp() {
    if(!$this->timestamp) {
      return FALSE;
    }
    return abs($this->timestamp - $this->receive_time) <= static::CLOCK_DEVIATION_THRESHOLD;
  }
  
  public function calculateCheck($secret) {
    $secret = $secret ?: ServiceManager::service('app')->server_secret;
    return static::sign("$secret:$this->timestamp:$this->message_id:$this->method:", $this->data);
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
    return base64_encode(hash("sha256",$secret . json_encode($body), TRUE));
  }
}