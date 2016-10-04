<?php

    class Controller_dashboard
    {

        public function  dashboard()
        {
            $template_vars = array();
            return $template_vars;
        }

        public function memcache()
        {
            $s = MC::runCommand('getStats');
            $stats = array();
            foreach($s as $server=>$statistics){
                $p = explode(':', $server);
                $stats[$p[0]]['name'] = $p[0];
                $stats[$p[0]]['port'] = $p[1];
                $stats[$p[0]]['data'] = $statistics;

            }
            $akeys = MC::runCommand('getAllKeys');
            $slist = MC::runCommand('getServerList');
            $mvrsn = MC::runCommand('getVersion');

            return array(
                'template'   => 'view',
                'statistics' => $stats,
                'allkeys'    => $akeys,
                'serverlist' => $slist,
                'mversion'   => $mvrsn,
            );
        }
    }