<?php

    class Controller_docstore {

        function getSource ()
        {

            if (!logged_in ()) {
                require_login ();
            }

            $iid = gv ('i');
            $_docstore = getModel ('docstore');

            $hash = $_docstore->getHashFromId ($iid);
            if (!isset($hash) || $hash == '') {
                $hash = '';
                Reportinator::alertDevelopers ('Item has a missing hash - Item doesn\'t exist', 'The user [' . sv ('puid') . '] tried to download a file that has no hash. Could not find a hash for the item: [' . $iid . ']');
            }

            $filename = $_docstore->getFilenameByHash ($hash);
            if (!isset($filename) || $filename == '') {
                //send error code 1 pdf
                if ($hash != '') {
                    //this is a major error. how does a record get created with a hash but not an encoded filename?
                    Reportinator::alertDevelopers ('Major Error - Item has a missing Filename', 'Could not find a filename for the item: [' . $iid . ']. A hash was found, and is: [' . $hash . ']');
                }
                $filename = 'errorpdfs/e1.pdf';
            }

            error_log ("Filename is: $filename");
            $file = Config::get('docstore_docs') . $filename;

            @ini_set('zlib.output_compression',0);
            @ini_set('implicit_flush',1);
            @ob_end_clean();
            set_time_limit(0);

            if (file_exists ($file)) {
                header ('Content-Encoding: none;');
                header ('X-Accel-Buffering: no');
                header ('Content-Description: DocStore File Access');
                header ('Content-Type: application/pdf');
                header ('Content-Disposition: attachment; filename=' . basename ($filename) . '.pdf');
                header ("Cache-Control:  max-age=1, must-revalidate");
                header ("Pragma: public");
                ob_end_flush ();
                flush ();
                readfile ($file);
                exit ();
            }

        }

        function get ()
        {

            if (!logged_in ()) {
                require_login ();
            }

            $iid = gv ('i');
            $_docstore = getModel ('docstore');

            $hash = $_docstore->getHashFromId ($iid);
            if (!isset($hash) || $hash == '') {
                echo "<h1>File Doesn't Exist</h1><br /><p>Please consult with your lecturer/administration and let them know this URL is broken.</p>";
                Reportinator::alertDevelopers ('Item has a missing hash', 'Could not find a hash for the item: [' . $iid . ']');
                exit;
            }

            $filename = $_docstore->getFilenameByHash ($hash);
            if (!isset($filename) || $filename == '') {
                echo "<h1>File Doesn't Exist</h1><br /><p>Please consult with your lecturer/administration and let them know this URL is broken.</p>";
                Reportinator::alertDevelopers ('Item has a missing filename', 'Could not find a filename for the item: [' . $iid . ']. A hash was found, and is: [' . $hash . ']');
                exit;
            }
            error_log ("Filename is: $filename");

            //$file = Config::get('store_folder') . "/$filename";
            $file = Config::get('docstore_docs')  . $filename;

            @ini_set('zlib.output_compression',0);
            @ini_set('implicit_flush',1);
            @ob_end_clean();
            set_time_limit(0);

            if (file_exists ($file)) {
                header ('Content-Encoding: none;');
                header ('X-Accel-Buffering: no');
                header ('Content-Description: DocStore File Access');
                header ('Content-Type: application/pdf');
                header ('Content-Disposition: attachment; filename=' . basename ($filename) . '.pdf');
                header ("Cache-Control:  max-age=1, must-revalidate");
                header ("Pragma: public");
                ob_end_flush ();
                flush ();
                readfile ($file);
                exit ();
            }
        }

        /**
         * @return array
         *
         * unlike create, receive will update all courses associated with an itemid (its receiving a new file)
         */

        function receive ()
        {

            $puid = $this->getPuid ();
            $iid = pv ('item_id');

            $_docstore = getModel ('docstore');

            // 1 - add document
            $success = $_docstore->addFile ($_FILES['uploadfile'], $puid, $iid);
            $t = [];
            if (!$success['status']) {
                //failures are always passed already json-encoded to bubble them up and out without processing
                $t['template'] = 'connect';
                $t['json'] = $success['json'];

                return $t;
            }
            $hash = $success['data'];
            $doAddActions = $success['isAdd'];

            //if($doAddActions){ //LOCRSUPP 390
            // 2 - push writes to licr
            $success = $_docstore->setURL ($_docstore->createURL ($hash), $iid);
            if (!$success['status']) {
                //failures are always passed already json-encoded to bubble them up and out without processing
                $t['template'] = 'connect';
                $t['json'] = $success['json'];

                return $t;
            }
            //}

            // 3 - pull licr metadata after writes
            $success = $_docstore->setMetadataByItemID ($iid);
            if (!$success['status']) {
                //failures are always passed already json-encoded to bubble them up and out without processing
                $t['template'] = 'connect';
                $t['json'] = $success['json'];

                return $t;
            }

            // 4 - request the item for the course (within docstore)
            $success = $_docstore->requestFileByItemID ($iid);
            if (!$success['status']) {
                //failures are always passed already json-encoded to bubble them up and out without processing
                $t['template'] = 'connect';
                $t['json'] = $success['json'];

                return $t;
            }

            // 5 - return hash
            $t['template'] = 'connect';
            $t['json'] = json_encode ($hash);

            return $t;
        }

        public function create ()
        {

            $puid = $this->getPuid ();
            $cid = pv ('course_id');
            $iid = pv ('item_id');

            $_docstore = getModel ('docstore');

            // 1 - add document
            $success = $_docstore->addFile ($_FILES['uploadfile'], $puid, $iid);
            $t = [];
            if (!$success['status']) {
                //failures are always passed already json-encoded to bubble them up and out without processing
                $t['template'] = 'connect';
                $t['json'] = $success['json'];

                return $t;
            }
            $hash = $success['data'];

            // 2 - push writes to licr
            $success = $_docstore->setURL ($_docstore->createURL ($hash), $iid);
            if (!$success['status']) {
                //failures are always passed already json-encoded to bubble them up and out without processing
                $t['template'] = 'connect';
                $t['json'] = $success['json'];

                return $t;
            }

            // 3 - pull licr metadata after writes
            $success = $_docstore->setMetadata ($cid, $iid);
            if (!$success['status']) {
                //failures are always passed already json-encoded to bubble them up and out without processing
                $t['template'] = 'connect';
                $t['json'] = $success['json'];

                return $t;
            }

            // 4 - request the item for the course (within docstore)
            $success = $_docstore->requestFile ($cid, $iid);
            if (!$success['status']) {
                //failures are always passed already json-encoded to bubble them up and out without processing
                $t['template'] = 'connect';
                $t['json'] = $success['json'];

                return $t;
            }

            // 5 - return hash
            $t['template'] = 'connect';
            $t['json'] = json_encode ($hash);

            return $t;
        }

        public function purgeCache ()
        {
            $_docstore = getModel ('docstore');
            $_docstore->deleteCache ();

            return [
                'template' => 'connect'
                ,
                'json'     => json_encode ([
                                               'success' => true,
                                               'message' => 'All cached PDFs Cleared'
                                           ])
            ];
        }

        public function purge ()
        {
            $_docstore = getModel ('docstore');
            $_docstore->deleteFiles (); //enable this.
            //$_docstore->deleteFilesList(); // disable this if the above is enabled, as well as the alert below
            //Reportinator::alertDevelopers('Enable LIVE DocStore Automatic Delete','Enable LIVE DocStore Automatic Delete in Controller::Docstore->purge()');
            return [
                'template' => 'connect'
                ,
                'json'     => json_encode ([
                                               'success' => true,
                                               'message' => 'A ticket will be submitted in LOCRSUPP when the action is completed.'
                                           ])
            ];
        }

        public function updateAllMetadata ()
        {
            $_docstore = getModel ('docstore');

            return [
                'template' => 'connect'
                ,
                'json'     => json_encode ([
                                               'success' => $_docstore->updateAllMetadata (),
                                               'message' => 'LOCR will email the results when finished'
                                           ])
            ];
        }

        public function updateStatus ()
        {
            $puid = $this->getPuid ();
            $sta = pv ('status');
            $iid = pv ('item_id');
            $cid = pv ('course_id');

            $_docstore = getModel ('docstore');
            $success = $_docstore->setCopyrightStatus ($iid, $sta, $puid)['status'];
            $_docstore->deleteCacheById ($iid);

            switch ($sta) {
                case 2:
                case 3:
                    $fds = 1;
                    $trs = 0;
                    break;
                case 4:
                    $fds = 0;
                    $trs = 1;
                    break;
                case 5:
                    $fds = 0;
                    $trs = 0;
                    break;
                default:
                    $fds = 0;
                    $trs = 0;
            }


            $_licr = getModel ('licr');

            $fairdealing = $_licr->getArray ('SetCIField', [
                'course'  => $cid,
                'item_id' => $iid,
                'field'   => 'fairdealing',
                'value'   => $fds
            ]);

            $transactional = $_licr->getArray ('SetCIField', [
                'course'  => $cid,
                'item_id' => $iid,
                'field'   => 'transactional',
                'value'   => $trs
            ]);

            $t = [];
            $t['template'] = 'connect';
            $t['json'] = json_encode (['success' => $success]);

            return $t;
        }

        public function updateDetails ()
        {
            $puid = $this->getPuid ();
            $pc = pv ('page_count');
            $wc = pv ('work_count');
            $iid = pv ('item_id');

            $_docstore = getModel ('docstore');
            $success = $_docstore->setCopyrightDetails ($iid, $pc, $wc, $puid)['status'];
            $_docstore->deleteCacheById ($iid);

            $t = [];
            $t['template'] = 'connect';
            $t['json'] = json_encode (['success' => $success]);

            return $t;
        }
    
        public function upsertDetails ()
        {
            $puid = $this->getPuid ();
            
            $iid = pv ('item_id');
            
            $formData = [
                'work_count' => pv('work_count'),
                'page_count' => pv('page_count'),
                'cost' => pv('cost'),
                'currency' => pv('currency'),
                'paid_amount' => pv('paid_amount'),
                'paid_date' => pv('paid_date'),
                'exchange_rate' => pv('exchange_rate'),
                'rightsholder' => pv('rightsholder'),
                'rights_uri' => pv('rights_uri')
            ];
        
            error_log(json_encode($formData));
            
            $_docstore = getModel ('docstore');
            $success = $_docstore->upsertCopyrightDetails ($iid, $puid, $formData)['status'];
            $_docstore->deleteCacheById ($iid);
        
            $t = [];
            $t['template'] = 'connect';
            $t['json'] = json_encode (['success' => $success]);
        
            return $t;
        }
    
        public function updateNote ()
        {
            $puid = $this->getPuid ();
            $note = pv ('c');
            $iid = pv ('i');
            $note_id = pv ('noteid', false);
            $ics = pv ('a', false);
        
            $_docstore = getModel ('docstore');
            if ($ics == true ){
                $success = $_docstore->setCopyrightAddenda ($iid, $note, $puid, $ics)['status'];
                $_docstore->deleteCacheById ($iid);
            } else {
                try {
                    $success = $_docstore->upsertCopyrightNotes($iid, $puid, $note, $note_id)['status'];
                } catch (\Exception $e) {
                    error_log($e->getMessage());
                }
            }
        
            $t = [];
            $t['template'] = 'connect';
            $t['json'] = json_encode (['success' => $success]);
        
            return $t;
        }


        public function getHistory ()
        {
            $iid = pv ('i');
            $_docstore = getModel ('docstore');
            $success = $_docstore->getHistory ($iid);

            $t = [];
            $t['template'] = 'connect';
            $t['json'] = json_encode (['success' => $success]);

            return $t;
        }

        private function getPuid ()
        {

            $initiator = pv ('initiator');

            if ($initiator === 'connect') {
                //mail('stefan.khan-kernahan@ubc.ca','DocStore Access Granted - Connect','Accessed DocStore via Connect');
                return pv ('puid');
            } else if ($initiator === 'client') {
                //mail('stefan.khan-kernahan@ubc.ca','DocStore Access Granted - Client','Accessed DocStore via Client');
                return sv ('puid');
            } else {
                mail ('stefan.khan-kernahan@ubc.ca', 'DocStore Access Blocked', 'Tried to access docstore without POSTing etc. Initiator was: ' . $initiator . ". Post was:" . serialize ($_POST));
                redirect ('/home');

                return false;//get ide to shut up
            }
        }

        public function migrate ()
        {
            
        }

        public function writeHistory ()
        {
            
        }

        public function writeMetadata ()
        {

            //load handles
            $dbdocs = new DocsPDO();

            //set fetch mode
            $dbdocs->setAttribute (PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            //prep statements
            $dlhist = [];

            try {
                $res = $dbdocs->query ("SELECT `item_id` FROM `docstore_licr_metadata`;") or die(print_r ($dbdocs->errorInfo (), true));
            } catch (PDOException $e) {
                error_log ($e->getMessage ());
                $dbdocs = null;
            }

            foreach ($res as $item) {
                array_push ($dlhist, $item['item_id']);
            }

            try {
                $irow = $dbdocs->query ("SELECT DISTINCT `item_id` FROM `docstore_licr`;") or die(print_r ($dbdocs->errorInfo (), true));
            } catch (PDOException $e) {
                error_log ($e->getMessage ());
                $dbdocs = null;
            }

            $maxQueries = 0;
            $_docstore = getModel ('docstore');
            foreach ($irow as $i) {
                $iid = $i['item_id'];
                if (!in_array ($iid, $dlhist) && $maxQueries < 100) {
                    $maxQueries++;
                    $_docstore->setMetadataByItemID ($iid);
                } elseif ($maxQueries >= 100) {
                    $dbdocs = null;
                    exit();
                }
            };
            $dbdocs = null;
        }

        public function findDuplicates ()
        {
            $now = time ();

            $items = [];
            //load handles
            $dblicr = new PDO(Config::get('licr_dbms') . ':host=' . Config::get('licr_host') . ';dbname=' . Config::get('licr_name'), Config::get('licr_user'), Config::get('licr_pass'));

            //set fetch mode
            $dblicr->setAttribute (PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            //$dbdocs->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            //prep statements
            $insert = $dblicr->prepare ("SELECT `instance_id`, `course_id`, `enddate` FROM `course_item` WHERE item_id = :i ORDER BY `instance_id` ASC");

            $derequest = [];

            foreach ($items as $iid) {
                try {
                    //insert values into production database
                    $insert->bindParam (':i', $iid, PDO::PARAM_INT);
                    $insert->execute ();
                } catch (PDOException $e) {
                    error_log ($e->getMessage ());
                    $dblicr = null;
                }

                $rows = $insert->fetchAll ();


                if (count ($rows) > 1) {
                    for ($i = 0; $i < count ($rows); $i++) {
                        if ($i > 0) {
                            $derequest[$rows[$i]['instance_id']] = [$rows[$i]['course_id'] => $iid];
                        }
                    }
                }
            };
            $dblicr = null;

            $_licr = getModel ('licr');

            $report = "Results\n\r";
            foreach ($derequest as $ci) {
                foreach ($ci as $course_id => $item_id) {
                    $imetadata = $_licr->getArray ('GetItemInfo', [
                        'item_id' => $item_id
                    ]);

                    if (!isset($imetadata) or !$imetadata) {
                        Reportinator::alertDevelopers ('Could not de-duplicate item', "Could not get item info for item id $item_id");
                    } else {
                        $res = $_licr->getArray (
                            'DerequestItem', [
                                               'course'  => $course_id,
                                               'item_id' => $item_id
                                           ]
                        );

                        $new_itemid = $_licr->getArray (
                            'CreateItem',
                            [
                                'title'           => $imetadata['title'],
                                'author'          => $imetadata['author'],
                                'callnumber'      => $imetadata['callnumber'],
                                'bibdata'         => $imetadata['bibdata'],
                                'uri'             => "docstore-skeleton-source-item-$item_id",
                                'type'            => 1,
                                'filelocation'    => "docstore-skeleton-source-item-$item_id",
                                'physical_format' => $imetadata['physical_format']
                            ]
                        );

                        $req = $_licr->getArray (
                            'RequestItem',
                            [
                                'course'     => $course_id,
                                'item_id'    => $new_itemid,
                                'loanperiod' => 'N/A',
                                'requestor'  => 'Y7WCPQZ1GO05'
                            ]
                        );

                        $report .= "Old ItemID: $item_id Created with NewItemID: $new_itemid and Requested with InstanceID: {$req['instance_id']}\n\r";
                    }
                }
            }
            Reportinator::alertDevelopers ("Dedupe Result", $report);
        }
    }//end class
