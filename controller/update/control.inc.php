<?php

class Controller_update{

    public function cidates(){
        $course = pv('c');
        $item   = pv('i');
        $typeId   = pv('type_id');
        $start  = date('Y-m-d', strtotime(pv('s')));
        $end    = date('Y-m-d', strtotime(pv('e')));

        $licr           = getModel('licr');


        $template_vars  = array();
        $result                     = json_decode($licr->getJSON('SetCIDates',array('course' => $course,'item_id' => $item, 'startdate' => $start, 'enddate' => $end)));

        error_log("the type_id is " . $typeId);

        if($typeId == 1) {
            $docstore       = getModel('docstore');
            $docstore->setExpirationDate($item, $course, $end);
        }

        $template_vars['success']   = (isset($result->success) ? $result->success : false);
        $template_vars['message']   = (isset($result->message) ? $result->message : "An error occurred");
        $template_vars['clf']       = false;
        $template_vars['json']      = json_encode(array('success' => $template_vars['success'], 'message' => $template_vars['message']));
        $template_vars['template']  = 'json';

        return $template_vars;
    }

    public function relativecidates(){
        $course = pv('c');
        $item   = pv('i');
        $start  = pv('s');
        $end    = pv('e');

        $licr           = getModel('licr');
        $template_vars  = array();

        $field          = 'relative_startdate';

        $result                     = json_decode($licr->getJSON('SetCIField',array('course' => $course,'item_id' => $item, 'field' => $field, 'value' => $start)));
        $template_vars['success']   = (isset($result->success) ? $result->success : false);
        $template_vars['message']   = (isset($result->message) ? $result->message : "An error occurred");


        $field          = 'relative_enddate';

        $result                     = json_decode($licr->getJSON('SetCIField',array('course' => $course,'item_id' => $item, 'field' => $field, 'value' => $end)));
        $template_vars['success']   = (isset($result->success) ? $result->success : false);
        $template_vars['message']   = (isset($result->message) ? $result->message : "An error occurred");


        $template_vars['clf']       = false;
        $template_vars['json']      = json_encode(array('success' => $template_vars['success'], 'message' => $template_vars['message']));
        $template_vars['template']  = 'json';

        return $template_vars;
    }

    public function ciloanperiod(){
        $course = pv('c');
        $item   = pv('i');
        $value  = pv('v');

        $licr           = getModel('licr');
        $template_vars  = array();

        $result                     = json_decode($licr->getJSON('SetCILoanPeriod',array('course' => $course,'item_id' => $item, 'loanperiod' => $value)));
        $template_vars['success']   = (isset($result->success) ? $result->success : false);
        $template_vars['message']   = (isset($result->message) ? $result->message : "An error occurred");


        $template_vars['clf']       = false;
        $template_vars['json']      = json_encode(array('success' => $template_vars['success'], 'message' => $template_vars['message']));
        $template_vars['template']  = 'json';

        return $template_vars;
    }

    public function cifield(){
        $course = pv('c');
        $item   = pv('i');
        $field  = pv('f');
        $value  = pv('v');

        $licr           = getModel('licr');
        $template_vars  = array();

        $result                     = json_decode($licr->getJSON('SetCIField',array('course' => $course,'item_id' => $item, 'field' => $field, 'value' => $value)));
        $template_vars['success']   = (isset($result->success) ? $result->success : false);
        $template_vars['message']   = (isset($result->message) ? $result->message : "An error occurred");


        $template_vars['clf']       = false;
        $template_vars['json']      = json_encode(array('success' => $template_vars['success'], 'message' => $template_vars['message']));
        $template_vars['template']  = 'json';

        return $template_vars;
    }

    public function cipickupbranch(){
        $course = pv('c');
        $item   = pv('i');
        $value  = pv('v');

        $licr           = getModel('licr');
        $template_vars  = array();

        $result                     = json_decode($licr->getJSON('SetCIPickupBranch',array('course' => $course,'item_id' => $item, 'branch' => $value)));
        $template_vars['success']   = (isset($result->success) ? $result->success : false);
        $template_vars['message']   = (isset($result->message) ? $result->message : "An error occurred");


        $template_vars['clf']       = false;
        $template_vars['json']      = json_encode(array('success' => $template_vars['success'], 'message' => $template_vars['message']));
        $template_vars['template']  = 'json';

        return $template_vars;
    }

