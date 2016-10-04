<?php

class Controller_eBooks
{
    private $licrmodel;
    private $bibmodel;

    public function __construct()
    {
        $this->licrmodel = getModel('licr');
        $this->bibmodel = getModel('bibdata');
    }

    public function eBooks()
    {
        $data = $this->licrmodel->getArray('eBooksReport', array());
        print "<table width='100%' border='1'><tr><th width='10%'>ItemID</th><th width='10%'>Title</th><th width='10%'>Author</th>
<th width='10%'>Publisher</th><th width='10%'>Publication Date</th>
<th width='10%'>URI</th><th width='10%'>Short URL</th><th width='10%'>File Location</th><th width='10%'>Format</th>
<th width='10%'>Course ID</th><th width='10%'>Course Title</th><th width='10%'>Course LMSID</th><th width='10%'>Section</th>
<th width='10%'>Course Start Date</th><th width='10%'>Course End Date</th></tr>";

        foreach ($data as $datum) {

            //var_dump($datum);die();
            $item_id = $datum['item_id'];
            $item_title = $datum['item_title'];
            $item_author = $datum['author'];
            $item_uri = $datum['uri'];
            $item_format = $datum['physical_format'];
            $item_filelocation = $datum['filelocation'];
            $item_shorturl = $datum['shorturl'];

            $bibdata = $this->bibmodel->getBibdata($datum['bibdata']);
            $bibdata = $bibdata['bibdata'];

            //print_r($bibdata);
            $item_publisher = $bibdata['item_publisher'];
            $item_pubdate = $bibdata['item_pubdate'];

            $course_title = $datum['course_title'];
            $course_id = $datum['course_id'];
            $course_lmsid = $datum['lmsid'];
            $course_section = $datum['section'];
            $course_startdate = $datum['startdate'];
            $course_enddate = $datum['enddate'];

            //Print results in a row
            print "<tr><td><a href='/details.item/id/$item_id'>$item_id</a></td><td>$item_title</td><td>$item_author</td>
<td>$item_publisher</td><td>$item_pubdate</td>
<td><a href='$item_uri'>URI</a></td><td><a href='$item_shorturl'>Short URL</a></td><td><a href='$item_filelocation'>File Location</a></td><td>$item_format\
</td>
<td>$course_id</td><td>$course_title</td><td>$course_lmsid</td><td>$course_section</td><td>$course_startdate</td><td>$course_enddate</td>
</tr>";
        }
        print "</table>";
    }
}
