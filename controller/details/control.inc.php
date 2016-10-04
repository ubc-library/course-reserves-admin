<?php

    /**
     * Class Controller_details
     */
    class Controller_details
    {


        /**
         * @return array
         */
        public function details()
        {
            $licr = getModel('licr');
            $template_vars = array();
            $template_vars['firstname'] = sv('userinfo')['firstname'];
            $template_vars['branches'] = $licr->getArray('ListBranches', NULL, TRUE);
            $template_vars['statuses'] = $licr->getArray('ListStatuses', NULL, TRUE);
            return $template_vars;
        }

        /*############################################################################*/
        /*############################## Calls to LiCR ###############################*/
        /*############################################################################*/

        /**
         * @return array
         */
        public function updatebibdata()
        {
            $_bibdata = getModel('bibdata');
            $template_vars = array();
            $template_vars['template'] = 'json';
            $template_vars['clf'] = FALSE;

            if (isset($_POST['bibdata'])) {
                $iid = pv('i');
                $result = $_bibdata->updateBibdata($iid, $_POST['bibdata']);
                $template_vars['success'] = $result['bibdata'] === 1 ? TRUE : FALSE;
                $template_vars['message'] = $result['message'];
                if ($template_vars['success']) {
                    $_docstore = getModel('docstore');
                    $res = $_docstore->setMetadata($iid);
                    if (!$res['status']) {
                        Reportinator::alertDevelopers('Update Metadata Failed', 'Could not update metadata for item ' . $iid . ' automatically after its bibdata was updated');
                    }
                }
            } else {
                $template_vars['success'] = FALSE;
                $template_vars['message'] = "Did not find bibdata";
            }


            $template_vars['json'] = json_encode(array('success' => $template_vars['success'], 'message' => $template_vars['message']));

            return $template_vars;
        }

        /**
         * @return array
         */
        public function set_ci_status()
        {
            $course = pv('c');
            $item = pv('i');
            $status = pv('s');

            $licr = getModel('licr');
            $template_vars = array();

            $result = json_decode($licr->getJSON('SetCIStatus', array('course' => $course, 'item_id' => $item, 'status' => $status)));
            $template_vars['success'] = (isset($result->success) ? $result->success : FALSE);
            $template_vars['message'] = (isset($result->message) ? $result->message : "An error occurred");
            $template_vars['clf'] = FALSE;
            $template_vars['json'] = json_encode(array('success' => $template_vars['success'], 'message' => $template_vars['message']));
            $template_vars['template'] = 'json';

            return $template_vars;
        }

        /**
         *
         */
        public function updatecifields()
        {
            //unused?
            /*
            $course = pv('c');
            $item   = pv('i');
            $start  = date('Y-m-d', strtotime(pv('s')));
            $end    = date('Y-m-d', strtotime(pv('e')));

            //$this->mailMe('My variables',serialize(array('course' => $course,'item_id' => $item, 'startdate' => $start, 'enddate' => $end)));

            $licr           =getModel('licr');
            $template_vars  = array();

            $result                     = json_decode($licr->getJSON('SetCIDates',array('course' => $course,'item_id' => $item, 'startdate' => $start, 'enddate' => $end)));
            $template_vars['success']   = (isset($result->success) ? $result->success : false);
            $template_vars['message']   = (isset($result->message) ? $result->message : "An error occurred");
            $template_vars['clf']       = false;
            $template_vars['json']      = json_encode(array('success' => $template_vars['success'], 'message' => $template_vars['message']));
            $template_vars['template']  = 'json';

            return $template_vars;*/
        }

        /*############################################################################*/
        /*############################# Calls within app #############################*/
        /*############################################################################*/

        /**
         * @param $subject
         * @param $body
         */
        private function mailMe($subject, $body)
        {
            #mail('skhanker@gmail.com', $subject, $body);
            error_log(json_encode(['sbj' => $subject, 'msg' => $body]));
        }

        /**
         * @param $type
         * @param $term
         */
        private function notFoundRedirect($type, $term)
        {
            ssv('message', "We could not find your $type using that url, please use the search form to locate your $type.");
            ssv('searchterm', $term);
            redirect('/search');
        }

        /*############################################################################*/
        /*################## Displays accessible through details() ###################*/
        /*############################################################################*/

        /**
         * @return array
         */
        public function item()
        {

            $licr = getModel('licr');

            //if no item found, redirect before loading anything
            $item = gv('id');
//        $info   = json_decode($licr->getJSON('GetItemInfo',array('item_id' => $item)), true);
            $info = $licr->getArray('GetItemInfo', array('item_id' => $item));
            if (!$info) {
                $this->notFoundRedirect('item', $item);
            }

            //we have an item to display
            $_utility = getModel('utility');
            $_docstore = getModel('docstore');
            $template_vars = array();

            //these check memcache first memcache, sets if dead
            $template_vars['brokenlinks'] = $_utility->getMenuBrokenLinks();
            $template_vars['newqcount'] = $_utility->getMenuNewItems();
            $template_vars['roles'] = $_utility->getList('roles', 'ListRoles');
            $template_vars['branches'] = $_utility->getList('branches', 'ListBranches');
            $template_vars['format'] = $_utility->getList('types', 'ListTypes');
            $template_vars['statuses'] = $_utility->getList('statuses', 'ListStatuses');
            $template_vars['loanperiods'] = $_utility->getList('loanperiods', 'ListLoanPeriods');
            $template_vars['parsed_statuses'] = $_utility->getParsedStatuses();

            $parsedTypes = $_utility->getParsedItemTypes();

            $template_vars['firstname'] = sv('userinfo')['firstname'];
            $template_vars['puid'] = sv('puid');
            $template_vars['userinfo'] = sv('userinfo');

            $template_vars['template'] = 'error';

            $template_vars['template_name'] = $template = $parsedTypes[$info['type_id']]['displayname'];
            $template_vars['template'] = 'default';

            $template_vars['template_types'] = $parsedTypes;
            $template_vars['info'] = $info;

            $template_vars['itemcourses'] = $licr->getArray('GetCoursesByItem', array('item' => $item));

            $parsedCourseItems = array();
            $index = 0;

            foreach ($template_vars['itemcourses'] as $cid => $row) {
                $parsedCourseItems[$index]['course_id'] = $cid;
                $parsedCourseItems[$index]['course_info'] = $row;
                $parsedCourseItems[$index]['course_instance'] = $licr->getArray('GetCourseInfo', array('course' => $cid));
                $parsedCourseItems[$index]['item_instance'] = $licr->getArray('GetCIInfo', array('item_id' => $item, 'course' => $cid));
                $parsedCourseItems[$index]['hidden'] = ($parsedCourseItems[$index]['item_instance']['hidden'] == 1);

                $next = $licr->getArray('GetNextStatuses', array('status' => $parsedCourseItems[$index]['item_instance']['status_id']), TRUE);
                foreach ($next as $key => $val) {
                    $parsedCourseItems[$index]['next_statuses'][$key] = $val['status'];
                }
                //TODO - roles_multi is hardcoded
                $parsedCourseItems[$index]['notes_staff'] = $licr->getArray('GetCINotes', array('item_id' => $item, 'course' => $cid, 'roles_multi' => 'Library Staff,Administrator'));
                $parsedCourseItems[$index]['notes_instructor'] = $licr->getArray('GetCINotes', array('item_id' => $item, 'course' => $cid, 'roles_multi' => 'Instructor,TA'));
                $parsedCourseItems[$index]['notes_students'] = $licr->getArray('GetCINotes', array('item_id' => $item, 'course' => $cid, 'roles_multi' => 'Student'));
                $cih = $licr->getArray('GetHistory', array('id' => $parsedCourseItems[$index]['item_instance']['instance_id'], 'table' => 'course_item'));
                $dsh = $_docstore->getHistory($item)['data'];
                
                foreach ($cih as &$entry) {
                    $parsedCourseItems[$index]['history'][strtotime($entry['time'])] = $entry;
                }
                unset($entry);
                
                foreach ($dsh as &$entry) {
                    $parsedCourseItems[$index]['history'][strtotime($entry['time'])] = $entry;
                }
                unset($entry);

                if (isset($parsedCourseItems[$index]['history'])) {
                    ksort($parsedCourseItems[$index]['history']);
                }

                $course_tags = $licr->getArray('ListTags', array('course' => $cid), TRUE);

                foreach ($course_tags as $key => $val) {
                    $parsedCourseItems[$index]['course_tags'][$val['tag_id']] = $val;
                }
                ++$index;
            }
            $template_vars['itemcourses'] = $parsedCourseItems;

            $_bibdata = getModel('bibdata');

            $bibdata = $_bibdata->getBibdata($info['bibdata'], $info['physical_format'], $item);

            $availabilityID = (isset($bibdata['bibdata']['availabilityid']) ? $bibdata['bibdata']['availabilityid'] : '');
            $template_vars['locations'] = FALSE;
            if (isset($availabilityID) && $availabilityID != '') {
                $_voyager = getModel('voyager');
                $template_vars['locations'] = $_voyager->getAvailability($availabilityID);
            }


            $template_vars ['bibdata'] = $bibdata ['bibdata'];
            $template_vars ['field_mapping'] = $bibdata ['fieldmap'];
            $template_vars ['fieldtitles'] = $bibdata ['fieldtitles'];

            if(array_key_exists('item_title', $bibdata['bibdata']) && !empty($bibdata['bibdata']['item_title'])){
                $template_vars['_titletag'] =   "i".$info['item_id'] . " - " .$bibdata['bibdata']['item_title'];
            }

            $template_vars['uri'] = $info['uri'];
            $template_vars['uri_dead'] = ($_utility->checkURIExists($info['uri']) === -1) ? TRUE : FALSE;
            if ($template_vars['uri_dead']) {
                if (!preg_match('/^https?:/', $template_vars['uri'])) {
                    error_log('Probably bad URI ' . $template_vars['uri'] . ' in item ' . $info['item_id']);
                } else {
                    error_log('URI is Dead: ' . $template_vars['uri']);
                }
            }
            if (isset($info['additional access'])) {
                $template_vars['addurls'] = $info['additional access'];
            }

            $template_vars['typeid'] = $info['type_id'];
            $template_vars['type'] = $template_vars['format'][$info['type_id']];
            $template_vars['physical_format'] = $info['physical_format'];
            $template_vars['itemid'] = $info['item_id'];
            $template_vars['hash'] = $info['hash'];
            $template_vars['gourl'] = $info['shorturl'];
            $template_vars['itemhistory'] = $licr->getArray('GetHistory', array('id' => $item, 'table' => 'item'));


            $template_vars['users'] = array();
            foreach ($template_vars['itemhistory'] as $record) {
                $template_vars['users'][$record['puid']] = $licr->getArray('GetUserInfo', array('puid' => $record['puid']), TRUE);
            }

            //DocStore
            $template_vars['isCopyright'] = ($template_vars['typeid'] == 1) ? TRUE : FALSE;
            $template_vars['copyrightStatus'] = $_docstore->getCopyrightStatus($item)['data'];
            $template_vars['copyrightDetails'] = $_docstore->getCopyrightDetails($item)['data'];
            
            $notes = $_docstore->getCopyrightAddenda($item, 0);
            $template_vars['copyrightAddenda'] = isset($notes['status']) ? $template_vars['copyrightAddenda'] = explode("--break--", $notes['data']) : 'no previous copyright notes';
    
            $notes = $_docstore->getCopyrightNotes($item);
            $template_vars['copyrightNotes'] = isset($notes['status']) ? $notes['data'] : 'no previous copyright notes';
            
            $template_vars['copyrightAddendaCS'] = $_docstore->getCopyrightAddenda($item, 1)['data'];
            $template_vars['copyrightTypes'] = json_decode($_docstore->getCopyrightTypeList()['json'], TRUE);


            //get Init arrays
            $template_vars['physical_mappings'] = $_utility->getInitArray('physical_format_map');
            $template_vars['physical_mappings_titles'] = $_utility->getInitArray('physical_format_display_titles');
            $template_vars['default_fields'] = $_utility->getInitArray('submit_defaults');
            $template_vars['required_fields'] = $_utility->getInitArray('submit_types')[(string)$template_vars['physical_format']];
            $template_vars['override_fields'] = $_utility->getInitArray('submit_overrides')[(string)$template_vars['physical_format']];


            //Reportinator::alertDevelopers('Viewed Item',"Viewed item $item"); // works

            return $template_vars;
        }


        /**
         * @return array
         */
        public function course()
        {

            $licr = getModel('licr');
            $course = gv('id');
            $info = $licr->getArray('GetCourseInfo', array('course' => $course));

            //if no course, redirect before making anything else load
            if (!$info) {
                $this->notFoundRedirect('course', $course);
            }

            $_utility = getModel('utility');
            $template_vars = array();

            //Memcached variables
            $template_vars['roles'] = $_utility->getList('roles', 'ListRoles');

            $template_vars['info'] = $info;

            //TODO - get rid of the below assumption... find name some other way
            //badAssumptionHere: 0 - Semester  | 1 - Course Code | 2 - Section | 3 - Course Name | 4 - Lecturer
            $badAssumptionHere = explode('-', $info['title']);
            if(is_array($badAssumptionHere) && array_key_exists(3, $badAssumptionHere)) {
                $template_vars['courseName'] = $badAssumptionHere[3];
            } else {
                $template_vars['courseName'] = '';
            }


            //from below is safe
            $safeAssumption = explode('.', $info['lmsid']);
            if(is_array($safeAssumption) && array_key_exists(5, $safeAssumption)) {
                $template_vars['semester'] = $safeAssumption[5];
            } else {
                $template_vars['semester'] = '';
            }
            
            $template_vars['defaultBranch'] = $info['default_branch_id'];
            $template_vars['courseCode'] = $info['coursecode'];
            $template_vars['courseNumber'] = $info['coursenumber'];
            $template_vars['section'] = $info['section'];
            $template_vars['externalId'] = $info['lmsid'];
            $template_vars['externalTitle'] = $info['title'];
            $template_vars['active'] = $info['active'];

            $template_vars['courseId'] = $course;

            //because twig might break
            if (!isset($template_vars['lecturer'])) {
                $template_vars['lecturer'] = array();
            }

            $template_vars['duration'] = date("j-M-y", strtotime($info['startdate'])) . ' to ' . date("j-M-y", strtotime($info['enddate']));

            $users = $licr->getArray('ListUsers', array('course' => gv('id'), 'role' => ''), TRUE);
            $parsedUsers = array();
            if (isset($users)) {
                $roleLookup = array(
                    "UBC_ISS"              => "ISS" //good
                    , "UBC_TA"             => "Instructor" //good
                    , "UBC_Sec_Instructor" => "Instructor" //good
                    , "UBC_Instructor"     => "Instructor" //good
                    , "UBC_Grader"         => "Instructor" // good-ish
                    , "UBC_CourseBuilder"  => "Instructor" //good
                    , "UBC_Auditor"        => "Student" //good
                    , "P"                  => "Student" //what even is this???
                    , "S"                  => "Student" // good
                    , '(Ares Import)'      => "Student" // almost certainly wrong, also why are we using SIS roles?? I thought they were display-only
                );
                foreach ($users as $k => &$user) {
                    $parsedUsers[$user['role']][] = array(
                        'name'        => $user['firstname'] . ' ' . $user['lastname']
                        , 'puid'      => $k
                        , 'libraryid' => (!isset($user['libraryid']) || $user['libraryid'] == '' ? '---' : $user['libraryid'])
                        , 'role'      => $user['sis_role'] != "" ? $roleLookup[$user['sis_role']] : $user['role']
                        , 'roleid'    => $user['role_id']
                        , 'email'     => (!isset($user['email']) || $user['email'] == '' ? '' : $user['email'])
                    );
                    if (($user['sis_role'] != "" ? $roleLookup[$user['sis_role']] : $user['role']) === "Instructor") {
                        $template_vars['lecturer'][] = $user['lastname'] . ', ' . $user['firstname'];
                    }
                }

                $template_vars['lecturer'] = implode("; ", $template_vars['lecturer']);
                unset($users);
                $parsedUsers = array_merge(array("Instructor" => array(), "TA" => array(), "Student" => array()), $parsedUsers);
            }

            $template_vars['available_items'] = $licr->getArray('ListCIs', array('course' => $course, 'visible' => 1));

            foreach ($template_vars['available_items'] as $iid => &$item) {
                $bibdata = unserialize($item['bibdata']);
                $template_vars['available_items'][$iid]['collection'] = isset($bibdata['collection_title']) ? $bibdata['collection_title'] : '';
                $template_vars['available_items'][$iid]['bibdata'] = $bibdata;
            }
            unset($item);

            $unavailable = $licr->getArray('ListCIs', array('course' => $course, 'visible' => 0));
            $template_vars['unavailable_items_cancelled'] = array();
            $template_vars['unavailable_items_not_cancelled'] = array();


            foreach ($unavailable as $iid => &$item) {
                $bibdata = unserialize($item['bibdata']);
                $unavailable[$iid]['collection'] = isset($bibdata['collection_title']) ? $bibdata['collection_title'] : '';
                $unavailable[$iid]['bibdata'] = $bibdata;
                if($unavailable[$iid]['cancelled']){
                    $template_vars['unavailable_items_cancelled'][$iid]=$unavailable[$iid];
                }else{
                    $template_vars['unavailable_items_not_cancelled'][$iid]=$unavailable[$iid];
                }
            }
            unset($item);

            $template_vars['firstname'] = sv('userinfo')['firstname'];
            $template_vars['branches'] = $licr->getArray('ListBranches', NULL, TRUE);
            $template_vars['enrolments'] = $parsedUsers;
            $template_vars['_titletag'] =   'c'.$course.' - '.$template_vars['externalTitle'];

            $template_vars['template'] = 'course';
            return $template_vars;
        }

        /**
         * @return array
         */
        public function user()
        {

            $licr = getModel('licr');
            $_utility = getModel('utility');
            $template_vars = array();

            //Memcached variables
            $template_vars['brokenlinks'] = $_utility->getMenuBrokenLinks();
            $template_vars['newqcount'] = $_utility->getMenuNewItems();
            $template_vars['firstname'] = sv('userinfo')['firstname'];
            $template_vars['id_groups'] = &$idbox_groups;


            $template_vars['user'] = $licr->getArray('GetUserInfo', array('puid' => gv('id')), TRUE);
            $courses = $licr->getArray('ListUserCourses', array('puid' => gv('id')));
            $parsedCourses = array();
            if (isset($courses)) {

                foreach ($courses as $k => &$course) {
                    if ($course['active']) {
                        $parsedCourses[] = array(
                            'courseid'     => $k
                            , 'coursename' => $course['title']
                            , 'semester'   => (preg_match("/2[0-9]{3}[W|S][0-9]/", $course['lmsid'], $match) ? $match[0] : '---')
                            , 'role'       => $course['role_name']
                        );
                    } else {
                    }
                }
                unset($courses);
            }
            $template_vars['courses'] = $parsedCourses;
            $template_vars['template'] = 'user';

            $template_vars['_titletag'] =   'u'.strtoupper(gv('id'));

            return $template_vars;
        }
    }//end class
