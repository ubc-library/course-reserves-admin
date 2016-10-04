<?php

    class Controller_search
    {

        public function __construct()
        {
            $this->_licr = getModel('licr');
            $this->data = array();
            $this->data['template'] = 'results';
        }

        public function search()
        {
            //error_log("Index.php POST data: " . var_export($_POST, true));
            if (!$this->isValidSearchterm(pv('ss'))) {
                return $this->data;
            }

            $search = $this->getSearchterm(pv('ss'));

            if ($this->tryItemRedirect($search)) {
                return $this->data;
            } elseif ($this->tryCourseRedirect($search)) {
                return $this->data;
            } elseif ($this->tryUserRedirect($search)) {
                return $this->data;
            };

            //what will be returned
            $this->data['items'] = $this->data['courses'] = $this->data['users'] = array();
            $this->data['itemCount'] = $this->data['courseCount'] = $this->data['userCount'] = 0;

            //stats and misc
            $this->data['coursetimetaken'] = 0;

            //  START SEARCHING
            $searchItems = $searchCourses = $searchUsers = TRUE;

            $search = pv('ss');

            if (preg_match('/^item:(.*)$/', $search, $matches) && count($matches) === 2) {
                $searchCourses = $searchUsers = FALSE;
                $search = trim($matches[1]);
                $this->tryItemRedirect('i' . $search);
            } elseif (preg_match('/^course:(.*)$/', $search, $matches) && count($matches) === 2) {
                $searchItems = $searchUsers = FALSE;
                $search = trim($matches[1]);
                $this->tryCourseRedirect('c' . $search);
            } elseif (preg_match('/^user:(.*)$/', $search, $matches) && count($matches) === 2) {
                $searchItems = $searchCourses = FALSE;
                $search = trim($matches[1]);
                $this->tryUserRedirect('u' . $search);
            }

            //Status
            $this->data['searchterm'] = $search;
            $this->data['cached'] = '';


            $time_start = microtime(TRUE);
            $itemResult=array();
            $courseResult=array();
            $userResult=array();

            if ($searchItems) {
                $itemResult = $this->searchItems($this->data['searchterm']);
                $this->data['items'] = $itemResult['data'];
                $this->data['itemCount'] = count($itemResult['data']);
                $this->data['itemTime'] = $itemResult['time_taken'];
            } else {
                $this->data['items'] = array();
                $this->data['itemCount'] = 0;
                $this->data['itemTime'] = 0;
            }

            if ($searchCourses) {
                $courseResult = $this->searchCourses($this->data['searchterm']);
                $this->data['courses'] = $courseResult['data'];
                $this->data['courseCount'] = count($courseResult['data']);
                $this->data['courseTime'] = $courseResult['time_taken'];
            } else {
                $this->data['courses'] = array();
                $this->data['courseCount'] = 0;
                $this->data['courseTime'] = 0;
            }

            if ($searchUsers) {
                $userResult = $this->searchUsers($this->data['searchterm']);
                $this->data['users'] = $userResult['data'];
                $this->data['userCount'] = count($userResult['data']);
                $this->data['userTime'] = $userResult['time_taken'];
            } else {
                $this->data['users'] = array();
                $this->data['userCount'] = 0;
                $this->data['userTime'] = 0;
            }

            if ($itemResult ['cached'] || $courseResult['cached']) {
                $this->data['cached'] = 'Results from cache';
            }

            $time_end = microtime(TRUE);

            $this->data['coursetimetaken'] = round(100 * ($time_end - $time_start)) / 100;

            return $this->data;
        }

        public function tags()
        {
            /*
            //Init Data
            $licr=getModel('licr');
            $template_vars  = array();

            $itemResult                     = $this->searchItems($template_vars['searchterm']);
            $template_vars['items']         = $itemResult['data'];
            $template_vars['itemCount']     = count($itemResult['data']);
            $template_vars['itemTime']      = $itemResult['time_taken'];

            $courseResult                   = $this->searchCourses($template_vars['searchterm']);
            $template_vars['courses']       = $courseResult['data'];
            $template_vars['courseCount']   = count($courseResult['data']);
            $template_vars['courseTime']    = $courseResult['time_taken'];

            $userResult                     = $this->searchUsers($template_vars['searchterm']);
            $template_vars['users']         = $userResult['data'];
            $template_vars['userCount']     = count($userResult['data']);
            $template_vars['userTime']      = $userResult['time_taken'];

            if( $itemResult ['cached'] || $courseResult['cached']){
                $template_vars['cached'] = 'Results from cache';
            }

            $time_end = microtime(true);

            $template_vars['coursetimetaken'] = $time_end - $time_start;

            return $template_vars;
            */
        }

        private function tryItemRedirect($search)
        {
            if (preg_match('/^i([0-9]*)$/', $search, $matches) && count($matches) === 2) {
                error_log('trying item redirect');
                if ($this->_licr->getArray('GetItemInfo', array('item_id' => $matches[1]))) {
                    redirect('/details.item/id/' . $matches[1]);
                } else {
                    $this->data['searchterm'] = '***Direct ItemID entry with invalid id! ItemID:' . $matches[1] . '***';
                }
            }
            return FALSE;
        }

        private function tryCourseRedirect($search)
        {
            if (preg_match('/^c([0-9]*)$/', $search, $matches) && count($matches) === 2) {
                error_log('trying course redirect');
                if ($this->_licr->getArray('GetCourseInfo', array('course' => $matches[1]))) {
                    redirect('/details.course/id/' . $matches[1]);
                } else {
                    $this->data['searchterm'] = '***Direct CourseID entry with invalid id! CourseID:' . $matches[1] . '***';
                }
            }
            return FALSE;
        }

        private function tryUserRedirect($search)
        {
            if (preg_match('/^u([a-zA-Z0-9]{12})$/', $search, $matches) && count($matches) == 2) {
                error_log('trying user redirect');
                if ($this->_licr->getArray('GetUserInfo', array('course' => $matches[1]))) {
                    redirect('/details.user/id/' . $matches[1]);
                } else {
                    $this->data['searchterm'] = '***Direct PUID entry with invalid id! PUID:' . $matches[1] . '***';
                }
            }
            return FALSE;
        }

        private function isValidSearchterm($term)
        {
            if ($term == NULL || ctype_space($term)) {
                error_log("[$term] is not a valid search term");
                $this->data['searchterm'] = '*** No Search Term Entered ***';
                return FALSE;
            }
            return TRUE;
        }

        private function getSearchterm($term)
        {
            return trim(strtolower($term));
        }

        private function searchItems($search_string)
        {

            $time_start = microtime(TRUE);

            //Init Data
            $licr = getModel('licr');

            //cache results

            $cached = FALSE;
            $data = array();

//        $key = 'searchitem'.md5($search_string);

//        if (!($data = MC::get($key))) {
//            if (MC::getResultCode() == Memcached::RES_NOTFOUND) {
            $result = $licr->getArray('SearchItems', array('search_string' => $search_string));
            if (isset($result)) {
                $data = $this->processItems($result);
            }
            //$memcache->set($key, $data,900);
//            }
//        }
//        else {
//            $cached = true;
//        }

            $time_end = microtime(TRUE);
            return array('data' => $data, 'cached' => $cached, 'key' => $search_string, 'time_taken' => round(100 * ($time_end - $time_start)) / 100);
        }

        private function processItems($items)
        {

            $parsedItems = array();
            foreach ($items as $k => &$item) {

                $bibdata = @unserialize($item['bibdata']);

                if (!$bibdata) {
                    error_log('UNSERIALIZE ERROR');
                    error_log($item['bibdata']);
                    unset($items[$k]);
                    break;
                }

                if (in_array($item['physical_format'], array('electronic_article', 'pdf_article'))) {
                    $collection = isset($bibdata['journal_title']) ? $bibdata['journal_title'] : '---';
                } else {
                    if (in_array($item['physical_format'], array('book_chapter', 'ebook_chapter', 'pdf_chapter'))) {
                        $collection = isset($bibdata['collection_title']) ? $bibdata['collection_title'] : '---';
                    } else {
                        $collection = '---';
                    }
                }

                $journal_volume = array_key_exists('journal_volume', $bibdata) ? $bibdata['journal_volume'] : NULL;
                $journal_issue = array_key_exists('journal_issue', $bibdata) ? $bibdata['journal_issue'] : NULL;
                $item_edition = array_key_exists('item_edition', $bibdata) ? $bibdata['item_edition'] : NULL;

                $parsedItems[] = array(
                    'itemid'            => $k
                    , 'callnumber'      => $item['callnumber']
                    , 'title'           => $item['title']
                    , 'collection'      => $collection
                    , 'author'          => $item['author']
                    , 'physical_format' => $item['physical_format']
                    , 'journal_volume'  => $journal_volume
                    , 'journal_issue'   => $journal_issue
                    , 'item_edition'    => $item_edition

                );

            }

            unset($item);

            return $parsedItems;
        }

        private function searchCourses($search_string)
        {

            $time_start = microtime(TRUE);

            //Init Data
            $licr = getModel('licr');

            //cache results

            $cached = FALSE;
            $key = 'searchcourse' . md5($search_string);

            if (!($data = MC::get($key))) {
                if (MC::getResultCode() == Memcached::RES_NOTFOUND) {
                    $result = $licr->getArray('SearchCourses', array('search_string' => $search_string, 'current' => FALSE, 'activeonly' => FALSE));
                    if (isset($result)) {
                        $data = $this->processCourses($result);
                    }
                    MC::set($key, $data, 900);
                }
            } else {
                $cached = TRUE;
            }
            $time_end = microtime(TRUE);
            return array('data' => $data, 'cached' => $cached, 'key' => $search_string, 'time_taken' => round(100 * ($time_end - $time_start)) / 100);
        }

        private function processCourses($courses)
        {
            $parsedCourses = array();
            foreach ($courses as $k => &$course) {
                $parsedCourses[] = array(
                    'courseid'     => $course['course_id']
                    , 'coursename' => $course['title']
                    , 'semester'   => (preg_match("/2[0-9]{3}[W|S][0-9]/", $course['lmsid'], $match) ? $match[0] : '---')
                    , 'branch'     => $course['branch']
                    , 'reserves'   => $course['total']
                );
            }
            unset($course);

            return $parsedCourses;
        }


        private function searchUsers($search_string)
        {

            $time_start = microtime(TRUE);
            $licr = getModel('licr');

            $users = $licr->getArray('SearchUsers', array('search_string' => $search_string));
            $data = array();

            if (isset($users)) {
                foreach ($users as $k => &$user) {
                    $data[] = array(
                        'name'        => $user['firstname'] . ' ' . $user['lastname']
                        , 'puid'      => $user['puid']
                        , 'libraryid' => $user['libraryid']
                        , 'email'     => (!isset($user['email']) || $user['email'] === ' ' ? '---' : $user['email'])
                    );
                }
                unset($user);
            }
            $time_end = microtime(TRUE);
            return array('data' => $data, 'key' => $search_string, 'time_taken' => round(100 * ($time_end - $time_start)) / 100);
        }


    }
