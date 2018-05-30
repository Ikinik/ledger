<?php
namespace app\ledger\ctrl;
use app\ledger\core\ctrl\AbstractBaseCtrl;
use app\ledger\model\DBModel;

class LongTermExpensesCtrl extends AbstractAuthCtrl {
//class ExpensesFromCtrl extends AbstractBaseCtrl {

  public function __construct(array $get = [], array $post = []){
    parent::__construct($get, $post);
  }

  public function execute(){
    $cost = null;
    $description = null;
    $date = null;
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

      if(isset($this->post['types'])){
        foreach ($this->post['types'] as $type) {
          $types[] = $type['id'];
        }
      }

      //validation end
      $db = DBModel::getInstance();
      return $db->insertLongTermExpense($this->userID, $cost, $types, $description, $date);

    }catch (\Exception $e) {
      http_response_code($e->getCode());
      die();
    }
  }

  public function getTypes(){
    $db = DBModel::getInstance();
    $types = $db->getTypesForOperation($this->userID,2);
    return $types;
  }


  public function getLongTermExpenses(){
    try{
      $db = DBModel::getInstance();
      $expenses = null;

      if(isset($this->get['date-to'])){
        if(!empty($this->get['date-to'])){
          $dateTo = (int)$this->get['date-to'];

          if(isset($this->get['date-from'])){
            if(!empty($this->get['date-from'])){
              $dateFrom = (int)$this->get['date-from'];

              if($dateFrom > $dateTo){
                  throw new \Exception('', 400); //bad request
              }

              $expenses = $db->getLongTermExpenses($this->userID, $dateTo, $dateFrom);
            }else{
              throw new \Exception('', 400); //bad request
            }
          }else{
            $expenses = $db->getLongTermExpenses($this->userID, $dateTo);
          }
        }else{
          throw new \Exception('', 400); //bad request
        }
      }else{
        $expenses = $db->getLongTermExpenses($this->userID);
      }

      return $expenses;
    }catch (\Exception $e) {
      http_response_code($e->getCode());
      die();
    }
  }

  public function removeLongTermExpense(){
    try{
      if(!isset($this->post['id'])){
        throw new \Exception('', 400); //bad request
      }

      if(empty($this->post['id'])){
        throw new \Exception('', 400); //bad request
      }

      $id = (int)$this->post['id'];

      $db = DBModel::getInstance();
      return $db->deleteMove($this->userID, $id);

    }catch (\Exception $e){
      http_response_code($e->getCode());
      echo $e;
      die();
    }
  }
}
