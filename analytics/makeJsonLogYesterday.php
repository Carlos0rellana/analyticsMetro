<?php
    define('ROOT_DIR', dirname(__FILE__,2));
    require_once(ROOT_DIR.'/control/basicFunctions.php');
    require_once(ROOT_DIR.'/control/functions_analytics.php');

    $yesterday = date('Y-m-d',strtotime(date('Y-m-d').' -1 days'));
    $tomorrow = date('Y-m-d',strtotime(date('Y-m-d').' +1 days'));
    print_r(createLogOneDayTenDaysAgo($yesterday,$yesterday.'.json',$tomorrow));

?>