<?php
    session_start();
    require('../core/init.php');
    require('../core/db.inc.php');
    require('../core/utility.inc.php');
    require('../core/authentication.php');
    require("../vendor/autoload.php");

    $config = Config::getAll();


    parse_query_string();

    $action = gv('action', 'home');
    $method = FALSE;
    if (strpos($action, '.') !== FALSE) {
        list ($action, $method) = explode('.', $action, 2);
    }

    $strictAuth = TRUE;

    if ($_SERVER ['REQUEST_METHOD'] === 'POST'
        && (
            isset ($_POST ['initiator'])
            && (
                $_POST ['initiator'] === 'connect'
                || $_POST ['initiator'] === 'client'
            )
        )
        && $action === 'docstore') {
        $strictAuth = FALSE; // will auth on puid instead
    }

    if ($strictAuth) {
        if ($action !== 'staticfile' && !logged_in()) {
            if (Config::get('authentication') || sv('authentication')) {
                if ($action !== 'login') {
                    ssv('pending_request', $_GET);
                    if (0 === count($_POST)) {
                        ssv('pending_post', $_POST);
                    }
                    redirect('/login.form');
                }
            }
        }
        if (logged_in() && $g = sv('pending_request')) {
            ssv('pending_request', FALSE);
            redirect_get($g);
        }
    }

    switch ($action) {
        case 'staticfile' :
            $static_server = getController('staticfile');
            $static_server->staticfile();
        break;
        case 'licr' :
        case 'voyager' :
            $controller = getController($action);
            $params = $_GET;
            unset ($params ['action']);
            $result = $controller->getJSON($method, $params);
            return_json($result);
        break;
        default :
            // TODO include controller
            $template_data = array();
            $controller = getController($action);
            if ($controller) {
                $controlfn = $method ? $method : $action;
                if (!method_exists($controller, $controlfn)) {
                    if (method_exists($controller, 'call')) {
                        $params = $_GET;
                        unset ($params ['action']);
                        $template_data = $controller->call($controlfn, $params);
                    } else {
                        die ("No method exists Control_$action::$controlfn");
                    }
                } else {
                    $template_data = $controller->$controlfn ();
                }
            } else {
                error_log("no controller for $action");
            }
            if (!empty ($template_data ['controller_error'])) {
                render_normal('error', NULL, $template_data);
            } else {
                require_once('../core/idboxapi.inc.php');
                $template_data ['role_admin'] = idboxCall('InGroup', array(
                    'group_name' => 'CR-Admin',
                    'puid'       => sv('puid')
                )) ? TRUE : FALSE;
                $template_data ['role_programadmin'] = idboxCall('InGroup', array(
                    'group_name' => 'CR-ProgramAdmin',
                    'puid'       => sv('puid')
                )) ? TRUE : FALSE;

                $template = empty ($template_data ['template']) ? NULL : $template_data ['template'];
                render_normal($action, $template, $template_data);
            }
    }
