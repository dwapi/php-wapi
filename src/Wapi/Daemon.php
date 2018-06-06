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
    $access_mask = !empty($params['access-mask']) ? $params['access-mask'] : '0.0.0.0';
    $host = !empty($params['host']) ? $params['host'] : '0.0.0.0';
    $port = !empty($params['port']) ? $params['port'] : 8080;
    $error_log_file = !empty($params['error-log-file']) ? $params['error-log-file'] : NULL;
    $ssl_cert_file = !empty($params['ssl-cert-file']) ? $params['ssl-cert-file'] : NULL;
    $ssl_port = !empty($params['ssl-port']) ? $params['ssl-port'] : 8443;
    $base_path = !empty($params['base-path']) ? $params['base-path'] : '';
    
    $params['address'] = $ssl_cert_file ? "wss://$host:$ssl_port$base_path" : "ws://$host:$port$base_path";
  
    if ($error_log_file) {
      ErrorHandler::init($error_log_file);
    }
  
    $loop = Factory::create();
    
    $wsStack = new HttpServer(
      new WsServer(
        $this->app = new $app_class_name($loop, $id, $server_secret, $params)
      )
    );
    
    $ws = new Server("$access_mask:$port", $loop);
    $this->wsApp = new IoServer($wsStack, $ws, $loop);
    
    if($ssl_cert_file) {
      // $part[0] = cert.pem, $part[1] = key.pem
      $parts = explode("|", $ssl_cert_file);
      $wss = new Server("$access_mask:$ssl_port", $loop);
      $wss = new SecureServer($wss, $loop, [
        'local_cert' => $parts[0], 
        'local_pk' => $parts[1], 
        'verify_peer' => FALSE, 
        'verify_peer_name' => FALSE
      ]);
      $this->wssApp = new IoServer($wsStack, $wss, $loop);
    }
  
    $this->app->init();
  
    $this->loop = $loop;
  }
  
  public function run() {
    $this->loop->run();
  }
  
  /**
   * @see https://gist.github.com/jadb/3949954
   * @param $argv
   * @return array
   */
  static function parseArgs($argv){
    array_shift($argv); $o = array();
    foreach ($argv as $a){
      if (substr($a,0,2) == '--'){ $eq = strpos($a,'=');
        if ($eq !== false){ $o[substr($a,2,$eq-2)] = substr($a,$eq+1); }
        else { $k = substr($a,2); if (!isset($o[$k])){ $o[$k] = true; } } }
      else if (substr($a,0,1) == '-'){
        if (substr($a,2,1) == '='){ $o[substr($a,1,1)] = substr($a,3); }
        else { foreach (str_split(substr($a,1)) as $k){ if (!isset($o[$k])){ $o[$k] = true; } } } }
      else { $o[] = $a; } }
    return $o;
  }
  
}