<?php
namespace app\ledger\ctrl;
use app\ledger\core\ctrl\AbstractBaseCtrl;
use app\ledger\model\DBModel;

class ExpensesFromCtrl extends AbstractAuthCtrl {
//class ExpensesFromCtrl extends AbstractBaseCtrl {
  private $cost = null;
  private $description = null;
  private $date = null;
  private $types = [];
  private $location = [];


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

        if(isset($post['types'])){
          foreach ($post['types'] as $type) {
            $this->types[] = $type['id'];
          }
        }

        if(isset($post['location'])){
          if(!isset($post['location']['latitude'])
          || !isset($post['location']['longitude'])){
            throw new \Exception('', 400); //bad request
          }

          $this->location['latitude'] = (float)$post['location']['latitude'];
          $this->location['longitude'] = (float)$post['location']['longitude'];

          if(isset($post['location']['altitude'])){
              $this->location['altitude'] = (float)$post['location']['altitude'];
          }else{
              $this->location['altitude'] = null;
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
      $types = $db->getTypesForMoveType($this->userID,1);
      return $types;
    }else{

      if(count($this->location) == 0)
        return $db->insertExpense($this->userID, $this->cost, $this->types, $this->description, $this->date);
      else
        return $db->insertExpenseWithPoint($this->userID,
                                           $this->cost,
                                           $this->types,
                                           $this->description,
                                           $this->date,
                                           $this->location['latitude'],
                                           $this->location['longitude'],
                                           $this->location['altitude']);
    }
  }
}
