<?php
namespace app\ledger\core;
use app\ledger\core\Route;

class Router {
  private static $instance = false;
  private $routes = [];
  private $get = [];
  private $post = [];

  private function __construct(){
    $this->routes['/'] = 'loginCtrl';
    $this->routes['login'] = 'loginCtrl';
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

    if()

    file_get_contents('php://input')

    $params = json_decode(file_get_contents('php://input'), true);
    return $params;
  }

  public function routeDefault(){
    if($queryArray = $this::parseQueryGetArray($_SERVER['QUERY_STRING'])){

      unset($queryArray['requri']);
      $this->$get = $queryArray;
      $this->$post = $this->parseQueryPostArray();

      $this->route($queryArray['requri'], $this->$get, $this->$post);
    }else{
      http_response_code(400); //bad request
      die();
    }
  }

  public function route($request, $get, $post){

    if(isset($routes[$request])){
      $controllerName = $routes[$request];
      $className = "\\app\\ledger\\ctrl\\" . $controllerName;
      $controler = new $className($get, $post);
      $controler->execute();

      return true;
    }else{
      return false;
    }
  }

}
