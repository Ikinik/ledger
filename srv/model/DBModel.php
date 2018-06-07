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

  public function registerUser(string $email, string $password){

    $stUserExists = $this->db->prepare("SELECT id FROM ledger.users WHERE users.email = ? LIMIT 1");
    $stAddUser = $this->db->prepare("INSERT INTO ledger.users (`password`, `email`) VALUES (?, ?)");

    try {
      $this->db->beginTransaction();

      $stUserExists->execute([$email]);
      if($stUserExists->rowCount() == 1){
        throw new \Exception('User already exist', 409);
      }

      $hash = password_hash($password, PASSWORD_BCRYPT);
      $stAddUser->execute([$hash, $email]);
      $userID = $this->db->lastInsertId();

      $this->db->commit();
    } catch (PDOException $e) {
      $this->db->rollBack();
      throw new \Exception('Something went wrong', 400);
    }

    $this->addType($userID, 'other', [true, true, true, true, true]);
    $this->addType($userID, 'waste', [true, true, false, false, false]);
    $this->addType($userID, 'fuel', [true, true, false, false, false]);
    $this->addType($userID, 'fun', [true, true, false, false, false]);
    $this->addType($userID, 'food', [true, true, false, false, false]);
    $this->addType($userID, 'rent', [false, true, true, true, true]);

    return $userID;
  }

  public function emailExist(string $email){
    $st = $this->db->prepare("SELECT id FROM ledger.users WHERE users.email = ? LIMIT 1");
    $st->execute([$email]);

    if($st->rowCount() != 1){
      return false;
    }
    $result = $st->fetch(\PDO::FETCH_ASSOC);
    return $result['id'];
  }

  /* DEPRECATED - NOT IN USE */
  public function getTypesForMoveType(int $userID,int $moveID){
    $st = $this->db->prepare("SELECT types.id, types.name
                              FROM ledger.types_moves
                              JOIN ledger.types on types.id = types_moves.type_id
                              where types_moves.move_id = ? and types.user_id = ?");
    $st->execute([$userID, $moveID]);
    $types = $st->fetchAll(\PDO::FETCH_ASSOC);
    return $types;
  }

  public function getTypesForOperation(int $userID,int $operationID){
    $st = $this->db->prepare("SELECT ledger.types.id AS id, ledger.types.name AS name FROM ledger.types_operations
                              JOIN ledger.types ON ledger.types_operations.type_id = ledger.types.id
                              WHERE ledger.types.valid = 1 AND ledger.types.user_id = ? AND ledger.types_operations.operation_id = ?");
    $st->execute([$userID, $operationID]);
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


  public function getExpenses(int $userID, $dateTo = null, $dateFrom = null){
    if($dateTo && $dateFrom){
      $st = $this->db->prepare("SELECT expenses.id, expenses.cost, expenses.description, expenses.date, expenses.lat, expenses.long, expenses.alt, expenses.created, expenses.types
                                FROM ledger.expenses where ledger.expenses.user_id = ? AND expenses.date <= FROM_UNIXTIME(?) AND expenses.date >= FROM_UNIXTIME(?) ORDER BY expenses.date LIMIT 500");
      $st->execute([$userID, $dateTo, $dateFrom]);
    }else if($dateTo){
      $st = $this->db->prepare("SELECT expenses.id, expenses.cost, expenses.description, expenses.date, expenses.lat, expenses.long, expenses.alt, expenses.created, expenses.types
                                FROM ledger.expenses where ledger.expenses.user_id = ? AND expenses.date <= FROM_UNIXTIME(?) ORDER BY expenses.date LIMIT 500");
      $st->execute([$userID, $dateTo]);
    }else {
      $st = $this->db->prepare("SELECT expenses.id, expenses.cost, expenses.description, expenses.date, expenses.lat, expenses.long, expenses.alt, expenses.created, expenses.types
                                FROM ledger.expenses where ledger.expenses.user_id = ? ORDER BY expenses.date LIMIT 500");
      $st->execute([$userID]);
    }

    $expenses = $st->fetchAll(\PDO::FETCH_ASSOC);

    for ($i=0; $i < count($expenses) ; $i++) {
      $expenses[$i]['types'] = json_decode($expenses[$i]['types']);
    }

    return $expenses;
  }

  public function getLongTermExpenses(int $userID, $dateTo = null, $dateFrom = null){
    if($dateTo && $dateFrom){
      $st = $this->db->prepare("SELECT long_term_expenses.id, long_term_expenses.cost, long_term_expenses.description, long_term_expenses.date, long_term_expenses.created, long_term_expenses.types
                                FROM ledger.long_term_expenses where ledger.long_term_expenses.user_id = ? AND long_term_expenses.date <= FROM_UNIXTIME(?) AND long_term_expenses.date >= FROM_UNIXTIME(?) ORDER BY long_term_expenses.date LIMIT 500");
      $st->execute([$userID, $dateTo, $dateFrom]);
    }else if($dateTo){
      $st = $this->db->prepare("SELECT long_term_expenses.id, long_term_expenses.cost, long_term_expenses.description, long_term_expenses.date, long_term_expenses.created, long_term_expenses.types
                                FROM ledger.long_term_expenses where ledger.long_term_expenses.user_id = ? AND long_term_expenses.date <= FROM_UNIXTIME(?) ORDER BY long_term_expenses.date LIMIT 500");
      $st->execute([$userID, $dateTo]);
    }else {
      $st = $this->db->prepare("SELECT long_term_expenses.id, long_term_expenses.cost, long_term_expenses.description, long_term_expenses.date, long_term_expenses.created, long_term_expenses.types
                                FROM ledger.long_term_expenses where ledger.long_term_expenses.user_id = ? ORDER BY long_term_expenses.date LIMIT 500");
      $st->execute([$userID]);
    }

    $expenses = $st->fetchAll(\PDO::FETCH_ASSOC);

    for ($i=0; $i < count($expenses) ; $i++) {
      $expenses[$i]['types'] = json_decode($expenses[$i]['types']);
    }

    return $expenses;
  }

  public function getIncomes(int $userID, $dateTo = null, $dateFrom = null){
    if($dateTo && $dateFrom){
      $st = $this->db->prepare("SELECT incomes.id, incomes.cost, incomes.description, incomes.date, incomes.created, incomes.types
                                FROM ledger.incomes where ledger.incomes.user_id = ? AND incomes.date <= FROM_UNIXTIME(?) AND incomes.date >= FROM_UNIXTIME(?) ORDER BY incomes.date LIMIT 500");
      $st->execute([$userID, $dateTo, $dateFrom]);
    }else if($dateTo){
      $st = $this->db->prepare("SELECT incomes.id, incomes.cost, incomes.description, incomes.date, incomes.created, incomes.types
                                FROM ledger.incomes where ledger.incomes.user_id = ? AND incomes.date <= FROM_UNIXTIME(?) ORDER BY incomes.date LIMIT 500");
      $st->execute([$userID, $dateTo]);
    }else {
      $st = $this->db->prepare("SELECT incomes.id, incomes.cost, incomes.description, incomes.date, incomes.created, incomes.types
                                FROM ledger.incomes where ledger.incomes.user_id = ? ORDER BY incomes.date LIMIT 500");
      $st->execute([$userID]);
    }

    $expenses = $st->fetchAll(\PDO::FETCH_ASSOC);

    for ($i=0; $i < count($expenses) ; $i++) {
      $expenses[$i]['types'] = json_decode($expenses[$i]['types']);
    }

    return $expenses;
  }

  public function getDebts(int $userID, $dateTo = null, $dateFrom = null){
    if($dateTo && $dateFrom){
      $st = $this->db->prepare("SELECT debts.id, debts.cost, debts.description, debts.date, debts.due_date, debts.created, debts.types
                                FROM ledger.debts where ledger.debts.user_id = ? AND debts.date <= FROM_UNIXTIME(?) AND debts.date >= FROM_UNIXTIME(?) ORDER BY debts.date LIMIT 500");
      $st->execute([$userID, $dateTo, $dateFrom]);
    }else if($dateTo){
      $st = $this->db->prepare("SELECT debts.id, debts.cost, debts.description, debts.date, debts.due_date, debts.created, debts.types
                                FROM ledger.debts where ledger.debts.user_id = ? AND debts.date <= FROM_UNIXTIME(?) ORDER BY debts.date LIMIT 500");
      $st->execute([$userID, $dateTo]);
    }else {
      $st = $this->db->prepare("SELECT debts.id, debts.cost, debts.description, debts.date, debts.due_date, debts.created, debts.types
                                FROM ledger.debts where ledger.debts.user_id = ? ORDER BY debts.date LIMIT 500");
      $st->execute([$userID]);
    }

    $expenses = $st->fetchAll(\PDO::FETCH_ASSOC);

    for ($i=0; $i < count($expenses) ; $i++) {
      $expenses[$i]['types'] = json_decode($expenses[$i]['types']);
    }

    return $expenses;
  }

  public function getClaims(int $userID, $dateTo = null, $dateFrom = null){
    if($dateTo && $dateFrom){
      $st = $this->db->prepare("SELECT claims.id, claims.cost, claims.description, claims.date, claims.due_date, claims.created, claims.types
                                FROM ledger.claims where ledger.claims.user_id = ? AND claims.date <= FROM_UNIXTIME(?) AND claims.date >= FROM_UNIXTIME(?) ORDER BY claims.date LIMIT 500");
      $st->execute([$userID, $dateTo, $dateFrom]);
    }else if($dateTo){
      $st = $this->db->prepare("SELECT claims.id, claims.cost, claims.description, claims.date, claims.due_date, claims.created, claims.types
                                FROM ledger.claims where ledger.claims.user_id = ? AND claims.date <= FROM_UNIXTIME(?) ORDER BY claims.date LIMIT 500");
      $st->execute([$userID, $dateTo]);
    }else {
      $st = $this->db->prepare("SELECT claims.id, claims.cost, claims.description, claims.date, claims.due_date, claims.created, claims.types
                                FROM ledger.claims where ledger.claims.user_id = ? ORDER BY claims.date LIMIT 500");
      $st->execute([$userID]);
    }

    $expenses = $st->fetchAll(\PDO::FETCH_ASSOC);

    for ($i=0; $i < count($expenses) ; $i++) {
      $expenses[$i]['types'] = json_decode($expenses[$i]['types']);
    }

    return $expenses;
  }

  public function deleteMove(int $userID, int $moveID){
    $st = $this->db->prepare("UPDATE ledger.moves SET moves.valid = 0 WHERE moves.user_id = ? AND moves.id = ?");
    return $st->execute([$userID, $moveID]);
  }

  public function getTypesForOperations(int $userID){
    $setCounter = $this->db->prepare("SET @row_number = 0");
    $st = $this->db->prepare("SELECT (@row_number:=@row_number + 1) AS rowId, types_for_operaions.id, types_for_operaions.name, types_for_operaions.for_types as forType FROM ledger.types_for_operaions WHERE types_for_operaions.user_id = ?");

    try{
      $this->db->beginTransaction();
      $setCounter->execute();
      $st->execute([$userID]);
      $results = $st->fetchAll(\PDO::FETCH_ASSOC);
      $this->db->commit();
    } catch (PDOException $e) {
      $this->db->rollBack();
      return false;
    }

    $countResults = count($results);
    for ($i=0; $i < $countResults; $i++) {
      $forType = json_decode($results[$i]['forType']);

      //prevent null value of wariable, problem with in_array function.
      if($forType == null){ $forType = [];}

      $newForType = [in_array(1, $forType),
                     in_array(2, $forType),
                     in_array(3, $forType),
                     in_array(4, $forType),
                     in_array(5, $forType)];
      $results[$i]['forType'] = $newForType;
    }
    return $results;
  }

  public function deleteType(int $userID, int $typeID){
    $st = $this->db->prepare("UPDATE ledger.types SET types.valid = 0 WHERE types.user_id = ? AND types.id = ?");
    return $st->execute([$userID, $typeID]);
  }

  public function updateType(int $userID, int $typeID, $typeName, array $forType){

    $stRename = $this->db->prepare("UPDATE ledger.types SET types.name = ? WHERE types.user_id = ? AND types.id = ?");
    $stDeleteOps = $this->db->prepare("DELETE FROM ledger.types_operations WHERE types_operations.type_id = ?");
    $toInsert = $this->db->prepare("INSERT INTO ledger.types_operations (types_operations.operation_id, types_operations.type_id) VALUES (?, ?)");

    try{
      $this->db->beginTransaction();

      $stRename->execute([$typeName, $userID, $typeID]);
      $stDeleteOps->execute([$typeID]);

      foreach ($forType as $i => $selected) {$typeID = $this->db->lastInsertId();
        $operationID = $i + 1;

        if($selected){
            $toInsert->execute([$operationID, $typeID]);
        }
      }

      $this->db->commit();
    }catch(PDOException $e){
      $this->db->rollBack();
      return false();
    }
  }

  public function addType(int $userID, $typeName, array $forType){
    $typeIns = $this->db->prepare("INSERT INTO ledger.types (types.user_id, types.name) VALUES (?, ?)");
    $toInsert = $this->db->prepare("INSERT INTO ledger.types_operations (types_operations.operation_id, types_operations.type_id) VALUES (?, ?)");

    try{
      $this->db->beginTransaction();

      $typeIns->execute([$userID, $typeName]);
      $typeID = $this->db->lastInsertId();

      foreach ($forType as $i => $selected) {
        $operationID = $i + 1;

        if($selected){
            $toInsert->execute([$operationID, $typeID]);
        }
      }

      $this->db->commit();
    }catch (PDOException $e){
      $this->rollBack();
      return false;
    }

  }




}
