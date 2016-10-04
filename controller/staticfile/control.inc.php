<?php

    class Controller_staticfile
    {
        public function staticfile()
        {
            ob_end_clean();
            ob_start();
            $view = gv('view');
            $resource = gv('res');
            $file = Config::get('approot') . '/view/' . $view . '/' . $resource;

//            error_log('Trying to get '.$file);
            if (file_exists($file)) {
                if (preg_match('/\.css$/', $resource)) {
                    header('Content-type: text/css');
                }
                if (preg_match('/\.js$/', $resource)) {
                    header('Content-type: text/javascript');
                }
                header('Content-length: ' . filesize($file));
                readfile($file);
            } else {
                header('HTTP/1.0 404 Not Found');
            }
            ob_end_flush();
            exit();
        }
    }
