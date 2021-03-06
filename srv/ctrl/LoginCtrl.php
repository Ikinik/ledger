<?php
namespace app\ledger\ctrl;
use app\ledger\core\ctrl\AbstractBaseCtrl;
use app\ledger\model\DBModel;

class LoginCtrl extends AbstractBaseCtrl {

  public function __construct(array $get = [], array $post = []){
    parent::__construct($get, $post);
    //$post = ["email" => "adam.sorfa@gmail.com", "pass" => "password"];
    //$post = $get;
  }

  public function execute(){
    try {
      if(!isset($this->post['email']) || !isset($this->post['pass'])){
        throw new \Exception('', 400); //bad request
      }

      if(empty($this->post['email']) || empty($this->post['pass'])){
        throw new \Exception('', 400); // bad request
      }

      $email = htmlspecialchars($this->post['email']);
      $pass = htmlspecialchars($this->post['pass']);

      //verify credentials
      $db = DBModel::getInstance();
      if(!($userID = $db->verifyUser($email, $pass))){
        http_response_code(403); //forbiden
        die();
      }

      ini_set('session.use_cookies', 1);
      ini_set('session.use_only_cookies', 1);
      ini_set('session.use_strict_mode', 1);
      ini_set('session.use_trans_sid', 0);

      session_start();
      session_regenerate_id();

      $_SESSION['user_id'] = $userID;
      setcookie('logged', true, 0, '/');

      return ['logged' => true];

    } catch (\Exception $e) {
      http_response_code($e->getCode());
      die();
    }
  }


  public function register(){
    try{
      if(!isset($this->post['email']) || !isset($this->post['invitation']) || !isset($this->post['pass'])){
        throw new \Exception('isset', 400); //bad request
      }

      if(empty($this->post['email']) || empty($this->post['invitation']) || empty($this->post['pass'])){
        throw new \Exception('empty', 400); // bad request
      }

      if(mb_strlen($this->post['pass']) < 5){
        throw new \Exception('pass', 400); // bad request
      }

      if($this->post['invitation'] != '123456789'){
        throw new \Exception('wrong invitation code', 400); // bad request
      }

      $email = htmlspecialchars($this->post['email']);
      $pass = htmlspecialchars($this->post['pass']);
      $invitation = htmlspecialchars($this->post['invitation']);

      $db = DBModel::getInstance();
      $userID = $db->registerUser($email, $pass);

      ini_set('session.use_cookies', 1);
      ini_set('session.use_only_cookies', 1);
      ini_set('session.use_strict_mode', 1);
      ini_set('session.use_trans_sid', 0);

      session_start();
      session_regenerate_id();

      $_SESSION['user_id'] = $userID;
      setcookie('logged', true, 0, '/');

      return ['logged' => true];

    } catch (\Exception $e) {
      http_response_code($e->getCode());
      return ["message" => $e->getMessage()];
      //die($e->getMessage());
    }
  }

  public function loginGoogleOAuth(){
    try{

      if(!isset($this->post['AuthID'])){
          throw new \Exception('isset', 400); //bad request
      }

      if(empty($this->post['AuthID'])){
          throw new \Exception('isset', 400); //bad request
      }

      //get user informationg from google
      $url = "https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=" . $this->post['AuthID'];
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Set so curl_exec returns the result instead of outputting it.
      $response = curl_exec($ch); // Get the response and close the channel.
      curl_close($ch);
      $data = json_decode($response);

      $db = DBModel::getInstance();
      if(!($userID = $db->emailExist($data->email))){
        throw new \Exception('Account is not registrated', 400);
      }

      ini_set('session.use_cookies', 1);
      ini_set('session.use_only_cookies', 1);
      ini_set('session.use_strict_mode', 1);
      ini_set('session.use_trans_sid', 0);

      session_start();
      session_regenerate_id();

      $_SESSION['user_id'] = $userID;
      setcookie('logged', true, 0, '/');
      setcookie('loggedGoogle', true, 0, '/');

      return ['logged' => true];

    } catch (\Exception $e) {
      http_response_code($e->getCode());
      die();
    }
  }

}
