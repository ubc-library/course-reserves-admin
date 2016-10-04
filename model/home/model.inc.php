<?php

    class Model_home {

        public function __construct ()
        {
            $this->newByStatus = ["copyright"];
        }

        public function getNewRequests ($arr, $statuses = null, $isAllData = false, $view = 'default')
        {
            if (in_array ($view, $this->newByStatus)) {
                $q = $this->getArrayByStatus ($arr, $statuses);
                $count = 0;
                foreach ($q as $status_category => $data) {
                    foreach ($data['recs'] as $r => $row) {
                        $q[$status_category]['types'][$row['type_id']] = true;
                    }
                    $count += count ($data['recs']);
                }
                $__u = getModel ('utility');
                $__u->setMenuNewItems ($count);

                return [
                    'records' => $q,
                    'count'   => $count];
            }

            return $this->getArrayByType ($arr, $statuses, $isAllData);
        }

        public function getInProcessRequests ($arr, $statuses = null, $view = 'default')
        {

            $recs = $this->getArrayByStatus ($arr, $statuses);

            $licr = getModel ('licr');
            $count = 0;
            foreach ($recs as $k => $v) {
                $next = $licr->getArray ('GetNextStatuses', ['status' => $v['status_id']], true);
                foreach ($next as $key => $val) {
                    $recs[$k]['next_statuses'][$key] = $val['status'];
                }
                $count += count ($v['recs']);
            }

            return [
                'records' => $recs,
                'count'   => $count];
        }

        public function getCompleteRequests ($arr, $statuses = null, $view = 'default')
        {

            return $this->getArrayByType ($arr, $statuses, false);
        }

        private function convertCourseID ($item)
        {

        }

        private function setNewItemCount ($count)
        {
            MC::set ('newitemcount', $count, 7200);
        }

        private function createKey ($string)
        {
            return strtolower (preg_replace ('/[^\\w-]|_+/', '', stripslashes ($string)));
        }

        private function getArrayByType ($arr, $statuses = null, $isAllData = false)
        {
            $recs = [];
            $count = 0;
            if (is_array ($arr)) {
                foreach ($arr as $k => $v) {
                    $row_tid = -1;
                    $key = $this->createKey($k);
                    $recs[$key]['disp'] = $k;
                    $recs[$key]['suff'] = $key;
                    foreach ($v as $row) {
                        if (in_array($row['status_id'], $statuses)) {
                            $recs[$key]['recs'][] = $row;
                            $recs[$key]['types'][$row['type_id']] = true;
                            $row_tid = $row['type_id'];
                        }
                    }
                    if (isset($key) && !isset($recs[$key]['recs'])) {
                        unset($recs[$key]);
                    } else {
                        $count += count($recs[$key]['recs']);
                    }
                    if ($row_tid === "2") {
                        $recs[$key]['recs'] = $this->orderByFormat($recs[$key]['recs']);
                    }
                }
                if ($isAllData) {
                    $this->setNewItemCount($count);
                }
            }

            return [
                'records' => $recs,
                'count'   => $count];
        }

        private function getArrayByStatus ($arr, $statuses = null)
        {
            $recs = [];

            if (!is_array ($arr)) {
                error_log ("No array passed. " . __FILE__ . " @ " . __LINE__);

                return $recs;
            }

            foreach ($arr as $k => $v) {
                foreach ($v as $row) {
                    if (in_array ($row['status_id'], $statuses)) {
                        $key = $this->createKey ($row['status']);
                        if (!isset($recs[$key])) {
                            $recs[$key]['disp'] = $row['status'];
                            $recs[$key]['status_id'] = $row['status_id'];
                            $recs[$key]['suff'] = $key;
                        }
                        $row['type'] = $k;
                        $recs[$key]['recs'][] = $row;
                    }
                }
                if (isset($key) && !isset($recs[$key]['recs'])) {
                    unset($recs[$key]);
                }
            }

            return $recs;
        }

        private function orderByFormat ($arr)
        {
            $formats = [];
            foreach ($arr as $item) {
                if (!in_array ($item['physical_format'], $formats)) {
                    array_push ($formats, $item['physical_format']);
                }
            }

            $temp = [];
            foreach ($formats as $key) {
                foreach ($arr as $item) {
                    if ($item['physical_format'] == $key) {
                        if ($key == null) {
                            $item['physical_format'] = 'undetermined';
                            $temp['_undetermined'][] = $item;
                        } else {
                            $temp[$key][] = $item;
                        }
                    }
                }
            }

            ksort ($temp);
            $arr = [];
            foreach ($temp as $format => $items) {
                foreach ($items as $item) {
                    array_push ($arr, $item);
                }
            }

            return $arr;
        }
    }
