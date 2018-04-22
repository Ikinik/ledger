<?php
namespace app\ledger\ctrl;
use app\ledger\core\ctrl\AbstractBaseCtrl;

abstract class AbstractAuthCtrl extends AbstractBaseCtrl {

  protected $userID = null;

   public function __construct(array $get = [], array $post = []){
     parent::__construct($get, $post);

     ini_set('session.use_cookies', 1);
     ini_set('session.use_only_cookies', 1);
     ini_set('session.use_strict_mode', 1);
     ini_set('session.use_trans_sid', 0);

     session_start();
     session_regenerate_id();

     if(!isset($_SESSION['user_id'])){
       $this->userID = null;
     }

     if(empty($_SESSION['user_id'])){
       $this->userID = null;
     }else{
       $this->userID = $_SESSION['user_id'];
       setcookie('logged', true, 0, '/');
     }

     if($this->userID == null){
       http_response_code(401); //unauthorized
       die();
     }

   }
}
