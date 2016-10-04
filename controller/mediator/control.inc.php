<?php class Controller_mediator{


    public function createItem()
    {
        /* Le Constant Stuff*/
        $template_vars             = array();
        $template_vars['template'] = 'json';
        header('Content-type:application/json');

        /* Le Dynamic Stuff */
        $form            = pv('bibdata'); //good
        $author          = pv('author'); //good
        $title           = pv('title'); //good
        $callnumber      = pv('callnumber'); //good
        $uri             = pv('uri'); //good
        $type            = pv('type'); //good
        $physical_format = pv('physical_format'); //good


        /* Le Condtional Stuff */
        $licr = getModel('licr'); //add in checks to only load if you got stuff from pv?

        $template_vars['json'] = $licr->getJSON('CreateItem', array(
            'title'         => $title
        , 'callnumber'      => $callnumber
        , 'bibdata'         => serialize($this->fixEncoding($form))
        , 'uri'             => $uri
        , 'type'            => $type
        , 'filelocation'    => $uri
        , 'author'          => $author
        , 'citation'        => ""
        , 'external_store'  => ""
        , 'physical_format' => $physical_format
        ));

        return $template_vars;
    }

    private function fixEncoding($form)
    {
        $ret = @json_encode($form);
        $e   = json_last_error();
        if ($e == JSON_ERROR_UTF8 && isset($form)) {
            if ($e) {
                $form = $this->array_recode('Latin1..UTF8', $form);
                $ret  = @json_encode($form);
                $e    = json_last_error();
            }
        }
        if ($e && ($e != JSON_ERROR_UTF8)) {
            error_log('JSON error not utf8 related: ' . $e);
        }
        unset($ret);

        return $form;
    }

    private function array_recode($fromto, $input)
    {
        if (!is_array($input)) {
            $uns = @unserialize($input);
            if (is_array($uns)) {
                $uns = array_recode($fromto, $uns);

                return serialize($uns);
            } else {
                @json_encode($input);
                if (json_last_error()) {
                    return recode_string($fromto, $input);
                } else {
                    return $input;
                }
            }
        } else {
            foreach ($input as $i => $v) {
                $input [$i] = array_recode($fromto, $v);
            }

            return $input;
        }
    }


    public function addItem(){

        /* Le Constant Stuff*/
        $template_vars=array();
        $template_vars['template']='json';
        header('Content-type:application/json');

        /* Le Dynamic Stuff */
        $form       =   pv('form');

        $title      =   pv('title');
        $callnumber =   pv('callnumber');
        $bibdata    =   serialize($form);
        $uri        =   $form['uri'];

        $type       =   pv('type');

        $citation   =   pv('citation');
        $external   =   pv('external');

        if($form['author']){
            $author     =   $form['author'];
        }
        else if($form['creator']){
            $author     =   $form['creator'];
        }
        else if($form['articleauthors']){
            $author     =   $form['articleauthors'];
        }
        else{
            $author     =   'not found';
        }


        /* Le Condtional Stuff */
        $licr=getModel('licr'); //add in checks to only load if you got stuff from pv?

        $results = $licr->getArray('CreateItem', array(
            'title'            => $title
        ,'callnumber'       => $callnumber
        ,'bibdata'          => $bibdata
        ,'uri'              => $uri
        ,'type'             => $type
        ,'filelocation'     => $uri
        ,'author'           => $author
        ,'citation'         => $citation
        ,'external_store'   => $external
        ));

        /* Le Processing Stuff */
        if(isset($results) && count($results)){
            $template_vars['json'] = json_encode($results);
        }
        else {
            $template_vars['json'] = json_encode("No Items Found");
        }

        return $template_vars;
    }
    public function requestItem(){

        /* Le Constant Stuff*/
        $template_vars=array();
        $template_vars['template']='json';
        header('Content-type:application/json');

        /* Le Dynamic Stuff */
        $course=pv('course');
        $item=pv('item_id');
        $loanperiod=pv('loanperiod');
        $puid=pv('requestor');

        /* Le Condtional Stuff */
        $licr=getModel('licr'); //add in checks to only load if you got stuff from pv?
        $results = $licr->getJSON('RequestItem', array(
            'course'            => $course
        ,'item_id'          => $item
        ,'loanperiod'       => $loanperiod
        ,'requestor'        => $puid
        ));
        $data = json_decode($results,true);
        /* Le Processing Stuff */
        if($data['success']){
            if(isset($data['data']) && count($data['data'])){
                $template_vars['json'] = json_encode($data['data']);
            }
        }
        else {
            $template_vars['json'] = json_encode($data['message']);
        }

        return $template_vars;
    }

    public function SetCIRequired(){
        /* Le Constant Stuff*/
        $template_vars=array();
        $template_vars['template']='json';
        header('Content-type:application/json');

        /* Le Dynamic Stuff */
        $course =   pv('c');
        $item   =   pv('i');
        $value  =   pv('v');

        /* Le Condtional Stuff */
        $licr=getModel('licr'); //add in checks to only load if you got stuff from pv?
        $results = $licr->getJSON('SetCIRequired', array(
            'course'            => $course
        ,'item'          => $item
        ,'required'     => $value
        ));

        //mail('skhanker@gmail.com','JSON - SetItemFileLocation', $results);

        $data = json_decode($results,true);
        /* Le Processing Stuff */
        if($data['success']){
            if(isset($data['data']) && count($data['data'])){
                $template_vars['json'] = json_encode(1);
            }
        }
        else {
            $template_vars['json'] = json_encode($data['message']);
        }

        return $template_vars;
    }

    public function SetCIStatus(){
        /* Le Constant Stuff*/
        $template_vars=array();
        $template_vars['template']='json';
        header('Content-type:application/json');

        /* Le Dynamic Stuff */
        $course =   pv('c');
        $item   =   pv('i');
        $value  =   pv('v');

        /* Le Condtional Stuff */
        $licr=getModel('licr'); //add in checks to only load if you got stuff from pv?
        $results = $licr->getJSON('SetCIStatus', array(
            'course'    => $course
        ,'item_id'  => $item
        ,'status'   => $value
        ));

        //mail('skhanker@gmail.com','JSON - SetItemFileLocation', $results);

        $data = json_decode($results,true);
        /* Le Processing Stuff */
        if($data['success']){
            if(isset($data['data']) && count($data['data'])){
                $template_vars['json'] = json_encode(1);
            }
        }
        else {
            $template_vars['json'] = json_encode($data['message']);
        }

        return $template_vars;
    }

    public function SetItemFileLocation(){

        /* Le Constant Stuff*/
        $template_vars=array();
        $template_vars['template']='json';
        header('Content-type:application/json');

        /* Le Dynamic Stuff */
        $item   =   pv('i');
        $value  =   pv('v');

        /* Le Condtional Stuff */
        $licr=getModel('licr'); //add in checks to only load if you got stuff from pv?
        $results = $licr->getJSON('SetItemFileLocation', array(
            'item_id'           => $item
        ,'filelocation'    => $value
        ));

        //mail('skhanker@gmail.com','JSON - SetItemFileLocation', $results);

        $data = json_decode($results,true);
        /* Le Processing Stuff */
        if($data['success']){
            if(isset($data['data']) && count($data['data'])){
                $template_vars['json'] = json_encode(1);
            }
        }
        else {
            $template_vars['json'] = json_encode($data['message']);
        }

        return $template_vars;
    }

    public function SetItemURI(){

        /* Le Constant Stuff*/
        $template_vars=array();
        $template_vars['template']='json';
        header('Content-type:application/json');

        /* Le Dynamic Stuff */
        $item   =   pv('i');
        $value  =   pv('v');

        /* Le Condtional Stuff */
        $licr=getModel('licr'); //add in checks to only load if you got stuff from pv?
        $results = $licr->getJSON('SetItemURI', array(
            'item_id'           => $item
        ,   'uri'               => $value
        ));

        //mail('skhanker@gmail.com','JSON - SetItemURI', $results);
        $data = json_decode($results,true);
        /* Le Processing Stuff */
        if($data['success']){
            if(isset($data['data']) && count($data['data'])){
                $template_vars['json'] = json_encode(1);
            }
        }
        else {
            $template_vars['json'] = json_encode($data['message']);
        }

        return $template_vars;
    }

    public function urlExists(){

        $exists = false;

        $url = pv('url');
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_exec($ch);
        if(curl_errno($ch)){
            //mail('skhanker@gmail.com','cURL Err No', curl_errno($ch));
        }
        if(curl_errno($ch)== 3) {
            $exists = -3;
        }
        if(curl_errno($ch)== 2 || curl_errno($ch)== 5 || curl_errno($ch)== 6) {
            $exists = -1;
        }
        if(curl_errno($ch)== 7 || curl_errno($ch)== 28 || curl_errno($ch)== 22) {
            $exists = 28;
        }
        else {
            $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($retcode >= 400){
                $exists = 400;
            }
            else if ($retcode == 200 || $retcode == 301 || $retcode == 302  || $retcode == 304 || $retcode == 307) {
                $exists = 200;
            }
        }
        curl_close($ch);

        $template_vars=array();
        $template_vars['template']='json';
        header('Content-type:application/json');
        $template_vars['json'] = json_encode($exists);

        return $template_vars;
    }

    public function AddCINote(){

        $content    = json_decode(pv('v'));
        $roles      = json_decode(pv('r'));

        $puid       = pv('p');
        $itemid     = pv('i');
        $courseid   = pv('c');

        $licr=getModel('licr');
        $data = json_decode($licr->getJSON('AddCINote', array('author_puid' => $puid, 'content' => $content,'roles_multi' => $roles, 'item_id' => $itemid, 'course' => $courseid)),true);

        $template_vars=array();
        $template_vars['template']='json';
        header('Content-type:application/json');
        if($data['success']){
            if(isset($data['data']) && count($data['data'])){
                $template_vars['json'] = json_encode(1);
            }
        }
        else {
            $template_vars['json'] = json_encode($data['message']);
        }

        return $template_vars;
    }



}