    public function ciprocessingbranch(){
        $course = pv('c');
        $item   = pv('i');
        $value  = pv('v');

        $licr           = getModel('licr');
        $template_vars  = array();

        $result                     = json_decode($licr->getJSON('SetCIProcessingBranch',array('course' => $course,'item_id' => $item, 'branch' => $value)));
        $template_vars['success']   = (isset($result->success) ? $result->success : false);
        $template_vars['message']   = (isset($result->message) ? $result->message : "An error occurred");


        $template_vars['clf']       = false;
        $template_vars['json']      = json_encode(array('success' => $template_vars['success'], 'message' => $template_vars['message']));
        $template_vars['template']  = 'json';

        return $template_vars;
    }

    public function courseclone(){
        $from   = pv('f');
        $to     = pv('t');

        $licr=getModel('licr');
        $result = json_decode($licr->getJSON('CopyCourse', array('from' => $from, 'to' => $to)));

        $template_vars['success'] = (isset($result->success) ? $result->success : false);
        $template_vars['message'] = (isset($result->message) ? $result->message : "An error occurred");
        $template_vars['clf'] = false;
        $template_vars['json'] = json_encode(array('success' => $template_vars['success'], 'message' => $template_vars['message']));
        $template_vars['template']='json';

        return $template_vars;
    }

    public function fetchuser(){
        $puid = pv('p');
        $licr=getModel('licr');
        if (isset($puid)){
            $user = json_decode($licr->getJSON('GetUserInfo', array('puid' => $puid)),true)['data'];
        }
        if(isset($user) && !empty($user)){
            $userString = $user['lastname'].', '.$user['firstname'].' ('.$user['puid'].')';
        }
        else {
            if(strlen($puid)==12){
                $userString = "-- user not found --";
            }
            else {
                $userString = "-- user not found, puid should be 12 characters --";
            }
        }

        $template_vars=array();
        $template_vars['template'] = 'json';
        $template_vars['json']= json_encode(array('user' => $userString));
        return $template_vars;
    }

    public function fetchcourse(){
        $cid = pv('c');
        $licr=getModel('licr');
        if (isset($cid)){
            $course = json_decode($licr->getJSON('GetCourseInfo', array('course' => $cid)),true)['data'];
        }
        if(isset($course) && !empty($course)){
            $courseString = $course['title'];
        }
        else {
            if(strlen($cid)>5){
                $courseString = "-- course not found --";
            }
            else {
                $courseString = "-- course not found, id should be 5 - 7 characters --";
            }
        }

        $template_vars=array();
        $template_vars['template'] = 'json';
        $template_vars['json']= json_encode(array('title' => $courseString));
        return $template_vars;
    }

    public function register(){
        $course = pv('c');
        $puid = pv('p');
        $role = pv('r');
        $licr=getModel('licr');
        $template_vars=array();
        $template_vars['template'] = 'json';
        $template_vars['json']= $licr->getJSON('Register',array('course' => $course,'puid' => $puid,'role' =>$role));
        return $template_vars;
    }

    public function deregister(){
        $course = pv('c');
        $puid = pv('p');
        $licr=getModel('licr');
        $template_vars=array();
        $template_vars['template'] = 'json';
        $template_vars['json']= $licr->getJSON('Deregister',array('course' => $course,'puid' => $puid));
        return $template_vars;
    }

    public function idboxregister(){
        $puid   = pv('p');
        $roles  = json_decode(pv('r'),true);

        require_once (Config::get ( 'approot' ) . '/core/idboxapi.inc.php');

        $success = array();
        foreach ($roles as $role){
            $success[$role] = idboxCall ('AddToGroup', array('puid' => $puid,'group_name' =>$role));
        }

        $template_vars=array();
        $template_vars['template'] = 'json';
        $template_vars['json']= json_encode($success);
        return $template_vars;
    }

