<?php
namespace Wapi;


interface AppInterface {
  
  /**
   * @return string[]
   */
  public function getMessageHandlers();
  
  public function init();
  
}