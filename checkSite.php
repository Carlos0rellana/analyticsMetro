<?php
        define('ROOT_DIR', realpath(__DIR__));
        require_once(ROOT_DIR.'/control/extraFunctions.php');
        if(isset($_GET["site"]) && $_GET["year"]){
                if(isset($_GET["type"]) && $_GET["type"]==="json"){
                        echo json_encode(bucleMonth($_GET["year"],$_GET["site"]));
                }else{
                        print_r(makeHtmlTable(bucleMonth($_GET["year"],$_GET["site"]),$_GET["site"]));
                }
        }
?>