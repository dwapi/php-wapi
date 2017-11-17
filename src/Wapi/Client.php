<?php
namespace Wapi;

use GuzzleHttp\Psr7\Request;
use Ratchet\ConnectionInterface;
use Wapi\Protocol\Protocol;

class Client {
  
  /**
   * @var ConnectionInterface
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
  
  /**
   * @var string[]
   */
  public $paths = [];
  
  public function __construct(ConnectionInterface $conn, Request $request) {
    $this->conn = $conn;
    $this->request = $request;
    $this->last_access = time();
  }
  
  public function getRequestPath() {
    return $this->request->getUri()->getPath();
  }
  
  /**
   * @return string
   */
  public function id() {
    return spl_object_hash($this->getConn());
  }
  
  /**
   * @return ConnectionInterface
   */
  public function getConn() {
    return $this->conn;
  }
  
  public function addPath($path) {
    return $this->paths[$path] = $path;
  }
  
  public function hasPath($path) {
    return !empty($this->paths[$path]);
  }
  
  public function send($data) {
    try {
      $this->getConn()->send(Protocol::encode($data));
    } catch (\Exception $e) {
      
    }
  }
  
}