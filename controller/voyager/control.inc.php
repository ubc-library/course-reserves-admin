<?php
class Controller_voyager{
  public function __construct(){
    $this->model=getModel('voyager');
  }
  
  public function call($command, $params) {
    return $this->model->call($command, $params);
  }

  public function getJSON($command, $params) {
    return $this->call($command, $params);
  }

  public function getArray($command, $params) {
    $res = $this->call($command, $params);
    $dec = json_decode($res, TRUE);
    return $dec['data'];
  }
}
