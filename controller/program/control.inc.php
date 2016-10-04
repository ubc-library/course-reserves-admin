<?php

    class Controller_program
    {
        private $licr;

        public function __construct()
        {
            $this->licr = getModel('licr');
        }

        public function program()
        {
            //list programs
            $programs = $this->licr->getArray('ListAllPrograms');
            //var_export($programs);
            //sort by grad year desc, name asc
            return array('level' => 'top', 'programs' => $programs, 'baseurl_cr' => Config::get('baseurl_cr'));
        }

        public function courses()
        {
            //list courses in a program for add/edit/delete
            $program_id = gv('edit');
            if (!$program_id) {
                return array('controller_error' => 'Program ID missing');
            }
            $courses = $this->licr->getArray('ListProgramCourses', array('program' => $program_id));
            if (!$courses) {
                return array('controller_error' => "Program ID $program_id not found");
            }
            $program_info = $this->licr->getArray('GetProgramInfo', array('program' => $program_id));
            $tvars = array(
                'level'          => 'program'
                , 'program_info' => $program_info
                , 'courses'      => $courses
            );
//    var_export($tvars);
            return $tvars;
        }

        public function create()
        {
            //create program with specified name and graduation year
            //called via ajax, returns json
            $name = pv('name');
            $year = pv('year');
            if (!$name || !$year) {
                $result = array('success' => FALSE, 'message' => 'Missing program name or graduation year');
            } else {
                $result = $this->licr->getArray('CreateProgram', array('name' => $name, 'gradyear' => $year));
            }
            return_json($result);
        }

        public function update() {
            $id = pv('id');
            $name = pv('name');
            $year = pv('year');
            if (!$name || !$year) {
                $result = array('success' => FALSE, 'message' => 'Missing program name or graduation year');
            } else {
                $result = $this->licr->getArray('UpdateProgram', array('id' => $id, 'name' => $name, 'gradyear' => $year));
            }
            return_json($result);
        }

        public function delete()
        {
            $program_id = pv('program');
            if (!$program_id) {
                $result = array('success' => FALSE, 'message' => 'No program ID specified');
            } else {
                $result = $this->licr->getArray('DeleteProgram', array('program' => $program_id));
            }
            return_json($result);
        }

        public function coursesearch()
        {
            $part = pv('partial');
            $res = $this->licr->getArray('SearchCourses', array('search_string' => $part, 'current' => 0));
            //convert to id,label,value
            $ret = array();
            foreach ($res as $result) {
                $ret[] = array('id' => $result['course_id'], 'label' => $result['title'], 'value' => $result['course_id']);
            }
            return_json($ret);
        }

        public function add_course()
        {
            //add course_id to program_id
            $course = pv('course');
            $program = pv('program');
            if (!$course || !$program) {
                $result = array('success' => FALSE, 'message' => 'Missing course and/or program');
            } else {
                $result = $this->licr->getArray('AddCourseToProgram', array('course' => $course, 'program' => $program));
            }
            return_json($result);
        }

        public function remove_course()
        {
            //remove course_id from program_id
            $course = pv('course');
            $program = pv('program');
            if (!$course || !$program) {
                $result = array('success' => FALSE, 'message' => 'Missing course and/or program');
            } else {
                $result = $this->licr->getArray('RemoveCourseFromProgram', array('course' => $course, 'program' => $program));
            }
            return_json($result);
        }
    }
