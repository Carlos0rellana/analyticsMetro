<?php
    define('ROOT_DIR', dirname(__FILE__,2));
    require_once(ROOT_DIR.'/control/basicFunctions.php');
    require_once(ROOT_DIR.'/control/functions_analytics_404.php');
    $http = false;
    $statusSite = false;
    $error = array();
    $error['error'] = array();
    

    if(isset($_GET["start"])){
        $start = $end = $_GET["start"];
        $statusStart = $statusEnd = true;
        if(!validateDate($start)){
            $statusStart = $statusEnd = false;
            array_push($error['error'],' -> Not format for START date');
        }
    }else{
        $start = $end = date('Y-m-d');
    }
    if(isset($_GET["end"])){
        $end=$_GET["end"];
        $statusEnd = true;
        if(!validateDate($end)){
            $statusEnd = false;
            array_push($error['error'],' -> Not format for END date');
        }
    }

    if(isset($_GET["http"]) && $_GET["http"]==='true'){$http=true;}

    if(isset($_GET["site"])){
        $statusSite = true;
        if(verifySite($_GET["site"])){
            $site = $_GET["site"];
        }else{
            $statusSite = false;
            array_push($error['error'],' -> Not found for SITE ID');
        }
    }else{
        array_push($error['error'],' -> Not found SITE ID');
    }

    if($statusSite) {
        echo(json_encode(getNotFoundList($site,$start,$end,$http)));
    }else{
        echo(json_encode($error));
    }
    
?>