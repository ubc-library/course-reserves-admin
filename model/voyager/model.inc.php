<?php

    /*
    * methods for interacting with Voyager
    *
    */


    class Model_voyager
    {
        /*
        * @return Array
        */

        public function call($command, $params)
        {
            if (method_exists($this, $command)) {
                return $this->$command($params);
            }
            return FALSE;
        }

        public function getAvailability($id)
        {
            //TODO this is wayyy too magical

            $itemBibid = str_replace("9KB", "", preg_replace("/[\s]/", "", $id));

            if ($itemBibid == "") {
                return array('status' => '-1', 'locations' => 'Could not be determined');
            }
            error_log("Finding availability of: $itemBibid");
            $voyagerURL = Config::get('availability_endpoint_ils') . $itemBibid;
            $voyagerXML = (simplexml_load_file($voyagerURL));
            $bibrecord = $voyagerXML->bibrecord;

            if (count((array)$bibrecord) === 0 || $bibrecord->count() === 0) {
                return array('status' => '-1', 'locations' => 'Could not be determined');
            }

            $locations = array();
            foreach ($bibrecord->item as $item) {
                $locations [] = array(
                    'status'     => (string)$item->status,
                    'location'   => (string)$item->location,
                    'callnumber' => (string)$item->callnumber
                );
            }

            return array('status' => '1', 'locations' => $locations);
        }
    }
