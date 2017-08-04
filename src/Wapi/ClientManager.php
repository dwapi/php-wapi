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
    $client = NULL;
    
    foreach($this->clients AS $id => $existing_client) {
      if($existing_client->conn->contains($conn)) {
        $client = $existing_client;
      }
    }
    
    return $client;
  }
  
}