<?php
namespace app\ledger\ctrl;

class loginCtrl {

  private $email;
  private $pass;

  public function __construct($get = '', $post = ''){
    try {
      if(!isset($post['email']) || !isset($post['pass'])){
        throw new \Exception('', 400); //bad request
      }

      if(empty($post['email']) || empty($post['pass'])){
        throw new \Exception('', 400); // bad request
      }

      if(!(($post['email'] == "adam.sorfa@gmail.com") && ($post['pass'] == 'password'))){
        throw new \Exception('', 403); //frobiden
      }else{

        $this->$email = $post['email'];
        $this->$pass = $post['pass'];
      }
    } catch (\Exception $e) {
      echo $e->getCode();
    }
  }


  public function execute(){

    echo $email;
    echo $pass;
  }

}
