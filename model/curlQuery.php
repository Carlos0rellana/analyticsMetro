<?php

    function cUrlQuery($queryArray) {
        $curl = curl_init();
        curl_setopt_array($curl,$queryArray);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    function httpResponses($url){
        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($handle);
        $httpCode = curl_getinfo($handle,CURLINFO_HTTP_CODE);
        curl_close($handle);
        return $httpCode;
    }

    function httpGETResponses($url){
        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($handle, CURLOPT_HEADER, 0);
        $response = curl_exec($handle);
        curl_close($handle);
        return $response;
    }

?>