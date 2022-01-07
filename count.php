<?php
    
    define('ROOT_DIR', realpath(__DIR__));
    require_once(ROOT_DIR.'/control/functions.php');
    $exclude=false;
    if(isset($_GET["start"])){
        $start = $end = $_GET["start"];
        if(isset($_GET["end"])){$end=$_GET["end"];}
        if(isset($_GET["exc"]) && $_GET["exc"]==='true'){$exclude=true;}
        if(isset($_GET["site"]) && strtolower($_GET["site"])!=='all'
        ){
            echo json_encode(bucleArticlesList($_GET["site"],$start,$end));
        }
    }elseif(isset($_GET["year"]) && isset($_GET["mounth"]) && isset($_GET["site"]) && strtolower($_GET["site"])!=='all'){
        echo json_encode(bucleSaveArticlesByMounth($_GET["site"],$_GET["mounth"],$_GET["year"]));
    }elseif(isset($_GET["date"]) && isset($_GET["site"]) && strtolower($_GET["site"])!=='all'){
        echo json_encode(bucleSaveArticlesByDay( $_GET["site"], $_GET["date"] ) );
    }elseif(isset($_GET["site"]) && strtolower($_GET["site"])!=='all' && isset($_GET["year"]) ){
        echo json_encode(bucleSaveArticlesByYear($_GET["site"],$_GET["year"]));
    }elseif(isset($_GET["site"]) && strtolower($_GET["site"])==='all' && isset($_GET["year"]) ){
        echo json_encode(bucleSaveArticlesByYearAllSites($_GET["site"],$_GET["year"]));
    }else{
        echo json_encode(autoBucleWithLimit('2021-01-01'));
    }
?>