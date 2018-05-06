<?php
use app\ledger\core\Router;
use app\ledger\core\CONFIG;

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'core'. DIRECTORY_SEPARATOR .'CONFIG.php');

spl_autoload_register(function ($className){

  $namespace = explode('\\', $className);
  if((count($namespace) > 2) && ($namespace[0] == "app") && ($namespace[1] == "ledger")){
    $namespace = array_slice($namespace, 2);
    $path =  CONFIG::APP_DIR . implode(DIRECTORY_SEPARATOR, $namespace) . '.php';

    include_once($path);
    return true;
  }else{
    return false;
  }
});

$router = Router::getInstance();

ob_start();
header('Content-Type: application/json');
echo json_encode($router->routeDefault(), 512);
ob_end_flush();
