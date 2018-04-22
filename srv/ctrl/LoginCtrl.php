<?php
namespace app\ledger\ctrl;
use app\ledger\core\ctrl\AbstractBaseCtrl;

class LoginCtrl extends AbstractBaseCtrl {

  private $email;
  private $pass;

  public function __construct(array $get = [], array $post = []){
    parent::__construct($get, $post);

    //$post = ["email" => "adam.sorfa@gmail.com", "pass" => "password"];

    try {
      if(!isset($post['email']) || !isset($post['pass'])){
        throw new \Exception('', 400); //bad request
      }

      if(empty($post['email']) || empty($post['pass'])){
        throw new \Exception('', 400); // bad request
      }

      $this->email = $post['email'];
      $this->pass = $post['pass'];

    } catch (\Exception $e) {
      http_response_code($e->getCode());
      die();
    }
  }


  public function execute(){
    //verify credentials
    if(!(($this->email == "adam.sorfa@gmail.com") && ($this->pass == 'password'))){
      http_response_code(403); //forbiden
      die();
    }

    ini_set('session.use_cookies', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_trans_sid', 0);

    session_start();
    session_regenerate_id();

    $_SESSION['user_id'] = '1234';
    setcookie('logged', true, 0, '/');

    return ['logged' => true];
  }

}
