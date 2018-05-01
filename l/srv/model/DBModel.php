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
    if($st->rowCount() = 1){
      return true;
    }else{
      return false;
    }
  }

  public function insertExpense(int $cost, int $type, string $description, int $date){
    try {
      $st = $this->db->prepare("INSERT INTO ledger.moves (`operation_id`, `user_id`, `cost`, `description`, `date`)
                                VALUES (?, ?, ?, ?, ?)");
      $st->execute([$cost,
                    $type,
                    empty($description) ? null : $description,
                    (empty($date) || $date = 0) ? null : $date
                  ]);
    } catch (PDOException $e) {
        //echo $e->getMessage();
        return false;
    }
    return true;
  }

  public function insertExpense(int $cost, int $type, string $description, int $date, float $lat, float $long, float $alt){
    $stp = $this->db->prepare("INSERT INTO points (`lat`, `long`, `alt`)
                               VALUES (?,?,?)");

   $st = $this->db->prepare("INSERT INTO ledger.moves (`operation_id`, `user_id`, `cost`, `description`, `date`, `point_id`)
                             VALUES (?, ?, ?, ?, ?, ?)");

    try {
      $this->db->beginTransaction();

      $stp->execute([$lat, $long, $alt]);                           
      $pointID = $stp->lastInsertId();
      $st->execute([$cost,
                    $type,
                    empty($description) ? null : $description,
                    (empty($date) || $date = 0) ? null : $date,
                    $pointID
                  ]);

      $this->db->commit();
    } catch (Exception $e) {
      $this->db->rollBack();
      return false;
    }

  }

}
