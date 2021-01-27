<?php

date_default_timezone_set('UTC');
$cronname = "BATCH_TEST";
require_once dirname(__FILE__) . "/../backend/api.php";

// $command = array(
//         "COMMAND" => "QueryBackorderList",
// );
// $r = backorder_api_call($command);
// echo "<pre>";
// print_r($r);
// echo "</pre>";

echo date("Y-m-d H:i:s") . " $cronname done.\n";
