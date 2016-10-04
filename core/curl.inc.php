<?php
    function curlIt($url, $options = [])
    {
        $defaults = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 15
        ];

        $ch = curl_init();

        //var_dump (json_encode (($options + $defaults)));

        curl_setopt_array($ch, ($options + $defaults));

        if (!$result = curl_exec($ch)) {
            error_log(curl_error($ch));
        }

        curl_close($ch);
        return $result;
    }

    function curlPost($url, $fields) {
        
        error_log(
            json_encode([
                'url' => $url,
                'post_data' => $fields
            ])
        );
        $response = curlIt($url, [CURLOPT_POST => count($fields), CURLOPT_POSTFIELDS => $fields]);
        return $response;
    }
