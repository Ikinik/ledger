<?php
namespace app\ledger\core;
use app\ledger\core\Route;
use app\ledger\core\ctrl\AbstractBaseCtrl;

class Router {
  private static $instance = false;
  private $routes = [];
  private $get = [];
  private $post = [];

  private function __construct(){
    $this->routes['/'] = 'LoginCtrl';
    $this->routes['login'] = 'LoginCtrl';
    $this->routes['login/register'] = 'LoginCtrl/register';

    $this->routes['expenses'] = 'ExpensesCtrl';
    $this->routes['expenses/types'] = 'ExpensesCtrl/getTypes';
    $this->routes['expenses/view'] = 'ExpensesCtrl/getExpenses';
    $this->routes['expenses/delete'] = 'ExpensesCtrl/removeExpense';

    $this->routes['long-term-expenses'] = 'LongTermExpensesCtrl';
    $this->routes['long-term-expenses/types'] = 'LongTermExpensesCtrl/getTypes';
    $this->routes['long-term-expenses/view'] = 'LongTermExpensesCtrl/getLongTermExpenses';
    $this->routes['long-term-expenses/delete'] = 'LongTermExpensesCtrl/removeLongTermExpense';

    $this->routes['incomes'] = 'IncomesCtrl';
    $this->routes['incomes/types'] = 'IncomesCtrl/getTypes';
    $this->routes['incomes/view'] = 'IncomesCtrl/getIncomes';
    $this->routes['incomes/delete'] = 'IncomesCtrl/removeIncome';

    $this->routes['debts'] = 'DebtsCtrl';
    $this->routes['debts/types'] = 'DebtsCtrl/getTypes';
    $this->routes['debts/view'] = 'DebtsCtrl/getDebts';
    $this->routes['debts/delete'] = 'DebtsCtrl/removeDebt';

    $this->routes['claims'] = 'ClaimsCtrl';
    $this->routes['claims/types'] = 'ClaimsCtrl/getTypes';
    $this->routes['claims/view'] = 'ClaimsCtrl/getClaims';
    $this->routes['claims/delete'] = 'ClaimsCtrl/removeClaim';

    $this->routes['types'] = 'TypesCtrl';
    $this->routes['types/update'] = 'TypesCtrl/updateTypes';
  }

  public static function getInstance(){
    if(self::$instance === false){
      self::$instance = new Router;
    }

    return self::$instance;
  }

  public static function parseQueryGetArray($requestQuery = NULL){

    if($requestQuery == NULL){
      $requestQuery = $_SERVER['QUERY_STRING'];
    }

    $getArray = array();
    parse_str($requestQuery, $getArray);

    try {
      if(!isset($getArray['requri'])){
        throw new \Exception();
      }

      if(empty($getArray['requri'])){
        throw new \Exception();
      }

      return $getArray;
    }catch (\Exception $e){
      return false;
    }
  }

  public static function parseQueryPostArray($requestParams = NULL){

    if($requestParams == NULL){
        $requestParams = file_get_contents('php://input');
    }

    if($params = json_decode($requestParams, true)){
        return $params;

        //html special chars xss protection
        foreach ($params as $key => $param) {
          $params[$key] = htmlspecialchars($param);
        }

    }else{
      return [];
    }
  }

  public function routeDefault(){
    if($queryArray = $this::parseQueryGetArray($_SERVER['QUERY_STRING'])){

      $requri = $queryArray['requri'];
      unset($queryArray['requri']);
      $this->get = $queryArray;
      $this->post = $this->parseQueryPostArray();

      $result = $this->route($requri, $this->get, $this->post);

      if(!$result && !is_array($result)){
        http_response_code(400); //bad request
        die();
      }
      return $result;

    }else{
      http_response_code(400); //bad request
      die();
    }
  }

  public function route($request, array $get, array $post){

    if(isset($this->routes[$request])){
      $controllerPathArr = explode("/", $this->routes[$request]);
      $controllerName = $controllerPathArr[0];

      $className = "\\app\\ledger\\ctrl\\" . $controllerName;
      $controller = new $className($get, $post);

      if($controller instanceof AbstractBaseCtrl){
            if(isset($controllerPathArr[1])){
              $method = $controllerPathArr[1];

              return $controller->$method();
            }else{
              return $controller->execute();
            }
      }else{
        return false;
      }

      return true;
    }else{
      return false;
    }
  }

}
