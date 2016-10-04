<?php

require(Config::get('approot').'core/db.inc.php');

class Generic{
  private $db;

  function __constructor($db){
    $dbfinder = new DBFinder(Config::get('dbuser'),Config::get('dbpass'),Config::get('dbname'),Config::get('environment'));
    $db = $dbfinder->getDB(Config::get('dbuser'),Config::get('dbpass'));
    $this->db=$db;
  }

  //various functions to set and get, etc.
}
