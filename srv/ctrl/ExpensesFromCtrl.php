<?php
namespace app\ledger\ctrl;
use app\ledger\core\ctrl\AbstractBaseCtrl;
use app\ledger\model\DBModel;

class ExpensesFromCtrl extends AbstractAuthCtrl {
//class ExpensesFromCtrl extends AbstractBaseCtrl {

  public function __construct(array $get = [], array $post = []){
    parent::__construct($get, $post);

    if(!isset($this->get['types'])){
      try{
        if(!isset($post['cost']) || !isset($post['type']) || !isset($post['description']) || !isset($post['date'])){
          throw new \Exception('', 400); //bad request
        }

        if(empty($post['type'])){
          throw new \Exception('', 400); //bad request
        }

        if(mb_strlen($post['description']) > 255){
          throw new \Exception('', 400); //bad request
        }

        $post['cost'] = (int)$post['cost'];
        $post['date'] = (int)$post['date'];
        $post['type'] = (int)$post['type'];

        $db = DBModel::getInstance();
        if(!$db->checkUserMoveType($this->userID, $post['type'], 1)){
          throw new \Exception('', 400); //bad request
        }

      }catch (\Exception $e) {
        http_response_code($e->getCode());
        die();
      }
    }
  }

  public function execute(){

    if(isset($this->get['types'])){
      $db = DBModel::getInstance();
      $types = $db->getTypesForMoveType($this->userID,1);
      return $types;
    }else{

      

      return null;
    }

  }

}
