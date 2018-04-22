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
    $this->routes['expenses'] = 'ExpensesFromCtrl';
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

      if(!($result = $this->route($requri, $this->get, $this->post))){
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
      $controllerName = $this->routes[$request];
      $className = "\\app\\ledger\\ctrl\\" . $controllerName;
      $controler = new $className($get, $post);

      if($controler instanceof AbstractBaseCtrl){
            return $controler->execute();
      }else{
        return false;
      }

      return true;
    }else{
      return false;
    }
  }

}
