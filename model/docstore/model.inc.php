<?php
    
    use Carbon\Carbon;
    
    require_once Config::get('approot') . '/core/db.inc.php';
    
class Model_docstore
{

    protected $status = false;
    protected $message = '';
    protected $docstoredirectory = '';

    const ERROR_ITEM_NOT_FOUND = -1;

    public function __construct()
    {
        // find the config folder or default to the dev folder
        $this->docstoredirectory = (Config::get('docstore_docs') ? Config::get('docstore_docs') : '/usr/local/dev/docstore-docs/');
    }
    
    //adds a new file. cannot have existing hash
    public function addFile($file, $puid, $itemid)
    {
        $this->status = false;

        // 1 - check that the system is up
        //TODO - should move to constructor but need global listener to know to display fail event sent by model
        $isWriteable = $this->verifyDocstoreWriteable($this->docstoredirectory);
        if (!$isWriteable) {
            Reportinator::alertDevelopers('DcoStore System is not Writeable', 'The system failed to be able to write to the docstore file save path');

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }

        // 2 - check to see we have a legit file
        $isFile = $this->verifyFileUpload($file, $itemid);
        if (!$isFile) {
            Reportinator::alertDevelopers('Docstore - PDF File Upload Failed - Check File', "The file upload for item $itemid has failed.\n\r\n\rPlease try re-uploading the file.\n\r\n\rError Code: 1");
            Reportinator::alertCopyright('Docstore - PDF File Upload Failed', "The file upload for item $itemid has failed.\n\r\n\rPlease try re-uploading the file.\n\r\n\rError Code: 1");

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }

        // 3 - establish existence
        $filename = $this->getFilenameByItemId($itemid);
        $isAdd = ($filename === '');

        // 4 - add or replace file
        if ($isAdd) {
            //4 a - add file
            $filename = $file['name'];
            $savefile = $this->createSaveName($filename);
            $isStored = $this->storeFile($file['tmp_name'], $savefile, $puid, $itemid);
            $hash = $this->getHashFromId($itemid);
            if ($hash === '') {
                //adding completely new file
                $hash = $this->uniqueHash();
                $write = $this->addDocstoreRecord($itemid, $hash, $savefile);
            } else {
                //adding file to record that already has a hash
                $write = $this->addDocstoreFilename($itemid, $savefile);
            }

        } else {
            //4 a - replace file
            $hash = $this->getHashFromId($itemid);
            $isStored = $this->storeFile($file['tmp_name'], $filename, $puid, $itemid);
            $write = [
                'status' => true,
                'action' => 'REPLACED DocStore File'];
        }
        if (!$isStored) {
            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }

        // 5 - log action (this is whether or not writing database record succeeded)
        $this->logHistory($itemid, $hash, $write['action'], $puid);

        if (!$write['status']) {
            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }

        Reportinator::alertDevelopers(
            'Licr-DocStore - Connect - Created DocStore Record - '
            , 'A file has been created in DocStore with hash ' . $hash . ' Uploader PUID: ' . $puid
        );

        $this->status = true;

        //return the hash to the program that invoked this method
        return [
            'status' => $this->status,
            'data' => $hash,
            'isAdd' => $isAdd];
    }

    public function createURL($hash)
    {
        return Config::get('docstore_endpoint') . "/download.get/$hash";
    }

    public function deleteFilesAlert()
    {
        $this->now = time();
    }

    public function deleteFilesList()
    {
        $dbh = $this->getDB();
        $this->status = false;
        $now = time();

        $sql = $dbh->prepare("
                SELECT p.`item_id`, `hash`, `filename`
                FROM `docstore_licr` d,
                (SELECT `item_id` FROM `docstore_licr_request` WHERE `purge` <= :now) as p
                WHERE d.`item_id` = p.`item_id`
                AND d.`copyright_id` != 5;
            ");
        $sql->bindValue(':now', $now, PDO::PARAM_INT);

        try {
            $this->status = true;
            $sql->execute();
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
            $dbh = null;
        }

        if (!$this->status) {
            Reportinator::alertDevelopers('Could not start automatically deleting files', 'The system was unable to query the list of files to delete');
        }

        $result = "Course\tItem\tHash" . str_repeat(' ', 105) . "\tFilename\n";
        $removed = false;
        while (($row = $sql->fetch(PDO::FETCH_ASSOC)) !== false) {
            $removed = true;
            $__temp = $this->getMetadata($row['item_id']);
            if(is_array($__temp)) {
                $result .= $__temp['course_id'] . "\t" . implode("\t", $row) . "\n\r";
            }
        }
        $dbh = null;
        if ($removed) {
            Reportinator::alertDevelopers('Expired Items Purged', "The following items were expired and have been deleted from the repository:\n" . $result);
        } else {
            Reportinator::alertDevelopers('No Expired Items Purged', "No items were found that were supposed to expire, so no files removed.");
        }
    }

    public function deleteFiles()
    {
        $dbh = $this->getDB();
        $this->status = false;
        $now = time();
    
        $yvr = Carbon::now();
        $yvr->timestamp($now)->timezone('America/Vancouver'); //is absolute, so in this case a negative number, yayz
        $offset = $yvr->getOffset();
        $now += $offset;

        $sql = $dbh->prepare("
                SELECT p.`item_id`, `hash`, `filename`
                FROM `docstore_licr` d,
                (SELECT `item_id` FROM `docstore_licr_request` WHERE `purge` <= :now) as p
                WHERE d.`item_id` = p.`item_id`
                AND d.`copyright_id` != 5;
            ");
        $sql->bindValue(':now', $now, PDO::PARAM_INT);

        try {
            $this->status = true;
            $sql->execute();
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
            $dbh = null;
        }

        if (!$this->status) {
            Reportinator::alertDevelopers('Could not start automatically deleting files', 'The system was unable to query the list of files to delete');
        }

        $result = "Item\tHash" . str_repeat(' ', 105) . "\tFilename\n";
        $removed = false;
        $_licr = getModel('licr');
        while (($row = $sql->fetch(PDO::FETCH_ASSOC)) !== false) {
            $removed = true;
            $this->deleteCopyrightFile($row['filename'], false);
            $this->derequestFileById($row['item_id']);
            $__temp = $this->getMetadata($row['item_id']);
            $_licr->call('SetCIStatus', [
                'course' => $__temp['course_id']
                ,
                'item_id' => $row['item_id']
                ,
                'status' => 8
            ]);
            $result .= implode("\t", $row) . "\n\r";
        }
        $dbh = null;
        if ($removed) {
            Reportinator::alertDevelopers('Expired Items Purged', "The following items were expired and have been deleted from the repository:\n" . $result);
            Reportinator::createTicket('Expired Items Purged', "The following items were expired and have been deleted from the repository:\n" . $result);
        } else {
            Reportinator::createTicket('No Expired Items Purged', "No items were found that were supposed to expire, so no files removed.");
        }


        //while file exists, new cached item could be made, so delete cache after files are deleted
        $this->deleteCache();
    }

    public function deleteCache()
    {
        $cachedFiles = '';
        $dir = new DirectoryIterator($this->docstoredirectory);
        $removed = false;
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                if (strpos($fileinfo->getFilename(), '--c.pdf') !== false) {
                    $removed = true;
                    $this->deleteFileFromServer($fileinfo->getPathname(), false);
                    $cachedFiles .= $fileinfo->getFilename() . "\n";
                }
            }
        }
        if ($removed) {
            Reportinator::alertDevelopers('Cache Cleared', "The following items were cleared from the cache\n\r" . $cachedFiles);
        }
    }

