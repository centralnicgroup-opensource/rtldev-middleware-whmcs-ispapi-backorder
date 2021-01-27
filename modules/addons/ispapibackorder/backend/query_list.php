<?php

require_once 'api.php';
$command = array_change_key_case($_POST, CASE_UPPER);
echo json_encode(backorder_api_query_list($command));
