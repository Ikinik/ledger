<?php
namespace app\ledger\ctrl;
use app\ledger\core\ctrl\AbstractBaseCtrl;
use app\ledger\model\DBModel;

class DebtsCtrl extends AbstractAuthCtrl {
//class ExpensesFromCtrl extends AbstractBaseCtrl {

  public function __construct(array $get = [], array $post = []){
    parent::__construct($get, $post);
  }

  public function execute(){
    $cost = null;
    $description = null;
    $date = null;
    $dueDate = null;
    $types = [];

    try{
      if(!isset($this->post['cost'])){
        throw new \Exception('', 400); //bad request
      }else{
        $cost = (int)$this->post['cost'];
      }

      if(isset($this->post['description'])){
        if(\mb_strlen($this->post['description']) > 255){
          throw new \Exception('', 400); //bad request
        }

        $description = $this->post['description'];
      }

      if(isset($this->post['date']) && $this->post['date'] != 0){
          $date = (int)$this->post['date'];
      }

      if(isset($this->post['dueDate']) && $this->post['dueDate'] != 0){
          $dueDate = (int)$this->post['dueDate'];
      }

      if(isset($this->post['types'])){
        foreach ($this->post['types'] as $type) {
          $types[] = $type['id'];
        }
      }

      //validation end

      $db = DBModel::getInstance();
      return $db->insertDebt($this->userID, $cost, $types, $description, $date, $dueDate);

    }catch (\Exception $e) {
      http_response_code($e->getCode());
      die();
    }
  }

  public function getTypes(){
    $db = DBModel::getInstance();
    $types = $db->getTypesForOperation($this->userID,4);
    return $types;
  }
}
