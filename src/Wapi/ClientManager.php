<?php
namespace Wapi;

use Ratchet\ConnectionInterface;

class ClientManager {
  
  /**
   * @var Client[]
   */
  public $clients;
  
  public function __construct() {
    $this->clients = [];
  }
  
  public function clientCreate(ConnectionInterface $conn)
    /** @var \GuzzleHttp\Psr7\Request $request */{
    $request = $conn->httpRequest;
    return new Client($conn, $request);
  }
  
  public function clientAdd(Client $client = NULL) {
    if($client) {
      $this->clients[$client->id()] = $client;
    }
  }
  
  public function clientRemove(Client $client = NULL) {
    $clients = $this->clients;
    if($client && !empty($clients[$client->id()])) {
      $client->getConn()->close();
      unset($clients[$client->id()]);
      $this->clients = $clients;
    }
  }
  
  
  public function getClientFromConn(ConnectionInterface $conn) {
    $hash = spl_object_hash($conn);
    return !empty($this->clients[$hash]) ? $this->clients[$hash] : NULL;
  }
  
}