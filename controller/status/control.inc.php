<?php
class Controller_status{
  function status(){
    $ret=array(
      'app_display_name'=>Config::get('app_display_name')
      ,'authentication'=>Config::get('authentication')
      ,'base_url'=>Config::get('baseurl')
      ,'theme'=>Config::get('theme')
    );
    return $ret;
  }
}
