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
    if($client && $existing_client = $this->clients[$client->id()]) {
      $client->getConn()->close();
      unset($this->clients[$client->id()]);
    }
  }
  
  
  public function getClientFromConn(ConnectionInterface $conn) {
    $hash = spl_object_hash($conn);
    return !empty($this->clients[$hash]) ? $this->clients[$hash] : NULL;
  }
  
}