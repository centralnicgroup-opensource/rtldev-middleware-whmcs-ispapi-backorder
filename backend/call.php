<?php 
include "api.php";
$command = array_change_key_case($_POST,CASE_UPPER);
echo json_encode(backorder_api_call($command));
?>
