<?php
    define('ROOT_DIR',  dirname(__FILE__,2));
    require_once(ROOT_DIR.'/control/basicFunctions.php');
    require_once(ROOT_DIR.'/control/functions_analytics.php');

    //$yesterday = date('Y-m-d',strtotime(date('Y-m-d').' -1 days'));
    $today = date('Y-m-d');

    //searchOnGoogleOneDayTenDaysAgo($yesterday,$yesterday.'.json',$today);
    $fileLogStatus=ROOT_DIR.'/logs/analytics/dailyPoll/'.$today.'/status.json';
    //echo('Carpeta de busqueda:::'.$today.'\n');
    if(file_exists($fileLogStatus)){
        
        $tempCheckList = json_decode(file_get_contents($fileLogStatus),true);
        if($tempCheckList[array_key_first($tempCheckList)]['progres']===0){
            $tempCheckList[array_key_first($tempCheckList)]['current']=true;
        }
        foreach ($tempCheckList as $key => $value) {
            if($value['current']){
                //echo('Día de busqueda:::'.$key.'\n');
                searchOnGoogleOneDayTenDaysAgo($key,$key.'.json',$today);
            }
        }
    }
    
?>