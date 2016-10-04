<?php

class Controller_linkchecker
{
    public function linkchecker()
    {
        $licr = getModel('licr');
        $broken = $licr->getArray('ListBrokenItems');
        $types = $licr->getArray('ListTypes');
        $bibdata = getModel('bibdata');
        foreach ($broken as $item_id => $data) {
            $bd = $bibdata->getBibdata($data['bibdata']);
            $broken[$item_id]['bibdata'] = array();
            foreach ($bd['bibdata'] as $k => $v) {
                if (($k !== 'item_uri') && trim($v)) {
                    $broken[$item_id]['bibdata'][$bd['fieldtitles'][$k]] = $v;
                }
            }
        }
        return array('count' => count($broken), 'items' => $broken, 'types' => $types);
    }

    public function dontcheck()
    {
        $itemid = gv('item');
        $dc = gv('set');
        $licr = getModel('licr');
        $licr->getArray('SetItemDoNotCheck', array('item_id' => $itemid, 'boolean' => $dc));
        exit();
    }

    public function update()
    {
        $itemid = pv('itemid');
        $url = pv('url');
        $licr = getModel('licr');
        $res = $licr->getArray('SetItemURI', array('item_id' => $itemid, 'uri' => $url));
        return_json($res);
    }
}
