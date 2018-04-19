<?php
namespace app\ledger\core;

class Route {
  private $path;
  private $method;
  private $options;

  public function __construct($ctrlClassName, $method, $options = []){
    $this->$path = $path;
    $this->$method = $method;
  }
}
