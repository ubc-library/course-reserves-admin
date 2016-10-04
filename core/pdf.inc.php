<?php
require_once('tcpdf/tcpdf.php');
require_once('tcpdf/tcpdi.php');

class ubcDisclaimerPDF extends TCPDI {
    //the array of pdf's to join, in order in which to be joined
    private $files = array();

    /**
     *
     *  add pdfs to be processed
     *  @param string $pdfURI add a pdf to the array
     *
     */
    public function addFiles($files) {
        $this->files = $files;
    }

    public function concat() {
        foreach($this->files AS $file) {
            $pagecount = $this->setSourceFile($file);
            for ($i = 1; $i <= $pagecount; $i++) {
                $tplidx = $this->importPage($i);
                $s = $this->getTemplateSize($tplidx);
                if($s['h'] > $s['w']){
                    $this->AddPage('P', array($s['w'], $s['h']));
                }
                else {
                    $this->AddPage('L', array($s['w'], $s['h']));
                }
                $this->useTemplate($tplidx);
            }
        }
    }
}

class ubcPDF extends TCPDI {
    //Page header
    public function Header() {
        // get the current page break margin
        $bMargin = $this->getBreakMargin();
        // get current auto-page-break mode
        $auto_page_break = $this->AutoPageBreak;
        // disable auto-page-break
        $this->SetAutoPageBreak(false, 0);
        // set bacground image
        $img_file = Config::get('approot').'/core/tcpdf/header.jpg';
        // Image(x-pos, y-pos, w, h, type, link, align, resize, dpi, palign, ismask, imgmask, border, fitbox, hidden, fironpage, alt, altimgs)
        $this->Image($img_file, 0, 0, 210, 297, '', '', '', false, 300, '', false, false, 0);
        // restore auto-page-break status
        $this->SetAutoPageBreak($auto_page_break, $bMargin);
        // set the starting point for the page content
        $this->setPageMark();
    }
}