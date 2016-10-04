<?php
//require_once(Config::get('approot').'/core/resolver.inc.php');
require_once(Config::get('approot').'/core/pdf.inc.php');

function get(){


    $hash = gv('id');
    $puid = gv('s');


    $_docstore = getModel('docstore');
    $filename  = $_docstore->getFilenameByHash($hash);
    error_log("Filename is: $filename");

    if (isset($filename)) {
        //$file = Config::get('store_folder') . "/$filename";
        $file = Config::get('docstore_docs') . $filename;

        if (file_exists($file)) {
            header('Content-Description: DocStore File Access');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename=' . basename($filename));
            header("Cache-Control:  max-age=1, must-revalidate");
            header("Pragma: public");
            ob_clean();
            flush();
            readfile($file);
            exit;
        }
    } else {
        //you should never reach here, as there is an error controller
        //this is here if some random error occurs
        echo "<h1>File Doesn't Exist</h1><br /><p>Please consult with your lecturer/administration and let them know this URL is broken.</p>";
        exit;
    }
}



function control_download_get(){

    $id = gv('id');

    $sessionID = gv('s');
    $puid = '';

    if (!isset($sessionID)) {
        //if nothing is set then we can defer to view to enforce a modal login
        //require_login('ldap');
        ssv('user',NULL);
        $errormessage = "Did someone give you this URL to enter directly? You need to access this content through Connect.";
        return array('display'=>'error', 'clf'=>true, 'message'=>$errormessage);
    }


    if ($sessionID === 'admin'){
        /*if(!logged_in()){
            if(Config::get('authentication') || sv('authentication')){
                if($action != 'login'){
                    redirect('/login.form');
                }
            }
        }*/
    }
    else {
        $puid = resolveSession($sessionID);
        if ($puid === '') {
            //if nothing is set then we can defer to view to enforce a modal login
            //require_login('ldap');
            ssv('user',NULL);
            $errormessage = "Your session has expired. Please retry your download.";
            return array('display'=>'error', 'clf'=>true, 'message'=>$errormessage);
        }
        else {
            //need to call this since you didn't go through ldap
            //to sssv stuff that will be needed to prevent system
            //from kicking you out.
            authenticate_granted($puid,$puid);
        }
    }



    $db=(new DBFinder())->getDB();

    //Ares Implementation
    $sql= $db->prepare("SELECT filename, aresid as itemid FROM ares_docstore WHERE hash = ?");
    $sql->bindValue(1, $id, PDO::PARAM_STR);
    $sql->execute();
    $row = $sql->fetch(PDO::FETCH_ASSOC);
    $db = NULL;
    $areshandle = connectToAres();
    $aweb= Config::get('areas_web');
    $query = $areshandle->prepare("SELECT Classes.Name as CourseName, Classes.ClassCode as CourseCode, Classes.Semester, Classes.Department, Items.Title
FROM Items, Classes
WHERE Location LIKE '{$aweb}/aresinternal/docs%'
AND CurrentStatus LIKE 'Item Available%'
AND Items.ClassID = Classes.ClassID
AND ItemID = ?");
    $query->bindValue(1, $row['itemid'], PDO::PARAM_STR);
    $query->execute();
    $itrs = $query->fetch(PDO::FETCH_ASSOC);
    unset($areshandle);
    unset($query);

    if(isset($row['filename'])){
        //allow script to work regardless of if file was stored with the .file extension
        $parts = explode('.file', $row['filename']);
        $title = $parts[0];
        $disclaimerURI = generateCopyright($title, $itrs);
        //name to save the file as
        $newfile = str_replace("/", "", stripslashes($itrs['Title'].'--'.$itrs['CourseName'].'--accessed-'.time()));
        $copyrightedPDF =& new ubcDisclaimerPDF();
        //First - Create some Metadata

        //lol if this works
        $copyrightedPDF->SetCreator("D'Storinator");
        $copyrightedPDF->SetAuthor('The University of British Columbia');
        //something like "UBC Course Reserves Content"
        $copyrightedPDF->SetTitle('Copyright-Notice');
        //something like "{course code} - {course title}"
        $copyrightedPDF->SetSubject('Copyright Notice');
        //something like {course tags}
        $copyrightedPDF->SetKeywords('copyright, notice, ubc, library');

        //Second - set array of files to merge

        //File 1 - Copyright Notice
        //File 2 - file being provided
        $copyrightedPDF->addFiles(array($disclaimerURI,Config::get('docstore_docs').$title.".file"));
        $copyrightedPDF->concat();

        //Third - Output file, named as {title}-{course-name}-reading-for-{student#}
        return array('display'=>'pdf', 'clf'=>false, 'pdf'=>$copyrightedPDF->Output("$newfile.pdf", "D"), 'name'=>$newfile);

        //return array('display'=>'doc-deliver','clf'=>false, 'filename'=>$newfile);
    }
    else {
        return array('display'=>'error');
    }
}

function generateCopyright($sourceTitle, &$metadata){

    // create new PDF document
    $pdf = new ubcPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('UBC Copyright Office');
    $pdf->SetTitle('Copyright-Notice');
    $pdf->SetSubject('Copyright Notice');

    // set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', 0));

    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, 23, PDF_MARGIN_RIGHT, true);
    $pdf->SetHeaderMargin(0);
    $pdf->SetFooterMargin(0);

    // remove default footer
    $pdf->setPrintFooter(false);

    // set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    // ---------------------------------------------------------

    // Print a text
    //if($metadata['copyright'] === "f"){

    $spoofedCopyright = 'p';

    if($spoofedCopyright === "f"){
        // set font
        $pdf->SetFont('helvetica', '', 13);
        $pdf->setFontSubsetting(false);
        // add a page
        $pdf->AddPage();
        $html = '<span style="color: rgb(255,255,255); letter-spacing: 8px; text-shadow: rgb(34, 34, 34) 1px 1px 0px;">SHORT EXCERPT</span><br><p>Title: '.htmlspecialchars($metadata['Title']).'</p><p>Course: '.htmlspecialchars($metadata['CourseName']).'</p><p>Course Code: '.htmlspecialchars($metadata['CourseCode']).'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Term: '.htmlspecialchars($metadata['Semester']).'</p><p>Department: '.htmlspecialchars($metadata['Department']).'</p><p>Copyright Statement of Responsibility<br/>This copy is made solely for your personal use for research, private study, education, parody, satire, criticism or review only. Further reproduction, fixation, distribution, transmission, dissemination, communication, or any other uses, may be an infringement of copyright if done without securing the permission of the copyright owner. You may not distribute, e-mail or otherwise communicate these materials to any other person.</p><br/><p><strong>For more information on UBC&rsquo;s Copyright Policies, please visit</strong> <a href="http://copyright.ubc.ca/">UBC Copyright</a></p>';
    }
    else{
        // set font
        $pdf->SetMargins(PDF_MARGIN_LEFT, 24, PDF_MARGIN_RIGHT, true);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->setFontSubsetting(false);
        // add a page
        $pdf->AddPage();
        $html = '<span style="color: rgb(255,255,255); letter-spacing: 8px; text-shadow: rgb(34, 34, 34) 1px 1px 0px;">WITH PERMISSION OF RIGHTSHOLDERS</span><br><p>Title: '.htmlspecialchars($metadata['Title']).'</p><p>Course: '.htmlspecialchars($metadata['CourseName']).'</p><p>Course Code: '.htmlspecialchars($metadata['CourseCode']).'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Term: '.htmlspecialchars($metadata['Semester']).'</p><p>Department: '.htmlspecialchars($metadata['Department']).'</p><p>Copyright Statement of Responsibility<br/>This copy is made solely for your personal use for research, private study, education, parody, satire, criticism or review only. Further reproduction, fixation, distribution, transmission, dissemination, communication, or any other uses, may be an infringement of copyright if done without securing the permission of the copyright owner. You may not distribute, e-mail or otherwise communicate these materials to any other person.</p><br/><p><strong>For more information on UBC&rsquo;s Copyright Policies, please visit</strong> <a href="http://copyright.ubc.ca/">UBC Copyright</a></p>';
    }
    $pdf->writeHTML($html, true, false, true, false, 'L');
    //$pdf->writeHTML($html, true, false, true, false, 'L');

    // ---------------------------------------------------------

    //Close and output PDF document
    $pdf->Output(Config::get('docstore_docs') .$sourceTitle.'-cover.file', 'F');

    return Config::get('docstore_docs') .$sourceTitle.'-cover.file';
}