    public function idboxderegister(){
        $puid   = pv('p');
        $roles  = json_decode(pv('r'),true);

        require_once (Config::get ( 'approot' ) . '/core/idboxapi.inc.php');

        $success = array();
        foreach ($roles as $role){
            $success[$role] = idboxCall ('RemoveFromGroup', array('puid' => $puid,'group_name' =>$role));
        }

        $template_vars=array();
        $template_vars['template'] = 'json';
        $template_vars['json']= json_encode($success);
        return $template_vars;
    }

    public function fetchroles(){
        $licr=getModel('licr');
        $template_vars=array();
        $template_vars['template'] = 'json';
        $template_vars['json']= $licr->getJSON('ListRoles');
        return $template_vars;
    }

    public function fetchidboxroles(){
        require_once (Config::get ( 'approot' ) . '/core/idboxapi.inc.php');
        $groups = idboxCall ('ListAllGroups', array());
        $parsedGroups = array();
        foreach ($groups as $group){
            if(!(strpos($group,'CR-') === false)){
                $parsedGroups[] = $group;
            }
        }
        unset($groups);
        $template_vars['template'] = 'json';
        $template_vars['json']= json_encode($parsedGroups);
        return $template_vars;
    }

    public function fetchidboxuserroles(){
        $puid   = pv('p');
        require_once (Config::get ( 'approot' ) . '/core/idboxapi.inc.php');
        $groups = idboxCall ('ListGroups', array('puid' => $puid));
        $parsedGroups = array();
        foreach ($groups as $group){
            if(!(strpos($group,'CR-') === false)){
                $parsedGroups[] = $group;
            }
        }
        unset($groups);
        $template_vars['template'] = 'json';
        $template_vars['json']= json_encode($parsedGroups);
        return $template_vars;
    }


    public function topdetails(){
        $itemid = pv('i');
        $status = rawurldecode(pv('s'));
        $type = rawurldecode(pv('t'));
        $course = pv('c');

        $licr=getModel('licr');
        $setStatus = $setType = '';

        if (isset($type)){
            $setType = $licr->getArray('SetItemType', array('item_id' => $itemid, 'type' => $type));
        }

        if(isset($course)){
            $setStatus .= $licr->getArray('SetItemStatus', array('course' => $course, 'item_id' => $itemid, 'status' => $status));
        }
        else {
            $itemcourses = json_decode($licr->getJSON('GetCoursesByItem',array('item' => $itemid)), true)['data'];
            foreach ($itemcourses as $key => $value) {
                $setStatus .= $licr->getArray('SetItemStatus', array('course' => $key, 'item_id' => $itemid, 'status' => $status));
            }
        }

        $template_vars=array();
        $template_vars['template'] = 'json';
        $template_vars['json']= json_encode(array('messages' => $setStatus.' '.$setType));
        return $template_vars;
      }

    public function status(){
        $itemid = pv('i');
        $status = rawurldecode(pv('s'));
        $course = pv('c');
        $licr=getModel('licr');
        $setStatus = '';
        if(isset($course)){
            $setStatus .= $licr->getArray('SetItemStatus', array('course' => $course, 'item_id' => $itemid, 'status' => $status));
        }
        else {
            $itemcourses = json_decode($licr->getJSON('GetCoursesByItem',array('item' => $itemid)), true)['data'];
            foreach ($itemcourses as $key => $value) {
                $setStatus .= $licr->getArray('SetItemStatus', array('course' => $key, 'item_id' => $itemid, 'status' => $status));
            }
        }
        return json_encode(array('messages' => $setStatus));
      }

      public function type(){
        $itemid = pv('i');
        $type = rawurldecode(pv('t'));
        $licr=getModel('licr');
        $setType = '';

        if (isset($type)){
            $setType = $licr->getArray('SetItemType', array('item_id' => $itemid, 'type' => $type));
        }
        return json_encode(array('messages' => $setType));
      }


