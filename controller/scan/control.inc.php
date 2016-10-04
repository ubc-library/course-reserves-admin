<?php

class Controller_scan
{
    public function scan()
    {
        return array(); // scanning is pretty much a javascript thing
    }

    public function acquire()
    {
        $item_id = gv('itemid');
        $target = gv('target');
        $status = false;
        switch ($target) {
            case 'shelf':
                $status = 'Available in Library Reserves';
                break;
            case 'stack':
                $status = 'Available in Library Stacks';
                break;
            default:
                $this->error('Invalid target status');
        }
        $licr = getModel('licr');
        $res = $licr->getArray('GetItemInfo', array('item_id' => $item_id));
        if (!$res) {
            $this->error('Item not found');
        }
        $course_ids = $res['course_ids'];
        $done = array();
        foreach ($course_ids as $course_id) {
//      $res=$licr->getArray('GetCIInfo',
//        array('course'=>$course_id,'item_id'=>$item_id)
//      );
//      if($res['status']=='In Stack Search'){
            $res = $licr->getArray('SetCIStatus',
                array('course' => $course_id, 'item_id' => $item_id, 'status' => $status)
            );
            if ($res) {
                $done[] = $course_id;
            }
//      }
        }
        if ($done) {
            if (count($done) > 1) {
                return_json(array('ok' => true, 'message' => "Updated to " . $status . " in courses " . implode(', ', $done)));
            } else {
                return_json(array('ok' => true, 'message' => "Updated to " . $status . " in course " . $done[0]));
            }
        }
        $this->error('No changes made.');
    }

    private function error($msg)
    {
        $ret = array('ok' => false, 'message' => $msg);
        header('Content-type: text/javascript');
        exit(json_encode($ret));
    }
}
