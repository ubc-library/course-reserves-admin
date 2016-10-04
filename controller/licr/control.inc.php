<?php

class Controller_licr
{

    public function __construct()
    {
        $this->model = getModel('licr');
    }

    public function getJSON($command, $params)
    {
        return $this->call($command, $params);
    }

    public function call($command, $params)
    {
        return $this->model->call($command, $params);
    }

    public function getArray($command, $params)
    {
        $res = $this->call($command, $params);
        $dec = json_decode($res, true);

        return $dec['data'];
    }

}
