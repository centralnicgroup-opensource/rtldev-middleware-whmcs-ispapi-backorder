<?php
require_once dirname(__FILE__)."/../../../../init.php";
require_once dirname(__FILE__)."/../backend/api.php";


$command = array(
		"COMMAND" => "GetAvailableFunds",
		"USER" => 2
);
$result = backorder_backend_api_call($command);

echo "<pre>";
print_r($result);


echo date("Y-m-d H:i:s")." BATCH_TEST done.\n";
?>
