<?php
// This proxies calls to LICR for the benefit of IE8 which apparently doesn't do CORS
    require_once('../core/init.php');
    if (
        empty ($_SERVER ['HTTP_REFERER'])
        || strpos($_SERVER ['HTTP_REFERER'], Config::get('baseurl')) !== 0
    ) {
        error_log('ptl: bad/no referer');
        die ();
    }
    require_once(Config::get('approot') . '/core/utility.inc.php');
    session_start();
    if (!isset ($_SESSION ['isStaff'])) {
        require_once(Config::get('approot') . '/core/idboxapi.inc.php');
        $isStaff = idboxCall('InGroup', array(
            'puid'       => sv('puid'),
            'group_name' => Config::get('idbox_group_access')
        ));
        ssv('isStaff', ($isStaff ? 'yes' : 'no'));
    }
    $get = $_GET;
    $get['activeuser'] = $_SESSION['puid'];
    if (sv('isStaff') === 'yes') {
        $get ['vtimestamp'] = ( string )date('U');
        $get ['vrand'] = ( string )mt_rand();
        ksort($get);
        foreach ($get as $k => $v) {
            $get [$k] = ( string )$v;
        }
        $verification = sha1(Config::get('secret') . serialize($get));
        $get ['verification'] = $verification;
    }

    $res = curlPost(Config::get('licr_api'), $get);
    header('Content-type: text/javascript');
    header('Content-Length: ' . strlen($res));
    echo $res;
    exit ();
