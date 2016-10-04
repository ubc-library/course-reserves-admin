<?php

class Model_utility
{

    public function resetMemcache()
    {
        $p = $this->getPickslip();
        MC::flush();
        $this->setPickslip($p);
    }
    
    /**
     * @return mixed
     */
    public function getMenuBrokenLinks()
    {
        $licr = getModel('licr');
        return $licr->getArray('CountBrokenItems');
    }

    public function getMenuNewItems()
    {
        if (!($count = MC::get('newitemcount'))) {
            if (MC::getResultCode() === Memcached::RES_NOTFOUND) {
                $count = 0;
            }
        }
        return $count;
    }

    public function setMenuNewItems($count)
    {
        MC::set('newitemcount', $count, 1800);
        return $count;
    }

    public function getDiskSpace()
    {
        $key = 'diskspace';
        if (!($used = MC::get($key))) {
            if (MC::getResultCode() === Memcached::RES_NOTFOUND) {
                $df = disk_free_space("/");
                $ds = disk_total_space("/");
                $used = (100 - round(($df / $ds) * 100));
                MC::set($key, $used, 1800);
            }
        }
        return $used;
    }

    public function getList($key, $command)
    {
        if (!($list = MC::get($key))) {
            $licr = getModel('licr');
            if (MC::getResultCode() === Memcached::RES_NOTFOUND) {
                $list = $licr->getArray($command);
                MC::set($key, $list, 7200);
            }
        }
        return $list;
    }

    public function getParsedStatuses()
    {
        $itemtypekeys = 'parsedstatuses';

        if (!($parsedTypes = MC::get($itemtypekeys))) {
            if (MC::getResultCode() === Memcached::RES_NOTFOUND) {
                $licr = getModel('licr');
                $types = $licr->getArray('ListStatuses', NULL, TRUE);
                if (isset($types)) {
                    $parsedTypes = array();
                    foreach ($types as $k => &$type) {
                        if (strpos($type['status_name'], 'ARES')) {
                        } else {
                            $parsedTypes[$type['status_id']] = array(
                                'status_id' => $type['status_id']
                            , 'status_name' => $type['status_name'],
                            );
                        }
                    }
                    unset($type);
                    MC::set($itemtypekeys, $parsedTypes, 7200);
                }
            }
        }

        return $parsedTypes;
    }

    public function getParsedItemTypes()
    {

        $itemtypekeys = 'cr_staff_parsed_item_types';

        if (!($parsedTypes = MC::get($itemtypekeys))) {
            if (MC::getResultCode() === Memcached::RES_NOTFOUND) {
                $licr = getModel('licr');
                $types = $licr->getArray('ListTypes', NULL, TRUE);
                if (isset($types)) {
                    $parsedTypes = array();
                    foreach ($types as $k => &$type) {
                        $parsedTypes[$k] = array(
                            'type_id' => $k
                        , 'name' => strtolower(preg_replace('/[^\\w]+/', '_', $type['name']))
                        , 'physical' => $type['physical']
                        , 'displayname' => $type['name'],
                        );
                    }
                    unset($type);
                }
                MC::set($itemtypekeys, $parsedTypes, 7200);
            }
        }

        return $parsedTypes;
    }

    public function getBibdata($bibdata)
    {

        $isAres = false;
        $bibdataraw = unserialize($bibdata);

        if (isset($bibdataraw['AresItem'])) {
            $bibdataraw = $bibdataraw['AresItem'];
            $isAres = true;
        }

        return array('bibdata' => $bibdataraw, 'isAres' => $isAres);
    }

    public function setPickslip($arr)
    {
        $k = 'most-recent-pickslip';
        MC::set($k, $arr, 0);//this would be set every time a pick slip is generated
    }

    public function getPickslip()
    {
        $k = 'most-recent-pickslip';
        if (!($arr = MC::get($k))) {
            if (MC::getResultCode() == Memcached::RES_NOTFOUND) {
                //technically you should never reach here as the memcached variable for this key is stored before a flush
                //and is set immediately after, making it persistent (see resetMemcache() above). but..you never know....
                //error_log('Technically you should never reach here, grep YND3487');
                $path = Config::get('approot') . '/www/barcodes';
                $time = 0;
                $slip = '';

                $d = dir($path);
                while (false !== ($entry = $d->read())) {
                    $filepath = "{$path}/{$entry}";
                    // could do also other checks than just checking whether the entry is a file
                    if (is_file($filepath) && filectime($filepath) > $time) {
                        $time = filectime($filepath);
                        $slip = $entry;
                    }
                }
                $arr = array('time' => $time, 'url' => '/barcodes/' . $slip);
                $this->setPickslip($arr);
            }
        }
        return $arr;
    }

    public function checkURIExists($url)
    {
        $exists = false;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_exec($ch);
        $errno = curl_errno($ch);
        if ($errno === 3) {
            $exists = -3;
        }
        if ($errno === 2 || $errno === 5 || $errno === 6) {
            $exists = -1;
        }
        if ($errno === 7 || $errno === 28 || $errno === 22) {
            $exists = 28;
        } else {
            $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($retcode >= 400) {
                $exists = false;
            } else if ($retcode == 200 || $retcode == 301 || $retcode == 302 || $retcode == 304 || $retcode == 307) {
                $exists = true;
            }
        }
        curl_close($ch);

        return $exists;
    }

    public function getAllIdBoxRoles()
    {
        require_once(Config::get('approot') . '/core/idboxapi.inc.php');
        $groups = idboxCall('ListAllGroups', array());
        $parsedGroups = array();
        /** @var array $groups */
        foreach ($groups as $group) {
            if (!(strpos($group, 'CR-') === false)) {
                $parsedGroups[] = $group;
            }
        }
        unset($groups);
        return $parsedGroups;
    }
    
    /**
     * @param $puid
     *
     * @return array
     */
    public function getUserIdBoxRoles($puid)
    {
        require_once Config::get('approot') . '/core/idboxapi.inc.php';
    
        $groups = idboxCall('ListGroups', array('puid' => $puid));
        
        $parsedGroups = array();
    
        /** @var array $groups */
        foreach ($groups as $group) {
            if (!(strpos($group, 'CR-') === false)) {
                $parsedGroups[] = $group;
            }
        }
        unset($groups);
        return $parsedGroups;
    }

    public function getInitArray($filename)
    {
        $key = 'crstaff_init_' . md5($filename);

        if (!($res = MC::get($key))) {
            if (MC::getResultCode() == Memcached::RES_NOTFOUND) {
                $url = Config::get('approot') . "/core/init/{$filename}.json";
                $json = file_get_contents($url);

                $json = str_replace(array("\n", "\r"), '', $json);
                $json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/', '$1"$3":', $json);
                $json = preg_replace('/(,)\s*}$/', '}', $json);

                $res = json_decode($json, true);
                MC::set($key, $res, 300);
            } else {
                //ignore memcache and deliver the file

                //LOCRSUPP 410 - memcache sometimes returns empty array https://jira.library.ubc.ca:8443/browse/LOCRSUPP-410
                error_log('Memcached borked, delivering file directly');
                $url = Config::get('approot') . "/core/init/{$filename}.json";
                $json = file_get_contents($url);

                $json = str_replace(array("\n", "\r"), '', $json);
                $json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/', '$1"$3":', $json);
                $json = preg_replace('/(,)\s*}$/', '}', $json);

                $res = json_decode($json, true);
            }
        }
        return $res;
    }
}
