<?php
namespace Wapi\MessageHandler;

use Wapi\Message;

interface MessageHandlerInterface {
  
  static function getMethods();
  static function isApplicable(Message $message);
  public function access();
  public function handle();
}