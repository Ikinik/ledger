<?php
namespace app\ledger\ctrl;
use app\ledger\core\ctrl\AbstractBaseCtrl;
use app\ledger\model\DBModel;

class ExpensesFromCtrl extends AbstractAuthCtrl {
//class ExpensesFromCtrl extends AbstractBaseCtrl {

  public function __construct(array $get = [], array $post = []){
    parent::__construct($get, $post);
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
