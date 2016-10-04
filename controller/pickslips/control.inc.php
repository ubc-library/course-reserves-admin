<?php
require_once(Config::get('approot') . '/core/tcpdf/tcpdf.php');

class Controller_pickslips
{

    public function __construct()
    {
        $this->page_orientation = 'L';//L for landscape, P for portrait
        $this->a4h = 297;
        $this->a4w = 210;

        //style barcode
        $this->barcodeStyle = array(
            'position' => '',
            'align' => 'C',
            'stretch' => false,
            'fitwidth' => true,
            'cellfitalign' => '',
            'border' => false,
            'hpadding' => 'none',
            'vpadding' => 'none',
            'fgcolor' => array(0, 0, 0),
            'bgcolor' => false, //array(255,255,255),
            'text' => true,
            'font' => 'helvetica',
            'fontsize' => 9,
            'stretchtext' => 4
        );


        //cell border
        $this->borderLineStyle = array(
            'width' => 0.2,
            'cap' => 'butt',
            'join' => 'miter',
            'dash' => 0,
            'color' => array(130, 130, 130)
        );

        //style divider
        $this->dividingLineStyle = array(
            'width' => 0.2,
            'cap' => 'butt',
            'join' => 'miter',
            'dash' => 1,
            'color' => array(150, 150, 150)
        );

        //give physical format a display name
        $this->switchFormat = array(
            'stream_video' => "Physical Media (Video)"
        , 'book_general' => "Book"
        , 'book_chapter' => "Book Chapter"
        , 'stream_music' => "Physical Media (Audio)"
        , 'stream_general' => "General Media"
        , 'undetermined' => "Undetermined"
        );
    }

    public function pickslips()
    {
        //TODO options for portrait and landscape
        //ids to process pickslips for
        $itemsToParse = pv('itemids');
        $suffix = pv('suffix');
        
        //error_log('PRINT_PICKSLIP: ' . json_encode(['itemids' => $itemsToParse, 'suffix' => $suffix]));
        
        return $this->generateLandscape($itemsToParse, $suffix);
    }

    public function specific()
    {
        //TODO options for portrait and landscape
        //ids to process pickslips for
        $i = gv('i');
        $c = gv('c');
        $suffix = 'specifc';
        return $this->generateLandscape(array("$i:$c"), $suffix);
    }

