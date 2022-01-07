<?php
    define('ROOT_DIR', realpath(__DIR__));
    require_once(ROOT_DIR.'/control/basicFunctions.php');
    require_once(ROOT_DIR.'/control/functions_analytics.php');
    require_once(ROOT_DIR.'/model/conection-query-mysql.php');

    $yesterday = date('Y-m-d',strtotime(date('Y-m-d').' -1 days'));
    createLogOneDayTenDaysAgo($yesterday,$yesterday.'.json');
    
?>