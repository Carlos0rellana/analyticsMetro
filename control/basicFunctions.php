<?php
    function checkFileOrJsonCreate($url,$data=null){
        if(file_exists($url) && $data==null){
            return $url;
        }else if($data!=null){
            $text = '';
            if(is_array($data) || is_object($data)){
                $text = json_encode($data);
            }else{
                $text = json_encode('content:'.$data);
            }
            writeFile($url,$text);
            return $url;
        }
        return false;
    }

    function writeFile($fileUrl,$data,$mode='w'){
        $fp = fopen($fileUrl,$mode);      
        fwrite($fp,$data);
        fclose($fp);
    }

    function createFolder($url){
        if (!file_exists($url)) {
            mkdir($url, 0777, true);
        }
    }
    function validateDate($date, $format = 'Y-m-d'){
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
    function printResults($reports){
        echo(json_encode($reports));
    }
    function timeCount($sec){
        switch ($sec) {
            case $sec>60:
                $sec = ($sec/60).'min';
                break;
            case $sec>3600:
                $sec = ($sec/3600).'hrs';
                break;
            case $sec>86400:
                $sec = ($sec/86400).'días';
                break;
        }
        return $sec;
    }
    function verifySite($name){
        $listSites = json_decode(file_get_contents(ROOT_DIR.'/data/sites.json'),true);
        return(array_key_exists($name,$listSites));
    }
?>