<?php
    define('ROOT_DIR', realpath(__DIR__));
    require_once(ROOT_DIR.'/control/functions.php');
    $exclude=false;
    if(isset($_GET["start"])){
        $start = $end = $_GET["start"];
        if(isset($_GET["end"])){$end=$_GET["end"];}
        if(isset($_GET["exc"]) && $_GET["exc"]==='true'){$exclude=true;}
        if(isset($_GET["site"])){
            echo json_encode(countResults($_GET["site"],$start,$end,$exclude));
        }else{
            echo json_encode(bucleCounts($start,$end,$exclude));
        }
    }
?>