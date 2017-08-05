<?php
namespace Wapi;

use Wapi\Exception\MessageInvalid;
use Wapi\Exception\WapiException;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;

abstract class App implements MessageComponentInterface, AppInterface {
  
  /**
   * @var string
   */
  public $server_secret;
  
  /** @var float */
  public $start_time;
  
  /** @var \Wapi\ClientManager */
  public $client_manager;
  
  public function __construct(LoopInterface $loop, $daemon_id, $server_secret) {
    $this->daemon_id = $daemon_id;
    $this->server_secret = $server_secret;
    $this->start_time = microtime(TRUE);
    ServiceManager::service('app', $this);
    ServiceManager::service('loop', $loop);
  }
  
  public function init() {
    $this->client_manager = ServiceManager::service('client_manager', new ClientManager());
  }
  
  public function provide($name, $utility) {
    ServiceManager::service($name, $utility);
  }
  
  public function uptime() {
    return time() - $this->start_time;
  }
  
  public function onOpen(ConnectionInterface $conn) {
    /** @var \GuzzleHttp\Psr7\Request */
    $request = $conn->httpRequest;
    $client = new Client($conn, $request);
    $client_manager =  $this->client_manager;
    $client_manager->clientAdd($client);
    $this->loop->addTimer(2, function() use ($client, $client_manager){
      if(empty($client->persist)) {
        $client_manager->clientRemove($client);
      }
    });
  }
  
  public function onMessage(ConnectionInterface $from, $msg) {
    $client = $this->client_manager->getClientFromConn($from);

    if(!$client) {
      $from->close();
      return;
    }

    try {
      $message = new Message($client, $msg);
    } catch (WapiException $e) {
      $from->send('Invalid request');
      $from->close();
      $this->client_manager->clientRemove($client);
      return;
    }
    
    $client->persist = TRUE;
  
    $handled = FALSE;
    foreach ($this->getMessageHandlers() AS $classname) {
      /** @var \Wapi\MessageHandler\MessageHandlerInterface $message_handler */
      if(!$handled && $classname::isApplicable($message)) {
        $message_handler = new $classname($message);
        $message_handler->handle();
        $handled = TRUE;
      }
    }
    if(!$handled) {
      $message->reply(NULL, new MessageInvalid());
      $client->persist = FALSE;
    }
  }
  
  public function onClose(ConnectionInterface $conn) {
    $client = $this->client_manager->getClientFromConn($conn);
    $this->client_manager->clientRemove($client);
  }
  
  public function onError(ConnectionInterface $conn, \Exception $e) {
    $client = $this->client_manager->getClientFromConn($conn);
    $this->client_manager->clientRemove($client);
    ErrorHandler::getInstance()->logException($e);
  }
}