    public function addtag(){
        $courseid   = pv('c');
        $tagid      = pv('t');
        $itemid     = pv('i');

        $licr=getModel('licr');
        $result = json_decode($licr->getJSON('AddItemToTag', array('item_id' => $itemid, 'tag' => $tagid,'course' => $courseid)));

        $template_vars['success'] = (isset($result->success) ? $result->success : false);
        $template_vars['message'] = (isset($result->message) ? $result->message : "An error occurred");
        $template_vars['clf'] = false;
        $template_vars['json'] = json_encode(array('success' => $template_vars['success'], 'message' => $template_vars['message']));
        $template_vars['template']='json';

        return $template_vars;
    }


    public function batchstatus(){
        $process= json_decode(pv('items'),true);
        $status = pv('status');

        $toProcess = array();

        foreach($process as $item){
            if(strpos($item['courseid'],',')){
                $parts = explode(',', $item['courseid']);
                foreach($parts as $k => $courseid){
                    $toProcess[] = array('item_id' => $item['itemid'], 'course_id' => $courseid);
                }
            }
            else{
                $toProcess[] = array('item_id' => $item['itemid'], 'course_id' => $item['courseid']);
            }
        }

        $message                    = "Batch processing result: \r\n".$this->batchProcess($toProcess,$status);

        $template_vars['clf']       = false;
        $template_vars['json']      = json_encode(array('success' => true, 'message' => $message));
        $template_vars['template']  = 'json';

        return $template_vars;
    }

    private function batchProcess($arr, $status, $messages = ''){

        $item = array_pop($arr);

        if (count($arr)>0){
            $messages .=  $this->batchProcess($arr,$status,$messages);
        }

        $licr=getModel('licr');
        $result = json_decode($licr->getJSON('SetCIStatus', array('course' => $item['course_id'], 'item_id' => $item['item_id'],'status' => $status)));

        $messages .= 'Item: '.$item['item_id'].': '.(isset($result->message) ? $result->message : "An error occurred")."\r\n";

        return $messages;
    }

    public function courseitemnote(){
        $puid = sv('puid');
        $content = json_decode(pv('c'));
        $roles = json_decode(pv('r'));
        $itemid = pv('i');
        $courseid = pv('cid');

        $licr=getModel('licr');
        $result = json_decode($licr->getJSON('AddCINote', array('author_puid' => $puid, 'content' => $content,'roles_multi' => $roles, 'item_id' => $itemid, 'course' => $courseid)));

        $template_vars['success'] = (isset($result->success) ? $result->success : false);
        $template_vars['message'] = (isset($result->message) ? $result->message : "An error occurred");
        $template_vars['clf'] = false;
        $template_vars['json'] = json_encode(array('success' => $template_vars['success'], 'message' => $template_vars['message']));
        $template_vars['template']='json';

        return $template_vars;
    }

    public function editnote(){
        $nid = json_decode(pv('n'));
        $content = json_decode(pv('c'));
        $roles = json_decode(pv('r'));

        $licr=getModel('licr');
        $result = json_decode($licr->getJSON('UpdateNote', array('note_id' => $nid, 'content' => $content,'roles_multi' => $roles)));

        $template_vars['success'] = (isset($result->success) ? $result->success : false);
        $template_vars['message'] = (isset($result->message) ? $result->message : "An error occurred");
        $template_vars['clf'] = false;
        $template_vars['json'] = json_encode(array('success' => $template_vars['success'], 'message' => $template_vars['message']));
        $template_vars['template']='json';

        return $template_vars;
    }

	public function course(){
		$course = pv('c');
		$field  = pv('f');
		$value  = pv('v');

		$licr           = getModel('licr');
		$template_vars  = array();

		$result                     = json_decode($licr->getJSON('UpdateCourse',array('course' => $course, "$field" => $value)));
		$template_vars['success']   = (isset($result->success) ? $result->success : false);
		$template_vars['message']   = (isset($result->message) ? $result->message : "An error occurred");


		$template_vars['clf']       = false;
		$template_vars['json']      = json_encode(array('success' => $template_vars['success'], 'message' => $template_vars['message']));
		$template_vars['template']  = 'json';

		return $template_vars;
	}

    public function getIDBoxRoles () {
        require_once (Config::get ( 'approot' ) . '/core/idboxapi.inc.php');
        return idboxCall ('ListGroups', array ('puid' => gv('id')));
    }




}
