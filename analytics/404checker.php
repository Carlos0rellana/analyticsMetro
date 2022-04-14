<?php
    define('ROOT_DIR', dirname(__FILE__,2));
    require_once(ROOT_DIR.'/control/basicFunctions.php');
    require_once(ROOT_DIR.'/control/functions_analytics_404.php');
    $http = false;
    $statusSite = false;
    $error = array();
    $error['error'] = array();

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
        $currentData = getNotFoundList($site,$http);
        $finale = array();
        //$finale['site']=$site;
        $finale['qty'] =count($currentData);
        if(count($currentData)>0){
            $finale['results']=$currentData;
        }
        echo(json_encode($finale));
    }else{
        echo(json_encode($error));
    }
    
?>