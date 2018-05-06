<?php
namespace app\ledger\model;
use app\ledger\core\CONFIG;

class DBModel {
  private static $instance = false;
  private $db;

  private function __construct(){
    try {
       $this->db = new \PDO('mysql:host='. CONFIG::MYSQL_HOST .';'. CONFIG::MYSQL_DB .';charset=utf8',
                             CONFIG::MYSQL_USER, CONFIG::MYSQL_PASS);
       $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
       $this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    } catch(\PDOException $err) {
      die($err->getMessage());
    }

  }

  public static function getInstance(){
    if(self::$instance === false){
      self::$instance = new DBModel;
    }

    return self::$instance;
  }

  /**
   * functioon for user verification
   * @param  string $email    user's email
   * @param  string $password user's password in plain text form
   * @return int or null    when user is successfuly validated return user_id or null if validation was not successfull
   */
  public function verifyUser(string $email, string $password){
    $st = $this->db->prepare("SELECT id, password FROM ledger.users WHERE users.email = ? LIMIT 1");
    $st->execute([$email]);

    if($st->rowCount() == 1){
        $user = $st->fetchAll()[0];
        $userPasswdHash = $user['password'];
        $userID = $user['id'];

        if(password_verify($password, $userPasswdHash)){
          //success
          return $userID;
        }else{
          //password did not match
          return null;
        }
    }else{
      return null;
    }
  }

  public function getTypesForMoveType(int $userID,int $moveID){
    $st = $this->db->prepare("SELECT types.id, types.name
                              FROM ledger.types_moves
                              JOIN ledger.types on types.id = types_moves.type_id
                              where types_moves.move_id = ? and types.user_id = ?");
    $st->execute([$userID, $moveID]);
    $types = $st->fetchAll(\PDO::FETCH_ASSOC);
    return $types;
  }

  public function checkUserMoveType(int $userID, int $typeID, int $moveID){
    $st = $this->db->prepare("SELECT types.id
                              FROM ledger.types_moves
                              JOIN ledger.types
                              ON types.id = types_moves.type_id
                              WHERE types_moves.move_id = ? AND types.user_id = ? AND types.id = ?
                              LIMIT 1");
    $st->execute([$moveID, $userID, $typeID]);
    if($st->rowCount() == 1){
      return true;
    }else{
      return false;
    }
  }

// !!!!!! INSERTS !!!!!!

  private function insertMove(int $operationID, int $userID, int $cost, array $types, $description, $date, $dueDate = null){
    $moveSt = $this->db->prepare("INSERT INTO ledger.moves (`operation_id`, `user_id`, `cost`, `description`, `date`, `due_date`)
                              VALUES (?, ?, ?, ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?))");

    $typeToMoveST = $this->db->prepare("INSERT INTO ledger.types_moves (`type_id`, `move_id`) VALUES (?, ?)");

    try {
      $this->db->beginTransaction();
      $moveSt->execute([$operationID, $userID, $cost, $description, $date, $dueDate]);

      $moveID = $this->db->lastInsertId();

      foreach ($types as $type) {
        $typeToMoveST->execute([$type, $moveID]);
      }

      $this->db->commit();
      return true;
    } catch (PDOException $e) {
        //echo $e->getMessage();
        $this->db->rollBack();
        return false;
    }
  }

  public function insertExpenseWithPoint(int $userID, int $cost, array $types, $description, $date, float $lat, float $long, $alt){
    $pointSt = $this->db->prepare("INSERT INTO ledger.points (`lat`, `long`, `alt`)
                               VALUES (?,?,?)");

    $moveSt = $this->db->prepare("INSERT INTO ledger.moves (`operation_id`, `user_id`, `cost`, `description`, `date`, `point_id`)
                             VALUES (?, ?, ?, ?, FROM_UNIXTIME(?), ?)");

    $typeToMoveST = $this->db->prepare("INSERT INTO ledger.types_moves (`type_id`, `move_id`) VALUES (?, ?)");


    try {
      $this->db->beginTransaction();

      $pointSt->execute([$lat, $long, $alt]);
      $pointID = $this->db->lastInsertId();
      $moveSt->execute([1, $userID, $cost, $description, $date, $pointID]);
      $moveID = $this->db->lastInsertId();

      foreach ($types as $type) {
        $typeToMoveST->execute([$type, $moveID]);
      }

      $this->db->commit();
      return true;
    } catch (Exception $e) {
      //echo $e->getMessage();
      $this->db->rollBack();
      return false;
    }
  }

  public function insertExpense(int $userID, int $cost, array $types, $description, $date){
    return $this->insertMove(1, $userID, $cost, $types, $description, $date);
  }

  public function insertLongTermExpense(int $userID, int $cost, array $types, $description, $date){
    return $this->insertMove(2, $userID, $cost, $types, $description, $date);
  }

  public function insertIncome(int $userID, int $cost, array $types, $description, $date){
    return $this->insertMove(3, $userID, $cost, $types, $description, $date);
  }

  public function insertDebt(int $userID, int $cost, array $types, $description, $date, $dueDate){
    return $this->insertMove(4, $userID, $cost, $types, $description, $date, $dueDate);
  }

  public function insertClaim(int $userID, int $cost, array $types, $description, $date, $dueDate){
    return $this->insertMove(5, $userID, $cost, $types, $description, $date, $dueDate);
  }


  public function getExpenses($userID){

    $st = $this->db->prepare("SELECT expenses.id, expenses.cost, expenses.description, expenses.date, expenses.lat, expenses.long, expenses.alt, expenses.created, expenses.types
                              FROM ledger.expenses where ledger.expenses.user_id = ?");

    $st->execute([$userID]);
    $expenses = $st->fetchAll(\PDO::FETCH_ASSOC);

    for ($i=0; $i < count($expenses) ; $i++) {
      $expenses[$i]['types'] = json_decode($expenses[$i]['types']);
    }

    return $expenses;
  }

}
