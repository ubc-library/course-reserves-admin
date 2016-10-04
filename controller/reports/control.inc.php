<?php

class Controller_reports{

    public function reports () {
        //we have an item to display
        $_utility       = getModel('utility');
        $template_vars  = array();

        $template_vars['brokenlinks']   = $_utility->getMenuBrokenLinks();
        $template_vars['newqcount']     = $_utility->getMenuNewItems();
        return $template_vars;
    }

    public function get(){

        $start_date = strtotime((pv('start') !== '' ? pv('start') : $this->getAcademicYearStart()));
        $end_date = strtotime((pv('end') !== '' ? pv('end') : "now"));

        $start_date = date('Y-m-d H-i-s', $start_date);
        $end_date   = date('Y-m-d H-i-s', $end_date);
        $res = $this->getReport($start_date, $end_date);
        return array(
            'template' => 'json',
            'json'       => json_encode(
                array(
                    'success'   => 'this is a fake success',
                    'data'      => $res['data'],
                    'url'       => $res['url']
                )
            )
        );
    }

    private function getAcademicYearStart(){
        $acad_year = ((int)date('Y', strtotime("now")));
        if ((int)date('m', strtotime("now")) < 9){
            $acad_year -=1;
            return "$acad_year-09-02 00:00:00";
        } else {
            return "$acad_year-09-02 00:00:00";
        }
    }

    private function getReport($start,$end){
        $dbh   = new PDO(Config::get('reports_dbms') . ':host=' . Config::get('reports_host') . ';dbname=' . Config::get('reports_name'), Config::get('reports_user'), Config::get('reports_pass'));
        $stmt = $dbh->prepare("CALL report__all_items_between_dates(?,?)");
        $stmt->bindParam(1, $start, PDO::PARAM_STR);
        $stmt->bindParam(2, $end, PDO::PARAM_STR);
        // call the stored procedure
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        $dbh = null;

        $now = strtotime("now");

        $file = Config::get('approot'). Config::get('reports_dir') . "/report-" . sv('puid') ."bet-$start-and-$end-$now.csv";
        $nonWWWReportDir = str_replace('/www/','',Config::get('reports_dir'));
        $url  = Config::get('baseurl') . $nonWWWReportDir . '/report-' . sv('puid') . "bet-$start-and-$end-$now.csv";//baseurl has no trailing slash
            // Open the file to get existing content
        $csv = implode(",", array_keys(reset($rows))) . PHP_EOL;
        foreach ($rows as $row) {
            $csv .= '"' . implode('","', $row) . '"' . PHP_EOL;
        }
        // Write the contents back to the file
        file_put_contents($file, $csv);
        return array(
             'data' => $rows
            ,'url'  => $url
        );
    }
}