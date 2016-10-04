<?php

class Controller_report
{
    public function physical()
    {
        $course_id = gv('course_id');
        if (!$course_id) {
            return array('controller_error' => 'No course ID specified');
        }
        $licr = getModel('licr');
        $tvars['physical'] = $licr->getArray('Report_Physical', array('course' => $course_id));
        foreach ($tvars['physical'] as $i => $data) {
            $tvars['physical'][$i]['required'] = $data['required'] ? 'Yes' : 'No';
        }
        $tvars['course'] = $licr->getArray('GetCourseInfo', array('course' => $course_id));
        $tvars['report'] = 'Physical Item Report for ' . $tvars['course']['title'];
        $tvars['headings'] = array(
            'title' => 'Title'
        , 'author' => 'Author(s)'
        , 'edition' => 'Edition'
        , 'publisher' => 'Publisher'
        , 'pubdate' => 'Publication Date'
        , 'physical_format' => 'Format'
        , 'isxn' => 'ISBN'
        , 'callnumber' => 'Call Number'
        , 'required' => 'Required'
        , 'start' => 'Start Date'
        , 'end' => 'End Date'
        );
        return $tvars;
    }
}
