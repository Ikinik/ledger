<?php
namespace app\ledger\core\ctrl;

abstract class AbstractBaseCtrl {
  protected $get;
  protected $post;

  function __construct(array $get = [], array $post = []){
    $this->get = $get;
    $this->post = $post;
  }

  abstract function execute();
}
