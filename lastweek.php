<?php
    define('ROOT_DIR', realpath(__DIR__));
    require_once(ROOT_DIR.'/control/functions.php');
    $exclude=false;
    echo json_encode(autoBucleLastWeekMode());
?>