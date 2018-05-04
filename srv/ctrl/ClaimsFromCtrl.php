<?php
namespace app\ledger\ctrl;
use app\ledger\core\ctrl\AbstractBaseCtrl;
use app\ledger\model\DBModel;

class ClaimsFromCtrl extends AbstractAuthCtrl {
//class ExpensesFromCtrl extends AbstractBaseCtrl {
  private $cost = null;
  private $description = null;
  private $date = null;
  private $dueDate = null;
  private $types = [];


  public function __construct(array $get = [], array $post = []){
    parent::__construct($get, $post);

    if(!isset($this->get['types'])){
      try{
        if(!isset($post['cost'])){
          throw new \Exception('', 400); //bad request
        }else{
          $this->cost = (int)$post['cost'];
        }

        if(isset($post['description'])){
          if(\mb_strlen($post['description']) > 255){
            throw new \Exception('', 400); //bad request
          }

          $this->description = $post['description'];
        }

        if(isset($post['date']) && $post['date'] != 0){
            $this->date = (int)$post['date'];
        }

        if(isset($post['dueDate']) && $post['dueDate'] != 0){
            $this->dueDate = (int)$post['dueDate'];
        }

        if(isset($post['types'])){
          foreach ($post['types'] as $type) {
            $this->types[] = $type['id'];
          }
        }

      }catch (\Exception $e) {
        http_response_code($e->getCode());
        die();
      }
    }
  }

  public function execute(){
    $db = DBModel::getInstance();

    if(isset($this->get['types'])){
      $types = $db->getTypesForMoveType($this->userID,5);

      return $types;
    }else{
      return $db->insertClaim($this->userID, $this->cost, $this->types, $this->description, $this->date, $this->dueDate);
    }
  }
}
