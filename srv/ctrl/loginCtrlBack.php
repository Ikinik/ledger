<?php
namespace app\ledger\ctrl;

$params = json_decode(file_get_contents('php://input'),true);

try {
  if(!isset($params['email']) || !isset($params['pass'])){
    throw new \Exception('', 400); //bad request
  }

  if(empty($params['email']) || empty($params['pass'])){
    throw new \Exception('', 400); // bad request
  }

  if(!(($params['email'] == "adam.sorfa@gmail.com") && ($params['pass'] == 'password'))){
    throw new \Exception('', 403); //frobiden
  }else{
    echo "logged as " . $params['email'] . " " . $params['pass'] ;
  }
} catch (\Exception $e) {
  echo $e->getCode();
}
