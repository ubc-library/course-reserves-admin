<?php

class Controller_admin{

    /*############################################################################*/
    /*################## Displays accessible through admin() ###################*/
    /*############################################################################*/

    //public call, doesn't display though
    public function refresh(){
        $_utility = getModel('utility');
        $_utility->resetMemcache();
        redirect('/home.complete'); //rebuilds the memcache values
    }
}//end class