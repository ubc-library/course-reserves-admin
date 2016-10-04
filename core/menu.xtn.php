<?php

class Appmenu_Twig_Extension extends Twig_Extension {

    public function getName()
    {
        return 'appmenu';
    }

    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction('getmenu', 'getMenu'),
        );
    }

    public function getMenu()
    {
        $licr=getModel('licr');
        
        //$memcache->delete($brokenlinks);
        if (!($template_vars['brokenlinks'] = MC::get('brokenlinks'))) {
            if (MC::getResultCode() == Memcached::RES_NOTFOUND) {
                $brokenlinks = $licr->getJSON('ListBrokenItems');
                $template_vars['brokenlinks'] = isset($brokenlinks['data']) ? count($brokenlinks['data']) : -1;
                MC::set('brokenlinks', $template_vars['brokenlinks'],0);
            }
        }

        if(isset($location)){
            $requests=$licr->getArray('GetHomepageData',array('branch'=> (int)$location));
            while(list($key,$val) = each ($template_vars['branches'])){
                if($val['branch_id'] == $location)
                    $template_vars['branch']=$val['branch_name'];
            }
        }
        else {
            $requests=$licr->getArray('GetHomepageData');
        }

        $new = $requests['New'];
        $template_vars['newrecs'] = array();
        $template_vars['newqcount']= 0;
        foreach($new as $k => $v){
            $template_vars['newrecs'][strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($k)))]['disp'] = $k;
            $template_vars['newrecs'][strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($k)))]['suff'] = strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($k)));
            foreach($v as $row){
                $template_vars['newrecs'][strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($k)))]['recs'][] = $row;
            }
            $template_vars['newqcount']+= count($template_vars['newrecs'][strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($k)))]['recs']);
        }
        MC::set('newitemcount', $template_vars['newqcount'],0);

        $inprocess=$requests['InProcess'];
        $template_vars['inprocs'] = array();
        $template_vars['inpqcount']= 0;
        foreach($inprocess as $k => $v){
            foreach($v as $row){
                $template_vars['inprocs'][strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($row['status'])))]['disp'] = $row['status'];
                $template_vars['inprocs'][strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($row['status'])))]['suff'] = strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($row['status'])));
                $row['type'] = $k;
                $template_vars['inprocs'][strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($row['status'])))]['recs'][] = $row;
            }
            $template_vars['inpqcount']+= count($template_vars['inprocs'][strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($k)))]['recs']);
        }

        $complete = $requests['Complete'];
        $template_vars['comrecs'] = array();
        $template_vars['comqcount']= 0;
        foreach($complete as $k => $v){
            $template_vars['comrecs'][strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($k)))]['disp'] = $k;
            $template_vars['comrecs'][strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($k)))]['suff'] = strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($k)));
            foreach($v as $row){
                $template_vars['comrecs'][strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($k)))]['recs'][] = $row;
            }
            $template_vars['comqcount']+= count($template_vars['comrecs'][strtolower(preg_replace('/[^\\w-]|_+/', '', stripslashes($k)))]['recs']);
        }

        $template_vars['temp_dir'] = sys_get_temp_dir();
    }
}