    public function archive()
    {
        $template_vars = array();
        $template_vars['template'] = 'archive';

        $dir = new DirectoryIterator(Config::get('approot') . '/www/barcodes');
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                $fn = $fileinfo->getFilename();
                $ct = $fileinfo->getCTime();
                if (strpos($fn, '.pdf') !== false) {
                    if ($ct < (time() - 302200)) {
                        $rm = Config::get('approot') . '/www/barcodes/' . $fn;
                        unlink($rm);
                        error_log('Deleted pickslip - older than 3 days: ' . $fn);
                    } else {
                        $url = Config::get('baseurl') . 'barcodes/' . $fn;
                        preg_match('/location_([a-z]*){1}.*pickslips_([a-z0-9@_]*){1}.pdf/i', $fn, $output_array);
                        $template_vars['files'][$ct]['location'] = str_replace('_', ' ', isset($output_array[1]) ? $output_array[1] : 'all');
                        $template_vars['files'][$ct]['time'] = str_replace('_', ' ', isset($output_array[2]) ? $output_array[2] : $output_array[1]);
                        $template_vars['files'][$ct]['url'] = $url;
                    }
                }
            }
        }

        if (isset($template_vars['files'])) {
            ksort($template_vars['files']);
        }

        return $template_vars;
    }

    private function initPDF($orientation, $date, $user)
    {
        // create new PDF document
        $pdf = new TCPDF($orientation, 'mm', 'A4', true, 'UTF-8', false, true);

        // set document information
        $pdf->SetCreator('Generated on ' . $date);
        $pdf->SetAuthor('UBC Library');
        $pdf->SetTitle('Pickslips');
        $pdf->SetSubject('Picksliips');
        $pdf->SetKeywords('Pickslips');

        // set default header data
        //$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Pickslips', PDF_HEADER_STRING . "\r\n" . 'Generated: ' . $date . " by $user");
        $pdf->SetPrintHeader(false); //LOCRSUPP-94 remove header

        // set header and footer fonts
        //$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN)); //LOCRSUPP-94 remove header
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        //$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetMargins(7, 7, 7);
        //$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set a barcode on the page footer
        $pdf->setBarcode(date('d/M/y Hi'));

        // set font
        $pdf->SetFont('helvetica', '', 10);//change $paddingTop if you change this

        return $pdf;
    }


    private function generateLandscape($itemsToParse, $suffix = 'Location: All')
    {
        $licr = getModel('licr');
        $_utility = getModel('utility');
        $user = sv('lastname') . ', ' . sv('firstname') . ' (' . sv('puid') . ')';

        $date = date('D jS M Y @ Hi');
        $pdf = $this->initPDF('L', $date, $user);

        // add a page
        $pdf->AddPage();

        // CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9. /* $pdf->Cell(0, 0, 'LOCR ID: '.intval($item)."  |course::".$course_id, 0, 1); $pdf->write1DBarcode(intval($item), 'C39', '', '', '', 10, 0.5, $style, 'N');            */

        //physical (vertical) height in mm of a single pickslip
        $paddingTop = 1;
        $height = ($this->a4w) / 2;
        $cellHeight = 7;
        $border = $this->borderLineStyle;
        $pullUp = 35;
        $count = 0;

        $_bibdata = getModel('bibdata');
        $_voyager = getModel('voyager');

        //give physical format a display name


        $firstEntry = true;
        $itemLimit = count($itemsToParse);
        $itemCount = 0;
        foreach ($itemsToParse as $item) {
            $itemCount++; //add up to number of items, for final page
            $courseCount = 0;

            $parts = explode(':', $item);
            $item = (int)$parts[0];
            //preg_match_all('/^([0-9]+)/', $parts[1], $courses);
            $matches = null;
            $failed = preg_match_all('/(\\d+)|(?:[a-zA-Z-_.0-9]+,?)/', $parts[1], $course_parts);
            $courses = array();
            if (count($course_parts) > 0) {
                foreach ($course_parts[1] as $matchedCourse) {
                    if (trim($matchedCourse) !== '') {
                        $courses[] = $matchedCourse;
                    }
                }
            }
            unset($parts);
            unset($course_parts);
            $courseLimit = count($courses);
            
            //error_log('PRINT_PICKSLIP_COURSES: ' . json_encode($courses));
            
            foreach ($courses as $cid) {
                $courseCount++; //add up to total number of courses, for final page
                $course_id = $cid;
                $info = $licr->getArray('GetItemInfo', array('item_id' => $item));
                $courseinfo = $licr->getArray('GetCourseInfo', array('course' => $course_id));
                $courseiteminfo = $licr->getArray('GetCIInfo', array('course' => $course_id, 'item_id' => $item));

                //error_log('GetCINotes: array(roles_multi7, item_id => ' . intval($item) . ', course => ' . $course_id .')');
                $notes = $licr->getArray('GetCINotes', array('roles_multi' => 7, 'item_id' => $item, 'course' => $course_id));

                if (isset($notes) && count($notes) > 0) {
                    $note = array_pop($notes[$item]);
                } else {
                    unset($note);
                }
                $instructorlist = '';
                
                if (isset($courseinfo['instructors'])) {
                    foreach ($courseinfo['instructors'] as $instructor) {
                        $instructorlist .= $instructor['lastname'] . ', ' . $instructor['firstname'] . '; ';
                    }
                }

                $b = $_bibdata->getBibdata($info['bibdata']);
                $bibdata = $b['bibdata'];

                //location
                $template_vars['locations'] = false;
                $shelfLocation = '';
                if (isset($info['callnumber']) && $info['callnumber'] != '') {
                    $availabilityID = json_decode(file_get_contents(Config::get('availability_endpoint') . urlencode($info['callnumber'])));
                    $locations = $_voyager->getAvailability($availabilityID);
                    if ($locations['status'] == -1) {
                        $shelfLocation = $locations['locations'];
                    } else {
                        foreach ($locations['locations'] as $entry) {
                            $shelfLocation .= $entry['location'] . ", ";
                        }
                        $shelfLocation = rtrim($shelfLocation);
                        $shelfLocation = rtrim($shelfLocation, ",");
                    }
                } else {
                    $shelfLocation = "Could not be determined";
                }

                //ensure every metadata item is accounted for or defaulted to a value to prevent tcpdf crashing
                $title = (!isset($bibdata['item_title']) || $bibdata['item_title'] == '' ? '---' : $bibdata['item_title']);

                $author = str_replace(';', '; ', (!isset($bibdata['item_author']) || $bibdata['item_author'] == '' ? '---' : $bibdata['item_author']));
                $author = (strlen($author) > 160) ? substr($author, 0, 157) . '...' : $author;

                $edition = (!isset($bibdata['item_edition']) || $bibdata['item_edition'] == '' ? '---' : $bibdata['item_edition']);
                $publisher = (!isset($bibdata['item_publisher']) || $bibdata['item_publisher'] == '' ? '---' : $bibdata['item_publisher']);
                $year = (!isset($bibdata['item_pubdate']) || $bibdata['item_pubdate'] == '' ? '---' : $bibdata['item_pubdate']);

                $isxn = (!isset($bibdata['item_isxn']) || $bibdata['item_isxn'] == '' ? '---' : $bibdata['item_isxn']);

                $callNumber = (!isset($info['callnumber']) || $info['callnumber'] == '' ? '---' : $info['callnumber']);
                $branch = (!isset($courseinfo['branch']) || $courseinfo['branch'] == '' ? '---' : $courseinfo['branch']);
                $class = (!isset($courseinfo['title']) || $courseinfo['title'] == '' ? '---' : $courseinfo['title']);
                $class = (strlen($class) > 76) ? substr($class, 0, 74) . '...' : $class;
                $returnValue = preg_match('/\\d{4}(W|S){1}(1|2){1}/', $courseinfo['lmsid'], $matches);
                $semester = ($returnValue == 0 ? '---' : $matches[0]);
                $coursecode = (!isset($courseinfo['coursecode']) || $courseinfo['coursecode'] == '' ? '---' : $courseinfo['coursecode']);
                $coursenumber = (!isset($courseinfo['coursenumber']) || $courseinfo['coursenumber'] == '' ? '---' : $courseinfo['coursenumber']);
                $coursestart = (!isset($courseiteminfo['dates']['course_item_start']) || $courseiteminfo['dates']['course_item_start'] == '' ? '---' : $courseiteminfo['dates']['course_item_start']);
                $coursestop = (!isset($courseiteminfo['dates']['course_item_end']) || $courseiteminfo['dates']['course_item_end'] == '' ? '---' : $courseiteminfo['dates']['course_item_end']);
                $request_time = (!isset($courseiteminfo['request_time']) || $courseiteminfo['request_time'] == '' ? '---' : $courseiteminfo['request_time']);
                $loanperiod = (!isset($courseiteminfo['loanperiod']) || $courseiteminfo['loanperiod'] == '' ? '---' : $courseiteminfo['loanperiod']);


                ++$count;

                // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

                $pdf->Line(0, 0, 0, 0, $this->borderLineStyle);

                // left hand barcode
                $pdf->setCellHeightRatio(1.25);
                $pdf->StartTransform();
                if ($firstEntry) {
                    $pdf->Rotate(90, 15, $height * 1 - $pullUp);
                    $pdf->setXY(15, $height * 1 - $pullUp);
                } else {
                    $pdf->Rotate(90, 15, 168);
                    $pdf->setXY(15, 168);
                }
                $pdf->write1DBarcode($item, 'C39', '', '', '', 14, 0.5, $this->barcodeStyle, 'N');
                $pdf->StopTransform();

                $y_axis = $firstEntry ? ($height * 1 - 126 + $pullUp) : ($height * 2 - ((98)));
                //write id, title and barcode into top row
                $pdf->setCellHeightRatio(1.5);

                //align barcode to the right (x = 230)
                $pdf->setXY(215, $y_axis);
                $pdf->write1DBarcode($item, 'C39', '', '', '', 12, 0.6, $this->barcodeStyle, 'N');

                //reset X to left hand side. Y is same as above, so its on the same starting line
                $pdf->setXY(30, $y_axis);

                //set spacing for readability
                $pdf->setCellPaddings(2, $paddingTop, 0, 0); //need left 15 to cater for left hand barcode

                //what we are writing
                $text = '(ItemID: ' . $item . ")";
                $pdf->MultiCell(40, $cellHeight + 4, $text, $this->borderLineStyle, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T');//write id and title

                $text = 'Title: ' . $title;
                $pdf->MultiCell(143, $cellHeight + 4, $text, $this->borderLineStyle, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title

                //keep manipulating y calculation based on previous cell height
                $y_axis += $cellHeight + 4;//calculated one-off because of the barcode height
                $pdf->setXY(30, $y_axis);

                $text = 'Author: ' . $author;
                $pdf->MultiCell(253, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title

                //keep manipulating y calculation based on previous cell height
                $y_axis += $cellHeight;
                $pdf->setXY(30, $y_axis);

                //what we are writing
                $text = 'Edition: ' . $edition;
                $pdf->MultiCell(60, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title
                $text = 'Publisher: ' . $publisher;
                $pdf->MultiCell(150, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title
                $text = 'Year: ' . $year;
                $pdf->MultiCell(43, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title

                //keep manipulating y calculation based on previous cell height
                $y_axis += $cellHeight;
                $pdf->setXY(30, $y_axis);

                //what we are writing
                $text = 'Shelf Location: ' . $shelfLocation;
                $pdf->MultiCell(253, $cellHeight + 5, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title

                //keep manipulating y calculation based on previous cell height
                $y_axis += $cellHeight + 5; //calculated one-off because of the Shelf Location height as per LOCRSUPP-329
                $pdf->setXY(30, $y_axis);

                //what we are writing
                $text = 'Call Number: ' . $callNumber;
                $pdf->MultiCell(100, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title
                $text = 'Class: ' . $class;
                $pdf->MultiCell(153, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title

                //keep manipulating y calculation based on previous cell height
                $y_axis += $cellHeight;
                $pdf->setXY(30, $y_axis);

                //what we are writing
                $text = 'Owning Branch: ' . $branch;
                $pdf->MultiCell(100, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title
                $text = 'Semester: ' . $semester;
                $pdf->MultiCell(43, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title
                $text = 'Course: ' . $coursecode . $coursenumber;
                $pdf->MultiCell(40, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title
                $text = 'Start: ' . $coursestart;
                $pdf->MultiCell(35, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title
                $text = 'End: ' . $coursestop;
                $pdf->MultiCell(35, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title

                //keep manipulating y calculation based on previous cell height
                $y_axis += $cellHeight;
                $pdf->setXY(30, $y_axis);

                //what we are writing
                $text = 'Format: ';
                $text .= (isset($info['physical_format']) && $info['physical_format'] != '') ? $this->switchFormat[$info['physical_format']] : 'could not be determined';
                $pdf->MultiCell(60, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title
                $text = 'Loan Period: ' . $loanperiod;
                $pdf->MultiCell(40, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title
                $text = 'Requested: ' . $request_time;
                $pdf->MultiCell(90, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title
                $text = 'ISXN: ' . $isxn;
                $pdf->MultiCell(63, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title

                //keep manipulating y calculation based on previous cell height
                $y_axis += $cellHeight;
                $pdf->setXY(30, $y_axis);

                $text = 'Instructor(s): ' . $instructorlist;
                $pdf->MultiCell(253, $cellHeight, $text, $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T'); //write id and title

                //keep manipulating y calculation based on previous cell height
                $y_axis += $cellHeight;
                $pdf->setXY(15, $y_axis);

                $pdf->setCellPaddings(0, $paddingTop - 30, 0, 0);
                $content = isset($note) && count($note) > 1 ? $note['content'] : "No notes found.";
                $pdf->Cell(0, 0, 'Processing Notes: ' . $content, 0, 1);
                $y_axis += $cellHeight * 3 - 30;

                //either draw a divider, or break the page, unless if last item
                $pdf->setCellHeightRatio(1.25);
                if ($firstEntry) {
                    $pdf->Ln();
                    $firstEntry = !$firstEntry;
                } else {
                    if ($itemCount == $itemLimit && $courseCount == $courseLimit) {
                        //left because I had error logs in here
                    } else {
                        $pdf->AddPage();
                    }
                    $count = 0;
                    $firstEntry = !$firstEntry;
                }
            }
        }
        
        $branch = str_replace(' ', '_', $suffix);
        $branch = str_replace(':', '', $branch);
        $branch = strtolower($branch);
        
        if(stripos($branch,'location_') !== 0) {
            $branch = 'location_all';
        }

        $generatedPDFFilePath = Config::get('approot') . '/www/barcodes/' . $branch . '_pickslips_' . str_replace(' ', '_', $date) . '.pdf';
        
        //save pdf to server
        $pdf->Output($generatedPDFFilePath, 'F');
        $template_vars = array();
        $template_vars['name'] = 'pickslips_' . str_replace(' ', '_', $date);
        $template_vars['template'] = 'pickslips';
        $template_vars['url'] = (Config::get('baseurl')) . 'barcodes/' . strtolower(str_replace(':', '', str_replace(' ', '_', $suffix))) . '_pickslips_' . str_replace(' ', '_', $date) . '.pdf';

        $_utility->setPickslip(array('time' => time(), 'url' => $template_vars['url']));

        //return PDF
        return $template_vars;
    }

    private function generatePortrait()
    {
        $licr = getModel('licr');
        $_utility = getModel('utility');
        $user = sv('lastname') . ', ' . sv('firstnam') . '(' . sv('puid') . ')';

        $date = date('D jS M Y @ Hi');

        // create new PDF document
        $pdf = $this->initPDF('P', $date, $user);

        //add page to print to
        $pdf->AddPage();

        //ids to process pickslips for
        $itemsToParse = pv('itemids');
        // CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9. /* $pdf->Cell(0, 0, 'LOCR ID: '.intval($item)."  |course::".$course_id, 0, 1); $pdf->write1DBarcode(intval($item), 'C39', '', '', '', 10, 0.5, $style, 'N');            *

        //physical (vertical) height in mm of a single pickslip
        $height = 123.5;
        $count = 0;

        $_bibdata = getModel('bibdata');
        $_voyager = getModel('voyager');

        //need to keep track/toggle between dividing line and page break
        $firstEntry = true;
        $itemLimit = count($itemsToParse);
        $itemCount = 0;

        foreach ($itemsToParse as $item) {
            $itemCount++;//add up to number of items, for final page
            $courseCount = 0;

            $parts = explode(':', $item);
            $item = (int)$parts[0];
            $matches = null;
            $failed = preg_match_all('/(\\d+)|(?:[a-zA-Z-_.0-9]+,?)/', $parts[1], $course_parts);
            $courses = array();
            if (count($course_parts) > 0) {
                foreach ($course_parts[1] as $matchedCourse) {
                    if (trim($matchedCourse) !== '') {
                        $courses[] = $matchedCourse;
                    }
                }
            }
            unset($parts);
            unset($course_parts);
            $courseLimit = count($courses);
            foreach ($courses as $cid) {
                $courseCount++;//add up to tital number of courses, for final page
                $course_id = (int)$cid;

                $info = $licr->getArray('GetItemInfo', array('item_id' => $item));
                $courseinfo = $licr->getArray('GetCourseInfo', array('course' => $course_id));
                $courseiteminfo = $licr->getArray('GetCIInfo', array('course' => $course_id, 'item_id' => $item));

                $notes = $licr->getArray('GetCINotes', array('roles_multi' => 7, 'item_id' => $item, 'course' => $course_id));
                $note = array_pop($notes[$item]);

                $instructorlist = '';
                if (isset($courseinfo['instructors'])) {
                    foreach ($courseinfo['instructors'] as $instructor) {
                        $instructorlist .= $instructor['lastname'] . ', ' . $instructor['firstname'];
                    }
                }

                $b = $_bibdata->getBibdata($info['bibdata']);
                $bibdata = $b['bibdata'];

                //location
                $availabilityID = $b['availabilityId'];
                $template_vars['locations'] = false;
                $locationString = '';
                if (isset($availabilityID) && $availabilityID != '') {
                    $locations = $_voyager->getAvailability($availabilityID);
                    if ($locations['status'] == -1) {
                        $locationString = $locations['locations'];
                    } else {
                        foreach ($locations['locations'] as $entry) {
                            $locationString .= $entry['location'] . ", ";
                        }
                        $locationString = rtrim($locationString);
                        $locationString = rtrim($locationString, ",");
                    }
                } else {
                    $locationString = "Could not be determined";
                }

                $title = (!isset($bibdata['item_title']) || $bibdata['item_title'] == '' ? '---' : $bibdata['item_title']);

                $author = (!isset($bibdata['item_author']) || $bibdata['item_author'] == '' ? '---' : $bibdata['item_author']);

                $shelfLocation = $locationString;

                $edition = (!isset($bibdata['item_edition']) || $bibdata['item_edition'] == '' ? '---' : $bibdata['item_edition']);
                $publisher = (!isset($bibdata['item_publisher']) || $bibdata['item_publisher'] == '' ? '---' : $bibdata['item_publisher']);
                $year = (!isset($bibdata['item_pubdate']) || $bibdata['item_pubdate'] == '' ? '---' : $bibdata['item_pubdate']);

                $callNumber = (!isset($info['callnumber']) || $info['callnumber'] == '' ? '---' : $info['callnumber']);
                $branch = (!isset($courseinfo['branch']) || $courseinfo['branch'] == '' ? '---' : $courseinfo['branch']);
                $class = (!isset($courseinfo['title']) || $courseinfo['title'] == '' ? '---' : $courseinfo['title']);
                $returnValue = preg_match('/\\d{4}(W|S){1}(1|2){1}/', $courseinfo['lmsid'], $matches);
                $semester = ($returnValue == 0 ? '---' : $matches[0]);
                $coursecode = (!isset($courseinfo['coursecode']) || $courseinfo['coursecode'] == '' ? '---' : $courseinfo['coursecode']);
                $coursenumber = (!isset($courseinfo['coursenumber']) || $courseinfo['coursenumber'] == '' ? '---' : $courseinfo['coursenumber']);
                $coursestart = (!isset($courseiteminfo['dates']['course_item_start']) || $courseiteminfo['dates']['course_item_start'] == '' ? '---' : $courseiteminfo['dates']['course_item_start']);
                $coursestop = (!isset($courseiteminfo['dates']['course_item_end']) || $courseiteminfo['dates']['course_item_end'] == '' ? '---' : $courseiteminfo['dates']['course_item_end']);
                $request_time = (!isset($courseiteminfo['request_time']) || $courseiteminfo['request_time'] == '' ? '---' : $courseiteminfo['request_time']);
                $loanperiod = (!isset($courseiteminfo['loanperiod']) || $courseiteminfo['loanperiod'] == '' ? '---' : $courseiteminfo['loanperiod']);


                ++$count;

                $itemdata = 'LOCR ID: ' . $item . "\r\n" . 'Title: ' . $title . "\r\n" . 'Shelf Location: ' . $shelfLocation . "\r\n" . 'Owning Branch: ' . $branch . "\r\n" . 'Call Number: ' . $callNumber . "\r\n" . 'Format: ' . $this->switchFormat[$info['physical_format']] . "\r\n\r\n" . 'Loan Period: ' . $loanperiod;
                $bibbdata = 'Author: ' . $author . "\r\n" . 'Edition: ' . $edition . "\r\n" . 'Publisher: ' . $publisher . "\r\n" . 'Year: ' . $year . "\r\n" . 'Class: ' . $class . "\r\n" . 'Semester: ' . $semester . '   | Course: ' . $coursecode . $coursenumber . "\r\n" . 'Start: ' . $coursestart . '   | End: ' . $coursestop . "\r\n" . 'Instructor: ' . $instructorlist . "\r\n" . 'Requested: ' . $request_time;

                //barcode
                $pdf->setCellHeightRatio(1.25);
                $pdf->StartTransform();
                if ($firstEntry) {
                    $pdf->Rotate(90, 15, $height * 1 - 33);
                    $pdf->setXY(15, $height * 1 - 33);
                } else {
                    $pdf->Rotate(90, 15, $height * 2 - 33);
                    $pdf->setXY(15, $height * 2 - 33);
                }

                $pdf->write1DBarcode($item, 'C39', '', '', '', 10, 0.5, $this->barcodeStyle, 'N');
                $pdf->StopTransform();

                //metadata
                $pdf->setCellHeightRatio(1.5);
                if ($firstEntry) {
                    $pdf->setXY(15, $height * 1 - 90);
                } else {
                    $pdf->setXY(15, $height * 2 - 90);
                }
                $pdf->setCellPaddings(15, 0, 0, 0);
                $pdf->MultiCell(90, 65, $itemdata, 0, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T');
                $pdf->setCellPaddings(8, 0, 0, 0);
                $pdf->MultiCell(90, 65, $bibbdata, 0, 'L', 0, 1, '', '', true, 0, false, true, 0, 'T');
                $content = isset($note) && count($note) > 1 ? $note['content'] : "No notes found.";
                $pdf->Cell(0, 0, 'Processing Notes: ' . $content, 0, 1);

                //either draw a divider, or break the page, unless if last item
                $pdf->setCellHeightRatio(1.25);
                if ($firstEntry) {
                    $pdf->Line(15, ($height * 1 + 22), 195, (($height * 1 + 22)), $this->dividingLineStyle);
                    $pdf->Ln();
                    $firstEntry = !$firstEntry;
                } else {
                    if ($itemCount == $itemLimit && $courseCount == $courseLimit) {
                        //left because I had error logs in here
                    } else {
                        $pdf->AddPage();
                    }
                    $count = 0;
                    $firstEntry = !$firstEntry;
                }
            }
        }

        //save pdf to server
        $pdf->Output(Config::get('approot') . '/www/barcodes/pickslips_' . str_replace(' ', '_', $date) . '.pdf', 'F');
        $template_vars = array();
        $template_vars['name'] = 'pickslips_' . str_replace(' ', '_', $date);
        $template_vars['template'] = 'pickslips';
        $template_vars['url'] = (Config::get('baseurl')) . 'barcodes/pickslips_' . str_replace(' ', '_', $date) . '.pdf';

        $_utility->setPickslip(array('time' => time(), 'url' => $template_vars['url']));


        //return PDF
        return $template_vars;
    }
}