    public function deleteCacheById($itemid)
    {
        $cachedFiles = '';
        $dir = new DirectoryIterator($this->docstoredirectory);
        $removed = false;
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                if (strpos($fileinfo->getFilename(), "$itemid--") !== false) {
                    if (strpos($fileinfo->getFilename(), '--c.pdf') !== false) {
                        $removed = true;
                        $this->deleteFileFromServer($fileinfo->getPathname(), false);
                        $cachedFiles .= $fileinfo->getFilename() . "\n";
                    }
                }
            }
        }
        if ($removed) {
            Reportinator::alertDevelopers('Cached Copy Cleared', "The following items were cleared from the cache\n\r" . $cachedFiles);
        }
    }

    public function deleteCopyrightFile($filename, $report = true)
    {
        if ($this->originalFileExists($filename)) {
            $this->deleteFileFromServer($this->docstoredirectory . $filename, $report);
            if ($report) {
                Reportinator::alertDevelopers('Original file deleted as it has expired', 'Applies to file: ' . $filename);
            }
        }
    }

    public function deleteCachedFile($itemid)
    {
        if ($res = $this->getMetadata($itemid)) {
            $this->deleteCachedFileByMetadata($res);
        }
    }

    public function getField($field)
    {

    }

    public function getCopyrightAddenda($itemid, $isCoversheet = 0)
    {
        
        #error_log('SELECT `addendum` FROM `docstore_licr_copyright_addenda` WHERE `item_id` =  ' . $itemid . ($isCoversheet ? ' AND `coversheet` = 1;' : ' AND `coversheet` = 0;'));
        
        $dbh = $this->getDB();
        $this->status = false;
        $sql = $dbh->prepare('SELECT `addendum` FROM `docstore_licr_copyright_addenda` WHERE `item_id` = :item_id ' . ($isCoversheet ? ' AND `coversheet` = 1;' : ' AND `coversheet` = 0;'));
        $sql->bindValue(':item_id', $itemid, PDO::PARAM_INT);
        $licr = getModel('licr');

        try {
            $sql->execute();
            $this->status = true;
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
            $dbh = null;
        }

        if (!$this->status) {
            $this->alert('DocStore::getCopyrightAddendum() - The System was Unable to access Copyright Addenda.', $this->message);
            $dbh = null;

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }

        $results = [];
        while (($row = $sql->fetch(PDO::FETCH_ASSOC)) !== false) {
            
            $results[] = $row['addendum'];
            
            /*
            $addenda = explode('--break--', $row['addendum']);
            foreach ($addenda as $addendum) {
                if (preg_match('/^(.*?)\s\((.*?)\):\s(.*)$/', $addendum, $m)) {
                    $puid = $m[1];
                    $when = date('Y-M-d h:i:s', $m[2]);
                    $msg = $m[3];
                    $userinfo = $licr->getArray('GetUserInfo', ['puid' => $puid], TRUE);
                    $line = $when . ' <i>' . $userinfo['firstname'] . ' ' . $userinfo['lastname'] . '</i> ' . $msg;
                    $results[] = $line;
                }
            }
            */
        }
        $result = implode('<br />', $results);
        $dbh = null;

        return [
            'status' => $this->status,
            'data' => $result];
    }
    
    public function upsertCopyrightNotes($itemid, $puid, $note, $noteid = false)
    {
        $dbh = $this->getDB();
        
        $this->status = false;
    
        $sql = "
            INSERT INTO `docstore`.`docstore_licr_notes` (`item_id`,`puid`,`note`,`timestamp`)
            VALUES (:item_id,:puid,:note,:times);";
        
        if($noteid) {
            $sql = 'UPDATE `docstore`.`docstore_licr_notes` SET `note` = :note WHERE `id` = :note_id;';
            $errorSQL = "UPDATE `docstore`.`docstore_licr_notes` SET `note` = {$note} WHERE `id` = {$noteid};";
            error_log($errorSQL);
        } else {
            $errorSQL = "INSERT INTO `docstore`.`docstore_licr_notes` (`item_id`,`puid`,`note`,`timestamp`) VALUES ({$itemid},{$puid},{$note},{time()});";
            error_log($errorSQL);
        }
        
        $upsertCopyrightNoteStatement = $dbh->prepare($sql);
    
        if($noteid) {
            $upsertCopyrightNoteStatement->bindValue(':note', $note, PDO::PARAM_STR);
            $upsertCopyrightNoteStatement->bindValue(':note_id', $noteid, PDO::PARAM_INT);
        } else {
            $upsertCopyrightNoteStatement->bindValue(':item_id', $itemid, PDO::PARAM_INT);
            $upsertCopyrightNoteStatement->bindValue(':puid', $puid, PDO::PARAM_STR);
            $upsertCopyrightNoteStatement->bindValue(':note', $note, PDO::PARAM_STR);
            $upsertCopyrightNoteStatement->bindValue(':times', time(), PDO::PARAM_INT);
        }
        
        try {
            $upsertCopyrightNoteStatement->execute();
            $this->status = true;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $this->message = $e->getMessage();
            $this->status = false;
            $dbh = null;
        }
        
        $dbh = null;
        
        if (!$this->status) {
            $this->message = "Could not upsert copyright note for item_id: $itemid";
            $this->alert('Writing URI to Item Record Failed', $this->message);
            $this->logHistory($itemid, $this->getHash($itemid), "Failed to update page details for item_id: $itemid", $puid);
            
            return [
                'status' => $this->status,
                'json' => json_encode([
                                          'status' => $this->status,
                                          'message' => $this->message])];
        }
        
        $this->logHistory($itemid, $this->getHash($itemid), "Upserted copyright note for item_id: $itemid", $puid);
        
        $this->deleteCacheById($itemid);
        
        return ['status' => $this->status];
    }
    
    public function getCopyrightNotes($itemid)
    {
        $dbh = $this->getDB();
        $this->status = false;
        $sql = $dbh->prepare('SELECT * FROM `docstore_licr_notes` WHERE `item_id` = :item_id;');
        $sql->bindValue(':item_id', $itemid, PDO::PARAM_INT);
        $licr = getModel('licr');
        
        try {
            $sql->execute();
            $this->status = true;
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
            $dbh = null;
        }
        
        if (!$this->status) {
            $this->alert('DocStore::getCopyrightNotes() - The System was Unable to access Copyright Notes.', $this->message);
            $dbh = null;
            
            return [
                'status' => $this->status,
                'json' => json_encode([
                                          'status' => $this->status,
                                          'message' => $this->message])];
        }
        
        $results = [];
        
        $dt = Carbon::now();
        
        while (($row = $sql->fetch(PDO::FETCH_ASSOC)) !== false) {
            $userInfo = $licr->getArray('GetUserInfo', ['puid' => $row['puid']], TRUE);
    
            $results [] = [
                'note_id' => $row['id'],
                'item_id' => $row['item_id'],
                'puid' => $row['puid'],
                'user_fname' => $userInfo['firstname'],
                'user_lname' => $userInfo['lastname'],
                'note' => $row['note'],
                'timestamp' => $row['timestamp'],
                'timestring' => $dt->timestamp($row['timestamp'])->timezone('America/Vancouver')->toIso8601String(),
                'isEditable' => (time() - $row['timestamp']) <= 3600
            ];
        }
        $dbh = null;
        
        return [
            'status' => $this->status,
            'data' => $results
        ];
    }
    
    public function getCopyrightDetails($itemid)
    {
        $dbh = $this->getDB();
        $this->status = false;
        $sql = $dbh->prepare("SELECT `page_count`,`work_count`, `cost`, `currency`, `paid_amount`, `paid_date`, `exchange_rate`, `rightsholder`, `rights_uri` FROM `docstore_licr_copyright_details` WHERE `item_id` = :item_id;");
        $sql->bindValue(':item_id', $itemid, PDO::PARAM_INT);

        try {
            $this->status = true;
            $sql->execute();
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
            $dbh = null;
        }
        if (!$this->status) {
            $this->alert('DocStore::getCopyrightDetails() - The System was Unable to access Copyright Details.', $this->message);
            $dbh = null;

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }

        $result = [
            'page_count' => -9999,
            'work_count' => -9999];
        while (($row = $sql->fetch(PDO::FETCH_ASSOC)) !== false) {
            $result = [
                'page_count' => $row['page_count'],
                'work_count' => $row['work_count'],
                'cost' => $row['cost'] ?: null ,
                'currency' => $row['currency'] ?: null ,
                'paid_amount' => $row['paid_amount'] ?: null ,
                'paid_date' => $row['paid_date'] ?: null ,
                'exchange_rate' => $row['exchange_rate'] ?: null ,
                'rightsholder' => $row['rightsholder'] ?: null ,
                'rights_uri' => $row['rights_uri'] ?: null
            ];
        }
        $dbh = null;

        return [
            'status' => $this->status,
            'data' => $result];
    }

    public function getCopyrightStatus($itemid)
    {
        $dbh = $this->getDB();
        $this->status = false;
        $sql = $dbh->prepare("SELECT `copyright_id` as `id` FROM  `docstore_licr` WHERE item_id = ?");
        $bind = [$itemid];

        try {
            $this->status = true;
            $sql->execute($bind);
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
            $dbh = null;
        }
        $result = '';
        while (($row = $sql->fetch(PDO::FETCH_ASSOC)) !== false) {
            $result = (int)$row['id'];
        }
        if ($result == '') {
            $result = self::ERROR_ITEM_NOT_FOUND;
            //error_log("The System was Unable to Request the Copyright Statuses from DocStore for ItemID:$itemid.");
        }
        $dbh = null;
        if (!$this->status) {
            $this->alert("The System was Unable to Request the Copyright Statuses from DocStore for ItemID:$itemid.", $this->message);

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }

        return [
            'status' => $this->status,
            'data' => $result];
    }

    public function getCopyrightTypeList()
    {
        $dbh = $this->getDB();
        $this->status = false;
        $sql = $dbh->prepare("SELECT `copyright_id` AS `key`, `determination_label` AS `value` FROM `docstore_licr_copyright`;");

        try {
            $this->status = true;
            $sql->execute();
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
            $dbh = null;
        }
        $result = [];
        while (($row = $sql->fetch(PDO::FETCH_ASSOC)) !== false) {
            $result[$row['key']] = $row['value'];
        }
        $dbh = null;
        if (!$this->status) {
            $this->alert('The System was Unable to Request a list of Copyright Types from DocStore.', $this->message);

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }

        return [
            'status' => $this->status,
            'json' => json_encode($result)];
    }

    //return all the records for an item. this will be sorted in place chronologically
    //with other history records for the item using the timestamps from ds and licr
    public function getHistory($itemid)
    {
        $dbh = $this->getDB();
        $stmt_get_dlh = $dbh->prepare("
                SELECT
                    `action` as 'note',
                    `user` as 'puid',
                    `timestamp` as 'time'
                FROM
                    `docstore_licr_history`
                WHERE
                    `item_id` = ?;
                ");
        $bind = [$itemid];

        $result = [];

        try {
            $stmt_get_dlh->execute($bind);
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
            $this->status = false;
            $dbh = null;
            Reportinator::alertDevelopers('Error retrieving history', 'The following message was thrown whilst trying to fetch the DocStore history for item: ' . $itemid . ': ' . $this->message);
        }
        if ($this->status) {
            $result = $stmt_get_dlh->fetchAll(PDO::FETCH_ASSOC);
        }
        $dbh = null;
        if (!$this->status) {
            $this->alert('The System was Unable to write a legit metadata record to the database. Investigate', $this->message);

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }

        return [
            'status' => $this->status,
            'data' => $result];
    }

    public function getFilenameByItemId($itemid, $internal = true, $nocover = true)
    {
        $hash = $this->getHash($itemid);
        if ($hash !== '') {
            return $this->getFilenameByHash($hash);
        }

        return $hash;
    }

    public function getFilenameByHash($hash, $internal = true, $nocover = true)
    {
        return $this->getFilename($hash);
    }

    public function getHashFromUri($uri)
    {
        $matches = null;
        preg_match('/([bcdfghjklmnpqrstvwxzBCDFGHJKLMNPQRSTVWXZ23456789=_.-]{60}?)/', $uri, $matches);
        if (isset($matches[1])) {
            error_log("Hash is: " . $matches[1]);

            return $matches[1];
        } else {
            error_log("Could not get a docstore has from: $uri");

            return false;
        }
    }

    public function getHashFromId($id)
    {
        return $this->getHash($id);
    }

    public function getPDF($hash)
    {
        $itemid = $this->getId($hash);

        if ($this->isExpired($itemid)) {
            return 'errorpdfs/expired.pdf';
        }

        $metadata = $this->getMetadata($itemid); //is $row or false

        if (!$metadata) {
            $this->setMetadataByItemID($itemid);
            $metadata = $this->getMetadata($itemid); //is $row
        }

        $pdfName = $this->createPDFName($metadata);

        if ($this->pdfExists($pdfName)) {
            return $pdfName;
        } else {
            $filename = $this->getFilenameByHash($hash);
            $theactualfile = $this->docstoredirectory . $filename;
            if (!file_exists($theactualfile)) {
                Reportinator::createTicket('Docstore - PDF Creation Failed - Missing File', 'The system attempted to make a PDF for item: ' . $itemid . ' (hash:' . $hash . ') but could not find a file in the docs folder with the name: ' . $filename . '. Error Code: 1');

                return 'errorpdfs/e1.pdf';
            }
            $thecoverpdf = $this->docstoredirectory . $this->generateCoversheet($pdfName, $metadata);
            $thefinalpdf = $this->docstoredirectory . $pdfName;

            //metadata - useless for programmatic approach unless if you want to parse the output file
            //$command  = "/usr/bin/pdftk $theactualfile dump_data output $theactualfile.log";
            //$logentry = "\r\nCommand: $command \r\nDumping report on: $theactualfile\r\n";
            //exec($command);

            $command = "/usr/bin/pdftk $thecoverpdf $theactualfile cat output $thefinalpdf verbose";
            $logentry = "\r\nCommand: $command \r\nAttempting to generate: $thefinalpdf \r\n\r\nSource file: $pdfName\r\n";
            exec($command, $output);
            foreach ($output as $k => $line) {
                $logentry .= "\r\n" . $line . "\r\n";
            }
            $entry = "\r\n#####################################################################################\r\nTime: " . time() . "\r\n$logentry\r\n#####################################################################################\r\n";
            file_put_contents($this->docstoredirectory . 'pdftk.log', $entry, FILE_APPEND | LOCK_EX);
            if ($this->pdfExists($pdfName)) {
                if (!unlink($thecoverpdf)) {
                    Reportinator::alertDevelopers('Could not clean up after pdf creation', 'Could not unlink the cover sheet after creation for: ' . str_replace('--c.pdf', '-cover.file', $filename));
                }

                return $pdfName;
            } else {
                Reportinator::alertDevelopers('Docstore - PDF Creation Failed - Check File', "Please verify that the Source PDF for this item is PDF/A or Flat. The server found the file but was unable to open it.\n\rAccess Source PDF at: https://cr-staff.library.ubc.ca/details.item/id/$itemid\n\r\n\rTechnical\n\rThe system attempted to make a PDF for item: $itemid (hash: $hash) but PDFtk failed to merge the coversheet and the actual PDF. Check the docs folder for the format of the file: $filename \n\r\n\rError Code: 2");
                Reportinator::alertCopyright('Docstore - PDF Creation Failed', "Please verify that the Source PDF for this item is PDF/A or Flat. The server found the file but was unable to open it.\n\rAccess Source PDF at: https://cr-staff.library.ubc.ca/details.item/id/$itemid\n\r\n\rTechnical\n\rThe system attempted to make a PDF for item: $itemid (hash: $hash) but PDFtk failed to merge the coversheet and the actual PDF. Check the docs folder for the format of the file: $filename \n\r\n\rError Code: 2");

                return 'errorpdfs/e2.pdf';
            }
        }
    }


    public function requestFile($courseid, $itemid)
    {
        $dbh = $this->getDB();
        $this->status = false;
        $sql = $dbh->prepare("SELECT COUNT(*) AS count FROM `docstore_licr` WHERE  `item_id` = ?;");
        $bind = [$itemid];

        try {
            $sql->execute($bind);
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
            $dbh = null;
        }

        if (!$sql->fetchColumn() == 0) {
            $_licr = getModel('licr');
            $cinfo = $_licr->getArray('GetCourseInfo', ['course' => $courseid]);
            $stmt_insert_dlr = $dbh->prepare("REPLACE INTO `docstore_licr_request` (`item_id`,`course_id`,`purge`) VALUES (?,?,?);");
            $bind = [
                $itemid,
                $courseid,
                $this->createEpoch($cinfo['enddate'])];
            try {
                $stmt_insert_dlr->execute($bind);
                $this->status = true;
            } catch (PDOException $e) {
                $this->message = $e->getMessage();
                $this->status = false;
                $dbh = null;
            }
        } else {
            $this->status = false;
            $this->message = "The course $courseid attempted to request the DocStore File #$itemid, but this file is not available";
            Reportinator::alertDevelopers('DocStore - Could not Request File', "The course $courseid attempted to request the DocStore File stored for Item: $itemid, but not filename/entry has been stored in the database for this item.");
        }
        $dbh = null;
        if (!$this->status) {
            $this->alert('The System was Unable to Request a legit DocStore Record. Investigate', $this->message);
            Reportinator::alertDevelopers('DocStore - Could not Request File', "The System was Unable to Request a legit DocStore Record. Investigate\nMessage sent:\n" . $this->message);

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }

        return ['status' => $this->status];
    }

    public function requestFileByItemID($itemid)
    {
        $this->status = false;

        $_licr = getModel('licr');
        $cinfo = $_licr->getArray('GetCoursesByItem', ['item' => $itemid]);

        $failed = [];

        foreach ($cinfo as $k => $v) {
            if (!$this->requestFile($k, $itemid)['status']) {
                $failed[] = $k;
            }
        }

        if (isset($failed) && count($failed) > 0) {
            Reportinator::alertDevelopers('The System was Unable to Request a legit DocStore Record. Investigate - error 4', $this->message);

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }
        $this->status = true;

        return ['status' => $this->status];
    }

    public function resolveInstanceID($instanceID)
    {
        $licr = getModel('licr');

        return $licr->getArray('ResolveInstanceID', ['instance_id' => $instanceID]);
    }

    public function setCopyrightAddenda($itemid, $addendum, $puid, $isCoversheet = 0)
    {
        $dbh = $this->getDB();
        if ($isCoversheet == 0) {
            $message = "$puid (" . time() . "): " . json_decode($addendum) . "--break--";
        } else {
            $message = json_decode($addendum);
        }

        $this->status = false;

        $sql = "
            INSERT INTO `docstore_licr_copyright_addenda` (`item_id`,`addendum`,`coversheet`)
            VALUES (:item_id,:addendum,:coversheet)";
        if ($isCoversheet) {
            $sql .= " ON DUPLICATE KEY UPDATE `addendum` = :addendum , `coversheet` = 1;";
        } else {
            $sql .= " ON DUPLICATE KEY UPDATE `addendum` = CONCAT(`addendum`,:addendum);";
        }

        $stmt_set_dl_copyright = $dbh->prepare($sql);
        $stmt_set_dl_copyright->bindValue(':addendum', $message, PDO::PARAM_STR);
        $stmt_set_dl_copyright->bindValue(':item_id', $itemid, PDO::PARAM_INT);
        $stmt_set_dl_copyright->bindValue(':coversheet', $isCoversheet, PDO::PARAM_INT);

        try {
            $stmt_set_dl_copyright->execute();
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
            $this->status = false;
            $dbh = null;
        }
        $dbh = null;
        if (!$this->status) {
            $this->message = "Could not add addendum for item_id: $itemid";
            $this->alert('Writing URI to Item Record Failed', $this->message);
            $this->logHistory($itemid, $this->getHash($itemid), "Failed to update page details for item_id: $itemid", $puid);

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }
        $this->logHistory($itemid, $this->getHash($itemid), "Added addendum for item_id: $itemid", $puid);
        $this->deleteCacheById($itemid);

        return ['status' => $this->status];
    }

    public function setExpirationDate($itemid, $courseid, $dateAsString)
    {
        $dbh = $this->getDB();
        $this->status = false;

        $purge = strtotime($dateAsString);

        $stmt_set_dl_copyright = $dbh->prepare("
            INSERT INTO `docstore_licr_request` (`item_id`,`course_id`,`purge`)
            VALUES (:item_id, :course_id, :purge)
            ON DUPLICATE KEY
            UPDATE `purge` = :purge;");
        $stmt_set_dl_copyright->bindValue(':purge', $purge, PDO::PARAM_INT);
        $stmt_set_dl_copyright->bindValue(':course_id', $courseid, PDO::PARAM_INT);
        $stmt_set_dl_copyright->bindValue(':item_id', $itemid, PDO::PARAM_INT);

        try {
            $stmt_set_dl_copyright->execute();
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
            error_log($this->message);
            $this->status = false;
            $dbh = null;
        }
        $dbh = null;
        if (!$this->status) {
            $this->message = "A user added an item to DocStore and the system could not write the generated URl to the LiCR Record. The details are\n\rItemID: $itemid\n\rURL: $itemid\n\rLiCR Error Message: ";
            $this->alert('Writing URI to Item Record Failed', $this->message);
            #$this->logHistory ($itemid, $this->getHash ($itemid), "Failed to update page details for item_id: $itemid", $puid);

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }
        #$this->logHistory ($itemid, $this->getHash ($itemid), "Updated expiration details of item_id: $itemid. Course_id: " . $old['page_count'] . "->$page_count. Entire Work: " . $old['work_count'] . "->$work_count", $puid);

        return ['status' => $this->status];
    }
    
    public function upsertCopyrightDetails ($itemID, $puid, $formData)
    {
        
        $dbh = $this->getDB();
        $this->status = false;
        
        $old = $this->getCopyrightDetails($itemID)['data'];
        
        $stmt_set_dl_copyright = $dbh->prepare("
            INSERT INTO `docstore_licr_copyright_details` (`item_id`,`page_count`,`work_count`, `cost`, `currency`, `paid_amount`, `paid_date`, `exchange_rate`, `rightsholder`, `rights_uri`)
            VALUES (:item_id, :page_count, :work_count, :cost, :currency, :paid_amount, :paid_date, :exchange_rate, :rightsholder, :rights_uri)
            ON DUPLICATE KEY
            UPDATE `page_count` = :page_count, `work_count` = :work_count, `cost` = :cost,  `currency` = :currency,  `paid_amount` = :paid_amount,  `paid_date` = :paid_date,  `exchange_rate` = :exchange_rate,  `rightsholder` = :rightsholder,  `rights_uri` = :rights_uri;");
        $stmt_set_dl_copyright->bindValue(':page_count', $formData['page_count'], PDO::PARAM_INT);
        $stmt_set_dl_copyright->bindValue(':work_count', $formData['work_count'], PDO::PARAM_INT);
        $stmt_set_dl_copyright->bindValue(':cost', $formData['cost'], PDO::PARAM_STR);
        $stmt_set_dl_copyright->bindValue(':currency', $formData['currency'], PDO::PARAM_STR);
        $stmt_set_dl_copyright->bindValue(':paid_amount', $formData['paid_amount'], PDO::PARAM_STR);
        $stmt_set_dl_copyright->bindValue(':paid_date', date('Y-m-d H:i:s', strtotime($formData['paid_date'])), PDO::PARAM_STR);
        $stmt_set_dl_copyright->bindValue(':exchange_rate', $formData['exchange_rate'], PDO::PARAM_STR);
        $stmt_set_dl_copyright->bindValue(':rightsholder', $formData['rightsholder'], PDO::PARAM_STR);
        $stmt_set_dl_copyright->bindValue(':rights_uri', $formData['rights_uri'], PDO::PARAM_STR);
        $stmt_set_dl_copyright->bindValue(':item_id', $itemID, PDO::PARAM_INT);
        
        try {
            $stmt_set_dl_copyright->execute();
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
            $this->status = false;
            
            error_log($this->message);
            error_log(json_encode($dbh->errorInfo()));
            
            $dbh = null;
        }
        $dbh = null;
        if (!$this->status) {
            $this->message = "A user added an item to DocStore and the system could not write the generated URl to the LiCR Record. The details are\n\rItemID: $itemID\n\rURL: $itemID\n\rLiCR Error Message: ";
            $this->alert('Writing URI to Item Record Failed', $this->message);
            $this->logHistory($itemID, $this->getHash($itemID), "Failed to update page details for item_id: $itemID", $puid);
            
            return [
                'status' => $this->status,
                'json' => json_encode([
                                          'status' => $this->status,
                                          'message' => $this->message])];
        }
        $this->logHistory($itemID, $this->getHash($itemID), "Updated page details of item_id: $itemID. Page count: " . $old['page_count'] . "->{$formData['page_count']}. Entire Work: " . $old['work_count'] . "->{$formData['work_count']}", $puid);
        
        return ['status' => $this->status];
    }

    public function setCopyrightDetails($itemid, $page_count, $work_count, $puid)
    {
        $dbh = $this->getDB();
        $this->status = false;

        $old = $this->getCopyrightDetails($itemid)['data'];

        $stmt_set_dl_copyright = $dbh->prepare("
            INSERT INTO `docstore_licr_copyright_details` (`item_id`,`page_count`,`work_count`)
            VALUES (:item_id, :page_count, :work_count)
            ON DUPLICATE KEY
            UPDATE `page_count` = :page_count, `work_count` = :work_count;");
        $stmt_set_dl_copyright->bindValue(':page_count', $page_count, PDO::PARAM_INT);
        $stmt_set_dl_copyright->bindValue(':work_count', $work_count, PDO::PARAM_INT);
        $stmt_set_dl_copyright->bindValue(':item_id', $itemid, PDO::PARAM_INT);

        try {
            $stmt_set_dl_copyright->execute();
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
            $this->status = false;
            $dbh = null;
        }
        $dbh = null;
        if (!$this->status) {
            $this->message = "A user added an item to DocStore and the system could not write the generated URl to the LiCR Record. The details are\n\rItemID: $itemid\n\rURL: $itemid\n\rLiCR Error Message: ";
            $this->alert('Writing URI to Item Record Failed', $this->message);
            $this->logHistory($itemid, $this->getHash($itemid), "Failed to update page details for item_id: $itemid", $puid);

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }
        $this->logHistory($itemid, $this->getHash($itemid), "Updated page details of item_id: $itemid. Page count: " . $old['page_count'] . "->$page_count. Entire Work: " . $old['work_count'] . "->$work_count", $puid);

        return ['status' => $this->status];
    }

    public function setCopyrightStatus($itemid, $copyright_id, $puid)
    {
        $dbh = $this->getDB();
        $this->status = false;
        $stmt_set_dl_copyright = $dbh->prepare("UPDATE `docstore_licr` SET `copyright_id` = :copyright_id WHERE `item_id` = :item_id;");
        $stmt_set_dl_copyright->bindValue(':copyright_id', $copyright_id, PDO::PARAM_INT);
        $stmt_set_dl_copyright->bindValue(':item_id', $itemid, PDO::PARAM_INT);

        try {
            $stmt_set_dl_copyright->execute();
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
            $this->status = false;
            $dbh = null;
        }
        $dbh = null;
        if (!$this->status) {
            $this->message = "A user tried to set a copyright status but it failed. The details are\n\rItemID: $itemid";
            $this->alert('Setting Copyright Status Failed', $this->message);
            $this->logHistory($itemid, $this->getHash($itemid), "Failed to update Copyright to copyright_id: $copyright_id", $puid);

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }
        $this->logHistory($itemid, $this->getHash($itemid), "Updated Copyright to copyright_id: $copyright_id", $puid);
        $this->deleteCachedFile($itemid);

        return ['status' => $this->status];
    }

    public function setMetadata($itemid, $courseid = null)
    {
        $_licr = getModel('licr');
        $iinfo = $_licr->getArray('GetItemInfo', ['item_id' => $itemid]);
        if (!isset($courseid) || $courseid == '') {
            $courseid = (int)array_pop($iinfo['course_ids']);
        }
        $cinfo = $_licr->getArray('GetCourseInfo', ['course' => $courseid]);
        //re-enable this
        //$bibdata = unserialize($iinfo['bibdata']);

        /* need to remove this - start */
        $_bibdata = getModel('bibdata');
        $bibdataArr = $_bibdata->getBibdata($iinfo['bibdata'], $iinfo['physical_format'], $itemid);
        $bibdata = $bibdataArr['bibdata'];
        error_log("Docstore model::setMetadata() -> remove the calls to bibdata model after QA");
        /* need to remove this - end */


        $this->deleteCacheById($itemid);

        $dbh = $this->getDB();
        $stmt_insert_dlm = $dbh->prepare("
            REPLACE INTO `docstore_licr_metadata` (
                 `item_id`
                ,`course_id`
                ,`item_title`
                ,`item_author`
                ,`item_publisher`
                ,`item_pubdate`
                ,`item_incpages`
                ,`course_title`
                ,`course_code`
                ,`course_term`
                ,`course_dept`
                ,`external_id`
            )
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?);");
        $bind = [
            $itemid,
            $courseid,
            $iinfo['title'],
            $iinfo['author'],
            $bibdata['item_publisher'],
            $bibdata['item_pubdate'],
            (empty($bibdata['item_incpages']) ? '' : $bibdata['item_incpages']),
            $cinfo['title'],
            (isset($cinfo['coursenumber']) ? $cinfo['coursenumber'] : '' . ' ' . isset($cinfo['section']) ? $cinfo['section'] : ''),
            $this->getSemester($cinfo['lmsid']),
            isset($cinfo['coursecode']) ? $cinfo['coursecode'] : '',
            $cinfo['lmsid']];

        try {
            $stmt_insert_dlm->execute($bind);
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
            error_log($e->getMessage());
            $this->status = false;
            $dbh = null;
        }
        $dbh = null;
        if (!$this->status) {
            $this->alert('The System was Unable to write a legit metadata record to the database. Investigate', $this->message);

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }

        return ['status' => $this->status];
    }

    public function setMetadataByItemID($itemid)
    {
        $this->status = false;
        $_licr = getModel('licr');
        $cinfo = $_licr->getArray('GetCoursesByItem', ['item' => $itemid]);
        $failed = [];

        foreach ($cinfo as $k => &$v) {
            if (!$this->setMetadata($itemid, $k)['status']) {
                $failed[] = $k;
            }
        }
        if (isset($failed) && count($failed) > 0) {
            $this->alert('The System was Unable to Request a legit DocStore Record. Investigate', $this->message);

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }
        $this->status = true;

        return ['status' => $this->status];
    }
    
    public function setURL($url, $item)
    {
        $this->status = false;
        $licr = getModel('licr');
        $results = $licr->getJSON('SetItemURI', [
            'item_id' => $item
            ,
            'uri' => $url
        ]);

        $data = json_decode($results, true);

        if ($data['success']) {
            if (isset($data['data']) && count($data['data'])) {
                $this->status = true;
            }
        } else {
            $this->message = "A user added an item to DocStore and the system could not write the generated URl to the LiCR Record. The details are\n\rItemID: $item\n\rURL: $url\n\rLiCR Error Message: " . $data['message'];
            $this->alert('Writing URI to Item Record Failed', $this->message);

            return [
                'status' => $this->status,
                'json' => json_encode([
                    'status' => $this->status,
                    'message' => $this->message])];
        }

        return ['status' => $this->status];
    }

    public function updateAllMetadata()
    {
        $dbh = $this->getDB();
        $this->status = false;

        $sql = $dbh->prepare("
                SELECT `item_id` FROM `docstore_licr`;
            ");

        try {
            $this->status = true;
            $sql->execute();
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
            $dbh = null;
        }

        if (!$this->status) {
            Reportinator::alertDevelopers('Could not start mass update of metadata', 'The system was unable to query the list of files to update');

            return false;
        }

        while (($row = $sql->fetch(PDO::FETCH_ASSOC)) !== false) {
            if (!$this->setMetadataByItemID($row['item_id'])) {
                Reportinator::alertDevelopers('Updating Metadata Failed - ' . $row['item_id'], 'Updating Metadata Failed for the item with ItemID:' . $row['item_id']);
            };
        }
        $dbh = null;

        return true;
    }

    private function isExpired($item_id)
    {
        $dbh = $this->getDB();
        $this->status = false;
        $now = time();

        $sql = $dbh->prepare(
            "SELECT `purge`
                FROM `docstore_licr`,`docstore_licr_request`
                WHERE `docstore_licr_request`.`item_id` = :item_id
                AND `docstore_licr_request`.`item_id` = `docstore_licr`.`item_id`
                AND `docstore_licr`.`copyright_id` != 5"
        );
        $sql->bindValue(':item_id', $item_id, PDO::PARAM_INT);

        try {
            $this->status = true;
            $sql->execute();
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
            $dbh = null;
        }

        if (!$this->status) {
            Reportinator::alertDevelopers('Could not determine if file was expired', 'The system was unable to query the purge date of item: ' . $item_id);
        }
        $expiry_time = $now;
        while (($row = $sql->fetch(PDO::FETCH_ASSOC)) !== false) {
            $expiry_time = $row['purge'];
        }
        $dbh = null;

        if ($expiry_time < $now) {
            return true;
        }

        return false;
    }

    private function alert($subject, $message)
    {
        $time = time();
        $subject = "Docstore - Alert - " . $subject . " - $time";
        $message = $message . "\n\rTime: " . time();

        error_log($subject . '  ||  ' . $message);
    }

    private function errorAlert($s, $m)
    {
        $this->alert($s, $m);
    }

    private function addDocstoreRecord($itemid, $hash, $savefile)
    {
        $dbh = $this->getDB();
        $sql = $dbh->prepare("INSERT INTO docstore_licr (`item_id`,`hash`,`filename`) VALUES (?,?,?);");
        $bind = [
            $itemid,
            $hash,
            $savefile];

        try {
            $sql->execute($bind);
            $success = true;
        } catch (PDOException $e) {
            echo $e->getMessage();
            $dbh = null;
            $success = false;
        }
        $dbh = null;
        if ($success) {
            return [
                'status' => true,
                'action' => 'CREATED DocStore File'];
        } else {
            Reportinator::alertDevelopers('DocStore - Could not add to docstore_licr', "The System was unable to create an entry for Item $itemid with the Hash $hash that was to be saved as $savefile. Please ensure the file is on the server and check the database to troubleshoot why.");

            return [
                'status' => false,
                'action' => 'FAILED to CREATE DocStore File'];
        }
    }

    private function addDocstoreFilename($itemid, $savefile)
    {
        $dbh = $this->getDB();
        $sql = $dbh->prepare("UPDATE docstore_licr SET `filename` = ? WHERE `item_id` = ?;");
        $bind = [
            $savefile,
            $itemid];

        try {
            $sql->execute($bind);
            $success = true;
        } catch (PDOException $e) {
            echo $e->getMessage();
            $dbh = null;
            $success = false;
        }
        $dbh = null;
        if ($success) {
            return [
                'status' => true,
                'action' => 'UPDATED DocStore File'];
        } else {
            Reportinator::alertDevelopers('DocStore - Could not add to docstore_licr', "The System was unable to update an entry for Item $itemid, that was to be saved as $savefile. Please ensure the file is on the server and check the database to troubleshoot why.");

            return [
                'status' => false,
                'action' => 'FAILED to UPDATE DocStore File'];
        }
    }

    private function createSaveName($filename)
    {
        return md5($filename . time() . rand(0, 123456789654987654321) . 'this is a salt value to thwart hackers') . '.file';
    }

    private function createPDFName($metadata)
    {
        $name = strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($metadata['item_id'] . '--' . $metadata['item_title'] . '--' . $metadata['course_title'] . '--C')) . '.pdf');
        $trunc = strlen($metadata['item_title']) - 1;
        // @skhanker please review this to make sure it doesn't affect anything else
        while (strlen($name) > 255) { // LOCRSUPP-886
            $name = strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($metadata['item_id'] . '--' . substr($metadata['item_title'], 0, $trunc--) . '--' . $metadata['course_title'] . '--C')) . '.pdf');
        }
        return $name;
    }

    private function deleteFileFromServer($path, $report = true)
    {
        if ($this->fileExists($path)) {
            unlink($path);
            if ($report) {
                Reportinator::alertDevelopers('File deleted from server', 'Applies to file: ' . $path);
            }
        }
    }

    //create record adding instance_id to existing hash:file pair.
    //pair will be found by passing
    private function derequestFileById($itemid)
    {
        $dbh = $this->getDB();
        $this->status = false;
        $sql = $dbh->prepare("DELETE FROM `docstore_licr_request` WHERE  `item_id` = ?;");
        $bind = [$itemid];
        try {
            $sql->execute($bind);
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
            $dbh = null;
            Reportinator::alertDevelopers('Could not derequest item', 'Could not automatically derequest item: ' . $itemid . ' from the table docstore_licr_request, please do so manually');
        }
        $dbh = null;
    }

    private function deleteCachedFileByMetadata($metadata)
    {
        $pdfName = $this->createPDFName($metadata);
        error_log('Try to unlink:' . $pdfName);
        if ($this->pdfExists($pdfName)) {
            unlink($this->docstoredirectory . $pdfName);
            Reportinator::alertDevelopers('Cached PDF Deleted because of Information Update', 'Applies to file: ' . $pdfName);
        }
    }

    private function fileExists($serverpath)
    {
        return file_exists($serverpath);
    }

    private function originalFileExists($filename)
    {
        return $this->fileExists($this->docstoredirectory . $filename);
    }

    private function pdfExists($pdfName)
    {
        return $this->fileExists($this->docstoredirectory . $pdfName);
    }

    private function getCopyrightCoverInfo($itemid)
    {
        $dbh = $this->getDB();
        $sql = $dbh->prepare("SELECT b.`determination_label`, b.`disclaimer` FROM `docstore_licr` a, `docstore_licr_copyright` b WHERE a.`item_id` = ? AND b.`copyright_id` = a.`copyright_id`;");

        $bind = [$itemid];
        try {
            $sql->execute($bind);
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
            $dbh = null;

            return false;
        }
        $row = $sql->fetch(PDO::FETCH_ASSOC);
        $dbh = null;

        return $row;
    }

    private function generateCoversheet($pdfName, $metadata)
    {

        require_once Config::get('approot') . '/core/pdf.inc.php';

        //remove the --c.pdf from the $pdfname and replace with -cover.file
        $pdfName = str_replace('--c.pdf', '-cover.file', $pdfName);
        $copyright = $this->getCopyrightCoverInfo($metadata['item_id']);
        $extranote = $this->getCopyrightAddenda($metadata['item_id'], 1)['data'];

        // create new PDF document
        $pdf = new ubcPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('UBC Copyright Office');
        $pdf->SetTitle('Copyright-Notice');
        $pdf->SetSubject('Copyright Notice');

        // set header and footer fonts
        $pdf->setHeaderFont([
            PDF_FONT_NAME_MAIN,
            '',
            0]);

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, 23, PDF_MARGIN_RIGHT, true);
        $pdf->SetHeaderMargin(0);
        $pdf->SetFooterMargin(0);

        // remove default footer
        $pdf->setPrintFooter(false);

        // set auto page breaks
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        // ---------------------------------------------------------

        // set font
        $pdf->SetMargins(PDF_MARGIN_LEFT, 24, PDF_MARGIN_RIGHT, true);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->setFontSubsetting(false);
        // add a page
        $pdf->AddPage();
        $html = '<span style="color: rgb(255,255,255); letter-spacing: 8px; text-shadow: rgb(34, 34, 34) 1px 1px 0px;">' . $copyright['determination_label'] . '</span><br>';

        //Collection - Book, Object Etc
        if (isset($metadata['collection_title']) && $metadata['collection_title'] != "") {
            $html .= '<p>Reading: ' . htmlspecialchars($metadata['item_title']) . '' . '&nbsp;&nbsp;&nbsp;<em>(' . htmlspecialchars($metadata['collection_title']) . ')</em></p>';
        } else if (isset($metadata['journal_title']) && $metadata['journal_title'] != "") {
            $html .= '<p>Journal: ' . htmlspecialchars($metadata['journal_title']) . '</p>';
            if (isset($metadata['item_title'])) {
                $html .= '<p>Article: ' . htmlspecialchars($metadata['item_title']) . '</p>';
            }
        } else {
            $html .= '<p>Title: ' . htmlspecialchars($metadata['item_title']) . '</p>';
        }

        if (isset($metadata['item_author'])) {
            $html .= '<p>Author: ' . htmlspecialchars($metadata['item_author']) . '</p>';
        }
        if (isset($metadata['item_editor']) && $metadata['item_editor'] != "") {
            $html .= '<p>Editor: ' . htmlspecialchars($metadata['item_editor']) . '</p>';
        }
        if (isset($metadata['item_publisher']) || isset($metadata['item_pubdate']) || isset($metadata['item_incpages'])) {
            $html .= '<p>';
            if (isset($metadata['item_publisher']) && $metadata['item_publisher'] != "") {
                $html .= 'Publisher: ' . htmlspecialchars($metadata['item_publisher']) . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            }
            if (isset($metadata['item_pubdate']) && $metadata['item_pubdate'] != "") {
                $html .= 'Publication Date: ' . htmlspecialchars($metadata['item_pubdate']) . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            }
            if (isset($metadata['item_incpages']) && $metadata['item_incpages'] != "") {
                $html .= 'Pages: ' . htmlspecialchars($metadata['item_incpages']);
            }
            $html .= '</p>';
        }


        if (isset($metadata['course_title']) || $metadata['course_code'] || isset($metadata['course_term']) || isset($metadata['course_dept'])) {
            $html .= '<p>';
            if (isset($metadata['course_title'])) {
                $html .= 'Course: ' . htmlspecialchars($metadata['course_title']) . '<br>';
            }
            if (isset($metadata['course_code'])) {
                $html .= 'Course Code: ' . htmlspecialchars($metadata['course_code']) . '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';;
            }
            if (isset($metadata['course_term'])) {
                $html .= 'Term: ' . htmlspecialchars($metadata['course_term']) . '<br>';
            }
            $html .= '</p>';
            if (isset($metadata['course_dept'])) {
                $html .= '<p>Department: ' . htmlspecialchars($metadata['course_dept']) . '</p>';
            }

        }
        $html .= $copyright['disclaimer'];

        if (isset($extranote) && $extranote != '') {
            $html .= '<strong>Additional Copyright Information: </strong><br>' . $extranote;
        }

        $pdf->writeHTML($html, true, false, true, false, 'L');
        //$pdf->writeHTML($html, true, false, true, false, 'L');

        //Close and output PDF document
        $pdf->Output($this->docstoredirectory . $pdfName, 'F');

        return $pdfName;
    }

    private function getDB()
    {
        //TODO - sep out into cfg file
        //prob want to read from a separate config file?

        try {
            $dbhandle = new DocsPDO();
    
            return $dbhandle;
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }

        throw new \Exception('Could not get DocStore Database');
        
        //return (new DBFinder($dbuser, $dbpass, $dbname))->getDB();
    }

    //returns a hash from the itemid
    //is using the history table as this is the only table with an id and hash that does not get purged
    //UPDATE - cannot use history table as now the hash can change under certain conditions.
    /**
     * @param $itemid
     * @return bool|string
     */
    private function getHash($itemid)
    {
        $dbh = $this->getDB();
        $hash = '';
        $sql = $dbh->prepare("SELECT DISTINCT `hash` FROM `docstore_licr` WHERE  `item_id` = ?;");

        $bind = [$itemid];
        try {
            $sql->execute($bind);
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
            $dbh = null;

            return false;
        }
        while (($row = $sql->fetch(PDO::FETCH_ASSOC)) !== false) {
            $hash = $row['hash'];
        }
        $dbh = null;

        return $hash;
    }


    //returns a itemid from the hash
    //was using the history table as this is the only table with an id and hash that does not get purged
    //is using the default table as this is the only table with a valid id and hash (history can have an empty hash until the file is uploaded)
    private function getId($hash)
    {
        $dbh = $this->getDB();
        $id = '';
        $sql = $dbh->prepare("SELECT DISTINCT `item_id` FROM `docstore_licr` WHERE  `hash` = ?;");

        $bind = [$hash];
        try {
            $sql->execute($bind);
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
            $dbh = null;

            return false;
        }
        while (($row = $sql->fetch(PDO::FETCH_ASSOC)) !== false) {
            $id = $row['item_id'];
        }
        $dbh = null;

        return $id;
    }

    private function getFilename($hash)
    {
        $dbh = $this->getDB();
        $filename = '';
        $sql = $dbh->prepare("SELECT DISTINCT `filename` FROM `docstore_licr` WHERE  `hash` = ?;");

        $bind = [$hash];
        try {
            $sql->execute($bind);
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
            $dbh = null;

            return false;
        }
        while (($row = $sql->fetch(PDO::FETCH_ASSOC)) !== false) {
            $filename = $row['filename'];
        }
        $dbh = null;

        return $filename;
    }

    /* @return array|bool
     */
    private function getMetadata($id)
    {
        $dbh = $this->getDB();
        $sql = $dbh->prepare("SELECT `title`, `author`,`bibdata` FROM `licr`.`item` WHERE  `item_id` = ?;");

        $bind = [$id];
        try {
            $sql->execute($bind);
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
            error_log($e->getMessage());
            $dbh = null;

            return false;
        }
        if (($row = $sql->fetch(PDO::FETCH_ASSOC)) == false) {
            Reportinator::alertDevelopers('DS:: Item that has no metadata: ' . $id, 'see subject');
            $dbh = null;
            //$this->setMetadataByItemID($id);
            //$this->getMetadata($id);
            return false;
        }


        $metadataKeys = [
            "availability_id",
            "collection_title",
            "item_author",
            "item_doi",
            "item_edition",
            "item_editor",
            "item_incpages",
            "item_isxn",
            "item_pubdate",
            "item_publisher",
            "item_pubplace",
            "item_title",
            "journal_issue",
            "journal_month",
            "journal_title",
            "journal_volume",
            "journal_year",
            "subject_terms"
        ];

        $tempM = unserialize($row['bibdata']);
        $metadata = [];
        foreach ($metadataKeys as $key) {
            if (isset($tempM[$key])) {
                $metadata[$key] = $tempM[$key];
            } else {
                $metadata[$key] = "";
            }
        }
        if ($metadata['item_title'] === "") {
            $metadata['item_title'] = $row['title'];
        }
        if ($metadata['item_author'] === "") {
            $metadata['item_author'] = $row['author'];
        }


        $sql = $dbh->prepare("SELECT * FROM `docstore_licr_metadata` WHERE  `item_id` = ?;");

        $bind = [$id];
        try {
            $sql->execute($bind);
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
            error_log($e->getMessage());
            $dbh = null;

            return false;
        }
        if (($row = $sql->fetch(PDO::FETCH_ASSOC)) == false) {
            $dbh = null;
            $this->setMetadataByItemID($id);
            $this->getMetadata($id);

            return false;
        }
        $dbh = null;
        foreach ($row as $k => $v) {
            if (!isset($metadata[$k])) {
                $metadata[$k] = $row[$k];
            }
        }

        return $metadata;
    }

    private function nonsense()
    {
        $chars = 'bcdfghjklmnpqrstvwxzBCDFGHJKLMNPQRSTVWXZ23456789-=_.';
        $chars = str_split($chars);
        //Note: rand() is inclusive
        $len = count($chars) - 1;
        $nonsense = '';
        for ($i = 0; $i < 60; $i++) {
            $nonsense .= $chars[rand(0, $len)];
        }

        return $nonsense;
    }

    private function uniqueHash()
    {
        $dbh = $this->getDB();
        $unique = false;
        $hash = '';

        $sql = $dbh->prepare("SELECT COUNT(*) AS count FROM `docstore_licr` WHERE  `hash` = ?;");

        while (!$unique) {
            $hash = $this->nonsense();
            $bind = [$hash];
            try {
                $sql->execute($bind);
            } catch (PDOException $e) {
                $this->message = $e->getMessage();
                $dbh = null;
            }

            if ($sql->fetchColumn() == 0) {
                $unique = true;
            }
        }
        $dbh = null;

        return $hash;
    }

    private function logHistory($itemid, $hash, $action, $puid)
    {
        $dbh = $this->getDB();
        $sql = $dbh->prepare("INSERT INTO docstore_licr_history (`item_id`, `hash`,`action`,`user`) VALUES (?,?,?,?);");
        $bind = [
            $itemid,
            $hash,
            $action,
            $puid];

        try {
            $sql->execute($bind);
        } catch (PDOException $e) {
            echo $e->getMessage();
            $dbh = null;
            $this->errorAlert('Database Error Thrown', 'The user ' . $puid . ' has tried to upload a DocStore file. Please note the timestamp in the subject line. The action logged was: ' . $action);
        }
        $dbh = null;
    }

    private function storeFile($file, $savefile, $puid, $itemid)
    {
        $path = $this->docstoredirectory . $savefile;
        if (move_uploaded_file($file, $path)) {
            Reportinator::alertDevelopers('Dev - Licr-DocStore - Connect - Uploaded File', "Received PDF:\nItem: $itemid\nWrote File $file as $path.\nUploader PUID: $puid");

            return true;
        } else {
            Reportinator::alertDevelopers('Dev - Licr-DocStore - Connect - Upload Failed', "PDF Failed:\nItem: $itemid\nCould not write file $file as $path.\nUploader PUID: $puid");

            return false;
        }
    }

    private function verifyDocstoreWriteable($folder)
    {
        if (!file_exists($folder)) {
            $this->status = false;
            $this->message = 'Cannot write to the docstore system.';
            $this->errorAlert("System Not Writable", $this->message);

            return false;
            /* if (!@mkdir($folder, 0777)) { $error = error_get_last(); return false;} */
        }

        return true;
    }

    private function verifyFileUpload($file, $itemid)
    {

        if (is_uploaded_file($file['tmp_name'])) {
        } else {
            $this->status = false;
            $this->message = 'Checking uploaded filename failed. Uploaded filename may have been manipulated.';
        }
        if ($file['error']) {
            $this->status = false;
            $this->message = "An error has occured";
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $this->message = "The uploaded file exceeds the maximum file size allowed (php.ini)";
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $this->message = "The uploaded file exceeds the maximum file size allowed for uploads (Form MAX_SIZE field)";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $this->message = "The uploaded file was only partially uploaded";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $this->message = "No file was uploaded";
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $this->message = "Missing a temporary folder";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $this->message = "Failed to write file to disk";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $this->message = "File upload stopped by extension";
                    break;
                default:
                    $this->message = "Unknown Error";;
                    break;
            }
            //$this->errorAlert("File Verification Failed",$this->message);
            error_log("File Verification Failed " . $this->message);
            Reportinator::createTicket("DocStore Upload Error - Item $itemid", $this->message);

            return false;
        }

        return true;
    }

    private function updateField($table, $field)
    {

    }

    private function createEpoch($dateStr)
    {
        try {
            $date = new DateTime($dateStr, new DateTimeZone('America/Vancouver'));
        } catch (Exception $e) {
            $this->errorAlert('Could not Create Date', "Could not read and create a purge date to insert into DocStore");

            return 0;
        }

        return $date->format('U');
    }

    private function getSemester($lmsid)
    {
        preg_match("/(?<=\.)(20[1-3]{2}[W|S][1-3]{1}(-[1-3]{1})?)(?=\.)/", $lmsid, $output_array);

        return isset($output_array[0]) ? $output_array[0] : '';
    }


}

class DocsPDO extends PDO
{

    private $engine;
    private $host;
    private $database;
    private $user;
    private $pass;

    public function __construct()
    {
        
        $dbname = Config::get('docstore_name');
        $dbuser = Config::get('docstore_user');
        $dbpass = Config::get('docstore_pass');
        $this->engine = Config::get('docstore_type');
        $this->host = Config::get('docstore_host');
        $this->database = $dbname;
        $this->user = $dbuser;
        $this->pass = $dbpass;
        $dns = $this->engine . ':dbname=' . $this->database . ";host=" . $this->host;
        parent::__construct($dns, $this->user, $this->pass);
    }
}
