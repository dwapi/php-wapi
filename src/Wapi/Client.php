<?php
namespace Wapi;

use GuzzleHttp\Psr7\Request;
use Ratchet\ConnectionInterface;

class Client {
  
  /**
   * @var \SplObjectStorage
   */
  public $conn;
  
  /**
   * @var Request
   */
  public $request;
  
  /**
   * @var integer
   */
  public $last_access;
  
  /**
   * @var bool
   */
  public $persist = FALSE;
  
  public function __construct(ConnectionInterface $conn, Request $request) {
    $this->conn = new \SplObjectStorage();
    $this->conn->attach($conn);
    $this->request = $request;
    $this->last_access = time();
  }
  
  /**
   * @return string
   */
  public function id() {
    return $this->conn->getHash($this->getConn());
  }
  
  /**
   * @return ConnectionInterface
   */
  public function getConn() {
    foreach($this->conn as $conn) {
      /** @var ConnectionInterface $conn */
      return $conn;
    }
  }
  
  public function getPath() {
    return $this->request->getUri()->getPath();
  }
  
  public function send($data) {
    try {
      $this->getConn()->send(json_encode($data));
    } catch (\Exception $e) {
      
    }
  }
  
}