<?php

    /**
     * Class Controller_home
     */
    class Controller_home {

        /**
         * Controller_home constructor.
         */
        public function __construct ()
        {

            if (sv ('flash_redirect') !== null && sv ('flash_redirect') != '') {
                $this->flashRedirect (sv ('flash_redirect'));
            }
            $this->_licr = getModel ('licr');
            $allStats = $this->_licr->getArray ('ListStatuses', null, true);


            $this->listStats = [];
            foreach ($allStats as $stat) {
                $this->listStats[] = (int) $stat['status_id'];
            }
            //LICR-162
            $this->copyStats = [
                2,
                3,
                4,
                5,
                8,
                9,
                10,
                11,
                22,
                23,
                29,
                30,
                33,
                34,
                37,
                38,
                39,
                40
            ];//19 is physical

            $this->_utility = getModel ('utility');
            $this->_home = getModel ('home');
            $this->branch_id = gv ('location', sv ('location', -1));
        }

        /**
         * @param $view
         *
         * @return array
         */
        private function initTemplateVars ($view)
        {
            $statuses = $this->_licr->getArray ('ListStatuses', null, true);
            $statusListComplete = [];
            $statusListNew = [];
            $statusListInProcess = [];
            $statusNames = [];

            foreach ($statuses as $status) {
                $statusNames[$status['status_id']] = $status['status_name'];
            }

            if ($view === 'copyright') {
                foreach ($statuses as $status) {
                    if ($status['status_id'] < 100 && in_array ($status['status_id'], $this->copyStats)) {
                        $key = "statusList{$status['category']}";
                        array_push ($$key, $status['status_id']);
                    }
                }
            } else {
                foreach ($statuses as $status) {
                    if ($status['status_id'] < 100) {
                        $key = "statusList{$status['category']}";
                        array_push ($$key, $status['status_id']);
                    }
                }
            }


            $branches = $this->_licr->getArray ('ListBranches', null, true);
            $branch_map = [
                -1 => 'All'
            ];
            foreach ($branches as $branch_info) {
                $branch_map [$branch_info ['branch_id']] = $branch_info ['name'];
            }
            if (!is_numeric ($this->branch_id)) {
                $this->branch_id = array_search ($this->branch_id, $branch_map);
            }
            $tv = [
                'branch_id'          => $this->branch_id,
                'branches'           => $branches,
                'statuses'           => $statuses,
                'statuses_new'       => $statusListNew,
                'statuses_inprocess' => $statusListInProcess,
                'statuses_complete'  => $statusListComplete,
                'status_names'       => $statusNames,
                'types'              => $this->_licr->getArray ('ListTypes', null, true),
                'loanperiods'        => $this->_licr->getArray ('ListLoanPeriods', null, true),
                'recent_pickslip'    => $this->_utility->getPickslip (),
                'parsed_statuses'    => $this->_utility->getParsedStatuses (),
                'firstname'          => sv ('userinfo')['firstname']
            ];

            switch ($view) {
                case "view":
                    $tv['_titletag'] = $branch_map[$this->branch_id] . ' - New / In Process Requests';
                    break;

                case "complete":
                    $tv['_titletag'] = $branch_map[$this->branch_id] . ' - Completed Requests';
                    break;

                case "archive":
                    $tv['_titletag'] = $branch_map[$this->branch_id] . ' - Archived Requests';
                    break;

                case "copyright":
                    $tv['_titletag'] = $branch_map[$this->branch_id] . ' - Copyright Requests';
                    break;

            }
            $tv ['branch_map'] = $branch_map;
            $tv ['branch_name'] = $branch_map [$tv ['branch_id']];

            return $tv;
        }

        /**
         * @param $url
         *
         * @return bool
         */
        private function flashRedirect ($url)
        {
            if ($url !== null) {
                ssv ('flash_redirect', null);
                redirect ($url);
            }

            return false;
        }

        /**
         * @param string $section
         *
         * @return mixed
         */
        private function resolveRequests ($section = "NewAndInProcess")
        {
            //error_log('getting homepage data for section: ' . $section);
            if ((int) $this->branch_id !== -1) {
                $data = $this->_licr->getArray ('GetHomepageData' . $section, ['branch' => $this->branch_id]);
            } else {
                $data = $this->_licr->getArray ('GetHomepageData' . $section);
            }
            return $data;
        }

        /**
         * @param $template_vars
         * @param $requests
         * @param $statuses
         * @param $templateName
         */
        private function setTemplateQueues (&$template_vars, &$requests, $statuses, $templateName)
        {
            //Queue - New
            $processedRequest = $this->_home->getNewRequests ($requests['New'], $statuses, $templateName);
            $template_vars['newrecs'] = $processedRequest['records'];
            $template_vars['newqcount'] = $processedRequest['count'];

            //Queue - InProcess
            $processedRequest = $this->_home->getInProcessRequests ($requests['InProcess'], $statuses, $templateName);
            $template_vars['inprocs'] = $processedRequest['records'];
            $template_vars['inpqcount'] = $processedRequest['count'];

            //Queue - Complete
            $processedRequest = $this->_home->getCompleteRequests ($requests['Complete'], $statuses, $templateName);
            $template_vars['comrecs'] = $processedRequest['records'];
            $template_vars['comqcount'] = $processedRequest['count'];

            //Queue - Archive
            $processedRequest = $this->_home->getCompleteRequests ($requests['Archive'], $statuses, $templateName);
            $template_vars['arcrecs'] = $processedRequest['records'];
            $template_vars['arcqcount'] = $processedRequest['count'];
        }

        /**
         * @param        $stats
         * @param string $templateName
         * @param string $section
         *
         * @return array
         */
        private function generateView ($stats, $templateName = 'view', $section = 'NewAndInProcess')
        {
            $template_vars = $this->initTemplateVars ($templateName);
            $message = sv ('error');
            if (isset($message)) {
                $template_vars['error'] = sv ('error');
                ssv ('error', '');
            }
            $requests = $this->resolveRequests ($section);
            //var_dump($template_vars);
            //var_dump($requests);
            //var_dump($stats);
            $this->setTemplateQueues ($template_vars, $requests, $stats, $templateName);
            $template_vars['template'] = $templateName;

            return $template_vars;
        }

        /**
         * @return array
         */
        public function home ()
        {
            return $this->generateView (array_diff ($this->listStats, $this->copyStats), 'view', 'NewAndInProcess');
        }

        /**
         * @return array
         */
        public function copyright ()
        {
            $templateName = 'copyright';
            $template_vars = $this->initTemplateVars ($templateName);
            $message = sv ('error');
            if (isset($message)) {
                $template_vars['error'] = sv ('error');
                ssv ('error', '');
            }
            $template_vars['template'] = $templateName;

            #echo json_encode($template_vars); die;

            return $template_vars;
            #return $this->generateView($this->copyStats, 'copyright', '');
        }

        /**
         * @return array
         */
        public function complete ()
        {

            $templateName = 'complete';
            $template_vars = $this->initTemplateVars ($templateName);
            $message = sv ('error');
            if (isset($message)) {
                $template_vars['error'] = sv ('error');
                ssv ('error', '');
            }
            $template_vars['template'] = $templateName;

            return $template_vars;

            #return $this->generateView($this->listStats, 'complete', 'Complete');
        }

        /**
         * @return array
         */
        public function archive ()
        {
            $templateName = 'archive';
            $template_vars = $this->initTemplateVars ($templateName);
            $message = sv ('error');
            if (isset($message)) {
                $template_vars['error'] = sv ('error');
                ssv ('error', '');
            }
            $template_vars['template'] = $templateName;

            return $template_vars;

            #return $this->generateView($this->listStats, 'archive', 'Archive');
        }


        function getCourseItemsFormatted ()
        {

            $dataTableIndex = [
                'item_id', 'lms_id','callnumber', 'title','range','author','request_time','branch_id'
            ];

            $draw = gv ('draw', 1);

            $limit = gv ('length', 50);

            $offset = gv ('start', 0);

            $orderByArray = gv ('order', []);

            $orderBy = '';

            foreach ($orderByArray as $order) {
                $orderBy .= '`' . $dataTableIndex[$order['column']] . '`,' . strtoupper($order['dir']) . ';';
            }

            $orderBy = rtrim($orderBy,';');

            $branch_id = gv ('branch_id', -2);
            $type_ids = gv ('type_ids', -1);
            $status_ids = gv ('status_ids', -1);
            $isArchive = gv ('isArchive', 0);

            $params = [
                'isArchive' => $isArchive
            ];

            if ($branch_id >= 0) {
                $params['branch_id'] = $branch_id;
            }

            if ($type_ids !== -1) {
                $params['type_ids'] = $type_ids;
            }

            if ($status_ids !== -1) {
                $params['status_ids'] = $status_ids;
            }

            $params['limit'] = $limit;

            $params['offset'] = $offset;

            if($orderBy !== ''){
                $params['orderBy'] = $orderBy;
            }

            #error_log(json_encode($params));

            $data = $this->_licr->getArray ('GetCourseItems', $params);

            $rows = isset($data['rows']) ? $data['rows'] : [];

            $ret = [
                'draw'            => $draw,
                'recordsTotal'    => $data['count'],
                'recordsFiltered' => $data['count'],
                'data'            => []
            ];

            // notes
            // this query is based on the client passing status_ids and type_ids, the client should be able to process this accordingly
            foreach ($rows as $row) {

                $row['bibdata'] = unserialize (json_decode ($row['bibdata']));

                ##error_log (json_encode ($row['bibdata']));

                $ret ['data'] [] = [
                    'item_id'     => $this->getValue ($row ['item_id']),
                    'course_id'   => $this->getValue ($row ['course_id']),
                    'lms_id'      => $this->getValue ($row ['lms_id']),
                    'branch_id'   => $this->getValue ($row ['branch_id']),
                    'call_number' => $this->getValue ($row['bibdata']['item_callnumber']),
                    'title'       => $this->getValue ($row ['title']),
                    'page_range'  => $this->getValue ($row ['range']),
                    'author'      => $this->getValue ($row ['author']),
                    'requested'   => $this->getValue ($row ['request_time'])

                ];
            }

            header ('Content-Type: application/json');
            echo json_encode ($ret);

            exit;

            return $ret;

        }

        private function getValue ($data)
        {
            if (!empty($data)) {
                return $data;
            }

            return '';
        }
    }

