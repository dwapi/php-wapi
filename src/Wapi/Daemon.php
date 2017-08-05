<?php
namespace Wapi;

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;

class Daemon {
  
  /** @var  \React\EventLoop\LoopInterface */
  public $loop;
  
  /** @var \Ratchet\Server\IoServer */
  public $wsApp;
  
  /** @var \Ratchet\Server\IoServer  */
  public $wssApp;
  
  /** @var \Wapi\AppInterface */
  public $app;
  
  public function __construct($params, $app_class_name = '\Wapi\App') {
    $server_secret = !empty($params['server-secret']) ? $params['server-secret'] : '';
    $id = !empty($params['id']) ? $params['id'] : 'default';
    $host = !empty($params['host']) ? $params['host'] : '0.0.0.0';
    $port = !empty($params['port']) ? $params['port'] : 8080;
    $error_log_file = !empty($params['error-log-file']) ? $params['error-log-file'] : NULL;
    $ssl_cert_file = !empty($params['ssl-cert-file']) ? $params['ssl-cert-file'] : 8080;
    $ssl_port = !empty($params['ssl-port']) ? $params['ssl-port'] : 8443;
  
    if ($error_log_file) {
      ErrorHandler::init($error_log_file);
    }
  
    $loop = Factory::create();
    
    $wsStack = new HttpServer(
      new WsServer(
        $this->app = new $app_class_name($loop, $id, $server_secret)
      )
    );
    
    $ws = new Server("$host:$port", $loop);
    $this->wsApp = new IoServer($wsStack, $ws, $loop);
    
    if($ssl_cert_file) {
      $wss = new Server("$host:$ssl_port", $loop);
      $wss = new SecureServer($wss, $loop, ['local_cert' => $ssl_cert_file, 'verify_peer' => FALSE]);
      $this->wssApp = new IoServer($wsStack, $wss, $loop);
    }
  
    $this->app->init();
  
    $this->loop = $loop;
  }
  
  public function run() {
    $this->loop->run();
  }
  
}